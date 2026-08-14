<?php
/**
 * Cắt vùng tranh Tông Chỉ từ ảnh xuất node Figma 6376:6694 và nhập Media Library.
 */

$_SERVER['HTTP_HOST']   = $_SERVER['HTTP_HOST'] ?? 'nntm.com';
$_SERVER['REQUEST_URI'] = $_SERVER['REQUEST_URI'] ?? '/dieu-thuong/';

require dirname( __DIR__ ) . '/wp-load.php';
require_once ABSPATH . 'wp-admin/includes/image.php';
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/media.php';

$source = dirname( __DIR__ ) . '/design/figma/6376-6694-tong-chi.png';
if ( ! is_file( $source ) ) {
	fwrite( STDERR, "Không đọc được ảnh Tông Chỉ đã tách từ Figma." . PHP_EOL );
	exit( 1 );
}

$existing = get_posts(
	array(
		'post_type'      => 'attachment',
		'post_status'    => 'inherit',
		'posts_per_page' => 1,
		'meta_key'       => '_nntm_figma_node',
		'meta_value'     => '6376:6694:tong-chi',
		'fields'         => 'ids',
	)
);
if ( $existing ) {
	echo (int) $existing[0] . PHP_EOL;
	exit;
}

$uploads = wp_upload_dir();
$target  = trailingslashit( $uploads['path'] ) . 'dieu-thuong-tong-chi-figma.png';
if ( ! copy( $source, $target ) ) {
	fwrite( STDERR, "Không sao chép được ảnh Figma vào uploads." . PHP_EOL );
	exit( 1 );
}

$attachment_id = wp_insert_attachment(
	array(
		'post_mime_type' => 'image/png',
		'post_title'     => 'Tông Chỉ – Diệu Thượng',
		'post_content'   => '',
		'post_status'    => 'inherit',
	),
	$target
);
if ( is_wp_error( $attachment_id ) ) {
	fwrite( STDERR, $attachment_id->get_error_message() . PHP_EOL );
	exit( 1 );
}

wp_update_attachment_metadata( $attachment_id, wp_generate_attachment_metadata( $attachment_id, $target ) );
update_post_meta( $attachment_id, '_wp_attachment_image_alt', 'Địa Tạng Bồ Tát giữa hào quang và kinh điển' );
update_post_meta( $attachment_id, '_nntm_figma_node', '6376:6694:tong-chi' );
echo (int) $attachment_id . PHP_EOL;
