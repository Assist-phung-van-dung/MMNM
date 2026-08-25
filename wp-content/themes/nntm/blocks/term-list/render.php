<?php

defined( 'ABSPATH' ) || exit;

$nntm_term_list_taxonomy = 'nntm_section';

$heading = isset( $attributes['heading'] ) ? (string) $attributes['heading'] : '';

$parent_term_id = isset( $attributes['parentTermId'] ) ? absint( $attributes['parentTermId'] ) : 0;

$show_description = ! isset( $attributes['showDescription'] ) || ! empty( $attributes['showDescription'] );

$cta_label = isset( $attributes['ctaLabel'] ) ? (string) $attributes['ctaLabel'] : '';
if ( '' === trim( wp_strip_all_tags( $cta_label ) ) ) {
	$cta_label = __( 'Xem thêm', 'nntm' );
}

$max_items = isset( $attributes['maxItems'] ) ? absint( $attributes['maxItems'] ) : 8;
$max_items = max( 1, min( 20, $max_items ) );  
$layout = isset( $attributes['layout'] ) && 'phap-toa' === $attributes['layout'] ? 'phap-toa' : 'overlay';
$autoplay = ! isset( $attributes['autoplay'] ) || ! empty( $attributes['autoplay'] );
$interval = max( 2, min( 20, absint( $attributes['interval'] ?? 5 ) ) );
$loop_delay = max( 0, min( 60, absint( $attributes['loopDelay'] ?? 0 ) ) );

$wrapper_attributes = get_block_wrapper_attributes(
	array(
		'class'         => 'nntm-term-list nntm-term-list--' . $layout,
		'data-autoplay' => $autoplay ? '1' : '0',
		'data-interval' => (string) $interval,
		'data-loop-delay' => (string) $loop_delay,
	)
);

$parent_term = null;
if ( $parent_term_id > 0 ) {
	$maybe_term = get_term( $parent_term_id, $nntm_term_list_taxonomy );
	if ( $maybe_term instanceof WP_Term ) {
		$parent_term = $maybe_term;
	}
}

$child_terms = array();
if ( $parent_term ) {
	$maybe_children = get_terms(
		array(
			'taxonomy'   => $nntm_term_list_taxonomy,
			'parent'     => $parent_term->term_id,
			'hide_empty' => false,
			'number'     => $max_items,
			'orderby'    => 'name',
			'order'      => 'ASC',
		)
	);

	if ( ! is_wp_error( $maybe_children ) ) {
		$child_terms = $maybe_children;

		if ( function_exists( 'nntm_sort_terms_by_order' ) ) {
			$child_terms = nntm_sort_terms_by_order( $child_terms );
		}
	}
}
?>
<section <?php echo $wrapper_attributes;  ?>>
	<div class="nntm-container">
		<?php if ( '' !== trim( wp_strip_all_tags( $heading ) ) ) : ?>
			<h2 class="nntm-term-list__heading"><?php echo wp_kses_post( $heading ); ?></h2>
		<?php endif; ?>

		<?php if ( ! $parent_term ) : ?>

			<p class="nntm-term-list__empty">
				<?php esc_html_e( 'Chưa chọn phân mục cha cho khối này. Vào bảng điều khiển bên phải để chọn một phân mục.', 'nntm' ); ?>
			</p>

		<?php elseif ( empty( $child_terms ) ) : ?>

			<p class="nntm-term-list__empty">
				<?php
				printf(
					 
					esc_html__( 'Phân mục "%s" chưa có phân mục con nào.', 'nntm' ),
					esc_html( $parent_term->name )
				);
				?>
			</p>

		<?php else : ?>

			<div class="nntm-term-list__carousel">
				<?php if ( 'phap-toa' === $layout ) : ?><button type="button" class="nntm-term-list__arrow nntm-term-list__arrow--prev" data-term-prev aria-label="Phần mục trước">←</button><?php endif; ?>
			<div class="nntm-term-list__track" data-term-track>
				<?php
				foreach ( $child_terms as $child_term ) :
					$term_link = get_term_link( $child_term );
					if ( is_wp_error( $term_link ) ) {
						continue;
					}

					$image_id  = absint( get_term_meta( $child_term->term_id, '_nntm_term_image', true ) );
					$thumbnail = $image_id > 0 ? wp_get_attachment_image(
						$image_id,
						'medium_large',
						false,
						array(
							'class'   => 'nntm-term-card__img-el',
							'loading' => 'lazy',
							'alt'     => $child_term->name,
						)
					) : '';

					$description = $show_description ? term_description( $child_term->term_id, $nntm_term_list_taxonomy ) : '';
					?>
					<a href="<?php echo esc_url( $term_link ); ?>" class="nntm-term-card">
						<span class="nntm-term-card__img">
							<?php
							if ( $thumbnail ) {
								echo wp_kses_post( $thumbnail );
							} else {
								echo '<span class="nntm-term-card__img-placeholder" aria-hidden="true"></span>';
							}
							?>
						</span>
						<span class="nntm-term-card__overlay">
							<span class="nntm-term-card__content">
								<span class="nntm-term-card__name"><?php echo esc_html( $child_term->name ); ?></span>
								<span class="nntm-term-card__meta">
									<?php if ( '' !== trim( wp_strip_all_tags( $description ) ) ) : ?>
										<span class="nntm-term-card__desc"><?php echo wp_kses_post( $description ); ?></span>
									<?php endif; ?>
									<?php
									 
									?>
									<span class="nntm-term-card__cta"><?php echo esc_html( $cta_label ); ?></span>
								</span>
							</span>
						</span>
					</a>
					<?php
				endforeach;
				?>
			</div>
				<?php if ( 'phap-toa' === $layout ) : ?><button type="button" class="nntm-term-list__arrow nntm-term-list__arrow--next" data-term-next aria-label="Phần mục tiếp theo">→</button><?php endif; ?>
			</div>

		<?php endif; ?>
	</div>
</section>
