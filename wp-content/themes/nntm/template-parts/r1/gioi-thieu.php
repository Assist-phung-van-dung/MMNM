<?php
/**
 * SECTION 1 bản R1 — "Giới thiệu về Nẵng Nhân Tịch Mặc".
 *
 * Figma "DESKTOP - R1" / SECTION 1 4231:853 (1366x647), bóc 10/08/2026:
 *   SECTION   flex dọc, đệm 60/83, canh giữa
 *   IMG       1160x252 — ba chiếc lá bồ đề 200x200 / 238x238,
 *             bóng 0/20/40 rgba(116,119,102,0.20)  (= --nntm-sh-lift)
 *   TEXT      990x230  flex dọc, cách 30, canh giữa
 *     tiêu đề  #A47764  EB Garamond 600 52/60  canh giữa
 *     đoạn văn #747766  Baskerville  400 18/26  canh giữa
 *
 * Baskerville không có bản web đủ dấu tiếng Việt → dùng --nntm-font-serif
 * (Lora). Xem docs/05-font-thay-the.md.
 *
 * Nội dung chữ lấy nguyên văn từ Figma nhưng bọc qua filter để sau này
 * đổi được mà không phải sửa template.
 *
 * @package NNTM
 */

defined( 'ABSPATH' ) || exit;

/**
 * Tiêu đề khối giới thiệu.
 *
 * @param string $tieu_de Tiêu đề mặc định lấy từ Figma.
 */
$nntm_r1_gt_tieu_de = apply_filters(
	'nntm_r1_gioi_thieu_tieu_de',
	__( 'Giới thiệu về Nẵng Nhân Tịch Mặc', 'nntm' )
);

/**
 * Các đoạn văn của khối giới thiệu.
 *
 * @param string[] $doan Danh sách đoạn văn mặc định lấy từ Figma.
 */
$nntm_r1_gt_doan = apply_filters(
	'nntm_r1_gioi_thieu_doan',
	array(
		__( '“Nẵng Nhân Tịch Mặc” (能仁寂默) là một danh hiệu Hán dịch của Đức Phật Thích Ca Mâu Ni, chứa đựng ý nghĩa rất sâu sắc về lý tưởng của người giác ngộ.', 'nntm' ),
		__( 'Vì vậy, Nẵng Nhân Tịch Mặc có thể hiểu là:', 'nntm' ),
		__( '“Bậc có năng lực cứu độ muôn loài bằng lòng từ bi, đồng thời luôn an trú trong trí tuệ và sự tịch tĩnh.”', 'nntm' ),
	)
);

/**
 * Ba ảnh lá bồ đề. Mảng ID tệp đính kèm — rỗng thì bỏ qua cả hàng ảnh
 * chứ không vẽ khung trống.
 *
 * @param int[] $ids ID tệp đính kèm.
 */
$nntm_r1_gt_anh = array_values( array_filter( array_map( 'absint', (array) apply_filters( 'nntm_r1_gioi_thieu_anh', array() ) ) ) );
?>
<section class="nntm-r1-gioi-thieu">
	<div class="nntm-r1-gioi-thieu__inner">

		<?php if ( ! empty( $nntm_r1_gt_anh ) ) : ?>
			<div class="nntm-r1-gioi-thieu__anh" aria-hidden="true">
				<?php foreach ( $nntm_r1_gt_anh as $nntm_r1_gt_i => $nntm_r1_gt_id ) : ?>
					<?php
					// Chiếc lá ở giữa to hơn (238 so với 200) đúng như Figma.
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
