<?php

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/inc/render-engineering-earth.php';

$heading       = isset( $attributes['heading'] ) ? (string) $attributes['heading'] : '';
$subheading    = isset( $attributes['subheading'] ) ? (string) $attributes['subheading'] : '';
$band_title    = isset( $attributes['bandTitle'] ) ? (string) $attributes['bandTitle'] : '';
$band_subtitle = isset( $attributes['bandSubtitle'] ) ? (string) $attributes['bandSubtitle'] : '';
$caption       = isset( $attributes['caption'] ) ? (string) $attributes['caption'] : '';

$main_video_url = isset( $attributes['mainVideoUrl'] ) ? (string) $attributes['mainVideoUrl'] : '';
$bg_video_url   = isset( $attributes['bgVideoUrl'] ) ? (string) $attributes['bgVideoUrl'] : '';
$video_post_id  = isset( $attributes['videoId'] ) ? absint( $attributes['videoId'] ) : 0;
$video_post_url = ( $video_post_id > 0 && 'nntm_video' === get_post_type( $video_post_id ) )
	? get_permalink( $video_post_id )
	: '';

 
 
$main_image_id  = isset( $attributes['mainImageId'] ) ? absint( $attributes['mainImageId'] ) : 0;
$main_image_url = isset( $attributes['mainImageUrl'] ) ? esc_url_raw( (string) $attributes['mainImageUrl'] ) : '';
$main_image_alt = isset( $attributes['mainImageAlt'] ) ? sanitize_text_field( (string) $attributes['mainImageAlt'] ) : '';

$is_home_figma = is_front_page();
$wrapper_class = $is_home_figma
	? 'nntm-engineering-earth nntm-engineering-earth--homepage-figma'
	: 'nntm-engineering-earth';
$wrapper_attributes = get_block_wrapper_attributes( array( 'class' => $wrapper_class ) );

$figma_main_image = get_theme_file_uri( '/assets/images/homepage/engineering-main.png' );
$figma_pip_image  = get_theme_file_uri( '/assets/images/homepage/engineering-pip.png' );
$main_video_id    = nntm_engineering_earth_extract_youtube_id( $main_video_url );
?>
<section <?php echo $wrapper_attributes;  ?>>

	<?php  ?>
	<div class="nntm-engineering-earth__white">
		<?php if ( '' !== trim( $heading ) || '' !== trim( $subheading ) ) : ?>
			<div class="nntm-engineering-earth__heading-group">
				<?php if ( '' !== trim( $heading ) ) : ?>
					<h2 class="nntm-engineering-earth__heading"><?php echo wp_kses_post( $heading ); ?></h2>
				<?php endif; ?>

				<?php if ( '' !== trim( $subheading ) ) : ?>
					<p class="nntm-engineering-earth__subheading"><?php echo wp_kses_post( $subheading ); ?></p>
				<?php endif; ?>
			</div>
		<?php endif; ?>
	</div>

	<?php if ( $is_home_figma ) : ?>
		<?php   ?>
		<div class="nntm-engineering-earth__band">
			<div class="nntm-engineering-earth__band-inner">
				<div class="nntm-engineering-earth__video-stage" data-nntm-ee-stage="1">
					<div
						class="nntm-engineering-earth__video-slot nntm-engineering-earth__video-slot--main nntm-engineering-earth__figma-main"
						data-role="main"
						data-video-id="<?php echo esc_attr( $main_video_id ); ?>"
						aria-label="<?php echo esc_attr( wp_strip_all_tags( $band_title ) ); ?>"
					>
						<img class="nntm-engineering-earth__video-poster" src="<?php echo esc_url( $figma_main_image ); ?>" alt="<?php echo esc_attr( wp_strip_all_tags( $band_title ) ); ?>">
						<div class="nntm-engineering-earth__video-embed" aria-hidden="true"></div>
						<?php if ( '' !== $video_post_url ) : ?>
							<a class="nntm-engineering-earth__video-link" href="<?php echo esc_url( $video_post_url ); ?>">
								<span class="nntm-sr-only"><?php esc_html_e( 'Xem bài viết video', 'nntm' ); ?></span>
							</a>
						<?php endif; ?>
					</div>
				</div>

				<?php if ( '' !== trim( $band_title ) || '' !== trim( $band_subtitle ) ) : ?>
					<div class="nntm-engineering-earth__band-text">
						<?php if ( '' !== trim( $band_title ) ) : ?>
							<h3 class="nntm-engineering-earth__band-title"><?php echo wp_kses_post( $band_title ); ?></h3>
						<?php endif; ?>
						<?php if ( '' !== trim( $band_subtitle ) ) : ?>
							<p class="nntm-engineering-earth__band-subtitle"><?php echo wp_kses_post( $band_subtitle ); ?></p>
						<?php endif; ?>
					</div>
				<?php endif; ?>
			</div>
		</div>

		<?php if ( '' !== trim( $caption ) ) : ?>
			<p class="nntm-engineering-earth__caption"><?php echo wp_kses_post( $caption ); ?></p>
		<?php endif; ?>

		<?php
		 
		$bg_video_id = nntm_engineering_earth_extract_youtube_id( $bg_video_url );
		?>
		<div class="nntm-engineering-earth__figma-pip">
			<div
				class="nntm-engineering-earth__video-slot nntm-engineering-earth__figma-pip-slot"
				data-role="bg"
				data-video-id="<?php echo esc_attr( $bg_video_id ); ?>"
				aria-label="<?php echo esc_attr__( 'Video Engineering Earth', 'nntm' ); ?>"
			>
				<?php if ( '' !== $bg_video_id ) : ?>
					<img class="nntm-engineering-earth__video-poster" src="<?php echo esc_url( 'https://img.youtube.com/vi/' . $bg_video_id . '/hqdefault.jpg' ); ?>" alt="" loading="lazy" decoding="async" />
				<?php else : ?>
					<img class="nntm-engineering-earth__video-poster" src="<?php echo esc_url( $figma_pip_image ); ?>" alt="" loading="lazy" decoding="async" />
				<?php endif; ?>

				<div class="nntm-engineering-earth__video-embed" aria-hidden="true"></div>

				<?php if ( '' !== $video_post_url ) : ?>
					<a class="nntm-engineering-earth__video-link" href="<?php echo esc_url( $video_post_url ); ?>">
						<span class="nntm-sr-only"><?php esc_html_e( 'Xem bài viết video', 'nntm' ); ?></span>
					</a>
				<?php endif; ?>
			</div>
		</div>
	<?php else : ?>
		<?php   ?>
		<div class="nntm-engineering-earth__band">
			<div class="nntm-engineering-earth__band-inner">
				<?php echo nntm_engineering_earth_render_video_stage( $main_video_url, $bg_video_url, $main_image_id, $main_image_url, $main_image_alt, $video_post_url );  ?>

				<?php if ( '' !== trim( $band_title ) || '' !== trim( $band_subtitle ) ) : ?>
					<div class="nntm-engineering-earth__band-text">
						<?php if ( '' !== trim( $band_title ) ) : ?>
							<h3 class="nntm-engineering-earth__band-title"><?php echo wp_kses_post( $band_title ); ?></h3>
						<?php endif; ?>
						<?php if ( '' !== trim( $band_subtitle ) ) : ?>
							<p class="nntm-engineering-earth__band-subtitle"><?php echo wp_kses_post( $band_subtitle ); ?></p>
						<?php endif; ?>
					</div>
				<?php endif; ?>
			</div>
		</div>
	<?php endif; ?>
</section>
