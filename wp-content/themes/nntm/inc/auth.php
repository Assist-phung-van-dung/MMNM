<?php

defined( 'ABSPATH' ) || exit;


/**
 * Chỉ cho phép chuyển hướng về trong nội bộ website (chống open redirect).
 *
 * Trả về '' nếu đường dẫn rỗng / khác host / không hợp lệ, để nơi gọi tự quyết
 * định dùng đường lui nào.
 */
function nntm_auth_safe_redirect( string $url ): string {
	$url = trim( $url );

	if ( '' === $url ) {
		return '';
	}

	$url = esc_url_raw( wp_unslash( $url ) );

	if ( '' === $url ) {
		return '';
	}



	$hop_le = wp_validate_redirect( $url, '' );

	return (string) $hop_le;
}

/**
 * Trang mà người dùng đang đứng khi bấm Đăng ký / Đăng nhập.
 *
 * Ưu tiên ?redirect_to trên URL, sau đó tới trang vừa rời (referer). Không lấy
 * chính các trang đăng nhập/đăng ký/quên mật khẩu làm đích, vì như vậy sau khi
 * đăng ký xong lại quay về đúng form vừa điền.
 */
function nntm_auth_redirect_from_request(): string {

	$tho = '';

	if ( isset( $_POST['redirect_to'] ) ) {
		$tho = (string) $_POST['redirect_to'];
	} elseif ( isset( $_GET['redirect_to'] ) ) {
		$tho = (string) $_GET['redirect_to'];
	}

	$tu_url = '' !== $tho ? nntm_auth_safe_redirect( $tho ) : '';

	if ( '' !== $tu_url && ! nntm_auth_la_trang_auth( $tu_url ) ) {
		return $tu_url;
	}

	$referer = wp_get_referer();

	if ( is_string( $referer ) && '' !== $referer ) {
		$tu_referer = nntm_auth_safe_redirect( $referer );

		if ( '' !== $tu_referer && ! nntm_auth_la_trang_auth( $tu_referer ) ) {
			return $tu_referer;
		}
	}

	return '';
}

/**
 * URL của trang đang xem, đã lọc an toàn — dùng làm đích quay về mặc định.
 */
function nntm_auth_current_url(): string {
	$duong = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_unslash( $_SERVER['REQUEST_URI'] ) : '';

	if ( '' === $duong ) {
		return '';
	}

	$url = home_url( $duong );

	if ( nntm_auth_la_trang_auth( $url ) ) {
		return '';
	}

	return nntm_auth_safe_redirect( $url );
}

/**
 * Đường dẫn này có phải chính trang đăng nhập / đăng ký / quên mật khẩu không?
 */
function nntm_auth_la_trang_auth( string $url ): bool {
	$duong = (string) wp_parse_url( $url, PHP_URL_PATH );

	if ( '' === $duong ) {
		return false;
	}

	foreach ( array( 'dang-nhap', 'dang-ky', 'quen-mat-khau' ) as $slug ) {
		if ( false !== strpos( $duong, '/' . $slug ) ) {
			return true;
		}
	}

	return false;
}

function nntm_login_url( string $redirect_to = '' ): string {
	$page = get_page_by_path( 'dang-nhap' );

	if ( $page ) {
		$url = get_permalink( $page );
		if ( $redirect_to ) {
			$url = add_query_arg( 'redirect_to', rawurlencode( $redirect_to ), $url );
		}
	} else {
		$url = wp_login_url( $redirect_to );
	}

	return apply_filters( 'nntm_login_url', $url, $redirect_to );
}

function nntm_register_url( string $redirect_to = '' ): string {
	$page = get_page_by_path( 'dang-ky' );

	if ( $page ) {
		$url = get_permalink( $page );
		if ( $redirect_to ) {
			$url = add_query_arg( 'redirect_to', rawurlencode( $redirect_to ), $url );
		}
	} else {
		$url = wp_registration_url();
	}

	return apply_filters( 'nntm_register_url', $url, $redirect_to );
}

function nntm_lostpassword_url( string $redirect_to = '' ): string {
	$page = get_page_by_path( 'quen-mat-khau' );

	if ( $page ) {
		$url = get_permalink( $page );
		if ( $redirect_to ) {
			$url = add_query_arg( 'redirect_to', rawurlencode( $redirect_to ), $url );
		}
	} else {
		$url = wp_lostpassword_url( $redirect_to );
	}

	return apply_filters( 'nntm_lostpassword_url', $url, $redirect_to );
}

function nntm_vung_mien_options(): array {
	return apply_filters(
		'nntm_vung_mien_options',
		array(
			'mien-bac'   => __( 'Miền Bắc', 'nntm' ),
			'mien-trung' => __( 'Miền Trung', 'nntm' ),
			'mien-nam'   => __( 'Miền Nam', 'nntm' ),
			'nuoc-ngoai' => __( 'Nước ngoài', 'nntm' ),
		)
	);
}

function nntm_auth_background_url(): string {
	$attachment_id = (int) get_option( 'nntm_auth_bg_id', 115 );
	$url           = $attachment_id ? (string) wp_get_attachment_image_url( $attachment_id, 'full' ) : '';

	return (string) apply_filters( 'nntm_auth_background_url', $url );
}


function nntm_enqueue_auth_assets(): void {
	$is_auth_page = is_page( array( 'dang-nhap', 'dang-ky', 'quen-mat-khau' ) );
	$is_guest     = ! is_user_logged_in();

	if ( $is_auth_page || $is_guest ) {
		$auth_css_path = NNTM_THEME_DIR . '/assets/css/pages/auth.css';
		wp_enqueue_style(
			'nntm-auth',
			NNTM_THEME_URI . '/assets/css/pages/auth.css',
			array( 'nntm-tokens', 'nntm-base' ),
			nntm_asset_version( $auth_css_path )
		);
	}

	if ( $is_guest ) {
		$auth_js_path = NNTM_THEME_DIR . '/assets/js/auth-modal.js';
		wp_enqueue_script(
			'nntm-auth-modal',
			NNTM_THEME_URI . '/assets/js/auth-modal.js',
			array(),
			nntm_asset_version( $auth_js_path ),
			true
		);
	}

	if ( is_page( 'dang-ky' ) && $is_guest ) {
		$register_js_path = NNTM_THEME_DIR . '/assets/js/auth-register.js';
		wp_enqueue_script(
			'nntm-auth-register',
			NNTM_THEME_URI . '/assets/js/auth-register.js',
			array(),
			nntm_asset_version( $register_js_path ),
			true
		);

		wp_localize_script(
			'nntm-auth-register',
			'nntmAuthRegister',
			array(
				'requiredName'     => __( 'Vui lòng nhập Họ và Tên.', 'nntm' ),
				'requiredEmail'    => __( 'Vui lòng nhập Email.', 'nntm' ),
				'invalidEmail'     => __( 'Email không hợp lệ. Vui lòng kiểm tra lại.', 'nntm' ),
				'requiredLogin'    => __( 'Vui lòng nhập Tên đăng nhập.', 'nntm' ),
				'shortLogin'       => __( 'Tên đăng nhập phải có ít nhất 4 ký tự.', 'nntm' ),
				'requiredDharma'   => __( 'Vui lòng nhập Pháp danh.', 'nntm' ),
				'shortDharma'      => __( 'Pháp danh phải có ít nhất 2 ký tự.', 'nntm' ),
				'requiredPassword' => __( 'Vui lòng nhập mật khẩu.', 'nntm' ),
				'shortPassword'    => __( 'Mật khẩu phải có ít nhất 8 ký tự.', 'nntm' ),
				'requiredConfirm'  => __( 'Vui lòng nhập lại mật khẩu.', 'nntm' ),
				'passwordMismatch' => __( 'Hai mật khẩu không khớp.', 'nntm' ),
				'requiredTerms'     => __( 'Vui lòng đồng ý với Điều khoản sử dụng.', 'nntm' ),
			)
		);
	}
}
add_action( 'wp_enqueue_scripts', 'nntm_enqueue_auth_assets' );

function nntm_dequeue_site_chrome_on_auth_pages(): void {
	if ( ! is_page( array( 'dang-nhap', 'dang-ky', 'quen-mat-khau' ) ) ) {
		return;
	}

	wp_dequeue_style( 'nntm-header' );
	wp_dequeue_style( 'nntm-footer' );
	wp_dequeue_script( 'nntm-header' );
	wp_dequeue_script( 'nntm-header-scroll' );
}
add_action( 'wp_enqueue_scripts', 'nntm_dequeue_site_chrome_on_auth_pages', 20 );

function nntm_render_auth_modal(): void {
	if ( is_user_logged_in() ) {
		return;
	}

	if ( is_page( array( 'dang-nhap', 'dang-ky', 'quen-mat-khau' ) ) ) {
		return;
	}

	get_template_part( 'template-parts/auth/modal-dang-nhap' );
}
add_action( 'wp_footer', 'nntm_render_auth_modal' );


/**
 * Gợi ý một tên đăng nhập duy nhất, dùng khi người dùng để trống ô Tên đăng nhập.
 */
function nntm_tao_ten_dang_nhap( string $phap_danh ): string {
	$goc = sanitize_title( sanitize_user( remove_accents( $phap_danh ), true ) );

	if ( '' === $goc ) {
		$goc = 'thanh-vien';
	}

	$ten_dang_nhap = $goc;
	$dem           = 1;

	while ( username_exists( $ten_dang_nhap ) && $dem < 200 ) {
		++$dem;
		$ten_dang_nhap = $goc . '-' . $dem;
	}

	return $ten_dang_nhap;
}

/**
 * Pháp danh KHÔNG còn là định danh đăng nhập (PROMPT 03).
 *
 * Nhiều tài khoản được phép trùng Pháp danh; nó chỉ là tên hiển thị trong hồ sơ.
 * Đăng nhập chỉ qua email hoặc tên đăng nhập (username) — cả hai đều là duy nhất.
 */


function nntm_handle_auth_post(): void {
	if ( empty( $_POST['nntm_auth_action'] ) ) {
		return;
	}

	$action = sanitize_key( wp_unslash( $_POST['nntm_auth_action'] ) );

	/*
	 * Đã đăng nhập rồi thì không xử lý lại đăng nhập/đăng ký nữa — form gửi lại
	 * (bấm F5, mở hai tab) chỉ tổ sinh nonce hỏng và chuyển hướng lung tung.
	 */
	if ( is_user_logged_in() && in_array( $action, array( 'dang-nhap', 'dang-ky' ), true ) ) {
		$dich = nntm_auth_redirect_from_request();

		wp_safe_redirect( $dich ? $dich : home_url( '/' ) );
		exit;
	}

	switch ( $action ) {
		case 'dang-nhap':
			nntm_handle_login_post();
			break;
		case 'dang-ky':
			nntm_handle_register_post();
			break;
		case 'quen-mat-khau':
			nntm_handle_lostpassword_post();
			break;
	}
}
add_action( 'template_redirect', 'nntm_handle_auth_post' );

function nntm_handle_login_post(): void {
	$nonce = isset( $_POST['nntm_auth_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nntm_auth_nonce'] ) ) : '';

	if ( ! wp_verify_nonce( $nonce, 'nntm_dang_nhap' ) ) {
		$GLOBALS['nntm_auth_errors'] = new WP_Error( 'nonce', __( 'Phiên làm việc đã hết hạn, vui lòng thử lại.', 'nntm' ) );
		return;
	}

	$redirect_to = isset( $_POST['redirect_to'] ) ? nntm_auth_safe_redirect( (string) $_POST['redirect_to'] ) : '';




	$user_login = isset( $_POST['user_login'] ) ? trim( (string) wp_unslash( $_POST['user_login'] ) ) : '';

	$creds = array(
		'user_login'    => $user_login,
		'user_password' => isset( $_POST['user_password'] ) ? (string) wp_unslash( $_POST['user_password'] ) : '',
		'remember'      => ! empty( $_POST['remember'] ),
	);

	$user = wp_signon( $creds, is_ssl() );

	if ( is_wp_error( $user ) ) {

		$GLOBALS['nntm_auth_errors'] = $user;
		return;
	}

	wp_safe_redirect( $redirect_to ? $redirect_to : home_url( '/' ) );
	exit;
}

function nntm_handle_lostpassword_post(): void {
	$nonce = isset( $_POST['nntm_auth_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nntm_auth_nonce'] ) ) : '';

	if ( ! wp_verify_nonce( $nonce, 'nntm_quen_mat_khau' ) ) {
		$GLOBALS['nntm_auth_errors'] = new WP_Error( 'nonce', __( 'Phiên làm việc đã hết hạn, vui lòng thử lại.', 'nntm' ) );
		return;
	}

	$user_login = isset( $_POST['user_login'] ) ? sanitize_text_field( wp_unslash( $_POST['user_login'] ) ) : '';

	if ( '' === $user_login ) {
		$GLOBALS['nntm_auth_errors'] = new WP_Error( 'trong', __( 'Vui lòng nhập email hoặc tên đăng nhập.', 'nntm' ) );
		return;
	}

	if ( ! function_exists( 'retrieve_password' ) ) {
		$GLOBALS['nntm_auth_errors'] = new WP_Error( 'khong_kha_dung', __( 'Tính năng khôi phục mật khẩu tạm thời không khả dụng, vui lòng liên hệ quản trị.', 'nntm' ) );
		return;
	}

	retrieve_password( $user_login );

	$GLOBALS['nntm_auth_success'] = __( 'Nếu email/tên đăng nhập có trong hệ thống, chúng tôi đã gửi hướng dẫn đặt lại mật khẩu.', 'nntm' );
}

function nntm_handle_register_post(): void {
	$nonce = isset( $_POST['nntm_auth_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nntm_auth_nonce'] ) ) : '';

	if ( ! wp_verify_nonce( $nonce, 'nntm_dang_ky' ) ) {
		$GLOBALS['nntm_auth_errors'] = new WP_Error( 'nonce', __( 'Phiên làm việc đã hết hạn, vui lòng thử lại.', 'nntm' ) );
		return;
	}

	$redirect_to  = isset( $_POST['redirect_to'] ) ? nntm_auth_safe_redirect( (string) $_POST['redirect_to'] ) : '';
	$ho_ten       = isset( $_POST['ho_ten'] ) ? sanitize_text_field( wp_unslash( $_POST['ho_ten'] ) ) : '';
	$email        = isset( $_POST['user_email'] ) ? sanitize_email( wp_unslash( $_POST['user_email'] ) ) : '';

	// user_login = TÊN ĐĂNG NHẬP (duy nhất). Pháp danh là ô riêng và ĐƯỢC PHÉP trùng.
	$ten_dang_nhap = isset( $_POST['user_login'] ) ? sanitize_user( wp_unslash( $_POST['user_login'] ), true ) : '';
	$ten_dang_nhap = trim( $ten_dang_nhap );
	$phap_danh    = isset( $_POST['nntm_phap_danh'] ) ? trim( sanitize_text_field( wp_unslash( $_POST['nntm_phap_danh'] ) ) ) : '';
	$password     = isset( $_POST['user_password'] ) ? (string) wp_unslash( $_POST['user_password'] ) : '';
	$password_2   = isset( $_POST['user_password_2'] ) ? (string) wp_unslash( $_POST['user_password_2'] ) : '';
	$vung_mien_tho = isset( $_POST['nntm_vung_mien'] ) ? sanitize_text_field( wp_unslash( $_POST['nntm_vung_mien'] ) ) : '';

	$vung_mien    = array_key_exists( $vung_mien_tho, nntm_vung_mien_options() ) ? $vung_mien_tho : '';
	$dia_chi      = isset( $_POST['nntm_dia_chi'] ) ? sanitize_text_field( wp_unslash( $_POST['nntm_dia_chi'] ) ) : '';
	$dien_thoai   = isset( $_POST['nntm_dien_thoai'] ) ? sanitize_text_field( wp_unslash( $_POST['nntm_dien_thoai'] ) ) : '';
	$dong_y_dieu_khoan = ! empty( $_POST['nntm_dong_y_dieu_khoan'] );
	$nhan_ban_tin       = ! empty( $_POST['nntm_nhan_ban_tin'] );

	$GLOBALS['nntm_auth_values'] = array(
		'ho_ten'            => $ho_ten,
		'user_email'        => $email,
		'user_login'        => $ten_dang_nhap,
		'nntm_phap_danh'    => $phap_danh,
		'nntm_vung_mien'    => $vung_mien,
		'nntm_dia_chi'      => $dia_chi,
		'nntm_dien_thoai'   => $dien_thoai,
		'nntm_nhan_ban_tin' => $nhan_ban_tin,
	);

	$errors = new WP_Error();

	if ( '' === $ho_ten ) {
		$errors->add( 'ho_ten', __( 'Vui lòng nhập Họ và Tên.', 'nntm' ) );
	}

	if ( '' === $email ) {
		$errors->add( 'email', __( 'Vui lòng nhập Email.', 'nntm' ) );
	} elseif ( ! is_email( $email ) ) {
		$errors->add( 'email', __( 'Email không hợp lệ.', 'nntm' ) );
	} elseif ( email_exists( $email ) ) {
		$errors->add( 'email', __( 'Email này đã được đăng ký.', 'nntm' ) );
	}

	// Tên đăng nhập: duy nhất, dùng để đăng nhập.
	if ( '' === $ten_dang_nhap ) {
		$errors->add( 'user_login', __( 'Vui lòng nhập Tên đăng nhập.', 'nntm' ) );
	} elseif ( mb_strlen( $ten_dang_nhap ) < 4 ) {
		$errors->add( 'user_login', __( 'Tên đăng nhập phải có ít nhất 4 ký tự.', 'nntm' ) );
	} elseif ( ! validate_username( $ten_dang_nhap ) ) {
		$errors->add( 'user_login', __( 'Tên đăng nhập chứa ký tự không hợp lệ. Chỉ dùng chữ không dấu, số, dấu chấm, gạch ngang và gạch dưới.', 'nntm' ) );
	} elseif ( username_exists( $ten_dang_nhap ) ) {
		$errors->add( 'user_login', __( 'Tên đăng nhập này đã có người dùng. Vui lòng chọn tên khác.', 'nntm' ) );
	}

	// Pháp danh: chỉ là tên hiển thị, ĐƯỢC PHÉP trùng giữa nhiều tài khoản.
	if ( '' === $phap_danh || mb_strlen( $phap_danh ) < 2 ) {
		$errors->add( 'nntm_phap_danh', __( 'Vui lòng nhập Pháp danh (ít nhất 2 ký tự).', 'nntm' ) );
	}

	if ( '' === $password || '' === $password_2 ) {
		$errors->add( 'password', __( 'Vui lòng nhập đầy đủ mật khẩu và mật khẩu xác nhận.', 'nntm' ) );
	} elseif ( $password !== $password_2 ) {
		$errors->add( 'password', __( 'Hai mật khẩu không khớp.', 'nntm' ) );
	} elseif ( strlen( $password ) < 8 ) {
		$errors->add( 'password', __( 'Mật khẩu phải có ít nhất 8 ký tự.', 'nntm' ) );
	}

	if ( ! $dong_y_dieu_khoan ) {
		$errors->add( 'dieu_khoan', __( 'Vui lòng đồng ý với Điều khoản sử dụng.', 'nntm' ) );
	}

	if ( $errors->has_errors() ) {
		$GLOBALS['nntm_auth_errors'] = $errors;
		return;
	}


	$user_login = $ten_dang_nhap;

	$user_id = wp_insert_user(
		array(
			'user_login'   => $user_login,
			'user_email'   => $email,
			'user_pass'    => $password,

			'display_name' => $phap_danh,
			'nickname'     => $phap_danh,
			'role'         => 'subscriber',
		)
	);

	if ( is_wp_error( $user_id ) ) {
		$GLOBALS['nntm_auth_errors'] = $user_id;
		return;
	}

	$ten_parts  = preg_split( '/\s+/', trim( $ho_ten ) );
	$last_name  = array_pop( $ten_parts );
	$first_name = implode( ' ', $ten_parts );
	if ( '' === $first_name ) {
		$first_name = $last_name;
		$last_name  = '';
	}

	wp_update_user(
		array(
			'ID'         => $user_id,
			'first_name' => $first_name,
			'last_name'  => $last_name,
		)
	);

	update_user_meta( $user_id, 'nntm_phap_danh', $phap_danh );
	update_user_meta( $user_id, 'nntm_vung_mien', $vung_mien );
	update_user_meta( $user_id, 'nntm_dia_chi', $dia_chi );
	update_user_meta( $user_id, 'nntm_dien_thoai', $dien_thoai );
	update_user_meta( $user_id, 'nntm_nhan_ban_tin', $nhan_ban_tin ? '1' : '0' );

	wp_new_user_notification( $user_id, null, 'both' );

	wp_set_current_user( $user_id );
	wp_set_auth_cookie( $user_id );

	wp_safe_redirect( $redirect_to ? $redirect_to : home_url( '/' ) );
	exit;
}
