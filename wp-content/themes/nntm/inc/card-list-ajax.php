<?php

defined( 'ABSPATH' ) || exit;

const NNTM_CARD_LIST_AJAX_ARG = 'nntm_cardlist_ajax';

function nntm_card_list_tim_khoi_phan_trang( array $blocks ): ?array {
	foreach ( $blocks as $block ) {
		if ( ! is_array( $block ) ) {
			continue;
		}

		$attrs = isset( $block['attrs'] ) && is_array( $block['attrs'] ) ? $block['attrs'] : array();

		if (
			isset( $block['blockName'] ) &&
			'nntm/card-list' === $block['blockName'] &&
			! empty( $attrs['showPaging'] )
		) {
			return $block;
		}

		if ( ! empty( $block['innerBlocks'] ) && is_array( $block['innerBlocks'] ) ) {
			$tim_duoc = nntm_card_list_tim_khoi_phan_trang( $block['innerBlocks'] );
			if ( null !== $tim_duoc ) {
				return $tim_duoc;
			}
		}
	}

	return null;
}

function nntm_card_list_bo_tham_so_ajax( string $link ): string {
	return (string) remove_query_arg( NNTM_CARD_LIST_AJAX_ARG, $link );
}

function nntm_card_list_tra_ve_khoi_json(): void {
	if ( is_admin() || wp_doing_ajax() ) {
		return;
	}

	if ( empty( $_GET[ NNTM_CARD_LIST_AJAX_ARG ] ) ) {  
		return;
	}

	if ( ! isset( $_SERVER['REQUEST_METHOD'] ) || 'GET' !== strtoupper( (string) $_SERVER['REQUEST_METHOD'] ) ) {
		return;
	}

	nocache_headers();

	$post = get_queried_object();

	if ( ! is_singular() || ! $post instanceof WP_Post ) {
		wp_send_json_error( array( 'message' => __( 'Trang này không có khối danh sách phân trang.', 'nntm' ) ), 400 );
	}

	if ( post_password_required( $post ) ) {
		wp_send_json_error( array( 'message' => __( 'Nội dung được bảo vệ bằng mật khẩu.', 'nntm' ) ), 403 );
	}

	$khoi = nntm_card_list_tim_khoi_phan_trang( parse_blocks( $post->post_content ) );

	if ( null === $khoi ) {
		wp_send_json_error( array( 'message' => __( 'Trang này không có khối danh sách phân trang.', 'nntm' ) ), 404 );
	}

	add_filter( 'get_pagenum_link', 'nntm_card_list_bo_tham_so_ajax' );
	$html = render_block( $khoi );
	remove_filter( 'get_pagenum_link', 'nntm_card_list_bo_tham_so_ajax' );

	if ( '' === trim( (string) $html ) ) {
		wp_send_json_error( array( 'message' => __( 'Không dựng được danh sách lúc này.', 'nntm' ) ), 500 );
	}

	wp_send_json_success(
		array(
			'html'  => $html,
			'paged' => max( 1, (int) get_query_var( 'paged' ) ),
		)
	);
}
add_action( 'template_redirect', 'nntm_card_list_tra_ve_khoi_json', 20 );
