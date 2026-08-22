<?php
/**
 * Kho ấn phẩm — /an-pham/
 *
 * Gallery bìa sách: 4 bìa một hàng, 12 bìa một trang, mỗi bìa CHỈ có ảnh và
 * tên. Chủ dự án chốt 22/08/2026.
 *
 * VÌ SAO KHÔNG DÙNG nntm_render_card_markup( 'books' ):
 * thẻ dùng chung đó luôn in thêm dòng "Xem thêm" — `$has_cta` của nó tính
 * `'video' !== $variant`, nên với variant "books" là luôn đúng, tắt bằng tham
 * số không được. Nó còn dựng cho nền tối (nền rgba(0,0,0,.5), chữ trắng, ảnh
 * VUÔNG 1:1) vì sinh ra cho dải đen trang Hoa Khai. Kho sách này nằm trên nền
 * trang thường và bìa sách là khổ DỌC. Sửa thẻ dùng chung cho vừa chỗ này thì
 * đụng cả Hoa Khai, Kim Cương Hành Giả — nên dựng riêng, gọn hơn nhiều.
 *
 * Số bài mỗi trang đặt ở nntm_an_pham_archive_query() (inc/an-pham.php), không
 * đặt bằng WP_Query mới trong file này — để phân trang tính đúng.
 *
 * @package NNTM
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>

<main id="nntm-noi-dung-chinh" class="nntm-an-pham-kho">

	<div class="nntm-container">

		<header class="nntm-an-pham-kho__dau">
			<h1 class="nntm-an-pham-kho__tieu-de"><?php post_type_archive_title(); ?></h1>
		</header>

		<?php if ( have_posts() ) : ?>

			<ul class="nntm-an-pham-kho__luoi">
				<?php
				while ( have_posts() ) :
					the_post();

					/*
					 * Bìa bấm vào là vào THẲNG trình đọc khi người xem được phép —
					 * cùng lối với thẻ bìa sách ở blocks/card/inc/render-card.php.
					 * Ấn phẩm đang khoá thì về trang chi tiết, nơi có lời mời
					 * thanh toán (trang đó không tự chuyển tiếp khi còn khoá).
					 */
					$nntm_dich = get_permalink();

					if ( function_exists( 'nntm_doc_url' ) && nntm_an_pham_can_access( get_post() ) ) {
						$nntm_doc = nntm_doc_url( get_post() );

						if ( '' !== $nntm_doc ) {
							$nntm_dich = $nntm_doc;
						}
					}
					?>
					<li class="nntm-an-pham-kho__o">
						<a class="nntm-an-pham-kho__the" href="<?php echo esc_url( $nntm_dich ); ?>">
							<span class="nntm-an-pham-kho__bia">
								<?php
								if ( has_post_thumbnail() ) {
									the_post_thumbnail(
										'medium_large',
										array(
											'class'   => 'nntm-an-pham-kho__anh',
											'loading' => 'lazy',
											'alt'     => the_title_attribute( array( 'echo' => false ) ),
										)
									);
								} else {
									/*
									 * Ấn phẩm chưa có ảnh bìa vẫn phải chiếm đúng một
									 * ô của lưới, nếu không cả hàng bị lệch. Ô rỗng
									 * này giữ đúng khổ dọc như bìa thật.
									 */
									echo '<span class="nntm-an-pham-kho__bia-trong" aria-hidden="true"></span>';
								}
								?>
							</span>
							<span class="nntm-an-pham-kho__ten"><?php the_title(); ?></span>
						</a>
					</li>
					<?php
				endwhile;
				?>
			</ul>

			<?php
			the_posts_pagination(
				array(
					'class'              => 'nntm-an-pham-kho__phan-trang',
					'mid_size'           => 2,
					'prev_text'          => esc_html__( 'Trước', 'nntm' ),
					'next_text'          => esc_html__( 'Sau', 'nntm' ),
					'screen_reader_text' => esc_html__( 'Chuyển trang kho ấn phẩm', 'nntm' ),
				)
			);
			?>

		<?php else : ?>

			<p class="nntm-an-pham-kho__trong"><?php esc_html_e( 'Chưa có ấn phẩm nào.', 'nntm' ); ?></p>

		<?php endif; ?>

	</div>

</main>

<?php
get_footer();
