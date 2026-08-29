<?php
/**
 * Cổng quyền đọc ấn phẩm.
 *
 * CHUYỂN TỪ theme/inc/an-pham.php sang — giữ NGUYÊN tên hàm và nguyên hành vi.
 * Giữ nguyên tên là cố ý: theme, nntm-core và nntm-search đều đang gọi thẳng
 * mấy cái tên này, đổi tên là phải sửa một loạt chỗ mà chẳng được gì.
 *
 * Đây là cổng DUY NHẤT. Trang đọc, thẻ trong danh sách, trang lưu trữ, endpoint
 * phục vụ file, ghi tiến độ đọc — tất cả đều hỏi qua nntm_an_pham_can_access().
 * Thêm cách mở khoá mới (ví dụ: đã thanh toán qua PayOS) thì cắm vào filter
 * `nntm_an_pham_da_thanh_toan`, không sửa file này.
 *
 * @package NNTM_Library
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'nntm_an_pham_bi_khoa' ) ) {
	/**
	 * Ấn phẩm này có bị đánh dấu khoá không.
	 *
	 * Ô tích nằm ở meta box trong trang sửa Ấn phẩm (nntm-core/class-post-meta.php).
	 *
	 * @param int|WP_Post|null $post Ấn phẩm.
	 */
	function nntm_an_pham_bi_khoa( $post = null ): bool {
		$post = get_post( $post );

		return $post ? (bool) get_post_meta( $post->ID, '_nntm_pub_khoa', true ) : false;
	}
}

if ( ! function_exists( 'nntm_an_pham_da_thanh_toan' ) ) {
	/**
	 * Người đang xem đã trả tiền cho ấn phẩm này chưa.
	 *
	 * Mặc định LUÔN false — chưa có module thanh toán nào cắm vào. Đây chính là
	 * chỗ PayOS sẽ móc vào sau này; không có ai móc thì sách khoá vẫn khoá.
	 *
	 * @param int|WP_Post|null $post Ấn phẩm.
	 */
	function nntm_an_pham_da_thanh_toan( $post = null ): bool {
		$post = get_post( $post );

		return (bool) apply_filters( 'nntm_an_pham_da_thanh_toan', false, $post, get_current_user_id() );
	}
}

if ( ! function_exists( 'nntm_an_pham_can_access' ) ) {
	/**
	 * Người đang xem có được đọc ấn phẩm này không.
	 *
	 * @param int|WP_Post|null $post Ấn phẩm.
	 */
	function nntm_an_pham_can_access( $post = null ): bool {
		$post = get_post( $post );
		$mo   = ! nntm_an_pham_bi_khoa( $post ) || nntm_an_pham_da_thanh_toan( $post );

		return (bool) apply_filters( 'nntm_an_pham_can_access', $mo, $post, get_current_user_id() );
	}
}

if ( ! function_exists( 'nntm_an_pham_pdf_id' ) ) {
	/**
	 * ID tệp PDF gắn với ấn phẩm.
	 *
	 * @param int|WP_Post|null $post Ấn phẩm.
	 */
	function nntm_an_pham_pdf_id( $post = null ): int {
		$post = get_post( $post );

		return $post ? absint( get_post_meta( $post->ID, '_nntm_pdf_file', true ) ) : 0;
	}
}

if ( ! function_exists( 'nntm_an_pham_pdf_url' ) ) {
	/**
	 * Đường dẫn để trình đọc lấy nội dung PDF.
	 *
	 * ĐỔI HÀNH VI so với bản cũ trong theme: trước đây trả về
	 * wp_get_attachment_url() — tức URL thẳng tới tệp trong uploads, ai có link
	 * là tải được. Nay trả về endpoint có kiểm quyền, và kiểm luôn tại đây để
	 * không rò link cho người không có quyền ngay từ HTML.
	 *
	 * @param int|WP_Post|null $post Ấn phẩm.
	 */
	function nntm_an_pham_pdf_url( $post = null ): string {
		$post = get_post( $post );

		if ( ! $post ) {
			return '';
		}

		$att_id = nntm_an_pham_pdf_id( $post );

		if ( ! $att_id ) {
			return '';
		}

		if ( ! nntm_an_pham_can_access( $post ) ) {
			return '';
		}

		return nntm_lib_url_doc_pdf( $att_id );
	}
}
