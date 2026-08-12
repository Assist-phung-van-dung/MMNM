<?php
/**
 * Seed trang Hoa Khai cho môi trường phát triển.
 *
 * Dựng theo Figma "05. HOA KHAI": SECTION 1 "Hoằng Pháp" + SECTION 4
 * "Tin tức" (bố cục tạp chí) + SECTION 5 "Ấn Phẩm" (băng cuộn ngang).
 *
 * Theo docs/04-kien-truc.md mục 3: Tin Tức và Hoằng Pháp dùng loại bài
 * viết có sẵn của WordPress với chuyên mục riêng, KHÔNG đẻ thêm CPT.
 * Ấn Phẩm dùng CPT nntm_publication.
 *
 *   "C:/xampp8_2/php/php.exe" tools/seed-hoa-khai.php
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
function nntm_seed_post( string $type, string $title, string $excerpt ): int {
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

/* ---------- 1. Chuyên mục Hoằng Pháp & Tin Tức (cho loại bài viết có sẵn) ---------- */

$cats = array(
	'hoang-phap' => 'Hoằng Pháp',
	'tin-tuc'    => 'Tin Tức',
);

$cat_ids = array();
foreach ( $cats as $slug => $name ) {
	$term = get_term_by( 'slug', $slug, 'category' );
	if ( ! $term ) {
		$res = wp_insert_term( $name, 'category', array( 'slug' => $slug ) );
		if ( is_wp_error( $res ) ) {
			echo "LOI chuyen muc {$name}: " . $res->get_error_message() . "\n";
			continue;
		}
		$cat_ids[ $slug ] = (int) $res['term_id'];
		echo "Tao chuyen muc: {$name}\n";
	} else {
		$cat_ids[ $slug ] = (int) $term->term_id;
		echo "Da co chuyen muc: {$name}\n";
	}
}

/* ---------- 2. Bài mẫu — mỗi chuyên mục 6 bài để bố cục tạp chí đủ chỗ ---------- */

$articles = array(
	'hoang-phap' => array(
		array( 'Chúng sanh tranh đấu và đau khổ do đâu?'),
		array( 'Kiến tạo giá trị cho cộng đồng từ sự tỉnh thức và yêu thương chân thành.'),
		array( 'Kiến tạo giá trị cho cộng đồng từ sự tỉnh thức và yêu thương chân thành.'),
		array( 'Kiến tạo giá trị cho cộng đồng từ sự tỉnh thức và yêu thương chân thành.', 'Nhẫn là giữ được tâm bình trước nghịch cảnh, khác hẳn với nén giận vào trong.' ),
		array( 'Kiến tạo giá trị cho cộng đồng từ sự tỉnh thức và yêu thương chân thành.', 'Có những câu hỏi mà trả lời chỉ làm dày thêm vọng tưởng.' ),
		array( 'Kiến tạo giá trị cho cộng đồng từ sự tỉnh thức và yêu thương chân thành.', 'Người quanh ta tin vào điều ta sống, chứ hiếm khi tin vào điều ta nói.' ),
	),
	'tin-tuc'    => array(
		array( 'Đại lễ Phật Đản 2026 tại Trú Xứ Nha Trang', 'Chương trình kéo dài ba ngày với lễ tắm Phật, thuyết pháp và đêm hoa đăng.' ),
		array( 'Khai giảng khoá tu mùa hè cho thanh thiếu niên', 'Khoá tu bảy ngày dành cho các bạn trẻ từ 15 đến 25 tuổi.' ),
		array( 'Ra mắt ấn phẩm mới của Tôn Sư', 'Bản in giới hạn được phát hành nhân dịp cuối năm.' ),
		array( 'Trú Xứ Bodh Gaya hoàn thành trùng tu chánh điện', 'Công trình kéo dài mười tám tháng với sự góp sức của Phật tử bốn phương.' ),
		array( 'Chương trình từ thiện mùa mưa miền Trung', 'Hơn hai nghìn phần quà đã được trao tận tay bà con vùng ngập.' ),
		array( 'Thư viện số mở cửa cho thành viên Đại Sĩ', 'Toàn bộ ấn phẩm đã số hoá nay có thể đọc trực tuyến.' ),
	),
);

foreach ( $articles as $slug => $items ) {
	if ( ! isset( $cat_ids[ $slug ] ) ) {
		continue;
	}
	foreach ( $items as list( $title, $excerpt ) ) {
		$id = nntm_seed_post( 'post', $title, $excerpt );
		if ( $id ) {
			wp_set_post_categories( $id, array( $cat_ids[ $slug ] ) );
		}
	}
	echo "Bai chuyen muc {$cats[ $slug ]}: " . count( $items ) . "\n";
}

/* ---------- 3. Ấn Phẩm (CPT nntm_publication) ---------- */

$publications = array(
	array( 'Biểu Tượng và Hoa Văn Mật Tông', '100 cuốn bản giới hạn bìa xanh.' ),
	array( 'Tuyển Tập Pháp Thoại Tôn Sư — Quyển I', 'Ghi chép từ các buổi giảng trong ba năm gần nhất.' ),
	array( 'Nghi Quỹ Tu Trì Hằng Ngày', 'Bản song ngữ Việt — Phạn, có phiên âm.' ),
	array( 'Chú Giải Kinh Kim Cang', 'Bản chú giải dành cho người mới bắt đầu.' ),
	array( 'Hành Trạng Chư Tổ', 'Tiểu sử và hành trạng các vị Tổ sư qua các thời kỳ.' ),
	array( 'Sổ Tay Công Phu', 'Dành cho thành viên tham gia khu Cộng Tu.' ),
);

foreach ( $publications as list( $title, $excerpt ) ) {
	nntm_seed_post( 'nntm_publication', $title, $excerpt );
}
echo 'An Pham: ' . count( $publications ) . "\n";

/* ---------- 4. Trang Hoa Khai ---------- */

$content = <<<HTML
<!-- wp:nntm/article-mosaic {"heading":"Hoằng Pháp","postType":"post","taxonomy":"category","termId":{$cat_ids['hoang-phap']},"leadMedia":"tall","orderBy":"newest"} /-->

<!-- wp:nntm/article-mosaic {"heading":"Tin tức","postType":"post","taxonomy":"category","termId":{$cat_ids['tin-tuc']},"leadMedia":"short","orderBy":"newest"} /-->

<!-- wp:nntm/card-list {"heading":"Ấn Phẩm","subheading":"Danh sách các ấn phẩm của Tôn Sư và Thầy","postType":"nntm_publication","variant":"books","layout":"carousel","postsPerPage":6,"orderBy":"newest"} /-->
HTML;

$slug     = 'hoa-khai';
$existing = get_page_by_path( $slug );
$args     = array(
	'post_type'    => 'page',
	'post_status'  => 'publish',
	'post_title'   => 'Hoa Khai',
	'post_name'    => $slug,
	'post_content' => $content,
);

if ( $existing ) {
	$args['ID'] = $existing->ID;
	wp_update_post( $args );
	echo "\nCap nhat trang Hoa Khai -> " . get_permalink( $existing->ID ) . "\n";
} else {
	$id = wp_insert_post( $args );
	echo "\nTao trang Hoa Khai -> " . get_permalink( $id ) . "\n";
}
