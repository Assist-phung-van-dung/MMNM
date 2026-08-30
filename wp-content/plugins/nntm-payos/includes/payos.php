<?php
/**
 * Nói chuyện với PayOS: tạo liên kết thanh toán và kiểm chữ ký webhook.
 *
 * NGUYÊN TẮC:
 *   1. Số tiền LẤY TỪ MÁY CHỦ, không nhận từ trình duyệt. Nhận từ trình duyệt
 *      là khách sửa một dòng trong công cụ nhà phát triển rồi mua sách 300k với
 *      giá 1k.
 *   2. Webhook mới là nguồn xác nhận. Trình duyệt quay về returnUrl chỉ để điều
 *      hướng — ai cũng gõ được URL đó kèm ?status=PAID.
 *   3. Chữ ký webhook kiểm bằng hằng số thời gian (hash_equals), không dùng
 *      dấu === trên chuỗi.
 *   4. Khoá không bao giờ ra khỏi máy chủ.
 *
 * @package NNTM_PayOS
 */

defined( 'ABSPATH' ) || exit;

const NNTM_PAYOS_API = 'https://api-merchant.payos.vn/v2/payment-requests';

/**
 * Ký dữ liệu tạo đơn theo cách PayOS quy định.
 *
 * PayOS ký trên đúng năm trường, ghép theo thứ tự cố định
 * amount, cancelUrl, description, orderCode, returnUrl — KHÔNG phải toàn bộ
 * payload, và thứ tự không được đổi.
 *
 * @param array $du_lieu Dữ liệu đơn.
 */
function nntm_payos_ky_tao_don( array $du_lieu ): string {
	$chuoi = sprintf(
		'amount=%s&cancelUrl=%s&description=%s&orderCode=%s&returnUrl=%s',
		$du_lieu['amount'],
		$du_lieu['cancelUrl'],
		$du_lieu['description'],
		$du_lieu['orderCode'],
		$du_lieu['returnUrl']
	);

	return hash_hmac( 'sha256', $chuoi, nntm_payos_khoa()['checksum_key'] );
}

/**
 * Ký dữ liệu webhook: mọi trường của data, sắp xếp theo TÊN KHOÁ tăng dần.
 *
 * @param array $data Phần data của webhook.
 */
function nntm_payos_ky_webhook( array $data ): string {
	ksort( $data );

	$doan = array();

	foreach ( $data as $k => $v ) {
		if ( is_array( $v ) || is_object( $v ) ) {
			$v = wp_json_encode( $v );
		} elseif ( is_bool( $v ) ) {
			$v = $v ? 'true' : 'false';
		} elseif ( null === $v ) {
			$v = '';
		}

		$doan[] = $k . '=' . $v;
	}

	return hash_hmac( 'sha256', implode( '&', $doan ), nntm_payos_khoa()['checksum_key'] );
}

/**
 * Kiểm chữ ký webhook.
 *
 * @param array  $data     Phần data.
 * @param string $chu_ky   Chữ ký PayOS gửi kèm.
 */
function nntm_payos_chu_ky_dung( array $data, string $chu_ky ): bool {
	if ( '' === $chu_ky || '' === nntm_payos_khoa()['checksum_key'] ) {
		return false;
	}

	// hash_equals: so sánh hằng thời gian, không để lộ thông tin qua thời gian chạy.
	return hash_equals( nntm_payos_ky_webhook( $data ), $chu_ky );
}

/**
 * Gọi PayOS tạo liên kết thanh toán.
 *
 * @param int    $order_code Mã đơn.
 * @param int    $so_tien    Số tiền (đồng), lấy từ máy chủ.
 * @param string $mo_ta      Mô tả ngắn, PayOS giới hạn 25 ký tự.
 * @param string $return_url Đường quay về.
 * @param string $cancel_url Đường huỷ.
 * @return array{checkoutUrl:string, qrCode:string, paymentLinkId:string}|WP_Error
 */
function nntm_payos_tao_lien_ket( int $order_code, int $so_tien, string $mo_ta, string $return_url, string $cancel_url ) {
	$khoa = nntm_payos_khoa();

	if ( ! nntm_payos_da_cau_hinh() ) {
		return new WP_Error( 'nntm_payos_thieu_khoa', __( 'Chưa cấu hình khoá PayOS.', 'nntm' ) );
	}

	$than = array(
		'orderCode'   => $order_code,
		'amount'      => $so_tien,
		'description' => mb_substr( $mo_ta, 0, 25 ),
		'returnUrl'   => $return_url,
		'cancelUrl'   => $cancel_url,
	);

	$than['signature'] = nntm_payos_ky_tao_don( $than );

	$tra_loi = wp_remote_post(
		NNTM_PAYOS_API,
		array(
			'timeout' => 20,
			'headers' => array(
				'Content-Type' => 'application/json',
				'x-client-id'  => $khoa['client_id'],
				'x-api-key'    => $khoa['api_key'],
			),
			'body'    => wp_json_encode( $than ),
		)
	);

	if ( is_wp_error( $tra_loi ) ) {
		return $tra_loi;
	}

	$json = json_decode( (string) wp_remote_retrieve_body( $tra_loi ), true );

	if ( ! is_array( $json ) || '00' !== ( $json['code'] ?? '' ) ) {
		/*
		 * Chỉ ghi mã lỗi và mô tả của PayOS vào nhật ký, KHÔNG ghi cả phần thân
		 * yêu cầu — trong đó có chữ ký, và nhật ký thì hay bị chép đi nơi khác.
		 */
		return new WP_Error(
			'nntm_payos_loi',
			sprintf(
				/* translators: %s: mô tả lỗi PayOS trả về. */
				__( 'PayOS từ chối tạo đơn: %s', 'nntm' ),
				is_array( $json ) ? (string) ( $json['desc'] ?? 'không rõ' ) : 'không đọc được phản hồi'
			)
		);
	}

	$d = $json['data'] ?? array();

	return array(
		'checkoutUrl'   => (string) ( $d['checkoutUrl'] ?? '' ),
		'qrCode'        => (string) ( $d['qrCode'] ?? '' ),
		'paymentLinkId' => (string) ( $d['paymentLinkId'] ?? '' ),
	);
}
