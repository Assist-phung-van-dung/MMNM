<?php
/**
 * Xử lý kích hoạt / hủy kích hoạt plugin.
 *
 * @package NNTM_Core
 */

namespace NNTM\Core;

// Chống truy cập trực tiếp file.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Activator
 */
class Activator {

	/**
	 * Chạy lúc kích hoạt plugin: tạo bảng, tạo vai trò, đăng ký CPT/taxonomy và 6 term mặc định.
	 *
	 * Lưu ý: lúc register_activation_hook chạy thì hook 'init' của request này đã qua rồi
	 * (WordPress đã init xong trước khi cho phép activate), nên nntm_core_bootstrap() chưa kịp
	 * gọi Post_Types/Taxonomies::hooks(). Phải gọi trực tiếp register_post_types()/register_taxonomies()
	 * ở đây thì wp_insert_term() (trong create_default_terms) mới thấy taxonomy đã tồn tại.
	 */
	public static function activate(): void {
		Schema::create_tables();
		Roles::create_roles();

		Post_Types::instance()->register_post_types();
		Taxonomies::instance()->register_taxonomies();
		Taxonomies::create_default_terms();

		flush_rewrite_rules();
	}

	/**
	 * Chạy lúc hủy kích hoạt plugin.
	 * CHỈ flush rewrite rules — tuyệt đối không xóa bảng, vai trò hay dữ liệu.
	 * Việc xóa dữ liệu (nếu quản trị viên thật sự muốn) thuộc về uninstall.php, có điều kiện riêng.
	 */
	public static function deactivate(): void {
		flush_rewrite_rules();
	}
}
