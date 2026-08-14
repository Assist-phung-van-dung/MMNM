<?php
/** Cập nhật trang Diệu Thượng theo node Figma 6376:6694. */

$_SERVER['HTTP_HOST']   = $_SERVER['HTTP_HOST'] ?? 'nntm.com';
$_SERVER['REQUEST_URI'] = $_SERVER['REQUEST_URI'] ?? '/dieu-thuong/';
require dirname( __DIR__ ) . '/wp-load.php';

$page = get_page_by_path( 'dieu-thuong' );
$images = get_posts(
	array(
		'post_type'      => 'attachment',
		'post_status'    => 'inherit',
		'posts_per_page' => 1,
		'meta_key'       => '_nntm_figma_node',
		'meta_value'     => '6376:6694:tong-chi',
		'fields'         => 'ids',
	)
);
if ( ! $page || ! $images ) {
	fwrite( STDERR, "Thiếu trang Diệu Thượng hoặc ảnh Figma đã nhập." . PHP_EOL );
	exit( 1 );
}

$image_id = (int) $images[0];
$feature  = array(
	'eyebrow'       => '',
	'heading'       => 'Tông Chỉ',
	'content'       => "Phát Bồ Đề Tâm\nTrên Sáu Xúc Xứ/ Ngũ Thủ Uẩn Tu Tứ Chánh Cần, Lấy Bát Chánh Đạo Làm Nền Tảng.",
	'imageId'       => $image_id,
	'imageUrl'      => wp_get_attachment_url( $image_id ),
	'imageAlt'      => 'Địa Tạng Bồ Tát giữa hào quang và kinh điển',
	'mediaPosition' => 'right',
);

$content  = '<!-- wp:nntm/feature ' . wp_json_encode( $feature, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG ) . ' /-->';
$content .= PHP_EOL . PHP_EOL;
$content .= '<!-- wp:nntm/tru-xu-list {"heading":"Trú Xứ","postsPerPage":4,"orderBy":"oldest","displayMode":"list"} /-->';

$result = wp_update_post( wp_slash( array( 'ID' => $page->ID, 'post_content' => $content ) ), true );
if ( is_wp_error( $result ) ) {
	fwrite( STDERR, $result->get_error_message() . PHP_EOL );
	exit( 1 );
}

echo "Đã cập nhật trang Diệu Thượng #{$page->ID}." . PHP_EOL;
