<?php
/**
 * Chân trang — DÙNG CHUNG cho mọi trang.
 *
 * Trước đây trang chủ có một biến thể riêng (nntm-footer--home) kèm dòng
 * "Hãy chia sẻ Ý kiến của bạn" và một lớp bọc phụ. Nay chân trang chỉ còn một
 * kiểu duy nhất; dòng mời góp ý đã tách ra thành khối riêng (nntm/y-kien) để
 * quản trị viên tự đặt vào bất kỳ trang nào, xem blocks/y-kien/.
 */

defined( 'ABSPATH' ) || exit;

$nntm_footer_lang = function_exists( 'pll_current_language' ) ? pll_current_language( 'slug' ) : 'vi';
$nntm_footer_en   = 'en' === $nntm_footer_lang;
?>

	<footer id="colophon" class="nntm-footer nntm-footer--chung">
		<div class="nntm-footer__bar">
			<div class="nntm-footer__inner">

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
								'fallback_cb'    => 'nntm_footer_menu_fallback',
							)
						);
						?>
					</nav>

					<p class="nntm-footer__copyright">
						<?php
						printf(

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
