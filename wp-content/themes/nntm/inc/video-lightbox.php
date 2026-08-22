<?php

defined( 'ABSPATH' ) || exit;

function nntm_video_lightbox_co_trong_khoi( array $blocks ): bool {
	foreach ( $blocks as $block ) {
		if ( ! is_array( $block ) || empty( $block['blockName'] ) ) {
			continue;
		}

		if ( 'nntm/engineering-earth' === $block['blockName'] ) {
			return true;
		}

		$attrs = isset( $block['attrs'] ) && is_array( $block['attrs'] ) ? $block['attrs'] : array();

		if (
			'nntm/card-list' === $block['blockName'] &&
			isset( $attrs['videoSource'] ) &&
			'youtube' === $attrs['videoSource']
		) {
			return true;
		}

		if ( ! empty( $block['innerBlocks'] ) && is_array( $block['innerBlocks'] ) ) {
			if ( nntm_video_lightbox_co_trong_khoi( $block['innerBlocks'] ) ) {
				return true;
			}
		}
	}

	return false;
}

function nntm_video_lightbox_can_tren_trang(): bool {
	static $can = null;

	if ( null !== $can ) {
		return $can;
	}

	$post = get_post();
	$can  = ( $post instanceof WP_Post ) && nntm_video_lightbox_co_trong_khoi( parse_blocks( $post->post_content ) );

	return $can;
}

function nntm_video_lightbox_enqueue(): void {
	if ( ! nntm_video_lightbox_can_tren_trang() ) {
		return;
	}

	$css_path = NNTM_THEME_DIR . '/assets/css/video-lightbox.css';
	wp_enqueue_style(
		'nntm-video-lightbox',
		NNTM_THEME_URI . '/assets/css/video-lightbox.css',
		array( 'nntm-tokens', 'nntm-base' ),
		nntm_asset_version( $css_path )
	);

	$js_path = NNTM_THEME_DIR . '/assets/js/video-lightbox.js';
	wp_enqueue_script(
		'nntm-video-lightbox',
		NNTM_THEME_URI . '/assets/js/video-lightbox.js',
		array(),
		nntm_asset_version( $js_path ),
		true
	);
}
add_action( 'wp_enqueue_scripts', 'nntm_video_lightbox_enqueue' );

function nntm_video_lightbox_render(): void {
	if ( ! nntm_video_lightbox_can_tren_trang() ) {
		return;
	}
	?>
	<div class="nntm-yt-lightbox" id="nntm-yt-lightbox" hidden>
		<div class="nntm-yt-lightbox__overlay" data-nntm-yt-lightbox-close></div>

		<div class="nntm-yt-lightbox__panel" role="dialog" aria-modal="true" aria-label="<?php esc_attr_e( 'Trình phát video', 'nntm' ); ?>">
			<button type="button" class="nntm-yt-lightbox__close" data-nntm-yt-lightbox-close>
				<span class="nntm-sr-only"><?php esc_html_e( 'Đóng video', 'nntm' ); ?></span>
				<span aria-hidden="true">&times;</span>
			</button>

			<div class="nntm-yt-lightbox__frame" data-nntm-yt-lightbox-frame></div>
		</div>
	</div>
	<?php
}
add_action( 'wp_footer', 'nntm_video_lightbox_render' );
