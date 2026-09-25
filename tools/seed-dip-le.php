<?php
/**
 * Seed 8 dip dac biet MAU (Phase 2, phieu khao sat cau 31: 6-8 lan/nam).
 *
 *   "C:/xampp8_2/php/php.exe" tools/seed-dip-le.php
 *
 * Chay nhieu lan duoc, KHONG tao trung (so theo tieu de).
 *
 * TAT CA TAO O TRANG THAI NHAP ("draft"): chi dip "Da dang" moi duoc gui. Noi
 * dung la loi mau — BQT phai thay bang loi chuc / phap ngu that roi moi bam
 * Dang. Danh sach dip that khach CHUA gui; day la cac ngay le pho bien, co the
 * khac voi truyen thong rieng cua dao trang.
 *
 * @package NNTM
 */

if ( PHP_SAPI !== 'cli' ) {
	exit( 'Chi chay tu dong lenh.' );
}

$_SERVER['HTTP_HOST']   = 'nntm.com';
$_SERVER['REQUEST_URI'] = '/';
require_once __DIR__ . '/../wp-load.php';

if ( ! class_exists( '\NNTM\Core\Ban_Tin' ) ) {
	exit( "Plugin nntm-core chua bat hoac chua co class Ban_Tin.\n" );
}

use NNTM\Core\Ban_Tin;

$mau = array(
	array( 'Tết Nguyên Đán', 1, 1, 'Cung chúc Tân Xuân' ),
	array( 'Rằm tháng Giêng — Tết Thượng Nguyên', 15, 1, '' ),
	array( 'Vía Đức Quán Thế Âm Bồ Tát Đản Sanh', 19, 2, '' ),
	array( 'Đại lễ Phật Đản', 15, 4, 'Mừng Đại lễ Phật Đản' ),
	array( 'Vía Đức Quán Thế Âm Bồ Tát Thành Đạo', 19, 6, '' ),
	array( 'Lễ Vu Lan Báo Hiếu', 15, 7, 'Mùa Vu Lan Báo Hiếu' ),
	array( 'Vía Đức Phật A Di Đà', 17, 11, '' ),
	array( 'Lễ Phật Thích Ca Thành Đạo', 8, 12, '' ),
);

echo "Seed dip dac biet (nhap)\n" . str_repeat( '-', 60 ) . "\n";

foreach ( $mau as list( $ten, $ngay, $thang, $tieu_de ) ) {
	$co = get_posts(
		array(
			'post_type'        => Ban_Tin::CPT_DIP,
			'title'            => $ten,
			'post_status'      => 'any',
			'posts_per_page'   => 1,
			'fields'           => 'ids',
			'suppress_filters' => true,
		)
	);

	if ( $co ) {
		echo "Da co : {$ten} (ID {$co[0]}) - bo qua, khong ghi de noi dung BQT da sua\n";
		continue;
	}

	$id = wp_insert_post(
		array(
			'post_type'    => Ban_Tin::CPT_DIP,
			'post_status'  => 'draft',
			'post_title'   => $ten,
			'post_content' => "Kính gửi {{ten}},\n\n[NỘI DUNG MẪU — Ban quản trị thay bằng lời chúc hoặc pháp ngữ, rồi bấm Đăng. Dịp còn ở trạng thái Nháp thì sẽ KHÔNG được gửi.]\n\nNam mô Bổn Sư Thích Ca Mâu Ni Phật.",
		),
		true
	);

	if ( is_wp_error( $id ) ) {
		echo "LOI  : {$ten} - " . $id->get_error_message() . "\n";
		continue;
	}

	update_post_meta( $id, Ban_Tin::META_DIP_LICH, 'am' );
	update_post_meta( $id, Ban_Tin::META_DIP_NGAY, $ngay );
	update_post_meta( $id, Ban_Tin::META_DIP_THANG, $thang );
	update_post_meta( $id, Ban_Tin::META_DIP_TIEU_DE, $tieu_de );
	update_post_meta( $id, '_nntm_dip_mau', 1 );

	echo sprintf( "Tao  : %-42s %2d/%2d AL -> lan toi %s (ID %d)\n", $ten, $ngay, $thang, Ban_Tin::lan_toi_cua_dip( $id ), $id );
}
