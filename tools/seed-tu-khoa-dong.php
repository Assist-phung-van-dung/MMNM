<?php
/**
 * Seed du lieu MAU cho Tu khoa dong (Phase 2, phieu khao sat cau 33-34).
 *
 *   "C:/xampp8_2/php/php.exe" tools/seed-tu-khoa-dong.php
 *   "C:/xampp8_2/php/php.exe" tools/seed-tu-khoa-dong.php --bat=<ID hoac slug bai/trang>
 *
 * Chay nhieu lan duoc, KHONG tao trung (so theo tieu de). CHI dung o local.
 *
 * Viec cua script:
 *   1. Tao 4 tu khoa mau, danh dau meta _nntm_tkd_mau = 1 de khach xoa khi co
 *      danh sach that (cau 34: khach cung cap tu khoa + hinh).
 *   2. Hinh lay tu tools/test-assets/anh (dung lai attachment bootstrap-demo da
 *      nhap neu co — cung meta _nntm_demo_asset). "Hoa sen" co y khong co hinh
 *      (mau the chi co chu).
 *   3. Bat hieu ung tren bai "hoa-sen-no-giua-bun-nho" (hoac --bat=...).
 *
 * @package NNTM
 */

if ( PHP_SAPI !== 'cli' ) {
	exit( 'Chi chay tu dong lenh.' );
}

$_SERVER['HTTP_HOST']   = 'nntm.com';
$_SERVER['REQUEST_URI'] = '/';
require_once __DIR__ . '/../wp-load.php';
require_once ABSPATH . 'wp-admin/includes/image.php';

add_filter( 'nntm_duoc_xem_khu_han_che', '__return_true' );

if ( ! class_exists( '\NNTM\Core\Tu_Khoa_Dong' ) ) {
	exit( "Plugin nntm-core chua bat hoac chua co class Tu_Khoa_Dong.\n" );
}

use NNTM\Core\Tu_Khoa_Dong;

$bat = 'hoa-sen-no-giua-bun-nho';
foreach ( array_slice( $argv, 1 ) as $arg ) {
	if ( 0 === strpos( $arg, '--bat=' ) ) {
		$bat = substr( $arg, 6 );
	}
}

echo "Seed Tu khoa dong (du lieu mau)\n";
echo str_repeat( '-', 60 ) . "\n";

/**
 * Tim hoac nhap mot anh trong tools/test-assets/anh.
 */
function nntm_tkd_anh_mau( string $file ): int {
	$da_co = get_posts(
		array(
			'post_type'        => 'attachment',
			'post_status'      => 'inherit',
			'posts_per_page'   => 1,
			'fields'           => 'ids',
			'meta_key'         => '_nntm_demo_asset',
			'meta_value'       => $file,
			'suppress_filters' => true,
		)
	);
	if ( $da_co ) {
		return (int) $da_co[0];
	}

	$src = __DIR__ . '/test-assets/anh/' . $file;
	if ( ! is_readable( $src ) ) {
		echo "  khong thay anh mau: {$file}\n";
		return 0;
	}

	$uploads = wp_upload_dir();
	$dich    = trailingslashit( $uploads['path'] ) . wp_unique_filename( $uploads['path'], $file );
	if ( ! copy( $src, $dich ) ) {
		echo "  chep that bai: {$file}\n";
		return 0;
	}

	$att_id = (int) wp_insert_attachment(
		array(
			'post_mime_type' => 'image/jpeg',
			'post_title'     => pathinfo( $file, PATHINFO_FILENAME ),
			'post_status'    => 'inherit',
		),
		$dich
	);
	wp_update_attachment_metadata( $att_id, wp_generate_attachment_metadata( $att_id, $dich ) );
	update_post_meta( $att_id, '_nntm_demo_asset', $file );

	return $att_id;
}

/**
 * Tim bai/trang theo ID hoac slug trong cac post type ap dung.
 */
function nntm_tkd_tim_bai( string $khoa ): int {
	if ( ctype_digit( $khoa ) ) {
		return get_post( (int) $khoa ) ? (int) $khoa : 0;
	}

	$ids = get_posts(
		array(
			'post_type'        => Tu_Khoa_Dong::post_types_ap_dung(),
			'name'             => sanitize_title( $khoa ),
			'post_status'      => 'any',
			'posts_per_page'   => 1,
			'fields'           => 'ids',
			'suppress_filters' => true,
		)
	);

	return $ids ? (int) $ids[0] : 0;
}

$mau = array(
	array(
		'ten'      => 'Hoa sen',
		'bien_the' => array( 'sen', 'đoá sen' ),
		'mo_ta'    => 'Mọc từ bùn mà không nhiễm bùn — biểu tượng của tâm thanh tịnh giữa đời thường.',
		'kieu'     => 'the',
		// Co y KHONG co hinh: kho anh mau chua co anh hoa sen, va anh dai dien
		// cua bai "Hoa sen no giua bun nho" la doi suong — gan vao se sai nghia.
		// Mau nay cho thay the chi co chu hien the nao.
		'anh_id'   => 0,
		'anh_file' => '',
	),
	array(
		'ten'      => 'tỉnh thức',
		'bien_the' => array( 'tâm tỉnh thức' ),
		'mo_ta'    => 'Biết rõ mình đang làm gì, nghĩ gì, ngay trong giây phút hiện tại.',
		'kieu'     => 'the',
		'anh_id'   => 0,
		'anh_file' => '02-tuong-phat.jpg',
	),
	array(
		'ten'      => 'hơi thở',
		'bien_the' => array(),
		'mo_ta'    => 'Theo dõi hơi thở vào, hơi thở ra — cửa ngõ đơn giản nhất để tâm lắng xuống.',
		'kieu'     => 'anh',
		'anh_id'   => 0,
		'anh_file' => '01-rung-thong.jpg',
	),
	array(
		'ten'      => 'nhân quả',
		'bien_the' => array(),
		'mo_ta'    => 'Gieo nhân nào gặt quả nấy — sống có trách nhiệm với từng ý nghĩ, lời nói, việc làm.',
		'kieu'     => 'the',
		'anh_id'   => 0,
		'anh_file' => '04-kinh-sach.jpg',
	),
);

foreach ( $mau as $m ) {
	$co = get_posts(
		array(
			'post_type'        => Tu_Khoa_Dong::POST_TYPE,
			'title'            => $m['ten'],
			'post_status'      => 'any',
			'posts_per_page'   => 1,
			'fields'           => 'ids',
			'suppress_filters' => true,
		)
	);

	if ( $co ) {
		$id = (int) $co[0];
		echo "Da co tu khoa : {$m['ten']} (ID {$id}) - cap nhat lai\n";
	} else {
		$id = (int) wp_insert_post(
			array(
				'post_type'   => Tu_Khoa_Dong::POST_TYPE,
				'post_status' => 'publish',
				'post_title'  => $m['ten'],
			),
			true
		);
		echo "Tao tu khoa   : {$m['ten']} (ID {$id})\n";
	}

	update_post_meta( $id, Tu_Khoa_Dong::META_BIEN_THE, Tu_Khoa_Dong::lam_sach_bien_the( implode( "\n", $m['bien_the'] ), $m['ten'] ) );
	update_post_meta( $id, Tu_Khoa_Dong::META_MO_TA, $m['mo_ta'] );
	update_post_meta( $id, Tu_Khoa_Dong::META_KIEU, $m['kieu'] );
	update_post_meta( $id, '_nntm_tkd_mau', 1 );

	$anh_id = $m['anh_id'] ?: ( $m['anh_file'] ? nntm_tkd_anh_mau( $m['anh_file'] ) : 0 );
	if ( $anh_id > 0 ) {
		set_post_thumbnail( $id, $anh_id );
	} else {
		delete_post_thumbnail( $id );
	}
}

Tu_Khoa_Dong::xoa_dem();

$bat_id = nntm_tkd_tim_bai( $bat );
if ( $bat_id > 0 ) {
	update_post_meta( $bat_id, Tu_Khoa_Dong::META_BAT, true );
	echo "Bat hieu ung  : " . get_the_title( $bat_id ) . " (ID {$bat_id}) -> " . get_permalink( $bat_id ) . "\n";
} else {
	echo "Khong tim thay bai/trang '{$bat}' de bat hieu ung - bo qua\n";
}

echo str_repeat( '-', 60 ) . "\n";
echo 'So tu khoa dung duoc: ' . count( Tu_Khoa_Dong::du_lieu() ) . "\n";
