<?php
/**
 * Từ khoá động — phần hiển thị (phiếu khảo sát câu 33–34).
 *
 * Dữ liệu ở plugin nntm-core (NNTM\Core\Tu_Khoa_Dong). File này chỉ quyết định
 * có nạp hiệu ứng trên trang hiện tại hay không và đưa danh sách xuống trình
 * duyệt. Việc dò chữ làm ở trình duyệt: nội dung trang ghép từ nhiều block
 * động, dò ở PHP phải bóc HTML từng block mà vẫn dễ chèn nhầm vào thuộc tính.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Trang đang xem có bật từ khoá động không.
 */
function nntm_tu_khoa_dong_trang_nay_bat(): bool {
	if ( is_admin() || is_feed() || ! is_singular() || ! class_exists( '\NNTM\Core\Tu_Khoa_Dong' ) ) {
		return false;
	}

	return \NNTM\Core\Tu_Khoa_Dong::dang_bat( (int) get_queried_object_id() );
}

function nntm_tu_khoa_dong_enqueue(): void {
	if ( ! nntm_tu_khoa_dong_trang_nay_bat() ) {
		return;
	}

	$du_lieu = \NNTM\Core\Tu_Khoa_Dong::du_lieu();
	if ( ! $du_lieu ) {
		return;
	}

	$css_path = NNTM_THEME_DIR . '/assets/css/tu-khoa-dong.css';
	wp_enqueue_style(
		'nntm-tu-khoa-dong',
		NNTM_THEME_URI . '/assets/css/tu-khoa-dong.css',
		array( 'nntm-tokens', 'nntm-base' ),
		nntm_asset_version( $css_path )
	);

	$js_path = NNTM_THEME_DIR . '/assets/js/tu-khoa-dong.js';
	wp_enqueue_script(
		'nntm-tu-khoa-dong',
		NNTM_THEME_URI . '/assets/js/tu-khoa-dong.js',
		array(),
		nntm_asset_version( $js_path ),
		array(
			'in_footer' => true,
			'strategy'  => 'defer',
		)
	);

	wp_add_inline_script(
		'nntm-tu-khoa-dong',
		'window.nntmTuKhoaDong = ' . wp_json_encode(
			array(
				'tuKhoa'    => $du_lieu,
				// Mỗi từ khoá chỉ gắn hiệu ứng N lần đầu trên một trang, tránh
				// một bài dài chi chít chữ gạch chân.
				'soLan'     => max( 1, (int) apply_filters( 'nntm_tu_khoa_dong_so_lan', 1 ) ),
				'vungDo'    => (string) apply_filters( 'nntm_tu_khoa_dong_vung_do', '#nntm-noi-dung-chinh' ),
				'xemThem'   => __( 'Xem thêm', 'nntm' ),
			),
			JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP
		) . ';',
		'before'
	);
}
add_action( 'wp_enqueue_scripts', 'nntm_tu_khoa_dong_enqueue' );
