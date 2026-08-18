<?php
/** Category archive for standard WordPress posts. */
defined( 'ABSPATH' ) || exit;

$css = NNTM_THEME_DIR . '/assets/css/pages/category-post.css';
wp_enqueue_style( 'nntm-category-post', NNTM_THEME_URI . '/assets/css/pages/category-post.css', array( 'nntm-tokens', 'nntm-base', 'nntm-layout' ), function_exists( 'nntm_asset_version' ) ? nntm_asset_version( $css ) : null );
$rows_css = NNTM_THEME_DIR . '/blocks/article-rows/style.css';
if ( file_exists( $rows_css ) ) {
	wp_enqueue_style( 'nntm-category-post-rows', NNTM_THEME_URI . '/blocks/article-rows/style.css', array( 'nntm-category-post' ), function_exists( 'nntm_asset_version' ) ? nntm_asset_version( $rows_css ) : null );
}

get_header();
$cat   = get_queried_object();
$paged = max( 1, (int) get_query_var( 'paged' ) );
$query = new WP_Query(
	array(
		'post_type'      => 'post',
		'post_status'    => 'publish',
		'cat'            => $cat instanceof WP_Term ? (int) $cat->term_id : 0,
		'posts_per_page' => 5,
		'paged'          => $paged,
		'orderby'        => 'date',
		'order'          => 'DESC',
	)
);
?>
<main id="nntm-noi-dung-chinh" class="nntm-category-post nntm-article-rows">
	<div class="nntm-category-post__inner nntm-article-rows__inner">
		<h1 class="nntm-category-post__heading nntm-article-rows__heading"><?php single_cat_title(); ?></h1>
		<?php if ( category_description() ) : ?>
			<div class="nntm-category-post__description"><?php echo wp_kses_post( category_description() ); ?></div>
		<?php endif; ?>

		<?php if ( $query->have_posts() ) : ?>
			<div class="nntm-article-rows__list">
				<?php $index = 0; ?>
				<?php while ( $query->have_posts() ) : $query->the_post(); ?>
					<?php
					$post_obj = get_post();
					if ( function_exists( 'nntm_render_section_article_row' ) && $post_obj instanceof WP_Post ) {
						echo nntm_render_section_article_row( $post_obj, $index, array( 'show_excerpt' => true, 'show_favorite' => true, 'cta_label' => __( 'Xem thêm', 'nntm' ), 'start_side' => 'left' ) ); // phpcs:ignore WordPress.Security.EscapeOutput
					} else {
						$reversed = 1 === $index % 2 ? ' nntm-article-rows__row--reversed' : '';
						?>
						<article class="nntm-article-rows__row<?php echo esc_attr( $reversed ); ?>">
							<a class="nntm-article-rows__img" href="<?php the_permalink(); ?>"><?php the_post_thumbnail( 'large', array( 'class' => 'nntm-article-rows__img-el' ) ); ?></a>
							<div class="nntm-article-rows__text">
								<h2 class="nntm-article-rows__title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
								<p class="nntm-article-rows__excerpt"><?php echo esc_html( wp_trim_words( get_the_excerpt(), 34, '…' ) ); ?></p>
								<div class="nntm-article-rows__actions">
									<?php if ( function_exists( 'nntm_section_render_favorite_button' ) ) echo nntm_section_render_favorite_button( get_the_ID(), 'nntm-article-rows__favorite' ); // phpcs:ignore WordPress.Security.EscapeOutput ?>
									<a class="nntm-article-rows__more" href="<?php the_permalink(); ?>"><?php esc_html_e( 'Xem thêm', 'nntm' ); ?></a>
								</div>
							</div>
						</article>
						<?php
					}
					$index++;
					?>
				<?php endwhile; ?>
			</div>

			<?php
			if ( function_exists( 'nntm_render_section_pagination' ) ) {
				echo nntm_render_section_pagination( $paged, (int) $query->max_num_pages ); // phpcs:ignore WordPress.Security.EscapeOutput
			} else {
				echo '<div class="nntm-category-post__pagination">' . wp_kses_post( paginate_links( array( 'total' => $query->max_num_pages, 'current' => $paged, 'type' => 'list' ) ) ) . '</div>';
			}
			?>
		<?php else : ?>
			<p><?php esc_html_e( 'Chưa có bài viết nào trong chuyên mục này.', 'nntm' ); ?></p>
		<?php endif; ?>
		<?php wp_reset_postdata(); ?>
	</div>
</main>
<?php get_footer(); ?>
