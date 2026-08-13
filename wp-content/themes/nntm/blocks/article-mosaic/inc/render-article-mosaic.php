<?php
/**
 * Hàm dựng HTML dùng chung cho render.php của block nntm/article-mosaic.
 *
 * Tách riêng ra file inc/ (bắt chước đúng blocks/card/inc/render-card.php)
 * vì render.php của block được WordPress core `require` (KHÔNG PHẢI
 * `require_once`) mỗi lần block render (xem wp-includes/blocks.php,
 * hàm register_block_type_from_metadata()). Block này chắc chắn xuất
 * hiện HAI LẦN trên cùng một trang "05. HOA KHAI" (SECTION 1 Hoằng
 * Pháp + SECTION 4 Tin tức) — nếu khai hàm thẳng trong render.php sẽ
 * "Cannot redeclare function" ở lần render thứ hai. File inc/ này được
 * require_once từ render.php nên chỉ khai báo đúng một lần dù render.php
 * bị require lại bao nhiêu lần.
 *
 * @package NNTM
 */

defined( 'ABSPATH' ) || exit;

/**
 * Dựng HTML ảnh đại diện (hoặc ô giữ chỗ khi bài không có ảnh) — dùng
 * chung cho bài nổi bật, thẻ vừa và thẻ nhỏ.
 *
 * @param WP_Post $mosaic_post Bài viết.
 * @param string  $img_class   Class ảnh (aspect-ratio khai ở style.css).
 * @return string HTML đã escape.
 */
function nntm_article_mosaic_render_thumb( WP_Post $mosaic_post, string $img_class ): string {
	$title     = get_the_title( $mosaic_post );
	$thumbnail = get_the_post_thumbnail(
		$mosaic_post,
		'medium_large',
		array(
			'class'   => 'nntm-article-mosaic__img-el',
			'loading' => 'lazy',
			'alt'     => $title,
		)
	);

	ob_start();
	?>
	<span class="<?php echo esc_attr( $img_class ); ?>">
		<?php
		if ( $thumbnail ) {
			echo wp_kses_post( $thumbnail );
		} else {
			echo '<span class="nntm-article-mosaic__img-placeholder" aria-hidden="true"></span>';
		}
		?>
	</span>
	<?php
	return trim( (string) ob_get_clean() );
}

/**
 * Dựng HTML dòng ngày cập nhật — bắt chước đúng markup/nhãn của
 * nntm-card__date (blocks/card/inc/render-card.php) để đồng bộ hệ thống.
 *
 * @param WP_Post $mosaic_post Bài viết.
 * @return string HTML đã escape.
 */
function nntm_article_mosaic_render_date( WP_Post $mosaic_post ): string {
	ob_start();
	?>
	<span class="nntm-article-mosaic__date">
		<span class="nntm-article-mosaic__date-icon" aria-hidden="true"></span>
		<?php
		echo esc_html(
			sprintf(
				/* translators: %s: ngày cập nhật bài viết, định dạng d. m. Y giống Figma */
				__( 'Cập nhật %s', 'nntm' ),
				get_the_modified_date( 'd. m. Y', $mosaic_post )
			)
		);
		?>
	</span>
	<?php
	return trim( (string) ob_get_clean() );
}

/**
 * Dựng HTML một thẻ vừa/nhỏ ở cột phải.
 *
 * SỬA 12/08/2026: đối chiếu ảnh Figma thật (fig-mosaic1.png) cho thấy
 * KHÔNG có chữ "Xem thêm" dưới bất kỳ ô nào — trước đây có một liên kết
 * "Xem thêm" riêng vì tiêu đề cố ý để là CHỮ THƯỜNG (yêu cầu 10/08/2026).
 * Quyết định đó nay đổi lại: bỏ hẳn liên kết "Xem thêm", tiêu đề tự làm
 * liên kết duy nhất của thẻ — không còn lồng hai liên kết trong cùng một
 * thẻ (không có <a> nào khác bên trong để lồng).
 *
 * @param WP_Post $mosaic_post   Bài viết.
 * @param string  $variant       'medium' hoặc 'small' — quyết định class ảnh/khối.
 * @param bool    $show_category Có hiện nhãn chuyên mục không.
 * @param bool    $show_date     Có hiện ngày cập nhật không.
 * @return string HTML đã escape.
 */
function nntm_article_mosaic_render_secondary_card( WP_Post $mosaic_post, string $variant, bool $show_category, bool $show_date ): string {
	$permalink = get_permalink( $mosaic_post );
	$title     = get_the_title( $mosaic_post );
	$img_class = 'nntm-article-mosaic__' . $variant . '-img';

	ob_start();
	?>
	<article class="nntm-article-mosaic__<?php echo esc_attr( $variant ); ?>-card">
		<?php echo nntm_article_mosaic_render_thumb( $mosaic_post, $img_class ); // phpcs:ignore WordPress.Security.EscapeOutput -- ham con da tu esc trong. ?>
		<div class="nntm-article-mosaic__<?php echo esc_attr( $variant ); ?>-body">
			<?php if ( $show_date ) : ?>
				<?php echo nntm_article_mosaic_render_date( $mosaic_post ); // phpcs:ignore WordPress.Security.EscapeOutput -- ham con da tu esc trong. ?>
			<?php endif; ?>

			<?php
			if ( $show_category ) :
				$term = nntm_card_get_primary_term( $mosaic_post->ID );
				if ( $term ) :
					?>
					<span class="nntm-article-mosaic__cat nntm-article-mosaic__cat--<?php echo esc_attr( $variant ); ?>"><?php echo esc_html( $term->name ); ?></span>
					<?php
				endif;
			endif;
			?>

			<h3 class="nntm-article-mosaic__<?php echo esc_attr( $variant ); ?>-title nntm-cat-2-dong">
				<a href="<?php echo esc_url( $permalink ); ?>"><?php echo esc_html( $title ); ?></a>
			</h3>
		</div>
	</article>
	<?php
	return trim( (string) ob_get_clean() );
}
