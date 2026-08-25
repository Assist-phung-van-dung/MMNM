<?php

defined( 'ABSPATH' ) || exit;

function nntm_sao_link_can_tai(): bool {
	if ( is_admin() ) {
		return false;
	}

	return (bool) apply_filters( 'nntm_sao_link_can_tai', true );
}

function nntm_sao_link_enqueue(): void {
	if ( ! nntm_sao_link_can_tai() ) {
		return;
	}

	$css = NNTM_THEME_DIR . '/assets/css/sao-link.css';
	$js  = NNTM_THEME_DIR . '/assets/js/sao-link.js';

	wp_enqueue_style(
		'nntm-sao-link',
		NNTM_THEME_URI . '/assets/css/sao-link.css',
		array( 'nntm-tokens' ),
		nntm_asset_version( $css )
	);

	wp_enqueue_script(
		'nntm-sao-link',
		NNTM_THEME_URI . '/assets/js/sao-link.js',
		array(),
		nntm_asset_version( $js ),
		true
	);
}
add_action( 'wp_enqueue_scripts', 'nntm_sao_link_enqueue', 40 );
