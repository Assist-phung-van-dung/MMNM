<?php

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();

	$post_id = get_the_ID();
	$excerpt = trim( get_the_excerpt() );
	$topic   = function_exists( 'nntm_retreat_primary_topic' ) ? nntm_retreat_primary_topic( $post_id ) : null;
	$term_id = $topic instanceof WP_Term ? (int) $topic->term_id : 0;
	$is_lich = $topic instanceof WP_Term && 'lich-tu' === $topic->slug;
	$related_heading = $is_lich ? __( 'Lịch tu liên quan', 'nntm' ) : __( 'Khóa tu liên quan', 'nntm' );

	$nntm_cho_dang_ky  = function_exists( 'nntm_dkkt_duoc_dang_ky' ) && nntm_dkkt_duoc_dang_ky( (int) $post_id );
	$nntm_dang_ky      = $nntm_cho_dang_ky && function_exists( 'nntm_dkkt_cua_toi' ) ? nntm_dkkt_cua_toi( (int) $post_id ) : null;
	$nntm_trang_thai   = is_array( $nntm_dang_ky ) ? (string) $nntm_dang_ky['status'] : '';
	$nntm_da_dang_ky   = '' !== $nntm_trang_thai;
	$nntm_nhan_nut     = $nntm_da_dang_ky && function_exists( 'nntm_dkkt_nhan_nut' )
		? nntm_dkkt_nhan_nut( $nntm_trang_thai )
		: __( 'Đăng ký Khóa Tu', 'nntm' );
	$nntm_loi_nhan     = function_exists( 'nntm_dkkt_loi_nhan' ) ? nntm_dkkt_loi_nhan( $nntm_trang_thai ) : '';

	$current_user = wp_get_current_user();
	$full_name    = is_user_logged_in() ? $current_user->display_name : '';
	$email        = is_user_logged_in() ? $current_user->user_email : '';
	?>
	<main id="nntm-noi-dung-chinh" class="nntm-article-detail nntm-retreat-detail">
		<article <?php post_class( 'nntm-article-detail__article nntm-retreat-detail__article' ); ?>>
			<div class="nntm-article-detail__inner">
				<h1 class="nntm-article-detail__title"><?php the_title(); ?></h1>
					<?php
					/* Bài không chọn nhạc nền thì hàm trả chuỗi rỗng, không in ra gì. */
					if ( function_exists( 'nntm_render_nhac_nen' ) ) {
						echo nntm_render_nhac_nen( (int) get_the_ID() );  // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					}
					?>

				<p class="nntm-article-detail__meta">
					<span class="nntm-article-detail__meta-dot" aria-hidden="true"></span>
					<?php
					printf(
						 
						esc_html__( 'Cập nhật %s', 'nntm' ),
						esc_html( get_the_modified_date( 'd. m. Y' ) )
					);
					?>
				</p>

				<?php if ( '' !== $excerpt ) : ?>
					<p class="nntm-article-detail__intro"><?php echo esc_html( $excerpt ); ?></p>
				<?php endif; ?>

				<?php if ( has_post_thumbnail() ) : ?>
					<figure class="nntm-article-detail__media">
						<?php the_post_thumbnail( 'full', array( 'loading' => 'eager' ) ); ?>
					</figure>
				<?php endif; ?>

				<div class="nntm-article-detail__content">
					<?php the_content(); ?>
				</div>

				<div class="nntm-retreat-detail__actions">
					<?php
					if ( function_exists( 'nntm_section_render_favorite_button' ) ) {
						echo nntm_section_render_favorite_button( $post_id, 'nntm-retreat-detail__favorite-button' );  
					}
					?>

					<?php if ( $nntm_cho_dang_ky ) : ?>
					<button
						type="button"
						class="nntm-retreat-detail__register<?php echo $nntm_da_dang_ky ? ' is-da-dang-ky' : ''; ?>"
						data-nntm-retreat-open-register
					>
						<span data-nntm-retreat-nhan><?php echo esc_html( $nntm_nhan_nut ); ?></span>
					</button>
					<?php endif; ?>

					<?php
					if ( function_exists( 'nntm_render_chia_se' ) ) {
						echo nntm_render_chia_se( (int) get_the_ID(), array( 'class_nut' => 'nntm-retreat-detail__share' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					}
					?>
				</div>
			</div>

			<div class="nntm-article-detail__related nntm-retreat-detail__related">
				<?php
				echo render_block(
					array(
						'blockName'    => 'nntm/card-list',
						'attrs'        => array(
							'heading'          => $related_heading,
							'postType'         => 'nntm_retreat',
							'taxonomy'         => 'nntm_topic',
							'termId'           => $term_id,
							'variant'          => 'article',
							'layout'           => 'carousel',
							'postsPerPage'     => 8,
							'excludePostId'    => $post_id,
							'autoplay'         => true,
							'autoplayInterval' => 5,
							'background'       => 'none',
							'showDate'         => false,
							'showCategory'     => false,
							'showCardCta'      => true,
							'cardCtaLabel'     => __( 'Xem thêm', 'nntm' ),
						),
						'innerBlocks'  => array(),
						'innerHTML'    => '',
						'innerContent' => array(),
					)
				);  
				?>
			</div>
		</article>
	</main>

	<?php if ( $nntm_cho_dang_ky ) : ?>
	<div class="nntm-retreat-modal" data-nntm-retreat-modal data-nntm-retreat-id="<?php echo esc_attr( (string) $post_id ); ?>" hidden>
		<div class="nntm-retreat-modal__backdrop" data-nntm-retreat-close></div>
		<div class="nntm-retreat-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="nntm-retreat-modal-title">
			<button type="button" class="nntm-retreat-modal__close" data-nntm-retreat-close aria-label="<?php esc_attr_e( 'Đóng', 'nntm' ); ?>">&times;</button>
			<h2 id="nntm-retreat-modal-title" class="nntm-retreat-modal__title"><?php esc_html_e( 'Đăng ký Khóa Tu', 'nntm' ); ?></h2>
			<p class="nntm-retreat-modal__retreat-name"><?php the_title(); ?></p>

			<div class="nntm-retreat-modal__da-dang-ky" data-nntm-retreat-xong <?php echo $nntm_da_dang_ky ? '' : 'hidden'; ?>>
				<p class="nntm-retreat-modal__da-dang-ky-chu" data-nntm-retreat-xong-chu><?php echo esc_html( $nntm_loi_nhan ); ?></p>
				<button type="button" class="nntm-retreat-modal__submit" data-nntm-retreat-close><?php esc_html_e( 'Đã hiểu', 'nntm' ); ?></button>
			</div>

			<form class="nntm-retreat-modal__form" data-nntm-retreat-form novalidate <?php echo $nntm_da_dang_ky ? 'hidden' : ''; ?>>
				<input type="hidden" name="action" value="nntm_retreat_signup">
				<input type="hidden" name="retreat_id" value="<?php echo esc_attr( (string) $post_id ); ?>">
				<?php wp_nonce_field( 'nntm_retreat_signup', 'nonce' ); ?>
				<div class="nntm-retreat-modal__honeypot" aria-hidden="true">
					<label>Website <input type="text" name="website" tabindex="-1" autocomplete="off"></label>
				</div>

				<label class="nntm-retreat-modal__field">
					<span><?php esc_html_e( 'Họ và tên', 'nntm' ); ?></span>
					<input type="text" name="full_name" value="<?php echo esc_attr( $full_name ); ?>" autocomplete="name" required>
				</label>
				<label class="nntm-retreat-modal__field">
					<span><?php esc_html_e( 'Số điện thoại', 'nntm' ); ?></span>
					<input type="tel" name="phone" autocomplete="tel" required>
				</label>
				<label class="nntm-retreat-modal__field">
					<span><?php esc_html_e( 'Email', 'nntm' ); ?></span>
					<input type="email" name="email" value="<?php echo esc_attr( $email ); ?>" autocomplete="email" required>
				</label>
				<label class="nntm-retreat-modal__field">
					<span><?php esc_html_e( 'Ghi chú', 'nntm' ); ?></span>
					<textarea name="note" rows="4"></textarea>
				</label>

				<p class="nntm-retreat-modal__message" data-nntm-retreat-message aria-live="polite"></p>
				<button type="submit" class="nntm-retreat-modal__submit"><?php esc_html_e( 'Gửi đăng ký', 'nntm' ); ?></button>
			</form>
		</div>
	</div>
	<?php endif; ?>
	<?php
endwhile;

get_footer();
