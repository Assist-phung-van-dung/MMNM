<?php

defined( 'ABSPATH' ) || exit;

/**
 * Assets for the /lien-he/ page.
 */
function nntm_enqueue_contact_assets(): void {
	if ( ! is_page( 'lien-he' ) ) {
		return;
	}

	$auth_css_path = NNTM_THEME_DIR . '/assets/css/pages/auth.css';
	wp_enqueue_style(
		'nntm-auth',
		NNTM_THEME_URI . '/assets/css/pages/auth.css',
		array( 'nntm-tokens', 'nntm-base' ),
		nntm_asset_version( $auth_css_path )
	);

	$contact_css_path = NNTM_THEME_DIR . '/assets/css/pages/contact.css';
	wp_enqueue_style(
		'nntm-contact',
		NNTM_THEME_URI . '/assets/css/pages/contact.css',
		array( 'nntm-auth', 'nntm-layout' ),
		nntm_asset_version( $contact_css_path )
	);

	$contact_js_path = NNTM_THEME_DIR . '/assets/js/contact.js';
	wp_enqueue_script(
		'nntm-contact',
		NNTM_THEME_URI . '/assets/js/contact.js',
		array(),
		nntm_asset_version( $contact_js_path ),
		true
	);

	wp_localize_script(
		'nntm-contact',
		'NNTMContact',
		array(
			'ajaxUrl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'nntm_contact_submit' ),
			'i18n'    => array(
				'sending'       => __( 'Đang gửi...', 'nntm' ),
				'submit'        => __( 'Gửi', 'nntm' ),
				'nameRequired'  => __( 'Vui lòng nhập họ và tên.', 'nntm' ),
				'emailRequired' => __( 'Vui lòng nhập email.', 'nntm' ),
				'emailInvalid'  => __( 'Email không đúng định dạng.', 'nntm' ),
				'question'      => __( 'Vui lòng nhập câu hỏi.', 'nntm' ),
				'genericError'  => __( 'Không thể gửi liên hệ lúc này. Vui lòng thử lại sau.', 'nntm' ),
			),
		)
	);
}
add_action( 'wp_enqueue_scripts', 'nntm_enqueue_contact_assets', 45 );

/**
 * Get a privacy-preserving key for basic contact-form rate limiting.
 */
function nntm_contact_rate_limit_key(): string {
	if ( is_user_logged_in() ) {
		return 'user_' . get_current_user_id();
	}

	$ip = '';
	if ( isset( $_SERVER['HTTP_CF_CONNECTING_IP'] ) ) {
		$candidate = trim( (string) wp_unslash( $_SERVER['HTTP_CF_CONNECTING_IP'] ) );
		if ( filter_var( $candidate, FILTER_VALIDATE_IP ) ) {
			$ip = $candidate;
		}
	}

	if ( '' === $ip && isset( $_SERVER['REMOTE_ADDR'] ) ) {
		$candidate = trim( (string) wp_unslash( $_SERVER['REMOTE_ADDR'] ) );
		if ( filter_var( $candidate, FILTER_VALIDATE_IP ) ) {
			$ip = $candidate;
		}
	}

	$user_agent = isset( $_SERVER['HTTP_USER_AGENT'] )
		? substr( sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ), 0, 190 )
		: '';

	$fingerprint = hash_hmac( 'sha256', $ip . '|' . $user_agent, wp_salt( 'auth' ) );

	return 'guest_' . substr( $fingerprint, 0, 32 );
}

/**
 * AJAX contact submit handler.
 */
function nntm_handle_contact_submit(): void {
	if ( ! check_ajax_referer( 'nntm_contact_submit', 'nonce', false ) ) {
		wp_send_json_error(
			array( 'message' => __( 'Phiên làm việc đã hết hạn. Vui lòng tải lại trang và thử lại.', 'nntm' ) ),
			403
		);
	}

	// Honeypot: silently pretend success so bots do not learn the field name.
	$website = isset( $_POST['website'] ) ? trim( (string) wp_unslash( $_POST['website'] ) ) : '';
	if ( '' !== $website ) {
		wp_send_json_success(
			array(
				'message' => __( 'Cảm ơn quý vị đã liên hệ với chúng tôi. Chúng tôi đã nhận được thông tin và sẽ phản hồi đến quý vị trong thời gian sớm nhất.', 'nntm' ),
			)
		);
	}

	$name     = isset( $_POST['ho_ten'] ) ? sanitize_text_field( wp_unslash( $_POST['ho_ten'] ) ) : '';
	$phone    = isset( $_POST['dien_thoai'] ) ? sanitize_text_field( wp_unslash( $_POST['dien_thoai'] ) ) : '';
	$email    = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
	$question = isset( $_POST['cau_hoi'] ) ? sanitize_textarea_field( wp_unslash( $_POST['cau_hoi'] ) ) : '';

	if ( '' === $name ) {
		wp_send_json_error( array( 'message' => __( 'Vui lòng nhập họ và tên.', 'nntm' ), 'field' => 'ho_ten' ), 422 );
	}

	if ( function_exists( 'mb_strlen' ) ? mb_strlen( $name, 'UTF-8' ) > 120 : strlen( $name ) > 120 ) {
		wp_send_json_error( array( 'message' => __( 'Họ và tên quá dài.', 'nntm' ), 'field' => 'ho_ten' ), 422 );
	}

	if ( '' === $email || ! is_email( $email ) ) {
		wp_send_json_error( array( 'message' => __( 'Vui lòng nhập email hợp lệ.', 'nntm' ), 'field' => 'email' ), 422 );
	}

	if ( '' !== $phone && strlen( $phone ) > 30 ) {
		wp_send_json_error( array( 'message' => __( 'Số điện thoại không hợp lệ.', 'nntm' ), 'field' => 'dien_thoai' ), 422 );
	}

	if ( '' === $question ) {
		wp_send_json_error( array( 'message' => __( 'Vui lòng nhập câu hỏi.', 'nntm' ), 'field' => 'cau_hoi' ), 422 );
	}

	$question_length = function_exists( 'mb_strlen' ) ? mb_strlen( $question, 'UTF-8' ) : strlen( $question );
	if ( $question_length > 5000 ) {
		wp_send_json_error( array( 'message' => __( 'Câu hỏi quá dài. Vui lòng rút gọn dưới 5.000 ký tự.', 'nntm' ), 'field' => 'cau_hoi' ), 422 );
	}

	$rate_key       = 'nntm_contact_' . nntm_contact_rate_limit_key();
	$last_submitted = (int) get_transient( $rate_key );

	if ( $last_submitted && ( time() - $last_submitted ) < 30 ) {
		wp_send_json_error(
			array( 'message' => __( 'Quý vị vừa gửi một liên hệ. Vui lòng chờ một chút trước khi gửi tiếp.', 'nntm' ) ),
			429
		);
	}

	set_transient( $rate_key, time(), 30 );

	// Lưu vào cơ sở dữ liệu TRƯỚC. Đây mới là nơi giữ lời nhắn; email chỉ là báo tin.
	$lh_id = function_exists( 'nntm_lh_luu' )
		? nntm_lh_luu(
			array(
				'ho_ten'     => $name,
				'email'      => $email,
				'dien_thoai' => $phone,
				'cau_hoi'    => $question,
			)
		)
		: 0;

	if ( $lh_id < 1 ) {
		delete_transient( $rate_key );
		wp_send_json_error(
			array( 'message' => __( 'Không lưu được liên hệ lúc này. Vui lòng thử lại sau.', 'nntm' ) ),
			500
		);
	}

	$recipient = sanitize_email( (string) apply_filters( 'nntm_contact_recipient', get_option( 'admin_email' ) ) );

	$site_name = wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES );
	$subject   = sprintf( '[%s] Liên hệ mới từ %s', $site_name, $name );
	$message   = implode(
		"\n",
		array(
			'Có liên hệ mới từ website ' . home_url( '/' ),
			'',
			'Họ và tên: ' . $name,
			'Điện thoại: ' . ( '' !== $phone ? $phone : 'Không cung cấp' ),
			'Email: ' . $email,
			'',
			'Câu hỏi:',
			$question,
			'',
			'Thời gian: ' . wp_date( 'd/m/Y H:i:s' ),
		)
	);

	$headers = array(
		'Content-Type: text/plain; charset=UTF-8',
		'Reply-To: ' . $name . ' <' . $email . '>',
	);

	$sent   = false;
	$loi    = '';
	$bat_loi = static function ( $err ) use ( &$loi ): void {
		if ( $err instanceof WP_Error ) {
			$loi = $err->get_error_message();
		}
	};

	if ( is_email( $recipient ) ) {
		add_action( 'wp_mail_failed', $bat_loi );
		$sent = (bool) wp_mail( $recipient, $subject, $message, $headers );
		remove_action( 'wp_mail_failed', $bat_loi );
	} else {
		$loi = __( 'Chưa cấu hình email nhận liên hệ.', 'nntm' );
	}

	update_post_meta( $lh_id, '_nntm_lh_da_gui_mail', $sent ? '1' : '0' );

	if ( ! $sent ) {
		update_post_meta( $lh_id, '_nntm_lh_loi_mail', $loi );

		// Email hỏng không phải lỗi của người gửi — lời nhắn đã nằm an toàn trong admin.
		error_log( 'NNTM contact form: khong gui duoc email bao. ' . $loi ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
	}

	wp_send_json_success(
		array(
			'message' => __( 'Cảm ơn quý vị đã liên hệ với chúng tôi. Chúng tôi đã nhận được thông tin và sẽ phản hồi đến quý vị trong thời gian sớm nhất.', 'nntm' ),
		)
	);
}
add_action( 'wp_ajax_nntm_contact_submit', 'nntm_handle_contact_submit' );
add_action( 'wp_ajax_nopriv_nntm_contact_submit', 'nntm_handle_contact_submit' );
