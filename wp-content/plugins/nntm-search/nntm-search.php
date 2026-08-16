<?php
/**
 * Plugin Name:       NNTM Search
 * Plugin URI:        https://nangnhantichmac.vn
 * Description:       Search for the "Nẵng Nhân Tịch Mặc" site: accent-insensitive text, instant suggestions, text inside PDFs, and search by image.
 * Version:           0.2.0
 * Requires at least: 6.4
 * Requires PHP:      8.1
 * Author:            NNTM
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       nntm
 * Domain Path:       /languages
 *
 * Where this sits in the architecture: docs/04-kien-truc.md section 1 —
 * "nntm-search/ ← accent-insensitive, autocomplete; images + OCR later".
 * Data and business logic live in plugins, presentation lives in the theme, so
 * switching themes must never lose search.
 *
 * Identifiers are English; user-facing strings stay Vietnamese and go through
 * the `nntm` text domain.
 *
 * @package NNTM_Search
 */

defined( 'ABSPATH' ) || exit;

define( 'NNTM_SEARCH_VERSION', '0.2.0' );
define( 'NNTM_SEARCH_DIR', __DIR__ );
define( 'NNTM_SEARCH_URI', plugin_dir_url( __FILE__ ) );
define( 'NNTM_SEARCH_NS', 'nntm-search/v1' );

require_once NNTM_SEARCH_DIR . '/includes/text.php';
require_once NNTM_SEARCH_DIR . '/includes/acl.php';
require_once NNTM_SEARCH_DIR . '/includes/schema.php';
require_once NNTM_SEARCH_DIR . '/includes/rate-limit.php';
require_once NNTM_SEARCH_DIR . '/includes/embed.php';
require_once NNTM_SEARCH_DIR . '/includes/pdf.php';
require_once NNTM_SEARCH_DIR . '/includes/download.php';
require_once NNTM_SEARCH_DIR . '/includes/engine.php';
require_once NNTM_SEARCH_DIR . '/includes/rest.php';
require_once NNTM_SEARCH_DIR . '/includes/image.php';
require_once NNTM_SEARCH_DIR . '/includes/assets.php';
