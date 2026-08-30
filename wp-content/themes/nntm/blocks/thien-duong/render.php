<?php

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/inc/register-track-audio-meta.php';
require_once __DIR__ . '/inc/render-thien-duong.php';

$heading    = isset( $attributes['heading'] ) ? (string) $attributes['heading'] : '';
$subheading = isset( $attributes['subheading'] ) ? (string) $attributes['subheading'] : '';

$cover_image_id  = isset( $attributes['coverImageId'] ) ? absint( $attributes['coverImageId'] ) : 0;
$cover_image_url = isset( $attributes['coverImageUrl'] ) ? esc_url_raw( (string) $attributes['coverImageUrl'] ) : '';

$tracks_per_page = isset( $attributes['tracksPerPage'] ) ? absint( $attributes['tracksPerPage'] ) : 20;
$tracks_per_page = max( 1, min( 50, $tracks_per_page ) );

$allowed_order_by = array( 'newest', 'oldest', 'title' );
$order_by_choice   = isset( $attributes['orderBy'] ) ? sanitize_key( (string) $attributes['orderBy'] ) : 'newest';
if ( ! in_array( $order_by_choice, $allowed_order_by, true ) ) {
	$order_by_choice = 'newest';
}

/*
 * Neo của khối, dùng làm đích quay về sau khi đăng nhập / đăng ký.
 *
 * Khối có bật "anchor" nên biên tập viên tự đặt được id; lúc đó WP đã in id ra
 * qua wrapper rồi, ta chỉ mượn lại giá trị. Không đặt thì tự sinh một id ổn
 * định theo thứ tự khối trên trang, để một trang có hai Thiền Đường vẫn không
 * trùng id.
 */
$anchor = isset( $attributes['anchor'] ) ? trim( (string) $attributes['anchor'] ) : '';

$wrapper_extra = array( 'class' => 'nntm-thien-duong' );

if ( '' === $anchor ) {
	/*
	 * Đếm qua $GLOBALS chứ không phải static: tệp này được include lại mỗi lần
	 * dựng khối, static ở đây không chắc giữ được giá trị giữa các lần gọi.
	 */
	$GLOBALS['nntm_thien_duong_stt'] = isset( $GLOBALS['nntm_thien_duong_stt'] )
		? (int) $GLOBALS['nntm_thien_duong_stt'] + 1
		: 1;

	$anchor              = 'nntm-thien-duong-' . $GLOBALS['nntm_thien_duong_stt'];
	$wrapper_extra['id'] = $anchor;
}

$wrapper_attributes = get_block_wrapper_attributes( $wrapper_extra );

$is_logged_in = (bool) apply_filters( 'nntm_thien_duong_can_access', is_user_logged_in() );

?>
<section <?php echo $wrapper_attributes;  ?>>
	<div class="nntm-thien-duong__inner">
		<div class="nntm-thien-duong__header">
			<?php if ( '' !== trim( wp_strip_all_tags( $heading ) ) ) : ?>
				<h2 class="nntm-thien-duong__heading"><?php echo wp_kses_post( $heading ); ?></h2>
			<?php endif; ?>

			<?php if ( '' !== trim( wp_strip_all_tags( $subheading ) ) ) : ?>
				<p class="nntm-thien-duong__subheading"><?php echo wp_kses_post( $subheading ); ?></p>
			<?php endif; ?>
		</div>

		<div class="nntm-thien-duong__embed">
			<div class="nntm-thien-duong__cover">
				<?php
				if ( $cover_image_id > 0 ) :
					echo wp_kses_post(
						wp_get_attachment_image(
							$cover_image_id,
							'large',
							false,
							array(
								'class'   => 'nntm-thien-duong__cover-img',
								'loading' => 'lazy',
								'alt'     => '',  
							)
						)
					);
				elseif ( '' !== $cover_image_url ) :
					?>
					<img class="nntm-thien-duong__cover-img" src="<?php echo esc_url( $cover_image_url ); ?>" alt="" loading="lazy" />
					<?php
				else :
					?>
					<span class="nntm-thien-duong__cover-placeholder" aria-hidden="true"></span>
					<?php
				endif;
				?>
			</div>

			<div class="nntm-thien-duong__player">
				<?php
				if ( ! $is_logged_in ) :
					echo nntm_thien_duong_render_guest_preview( $tracks_per_page, $order_by_choice, $anchor );  
				else :
					$tracks = nntm_thien_duong_get_tracks( $tracks_per_page, $order_by_choice );

					if ( empty( $tracks ) ) :
						?>
						<p class="nntm-thien-duong__empty"><?php esc_html_e( 'Chưa có bản nhạc thiền nào được đăng.', 'nntm' ); ?></p>
						<?php
					else :
						echo nntm_thien_duong_render_player( $tracks );  
					endif;
				endif;
				?>
			</div>
		</div>
	</div>
</section>
