<?php
/**
 * Render động cho block nntm/engineering-earth — "dải phim".
 *
 * Figma R4, khung 01_HOMEPAGE 6376:6322, node Frame 155 `6376:6412`
 * (y=3323, cao 714). Đọc node thật ngày 10/08/2026.
 *
 * Bố cục:
 *   - Tiêu đề lớn + dòng phụ, căn giữa.
 *   - Dải nền đen TRÀN VIỀN (rộng 1366 trong khi nội dung trang rộng
 *     1180) chứa ảnh lớn bên trái và tiêu đề phụ bên phải.
 *   - Đoạn chú thích nằm dưới dải, bên trái.
 *   - Thẻ video nổi ĐÈ LÊN mép dưới dải, lệch sang phải.
 *
 * XỬ LÝ THIẾU DỮ LIỆU, không được vỡ bố cục:
 *   không có ảnh    -> ô giữ chỗ màu xám, dải vẫn đủ chiều cao.
 *   không chọn video -> bỏ hẳn thẻ video, không để khung rỗng đè lên dải.
 *   video không ảnh  -> vẫn hiện thẻ với ô giữ chỗ + nút phát.
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
$caption       = isset( $attributes['caption'] ) ? (string) $attributes['caption'] : '';

$image_id  = isset( $attributes['imageId'] ) ? absint( $attributes['imageId'] ) : 0;
$image_url = isset( $attributes['imageUrl'] ) ? esc_url_raw( (string) $attributes['imageUrl'] ) : '';
$image_alt = isset( $attributes['imageAlt'] ) ? sanitize_text_field( (string) $attributes['imageAlt'] ) : '';

$video_id = isset( $attributes['videoId'] ) ? absint( $attributes['videoId'] ) : 0;

$wrapper_attributes = get_block_wrapper_attributes( array( 'class' => 'nntm-engineering-earth' ) );
?>
<section <?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput -- get_block_wrapper_attributes() da tu esc_attr() tung thuoc tinh. ?>>
	<div class="nntm-engineering-earth__inner">

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

		<?php
		/*
		 * Dải đen tràn viền. Figma: Frame 145 rộng 1366 đặt ở x=-93 so với
		 * khung nội dung rộng 1180 — tức thò đều 93px mỗi bên. CSS lo phần
		 * tràn này (xem style.css), HTML không cần biết.
		 */
		?>
		<div class="nntm-engineering-earth__band">
			<div class="nntm-engineering-earth__band-inner">

				<div class="nntm-engineering-earth__media">
					<?php echo nntm_engineering_earth_render_image( $image_id, $image_url, $image_alt ); // phpcs:ignore WordPress.Security.EscapeOutput -- ham con da tu esc trong. ?>
				</div>

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

		<?php if ( '' !== trim( $caption ) ) : ?>
			<p class="nntm-engineering-earth__caption"><?php echo wp_kses_post( $caption ); ?></p>
		<?php endif; ?>

		<?php
		/*
		 * Thẻ video nổi đè lên mép dưới dải đen.
		 *
		 * Trong Figma, thẻ này là CARD biến thể VIDEO nhưng hai lớp `DATE`
		 * và `Frame 125` (nhãn chuyên mục + tiêu đề) đều visible=false —
		 * tức thẻ CHỈ có ảnh và nút phát. Đừng thấy tên lớp mà thêm chữ
		 * vào, sẽ khác thiết kế.
		 */
		echo nntm_engineering_earth_render_video_card( $video_id ); // phpcs:ignore WordPress.Security.EscapeOutput -- ham con da tu esc trong.
		?>

	</div>
</section>
