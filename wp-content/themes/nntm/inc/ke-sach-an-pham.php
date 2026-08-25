<?php

defined( 'ABSPATH' ) || exit;

const NNTM_KE_SACH_LOP = 'nntm-hk-publications';

function nntm_ke_sach_trang(): array {
	return (array) apply_filters( 'nntm_ke_sach_trang', array( 'hoa-khai', 'nghi-quy' ) );
}

function nntm_ke_sach_tren_trang(): bool {
	return is_page( nntm_ke_sach_trang() );
}

function nntm_ke_sach_enqueue(): void {
	if ( ! nntm_ke_sach_tren_trang() ) {
		return;
	}

	$css_path = NNTM_THEME_DIR . '/assets/css/pages/ke-sach-an-pham.css';
	wp_enqueue_style(
		'nntm-ke-sach-an-pham',
		NNTM_THEME_URI . '/assets/css/pages/ke-sach-an-pham.css',
		array( 'nntm-tokens', 'nntm-base', 'nntm-layout', 'nntm-card-list-style' ),
		nntm_asset_version( $css_path )
	);
}
add_action( 'wp_enqueue_scripts', 'nntm_ke_sach_enqueue', 61 );

function nntm_ke_sach_chot_bo_cuc( array $parsed_block ): array {
	if ( is_admin() || empty( $parsed_block['blockName'] ) ) {
		return $parsed_block;
	}

	if ( 'nntm/card-list' !== $parsed_block['blockName'] || ! is_page( 'nghi-quy' ) ) {
		return $parsed_block;
	}

	$attrs = isset( $parsed_block['attrs'] ) && is_array( $parsed_block['attrs'] ) ? $parsed_block['attrs'] : array();

	if ( ! isset( $attrs['postType'] ) || 'nntm_publication' !== $attrs['postType'] ) {
		return $parsed_block;
	}

	$class_name = isset( $attrs['className'] ) ? (string) $attrs['className'] : '';

	$parsed_block['attrs']['className']    = trim( (string) preg_replace( '/(^|\\s)' . preg_quote( NNTM_KE_SACH_LOP, '/' ) . '(?=\\s|$)/', ' ', $class_name ) );
	$parsed_block['attrs']['layout']       = 'marquee';
	$parsed_block['attrs']['variant']      = 'books';
	$parsed_block['attrs']['showPaging']   = false;
	$parsed_block['attrs']['showDate']     = false;
	$parsed_block['attrs']['showCategory'] = false;

	if ( ! isset( $attrs['postsPerPage'] ) || (int) $attrs['postsPerPage'] < 12 ) {
		$parsed_block['attrs']['postsPerPage'] = 12;
	}

	return $parsed_block;
}
add_filter( 'render_block_data', 'nntm_ke_sach_chot_bo_cuc', 10, 1 );
