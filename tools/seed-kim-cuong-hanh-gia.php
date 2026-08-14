<?php
/**
 * Seed du lieu cho khu "Kim Cuong Hanh Gia" (docs/04-kien-truc.md muc 10,
 * docs/07-ban-giao.md muc "Kim Cuong Hanh Gia — chua dung").
 *
 *   "C:/xampp8_2/php/php.exe" tools/seed-kim-cuong-hanh-gia.php
 *
 * ============================================================================
 * QUAN TRONG — DOC TRUOC KHI SUA BAT KY THU GI O TRANG 243:
 *
 * Script nay la NGUON SU THAT DUY NHAT cho cau hinh trang "Kim Cuong Hanh
 * Gia" (ID 243). MOI thay doi cau hinh trang nay (class, tranVien, layout,
 * bien the, v.v.) TU NAY PHAI SUA VAO DAY — KHONG duoc sua bang mot script
 * tam (vd script rieng trong thu muc scratchpad) roi bo di.
 *
 * DAY LA BAI HOC THAT: mot phan viec truoc do da gan className="nntm-banner
 * --kc-dau"/tranVien=true cho banner va className cho card-list Nghi Quy
 * BANG MOT SCRIPT TAM, KHONG cap nhat vao day. Lan sau chay lai script nay,
 * toan bo cau hinh do bi XOA SACH vi script nay ghi de het noi dung 4 dai —
 * banner mat tranVien/className, ca hai card-list mat className, dai Nghi
 * Quy tro lai layout=grid thay vi bang tu chay. Chu du an bao "sao hinh
 * khong full nua" chinh la hau qua cua loi nay.
 *
 * Chay nhieu lan duoc, KHONG tao trung (tim theo tieu de bai / ghi de
 * cung mot trang co dinh ID 243). CHI dung o local. Khuon mau chep tu
 * tools/seed-dai-si-hanh-gia.php — DA vá lỗi Polylang, chép đúng cách
 * gán ngôn ngữ (xem ham nntm_kchg_seed_article() ben duoi).
 *
 * Viec cua script:
 *   1. Xac nhan term "Kim Cuong Hanh Gia" (nntm_section, id co dinh 50,
 *      con cua "Nhap Phap Gioi" id 7) DA CO SAN — script nay KHONG tao
 *      term moi, chi doc lai de doi chieu (khac seed-dai-si-hanh-gia.php,
 *      noi term chua co san luc do).
 *   2. Seed 26 bai nntm_article "Kim Cương Hành Giả - Bài 1".."Bài 26",
 *      gan term id 50, anh dai dien lay lai tu Thu vien co san (KHONG nap
 *      anh moi). 26 bai / 6 bai moi trang = 5 trang phan trang (trang cuoi
 *      con 2 bai) - dung so luong theo thiet ke.
 *   3. Ghi noi dung trang "Kim Cuong Hanh Gia" (ID 243, dang rong) thanh
 *      4 block dung 4 dai theo thiet ke (TRANG THAI CUOI CUNG, chay lai
 *      bao nhieu lan cung phai ra dung nhu vay):
 *        a. nntm/banner       - anh dai dien vang (attachment 241),
 *                                tranVien=true, className=nntm-banner--kc-dau
 *        b. nntm/card-list    - variant kim-cuong, nen vang, loc theo
 *                                term 50, luoi 3 cot, 6 bai/trang, co
 *                                phan trang (giu nguyen nhu da co)
 *        c. nntm/card-list    - "Nghi Quy", nntm_publication, variant
 *                                books, nen kem, layout=marquee (bang TU
 *                                CHAY, khong nut/thanh cuon),
 *                                className=nntm-kc-nghi-quy, showViewAll,
 *                                viewAllLabel="Xem Tất cả", viewAllUrl=/nghi-quy/
 *        d. nntm/banner       - "Le Dan Khong Tuoc", nut "Tham gia" lay
 *                                URL qua filter nntm_tham_gia_chuoi_tri_url,
 *                                tranVien=true, className=nntm-banner--kc-ledan
 *
 * @package NNTM
 */

if ( PHP_SAPI !== 'cli' ) {
	exit( 'Chi chay tu dong lenh.' );
}

$_SERVER['HTTP_HOST']   = 'nntm.com';
$_SERVER['REQUEST_URI'] = '/kim-cuong-hanh-gia/';
require_once __DIR__ . '/../wp-load.php';

/*
 * BAT BUOC: script chay qua dong lenh KHONG dang nhap (khong co current
 * user), nen inc/hanh-gia.php (pre_get_posts, KHONG duoc sua file do) tu
 * dong loai het bai thuoc term "kim-cuong-hanh-gia" (id 50) ra khoi MOI
 * truy van get_posts()/WP_Query cua chinh script nay — ke ca truy van
 * KIEM TRA TRUNG LAP o buoc 2 ben duoi. Khong bat filter nay thi script
 * KHONG BAO GIO thay bai da tao truoc do -> tao lai 26 bai moi MOI LAN
 * chay, vi pham yeu cau "chay lai khong tao trung" (da phat hien va sua
 * ngay trong luot lam nay: chay lan 2 ra 52 bai thay vi 26).
 */
add_filter( 'nntm_duoc_xem_khu_han_che', '__return_true' );

/* ---------- 1. Xac nhan term "Kim Cuong Hanh Gia" (id co dinh 50) ---------- */

$kim_cuong_term_id = 50;
$kim_cuong_term    = get_term( $kim_cuong_term_id, 'nntm_section' );

if ( ! $kim_cuong_term || is_wp_error( $kim_cuong_term ) ) {
	// Du phong: neu vi ly do gi ID 50 khong con dung (vd CSDL khac), tim
	// lai theo slug — KHONG tu tao term moi de tranh sinh id le thu hai.
	$found_term = get_term_by( 'slug', 'kim-cuong-hanh-gia', 'nntm_section' );
	if ( ! $found_term || is_wp_error( $found_term ) ) {
		exit( "LOI: khong tim thay term 'kim-cuong-hanh-gia' (nntm_section). Kiem tra lai CSDL truoc khi chay tiep.\n" );
	}
	$kim_cuong_term_id = (int) $found_term->term_id;
	$kim_cuong_term    = $found_term;
}

echo "Term (nntm_section): Kim Cuong Hanh Gia id={$kim_cuong_term_id} slug={$kim_cuong_term->slug} parent={$kim_cuong_term->parent}\n";

/* ---------- 2. 26 bai mau gan term "Kim Cuong Hanh Gia" ---------- */

// Anh dai dien tai su dung tu Thu vien co san (khong nap anh moi) — dung lai
// dung pool anh Phat giao hop canh da dung cho Dai Si (id 118-123, 180-185).
$image_pool = array( 118, 119, 120, 121, 122, 123, 180, 181, 182, 183, 184, 185 );

// Cum tu "Kim Cang Bat Nha Ba La Mat" CHI xuat hien trong noi dung cac bai
// nay — dung lam chuoi tim kiem doi chung bay Polylang (xem kiem chung
// buoc 5 trong bao cao).
$sample_paragraphs = array(
	'Kim Cang Bát Nhã Ba La Mật là một trong những bộ kinh cốt lõi của Phật giáo Đại thừa, xoáy sâu vào trí tuệ đoạn trừ mọi chấp thủ — chấp ngã, chấp pháp, chấp vào chính giáo pháp đang được giảng nói. Hành giả Kim Cương tu tập trên nền tảng này, lấy trí tuệ Bát Nhã làm kim chỉ nam xuyên suốt.',
	'Khác với hành trì thông thường dựa vào tín tâm đơn thuần, con đường Kim Cương Hành Giả đòi hỏi sự quán chiếu miên mật: mọi hiện tượng đều như huyễn, như mộng, như bọt, như bóng — thấy được điều này thì tâm không còn bị trói buộc bởi được mất, hơn thua.',
	'Nghi quỹ trì tụng và thiền quán được xem như phương tiện thiện xảo để đưa hành giả từ lý thuyết vào thực chứng: mỗi thời khóa công phu là một lần quay về quán chiếu tự tâm, không phải chỉ là nghi lễ hình thức.',
	'Bài viết này thuộc chuỗi giới thiệu khu Kim Cương Hành Giả của Năng Nhân Tịch Mặc — nơi giới thiệu những chủ đề tu tập chuyên sâu dành cho hành giả đã có nền tảng vững, mong muốn tiến sâu hơn vào trí tuệ Bát Nhã.',
);

/**
 * Tao (neu chua co) mot bai nntm_article mau cho khu Kim Cuong, gan term
 * va anh dai dien.
 *
 * Idempotent: neu da co bai trung tieu de (bat ky trang thai nao) thi BO
 * QUA hoan toan — khong sua lai ngay dang, giu nguyen thu tu on dinh tu
 * lan chay dau tien.
 *
 * @param string $title     Tieu de bai.
 * @param int    $term_id   ID term nntm_section can gan.
 * @param int    $image_id  ID anh dai dien (attachment co san).
 * @param string $post_date Ngay dang (Y-m-d H:i:s), CHI dung khi tao moi.
 * @return array{id:int, created:bool}
 */
function nntm_kchg_seed_article( string $title, int $term_id, int $image_id, string $post_date ): array {
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
	 * BAT BUOC gan ngon ngu Polylang — dung y het cach da va o
	 * seed-dai-si-hanh-gia.php. Bo qua buoc nay thi bai KHONG co term
	 * "language", va Polylang se loai no khoi moi truy van nhieu post
	 * type — ke ca TIM KIEM cua site (post_type='any'), du van hien du
	 * tren cac dai lay dung mot post_type='nntm_article'. Da dinh dung
	 * loi nay ngay 14/08/2026 voi 9 bai Dai Si — xem docs/07-ban-giao.md.
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
// bat ke chay vao gio nao trong ngay). Moi bai LUI 10 phut so voi bai truoc
// -> "Bai 1" moi nhat, "Bai 26" cu nhat. Cach nhau 10 phut, du de KHONG bao
// gio trung gio du chay lai vao thoi diem nao (giong dung ky thuat da dung
// o seed-dai-si-hanh-gia.php, tranh loi "6 bai trung ngay dang" da ghi o
// docs/07-ban-giao.md).
$base_timestamp = time() - HOUR_IN_SECONDS;

$total_articles = 26;
$created_count   = 0;
$skipped_count   = 0;

for ( $i = 1; $i <= $total_articles; $i++ ) {
	$title     = sprintf( 'Kim Cương Hành Giả - Bài %d', $i );
	$image_id  = $image_pool[ ( $i - 1 ) % count( $image_pool ) ];
	// date() (khong phai gmdate()) — 'post_date' luu theo GIO DIA PHUONG cua
	// site, get_gmt_from_date() se tu quy doi ra GMT cho 'post_date_gmt'.
	// Dung gmdate() o day se lam bai roi vao trang thai "future" thay vi
	// "publish" (bay da ghi trong docs/07-ban-giao.md).
	$post_date = date( 'Y-m-d H:i:s', $base_timestamp - ( $i - 1 ) * 600 );

	$result = nntm_kchg_seed_article( $title, $kim_cuong_term_id, $image_id, $post_date );

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

echo "\n{$total_articles} bai 'Kim Cương Hành Giả':\n";
echo "  Tao moi: {$created_count}\n";
echo "  Da co san (bo qua): {$skipped_count}\n";

/* ---------- 3. Trang "Kim Cuong Hanh Gia" (ID co dinh 243) ---------- */

$page_id = 243;
$page    = get_post( $page_id );

if ( ! $page || 'page' !== $page->post_type ) {
	exit( "LOI: khong tim thay trang dich (ID {$page_id}).\n" );
}

// Dai 1: banner anh dai dien vang — TRAN VIEN (tran het chieu rong man
// hinh), className rieng de CSS trang 243 nham dung dai nay neu can chinh
// sau nay. CA HAI thuoc tinh nay TUNG bi gan bang mot script tam roi mat
// khi chay lai script nay — nay da chuyen han vao day, xem ghi chu dau file.
$banner_image_id = 241;

$banner_attrs = array(
	'className' => 'nntm-banner--kc-dau',
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

// Dai 2: luoi bai Kim Cuong, nen vang, variant kim-cuong.
$card_list_kim_cuong_attrs = array(
	'heading'      => 'Kim Cương Hành Giả',
	'postType'     => 'nntm_article',
	'taxonomy'     => 'nntm_section',
	'termId'       => $kim_cuong_term_id,
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

// Dai 3: "Nghi Quy" — an pham (nntm_publication), variant books, nen kem,
// layout=marquee (bang TU CHAY lien tuc, KHONG nut mui ten, KHONG thanh
// cuon — chu du an bao "Nghi Quy khong con tu chay nua" vi layout=carousel
// truoc day CHI la khung cuon tay co nut/thanh cuon, chua bao gio la bang
// tu chay; xem inc/render-card-list-marquee.php). className rieng de
// style.css nham dung dai nay (da co san khoi CSS scoped ".nntm-kc-nghi-quy"
// trong blocks/card-list/style.css). viewAllUrl ghi de vi Nghi Quy la mot
// Page ghep tu block (/nghi-quy/), khong phai kho luu tru cua nntm_publication.
$card_list_nghi_quy_attrs = array(
	'heading'       => 'Nghi Quỹ',
	'className'     => 'nntm-kc-nghi-quy',
	'postType'      => 'nntm_publication',
	'variant'       => 'books',
	'layout'        => 'marquee',
	'postsPerPage'  => 5,
	'background'    => 'kem',
	'showCardCta'   => true,
	'showViewAll'   => true,
	'viewAllLabel'  => 'Xem Tất cả',
	'viewAllUrl'    => '/nghi-quy/',
);

// Dai 4: banner "Le Dan Khong Tuoc" — nut "Tham gia" lay URL qua filter
// nntm_tham_gia_chuoi_tri_url (phan Cong Tu se cam vao sau). Tam dung lai
// anh 239 (da co san trong Thu vien, dung chung voi banner Dai Si) vi chua
// co anh rieng cho dai nay — ghi ro trong bao cao la anh tam. TRAN VIEN +
// className rieng, cung ly do nhu Dai 1 — xem ghi chu dau file.
$le_dan_image_id = 239;

$banner_le_dan_attrs = array(
	'className' => 'nntm-banner--kc-ledan',
	'tranVien'  => true,
	'slides'    => array(
		array(
			'imageId'     => $le_dan_image_id,
			'imageUrl'    => wp_get_attachment_url( $le_dan_image_id ) ?: '',
			'imageAlt'    => '',
			'heading'     => 'Lễ Đàn Khổng Tước',
			'text'        => 'Trì tụng Tam Bộ Chú Ngôn',
			'showButton'  => true,
			'buttonLabel' => 'Tham gia',
		),
	),
);

$page_content = nntm_home_block_pattern_content( 'nntm/banner', $banner_attrs )
	. "\n\n"
	. nntm_home_block_pattern_content( 'nntm/card-list', $card_list_kim_cuong_attrs )
	. "\n\n"
	. nntm_home_block_pattern_content( 'nntm/card-list', $card_list_nghi_quy_attrs )
	. "\n\n"
	. nntm_home_block_pattern_content( 'nntm/banner', $banner_le_dan_attrs );

/*
 * GIU LAI cac block do SCRIPT KHAC them vao trang nay.
 *
 * DA CAN THAT ngay 14/08/2026: script nay ghi de toan bo noi dung trang 243,
 * xoa mat khoi `nntm/cong-tu` ma tools/seed-cong-tu.php da chen vao truoc do.
 * Khoi Thong Ke + Bang Xep Hang bien mat khoi trang ma khong ai bao loi —
 * chay lai mot script seed lai giet san pham cua script khac.
 *
 * Cach chua: chi dung lai 4 dai MA SCRIPT NAY SO HUU, con moi block la thi
 * noi tiep phia sau, giu nguyen thu tu tuong doi. Script seed khac them dai
 * moi vao trang nay cung se song sot.
 */
$block_giu_lai = array();
foreach ( parse_blocks( get_post( $page_id )->post_content ) as $block_cu ) {
	if ( empty( $block_cu['blockName'] ) ) {
		continue;
	}

	// 4 dai do chinh script nay dung -> bo di, vi vua dung lai o tren.
	if ( in_array( $block_cu['blockName'], array( 'nntm/banner', 'nntm/card-list' ), true ) ) {
		continue;
	}

	$block_giu_lai[] = trim( serialize_block( $block_cu ) );
}

if ( $block_giu_lai ) {
	$page_content .= "\n\n" . implode( "\n\n", $block_giu_lai );
}

wp_update_post( array(
	'ID'           => $page_id,
	'post_content' => $page_content,
) );

echo "\nDa ghi de trang 'Kim Cuong Hanh Gia' (ID {$page_id}):\n";
echo "  1. nntm/banner — anh {$banner_image_id}, tranVien=true, className=nntm-banner--kc-dau.\n";
echo "  2. nntm/card-list — variant kim-cuong, nen vang, termId={$kim_cuong_term_id}, 6 bai/trang, phan trang bat.\n";
echo "  3. nntm/card-list — Nghi Quy, variant books, nen kem, nntm_publication, layout=marquee, className=nntm-kc-nghi-quy, Xem Tat ca -> /nghi-quy/.\n";
echo "  4. nntm/banner — Le Dan Khong Tuoc, nut Tham gia (anh {$le_dan_image_id}, anh tam - chua co thiet ke rieng), tranVien=true, className=nntm-banner--kc-ledan.\n";
echo "\nXem trang: " . get_permalink( $page_id ) . "\n";
