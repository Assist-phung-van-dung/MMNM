<?php
/**
 * Khởi động theme Nẵng Nhân Tịch Mặc.
 *
 * File này chỉ định nghĩa hằng và nạp các file trong inc/.
 * Logic thật nằm trong từng file inc/*.php, không viết ở đây.
 */

defined( 'ABSPATH' ) || exit;

define( 'NNTM_THEME_VERSION', '0.1.0' );
define( 'NNTM_THEME_DIR', get_template_directory() );
define( 'NNTM_THEME_URI', get_template_directory_uri() );

require NNTM_THEME_DIR . '/inc/setup.php';

// Tương thích patch "Ẩn tiêu đề trang" nếu đã được cài ở theme hiện tại.
if ( file_exists( NNTM_THEME_DIR . '/inc/page-settings.php' ) ) {
	require NNTM_THEME_DIR . '/inc/page-settings.php';
}
require NNTM_THEME_DIR . '/inc/enqueue.php';
require NNTM_THEME_DIR . '/inc/blocks.php';
require NNTM_THEME_DIR . '/inc/patterns.php';
require NNTM_THEME_DIR . '/inc/cleanup.php';
require NNTM_THEME_DIR . '/inc/language-switcher.php';
require NNTM_THEME_DIR . '/inc/auth.php';
require NNTM_THEME_DIR . '/inc/favorites.php';
require NNTM_THEME_DIR . '/inc/section-article.php';
require NNTM_THEME_DIR . '/inc/hanh-gia.php';
require NNTM_THEME_DIR . '/inc/an-pham.php';
require NNTM_THEME_DIR . '/inc/cong-tu.php';
