<?php
/**
 * Query layer — swappable without touching any caller.
 *
 * Runs on WP_Query today. When an external engine arrives, hook
 * `nntm_search_engine_results` and it replaces the whole thing: REST, the
 * dropdown and the results page all go through here.
 *
 * ⚠️ Read includes/acl.php before swapping in an external engine. WP_Query is
 * still covered by the theme's pre_get_posts gate; an external engine is not.
 *
 * @package NNTM_Search
 */

defined( 'ABSPATH' ) || exit;

/**
 * Result groups and the post types behind each tab.
 *
 * NEVER use post_type = 'any': the Polylang trap in docs/07-ban-giao.md
 * section 9 — posts with no language assigned VANISH from an 'any' query while
 * still showing up when the post type is named explicitly. Naming them is the
 * only reliable option.
 *
 * @return array<string, array{label: string, post_type: string[]}>
 */
function nntm_search_groups(): array {
	return (array) apply_filters(
		'nntm_search_groups',
		array(
			/*
			 * SỬA 17/08/2026: BỎ 'page' khỏi phạm vi tìm — chủ dự án chốt lại
			 * "tìm thường chỉ ra bài viết hoặc PDF", ngược với yêu cầu trước đó
			 * ở docs/10-ban-giao-tim-kiem.md mục 3 ("Đề bài yêu cầu tìm cả
			 * trang"). Cổng quyền cho Page (nntm_trang_can_dang_nhap() trong
			 * includes/acl.php) VẪN giữ nguyên — không xoá phòng khi Page quay
			 * lại phạm vi tìm qua filter, không phải sửa lại từ đầu.
			 */
			'all'     => array(
				'label'     => __( 'Tất cả', 'nntm' ),
				'post_type' => array( 'nntm_article', 'nntm_publication', 'nntm_video', 'nntm_talk', 'nntm_retreat', 'post' ),
			),
			'article' => array(
				'label'     => __( 'Bài viết', 'nntm' ),
				'post_type' => array( 'nntm_article', 'post' ),
			),
			'pdf'     => array(
				'label'     => __( 'Tài liệu PDF', 'nntm' ),
				'post_type' => array( 'nntm_publication' ),
			),
		)
	);
}

/**
 * Run a search and return normalised results.
 *
 * @param string $query      Search query.
 * @param string $group      Group key.
 * @param int    $page       1-based page number.
 * @param int    $per_page   Results per page.
 * @param bool   $with_counts Whether to compute per-tab counts.
 * @return array{rows: array[], total: int, counts: array<string, int>, pdf: array[]}
 */
function nntm_search_query( string $query, string $group = 'all', int $page = 1, int $per_page = 10, bool $with_counts = true ): array {
	$groups = nntm_search_groups();
	$group  = isset( $groups[ $group ] ) ? $group : 'all';

	$results = array(
		'rows'   => array(),
		'total'  => 0,
		'counts' => array(),
		'pdf'    => array(),
	);

	if ( mb_strlen( trim( $query ) ) < 2 ) {
		return $results;
	}

	// PDF page hits come from our own table, not from wp_posts. The full list is
	// fetched so the tab counts can be honest; only a slice is displayed.
	$pdf_all   = nntm_search_pdf_hits( $query );
	$pdf_total = count( $pdf_all );
	$pdf_shown = array_slice( $pdf_all, 0, 'pdf' === $group ? $per_page : 3 );

	if ( $with_counts ) {
		foreach ( $groups as $key => $config ) {
			$counter = new WP_Query(
				array(
					's'              => $query,
					'post_type'      => $config['post_type'],
					'post_status'    => 'publish',
					'posts_per_page' => 1,
					'fields'         => 'ids',
					'no_found_rows'  => false,
				)
			);

			$results['counts'][ $key ] = (int) $counter->found_posts;
		}

		/*
		 * Pages inside PDFs are results in their own right, so they count in both
		 * the PDF tab and the "all" tab. Leaving them out of "all" made the tab
		 * read 6 while the page listed 7 rows and the summary line said 7 — three
		 * numbers, three different answers.
		 */
		$results['counts']['pdf'] += $pdf_total;
		$results['counts']['all'] += $pdf_total;
	}

	$wp_query = new WP_Query(
		array(
			's'              => $query,
			'post_type'      => $groups[ $group ]['post_type'],
			'post_status'    => 'publish',
			'posts_per_page' => $per_page,
			'paged'          => max( 1, $page ),
		)
	);

	/*
	 * ⚠️ CHƯA CHUẨN khi câu tìm có dấu hoặc là câu dài: $wp_query->found_posts
	 * đếm ở SQL, TRƯỚC bộ lọc nntm_search_content_matches_query() ở vòng lặp
	 * bên dưới — nên số này (và $results['counts'] phía trên) có thể CAO HƠN
	 * số dòng thực sự hiện ra. Đúng cho $pdf_total (đã lọc trước khi đếm ở
	 * nntm_search_pdf_hits()). Sửa cho $wp_query cần đếm SAU khi lọc — chưa
	 * làm vì phải tải nội dung của mọi ứng viên trong MỌI tab (không chỉ 1
	 * dòng như hiện tại), tốn thêm truy vấn. Ghi rõ để không ai tưởng đã sửa
	 * triệt để.
	 */
	$results['total'] = (int) $wp_query->found_posts + $pdf_total;

	// PDF page hits lead: knowing the exact page is more useful than a title match.
	foreach ( $pdf_shown as $hit ) {
		$results['rows'][] = $hit;
	}

	$terms = nntm_search_split_terms( $query );

	foreach ( $wp_query->posts as $post ) {
		// Second pass — see includes/acl.php.
		if ( ! nntm_search_can_view( $post->ID ) ) {
			continue;
		}

		/*
		 * Hai bug có thật 17/08/2026, cùng lọc bằng nntm_search_content_matches_query()
		 * (includes/text.php) — chi tiết đầy đủ ở đó, gồm cả bản dùng cho PDF
		 * (nntm_search_pdf_filter_results() trong includes/pdf.php):
		 *
		 *   1. Câu ngắn: tìm "rừng" ra cả bài không hề có chữ "rừng" (ví dụ bài
		 *      chỉ có "trùng tu"). Nguyên nhân KHÔNG nằm ở code — collation
		 *      `utf8mb4_unicode_ci` của CSDL tự coi ký tự có dấu ngang bằng ký
		 *      tự gốc khi WP_Query so `LIKE`.
		 *   2. Câu dài: WP_Query mặc định AND-theo-từ, không đòi các từ phải
		 *      đứng gần nhau — bài chỉ tình cờ rải rác đủ từng từ, không liên
		 *      quan gì tới câu tìm, vẫn khớp.
		 */
		$haystack = $post->post_title . ' ' . $post->post_excerpt . ' ' . $post->post_content;

		if ( ! nntm_search_content_matches_query( $haystack, $query, $terms ) ) {
			continue;
		}

		$results['rows'][] = nntm_search_build_row( $post, $query );
	}

	/**
	 * Replacement point for an external engine.
	 *
	 * @param array  $results  Results from WP_Query.
	 * @param string $query    Search query.
	 * @param string $group    Group key.
	 * @param int    $page     Page number.
	 * @param int    $per_page Results per page.
	 */
	return (array) apply_filters( 'nntm_search_engine_results', $results, $query, $group, $page, $per_page );
}

/**
 * Normalise a post into a row.
 *
 * Returns an ARRAY rather than a WP_Post because results also include "a page
 * inside a PDF", which is not a post at all. Fixing the shape now means the
 * rendering side never has to change when PDF hits arrive.
 *
 * @param WP_Post $post  Post.
 * @param string  $query Search query.
 * @return array
 */
function nntm_search_build_row( WP_Post $post, string $query ): array {
	$label = '';

	/*
	 * Taxonomy priority (nntm_section → nntm_topic → nntm_series → category) is
	 * owned by the theme in blocks/card/inc/render-card.php. Copying it here
	 * would mean two copies of one rule. That file is only required by block
	 * render callbacks, so in a REST context it has not loaded yet — load it,
	 * guarded, so a different theme cannot fatal.
	 */
	if ( ! function_exists( 'nntm_card_get_primary_term' ) ) {
		$card_helpers = get_template_directory() . '/blocks/card/inc/render-card.php';

		if ( is_readable( $card_helpers ) ) {
			require_once $card_helpers;
		}
	}

	if ( function_exists( 'nntm_card_get_primary_term' ) ) {
		$term  = nntm_card_get_primary_term( $post->ID );
		$label = $term instanceof WP_Term ? $term->name : '';
	}

	if ( 'nntm_publication' === $post->post_type ) {
		$label = __( 'Ấn phẩm PDF', 'nntm' );
	} elseif ( 'page' === $post->post_type ) {
		$label = __( 'Trang', 'nntm' );
	}

	$source  = '' !== $post->post_excerpt ? $post->post_excerpt : $post->post_content;
	$excerpt = nntm_search_excerpt( $source, $query );

	return array(
		'id'        => $post->ID,
		'type'      => $post->post_type,
		'title'     => nntm_search_highlight( get_the_title( $post ), $query ),
		'excerpt'   => nntm_search_highlight( $excerpt, $query ),
		'permalink' => (string) get_permalink( $post ),
		'thumb'     => (string) ( get_the_post_thumbnail_url( $post, 'thumbnail' ) ?: '' ),
		'thumb_tag' => (string) get_the_post_thumbnail(
			$post,
			'medium_large',
			array(
				'class'   => 'nntm-article-rows__img-el',
				'loading' => 'lazy',
				'alt'     => get_the_title( $post ),
			)
		),
		'label'     => $label,
		'cta_1'     => 'nntm_publication' === $post->post_type ? __( 'Mở ấn phẩm', 'nntm' ) : __( 'Đọc bài', 'nntm' ),
		'cta_2'     => __( 'Xem thêm', 'nntm' ),
	);
}
