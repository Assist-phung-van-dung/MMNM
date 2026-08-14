<?php
/**
 * Kích hoạt Polylang, tạo VI/EN và gán dữ liệu hiện có cho tiếng Việt.
 * Chạy một lần: C:\xampp8_2\php\php.exe tools\setup-polylang.php
 */

$_SERVER['HTTP_HOST']   = $_SERVER['HTTP_HOST'] ?? 'nntm.com';
$_SERVER['REQUEST_URI'] = $_SERVER['REQUEST_URI'] ?? '/';
$_SERVER['SERVER_NAME'] = $_SERVER['SERVER_NAME'] ?? 'nntm.com';
$_SERVER['SERVER_PORT'] = $_SERVER['SERVER_PORT'] ?? '80';
$_GET['page']            = 'mlang';
define( 'WP_ADMIN', true );

require dirname( __DIR__ ) . '/wp-load.php';
require_once ABSPATH . 'wp-admin/includes/plugin.php';

$plugin = 'polylang/polylang.php';
if ( ! is_plugin_active( $plugin ) ) {
	$result = activate_plugin( $plugin );
	if ( is_wp_error( $result ) ) {
		fwrite( STDERR, $result->get_error_message() . PHP_EOL );
		exit( 1 );
	}
}

if ( ! function_exists( 'PLL' ) ) {
	fwrite( STDERR, "Polylang chưa khởi tạo được." . PHP_EOL );
	exit( 1 );
}

foreach (
	array(
		array( 'locale' => 'vi', 'name' => 'Tiếng Việt', 'slug' => 'vi', 'flag' => 'vn', 'term_group' => 0 ),
		array( 'locale' => 'en_US', 'name' => 'English', 'slug' => 'en', 'flag' => 'us', 'term_group' => 1 ),
	) as $language
) {
	if ( ! PLL()->model->get_language( $language['slug'] ) ) {
		$result = PLL()->model->add_language( $language );
		if ( is_wp_error( $result ) ) {
			fwrite( STDERR, $result->get_error_message() . PHP_EOL );
			exit( 1 );
		}
	}
}

PLL()->model->update_default_lang( 'vi' );
update_option( 'WPLANG', 'vi' );

$post_types = get_post_types( array( 'public' => true ), 'names' );
$post_ids   = get_posts(
	array(
		'post_type'              => array_values( $post_types ),
		'post_status'            => 'any',
		'posts_per_page'         => -1,
		'fields'                 => 'ids',
		'suppress_filters'       => true,
		'update_post_meta_cache' => false,
		'update_post_term_cache' => false,
	)
);

foreach ( $post_ids as $post_id ) {
	if ( ! pll_get_post_language( $post_id ) ) {
		pll_set_post_language( $post_id, 'vi' );
	}
}

// Tạo bản tiếng Anh của homepage để nút EN luôn dẫn đến một trang hợp lệ.
// Nội dung ban đầu được sao chép; admin có thể dịch từng Gutenberg block sau.
$front_page_id = (int) get_option( 'page_on_front' );
$translations  = pll_get_post_translations( $front_page_id );
if ( empty( $translations['en'] ) ) {
	$front_page    = get_post( $front_page_id );
	$english_title = $front_page ? $front_page->post_title . ' — English' : 'Home';
	$english_id    = wp_insert_post(
		array(
			'post_type'    => 'page',
			'post_status'  => 'publish',
			'post_title'   => $english_title,
			'post_name'    => 'home-en',
			'post_content' => $front_page ? $front_page->post_content : '',
		),
		true
	);
	if ( is_wp_error( $english_id ) ) {
		fwrite( STDERR, $english_id->get_error_message() . PHP_EOL );
		exit( 1 );
	}
	pll_set_post_language( $english_id, 'en' );
	pll_save_post_translations( array( 'vi' => $front_page_id, 'en' => $english_id ) );
}

foreach ( get_taxonomies( array( 'public' => true ), 'names' ) as $taxonomy ) {
	if ( in_array( $taxonomy, array( 'language', 'post_translations', 'term_language', 'term_translations' ), true ) ) {
		continue;
	}
	foreach ( get_terms( array( 'taxonomy' => $taxonomy, 'hide_empty' => false, 'lang' => '' ) ) as $term ) {
		if ( ! is_wp_error( $term ) && ! pll_get_term_language( $term->term_id ) ) {
			pll_set_term_language( $term->term_id, 'vi' );
		}
	}
}

flush_rewrite_rules( false );
echo "Đã cấu hình Polylang: mặc định VI, có thêm EN; dữ liệu hiện tại được gán VI." . PHP_EOL;
