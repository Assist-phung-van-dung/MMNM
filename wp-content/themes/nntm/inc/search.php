<?php

defined( 'ABSPATH' ) || exit;

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

function nntm_get_search_results( string $query, string $group, int $page, int $per_page ): array {
	if ( function_exists( 'nntm_search_query' ) ) {
		return nntm_search_query( $query, $group, $page, $per_page, true );
	}

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

function nntm_render_search_row( array $row, bool $flipped ): void {
	$classes = 'nntm-article-rows__row';

	if ( $flipped ) {
		$classes .= ' nntm-article-rows__row--reversed';
	}

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
