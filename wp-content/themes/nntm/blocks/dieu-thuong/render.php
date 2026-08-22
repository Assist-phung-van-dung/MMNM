<?php
defined( 'ABSPATH' ) || exit;

$render_image = static function ( array $item, string $class, string $size = 'large', bool $lazy = true ): string {
	$id  = isset( $item['imageId'] ) ? absint( $item['imageId'] ) : 0;
	$url = isset( $item['imageUrl'] ) ? esc_url_raw( (string) $item['imageUrl'] ) : '';
	$alt = isset( $item['imageAlt'] ) ? sanitize_text_field( (string) $item['imageAlt'] ) : '';

	if ( $id ) {
		return (string) wp_get_attachment_image(
			$id,
			$size,
			false,
			array(
				'class'   => $class,
				'alt'     => $alt,
				'loading' => $lazy ? 'lazy' : 'eager',
			)
		);
	}

	if ( ! $url ) {
		return '';
	}

	return '<img class="' . esc_attr( $class ) . '" src="' . esc_url( $url ) . '" alt="' . esc_attr( $alt ) . '" loading="' . ( $lazy ? 'lazy' : 'eager' ) . '">';
};

$banner_images = isset( $attributes['bannerImages'] ) && is_array( $attributes['bannerImages'] ) ? $attributes['bannerImages'] : array();
$banner_images = array_values(
	array_filter(
		$banner_images,
		static fn( $item ): bool => is_array( $item ) && ( ! empty( $item['imageId'] ) || ! empty( $item['imageUrl'] ) )
	)
);

if ( ! $banner_images && ( ! empty( $attributes['bannerImageId'] ) || ! empty( $attributes['bannerImageUrl'] ) ) ) {
	$banner_images[] = array(
		'imageId'  => absint( $attributes['bannerImageId'] ?? 0 ),
		'imageUrl' => esc_url_raw( (string) ( $attributes['bannerImageUrl'] ?? '' ) ),
		'imageAlt' => '',
	);
}

$gallery = isset( $attributes['gallery'] ) && is_array( $attributes['gallery'] ) ? array_slice( $attributes['gallery'], 0, 3 ) : array();
$gallery = array_values(
	array_filter(
		$gallery,
		static fn( $item ): bool => is_array( $item ) && ( ! empty( $item['imageId'] ) || ! empty( $item['imageUrl'] ) )
	)
);

$portrait = $render_image(
	array(
		'imageId'  => absint( $attributes['portraitImageId'] ?? 0 ),
		'imageUrl' => esc_url_raw( (string) ( $attributes['portraitImageUrl'] ?? '' ) ),
		'imageAlt' => '',
	),
	'nntm-dt__portrait-img',
	'large'
);

$story_heading     = sanitize_text_field( (string) ( $attributes['storyHeading'] ?? '' ) );
$story_text_top    = sanitize_textarea_field( (string) ( $attributes['storyTextTop'] ?? '' ) );
$story_text_bottom = sanitize_textarea_field( (string) ( $attributes['storyTextBottom'] ?? '' ) );
$cta_label         = sanitize_text_field( (string) ( $attributes['ctaLabel'] ?? '' ) );
$cta_url           = esc_url( (string) ( $attributes['ctaUrl'] ?? '' ) );
$banner_interval   = max( 3, min( 20, absint( $attributes['bannerInterval'] ?? 6 ) ) );
$banner_autoplay   = ! array_key_exists( 'bannerAutoplay', $attributes ) || ! empty( $attributes['bannerAutoplay'] );

$wrapper = get_block_wrapper_attributes(
	array(
		'class'                => 'nntm-dt',
		'data-banner-autoplay' => $banner_autoplay ? '1' : '0',
		'data-banner-interval' => (string) $banner_interval,
	)
);
?>
<section <?php echo $wrapper;  ?>>
	<?php if ( $banner_images ) : ?>
		<div class="nntm-dt__banner" data-dt-banner aria-label="<?php esc_attr_e( 'Banner hình ảnh', 'nntm' ); ?>">
			<?php foreach ( $banner_images as $index => $item ) : ?>
				<div class="nntm-dt__banner-slide<?php echo 0 === $index ? ' is-active' : ''; ?>" data-dt-banner-slide aria-hidden="<?php echo 0 === $index ? 'false' : 'true'; ?>">
					<?php echo wp_kses_post( $render_image( $item, 'nntm-dt__banner-img', 'full', 0 !== $index ) ); ?>
				</div>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>

	<div class="nntm-dt__story">
		<div class="nntm-dt__story-inner">
			<?php if ( $portrait ) : ?><div class="nntm-dt__portrait"><?php echo wp_kses_post( $portrait ); ?></div><?php endif; ?>
			<?php if ( $story_heading ) : ?><h2><?php echo esc_html( $story_heading ); ?></h2><?php endif; ?>
			<?php if ( $story_text_top ) : ?><p><?php echo nl2br( esc_html( $story_text_top ) ); ?></p><?php endif; ?>

			<?php if ( $gallery ) : ?>
				<div class="nntm-dt__gallery">
					<?php foreach ( $gallery as $item ) : ?>
						<figure><?php echo wp_kses_post( $render_image( $item, 'nntm-dt__gallery-img', 'large' ) ); ?></figure>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>

			<?php if ( $story_text_bottom ) : ?><p><?php echo nl2br( esc_html( $story_text_bottom ) ); ?></p><?php endif; ?>
			<?php if ( $cta_label && $cta_url ) : ?><a class="nntm-dt__cta" href="<?php echo esc_url( $cta_url ); ?>"><?php echo esc_html( $cta_label ); ?></a><?php endif; ?>
		</div>
	</div>
</section>
