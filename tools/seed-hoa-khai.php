<?php
/** Dữ liệu mẫu và cấu hình block trang Hoa Khai. Chạy lặp lại an toàn. */
if ( PHP_SAPI !== 'cli' ) { exit( 'Chỉ chạy từ dòng lệnh.' ); }
$_SERVER['HTTP_HOST'] = 'nntm.com'; $_SERVER['REQUEST_URI'] = '/hoa-khai/';
require_once __DIR__ . '/../wp-load.php';
require_once ABSPATH . 'wp-admin/includes/image.php';
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/media.php';

function nntm_hk_upsert( string $type, string $title, string $excerpt, int $image_id ): int {
	$found = get_posts( array( 'post_type' => $type, 'post_status' => 'any', 'title' => $title, 'posts_per_page' => 1 ) );
	$args = array( 'post_type' => $type, 'post_status' => 'publish', 'post_title' => $title, 'post_excerpt' => $excerpt, 'post_content' => '<!-- wp:paragraph --><p>' . esc_html( $excerpt ) . '</p><!-- /wp:paragraph -->' );
	if ( $found ) { $args['ID'] = $found[0]->ID; $id = wp_update_post( $args ); } else { $id = wp_insert_post( $args ); }
	if ( $id && get_post( $image_id ) ) { set_post_thumbnail( $id, $image_id ); }
	return (int) $id;
}

$categories = array();
foreach ( array( 'tin-tuc' => 'Tin tức', 'hoang-phap' => 'Hoằng Pháp' ) as $slug => $name ) {
	$term = get_term_by( 'slug', $slug, 'category' );
	if ( ! $term ) { $made = wp_insert_term( $name, 'category', array( 'slug' => $slug ) ); $categories[ $slug ] = (int) $made['term_id']; }
	else { $categories[ $slug ] = (int) $term->term_id; }
}

$items = array(
	'tin-tuc' => array(
		array( 'Chúng sanh tranh đấu và đau khổ do đâu?', 'Mở đầu tham vấn về nguồn gốc của khổ đau và con đường chuyển hoá.', 123 ),
		array( 'Đại lễ Phật Đản 2026 tại Trú Xứ Nha Trang', 'Đại chúng trang nghiêm cử hành lễ Phật Đản.', 122 ),
		array( 'Khai giảng khoá tu mùa hè cho thanh thiếu niên', 'Khoá tu dành cho các bạn trẻ.', 121 ),
		array( 'Ra mắt ấn phẩm mới của Tôn Sư', 'Ấn phẩm mới được giới thiệu tới đại chúng.', 120 ),
		array( 'Trú Xứ Bodh Gaya hoàn thành trùng tu chánh điện', 'Công trình được hoàn mãn trong niềm hoan hỷ.', 119 ),
		array( 'Chương trình từ thiện mùa mưa miền Trung', 'Những phần quà đã được trao tận tay bà con.', 118 ),
		array( 'Thư viện số mở cửa phục vụ thành viên', 'Kho tư liệu số được mở rộng để phục vụ việc học pháp.', 115 ),
	),
	'hoang-phap' => array(
		array( 'Chúng sanh tranh đấu và đau khổ do đâu?', 'Mở đầu tham vấn về nguồn gốc của khổ đau và con đường chuyển hoá.', 123 ),
		array( 'Kiến tạo giá trị cho cộng đồng từ sự tỉnh thức', 'Kiến tạo giá trị cho cộng đồng từ sự tỉnh thức và yêu thương chân thành.', 122 ),
		array( 'Nhẫn nhục không phải là cam chịu', 'Giữ tâm bình an trước nghịch cảnh.', 121 ),
		array( 'Bố thí ba-la-mật trong đời sống thường nhật', 'Cho đi bằng lòng chân thành.', 120 ),
		array( 'Vì sao Đức Phật im lặng trước câu hỏi siêu hình', 'Sự im lặng cũng là một lời khai thị.', 119 ),
		array( 'Hoằng pháp bằng đời sống, không chỉ bằng lời', 'Điều ta sống có sức thuyết phục hơn điều ta nói.', 118 ),
	),
);
$post_ids = array();
foreach ( $items as $slug => $posts ) {
	foreach ( $posts as list( $title, $excerpt, $image ) ) {
		$id = nntm_hk_upsert( 'post', $title, $excerpt, $image );
		wp_set_post_categories( $id, array( $categories[ $slug ] ), true );
		$post_ids[ $slug ][] = $id;
	}
}

$book_path = __DIR__ . '/../design/hoa-khai/publication-cover-clean.png';
$book_id = 0;
$existing = get_posts( array( 'post_type' => 'attachment', 'post_status' => 'inherit', 'title' => 'Bìa ấn phẩm Hoa Khai', 'posts_per_page' => 1 ) );
if ( $existing ) { $book_id = (int) $existing[0]->ID; }
elseif ( is_file( $book_path ) ) {
	$upload = wp_upload_bits( 'bia-an-pham-hoa-khai.png', null, file_get_contents( $book_path ) );
	if ( empty( $upload['error'] ) ) {
		$book_id = wp_insert_attachment( array( 'post_mime_type' => 'image/png', 'post_title' => 'Bìa ấn phẩm Hoa Khai', 'post_status' => 'inherit' ), $upload['file'] );
		wp_update_attachment_metadata( $book_id, wp_generate_attachment_metadata( $book_id, $upload['file'] ) );
	}
}
$books = array( 'Biểu Tượng và Hoa Văn Mật Tông', 'Tuyển Tập Pháp Thoại Tôn Sư — Quyển I', 'Nghi Quỹ Tu Trì Hằng Ngày', 'Chú Giải Kinh Kim Cang', 'Hành Trạng Chư Tổ', 'Sổ Tay Công Phu' );
foreach ( $books as $title ) { nntm_hk_upsert( 'nntm_publication', $title, 'Ấn phẩm dành cho hành giả và thành viên Năng Nhân Tịch Mặc.', $book_id ); }

$tin = $categories['tin-tuc']; $hoang = $categories['hoang-phap'];
$tin_lead = $post_ids['tin-tuc'][0]; $hoang_lead = $post_ids['hoang-phap'][0];
$content = <<<HTML
<!-- wp:nntm/article-mosaic {"heading":"Tin tức","postType":"post","taxonomy":"category","termId":{$tin},"pinnedPostId":{$tin_lead},"leadMedia":"short","secondaryLayout":"grid","orderBy":"newest","showDate":false,"showCategory":false,"showExcerpt":true,"cardCtaLabel":"Xem thêm","viewAllLabel":"Xem Tất cả","viewAllUrl":"/category/tin-tuc/","className":"nntm-hk-news"} /-->

<!-- wp:nntm/article-mosaic {"heading":"Hoằng Pháp","postType":"post","taxonomy":"category","termId":{$hoang},"pinnedPostId":{$hoang_lead},"leadMedia":"tall","orderBy":"newest","showDate":false,"showCategory":false,"showExcerpt":false,"cardCtaLabel":"Xem thêm","viewAllLabel":"Xem Tất cả","viewAllUrl":"/category/hoang-phap/","className":"nntm-hk-dharma"} /-->

<!-- wp:nntm/card-list {"heading":"Ấn Phẩm","postType":"nntm_publication","variant":"books","layout":"marquee","postsPerPage":10,"orderBy":"newest","showDate":false,"showCategory":false,"className":"nntm-hk-publications"} /-->
HTML;
$page = get_page_by_path( 'hoa-khai' );
$args = array( 'post_type' => 'page', 'post_status' => 'publish', 'post_title' => 'Hoa Khai', 'post_name' => 'hoa-khai', 'post_content' => $content );
if ( $page ) { $args['ID'] = $page->ID; $id = wp_update_post( $args ); } else { $id = wp_insert_post( $args ); }
echo get_permalink( $id ), PHP_EOL;
