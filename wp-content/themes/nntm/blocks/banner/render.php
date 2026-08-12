<?php
/**
 * Render động cho block nntm/banner — ảnh lớn bo góc đầu trang, biểu tượng
 * + tiêu đề + phụ đề canh giữa đè lên ảnh, tự chạy.
 *
 * Nguồn số đo: Figma QmYLYBVSRkqIbKuKzPUfUe, trang "DESKTOP - R1",
 * khung BANNER 4231:941 (1326x700, bo góc 40) — bóc ngày 10/08/2026.
 *
 * Khung "BACK NEXT BUTTON" (4231:955) trong Figma để visible:false nên
 * KHÔNG dựng nút mũi tên. Chuyển tấm bằng dãy chấm hoặc phím mũi tên.
 *
 * ⚠️ BẪY require: hàm dùng chung nằm ở inc/render-banner.php, nạp bằng
 * require_once — file này bị WordPress core `require` (không phải
 * require_once) mỗi lần render.
 *
 * Block tự mang đệm ngoài (docs/04-kien-truc.md mục 11).
 *
 * @package NNTM
 * @var array    $attributes Thuộc tính của block.
 * @var string   $content    Nội dung InnerBlocks (không dùng ở block này).
 * @var WP_Block $block      Instance block hiện tại.
 */

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/inc/render-banner.php';

// ---------- Đọc & làm sạch thuộc tính ----------

$nntm_bn_raw = ( isset( $attributes['slides'] ) && is_array( $attributes['slides'] ) ) ? $attributes['slides'] : array();

$nntm_bn_slides = array();
foreach ( $nntm_bn_raw as $nntm_bn_item ) {
	if ( ! is_array( $nntm_bn_item ) ) {
		continue;
	}
	$nntm_bn_clean = nntm_banner_clean_slide( $nntm_bn_item );
	if ( null !== $nntm_bn_clean ) {
		$nntm_bn_slides[] = $nntm_bn_clean;
	}
}

$nntm_bn_tong = count( $nntm_bn_slides );

// ---------- Chưa có tấm nào ----------
if ( 0 === $nntm_bn_tong ) {
	/*
	 * Không xuất gì ra trang thật — chỉ nhắc TRONG trình soạn thảo.
	 * ServerSideRender của Gutenberg lấy bản xem trước qua REST API nên
	 * request đó có REST_REQUEST = true; trang thật thì không.
	 */
	if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
		$nntm_bn_wrap_rong = get_block_wrapper_attributes( array( 'class' => 'nntm-banner nntm-banner--empty' ) );
		?>
		<div <?php echo $nntm_bn_wrap_rong; // phpcs:ignore WordPress.Security.EscapeOutput -- get_block_wrapper_attributes() da tu esc_attr() tung thuoc tinh. ?>>
			<p class="nntm-banner__empty-notice">
				<?php esc_html_e( 'Chưa có tấm nào. Mở bảng điều khiển bên phải để thêm ảnh, tiêu đề và phụ đề cho ít nhất một tấm.', 'nntm' ); ?>
			</p>
		</div>
		<?php
	}
	return;
}

$nntm_bn_nhieu_tam = $nntm_bn_tong > 1;

// Tự chạy: mặc định bật, tắt hẳn khi chỉ có 1 tấm (không có gì để chuyển).
$nntm_bn_autoplay = $nntm_bn_nhieu_tam && ( ! isset( $attributes['autoplay'] ) || ! empty( $attributes['autoplay'] ) );

// Chu kỳ (giây) — chặn biên 2–30 để khách không nhập giá trị vô lý.
$nntm_bn_chu_ky = isset( $attributes['interval'] ) ? (float) $attributes['interval'] : 6;
$nntm_bn_chu_ky = max( 2, min( 30, $nntm_bn_chu_ky ) );

// Biểu tượng trang trí phía trên tiêu đề (Vector 105x134 trong Figma).
$nntm_bn_emblem_id  = isset( $attributes['emblemId'] ) ? absint( $attributes['emblemId'] ) : 0;
$nntm_bn_emblem_url = isset( $attributes['emblemUrl'] ) ? esc_url_raw( (string) $attributes['emblemUrl'] ) : '';
$nntm_bn_emblem_alt = isset( $attributes['emblemAlt'] ) ? sanitize_text_field( (string) $attributes['emblemAlt'] ) : '';

$nntm_bn_wrapper = get_block_wrapper_attributes(
	array(
		'class'              => 'nntm-banner',
		'data-nntm-autoplay' => $nntm_bn_autoplay ? '1' : '0',
		'data-nntm-interval' => (string) $nntm_bn_chu_ky,
	)
);
?>
<section <?php echo $nntm_bn_wrapper; // phpcs:ignore WordPress.Security.EscapeOutput -- get_block_wrapper_attributes() da tu esc_attr() tung thuoc tinh. ?>>
	<div
		class="nntm-banner__stage"
		<?php if ( $nntm_bn_nhieu_tam ) : ?>
			aria-roledescription="carousel"
			aria-label="<?php esc_attr_e( 'Băng chuyền ảnh lớn đầu trang', 'nntm' ); ?>"
		<?php endif; ?>
	>
		<?php foreach ( $nntm_bn_slides as $nntm_bn_i => $nntm_bn_slide ) : ?>
			<div
				class="nntm-banner__slide<?php echo 0 === $nntm_bn_i ? ' is-active' : ''; ?>"
				<?php if ( $nntm_bn_nhieu_tam ) : ?>
					role="group"
					aria-roledescription="slide"
					aria-label="<?php echo esc_attr( sprintf( /* translators: %1$d: so thu tu tam, %2$d: tong so tam. */ __( 'Tấm %1$d trên %2$d', 'nntm' ), $nntm_bn_i + 1, $nntm_bn_tong ) ); ?>"
				<?php endif; ?>
			>
				<?php echo nntm_banner_render_anh( $nntm_bn_slide, $nntm_bn_i ); // phpcs:ignore WordPress.Security.EscapeOutput -- ham con da tu esc trong. ?>

				<div class="nntm-banner__overlay" aria-hidden="true"></div>

				<div class="nntm-banner__text">
					<?php
					// Biểu tượng chỉ vẽ ở tấm đầu tiên trong DOM là không đủ —
					// mỗi tấm phủ kín khung nên tấm nào cũng cần biểu tượng riêng.
					if ( $nntm_bn_emblem_id > 0 ) :
						$nntm_bn_emblem_attrs = array(
							'class'    => 'nntm-banner__emblem',
							'alt'      => $nntm_bn_emblem_alt,
							'loading'  => 'lazy',
							'decoding' => 'async',
						);
						// Chỉ gắn role khi thật sự là ảnh trang trí — gắn role=""
						// sẽ sinh thuộc tính rỗng vô nghĩa trong HTML.
						if ( '' === $nntm_bn_emblem_alt ) {
							$nntm_bn_emblem_attrs['role'] = 'presentation';
						}
						echo wp_get_attachment_image( $nntm_bn_emblem_id, 'medium', false, $nntm_bn_emblem_attrs );
					elseif ( '' !== $nntm_bn_emblem_url ) :
						?>
						<img
							class="nntm-banner__emblem"
							src="<?php echo esc_url( $nntm_bn_emblem_url ); ?>"
							alt="<?php echo esc_attr( $nntm_bn_emblem_alt ); ?>"
							loading="lazy"
							decoding="async"
							<?php echo '' === $nntm_bn_emblem_alt ? 'role="presentation"' : ''; // phpcs:ignore WordPress.Security.EscapeOutput -- gia tri co dinh. ?>
						/>
						<?php
					endif;
					?>

					<div class="nntm-banner__text-inner">
						<?php if ( '' !== trim( $nntm_bn_slide['heading'] ) ) : ?>
							<p class="nntm-banner__heading"><?php echo nl2br( esc_html( $nntm_bn_slide['heading'] ) ); ?></p>
						<?php endif; ?>

						<?php if ( '' !== trim( $nntm_bn_slide['text'] ) ) : ?>
							<p class="nntm-banner__sub"><?php echo nl2br( esc_html( $nntm_bn_slide['text'] ) ); ?></p>
						<?php endif; ?>
					</div>
				</div>
			</div>
		<?php endforeach; ?>

		<?php if ( $nntm_bn_nhieu_tam ) : ?>
			<?php echo nntm_banner_render_dots( $nntm_bn_tong ); // phpcs:ignore WordPress.Security.EscapeOutput -- ham con da tu esc trong. ?>
		<?php endif; ?>
	</div>
</section>
