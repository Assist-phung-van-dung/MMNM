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

/**
 * Chương trình đang mở hiện tại, hoặc null kèm thông báo thân thiện lưu vào
 * $GLOBALS['nntm_congtu_errors'] — KHÔNG BAO GIỜ lỗi trắng trang.
 *
 * @param string $modal 'tham-gia' hoặc 'cap-nhat' — popup nào đang gọi hàm
 *                      này, để nntm_congtu_dat_loi() nhớ đúng chỗ mở lại.
 */
function nntm_congtu_lay_chuong_trinh_hoac_bao_loi( string $modal ): ?WP_Post {
	$program = function_exists( 'nntm_program_hien_tai' ) ? nntm_program_hien_tai() : null;

	if ( ! $program ) {
		nntm_congtu_dat_loi(
			$modal,
			new WP_Error(
				'chua_co_chuong_trinh',
				__( 'Hiện không có chương trình trì tụng nào đang mở. Mời quay lại sau.', 'nntm' )
			)
		);
	}

	return $program;
}

/**
 * Xử lý "Tham gia" (lần đầu) / "Cam kết thêm" (đã tham gia) — dùng CHUNG
 * nntm_kpi_cam_ket(), đúng chốt nghiệp vụ 14/08/2026.
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

	$program = nntm_congtu_lay_chuong_trinh_hoac_bao_loi( 'tham-gia' );
	if ( ! $program ) {
		return;
	}

	$user_id = get_current_user_id();

	if ( ! function_exists( 'nntm_kpi_da_tham_gia' ) || ! function_exists( 'nntm_kpi_cam_ket' ) ) {
		nntm_congtu_dat_loi( 'tham-gia', new WP_Error( 'thieu_ham', __( 'Chức năng Cộng Tu tạm thời không khả dụng.', 'nntm' ) ) );
		return;
	}

	$da_tham_gia = nntm_kpi_da_tham_gia( $program->ID, $user_id );

	// Điều khoản sử dụng CHỈ bắt buộc ở lần tham gia đầu tiên.
	if ( ! $da_tham_gia && empty( $_POST['nntm_congtu_dong_y'] ) ) {
		nntm_congtu_dat_loi(
			'tham-gia',
			new WP_Error( 'dieu_khoan', __( 'Vui lòng đồng ý với Điều khoản sử dụng.', 'nntm' ) )
		);
		return;
	}

	$so_chuoi = nntm_congtu_so_nguyen_duong( $_POST['so_chuoi'] ?? '' );

	if ( false === $so_chuoi ) {
		nntm_congtu_dat_loi(
			'tham-gia',
			new WP_Error( 'so_khong_hop_le', __( 'Vui lòng nhập một số chuỗi lớn hơn 0.', 'nntm' ) )
		);
		return;
	}

	$ket_qua = nntm_kpi_cam_ket( $program->ID, $user_id, $so_chuoi );

	if ( is_wp_error( $ket_qua ) ) {
		nntm_congtu_dat_loi( 'tham-gia', $ket_qua );
		return;
	}

	// "Nhận thông tin của trang" — không bắt buộc, chỉ lưu lại lựa chọn.
	update_user_meta( $user_id, 'nntm_nhan_ban_tin', empty( $_POST['nntm_congtu_ban_tin'] ) ? '0' : '1' );

	$url = add_query_arg( 'nntm_congtu_ok', 'cam-ket', nntm_congtu_url_hien_tai() );
	wp_safe_redirect( $url ? $url : home_url( '/' ) );
	exit;
}

/**
 * Xử lý "Ghi Nhận" (khai báo hằng ngày) — nntm_kpi_ghi_nhan() luôn ghi vào
 * NGÀY HIỆN TẠI, không nhận tham số ngày từ đây (không thể khai lùi ngày).
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

	$program = nntm_congtu_lay_chuong_trinh_hoac_bao_loi( 'cap-nhat' );
	if ( ! $program ) {
		return;
	}

	if ( ! function_exists( 'nntm_kpi_ghi_nhan' ) ) {
		nntm_congtu_dat_loi( 'cap-nhat', new WP_Error( 'thieu_ham', __( 'Chức năng Cộng Tu tạm thời không khả dụng.', 'nntm' ) ) );
		return;
	}

	$so_chuoi = nntm_congtu_so_nguyen_duong( $_POST['so_chuoi'] ?? '' );

	if ( false === $so_chuoi ) {
		nntm_congtu_dat_loi(
			'cap-nhat',
			new WP_Error( 'so_khong_hop_le', __( 'Vui lòng nhập một số chuỗi lớn hơn 0.', 'nntm' ) )
		);
		return;
	}

	$user_id = get_current_user_id();
	$ket_qua = nntm_kpi_ghi_nhan( $program->ID, $user_id, $so_chuoi );

	if ( is_wp_error( $ket_qua ) ) {
		nntm_congtu_dat_loi( 'cap-nhat', $ket_qua );
		return;
	}

	$url = add_query_arg( 'nntm_congtu_ok', 'ghi-nhan', nntm_congtu_url_hien_tai() );
	wp_safe_redirect( $url ? $url : home_url( '/' ) );
	exit;
}

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
