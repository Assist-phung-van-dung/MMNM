<?php
/**
 * Hàm dựng HTML cho MỘT thẻ nội dung (card).
 *
 * Tách riêng thành hàm dùng chung để block `nntm/card` (đứng một mình)
 * và block `nntm/card-list` (lặp nhiều thẻ) đều gọi cùng một chỗ —
 * đúng nguyên tắc "sửa một variant thì sửa đúng một chỗ" ở
 * docs/04-kien-truc.md mục 2.
 *
 * File này được require_once từ render.php của cả hai block, nên
 * không cần bọc function_exists(): require_once tự đảm bảo hàm chỉ
 * khai báo một lần dù render.php của block bị WordPress require()
 * lại nhiều lần trong một lượt tải trang.
 *
 * @package NNTM
 */

defined( 'ABSPATH' ) || exit;

/**
 * Danh sách biến thể hợp lệ — PHẢI trùng tên với variant trong Figma
 * (component set CARD, node 6134:2530).
 *
 * @return string[]
 */
function nntm_card_allowed_variants(): array {
	return array( 'article', 'small', 'xs', 'dai-si', 'article-hover', 'video', 'khoa-tu', 'books' );
}

/**
 * Lấy taxonomy term "chính" của một bài để hiển thị nhãn phân mục trên thẻ.
 * Duyệt theo thứ tự ưu tiên vì mỗi CPT gắn taxonomy khác nhau
 * (xem class-taxonomies.php): nntm_section chỉ có ở nntm_article,
 * nntm_topic có ở article/publication/talk, nntm_series ở talk/video,
 * category là taxonomy có sẵn của post type `post`.
 *
 * @param int $post_id ID bài viết.
 * @return WP_Term|null
 */
function nntm_card_get_primary_term( int $post_id ): ?WP_Term {
	$priority = array( 'nntm_section', 'nntm_topic', 'nntm_series', 'category' );

	foreach ( $priority as $taxonomy ) {
		if ( ! taxonomy_exists( $taxonomy ) ) {
			continue;
		}

		$terms = get_the_terms( $post_id, $taxonomy );

		if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
			return $terms[0];
		}
	}

	return null;
}

/**
 * Dựng HTML cho một thẻ nội dung.
 *
 * Block động: hàm này chạy lại mỗi lần trang được tải, không có gì
 * lưu cứng vào nội dung bài — đổi thiết kế variant sau này chỉ sửa
 * ở đây và ở style.css, bài cũ tự cập nhật theo.
 *
 * @param int    $post_id       ID bài viết cần hiển thị.
 * @param string $variant       Biến thể Figma, xem nntm_card_allowed_variants().
 * @param bool   $show_date     Có hiện ngày cập nhật không.
 * @param bool   $show_excerpt  Có hiện đoạn mô tả ngắn không (chỉ áp dụng cho variant có ô mô tả).
 * @param bool   $show_category Có hiện nhãn phân mục không.
 * @return string HTML đã escape, sẵn sàng echo.
 */
function nntm_render_card_markup( int $post_id, string $variant, bool $show_date = true, bool $show_excerpt = true, bool $show_category = true ): string {
	if ( ! in_array( $variant, nntm_card_allowed_variants(), true ) ) {
		$variant = 'article';
	}

	$post = $post_id > 0 ? get_post( $post_id ) : null;

	if ( ! $post instanceof WP_Post || 'publish' !== $post->post_status ) {
		return '<p class="nntm-card nntm-card--empty">' . esc_html__( 'Chưa chọn bài viết để hiển thị.', 'nntm' ) . '</p>';
	}

	// dai-si chỉ hiện ảnh + tên chủ đề, không có ngày/nhãn/mô tả/nút — theo đúng Figma DAI SI CARD.
	$is_dai_si = ( 'dai-si' === $variant );
	// video không có nút "Xem thêm": cả thẻ bấm vào là phát video, có icon play đè lên ảnh thay cho nút.
	$has_cta     = ! $is_dai_si && 'video' !== $variant;
	$has_excerpt = $show_excerpt && in_array( $variant, array( 'article', 'khoa-tu', 'books' ), true );

	$permalink = get_permalink( $post );
	$title     = get_the_title( $post );
	$thumbnail = get_the_post_thumbnail(
		$post,
		'medium_large',
		array(
			'class'   => 'nntm-card__img-el',
			'loading' => 'lazy',
			'alt'     => $title,
		)
	);

	$classes   = array( 'nntm-card', 'nntm-card--' . $variant );
	$class_attr = esc_attr( implode( ' ', $classes ) );

	ob_start();
	?>
	<a href="<?php echo esc_url( $permalink ); ?>" class="<?php echo esc_attr( $class_attr ); ?>">
		<span class="nntm-card__img">
			<?php
			if ( $thumbnail ) {
				echo wp_kses_post( $thumbnail );
			} else {
				echo '<span class="nntm-card__img-placeholder" aria-hidden="true"></span>';
			}
			?>
			<?php if ( 'video' === $variant ) : ?>
				<span class="nntm-card__play" aria-hidden="true"></span>
			<?php endif; ?>
		</span>
		<span class="nntm-card__body">
			<?php if ( $show_date && ! $is_dai_si ) : ?>
				<span class="nntm-card__date">
					<span class="nntm-card__date-icon" aria-hidden="true"></span>
					<?php
					echo esc_html(
						sprintf(
							/* translators: %s: ngày cập nhật bài viết, định dạng d. m. Y giống Figma */
							__( 'Cập nhật %s', 'nntm' ),
							get_the_modified_date( 'd. m. Y', $post )
						)
					);
					?>
				</span>
			<?php endif; ?>

			<?php
			if ( $show_category && ! $is_dai_si ) :
				$term = nntm_card_get_primary_term( $post->ID );
				if ( $term ) :
					?>
					<span class="nntm-card__cat"><?php echo esc_html( $term->name ); ?></span>
					<?php
				endif;
			endif;
			?>

			<span class="nntm-card__title"><?php echo esc_html( $title ); ?></span>

			<?php if ( $has_excerpt ) : ?>
				<span class="nntm-card__excerpt"><?php echo esc_html( wp_trim_words( get_the_excerpt( $post ), 24, '…' ) ); ?></span>
			<?php endif; ?>

			<?php if ( $has_cta ) : ?>
				<span class="nntm-card__cta"><?php esc_html_e( 'Xem thêm', 'nntm' ); ?></span>
			<?php endif; ?>
		</span>
	</a>
	<?php
	return trim( (string) ob_get_clean() );
}
