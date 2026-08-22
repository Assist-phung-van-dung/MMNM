<?php
/**
 * Cộng Tu "chuỗi trì" — hai trang PHP template (Tham gia / Khai báo) + cắm
 * vào filter nút "Tham gia" ở banner Lễ Đàn Khổng Tước.
 *
 * Đúng docs/04-kien-truc.md mục 1: NGHIỆP VỤ nằm ở plugin
 * (wp-content/plugins/nntm-core/includes/class-chuoi-tri.php, ĐÃ XONG,
 * TUYỆT ĐỐI không sửa). File này CHỈ làm giao diện — gọi các hàm
 * nntm_kpi_...()/nntm_program_...() đã có sẵn qua function_exists() cho an
 * toàn (đề phòng thứ tự nạp plugin/theme).
 *
 * Theo docs/07-ban-giao.md mục "Đang làm dở — Cộng Tu chuỗi trì", chốt
 * 14/08/2026:
 *   - CAM KẾT và THỰC TẾ là hai dòng số riêng, CHỈ CỘNG THÊM.
 *   - Mọi thành viên đã đăng nhập tham gia được, không phân cấp.
 *   - Một ngày khai báo nhiều lần, cộng dồn. KHÔNG khai lùi ngày (đã chặn ở
 *     tầng nghiệp vụ, nntm_kpi_ghi_nhan() luôn dùng current_time()).
 *   - Vượt cam kết: số thật giữ nguyên, chỉ thanh tiến trình chặn ở 100%.
 *
 * ⚠️ NGÀY GIỜ: chỉ dùng current_time(), không bao giờ dùng gmdate()/date()
 * trần (bài học 07-ban-giao.md — bài future).
 *
 * @package NNTM
 */

defined( 'ABSPATH' ) || exit;

/* =========================================================================
 * 1. URL helper dùng chung — nơi khác gọi qua function_exists().
 * ========================================================================= */

/**
 * URL của hai trang Cộng Tu "chuỗi trì".
 *
 * Ưu tiên Page theo slug cố định; script tools/seed-cong-tu.php tạo hai
 * trang này. Chưa seed thì rơi về trang chủ (KHÔNG lỗi trắng trang) — nơi
 * gọi (vd banner) tự vô hiệu hoá nút khi URL rỗng, nên ở đây trả chuỗi rỗng
 * là lựa chọn an toàn hơn home_url() khi trang chưa tồn tại.
 *
 * @param string $viec 'tham-gia' (mặc định) hoặc 'khai-bao'.
 * @return string URL tuyệt đối, hoặc chuỗi rỗng nếu trang chưa được tạo.
 */
function nntm_chuoi_tri_url( string $viec = 'tham-gia' ): string {
	$slug = ( 'khai-bao' === $viec ) ? 'khai-bao-chuoi-tri' : 'tham-gia-chuoi-tri';
	$page = get_page_by_path( $slug );
	$url  = $page ? (string) get_permalink( $page ) : '';

	return (string) apply_filters( 'nntm_chuoi_tri_url', $url, $viec );
}

/**
 * Cắm vào filter có sẵn ở blocks/banner/render.php: nút "Tham gia" ở dải
 * "Lễ Đàn Khổng Tước" (trang Kim Cương Hành Giả, ID 243) đang bị vô hiệu hoá
 * vì filter này trước đây chưa ai cắm vào — chỉ cần trả đúng URL, banner tự
 * hiện nút sống, KHÔNG sửa blocks/banner/**.
 *
 * Từ 14/08/2026 nút này mở POPUP (xem nntm_congtu_banner_btn_attrs()) nên
 * href chỉ còn là DỰ PHÒNG khi tắt JS — vẫn phải trỏ đúng trang thật theo
 * đúng trạng thái người xem: người đã tham gia bấm "Cập nhật chuỗi trì" mà
 * tắt JS thì phải rơi vào trang khai báo (ghi nhận), không phải trang tham
 * gia lần đầu.
 *
 * @param string $url URL mặc định (rỗng) banner truyền vào.
 * @return string
 */
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

/* =========================================================================
 * 1b. Nút "Tham gia"/"Cập nhật chuỗi trì" trên banner MỞ POPUP.
 *
 * Yêu cầu chủ dự án 14/08/2026: bấm nút không qua trang khác nữa, hiện popup
 * ngay. Hai điểm cắm nntm_banner_btn_label / nntm_banner_btn_attrs đã mở sẵn
 * ở blocks/banner/render.php (chỉ 2 filter + in attrs, KHÔNG đổi hành vi mặc
 * định khi không ai cắm). File này cắm vào cả hai, đổi nhãn/thuộc tính theo
 * BA trạng thái người xem — xem bảng trong yêu cầu chủ dự án.
 * ========================================================================= */

/**
 * Trạng thái người đang xem, dùng chung cho nhãn + thuộc tính + href của nút.
 * Tính MỘT LẦN, dùng lại ở cả ba nơi để không truy vấn CSDL nhiều lần trên
 * cùng một request.
 *
 *   'khach'                : chưa đăng nhập.
 *   'khong-co-chuong-trinh': đã đăng nhập nhưng không có chương trình đang mở
 *                            — coi như khách về mặt hiển thị (vẫn "Tham gia").
 *   'chua-tham-gia'        : đã đăng nhập, có chương trình mở, CHƯA cam kết.
 *   'da-tham-gia'          : đã đăng nhập, có chương trình mở, ĐÃ cam kết.
 *
 * @return string
 */
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

/**
 * Nhãn nút — "Cập nhật chuỗi trì" CHỈ khi đã tham gia, còn lại luôn "Tham gia".
 *
 * @param string $label Nhãn mặc định banner truyền vào (thuộc tính buttonLabel).
 * @param array  $slide Dữ liệu tấm banner hiện tại (không dùng ở đây).
 * @return string
 */
function nntm_congtu_banner_btn_label( string $label, array $slide ): string {
	return 'da-tham-gia' === nntm_congtu_trang_thai_nut_banner()
		? __( 'Cập nhật chuỗi trì', 'nntm' )
		: __( 'Tham gia', 'nntm' );
}
add_filter( 'nntm_banner_btn_label', 'nntm_congtu_banner_btn_label', 10, 2 );

/**
 * Thuộc tính gắn lên thẻ <a> của nút — JS (assets/js/cong-tu-modal.js hoặc
 * auth-modal.js) đọc data-* này để biết mở popup nào.
 *
 * @param array $attrs Thuộc tính đã có (rỗng theo mặc định).
 * @param array $slide Dữ liệu tấm banner hiện tại (không dùng ở đây).
 * @return array
 */
function nntm_congtu_banner_btn_attrs( array $attrs, array $slide ): array {
	switch ( nntm_congtu_trang_thai_nut_banner() ) {
		case 'khach':
			// Chưa đăng nhập — dùng lại modal đăng nhập đã có (auth-modal.js).
			$attrs['data-nntm-auth-modal'] = 'dang-nhap';
			break;
		case 'da-tham-gia':
			$attrs['data-nntm-chuoi-tri'] = 'cap-nhat';
			break;
		case 'chua-tham-gia':
			$attrs['data-nntm-chuoi-tri'] = 'tham-gia';
			break;
		default:
			// 'khong-co-chuong-trinh': không gắn data- nào — nút rơi về hành
			// vi liên kết bình thường, dẫn tới trang tham gia (sẽ tự báo
			// "chưa có chương trình đang mở").
			break;
	}

	return $attrs;
}
add_filter( 'nntm_banner_btn_attrs', 'nntm_congtu_banner_btn_attrs', 10, 2 );

/* =========================================================================
 * 2. Nạp CSS/JS — chỉ trên 2 trang Cộng Tu + trang có block nntm/cong-tu.
 * ========================================================================= */

/**
 * Trang hiện tại có chèn block nntm/cong-tu trong nội dung hay không.
 * Dùng has_block() trên $post->post_content — an toàn cả trong REST/loop.
 */
function nntm_congtu_trang_co_block(): bool {
	$post = get_post();
	return ( $post instanceof WP_Post ) && has_block( 'nntm/cong-tu', $post );
}

/**
 * Trang hiện tại (BẤT KỲ trang nào) có in popup Cộng Tu ở chân trang hay
 * không — đúng điều kiện dùng ở nntm_congtu_render_modal(), tách ra hàm
 * riêng để nntm_congtu_enqueue_assets() gọi lại mà không lệch điều kiện.
 *
 * Popup chỉ in cho THÀNH VIÊN ĐÃ ĐĂNG NHẬP khi có chương trình đang mở, và
 * KHÔNG in trên chính 2 trang Cộng Tu toàn màn hình (đã có form ngay trên
 * trang, in thêm ở chân trang sẽ trùng id phần tử).
 */
function nntm_congtu_co_modal_tren_trang(): bool {
	if ( ! is_user_logged_in() ) {
		return false;
	}

	if ( is_page( array( 'tham-gia-chuoi-tri', 'khai-bao-chuoi-tri' ) ) ) {
		return false;
	}

	return function_exists( 'nntm_program_hien_tai' ) && null !== nntm_program_hien_tai();
}

/**
 * CSS/JS cho 2 trang Cộng Tu + mọi trang có block Thống Kê/BXH + mọi trang
 * có popup Cộng Tu ở chân trang (yêu cầu 14/08/2026: nút "Tham gia"/"Cập
 * nhật chuỗi trì" mở popup ngay tại chỗ, không chỉ ở 2 trang Cộng Tu).
 *
 * Hai trang tự dựng (page-tham-gia-chuoi-tri.php/page-khai-bao-chuoi-tri.php)
 * TÁI SỬ DỤNG lớp CSS của auth.css (.nntm-auth-page, .nntm-auth-card,
 * .nntm-auth-form, .nntm-auth-btn…) đúng yêu cầu — nên phải tự nạp thêm
 * auth.css ở đây (không sửa inc/auth.php, file đó chỉ tự nạp cho 3 trang
 * đăng nhập/đăng ký/quên mật khẩu, hoặc cho khách trên mọi trang khác —
 * người ĐÃ đăng nhập không rơi vào điều kiện đó nên phải tự lo ở đây).
 */
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

	// Hai trang toàn màn hình + popup (mọi trang khác) đều mượn nguyên
	// khuôn thẻ kính mờ + modal của auth.css.
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

	// Ba nút bấm nhanh 10/20/50 chỉ có ở màn khai báo — JS thuần, không thư
	// viện; tắt JS vẫn gõ tay được vào ô số (yêu cầu bắt buộc).
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

	// Mở/đóng popup "Tham Gia"/"Cập Nhật Chuỗi Trì" — chỉ cần trên trang có
	// popup thật sự in ra (mọi trang khác cho thành viên khi có chương trình
	// mở), tránh nạp JS thừa ở 2 trang Cộng Tu (đã có form ngay trên trang).
	if ( $co_modal ) {
		$modal_js_path = NNTM_THEME_DIR . '/assets/js/cong-tu-modal.js';
		wp_enqueue_script(
			'nntm-cong-tu-modal',
			NNTM_THEME_URI . '/assets/js/cong-tu-modal.js',
			array(),
			nntm_asset_version( $modal_js_path ),
			true
		);

		// Gửi form ngay trong popup, KHÔNG tải lại trang (yêu cầu chủ dự án
		// 21/08/2026) — xem nntm_congtu_ajax_gui_form() ở mục 5c. Không có
		// biến này (vd JS bị chặn) thì form tự POST như cũ.
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

/**
 * In popup Cộng Tu ("Tham Gia"/"Cập Nhật Chuỗi Trì") ở chân trang — cùng
 * khuôn nntm_render_auth_modal() trong inc/auth.php.
 */
function nntm_congtu_render_modal(): void {
	if ( ! nntm_congtu_co_modal_tren_trang() ) {
		return;
	}

	get_template_part( 'template-parts/cong-tu/modal-chuoi-tri' );
}
add_action( 'wp_footer', 'nntm_congtu_render_modal' );

/**
 * Gắn class lên <body> để JS tự mở lại đúng popup khi trang tải lại sau
 * một lần POST từ modal — cả hai trường hợp:
 *   - LỖI (không chuyển hướng, cùng request): đọc $GLOBALS['nntm_congtu_modal_loi'].
 *   - THÀNH CÔNG (đã wp_safe_redirect về URL hiện tại): đọc query arg
 *     nntm_congtu_ok mà nntm_congtu_xu_ly_cam_ket()/..._ghi_nhan() gắn vào.
 *
 * @param array $classes Danh sách class hiện có.
 * @return array
 */
function nntm_congtu_body_class( array $classes ): array {
	$modal = '';

	if ( ! empty( $GLOBALS['nntm_congtu_modal_loi'] ) ) {
		$modal = (string) $GLOBALS['nntm_congtu_modal_loi'];
	} elseif ( isset( $_GET['nntm_congtu_ok'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- chi doc de quyet dinh mo lai popup nao, khong tao doi du lieu.
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

/**
 * Hai trang Cộng Tu tự dựng toàn màn hình (giống page-dang-nhap.php), không
 * gọi get_header()/get_footer() — gỡ CSS/JS đầu/chân trang site, cùng khuôn
 * với nntm_dequeue_site_chrome_on_auth_pages() trong inc/auth.php (không sửa
 * file đó, lặp lại đúng khuôn ở đây).
 */
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

/* =========================================================================
 * 3. Cổng quyền — hai trang CHỈ dành cho thành viên đã đăng nhập.
 * ========================================================================= */

/**
 * Chưa đăng nhập vào 2 trang Cộng Tu → đẩy về trang đăng nhập kèm
 * redirect_to, đúng khuôn inc/hanh-gia.php.
 */
function nntm_congtu_yeu_cau_dang_nhap(): void {
	if ( ! is_page( array( 'tham-gia-chuoi-tri', 'khai-bao-chuoi-tri' ) ) ) {
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

/* =========================================================================
 * 4. Kiểm tra số nhập vào — dùng chung cho cả hai form.
 * ========================================================================= */

/**
 * Chuyển chuỗi người dùng gõ thành số nguyên dương, hoặc false nếu không
 * hợp lệ. Từ chối RÕ RÀNG: rỗng, số 0, số âm, số thập phân, chữ, khoảng
 * trắng lẫn số — ctype_digit() chỉ chấp nhận toàn chữ số (không dấu trừ,
 * không dấu chấm), nên "-5", "5.5", "5 chuỗi", "abc" đều bị loại ở đây,
 * KHÔNG bao giờ lọt xuống tới nntm_kpi_cam_ket()/nntm_kpi_ghi_nhan().
 *
 * @param mixed $raw Giá trị thô từ $_POST.
 * @return int|false
 */
function nntm_congtu_so_nguyen_duong( $raw ) {
	$chuoi = trim( (string) $raw );

	if ( '' === $chuoi || ! ctype_digit( $chuoi ) ) {
		return false;
	}

	$so = (int) $chuoi;

	return $so > 0 ? $so : false;
}

/* =========================================================================
 * 4b. Đồng bộ cache/tổng KPI sau khi ghi.
 * ========================================================================= */

/**
 * Xóa mọi transient Bảng Xếp Hạng của một chương trình mà theme có thể biết.
 *
 * Plugin cũ chốt BXH 24h và không xóa cache khi ghi. Điều đó tạo ra lỗi rất
 * khó hiểu: trang được mở khi chưa có dữ liệu -> cache mảng rỗng -> người dùng
 * ghi chuỗi sau đó nhưng BXH vẫn rỗng tới 24h. Từ yêu cầu sửa 15/08/2026,
 * cache BXH phải bị invalidated ngay sau một lần cam kết/ghi nhận thành công.
 *
 * delete_transient() được gọi thay vì DELETE option trực tiếp để tương thích
 * cả site có persistent object cache. Ngoài hai limit thường dùng (50/200),
 * ta quét các transient đang lưu trong wp_options để dọn các limit tùy chỉnh.
 *
 * @param int $program_id ID chương trình.
 */
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

/**
 * Sau khi plugin ghi log thành công, tính lại aggregate (nếu API có sẵn) rồi
 * xóa cache BXH. wp_nntm_kpi_log vẫn là source of truth; option/transient chỉ
 * là cache có thể tái tạo.
 *
 * @param int $program_id ID chương trình.
 */
function nntm_congtu_dong_bo_kpi_sau_ghi( int $program_id ): void {
	if ( $program_id <= 0 ) {
		return;
	}

	if ( function_exists( 'nntm_kpi_tinh_lai_tong' ) ) {
		nntm_kpi_tinh_lai_tong( $program_id );
	}

	nntm_congtu_xoa_cache_bxh( $program_id );
}

/* =========================================================================
 * 5. Xử lý POST — điểm vào duy nhất ở template_redirect.
 * ========================================================================= */

/**
 * Phân theo trường ẩn nntm_congtu_action, giống khuôn nntm_handle_auth_post()
 * trong inc/auth.php. Lỗi/thành công lưu vào $GLOBALS để template in ngay
 * trên form đang đứng — KHÔNG chuyển hướng khi có lỗi.
 */
function nntm_congtu_xu_ly_post(): void {
	if ( empty( $_POST['nntm_congtu_action'] ) ) {
		return;
	}

	// Trang yêu cầu đăng nhập đã chặn ở mức ưu tiên 5 phía trên; đây là lớp
	// phòng thủ thứ hai, tránh mọi ngả POST trực tiếp không qua trang.
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

/**
 * URL của trang đang đứng — dùng làm đích redirect sau khi POST thành công
 * từ popup (yêu cầu 14/08/2026: form trong popup POST về CHÍNH trang đang
 * đứng, không phải trang chuỗi trì; xong việc quay lại đúng chỗ). Cùng biểu
 * thức đã dùng ở nntm_congtu_yeu_cau_dang_nhap() phía trên.
 *
 * @return string
 */
function nntm_congtu_url_hien_tai(): string {
	return home_url( add_query_arg( array() ) );
}

/**
 * Ghi lỗi + NHỚ popup nào cần JS tự mở lại (xem nntm_congtu_body_class()) —
 * dùng chung cho mọi nhánh lỗi của cả hai hành động 'cam-ket' và 'ghi-nhan'.
 *
 * @param string   $modal 'tham-gia' hoặc 'cap-nhat'.
 * @param WP_Error $error Lỗi cần hiện lại trên form.
 */
function nntm_congtu_dat_loi( string $modal, WP_Error $error ): void {
	$GLOBALS['nntm_congtu_errors']    = $error;
	$GLOBALS['nntm_congtu_modal_loi'] = $modal;
}

/* -------------------------------------------------------------------------
 * 5a. NGHIỆP VỤ THUẦN — không đụng $GLOBALS, không chuyển hướng, không in
 * gì cả. Trả về ID chương trình đã ghi hoặc WP_Error.
 *
 * Tách ra 21/08/2026 (yêu cầu chủ dự án: "nhập xong không
 * muốn load lại page") để MỘT bộ luật dùng cho CẢ HAI đường vào:
 *   - POST thường (template_redirect) — vẫn giữ nguyên cho trường hợp tắt JS.
 *   - AJAX (admin-ajax.php) — xem mục 5c.
 * Không có bản sao thứ hai của luật nghiệp vụ nào ở đây.
 * ------------------------------------------------------------------------- */

/**
 * Ghi CAM KẾT ("Tham gia" lần đầu / "Cam kết thêm").
 *
 * @param mixed $so_chuoi_raw Giá trị thô người dùng gõ.
 * @param bool  $dong_y       Đã tích "đồng ý Điều khoản" hay chưa.
 * @param bool  $ban_tin      Có nhận bản tin hay không.
 * @return int|WP_Error ID chương trình đã ghi, hoặc lỗi để nơi gọi tự hiện.
 */
function nntm_congtu_ghi_cam_ket( $so_chuoi_raw, bool $dong_y, bool $ban_tin ) {
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

	// Điều khoản sử dụng CHỈ bắt buộc ở lần tham gia đầu tiên.
	if ( ! nntm_kpi_da_tham_gia( $program->ID, $user_id ) && ! $dong_y ) {
		return new WP_Error( 'dieu_khoan', __( 'Vui lòng đồng ý với Điều khoản sử dụng.', 'nntm' ) );
	}

	$so_chuoi = nntm_congtu_so_nguyen_duong( $so_chuoi_raw );

	if ( false === $so_chuoi ) {
		return new WP_Error( 'so_khong_hop_le', __( 'Vui lòng nhập một số chuỗi lớn hơn 0.', 'nntm' ) );
	}

	$ket_qua = nntm_kpi_cam_ket( $program->ID, $user_id, $so_chuoi );

	if ( is_wp_error( $ket_qua ) ) {
		return $ket_qua;
	}

	// Ghi log đã thành công: aggregate/cache chỉ là dữ liệu dẫn xuất, phải
	// đồng bộ ngay để trang Kim Cương không giữ BXH rỗng tới 24 giờ.
	nntm_congtu_dong_bo_kpi_sau_ghi( $program->ID );

	// "Nhận thông tin của trang" — không bắt buộc, chỉ lưu lại lựa chọn.
	update_user_meta( $user_id, 'nntm_nhan_ban_tin', $ban_tin ? '1' : '0' );

	return (int) $program->ID;
}

/**
 * Ghi THỰC HIỆN ("Ghi Nhận" / "Cập Nhật Chuỗi Trì").
 *
 * nntm_kpi_ghi_nhan() luôn ghi vào NGÀY HIỆN TẠI, không nhận tham số ngày
 * (không thể khai lùi ngày — chốt nghiệp vụ 14/08/2026).
 *
 * @param mixed $so_chuoi_raw Giá trị thô người dùng gõ.
 * @return int|WP_Error ID chương trình đã ghi, hoặc lỗi để nơi gọi tự hiện.
 */
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

	// Ghi thực tế thành công phải làm BXH thấy dữ liệu ngay ở request kế tiếp.
	nntm_congtu_dong_bo_kpi_sau_ghi( $program->ID );

	return (int) $program->ID;
}

/**
 * Xử lý "Tham gia" (lần đầu) / "Cam kết thêm" — POST thường (tắt JS).
 *
 * Form có thể POST từ popup ở BẤT KỲ trang nào (không chỉ trang
 * /tham-gia-chuoi-tri/) — thành công thì quay lại ĐÚNG trang đang đứng,
 * kèm tham số để JS tự mở lại popup và hiện thông báo.
 */
function nntm_congtu_xu_ly_cam_ket(): void {
	$nonce = isset( $_POST['nntm_congtu_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nntm_congtu_nonce'] ) ) : '';

	if ( ! wp_verify_nonce( $nonce, 'nntm_congtu_cam_ket' ) ) {
		nntm_congtu_dat_loi( 'tham-gia', new WP_Error( 'nonce', __( 'Phiên làm việc đã hết hạn, vui lòng thử lại.', 'nntm' ) ) );
		return;
	}

	$ket_qua = nntm_congtu_ghi_cam_ket(
		$_POST['so_chuoi'] ?? '',
		! empty( $_POST['nntm_congtu_dong_y'] ),
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

/**
 * Xử lý "Ghi Nhận" (khai báo hằng ngày) — POST thường (tắt JS).
 *
 * Form có thể POST từ popup "Cập Nhật Chuỗi Trì" ở BẤT KỲ trang nào —
 * thành công thì quay lại ĐÚNG trang đang đứng, kèm tham số để JS tự mở lại
 * popup và hiện thông báo.
 */
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

/* =========================================================================
 * 5c. GỬI FORM BẰNG AJAX — không tải lại trang.
 *
 * Yêu cầu chủ dự án 21/08/2026 (trang Kim Cương Hành Giả): "khi nhấn Cập
 * nhật chuỗi trì sẽ có form nhập, sau khi nhập xong anh không muốn load lại
 * page mà muốn cập nhật luôn và có 1 thông báo, và load lại bảng xếp hạng và
 * Thống Kê Của Đạo Tràng luôn."
 *
 * Endpoint này KHÔNG có luật nghiệp vụ riêng — chỉ kiểm nonce rồi gọi đúng
 * hai hàm ở mục 5a mà POST thường đang dùng, nên hai đường vào không thể
 * lệch luật nhau. Tắt JS thì form vẫn POST như cũ (method="post" giữ nguyên
 * trong template).
 * ========================================================================= */

/**
 * Dựng lại HTML hai khối "Thống Kê Của Đạo Tràng" + "Bảng Xếp Hạng Cá Nhân"
 * để trả về cho JS thay tại chỗ.
 *
 * Dùng ĐÚNG hai hàm render của block nntm/cong-tu (không chép lại markup),
 * nên số liệu/hình dạng luôn khớp với lần tải trang đầy đủ. Chỉ trả về phần
 * RUỘT (thẻ .nntm-cong-tu__thong-ke và .nntm-cong-tu__bxh) — thẻ <section>
 * bọc ngoài giữ nguyên trong DOM vì nó mang các class do
 * inc/kim-cuong-hanh-gia.php gắn theo trang (render_block_data chỉ chạy khi
 * is_page('kim-cuong-hanh-gia'), không có trong request admin-ajax).
 *
 * Tiêu đề "Thống Kê Của Đạo Tràng" nằm NGOÀI thẻ .nntm-cong-tu__thong-ke
 * (xem chú thích trong blocks/cong-tu/inc/render-cong-tu.php) nên truyền
 * heading rỗng ở đây là đúng; còn tiêu đề BXH nằm TRONG .nntm-cong-tu__bxh
 * nên phải truyền lại.
 *
 * @param int    $program_id_khoi ID chương trình khối đang hiển thị (0 = chương trình đang mở).
 * @param string $bxh_heading     Tiêu đề bảng xếp hạng đang hiện trên trang.
 * @param int    $bxh_limit       Số dòng bảng xếp hạng của khối.
 * @return array{thong_ke_html?:string,bxh_html?:string}
 */
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

/**
 * Endpoint AJAX cho cả hai form trong popup Cộng Tu.
 *
 * Dùng lại ĐÚNG nonce mà form đã in ra sẵn (nntm_congtu_cam_ket /
 * nntm_congtu_ghi_nhan) — JS chỉ gửi nguyên FormData của form, không cần
 * nonce thứ hai.
 */
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
			! empty( $_POST['nntm_congtu_dong_y'] ),
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

		// Đã cam kết xong thì nút trên banner phải thành "Cập nhật chuỗi trì"
		// ngay, không đợi lần tải trang sau (xem nntm_congtu_banner_btn_label()).
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

/* =========================================================================
 * 6. Tiện ích hiển thị dùng chung cho template + block.
 * ========================================================================= */

/**
 * Pháp danh hiển thị của một thành viên — meta nntm_phap_danh, rơi về
 * display_name. Dùng chung để trang Tham gia/Khai báo và block BXH hiển thị
 * nhất quán với dòng "Xin chào".
 *
 * @param int $user_id ID thành viên.
 * @return string
 */
function nntm_congtu_phap_danh( int $user_id ): string {
	$phap_danh = trim( (string) get_user_meta( $user_id, 'nntm_phap_danh', true ) );

	if ( '' !== $phap_danh ) {
		return $phap_danh;
	}

	$user = get_userdata( $user_id );
	return $user ? $user->display_name : '';
}

/**
 * Định dạng số có dấu chấm ngăn nghìn kiểu Việt Nam — dùng chung cho template
 * lẫn block Thống Kê/BXH, tránh lặp number_format() rải rác nhiều nơi.
 *
 * @param int $n Số cần định dạng.
 * @return string
 */
function nntm_congtu_dinh_dang_so( int $n ): string {
	return number_format( $n, 0, ',', '.' );
}

/*
 * Ba câu trạng thái dưới đây được in ở template LẦN ĐẦU và gửi lại qua AJAX
 * sau mỗi lần ghi (mục 5c) — đặt ở MỘT chỗ để hai đường không bao giờ lệch
 * chữ nhau. Trả về CHỮ THUẦN (chưa escape): template tự esc_html(), JS tự
 * gán bằng textContent.
 */

/**
 * "Hôm nay bạn đã ghi N chuỗi." — form Khai Báo / popup Cập Nhật Chuỗi Trì.
 *
 * @param int $so_hom_nay Số chuỗi đã ghi trong ngày.
 * @return string
 */
function nntm_congtu_cau_hom_nay( int $so_hom_nay ): string {
	return sprintf(
		/* translators: %s: số chuỗi đã ghi hôm nay */
		__( 'Hôm nay bạn đã ghi %s chuỗi.', 'nntm' ),
		nntm_congtu_dinh_dang_so( $so_hom_nay )
	);
}

/**
 * "Bạn đã cam kết A chuỗi, đã thực hiện B chuỗi." — form Tham Gia/Cam Kết Thêm.
 *
 * @param int $cam_ket   Tổng cam kết.
 * @param int $thuc_hien Tổng đã thực hiện.
 * @return string
 */
function nntm_congtu_cau_da_cam_ket( int $cam_ket, int $thuc_hien ): string {
	return sprintf(
		/* translators: 1: số chuỗi đã cam kết, 2: số chuỗi đã thực hiện */
		__( 'Bạn đã cam kết %1$s chuỗi, đã thực hiện %2$s chuỗi.', 'nntm' ),
		nntm_congtu_dinh_dang_so( $cam_ket ),
		nntm_congtu_dinh_dang_so( $thuc_hien )
	);
}

/**
 * "Tổng cam kết A · đã thực hiện B · tiến trình C%" — chân form Khai Báo và
 * thông báo sau khi Ghi Nhận.
 *
 * ĐỔI 21/08/2026 theo yêu cầu chủ dự án: tiến trình KHÔNG còn chặn ở 100% —
 * trì 50/25 chuỗi thì hiện đúng "200%". Chỉ ĐỘ RỘNG thanh tiến trình mới bị
 * chặn (xem nntm_congtu_block_be_rong_thanh() trong
 * blocks/cong-tu/inc/render-cong-tu.php).
 *
 * @param array $tong Mảng trả về từ nntm_kpi_tong_cua_nguoi().
 * @return string
 */
function nntm_congtu_cau_tong_ket( array $tong ): string {
	$tien_trinh = isset( $tong['tien_trinh'] ) ? (float) $tong['tien_trinh'] : 0.0;

	return sprintf(
		/* translators: 1: cam kết, 2: thực hiện, 3: tiến trình phần trăm */
		__( 'Tổng cam kết %1$s · đã thực hiện %2$s · tiến trình %3$s%%', 'nntm' ),
		nntm_congtu_dinh_dang_so( isset( $tong['cam_ket'] ) ? (int) $tong['cam_ket'] : 0 ),
		nntm_congtu_dinh_dang_so( isset( $tong['thuc_hien'] ) ? (int) $tong['thuc_hien'] : 0 ),
		(string) max( 0, (int) round( $tien_trinh * 100 ) )
	);
}
