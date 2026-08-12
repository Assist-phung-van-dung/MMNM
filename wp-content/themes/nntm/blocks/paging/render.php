<?php
/**
 * Render động cho block nntm/paging.
 *
 * Đây là bản BACK/NEXT dùng độc lập, dựa trên truy vấn CHÍNH của trang
 * (global $wp_query — giống hệt cách nntm/card-list tự tính phân trang
 * bên trong nó ở blocks/card-list/render.php), để dùng ở những nơi không
 * chèn nntm/card-list nhưng vẫn cần điều hướng trang danh sách (ví dụ
 * template kết quả tìm kiếm, trang lưu trữ dùng vòng lặp mặc định).
 * KHÔNG đụng đến blocks/card-list/ — phân trang trong đó vẫn giữ nguyên,
 * độc lập với block này.
 *
 * @package NNTM
 * @var array    $attributes Thuộc tính của block.
 * @var string   $content    Nội dung InnerBlocks (không dùng ở block này).
 * @var WP_Block $block      Instance block hiện tại.
 */

defined( 'ABSPATH' ) || exit;

global $wp_query;

$allowed_alignments = array( 'left', 'center', 'right' );
$alignment           = isset( $attributes['alignment'] ) ? sanitize_key( (string) $attributes['alignment'] ) : 'center';
if ( ! in_array( $alignment, $allowed_alignments, true ) ) {
	$alignment = 'center';
}

$label_prev = isset( $attributes['labelPrev'] ) ? (string) $attributes['labelPrev'] : '';
$label_prev = ( '' !== trim( $label_prev ) ) ? $label_prev : __( 'BACK', 'nntm' );

$label_next = isset( $attributes['labelNext'] ) ? (string) $attributes['labelNext'] : '';
$label_next = ( '' !== trim( $label_next ) ) ? $label_next : __( 'NEXT', 'nntm' );

$max_num_pages = ( $wp_query instanceof WP_Query ) ? (int) $wp_query->max_num_pages : 1;

// Không có trang thứ hai trong truy vấn chính: không có gì để chuyển, không in ra gì cả.
if ( $max_num_pages < 2 ) {
	return;
}

$paged = get_query_var( 'paged' );
if ( ! $paged ) {
	$paged = get_query_var( 'page' ); // trang tinh (khong phai vong lap bai viet) dung query var "page".
}
if ( ! $paged ) {
	$paged = isset( $_GET['paged'] ) ? absint( wp_unslash( $_GET['paged'] ) ) : 1; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- chi doc de phan trang, khong ghi du lieu.
}
$paged = max( 1, absint( $paged ) );

$prev_url = null;
$next_url = null;

if ( $paged > 1 ) {
	$prev_url = ( 1 === $paged - 1 ) ? remove_query_arg( 'paged' ) : add_query_arg( 'paged', $paged - 1 );
}

if ( $paged < $max_num_pages ) {
	$next_url = add_query_arg( 'paged', $paged + 1 );
}

if ( ! $prev_url && ! $next_url ) {
	return;
}

$wrapper_attributes = get_block_wrapper_attributes( array( 'class' => 'nntm-paging nntm-paging--' . $alignment ) );
?>
<nav <?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput -- get_block_wrapper_attributes() da tu esc_attr() tung thuoc tinh. ?> aria-label="<?php esc_attr_e( 'Phân trang', 'nntm' ); ?>">
	<?php if ( $prev_url ) : ?>
		<a class="nntm-paging__btn nntm-paging__btn--prev" href="<?php echo esc_url( $prev_url ); ?>">
			<span class="nntm-paging__icon" aria-hidden="true"></span>
			<span class="nntm-paging__label"><?php echo esc_html( $label_prev ); ?></span>
		</a>
	<?php else : ?>
		<span class="nntm-paging__btn nntm-paging__btn--prev nntm-paging__btn--disabled" aria-disabled="true">
			<span class="nntm-paging__icon" aria-hidden="true"></span>
			<span class="nntm-paging__label"><?php echo esc_html( $label_prev ); ?></span>
		</span>
	<?php endif; ?>

	<?php if ( $next_url ) : ?>
		<a class="nntm-paging__btn nntm-paging__btn--next" href="<?php echo esc_url( $next_url ); ?>">
			<span class="nntm-paging__label"><?php echo esc_html( $label_next ); ?></span>
			<span class="nntm-paging__icon" aria-hidden="true"></span>
		</a>
	<?php else : ?>
		<span class="nntm-paging__btn nntm-paging__btn--next nntm-paging__btn--disabled" aria-disabled="true">
			<span class="nntm-paging__label"><?php echo esc_html( $label_next ); ?></span>
			<span class="nntm-paging__icon" aria-hidden="true"></span>
		</span>
	<?php endif; ?>
</nav>
