<?php

defined( 'ABSPATH' ) || exit;

$nntm_r1_npg_page = get_page_by_path( 'nhap-phap-gioi' );
$nntm_r1_npg_url  = $nntm_r1_npg_page ? get_permalink( $nntm_r1_npg_page ) : home_url( '/' );
?>
<header class="nntm-r1-header">
	<div class="nntm-r1-header__bar">

		<a class="nntm-r1-header__logo" href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home">
			<?php
			 
			?>
			<svg class="nntm-r1-header__logo-mark" viewBox="0 0 39 50" role="img"
				aria-label="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>" focusable="false">
				<path fill="currentColor" d="M19.5 0c2.4 4.6 6.9 6.5 11.2 6.1-.6 3.9-3 6.8-6.2 8.3 3.7.5 7.2-.9 9.6-3.5 1 4.2-.5 8-3.4 10.5 3.6-.6 6.5-2.8 8.3-5.9.3 5.5-2.9 10.2-7.7 12.2 2.9.6 5.8 0 8.2-1.6-1.7 5.4-6.4 9.2-12 9.8v3.9h6.1v2.6h-6.1V50h-2.6v-7.6h-6.1v-2.6h6.1v-3.9C9.4 35.3 4.7 31.5 3 26.1c2.4 1.6 5.3 2.2 8.2 1.6C6.4 25.7 3.2 21 3.5 15.5c1.8 3.1 4.7 5.3 8.3 5.9-2.9-2.5-4.4-6.3-3.4-10.5 2.4 2.6 5.9 4 9.6 3.5-3.2-1.5-5.6-4.4-6.2-8.3C16.1 6.5 20.6 4.6 19.5 0z"/>
			</svg>
			<span class="nntm-sr-only"><?php echo esc_html( get_bloginfo( 'name' ) ); ?></span>
		</a>

		<nav class="nntm-r1-header__nav" aria-label="<?php esc_attr_e( 'Menu chính', 'nntm' ); ?>">
			<?php
			if ( has_nav_menu( 'primary' ) ) {
				wp_nav_menu(
					array(
						'theme_location' => 'primary',
						'container'      => false,
						'menu_class'     => 'nntm-r1-header__menu',
						'depth'          => 1,
						'fallback_cb'    => false,
					)
				);
			}
			?>

			<a class="nntm-r1-header__cta" href="<?php echo esc_url( $nntm_r1_npg_url ); ?>">
				<?php esc_html_e( 'Nhập Pháp Giới', 'nntm' ); ?>
			</a>

			<a class="nntm-r1-header__search" href="<?php echo esc_url( home_url( '/?s=' ) ); ?>">
				<span class="nntm-sr-only"><?php esc_html_e( 'Tìm kiếm', 'nntm' ); ?></span>
				<svg viewBox="0 0 22 22" aria-hidden="true" focusable="false">
					<circle cx="9.2" cy="9.2" r="7.6" fill="none" stroke="currentColor" stroke-width="2"/>
					<line x1="14.8" y1="14.8" x2="20.6" y2="20.6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
				</svg>
			</a>
		</nav>

	</div>
</header>
