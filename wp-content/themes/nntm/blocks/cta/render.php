<?php

defined( 'ABSPATH' ) || exit;

$allowed_variants = array( 'default', 'ghost', 'cta-text', 'fav-button' );
$variant           = isset( $attributes['variant'] ) ? sanitize_key( (string) $attributes['variant'] ) : 'default';
if ( ! in_array( $variant, $allowed_variants, true ) ) {
	$variant = 'default';
}

$text          = isset( $attributes['text'] ) ? (string) $attributes['text'] : '';
$url           = isset( $attributes['url'] ) ? esc_url_raw( (string) $attributes['url'] ) : '';
$opens_new_tab = ! empty( $attributes['opensInNewTab'] );
$aria_label    = isset( $attributes['ariaLabel'] ) ? trim( (string) $attributes['ariaLabel'] ) : '';
$object_id     = isset( $attributes['objectId'] ) ? absint( $attributes['objectId'] ) : 0;
$favorited     = ! empty( $attributes['favorited'] );

if ( 'fav-button' === $variant ) :
	$label = ( '' !== $text ) ? $text : __( 'Yêu thích', 'nntm' );

	$wrapper_extra = array(
		'class'               => 'nntm-cta nntm-cta--fav-button',
		'type'                => 'button',
		'aria-pressed'        => $favorited ? 'true' : 'false',
		'data-nntm-object-id' => (string) $object_id,
	);
	if ( '' !== $aria_label ) {
		$wrapper_extra['aria-label'] = $aria_label;
	}

	$wrapper_attributes = get_block_wrapper_attributes( $wrapper_extra );
	?>
	<button <?php echo $wrapper_attributes;  ?>>
		<span class="nntm-cta__icon" aria-hidden="true">
			<svg viewBox="0 0 23 21" width="23" height="21" xmlns="http://www.w3.org/2000/svg" focusable="false">
				<path d="M11.5 19.3C7.9 16.6 2 12 2 7.3 2 4.4 4.3 2 7.2 2c1.8 0 3.4.9 4.3 2.3C12.4 2.9 14 2 15.8 2 18.7 2 21 4.4 21 7.3c0 4.7-5.9 9.3-9.5 12z" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round" />
			</svg>
		</span>
		<span class="nntm-cta__label"><?php echo esc_html( $label ); ?></span>
	</button>
	<?php
else :
	$label = ( '' !== $text ) ? $text : __( 'Xem thêm', 'nntm' );

	$wrapper_extra = array(
		'class' => 'nntm-cta nntm-cta--' . $variant,
	);
	if ( '' !== $aria_label ) {
		$wrapper_extra['aria-label'] = $aria_label;
	}

	if ( '' !== $url ) {
		$wrapper_extra['href'] = $url;
		if ( $opens_new_tab ) {
			$wrapper_extra['target'] = '_blank';
			$wrapper_extra['rel']    = 'noopener';
		}
		$tag = 'a';
	} else {
		$wrapper_extra['type'] = 'button';
		$tag                   = 'button';
	}

	$wrapper_attributes = get_block_wrapper_attributes( $wrapper_extra );
	?>
	<<?php echo tag_escape( $tag ); ?> <?php echo $wrapper_attributes;  ?>>
		<span class="nntm-cta__label"><?php echo esc_html( $label ); ?></span>
	</<?php echo tag_escape( $tag ); ?>>
	<?php
endif;
