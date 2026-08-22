<?php
/**
 * Trang chi tiết ấn phẩm (CPT `nntm_publication`).
 *
 * @package NNTM
 */

defined( 'ABSPATH' ) || exit;

/**
 * Ấn phẩm này có bị khoá (yêu cầu thanh toán) không.
 * Trường `_nntm_pub_khoa` đặt qua meta box "Tệp PDF & Khoá xem", mặc định false.
 */
function nntm_an_pham_bi_khoa( $post = null ): bool {
	$post = get_post( $post );

	return $post ? (bool) get_post_meta( $post->ID, '_nntm_pub_khoa', true ) : false;
}

/**
 * Người đang xem đã thanh toán ấn phẩm khoá này chưa.
 * Chưa nối cổng thanh toán thật — luôn false, để filter riêng ghi đè sau khi
 * tính năng thanh toán được chốt (xem docs/07-ban-giao.md mục "Chờ khách trả lời").
 */
function nntm_an_pham_da_thanh_toan( $post = null ): bool {
	$post = get_post( $post );

	return (bool) apply_filters( 'nntm_an_pham_da_thanh_toan', false, $post, get_current_user_id() );
}

/**
 * Người đang xem có được đọc ấn phẩm này hay không.
 * Ấn phẩm không khoá → luôn mở. Ấn phẩm khoá → phải thanh toán trước.
 * Giữ filter cũ `nntm_an_pham_can_access` để nơi khác vẫn ghi đè được.
 */
function nntm_an_pham_can_access( $post = null ): bool {
	$post = get_post( $post );
	$mo   = ! nntm_an_pham_bi_khoa( $post ) || nntm_an_pham_da_thanh_toan( $post );

	return (bool) apply_filters( 'nntm_an_pham_can_access', $mo, $post, get_current_user_id() );
}

/**
 * URL công khai của tệp PDF gắn với ấn phẩm, rỗng nếu chưa gắn tệp.
 */
function nntm_an_pham_pdf_url( $post = null ): string {
	$post   = get_post( $post );
	$att_id = $post ? absint( get_post_meta( $post->ID, '_nntm_pdf_file', true ) ) : 0;

	if ( ! $att_id ) {
		return '';
	}

	$url = wp_get_attachment_url( $att_id );

	return $url ? $url : '';
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

/** Số bìa sách mỗi trang ở kho ấn phẩm — 12 = 3 hàng đủ của lưới 4 cột. */
const NNTM_AN_PHAM_MOI_TRANG = 12;

/**
 * Kho ấn phẩm /an-pham/ — 12 bìa mỗi trang, mới nhất trước.
 *
 * Đặt ở pre_get_posts chứ không dùng WP_Query riêng trong template: có vậy
 * `paginate_links()` và `$wp_query->max_num_pages` mới tính đúng, và /page/2/
 * mới không trả 404.
 */
function nntm_an_pham_archive_query( WP_Query $query ): void {
	if ( is_admin() || ! $query->is_main_query() || ! $query->is_post_type_archive( 'nntm_publication' ) ) {
		return;
	}

	$query->set( 'posts_per_page', NNTM_AN_PHAM_MOI_TRANG );
	$query->set( 'orderby', 'date' );
	$query->set( 'order', 'DESC' );
}
add_action( 'pre_get_posts', 'nntm_an_pham_archive_query' );
