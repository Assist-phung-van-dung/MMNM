<?php
/**
 * "Khoá tu" — đăng ký của người này, mới nhất trước (bảng nntm_dkkt_bang()).
 */

defined( 'ABSPATH' ) || exit;

$nntm_tk_ds = nntm_tk_lay_khoa_tu( get_current_user_id() );
?>
<section class="nntm-tk__muc" id="khoa-tu" aria-labelledby="nntm-tk-h-khoa-tu">
	<h2 class="nntm-tk__muc-tieu-de" id="nntm-tk-h-khoa-tu"><?php esc_html_e( 'Khoá tu', 'nntm' ); ?></h2>

	<?php if ( empty( $nntm_tk_ds ) ) : ?>
		<p class="nntm-tk__rong">
			<?php esc_html_e( 'Bạn chưa đăng ký khoá tu nào.', 'nntm' ); ?>
			<?php $nntm_tk_kho = get_post_type_archive_link( 'nntm_retreat' ); ?>
			<?php if ( $nntm_tk_kho ) : ?>
				<a href="<?php echo esc_url( $nntm_tk_kho ); ?>"><?php esc_html_e( 'Xem các khoá tu', 'nntm' ); ?></a>
			<?php endif; ?>
		</p>
	<?php else : ?>
		<ul class="nntm-tk__ds-khoa-tu">
			<?php foreach ( $nntm_tk_ds as $nntm_tk_kt ) : ?>
				<li class="nntm-tk__khoa-tu-dong">
					<div>
						<?php if ( '' !== $nntm_tk_kt['url'] ) : ?>
							<a class="nntm-tk__khoa-tu-ten" href="<?php echo esc_url( $nntm_tk_kt['url'] ); ?>"><?php echo esc_html( $nntm_tk_kt['tieu_de'] ); ?></a>
						<?php else : ?>
							<span class="nntm-tk__khoa-tu-ten"><?php echo esc_html( $nntm_tk_kt['tieu_de'] ); ?></span>
						<?php endif; ?>
						<p class="nntm-tk__khoa-tu-ngay"><?php echo esc_html( $nntm_tk_kt['ngay'] ); ?></p>
					</div>
					<span class="nntm-tk__pill nntm-tk__pill--<?php echo esc_attr( $nntm_tk_kt['trang_thai_ma'] ); ?>"><?php echo esc_html( $nntm_tk_kt['trang_thai_nhan'] ); ?></span>
				</li>
			<?php endforeach; ?>
		</ul>
	<?php endif; ?>
</section>
