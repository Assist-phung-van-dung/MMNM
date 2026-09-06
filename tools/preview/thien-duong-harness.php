<?php
/*
 * Dựng trang xem thử khối Thiền Đường ở dạng MỘT tệp HTML tự chứa.
 *
 * Browser pane chặn mọi subresource của vhost nntm.com nên mở thẳng trang thật
 * thì mất sạch CSS, đo đạc gì cũng sai. Script này chạy bằng PHP CLI: nạp
 * WordPress, dựng khối ở cả hai trạng thái (chưa đăng nhập / đã đăng nhập) rồi
 * nhúng luôn CSS vào trong tệp, mở bằng file:// là thấy đúng như trang thật.
 *
 *   php tools/preview/thien-duong-harness.php > tools/preview/thien-duong.html
 */

define( 'WP_USE_THEMES', false );
require dirname( __DIR__, 2 ) . '/wp-load.php';

$theme = get_template_directory();

$css_files = array(
	$theme . '/assets/css/tokens.css',
	$theme . '/assets/css/tokens.generated.css',
	$theme . '/assets/css/base.css',
	$theme . '/blocks/thien-duong/style.css',
	$theme . '/assets/css/pages/lien-dan-figma.css',
);

$css = '';
foreach ( $css_files as $file ) {
	if ( is_readable( $file ) ) {
		$css .= "\n/* ===== " . basename( $file ) . " ===== */\n" . file_get_contents( $file );
	}
}

require_once $theme . '/blocks/thien-duong/inc/register-track-audio-meta.php';
require_once $theme . '/blocks/thien-duong/inc/render-thien-duong.php';

/*
 * Kho nhạc thật ở local chỉ có vài bài, mà thứ cần soi lại là lúc danh sách
 * dài. Truyền --nhan-ban=N để nhân bản danh sách lên cho đủ dài mà xem.
 */
$nhan_ban = 1;
$ep_rong  = false;
foreach ( array_slice( $argv, 1 ) as $tham_so ) {
	if ( 0 === strpos( $tham_so, '--nhan-ban=' ) ) {
		$nhan_ban = max( 1, (int) substr( $tham_so, 11 ) );
	}
	if ( '--rong' === $tham_so ) {
		$ep_rong = true;
	}
}

/*
 * Browser pane chỉ rộng hơn 500px nên không tài nào xem bản màn rộng ở cỡ chữ
 * đọc được. Cờ --rong gỡ hết khối @media thu nhỏ ra khỏi CSS, để bản màn rộng
 * hiện ngay ở bề ngang hẹp mà soi cho kỹ.
 */
function nntm_harness_bo_media( string $css ): string {
	foreach ( array( '@media (max-width: 1151px)', '@media (max-width: 767px)', '@media (max-width: 480px)' ) as $mo_dau ) {
		while ( false !== ( $vi_tri = strpos( $css, $mo_dau ) ) ) {
			$con  = 0;
			$chay = strpos( $css, '{', $vi_tri );
			for ( $i = $chay; $i < strlen( $css ); $i++ ) {
				if ( '{' === $css[ $i ] ) {
					$con++;
				} elseif ( '}' === $css[ $i ] ) {
					$con--;
					if ( 0 === $con ) {
						$css = substr( $css, 0, $vi_tri ) . substr( $css, $i + 1 );
						break 1;
					}
				}
			}
		}
	}

	return $css;
}

$guest = nntm_thien_duong_render_guest_preview( 20, 'newest', 'demo' );

$tracks = nntm_thien_duong_get_tracks( 20, 'newest' );

if ( $nhan_ban > 1 && $tracks ) {
	$goc = $tracks;
	for ( $lan = 1; $lan < $nhan_ban; $lan++ ) {
		foreach ( $goc as $bai ) {
			$bai['id']    = $bai['id'] + $lan * 10000;
			$tracks[]     = $bai;
		}
	}
}
$member = $tracks ? nntm_thien_duong_render_player( $tracks ) : '<p>Chưa có bản nhạc nào.</p>';

$cover = '<span class="nntm-thien-duong__cover-placeholder" aria-hidden="true"></span>';

function nntm_harness_section( string $label, string $player, string $cover ): string {
	return '
<section class="nntm-thien-duong">
	<div class="nntm-thien-duong__inner">
		<div class="nntm-thien-duong__header">
			<h2 class="nntm-thien-duong__heading">Thiền Đường</h2>
			<p class="nntm-thien-duong__subheading">' . esc_html( $label ) . '</p>
		</div>
		<div class="nntm-thien-duong__embed">
			<div class="nntm-thien-duong__cover">' . $cover . '</div>
			<div class="nntm-thien-duong__player">' . $player . '</div>
		</div>
	</div>
</section>';
}

echo '<!doctype html><html lang="vi"><head><meta charset="utf-8">';
echo '<meta name="viewport" content="width=device-width, initial-scale=1">';
echo '<title>Thiền Đường — xem thử</title>';
echo '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>';
echo '<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;700&family=Lora:wght@400;500&family=EB+Garamond:wght@400;600&family=Inter:wght@400;500&family=Questrial&display=swap">';
echo '<style>' . ( $ep_rong ? nntm_harness_bo_media( $css ) : $css ) . '</style>';
echo '<style>body{margin:0;background:#fff}.nntm-harness-tag{font:700 13px/1 system-ui;padding:10px 16px;background:#111;color:#fff}</style>';
if ( $ep_rong ) {
	echo '<style>.nntm-thien-duong__inner{padding:20px 16px}.nntm-thien-duong__embed{grid-template-columns:1fr}.nntm-thien-duong__cover{display:none}.nntm-thien-duong__player{height:385px;min-height:385px}</style>';
}
echo '</head><body>';
echo '<script>' . file_get_contents( $theme . '/blocks/thien-duong/view.js' ) . '</script>';
echo '<div class="nntm-harness-tag">CHƯA ĐĂNG NHẬP</div>';
echo nntm_harness_section( 'Trạng thái khách', $guest, $cover );
echo '<div class="nntm-harness-tag">ĐÃ ĐĂNG NHẬP</div>';
echo nntm_harness_section( 'Trạng thái thành viên', $member, $cover );
echo '</body></html>';
