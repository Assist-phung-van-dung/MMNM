<?php

defined( 'ABSPATH' ) || exit;

get_header();

$post_type = get_query_var( 'post_type' );
if ( is_array( $post_type ) ) {
	$post_type = reset( $post_type );
}
$post_type = sanitize_key( (string) $post_type );

if ( '' === $post_type ) {
	$queried_object = get_queried_object();
	if ( $queried_object instanceof WP_Post_Type ) {
		$post_type = sanitize_key( $queried_object->name );
	}
}

$favorite_types = function_exists( 'nntm_section_favorite_post_types' )
	? nntm_section_favorite_post_types()
	: array();
$show_favorite = in_array( $post_type, $favorite_types, true );
?>
<main id="nntm-noi-dung-chinh" class="nntm-article-rows nntm-cpt-archive">
	<div class="nntm-article-rows__inner">
		<h1 class="nntm-article-rows__heading"><?php echo esc_html( post_type_archive_title( '', false ) ); ?></h1>

		<?php if ( have_posts() ) : ?>
			<div class="nntm-article-rows__list">
				<?php
				$index = 0;
				while ( have_posts() ) :
					the_post();
					$post = get_post();

					if ( ! $post instanceof WP_Post ) {
						continue;
					}

					echo nntm_render_section_article_row(
						$post,
						$index,
						array(
							'permalink'     => nntm_cpt_archive_item_url( $post ),
							'show_excerpt'  => true,
							'show_favorite' => $show_favorite,
							'cta_label'     => __( 'Xem thêm', 'nntm' ),
						)
					); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Renderer escapes output internally.
					++$index;
				endwhile;
				?>
			</div>

			<?php
			global $wp_query;
			$current_page = max( 1, absint( get_query_var( 'paged' ) ) );

			if ( function_exists( 'nntm_render_section_pagination' ) ) {
				echo nntm_render_section_pagination( $current_page, (int) $wp_query->max_num_pages ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			} else {
				the_posts_pagination();
			}
			?>
		<?php else : ?>
			<p class="nntm-article-rows__empty"><?php esc_html_e( 'Chưa có nội dung.', 'nntm' ); ?></p>
		<?php endif; ?>
	</div>
</main>
<?php get_footer(); ?>
