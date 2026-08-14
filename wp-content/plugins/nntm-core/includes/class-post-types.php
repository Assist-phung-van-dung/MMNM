<?php
/**
 * Đăng ký các Custom Post Type của NNTM.
 * Xem bảng data model tại docs/04-kien-truc.md mục 3.
 *
 * @package NNTM_Core
 */

namespace NNTM\Core;

// Chống truy cập trực tiếp file.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Post_Types
 */
class Post_Types {

	/**
	 * Instance duy nhất (singleton).
	 *
	 * @var Post_Types|null
	 */
	private static ?Post_Types $instance = null;

	/**
	 * Lấy instance duy nhất.
	 */
	public static function instance(): Post_Types {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Gắn hook đăng ký CPT.
	 */
	public function hooks(): void {
		add_action( 'init', array( $this, 'register_post_types' ) );
	}

	/**
	 * Đăng ký toàn bộ 7 CPT theo mục 3 kiến trúc.
	 * Tất cả show_in_rest = true để dùng được trình soạn thảo block (yêu cầu bắt buộc).
	 */
	public function register_post_types(): void {
		register_post_type( 'nntm_article', $this->args_article() );
		register_post_type( 'nntm_publication', $this->args_publication() );
		register_post_type( 'nntm_talk', $this->args_talk() );
		register_post_type( 'nntm_retreat', $this->args_retreat() );
		register_post_type( 'nntm_abode', $this->args_abode() );
		register_post_type( 'nntm_video', $this->args_video() );
		register_post_type( 'nntm_zen_track', $this->args_zen_track() );
		register_post_type( 'nntm_program', $this->args_program() );
	}

	/**
	 * Sinh mảng nhãn (labels) tiếng Việt dùng chung cho các CPT.
	 * Tiếng Việt không biến đổi số ít/nhiều nên $plural mặc định lấy theo $singular.
	 *
	 * @param string      $singular Tên số ít, ví dụ "Bài viết".
	 * @param string|null $plural   Tên số nhiều, để trống thì lấy theo $singular.
	 */
	private function labels( string $singular, ?string $plural = null ): array {
		$plural = $plural ?? $singular;

		return array(
			'name'                  => $plural,
			'singular_name'         => $singular,
			'add_new'               => sprintf(
				/* translators: %s: tên số ít của post type */
				__( 'Thêm %s', 'nntm' ),
				mb_strtolower( $singular, 'UTF-8' )
			),
			'add_new_item'          => sprintf( __( 'Thêm %s mới', 'nntm' ), mb_strtolower( $singular, 'UTF-8' ) ),
			'edit_item'             => sprintf( __( 'Sửa %s', 'nntm' ), mb_strtolower( $singular, 'UTF-8' ) ),
			'new_item'              => sprintf( __( '%s mới', 'nntm' ), $singular ),
			'view_item'             => sprintf( __( 'Xem %s', 'nntm' ), mb_strtolower( $singular, 'UTF-8' ) ),
			'view_items'            => sprintf( __( 'Xem %s', 'nntm' ), mb_strtolower( $plural, 'UTF-8' ) ),
			'search_items'          => sprintf( __( 'Tìm %s', 'nntm' ), mb_strtolower( $plural, 'UTF-8' ) ),
			'not_found'             => sprintf( __( 'Không tìm thấy %s nào', 'nntm' ), mb_strtolower( $plural, 'UTF-8' ) ),
			'not_found_in_trash'    => sprintf( __( 'Không có %s nào trong thùng rác', 'nntm' ), mb_strtolower( $plural, 'UTF-8' ) ),
			'all_items'             => sprintf( __( 'Tất cả %s', 'nntm' ), mb_strtolower( $plural, 'UTF-8' ) ),
			'archives'              => sprintf( __( 'Kho %s', 'nntm' ), mb_strtolower( $plural, 'UTF-8' ) ),
			'attributes'            => sprintf( __( 'Thuộc tính %s', 'nntm' ), mb_strtolower( $singular, 'UTF-8' ) ),
			'insert_into_item'      => sprintf( __( 'Chèn vào %s', 'nntm' ), mb_strtolower( $singular, 'UTF-8' ) ),
			'uploaded_to_this_item' => sprintf( __( 'Đã tải lên cho %s này', 'nntm' ), mb_strtolower( $singular, 'UTF-8' ) ),
			'featured_image'        => __( 'Ảnh đại diện', 'nntm' ),
			'set_featured_image'    => __( 'Đặt ảnh đại diện', 'nntm' ),
			'remove_featured_image' => __( 'Gỡ ảnh đại diện', 'nntm' ),
			'use_featured_image'    => __( 'Dùng làm ảnh đại diện', 'nntm' ),
			'menu_name'             => $plural,
			'name_admin_bar'        => $singular,
			'item_published'        => sprintf( __( 'Đã đăng %s', 'nntm' ), mb_strtolower( $singular, 'UTF-8' ) ),
			'item_updated'          => sprintf( __( 'Đã cập nhật %s', 'nntm' ), mb_strtolower( $singular, 'UTF-8' ) ),
		);
	}

	/**
	 * nntm_article — Bài viết của 6 phân mục.
	 * Đầy đủ title, editor, thumbnail, excerpt, revisions, author theo yêu cầu.
	 */
	private function args_article(): array {
		return array(
			'labels'             => $this->labels( __( 'Bài viết', 'nntm' ) ),
			'public'             => true,
			'show_in_rest'       => true,
			'menu_icon'          => 'dashicons-edit-page',
			'menu_position'      => 30,
			'supports'           => array( 'title', 'editor', 'thumbnail', 'excerpt', 'revisions', 'author' ),
			'has_archive'        => true,
			'show_in_menu'       => true,
			'show_in_admin_bar'  => true,
			'show_in_nav_menus'  => true,
			'hierarchical'       => false,
			'rewrite'            => array(
				'slug'       => 'bai-viet',
				'with_front' => false,
			),
			'capability_type'    => 'post',
			'map_meta_cap'       => true,
		);
	}

	/**
	 * nntm_publication — Ấn phẩm PDF (BOOKS).
	 */
	private function args_publication(): array {
		return array(
			'labels'             => $this->labels( __( 'Ấn phẩm', 'nntm' ) ),
			'public'             => true,
			'show_in_rest'       => true,
			'menu_icon'          => 'dashicons-media-document',
			'menu_position'      => 31,
			'supports'           => array( 'title', 'editor', 'thumbnail', 'excerpt', 'revisions', 'author' ),
			'has_archive'        => true,
			'show_in_menu'       => true,
			'show_in_admin_bar'  => true,
			'show_in_nav_menus'  => true,
			'hierarchical'       => false,
			'rewrite'            => array(
				'slug'       => 'an-pham',
				'with_front' => false,
			),
			'capability_type'    => 'post',
			'map_meta_cap'       => true,
		);
	}

	/**
	 * nntm_talk — Pháp Thoại (audio ~1h).
	 */
	private function args_talk(): array {
		return array(
			'labels'             => $this->labels( __( 'Pháp Thoại', 'nntm' ) ),
			'public'             => true,
			'show_in_rest'       => true,
			'menu_icon'          => 'dashicons-microphone',
			'menu_position'      => 32,
			'supports'           => array( 'title', 'editor', 'thumbnail', 'excerpt', 'revisions', 'author' ),
			'has_archive'        => true,
			'show_in_menu'       => true,
			'show_in_admin_bar'  => true,
			'show_in_nav_menus'  => true,
			'hierarchical'       => false,
			'rewrite'            => array(
				'slug'       => 'phap-thoai',
				'with_front' => false,
			),
			'capability_type'    => 'post',
			'map_meta_cap'       => true,
		);
	}

	/**
	 * nntm_retreat — Khóa Tu.
	 */
	private function args_retreat(): array {
		return array(
			'labels'             => $this->labels( __( 'Khóa Tu', 'nntm' ) ),
			'public'             => true,
			'show_in_rest'       => true,
			'menu_icon'          => 'dashicons-calendar-alt',
			'menu_position'      => 33,
			'supports'           => array( 'title', 'editor', 'thumbnail', 'excerpt', 'revisions', 'author' ),
			'has_archive'        => true,
			'show_in_menu'       => true,
			'show_in_admin_bar'  => true,
			'show_in_nav_menus'  => true,
			'hierarchical'       => false,
			'rewrite'            => array(
				'slug'       => 'khoa-tu',
				'with_front' => false,
			),
			'capability_type'    => 'post',
			'map_meta_cap'       => true,
		);
	}

	/**
	 * nntm_abode — Trú Xứ.
	 */
	private function args_abode(): array {
		return array(
			'labels'             => $this->labels( __( 'Trú Xứ', 'nntm' ) ),
			'public'             => true,
			'show_in_rest'       => true,
			'menu_icon'          => 'dashicons-admin-home',
			'menu_position'      => 34,
			'supports'           => array( 'title', 'editor', 'thumbnail', 'excerpt', 'revisions', 'author' ),
			'has_archive'        => true,
			'show_in_menu'       => true,
			'show_in_admin_bar'  => true,
			'show_in_nav_menus'  => true,
			'hierarchical'       => false,
			'rewrite'            => array(
				'slug'       => 'tru-xu',
				'with_front' => false,
			),
			'capability_type'    => 'post',
			'map_meta_cap'       => true,
		);
	}

	/**
	 * nntm_video — Video / phim Phật pháp.
	 */
	private function args_video(): array {
		return array(
			'labels'             => $this->labels( __( 'Video', 'nntm' ) ),
			'public'             => true,
			'show_in_rest'       => true,
			'menu_icon'          => 'dashicons-video-alt3',
			'menu_position'      => 35,
			'supports'           => array( 'title', 'editor', 'thumbnail', 'excerpt', 'revisions', 'author' ),
			'has_archive'        => true,
			'show_in_menu'       => true,
			'show_in_admin_bar'  => true,
			'show_in_nav_menus'  => true,
			'hierarchical'       => false,
			'rewrite'            => array(
				'slug'       => 'video',
				'with_front' => false,
			),
			'capability_type'    => 'post',
			'map_meta_cap'       => true,
		);
	}

	/**
	 * nntm_zen_track — Nhạc thiền cho Thiền Đường.
	 */
	private function args_zen_track(): array {
		return array(
			'labels'             => $this->labels( __( 'Nhạc thiền', 'nntm' ) ),
			'public'             => true,
			'show_in_rest'       => true,
			'menu_icon'          => 'dashicons-format-audio',
			'menu_position'      => 36,
			'supports'           => array( 'title', 'editor', 'thumbnail', 'excerpt', 'revisions', 'author' ),
			'has_archive'        => true,
			'show_in_menu'       => true,
			'show_in_admin_bar'  => true,
			'show_in_nav_menus'  => true,
			'hierarchical'       => false,
			'rewrite'            => array(
				'slug'       => 'nhac-thien',
				'with_front' => false,
			),
			'capability_type'    => 'post',
			'map_meta_cap'       => true,
		);
	}

	/**
	 * nntm_program — Chương trình trì tụng (Cộng Tu).
	 * Mỗi chương trình là một đợt cam kết/khai báo có tên riêng, ví dụ
	 * "Lễ Đàn Khổng Tước — Trì tụng Tam Bộ Chú Ngôn". Sẽ có nhiều chương
	 * trình theo thời gian nên không giới hạn has_archive công khai riêng —
	 * giao diện Cộng Tu tự chọn chương trình đang mở để hiển thị.
	 */
	private function args_program(): array {
		return array(
			'labels'             => $this->labels( __( 'Chương trình trì tụng', 'nntm' ), __( 'Chương trình trì tụng', 'nntm' ) ),
			'public'             => true,
			'show_in_rest'       => true,
			'menu_icon'          => 'dashicons-chart-line',
			'menu_position'      => 37,
			'supports'           => array( 'title', 'editor', 'thumbnail', 'excerpt', 'revisions' ),
			'has_archive'        => false,
			'show_in_menu'       => true,
			'show_in_admin_bar'  => true,
			'show_in_nav_menus'  => true,
			'hierarchical'       => false,
			'rewrite'            => array(
				'slug'       => 'chuong-trinh',
				'with_front' => false,
			),
			'capability_type'    => 'post',
			'map_meta_cap'       => true,
		);
	}
}
