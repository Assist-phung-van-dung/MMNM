<?php
/**
 * Điều hướng theo khu vực trong trang — cột dính bên trái ở màn rộng, hàng
 * chip cuộn ngang ở màn hẹp (CSS lo việc chuyển đổi, không có gì khác nhau
 * trong markup). Hoạt động đầy đủ không cần JS: đây chỉ là các liên kết neo
 * (#id) thật; assets/js/tai-khoan.js chỉ thêm trạng thái "đang xem" khi cuộn.
 */

defined( 'ABSPATH' ) || exit;

$nntm_tk_muc = array(
	'dang-doc' => __( 'Đang đọc', 'nntm' ),
	'thu-vien' => __( 'Thư viện của tôi', 'nntm' ),
	'yeu-thich' => __( 'Yêu thích', 'nntm' ),
	'cong-tu'  => __( 'Cộng tu', 'nntm' ),
	'khoa-tu'  => __( 'Khoá tu', 'nntm' ),
	'ho-so'    => __( 'Hồ sơ', 'nntm' ),
	'bao-mat'  => __( 'Bảo mật', 'nntm' ),
);
?>
<nav class="nntm-tk__dieu-huong" aria-label="<?php esc_attr_e( 'Điều hướng tài khoản', 'nntm' ); ?>" data-nntm-tk-dieu-huong>
	<ul class="nntm-tk__dieu-huong-ds">
		<?php foreach ( $nntm_tk_muc as $nntm_tk_id => $nntm_tk_nhan ) : ?>
			<li>
				<a href="#<?php echo esc_attr( $nntm_tk_id ); ?>" data-nntm-tk-muc="<?php echo esc_attr( $nntm_tk_id ); ?>">
					<?php echo esc_html( $nntm_tk_nhan ); ?>
				</a>
			</li>
		<?php endforeach; ?>
	</ul>
</nav>
