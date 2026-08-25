<?php

defined( 'ABSPATH' ) || exit;

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

	}

	if ( 'nntm/cong-tu' === $parsed_block['blockName'] ) {

		$parsed_block['attrs']['background'] = 'vang';
		return nntm_kchg_add_block_class( $parsed_block, 'nntm-kchg-ranking' );
	}

	return $parsed_block;
}
add_filter( 'render_block_data', 'nntm_kchg_semantic_block_classes', 10, 1 );
