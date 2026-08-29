<?php
/**
 * Plugin Name: NNTM Library
 * Description: Ấn phẩm PDF — kho riêng, cổng quyền đọc, trang đọc và tiến độ đọc. Nghiệp vụ tách khỏi theme để đổi giao diện không mất dữ liệu.
 * Version:     1.0.0
 * Author:      Nẵng Nhân Tịch Mặc
 * Text Domain: nntm
 *
 * VÌ SAO CÓ PLUGIN NÀY
 *
 * docs/04-kien-truc.md mục 1 (chốt 06/08/2026) quy định "dữ liệu và nghiệp vụ ở
 * plugin, hình ảnh ở theme", và sơ đồ ở đó đã đặt sẵn ô `nntm-library` cho phần
 * "PDF: bảo vệ, watermark, ghi chú, bookmark". Ô đó chưa từng được dựng, nên
 * nghiệp vụ chảy ngược vào theme: theme ghi thẳng vào bảng
 * wp_nntm_reading_progress do nntm-core tạo, và theme cũng giữ luôn cổng quyền
 * đọc. Đổi theme là mất sạch những thứ đó.
 *
 * Plugin này nhận lại đúng phần nghiệp vụ. Theme chỉ còn template, CSS và JS.
 *
 * RANH GIỚI — đọc trước khi thêm code vào đây:
 *   thuộc plugin : ai được đọc, file nằm đâu, phục vụ file, ghi tiến độ, đường dẫn
 *   thuộc theme  : template-doc-sach.php, single-nntm_publication.php, CSS, JS
 *
 * @package NNTM_Library
 */

defined( 'ABSPATH' ) || exit;

define( 'NNTM_LIB_VER', '1.0.0' );
define( 'NNTM_LIB_PATH', plugin_dir_path( __FILE__ ) );
define( 'NNTM_LIB_URL', plugin_dir_url( __FILE__ ) );

require_once NNTM_LIB_PATH . 'includes/kho-rieng.php';
require_once NNTM_LIB_PATH . 'includes/quyen-truy-cap.php';
require_once NNTM_LIB_PATH . 'includes/phuc-vu-pdf.php';
require_once NNTM_LIB_PATH . 'includes/trang-doc.php';
require_once NNTM_LIB_PATH . 'includes/tien-do-doc.php';
require_once NNTM_LIB_PATH . 'includes/di-doi.php';

/**
 * Bật plugin: dựng kho riêng và nạp lại luật đường dẫn.
 *
 * Không tự di dời file ở đây — di dời là việc đụng vào 45 tệp thật, phải do
 * quản trị bấm nút và xem báo cáo, không được lặng lẽ chạy lúc kích hoạt.
 */
function nntm_lib_kich_hoat(): void {
	nntm_lib_dung_kho_rieng();
	nntm_lib_dang_ky_endpoint_doc();
	flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'nntm_lib_kich_hoat' );

/**
 * Tắt plugin: dọn luật đường dẫn.
 *
 * Không xoá file, không xoá bảng — dữ liệu người đọc phải sống sót qua việc
 * bật/tắt plugin.
 */
function nntm_lib_ngung(): void {
	flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'nntm_lib_ngung' );
