<?php
/**
 * Kiem tra hoi quy: tim kiem PDF khong duoc lo nguyen van trang sach cho nguoi
 * chua mua (nhanh "bao-mat-tim-kiem-pdf").
 *
 *   "C:/xampp8_2/php/php.exe" tools/kiem-tra-bao-mat-tim-kiem-pdf.php
 *
 * LO HONG DA VA: nntm_search_pdf_rows_from() (nntm-search/includes/pdf.php)
 * chi kiem nntm_search_can_view() — muc do do CHI phan biet 'public'/'member',
 * khong biet "da mua" la gi, va voi an pham dang ban thi luon coi la 'public'
 * (xem chu thich ⚠️ o acl.php quanh dong 96: muc do do quyet dinh THE an pham
 * co hien ra khong, khong phai khung tra tien). Ket qua: khach chua dang nhap
 * go dung tu khoa la doc duoc nguyen van trang sach dang khoa.
 *
 * Script nay dung du lieu THAT (mot an pham 'publish' co san) nhung chi khoa no
 * TAM THOI trong MOT transaction roi ROLLBACK — khong doi gi vinh vien. Bang
 * nntm_pdf_pages/nntm_payos_orders la InnoDB nen ROLLBACK xoa sach moi thay doi
 * du lieu; rieng CREATE TABLE (DDL) gay commit ngam nen phai lam TRUOC khi mo
 * transaction (xem buoc 0 ben duoi).
 *
 * VI SAO PHAI TU KICH HOAT nntm-library + nntm-payos: o CSDL local hai plugin
 * nay dang TAT (xem wp_options.active_plugins) nhung kich ban can ham that cua
 * chung (nntm_lib_duoc_doc_tep(), nntm_payos_da_mua()) de mo phong dung "da
 * mua" khac "chua mua" — khong the danh gia dieu do bang du lieu gia. Bom filter
 * vao option_active_plugins TRUOC wp-load.php la cach chuan de WordPress tu
 * require hai tep plugin nay nhu dang bat that, khong dung o dau CSDL.
 *
 * @package NNTM
 */

if ( PHP_SAPI !== 'cli' ) {
	exit( "Chi chay tu dong lenh.\n" );
}

$_SERVER['HTTP_HOST']   = 'nntm.com';
$_SERVER['REQUEST_URI'] = '/';

$GLOBALS['wp_filter']['option_active_plugins'][10][] = array(
	'function'      => function ( $p ) {
		$p = (array) $p;
		foreach ( array( 'nntm-library/nntm-library.php', 'nntm-payos/nntm-payos.php' ) as $x ) {
			if ( ! in_array( $x, $p, true ) ) {
				$p[] = $x;
			}
		}
		return $p;
	},
	'accepted_args' => 1,
);

require_once __DIR__ . '/../wp-load.php';

/* -------------------------------------------------------------------------
 * Ha tang kiem tra: dem PASS/FAIL, khong dung phpunit vi day la script CLI
 * doc lap, chay truc tiep tren CSDL dev.
 * ------------------------------------------------------------------------- */

$ktbm_so_loi = 0;
$ktbm_so_dem = 0;

/**
 * Ghi mot khang dinh va cong don so lieu.
 *
 * @param bool   $dung_khong Dieu kien mong doi la dung.
 * @param string $mo_ta      Mo ta ngan gon.
 */
function ktbm_assert( bool $dung_khong, string $mo_ta ): void {
	global $ktbm_so_loi, $ktbm_so_dem;

	++$ktbm_so_dem;

	if ( $dung_khong ) {
		printf( "  PASS  %s\n", $mo_ta );
		return;
	}

	printf( "  FAIL  %s\n", $mo_ta );
	++$ktbm_so_loi;
}

if ( ! function_exists( 'nntm_lib_duoc_doc_tep' )
	|| ! function_exists( 'nntm_payos_da_mua' )
	|| ! function_exists( 'nntm_payos_bang' )
	|| ! function_exists( 'nntm_search_pdf_rows_from' )
) {
	fwrite( STDERR, "Thieu ham can thiet — kiem tra plugin co force-load duoc khong (xem loi tren, neu co).\n" );
	exit( 1 );
}

/*
 * NGUY HIEM DA DO DUOC BANG TAY (dung lai o day cho lan chay sau):
 *
 * nntm-library/includes/di-doi.php moc vao 'added_post_meta'/'updated_post_meta'
 * va, moi khi ai do ghi meta '_nntm_pdf_file' hoac '_nntm_pdf_xem_thu', DI CHUYEN
 * THAT tep tren DIA (rename/copy+unlink) ra kho rieng NGOAI wp-content/uploads —
 * KHONG chi ghi CSDL. update_post_meta() ben duoi (buoc 1) goi dung hook nay.
 *
 * ROLLBACK chi hoan tac CSDL (transaction), KHONG hoan tac thao tac tren DIA —
 * lan chay dau tien cua script nay da vo tinh doi tep that
 * wp-content/uploads/2026/08/luan-ve-tu-dieu-de.pdf ra ngoai thu muc du an. Go
 * hook nay TRUOC khi dung du lieu de khong lap lai su co.
 */
remove_action( 'added_post_meta', 'nntm_lib_tu_dong_di_doi', 10 );
remove_action( 'updated_post_meta', 'nntm_lib_tu_dong_di_doi', 10 );

echo "=== 0. Chuan bi (ngoai transaction — co the co DDL) ===\n";

global $wpdb;

/*
 * Bang don hang cua nntm-payos co the chua ton tai vi plugin dang tat o local
 * (khong ai tung kich hoat de chay register_activation_hook). CREATE TABLE gay
 * implicit commit trong MySQL/InnoDB, nen phai lam O DAY, TRUOC khi mo
 * transaction — lam trong transaction se khoa khong ROLLBACK duoc nua.
 */
$bang_don_hang = nntm_payos_bang();

if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $bang_don_hang ) ) !== $bang_don_hang ) {
	nntm_payos_dung_bang();
	printf( "  Da tao bang %s (chua co san).\n", $bang_don_hang );
} else {
	printf( "  Bang %s da co san.\n", $bang_don_hang );
}

$uploads  = wp_get_upload_dir();
$duong_du = '2026/08/luan-ve-tu-dieu-de.pdf';
$duong_tp = trailingslashit( (string) $uploads['basedir'] ) . $duong_du;

if ( ! is_readable( $duong_tp ) ) {
	fwrite( STDERR, "Khong thay tep mau: {$duong_tp}\n" );
	exit( 1 );
}

$pub_id = (int) $wpdb->get_var(
	"SELECT ID FROM {$wpdb->posts} WHERE post_type = 'nntm_publication' AND post_status = 'publish' ORDER BY ID ASC LIMIT 1"
);

if ( ! $pub_id ) {
	fwrite( STDERR, "Khong co an pham 'publish' nao trong CSDL de muon dung tam thoi.\n" );
	exit( 1 );
}

$buyer_id = (int) $wpdb->get_var( "SELECT ID FROM {$wpdb->users} ORDER BY ID ASC LIMIT 1" );

if ( ! $buyer_id ) {
	fwrite( STDERR, "Khong co user nao trong CSDL de dong vai nguoi da mua.\n" );
	exit( 1 );
}

printf( "  An pham muon tam thoi de khoa: #%d (%s)\n", $pub_id, get_the_title( $pub_id ) );
printf( "  Nguoi dung dong vai da mua:    #%d\n", $buyer_id );

// Chup lai gia tri THAT truoc khi dung, de kiem sau ROLLBACK no tro ve dung cho.
$gia_truoc_khi_test = get_post_meta( $pub_id, '_nntm_pub_gia', true );
$tep_truoc_khi_test  = get_post_meta( $pub_id, '_nntm_pdf_file', true );

const KTBM_MARKER_KHOA  = 'BI-MAT-TRA-PHI-KHOA';
const KTBM_MARKER_MOCOI = 'BI-MAT-TRA-PHI-MOCOI';
const KTBM_MARKER_CHUNG = 'BI-MAT-TRA-PHI';

$att_id = 0;
$ma_don = 0;

$wpdb->query( 'START TRANSACTION' );

try {
	echo "\n=== 1. Dung du lieu (trong transaction) ===\n";

	/*
	 * Attachment PDF MOI, KHONG gan post_parent — khong dung chung voi an pham
	 * nao khac, de duoc dung dung y "tep rieng cua mot cuon dang khoa".
	 */
	$att_id = wp_insert_post(
		array(
			'post_type'      => 'attachment',
			'post_mime_type' => 'application/pdf',
			'post_title'     => 'Kiem tra bao mat tim kiem PDF (tam thoi)',
			'post_status'    => 'inherit',
			'post_parent'    => 0,
			'meta_input'     => array( '_wp_attached_file' => $duong_du ),
		),
		true
	);

	if ( is_wp_error( $att_id ) || ! $att_id ) {
		throw new Exception( 'Khong tao duoc attachment thu nghiem: ' . ( is_wp_error( $att_id ) ? $att_id->get_error_message() : 'khong ro loi' ) );
	}

	printf( "  Attachment thu nghiem #%d -> %s\n", $att_id, $duong_du );

	// Gan tep cho an pham THAT roi dat gia -> an pham tu dong bi khoa (xem
	// nntm-payos/includes/gia.php ham nntm_payos_gia_thi_khoa(): co gia la khoa).
	update_post_meta( $pub_id, '_nntm_pdf_file', $att_id );
	update_post_meta( $pub_id, '_nntm_pub_gia', 50000 );
	wp_cache_flush();

	// Dong pdf_pages CHINH: gan dung voi an pham dang khoa o tren.
	$noi_dung_khoa = 'Doan can tra phi, chi nguoi da mua duoc doc: ' . KTBM_MARKER_KHOA . ' — noi ve Tu Dieu De.';

	$wpdb->insert(
		nntm_search_table_pdf_pages(),
		array(
			'attachment_id' => $att_id,
			'post_id'       => $pub_id,
			'page_no'       => 57,
			'content'       => $noi_dung_khoa,
			'folded'        => nntm_search_fold( $noi_dung_khoa ),
			'source'        => 'text',
			'updated_at'    => current_time( 'mysql' ),
		),
		array( '%d', '%d', '%d', '%s', '%s', '%s', '%s' )
	);

	/*
	 * Dong pdf_pages "mo coi" (post_id = 0) nhung CUNG mot attachment voi sach
	 * dang khoa o tren.
	 *
	 * VI SAO KHONG DUNG mot attachment_id "khong ton tai" (moi doc de nghi ban
	 * dau): da do bang tay — voi mot tep KHONG gan an pham nao ca
	 * (nntm_lib_cac_an_pham_cua_pdf() tra ve rong), nntm_lib_duoc_doc_tep() mac
	 * dinh MO (goi filter nntm_an_pham_can_access voi mac dinh true, va khong
	 * ai cham vao gia tri do khi $post la null — xem class-nghi-quy-quiz.php
	 * loc_quyen_xem()). Tuc la mot tep "mo coi that su" luon duoc coi la doc
	 * duoc, DUNG NHU THIET KE, vi khong co gi de khoa ma tu choi.
	 *
	 * Muon dong "mo coi" (post_id=0 o BANG NAY) thuc su khong doc duoc, phai
	 * gan no voi mot tep MA nntm-library BIET la dang khoa — mo phong dung
	 * kieu lech du lieu that: nntm_search_pdf_owner() (chi lay MOT chu so huu
	 * theo post_parent/postmeta) tra ve 0 cho dong nay du attachment_id vẫn
	 * dang duoc mot an pham dang ban tro toi qua _nntm_pdf_file.
	 */
	$noi_dung_mocoi = 'Trang mo coi trong chi muc nhung tep van thuoc sach dang khoa: ' . KTBM_MARKER_MOCOI . '.';

	$wpdb->insert(
		nntm_search_table_pdf_pages(),
		array(
			'attachment_id' => $att_id,
			'post_id'       => 0,
			'page_no'       => 58,
			'content'       => $noi_dung_mocoi,
			'folded'        => nntm_search_fold( $noi_dung_mocoi ),
			'source'        => 'text',
			'updated_at'    => current_time( 'mysql' ),
		),
		array( '%d', '%d', '%d', '%s', '%s', '%s', '%s' )
	);

	// Don hang DA TRA TIEN cua nguoi mua, cho DUNG an pham dang khoa o tren.
	$ma_don = (int) ( time() . wp_rand( 100, 999 ) );

	$wpdb->insert(
		$bang_don_hang,
		array(
			'user_id'    => $buyer_id,
			'post_id'    => $pub_id,
			'amount'     => 50000,
			'status'     => 'paid',
			'order_code' => $ma_don,
			'created_at' => current_time( 'mysql' ),
		),
		array( '%d', '%d', '%d', '%s', '%d', '%s' )
	);

	wp_cache_flush();

	// FULLTEXT khong thay hang chua commit, nen lay hit bang SELECT thuong —
	// dung nhu duong lui LIKE thuc su dung trong nntm_search_pdf_pages_like().
	$hits = $wpdb->get_results(
		$wpdb->prepare(
			'SELECT attachment_id, post_id, page_no, content, source, 0 AS score
			 FROM ' . nntm_search_table_pdf_pages() . '
			 WHERE content LIKE %s
			 ORDER BY page_no ASC',
			'%' . $wpdb->esc_like( KTBM_MARKER_CHUNG ) . '%'
		)
	);

	ktbm_assert( 2 === count( $hits ), 'Lay duoc dung 2 dong vua chen (khoa + mo coi) bang SELECT thuong' );

	echo "\n=== 2. Khach — chua dang nhap (user 0) ===\n";

	wp_set_current_user( 0 );
	wp_cache_flush();

	$rows_khach = nntm_search_pdf_rows_from( $hits, 'tu dieu de' );
	$encoded    = (string) wp_json_encode( $rows_khach );

	$dong_khoa_khach = null;

	foreach ( $rows_khach as $r ) {
		if ( (int) $r['id'] === $pub_id ) {
			$dong_khoa_khach = $r;
		}
	}

	ktbm_assert( null !== $dong_khoa_khach, 'Dong sach dang khoa VAN CON trong ket qua (nhu, khong bien mat)' );

	if ( null !== $dong_khoa_khach ) {
		ktbm_assert( '' === $dong_khoa_khach['excerpt'], 'Excerpt rong voi khach chua mua' );
		ktbm_assert( '' === $dong_khoa_khach['cta_2'], 'cta_2 rong (khong nut Tai xuong) voi khach chua mua' );
		ktbm_assert( ! array_key_exists( 'cta_2_url', $dong_khoa_khach ), 'Khong dat cta_2_url voi khach chua mua (endpoint tai se 403)' );
		ktbm_assert(
			false === mb_strpos( (string) wp_json_encode( $dong_khoa_khach ), KTBM_MARKER_KHOA ),
			'Khong mot truong nao cua dong (title/excerpt/label) chua chu bi mat, voi khach'
		);
	}

	ktbm_assert(
		1 === count( $rows_khach ),
		'Dong "mo coi" gan voi tep dang khoa bi loai HOAN TOAN khoi ket qua cua khach (khong co an pham de moi mua)'
	);

	ktbm_assert(
		false === mb_strpos( $encoded, KTBM_MARKER_MOCOI ),
		'Chu bi mat cua dong mo coi khong lot ra duoi bat ky dang nao trong ket qua cua khach'
	);

	echo "\n=== 3. REST /nntm-search/v1/suggest — khach ===\n";

	$yeu_cau = new WP_REST_Request( 'GET', '/nntm-search/v1/suggest' );
	$yeu_cau->set_param( 'q', KTBM_MARKER_CHUNG );
	$yeu_cau->set_param( 'group', 'pdf' );

	$phan_hoi   = rest_do_request( $yeu_cau );
	$json_rest  = (string) wp_json_encode( $phan_hoi->get_data() );

	// FULLTEXT khong thay duoc hang chua commit nen co the khong ra dong nao —
	// khang dinh chinh la KHONG CO CHU BI MAT o bat ky dau trong JSON tra ve,
	// bat ke co dong nao khop hay khong.
	ktbm_assert(
		false === mb_strpos( $json_rest, KTBM_MARKER_KHOA ) && false === mb_strpos( $json_rest, KTBM_MARKER_MOCOI ),
		'REST suggest khong lo chu bi mat cho khach (co the tra ve rong vi FULLTEXT chua thay hang chua commit — van PASS)'
	);

	echo "\n=== 4. Nguoi da mua (user #{$buyer_id}) ===\n";

	wp_set_current_user( $buyer_id );
	wp_cache_flush();

	$rows_mua = nntm_search_pdf_rows_from( $hits, 'tu dieu de' );

	$dong_khoa_mua = null;

	foreach ( $rows_mua as $r ) {
		if ( (int) $r['id'] === $pub_id ) {
			$dong_khoa_mua = $r;
		}
	}

	ktbm_assert( null !== $dong_khoa_mua, 'Dong sach da mua co trong ket qua cua nguoi mua' );

	if ( null !== $dong_khoa_mua ) {
		ktbm_assert(
			false !== mb_strpos( $dong_khoa_mua['excerpt'], KTBM_MARKER_KHOA ),
			'Nguoi da mua thay duoc excerpt THAT, co chu bi mat'
		);
		ktbm_assert( '' !== $dong_khoa_mua['cta_2'], 'Nguoi da mua van co nut Tai xuong (cta_2)' );
	}

	ktbm_assert(
		2 === count( $rows_mua ),
		'Voi nguoi da mua, ca dong "mo coi" cung hien ra (cung mot tep vua duoc mo khoa)'
	);
} catch ( Throwable $loi_bat ) {
	fwrite( STDERR, 'LOI: ' . $loi_bat->getMessage() . "\n" . $loi_bat->getTraceAsString() . "\n" );
	++$ktbm_so_loi;
} finally {
	$wpdb->query( 'ROLLBACK' );
	wp_set_current_user( 0 );
	wp_cache_flush();
}

echo "\n=== 5. Sau ROLLBACK — CSDL phai tro ve nguyen trang ===\n";

ktbm_assert(
	get_post_meta( $pub_id, '_nntm_pub_gia', true ) === $gia_truoc_khi_test,
	'Gia cua an pham that tro ve DUNG gia tri truoc khi test (khong con 50000 neu truoc do khong co)'
);

ktbm_assert(
	get_post_meta( $pub_id, '_nntm_pdf_file', true ) === $tep_truoc_khi_test,
	'Tep PDF cua an pham that tro ve DUNG gia tri truoc khi test'
);

ktbm_assert( ! ( $att_id && get_post( $att_id ) ), 'Attachment thu nghiem khong con ton tai' );

$con_lai_pdf_pages = (int) $wpdb->get_var(
	$wpdb->prepare(
		'SELECT COUNT(*) FROM ' . nntm_search_table_pdf_pages() . ' WHERE content LIKE %s',
		'%' . $wpdb->esc_like( KTBM_MARKER_CHUNG ) . '%'
	)
);

ktbm_assert( 0 === $con_lai_pdf_pages, 'Khong con dong pdf_pages thu nghiem nao sot lai trong CSDL' );

$con_lai_don_hang = $ma_don
	? (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$bang_don_hang} WHERE order_code = %d", $ma_don ) )
	: 0;

ktbm_assert( 0 === $con_lai_don_hang, 'Khong con don hang thu nghiem nao sot lai trong CSDL' );

echo "\n=== Ket qua ===\n";
printf( "%d/%d kiem tra PASS.\n", $ktbm_so_dem - $ktbm_so_loi, $ktbm_so_dem );

exit( $ktbm_so_loi > 0 ? 1 : 0 );
