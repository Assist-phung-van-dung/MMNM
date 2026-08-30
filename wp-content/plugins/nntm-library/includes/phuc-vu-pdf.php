<?php
/**
 * Phục vụ tệp PDF qua endpoint có kiểm quyền.
 *
 * Đây là CỬA DUY NHẤT ra tới nội dung PDF sau khi kho riêng đã nằm ngoài tầm
 * web. Mọi yêu cầu đều phải đi qua nntm_an_pham_can_access().
 *
 * VÌ SAO PHẢI ĐỠ ĐƯỢC "Range": pdf.js không tải cả cuốn rồi mới vẽ, nó xin từng
 * khúc byte một (HTTP Range). Máy chủ không đỡ Range thì pdf.js đành tải trọn
 * cuốn — sách vài trăm trang là mỗi lần mở tốn vài chục MB và người đọc ngồi
 * chờ. Đỡ Range thì mở tới đâu tải tới đó.
 *
 * @package NNTM_Library
 */

defined( 'ABSPATH' ) || exit;

const NNTM_LIB_REST_NS = 'nntm-library/v1';

/**
 * Đăng ký endpoint đọc PDF.
 */
function nntm_lib_dang_ky_route_pdf(): void {
	register_rest_route(
		NNTM_LIB_REST_NS,
		'/pdf/(?P<id>\d+)',
		array(
			'methods'             => array( 'GET', 'HEAD' ),
			'callback'            => 'nntm_lib_phuc_vu_pdf',
			// Quyền kiểm trong callback để còn phân biệt được 404 với 403.
			'permission_callback' => '__return_true',
			'args'                => array(
				'id'  => array(
					'required'          => true,
					'sanitize_callback' => 'absint',
				),
				'tai' => array(
					'required'          => false,
					'sanitize_callback' => 'absint',
				),
			),
		)
	);
}
add_action( 'rest_api_init', 'nntm_lib_dang_ky_route_pdf' );

/**
 * Đường dẫn đọc một tệp PDF.
 *
 * @param int  $attachment_id ID tệp đính kèm.
 * @param bool $tai_ve        true thì trình duyệt tải xuống, false thì mở tại chỗ.
 */
function nntm_lib_url_doc_pdf( int $attachment_id, bool $tai_ve = false ): string {
	$url = rest_url( NNTM_LIB_REST_NS . '/pdf/' . $attachment_id );

	return $tai_ve ? add_query_arg( 'tai', '1', $url ) : $url;
}

/**
 * MỌI ấn phẩm đang dùng tệp PDF này.
 *
 * VÌ SAO TRẢ VỀ DANH SÁCH CHỨ KHÔNG PHẢI MỘT: dữ liệu hiện tại có mỗi tệp bị
 * 5–6 ấn phẩm dùng chung. Lấy "một cái" thì mỗi nơi lấy ra một cái khác nhau —
 * đã đo được thật: endpoint đọc tra theo meta ra ấn phẩm #362, endpoint tải về
 * tra theo post_parent ra #354, nên khoá cuốn này mà cuốn kia vẫn tải được.
 *
 * @param int $attachment_id ID tệp đính kèm.
 * @return int[] ID các ấn phẩm.
 */
function nntm_lib_cac_an_pham_cua_pdf( int $attachment_id ): array {
	global $wpdb;

	$ids = $wpdb->get_col(
		$wpdb->prepare(
			"SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_nntm_pdf_file' AND meta_value = %d",
			$attachment_id
		)
	);

	$ra = array();

	foreach ( $ids as $id ) {
		if ( 'nntm_publication' === get_post_type( (int) $id ) ) {
			$ra[] = (int) $id;
		}
	}

	$cha = (int) get_post_field( 'post_parent', $attachment_id );

	if ( $cha > 0 && 'nntm_publication' === get_post_type( $cha ) ) {
		$ra[] = $cha;
	}

	return array_values( array_unique( $ra ) );
}

/**
 * Tệp này có thật sự dùng được không: có tồn tại, đúng PDF, và đọc được từ đĩa.
 *
 * VÌ SAO CẦN: dữ liệu của dự án có bản ghi _nntm_pdf_file trỏ tới một ID KHÔNG
 * PHẢI tệp đính kèm — sáu ấn phẩm đang trỏ vào cùng một ID hỏng như vậy. Không
 * kiểm ở đây thì nntm_an_pham_pdf_url() vẫn dựng ra một đường dẫn trông hợp lệ,
 * trình đọc gọi tới thì nhận 404 và báo "Không mở được tệp" — người dùng tưởng
 * hỏng trình đọc, trong khi thật ra là bản ghi trỏ sai.
 *
 * Trả về chuỗi rỗng cho nơi gọi thì trình đọc hiện trạng thái "sách chưa có
 * tệp" tử tế hơn nhiều.
 *
 * @param int $attachment_id ID tệp đính kèm.
 */
function nntm_lib_tep_dung_duoc( int $attachment_id ): bool {
	if ( $attachment_id <= 0 || 'attachment' !== get_post_type( $attachment_id ) ) {
		return false;
	}

	if ( 'application/pdf' !== get_post_mime_type( $attachment_id ) ) {
		return false;
	}

	$duong = get_attached_file( $attachment_id );

	return (bool) ( $duong && is_readable( $duong ) );
}

/**
 * Tệp này có phải tệp xem thử của một ấn phẩm nào không.
 *
 * @param int $attachment_id ID tệp đính kèm.
 */
function nntm_lib_la_tep_xem_thu( int $attachment_id ): bool {
	global $wpdb;

	if ( $attachment_id <= 0 ) {
		return false;
	}

	return (bool) $wpdb->get_var(
		$wpdb->prepare(
			"SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_nntm_pdf_xem_thu' AND meta_value = %d LIMIT 1",
			$attachment_id
		)
	);
}

/**
 * Ấn phẩm đại diện của một tệp — chỉ dùng để hiển thị, đừng dùng để xét quyền.
 *
 * @param int $attachment_id ID tệp đính kèm.
 */
function nntm_lib_an_pham_cua_pdf( int $attachment_id ): int {
	$ds = nntm_lib_cac_an_pham_cua_pdf( $attachment_id );

	return $ds ? $ds[0] : 0;
}

/**
 * Người đang xem có được lấy nội dung tệp này không.
 *
 * Quy tắc: đọc được DÙ CHỈ MỘT ấn phẩm dùng tệp này là được lấy tệp.
 *
 * Nghe có vẻ lỏng, nhưng siết hơn cũng vô nghĩa: các ấn phẩm đó dùng CHUNG một
 * tệp, cùng một dãy byte. Người đọc hợp lệ cuốn miễn phí đằng nào cũng lấy
 * được đúng dãy byte ấy. Chặn ở đây chỉ gây phiền chứ không bịt được gì.
 *
 * Chỗ thật sự phải sửa là DỮ LIỆU: mỗi ấn phẩm cần tệp riêng. Chừng nào còn
 * dùng chung thì không thể bán riêng từng cuốn.
 *
 * @param int $attachment_id ID tệp đính kèm.
 */
function nntm_lib_duoc_doc_tep( int $attachment_id ): bool {
	/*
	 * Tệp XEM THỬ vốn để mời chào người chưa mua, nên ai cũng lấy được. Phải xét
	 * trước, vì nó gắn với một cuốn đang khoá — hỏi quyền cuốn đó thì luôn ra
	 * "không", và người chưa mua sẽ chẳng thấy gì để mà quyết định mua.
	 */
	if ( nntm_lib_la_tep_xem_thu( $attachment_id ) ) {
		return true;
	}

	$ds = nntm_lib_cac_an_pham_cua_pdf( $attachment_id );

	// Tệp mồ côi, không gắn ấn phẩm nào: để filter chung quyết định.
	if ( ! $ds ) {
		return (bool) apply_filters( 'nntm_an_pham_can_access', true, null, get_current_user_id() );
	}

	foreach ( $ds as $id ) {
		if ( nntm_an_pham_can_access( $id ) ) {
			return true;
		}
	}

	return false;
}

/**
 * Đẩy tệp PDF về trình duyệt sau khi đã kiểm quyền.
 *
 * @param WP_REST_Request $request Yêu cầu.
 * @return WP_Error|void Thoát thẳng khi thành công.
 */
function nntm_lib_phuc_vu_pdf( WP_REST_Request $request ) {
	$attachment_id = absint( $request->get_param( 'id' ) );

	/*
	 * Chỉ nhận số nguyên rồi tra CSDL — không có chỗ nào nhận đường dẫn do
	 * người dùng đặt, nên không có cửa cho tấn công đi ngược thư mục.
	 */
	if ( 'attachment' !== get_post_type( $attachment_id ) ) {
		return new WP_Error( 'nntm_lib_khong_thay', __( 'Không tìm thấy tài liệu.', 'nntm' ), array( 'status' => 404 ) );
	}

	if ( 'application/pdf' !== get_post_mime_type( $attachment_id ) ) {
		return new WP_Error( 'nntm_lib_khong_pdf', __( 'Tệp này không phải PDF.', 'nntm' ), array( 'status' => 415 ) );
	}

	if ( ! nntm_lib_duoc_doc_tep( $attachment_id ) ) {
		return new WP_Error(
			'nntm_lib_khong_co_quyen',
			__( 'Bạn chưa được phép đọc tài liệu này.', 'nntm' ),
			array( 'status' => 403 )
		);
	}

	$path = get_attached_file( $attachment_id );

	if ( ! $path || ! is_readable( $path ) ) {
		return new WP_Error( 'nntm_lib_thieu_tep', __( 'Không đọc được tệp.', 'nntm' ), array( 'status' => 404 ) );
	}

	nntm_lib_day_tep( $path, (bool) $request->get_param( 'tai' ), 'HEAD' === $request->get_method() );
}

/**
 * Đọc dải byte mà trình duyệt xin trong tiêu đề Range.
 *
 * Chỉ đỡ MỘT dải — pdf.js chưa bao giờ xin nhiều dải một lúc, và đỡ nhiều dải
 * phải trả về multipart/byteranges, phức tạp hơn nhiều mà không ai dùng.
 *
 * @param int $kich_thuoc Kích thước tệp.
 * @return array{0:int,1:int}|null [đầu, cuối] hoặc null nếu không có Range hợp lệ.
 */
function nntm_lib_doc_range( int $kich_thuoc ): ?array {
	$tho = isset( $_SERVER['HTTP_RANGE'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_RANGE'] ) ) : '';

	if ( '' === $tho || ! preg_match( '/^bytes=(\d*)-(\d*)$/', $tho, $m ) ) {
		return null;
	}

	$co_dau  = '' !== $m[1];
	$co_cuoi = '' !== $m[2];

	if ( ! $co_dau && ! $co_cuoi ) {
		return null;
	}

	if ( ! $co_dau ) {
		// "bytes=-500" nghĩa là 500 byte CUỐI tệp, không phải từ 0 tới 500.
		$dai  = min( (int) $m[2], $kich_thuoc );
		$dau  = $kich_thuoc - $dai;
		$cuoi = $kich_thuoc - 1;
	} else {
		$dau  = (int) $m[1];
		$cuoi = $co_cuoi ? (int) $m[2] : $kich_thuoc - 1;
	}

	$cuoi = min( $cuoi, $kich_thuoc - 1 );

	if ( $dau < 0 || $dau > $cuoi ) {
		return null;
	}

	return array( $dau, $cuoi );
}

/**
 * Đẩy tệp ra output, có đỡ Range.
 *
 * @param string $path    Đường dẫn tệp.
 * @param bool   $tai_ve  Tải xuống hay mở tại chỗ.
 * @param bool   $chi_dau Chỉ trả tiêu đề (HEAD).
 */
function nntm_lib_day_tep( string $path, bool $tai_ve, bool $chi_dau = false ): void {
	$kich_thuoc = (int) filesize( $path );
	$ten        = basename( $path );

	/*
	 * Dọn sạch mọi thứ đã trót in ra. Thiếu bước này thì tệp tải về dính vài
	 * byte rác ở đầu và trình đọc PDF báo tệp hỏng.
	 */
	while ( ob_get_level() > 0 ) {
		ob_end_clean();
	}

	$dai = nntm_lib_doc_range( $kich_thuoc );

	nocache_headers();
	// Nội dung có thể là hàng đã bán — không cho proxy dùng chung giữ lại.
	header( 'Cache-Control: private, no-store, max-age=0' );
	header( 'X-Robots-Tag: noindex, nofollow', true );
	header( 'X-Content-Type-Options: nosniff' );
	header( 'Accept-Ranges: bytes' );
	header( 'Content-Type: application/pdf' );
	header(
		sprintf(
			'Content-Disposition: %s; filename="%s"',
			$tai_ve ? 'attachment' : 'inline',
			rawurlencode( $ten )
		)
	);

	if ( null === $dai ) {
		header( 'Content-Length: ' . $kich_thuoc );

		if ( $chi_dau ) {
			exit;
		}

		readfile( $path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_readfile -- tep nhi phan, WP_Filesystem khong day thang ra output duoc.
		exit;
	}

	list( $dau, $cuoi ) = $dai;
	$do_dai             = $cuoi - $dau + 1;

	status_header( 206 );
	header( sprintf( 'Content-Range: bytes %d-%d/%d', $dau, $cuoi, $kich_thuoc ) );
	header( 'Content-Length: ' . $do_dai );

	if ( $chi_dau ) {
		exit;
	}

	$fh = fopen( $path, 'rb' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen

	if ( ! $fh ) {
		exit;
	}

	fseek( $fh, $dau );

	$con = $do_dai;

	while ( $con > 0 && ! feof( $fh ) ) {
		$khuc = fread( $fh, (int) min( 262144, $con ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fread

		if ( false === $khuc || '' === $khuc ) {
			break;
		}

		echo $khuc; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- du lieu nhi phan cua tep PDF.
		flush();

		$con -= strlen( $khuc );
	}

	fclose( $fh ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose
	exit;
}
