<?php

defined( 'ABSPATH' ) || exit;

$nntm_r1_la_so_bai = (int) apply_filters( 'nntm_r1_list_article_so_bai', 6 );

$nntm_r1_la_cha_slug = (string) apply_filters( 'nntm_r1_list_article_chuyen_muc_cha', 'phap-toa' );
$nntm_r1_la_cha      = get_term_by( 'slug', $nntm_r1_la_cha_slug, 'nntm_section' );

$nntm_r1_la_tabs = array();
if ( $nntm_r1_la_cha instanceof WP_Term ) {
	$nntm_r1_la_con = get_terms(
		array(
			'taxonomy'   => 'nntm_section',
			'parent'     => $nntm_r1_la_cha->term_id,
			'hide_empty' => false,
		)
	);
	if ( ! is_wp_error( $nntm_r1_la_con ) ) {
		 
		$nntm_r1_la_tabs = function_exists( 'nntm_sort_terms_by_order' )
			? nntm_sort_terms_by_order( $nntm_r1_la_con )
			: $nntm_r1_la_con;
	}
}

$nntm_r1_la_dang_loc = isset( $_GET['muc'] ) ? sanitize_title( wp_unslash( $_GET['muc'] ) ) : '';  

$nntm_r1_la_trang = max( 1, (int) get_query_var( 'paged' ) ?: ( isset( $_GET['trang'] ) ? absint( wp_unslash( $_GET['trang'] ) ) : 1 ) );  

$nntm_r1_la_args = array(
	'post_type'           => 'nntm_article',
	'post_status'         => 'publish',
	'posts_per_page'      => $nntm_r1_la_so_bai,
	'paged'               => $nntm_r1_la_trang,
	'ignore_sticky_posts' => true,
	'no_found_rows'       => false,
);

if ( '' !== $nntm_r1_la_dang_loc ) {
	$nntm_r1_la_args['tax_query'] = array(  
		array(
			'taxonomy' => 'nntm_section',
			'field'    => 'slug',
			'terms'    => $nntm_r1_la_dang_loc,
		),
	);
} elseif ( $nntm_r1_la_cha instanceof WP_Term ) {
	 
	$nntm_r1_la_args['tax_query'] = array(  
		array(
			'taxonomy'         => 'nntm_section',
			'field'            => 'term_id',
			'terms'            => $nntm_r1_la_cha->term_id,
			'include_children' => true,
		),
	);
}

$nntm_r1_la_query = new WP_Query( $nntm_r1_la_args );

$nntm_r1_la_base = get_permalink();

$nntm_r1_la_url = static function ( string $base, string $muc, int $trang ): string {
	$args = array();
	if ( '' !== $muc ) {
		$args['muc'] = $muc;
	}
	if ( $trang > 1 ) {
		$args['trang'] = $trang;
	}
	return empty( $args ) ? $base : add_query_arg( $args, $base );
};
?>
<section class="nntm-r1-bai-viet">
	<div class="nntm-r1-bai-viet__inner">

		<h2 class="nntm-r1-bai-viet__tieu-de">
			<?php
			echo esc_html(
				$nntm_r1_la_cha instanceof WP_Term
					 
					? sprintf( __( 'Bài viết %s mới nhất', 'nntm' ), $nntm_r1_la_cha->name )
					: __( 'Bài viết mới nhất', 'nntm' )
			);
			?>
		</h2>

		<?php if ( ! empty( $nntm_r1_la_tabs ) ) : ?>
			<nav class="nntm-r1-bai-viet__tabs" aria-label="<?php esc_attr_e( 'Lọc bài viết theo chuyên mục', 'nntm' ); ?>">
				<a
					class="nntm-r1-bai-viet__tab<?php echo '' === $nntm_r1_la_dang_loc ? ' is-active' : ''; ?>"
					href="<?php echo esc_url( $nntm_r1_la_url( $nntm_r1_la_base, '', 1 ) ); ?>"
					<?php echo '' === $nntm_r1_la_dang_loc ? 'aria-current="true"' : '';  ?>
				><?php esc_html_e( 'Tất cả', 'nntm' ); ?></a>

				<?php foreach ( $nntm_r1_la_tabs as $nntm_r1_la_tab ) : ?>
					<?php $nntm_r1_la_tab_active = ( $nntm_r1_la_dang_loc === $nntm_r1_la_tab->slug ); ?>
					<a
						class="nntm-r1-bai-viet__tab<?php echo $nntm_r1_la_tab_active ? ' is-active' : ''; ?>"
						href="<?php echo esc_url( $nntm_r1_la_url( $nntm_r1_la_base, $nntm_r1_la_tab->slug, 1 ) ); ?>"
						<?php echo $nntm_r1_la_tab_active ? 'aria-current="true"' : '';  ?>
					><?php echo esc_html( $nntm_r1_la_tab->name ); ?></a>
				<?php endforeach; ?>
			</nav>
		<?php endif; ?>

		<?php if ( $nntm_r1_la_query->have_posts() ) : ?>
			<ul class="nntm-r1-bai-viet__list">
				<?php
				while ( $nntm_r1_la_query->have_posts() ) :
					$nntm_r1_la_query->the_post();

					$nntm_r1_la_terms = get_the_terms( get_the_ID(), 'nntm_section' );
					$nntm_r1_la_chip  = null;
					if ( is_array( $nntm_r1_la_terms ) ) {
						foreach ( $nntm_r1_la_terms as $nntm_r1_la_t ) {
							if ( $nntm_r1_la_t->parent > 0 ) {
								$nntm_r1_la_chip = $nntm_r1_la_t;
								break;
							}
							if ( null === $nntm_r1_la_chip ) {
								$nntm_r1_la_chip = $nntm_r1_la_t;
							}
						}
					}
					?>
					<li class="nntm-r1-bai-viet__the">
						<a class="nntm-r1-bai-viet__the-lien-ket" href="<?php the_permalink(); ?>">
							<span class="nntm-r1-bai-viet__the-anh">
								<?php
								if ( has_post_thumbnail() ) {
									the_post_thumbnail(
										'medium_large',
										array(
											'class'    => 'nntm-r1-bai-viet__anh',
											'alt'      => '',
											'loading'  => 'lazy',
											'decoding' => 'async',
										)
									);
								}
								?>
							</span>

							<span class="nntm-r1-bai-viet__the-noi">
								<?php if ( $nntm_r1_la_chip instanceof WP_Term ) : ?>
									<span class="nntm-r1-bai-viet__chip"><?php echo esc_html( $nntm_r1_la_chip->name ); ?></span>
								<?php endif; ?>

								<span class="nntm-r1-bai-viet__the-tieu-de"><?php the_title(); ?></span>
							</span>

							<span class="nntm-r1-bai-viet__ngay">
								<svg viewBox="0 0 12 12" aria-hidden="true" focusable="false">
									<circle cx="6" cy="6" r="5.2" fill="none" stroke="currentColor" stroke-width="1.4"/>
									<path d="M6 3v3.3l2.1 1.2" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/>
								</svg>
								<?php
								printf(
									 
									esc_html__( 'Cập nhật %s', 'nntm' ),
									esc_html( get_the_modified_date( 'd. m. Y' ) )
								);
								?>
							</span>
						</a>
					</li>
					<?php
				endwhile;
				wp_reset_postdata();
				?>
			</ul>

			<?php if ( $nntm_r1_la_query->max_num_pages > 1 ) : ?>
				<nav class="nntm-r1-paging" aria-label="<?php esc_attr_e( 'Phân trang bài viết', 'nntm' ); ?>">
					<?php $nntm_r1_la_co_truoc = $nntm_r1_la_trang > 1; ?>
					<?php if ( $nntm_r1_la_co_truoc ) : ?>
						<a class="nntm-r1-paging__nut" href="<?php echo esc_url( $nntm_r1_la_url( $nntm_r1_la_base, $nntm_r1_la_dang_loc, $nntm_r1_la_trang - 1 ) ); ?>">
							<span class="nntm-sr-only"><?php esc_html_e( 'Trang trước', 'nntm' ); ?></span>
							<svg viewBox="0 0 20 16" aria-hidden="true" focusable="false"><path d="M8 1 1 8l7 7M1 8h18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
						</a>
					<?php else : ?>
						<span class="nntm-r1-paging__nut is-tat" aria-hidden="true">
							<svg viewBox="0 0 20 16" focusable="false"><path d="M8 1 1 8l7 7M1 8h18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
						</span>
					<?php endif; ?>

					<?php for ( $nntm_r1_la_p = 1; $nntm_r1_la_p <= $nntm_r1_la_query->max_num_pages; $nntm_r1_la_p++ ) : ?>
						<?php if ( $nntm_r1_la_p === $nntm_r1_la_trang ) : ?>
							<span class="nntm-r1-paging__so is-active" aria-current="page"><?php echo esc_html( number_format_i18n( $nntm_r1_la_p ) ); ?></span>
						<?php else : ?>
							<a class="nntm-r1-paging__so" href="<?php echo esc_url( $nntm_r1_la_url( $nntm_r1_la_base, $nntm_r1_la_dang_loc, $nntm_r1_la_p ) ); ?>">
								<?php echo esc_html( number_format_i18n( $nntm_r1_la_p ) ); ?>
							</a>
						<?php endif; ?>
					<?php endfor; ?>

					<?php $nntm_r1_la_co_sau = $nntm_r1_la_trang < $nntm_r1_la_query->max_num_pages; ?>
					<?php if ( $nntm_r1_la_co_sau ) : ?>
						<a class="nntm-r1-paging__nut" href="<?php echo esc_url( $nntm_r1_la_url( $nntm_r1_la_base, $nntm_r1_la_dang_loc, $nntm_r1_la_trang + 1 ) ); ?>">
							<span class="nntm-sr-only"><?php esc_html_e( 'Trang sau', 'nntm' ); ?></span>
							<svg viewBox="0 0 20 16" aria-hidden="true" focusable="false"><path d="M12 1l7 7-7 7M19 8H1" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
						</a>
					<?php else : ?>
						<span class="nntm-r1-paging__nut is-tat" aria-hidden="true">
							<svg viewBox="0 0 20 16" focusable="false"><path d="M12 1l7 7-7 7M19 8H1" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
						</span>
					<?php endif; ?>
				</nav>
			<?php endif; ?>

		<?php else : ?>
			<p class="nntm-r1-bai-viet__trong"><?php esc_html_e( 'Chưa có bài viết nào trong chuyên mục này.', 'nntm' ); ?></p>
		<?php endif; ?>

	</div>
</section>
