<?php
/**
 * "Yêu thích" — 6 mục gần nhất, xem thêm dẫn sang /yeu-thich/.
 */

defined( 'ABSPATH' ) || exit;

$nntm_tk_yt = nntm_tk_lay_yeu_thich( get_current_user_id() );
?>
<section class="nntm-tk__muc" id="yeu-thich" aria-labelledby="nntm-tk-h-yeu-thich">
	<h2 class="nntm-tk__muc-tieu-de" id="nntm-tk-h-yeu-thich"><?php esc_html_e( 'Yêu thích', 'nntm' ); ?></h2>

	<?php if ( empty( $nntm_tk_yt['muc'] ) ) : ?>
		<p class="nntm-tk__rong"><?php esc_html_e( 'Bạn chưa yêu thích bài viết hay ấn phẩm nào.', 'nntm' ); ?></p>
	<?php else : ?>
		<div class="nntm-tk__the-luoi nntm-tk__the-luoi--nho">
			<?php foreach ( $nntm_tk_yt['muc'] as $nntm_tk_muc ) : ?>
				<a class="nntm-tk__the-yeu-thich" href="<?php echo esc_url( $nntm_tk_muc['url'] ); ?>">
					<span class="nntm-tk__the-yeu-thich-anh">
						<?php if ( '' !== $nntm_tk_muc['anh'] ) : ?>
							<img src="<?php echo esc_url( $nntm_tk_muc['anh'] ); ?>" alt="" loading="lazy" />
						<?php else : ?>
							<span class="nntm-tk__the-sach-anh-rong" aria-hidden="true"></span>
						<?php endif; ?>
					</span>
					<span class="nntm-tk__the-yeu-thich-chu">
						<span class="nntm-tk__the-yeu-thich-loai"><?php echo esc_html( $nntm_tk_muc['loai_nhan'] ); ?></span>
						<span class="nntm-tk__the-yeu-thich-ten"><?php echo esc_html( $nntm_tk_muc['tieu_de'] ); ?></span>
					</span>
				</a>
			<?php endforeach; ?>
		</div>

		<?php $nntm_tk_url_yt = (string) apply_filters( 'nntm_account_favorites_url', home_url( '/yeu-thich/' ) ); ?>
		<p class="nntm-tk__xem-tat-ca">
			<a href="<?php echo esc_url( $nntm_tk_url_yt ); ?>">
				<?php
				/* translators: %d: tổng số mục yêu thích */
				echo esc_html( sprintf( __( 'Xem tất cả (%d)', 'nntm' ), (int) $nntm_tk_yt['tong'] ) );
				?>
			</a>
		</p>
	<?php endif; ?>
</section>
