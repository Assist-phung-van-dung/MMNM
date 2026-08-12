<?php
/**
 * Template phần đầu trang — logo, menu chính, ngôn ngữ, tìm kiếm, tài khoản.
 *
 * Dựng lại theo số đo THẬT bóc từ Figma (đã được chủ dự án xác nhận —
 * xem chỉ đạo trong phiên làm việc; KHÔNG gọi lại Figma API vì đang bị
 * rate-limit). Ba trạng thái:
 *
 *   A — CHƯA ĐĂNG NHẬP  (.nntm-header--guest) : nền trắng, menu 6 mục,
 *       màu chữ --nntm-muc-nhat, khu phải có ô tìm kiếm + "Đăng nhập" + ngôn ngữ.
 *   B — ĐÃ ĐĂNG NHẬP    (.nntm-header--auth)  : nền trắng 60% + làm mờ nền,
 *       menu 7 mục (thêm "Tin Tức"), màu chữ --nntm-chinh, khu phải chỉ có
 *       icon tài khoản + ngôn ngữ.
 *   C — DÍNH TRÊN CÙNG KHI CUỘN (.nntm-header--sticky, cộng .nntm-header--stuck
 *       do header.js gắn qua IntersectionObserver): giữ nguyên nền màu của
 *       trạng thái A/B (không có nền "trong suốt" nào cả), chỉ thêm bóng nhẹ.
 *
 * Mục menu đang xem hiển thị dạng viên thuốc qua class current-menu-item /
 * current_page_item mà WordPress tự gắn — xem header.css.
 */

defined( 'ABSPATH' ) || exit;

$nntm_logged_in   = is_user_logged_in();
$nntm_header_class = 'nntm-header ' . ( $nntm_logged_in ? 'nntm-header--auth' : 'nntm-header--guest' );

if ( apply_filters( 'nntm_header_sticky', true ) ) {
	$nntm_header_class .= ' nntm-header--sticky';
}

$nntm_nav_id = 'nntm-primary-menu';
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

		<?php /*
		SUY DOAN: chua co Figma mobile — nut hamburger mo/dong menu chinh tren
		man hinh nho. Figma chi co khung desktop 1366px nen khong the biet
		hinh dang that cua nut nay tren mobile; hanh vi la tu dung hoan toan.
		*/ ?>
		<button
			type="button"
			class="nntm-header__menu-toggle"
			aria-expanded="false"
			aria-controls="<?php echo esc_attr( $nntm_nav_id ); ?>"
		>
			<span class="nntm-sr-only"><?php esc_html_e( 'Mở menu chính', 'nntm' ); ?></span>
			<span class="nntm-header__menu-toggle-icon" aria-hidden="true"></span>
		</button>

		<nav id="<?php echo esc_attr( $nntm_nav_id ); ?>" class="nntm-header__nav" aria-label="<?php esc_attr_e( 'Menu chính', 'nntm' ); ?>">
			<?php
			/*
			 * Mục "Tin Tức" chỉ hiện khi đã đăng nhập (trạng thái B có 7 mục,
			 * trạng thái A có 6 mục — xem docs/04-kien-truc.md). Nhận diện
			 * bằng TÊN MỤC: ban quản trị đặt tên mục trong Giao diện → Menu
			 * đúng là "Tin Tức" (không phân biệt hoa/thường, tự bỏ khoảng
			 * trắng thừa hai đầu) thì mục sẽ tự ẩn với khách chưa đăng nhập.
			 * Đổi tên mục khác đi thì mục KHÔNG còn được lọc và sẽ hiện với
			 * mọi người — đây là điều cần lưu ý khi bàn giao cho ban quản trị.
			 * Chỉ áp filter đúng lúc gọi wp_nav_menu() rồi gỡ ngay, để không
			 * ảnh hưởng menu "footer" hay bất kỳ chỗ gọi wp_nav_menu nào khác.
			 */
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
							return 'tin tức' !== $title;
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

			<?php if ( $nntm_logged_in ) : ?>

				<?php
				$nntm_current_user  = wp_get_current_user();
				$nntm_rank          = nntm_user_rank( $nntm_current_user->ID );
				/*
				 * Trang "Yêu thích" chưa có slug cố định trong dự án (mới có
				 * bảng dữ liệu wp_nntm_favorites, xem class-schema.php) —
				 * SUY DOAN tạm dùng /yeu-thich/, lọc qua filter để ban quản
				 * trị hoặc lập trình viên sau này trỏ đúng trang khi có.
				 */
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
							/* translators: %s: tên hiển thị của thành viên đang đăng nhập. */
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

				<?php
				/*
				 * Nút chuyển ngôn ngữ — CHỈ HIỆN MỘT NÚT.
				 *
				 * Quyết định của anh Úy 10/08/2026, khớp với Figma R4 (bản
				 * chưa đăng nhập chỉ có một nút EN): nút hiển thị là ngôn
				 * ngữ SẼ CHUYỂN SANG, không phải ngôn ngữ đang xem. Mặc
				 * định site chạy tiếng Việt nên nút hiện "EN".
				 *
				 * Suy từ locale thật thay vì viết cứng, để khi bật Polylang
				 * và người đọc đang ở bản tiếng Anh thì nút tự đổi thành
				 * "VI". Polylang sau này thay cả khối này bằng
				 * pll_the_languages().
				 */
				$nntm_dang_tieng_viet = ( 0 === strpos( get_locale(), 'vi' ) );
				?>
				<div class="nntm-header__lang" id="nntm-lang-switch">
					<span class="nntm-lang-btn nntm-lang-btn--active">
						<?php echo $nntm_dang_tieng_viet ? esc_html__( 'EN', 'nntm' ) : esc_html__( 'VI', 'nntm' ); ?>
					</span>
				</div>

			<?php else : ?>

				<form role="search" method="get" class="nntm-header__search-form" action="<?php echo esc_url( home_url( '/' ) ); ?>">
					<label for="<?php echo esc_attr( wp_unique_id( 'nntm-header-search-' ) ); ?>" class="nntm-sr-only">
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
						class="nntm-header__search-field"
						name="s"
						placeholder="<?php esc_attr_e( 'Nhập từ khoá', 'nntm' ); ?>"
						value="<?php echo esc_attr( get_search_query() ); ?>"
					/>
				</form>

				<a href="<?php echo esc_url( wp_login_url( get_permalink() ?: home_url( '/' ) ) ); ?>" class="nntm-header__login">
					<?php esc_html_e( 'Đăng nhập', 'nntm' ); ?>
				</a>

				<div class="nntm-header__lang" id="nntm-lang-switch">
					<?php
					// Chi hien MOT nut, la ngon ngu se chuyen sang — xem chu
					// thich day du o khoi ngon ngu phia tren trong file nay.
					$nntm_dang_tieng_viet = ( 0 === strpos( get_locale(), 'vi' ) );
					?>
					<span class="nntm-lang-btn nntm-lang-btn--active">
						<?php echo $nntm_dang_tieng_viet ? esc_html__( 'EN', 'nntm' ) : esc_html__( 'VI', 'nntm' ); ?>
					</span>
				</div>

			<?php endif; ?>

		</div>

	</div>
</header>
