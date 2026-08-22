<?php

defined( 'ABSPATH' ) || exit;

function nntm_resource_hints( array $urls, string $relation_type ): array {
	if ( 'preconnect' === $relation_type ) {
		$urls[] = array(
			'href' => 'https://fonts.googleapis.com',
		);
		$urls[] = array(
			'href'        => 'https://fonts.gstatic.com',
			'crossorigin' => 'anonymous',
		);
	}
	return $urls;
}
add_filter( 'wp_resource_hints', 'nntm_resource_hints', 10, 2 );

function nntm_google_fonts_url(): string {
	$families = array(
		'Be Vietnam Pro:wght@400;500;700',
		'Lora:wght@400;500',
		'EB Garamond:wght@400;600',
		'Inter:wght@400;500',
		'Questrial:wght@400',
	);

	 

	$query = 'family=' . implode( '&family=', array_map( 'rawurlencode', $families ) ) . '&display=swap';

	return 'https://fonts.googleapis.com/css2?' . $query;
}

function nntm_enqueue_assets(): void {
	 
	wp_enqueue_style( 'nntm-google-fonts', nntm_google_fonts_url(), array(), null );

	$tokens_path = NNTM_THEME_DIR . '/assets/css/tokens.css';
	wp_enqueue_style( 'nntm-tokens', NNTM_THEME_URI . '/assets/css/tokens.css', array(), nntm_asset_version( $tokens_path ) );

	$tokens_generated_path = NNTM_THEME_DIR . '/assets/css/tokens.generated.css';
	if ( file_exists( $tokens_generated_path ) ) {
		wp_enqueue_style( 'nntm-tokens-generated', NNTM_THEME_URI . '/assets/css/tokens.generated.css', array( 'nntm-tokens' ), nntm_asset_version( $tokens_generated_path ) );
	}

	$base_path = NNTM_THEME_DIR . '/assets/css/base.css';
	wp_enqueue_style( 'nntm-base', NNTM_THEME_URI . '/assets/css/base.css', array( 'nntm-tokens' ), nntm_asset_version( $base_path ) );

	$layout_path = NNTM_THEME_DIR . '/assets/css/layout.css';
	wp_enqueue_style( 'nntm-layout', NNTM_THEME_URI . '/assets/css/layout.css', array( 'nntm-tokens', 'nntm-base' ), nntm_asset_version( $layout_path ) );

	$header_css_path = NNTM_THEME_DIR . '/assets/css/header.css';
	wp_enqueue_style( 'nntm-header', NNTM_THEME_URI . '/assets/css/header.css', array( 'nntm-tokens', 'nntm-base', 'nntm-layout' ), nntm_asset_version( $header_css_path ) );

	$footer_css_path = NNTM_THEME_DIR . '/assets/css/footer.css';
	wp_enqueue_style( 'nntm-footer', NNTM_THEME_URI . '/assets/css/footer.css', array( 'nntm-tokens', 'nntm-base', 'nntm-layout' ), nntm_asset_version( $footer_css_path ) );

	$responsive_css_path = NNTM_THEME_DIR . '/assets/css/responsive.css';
	wp_enqueue_style( 'nntm-responsive', NNTM_THEME_URI . '/assets/css/responsive.css', array( 'nntm-tokens', 'nntm-base', 'nntm-layout', 'nntm-header', 'nntm-footer' ), nntm_asset_version( $responsive_css_path ) );

	if ( function_exists( 'nntm_preloader_enabled' ) && nntm_preloader_enabled() ) {
		$preloader_css_path = NNTM_THEME_DIR . '/assets/css/preloader.css';
		wp_enqueue_style( 'nntm-preloader', NNTM_THEME_URI . '/assets/css/preloader.css', array( 'nntm-tokens' ), nntm_asset_version( $preloader_css_path ) );

		$preloader_js_path = NNTM_THEME_DIR . '/assets/js/preloader.js';
		wp_enqueue_script( 'nntm-preloader', NNTM_THEME_URI . '/assets/js/preloader.js', array(), nntm_asset_version( $preloader_js_path ), true );
	}

	$header_js_path = NNTM_THEME_DIR . '/assets/js/header.js';
	wp_enqueue_script( 'nntm-header', NNTM_THEME_URI . '/assets/js/header.js', array(), nntm_asset_version( $header_js_path ), true );

	$header_scroll_js_path = NNTM_THEME_DIR . '/assets/js/header-scroll.js';
	wp_enqueue_script( 'nntm-header-scroll', NNTM_THEME_URI . '/assets/js/header-scroll.js', array(), nntm_asset_version( $header_scroll_js_path ), true );
	wp_script_add_data( 'nntm-header-scroll', 'strategy', 'defer' );
}
add_action( 'wp_enqueue_scripts', 'nntm_enqueue_assets' );

function nntm_enqueue_r1_assets(): void {
	if ( ! is_page( 'r1' ) ) {
		return;
	}

	$r1_css_path = NNTM_THEME_DIR . '/assets/css/pages/r1.css';
	wp_enqueue_style(
		'nntm-r1',
		NNTM_THEME_URI . '/assets/css/pages/r1.css',
		array( 'nntm-tokens', 'nntm-base' ),
		nntm_asset_version( $r1_css_path )
	);

	$r1_js_path = NNTM_THEME_DIR . '/assets/js/r1.js';
	wp_enqueue_script(
		'nntm-r1',
		NNTM_THEME_URI . '/assets/js/r1.js',
		array(),
		nntm_asset_version( $r1_js_path ),
		true
	);

	wp_dequeue_style( 'nntm-header' );
	wp_dequeue_style( 'nntm-footer' );
	wp_dequeue_script( 'nntm-header' );
	wp_dequeue_script( 'nntm-header-scroll' );
}
add_action( 'wp_enqueue_scripts', 'nntm_enqueue_r1_assets', 20 );

function nntm_editor_assets(): void {
	add_editor_style(
		array(
			'assets/css/tokens.css',
			'assets/css/base.css',
		)
	);
}
add_action( 'after_setup_theme', 'nntm_editor_assets' );

function nntm_asset_version( string $absolute_path ) {
	return file_exists( $absolute_path ) ? filemtime( $absolute_path ) : false;
}

function nntm_enqueue_regular_article_detail_assets(): void {
	if ( ! is_singular( 'nntm_article' ) ) {
		return;
	}

	if ( function_exists( 'nntm_bai_thuoc_hanh_gia' ) && null !== nntm_bai_thuoc_hanh_gia( get_queried_object() ) ) {
		return;
	}

	$css_path = NNTM_THEME_DIR . '/assets/css/pages/article-detail.css';
	wp_enqueue_style(
		'nntm-article-detail',
		NNTM_THEME_URI . '/assets/css/pages/article-detail.css',
		array( 'nntm-tokens', 'nntm-base', 'nntm-layout' ),
		nntm_asset_version( $css_path )
	);
}
add_action( 'wp_enqueue_scripts', 'nntm_enqueue_regular_article_detail_assets', 35 );

function nntm_enqueue_homepage_figma_assets(): void {
	if ( ! is_front_page() ) {
		return;
	}

	$css_path = NNTM_THEME_DIR . '/assets/css/pages/homepage-figma.css';
	wp_enqueue_style(
		'nntm-homepage-figma',
		NNTM_THEME_URI . '/assets/css/pages/homepage-figma.css',
		array(
			'nntm-tokens',
			'nntm-base',
			'nntm-layout',
			'nntm-header',
			'nntm-footer',
			'nntm-hero-slider-style',
			'nntm-article-mosaic-style',
			'nntm-article-feature-style',
			'nntm-card-list-style',
			'nntm-engineering-earth-style',
		),
		nntm_asset_version( $css_path )
	);
}
add_action( 'wp_enqueue_scripts', 'nntm_enqueue_homepage_figma_assets', 40 );

function nntm_enqueue_dieu_thuong_figma_assets(): void {
	if ( ! is_page( 'dieu-thuong' ) ) {
		return;
	}

	$css_path = NNTM_THEME_DIR . '/assets/css/pages/dieu-thuong-figma.css';
	wp_enqueue_style(
		'nntm-dieu-thuong-figma',
		NNTM_THEME_URI . '/assets/css/pages/dieu-thuong-figma.css',
		array(
			'nntm-tokens',
			'nntm-base',
			'nntm-layout',
			'nntm-header',
			'nntm-footer',
			'nntm-feature-style',
			'nntm-tru-xu-list-style',
		),
		nntm_asset_version( $css_path )
	);
}
add_action( 'wp_enqueue_scripts', 'nntm_enqueue_dieu_thuong_figma_assets', 41 );

function nntm_enqueue_lien_dan_figma_assets(): void {
	if ( ! is_page( 'lien-dan' ) ) {
		return;
	}

	$css_path = NNTM_THEME_DIR . '/assets/css/pages/lien-dan-figma.css';
	wp_enqueue_style(
		'nntm-lien-dan-figma',
		NNTM_THEME_URI . '/assets/css/pages/lien-dan-figma.css',
		array( 'nntm-tokens', 'nntm-base', 'nntm-layout', 'nntm-header', 'nntm-footer' ),
		nntm_asset_version( $css_path )
	);
}
add_action( 'wp_enqueue_scripts', 'nntm_enqueue_lien_dan_figma_assets', 60 );

function nntm_enqueue_an_pham_kho_assets(): void {
	if ( ! is_post_type_archive( 'nntm_publication' ) ) {
		return;
	}

	$css_path = NNTM_THEME_DIR . '/assets/css/pages/an-pham-kho.css';
	wp_enqueue_style(
		'nntm-an-pham-kho',
		NNTM_THEME_URI . '/assets/css/pages/an-pham-kho.css',
		array( 'nntm-tokens', 'nntm-base', 'nntm-layout', 'nntm-header', 'nntm-footer' ),
		nntm_asset_version( $css_path )
	);
}
add_action( 'wp_enqueue_scripts', 'nntm_enqueue_an_pham_kho_assets', 62 );

function nntm_enqueue_hoa_khai_figma_assets(): void {
	if ( ! is_page( 'hoa-khai' ) ) {
		return;
	}

	$css_path = NNTM_THEME_DIR . '/assets/css/pages/hoa-khai-figma.css';
	wp_enqueue_style(
		'nntm-hoa-khai-figma',
		NNTM_THEME_URI . '/assets/css/pages/hoa-khai-figma.css',
		array( 'nntm-tokens', 'nntm-base', 'nntm-layout', 'nntm-header', 'nntm-footer', 'nntm-article-mosaic-style', 'nntm-card-list-style' ),
		nntm_asset_version( $css_path )
	);
}
add_action( 'wp_enqueue_scripts', 'nntm_enqueue_hoa_khai_figma_assets', 61 );

 
function nntm_enqueue_vuon_xoai_figma_assets(): void {
	if ( ! is_page( 'vuon-xoai' ) ) { return; }
	$css_path = NNTM_THEME_DIR . '/assets/css/pages/vuon-xoai-figma.css';
	wp_enqueue_style( 'nntm-vuon-xoai-figma', NNTM_THEME_URI . '/assets/css/pages/vuon-xoai-figma.css', array( 'nntm-tokens', 'nntm-base', 'nntm-layout', 'nntm-header', 'nntm-footer', 'nntm-card-list-style', 'nntm-article-mosaic-style' ), nntm_asset_version( $css_path ) );
}
add_action( 'wp_enqueue_scripts', 'nntm_enqueue_vuon_xoai_figma_assets', 62 );

function nntm_enqueue_nhap_phap_gioi_figma_assets(): void {
	if ( ! is_page( 'nhap-phap-gioi' ) ) {
		return;
	}

	$css_path = NNTM_THEME_DIR . '/assets/css/pages/nhap-phap-gioi-figma.css';
	wp_enqueue_style(
		'nntm-nhap-phap-gioi-figma',
		NNTM_THEME_URI . '/assets/css/pages/nhap-phap-gioi-figma.css',
		array(
			'nntm-tokens',
			'nntm-base',
			'nntm-layout',
			'nntm-header',
			'nntm-footer',
			'nntm-rank-card-style',
			'nntm-card-list-style',
		),
		nntm_asset_version( $css_path )
	);
}
add_action( 'wp_enqueue_scripts', 'nntm_enqueue_nhap_phap_gioi_figma_assets', 63 );

 
function nntm_enqueue_kim_cuong_hanh_gia_figma_assets(): void {
	if ( ! is_page( 'kim-cuong-hanh-gia' ) ) {
		return;
	}

	$css_path = NNTM_THEME_DIR . '/assets/css/pages/kim-cuong-hanh-gia-figma.css';
	wp_enqueue_style(
		'nntm-kim-cuong-hanh-gia-figma',
		NNTM_THEME_URI . '/assets/css/pages/kim-cuong-hanh-gia-figma.css',
		array(
			'nntm-tokens',
			'nntm-base',
			'nntm-layout',
			'nntm-header',
			'nntm-footer',
			'nntm-banner-style',
			'nntm-card-list-style',
			'nntm-cong-tu-style',
		),
		nntm_asset_version( $css_path )
	);
}
add_action( 'wp_enqueue_scripts', 'nntm_enqueue_kim_cuong_hanh_gia_figma_assets', 64 );
