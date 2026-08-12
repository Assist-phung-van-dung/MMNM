<?php
/**
 * Render động cho block nntm/article-rows — danh sách bài viết xếp
 * thành hàng ngang, ảnh và chữ đảo bên luân phiên theo từng hàng.
 * Dùng cho trang chi tiết truyền thống (Figma: "03. PHAP TOA - NGUYEN THUY").
 *
 * Không lưu HTML vào nội dung bài: mỗi lần tải trang, WP_Query chạy
 * lại từ $attributes hiện tại — bắt chước đúng phong cách của
 * blocks/card-list/render.php.
 *
 * @package NNTM
 * @var array    $attributes Thuộc tính của block.
 * @var string   $content    Nội dung InnerBlocks (không dùng ở block này).
 * @var WP_Block $block      Instance block hiện tại.
 */

defined( 'ABSPATH' ) || exit;

// Dùng lại nntm_card_get_primary_term() đã có ở block nntm/card thay vì
// viết lại logic ưu tiên taxonomy (nntm_section -> nntm_topic ->
// nntm_series -> category) — đúng nguyên tắc "sửa một chỗ" ở
// docs/04-kien-truc.md mục 2. require_once tự đảm bảo hàm chỉ khai báo
// một lần dù render.php của nhiều block cùng require file này.
require_once get_template_directory() . '/blocks/card/inc/render-card.php';

// ---------- Đọc & làm sạch thuộc tính (danh sách trắng) ----------

$allowed_post_types = array( 'nntm_article', 'nntm_publication', 'nntm_talk', 'nntm_video', 'post' );
$post_type          = isset( $attributes['postType'] ) ? sanitize_key( (string) $attributes['postType'] ) : 'nntm_article';
if ( ! in_array( $post_type, $allowed_post_types, true ) ) {
	$post_type = 'nntm_article';
}

// Giới hạn hợp lý — không bao giờ truy vấn không giới hạn.
$posts_per_page = isset( $attributes['postsPerPage'] ) ? absint( $attributes['postsPerPage'] ) : 4;
$posts_per_page = max( 1, min( 12, $posts_per_page ) );

$order_by_choice = isset( $attributes['orderBy'] ) ? sanitize_key( (string) $attributes['orderBy'] ) : 'newest';
if ( ! in_array( $order_by_choice, array( 'newest', 'oldest', 'title' ), true ) ) {
	$order_by_choice = 'newest';
}

$start_side = isset( $attributes['startSide'] ) ? sanitize_key( (string) $attributes['startSide'] ) : 'left';
if ( ! in_array( $start_side, array( 'left', 'right' ), true ) ) {
	$start_side = 'left';
}

$show_category = ! isset( $attributes['showCategory'] ) || ! empty( $attributes['showCategory'] );
$show_excerpt  = ! isset( $attributes['showExcerpt'] ) || ! empty( $attributes['showExcerpt'] );

$heading = isset( $attributes['heading'] ) ? (string) $attributes['heading'] : '';

$primary_cta_label   = isset( $attributes['primaryCtaLabel'] ) && '' !== trim( (string) $attributes['primaryCtaLabel'] )
	? (string) $attributes['primaryCtaLabel']
	: __( 'Đọc bài', 'nntm' );
$secondary_cta_label = isset( $attributes['secondaryCtaLabel'] ) && '' !== trim( (string) $attributes['secondaryCtaLabel'] )
	? (string) $attributes['secondaryCtaLabel']
	: __( 'Xem thêm', 'nntm' );

$taxonomy = isset( $attributes['taxonomy'] ) ? sanitize_key( (string) $attributes['taxonomy'] ) : '';
$term_id  = isset( $attributes['termId'] ) ? absint( $attributes['termId'] ) : 0;

// ---------- Truy vấn ----------
// Block này không phân trang (yêu cầu) nên luôn bật no_found_rows để
// bớt một truy vấn COUNT không cần thiết.
$query_args = array(
	'post_type'           => $post_type,
	'post_status'         => 'publish',
	'posts_per_page'      => $posts_per_page,
	'ignore_sticky_posts' => true,
	'no_found_rows'       => true,
	/*
	 * KHÔNG tắt update_post_meta_cache / update_post_term_cache ở đây.
	 * Mỗi hàng đọc cả ảnh đại diện (post meta `_thumbnail_id`) lẫn nhãn
	 * chuyên mục (get_the_terms() qua nntm_card_get_primary_term()).
	 * Tắt bộ nhớ đệm sẽ khiến mỗi bài sinh thêm truy vấn riêng — chậm
	 * hơn chứ không nhanh hơn.
	 */
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
	$query_args['tax_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- can loc theo 1 term, khong tranh duoc.
		array(
			'taxonomy' => $taxonomy,
			'field'    => 'term_id',
			'terms'    => array( $term_id ),
		),
	);
}

$query = new WP_Query( $query_args );

$wrapper_attributes = get_block_wrapper_attributes( array( 'class' => 'nntm-article-rows' ) );
?>
<section <?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput -- get_block_wrapper_attributes() da tu esc_attr() tung thuoc tinh. ?>>
	<div class="nntm-article-rows__inner">
		<?php if ( '' !== $heading ) : ?>
			<h2 class="nntm-article-rows__heading"><?php echo wp_kses_post( $heading ); ?></h2>
		<?php endif; ?>

		<?php if ( $query->have_posts() ) : ?>
			<div class="nntm-article-rows__list">
				<?php
				$row_index = 0;
				foreach ( $query->posts as $queried_post ) :
					// Hàng đầu tiên theo $start_side, các hàng sau tự đảo luân phiên.
					// DOM luôn xuất ẢNH trước rồi mới tới CHỮ (xem bên dưới) — việc
					// đảo bên chỉ làm bằng class CSS này (grid-template-columns +
					// order trong style.css), KHÔNG đảo thứ tự phần tử trong HTML,
					// để thứ tự đọc bàn phím / trình đọc màn hình luôn nhất quán:
					// ảnh trước, chữ sau, dù hàng hiển thị đảo bên hay không.
					$img_on_right = ( 'right' === $start_side ) ? ( 0 === $row_index % 2 ) : ( 1 === $row_index % 2 );

					$row_classes = array( 'nntm-article-rows__row' );
					if ( $img_on_right ) {
						$row_classes[] = 'nntm-article-rows__row--reversed';
					}

					$permalink = get_permalink( $queried_post );
					$title     = get_the_title( $queried_post );
					$thumbnail = get_the_post_thumbnail(
						$queried_post,
						'medium_large',
						array(
							'class'   => 'nntm-article-rows__img-el',
							'loading' => 'lazy',
							'alt'     => $title,
						)
					);
					?>
					<article class="<?php echo esc_attr( implode( ' ', $row_classes ) ); ?>">
						<span class="nntm-article-rows__img">
							<?php
							if ( $thumbnail ) {
								echo wp_kses_post( $thumbnail );
							} else {
								echo '<span class="nntm-article-rows__img-placeholder" aria-hidden="true"></span>';
							}
							?>
						</span>
						<div class="nntm-article-rows__text">
							<?php
							if ( $show_category ) :
								$term = nntm_card_get_primary_term( $queried_post->ID );
								if ( $term ) :
									?>
									<span class="nntm-article-rows__cat"><?php echo esc_html( $term->name ); ?></span>
									<?php
								endif;
							endif;
							?>

							<h3 class="nntm-article-rows__title">
								<a href="<?php echo esc_url( $permalink ); ?>"><?php echo esc_html( $title ); ?></a>
							</h3>

							<?php if ( $show_excerpt ) : ?>
								<p class="nntm-article-rows__excerpt">
									<?php echo esc_html( wp_trim_words( get_the_excerpt( $queried_post ), 24, '…' ) ); ?>
								</p>
							<?php endif; ?>

							<div class="nntm-article-rows__ctas">
								<a class="nntm-article-rows__cta nntm-article-rows__cta--primary" href="<?php echo esc_url( $permalink ); ?>">
									<?php echo esc_html( $primary_cta_label ); ?>
								</a>
								<a class="nntm-article-rows__cta nntm-article-rows__cta--secondary" href="<?php echo esc_url( $permalink ); ?>">
									<?php echo esc_html( $secondary_cta_label ); ?>
								</a>
							</div>
						</div>
					</article>
					<?php
					++$row_index;
				endforeach;
				?>
			</div>
		<?php else : ?>
			<p class="nntm-article-rows__empty"><?php esc_html_e( 'Chưa có bài viết nào phù hợp để hiển thị.', 'nntm' ); ?></p>
		<?php endif; ?>
	</div>
</section>
