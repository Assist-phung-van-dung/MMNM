<?php
/**
 * Search by image.
 *
 * HOW IT WORKS: the picture is described in words, then those words are
 * searched exactly as if the visitor had typed them. A photo of the sun yields
 * "mặt trời" and runs a normal text search. The detected words are returned so
 * the interface can show them — a search whose reasoning is visible is a search
 * people trust.
 *
 * If nothing is recognised confidently, it falls back to nearest-vector
 * matching, which still finds visually similar pictures.
 *
 * WHAT HAPPENS TO THE UPLOADED IMAGE: nothing is kept. No attachment is
 * created, nothing is copied into wp-content/uploads. Everything under uploads
 * has a public, guessable URL — parking someone's private photo there forever
 * would be a leak we created ourselves. The file is read straight from PHP's
 * temp directory (outside the web root) and PHP deletes it when the request
 * ends.
 *
 * @package NNTM_Search
 */

defined( 'ABSPATH' ) || exit;

const NNTM_SEARCH_IMAGE_MAX_BYTES = 5242880; // 5MB.
const NNTM_SEARCH_IMAGE_MIMES     = array( 'image/jpeg', 'image/png', 'image/webp', 'image/gif' );

add_action( 'rest_api_init', 'nntm_search_register_image_route' );

/**
 * Whether search-by-image is switched on for this environment.
 *
 * Off by config, not just by the Python service being down: an operator who
 * has deliberately not deployed tools/embed-service (e.g. staging, or a box
 * without the CLIP model) wants the upload button gone and the endpoint 404,
 * not a live button that always 503s.
 *
 *   define( 'NNTM_SEARCH_IMAGE_ENABLED', false );
 *
 * @return bool
 */
function nntm_search_image_enabled(): bool {
	return ! defined( 'NNTM_SEARCH_IMAGE_ENABLED' ) || (bool) NNTM_SEARCH_IMAGE_ENABLED;
}

/**
 * Register the image search endpoint.
 */
function nntm_search_register_image_route(): void {
	if ( ! nntm_search_image_enabled() ) {
		return;
	}

	register_rest_route(
		NNTM_SEARCH_NS,
		'/image',
		array(
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => 'nntm_search_handle_image',
			'permission_callback' => 'nntm_search_permission_image',
		)
	);
}

/**
 * Gate for the image endpoint.
 *
 * Two things differ from /suggest, both deliberately:
 *   - NONCE REQUIRED: this is a POST that uploads a file and burns real CPU.
 *     Without a nonce any page on the internet could auto-submit images here.
 *   - TIGHTER QUOTA (10 vs 30): each call costs tens of milliseconds of model
 *     time, far more expensive than one database query.
 *
 * @return true|WP_Error
 */
function nntm_search_permission_image() {
	$nonce = isset( $_SERVER['HTTP_X_WP_NONCE'] )
		? sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_WP_NONCE'] ) )
		: '';

	if ( ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
		return new WP_Error(
			'nntm_invalid_nonce',
			__( 'Phiên làm việc đã hết hạn, tải lại trang rồi thử lại.', 'nntm' ),
			array( 'status' => 403 )
		);
	}

	if ( ! nntm_search_rate_allow( 'image', 10, MINUTE_IN_SECONDS ) ) {
		return new WP_Error(
			'nntm_rate_limited',
			__( 'Bạn tìm hơi nhanh, thử lại sau một chút.', 'nntm' ),
			array( 'status' => 429 )
		);
	}

	return true;
}

/**
 * Accept an image, read keywords from it, search those keywords.
 *
 * @param WP_REST_Request $request Request.
 * @return WP_REST_Response|WP_Error
 */
function nntm_search_handle_image( WP_REST_Request $request ) {
	$started_at = microtime( true );
	$file       = $request->get_file_params()['anh'] ?? null;

	if ( ! is_array( $file ) || ! isset( $file['tmp_name'] ) || UPLOAD_ERR_OK !== ( $file['error'] ?? -1 ) ) {
		return new WP_Error( 'nntm_no_image', __( 'Chưa chọn ảnh.', 'nntm' ), array( 'status' => 400 ) );
	}

	// Blocks the trick of passing an arbitrary path as tmp_name to read system files.
	if ( ! is_uploaded_file( $file['tmp_name'] ) ) {
		return new WP_Error( 'nntm_bad_image', __( 'Ảnh không hợp lệ.', 'nntm' ), array( 'status' => 400 ) );
	}

	if ( (int) ( $file['size'] ?? 0 ) > NNTM_SEARCH_IMAGE_MAX_BYTES ) {
		return new WP_Error( 'nntm_image_too_big', __( 'Ảnh quá lớn, tối đa 5MB.', 'nntm' ), array( 'status' => 413 ) );
	}

	/*
	 * Check the type by ACTUAL CONTENT (finfo), never $file['type'] — that field
	 * is whatever the browser chose to send.
	 */
	$finfo = finfo_open( FILEINFO_MIME_TYPE );
	$mime  = $finfo ? finfo_file( $finfo, $file['tmp_name'] ) : '';

	if ( $finfo ) {
		finfo_close( $finfo );
	}

	if ( ! in_array( $mime, NNTM_SEARCH_IMAGE_MIMES, true ) ) {
		return new WP_Error(
			'nntm_image_wrong_type',
			__( 'Chỉ nhận ảnh JPG, PNG, WEBP hoặc GIF.', 'nntm' ),
			array( 'status' => 415 )
		);
	}

	$read = nntm_search_image_keywords( $file['tmp_name'] );

	if ( is_wp_error( $read ) ) {
		nntm_search_log_image_request( 'error', 0, $started_at );

		return new WP_Error( 'nntm_service_failed', $read->get_error_message(), array( 'status' => 503 ) );
	}

	$keywords = $read['keywords'];

	if ( ! empty( $keywords ) ) {
		$words = array_column( $keywords, 'word' );

		// Search the strongest keyword first. Searching all of them at once ANDs
		// the terms together and usually returns nothing: a page rarely contains
		// "mặt trời" and "sương mù" and "núi" all at once.
		$rows = array();

		foreach ( $words as $word ) {
			$found = nntm_search_query( $word, 'all', 1, 6, false );

			foreach ( $found['rows'] as $row ) {
				$rows[ $row['permalink'] ] = $row;
			}

			if ( count( $rows ) >= 6 ) {
				break;
			}
		}

		if ( ! empty( $rows ) ) {
			nntm_search_log_image_request( 'keyword', count( $rows ), $started_at );

			return rest_ensure_response(
				array(
					'keywords' => $keywords,
					'mode'     => 'keyword',
					'results'  => array_values( array_slice( $rows, 0, 6 ) ),
					'total'    => count( $rows ),
					'see_all'  => esc_url_raw( add_query_arg( 's', $words[0], home_url( '/' ) ) ),
				)
			);
		}
	}

	// Nothing matched the words — fall back to visual similarity.
	$vector = nntm_search_embed_image( $file['tmp_name'] );

	/*
	 * The Python service already succeeded once in this very request (the
	 * keyword read above). A failure here means the service went down or
	 * timed out in between — a real service failure, not "no similar image
	 * exists". Answering 200 with an empty list would be indistinguishable
	 * from a legitimate empty result, which is exactly what the audit flagged:
	 * a service failure must never look like "not found".
	 */
	if ( is_wp_error( $vector ) ) {
		nntm_search_log_image_request( 'error', 0, $started_at );

		return new WP_Error( 'nntm_service_failed', $vector->get_error_message(), array( 'status' => 503 ) );
	}

	$nearest = nntm_search_vector_search( $vector, nntm_search_viewer_acl(), 30 );

	nntm_search_log_image_request( 'similar', count( $nearest ), $started_at );

	return rest_ensure_response(
		array(
			'keywords' => $keywords,
			'mode'     => 'similar',
			'results'  => nntm_search_group_by_post( $nearest, 6 ),
			'total'    => count( $nearest ),
			'see_all'  => '',
		)
	);
}

/**
 * One structured log line per finished /image request.
 *
 * `request_id` matches the id attached to every Python call this request
 * made (see nntm_search_log_python_call() in includes/embed.php), so a log
 * reader can join the two. Deliberately NOT logged: the uploaded image,
 * base64, cookies, or any request/authorization header.
 *
 * @param string $mode         'keyword' | 'similar' | 'error'.
 * @param int    $result_count Number of rows returned.
 * @param float  $started_at   microtime(true) captured at the top of the handler.
 */
function nntm_search_log_image_request( string $mode, int $result_count, float $started_at ): void {
	// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- structured, no sensitive fields.
	error_log(
		'[nntm-search] ' . wp_json_encode(
			array(
				'request_id'   => nntm_search_request_id(),
				'route'        => '/image',
				'mode'         => $mode,
				'result_count' => $result_count,
				'total_ms'     => (int) round( ( microtime( true ) - $started_at ) * 1000 ),
			)
		)
	);
}

/**
 * Collapse image hits down to one row per post.
 *
 * A post with several images would otherwise fill the whole list on its own.
 * Keeps each post's best-scoring image.
 *
 * @param array $nearest Output of nntm_search_vector_search().
 * @param int   $limit   How many posts to return.
 * @return array
 */
function nntm_search_group_by_post( array $nearest, int $limit ): array {
	$by_post = array();

	foreach ( $nearest as $hit ) {
		$post_id = $hit['post_id'];

		if ( $post_id <= 0 || isset( $by_post[ $post_id ] ) ) {
			continue;
		}

		// Second pass — the index can lag a permission change by a few seconds.
		if ( ! nntm_search_can_view( $post_id ) ) {
			continue;
		}

		$post = get_post( $post_id );

		if ( ! $post instanceof WP_Post ) {
			continue;
		}

		$by_post[ $post_id ] = array(
			'title'     => esc_html( get_the_title( $post ) ),
			'excerpt'   => esc_html( nntm_search_excerpt( $post->post_excerpt ?: $post->post_content, '', 120 ) ),
			'permalink' => esc_url_raw( (string) get_permalink( $post ) ),
			'thumb'     => esc_url_raw( (string) wp_get_attachment_image_url( $hit['attachment_id'], 'thumbnail' ) ),
			'label'     => __( 'Giống ảnh bạn gửi', 'nntm' ),
		);

		if ( count( $by_post ) >= $limit ) {
			break;
		}
	}

	return array_values( $by_post );
}

/* =========================================================================
 * Indexing the media library.
 * ========================================================================= */

/**
 * Index one library image.
 *
 * @param int $attachment_id Attachment ID.
 * @return bool|WP_Error
 */
function nntm_search_index_image( int $attachment_id ) {
	$path = get_attached_file( $attachment_id );

	if ( ! $path || ! is_readable( $path ) ) {
		return new WP_Error( 'nntm_file_missing', __( 'Không đọc được file ảnh.', 'nntm' ) );
	}

	$vector = nntm_search_embed_image( $path );

	if ( is_wp_error( $vector ) ) {
		return $vector;
	}

	return nntm_search_store_vector( $attachment_id, $vector );
}

/**
 * Index new uploads immediately.
 *
 * Synchronous for now because one image costs tens of milliseconds. When the
 * client bulk-uploads thousands, move this one function onto Action Scheduler —
 * nothing else has to change.
 *
 * @param int $attachment_id New attachment ID.
 */
function nntm_search_on_add_image( int $attachment_id ): void {
	if ( ! nntm_search_image_enabled() || ! wp_attachment_is_image( $attachment_id ) ) {
		return;
	}

	$result = nntm_search_index_image( $attachment_id );

	if ( is_wp_error( $result ) ) {
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- no raw file/image data, only ids and an error code.
		error_log(
			sprintf(
				'[nntm-search] index image failed: attachment_id=%d code=%s message=%s',
				$attachment_id,
				$result->get_error_code(),
				$result->get_error_message()
			)
		);
	}
}
add_action( 'add_attachment', 'nntm_search_on_add_image' );

/**
 * Remove the vector when the image is deleted, so the table has no orphans.
 *
 * @param int $post_id Attachment ID.
 */
function nntm_search_on_delete_image( int $post_id ): void {
	global $wpdb;

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$wpdb->delete( nntm_search_table_vectors(), array( 'attachment_id' => $post_id ), array( '%d' ) );
}
add_action( 'delete_attachment', 'nntm_search_on_delete_image' );
