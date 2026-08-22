<?php
/**
 * Gan tep PDF cho cac an pham CHUA co tep, de trinh doc /doc/ va bia sach
 * variant "books" cua block nntm/card-list hoat dong ngay sau khi keo code moi
 * ve — khong phai vao sua tay tung an pham.
 *
 *   "C:/xampp8_2/php/php.exe" tools/gan-pdf-an-pham.php
 *   "C:/xampp8_2/php/php.exe" tools/gan-pdf-an-pham.php --mot-lan
 *   "C:/xampp8_2/php/php.exe" tools/gan-pdf-an-pham.php --khoa=3
 *   "C:/xampp8_2/php/php.exe" tools/gan-pdf-an-pham.php xoa
 *
 * VI SAO CAN SCRIPT NAY:
 * hanh vi "bam bia sach vao thang trinh doc" o blocks/card/inc/render-card.php
 * KHONG do thuoc tinh block nao bat/tat — no doc postmeta `_nntm_pdf_file` cua
 * tung an pham. An pham chua gan tep thi nntm_doc_url() tra chuoi rong va the
 * ve trang chi tiet nhu cu. Nen keo code ve ma khong ghi postmeta thi block
 * trong nhu khong doi gi.
 *
 * CAC TEP PDF DA NAM SAN trong wp-content/uploads/ (theo Git) nhung KHONG co
 * ban ghi attachment trong wp_posts, nen bo chon tep cua meta box khong thay
 * chung. Script tao ban ghi attachment TRO VAO DUNG TEP DANG CO — khong chep
 * them, khong sinh trung.
 *
 * Che do mac dinh gan LAP VONG: it tep hon so an pham thi dung lai tep cho
 * nhieu an pham, de moi the trong danh sach deu bam vao doc duoc khi test.
 * Muon moi tep chi dung dung mot lan thi them --mot-lan.
 *
 * Chay nhieu lan duoc, KHONG ghi de an pham da co tep san. CHI dung o local /
 * moi truong dung thu.
 *
 * @package NNTM
 */

if ( PHP_SAPI !== 'cli' ) {
	exit( 'Chi chay tu dong lenh.' );
}

$_SERVER['HTTP_HOST']   = 'nntm.com';
$_SERVER['REQUEST_URI'] = '/';
require_once __DIR__ . '/../wp-load.php';

/** Danh dau attachment do script tao, de che do xoa biet dau la cua minh. */
const NNTM_GAN_PDF_DAU_ATT = '_nntm_pdf_script_asset';

/** Danh dau an pham do script gan, de che do xoa khong dung vao ban gan tay. */
const NNTM_GAN_PDF_DAU_PUB = '_nntm_pdf_gan_boi_script';

$doi_so  = array_slice( $argv, 1 );
$xoa     = in_array( 'xoa', $doi_so, true );
$mot_lan = in_array( '--mot-lan', $doi_so, true );
$so_khoa = 0;

foreach ( $doi_so as $ds ) {
	if ( 0 === strpos( $ds, '--khoa=' ) ) {
		$so_khoa = absint( substr( $ds, 7 ) );
	}
}

/* -------------------------------------------------------------------------
 * Che do xoa — chi thu hoi nhung gi script tung gan.
 *
 * KHONG xoa ban ghi attachment: tep PDF nam trong Git, wp_delete_attachment()
 * se xoa luon tep tren dia. Ban ghi attachment con lai thi vo hai va lan chay
 * sau dung lai duoc.
 * ------------------------------------------------------------------------- */

if ( $xoa ) {
	$da_gan = get_posts(
		array(
			'post_type'      => 'nntm_publication',
			'post_status'    => 'any',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'meta_key'       => NNTM_GAN_PDF_DAU_PUB, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
		)
	);

	foreach ( $da_gan as $pub_id ) {
		delete_post_meta( (int) $pub_id, '_nntm_pdf_file' );
		delete_post_meta( (int) $pub_id, '_nntm_pub_khoa' );
		delete_post_meta( (int) $pub_id, NNTM_GAN_PDF_DAU_PUB );
	}

	printf( "Da thu hoi tep khoi %d an pham. Ban ghi attachment giu lai.\n", count( $da_gan ) );
	exit;
}

/* -------------------------------------------------------------------------
 * 1. Quet PDF co san trong uploads, tao attachment cho tep nao chua co.
 * ------------------------------------------------------------------------- */

echo "=== 1. Tep PDF trong uploads ===\n";

$uploads = wp_get_upload_dir();
$goc     = rtrim( str_replace( '\\', '/', $uploads['basedir'] ), '/' );

if ( ! is_dir( $goc ) ) {
	exit( "Khong thay thu muc uploads.\n" );
}

$duyet = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $goc, FilesystemIterator::SKIP_DOTS ) );
$tep   = array();

foreach ( $duyet as $item ) {
	if ( $item->isFile() && 0 === strcasecmp( 'pdf', $item->getExtension() ) ) {
		$tep[] = str_replace( '\\', '/', $item->getPathname() );
	}
}

sort( $tep );

if ( ! $tep ) {
	exit( "Khong co tep PDF nao trong uploads — tai len vai tep roi chay lai.\n" );
}

$att_ids = array();

foreach ( $tep as $duong ) {
	$tuong_doi = ltrim( substr( $duong, strlen( $goc ) ), '/' );

	// Da co attachment tro vao dung tep nay chua.
	$da_co = get_posts(
		array(
			'post_type'      => 'attachment',
			'post_status'    => 'inherit',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'meta_key'       => '_wp_attached_file', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
			'meta_value'     => $tuong_doi,          // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
		)
	);

	if ( $da_co ) {
		$att_ids[] = (int) $da_co[0];
		printf( "  #%-5d %-46s (da co san)\n", (int) $da_co[0], mb_substr( $tuong_doi, 0, 44 ) );
		continue;
	}

	$ten = pathinfo( $tuong_doi, PATHINFO_FILENAME );

	$att_id = wp_insert_attachment(
		array(
			'post_mime_type' => 'application/pdf',
			'post_title'     => ucwords( str_replace( array( '-', '_' ), ' ', $ten ) ),
			'post_status'    => 'inherit',
		),
		$duong
	);

	if ( is_wp_error( $att_id ) || ! $att_id ) {
		printf( "  LOI tao attachment: %s\n", $tuong_doi );
		continue;
	}

	/*
	 * KHONG goi wp_generate_attachment_metadata(): voi PDF no can Imagick de ve
	 * anh bia, may khong co Imagick thi bao loi. Trinh doc chi can
	 * `_wp_attached_file` — wp_insert_attachment() da ghi san — de
	 * wp_get_attachment_url() tra ra duong dan.
	 */
	update_post_meta( (int) $att_id, NNTM_GAN_PDF_DAU_ATT, 1 );

	$att_ids[] = (int) $att_id;
	printf( "  #%-5d %-46s (moi tao)\n", (int) $att_id, mb_substr( $tuong_doi, 0, 44 ) );
}

if ( ! $att_ids ) {
	exit( "\nKhong tao duoc attachment nao.\n" );
}

/* -------------------------------------------------------------------------
 * 2. Gan cho cac an pham chua co tep.
 * ------------------------------------------------------------------------- */

printf( "\n=== 2. Gan cho an pham (%d tep) ===\n", count( $att_ids ) );

$chua_co = get_posts(
	array(
		'post_type'      => 'nntm_publication',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'orderby'        => 'date',
		'order'          => 'DESC',
		// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
		'meta_query'     => array(
			'relation' => 'OR',
			array(
				'key'     => '_nntm_pdf_file',
				'compare' => 'NOT EXISTS',
			),
			array(
				'key'     => '_nntm_pdf_file',
				'value'   => 0,
				'compare' => '<=',
				'type'    => 'NUMERIC',
			),
		),
	)
);

if ( ! $chua_co ) {
	echo "  Moi an pham deu da co tep — khong con gi de gan.\n";
	exit;
}

if ( $mot_lan ) {
	$chua_co = array_slice( $chua_co, 0, count( $att_ids ) );
}

$i    = 0;
$da   = 0;
$khoa = 0;

foreach ( $chua_co as $pub ) {
	$att_id = $att_ids[ $i % count( $att_ids ) ];
	++$i;

	update_post_meta( $pub->ID, '_nntm_pdf_file', $att_id );
	update_post_meta( $pub->ID, NNTM_GAN_PDF_DAU_PUB, 1 );

	/*
	 * Khoa may cuon dau de thu duong "chua thanh toan": khoa thi
	 * nntm_an_pham_da_thanh_toan() dang hardcode false (chua noi cong thanh
	 * toan) nen the quay ve trang chi tiet va /doc/ redirect ra — dung nhu
	 * thiet ke, khong phai loi.
	 */
	$bi_khoa = $khoa < $so_khoa;

	if ( $bi_khoa ) {
		update_post_meta( $pub->ID, '_nntm_pub_khoa', true );
		++$khoa;
	}

	++$da;

	if ( $da <= 10 ) {
		printf(
			"  #%-5d %-40s tep #%-5d %s\n",
			$pub->ID,
			mb_substr( $pub->post_title, 0, 38 ),
			$att_id,
			$bi_khoa ? 'KHOA -> trang chi tiet' : nntm_doc_url( $pub->ID )
		);
	}
}

if ( $da > 10 ) {
	printf( "  ... va %d an pham nua\n", $da - 10 );
}

printf( "\nXong: %d an pham duoc gan tep, %d trong so do bi khoa.\n", $da, $khoa );
echo "Mo lai trang co block Danh sach the variant \"books\" — bia sach gio bam vao thang /doc/.\n";
echo "Muon thu hoi: them doi so 'xoa'.\n";
