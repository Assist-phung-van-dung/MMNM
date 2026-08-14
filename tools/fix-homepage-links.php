<?php
/**
 * Sửa dữ liệu homepage sau import: host cũ và nhãn Unicode bị lưu nguyên văn.
 * Chạy: C:\xampp8_2\php\php.exe tools\fix-homepage-links.php
 */

$_SERVER['HTTP_HOST']   = $_SERVER['HTTP_HOST'] ?? 'nntm.com';
$_SERVER['REQUEST_URI'] = $_SERVER['REQUEST_URI'] ?? '/';
$_SERVER['SERVER_NAME'] = $_SERVER['SERVER_NAME'] ?? 'nntm.com';
$_SERVER['SERVER_PORT'] = $_SERVER['SERVER_PORT'] ?? '80';

require dirname( __DIR__ ) . '/wp-load.php';

$front_page_id = (int) get_option( 'page_on_front' );
$content       = (string) get_post_field( 'post_content', $front_page_id );
$xem_them      = "Xem th\u{00EA}m";

$content = str_replace( 'http://localhost:8080', home_url(), $content );
$content = str_replace( array( 'Xem thu{00EA}m', 'Xem th\u{00EA}m' ), $xem_them, $content );
$content = preg_replace(
	'/(<!-- wp:nntm\/article-mosaic \{[^\n]*"heading":"Hoạt động - Sự kiện"[^\n]*"viewAllLabel":")Xem thêm("[^\n]*\} \/-->)/u',
	'$1Xem tất cả$2',
	$content
);

$result = wp_update_post(
	array(
		'ID'           => $front_page_id,
		'post_content' => $content,
	),
	true
);

if ( is_wp_error( $result ) ) {
	fwrite( STDERR, $result->get_error_message() . PHP_EOL );
	exit( 1 );
}

flush_rewrite_rules( false );

if ( function_exists( 'pll_get_post_translations' ) ) {
	$translations = pll_get_post_translations( $front_page_id );
	if ( ! empty( $translations['en'] ) ) {
		$english_id      = (int) $translations['en'];
		$english_content = (string) get_post_field( 'post_content', $english_id );
		$english_content = preg_replace(
			'/(<!-- wp:nntm\/article-mosaic \{[^\n]*"heading":"Hoạt động - Sự kiện"[^\n]*"viewAllLabel":")[^"]*("[^\n]*\} \/-->)/u',
			'$1View all$2',
			$english_content
		);
		wp_update_post( array( 'ID' => $english_id, 'post_content' => $english_content ) );
	}
}

echo "Đã sửa homepage #{$front_page_id} và làm mới rewrite rules." . PHP_EOL;
