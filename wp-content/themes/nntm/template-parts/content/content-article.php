<?php
/**
 * Hiển thị một bài viết — dạng thẻ tóm tắt (index/archive/search) hoặc
 * đầy đủ (single). Ranh giới quyết định bằng is_singular().
 *
 * TODO Phase sau: tách thẻ tóm tắt thành block nntm/card-list theo
 * đúng biến thể Figma (ARTICLE, SMALL, XS...) — hiện dùng markup PHP
 * tối giản để có khung chạy được trước.
 */

defined( 'ABSPATH' ) || exit;
?>
<article id="post-<?php the_ID(); ?>" <?php post_class( 'nntm-article' ); ?>>

	<?php if ( has_post_thumbnail() ) : ?>
		<div class="nntm-article__thumb">
			<?php if ( is_singular() ) : ?>
				<?php the_post_thumbnail( 'large' ); ?>
			<?php else : ?>
				<a href="<?php the_permalink(); ?>" aria-hidden="true" tabindex="-1">
					<?php the_post_thumbnail( 'medium' ); ?>
				</a>
			<?php endif; ?>
		</div>
	<?php endif; ?>

	<div class="nntm-article__body">
		<?php if ( is_singular() ) : ?>
			<h1 class="nntm-article__title"><?php the_title(); ?></h1>
		<?php else : ?>
			<h2 class="nntm-article__title">
				<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
			</h2>
		<?php endif; ?>

		<p class="nntm-article__meta">
			<?php echo esc_html( get_the_date() ); ?>
		</p>

		<div class="nntm-article__content">
			<?php
			if ( is_singular() ) {
				the_content();
			} else {
				the_excerpt();
			}
			?>
		</div>
	</div>

</article>
