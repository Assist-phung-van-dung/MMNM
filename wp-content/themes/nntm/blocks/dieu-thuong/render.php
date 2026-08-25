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

$gallery_term_id   = isset( $attributes['galleryTermId'] ) ? absint( $attributes['galleryTermId'] ) : 0;
$gallery_hien      = isset( $attributes['galleryMax'] ) ? absint( $attributes['galleryMax'] ) : 3;
$gallery_hien      = max( 1, min( 6, $gallery_hien ) );
$gallery_lay       = (int) apply_filters( 'nntm_dt_gallery_so_the_lay', 20 );
$gallery_autoplay  = ! array_key_exists( 'galleryAutoplay', $attributes ) || ! empty( $attributes['galleryAutoplay'] );
$gallery_interval  = max( 2, min( 20, absint( $attributes['galleryInterval'] ?? 5 ) ) );
$gallery_loop_delay = max( 0, min( 60, absint( $attributes['galleryLoopDelay'] ?? 0 ) ) );
$gallery_terms      = array();
$gallery_parent     = $gallery_term_id > 0 ? get_term( $gallery_term_id, 'nntm_section' ) : null;

if ( $gallery_parent instanceof WP_Term ) {
	$maybe_gallery_terms = get_terms(
		array(
			'taxonomy'   => 'nntm_section',
			'parent'     => $gallery_parent->term_id,
			'hide_empty' => false,
			'number'     => $gallery_lay,
			'orderby'    => 'name',
			'order'      => 'ASC',
		)
	);

	if ( ! is_wp_error( $maybe_gallery_terms ) ) {
		$gallery_terms = $maybe_gallery_terms;

		if ( function_exists( 'nntm_sort_terms_by_order' ) ) {
			$gallery_terms = nntm_sort_terms_by_order( $gallery_terms );
		}
	}
}

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

			<?php if ( $gallery_term_id > 0 && $gallery_terms ) : ?>
				<div
					class="nntm-dt__gallery nntm-dt__gallery--terms"
					data-dt-gallery
					data-gallery-autoplay="<?php echo esc_attr( $gallery_autoplay ? '1' : '0' ); ?>"
					data-gallery-interval="<?php echo esc_attr( (string) $gallery_interval ); ?>"
					data-gallery-loop-delay="<?php echo esc_attr( (string) $gallery_loop_delay ); ?>"
					style="--nntm-dt-hien:<?php echo esc_attr( (string) $gallery_hien ); ?>"
				>
					<button type="button" class="nntm-dt__gallery-arrow nntm-dt__gallery-arrow--prev" data-dt-gallery-prev aria-label="<?php esc_attr_e( 'Phân mục trước', 'nntm' ); ?>">&larr;</button>
					<div class="nntm-dt__gallery-track" data-dt-gallery-track>
						<?php foreach ( $gallery_terms as $gallery_term ) : ?>
							<?php
							$term_link = get_term_link( $gallery_term );
							if ( is_wp_error( $term_link ) ) {
								continue;
							}

							$image_id = absint( get_term_meta( $gallery_term->term_id, '_nntm_term_image', true ) );
							$image     = $image_id > 0 ? wp_get_attachment_image(
								$image_id,
								'medium_large',
								false,
								array(
									'class'   => 'nntm-dt__gallery-card-img-el',
									'loading' => 'lazy',
									'alt'     => $gallery_term->name,
								)
							) : '';
							$description = trim( wp_strip_all_tags( term_description( $gallery_term->term_id, 'nntm_section' ), true ) );
							?>
							<a href="<?php echo esc_url( $term_link ); ?>" class="nntm-dt__gallery-card">
								<span class="nntm-dt__gallery-card-img">
									<?php if ( $image ) : ?>
										<?php echo wp_kses_post( $image ); ?>
									<?php else : ?>
										<span class="nntm-dt__gallery-card-placeholder" aria-hidden="true"></span>
									<?php endif; ?>
								</span>
								<span class="nntm-dt__gallery-card-content">
									<span class="nntm-dt__gallery-card-name"><?php echo esc_html( $gallery_term->name ); ?></span>
									<?php if ( '' !== $description ) : ?>
										<span class="nntm-dt__gallery-card-desc"><?php echo esc_html( $description ); ?></span>
									<?php endif; ?>
									<span class="nntm-dt__gallery-card-cta"><?php esc_html_e( 'Xem thêm', 'nntm' ); ?></span>
								</span>
							</a>
						<?php endforeach; ?>
					</div>
					<button type="button" class="nntm-dt__gallery-arrow nntm-dt__gallery-arrow--next" data-dt-gallery-next aria-label="<?php esc_attr_e( 'Phân mục tiếp theo', 'nntm' ); ?>">&rarr;</button>
				</div>
			<?php elseif ( $gallery_term_id > 0 ) : ?>
				<p class="nntm-dt__gallery-empty"><?php esc_html_e( 'Phân mục đã chọn chưa có phân mục con.', 'nntm' ); ?></p>
			<?php elseif ( $gallery ) : ?>
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
