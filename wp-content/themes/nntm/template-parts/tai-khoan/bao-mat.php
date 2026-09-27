<?php
/**
 * "Bảo mật" — đổi mật khẩu (giữ nguyên phiên đăng nhập, xem
 * nntm_tk_xu_ly_mat_khau()) + đăng xuất.
 */

defined( 'ABSPATH' ) || exit;

$nntm_tk_tb = nntm_tk_thong_bao_hien_tai();
?>
<section class="nntm-tk__muc" id="bao-mat" aria-labelledby="nntm-tk-h-bao-mat">
	<h2 class="nntm-tk__muc-tieu-de" id="nntm-tk-h-bao-mat"><?php esc_html_e( 'Bảo mật', 'nntm' ); ?></h2>

	<?php if ( $nntm_tk_tb && 'bao-mat' === $nntm_tk_tb['muc'] ) : ?>
		<div
			class="nntm-tk__thong-bao nntm-tk__thong-bao--<?php echo esc_attr( $nntm_tk_tb['loai'] ); ?>"
			role="<?php echo esc_attr( 'loi' === $nntm_tk_tb['loai'] ? 'alert' : 'status' ); ?>"
		>
			<?php echo esc_html( $nntm_tk_tb['noi_dung'] ); ?>
		</div>
	<?php endif; ?>

	<form class="nntm-tk__form" method="post">
		<?php wp_nonce_field( 'nntm_tk_mat_khau', 'nntm_tk_nonce_mat_khau' ); ?>
		<input type="hidden" name="nntm_tk_action" value="mat-khau" />

		<div class="nntm-tk__truong">
			<label for="nntm-tk-mk-cu"><?php esc_html_e( 'Mật khẩu hiện tại', 'nntm' ); ?></label>
			<input type="password" id="nntm-tk-mk-cu" name="mat_khau_hien_tai" autocomplete="current-password" required />
		</div>

		<div class="nntm-tk__truong">
			<label for="nntm-tk-mk-moi"><?php esc_html_e( 'Mật khẩu mới', 'nntm' ); ?></label>
			<input type="password" id="nntm-tk-mk-moi" name="mat_khau_moi" autocomplete="new-password" minlength="8" required />
		</div>

		<div class="nntm-tk__truong">
			<label for="nntm-tk-mk-moi-2"><?php esc_html_e( 'Nhập lại mật khẩu mới', 'nntm' ); ?></label>
			<input type="password" id="nntm-tk-mk-moi-2" name="mat_khau_moi_2" autocomplete="new-password" minlength="8" required />
		</div>

		<button type="submit" class="nntm-tk__nut nntm-tk__nut--dac"><?php esc_html_e( 'Đổi mật khẩu', 'nntm' ); ?></button>
	</form>

	<p class="nntm-tk__dang-xuat">
		<a href="<?php echo esc_url( wp_logout_url( home_url( '/' ) ) ); ?>"><?php esc_html_e( 'Đăng xuất', 'nntm' ); ?></a>
	</p>
</section>
