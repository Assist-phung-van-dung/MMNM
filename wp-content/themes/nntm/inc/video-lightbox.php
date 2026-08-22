<?php
/**
 * Popup toàn màn hình phát video YouTube — thành phần DÙNG CHUNG của theme.
 *
 * Yêu cầu chủ dự án 21/08/2026, hai lượt:
 *   1. "hiện tại 2 phần này là link youtube, anh muốn khi nhấn vào sẽ mở 1
 *      popup full màn hình và xem hết video" — hai băng chạy trang chủ
 *      "Xuyên Vạn Kiếp" và "GITA CENTER x NẴNG NHÂN TỊCH MẶC"
 *      (block nntm/card-list, videoSource=youtube).
 *   2. "2 video ở block Dải phim anh cũng muốn nhấn vào sẽ mở popup" — khung
 *      lớn + thẻ nhỏ tràn mép của block nntm/engineering-earth.
 *
 * VÌ SAO Ở CẤP THEME chứ không nằm trong một block: HAI block khác nhau cùng
 * dùng popup này. Lần đầu popup được in từ chính blocks/card-list/, và
 * engineering-earth chỉ chạy được nhờ trang chủ TÌNH CỜ có thêm băng YouTube
 * — một ràng buộc ngầm sẽ vỡ ngay khi dải phim được chèn ở trang khác. Đưa
 * lên đây thì mỗi block chỉ cần có `data-video-id` trên phần tử của nó.
 *
 * Cùng khuôn với inc/cong-tu.php: MỘT hàm điều kiện duy nhất
 * (nntm_video_lightbox_can_tren_trang()) dùng cho cả việc nạp CSS/JS lẫn việc
 * in markup ở chân trang, để hai chỗ không thể lệch điều kiện nhau.
 *
 * @package NNTM
 */

defined( 'ABSPATH' ) || exit;

/**
 * Trong danh sách khối (đệ quy cả khối lồng) có khối nào cần popup video?
 *
 * Cần khi:
 *   - có nntm/engineering-earth (dải phim luôn là video YouTube), hoặc
 *   - có nntm/card-list đang lấy nội dung từ link YouTube dán tay
 *     (videoSource=youtube) — card-list nguồn bài viết KHÔNG cần.
 *
 * @param array $blocks Kết quả parse_blocks().
 * @return bool
 */
function nntm_video_lightbox_co_trong_khoi( array $blocks ): bool {
	foreach ( $blocks as $block ) {
		if ( ! is_array( $block ) || empty( $block['blockName'] ) ) {
			continue;
		}

		if ( 'nntm/engineering-earth' === $block['blockName'] ) {
			return true;
		}

		$attrs = isset( $block['attrs'] ) && is_array( $block['attrs'] ) ? $block['attrs'] : array();

		if (
			'nntm/card-list' === $block['blockName'] &&
			isset( $attrs['videoSource'] ) &&
			'youtube' === $attrs['videoSource']
		) {
			return true;
		}

		if ( ! empty( $block['innerBlocks'] ) && is_array( $block['innerBlocks'] ) ) {
			if ( nntm_video_lightbox_co_trong_khoi( $block['innerBlocks'] ) ) {
				return true;
			}
		}
	}

	return false;
}

/**
 * Trang đang xem có cần popup video hay không. Tính MỘT LẦN cho mỗi request —
 * hàm được gọi ở cả wp_enqueue_scripts và wp_footer.
 *
 * @return bool
 */
function nntm_video_lightbox_can_tren_trang(): bool {
	static $can = null;

	if ( null !== $can ) {
		return $can;
	}

	$post = get_post();
	$can  = ( $post instanceof WP_Post ) && nntm_video_lightbox_co_trong_khoi( parse_blocks( $post->post_content ) );

	return $can;
}

/**
 * Nạp CSS/JS của popup — chỉ ở trang thật sự có video.
 */
function nntm_video_lightbox_enqueue(): void {
	if ( ! nntm_video_lightbox_can_tren_trang() ) {
		return;
	}

	$css_path = NNTM_THEME_DIR . '/assets/css/video-lightbox.css';
	wp_enqueue_style(
		'nntm-video-lightbox',
		NNTM_THEME_URI . '/assets/css/video-lightbox.css',
		array( 'nntm-tokens', 'nntm-base' ),
		nntm_asset_version( $css_path )
	);

	$js_path = NNTM_THEME_DIR . '/assets/js/video-lightbox.js';
	wp_enqueue_script(
		'nntm-video-lightbox',
		NNTM_THEME_URI . '/assets/js/video-lightbox.js',
		array(),
		nntm_asset_version( $js_path ),
		true
	);
}
add_action( 'wp_enqueue_scripts', 'nntm_video_lightbox_enqueue' );

/**
 * In khung popup ở chân trang — MỘT lần cho cả trang, mọi khối dùng chung.
 *
 * Chỉ có KHUNG RỖNG; <iframe> do assets/js/video-lightbox.js chèn lúc bấm và
 * tháo ra lúc đóng (tháo iframe là cách chắc chắn nhất để video dừng phát).
 *
 * Markup dựng ở PHP (không dựng bằng JS) để mọi chữ đi qua bộ dịch của
 * WordPress, đúng khuôn các popup khác của theme
 * (template-parts/auth/modal-dang-nhap.php, template-parts/cong-tu/modal-chuoi-tri.php).
 *
 * wp_footer() được gọi NGOÀI .nntm-site-frame (xem footer.php) nên position:
 * fixed của popup không bị lớp `zoom` trong assets/css/responsive.css làm lệch.
 */
function nntm_video_lightbox_render(): void {
	if ( ! nntm_video_lightbox_can_tren_trang() ) {
		return;
	}
	?>
	<div class="nntm-yt-lightbox" id="nntm-yt-lightbox" hidden>
		<div class="nntm-yt-lightbox__overlay" data-nntm-yt-lightbox-close></div>

		<div class="nntm-yt-lightbox__panel" role="dialog" aria-modal="true" aria-label="<?php esc_attr_e( 'Trình phát video', 'nntm' ); ?>">
			<button type="button" class="nntm-yt-lightbox__close" data-nntm-yt-lightbox-close>
				<span class="nntm-sr-only"><?php esc_html_e( 'Đóng video', 'nntm' ); ?></span>
				<span aria-hidden="true">&times;</span>
			</button>

			<div class="nntm-yt-lightbox__frame" data-nntm-yt-lightbox-frame></div>
		</div>
	</div>
	<?php
}
add_action( 'wp_footer', 'nntm_video_lightbox_render' );
