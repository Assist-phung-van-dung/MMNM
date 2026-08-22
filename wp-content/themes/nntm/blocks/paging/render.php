<?php

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

if ( $max_num_pages < 2 ) {
	return;
}

$paged = get_query_var( 'paged' );
if ( ! $paged ) {
	$paged = get_query_var( 'page' );  
}
if ( ! $paged ) {
	$paged = isset( $_GET['paged'] ) ? absint( wp_unslash( $_GET['paged'] ) ) : 1;  
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
<nav <?php echo $wrapper_attributes;  ?> aria-label="<?php esc_attr_e( 'Phân trang', 'nntm' ); ?>">
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
