<?php
/**
 * Chạy khi quản trị viên gỡ cài đặt (xóa hẳn) plugin từ màn hình wp-admin/plugins.php.
 * Khác với deactivate: uninstall chỉ chạy khi bấm "Xóa", không chạy khi chỉ "Tắt".
 *
 * @package NNTM_Core
 */

// Chống truy cập trực tiếp file — hằng số này chỉ WordPress định nghĩa lúc gỡ cài đặt thật sự.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

/*
 * Mặc định KHÔNG xóa bất kỳ dữ liệu nào.
 * Lý do: dữ liệu trong các bảng nntm_* (ghi chú, yêu thích, tiến độ đọc, đăng ký khóa tu...)
 * là dữ liệu thật của thành viên. Gỡ nhầm plugin (thử nghiệm, đổi tên, cập nhật lại...) không
 * được phép làm mất dữ liệu này — mất thì không cách nào khôi phục lại được.
 * Chỉ khi quản trị viên CHỦ ĐỘNG khai báo hằng số NNTM_DELETE_DATA_ON_UNINSTALL = true
 * (ví dụ thêm tạm vào wp-config.php ngay trước khi gỡ) thì mới thực sự xóa bên dưới.
 */
if ( ! defined( 'NNTM_DELETE_DATA_ON_UNINSTALL' ) || true !== NNTM_DELETE_DATA_ON_UNINSTALL ) {
	return;
}

global $wpdb;

// uninstall.php chạy độc lập, plugin chính chưa được nạp đầy đủ nên phải tự require lớp cần dùng.
require_once __DIR__ . '/includes/class-schema.php';
require_once __DIR__ . '/includes/class-roles.php';

// Xóa toàn bộ bảng dữ liệu riêng của plugin.
foreach ( \NNTM\Core\Schema::table_names() as $name ) {
	$table = \NNTM\Core\Schema::table( $name );
	// Tên bảng do chính plugin sinh ra (không phải input người dùng) nên không cần/không thể prepare() định danh bảng.
	$wpdb->query( "DROP TABLE IF EXISTS {$table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
}

// Xóa 2 vai trò thành viên do plugin tạo ra.
remove_role( \NNTM\Core\Roles::ROLE_DAI_SI );
remove_role( \NNTM\Core\Roles::ROLE_KIM_CUONG );

// Xóa option lưu version schema.
delete_option( \NNTM\Core\Schema::OPTION_VERSION );
