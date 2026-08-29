<?php

defined( 'ABSPATH' ) || exit;

$args        = is_array( $args ) ? $args : array();
$redirect_to = isset( $args['redirect_to'] ) ? (string) $args['redirect_to'] : '';
$compact     = ! empty( $args['compact'] );

$is_this_action = ! empty( $_POST['nntm_auth_action'] ) && 'dang-nhap' === wp_unslash( $_POST['nntm_auth_action'] );  
$errors         = ( $is_this_action && isset( $GLOBALS['nntm_auth_errors'] ) && is_wp_error( $GLOBALS['nntm_auth_errors'] ) )
	? $GLOBALS['nntm_auth_errors']
	: null;

$google_url = apply_filters( 'nntm_google_login_url', '' );
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

	<form class="nntm-auth-form" method="post">
		<?php wp_nonce_field( 'nntm_dang_nhap', 'nntm_auth_nonce' ); ?>
		<input type="hidden" name="nntm_auth_action" value="dang-nhap" />
		<input type="hidden" name="redirect_to" value="<?php echo esc_attr( $redirect_to ); ?>" />

		<div class="nntm-auth-field">
			<?php
			/*
			 * Nhãn nêu Email trước vì tài khoản mới chỉ có email — form đăng ký
			 * đã bỏ ô Tên đăng nhập. Vẫn giữ type="text" và vẫn nhận tên đăng
			 * nhập: những tài khoản lập trước thay đổi này có tên do chính họ
			 * đặt và có thể vẫn đang dùng nó. WordPress nhận cả hai sẵn.
			 */
			?>
			<label for="nntm-login-user"><?php esc_html_e( 'Email', 'nntm' ); ?></label>
			<div class="nntm-auth-field__control nntm-auth-field__control--icon">
				<svg class="nntm-auth-field__icon" aria-hidden="true" width="16" height="16" viewBox="0 0 16 16" fill="none">
					<circle cx="8" cy="5" r="3" stroke="currentColor" stroke-width="1.4" />
					<path d="M2 14c.9-3 3.2-4.5 6-4.5S13.1 11 14 14" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" />
				</svg>
				<input
					type="text"
					id="nntm-login-user"
					name="user_login"
					placeholder="<?php esc_attr_e( 'vd: nguyenvana@gmail.com', 'nntm' ); ?>"
					autocomplete="username"
					required
				/>
			</div>
		</div>

		<div class="nntm-auth-field">
			<label for="nntm-login-pass"><?php esc_html_e( 'Password', 'nntm' ); ?></label>
			<div class="nntm-auth-field__control nntm-auth-field__control--icon">
				<svg class="nntm-auth-field__icon" aria-hidden="true" width="16" height="16" viewBox="0 0 16 16" fill="none">
					<rect x="3" y="7" width="10" height="7" rx="1.5" stroke="currentColor" stroke-width="1.4" />
					<path d="M5 7V5a3 3 0 0 1 6 0v2" stroke="currentColor" stroke-width="1.4" />
				</svg>
				<input
					type="password"
					id="nntm-login-pass"
					name="user_password"
					placeholder="<?php esc_attr_e( 'Nhập mật khẩu', 'nntm' ); ?>"
					autocomplete="current-password"
					required
				/>
			</div>
		</div>

		<p class="nntm-auth-form__forgot">
			<a href="<?php echo esc_url( nntm_lostpassword_url( $redirect_to ) ); ?>" data-nntm-auth-link>
				<?php esc_html_e( 'Quên mật khẩu?', 'nntm' ); ?>
			</a>
		</p>

		<button type="submit" class="nntm-auth-btn nntm-auth-btn--dac">
			<?php esc_html_e( 'Đăng Nhập', 'nntm' ); ?>
		</button>

		<p class="nntm-auth-form__switch">
			<?php esc_html_e( 'Chưa có tài khoản?', 'nntm' ); ?>
			<a href="<?php echo esc_url( nntm_register_url( $redirect_to ) ); ?>" data-nntm-auth-link>
				<strong><?php esc_html_e( 'Đăng ký ngay', 'nntm' ); ?></strong>
			</a>
		</p>

		<?php
		?>
	</form>
</div>
