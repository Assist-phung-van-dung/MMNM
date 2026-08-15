<?php
/**
 * Popup "Tham Gia Chuỗi Trì" / "Cập Nhật Chuỗi Trì" — in ở chân trang (hook
 * wp_footer, đăng ký trong inc/cong-tu.php::nntm_congtu_render_modal()) cho
 * THÀNH VIÊN ĐÃ ĐĂNG NHẬP, ở MỌI trang, khi có chương trình trì tụng đang
 * mở. Mở bằng cách bấm phần tử có [data-nntm-chuoi-tri="tham-gia"] hoặc
 * [data-nntm-chuoi-tri="cap-nhat"] (vd nút trên banner "Lễ Đàn Khổng Tước")
 * — xử lý bởi assets/js/cong-tu-modal.js.
 *
 * Yêu cầu chủ dự án 14/08/2026: "nhấn vào tham gia anh không muốn qua page
 * khác nữa, anh muốn nó hiện popup lên đó luôn. Và khi tham gia rồi thì nút
 * tham gia chuyển thành nút cập nhật chuỗi trì, cũng mở popup."
 *
 * KHÔNG chép lại HTML hai form — get_template_part() với $args, đúng khuôn
 * template-parts/auth/modal-dang-nhap.php đã dùng cho modal đăng nhập:
 *   - "tham-gia": tái sử dụng form-tham-gia.php NGUYÊN VẸN (số cam kết +
 *     2 checkbox, tự đổi tiêu đề "Tham Gia"/"Cam Kết Thêm" theo trạng thái).
 *   - "cap-nhat": tái sử dụng form-khai-bao.php, CHỈ đổi tiêu đề hiển thị
 *     thành "Cập Nhật Chuỗi Trì" qua $args (ảnh thiết kế chủ dự án gửi ghi
 *     "chuỗi trị" — lỗi gõ, cả dự án dùng "chuỗi trì", xem docs/07-ban-giao.md).
 *
 * Cả hai đều dùng chung class .nntm-auth-modal (auth.css) — không chép lại
 * CSS lớp phủ/khung modal, chỉ khác id để JS mở đúng hộp thoại.
 *
 * @package NNTM
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="nntm-auth-modal" id="nntm-cong-tu-modal-tham-gia" hidden>
	<div class="nntm-auth-modal__overlay" data-nntm-congtu-modal-overlay></div>

	<div class="nntm-auth-modal__panel" role="dialog" aria-modal="true" aria-label="<?php esc_attr_e( 'Tham Gia Chuỗi Trì', 'nntm' ); ?>">
		<button type="button" class="nntm-auth-modal__close" data-nntm-congtu-modal-close>
			<span class="nntm-sr-only"><?php esc_html_e( 'Đóng', 'nntm' ); ?></span>
			<span aria-hidden="true">&times;</span>
		</button>

		<?php get_template_part( 'template-parts/cong-tu/form-tham-gia' ); ?>
	</div>
</div>

<div class="nntm-auth-modal" id="nntm-cong-tu-modal-cap-nhat" hidden>
	<div class="nntm-auth-modal__overlay" data-nntm-congtu-modal-overlay></div>

	<div class="nntm-auth-modal__panel" role="dialog" aria-modal="true" aria-label="<?php esc_attr_e( 'Cập Nhật Chuỗi Trì', 'nntm' ); ?>">
		<button type="button" class="nntm-auth-modal__close" data-nntm-congtu-modal-close>
			<span class="nntm-sr-only"><?php esc_html_e( 'Đóng', 'nntm' ); ?></span>
			<span aria-hidden="true">&times;</span>
		</button>

		<?php
		get_template_part(
			'template-parts/cong-tu/form-khai-bao',
			null,
			array(
				'tieu_de'  => __( 'Cập Nhật Chuỗi Trì', 'nntm' ),
				'them_lop' => 'nntm-auth-card--cap-nhat',
			)
		);
		?>
	</div>
</div>
