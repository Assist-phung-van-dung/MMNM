<?php
/**
 * Tạo và nâng cấp các bảng dữ liệu riêng của NNTM (không nhét vào wp_postmeta).
 * Xem bảng danh sách tại docs/04-kien-truc.md mục 3.
 *
 * @package NNTM_Core
 */

namespace NNTM\Core;

// Chống truy cập trực tiếp file.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Schema
 */
class Schema {

	/**
	 * Tên option lưu version schema đã cài, dùng để so sánh và quyết định có cần nâng cấp không.
	 */
	const OPTION_VERSION = 'nntm_schema_version';

	/**
	 * Danh sách hậu tố tên bảng (không kèm tiền tố $wpdb->prefix . 'nntm_').
	 * Dùng chung cho create_tables() và nơi khác (vd. uninstall.php) cần liệt kê đủ bảng.
	 *
	 * @return string[]
	 */
	public static function table_names(): array {
		return array(
			'reading_progress',
			'notes',
			'favorites',
			'retreat_signup',
			'kpi_log',
		);
	}

	/**
	 * Trả về tên bảng đầy đủ kèm tiền tố, ví dụ Schema::table( 'favorites' ) => wp_nntm_favorites.
	 * Nơi khác trong plugin/theme dùng hàm này thay vì tự ghép chuỗi tiền tố.
	 *
	 * @param string $name Tên ngắn của bảng, không kèm tiền tố.
	 */
	public static function table( string $name ): string {
		global $wpdb;
		return $wpdb->prefix . 'nntm_' . $name;
	}

	/**
	 * Tạo (hoặc cập nhật cấu trúc) toàn bộ bảng bằng dbDelta.
	 * dbDelta tự so sánh cấu trúc hiện có với câu SQL nên gọi lại nhiều lần vẫn an toàn,
	 * không xóa dữ liệu đã có.
	 */
	public static function create_tables(): void {
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset_collate = $wpdb->get_charset_collate();

		foreach ( self::schema_sql( $charset_collate ) as $sql ) {
			dbDelta( $sql );
		}

		update_option( self::OPTION_VERSION, NNTM_CORE_SCHEMA_VERSION );
	}

	/**
	 * Chạy trên plugins_loaded: so sánh version đã lưu với hằng NNTM_CORE_SCHEMA_VERSION,
	 * khác nhau (kể cả lần đầu, chưa có option) thì tạo/nâng cấp bảng.
	 */
	public static function maybe_upgrade(): void {
		$installed = get_option( self::OPTION_VERSION, '' );

		if ( $installed !== NNTM_CORE_SCHEMA_VERSION ) {
			self::create_tables();
		}
	}

	/**
	 * Câu lệnh SQL tạo từng bảng — định dạng đúng chuẩn dbDelta:
	 * mỗi cột một dòng, hai khoảng trắng trước "PRIMARY KEY", KEY thay vì INDEX.
	 *
	 * @param string $charset_collate Chuỗi charset/collate lấy từ $wpdb->get_charset_collate().
	 * @return string[]
	 */
	private static function schema_sql( string $charset_collate ): array {
		$sql = array();

		// nntm_reading_progress — vị trí đang đọc PDF / đang nghe audio.
		$table = self::table( 'reading_progress' );
		$sql[] = "CREATE TABLE {$table} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			user_id BIGINT UNSIGNED NOT NULL,
			object_id BIGINT UNSIGNED NOT NULL,
			object_type VARCHAR(20) NOT NULL,
			position VARCHAR(50) NOT NULL,
			updated_at DATETIME NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY user_object (user_id, object_id, object_type),
			KEY user_id (user_id)
		) {$charset_collate};";

		// nntm_notes — ghi chú cá nhân theo trang PDF.
		$table = self::table( 'notes' );
		$sql[] = "CREATE TABLE {$table} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			user_id BIGINT UNSIGNED NOT NULL,
			object_id BIGINT UNSIGNED NOT NULL,
			page_number INT UNSIGNED NOT NULL,
			content TEXT NOT NULL,
			created_at DATETIME NOT NULL,
			updated_at DATETIME NOT NULL,
			PRIMARY KEY  (id),
			KEY user_object (user_id, object_id)
		) {$charset_collate};";

		// nntm_favorites — yêu thích.
		$table = self::table( 'favorites' );
		$sql[] = "CREATE TABLE {$table} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			user_id BIGINT UNSIGNED NOT NULL,
			object_id BIGINT UNSIGNED NOT NULL,
			object_type VARCHAR(20) NOT NULL,
			created_at DATETIME NOT NULL,
			PRIMARY KEY  (id),
			UNIQUE KEY user_object (user_id, object_id),
			KEY user_id (user_id)
		) {$charset_collate};";

		// nntm_retreat_signup — đăng ký khóa tu. user_id để 0 khi cho phép khách chưa đăng nhập đăng ký.
		$table = self::table( 'retreat_signup' );
		$sql[] = "CREATE TABLE {$table} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			retreat_id BIGINT UNSIGNED NOT NULL,
			user_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
			full_name VARCHAR(191) NOT NULL,
			phone VARCHAR(30) NOT NULL,
			email VARCHAR(191) NOT NULL,
			note TEXT NULL,
			status VARCHAR(20) NOT NULL DEFAULT 'pending',
			created_at DATETIME NOT NULL,
			PRIMARY KEY  (id),
			KEY retreat_id (retreat_id),
			KEY user_id (user_id)
		) {$charset_collate};";

		// nntm_kpi_log — khai báo công phu Cộng Tu "chuỗi trì" (docs/07-ban-giao.md).
		// program_id thêm ở schema 1.1.0 để tách số liệu theo từng chương trình
		// (nntm_program) — dự án sẽ có NHIỀU chương trình theo thời gian.
		$table = self::table( 'kpi_log' );
		$sql[] = "CREATE TABLE {$table} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			program_id BIGINT UNSIGNED NOT NULL DEFAULT 0,
			user_id BIGINT UNSIGNED NOT NULL,
			log_date DATE NOT NULL,
			metric VARCHAR(50) NOT NULL,
			value INT NOT NULL DEFAULT 0,
			created_at DATETIME NOT NULL,
			PRIMARY KEY  (id),
			KEY user_date (user_id, log_date),
			KEY ct_nguoi_metric (program_id, user_id, metric),
			KEY ct_metric (program_id, metric)
		) {$charset_collate};";

		return $sql;
	}
}

/*
 * Tự đăng ký hook nâng cấp version ngay tại đây (thay vì sửa nntm-core.php đã hoàn thiện):
 * priority 5 để chạy trước nntm_core_bootstrap (priority mặc định 10), phòng khi các phần
 * khác lúc plugins_loaded cần bảng đã tồn tại.
 */
add_action( 'plugins_loaded', array( __NAMESPACE__ . '\\Schema', 'maybe_upgrade' ), 5 );
