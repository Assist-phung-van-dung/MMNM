<?php
/**
 * Form "Tham Gia Chuỗi Trì" / "Cam Kết Thêm" — MỘT template duy nhất, đổi
 * chữ theo nntm_kpi_da_tham_gia() (đúng yêu cầu, KHÔNG đẻ file thứ hai).
 *
 * Dựng theo ảnh thiết kế thật design/figma/6613-10636@1x.png (node Figma
 * 6613:10636, khung 1366×770 — xem docs/07-ban-giao.md). Số đo bóc bằng
 * cách so màu từng điểm ảnh (không có GD/Imagick ở máy này nên đo bằng
 * Pillow qua dòng lệnh `python`), xem chi tiết số đo trong cong-tu.css.
 *
 * @package NNTM
 */

defined( 'ABSPATH' ) || exit;

$nntm_ct_program = function_exists( 'nntm_program_hien_tai' ) ? nntm_program_hien_tai() : null;
$nntm_ct_errors  = isset( $GLOBALS['nntm_congtu_errors'] ) && is_wp_error( $GLOBALS['nntm_congtu_errors'] ) ? $GLOBALS['nntm_congtu_errors'] : null;
$nntm_ct_ok      = isset( $_GET['nntm_congtu_ok'] ) && 'cam-ket' === $_GET['nntm_congtu_ok']; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- chi doc co dua hien thong bao, khong tao doi.

$nntm_ct_user_id = get_current_user_id();
?>
<div class="nntm-auth-card nntm-auth-card--cong-tu">

	<?php if ( ! $nntm_ct_program ) : ?>

		<h1 class="nntm-auth-card__title nntm-auth-card__title--cong-tu"><?php esc_html_e( 'Tham Gia Chuỗi Trì', 'nntm' ); ?></h1>

		<div class="nntm-auth-alert nntm-auth-alert--loi" role="alert">
			<p>
				<?php
				echo esc_html(
					$nntm_ct_errors
						? wp_strip_all_tags( $nntm_ct_errors->get_error_message() )
						: __( 'Hiện không có chương trình trì tụng nào đang mở. Mời quay lại sau.', 'nntm' )
				);
				?>
			</p>
		</div>

	<?php else : ?>

		<?php
		$nntm_ct_da_tham_gia = function_exists( 'nntm_kpi_da_tham_gia' ) && nntm_kpi_da_tham_gia( $nntm_ct_program->ID, $nntm_ct_user_id );
		$nntm_ct_tong         = function_exists( 'nntm_kpi_tong_cua_nguoi' ) ? nntm_kpi_tong_cua_nguoi( $nntm_ct_program->ID, $nntm_ct_user_id ) : array(
			'cam_ket'   => 0,
			'thuc_hien' => 0,
		);

		$nntm_ct_tieu_de = $nntm_ct_da_tham_gia ? __( 'Cam Kết Thêm', 'nntm' ) : __( 'Tham Gia Chuỗi Trì', 'nntm' );
		$nntm_ct_nhan    = $nntm_ct_da_tham_gia ? __( 'Số chuỗi cam kết thêm', 'nntm' ) : __( 'Số chuỗi cam kết sẽ trì', 'nntm' );
		$nntm_ct_nut     = $nntm_ct_da_tham_gia ? __( 'Xác Nhận Cam Kết', 'nntm' ) : __( 'Xác Nhận Đăng Ký', 'nntm' );

		$nntm_ct_phap_danh = function_exists( 'nntm_congtu_phap_danh' ) ? nntm_congtu_phap_danh( $nntm_ct_user_id ) : '';
		?>

		<h1 class="nntm-auth-card__title nntm-auth-card__title--cong-tu"><?php echo esc_html( $nntm_ct_tieu_de ); ?></h1>

		<div class="nntm-cong-tu__chao">
			<?php echo get_avatar( $nntm_ct_user_id, 40, '', '', array( 'class' => 'nntm-cong-tu__avatar' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- get_avatar() da tu esc. ?>
			<span class="nntm-cong-tu__chao-text">
				<em><?php esc_html_e( 'Xin chào', 'nntm' ); ?></em>
				<strong><?php echo esc_html( $nntm_ct_phap_danh ); ?></strong>
			</span>
		</div>

		<?php if ( $nntm_ct_da_tham_gia ) : ?>
			<p class="nntm-cong-tu__hien-trang">
				<?php
				printf(
					/* translators: 1: số chuỗi đã cam kết, 2: số chuỗi đã thực hiện */
					esc_html__( 'Bạn đã cam kết %1$s chuỗi, đã thực hiện %2$s chuỗi.', 'nntm' ),
					esc_html( nntm_congtu_dinh_dang_so( (int) $nntm_ct_tong['cam_ket'] ) ),
					esc_html( nntm_congtu_dinh_dang_so( (int) $nntm_ct_tong['thuc_hien'] ) )
				);
				?>
			</p>
		<?php endif; ?>

		<?php if ( $nntm_ct_errors ) : ?>
			<div class="nntm-auth-alert nntm-auth-alert--loi" role="alert">
				<?php foreach ( $nntm_ct_errors->get_error_messages() as $nntm_ct_message ) : ?>
					<p><?php echo esc_html( wp_strip_all_tags( $nntm_ct_message ) ); ?></p>
				<?php endforeach; ?>
			</div>
		<?php elseif ( $nntm_ct_ok ) : ?>
			<div class="nntm-auth-alert nntm-auth-alert--ok" role="status">
				<p><?php esc_html_e( 'Đã ghi nhận, cảm ơn bạn đã phát tâm.', 'nntm' ); ?></p>
			</div>
		<?php endif; ?>

		<form class="nntm-auth-form" method="post">
			<?php wp_nonce_field( 'nntm_congtu_cam_ket', 'nntm_congtu_nonce' ); ?>
			<input type="hidden" name="nntm_congtu_action" value="cam-ket" />

			<div class="nntm-auth-field">
				<label for="nntm-congtu-so-chuoi"><?php echo esc_html( $nntm_ct_nhan ); ?></label>
				<div class="nntm-auth-field__control">
					<input
						type="number"
						min="1"
						step="1"
						inputmode="numeric"
						id="nntm-congtu-so-chuoi"
						name="so_chuoi"
						placeholder="<?php esc_attr_e( 'Vui lòng nhập số', 'nntm' ); ?>"
						required
					/>
				</div>
			</div>

			<?php if ( ! $nntm_ct_da_tham_gia ) : ?>
				<div class="nntm-auth-checkbox">
					<label>
						<input type="checkbox" name="nntm_congtu_dong_y" value="1" required />
						<span>
							<?php esc_html_e( 'Tôi đã đọc và đồng ý với', 'nntm' ); ?>
							<a href="<?php echo esc_url( home_url( '/chinh-sach/' ) ); ?>"><strong><?php esc_html_e( 'Điều khoản sử dụng', 'nntm' ); ?></strong></a>
						</span>
					</label>
				</div>
			<?php endif; ?>

			<div class="nntm-auth-checkbox">
				<label>
					<input type="checkbox" name="nntm_congtu_ban_tin" value="1" />
					<span><?php esc_html_e( 'Nhận thông tin của trang', 'nntm' ); ?></span>
				</label>
			</div>

			<button type="submit" class="nntm-auth-btn nntm-auth-btn--dac nntm-cong-tu__submit">
				<?php echo esc_html( $nntm_ct_nut ); ?>
			</button>
		</form>

	<?php endif; ?>

</div>
