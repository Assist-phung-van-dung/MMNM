<?php
/**
 * Plugin Name: NNTM PayOS
 * Description: Bán ấn phẩm PDF: đặt giá, chọn cuốn phải mua, khung thanh toán PayOS, mở khoá sau khi trả tiền.
 * Version:     1.0.0
 * Author:      Nẵng Nhân Tịch Mặc
 * Text Domain: nntm
 *
 * VÌ SAO LÀ PLUGIN RIÊNG, KHÔNG NHÉT VÀO THEME HAY nntm-library:
 *   - Đơn hàng là dữ liệu tài chính. Đổi theme không được mất.
 *   - nntm-library lo "ai được đọc"; plugin này lo "ai đã trả tiền". Hai việc
 *     khác nhau, nối nhau qua đúng một bộ lọc `nntm_an_pham_da_thanh_toan`.
 *     Bỏ plugin này đi thì sách khoá vẫn khoá, không có cửa nào hở ra.
 *
 * KHOÁ PAYOS KHÔNG NẰM TRONG CƠ SỞ DỮ LIỆU. Xem includes/cai-dat.php.
 *
 * @package NNTM_PayOS
 */

defined( 'ABSPATH' ) || exit;

define( 'NNTM_PAYOS_VER', '1.1.0' );
define( 'NNTM_PAYOS_PATH', plugin_dir_path( __FILE__ ) );
define( 'NNTM_PAYOS_URL', plugin_dir_url( __FILE__ ) );

require_once NNTM_PAYOS_PATH . 'includes/cai-dat.php';
require_once NNTM_PAYOS_PATH . 'includes/gia.php';
require_once NNTM_PAYOS_PATH . 'includes/don-hang.php';
require_once NNTM_PAYOS_PATH . 'includes/qr.php';
require_once NNTM_PAYOS_PATH . 'includes/payos.php';
require_once NNTM_PAYOS_PATH . 'includes/rest.php';
require_once NNTM_PAYOS_PATH . 'includes/man-don-hang.php';
require_once NNTM_PAYOS_PATH . 'includes/khung-thanh-toan.php';

/**
 * Bật plugin: dựng bảng đơn hàng.
 */
function nntm_payos_kich_hoat(): void {
	nntm_payos_dung_bang();
}
register_activation_hook( __FILE__, 'nntm_payos_kich_hoat' );

/**
 * Bảng đơn hàng có thể thiếu khi cập nhật mã bằng cách chép tệp (không kích
 * hoạt lại plugin) — kiểm mỗi lần vào admin cho chắc, tốn một truy vấn.
 */
function nntm_payos_kiem_bang(): void {
	if ( get_option( 'nntm_payos_db_ver' ) !== NNTM_PAYOS_VER ) {
		nntm_payos_dung_bang();
	}
}
add_action( 'admin_init', 'nntm_payos_kiem_bang' );
