<?php

defined( 'ABSPATH' ) || exit;

define( 'NNTM_THEME_VERSION', '0.1.0' );
define( 'NNTM_THEME_DIR', get_template_directory() );
define( 'NNTM_THEME_URI', get_template_directory_uri() );

require NNTM_THEME_DIR . '/inc/setup.php';
require NNTM_THEME_DIR . '/inc/page-settings.php';
require NNTM_THEME_DIR . '/inc/enqueue.php';
require NNTM_THEME_DIR . '/inc/preloader.php';
require NNTM_THEME_DIR . '/inc/blocks.php';
require NNTM_THEME_DIR . '/inc/patterns.php';
require NNTM_THEME_DIR . '/inc/cleanup.php';
require NNTM_THEME_DIR . '/inc/language-switcher.php';
require NNTM_THEME_DIR . '/inc/auth.php';
require NNTM_THEME_DIR . '/inc/favorites.php';
require_once NNTM_THEME_DIR . '/inc/section-article.php';
require_once NNTM_THEME_DIR . '/inc/retreat.php';
require NNTM_THEME_DIR . '/inc/hanh-gia.php';
require NNTM_THEME_DIR . '/inc/hoa-khai.php';
require NNTM_THEME_DIR . '/inc/an-pham.php';
require NNTM_THEME_DIR . '/inc/doc-sach.php';
require NNTM_THEME_DIR . '/inc/cong-tu.php';
require NNTM_THEME_DIR . '/inc/kim-cuong-hanh-gia.php';
require NNTM_THEME_DIR . '/inc/card-list-ajax.php';
require NNTM_THEME_DIR . '/inc/video-lightbox.php';
require NNTM_THEME_DIR . '/inc/search.php';
