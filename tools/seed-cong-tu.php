<?php
/**
 * Seed du lieu cho Cong Tu "chuoi tri" (docs/07-ban-giao.md muc "Dang lam
 * do — Cong Tu chuoi tri").
 *
 *   "C:/xampp8_2/php/php.exe" tools/seed-cong-tu.php
 *
 * Chay nhieu lan duoc, KHONG tao trung. CHI dung o local. Khuon mau chep tu
 * tools/seed-kim-cuong-hanh-gia.php.
 *
 * Viec cua script:
 *   1. Tao 2 Page noi dung rong: tham-gia-chuoi-tri, khai-bao-chuoi-tri
 *      (template PHP page-{slug}.php cua theme lo het phan hien thi).
 *   2. Tao 1 nntm_program "Le Dan Khong Tuoc", excerpt "Tri tung Tam Bo
 *      Chu Ngon", _nntm_program_dang_mo=1, bat dau hom nay, khong ket thuc.
 *   3. Chen block nntm/cong-tu vao CUOI noi dung Page 243
 *      (/kim-cuong-hanh-gia/), GIU NGUYEN 4 block dang co.
 *
 * @package NNTM
 */

if ( PHP_SAPI !== 'cli' ) {
	exit( 'Chi chay tu dong lenh.' );
}

$_SERVER['HTTP_HOST']   = 'nntm.com';
$_SERVER['REQUEST_URI'] = '/kim-cuong-hanh-gia/';
require_once __DIR__ . '/../wp-load.php';

// Dong lenh khong co phien dang nhap — bo qua bo chan quyen khu Hanh Gia
// (giong het ly do da ghi trong seed-kim-cuong-hanh-gia.php) de doc/sua
// duoc Page 243 va khong bi giau bai khoi truy van kiem tra trung lap.
add_filter( 'nntm_duoc_xem_khu_han_che', '__return_true' );

$ngon_ngu_mac_dinh = function_exists( 'pll_default_language' ) ? pll_default_language() : 'vi';

/**
 * Gan ngon ngu Polylang cho mot post — BAT BUOC, thieu buoc nay thi bai/
 * trang bien mat khoi moi truy van post_type=any (kiem kiem, REST) du van
 * hien du tren truy van dung 1 post_type (bay da dinh 14/08/2026, xem
 * docs/07-ban-giao.md).
 *
 * @param int    $post_id ID bai/trang vua tao.
 * @param string $ngon_ngu Ma ngon ngu Polylang.
 */
function nntm_ct_gan_ngon_ngu( int $post_id, string $ngon_ngu ): void {
	if ( function_exists( 'pll_set_post_language' ) ) {
		pll_set_post_language( $post_id, $ngon_ngu );
	}
}

echo "Seed Cong Tu \"chuoi tri\"\n";
echo str_repeat( '-', 60 ) . "\n";

/* ---------- 1. Hai Page noi dung rong ---------- */

$nntm_ct_pages = array(
	'tham-gia-chuoi-tri' => 'Tham gia chuỗi trì',
	'khai-bao-chuoi-tri' => 'Khai báo chuỗi trì',
);

foreach ( $nntm_ct_pages as $slug => $title ) {
	$existing = get_page_by_path( $slug );

	if ( $existing ) {
		echo "Da co san Page : {$slug} (ID {$existing->ID}) - bo qua\n";
		// Van dam bao co ngon ngu du la trang cu chua tung duoc gan.
		nntm_ct_gan_ngon_ngu( (int) $existing->ID, $ngon_ngu_mac_dinh );
		continue;
	}

	$page_id = wp_insert_post(
		array(
			'post_type'    => 'page',
			'post_status'  => 'publish',
			'post_title'   => $title,
			'post_name'    => $slug,
			'post_content' => '',
		),
		true
	);

	if ( is_wp_error( $page_id ) ) {
		echo "LOI khi tao Page {$slug}: " . $page_id->get_error_message() . "\n";
		continue;
	}

	nntm_ct_gan_ngon_ngu( (int) $page_id, $ngon_ngu_mac_dinh );

	echo "Da tao Page    : {$slug} (ID {$page_id})\n";
}

/* ---------- 2. Chuong trinh "Le Dan Khong Tuoc" ---------- */

$nntm_ct_ten_chuong_trinh = 'Lễ Đàn Khổng Tước';

$nntm_ct_tim_chuong_trinh = get_posts(
	array(
		'post_type'      => 'nntm_program',
		'post_status'    => 'any',
		'title'          => $nntm_ct_ten_chuong_trinh,
		'posts_per_page' => 1,
	)
);

if ( ! empty( $nntm_ct_tim_chuong_trinh ) ) {
	$nntm_ct_program_id = (int) $nntm_ct_tim_chuong_trinh[0]->ID;
	echo "Da co san chuong trinh : '{$nntm_ct_ten_chuong_trinh}' (ID {$nntm_ct_program_id}) - bo qua tao moi\n";

	// Dam bao cong tac mo/ngay bat dau van dung ngay ca khi chay lai (khong
	// doi neu BQT da chinh tay), chi tu tao lai khi thieu.
	if ( '' === (string) get_post_meta( $nntm_ct_program_id, '_nntm_program_bat_dau', true ) ) {
		update_post_meta( $nntm_ct_program_id, '_nntm_program_bat_dau', current_time( 'Y-m-d' ) );
	}
} else {
	$nntm_ct_program_id = wp_insert_post(
		array(
			'post_type'    => 'nntm_program',
			'post_status'  => 'publish',
			'post_title'   => $nntm_ct_ten_chuong_trinh,
			'post_excerpt' => 'Trì tụng Tam Bộ Chú Ngôn',
			'post_content' => '',
		),
		true
	);

	if ( is_wp_error( $nntm_ct_program_id ) ) {
		exit( 'LOI khi tao chuong trinh: ' . $nntm_ct_program_id->get_error_message() . "\n" );
	}

	nntm_ct_gan_ngon_ngu( (int) $nntm_ct_program_id, $ngon_ngu_mac_dinh );

	update_post_meta( $nntm_ct_program_id, '_nntm_program_dang_mo', 1 );
	update_post_meta( $nntm_ct_program_id, '_nntm_program_bat_dau', current_time( 'Y-m-d' ) );
	update_post_meta( $nntm_ct_program_id, '_nntm_program_ket_thuc', '' );
	update_post_meta( $nntm_ct_program_id, '_nntm_program_don_vi', 'chuỗi' );

	echo "Da tao chuong trinh    : '{$nntm_ct_ten_chuong_trinh}' (ID {$nntm_ct_program_id})\n";
}

/* ---------- 3. Chen block nntm/cong-tu vao cuoi noi dung Page 243 ---------- */

$nntm_ct_trang_243 = get_post( 243 );

if ( ! $nntm_ct_trang_243 || 'page' !== $nntm_ct_trang_243->post_type ) {
	exit( "LOI: khong tim thay Page 243 (Kim Cuong Hanh Gia).\n" );
}

$nntm_ct_block_marker = 'wp:nntm/cong-tu';

if ( false !== strpos( $nntm_ct_trang_243->post_content, $nntm_ct_block_marker ) ) {
	echo "Page 243 da co block nntm/cong-tu - bo qua chen lai.\n";
} else {
	$nntm_ct_block_attrs = array(
		'heading'    => 'Thống Kê Của Đạo Tràng',
		'bxhHeading' => 'Bảng Xếp Hạng Cá Nhân',
		'background' => 'kem',
	);

	$nntm_ct_block_content = function_exists( 'nntm_home_block_pattern_content' )
		? nntm_home_block_pattern_content( 'nntm/cong-tu', $nntm_ct_block_attrs )
		: ( '<!-- wp:nntm/cong-tu ' . wp_json_encode( $nntm_ct_block_attrs, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . ' /-->' );

	$nntm_ct_noi_dung_moi = rtrim( $nntm_ct_trang_243->post_content ) . "\n\n" . $nntm_ct_block_content;

	wp_update_post(
		array(
			'ID'           => 243,
			'post_content' => $nntm_ct_noi_dung_moi,
		)
	);

	echo "Da chen block nntm/cong-tu vao cuoi Page 243 (Kim Cuong Hanh Gia), giu nguyen 4 block truoc do.\n";
}

echo str_repeat( '-', 60 ) . "\n";
echo "Xong.\n";
echo 'Xem: ' . home_url( '/tham-gia-chuoi-tri/' ) . "\n";
echo 'Xem: ' . home_url( '/khai-bao-chuoi-tri/' ) . "\n";
echo 'Xem: ' . get_permalink( 243 ) . "\n";
