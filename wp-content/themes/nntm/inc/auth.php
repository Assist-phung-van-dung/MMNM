<?php
/**
 * Đăng nhập / Đăng ký / Quên mật khẩu.
 *
 * Theo docs/04-kien-truc.md mục 2: đăng nhập/đăng ký/trang tài khoản là
 * PHP template, KHÔNG phải block Gutenberg. File này gồm:
 *   1. Ba hàm URL dùng chung (nntm_login_url/nntm_register_url/nntm_lostpassword_url).
 *   2. Nạp CSS/JS riêng cho 3 trang + modal đăng nhập ở chân trang.
 *   3. Xử lý POST cho cả 3 form — KHÔNG bao giờ đá người dùng sang
 *      wp-login.php, lỗi in ngay trên form đang đứng.
 *
 * @package NNTM
 */

defined( 'ABSPATH' ) || exit;

/* =========================================================================
 * 1. URL helper dùng chung — phần khác của dự án gọi qua function_exists().
 * ========================================================================= */

/**
 * URL trang đăng nhập. Ưu tiên Page slug `dang-nhap` (theme tự dựng theo
 * 3 ảnh thiết kế anh Úy gửi); chưa seed trang thì rơi về wp_login_url().
 *
 * @param string $redirect_to URL chuyển hướng sau khi đăng nhập thành công.
 * @return string
 */
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

/**
 * URL trang đăng ký. Ưu tiên Page slug `dang-ky`, rơi về wp_registration_url().
 *
 * @param string $redirect_to URL chuyển hướng sau khi đăng ký thành công.
 * @return string
 */
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

/**
 * URL trang quên mật khẩu. Ưu tiên Page slug `quen-mat-khau`, rơi về
 * wp_lostpassword_url().
 *
 * @param string $redirect_to URL chuyển hướng sau khi gửi yêu cầu thành công.
 * @return string
 */
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

/**
 * Danh sách tuỳ chọn "Vùng miền" trong form đăng ký. Lọc qua filter để
 * ban quản trị đổi được mà không cần sửa code.
 *
 * @return array<string,string> key => nhãn hiển thị.
 */
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

/**
 * URL ảnh nền phong cảnh núi cho 3 trang đăng nhập/đăng ký/quên mật khẩu.
 *
 * Mặc định lấy attachment ID 115 ("Núi lớp lớp trong sương", 2000x1333,
 * đúng ảnh trong 3 thiết kế anh Úy gửi) qua option `nntm_auth_bg_id`.
 * Ban quản trị đổi ảnh chỉ cần đổi option này (Cài đặt → hoặc
 * update_option( 'nntm_auth_bg_id', <attachment ID mới> )) — KHÔNG phải
 * sửa code. Ảnh bị xoá / option rỗng thì trả về chuỗi rỗng, auth.css tự
 * có gradient dự phòng.
 *
 * @return string
 */
function nntm_auth_background_url(): string {
	$attachment_id = (int) get_option( 'nntm_auth_bg_id', 115 );
	$url           = $attachment_id ? (string) wp_get_attachment_image_url( $attachment_id, 'full' ) : '';

	return (string) apply_filters( 'nntm_auth_background_url', $url );
}

/* =========================================================================
 * 2. Nạp CSS/JS
 * ========================================================================= */

/**
 * CSS/JS cho 3 trang đăng nhập/đăng ký/quên mật khẩu + modal đăng nhập
 * dùng chung ở chân trang (mọi trang, chỉ cho khách chưa đăng nhập).
 */
function nntm_enqueue_auth_assets(): void {
	$is_auth_page = is_page( array( 'dang-nhap', 'dang-ky', 'quen-mat-khau' ) );
	$is_guest     = ! is_user_logged_in();

	/*
	 * auth.css chứa cả style của 3 trang lẫn style của modal đăng nhập
	 * (spec: "CSS của modal để chung trong auth.css"). Nạp trên 3 trang
	 * chuyên dụng, HOẶC trên mọi trang cho khách chưa đăng nhập (để nút
	 * "Mời vào" ở Nhập Pháp Giới mở modal có CSS mà dùng).
	 */
	if ( $is_auth_page || $is_guest ) {
		$auth_css_path = NNTM_THEME_DIR . '/assets/css/pages/auth.css';
		wp_enqueue_style(
			'nntm-auth',
			NNTM_THEME_URI . '/assets/css/pages/auth.css',
			array( 'nntm-tokens', 'nntm-base' ),
			nntm_asset_version( $auth_css_path )
		);
	}

	// Modal chỉ cần JS trên mọi trang cho khách chưa đăng nhập.
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
}
add_action( 'wp_enqueue_scripts', 'nntm_enqueue_auth_assets' );

/**
 * Gỡ CSS/JS đầu-chân trang site trên 3 trang đăng nhập/đăng ký/quên mật
 * khẩu — các trang này tự dựng toàn màn hình (page-dang-nhap.php...),
 * không gọi get_header()/get_footer(). Chạy ở priority 20 giống
 * nntm_enqueue_r1_assets() trong inc/enqueue.php (không sửa file đó vì
 * nằm ngoài phạm vi việc này, nên lặp lại đúng khuôn mẫu ở đây).
 */
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

/**
 * In modal đăng nhập ở chân trang cho khách chưa đăng nhập. Không in
 * trên chính 3 trang đăng nhập/đăng ký/quên mật khẩu vì các trang đó đã
 * có form đầy đủ ngay trên trang, tránh trùng id phần tử.
 */
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

/* =========================================================================
 * 2b. Pháp danh tiếng Việt có dấu ↔ tên đăng nhập kỹ thuật không dấu.
 *
 * WordPress lõi bóc dấu trong sanitize_user( $x, true ) nên validate_username()
 * luôn false với pháp danh có dấu. Giải pháp: pháp danh (giữ nguyên dấu) chỉ
 * dùng để hiển thị (display_name/nickname + meta nntm_phap_danh); user_login
 * kỹ thuật sinh tự động, không dấu, dùng để đăng nhập lõi WordPress.
 * ========================================================================= */

/**
 * Sinh tên đăng nhập kỹ thuật (không dấu, dạng slug) từ một pháp danh có dấu.
 *
 * Không đọc/ghi gì tới CSDL ngoài username_exists() để dò trùng — hàm thuần
 * cho mục đích sinh chuỗi. Trùng thì thêm hậu tố -2, -3... cho tới khi ra
 * user_login trống (giới hạn 200 lần thử để không lặp vô hạn).
 *
 * @param string $phap_danh Pháp danh gốc, có dấu, đã trim.
 * @return string Tên đăng nhập kỹ thuật, duy nhất tại thời điểm gọi.
 */
function nntm_tao_ten_dang_nhap( string $phap_danh ): string {
	$goc = sanitize_title( sanitize_user( remove_accents( $phap_danh ), true ) );

	if ( '' === $goc ) {
		$goc = 'thanh-vien';
	}

	$ten_dang_nhap = $goc;
	$dem           = 1;

	// Giới hạn 200 lần thử để chắc chắn không lặp vô hạn khi trùng liên tục.
	while ( username_exists( $ten_dang_nhap ) && $dem < 200 ) {
		++$dem;
		$ten_dang_nhap = $goc . '-' . $dem;
	}

	return $ten_dang_nhap;
}

/**
 * Tìm user theo pháp danh (meta `nntm_phap_danh`), không phân biệt hoa/thường,
 * đã trim. Tìm ra nhiều hơn một user (dữ liệu cũ/lỗi) thì trả null — không
 * đoán bừa, để tránh đăng nhập nhầm người.
 *
 * @param string $chuoi Chuỗi người dùng gõ vào ô "Tên đăng nhập/ Pháp danh".
 * @return WP_User|null
 */
function nntm_tim_user_theo_phap_danh( string $chuoi ): ?WP_User {
	$chuoi = trim( $chuoi );

	if ( '' === $chuoi ) {
		return null;
	}

	$users = get_users(
		array(
			'meta_key'     => 'nntm_phap_danh', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- bảng user nhỏ, chấp nhận được.
			'meta_value'   => $chuoi, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
			'meta_compare' => '=',
			'number'       => 2,
		)
	);

	// meta_value trong get_users() so khớp đúng-chuỗi (không phân biệt hoa/
	// thường tuỳ collation CSDL) — lọc lại bằng tay cho chắc, không phân biệt
	// hoa/thường theo kiểu ASCII lẫn có dấu.
	$khop        = array();
	$chuoi_thuong = function_exists( 'mb_strtolower' ) ? mb_strtolower( $chuoi, 'UTF-8' ) : strtolower( $chuoi );
	foreach ( $users as $user ) {
		$phap_danh_luu = trim( (string) get_user_meta( $user->ID, 'nntm_phap_danh', true ) );
		$luu_thuong    = function_exists( 'mb_strtolower' ) ? mb_strtolower( $phap_danh_luu, 'UTF-8' ) : strtolower( $phap_danh_luu );
		if ( $luu_thuong === $chuoi_thuong ) {
			$khop[] = $user;
		}
	}

	if ( 1 !== count( $khop ) ) {
		return null;
	}

	return $khop[0];
}

/* =========================================================================
 * 3. Xử lý POST — không bao giờ đá sang wp-login.php.
 * ========================================================================= */

/**
 * Điểm vào duy nhất, chạy ở template_redirect, phân theo trường ẩn
 * `nntm_auth_action`. Lỗi/thành công lưu vào $GLOBALS để 3 template part
 * in ngay trên form đang đứng.
 */
function nntm_handle_auth_post(): void {
	if ( empty( $_POST['nntm_auth_action'] ) ) {
		return;
	}

	$action = sanitize_key( wp_unslash( $_POST['nntm_auth_action'] ) );

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

/**
 * Xử lý đăng nhập bằng wp_signon(). Thất bại thì lưu WP_Error để form in
 * ra, KHÔNG chuyển hướng sang wp-login.php.
 */
function nntm_handle_login_post(): void {
	$nonce = isset( $_POST['nntm_auth_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nntm_auth_nonce'] ) ) : '';

	if ( ! wp_verify_nonce( $nonce, 'nntm_dang_nhap' ) ) {
		$GLOBALS['nntm_auth_errors'] = new WP_Error( 'nonce', __( 'Phiên làm việc đã hết hạn, vui lòng thử lại.', 'nntm' ) );
		return;
	}

	$redirect_to = isset( $_POST['redirect_to'] ) ? esc_url_raw( wp_unslash( $_POST['redirect_to'] ) ) : '';

	/*
	 * Ô này ghi "Tên đăng nhập/ Pháp danh" nên phải chấp nhận cả hai. KHÔNG
	 * sanitize_user() ở đây vì nó bóc dấu tiếng Việt, hỏng luôn pháp danh —
	 * chỉ trim(), giữ nguyên chuỗi người dùng gõ.
	 */
	$dang_nhap_tho = isset( $_POST['user_login'] ) ? trim( (string) wp_unslash( $_POST['user_login'] ) ) : '';
	$user_login    = $dang_nhap_tho;

	if ( '' !== $dang_nhap_tho && ! username_exists( $dang_nhap_tho ) && ! email_exists( $dang_nhap_tho ) ) {
		$user_theo_phap_danh = nntm_tim_user_theo_phap_danh( $dang_nhap_tho );
		if ( $user_theo_phap_danh instanceof WP_User ) {
			$user_login = $user_theo_phap_danh->user_login;
		}
	}

	$creds = array(
		'user_login'    => $user_login,
		'user_password' => isset( $_POST['user_password'] ) ? (string) wp_unslash( $_POST['user_password'] ) : '',
		'remember'      => ! empty( $_POST['remember'] ),
	);

	$user = wp_signon( $creds, is_ssl() );

	if ( is_wp_error( $user ) ) {
		// Lưu lỗi để in ngay trên form — KHÔNG đá sang wp-login.php.
		$GLOBALS['nntm_auth_errors'] = $user;
		return;
	}

	wp_safe_redirect( $redirect_to ? $redirect_to : home_url( '/' ) );
	exit;
}

/**
 * Xử lý "Quên mật khẩu" bằng retrieve_password() lõi WordPress.
 *
 * retrieve_password() nằm trong wp-includes/user.php từ WP 5.7 nên gọi
 * thẳng được ở đây, không cần nạp wp-login.php.
 */
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

	// Kết quả (đúng/sai) CỐ Ý không được đọc ở đây — xem ghi chú bảo mật dưới.
	retrieve_password( $user_login );

	/*
	 * Bảo mật: dù email/tên đăng nhập có tồn tại trong hệ thống hay không
	 * vẫn báo đúng MỘT thông báo chung chung, để không lộ ra ngoài danh
	 * sách email nào đã từng đăng ký.
	 */
	$GLOBALS['nntm_auth_success'] = __( 'Nếu email/tên đăng nhập có trong hệ thống, chúng tôi đã gửi hướng dẫn đặt lại mật khẩu.', 'nntm' );
}

/**
 * Xử lý đăng ký thành viên mới. Vai trò mặc định `subscriber` — đúng
 * quyết định khảo sát câu 7–8: ai cũng tự đăng ký được, ban quản trị
 * nâng cấp thủ công lên nntm_dai_si / nntm_kim_cuong sau.
 */
function nntm_handle_register_post(): void {
	$nonce = isset( $_POST['nntm_auth_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nntm_auth_nonce'] ) ) : '';

	if ( ! wp_verify_nonce( $nonce, 'nntm_dang_ky' ) ) {
		$GLOBALS['nntm_auth_errors'] = new WP_Error( 'nonce', __( 'Phiên làm việc đã hết hạn, vui lòng thử lại.', 'nntm' ) );
		return;
	}

	/*
	 * Form này là cửa đăng ký chính thức của site (khảo sát câu 7–8), nên
	 * CỐ Ý không đọc option `users_can_register` của WordPress ở đây — dù
	 * BQT có tắt "Ai cũng có thể đăng ký" trong Cài đặt chung, form này
	 * vẫn cho qua. Muốn khoá đăng ký thật sự thì cần thêm một cái công
	 * tắc riêng (ví dụ option `nntm_dong_dang_ky`) rồi kiểm tra ở đây.
	 */

	$redirect_to  = isset( $_POST['redirect_to'] ) ? esc_url_raw( wp_unslash( $_POST['redirect_to'] ) ) : '';
	$ho_ten       = isset( $_POST['ho_ten'] ) ? sanitize_text_field( wp_unslash( $_POST['ho_ten'] ) ) : '';
	$email        = isset( $_POST['user_email'] ) ? sanitize_email( wp_unslash( $_POST['user_email'] ) ) : '';
	// Pháp danh hiển thị công khai: GIỮ NGUYÊN dấu tiếng Việt, không đưa qua
	// sanitize_user()/validate_username() (bóc dấu, sẽ luôn báo lỗi). Tên
	// đăng nhập kỹ thuật không dấu sinh riêng ở dưới, sau khi qua hết kiểm tra.
	$phap_danh    = isset( $_POST['user_login'] ) ? trim( sanitize_text_field( wp_unslash( $_POST['user_login'] ) ) ) : '';
	$password     = isset( $_POST['user_password'] ) ? (string) wp_unslash( $_POST['user_password'] ) : '';
	$password_2   = isset( $_POST['user_password_2'] ) ? (string) wp_unslash( $_POST['user_password_2'] ) : '';
	$vung_mien_tho = isset( $_POST['nntm_vung_mien'] ) ? sanitize_text_field( wp_unslash( $_POST['nntm_vung_mien'] ) ) : '';
	// Chỉ chấp nhận key nằm trong danh sách cho phép — không khớp thì lưu rỗng
	// (trường không bắt buộc nên không cần báo lỗi cho người dùng).
	$vung_mien    = array_key_exists( $vung_mien_tho, nntm_vung_mien_options() ) ? $vung_mien_tho : '';
	$dia_chi      = isset( $_POST['nntm_dia_chi'] ) ? sanitize_text_field( wp_unslash( $_POST['nntm_dia_chi'] ) ) : '';
	$dien_thoai   = isset( $_POST['nntm_dien_thoai'] ) ? sanitize_text_field( wp_unslash( $_POST['nntm_dien_thoai'] ) ) : '';
	$nhan_ban_tin = ! empty( $_POST['nntm_nhan_ban_tin'] );
	$dong_y       = ! empty( $_POST['nntm_dong_y_dieu_khoan'] );

	// Giữ lại giá trị đã gõ để điền lại vào form khi có lỗi (trừ mật khẩu).
	$GLOBALS['nntm_auth_values'] = array(
		'ho_ten'            => $ho_ten,
		'user_email'        => $email,
		'user_login'        => $phap_danh,
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

	if ( '' === $phap_danh || mb_strlen( $phap_danh ) < 2 ) {
		$errors->add( 'user_login', __( 'Vui lòng nhập Pháp danh (ít nhất 2 ký tự).', 'nntm' ) );
	} elseif ( nntm_tim_user_theo_phap_danh( $phap_danh ) instanceof WP_User ) {
		$errors->add( 'user_login', __( 'Pháp danh này đã có người dùng.', 'nntm' ) );
	}

	if ( '' === $password || '' === $password_2 ) {
		$errors->add( 'password', __( 'Vui lòng nhập đủ Password và Re-type password.', 'nntm' ) );
	} elseif ( $password !== $password_2 ) {
		$errors->add( 'password', __( 'Hai mật khẩu không khớp.', 'nntm' ) );
	} elseif ( strlen( $password ) < 8 ) {
		$errors->add( 'password', __( 'Mật khẩu phải có ít nhất 8 ký tự.', 'nntm' ) );
	}

	if ( ! $dong_y ) {
		$errors->add( 'dieu_khoan', __( 'Vui lòng đồng ý với Điều khoản sử dụng.', 'nntm' ) );
	}

	if ( $errors->has_errors() ) {
		$GLOBALS['nntm_auth_errors'] = $errors;
		return;
	}

	// Sinh tên đăng nhập kỹ thuật không dấu SAU khi đã chắc chắn qua hết kiểm
	// tra ở trên (tránh sinh/lãng phí tên khi form còn lỗi khác).
	$user_login = nntm_tao_ten_dang_nhap( $phap_danh );

	$user_id = wp_insert_user(
		array(
			'user_login'   => $user_login,
			'user_email'   => $email,
			'user_pass'    => $password,
			// Pháp danh giữ nguyên dấu, hiển thị công khai.
			'display_name' => $phap_danh,
			'nickname'     => $phap_danh,
			'role'         => 'subscriber',
		)
	);

	if ( is_wp_error( $user_id ) ) {
		$GLOBALS['nntm_auth_errors'] = $user_id;
		return;
	}

	// Tách Họ và Tên thành first_name/last_name ở khoảng trắng cuối cùng.
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

	// Tự đăng nhập ngay sau khi đăng ký thành công.
	wp_set_current_user( $user_id );
	wp_set_auth_cookie( $user_id );

	wp_safe_redirect( $redirect_to ? $redirect_to : home_url( '/' ) );
	exit;
}
