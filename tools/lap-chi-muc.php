<?php
/**
 * Lập chỉ mục tìm kiếm cho kho nội dung (ảnh + PDF) — Phase 2.
 *
 *   "C:/xampp8_2/php/php.exe" tools/lap-chi-muc.php                   chỉ in tình trạng
 *   "C:/xampp8_2/php/php.exe" tools/lap-chi-muc.php --thieu --chay    lập chỉ mục phần còn thiếu, chạy tới hết
 *   "C:/xampp8_2/php/php.exe" tools/lap-chi-muc.php --quyen --chay    gắn lại bài chứa + quyền (không gọi dịch vụ)
 *   "C:/xampp8_2/php/php.exe" tools/lap-chi-muc.php --tat-ca --chay   làm lại toàn bộ (vd. sau khi đổi model)
 *   "C:/xampp8_2/php/php.exe" tools/lap-chi-muc.php --chay            chỉ xử lý hàng đợi đang có
 *
 * Bỏ --chay thì chỉ xếp hàng — WP-Cron làm dần mỗi phút. Dòng lệnh không bị
 * giới hạn thời gian như cron qua web, hợp với kho lớn nhập ở Phase 1.
 *
 * OCR trang PDF scan là hàng đợi riêng: tools/ocr-pdf.php --chay.
 *
 * @package NNTM
 */

if ( PHP_SAPI !== 'cli' ) {
	exit( 'Chi chay tu dong lenh.' );
}

$_SERVER['HTTP_HOST']   = 'nntm.com';
$_SERVER['REQUEST_URI'] = '/';
require_once __DIR__ . '/../wp-load.php';

if ( ! function_exists( 'nntm_search_cm_chay_lo' ) ) {
	exit( "Plugin nntm-search chua bat hoac chua co hang doi chi muc.\n" );
}

$co = static fn( string $c ): bool => in_array( $c, $argv, true );

$in_tinh_trang = static function (): void {
	$tk = nntm_search_cm_thong_ke();
	echo str_repeat( '-', 60 ) . "\n";
	printf( "Anh : %d/%d co chi muc, %d chua gan bai%s\n", $tk['anh_co'], $tk['anh_tong'], $tk['anh_mo_coi'], nntm_search_image_enabled() ? '' : '  (TIM BANG ANH DANG TAT)' );
	printf( "PDF : %d/%d co chi muc (%d trang), %d chua gan an pham, %d trang cho OCR%s\n", $tk['pdf_co'], $tk['pdf_tong'], $tk['pdf_trang'], $tk['pdf_mo_coi'], $tk['ocr_cho'], nntm_search_pdf_enabled() ? '' : '  (TIM TRONG PDF DANG TAT)' );
	printf( "Hang doi: %d, loi: %d\n", $tk['cho'], $tk['loi'] );
	echo str_repeat( '-', 60 ) . "\n";
};

$in_tinh_trang();

$pham_vi = $co( '--thieu' ) ? 'thieu' : ( $co( '--quyen' ) ? 'quyen' : ( $co( '--tat-ca' ) ? 'tat_ca' : '' ) );

if ( '' !== $pham_vi ) {
	delete_option( NNTM_SEARCH_CM_DUNG );
	printf( "Da xep hang %d file (%s).\n", nntm_search_cm_xep_hang_loat( $pham_vi ), $pham_vi );
}

if ( ! $co( '--chay' ) ) {
	exit( '' !== $pham_vi ? "WP-Cron se xu ly dan. Them --chay de lam ngay.\n" : '' );
}

delete_option( NNTM_SEARCH_CM_DUNG );
$bat_dau = microtime( true );
$tong    = 0;
$dung_im = 0;

while ( nntm_search_cm_ke_tiep() ) {
	$truoc = nntm_search_cm_thong_ke()['cho'];
	$n     = nntm_search_cm_chay_lo( 60 );
	$tong += $n;
	$tk    = nntm_search_cm_thong_ke();

	printf( "+%d file — con cho %d, loi %d (%.0fs)\n", $n, $tk['cho'], $tk['loi'], microtime( true ) - $bat_dau );

	$dung = nntm_search_cm_dang_tam_dung();
	if ( $dung ) {
		exit( 'Tam dung: ' . $dung['ly_do'] . "\n" );
	}

	// Không nhúc nhích 3 lượt liền (cron đang giữ khoá, file cứ thử lại) thì thôi.
	$dung_im = $tk['cho'] < $truoc ? 0 : $dung_im + 1;
	if ( $dung_im >= 3 ) {
		exit( "Khong tien trien sau 3 luot — xem file loi o Cong cu > Chi muc tim kiem.\n" );
	}
}

printf( "Xong: %d file trong %.0f giay.\n", $tong, microtime( true ) - $bat_dau );
$in_tinh_trang();
