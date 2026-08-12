<?php
/**
 * Khối "Tổng Chỉ" bản R1 — băng cuộn ngang gồm 5 thẻ ảnh lớn.
 *
 * Figma "DESKTOP - R1" / TONG CHI 4231:869 (1326x714), bóc 10/08/2026:
 *   TONG CHI  nền #F7F1DE, bo 40
 *   TITLE     "Tổng Chỉ"  #A47764  EB Garamond 600 52/60
 *   LIST      1364x450  flex ngang, cách 30, đệm trái 184
 *             -> thẻ cuối CỐ Ý ló ra mép phải để gợi ý còn nội dung
 *   THẺ       994x450  nền ảnh, bo 40, đệm 60/50, canh giữa
 *     số       90x70 nền #F7F1DE, chữ #747766 Battambang 700 30/38
 *     nội dung #FCFDFE  Battambang 700 30/38  canh giữa
 *   NÚT       58x58  nền #FCFDFE, mũi tên viền #1F4E79 2px,
 *             bóng 0/5/30 rgba(85,110,158,0.20)
 *
 * Khác với banner: hai nút trái/phải ở đây HIỆN (không bị ẩn như trong
 * khung BANNER), nên có dựng.
 *
 * @package NNTM
 */

defined( 'ABSPATH' ) || exit;

/**
 * Năm điều Tổng Chỉ. Mỗi phần tử: `noi_dung` (chữ trên thẻ) và `anh`
 * (ID tệp đính kèm làm nền, 0 = để nền rêu trơn).
 *
 * @param array[] $dieu Danh sách điều, lấy nguyên văn từ Figma.
 */
$nntm_r1_tc_dieu = apply_filters(
	'nntm_r1_tong_chi_dieu',
	array(
		array(
			'noi_dung' => __( "Phát Bồ Đề Tâm.\nTrên Sáu Xúc Xứ hoặc Ngũ Thủ Uẩn tu Tứ Chánh Cần, lấy Bát Chánh Đạo làm nền tảng.", 'nntm' ),
			'anh'      => 0,
		),
		array(
			'noi_dung' => __( 'Thể nhập Pháp giới Tạng Thân A Di Đà Phật làm cứu cánh', 'nntm' ),
			'anh'      => 0,
		),
		array(
			'noi_dung' => __( "Trực chỉ nhân tâm\nKiến tánh thành Phật", 'nntm' ),
			'anh'      => 0,
		),
		array(
			'noi_dung' => __( "Thể nhập Pháp thân thanh tịnh Tỳ Lô Giá Na Phật\nTức thân thành Phật", 'nntm' ),
			'anh'      => 0,
		),
		array(
			'noi_dung' => __( 'Huấn thị tối hậu của Tôn Sư Thích Long Viễn – con đường đưa đến thành tựu như ý nguyện & chứng ngộ cứu cánh Phạm hạnh', 'nntm' ),
			'anh'      => 0,
		),
	)
);

$nntm_r1_tc_tong = count( $nntm_r1_tc_dieu );

if ( 0 === $nntm_r1_tc_tong ) {
	return;
}
?>
<section class="nntm-r1-tong-chi">
	<div class="nntm-r1-tong-chi__khung">

		<h2 class="nntm-r1-tong-chi__tieu-de"><?php esc_html_e( 'Tổng Chỉ', 'nntm' ); ?></h2>

		<div class="nntm-r1-tong-chi__bang" data-nntm-tongchi>
			<?php
			/*
			 * Băng cuộn dùng overflow ngang thật (không phải transform) để
			 * người dùng vuốt được trên cảm ứng và dùng được thanh cuộn
			 * bàn phím. Hai nút chỉ gọi scrollBy().
			 *
			 * KHÔNG dùng scroll-snap: bài học đã ghi trong docs/07-ban-giao.md
			 * — scroll-snap từng làm băng cuộn tự nhảy 110px.
			 */
			?>
			<ol class="nntm-r1-tong-chi__list" data-nntm-tongchi-track>
				<?php foreach ( $nntm_r1_tc_dieu as $nntm_r1_tc_i => $nntm_r1_tc_item ) : ?>
					<?php
					$nntm_r1_tc_anh_id = isset( $nntm_r1_tc_item['anh'] ) ? absint( $nntm_r1_tc_item['anh'] ) : 0;
					$nntm_r1_tc_noi    = isset( $nntm_r1_tc_item['noi_dung'] ) ? (string) $nntm_r1_tc_item['noi_dung'] : '';
					?>
					<li class="nntm-r1-tong-chi__the">
						<?php if ( $nntm_r1_tc_anh_id > 0 ) : ?>
							<?php
							echo wp_get_attachment_image(
								$nntm_r1_tc_anh_id,
								'large',
								false,
								array(
									'class'    => 'nntm-r1-tong-chi__the-anh',
									'alt'      => '',
									'role'     => 'presentation',
									'loading'  => 'lazy',
									'decoding' => 'async',
								)
							);
							?>
						<?php endif; ?>

						<div class="nntm-r1-tong-chi__the-phu" aria-hidden="true"></div>

						<div class="nntm-r1-tong-chi__the-noi">
							<span class="nntm-r1-tong-chi__so" aria-hidden="true"><?php echo esc_html( number_format_i18n( $nntm_r1_tc_i + 1 ) ); ?></span>
							<p class="nntm-r1-tong-chi__chu"><?php echo nl2br( esc_html( $nntm_r1_tc_noi ) ); ?></p>
						</div>
					</li>
				<?php endforeach; ?>
			</ol>

			<button type="button" class="nntm-r1-tong-chi__nut nntm-r1-tong-chi__nut--truoc" data-nntm-tongchi-truoc>
				<span class="nntm-sr-only"><?php esc_html_e( 'Xem điều trước', 'nntm' ); ?></span>
				<svg viewBox="0 0 20 16" aria-hidden="true" focusable="false">
					<path d="M8 1 1 8l7 7M1 8h18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
				</svg>
			</button>

			<button type="button" class="nntm-r1-tong-chi__nut nntm-r1-tong-chi__nut--sau" data-nntm-tongchi-sau>
				<span class="nntm-sr-only"><?php esc_html_e( 'Xem điều tiếp theo', 'nntm' ); ?></span>
				<svg viewBox="0 0 20 16" aria-hidden="true" focusable="false">
					<path d="M12 1l7 7-7 7M19 8H1" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
				</svg>
			</button>
		</div>

	</div>
</section>
