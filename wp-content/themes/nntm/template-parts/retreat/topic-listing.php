<?php
/**
 * Listing dùng chung cho chu-de/khoa-tu và chu-de/lich-tu.
 * Giao diện đồng nhất với phan-muc/nguyen-thuy.
 *
 * @package NNTM
 */

defined( 'ABSPATH' ) || exit;

get_header();
$term = get_queried_object();
?>
<main id="nntm-noi-dung-chinh" class="nntm-article-rows nntm-retreat-topic">
	<div class="nntm-article-rows__inner">
		<h1 class="nntm-article-rows__heading"><?php echo esc_html( $term instanceof WP_Term ? $term->name : single_term_title( '', false ) ); ?></h1>

		<?php if ( have_posts() ) : ?>
			<div class="nntm-article-rows__list">
				<?php
				$index = 0;
				while ( have_posts() ) :
					the_post();
					$post = get_post();
					if ( $post instanceof WP_Post ) {
						echo nntm_render_retreat_topic_row( $post, $index ); // phpcs:ignore WordPress.Security.EscapeOutput -- helper tự escape.
						++$index;
					}
				endwhile;
				?>
			</div>

			<?php
			global $wp_query;
			$current_page = max( 1, absint( get_query_var( 'paged' ) ) );
			if ( function_exists( 'nntm_render_section_pagination' ) ) {
				echo nntm_render_section_pagination( $current_page, (int) $wp_query->max_num_pages ); // phpcs:ignore WordPress.Security.EscapeOutput -- helper tự escape.
			} else {
				the_posts_pagination();
			}
			?>
		<?php else : ?>
			<p class="nntm-article-rows__empty"><?php esc_html_e( 'Chưa có nội dung trong chủ đề này.', 'nntm' ); ?></p>
		<?php endif; ?>
	</div>
</main>
<?php get_footer(); ?>
