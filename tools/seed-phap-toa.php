<?php
/**
 * Seed trang Pháp Tòa + trang chi tiết truyền thống cho môi trường phát triển.
 *
 * Dựng theo Figma "03. PHAP TOA" và "03. PHAP TOA - NGUYEN THUY".
 * Cấu trúc dữ liệu theo docs/04-kien-truc.md mục 10: 4 truyền thống là
 * TERM CON của term "Pháp Tòa" trong taxonomy nntm_section.
 *
 *   "C:/xampp8_2/php/php.exe" tools/seed-phap-toa.php
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

/* ---------- 1. Bốn truyền thống là term con của "Pháp Tòa" ---------- */

$parent = get_term_by( 'slug', 'phap-toa', 'nntm_section' );
if ( ! $parent ) {
	exit( "Khong tim thay term cha 'phap-toa'. Kich hoat lai plugin nntm-core truoc.\n" );
}
echo "Term cha: {$parent->name} (ID {$parent->term_id})\n";

$traditions = array(
	'nguyen-thuy' => array( 'Nguyên Thuỷ', 'Phật Giáo Nguyên Thủy - Nền Tảng Để Thành Tựu Phật Quả.' ),
	'dai-thua'    => array( 'Đại Thừa', 'Con đường Bồ Tát hạnh, lấy độ sinh làm bản nguyện.' ),
	'tinh-do'     => array( 'Tịnh Độ', 'Nương tựa bản nguyện, nhất tâm niệm Phật cầu sinh.' ),
	'mat-tong'    => array( 'Mật Tông', 'Pháp môn phương tiện thiện xảo, thân khẩu ý hợp nhất.' ),
);

$term_ids = array();
foreach ( $traditions as $slug => list( $name, $desc ) ) {
	$existing = get_term_by( 'slug', $slug, 'nntm_section' );
	if ( $existing ) {
		wp_update_term( $existing->term_id, 'nntm_section', array( 'description' => $desc, 'parent' => $parent->term_id ) );
		$term_ids[ $slug ] = $existing->term_id;
		echo "  Da co: {$name}\n";
		continue;
	}
	$res = wp_insert_term(
		$name,
		'nntm_section',
		array( 'slug' => $slug, 'description' => $desc, 'parent' => $parent->term_id )
	);
	if ( is_wp_error( $res ) ) {
		echo "  LOI {$name}: " . $res->get_error_message() . "\n";
		continue;
	}
	$term_ids[ $slug ] = $res['term_id'];
	echo "  Tao moi: {$name}\n";
}

/* ---------- 2. Bài viết mẫu gán vào Nguyên Thuỷ ---------- */

if ( isset( $term_ids['nguyen-thuy'] ) ) {
	$samples = array(
		'Bài Viết về Nguyên Thuỷ 1' => 'Tứ Diệu Đế là nền tảng đầu tiên Đức Phật tuyên thuyết tại vườn Lộc Uyển, mở ra con đường thoát khổ cho muôn loài.',
		'Bài Viết về Nguyên Thuỷ 2' => 'Bát Chánh Đạo không phải tám bước nối tiếp, mà là tám mặt của cùng một đời sống tỉnh thức.',
		'Bài Viết về Nguyên Thuỷ 3' => 'Thiền Tứ Niệm Xứ đưa hành giả trở về quán sát thân, thọ, tâm, pháp ngay trong từng khoảnh khắc.',
		'Bài Viết về Nguyên Thuỷ 4' => 'Giới luật không phải sự trói buộc, mà là hàng rào giữ cho tâm được yên trong lúc tu tập.',
	);

	foreach ( $samples as $title => $excerpt ) {
		$found = get_posts(
			array(
				'post_type'      => 'nntm_article',
				'post_status'    => 'any',
				'title'          => $title,
				'posts_per_page' => 1,
				'no_found_rows'  => true,
			)
		);
		if ( $found ) {
			$id = $found[0]->ID;
			echo "  Da co bai: {$title}\n";
		} else {
			$id = wp_insert_post(
				array(
					'post_type'    => 'nntm_article',
					'post_status'  => 'publish',
					'post_title'   => $title,
					'post_excerpt' => $excerpt,
					'post_content' => '<!-- wp:paragraph --><p>' . esc_html( $excerpt ) . '</p><!-- /wp:paragraph -->',
				)
			);
			echo "  Tao bai: {$title}\n";
		}
		if ( $id && ! is_wp_error( $id ) ) {
			wp_set_object_terms( $id, array( (int) $term_ids['nguyen-thuy'] ), 'nntm_section' );
		}
	}
}

/* ---------- 3. Trang ---------- */

/**
 * Tạo hoặc cập nhật một trang theo slug.
 *
 * @param string $slug    Đường dẫn tĩnh.
 * @param string $title   Tiêu đề.
 * @param string $content Nội dung block.
 */
function nntm_seed_page( string $slug, string $title, string $content ): void {
	$existing = get_page_by_path( $slug );
	$args     = array(
		'post_type'    => 'page',
		'post_status'  => 'publish',
		'post_title'   => $title,
		'post_name'    => $slug,
		'post_content' => $content,
	);
	if ( $existing ) {
		$args['ID'] = $existing->ID;
		wp_update_post( $args );
		echo "Cap nhat trang: {$title} -> " . get_permalink( $existing->ID ) . "\n";
		return;
	}
	$id = wp_insert_post( $args );
	echo "Tao trang: {$title} -> " . get_permalink( $id ) . "\n";
}

// Trang Pháp Tòa — liệt kê 4 truyền thống.
nntm_seed_page(
	'phap-toa',
	'Pháp Toà',
	'<!-- wp:nntm/term-list {"heading":"Pháp Toà","parentTermId":' . (int) $parent->term_id . ',"showDescription":true,"ctaLabel":"Xem thêm"} /-->'
);

// Trang chi tiết Nguyên Thuỷ — danh sách bài xếp so le.
if ( isset( $term_ids['nguyen-thuy'] ) ) {
	nntm_seed_page(
		'nguyen-thuy',
		'Nguyên Thuỷ',
		'<!-- wp:nntm/article-rows {"heading":"Nguyên Thuỷ","postType":"nntm_article","taxonomy":"nntm_section","termId":'
			. (int) $term_ids['nguyen-thuy']
			. ',"postsPerPage":4,"orderBy":"oldest","startSide":"left"} /-->'
	);
}

/* ---------- 4. Thứ tự hiển thị đúng như Figma ---------- */

$order = array( 'nguyen-thuy' => 1, 'dai-thua' => 2, 'tinh-do' => 3, 'mat-tong' => 4 );
foreach ( $order as $slug => $num ) {
	if ( isset( $term_ids[ $slug ] ) ) {
		update_term_meta( $term_ids[ $slug ], '_nntm_term_order', $num );
	}
}
echo "Da dat thu tu hien thi theo Figma\n";
