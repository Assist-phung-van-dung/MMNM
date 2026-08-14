<?php
/**
 * Seed trang "Nhập Pháp Giới" (slug nhap-phap-gioi, ID 79) theo bản thiết
 * kế anh Úy gửi cho block nntm/rank-card + 2 khối "Gót Son" / "GITA CENTRE"
 * copy nguyên từ trang chủ (post ID 110).
 *
 *   "C:/xampp8_2/php/php.exe" tools/seed-nhap-phap-gioi.php
 *
 * Chạy nhiều lần được, không tạo trùng. CHỈ dùng ở local.
 *
 * Việc của script:
 *   1. Tạo (nếu chưa có) 2 trang đích rỗng, đã publish: "Đại Sĩ Hành Giả"
 *      và "Kim Cương Hành Giả" — Figma đã có 2 frame này nhưng chưa dựng,
 *      tạm để trống nội dung, chờ phần việc khác dựng chi tiết.
 *   2. Ghi đè nội dung trang "nhap-phap-gioi" (ID 79) thành 3 block:
 *      nntm/rank-card + 2 x nntm/card-list ("Gót Son", "GITA CENTRE").
 *
 * @package NNTM
 */

if ( PHP_SAPI !== 'cli' ) {
	exit( 'Chi chay tu dong lenh.' );
}

$_SERVER['HTTP_HOST'] = 'nntm.com';
require_once __DIR__ . '/../wp-load.php';

/**
 * Tạo trang đích rỗng (nếu chưa có), publish sẵn.
 *
 * @param string $slug  Slug trang.
 * @param string $title Tiêu đề trang.
 * @return int ID trang.
 */
function nntm_seed_empty_page( string $slug, string $title ): int {
	$existing = get_page_by_path( $slug );
	if ( $existing ) {
		return (int) $existing->ID;
	}

	// SUY DOAN: Figma chỉ có frame tiêu đề cho 2 trang này, chưa dựng nội
	// dung chi tiết — để trống một đoạn văn placeholder, chờ phần việc
	// khác dựng layout thật khi có thiết kế.
	$id = wp_insert_post(
		array(
			'post_type'    => 'page',
			'post_status'  => 'publish',
			'post_title'   => $title,
			'post_name'    => $slug,
			'post_content' => '<!-- wp:paragraph --><p>Nội dung đang cập nhật.</p><!-- /wp:paragraph -->',
		)
	);

	return is_wp_error( $id ) ? 0 : (int) $id;
}

/* ---------- 1. Hai trang đích ---------- */

$dai_si_id    = nntm_seed_empty_page( 'dai-si-hanh-gia', 'Đại Sĩ Hành Giả' );
$kim_cuong_id = nntm_seed_empty_page( 'kim-cuong-hanh-gia', 'Kim Cương Hành Giả' );

echo "Trang dich:\n";
echo '  Dai Si Hanh Gia:    ' . ( $dai_si_id ? get_permalink( $dai_si_id ) : 'LOI - khong tao duoc' ) . "\n";
echo '  Kim Cuong Hanh Gia: ' . ( $kim_cuong_id ? get_permalink( $kim_cuong_id ) : 'LOI - khong tao duoc' ) . "\n";

/* ---------- 2. Nội dung block nntm/rank-card ---------- */

$bg_image_id    = 240;
$dai_si_img_id  = 239;
$kim_cuong_img  = 241;

$rank_card_attrs = array(
	'heading'    => 'Nhập Pháp Giới',
	'bgImageId'  => $bg_image_id,
	'bgImageUrl' => wp_get_attachment_url( $bg_image_id ) ?: '',
	'bgImageAlt' => '',
	'minHeight'  => 690,
	'cards'      => array(
		array(
			'imageId'        => $dai_si_img_id,
			'imageUrl'       => wp_get_attachment_url( $dai_si_img_id ) ?: '',
			'imageAlt'       => '',
			'title'          => 'Đại Sĩ Hành Giả',
			'ctaLabel'       => 'Mời vào',
			'targetUrl'      => $dai_si_id ? get_permalink( $dai_si_id ) : '',
			'requiredAccess' => 'login',
		),
		array(
			'imageId'        => $kim_cuong_img,
			'imageUrl'       => wp_get_attachment_url( $kim_cuong_img ) ?: '',
			'imageAlt'       => '',
			'title'          => 'Kim Cương Hành Giả',
			'ctaLabel'       => 'Mời vào',
			'targetUrl'      => $kim_cuong_id ? get_permalink( $kim_cuong_id ) : '',
			'requiredAccess' => 'login',
		),
	),
);

/* ---------- 3. Copy nguyên attrs "Gót Son" + "GITA CENTRE" từ trang chủ ---------- */

$home_post = get_post( 110 );
if ( ! $home_post ) {
	exit( "LOI: khong tim thay trang chu (ID 110).\n" );
}

$home_blocks   = parse_blocks( $home_post->post_content );
$got_son_attrs = null;
$gita_attrs    = null;

foreach ( $home_blocks as $home_block ) {
	if ( 'nntm/card-list' !== ( $home_block['blockName'] ?? '' ) ) {
		continue;
	}
	$attrs = $home_block['attrs'] ?? array();

	if ( null === $got_son_attrs && ( 'Gót Son' === ( $attrs['headingAbove'] ?? '' ) ) ) {
		$got_son_attrs = $attrs;
	} elseif ( null === $gita_attrs && false !== stripos( (string) ( $attrs['heading'] ?? '' ), 'gita' ) ) {
		$gita_attrs = $attrs;
	}
}

if ( null === $got_son_attrs ) {
	exit( "LOI: khong tim thay khoi card-list 'Got Son' tren trang chu.\n" );
}
if ( null === $gita_attrs ) {
	exit( "LOI: khong tim thay khoi card-list 'GITA CENTRE' tren trang chu.\n" );
}

/* ---------- 4. Ráp chuỗi block ---------- */

$content = nntm_home_block_pattern_content( 'nntm/rank-card', $rank_card_attrs )
	. "\n\n"
	. nntm_home_block_pattern_content( 'nntm/card-list', $got_son_attrs )
	. "\n\n"
	. nntm_home_block_pattern_content( 'nntm/card-list', $gita_attrs );

$page_id = 79;
$page    = get_post( $page_id );
if ( ! $page ) {
	exit( "LOI: khong tim thay trang 'nhap-phap-gioi' (ID {$page_id}).\n" );
}

wp_update_post(
	array(
		'ID'           => $page_id,
		'post_content' => $content,
	)
);

echo "\nDa ghi de trang 'Nhap Phap Gioi' (ID {$page_id}):\n";
echo "  1. nntm/rank-card — heading, anh nen 240, 2 the (anh 239 / 241), targetUrl toi 2 trang dich vua tao, requiredAccess=login.\n";
echo "  2. nntm/card-list — 'Got Son', attrs copy nguyen tu trang chu (ID 110).\n";
echo "  3. nntm/card-list — 'GITA CENTRE', attrs copy nguyen tu trang chu (ID 110).\n";
echo "\nXem trang: " . get_permalink( $page_id ) . "\n";
