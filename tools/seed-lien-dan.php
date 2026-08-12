<?php
/**
 * Seed trang Liên Đàn cho môi trường phát triển.
 *
 * Dựng theo Figma "04. LIEN DAN": SECTION 6 "Khoá Tu" (lưới thẻ 388×429),
 * SECTION 7 "Lịch Tu" (băng cuộn ngang), SECTION 5 "Thiền Đường".
 *
 * Khoá Tu và Lịch Tu tách nhau bằng chủ đề (taxonomy nntm_topic) để ban
 * quản trị tự đổi được, không cần lập trình viên.
 *
 *   "C:/xampp8_2/php/php.exe" tools/seed-lien-dan.php
 *
 * Chạy nhiều lần được, không tạo trùng. CHỈ dùng ở local.
 *
 * @package NNTM
 */

if ( PHP_SAPI !== 'cli' ) {
	exit( 'Chi chay tu dong lenh.' );
}

$_SERVER['HTTP_HOST'] = 'localhost:8080';
require_once __DIR__ . '/../wp-load.php';

/**
 * Tạo bài nếu chưa có, trả về ID.
 *
 * @param string $type    Loại nội dung.
 * @param string $title   Tiêu đề.
 * @param string $excerpt Mô tả ngắn.
 * @return int
 */
function nntm_seed_ld_post( string $type, string $title, string $excerpt ): int {
	$found = get_posts(
		array(
			'post_type'      => $type,
			'post_status'    => 'any',
			'title'          => $title,
			'posts_per_page' => 1,
			'no_found_rows'  => true,
		)
	);
	if ( $found ) {
		return (int) $found[0]->ID;
	}

	$id = wp_insert_post(
		array(
			'post_type'    => $type,
			'post_status'  => 'publish',
			'post_title'   => $title,
			'post_excerpt' => $excerpt,
			'post_content' => '<!-- wp:paragraph --><p>' . esc_html( $excerpt ) . '</p><!-- /wp:paragraph -->',
		)
	);

	return is_wp_error( $id ) ? 0 : (int) $id;
}

/* ---------- 1. Chủ đề tách Khoá Tu / Lịch Tu ---------- */

$topics  = array(
	'khoa-tu' => 'Khoá Tu',
	'lich-tu' => 'Lịch Tu',
);
$topic_ids = array();

foreach ( $topics as $slug => $name ) {
	$term = get_term_by( 'slug', $slug, 'nntm_topic' );
	if ( ! $term ) {
		$res = wp_insert_term( $name, 'nntm_topic', array( 'slug' => $slug ) );
		if ( is_wp_error( $res ) ) {
			echo "LOI chu de {$name}: " . $res->get_error_message() . "\n";
			continue;
		}
		$topic_ids[ $slug ] = (int) $res['term_id'];
		echo "Tao chu de: {$name}\n";
	} else {
		$topic_ids[ $slug ] = (int) $term->term_id;
		echo "Da co chu de: {$name}\n";
	}
}

/* ---------- 2. Khoá Tu (6 thẻ, lưới 3 cột 2 hàng như Figma) ---------- */

$retreats = array(
	'khoa-tu' => array(
		array( 'Khoá tu Hè: Lễ Vu Lan 2026', 'Bảy ngày tu học tại Trú Xứ Nha Trang, dành cho mọi lứa tuổi.' ),
		array( 'Khoá tu Mùa Xuân: An trú trong hiện tại', 'Năm ngày thực tập chánh niệm đầu năm mới.' ),
		array( 'Khoá tu Thanh Thiếu Niên', 'Dành cho các bạn trẻ từ 15 đến 25 tuổi, học cách sống chậm lại.' ),
		array( 'Khoá tu Bát Quan Trai', 'Một ngày một đêm giữ tám giới, tập buông bớt.' ),
		array( 'Khoá tu Thiền Tứ Niệm Xứ', 'Mười ngày im lặng, quán sát thân thọ tâm pháp.' ),
		array( 'Khoá tu Cuối Năm: Nhìn lại một năm', 'Ba ngày soi lại tâm mình trước thềm năm mới.' ),
	),
	'lich-tu' => array(
		array( 'Ngày vía Đức Phật Thích Ca thành đạo', 'Mùng 8 tháng Chạp âm lịch — toạ thiền và tụng kinh suốt đêm.' ),
		array( 'Đại lễ Phật Đản', 'Rằm tháng Tư âm lịch — lễ tắm Phật và đêm hoa đăng.' ),
		array( 'Lễ Vu Lan báo hiếu', 'Rằm tháng Bảy âm lịch — cài hoa hồng và cúng dường trai tăng.' ),
		array( 'Ngày vía Đức Quán Thế Âm', 'Ba dịp trong năm, tụng kinh Phổ Môn và phóng sanh.' ),
		array( 'Ngày sám hối định kỳ', 'Mười bốn và ba mươi âm lịch hằng tháng.' ),
	),
);

foreach ( $retreats as $slug => $items ) {
	if ( ! isset( $topic_ids[ $slug ] ) ) {
		continue;
	}
	foreach ( $items as list( $title, $excerpt ) ) {
		$id = nntm_seed_ld_post( 'nntm_retreat', $title, $excerpt );
		if ( $id ) {
			wp_set_object_terms( $id, array( $topic_ids[ $slug ] ), 'nntm_topic' );
		}
	}
	echo "{$topics[ $slug ]}: " . count( $items ) . " muc\n";
}

/* ---------- 3. Nhạc thiền cho Thiền Đường ---------- */

$tracks = array(
	array( 'Tiếng chuông sớm', 'Chuông ngân trong sương, mở đầu một ngày tĩnh lặng.' ),
	array( 'Suối nguồn tĩnh lặng', 'Tiếng nước chảy đều, thích hợp cho toạ thiền dài.' ),
	array( 'Hơi thở an nhiên', 'Nhịp chậm, dẫn dắt theo hơi thở vào ra.' ),
	array( 'Rừng khuya', 'Âm thanh thiên nhiên về đêm, không lời.' ),
	array( 'Trăng trên đỉnh núi', 'Sáo trúc và đàn tranh, giai điệu thưa thoáng.' ),
);

foreach ( $tracks as list( $title, $excerpt ) ) {
	nntm_seed_ld_post( 'nntm_zen_track', $title, $excerpt );
}
echo 'Nhac thien: ' . count( $tracks ) . " bai\n";

/* ---------- 4. Trang Liên Đàn ---------- */

$content = <<<HTML
<!-- wp:nntm/card-list {"heading":"Khoá Tu","postType":"nntm_retreat","taxonomy":"nntm_topic","termId":{$topic_ids['khoa-tu']},"variant":"khoa-tu","columns":3,"postsPerPage":6,"orderBy":"newest"} /-->

<!-- wp:nntm/card-list {"heading":"Lịch Tu","postType":"nntm_retreat","taxonomy":"nntm_topic","termId":{$topic_ids['lich-tu']},"variant":"khoa-tu","layout":"carousel","postsPerPage":5,"orderBy":"newest"} /-->

<!-- wp:nntm/thien-duong {"heading":"Thiền Đường","subheading":"Không gian nghe nhạc thiền dành cho thành viên, tự chọn bài, nghe lúc nào cũng được.","tracksPerPage":20,"orderBy":"newest"} /-->
HTML;

$slug     = 'lien-dan';
$existing = get_page_by_path( $slug );
$args     = array(
	'post_type'    => 'page',
	'post_status'  => 'publish',
	'post_title'   => 'Liên Đàn',
	'post_name'    => $slug,
	'post_content' => $content,
);

if ( $existing ) {
	$args['ID'] = $existing->ID;
	wp_update_post( $args );
	echo "\nCap nhat trang Lien Dan -> " . get_permalink( $existing->ID ) . "\n";
} else {
	$id = wp_insert_post( $args );
	echo "\nTao trang Lien Dan -> " . get_permalink( $id ) . "\n";
}
