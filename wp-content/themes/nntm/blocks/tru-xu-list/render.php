<?php

defined( 'ABSPATH' ) || exit;

 

$heading = isset( $attributes['heading'] ) ? (string) $attributes['heading'] : '';
$display_mode = isset( $attributes['displayMode'] ) ? sanitize_key( (string) $attributes['displayMode'] ) : 'cards';
if ( ! in_array( $display_mode, array( 'cards', 'list' ), true ) ) {
	$display_mode = 'cards';
}

$posts_per_page = isset( $attributes['postsPerPage'] ) ? absint( $attributes['postsPerPage'] ) : 4;
$posts_per_page = max( 1, min( 12, $posts_per_page ) );  

$allowed_order_by = array( 'newest', 'oldest', 'title' );
$order_by_choice  = isset( $attributes['orderBy'] ) ? sanitize_key( (string) $attributes['orderBy'] ) : 'newest';
if ( ! in_array( $order_by_choice, $allowed_order_by, true ) ) {
	$order_by_choice = 'newest';
}

$query_args = array(
	'post_type'           => 'nntm_abode',
	'post_status'         => 'publish',
	'posts_per_page'      => $posts_per_page,
	'ignore_sticky_posts' => true,
	'no_found_rows'       => true,  
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

	case 'newest':
	default:
		$query_args['orderby'] = 'date';
		$query_args['order']   = 'DESC';
		break;
}

$query = new WP_Query( $query_args );

$wrapper_attributes = get_block_wrapper_attributes( array( 'class' => 'nntm-tru-xu-list nntm-tru-xu-list--' . $display_mode ) );
?>
<section <?php echo $wrapper_attributes;  ?>>
	<div class="nntm-container">
		<?php if ( '' !== trim( wp_strip_all_tags( $heading ) ) ) : ?>
			<h2 class="nntm-tru-xu-list__heading"><?php echo wp_kses_post( $heading ); ?></h2>
		<?php endif; ?>

		<?php if ( $query->have_posts() && 'list' === $display_mode ) : ?>
			<ul class="nntm-tru-xu-list__plain-list">
				<?php foreach ( $query->posts as $index => $abode ) : ?>
					<?php $location = (string) get_post_meta( $abode->ID, '_nntm_abode_location', true ); ?>
					<li style="--nntm-item-index: <?php echo esc_attr( (string) $index ); ?>">
						<a href="<?php echo esc_url( get_permalink( $abode ) ); ?>">
							<?php echo esc_html( get_the_title( $abode ) ); ?><?php echo '' !== trim( $location ) ? ' (' . esc_html( $location ) . ')' : ''; ?>
						</a>

						<?php
						// Trú Xứ chưa nhập toạ độ thì hàm trả về rỗng, không hiện nút.
						if ( function_exists( 'nntm_tru_xu_nut_dia_chi' ) ) {
							echo nntm_tru_xu_nut_dia_chi( (int) $abode->ID );  
						}
						?>
					</li>
				<?php endforeach; wp_reset_postdata(); ?>
			</ul>
		<?php elseif ( $query->have_posts() ) : ?>
			<div class="nntm-tru-xu-list__grid">
				<?php
				foreach ( $query->posts as $abode ) :
					$permalink = get_permalink( $abode );
					$title     = get_the_title( $abode );

					$location = get_post_meta( $abode->ID, '_nntm_abode_location', true );
					if ( ! is_string( $location ) || '' === trim( $location ) ) {
						 
						$location = $abode->post_excerpt;
					}

					$thumbnail = get_the_post_thumbnail(
						$abode,
						'medium_large',
						array(
							'class'   => 'nntm-tru-xu-card__img-el',
							'loading' => 'lazy',
							'alt'     => $title,
						)
					);
					?>
					<?php
					/*
					 * Thẻ Trú Xứ vốn là một liên kết bọc toàn bộ. Nút "Địa chỉ"
					 * KHÔNG được nằm trong liên kết đó (button trong a là HTML
					 * sai và bấm vào sẽ chuyển trang), nên bọc thêm một lớp
					 * ngoài để đặt nút cạnh liên kết.
					 */
					$nut_dia_chi = function_exists( 'nntm_tru_xu_nut_dia_chi' )
						? nntm_tru_xu_nut_dia_chi( (int) $abode->ID )
						: '';
					?>
					<div class="nntm-tru-xu-o">
					<a href="<?php echo esc_url( $permalink ); ?>" class="nntm-tru-xu-card">
						<span class="nntm-tru-xu-card__img">
							<?php
							if ( $thumbnail ) {
								echo wp_kses_post( $thumbnail );
							} else {
								echo '<span class="nntm-tru-xu-card__img-placeholder" aria-hidden="true"></span>';
							}
							?>
						</span>
						<span class="nntm-tru-xu-card__overlay">
							<span class="nntm-tru-xu-card__name"><?php echo esc_html( $title ); ?></span>
							<?php if ( '' !== trim( (string) $location ) ) : ?>
								<span class="nntm-tru-xu-card__location"><?php echo esc_html( $location ); ?></span>
							<?php endif; ?>
						</span>
					</a>

					<?php
					// Trú Xứ chưa nhập toạ độ thì hàm trả về rỗng, không hiện nút.
					echo $nut_dia_chi;  
					?>
					</div>
					<?php
				endforeach;
				wp_reset_postdata();
				?>
			</div>
		<?php else : ?>
			<p class="nntm-tru-xu-list__empty"><?php esc_html_e( 'Chưa có Trú Xứ nào được đăng.', 'nntm' ); ?></p>
		<?php endif; ?>
	</div>
</section>
