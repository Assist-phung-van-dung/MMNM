<?php

defined( 'ABSPATH' ) || exit;

/**
 * Return public NNTM post types that expose an archive.
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
 * NNTM CPTs that already own a dedicated single template and must keep their
 * special business logic.
 *
 * @return string[]
 */
function nntm_cpt_dedicated_single_post_types(): array {
	return array( 'nntm_article', 'nntm_publication', 'nntm_retreat' );
}

/**
 * NNTM CPTs that should use the shared article-detail presentation.
 *
 * @return string[]
 */
function nntm_cpt_shared_single_post_types(): array {
	return array_values(
		array_diff(
			nntm_cpt_archive_post_types(),
			nntm_cpt_dedicated_single_post_types()
		)
	);
}

/**
 * Whether the current request is an NNTM CPT archive using the shared UI.
 */
function nntm_is_cpt_archive_listing(): bool {
	$post_types = nntm_cpt_archive_post_types();
	return ! empty( $post_types ) && is_post_type_archive( $post_types );
}

/**
 * Whether the current request is a generic NNTM CPT single using the shared UI.
 */
function nntm_is_cpt_shared_single(): bool {
	$post_types = nntm_cpt_shared_single_post_types();
	return ! empty( $post_types ) && is_singular( $post_types );
}

/**
 * Preserve any special destination URL used by a CPT archive.
 */
function nntm_cpt_archive_item_url( WP_Post $post ): string {
	$url = get_permalink( $post );

	// Ấn phẩm keeps its existing direct-document behavior when the user can read it.
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
 * Load the exact row-layout foundation used by /phan-muc/nguyen-thuy/ and
 * the current article-detail presentation for generic CPT singles.
 */
function nntm_enqueue_cpt_archive_assets(): void {
	if ( nntm_is_cpt_archive_listing() ) {
		$rows_css = NNTM_THEME_DIR . '/blocks/article-rows/style.css';
		wp_enqueue_style(
			'nntm-cpt-archive-article-rows',
			NNTM_THEME_URI . '/blocks/article-rows/style.css',
			array( 'nntm-tokens', 'nntm-base', 'nntm-layout', 'nntm-favorites' ),
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

	if ( nntm_is_cpt_shared_single() ) {
		$article_css = NNTM_THEME_DIR . '/assets/css/pages/article-detail.css';
		wp_enqueue_style(
			'nntm-cpt-article-detail',
			NNTM_THEME_URI . '/assets/css/pages/article-detail.css',
			array( 'nntm-tokens', 'nntm-base', 'nntm-layout', 'nntm-favorites' ),
			nntm_asset_version( $article_css )
		);

		$detail_css = NNTM_THEME_DIR . '/assets/css/pages/cpt-detail.css';
		wp_enqueue_style(
			'nntm-cpt-detail',
			NNTM_THEME_URI . '/assets/css/pages/cpt-detail.css',
			array( 'nntm-cpt-article-detail' ),
			nntm_asset_version( $detail_css )
		);
	}
}
add_action( 'wp_enqueue_scripts', 'nntm_enqueue_cpt_archive_assets', 41 );

/**
 * Route generic NNTM CPT singles to one maintainable shared template.
 */
function nntm_cpt_shared_single_template( string $template ): string {
	if ( ! nntm_is_cpt_shared_single() ) {
		return $template;
	}

	$shared_template = NNTM_THEME_DIR . '/template-parts/single/cpt-detail.php';
	return is_readable( $shared_template ) ? $shared_template : $template;
}
add_filter( 'single_template', 'nntm_cpt_shared_single_template', 50 );
