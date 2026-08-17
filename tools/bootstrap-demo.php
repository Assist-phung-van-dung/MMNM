<?php
/**
 * Dựng toàn bộ dữ liệu demo để test hệ thống tìm kiếm.
 *
 * Dùng khi máy khác kéo repo về: CSDL trắng, không có dump của khách, nhưng vẫn
 * cần đủ nội dung để chạy hết 10 kịch bản trong docs/09-kich-ban-test-tay.md.
 *
 *   Dựng:  "C:/xampp/php/php.exe" tools/bootstrap-demo.php
 *   Xoá:   "C:/xampp/php/php.exe" tools/bootstrap-demo.php xoa
 *
 * CHẠY LẠI NHIỀU LẦN ĐƯỢC và chỉ THÊM: máy nào đã có nội dung thật của khách thì
 * script chỉ bù những thứ còn thiếu, không đụng vào bài nào không phải của nó.
 * Mọi thứ nó tạo đều mang tiền tố [DEMO] hoặc slug `demo-`, nên xoá lại sạch.
 *
 * Nguồn dữ liệu nằm trong repo, không phụ thuộc máy nào:
 *   tools/test-assets/anh/   5 ảnh đã thu nhỏ, từ khoá đã đo sẵn
 *   tools/test-assets/pdf/   3 file PDF tiếng Việt có chữ thật
 *
 * @package NNTM
 */

if ( PHP_SAPI !== 'cli' ) {
	exit( "Chi chay tu dong lenh.\n" );
}

$_SERVER['HTTP_HOST'] = getenv( 'NNTM_HOST' ) ?: 'localhost:8080';

require_once __DIR__ . '/../wp-load.php';
require_once ABSPATH . 'wp-admin/includes/image.php';
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/plugin.php';

const NNTM_DEMO_SLUG = 'demo-';
const NNTM_DEMO_USER = 'nntm_test';
const NNTM_DEMO_PASS = 'TestNntm!2026';

/** Trang cần có để chạy được kịch bản cổng quyền và tab lọc. */
const NNTM_DEMO_PAGES = array(
	'dang-nhap'          => 'Đăng nhập',
	'dai-si-hanh-gia'    => 'Đại Sĩ Hành Giả',
	'kim-cuong-hanh-gia' => 'Kim Cương Hành Giả',
	'nghi-quy'           => 'Nghi Quỹ',
	'dieu-thuong'        => 'Diệu Thượng',
	'phap-toa'           => 'Pháp Toà',
	'lien-dan'           => 'Liên Đàn',
	'hoa-khai'           => 'Hoa Khai',
	'vuon-xoai'          => 'Vườn Xoài',
	'nhap-phap-gioi'     => 'Nhập Pháp Giới',
);

/**
 * Ảnh mẫu → từ khoá máy đọc ra (ĐO THẬT sau khi thu nhỏ, 16/08/2026)
 * → bài viết sẽ dùng nó làm ảnh đại diện.
 *
 * Ràng buộc ảnh với bài là điều làm cho việc test tìm-bằng-ảnh có kết quả
 * đoán trước được, thay vì "chắc là đúng rồi".
 */
const NNTM_DEMO_IMAGES = array(
	'01-rung-thong.jpg' => array( 'Rừng thông buổi sớm', 'rừng 33% · sương mù 28% · rừng thông 26%' ),
	'02-tuong-phat.jpg' => array( 'Tượng Phật trong chánh điện', 'tượng Phật 85%' ),
	'03-ngoi-chua.jpg'  => array( 'Ngôi chùa trên đỉnh núi', 'ngôi chùa 35% · chư tăng 18%' ),
	'04-kinh-sach.jpg'  => array( 'Giữ gìn kinh sách', 'sách 62% · kinh sách 22%' ),
	'05-nui.jpg'        => array( 'Đường lên núi', 'núi 42% · sương mù 25%' ),
);

/** Bài viết demo. `khu` là slug term nntm_section; `anh` là ảnh đại diện. */
const NNTM_DEMO_POSTS = array(
	array(
		'slug' => 'rung-thong', 'khu' => 'hoa-khai', 'anh' => '01-rung-thong.jpg',
		'title' => 'Rừng thông buổi sớm',
		'body'  => 'Rừng thông buổi sớm còn đẫm sương. Hành giả đi chậm giữa hai hàng cây, nghe tiếng lá reo trên đầu. Đi trong rừng thông mà giữ được hơi thở đều thì ngồi xuống đâu cũng thành toạ cụ.',
	),
	array(
		'slug' => 'tuong-phat', 'khu' => 'hoa-khai', 'anh' => '02-tuong-phat.jpg',
		'title' => 'Tượng Phật trong chánh điện',
		'body'  => 'Tượng Phật đặt chính giữa chánh điện, mắt khép hờ nhìn xuống. Người xưa tạc tượng Phật không phải để thờ một hình tướng, mà để nhắc người đứng trước tượng nhớ lại cái tâm vốn lặng của mình.',
	),
	array(
		'slug' => 'ngoi-chua', 'khu' => 'dieu-thuong', 'anh' => '03-ngoi-chua.jpg',
		'title' => 'Ngôi chùa trên đỉnh núi',
		'body'  => 'Ngôi chùa nằm lưng chừng núi, phải leo hơn ba trăm bậc đá mới tới. Sáng sớm mây phủ kín chân núi, nhìn xuống chỉ thấy một biển trắng. Ngôi chùa ấy không có gì ngoài một chánh điện gỗ và một quả chuông.',
	),
	array(
		'slug' => 'kinh-sach', 'khu' => 'dieu-thuong', 'anh' => '04-kinh-sach.jpg',
		'title' => 'Giữ gìn kinh sách',
		'body'  => 'Kinh sách cũ giấy đã ngả vàng, phải để nơi khô ráo, tránh nắng chiếu thẳng. Người xưa bọc kinh sách bằng vải điều, mỗi lần mở ra đều rửa tay. Giữ quyển sách cẩn thận cũng là một cách giữ tâm.',
	),
	array(
		'slug' => 'duong-len-nui', 'khu' => 'phap-toa', 'anh' => '05-nui.jpg',
		'title' => 'Đường lên núi',
		'body'  => 'Đường lên núi dốc và dài. Người đi nhanh thì hụt hơi ở đoạn giữa, người đi chậm lại tới trước. Núi không thử sức chân, núi thử cái tâm nôn nóng.',
	),
	array(
		'slug' => 'tu-dieu-de', 'khu' => 'phap-toa', 'anh' => '',
		'title' => 'Tứ Diệu Đế nói gì',
		'body'  => 'Tứ Diệu Đế là bài pháp đầu tiên Đức Phật tuyên thuyết tại vườn Lộc Uyển. Bốn sự thật ấy không phải tín điều để tin, mà là điều để tự mình kiểm chứng.',
	),
	array(
		'slug' => 'sen-trong-bun', 'khu' => 'lien-dan', 'anh' => '',
		'title' => 'Hoa sen nở giữa bùn',
		'body'  => 'Bùn nhơ không làm hoa sen ô uế, cũng như nghịch cảnh không làm tâm tỉnh thức vấy bẩn. Hoa sen mọc từ bùn nhưng cánh hoa không dính một hạt bùn nào.',
	),
	array(
		'slug' => 'vuon-xoai-mua-qua', 'khu' => 'vuon-xoai', 'anh' => '',
		'title' => 'Vườn xoài mùa quả chín',
		'body'  => 'Vườn xoài đến mùa thì tự trĩu quả, không cần ai giục. Việc tu cũng vậy, đủ duyên thì kết trái, thúc ép chỉ làm rụng non.',
	),
	// Ba bài trong khu hạn chế — dùng cho kịch bản cổng quyền.
	array(
		'slug' => 'han-che-tuong-phat', 'khu' => 'kim-cuong-hanh-gia', 'anh' => '',
		'title' => 'Tượng Phật — bài dành cho thành viên',
		'body'  => 'Bài này cũng nói về tượng Phật, nhưng nằm trong khu Kim Cương Hành Giả. Khách chưa đăng nhập KHÔNG được thấy bài này trong bất kỳ kết quả tìm kiếm nào. Đọc được câu này mà chưa đăng nhập tức là có rò dữ liệu.',
	),
	array(
		'slug' => 'han-che-kim-cuong', 'khu' => 'kim-cuong-hanh-gia', 'anh' => '',
		'title' => 'Kim Cương Hành Giả — nghi thức riêng',
		'body'  => 'Nghi thức của khu Kim Cương Hành Giả chỉ dành cho thành viên đã được ban quản trị nâng cấp. Nội dung này không được xuất hiện với khách vãng lai.',
	),
	array(
		'slug' => 'han-che-dai-si', 'khu' => 'dai-si-hanh-gia', 'anh' => '',
		'title' => 'Đại Sĩ Hành Giả — bài mở đầu',
		'body'  => 'Bài mở đầu của khu Đại Sĩ Hành Giả. Cũng như bài Kim Cương, nội dung này chỉ hiện với thành viên đã đăng nhập.',
	),
);

/** Ấn phẩm demo, mỗi cuốn gắn một file PDF. */
const NNTM_DEMO_PUBS = array(
	'nghi-quy-tung-niem-hang-ngay.pdf' => 'Nghi Quỹ Tụng Niệm Hằng Ngày',
	'luan-ve-tu-dieu-de.pdf'           => 'Luận Về Tứ Diệu Đế',
	'kinh-phap-cu-trich-giang.pdf'     => 'Kinh Pháp Cú — Trích Giảng',
);

/**
 * Thư mục để ảnh cho người test kéo thả — mặc định là Downloads.
 *
 * @return string
 */
function nntm_demo_drop_dir(): string {
	$env = getenv( 'NNTM_TEST_DIR' );

	if ( is_string( $env ) && '' !== $env ) {
		return rtrim( str_replace( '\\', '/', $env ), '/' );
	}

	$home = getenv( 'USERPROFILE' ) ?: getenv( 'HOME' );

	if ( is_string( $home ) && is_dir( $home . '/Downloads' ) ) {
		return str_replace( '\\', '/', $home ) . '/Downloads/nntm-test-anh';
	}

	return str_replace( '\\', '/', __DIR__ ) . '/test-assets/drop';
}

/**
 * Gán ngôn ngữ cho bài nếu Polylang đang bật.
 *
 * Bẫy ở docs/07-ban-giao.md mục 9: bài không gán ngôn ngữ sẽ BIẾN MẤT khỏi truy
 * vấn `post_type = 'any'`, tức là mất khỏi tìm kiếm, dù vẫn hiện ở trang danh sách.
 *
 * @param int $post_id ID bài.
 */
function nntm_demo_set_lang( int $post_id ): void {
	if ( function_exists( 'pll_set_post_language' ) && function_exists( 'pll_default_language' ) ) {
		pll_set_post_language( $post_id, pll_default_language() ?: 'vi' );
	}
}

/* =========================================================================
 * XOÁ
 * ========================================================================= */

if ( isset( $argv[1] ) && 'xoa' === $argv[1] ) {
	global $wpdb;

	$ids = $wpdb->get_col(
		$wpdb->prepare(
			"SELECT ID FROM {$wpdb->posts} WHERE post_name LIKE %s",
			$wpdb->esc_like( NNTM_DEMO_SLUG ) . '%'
		)
	);

	foreach ( $ids as $id ) {
		wp_delete_post( (int) $id, true );
	}

	$user = get_user_by( 'login', NNTM_DEMO_USER );

	if ( $user ) {
		require_once ABSPATH . 'wp-admin/includes/user.php';
		wp_delete_user( $user->ID );
	}

	$drop = nntm_demo_drop_dir();

	if ( is_dir( $drop ) ) {
		array_map( 'unlink', glob( $drop . '/*' ) ?: array() );
		@rmdir( $drop );
	}

	printf( "Da xoa %d bai/trang demo, tai khoan test va thu muc anh.\n", count( $ids ) );
	exit( 0 );
}

/* =========================================================================
 * DỰNG
 * ========================================================================= */

echo "=== 1. Theme va plugin ===\n";

if ( 'nntm' !== get_stylesheet() ) {
	switch_theme( 'nntm' );
	echo "  theme nntm: da bat\n";
} else {
	echo "  theme nntm: dang bat\n";
}

foreach ( array( 'nntm-core/nntm-core.php', 'nntm-search/nntm-search.php' ) as $plugin ) {
	if ( is_plugin_active( $plugin ) ) {
		printf( "  %-32s dang bat\n", $plugin );
		continue;
	}

	$kq = activate_plugin( $plugin );
	printf( "  %-32s %s\n", $plugin, is_wp_error( $kq ) ? 'LOI: ' . $kq->get_error_message() : 'da bat' );
}

// Bảng của nntm-search chỉ tạo lúc kích hoạt; plugin đã bật sẵn thì gọi tay.
if ( function_exists( 'nntm_search_create_tables' ) ) {
	nntm_search_create_tables();
	echo "  bang wp_nntm_pdf_pages / wp_nntm_image_vectors: san sang\n";
}

if ( '/%postname%/' !== get_option( 'permalink_structure' ) ) {
	update_option( 'permalink_structure', '/%postname%/' );
	echo "  duong dan tinh: /%postname%/\n";
}

echo "\n=== 2. Tai khoan thanh vien ===\n";

if ( get_user_by( 'login', NNTM_DEMO_USER ) ) {
	echo '  ' . NNTM_DEMO_USER . ": da co\n";
} else {
	$uid = wp_insert_user(
		array(
			'user_login'   => NNTM_DEMO_USER,
			'user_pass'    => NNTM_DEMO_PASS,
			'user_email'   => 'test@local.test',
			'display_name' => 'Thành viên thử',
			'role'         => 'subscriber',
		)
	);

	echo is_wp_error( $uid )
		? '  LOI: ' . $uid->get_error_message() . "\n"
		: '  tao moi: ' . NNTM_DEMO_USER . ' / ' . NNTM_DEMO_PASS . "\n";
}

echo "\n=== 3. Trang ===\n";

$so_trang_moi = 0;

foreach ( NNTM_DEMO_PAGES as $slug => $title ) {
	if ( get_page_by_path( $slug, OBJECT, 'page' ) ) {
		continue;
	}

	$id = wp_insert_post(
		array(
			'post_type'    => 'page',
			'post_status'  => 'publish',
			'post_title'   => $title,
			'post_name'    => $slug,
			'post_content' => '',
		)
	);

	if ( ! is_wp_error( $id ) ) {
		nntm_demo_set_lang( (int) $id );
		++$so_trang_moi;
	}
}

printf( "  tao moi %d trang, %d trang da co san\n", $so_trang_moi, count( NNTM_DEMO_PAGES ) - $so_trang_moi );

echo "\n=== 4. Anh vao Thu vien ===\n";

$uploads   = wp_upload_dir();
$anh_theo_ten = array();

foreach ( NNTM_DEMO_IMAGES as $file => $info ) {
	$src = __DIR__ . '/test-assets/anh/' . $file;

	if ( ! is_file( $src ) ) {
		printf( "  THIEU nguon: %s\n", $file );
		continue;
	}

	$da_co = get_posts(
		array(
			'post_type'      => 'attachment',
			'post_status'    => 'inherit',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'meta_key'       => '_nntm_demo_asset',   // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			'meta_value'     => $file,                 // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
		)
	);

	if ( $da_co ) {
		$anh_theo_ten[ $file ] = (int) $da_co[0];
		printf( "  %-22s da co (ID %d)\n", $file, $da_co[0] );
		continue;
	}

	$dich = trailingslashit( $uploads['path'] ) . $file;

	if ( ! copy( $src, $dich ) ) {
		printf( "  chep that bai: %s\n", $file );
		continue;
	}

	$id = wp_insert_attachment(
		array(
			'post_mime_type' => 'image/jpeg',
			'post_title'     => $info[0],
			'post_status'    => 'inherit',
		),
		$dich
	);

	wp_update_attachment_metadata( $id, wp_generate_attachment_metadata( $id, $dich ) );
	update_post_meta( $id, '_nntm_demo_asset', $file );

	$anh_theo_ten[ $file ] = (int) $id;
	printf( "  %-22s nap moi (ID %d)  ->  %s\n", $file, $id, $info[1] );
}

echo "\n=== 5. Bai viet ===\n";

foreach ( NNTM_DEMO_POSTS as $mau ) {
	$slug = NNTM_DEMO_SLUG . $mau['slug'];
	$co   = get_page_by_path( $slug, OBJECT, 'nntm_article' );

	$data = array(
		'post_type'    => 'nntm_article',
		'post_status'  => 'publish',
		'post_title'   => '[DEMO] ' . $mau['title'],
		'post_name'    => $slug,
		'post_content' => $mau['body'],
		'post_excerpt' => mb_substr( $mau['body'], 0, 140 ) . '…',
	);

	if ( $co ) {
		$data['ID'] = $co->ID;
		$id         = wp_update_post( $data );
	} else {
		// Ngày lệch nhau vài phút — bẫy "ngày đăng trùng làm thứ tự bài không
		// ổn định" ở docs/07-ban-giao.md muc 7.
		static $lech = 0;
		++$lech;
		$data['post_date']     = gmdate( 'Y-m-d H:i:s', time() - $lech * 300 );
		$data['post_date_gmt'] = get_gmt_from_date( $data['post_date'] );

		$id = wp_insert_post( $data );
	}

	if ( is_wp_error( $id ) || ! $id ) {
		printf( "  LOI: %s\n", $mau['slug'] );
		continue;
	}

	$term = get_term_by( 'slug', $mau['khu'], 'nntm_section' );

	if ( $term ) {
		wp_set_object_terms( (int) $id, array( (int) $term->term_id ), 'nntm_section', false );
	}

	if ( '' !== $mau['anh'] && isset( $anh_theo_ten[ $mau['anh'] ] ) ) {
		set_post_thumbnail( (int) $id, $anh_theo_ten[ $mau['anh'] ] );
	}

	nntm_demo_set_lang( (int) $id );

	printf( "  #%-4d %-40s [%s]\n", $id, mb_substr( $mau['title'], 0, 38 ), $mau['khu'] );
}

echo "\n=== 6. An pham + file PDF ===\n";

$dich_vu_song = false;

if ( function_exists( 'nntm_search_service_url' ) ) {
	$ping = wp_remote_get( nntm_search_service_url() . '/khoe', array( 'timeout' => 3 ) );
	$dich_vu_song = ! is_wp_error( $ping ) && 200 === (int) wp_remote_retrieve_response_code( $ping );
}

printf( "  dich vu doc PDF/anh: %s\n", $dich_vu_song ? 'DANG CHAY' : 'KHONG CHAY — bo qua lap chi muc' );

$i = 0;

foreach ( NNTM_DEMO_PUBS as $file => $title ) {
	$src  = __DIR__ . '/test-assets/pdf/' . $file;
	$slug = NNTM_DEMO_SLUG . sanitize_title( pathinfo( $file, PATHINFO_FILENAME ) );

	if ( ! is_file( $src ) ) {
		printf( "  THIEU nguon: %s\n", $file );
		continue;
	}

	// Ấn phẩm.
	$pub = get_page_by_path( $slug, OBJECT, 'nntm_publication' );

	if ( $pub ) {
		$pub_id = $pub->ID;
	} else {
		$pub_id = wp_insert_post(
			array(
				'post_type'    => 'nntm_publication',
				'post_status'  => 'publish',
				'post_title'   => '[DEMO] ' . $title,
				'post_name'    => $slug,
				'post_content' => 'Ấn phẩm demo dùng để thử tính năng tìm nội dung bên trong file PDF.',
			)
		);
		nntm_demo_set_lang( (int) $pub_id );
	}

	// File PDF.
	$da_co = get_posts(
		array(
			'post_type'      => 'attachment',
			'post_status'    => 'inherit',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'meta_key'       => '_nntm_demo_asset',   // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			'meta_value'     => $file,                 // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
		)
	);

	if ( $da_co ) {
		$att_id = (int) $da_co[0];
	} else {
		$dich = trailingslashit( $uploads['path'] ) . $file;

		if ( ! copy( $src, $dich ) ) {
			printf( "  chep that bai: %s\n", $file );
			continue;
		}

		$att_id = (int) wp_insert_attachment(
			array(
				'post_mime_type' => 'application/pdf',
				'post_title'     => $title,
				'post_status'    => 'inherit',
			),
			$dich
		);

		wp_update_attachment_metadata( $att_id, wp_generate_attachment_metadata( $att_id, $dich ) );
		update_post_meta( $att_id, '_nntm_demo_asset', $file );
	}

	update_post_meta( (int) $pub_id, '_nntm_pdf_file', $att_id );

	$so_trang = 0;

	if ( $dich_vu_song && function_exists( 'nntm_search_index_pdf' ) ) {
		$kq       = nntm_search_index_pdf( $att_id );
		$so_trang = is_wp_error( $kq ) ? 0 : (int) $kq;
	}

	printf( "  #%-4d %-38s file #%d, %d trang da lap chi muc\n", $pub_id, mb_substr( $title, 0, 36 ), $att_id, $so_trang );
	++$i;
}

echo "\n=== 7. Lap chi muc anh ===\n";

if ( $dich_vu_song && function_exists( 'nntm_search_index_image' ) ) {
	$xong = 0;

	foreach ( $anh_theo_ten as $file => $id ) {
		if ( ! is_wp_error( nntm_search_index_image( $id ) ) ) {
			++$xong;
		}
	}

	printf( "  %d/%d anh\n", $xong, count( $anh_theo_ten ) );
} else {
	echo "  bo qua — dich vu khong chay\n";
}

echo "\n=== 8. Anh de keo tha + file cho case bao mat ===\n";

$drop = nntm_demo_drop_dir();

if ( ! is_dir( $drop ) ) {
	wp_mkdir_p( $drop );
}

foreach ( NNTM_DEMO_IMAGES as $file => $info ) {
	$src = __DIR__ . '/test-assets/anh/' . $file;

	if ( is_file( $src ) ) {
		copy( $src, $drop . '/' . $file );
		printf( "  %-22s %s\n", $file, $info[1] );
	}
}

// Ảnh quá cỡ: sinh tại chỗ bằng cách đệm thêm byte vào cuối một ảnh thật —
// vẫn là JPEG hợp lệ, chỉ nặng hơn 5MB. Không commit file 5MB vào repo.
$qua_lon = $drop . '/06-anh-qua-lon.jpg';

if ( ! is_file( $qua_lon ) || filesize( $qua_lon ) < 5242880 ) {
	copy( __DIR__ . '/test-assets/anh/02-tuong-phat.jpg', $qua_lon );
	file_put_contents( $qua_lon, str_repeat( "\0", 5400000 ), FILE_APPEND ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
}

printf( "  %-22s %.1f MB — dung cho case qua dung luong\n", '06-anh-qua-lon.jpg', filesize( $qua_lon ) / 1048576 );

// File giả mạo: nội dung HTML, đuôi .jpg.
file_put_contents( $drop . '/07-file-gia-mao.jpg', "<html><body><h1>Day khong phai anh</h1></body></html>\n" ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
printf( "  %-22s file HTML doi duoi .jpg — dung cho case sai dinh dang\n", '07-file-gia-mao.jpg' );

echo "\n=== 9. Kiem tra nhanh ===\n";

foreach ( array( 'ngôi chùa', 'kinh sách', 'tượng Phật', 'rừng thông' ) as $tu ) {
	$q = new WP_Query(
		array(
			's'              => $tu,
			'post_type'      => 'nntm_article',
			'post_status'    => 'publish',
			'posts_per_page' => 1,
			'fields'         => 'ids',
		)
	);

	printf( "  tim \"%s\" -> %d bai\n", $tu, $q->found_posts );
}

if ( $dich_vu_song && function_exists( 'nntm_search_pdf_pages' ) ) {
	$hits = nntm_search_pdf_pages( 'chuoi hat', 3 );
	printf( "  tim trong PDF \"chuoi hat\" -> %d trang\n", count( $hits ) );
}

printf( "\nXong. Anh keo tha: %s\n", $drop );
echo "Xoa sach: php tools/bootstrap-demo.php xoa\n";
