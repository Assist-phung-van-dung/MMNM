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
 *       menu 7 mục (thêm "Tin Tức"), màu chữ --nntm-chinh, khu phải có ô tìm
 *       kiếm + icon tài khoản + ngôn ngữ.
 *
 *   SỬA 17/08/2026: trước đây ô tìm kiếm CHỈ nằm trong nhánh A (Figma có
 *       component riêng cho trạng thái B không vẽ ô tìm kiếm) — chủ dự án
 *       xác nhận đây là thiếu sót, không phải cố ý: thành viên đăng nhập là
 *       người xem được nhiều nội dung nhất nên càng cần chỗ để tìm. Ô tìm
 *       kiếm chuyển ra ngoài if/else, dùng chung cho cả hai trạng thái.
 *   C — DÍNH TRÊN CÙNG KHI CUỘN (.nntm-header--sticky, cộng .nntm-header--stuck
 *       do header.js gắn qua IntersectionObserver): giữ nguyên nền màu của
 *       trạng thái A/B (không có nền "trong suốt" nào cả), chỉ thêm bóng nhẹ.
 *
 * CHỈ ĐẠO MỚI 12/08/2026 (spec-trang-chu.md, mục H1) — chồng thêm lên ba
 * trạng thái trên, không thay thế:
 *
 *   .nntm-header--trong : ở đỉnh trang, CHỈ gắn cho trang có banner ảnh
 *       ngay dưới đầu trang (dùng lại nntm_page_starts_with_hero(), cùng
 *       hàm mà inc/setup.php dùng để gắn class nntm-dau-trang-de-len lên
 *       <body>). Nền trong suốt, đè lên banner, chữ/icon không có nền
 *       riêng (menu, logo, "Đăng nhập", icon tài khoản) đổi sang màu sáng.
 *   .nntm-header--dac    : nền trắng đặc, chữ theo đúng màu trạng thái A/B
 *       như cũ. Trang KHÔNG có banner nhận thẳng class này, không cần JS.
 *
 * assets/js/header-scroll.js đổi qua lại hai class này bằng
 * IntersectionObserver khi cuộn quá 80px — chỉ hoạt động nếu header bắt
 * đầu ở .nntm-header--trong; trang không có banner thì script tự bỏ qua.
 *
 * Mục menu đang xem hiển thị dạng viên thuốc qua class current-menu-item /
 * current_page_item mà WordPress tự gắn — xem header.css.
 */

defined( 'ABSPATH' ) || exit;

$nntm_logged_in   = is_user_logged_in();
$nntm_header_class = 'nntm-header ' . ( $nntm_logged_in ? 'nntm-header--auth' : 'nntm-header--guest' );

/*
 * H1: trạng thái trong suốt chỉ áp cho trang mở đầu bằng banner ảnh ngay
 * dưới đầu trang (tái dùng nntm_page_starts_with_hero(), đã có sẵn trong
 * inc/setup.php cho đúng mục đích này — không viết hàm mới trùng việc).
 * Trang thường (không hero) nhận .nntm-header--dac ngay, trắng từ đầu.
 */
$nntm_has_hero = ( is_front_page() || is_page() )
	&& function_exists( 'nntm_page_starts_with_hero' )
	&& nntm_page_starts_with_hero( get_queried_object() );

$nntm_header_class .= $nntm_has_hero ? ' nntm-header--trong' : ' nntm-header--dac';

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
			/*
			 * SỬA 17/08/2026: ô tìm kiếm giờ hiện ở CẢ HAI trạng thái đăng
			 * nhập/chưa đăng nhập — trước đây chỉ nằm trong nhánh "chưa đăng
			 * nhập" nên thành viên đăng nhập vào thì KHÔNG có chỗ nào để tìm,
			 * dù họ là người xem được nhiều nội dung nhất. Figma có component
			 * riêng cho trạng thái đã đăng nhập không vẽ ô tìm kiếm (từng nghi
			 * là cố ý, xem docs/10-ban-giao-tim-kiem.md mục 10.5) — chủ dự án
			 * đã xác nhận đây là thiếu sót cần thêm, không phải thiết kế cố ý.
			 */
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
				/*
				 * Tìm bằng hình ảnh. Nút và ô chọn file để ẨN SẴN trong HTML
				 * chứ không do JavaScript sinh ra, để không nhấp nháy lúc tải
				 * trang. Plugin nntm-search bật thì gỡ thuộc tính hidden;
				 * plugin tắt thì cả hai vô hình và form vẫn chạy như cũ —
				 * theme không phụ thuộc plugin.
				 */
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

			<?php else : ?>

				<a href="<?php echo esc_url( wp_login_url( get_permalink() ?: home_url( '/' ) ) ); ?>" class="nntm-header__login">
					<?php esc_html_e( 'Đăng nhập', 'nntm' ); ?>
				</a>

			<?php endif; ?>

			<?php
			/* Polylang: hiển thị đủ VN/EN và đánh dấu ngôn ngữ hiện tại. */
			?>
			<div class="nntm-header__lang" id="nntm-lang-switch">
				<?php nntm_render_language_switcher(); ?>
			</div>

		</div>

	</div>
</header>
