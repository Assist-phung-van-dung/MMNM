<?php
/**
 * Chi tiết nntm_article — layout chung theo ảnh "BAI CHI TIET".
 * Desktop 1366px, vùng nội dung 1184px.
 * Nghi Quỹ không đi qua template này.
 *
 * @package NNTM
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();

	$post_id      = get_the_ID();
	$excerpt      = trim( get_the_excerpt() );
	$section_term = nntm_article_deepest_section_term( $post_id );
	$related_args = array(
		'post_type'           => 'nntm_article',
		'post_status'         => 'publish',
		'posts_per_page'      => 9,
		'post__not_in'        => array( $post_id ),
		'ignore_sticky_posts' => true,
		'no_found_rows'       => true,
		'orderby'             => 'date',
		'order'               => 'DESC',
	);

	if ( $section_term instanceof WP_Term ) {
		$related_args['tax_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- lọc bài liên quan cùng phân mục.
			array(
				'taxonomy' => 'nntm_section',
				'field'    => 'term_id',
				'terms'    => array( $section_term->term_id ),
			),
		);
	}

	$related = new WP_Query( $related_args );
	?>
	<main id="nntm-noi-dung-chinh" class="nntm-article-detail">
		<article <?php post_class( 'nntm-article-detail__article' ); ?>>
			<div class="nntm-section-shell nntm-article-detail__inner">
				<h1 class="nntm-article-detail__title"><?php the_title(); ?></h1>

				<p class="nntm-article-detail__meta">
					<span class="nntm-article-detail__meta-dot" aria-hidden="true"></span>
					<?php
					printf(
						/* translators: %s: ngày cập nhật */
						esc_html__( 'Cập nhật %s', 'nntm' ),
						esc_html( get_the_modified_date( 'd. m. Y' ) )
					);
					?>
				</p>

				<?php if ( '' !== $excerpt ) : ?>
					<p class="nntm-article-detail__intro"><?php echo esc_html( $excerpt ); ?></p>
				<?php endif; ?>

				<?php if ( has_post_thumbnail() ) : ?>
					<figure class="nntm-article-detail__media">
						<?php the_post_thumbnail( 'full', array( 'loading' => 'eager' ) ); ?>
					</figure>
				<?php endif; ?>

				<div class="nntm-article-detail__content">
					<?php the_content(); ?>
				</div>

				<div class="nntm-article-detail__favorite">
					<?php echo nntm_section_render_favorite_button( $post_id, 'nntm-article-detail__favorite-button' ); // phpcs:ignore WordPress.Security.EscapeOutput -- helper tự escape. ?>
				</div>
			</div>

			<?php if ( $related->have_posts() ) : ?>
				<section class="nntm-article-related" aria-labelledby="nntm-related-heading">
					<div class="nntm-section-shell nntm-article-related__inner">
						<h2 id="nntm-related-heading" class="nntm-article-related__heading"><?php esc_html_e( 'Bài Viết Liên Quan', 'nntm' ); ?></h2>

						<div class="nntm-related-carousel" data-nntm-related-carousel data-nntm-related-autoplay="5000">
							<button type="button" class="nntm-related-carousel__nav nntm-related-carousel__nav--prev" data-nntm-related-prev aria-label="<?php esc_attr_e( 'Bài liên quan trước', 'nntm' ); ?>">
								<span aria-hidden="true">&larr;</span>
							</button>

							<div class="nntm-related-carousel__track" data-nntm-related-track tabindex="0" role="group" aria-label="<?php esc_attr_e( 'Danh sách bài viết liên quan', 'nntm' ); ?>">
								<?php foreach ( $related->posts as $related_post ) : ?>
									<?php
									$related_title = get_the_title( $related_post );
									$related_url   = get_permalink( $related_post );
									?>
									<article class="nntm-related-card">
										<a class="nntm-related-card__image" href="<?php echo esc_url( $related_url ); ?>" aria-label="<?php echo esc_attr( $related_title ); ?>">
											<?php
											$related_thumb = get_the_post_thumbnail( $related_post, 'medium_large', array( 'loading' => 'lazy', 'alt' => $related_title ) );
											if ( $related_thumb ) {
												echo wp_kses_post( $related_thumb );
											} else {
												echo '<span aria-hidden="true"></span>';
											}
											?>
										</a>
										<div class="nntm-related-card__body">
											<h3 class="nntm-related-card__title"><a href="<?php echo esc_url( $related_url ); ?>"><?php echo esc_html( $related_title ); ?></a></h3>
											<p class="nntm-related-card__excerpt"><?php echo esc_html( wp_trim_words( get_the_excerpt( $related_post ), 23, '…' ) ); ?></p>
											<a class="nntm-related-card__more" href="<?php echo esc_url( $related_url ); ?>"><?php esc_html_e( 'Xem thêm', 'nntm' ); ?></a>
										</div>
									</article>
								<?php endforeach; ?>
							</div>

							<button type="button" class="nntm-related-carousel__nav nntm-related-carousel__nav--next" data-nntm-related-next aria-label="<?php esc_attr_e( 'Bài liên quan tiếp theo', 'nntm' ); ?>">
								<span aria-hidden="true">&rarr;</span>
							</button>
						</div>
					</div>
				</section>
			<?php endif; ?>
		</article>
	</main>
	<?php
	wp_reset_postdata();
endwhile;

get_footer();
