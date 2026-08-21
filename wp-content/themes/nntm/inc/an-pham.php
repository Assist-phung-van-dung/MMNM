<?php
/**
 * Trang chi tiết ấn phẩm (CPT `nntm_publication`).
 *
 * @package NNTM
 */

defined( 'ABSPATH' ) || exit;

/**
 * Ấn phẩm này có bị khoá (yêu cầu thanh toán) không.
 * Trường `_nntm_pub_khoa` đặt qua meta box "Tệp PDF & Khoá xem", mặc định false.
 */
function nntm_an_pham_bi_khoa( $post = null ): bool {
	$post = get_post( $post );

	return $post ? (bool) get_post_meta( $post->ID, '_nntm_pub_khoa', true ) : false;
}

/**
 * Người đang xem đã thanh toán ấn phẩm khoá này chưa.
 * Chưa nối cổng thanh toán thật — luôn false, để filter riêng ghi đè sau khi
 * tính năng thanh toán được chốt (xem docs/07-ban-giao.md mục "Chờ khách trả lời").
 */
function nntm_an_pham_da_thanh_toan( $post = null ): bool {
	$post = get_post( $post );

	return (bool) apply_filters( 'nntm_an_pham_da_thanh_toan', false, $post, get_current_user_id() );
}

/**
 * Người đang xem có được đọc ấn phẩm này hay không.
 * Ấn phẩm không khoá → luôn mở. Ấn phẩm khoá → phải thanh toán trước.
 * Giữ filter cũ `nntm_an_pham_can_access` để nơi khác vẫn ghi đè được.
 */
function nntm_an_pham_can_access( $post = null ): bool {
	$post = get_post( $post );
	$mo   = ! nntm_an_pham_bi_khoa( $post ) || nntm_an_pham_da_thanh_toan( $post );

	return (bool) apply_filters( 'nntm_an_pham_can_access', $mo, $post, get_current_user_id() );
}

/**
 * URL công khai của tệp PDF gắn với ấn phẩm, rỗng nếu chưa gắn tệp.
 */
function nntm_an_pham_pdf_url( $post = null ): string {
	$post   = get_post( $post );
	$att_id = $post ? absint( get_post_meta( $post->ID, '_nntm_pdf_file', true ) ) : 0;

	if ( ! $att_id ) {
		return '';
	}

	$url = wp_get_attachment_url( $att_id );

	return $url ? $url : '';
}

/**
 * Nạp CSS/JS riêng cho single nntm_publication.
 */
function nntm_an_pham_enqueue_assets(): void {
	if ( ! is_singular( 'nntm_publication' ) ) {
		return;
	}

	$css_path = NNTM_THEME_DIR . '/assets/css/pages/an-pham.css';
	wp_enqueue_style(
		'nntm-an-pham',
		NNTM_THEME_URI . '/assets/css/pages/an-pham.css',
		array( 'nntm-tokens', 'nntm-base' ),
		nntm_asset_version( $css_path )
	);

	$js_path = NNTM_THEME_DIR . '/assets/js/an-pham.js';
	if ( is_readable( $js_path ) ) {
		wp_enqueue_script(
			'nntm-an-pham',
			NNTM_THEME_URI . '/assets/js/an-pham.js',
			array(),
			nntm_asset_version( $js_path ),
			true
		);
	}

	/*
	 * Nếu site đang có patch Yêu thích dùng chung thì publication cũng phải
	 * nạp asset của patch đó. Điều kiện file_exists giữ compatibility với
	 * source cũ chưa cài patch favorites.
	 */
	$favorites_css = NNTM_THEME_DIR . '/assets/css/favorites.css';
	$favorites_js  = NNTM_THEME_DIR . '/assets/js/favorites.js';
	if ( function_exists( 'nntm_section_render_favorite_button' ) && is_readable( $favorites_css ) && is_readable( $favorites_js ) ) {
		wp_enqueue_style(
			'nntm-favorites',
			NNTM_THEME_URI . '/assets/css/favorites.css',
			array( 'nntm-tokens', 'nntm-base' ),
			nntm_asset_version( $favorites_css )
		);

		wp_enqueue_script(
			'nntm-favorites',
			NNTM_THEME_URI . '/assets/js/favorites.js',
			array(),
			nntm_asset_version( $favorites_js ),
			true
		);

		wp_localize_script(
			'nntm-favorites',
			'nntmFavorites',
			array(
				'ajaxUrl'      => admin_url( 'admin-ajax.php' ),
				'nonce'        => wp_create_nonce( 'nntm_favorite_toggle' ),
				'isLoggedIn'   => is_user_logged_in(),
				'activeText'   => __( 'Đã yêu thích', 'nntm' ),
				'inactiveText' => __( 'Chưa yêu thích', 'nntm' ),
				'errorText'    => __( 'Không thể cập nhật Yêu thích. Vui lòng thử lại.', 'nntm' ),
			)
		);
	}

	/*
	 * Trình đọc 3D flip book — chỉ nạp khi ấn phẩm có tệp PDF gắn kèm VÀ
	 * người xem thực sự được đọc. Hai thư viện MIT/Apache miễn phí, tự lưu
	 * trong theme (không gọi CDN ngoài): pdf.js dựng ảnh từng trang, page-flip
	 * (StPageFlip) tạo hiệu ứng lật 3D trên các ảnh đó.
	 */
	$post     = get_post();
	$pdf_url  = nntm_an_pham_pdf_url( $post );
	$duoc_xem = nntm_an_pham_can_access( $post );

	if ( '' !== $pdf_url && $duoc_xem ) {
		$pdfjs_path = NNTM_THEME_DIR . '/assets/vendor/pdfjs/pdf.min.js';
		$flip_path  = NNTM_THEME_DIR . '/assets/vendor/page-flip/page-flip.browser.js';

		if ( is_readable( $pdfjs_path ) && is_readable( $flip_path ) ) {
			wp_enqueue_script(
				'nntm-vendor-pdfjs',
				NNTM_THEME_URI . '/assets/vendor/pdfjs/pdf.min.js',
				array(),
				nntm_asset_version( $pdfjs_path ),
				true
			);

			wp_enqueue_script(
				'nntm-vendor-page-flip',
				NNTM_THEME_URI . '/assets/vendor/page-flip/page-flip.browser.js',
				array(),
				nntm_asset_version( $flip_path ),
				true
			);

			$flipbook_js = NNTM_THEME_DIR . '/assets/js/an-pham-flipbook.js';
			wp_enqueue_script(
				'nntm-an-pham-flipbook',
				NNTM_THEME_URI . '/assets/js/an-pham-flipbook.js',
				array( 'nntm-vendor-pdfjs', 'nntm-vendor-page-flip' ),
				nntm_asset_version( $flipbook_js ),
				true
			);

			wp_localize_script(
				'nntm-an-pham-flipbook',
				'nntmAnPhamFlipbook',
				array(
					'pdfUrl'      => $pdf_url,
					'workerUrl'   => NNTM_THEME_URI . '/assets/vendor/pdfjs/pdf.worker.min.js',
					'title'       => get_the_title( $post ),
					'dangTai'     => __( 'Đang chuẩn bị sách…', 'nntm' ),
					'loi'         => __( 'Không mở được tệp PDF. Vui lòng thử lại.', 'nntm' ),
					'dong'        => __( 'Đóng', 'nntm' ),
					'trangTruoc'  => __( 'Trang trước', 'nntm' ),
					'trangSau'    => __( 'Trang sau', 'nntm' ),
				)
			);
		}
	}
}
add_action( 'wp_enqueue_scripts', 'nntm_an_pham_enqueue_assets', 30 );
