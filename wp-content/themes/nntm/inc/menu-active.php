<?php

defined( 'ABSPATH' ) || exit;

const NNTM_MENU_VI_TRI = 'primary';

/**
 * Bản đồ dự phòng cho những đích đến không nằm trong cây nntm_section.
 *
 * Khoá nhận các dạng:
 *   page:<slug>                — một trang cụ thể
 *   term:<taxonomy>:<slug>     — một term cụ thể (dùng cho cả archive lẫn bài nằm trong term đó)
 *   tax:<taxonomy>             — mọi archive của một taxonomy
 *   post_type:<name>           — mọi bài của một post type
 *   archive:<post_type>        — trang archive của một post type
 *
 * Giá trị là slug của trang đang làm mục gốc trong menu chính.
 *
 * @return array<string,string>
 */
function nntm_menu_ban_do(): array {
	return (array) apply_filters(
		'nntm_menu_ban_do',
		array(
			// Nghi Quỹ là term của nntm_topic (không phân cấp) nên không nằm trong cây
			// nntm_section — khối Nghi Quỹ nằm trong Nhập Pháp Giới.
			'page:nghi-quy'              => 'nhap-phap-gioi',
			'term:nntm_topic:nghi-quy'   => 'nhap-phap-gioi',

			// Chuyên mục của bài viết thường.
			'term:category:tin-tuc'      => 'hoa-khai',
			'term:category:hoang-phap'   => 'hoa-khai',

			'post_type:nntm_publication' => 'hoa-khai',
			'archive:nntm_publication'   => 'hoa-khai',

			// Khoá Tu / Lịch Tu — cả trang chủ đề lẫn trang danh sách khoá tu.
			'term:nntm_topic:khoa-tu'    => 'lien-dan',
			'term:nntm_topic:lich-tu'    => 'lien-dan',
			'archive:nntm_retreat'       => 'lien-dan',

			'post_type:nntm_video'       => 'nhap-phap-gioi',
			'archive:nntm_video'         => 'nhap-phap-gioi',
			'tax:nntm_series'            => 'nhap-phap-gioi',
		)
	);
}

/**
 * Leo từ một term lên tới gốc của cây và trả về slug gốc.
 */
function nntm_menu_goc_cua_term( WP_Term $term ): string {
	if ( ! is_taxonomy_hierarchical( $term->taxonomy ) ) {
		return $term->slug;
	}

	$hien_tai = $term;
	$chan     = 0;

	while ( $hien_tai->parent > 0 && $chan < 10 ) {
		$cha = get_term( $hien_tai->parent, $term->taxonomy );

		if ( ! $cha instanceof WP_Term ) {
			break;
		}

		$hien_tai = $cha;
		++$chan;
	}

	return $hien_tai->slug;
}

/**
 * Xếp các ứng viên theo thứ tự ưu tiên cho trang đang xem.
 *
 * Ứng viên không có dấu ":" là slug mục gốc suy trực tiếp từ cây nntm_section.
 *
 * @return string[]
 */
function nntm_menu_ung_vien(): array {
	$ung_vien = array();

	if ( is_page() ) {
		$trang = get_queried_object();

		if ( $trang instanceof WP_Post ) {
			$ung_vien[] = 'page:' . $trang->post_name;

			$term = get_term_by( 'slug', $trang->post_name, 'nntm_section' );

			if ( $term instanceof WP_Term ) {
				$ung_vien[] = nntm_menu_goc_cua_term( $term );
			}
		}

		return $ung_vien;
	}

	if ( is_singular() ) {
		$bai = get_queried_object();

		if ( ! $bai instanceof WP_Post ) {
			return $ung_vien;
		}

		$muc = get_the_terms( $bai, 'nntm_section' );

		if ( ! empty( $muc ) && ! is_wp_error( $muc ) ) {
			$ung_vien[] = nntm_menu_goc_cua_term( $muc[0] );
		}

		foreach ( get_object_taxonomies( $bai->post_type ) as $taxonomy ) {
			$terms = get_the_terms( $bai, $taxonomy );

			if ( empty( $terms ) || is_wp_error( $terms ) ) {
				continue;
			}

			foreach ( $terms as $term ) {
				$ung_vien[] = 'term:' . $taxonomy . ':' . $term->slug;
			}
		}

		$ung_vien[] = 'post_type:' . $bai->post_type;

		return $ung_vien;
	}

	if ( is_tax() || is_category() || is_tag() ) {
		$term = get_queried_object();

		if ( $term instanceof WP_Term ) {
			$ung_vien[] = 'term:' . $term->taxonomy . ':' . $term->slug;
			$ung_vien[] = nntm_menu_goc_cua_term( $term );
			$ung_vien[] = 'tax:' . $term->taxonomy;
		}

		return $ung_vien;
	}

	if ( is_post_type_archive() ) {
		$loai = get_query_var( 'post_type' );
		$loai = is_array( $loai ) ? reset( $loai ) : (string) $loai;

		if ( '' !== $loai ) {
			$ung_vien[] = 'archive:' . $loai;
			$ung_vien[] = 'post_type:' . $loai;
		}
	}

	return $ung_vien;
}

/**
 * Slug trang đang làm mục gốc trong menu cho trang đang xem, '' nếu không suy được.
 */
function nntm_menu_muc_goc(): string {
	$ban_do = nntm_menu_ban_do();

	foreach ( nntm_menu_ung_vien() as $ung_vien ) {
		if ( '' === $ung_vien ) {
			continue;
		}

		if ( isset( $ban_do[ $ung_vien ] ) ) {
			return (string) $ban_do[ $ung_vien ];
		}

		// Không có tiền tố nghĩa là đã suy thẳng ra slug mục gốc từ cây nntm_section.
		if ( false === strpos( $ung_vien, ':' ) ) {
			return $ung_vien;
		}
	}

	return '';
}

/**
 * Sáng mục cha trong menu chính khi trang đang xem không phải một menu item.
 *
 * Dùng lại class current-menu-ancestor của WordPress nên không cần thêm CSS.
 *
 * @param array  $items Menu items đã gắn class ngữ cảnh.
 * @param object $args  Tham số của wp_nav_menu().
 * @return array
 */
function nntm_menu_danh_dau_muc_cha( $items, $args ) {
	if ( ! is_array( $items ) || empty( $items ) ) {
		return $items;
	}

	if ( NNTM_MENU_VI_TRI !== ( isset( $args->theme_location ) ? (string) $args->theme_location : '' ) ) {
		return $items;
	}

	$da_sang = array( 'current-menu-item', 'current_page_item', 'current-menu-ancestor', 'current_page_ancestor' );

	foreach ( $items as $item ) {
		if ( ! empty( $item->classes ) && is_array( $item->classes ) && array_intersect( $da_sang, $item->classes ) ) {
			return $items;
		}
	}

	$goc = nntm_menu_muc_goc();

	if ( '' === $goc ) {
		return $items;
	}

	foreach ( $items as $item ) {
		if ( 'post_type' !== $item->type || 'page' !== $item->object ) {
			continue;
		}

		if ( get_post_field( 'post_name', (int) $item->object_id ) !== $goc ) {
			continue;
		}

		$item->classes   = is_array( $item->classes ) ? $item->classes : array();
		$item->classes[] = 'current-menu-ancestor';

		break;
	}

	return $items;
}
add_filter( 'wp_nav_menu_objects', 'nntm_menu_danh_dau_muc_cha', 20, 2 );
