<?php

defined( 'ABSPATH' ) || exit;

$nntm_fb_items_raw = ( isset( $attributes['items'] ) && is_array( $attributes['items'] ) ) ? $attributes['items'] : array();
$nntm_fb_items     = array();

foreach ( $nntm_fb_items_raw as $nntm_fb_item ) {
	if ( ! is_array( $nntm_fb_item ) ) {
		continue;
	}

	$nntm_fb_label = isset( $nntm_fb_item['label'] ) ? sanitize_text_field( (string) $nntm_fb_item['label'] ) : '';
	$nntm_fb_url   = isset( $nntm_fb_item['url'] ) ? trim( (string) $nntm_fb_item['url'] ) : '';

	if ( '' === $nntm_fb_label ) {
		continue;
	}

	$nntm_fb_items[] = array(
		'label' => $nntm_fb_label,
		'url'   => ( '' !== $nntm_fb_url && '#' === $nntm_fb_url[0] ) ? $nntm_fb_url : esc_url_raw( $nntm_fb_url ),
	);
}

if ( empty( $nntm_fb_items ) ) {
	return;
}

$nntm_fb_mo_khi = isset( $attributes['moKhi'] ) ? sanitize_key( (string) $attributes['moKhi'] ) : 'qua-banner';
if ( ! in_array( $nntm_fb_mo_khi, array( 'qua-banner', 'cuon-ngay' ), true ) ) {
	$nntm_fb_mo_khi = 'qua-banner';
}

$nntm_fb_wrapper = get_block_wrapper_attributes(
	array(
		'class'        => 'nntm-floating-bar',
		'data-mo-khi'  => $nntm_fb_mo_khi,
	)
);
?>
<nav <?php echo $nntm_fb_wrapper; // phpcs:ignore WordPress.Security.EscapeOutput ?> aria-label="<?php esc_attr_e( 'Liên kết nhanh', 'nntm' ); ?>">
	<div class="nntm-floating-bar__khung">
		<?php foreach ( $nntm_fb_items as $nntm_fb_item ) : ?>
			<?php if ( '' !== $nntm_fb_item['url'] ) : ?>
				<a class="nntm-floating-bar__o" href="<?php echo esc_url( $nntm_fb_item['url'] ); ?>">
					<?php echo esc_html( $nntm_fb_item['label'] ); ?>
				</a>
			<?php else : ?>
				<span class="nntm-floating-bar__o nntm-floating-bar__o--chua-co-link">
					<?php echo esc_html( $nntm_fb_item['label'] ); ?>
				</span>
			<?php endif; ?>
		<?php endforeach; ?>
	</div>
</nav>
