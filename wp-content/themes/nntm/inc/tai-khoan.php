<?php
/**
 * Trang "Tài khoản của tôi" (/tai-khoan/) — tổng hợp mọi thứ liên quan tới
 * MỘT thành viên: đang đọc, thư viện đã mua, yêu thích, cộng tu, khoá tu,
 * hồ sơ, bảo mật.
 *
 * MÀN TỰ DỰNG — Figma chưa có thiết kế cho màn này (docs/ chưa có mục riêng).
 * Là ĐƯỜNG DẪN ẢO như /yeu-thich/ (inc/favorites.php): không tạo Page trong
 * DB, request được nhận diện qua $wp->request rồi ép template_include, vì
 * BQT không cần sửa nội dung màn chức năng này trong trình soạn thảo.
 *
 * File này CHỈ lo: định tuyến, chặn khách, không cache/không index, nạp
 * CSS/JS, xử lý hai form POST (hồ sơ + đổi mật khẩu), và các hàm lấy dữ liệu
 * cho từng khối (mỗi hàm nhỏ, một việc, đều có filter riêng để harness xem
 * thử (tools/preview/tai-khoan-harness.php) bơm dữ liệu giả mà không đụng
 * DB thật). Markup nằm ở template-parts/tai-khoan/*.php.
 */

defined( 'ABSPATH' ) || exit;

/* ============================================================ Định tuyến */

function nntm_tk_slug(): string {
	return 'tai-khoan';
}

/** Có phải request đang tới đúng /tai-khoan/ không — dùng $wp->request y hệt cách favorites.php làm, không cần rewrite rule riêng. */
function nntm_tk_is_request(): bool {
	global $wp;
	$request = isset( $wp->request ) ? trim( (string) $wp->request, '/' ) : '';
	return nntm_tk_slug() === $request;
}

/** URL trang tài khoản — CÙNG một filter với header.php để không có hai nguồn sự thật cho một đường dẫn. */
function nntm_tk_url(): string {
	return (string) apply_filters( 'nntm_account_page_url', home_url( '/' . nntm_tk_slug() . '/' ) );
}

function nntm_tk_disable_canonical( $redirect_url ) {
	return nntm_tk_is_request() ? false : $redirect_url;
}
add_filter( 'redirect_canonical', 'nntm_tk_disable_canonical' );

function nntm_tk_template_include( string $template ): string {
	if ( ! nntm_tk_is_request() ) {
		return $template;
	}

	$rieng = NNTM_THEME_DIR . '/page-tai-khoan.php';
	if ( ! is_readable( $rieng ) ) {
		return $template;
	}

	global $wp_query;
	if ( $wp_query instanceof WP_Query ) {
		$wp_query->is_404  = false;
		$wp_query->is_page = true;
	}
	status_header( 200 );

	return $rieng;
}
add_filter( 'template_include', 'nntm_tk_template_include', 99 );

function nntm_tk_body_class( array $classes ): array {
	if ( nntm_tk_is_request() ) {
		$classes[] = 'page-tai-khoan';
	}
	return array_values( array_unique( $classes ) );
}
add_filter( 'body_class', 'nntm_tk_body_class' );

function nntm_tk_document_title( string $title ): string {
	return nntm_tk_is_request() ? __( 'Tài khoản của tôi', 'nntm' ) : $title;
}
add_filter( 'pre_get_document_title', 'nntm_tk_document_title' );

/** Khách vào /tai-khoan/ thì đi đăng nhập trước, quay lại đúng đây sau khi vào được. */
function nntm_tk_yeu_cau_dang_nhap(): void {
	if ( ! nntm_tk_is_request() || is_user_logged_in() ) {
		return;
	}

	$dich = function_exists( 'nntm_login_url' ) ? nntm_login_url( nntm_tk_url() ) : wp_login_url( nntm_tk_url() );

	wp_safe_redirect( $dich );
	exit;
}
add_action( 'template_redirect', 'nntm_tk_yeu_cau_dang_nhap', 5 );

/** Số liệu là của riêng người xem — không được cache chung, không cho máy tìm kiếm. */
function nntm_tk_khong_cache(): void {
	if ( nntm_tk_is_request() && is_user_logged_in() ) {
		nocache_headers();
	}
}
add_action( 'template_redirect', 'nntm_tk_khong_cache', 6 );

add_filter(
	'wp_robots',
	static function ( array $robots ): array {
		return nntm_tk_is_request() ? wp_robots_no_robots( $robots ) : $robots;
	}
);

function nntm_tk_enqueue_assets(): void {
	if ( ! nntm_tk_is_request() ) {
		return;
	}

	$css = NNTM_THEME_DIR . '/assets/css/pages/tai-khoan.css';
	wp_enqueue_style(
		'nntm-tai-khoan',
		NNTM_THEME_URI . '/assets/css/pages/tai-khoan.css',
		array( 'nntm-tokens', 'nntm-base', 'nntm-layout' ),
		nntm_asset_version( $css )
	);

	$js = NNTM_THEME_DIR . '/assets/js/tai-khoan.js';
	wp_enqueue_script(
		'nntm-tai-khoan',
		NNTM_THEME_URI . '/assets/js/tai-khoan.js',
		array(),
		nntm_asset_version( $js ),
		true
	);
}
add_action( 'wp_enqueue_scripts', 'nntm_tk_enqueue_assets' );

/* ============================================================ Xử lý POST */

/**
 * Họ và Pháp danh (hoặc Họ và Tên) tách first_name/last_name Y HỆT auth.php —
 * lấy từ đăng ký để hai nơi khớp nhau, tránh mỗi nơi một kiểu tách tên.
 *
 * @return array{0:string,1:string} [first_name, last_name]
 */
function nntm_tk_tach_ten( string $ho_ten ): array {
	$parts = preg_split( '/\s+/', trim( $ho_ten ) );
	$last  = array_pop( $parts );
	$first = implode( ' ', $parts );

	if ( '' === $first ) {
		$first = $last;
		$last  = '';
	}

	return array( $first, (string) $last );
}

/** Kết thúc một lượt xử lý POST: PRG về đúng khu vực, kèm mã kết quả cố định (không echo thẳng giá trị từ request). */
function nntm_tk_ket_thuc_post( string $ma, string $muc ): void {
	$url = add_query_arg( 'tk', $ma, nntm_tk_url() ) . '#' . $muc;
	wp_safe_redirect( $url );
	exit;
}

/** Bảng ánh xạ mã kết quả -> thông báo cố định. Không có mã thì không hiện gì. */
function nntm_tk_thong_bao_tu_ma( string $ma ): ?array {
	$ds = array(
		'da-luu'                 => array(
			'loai'     => 'thanh-cong',
			'muc'      => 'ho-so',
			'noi_dung' => __( 'Đã lưu thông tin hồ sơ.', 'nntm' ),
		),
		'phap-danh-trong'        => array(
			'loai'     => 'loi',
			'muc'      => 'ho-so',
			'noi_dung' => __( 'Vui lòng nhập Pháp danh (ít nhất 2 ký tự).', 'nntm' ),
		),
		'phien-het-han-ho-so'    => array(
			'loai'     => 'loi',
			'muc'      => 'ho-so',
			'noi_dung' => __( 'Phiên làm việc đã hết hạn, vui lòng thử lại.', 'nntm' ),
		),
		'da-doi-mat-khau'        => array(
			'loai'     => 'thanh-cong',
			'muc'      => 'bao-mat',
			'noi_dung' => __( 'Đã đổi mật khẩu.', 'nntm' ),
		),
		'sai-mat-khau'           => array(
			'loai'     => 'loi',
			'muc'      => 'bao-mat',
			'noi_dung' => __( 'Mật khẩu hiện tại không đúng.', 'nntm' ),
		),
		'mat-khau-ngan'          => array(
			'loai'     => 'loi',
			'muc'      => 'bao-mat',
			'noi_dung' => __( 'Mật khẩu mới phải có ít nhất 8 ký tự.', 'nntm' ),
		),
		'mat-khau-khong-khop'    => array(
			'loai'     => 'loi',
			'muc'      => 'bao-mat',
			'noi_dung' => __( 'Hai mật khẩu mới không khớp.', 'nntm' ),
		),
		'phien-het-han-mat-khau' => array(
			'loai'     => 'loi',
			'muc'      => 'bao-mat',
			'noi_dung' => __( 'Phiên làm việc đã hết hạn, vui lòng thử lại.', 'nntm' ),
		),
	);

	return $ds[ $ma ] ?? null;
}

/** Đọc ?tk= một lần, trả về thông báo đã ánh xạ (hoặc null). Chỉ đọc để hiển thị, không dùng để ghi dữ liệu. */
function nntm_tk_thong_bao_hien_tai(): ?array {
	static $da_doc = false;
	static $ket_qua = null;

	if ( $da_doc ) {
		return $ket_qua;
	}
	$da_doc = true;

	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- chỉ đọc mã kết quả cố định để hiển thị, giá trị thô không bao giờ được echo.
	$ma = isset( $_GET['tk'] ) ? sanitize_key( wp_unslash( $_GET['tk'] ) ) : '';

	$ket_qua = '' !== $ma ? nntm_tk_thong_bao_tu_ma( $ma ) : null;
	return $ket_qua;
}

function nntm_tk_xu_ly_ho_so(): void {
	$nonce = isset( $_POST['nntm_tk_nonce_ho_so'] ) ? sanitize_text_field( wp_unslash( $_POST['nntm_tk_nonce_ho_so'] ) ) : '';
	if ( ! wp_verify_nonce( $nonce, 'nntm_tk_ho_so' ) ) {
		nntm_tk_ket_thuc_post( 'phien-het-han-ho-so', 'ho-so' );
	}

	// KHÔNG BAO GIỜ lấy user id từ request — luôn là người đang đăng nhập.
	$uid = get_current_user_id();

	$ho_ten          = isset( $_POST['ho_ten'] ) ? sanitize_text_field( wp_unslash( $_POST['ho_ten'] ) ) : '';
	$phap_danh       = isset( $_POST['nntm_phap_danh'] ) ? trim( sanitize_text_field( wp_unslash( $_POST['nntm_phap_danh'] ) ) ) : '';
	$dien_thoai_tho  = isset( $_POST['nntm_dien_thoai'] ) ? (string) wp_unslash( $_POST['nntm_dien_thoai'] ) : '';
	$dien_thoai      = (string) preg_replace( '/[^0-9+]/', '', $dien_thoai_tho );
	$vung_mien_tho   = isset( $_POST['nntm_vung_mien'] ) ? sanitize_text_field( wp_unslash( $_POST['nntm_vung_mien'] ) ) : '';
	$vung_mien       = array_key_exists( $vung_mien_tho, nntm_vung_mien_options() ) ? $vung_mien_tho : '';
	$dia_chi         = isset( $_POST['nntm_dia_chi'] ) ? sanitize_textarea_field( wp_unslash( $_POST['nntm_dia_chi'] ) ) : '';
	$nhan_ban_tin    = ! empty( $_POST['nntm_nhan_ban_tin'] );

	if ( '' === $phap_danh || mb_strlen( $phap_danh ) < 2 ) {
		nntm_tk_ket_thuc_post( 'phap-danh-trong', 'ho-so' );
	}

	list( $first_name, $last_name ) = nntm_tk_tach_ten( $ho_ten );

	wp_update_user(
		array(
			'ID'           => $uid,
			'first_name'   => $first_name,
			'last_name'    => $last_name,
			// Pháp danh vẫn là tên hiển thị công khai, giống lúc đăng ký (inc/auth.php).
			'display_name' => $phap_danh,
			'nickname'     => $phap_danh,
		)
	);

	update_user_meta( $uid, 'nntm_phap_danh', $phap_danh );
	update_user_meta( $uid, 'nntm_dien_thoai', $dien_thoai );
	update_user_meta( $uid, 'nntm_vung_mien', $vung_mien );
	update_user_meta( $uid, 'nntm_dia_chi', $dia_chi );
	update_user_meta( $uid, 'nntm_nhan_ban_tin', $nhan_ban_tin ? '1' : '0' );

	nntm_tk_ket_thuc_post( 'da-luu', 'ho-so' );
}

function nntm_tk_xu_ly_mat_khau(): void {
	$nonce = isset( $_POST['nntm_tk_nonce_mat_khau'] ) ? sanitize_text_field( wp_unslash( $_POST['nntm_tk_nonce_mat_khau'] ) ) : '';
	if ( ! wp_verify_nonce( $nonce, 'nntm_tk_mat_khau' ) ) {
		nntm_tk_ket_thuc_post( 'phien-het-han-mat-khau', 'bao-mat' );
	}

	$uid  = get_current_user_id();
	$user = get_userdata( $uid );

	$mat_khau_cu   = isset( $_POST['mat_khau_hien_tai'] ) ? (string) wp_unslash( $_POST['mat_khau_hien_tai'] ) : '';
	$mat_khau_moi  = isset( $_POST['mat_khau_moi'] ) ? (string) wp_unslash( $_POST['mat_khau_moi'] ) : '';
	$mat_khau_moi2 = isset( $_POST['mat_khau_moi_2'] ) ? (string) wp_unslash( $_POST['mat_khau_moi_2'] ) : '';

	if ( ! $user || ! wp_check_password( $mat_khau_cu, $user->user_pass, $uid ) ) {
		nntm_tk_ket_thuc_post( 'sai-mat-khau', 'bao-mat' );
	}

	if ( strlen( $mat_khau_moi ) < 8 ) {
		nntm_tk_ket_thuc_post( 'mat-khau-ngan', 'bao-mat' );
	}

	if ( $mat_khau_moi !== $mat_khau_moi2 ) {
		nntm_tk_ket_thuc_post( 'mat-khau-khong-khop', 'bao-mat' );
	}

	wp_set_password( $mat_khau_moi, $uid );

	/*
	 * wp_set_password() đổi khoá băm và huỷ session hiện có — không đăng nhập
	 * lại ngay thì người vừa đổi mật khẩu thành công lại bị đá ra ngoài, cảm
	 * giác chẳng khác gì vừa gặp lỗi.
	 */
	wp_set_current_user( $uid );
	wp_set_auth_cookie( $uid );

	nntm_tk_ket_thuc_post( 'da-doi-mat-khau', 'bao-mat' );
}

function nntm_tk_xu_ly_post(): void {
	if ( ! nntm_tk_is_request() || ! is_user_logged_in() ) {
		return;
	}

	if ( empty( $_POST['nntm_tk_action'] ) ) {
		return;
	}

	$action = sanitize_key( wp_unslash( $_POST['nntm_tk_action'] ) );

	if ( 'ho-so' === $action ) {
		nntm_tk_xu_ly_ho_so();
	} elseif ( 'mat-khau' === $action ) {
		nntm_tk_xu_ly_mat_khau();
	}
}
add_action( 'template_redirect', 'nntm_tk_xu_ly_post', 8 );

/* ============================================================ Dữ liệu: dải đầu */

function nntm_tk_chu_dau( string $ten ): string {
	$ten = trim( $ten );
	if ( '' === $ten ) {
		return '?';
	}
	return mb_strtoupper( mb_substr( $ten, 0, 1 ) );
}

function nntm_tk_ten_cap( ?string $rank ): string {
	if ( 'kim_cuong' === $rank ) {
		return __( 'Kim Cương Hành Giả', 'nntm' );
	}
	if ( 'dai_si' === $rank ) {
		return __( 'Đại Sĩ Hành Giả', 'nntm' );
	}
	return __( 'Thành viên', 'nntm' );
}

/**
 * Dữ liệu dải đầu (monogram, Pháp danh, cấp, ngày gia nhập).
 *
 * KHÔNG dùng get_avatar()/gravatar — docs/04-kien-truc.md mục "quyền riêng
 * tư ảnh đại diện" nói rõ không hiện ảnh thật của thành viên khi trang chưa
 * có tính năng tự tải ảnh lên; monogram chữ cái đầu Pháp danh là đủ.
 */
function nntm_tk_lay_hero( int $uid ): array {
	$user      = get_userdata( $uid );
	$phap_danh = $user ? (string) get_user_meta( $uid, 'nntm_phap_danh', true ) : '';
	if ( '' === $phap_danh && $user ) {
		$phap_danh = $user->display_name;
	}

	$mac_dinh = array(
		'phap_danh'     => $phap_danh,
		'ho_ten'        => $user ? trim( $user->first_name . ' ' . $user->last_name ) : '',
		'chu_dau'       => nntm_tk_chu_dau( $phap_danh ),
		'ten_cap'       => nntm_tk_ten_cap( $user && function_exists( 'nntm_user_rank' ) ? nntm_user_rank( $uid ) : null ),
		'ngay_gia_nhap' => $user ? mysql2date( 'm/Y', $user->user_registered ) : '',
	);

	return (array) apply_filters( 'nntm_tk_du_lieu_hero', $mac_dinh, $uid );
}

/* ============================================================ Dữ liệu: thống kê */

function nntm_tk_bang_ton_tai( string $ten_bang ): bool {
	global $wpdb;

	static $cache = array();
	if ( array_key_exists( $ten_bang, $cache ) ) {
		return $cache[ $ten_bang ];
	}

	$like  = $wpdb->esc_like( $ten_bang );
	$found = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $like ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery

	$cache[ $ten_bang ] = ( $found === $ten_bang );
	return $cache[ $ten_bang ];
}

function nntm_tk_bang_dang_doc(): string {
	global $wpdb;
	return $wpdb->prefix . 'nntm_reading_progress';
}

function nntm_tk_bang_don_hang(): string {
	global $wpdb;
	return $wpdb->prefix . 'nntm_payos_orders';
}

/** 4 ô số nổi trên mép dải đầu — ẩn hẳn ô nào không có nguồn dữ liệu (bảng chưa cài, plugin tắt). */
function nntm_tk_lay_thong_ke( int $uid ): array {
	global $wpdb;
	$o = array();

	if ( $uid > 0 && nntm_tk_bang_ton_tai( nntm_tk_bang_dang_doc() ) ) {
		$bang = nntm_tk_bang_dang_doc();
		$so   = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$bang} WHERE user_id = %d AND object_type = %s", $uid, 'publication' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL
		$o['dang_doc'] = array(
			'nhan' => __( 'Sách đang đọc', 'nntm' ),
			'so'   => $so,
			'href' => '#dang-doc',
		);
	}

	if ( $uid > 0 && nntm_tk_bang_ton_tai( nntm_tk_bang_don_hang() ) ) {
		$bang = nntm_tk_bang_don_hang();
		$so   = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(DISTINCT post_id) FROM {$bang} WHERE user_id = %d AND status = 'paid'", $uid ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL
		$o['thu_vien'] = array(
			'nhan' => __( 'Sách đã thỉnh', 'nntm' ),
			'so'   => $so,
			'href' => '#thu-vien',
		);
	}

	if ( $uid > 0 && function_exists( 'nntm_section_favorites_table_exists' ) && nntm_section_favorites_table_exists() ) {
		$bang = $wpdb->prefix . 'nntm_favorites';
		$so   = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$bang} WHERE user_id = %d", $uid ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL
		$o['yeu_thich'] = array(
			'nhan' => __( 'Yêu thích', 'nntm' ),
			'so'   => $so,
			'href' => '#yeu-thich',
		);
	}

	if ( $uid > 0 && function_exists( 'nntm_dkkt_bang' ) && nntm_tk_bang_ton_tai( nntm_dkkt_bang() ) ) {
		$bang = nntm_dkkt_bang();
		$so   = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$bang} WHERE user_id = %d AND status <> 'cancelled'", $uid ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery,WordPress.DB.PreparedSQL
		$o['khoa_tu'] = array(
			'nhan' => __( 'Khoá tu đã đăng ký', 'nntm' ),
			'so'   => $so,
			'href' => '#khoa-tu',
		);
	}

	return (array) apply_filters( 'nntm_tk_du_lieu_thong_ke', $o, $uid );
}

/* ============================================================ Dữ liệu: Đang đọc */

function nntm_tk_lay_dang_doc( int $uid, int $so_luong = 6 ): array {
	$mac_dinh = array();

	if ( $uid <= 0 || ! nntm_tk_bang_ton_tai( nntm_tk_bang_dang_doc() ) ) {
		return (array) apply_filters( 'nntm_tk_du_lieu_dang_doc', $mac_dinh, $uid );
	}

	global $wpdb;
	$bang = nntm_tk_bang_dang_doc();

	// Lấy dư ra vì một số dòng có thể trỏ tới ấn phẩm đã gỡ/hết quyền — lọc lại bên dưới.
	$hang = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT object_id, position, updated_at FROM {$bang} WHERE user_id = %d AND object_type = %s ORDER BY updated_at DESC LIMIT %d", // phpcs:ignore WordPress.DB.PreparedSQL
			$uid,
			'publication',
			$so_luong * 3
		)
	); // phpcs:ignore WordPress.DB.DirectDatabaseQuery

	$ket_qua = array();
	foreach ( (array) $hang as $dong ) {
		if ( count( $ket_qua ) >= $so_luong ) {
			break;
		}

		$post = get_post( (int) $dong->object_id );
		if ( ! $post instanceof WP_Post || 'publish' !== $post->post_status || 'nntm_publication' !== $post->post_type ) {
			continue;
		}
		if ( function_exists( 'nntm_an_pham_can_access' ) && ! nntm_an_pham_can_access( $post ) ) {
			continue;
		}

		$trang = max( 1, (int) $dong->position );
		$url   = function_exists( 'nntm_doc_url' ) ? nntm_doc_url( $post ) : (string) get_permalink( $post );
		if ( '' !== $url ) {
			$url = add_query_arg( 'trang', $trang, $url );
		}

		$ket_qua[] = array(
			'tieu_de'    => get_the_title( $post ),
			'url'        => $url,
			'anh'        => (string) get_the_post_thumbnail_url( $post, 'medium' ),
			'trang'      => $trang,
			'thoi_gian'  => sprintf(
				/* translators: %s: khoảng thời gian, ví dụ "3 ngày" */
				__( '%s trước', 'nntm' ),
				human_time_diff( strtotime( (string) $dong->updated_at ), current_time( 'timestamp' ) ) // phpcs:ignore WordPress.DateTime.CurrentTimeTimestamp.Requested
			),
		);
	}

	return (array) apply_filters( 'nntm_tk_du_lieu_dang_doc', $ket_qua, $uid );
}

/* ============================================================ Dữ liệu: Thư viện của tôi */

function nntm_tk_trang_thai_don_nhan( string $trang_thai ): string {
	switch ( $trang_thai ) {
		case 'paid':
			return __( 'Đã thanh toán', 'nntm' );
		case 'pending':
			return __( 'Đang chờ', 'nntm' );
		case 'cancelled':
			return __( 'Đã huỷ', 'nntm' );
		case 'failed':
			return __( 'Thất bại', 'nntm' );
		default:
			return $trang_thai;
	}
}

/**
 * Sách đã mua + lịch sử đơn hàng. KHÔNG BAO GIỜ trả qr_code/payment_link_id
 * ra ngoài — hai cột đó chỉ để trình mở lại khung thanh toán, không phải thứ
 * để in lên màn hình tài khoản.
 */
function nntm_tk_lay_thu_vien( int $uid ): array {
	$mac_dinh = array(
		'sach'     => array(),
		'don_hang' => array(),
	);

	if ( $uid <= 0 || ! nntm_tk_bang_ton_tai( nntm_tk_bang_don_hang() ) ) {
		return (array) apply_filters( 'nntm_tk_du_lieu_thu_vien', $mac_dinh, $uid );
	}

	global $wpdb;
	$bang = nntm_tk_bang_don_hang();

	$don_goc = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT post_id, amount, status, order_code, created_at FROM {$bang} WHERE user_id = %d ORDER BY created_at DESC, id DESC LIMIT 50", // phpcs:ignore WordPress.DB.PreparedSQL
			$uid
		)
	); // phpcs:ignore WordPress.DB.DirectDatabaseQuery

	$sach     = array();
	$da_them  = array();
	$don_hang = array();

	foreach ( (array) $don_goc as $don ) {
		$post_id = (int) $don->post_id;
		$post    = get_post( $post_id );
		$ten     = $post instanceof WP_Post ? get_the_title( $post ) : __( '(Ấn phẩm không còn tồn tại)', 'nntm' );

		if ( 'paid' === $don->status && $post instanceof WP_Post && ! in_array( $post_id, $da_them, true ) ) {
			$sach[]    = array(
				'tieu_de' => $ten,
				'url'     => function_exists( 'nntm_doc_url' ) ? nntm_doc_url( $post ) : (string) get_permalink( $post ),
				'anh'     => (string) get_the_post_thumbnail_url( $post, 'medium' ),
			);
			$da_them[] = $post_id;
		}

		$tiep_tuc_url = '';
		if ( 'pending' === $don->status && $post instanceof WP_Post ) {
			// Đơn còn treo: đưa thẳng về trang đọc, nơi paywall sẽ mở lại đúng khung thanh toán.
			$tiep_tuc_url = function_exists( 'nntm_doc_url' ) ? nntm_doc_url( $post ) : (string) get_permalink( $post );
		}

		$don_hang[] = array(
			'ma'               => (string) $don->order_code,
			'ten_an_pham'      => $ten,
			'so_tien_hien_thi' => number_format_i18n( (int) $don->amount ) . ' ₫',
			'trang_thai_ma'    => sanitize_key( $don->status ),
			'trang_thai_nhan'  => nntm_tk_trang_thai_don_nhan( $don->status ),
			'ngay'             => mysql2date( get_option( 'date_format' ), $don->created_at ),
			'tiep_tuc_url'     => $tiep_tuc_url,
		);
	}

	return (array) apply_filters(
		'nntm_tk_du_lieu_thu_vien',
		array(
			'sach'     => $sach,
			'don_hang' => $don_hang,
		),
		$uid
	);
}

/* ============================================================ Dữ liệu: Yêu thích */

function nntm_tk_ten_loai_bai( string $post_type ): string {
	$obj = get_post_type_object( $post_type );
	return $obj ? (string) $obj->labels->singular_name : $post_type;
}

function nntm_tk_lay_yeu_thich( int $uid ): array {
	$mac_dinh = array(
		'muc' => array(),
		'tong' => 0,
	);

	if ( $uid <= 0 || ! function_exists( 'nntm_section_get_favorites_page' ) ) {
		return (array) apply_filters( 'nntm_tk_du_lieu_yeu_thich', $mac_dinh, $uid );
	}

	$trang = nntm_section_get_favorites_page( $uid, 1, 6 );

	$muc = array();
	foreach ( $trang['posts'] as $post ) {
		$muc[] = array(
			'tieu_de'   => get_the_title( $post ),
			'url'       => (string) get_permalink( $post ),
			'anh'       => (string) get_the_post_thumbnail_url( $post, 'medium' ),
			'loai_nhan' => nntm_tk_ten_loai_bai( $post->post_type ),
		);
	}

	return (array) apply_filters(
		'nntm_tk_du_lieu_yeu_thich',
		array(
			'muc'  => $muc,
			'tong' => (int) $trang['total'],
		),
		$uid
	);
}

/* ============================================================ Dữ liệu: Cộng tu */

/** Thẻ tóm tắt, cố tình nhẹ — không lặp lại cả dashboard, chỉ dẫn đường sang đó. */
function nntm_tk_lay_cong_tu( int $uid ): array {
	$mac_dinh = array(
		'url'              => function_exists( 'nntm_congtu_url_ca_nhan' ) ? nntm_congtu_url_ca_nhan() : '',
		'ten_chuong_trinh' => '',
		'cam_ket'          => 0,
		'thuc_hien'        => 0,
		'co_du_lieu'       => false,
	);

	if ( $uid > 0 && function_exists( 'nntm_program_hien_tai' ) ) {
		$ct = nntm_program_hien_tai();

		if ( $ct instanceof WP_Post ) {
			$mac_dinh['ten_chuong_trinh'] = get_the_title( $ct );

			if ( function_exists( 'nntm_kpi_da_tham_gia' ) && function_exists( 'nntm_kpi_tong_cua_nguoi' ) && nntm_kpi_da_tham_gia( $ct->ID, $uid ) ) {
				$tong = nntm_kpi_tong_cua_nguoi( $ct->ID, $uid );

				$mac_dinh['cam_ket']    = (int) $tong['cam_ket'];
				$mac_dinh['thuc_hien']  = (int) $tong['thuc_hien'];
				$mac_dinh['co_du_lieu'] = true;
			}
		}
	}

	return (array) apply_filters( 'nntm_tk_du_lieu_cong_tu', $mac_dinh, $uid );
}

/* ============================================================ Dữ liệu: Khoá tu */

function nntm_tk_lay_khoa_tu( int $uid ): array {
	$mac_dinh = array();

	if ( $uid <= 0 || ! function_exists( 'nntm_dkkt_bang' ) || ! nntm_tk_bang_ton_tai( nntm_dkkt_bang() ) ) {
		return (array) apply_filters( 'nntm_tk_du_lieu_khoa_tu', $mac_dinh, $uid );
	}

	global $wpdb;
	$bang = nntm_dkkt_bang();

	$hang = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT retreat_id, status, created_at FROM {$bang} WHERE user_id = %d ORDER BY created_at DESC, id DESC LIMIT 20", // phpcs:ignore WordPress.DB.PreparedSQL
			$uid
		)
	); // phpcs:ignore WordPress.DB.DirectDatabaseQuery

	$ket_qua = array();
	foreach ( (array) $hang as $dong ) {
		$post      = get_post( (int) $dong->retreat_id );
		$ket_qua[] = array(
			'tieu_de'         => $post instanceof WP_Post ? get_the_title( $post ) : __( '(Khoá tu không còn tồn tại)', 'nntm' ),
			'url'             => $post instanceof WP_Post ? (string) get_permalink( $post ) : '',
			'trang_thai_ma'   => sanitize_key( $dong->status ),
			'trang_thai_nhan' => function_exists( 'nntm_dkkt_ten_trang_thai' ) ? nntm_dkkt_ten_trang_thai( $dong->status ) : $dong->status,
			'ngay'            => mysql2date( get_option( 'date_format' ), $dong->created_at ),
		);
	}

	return (array) apply_filters( 'nntm_tk_du_lieu_khoa_tu', $ket_qua, $uid );
}

/* ============================================================ Dữ liệu: Hồ sơ */

function nntm_tk_lay_ho_so( int $uid ): array {
	$user = get_userdata( $uid );

	$mac_dinh = array(
		'ho_ten'       => $user ? trim( $user->first_name . ' ' . $user->last_name ) : '',
		'phap_danh'    => $user ? (string) get_user_meta( $uid, 'nntm_phap_danh', true ) : '',
		'email'        => $user ? $user->user_email : '',
		'dien_thoai'   => $user ? (string) get_user_meta( $uid, 'nntm_dien_thoai', true ) : '',
		'vung_mien'    => $user ? (string) get_user_meta( $uid, 'nntm_vung_mien', true ) : '',
		'dia_chi'      => $user ? (string) get_user_meta( $uid, 'nntm_dia_chi', true ) : '',
		'nhan_ban_tin' => $user ? ( '1' === get_user_meta( $uid, 'nntm_nhan_ban_tin', true ) ) : false,
	);

	return (array) apply_filters( 'nntm_tk_du_lieu_ho_so', $mac_dinh, $uid );
}
