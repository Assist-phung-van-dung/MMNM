<?php
/**
 * Seed 3 Page cho màn Đăng nhập / Đăng ký / Quên mật khẩu.
 *
 * Nội dung để trống — template PHP (page-dang-nhap.php, page-dang-ky.php,
 * page-quen-mat-khau.php) lo hết phần hiển thị, không dùng block.
 *
 *   "C:/xampp8_2/php/php.exe" tools/seed-trang-dang-nhap.php
 *
 * Chạy nhiều lần được, không tạo trùng. CHỈ dùng ở local.
 *
 * @package NNTM
 */

if ( PHP_SAPI !== 'cli' ) {
	exit( 'Chi chay tu dong lenh.' );
}

$_SERVER['HTTP_HOST'] = 'nntm.com';
require_once __DIR__ . '/../wp-load.php';

$nntm_pages = array(
	'dang-nhap'     => 'Đăng nhập',
	'dang-ky'       => 'Đăng ký thành viên',
	'quen-mat-khau' => 'Quên mật khẩu',
);

echo "Seed trang Dang nhap / Dang ky / Quen mat khau\n";
echo str_repeat( '-', 50 ) . "\n";

foreach ( $nntm_pages as $slug => $title ) {
	$existing = get_page_by_path( $slug );

	if ( $existing ) {
		echo "Da co san : {$slug} (ID {$existing->ID}) — bo qua\n";
		continue;
	}

	$id = wp_insert_post(
		array(
			'post_type'    => 'page',
			'post_status'  => 'publish',
			'post_title'   => $title,
			'post_name'    => $slug,
			'post_content' => '',
		),
		true
	);

	if ( is_wp_error( $id ) ) {
		echo "LOI khi tao {$slug}: " . $id->get_error_message() . "\n";
		continue;
	}

	echo "Da tao    : {$slug} (ID {$id})\n";
}

echo str_repeat( '-', 50 ) . "\n";
echo "Xong.\n";
