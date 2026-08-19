<?php
/**
 * Template chân trang.
 *
 * Homepage bám theo Figma FOOTER node 6376:6324:
 * - lớp ngoài 1366px nền đen, padding 20px;
 * - panel #4F4F4F 1326px;
 * - hàng "Hãy chia sẻ / Ý kiến / của bạn";
 * - SUB gồm rule 1225px và hàng logo + menu / copyright 1226px.
 *
 * Các trang trong vẫn dùng cấu trúc footer gọn hiện có; wrapper bổ sung chỉ
 * được render trên homepage để không làm thay đổi layout ngoài scope này.
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
					<div class="nntm-footer__share-row">
						<div class="nntm-footer__share">
							<span><?php echo esc_html( $nntm_footer_en ? 'Share your' : 'Hãy chia sẻ' ); ?></span>
							<a class="nntm-footer__share-link" href="<?php echo esc_url( home_url( '/lien-he/' ) ); ?>">
								<?php echo esc_html( $nntm_footer_en ? 'Feedback' : 'Ý kiến' ); ?>
							</a>
							<span><?php echo esc_html( $nntm_footer_en ? 'with us' : 'của bạn' ); ?></span>
						</div>
					</div>

					<div class="nntm-footer__sub">
						<div class="nntm-footer__sub-content">
							<hr class="nntm-footer__rule">
				<?php endif; ?>

				<div class="nntm-footer__row">
					<?php if ( $nntm_is_home ) : ?>
						<div class="nntm-footer__left">
					<?php endif; ?>

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

					<?php if ( $nntm_is_home ) : ?>
						</div>
					<?php endif; ?>

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

				<?php if ( $nntm_is_home ) : ?>
						</div>
					</div>
				<?php endif; ?>
			</div>
		</div>
	</footer>
</div><!-- /.nntm-site-frame -->

<?php wp_footer(); ?>
</body>
</html>
