<?php

defined( 'ABSPATH' ) || exit;

function nntm_section_archive_query( WP_Query $query ): void {
	if ( is_admin() || ! $query->is_main_query() || ! $query->is_tax( 'nntm_section' ) ) {
		return;
	}

	$query->set( 'post_type', 'nntm_article' );
	$query->set( 'post_status', 'publish' );
	$query->set( 'posts_per_page', 5 );
	$query->set( 'orderby', 'date' );
	$query->set( 'order', 'DESC' );
}
add_action( 'pre_get_posts', 'nntm_section_archive_query' );

function nntm_enqueue_section_article_assets(): void {
	$is_favorites = function_exists( 'nntm_section_is_favorites_request' ) && nntm_section_is_favorites_request();
	if ( ! is_tax( 'nntm_section' ) && ! is_singular( 'nntm_article' ) && ! $is_favorites ) {
		return;
	}

	$page_css = NNTM_THEME_DIR . '/assets/css/pages/section-article.css';
	wp_enqueue_style(
		'nntm-section-article',
		NNTM_THEME_URI . '/assets/css/pages/section-article.css',
		array( 'nntm-tokens', 'nntm-base', 'nntm-layout' ),
		nntm_asset_version( $page_css )
	);

	if ( is_tax( 'nntm_section' ) || $is_favorites ) {
		$rows_css = NNTM_THEME_DIR . '/blocks/article-rows/style.css';
		wp_enqueue_style(
			'nntm-section-article-rows',
			NNTM_THEME_URI . '/blocks/article-rows/style.css',
			array( 'nntm-tokens', 'nntm-base', 'nntm-layout', 'nntm-favorites' ),
			nntm_asset_version( $rows_css )
		);
	}

	if ( is_singular( 'nntm_article' ) ) {
		$page_js = NNTM_THEME_DIR . '/assets/js/section-article.js';
		wp_enqueue_script(
			'nntm-section-article',
			NNTM_THEME_URI . '/assets/js/section-article.js',
			array(),
			nntm_asset_version( $page_js ),
			true
		);
	}
}
add_action( 'wp_enqueue_scripts', 'nntm_enqueue_section_article_assets', 40 );

function nntm_article_deepest_section_term( int $post_id ): ?WP_Term {
	$terms = get_the_terms( $post_id, 'nntm_section' );
	if ( empty( $terms ) || is_wp_error( $terms ) ) {
		return null;
	}

	usort(
		$terms,
		static function ( WP_Term $a, WP_Term $b ): int {
			$depth_a = count( get_ancestors( $a->term_id, 'nntm_section', 'taxonomy' ) );
			$depth_b = count( get_ancestors( $b->term_id, 'nntm_section', 'taxonomy' ) );
			return $depth_b <=> $depth_a;
		}
	);

	return $terms[0] instanceof WP_Term ? $terms[0] : null;
}

function nntm_render_section_article_row( WP_Post $post, int $index, array $args = array() ): string {
	$defaults = array(
		'show_excerpt'  => true,
		'show_favorite' => true,
		'cta_label'     => __( 'Xem thêm', 'nntm' ),
		'start_side'    => 'left',
		'permalink'     => '',
	);
	$args = wp_parse_args( $args, $defaults );

	$index          = max( 0, $index );
	$start_on_right = ( 'right' === $args['start_side'] );
	$image_on_right = $start_on_right ? ( 0 === $index % 2 ) : ( 1 === $index % 2 );
	$classes        = array( 'nntm-article-rows__row' );
	if ( $image_on_right ) {
		$classes[] = 'nntm-article-rows__row--reversed';
	}

	$permalink = '' !== trim( (string) $args['permalink'] ) ? (string) $args['permalink'] : get_permalink( $post );
	$title     = get_the_title( $post );
	$thumbnail = get_the_post_thumbnail(
		$post,
		'large',
		array(
			'class'   => 'nntm-article-rows__img-el',
			'loading' => 'lazy',
			'alt'     => $title,
		)
	);

	ob_start();
	?>
	<article class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>">
		<a class="nntm-article-rows__img" href="<?php echo esc_url( $permalink ); ?>" aria-label="<?php echo esc_attr( $title ); ?>">
			<?php
			if ( $thumbnail ) {
				echo wp_kses_post( $thumbnail );
			} else {
				echo '<span class="nntm-article-rows__img-placeholder" aria-hidden="true"></span>';
			}
			?>
		</a>

		<div class="nntm-article-rows__text">
			<h2 class="nntm-article-rows__title">
				<a href="<?php echo esc_url( $permalink ); ?>"><?php echo esc_html( $title ); ?></a>
			</h2>

			<?php if ( ! empty( $args['show_excerpt'] ) ) : ?>
				<p class="nntm-article-rows__excerpt">
					<?php echo esc_html( wp_trim_words( get_the_excerpt( $post ), 34, '…' ) ); ?>
				</p>
			<?php endif; ?>

			<div class="nntm-article-rows__actions">
				<?php if ( ! empty( $args['show_favorite'] ) ) : ?>
					<?php echo nntm_section_render_favorite_button( $post->ID, 'nntm-article-rows__favorite' );  ?>
				<?php endif; ?>

				<a class="nntm-article-rows__more" href="<?php echo esc_url( $permalink ); ?>">
					<?php echo esc_html( (string) $args['cta_label'] ); ?>
				</a>
			</div>
		</div>
	</article>
	<?php
	return trim( (string) ob_get_clean() );
}

function nntm_section_pagination_url( int $page, string $base_url = '' ): string {
	$page = max( 1, $page );
	if ( '' === $base_url ) {
		return get_pagenum_link( $page );
	}

	$base_url = trailingslashit( $base_url );
	return 1 === $page ? $base_url : trailingslashit( $base_url . 'page/' . $page );
}

function nntm_render_section_pagination( int $current, int $total, string $base_url = '' ): string {
	$current = max( 1, $current );
	$total   = max( 1, $total );

	if ( $total <= 1 ) {
		return '';
	}

	$pages = array();
	for ( $page = 1; $page <= $total; $page++ ) {
		if ( $total <= 7 || 1 === $page || $total === $page || abs( $page - $current ) <= 2 ) {
			$pages[] = $page;
		}
	}

	ob_start();
	?>
	<nav class="nntm-section-pagination" aria-label="<?php esc_attr_e( 'Phân trang danh sách bài viết', 'nntm' ); ?>">
		<?php if ( $current > 1 ) : ?>
			<a class="nntm-section-pagination__arrow" href="<?php echo esc_url( nntm_section_pagination_url( $current - 1, $base_url ) ); ?>" rel="prev">
				<span aria-hidden="true">&larr;</span><span class="nntm-sr-only"><?php esc_html_e( 'Trang trước', 'nntm' ); ?></span>
			</a>
		<?php else : ?>
			<span class="nntm-section-pagination__arrow is-disabled" aria-disabled="true"><span aria-hidden="true">&larr;</span></span>
		<?php endif; ?>

		<div class="nntm-section-pagination__numbers">
			<?php
			$previous = 0;
			foreach ( $pages as $page ) :
				if ( $previous > 0 && $page > $previous + 1 ) {
					echo '<span class="nntm-section-pagination__ellipsis" aria-hidden="true">&hellip;</span>';
				}

				if ( $page === $current ) {
					printf( '<span class="nntm-section-pagination__number is-current" aria-current="page">%d</span>', (int) $page );
				} else {
					printf(
						'<a class="nntm-section-pagination__number" href="%1$s"><span class="nntm-sr-only">%2$s </span>%3$d</a>',
						esc_url( nntm_section_pagination_url( $page, $base_url ) ),
						esc_html__( 'Trang', 'nntm' ),
						(int) $page
					);
				}
				$previous = $page;
			endforeach;
			?>
		</div>

		<?php if ( $current < $total ) : ?>
			<a class="nntm-section-pagination__arrow" href="<?php echo esc_url( nntm_section_pagination_url( $current + 1, $base_url ) ); ?>" rel="next">
				<span aria-hidden="true">&rarr;</span><span class="nntm-sr-only"><?php esc_html_e( 'Trang sau', 'nntm' ); ?></span>
			</a>
		<?php else : ?>
			<span class="nntm-section-pagination__arrow is-disabled" aria-disabled="true"><span aria-hidden="true">&rarr;</span></span>
		<?php endif; ?>
	</nav>
	<?php
	return trim( (string) ob_get_clean() );
}
require_once __DIR__ . '/cpt-archive.php';
