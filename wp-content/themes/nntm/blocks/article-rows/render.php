<?php
/**
 * Render block nntm/article-rows — dùng chung giao diện danh sách phân mục.
 * Markup từng hàng được chia sẻ với taxonomy-nntm_section.php qua helper
 * nntm_render_section_article_row().
 *
 * @package NNTM
 * @var array $attributes
 */

defined( 'ABSPATH' ) || exit;

$allowed_post_types = array( 'nntm_article', 'nntm_publication', 'nntm_talk', 'nntm_video', 'post' );
$post_type          = isset( $attributes['postType'] ) ? sanitize_key( (string) $attributes['postType'] ) : 'nntm_article';
if ( ! in_array( $post_type, $allowed_post_types, true ) ) {
	$post_type = 'nntm_article';
}

$posts_per_page = isset( $attributes['postsPerPage'] ) ? absint( $attributes['postsPerPage'] ) : 5;
$posts_per_page = max( 1, min( 12, $posts_per_page ) );

$order_by_choice = isset( $attributes['orderBy'] ) ? sanitize_key( (string) $attributes['orderBy'] ) : 'newest';
if ( ! in_array( $order_by_choice, array( 'newest', 'oldest', 'title' ), true ) ) {
	$order_by_choice = 'newest';
}

$start_side = isset( $attributes['startSide'] ) ? sanitize_key( (string) $attributes['startSide'] ) : 'left';
if ( ! in_array( $start_side, array( 'left', 'right' ), true ) ) {
	$start_side = 'left';
}

$show_excerpt  = ! isset( $attributes['showExcerpt'] ) || ! empty( $attributes['showExcerpt'] );
$show_favorite = ! isset( $attributes['showFavorite'] ) || ! empty( $attributes['showFavorite'] );
$show_paging   = ! empty( $attributes['showPaging'] );
$heading       = isset( $attributes['heading'] ) ? (string) $attributes['heading'] : '';
$cta_label     = isset( $attributes['secondaryCtaLabel'] ) && '' !== trim( (string) $attributes['secondaryCtaLabel'] )
	? (string) $attributes['secondaryCtaLabel']
	: __( 'Xem thêm', 'nntm' );

$taxonomy = isset( $attributes['taxonomy'] ) ? sanitize_key( (string) $attributes['taxonomy'] ) : '';
$term_id  = isset( $attributes['termId'] ) ? absint( $attributes['termId'] ) : 0;

$paged = $show_paging ? max( 1, absint( get_query_var( 'paged' ) ) ) : 1;
if ( $show_paging && isset( $_GET['paged'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- chỉ đọc số trang.
	$paged = max( 1, absint( wp_unslash( $_GET['paged'] ) ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
}

$query_args = array(
	'post_type'           => $post_type,
	'post_status'         => 'publish',
	'posts_per_page'      => $posts_per_page,
	'paged'               => $paged,
	'ignore_sticky_posts' => true,
	'no_found_rows'       => ! $show_paging,
);

switch ( $order_by_choice ) {
	case 'oldest':
		$query_args['orderby'] = 'date';
		$query_args['order']   = 'ASC';
		break;
	case 'title':
		$query_args['orderby'] = 'title';
		$query_args['order']   = 'ASC';
		break;
	case 'newest':
	default:
		$query_args['orderby'] = 'date';
		$query_args['order']   = 'DESC';
		break;
}

if ( '' !== $taxonomy && taxonomy_exists( $taxonomy ) && $term_id > 0 ) {
	$query_args['tax_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- nguồn block do admin chọn.
		array(
			'taxonomy'         => $taxonomy,
			'field'            => 'term_id',
			'terms'            => array( $term_id ),
			'include_children' => true,
		),
	);
}

$query = new WP_Query( $query_args );

$wrapper_attributes = get_block_wrapper_attributes( array( 'class' => 'nntm-article-rows' ) );
?>
<section <?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput -- core tự escape. ?>>
	<div class="nntm-article-rows__inner">
		<?php if ( '' !== trim( wp_strip_all_tags( $heading ) ) ) : ?>
			<h2 class="nntm-article-rows__heading"><?php echo wp_kses_post( $heading ); ?></h2>
		<?php endif; ?>

		<?php if ( $query->have_posts() ) : ?>
			<div class="nntm-article-rows__list">
				<?php foreach ( $query->posts as $index => $queried_post ) : ?>
					<?php
					echo nntm_render_section_article_row( // phpcs:ignore WordPress.Security.EscapeOutput -- helper tự escape.
						$queried_post,
						(int) $index,
						array(
							'show_excerpt'  => $show_excerpt,
							'show_favorite' => $show_favorite,
							'cta_label'     => $cta_label,
							'start_side'    => $start_side,
						)
					);
					?>
				<?php endforeach; ?>
			</div>

			<?php if ( $show_paging ) : ?>
				<?php echo nntm_render_section_pagination( $paged, (int) $query->max_num_pages ); // phpcs:ignore WordPress.Security.EscapeOutput -- helper tự escape. ?>
			<?php endif; ?>
		<?php else : ?>
			<p class="nntm-article-rows__empty"><?php esc_html_e( 'Chưa có bài viết nào phù hợp để hiển thị.', 'nntm' ); ?></p>
		<?php endif; ?>
	</div>
</section>
