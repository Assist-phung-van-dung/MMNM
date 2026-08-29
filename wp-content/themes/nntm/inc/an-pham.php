<?php
/*
 * Ấn phẩm — phần HÌNH ẢNH.
 *
 * Bốn hàm nghiệp vụ (nntm_an_pham_bi_khoa / da_thanh_toan / can_access /
 * pdf_url) đã chuyển sang plugin nntm-library, xem
 * plugins/nntm-library/includes/quyen-truy-cap.php.
 *
 * VÌ SAO CHUYỂN: cổng quyền đọc và đường lấy tệp là nghiệp vụ, đổi theme không
 * được mất. docs/04-kien-truc.md mục 1 đã chốt như vậy từ 06/08/2026.
 *
 * Ở lại đây chỉ còn việc nạp CSS/JS và đặt số bài mỗi trang cho kho lưu trữ.
 */

defined( 'ABSPATH' ) || exit;

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

const NNTM_AN_PHAM_MOI_TRANG = 12;

function nntm_an_pham_archive_query( WP_Query $query ): void {
	if ( is_admin() || ! $query->is_main_query() || ! $query->is_post_type_archive( 'nntm_publication' ) ) {
		return;
	}

	$query->set( 'posts_per_page', NNTM_AN_PHAM_MOI_TRANG );
	$query->set( 'orderby', 'date' );
	$query->set( 'order', 'DESC' );
}
add_action( 'pre_get_posts', 'nntm_an_pham_archive_query' );
