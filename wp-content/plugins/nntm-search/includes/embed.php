<?php
/**
 * Talking to the local image service.
 *
 * The service runs on 127.0.0.1 (ONNX Runtime + CLIP ViT-B/32). Isolated in
 * this file so switching to a hosted API later means editing two functions and
 * nothing else.
 *
 * ⚠️ THE SERVICE ADDRESS IS INTERNAL. Never put it in a response, never expose
 * it to JavaScript, never include it in an error message shown to a user.
 * Leaking it lets outsiders call it directly and burn server CPU for free.
 *
 * @package NNTM_Search
 */

defined( 'ABSPATH' ) || exit;

/**
 * Service base URL.
 *
 * Defined in wp-config.php so each environment differs without a code change:
 *   define( 'NNTM_SEARCH_SERVICE_URL', 'http://127.0.0.1:8765' );
 *
 * @return string
 */
function nntm_search_service_url(): string {
	return defined( 'NNTM_SEARCH_SERVICE_URL' )
		? (string) NNTM_SEARCH_SERVICE_URL
		: 'http://127.0.0.1:8765';
}

/**
 * Model identifier stored alongside every vector.
 *
 * Vectors are only comparable within the same model. Storing the name means a
 * model change can be rolled out by filtering on this column instead of wiping
 * the table and starting over.
 *
 * @return string
 */
function nntm_search_model(): string {
	return (string) apply_filters( 'nntm_search_model', 'clip-vit-b32-onnx' );
}

/**
 * POST a file to the service as multipart/form-data.
 *
 * @param string $endpoint  Path, e.g. '/anh/tu-khoa'.
 * @param string $file_path Local file.
 * @param string $field     Form field name.
 * @param int    $timeout   Seconds.
 * @return array|WP_Error Decoded JSON.
 */
function nntm_search_post_file( string $endpoint, string $file_path, string $field, int $timeout = 20 ) {
	$generic = new WP_Error( 'nntm_service_failed', __( 'Không xử lý được ảnh lúc này.', 'nntm' ) );

	if ( ! is_readable( $file_path ) ) {
		return $generic;
	}

	$body = file_get_contents( $file_path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- local file, not a URL.

	if ( false === $body ) {
		return $generic;
	}

	$boundary = wp_generate_password( 24, false );

	$payload = "--{$boundary}\r\n"
		. 'Content-Disposition: form-data; name="' . $field . '"; filename="' . basename( $file_path ) . "\"\r\n"
		. "Content-Type: application/octet-stream\r\n\r\n"
		. $body . "\r\n"
		. "--{$boundary}--\r\n";

	$response = wp_remote_post(
		nntm_search_service_url() . $endpoint,
		array(
			'timeout' => $timeout,
			'headers' => array( 'Content-Type' => 'multipart/form-data; boundary=' . $boundary ),
			'body'    => $payload,
		)
	);

	if ( is_wp_error( $response ) ) {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( '[nntm-search] service: ' . $response->get_error_message() ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		}
		return $generic;
	}

	if ( 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
		return $generic;
	}

	$data = json_decode( (string) wp_remote_retrieve_body( $response ), true );

	return is_array( $data ) ? $data : $generic;
}

/**
 * Read an image and return the Vietnamese keywords describing it.
 *
 * This is the primary path for image search: describe the picture in words,
 * then search those words exactly as if the visitor had typed them.
 *
 * @param string $file_path Local image file.
 * @return array{keywords: array<int, array{word: string, score: float}>}|WP_Error
 */
function nntm_search_image_keywords( string $file_path ) {
	$data = nntm_search_post_file( '/anh/tu-khoa', $file_path, 'anh' );

	if ( is_wp_error( $data ) ) {
		return $data;
	}

	$keywords = array();

	foreach ( (array) ( $data['tu_khoa'] ?? array() ) as $item ) {
		if ( empty( $item['tu'] ) ) {
			continue;
		}

		$keywords[] = array(
			'word'  => sanitize_text_field( (string) $item['tu'] ),
			'score' => round( (float) ( $item['diem'] ?? 0 ), 4 ),
		);
	}

	return array( 'keywords' => $keywords );
}

/**
 * Embed an image into a vector.
 *
 * Kept as the fallback for when no keyword is confident enough — a picture with
 * no recognisable subject still has neighbours in the library.
 *
 * @param string $file_path Local image file.
 * @return float[]|WP_Error
 */
function nntm_search_embed_image( string $file_path ) {
	$data = nntm_search_post_file( '/embed/image', $file_path, 'anh' );

	if ( is_wp_error( $data ) ) {
		return $data;
	}

	if ( empty( $data['vector'] ) || ! is_array( $data['vector'] ) ) {
		return new WP_Error( 'nntm_service_failed', __( 'Không xử lý được ảnh lúc này.', 'nntm' ) );
	}

	return array_map( 'floatval', $data['vector'] );
}

/* =========================================================================
 * Vector storage and lookup.
 * ========================================================================= */

/**
 * Find the post that uses an image.
 *
 * DO NOT rely on `post_parent`. Measured on this project 15/08/2026: nearly
 * every image has post_parent = 0 because they were uploaded straight into the
 * Media Library and inserted through blocks, not from inside the post editor.
 * Relying on post_parent makes image search return nothing at all despite a
 * fully populated index.
 *
 * Lookup order: parent → featured image → referenced in content (block
 * `"imageId":N` or the classic editor's `wp-image-N` class).
 *
 * REGEXP rather than LIKE: `LIKE '%"imageId":11%'` also matches image 118 and
 * 1180. REGEXP can pin the digit boundary.
 *
 * @param int $attachment_id Attachment ID.
 * @return int Post ID, 0 when nothing uses it.
 */
function nntm_search_post_using_image( int $attachment_id ): int {
	global $wpdb;

	$attachment = get_post( $attachment_id );

	if ( $attachment instanceof WP_Post && (int) $attachment->post_parent > 0 ) {
		return (int) $attachment->post_parent;
	}

	// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$post_id = (int) $wpdb->get_var(
		$wpdb->prepare(
			"SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_thumbnail_id' AND meta_value = %d LIMIT 1",
			$attachment_id
		)
	);

	if ( $post_id > 0 ) {
		return $post_id;
	}

	$post_id = (int) $wpdb->get_var(
		$wpdb->prepare(
			"SELECT ID FROM {$wpdb->posts}
			 WHERE post_status = 'publish'
			   AND post_type NOT IN ( 'attachment', 'revision' )
			   AND ( post_content REGEXP %s OR post_content REGEXP %s )
			 LIMIT 1",
			'"imageId":' . $attachment_id . '([^0-9]|$)',
			'wp-image-' . $attachment_id . '([^0-9]|$)'
		)
	);
	// phpcs:enable

	return $post_id;
}

/**
 * Normalise a vector to unit length.
 *
 * Done once at write time so comparison is a plain dot product.
 *
 * @param float[] $vector Vector.
 * @return float[]
 */
function nntm_search_normalize( array $vector ): array {
	$sum = 0.0;

	foreach ( $vector as $x ) {
		$sum += $x * $x;
	}

	$length = sqrt( $sum );

	return $length > 0.0
		? array_map( static fn( float $x ): float => $x / $length, $vector )
		: $vector;
}

/**
 * Store one image vector.
 *
 * @param int     $attachment_id Attachment ID.
 * @param float[] $vector        Raw vector from the service.
 * @return bool
 */
function nntm_search_store_vector( int $attachment_id, array $vector ): bool {
	global $wpdb;

	if ( empty( $vector ) ) {
		return false;
	}

	$attachment = get_post( $attachment_id );

	if ( ! $attachment instanceof WP_Post ) {
		return false;
	}

	$unit    = nntm_search_normalize( $vector );
	$post_id = nntm_search_post_using_image( $attachment_id );

	// An image inherits the permission of the post that uses it: an illustration
	// inside a Hành Giả article is Hành Giả content too. Orphan images are public
	// because they belong to no section.
	$acl = 'public';

	if ( $post_id > 0 ) {
		$parent = get_post( $post_id );

		if ( $parent instanceof WP_Post ) {
			$acl = nntm_search_post_acl( $parent );
		}
	}

	$lang = function_exists( 'pll_get_post_language' ) && $post_id > 0
		? (string) pll_get_post_language( $post_id )
		: '';

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	return false !== $wpdb->replace(
		nntm_search_table_vectors(),
		array(
			'attachment_id' => $attachment_id,
			'post_id'       => $post_id,
			'acl'           => $acl,
			'lang'          => $lang,
			'model'         => nntm_search_model(),
			'dim'           => count( $unit ),
			'vector'        => pack( 'g*', ...$unit ),
			'updated_at'    => current_time( 'mysql' ),
		),
		array( '%d', '%d', '%s', '%s', '%s', '%d', '%s', '%s' )
	);
}

/**
 * Nearest images to a query vector.
 *
 * Why a full scan instead of a dedicated vector database: 2,000 images × 512
 * dims × 4 bytes is 8MB. Scanning it takes tens of milliseconds — faster than a
 * network hop to an external service, with nothing extra to install, run and
 * back up. Revisit past roughly 20,000–50,000 images; the vectors are already
 * here, so moving them costs nothing.
 *
 * @param float[]  $vector Query vector.
 * @param string[] $acl    Access levels the viewer may see.
 * @param int      $limit  How many to return.
 * @return array<int, array{attachment_id: int, post_id: int, score: float}>
 */
function nntm_search_vector_search( array $vector, array $acl, int $limit = 12 ): array {
	global $wpdb;

	if ( empty( $vector ) || empty( $acl ) ) {
		return array();
	}

	$query = nntm_search_normalize( $vector );
	$dim   = count( $query );

	// Filter permission IN SQL: never load vectors the viewer may not see, not
	// even to discard them afterwards.
	$placeholders = implode( ', ', array_fill( 0, count( $acl ), '%s' ) );
	$table        = nntm_search_table_vectors();

	// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$rows = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT attachment_id, post_id, dim, vector FROM {$table}
			 WHERE model = %s AND dim = %d AND acl IN ({$placeholders})",
			array_merge( array( nntm_search_model(), $dim ), $acl )
		)
	);
	// phpcs:enable

	$scored = array();

	foreach ( $rows as $row ) {
		$stored = unpack( 'g*', $row->vector );

		if ( ! is_array( $stored ) || count( $stored ) !== $dim ) {
			continue;
		}

		// Both sides are unit vectors, so cosine is just the dot product.
		$dot = 0.0;
		$i   = 1; // unpack() indexes from 1.

		foreach ( $query as $x ) {
			$dot += $x * $stored[ $i ];
			++$i;
		}

		$scored[] = array(
			'attachment_id' => (int) $row->attachment_id,
			'post_id'       => (int) $row->post_id,
			'score'         => $dot,
		);
	}

	usort( $scored, static fn( array $a, array $b ): int => $b['score'] <=> $a['score'] );

	return array_slice( $scored, 0, max( 1, $limit ) );
}
