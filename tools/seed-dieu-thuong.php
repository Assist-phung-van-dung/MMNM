<?php
/**
 * Seed trang Diệu Thượng cho môi trường phát triển.
 *
 * Dựng lại đúng cấu trúc Figma trang "02. DIEU THUONG": SECTION 4 "Tông chỉ"
 * + SECTION 6 "Trú Xứ". Chạy nhiều lần được, không tạo trùng.
 *
 *   "C:/xampp8_2/php/php.exe" tools/seed-dieu-thuong.php
 *
 * CHỈ dùng ở local. Nội dung là dữ liệu mẫu lấy từ Figma để xem hình hài,
 * không phải nội dung thật của khách.
 *
 * @package NNTM
 */

if ( PHP_SAPI !== 'cli' ) {
	exit( 'Chi chay tu dong lenh.' );
}

$_SERVER['HTTP_HOST'] = 'localhost:8080';
require_once __DIR__ . '/../wp-load.php';

/** Trú Xứ — tên và địa điểm lấy đúng từ Figma. */
$abodes = array(
	array( 'Phật Đảnh Bảo Vương', 'Việt Nam - Nha Trang' ),
	array( 'Phật Đảnh Quang Tụ', 'Việt Nam - Đà Lạt' ),
	array( 'Phật Đảnh Bảo Tạng', 'Ấn Độ - Bodh Gaya' ),
	array( 'Phật Đảnh Đăng Nhiên', 'Ấn Độ - Javata' ),
);

foreach ( $abodes as $i => list( $title, $location ) ) {
	// get_page_by_title() da bi khai tu tu WP 6.2 — dung WP_Query.
	$found = get_posts(
		array(
			'post_type'              => 'nntm_abode',
			'post_status'            => 'any',
			'title'                  => $title,
			'posts_per_page'         => 1,
			'no_found_rows'          => true,
			'update_post_term_cache' => false,
		)
	);
	$existing = $found ? $found[0] : null;

	if ( $existing ) {
		echo "Da co: {$title}\n";
		$id = $existing->ID;
	} else {
		$id = wp_insert_post(
			array(
				'post_type'    => 'nntm_abode',
				'post_status'  => 'publish',
				'post_title'   => $title,
				'post_excerpt' => $location,
				'menu_order'   => $i,
			)
		);
		echo "Tao moi: {$title}\n";
	}
	if ( $id && ! is_wp_error( $id ) ) {
		update_post_meta( $id, '_nntm_abode_location', $location );
	}
}

/* ---------- Trang phụ cho chân trang (Figma: "Về chúng tôi | Liên hệ | Chính sách") ---------- */

$footer_pages = array(
	've-chung-toi' => 'Về chúng tôi',
	'lien-he'      => 'Liên hệ',
	'chinh-sach'   => 'Chính sách',
);

foreach ( $footer_pages as $slug => $title ) {
	if ( get_page_by_path( $slug ) ) {
		echo "Da co trang: {$title}\n";
		continue;
	}
	wp_insert_post(
		array(
			'post_type'    => 'page',
			'post_status'  => 'publish',
			'post_title'   => $title,
			'post_name'    => $slug,
			'post_content' => '<!-- wp:paragraph --><p>Nội dung đang cập nhật.</p><!-- /wp:paragraph -->',
		)
	);
	echo "Tao trang: {$title}\n";
}

/* ---------- Trang Diệu Thượng ---------- */

$tong_chi = 'Nẵng Nhân Tịch Mặc lấy sự tĩnh lặng làm gốc, lấy trí tuệ làm đường. '
	. 'Mọi câu chuyện trên trang đều là người thật, việc thật, được khảo sát và phỏng vấn trực tiếp. '
	. 'Chúng tôi tin rằng chánh pháp không nằm ở lời hoa mỹ, mà ở đời sống được sống thật.';

$content = <<<HTML
<!-- wp:nntm/feature {"eyebrow":"Cập nhật 15. 06. 2026","heading":"Tông chỉ","content":"{$tong_chi}","mediaPosition":"right"} /-->

<!-- wp:nntm/tru-xu-list {"heading":"Trú Xứ","postsPerPage":4,"orderBy":"oldest"} /-->
HTML;

$slug     = 'dieu-thuong';
$existing = get_page_by_path( $slug );
$args     = array(
	'post_type'    => 'page',
	'post_status'  => 'publish',
	'post_title'   => 'Diệu Thượng',
	'post_name'    => $slug,
	'post_content' => $content,
);

if ( $existing ) {
	$args['ID'] = $existing->ID;
	wp_update_post( $args );
	echo "\nCap nhat trang Dieu Thuong\n";
} else {
	wp_insert_post( $args );
	echo "\nTao trang Dieu Thuong\n";
}

$page = get_page_by_path( $slug );
echo get_permalink( $page ) . "\n";
