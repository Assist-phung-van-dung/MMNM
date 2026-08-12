<?php
/**
 * Thiết lập tính năng cơ bản của theme (after_setup_theme).
 */

defined( 'ABSPATH' ) || exit;

/**
 * Bật các tính năng lõi của theme.
 */
function nntm_setup(): void {
	// Nạp bản dịch cho text domain nntm (ưu tiên languages/ trong theme).
	load_theme_textdomain( 'nntm', NNTM_THEME_DIR . '/languages' );

	// Tự viết <title>, không dùng thẻ title cứng trong header.php.
	add_theme_support( 'title-tag' );

	// Ảnh đại diện bài viết.
	add_theme_support( 'post-thumbnails' );

	// Đánh dấu HTML5 cho các phần tử lõi.
	add_theme_support(
		'html5',
		array(
			'search-form',
			'comment-form',
			'comment-list',
			'gallery',
			'caption',
			'style',
			'script',
			'navigation-widgets',
		)
	);

	// Nhúng video/oEmbed co giãn theo khung chứa.
	add_theme_support( 'responsive-embeds' );

	// Trình soạn thảo dùng CSS riêng để hiển thị giống trang thật.
	add_theme_support( 'editor-styles' );

	// Cho phép block căn "rộng" (align wide) — khớp --nntm-w-rong trong tokens.
	add_theme_support( 'align-wide' );

	// Logo tùy biến, có dự phòng khi khách chưa đặt logo.
	add_theme_support(
		'custom-logo',
		array(
			'height'      => 80,
			'width'       => 240,
			'flex-height' => true,
			'flex-width'  => true,
		)
	);

	// Cho phép template part chọn qua block (theme lai: block + PHP template).
	add_theme_support( 'block-template-parts' );

	// Hai vị trí menu theo yêu cầu kiến trúc: menu chính và menu chân trang.
	register_nav_menus(
		array(
			'primary' => esc_html__( 'Menu chính', 'nntm' ),
			'footer'  => esc_html__( 'Menu chân trang', 'nntm' ),
		)
	);
}
add_action( 'after_setup_theme', 'nntm_setup' );

/**
 * Menu chân trang dự phòng khi ban quản trị chưa gán menu thật.
 *
 * Hiện đúng 3 mục như Figma để chân trang không trống hoác lúc mới dựng.
 * Chỉ trỏ tới trang đã tồn tại — trang chưa tạo thì bỏ qua, tránh dẫn
 * người đọc vào liên kết chết. Gán menu ở Giao diện → Menu là hàm này
 * tự nhường chỗ.
 */
function nntm_footer_menu_fallback(): void {
	$items = array(
		've-chung-toi' => esc_html__( 'Về chúng tôi', 'nntm' ),
		'lien-he'      => esc_html__( 'Liên hệ', 'nntm' ),
		'chinh-sach'   => esc_html__( 'Chính sách', 'nntm' ),
	);

	$links = array();
	foreach ( $items as $slug => $label ) {
		$page = get_page_by_path( $slug );
		if ( $page ) {
			$links[] = '<li><a href="' . esc_url( get_permalink( $page ) ) . '">' . esc_html( $label ) . '</a></li>';
		}
	}

	if ( ! $links ) {
		return;
	}

	echo '<ul class="nntm-footer-nav">' . implode( '', $links ) . '</ul>'; // phpcs:ignore WordPress.Security.EscapeOutput -- tung phan tu da escape o tren.
}

/**
 * Trang này đã tự có tiêu đề trong nội dung chưa?
 *
 * Các trang phân mục được ghép từ block của dự án, và mỗi block đã tự vẽ
 * tiêu đề section theo đúng Figma (ví dụ "Nguyên Thuỷ", "Trú Xứ", "Tông chỉ").
 * Nếu template in thêm tiêu đề trang nữa thì chữ hiện hai lần — Figma chỉ có
 * một. Vậy: hễ nội dung có block `nntm/*` mang thuộc tính `heading` không
 * rỗng thì template nhường, để block làm tiêu đề.
 *
 * Trang thường (Về chúng tôi, Liên hệ...) không có block nào như vậy nên
 * vẫn hiện tiêu đề như bình thường.
 *
 * @param WP_Post|null $post Bài cần kiểm tra.
 * @return bool
 */
function nntm_page_has_own_heading( ?WP_Post $post = null ): bool {
	$post = $post ?: get_post();
	if ( ! $post || ! has_blocks( $post ) ) {
		return false;
	}

	foreach ( parse_blocks( $post->post_content ) as $block ) {
		if ( empty( $block['blockName'] ) || 0 !== strpos( $block['blockName'], 'nntm/' ) ) {
			continue;
		}
		$heading = $block['attrs']['heading'] ?? '';
		if ( is_string( $heading ) && '' !== trim( wp_strip_all_tags( $heading ) ) ) {
			return true;
		}
	}

	return false;
}

/**
 * Trang này có được ghép từ block section của dự án không?
 *
 * Các block section (`nntm/*`) đã tự mang đệm ngoài đúng theo Figma
 * (ví dụ SECTION 1 của Hoa Khai có đệm ngang 70px). Nếu template lại bọc
 * chúng trong `.nntm-container` (rộng tối đa 1220 + đệm 40) thì thành đệm
 * chồng đệm: nội dung hẹp hơn thiết kế khoảng 200px trên mọi trang phân mục.
 *
 * Vậy: trang ghép từ block thì `<main>` để toàn chiều rộng, nhường việc
 * canh lề cho từng block — đúng như Figma. Trang thường vẫn dùng container.
 *
 * @param WP_Post|null $post Bài cần kiểm tra.
 * @return bool
 */
function nntm_page_uses_section_blocks( ?WP_Post $post = null ): bool {
	$post = $post ?: get_post();
	if ( ! $post || ! has_blocks( $post ) ) {
		return false;
	}

	foreach ( parse_blocks( $post->post_content ) as $block ) {
		if ( ! empty( $block['blockName'] ) && 0 === strpos( $block['blockName'], 'nntm/' ) ) {
			return true;
		}
	}

	return false;
}

/**
 * Trang này có mở đầu bằng một khối ảnh lớn tràn viền (hero) không?
 *
 * Figma R4 đặt HEADER ở `y=0` TRÙNG chỗ với BANNER (`y=0`, cao 768) — tức
 * đầu trang ĐÈ LÊN ảnh hero chứ không phải là một dải riêng đẩy ảnh xuống.
 * Đo trên node thật ngày 10/08/2026: HEADER `6376:6487` 1366x76 tại y=0,
 * BANNER `6376:6325` 1366x768 cũng tại y=0.
 *
 * VÌ SAO KHÔNG CHO ĐÈ Ở MỌI TRANG: trang không mở đầu bằng ảnh lớn (Diệu
 * Thượng, Pháp Toà, Về chúng tôi...) mà để header đè thì 76px đầu của nội
 * dung chui xuống dưới thanh menu và mất hút. Nên chỉ đè đúng những trang
 * có hero làm khối đầu tiên.
 *
 * Chỉ xét khối ĐẦU TIÊN có tên: hero nằm giữa trang thì không tính, vì
 * lúc đó phần đầu trang vẫn là nội dung thường.
 *
 * @param WP_Post|null $post Bài cần kiểm tra.
 * @return bool
 */
function nntm_page_starts_with_hero( ?WP_Post $post = null ): bool {
	$post = $post ?: get_post();
	if ( ! $post || ! has_blocks( $post ) ) {
		return false;
	}

	/**
	 * Danh sách block được coi là "ảnh lớn tràn viền đầu trang".
	 *
	 * Lọc qua filter để sau này thêm block hero mới thì khai ở một chỗ,
	 * không phải sửa hàm này.
	 *
	 * @param string[] $blocks Tên block.
	 */
	$hero_blocks = apply_filters(
		'nntm_hero_block_names',
		array( 'nntm/hero-slider', 'nntm/banner' )
	);

	foreach ( parse_blocks( $post->post_content ) as $block ) {
		if ( empty( $block['blockName'] ) ) {
			continue; // khoảng trắng giữa các block, bỏ qua
		}
		return in_array( $block['blockName'], $hero_blocks, true );
	}

	return false;
}

/**
 * Gắn class lên <body> để CSS biết đầu trang có phải đè lên hero không.
 *
 * Làm bằng class trên body thay vì viết thẳng vào header.php: header.php
 * là template dùng chung cho MỌI trang, quyết định bố cục phải nằm ở CSS
 * để đổi sau này không phải đụng vào PHP.
 *
 * @param string[] $classes Danh sách class.
 * @return string[]
 */
function nntm_body_class_hero( array $classes ): array {
	if ( ( is_page() || is_front_page() ) && nntm_page_starts_with_hero( get_queried_object() ) ) {
		$classes[] = 'nntm-dau-trang-de-len';
	}

	return $classes;
}
add_filter( 'body_class', 'nntm_body_class_hero' );
