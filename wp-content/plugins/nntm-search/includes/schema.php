<?php
/**
 * Custom tables.
 *
 * Own tables rather than wp_postmeta, per docs/04-kien-truc.md section 3:
 * anything written in high volume slows the whole site down when stuffed into
 * postmeta. A 300-page PDF is 300 rows; a 2,000-image library is 2,000 vectors.
 *
 * @package NNTM_Search
 */

defined( 'ABSPATH' ) || exit;

register_activation_hook( NNTM_SEARCH_DIR . '/nntm-search.php', 'nntm_search_create_tables' );

/**
 * Create the plugin tables. Safe to run repeatedly (dbDelta).
 */
function nntm_search_create_tables(): void {
	global $wpdb;

	require_once ABSPATH . 'wp-admin/includes/upgrade.php';

	$collate = $wpdb->get_charset_collate();

	/*
	 * Vectors are stored as packed binary (pack 'g*' — little-endian float32),
	 * not JSON: 512 dims is 2KB instead of ~6KB as a JSON string, and unpack()
	 * is far faster than json_decode() when scanning the whole table.
	 *
	 * Vectors are normalised to unit length at index time, so comparison is a
	 * plain dot product — no division and no square roots per candidate.
	 */
	$vectors = "CREATE TABLE {$wpdb->prefix}nntm_image_vectors (
		attachment_id BIGINT UNSIGNED NOT NULL,
		post_id       BIGINT UNSIGNED NOT NULL DEFAULT 0,
		acl           VARCHAR(16) NOT NULL DEFAULT 'public',
		lang          VARCHAR(10) NOT NULL DEFAULT '',
		model         VARCHAR(64) NOT NULL,
		dim           SMALLINT UNSIGNED NOT NULL,
		vector        MEDIUMBLOB NOT NULL,
		updated_at    DATETIME NOT NULL,
		PRIMARY KEY  (attachment_id),
		KEY idx_post (post_id),
		KEY idx_model (model),
		KEY idx_acl (acl)
	) $collate;";

	/*
	 * One row per PDF page. That is the entire reason we can answer "which page
	 * is it on" and deep-link into the reader; collapsing a book into one text
	 * blob throws that away permanently.
	 *
	 * FULLTEXT on the folded column, so lookups use MATCH ... AGAINST instead of
	 * LIKE '%...%'. Note: innodb_ft_min_token_size defaults to 3, which silently
	 * drops every two-letter Vietnamese syllable once folded ("vô"→vo, "từ"→tu).
	 * Set it to 2 in my.cnf and rebuild, or those words become unsearchable.
	 */
	$pages = "CREATE TABLE {$wpdb->prefix}nntm_pdf_pages (
		id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
		attachment_id BIGINT UNSIGNED NOT NULL,
		post_id       BIGINT UNSIGNED NOT NULL DEFAULT 0,
		page_no       SMALLINT UNSIGNED NOT NULL,
		content       LONGTEXT NOT NULL,
		folded        LONGTEXT NOT NULL,
		source        VARCHAR(8) NOT NULL DEFAULT 'text',
		updated_at    DATETIME NOT NULL,
		PRIMARY KEY  (id),
		UNIQUE KEY uniq_page (attachment_id, page_no),
		KEY idx_post (post_id),
		FULLTEXT KEY ft_folded (folded)
	) $collate;";

	dbDelta( $vectors );
	dbDelta( $pages );

	update_option( 'nntm_search_db_version', '2' );
}

/**
 * Image vector table name.
 *
 * @return string
 */
function nntm_search_table_vectors(): string {
	global $wpdb;

	return $wpdb->prefix . 'nntm_image_vectors';
}

/**
 * PDF page table name.
 *
 * @return string
 */
function nntm_search_table_pdf_pages(): string {
	global $wpdb;

	return $wpdb->prefix . 'nntm_pdf_pages';
}
