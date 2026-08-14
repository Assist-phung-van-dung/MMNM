<?php
defined( 'ABSPATH' ) || exit;

$text = static fn( string $key, string $default = '' ): string => isset( $attributes[ $key ] ) ? (string) $attributes[ $key ] : $default;
$image = static function ( array $item, string $class, string $size = 'large' ): string {
	$id = isset( $item['imageId'] ) ? absint( $item['imageId'] ) : 0;
	$url = isset( $item['imageUrl'] ) ? esc_url_raw( (string) $item['imageUrl'] ) : '';
	$alt = isset( $item['imageAlt'] ) ? sanitize_text_field( (string) $item['imageAlt'] ) : '';
	if ( $id ) return (string) wp_get_attachment_image( $id, $size, false, array( 'class' => $class, 'alt' => $alt ) );
	return $url ? '<img class="' . esc_attr( $class ) . '" src="' . esc_url( $url ) . '" alt="' . esc_attr( $alt ) . '" loading="lazy">' : '';
};
$slides = isset( $attributes['slides'] ) && is_array( $attributes['slides'] ) ? array_values( array_filter( $attributes['slides'], fn( $s ) => ! empty( $s['imageId'] ) || ! empty( $s['imageUrl'] ) ) ) : array();
$gallery = isset( $attributes['gallery'] ) && is_array( $attributes['gallery'] ) ? array_slice( $attributes['gallery'], 0, 3 ) : array();
$banner = $image( array( 'imageId' => $attributes['bannerImageId'] ?? 0, 'imageUrl' => $attributes['bannerImageUrl'] ?? '', 'imageAlt' => '' ), 'nntm-dt__banner-img', 'full' );
$portrait = $image( array( 'imageId' => $attributes['portraitImageId'] ?? 0, 'imageUrl' => $attributes['portraitImageUrl'] ?? '', 'imageAlt' => '' ), 'nntm-dt__portrait-img', 'large' );
$interval = max( 3, min( 20, absint( $attributes['interval'] ?? 6 ) ) );
$wrapper = get_block_wrapper_attributes( array( 'class' => 'nntm-dt', 'data-autoplay' => ! empty( $attributes['autoplay'] ) ? '1' : '0', 'data-interval' => (string) $interval ) );
?>
<div <?php echo $wrapper; // phpcs:ignore WordPress.Security.EscapeOutput ?>>
  <section class="nntm-dt__principles">
    <div class="nntm-dt__intro">
      <h1><?php echo wp_kses_post( $text( 'heading', 'Tông Chỉ' ) ); ?></h1>
      <p class="nntm-dt__intro-title"><?php echo wp_kses_post( $text( 'introTitle' ) ); ?></p>
      <p><?php echo wp_kses_post( $text( 'introText' ) ); ?></p>
    </div>
    <?php if ( $slides ) : ?>
      <div class="nntm-dt__slider" aria-roledescription="carousel">
        <button class="nntm-dt__arrow nntm-dt__arrow--prev" type="button" aria-label="Ảnh trước" data-dt-prev>←</button>
        <div class="nntm-dt__track" data-dt-track tabindex="0">
          <?php foreach ( $slides as $index => $slide ) : ?>
            <article class="nntm-dt__slide" data-index="<?php echo esc_attr( (string) $index ); ?>">
              <div class="nntm-dt__slide-media"><?php echo wp_kses_post( $image( $slide, 'nntm-dt__slide-img', 'large' ) ); ?></div>
              <div class="nntm-dt__slide-copy">
                <?php if ( ! empty( $slide['heading'] ) ) : ?><h2><?php echo wp_kses_post( (string) $slide['heading'] ); ?></h2><?php endif; ?>
                <?php if ( ! empty( $slide['text'] ) ) : ?><p><?php echo wp_kses_post( (string) $slide['text'] ); ?></p><?php endif; ?>
              </div>
            </article>
          <?php endforeach; ?>
        </div>
        <button class="nntm-dt__arrow nntm-dt__arrow--next" type="button" aria-label="Ảnh tiếp theo" data-dt-next>→</button>
      </div>
    <?php endif; ?>
  </section>
  <section class="nntm-dt__story">
    <div class="nntm-dt__banner"><?php echo wp_kses_post( $banner ); ?></div>
    <div class="nntm-dt__story-inner nntm-container">
      <?php if ( $portrait ) : ?><div class="nntm-dt__portrait"><?php echo wp_kses_post( $portrait ); ?></div><?php endif; ?>
      <h2><?php echo wp_kses_post( $text( 'storyHeading' ) ); ?></h2>
      <p><?php echo wp_kses_post( $text( 'storyTextTop' ) ); ?></p>
      <?php if ( $gallery ) : ?><div class="nntm-dt__gallery"><?php foreach ( $gallery as $item ) echo '<figure>' . wp_kses_post( $image( $item, 'nntm-dt__gallery-img', 'large' ) ) . '</figure>'; ?></div><?php endif; ?>
      <p><?php echo wp_kses_post( $text( 'storyTextBottom' ) ); ?></p>
      <?php if ( $text( 'ctaLabel' ) ) : ?><a class="nntm-dt__cta" href="<?php echo esc_url( $text( 'ctaUrl', '#' ) ?: '#' ); ?>"><?php echo esc_html( $text( 'ctaLabel' ) ); ?></a><?php endif; ?>
    </div>
  </section>
</div>
