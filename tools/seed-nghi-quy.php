<?php
/**
 * Seed du lieu cho trang "Nghi Quy" (/nghi-quy/) va sua trang Kim Cuong
 * Hanh Gia (ID 243) — xem docs/07-ban-giao.md muc "LOI 2" cua phien
 * 14/08/2026 (bao cao khach: "Nghi Quy la 1 cai rieng, dang slider chay
 * lien tuc, co nut Xem Tat Ca dan sang trang rieng").
 *
 *   "C:/xampp8_2/php/php.exe" tools/seed-nghi-quy.php
 *
 * Chay nhieu lan duoc, KHONG tao trung (tim theo tieu de bai / ghi de
 * cung mot trang co dinh slug 'nghi-quy'). CHI dung o local. Khuon mau
 * chep tu tools/seed-kim-cuong-hanh-gia.php.
 *
 * Viec cua script:
 *   1. CSDL da co san 6 an pham nntm_publication (tao boi
 *      tools/seed-hoa-khai.php) — seed them 39 an pham nua cho du 45
 *      (5 trang x 9 the/trang theo thiet ke), TAI SU DUNG anh bia da co
 *      trong Thu vien (attachment id 229, dang dung chung cho ca 6 an
 *      pham hien co — kiem chung bang truy van truoc khi viet script
 *      nay), KHONG nap anh moi. Ten lap lai theo 6 mau co san, kem so
 *      thu tu de tranh trung tieu de.
 *   2. Tao (neu chua co) Page 'nghi-quy' ("Nghi Quy"), ghep block
 *      nntm/card-list: postType nntm_publication, variant books, luoi
 *      3 cot, 9 bai/trang, co phan trang, nen kem, class rieng
 *      "nntm-nghi-quy-grid" (CSS da them o blocks/card-list/style.css)
 *      de the trang + anh doc + chu can giua dung nhu anh thiet ke.
 *   3. Ghi de lai trang "Kim Cuong Hanh Gia" (ID 243):
 *        - hai bien nntm/banner (dau trang + "Le Dan Khong Tuoc") bat
 *          tranVien:true — tran het chieu rong man hinh, goc vuong.
 *        - khoi nntm/card-list "Nghi Quy" doi layout sang carousel (bang
 *          chay lien tuc, tu chay), them nut "Xem Tat Ca" tro sang
 *          /nghi-quy/ qua thuoc tinh moi viewAllUrl.
 *      Khoi luoi bai Kim Cuong (dai 2) giu nguyen khong doi.
 *
 * @package NNTM
 */

if ( PHP_SAPI !== 'cli' ) {
	exit( 'Chi chay tu dong lenh.' );
}

$_SERVER['HTTP_HOST']   = 'nntm.com';
$_SERVER['REQUEST_URI'] = '/nghi-quy/';
require_once __DIR__ . '/../wp-load.php';

/* ---------- 1. Seed them an pham cho du 45 ---------- */

// Anh bia tai su dung tu Thu vien co san — DA kiem chung truoc khi viet
// script nay: ca 6 an pham hien co (id 40-45, tao boi seed-hoa-khai.php)
// deu dung chung dung mot attachment id 229 ("Bia an pham Hoa Khai").
// KHONG nap anh moi, dung dung lai id nay cho toan bo an pham moi.
$nntm_nq_image_id = 229;

if ( ! get_post( $nntm_nq_image_id ) ) {
	exit( "LOI: khong tim thay attachment id {$nntm_nq_image_id} trong Thu vien — kiem tra lai CSDL truoc khi chay tiep.\n" );
}

// 6 ten mau da co san (tao boi tools/seed-hoa-khai.php) — dung lai dung
// theo yeu cau "ten lap lai theo mau co san cung duoc", kem so thu tu de
// khong trung tieu de.
$nntm_nq_base_titles = array(
	'Biểu Tượng và Hoa Văn Mật Tông',
	'Tuyển Tập Pháp Thoại Tôn Sư — Quyển I',
	'Nghi Quỹ Tu Trì Hằng Ngày',
	'Chú Giải Kinh Kim Cang',
	'Hành Trạng Chư Tổ',
	'Sổ Tay Công Phu',
);

$nntm_nq_excerpt = 'Ấn phẩm dành cho hành giả và thành viên Năng Nhân Tịch Mặc.';

/**
 * Tao (neu chua co) mot bai nntm_publication mau, gan anh dai dien tai su
 * dung. Idempotent: trung tieu de (bat ky trang thai nao) thi bo qua.
 *
 * @param string $title     Tieu de bai — DUY NHAT, dung lam khoa kiem trung.
 * @param string $excerpt   Doan trich ngan.
 * @param int    $image_id  ID anh bia tai su dung.
 * @param string $post_date Ngay dang (Y-m-d H:i:s), chi dung khi tao moi.
 * @return array{id:int, created:bool}
 */
function nntm_nq_seed_publication( string $title, string $excerpt, int $image_id, string $post_date ): array {
	$found = get_posts( array(
		'post_type'      => 'nntm_publication',
		'post_status'    => 'any',
		'title'          => $title,
		'posts_per_page' => 1,
	) );

	if ( ! empty( $found ) ) {
		return array( 'id' => (int) $found[0]->ID, 'created' => false );
	}

	$post_id = wp_insert_post( array(
		'post_type'     => 'nntm_publication',
		'post_status'   => 'publish',
		'post_title'    => $title,
		'post_excerpt'  => $excerpt,
		'post_content'  => '<!-- wp:paragraph --><p>' . esc_html( $excerpt ) . '</p><!-- /wp:paragraph -->',
		'post_date'     => $post_date,
		'post_date_gmt' => get_gmt_from_date( $post_date ),
	) );

	if ( is_wp_error( $post_id ) || ! $post_id ) {
		return array( 'id' => 0, 'created' => false );
	}

	/*
	 * BAT BUOC gan ngon ngu Polylang — bo qua se lam bai BIEN MAT khoi tim
	 * kiem (post_type='any') du van hien tren dai loc dung mot post_type
	 * (xem "Bay Polylang" trong docs/07-ban-giao.md).
	 */
	if ( function_exists( 'pll_set_post_language' ) ) {
		pll_set_post_language( $post_id, function_exists( 'pll_default_language' ) ? pll_default_language() : 'vi' );
	}

	set_post_thumbnail( $post_id, $image_id );

	return array( 'id' => (int) $post_id, 'created' => true );
}

// Da co san 6 an pham -> can them 39 nua cho du 45 (5 trang x 9/trang).
$nntm_nq_can_them = 39;

// Moc thoi gian goc: 2 GIO TRUOC luc chay script (luon nam trong QUA KHU).
// Moi bai LUI 7 phut so voi bai truoc (>=5 phut theo yeu cau) de khong bao
// gio trung ngay dang du chay lai vao thoi diem nao.
$nntm_nq_base_timestamp = time() - 2 * HOUR_IN_SECONDS;

$nntm_nq_created = 0;
$nntm_nq_skipped = 0;

for ( $i = 1; $i <= $nntm_nq_can_them; $i++ ) {
	$base_title = $nntm_nq_base_titles[ ( $i - 1 ) % count( $nntm_nq_base_titles ) ];
	$title      = sprintf( '%s — Tập %d', $base_title, $i + 1 ); // +1: tap 1 la ban goc da co san khong danh so.

	// date() (khong phai gmdate()) — 'post_date' luu gio dia phuong cua
	// site, get_gmt_from_date() tu quy doi ra GMT cho 'post_date_gmt'.
	// Dung gmdate() se lam bai roi vao trang thai "future" thay vi "publish".
	$post_date = date( 'Y-m-d H:i:s', $nntm_nq_base_timestamp - ( $i - 1 ) * 420 );

	$result = nntm_nq_seed_publication( $title, $nntm_nq_excerpt, $nntm_nq_image_id, $post_date );

	if ( 0 === $result['id'] ) {
		echo "  LOI: khong tao duoc an pham '{$title}'.\n";
		continue;
	}

	if ( $result['created'] ) {
		++$nntm_nq_created;
	} else {
		++$nntm_nq_skipped;
	}
}

echo "An pham 'Nghi Quy' ({$nntm_nq_can_them} du kien them):\n";
echo "  Tao moi: {$nntm_nq_created}\n";
echo "  Da co san (bo qua): {$nntm_nq_skipped}\n";

$nntm_nq_tong_an_pham = ( new WP_Query( array(
	'post_type'      => 'nntm_publication',
	'post_status'    => 'publish',
	'posts_per_page' => -1,
	'fields'         => 'ids',
	'no_found_rows'  => false,
) ) )->found_posts;

echo "Tong so an pham 'publish' hien co trong CSDL: {$nntm_nq_tong_an_pham}\n";

/* ---------- 2. Trang "Nghi Quy" (Page moi, ghep tu block) ---------- */

$nntm_nq_page = get_page_by_path( 'nghi-quy' );

$nntm_nq_grid_attrs = array(
	'heading'      => 'Nghi Quỹ',
	'postType'     => 'nntm_publication',
	'variant'      => 'books',
	'layout'       => 'grid',
	'columns'      => 3,
	'postsPerPage' => 9,
	'showPaging'   => true,
	'background'   => 'kem',
	'showCardCta'  => true,
	'cardCtaLabel' => 'Xem thêm',
	'className'    => 'nntm-nghi-quy-grid',
);

$nntm_nq_page_content = nntm_home_block_pattern_content( 'nntm/card-list', $nntm_nq_grid_attrs );

if ( ! $nntm_nq_page ) {
	$nntm_nq_page_id = wp_insert_post( array(
		'post_type'    => 'page',
		'post_status'  => 'publish',
		'post_title'   => 'Nghi Quỹ',
		'post_name'    => 'nghi-quy',
		'post_content' => $nntm_nq_page_content,
	) );

	if ( is_wp_error( $nntm_nq_page_id ) || ! $nntm_nq_page_id ) {
		exit( "LOI: khong tao duoc trang 'nghi-quy'.\n" );
	}

	if ( function_exists( 'pll_set_post_language' ) ) {
		pll_set_post_language( $nntm_nq_page_id, function_exists( 'pll_default_language' ) ? pll_default_language() : 'vi' );
	}

	echo "\nDa TAO MOI trang 'Nghi Quy' (ID {$nntm_nq_page_id}).\n";
} else {
	$nntm_nq_page_id = (int) $nntm_nq_page->ID;

	wp_update_post( array(
		'ID'           => $nntm_nq_page_id,
		'post_content' => $nntm_nq_page_content,
	) );

	echo "\nDa GHI DE trang 'Nghi Quy' co san (ID {$nntm_nq_page_id}).\n";
}

echo 'Xem trang: ' . get_permalink( $nntm_nq_page_id ) . "\n";

/* ---------- 3. Sua trang "Kim Cuong Hanh Gia" (ID co dinh 243) ---------- */

$nntm_kchg_page_id = 243;
$nntm_kchg_page     = get_post( $nntm_kchg_page_id );

if ( ! $nntm_kchg_page || 'page' !== $nntm_kchg_page->post_type ) {
	exit( "LOI: khong tim thay trang Kim Cuong Hanh Gia (ID {$nntm_kchg_page_id}) — bo qua buoc 3.\n" );
}

$nntm_kchg_term_id = 50;
$nntm_kchg_term    = get_term( $nntm_kchg_term_id, 'nntm_section' );
if ( ! $nntm_kchg_term || is_wp_error( $nntm_kchg_term ) ) {
	$nntm_kchg_found_term = get_term_by( 'slug', 'kim-cuong-hanh-gia', 'nntm_section' );
	if ( $nntm_kchg_found_term && ! is_wp_error( $nntm_kchg_found_term ) ) {
		$nntm_kchg_term_id = (int) $nntm_kchg_found_term->term_id;
	}
}

// Dai 1: banner dau trang — anh dai dien vang (attachment 241, giong ban
// goc cua tools/seed-kim-cuong-hanh-gia.php), nay THEM tranVien:true.
$nntm_kchg_banner_image_id = 241;
$nntm_kchg_banner_attrs    = array(
	'slides' => array(
		array(
			'imageId'  => $nntm_kchg_banner_image_id,
			'imageUrl' => wp_get_attachment_url( $nntm_kchg_banner_image_id ) ?: '',
			'imageAlt' => '',
			'heading'  => '',
			'text'     => '',
		),
	),
	'tranVien' => true,
);

// Dai 2: luoi bai Kim Cuong — GIU NGUYEN khong doi (dung yeu cau: chi sua
// loi banner + Nghi Quy, khong dung vao dai nay).
$nntm_kchg_card_list_kim_cuong_attrs = array(
	'heading'      => 'Kim Cương Hành Giả',
	'postType'     => 'nntm_article',
	'taxonomy'     => 'nntm_section',
	'termId'       => $nntm_kchg_term_id,
	'variant'      => 'kim-cuong',
	'layout'       => 'grid',
	'columns'      => 3,
	'postsPerPage' => 6,
	'showPaging'   => true,
	'background'   => 'vang',
	'showCardCta'  => true,
	'showDate'     => false,
	'showCategory' => false,
);

// Dai 3: "Nghi Quy" — DOI sang bang chay lien tuc (layout carousel, tu
// chay) + nut "Xem Tat Ca" tro sang /nghi-quy/ qua thuoc tinh moi
// viewAllUrl (block.json + editor.js da them). Tang postsPerPage tu 5
// len 12 de bang chay co du the cho cam giac "chay lien tuc", khong con
// chi la 5 the tinh nhu truoc.
$nntm_kchg_card_list_nghi_quy_attrs = array(
	'heading'          => 'Nghi Quỹ',
	'postType'         => 'nntm_publication',
	'variant'          => 'books',
	'layout'           => 'carousel',
	'postsPerPage'     => 12,
	'autoplay'         => true,
	'autoplayInterval' => 6,
	'background'       => 'kem',
	'showCardCta'      => true,
	'showViewAll'      => true,
	'viewAllLabel'     => 'Xem Tất cả',
	'viewAllUrl'       => '/nghi-quy/',
);

// Dai 4: banner "Le Dan Khong Tuoc" — nut Tham gia GIU NGUYEN, nay THEM
// tranVien:true.
$nntm_kchg_le_dan_image_id = 239;
$nntm_kchg_banner_le_dan_attrs = array(
	'slides' => array(
		array(
			'imageId'     => $nntm_kchg_le_dan_image_id,
			'imageUrl'    => wp_get_attachment_url( $nntm_kchg_le_dan_image_id ) ?: '',
			'imageAlt'    => '',
			'heading'     => 'Lễ Đàn Khổng Tước',
			'text'        => 'Trì tụng Tam Bộ Chú Ngôn',
			'showButton'  => true,
			'buttonLabel' => 'Tham gia',
		),
	),
	'tranVien' => true,
);

$nntm_kchg_page_content = nntm_home_block_pattern_content( 'nntm/banner', $nntm_kchg_banner_attrs )
	. "\n\n"
	. nntm_home_block_pattern_content( 'nntm/card-list', $nntm_kchg_card_list_kim_cuong_attrs )
	. "\n\n"
	. nntm_home_block_pattern_content( 'nntm/card-list', $nntm_kchg_card_list_nghi_quy_attrs )
	. "\n\n"
	. nntm_home_block_pattern_content( 'nntm/banner', $nntm_kchg_banner_le_dan_attrs );

wp_update_post( array(
	'ID'           => $nntm_kchg_page_id,
	'post_content' => $nntm_kchg_page_content,
) );

echo "\nDa GHI DE trang 'Kim Cuong Hanh Gia' (ID {$nntm_kchg_page_id}):\n";
echo "  1. nntm/banner — tranVien:true (tran het chieu rong man hinh).\n";
echo "  2. nntm/card-list — Kim Cuong, GIU NGUYEN nhu cu.\n";
echo "  3. nntm/card-list — Nghi Quy, DOI sang layout carousel (bang chay lien tuc, tu chay), nut Xem Tat Ca tro /nghi-quy/.\n";
echo "  4. nntm/banner — Le Dan Khong Tuoc, tranVien:true.\n";
echo "\nXem trang: " . get_permalink( $nntm_kchg_page_id ) . "\n";
