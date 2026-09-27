<?php
/**
 * "Cộng tu" — một thẻ tóm tắt, cố tình nhẹ, dẫn sang dashboard riêng
 * (template-parts/cong-tu/ca-nhan.php) chứ không lặp lại số liệu ở đó.
 */

defined( 'ABSPATH' ) || exit;

$nntm_tk_ct = nntm_tk_lay_cong_tu( get_current_user_id() );
?>
<section class="nntm-tk__muc" id="cong-tu" aria-labelledby="nntm-tk-h-cong-tu">
	<h2 class="nntm-tk__muc-tieu-de" id="nntm-tk-h-cong-tu"><?php esc_html_e( 'Cộng tu', 'nntm' ); ?></h2>

	<div class="nntm-tk__the-cong-tu">
		<?php if ( '' !== $nntm_tk_ct['ten_chuong_trinh'] ) : ?>
			<p class="nntm-tk__the-cong-tu-chuong-trinh"><?php echo esc_html( $nntm_tk_ct['ten_chuong_trinh'] ); ?></p>
		<?php endif; ?>

		<?php if ( $nntm_tk_ct['co_du_lieu'] ) : ?>
			<p class="nntm-tk__the-cong-tu-so">
				<strong><?php echo esc_html( number_format_i18n( $nntm_tk_ct['thuc_hien'] ) ); ?></strong>
				<span>/ <?php echo esc_html( number_format_i18n( $nntm_tk_ct['cam_ket'] ) ); ?></span>
			</p>
		<?php else : ?>
			<p class="nntm-tk__the-cong-tu-loi"><?php esc_html_e( 'Bạn chưa phát nguyện cùng chương trình cộng tu nào.', 'nntm' ); ?></p>
		<?php endif; ?>

		<?php if ( '' !== $nntm_tk_ct['url'] ) : ?>
			<a class="nntm-tk__nut" href="<?php echo esc_url( $nntm_tk_ct['url'] ); ?>"><?php esc_html_e( 'Mở sổ cộng tu', 'nntm' ); ?></a>
		<?php endif; ?>
	</div>
</section>
