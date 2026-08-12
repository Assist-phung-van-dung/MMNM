<?php
/**
 * Hàm dựng HTML + làm sạch dữ liệu dùng chung cho render.php của block
 * nntm/hero-slider.
 *
 * Tách riêng ra file inc/ (bắt chước đúng blocks/thien-duong/inc/ và
 * blocks/article-mosaic/inc/) vì render.php của block bị WordPress core
 * `require` (KHÔNG PHẢI `require_once`) mỗi lần block render — xem
 * wp-includes/blocks.php, hàm register_block_type_from_metadata(). Khai
 * hàm thẳng trong render.php sẽ chết với lỗi "Cannot redeclare function"
 * nếu khối này render lần thứ hai trên cùng một request (ví dụ
 * ServerSideRender trong trình soạn thảo, hoặc trang có khối này hai lần).
 * File inc/ này được require_once từ render.php nên chỉ khai báo đúng
 * một lần dù render.php bị require lại bao nhiêu lần.
 *
 * @package NNTM
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'nntm_hero_slider_clean_slide' ) ) {
	/**
	 * Làm sạch một phần tử thô trong thuộc tính "slides".
	 *
	 * Trả về null khi tấm hoàn toàn trống (không ảnh, không tiêu đề, không
	 * mô tả, không nút) — tấm rỗng bị loại thẳng ở render.php, không tính
	 * vào tổng số tấm hiển thị (tránh đếm sai trong nhãn "Tấm n trên N" và
	 * tránh dựng một tấm trống xấu trên trang thật).
	 *
	 * @param array $raw_slide Một phần tử thô từ thuộc tính "slides".
	 * @return array{
	 *     image_id: int,
	 *     image_url: string,
	 *     image_alt: string,
	 *     heading: string,
	 *     text: string,
	 *     cta_label: string,
	 *     cta_url: string,
	 * }|null
	 */
	function nntm_hero_slider_clean_slide( array $raw_slide ): ?array {
		$image_id  = isset( $raw_slide['imageId'] ) ? absint( $raw_slide['imageId'] ) : 0;
		$image_url = isset( $raw_slide['imageUrl'] ) ? esc_url_raw( (string) $raw_slide['imageUrl'] ) : '';
		$image_alt = isset( $raw_slide['imageAlt'] ) ? sanitize_text_field( (string) $raw_slide['imageAlt'] ) : '';
		// sanitize_textarea_field() (KHÔNG phải sanitize_text_field()) — giữ lại
		// dấu xuống dòng \n khách gõ trong ô nhập, để nntm_hero_slider_multiline()
		// tách đúng 2 dòng tiêu đề theo thiết kế ("Từ bi trong hành động," /
		// "tĩnh lặng trong tâm hồn."). sanitize_text_field() sẽ âm thầm đổi \n
		// thành khoảng trắng, làm mất xuống dòng.
		$heading   = isset( $raw_slide['heading'] ) ? sanitize_textarea_field( (string) $raw_slide['heading'] ) : '';
		$text      = isset( $raw_slide['text'] ) ? sanitize_text_field( (string) $raw_slide['text'] ) : '';
		$cta_label = isset( $raw_slide['ctaLabel'] ) ? sanitize_text_field( (string) $raw_slide['ctaLabel'] ) : '';
		$cta_url   = isset( $raw_slide['ctaUrl'] ) ? esc_url_raw( (string) $raw_slide['ctaUrl'] ) : '';

		$has_image   = $image_id > 0 || '' !== $image_url;
		$has_heading = '' !== trim( $heading );
		$has_text    = '' !== trim( $text );

		if ( ! $has_image && ! $has_heading && ! $has_text ) {
			// Tấm hoàn toàn rỗng (khách vừa bấm "Thêm tấm" nhưng chưa nhập gì) — bỏ qua.
			return null;
		}

		return array(
			'image_id'  => $image_id,
			'image_url' => $image_url,
			'image_alt' => $image_alt,
			'heading'   => $heading,
			'text'      => $text,
			'cta_label' => $cta_label,
			'cta_url'   => $cta_url,
		);
	}
}

if ( ! function_exists( 'nntm_hero_slider_status_text' ) ) {
	/**
	 * Nhãn "Tấm n trên N" — dùng cho aria-label từng tấm, chấm chuyển tấm
	 * và vùng aria-live.
	 *
	 * @param int $current Số thứ tự tấm (1-based).
	 * @param int $total   Tổng số tấm.
	 * @return string
	 */
	function nntm_hero_slider_status_text( int $current, int $total ): string {
		return sprintf(
			/* translators: 1: số thứ tự tấm hiện tại, 2: tổng số tấm */
			__( 'Tấm %1$d trên %2$d', 'nntm' ),
			$current,
			$total
		);
	}
}

if ( ! function_exists( 'nntm_hero_slider_render_image' ) ) {
	/**
	 * Dựng HTML ảnh nền của một tấm (hoặc ô giữ chỗ khi chưa chọn ảnh).
	 *
	 * Tấm đầu ($is_first = true) nạp ngay (loading="eager" + fetchpriority="high")
	 * vì đây là ảnh lớn nhất màn hình đầu (LCP) — ảnh hưởng trực tiếp tốc độ
	 * tải cảm nhận (yêu cầu nhiệm vụ). Các tấm sau nạp "lazy" vì ẩn ngoài
	 * khung nhìn ban đầu.
	 *
	 * @param array $slide    Tấm đã làm sạch từ nntm_hero_slider_clean_slide().
	 * @param bool  $is_first Có phải tấm đầu tiên không.
	 * @return string HTML đã escape.
	 */
	function nntm_hero_slider_render_image( array $slide, bool $is_first ): string {
		$loading_attr = $is_first ? 'eager' : 'lazy';

		ob_start();

		if ( $slide['image_id'] > 0 ) {
			$image_attrs = array(
				'class'   => 'nntm-hero-slider__img',
				'loading' => $loading_attr,
				'alt'     => $slide['image_alt'],
			);
			if ( $is_first ) {
				$image_attrs['fetchpriority'] = 'high';
			}
			echo wp_kses_post( wp_get_attachment_image( $slide['image_id'], 'full', false, $image_attrs ) );
		} elseif ( '' !== $slide['image_url'] ) {
			?>
			<img
				class="nntm-hero-slider__img"
				src="<?php echo esc_url( $slide['image_url'] ); ?>"
				alt="<?php echo esc_attr( $slide['image_alt'] ); ?>"
				loading="<?php echo esc_attr( $loading_attr ); ?>"
				<?php echo $is_first ? 'fetchpriority="high"' : ''; // phpcs:ignore WordPress.Security.EscapeOutput -- gia tri co dinh, khong tu du lieu nguoi dung. ?>
			/>
			<?php
		} else {
			echo '<span class="nntm-hero-slider__img-placeholder" aria-hidden="true"></span>';
		}

		return trim( (string) ob_get_clean() );
	}
}

if ( ! function_exists( 'nntm_hero_slider_render_slide' ) ) {
	/**
	 * Dựng HTML đầy đủ một tấm: ảnh + lớp phủ chuyển sắc + khối chữ đè lên
	 * ảnh. Cả ảnh và chữ nằm trong cùng một ".nntm-hero-slider__slide" —
	 * chuyển tấm bằng làm mờ chồng (fade) cả khối, không trượt ngang, đúng
	 * yêu cầu nhiệm vụ.
	 *
	 * @param array $slide        Tấm đã làm sạch từ nntm_hero_slider_clean_slide().
	 * @param int   $index        Vị trí trong danh sách (0-based).
	 * @param int   $total        Tổng số tấm.
	 * @param bool  $has_multiple Có nhiều hơn một tấm không — chỉ gắn vai trò
	 *                            "slide"/aria-label khi thật sự là một băng
	 *                            chuyền (một tấm duy nhất thì không cần thêm
	 *                            ngữ nghĩa carousel, tránh nhiễu trình đọc màn hình).
	 * @return string HTML đã escape.
	 */
	function nntm_hero_slider_render_slide( array $slide, int $index, int $total, bool $has_multiple ): string {
		$has_heading = '' !== trim( $slide['heading'] );
		$has_text    = '' !== trim( $slide['text'] );
		$has_cta     = '' !== trim( $slide['cta_label'] ) && '' !== $slide['cta_url'];
		$has_content = $has_heading || $has_text || $has_cta;

		ob_start();
		?>
		<div
			class="nntm-hero-slider__slide<?php echo 0 === $index ? ' is-active' : ''; ?>"
			data-nntm-hero-slide="<?php echo esc_attr( (string) $index ); ?>"
			<?php if ( $has_multiple ) : ?>
				role="group"
				aria-roledescription="slide"
				aria-label="<?php echo esc_attr( nntm_hero_slider_status_text( $index + 1, $total ) ); ?>"
			<?php endif; ?>
		>
			<?php echo nntm_hero_slider_render_image( $slide, 0 === $index ); // phpcs:ignore WordPress.Security.EscapeOutput -- ham con da tu esc trong. ?>

			<span class="nntm-hero-slider__overlay" aria-hidden="true"></span>

			<?php if ( $has_content ) : ?>
				<div class="nntm-hero-slider__content">
					<?php if ( $has_heading ) : ?>
						<h2 class="nntm-hero-slider__heading"><?php echo nntm_hero_slider_multiline( $slide['heading'] ); // phpcs:ignore WordPress.Security.EscapeOutput -- ham con da tu esc trong. ?></h2>
					<?php endif; ?>

					<?php if ( $has_text ) : ?>
						<p class="nntm-hero-slider__text"><?php echo esc_html( $slide['text'] ); ?></p>
					<?php endif; ?>

					<?php if ( $has_cta ) : ?>
						<a class="nntm-hero-slider__cta" href="<?php echo esc_url( $slide['cta_url'] ); ?>">
							<?php echo esc_html( $slide['cta_label'] ); ?>
						</a>
					<?php endif; ?>
				</div>
			<?php endif; ?>
		</div>
		<?php
		return trim( (string) ob_get_clean() );
	}
}

if ( ! function_exists( 'nntm_hero_slider_multiline' ) ) {
	/**
	 * Cho phép tiêu đề tấm xuống dòng theo đúng thiết kế (ví dụ "Từ bi
	 * trong hành động," / "tĩnh lặng trong tâm hồn.") mà không phải mở
	 * HTML tuỳ ý: khách chỉ cần gõ xuống dòng (Enter/\n) trong ô nhập ở
	 * bảng điều khiển, ở đây escape toàn bộ chữ rồi mới đổi \n thành <br>
	 * — không có chuỗi nào của khách lọt ra ngoài esc_html().
	 *
	 * @param string $text Tiêu đề thô (có thể chứa \n / \r\n).
	 * @return string HTML đã escape, an toàn để echo trực tiếp.
	 */
	function nntm_hero_slider_multiline( string $text ): string {
		$lines = preg_split( '/\r\n|\r|\n/', $text );
		$lines = array_map( 'esc_html', $lines );
		return implode( '<br />', $lines );
	}
}

if ( ! function_exists( 'nntm_hero_slider_render_dots' ) ) {
	/**
	 * Dựng dải chấm chuyển tấm — chỉ được gọi khi có nhiều hơn một tấm
	 * (render.php tự kiểm tra trước, hàm này không tự kiểm tra lại).
	 *
	 * @param int $total Tổng số tấm.
	 * @return string HTML đã escape.
	 */
	function nntm_hero_slider_render_dots( int $total ): string {
		ob_start();
		?>
		<div class="nntm-hero-slider__dots" data-nntm-hero-dots>
			<?php for ( $i = 0; $i < $total; $i++ ) : ?>
				<button
					type="button"
					class="nntm-hero-slider__dot<?php echo 0 === $i ? ' is-active' : ''; ?>"
					data-nntm-hero-dot="<?php echo esc_attr( (string) $i ); ?>"
					aria-label="<?php echo esc_attr( sprintf( /* translators: %d: so thu tu tam */ __( 'Xem tấm %d', 'nntm' ), $i + 1 ) ); ?>"
					<?php echo 0 === $i ? 'aria-current="true"' : ''; // phpcs:ignore WordPress.Security.EscapeOutput -- gia tri co dinh, khong tu du lieu nguoi dung. ?>
				></button>
			<?php endfor; ?>
		</div>
		<?php
		return trim( (string) ob_get_clean() );
	}
}

if ( ! function_exists( 'nntm_hero_slider_render_quicklinks' ) ) {
	/**
	 * Dựng dải nút liên kết nhanh ở đáy trái — lấy term con của
	 * $parent_term_id trong taxonomy nntm_section (docs/04-kien-truc.md
	 * mục 10: ví dụ term cha "Pháp Tòa" có 4 term con Nguyên Thuỷ / Đại
	 * Thừa / Tịnh Độ / Mật Tông). Không có term con nào thì trả về chuỗi
	 * rỗng — render.php sẽ không dựng khối rỗng.
	 *
	 * @param int $parent_term_id ID term cha trong nntm_section.
	 * @return string HTML đã escape (rỗng nếu không có term con hợp lệ).
	 */
	function nntm_hero_slider_render_quicklinks( int $parent_term_id ): string {
		if ( $parent_term_id <= 0 || ! taxonomy_exists( 'nntm_section' ) ) {
			return '';
		}

		$child_terms = get_terms(
			array(
				'taxonomy'   => 'nntm_section',
				'parent'     => $parent_term_id,
				'hide_empty' => false,
			)
		);

		if ( is_wp_error( $child_terms ) || empty( $child_terms ) ) {
			return '';
		}

		/*
		 * Sắp theo trường "Thứ tự hiển thị" của ban quản trị — dùng chung hàm
		 * với block nntm/term-list để hai nơi không xếp ra hai thứ tự khác
		 * nhau cho cùng một dữ liệu. Hàm nằm ở plugin nntm-core
		 * (includes/functions.php) vì đây là logic dữ liệu.
		 * Thiếu plugin thì rơi về thứ tự mặc định của get_terms().
		 */
		if ( function_exists( 'nntm_sort_terms_by_order' ) ) {
			$child_terms = nntm_sort_terms_by_order( $child_terms );
		}

		ob_start();
		?>
		<nav class="nntm-hero-slider__quicklinks" aria-label="<?php esc_attr_e( 'Liên kết nhanh', 'nntm' ); ?>">
			<?php foreach ( $child_terms as $child_term ) : ?>
				<?php
				$term_link = get_term_link( $child_term );
				if ( is_wp_error( $term_link ) ) {
					continue;
				}
				?>
				<a class="nntm-hero-slider__quicklink" href="<?php echo esc_url( $term_link ); ?>">
					<?php echo esc_html( $child_term->name ); ?>
				</a>
			<?php endforeach; ?>
		</nav>
		<?php
		return trim( (string) ob_get_clean() );
	}
}

if ( ! function_exists( 'nntm_hero_slider_render_sidecard' ) ) {
	/**
	 * Dựng thẻ mờ góc phải dưới — nội dung tĩnh (không đổi theo tấm đang
	 * xem), lấy từ 4 thuộc tính sideCard* của block. Không có tiêu đề và
	 * không có mô tả thì trả về chuỗi rỗng (không dựng khối rỗng xấu).
	 *
	 * @param string $heading   sideCardHeading đã làm sạch.
	 * @param string $text      sideCardText đã làm sạch.
	 * @param string $cta_label sideCardCtaLabel đã làm sạch.
	 * @param string $cta_url   sideCardCtaUrl đã làm sạch.
	 * @return string HTML đã escape (rỗng nếu không có gì để hiện).
	 */
	function nntm_hero_slider_render_sidecard( string $heading, string $text, string $cta_label, string $cta_url ): string {
		$has_heading = '' !== trim( $heading );
		$has_text    = '' !== trim( $text );
		$has_cta     = '' !== trim( $cta_label ) && '' !== $cta_url;

		if ( ! $has_heading && ! $has_text ) {
			return '';
		}

		ob_start();
		?>
		<aside class="nntm-hero-slider__sidecard">
			<?php if ( $has_heading ) : ?>
				<p class="nntm-hero-slider__sidecard-heading"><?php echo nntm_hero_slider_multiline( $heading ); // phpcs:ignore WordPress.Security.EscapeOutput -- ham con da tu esc trong. ?></p>
			<?php endif; ?>

			<?php if ( $has_text ) : ?>
				<p class="nntm-hero-slider__sidecard-text"><?php echo esc_html( $text ); ?></p>
			<?php endif; ?>

			<?php if ( $has_cta ) : ?>
				<a class="nntm-hero-slider__sidecard-cta" href="<?php echo esc_url( $cta_url ); ?>">
					<?php echo esc_html( $cta_label ); ?>
				</a>
			<?php endif; ?>
		</aside>
		<?php
		return trim( (string) ob_get_clean() );
	}
}
