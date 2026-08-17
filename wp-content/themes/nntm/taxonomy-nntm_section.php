<?php
/**
 * Danh sách bài của một phân mục / term con nntm_section.
 * Thiết kế dùng chung cho mọi phân mục; Nghi Quỹ là Page riêng nên không
 * đi qua template này.
 *
 * @package NNTM
 */

defined( 'ABSPATH' ) || exit;

get_header();

$term = get_queried_object();
?>
<main id="nntm-noi-dung-chinh" class="nntm-article-rows nntm-article-rows--taxonomy">
	<div class="nntm-article-rows__inner">
		<h1 class="nntm-article-rows__heading">
			<?php echo esc_html( $term instanceof WP_Term ? $term->name : single_term_title( '', false ) ); ?>
		</h1>

		<?php if ( have_posts() ) : ?>
			<div class="nntm-article-rows__list">
				<?php
				$row_index = 0;
				while ( have_posts() ) :
					the_post();
					$post = get_post();
					if ( $post instanceof WP_Post ) {
						echo nntm_render_section_article_row( // phpcs:ignore WordPress.Security.EscapeOutput -- helper tự escape.
							$post,
							$row_index,
							array(
								'show_excerpt'  => true,
								'show_favorite' => true,
								'cta_label'     => __( 'Xem thêm', 'nntm' ),
								'start_side'    => 'left',
							)
						);
						++$row_index;
					}
				endwhile;
				?>
			</div>

			<?php
			global $wp_query;
			$current_page = max( 1, (int) get_query_var( 'paged' ) );
			echo nntm_render_section_pagination( $current_page, (int) $wp_query->max_num_pages ); // phpcs:ignore WordPress.Security.EscapeOutput -- helper tự escape.
			?>
		<?php else : ?>
			<p class="nntm-article-rows__empty"><?php esc_html_e( 'Chưa có bài viết nào trong mục này.', 'nntm' ); ?></p>
		<?php endif; ?>
	</div>
</main>
<?php
get_footer();
