<?php

defined( 'ABSPATH' ) || exit;

 
function nntm_chuoi_tri_url( string $viec = 'tham-gia' ): string {
	$slug = ( 'khai-bao' === $viec ) ? 'khai-bao-chuoi-tri' : 'tham-gia-chuoi-tri';
	$page = get_page_by_path( $slug );
	$url  = $page ? (string) get_permalink( $page ) : '';

	return (string) apply_filters( 'nntm_chuoi_tri_url', $url, $viec );
}

function nntm_congtu_tham_gia_chuoi_tri_url( string $url ): string {
	if ( 'da-tham-gia' === nntm_congtu_trang_thai_nut_banner() ) {
		$url_khai_bao = nntm_chuoi_tri_url( 'khai-bao' );
		if ( '' !== $url_khai_bao ) {
			return $url_khai_bao;
		}
	}

	$url_that = nntm_chuoi_tri_url( 'tham-gia' );
	return '' !== $url_that ? $url_that : $url;
}
add_filter( 'nntm_tham_gia_chuoi_tri_url', 'nntm_congtu_tham_gia_chuoi_tri_url' );

 
function nntm_congtu_trang_thai_nut_banner(): string {
	if ( ! is_user_logged_in() ) {
		return 'khach';
	}

	$program = function_exists( 'nntm_program_hien_tai' ) ? nntm_program_hien_tai() : null;
	if ( ! $program ) {
		return 'khong-co-chuong-trinh';
	}

	$da_tham_gia = function_exists( 'nntm_kpi_da_tham_gia' ) && nntm_kpi_da_tham_gia( $program->ID, get_current_user_id() );

	return $da_tham_gia ? 'da-tham-gia' : 'chua-tham-gia';
}

function nntm_congtu_banner_btn_label( string $label, array $slide ): string {
	return 'da-tham-gia' === nntm_congtu_trang_thai_nut_banner()
		? __( 'Cập nhật chuỗi trì', 'nntm' )
		: __( 'Tham gia', 'nntm' );
}
add_filter( 'nntm_banner_btn_label', 'nntm_congtu_banner_btn_label', 10, 2 );

/**
 * Đường dẫn trang giới thiệu của chương trình đang mở, '' nếu chưa có chương trình.
 */
function nntm_congtu_url_gioi_thieu(): string {
	$program = function_exists( 'nntm_program_hien_tai' ) ? nntm_program_hien_tai() : null;

	if ( ! $program instanceof WP_Post ) {
		return '';
	}

	return (string) get_permalink( $program );
}

/**
 * Nút "Xem thêm" đứng trước nút Tham gia trên banner, dẫn vào trang giới thiệu.
 *
 * Khách chưa đăng nhập vẫn thấy nút, nhưng trang giới thiệu sẽ đẩy họ qua đăng
 * nhập rồi quay lại — đúng như các trang Cộng Tu khác.
 *
 * @param string $html  HTML sẵn có.
 * @param array  $slide Dữ liệu tấm banner.
 */
function nntm_congtu_banner_btn_xem_them( string $html, array $slide ): string {
	$url = nntm_congtu_url_gioi_thieu();

	if ( '' === $url ) {
		return $html;
	}

	return $html . sprintf(
		'<a class="nntm-banner__btn nntm-banner__btn--phu" href="%1$s">%2$s</a>',
		esc_url( $url ),
		esc_html__( 'Xem thêm', 'nntm' )
	);
}
add_filter( 'nntm_banner_btn_truoc', 'nntm_congtu_banner_btn_xem_them', 10, 2 );

function nntm_congtu_banner_btn_attrs( array $attrs, array $slide ): array {
	switch ( nntm_congtu_trang_thai_nut_banner() ) {
		case 'khach':
			 
			$attrs['data-nntm-auth-modal'] = 'dang-nhap';
			break;
		case 'da-tham-gia':
			$attrs['data-nntm-chuoi-tri'] = 'cap-nhat';
			break;
		case 'chua-tham-gia':
			$attrs['data-nntm-chuoi-tri'] = 'tham-gia';
			break;
		default:

			 
			break;
	}

	return $attrs;
}
add_filter( 'nntm_banner_btn_attrs', 'nntm_congtu_banner_btn_attrs', 10, 2 );

 
function nntm_congtu_trang_co_block(): bool {
	$post = get_post();
	return ( $post instanceof WP_Post ) && has_block( 'nntm/cong-tu', $post );
}

function nntm_congtu_co_modal_tren_trang(): bool {
	if ( ! is_user_logged_in() ) {
		return false;
	}

	if ( is_page( array( 'tham-gia-chuoi-tri', 'khai-bao-chuoi-tri' ) ) ) {
		return false;
	}

	return function_exists( 'nntm_program_hien_tai' ) && null !== nntm_program_hien_tai();
}

function nntm_congtu_enqueue_assets(): void {
	$la_trang_tham_gia = is_page( 'tham-gia-chuoi-tri' );
	$la_trang_khai_bao = is_page( 'khai-bao-chuoi-tri' );
	$la_trang_cong_tu  = $la_trang_tham_gia || $la_trang_khai_bao;
	$co_block          = nntm_congtu_trang_co_block();
	$co_modal          = nntm_congtu_co_modal_tren_trang();

	if ( ! $la_trang_cong_tu && ! $co_block && ! $co_modal ) {
		return;
	}

	$deps = array( 'nntm-tokens', 'nntm-base' );

	 
	if ( $la_trang_cong_tu || $co_modal ) {
		$auth_css_path = NNTM_THEME_DIR . '/assets/css/pages/auth.css';
		wp_enqueue_style(
			'nntm-auth',
			NNTM_THEME_URI . '/assets/css/pages/auth.css',
			array( 'nntm-tokens', 'nntm-base' ),
			nntm_asset_version( $auth_css_path )
		);
		$deps[] = 'nntm-auth';
	}

	$css_path = NNTM_THEME_DIR . '/assets/css/pages/cong-tu.css';
	wp_enqueue_style(
		'nntm-cong-tu',
		NNTM_THEME_URI . '/assets/css/pages/cong-tu.css',
		$deps,
		nntm_asset_version( $css_path )
	);

	 
	if ( $la_trang_khai_bao ) {
		$js_path = NNTM_THEME_DIR . '/assets/js/cong-tu.js';
		wp_enqueue_script(
			'nntm-cong-tu',
			NNTM_THEME_URI . '/assets/js/cong-tu.js',
			array(),
			nntm_asset_version( $js_path ),
			true
		);
	}

	 
	 
	if ( $co_modal ) {
		$modal_js_path = NNTM_THEME_DIR . '/assets/js/cong-tu-modal.js';
		wp_enqueue_script(
			'nntm-cong-tu-modal',
			NNTM_THEME_URI . '/assets/js/cong-tu-modal.js',
			array(),
			nntm_asset_version( $modal_js_path ),
			true
		);

		 
		 
		wp_localize_script(
			'nntm-cong-tu-modal',
			'nntmCongTu',
			array(
				'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
				'action'    => 'nntm_congtu_gui_form',
				'errorText' => __( 'Không gửi được lúc này. Vui lòng thử lại.', 'nntm' ),
			)
		);
	}
}
add_action( 'wp_enqueue_scripts', 'nntm_congtu_enqueue_assets' );

function nntm_congtu_render_modal(): void {
	if ( ! nntm_congtu_co_modal_tren_trang() ) {
		return;
	}

	get_template_part( 'template-parts/cong-tu/modal-chuoi-tri' );
}
add_action( 'wp_footer', 'nntm_congtu_render_modal' );

function nntm_congtu_body_class( array $classes ): array {
	$modal = '';

	if ( ! empty( $GLOBALS['nntm_congtu_modal_loi'] ) ) {
		$modal = (string) $GLOBALS['nntm_congtu_modal_loi'];
	} elseif ( isset( $_GET['nntm_congtu_ok'] ) ) {  
		$ok = sanitize_key( wp_unslash( $_GET['nntm_congtu_ok'] ) );
		if ( 'cam-ket' === $ok ) {
			$modal = 'tham-gia';
		} elseif ( 'ghi-nhan' === $ok ) {
			$modal = 'cap-nhat';
		}
	}

	if ( '' !== $modal ) {
		$classes[] = 'nntm-congtu-mo-lai';
		$classes[] = 'nntm-congtu-mo-lai--' . sanitize_html_class( $modal );
	}

	return $classes;
}
add_filter( 'body_class', 'nntm_congtu_body_class' );

function nntm_congtu_dequeue_site_chrome(): void {
	if ( ! is_page( array( 'tham-gia-chuoi-tri', 'khai-bao-chuoi-tri' ) ) ) {
		return;
	}

	wp_dequeue_style( 'nntm-header' );
	wp_dequeue_style( 'nntm-footer' );
	wp_dequeue_script( 'nntm-header' );
	wp_dequeue_script( 'nntm-header-scroll' );
}
add_action( 'wp_enqueue_scripts', 'nntm_congtu_dequeue_site_chrome', 20 );

 
function nntm_congtu_yeu_cau_dang_nhap(): void {
	$can_gac = is_page( array( 'tham-gia-chuoi-tri', 'khai-bao-chuoi-tri' ) )
		|| is_singular( 'nntm_program' );

	if ( ! $can_gac ) {
		return;
	}

	if ( is_user_logged_in() ) {
		return;
	}

	$url_hien_tai  = home_url( add_query_arg( array() ) );
	$url_dang_nhap = function_exists( 'nntm_login_url' )
		? nntm_login_url( $url_hien_tai )
		: wp_login_url( $url_hien_tai );

	wp_safe_redirect( $url_dang_nhap );
	exit;
}
add_action( 'template_redirect', 'nntm_congtu_yeu_cau_dang_nhap', 5 );

 
function nntm_congtu_so_nguyen_duong( $raw ) {
	$chuoi = trim( (string) $raw );

	if ( '' === $chuoi || ! ctype_digit( $chuoi ) ) {
		return false;
	}

	$so = (int) $chuoi;

	return $so > 0 ? $so : false;
}

 
function nntm_congtu_xoa_cache_bxh( int $program_id ): void {
	if ( $program_id <= 0 ) {
		return;
	}

	$limits = apply_filters( 'nntm_congtu_bxh_cache_limits', array( 50, 200 ), $program_id );
	$limits = is_array( $limits ) ? $limits : array( 50, 200 );

	foreach ( array_unique( array_map( 'absint', $limits ) ) as $limit ) {
		if ( $limit > 0 ) {
			delete_transient( 'nntm_kpi_bxh_' . $program_id . '_' . $limit );
		}
	}

	global $wpdb;
	$prefix = '_transient_nntm_kpi_bxh_' . $program_id . '_';
	$like   = $wpdb->esc_like( $prefix ) . '%';
	$names  = $wpdb->get_col(
		$wpdb->prepare(
			"SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s",
			$like
		)
	);

	foreach ( (array) $names as $option_name ) {
		$transient_name = substr( (string) $option_name, strlen( '_transient_' ) );
		if ( '' !== $transient_name ) {
			delete_transient( $transient_name );
		}
	}
}

function nntm_congtu_dong_bo_kpi_sau_ghi( int $program_id ): void {
	if ( $program_id <= 0 ) {
		return;
	}

	if ( function_exists( 'nntm_kpi_tinh_lai_tong' ) ) {
		nntm_kpi_tinh_lai_tong( $program_id );
	}

	nntm_congtu_xoa_cache_bxh( $program_id );
}

 
function nntm_congtu_xu_ly_post(): void {
	if ( empty( $_POST['nntm_congtu_action'] ) ) {
		return;
	}

	 
	if ( ! is_user_logged_in() ) {
		return;
	}

	$action = sanitize_key( wp_unslash( $_POST['nntm_congtu_action'] ) );

	switch ( $action ) {
		case 'cam-ket':
			nntm_congtu_xu_ly_cam_ket();
			break;
		case 'ghi-nhan':
			nntm_congtu_xu_ly_ghi_nhan();
			break;
	}
}
add_action( 'template_redirect', 'nntm_congtu_xu_ly_post', 10 );

function nntm_congtu_url_hien_tai(): string {
	return home_url( add_query_arg( array() ) );
}

function nntm_congtu_dat_loi( string $modal, WP_Error $error ): void {
	$GLOBALS['nntm_congtu_errors']    = $error;
	$GLOBALS['nntm_congtu_modal_loi'] = $modal;
}

 
/**
 * Ghi cam kết số chuỗi của một người cho chương trình đang mở.
 *
 * Tham số $dong_y trước đây nằm giữa nhưng không dùng tới, trong khi cả hai chỗ
 * gọi đều chỉ truyền 2 đối số — PHP báo ArgumentCountError nên form luôn hỏng.
 *
 * @param mixed $so_chuoi_raw Số chuỗi người dùng nhập.
 * @param bool  $ban_tin      Có nhận bản tin hay không.
 * @return int|WP_Error ID chương trình, hoặc lỗi.
 */
function nntm_congtu_ghi_cam_ket( $so_chuoi_raw, bool $ban_tin ) {
	$program = function_exists( 'nntm_program_hien_tai' ) ? nntm_program_hien_tai() : null;

	if ( ! $program ) {
		return new WP_Error(
			'chua_co_chuong_trinh',
			__( 'Hiện không có chương trình trì tụng nào đang mở. Mời quay lại sau.', 'nntm' )
		);
	}

	if ( ! function_exists( 'nntm_kpi_da_tham_gia' ) || ! function_exists( 'nntm_kpi_cam_ket' ) ) {
		return new WP_Error( 'thieu_ham', __( 'Chức năng Cộng Tu tạm thời không khả dụng.', 'nntm' ) );
	}

	$user_id = get_current_user_id();
	$so_chuoi = nntm_congtu_so_nguyen_duong( $so_chuoi_raw );

	if ( false === $so_chuoi ) {
		return new WP_Error( 'so_khong_hop_le', __( 'Vui lòng nhập một số chuỗi lớn hơn 0.', 'nntm' ) );
	}

	$ket_qua = nntm_kpi_cam_ket( $program->ID, $user_id, $so_chuoi );

	if ( is_wp_error( $ket_qua ) ) {
		return $ket_qua;
	}

	 
	nntm_congtu_dong_bo_kpi_sau_ghi( $program->ID );

	update_user_meta( $user_id, 'nntm_nhan_ban_tin', $ban_tin ? '1' : '0' );

	return (int) $program->ID;
}

function nntm_congtu_ghi_thuc_hien( $so_chuoi_raw ) {
	$program = function_exists( 'nntm_program_hien_tai' ) ? nntm_program_hien_tai() : null;

	if ( ! $program ) {
		return new WP_Error(
			'chua_co_chuong_trinh',
			__( 'Hiện không có chương trình trì tụng nào đang mở. Mời quay lại sau.', 'nntm' )
		);
	}

	if ( ! function_exists( 'nntm_kpi_ghi_nhan' ) ) {
		return new WP_Error( 'thieu_ham', __( 'Chức năng Cộng Tu tạm thời không khả dụng.', 'nntm' ) );
	}

	$so_chuoi = nntm_congtu_so_nguyen_duong( $so_chuoi_raw );

	if ( false === $so_chuoi ) {
		return new WP_Error( 'so_khong_hop_le', __( 'Vui lòng nhập một số chuỗi lớn hơn 0.', 'nntm' ) );
	}

	$ket_qua = nntm_kpi_ghi_nhan( $program->ID, get_current_user_id(), $so_chuoi );

	if ( is_wp_error( $ket_qua ) ) {
		return $ket_qua;
	}

	nntm_congtu_dong_bo_kpi_sau_ghi( $program->ID );

	return (int) $program->ID;
}

function nntm_congtu_xu_ly_cam_ket(): void {
	$nonce = isset( $_POST['nntm_congtu_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nntm_congtu_nonce'] ) ) : '';

	if ( ! wp_verify_nonce( $nonce, 'nntm_congtu_cam_ket' ) ) {
		nntm_congtu_dat_loi( 'tham-gia', new WP_Error( 'nonce', __( 'Phiên làm việc đã hết hạn, vui lòng thử lại.', 'nntm' ) ) );
		return;
	}

	$ket_qua = nntm_congtu_ghi_cam_ket(
		$_POST['so_chuoi'] ?? '',
		! empty( $_POST['nntm_congtu_ban_tin'] )
	);

	if ( is_wp_error( $ket_qua ) ) {
		nntm_congtu_dat_loi( 'tham-gia', $ket_qua );
		return;
	}

	$url = add_query_arg( 'nntm_congtu_ok', 'cam-ket', nntm_congtu_url_hien_tai() );
	wp_safe_redirect( $url ? $url : home_url( '/' ) );
	exit;
}

function nntm_congtu_xu_ly_ghi_nhan(): void {
	$nonce = isset( $_POST['nntm_congtu_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nntm_congtu_nonce'] ) ) : '';

	if ( ! wp_verify_nonce( $nonce, 'nntm_congtu_ghi_nhan' ) ) {
		nntm_congtu_dat_loi( 'cap-nhat', new WP_Error( 'nonce', __( 'Phiên làm việc đã hết hạn, vui lòng thử lại.', 'nntm' ) ) );
		return;
	}

	$ket_qua = nntm_congtu_ghi_thuc_hien( $_POST['so_chuoi'] ?? '' );

	if ( is_wp_error( $ket_qua ) ) {
		nntm_congtu_dat_loi( 'cap-nhat', $ket_qua );
		return;
	}

	$url = add_query_arg( 'nntm_congtu_ok', 'ghi-nhan', nntm_congtu_url_hien_tai() );
	wp_safe_redirect( $url ? $url : home_url( '/' ) );
	exit;
}

 
function nntm_congtu_ajax_html_khoi( int $program_id_khoi, string $bxh_heading, int $bxh_limit ): array {
	$render_file = get_template_directory() . '/blocks/cong-tu/inc/render-cong-tu.php';

	if ( ! file_exists( $render_file ) ) {
		return array();
	}

	require_once $render_file;

	$program = nntm_congtu_block_resolve_program( $program_id_khoi );

	if ( ! $program ) {
		return array();
	}

	$bxh_limit = ( $bxh_limit > 0 ) ? min( 500, $bxh_limit ) : 50;
	$du_lieu   = nntm_congtu_block_lay_du_lieu_nhat_quan( $program, $bxh_limit );

	return array(
		'thong_ke_html' => nntm_congtu_block_render_thong_ke( $program, '', $du_lieu['tong'] ),
		'bxh_html'      => nntm_congtu_block_render_bxh( $program, $bxh_heading, $bxh_limit, $du_lieu['bxh'] ),
	);
}

function nntm_congtu_ajax_gui_form(): void {
	if ( ! is_user_logged_in() ) {
		wp_send_json_error( array( 'message' => __( 'Vui lòng đăng nhập để tiếp tục.', 'nntm' ) ), 401 );
	}

	$viec = isset( $_POST['nntm_congtu_action'] ) ? sanitize_key( wp_unslash( $_POST['nntm_congtu_action'] ) ) : '';

	if ( ! in_array( $viec, array( 'cam-ket', 'ghi-nhan' ), true ) ) {
		wp_send_json_error( array( 'message' => __( 'Yêu cầu không hợp lệ.', 'nntm' ) ), 400 );
	}

	$nonce = isset( $_POST['nntm_congtu_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nntm_congtu_nonce'] ) ) : '';

	if ( ! wp_verify_nonce( $nonce, 'nntm_congtu_' . str_replace( '-', '_', $viec ) ) ) {
		wp_send_json_error(
			array(
				'message' => __( 'Phiên làm việc đã hết hạn, vui lòng tải lại trang.', 'nntm' ),
				'het_han' => true,
			)
		);
	}

	if ( 'cam-ket' === $viec ) {
		$ket_qua = nntm_congtu_ghi_cam_ket(
			$_POST['so_chuoi'] ?? '',
			! empty( $_POST['nntm_congtu_ban_tin'] )
		);
	} else {
		$ket_qua = nntm_congtu_ghi_thuc_hien( $_POST['so_chuoi'] ?? '' );
	}

	if ( is_wp_error( $ket_qua ) ) {
		wp_send_json_error( array( 'message' => wp_strip_all_tags( $ket_qua->get_error_message() ) ) );
	}

	$program_id = (int) $ket_qua;
	$user_id    = get_current_user_id();
	$tong       = function_exists( 'nntm_kpi_tong_cua_nguoi' )
		? (array) nntm_kpi_tong_cua_nguoi( $program_id, $user_id )
		: array(
			'cam_ket'    => 0,
			'thuc_hien'  => 0,
			'tien_trinh' => 0.0,
		);

	$du_lieu = array(
		'thong_bao' => __( 'Đã ghi nhận, cảm ơn bạn đã phát tâm.', 'nntm' ),
		'tong_ket'  => nntm_congtu_cau_tong_ket( $tong ),
	);

	if ( 'ghi-nhan' === $viec ) {
		$hom_nay = function_exists( 'nntm_kpi_ghi_hom_nay' ) ? (int) nntm_kpi_ghi_hom_nay( $program_id, $user_id ) : 0;

		$du_lieu['hien_trang'] = nntm_congtu_cau_hom_nay( $hom_nay );
	} else {
		$du_lieu['hien_trang'] = nntm_congtu_cau_da_cam_ket( (int) $tong['cam_ket'], (int) $tong['thuc_hien'] );

		 
		$du_lieu['nhan_nut_banner'] = __( 'Cập nhật chuỗi trì', 'nntm' );
	}

	if ( ! empty( $_POST['lam_moi_khoi'] ) ) {
		$du_lieu = array_merge(
			$du_lieu,
			nntm_congtu_ajax_html_khoi(
				isset( $_POST['khoi_program_id'] ) ? absint( wp_unslash( $_POST['khoi_program_id'] ) ) : 0,
				isset( $_POST['khoi_bxh_heading'] ) ? sanitize_text_field( wp_unslash( $_POST['khoi_bxh_heading'] ) ) : '',
				isset( $_POST['khoi_bxh_limit'] ) ? absint( wp_unslash( $_POST['khoi_bxh_limit'] ) ) : 50
			)
		);
	}

	wp_send_json_success( $du_lieu );
}
add_action( 'wp_ajax_nntm_congtu_gui_form', 'nntm_congtu_ajax_gui_form' );

 
function nntm_congtu_phap_danh( int $user_id ): string {
	$phap_danh = trim( (string) get_user_meta( $user_id, 'nntm_phap_danh', true ) );

	if ( '' !== $phap_danh ) {
		return $phap_danh;
	}

	$user = get_userdata( $user_id );
	return $user ? $user->display_name : '';
}

function nntm_congtu_dinh_dang_so( int $n ): string {
	return number_format( $n, 0, ',', '.' );
}

 
function nntm_congtu_cau_hom_nay( int $so_hom_nay ): string {
	return sprintf(
		 
		__( 'Hôm nay bạn đã ghi %s chuỗi.', 'nntm' ),
		nntm_congtu_dinh_dang_so( $so_hom_nay )
	);
}

function nntm_congtu_cau_da_cam_ket( int $cam_ket, int $thuc_hien ): string {
	return sprintf(
		 
		__( 'Bạn đã cam kết %1$s chuỗi, đã thực hiện %2$s chuỗi.', 'nntm' ),
		nntm_congtu_dinh_dang_so( $cam_ket ),
		nntm_congtu_dinh_dang_so( $thuc_hien )
	);
}

function nntm_congtu_cau_tong_ket( array $tong ): string {
	$tien_trinh = isset( $tong['tien_trinh'] ) ? (float) $tong['tien_trinh'] : 0.0;

	return sprintf(
		 
		__( 'Tổng cam kết: %1$s · Đã trì: %2$s · Tiến trình: %3$s%%', 'nntm' ),
		nntm_congtu_dinh_dang_so( isset( $tong['cam_ket'] ) ? (int) $tong['cam_ket'] : 0 ),
		nntm_congtu_dinh_dang_so( isset( $tong['thuc_hien'] ) ? (int) $tong['thuc_hien'] : 0 ),
		(string) max( 0, (int) round( $tien_trinh * 100 ) )
	);
}

/**
 * Giao diện riêng cho trang giới thiệu chương trình trì tụng.
 */
function nntm_congtu_enqueue_trang_chuong_trinh(): void {
	if ( ! is_singular( 'nntm_program' ) ) {
		return;
	}

	$css = NNTM_THEME_DIR . '/assets/css/pages/chuong-trinh.css';

	if ( ! is_readable( $css ) ) {
		return;
	}

	wp_enqueue_style(
		'nntm-chuong-trinh',
		NNTM_THEME_URI . '/assets/css/pages/chuong-trinh.css',
		array( 'nntm-tokens', 'nntm-base', 'nntm-layout' ),
		nntm_asset_version( $css )
	);
}
add_action( 'wp_enqueue_scripts', 'nntm_congtu_enqueue_trang_chuong_trinh', 46 );
