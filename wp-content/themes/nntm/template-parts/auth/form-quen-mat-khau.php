<?php
/**
 * Form "Quên mật khẩu" — dùng ở page-quen-mat-khau.php.
 *
 * Tham số nhận qua $args:
 *   redirect_to (string) URL chuyển hướng sau khi gửi yêu cầu thành công.
 *   compact     (bool)   true khi dùng trong khung hẹp hơn (dự phòng).
 *
 * @package NNTM
 * @var array $args
 */

defined( 'ABSPATH' ) || exit;

$args        = is_array( $args ) ? $args : array();
$redirect_to = isset( $args['redirect_to'] ) ? (string) $args['redirect_to'] : '';
$compact     = ! empty( $args['compact'] );

$is_this_action = ! empty( $_POST['nntm_auth_action'] ) && 'quen-mat-khau' === wp_unslash( $_POST['nntm_auth_action'] ); // phpcs:ignore WordPress.Security.NonceVerification.Missing -- chỉ đọc để biết có in thông báo hay không, nonce đã kiểm ở inc/auth.php.
$errors  = ( $is_this_action && isset( $GLOBALS['nntm_auth_errors'] ) && is_wp_error( $GLOBALS['nntm_auth_errors'] ) ) ? $GLOBALS['nntm_auth_errors'] : null;
$success = ( $is_this_action && ! empty( $GLOBALS['nntm_auth_success'] ) ) ? (string) $GLOBALS['nntm_auth_success'] : '';
?>
<div class="nntm-auth-card<?php echo $compact ? ' nntm-auth-card--compact' : ''; ?>">

	<h1 class="nntm-auth-card__title">
		<span><?php esc_html_e( 'NĂNG NHÂN', 'nntm' ); ?></span>
		<span><?php esc_html_e( 'TỊCH MẶC', 'nntm' ); ?></span>
	</h1>

	<?php if ( $errors ) : ?>
		<div class="nntm-auth-alert nntm-auth-alert--loi" role="alert">
			<?php foreach ( $errors->get_error_messages() as $message ) : ?>
				<p><?php echo esc_html( wp_strip_all_tags( $message ) ); ?></p>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>

	<?php if ( $success ) : ?>
		<div class="nntm-auth-alert nntm-auth-alert--ok" role="status">
			<p><?php echo esc_html( $success ); ?></p>
		</div>
	<?php endif; ?>

	<form class="nntm-auth-form" method="post">
		<?php wp_nonce_field( 'nntm_quen_mat_khau', 'nntm_auth_nonce' ); ?>
		<input type="hidden" name="nntm_auth_action" value="quen-mat-khau" />
		<input type="hidden" name="redirect_to" value="<?php echo esc_attr( $redirect_to ); ?>" />

		<div class="nntm-auth-field">
			<label for="nntm-lost-login"><?php esc_html_e( 'Nhập email để nhận mật khẩu', 'nntm' ); ?></label>
			<div class="nntm-auth-field__control">
				<input
					type="email"
					id="nntm-lost-login"
					name="user_login"
					autocomplete="email"
					required
				/>
			</div>
		</div>

		<button type="submit" class="nntm-auth-btn nntm-auth-btn--dac">
			<?php esc_html_e( 'Gửi mật khẩu cho tôi', 'nntm' ); ?>
		</button>
	</form>
</div>
