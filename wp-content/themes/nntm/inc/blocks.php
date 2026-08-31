<?php

defined('ABSPATH') || exit;

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
 * Danh so hieu (version) cho CSS/JS cua block theo GIO SUA TEP.
 *
 * Vi sao can: WordPress lay so hieu cho tep cua block theo cong thuc trong
 * register_block_style_handle() —
 *
 *     SCRIPT_DEBUG bat  -> filemtime cua chinh tep
 *     SCRIPT_DEBUG tat  -> truong "version" trong block.json, khong co thi false
 *     false             -> WP_Styles dung tam phien ban WordPress (vd 7.0.3)
 *
 * May phat trien bat SCRIPT_DEBUG nen sua CSS la thay doi ngay. May chu that
 * thi tat, ma phan lon block.json cua theme khong khai "version", nen dia chi
 * tep luon la ...style.css?ver=7.0.3 — sua CSS xong day len, dia chi khong doi,
 * trinh duyet va CDN cu the ma dung ban cu. Dung la "sua roi ma khong nhan".
 *
 * Moc gio lay theo tep MOI NHAT trong thu muc block, nen doi bat ky tep nao
 * (style.css, view.js, render.php...) cung ra so hieu moi.
 *
 * Chi dong vao block cua theme nay; block cua WordPress va cua plugin giu
 * nguyen cach danh so cua ho.
 */
function nntm_block_so_hieu(array $metadata): array
{
	if (empty($metadata['file'])) {
		return $metadata;
	}

	$thu_muc = wp_normalize_path(dirname((string) $metadata['file']));
	$goc     = wp_normalize_path(NNTM_THEME_DIR . '/blocks');

	if (0 !== strpos($thu_muc, $goc)) {
		return $metadata;
	}

	$moc = 0;

	foreach (array('style.css', 'editor.css', 'view.js', 'editor.js', 'render.php', 'block.json') as $ten) {
		$duong_dan = $thu_muc . '/' . $ten;

		if (file_exists($duong_dan)) {
			$moc = max($moc, (int) filemtime($duong_dan));
		}
	}

	if ($moc > 0) {
		$metadata['version'] = (string) $moc;
	}

	return $metadata;
}
add_filter('block_type_metadata', 'nntm_block_so_hieu');

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
 * Cho phep vai thuoc tinh cua the <video> di qua wp_kses_post.
 *
 * Cac block render video bang chinh code cua theme roi loc lai qua wp_kses_post
 * cho an toan. Nhung danh sach mac dinh cua WordPress khong biet ba thuoc tinh
 * duoi day nen chung bi cat mat — trong khi day chinh la thu tat menu tai
 * xuong, tat cua so noi (Picture in Picture) va tat phat tu xa cho nhung video
 * chi dung de trinh bay hinh anh. Ca ba deu chi dieu khien giao dien phat, khong
 * mang duoc ma nao vao trang.
 */
function nntm_kses_cho_phep_thuoc_tinh_video(array $tags, $context): array
{
	if ('post' !== $context || ! isset($tags['video'])) {
		return $tags;
	}

	$tags['video']['controlslist']            = true;
	$tags['video']['disablepictureinpicture'] = true;
	$tags['video']['disableremoteplayback']   = true;

	return $tags;
}
add_filter('wp_kses_allowed_html', 'nntm_kses_cho_phep_thuoc_tinh_video', 10, 2);

function nntm_home_block_pattern_content(string $name, array $attributes = array()): string
{
	$encoded = empty($attributes)
		? ''
		: ' ' . wp_json_encode($attributes, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

	return '<!-- wp:' . $name . $encoded . ' /-->';
}

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
				'interval' => 5,
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
