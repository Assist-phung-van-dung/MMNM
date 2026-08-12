<?php
/**
 * Đăng ký các Taxonomy của NNTM.
 * Xem bảng taxonomy tại docs/04-kien-truc.md mục 3.
 *
 * @package NNTM_Core
 */

namespace NNTM\Core;

// Chống truy cập trực tiếp file.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Taxonomies
 */
class Taxonomies {

	/**
	 * Instance duy nhất (singleton).
	 *
	 * @var Taxonomies|null
	 */
	private static ?Taxonomies $instance = null;

	/**
	 * 6 phân mục mặc định — slug không dấu => tên hiển thị.
	 * Dùng chung cho cả đăng ký lúc kích hoạt (Activator) lẫn tham chiếu nơi khác.
	 *
	 * @return array<string, string>
	 */
	public static function default_sections(): array {
		return array(
			'dieu-thuong'     => 'Diệu Thượng',
			'phap-toa'        => 'Pháp Tòa',
			'lien-dan'        => 'Liên Đàn',
			'hoa-khai'        => 'Hoa Khai',
			'vuon-xoai'       => 'Vườn Xoài',
			'nhap-phap-gioi'  => 'Nhập Pháp Giới',
		);
	}

	/**
	 * Lấy instance duy nhất.
	 */
	public static function instance(): Taxonomies {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Gắn hook đăng ký taxonomy.
	 */
	public function hooks(): void {
		add_action( 'init', array( $this, 'register_taxonomies' ) );
	}

	/**
	 * Đăng ký 3 taxonomy: nntm_section, nntm_topic, nntm_series.
	 */
	public function register_taxonomies(): void {
		register_taxonomy(
			'nntm_section',
			array( 'nntm_article' ),
			array(
				'labels'            => array(
					'name'              => __( 'Phân mục', 'nntm' ),
					'singular_name'     => __( 'Phân mục', 'nntm' ),
					'search_items'      => __( 'Tìm phân mục', 'nntm' ),
					'all_items'         => __( 'Tất cả phân mục', 'nntm' ),
					'parent_item'       => __( 'Phân mục cha', 'nntm' ),
					'parent_item_colon' => __( 'Phân mục cha:', 'nntm' ),
					'edit_item'         => __( 'Sửa phân mục', 'nntm' ),
					'update_item'       => __( 'Cập nhật phân mục', 'nntm' ),
					'add_new_item'      => __( 'Thêm phân mục mới', 'nntm' ),
					'new_item_name'     => __( 'Tên phân mục mới', 'nntm' ),
					'menu_name'         => __( 'Phân mục', 'nntm' ),
				),
				'hierarchical'      => true,
				'public'            => true,
				'show_in_rest'      => true,
				'show_admin_column' => true,
				'rewrite'           => array(
					'slug'       => 'phan-muc',
					'with_front' => false,
				),
			)
		);

		/*
		 * nntm_retreat và nntm_zen_track được gắn thêm từ 06/08/2026:
		 * thẻ Khoá Tu trong Figma có nhãn chuyên mục, mà hai loại nội dung này
		 * trước đó không có taxonomy nào nên nhãn không có nguồn dữ liệu.
		 * Chủ đề cũng là cách để ban quản trị tự tách "Khoá Tu" với "Lịch Tu"
		 * trên trang Liên Đàn mà không cần lập trình viên.
		 */
		register_taxonomy(
			'nntm_topic',
			array( 'nntm_article', 'nntm_publication', 'nntm_talk', 'nntm_retreat', 'nntm_zen_track' ),
			array(
				'labels'            => array(
					'name'              => __( 'Chủ đề', 'nntm' ),
					'singular_name'     => __( 'Chủ đề', 'nntm' ),
					'search_items'      => __( 'Tìm chủ đề', 'nntm' ),
					'all_items'         => __( 'Tất cả chủ đề', 'nntm' ),
					'edit_item'         => __( 'Sửa chủ đề', 'nntm' ),
					'update_item'       => __( 'Cập nhật chủ đề', 'nntm' ),
					'add_new_item'      => __( 'Thêm chủ đề mới', 'nntm' ),
					'new_item_name'     => __( 'Tên chủ đề mới', 'nntm' ),
					'separate_items_with_commas' => __( 'Phân cách các chủ đề bằng dấu phẩy', 'nntm' ),
					'menu_name'         => __( 'Chủ đề', 'nntm' ),
				),
				'hierarchical'      => false,
				'public'            => true,
				'show_in_rest'      => true,
				'show_admin_column' => true,
				'rewrite'           => array(
					'slug'       => 'chu-de',
					'with_front' => false,
				),
			)
		);

		register_taxonomy(
			'nntm_series',
			array( 'nntm_talk', 'nntm_video' ),
			array(
				'labels'            => array(
					'name'              => __( 'Bộ', 'nntm' ),
					'singular_name'     => __( 'Bộ', 'nntm' ),
					'search_items'      => __( 'Tìm bộ', 'nntm' ),
					'all_items'         => __( 'Tất cả các bộ', 'nntm' ),
					'edit_item'         => __( 'Sửa bộ', 'nntm' ),
					'update_item'       => __( 'Cập nhật bộ', 'nntm' ),
					'add_new_item'      => __( 'Thêm bộ mới', 'nntm' ),
					'new_item_name'     => __( 'Tên bộ mới', 'nntm' ),
					'separate_items_with_commas' => __( 'Phân cách các bộ bằng dấu phẩy', 'nntm' ),
					'menu_name'         => __( 'Bộ / Series', 'nntm' ),
				),
				'hierarchical'      => false,
				'public'            => true,
				'show_in_rest'      => true,
				'show_admin_column' => true,
				'rewrite'           => array(
					'slug'       => 'bo',
					'with_front' => false,
				),
			)
		);
	}

	/**
	 * Tạo sẵn 6 term phân mục — gọi lúc kích hoạt plugin (Activator).
	 * Kiểm tra term_exists để không tạo trùng khi kích hoạt lại.
	 */
	public static function create_default_terms(): void {
		foreach ( self::default_sections() as $slug => $name ) {
			if ( ! term_exists( $slug, 'nntm_section' ) ) {
				wp_insert_term(
					$name,
					'nntm_section',
					array( 'slug' => $slug )
				);
			}
		}
	}
}
