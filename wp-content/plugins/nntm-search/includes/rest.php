<?php
/**
 * REST API: /wp-json/nntm-search/v1/…
 *
 * The namespace follows the project prefix convention (docs/04-kien-truc.md
 * section 9). A namespace is a public contract — renaming it later breaks
 * caches and every caller.
 *
 * @package NNTM_Search
 */

defined( 'ABSPATH' ) || exit;

add_action( 'rest_api_init', 'nntm_search_register_routes' );

/**
 * Register the read endpoints.
 */
function nntm_search_register_routes(): void {
	register_rest_route(
		NNTM_SEARCH_NS,
		'/suggest',
		array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => 'nntm_search_handle_suggest',
			'permission_callback' => 'nntm_search_permission_read',
			'args'                => array(
				'q'     => array(
					'required'          => true,
					'type'              => 'string',
					'sanitize_callback' => 'sanitize_text_field',
					'validate_callback' => static fn( $value ): bool => is_string( $value ) && mb_strlen( $value ) <= 100,
				),
				'group' => array(
					'type'    => 'string',
					'enum'    => array( 'all', 'article', 'pdf' ),
					'default' => 'all',
				),
			),
		)
	);
}

/**
 * Gate for read-only endpoints.
 *
 * No nonce here on purpose: for a logged-out visitor a WordPress nonce is not
 * tied to any session, so requiring it only creates a false sense of safety.
 * This endpoint writes nothing and accepts no permission parameter, so the CSRF
 * risk is zero. What needs defending against is abuse — hence the quota.
 *
 * @return true|WP_Error
 */
function nntm_search_permission_read() {
	if ( ! nntm_search_rate_allow( 'suggest', 30, MINUTE_IN_SECONDS ) ) {
		return new WP_Error(
			'nntm_rate_limited',
			__( 'Bạn tìm hơi nhanh, thử lại sau một chút.', 'nntm' ),
			array( 'status' => 429 )
		);
	}

	return true;
}

/**
 * Instant suggestions for the header dropdown.
 *
 * @param WP_REST_Request $request Request.
 * @return WP_REST_Response
 */
function nntm_search_handle_suggest( WP_REST_Request $request ): WP_REST_Response {
	$query = trim( (string) $request->get_param( 'q' ) );
	$group = (string) $request->get_param( 'group' );

	/*
	 * Permission is NOT read from $request. The engine asks
	 * nntm_search_viewer_acl() about the server-side session. If anyone ever adds
	 * an `acl` parameter here, that is the hole — see includes/acl.php.
	 */
	$found = nntm_search_query( $query, $group, 1, 6, false );

	$allowed_tags = array( 'mark' => array() );
	$results      = array();

	foreach ( $found['rows'] as $row ) {
		$results[] = array(
			'title'     => wp_kses( $row['title'], $allowed_tags ),
			'excerpt'   => wp_kses( $row['excerpt'], $allowed_tags ),
			'permalink' => esc_url_raw( $row['permalink'] ),
			'thumb'     => esc_url_raw( $row['thumb'] ),
			'label'     => $row['label'],
		);
	}

	return rest_ensure_response(
		array(
			'results' => $results,
			'total'   => (int) $found['total'],
			'see_all' => esc_url_raw(
				add_query_arg(
					array(
						's'     => $query,
						'group' => $group,
					),
					home_url( '/' )
				)
			),
		)
	);
}
