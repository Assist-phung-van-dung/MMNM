<?php

defined( 'ABSPATH' ) || exit;

function nntm_setup(): void {
	 
	load_theme_textdomain( 'nntm', NNTM_THEME_DIR . '/languages' );

	add_theme_support( 'title-tag' );

	add_theme_support( 'post-thumbnails' );

	add_theme_support(
		'html5',
		array(
			'search-form',
			'comment-form',
			'comment-list',
			'gallery',
			'caption',
			'style',
			'script',
			'navigation-widgets',
		)
	);

	add_theme_support( 'responsive-embeds' );

	add_theme_support( 'editor-styles' );

	add_theme_support( 'align-wide' );

	add_theme_support(
		'custom-logo',
		array(
			'height'      => 80,
			'width'       => 240,
			'flex-height' => true,
			'flex-width'  => true,
		)
	);

	add_theme_support( 'block-template-parts' );

	register_nav_menus(
		array(
			'primary' => esc_html__( 'Menu chính', 'nntm' ),
			'footer'  => esc_html__( 'Menu chân trang', 'nntm' ),
		)
	);
}
add_action( 'after_setup_theme', 'nntm_setup' );

function nntm_footer_menu_fallback(): void {
	$items = array(
		've-chung-toi' => esc_html__( 'Về chúng tôi', 'nntm' ),
		'lien-he'      => esc_html__( 'Liên hệ', 'nntm' ),
		'chinh-sach'   => esc_html__( 'Chính sách', 'nntm' ),
	);

	$links = array();
	foreach ( $items as $slug => $label ) {
		$page = get_page_by_path( $slug );
		if ( $page ) {
			$links[] = '<li><a href="' . esc_url( get_permalink( $page ) ) . '">' . esc_html( $label ) . '</a></li>';
		}
	}

	if ( ! $links ) {
		return;
	}

	echo '<ul class="nntm-footer-nav">' . implode( '', $links ) . '</ul>';  
}

function nntm_page_has_own_heading( ?WP_Post $post = null ): bool {
	$post = $post ?: get_post();
	if ( ! $post || ! has_blocks( $post ) ) {
		return false;
	}

	foreach ( parse_blocks( $post->post_content ) as $block ) {
		if ( empty( $block['blockName'] ) || 0 !== strpos( $block['blockName'], 'nntm/' ) ) {
			continue;
		}
		$heading = $block['attrs']['heading'] ?? '';
		if ( is_string( $heading ) && '' !== trim( wp_strip_all_tags( $heading ) ) ) {
			return true;
		}
	}

	return false;
}

function nntm_page_uses_section_blocks( ?WP_Post $post = null ): bool {
	$post = $post ?: get_post();
	if ( ! $post || ! has_blocks( $post ) ) {
		return false;
	}

	foreach ( parse_blocks( $post->post_content ) as $block ) {
		if ( ! empty( $block['blockName'] ) && 0 === strpos( $block['blockName'], 'nntm/' ) ) {
			return true;
		}
	}

	return false;
}

function nntm_page_starts_with_hero( ?WP_Post $post = null ): bool {
	$post = $post ?: get_post();
	if ( ! $post || ! has_blocks( $post ) ) {
		return false;
	}

	$hero_blocks = apply_filters(
		'nntm_hero_block_names',
		array( 'nntm/hero-slider', 'nntm/banner' )
	);

	foreach ( parse_blocks( $post->post_content ) as $block ) {
		if ( empty( $block['blockName'] ) ) {
			continue;  
		}
		return in_array( $block['blockName'], $hero_blocks, true );
	}

	return false;
}

function nntm_body_class_hero( array $classes ): array {
	if ( ( is_page() || is_front_page() ) && nntm_page_starts_with_hero( get_queried_object() ) ) {
		$classes[] = 'nntm-dau-trang-de-len';
	}

	return $classes;
}
add_filter( 'body_class', 'nntm_body_class_hero' );
