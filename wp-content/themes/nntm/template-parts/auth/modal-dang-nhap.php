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
?>
<div class="nntm-auth-modal" id="nntm-auth-modal" hidden>
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
