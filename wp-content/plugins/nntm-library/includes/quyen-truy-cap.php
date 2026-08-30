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
		$khoa = $post ? (bool) get_post_meta( $post->ID, '_nntm_pub_khoa', true ) : false;

		/*
		 * Mở bộ lọc để nơi khác tự kết luận "cuốn này đóng". nntm-payos dùng nó
		 * để nói: đã đặt giá thì đương nhiên phải mua mới đọc được — quản trị
		 * chỉ cần nhập giá, không phải nhớ tick thêm một ô nữa ở hộp khác.
		 */
		return (bool) apply_filters( 'nntm_an_pham_bi_khoa', $khoa, $post );
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

if ( ! function_exists( 'nntm_an_pham_pdf_xem_thu_id' ) ) {
	/**
	 * ID tệp PDF XEM THỬ của ấn phẩm (một hai trang đầu).
	 *
	 * VÌ SAO PHẢI CÓ TỆP RIÊNG chứ không cắt trang lúc chạy: máy chủ này không
	 * có Imagick lẫn Ghostscript nên không tách được trang từ tệp gốc. Gửi cả
	 * cuốn về rồi giấu trang bằng JavaScript thì mở công cụ nhà phát triển là
	 * đọc hết — tức là không chặn được gì.
	 *
	 * Quản trị tải lên một tệp riêng chỉ gồm mấy trang đầu. Máy chủ chỉ gửi
	 * đúng tệp đó cho người chưa mua, nên không có gì để lộ.
	 *
	 * @param int|WP_Post|null $post Ấn phẩm.
	 */
	function nntm_an_pham_pdf_xem_thu_id( $post = null ): int {
		$post = get_post( $post );

		return $post ? absint( get_post_meta( $post->ID, '_nntm_pdf_xem_thu', true ) ) : 0;
	}
}

if ( ! function_exists( 'nntm_an_pham_che_do_doc' ) ) {
	/**
	 * Người đang xem được đọc ấn phẩm này ở mức nào.
	 *
	 *   'day-du' — đọc trọn cuốn
	 *   'xem-thu' — chỉ được tệp xem thử, rồi gặp khung thanh toán
	 *   'chan'   — không có gì để đọc (chưa cấu hình tệp xem thử)
	 *
	 * @param int|WP_Post|null $post Ấn phẩm.
	 */
	function nntm_an_pham_che_do_doc( $post = null ): string {
		$post = get_post( $post );

		if ( ! $post ) {
			return 'chan';
		}

		if ( nntm_an_pham_can_access( $post ) ) {
			return 'day-du';
		}

		/*
		 * Phải kiểm tệp có dùng được không, không chỉ có ID hay không: bản ghi
		 * trỏ vào một ID không tồn tại thì coi như chưa có tệp xem thử, chứ đừng
		 * mời người ta vào trình đọc rồi báo lỗi.
		 */
		return nntm_lib_tep_dung_duoc( nntm_an_pham_pdf_xem_thu_id( $post ) ) ? 'xem-thu' : 'chan';
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

		/*
		 * Chưa đủ quyền thì trả về TỆP XEM THỬ, không trả tệp gốc. Đây là chỗ
		 * quyết định người chưa mua nhận được những byte nào — quyết ở máy chủ,
		 * không phải ở trình duyệt.
		 */
		$att_id = nntm_an_pham_can_access( $post )
			? nntm_an_pham_pdf_id( $post )
			: nntm_an_pham_pdf_xem_thu_id( $post );

		return nntm_lib_tep_dung_duoc( $att_id ) ? nntm_lib_url_doc_pdf( $att_id ) : '';
	}
}
