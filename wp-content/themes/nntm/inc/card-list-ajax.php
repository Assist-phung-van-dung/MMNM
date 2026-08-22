<?php
/**
 * Đổi trang khối nntm/card-list KHÔNG tải lại trang.
 *
 * Yêu cầu chủ dự án 21/08/2026 (trang Kim Cương Hành Giả): "phần block thứ,
 * anh muốn khi nhấn chuyển page sẽ không load lại trang mà đổi luôn trên
 * giao diện hiện tại."
 *
 * CÁCH LÀM — và lý do KHÔNG dùng admin-ajax.php ở đây:
 *   Yêu cầu gửi tới CHÍNH đường dẫn trang đó (ví dụ
 *   /kim-cuong-hanh-gia/page/2/) chỉ thêm ?nntm_cardlist_ajax=1. Nhờ vậy
 *   WordPress phân tích URL y như một lần bấm chuột thường:
 *     - get_query_var('paged') = 2 (đo thật 21/08/2026: rewrite khớp
 *       pagename=kim-cuong-hanh-gia&paged=2), nên blocks/card-list/render.php
 *       lấy đúng trang mà KHÔNG phải chép lại logic phân trang ở đây;
 *     - is_page('kim-cuong-hanh-gia') = true, nên filter render_block_data ở
 *       inc/kim-cuong-hanh-gia.php vẫn gắn class .nntm-kchg-articles — dựng
 *       qua admin-ajax.php thì is_page() sai và cả dải mất kiểu dáng;
 *     - get_pagenum_link() ráp đúng liên kết /page/N/ cho các trang kế tiếp;
 *     - mọi cổng quyền chạy trước (ưu tiên 20, sau
 *       nntm_hanh_gia_chan_quyen()) nên khách chưa đăng nhập bị chuyển hướng
 *       y như thường, không lọt dữ liệu qua ngả AJAX.
 *
 * Thuộc tính khối lấy từ post_content, KHÔNG nhận từ trình duyệt gửi lên —
 * không có đường nào để người ngoài đổi post_type/taxonomy của truy vấn.
 *
 * @package NNTM
 */

defined( 'ABSPATH' ) || exit;

/**
 * Tên tham số truy vấn bật chế độ trả JSON.
 */
const NNTM_CARD_LIST_AJAX_ARG = 'nntm_cardlist_ajax';

/**
 * Tìm khối nntm/card-list ĐẦU TIÊN đang bật phân trang, đệ quy cả khối lồng.
 *
 * "Đầu tiên" là đủ: blocks/card-list/render.php dùng chung tham số ?paged=
 * cho mọi khối, nên theo thiết kế mỗi trang chỉ có MỘT khối bật showPaging
 * (xem chú thích trong file đó). Có nhiều hơn một thì bản POST thường cũng
 * đã lệch nhau từ trước, không phải chuyện file này sinh ra.
 *
 * @param array $blocks Danh sách khối đã parse_blocks().
 * @return array|null Khối tìm được, hoặc null.
 */
function nntm_card_list_tim_khoi_phan_trang( array $blocks ): ?array {
	foreach ( $blocks as $block ) {
		if ( ! is_array( $block ) ) {
			continue;
		}

		$attrs = isset( $block['attrs'] ) && is_array( $block['attrs'] ) ? $block['attrs'] : array();

		if (
			isset( $block['blockName'] ) &&
			'nntm/card-list' === $block['blockName'] &&
			! empty( $attrs['showPaging'] )
		) {
			return $block;
		}

		if ( ! empty( $block['innerBlocks'] ) && is_array( $block['innerBlocks'] ) ) {
			$tim_duoc = nntm_card_list_tim_khoi_phan_trang( $block['innerBlocks'] );
			if ( null !== $tim_duoc ) {
				return $tim_duoc;
			}
		}
	}

	return null;
}

/**
 * Bỏ tham số ?nntm_cardlist_ajax=1 khỏi các liên kết phân trang do
 * get_pagenum_link() sinh ra trong lượt dựng này.
 *
 * BẮT BUỘC: get_pagenum_link() giữ nguyên mọi tham số truy vấn đang có trên
 * URL hiện tại, nên nếu không lọc thì HTML trả về sẽ mang liên kết
 * "/page/3/?nntm_cardlist_ajax=1" — bấm tiếp (hoặc mở tab mới / bấm khi JS
 * lỗi) sẽ hiện ra JSON thô thay vì trang web.
 *
 * @param string $link Liên kết get_pagenum_link() vừa ráp.
 * @return string
 */
function nntm_card_list_bo_tham_so_ajax( string $link ): string {
	return (string) remove_query_arg( NNTM_CARD_LIST_AJAX_ARG, $link );
}

/**
 * Trả về JSON { html } chứa khối card-list của trang hiện tại, rồi dừng.
 *
 * Chạy ở ưu tiên 20 — SAU mọi cổng quyền cắm ở ưu tiên mặc định 10
 * (nntm_hanh_gia_chan_quyen(), nntm_congtu_yeu_cau_dang_nhap()...), nên
 * request bị chặn thì đã wp_safe_redirect() + exit trước khi tới đây.
 */
function nntm_card_list_tra_ve_khoi_json(): void {
	if ( is_admin() || wp_doing_ajax() ) {
		return;
	}

	if ( empty( $_GET[ NNTM_CARD_LIST_AJAX_ARG ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- chi doc, tra ve noi dung cong khai cua chinh trang dang xem.
		return;
	}

	if ( ! isset( $_SERVER['REQUEST_METHOD'] ) || 'GET' !== strtoupper( (string) $_SERVER['REQUEST_METHOD'] ) ) {
		return;
	}

	nocache_headers();

	$post = get_queried_object();

	if ( ! is_singular() || ! $post instanceof WP_Post ) {
		wp_send_json_error( array( 'message' => __( 'Trang này không có khối danh sách phân trang.', 'nntm' ) ), 400 );
	}

	// Bài đặt mật khẩu: không dựng nội dung khi chưa mở khoá.
	if ( post_password_required( $post ) ) {
		wp_send_json_error( array( 'message' => __( 'Nội dung được bảo vệ bằng mật khẩu.', 'nntm' ) ), 403 );
	}

	$khoi = nntm_card_list_tim_khoi_phan_trang( parse_blocks( $post->post_content ) );

	if ( null === $khoi ) {
		wp_send_json_error( array( 'message' => __( 'Trang này không có khối danh sách phân trang.', 'nntm' ) ), 404 );
	}

	add_filter( 'get_pagenum_link', 'nntm_card_list_bo_tham_so_ajax' );
	$html = render_block( $khoi );
	remove_filter( 'get_pagenum_link', 'nntm_card_list_bo_tham_so_ajax' );

	if ( '' === trim( (string) $html ) ) {
		wp_send_json_error( array( 'message' => __( 'Không dựng được danh sách lúc này.', 'nntm' ) ), 500 );
	}

	wp_send_json_success(
		array(
			'html'  => $html,
			'paged' => max( 1, (int) get_query_var( 'paged' ) ),
		)
	);
}
add_action( 'template_redirect', 'nntm_card_list_tra_ve_khoi_json', 20 );
