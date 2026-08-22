<?php

defined( 'ABSPATH' ) || exit;

function nntm_article_mosaic_render_thumb( WP_Post $mosaic_post, string $img_class ): string {
	$title     = get_the_title( $mosaic_post );
	$permalink = get_permalink( $mosaic_post );
	$thumbnail = get_the_post_thumbnail(
		$mosaic_post,
		'medium_large',
		array(
			'class'   => 'nntm-article-mosaic__img-el',
			'loading' => 'lazy',
			'alt'     => $title,
		)
	);

	ob_start();
	?>
	<a class="nntm-article-mosaic__media-link" href="<?php echo esc_url( $permalink ); ?>" aria-label="<?php echo esc_attr( $title ); ?>">
		<span class="<?php echo esc_attr( $img_class ); ?>">
			<?php
			if ( $thumbnail ) {
				echo wp_kses_post( $thumbnail );
			} else {
				echo '<span class="nntm-article-mosaic__img-placeholder" aria-hidden="true"></span>';
			}
			?>
		</span>
	</a>
	<?php
	return trim( (string) ob_get_clean() );
}

function nntm_article_mosaic_render_date( WP_Post $mosaic_post ): string {
	ob_start();
	?>
	<span class="nntm-article-mosaic__date">
		<span class="nntm-article-mosaic__date-icon" aria-hidden="true"></span>
		<?php
		echo esc_html(
			sprintf(
				 
				__( 'Cập nhật %s', 'nntm' ),
				get_the_modified_date( 'd. m. Y', $mosaic_post )
			)
		);
		?>
	</span>
	<?php
	return trim( (string) ob_get_clean() );
}

function nntm_article_mosaic_render_secondary_card( WP_Post $mosaic_post, string $variant, bool $show_category, bool $show_date, string $cta_label ): string {
	$permalink = get_permalink( $mosaic_post );
	$title     = get_the_title( $mosaic_post );
	$img_class = 'nntm-article-mosaic__' . $variant . '-img';

	ob_start();
	?>
	<article class="nntm-article-mosaic__<?php echo esc_attr( $variant ); ?>-card">
		<?php echo nntm_article_mosaic_render_thumb( $mosaic_post, $img_class );  ?>
		<div class="nntm-article-mosaic__<?php echo esc_attr( $variant ); ?>-body">
			<?php if ( $show_date ) : ?>
				<?php echo nntm_article_mosaic_render_date( $mosaic_post );  ?>
			<?php endif; ?>

			<?php
			if ( $show_category ) :
				$term = nntm_card_get_primary_term( $mosaic_post->ID );
				if ( $term ) :
					?>
					<span class="nntm-article-mosaic__cat nntm-article-mosaic__cat--<?php echo esc_attr( $variant ); ?>"><?php echo esc_html( $term->name ); ?></span>
					<?php
				endif;
			endif;
			?>

			<h3 class="nntm-article-mosaic__<?php echo esc_attr( $variant ); ?>-title nntm-cat-2-dong">
				<a href="<?php echo esc_url( $permalink ); ?>"><?php echo esc_html( $title ); ?></a>
			</h3>
			<?php if ( '' !== $cta_label ) : ?>
				<a class="nntm-article-mosaic__card-cta" href="<?php echo esc_url( $permalink ); ?>"><?php echo esc_html( $cta_label ); ?></a>
			<?php endif; ?>
		</div>
	</article>
	<?php
	return trim( (string) ob_get_clean() );
}
