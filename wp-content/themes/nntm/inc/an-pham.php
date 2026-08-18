<?php
/**
 * Trang chi tiết ấn phẩm (CPT `nntm_publication`).
 *
 * @package NNTM
 */

defined( 'ABSPATH' ) || exit;

/**
 * Người đang xem có được đọc ấn phẩm này hay không.
 * Mặc định mở; giữ filter cũ để không đổi nghiệp vụ dự án.
 */
function nntm_an_pham_can_access( ?WP_Post $post = null ): bool {
	$post = get_post( $post );

	return (bool) apply_filters( 'nntm_an_pham_can_access', true, $post, get_current_user_id() );
}

/**
 * Nạp CSS/JS riêng cho single nntm_publication.
 */
function nntm_an_pham_enqueue_assets(): void {
	if ( ! is_singular( 'nntm_publication' ) ) {
		return;
	}

	$css_path = NNTM_THEME_DIR . '/assets/css/pages/an-pham.css';
	wp_enqueue_style(
		'nntm-an-pham',
		NNTM_THEME_URI . '/assets/css/pages/an-pham.css',
		array( 'nntm-tokens', 'nntm-base' ),
		nntm_asset_version( $css_path )
	);

	$js_path = NNTM_THEME_DIR . '/assets/js/an-pham.js';
	if ( is_readable( $js_path ) ) {
		wp_enqueue_script(
			'nntm-an-pham',
			NNTM_THEME_URI . '/assets/js/an-pham.js',
			array(),
			nntm_asset_version( $js_path ),
			true
		);
	}

	/*
	 * Nếu site đang có patch Yêu thích dùng chung thì publication cũng phải
	 * nạp asset của patch đó. Điều kiện file_exists giữ compatibility với
	 * source cũ chưa cài patch favorites.
	 */
	$favorites_css = NNTM_THEME_DIR . '/assets/css/favorites.css';
	$favorites_js  = NNTM_THEME_DIR . '/assets/js/favorites.js';
	if ( function_exists( 'nntm_section_render_favorite_button' ) && is_readable( $favorites_css ) && is_readable( $favorites_js ) ) {
		wp_enqueue_style(
			'nntm-favorites',
			NNTM_THEME_URI . '/assets/css/favorites.css',
			array( 'nntm-tokens', 'nntm-base' ),
			nntm_asset_version( $favorites_css )
		);

		wp_enqueue_script(
			'nntm-favorites',
			NNTM_THEME_URI . '/assets/js/favorites.js',
			array(),
			nntm_asset_version( $favorites_js ),
			true
		);

		wp_localize_script(
			'nntm-favorites',
			'nntmFavorites',
			array(
				'ajaxUrl'      => admin_url( 'admin-ajax.php' ),
				'nonce'        => wp_create_nonce( 'nntm_favorite_toggle' ),
				'isLoggedIn'   => is_user_logged_in(),
				'activeText'   => __( 'Đã yêu thích', 'nntm' ),
				'inactiveText' => __( 'Chưa yêu thích', 'nntm' ),
				'errorText'    => __( 'Không thể cập nhật Yêu thích. Vui lòng thử lại.', 'nntm' ),
			)
		);
	}
}
add_action( 'wp_enqueue_scripts', 'nntm_an_pham_enqueue_assets', 30 );
