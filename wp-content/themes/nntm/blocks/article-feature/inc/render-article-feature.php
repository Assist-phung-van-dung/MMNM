<?php
/**
 * Hàm dựng HTML dùng chung cho render.php của block nntm/article-feature.
 *
 * Tách riêng ra file inc/ vì render.php của block bị WordPress core
 * `require` (KHÔNG PHẢI `require_once`) mỗi lần block render — khai hàm
 * thẳng trong render.php sẽ "Cannot redeclare function" ngay khi block
 * xuất hiện lần thứ hai trên cùng một trang. Xem docs/07-ban-giao.md
 * mục "Bài học rút ra".
 *
 * @package NNTM
 */

defined( 'ABSPATH' ) || exit;

/**
 * Lấy N đoạn văn đầu tiên của một bài, giữ nguyên cách chia đoạn.
 *
 * VÌ SAO KHÔNG DÙNG wp_trim_words(): hàm đó trả về một khối chữ liền,
 * mất hết cách chia đoạn — trong khi thiết kế Figma cho thấy thân bài
 * gồm nhiều đoạn tách nhau rõ ràng (lời hỏi, lời đáp, lời giảng).
 *
 * VÌ SAO KHÔNG ÁP FILTER 'the_content': nếu chính bài được chọn lại có
 * block này bên trong thì filter gọi đệ quy vô hạn và làm trắng trang.
 * do_blocks() đủ để dựng nội dung block, còn shortcode thì gỡ bỏ vì
 * khối này chỉ hiện phần mở đầu bài, không phải trang đọc đầy đủ.
 *
 * @param WP_Post $article    Bài viết.
 * @param int     $max_paragraphs Số đoạn tối đa (>=1).
 * @return string HTML các đoạn văn, đã lọc qua wp_kses_post().
 */
function nntm_article_feature_get_paragraphs( WP_Post $article, int $max_paragraphs ): string {
	$max_paragraphs = max( 1, $max_paragraphs );

	$raw = strip_shortcodes( $article->post_content );
	$raw = has_blocks( $article ) ? do_blocks( $raw ) : wpautop( $raw );

	// do_blocks() trả về HTML đã có <p>; nội dung kiểu cũ thì wpautop() lo.
	// Còn sót chữ trần (không bọc thẻ) thì wpautop() bọc nốt cho chắc.
	if ( false === strpos( $raw, '<p' ) ) {
		$raw = wpautop( $raw );
	}

	// Cắt theo </p>: giữ lại đúng $max_paragraphs đoạn đầu. preg_match_all
	// an toàn hơn explode vì bỏ qua chú thích block xen giữa các đoạn.
	if ( ! preg_match_all( '#<p\b[^>]*>.*?</p>#is', $raw, $matches ) || empty( $matches[0] ) ) {
		return wp_kses_post( $raw );
	}

	$keep = array_slice( $matches[0], 0, $max_paragraphs );

	return wp_kses_post( implode( "\n", $keep ) );
}

/**
 * Tìm bài để hiển thị: ưu tiên bài ban quản trị chọn thẳng, không có thì
 * lấy bài mới nhất của nguồn đã chọn.
 *
 * Truy vấn dự phòng luôn kèm `ID` làm tiêu chí phụ: dữ liệu nhập hàng
 * loạt hay có ngày đăng trùng nhau từng giây, mà ORDER BY một cột trùng
 * giá trị thì MySQL được phép trả thứ tự bất kỳ — mỗi lần tải trang lại
 * ra một bài khác. Đây đúng là lỗi đã sửa ở nntm/article-mosaic ngày
 * 10/08/2026, không lặp lại ở đây nữa.
 *
 * @param int    $post_id  ID bài chọn thẳng (0 = tự lấy).
 * @param string $post_type Loại nội dung dùng cho truy vấn dự phòng.
 * @param string $taxonomy  Taxonomy lọc (rỗng = không lọc).
 * @param int    $term_id   Term lọc.
 * @return WP_Post|null
 */
function nntm_article_feature_find_post( int $post_id, string $post_type, string $taxonomy, int $term_id ): ?WP_Post {
	if ( $post_id > 0 ) {
		$chosen = get_post( $post_id );
		if ( $chosen instanceof WP_Post && 'publish' === $chosen->post_status ) {
			return $chosen;
		}
		// Bài đã chọn bị xoá hoặc chuyển về nháp thì rơi về bài mới nhất,
		// để trang không thủng một mảng trống.
	}

	$args = array(
		'post_type'           => $post_type,
		'post_status'         => 'publish',
		'posts_per_page'      => 1,
		'ignore_sticky_posts' => true,
		'no_found_rows'       => true,
		'orderby'             => array(
			'date' => 'DESC',
			'ID'   => 'DESC',
		),
	);

	if ( '' !== $taxonomy && taxonomy_exists( $taxonomy ) && $term_id > 0 ) {
		$args['tax_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- loc theo 1 term, khong tranh duoc.
			array(
				'taxonomy' => $taxonomy,
				'field'    => 'term_id',
				'terms'    => array( $term_id ),
			),
		);
	}

	$query = new WP_Query( $args );

	return empty( $query->posts ) ? null : $query->posts[0];
}
