<?php

defined( 'ABSPATH' ) || exit;

const NNTM_HK_LOP_DAI_AN_PHAM = 'nntm-hk-publications';

function nntm_hk_chot_bo_cuc_dai_an_pham( array $parsed_block ): array {
	if ( is_admin() || empty( $parsed_block['blockName'] ) ) {
		return $parsed_block;
	}

	if ( 'nntm/card-list' !== $parsed_block['blockName'] || ! is_page( 'hoa-khai' ) ) {
		return $parsed_block;
	}

	$attrs      = isset( $parsed_block['attrs'] ) && is_array( $parsed_block['attrs'] ) ? $parsed_block['attrs'] : array();
	$class_name = isset( $attrs['className'] ) ? (string) $attrs['className'] : '';

	if ( false === strpos( $class_name, NNTM_HK_LOP_DAI_AN_PHAM ) ) {
		return $parsed_block;
	}

	$parsed_block['attrs']['layout']  = 'marquee';
	$parsed_block['attrs']['variant'] = 'books';
	$parsed_block['attrs']['className'] = trim( (string) preg_replace( '/(^|\\s)' . preg_quote( NNTM_HK_LOP_DAI_AN_PHAM, '/' ) . '(?=\\s|$)/', ' ', $class_name ) );

	return $parsed_block;
}
add_filter( 'render_block_data', 'nntm_hk_chot_bo_cuc_dai_an_pham', 10, 1 );
