<?php

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/inc/render-card.php';

$variant       = isset( $attributes['variant'] ) ? sanitize_key( (string) $attributes['variant'] ) : 'article';
$post_id       = isset( $attributes['postId'] ) ? absint( $attributes['postId'] ) : 0;
$show_date     = ! empty( $attributes['showDate'] );
$show_excerpt  = ! empty( $attributes['showExcerpt'] );
$show_category = ! empty( $attributes['showCategory'] );
$show_cta      = ! empty( $attributes['showCta'] );
$cta_label     = isset( $attributes['ctaLabel'] ) ? (string) $attributes['ctaLabel'] : __( 'Xem thêm', 'nntm' );

echo nntm_render_card_markup( $post_id, $variant, $show_date, $show_excerpt, $show_category, $show_cta, $cta_label );  
