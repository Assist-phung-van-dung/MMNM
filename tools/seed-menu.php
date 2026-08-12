<?php
/**
 * Tạo và gán menu chính + menu chân trang.
 *
 * Mục menu lấy đúng theo Figma (component HEADER / MAIN MENU):
 * Diệu Thượng · Pháp Toà · Liên Đàn · Hoa Khai · Vườn Xoài · Nhập Pháp Giới
 *
 *   "C:/xampp8_2/php/php.exe" tools/seed-menu.php
 *
 * Chạy nhiều lần được. Sau khi chạy, ban quản trị vẫn sửa menu bình thường
 * ở Giao diện → Menu — script này chỉ dựng sẵn lần đầu.
 *
 * @package NNTM
 */

if ( PHP_SAPI !== 'cli' ) {
	exit( 'Chi chay tu dong lenh.' );
}

$_SERVER['HTTP_HOST'] = 'localhost:8080';
require_once __DIR__ . '/../wp-load.php';

/**
 * Lấy ID trang theo slug, tạo trang trống nếu chưa có.
 *
 * @param string $slug  Đường dẫn tĩnh.
 * @param string $title Tiêu đề.
 * @return int
 */
function nntm_ensure_page( string $slug, string $title ): int {
	$page = get_page_by_path( $slug );
	if ( $page ) {
		return (int) $page->ID;
	}

	$id = wp_insert_post(
		array(
			'post_type'    => 'page',
			'post_status'  => 'publish',
			'post_title'   => $title,
			'post_name'    => $slug,
			'post_content' => '<!-- wp:paragraph --><p>Nội dung đang cập nhật.</p><!-- /wp:paragraph -->',
		)
	);
	echo "  Tao trang trong: {$title}\n";

	return is_wp_error( $id ) ? 0 : (int) $id;
}

/* ---------- Menu chính ---------- */

$sections = array(
	'dieu-thuong'    => 'Diệu Thượng',
	'phap-toa'       => 'Pháp Toà',
	'lien-dan'       => 'Liên Đàn',
	'hoa-khai'       => 'Hoa Khai',
	'vuon-xoai'      => 'Vườn Xoài',
	'nhap-phap-gioi' => 'Nhập Pháp Giới',
);

$menu_name = 'Menu chính';
$menu      = wp_get_nav_menu_object( $menu_name );
if ( ! $menu ) {
	$menu_id = wp_create_nav_menu( $menu_name );
	echo "Tao menu: {$menu_name}\n";
} else {
	$menu_id = (int) $menu->term_id;
	echo "Da co menu: {$menu_name}\n";
}

// Danh sách mục đã có trong menu, để chạy lại không tạo trùng.
$existing = wp_get_nav_menu_items( $menu_id );
$have     = array();
foreach ( (array) $existing as $item ) {
	$have[] = (int) $item->object_id;
}

$pos = 0;
foreach ( $sections as $slug => $title ) {
	++$pos;
	$page_id = nntm_ensure_page( $slug, $title );

	// Mục đã có trong menu thì bỏ qua bước thêm, NHƯNG vẫn phải chạy tiếp
	// xuống dưới để còn chèn "Tin Tức" sau "Hoa Khai".
	if ( $page_id && ! in_array( $page_id, $have, true ) ) {
		wp_update_nav_menu_item(
			$menu_id,
			0,
			array(
				'menu-item-title'     => $title,
				'menu-item-object'    => 'page',
				'menu-item-object-id' => $page_id,
				'menu-item-type'      => 'post_type',
				'menu-item-status'    => 'publish',
				'menu-item-position'  => $pos,
			)
		);
		echo "  Them muc: {$title}\n";
	}

	/*
	 * "Tin Tức" chèn ngay sau "Hoa Khai" — đúng thứ tự trong Figma ở
	 * bản đầu trang dành cho người ĐÃ ĐĂNG NHẬP.
	 *
	 * Mục này KHÔNG phải trang, mà trỏ tới chuyên mục "Tin Tức" của bài
	 * viết thường (docs/04-kien-truc.md mục 3: Tin Tức và Hoằng Pháp dùng
	 * loại bài viết có sẵn, không đẻ thêm CPT).
	 *
	 * header.php lọc mục này ra khi khách chưa đăng nhập, nhận diện theo
	 * ĐÚNG TÊN "Tin Tức". Ban quản trị đổi tên mục thì nó sẽ hiện với mọi
	 * người — có ghi chú trong header.php.
	 */
	if ( 'hoa-khai' === $slug ) {
		$tin_tuc = get_term_by( 'slug', 'tin-tuc', 'category' );
		if ( $tin_tuc && ! in_array( (int) $tin_tuc->term_id, $have, true ) ) {
			++$pos;
			wp_update_nav_menu_item(
				$menu_id,
				0,
				array(
					'menu-item-title'     => 'Tin Tức',
					'menu-item-object'    => 'category',
					'menu-item-object-id' => (int) $tin_tuc->term_id,
					'menu-item-type'      => 'taxonomy',
					'menu-item-status'    => 'publish',
					'menu-item-position'  => $pos,
				)
			);
			echo "  Them muc: Tin Tức (chuyen muc, chi hien khi da dang nhap)\n";
		}
	}
}

/*
 * Sắp lại thứ tự cho khớp Figma. Cần bước riêng vì wp_update_nav_menu_item()
 * chỉ đặt vị trí cho mục MỚI thêm — mục đã có từ lần chạy trước vẫn giữ vị
 * trí cũ, nên "Tin Tức" chèn vào sẽ rơi xuống cuối thay vì đứng sau Hoa Khai.
 */
$thu_tu = array( 'Diệu Thượng', 'Pháp Toà', 'Liên Đàn', 'Hoa Khai', 'Tin Tức', 'Vườn Xoài', 'Nhập Pháp Giới' );

foreach ( (array) wp_get_nav_menu_items( $menu_id ) as $item ) {
	$vi_tri = array_search( trim( $item->title ), $thu_tu, true );
	if ( false === $vi_tri ) {
		continue;
	}
	wp_update_post(
		array(
			'ID'         => $item->ID,
			'menu_order' => $vi_tri + 1,
		)
	);
}
echo "Da sap lai thu tu menu theo Figma\n";

/* ---------- Menu chân trang ---------- */

$footer_items = array(
	've-chung-toi' => 'Về chúng tôi',
	'lien-he'      => 'Liên hệ',
	'chinh-sach'   => 'Chính sách',
);

$footer_name = 'Menu chân trang';
$footer_menu = wp_get_nav_menu_object( $footer_name );
if ( ! $footer_menu ) {
	$footer_id = wp_create_nav_menu( $footer_name );
	echo "Tao menu: {$footer_name}\n";
} else {
	$footer_id = (int) $footer_menu->term_id;
	echo "Da co menu: {$footer_name}\n";
}

$existing_f = wp_get_nav_menu_items( $footer_id );
$have_f     = array();
foreach ( (array) $existing_f as $item ) {
	$have_f[] = (int) $item->object_id;
}

foreach ( $footer_items as $slug => $title ) {
	$page_id = nntm_ensure_page( $slug, $title );
	if ( ! $page_id || in_array( $page_id, $have_f, true ) ) {
		continue;
	}
	wp_update_nav_menu_item(
		$footer_id,
		0,
		array(
			'menu-item-title'     => $title,
			'menu-item-object'    => 'page',
			'menu-item-object-id' => $page_id,
			'menu-item-type'      => 'post_type',
			'menu-item-status'    => 'publish',
		)
	);
	echo "  Them muc: {$title}\n";
}

/* ---------- Gán vào vị trí trong theme ---------- */

set_theme_mod(
	'nav_menu_locations',
	array(
		'primary' => $menu_id,
		'footer'  => $footer_id,
	)
);

echo "\nDa gan: primary -> Menu chính, footer -> Menu chân trang\n";
