<?php
/**
 * Render động cho block nntm/rank-card — hero trang "Nhập Pháp Giới": ảnh
 * nền tràn viền, tiêu đề giữa, hàng thẻ cấp bậc dẫn sang trang riêng theo
 * quyền thành viên (docs/04-kien-truc.md mục 2, biến thể Figma
 * "CARD DAI SI/KIM CUONG").
 *
 * WordPress tự require file này (khai báo qua "render" trong block.json)
 * mỗi khi block xuất hiện trên trang. Không lưu HTML vào nội dung bài.
 *
 * Logic quyền dùng chung nằm ở inc/render-rank-card.php, nạp bằng
 * require_once — render.php của block bị WordPress core `require` (KHÔNG
 * PHẢI `require_once`) mỗi lần render (xem wp-includes/blocks.php,
 * register_block_type_from_metadata() -> render_block()). Khai hàm thẳng
 * trong file này sẽ chết với lỗi "Cannot redeclare function" nếu khối này
 * render lần thứ hai trên cùng một trang/request (ví dụ ServerSideRender
 * trong trình soạn thảo).
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

require_once __DIR__ . '/inc/render-rank-card.php';

// ---------- Đọc & làm sạch thuộc tính ----------

$nntm_rc_heading = isset( $attributes['heading'] ) ? (string) $attributes['heading'] : '';

$nntm_rc_bg_image_id  = isset( $attributes['bgImageId'] ) ? absint( $attributes['bgImageId'] ) : 0;
$nntm_rc_bg_image_url = isset( $attributes['bgImageUrl'] ) ? esc_url_raw( (string) $attributes['bgImageUrl'] ) : '';
$nntm_rc_bg_image_alt = isset( $attributes['bgImageAlt'] ) ? trim( (string) $attributes['bgImageAlt'] ) : '';

// Ảnh nền ưu tiên lấy theo ID (kích cỡ "full" từ thư viện) — có ID thì lấy
// đúng URL hiện hành của tệp, tránh lệch nếu ảnh trong thư viện đã đổi.
if ( $nntm_rc_bg_image_id > 0 ) {
	$nntm_rc_bg_src = wp_get_attachment_image_url( $nntm_rc_bg_image_id, 'full' );
	if ( $nntm_rc_bg_src ) {
		$nntm_rc_bg_image_url = $nntm_rc_bg_src;
	}
}

$nntm_rc_min_height = isset( $attributes['minHeight'] ) ? absint( $attributes['minHeight'] ) : 690;
if ( $nntm_rc_min_height <= 0 ) {
	$nntm_rc_min_height = 690;
}

$nntm_rc_raw_cards = ( isset( $attributes['cards'] ) && is_array( $attributes['cards'] ) ) ? $attributes['cards'] : array();

$nntm_rc_cards = array();
foreach ( $nntm_rc_raw_cards as $nntm_rc_raw_card ) {
	if ( ! is_array( $nntm_rc_raw_card ) ) {
		continue;
	}
	$nntm_rc_clean_card = nntm_rank_card_clean_card( $nntm_rc_raw_card );
	if ( null !== $nntm_rc_clean_card ) {
		$nntm_rc_cards[] = $nntm_rc_clean_card;
	}
}

// Chưa có thẻ nào: không xuất gì ra trang thật, chỉ báo trong trình soạn
// thảo (bắt chước đúng blocks/hero-slider/render.php).
if ( empty( $nntm_rc_cards ) ) {
	if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
		$nntm_rc_wrapper_attributes = get_block_wrapper_attributes( array( 'class' => 'nntm-rank-card nntm-rank-card--empty' ) );
		?>
		<div <?php echo $nntm_rc_wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput -- get_block_wrapper_attributes() da tu esc_attr() tung thuoc tinh. ?>>
			<p class="nntm-rank-card__empty-notice">
				<?php esc_html_e( 'Chưa có thẻ cấp bậc nào. Mở bảng điều khiển bên phải để thêm ít nhất một thẻ.', 'nntm' ); ?>
			</p>
		</div>
		<?php
	}
	return;
}

$nntm_rc_style = 'min-height:' . $nntm_rc_min_height . 'px;';
if ( '' !== $nntm_rc_bg_image_url ) {
	$nntm_rc_style .= 'background-image:url(' . esc_url( $nntm_rc_bg_image_url ) . ');';
}

$nntm_rc_wrapper_extra = array(
	'class' => 'nntm-rank-card',
	'style' => $nntm_rc_style,
);

// Ảnh nền chỉ là CSS background-image (không có thẻ <img>) nên trình đọc
// màn hình bỏ qua mặc định. Có mô tả (alt) thì gắn role="img" + aria-label
// để người dùng vẫn biết nội dung ảnh; không có thì để mặc định (trang trí).
if ( '' !== $nntm_rc_bg_image_alt ) {
	$nntm_rc_wrapper_extra['role']       = 'img';
	$nntm_rc_wrapper_extra['aria-label'] = $nntm_rc_bg_image_alt;
}

$nntm_rc_wrapper_attributes = get_block_wrapper_attributes( $nntm_rc_wrapper_extra );
?>
<section <?php echo $nntm_rc_wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput -- get_block_wrapper_attributes() da tu esc_attr() tung thuoc tinh. ?>>
	<div class="nntm-rank-card__overlay">
		<?php if ( '' !== trim( wp_strip_all_tags( $nntm_rc_heading ) ) ) : ?>
			<h2 class="nntm-rank-card__heading"><?php echo wp_kses_post( $nntm_rc_heading ); ?></h2>
		<?php endif; ?>

		<div class="nntm-rank-card__row">
			<?php foreach ( $nntm_rc_cards as $nntm_rc_card ) : ?>
				<?php echo nntm_rank_card_render_card( $nntm_rc_card ); // phpcs:ignore WordPress.Security.EscapeOutput -- ham con da tu esc trong. ?>
			<?php endforeach; ?>
		</div>
	</div>
</section>
