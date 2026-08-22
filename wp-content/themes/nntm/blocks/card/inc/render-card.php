<?php

defined( 'ABSPATH' ) || exit;

function nntm_card_allowed_variants(): array {
	return array( 'article', 'small', 'xs', 'dai-si', 'kim-cuong', 'article-hover', 'video', 'khoa-tu', 'books' );
}

function nntm_card_get_primary_term( int $post_id ): ?WP_Term {
	$priority = array( 'nntm_section', 'nntm_topic', 'nntm_series', 'category' );

	foreach ( $priority as $taxonomy ) {
		if ( ! taxonomy_exists( $taxonomy ) ) {
			continue;
		}

		$terms = get_the_terms( $post_id, $taxonomy );

		if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
			return $terms[0];
		}
	}

	return null;
}

function nntm_render_card_markup( int $post_id, string $variant, bool $show_date = true, bool $show_excerpt = true, bool $show_category = true, bool $show_cta = false, string $cta_label = 'Xem thêm' ): string {
	if ( ! in_array( $variant, nntm_card_allowed_variants(), true ) ) {
		$variant = 'article';
	}

	$post = $post_id > 0 ? get_post( $post_id ) : null;

	if ( ! $post instanceof WP_Post || 'publish' !== $post->post_status ) {
		return '<p class="nntm-card nntm-card--empty">' . esc_html__( 'Chưa chọn bài viết để hiển thị.', 'nntm' ) . '</p>';
	}

	$is_dai_si = ( 'dai-si' === $variant );
	 
	$is_kim_cuong = ( 'kim-cuong' === $variant );
	 
	$has_cta     = $is_dai_si || $is_kim_cuong || ( 'video' !== $variant ) || $show_cta;
	$has_excerpt = $show_excerpt && ! $is_dai_si && in_array( $variant, array( 'article', 'khoa-tu', 'books', 'kim-cuong' ), true );

	$permalink = get_permalink( $post );

	if ( 'books' === $variant ) {
		$duong_doc = nntm_doc_url( $post );

		if ( '' !== $duong_doc && nntm_an_pham_can_access( $post ) ) {
			$permalink = $duong_doc;
		}
	}

	$title     = get_the_title( $post );
	$thumbnail = get_the_post_thumbnail(
		$post,
		'medium_large',
		array(
			'class'   => 'nntm-card__img-el',
			'loading' => 'lazy',
			'alt'     => $title,
		)
	);

	$classes   = array( 'nntm-card', 'nntm-card--' . $variant );
	$class_attr = esc_attr( implode( ' ', $classes ) );

	ob_start();
	?>
	<a href="<?php echo esc_url( $permalink ); ?>" class="<?php echo esc_attr( $class_attr ); ?>">
		<span class="nntm-card__img">
			<?php
			if ( $thumbnail ) {
				echo wp_kses_post( $thumbnail );
			} else {
				echo '<span class="nntm-card__img-placeholder" aria-hidden="true"></span>';
			}
			?>
			<?php if ( 'video' === $variant ) : ?>
				<span class="nntm-card__play" aria-hidden="true"></span>
			<?php endif; ?>
		</span>
		<span class="nntm-card__body">
			<?php if ( $show_date && ! $is_dai_si ) : ?>
				<span class="nntm-card__date">
					<span class="nntm-card__date-icon" aria-hidden="true"></span>
					<?php
					echo esc_html(
						sprintf(
							 
							__( 'Cập nhật %s', 'nntm' ),
							get_the_modified_date( 'd. m. Y', $post )
						)
					);
					?>
				</span>
			<?php endif; ?>

			<?php
			if ( $show_category && ! $is_dai_si ) :
				$term = nntm_card_get_primary_term( $post->ID );
				if ( $term ) :
					?>
					<span class="nntm-card__cat"><?php echo esc_html( $term->name ); ?></span>
					<?php
				endif;
			endif;
			?>

			<span class="nntm-card__title"><?php echo esc_html( $title ); ?></span>

			<?php if ( $has_excerpt ) : ?>
				<span class="nntm-card__excerpt"><?php echo esc_html( wp_trim_words( get_the_excerpt( $post ), 24, '…' ) ); ?></span>
			<?php endif; ?>

			<?php if ( $has_cta ) : ?>
				<span class="nntm-card__cta"><?php echo esc_html( '' !== trim( $cta_label ) ? $cta_label : __( 'Xem thêm', 'nntm' ) ); ?></span>
			<?php endif; ?>
		</span>
	</a>
	<?php
	return trim( (string) ob_get_clean() );
}
