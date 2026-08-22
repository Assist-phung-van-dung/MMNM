<?php

defined( 'ABSPATH' ) || exit;

$eyebrow = isset( $attributes['eyebrow'] ) ? (string) $attributes['eyebrow'] : '';
$heading = isset( $attributes['heading'] ) ? (string) $attributes['heading'] : '';
$content = isset( $attributes['content'] ) ? (string) $attributes['content'] : '';

$image_id  = isset( $attributes['imageId'] ) ? absint( $attributes['imageId'] ) : 0;
$image_url = isset( $attributes['imageUrl'] ) ? esc_url_raw( (string) $attributes['imageUrl'] ) : '';
$image_alt = isset( $attributes['imageAlt'] ) ? trim( (string) $attributes['imageAlt'] ) : '';

$media_position = isset( $attributes['mediaPosition'] ) ? sanitize_key( (string) $attributes['mediaPosition'] ) : 'right';
if ( ! in_array( $media_position, array( 'left', 'right' ), true ) ) {
	$media_position = 'right';
}

$wrapper_attributes = get_block_wrapper_attributes(
	array(
		'class' => 'nntm-feature nntm-feature--media-' . $media_position,
	)
);
?>
<section <?php echo $wrapper_attributes;  ?>>
	<div class="nntm-container">
		<div class="nntm-feature__content">
			<div class="nntm-feature__text">
				<div class="nntm-feature__text-inner">
					<?php if ( '' !== trim( wp_strip_all_tags( $eyebrow ) ) ) : ?>
						<span class="nntm-feature__eyebrow"><?php echo wp_kses_post( $eyebrow ); ?></span>
					<?php endif; ?>

					<?php if ( '' !== trim( wp_strip_all_tags( $heading ) ) ) : ?>
						<h2 class="nntm-feature__heading"><?php echo wp_kses_post( $heading ); ?></h2>
					<?php endif; ?>

					<?php if ( '' !== trim( wp_strip_all_tags( $content ) ) ) : ?>
						<div class="nntm-feature__body"><?php echo wp_kses_post( $content ); ?></div>
					<?php endif; ?>
				</div>
			</div>

			<div class="nntm-feature__media">
				<?php

				$is_decorative = ( '' === $image_alt );

				if ( $image_id > 0 ) :
					$image_attrs = array(
						'class'   => 'nntm-feature__media-img',
						'loading' => 'lazy',
						'alt'     => $image_alt,
					);
					if ( $is_decorative ) {
						$image_attrs['role'] = 'presentation';
					}
					echo wp_kses_post(
						wp_get_attachment_image( $image_id, 'large', false, $image_attrs )
					);
				elseif ( '' !== $image_url ) :
					?>
					<img
						class="nntm-feature__media-img"
						src="<?php echo esc_url( $image_url ); ?>"
						alt="<?php echo esc_attr( $image_alt ); ?>"
						loading="lazy"
						<?php echo $is_decorative ? 'role="presentation"' : '';  ?>
					/>
					<?php
				else :
					?>
					<span class="nntm-feature__media-placeholder" aria-hidden="true"></span>
					<?php
				endif;
				?>
			</div>
		</div>
	</div>
</section>
