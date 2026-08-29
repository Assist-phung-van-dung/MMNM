<?php
/**
 * Trang đọc /doc/ — đăng ký đường dẫn và canh cổng quyền.
 *
 * CHUYỂN TỪ theme/inc/doc-sach.php sang. Phần ở lại theme là phần hình ảnh:
 * chọn template, nạp CSS/JS, giấu thanh quản trị. Phần sang đây là phần quyết
 * định AI ĐƯỢC VÀO — nghiệp vụ, không được mất khi đổi theme.
 *
 * Tên hàm và tên endpoint giữ nguyên như bản cũ, nên các URL đã phát đi
 * (/an-pham/<slug>/doc/) vẫn sống, chỉ cần nạp lại luật đường dẫn một lần.
 *
 * @package NNTM_Library
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'NNTM_DOC_ENDPOINT' ) ) {
	define( 'NNTM_DOC_ENDPOINT', 'doc' );
}

/**
 * Đổi số này khi luật đường dẫn thay đổi để WordPress nạp lại đúng một lần.
 */
const NNTM_LIB_DOC_REWRITE_VER = '2-library';

/**
 * Đăng ký endpoint /doc/ sau mỗi permalink của ấn phẩm.
 */
function nntm_lib_dang_ky_endpoint_doc(): void {
	add_rewrite_endpoint( NNTM_DOC_ENDPOINT, EP_PERMALINK );

	if ( NNTM_LIB_DOC_REWRITE_VER !== get_option( 'nntm_lib_doc_rewrite_ver' ) ) {
		flush_rewrite_rules();
		update_option( 'nntm_lib_doc_rewrite_ver', NNTM_LIB_DOC_REWRITE_VER );
	}
}
add_action( 'init', 'nntm_lib_dang_ky_endpoint_doc' );

if ( ! function_exists( 'nntm_dang_o_trang_doc' ) ) {
	/**
	 * Đang ở trang đọc của một ấn phẩm hay không.
	 */
	function nntm_dang_o_trang_doc(): bool {
		global $wp_query;

		if ( ! is_singular( 'nntm_publication' ) ) {
			return false;
		}

		return isset( $wp_query->query_vars[ NNTM_DOC_ENDPOINT ] );
	}
}

if ( ! function_exists( 'nntm_doc_url' ) ) {
	/**
	 * Đường dẫn trang đọc của một ấn phẩm.
	 *
	 * @param int|WP_Post|null $post Ấn phẩm.
	 */
	function nntm_doc_url( $post = null ): string {
		$post = get_post( $post );

		if ( ! $post ) {
			return '';
		}

		return trailingslashit( trailingslashit( (string) get_permalink( $post ) ) . NNTM_DOC_ENDPOINT );
	}
}

/**
 * Chặn người không có quyền vào trang đọc.
 *
 * Chưa đăng nhập thì đưa sang đăng nhập rồi quay lại; đã đăng nhập mà vẫn không
 * đủ quyền thì đưa về trang chi tiết — nơi có nút mua hoặc nút trả lời câu hỏi.
 */
function nntm_doc_chan_quyen(): void {
	if ( ! nntm_dang_o_trang_doc() ) {
		return;
	}

	$post = get_queried_object();

	if ( ! $post instanceof WP_Post ) {
		return;
	}

	if ( nntm_an_pham_can_access( $post ) ) {
		return;
	}

	if ( ! is_user_logged_in() ) {
		$dich = function_exists( 'nntm_login_url' )
			? nntm_login_url( nntm_doc_url( $post ) )
			: wp_login_url( nntm_doc_url( $post ) );

		wp_safe_redirect( $dich );
		exit;
	}

	wp_safe_redirect( (string) get_permalink( $post ) );
	exit;
}
add_action( 'template_redirect', 'nntm_doc_chan_quyen', 5 );

/**
 * Vào thẳng trang đọc khi người xem đã có quyền.
 *
 * Có ?chi-tiet thì ở lại trang giới thiệu — dùng cho lúc quản trị muốn xem
 * trang chi tiết của một cuốn mình đã có quyền đọc.
 */
function nntm_an_pham_chuyen_sang_trang_doc(): void {
	if ( is_admin() || ! is_singular( 'nntm_publication' ) ) {
		return;
	}

	if ( nntm_dang_o_trang_doc() ) {
		return;
	}

	if ( is_feed() || is_embed() || is_preview() || is_customize_preview() ) {
		return;
	}

	if ( isset( $_GET['chi-tiet'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- chi doc, khong doi trang thai.
		return;
	}

	$post = get_queried_object();

	if ( ! $post instanceof WP_Post ) {
		return;
	}

	if ( ! apply_filters( 'nntm_an_pham_tu_chuyen_sang_doc', true, $post ) ) {
		return;
	}

	if ( ! nntm_an_pham_can_access( $post ) ) {
		return;
	}

	$dich = nntm_doc_url( $post );

	if ( '' === $dich ) {
		return;
	}

	wp_safe_redirect( $dich, 302 );
	exit;
}
add_action( 'template_redirect', 'nntm_an_pham_chuyen_sang_trang_doc', 6 );
