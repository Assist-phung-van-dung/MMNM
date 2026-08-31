<?php

defined( 'ABSPATH' ) || exit;

get_header();

$nntm_cap_hanh_gia = function_exists( 'nntm_bai_thuoc_hanh_gia' )
	? nntm_bai_thuoc_hanh_gia( get_queried_object() )
	: null;

if ( null === $nntm_cap_hanh_gia ) {
	while ( have_posts() ) {
		the_post();

		$nntm_post_id = get_the_ID();
		$nntm_excerpt = trim( get_the_excerpt() );

		$nntm_term_id_hien_tai = 0;
		$nntm_terms_hien_tai   = get_the_terms( $nntm_post_id, 'nntm_section' );
		if ( is_array( $nntm_terms_hien_tai ) && ! empty( $nntm_terms_hien_tai ) ) {
			usort(
				$nntm_terms_hien_tai,
				static function ( WP_Term $a, WP_Term $b ): int {
					$depth_a = count( get_ancestors( $a->term_id, 'nntm_section', 'taxonomy' ) );
					$depth_b = count( get_ancestors( $b->term_id, 'nntm_section', 'taxonomy' ) );
					return $depth_b <=> $depth_a;
				}
			);
			$nntm_term_id_hien_tai = (int) $nntm_terms_hien_tai[0]->term_id;
		}

		$nntm_lien_quan = nntm_bai_lien_quan_nguon( (int) $nntm_post_id, $nntm_term_id_hien_tai );
		?>
		<main id="nntm-noi-dung-chinh" class="nntm-article-detail">
			<article <?php post_class( 'nntm-article-detail__article' ); ?>>
				<div class="nntm-article-detail__inner">
					<h1 class="nntm-article-detail__title"><?php the_title(); ?></h1>

					<?php
					/* Bài không chọn nhạc nền thì hàm trả chuỗi rỗng, không in ra gì. */
					if ( function_exists( 'nntm_render_nhac_nen' ) ) {
						echo nntm_render_nhac_nen( (int) $nntm_post_id );  // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					}
					?>

					<p class="nntm-article-detail__meta">
						<span class="nntm-article-detail__meta-dot" aria-hidden="true"></span>
						<?php
						printf(
							 
							esc_html__( 'Cập nhật %s', 'nntm' ),
							esc_html( get_the_modified_date( 'd. m. Y' ) )
						);
						?>
					</p>

					<?php if ( '' !== $nntm_excerpt ) : ?>
						<p class="nntm-article-detail__intro"><?php echo esc_html( $nntm_excerpt ); ?></p>
					<?php endif; ?>

					<?php if ( has_post_thumbnail() ) : ?>
						<figure class="nntm-article-detail__media">
							<?php the_post_thumbnail( 'full', array( 'loading' => 'eager' ) ); ?>
						</figure>
					<?php endif; ?>

					<div class="nntm-article-detail__content">
						<?php the_content(); ?>
					</div>

					<div class="nntm-article-detail__favorite nntm-article-detail__actions">
						<?php
						if ( function_exists( 'nntm_section_render_favorite_button' ) ) {
							echo nntm_section_render_favorite_button( $nntm_post_id, 'nntm-article-detail__favorite-button' );  
						}
						?>
						<?php
						if ( function_exists( 'nntm_render_chia_se' ) ) {
							echo nntm_render_chia_se( (int) $nntm_post_id, array( 'class_nut' => 'nntm-article-detail__share' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						}
						?>
					</div>
				</div>

				<div class="nntm-article-detail__related">
					<?php
					echo render_block(
						array(
							'blockName'    => 'nntm/card-list',
							'attrs'        => array(
								'heading'          => $nntm_lien_quan['heading'],
								'postType'         => 'nntm_article',
								'taxonomy'         => $nntm_lien_quan['taxonomy'],
								'termId'           => $nntm_lien_quan['term_id'],
								'variant'          => 'article',
								'layout'           => 'carousel',
								'postsPerPage'     => 8,
								'excludePostId'    => $nntm_post_id,
								'autoplay'         => true,
								'autoplayInterval' => 5,
								'background'       => 'none',
								'showDate'         => false,
								'showCategory'     => false,
								'showCardCta'      => true,
								'cardCtaLabel'     => __( 'Xem thêm', 'nntm' ),
							),
							'innerBlocks'  => array(),
							'innerHTML'    => '',
							'innerContent' => array(),
						)
					);  
					?>
				</div>
			</article>
		</main>
		<?php
	}
} else {
	while ( have_posts() ) {
		the_post();

		$nntm_class_bien_the = ( 'kim_cuong' === $nntm_cap_hanh_gia )
			? 'nntm-bai-hanh-gia--kim-cuong'
			: 'nntm-bai-hanh-gia--dai-si';

		$nntm_ngay_cap_nhat = get_the_modified_date( 'd. m. Y' );

		$nntm_terms_hien_tai   = get_the_terms( get_the_ID(), 'nntm_section' );
		$nntm_term_id_hien_tai = ( is_array( $nntm_terms_hien_tai ) && ! empty( $nntm_terms_hien_tai ) )
			? (int) $nntm_terms_hien_tai[0]->term_id
			: 0;

		$nntm_lien_quan = nntm_bai_lien_quan_nguon( (int) get_the_ID(), $nntm_term_id_hien_tai );
		?>
		<main id="nntm-noi-dung-chinh">
			<section class="nntm-bai-hanh-gia__than <?php echo esc_attr( $nntm_class_bien_the ); ?>">
				<div class="nntm-bai-hanh-gia__khung">
					<h1 class="nntm-bai-hanh-gia__tieu-de"><?php the_title(); ?></h1>

					<?php
					if ( function_exists( 'nntm_render_nhac_nen' ) ) {
						echo nntm_render_nhac_nen( (int) get_the_ID() );  // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					}
					?>

					<p class="nntm-bai-hanh-gia__ngay">
						<span class="nntm-bai-hanh-gia__cham" aria-hidden="true"></span>
						<?php
						printf(
							 
							esc_html__( 'Cập nhật %s', 'nntm' ),
							esc_html( $nntm_ngay_cap_nhat )
						);
						?>
					</p>

					<div class="nntm-bai-hanh-gia__noi-dung">
						<?php the_content(); ?>
					</div>

					<div class="nntm-bai-hanh-gia__hang-nut">
						<?php
						if ( function_exists( 'nntm_section_render_favorite_button' ) ) {
							echo nntm_section_render_favorite_button( get_the_ID(), 'nntm-bai-hanh-gia__yeu-thich' );  
						}
						?>
						<?php
						if ( function_exists( 'nntm_render_chia_se' ) ) {
							echo nntm_render_chia_se( (int) get_the_ID(), array( 'class_nut' => 'nntm-bai-hanh-gia__dang-ky' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						}
						?>
					</div>
				</div>
			</section>

			<div class="nntm-bai-hanh-gia__lien-quan">
				<?php
				echo render_block(
					array(
						'blockName'    => 'nntm/card-list',
						'attrs'        => array(
							'heading'          => $nntm_lien_quan['heading'],
							'postType'         => 'nntm_article',
							'taxonomy'         => $nntm_lien_quan['taxonomy'],
							'termId'           => $nntm_lien_quan['term_id'],
							'variant'          => 'article',
							'layout'           => 'carousel',
							'postsPerPage'     => 8,
							'excludePostId'    => get_the_ID(),
							'autoplay'         => true,
							'autoplayInterval' => 5,
							'background'       => 'none',
							'showDate'         => false,
							'showCategory'     => false,
							'showCardCta'      => true,
							'cardCtaLabel'     => __( 'Xem thêm', 'nntm' ),
						),
						'innerBlocks'  => array(),
						'innerHTML'    => '',
						'innerContent' => array(),
					)
				);  
				?>
			</div>
		</main>
		<?php
	}
}

get_footer();
