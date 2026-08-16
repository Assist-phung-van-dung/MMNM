<?php
/**
 * Dựng bộ dữ liệu để TEST TAY tính năng tìm kiếm.
 *
 * Mọi thứ script này tạo ra đều mang tiền tố [TEST] hoặc slug bắt đầu bằng
 * `test-tk-`, nên xoá lại sạch được và không lẫn với nội dung thật.
 *
 *   Dựng:  "C:/xampp/php/php.exe" tools/test-data-tim-kiem.php
 *   Xoá:   "C:/xampp/php/php.exe" tools/test-data-tim-kiem.php xoa
 *
 * Chạy lại nhiều lần được: bài nào đã có thì cập nhật, không tạo bản trùng.
 *
 * VÌ SAO CẦN BỘ NÀY: test tay chỉ có ý nghĩa khi biết TRƯỚC kết quả đúng phải
 * là gì. Nội dung thật của khách thì không đoán được, nên ở đây mỗi bài được
 * gieo đúng một từ khoá mà ảnh mẫu sẽ đọc ra — thả ảnh rừng thông vào ô tìm
 * thì phải ra đúng bài "Rừng thông", không phải "chắc là đúng rồi".
 *
 * @package NNTM
 */

if ( PHP_SAPI !== 'cli' ) {
	exit( "Chi chay tu dong lenh.\n" );
}

$_SERVER['HTTP_HOST'] = 'localhost:8080';

require_once __DIR__ . '/../wp-load.php';
require_once ABSPATH . 'wp-admin/includes/image.php';
require_once ABSPATH . 'wp-admin/includes/file.php';

const NNTM_TEST_PREFIX = 'test-tk-';
const NNTM_TEST_USER   = 'nntm_test';
const NNTM_TEST_PASS   = 'TestNntm!2026';

/**
 * Thư mục chứa ảnh mẫu để người test kéo thả.
 *
 * Mặc định đặt trong Downloads của người dùng — đó là chỗ hộp thoại "chọn tệp"
 * của trình duyệt mở ra đầu tiên, kéo thả cũng gần tay nhất. Đổi được bằng biến
 * môi trường NNTM_TEST_DIR, và có đường lui vào tools/test-data/anh nếu máy
 * không có Downloads (ví dụ chạy trên Linux CI).
 *
 * @return string
 */
function nntm_test_thu_muc_anh(): string {
	$tu_moi_truong = getenv( 'NNTM_TEST_DIR' );

	if ( is_string( $tu_moi_truong ) && '' !== $tu_moi_truong ) {
		return rtrim( str_replace( '\\', '/', $tu_moi_truong ), '/' );
	}

	$home = getenv( 'USERPROFILE' ) ?: getenv( 'HOME' );

	if ( is_string( $home ) && is_dir( $home . '/Downloads' ) ) {
		return str_replace( '\\', '/', $home ) . '/Downloads/nntm-test-anh';
	}

	return str_replace( '\\', '/', __DIR__ ) . '/test-data/anh';
}

/**
 * Ảnh mẫu: file nguồn trong Thư viện → tên thân thiện để người test biết kéo cái nào.
 *
 * Từ khoá ghi kèm là kết quả ĐO THẬT của dịch vụ đọc ảnh ngày 15/08/2026, không
 * phải phỏng đoán. Nếu đổi model thì phải đo lại rồi sửa bảng này.
 */
const NNTM_TEST_IMAGES = array(
	'c7bfce6213192b41e6396529be40443f11e1297a.jpg' => array( '01-rung-thong.jpg', 'rừng thông 40% · rừng 26% · sương mù 25%' ),
	'cc97982112412dcd17d900a439d8edf2d4aea1de.jpg' => array( '02-tuong-phat.jpg', 'tượng Phật 88%' ),
	'banner-nui-tu-vien.webp'                      => array( '03-ngoi-chua.webp', 'ngôi chùa 34% · chư tăng 18%' ),
	'bia-an-pham-hoa-khai.png'                     => array( '04-kinh-sach.png', 'sách 59% · kinh sách 33%' ),
	'20b111696a786c15d35ea6b319b05b4ff42f7c31.jpg' => array( '05-nui.jpg', 'núi 34% · ngoài trời 28%' ),
	'1533571a36800c07a07486bf21df5cfc98e47277.jpg' => array( '06-anh-qua-lon.jpg', 'lá cây 87% — file 5,3MB, dùng cho case quá dung lượng' ),
);

/**
 * Bài viết mẫu. Mỗi bài gieo đúng một từ khoá mà ảnh tương ứng đọc ra.
 *
 * `khu` = slug term nntm_section. Bài cuối cố tình đặt vào khu hạn chế để thử
 * cổng quyền: khách tìm "tượng Phật" chỉ được thấy bài công khai, thành viên
 * thấy cả hai.
 */
const NNTM_TEST_POSTS = array(
	array(
		'slug'  => NNTM_TEST_PREFIX . 'rung-thong',
		'title' => '[TEST] Rừng thông buổi sớm',
		'body'  => 'Rừng thông buổi sớm còn đẫm sương. Hành giả đi chậm giữa hai hàng cây, '
			. 'nghe tiếng lá reo trên đầu. Đi trong rừng thông mà giữ được hơi thở đều '
			. 'thì ngồi xuống đâu cũng thành toạ cụ.',
		'khu'   => 'hoa-khai',
	),
	array(
		'slug'  => NNTM_TEST_PREFIX . 'tuong-phat',
		'title' => '[TEST] Tượng Phật trong chánh điện',
		'body'  => 'Tượng Phật đặt chính giữa chánh điện, mắt khép hờ nhìn xuống. '
			. 'Người xưa tạc tượng Phật không phải để thờ một hình tướng, mà để nhắc '
			. 'người đứng trước tượng nhớ lại cái tâm vốn lặng của mình.',
		'khu'   => 'hoa-khai',
	),
	array(
		'slug'  => NNTM_TEST_PREFIX . 'ngoi-chua',
		'title' => '[TEST] Ngôi chùa trên đỉnh núi',
		'body'  => 'Ngôi chùa nằm lưng chừng núi, phải leo hơn ba trăm bậc đá mới tới. '
			. 'Sáng sớm mây phủ kín chân núi, nhìn xuống chỉ thấy một biển trắng. '
			. 'Ngôi chùa ấy không có gì ngoài một chánh điện gỗ và một quả chuông.',
		'khu'   => 'dieu-thuong',
	),
	array(
		'slug'  => NNTM_TEST_PREFIX . 'kinh-sach',
		'title' => '[TEST] Giữ gìn kinh sách',
		'body'  => 'Kinh sách cũ giấy đã ngả vàng, phải để nơi khô ráo, tránh nắng chiếu '
			. 'thẳng. Người xưa bọc kinh sách bằng vải điều, mỗi lần mở ra đều rửa tay. '
			. 'Giữ quyển sách cẩn thận cũng là một cách giữ tâm.',
		'khu'   => 'dieu-thuong',
	),
	array(
		'slug'  => NNTM_TEST_PREFIX . 'tuong-phat-han-che',
		'title' => '[TEST] Tượng Phật — bài dành cho thành viên',
		'body'  => 'Bài này cũng nói về tượng Phật, nhưng nằm trong khu Kim Cương Hành Giả. '
			. 'Khách chưa đăng nhập KHÔNG được thấy bài này trong bất kỳ kết quả tìm kiếm nào. '
			. 'Nếu bạn đang đọc câu này mà chưa đăng nhập thì đó là lỗi rò dữ liệu.',
		'khu'   => 'kim-cuong-hanh-gia',
	),
);

/* =========================================================================
 * Xoá
 * ========================================================================= */

if ( isset( $argv[1] ) && 'xoa' === $argv[1] ) {
	$posts = get_posts(
		array(
			'post_type'      => 'nntm_article',
			'post_status'    => 'any',
			'posts_per_page' => -1,
			'name'           => '',
			's'              => '[TEST]',
		)
	);

	$n = 0;

	foreach ( $posts as $p ) {
		if ( 0 === strpos( $p->post_name, NNTM_TEST_PREFIX ) ) {
			wp_delete_post( $p->ID, true );
			++$n;
		}
	}

	$user = get_user_by( 'login', NNTM_TEST_USER );

	if ( $user ) {
		require_once ABSPATH . 'wp-admin/includes/user.php';
		wp_delete_user( $user->ID );
		echo "Da xoa tai khoan " . NNTM_TEST_USER . "\n";
	}

	$thu_muc = nntm_test_thu_muc_anh();

	if ( is_dir( $thu_muc ) ) {
		foreach ( glob( $thu_muc . '/*' ) as $f ) {
			unlink( $f );
		}
		@rmdir( $thu_muc );
		echo "Da xoa thu muc $thu_muc\n";
	}

	echo "Da xoa $n bai [TEST].\n";
	exit( 0 );
}

/* =========================================================================
 * Dựng
 * ========================================================================= */

echo "=== 1. Tai khoan thanh vien de test cong quyen ===\n";

$user = get_user_by( 'login', NNTM_TEST_USER );

if ( $user ) {
	echo '  da co: ' . NNTM_TEST_USER . " (ID {$user->ID})\n";
} else {
	$uid = wp_insert_user(
		array(
			'user_login'   => NNTM_TEST_USER,
			'user_pass'    => NNTM_TEST_PASS,
			'user_email'   => 'test@local.test',
			'display_name' => 'Thành viên thử',
			'role'         => 'subscriber',
		)
	);

	if ( is_wp_error( $uid ) ) {
		echo '  LOI: ' . $uid->get_error_message() . "\n";
	} else {
		echo '  tao moi: ' . NNTM_TEST_USER . " / " . NNTM_TEST_PASS . " (ID $uid)\n";
	}
}

echo "\n=== 2. Bai viet mau ===\n";

foreach ( NNTM_TEST_POSTS as $mau ) {
	$co = get_page_by_path( $mau['slug'], OBJECT, 'nntm_article' );

	$data = array(
		'post_type'    => 'nntm_article',
		'post_status'  => 'publish',
		'post_title'   => $mau['title'],
		'post_name'    => $mau['slug'],
		'post_content' => $mau['body'],
		'post_excerpt' => mb_substr( $mau['body'], 0, 140 ) . '…',
	);

	if ( $co ) {
		$data['ID'] = $co->ID;
		$id         = wp_update_post( $data );
		$viec       = 'cap nhat';
	} else {
		// Ngày lệch nhau vài phút — bẫy "ngày đăng trùng nhau làm thứ tự bài
		// không ổn định" ở docs/07-ban-giao.md muc 7.
		$data['post_date']     = date( 'Y-m-d H:i:s', strtotime( '-' . count( NNTM_TEST_POSTS ) . ' minutes' ) );
		$data['post_date_gmt'] = get_gmt_from_date( $data['post_date'] );

		$id   = wp_insert_post( $data );
		$viec = 'tao moi';
	}

	if ( is_wp_error( $id ) || ! $id ) {
		echo "  LOI voi {$mau['slug']}\n";
		continue;
	}

	$term = get_term_by( 'slug', $mau['khu'], 'nntm_section' );

	if ( $term ) {
		wp_set_object_terms( $id, array( (int) $term->term_id ), 'nntm_section', false );
	}

	// Bẫy Polylang (muc 9 ban giao): bai khong gan ngon ngu se BIEN MAT khoi
	// truy van post_type = 'any', tuc la mat khoi tim kiem.
	if ( function_exists( 'pll_set_post_language' ) ) {
		pll_set_post_language( $id, 'vi' );
	}

	printf( "  %-9s #%-4d %-46s [%s]\n", $viec, $id, $mau['title'], $mau['khu'] );
}

echo "\n=== 3. Anh mau de keo tha ===\n";

$thu_muc = nntm_test_thu_muc_anh();

if ( ! is_dir( $thu_muc ) ) {
	wp_mkdir_p( $thu_muc );
}

$uploads = wp_upload_dir();

foreach ( NNTM_TEST_IMAGES as $nguon => $info ) {
	list( $ten_moi, $tu_khoa ) = $info;

	$duong_dan_nguon = trailingslashit( $uploads['basedir'] ) . '2026/08/' . $nguon;

	if ( ! is_file( $duong_dan_nguon ) ) {
		printf( "  THIEU nguon: %s\n", $nguon );
		continue;
	}

	$dich = $thu_muc . '/' . $ten_moi;

	if ( ! copy( $duong_dan_nguon, $dich ) ) {
		printf( "  chep that bai: %s\n", $ten_moi );
		continue;
	}

	printf( "  %-22s %6.1f MB   ->  %s\n", $ten_moi, filesize( $dich ) / 1048576, $tu_khoa );
}

// File giả mạo: nội dung HTML, đuôi .jpg — dùng cho case "sai định dạng".
$gia_mao = $thu_muc . '/07-file-gia-mao.jpg';
file_put_contents( $gia_mao, "<html><body><h1>Day khong phai anh</h1></body></html>\n" );
printf( "  %-22s %6.1f KB   ->  file HTML doi duoi .jpg, dung cho case 415\n", '07-file-gia-mao.jpg', filesize( $gia_mao ) / 1024 );

echo "\n=== 4. Kiem tra nhanh ===\n";

foreach ( array( 'rừng thông', 'tượng Phật', 'ngôi chùa', 'kinh sách' ) as $tu ) {
	$q = new WP_Query(
		array(
			's'              => $tu,
			'post_type'      => 'nntm_article',
			'post_status'    => 'publish',
			'posts_per_page' => 5,
			'fields'         => 'ids',
		)
	);

	printf( "  tim \"%s\" -> %d bai\n", $tu, $q->found_posts );
}

echo "\nXong. Thu muc anh mau: " . str_replace( '\\', '/', $thu_muc ) . "\n";
echo "Xoa sach lai: php tools/test-data-tim-kiem.php xoa\n";
