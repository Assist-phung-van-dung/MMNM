<?php
/**
 * Chi tiết Nghi Quỹ / Ấn phẩm — cùng cấu trúc với chi tiết bài viết thường.
 * Desktop source-of-truth: outer 1366px, content 1184px.
 *
 * @package NNTM
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();

	$post_id = get_the_ID();
	$excerpt = trim( get_the_excerpt() );

	$topic_terms = taxonomy_exists( 'nntm_topic' ) ? get_the_terms( $post_id, 'nntm_topic' ) : false;
	$topic_id    = ( is_array( $topic_terms ) && ! empty( $topic_terms ) ) ? (int) $topic_terms[0]->term_id : 0;

	$related_args = array(
		'post_type'           => 'nntm_publication',
		'post_status'         => 'publish',
		'posts_per_page'      => 9,
		'post__not_in'        => array( $post_id ),
		'ignore_sticky_posts' => true,
		'no_found_rows'       => true,
		'orderby'             => 'date',
		'order'               => 'DESC',
	);

	if ( $topic_id > 0 ) {
		$related_args['tax_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- cần lọc cùng chủ đề.
			array(
				'taxonomy' => 'nntm_topic',
				'field'    => 'term_id',
				'terms'    => array( $topic_id ),
			),
		);
	}

	$related  = new WP_Query( $related_args );
	$pdf_url  = nntm_an_pham_pdf_url( $post_id );
	$bi_khoa  = nntm_an_pham_bi_khoa( $post_id );
	$duoc_xem = nntm_an_pham_can_access( $post_id );
	?>

	<main id="nntm-noi-dung-chinh" class="nntm-an-pham-detail">
		<article <?php post_class( 'nntm-an-pham-detail__article' ); ?>>
			<section class="nntm-an-pham__than">
				<div class="nntm-an-pham__khung">
					<h1 class="nntm-an-pham__tieu-de"><?php the_title(); ?></h1>

					<p class="nntm-an-pham__ngay">
						<span class="nntm-an-pham__cham" aria-hidden="true"></span>
						<?php
						printf(
							/* translators: %s: ngày cập nhật */
							esc_html__( 'Cập nhật %s', 'nntm' ),
							esc_html( get_the_modified_date( 'd. m. Y' ) )
						);
						?>
					</p>

					<?php if ( '' !== $excerpt ) : ?>
						<p class="nntm-an-pham__mo-dau"><?php echo esc_html( $excerpt ); ?></p>
					<?php endif; ?>

					<?php if ( has_post_thumbnail() ) : ?>
						<figure class="nntm-an-pham__anh-bia">
							<?php
							the_post_thumbnail(
								'full',
								array(
									'class'   => 'nntm-an-pham__anh-bia-img',
									'alt'     => the_title_attribute( array( 'echo' => false ) ),
									'loading' => 'eager',
								)
							);
							?>
						</figure>
					<?php endif; ?>

					<div class="nntm-an-pham__noi-dung">
						<?php the_content(); ?>
					</div>

					<div class="nntm-an-pham__hang-nut">
						<?php if ( function_exists( 'nntm_section_render_favorite_button' ) ) : ?>
							<?php echo nntm_section_render_favorite_button( $post_id, 'nntm-an-pham__favorite-button' ); // phpcs:ignore WordPress.Security.EscapeOutput -- helper tự escape. ?>
						<?php else : ?>
							<button
								type="button"
								class="nntm-an-pham__yeu-thich"
								data-nntm-favorite="<?php echo esc_attr( (string) $post_id ); ?>"
								<?php echo is_user_logged_in() ? '' : 'data-nntm-auth-modal="dang-nhap"'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- thuộc tính cố định. ?>
							>
								<svg class="nntm-an-pham__tim" viewBox="0 0 24 24" width="22" height="22" aria-hidden="true" focusable="false">
									<path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78L12 21.23l8.84-8.84a5.5 5.5 0 0 0 0-7.78Z" />
								</svg>
								<span><?php esc_html_e( 'Yêu thích', 'nntm' ); ?></span>
							</button>
						<?php endif; ?>

						<?php if ( '' !== $pdf_url && $duoc_xem ) : ?>
							<button type="button" class="nntm-an-pham__doc-nut" data-nntm-an-pham-doc>
								<?php esc_html_e( 'Đọc ấn phẩm', 'nntm' ); ?>
							</button>
						<?php elseif ( $bi_khoa ) : ?>
							<p class="nntm-an-pham__khoa">
								<?php esc_html_e( 'Ấn phẩm này yêu cầu thanh toán mới xem được nội dung đầy đủ.', 'nntm' ); ?>
							</p>
						<?php endif; ?>
					</div>
				</div>
			</section>

			<?php if ( $related->have_posts() ) : ?>
				<section class="nntm-an-pham-related" aria-labelledby="nntm-an-pham-related-heading">
					<div class="nntm-an-pham__khung nntm-an-pham-related__inner">
						<h2 id="nntm-an-pham-related-heading" class="nntm-an-pham-related__heading"><?php esc_html_e( 'Cùng chuyên mục', 'nntm' ); ?></h2>

						<div class="nntm-an-pham-carousel" data-nntm-an-pham-carousel data-nntm-an-pham-autoplay="5000">
							<button type="button" class="nntm-an-pham-carousel__nav nntm-an-pham-carousel__nav--prev" data-nntm-an-pham-prev aria-label="<?php esc_attr_e( 'Ấn phẩm trước', 'nntm' ); ?>">
								<span class="nntm-an-pham-carousel__nav-icon" aria-hidden="true"></span>
							</button>

							<div class="nntm-an-pham-carousel__track" data-nntm-an-pham-track tabindex="0" role="group" aria-label="<?php esc_attr_e( 'Danh sách ấn phẩm cùng chuyên mục', 'nntm' ); ?>">
								<?php foreach ( $related->posts as $related_post ) : ?>
									<?php
									$related_title = get_the_title( $related_post );
									$related_url   = get_permalink( $related_post );
									?>
									<article class="nntm-an-pham-card">
										<a class="nntm-an-pham-card__image" href="<?php echo esc_url( $related_url ); ?>" aria-label="<?php echo esc_attr( $related_title ); ?>">
											<?php
											$thumb = get_the_post_thumbnail(
												$related_post,
												'medium_large',
												array(
													'loading' => 'lazy',
													'alt'     => $related_title,
												)
											);
											if ( $thumb ) {
												echo wp_kses_post( $thumb );
											} else {
												echo '<span aria-hidden="true"></span>';
											}
											?>
										</a>
										<div class="nntm-an-pham-card__body">
											<h3 class="nntm-an-pham-card__title"><a href="<?php echo esc_url( $related_url ); ?>"><?php echo esc_html( $related_title ); ?></a></h3>
											<a class="nntm-an-pham-card__more" href="<?php echo esc_url( $related_url ); ?>"><?php esc_html_e( 'Xem thêm', 'nntm' ); ?></a>
										</div>
									</article>
								<?php endforeach; ?>
							</div>

							<button type="button" class="nntm-an-pham-carousel__nav nntm-an-pham-carousel__nav--next" data-nntm-an-pham-next aria-label="<?php esc_attr_e( 'Ấn phẩm tiếp theo', 'nntm' ); ?>">
								<span class="nntm-an-pham-carousel__nav-icon" aria-hidden="true"></span>
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
