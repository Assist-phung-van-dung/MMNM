<?php

defined( 'ABSPATH' ) || exit;

$nntm_r1_gt_tieu_de = apply_filters(
	'nntm_r1_gioi_thieu_tieu_de',
	__( 'Giới thiệu về Nẵng Nhân Tịch Mặc', 'nntm' )
);

$nntm_r1_gt_doan = apply_filters(
	'nntm_r1_gioi_thieu_doan',
	array(
		__( '“Nẵng Nhân Tịch Mặc” (能仁寂默) là một danh hiệu Hán dịch của Đức Phật Thích Ca Mâu Ni, chứa đựng ý nghĩa rất sâu sắc về lý tưởng của người giác ngộ.', 'nntm' ),
		__( 'Vì vậy, Nẵng Nhân Tịch Mặc có thể hiểu là:', 'nntm' ),
		__( '“Bậc có năng lực cứu độ muôn loài bằng lòng từ bi, đồng thời luôn an trú trong trí tuệ và sự tịch tĩnh.”', 'nntm' ),
	)
);

$nntm_r1_gt_anh = array_values( array_filter( array_map( 'absint', (array) apply_filters( 'nntm_r1_gioi_thieu_anh', array() ) ) ) );
?>
<section class="nntm-r1-gioi-thieu">
	<div class="nntm-r1-gioi-thieu__inner">

		<?php if ( ! empty( $nntm_r1_gt_anh ) ) : ?>
			<div class="nntm-r1-gioi-thieu__anh" aria-hidden="true">
				<?php foreach ( $nntm_r1_gt_anh as $nntm_r1_gt_i => $nntm_r1_gt_id ) : ?>
					<?php
					 
					echo wp_get_attachment_image(
						$nntm_r1_gt_id,
						'medium',
						false,
						array(
							'class'    => 'nntm-r1-gioi-thieu__la' . ( 1 === $nntm_r1_gt_i ? ' is-lon' : '' ),
							'alt'      => '',
							'role'     => 'presentation',
							'loading'  => 'lazy',
							'decoding' => 'async',
						)
					);
					?>
				<?php endforeach; ?>
			</div>
		<?php endif; ?>

		<div class="nntm-r1-gioi-thieu__text">
			<h2 class="nntm-r1-gioi-thieu__tieu-de"><?php echo esc_html( $nntm_r1_gt_tieu_de ); ?></h2>

			<div class="nntm-r1-gioi-thieu__than">
				<?php foreach ( $nntm_r1_gt_doan as $nntm_r1_gt_p ) : ?>
					<p><?php echo esc_html( $nntm_r1_gt_p ); ?></p>
				<?php endforeach; ?>
			</div>
		</div>

	</div>
</section>
