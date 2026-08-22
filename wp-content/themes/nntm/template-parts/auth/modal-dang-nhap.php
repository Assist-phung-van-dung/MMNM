<?php

defined( 'ABSPATH' ) || exit;

$nntm_modal_has_login_error = ! empty( $GLOBALS['nntm_auth_errors'] )
	&& is_wp_error( $GLOBALS['nntm_auth_errors'] )
	&& ! empty( $_POST['nntm_auth_action'] )  
	&& 'dang-nhap' === sanitize_key( wp_unslash( $_POST['nntm_auth_action'] ) );  
?>
<div class="nntm-auth-modal" id="nntm-auth-modal"<?php echo $nntm_modal_has_login_error ? '' : ' hidden';  ?>>
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
