<?php

defined( 'ABSPATH' ) || exit;

function nntm_header_khoi_nen_toi(): array {
	return (array) apply_filters(
		'nntm_header_khoi_nen_toi',
		array( 'nntm/hero-slider', 'nntm/banner', 'nntm/rank-card' )
	);
}

function nntm_header_khoi_nen_sang(): array {
	return (array) apply_filters(
		'nntm_header_khoi_nen_sang',
		array(
			'nntm/feature-carousel',
			'nntm/feature-carousel-gallery',
			'nntm/article-mosaic',
			'nntm/article-feature',
			'nntm/article-rows',
			'nntm/card-list',
			'nntm/term-list',
			'nntm/tru-xu-list',
			'nntm/dieu-thuong',
			'nntm/thien-duong',
			'nntm/engineering-earth',
			'nntm/cong-tu',
			'nntm/feature',
		)
	);
}

function nntm_header_khoi_dau( ?WP_Post $post = null ): ?array {
	$post = $post ?: get_post();

	if ( ! $post instanceof WP_Post || ! has_blocks( $post ) ) {
		return null;
	}

	foreach ( parse_blocks( $post->post_content ) as $block ) {
		if ( ! empty( $block['blockName'] ) ) {
			return $block;
		}
	}

	return null;
}

function nntm_header_kieu_dau_trang( ?WP_Post $post = null ): string {
	$block = nntm_header_khoi_dau( $post );

	if ( null === $block ) {
		return 'dac';
	}

	$ten   = (string) $block['blockName'];
	$attrs = isset( $block['attrs'] ) && is_array( $block['attrs'] ) ? $block['attrs'] : array();

	if ( in_array( $ten, nntm_header_khoi_nen_toi(), true ) ) {
		return 'toi';
	}

	if ( ! in_array( $ten, nntm_header_khoi_nen_sang(), true ) ) {
		return 'dac';
	}

	$nen = isset( $attrs['background'] ) ? sanitize_key( (string) $attrs['background'] ) : '';

	return in_array( $nen, array( 'toi', 'cham', 'den' ), true ) ? 'toi' : 'sang';
}

function nntm_header_them_khoi_hero( array $ten_khoi ): array {
	$post = ( is_page() || is_front_page() ) ? get_queried_object() : null;

	if ( ! $post instanceof WP_Post ) {
		return $ten_khoi;
	}

	if ( 'dac' === nntm_header_kieu_dau_trang( $post ) ) {
		return $ten_khoi;
	}

	$block = nntm_header_khoi_dau( $post );

	if ( null !== $block && ! in_array( (string) $block['blockName'], $ten_khoi, true ) ) {
		$ten_khoi[] = (string) $block['blockName'];
	}

	return $ten_khoi;
}
add_filter( 'nntm_hero_block_names', 'nntm_header_them_khoi_hero' );

function nntm_header_body_class( array $classes ): array {
	if ( ! is_page() && ! is_front_page() ) {
		return $classes;
	}

	$post = get_queried_object();

	if ( ! $post instanceof WP_Post ) {
		return $classes;
	}

	if ( 'sang' === nntm_header_kieu_dau_trang( $post ) ) {
		$classes[] = 'nntm-dau-trang-sang';
	}

	return $classes;
}
add_filter( 'body_class', 'nntm_header_body_class' );

function nntm_header_do_chieu_cao(): void {
	if ( ! wp_script_is( 'nntm-header', 'enqueued' ) ) {
		return;
	}

	$js = <<<'JS'
( function () {
	var root = document.documentElement;

	function do_() {
		var h = document.querySelector( '.nntm-header' );

		if ( ! h ) {
			return;
		}

		root.style.setProperty( '--nntm-header-h-that', h.offsetHeight + 'px' );
	}

	do_();

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', do_ );
	}

	if ( document.fonts && document.fonts.ready && document.fonts.ready.then ) {
		document.fonts.ready.then( do_ );
	}

	window.addEventListener( 'load', do_ );
	window.addEventListener( 'resize', do_, { passive: true } );
} )();
JS;

	wp_add_inline_script( 'nntm-header', $js );
}
add_action( 'wp_enqueue_scripts', 'nntm_header_do_chieu_cao', 90 );
