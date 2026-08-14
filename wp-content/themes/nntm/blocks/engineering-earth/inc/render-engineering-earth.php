<?php
/**
 * Hàm dựng HTML dùng chung cho render.php của block nntm/engineering-earth.
 *
 * D1 — khối "The Drum of the True Dharma" (docs/spec-trang-chu.md mục D1):
 * nguồn video là link YouTube admin dán vào block (KHÔNG dùng YouTube Data
 * API, giống G1 ở nntm/card-list).
 *
 * SỬA 13/08/2026 (đối chiếu lại với ảnh Figma thật, xem render.php mục
 * "LỖI 1" trong ghi chú của điều phối viên): bố cục KHÔNG phải 2 khe xếp
 * dọc tỉ lệ 3:1 như bản trước — mà là:
 *   - Khung media LỚN bên trái, cao gần hết dải đen (590x~299) = video CHÍNH.
 *   - Chữ "ENGINEERING EARTH" bên phải.
 *   - MỘT THẺ NHỎ (350x197, tỉ lệ 16:9) ĐÈ LÊN góc dưới-phải, tràn xuống
 *     DƯỚI mép dải đen ~43px = video NỀN (tự phát/câm/lặp/không điều khiển).
 * Nhấp vào thẻ nào cũng đổi vai trò cho thẻ kia (xem view.js) — style.css
 * quyết định khe nào to/khe nào nhỏ dựa theo class --main/--bg, nên JS chỉ
 * cần đổi class, không cần biết toạ độ.
 *
 * Tách sang inc/ vì render.php bị WordPress core `require` (KHÔNG phải
 * `require_once`) mỗi lần render — khai hàm thẳng trong render.php sẽ
 * "Cannot redeclare function" khi block xuất hiện lần thứ hai trên cùng
 * trang. Xem docs/07-ban-giao.md mục "Bài học rút ra".
 *
 * @package NNTM
 */

defined( 'ABSPATH' ) || exit;

/**
 * Tách ID YouTube (11 ký tự) từ một chuỗi dán vào — chấp nhận link đầy đủ
 * (watch?v=, youtu.be/, embed/, shorts/) hoặc ID trần.
 *
 * Trùng logic với nntm_card_list_extract_youtube_id() ở
 * blocks/card-list/inc/render-card-list-youtube.php — cố ý KHÔNG gọi
 * chéo sang thư mục block khác (phạm vi nhiệm vụ chỉ được sửa trong
 * blocks/engineering-earth/**, không đụng blocks/card-list/**), nên giữ
 * một bản riêng ở đây. Nếu sau này tách ra tiện ích dùng chung thì gộp lại.
 *
 * @param string $raw Chuỗi dán vào (URL hoặc ID trần).
 * @return string ID hợp lệ, hoặc chuỗi rỗng nếu không nhận diện được.
 */
function nntm_engineering_earth_extract_youtube_id( string $raw ): string {
	$value = trim( $raw );

	if ( '' === $value ) {
		return '';
	}

	if ( preg_match( '/^[A-Za-z0-9_-]{11}$/', $value ) ) {
		return $value;
	}

	if ( preg_match( '#(?:youtube(?:-nocookie)?\.com/(?:watch\?v=|embed/|shorts/|v/)|youtu\.be/)([A-Za-z0-9_-]{11})#i', $value, $matches ) ) {
		return $matches[1];
	}

	return '';
}

/**
 * Dựng phần "bên trong" của một khe video: ảnh giữ chỗ (poster/ảnh tĩnh
 * dự phòng/icon) + <div> rỗng để JS (view.js) tự chèn <iframe> — dùng
 * chung cho cả khe main và khe bg, khác nhau ở nguồn ảnh dự phòng.
 *
 * @param string $video_id   ID YouTube — rỗng nếu chưa dán link.
 * @param string $fallback_image_html HTML <img> tĩnh dự phòng khi KHÔNG có
 *                           video (đã escape sẵn ở nơi gọi) — rỗng thì dùng
 *                           icon khay phim chung.
 * @return string HTML đã escape.
 */
function nntm_engineering_earth_render_slot_media( string $video_id, string $fallback_image_html ): string {
	$poster_url = '' !== $video_id ? esc_url( 'https://img.youtube.com/vi/' . $video_id . '/hqdefault.jpg' ) : '';

	ob_start();
	?>
	<?php if ( '' !== $poster_url ) : ?>
		<img class="nntm-engineering-earth__video-poster" src="<?php echo $poster_url; // phpcs:ignore WordPress.Security.EscapeOutput -- da esc_url() truoc do. ?>" alt="" loading="lazy" decoding="async" />
	<?php elseif ( '' !== $fallback_image_html ) : ?>
		<?php echo $fallback_image_html; // phpcs:ignore WordPress.Security.EscapeOutput -- da escape o noi goi (wp_get_attachment_image()/esc_url()). ?>
	<?php else : ?>
		<span class="nntm-engineering-earth__video-poster nntm-engineering-earth__video-poster--rong" aria-hidden="true">
			<svg viewBox="0 0 48 48" width="30" height="30" fill="none" focusable="false">
				<rect x="4" y="10" width="40" height="28" rx="4" stroke="currentColor" stroke-width="2" />
				<path d="M20 18 L30 24 L20 30 Z" fill="currentColor" />
			</svg>
		</span>
	<?php endif; ?>
	<div class="nntm-engineering-earth__video-embed" aria-hidden="true"></div>
	<?php
	return trim( (string) ob_get_clean() );
}

/**
 * Dựng một "khe" video (main hoặc bg).
 *
 * Chưa có video (admin chưa dán link):
 *   - Khe MAIN: hiện ảnh tĩnh dự phòng (mainImageId/mainImageUrl của
 *     block, admin tự chọn trong trình soạn thảo) — KHÔI PHỤC khả năng
 *     đặt ảnh nền cho khung lớn theo yêu cầu 13/08/2026. Không có ảnh
 *     thì hiện icon khay phim, không để ô đen trống trơn.
 *   - Khe BG: hiện icon khay phim (không có ảnh dự phòng riêng cho khe
 *     nhỏ — Figma không yêu cầu).
 *
 * @param string $video_id ID YouTube.
 * @param string $role     'main' hoặc 'bg'.
 * @param string $label    Chữ mô tả cho aria-label / trình đọc màn hình.
 * @param string $fallback_image_html HTML <img> tĩnh dự phòng (chỉ dùng cho role=main), đã escape sẵn.
 * @return string HTML đã escape.
 */
function nntm_engineering_earth_render_video_slot( string $video_id, string $role, string $label, string $fallback_image_html = '', string $link_url = '' ): string {
	$is_main    = ( 'main' === $role );
	$role_class = $is_main ? 'nntm-engineering-earth__video-slot--main' : 'nntm-engineering-earth__video-slot--bg';

	ob_start();
	?>
	<div
		class="nntm-engineering-earth__video-slot <?php echo esc_attr( $role_class ); ?>"
		data-role="<?php echo esc_attr( $role ); ?>"
		data-video-id="<?php echo esc_attr( $video_id ); ?>"
		aria-label="<?php echo esc_attr( $label ); ?>"
	>
		<?php echo nntm_engineering_earth_render_slot_media( $video_id, $is_main ? $fallback_image_html : '' ); // phpcs:ignore WordPress.Security.EscapeOutput -- da escape ben trong. ?>
		<?php if ( '' !== $link_url ) : ?>
			<a class="nntm-engineering-earth__video-link" href="<?php echo esc_url( $link_url ); ?>">
				<span class="nntm-sr-only"><?php esc_html_e( 'Xem bài viết video', 'nntm' ); ?></span>
			</a>
		<?php endif; ?>
	</div>
	<?php
	return trim( (string) ob_get_clean() );
}

/**
 * Dựng ảnh tĩnh dự phòng cho khung media LỚN khi chưa dán link video —
 * ưu tiên ID ảnh trong Thư viện (có srcset), rơi về URL ngoài nếu có,
 * không có gì thì trả rỗng (khi đó render_video_slot() tự hiện icon).
 *
 * @param int    $image_id  ID ảnh trong Thư viện.
 * @param string $image_url URL ảnh ngoài (dự phòng khi không chọn từ Thư viện).
 * @param string $image_alt Chữ thay ảnh.
 * @return string HTML <img> đã escape, hoặc rỗng.
 */
function nntm_engineering_earth_render_main_fallback_image( int $image_id, string $image_url, string $image_alt ): string {
	if ( $image_id > 0 ) {
		$html = wp_get_attachment_image(
			$image_id,
			'large',
			false,
			array(
				'class'   => 'nntm-engineering-earth__video-poster',
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
			'<img class="nntm-engineering-earth__video-poster" src="%s" alt="%s" loading="lazy" />',
			esc_url( $image_url ),
			esc_attr( $image_alt )
		);
	}

	return '';
}

/**
 * Dựng cả sân khấu 2 video (main + bg) của dải đen.
 *
 * @param string $main_video_url_or_id Link/ID YouTube video chính.
 * @param string $bg_video_url_or_id   Link/ID YouTube video nền.
 * @param int    $main_image_id  ID ảnh Thư viện dự phòng cho khung lớn (khi chưa có mainVideoUrl).
 * @param string $main_image_url URL ảnh ngoài dự phòng cho khung lớn.
 * @param string $main_image_alt Chữ thay ảnh dự phòng.
 * @return string HTML đã escape.
 */
function nntm_engineering_earth_render_video_stage( string $main_video_url_or_id, string $bg_video_url_or_id, int $main_image_id, string $main_image_url, string $main_image_alt, string $video_link_url = '' ): string {
	$main_id = nntm_engineering_earth_extract_youtube_id( $main_video_url_or_id );
	$bg_id   = nntm_engineering_earth_extract_youtube_id( $bg_video_url_or_id );

	$main_fallback_image = ( '' === $main_id )
		? nntm_engineering_earth_render_main_fallback_image( $main_image_id, $main_image_url, $main_image_alt )
		: '';

	ob_start();
	?>
	<div class="nntm-engineering-earth__video-stage" data-nntm-ee-stage="1">
		<?php echo nntm_engineering_earth_render_video_slot( $main_id, 'main', __( 'Video chính', 'nntm' ), $main_fallback_image, $video_link_url ); // phpcs:ignore WordPress.Security.EscapeOutput -- da escape ben trong. ?>
		<?php echo nntm_engineering_earth_render_video_slot( $bg_id, 'bg', __( 'Video nền', 'nntm' ), '', $video_link_url ); // phpcs:ignore WordPress.Security.EscapeOutput -- da escape ben trong. ?>
	</div>
	<?php
	return trim( (string) ob_get_clean() );
}
