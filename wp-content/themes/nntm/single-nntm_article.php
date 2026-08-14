<?php
/**
 * Template cho CPT `nntm_article` — WordPress tự chọn file này theo quy
 * tắc single-{post_type}.php, ưu tiên hơn single.php.
 *
 * Bài thuộc khu Hành Giả (term con "dai-si-hanh-gia" / "kim-cuong-hanh-gia"
 * của taxonomy `nntm_section`, dưới term cha "Nhập Pháp Giới" term_id=7 —
 * xem docs/04-kien-truc.md mục 10 và inc/hanh-gia.php) hiển thị dải nền
 * navy tràn viền theo thiết kế riêng. Bài KHÔNG thuộc khu này hiển thị
 * đúng như single.php cũ (nền sáng, bố cục thường) — file này KHÔNG được
 * bắt mọi `nntm_article` mặc áo navy.
 *
 * Ràng buộc docs/04-kien-truc.md mục 3 ("giao diện tự đổi theo cấp bằng
 * cách gắn class gốc lên <body> và đảo biến CSS — không nhân đôi
 * template"): Đại Sĩ và Kim Cương dùng CHUNG khối HTML bên dưới, chỉ khác
 * class biến thể "nntm-bai-hanh-gia--dai-si" / "--kim-cuong" để đảo biến
 * CSS trong assets/css/pages/bai-hanh-gia.css. Sau này có thiết kế Kim
 * Cương thật, chỉ cần thêm một khối biến CSS — không đụng file này.
 *
 * Số đo áp dụng (bóc từ ảnh thiết kế, quy về khung 1366, là ƯỚC LƯỢNG —
 * xem ghi chú đầu assets/css/pages/bai-hanh-gia.css):
 *   Khung nội dung 1181px căn giữa · tiêu đề 42px cách mép trên dải 60px ·
 *   dòng ngày 15px cách tiêu đề 18px · đoạn văn 16px/1.75 cách nhau 12px ·
 *   ảnh trong bài 550×684 cách trên/dưới 28px · hàng nút cách đoạn cuối
 *   40px · đáy dải navy đệm dưới 90px.
 *
 * @package NNTM
 */

defined( 'ABSPATH' ) || exit;

get_header();

$nntm_cap_hanh_gia = function_exists( 'nntm_bai_thuoc_hanh_gia' ) ? nntm_bai_thuoc_hanh_gia( get_queried_object() ) : null;

if ( null === $nntm_cap_hanh_gia ) :

	/*
	 * Bài KHÔNG thuộc khu Hành Giả — giữ nguyên khung tối thiểu của
	 * single.php (xem file đó), không dựng dải navy.
	 */
	?>
	<main id="nntm-noi-dung-chinh" class="nntm-container nntm-mt-8 nntm-mb-8">
		<?php
		while ( have_posts() ) :
			the_post();
			get_template_part( 'template-parts/content/content', 'article' );
		endwhile;
		?>
	</main>
	<?php

else :

	while ( have_posts() ) :
		the_post();

		$nntm_class_bien_the = ( 'kim_cuong' === $nntm_cap_hanh_gia )
			? 'nntm-bai-hanh-gia--kim-cuong'
			: 'nntm-bai-hanh-gia--dai-si';

		// Ngày cập nhật đúng định dạng thiết kế "15. 06. 2026".
		$nntm_ngay_cap_nhat = get_the_modified_date( 'd. m. Y' );

		// URL "Đăng Ký Khoá Tu": trang slug "lien-dan" nếu có, không thì trang chủ.
		$nntm_trang_lien_dan = get_page_by_path( 'lien-dan' );
		$nntm_url_khoa_tu    = apply_filters(
			'nntm_dang_ky_khoa_tu_url',
			$nntm_trang_lien_dan ? get_permalink( $nntm_trang_lien_dan ) : home_url( '/' )
		);

		// Term của chính bài đang xem — dùng làm bộ lọc cho "Bài viết liên quan".
		$nntm_terms_hien_tai   = get_the_terms( get_the_ID(), 'nntm_section' );
		$nntm_term_id_hien_tai = ( is_array( $nntm_terms_hien_tai ) && ! empty( $nntm_terms_hien_tai ) )
			? (int) $nntm_terms_hien_tai[0]->term_id
			: 0;
		?>

		<main id="nntm-noi-dung-chinh">

			<section class="nntm-bai-hanh-gia__than <?php echo esc_attr( $nntm_class_bien_the ); ?>">
				<div class="nntm-bai-hanh-gia__khung">

					<h1 class="nntm-bai-hanh-gia__tieu-de"><?php the_title(); ?></h1>

					<p class="nntm-bai-hanh-gia__ngay">
						<span class="nntm-bai-hanh-gia__cham" aria-hidden="true"></span>
						<?php
						printf(
							/* translators: %s: ngày cập nhật bài viết, định dạng "15. 06. 2026". */
							esc_html__( 'Cập nhật %s', 'nntm' ),
							esc_html( $nntm_ngay_cap_nhat )
						);
						?>
					</p>

					<div class="nntm-bai-hanh-gia__noi-dung">
						<?php the_content(); ?>
					</div>

					<div class="nntm-bai-hanh-gia__hang-nut">
						<?php
						/*
						 * TODO (nợ lại — báo cáo lại cho anh Úy): dự án mới có
						 * bảng wp_nntm_favorites (xem
						 * wp-content/plugins/nntm-core/includes/class-schema.php)
						 * CHƯA có nghiệp vụ lưu/bỏ yêu thích (không có endpoint
						 * AJAX/REST nào xử lý data-nntm-favorite ở thời điểm
						 * viết file này). Nút dưới đây CHỈ đúng thiết kế + đúng
						 * data-attribute để phần việc khác gắn hành vi vào sau,
						 * chưa có JS xử lý click.
						 */
						?>
						<button
							type="button"
							class="nntm-bai-hanh-gia__yeu-thich"
							data-nntm-favorite="<?php echo esc_attr( (string) get_the_ID() ); ?>"
							<?php echo is_user_logged_in() ? '' : 'data-nntm-auth-modal="dang-nhap"'; ?>
						>
							<svg class="nntm-bai-hanh-gia__tim" viewBox="0 0 24 24" width="24" height="24" aria-hidden="true" focusable="false">
								<path fill="currentColor" d="M12 21s-7.2-4.6-10-9.3C.4 8.6 1.6 5 5 4c2.1-.6 4 .3 5.3 2C11.6 4.3 13.5 3.4 15.6 4c3.4 1 4.6 4.6 3 7.7-2.8 4.7-10 9.3-10 9.3z" />
							</svg>
							<?php esc_html_e( 'Yêu thích', 'nntm' ); ?>
						</button>

						<a class="nntm-bai-hanh-gia__dang-ky" href="<?php echo esc_url( $nntm_url_khoa_tu ); ?>">
							<?php esc_html_e( 'Đăng Ký Khoá Tu', 'nntm' ); ?>
						</a>
					</div>

				</div>
			</section>

			<div class="nntm-bai-hanh-gia__lien-quan">
				<?php
				/*
				 * Tái dùng block nntm/card-list qua render_block() thay vì
				 * viết lại HTML thẻ (không đụng file trong blocks/**).
				 *
				 * Ghi chú còn nợ: card-list hiện KHÔNG có thuộc tính loại trừ
				 * bài đang xem khỏi danh sách, nên bài đang xem có thể lọt
				 * vào chính "Bài viết liên quan" của nó nếu nó nằm trong
				 * postsPerPage=8 bài mới nhất cùng term. Không tự sửa block
				 * theo đúng giới hạn công việc — cần xử lý ở phần việc khác
				 * (thêm attribute loại trừ hoặc excludePostId cho card-list).
				 *
				 * "showCardCta"/"cardCtaLabel" (dòng "Xem thêm" trên thẻ)
				 * chưa có trong block.json tại thời điểm viết file này — cứ
				 * truyền vào, WordPress bỏ qua thuộc tính lạ, không lỗi.
				 */
				echo render_block(
					array(
						'blockName'    => 'nntm/card-list',
						'attrs'        => array(
							'heading'      => __( 'Bài viết liên quan', 'nntm' ),
							'postType'     => 'nntm_article',
							'taxonomy'     => 'nntm_section',
							'termId'       => $nntm_term_id_hien_tai,
							'variant'      => 'article',
							'layout'       => 'carousel',
							'postsPerPage' => 8,
							'background'   => 'none',
							'showDate'     => false,
							'showCategory' => false,
							'showCardCta'  => true,
							'cardCtaLabel' => __( 'Xem thêm', 'nntm' ),
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

<?php endif; ?>

<?php
get_footer();
