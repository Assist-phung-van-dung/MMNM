<?php

defined( 'ABSPATH' ) || exit;

 
function nntm_bai_thuoc_hanh_gia( ?WP_Post $post = null ): ?string {
	$post = get_post( $post );

	if ( ! $post instanceof WP_Post || 'nntm_article' !== $post->post_type ) {
		return null;
	}

	if ( ! taxonomy_exists( 'nntm_section' ) ) {
		return null;
	}

	$terms = get_the_terms( $post, 'nntm_section' );

	if ( ! is_array( $terms ) || empty( $terms ) ) {
		return null;
	}

	 
	 
	$slug_theo_cap = apply_filters(
		'nntm_hanh_gia_slug_theo_cap',
		array(
			'dai-si-hanh-gia'    => 'dai_si',
			'kim-cuong-hanh-gia' => 'kim_cuong',
			 
			'nhap-phap-gioi'     => 'chung',
		)
	);

	foreach ( $terms as $term ) {
		if ( $term instanceof WP_Term && isset( $slug_theo_cap[ $term->slug ] ) ) {
			return $slug_theo_cap[ $term->slug ];
		}
	}

	return null;
}

function nntm_hanh_gia_body_class( array $classes ): array {
	if ( ! is_singular( 'nntm_article' ) ) {
		return $classes;
	}

	$cap = nntm_bai_thuoc_hanh_gia( get_queried_object() );

	if ( null === $cap ) {
		return $classes;
	}

	$classes[] = 'is-bai-hanh-gia';

	if ( 'kim_cuong' === $cap ) {
		$classes[] = 'is-bai-kim-cuong';
	} elseif ( 'dai_si' === $cap ) {
		$classes[] = 'is-bai-dai-si';
	}

	return $classes;
}
add_filter( 'body_class', 'nntm_hanh_gia_body_class' );

 
function nntm_hanh_gia_chan_quyen(): void {
	if ( ! is_singular( 'nntm_article' ) ) {
		return;
	}

	$post = get_queried_object();
	$cap  = nntm_bai_thuoc_hanh_gia( $post instanceof WP_Post ? $post : null );

	 
	if ( 'kim_cuong' !== $cap ) {
		return;
	}

	if ( current_user_can( 'manage_options' ) ) {
		return;
	}

	$duoc_vao = is_user_logged_in();
	$duoc_vao = (bool) apply_filters( 'nntm_bai_hanh_gia_can_access', $duoc_vao, $post, get_current_user_id() );

	if ( $duoc_vao ) {
		return;
	}

	$url_hien_tai = get_permalink( $post );
	$url_dang_nhap = function_exists( 'nntm_login_url' )
		? nntm_login_url( $url_hien_tai ? $url_hien_tai : '' )
		: wp_login_url( $url_hien_tai ? $url_hien_tai : '' );

	wp_safe_redirect( $url_dang_nhap );
	exit;
}
add_action( 'template_redirect', 'nntm_hanh_gia_chan_quyen' );

 
function nntm_trang_can_dang_nhap(): array {
	return (array) apply_filters(
		'nntm_trang_can_dang_nhap',
		array( 'kim-cuong-hanh-gia' )
	);
}

 
function nntm_term_khu_han_che(): array {
	return (array) apply_filters(
		'nntm_term_khu_han_che',

		array( 'kim-cuong-hanh-gia' )
	);
}

function nntm_duoc_xem_khu_han_che(): bool {
	$duoc = is_user_logged_in();

	if ( current_user_can( 'manage_options' ) ) {
		$duoc = true;
	}

	if ( 'cli' === PHP_SAPI ) {
		$duoc = true;
	}

	return (bool) apply_filters( 'nntm_duoc_xem_khu_han_che', $duoc, get_current_user_id() );
}

function nntm_hanh_gia_chan_trang_danh_sach(): void {
	if ( nntm_duoc_xem_khu_han_che() ) {
		return;
	}

	$can_chan = false;

	if ( is_page( nntm_trang_can_dang_nhap() ) ) {
		$can_chan = true;
	}

	 
	if ( is_tax( 'nntm_section', nntm_term_khu_han_che() ) ) {
		$can_chan = true;
	}

	if ( ! $can_chan ) {
		return;
	}

	$url_hien_tai = home_url( add_query_arg( array() ) );

	$url_dang_nhap = function_exists( 'nntm_login_url' )
		? nntm_login_url( $url_hien_tai )
		: wp_login_url( $url_hien_tai );

	wp_safe_redirect( $url_dang_nhap );
	exit;
}
add_action( 'template_redirect', 'nntm_hanh_gia_chan_trang_danh_sach' );

function nntm_hanh_gia_loai_khoi_truy_van( WP_Query $query ): void {
	 
	if ( is_admin() ) {
		return;
	}

	if ( nntm_duoc_xem_khu_han_che() ) {
		return;
	}

	$post_type = $query->get( 'post_type' );

	$co_the_cham = false;
	if ( empty( $post_type ) || 'any' === $post_type ) {
		$co_the_cham = true;  
	} elseif ( is_array( $post_type ) ) {
		$co_the_cham = in_array( 'nntm_article', $post_type, true );
	} else {
		$co_the_cham = ( 'nntm_article' === $post_type );
	}

	if ( ! $co_the_cham ) {
		return;
	}

	$slugs = nntm_term_khu_han_che();
	if ( empty( $slugs ) ) {
		return;
	}

	$dieu_kien = array(
		'taxonomy'         => 'nntm_section',
		'field'            => 'slug',
		'terms'            => $slugs,
		'operator'         => 'NOT IN',
		'include_children' => false,
	);

	$tax_query = $query->get( 'tax_query' );

	if ( empty( $tax_query ) || ! is_array( $tax_query ) ) {
		$tax_query = array( $dieu_kien );
	} else {

		$tax_query['relation'] = 'AND';
		$tax_query[]           = $dieu_kien;
	}

	$query->set( 'tax_query', $tax_query );
}
add_action( 'pre_get_posts', 'nntm_hanh_gia_loai_khoi_truy_van' );

function nntm_nhap_phap_gioi_rank_card_access( bool $can_access, array $card ): bool {
	$target_url = isset( $card['targetUrl'] ) ? (string) $card['targetUrl'] : '';
	$path       = $target_url ? (string) wp_parse_url( $target_url, PHP_URL_PATH ) : '';
	$slug       = $path ? sanitize_title( basename( untrailingslashit( $path ) ) ) : '';

	if ( 'dai-si-hanh-gia' === $slug ) {
		return true;
	}

	if ( 'kim-cuong-hanh-gia' === $slug ) {
		return current_user_can( 'manage_options' ) || is_user_logged_in();
	}

	return $can_access;
}
add_filter( 'nntm_rank_card_can_access', 'nntm_nhap_phap_gioi_rank_card_access', 10, 2 );

function nntm_nhap_phap_gioi_enqueue_assets(): void {
	if ( ! is_page( 'nhap-phap-gioi' ) ) {
		return;
	}

	$css_path = NNTM_THEME_DIR . '/assets/css/pages/nhap-phap-gioi.css';
	wp_enqueue_style(
		'nntm-nhap-phap-gioi',
		NNTM_THEME_URI . '/assets/css/pages/nhap-phap-gioi.css',
		array( 'nntm-tokens', 'nntm-base', 'nntm-layout' ),
		nntm_asset_version( $css_path )
	);
}
add_action( 'wp_enqueue_scripts', 'nntm_nhap_phap_gioi_enqueue_assets', 30 );

 
function nntm_hanh_gia_enqueue_assets(): void {
	if ( ! is_singular( 'nntm_article' ) ) {
		return;
	}

	if ( null === nntm_bai_thuoc_hanh_gia( get_queried_object() ) ) {
		return;
	}

	$css_path = NNTM_THEME_DIR . '/assets/css/pages/bai-hanh-gia.css';
	wp_enqueue_style(
		'nntm-bai-hanh-gia',
		NNTM_THEME_URI . '/assets/css/pages/bai-hanh-gia.css',
		array( 'nntm-tokens', 'nntm-base' ),
		nntm_asset_version( $css_path )
	);
}
add_action( 'wp_enqueue_scripts', 'nntm_hanh_gia_enqueue_assets' );
