<?php
/**
 * Dữ liệu mẫu local cho trang Liên Đàn. Chạy lặp lại an toàn.
 *
 * C:/xampp8_2/php/php.exe tools/seed-lien-dan.php
 *
 * @package NNTM
 */

if ( PHP_SAPI !== 'cli' ) {
	exit( 'Chỉ chạy từ dòng lệnh.' );
}

$_SERVER['HTTP_HOST']   = 'nntm.com';
$_SERVER['REQUEST_URI'] = '/lien-dan/';
require_once __DIR__ . '/../wp-load.php';

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
		$id = (int) $found[0]->ID;
		wp_update_post( array( 'ID' => $id, 'post_excerpt' => $excerpt ) );
		return $id;
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

$topics = array( 'khoa-tu' => 'Khoá Tu', 'lich-tu' => 'Lịch Tu' );
$topic_ids = array();
foreach ( $topics as $slug => $name ) {
	$term = get_term_by( 'slug', $slug, 'nntm_topic' );
	if ( ! $term ) {
		$result = wp_insert_term( $name, 'nntm_topic', array( 'slug' => $slug ) );
		if ( is_wp_error( $result ) ) {
			continue;
		}
		$topic_ids[ $slug ] = (int) $result['term_id'];
	} else {
		$topic_ids[ $slug ] = (int) $term->term_id;
	}
}

$groups = array(
	'khoa-tu' => array(
		array( 'Khoá tu Hè: Lễ Vu Lan 2026', 'Bảy ngày tu học, nuôi dưỡng lòng biết ơn và sự tỉnh thức.', 185 ),
		array( 'Khoá tu Mùa Xuân: An trú hiện tại', 'Năm ngày thực tập chánh niệm đầu năm mới.', 193 ),
		array( 'Khoá tu Thanh Thiếu Niên', 'Không gian học và thực tập dành cho người trẻ.', 192 ),
		array( 'Khoá tu Bát Quan Trai', 'Một ngày một đêm giữ tám giới, tập buông bỏ.', 190 ),
		array( 'Khoá tu Thiền Tứ Niệm Xứ', 'Bốn ngày im lặng, quan sát thân, thọ, tâm, pháp.', 189 ),
		array( 'Khoá tu Cuối Năm: Nhìn lại một năm', 'Ba ngày soi lại tâm mình trước thềm năm mới.', 123 ),
	),
	'lich-tu' => array(
		array( 'Lịch Tu Mùa Thu - Đợt 1', 'Thực tập bình an trong từng bước chân.', 193 ),
		array( 'Lịch Tu Mùa Thu - Đợt 2', 'Cùng trở về chăm sóc thân tâm.', 190 ),
		array( 'Lịch Tu Mùa Đông', 'Tĩnh lặng và sưởi ấm tình huynh đệ.', 192 ),
		array( 'Ngày tu Chánh Niệm', 'Một ngày sống sâu sắc trong hiện tại.', 189 ),
		array( 'Lịch Tu Đầu Xuân', 'Khởi đầu năm mới với tâm sáng trong.', 185 ),
	),
);

foreach ( $groups as $slug => $items ) {
	foreach ( $items as list( $title, $excerpt, $image_id ) ) {
		$id = nntm_seed_ld_post( 'nntm_retreat', $title, $excerpt );
		if ( $id ) {
			wp_set_object_terms( $id, array( $topic_ids[ $slug ] ), 'nntm_topic' );
			if ( get_post( $image_id ) ) {
				set_post_thumbnail( $id, $image_id );
			}
		}
	}
}

$tracks = array(
	array( 'Tiếng chuông sớm', 'Mở đầu một ngày tĩnh lặng.', 68 ),
	array( 'Suối nguồn tĩnh lặng', 'Âm thanh nhẹ cho thời thiền dài.', 69 ),
	array( 'Hơi thở an nhiên', 'Nhịp chậm dẫn theo hơi thở.', 70 ),
	array( 'Rừng khuya', 'Âm thanh thiên nhiên về đêm.', 68 ),
	array( 'Trăng trên đỉnh núi', 'Giai điệu thưa thoáng và bình an.', 69 ),
);
foreach ( $tracks as list( $title, $excerpt, $audio_id ) ) {
	$id = nntm_seed_ld_post( 'nntm_zen_track', $title, $excerpt );
	if ( $id && get_post( $audio_id ) ) {
		update_post_meta( $id, '_nntm_track_audio', $audio_id );
		$track_images = array( 184, 193, 192, 190, 185 );
		$image_id = $track_images[ array_search( $title, array_column( $tracks, 0 ), true ) % count( $track_images ) ];
		if ( get_post( $image_id ) ) {
			set_post_thumbnail( $id, $image_id );
		}
	}
}

$khoa_term = $topic_ids['khoa-tu'];
$lich_term = $topic_ids['lich-tu'];
$content = <<<HTML
<!-- wp:nntm/card-list {"heading":"Khoá Tu","postType":"nntm_retreat","taxonomy":"nntm_topic","termId":{$khoa_term},"variant":"khoa-tu","layout":"carousel","postsPerPage":6,"orderBy":"newest","showDate":false,"showCategory":false,"showViewAll":true,"viewAllLabel":"Xem tất cả","autoplay":true,"autoplayInterval":6,"className":"nntm-lien-dan-khoa"} /-->

<!-- wp:nntm/card-list {"heading":"Lịch Tu","postType":"nntm_retreat","taxonomy":"nntm_topic","termId":{$lich_term},"variant":"khoa-tu","layout":"carousel","postsPerPage":5,"orderBy":"newest","showDate":false,"showCategory":false,"autoplay":true,"autoplayInterval":6,"className":"nntm-lien-dan-lich"} /-->

<!-- wp:nntm/thien-duong {"heading":"Thiền Đường","subheading":"","coverImageId":184,"tracksPerPage":20,"orderBy":"newest"} /-->
HTML;

$page = get_page_by_path( 'lien-dan' );
$args = array(
	'post_type'    => 'page',
	'post_status'  => 'publish',
	'post_title'   => 'Liên Đàn',
	'post_name'    => 'lien-dan',
	'post_content' => $content,
);
if ( $page ) {
	$args['ID'] = $page->ID;
	$page_id = wp_update_post( $args );
} else {
	$page_id = wp_insert_post( $args );
}

echo 'Liên Đàn: ' . get_permalink( $page_id ) . PHP_EOL;
