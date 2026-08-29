<?php

defined( 'ABSPATH' ) || exit;

require_once get_template_directory() . '/blocks/card/inc/render-card.php';
require_once __DIR__ . '/inc/render-card-list-youtube.php';
require_once __DIR__ . '/inc/render-card-list-marquee.php';

$allowed_post_types = array( 'nntm_article', 'nntm_publication', 'nntm_talk', 'nntm_retreat', 'nntm_abode', 'nntm_video', 'nntm_zen_track', 'nntm_chuyen_thay_toi', 'post' );
$post_type          = isset( $attributes['postType'] ) ? sanitize_key( (string) $attributes['postType'] ) : 'nntm_article';
if ( ! in_array( $post_type, $allowed_post_types, true ) ) {
	$post_type = 'nntm_article';
}

$variant = isset( $attributes['variant'] ) ? sanitize_key( (string) $attributes['variant'] ) : 'article';
if ( ! in_array( $variant, nntm_card_allowed_variants(), true ) ) {
	$variant = 'article';
}

$columns = isset( $attributes['columns'] ) ? absint( $attributes['columns'] ) : 3;
if ( ! in_array( $columns, array( 2, 3, 4 ), true ) ) {
	$columns = 3;
}

$posts_per_page = isset( $attributes['postsPerPage'] ) ? absint( $attributes['postsPerPage'] ) : 6;
$posts_per_page = max( 1, min( 24, $posts_per_page ) );

$order_by_choice  = isset( $attributes['orderBy'] ) ? sanitize_key( (string) $attributes['orderBy'] ) : 'newest';

 
 
$exclude_post_id  = isset( $attributes['excludePostId'] ) ? absint( $attributes['excludePostId'] ) : 0;
$show_paging      = ! empty( $attributes['showPaging'] );
$heading          = isset( $attributes['heading'] ) ? (string) $attributes['heading'] : '';
$subheading       = isset( $attributes['subheading'] ) ? (string) $attributes['subheading'] : '';
$has_subheading   = '' !== trim( wp_strip_all_tags( $subheading ) );
$show_view_all    = ! empty( $attributes['showViewAll'] );
$view_all_label   = isset( $attributes['viewAllLabel'] ) ? (string) $attributes['viewAllLabel'] : __( 'Xem tất cả', 'nntm' );

 

 

$layout = isset( $attributes['layout'] ) ? sanitize_key( (string) $attributes['layout'] ) : 'grid';
if ( ! in_array( $layout, array( 'grid', 'carousel', 'marquee' ), true ) ) {
	$layout = 'grid';
}
$is_carousel = ( 'carousel' === $layout );
$is_marquee  = ( 'marquee' === $layout );
$is_books_marquee = $is_marquee && ( 'books' === $variant );

 
 
$autoplay          = isset( $attributes['autoplay'] ) ? (bool) $attributes['autoplay'] : true;
$autoplay_interval = isset( $attributes['autoplayInterval'] ) ? absint( $attributes['autoplayInterval'] ) : 6;
$autoplay_interval = max( 2, min( 20, $autoplay_interval ? $autoplay_interval : 6 ) );

$taxonomy = isset( $attributes['taxonomy'] ) ? sanitize_key( (string) $attributes['taxonomy'] ) : '';
$term_id  = isset( $attributes['termId'] ) ? absint( $attributes['termId'] ) : 0;
$view_all_url = get_post_type_archive_link( $post_type );
if ( $taxonomy && $term_id ) {
	$term_link = get_term_link( $term_id, $taxonomy );
	if ( ! is_wp_error( $term_link ) ) {
		$view_all_url = $term_link;
	}
}

 
$view_all_url_override = isset( $attributes['viewAllUrl'] ) ? esc_url_raw( (string) $attributes['viewAllUrl'] ) : '';
if ( '' !== $view_all_url_override ) {
	$view_all_url = $view_all_url_override;
}

$video_source = isset( $attributes['videoSource'] ) ? sanitize_key( (string) $attributes['videoSource'] ) : 'posts';
if ( ! in_array( $video_source, array( 'posts', 'youtube' ), true ) ) {
	$video_source = 'posts';
}
$is_youtube_source  = ( 'youtube' === $video_source );
$youtube_items_raw  = isset( $attributes['youtubeItems'] ) ? (string) $attributes['youtubeItems'] : '';
$youtube_video_items = $is_youtube_source ? nntm_card_list_parse_youtube_items( $youtube_items_raw ) : array();

 

 
$paged = get_query_var( 'paged' );
if ( ! $paged ) {
	$paged = isset( $_GET['paged'] ) ? absint( wp_unslash( $_GET['paged'] ) ) : 1;  
}
$paged = max( 1, absint( $paged ) );

$is_carousel_like = ( $is_carousel || $is_marquee );
$query_args       = array(
	'post_type'              => $post_type,
	'post_status'            => 'publish',
	'posts_per_page'         => $posts_per_page,
	'paged'                  => $is_carousel_like ? 1 : $paged,
	'ignore_sticky_posts'    => true,
	'no_found_rows'       => $is_carousel_like ? true : ! $show_paging,  
	 
);

switch ( $order_by_choice ) {
	case 'oldest':
		$query_args['orderby'] = 'date';
		$query_args['order']   = 'ASC';
		break;

	case 'title':
		$query_args['orderby'] = 'title';
		$query_args['order']   = 'ASC';
		break;

	case 'manual':
		$manual_ids_raw = isset( $attributes['manualOrderIds'] ) ? (string) $attributes['manualOrderIds'] : '';
		$manual_ids     = array_values( array_filter( array_map( 'absint', explode( ',', $manual_ids_raw ) ) ) );

		 
		 
		if ( $exclude_post_id > 0 ) {
			$manual_ids = array_values( array_diff( $manual_ids, array( $exclude_post_id ) ) );
		}

		if ( ! empty( $manual_ids ) ) {
			 
			$query_args['post__in'] = array_slice( $manual_ids, 0, $posts_per_page );
			$query_args['orderby']  = 'post__in';
		} else {
			$query_args['orderby'] = 'date';
			$query_args['order']   = 'DESC';
		}
		break;

	case 'newest':
	default:
		$query_args['orderby'] = 'date';
		$query_args['order']   = 'DESC';
		break;
}

 

if ( $exclude_post_id > 0 && ! isset( $query_args['post__in'] ) ) {
	$query_args['post__not_in'] = array( $exclude_post_id );
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

$query = $is_youtube_source ? null : new WP_Query( $query_args );

$background = isset( $attributes['background'] ) ? sanitize_key( (string) $attributes['background'] ) : 'none';
if ( ! in_array( $background, array( 'none', 'kem', 'cam', 'toi', 'cham', 'vang' ), true ) ) {
	$background = 'none';
}

$heading_above = isset( $attributes['headingAbove'] ) ? (string) $attributes['headingAbove'] : '';
$caption_below = isset( $attributes['captionBelow'] ) ? (string) $attributes['captionBelow'] : '';

$show_date     = ! isset( $attributes['showDate'] ) || ! empty( $attributes['showDate'] );
$show_category = ! isset( $attributes['showCategory'] ) || ! empty( $attributes['showCategory'] );

$show_card_cta   = ! empty( $attributes['showCardCta'] );
$card_cta_label  = isset( $attributes['cardCtaLabel'] ) ? (string) $attributes['cardCtaLabel'] : __( 'Xem thêm', 'nntm' );
$enable_quiz     = ! empty( $attributes['enableQuiz'] );

$wrapper_attributes = get_block_wrapper_attributes(
	array(
		'class' => 'nntm-card-list'
			. ( 'none' !== $background ? ' nntm-card-list--nen-' . $background : '' )
			. ( $is_books_marquee ? ' nntm-card-list--books-marquee' : '' )
	)
);
?>
<section <?php echo $wrapper_attributes;  ?>>

	<?php if ( '' !== trim( $heading_above ) ) : ?>
		<p class="nntm-card-list__heading-above"><?php echo wp_kses_post( $heading_above ); ?></p>
	<?php endif; ?>

	<?php
	 
	?>
	<div class="nntm-card-list__band">
	<div class="nntm-container">
		<?php if ( '' !== $heading || ( ! $is_books_marquee && $show_view_all && $view_all_url ) ) : ?>
			<div class="nntm-card-list__header-row">
				<?php if ( '' !== $heading ) : ?>
					<h2 class="nntm-card-list__heading<?php echo $has_subheading ? ' nntm-card-list__heading--with-sub' : ''; ?>"><?php echo wp_kses_post( $heading ); ?></h2>
				<?php endif; ?>
				<?php if ( ! $is_books_marquee && $show_view_all && $view_all_url ) : ?>
					<a class="nntm-card-list__view-all" href="<?php echo esc_url( $view_all_url ); ?>"><?php echo esc_html( $view_all_label ); ?></a>
				<?php endif; ?>
			</div>
		<?php endif; ?>

		<?php if ( $has_subheading ) : ?>
			<p class="nntm-card-list__subheading"><?php echo wp_kses_post( $subheading ); ?></p>
		<?php endif; ?>

		<?php if ( $is_youtube_source ) : ?>

			<?php if ( ! empty( $youtube_video_items ) ) : ?>
				<?php
				 
				$framed_cards = ( 'cam' === $background );
				echo nntm_card_list_render_youtube_marquee( $youtube_video_items, $framed_cards );  
				?>
			<?php else : ?>
				<p class="nntm-card-list__empty"><?php esc_html_e( 'Chưa dán đường dẫn YouTube nào — vào trình soạn thảo, ô "Danh sách link YouTube" để thêm.', 'nntm' ); ?></p>
			<?php endif; ?>

		<?php elseif ( $query->have_posts() ) : ?>

			<?php if ( $is_marquee ) : ?>

				<?php
				 
				echo nntm_card_list_render_posts_marquee( wp_list_pluck( $query->posts, 'ID' ), $variant, $show_date, $show_category, $show_card_cta, $card_cta_label, $enable_quiz );
				?>

			<?php elseif ( $is_carousel ) : ?>

				<?php

				 

				 
				?>
				<div class="nntm-card-list__carousel" data-autoplay="<?php echo esc_attr( $autoplay ? 'true' : 'false' ); ?>" data-autoplay-interval="<?php echo esc_attr( (string) $autoplay_interval ); ?>">
					<button type="button" class="nntm-card-list__nav nntm-card-list__nav--prev" aria-label="<?php esc_attr_e( 'Xem thẻ trước', 'nntm' ); ?>">
						<span class="nntm-card-list__nav-icon" aria-hidden="true"></span>
					</button>

					<div class="nntm-card-list__track" tabindex="0" role="group" aria-label="<?php esc_attr_e( 'Danh sách cuộn ngang, dùng phím mũi tên trái/phải để cuộn', 'nntm' ); ?>">
						<?php foreach ( $query->posts as $queried_post ) : ?>
							<div class="nntm-card-list__track-item">
								<?php echo nntm_render_card_markup( $queried_post->ID, $variant, $show_date, true, $show_category, $show_card_cta, $card_cta_label, $enable_quiz );  ?>
							</div>
						<?php endforeach; ?>
					</div>

					<button type="button" class="nntm-card-list__nav nntm-card-list__nav--next" aria-label="<?php esc_attr_e( 'Xem thẻ tiếp theo', 'nntm' ); ?>">
						<span class="nntm-card-list__nav-icon" aria-hidden="true"></span>
					</button>
				</div>

			<?php else : ?>

				<div class="nntm-grid nntm-grid--<?php echo esc_attr( (string) $columns ); ?>">
					<?php foreach ( $query->posts as $queried_post ) : ?>
						<?php echo nntm_render_card_markup( $queried_post->ID, $variant, $show_date, true, $show_category, $show_card_cta, $card_cta_label, $enable_quiz );  ?>
					<?php endforeach; ?>
				</div>

				<?php if ( $show_paging && $query->max_num_pages > 1 ) : ?>
					<?php
					 
					$tong_trang = (int) $query->max_num_pages;

					$lien_ket_trang = static function ( int $so ): string {
						return get_pagenum_link( max( 1, $so ) );
					};
					?>
					<nav class="nntm-card-list__paging" aria-label="<?php esc_attr_e( 'Phân trang danh sách', 'nntm' ); ?>">
						<?php if ( $paged > 1 ) : ?>
							<a class="nntm-card-list__paging-btn nntm-card-list__paging-btn--prev" href="<?php echo esc_url( $lien_ket_trang( $paged - 1 ) ); ?>" rel="prev">
								<span class="nntm-sr-only"><?php esc_html_e( 'Trang trước', 'nntm' ); ?></span>
								<span aria-hidden="true">&larr;</span>
							</a>
						<?php endif; ?>

						<?php
						 
						$cua_so = 2;  
						$da_in  = 0;

						for ( $so = 1; $so <= $tong_trang; $so++ ) {
							$trong_cua_so = ( 1 === $so )
								|| ( $tong_trang === $so )
								|| ( abs( $so - $paged ) <= $cua_so );

							if ( ! $trong_cua_so ) {
								 
								if ( $da_in !== $so - 1 ) {
									continue;
								}
								echo '<span class="nntm-card-list__paging-luoc" aria-hidden="true">&hellip;</span>';
								continue;
							}

							$da_in = $so;

							if ( $so === $paged ) {
								printf(
									'<span class="nntm-card-list__paging-so nntm-card-list__paging-so--dang-xem" aria-current="page">%d</span>',
									(int) $so
								);
								continue;
							}

							printf(
								'<a class="nntm-card-list__paging-so" href="%1$s"><span class="nntm-sr-only">%2$s </span>%3$d</a>',
								esc_url( $lien_ket_trang( $so ) ),
								esc_html__( 'Trang', 'nntm' ),
								(int) $so
							);
						}
						?>

						<?php if ( $paged < $tong_trang ) : ?>
							<a class="nntm-card-list__paging-btn nntm-card-list__paging-btn--next" href="<?php echo esc_url( $lien_ket_trang( $paged + 1 ) ); ?>" rel="next">
								<span class="nntm-sr-only"><?php esc_html_e( 'Trang sau', 'nntm' ); ?></span>
								<span aria-hidden="true">&rarr;</span>
							</a>
						<?php endif; ?>
					</nav>
				<?php endif; ?>

			<?php endif; ?>

		<?php else : ?>
			<p class="nntm-card-list__empty"><?php esc_html_e( 'Chưa có nội dung nào phù hợp.', 'nntm' ); ?></p>
		<?php endif; ?>

		<?php if ( $is_books_marquee && $show_view_all && $view_all_url ) : ?>
			<div class="nntm-card-list__view-all-wrap">
				<a class="nntm-card-list__view-all" href="<?php echo esc_url( $view_all_url ); ?>"><?php echo esc_html( $view_all_label ); ?></a>
			</div>
		<?php endif; ?>

		<?php
		 
		$nntm_cl_spotify = isset( $attributes['spotifyUrl'] ) ? esc_url_raw( (string) $attributes['spotifyUrl'] ) : '';
		$nntm_cl_youtube = isset( $attributes['youtubeUrl'] ) ? esc_url_raw( (string) $attributes['youtubeUrl'] ) : '';
		$nntm_cl_apple   = isset( $attributes['appleMusicUrl'] ) ? esc_url_raw( (string) $attributes['appleMusicUrl'] ) : '';

		if ( '' !== $nntm_cl_spotify || '' !== $nntm_cl_youtube || '' !== $nntm_cl_apple ) :
			?>
			<div class="nntm-card-list__icons">
				<?php if ( '' !== $nntm_cl_spotify ) : ?>
					<a class="nntm-card-list__icon nntm-card-list__icon--spotify" href="<?php echo esc_url( $nntm_cl_spotify ); ?>" target="_blank" rel="noopener noreferrer">
						<span class="nntm-sr-only"><?php esc_html_e( 'Nghe trên Spotify (mở tab mới)', 'nntm' ); ?></span>
						<svg viewBox="0 0 50 50" width="50" height="50" aria-hidden="true" focusable="false">
							<circle cx="25" cy="25" r="25" fill="currentColor" />
							<path d="M13 19c8-2.4 16.5-1.8 23.5 2.2M15 26c6.6-2 13.6-1.5 19.4 1.8M17 33c5.3-1.6 10.9-1.2 15.5 1.4"
								stroke="var(--nntm-cl-icon-nen)" stroke-width="3.4" stroke-linecap="round" fill="none" />
						</svg>
					</a>
				<?php endif; ?>

				<?php if ( '' !== $nntm_cl_youtube ) : ?>
					<a class="nntm-card-list__icon nntm-card-list__icon--youtube" href="<?php echo esc_url( $nntm_cl_youtube ); ?>" target="_blank" rel="noopener noreferrer">
						<span class="nntm-sr-only"><?php esc_html_e( 'Xem trên YouTube (mở tab mới)', 'nntm' ); ?></span>
						<svg viewBox="0 0 60 47" width="60" height="47" aria-hidden="true" focusable="false">
							<rect x="0" y="0" width="60" height="47" rx="12" fill="currentColor" />
							<path d="M24 14 L42 23.5 L24 33 Z" fill="var(--nntm-cl-icon-nen)" />
						</svg>
					</a>
				<?php endif; ?>

				<?php

				?>
				<?php if ( '' !== $nntm_cl_apple ) : ?>
					<a class="nntm-card-list__icon nntm-card-list__icon--apple" href="<?php echo esc_url( $nntm_cl_apple ); ?>" target="_blank" rel="noopener noreferrer nofollow">
						<span class="nntm-sr-only"><?php esc_html_e( 'Nghe trên Apple Music (mở tab mới)', 'nntm' ); ?></span>
						<svg viewBox="0 0 50 50" width="50" height="50" aria-hidden="true" focusable="false">
							<rect x="0" y="0" width="50" height="50" rx="11" fill="currentColor" />
							<g fill="var(--nntm-cl-icon-nen)">
								<ellipse cx="18.4" cy="33.6" rx="4.2" ry="3.6" />
								<ellipse cx="31.6" cy="30.8" rx="4.2" ry="3.6" />
							</g>
							<path d="M22.6 33.6 V16.6 L35.8 13.9 V30.8 M22.6 22.4 L35.8 19.7"
								stroke="var(--nntm-cl-icon-nen)" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round" fill="none" />
						</svg>
					</a>
				<?php endif; ?>
			</div>
			<?php
		endif;
		?>

		<?php
		/*
		 * Phần mô tả nằm NGAY TRONG dải nền của chính section (trước đây nó là
		 * thẻ anh em đặt sau dải nền, lại position:absolute nên rơi hẳn xuống
		 * vùng nền của section kế tiếp).
		 */
		?>
		<?php if ( '' !== trim( $caption_below ) ) : ?>
			<p class="nntm-card-list__caption-below"><?php echo wp_kses_post( $caption_below ); ?></p>
		<?php endif; ?>
	</div>
	</div>
</section>
