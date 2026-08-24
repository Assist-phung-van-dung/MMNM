<?php

defined( 'ABSPATH' ) || exit;

function nntm_reveal_bo_qua(): array {
	return (array) apply_filters( 'nntm_reveal_bo_qua', array( 'nntm/floating-video', 'nntm/floating-bar' ) );
}

function nntm_reveal_head_script(): void {
	if ( is_admin() ) {
		return;
	}
	?>
	<script>document.documentElement.classList.add('nntm-co-js');</script>
	<?php
}
add_action( 'wp_head', 'nntm_reveal_head_script', 1 );

function nntm_reveal_danh_dau_khoi( $block_content, $parsed_block ) {
	if ( is_admin() || is_feed() ) {
		return $block_content;
	}

	if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
		return $block_content;
	}

	if ( ! is_array( $parsed_block ) || empty( $parsed_block['blockName'] ) ) {
		return $block_content;
	}

	if ( 0 !== strpos( (string) $parsed_block['blockName'], 'nntm/' ) ) {
		return $block_content;
	}

	if ( in_array( $parsed_block['blockName'], nntm_reveal_bo_qua(), true ) ) {
		return $block_content;
	}

	if ( '' === trim( (string) $block_content ) || ! class_exists( 'WP_HTML_Tag_Processor' ) ) {
		return $block_content;
	}

	$the = new WP_HTML_Tag_Processor( (string) $block_content );

	if ( ! $the->next_tag() ) {
		return $block_content;
	}

	$the->add_class( 'nntm-reveal' );

	return $the->get_updated_html();
}
add_filter( 'render_block', 'nntm_reveal_danh_dau_khoi', 20, 2 );

function nntm_reveal_enqueue(): void {
	$css_path = NNTM_THEME_DIR . '/assets/css/reveal.css';
	wp_enqueue_style(
		'nntm-reveal',
		NNTM_THEME_URI . '/assets/css/reveal.css',
		array( 'nntm-tokens', 'nntm-base' ),
		nntm_asset_version( $css_path )
	);

	$js_path = NNTM_THEME_DIR . '/assets/js/reveal.js';
	wp_enqueue_script(
		'nntm-reveal',
		NNTM_THEME_URI . '/assets/js/reveal.js',
		array(),
		nntm_asset_version( $js_path ),
		true
	);
}
add_action( 'wp_enqueue_scripts', 'nntm_reveal_enqueue' );
