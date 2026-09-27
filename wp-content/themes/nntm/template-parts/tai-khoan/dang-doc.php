<?php
/**
 * "Đang đọc" — 6 ấn phẩm gần nhất theo bảng nntm_reading_progress.
 */

defined( 'ABSPATH' ) || exit;

$nntm_tk_ds = nntm_tk_lay_dang_doc( get_current_user_id(), 6 );
?>
<section class="nntm-tk__muc" id="dang-doc" aria-labelledby="nntm-tk-h-dang-doc">
	<h2 class="nntm-tk__muc-tieu-de" id="nntm-tk-h-dang-doc"><?php esc_html_e( 'Đang đọc', 'nntm' ); ?></h2>

	<?php if ( empty( $nntm_tk_ds ) ) : ?>
		<p class="nntm-tk__rong">
			<?php esc_html_e( 'Bạn chưa đọc dở cuốn nào. Ghé thư viện, chọn một cuốn để bắt đầu.', 'nntm' ); ?>
			<?php $nntm_tk_kho = get_post_type_archive_link( 'nntm_publication' ); ?>
			<?php if ( $nntm_tk_kho ) : ?>
				<a href="<?php echo esc_url( $nntm_tk_kho ); ?>"><?php esc_html_e( 'Xem thư viện ấn phẩm', 'nntm' ); ?></a>
			<?php endif; ?>
		</p>
	<?php else : ?>
		<div class="nntm-tk__the-luoi">
			<?php foreach ( $nntm_tk_ds as $nntm_tk_muc ) : ?>
				<article class="nntm-tk__the-sach">
					<div class="nntm-tk__the-sach-anh">
						<?php if ( '' !== $nntm_tk_muc['anh'] ) : ?>
							<img src="<?php echo esc_url( $nntm_tk_muc['anh'] ); ?>" alt="" loading="lazy" />
						<?php else : ?>
							<span class="nntm-tk__the-sach-anh-rong" aria-hidden="true"></span>
						<?php endif; ?>
					</div>
					<div class="nntm-tk__the-sach-noi-dung">
						<h3 class="nntm-tk__the-sach-ten"><?php echo esc_html( $nntm_tk_muc['tieu_de'] ); ?></h3>
						<p class="nntm-tk__the-sach-phu">
							<?php
							/* translators: %d: số trang */
							echo esc_html( sprintf( __( 'Đang ở trang %d', 'nntm' ), $nntm_tk_muc['trang'] ) );
							?>
							· <?php echo esc_html( $nntm_tk_muc['thoi_gian'] ); ?>
						</p>
						<?php if ( '' !== $nntm_tk_muc['url'] ) : ?>
							<a class="nntm-tk__nut nntm-tk__nut--nho" href="<?php echo esc_url( $nntm_tk_muc['url'] ); ?>"><?php esc_html_e( 'Đọc tiếp', 'nntm' ); ?></a>
						<?php endif; ?>
					</div>
				</article>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>
</section>
