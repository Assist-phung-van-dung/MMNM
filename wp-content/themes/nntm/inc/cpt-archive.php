<?php

defined( 'ABSPATH' ) || exit;

/**
 * Return public NNTM post types that expose an archive.
 *
 * Keeping this list dynamic means future nntm_* CPT archives automatically
 * receive the same listing UI unless they intentionally provide a dedicated
 * archive template.
 *
 * @return string[]
 */
function nntm_cpt_archive_post_types(): array {
	$post_types = get_post_types( array( 'public' => true ), 'objects' );
	$result     = array();

	foreach ( $post_types as $post_type ) {
		if ( ! $post_type instanceof WP_Post_Type ) {
			continue;
		}

		if ( 0 !== strpos( $post_type->name, 'nntm_' ) || empty( $post_type->has_archive ) ) {
			continue;
		}

		$result[] = sanitize_key( $post_type->name );
	}

	return array_values( array_unique( $result ) );
}

/**
 * Whether the current request is an NNTM CPT archive using the shared UI.
 */
function nntm_is_cpt_archive_listing(): bool {
	$post_types = nntm_cpt_archive_post_types();
	return ! empty( $post_types ) && is_post_type_archive( $post_types );
}

/**
 * Preserve any special destination URL used by a CPT archive.
 */
function nntm_cpt_archive_item_url( WP_Post $post ): string {
	$url = get_permalink( $post );

	// Ấn phẩm previously linked directly to its readable document when allowed.
	if (
		'nntm_publication' === $post->post_type
		&& function_exists( 'nntm_doc_url' )
		&& function_exists( 'nntm_an_pham_can_access' )
		&& nntm_an_pham_can_access( $post )
	) {
		$document_url = (string) nntm_doc_url( $post );
		if ( '' !== trim( $document_url ) ) {
			$url = $document_url;
		}
	}

	return (string) $url;
}

/**
 * Load the exact row-layout foundation used by /chu-de/khoa-tu/.
 */
function nntm_enqueue_cpt_archive_assets(): void {
	if ( ! nntm_is_cpt_archive_listing() ) {
		return;
	}

	$rows_css = NNTM_THEME_DIR . '/blocks/article-rows/style.css';
	wp_enqueue_style(
		'nntm-cpt-archive-article-rows',
		NNTM_THEME_URI . '/blocks/article-rows/style.css',
		array( 'nntm-tokens', 'nntm-base', 'nntm-layout' ),
		nntm_asset_version( $rows_css )
	);

	$archive_css = NNTM_THEME_DIR . '/assets/css/pages/cpt-archive.css';
	wp_enqueue_style(
		'nntm-cpt-archive',
		NNTM_THEME_URI . '/assets/css/pages/cpt-archive.css',
		array( 'nntm-cpt-archive-article-rows' ),
		nntm_asset_version( $archive_css )
	);
}
add_action( 'wp_enqueue_scripts', 'nntm_enqueue_cpt_archive_assets', 41 );
