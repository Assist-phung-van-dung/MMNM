<?php

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/inc/render-banner.php';

$nntm_bn_raw = ( isset( $attributes['slides'] ) && is_array( $attributes['slides'] ) ) ? $attributes['slides'] : array();

$nntm_bn_slides = array();
foreach ( $nntm_bn_raw as $nntm_bn_item ) {
	if ( ! is_array( $nntm_bn_item ) ) {
		continue;
	}
	$nntm_bn_clean = nntm_banner_clean_slide( $nntm_bn_item );
	if ( null !== $nntm_bn_clean ) {
		$nntm_bn_slides[] = $nntm_bn_clean;
	}
}

$nntm_bn_tong = count( $nntm_bn_slides );

if ( 0 === $nntm_bn_tong ) {
	 
	if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
		$nntm_bn_wrap_rong = get_block_wrapper_attributes( array( 'class' => 'nntm-banner nntm-banner--empty' ) );
		?>
		<div <?php echo $nntm_bn_wrap_rong;  ?>>
			<p class="nntm-banner__empty-notice">
				<?php esc_html_e( 'Chưa có tấm nào. Mở bảng điều khiển bên phải để thêm ảnh, tiêu đề và phụ đề cho ít nhất một tấm.', 'nntm' ); ?>
			</p>
		</div>
		<?php
	}
	return;
}

$nntm_bn_nhieu_tam = $nntm_bn_tong > 1;

$nntm_bn_autoplay = $nntm_bn_nhieu_tam && ( ! isset( $attributes['autoplay'] ) || ! empty( $attributes['autoplay'] ) );

$nntm_bn_chu_ky = isset( $attributes['interval'] ) ? (float) $attributes['interval'] : 6;
$nntm_bn_chu_ky = max( 2, min( 30, $nntm_bn_chu_ky ) );

$nntm_bn_emblem_id  = isset( $attributes['emblemId'] ) ? absint( $attributes['emblemId'] ) : 0;
$nntm_bn_emblem_url = isset( $attributes['emblemUrl'] ) ? esc_url_raw( (string) $attributes['emblemUrl'] ) : '';
$nntm_bn_emblem_alt = isset( $attributes['emblemAlt'] ) ? sanitize_text_field( (string) $attributes['emblemAlt'] ) : '';

$nntm_bn_tran_vien = ! empty( $attributes['tranVien'] );

$nntm_bn_wrapper = get_block_wrapper_attributes(
	array(
		'class'              => 'nntm-banner' . ( $nntm_bn_tran_vien ? ' nntm-banner--tran-vien' : '' ),
		'data-nntm-autoplay' => $nntm_bn_autoplay ? '1' : '0',
		'data-nntm-interval' => (string) $nntm_bn_chu_ky,
	)
);
?>
<section <?php echo $nntm_bn_wrapper;  ?>>
	<div
		class="nntm-banner__stage"
		<?php if ( $nntm_bn_nhieu_tam ) : ?>
			aria-roledescription="carousel"
			aria-label="<?php esc_attr_e( 'Băng chuyền ảnh lớn đầu trang', 'nntm' ); ?>"
		<?php endif; ?>
	>
		<?php foreach ( $nntm_bn_slides as $nntm_bn_i => $nntm_bn_slide ) : ?>
			<div
				class="nntm-banner__slide<?php echo 0 === $nntm_bn_i ? ' is-active' : ''; ?>"
				<?php if ( $nntm_bn_nhieu_tam ) : ?>
					role="group"
					aria-roledescription="slide"
					aria-label="<?php echo esc_attr( sprintf(   __( 'Tấm %1$d trên %2$d', 'nntm' ), $nntm_bn_i + 1, $nntm_bn_tong ) ); ?>"
				<?php endif; ?>
			>
				<?php echo nntm_banner_render_anh( $nntm_bn_slide, $nntm_bn_i );  ?>

				<div class="nntm-banner__overlay" aria-hidden="true"></div>

				<div class="nntm-banner__text">
					<?php

					if ( $nntm_bn_emblem_id > 0 ) :
						$nntm_bn_emblem_attrs = array(
							'class'    => 'nntm-banner__emblem',
							'alt'      => $nntm_bn_emblem_alt,
							'loading'  => 'lazy',
							'decoding' => 'async',
						);

						if ( '' === $nntm_bn_emblem_alt ) {
							$nntm_bn_emblem_attrs['role'] = 'presentation';
						}
						echo wp_get_attachment_image( $nntm_bn_emblem_id, 'medium', false, $nntm_bn_emblem_attrs );
					elseif ( '' !== $nntm_bn_emblem_url ) :
						?>
						<img
							class="nntm-banner__emblem"
							src="<?php echo esc_url( $nntm_bn_emblem_url ); ?>"
							alt="<?php echo esc_attr( $nntm_bn_emblem_alt ); ?>"
							loading="lazy"
							decoding="async"
							<?php echo '' === $nntm_bn_emblem_alt ? 'role="presentation"' : '';  ?>
						/>
						<?php
					endif;
					?>

					<div class="nntm-banner__text-inner">
						<?php if ( '' !== trim( $nntm_bn_slide['heading'] ) ) : ?>
							<p class="nntm-banner__heading"><?php echo nl2br( esc_html( $nntm_bn_slide['heading'] ) ); ?></p>
						<?php endif; ?>

						<?php if ( '' !== trim( $nntm_bn_slide['text'] ) ) : ?>
							<p class="nntm-banner__sub"><?php echo nl2br( esc_html( $nntm_bn_slide['text'] ) ); ?></p>
						<?php endif; ?>

						<?php
						 
						if ( ! empty( $nntm_bn_slide['showButton'] ) ) :
							$nntm_bn_btn_url   = apply_filters( 'nntm_tham_gia_chuoi_tri_url', '' );
							$nntm_bn_btn_label = '' !== trim( $nntm_bn_slide['buttonLabel'] ) ? $nntm_bn_slide['buttonLabel'] : __( 'Tham gia', 'nntm' );

							$nntm_bn_btn_label = apply_filters( 'nntm_banner_btn_label', $nntm_bn_btn_label, $nntm_bn_slide );
							$nntm_bn_btn_attrs = (array) apply_filters( 'nntm_banner_btn_attrs', array(), $nntm_bn_slide );

							 
							$nntm_bn_btn_attrs_html = '';
							foreach ( $nntm_bn_btn_attrs as $nntm_bn_attr_key => $nntm_bn_attr_val ) {
								if ( ! is_string( $nntm_bn_attr_key ) || ! preg_match( '/^[a-z][a-z0-9-]*$/', $nntm_bn_attr_key ) ) {
									continue;
								}
								$nntm_bn_btn_attrs_html .= ' ' . esc_attr( $nntm_bn_attr_key ) . '="' . esc_attr( (string) $nntm_bn_attr_val ) . '"';
							}

							if ( '' !== $nntm_bn_btn_url ) :
								?>
								<a class="nntm-banner__btn" href="<?php echo esc_url( $nntm_bn_btn_url ); ?>"<?php echo $nntm_bn_btn_attrs_html;  ?>><?php echo esc_html( $nntm_bn_btn_label ); ?></a>
								<?php
							else :
								?>
								<button
									type="button"
									class="nntm-banner__btn nntm-banner__btn--tat"
									disabled
									title="<?php esc_attr_e( 'Chức năng Cộng Tu (chuỗi trì) chưa mở — sẽ bật khi phần này hoàn tất.', 'nntm' ); ?>"
								><?php echo esc_html( $nntm_bn_btn_label ); ?></button>
								<?php
							endif;
						endif;
						?>
					</div>
				</div>
			</div>
		<?php endforeach; ?>

		<?php if ( $nntm_bn_nhieu_tam ) : ?>
			<?php echo nntm_banner_render_dots( $nntm_bn_tong );  ?>
		<?php endif; ?>
	</div>
</section>
