<?php
/**
 * Danh sách 20 kiểu hiệu ứng con trỏ chuột — NGUỒN DUY NHẤT.
 *
 * Vẽ hiệu ứng là việc của theme (wp-content/themes/nntm/assets/js/con-tro.js +
 * inc/con-tro.php). Nhưng cấu hình con trỏ giờ gắn được vào TỪ KHOÁ ĐỘNG — một
 * post type của plugin này (class-tu-khoa-dong.php) — nên danh sách 20 kiểu
 * phải nằm ở MỘT nơi để cả hai phía dùng chung, tránh chép hai bản rồi lệch
 * nhau khi thêm/sửa một kiểu.
 *
 * Hàm thuần, không có tác dụng phụ, không phụ thuộc lớp nào — an toàn để cả
 * theme lẫn phần còn lại của plugin gọi thẳng.
 *
 * @package NNTM_Core
 */

defined( 'ABSPATH' ) || exit;

/**
 * 20 kiểu hiệu ứng con trỏ: mã => tên hiển thị + mô tả 1 dòng.
 *
 * Hành vi vẽ thật nằm ở theme (sổ HIEU_UNG trong assets/js/con-tro.js) — mảng
 * này chỉ phục vụ hiển thị nhãn và kiểm hợp lệ (mã lạ -> coi như không có).
 */
function nntm_con_tro_ds_kieu(): array {
	return array(
		'mat-troi'        => array(
			'ten'   => __( 'Mặt trời', 'nntm' ),
			'mo_ta' => __( 'Lõi tròn vàng + 12 tia xoay chậm, vệt bụi ánh vàng tan dần.', 'nntm' ),
		),
		'hoa-sen'         => array(
			'ten'   => __( 'Hoa sen', 'nntm' ),
			'mo_ta' => __( 'Đoá sen 6 cánh xoay nhẹ, cánh sen nhỏ rơi lả tả rồi mờ.', 'nntm' ),
		),
		'dom-sang'        => array(
			'ten'   => __( 'Đóm sáng', 'nntm' ),
			'mo_ta' => __( 'Ngôi sao 4 cánh, đi qua đâu lấp lánh sao nhỏ nhấp nháy.', 'nntm' ),
		),
		'hao-quang'       => array(
			'ten'   => __( 'Hào quang', 'nntm' ),
			'mo_ta' => __( 'Chấm sáng đầu con trỏ + vòng hào quang đi theo chậm một nhịp.', 'nntm' ),
		),
		'sao-choi'        => array(
			'ten'   => __( 'Sao chổi', 'nntm' ),
			'mo_ta' => __( 'Đầu sáng kéo đuôi thon mượt theo quỹ đạo, mờ dần.', 'nntm' ),
		),
		'gon-nuoc'        => array(
			'ten'   => __( 'Gợn nước', 'nntm' ),
			'mo_ta' => __( 'Chấm mềm, rê chuột toả gợn vòng tròn như mặt nước.', 'nntm' ),
		),
		'dom-dom'         => array(
			'ten'   => __( 'Đom đóm', 'nntm' ),
			'mo_ta' => __( 'Vài đốm sáng bay lượn quanh con trỏ, nhấp nháy.', 'nntm' ),
		),
		'trang-khuyet'    => array(
			'ten'   => __( 'Trăng khuyết', 'nntm' ),
			'mo_ta' => __( 'Vầng trăng khuyết, vệt ánh bạc mờ.', 'nntm' ),
		),
		'banh-xe-phap'    => array(
			'ten'   => __( 'Bánh xe Pháp', 'nntm' ),
			'mo_ta' => __( 'Pháp luân 8 căm xoay chậm, vệt vòng tròn nhỏ vàng.', 'nntm' ),
		),
		'la-bo-de'        => array(
			'ten'   => __( 'Lá bồ đề', 'nntm' ),
			'mo_ta' => __( 'Lá bồ đề thuôn nhọn có gân giữa, lá nhỏ bay theo gió rồi rơi.', 'nntm' ),
		),
		'ngon-nen'        => array(
			'ten'   => __( 'Ngọn nến', 'nntm' ),
			'mo_ta' => __( 'Ngọn nến lung linh, tàn lửa nhỏ bay lên rồi tắt.', 'nntm' ),
		),
		'khoi-huong'      => array(
			'ten'   => __( 'Khói hương', 'nntm' ),
			'mo_ta' => __( 'Chấm đầu nhang đỏ, làn khói mảnh uốn lượn bay lên.', 'nntm' ),
		),
		'chuoi-hat'       => array(
			'ten'   => __( 'Chuỗi hạt', 'nntm' ),
			'mo_ta' => __( 'Chuỗi hạt tròn nối nhau đi theo con trỏ như dây mềm.', 'nntm' ),
		),
		'bui-vang'        => array(
			'ten'   => __( 'Bụi vàng', 'nntm' ),
			'mo_ta' => __( 'Con trỏ chấm nhỏ, rắc bụi vàng li ti rơi nhẹ.', 'nntm' ),
		),
		'vien-tron-thien' => array(
			'ten'   => __( 'Viền tròn thiền (Ensō)', 'nntm' ),
			'mo_ta' => __( 'Nét Ensō vẽ bằng bút lông, hở một khe, xoay chậm.', 'nntm' ),
		),
		'net-muc'         => array(
			'ten'   => __( 'Nét mực', 'nntm' ),
			'mo_ta' => __( 'Vệt bút lông thư pháp: dày khi đi chậm, mảnh khi đi nhanh.', 'nntm' ),
		),
		'bong-bong'       => array(
			'ten'   => __( 'Bong bóng', 'nntm' ),
			'mo_ta' => __( 'Bong bóng xà phòng nhỏ nổi lên, có ánh viền.', 'nntm' ),
		),
		'hoa-mai'         => array(
			'ten'   => __( 'Hoa mai', 'nntm' ),
			'mo_ta' => __( 'Hoa mai 5 cánh vàng, cánh mai rơi xoay — không khí Tết.', 'nntm' ),
		),
		'sao-bang'        => array(
			'ten'   => __( 'Sao băng', 'nntm' ),
			'mo_ta' => __( 'Rê nhanh bắn tia lửa ngược hướng đi; đi chậm chỉ còn đốm nhỏ.', 'nntm' ),
		),
		'cham-vong'       => array(
			'ten'   => __( 'Chấm vòng', 'nntm' ),
			'mo_ta' => __( 'Tối giản: chấm nhỏ + vòng tròn theo trễ, nở to trên link/nút.', 'nntm' ),
		),
	);
}

/** Mã kiểu có hợp lệ không (một trong 20 kiểu ở trên). */
function nntm_con_tro_kieu_hop_le( string $kieu ): bool {
	return array_key_exists( $kieu, nntm_con_tro_ds_kieu() );
}

/** Lọc một mã kiểu thô: hợp lệ thì giữ, mã lạ/rỗng -> chuỗi rỗng ("không gắn"). */
function nntm_con_tro_sanitize_kieu_dung_chung( $tho ): string {
	$khoa = sanitize_key( (string) $tho );

	return nntm_con_tro_kieu_hop_le( $khoa ) ? $khoa : '';
}

/** Lọc một màu hex thô: hợp lệ thì giữ, sai định dạng -> chuỗi rỗng. */
function nntm_con_tro_sanitize_mau_dung_chung( $tho ): string {
	$tho = trim( (string) $tho );

	if ( '' === $tho ) {
		return '';
	}

	$sach = sanitize_hex_color( $tho );

	return $sach ? $sach : '';
}

/** Bảng màu mặc định — khớp MAU_MAC_DINH trong assets/js/con-tro.js của theme. */
function nntm_con_tro_mau_mac_dinh( string $kieu ): string {
	static $bang = null;

	if ( null === $bang ) {
		$bang = array(
			'mat-troi'        => '#D4AF37',
			'hoa-sen'         => '#E9B9A5',
			'dom-sang'        => '#EAD79B',
			'hao-quang'       => '#D4AF37',
			'sao-choi'        => '#D4AF37',
			'gon-nuoc'        => '#747766',
			'dom-dom'         => '#EAD79B',
			'trang-khuyet'    => '#F0EEE9',
			'banh-xe-phap'    => '#D4AF37',
			'la-bo-de'        => '#747766',
			'ngon-nen'        => '#FEBE98',
			'khoi-huong'      => '#747766',
			'chuoi-hat'       => '#A47764',
			'bui-vang'        => '#D4AF37',
			'vien-tron-thien' => '#3F3B3B',
			'net-muc'         => '#3F3B3B',
			'bong-bong'       => '#FCFDFE',
			'hoa-mai'         => '#DEC378',
			'sao-bang'        => '#FFF8D9',
			'cham-vong'       => '#747766',
		);
	}

	return $bang[ $kieu ] ?? '#D4AF37';
}
