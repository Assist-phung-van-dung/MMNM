<?php

defined( 'ABSPATH' ) || exit;

 
require_once __DIR__ . '/inc/render-article-feature.php';

$allowed_post_types = array( 'post', 'nntm_article', 'nntm_publication', 'nntm_talk' );
$post_type          = isset( $attributes['postType'] ) ? sanitize_key( (string) $attributes['postType'] ) : 'post';
if ( ! in_array( $post_type, $allowed_post_types, true ) ) {
	$post_type = 'post';
}

$media_position = isset( $attributes['mediaPosition'] ) ? sanitize_key( (string) $attributes['mediaPosition'] ) : 'right';
if ( ! in_array( $media_position, array( 'left', 'right' ), true ) ) {
	$media_position = 'right';
}

$post_id  = isset( $attributes['postId'] ) ? absint( $attributes['postId'] ) : 0;
$taxonomy = isset( $attributes['taxonomy'] ) ? sanitize_key( (string) $attributes['taxonomy'] ) : '';
$term_id  = isset( $attributes['termId'] ) ? absint( $attributes['termId'] ) : 0;

 
$max_paragraphs = isset( $attributes['maxParagraphs'] ) ? absint( $attributes['maxParagraphs'] ) : 8;
$max_paragraphs = min( 30, max( 1, $max_paragraphs ) );

$cta_label = isset( $attributes['ctaLabel'] ) && '' !== trim( (string) $attributes['ctaLabel'] )
	? (string) $attributes['ctaLabel']
	: __( 'Xem tiếp', 'nntm' );

$show_quote_mark = ! isset( $attributes['showQuoteMark'] ) || ! empty( $attributes['showQuoteMark'] );

$attribution_override = isset( $attributes['attribution'] ) ? (string) $attributes['attribution'] : '';

$article = nntm_article_feature_find_post( $post_id, $post_type, $taxonomy, $term_id );

if ( null === $article ) {
	$wrapper_attributes = get_block_wrapper_attributes( array( 'class' => 'nntm-article-feature' ) );
	?>
	<section <?php echo $wrapper_attributes;  ?>>
		<div class="nntm-article-feature__inner">
			<p class="nntm-article-feature__empty"><?php esc_html_e( 'Chưa chọn được bài viết nào để hiển thị.', 'nntm' ); ?></p>
		</div>
	</section>
	<?php
	return;
}

$permalink = get_permalink( $article );
$title     = get_the_title( $article );

$attribution = '' !== trim( $attribution_override )
	? $attribution_override
	: (string) $article->post_excerpt;

$paragraphs = nntm_article_feature_get_paragraphs( $article, $max_paragraphs );

$thumbnail = get_the_post_thumbnail(
	$article,
	'large',
	array(
		'class'   => 'nntm-article-feature__img-el',
		'loading' => 'lazy',
		'alt'     => $title,
	)
);

$wrapper_classes = 'nntm-article-feature nntm-article-feature--media-' . $media_position;
if ( ! $thumbnail ) {
	$wrapper_classes .= ' nntm-article-feature--khong-anh';
}

$wrapper_attributes = get_block_wrapper_attributes( array( 'class' => $wrapper_classes ) );
?>
<section <?php echo $wrapper_attributes;  ?>>
	<div class="nntm-article-feature__inner">

		<?php if ( $show_quote_mark ) : ?>
			<?php
			 
			?>
			<span class="nntm-article-feature__quote" aria-hidden="true"><span class="nntm-article-feature__quote-glyph">&ldquo;</span></span>
		<?php endif; ?>

		<div class="nntm-article-feature__text">
			<h2 class="nntm-article-feature__title">
				<a href="<?php echo esc_url( $permalink ); ?>"><?php echo esc_html( $title ); ?></a>
			</h2>

			<?php if ( '' !== trim( (string) $attribution ) ) : ?>
				<p class="nntm-article-feature__attribution"><?php echo wp_kses_post( $attribution ); ?></p>
			<?php endif; ?>

			<div class="nntm-article-feature__box">
				<?php if ( '' !== trim( $paragraphs ) ) : ?>
					<div class="nntm-article-feature__body">
						<?php echo $paragraphs;  ?>
					</div>
				<?php endif; ?>

				<a class="nntm-article-feature__cta" href="<?php echo esc_url( $permalink ); ?>">
					<?php echo esc_html( $cta_label ); ?>
				</a>
			</div>
		</div>

		<?php if ( $thumbnail ) : ?>
			<a class="nntm-article-feature__media-link" href="<?php echo esc_url( $permalink ); ?>" aria-label="<?php echo esc_attr( $title ); ?>">
				<div class="nntm-article-feature__media">
					<?php echo wp_kses_post( $thumbnail ); ?>
				</div>
			</a>
		<?php endif; ?>

	</div>
</section>
