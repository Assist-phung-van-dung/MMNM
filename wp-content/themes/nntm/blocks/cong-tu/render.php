<?php
/**
 * Render động cho block nntm/cong-tu — khối "Thống Kê Của Đạo Tràng" +
 * "Bảng Xếp Hạng Cá Nhân" cho chương trình trì tụng "chuỗi trì" (Cộng Tu,
 * Phase 2). Đọc dữ liệu qua các hàm ĐÃ CÓ SẴN ở
 * wp-content/plugins/nntm-core/includes/class-chuoi-tri.php (tầng nghiệp
 * vụ ĐÃ XONG, KHÔNG sửa) — file này chỉ vẽ giao diện.
 *
 * WordPress tự require file này (khai qua "render" trong block.json) mỗi
 * khi block xuất hiện trên trang. Logic dùng chung nằm ở
 * inc/render-cong-tu.php, nạp bằng require_once — render.php của block bị
 * lõi WordPress `require` (KHÔNG PHẢI `require_once`), khai hàm thẳng ở
 * đây sẽ chết "Cannot redeclare function" nếu render lần hai (ví dụ
 * ServerSideRender trong trình soạn thảo). Đúng khuôn blocks/rank-card/.
 *
 * Block tự mang đệm ngoài, tràn hết chiều rộng — không bọc trong
 * .nntm-container (docs/04-kien-truc.md mục 11).
 *
 * @package NNTM
 * @var array    $attributes Thuộc tính của block.
 * @var string   $content    Nội dung InnerBlocks (không dùng ở block này).
 * @var WP_Block $block      Instance block hiện tại.
 */

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/inc/render-cong-tu.php';

// ---------- Đọc & làm sạch thuộc tính ----------

$nntm_ctb_program_id   = isset( $attributes['programId'] ) ? absint( $attributes['programId'] ) : 0;
$nntm_ctb_heading      = isset( $attributes['heading'] ) ? (string) $attributes['heading'] : '';
$nntm_ctb_bxh_heading  = isset( $attributes['bxhHeading'] ) ? (string) $attributes['bxhHeading'] : '';
$nntm_ctb_show_thongke = ! isset( $attributes['showThongKe'] ) || ! empty( $attributes['showThongKe'] );
$nntm_ctb_show_bxh     = ! isset( $attributes['showBxh'] ) || ! empty( $attributes['showBxh'] );
$nntm_ctb_bxh_limit    = isset( $attributes['bxhLimit'] ) ? absint( $attributes['bxhLimit'] ) : 50;
if ( $nntm_ctb_bxh_limit <= 0 ) {
	$nntm_ctb_bxh_limit = 50;
}

$nntm_ctb_background = isset( $attributes['background'] ) ? (string) $attributes['background'] : 'kem';
if ( ! in_array( $nntm_ctb_background, array( 'vang', 'kem', 'none' ), true ) ) {
	$nntm_ctb_background = 'kem';
}

$nntm_ctb_program = nntm_congtu_block_resolve_program( $nntm_ctb_program_id );

$nntm_ctb_wrapper_attributes = get_block_wrapper_attributes(
	array(
		'class' => 'nntm-cong-tu nntm-cong-tu--nen-' . $nntm_ctb_background,
	)
);
?>
<section <?php echo $nntm_ctb_wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput -- get_block_wrapper_attributes() da tu esc_attr() tung thuoc tinh. ?>>
	<?php if ( ! $nntm_ctb_program ) : ?>
		<p class="nntm-cong-tu__rong">
			<?php esc_html_e( 'Hiện không có chương trình trì tụng nào đang mở để hiển thị thống kê.', 'nntm' ); ?>
		</p>
	<?php else : ?>
		<?php if ( $nntm_ctb_show_thongke ) : ?>
			<?php echo nntm_congtu_block_render_thong_ke( $nntm_ctb_program, $nntm_ctb_heading ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- ham con da tu esc trong. ?>
		<?php endif; ?>

		<?php if ( $nntm_ctb_show_bxh ) : ?>
			<?php echo nntm_congtu_block_render_bxh( $nntm_ctb_program, $nntm_ctb_bxh_heading, $nntm_ctb_bxh_limit ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- ham con da tu esc trong. ?>
		<?php endif; ?>
	<?php endif; ?>
</section>
