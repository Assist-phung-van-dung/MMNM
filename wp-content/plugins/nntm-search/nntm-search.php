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

/*
 * Doc file .env cua CHINH plugin nay (khong phai .env o goc site — cai do
 * chi danh cho tools/figma-sync.mjs). Dat trong wp-content nen bind mount
 * tu Windows phu ca Docker lan khong-Docker: sua .env la thay ngay lan tai
 * trang ke tiep, khong can restart gi. Du an khong co composer/vendor nen
 * tu doc KEY=VALUE, khong keo thu vien ngoai.
 */
foreach ( ( is_readable( NNTM_SEARCH_DIR . '/.env' ) ? file( NNTM_SEARCH_DIR . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES ) : array() ) as $nntm_search_env_line ) {
	$nntm_search_env_line = trim( $nntm_search_env_line );

	if ( '' === $nntm_search_env_line || '#' === $nntm_search_env_line[0] || false === strpos( $nntm_search_env_line, '=' ) ) {
		continue;
	}

	list( $nntm_search_env_key, $nntm_search_env_value ) = explode( '=', $nntm_search_env_line, 2 );
	$nntm_search_env_key                                 = trim( $nntm_search_env_key );

	// Khong ghi de neu bien da co san tu moi truong that (vd dat qua shell).
	if ( '' !== $nntm_search_env_key && false === getenv( $nntm_search_env_key ) ) {
		putenv( $nntm_search_env_key . '=' . trim( $nntm_search_env_value ) );
	}
}
unset( $nntm_search_env_line, $nntm_search_env_key, $nntm_search_env_value );

/**
 * Doc mot co true/false tu .env cua plugin, mac dinh BAT neu khong khai
 * hoac gia tri khong hop le.
 *
 * @param string $name Ten bien.
 * @return bool
 */
function nntm_search_env_bool( string $name ): bool {
	$value = getenv( $name );

	if ( false === $value || '' === trim( $value ) ) {
		return true;
	}

	return ! in_array( strtolower( trim( $value ) ), array( 'false', '0', 'off', 'no' ), true );
}

// Bat/tat tim bang anh va tim trong PDF — chinh trong .env cung thu muc.
define( 'NNTM_SEARCH_IMAGE_ENABLED', nntm_search_env_bool( 'NNTM_SEARCH_IMAGE_ENABLED' ) );
define( 'NNTM_SEARCH_PDF_ENABLED', nntm_search_env_bool( 'NNTM_SEARCH_PDF_ENABLED' ) );

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
