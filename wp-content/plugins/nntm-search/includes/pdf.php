<?php
/**
 * Reading and searching the text inside PDF files.
 *
 * PDFs are ordinary Media Library attachments — no separate storage, no
 * out-of-webroot directory. Text extraction happens in the local Python service
 * rather than by shelling out to `pdftotext`, which removes a shell_exec() call
 * (an attack surface plus per-OS binary path config) and one more dependency to
 * install.
 *
 * One row per page. That is what lets a result say "page 37" and deep-link
 * there; a whole-book text blob throws that away permanently.
 *
 * @package NNTM_Search
 */

defined( 'ABSPATH' ) || exit;

/**
 * Extract every page of a PDF attachment and store it.
 *
 * @param int $attachment_id Attachment ID.
 * @return int|WP_Error Number of pages stored.
 */
function nntm_search_index_pdf( int $attachment_id ) {
	global $wpdb;

	$path = get_attached_file( $attachment_id );

	if ( ! $path || ! is_readable( $path ) ) {
		return new WP_Error( 'nntm_pdf_unreadable', __( 'Không đọc được file PDF.', 'nntm' ) );
	}

	$body = file_get_contents( $path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- local file, not a URL.

	if ( false === $body ) {
		return new WP_Error( 'nntm_pdf_unreadable', __( 'Không đọc được file PDF.', 'nntm' ) );
	}

	$boundary = wp_generate_password( 24, false );

	$payload = "--{$boundary}\r\n"
		. 'Content-Disposition: form-data; name="tep"; filename="' . basename( $path ) . "\"\r\n"
		. "Content-Type: application/pdf\r\n\r\n"
		. $body . "\r\n"
		. "--{$boundary}--\r\n";

	$response = wp_remote_post(
		nntm_search_service_url() . '/pdf/text',
		array(
			'timeout' => 120,
			'headers' => array( 'Content-Type' => 'multipart/form-data; boundary=' . $boundary ),
			'body'    => $payload,
		)
	);

	if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
		return new WP_Error( 'nntm_pdf_service', __( 'Dịch vụ đọc PDF không phản hồi.', 'nntm' ) );
	}

	$data = json_decode( (string) wp_remote_retrieve_body( $response ), true );

	if ( ! is_array( $data ) || empty( $data['trang'] ) ) {
		return new WP_Error( 'nntm_pdf_empty', __( 'PDF không có trang nào đọc được.', 'nntm' ) );
	}

	$post_id = nntm_search_pdf_owner( $attachment_id );
	$stored  = 0;

	foreach ( $data['trang'] as $page ) {
		$content = trim( (string) ( $page['chu'] ?? '' ) );

		// Empty page means a scanned image. Skipped for now — OCR (Tesseract,
		// running locally, no third-party service) plugs in exactly here.
		if ( '' === $content ) {
			continue;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->replace(
			nntm_search_table_pdf_pages(),
			array(
				'attachment_id' => $attachment_id,
				'post_id'       => $post_id,
				'page_no'       => (int) $page['trang'],
				'content'       => $content,
				'folded'        => nntm_search_fold( $content ),
				'source'        => (string) ( $page['nguon'] ?? 'text' ),
				'updated_at'    => current_time( 'mysql' ),
			),
			array( '%d', '%d', '%d', '%s', '%s', '%s', '%s' )
		);

		++$stored;
	}

	return $stored;
}

/**
 * Which post does this PDF belong to.
 *
 * Same problem as images: attachments uploaded straight into the Media Library
 * have post_parent = 0. Fall back to whichever publication points at it.
 *
 * @param int $attachment_id Attachment ID.
 * @return int
 */
function nntm_search_pdf_owner( int $attachment_id ): int {
	global $wpdb;

	$attachment = get_post( $attachment_id );

	if ( $attachment instanceof WP_Post && (int) $attachment->post_parent > 0 ) {
		return (int) $attachment->post_parent;
	}

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	return (int) $wpdb->get_var(
		$wpdb->prepare(
			"SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_nntm_pdf_file' AND meta_value = %d LIMIT 1",
			$attachment_id
		)
	);
}

/**
 * Shortest word the FULLTEXT index will hold, read from the server.
 *
 * Hardcoding 3 would be wrong the moment someone sets it to 2 in my.cnf — and
 * setting it to 2 is exactly what a Vietnamese site should do. Ask the server.
 *
 * @return int
 */
function nntm_search_ft_min_token(): int {
	static $min = null;

	if ( null !== $min ) {
		return $min;
	}

	$cached = get_transient( 'nntm_search_ft_min_token' );

	if ( false !== $cached ) {
		$min = (int) $cached;

		return $min;
	}

	global $wpdb;

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$row = $wpdb->get_row( "SHOW VARIABLES LIKE 'innodb_ft_min_token_size'" );

	$min = ( $row && isset( $row->Value ) ) ? max( 1, (int) $row->Value ) : 3;

	set_transient( 'nntm_search_ft_min_token', $min, DAY_IN_SECONDS );

	return $min;
}

/**
 * Những từ mà chỉ mục FULLTEXT đang bỏ qua.
 *
 * MEASURED 16/08/2026 — cái bẫy thứ hai của cùng một lỗi:
 *
 * Danh sách stopword mặc định của InnoDB là tiếng Anh và có `de`, `la`, `com`,
 * `in`, `it`, `be`, `at`, `on`, `to`. Bỏ dấu tiếng Việt xong thì "là" → `la`,
 * "để"/"đế" → `de`, "cơm" → `com`. Những từ này bị ném khỏi chỉ mục, nên bắt
 * buộc chúng phải khớp là câu tìm không bao giờ ra kết quả.
 *
 * Cách chữa tận gốc là trỏ `innodb_ft_server_stopword_table` sang một bảng
 * RỖNG rồi dựng lại chỉ mục — đã làm ở local, xem my.ini. Nhưng máy chủ nào
 * không cho đổi cấu hình (hosting dùng chung, không có quyền SUPER) thì vẫn
 * phải chạy được, nên đọc luôn danh sách đang có hiệu lực và tự né.
 *
 * @return string[] Danh sách đã hạ chữ thường.
 */
function nntm_search_ft_stopwords(): array {
	static $words = null;

	if ( null !== $words ) {
		return $words;
	}

	$cached = get_transient( 'nntm_search_ft_stopwords' );

	if ( is_array( $cached ) ) {
		$words = $cached;

		return $words;
	}

	global $wpdb;

	// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$row    = $wpdb->get_row( "SHOW VARIABLES LIKE 'innodb_ft_server_stopword_table'" );
	$custom = ( $row && isset( $row->Value ) ) ? trim( (string) $row->Value ) : '';

	if ( '' !== $custom ) {
		// Giá trị có dạng `csdl/bang`; tách ra rồi bọc backtick từng phần.
		$parts = array_map( 'sanitize_key', explode( '/', $custom ) );

		if ( 2 === count( $parts ) && '' !== $parts[0] && '' !== $parts[1] ) {
			$table = '`' . $parts[0] . '`.`' . $parts[1] . '`';
			$found = $wpdb->get_col( "SELECT value FROM {$table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- ten bang da qua sanitize_key.
		}
	}

	if ( ! isset( $found ) || null === $found ) {
		$found = $wpdb->get_col( 'SELECT value FROM INFORMATION_SCHEMA.INNODB_FT_DEFAULT_STOPWORD' );
	}
	// phpcs:enable

	$words = array_map( 'mb_strtolower', array_filter( (array) $found ) );

	set_transient( 'nntm_search_ft_stopwords', $words, DAY_IN_SECONDS );

	return $words;
}

/**
 * Đường lui khi mọi từ khoá đều ngắn hơn ngưỡng của chỉ mục.
 *
 * Chỉ chạy cho đúng trường hợp hẹp đó — ví dụ tìm mỗi chữ "vô". FULLTEXT không
 * trả lời được câu này nên phải quét, nhưng quét trên một bảng chỉ chứa text
 * PDF (hàng nghìn dòng) chứ không phải trên wp_posts, và có LIMIT chặn.
 *
 * @param string   $query Chuỗi tìm gốc.
 * @param string[] $terms Các từ đã tách.
 * @param int      $limit Số dòng tối đa.
 * @return array[]
 */
function nntm_search_pdf_pages_like( string $query, array $terms, int $limit ): array {
	global $wpdb;

	$table  = nntm_search_table_pdf_pages();
	$where  = array();
	$params = array();

	foreach ( $terms as $term ) {
		$where[]  = 'folded LIKE %s';
		$params[] = '%' . $wpdb->esc_like( nntm_search_fold( $term ) ) . '%';
	}

	$params[] = max( 1, $limit );

	// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$hits = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT attachment_id, post_id, page_no, content, 0 AS score
			 FROM {$table}
			 WHERE " . implode( ' AND ', $where ) . '
			 ORDER BY attachment_id, page_no
			 LIMIT %d',
			$params
		)
	);
	// phpcs:enable

	return nntm_search_pdf_rows_from( $hits, $query );
}

/**
 * Every PDF page matching a query, after permission filtering.
 *
 * Returns the FULL hit list rather than a page of it, because the caller needs
 * an honest count for the "Tài liệu PDF" tab. Capped at 200 to stay bounded;
 * hitting the cap is logged rather than silently swallowed — a truncated result
 * that looks complete is worse than one that admits it.
 *
 * @param string $query Search query.
 * @return array[] Rows shaped like nntm_search_build_row().
 */
function nntm_search_pdf_hits( string $query ): array {
	static $cache = array();

	$key = md5( $query . '|' . implode( ',', nntm_search_viewer_acl() ) );

	// One search page render asks for this twice (once for counts, once for
	// display). Same request, same answer — no reason to run it twice.
	if ( isset( $cache[ $key ] ) ) {
		return $cache[ $key ];
	}

	$cap  = 200;
	$hits = nntm_search_pdf_pages( $query, $cap );

	if ( count( $hits ) >= $cap ) {
		error_log( '[nntm-search] PDF hits hit the ' . $cap . ' cap for: ' . $query ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
	}

	$cache[ $key ] = $hits;

	return $hits;
}

/**
 * Search inside indexed PDF pages.
 *
 * Uses FULLTEXT MATCH on the folded column — not LIKE '%…%', which would table
 * scan. Terms are folded the same way the column was, so accent-free typing
 * works.
 *
 * @param string $query Search query.
 * @param int    $limit Max page hits.
 * @return array[] Rows shaped like nntm_search_build_row().
 */
function nntm_search_pdf_pages( string $query, int $limit = 3 ): array {
	global $wpdb;

	$terms = nntm_search_split_terms( $query );

	if ( empty( $terms ) ) {
		return array();
	}

	$table = nntm_search_table_pdf_pages();

	/*
	 * MEASURED 16/08/2026 — the bug this guards against:
	 *
	 * Searching "Tứ Diệu Đế là bài pháp đầu tiên Đức Phật tuyên thuyết tại"
	 * returned nothing, even though the sentence is verbatim on page 1 of a
	 * demo PDF. Every term matched on its own; requiring all of them matched
	 * nothing.
	 *
	 * Cause: folded, "Tứ" → "tu", "Đế" → "de", "là" → "la" — two characters
	 * each, below innodb_ft_min_token_size (default 3), so those tokens are
	 * never indexed at all. Requiring them with +tu* can therefore never be
	 * satisfied on the row that actually contains the sentence, and one
	 * unsatisfiable term kills the whole AND.
	 *
	 * Vietnamese is full of two-letter syllables once folded — là, và, có, ở,
	 * để, tứ, đế. So this did not merely lose short words; it broke almost
	 * every long phrase.
	 *
	 * Fix: only REQUIRE terms the index can actually hold. Shorter ones ride
	 * along un-prefixed so they still lift relevance when present. Raising
	 * innodb_ft_min_token_size to 2 on the server makes more terms required
	 * again — the code reads the server's value instead of assuming one.
	 */
	$min_token = nntm_search_ft_min_token();
	$stopwords = nntm_search_ft_stopwords();

	$required = array();
	$optional = array();

	foreach ( $terms as $term ) {
		$folded = nntm_search_fold( $term );

		// Chỉ bắt buộc những từ mà chỉ mục thật sự có thể chứa: đủ dài VÀ không
		// nằm trong danh sách stopword. Bắt buộc một từ không index được thì
		// không dòng nào thoả, cả câu tìm chết theo.
		$indexable = mb_strlen( $folded ) >= $min_token
			&& ! in_array( $folded, $stopwords, true );

		if ( $indexable ) {
			$required[] = '+' . $folded . '*';
		} else {
			$optional[] = $folded;
		}
	}

	// Nothing long enough to index — e.g. someone searched just "vô". FULLTEXT
	// cannot answer that at all, so fall back to a bounded LIKE on the folded
	// column. Slow by nature, which is why it only runs for this narrow case.
	if ( empty( $required ) ) {
		return nntm_search_pdf_pages_like( $query, $terms, $limit );
	}

	$expression = implode( ' ', array_merge( $required, $optional ) );

	// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$hits = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT attachment_id, post_id, page_no, content,
			        MATCH(folded) AGAINST (%s IN BOOLEAN MODE) AS score
			 FROM {$table}
			 WHERE MATCH(folded) AGAINST (%s IN BOOLEAN MODE)
			 ORDER BY score DESC
			 LIMIT %d",
			$expression,
			$expression,
			max( 1, $limit )
		)
	);
	// phpcs:enable

	return nntm_search_pdf_rows_from( $hits, $query );
}

/**
 * Dựng hàng kết quả từ các dòng CSDL, có lọc quyền.
 *
 * Tách riêng để hai đường truy vấn (FULLTEXT và đường lui LIKE) dùng chung —
 * hai bản dựng hàng khác nhau là hai chỗ để lệch, mà lệch ở lọc quyền là rò.
 *
 * @param array  $hits  Dòng lấy từ CSDL.
 * @param string $query Chuỗi tìm, để tô sáng.
 * @return array[]
 */
function nntm_search_pdf_rows_from( array $hits, string $query ): array {
	$rows = array();

	foreach ( $hits as $hit ) {
		$post_id = (int) $hit->post_id;

		// A PDF page inherits the permission of the publication that owns it.
		if ( $post_id > 0 && ! nntm_search_can_view( $post_id ) ) {
			continue;
		}

		$post      = $post_id > 0 ? get_post( $post_id ) : null;
		$title     = $post instanceof WP_Post
			? get_the_title( $post )
			: get_the_title( (int) $hit->attachment_id );
		$permalink = $post instanceof WP_Post
			? add_query_arg( 'trang', (int) $hit->page_no, (string) get_permalink( $post ) )
			: (string) wp_get_attachment_url( (int) $hit->attachment_id );

		$rows[] = array(
			'id'        => $post_id,
			'type'      => 'pdf_page',
			'title'     => nntm_search_highlight( $title, $query ),
			'excerpt'   => nntm_search_highlight( nntm_search_excerpt( (string) $hit->content, $query ), $query ),
			'permalink' => $permalink,
			'thumb'     => $post instanceof WP_Post ? (string) ( get_the_post_thumbnail_url( $post, 'thumbnail' ) ?: '' ) : '',
			'thumb_tag' => $post instanceof WP_Post
				? (string) get_the_post_thumbnail( $post, 'medium_large', array( 'class' => 'nntm-article-rows__img-el', 'loading' => 'lazy' ) )
				: '',
			/* translators: %d: page number inside the PDF. */
			'label'     => sprintf( __( 'PDF · trang %d', 'nntm' ), (int) $hit->page_no ),
			'cta_1'     => __( 'Mở đúng trang', 'nntm' ),
			'cta_2'     => __( 'Tải xuống', 'nntm' ),
			// Second action points somewhere else than the first, so the row
			// renderer needs its own URL rather than reusing the permalink.
			'cta_2_url' => nntm_search_pdf_download_url( (int) $hit->attachment_id ),
			'cta_2_download' => true,
		);
	}

	return $rows;
}

/**
 * Index a PDF as soon as it is uploaded.
 *
 * @param int $attachment_id New attachment ID.
 */
function nntm_search_on_add_pdf( int $attachment_id ): void {
	if ( 'application/pdf' !== get_post_mime_type( $attachment_id ) ) {
		return;
	}

	nntm_search_index_pdf( $attachment_id );
}
add_action( 'add_attachment', 'nntm_search_on_add_pdf' );

/**
 * Drop indexed pages when the file is deleted.
 *
 * @param int $post_id Attachment ID.
 */
function nntm_search_on_delete_pdf( int $post_id ): void {
	global $wpdb;

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$wpdb->delete( nntm_search_table_pdf_pages(), array( 'attachment_id' => $post_id ), array( '%d' ) );
}
add_action( 'delete_attachment', 'nntm_search_on_delete_pdf' );
