<?php
/**
 * Form "Khai Báo Chuỗi Trì" — SUY DOAN, KHÔNG có thiết kế Figma/ảnh cho màn
 * này (docs/07-ban-giao.md giao tự dựng theo đúng phong cách màn "Tham Gia
 * Chuỗi Trì"). Mọi số đo ở đây là ước lượng theo cùng ngôn ngữ thiết kế
 * (thẻ kính mờ auth.css), KHÔNG có số đo Figma thật để đối chiếu.
 *
 * Ba nút bấm nhanh 10/20/50 theo đúng ba mức chủ dự án vẽ trong sơ đồ
 * (docs/07-ban-giao.md) — JS thuần (assets/js/cong-tu.js), tắt JS vẫn gõ
 * tay được vào ô số bình thường.
 *
 * Tham số nhận qua $args (giống khuôn template-parts/auth/form-dang-nhap.php):
 *   tieu_de  (string) Ghi đè tiêu đề — dùng ở popup "Cập Nhật Chuỗi Trì"
 *            (yêu cầu chủ dự án 14/08/2026, xem template-parts/cong-tu/modal-chuoi-tri.php).
 *            Rỗng/không truyền thì giữ nguyên "Khai Báo Chuỗi Trì" như cũ.
 *   them_lop (string) Thêm class vào thẻ ngoài cùng — dùng gắn số đo riêng
 *            của popup (.nntm-auth-card--cap-nhat trong cong-tu.css).
 *
 * @package NNTM
 * @var array $args
 */

defined( 'ABSPATH' ) || exit;

$nntm_ct_args     = is_array( $args ?? null ) ? $args : array();
$nntm_ct_tieu_de  = isset( $nntm_ct_args['tieu_de'] ) && '' !== trim( (string) $nntm_ct_args['tieu_de'] )
	? (string) $nntm_ct_args['tieu_de']
	: __( 'Khai Báo Chuỗi Trì', 'nntm' );
$nntm_ct_them_lop = isset( $nntm_ct_args['them_lop'] ) ? sanitize_html_class( (string) $nntm_ct_args['them_lop'] ) : '';

$nntm_ct_program = function_exists( 'nntm_program_hien_tai' ) ? nntm_program_hien_tai() : null;
$nntm_ct_errors  = isset( $GLOBALS['nntm_congtu_errors'] ) && is_wp_error( $GLOBALS['nntm_congtu_errors'] ) ? $GLOBALS['nntm_congtu_errors'] : null;
$nntm_ct_ok      = isset( $_GET['nntm_congtu_ok'] ) && 'ghi-nhan' === $_GET['nntm_congtu_ok']; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- chi doc co dua hien thong bao, khong tao doi.

$nntm_ct_user_id = get_current_user_id();
?>
<div class="nntm-auth-card nntm-auth-card--cong-tu<?php echo $nntm_ct_them_lop ? ' ' . esc_attr( $nntm_ct_them_lop ) : ''; ?>">

	<h1 class="nntm-auth-card__title nntm-auth-card__title--cong-tu"><?php echo esc_html( $nntm_ct_tieu_de ); ?></h1>

	<?php if ( ! $nntm_ct_program ) : ?>

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
		$nntm_ct_phap_danh = function_exists( 'nntm_congtu_phap_danh' ) ? nntm_congtu_phap_danh( $nntm_ct_user_id ) : '';
		$nntm_ct_hom_nay    = function_exists( 'nntm_kpi_ghi_hom_nay' ) ? nntm_kpi_ghi_hom_nay( $nntm_ct_program->ID, $nntm_ct_user_id ) : 0;
		$nntm_ct_tong       = function_exists( 'nntm_kpi_tong_cua_nguoi' ) ? nntm_kpi_tong_cua_nguoi( $nntm_ct_program->ID, $nntm_ct_user_id ) : array(
			'cam_ket'    => 0,
			'thuc_hien'  => 0,
			'tien_trinh' => 0.0,
		);
		?>

		<div class="nntm-cong-tu__chao">
			<?php echo get_avatar( $nntm_ct_user_id, 40, '', '', array( 'class' => 'nntm-cong-tu__avatar' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- get_avatar() da tu esc. ?>
			<span class="nntm-cong-tu__chao-text">
				<em><?php esc_html_e( 'Xin chào', 'nntm' ); ?></em>
				<strong><?php echo esc_html( $nntm_ct_phap_danh ); ?></strong>
			</span>
		</div>

		<p class="nntm-cong-tu__hien-trang"><?php echo esc_html( nntm_congtu_cau_hom_nay( (int) $nntm_ct_hom_nay ) ); ?></p>

		<?php
		/*
		 * Ô thông báo — JS thay RUỘT của thẻ này sau mỗi lần gửi bằng AJAX
		 * (assets/js/cong-tu-modal.js), nên không bao giờ có hai thông báo
		 * cùng lúc. Tắt JS thì đây vẫn là chỗ in thông báo của POST thường.
		 */
		?>
		<div class="nntm-cong-tu__thong-bao" data-nntm-congtu-thong-bao>
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
		</div>

		<form class="nntm-auth-form" method="post" data-nntm-congtu-ajax="ghi-nhan">
			<?php wp_nonce_field( 'nntm_congtu_ghi_nhan', 'nntm_congtu_nonce' ); ?>
			<input type="hidden" name="nntm_congtu_action" value="ghi-nhan" />

			<div class="nntm-auth-field">
				<label for="nntm-congtu-so-vua-tri"><?php esc_html_e( 'Số chuỗi vừa trì', 'nntm' ); ?></label>
				<div class="nntm-auth-field__control">
					<input
						type="number"
						min="1"
						step="1"
						inputmode="numeric"
						id="nntm-congtu-so-vua-tri"
						name="so_chuoi"
						placeholder="<?php esc_attr_e( 'Vui lòng nhập số', 'nntm' ); ?>"
						required
					/>
				</div>
			</div>
			<button type="submit" class="nntm-auth-btn nntm-auth-btn--dac nntm-cong-tu__submit">
				<?php esc_html_e( 'Ghi Nhận', 'nntm' ); ?>
			</button>
		</form>

		<p class="nntm-cong-tu__tong-ket"><?php echo esc_html( nntm_congtu_cau_tong_ket( $nntm_ct_tong ) ); ?></p>

	<?php endif; ?>

</div>
