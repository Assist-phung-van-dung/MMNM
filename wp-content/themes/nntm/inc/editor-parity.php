<?php
/**
 * Đồng bộ trình soạn thảo với trang thật.
 *
 * Vấn đề: trong admin nhiều block trông khác hẳn ngoài trang. Có ba nguyên nhân,
 * tệp này xử lý cả ba.
 *
 * 1. THIẾU CSS LÕI. Trước đây trình soạn thảo chỉ nạp tokens.css và base.css.
 *    Thiếu layout.css — nơi định nghĩa .nntm-container, khung giới hạn mà gần
 *    như mọi block đều nằm trong. Thiếu cả block-style.css (chiều rộng, màu chữ,
 *    font) và tokens.generated.css.
 *
 * 2. THIẾU CSS THEO TRANG. Ngoài trang, mỗi trang nạp thêm một tệp CSS riêng vẽ
 *    lại block cho đúng bản Figma — nặng nhất là homepage-figma.css với hơn 200
 *    quy tắc. Trình soạn thảo không nạp tệp nào trong số đó.
 *
 * 3. SAI PHẠM VI THÂN TRANG. Những tệp trên bọc quy tắc trong `.home`,
 *    `.single-nntm_article`... — là class của thẻ <body> ngoài trang. Thân của
 *    khung soạn thảo không có các class đó nên dù có nạp cũng không quy tắc nào
 *    khớp. Ở đây chúng được đổi sang `.editor-styles-wrapper` — class mà
 *    WordPress đặt trên thân khung soạn thảo — nên giữ nguyên độ ưu tiên
 *    (0,1,0), thứ tự tầng không đổi.
 *
 * CSS theo trang được nạp qua block_editor_settings_all thay vì add_editor_style
 * vì chỉ ở hook đó mới biết đang sửa bài nào; add_editor_style chạy từ
 * after_setup_theme, lúc chưa có ngữ cảnh gì.
 */

defined( 'ABSPATH' ) || exit;

/**
 * CSS luôn nạp vào trình soạn thảo, đúng thứ tự phụ thuộc như ngoài trang.
 *
 * Không có header.css / footer.css: đầu trang và chân trang không nằm trong
 * khung soạn thảo. Cũng không có responsive.css vì tệp đó chỉ chỉnh đầu/chân
 * trang và khung ngoài.
 *
 * @return string[] Đường dẫn tương đối so với thư mục theme.
 */
function nntm_editor_parity_css_chung(): array {
	return (array) apply_filters(
		'nntm_editor_parity_css_chung',
		array(
			'assets/css/tokens.css',
			'assets/css/tokens.generated.css',
			'assets/css/base.css',
			'assets/css/layout.css',
			'assets/css/block-style.css',
		)
	);
}

function nntm_editor_parity_dang_ky_css_chung(): void {
	foreach ( nntm_editor_parity_css_chung() as $duong_dan ) {
		if ( is_file( get_theme_file_path( $duong_dan ) ) ) {
			add_editor_style( $duong_dan );
		}
	}
}
add_action( 'after_setup_theme', 'nntm_editor_parity_dang_ky_css_chung' );

/**
 * Bản đồ CSS theo trang.
 *
 * Mỗi mục là một tệp CSS ngoài trang, kèm điều kiện nạp và danh sách selector
 * thân trang cần đổi phạm vi.
 *
 * Thứ tự trong mảng chính là thứ tự nạp, và phải khớp với thứ tự ưu tiên của
 * các hook wp_enqueue_scripts ngoài trang — riêng nhap-phap-gioi có hai tệp
 * (ưu tiên 30 rồi 63) nên thứ tự giữa chúng có ý nghĩa.
 *
 * 'than' chỉ liệt kê ĐÚNG những selector thân trang có thật trong tệp. Cố tình
 * không dùng mẫu chung kiểu `.page-*`: trong các tệp này còn có `.page-numbers`
 * — class của cụm phân trang, không phải class thân trang — đổi nhầm là hỏng.
 *
 * @return array<int, array{css: string, trang_chu?: bool, slug?: string[], post_type?: string[], than?: string[]}>
 */
function nntm_editor_parity_ban_do(): array {
	return (array) apply_filters(
		'nntm_editor_parity_ban_do',
		array(
			array(
				'css'       => 'assets/css/pages/nhap-phap-gioi.css',
				'slug'      => array( 'nhap-phap-gioi' ),
			),
			array(
				'css'       => 'assets/css/pages/article-detail.css',
				'post_type' => array( 'nntm_article' ),
				'than'      => array( '.single-nntm_article' ),
			),
			array(
				'css'       => 'assets/css/pages/homepage-figma.css',
				'trang_chu' => true,
				'than'      => array( '.home' ),
			),
			array(
				'css'       => 'assets/css/pages/dieu-thuong-figma.css',
				'slug'      => array( 'dieu-thuong' ),
			),
			array(
				'css'       => 'assets/css/pages/r1.css',
				'slug'      => array( 'r1' ),
				'than'      => array( 'body.nntm-r1' ),
			),
			/*
			 * Hai tep duoi day khong phai CSS cua trang, ma la phan bi tach ra
			 * khoi blocks/card-list/style.css de ngoai trang khong phai tai thu
			 * minh khong dung. Nhung trinh soan thao thi van phai co du, neu
			 * khong block Card List se vo ngay trong admin: chung chi duoc nap
			 * qua wp_enqueue_scripts, ma hook do khong chay trong wp-admin.
			 */
			array(
				'css'      => 'blocks/card-list/style-youtube.css',
				'kiem_tra' => 'nntm_editor_parity_co_bang_youtube',
			),
			array(
				'css'      => 'assets/css/pages/lien-dan-khoa-lich.css',
				'slug'     => array( 'lien-dan', 'vuon-xoai' ),
			),
			array(
				'css'       => 'assets/css/pages/lien-dan-figma.css',
				'slug'      => array( 'lien-dan' ),
			),
			array(
				'css'       => 'assets/css/pages/hoa-khai-figma.css',
				'slug'      => array( 'hoa-khai' ),
			),
			array(
				'css'       => 'assets/css/pages/ke-sach-an-pham.css',
				'slug'      => array( 'hoa-khai', 'nghi-quy' ),
			),
			array(
				'css'       => 'assets/css/pages/vuon-xoai-figma.css',
				'slug'      => array( 'vuon-xoai' ),
			),
			array(
				'css'       => 'assets/css/pages/nhap-phap-gioi-figma.css',
				'slug'      => array( 'nhap-phap-gioi' ),
			),
			array(
				'css'       => 'assets/css/pages/kim-cuong-hanh-gia-figma.css',
				'slug'      => array( 'kim-cuong-hanh-gia' ),
			),
		)
	);
}

/**
 * Bài đang sửa có Card List nào dùng băng chạy YouTube không.
 *
 * Dùng lại đúng hàm quét mà bản ngoài trang dùng (inc/enqueue.php), để hai bên
 * không thể lệch điều kiện nhau.
 */
function nntm_editor_parity_co_bang_youtube( WP_Post $post ): bool {
	if ( ! function_exists( 'nntm_card_list_co_bang_youtube' ) ) {
		return false;
	}

	return nntm_card_list_co_bang_youtube( parse_blocks( $post->post_content ) );
}

/**
 * Bài đang sửa có khớp điều kiện của một mục trong bản đồ không.
 */
function nntm_editor_parity_khop( array $muc, WP_Post $post ): bool {
	if ( ! empty( $muc['trang_chu'] ) ) {
		$id_trang_chu = (int) get_option( 'page_on_front' );

		return $id_trang_chu > 0 && $id_trang_chu === (int) $post->ID;
	}

	if ( ! empty( $muc['post_type'] ) ) {
		return in_array( $post->post_type, (array) $muc['post_type'], true );
	}

	if ( ! empty( $muc['slug'] ) ) {
		return 'page' === $post->post_type && in_array( $post->post_name, (array) $muc['slug'], true );
	}

	/*
	 * Điều kiện dạng hàm — dành cho tệp CSS không gắn với một trang cụ thể nào,
	 * mà gắn với việc bài đang sửa CÓ CHỨA một khối nhất định hay không.
	 */
	if ( ! empty( $muc['kiem_tra'] ) && is_callable( $muc['kiem_tra'] ) ) {
		return (bool) call_user_func( $muc['kiem_tra'], $post );
	}

	return false;
}

/**
 * Đổi selector thân trang sang class thân của khung soạn thảo.
 *
 * Chỉ đổi khi selector đứng độc lập: `.home` phải không được ăn vào `.homepage`
 * hay `.home-cu`, và cũng không được khớp phần đuôi của một class khác.
 */
function nntm_editor_parity_doi_pham_vi( string $css, array $than ): string {
	foreach ( $than as $chon ) {
		$chon = trim( $chon );

		if ( '' === $chon ) {
			continue;
		}

		$css = preg_replace(
			'/(?<![\w.#-])' . preg_quote( $chon, '/' ) . '(?![\w-])/',
			'.editor-styles-wrapper',
			$css
		);
	}

	return $css;
}

/**
 * Nạp CSS theo trang vào khung soạn thảo.
 *
 * baseURL để WordPress tự đổi mọi đường dẫn tương đối trong tệp (url(...) trỏ
 * tới ảnh, font) thành đường dẫn tuyệt đối — giống hệt cách core xử lý các tệp
 * khai báo qua add_editor_style.
 */
function nntm_editor_parity_them_css_theo_trang( array $settings, $context ): array {
	if ( ! is_object( $context ) || empty( $context->post ) || ! ( $context->post instanceof WP_Post ) ) {
		return $settings;
	}

	$post = $context->post;

	if ( ! isset( $settings['styles'] ) || ! is_array( $settings['styles'] ) ) {
		$settings['styles'] = array();
	}

	foreach ( nntm_editor_parity_ban_do() as $muc ) {
		if ( ! nntm_editor_parity_khop( $muc, $post ) ) {
			continue;
		}

		$duong_dan = get_theme_file_path( $muc['css'] );

		if ( ! is_file( $duong_dan ) ) {
			continue;
		}

		$css = file_get_contents( $duong_dan ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents

		if ( false === $css || '' === $css ) {
			continue;
		}

		if ( ! empty( $muc['than'] ) ) {
			$css = nntm_editor_parity_doi_pham_vi( $css, (array) $muc['than'] );
		}

		$settings['styles'][] = array(
			'css'            => $css,
			'baseURL'        => get_theme_file_uri( $muc['css'] ),
			'__unstableType' => 'theme',
			'isGlobalStyles' => false,
		);
	}

	return $settings;
}
add_filter( 'block_editor_settings_all', 'nntm_editor_parity_them_css_theo_trang', 10, 2 );

/**
 * Đo bề ngang khung soạn thảo, đưa vào biến --nntm-vw.
 *
 * Block đặt Full Width dùng calc(50% - var(--nntm-vw)/2) để trải hết bề ngang.
 * Ngoài trang, biến này do một đoạn script trong <head> đo. Trong admin thì
 * khung soạn thảo nằm trong một <iframe> riêng nên phải đo lại theo bề ngang
 * của chính khung đó, nếu không block full width trong admin sẽ rộng hơn khung
 * đúng bằng bề rộng thanh cuộn và sinh thanh cuộn ngang.
 */
function nntm_editor_parity_script(): void {
	$duong_dan = '/assets/js/editor/parity-vw.js';

	wp_enqueue_script(
		'nntm-editor-parity-vw',
		NNTM_THEME_URI . $duong_dan,
		array(),
		nntm_asset_version( NNTM_THEME_DIR . $duong_dan ),
		true
	);
}
add_action( 'enqueue_block_editor_assets', 'nntm_editor_parity_script' );
