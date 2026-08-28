<?php

defined( 'ABSPATH' ) || exit;

$args        = is_array( $args ) ? $args : array();
$redirect_to = isset( $args['redirect_to'] ) ? (string) $args['redirect_to'] : '';

$is_this_action = ! empty( $_POST['nntm_auth_action'] ) && 'dang-ky' === wp_unslash( $_POST['nntm_auth_action'] );  
$errors = ( $is_this_action && isset( $GLOBALS['nntm_auth_errors'] ) && is_wp_error( $GLOBALS['nntm_auth_errors'] ) ) ? $GLOBALS['nntm_auth_errors'] : null;

$values = wp_parse_args(
	( $is_this_action && isset( $GLOBALS['nntm_auth_values'] ) && is_array( $GLOBALS['nntm_auth_values'] ) ) ? $GLOBALS['nntm_auth_values'] : array(),
	array(
		'ho_ten'            => '',
		'user_email'        => '',
		'user_login'        => '',
		'nntm_phap_danh'    => '',
		'nntm_vung_mien'    => '',
		'nntm_dia_chi'      => '',
		'nntm_dien_thoai'   => '',
		'nntm_nhan_ban_tin' => false,
	)
);

$nntm_chinh_sach     = get_page_by_path( 'chinh-sach' );
$nntm_chinh_sach_url = $nntm_chinh_sach ? get_permalink( $nntm_chinh_sach ) : home_url( '/chinh-sach/' );
?>
<div class="nntm-auth-card nntm-auth-card--rong">

	<h1 class="nntm-auth-card__title nntm-auth-card__title--dang-ky">
		<?php esc_html_e( 'Đăng ký thành viên', 'nntm' ); ?>
	</h1>

	<?php if ( $errors ) : ?>
		<div class="nntm-auth-alert nntm-auth-alert--loi" role="alert">
			<?php foreach ( $errors->get_error_messages() as $message ) : ?>
				<p><?php echo esc_html( wp_strip_all_tags( $message ) ); ?></p>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>

	<form class="nntm-auth-form" method="post">
		<?php wp_nonce_field( 'nntm_dang_ky', 'nntm_auth_nonce' ); ?>
		<input type="hidden" name="nntm_auth_action" value="dang-ky" />
		<input type="hidden" name="redirect_to" value="<?php echo esc_attr( $redirect_to ); ?>" />

		<div class="nntm-auth-field">
			<label for="nntm-reg-hoten"><?php esc_html_e( 'Họ và Tên', 'nntm' ); ?></label>
			<div class="nntm-auth-field__control">
				<input
					type="text"
					id="nntm-reg-hoten"
					name="ho_ten"
					value="<?php echo esc_attr( $values['ho_ten'] ); ?>"
					autocomplete="name"
					required
				/>
			</div>
		</div>

		<div class="nntm-auth-field">
			<label for="nntm-reg-email"><?php esc_html_e( 'Email', 'nntm' ); ?></label>
			<div class="nntm-auth-field__control">
				<input
					type="email"
					id="nntm-reg-email"
					name="user_email"
					value="<?php echo esc_attr( $values['user_email'] ); ?>"
					autocomplete="email"
					required
				/>
			</div>
		</div>

		<div class="nntm-auth-field">
			<label for="nntm-reg-login"><?php esc_html_e( 'Tên đăng nhập', 'nntm' ); ?></label>
			<div class="nntm-auth-field__control">
				<input
					type="text"
					id="nntm-reg-login"
					name="user_login"
					placeholder="<?php esc_attr_e( 'vd: nguyen-van-a', 'nntm' ); ?>"
					value="<?php echo esc_attr( $values['user_login'] ); ?>"
					autocomplete="username"
					minlength="4"
					required
				/>
				<p class="nntm-auth-hint">
					<?php esc_html_e( 'Dùng để đăng nhập, không được trùng với người khác. Chữ không dấu, số, dấu chấm, gạch ngang hoặc gạch dưới.', 'nntm' ); ?>
				</p>
			</div>
		</div>

		<div class="nntm-auth-field">
			<label for="nntm-reg-phapdanh"><?php esc_html_e( 'Pháp danh', 'nntm' ); ?></label>
			<div class="nntm-auth-field__control">
				<input
					type="text"
					id="nntm-reg-phapdanh"
					name="nntm_phap_danh"
					placeholder="<?php esc_attr_e( 'Pháp danh của bạn', 'nntm' ); ?>"
					value="<?php echo esc_attr( $values['nntm_phap_danh'] ); ?>"
					minlength="2"
					required
				/>
				<p class="nntm-auth-hint">
					<?php esc_html_e( 'Chỉ là tên hiển thị công khai. Nhiều người có thể cùng một Pháp danh, và Pháp danh không dùng để đăng nhập.', 'nntm' ); ?>
				</p>
			</div>
		</div>

		<div class="nntm-auth-field">
			<label for="nntm-reg-pass"><?php esc_html_e( 'Password', 'nntm' ); ?></label>
			<div class="nntm-auth-field__control nntm-auth-field__control--icon">
				<svg class="nntm-auth-field__icon" aria-hidden="true" width="16" height="16" viewBox="0 0 16 16" fill="none">
					<rect x="3" y="7" width="10" height="7" rx="1.5" stroke="currentColor" stroke-width="1.4" />
					<path d="M5 7V5a3 3 0 0 1 6 0v2" stroke="currentColor" stroke-width="1.4" />
				</svg>
				<input
					type="password"
					id="nntm-reg-pass"
					name="user_password"
					placeholder="<?php esc_attr_e( 'Nhập mật khẩu', 'nntm' ); ?>"
					autocomplete="new-password"
					minlength="8"
					required
				/>
			</div>
		</div>

		<div class="nntm-auth-field">
			<label for="nntm-reg-pass2"><?php esc_html_e( 'Re-type password', 'nntm' ); ?></label>
			<div class="nntm-auth-field__control nntm-auth-field__control--icon">
				<svg class="nntm-auth-field__icon" aria-hidden="true" width="16" height="16" viewBox="0 0 16 16" fill="none">
					<rect x="3" y="7" width="10" height="7" rx="1.5" stroke="currentColor" stroke-width="1.4" />
					<path d="M5 7V5a3 3 0 0 1 6 0v2" stroke="currentColor" stroke-width="1.4" />
				</svg>
				<input
					type="password"
					id="nntm-reg-pass2"
					name="user_password_2"
					placeholder="<?php esc_attr_e( 'Nhập lại mật khẩu', 'nntm' ); ?>"
					autocomplete="new-password"
					minlength="8"
					required
				/>
			</div>
		</div>

		<div class="nntm-auth-field">
			<label for="nntm-reg-vungmien"><?php esc_html_e( 'Vùng miền', 'nntm' ); ?></label>
			<div class="nntm-auth-field__control">
				<select id="nntm-reg-vungmien" name="nntm_vung_mien">
					<option value=""><?php esc_html_e( 'Chọn vùng miền', 'nntm' ); ?></option>
					<?php foreach ( nntm_vung_mien_options() as $nntm_vm_key => $nntm_vm_label ) : ?>
						<option value="<?php echo esc_attr( $nntm_vm_key ); ?>" <?php selected( $values['nntm_vung_mien'], $nntm_vm_key ); ?>>
							<?php echo esc_html( $nntm_vm_label ); ?>
						</option>
					<?php endforeach; ?>
				</select>
			</div>
		</div>

		<div class="nntm-auth-field">
			<label for="nntm-reg-diachi"><?php esc_html_e( 'Địa chỉ (Optional)', 'nntm' ); ?></label>
			<div class="nntm-auth-field__control">
				<input
					type="text"
					id="nntm-reg-diachi"
					name="nntm_dia_chi"
					value="<?php echo esc_attr( $values['nntm_dia_chi'] ); ?>"
					autocomplete="street-address"
				/>
			</div>
		</div>

		<div class="nntm-auth-field">
			<label for="nntm-reg-dienthoai"><?php esc_html_e( 'Số điện thoại (Optional)', 'nntm' ); ?></label>
			<div class="nntm-auth-field__control">
				<input
					type="text"
					id="nntm-reg-dienthoai"
					name="nntm_dien_thoai"
					value="<?php echo esc_attr( $values['nntm_dien_thoai'] ); ?>"
					autocomplete="tel"
				/>
			</div>
		</div>
		<div class="nntm-auth-checkbox">
			<label>
				<input type="checkbox" name="nntm_dong_y_dieu_khoan" value="1" required />
				<span>
					<?php
					printf(
						/* translators: %s: link "Điều khoản sử dụng". */
						esc_html__( 'Tôi đã đọc và đồng ý với %s', 'nntm' ),
						'<a href="' . esc_url( $nntm_chinh_sach_url ) . '"><strong>' . esc_html__( 'Điều khoản sử dụng', 'nntm' ) . '</strong></a>'
					);
					?>
				</span>
			</label>
		</div>

		<div class="nntm-auth-checkbox">
			<label>
				<input type="checkbox" name="nntm_nhan_ban_tin" value="1" <?php checked( $values['nntm_nhan_ban_tin'] ); ?> />
				<span><?php esc_html_e( 'Nhận thông tin của trang', 'nntm' ); ?></span>
			</label>
		</div>
		<button type="submit" class="nntm-auth-btn nntm-auth-btn--dac nntm-auth-btn--full">
			<?php esc_html_e( 'Đăng Ký', 'nntm' ); ?>
		</button>
	</form>
</div>
