<?php
/**
 * Nạp CSS/JS và font cho theme.
 *
 * Thứ tự phụ thuộc bắt buộc: tokens.css → tokens.generated.css (nếu có)
 * → base.css / layout.css. Mọi file sau đều khai `deps` trỏ về tokens
 * để không bao giờ bị nạp trước tokens.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Preconnect tới Google Fonts để giảm độ trễ tải font.
 *
 * @param array  $urls          Danh sách URL preconnect hiện có.
 * @param string $relation_type Loại resource hint đang xử lý.
 * @return array
 */
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

/**
 * URL Google Fonts dùng chung cho theme và trình soạn thảo.
 *
 * QUAN TRỌNG: bản thiết kế Figma dùng Battambang / Baskerville /
 * Google Sans Flex / Century Gothic. Cả bốn bộ chữ này KHÔNG có bộ
 * chữ tiếng Việt đầy đủ hoặc phải mua giấy phép thương mại, nên đã
 * thay bằng bộ tương đương bên dưới (xem docs/05-font-thay-the.md).
 * KHÔNG đổi lại danh sách này cho "giống Figma" — đó là quyết định
 * đã chốt, không phải thiếu sót.
 *   Battambang        → Be Vietnam Pro
 *   Baskerville       → Lora
 *   Google Sans Flex  → Inter
 *   Century Gothic    → Questrial
 *   (EB Garamond giữ nguyên như Figma)
 *
 * @return string
 */
function nntm_google_fonts_url(): string {
	$families = array(
		'Be Vietnam Pro:wght@400;500;700',
		'Lora:wght@400;500',
		'EB Garamond:wght@400;600',
		'Inter:wght@400;500',
		'Questrial:wght@400',
	);

	// Không dùng add_query_arg() ở đây: Google Fonts cần nhiều tham số
	// `family` lặp lại (?family=A&family=B...), còn add_query_arg() chỉ
	// nhận một khóa duy nhất và sẽ mã hóa đè lên dấu `&` thủ công của
	// mình, làm hỏng URL. Tự ráp chuỗi query để giữ đúng định dạng.
	$query = 'family=' . implode( '&family=', array_map( 'rawurlencode', $families ) ) . '&display=swap';

	return 'https://fonts.googleapis.com/css2?' . $query;
}

/**
 * Nạp CSS/JS cho trang xem (front-end).
 */
function nntm_enqueue_assets(): void {
	// Font Google — một lệnh gọi duy nhất cho toàn bộ họ chữ cần dùng.
	wp_enqueue_style( 'nntm-google-fonts', nntm_google_fonts_url(), array(), null );

	// Token màu/chữ/khoảng cách — PHẢI nạp trước mọi file khác.
	$tokens_path = NNTM_THEME_DIR . '/assets/css/tokens.css';
	wp_enqueue_style( 'nntm-tokens', NNTM_THEME_URI . '/assets/css/tokens.css', array(), nntm_asset_version( $tokens_path ) );

	// Token sinh tự động từ Figma — nạp thêm nếu tools/figma-sync.mjs đã chạy.
	$tokens_generated_path = NNTM_THEME_DIR . '/assets/css/tokens.generated.css';
	if ( file_exists( $tokens_generated_path ) ) {
		wp_enqueue_style( 'nntm-tokens-generated', NNTM_THEME_URI . '/assets/css/tokens.generated.css', array( 'nntm-tokens' ), nntm_asset_version( $tokens_generated_path ) );
	}

	$base_path = NNTM_THEME_DIR . '/assets/css/base.css';
	wp_enqueue_style( 'nntm-base', NNTM_THEME_URI . '/assets/css/base.css', array( 'nntm-tokens' ), nntm_asset_version( $base_path ) );

	$layout_path = NNTM_THEME_DIR . '/assets/css/layout.css';
	wp_enqueue_style( 'nntm-layout', NNTM_THEME_URI . '/assets/css/layout.css', array( 'nntm-tokens', 'nntm-base' ), nntm_asset_version( $layout_path ) );

	// Đầu trang — logo, menu chính, ngôn ngữ, tìm kiếm, tài khoản.
	$header_css_path = NNTM_THEME_DIR . '/assets/css/header.css';
	wp_enqueue_style( 'nntm-header', NNTM_THEME_URI . '/assets/css/header.css', array( 'nntm-tokens', 'nntm-base', 'nntm-layout' ), nntm_asset_version( $header_css_path ) );

	// Chân trang — cột liên kết, thông tin liên hệ, bản quyền.
	$footer_css_path = NNTM_THEME_DIR . '/assets/css/footer.css';
	wp_enqueue_style( 'nntm-footer', NNTM_THEME_URI . '/assets/css/footer.css', array( 'nntm-tokens', 'nntm-base', 'nntm-layout' ), nntm_asset_version( $footer_css_path ) );

	// Menu di động (hamburger), bẫy tiêu điểm, header dính khi cuộn.
	$header_js_path = NNTM_THEME_DIR . '/assets/js/header.js';
	wp_enqueue_script( 'nntm-header', NNTM_THEME_URI . '/assets/js/header.js', array(), nntm_asset_version( $header_js_path ), true );

	/*
	 * H1 (12/08/2026): đầu trang đổi màu theo cuộn. Tách riêng khỏi
	 * header.js (script trên lo menu/tài khoản/dính khi cuộn — một việc
	 * khác) để sau này đổi ngưỡng 80px hay bỏ hẳn tính năng này không phải
	 * đụng vào các hành vi còn lại. Tự thoát sớm ở phía JS trên trang
	 * không có banner nên enqueue ở mọi trang cũng không tốn gì.
	 */
	$header_scroll_js_path = NNTM_THEME_DIR . '/assets/js/header-scroll.js';
	wp_enqueue_script( 'nntm-header-scroll', NNTM_THEME_URI . '/assets/js/header-scroll.js', array(), nntm_asset_version( $header_scroll_js_path ), true );
	wp_script_add_data( 'nntm-header-scroll', 'strategy', 'defer' );
}
add_action( 'wp_enqueue_scripts', 'nntm_enqueue_assets' );

/**
 * CSS/JS riêng cho trang dựng theo bản thiết kế R1 (page-r1.php).
 *
 * Chỉ nạp đúng trang có slug `r1` — mọi trang khác của site không tải
 * thêm byte nào. Bỏ bản R1 chỉ cần xoá trang đó, không phải sửa file này.
 */
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

	/*
	 * Trang R1 tự dựng đầu trang và chân trang riêng (template-parts/r1/)
	 * nên không cần CSS/JS của đầu-chân trang bản R3/R4 — gỡ ra để không
	 * có luật CSS nào của bản cũ đè lên.
	 */
	wp_dequeue_style( 'nntm-header' );
	wp_dequeue_style( 'nntm-footer' );
	wp_dequeue_script( 'nntm-header' );
	wp_dequeue_script( 'nntm-header-scroll' );
}
add_action( 'wp_enqueue_scripts', 'nntm_enqueue_r1_assets', 20 );

/**
 * CSS cho trình soạn thảo khối — dùng đúng token/base để khách thấy
 * trước đúng như trang thật khi sửa bằng block.
 */
function nntm_editor_assets(): void {
	add_editor_style(
		array(
			'assets/css/tokens.css',
			'assets/css/base.css',
		)
	);
}
add_action( 'after_setup_theme', 'nntm_editor_assets' );

/**
 * Lấy filemtime() làm số phiên bản, tránh dính cache cũ khi đang phát triển.
 * Trả về false (không ghi version) nếu file không tồn tại.
 *
 * @param string $absolute_path Đường dẫn tuyệt đối tới file asset.
 * @return int|false
 */
function nntm_asset_version( string $absolute_path ) {
	return file_exists( $absolute_path ) ? filemtime( $absolute_path ) : false;
}

/**
 * CSS riêng cho chi tiết nntm_article thường.
 * Đại Sĩ / Kim Cương vẫn dùng bai-hanh-gia.css; Nghi Quỹ là CPT khác.
 */
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

/**
 * Pixel-level homepage composition matched to Figma node 6376:6322.
 *
 * The homepage reuses generic blocks also used on archive/detail pages.
 * Keep the frame-specific geometry in one page-scoped stylesheet so fixes
 * here do not regress those shared block instances elsewhere.
 */
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

/**
 * Pixel-level Diệu Thượng composition matched to Figma node 6376:6694.
 * Keep page-specific geometry out of shared Feature/Trú Xứ/footer styles.
 */
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

/**
 * Pixel reconciliation for the Liên Đàn page (Figma frame 6376:6744).
 * Isolated to this slug so shared blocks keep their reusable defaults.
 */
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

/**
 * Pixel reconciliation for Hoa Khai (Figma frame 6376:6603).
 * Page-scoped so shared article-mosaic/card-list defaults remain reusable.
 */
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


/** Pixel reconciliation for Vườn Xoài (Figma frame 6386:5177). */
function nntm_enqueue_vuon_xoai_figma_assets(): void {
	if ( ! is_page( 'vuon-xoai' ) ) { return; }
	$css_path = NNTM_THEME_DIR . '/assets/css/pages/vuon-xoai-figma.css';
	wp_enqueue_style( 'nntm-vuon-xoai-figma', NNTM_THEME_URI . '/assets/css/pages/vuon-xoai-figma.css', array( 'nntm-tokens', 'nntm-base', 'nntm-layout', 'nntm-header', 'nntm-footer', 'nntm-card-list-style', 'nntm-article-mosaic-style' ), nntm_asset_version( $css_path ) );
	$js_path = NNTM_THEME_DIR . '/assets/js/vuon-xoai.js';
	wp_enqueue_script( 'nntm-vuon-xoai', NNTM_THEME_URI . '/assets/js/vuon-xoai.js', array(), nntm_asset_version( $js_path ), true );
}
add_action( 'wp_enqueue_scripts', 'nntm_enqueue_vuon_xoai_figma_assets', 62 );
