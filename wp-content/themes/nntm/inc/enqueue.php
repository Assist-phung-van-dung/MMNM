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

/*
 * CSS cho trình soạn thảo đã chuyển sang inc/editor-parity.php.
 *
 * Ở đây trước kia chỉ khai báo tokens.css và base.css — thiếu layout.css nên
 * .nntm-container không tồn tại trong admin và mọi block bị vỡ khung so với
 * ngoài trang. Danh sách đầy đủ, cùng với CSS riêng theo từng trang, nay nằm
 * chung một chỗ trong nntm_editor_parity_css_chung() và
 * nntm_editor_parity_ban_do().
 */

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

/**
 * Trang đang xem có Card List nào dùng băng chạy YouTube không?
 *
 * Phải soi vào attribute chứ không dùng has_block() được: has_block() chỉ biết
 * trang CÓ block nntm/card-list, trong khi 9 trang đều có — thứ cần biết là
 * trong đó có cái nào đặt videoSource = youtube hay không.
 */
function nntm_card_list_co_bang_youtube( array $blocks ): bool {
	foreach ( $blocks as $block ) {
		if ( ! is_array( $block ) ) {
			continue;
		}

		$attrs = ( isset( $block['attrs'] ) && is_array( $block['attrs'] ) ) ? $block['attrs'] : array();

		if (
			isset( $block['blockName'] ) &&
			'nntm/card-list' === $block['blockName'] &&
			'youtube' === ( $attrs['videoSource'] ?? '' )
		) {
			return true;
		}

		if ( ! empty( $block['innerBlocks'] ) && is_array( $block['innerBlocks'] ) && nntm_card_list_co_bang_youtube( $block['innerBlocks'] ) ) {
			return true;
		}
	}

	return false;
}

/**
 * CSS của băng chạy YouTube — chỉ nạp đúng trang cần.
 *
 * Trước đây khối CSS này nằm chung trong blocks/card-list/style.css, mà tệp đó
 * nạp trọn gói trên MỌI trang có Card List. Đo thật: 9 trang dùng Card List,
 * chỉ 2 trang dùng băng chạy YouTube — 7 trang còn lại tải về gần 12KB CSS
 * không chạm tới một dòng nào.
 *
 * Nạp ở wp_enqueue_scripts (không phải lúc render) để thẻ <link> nằm trong
 * <head>: nạp muộn thì băng video sẽ loé lên một nhịp chưa có định dạng.
 */
function nntm_enqueue_card_list_youtube_style(): void {
	if ( ! is_singular() ) {
		return;
	}

	$post = get_queried_object();

	if ( ! $post instanceof WP_Post || ! has_block( 'nntm/card-list', $post ) ) {
		return;
	}

	if ( ! nntm_card_list_co_bang_youtube( parse_blocks( $post->post_content ) ) ) {
		return;
	}

	$css_path = NNTM_THEME_DIR . '/blocks/card-list/style-youtube.css';

	if ( ! is_file( $css_path ) ) {
		return;
	}

	wp_enqueue_style(
		'nntm-card-list-youtube',
		NNTM_THEME_URI . '/blocks/card-list/style-youtube.css',
		/*
		 * Phụ thuộc vào CSS gốc của block: các biến --nntm-cl-yt-* được khai báo
		 * ở đó trên một selector dùng chung, băng YouTube chỉ đọc lại.
		 */
		array( 'nntm-card-list-style' ),
		nntm_asset_version( $css_path )
	);
}
add_action( 'wp_enqueue_scripts', 'nntm_enqueue_card_list_youtube_style', 30 );

/**
 * CSS của hai dải "Khoá tu" / "Lịch tu" — dùng chung cho Liên Đàn và Vườn Xoài.
 *
 * Trước đây nằm trong blocks/card-list/style.css, tức CSS của chính block. Đó là
 * chỗ sai: block dùng chung cho 9 trang, không nên biết tên một trang cụ thể nào.
 * Hai class .nntm-lien-dan-khoa / .nntm-lien-dan-lich là do quản trị tự đặt ở ô
 * "class bổ sung", và chỉ hai trang này dùng tới.
 *
 * Ưu tiên 50: chạy SAU khi block đăng ký style (phụ thuộc nntm-card-list-style)
 * và TRƯỚC hai tệp riêng của trang (ưu tiên 60 và 62) — giữ đúng thứ tự tầng như
 * lúc còn nằm chung, để không quy tắc nào đổi bên thắng thua.
 */
function nntm_enqueue_khoa_lich_style(): void {
	if ( ! is_page( array( 'lien-dan', 'vuon-xoai' ) ) ) {
		return;
	}

	$css_path = NNTM_THEME_DIR . '/assets/css/pages/lien-dan-khoa-lich.css';

	if ( ! is_file( $css_path ) ) {
		return;
	}

	wp_enqueue_style(
		'nntm-khoa-lich',
		NNTM_THEME_URI . '/assets/css/pages/lien-dan-khoa-lich.css',
		array( 'nntm-tokens', 'nntm-base', 'nntm-layout', 'nntm-card-list-style' ),
		nntm_asset_version( $css_path )
	);
}
add_action( 'wp_enqueue_scripts', 'nntm_enqueue_khoa_lich_style', 50 );
