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
	 * Phần trăm hiển thị (làm tròn) — KHÔNG chặn trần.
	 *
	 * ĐỔI 21/08/2026 theo yêu cầu chủ dự án: "tiến trình có thể cho phép vượt
	 * 100%, hiện tại anh thấy quá nhưng vẫn không thấy tính hơn 100%". Trước
	 * đây con số bị chặn ở 100 nên trì 50/25 chuỗi vẫn chỉ hiện "100%" —
	 * người trì vượt cam kết không thấy được công của mình. Nay số hiện đúng
	 * tỉ lệ thật (vd 200%).
	 *
	 * Chỉ ĐỘ RỘNG thanh vẫn phải chặn ở 100 — xem
	 * nntm_congtu_block_be_rong_thanh(): một thanh không thể dài hơn cái máng
	 * chứa nó.
	 *
	 * @param float $tien_trinh Tỉ lệ thô (0..N), N có thể > 1 khi vượt cam kết.
	 * @return int Phần trăm đã làm tròn, tối thiểu 0, KHÔNG có trần.
	 */
	function nntm_congtu_block_phan_tram_hien_thi( float $tien_trinh ): int {
		return max( 0, (int) round( $tien_trinh * 100 ) );
	}
}

if ( ! function_exists( 'nntm_congtu_block_be_rong_thanh' ) ) {
	/**
	 * Bề rộng (%) của thanh tiến trình — CHẶN Ở 100.
	 *
	 * Tách khỏi nntm_congtu_block_phan_tram_hien_thi() từ 21/08/2026: con số
	 * chữ được phép vượt 100%, còn thanh thì không (vẽ width:200% sẽ tràn ra
	 * khỏi máng và phá cả bảng).
	 *
	 * @param int $phan_tram Phần trăm thật (có thể > 100).
	 * @return int 0..100.
	 */
	function nntm_congtu_block_be_rong_thanh( int $phan_tram ): int {
		return max( 0, min( 100, $phan_tram ) );
	}
}

if ( ! function_exists( 'nntm_congtu_block_du_lieu_tu_log' ) ) {
	/**
	 * Đọc dữ liệu KPI trực tiếp từ sổ wp_nntm_kpi_log làm phương án kiểm chứng
	 * và fallback. Đây KHÔNG phải nguồn ghi mới; chỉ đọc để UI không bị mù khi
	 * cache/plugin cũ trả mảng rỗng hoặc option aggregate bị stale.
	 *
	 * Người đã có CAM KẾT vẫn là người tham gia và phải xuất hiện trong BXH với
	 * THỰC HIỆN = 0. Quy tắc nghiệp vụ là xếp theo thuc_hien giảm dần, không có
	 * quy tắc loại bỏ người có 0 lần thực hiện.
	 *
	 * @param int $program_id ID chương trình.
	 * @param int $limit      Số dòng tối đa.
	 * @return array{tong:array,bxh:array}
	 */
	function nntm_congtu_block_du_lieu_tu_log( int $program_id, int $limit ): array {
		$empty = array(
			'tong' => array( 'cam_ket' => 0, 'thuc_hien' => 0, 'so_nguoi' => 0, 'tien_trinh' => 0.0 ),
			'bxh'  => array(),
		);

		if ( $program_id <= 0 ) {
			return $empty;
		}

		global $wpdb;
		$table = $wpdb->prefix . 'nntm_kpi_log';
		$exists = $wpdb->get_var(
			$wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table ) )
		);
		if ( $table !== $exists ) {
			return $empty;
		}

		$limit = max( 1, min( 500, $limit ) );

		// Tổng theo NGƯỜI trước rồi mới cộng toàn chương trình để so_nguoi không
		// bị nhân lên bởi việc một người ghi nhiều dòng trong ngày.
		$totals = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT
					COALESCE(SUM(x.cam_ket), 0) AS cam_ket,
					COALESCE(SUM(x.thuc_hien), 0) AS thuc_hien,
					COUNT(*) AS so_nguoi
				FROM (
					SELECT user_id,
						SUM(CASE WHEN metric = 'cam_ket' THEN value ELSE 0 END) AS cam_ket,
						SUM(CASE WHEN metric = 'thuc_hien' THEN value ELSE 0 END) AS thuc_hien
					FROM {$table}
					WHERE program_id = %d
					GROUP BY user_id
					HAVING cam_ket > 0 OR thuc_hien > 0
				) x",
				$program_id
			),
			ARRAY_A
		);

		$cam_ket   = isset( $totals['cam_ket'] ) ? (int) $totals['cam_ket'] : 0;
		$thuc_hien = isset( $totals['thuc_hien'] ) ? (int) $totals['thuc_hien'] : 0;
		$so_nguoi  = isset( $totals['so_nguoi'] ) ? (int) $totals['so_nguoi'] : 0;

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT user_id,
					SUM(CASE WHEN metric = 'cam_ket' THEN value ELSE 0 END) AS cam_ket,
					SUM(CASE WHEN metric = 'thuc_hien' THEN value ELSE 0 END) AS thuc_hien
				FROM {$table}
				WHERE program_id = %d
				GROUP BY user_id
				HAVING cam_ket > 0 OR thuc_hien > 0
				ORDER BY thuc_hien DESC, cam_ket DESC, user_id ASC
				LIMIT %d",
				$program_id,
				$limit
			),
			ARRAY_A
		);

		$user_ids = array_map( 'intval', wp_list_pluck( (array) $rows, 'user_id' ) );
		$info = array();
		if ( $user_ids && function_exists( 'nntm_kpi_phap_danh_va_vung_mien' ) ) {
			$info = (array) nntm_kpi_phap_danh_va_vung_mien( $user_ids );
		}

		$vung_options = function_exists( 'nntm_vung_mien_options' ) ? nntm_vung_mien_options() : array();
		$bxh = array();
		$hang = 0;

		foreach ( (array) $rows as $row ) {
			++$hang;
			$user_id    = (int) $row['user_id'];
			$user        = get_userdata( $user_id );
			$cam         = (int) $row['cam_ket'];
			$thuc        = (int) $row['thuc_hien'];
			$phap_danh   = trim( (string) ( $info[ $user_id ]['phap_danh'] ?? '' ) );
			$vung_mien   = trim( (string) ( $info[ $user_id ]['vung_mien'] ?? '' ) );

			if ( '' === $phap_danh ) {
				$phap_danh = trim( (string) get_user_meta( $user_id, 'nntm_phap_danh', true ) );
				if ( '' === $phap_danh && $user ) {
					$phap_danh = (string) $user->display_name;
				}
			}

			if ( '' === $vung_mien ) {
				$vung_key  = (string) get_user_meta( $user_id, 'nntm_vung_mien', true );
				$vung_mien = isset( $vung_options[ $vung_key ] ) ? (string) $vung_options[ $vung_key ] : $vung_key;
			}

			$bxh[] = array(
				'hang'       => $hang,
				'user_id'    => $user_id,
				'phap_danh'  => $phap_danh,
				'vung_mien'  => $vung_mien,
				'avatar'     => get_avatar_url( $user_id ),
				'thuc_hien'  => $thuc,
				'cam_ket'    => $cam,
				'tien_trinh' => $cam > 0 ? ( $thuc / $cam ) : 0.0,
			);
		}

		return array(
			'tong' => array(
				'cam_ket'    => $cam_ket,
				'thuc_hien'  => $thuc_hien,
				'so_nguoi'   => $so_nguoi,
				'tien_trinh' => $cam_ket > 0 ? ( $thuc_hien / $cam_ket ) : 0.0,
			),
			'bxh' => $bxh,
		);
	}
}

if ( ! function_exists( 'nntm_congtu_block_lay_du_lieu_nhat_quan' ) ) {
	/**
	 * Lấy thống kê + BXH nhất quán. Ưu tiên API plugin khi API khớp sổ log;
	 * nếu cache cũ trả rỗng/sai tổng thì tự rebuild cache và cuối cùng dùng sổ
	 * log thật làm fallback hiển thị.
	 *
	 * @param WP_Post $program Chương trình.
	 * @param int     $limit   Số dòng BXH.
	 * @return array{tong:array,bxh:array,api_ok:bool}
	 */
	function nntm_congtu_block_lay_du_lieu_nhat_quan( WP_Post $program, int $limit ): array {
		$default_tong = array( 'cam_ket' => 0, 'thuc_hien' => 0, 'so_nguoi' => 0, 'tien_trinh' => 0.0 );
		$api_tong = function_exists( 'nntm_kpi_tong_chuong_trinh' )
			? (array) nntm_kpi_tong_chuong_trinh( $program->ID )
			: $default_tong;
		$api_bxh = function_exists( 'nntm_kpi_bang_xep_hang' )
			? (array) nntm_kpi_bang_xep_hang( $program->ID, $limit )
			: array();
		$raw = nntm_congtu_block_du_lieu_tu_log( $program->ID, $limit );

		$api_tong_norm = wp_parse_args( $api_tong, $default_tong );
		$raw_tong      = $raw['tong'];
		$lech_tong =
			(int) $api_tong_norm['cam_ket'] !== (int) $raw_tong['cam_ket'] ||
			(int) $api_tong_norm['thuc_hien'] !== (int) $raw_tong['thuc_hien'] ||
			(int) $api_tong_norm['so_nguoi'] !== (int) $raw_tong['so_nguoi'];
		$bxh_bi_stale = empty( $api_bxh ) && (int) $raw_tong['thuc_hien'] > 0;

		if ( $lech_tong || $bxh_bi_stale ) {
			// Nếu plugin có API recovery thì dùng đúng API của tầng nghiệp vụ.
			if ( function_exists( 'nntm_kpi_tinh_lai_tong' ) ) {
				nntm_kpi_tinh_lai_tong( $program->ID );
			}

			if ( function_exists( 'nntm_congtu_xoa_cache_bxh' ) ) {
				nntm_congtu_xoa_cache_bxh( $program->ID );
			} else {
				delete_transient( 'nntm_kpi_bxh_' . $program->ID . '_' . $limit );
			}

			// Thử API lại sau repair. Nếu plugin cũ vẫn loại người thuc_hien=0,
			// raw['bxh'] phía dưới sẽ là fallback đúng nghĩa người tham gia.
			$api_tong = function_exists( 'nntm_kpi_tong_chuong_trinh' )
				? (array) nntm_kpi_tong_chuong_trinh( $program->ID )
				: $default_tong;
			$api_bxh = function_exists( 'nntm_kpi_bang_xep_hang' )
				? (array) nntm_kpi_bang_xep_hang( $program->ID, $limit )
				: array();
		}

		$api_tong_norm = wp_parse_args( $api_tong, $default_tong );
		$tong_final = (
			(int) $api_tong_norm['cam_ket'] === (int) $raw_tong['cam_ket'] &&
			(int) $api_tong_norm['thuc_hien'] === (int) $raw_tong['thuc_hien'] &&
			(int) $api_tong_norm['so_nguoi'] === (int) $raw_tong['so_nguoi']
		) ? $api_tong_norm : $raw_tong;

		$raw_ids = array_map( 'intval', wp_list_pluck( (array) $raw['bxh'], 'user_id' ) );
		$api_ids = array_map( 'intval', wp_list_pluck( (array) $api_bxh, 'user_id' ) );
		$api_thieu_nguoi_tham_gia = ! empty( array_diff( $raw_ids, $api_ids ) );

		return array(
			'tong'   => $tong_final,
			'bxh'    => ( empty( $api_bxh ) || $api_thieu_nguoi_tham_gia ) ? $raw['bxh'] : $api_bxh,
			'api_ok' => function_exists( 'nntm_kpi_tong_chuong_trinh' ) && function_exists( 'nntm_kpi_bang_xep_hang' ),
		);
	}
}

if ( ! function_exists( 'nntm_congtu_block_render_thong_ke' ) ) {
	/**
	 * HTML khối "Thống Kê Của Đạo Tràng" — 3 ô: tổng cam kết, tổng thực hiện,
	 * tiến trình chung. Số lớn serif đỏ thẫm (--nntm-do-tham), nhãn chữ hoa nhỏ.
	 *
	 * @param WP_Post $program Chương trình đang hiển thị.
	 * @param string     $heading Tiêu đề khối.
	 * @param array|null $tong    Dữ liệu tổng đã resolve; null thì tự lấy.
	 * @return string HTML đã escape sẵn.
	 */
	function nntm_congtu_block_render_thong_ke( WP_Post $program, string $heading, ?array $tong = null ): string {
		if ( null === $tong ) {
			$du_lieu = nntm_congtu_block_lay_du_lieu_nhat_quan( $program, 50 );
			$tong    = $du_lieu['tong'];
		}

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
						<?php
						/*
						 * aria-valuemax nới theo giá trị thật khi vượt cam kết
						 * (21/08/2026) — khai valuenow="200" mà valuemax="100"
						 * là mâu thuẫn, trình đọc màn hình sẽ tự kẹp về 100 và
						 * người dùng bàn phím lại nghe sai đúng con số mà chủ
						 * dự án muốn thấy.
						 */
						?>
						<div class="nntm-cong-tu__thanh nntm-cong-tu__thanh--nho<?php echo $phan_tram > 100 ? ' nntm-cong-tu__thanh--vuot' : ''; ?>" role="progressbar" aria-valuenow="<?php echo esc_attr( (string) $phan_tram ); ?>" aria-valuemin="0" aria-valuemax="<?php echo esc_attr( (string) max( 100, $phan_tram ) ); ?>">
							<div class="nntm-cong-tu__thanh-fill" style="width:<?php echo esc_attr( (string) nntm_congtu_block_be_rong_thanh( $phan_tram ) ); ?>%"></div>
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
				<div class="nntm-cong-tu__thanh nntm-cong-tu__thanh--nho<?php echo $phan_tram > 100 ? ' nntm-cong-tu__thanh--vuot' : ''; ?>" role="progressbar" aria-valuenow="<?php echo esc_attr( (string) $phan_tram ); ?>" aria-valuemin="0" aria-valuemax="<?php echo esc_attr( (string) max( 100, $phan_tram ) ); ?>">
					<div class="nntm-cong-tu__thanh-fill" style="width:<?php echo esc_attr( (string) nntm_congtu_block_be_rong_thanh( $phan_tram ) ); ?>%"></div>
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
	 * @param int        $limit   Số dòng tối đa lấy từ nntm_kpi_bang_xep_hang().
	 * @param array|null $bxh     Dữ liệu BXH đã resolve; null thì tự lấy.
	 * @return string HTML đã escape sẵn.
	 */
	function nntm_congtu_block_render_bxh( WP_Post $program, string $heading, int $limit, ?array $bxh = null ): string {
		if ( null === $bxh ) {
			$du_lieu = nntm_congtu_block_lay_du_lieu_nhat_quan( $program, $limit );
			$bxh     = $du_lieu['bxh'];
		}

		if ( empty( $bxh ) ) {
			ob_start();
			?>
			<div class="nntm-cong-tu__bxh">
				<?php if ( '' !== trim( wp_strip_all_tags( $heading ) ) ) : ?>
					<h2 class="nntm-cong-tu__bxh-heading"><?php echo esc_html( $heading ); ?></h2>
				<?php endif; ?>
				<p class="nntm-cong-tu__bxh-rong"><?php esc_html_e( 'Chưa có dữ liệu tham gia để xếp hạng.', 'nntm' ); ?></p>
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
