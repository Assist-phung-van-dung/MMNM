<?php
/**
 * Logic dùng chung cho render.php của block nntm/cong-tu.
 *
 * render.php của block bị lõi WordPress `require` (KHÔNG PHẢI `require_once`)
 * mỗi lần khối này render (xem register_block_type_from_metadata() ->
 * render_block()) — nạp hàm thẳng trong render.php sẽ chết "Cannot redeclare
 * function" nếu khối render lần thứ hai trong cùng request (ví dụ
 * ServerSideRender trong trình soạn thảo). Đúng khuôn
 * blocks/rank-card/inc/render-rank-card.php.
 *
 * @package NNTM
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'nntm_congtu_block_resolve_program' ) ) {
	/**
	 * Xác định chương trình cần hiển thị: programId=0 → tự lấy chương trình
	 * đang mở (nntm_program_hien_tai()); khác 0 → dùng đúng ID đó (kể cả
	 * chương trình đã đóng — BQT có thể muốn "chốt sổ" hiển thị số cuối cùng
	 * của một đợt đã kết thúc).
	 *
	 * @param int $program_id Giá trị attribute "programId".
	 * @return WP_Post|null
	 */
	function nntm_congtu_block_resolve_program( int $program_id ): ?WP_Post {
		if ( $program_id > 0 ) {
			$post = get_post( $program_id );
			return ( $post instanceof WP_Post && 'nntm_program' === $post->post_type ) ? $post : null;
		}

		return function_exists( 'nntm_program_hien_tai' ) ? nntm_program_hien_tai() : null;
	}
}

if ( ! function_exists( 'nntm_congtu_block_dinh_dang_so' ) ) {
	/**
	 * Định dạng số kiểu Việt Nam (dấu chấm ngăn nghìn). Không phụ thuộc
	 * inc/cong-tu.php của theme (nntm_congtu_dinh_dang_so()) để block vẫn tự
	 * đứng vững nếu file đó vì lý do gì chưa được nạp — cùng công thức.
	 *
	 * @param int $n Số cần định dạng.
	 * @return string
	 */
	function nntm_congtu_block_dinh_dang_so( int $n ): string {
		return number_format( $n, 0, ',', '.' );
	}
}

if ( ! function_exists( 'nntm_congtu_block_phan_tram_hien_thi' ) ) {
	/**
	 * Phần trăm hiển thị (làm tròn, CHẶN Ở 100) — dùng cho cả số hiển thị lẫn
	 * độ rộng thanh tiến trình. Số THẬT (cam_ket/thuc_hien) không hề bị đụng
	 * tới ở đây, chỉ mỗi tỉ lệ hiển thị này bị chặn — đúng chốt nghiệp vụ
	 * "vượt cam kết thì số thật giữ nguyên, chỉ thanh tiến trình chặn ở 100%".
	 *
	 * @param float $tien_trinh Tỉ lệ thô (0..N), N có thể > 1 khi vượt cam kết.
	 * @return int 0..100.
	 */
	function nntm_congtu_block_phan_tram_hien_thi( float $tien_trinh ): int {
		$phan_tram = (int) round( $tien_trinh * 100 );
		return max( 0, min( 100, $phan_tram ) );
	}
}

if ( ! function_exists( 'nntm_congtu_block_render_thong_ke' ) ) {
	/**
	 * HTML khối "Thống Kê Của Đạo Tràng" — 3 ô: tổng cam kết, tổng thực hiện,
	 * tiến trình chung. Số lớn serif đỏ thẫm (--nntm-do-tham), nhãn chữ hoa nhỏ.
	 *
	 * @param WP_Post $program Chương trình đang hiển thị.
	 * @param string  $heading Tiêu đề khối.
	 * @return string HTML đã escape sẵn.
	 */
	function nntm_congtu_block_render_thong_ke( WP_Post $program, string $heading ): string {
		$tong = function_exists( 'nntm_kpi_tong_chuong_trinh' )
			? nntm_kpi_tong_chuong_trinh( $program->ID )
			: array( 'cam_ket' => 0, 'thuc_hien' => 0, 'so_nguoi' => 0, 'tien_trinh' => 0.0 );

		$phan_tram = nntm_congtu_block_phan_tram_hien_thi( (float) $tong['tien_trinh'] );

		ob_start();
		?>
		<?php
		/*
		 * SỬA 14/08/2026 (phản hồi chủ dự án "khác xa thiết kế — xem lại kỹ
		 * phần dưới"): tiêu đề "Thống Kê Của Đạo Tràng" chuyển ra NGOÀI thẻ
		 * trắng (trước đây nằm TRONG .thong-ke, cùng khối với box-shadow
		 * trắng). Ảnh giao diện cho thấy tiêu đề màu TRẮNG nằm trên nền vàng
		 * của cả dải, còn thẻ trắng bên dưới chỉ cao ~135px chứa đúng 3 cột
		 * số — nếu để tiêu đề (chữ trắng) lọt vào thẻ trắng sẽ không đọc
		 * được (trắng trên trắng) và thẻ sẽ cao hơn 135 rất nhiều.
		 */
			if ( '' !== trim( wp_strip_all_tags( $heading ) ) ) :
				?>
			<h2 class="nntm-cong-tu__thong-ke-heading"><?php echo esc_html( $heading ); ?></h2>
			<?php endif; ?>
		<div class="nntm-cong-tu__thong-ke">
			<div class="nntm-cong-tu__thong-ke-grid">
				<div class="nntm-cong-tu__o">
					<p class="nntm-cong-tu__o-so"><?php echo esc_html( nntm_congtu_block_dinh_dang_so( (int) $tong['cam_ket'] ) ); ?></p>
					<p class="nntm-cong-tu__o-nhan"><?php esc_html_e( 'Tổng số chuỗi trì đã đăng ký', 'nntm' ); ?></p>
				</div>
				<div class="nntm-cong-tu__o">
					<p class="nntm-cong-tu__o-so"><?php echo esc_html( nntm_congtu_block_dinh_dang_so( (int) $tong['thuc_hien'] ) ); ?></p>
					<p class="nntm-cong-tu__o-nhan"><?php esc_html_e( 'Tổng số chuỗi trì đã thực hiện', 'nntm' ); ?></p>
				</div>
				<div class="nntm-cong-tu__o nntm-cong-tu__o--tien-trinh">
					<?php
					/*
					 * SỬA 14/08/2026: cột 3 — ảnh giao diện đặt THANH TIẾN TRÌNH
					 * NHỎ nằm BÊN TRÁI con số %, CÙNG MỘT HÀNG (trước đây thanh
					 * nằm phía DƯỚI, tách dòng riêng). Bọc chung một hàng
					 * ".o-hang" để CSS xếp ngang được mà không phải đổi ý nghĩa
					 * ngữ nghĩa của các phần tử con.
					 */
					?>
					<div class="nntm-cong-tu__o-hang">
						<div class="nntm-cong-tu__thanh nntm-cong-tu__thanh--nho" role="progressbar" aria-valuenow="<?php echo esc_attr( (string) $phan_tram ); ?>" aria-valuemin="0" aria-valuemax="100">
							<div class="nntm-cong-tu__thanh-fill" style="width:<?php echo esc_attr( (string) $phan_tram ); ?>%"></div>
						</div>
						<p class="nntm-cong-tu__o-so"><?php echo esc_html( (string) $phan_tram ); ?>%</p>
					</div>
					<p class="nntm-cong-tu__o-nhan"><?php esc_html_e( 'Tiến trình chung của đạo tràng', 'nntm' ); ?></p>
				</div>
			</div>
		</div>
		<?php
		return (string) ob_get_clean();
	}
}

if ( ! function_exists( 'nntm_congtu_block_render_hang' ) ) {
	/**
	 * Một dòng <tr> của bảng xếp hạng.
	 *
	 * @param array $dong    Một phần tử trả về từ nntm_kpi_bang_xep_hang()/tự ráp cho dòng ghim.
	 * @param bool  $la_minh Có phải dòng của người đang xem hay không.
	 * @return string HTML đã escape sẵn.
	 */
	function nntm_congtu_block_render_hang( array $dong, bool $la_minh = false ): string {
		$phan_tram = nntm_congtu_block_phan_tram_hien_thi( (float) ( $dong['tien_trinh'] ?? 0.0 ) );
		$phap_danh = '' !== trim( (string) ( $dong['phap_danh'] ?? '' ) ) ? $dong['phap_danh'] : __( '(chưa đặt pháp danh)', 'nntm' );

		ob_start();
		?>
		<tr class="nntm-cong-tu__bxh-row<?php echo $la_minh ? ' nntm-cong-tu__bxh-row--minh' : ''; ?>">
			<td class="nntm-cong-tu__bxh-hang"><?php echo esc_html( (string) ( $dong['hang'] ?? '—' ) ); ?></td>
			<td class="nntm-cong-tu__bxh-nguoi">
				<img class="nntm-cong-tu__bxh-avatar" src="<?php echo esc_url( (string) ( $dong['avatar'] ?? '' ) ); ?>" alt="" width="32" height="32" loading="lazy" />
				<span class="nntm-cong-tu__bxh-phap-danh"><?php echo esc_html( $phap_danh ); ?><?php echo $la_minh ? ' <em>(' . esc_html__( 'bạn', 'nntm' ) . ')</em>' : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- da esc_html() truoc do. ?></span>
			</td>
			<td class="nntm-cong-tu__bxh-vung-mien"><?php echo esc_html( (string) ( $dong['vung_mien'] ?? '' ) ); ?></td>
			<td class="nntm-cong-tu__bxh-so"><?php echo esc_html( nntm_congtu_block_dinh_dang_so( (int) ( $dong['thuc_hien'] ?? 0 ) ) ); ?></td>
			<td class="nntm-cong-tu__bxh-tien-trinh">
				<div class="nntm-cong-tu__thanh nntm-cong-tu__thanh--nho" role="progressbar" aria-valuenow="<?php echo esc_attr( (string) $phan_tram ); ?>" aria-valuemin="0" aria-valuemax="100">
					<div class="nntm-cong-tu__thanh-fill" style="width:<?php echo esc_attr( (string) $phan_tram ); ?>%"></div>
				</div>
				<span class="nntm-cong-tu__bxh-phan-tram"><?php echo esc_html( (string) $phan_tram ); ?>%</span>
			</td>
		</tr>
		<?php
		return (string) ob_get_clean();
	}
}

if ( ! function_exists( 'nntm_congtu_block_render_bxh' ) ) {
	/**
	 * HTML "Bảng Xếp Hạng Cá Nhân" — bảng <table> thật (có <caption> ẩn cho
	 * trình đọc màn hình), đầu bảng nền đỏ thẫm chữ trắng in hoa. Công khai
	 * cho cả khách chưa đăng nhập (khảo sát câu 28) — chỉ ẩn dòng ghim "của
	 * bạn" khi chưa đăng nhập. Người đang xem rơi ngoài $limit vẫn được ghim
	 * thêm một dòng riêng ở cuối bảng.
	 *
	 * @param WP_Post $program Chương trình đang hiển thị.
	 * @param string  $heading Tiêu đề khối.
	 * @param int     $limit   Số dòng tối đa lấy từ nntm_kpi_bang_xep_hang().
	 * @return string HTML đã escape sẵn.
	 */
	function nntm_congtu_block_render_bxh( WP_Post $program, string $heading, int $limit ): string {
		$bxh = function_exists( 'nntm_kpi_bang_xep_hang' ) ? nntm_kpi_bang_xep_hang( $program->ID, $limit ) : array();

		if ( empty( $bxh ) ) {
			ob_start();
			?>
			<div class="nntm-cong-tu__bxh">
				<?php if ( '' !== trim( wp_strip_all_tags( $heading ) ) ) : ?>
					<h2 class="nntm-cong-tu__bxh-heading"><?php echo esc_html( $heading ); ?></h2>
				<?php endif; ?>
				<p class="nntm-cong-tu__bxh-rong"><?php esc_html_e( 'Chưa có ai tham gia chương trình này.', 'nntm' ); ?></p>
			</div>
			<?php
			return (string) ob_get_clean();
		}

		// Dòng ghim "của bạn" — chỉ khi đã đăng nhập, đã tham gia, và KHÔNG
		// đã nằm sẵn trong $bxh (nghĩa là rơi ngoài $limit).
		$dong_ghim = null;
		if ( is_user_logged_in() && function_exists( 'nntm_kpi_hang_cua_nguoi' ) ) {
			$user_id       = get_current_user_id();
			$da_trong_bang = false;
			foreach ( $bxh as $dong ) {
				if ( (int) $dong['user_id'] === $user_id ) {
					$da_trong_bang = true;
					break;
				}
			}

			if ( ! $da_trong_bang ) {
				$hang = nntm_kpi_hang_cua_nguoi( $program->ID, $user_id );
				if ( null !== $hang && function_exists( 'nntm_kpi_tong_cua_nguoi' ) && function_exists( 'nntm_kpi_phap_danh_va_vung_mien' ) ) {
					$tong_minh   = nntm_kpi_tong_cua_nguoi( $program->ID, $user_id );
					$thong_tin   = nntm_kpi_phap_danh_va_vung_mien( array( $user_id ) );
					$dong_ghim   = array(
						'hang'       => $hang,
						'user_id'    => $user_id,
						'phap_danh'  => $thong_tin[ $user_id ]['phap_danh'] ?? '',
						'vung_mien'  => $thong_tin[ $user_id ]['vung_mien'] ?? '',
						'avatar'     => get_avatar_url( $user_id ),
						'thuc_hien'  => $tong_minh['thuc_hien'],
						'cam_ket'    => $tong_minh['cam_ket'],
						'tien_trinh' => $tong_minh['tien_trinh'],
					);
				}
			}
		}

		$user_id_dang_xem = is_user_logged_in() ? get_current_user_id() : 0;

		ob_start();
		?>
		<div class="nntm-cong-tu__bxh">
			<?php if ( '' !== trim( wp_strip_all_tags( $heading ) ) ) : ?>
				<h2 class="nntm-cong-tu__bxh-heading"><?php echo esc_html( $heading ); ?></h2>
			<?php endif; ?>

			<div class="nntm-cong-tu__bxh-cuon">
				<table class="nntm-cong-tu__bxh-bang">
					<caption class="nntm-sr-only"><?php echo esc_html( $heading ? $heading : __( 'Bảng xếp hạng cá nhân', 'nntm' ) ); ?></caption>
					<thead>
						<tr>
							<th scope="col" class="nntm-sr-only"><?php esc_html_e( 'Hạng', 'nntm' ); ?></th>
							<th scope="col"><?php esc_html_e( 'Pháp danh', 'nntm' ); ?></th>
							<th scope="col"><?php esc_html_e( 'Vùng miền', 'nntm' ); ?></th>
							<th scope="col"><?php esc_html_e( 'Số chuỗi trì', 'nntm' ); ?></th>
							<th scope="col"><?php esc_html_e( 'Tiến trình', 'nntm' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $bxh as $dong ) : ?>
							<?php echo nntm_congtu_block_render_hang( $dong, $user_id_dang_xem === (int) $dong['user_id'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- ham con da tu esc. ?>
						<?php endforeach; ?>

						<?php if ( null !== $dong_ghim ) : ?>
							<tr class="nntm-cong-tu__bxh-ngan-cach" aria-hidden="true"><td colspan="5">⋯</td></tr>
							<?php echo nntm_congtu_block_render_hang( $dong_ghim, true ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- ham con da tu esc. ?>
						<?php endif; ?>
					</tbody>
				</table>
			</div>
		</div>
		<?php
		return (string) ob_get_clean();
	}
}
