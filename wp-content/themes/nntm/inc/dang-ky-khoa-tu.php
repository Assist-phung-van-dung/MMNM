<?php

defined( 'ABSPATH' ) || exit;

/**
 * Tên bảng lưu đăng ký khóa tu.
 */
function nntm_dkkt_bang(): string {
	global $wpdb;

	return $wpdb->prefix . 'nntm_retreat_signup';
}

/**
 * Các trạng thái của một đăng ký.
 *
 * @return array<string,string>
 */
function nntm_dkkt_trang_thai(): array {
	return array(
		'pending'   => __( 'Chờ duyệt', 'nntm' ),
		'approved'  => __( 'Đã duyệt', 'nntm' ),
		'cancelled' => __( 'Đã huỷ', 'nntm' ),
	);
}

function nntm_dkkt_ten_trang_thai( string $trang_thai ): string {
	$ds = nntm_dkkt_trang_thai();

	return isset( $ds[ $trang_thai ] ) ? $ds[ $trang_thai ] : $trang_thai;
}

/**
 * Đăng ký của người đang xem cho một khóa tu, null nếu chưa đăng ký.
 *
 * Chỉ tra được cho người đã đăng nhập — khách vãng lai thì trình duyệt tự nhớ.
 *
 * @return array<string,mixed>|null
 */
function nntm_dkkt_cua_toi( int $retreat_id ): ?array {
	if ( $retreat_id < 1 || ! is_user_logged_in() ) {
		return null;
	}

	if ( ! function_exists( 'nntm_retreat_signup_table_exists' ) || ! nntm_retreat_signup_table_exists() ) {
		return null;
	}

	global $wpdb;

	$bang = nntm_dkkt_bang();

	$dong = $wpdb->get_row( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->prepare(
			"SELECT id, status, created_at FROM {$bang} WHERE retreat_id = %d AND user_id = %d AND status <> 'cancelled' ORDER BY id DESC LIMIT 1", // phpcs:ignore WordPress.DB.PreparedSQL
			$retreat_id,
			get_current_user_id()
		),
		ARRAY_A
	);

	return is_array( $dong ) ? $dong : null;
}

/**
 * Câu thông báo hiện ra khi người đã đăng ký bấm lại nút.
 */
function nntm_dkkt_loi_nhan( string $trang_thai ): string {
	switch ( $trang_thai ) {
		case 'approved':
			return __( 'Đăng ký của bạn đã được duyệt. Hẹn gặp bạn tại khóa tu — ban quản trị sẽ nhắn thêm thông tin trước ngày khai khóa.', 'nntm' );

		case 'cancelled':
			return __( 'Đăng ký trước đó của bạn đã được huỷ. Bạn có thể đăng ký lại.', 'nntm' );

		case 'pending':
		default:
			return __( 'Bạn đã đăng ký khóa tu này rồi. Đăng ký đang chờ ban quản trị duyệt, chúng tôi sẽ liên hệ với bạn để xác nhận.', 'nntm' );
	}
}

/**
 * Nhãn nút khi đã đăng ký.
 */
function nntm_dkkt_nhan_nut( string $trang_thai ): string {
	return 'approved' === $trang_thai
		? __( 'Đã đăng ký', 'nntm' )
		: __( 'Đang chờ duyệt', 'nntm' );
}

/**
 * Chỉ bài thuộc chủ đề "khoa-tu" mới cho đăng ký. Lịch tu là bảng lịch, không
 * phải khóa để ghi danh, nên không hiện nút.
 */
function nntm_dkkt_duoc_dang_ky( int $post_id ): bool {
	$post = get_post( $post_id );

	if ( ! $post instanceof WP_Post || 'nntm_retreat' !== $post->post_type ) {
		return (bool) apply_filters( 'nntm_dkkt_duoc_dang_ky', false, $post_id );
	}

	$duoc  = false;
	$terms = get_the_terms( $post, 'nntm_topic' );

	if ( is_array( $terms ) ) {
		foreach ( $terms as $term ) {
			if ( $term instanceof WP_Term && 'khoa-tu' === $term->slug ) {
				$duoc = true;
				break;
			}
		}
	}

	return (bool) apply_filters( 'nntm_dkkt_duoc_dang_ky', $duoc, $post_id );
}

require_once __DIR__ . '/dang-ky-khoa-tu-admin.php';
