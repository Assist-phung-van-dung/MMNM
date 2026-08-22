<?php

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'nntm_banner_clean_slide' ) ) {

	function nntm_banner_clean_slide( array $slide ): ?array {
		$media_type = isset( $slide['mediaType'] ) ? sanitize_key( (string) $slide['mediaType'] ) : 'image';
		if ( ! in_array( $media_type, array( 'image', 'video' ), true ) ) {
			$media_type = 'image';
		}

		$clean = array(
			'mediaType' => $media_type,
			'imageId'  => isset( $slide['imageId'] ) ? absint( $slide['imageId'] ) : 0,
			'imageUrl' => isset( $slide['imageUrl'] ) ? esc_url_raw( (string) $slide['imageUrl'] ) : '',
			'imageAlt' => isset( $slide['imageAlt'] ) ? sanitize_text_field( (string) $slide['imageAlt'] ) : '',

			'heading'  => isset( $slide['heading'] ) ? sanitize_textarea_field( (string) $slide['heading'] ) : '',
			'text'     => isset( $slide['text'] ) ? sanitize_textarea_field( (string) $slide['text'] ) : '',


			'showButton'  => ! empty( $slide['showButton'] ),
			'buttonLabel' => isset( $slide['buttonLabel'] ) ? sanitize_text_field( (string) $slide['buttonLabel'] ) : '',
		);

		$co_media = ( $clean['imageId'] > 0 || '' !== $clean['imageUrl'] );
		$co_chu = ( '' !== trim( $clean['heading'] ) || '' !== trim( $clean['text'] ) || $clean['showButton'] );

		return ( $co_media || $co_chu ) ? $clean : null;
	}
}

if ( ! function_exists( 'nntm_banner_render_anh' ) ) {

	function nntm_banner_render_anh( array $slide, int $index ): string {
		$media_type = isset( $slide['mediaType'] ) ? (string) $slide['mediaType'] : 'image';

		if ( 'video' === $media_type ) {
			$video_url = '';
			if ( $slide['imageId'] > 0 ) {
				$attachment_url = wp_get_attachment_url( $slide['imageId'] );
				if ( $attachment_url ) {
					$video_url = $attachment_url;
				}
			}
			if ( '' === $video_url && '' !== $slide['imageUrl'] ) {
				$video_url = $slide['imageUrl'];
			}

			if ( '' === $video_url ) {
				return '';
			}

			return sprintf(
				'<video class="nntm-banner__img nntm-banner__video" src="%1$s" autoplay muted loop playsinline preload="auto" aria-hidden="true"></video>',
				esc_url( $video_url )
			);
		}
		$alt         = $slide['imageAlt'];
		$trang_tri   = ( '' === $alt );
		$thuoc_tinh  = array(
			'class' => 'nntm-banner__img',
			'alt'   => $alt,

			'loading'  => 0 === $index ? 'eager' : 'lazy',
			'decoding' => 'async',
		);
		if ( $trang_tri ) {
			$thuoc_tinh['role'] = 'presentation';
		}

		if ( $slide['imageId'] > 0 ) {
			return wp_get_attachment_image( $slide['imageId'], 'full', false, $thuoc_tinh );
		}

		if ( '' !== $slide['imageUrl'] ) {
			return sprintf(
				'<img class="nntm-banner__img" src="%1$s" alt="%2$s" loading="%3$s" decoding="async"%4$s />',
				esc_url( $slide['imageUrl'] ),
				esc_attr( $alt ),
				esc_attr( $thuoc_tinh['loading'] ),
				$trang_tri ? ' role="presentation"' : ''
			);
		}

		return '';
	}
}

if ( ! function_exists( 'nntm_banner_render_dots' ) ) {

	function nntm_banner_render_dots( int $tong ): string {
		$html = '<div class="nntm-banner__dots" data-nntm-banner-dots>';
		for ( $i = 0; $i < $tong; $i++ ) {
			$html .= sprintf(
				'<button type="button" class="nntm-banner__dot%1$s" data-nntm-banner-dot="%2$d" aria-label="%3$s"%4$s></button>',
				0 === $i ? ' is-active' : '',
				$i,
				esc_attr(
					sprintf(

						__( 'Tới tấm %1$d trên %2$d', 'nntm' ),
						$i + 1,
						$tong
					)
				),
				0 === $i ? ' aria-current="true"' : ''
			);
		}
		$html .= '</div>';

		return $html;
	}
}
