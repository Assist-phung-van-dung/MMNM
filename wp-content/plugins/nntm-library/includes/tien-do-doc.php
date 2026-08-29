<?php
/**
 * Tiến độ đọc — nhớ người đọc đang dừng ở trang nào.
 *
 * CHUYỂN TỪ theme/inc/doc-sach.php sang. Đây là lý do rõ nhất khiến plugin này
 * phải tồn tại: bảng wp_nntm_reading_progress do nntm-core tạo
 * (includes/class-schema.php) nhưng lại bị THEME ghi thẳng bằng $wpdb->insert.
 * Đổi theme là mất luôn chỗ đọc/ghi, dữ liệu nằm đó mà không ai dùng.
 *
 * @package NNTM_Library
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'nntm_doc_bang_tien_do' ) ) {
	/**
	 * Tên bảng tiến độ đọc. Bảng do nntm-core dựng, ở đây chỉ dùng.
	 */
	function nntm_doc_bang_tien_do(): string {
		global $wpdb;

		return $wpdb->prefix . 'nntm_reading_progress';
	}
}

if ( ! function_exists( 'nntm_doc_lay_vi_tri' ) ) {
	/**
	 * Trang đang đọc dở của người dùng hiện tại.
	 *
	 * @param int $object_id ID ấn phẩm.
	 */
	function nntm_doc_lay_vi_tri( int $object_id ): int {
		global $wpdb;

		if ( get_current_user_id() <= 0 ) {
			return 0;
		}

		$bang = nntm_doc_bang_tien_do();

		return (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT position FROM {$bang} WHERE user_id = %d AND object_id = %d AND object_type = %s LIMIT 1",
				get_current_user_id(),
				$object_id,
				'publication'
			)
		);
	}
}

/**
 * Ghi lại trang đang đọc.
 *
 * Kiểm quyền ở đây nữa dù trang đọc đã chặn: yêu cầu AJAX đi thẳng vào
 * admin-ajax.php, không qua template_redirect, nên nếu không kiểm thì người
 * không có quyền vẫn ghi được tiến độ cho một cuốn họ chưa mua.
 */
function nntm_doc_ajax_luu_tien_do(): void {
	check_ajax_referer( 'nntm_doc_tien_do', 'nonce' );

	if ( ! is_user_logged_in() ) {
		wp_send_json_error( array( 'message' => __( 'Chưa đăng nhập.', 'nntm' ) ), 403 );
	}

	$object_id = isset( $_POST['object_id'] ) ? absint( wp_unslash( $_POST['object_id'] ) ) : 0;
	$trang     = isset( $_POST['trang'] ) ? absint( wp_unslash( $_POST['trang'] ) ) : 0;

	if ( $object_id <= 0 || $trang <= 0 || 'nntm_publication' !== get_post_type( $object_id ) ) {
		wp_send_json_error( array( 'message' => __( 'Dữ liệu không hợp lệ.', 'nntm' ) ), 400 );
	}

	if ( ! nntm_an_pham_can_access( $object_id ) ) {
		wp_send_json_error( array( 'message' => __( 'Không có quyền.', 'nntm' ) ), 403 );
	}

	global $wpdb;

	$bang    = nntm_doc_bang_tien_do();
	$user_id = get_current_user_id();

	$da_co = (int) $wpdb->get_var(
		$wpdb->prepare(
			"SELECT id FROM {$bang} WHERE user_id = %d AND object_id = %d AND object_type = %s LIMIT 1",
			$user_id,
			$object_id,
			'publication'
		)
	);

	$data = array(
		'user_id'     => $user_id,
		'object_id'   => $object_id,
		'object_type' => 'publication',
		'position'    => (string) $trang,
		'updated_at'  => current_time( 'mysql' ),
	);

	if ( $da_co > 0 ) {
		$wpdb->update( $bang, $data, array( 'id' => $da_co ) );
	} else {
		$wpdb->insert( $bang, $data );
	}

	wp_send_json_success( array( 'trang' => $trang ) );
}
add_action( 'wp_ajax_nntm_doc_tien_do', 'nntm_doc_ajax_luu_tien_do' );
