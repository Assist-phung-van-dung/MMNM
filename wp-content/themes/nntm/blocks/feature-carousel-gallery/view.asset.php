<?php
/*
 * Số phiên bản cho view.js của block này.
 *
 * VÌ SAO cần: WordPress tự tìm file "<tên script>.asset.php" nằm cạnh script.
 * Không có file này thì nó đăng ký script với version = false, và khi in ra
 * trang nó đắp số phiên bản của lõi WordPress vào (…/view.js?ver=7.0.3). Số đó
 * KHÔNG đổi khi mình sửa view.js, nên sau khi đẩy code lên máy chủ thật thì
 * trình duyệt (và các trình gộp/nén JS) vẫn giữ nguyên bản cũ trong bộ nhớ đệm.
 *
 * Riêng style.css thì WordPress đã tự lấy filemtime, nên CSS mới về mà JS cũ ở
 * lại — lệch nhau. Với khối này, JS cũ không gắn class chế độ xem, mà CSS mới
 * lại ẩn cả hai khung theo class đó, nên bấm "Xem Chi Tiết" chỉ thấy một màn
 * hình đen trơn.
 *
 * filemtime đổi mỗi lần sửa file nên đường dẫn đổi theo, bộ nhớ đệm tự hết hạn.
 *
 * view.js là JavaScript thuần, không phụ thuộc gói nào của WordPress.
 */

defined( 'ABSPATH' ) || exit;

return array(
	'dependencies' => array(),
	'version'      => (string) filemtime( __DIR__ . '/view.js' ),
);
