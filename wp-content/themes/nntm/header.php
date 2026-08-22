<?php

defined( 'ABSPATH' ) || exit;

$nntm_logged_in   = is_user_logged_in();
$nntm_header_class = 'nntm-header ' . ( $nntm_logged_in ? 'nntm-header--auth' : 'nntm-header--guest' );

$nntm_has_hero = ( is_front_page() || is_page() )
	&& function_exists( 'nntm_page_starts_with_hero' )
	&& nntm_page_starts_with_hero( get_queried_object() );

$nntm_header_class .= $nntm_has_hero ? ' nntm-header--trong' : ' nntm-header--dac';

if ( apply_filters( 'nntm_header_sticky', true ) ) {
	$nntm_header_class .= ' nntm-header--sticky';
}

$nntm_nav_id   = 'nntm-primary-menu';
$nntm_panel_id = 'nntm-header-panel';
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>" />
	<meta name="viewport" content="width=device-width, initial-scale=1" />
	<link rel="profile" href="https://gmpg.org/xfn/11" />
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<a class="nntm-sr-only" href="#nntm-noi-dung-chinh"><?php esc_html_e( 'Bỏ qua tới nội dung chính', 'nntm' ); ?></a>

<div class="nntm-site-frame">
<header id="masthead" class="<?php echo esc_attr( $nntm_header_class ); ?>">
	<div class="nntm-header__bar">

		<div class="nntm-header__brand">
			<?php if ( has_custom_logo() ) : ?>
				<?php the_custom_logo(); ?>
			<?php else : ?>
				<a class="nntm-header__logo" href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home">
					<span class="nntm-header__logo-line"><?php esc_html_e( 'Năng Nhân', 'nntm' ); ?></span>
					<span class="nntm-header__logo-line"><?php esc_html_e( 'Tịch Mặc', 'nntm' ); ?></span>
				</a>
			<?php endif; ?>
		</div>

		<?php   ?>
		<button
			type="button"
			class="nntm-header__menu-toggle"
			aria-expanded="false"
			aria-controls="<?php echo esc_attr( $nntm_panel_id ); ?>"
		>
			<span class="nntm-sr-only"><?php esc_html_e( 'Mở menu chính', 'nntm' ); ?></span>
			<span class="nntm-header__menu-toggle-icon" aria-hidden="true"></span>
		</button>

		<?php

		?>
		<div class="nntm-header__panel" id="<?php echo esc_attr( $nntm_panel_id ); ?>">

			<?php   ?>
			<div class="nntm-header__panel-head">
				<span class="nntm-header__panel-title"><?php esc_html_e( 'Menu', 'nntm' ); ?></span>
				<button type="button" class="nntm-header__panel-close" data-nntm-menu-close>
					<span class="nntm-sr-only"><?php esc_html_e( 'Đóng menu', 'nntm' ); ?></span>
					<svg width="22" height="22" viewBox="0 0 22 22" fill="none" aria-hidden="true" focusable="false">
						<path d="M4 4l14 14M18 4L4 18" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
					</svg>
				</button>
			</div>

			<nav id="<?php echo esc_attr( $nntm_nav_id ); ?>" class="nntm-header__nav" aria-label="<?php esc_attr_e( 'Menu chính', 'nntm' ); ?>">
				<?php

				$nntm_hide_tin_tuc = static function ( $sorted_menu_items, $args ) {
					if ( empty( $args->theme_location ) || 'primary' !== $args->theme_location ) {
						return $sorted_menu_items;
					}

					return array_values(
						array_filter(
							$sorted_menu_items,
							static function ( $item ) {
								$title = wp_strip_all_tags( $item->title );
								$title = function_exists( 'mb_strtolower' )
									? mb_strtolower( trim( $title ), 'UTF-8' )
									: strtolower( trim( $title ) );
								return ! in_array( $title, array( 'tin tức', 'news' ), true );
							}
						)
					);
				};

				if ( ! $nntm_logged_in ) {
					add_filter( 'wp_nav_menu_objects', $nntm_hide_tin_tuc, 10, 2 );
				}

				wp_nav_menu(
					array(
						'theme_location' => 'primary',
						'container'      => false,
						'menu_class'     => 'nntm-main-nav',
						'fallback_cb'    => false,
						'depth'          => 2,
					)
				);

				if ( ! $nntm_logged_in ) {
					remove_filter( 'wp_nav_menu_objects', $nntm_hide_tin_tuc, 10 );
				}
				?>
			</nav>

			<div class="nntm-header__tools">

				<?php

				$nntm_search_id = wp_unique_id( 'nntm-header-search-' );
				?>
				<form role="search" method="get" class="nntm-header__search-form" action="<?php echo esc_url( home_url( '/' ) ); ?>">
					<label for="<?php echo esc_attr( $nntm_search_id ); ?>" class="nntm-sr-only">
						<?php esc_html_e( 'Tìm kiếm', 'nntm' ); ?>
					</label>
					<button type="submit" class="nntm-header__search-submit">
						<span class="nntm-sr-only"><?php esc_html_e( 'Tìm kiếm', 'nntm' ); ?></span>
						<svg width="25" height="25" viewBox="0 0 25 25" fill="none" aria-hidden="true" focusable="false">
							<circle cx="10.5" cy="10.5" r="7" stroke="currentColor" stroke-width="2" />
							<line x1="15.6" y1="15.6" x2="21" y2="21" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
						</svg>
					</button>
					<input
						type="search"
						id="<?php echo esc_attr( $nntm_search_id ); ?>"
						class="nntm-header__search-field"
						name="s"
						placeholder="<?php esc_attr_e( 'Nhập từ khoá', 'nntm' ); ?>"
						value="<?php echo esc_attr( get_search_query() ); ?>"
					/>

					<?php

					?>
					<button type="button" class="nntm-header__search-camera" hidden>
						<span class="nntm-sr-only"><?php esc_html_e( 'Tìm bằng hình ảnh', 'nntm' ); ?></span>
						<svg width="20" height="20" viewBox="0 0 20 20" fill="none" aria-hidden="true" focusable="false">
							<rect x="1.5" y="5" width="17" height="12.5" rx="2.5" stroke="currentColor" stroke-width="1.6" />
							<circle cx="10" cy="11.25" r="3.5" stroke="currentColor" stroke-width="1.6" />
							<path d="M7 5l1.2-2h3.6L13 5" stroke="currentColor" stroke-width="1.6" stroke-linejoin="round" />
						</svg>
					</button>
					<input type="file" class="nntm-header__search-file" accept="image/jpeg,image/png,image/webp,image/gif" hidden />
				</form>

				<?php if ( $nntm_logged_in ) : ?>

					<?php
					$nntm_current_user  = wp_get_current_user();
					$nntm_rank          = nntm_user_rank( $nntm_current_user->ID );

					$nntm_account_url   = apply_filters( 'nntm_account_page_url', home_url( '/tai-khoan/' ) );
					$nntm_favorites_url = apply_filters( 'nntm_account_favorites_url', home_url( '/yeu-thich/' ) );
					$nntm_logout_url    = wp_logout_url( home_url( '/' ) );
					?>
					<div class="nntm-header__account">
						<button
							type="button"
							class="nntm-header__account-toggle"
							aria-haspopup="true"
							aria-expanded="false"
							aria-controls="nntm-account-panel"
						>
							<span class="nntm-sr-only"><?php esc_html_e( 'Mở menu tài khoản', 'nntm' ); ?></span>
							<svg class="nntm-header__account-icon" width="25" height="25" viewBox="0 0 25 25" fill="none" aria-hidden="true" focusable="false">
								<circle cx="12.5" cy="8" r="4.5" stroke="currentColor" stroke-width="2" />
								<path d="M4 21c1.5-4.5 5-6.5 8.5-6.5S19.5 16.5 21 21" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
							</svg>
						</button>

						<div class="nntm-header__account-panel" id="nntm-account-panel" hidden>
							<p class="nntm-header__account-name">
								<?php

								echo esc_html( sprintf( __( 'Xin chào, %s', 'nntm' ), $nntm_current_user->display_name ) );
								?>
							</p>
							<?php if ( 'kim_cuong' === $nntm_rank ) : ?>
								<p class="nntm-header__account-rank"><?php esc_html_e( 'Kim Cương Hành Giả', 'nntm' ); ?></p>
							<?php elseif ( 'dai_si' === $nntm_rank ) : ?>
								<p class="nntm-header__account-rank"><?php esc_html_e( 'Đại Sĩ', 'nntm' ); ?></p>
							<?php endif; ?>
							<ul class="nntm-header__account-menu">
								<li><a href="<?php echo esc_url( $nntm_account_url ); ?>"><?php esc_html_e( 'Trang tài khoản', 'nntm' ); ?></a></li>
								<li><a href="<?php echo esc_url( $nntm_favorites_url ); ?>"><?php esc_html_e( 'Yêu thích', 'nntm' ); ?></a></li>
								<li><a href="<?php echo esc_url( $nntm_logout_url ); ?>"><?php esc_html_e( 'Đăng xuất', 'nntm' ); ?></a></li>
							</ul>
						</div>
					</div>

				<?php else : ?>

					<?php
					$nntm_login_redirect = is_singular() ? get_permalink() : home_url( '/' );
					$nntm_header_login_url = function_exists( 'nntm_login_url' )
						? nntm_login_url( $nntm_login_redirect ?: home_url( '/' ) )
						: wp_login_url( $nntm_login_redirect ?: home_url( '/' ) );
					?>
					<a
						href="<?php echo esc_url( $nntm_header_login_url ); ?>"
						class="nntm-header__login"
						data-nntm-auth-modal="dang-nhap"
						data-nntm-auth-redirect="<?php echo esc_url( $nntm_login_redirect ?: home_url( '/' ) ); ?>"
					>
						<?php esc_html_e( 'Đăng nhập', 'nntm' ); ?>
					</a>

				<?php endif; ?>

				<?php

				?>
				<div class="nntm-header__lang" id="nntm-lang-switch">
					<?php nntm_render_language_switcher(); ?>
				</div>

			</div><!-- /.nntm-header__tools -->

		</div><!-- /.nntm-header__panel -->

	</div>

	<?php

	?>
	<div class="nntm-header__scrim" data-nntm-menu-close aria-hidden="true"></div>
</header>
