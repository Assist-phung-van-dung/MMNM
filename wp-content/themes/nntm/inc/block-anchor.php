<?php

defined( 'ABSPATH' ) || exit;

/**
 * Ensure the optional Gutenberg HTML Anchor is applied to every NNTM block.
 *
 * Most NNTM dynamic blocks already use get_block_wrapper_attributes(), so
 * WordPress adds the anchor automatically. This render filter is a safety net
 * for blocks whose renderer returns custom markup without block wrapper attrs
 * (for example the card block).
 *
 * Empty anchors are intentionally ignored, so no id attribute is rendered.
 * Existing root IDs are never overwritten.
 *
 * @param string $block_content Rendered block HTML.
 * @param array  $block         Parsed block data.
 * @return string
 */
function nntm_apply_optional_block_anchor( string $block_content, array $block ): string {
	$block_name = isset( $block['blockName'] ) ? (string) $block['blockName'] : '';

	if ( 0 !== strpos( $block_name, 'nntm/' ) ) {
		return $block_content;
	}

	$raw_anchor = isset( $block['attrs']['anchor'] ) ? trim( (string) $block['attrs']['anchor'] ) : '';
	if ( '' === $raw_anchor ) {
		return $block_content;
	}

	$anchor = sanitize_title( $raw_anchor );
	if ( '' === $anchor || '' === trim( $block_content ) ) {
		return $block_content;
	}

	if ( class_exists( 'WP_HTML_Tag_Processor' ) ) {
		$processor = new WP_HTML_Tag_Processor( $block_content );

		if ( $processor->next_tag() ) {
			$existing_id = $processor->get_attribute( 'id' );

			if ( null === $existing_id || '' === trim( (string) $existing_id ) ) {
				$processor->set_attribute( 'id', $anchor );
				return $processor->get_updated_html();
			}
		}

		return $block_content;
	}

	// Compatibility fallback for older WordPress versions without the HTML API.
	return preg_replace_callback(
		'/^(\s*<([a-zA-Z][a-zA-Z0-9:-]*))(\s[^>]*)?>/',
		static function ( array $matches ) use ( $anchor ): string {
			$attributes = isset( $matches[3] ) ? $matches[3] : '';

			if ( preg_match( '/\sid\s*=\s*(["\']).*?\1/i', $attributes ) ) {
				return $matches[0];
			}

			return $matches[1] . ' id="' . esc_attr( $anchor ) . '"' . $attributes . '>';
		},
		$block_content,
		1
	) ?: $block_content;
}
add_filter( 'render_block', 'nntm_apply_optional_block_anchor', 10, 2 );
