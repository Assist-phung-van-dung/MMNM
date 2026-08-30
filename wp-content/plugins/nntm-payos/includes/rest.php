<?php
/**
 * Ba đường REST: tạo đơn, tra trạng thái, nhận webhook.
 *
 * @package NNTM_PayOS
 */

defined( 'ABSPATH' ) || exit;

const NNTM_PAYOS_NS = 'nntm-payos/v1';

/**
 * Đăng ký đường REST.
 */
function nntm_payos_dang_ky_rest(): void {
	register_rest_route(
		NNTM_PAYOS_NS,
		'/tao-don',
		array(
			'methods'             => 'POST',
			'callback'            => 'nntm_payos_rest_tao_don',
			// Bắt buộc đăng nhập: quyền đọc gắn với tài khoản, mua hộ vô danh thì
			// không biết trả quyền cho ai.
			'permission_callback' => static fn(): bool => is_user_logged_in(),
			'args'                => array(
				'pub' => array(
					'required'          => true,
					'sanitize_callback' => 'absint',
				),
			),
		)
	);

	register_rest_route(
		NNTM_PAYOS_NS,
		'/trang-thai',
		array(
			'methods'             => 'GET',
			'callback'            => 'nntm_payos_rest_trang_thai',
			'permission_callback' => static fn(): bool => is_user_logged_in(),
			'args'                => array(
				'ma' => array(
					'required'          => true,
					'sanitize_callback' => 'absint',
				),
			),
		)
	);

	/*
	 * Webhook mở cho mọi người gọi được — PayOS không đăng nhập vào site. Chốt
	 * chặn là CHỮ KÝ, kiểm trong callback.
	 */
	register_rest_route(
		NNTM_PAYOS_NS,
		'/webhook',
		array(
			'methods'             => 'POST',
			'callback'            => 'nntm_payos_rest_webhook',
			'permission_callback' => '__return_true',
		)
	);
}
add_action( 'rest_api_init', 'nntm_payos_dang_ky_rest' );

/**
 * Tạo đơn và trả về thứ cần để vẽ khung thanh toán.
 *
 * @param WP_REST_Request $req Yêu cầu.
 */
function nntm_payos_rest_tao_don( WP_REST_Request $req ) {
	$post_id = absint( $req->get_param( 'pub' ) );
	$post    = get_post( $post_id );
	$user_id = get_current_user_id();

	if ( ! $post || 'nntm_publication' !== $post->post_type ) {
		return new WP_Error( 'nntm_payos_khong_thay', __( 'Không tìm thấy ấn phẩm.', 'nntm' ), array( 'status' => 404 ) );
	}

	// Đã mua rồi thì đừng bán lần nữa.
	if ( function_exists( 'nntm_an_pham_can_access' ) && nntm_an_pham_can_access( $post ) ) {
		return rest_ensure_response(
			array(
				'daMua' => true,
				'url'   => function_exists( 'nntm_doc_url' ) ? nntm_doc_url( $post ) : get_permalink( $post ),
			)
		);
	}

	if ( ! nntm_payos_dang_ban( $post ) ) {
		return new WP_Error( 'nntm_payos_khong_ban', __( 'Ấn phẩm này hiện không bán.', 'nntm' ), array( 'status' => 409 ) );
	}

	// SỐ TIỀN LẤY TỪ MÁY CHỦ. Không đọc gì từ yêu cầu gửi lên.
	$so_tien = nntm_payos_gia( $post );

	$don = nntm_payos_don_dang_cho( $user_id, $post_id, $so_tien );
	$ma  = $don ? (int) $don->order_code : 0;

	if ( ! $ma ) {
		$moi = nntm_payos_tao_don( $user_id, $post_id, $so_tien );

		if ( ! $moi ) {
			return new WP_Error( 'nntm_payos_khong_tao_duoc', __( 'Không tạo được đơn. Thử lại giúp tôi.', 'nntm' ), array( 'status' => 500 ) );
		}

		$ma  = $moi['order_code'];
		$don = nntm_payos_don_theo_ma( $ma );
	}

	$chung = array(
		'ma'      => $ma,
		'soTien'  => $so_tien,
		'tien'    => nntm_payos_dinh_dang_tien( $so_tien ),
		'tenSach' => get_the_title( $post ),
	);

	/*
	 * Chế độ thử: không gọi PayOS, trả về một đường dẫn nội bộ để bấm vào là
	 * đánh dấu đã trả. Chỉ chạy khi wp-config.php khai NNTM_PAYOS_THU.
	 */
	if ( nntm_payos_che_do_thu() ) {
		return rest_ensure_response(
			array_merge(
				$chung,
				array(
					'cheDoThu'    => true,
					/*
					 * Chế độ thử không có chuỗi VietQR thật, nên vẽ tạm mã QR chứa
					 * chính đường dẫn "đã trả tiền" — quét bằng điện thoại là mở
					 * ngay, tiện thử cả luồng.
					 */
					'qrSvg'       => nntm_payos_qr_svg(
						wp_nonce_url(
							add_query_arg( array( 'nntm_payos_thu' => $ma ), home_url( '/' ) ),
							'nntm_payos_thu_' . $ma
						)
					),
					'checkoutUrl' => wp_nonce_url(
						add_query_arg(
							array(
								'nntm_payos_thu' => $ma,
							),
							home_url( '/' )
						),
						'nntm_payos_thu_' . $ma
					),
				)
			)
		);
	}

	/*
	 * Đơn cũ còn hạn: dựng lại mã QR từ chuỗi VietQR đã cất, không gọi PayOS
	 * thêm lần nữa.
	 */
	if ( $don && '' !== (string) $don->checkout_url ) {
		return rest_ensure_response(
			array_merge(
				$chung,
				array(
					'checkoutUrl' => (string) $don->checkout_url,
					'qrSvg'       => nntm_payos_qr_svg( (string) $don->qr_code ),
				)
			)
		);
	}

	$kq = nntm_payos_tao_lien_ket(
		$ma,
		$so_tien,
		'NNTM ' . $ma,
		add_query_arg( 'nntm_payos_ma', $ma, (string) get_permalink( $post ) ),
		add_query_arg( 'nntm_payos_huy', $ma, (string) get_permalink( $post ) )
	);

	if ( is_wp_error( $kq ) ) {
		return new WP_Error( 'nntm_payos_loi', $kq->get_error_message(), array( 'status' => 502 ) );
	}

	nntm_payos_luu_lien_ket( $ma, $kq['paymentLinkId'], $kq['checkoutUrl'], $kq['qrCode'] );

	/*
	 * Trả về ẢNH QR đã vẽ sẵn, không trả chuỗi VietQR thô: trình duyệt không
	 * cần biết chuỗi đó, và cũng chẳng có bộ vẽ QR nào ở phía trình duyệt.
	 */
	return rest_ensure_response(
		array_merge(
			$chung,
			array(
				'checkoutUrl' => $kq['checkoutUrl'],
				'qrSvg'       => nntm_payos_qr_svg( $kq['qrCode'] ),
			)
		)
	);
}

/**
 * Tra trạng thái một đơn — khung thanh toán hỏi lại sau khi khách trả tiền.
 *
 * @param WP_REST_Request $req Yêu cầu.
 */
function nntm_payos_rest_trang_thai( WP_REST_Request $req ) {
	$ma  = absint( $req->get_param( 'ma' ) );
	$don = nntm_payos_don_theo_ma( $ma );

	if ( ! $don ) {
		return new WP_Error( 'nntm_payos_khong_thay_don', __( 'Không tìm thấy đơn.', 'nntm' ), array( 'status' => 404 ) );
	}

	// Chỉ chủ đơn được hỏi. Thiếu dòng này là ai cũng dò được đơn của người khác.
	if ( (int) $don->user_id !== get_current_user_id() ) {
		return new WP_Error( 'nntm_payos_khong_phai_don_cua_ban', __( 'Không có quyền.', 'nntm' ), array( 'status' => 403 ) );
	}

	$xong = 'paid' === $don->status;

	return rest_ensure_response(
		array(
			'daTra' => $xong,
			'url'   => $xong && function_exists( 'nntm_doc_url' ) ? nntm_doc_url( (int) $don->post_id ) : '',
		)
	);
}

/**
 * Nhận webhook PayOS.
 *
 * @param WP_REST_Request $req Yêu cầu.
 */
function nntm_payos_rest_webhook( WP_REST_Request $req ) {
	$than = $req->get_json_params();

	if ( ! is_array( $than ) ) {
		return new WP_Error( 'nntm_payos_than_hong', 'invalid body', array( 'status' => 400 ) );
	}

	$data   = isset( $than['data'] ) && is_array( $than['data'] ) ? $than['data'] : array();
	$chu_ky = isset( $than['signature'] ) ? (string) $than['signature'] : '';

	/*
	 * KIỂM CHỮ KÝ TRƯỚC MỌI THỨ. Không có bước này thì bất kỳ ai cũng gửi được
	 * một gói JSON tự chế tới địa chỉ này và mở khoá cả thư viện.
	 */
	if ( ! nntm_payos_chu_ky_dung( $data, $chu_ky ) ) {
		return new WP_Error( 'nntm_payos_chu_ky_sai', 'bad signature', array( 'status' => 401 ) );
	}

	$ma      = isset( $data['orderCode'] ) ? absint( $data['orderCode'] ) : 0;
	$so_tien = isset( $data['amount'] ) ? absint( $data['amount'] ) : 0;
	$ma_kq   = isset( $data['code'] ) ? (string) $data['code'] : '';

	if ( $ma <= 0 ) {
		return rest_ensure_response( array( 'success' => true ) );
	}

	if ( '00' === $ma_kq ) {
		nntm_payos_danh_dau_da_tra( $ma, $so_tien );
	}

	/*
	 * Luôn trả 200 kể cả khi không tìm thấy đơn: PayOS coi mã lỗi là "chưa nhận
	 * được" và sẽ gọi lại mãi. Chữ ký đã đúng nghĩa là gói tin hợp lệ.
	 */
	return rest_ensure_response( array( 'success' => true ) );
}

/**
 * Đường tắt của CHẾ ĐỘ THỬ: bấm vào là đánh dấu đơn đã trả.
 *
 * Có ba lớp khoá để không thành lỗ hổng: phải khai NNTM_PAYOS_THU trong
 * wp-config.php, phải đăng nhập đúng chủ đơn, và phải có nonce.
 */
function nntm_payos_xu_ly_thu(): void {
	if ( ! nntm_payos_che_do_thu() || ! isset( $_GET['nntm_payos_thu'] ) ) {
		return;
	}

	$ma = absint( wp_unslash( $_GET['nntm_payos_thu'] ) );

	if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'nntm_payos_thu_' . $ma ) ) {
		wp_die( esc_html__( 'Liên kết thử không hợp lệ.', 'nntm' ) );
	}

	$don = nntm_payos_don_theo_ma( $ma );

	if ( ! $don || (int) $don->user_id !== get_current_user_id() ) {
		wp_die( esc_html__( 'Không phải đơn của bạn.', 'nntm' ) );
	}

	nntm_payos_danh_dau_da_tra( $ma, (int) $don->amount );

	$dich = function_exists( 'nntm_doc_url' ) ? nntm_doc_url( (int) $don->post_id ) : get_permalink( (int) $don->post_id );

	wp_safe_redirect( $dich );
	exit;
}
add_action( 'template_redirect', 'nntm_payos_xu_ly_thu', 1 );
