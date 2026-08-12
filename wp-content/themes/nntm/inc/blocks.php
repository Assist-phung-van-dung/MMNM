<?php

/**
 * Cơ chế đăng ký block riêng của theme.
 *
 * Chỉ dựng cơ chế quét thư mục blocks/ — CHƯA viết block cụ thể nào.
 * Mỗi block sau này chỉ cần thêm một thư mục con có block.json vào
 * blocks/ là tự được đăng ký, không cần sửa file này.
 */

defined('ABSPATH') || exit;

/**
 * Đăng ký category block riêng cho theme, đặt lên đầu danh sách.
 *
 * @param array $categories Danh sách category block hiện có.
 * @return array
 */
function nntm_block_categories(array $categories): array
{
	return array_merge(
		array(
			array(
				'slug'  => 'nntm',
				'title' => esc_html__('Nẵng Nhân Tịch Mặc', 'nntm'),
			),
		),
		$categories
	);
}
add_filter('block_categories_all', 'nntm_block_categories');

/**
 * Quét thư mục blocks/ và đăng ký mọi block có block.json.
 */
function nntm_register_blocks(): void
{
	$blocks_dir = NNTM_THEME_DIR . '/blocks';

	if (! is_dir($blocks_dir)) {
		return;
	}

	foreach (glob($blocks_dir . '/*', GLOB_ONLYDIR) as $block_dir) {
		if (file_exists($block_dir . '/block.json')) {
			register_block_type($block_dir);
		}
	}
}
add_action('init', 'nntm_register_blocks');

/**
 * Tao noi dung comment cua mot dynamic block de dung trong block pattern.
 *
 * @param string $name       Ten block, vi du nntm/hero-slider.
 * @param array  $attributes Thuoc tinh khoi tao.
 * @return string
 */
function nntm_home_block_pattern_content(string $name, array $attributes = array()): string
{
	$encoded = empty($attributes)
		? ''
		: ' ' . wp_json_encode($attributes, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

	return '<!-- wp:' . $name . $encoded . ' /-->';
}

/**
 * Dang ky tung section homepage thanh mot mau Gutenberg rieng.
 *
 * Nguoi quan tri co the chen lai section da xoa tu tab Mau (Patterns), sau
 * do chinh anh, video, noi dung va nguon bai trong sidebar cua chinh block.
 */
function nntm_register_homepage_patterns(): void
{
	if (! function_exists('register_block_pattern')) {
		return;
	}

	if (function_exists('register_block_pattern_category')) {
		register_block_pattern_category(
			'nntm-homepage',
			array('label' => esc_html__('NNTM - Trang chủ', 'nntm'))
		);
	}

	$patterns = array(
		'hero' => array(
			'title'       => __('01. Hero Slider', 'nntm'),
			'description' => __('Slider đầu trang: tự chọn ảnh, tiêu đề, mô tả và liên kết cho từng slide.', 'nntm'),
			'block'       => 'nntm/hero-slider',
			'attributes'  => array(
				'slides'   => array(
					array(
						'imageId'  => 0,
						'imageUrl' => '',
						'imageAlt' => '',
						'heading'  => 'Tiêu đề slide',
						'text'     => 'Nội dung mô tả slide.',
						'ctaLabel' => 'Xem thêm',
						'ctaUrl'   => '',
					),
				),
				'autoplay' => true,
				'interval' => 6,
			),
		),
		'featured-articles' => array(
			'title'       => __('02. Bài viết nổi bật', 'nntm'),
			'description' => __('Một bài lớn và năm bài phụ; chọn chuyên mục, thứ tự và nội dung hiển thị.', 'nntm'),
			'block'       => 'nntm/article-mosaic',
			'attributes'  => array(
				'heading'         => 'Chúng sanh tranh đấu và đau khổ do đâu?',
				'postType'        => 'post',
				'taxonomy'        => 'category',
				'leadMedia'       => 'tall',
				'secondaryLayout' => 'mosaic',
				'ctaLabel'        => 'Xem thêm',
				'viewAllLabel'    => 'Xem Tất cả',
			),
		),
		'video-carousel' => array(
			'title'       => __('03. Video Carousel', 'nntm'),
			'description' => __('Dải video kéo ngang; chọn series, số lượng, nền và chế độ tự chạy.', 'nntm'),
			'block'       => 'nntm/card-list',
			'attributes'  => array(
				'heading'      => 'Gót Son',
				'subheading'   => 'Xuyên Vạn Kiếp',
				'postType'     => 'nntm_video',
				'taxonomy'     => 'nntm_series',
				'variant'      => 'video',
				'layout'       => 'carousel',
				'postsPerPage' => 6,
			),
		),
		'feature' => array(
			'title'       => __('04. Nội dung và ảnh nổi bật', 'nntm'),
			'description' => __('Section hai cột cho phép nhập trực tiếp nhãn, tiêu đề, nội dung và ảnh.', 'nntm'),
			'block'       => 'nntm/feature',
			'attributes'  => array(
				'eyebrow'       => 'Video',
				'heading'       => 'Tiêu đề section',
				'content'       => 'Nhập nội dung section tại đây.',
				'mediaPosition' => 'left',
			),
		),
		'engineering-earth' => array(
			'title'       => __('05. Engineering Earth', 'nntm'),
			'description' => __('Section phim: chỉnh tiêu đề, ảnh lớn, chú thích và chọn video nổi.', 'nntm'),
			'block'       => 'nntm/engineering-earth',
			'attributes'  => array(),
		),
		'events' => array(
			'title'       => __('06. Hoạt động - Sự kiện', 'nntm'),
			'description' => __('Lưới bài sự kiện; chọn chuyên mục và tùy chỉnh nhãn Xem thêm/Xem tất cả.', 'nntm'),
			'block'       => 'nntm/article-mosaic',
			'attributes'  => array(
				'heading'         => 'Hoạt động - Sự kiện',
				'postType'        => 'post',
				'taxonomy'        => 'category',
				'leadMedia'       => 'short',
				'secondaryLayout' => 'grid',
				'showDate'        => false,
				'showCategory'    => false,
				'showExcerpt'     => true,
				'ctaLabel'        => 'Xem thêm',
				'viewAllLabel'    => 'Xem Tất cả',
			),
		),
		'gita' => array(
			'title'       => __('07. GITA Centre', 'nntm'),
			'description' => __('Carousel video nền cam với liên kết Spotify và YouTube.', 'nntm'),
			'block'       => 'nntm/card-list',
			'attributes'  => array(
				'heading'      => 'GITA CENTRE x NĂNG NHÂN TỊCH MẶC',
				'subheading'   => 'Sự kết hợp lan toả — âm nhạc Phật giáo đương đại, một làn gió mới.',
				'postType'     => 'nntm_video',
				'taxonomy'     => 'nntm_series',
				'variant'      => 'video',
				'layout'       => 'carousel',
				'postsPerPage' => 6,
				'background'   => 'cam',
				'showDate'     => false,
				'showCategory' => false,
			),
		),
	);

	foreach ($patterns as $slug => $pattern) {
		register_block_pattern(
			'nntm-homepage/' . $slug,
			array(
				'title'       => $pattern['title'],
				'description' => $pattern['description'],
				'categories'  => array('nntm-homepage'),
				'blockTypes'  => array($pattern['block']),
				'content'     => nntm_home_block_pattern_content($pattern['block'], $pattern['attributes']),
			),
		);
	}
}
add_action('init', 'nntm_register_homepage_patterns', 20);
