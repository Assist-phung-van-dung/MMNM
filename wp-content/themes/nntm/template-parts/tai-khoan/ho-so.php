<?php
/**
 * "Hồ sơ" — sửa Họ tên, Pháp danh, số điện thoại, vùng miền, địa chỉ, nhận
 * bản tin. Email chỉ hiển thị (đổi email không nằm trong màn này — xem
 * inc/tai-khoan.php: đây KHÔNG phải tài khoản đăng nhập nên không đổi ở đây).
 */

defined( 'ABSPATH' ) || exit;

$nntm_tk_ho_so = nntm_tk_lay_ho_so( get_current_user_id() );
$nntm_tk_tb    = nntm_tk_thong_bao_hien_tai();
?>
<section class="nntm-tk__muc" id="ho-so" aria-labelledby="nntm-tk-h-ho-so">
	<h2 class="nntm-tk__muc-tieu-de" id="nntm-tk-h-ho-so"><?php esc_html_e( 'Hồ sơ', 'nntm' ); ?></h2>

	<?php if ( $nntm_tk_tb && 'ho-so' === $nntm_tk_tb['muc'] ) : ?>
		<div
			class="nntm-tk__thong-bao nntm-tk__thong-bao--<?php echo esc_attr( $nntm_tk_tb['loai'] ); ?>"
			role="<?php echo esc_attr( 'loi' === $nntm_tk_tb['loai'] ? 'alert' : 'status' ); ?>"
		>
			<?php echo esc_html( $nntm_tk_tb['noi_dung'] ); ?>
		</div>
	<?php endif; ?>

	<form class="nntm-tk__form" method="post">
		<?php wp_nonce_field( 'nntm_tk_ho_so', 'nntm_tk_nonce_ho_so' ); ?>
		<input type="hidden" name="nntm_tk_action" value="ho-so" />

		<div class="nntm-tk__truong">
			<label for="nntm-tk-ho-ten"><?php esc_html_e( 'Họ và tên', 'nntm' ); ?></label>
			<input type="text" id="nntm-tk-ho-ten" name="ho_ten" value="<?php echo esc_attr( $nntm_tk_ho_so['ho_ten'] ); ?>" autocomplete="name" />
		</div>

		<div class="nntm-tk__truong">
			<label for="nntm-tk-phap-danh"><?php esc_html_e( 'Pháp danh', 'nntm' ); ?></label>
			<input type="text" id="nntm-tk-phap-danh" name="nntm_phap_danh" value="<?php echo esc_attr( $nntm_tk_ho_so['phap_danh'] ); ?>" minlength="2" required />
		</div>

		<div class="nntm-tk__truong">
			<label for="nntm-tk-email"><?php esc_html_e( 'Email', 'nntm' ); ?></label>
			<input type="email" id="nntm-tk-email" value="<?php echo esc_attr( $nntm_tk_ho_so['email'] ); ?>" disabled />
			<p class="nntm-tk__goi-y"><?php esc_html_e( 'Liên hệ ban quản trị để đổi email.', 'nntm' ); ?></p>
		</div>

		<div class="nntm-tk__truong">
			<label for="nntm-tk-dien-thoai"><?php esc_html_e( 'Số điện thoại', 'nntm' ); ?></label>
			<input type="text" id="nntm-tk-dien-thoai" name="nntm_dien_thoai" value="<?php echo esc_attr( $nntm_tk_ho_so['dien_thoai'] ); ?>" autocomplete="tel" />
		</div>

		<div class="nntm-tk__truong">
			<label for="nntm-tk-vung-mien"><?php esc_html_e( 'Vùng miền', 'nntm' ); ?></label>
			<select id="nntm-tk-vung-mien" name="nntm_vung_mien">
				<option value=""><?php esc_html_e( 'Chọn vùng miền', 'nntm' ); ?></option>
				<?php foreach ( nntm_vung_mien_options() as $nntm_tk_vm_key => $nntm_tk_vm_nhan ) : ?>
					<option value="<?php echo esc_attr( $nntm_tk_vm_key ); ?>" <?php selected( $nntm_tk_ho_so['vung_mien'], $nntm_tk_vm_key ); ?>>
						<?php echo esc_html( $nntm_tk_vm_nhan ); ?>
					</option>
				<?php endforeach; ?>
			</select>
		</div>

		<div class="nntm-tk__truong">
			<label for="nntm-tk-dia-chi"><?php esc_html_e( 'Địa chỉ', 'nntm' ); ?></label>
			<textarea id="nntm-tk-dia-chi" name="nntm_dia_chi" rows="2" autocomplete="street-address"><?php echo esc_textarea( $nntm_tk_ho_so['dia_chi'] ); ?></textarea>
		</div>

		<div class="nntm-tk__o-vuong">
			<label>
				<input type="checkbox" name="nntm_nhan_ban_tin" value="1" <?php checked( $nntm_tk_ho_so['nhan_ban_tin'] ); ?> />
				<span><?php esc_html_e( 'Nhận bản tin qua email', 'nntm' ); ?></span>
			</label>
		</div>

		<button type="submit" class="nntm-tk__nut nntm-tk__nut--dac"><?php esc_html_e( 'Lưu thay đổi', 'nntm' ); ?></button>
	</form>
</section>
