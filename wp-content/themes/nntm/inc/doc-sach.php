<?php

defined( 'ABSPATH' ) || exit;

const NNTM_DOC_ENDPOINT = 'doc';

const NNTM_DOC_REWRITE_VERSION = '1';

function nntm_doc_dang_ky_endpoint(): void {
	add_rewrite_endpoint( NNTM_DOC_ENDPOINT, EP_PERMALINK );

	if ( NNTM_DOC_REWRITE_VERSION !== get_option( 'nntm_doc_rewrite_version' ) ) {
		flush_rewrite_rules();
		update_option( 'nntm_doc_rewrite_version', NNTM_DOC_REWRITE_VERSION );
	}
}
add_action( 'init', 'nntm_doc_dang_ky_endpoint' );

function nntm_dang_o_trang_doc(): bool {
	global $wp_query;

	if ( ! is_singular( 'nntm_publication' ) ) {
		return false;
	}

	return isset( $wp_query->query_vars[ NNTM_DOC_ENDPOINT ] );
}

function nntm_doc_url( $post = null ): string {
	$post = get_post( $post );

	if ( ! $post ) {
		return '';
	}

	return trailingslashit( trailingslashit( (string) get_permalink( $post ) ) . NNTM_DOC_ENDPOINT );
}

function nntm_doc_chan_quyen(): void {
	if ( ! nntm_dang_o_trang_doc() ) {
		return;
	}

	$post = get_queried_object();

	if ( ! $post instanceof WP_Post ) {
		return;
	}

	$chi_tiet = (string) get_permalink( $post );

	if ( nntm_an_pham_can_access( $post ) ) {
		return;
	}

	if ( ! is_user_logged_in() ) {
		$dich = function_exists( 'nntm_login_url' )
			? nntm_login_url( nntm_doc_url( $post ) )
			: wp_login_url( nntm_doc_url( $post ) );

		wp_safe_redirect( $dich );
		exit;
	}

	wp_safe_redirect( $chi_tiet );
	exit;
}
add_action( 'template_redirect', 'nntm_doc_chan_quyen', 5 );

function nntm_an_pham_chuyen_sang_trang_doc(): void {
	if ( is_admin() || ! is_singular( 'nntm_publication' ) ) {
		return;
	}

	if ( nntm_dang_o_trang_doc() ) {
		return;
	}

	if ( is_feed() || is_embed() || is_preview() || is_customize_preview() ) {
		return;
	}

	if ( isset( $_GET['chi-tiet'] ) ) {  
		return;
	}

	$post = get_queried_object();

	if ( ! $post instanceof WP_Post ) {
		return;
	}

	if ( ! apply_filters( 'nntm_an_pham_tu_chuyen_sang_doc', true, $post ) ) {
		return;
	}

	if ( ! nntm_an_pham_can_access( $post ) ) {
		return;
	}

	$dich = nntm_doc_url( $post );

	if ( '' === $dich ) {
		return;
	}

	wp_safe_redirect( $dich, 302 );
	exit;
}
add_action( 'template_redirect', 'nntm_an_pham_chuyen_sang_trang_doc', 6 );

function nntm_doc_chon_template( string $template ): string {
	if ( ! nntm_dang_o_trang_doc() ) {
		return $template;
	}

	$rieng = NNTM_THEME_DIR . '/template-doc-sach.php';

	return is_readable( $rieng ) ? $rieng : $template;
}
add_filter( 'template_include', 'nntm_doc_chon_template' );

function nntm_doc_enqueue_assets(): void {
	if ( ! nntm_dang_o_trang_doc() ) {
		return;
	}

	$post = get_queried_object();

	if ( ! $post instanceof WP_Post ) {
		return;
	}

	$pdf_url = nntm_an_pham_pdf_url( $post );

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
			'viTri'     => nntm_doc_lay_vi_tri( $post->ID ),
			'dangNhap'  => is_user_logged_in(),
			 
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
	if ( ! nntm_dang_o_trang_doc() ) {
		return;
	}

	foreach ( array( 'nntm-base', 'nntm-header', 'nntm-footer', 'nntm-layout', 'nntm-an-pham', 'nntm-search-bar', 'nntm-favorites' ) as $handle ) {
		wp_dequeue_style( $handle );
		wp_dequeue_script( $handle );
	}
}
add_action( 'wp_enqueue_scripts', 'nntm_doc_dequeue_site_chrome', 100 );

function nntm_doc_an_admin_bar( $hien ) {
	return nntm_dang_o_trang_doc() ? false : $hien;
}
add_filter( 'show_admin_bar', 'nntm_doc_an_admin_bar' );

 
function nntm_doc_bang_tien_do(): string {
	global $wpdb;

	return $wpdb->prefix . 'nntm_reading_progress';
}

function nntm_doc_lay_vi_tri( int $object_id ): int {
	global $wpdb;

	if ( get_current_user_id() <= 0 ) {
		return 0;
	}

	$bang = nntm_doc_bang_tien_do();

	return (int) $wpdb->get_var(
		$wpdb->prepare(
			"SELECT position FROM {$bang} WHERE user_id = %d AND object_id = %d AND object_type = %s LIMIT 1",
			get_current_user_id(),
			$object_id,
			'publication'
		)
	);
}

function nntm_doc_ajax_luu_tien_do(): void {
	check_ajax_referer( 'nntm_doc_tien_do', 'nonce' );

	if ( ! is_user_logged_in() ) {
		wp_send_json_error( array( 'message' => __( 'Chưa đăng nhập.', 'nntm' ) ), 403 );
	}

	$object_id = isset( $_POST['object_id'] ) ? absint( wp_unslash( $_POST['object_id'] ) ) : 0;
	$trang     = isset( $_POST['trang'] ) ? absint( wp_unslash( $_POST['trang'] ) ) : 0;

	if ( $object_id <= 0 || $trang <= 0 || 'nntm_publication' !== get_post_type( $object_id ) ) {
		wp_send_json_error( array( 'message' => __( 'Dữ liệu không hợp lệ.', 'nntm' ) ), 400 );
	}

	if ( ! nntm_an_pham_can_access( $object_id ) ) {
		wp_send_json_error( array( 'message' => __( 'Không có quyền.', 'nntm' ) ), 403 );
	}

	global $wpdb;

	$bang    = nntm_doc_bang_tien_do();
	$user_id = get_current_user_id();

	$da_co = (int) $wpdb->get_var(
		$wpdb->prepare(
			"SELECT id FROM {$bang} WHERE user_id = %d AND object_id = %d AND object_type = %s LIMIT 1",
			$user_id,
			$object_id,
			'publication'
		)
	);

	$data = array(
		'user_id'     => $user_id,
		'object_id'   => $object_id,
		'object_type' => 'publication',
		'position'    => (string) $trang,
		'updated_at'  => current_time( 'mysql' ),
	);

	if ( $da_co > 0 ) {
		$wpdb->update( $bang, $data, array( 'id' => $da_co ) );
	} else {
		$wpdb->insert( $bang, $data );
	}

	wp_send_json_success( array( 'trang' => $trang ) );
}
add_action( 'wp_ajax_nntm_doc_tien_do', 'nntm_doc_ajax_luu_tien_do' );
