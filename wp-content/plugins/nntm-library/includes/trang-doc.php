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

/**
 * Cờ chống đệ quy khi đang lấy permalink GỐC.
 *
 * @var bool
 */
$GLOBALS['nntm_lib_permalink_goc'] = false;

/**
 * Permalink GỐC của ấn phẩm — dạng /an-pham/<slug>/, chưa gắn /doc/.
 *
 * Cần hàm riêng vì bộ lọc bên dưới đã đổi get_permalink() thành đường dẫn
 * /doc/. Không có đường lấy bản gốc thì nntm_doc_url() sẽ ghép thành
 * /doc/doc/, và chỗ nào chuyển hướng về trang chi tiết sẽ quay vòng vô tận.
 *
 * @param int|WP_Post|null $post Ấn phẩm.
 */
function nntm_an_pham_url_chi_tiet( $post = null ): string {
	$post = get_post( $post );

	if ( ! $post ) {
		return '';
	}

	$GLOBALS['nntm_lib_permalink_goc'] = true;
	$url                               = (string) get_permalink( $post );
	$GLOBALS['nntm_lib_permalink_goc'] = false;

	return $url;
}

if ( ! function_exists( 'nntm_doc_url' ) ) {
	/**
	 * Đường dẫn trang đọc của một ấn phẩm.
	 *
	 * @param int|WP_Post|null $post Ấn phẩm.
	 */
	function nntm_doc_url( $post = null ): string {
		$goc = nntm_an_pham_url_chi_tiet( $post );

		return '' === $goc ? '' : trailingslashit( trailingslashit( $goc ) . NNTM_DOC_ENDPOINT );
	}
}

/**
 * MỌI đường dẫn tới ấn phẩm đều trỏ thẳng vào trình đọc.
 *
 * VÌ SAO LÀM Ở ĐÂY chứ không sửa từng khối: link tới ấn phẩm được dựng ở ít
 * nhất bảy chỗ — thẻ Card, article-feature, article-mosaic, trang lưu trữ, kết
 * quả tìm kiếm, trang chi tiết, popup Nghi Quỹ. Sửa từng chỗ thì lần nào cũng
 * sót một hai chỗ, và khối mới viết sau lại quên. Móc vào chính get_permalink()
 * thì mọi nơi tự đúng, kể cả nơi chưa ai nghĩ tới.
 *
 * KHÔNG CÓ NGOẠI LỆ. Cuốn chưa mở được (chờ trả lời câu hỏi, chờ thanh toán,
 * hay chưa gắn tệp) cũng đi vào /doc/ — cửa ải hiện ngay trong trình đọc chứ
 * không đá người ta về trang giới thiệu.
 *
 * Bản đầu em có chừa ngoại lệ cho mấy cuốn đó, vì lúc ấy /doc/ đá ngược ra
 * trang giới thiệu còn trang giới thiệu lại đẩy vào /doc/ — quay vòng vô tận.
 * Đã gỡ hẳn cú đá ngược đó trong nntm_doc_chan_quyen(), nên ngoại lệ hết lý do
 * tồn tại.
 *
 * @param string  $url  Đường dẫn WordPress vừa dựng.
 * @param WP_Post $post Bài viết.
 */
function nntm_lib_permalink_sang_doc( $url, $post ) {
	if ( $GLOBALS['nntm_lib_permalink_goc'] ) {
		return $url;
	}

	if ( is_admin() || ! $post instanceof WP_Post || 'nntm_publication' !== $post->post_type ) {
		return $url;
	}

	return trailingslashit( trailingslashit( $url ) . NNTM_DOC_ENDPOINT );
}
add_filter( 'post_type_link', 'nntm_lib_permalink_sang_doc', 10, 2 );

/**
 * Vì sao người đang xem chưa mở được cuốn này.
 *
 * '' = mở bình thường; 'quiz' = còn chờ trả lời câu hỏi; 'mua' = còn chờ thanh
 * toán; 'thieu-tep' = chưa gắn tệp PDF nào.
 *
 * @param int|WP_Post|null $post Ấn phẩm.
 */
function nntm_an_pham_ly_do_khoa( $post = null ): string {
	$post = get_post( $post );

	if ( ! $post || nntm_an_pham_can_access( $post ) ) {
		return '';
	}

	if ( function_exists( 'nntm_quiz_con_chan' ) && nntm_quiz_con_chan( (int) $post->ID ) ) {
		return 'quiz';
	}

	if ( function_exists( 'nntm_payos_dang_ban' ) && nntm_payos_dang_ban( $post ) ) {
		return 'mua';
	}

	return 'thieu-tep';
}

/**
 * Trang đọc KHÔNG đá ai ra ngoài nữa.
 *
 * VÌ SAO BỎ CÚ ĐÁ NGƯỢC: trước đây người chưa đủ quyền bị đẩy về trang giới
 * thiệu. Nhưng trang giới thiệu lại tự đẩy vào /doc/, nên phải chừa ngoại lệ
 * cho mấy cuốn đó — và thế là link của chúng không có /doc/, đúng thứ khách
 * không muốn.
 *
 * Nay cửa ải hiện ngay TRONG trình đọc: popup câu hỏi hoặc khung thanh toán tự
 * mở đè lên. Nội dung vẫn kín như cũ — nntm_an_pham_pdf_url() không trả tệp gốc
 * cho người chưa đủ quyền, nên chẳng có gì để lộ.
 *
 * Hàm giữ lại (rỗng) để chỗ nào từng gọi tới không vỡ.
 */
function nntm_doc_chan_quyen(): void {
}

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

	/*
	 * LUÔN sang trình đọc, không trừ trường hợp nào. Cửa ải (câu hỏi / thanh
	 * toán) hiện ngay trong đó.
	 */
	$dich = nntm_doc_url( $post );

	if ( '' === $dich ) {
		return;
	}

	wp_safe_redirect( $dich, 302 );
	exit;
}
add_action( 'template_redirect', 'nntm_an_pham_chuyen_sang_trang_doc', 6 );
