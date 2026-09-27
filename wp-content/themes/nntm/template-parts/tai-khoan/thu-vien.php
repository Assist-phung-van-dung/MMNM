<?php
/**
 * "Thư viện của tôi" — sách đã mua (paid) + lịch sử đơn hàng nntm_payos_orders.
 * KHÔNG in qr_code/payment_link_id — nntm_tk_lay_thu_vien() đã lọc sạch trước khi tới đây.
 */

defined( 'ABSPATH' ) || exit;

$nntm_tk_tv = nntm_tk_lay_thu_vien( get_current_user_id() );
?>
<section class="nntm-tk__muc" id="thu-vien" aria-labelledby="nntm-tk-h-thu-vien">
	<h2 class="nntm-tk__muc-tieu-de" id="nntm-tk-h-thu-vien"><?php esc_html_e( 'Thư viện của tôi', 'nntm' ); ?></h2>

	<?php if ( empty( $nntm_tk_tv['sach'] ) && empty( $nntm_tk_tv['don_hang'] ) ) : ?>
		<p class="nntm-tk__rong">
			<?php esc_html_e( 'Bạn chưa thỉnh ấn phẩm nào.', 'nntm' ); ?>
			<?php $nntm_tk_kho = get_post_type_archive_link( 'nntm_publication' ); ?>
			<?php if ( $nntm_tk_kho ) : ?>
				<a href="<?php echo esc_url( $nntm_tk_kho ); ?>"><?php esc_html_e( 'Xem thư viện ấn phẩm', 'nntm' ); ?></a>
			<?php endif; ?>
		</p>
	<?php else : ?>

		<?php if ( ! empty( $nntm_tk_tv['sach'] ) ) : ?>
			<div class="nntm-tk__the-luoi">
				<?php foreach ( $nntm_tk_tv['sach'] as $nntm_tk_muc ) : ?>
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
							<?php if ( '' !== $nntm_tk_muc['url'] ) : ?>
								<a class="nntm-tk__nut nntm-tk__nut--nho" href="<?php echo esc_url( $nntm_tk_muc['url'] ); ?>"><?php esc_html_e( 'Đọc ngay', 'nntm' ); ?></a>
							<?php endif; ?>
						</div>
					</article>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>

		<?php if ( ! empty( $nntm_tk_tv['don_hang'] ) ) : ?>
			<h3 class="nntm-tk__phu-de"><?php esc_html_e( 'Lịch sử đơn hàng', 'nntm' ); ?></h3>

			<div class="nntm-tk__bang-boc">
				<table class="nntm-tk__bang">
					<thead>
						<tr>
							<th scope="col"><?php esc_html_e( 'Mã đơn', 'nntm' ); ?></th>
							<th scope="col"><?php esc_html_e( 'Ấn phẩm', 'nntm' ); ?></th>
							<th scope="col"><?php esc_html_e( 'Số tiền', 'nntm' ); ?></th>
							<th scope="col"><?php esc_html_e( 'Trạng thái', 'nntm' ); ?></th>
							<th scope="col"><?php esc_html_e( 'Ngày', 'nntm' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $nntm_tk_tv['don_hang'] as $nntm_tk_don ) : ?>
							<tr>
								<td data-nhan="<?php esc_attr_e( 'Mã đơn', 'nntm' ); ?>"><?php echo esc_html( $nntm_tk_don['ma'] ); ?></td>
								<td data-nhan="<?php esc_attr_e( 'Ấn phẩm', 'nntm' ); ?>"><?php echo esc_html( $nntm_tk_don['ten_an_pham'] ); ?></td>
								<td data-nhan="<?php esc_attr_e( 'Số tiền', 'nntm' ); ?>"><?php echo esc_html( $nntm_tk_don['so_tien_hien_thi'] ); ?></td>
								<td data-nhan="<?php esc_attr_e( 'Trạng thái', 'nntm' ); ?>">
									<span class="nntm-tk__pill nntm-tk__pill--<?php echo esc_attr( $nntm_tk_don['trang_thai_ma'] ); ?>"><?php echo esc_html( $nntm_tk_don['trang_thai_nhan'] ); ?></span>
									<?php if ( '' !== $nntm_tk_don['tiep_tuc_url'] ) : ?>
										<a class="nntm-tk__tiep-tuc" href="<?php echo esc_url( $nntm_tk_don['tiep_tuc_url'] ); ?>"><?php esc_html_e( 'Tiếp tục thanh toán', 'nntm' ); ?></a>
									<?php endif; ?>
								</td>
								<td data-nhan="<?php esc_attr_e( 'Ngày', 'nntm' ); ?>"><?php echo esc_html( $nntm_tk_don['ngay'] ); ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		<?php endif; ?>

	<?php endif; ?>
</section>
