<?php
/**
 * Template cho CPT `nntm_publication` — WordPress tự chọn file này theo
 * quy tắc single-{post_type}.php, ưu tiên hơn single.php dùng chung.
 *
 * Bối cảnh (giao việc 14/08/2026): chủ dự án gửi ảnh thiết kế trang chi
 * tiết MỘT ấn phẩm (vd "Chiếc Hộp Ánh Trăng"). Đây là BỘ ÁO THỨ BA, khác
 * hẳn hai bộ đã có:
 *   - single-nntm_article.php (khu Hành Giả)  → nền navy (Đại Sĩ) / vàng
 *     nghệ (Kim Cương).
 *   - Trang này (ấn phẩm)                     → nền TRẮNG/rất nhạt, tiêu
 *     đề màu rêu (--nntm-reu), dải liên quan nền kem.
 * KHÔNG được dùng lại class `nntm-bai-hanh-gia*` — đó là khuôn của khu
 * Hành Giả, hai template không dùng chung CSS dù cùng noi theo cấu trúc
 * (dải riêng + khung 1181 + dải liên quan gọi lại card-list).
 *
 * Số đo áp dụng (bóc từ ảnh thiết kế, quy về khung 1366, là ƯỚC LƯỢNG —
 * xem ghi chú đầu assets/css/pages/an-pham.css):
 *   Khung nội dung 1181px căn giữa · tiêu đề ~40px đệm trên ~60px ·
 *   dòng "Cập nhật ..." 14px cách tiêu đề ~16px · đoạn mô tả serif
 *   16px/1.75 · ảnh bìa lớn ~1000px canh giữa cách trên/dưới ~30px ·
 *   hàng nút cuối CHỈ có "Yêu thích" (không có "Đăng Ký Khoá Tu") ·
 *   dải "Cùng chuyên mục" nền kem, tiêu đề ~40px màu rêu căn trái theo
 *   khung, băng thẻ kéo ngang (carousel) biến thể "books".
 *
 * @package NNTM
 */

defined( 'ABSPATH' ) || exit;

get_header();

while ( have_posts() ) :
	the_post();

	$nntm_an_pham_id = get_the_ID();

	// Ngày cập nhật đúng định dạng thiết kế "15. 06. 2026" — cùng cách
	// làm với single-nntm_article.php để nhất quán trên toàn site.
	$nntm_an_pham_ngay_cap_nhat = get_the_modified_date( 'd. m. Y' );

	// Term `nntm_topic` của chính ấn phẩm đang xem — dùng làm bộ lọc cho
	// dải "Cùng chuyên mục". Không có term thì card-list vẫn chạy được
	// với termId=0 (nghĩa là không lọc theo term, lấy mới nhất — xem
	// blocks/card-list/render.php cách xử lý termId=0).
	$nntm_an_pham_terms   = taxonomy_exists( 'nntm_topic' ) ? get_the_terms( $nntm_an_pham_id, 'nntm_topic' ) : false;
	$nntm_an_pham_term_id = ( is_array( $nntm_an_pham_terms ) && ! empty( $nntm_an_pham_terms ) )
		? (int) $nntm_an_pham_terms[0]->term_id
		: 0;
	?>

	<main id="nntm-noi-dung-chinh">

		<section class="nntm-an-pham__than">
			<div class="nntm-an-pham__khung">

				<h1 class="nntm-an-pham__tieu-de"><?php the_title(); ?></h1>

				<p class="nntm-an-pham__ngay">
					<span class="nntm-an-pham__cham" aria-hidden="true"></span>
					<?php
					printf(
						/* translators: %s: ngày cập nhật ấn phẩm, định dạng "15. 06. 2026". */
						esc_html__( 'Cập nhật %s', 'nntm' ),
						esc_html( $nntm_an_pham_ngay_cap_nhat )
					);
					?>
				</p>

				<?php if ( has_post_thumbnail() ) : ?>
					<div class="nntm-an-pham__anh-bia">
						<?php
						the_post_thumbnail(
							'large',
							array(
								'class' => 'nntm-an-pham__anh-bia-img',
								'alt'   => the_title_attribute( array( 'echo' => false ) ),
							)
						);
						?>
					</div>
				<?php endif; ?>

				<div class="nntm-an-pham__noi-dung">
					<?php the_content(); ?>
				</div>

				<div class="nntm-an-pham__hang-nut">
					<?php
					/*
					 * TODO (nợ lại — báo cáo lại cho anh Úy, giống hệt ghi chú
					 * trong single-nntm_article.php): dự án mới có bảng
					 * wp_nntm_favorites (xem
					 * wp-content/plugins/nntm-core/includes/class-schema.php)
					 * CHƯA có nghiệp vụ lưu/bỏ yêu thích (chưa có endpoint
					 * AJAX/REST xử lý data-nntm-favorite ở thời điểm viết file
					 * này). Nút dưới đây CHỈ đúng thiết kế + đúng
					 * data-attribute để phần việc khác gắn hành vi vào sau,
					 * chưa có JS xử lý click.
					 *
					 * Khác với trang bài viết Hành Giả: ảnh thiết kế trang ấn
					 * phẩm CHỈ có một nút "Yêu thích", KHÔNG có nút "Đăng Ký
					 * Khoá Tu" — không thêm nút đó vào đây.
					 */
					?>
					<button
						type="button"
						class="nntm-an-pham__yeu-thich"
						data-nntm-favorite="<?php echo esc_attr( (string) $nntm_an_pham_id ); ?>"
						<?php echo is_user_logged_in() ? '' : 'data-nntm-auth-modal="dang-nhap"'; ?>
					>
						<svg class="nntm-an-pham__tim" viewBox="0 0 24 24" width="24" height="24" aria-hidden="true" focusable="false">
							<path fill="currentColor" d="M12 21s-7.2-4.6-10-9.3C.4 8.6 1.6 5 5 4c2.1-.6 4 .3 5.3 2C11.6 4.3 13.5 3.4 15.6 4c3.4 1 4.6 4.6 3 7.7-2.8 4.7-10 9.3-10 9.3z" />
						</svg>
						<?php esc_html_e( 'Yêu thích', 'nntm' ); ?>
					</button>
				</div>

			</div>
		</section>

		<div class="nntm-an-pham__cung-chuyen-muc">
			<?php
			/*
			 * Tái dùng block nntm/card-list qua render_block() thay vì viết
			 * lại HTML thẻ — đúng khuôn mẫu single-nntm_article.php và đúng
			 * ràng buộc KHÔNG sửa file trong blocks/** (có người khác đang
			 * làm song song ở đó).
			 *
			 * excludePostId: loại chính ấn phẩm đang xem khỏi dải "Cùng
			 * chuyên mục" (thuộc tính đã có sẵn trong block.json, xem
			 * blocks/card-list/render.php dòng dùng absint( excludePostId )).
			 */
			echo render_block(
				array(
					'blockName'    => 'nntm/card-list',
					'attrs'        => array(
						'heading'      => __( 'Cùng chuyên mục', 'nntm' ),
						'postType'     => 'nntm_publication',
						'taxonomy'     => 'nntm_topic',
						'termId'       => $nntm_an_pham_term_id,
						'variant'      => 'books',
						'layout'       => 'carousel',
						'postsPerPage' => 8,
						'background'   => 'kem',
						'showCardCta'  => true,
						'cardCtaLabel' => __( 'Xem thêm', 'nntm' ),
						'excludePostId' => $nntm_an_pham_id,
					),
					'innerBlocks'  => array(),
					'innerHTML'    => '',
					'innerContent' => array(),
				)
			); // phpcs:ignore WordPress.Security.EscapeOutput -- render.php của block đã tự escape mọi đầu ra của nó.
			?>
		</div>

	</main>

<?php endwhile; ?>

<?php
get_footer();
