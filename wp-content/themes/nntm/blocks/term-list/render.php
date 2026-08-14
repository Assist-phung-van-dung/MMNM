<?php
/**
 * Render động cho block nntm/term-list — SECTION 6 "Pháp Toà" (và tái dùng
 * cho 5 phân mục còn lại).
 *
 * Không lưu HTML vào nội dung bài: mỗi lần tải trang, get_terms() chạy lại
 * từ $attributes hiện tại. Khách đổi phân mục cha / số lượng / mô tả trên
 * trang, không cần lập trình viên.
 *
 * Mô tả term dùng luôn trường "description" có sẵn của WordPress (không đẻ
 * thêm trường riêng — xem includes/class-term-meta.php ở plugin nntm-core).
 * Ảnh nền thẻ lấy từ term meta "_nntm_term_image" do class-term-meta.php
 * đăng ký và cấp ô chọn ảnh ở màn quản trị taxonomy.
 *
 * Ghi chú: file này có thể được require() nhiều lần trong CÙNG một request
 * (ví dụ trang có 2 khối nntm/term-list, hoặc ServerSideRender gọi lại
 * trong trình soạn thảo) — nên KHÔNG khai báo hằng số/hàm ở top-level tại
 * đây (sẽ vỡ vì khai báo trùng lần thứ hai); mọi thứ dùng biến cục bộ.
 *
 * @package NNTM
 * @var array    $attributes Thuộc tính của block.
 * @var string   $content    Nội dung InnerBlocks (không dùng ở block này).
 * @var WP_Block $block      Instance block hiện tại.
 */

defined( 'ABSPATH' ) || exit;

$nntm_term_list_taxonomy = 'nntm_section';

// ---------- Đọc & làm sạch thuộc tính ----------

$heading = isset( $attributes['heading'] ) ? (string) $attributes['heading'] : '';

$parent_term_id = isset( $attributes['parentTermId'] ) ? absint( $attributes['parentTermId'] ) : 0;

$show_description = ! isset( $attributes['showDescription'] ) || ! empty( $attributes['showDescription'] );

$cta_label = isset( $attributes['ctaLabel'] ) ? (string) $attributes['ctaLabel'] : '';
if ( '' === trim( wp_strip_all_tags( $cta_label ) ) ) {
	$cta_label = __( 'Xem thêm', 'nntm' );
}

$max_items = isset( $attributes['maxItems'] ) ? absint( $attributes['maxItems'] ) : 8;
$max_items = max( 1, min( 20, $max_items ) ); // gioi han hop ly theo dac ta block, khong bao gio truy van khong gioi han.
$layout = isset( $attributes['layout'] ) && 'phap-toa' === $attributes['layout'] ? 'phap-toa' : 'overlay';
$autoplay = ! isset( $attributes['autoplay'] ) || ! empty( $attributes['autoplay'] );
$interval = max( 2, min( 20, absint( $attributes['interval'] ?? 5 ) ) );

$wrapper_attributes = get_block_wrapper_attributes(
	array(
		'class'         => 'nntm-term-list nntm-term-list--' . $layout,
		'data-autoplay' => $autoplay ? '1' : '0',
		'data-interval' => (string) $interval,
	)
);

// ---------- Kiểm tra term cha ----------

$parent_term = null;
if ( $parent_term_id > 0 ) {
	$maybe_term = get_term( $parent_term_id, $nntm_term_list_taxonomy );
	if ( $maybe_term instanceof WP_Term ) {
		$parent_term = $maybe_term;
	}
}

// ---------- Lấy term con (chỉ khi đã có term cha hợp lệ) ----------

$child_terms = array();
if ( $parent_term ) {
	$maybe_children = get_terms(
		array(
			'taxonomy'   => $nntm_term_list_taxonomy,
			'parent'     => $parent_term->term_id,
			'hide_empty' => false,
			'number'     => $max_items,
			'orderby'    => 'name',
			'order'      => 'ASC',
		)
	);

	if ( ! is_wp_error( $maybe_children ) ) {
		$child_terms = $maybe_children;

		/*
		 * Sắp theo trường "Thứ tự hiển thị" ban quản trị tự nhập.
		 * Hàm nằm ở plugin nntm-core (includes/functions.php) vì đây là logic
		 * dữ liệu và block nntm/hero-slider cũng dùng chung — trước đây mỗi nơi
		 * tự sắp một kiểu nên cùng dữ liệu mà ra hai thứ tự khác nhau.
		 * Thiếu plugin thì rơi về thứ tự mặc định của get_terms().
		 */
		if ( function_exists( 'nntm_sort_terms_by_order' ) ) {
			$child_terms = nntm_sort_terms_by_order( $child_terms );
		}
	}
}
?>
<section <?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput -- get_block_wrapper_attributes() da tu esc_attr() tung thuoc tinh. ?>>
	<div class="nntm-container">
		<?php if ( '' !== trim( wp_strip_all_tags( $heading ) ) ) : ?>
			<h2 class="nntm-term-list__heading"><?php echo wp_kses_post( $heading ); ?></h2>
		<?php endif; ?>

		<?php if ( ! $parent_term ) : ?>

			<p class="nntm-term-list__empty">
				<?php esc_html_e( 'Chưa chọn phân mục cha cho khối này. Vào bảng điều khiển bên phải để chọn một phân mục.', 'nntm' ); ?>
			</p>

		<?php elseif ( empty( $child_terms ) ) : ?>

			<p class="nntm-term-list__empty">
				<?php
				printf(
					/* translators: %s: tên phân mục cha */
					esc_html__( 'Phân mục "%s" chưa có phân mục con nào.', 'nntm' ),
					esc_html( $parent_term->name )
				);
				?>
			</p>

		<?php else : ?>

			<div class="nntm-term-list__carousel">
				<?php if ( 'phap-toa' === $layout ) : ?><button type="button" class="nntm-term-list__arrow nntm-term-list__arrow--prev" data-term-prev aria-label="Phần mục trước">←</button><?php endif; ?>
			<div class="nntm-term-list__track" data-term-track>
				<?php
				foreach ( $child_terms as $child_term ) :
					$term_link = get_term_link( $child_term );
					if ( is_wp_error( $term_link ) ) {
						continue;
					}

					$image_id  = absint( get_term_meta( $child_term->term_id, '_nntm_term_image', true ) );
					$thumbnail = $image_id > 0 ? wp_get_attachment_image(
						$image_id,
						'medium_large',
						false,
						array(
							'class'   => 'nntm-term-card__img-el',
							'loading' => 'lazy',
							'alt'     => $child_term->name,
						)
					) : '';

					$description = $show_description ? term_description( $child_term->term_id, $nntm_term_list_taxonomy ) : '';
					?>
					<a href="<?php echo esc_url( $term_link ); ?>" class="nntm-term-card">
						<span class="nntm-term-card__img">
							<?php
							if ( $thumbnail ) {
								echo wp_kses_post( $thumbnail );
							} else {
								echo '<span class="nntm-term-card__img-placeholder" aria-hidden="true"></span>';
							}
							?>
						</span>
						<span class="nntm-term-card__overlay">
							<span class="nntm-term-card__content">
								<span class="nntm-term-card__name"><?php echo esc_html( $child_term->name ); ?></span>
								<span class="nntm-term-card__meta">
									<?php if ( '' !== trim( wp_strip_all_tags( $description ) ) ) : ?>
										<span class="nntm-term-card__desc"><?php echo wp_kses_post( $description ); ?></span>
									<?php endif; ?>
									<?php
									/*
									 * Nút "Xem thêm" KHÔNG được là thẻ <a> riêng: cả thẻ đã là
									 * một <a> bao ngoài (href tới get_term_link()). HTML không
									 * cho phép lồng <a> trong <a> (trình duyệt sẽ tự đóng thẻ
									 * ngoài một cách khó lường, phá layout và phá cả liên kết).
									 * Vì vậy nút chỉ là <span> được tô giống nút bằng CSS —
									 * bấm vào bất kỳ đâu trên thẻ (kể cả "nút" này) đều điều
									 * hướng qua đúng một liên kết duy nhất của thẻ.
									 */
									?>
									<span class="nntm-term-card__cta"><?php echo esc_html( $cta_label ); ?></span>
								</span>
							</span>
						</span>
					</a>
					<?php
				endforeach;
				?>
			</div>
				<?php if ( 'phap-toa' === $layout ) : ?><button type="button" class="nntm-term-list__arrow nntm-term-list__arrow--next" data-term-next aria-label="Phần mục tiếp theo">→</button><?php endif; ?>
			</div>

		<?php endif; ?>
	</div>
</section>
