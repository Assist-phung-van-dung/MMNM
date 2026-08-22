<?php

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/inc/render-hero-slider.php';

$nntm_hs_raw_slides = ( isset( $attributes['slides'] ) && is_array( $attributes['slides'] ) ) ? $attributes['slides'] : array();

$nntm_hs_slides = array();
foreach ( $nntm_hs_raw_slides as $nntm_hs_raw_slide ) {
	if ( ! is_array( $nntm_hs_raw_slide ) ) {
		continue;
	}
	$nntm_hs_clean_slide = nntm_hero_slider_clean_slide( $nntm_hs_raw_slide );
	if ( null !== $nntm_hs_clean_slide ) {
		$nntm_hs_slides[] = $nntm_hs_clean_slide;
	}
}

$nntm_hs_slide_count = count( $nntm_hs_slides );

if ( 0 === $nntm_hs_slide_count ) {
	 
	if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
		$nntm_hs_wrapper_attributes = get_block_wrapper_attributes( array( 'class' => 'nntm-hero-slider nntm-hero-slider--empty' ) );
		?>
		<div <?php echo $nntm_hs_wrapper_attributes;  ?>>
			<p class="nntm-hero-slider__empty-notice">
				<?php esc_html_e( 'Chưa có tấm băng chuyền nào. Mở bảng điều khiển bên phải để thêm ảnh, tiêu đề và mô tả cho ít nhất một tấm.', 'nntm' ); ?>
			</p>
		</div>
		<?php
	}
	return;
}

$nntm_hs_has_multiple = $nntm_hs_slide_count > 1;

$nntm_hs_autoplay = $nntm_hs_has_multiple && ( ! isset( $attributes['autoplay'] ) || ! empty( $attributes['autoplay'] ) );

 
 
$nntm_hs_interval = isset( $attributes['interval'] ) ? (float) $attributes['interval'] : 6;
$nntm_hs_interval = max( 2, min( 30, $nntm_hs_interval ) );

 

$nntm_hs_show_live_region = $nntm_hs_has_multiple && ! $nntm_hs_autoplay;

 
 
$nntm_hs_show_nav = $nntm_hs_has_multiple && ( ! isset( $attributes['arrowsEnabled'] ) || ! empty( $attributes['arrowsEnabled'] ) );

$nntm_hs_quicklinks_parent_id = isset( $attributes['quickLinksParentTermId'] ) ? absint( $attributes['quickLinksParentTermId'] ) : 0;
$nntm_hs_quicklinks_html      = nntm_hero_slider_render_quicklinks( $nntm_hs_quicklinks_parent_id );

$nntm_hs_sidecard_enabled  = ! isset( $attributes['sideCardEnabled'] ) || ! empty( $attributes['sideCardEnabled'] );
$nntm_hs_sidecard_article  = null;

if ( $nntm_hs_sidecard_enabled && function_exists( 'nntm_core_get_latest_posts' ) ) {
	$nntm_hs_sidecard_posts = nntm_core_get_latest_posts(
		array(
			'post_type' => isset( $attributes['sideCardPostType'] ) ? sanitize_key( (string) $attributes['sideCardPostType'] ) : 'nntm_article',
			'taxonomy'  => isset( $attributes['sideCardTaxonomy'] ) ? sanitize_key( (string) $attributes['sideCardTaxonomy'] ) : 'nntm_section',
			'term_id'   => isset( $attributes['sideCardTermId'] ) ? absint( $attributes['sideCardTermId'] ) : 0,
			'number'    => 1,
		)
	);
	$nntm_hs_sidecard_article = ! empty( $nntm_hs_sidecard_posts ) ? $nntm_hs_sidecard_posts[0] : null;
}

$nntm_hs_sidecard_cta_label = isset( $attributes['sideCardCtaLabel'] ) && '' !== trim( (string) $attributes['sideCardCtaLabel'] )
	? sanitize_text_field( (string) $attributes['sideCardCtaLabel'] )
	: __( 'Xem thêm', 'nntm' );

$nntm_hs_sidecard_html = nntm_hero_slider_render_sidecard( $nntm_hs_sidecard_article, $nntm_hs_sidecard_cta_label );

$nntm_hs_wrapper_attributes = get_block_wrapper_attributes(
	array(
		'class'               => 'nntm-hero-slider',
		'data-nntm-autoplay' => $nntm_hs_autoplay ? '1' : '0',
		'data-nntm-interval' => (string) $nntm_hs_interval,
	)
);
?>
<section <?php echo $nntm_hs_wrapper_attributes;  ?>>
	<div
		class="nntm-hero-slider__stage"
		<?php if ( $nntm_hs_has_multiple ) : ?>
			aria-roledescription="carousel"
			aria-label="<?php esc_attr_e( 'Băng chuyền đầu trang chủ', 'nntm' ); ?>"
		<?php endif; ?>
	>
		<div class="nntm-hero-slider__track">
			<?php foreach ( $nntm_hs_slides as $nntm_hs_index => $nntm_hs_slide ) : ?>
				<?php echo nntm_hero_slider_render_slide( $nntm_hs_slide, $nntm_hs_index, $nntm_hs_slide_count, $nntm_hs_has_multiple );  ?>
			<?php endforeach; ?>
		</div>

		<?php if ( $nntm_hs_show_nav ) : ?>
			<?php echo nntm_hero_slider_render_nav();  ?>
		<?php endif; ?>

		<?php if ( '' !== $nntm_hs_sidecard_html ) : ?>
			<?php echo $nntm_hs_sidecard_html;  ?>
		<?php endif; ?>

		<?php if ( '' !== $nntm_hs_quicklinks_html ) : ?>
			<?php echo $nntm_hs_quicklinks_html;  ?>
		<?php endif; ?>

		<?php if ( $nntm_hs_has_multiple ) : ?>
			<?php echo nntm_hero_slider_render_dots( $nntm_hs_slide_count );  ?>
		<?php endif; ?>

		<?php if ( $nntm_hs_show_live_region ) : ?>
			<p class="nntm-hero-slider__status nntm-sr-only" aria-live="polite" data-nntm-hero-status>
				<?php echo esc_html( nntm_hero_slider_status_text( 1, $nntm_hs_slide_count ) ); ?>
			</p>
		<?php endif; ?>
	</div>
</section>
