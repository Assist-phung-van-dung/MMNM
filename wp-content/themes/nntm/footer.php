<?php
/**
 * Template phần chân trang.
 *
 * Dựng theo component FOOTER, đối chiếu ảnh cắt vùng chân trang
 * fig-footer.png (chỉ đạo anh Úy 13/08/2026): một khối nền xám
 * --nntm-muc-nhat thụt vào khỏi mép trái/phải, phía trên/dưới là nền
 * trắng của <footer>; bên trong khối là một đường kẻ mảnh ở trên, rồi
 * đến hàng logo + 3 liên kết (trái) và bản quyền (phải).
 *
 * ĐÃ GỠ 13/08/2026: khối "Hãy chia sẻ Ý kiến của bạn" cùng ô liên kết của
 * nó. Bản Figma chân trang (fig-footer.png) KHÔNG có khối này — quyết
 * định giữ lại ở phiên 10/08 đã bị anh Úy huỷ hôm nay để làm đúng thiết
 * kế. KHÔNG khôi phục lại khối này trừ khi có chỉ đạo mới bằng văn bản.
 *
 * Số đo lấy bằng cách xuất ảnh khung ở tỉ lệ 2x rồi đo pixel (API đọc node
 * đang bị Figma chặn tốc độ). Sai số khoảng 1px. Mọi con số đo bằng ảnh đều
 * ghi chú "do tu anh" để đối chiếu lại khi đọc được node thật.
 *
 *   Thanh chân trang  1326x127   nen #4F4F4F
 *     Duong ke        1225x1     #AAAE99, cach dinh 29, cach trai 30
 *     Hang noi dung   cach dinh 51
 *       Logo 2 dong   142x46     chu #F7F1DE
 *       Lien ket      cach logo 22, chu 16px, #E0E0E0
 *       Ban quyen     sat phai, cung mep phai voi duong ke
 *
 * @package NNTM
 */

defined( 'ABSPATH' ) || exit;

$nntm_footer_lang = function_exists( 'pll_current_language' ) ? pll_current_language( 'slug' ) : 'vi';
$nntm_footer_en   = 'en' === $nntm_footer_lang;
$nntm_front_id    = (int) get_option( 'page_on_front' );
$nntm_is_home     = is_front_page();
if ( ! $nntm_is_home && $nntm_front_id && function_exists( 'pll_get_post_translations' ) ) {
	$nntm_is_home = in_array( get_queried_object_id(), pll_get_post_translations( $nntm_front_id ), true );
}
?>

	<footer id="colophon" class="nntm-footer <?php echo $nntm_is_home ? 'nntm-footer--home' : 'nntm-footer--inner'; ?>">
		<div class="nntm-footer__bar">
			<div class="nntm-footer__inner">
				<?php if ( $nntm_is_home ) : ?>
					<div class="nntm-footer__share">
						<span><?php echo esc_html( $nntm_footer_en ? 'Share your' : 'Hãy chia sẻ' ); ?></span>
						<a class="nntm-footer__share-link" href="<?php echo esc_url( home_url( '/lien-he/' ) ); ?>">
							<?php echo esc_html( $nntm_footer_en ? 'Feedback' : 'Ý kiến' ); ?>
						</a>
						<span><?php echo esc_html( $nntm_footer_en ? 'with us' : 'của bạn' ); ?></span>
					</div>
				<?php endif; ?>

				<?php if ( $nntm_is_home ) : ?>
					<hr class="nntm-footer__rule">
				<?php endif; ?>

				<div class="nntm-footer__row">

					<a class="nntm-footer__logo" href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home">
						<span class="nntm-footer__logo-line"><?php echo esc_html( $nntm_footer_en ? 'Nang Nhan' : 'Năng Nhân' ); ?></span>
						<span class="nntm-footer__logo-line"><?php echo esc_html( $nntm_footer_en ? 'Tich Mac' : 'Tịch Mặc' ); ?></span>
					</a>

					<nav class="nntm-footer__nav" aria-label="<?php esc_attr_e( 'Menu chân trang', 'nntm' ); ?>">
						<?php
						wp_nav_menu(
							array(
								'theme_location' => 'footer',
								'container'      => false,
								'menu_class'     => 'nntm-footer-nav',
								'depth'          => 1,
								/*
								 * Chưa gán menu thì hiện đúng 3 mục như Figma
								 * ("Về chúng tôi | Liên hệ | Chính sách").
								 * Khách gán menu thật ở Giao diện → Menu là
								 * phần này tự nhường chỗ.
								 */
								'fallback_cb'    => 'nntm_footer_menu_fallback',
							)
						);
						?>
					</nav>

					<p class="nntm-footer__copyright">
						<?php
						printf(
							/* translators: %s: năm hiện tại. */
							$nntm_footer_en ? '© Copyright Nang Nhan Tich Mac %s' : '© Copyright Năng Nhân Tịch Mặc %s',
							esc_html( wp_date( 'Y' ) )
						);
						?>
					</p>

				</div>

			</div>
		</div>
	</footer>
</div><!-- /.nntm-site-frame -->

<?php wp_footer(); ?>
</body>
</html>
