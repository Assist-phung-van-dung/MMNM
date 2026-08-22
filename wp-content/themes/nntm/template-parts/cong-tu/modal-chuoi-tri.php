<?php

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
