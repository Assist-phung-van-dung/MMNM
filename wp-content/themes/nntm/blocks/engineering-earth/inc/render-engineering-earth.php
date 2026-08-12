<?php
/**
 * Hàm dựng HTML dùng chung cho render.php của block nntm/engineering-earth.
 *
 * Tách sang inc/ vì render.php của block bị WordPress core `require`
 * (KHÔNG phải `require_once`) mỗi lần render — khai hàm thẳng trong
 * render.php sẽ "Cannot redeclare function" khi block xuất hiện lần thứ
 * hai trên cùng trang. Xem docs/07-ban-giao.md mục "Bài học rút ra".
 *
 * @package NNTM
 */

defined( 'ABSPATH' ) || exit;

/**
 * Dựng ảnh lớn trong dải đen.
 *
 * Ưu tiên ID thư viện (có srcset, ảnh responsive); chỉ rơi về URL thô khi
 * ban quản trị dán đường dẫn ngoài. Không có gì thì trả ô giữ chỗ để dải
 * đen vẫn đủ chiều cao, không bị sụp.
 *
 * @param int    $image_id  ID ảnh trong Thư viện.
 * @param string $image_url URL ảnh ngoài (dự phòng).
 * @param string $image_alt Chữ thay ảnh.
 * @return string HTML đã escape.
 */
function nntm_engineering_earth_render_image( int $image_id, string $image_url, string $image_alt ): string {
	if ( $image_id > 0 ) {
		$html = wp_get_attachment_image(
			$image_id,
			'large',
			false,
			array(
				'class'   => 'nntm-engineering-earth__img-el',
				'loading' => 'lazy',
				'alt'     => $image_alt,
			)
		);
		if ( $html ) {
			return $html;
		}
	}

	if ( '' !== $image_url ) {
		return sprintf(
			'<img class="nntm-engineering-earth__img-el" src="%s" alt="%s" loading="lazy" />',
			esc_url( $image_url ),
			esc_attr( $image_alt )
		);
	}

	return '<span class="nntm-engineering-earth__img-placeholder" aria-hidden="true"></span>';
}

/**
 * Dựng thẻ video nổi đè lên mép dưới dải đen.
 *
 * Figma: CARD 388x243, nền #000000 mờ 70%, đệm 20, bên trong chỉ có ảnh
 * 348x203 và nút phát 75x75 — hai lớp `DATE` và `Frame 125` (nhãn chuyên
 * mục + tiêu đề) đều visible=false nên KHÔNG hiện chữ gì.
 *
 * Chưa chọn video thì trả chuỗi rỗng: thà thiếu thẻ còn hơn để một khung
 * xám rỗng đè lên dải đen.
 *
 * @param int $video_id ID bài video (CPT nntm_video hoặc bất kỳ bài nào).
 * @return string HTML đã escape.
 */
function nntm_engineering_earth_render_video_card( int $video_id ): string {
	if ( $video_id <= 0 ) {
		return '';
	}

	$video = get_post( $video_id );
	if ( ! $video instanceof WP_Post || 'publish' !== $video->post_status ) {
		return '';
	}

	$permalink = get_permalink( $video );
	$title     = get_the_title( $video );
	$thumbnail = get_the_post_thumbnail(
		$video,
		'medium_large',
		array(
			'class'   => 'nntm-engineering-earth__card-img-el',
			'loading' => 'lazy',
			'alt'     => '',
		)
	);

	ob_start();
	?>
	<a class="nntm-engineering-earth__card" href="<?php echo esc_url( $permalink ); ?>">
		<span class="nntm-engineering-earth__card-media">
			<?php
			if ( $thumbnail ) {
				echo wp_kses_post( $thumbnail );
			} else {
				echo '<span class="nntm-engineering-earth__card-placeholder" aria-hidden="true"></span>';
			}
			?>
			<?php
			/*
			 * Nút phát: Figma vẽ vector 75x75 màu #FCFCFC. Dựng bằng SVG
			 * thay vì ảnh để đổi màu theo biến CSS và không thêm một lượt
			 * tải tệp.
			 */
			?>
			<span class="nntm-engineering-earth__card-play" aria-hidden="true">
				<svg viewBox="0 0 75 75" width="75" height="75" fill="none" focusable="false">
					<circle cx="37.5" cy="37.5" r="35" stroke="currentColor" stroke-width="3" />
					<path d="M30 24.5 L54 37.5 L30 50.5 Z" fill="currentColor" />
				</svg>
			</span>
		</span>
		<?php
		/*
		 * Thẻ chỉ có ảnh nên không có chữ nào để trình đọc màn hình bám
		 * vào. Tên bài để ở đây, chỉ trình đọc màn hình nghe thấy — nếu
		 * không thì liên kết này hoàn toàn câm.
		 */
		?>
		<span class="nntm-sr-only">
			<?php
			/* translators: %s: tên video. */
			echo esc_html( sprintf( __( 'Xem video %s', 'nntm' ), $title ) );
			?>
		</span>
	</a>
	<?php
	return trim( (string) ob_get_clean() );
}
