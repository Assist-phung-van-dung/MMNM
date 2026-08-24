<?php

defined( 'ABSPATH' ) || exit;

function nntm_len_dau_bat(): bool {
	return (bool) apply_filters( 'nntm_len_dau_bat', ! is_admin() );
}

function nntm_len_dau_enqueue(): void {
	if ( ! nntm_len_dau_bat() ) {
		return;
	}

	$css_path = NNTM_THEME_DIR . '/assets/css/len-dau-trang.css';
	wp_enqueue_style(
		'nntm-len-dau-trang',
		NNTM_THEME_URI . '/assets/css/len-dau-trang.css',
		array( 'nntm-tokens', 'nntm-base' ),
		nntm_asset_version( $css_path )
	);

	$js_path = NNTM_THEME_DIR . '/assets/js/len-dau-trang.js';
	wp_enqueue_script(
		'nntm-len-dau-trang',
		NNTM_THEME_URI . '/assets/js/len-dau-trang.js',
		array(),
		nntm_asset_version( $js_path ),
		true
	);
}
add_action( 'wp_enqueue_scripts', 'nntm_len_dau_enqueue' );

function nntm_len_dau_render(): void {
	if ( ! nntm_len_dau_bat() ) {
		return;
	}
	?>
	<button type="button" class="nntm-len-dau" aria-hidden="true" tabindex="-1">
		<span class="nntm-sr-only"><?php esc_html_e( 'Lên đầu trang', 'nntm' ); ?></span>
		<span class="nntm-len-dau__icon" aria-hidden="true"></span>
	</button>
	<?php
}
add_action( 'wp_footer', 'nntm_len_dau_render' );
