<?php
/**
 * Chân trang bản R1.
 *
 * Figma "DESKTOP - R1" / FOOTER 6027:4330 (1326x424), bóc 10/08/2026:
 *   FOOTER   nền #747766, bo 40
 *   Frame 97 1226x253  flex ngang, cách 90, SPACE_BETWEEN
 *     COL 1   504 chữ + biểu tượng chìm 183x232 #999D86
 *     COL 2   "Sitemap"        + 6 mục
 *     COL 3   "Liên kết nhanh" + 4 mục
 *   tiêu đề cột  #E9B9A5  Battambang 700 18/46
 *   đoạn văn     #FFFFFF  Baskerville 400 15/22   (-> Lora)
 *   mục          #FFFFFF  Battambang  400 16/30   (-> Be Vietnam Pro)
 *   SUB      1226x47  đường kẻ #F7F1DE 1px, hai đầu là bản quyền và
 *            "Điều khoản sử dụng | Chính sách bảo mật"
 *
 * Hai cột liên kết lấy từ menu khách tự quản (vị trí `primary` và
 * `footer`) chứ không viết cứng, để thêm bớt mục không cần lập trình viên.
 *
 * @package NNTM
 */

defined( 'ABSPATH' ) || exit;

/** Đoạn giới thiệu ở cột trái — mặc định lấy nguyên văn từ Figma. */
$nntm_r1_ft_doan = apply_filters(
	'nntm_r1_footer_doan',
	array(
		__( '“Nẵng Nhân Tịch Mặc” (能仁寂默) là một danh hiệu Hán dịch của Đức Phật Thích Ca Mâu Ni, chứa đựng ý nghĩa rất sâu sắc về lý tưởng của người giác ngộ.', 'nntm' ),
		__( 'Vì vậy, Nẵng Nhân Tịch Mặc có thể hiểu là:', 'nntm' ),
		__( '“Bậc có năng lực cứu độ muôn loài bằng lòng từ bi, đồng thời luôn an trú trong trí tuệ và sự tịch tĩnh.”', 'nntm' ),
	)
);
?>
<footer class="nntm-r1-footer">
	<div class="nntm-r1-footer__khung">

		<div class="nntm-r1-footer__tren">

			<div class="nntm-r1-footer__col nntm-r1-footer__col--gioi-thieu">
				<h2 class="nntm-r1-footer__tieu-de">
					<?php
					printf(
						/* translators: %s: ten site. */
						esc_html__( 'Giới thiệu về %s', 'nntm' ),
						esc_html( get_bloginfo( 'name' ) )
					);
					?>
				</h2>
				<div class="nntm-r1-footer__than">
					<?php foreach ( $nntm_r1_ft_doan as $nntm_r1_ft_p ) : ?>
						<p><?php echo esc_html( $nntm_r1_ft_p ); ?></p>
					<?php endforeach; ?>
				</div>

				<?php
				/*
				 * Biểu tượng chìm phía sau chữ (Vector 183x232 #999D86 trong
				 * Figma). Thuần trang trí -> aria-hidden.
				 */
				?>
				<svg class="nntm-r1-footer__dau-an" viewBox="0 0 39 50" aria-hidden="true" focusable="false">
					<path fill="currentColor" d="M19.5 0c2.4 4.6 6.9 6.5 11.2 6.1-.6 3.9-3 6.8-6.2 8.3 3.7.5 7.2-.9 9.6-3.5 1 4.2-.5 8-3.4 10.5 3.6-.6 6.5-2.8 8.3-5.9.3 5.5-2.9 10.2-7.7 12.2 2.9.6 5.8 0 8.2-1.6-1.7 5.4-6.4 9.2-12 9.8v3.9h6.1v2.6h-6.1V50h-2.6v-7.6h-6.1v-2.6h6.1v-3.9C9.4 35.3 4.7 31.5 3 26.1c2.4 1.6 5.3 2.2 8.2 1.6C6.4 25.7 3.2 21 3.5 15.5c1.8 3.1 4.7 5.3 8.3 5.9-2.9-2.5-4.4-6.3-3.4-10.5 2.4 2.6 5.9 4 9.6 3.5-3.2-1.5-5.6-4.4-6.2-8.3C16.1 6.5 20.6 4.6 19.5 0z"/>
				</svg>
			</div>

			<?php if ( has_nav_menu( 'primary' ) ) : ?>
				<nav class="nntm-r1-footer__col" aria-label="<?php esc_attr_e( 'Sơ đồ trang', 'nntm' ); ?>">
					<h2 class="nntm-r1-footer__tieu-de"><?php esc_html_e( 'Sitemap', 'nntm' ); ?></h2>
					<?php
					wp_nav_menu(
						array(
							'theme_location' => 'primary',
							'container'      => false,
							'menu_class'     => 'nntm-r1-footer__menu',
							'depth'          => 1,
							'fallback_cb'    => false,
						)
					);
					?>
				</nav>
			<?php endif; ?>

			<?php if ( has_nav_menu( 'footer' ) ) : ?>
				<nav class="nntm-r1-footer__col" aria-label="<?php esc_attr_e( 'Liên kết nhanh', 'nntm' ); ?>">
					<h2 class="nntm-r1-footer__tieu-de"><?php esc_html_e( 'Liên kết nhanh', 'nntm' ); ?></h2>
					<?php
					wp_nav_menu(
						array(
							'theme_location' => 'footer',
							'container'      => false,
							'menu_class'     => 'nntm-r1-footer__menu',
							'depth'          => 1,
							'fallback_cb'    => false,
						)
					);
					?>
				</nav>
			<?php endif; ?>

		</div>

		<div class="nntm-r1-footer__duoi">
			<p class="nntm-r1-footer__ban-quyen">
				<?php
				printf(
					/* translators: %1$s: nam hien tai, %2$s: ten site. */
					esc_html__( '© Bản quyền %1$s %2$s', 'nntm' ),
					esc_html( wp_date( 'Y' ) ),
					esc_html( get_bloginfo( 'name' ) )
				);
				?>
			</p>

			<p class="nntm-r1-footer__phap-ly">
				<?php
				$nntm_r1_ft_dieu_khoan = get_page_by_path( 'chinh-sach' );
				$nntm_r1_ft_bao_mat    = get_page_by_path( 'chinh-sach-bao-mat' );
				?>
				<?php if ( $nntm_r1_ft_dieu_khoan ) : ?>
					<a href="<?php echo esc_url( get_permalink( $nntm_r1_ft_dieu_khoan ) ); ?>"><?php esc_html_e( 'Điều khoản sử dụng', 'nntm' ); ?></a>
				<?php else : ?>
					<span><?php esc_html_e( 'Điều khoản sử dụng', 'nntm' ); ?></span>
				<?php endif; ?>
				<span aria-hidden="true">|</span>
				<?php if ( $nntm_r1_ft_bao_mat ) : ?>
					<a href="<?php echo esc_url( get_permalink( $nntm_r1_ft_bao_mat ) ); ?>"><?php esc_html_e( 'Chính sách bảo mật', 'nntm' ); ?></a>
				<?php else : ?>
					<span><?php esc_html_e( 'Chính sách bảo mật', 'nntm' ); ?></span>
				<?php endif; ?>
			</p>
		</div>

	</div>
</footer>
