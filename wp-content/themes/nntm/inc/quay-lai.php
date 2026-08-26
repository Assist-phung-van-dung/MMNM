<?php

defined( 'ABSPATH' ) || exit;

const NNTM_QUAY_LAI_TIEN_TO = 'nntm-muc-';

/**
 * Card List sections need a stable id so the reader's back button can return to
 * the exact section the visitor clicked from. Editors can set the HTML anchor
 * themselves; this fills in a deterministic id when they have not.
 *
 * Runs after nntm_apply_optional_block_anchor() so a hand-set anchor always wins.
 *
 * @param string $block_content Rendered block HTML.
 * @param array  $block         Parsed block data.
 * @return string
 */
function nntm_quay_lai_neo_du_phong( string $block_content, array $block ): string {
	if ( is_admin() || 'nntm/card-list' !== ( isset( $block['blockName'] ) ? (string) $block['blockName'] : '' ) ) {
		return $block_content;
	}

	if ( '' === trim( $block_content ) || ! class_exists( 'WP_HTML_Tag_Processor' ) ) {
		return $block_content;
	}

	$processor = new WP_HTML_Tag_Processor( $block_content );

	if ( ! $processor->next_tag() ) {
		return $block_content;
	}

	$id_hien_co = $processor->get_attribute( 'id' );

	if ( null !== $id_hien_co && '' !== trim( (string) $id_hien_co ) ) {
		return $block_content;
	}

	$attrs = isset( $block['attrs'] ) && is_array( $block['attrs'] ) ? $block['attrs'] : array();

	$processor->set_attribute( 'id', NNTM_QUAY_LAI_TIEN_TO . substr( md5( (string) wp_json_encode( $attrs ) ), 0, 8 ) );

	return $processor->get_updated_html();
}
add_filter( 'render_block', 'nntm_quay_lai_neo_du_phong', 11, 2 );

function nntm_quay_lai_enqueue(): void {
	$js = NNTM_THEME_DIR . '/assets/js/quay-lai.js';

	if ( ! is_readable( $js ) ) {
		return;
	}

	wp_enqueue_script( 'nntm-quay-lai', NNTM_THEME_URI . '/assets/js/quay-lai.js', array(), nntm_asset_version( $js ), true );
}
add_action( 'wp_enqueue_scripts', 'nntm_quay_lai_enqueue', 12 );
