<?php
 
defined( 'ABSPATH' ) || exit;

if ( 'post' !== get_post_type() ) {
	get_header();
	?>
	<main id="nntm-noi-dung-chinh" class="nntm-container nntm-mt-8 nntm-mb-8">
		<?php while ( have_posts() ) : the_post(); get_template_part( 'template-parts/content/content', 'article' ); endwhile; ?>
	</main>
	<?php
	get_footer();
	return;
}

$css = NNTM_THEME_DIR . '/assets/css/pages/category-post.css';
wp_enqueue_style( 'nntm-category-post', NNTM_THEME_URI . '/assets/css/pages/category-post.css', array( 'nntm-tokens', 'nntm-base', 'nntm-layout' ), function_exists( 'nntm_asset_version' ) ? nntm_asset_version( $css ) : null );
get_header();

while ( have_posts() ) :
	the_post();
	$categories = get_the_category();
	$category   = ! empty( $categories ) ? $categories[0] : null;
	$excerpt    = get_the_excerpt();
	?>
	<main id="nntm-noi-dung-chinh" class="nntm-post-detail">
		<article class="nntm-post-detail__main">
			<div class="nntm-post-detail__inner">
				<h1 class="nntm-post-detail__title"><?php the_title(); ?></h1>
				<p class="nntm-post-detail__meta"><span class="nntm-post-detail__meta-dot" aria-hidden="true"></span><?php printf( esc_html__( 'Cập nhật %s', 'nntm' ), esc_html( get_the_modified_date( 'd. m. Y' ) ) ); ?></p>
				<?php if ( '' !== trim( $excerpt ) ) : ?><p class="nntm-post-detail__intro"><?php echo esc_html( $excerpt ); ?></p><?php endif; ?>
				<?php if ( has_post_thumbnail() ) : ?><figure class="nntm-post-detail__media"><?php the_post_thumbnail( 'full' ); ?></figure><?php endif; ?>
				<div class="nntm-post-detail__content"><?php the_content(); ?></div>
				<div class="nntm-post-detail__favorite nntm-post-detail__actions">
					<?php if ( function_exists( 'nntm_section_render_favorite_button' ) ) { echo nntm_section_render_favorite_button( get_the_ID(), 'nntm-post-detail__favorite-button' ); } ?>
					<?php if ( function_exists( 'nntm_render_chia_se' ) ) { echo nntm_render_chia_se( (int) get_the_ID(), array( 'class_nut' => 'nntm-post-detail__share' ) ); } ?>
				</div>
			</div>
		</article>

		<?php if ( $category instanceof WP_Term ) : ?>
			<section class="nntm-post-detail__related">
				<?php
				echo render_block(
					array(
						'blockName' => 'nntm/card-list',
						'attrs' => array(
							'heading' => __( 'Bài Viết Liên Quan', 'nntm' ),
							'postType' => 'post',
							'taxonomy' => 'category',
							'termId' => (int) $category->term_id,
							'variant' => 'article',
							'layout' => 'carousel',
							'postsPerPage' => 8,
							'excludePostId' => get_the_ID(),
							'autoplay' => true,
							'autoplayInterval' => 5,
							'background' => 'none',
							'showDate' => false,
							'showCategory' => false,
							'showCardCta' => true,
							'cardCtaLabel' => __( 'Xem thêm', 'nntm' ),
						),
						'innerBlocks' => array(), 'innerHTML' => '', 'innerContent' => array(),
					)
				);  
				?>
			</section>
		<?php endif; ?>
	</main>
	<?php
endwhile;
get_footer();
