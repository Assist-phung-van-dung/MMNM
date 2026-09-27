<?php
/**
 * Dữ liệu GIẢ cho harness xem thử trang tài khoản (--demo) — KHÔNG chạm DB.
 *
 * Mỗi hàm lấy dữ liệu trong inc/tai-khoan.php đều kết thúc bằng một
 * apply_filters( 'nntm_tk_du_lieu_<khu_vuc>', $ket_qua, $uid ), đúng để chỗ
 * này móc vào và thay hẳn bằng dữ liệu mẫu — nhờ vậy mọi trạng thái "đã có
 * dữ liệu" của từng khối đều xem được dù máy local không có đơn hàng/tiến độ
 * đọc/đăng ký khoá tu thật nào.
 */

defined( 'ABSPATH' ) || exit;

add_filter(
	'nntm_tk_du_lieu_hero',
	static function () {
		return array(
			'phap_danh'     => 'Tâm An',
			'ho_ten'        => 'Nguyễn Thị Tâm An',
			'chu_dau'       => 'T',
			'ten_cap'       => 'Kim Cương Hành Giả',
			'ngay_gia_nhap' => '03/2024',
		);
	}
);

add_filter(
	'nntm_tk_du_lieu_thong_ke',
	static function () {
		return array(
			'dang_doc'  => array( 'nhan' => 'Sách đang đọc', 'so' => 3, 'href' => '#dang-doc' ),
			'thu_vien'  => array( 'nhan' => 'Sách đã thỉnh', 'so' => 7, 'href' => '#thu-vien' ),
			'yeu_thich' => array( 'nhan' => 'Yêu thích', 'so' => 12, 'href' => '#yeu-thich' ),
			'khoa_tu'   => array( 'nhan' => 'Khoá tu đã đăng ký', 'so' => 2, 'href' => '#khoa-tu' ),
		);
	}
);

add_filter(
	'nntm_tk_du_lieu_dang_doc',
	static function () {
		return array(
			array(
				'tieu_de'   => 'Kinh Pháp Cú — bản dịch chú giải',
				'url'       => '#',
				'anh'       => '',
				'trang'     => 42,
				'thoi_gian' => '3 giờ trước',
			),
			array(
				'tieu_de'   => 'Nghi Quỹ Trì Tụng Hằng Ngày',
				'url'       => '#',
				'anh'       => '',
				'trang'     => 8,
				'thoi_gian' => '1 ngày trước',
			),
			array(
				'tieu_de'   => 'Con Đường Chuyển Hoá',
				'url'       => '#',
				'anh'       => '',
				'trang'     => 120,
				'thoi_gian' => '5 ngày trước',
			),
		);
	}
);

add_filter(
	'nntm_tk_du_lieu_thu_vien',
	static function () {
		return array(
			'sach'     => array(
				array( 'tieu_de' => 'Kinh Pháp Cú — bản dịch chú giải', 'url' => '#', 'anh' => '' ),
				array( 'tieu_de' => 'Con Đường Chuyển Hoá', 'url' => '#', 'anh' => '' ),
			),
			'don_hang' => array(
				array(
					'ma'               => '17123456789',
					'ten_an_pham'      => 'Kinh Pháp Cú — bản dịch chú giải',
					'so_tien_hien_thi' => '120.000 ₫',
					'trang_thai_ma'    => 'paid',
					'trang_thai_nhan'  => 'Đã thanh toán',
					'ngay'             => '12/08/2026',
					'tiep_tuc_url'     => '',
				),
				array(
					'ma'               => '17123456790',
					'ten_an_pham'      => 'Thiền Tông Trực Chỉ',
					'so_tien_hien_thi' => '89.000 ₫',
					'trang_thai_ma'    => 'pending',
					'trang_thai_nhan'  => 'Đang chờ',
					'ngay'             => '20/09/2026',
					'tiep_tuc_url'     => '#',
				),
				array(
					'ma'               => '17123456791',
					'ten_an_pham'      => 'Bát Nhã Tâm Kinh Giảng Giải',
					'so_tien_hien_thi' => '65.000 ₫',
					'trang_thai_ma'    => 'cancelled',
					'trang_thai_nhan'  => 'Đã huỷ',
					'ngay'             => '02/07/2026',
					'tiep_tuc_url'     => '',
				),
				array(
					'ma'               => '17123456792',
					'ten_an_pham'      => 'Con Đường Chuyển Hoá',
					'so_tien_hien_thi' => '99.000 ₫',
					'trang_thai_ma'    => 'failed',
					'trang_thai_nhan'  => 'Thất bại',
					'ngay'             => '30/05/2026',
					'tiep_tuc_url'     => '',
				),
			),
		);
	}
);

add_filter(
	'nntm_tk_du_lieu_yeu_thich',
	static function () {
		return array(
			'muc'  => array(
				array( 'tieu_de' => 'Sống tỉnh thức giữa đời thường', 'url' => '#', 'anh' => '', 'loai_nhan' => 'Bài viết' ),
				array( 'tieu_de' => 'An Cư Kiết Đông 2026', 'url' => '#', 'anh' => '', 'loai_nhan' => 'Khoá tu' ),
				array( 'tieu_de' => 'Bát Nhã Tâm Kinh Giảng Giải', 'url' => '#', 'anh' => '', 'loai_nhan' => 'Ấn phẩm' ),
			),
			'tong' => 12,
		);
	}
);

add_filter(
	'nntm_tk_du_lieu_cong_tu',
	static function () {
		return array(
			'url'              => '#',
			'ten_chuong_trinh' => 'An Cư Kiết Đông 2026',
			'cam_ket'          => 90,
			'thuc_hien'        => 54,
			'co_du_lieu'       => true,
		);
	}
);

add_filter(
	'nntm_tk_du_lieu_khoa_tu',
	static function () {
		return array(
			array(
				'tieu_de'         => 'An Cư Kiết Đông 2026',
				'url'             => '#',
				'trang_thai_ma'   => 'approved',
				'trang_thai_nhan' => 'Đã duyệt',
				'ngay'            => '15/09/2026',
			),
			array(
				'tieu_de'         => 'Khoá Tu Mùa Xuân 2026',
				'url'             => '#',
				'trang_thai_ma'   => 'pending',
				'trang_thai_nhan' => 'Chờ duyệt',
				'ngay'            => '02/01/2026',
			),
		);
	}
);

add_filter(
	'nntm_tk_du_lieu_ho_so',
	static function () {
		return array(
			'ho_ten'       => 'Nguyễn Thị Tâm An',
			'phap_danh'    => 'Tâm An',
			'email'        => 'tam.an@vidu.vn',
			'dien_thoai'   => '0912345678',
			'vung_mien'    => 'mien-bac',
			'dia_chi'      => 'Số 12, ngõ 34, phường Yên Hoà, Hà Nội',
			'nhan_ban_tin' => true,
		);
	}
);
