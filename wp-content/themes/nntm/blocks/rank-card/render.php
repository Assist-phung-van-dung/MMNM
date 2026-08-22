<?php

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/inc/render-rank-card.php';

$nntm_rc_heading = isset( $attributes['heading'] ) ? (string) $attributes['heading'] : '';

$nntm_rc_bg_media_type = isset( $attributes['bgMediaType'] ) ? sanitize_key( (string) $attributes['bgMediaType'] ) : 'image';
if ( ! in_array( $nntm_rc_bg_media_type, array( 'image', 'video' ), true ) ) {
	$nntm_rc_bg_media_type = 'image';
}

$nntm_rc_bg_image_id  = isset( $attributes['bgImageId'] ) ? absint( $attributes['bgImageId'] ) : 0;
$nntm_rc_bg_image_url = isset( $attributes['bgImageUrl'] ) ? esc_url_raw( (string) $attributes['bgImageUrl'] ) : '';
$nntm_rc_bg_image_alt = isset( $attributes['bgImageAlt'] ) ? trim( (string) $attributes['bgImageAlt'] ) : '';

if ( $nntm_rc_bg_image_id > 0 ) {
	$nntm_rc_bg_src = 'video' === $nntm_rc_bg_media_type
		? wp_get_attachment_url( $nntm_rc_bg_image_id )
		: wp_get_attachment_image_url( $nntm_rc_bg_image_id, 'full' );
	if ( $nntm_rc_bg_src ) {
		$nntm_rc_bg_image_url = $nntm_rc_bg_src;
	}
}

$nntm_rc_min_height = isset( $attributes['minHeight'] ) ? absint( $attributes['minHeight'] ) : 690;
if ( $nntm_rc_min_height <= 0 ) {
	$nntm_rc_min_height = 690;
}

$nntm_rc_raw_cards = ( isset( $attributes['cards'] ) && is_array( $attributes['cards'] ) ) ? $attributes['cards'] : array();

$nntm_rc_cards = array();
foreach ( $nntm_rc_raw_cards as $nntm_rc_raw_card ) {
	if ( ! is_array( $nntm_rc_raw_card ) ) {
		continue;
	}
	$nntm_rc_clean_card = nntm_rank_card_clean_card( $nntm_rc_raw_card );
	if ( null !== $nntm_rc_clean_card ) {
		$nntm_rc_cards[] = $nntm_rc_clean_card;
	}
}


if ( empty( $nntm_rc_cards ) ) {
	if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
		$nntm_rc_wrapper_attributes = get_block_wrapper_attributes( array( 'class' => 'nntm-rank-card nntm-rank-card--empty' ) );
		?>
		<div <?php echo $nntm_rc_wrapper_attributes;  ?>>
			<p class="nntm-rank-card__empty-notice">
				<?php esc_html_e( 'Chưa có thẻ cấp bậc nào. Mở bảng điều khiển bên phải để thêm ít nhất một thẻ.', 'nntm' ); ?>
			</p>
		</div>
		<?php
	}
	return;
}

$nntm_rc_style = 'min-height:' . $nntm_rc_min_height . 'px;';
if ( 'image' === $nntm_rc_bg_media_type && '' !== $nntm_rc_bg_image_url ) {
	$nntm_rc_style .= 'background-image:url(' . esc_url( $nntm_rc_bg_image_url ) . ');';
}

$nntm_rc_wrapper_extra = array(
	'class' => 'nntm-rank-card',
	'style' => $nntm_rc_style,
);



if ( 'image' === $nntm_rc_bg_media_type && '' !== $nntm_rc_bg_image_alt ) {
	$nntm_rc_wrapper_extra['role']       = 'img';
	$nntm_rc_wrapper_extra['aria-label'] = $nntm_rc_bg_image_alt;
}

$nntm_rc_wrapper_attributes = get_block_wrapper_attributes( $nntm_rc_wrapper_extra );
?>
<section <?php echo $nntm_rc_wrapper_attributes;  ?>>
	<?php if ( 'video' === $nntm_rc_bg_media_type && '' !== $nntm_rc_bg_image_url ) : ?>
		<video class="nntm-rank-card__bg-video" src="<?php echo esc_url( $nntm_rc_bg_image_url ); ?>" autoplay muted loop playsinline preload="auto" aria-hidden="true"></video>
	<?php endif; ?>
	<div class="nntm-rank-card__overlay">
		<?php if ( '' !== trim( wp_strip_all_tags( $nntm_rc_heading ) ) ) : ?>
			<h2 class="nntm-rank-card__heading"><?php echo wp_kses_post( $nntm_rc_heading ); ?></h2>
		<?php endif; ?>

		<div class="nntm-rank-card__row">
			<?php foreach ( $nntm_rc_cards as $nntm_rc_card ) : ?>
				<?php echo nntm_rank_card_render_card( $nntm_rc_card );  ?>
			<?php endforeach; ?>
		</div>
	</div>
</section>
