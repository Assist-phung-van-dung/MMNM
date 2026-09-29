<?php

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'nntm_hero_slider_clean_slide' ) ) {
	 
	function nntm_hero_slider_clean_slide( array $raw_slide ): ?array {
		$image_id  = isset( $raw_slide['imageId'] ) ? absint( $raw_slide['imageId'] ) : 0;
		$image_url = isset( $raw_slide['imageUrl'] ) ? esc_url_raw( (string) $raw_slide['imageUrl'] ) : '';
		$image_alt = isset( $raw_slide['imageAlt'] ) ? sanitize_text_field( (string) $raw_slide['imageAlt'] ) : '';

		 

		$heading   = isset( $raw_slide['heading'] ) ? sanitize_textarea_field( (string) $raw_slide['heading'] ) : '';
		$text      = isset( $raw_slide['text'] ) ? sanitize_text_field( (string) $raw_slide['text'] ) : '';
		$cta_label = isset( $raw_slide['ctaLabel'] ) ? sanitize_text_field( (string) $raw_slide['ctaLabel'] ) : '';
		$cta_url   = isset( $raw_slide['ctaUrl'] ) ? esc_url_raw( (string) $raw_slide['ctaUrl'] ) : '';

		$has_image   = $image_id > 0 || '' !== $image_url;
		$has_heading = '' !== trim( $heading );
		$has_text    = '' !== trim( $text );

		if ( ! $has_image && ! $has_heading && ! $has_text ) {
			 
			return null;
		}

		return array(
			'image_id'  => $image_id,
			'image_url' => $image_url,
			'image_alt' => $image_alt,
			'heading'   => $heading,
			'text'      => $text,
			'cta_label' => $cta_label,
			'cta_url'   => $cta_url,
		);
	}
}

if ( ! function_exists( 'nntm_hero_slider_status_text' ) ) {
	 
	function nntm_hero_slider_status_text( int $current, int $total ): string {
		return sprintf(
			 
			__( 'Tấm %1$d trên %2$d', 'nntm' ),
			$current,
			$total
		);
	}
}

if ( ! function_exists( 'nntm_hero_slider_render_image' ) ) {
	 
	function nntm_hero_slider_render_image( array $slide, bool $is_first ): string {
		$loading_attr = $is_first ? 'eager' : 'lazy';

		ob_start();

		if ( $slide['image_id'] > 0 ) {
			$image_attrs = array(
				'class'   => 'nntm-hero-slider__img',
				'loading' => $loading_attr,
				'alt'     => $slide['image_alt'],
			);
			if ( $is_first ) {
				$image_attrs['fetchpriority'] = 'high';
			}
			echo wp_kses_post( wp_get_attachment_image( $slide['image_id'], 'full', false, $image_attrs ) );
		} elseif ( '' !== $slide['image_url'] ) {
			?>
			<img
				class="nntm-hero-slider__img"
				src="<?php echo esc_url( $slide['image_url'] ); ?>"
				alt="<?php echo esc_attr( $slide['image_alt'] ); ?>"
				loading="<?php echo esc_attr( $loading_attr ); ?>"
				<?php echo $is_first ? 'fetchpriority="high"' : '';  ?>
			/>
			<?php
		} else {
			echo '<span class="nntm-hero-slider__img-placeholder" aria-hidden="true"></span>';
		}

		return trim( (string) ob_get_clean() );
	}
}

if ( ! function_exists( 'nntm_hero_slider_render_slide' ) ) {
	 
	function nntm_hero_slider_render_slide( array $slide, int $index, int $total, bool $has_multiple ): string {
		$has_heading = '' !== trim( $slide['heading'] );
		$has_text    = '' !== trim( $slide['text'] );
		$has_cta     = '' !== trim( $slide['cta_label'] ) && '' !== $slide['cta_url'];
		$has_content = $has_heading || $has_text || $has_cta;

		ob_start();
		?>
		<div
			class="nntm-hero-slider__slide<?php echo 0 === $index ? ' is-active' : ''; ?>"
			data-nntm-hero-slide="<?php echo esc_attr( (string) $index ); ?>"
			<?php if ( $has_multiple ) : ?>
				role="group"
				aria-roledescription="slide"
				aria-label="<?php echo esc_attr( nntm_hero_slider_status_text( $index + 1, $total ) ); ?>"
			<?php endif; ?>
		>
			<?php echo nntm_hero_slider_render_image( $slide, 0 === $index );  ?>

			<span class="nntm-hero-slider__overlay" aria-hidden="true"></span>

			<?php if ( $has_content ) : ?>
				<div class="nntm-hero-slider__content">
					<?php if ( $has_heading ) : ?>
						<h2 class="nntm-hero-slider__heading"><?php echo nntm_hero_slider_multiline( $slide['heading'] );  ?></h2>
					<?php endif; ?>

					<?php if ( $has_text ) : ?>
						<p class="nntm-hero-slider__text"><?php echo esc_html( $slide['text'] ); ?></p>
					<?php endif; ?>

					<?php if ( $has_cta ) : ?>
						<a class="nntm-hero-slider__cta" href="<?php echo esc_url( $slide['cta_url'] ); ?>">
							<?php echo esc_html( $slide['cta_label'] ); ?>
						</a>
					<?php endif; ?>
				</div>
			<?php endif; ?>
		</div>
		<?php
		return trim( (string) ob_get_clean() );
	}
}

if ( ! function_exists( 'nntm_hero_slider_multiline' ) ) {
	 
	function nntm_hero_slider_multiline( string $text ): string {
		$lines = preg_split( '/\r\n|\r|\n/', $text );
		$lines = array_map( 'esc_html', $lines );
		return implode( '<br />', $lines );
	}
}

if ( ! function_exists( 'nntm_hero_slider_render_dots' ) ) {
	 
	function nntm_hero_slider_render_dots( int $total ): string {
		ob_start();
		?>
		<div class="nntm-hero-slider__dots" data-nntm-hero-dots>
			<?php for ( $i = 0; $i < $total; $i++ ) : ?>
				<button
					type="button"
					class="nntm-hero-slider__dot<?php echo 0 === $i ? ' is-active' : ''; ?>"
					data-nntm-hero-dot="<?php echo esc_attr( (string) $i ); ?>"
					aria-label="<?php echo esc_attr( sprintf(   __( 'Xem tấm %d', 'nntm' ), $i + 1 ) ); ?>"
					<?php echo 0 === $i ? 'aria-current="true"' : '';  ?>
				></button>
			<?php endfor; ?>
		</div>
		<?php
		return trim( (string) ob_get_clean() );
	}
}

if ( ! function_exists( 'nntm_hero_slider_render_nav' ) ) {
	 
	function nntm_hero_slider_render_nav(): string {
		ob_start();
		?>
		<button
			type="button"
			class="nntm-hero-slider__nav nntm-hero-slider__nav--prev"
			data-nntm-hero-prev
			aria-label="<?php esc_attr_e( 'Tấm trước', 'nntm' ); ?>"
		>
			<svg viewBox="0 0 20 16" aria-hidden="true" focusable="false">
				<path d="M8 1 1 8l7 7M1 8h18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
			</svg>
		</button>
		<button
			type="button"
			class="nntm-hero-slider__nav nntm-hero-slider__nav--next"
			data-nntm-hero-next
			aria-label="<?php esc_attr_e( 'Tấm sau', 'nntm' ); ?>"
		>
			<svg viewBox="0 0 20 16" aria-hidden="true" focusable="false">
				<path d="M12 1l7 7-7 7M19 8H1" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
			</svg>
		</button>
		<?php
		return trim( (string) ob_get_clean() );
	}
}

if ( ! function_exists( 'nntm_hero_slider_hex_to_rgb' ) ) {

	function nntm_hero_slider_hex_to_rgb( string $hex ): array {
		$hex = trim( $hex );

		if ( ! preg_match( '/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/', $hex ) ) {
			return array();
		}

		$raw = substr( $hex, 1 );

		if ( 3 === strlen( $raw ) ) {
			$raw = $raw[0] . $raw[0] . $raw[1] . $raw[1] . $raw[2] . $raw[2];
		}

		return array(
			(int) hexdec( substr( $raw, 0, 2 ) ),
			(int) hexdec( substr( $raw, 2, 2 ) ),
			(int) hexdec( substr( $raw, 4, 2 ) ),
		);
	}
}

if ( ! function_exists( 'nntm_hero_slider_color_css' ) ) {

	function nntm_hero_slider_color_css( string $hex, float $alpha = 1.0 ): string {
		$rgb = nntm_hero_slider_hex_to_rgb( $hex );

		if ( empty( $rgb ) ) {
			return '';
		}

		$alpha = max( 0.0, min( 1.0, $alpha ) );

		if ( $alpha >= 1.0 ) {
			return sprintf( 'rgb(%d,%d,%d)', $rgb[0], $rgb[1], $rgb[2] );
		}

		return sprintf( 'rgba(%d,%d,%d,%s)', $rgb[0], $rgb[1], $rgb[2], number_format( $alpha, 2, '.', '' ) );
	}
}

if ( ! function_exists( 'nntm_hero_slider_contrast_color' ) ) {

	function nntm_hero_slider_contrast_color( string $hex ): string {
		$rgb = nntm_hero_slider_hex_to_rgb( $hex );

		if ( empty( $rgb ) ) {
			return '';
		}

		$luminance = ( 0.299 * $rgb[0] + 0.587 * $rgb[1] + 0.114 * $rgb[2] ) / 255;

		return $luminance > 0.55 ? 'rgb(63,59,59)' : 'rgb(247,241,222)';
	}
}

if ( ! function_exists( 'nntm_hero_slider_quicklinks_style' ) ) {

	function nntm_hero_slider_quicklinks_style( string $bg_color, float $bg_opacity, string $text_color ): string {
		$bg_soft  = nntm_hero_slider_color_css( $bg_color, $bg_opacity / 100 );
		$bg_solid = nntm_hero_slider_color_css( $bg_color );
		$text     = nntm_hero_slider_color_css( $text_color );

		$vars = array();

		if ( '' !== $bg_soft ) {
			$vars['--nntm-hero-ql-nen'] = $bg_soft;
		}

		if ( '' !== $text ) {
			$vars['--nntm-hero-ql-chu']       = $text;
			$vars['--nntm-hero-ql-vien']      = $text;
			$vars['--nntm-hero-ql-nen-hover'] = $text;
		}

		if ( '' !== $bg_solid ) {
			$vars['--nntm-hero-ql-chu-hover'] = $bg_solid;
		} elseif ( '' !== $text ) {
			$vars['--nntm-hero-ql-chu-hover'] = nntm_hero_slider_contrast_color( $text_color );
		}

		if ( empty( $vars ) ) {
			return '';
		}

		$pairs = array();

		foreach ( $vars as $name => $value ) {
			$pairs[] = $name . ':' . $value;
		}

		return implode( ';', $pairs ) . ';';
	}
}

if ( ! function_exists( 'nntm_hero_slider_render_quicklinks' ) ) {
	 
	function nntm_hero_slider_render_quicklinks( int $parent_term_id, string $inline_style = '' ): string {
		if ( $parent_term_id <= 0 || ! taxonomy_exists( 'nntm_section' ) ) {
			return '';
		}

		$child_terms = get_terms(
			array(
				'taxonomy'   => 'nntm_section',
				'parent'     => $parent_term_id,
				'hide_empty' => false,
			)
		);

		if ( is_wp_error( $child_terms ) || empty( $child_terms ) ) {
			return '';
		}

		if ( function_exists( 'nntm_sort_terms_by_order' ) ) {
			$child_terms = nntm_sort_terms_by_order( $child_terms );
		}

		ob_start();
		?>
		<nav
			class="nntm-hero-slider__quicklinks"
			aria-label="<?php esc_attr_e( 'Liên kết nhanh', 'nntm' ); ?>"
			<?php if ( '' !== $inline_style ) : ?>
				style="<?php echo esc_attr( $inline_style ); ?>"
			<?php endif; ?>
		>
			<?php foreach ( $child_terms as $child_term ) : ?>
				<?php
				$term_link = get_term_link( $child_term );
				if ( is_wp_error( $term_link ) ) {
					continue;
				}
				?>
				<a class="nntm-hero-slider__quicklink" href="<?php echo esc_url( $term_link ); ?>">
					<?php echo esc_html( $child_term->name ); ?>
				</a>
			<?php endforeach; ?>
		</nav>
		<?php
		return trim( (string) ob_get_clean() );
	}
}

if ( ! function_exists( 'nntm_hero_slider_render_sidecard' ) ) {
	 
	function nntm_hero_slider_render_sidecard( ?WP_Post $article, string $cta_label ): string {
		if ( null === $article ) {
			return '';
		}

		$permalink = get_permalink( $article );
		$title     = get_the_title( $article );

		if ( '' === trim( $title ) || ! $permalink ) {
			return '';
		}

		 
		$excerpt = trim( wp_strip_all_tags( (string) $article->post_excerpt ) );

		ob_start();
		?>
		<aside class="nntm-hero-slider__sidecard">
			<?php
			/*
			 * Không kẹp 2 dòng: chữ to hoặc tên bài dài thì thẻ dãn cao ra, không
			 * cắt mất chữ.
			 *
			 * Class là `-subheading` chứ không phải `-heading`: bảng "Chữ trong
			 * khối" nhận vai trò qua mảnh class, nên tên cũ rơi chung vai trò
			 * "Tiêu đề" với chữ lớn bên trái — chỉnh một lần là đổi cả hai. Tên
			 * này đẩy nó sang vai trò "Tiêu đề phụ" để chỉnh riêng.
			 */
			?>
			<p class="nntm-hero-slider__sidecard-subheading">
				<a href="<?php echo esc_url( $permalink ); ?>"><?php echo esc_html( $title ); ?></a>
			</p>

			<?php if ( '' !== $excerpt ) : ?>
				<p class="nntm-hero-slider__sidecard-text"><?php echo esc_html( wp_trim_words( $excerpt, 16, '…' ) ); ?></p>
			<?php endif; ?>

			<?php if ( '' !== trim( $cta_label ) ) : ?>
				<a class="nntm-hero-slider__sidecard-cta" href="<?php echo esc_url( $permalink ); ?>">
					<?php echo esc_html( $cta_label ); ?>
				</a>
			<?php endif; ?>
		</aside>
		<?php
		return trim( (string) ob_get_clean() );
	}
}
