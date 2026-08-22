<?php

defined( 'ABSPATH' ) || exit;

$slides = array( 184, 182, 181, 183, 180 );
$slides = array_values( array_filter( $slides, static fn( $id ) => wp_attachment_is_image( $id ) ) );
if ( ! $slides ) { return; }
?>
<section class="nntm-vx-shell" aria-labelledby="nntm-vx-title">
	<div class="nntm-vx-shell__inner">
		<h2 id="nntm-vx-title" class="nntm-vx-shell__title">Hư Không và Vỏ Ốc</h2>
		<div class="nntm-vx-shell__stage" data-vx-gallery data-active="0">
			<button type="button" class="nntm-vx-shell__nav nntm-vx-shell__nav--prev" data-vx-prev aria-label="<?php esc_attr_e( 'Ảnh trước', 'nntm' ); ?>"><span aria-hidden="true">←</span></button>
			<div class="nntm-vx-shell__slides">
				<?php foreach ( $slides as $index => $image_id ) : ?>
					<figure class="nntm-vx-shell__slide" data-vx-slide data-index="<?php echo esc_attr( (string) $index ); ?>">
						<?php echo wp_get_attachment_image( $image_id, 'large', false, array( 'class' => 'nntm-vx-shell__img', 'loading' => 0 === $index ? 'eager' : 'lazy' ) );  ?>
					</figure>
				<?php endforeach; ?>
			</div>
			<button type="button" class="nntm-vx-shell__nav nntm-vx-shell__nav--next" data-vx-next aria-label="<?php esc_attr_e( 'Ảnh tiếp theo', 'nntm' ); ?>"><span aria-hidden="true">→</span></button>
		</div>
		<p class="nntm-vx-shell__caption"><strong>Album Tác phẩm của các Chú Tiểu &amp; Tiểu Ni</strong><span>Xem Chi Tiết</span></p>
	</div>
</section>
