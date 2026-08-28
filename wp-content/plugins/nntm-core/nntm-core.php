<?php
/**
 * Plugin Name:       NNTM Core
 * Plugin URI:        https://nangnhantichmac.vn
 * Description:       Nền tảng dữ liệu và nghiệp vụ cho website Phật pháp "Nẵng Nhân Tịch Mặc": Custom Post Type, Taxonomy, vai trò thành viên, bảng dữ liệu riêng.
 * Version:           0.1.0
 * Requires at least: 6.4
 * Requires PHP:      8.1
 * Author:            NNTM
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       nntm
 * Domain Path:       /languages
 *
 * @package NNTM_Core
 */

// Chống truy cập trực tiếp file.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Hằng số dùng chung trong toàn plugin.
define( 'NNTM_CORE_VERSION', '0.1.0' );
define( 'NNTM_CORE_DIR', plugin_dir_path( __FILE__ ) );
define( 'NNTM_CORE_URL', plugin_dir_url( __FILE__ ) );
define( 'NNTM_CORE_FILE', __FILE__ );
define( 'NNTM_CORE_SCHEMA_VERSION', '1.1.0' );

/**
 * Nạp các file lớp của plugin.
 * Chưa dùng autoload composer (yêu cầu "không chạy composer/npm") nên require thủ công theo thứ tự phụ thuộc.
 */
require_once NNTM_CORE_DIR . 'includes/class-post-types.php';
require_once NNTM_CORE_DIR . 'includes/class-taxonomies.php';
require_once NNTM_CORE_DIR . 'includes/class-term-meta.php';
require_once NNTM_CORE_DIR . 'includes/class-roles.php';
require_once NNTM_CORE_DIR . 'includes/functions.php';
require_once NNTM_CORE_DIR . 'includes/class-post-meta.php';
require_once NNTM_CORE_DIR . 'includes/class-nghi-quy-quiz.php';
require_once NNTM_CORE_DIR . 'includes/class-schema.php';
require_once NNTM_CORE_DIR . 'includes/class-chuoi-tri.php';
require_once NNTM_CORE_DIR . 'includes/class-activator.php';

/**
 * Nạp bản dịch (song ngữ — mục 7 kiến trúc).
 */
function nntm_core_load_textdomain() {
	load_plugin_textdomain( 'nntm', false, dirname( plugin_basename( NNTM_CORE_FILE ) ) . '/languages' );
}
add_action( 'init', 'nntm_core_load_textdomain', 1 );

/**
 * Khởi tạo các thành phần chính của plugin.
 */
function nntm_core_bootstrap() {
	\NNTM\Core\Post_Types::instance()->hooks();
	\NNTM\Core\Taxonomies::instance()->hooks();
	\NNTM\Core\Term_Meta::instance()->hooks();
	\NNTM\Core\Post_Meta::instance()->hooks();
	\NNTM\Core\Nghi_Quy_Quiz::instance()->hooks();
	\NNTM\Core\Roles::instance()->hooks();
}
add_action( 'plugins_loaded', 'nntm_core_bootstrap' );

// Kích hoạt / hủy kích hoạt.
register_activation_hook( NNTM_CORE_FILE, array( '\NNTM\Core\Activator', 'activate' ) );
register_deactivation_hook( NNTM_CORE_FILE, array( '\NNTM\Core\Activator', 'deactivate' ) );
