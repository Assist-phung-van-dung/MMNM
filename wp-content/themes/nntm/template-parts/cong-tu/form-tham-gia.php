<?php
/**
 * Form "Tham Gia Chuỗi Trì" / "Cam Kết Thêm" — MỘT template duy nhất, đổi
 * chữ theo nntm_kpi_da_tham_gia() (đúng yêu cầu, KHÔNG đẻ file thứ hai).
 *
 * Dựng theo ảnh thiết kế thật design/figma/6613-10636@1x.png (node Figma
 * 6613:10636, khung 1366×770 — xem docs/07-ban-giao.md). Số đo bóc bằng
 * cách so màu từng điểm ảnh (không có GD/Imagick ở máy này nên đo bằng
 * Pillow qua dòng lệnh `python`), xem chi tiết số đo trong cong-tu.css.
 *
 * @package NNTM
 */

defined( 'ABSPATH' ) || exit;

$nntm_ct_program = function_exists( 'nntm_program_hien_tai' ) ? nntm_program_hien_tai() : null;
$nntm_ct_errors  = isset( $GLOBALS['nntm_congtu_errors'] ) && is_wp_error( $GLOBALS['nntm_congtu_errors'] ) ? $GLOBALS['nntm_congtu_errors'] : null;
$nntm_ct_ok      = isset( $_GET['nntm_congtu_ok'] ) && 'cam-ket' === $_GET['nntm_congtu_ok']; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- chi doc co dua hien thong bao, khong tao doi.

$nntm_ct_user_id = get_current_user_id();
?>
<div class="nntm-auth-card nntm-auth-card--cong-tu">

	<?php if ( ! $nntm_ct_program ) : ?>

		<h1 class="nntm-auth-card__title nntm-auth-card__title--cong-tu"><?php esc_html_e( 'Tham Gia Chuỗi Trì', 'nntm' ); ?></h1>

		<div class="nntm-auth-alert nntm-auth-alert--loi" role="alert">
			<p>
				<?php
				echo esc_html(
					$nntm_ct_errors
						? wp_strip_all_tags( $nntm_ct_errors->get_error_message() )
						: __( 'Hiện không có chương trình trì tụng nào đang mở. Mời quay lại sau.', 'nntm' )
				);
				?>
			</p>
		</div>

	<?php else : ?>

		<?php
		$nntm_ct_da_tham_gia = function_exists( 'nntm_kpi_da_tham_gia' ) && nntm_kpi_da_tham_gia( $nntm_ct_program->ID, $nntm_ct_user_id );
		$nntm_ct_tong         = function_exists( 'nntm_kpi_tong_cua_nguoi' ) ? nntm_kpi_tong_cua_nguoi( $nntm_ct_program->ID, $nntm_ct_user_id ) : array(
			'cam_ket'   => 0,
			'thuc_hien' => 0,
		);

		$nntm_ct_tieu_de = $nntm_ct_da_tham_gia ? __( 'Cam Kết Thêm', 'nntm' ) : __( 'Tham Gia Chuỗi Trì', 'nntm' );
		$nntm_ct_nhan    = $nntm_ct_da_tham_gia ? __( 'Số chuỗi cam kết thêm', 'nntm' ) : __( 'Số chuỗi cam kết sẽ trì', 'nntm' );
		$nntm_ct_nut     = $nntm_ct_da_tham_gia ? __( 'Xác Nhận Cam Kết', 'nntm' ) : __( 'Xác Nhận Đăng Ký', 'nntm' );

		$nntm_ct_phap_danh = function_exists( 'nntm_congtu_phap_danh' ) ? nntm_congtu_phap_danh( $nntm_ct_user_id ) : '';
		?>

		<h1 class="nntm-auth-card__title nntm-auth-card__title--cong-tu"><?php echo esc_html( $nntm_ct_tieu_de ); ?></h1>

		<div class="nntm-cong-tu__chao">
			<?php echo get_avatar( $nntm_ct_user_id, 40, '', '', array( 'class' => 'nntm-cong-tu__avatar' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- get_avatar() da tu esc. ?>
			<span class="nntm-cong-tu__chao-text">
				<em><?php esc_html_e( 'Xin chào', 'nntm' ); ?></em>
				<strong><?php echo esc_html( $nntm_ct_phap_danh ); ?></strong>
			</span>
		</div>

		<?php
		/*
		 * Dòng trạng thái LUÔN in ra (rỗng khi chưa tham gia) — AJAX sau khi
		 * cam kết xong sẽ điền chữ vào đúng thẻ này mà không phải tải lại
		 * trang, nên thẻ phải tồn tại sẵn trong DOM. Thẻ rỗng bị CSS ẩn
		 * (:empty trong cong-tu.css) nên lần đầu vẫn không chiếm chỗ — VÌ VẬY
		 * mở/đóng thẻ phải nằm SÁT nhau, không xuống dòng: :empty không khớp
		 * phần tử có dù chỉ một khoảng trắng bên trong.
		 */
		?>
		<p class="nntm-cong-tu__hien-trang"><?php echo $nntm_ct_da_tham_gia ? esc_html( nntm_congtu_cau_da_cam_ket( (int) $nntm_ct_tong['cam_ket'], (int) $nntm_ct_tong['thuc_hien'] ) ) : ''; ?></p>

		<?php
		// Ô thông báo dùng chung cho POST thường và AJAX — xem form-khai-bao.php.
		?>
		<div class="nntm-cong-tu__thong-bao" data-nntm-congtu-thong-bao>
			<?php if ( $nntm_ct_errors ) : ?>
				<div class="nntm-auth-alert nntm-auth-alert--loi" role="alert">
					<?php foreach ( $nntm_ct_errors->get_error_messages() as $nntm_ct_message ) : ?>
						<p><?php echo esc_html( wp_strip_all_tags( $nntm_ct_message ) ); ?></p>
					<?php endforeach; ?>
				</div>
			<?php elseif ( $nntm_ct_ok ) : ?>
				<div class="nntm-auth-alert nntm-auth-alert--ok" role="status">
					<p><?php esc_html_e( 'Đã ghi nhận, cảm ơn bạn đã phát tâm.', 'nntm' ); ?></p>
				</div>
			<?php endif; ?>
		</div>

		<form class="nntm-auth-form" method="post" data-nntm-congtu-ajax="cam-ket">
			<?php wp_nonce_field( 'nntm_congtu_cam_ket', 'nntm_congtu_nonce' ); ?>
			<input type="hidden" name="nntm_congtu_action" value="cam-ket" />
			<button type="submit" class="nntm-auth-btn nntm-auth-btn--dac nntm-cong-tu__submit">
				<?php echo esc_html( $nntm_ct_nut ); ?>
			</button>
		</form>

	<?php endif; ?>

</div>
