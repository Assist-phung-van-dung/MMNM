<?php
/**
 * Front-end assets for the header search bar.
 *
 * @package NNTM_Search
 */

defined( 'ABSPATH' ) || exit;

add_action( 'wp_enqueue_scripts', 'nntm_search_enqueue_assets' );

/**
 * Load the dropdown script and styles. Runs on every page, because the search
 * bar lives in the header.
 */
function nntm_search_enqueue_assets(): void {
	$script = NNTM_SEARCH_DIR . '/assets/js/search-bar.js';
	$style  = NNTM_SEARCH_DIR . '/assets/css/search-bar.css';

	wp_enqueue_style(
		'nntm-search-bar',
		NNTM_SEARCH_URI . 'assets/css/search-bar.css',
		array(),
		(string) ( file_exists( $style ) ? filemtime( $style ) : NNTM_SEARCH_VERSION )
	);

	wp_enqueue_script(
		'nntm-search-bar',
		NNTM_SEARCH_URI . 'assets/js/search-bar.js',
		array(),
		(string) ( file_exists( $script ) ? filemtime( $script ) : NNTM_SEARCH_VERSION ),
		true
	);

	wp_localize_script(
		'nntm-search-bar',
		'nntmSearch',
		array(
			'root'  => esc_url_raw( rest_url( NNTM_SEARCH_NS . '/' ) ),
			'nonce' => wp_create_nonce( 'wp_rest' ),
			'i18n'  => array(
				'searching'    => __( 'Đang tìm…', 'nntm' ),
				'readingImage' => __( 'Đang xem ảnh…', 'nntm' ),
				'noResults'    => __( 'Không tìm thấy nội dung nào.', 'nntm' ),
				'noImageMatch' => __( 'Không thấy nội dung nào hợp với ảnh này.', 'nntm' ),
				'tooFast'      => __( 'Bạn tìm hơi nhanh, thử lại sau một chút.', 'nntm' ),
				'imageTooBig'  => __( 'Ảnh quá lớn, tối đa 5MB.', 'nntm' ),
				'imageBadType' => __( 'Chỉ nhận ảnh JPG, PNG, WEBP hoặc GIF.', 'nntm' ),
				'failed'       => __( 'Tìm kiếm tạm thời không dùng được.', 'nntm' ),
				'seeAll'       => __( 'Xem tất cả kết quả', 'nntm' ),
				'imageReads'   => __( 'Ảnh này có:', 'nntm' ),
				'noTextMatch'  => __( 'Đọc được từ khoá nhưng không bài nào nhắc tới — hiện nội dung có ảnh trông giống nhất:', 'nntm' ),
				'noKeyword'    => __( 'Không đọc được từ khoá nào — hiện nội dung có ảnh trông giống nhất:', 'nntm' ),
			),
		)
	);
}
