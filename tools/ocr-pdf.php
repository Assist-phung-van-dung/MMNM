<?php
/**
 * Lập chỉ mục lại PDF + OCR trang scan cho kho đã có (Phase 2, khảo sát câu 11–12).
 *
 *   "C:/xampp8_2/php/php.exe" tools/ocr-pdf.php                 xếp hàng OCR cho MỌI PDF (cron xử lý dần)
 *   "C:/xampp8_2/php/php.exe" tools/ocr-pdf.php --chay          xếp hàng rồi chạy OCR tới hết ngay tại đây
 *   "C:/xampp8_2/php/php.exe" tools/ocr-pdf.php --id=123 --chay chỉ một file
 *   "C:/xampp8_2/php/php.exe" tools/ocr-pdf.php --lam-lai       xoá chữ OCR cũ, làm lại từ đầu
 *
 * PDF tải lên TRƯỚC khi có OCR thì trang scan đã bị bỏ qua mà không để lại dấu
 * — cần chạy script này một lần sau khi cài Tesseract. Chạy bằng dòng lệnh
 * không bị giới hạn thời gian như cron qua web, hợp với lượng lớn.
 *
 * Cần dịch vụ Python đang chạy (tools/embed-service) và đã cài Tesseract + gói vie.
 *
 * @package NNTM
 */

if ( PHP_SAPI !== 'cli' ) {
	exit( 'Chi chay tu dong lenh.' );
}

$_SERVER['HTTP_HOST']   = 'nntm.com';
$_SERVER['REQUEST_URI'] = '/';
require_once __DIR__ . '/../wp-load.php';

if ( ! function_exists( 'nntm_search_ocr_chay_lo' ) ) {
	exit( "Plugin nntm-search chua bat hoac chua co OCR.\n" );
}

$chay    = in_array( '--chay', $argv, true );
$lam_lai = in_array( '--lam-lai', $argv, true );
$chi_id  = 0;
foreach ( $argv as $arg ) {
	if ( 0 === strpos( $arg, '--id=' ) ) {
		$chi_id = (int) substr( $arg, 5 );
	}
}

if ( ! nntm_search_ocr_enabled() ) {
	exit( "OCR dang tat (NNTM_SEARCH_PDF_ENABLED / NNTM_SEARCH_OCR_ENABLED).\n" );
}

// Kiểm dịch vụ trước — đỡ chạy hết thư viện rồi mới biết thiếu Tesseract.
$khoe = wp_remote_get( nntm_search_service_url() . '/ocr/khoe', array( 'timeout' => 10 ) );
$tt   = is_wp_error( $khoe ) ? null : json_decode( (string) wp_remote_retrieve_body( $khoe ), true );
if ( ! is_array( $tt ) ) {
	exit( 'Dich vu Python khong phan hoi tai ' . nntm_search_service_url() . "\n" );
}
echo 'OCR: ' . ( ! empty( $tt['san_sang'] ) ? 'san sang — tesseract ' . ( $tt['phien_ban'] ?? '?' ) : 'CHUA SAN SANG — ' . ( $tt['ly_do'] ?? '' ) ) . "\n";
if ( empty( $tt['san_sang'] ) && $chay ) {
	exit( "Dung lai: cai Tesseract + goi vie truoc (docs/15-ocr-pdf.md).\n" );
}

global $wpdb;

$ids = $chi_id > 0
	? array( $chi_id )
	: get_posts(
		array(
			'post_type'        => 'attachment',
			'post_status'      => 'inherit',
			'post_mime_type'   => 'application/pdf',
			'posts_per_page'   => -1,
			'fields'           => 'ids',
			'orderby'          => 'ID',
			'order'            => 'ASC',
			'suppress_filters' => true,
		)
	);

echo str_repeat( '-', 60 ) . "\n";

foreach ( $ids as $id ) {
	$id = (int) $id;

	if ( $lam_lai ) {
		$wpdb->delete( nntm_search_table_pdf_pages(), array( 'attachment_id' => $id, 'source' => 'ocr' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
	}

	$kq = nntm_search_index_pdf( $id );
	$t  = nntm_search_ocr_tinh_trang( $id );

	printf(
		"#%-5d %-45s %s\n",
		$id,
		mb_strimwidth( get_the_title( $id ), 0, 45, '…' ),
		is_wp_error( $kq )
			? 'LOI: ' . $kq->get_error_message()
			: sprintf( '%d trang chu, %d trang OCR san, cho OCR %d', $t['chu'], $t['ocr'], $t['cho'] )
	);
}

if ( ! $chay ) {
	echo str_repeat( '-', 60 ) . "\nDa xep hang. Cron se OCR dan " . nntm_search_ocr_so_trang_moi_lan() . " trang/phut. Chay lai voi --chay de lam ngay.\n";
	exit;
}

echo str_repeat( '-', 60 ) . "\n";

delete_option( NNTM_SEARCH_OCR_DUNG );
$bat_dau = microtime( true );
$tong    = 0;
$dung_im = 0;

while ( $att = nntm_search_ocr_pdf_ke_tiep() ) { // phpcs:ignore WordPress.CodeAnalysis.AssignmentInCondition.FoundInWhileCondition
	$lan   = microtime( true );
	$truoc = nntm_search_ocr_tinh_trang( $att )['cho'];
	$n     = nntm_search_ocr_chay_lo();
	$t     = nntm_search_ocr_tinh_trang( $att );

	// Lô không nhúc nhích (cron đang giữ khoá, dịch vụ treo…) 3 lần liền thì thôi.
	$dung_im = $t['cho'] < $truoc ? 0 : $dung_im + 1;
	if ( $dung_im >= 3 ) {
		exit( "Khong tien trien sau 3 lan (co the cron dang chay OCR song song). Thu lai sau.
" );
	}

	printf( "#%-5d +%d trang (%.1fs) — OCR %d, cho %d, loi %d\n", $att, $n, microtime( true ) - $lan, $t['ocr'], $t['cho'], $t['loi'] );

	$tong += $n;

	$dung = nntm_search_ocr_dang_tam_dung();
	if ( $dung ) {
		exit( 'Tam dung: ' . $dung['ly_do'] . "\n" );
	}
}

printf( "Xong: %d trang trong %.0f giay.\n", $tong, microtime( true ) - $bat_dau );
