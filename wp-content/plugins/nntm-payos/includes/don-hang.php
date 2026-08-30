<?php
/**
 * Đơn hàng và quyền đã mua.
 *
 * "Đã mua" = tồn tại một dòng status='paid' cho đúng cặp (người dùng, ấn phẩm).
 * Không cần bảng quyền riêng — thêm bảng thứ hai chỉ tạo thêm chỗ để hai bên
 * lệch nhau.
 *
 * @package NNTM_PayOS
 */

defined( 'ABSPATH' ) || exit;

/**
 * Tên bảng đơn hàng.
 */
function nntm_payos_bang(): string {
	global $wpdb;

	return $wpdb->prefix . 'nntm_payos_orders';
}

/**
 * Dựng bảng đơn hàng.
 */
function nntm_payos_dung_bang(): void {
	global $wpdb;

	require_once ABSPATH . 'wp-admin/includes/upgrade.php';

	$bang    = nntm_payos_bang();
	$collate = $wpdb->get_charset_collate();

	/*
	 * order_code UNIQUE là chốt chặn ghi trùng: PayOS gọi lại webhook nhiều lần
	 * là chuyện bình thường, không có ràng buộc này thì một lần trả tiền có thể
	 * sinh ra vài dòng "đã thanh toán".
	 */
	dbDelta(
		"CREATE TABLE {$bang} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			user_id BIGINT UNSIGNED NOT NULL,
			post_id BIGINT UNSIGNED NOT NULL,
			amount BIGINT UNSIGNED NOT NULL,
			status VARCHAR(20) NOT NULL DEFAULT 'pending',
			order_code BIGINT UNSIGNED NOT NULL,
			payment_link_id VARCHAR(64) NOT NULL DEFAULT '',
			checkout_url TEXT NULL,
			qr_code TEXT NULL,
			created_at DATETIME NOT NULL,
			paid_at DATETIME NULL,
			PRIMARY KEY (id),
			UNIQUE KEY order_code (order_code),
			KEY user_post (user_id, post_id),
			KEY status (status)
		) {$collate};"
	);

	update_option( 'nntm_payos_db_ver', NNTM_PAYOS_VER );
}

/**
 * Người dùng đã mua ấn phẩm này chưa.
 *
 * @param int $user_id ID người dùng.
 * @param int $post_id ID ấn phẩm.
 */
function nntm_payos_da_mua( int $user_id, int $post_id ): bool {
	global $wpdb;

	if ( $user_id <= 0 || $post_id <= 0 ) {
		return false;
	}

	$bang = nntm_payos_bang();

	return (bool) $wpdb->get_var(
		$wpdb->prepare(
			"SELECT id FROM {$bang} WHERE user_id = %d AND post_id = %d AND status = 'paid' LIMIT 1",
			$user_id,
			$post_id
		)
	);
}

/**
 * Cắm vào cổng quyền của nntm-library.
 *
 * Đây là TOÀN BỘ mối nối giữa plugin này và phần còn lại của site. Tắt plugin
 * là bộ lọc biến mất, hàm gốc trả false, sách khoá vẫn khoá — không hở.
 *
 * @param bool         $da   Giá trị trước đó.
 * @param WP_Post|null $post Ấn phẩm.
 * @param int          $user ID người dùng.
 */
function nntm_payos_loc_da_thanh_toan( $da, $post, $user ): bool {
	if ( $da ) {
		return true;
	}

	$post = get_post( $post );

	if ( ! $post || 'nntm_publication' !== $post->post_type ) {
		return false;
	}

	return nntm_payos_da_mua( (int) $user, (int) $post->ID );
}
add_filter( 'nntm_an_pham_da_thanh_toan', 'nntm_payos_loc_da_thanh_toan', 10, 3 );

/**
 * Đơn đang chờ gần nhất của một người cho một cuốn.
 *
 * Dùng lại đơn cũ thay vì đẻ đơn mới mỗi lần bấm: khách bấm "Mua" ba lần mà
 * chưa trả tiền thì không nên thành ba đơn treo.
 *
 * @param int $user_id ID người dùng.
 * @param int $post_id ID ấn phẩm.
 * @param int $so_tien Số tiền phải khớp.
 */
function nntm_payos_don_dang_cho( int $user_id, int $post_id, int $so_tien ) {
	global $wpdb;

	$bang = nntm_payos_bang();

	return $wpdb->get_row(
		$wpdb->prepare(
			"SELECT * FROM {$bang}
			 WHERE user_id = %d AND post_id = %d AND status = 'pending' AND amount = %d
			 ORDER BY id DESC LIMIT 1",
			$user_id,
			$post_id,
			$so_tien
		)
	);
}

/**
 * Tạo một đơn mới ở trạng thái chờ.
 *
 * @param int $user_id ID người dùng.
 * @param int $post_id ID ấn phẩm.
 * @param int $so_tien Số tiền, đồng.
 * @return array{id:int, order_code:int}|null
 */
function nntm_payos_tao_don( int $user_id, int $post_id, int $so_tien ): ?array {
	global $wpdb;

	$bang = nntm_payos_bang();

	/*
	 * PayOS đòi orderCode là số nguyên dương duy nhất. Lấy mốc thời gian giây
	 * ghép thêm ba chữ số ngẫu nhiên: đủ nhỏ để vừa kiểu số của PayOS, và hai
	 * người bấm cùng một giây vẫn ra hai mã khác nhau. Trùng thì UNIQUE chặn và
	 * vòng lặp thử lại.
	 */
	for ( $lan = 0; $lan < 5; $lan++ ) {
		$ma = (int) ( time() . wp_rand( 100, 999 ) );

		$ok = $wpdb->insert(
			$bang,
			array(
				'user_id'    => $user_id,
				'post_id'    => $post_id,
				'amount'     => $so_tien,
				'status'     => 'pending',
				'order_code' => $ma,
				'created_at' => current_time( 'mysql' ),
			),
			array( '%d', '%d', '%d', '%s', '%d', '%s' )
		);

		if ( $ok ) {
			return array(
				'id'         => (int) $wpdb->insert_id,
				'order_code' => $ma,
			);
		}
	}

	return null;
}

/**
 * Đọc một đơn theo mã đơn.
 *
 * @param int $order_code Mã đơn.
 */
function nntm_payos_don_theo_ma( int $order_code ) {
	global $wpdb;

	$bang = nntm_payos_bang();

	return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$bang} WHERE order_code = %d LIMIT 1", $order_code ) );
}

/**
 * Ghi nhận một đơn đã thanh toán.
 *
 * Không làm gì nếu đơn đã 'paid' — webhook gọi lại lần thứ hai không được ghi đè
 * mốc thời gian, và cũng không được coi là lỗi.
 *
 * @param int $order_code Mã đơn.
 * @param int $so_tien    Số tiền PayOS báo đã nhận.
 * @return bool Có ghi nhận được không.
 */
function nntm_payos_danh_dau_da_tra( int $order_code, int $so_tien ): bool {
	global $wpdb;

	$don = nntm_payos_don_theo_ma( $order_code );

	if ( ! $don ) {
		return false;
	}

	if ( 'paid' === $don->status ) {
		return true;
	}

	/*
	 * Số tiền phải khớp đúng đơn đã lưu. Không so thì ai đó trả 1.000đ cho một
	 * đơn 300.000đ vẫn mở được sách.
	 */
	if ( (int) $don->amount !== $so_tien ) {
		return false;
	}

	$wpdb->update(
		nntm_payos_bang(),
		array(
			'status'  => 'paid',
			'paid_at' => current_time( 'mysql' ),
		),
		array( 'order_code' => $order_code ),
		array( '%s', '%s' ),
		array( '%d' )
	);

	do_action( 'nntm_payos_da_thanh_toan', (int) $don->user_id, (int) $don->post_id, $order_code );

	return true;
}

/**
 * Lưu thông tin PayOS trả về cho một đơn.
 *
 * @param int    $order_code Mã đơn.
 * @param string $link_id    payment_link_id.
 * @param string $url        checkoutUrl.
 */
function nntm_payos_luu_lien_ket( int $order_code, string $link_id, string $url, string $qr = '' ): void {
	global $wpdb;

	/*
	 * Cất luôn chuỗi VietQR để lần mở khung sau vẽ lại được mã QR mà không phải
	 * gọi PayOS thêm lần nữa.
	 */
	$wpdb->update(
		nntm_payos_bang(),
		array(
			'payment_link_id' => $link_id,
			'checkout_url'    => $url,
			'qr_code'         => $qr,
		),
		array( 'order_code' => $order_code ),
		array( '%s', '%s', '%s' ),
		array( '%d' )
	);
}

/**
 * Bảng đơn gần đây cho màn quản trị.
 */
function nntm_payos_bang_don_gan_day(): void {
	global $wpdb;

	$bang = nntm_payos_bang();

	if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $bang ) ) !== $bang ) {
		echo '<h2>' . esc_html__( 'Đơn hàng', 'nntm' ) . '</h2>';
		echo '<p>' . esc_html__( 'Chưa có bảng đơn hàng. Tắt rồi bật lại plugin NNTM PayOS.', 'nntm' ) . '</p>';
		return;
	}

	$rows = $wpdb->get_results( "SELECT * FROM {$bang} ORDER BY id DESC LIMIT 30" );

	echo '<h2>' . esc_html__( 'Đơn gần đây', 'nntm' ) . '</h2>';

	if ( ! $rows ) {
		echo '<p>' . esc_html__( 'Chưa có đơn nào.', 'nntm' ) . '</p>';
		return;
	}

	echo '<table class="widefat striped"><thead><tr>'
		. '<th>' . esc_html__( 'Mã đơn', 'nntm' ) . '</th>'
		. '<th>' . esc_html__( 'Người mua', 'nntm' ) . '</th>'
		. '<th>' . esc_html__( 'Ấn phẩm', 'nntm' ) . '</th>'
		. '<th>' . esc_html__( 'Số tiền', 'nntm' ) . '</th>'
		. '<th>' . esc_html__( 'Trạng thái', 'nntm' ) . '</th>'
		. '<th>' . esc_html__( 'Tạo lúc', 'nntm' ) . '</th>'
		. '</tr></thead><tbody>';

	foreach ( $rows as $r ) {
		$u = get_userdata( (int) $r->user_id );

		printf(
			'<tr><td><code>%d</code></td><td>%s</td><td>%s</td><td>%s</td><td>%s</td><td>%s</td></tr>',
			(int) $r->order_code,
			esc_html( $u ? $u->user_login : '#' . (int) $r->user_id ),
			esc_html( get_the_title( (int) $r->post_id ) ),
			esc_html( nntm_payos_dinh_dang_tien( (int) $r->amount ) ),
			'paid' === $r->status
				? '<strong style="color:#046b02;">' . esc_html__( 'đã trả', 'nntm' ) . '</strong>'
				: esc_html( $r->status ),
			esc_html( (string) $r->created_at )
		);
	}

	echo '</tbody></table>';
}
