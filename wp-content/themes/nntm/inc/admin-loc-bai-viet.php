<?php
/**
 * Ô lọc theo phân mục / chủ đề trong bảng danh sách bài ở trang quản trị.
 *
 * WordPress chỉ tự dựng ô lọc cho taxonomy có sẵn (Chuyên mục của "Tin tức" —
 * xem WP_Posts_List_Table::categories_dropdown). Các taxonomy do nntm-core
 * đăng ký thì không được gì, nên bảng "Bài viết" có vài trăm dòng mà không có
 * cách nào thu hẹp lại. Tệp này bù đúng chỗ đó.
 *
 * Không cần bộ lọc parse_query: các taxonomy đều đăng ký public = true nên
 * query_var bật sẵn, WP_Query hiểu thẳng ?nntm_section=<slug> trên edit.php.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Những taxonomy đáng dựng ô lọc cho một loại nội dung.
 *
 * Bỏ taxonomy có sẵn của WordPress (category, post_tag): "Tin tức" đã được lõi
 * dựng sẵn ô Chuyên mục rồi, thêm nữa là hai ô trùng nhau nằm cạnh nhau.
 *
 * @param string $post_type Loại nội dung của màn hình đang mở.
 * @return WP_Taxonomy[]
 */
function nntm_loc_taxonomy_cua_loai( string $post_type ): array {
	if ( '' === $post_type ) {
		return array();
	}

	$ra = array();

	foreach ( (array) get_object_taxonomies( $post_type, 'objects' ) as $taxonomy ) {
		if ( ! $taxonomy instanceof WP_Taxonomy ) {
			continue;
		}

		if ( $taxonomy->_builtin || ! $taxonomy->show_ui ) {
			continue;
		}

		$ra[] = $taxonomy;
	}

	/**
	 * Thêm/bớt ô lọc mà không phải sửa tệp này.
	 *
	 * @param WP_Taxonomy[] $ra        Danh sách taxonomy sẽ dựng ô lọc.
	 * @param string        $post_type Loại nội dung đang xem.
	 */
	return (array) apply_filters( 'nntm_loc_taxonomy', $ra, $post_type );
}

/**
 * Giá trị đang chọn của một ô lọc, lấy từ URL.
 *
 * Trả về slug chứ không phải ID: wp_dropdown_categories bên dưới dùng
 * value_field = 'slug' để URL đọc được bằng mắt, và WP_Query cũng nhận slug.
 */
function nntm_loc_gia_tri_dang_chon( string $taxonomy ): string {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- chỉ là bộ lọc đọc, không đổi gì.
	if ( ! isset( $_GET[ $taxonomy ] ) ) {
		return '';
	}

	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	return sanitize_title( wp_unslash( $_GET[ $taxonomy ] ) );
}

/**
 * In các ô lọc lên đầu bảng danh sách.
 *
 * @param string $post_type Loại nội dung của bảng.
 * @param string $which     'top' hay 'bottom'. Lõi chỉ gọi ở 'top', vẫn chặn
 *                          cho chắc để sau này lõi đổi thì không ra hai bộ ô.
 */
function nntm_loc_in_o_chon( string $post_type, string $which = 'top' ): void {
	if ( 'top' !== $which ) {
		return;
	}

	foreach ( nntm_loc_taxonomy_cua_loai( $post_type ) as $taxonomy ) {
		$ten_thuong = mb_strtolower( $taxonomy->labels->singular_name, 'UTF-8' );

		printf(
			'<label class="screen-reader-text" for="%1$s">%2$s</label>',
			esc_attr( $taxonomy->name ),
			/* translators: %s: tên taxonomy, ví dụ "phân mục" */
			esc_html( sprintf( __( 'Lọc theo %s', 'nntm' ), $ten_thuong ) )
		);

		wp_dropdown_categories(
			array(
				'taxonomy'        => $taxonomy->name,
				'name'            => $taxonomy->name,
				'id'              => $taxonomy->name,
				'value_field'     => 'slug',
				'selected'        => nntm_loc_gia_tri_dang_chon( $taxonomy->name ),
				/* translators: %s: tên taxonomy số nhiều, ví dụ "phân mục" */
				'show_option_all' => sprintf( __( 'Mọi %s', 'nntm' ), mb_strtolower( $taxonomy->labels->name, 'UTF-8' ) ),
				/*
				 * hide_empty = false: danh sách giữ nguyên hình dạng dù phân mục
				 * chưa có bài nào. Ẩn đi thì ô lọc cứ đổi mỗi lần đăng/xoá bài,
				 * quản trị không đoán được còn mục nào.
				 */
				'hide_empty'      => false,
				'hierarchical'    => (bool) $taxonomy->hierarchical,
				'show_count'      => false,
				'orderby'         => 'name',
			)
		);
	}
}
add_action( 'restrict_manage_posts', 'nntm_loc_in_o_chon', 10, 2 );
