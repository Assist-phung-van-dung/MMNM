<?php

defined( 'ABSPATH' ) || exit;

function nntm_card_list_render_marquee_nav(): string {
	ob_start();
	?>
	<button type="button" class="nntm-card-list__marquee-nav nntm-card-list__marquee-nav--prev" aria-label="<?php esc_attr_e( 'Xem thẻ trước', 'nntm' ); ?>">
		<span class="nntm-card-list__marquee-nav-icon" aria-hidden="true"></span>
	</button>
	<button type="button" class="nntm-card-list__marquee-nav nntm-card-list__marquee-nav--next" aria-label="<?php esc_attr_e( 'Xem thẻ tiếp theo', 'nntm' ); ?>">
		<span class="nntm-card-list__marquee-nav-icon" aria-hidden="true"></span>
	</button>
	<?php
	return trim( (string) ob_get_clean() );
}

function nntm_card_list_repeat_posts_for_width( array $post_ids, int $card_width, int $gap ): array {
	$count = count( $post_ids );
	if ( 0 === $count ) {
		return $post_ids;
	}

	$assumed_max_container_width = 2600;
	$min_strip_width             = 2 * $assumed_max_container_width;

	$target_item_count = (int) ceil( ( $min_strip_width + $gap ) / ( $card_width + $gap ) );
	$repeats           = max( 1, (int) ceil( $target_item_count / $count ) );

	$repeats = min( $repeats, 40 );

	$result = array();
	for ( $i = 0; $i < $repeats; $i++ ) {
		array_push( $result, ...$post_ids );
	}

	return $result;
}

function nntm_card_list_estimate_marquee_card_metrics( string $variant ): array {
	if ( 'books' === $variant ) {
		return array(
			'width' => 346,
			'gap'   => 14,
		);
	}

	return array(
		'width' => 320,
		'gap'   => 20,
	);
}

function nntm_card_list_wrap_marquee_item( string $card_html, bool $aria_hidden_dup ): string {
	if ( $aria_hidden_dup ) {

		$card_html = preg_replace( '/<a /', '<a tabindex="-1" ', $card_html, 1 );
	}

	$aria_attr = $aria_hidden_dup ? ' aria-hidden="true"' : '';

	return '<div class="nntm-card-list__marquee-item"' . $aria_attr . '>' . $card_html . '</div>';  
}

function nntm_card_list_render_posts_marquee( array $post_ids, string $variant, bool $show_date, bool $show_category, bool $show_card_cta, string $card_cta_label ): string {
	$post_ids = array_values( array_filter( array_map( 'absint', $post_ids ) ) );

	if ( empty( $post_ids ) ) {
		return '';
	}

	$unique_count = count( $post_ids );
	$metrics      = nntm_card_list_estimate_marquee_card_metrics( $variant );
	$filled_ids   = nntm_card_list_repeat_posts_for_width( $post_ids, $metrics['width'], $metrics['gap'] );

	 
	 
	$duration_seconds = max( 20, count( $filled_ids ) * 5 );

	ob_start();
	?>
	<div class="nntm-card-list__marquee">
		<div class="nntm-card-list__marquee-track" style="--nntm-marquee-duration: <?php echo esc_attr( (string) $duration_seconds ); ?>s;">
			<?php foreach ( $filled_ids as $i => $post_id ) : ?>
				<?php
				$card_html = nntm_render_card_markup( $post_id, $variant, $show_date, true, $show_category, $show_card_cta, $card_cta_label );
				echo nntm_card_list_wrap_marquee_item( $card_html, $i >= $unique_count );  
				?>
			<?php endforeach; ?>
			<?php foreach ( $filled_ids as $post_id ) : ?>
				<?php
				$card_html = nntm_render_card_markup( $post_id, $variant, $show_date, true, $show_category, $show_card_cta, $card_cta_label );
				echo nntm_card_list_wrap_marquee_item( $card_html, true );  
				?>
			<?php endforeach; ?>
		</div>

		<?php
		 
		if ( count( $filled_ids ) > 1 ) :
			echo nntm_card_list_render_marquee_nav();  
		endif;
		?>
	</div>
	<?php
	return trim( (string) ob_get_clean() );
}
