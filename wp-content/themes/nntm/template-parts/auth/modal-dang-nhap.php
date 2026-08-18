<?php
/**
 * Modal đăng nhập gọn — in ở chân trang (hook wp_footer, đăng ký trong
 * inc/auth.php::nntm_render_auth_modal()) cho khách chưa đăng nhập, ở
 * MỌI trang. Mở bằng cách bấm phần tử có [data-nntm-auth-modal] (vd nút
 * "Mời vào" ở trang Nhập Pháp Giới) — xử lý bởi assets/js/auth-modal.js.
 *
 * Modal KHÔNG dùng ảnh nền phong cảnh núi (chỉ nổi trên trang đang xem
 * với lớp phủ tối) — xem assets/css/pages/auth.css.
 *
 * @package NNTM
 */

defined( 'ABSPATH' ) || exit;

$nntm_modal_has_login_error = ! empty( $GLOBALS['nntm_auth_errors'] )
	&& is_wp_error( $GLOBALS['nntm_auth_errors'] )
	&& ! empty( $_POST['nntm_auth_action'] ) // phpcs:ignore WordPress.Security.NonceVerification.Missing -- chỉ dùng để quyết định trạng thái hiển thị; nonce đã kiểm trong inc/auth.php.
	&& 'dang-nhap' === sanitize_key( wp_unslash( $_POST['nntm_auth_action'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
?>
<div class="nntm-auth-modal" id="nntm-auth-modal"<?php echo $nntm_modal_has_login_error ? '' : ' hidden'; // phpcs:ignore WordPress.Security.EscapeOutput -- chuỗi cố định. ?>>
	<div class="nntm-auth-modal__overlay" data-nntm-auth-modal-overlay></div>

	<div class="nntm-auth-modal__panel" role="dialog" aria-modal="true" aria-label="<?php esc_attr_e( 'Đăng nhập', 'nntm' ); ?>">
		<button type="button" class="nntm-auth-modal__close" data-nntm-auth-modal-close>
			<span class="nntm-sr-only"><?php esc_html_e( 'Đóng', 'nntm' ); ?></span>
			<span aria-hidden="true">&times;</span>
		</button>

		<?php
		get_template_part(
			'template-parts/auth/form-dang-nhap',
			null,
			array( 'compact' => true )
		);
		?>
	</div>
</div>
