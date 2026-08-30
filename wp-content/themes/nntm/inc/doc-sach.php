<?php
/*
 * Trang đọc sách — phần HÌNH ẢNH.
 *
 * Phần nghiệp vụ đã chuyển sang plugin nntm-library:
 *   - đăng ký endpoint /doc/, nntm_dang_o_trang_doc(), nntm_doc_url()
 *     → includes/trang-doc.php
 *   - chặn quyền vào trang đọc, tự chuyển sang trang đọc
 *     → includes/trang-doc.php
 *   - bảng tiến độ đọc (wp_nntm_reading_progress)
 *     → includes/tien-do-doc.php
 *
 * VÌ SAO CHUYỂN: bảng tiến độ đọc do plugin nntm-core dựng nhưng lại bị theme
 * ghi thẳng bằng $wpdb->insert. Đổi theme là mất luôn chỗ đọc/ghi, dữ liệu nằm
 * đó mà không ai dùng. docs/04-kien-truc.md mục 1 chốt "dữ liệu và nghiệp vụ ở
 * plugin, hình ảnh ở theme".
 *
 * Ở lại đây: chọn template, nạp CSS/JS, gỡ giao diện chung, giấu thanh quản trị.
 *
 * Mọi hàm gọi sang plugin đều bọc function_exists(): tắt plugin thì trang đọc
 * mất tác dụng, nhưng site vẫn chạy chứ không trắng màn hình.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Đang ở trang đọc hay không — hỏi plugin, không tự đoán.
 *
 * Bọc lại thành một hàm riêng của theme để mỗi chỗ dùng khỏi phải tự kiểm tra
 * xem plugin có bật không.
 */
function nntm_theme_o_trang_doc(): bool {
	return function_exists( 'nntm_dang_o_trang_doc' ) && nntm_dang_o_trang_doc();
}

function nntm_doc_chon_template( string $template ): string {
	if ( ! nntm_theme_o_trang_doc() ) {
		return $template;
	}

	$rieng = NNTM_THEME_DIR . '/template-doc-sach.php';

	return is_readable( $rieng ) ? $rieng : $template;
}
add_filter( 'template_include', 'nntm_doc_chon_template' );

function nntm_doc_enqueue_assets(): void {
	if ( ! nntm_theme_o_trang_doc() ) {
		return;
	}

	$post = get_queried_object();

	if ( ! $post instanceof WP_Post ) {
		return;
	}

	/*
	 * Từ khi có kho riêng, hàm này trả về đường dẫn endpoint có kiểm quyền chứ
	 * không còn là URL thẳng tới tệp trong uploads. Nó cũng trả về chuỗi rỗng
	 * khi người xem chưa đủ quyền, nên không rò link ra HTML.
	 */
	$pdf_url = function_exists( 'nntm_an_pham_pdf_url' ) ? nntm_an_pham_pdf_url( $post ) : '';

	$css = NNTM_THEME_DIR . '/assets/css/pages/doc-sach.css';
	wp_enqueue_style(
		'nntm-doc-sach',
		NNTM_THEME_URI . '/assets/css/pages/doc-sach.css',
		array( 'nntm-tokens' ),
		nntm_asset_version( $css )
	);

	$pdfjs = NNTM_THEME_DIR . '/assets/vendor/pdfjs/pdf.min.js';
	$flip  = NNTM_THEME_DIR . '/assets/vendor/page-flip/page-flip.browser.js';
	$js    = NNTM_THEME_DIR . '/assets/js/doc-sach.js';

	$co_tep    = '' !== $pdf_url && is_readable( $pdfjs ) && is_readable( $flip );
	$phu_thuoc = array();

	if ( $co_tep ) {
		wp_enqueue_script( 'nntm-vendor-pdfjs', NNTM_THEME_URI . '/assets/vendor/pdfjs/pdf.min.js', array(), nntm_asset_version( $pdfjs ), true );
		wp_enqueue_script( 'nntm-vendor-page-flip', NNTM_THEME_URI . '/assets/vendor/page-flip/page-flip.browser.js', array(), nntm_asset_version( $flip ), true );

		$phu_thuoc = array( 'nntm-vendor-pdfjs', 'nntm-vendor-page-flip' );
	}

	wp_enqueue_script( 'nntm-doc-sach', NNTM_THEME_URI . '/assets/js/doc-sach.js', $phu_thuoc, nntm_asset_version( $js ), true );

	wp_localize_script(
		'nntm-doc-sach',
		'nntmDocSach',
		array(
			'pdfUrl'    => $co_tep ? $pdf_url : '',
			'workerUrl' => NNTM_THEME_URI . '/assets/vendor/pdfjs/pdf.worker.min.js',
			'objectId'  => $post->ID,
			'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
			'nonce'     => wp_create_nonce( 'nntm_doc_tien_do' ),
			'viTri'     => function_exists( 'nntm_doc_lay_vi_tri' ) ? nntm_doc_lay_vi_tri( $post->ID ) : 0,
			'dangNhap'  => is_user_logged_in(),
			/*
			 * Đang đọc bản xem thử hay bản đầy đủ. Trình đọc dùng cờ này để biết
			 * lúc nào cần báo "hết phần xem thử" cho khung thanh toán.
			 */
			'xemThu'    => function_exists( 'nntm_an_pham_che_do_doc' ) && 'xem-thu' === nntm_an_pham_che_do_doc( $post ),

			'watermark' => is_user_logged_in() ? wp_get_current_user()->display_name : '',
			'i18n'      => array(
				'dangTai'     => __( 'Đang mở sách…', 'nntm' ),
				'loi'         => __( 'Không mở được tệp. Tải lại trang giúp tôi.', 'nntm' ),
				'trang'       => __( 'Trang', 'nntm' ),
				'khongMucLuc' => __( 'Tệp này không có mục lục.', 'nntm' ),
				'trangAnh'    => __( 'Trang này là ảnh hoặc sơ đồ — đổi sang cách xem “Lật” để thấy đúng bản in.', 'nntm' ),
			),
		)
	);
}
add_action( 'wp_enqueue_scripts', 'nntm_doc_enqueue_assets', 5 );

function nntm_doc_dequeue_site_chrome(): void {
	if ( ! nntm_theme_o_trang_doc() ) {
		return;
	}

	foreach ( array( 'nntm-base', 'nntm-header', 'nntm-footer', 'nntm-layout', 'nntm-an-pham', 'nntm-search-bar', 'nntm-favorites' ) as $handle ) {
		wp_dequeue_style( $handle );
		wp_dequeue_script( $handle );
	}
}
add_action( 'wp_enqueue_scripts', 'nntm_doc_dequeue_site_chrome', 100 );

function nntm_doc_an_admin_bar( $hien ) {
	return nntm_theme_o_trang_doc() ? false : $hien;
}
add_filter( 'show_admin_bar', 'nntm_doc_an_admin_bar' );
