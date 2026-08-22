<?php

defined( 'ABSPATH' ) || exit;

function nntm_engineering_earth_extract_youtube_id( string $raw ): string {
	$value = trim( $raw );

	if ( '' === $value ) {
		return '';
	}

	if ( preg_match( '/^[A-Za-z0-9_-]{11}$/', $value ) ) {
		return $value;
	}

	if ( preg_match( '#(?:youtube(?:-nocookie)?\.com/(?:watch\?v=|embed/|shorts/|v/)|youtu\.be/)([A-Za-z0-9_-]{11})#i', $value, $matches ) ) {
		return $matches[1];
	}

	return '';
}

function nntm_engineering_earth_render_slot_media( string $video_id, string $fallback_image_html ): string {
	$poster_url = '' !== $video_id ? esc_url( 'https://img.youtube.com/vi/' . $video_id . '/hqdefault.jpg' ) : '';

	ob_start();
	?>
	<?php if ( '' !== $poster_url ) : ?>
		<img class="nntm-engineering-earth__video-poster" src="<?php echo $poster_url;  ?>" alt="" loading="lazy" decoding="async" />
	<?php elseif ( '' !== $fallback_image_html ) : ?>
		<?php echo $fallback_image_html;  ?>
	<?php else : ?>
		<span class="nntm-engineering-earth__video-poster nntm-engineering-earth__video-poster--rong" aria-hidden="true">
			<svg viewBox="0 0 48 48" width="30" height="30" fill="none" focusable="false">
				<rect x="4" y="10" width="40" height="28" rx="4" stroke="currentColor" stroke-width="2" />
				<path d="M20 18 L30 24 L20 30 Z" fill="currentColor" />
			</svg>
		</span>
	<?php endif; ?>
	<div class="nntm-engineering-earth__video-embed" aria-hidden="true"></div>
	<?php
	return trim( (string) ob_get_clean() );
}

function nntm_engineering_earth_render_video_slot( string $video_id, string $role, string $label, string $fallback_image_html = '', string $link_url = '' ): string {
	$is_main    = ( 'main' === $role );
	$role_class = $is_main ? 'nntm-engineering-earth__video-slot--main' : 'nntm-engineering-earth__video-slot--bg';

	ob_start();
	?>
	<div
		class="nntm-engineering-earth__video-slot <?php echo esc_attr( $role_class ); ?>"
		data-role="<?php echo esc_attr( $role ); ?>"
		data-video-id="<?php echo esc_attr( $video_id ); ?>"
		aria-label="<?php echo esc_attr( $label ); ?>"
	>
		<?php echo nntm_engineering_earth_render_slot_media( $video_id, $is_main ? $fallback_image_html : '' );  ?>
		<?php if ( '' !== $link_url ) : ?>
			<a class="nntm-engineering-earth__video-link" href="<?php echo esc_url( $link_url ); ?>">
				<span class="nntm-sr-only"><?php esc_html_e( 'Xem bài viết video', 'nntm' ); ?></span>
			</a>
		<?php endif; ?>
	</div>
	<?php
	return trim( (string) ob_get_clean() );
}

function nntm_engineering_earth_render_main_fallback_image( int $image_id, string $image_url, string $image_alt ): string {
	if ( $image_id > 0 ) {
		$html = wp_get_attachment_image(
			$image_id,
			'large',
			false,
			array(
				'class'   => 'nntm-engineering-earth__video-poster',
				'loading' => 'lazy',
				'alt'     => $image_alt,
			)
		);
		if ( $html ) {
			return $html;
		}
	}

	if ( '' !== $image_url ) {
		return sprintf(
			'<img class="nntm-engineering-earth__video-poster" src="%s" alt="%s" loading="lazy" />',
			esc_url( $image_url ),
			esc_attr( $image_alt )
		);
	}

	return '';
}

function nntm_engineering_earth_render_video_stage( string $main_video_url_or_id, string $bg_video_url_or_id, int $main_image_id, string $main_image_url, string $main_image_alt, string $video_link_url = '' ): string {
	$main_id = nntm_engineering_earth_extract_youtube_id( $main_video_url_or_id );
	$bg_id   = nntm_engineering_earth_extract_youtube_id( $bg_video_url_or_id );

	$main_fallback_image = ( '' === $main_id )
		? nntm_engineering_earth_render_main_fallback_image( $main_image_id, $main_image_url, $main_image_alt )
		: '';

	ob_start();
	?>
	<div class="nntm-engineering-earth__video-stage" data-nntm-ee-stage="1">
		<?php echo nntm_engineering_earth_render_video_slot( $main_id, 'main', __( 'Video chính', 'nntm' ), $main_fallback_image, $video_link_url );  ?>
		<!-- <?php echo nntm_engineering_earth_render_video_slot( $bg_id, 'bg', __( 'Video nền', 'nntm' ), '', $video_link_url );  ?> -->
	</div>
	<?php
	return trim( (string) ob_get_clean() );
}
