<?php
/**
 * Render động cho block nntm/hero-slider — băng chuyền đầu trang chủ, ảnh
 * lớn phủ kín tràn hết chiều rộng, chữ đè lên ảnh. Tự chạy (yêu cầu rõ của
 * chủ dự án), chuyển tấm bằng làm mờ chồng (fade), không trượt ngang.
 *
 * Không lưu HTML cố định: mỗi lần tải trang, PHP dựng lại từ thuộc tính
 * hiện tại của block — khách thêm/xoá/sắp lại tấm trong bảng điều khiển
 * bên phải, không cần lập trình viên (docs/04-kien-truc.md mục 2).
 *
 * ⚠️ BẪY require: hàm dùng chung nằm ở inc/render-hero-slider.php, nạp
 * bằng require_once — render.php của block bị WordPress core `require`
 * (KHÔNG PHẢI `require_once`) mỗi lần render (xem wp-includes/blocks.php,
 * hàm register_block_type_from_metadata() -> render_block()). Khai hàm
 * thẳng trong file này sẽ chết với lỗi "Cannot redeclare function" nếu
 * khối này render lần thứ hai trên cùng một trang/request (ví dụ
 * ServerSideRender trong trình soạn thảo gọi lại render_block()). File
 * này chỉ chứa biến cục bộ và câu lệnh procedural, an toàn khi bị require
 * lại nhiều lần.
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

require_once __DIR__ . '/inc/render-hero-slider.php';

// ---------- Đọc & làm sạch thuộc tính ----------

$nntm_hs_raw_slides = ( isset( $attributes['slides'] ) && is_array( $attributes['slides'] ) ) ? $attributes['slides'] : array();

$nntm_hs_slides = array();
foreach ( $nntm_hs_raw_slides as $nntm_hs_raw_slide ) {
	if ( ! is_array( $nntm_hs_raw_slide ) ) {
		continue;
	}
	$nntm_hs_clean_slide = nntm_hero_slider_clean_slide( $nntm_hs_raw_slide );
	if ( null !== $nntm_hs_clean_slide ) {
		$nntm_hs_slides[] = $nntm_hs_clean_slide;
	}
}

$nntm_hs_slide_count = count( $nntm_hs_slides );

// ---------- Chưa có tấm nào ----------
if ( 0 === $nntm_hs_slide_count ) {
	/*
	 * KHÔNG xuất gì ra trang thật khi chưa có tấm nào (yêu cầu nhiệm vụ) —
	 * chỉ hiện thông báo thân thiện TRONG trình soạn thảo.
	 *
	 * Phân biệt bằng hằng REST_REQUEST: ServerSideRender của Gutenberg lấy
	 * bản xem trước qua REST API (/wp/v2/block-renderer/nntm%2Fhero-slider),
	 * request đó có REST_REQUEST = true. Khi khách ngoài site tải trang
	 * thật, WordPress KHÔNG đi qua đường REST này nên REST_REQUEST là
	 * false/chưa định nghĩa — is_admin() không dùng được ở đây vì không
	 * phản ánh đúng ngữ cảnh REST (bắt chước đúng blocks/hero-banner/render.php).
	 */
	if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
		$nntm_hs_wrapper_attributes = get_block_wrapper_attributes( array( 'class' => 'nntm-hero-slider nntm-hero-slider--empty' ) );
		?>
		<div <?php echo $nntm_hs_wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput -- get_block_wrapper_attributes() da tu esc_attr() tung thuoc tinh. ?>>
			<p class="nntm-hero-slider__empty-notice">
				<?php esc_html_e( 'Chưa có tấm băng chuyền nào. Mở bảng điều khiển bên phải để thêm ảnh, tiêu đề và mô tả cho ít nhất một tấm.', 'nntm' ); ?>
			</p>
		</div>
		<?php
	}
	return;
}

$nntm_hs_has_multiple = $nntm_hs_slide_count > 1;

// Tự chạy: mặc định true theo yêu cầu, tắt hẳn khi chỉ có 1 tấm (không có gì để chuyển).
$nntm_hs_autoplay = $nntm_hs_has_multiple && ( ! isset( $attributes['autoplay'] ) || ! empty( $attributes['autoplay'] ) );

// Chu kỳ tự chạy (giây) — mặc định 6, chặn biên 2–30 để tránh khách nhập
// giá trị vô lý (0/âm làm view.js lặp vô hạn, hoặc quá lớn mất tác dụng
// "tự chạy").
$nntm_hs_interval = isset( $attributes['interval'] ) ? (float) $attributes['interval'] : 6;
$nntm_hs_interval = max( 2, min( 30, $nntm_hs_interval ) );

// Vùng "đổi nội dung" báo cho trình đọc màn hình biết tấm hiện tại — CHỈ
// dựng khi có nhiều tấm và KHÔNG autoplay (autoplay tự đổi tấm liên tục,
// gắn aria-live vào đây sẽ làm trình đọc màn hình đọc liên tục rất phiền
// — đúng yêu cầu nhiệm vụ).
$nntm_hs_show_live_region = $nntm_hs_has_multiple && ! $nntm_hs_autoplay;

$nntm_hs_quicklinks_parent_id = isset( $attributes['quickLinksParentTermId'] ) ? absint( $attributes['quickLinksParentTermId'] ) : 0;
$nntm_hs_quicklinks_html      = nntm_hero_slider_render_quicklinks( $nntm_hs_quicklinks_parent_id );

$nntm_hs_sidecard_html = nntm_hero_slider_render_sidecard(
	// sanitize_textarea_field() de giu \n — the phai cung co tieu de 2 dong.
	isset( $attributes['sideCardHeading'] ) ? sanitize_textarea_field( (string) $attributes['sideCardHeading'] ) : '',
	isset( $attributes['sideCardText'] ) ? sanitize_text_field( (string) $attributes['sideCardText'] ) : '',
	isset( $attributes['sideCardCtaLabel'] ) ? sanitize_text_field( (string) $attributes['sideCardCtaLabel'] ) : '',
	isset( $attributes['sideCardCtaUrl'] ) ? esc_url_raw( (string) $attributes['sideCardCtaUrl'] ) : ''
);

$nntm_hs_wrapper_attributes = get_block_wrapper_attributes(
	array(
		'class'               => 'nntm-hero-slider',
		'data-nntm-autoplay' => $nntm_hs_autoplay ? '1' : '0',
		'data-nntm-interval' => (string) $nntm_hs_interval,
	)
);
?>
<section <?php echo $nntm_hs_wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput -- get_block_wrapper_attributes() da tu esc_attr() tung thuoc tinh. ?>>
	<div
		class="nntm-hero-slider__stage"
		<?php if ( $nntm_hs_has_multiple ) : ?>
			aria-roledescription="carousel"
			aria-label="<?php esc_attr_e( 'Băng chuyền đầu trang chủ', 'nntm' ); ?>"
		<?php endif; ?>
	>
		<div class="nntm-hero-slider__track">
			<?php foreach ( $nntm_hs_slides as $nntm_hs_index => $nntm_hs_slide ) : ?>
				<?php echo nntm_hero_slider_render_slide( $nntm_hs_slide, $nntm_hs_index, $nntm_hs_slide_count, $nntm_hs_has_multiple ); // phpcs:ignore WordPress.Security.EscapeOutput -- ham con da tu esc trong. ?>
			<?php endforeach; ?>
		</div>

		<?php if ( '' !== $nntm_hs_sidecard_html ) : ?>
			<?php echo $nntm_hs_sidecard_html; // phpcs:ignore WordPress.Security.EscapeOutput -- ham con da tu esc trong. ?>
		<?php endif; ?>

		<?php if ( '' !== $nntm_hs_quicklinks_html ) : ?>
			<?php echo $nntm_hs_quicklinks_html; // phpcs:ignore WordPress.Security.EscapeOutput -- ham con da tu esc trong. ?>
		<?php endif; ?>

		<?php if ( $nntm_hs_has_multiple ) : ?>
			<?php echo nntm_hero_slider_render_dots( $nntm_hs_slide_count ); // phpcs:ignore WordPress.Security.EscapeOutput -- ham con da tu esc trong. ?>
		<?php endif; ?>

		<?php if ( $nntm_hs_show_live_region ) : ?>
			<p class="nntm-hero-slider__status nntm-sr-only" aria-live="polite" data-nntm-hero-status>
				<?php echo esc_html( nntm_hero_slider_status_text( 1, $nntm_hs_slide_count ) ); ?>
			</p>
		<?php endif; ?>
	</div>
</section>
