<?php
/**
 * Seed trang chủ theo Figma "DESKTOP - R4 (UPDATED 6AUG)" / 01_HOMEPAGE
 * (node 6376:6322).
 *
 * Trang chủ gồm 9 khối; 7 khối dùng lại block đã có, chỉ băng chuyền đầu
 * trang là block mới (nntm/hero-slider).
 *
 *   "C:/xampp8_2/php/php.exe" tools/seed-trang-chu.php
 *
 * Chạy nhiều lần được, không tạo trùng. CHỈ dùng ở local.
 *
 * ẢNH: nạp từ thư mục ảnh mẫu tự sinh (dải màu theo bảng màu dự án) để
 * trang có hình mà xem. Khi khách gửi ảnh thật thì thay trong Thư viện.
 *
 * @package NNTM
 */

if ( PHP_SAPI !== 'cli' ) {
	exit( 'Chi chay tu dong lenh.' );
}

$_SERVER['HTTP_HOST'] = 'localhost:8080';
require_once __DIR__ . '/../wp-load.php';
require_once ABSPATH . 'wp-admin/includes/image.php';
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/media.php';

/** Thư mục chứa ảnh mẫu tự sinh. */
const NNTM_SEED_IMG_DIR = 'C:/Users/PHUNG_VAN_DUNG/AppData/Local/Temp/claude/C--xampp8-2-htdocs-NNTM/00b90797-9688-4862-8130-a92e5ae70f58/scratchpad/gen';

/**
 * Nạp một ảnh vào Thư viện, trả về ID. Đã có thì dùng lại.
 *
 * @param string $file  Tên tệp trong thư mục ảnh mẫu.
 * @param string $title Tiêu đề hiển thị trong Thư viện.
 * @return int
 */
function nntm_seed_image( string $file, string $title ): int {
	$found = get_posts(
		array(
			'post_type'      => 'attachment',
			'post_status'    => 'inherit',
			'title'          => $title,
			'posts_per_page' => 1,
			'no_found_rows'  => true,
		)
	);
	if ( $found ) {
		return (int) $found[0]->ID;
	}

	$src = NNTM_SEED_IMG_DIR . '/' . $file;
	if ( ! file_exists( $src ) ) {
		echo "  THIEU anh: {$src}\n";
		return 0;
	}

	$up   = wp_upload_dir();
	$dest = trailingslashit( $up['path'] ) . $file;
	if ( ! copy( $src, $dest ) ) {
		echo "  Khong chep duoc: {$file}\n";
		return 0;
	}

	$id = wp_insert_attachment(
		array(
			'post_mime_type' => 'image/png',
			'post_title'     => $title,
			'post_status'    => 'inherit',
		),
		$dest
	);
	if ( is_wp_error( $id ) || ! $id ) {
		return 0;
	}
	wp_update_attachment_metadata( $id, wp_generate_attachment_metadata( $id, $dest ) );
	echo "  Nap anh: {$title}\n";

	return (int) $id;
}

/**
 * Tạo bài nếu chưa có, gán ảnh đại diện.
 *
 * @param string $type     Loại nội dung.
 * @param string $title    Tiêu đề.
 * @param string $excerpt  Mô tả ngắn.
 * @param int    $thumb_id ID ảnh đại diện.
 * @return int
 */
function nntm_seed_item( string $type, string $title, string $excerpt, int $thumb_id = 0 ): int {
	$found = get_posts(
		array(
			'post_type'      => $type,
			'post_status'    => 'any',
			'title'          => $title,
			'posts_per_page' => 1,
			'no_found_rows'  => true,
		)
	);
	$id = $found ? (int) $found[0]->ID : 0;

	if ( ! $id ) {
		$id = wp_insert_post(
			array(
				'post_type'    => $type,
				'post_status'  => 'publish',
				'post_title'   => $title,
				'post_excerpt' => $excerpt,
				'post_content' => '<!-- wp:paragraph --><p>' . esc_html( $excerpt ) . '</p><!-- /wp:paragraph -->',
			)
		);
		$id = is_wp_error( $id ) ? 0 : (int) $id;
	}

	if ( $id && $thumb_id && ! has_post_thumbnail( $id ) ) {
		set_post_thumbnail( $id, $thumb_id );
	}

	return $id;
}

/* ---------- 1. Ảnh ---------- */

echo "Nap anh mau:\n";
$hero_ids = array();
foreach ( array( 1, 2, 3 ) as $i ) {
	$hero_ids[] = nntm_seed_image( "hero-{$i}.png", "Anh bia trang chu {$i}" );
}
$thumb_ids = array();
foreach ( range( 1, 8 ) as $i ) {
	$thumb_ids[] = nntm_seed_image( "thumb-{$i}.png", "Anh minh hoa {$i}" );
}

/* ---------- 2. Video cho hai băng cuộn ---------- */

$series = array(
	'got-son'     => 'Gót Son — Xuyên Vạn Kiếp',
	'gita-center' => 'GITA Center',
);
$series_ids = array();
foreach ( $series as $slug => $name ) {
	$term = get_term_by( 'slug', $slug, 'nntm_series' );
	if ( ! $term ) {
		$res = wp_insert_term( $name, 'nntm_series', array( 'slug' => $slug ) );
		$series_ids[ $slug ] = is_wp_error( $res ) ? 0 : (int) $res['term_id'];
		echo "Tao bo: {$name}\n";
	} else {
		$series_ids[ $slug ] = (int) $term->term_id;
	}
}

$videos = array(
	'got-son'     => array(
		'Tập 18 — Chân Sư Hiện Thánh Tướng Phương Tiện Ký',
		'Tập 17 — Đồng Chơn Vi Diệu Hạnh, Bồ Tát Đại Ứng Linh',
		'Tập 16 — Chân Sư Hiện Đại Bi Thần Lực Ký',
		'Tập 15 — Đại Nghiệp Hải Tiêu Dung, Chuyển Thức Thành Trí',
		'Tập 14 — Vô Uý Thí Giữa Đời Trược',
		'Tập 13 — Nhất Niệm Hồi Quang',
	),
	'gita-center' => array(
		'Lắng nghe tiếng lòng — GITA Center x Nẵng Nhân Tịch Mặc',
		'Đường về — Bản phối đương đại',
		'Tiếng chuông chiều — Live Session',
		'Một đoá vô ưu — Official Performance',
		'Gió qua miền tĩnh lặng',
		'Đêm nhạc Phật giáo đương đại 2026',
	),
);

foreach ( $videos as $slug => $titles ) {
	foreach ( $titles as $k => $title ) {
		$id = nntm_seed_item( 'nntm_video', $title, 'Video thuộc bộ ' . $series[ $slug ] . '.', $thumb_ids[ $k % count( $thumb_ids ) ] ?? 0 );
		if ( $id && ! empty( $series_ids[ $slug ] ) ) {
			wp_set_object_terms( $id, array( $series_ids[ $slug ] ), 'nntm_series' );
		}
	}
	echo "Video bo {$series[ $slug ]}: " . count( $titles ) . "\n";
}

/* ---------- 3. Gán ảnh cho bài viết đã có (để lưới không toàn ô xám) ---------- */

$no_thumb = get_posts(
	array(
		'post_type'      => array( 'post', 'nntm_article', 'nntm_publication', 'nntm_retreat', 'nntm_abode' ),
		'posts_per_page' => 40,
		'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- chay 1 lan o local.
			array(
				'key'     => '_thumbnail_id',
				'compare' => 'NOT EXISTS',
			),
		),
	)
);
foreach ( $no_thumb as $k => $p ) {
	if ( ! empty( $thumb_ids[ $k % count( $thumb_ids ) ] ) ) {
		set_post_thumbnail( $p->ID, $thumb_ids[ $k % count( $thumb_ids ) ] );
	}
}
echo 'Da gan anh cho ' . count( $no_thumb ) . " bai chua co anh\n";

/* ---------- 4. Trang chủ ---------- */

$phap_toa = get_term_by( 'slug', 'phap-toa', 'nntm_section' );
$hoang_phap = get_term_by( 'slug', 'hoang-phap', 'category' );
$tin_tuc  = get_term_by( 'slug', 'tin-tuc', 'category' );

$slides = array();
$slide_text = array(
	array( 'Từ bi trong hành động, tĩnh lặng trong tâm hồn.', 'Thực hành yêu thương bằng trí tuệ, nuôi dưỡng bình an từ nội tâm.' ),
	array( 'Mỗi bước chân là một lần trở về.', 'Chánh niệm không ở đâu xa, nó nằm trong hơi thở kế tiếp.' ),
	array( 'Nghe được tiếng lòng mình, mới nghe được tiếng đời.', 'Tĩnh lặng là nơi trí tuệ bắt đầu lên tiếng.' ),
);
foreach ( $hero_ids as $k => $img_id ) {
	if ( ! $img_id ) {
		continue;
	}
	$slides[] = array(
		'imageId'  => $img_id,
		'imageUrl' => wp_get_attachment_image_url( $img_id, 'full' ),
		'imageAlt' => '',
		'heading'  => $slide_text[ $k ][0],
		'text'     => $slide_text[ $k ][1],
		'ctaLabel' => 'Xem thêm',
		'ctaUrl'   => $phap_toa ? get_term_link( $phap_toa ) : home_url( '/' ),
	);
}

$hero_attrs = wp_json_encode(
	array(
		'slides'                 => $slides,
		'autoplay'               => true,
		'interval'               => 6,
		'sideCardHeading'        => 'Kiến tạo giá trị cho cộng đồng từ sự tỉnh thức và yêu thương chân thành.',
		'sideCardText'           => 'Những việc làm nhỏ, bền bỉ, không ồn ào.',
		'sideCardCtaLabel'       => 'Xem thêm',
		'sideCardCtaUrl'         => home_url( '/hoa-khai/' ),
		'quickLinksParentTermId' => $phap_toa ? (int) $phap_toa->term_id : 0,
	),
	JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
);

$mosaic1 = wp_json_encode(
	array(
		'heading'    => 'Chúng sanh tranh đấu và đau khổ do đâu?',
		'postType'   => 'post',
		'taxonomy'   => 'category',
		'termId'     => $hoang_phap ? (int) $hoang_phap->term_id : 0,
		'leadMedia'  => 'tall',
		'orderBy'    => 'newest',
		'ctaLabel'   => 'Xem thêm',
		'viewAllLabel' => 'Xem Tất cả',
		'viewAllUrl' => $hoang_phap ? get_term_link( $hoang_phap ) : home_url( '/' ),
	),
	JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
);

$mosaic2 = wp_json_encode(
	array(
		'heading'         => 'Hoạt động - Sự kiện',
		'postType'        => 'post',
		'taxonomy'        => 'category',
		'termId'          => $tin_tuc ? (int) $tin_tuc->term_id : 0,
		'leadMedia'       => 'short',
		'secondaryLayout' => 'grid',
		'orderBy'         => 'newest',
		'showDate'        => false,
		'showCategory'    => false,
		'showExcerpt'     => true,
		'ctaLabel'        => 'Xem thêm',
		'viewAllLabel'    => 'Xem Tất cả',
		'viewAllUrl'      => $tin_tuc ? get_term_link( $tin_tuc ) : home_url( '/' ),
	),
	JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
);

$got_son = wp_json_encode(
	array(
		'heading'      => 'Gót Son',
		'subheading'   => 'Xuyên Vạn Kiếp',
		'postType'     => 'nntm_video',
		'taxonomy'     => 'nntm_series',
		'termId'       => (int) ( $series_ids['got-son'] ?? 0 ),
		'variant'      => 'video',
		'layout'       => 'carousel',
		'postsPerPage' => 6,
		'orderBy'      => 'newest',
	),
	JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
);

$gita = wp_json_encode(
	array(
		'heading'      => 'GITA CENTRE x NĂNG NHÂN TỊCH MẶC',
		'subheading'   => 'Sự kết hợp lan tỏa, ÂM NHẠC PHẬT GIÁO ĐƯƠNG ĐẠI, một làn gió mới của nhạc Phật giáo......',
		'postType'     => 'nntm_video',
		'taxonomy'     => 'nntm_series',
		'termId'       => (int) ( $series_ids['gita-center'] ?? 0 ),
		'variant'      => 'video',
		'layout'       => 'carousel',
		'postsPerPage' => 6,
		'orderBy'      => 'newest',
		'background'   => 'cam',
		'showDate'     => false,
		'showCategory' => false,
		'spotifyUrl'   => 'https://open.spotify.com/intl-fr/album/3RJyfEki1woOJz1Ap3zCs8',
		'youtubeUrl'   => 'https://www.youtube.com/@Gita.centre',
	),
	JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
);

$drum = wp_json_encode(
	array(
		'eyebrow'       => 'Video',
		'heading'       => 'The Drum of the True Dharma',
		'content'       => 'Điện ảnh hoá Phật pháp — những câu chuyện về Đức Phật được kể lại một cách chân thật, gần gũi với người xem hôm nay.',
		'mediaPosition' => 'left',
	),
	JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
);

$content = <<<HTML
<!-- wp:nntm/hero-slider {$hero_attrs} /-->

<!-- wp:nntm/article-mosaic {$mosaic1} /-->

<!-- wp:nntm/card-list {$got_son} /-->

<!-- wp:nntm/feature {$drum} /-->

<!-- wp:nntm/article-mosaic {$mosaic2} /-->

<!-- wp:nntm/card-list {$gita} /-->
HTML;

$slug     = 'trang-chu';
$existing = get_page_by_path( $slug );
$args     = array(
	'post_type'    => 'page',
	'post_status'  => 'publish',
	'post_title'   => 'Trang chủ',
	'post_name'    => $slug,
	'post_content' => $content,
);

if ( $existing ) {
	$args['ID'] = $existing->ID;
	wp_update_post( $args );
	$page_id = (int) $existing->ID;
	echo "\nCap nhat trang chu\n";
} else {
	$page_id = (int) wp_insert_post( $args );
	echo "\nTao trang chu\n";
}

// Đặt làm trang chủ của site.
update_option( 'show_on_front', 'page' );
update_option( 'page_on_front', $page_id );

echo 'Trang chu: ' . get_permalink( $page_id ) . "\n";
echo "Da dat lam trang chu cua site (Cai dat -> Doc)\n";
