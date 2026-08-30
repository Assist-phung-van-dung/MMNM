<?php
/**
 * Màn "Đơn hàng" — trang riêng dưới menu Ấn phẩm.
 *
 * Tách khỏi màn Thanh toán vì hai việc khác nhau: màn kia để CÀI ĐẶT (nhập khoá,
 * lấy địa chỉ webhook), màn này để THEO DÕI hằng ngày. Nhét chung thì mỗi lần
 * xem đơn lại phải lướt qua phần cấu hình.
 *
 * @package NNTM_PayOS
 */

defined( 'ABSPATH' ) || exit;

/**
 * Bảng màu cho từng trạng thái đơn.
 *
 * @return array<string,array{nhan:string, chu:string, nen:string, vien:string}>
 */
function nntm_payos_mau_trang_thai(): array {
	return array(
		'paid'      => array(
			'nhan' => __( 'Đã thanh toán', 'nntm' ),
			'chu'  => '#0a5c2e',
			'nen'  => '#d7f2e2',
			'vien' => '#8fd6ae',
		),
		'pending'   => array(
			'nhan' => __( 'Đang chờ', 'nntm' ),
			'chu'  => '#8a5a00',
			'nen'  => '#fdf0d5',
			'vien' => '#eccb84',
		),
		'cancelled' => array(
			'nhan' => __( 'Đã huỷ', 'nntm' ),
			'chu'  => '#5c5f62',
			'nen'  => '#eceeef',
			'vien' => '#c9cdd0',
		),
		'failed'    => array(
			'nhan' => __( 'Thất bại', 'nntm' ),
			'chu'  => '#8c1d18',
			'nen'  => '#fbe3e1',
			'vien' => '#eeaaa4',
		),
	);
}

/**
 * Vẽ một cái nhãn trạng thái có màu.
 *
 * @param string $trang_thai Trạng thái thô trong CSDL.
 */
function nntm_payos_nhan_trang_thai( string $trang_thai ): string {
	$bang = nntm_payos_mau_trang_thai();
	$m    = $bang[ $trang_thai ] ?? array(
		'nhan' => $trang_thai,
		'chu'  => '#5c5f62',
		'nen'  => '#eceeef',
		'vien' => '#c9cdd0',
	);

	return sprintf(
		'<span style="display:inline-block;padding:3px 10px;border:1px solid %s;border-radius:999px;background:%s;color:%s;font-weight:600;font-size:12px;white-space:nowrap;">%s</span>',
		esc_attr( $m['vien'] ),
		esc_attr( $m['nen'] ),
		esc_attr( $m['chu'] ),
		esc_html( $m['nhan'] )
	);
}

/**
 * Thêm màn Đơn hàng vào menu.
 */
function nntm_payos_them_man_don(): void {
	add_submenu_page(
		'edit.php?post_type=nntm_publication',
		__( 'Đơn hàng', 'nntm' ),
		__( 'Đơn hàng', 'nntm' ),
		'manage_options',
		'nntm-don-hang',
		'nntm_payos_ve_man_don'
	);
}
add_action( 'admin_menu', 'nntm_payos_them_man_don' );

/**
 * Đếm đơn theo từng trạng thái.
 *
 * @return array<string,int>
 */
function nntm_payos_dem_theo_trang_thai(): array {
	global $wpdb;

	$bang = nntm_payos_bang();
	$ra   = array();

	foreach ( $wpdb->get_results( "SELECT status, COUNT(*) AS n, SUM(amount) AS tien FROM {$bang} GROUP BY status" ) as $r ) {
		$ra[ $r->status ] = array(
			'n'    => (int) $r->n,
			'tien' => (int) $r->tien,
		);
	}

	return $ra;
}

/**
 * Vẽ màn Đơn hàng.
 */
function nntm_payos_ve_man_don(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	global $wpdb;

	$bang = nntm_payos_bang();

	if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $bang ) ) !== $bang ) {
		echo '<div class="wrap"><h1>' . esc_html__( 'Đơn hàng', 'nntm' ) . '</h1>';
		echo '<p>' . esc_html__( 'Chưa có bảng đơn hàng. Tắt rồi bật lại plugin NNTM PayOS.', 'nntm' ) . '</p></div>';
		return;
	}

	// phpcs:disable WordPress.Security.NonceVerification.Recommended -- chi loc de xem, khong doi trang thai.
	$loc   = isset( $_GET['tt'] ) ? sanitize_key( wp_unslash( $_GET['tt'] ) ) : '';
	$trang = isset( $_GET['paged'] ) ? max( 1, absint( wp_unslash( $_GET['paged'] ) ) ) : 1;
	// phpcs:enable

	$moi_trang = 30;
	$bo_qua    = ( $trang - 1 ) * $moi_trang;
	$dem       = nntm_payos_dem_theo_trang_thai();
	$hop_le    = array_keys( nntm_payos_mau_trang_thai() );
	$loc_dung  = in_array( $loc, $hop_le, true ) ? $loc : '';

	if ( '' !== $loc_dung ) {
		$tong = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$bang} WHERE status = %s", $loc_dung ) );
		$rows = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$bang} WHERE status = %s ORDER BY id DESC LIMIT %d OFFSET %d", $loc_dung, $moi_trang, $bo_qua ) );
	} else {
		$tong = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$bang}" );
		$rows = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$bang} ORDER BY id DESC LIMIT %d OFFSET %d", $moi_trang, $bo_qua ) );
	}

	$url_goc = admin_url( 'edit.php?post_type=nntm_publication&page=nntm-don-hang' );
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Đơn hàng', 'nntm' ); ?></h1>

		<?php /* Thẻ tổng kết — nhìn một cái là biết tình hình. */ ?>
		<div style="display:flex;flex-wrap:wrap;gap:12px;margin:16px 0 20px;">
			<?php foreach ( nntm_payos_mau_trang_thai() as $ma => $m ) : ?>
				<?php
				$so   = $dem[ $ma ]['n'] ?? 0;
				$tien = $dem[ $ma ]['tien'] ?? 0;
				?>
				<a href="<?php echo esc_url( add_query_arg( 'tt', $ma, $url_goc ) ); ?>"
					style="flex:1 1 170px;min-width:170px;padding:14px 16px;border:1px solid <?php echo esc_attr( $m['vien'] ); ?>;border-radius:8px;background:<?php echo esc_attr( $m['nen'] ); ?>;text-decoration:none;<?php echo $loc_dung === $ma ? 'box-shadow:0 0 0 2px ' . esc_attr( $m['chu'] ) . ';' : ''; ?>">
					<div style="font-size:12px;font-weight:700;color:<?php echo esc_attr( $m['chu'] ); ?>;"><?php echo esc_html( $m['nhan'] ); ?></div>
					<div style="font-size:26px;font-weight:700;line-height:1.2;color:<?php echo esc_attr( $m['chu'] ); ?>;"><?php echo esc_html( number_format_i18n( $so ) ); ?></div>
					<div style="font-size:12px;color:<?php echo esc_attr( $m['chu'] ); ?>;opacity:.85;"><?php echo esc_html( nntm_payos_dinh_dang_tien( $tien ) ); ?></div>
				</a>
			<?php endforeach; ?>
		</div>

		<p>
			<?php if ( '' !== $loc_dung ) : ?>
				<a class="button" href="<?php echo esc_url( $url_goc ); ?>"><?php esc_html_e( '← Xem tất cả', 'nntm' ); ?></a>
			<?php endif; ?>
			<span style="margin-left:8px;color:#646970;">
				<?php
				printf(
					/* translators: %d: tổng số đơn. */
					esc_html__( '%d đơn', 'nntm' ),
					(int) $tong
				);
				?>
			</span>
		</p>

		<?php if ( ! $rows ) : ?>
			<div class="notice notice-info inline"><p><?php esc_html_e( 'Chưa có đơn nào.', 'nntm' ); ?></p></div>
		<?php else : ?>
			<table class="widefat striped">
				<thead>
					<tr>
						<th style="width:150px;"><?php esc_html_e( 'Mã đơn', 'nntm' ); ?></th>
						<th style="width:150px;"><?php esc_html_e( 'Trạng thái', 'nntm' ); ?></th>
						<th><?php esc_html_e( 'Ấn phẩm', 'nntm' ); ?></th>
						<th style="width:170px;"><?php esc_html_e( 'Người mua', 'nntm' ); ?></th>
						<th style="width:130px;"><?php esc_html_e( 'Số tiền', 'nntm' ); ?></th>
						<th style="width:160px;"><?php esc_html_e( 'Tạo lúc', 'nntm' ); ?></th>
						<th style="width:160px;"><?php esc_html_e( 'Trả lúc', 'nntm' ); ?></th>
					</tr>
				</thead>
				<tbody>
				<?php foreach ( $rows as $r ) : ?>
					<?php $u = get_userdata( (int) $r->user_id ); ?>
					<tr>
						<td><code><?php echo esc_html( (string) $r->order_code ); ?></code></td>
						<td><?php echo wp_kses_post( nntm_payos_nhan_trang_thai( (string) $r->status ) ); ?></td>
						<td>
							<a href="<?php echo esc_url( (string) get_edit_post_link( (int) $r->post_id ) ); ?>">
								<?php echo esc_html( get_the_title( (int) $r->post_id ) ?: '#' . (int) $r->post_id ); ?>
							</a>
						</td>
						<td>
							<?php if ( $u ) : ?>
								<a href="<?php echo esc_url( get_edit_user_link( $u->ID ) ); ?>"><?php echo esc_html( $u->user_login ); ?></a>
							<?php else : ?>
								<em><?php echo esc_html( '#' . (int) $r->user_id ); ?></em>
							<?php endif; ?>
						</td>
						<td><strong><?php echo esc_html( nntm_payos_dinh_dang_tien( (int) $r->amount ) ); ?></strong></td>
						<td><?php echo esc_html( (string) $r->created_at ); ?></td>
						<td><?php echo $r->paid_at ? esc_html( (string) $r->paid_at ) : '—'; ?></td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>

			<?php
			$so_trang = (int) ceil( $tong / $moi_trang );

			if ( $so_trang > 1 ) :
				?>
				<div class="tablenav"><div class="tablenav-pages" style="margin:12px 0;">
					<?php
					echo wp_kses_post(
						paginate_links(
							array(
								'base'      => add_query_arg( 'paged', '%#%', '' !== $loc_dung ? add_query_arg( 'tt', $loc_dung, $url_goc ) : $url_goc ),
								'format'    => '',
								'current'   => $trang,
								'total'     => $so_trang,
								'prev_text' => '‹',
								'next_text' => '›',
							)
						)
					);
					?>
				</div></div>
			<?php endif; ?>
		<?php endif; ?>
	</div>
	<?php
}
