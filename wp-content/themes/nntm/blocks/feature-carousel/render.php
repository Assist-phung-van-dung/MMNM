<?php
defined( 'ABSPATH' ) || exit;

$render_image = static function ( array $item, string $class ): string {
	$id  = isset( $item['imageId'] ) ? absint( $item['imageId'] ) : 0;
	$url = isset( $item['imageUrl'] ) ? esc_url_raw( (string) $item['imageUrl'] ) : '';
	$alt = isset( $item['imageAlt'] ) ? sanitize_text_field( (string) $item['imageAlt'] ) : '';

	if ( $id ) {
		return (string) wp_get_attachment_image(
			$id,
			'full',
			false,
			array(
				'class'   => $class,
				'alt'     => $alt,
				'loading' => 'lazy',
			)
		);
	}

	if ( ! $url ) {
		return '';
	}

	return '<img class="' . esc_attr( $class ) . '" src="' . esc_url( $url ) . '" alt="' . esc_attr( $alt ) . '" loading="lazy">';
};

/*
 * Video trong carousel chay theo MOT trong hai che do:
 *
 *   anh-dong (mac dinh) — video lap lai nhu mot anh dong, carousel van tu chay
 *                         theo dung chu ky da dat.
 *   phat-het            — carousel dung lai cho video phat het roi moi chuyen.
 *
 * Truoc day hai che do nay bi tron lam mot: the <video> luon co "loop", trong
 * khi view.js lai dung dong ho cho toi khi video ket thuc. Video co "loop" thi
 * KHONG BAO GIO phat su kien "ended" — nen mot khi slide video vao giua la
 * carousel dung han, ra khoi vung chuot cung khong chay lai.
 */
$video_mode = ( 'phat-het' === ( $attributes['videoMode'] ?? 'anh-dong' ) ) ? 'phat-het' : 'anh-dong';

$render_video = static function ( array $item, string $class, string $label ) use ( $video_mode ): string {
	$id         = isset( $item['videoId'] ) ? absint( $item['videoId'] ) : 0;
	$url        = $id ? wp_get_attachment_url( $id ) : '';
	$url        = $url ? $url : esc_url_raw( (string) ( $item['videoUrl'] ?? '' ) );
	$poster_id  = isset( $item['videoPosterId'] ) ? absint( $item['videoPosterId'] ) : 0;
	$poster_url = $poster_id ? wp_get_attachment_image_url( $poster_id, 'full' ) : '';
	$poster_url = $poster_url ? $poster_url : esc_url_raw( (string) ( $item['videoPosterUrl'] ?? '' ) );

	if ( ! $url ) {
		return '';
	}

	$poster_attr = $poster_url ? ' poster="' . esc_url( $poster_url ) . '"' : '';

	/*
	 * Video o day chi de TRINH BAY hinh anh, khong phai de nguoi xem dieu khien:
	 *   - bo "controls"          -> khong co nut chay/dung, thanh tien do
	 *   - disablepictureinpicture -> khong cho tach ra cua so noi
	 *   - controlslist            -> chan menu tai xuong, doi toc do, phat tu xa
	 *   - muted + playsinline     -> trinh duyet cho tu chay, khong bung toan man
	 *   - loop                    -> CHI o che do anh-dong; o che do phat-het thi
	 *                                phai bo, vi video co loop khong bao gio phat
	 *                                su kien "ended" de carousel biet ma chuyen.
	 * Viec chay/dung theo slide dang xem van do view.js lo, khong doi.
	 */
	$loop_attr = ( 'anh-dong' === $video_mode ) ? ' loop' : '';

	return '<video class="' . esc_attr( $class ) . '" src="' . esc_url( $url ) . '" muted' . $loop_attr . ' playsinline preload="metadata"'
		. ' disablepictureinpicture disableremoteplayback controlslist="nodownload noplaybackrate noremoteplayback"'
		. ' data-fc-video aria-label="' . esc_attr( $label ) . '"' . $poster_attr . '></video>';
};

$slide_media_type = static function ( array $slide ): string {
	return 'video' === ( $slide['mediaType'] ?? 'image' ) ? 'video' : 'image';
};

$slides = isset( $attributes['slides'] ) && is_array( $attributes['slides'] ) ? $attributes['slides'] : array();
$slides = array_values(
	array_filter(
		$slides,
		static function ( $slide ) use ( $slide_media_type ): bool {
			if ( ! is_array( $slide ) ) {
				return false;
			}

			if ( 'video' === $slide_media_type( $slide ) ) {
				return ! empty( $slide['videoId'] ) || ! empty( $slide['videoUrl'] );
			}

			return ! empty( $slide['imageId'] ) || ! empty( $slide['imageUrl'] );
		}
	)
);

$heading          = sanitize_text_field( (string) ( $attributes['heading'] ?? '' ) );
$intro_title      = sanitize_text_field( (string) ( $attributes['introTitle'] ?? '' ) );
$intro_text       = sanitize_textarea_field( (string) ( $attributes['introText'] ?? '' ) );
$heading_style    = in_array( $attributes['headingStyle'] ?? 'plain', array( 'plain', 'badge' ), true ) ? (string) $attributes['headingStyle'] : 'plain';
$background_style = in_array( $attributes['backgroundStyle'] ?? 'cream', array( 'cream', 'white' ), true ) ? (string) $attributes['backgroundStyle'] : 'cream';
$arrow_style      = in_array( $attributes['arrowStyle'] ?? 'boxed', array( 'boxed', 'plain' ), true ) ? (string) $attributes['arrowStyle'] : 'boxed';
$interval         = max( 3, min( 20, absint( $attributes['interval'] ?? 6 ) ) );
$show_arrows      = ! array_key_exists( 'showArrows', $attributes ) || ! empty( $attributes['showArrows'] );
$autoplay         = ! empty( $attributes['autoplay'] );

$classes = array(
	'nntm-feature-carousel',
	'nntm-feature-carousel--bg-' . $background_style,
	'nntm-feature-carousel--heading-' . $heading_style,
	'nntm-feature-carousel--arrows-' . $arrow_style,
);

if ( ! $intro_title && ! $intro_text ) {
	$classes[] = 'nntm-feature-carousel--compact-header';
}

$wrapper = get_block_wrapper_attributes(
	array(
		'class'         => implode( ' ', $classes ),
		'data-autoplay'   => $autoplay ? '1' : '0',
		'data-interval'   => (string) $interval,
		'data-video-mode' => $video_mode,
	)
);
?>
<section <?php echo $wrapper;  ?>>
	<div class="nntm-feature-carousel__header">
		<?php if ( $heading ) : ?>
			<h2 class="nntm-feature-carousel__heading"><span><?php echo esc_html( $heading ); ?></span></h2>
		<?php endif; ?>
		<?php if ( $intro_title ) : ?>
			<p class="nntm-feature-carousel__intro-title"><?php echo esc_html( $intro_title ); ?></p>
		<?php endif; ?>
		<?php if ( $intro_text ) : ?>
			<p class="nntm-feature-carousel__intro-text"><?php echo nl2br( esc_html( $intro_text ) ); ?></p>
		<?php endif; ?>
	</div>

	<?php if ( $slides ) : ?>
		<div class="nntm-feature-carousel__slider" aria-roledescription="carousel" aria-label="<?php echo esc_attr( $heading ?: __( 'Nội dung nổi bật', 'nntm' ) ); ?>">
			<?php if ( $show_arrows && count( $slides ) > 1 ) : ?>
				<button class="nntm-feature-carousel__arrow nntm-feature-carousel__arrow--prev" type="button" aria-label="<?php esc_attr_e( 'Slide trước', 'nntm' ); ?>" data-fc-prev>←</button>
			<?php endif; ?>

			<div class="nntm-feature-carousel__track" data-fc-track tabindex="0">
				<?php foreach ( $slides as $index => $slide ) : ?>
					<?php
					$slide_heading = sanitize_text_field( (string) ( $slide['heading'] ?? '' ) );
					$slide_text    = sanitize_textarea_field( (string) ( $slide['text'] ?? '' ) );
					$cta_label     = sanitize_text_field( (string) ( $slide['ctaLabel'] ?? '' ) );
					$cta_url       = esc_url( (string) ( $slide['ctaUrl'] ?? '' ) );
					$media_type    = $slide_media_type( $slide );
					$media_html    = 'video' === $media_type
						? $render_video( $slide, 'nntm-feature-carousel__video', $slide_heading ?: __( 'Video nổi bật', 'nntm' ) )
						: $render_image( $slide, 'nntm-feature-carousel__image' );
					?>
					<article class="nntm-feature-carousel__slide nntm-feature-carousel__slide--<?php echo esc_attr( $media_type ); ?>" data-fc-slide data-index="<?php echo esc_attr( (string) $index ); ?>">
						<div class="nntm-feature-carousel__media">
							<?php echo wp_kses_post( $media_html ); ?>
						</div>
						<?php if ( $slide_heading || $slide_text || ( $cta_label && $cta_url ) ) : ?>
							<div class="nntm-feature-carousel__copy">
								<?php if ( $slide_heading ) : ?><h3><?php echo esc_html( $slide_heading ); ?></h3><?php endif; ?>
								<?php if ( $slide_text ) : ?><p><?php echo nl2br( esc_html( $slide_text ) ); ?></p><?php endif; ?>
								<?php if ( $cta_label && $cta_url ) : ?><a href="<?php echo esc_url( $cta_url ); ?>"><?php echo esc_html( $cta_label ); ?></a><?php endif; ?>
							</div>
						<?php endif; ?>
					</article>
				<?php endforeach; ?>
			</div>

			<?php if ( $show_arrows && count( $slides ) > 1 ) : ?>
				<button class="nntm-feature-carousel__arrow nntm-feature-carousel__arrow--next" type="button" aria-label="<?php esc_attr_e( 'Slide tiếp theo', 'nntm' ); ?>" data-fc-next>→</button>
			<?php endif; ?>
		</div>
	<?php endif; ?>
</section>
