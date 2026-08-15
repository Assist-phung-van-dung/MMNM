<?php
/**
 * Seed du lieu cho khu "Dai Si Hanh Gia" (docs/04-kien-truc.md muc 10).
 *
 *   "C:/xampp8_2/php/php.exe" tools/seed-dai-si-hanh-gia.php
 *
 * Chay nhieu lan duoc, KHONG tao trung (tim theo slug term / theo tieu de
 * bai / theo ID trang co dinh 242). CHI dung o local.
 *
 * Viec cua script:
 *   1. Tao (neu chua co) 2 term con duoi "Nhap Phap Gioi" (term_id 7) theo
 *      dung khuon mau da chot o docs/04-kien-truc.md muc 10 (term con cua
 *      nntm_section, KHONG de CPT/taxonomy moi):
 *        - "Dai Si Hanh Gia"    slug dai-si-hanh-gia    parent 7
 *        - "Kim Cuong Hanh Gia" slug kim-cuong-hanh-gia parent 7
 *   2. Seed 9 bai nntm_article "Dai Si Hanh Gia - Bai 1".."Bai 9", gan
 *      term "Dai Si Hanh Gia", anh dai dien lay lai tu Thu vien co san
 *      (KHONG nap anh moi). Ngay dang LECH NHAU it nhat 10 phut de
 *      orderby=date luon ra dung mot thu tu on dinh (loi da ghi trong
 *      docs/07-ban-giao.md: 6 bai trung ngay dang lam danh sach xoay vi
 *      tri moi lan tai).
 *   3. Ghi noi dung trang "Dai Si Hanh Gia" (ID 242, dang rong) thanh 2
 *      block: nntm/banner (anh 239) + nntm/card-list (variant dai-si,
 *      nen cham, loc theo term vua tao o buoc 1).
 *
 * @package NNTM
 */

if ( PHP_SAPI !== 'cli' ) {
	exit( 'Chi chay tu dong lenh.' );
}

$_SERVER['HTTP_HOST']   = 'nntm.com';
$_SERVER['REQUEST_URI'] = '/dai-si-hanh-gia/';
require_once __DIR__ . '/../wp-load.php';

/* ---------- 1. Hai term con duoi "Nhap Phap Gioi" (term_id 7) ---------- */

/**
 * Tao (neu chua co) mot term con cua nntm_section duoi "Nhap Phap Gioi".
 *
 * @param string $slug   Slug term.
 * @param string $name   Ten hien thi.
 * @param int    $parent ID term cha.
 * @return int ID term.
 */
function nntm_dshg_seed_term( string $slug, string $name, int $parent ): int {
	$existing = get_term_by( 'slug', $slug, 'nntm_section' );
	if ( $existing && ! is_wp_error( $existing ) ) {
		return (int) $existing->term_id;
	}

	$inserted = wp_insert_term( $name, 'nntm_section', array(
		'slug'   => $slug,
		'parent' => $parent,
	) );

	if ( is_wp_error( $inserted ) ) {
		return 0;
	}

	return (int) $inserted['term_id'];
}

$parent_term_id     = 7; // "Nhap Phap Gioi" — da co san tren site.
$dai_si_term_id     = nntm_dshg_seed_term( 'dai-si-hanh-gia', 'Đại Sĩ Hành Giả', $parent_term_id );
$kim_cuong_term_id  = nntm_dshg_seed_term( 'kim-cuong-hanh-gia', 'Kim Cương Hành Giả', $parent_term_id );

echo "Term (nntm_section, con cua term_id {$parent_term_id}):\n";
echo "  Dai Si Hanh Gia:    id={$dai_si_term_id}\n";
echo "  Kim Cuong Hanh Gia: id={$kim_cuong_term_id}\n";

if ( ! $dai_si_term_id ) {
	exit( "LOI: khong tao duoc term 'dai-si-hanh-gia'.\n" );
}

/* ---------- 2. 9 bai mau gan term "Dai Si Hanh Gia" ---------- */

// Anh dai dien tai su dung tu Thu vien co san (khong nap anh moi) — anh
// Phat giao hop canh, ID 118-123 va 180-185 theo yeu cau.
$image_pool = array( 118, 119, 120, 121, 122, 123, 180, 181, 182, 183, 184, 185 );

$sample_paragraphs = array(
	'Ngài Long Thọ Bồ Tát là một trong những luận sư vĩ đại nhất của Phật giáo Đại thừa, người đã hệ thống hoá tư tưởng Trung Quán và mở ra một chương mới cho triết học Phật giáo Ấn Độ. Cuộc đời ngài gắn liền với nhiều truyền thuyết, nhưng cốt lõi tư tưởng để lại vẫn soi sáng con đường tu tập cho hậu thế.',
	'Trước tác của ngài, tiêu biểu là bộ "Trung Luận" (Mūlamadhyamakakārikā), đã đặt nền móng cho lý thuyết về Tánh Không — không phải là hư vô mà là bản chất duyên khởi của vạn pháp. Từ đó, người học Phật có thể buông bỏ chấp thủ vào các cực đoan có và không, thường và đoạn.',
	'Hành giả tu theo tinh thần Long Thọ không dừng lại ở lý luận suông, mà lấy trí tuệ Bát Nhã làm kim chỉ nam cho đời sống hằng ngày: quán chiếu duyên khởi trong từng niệm, từng hành động, để dần dần chuyển hoá phiền não thành an lạc.',
	'Bài viết này là một phần trong chuỗi bài giới thiệu về các bậc Đại Sĩ Hành Giả — những tấm gương tu tập và hoằng pháp mà Năng Nhân Tịch Mặc mong muốn giới thiệu đến đại chúng, để mỗi người tự tìm thấy cho mình một hướng đi phù hợp trên con đường giác ngộ.',
);

/**
 * Tao (neu chua co) mot bai nntm_article mau, gan term va anh dai dien.
 *
 * Idempotent: neu da co bai trung tieu de (bat ky trang thai nao) thi BO
 * QUA hoan toan — khong sua lai ngay dang, de giu nguyen thu tu da on dinh
 * tu lan chay dau tien.
 *
 * @param string $title    Tieu de bai.
 * @param int    $term_id  ID term nntm_section can gan.
 * @param int    $image_id ID anh dai dien (attachment co san).
 * @param string $post_date Ngay dang (Y-m-d H:i:s), CHI dung khi tao moi.
 * @return array{id:int, created:bool}
 */
function nntm_dshg_seed_article( string $title, int $term_id, int $image_id, string $post_date ): array {
	$found = get_posts( array(
		'post_type'      => 'nntm_article',
		'post_status'    => 'any',
		'title'          => $title,
		'posts_per_page' => 1,
	) );

	if ( ! empty( $found ) ) {
		return array( 'id' => (int) $found[0]->ID, 'created' => false );
	}

	$content = '';
	foreach ( $GLOBALS['sample_paragraphs'] as $paragraph ) {
		$content .= '<!-- wp:paragraph --><p>' . esc_html( $paragraph ) . '</p><!-- /wp:paragraph -->' . "\n\n";
	}

	$post_id = wp_insert_post( array(
		'post_type'     => 'nntm_article',
		'post_status'   => 'publish',
		'post_title'    => $title,
		'post_content'  => trim( $content ),
		'post_excerpt'  => wp_trim_words( $GLOBALS['sample_paragraphs'][0], 30, '…' ),
		'post_date'     => $post_date,
		'post_date_gmt' => get_gmt_from_date( $post_date ),
	) );

	if ( is_wp_error( $post_id ) || ! $post_id ) {
		return array( 'id' => 0, 'created' => false );
	}

	wp_set_object_terms( $post_id, array( $term_id ), 'nntm_section' );

	/*
	 * BAT BUOC gan ngon ngu Polylang. Bo qua buoc nay thi bai KHONG co
	 * term "language", va Polylang se loai no khoi moi truy van nhieu
	 * post type — ke ca TIM KIEM cua site. Trieu chung rat kho doan:
	 * truy van post_type='nntm_article' van thay bai, nhung tim kiem
	 * (post_type='any') thi khong. Da dinh dung loi nay ngay 14/08/2026
	 * voi 9 bai Dai Si.
	 */
	if ( function_exists( 'pll_set_post_language' ) ) {
		pll_set_post_language( $post_id, function_exists( 'pll_default_language' ) ? pll_default_language() : 'vi' );
	}

	if ( get_post( $image_id ) ) {
		set_post_thumbnail( $post_id, $image_id );
	}

	return array( 'id' => (int) $post_id, 'created' => true );
}

// Moc thoi gian goc: 1 GIO TRUOC luc chay script (luon nam trong QUA KHU
// bat ke chay vao gio nao trong ngay — dung "today 08:00" truoc day co
// the roi vao TUONG LAI neu chay truoc 8h sang, khien wp_insert_post tu
// doi status thanh "future" thay vi "publish"). Moi bai LUI 10 phut so
// voi bai truoc -> "Bai 1" moi nhat (dung dau danh sach orderby=date DESC
// mac dinh cua card-list), "Bai 9" cu nhat. Cach nhau 10 phut, du de KHONG
// bao gio trung gio du chay lai vao thoi diem nao.
$base_timestamp = time() - HOUR_IN_SECONDS;

$created_count = 0;
$skipped_count = 0;

for ( $i = 1; $i <= 9; $i++ ) {
	$title     = sprintf( 'Đại Sĩ Hành Giả - Bài %d', $i );
	$image_id  = $image_pool[ ( $i - 1 ) % count( $image_pool ) ];
	// date() (khong phai gmdate()) — 'post_date' luu theo GIO DIA PHUONG cua
	// site, get_gmt_from_date() se tu quy doi ra GMT cho 'post_date_gmt'.
	$post_date = date( 'Y-m-d H:i:s', $base_timestamp - ( $i - 1 ) * 600 );

	$result = nntm_dshg_seed_article( $title, $dai_si_term_id, $image_id, $post_date );

	if ( 0 === $result['id'] ) {
		echo "  LOI: khong tao duoc bai '{$title}'.\n";
		continue;
	}

	if ( $result['created'] ) {
		++$created_count;
	} else {
		++$skipped_count;
	}
}

echo "\n9 bai 'Đại Sĩ Hành Giả':\n";
echo "  Tao moi: {$created_count}\n";
echo "  Da co san (bo qua): {$skipped_count}\n";

/* ---------- 3. Trang "Dai Si Hanh Gia" (ID co dinh 242) ---------- */

$page_id = 242;
$page    = get_post( $page_id );

if ( ! $page || 'page' !== $page->post_type ) {
	exit( "LOI: khong tim thay trang dich (ID {$page_id}).\n" );
}

$banner_image_id = 239; // anh thien duoi trang, dung anh trong thiet ke.

// Tran het chieu rong man hinh, className dung CHUNG voi banner dau trang
// cua Kim Cuong (blocks/banner/style.css .nntm-banner--khu-dau) — sua
// 14/08/2026 theo yeu cau chu du an "sao hinh khong full nua" (docs/07-ban-giao.md
// muc 9, viec 3). SUA VAO DAY, KHONG sua bang script tam roi vut di.
$banner_attrs = array(
	'className' => 'nntm-banner--khu-dau',
	'tranVien'  => true,
	'slides'    => array(
		array(
			'imageId'  => $banner_image_id,
			'imageUrl' => wp_get_attachment_url( $banner_image_id ) ?: '',
			'imageAlt' => '',
			'heading'  => '',
			'text'     => '',
		),
	),
);

$card_list_attrs = array(
	'heading'      => 'Ngài Long Thọ Bồ Tát',
	'subheading'   => 'Cuộc đời – Tư Tưởng - Tác Phẩm',
	'postType'     => 'nntm_article',
	'taxonomy'     => 'nntm_section',
	'termId'       => $dai_si_term_id,
	'variant'      => 'dai-si',
	'layout'       => 'grid',
	'columns'      => 3,
	'postsPerPage' => 9,
	'showPaging'   => true,
	'background'   => 'cham',
	'showCardCta'  => true,
	'showDate'     => false,
	'showCategory' => false,
);

$page_content = nntm_home_block_pattern_content( 'nntm/banner', $banner_attrs )
	. "\n\n"
	. nntm_home_block_pattern_content( 'nntm/card-list', $card_list_attrs );

wp_update_post( array(
	'ID'           => $page_id,
	'post_content' => $page_content,
) );

echo "\nDa ghi de trang 'Dai Si Hanh Gia' (ID {$page_id}):\n";
echo "  1. nntm/banner — anh {$banner_image_id}, tranVien=true, className=nntm-banner--khu-dau.\n";
echo "  2. nntm/card-list — variant dai-si, nen cham, termId={$dai_si_term_id}, 9 bai/trang, phan trang bat.\n";
echo "\nXem trang: " . get_permalink( $page_id ) . "\n";
