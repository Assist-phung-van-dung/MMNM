<?php
/**
 * Search results page — data shaping and row rendering.
 *
 * Layout follows the nntm/article-rows block (Figma "03. PHAP TOA - NGUYEN
 * THUY"): full-width rows with the image and text alternating sides. It reuses
 * that block's CSS classes rather than declaring a second set, so a Figma change
 * updates both places at once.
 *
 * Why the row renderer lives in inc/ and not in blocks/article-rows/render.php:
 * that file is `require`d (not require_once), so declaring a function there dies
 * with "Cannot redeclare function" the second time the block renders in one
 * request — the trap recorded in docs/07-ban-giao.md section 9.
 *
 * The query layer belongs to the nntm-search plugin. What is left here is a
 * fallback so the page never goes blank when the plugin is switched off.
 *
 * @package NNTM
 */

defined( 'ABSPATH' ) || exit;

/**
 * Result groups shown as tabs.
 *
 * @return array<string, array{label: string, post_type: string[]}>
 */
function nntm_result_groups(): array {
	if ( function_exists( 'nntm_search_groups' ) ) {
		return nntm_search_groups();
	}

	return array(
		'all'     => array(
			'label'     => __( 'Tất cả', 'nntm' ),
			'post_type' => array( 'nntm_article', 'nntm_publication', 'nntm_video', 'nntm_talk', 'nntm_retreat', 'post', 'page' ),
		),
		'article' => array(
			'label'     => __( 'Bài viết', 'nntm' ),
			'post_type' => array( 'nntm_article', 'post' ),
		),
		'pdf'     => array(
			'label'     => __( 'Tài liệu PDF', 'nntm' ),
			'post_type' => array( 'nntm_publication' ),
		),
	);
}

/**
 * Fetch results for the search page.
 *
 * @param string $query    Search query.
 * @param string $group    Group key.
 * @param int    $page     1-based page number.
 * @param int    $per_page Results per page.
 * @return array{rows: array[], total: int, counts: array<string, int>}
 */
function nntm_get_search_results( string $query, string $group, int $page, int $per_page ): array {
	if ( function_exists( 'nntm_search_query' ) ) {
		return nntm_search_query( $query, $group, $page, $per_page, true );
	}

	// Fallback: plugin disabled. Plain WP_Query, no highlighting, no PDF pages.
	$groups = nntm_result_groups();
	$group  = isset( $groups[ $group ] ) ? $group : 'all';

	$results = array(
		'rows'   => array(),
		'total'  => 0,
		'counts' => array(),
	);

	if ( '' === trim( $query ) ) {
		return $results;
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

	$results['total'] = (int) $wp_query->found_posts;

	foreach ( $wp_query->posts as $post ) {
		$results['rows'][] = array(
			'title'     => esc_html( get_the_title( $post ) ),
			'excerpt'   => esc_html( wp_trim_words( get_the_excerpt( $post ), 30, '…' ) ),
			'permalink' => (string) get_permalink( $post ),
			'thumb_tag' => (string) get_the_post_thumbnail( $post, 'medium_large', array( 'class' => 'nntm-article-rows__img-el', 'loading' => 'lazy' ) ),
			'label'     => '',
			'cta_1'     => __( 'Đọc bài', 'nntm' ),
			'cta_2'     => __( 'Xem thêm', 'nntm' ),
		);
	}

	return $results;
}

/**
 * Render one result row.
 *
 * The DOM always emits the IMAGE first and the TEXT second; flipping sides is
 * done purely with a CSS class. That keeps the reading and focus order the same
 * on every row for keyboard and screen-reader users — the rule fixed in
 * docs/04-kien-truc.md section 10.
 *
 * @param array $row     Row data. `title` and `excerpt` are pre-escaped and may
 *                       contain `<mark>`.
 * @param bool  $flipped Whether the image goes on the right.
 */
function nntm_render_search_row( array $row, bool $flipped ): void {
	$classes = 'nntm-article-rows__row';

	if ( $flipped ) {
		$classes .= ' nntm-article-rows__row--reversed';
	}

	// Allowlist: only <mark> gets through, and only the highlighter inserts it.
	$allowed_tags = array( 'mark' => array() );
	$thumb        = (string) ( $row['thumb_tag'] ?? '' );
	?>
	<article class="<?php echo esc_attr( $classes ); ?>">
		<span class="nntm-article-rows__img">
			<?php
			echo '' !== $thumb
				? wp_kses_post( $thumb )
				: '<span class="nntm-article-rows__img-placeholder" aria-hidden="true"></span>';
			?>
		</span>

		<div class="nntm-article-rows__text">
			<?php if ( '' !== $row['label'] ) : ?>
				<span class="nntm-article-rows__cat"><?php echo esc_html( $row['label'] ); ?></span>
			<?php endif; ?>

			<h3 class="nntm-article-rows__title">
				<a href="<?php echo esc_url( $row['permalink'] ); ?>">
					<?php echo wp_kses( $row['title'], $allowed_tags ); ?>
				</a>
			</h3>

			<?php if ( '' !== $row['excerpt'] ) : ?>
				<p class="nntm-article-rows__excerpt">
					<?php echo wp_kses( $row['excerpt'], $allowed_tags ); ?>
				</p>
			<?php endif; ?>

			<div class="nntm-article-rows__ctas">
				<a class="nntm-article-rows__cta nntm-article-rows__cta--primary" href="<?php echo esc_url( $row['permalink'] ); ?>">
					<?php echo esc_html( $row['cta_1'] ); ?>
				</a>
				<?php
				if ( '' !== $row['cta_2'] ) :
					// Nút phụ có thể trỏ đi chỗ khác nút chính (ví dụ hàng PDF:
					// nút chính mở đúng trang, nút phụ tải file về).
					$cta_2_url = ! empty( $row['cta_2_url'] ) ? $row['cta_2_url'] : $row['permalink'];
					?>
					<a
						class="nntm-article-rows__cta nntm-article-rows__cta--secondary"
						href="<?php echo esc_url( $cta_2_url ); ?>"
						<?php echo ! empty( $row['cta_2_download'] ) ? 'download rel="nofollow"' : ''; ?>
					>
						<?php echo esc_html( $row['cta_2'] ); ?>
					</a>
				<?php endif; ?>
			</div>
		</div>
	</article>
	<?php
}

/**
 * Load the search page styles, plus the article-rows block stylesheet.
 *
 * The block only enqueues its own CSS when it appears in post content, and the
 * search page contains no blocks — so load it explicitly here.
 *
 * Same pattern as nntm_publication_enqueue_assets() in inc/an-pham.php: do not touch
 * inc/enqueue.php.
 */
function nntm_search_page_assets(): void {
	if ( ! is_search() ) {
		return;
	}

	$block_css = NNTM_THEME_DIR . '/blocks/article-rows/style.css';
	wp_enqueue_style(
		'nntm-article-rows-search',
		NNTM_THEME_URI . '/blocks/article-rows/style.css',
		array( 'nntm-tokens', 'nntm-base' ),
		nntm_asset_version( $block_css )
	);

	$page_css = NNTM_THEME_DIR . '/assets/css/pages/search.css';
	wp_enqueue_style(
		'nntm-search-page',
		NNTM_THEME_URI . '/assets/css/pages/search.css',
		array( 'nntm-article-rows-search' ),
		nntm_asset_version( $page_css )
	);
}
add_action( 'wp_enqueue_scripts', 'nntm_search_page_assets' );
