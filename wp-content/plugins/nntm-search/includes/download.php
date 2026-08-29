<?php
/**
 * Tải file PDF về máy.
 *
 * ĐỌC TRƯỚC KHI SỬA — chỗ này nghịch với một quyết định đã chốt:
 *
 * docs/04-kien-truc.md mục 4 chốt "file PDF gốc không bao giờ lộ URL", trình
 * đọc "gỡ hết nút tải/in", và watermark tên người đọc. Toàn bộ mục đó tồn tại
 * để CHẶN tải về. Chủ dự án yêu cầu ngược lại ngày 16/08/2026, sau khi đã chốt
 * PDF chỉ là file trong Thư viện Media.
 *
 * Hệ quả cần biết: một khi file nằm trong wp-content/uploads thì nó ĐÃ tải
 * được bởi bất kỳ ai đoán ra URL, không cần endpoint này. Endpoint này không
 * làm site an toàn hơn — nó chỉ làm cho việc siết lại sau này KHẢ THI:
 *
 *   1. Link đưa cho người dùng không phải là URL file, nên đổi cách lưu trữ về
 *      sau không phá link đã phát đi.
 *   2. Có đúng MỘT chỗ kiểm quyền. Hôm nào khách chốt "Thư Viện PDF bắt buộc
 *      đăng nhập" thì filter `nntm_an_pham_can_access` trả false là xong, không
 *      phải đi tìm khắp nơi.
 *
 * Muốn chặn thật thì vẫn phải làm nốt hai việc NGOÀI file này: chuyển file ra
 * ngoài thư mục web (hoặc chặn bằng .htaccess), và bật cổng quyền ở filter trên.
 *
 * @package NNTM_Search
 */

defined( 'ABSPATH' ) || exit;

add_action( 'rest_api_init', 'nntm_search_register_download_route' );

/**
 * Đăng ký endpoint tải file.
 */
function nntm_search_register_download_route(): void {
	register_rest_route(
		NNTM_SEARCH_NS,
		'/pdf/(?P<id>\d+)',
		array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => 'nntm_search_serve_pdf',
			'permission_callback' => '__return_true', // Quyền kiểm trong callback, xem bên dưới.
			'args'                => array(
				'id' => array(
					'required'          => true,
					'sanitize_callback' => 'absint',
				),
			),
		)
	);
}

/**
 * Đường dẫn tải một file PDF.
 *
 * @param int $attachment_id ID file đính kèm.
 * @return string
 */
function nntm_search_pdf_download_url( int $attachment_id ): string {
	return rest_url( NNTM_SEARCH_NS . '/pdf/' . $attachment_id );
}

/**
 * Đẩy file PDF về trình duyệt.
 *
 * Không dùng permission_callback vì nó chỉ trả JSON lỗi; ở đây cần trả trang
 * lỗi tử tế cho người bấm nhầm link trong trình duyệt.
 *
 * @param WP_REST_Request $request Yêu cầu.
 * @return WP_Error|void Thoát thẳng khi thành công.
 */
function nntm_search_serve_pdf( WP_REST_Request $request ) {
	$attachment_id = absint( $request->get_param( 'id' ) );

	// Chỉ nhận số nguyên rồi tra trong CSDL — không có chỗ nào cho đường dẫn
	// người dùng tự đặt, nên không có cửa cho tấn công đi ngược thư mục.
	if ( 'attachment' !== get_post_type( $attachment_id ) ) {
		return new WP_Error( 'nntm_not_found', __( 'Không tìm thấy tài liệu.', 'nntm' ), array( 'status' => 404 ) );
	}

	if ( 'application/pdf' !== get_post_mime_type( $attachment_id ) ) {
		return new WP_Error( 'nntm_not_pdf', __( 'Tệp này không phải PDF.', 'nntm' ), array( 'status' => 415 ) );
	}

	/*
	 * Cổng quyền. Chủ dự án đã CHỐT ngày 30/08/2026: file PDF nằm ngoài thư mục
	 * web và mọi đường ra đều phải qua kiểm quyền — nên câu hỏi bỏ ngỏ ở
	 * docs/03-chot mục A đã có đáp án.
	 *
	 * PHẢI gọi hàm nntm_an_pham_can_access() chứ không gọi thẳng filter cùng
	 * tên. Ô "khoá" của ấn phẩm (_nntm_pub_khoa) được xét TRONG THÂN HÀM, không
	 * phải trong filter; gọi thẳng filter với mặc định true là bỏ qua ô khoá và
	 * ai cũng tải được sách đang khoá. Đã đo bằng tay: trước khi sửa, endpoint
	 * này trả 200 cho đúng cuốn mà endpoint đọc trả 403.
	 */
	$post_id     = nntm_search_pdf_owner( $attachment_id );
	$publication = $post_id > 0 ? get_post( $post_id ) : null;

	/*
	 * Dùng hàm của nntm-library: nó xét MỌI ấn phẩm đang dùng tệp này, còn
	 * nntm_search_pdf_owner() chỉ lấy một cái theo post_parent. Hai cách tra ra
	 * hai ấn phẩm khác nhau chính là nguyên nhân của lỗ nói trên.
	 */
	if ( function_exists( 'nntm_lib_duoc_doc_tep' ) ) {
		$can_read = nntm_lib_duoc_doc_tep( $attachment_id );
	} else {
		$can_read = (bool) apply_filters(
			'nntm_an_pham_can_access',
			true,
			$publication,
			get_current_user_id()
		);
	}

	if ( ! $can_read ) {
		return new WP_Error(
			'nntm_forbidden',
			__( 'Tài liệu này dành cho thành viên. Đăng nhập rồi thử lại.', 'nntm' ),
			array( 'status' => 403 )
		);
	}

	$path = get_attached_file( $attachment_id );

	if ( ! $path || ! is_readable( $path ) ) {
		return new WP_Error( 'nntm_missing_file', __( 'Không đọc được tệp.', 'nntm' ), array( 'status' => 404 ) );
	}

	$name = basename( $path );
	$size = (int) filesize( $path );

	// Dọn sạch mọi thứ đã trót in ra, nếu không file tải về sẽ dính vài byte
	// rác ở đầu và Acrobat báo hỏng.
	while ( ob_get_level() > 0 ) {
		ob_end_clean();
	}

	nocache_headers();
	header( 'Content-Type: application/pdf' );
	header( 'Content-Disposition: attachment; filename="' . rawurlencode( $name ) . '"' );
	header( 'Content-Length: ' . $size );
	header( 'X-Content-Type-Options: nosniff' );

	readfile( $path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_readfile -- day file nhi phan, khong dung WP_Filesystem duoc.

	exit;
}
