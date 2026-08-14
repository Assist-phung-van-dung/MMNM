<?php
/**
 * Render động cho block nntm/card.
 *
 * WordPress tự require file này (khai báo qua "render" trong block.json)
 * mỗi khi block xuất hiện trên trang, với $attributes / $content / $block
 * sẵn có trong scope. Không lưu HTML vào nội dung bài — đổi thiết kế
 * variant sau này chỉ cần sửa render-card.php + style.css.
 *
 * @package NNTM
 * @var array    $attributes Thuộc tính của block.
 * @var string   $content    Nội dung InnerBlocks (không dùng ở block này).
 * @var WP_Block $block      Instance block hiện tại.
 */

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/inc/render-card.php';

$variant       = isset( $attributes['variant'] ) ? sanitize_key( (string) $attributes['variant'] ) : 'article';
$post_id       = isset( $attributes['postId'] ) ? absint( $attributes['postId'] ) : 0;
$show_date     = ! empty( $attributes['showDate'] );
$show_excerpt  = ! empty( $attributes['showExcerpt'] );
$show_category = ! empty( $attributes['showCategory'] );
$show_cta      = ! empty( $attributes['showCta'] );
$cta_label     = isset( $attributes['ctaLabel'] ) ? (string) $attributes['ctaLabel'] : __( 'Xem thêm', 'nntm' );

echo nntm_render_card_markup( $post_id, $variant, $show_date, $show_excerpt, $show_category, $show_cta, $cta_label ); // phpcs:ignore WordPress.Security.EscapeOutput -- markup đã được escape từng phần bên trong nntm_render_card_markup().
