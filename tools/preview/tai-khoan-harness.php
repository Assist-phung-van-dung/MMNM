<?php
/*
 * Dựng trang "Tài khoản của tôi" xem thử ở dạng MỘT tệp HTML tự chứa.
 *
 * Trang thật là đường dẫn ảo (/tai-khoan/, không phải Page trong DB), bắt
 * buộc đăng nhập, và Browser pane không tải được subresource của vhost
 * nntm.com. Script này chạy bằng PHP CLI: nạp WordPress, "đăng nhập" thành
 * một người dùng có sẵn, dựng lại đúng các template-parts của trang, rồi
 * nhúng CSS/JS vào một tệp — mở bằng file:// là thấy đúng như trang thật.
 *
 *   php tools/preview/tai-khoan-harness.php --user=1 --demo > tools/preview/tai-khoan.html
 *   php tools/preview/tai-khoan-harness.php --user=1        > tools/preview/tai-khoan-rong.html
 *
 * --user=ID  Người dùng để "đăng nhập" khi dựng trang. Bỏ qua thì lấy quản
 *            trị viên đầu tiên tìm thấy.
 * --demo     Bơm dữ liệu mẫu (KHÔNG ghi DB) cho mọi khối qua các filter
 *            nntm_tk_du_lieu_* — xem tools/preview/tai-khoan-du-lieu-demo.php.
 *            Không có cờ này thì trang hiện đúng dữ liệu thật của --user,
 *            thường là rỗng ở máy local (đúng mục đích: xem trạng thái rỗng).
 */

define( 'WP_USE_THEMES', false );
require dirname( __DIR__, 2 ) . '/wp-load.php';

error_reporting( E_ALL ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions -- script CLI xem thử, cố tình bật hết để bắt notice.
ini_set( 'display_errors', '1' );

$user_id = 0;
$demo    = false;

foreach ( array_slice( $argv, 1 ) as $tham_so ) {
	if ( 0 === strpos( $tham_so, '--user=' ) ) {
		$user_id = (int) substr( $tham_so, 7 );
	}
	if ( '--demo' === $tham_so ) {
		$demo = true;
	}
}

if ( $user_id <= 0 ) {
	$admins  = get_users(
		array(
			'role'    => 'administrator',
			'number'  => 1,
			'orderby' => 'ID',
			'order'   => 'ASC',
		)
	);
	$user_id = $admins ? (int) $admins[0]->ID : 0;
}

if ( $user_id <= 0 ) {
	fwrite( STDERR, "Khong tim thay nguoi dung nao de xem thu (--user=ID hoac can it nhat mot quan tri vien).\n" );
	exit( 1 );
}

wp_set_current_user( $user_id );

if ( $demo ) {
	require __DIR__ . '/tai-khoan-du-lieu-demo.php';
}

$theme = get_template_directory();

$css_files = array(
	$theme . '/assets/css/tokens.css',
	$theme . '/assets/css/base.css',
	$theme . '/assets/css/layout.css',
	$theme . '/assets/css/pages/tai-khoan.css',
);

$css = '';
foreach ( $css_files as $file ) {
	if ( is_readable( $file ) ) {
		$css .= "\n/* ===== " . basename( $file ) . " ===== */\n" . file_get_contents( $file );
	}
}

$js_file = $theme . '/assets/js/tai-khoan.js';
$js      = is_readable( $js_file ) ? file_get_contents( $js_file ) : '';

/*
 * Dựng đúng cây template-parts mà page-tai-khoan.php dùng — không get_header()/
 * get_footer() vì hai hàm đó kéo theo cả bộ khung site (header, footer, font,
 * script khác) mà trang xem thử không cần và Browser pane cũng không tải nổi.
 */
ob_start();
get_template_part( 'template-parts/tai-khoan/hero' );
echo '<div class="nntm-tk__khung nntm-tk__bo-cuc">';
get_template_part( 'template-parts/tai-khoan/dieu-huong' );
echo '<div class="nntm-tk__noi-dung">';
get_template_part( 'template-parts/tai-khoan/dang-doc' );
get_template_part( 'template-parts/tai-khoan/thu-vien' );
get_template_part( 'template-parts/tai-khoan/yeu-thich' );
get_template_part( 'template-parts/tai-khoan/cong-tu' );
get_template_part( 'template-parts/tai-khoan/khoa-tu' );
get_template_part( 'template-parts/tai-khoan/ho-so' );
get_template_part( 'template-parts/tai-khoan/bao-mat' );
echo '</div></div>';
$noi_dung = ob_get_clean();

$nhan = $demo ? 'DEMO — DU LIEU GIA (khong ghi DB)' : 'NGUOI DUNG THAT #' . $user_id . ' — co the rong';

echo '<!doctype html><html lang="vi"><head><meta charset="utf-8">';
echo '<meta name="viewport" content="width=device-width, initial-scale=1">';
echo '<title>Tài khoản của tôi — xem thử</title>';
echo '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>';
echo '<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=EB+Garamond:ital,wght@0,400;0,500;0,600;0,700;1,400;1,600&display=swap">';
echo '<style>' . $css . '</style>';
echo '<style>body{margin:0;background:#fff}.nntm-harness-tag{font:700 13px/1 system-ui;padding:10px 16px;background:#111;color:#fff}</style>';
echo '</head><body>';
echo '<div class="nntm-harness-tag">' . esc_html( $nhan ) . '</div>';
echo '<main id="nntm-noi-dung-chinh" class="nntm-tk">' . $noi_dung . '</main>';
echo '<script>' . $js . '</script>';
echo '</body></html>';
