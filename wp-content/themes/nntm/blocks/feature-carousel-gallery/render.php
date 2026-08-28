<?php
defined( 'ABSPATH' ) || exit;

$render_image = static function ( array $item, string $class, bool $eager = false ): string {
	$id  = isset( $item['imageId'] ) ? absint( $item['imageId'] ) : 0;
	$url = isset( $item['imageUrl'] ) ? esc_url_raw( (string) $item['imageUrl'] ) : '';
	$alt = isset( $item['imageAlt'] ) ? sanitize_text_field( (string) $item['imageAlt'] ) : '';
	$attrs = array(
		'class'    => $class,
		'alt'      => $alt,
		'loading'  => $eager ? 'eager' : 'lazy',
		'decoding' => 'async',
	);
	if ( $eager ) {
		$attrs['fetchpriority'] = 'high';
	}
	if ( $id ) {
		return (string) wp_get_attachment_image( $id, 'full', false, $attrs );
	}
	if ( ! $url ) {
		return '';
	}
	$extra = $eager ? ' loading="eager" fetchpriority="high"' : ' loading="lazy"';
	return '<img class="' . esc_attr( $class ) . '" src="' . esc_url( $url ) . '" alt="' . esc_attr( $alt ) . '" decoding="async"' . $extra . '>';
};

$slides = isset( $attributes['slides'] ) && is_array( $attributes['slides'] ) ? $attributes['slides'] : array();
$slides = array_values(
	array_filter(
		$slides,
		static fn( $slide ): bool => is_array( $slide ) && ( ! empty( $slide['imageId'] ) || ! empty( $slide['imageUrl'] ) )
	)
);
$heading          = sanitize_text_field( (string) ( $attributes['heading'] ?? '' ) );
$background_style = in_array( $attributes['backgroundStyle'] ?? 'white', array( 'white', 'cream' ), true ) ? (string) $attributes['backgroundStyle'] : 'white';
$arrow_style      = in_array( $attributes['arrowStyle'] ?? 'plain', array( 'plain', 'boxed' ), true ) ? (string) $attributes['arrowStyle'] : 'plain';
$interval         = max( 3, min( 20, absint( $attributes['interval'] ?? 5 ) ) );

/*
 * Nền của khung xem tác phẩm — quản trị chọn từ bảng màu của dự án, mặc định đen.
 * Danh sách này phải trùng enum trong block.json và lớp CSS trong style.css.
 */
$viewer_bg_allowed = array( 'den', 'muc', 'mem', 'cham', 'reu', 'kem' );
$viewer_bg         = isset( $attributes['viewerBackground'] ) ? sanitize_key( (string) $attributes['viewerBackground'] ) : 'den';
if ( ! in_array( $viewer_bg, $viewer_bg_allowed, true ) ) {
	$viewer_bg = 'den';
}
$show_arrows      = ! array_key_exists( 'showArrows', $attributes ) || ! empty( $attributes['showArrows'] );
$autoplay         = ! empty( $attributes['autoplay'] );
$uid              = 'nntm-fgc-' . wp_unique_id();

$classes = array(
	'nntm-feature-gallery-carousel',
	'nntm-feature-gallery-carousel--bg-' . $background_style,
	'nntm-feature-gallery-carousel--arrows-' . $arrow_style,
);
$wrapper = get_block_wrapper_attributes(
	array(
		'class'         => implode( ' ', $classes ),
		'data-autoplay' => $autoplay ? '1' : '0',
		'data-interval' => (string) $interval,
		'id'            => $uid,
	)
);
?>
<section <?php echo $wrapper;  ?>>
	<?php if ( $heading ) : ?>
		<header class="nntm-feature-gallery-carousel__header">
			<h2 class="nntm-feature-gallery-carousel__heading"><span><?php echo esc_html( $heading ); ?></span></h2>
		</header>
	<?php endif; ?>

	<?php if ( $slides ) : ?>
		<div class="nntm-feature-gallery-carousel__slider" aria-roledescription="carousel" aria-label="<?php echo esc_attr( $heading ?: __( 'Thư viện ảnh nổi bật', 'nntm' ) ); ?>">
			<?php if ( $show_arrows && count( $slides ) > 1 ) : ?>
				<button class="nntm-feature-gallery-carousel__arrow nntm-feature-gallery-carousel__arrow--prev" type="button" aria-label="<?php esc_attr_e( 'Ảnh trước', 'nntm' ); ?>" data-fgc-prev><span aria-hidden="true">←</span></button>
			<?php endif; ?>

			<div class="nntm-feature-gallery-carousel__track" data-fgc-track tabindex="0">
				<?php foreach ( $slides as $index => $slide ) :
					$title        = sanitize_text_field( (string) ( $slide['title'] ?? '' ) );
					$detail_label = sanitize_text_field( (string) ( $slide['detailLabel'] ?? __( 'Xem Chi Tiết', 'nntm' ) ) );
					$modal_id     = $uid . '-dialog-' . $index;
					?>
					<figure class="nntm-feature-gallery-carousel__slide" data-fgc-slide data-index="<?php echo esc_attr( (string) $index ); ?>">
						<?php
						/*
						 * Chính tấm ảnh là nút mở khung xem tác phẩm: bấm thẳng vào ảnh
						 * là xem được, không bắt phải bấm CTA. Dùng <button> thật nên
						 * Tab/Enter/Space đều dùng được; view.js gỡ Tab khỏi các ảnh
						 * không nằm giữa để bàn phím không lạc vào slide đang bị ẩn.
						 */
						?>
						<button
							class="nntm-feature-gallery-carousel__media"
							type="button"
							data-fgc-open="<?php echo esc_attr( $modal_id ); ?>"
							data-fgc-media
							aria-label="<?php echo esc_attr( $title ? sprintf( __( 'Xem tác phẩm: %s', 'nntm' ), $title ) : __( 'Xem tác phẩm', 'nntm' ) ); ?>"
						>
							<?php echo wp_kses_post( $render_image( $slide, 'nntm-feature-gallery-carousel__image', 0 === $index ) ); ?>
						</button>
						<figcaption class="nntm-feature-gallery-carousel__copy">
							<?php if ( $title ) : ?><h3><?php echo esc_html( $title ); ?></h3><?php endif; ?>
							<?php if ( $detail_label ) : ?>
								<button class="nntm-feature-gallery-carousel__detail" type="button" data-fgc-open="<?php echo esc_attr( $modal_id ); ?>"><?php echo esc_html( $detail_label ); ?></button>
							<?php endif; ?>
						</figcaption>
					</figure>
				<?php endforeach; ?>
			</div>

			<?php if ( $show_arrows && count( $slides ) > 1 ) : ?>
				<button class="nntm-feature-gallery-carousel__arrow nntm-feature-gallery-carousel__arrow--next" type="button" aria-label="<?php esc_attr_e( 'Ảnh tiếp theo', 'nntm' ); ?>" data-fgc-next><span aria-hidden="true">→</span></button>
			<?php endif; ?>
		</div>

		<div class="nntm-feature-gallery-carousel__dialogs">
			<?php foreach ( $slides as $index => $slide ) :
				$title       = sanitize_text_field( (string) ( $slide['title'] ?? '' ) );
				$popup_title = sanitize_text_field( (string) ( $slide['popupTitle'] ?? '' ) );
				$popup_title = $popup_title ?: $title;
				$popup_intro = sanitize_textarea_field( (string) ( $slide['popupIntro'] ?? '' ) );
				$details_raw = isset( $slide['details'] ) && is_array( $slide['details'] ) ? array_values( $slide['details'] ) : array();
				$details     = array_values(
					array_filter(
						$details_raw,
						static function ( $detail ): bool {
							return is_array( $detail ) && (
								! empty( $detail['imageId'] ) ||
								! empty( $detail['imageUrl'] ) ||
								! empty( $detail['title'] ) ||
								! empty( $detail['text'] )
							);
						}
					)
				);
				$modal_id = $uid . '-dialog-' . $index;
				?>
				<div class="nntm-feature-gallery-modal nntm-feature-gallery-modal--nen-<?php echo esc_attr( $viewer_bg ); ?>" id="<?php echo esc_attr( $modal_id ); ?>" role="dialog" aria-modal="true" aria-hidden="true" aria-labelledby="<?php echo esc_attr( $modal_id . '-title' ); ?>" data-fgc-dialog hidden>
					<div class="nntm-feature-gallery-modal__backdrop" data-fgc-close></div>
					<div class="nntm-feature-gallery-modal__panel" role="document">
						<span class="nntm-feature-gallery-modal__quote" aria-hidden="true">&ldquo;</span>
						<button class="nntm-feature-gallery-modal__close" type="button" aria-label="<?php esc_attr_e( 'Đóng', 'nntm' ); ?>" data-fgc-close><span aria-hidden="true">×</span></button>

						<header class="nntm-feature-gallery-modal__header">
							<?php if ( $popup_title ) : ?><h3 id="<?php echo esc_attr( $modal_id . '-title' ); ?>"><?php echo esc_html( $popup_title ); ?></h3><?php endif; ?>
							<?php if ( $popup_intro ) : ?><p><?php echo nl2br( esc_html( $popup_intro ) ); ?></p><?php endif; ?>
						</header>

						<?php if ( $details ) : ?>
							<div class="nntm-feature-gallery-modal__carousel" data-fgc-popup-carousel>
								<?php if ( count( $details ) > 1 ) : ?>
									<button class="nntm-feature-gallery-modal__arrow nntm-feature-gallery-modal__arrow--prev" type="button" aria-label="<?php esc_attr_e( 'Ảnh chi tiết trước', 'nntm' ); ?>" data-fgc-popup-prev><span aria-hidden="true">←</span></button>
								<?php endif; ?>

								<div class="nntm-feature-gallery-modal__viewport" data-fgc-popup-viewport tabindex="0">
									<?php foreach ( $details as $detail_index => $detail ) :
										$detail_title = sanitize_text_field( (string) ( $detail['title'] ?? '' ) );
										$detail_text  = sanitize_textarea_field( (string) ( $detail['text'] ?? '' ) );
										$detail_image = $render_image( $detail, 'nntm-feature-gallery-modal__image', 0 === $detail_index );
										$detail_co_chu = ( '' !== $detail_title || '' !== $detail_text );
										?>
										<article class="nntm-feature-gallery-modal__slide<?php echo $detail_co_chu ? '' : ' nntm-feature-gallery-modal__slide--anh-doc'; ?>" data-fgc-popup-slide data-popup-index="<?php echo esc_attr( (string) $detail_index ); ?>" aria-hidden="<?php echo 0 === $detail_index ? 'false' : 'true'; ?>">
											<?php if ( $detail_image ) : ?>
												<div class="nntm-feature-gallery-modal__media"><?php echo wp_kses_post( $detail_image ); ?></div>
											<?php endif; ?>
											<?php if ( $detail_title || $detail_text ) : ?>
												<div class="nntm-feature-gallery-modal__body">
													<?php if ( $detail_title ) : ?><h4><?php echo esc_html( $detail_title ); ?></h4><?php endif; ?>
													<?php if ( $detail_text ) : ?><p><?php echo nl2br( esc_html( $detail_text ) ); ?></p><?php endif; ?>
												</div>
											<?php endif; ?>
										</article>
									<?php endforeach; ?>
								</div>

								<?php if ( count( $details ) > 1 ) : ?>
									<button class="nntm-feature-gallery-modal__arrow nntm-feature-gallery-modal__arrow--next" type="button" aria-label="<?php esc_attr_e( 'Ảnh chi tiết tiếp theo', 'nntm' ); ?>" data-fgc-popup-next><span aria-hidden="true">→</span></button>
								<?php endif; ?>
							</div>

							<footer class="nntm-feature-gallery-modal__footer">
								<div class="nntm-feature-gallery-modal__dots" role="tablist" aria-label="<?php esc_attr_e( 'Chọn ảnh chi tiết', 'nntm' ); ?>">
									<?php foreach ( $details as $detail_index => $_detail ) : ?>
										<button class="nntm-feature-gallery-modal__dot" type="button" aria-label="<?php echo esc_attr( sprintf( __( 'Ảnh %d', 'nntm' ), $detail_index + 1 ) ); ?>" aria-selected="<?php echo 0 === $detail_index ? 'true' : 'false'; ?>" data-fgc-popup-dot="<?php echo esc_attr( (string) $detail_index ); ?>"></button>
									<?php endforeach; ?>
								</div>
								<div class="nntm-feature-gallery-modal__counter" aria-live="polite"><span data-fgc-popup-current>1</span><span aria-hidden="true"> / </span><span><?php echo esc_html( (string) count( $details ) ); ?></span></div>
							</footer>
						<?php else : ?>
							<p class="nntm-feature-gallery-modal__empty"><?php esc_html_e( 'Chưa có nội dung chi tiết.', 'nntm' ); ?></p>
						<?php endif; ?>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>
</section>
