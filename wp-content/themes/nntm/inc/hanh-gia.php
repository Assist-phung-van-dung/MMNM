<?php
/**
 * Khu "Hành Giả" (Đại Sĩ / Kim Cương) trong phân mục Nhập Pháp Giới.
 *
 * Theo docs/04-kien-truc.md mục 10: cấp con dùng TERM CON của
 * `nntm_section`, không đẻ CPT/taxonomy mới. Term cha "Nhập Pháp Giới"
 * (term_id=7) có hai term con dự kiến slug `dai-si-hanh-gia` và
 * `kim-cuong-hanh-gia` — phần việc khác đang tạo hai term này, có thể
 * CHƯA tồn tại lúc file này chạy, nên mọi hàm ở đây phải chịu được việc
 * term chưa có (trả về null, không lỗi, không phá trang).
 *
 * @package NNTM
 */

defined( 'ABSPATH' ) || exit;

/* =========================================================================
 * 1. Nhận diện bài thuộc khu Hành Giả nào.
 * ========================================================================= */

/**
 * Bài viết có thuộc khu Hành Giả hay không, và thuộc cấp nào.
 *
 * Nhận diện bằng SLUG của term gắn trực tiếp lên bài (term con của
 * `nntm_section`) — KHÔNG hardcode term_id, vì term cha/con là dữ liệu do
 * phần việc khác tạo, có thể tạo sau hoặc đổi ID.
 *
 * @param WP_Post|null $post Bài viết cần kiểm tra, mặc định bài hiện tại.
 * @return string|null 'dai_si' | 'kim_cuong' | 'chung' | null (không thuộc khu này).
 */
function nntm_bai_thuoc_hanh_gia( ?WP_Post $post = null ): ?string {
	$post = get_post( $post );

	if ( ! $post instanceof WP_Post || 'nntm_article' !== $post->post_type ) {
		return null;
	}

	// Taxonomy chưa đăng ký (plugin nntm-core chưa nạp) → không suy đoán bừa.
	if ( ! taxonomy_exists( 'nntm_section' ) ) {
		return null;
	}

	$terms = get_the_terms( $post, 'nntm_section' );

	if ( ! is_array( $terms ) || empty( $terms ) ) {
		return null;
	}

	// Ánh xạ slug → mã cấp. Term chưa được tạo (phần việc song song chưa
	// xong) thì đơn giản là không có slug nào khớp — hàm trả null, KHÔNG lỗi.
	// Đưa qua filter để kiểm thử/mở rộng được mà không phải sửa file này.
	$slug_theo_cap = apply_filters(
		'nntm_hanh_gia_slug_theo_cap',
		array(
			'dai-si-hanh-gia'    => 'dai_si',
			'kim-cuong-hanh-gia' => 'kim_cuong',
			/*
			 * SỬA 17/08/2026: bài gắn THẲNG vào term cha "Nhập Pháp Giới"
			 * (không qua 2 term con) từng bị coi là KHÔNG thuộc khu Hành Giả
			 * — hàm trả null nên nntm_hanh_gia_chan_quyen() bỏ qua, khách
			 * chưa đăng nhập đọc thẳng được bằng URL. ĐO THẬT: bài #7 "Bài
			 * thử số 4" gắn thẳng vào nhap-phap-gioi. 'chung' = thuộc khu
			 * hạn chế nhưng không riêng cấp nào — khớp với
			 * nntm_term_khu_han_che() (đã thêm nhap-phap-gioi ở đó) và với
			 * nntm_duoc_xem_khu_han_che() (hiện chỉ đòi đăng nhập, chưa phân
			 * biệt cấp — xem chú thích hàm đó).
			 */
			'nhap-phap-gioi'     => 'chung',
		)
	);

	foreach ( $terms as $term ) {
		if ( $term instanceof WP_Term && isset( $slug_theo_cap[ $term->slug ] ) ) {
			return $slug_theo_cap[ $term->slug ];
		}
	}

	return null;
}

/**
 * Gắn class nhận diện lên <body> cho bài thuộc khu Hành Giả, để CSS
 * (assets/css/pages/bai-hanh-gia.css) và các phần khác của theme bám vào.
 *
 * @param array $classes Danh sách class hiện có.
 * @return array
 */
function nntm_hanh_gia_body_class( array $classes ): array {
	if ( ! is_singular( 'nntm_article' ) ) {
		return $classes;
	}

	$cap = nntm_bai_thuoc_hanh_gia( get_queried_object() );

	if ( null === $cap ) {
		return $classes;
	}

	$classes[] = 'is-bai-hanh-gia';

	// 'chung' (nhap-phap-gioi, không riêng cấp nào) không nên đội lốt is-bai-dai-si.
	if ( 'kim_cuong' === $cap ) {
		$classes[] = 'is-bai-kim-cuong';
	} elseif ( 'dai_si' === $cap ) {
		$classes[] = 'is-bai-dai-si';
	}

	return $classes;
}
add_filter( 'body_class', 'nntm_hanh_gia_body_class' );

/* =========================================================================
 * 2. Cổng quyền — bài khu Hành Giả yêu cầu đăng nhập.
 * ========================================================================= */

/**
 * Chặn khách chưa đăng nhập vào thẳng URL bài thuộc khu Hành Giả.
 *
 * Đưa qua filter `nntm_bai_hanh_gia_can_access` để phần việc khác đổi được
 * điều kiện (ví dụ chốt mức quyền theo role nntm_dai_si/nntm_kim_cuong) mà
 * không phải sửa file này. Quản trị viên luôn vào được để còn xem/sửa bài.
 */
function nntm_hanh_gia_chan_quyen(): void {
	if ( ! is_singular( 'nntm_article' ) ) {
		return;
	}

	$post = get_queried_object();
	$cap  = nntm_bai_thuoc_hanh_gia( $post instanceof WP_Post ? $post : null );

	if ( null === $cap ) {
		return;
	}

	if ( current_user_can( 'manage_options' ) ) {
		return;
	}

	$duoc_vao = is_user_logged_in();
	$duoc_vao = (bool) apply_filters( 'nntm_bai_hanh_gia_can_access', $duoc_vao, $post, get_current_user_id() );

	if ( $duoc_vao ) {
		return;
	}

	$url_hien_tai = get_permalink( $post );
	$url_dang_nhap = function_exists( 'nntm_login_url' )
		? nntm_login_url( $url_hien_tai ? $url_hien_tai : '' )
		: wp_login_url( $url_hien_tai ? $url_hien_tai : '' );

	wp_safe_redirect( $url_dang_nhap );
	exit;
}
add_action( 'template_redirect', 'nntm_hanh_gia_chan_quyen' );

/* =========================================================================
 * 3. Chặn rò nội dung khu Hành Giả ra ngoài.
 *
 * Chặn ở trang thôi là CHƯA ĐỦ. Nội dung khoá rò ra chủ yếu ở những chỗ
 * không ai để ý: kết quả tìm kiếm, các dải "bài mới nhất" trên trang chủ,
 * feed RSS, REST API. Đo thực tế ngày 14/08/2026 trước khi bịt: một truy
 * vấn tìm kiếm của khách chưa đăng nhập trả về 10 bài Đại Sĩ.
 *
 * Vậy chặn hai tầng:
 *   Tầng 1 — chặn lối vào thẳng (trang danh sách, kho lưu trữ chuyên mục).
 *   Tầng 2 — loại bài khu này khỏi MỌI truy vấn khác của khách chưa đăng nhập.
 * ========================================================================= */

/**
 * Slug các Page phải đăng nhập mới xem được.
 *
 * Lọc qua filter để ban quản trị thêm trang khác mà không phải sửa code.
 *
 * @return string[]
 */
function nntm_trang_can_dang_nhap(): array {
	return (array) apply_filters(
		'nntm_trang_can_dang_nhap',
		array( 'dai-si-hanh-gia', 'kim-cuong-hanh-gia' )
	);
}

/*
 * SỬA 17/08/2026 — lỗ rò thứ ba cùng loại (sau vụ 10 bài Đại Sĩ 14/08 và vụ
 * Page dai-si/kim-cuong 15/08, xem docs/10-ban-giao-tim-kiem.md mục 3):
 *
 * Danh sách dưới chỉ có hai term CON (dai-si-hanh-gia, kim-cuong-hanh-gia).
 * Nhưng bài viết gắn THẲNG vào term CHA "Nhập Pháp Giới" (slug
 * nhap-phap-gioi) — get_the_terms() trả đúng term đã gắn, không tự suy ra
 * term cha của nó — nên không khớp danh sách và bị coi là public. ĐO THẬT:
 * bài #7 "Bài thử số 4" gắn thẳng vào nhap-phap-gioi, đang public.
 */

/**
 * Slug các term `nntm_section` bị hạn chế (nội dung chỉ cho thành viên).
 *
 * Dùng chung cho cả cổng quyền lẫn bộ lọc truy vấn, để hai chỗ không bao
 * giờ lệch nhau — lệch là rò.
 *
 * @return string[]
 */
function nntm_term_khu_han_che(): array {
	return (array) apply_filters(
		'nntm_term_khu_han_che',
		// 'nhap-phap-gioi': term CHA — bài gắn thẳng vào đây (không qua 2 term
		// con) từng lọt qua, xem ghi chú SỬA 17/08/2026 ở nntm_trang_can_dang_nhap().
		array( 'nhap-phap-gioi', 'dai-si-hanh-gia', 'kim-cuong-hanh-gia' )
	);
}

/**
 * Người đang xem có được thấy nội dung khu hạn chế không?
 *
 * Hiện tại: chỉ cần đăng nhập (khớp mặc định của block nntm/rank-card và
 * quyết định của chủ dự án 14/08/2026 — "mọi thành viên đã đăng nhập").
 * Khách chốt lại mức quyền theo cấp thì đổi bằng filter, không sửa code.
 *
 * @return bool
 */
function nntm_duoc_xem_khu_han_che(): bool {
	$duoc = is_user_logged_in();

	if ( current_user_can( 'manage_options' ) ) {
		$duoc = true;
	}

	/*
	 * Dòng lệnh (script trong tools/, WP-CLI, cron chạy tay) KHÔNG có
	 * người xem nào để mà chặn — ở đó không hề tồn tại phiên đăng nhập,
	 * nên `is_user_logged_in()` luôn false và bộ lọc sẽ giấu bài khỏi
	 * chính công cụ của mình.
	 *
	 * ĐÃ CẮN THẬT ngày 14/08/2026: `tools/seed-kim-cuong-hanh-gia.php`
	 * kiểm tra trùng bằng một truy vấn `nntm_article`, bị bộ lọc giấu mất
	 * 26 bài vừa tạo, nên chạy lần hai nó tạo thêm 26 bài nữa — tổng 52.
	 * Vá ở từng script là sai chỗ: mọi script seed và mọi công cụ bảo trì
	 * sau này đều sẽ dính. Chặn là việc của tầng web, vậy loại trừ CLI
	 * ngay tại đây một lần cho tất cả.
	 */
	if ( 'cli' === PHP_SAPI ) {
		$duoc = true;
	}

	return (bool) apply_filters( 'nntm_duoc_xem_khu_han_che', $duoc, get_current_user_id() );
}

/**
 * Tầng 1 — chặn lối vào thẳng: Page danh sách và kho lưu trữ chuyên mục
 * của khu Hành Giả. Đẩy khách chưa đăng nhập về trang đăng nhập kèm
 * `redirect_to` để đăng nhập xong quay lại đúng chỗ đang muốn xem.
 */
function nntm_hanh_gia_chan_trang_danh_sach(): void {
	if ( nntm_duoc_xem_khu_han_che() ) {
		return;
	}

	$can_chan = false;

	if ( is_page( nntm_trang_can_dang_nhap() ) ) {
		$can_chan = true;
	}

	// Kho lưu trữ chuyên mục, ví dụ /phan-muc/dai-si-hanh-gia/ — cùng nội
	// dung với trang danh sách nên phải chặn y hệt, nếu không là cửa sau.
	if ( is_tax( 'nntm_section', nntm_term_khu_han_che() ) ) {
		$can_chan = true;
	}

	if ( ! $can_chan ) {
		return;
	}

	$url_hien_tai = home_url( add_query_arg( array() ) );

	$url_dang_nhap = function_exists( 'nntm_login_url' )
		? nntm_login_url( $url_hien_tai )
		: wp_login_url( $url_hien_tai );

	wp_safe_redirect( $url_dang_nhap );
	exit;
}
add_action( 'template_redirect', 'nntm_hanh_gia_chan_trang_danh_sach' );

/**
 * Tầng 2 — loại bài khu Hành Giả khỏi mọi truy vấn của khách chưa đăng nhập.
 *
 * VÌ SAO KHÔNG ÁP CHO MỌI TRUY VẤN: thêm `tax_query` vào truy vấn của
 * post type không đăng ký `nntm_section` là thừa một phép JOIN trên mọi
 * trang của site. Chỉ áp khi truy vấn thật sự có thể chạm tới
 * `nntm_article` — tức là hoặc khai đúng post type đó, hoặc không khai gì
 * (tìm kiếm, feed, `any`).
 *
 * VÌ SAO PHẢI CỘNG THÊM CHỨ KHÔNG GÁN ĐÈ: block nntm/card-list đã tự đặt
 * `tax_query` để lọc theo chuyên mục. Gán đè sẽ giết bộ lọc của block và
 * làm mọi dải bài trên site hiện sai nội dung.
 *
 * @param WP_Query $query Truy vấn đang chuẩn bị chạy.
 */
function nntm_hanh_gia_loai_khoi_truy_van( WP_Query $query ): void {
	// Trang quản trị giữ nguyên — BQT phải thấy và sửa được bài.
	if ( is_admin() ) {
		return;
	}

	if ( nntm_duoc_xem_khu_han_che() ) {
		return;
	}

	// Truy vấn này có thể chạm tới nntm_article không?
	$post_type = $query->get( 'post_type' );

	$co_the_cham = false;
	if ( empty( $post_type ) || 'any' === $post_type ) {
		$co_the_cham = true; // tìm kiếm, feed, truy vấn không khai post type
	} elseif ( is_array( $post_type ) ) {
		$co_the_cham = in_array( 'nntm_article', $post_type, true );
	} else {
		$co_the_cham = ( 'nntm_article' === $post_type );
	}

	if ( ! $co_the_cham ) {
		return;
	}

	$slugs = nntm_term_khu_han_che();
	if ( empty( $slugs ) ) {
		return;
	}

	$dieu_kien = array(
		'taxonomy'         => 'nntm_section',
		'field'            => 'slug',
		'terms'            => $slugs,
		'operator'         => 'NOT IN',
		'include_children' => false,
	);

	$tax_query = $query->get( 'tax_query' );

	if ( empty( $tax_query ) || ! is_array( $tax_query ) ) {
		$tax_query = array( $dieu_kien );
	} else {
		// Giữ nguyên bộ lọc sẵn có của block, chỉ CỘNG THÊM điều kiện loại
		// trừ và buộc quan hệ AND để hai điều kiện cùng phải đúng.
		$tax_query['relation'] = 'AND';
		$tax_query[]           = $dieu_kien;
	}

	$query->set( 'tax_query', $tax_query );
}
add_action( 'pre_get_posts', 'nntm_hanh_gia_loai_khoi_truy_van' );

/* =========================================================================
 * 4. CSS riêng cho khu Hành Giả — chỉ nạp đúng trang cần.
 * ========================================================================= */

/**
 * Nạp bai-hanh-gia.css chỉ khi đang xem một bài `nntm_article` thuộc khu
 * Hành Giả — khuôn mẫu giống nntm_enqueue_r1_assets() ở inc/enqueue.php
 * (không sửa file đó vì nằm ngoài phạm vi việc này).
 */
function nntm_hanh_gia_enqueue_assets(): void {
	if ( ! is_singular( 'nntm_article' ) ) {
		return;
	}

	if ( null === nntm_bai_thuoc_hanh_gia( get_queried_object() ) ) {
		return;
	}

	$css_path = NNTM_THEME_DIR . '/assets/css/pages/bai-hanh-gia.css';
	wp_enqueue_style(
		'nntm-bai-hanh-gia',
		NNTM_THEME_URI . '/assets/css/pages/bai-hanh-gia.css',
		array( 'nntm-tokens', 'nntm-base' ),
		nntm_asset_version( $css_path )
	);
}
add_action( 'wp_enqueue_scripts', 'nntm_hanh_gia_enqueue_assets' );
