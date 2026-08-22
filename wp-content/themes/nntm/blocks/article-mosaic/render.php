<?php

defined( 'ABSPATH' ) || exit;

 

 
require_once get_template_directory() . '/blocks/card/inc/render-card.php';

 

 
require_once __DIR__ . '/inc/render-article-mosaic.php';

$allowed_post_types = array( 'post', 'nntm_article', 'nntm_publication', 'nntm_talk', 'nntm_video' );
$post_type          = isset( $attributes['postType'] ) ? sanitize_key( (string) $attributes['postType'] ) : 'post';
if ( ! in_array( $post_type, $allowed_post_types, true ) ) {
	$post_type = 'post';
}

$lead_media = isset( $attributes['leadMedia'] ) ? sanitize_key( (string) $attributes['leadMedia'] ) : 'tall';
if ( ! in_array( $lead_media, array( 'tall', 'short' ), true ) ) {
	$lead_media = 'tall';
}

$secondary_layout = isset( $attributes['secondaryLayout'] ) ? sanitize_key( (string) $attributes['secondaryLayout'] ) : 'mosaic';
if ( ! in_array( $secondary_layout, array( 'mosaic', 'grid' ), true ) ) {
	$secondary_layout = 'mosaic';
}

$order_by_choice = isset( $attributes['orderBy'] ) ? sanitize_key( (string) $attributes['orderBy'] ) : 'newest';
if ( ! in_array( $order_by_choice, array( 'newest', 'oldest', 'title', 'manual' ), true ) ) {
	$order_by_choice = 'newest';
}

$show_category = ! isset( $attributes['showCategory'] ) || ! empty( $attributes['showCategory'] );
$show_excerpt  = ! isset( $attributes['showExcerpt'] ) || ! empty( $attributes['showExcerpt'] );
$show_date     = ! isset( $attributes['showDate'] ) || ! empty( $attributes['showDate'] );

$heading = isset( $attributes['heading'] ) ? (string) $attributes['heading'] : '';
$card_cta_label = isset( $attributes['cardCtaLabel'] )
	? sanitize_text_field( (string) $attributes['cardCtaLabel'] )
	: __( 'Xem thêm', 'nntm' );

$taxonomy = isset( $attributes['taxonomy'] ) ? sanitize_key( (string) $attributes['taxonomy'] ) : '';
$term_id  = isset( $attributes['termId'] ) ? absint( $attributes['termId'] ) : 0;

 

 
$query_args = array(
	'post_type'           => $post_type,
	'post_status'         => 'publish',
	 
	'posts_per_page'      => 'grid' === $secondary_layout ? 7 : 6,
	'ignore_sticky_posts' => true,
	'no_found_rows'       => true,
	 
);

switch ( $order_by_choice ) {
	case 'oldest':
		$query_args['orderby'] = array(
			'date' => 'ASC',
			'ID'   => 'ASC',
		);
		break;

	case 'title':
		$query_args['orderby'] = array(
			'title' => 'ASC',
			'ID'    => 'ASC',
		);
		break;

	case 'manual':
		 
		$manual_ids = array();
		if ( isset( $attributes['manualOrderIds'] ) ) {
			foreach ( preg_split( '/[^0-9]+/', (string) $attributes['manualOrderIds'] ) as $one_id ) {
				$one_id = absint( $one_id );
				if ( $one_id > 0 ) {
					$manual_ids[] = $one_id;
				}
			}
		}
		if ( ! empty( $manual_ids ) ) {
			$query_args['post__in'] = $manual_ids;
			$query_args['orderby']  = 'post__in';
			unset( $query_args['order'] );
		} else {
			 
			$query_args['orderby'] = array(
				'date' => 'DESC',
				'ID'   => 'DESC',
			);
		}
		break;

	case 'newest':
	default:
		$query_args['orderby'] = array(
			'date' => 'DESC',
			'ID'   => 'DESC',
		);
		break;
}

if ( '' !== $taxonomy && taxonomy_exists( $taxonomy ) && $term_id > 0 ) {
	$query_args['tax_query'] = array(  
		array(
			'taxonomy' => $taxonomy,
			'field'    => 'term_id',
			'terms'    => array( $term_id ),
		),
	);
}

$pinned_post_id = isset( $attributes['pinnedPostId'] ) ? absint( $attributes['pinnedPostId'] ) : 0;
$pinned_post    = null;
if ( $pinned_post_id > 0 && 'manual' !== $order_by_choice && function_exists( 'nntm_core_validate_pinned_post' ) ) {
	$pinned_post = nntm_core_validate_pinned_post( $pinned_post_id, $post_type );
}

if ( $pinned_post ) {
	$query_args['posts_per_page'] = max( 0, $query_args['posts_per_page'] - 1 );
	$query_args['post__not_in']   = array( $pinned_post->ID );
}

$query = new WP_Query( $query_args );

$mosaic_posts = $pinned_post ? array_merge( array( $pinned_post ), $query->posts ) : $query->posts;
$total_posts  = count( $mosaic_posts );

 
$lead_post = $total_posts > 0 ? $mosaic_posts[0] : null;

if ( 'grid' === $secondary_layout ) {
	 
	$medium_posts = array();
	$small_posts  = array();
	$grid_posts   = array_slice( $mosaic_posts, 1, 6 );
} else {
	$medium_posts = array_slice( $mosaic_posts, 1, 2 );
	$small_posts  = array_slice( $mosaic_posts, 3, 3 );
	$grid_posts   = array();
}

$has_secondary = ! empty( $medium_posts ) || ! empty( $small_posts ) || ! empty( $grid_posts );

 

 
 
$wrapper_attributes = get_block_wrapper_attributes(
	array(
		'class' => 'nntm-article-mosaic'
			. ' nntm-article-mosaic--lead-' . $lead_media
			. ' nntm-article-mosaic--phai-' . $secondary_layout,
	)
);
?>
<section <?php echo $wrapper_attributes;  ?>>
	<div class="nntm-article-mosaic__inner">
		<?php if ( '' !== $heading ) : ?>
			<h2 class="nntm-article-mosaic__heading"><?php echo wp_kses_post( $heading ); ?></h2>
		<?php endif; ?>

		<?php if ( null === $lead_post ) : ?>
			<p class="nntm-article-mosaic__empty"><?php esc_html_e( 'Chưa có bài viết nào phù hợp để hiển thị.', 'nntm' ); ?></p>
		<?php else : ?>
			<div class="nntm-article-mosaic__content">
				<div class="nntm-article-mosaic__list<?php echo $has_secondary ? '' : ' nntm-article-mosaic__list--solo'; ?>">
					<?php
					 
					$lead_permalink  = get_permalink( $lead_post );
					$lead_title      = get_the_title( $lead_post );
					$lead_img_class  = 'nntm-article-mosaic__lead-img nntm-article-mosaic__lead-img--' . $lead_media;
					?>
					<article class="nntm-article-mosaic__lead">
						<?php echo nntm_article_mosaic_render_thumb( $lead_post, $lead_img_class );  ?>
						<div class="nntm-article-mosaic__lead-body">
							<?php if ( $show_date ) : ?>
								<?php echo nntm_article_mosaic_render_date( $lead_post );  ?>
							<?php endif; ?>

							<div class="nntm-article-mosaic__lead-text">
								<?php
								if ( $show_category ) :
									$lead_term = nntm_card_get_primary_term( $lead_post->ID );
									if ( $lead_term ) :
										?>
										<span class="nntm-article-mosaic__cat nntm-article-mosaic__cat--lead"><?php echo esc_html( $lead_term->name ); ?></span>
										<?php
									endif;
								endif;
								?>

								<h3 class="nntm-article-mosaic__lead-title nntm-cat-2-dong">
									<a href="<?php echo esc_url( $lead_permalink ); ?>"><?php echo esc_html( $lead_title ); ?></a>
								</h3>

								<?php if ( 'grid' === $secondary_layout && $show_excerpt ) : ?>
									<p class="nntm-article-mosaic__lead-excerpt">
										<?php echo esc_html( wp_trim_words( get_the_excerpt( $lead_post ), 28, '…' ) ); ?>
									</p>
								<?php endif; ?>
							</div>
							<?php if ( '' !== $card_cta_label ) : ?>
								<a class="nntm-article-mosaic__card-cta" href="<?php echo esc_url( $lead_permalink ); ?>"><?php echo esc_html( $card_cta_label ); ?></a>
							<?php endif; ?>
						</div>
					</article>

					<?php if ( $has_secondary ) : ?>
						<div class="nntm-article-mosaic__secondary">
							<?php if ( ! empty( $grid_posts ) ) : ?>
								<div class="nntm-article-mosaic__grid">
									<?php foreach ( $grid_posts as $grid_post ) : ?>
										<?php echo nntm_article_mosaic_render_secondary_card( $grid_post, 'small', $show_category, $show_date, $card_cta_label );  ?>
									<?php endforeach; ?>
								</div>
							<?php endif; ?>

							<?php if ( ! empty( $medium_posts ) ) : ?>
								<div class="nntm-article-mosaic__medium-row">
									<?php foreach ( $medium_posts as $medium_post ) : ?>
										<?php echo nntm_article_mosaic_render_secondary_card( $medium_post, 'medium', $show_category, $show_date, $card_cta_label );  ?>
									<?php endforeach; ?>
								</div>
							<?php endif; ?>

							<?php if ( ! empty( $small_posts ) ) : ?>
								<div class="nntm-article-mosaic__small-row">
									<?php foreach ( $small_posts as $small_post ) : ?>
										<?php echo nntm_article_mosaic_render_secondary_card( $small_post, 'small', $show_category, $show_date, $card_cta_label );  ?>
									<?php endforeach; ?>
								</div>
							<?php endif; ?>
						</div>
					<?php endif; ?>
				</div>
			</div>
		<?php endif; ?>

		<?php
		 
		$nntm_am_xem_nhan = isset( $attributes['viewAllLabel'] ) && '' !== trim( (string) $attributes['viewAllLabel'] )
			? sanitize_text_field( (string) $attributes['viewAllLabel'] )
			: __( 'Xem Tất cả', 'nntm' );
		$nntm_am_xem_url  = isset( $attributes['viewAllUrl'] ) ? esc_url_raw( (string) $attributes['viewAllUrl'] ) : '';

		if ( '' === $nntm_am_xem_url && '' !== $taxonomy && $term_id > 0 ) {
			$nntm_am_term_url = get_term_link( $term_id, $taxonomy );
			if ( ! is_wp_error( $nntm_am_term_url ) ) {
				$nntm_am_xem_url = $nntm_am_term_url;
			}
		}

		if ( '' === $nntm_am_xem_url ) {
			$nntm_am_xem_url = home_url( '/' );
		}

		if ( '' !== trim( $nntm_am_xem_nhan ) ) :
			?>
			<div class="nntm-article-mosaic__viewall-wrap">
				<a class="nntm-article-mosaic__viewall" href="<?php echo esc_url( $nntm_am_xem_url ); ?>">
					<?php echo esc_html( $nntm_am_xem_nhan ); ?>
				</a>
			</div>
			<?php
		endif;
		?>
	</div>
</section>
