<?php
/**
 * Dải đầu trang tài khoản: monogram + Pháp danh + cấp + 4 ô số nổi mép dải.
 * Tinh thần giống template-parts/cong-tu/ca-nhan.php (bảng màu tai-* tối ấm).
 */

defined( 'ABSPATH' ) || exit;

$nntm_tk_uid  = get_current_user_id();
$nntm_tk_hero = nntm_tk_lay_hero( $nntm_tk_uid );
$nntm_tk_o    = nntm_tk_lay_thong_ke( $nntm_tk_uid );
?>
<section class="nntm-tk__dai">
	<div class="nntm-tk__khung nntm-tk__dai-trong">
		<p class="nntm-tk__monogram" aria-hidden="true"><?php echo esc_html( $nntm_tk_hero['chu_dau'] ); ?></p>

		<div class="nntm-tk__dai-chu">
			<h1 class="nntm-tk__ten"><?php echo esc_html( $nntm_tk_hero['phap_danh'] ); ?></h1>

			<?php if ( '' !== $nntm_tk_hero['ho_ten'] ) : ?>
				<p class="nntm-tk__ho-ten"><?php echo esc_html( $nntm_tk_hero['ho_ten'] ); ?></p>
			<?php endif; ?>

			<p class="nntm-tk__meta">
				<span class="nntm-tk__cap"><?php echo esc_html( $nntm_tk_hero['ten_cap'] ); ?></span>
				<?php if ( '' !== $nntm_tk_hero['ngay_gia_nhap'] ) : ?>
					<span class="nntm-tk__ngay-gia-nhap">
						<?php
						/* translators: %s: tháng/năm gia nhập, ví dụ 09/2026 */
						echo esc_html( sprintf( __( 'Đồng tu từ tháng %s', 'nntm' ), $nntm_tk_hero['ngay_gia_nhap'] ) );
						?>
					</span>
				<?php endif; ?>
			</p>
		</div>
	</div>

	<?php if ( ! empty( $nntm_tk_o ) ) : ?>
		<div class="nntm-tk__khung">
			<ul class="nntm-tk__o-luoi">
				<?php foreach ( $nntm_tk_o as $nntm_tk_muc ) : ?>
					<li class="nntm-tk__o">
						<a href="<?php echo esc_url( $nntm_tk_muc['href'] ); ?>">
							<span class="nntm-tk__o-so"><?php echo esc_html( number_format_i18n( (int) $nntm_tk_muc['so'] ) ); ?></span>
							<span class="nntm-tk__o-nhan"><?php echo esc_html( $nntm_tk_muc['nhan'] ); ?></span>
						</a>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>
	<?php endif; ?>
</section>
