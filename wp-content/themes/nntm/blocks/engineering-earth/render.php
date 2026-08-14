<?php
/**
 * Render động cho block nntm/engineering-earth — "dải phim" trang chủ.
 *
 * Cập nhật 12/08/2026 (docs/spec-trang-chu.md mục A + D1): tách đúng 2 dải
 * theo Figma (trước đây gộp làm một khối cao 709):
 *   - Dải TRẮNG cao 254px: tiêu đề lớn "The Drum of the True Dharma" + dòng phụ.
 *   - Dải ĐEN cao 418px: khung media LỚN bên trái (590x~299) + chữ
 *     "ENGINEERING EARTH" bên phải + MỘT THẺ NHỎ (350x197, tỉ lệ 16:9) đè
 *     lên góc dưới-phải, TRÀN XUỐNG dưới mép dải đen ~43px.
 *
 * SỬA 13/08/2026 (đối chiếu ảnh chụp trang thật với Figma — bản 12/08/2026
 * dựng SAI cấu trúc: hai khe xếp DỌC theo tỉ lệ 3:1, không giống Figma):
 *   - Khung media lớn  = video CHÍNH  (nếu chưa dán link thì hiện ẢNH TĨNH
 *     dự phòng — xem mainImageId/mainImageUrl bên dưới, KHÔI PHỤC khả năng
 *     này sau khi lượt trước đã gỡ nhầm).
 *   - Thẻ nhỏ tràn mép  = video NỀN   (tự phát, câm tiếng, lặp, không thanh
 *     điều khiển).
 *   - Nhấp vào thẻ nào cũng đổi vai trò cho thẻ kia (xem view.js).
 *
 * HƯỚNG DẪN ADMIN DÁN LINK YOUTUBE (D1 — chưa ai dán link nào tính đến
 * 12/08/2026, CSDL hiện KHÔNG có video nào lưu URL YouTube — 12 bài CPT
 * nntm_video mẫu chỉ có ảnh đại diện, không dùng được, xem
 * docs/spec-trang-chu.md mục D1):
 *   1. Mở block này trong trình soạn thảo (Gutenberg) → panel bên phải
 *      "Video (D1 — dán link YouTube)".
 *   2. Ô "Video chính (khung lớn)": dán 1 link/ID cho video LỚN. Chưa dán
 *      thì có thể chọn ẢNH TĨNH thay thế ở panel "Ảnh dự phòng khung lớn".
 *   3. Ô "Video nền (thẻ nhỏ tràn mép)": dán 1 link/ID cho video NHỎ.
 *   4. Chấp nhận cả 3 dạng: link đầy đủ (youtube.com/watch?v=…), link rút
 *      gọn (youtu.be/…), hoặc chỉ ID video (11 ký tự).
 *   5. KHÔNG dùng YouTube Data API (anh Úy chốt 12/08/2026, giống G1 ở
 *      nntm/card-list) — mọi thứ xử lý bằng cách tách ID từ chuỗi dán
 *      vào, xem inc/render-engineering-earth.php.
 *
 * XỬ LÝ THIẾU DỮ LIỆU, không được vỡ bố cục: chưa dán link nào -> khung
 * lớn hiện ảnh tĩnh dự phòng (hoặc icon khay phim nếu cũng chưa chọn ảnh),
 * thẻ nhỏ hiện icon khay phim — không để ô đen trống trơn. Hai dải vẫn
 * đúng 254/418.
 *
 * @package NNTM
 * @var array    $attributes Thuộc tính của block.
 * @var string   $content    Nội dung InnerBlocks (không dùng ở block này).
 * @var WP_Block $block      Instance block hiện tại.
 */

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/inc/render-engineering-earth.php';

// ---------- Đọc & làm sạch thuộc tính ----------

$heading       = isset( $attributes['heading'] ) ? (string) $attributes['heading'] : '';
$subheading    = isset( $attributes['subheading'] ) ? (string) $attributes['subheading'] : '';
$band_title    = isset( $attributes['bandTitle'] ) ? (string) $attributes['bandTitle'] : '';
$band_subtitle = isset( $attributes['bandSubtitle'] ) ? (string) $attributes['bandSubtitle'] : '';

$main_video_url = isset( $attributes['mainVideoUrl'] ) ? (string) $attributes['mainVideoUrl'] : '';
$bg_video_url   = isset( $attributes['bgVideoUrl'] ) ? (string) $attributes['bgVideoUrl'] : '';
$video_post_id  = isset( $attributes['videoId'] ) ? absint( $attributes['videoId'] ) : 0;
$video_post_url = ( $video_post_id > 0 && 'nntm_video' === get_post_type( $video_post_id ) )
	? get_permalink( $video_post_id )
	: '';

// Ảnh tĩnh dự phòng cho khung media LỚN khi CHƯA dán mainVideoUrl — khôi
// phục lại theo yêu cầu 13/08/2026 (trước đó đã gỡ, khiến khung lớn trống
// trơn khi chưa có video).
$main_image_id  = isset( $attributes['mainImageId'] ) ? absint( $attributes['mainImageId'] ) : 0;
$main_image_url = isset( $attributes['mainImageUrl'] ) ? esc_url_raw( (string) $attributes['mainImageUrl'] ) : '';
$main_image_alt = isset( $attributes['mainImageAlt'] ) ? sanitize_text_field( (string) $attributes['mainImageAlt'] ) : '';

$wrapper_attributes = get_block_wrapper_attributes( array( 'class' => 'nntm-engineering-earth' ) );
?>
<section <?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput -- get_block_wrapper_attributes() da tu esc_attr() tung thuoc tinh. ?>>

	<?php // ---------- Dải 1: TRẮNG, cao 254px — tiêu đề lớn ---------- ?>
	<div class="nntm-engineering-earth__white">
		<?php if ( '' !== trim( $heading ) || '' !== trim( $subheading ) ) : ?>
			<div class="nntm-engineering-earth__heading-group">
				<?php if ( '' !== trim( $heading ) ) : ?>
					<h2 class="nntm-engineering-earth__heading"><?php echo wp_kses_post( $heading ); ?></h2>
				<?php endif; ?>

				<?php if ( '' !== trim( $subheading ) ) : ?>
					<p class="nntm-engineering-earth__subheading"><?php echo wp_kses_post( $subheading ); ?></p>
				<?php endif; ?>
			</div>
		<?php endif; ?>
	</div>

	<?php
	/*
	 * Dải 2: ĐEN, cao 418px — tràn viền (rộng 1366 trong khi nội dung
	 * trang rộng 1180). CSS lo phần tràn này (xem style.css), HTML không
	 * cần biết.
	 */
	?>
	<div class="nntm-engineering-earth__band">
		<div class="nntm-engineering-earth__band-inner">

			<?php echo nntm_engineering_earth_render_video_stage( $main_video_url, $bg_video_url, $main_image_id, $main_image_url, $main_image_alt, $video_post_url ); // phpcs:ignore WordPress.Security.EscapeOutput -- ham con da tu esc trong. ?>

			<?php if ( '' !== trim( $band_title ) || '' !== trim( $band_subtitle ) ) : ?>
				<div class="nntm-engineering-earth__band-text">
					<?php if ( '' !== trim( $band_title ) ) : ?>
						<h3 class="nntm-engineering-earth__band-title"><?php echo wp_kses_post( $band_title ); ?></h3>
					<?php endif; ?>

					<?php if ( '' !== trim( $band_subtitle ) ) : ?>
						<p class="nntm-engineering-earth__band-subtitle"><?php echo wp_kses_post( $band_subtitle ); ?></p>
					<?php endif; ?>
				</div>
			<?php endif; ?>

		</div>
	</div>
</section>
