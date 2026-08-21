<?php
/**
 * Front-end semantic classes for the Kim Cương Hành Giả composition.
 *
 * The page is assembled from reusable dynamic blocks.  The design-specific
 * stylesheet must never depend on WordPress generated selectors such as
 * `.page-id-243`, so this file gives the five bands stable semantic classes
 * while keeping the stored Gutenberg order untouched.
 *
 * @package NNTM
 */

defined( 'ABSPATH' ) || exit;

/**
 * Append a class name to parsed block attributes without duplicating it.
 *
 * @param array  $block Parsed block data.
 * @param string $class Class to append.
 * @return array
 */
function nntm_kchg_add_block_class( array $block, string $class ): array {
	if ( ! isset( $block['attrs'] ) || ! is_array( $block['attrs'] ) ) {
		$block['attrs'] = array();
	}

	$current = isset( $block['attrs']['className'] ) ? trim( (string) $block['attrs']['className'] ) : '';
	$classes = '' === $current ? array() : preg_split( '/\s+/', $current );
	$classes = is_array( $classes ) ? array_values( array_filter( $classes ) ) : array();

	if ( ! in_array( $class, $classes, true ) ) {
		$classes[] = $class;
	}

	$block['attrs']['className'] = implode( ' ', $classes );
	return $block;
}

/**
 * Give every visual band a stable class on the Kim Cương page.
 *
 * This does not reorder blocks and does not delete editor content.  It only
 * enriches the parsed attributes before the dynamic block render callback
 * runs, so existing databases receive the semantic classes immediately.
 *
 * @param array $parsed_block Parsed Gutenberg block.
 * @return array
 */
function nntm_kchg_semantic_block_classes( array $parsed_block ): array {
	if ( is_admin() || ! is_page( 'kim-cuong-hanh-gia' ) || empty( $parsed_block['blockName'] ) ) {
		return $parsed_block;
	}

	$attrs      = isset( $parsed_block['attrs'] ) && is_array( $parsed_block['attrs'] ) ? $parsed_block['attrs'] : array();
	$class_name = isset( $attrs['className'] ) ? (string) $attrs['className'] : '';

	if ( 'nntm/banner' === $parsed_block['blockName'] ) {
		if ( false !== strpos( $class_name, 'nntm-banner--khu-dau' ) ) {
			return nntm_kchg_add_block_class( $parsed_block, 'nntm-kchg-hero' );
		}

		if ( false !== strpos( $class_name, 'nntm-banner--kc-ledan' ) ) {
			return nntm_kchg_add_block_class( $parsed_block, 'nntm-kchg-le-dan' );
		}
	}

	if ( 'nntm/card-list' === $parsed_block['blockName'] ) {
		if ( isset( $attrs['variant'] ) && 'kim-cuong' === $attrs['variant'] ) {
			return nntm_kchg_add_block_class( $parsed_block, 'nntm-kchg-articles' );
		}

		if (
			false !== strpos( $class_name, 'nntm-kc-nghi-quy' ) ||
			( isset( $attrs['heading'] ) && 'Nghi Quỹ' === trim( (string) $attrs['heading'] ) )
		) {
			return nntm_kchg_add_block_class( $parsed_block, 'nntm-kchg-nghi-quy' );
		}
	}

	if ( 'nntm/cong-tu' === $parsed_block['blockName'] ) {
		// The Kim Cương artwork uses the gold treatment for this final band.
		$parsed_block['attrs']['background'] = 'vang';
		return nntm_kchg_add_block_class( $parsed_block, 'nntm-kchg-ranking' );
	}

	return $parsed_block;
}
add_filter( 'render_block_data', 'nntm_kchg_semantic_block_classes', 10, 1 );
