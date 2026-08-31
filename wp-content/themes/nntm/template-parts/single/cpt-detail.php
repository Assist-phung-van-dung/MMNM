<?php

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();

	$post_id   = get_the_ID();
	$post_type = sanitize_key( (string) get_post_type( $post_id ) );
	$excerpt   = trim( (string) get_the_excerpt() );
	$type_obj  = get_post_type_object( $post_type );
	$type_name = $type_obj instanceof WP_Post_Type ? $type_obj->labels->singular_name : __( 'Nội dung', 'nntm' );
	?>
	<main id="nntm-noi-dung-chinh" class="nntm-article-detail nntm-cpt-detail">
		<article <?php post_class( 'nntm-article-detail__article nntm-cpt-detail__article' ); ?>>
			<div class="nntm-article-detail__inner">
				<h1 class="nntm-article-detail__title"><?php the_title(); ?></h1>
					<?php
					/* Bài không chọn nhạc nền thì hàm trả chuỗi rỗng, không in ra gì. */
					if ( function_exists( 'nntm_render_nhac_nen' ) ) {
						echo nntm_render_nhac_nen( (int) $post_id );  // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
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

				<?php
				$retreat_archive_url = get_post_type_archive_link( 'nntm_retreat' );
				if ( ! is_string( $retreat_archive_url ) || '' === trim( $retreat_archive_url ) ) {
					$retreat_archive_url = home_url( '/khoa-tu/' );
				}
				?>

				<div class="nntm-cpt-detail__actions">
					<?php
					if ( function_exists( 'nntm_section_render_favorite_button' ) ) {
						echo nntm_section_render_favorite_button( $post_id, 'nntm-cpt-detail__favorite-button' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					}
					?>
					<?php
					if ( function_exists( 'nntm_render_chia_se' ) ) {
						echo nntm_render_chia_se( (int) $post_id, array( 'class_nut' => 'nntm-bai-hanh-gia__dang-ky' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					}
					?>
				</div>
			</div>

			<div class="nntm-article-detail__related nntm-cpt-detail__related">
				<?php
				echo render_block(
					array(
						'blockName'    => 'nntm/card-list',
						'attrs'        => array(
							'heading'          => sprintf(
								/* translators: %s: post type label. */
								__( '%s liên quan', 'nntm' ),
								$type_name
							),
							'postType'         => $post_type,
							'variant'          => 'article',
							'layout'           => 'carousel',
							'postsPerPage'     => 8,
							'excludePostId'    => $post_id,
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
				); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				?>
			</div>
		</article>
	</main>
	<?php
endwhile;

get_footer();
