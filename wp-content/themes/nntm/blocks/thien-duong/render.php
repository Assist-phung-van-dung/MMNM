<?php
/**
 * Render động cho block nntm/thien-duong — Figma "04. LIEN DAN" SECTION 5
 * "Thiền Đường". Khảo sát câu 24: "Đăng nhập và nghe nhạc thiền — đơn
 * giản, nhẹ nhàng." Khảo sát câu 25: thành viên tự chọn bài, không có
 * khung giờ cộng tu cố định — nên trình phát này không có logic "giờ
 * cộng tu", chỉ có danh sách bài tự chọn.
 *
 * Không lưu HTML vào nội dung bài: mỗi lần tải trang, WP_Query (khi đã
 * đăng nhập) chạy lại từ $attributes hiện tại — bắt chước đúng phong cách
 * của blocks/article-mosaic/render.php và blocks/tru-xu-list/render.php.
 *
 * RÒ RỈ ÂM THANH: toàn bộ logic đọc meta "_nntm_track_audio" và
 * wp_get_attachment_url() chỉ chạy trong nhánh is_user_logged_in() bên
 * dưới. Nhánh chưa đăng nhập gọi nntm_thien_duong_render_login_invite() —
 * hàm này không đụng tới WP_Query hay attachment nào, nên chắc chắn không
 * có đường dẫn .mp3/.m4a/.ogg/wp-content/uploads nào lọt ra HTML.
 *
 * ⚠️ Hàm dùng chung (đăng ký meta, dựng HTML) nằm ở inc/*.php, nạp bằng
 * require_once — vì render.php của block bị WordPress core `require`
 * (KHÔNG PHẢI `require_once`) mỗi lần render (xem wp-includes/blocks.php).
 * Khai hàm thẳng trong file này sẽ chết với lỗi "Cannot redeclare function"
 * nếu khối này render hai lần trên cùng một trang.
 *
 * @package NNTM
 * @var array    $attributes Thuộc tính của block.
 * @var string   $content    Nội dung InnerBlocks (không dùng ở block này).
 * @var WP_Block $block      Instance block hiện tại.
 */

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/inc/register-track-audio-meta.php';
require_once __DIR__ . '/inc/render-thien-duong.php';

// ---------- Đọc & làm sạch thuộc tính ----------

$heading    = isset( $attributes['heading'] ) ? (string) $attributes['heading'] : '';
$subheading = isset( $attributes['subheading'] ) ? (string) $attributes['subheading'] : '';

$cover_image_id  = isset( $attributes['coverImageId'] ) ? absint( $attributes['coverImageId'] ) : 0;
$cover_image_url = isset( $attributes['coverImageUrl'] ) ? esc_url_raw( (string) $attributes['coverImageUrl'] ) : '';

// Mặc định 20, tối đa 50 — theo yêu cầu, không bao giờ truy vấn không giới hạn.
$tracks_per_page = isset( $attributes['tracksPerPage'] ) ? absint( $attributes['tracksPerPage'] ) : 20;
$tracks_per_page = max( 1, min( 50, $tracks_per_page ) );

$allowed_order_by = array( 'newest', 'oldest', 'title' );
$order_by_choice   = isset( $attributes['orderBy'] ) ? sanitize_key( (string) $attributes['orderBy'] ) : 'newest';
if ( ! in_array( $order_by_choice, $allowed_order_by, true ) ) {
	$order_by_choice = 'newest';
}

$wrapper_attributes = get_block_wrapper_attributes( array( 'class' => 'nntm-thien-duong' ) );

/*
 * ---------- Cổng vào Thiền Đường ----------
 *
 * Mặc định: chỉ cần ĐĂNG NHẬP. Đây là đúng nghĩa đen phiếu khảo sát câu 24
 * ("Đăng nhập và nghe nhạc thiền — đơn giản, nhẹ nhàng"), tức là điều khách
 * đã tự tay tích chọn.
 *
 * ĐIỂM CÒN MỜ, CHỜ KHÁCH XÁC NHẬN (xem docs/03-chot-tu-khao-sat.md mục ⚠️ A):
 * docs/04-kien-truc.md mục 3 lại xếp Thiền Đường vào quyền
 * `nntm_access_meditation` — tức chỉ Đại Sĩ và Kim Cương Hành Giả mới vào
 * được, thành viên thường thì không. Hai tài liệu chưa khớp nhau và khách
 * chưa nói rõ.
 *
 * Vì chưa rõ nên KHÔNG tự quyết siết chặt: theo đúng phiếu khảo sát, và mở
 * sẵn filter để đổi bằng một dòng khi khách chốt, không phải sửa lại block:
 *
 *   add_filter( 'nntm_thien_duong_can_access', function () {
 *       return current_user_can( 'nntm_access_meditation' );
 *   } );
 */
$is_logged_in = (bool) apply_filters( 'nntm_thien_duong_can_access', is_user_logged_in() );

?>
<section <?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput -- get_block_wrapper_attributes() da tu esc_attr() tung thuoc tinh. ?>>
	<div class="nntm-thien-duong__inner">
		<div class="nntm-thien-duong__header">
			<?php if ( '' !== trim( wp_strip_all_tags( $heading ) ) ) : ?>
				<h2 class="nntm-thien-duong__heading"><?php echo wp_kses_post( $heading ); ?></h2>
			<?php endif; ?>

			<?php if ( '' !== trim( wp_strip_all_tags( $subheading ) ) ) : ?>
				<p class="nntm-thien-duong__subheading"><?php echo wp_kses_post( $subheading ); ?></p>
			<?php endif; ?>
		</div>

		<div class="nntm-thien-duong__embed">
			<div class="nntm-thien-duong__cover">
				<?php
				if ( $cover_image_id > 0 ) :
					echo wp_kses_post(
						wp_get_attachment_image(
							$cover_image_id,
							'large',
							false,
							array(
								'class'   => 'nntm-thien-duong__cover-img',
								'loading' => 'lazy',
								'alt'     => '', // anh minh hoa, khong mang thong tin rieng ngoai tieu de da hien ben tren.
							)
						)
					);
				elseif ( '' !== $cover_image_url ) :
					?>
					<img class="nntm-thien-duong__cover-img" src="<?php echo esc_url( $cover_image_url ); ?>" alt="" loading="lazy" />
					<?php
				else :
					?>
					<span class="nntm-thien-duong__cover-placeholder" aria-hidden="true"></span>
					<?php
				endif;
				?>
			</div>

			<div class="nntm-thien-duong__player">
				<?php
				if ( ! $is_logged_in ) :
					echo nntm_thien_duong_render_guest_preview( $tracks_per_page, $order_by_choice ); // phpcs:ignore WordPress.Security.EscapeOutput -- ham con da tu esc trong va khong doc URL am thanh.
				else :
					$tracks = nntm_thien_duong_get_tracks( $tracks_per_page, $order_by_choice );

					if ( empty( $tracks ) ) :
						?>
						<p class="nntm-thien-duong__empty"><?php esc_html_e( 'Chưa có bản nhạc thiền nào được đăng.', 'nntm' ); ?></p>
						<?php
					else :
						echo nntm_thien_duong_render_player( $tracks ); // phpcs:ignore WordPress.Security.EscapeOutput -- ham con da tu esc trong.
					endif;
				endif;
				?>
			</div>
		</div>
	</div>
</section>
