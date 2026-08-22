<?php
/**
 * Trang đọc ấn phẩm — toàn màn hình, tách khỏi trang chi tiết.
 *
 * Đường dẫn: /an-pham/{slug}/doc/
 *
 * VÌ SAO LÀ TRANG RIÊNG, KHÔNG PHẢI POPUP TRÊN TRANG CHI TIẾT:
 * trang chi tiết là chỗ giới thiệu (bìa, mô tả, nút yêu thích) — người ta ghé
 * qua rồi đi. Còn đọc sách là việc kéo dài hàng chục phút: cần hết màn hình,
 * cần link chia sẻ được, cần quay lại đúng chỗ đang đọc. Popup không có URL
 * riêng nên mất cả ba.
 *
 * ⚠️ MỘT GIỚI HẠN CỦA PDF, KHÔNG PHẢI THIẾU SÓT:
 * ấn phẩm ở đây là PDF — bố cục cố định. Không thể "đổi cỡ chữ" cho chữ chảy
 * lại như trình đọc EPUB. Thay vào đó là thu/phóng và chọn một trang / hai
 * trang. Muốn chữ chảy lại thì phải đổi nguồn nội dung sang EPUB/HTML, đó là
 * việc khác hẳn.
 *
 * Bám docs/04-kien-truc.md mục 4: trình đọc là PHP template (khách không sửa
 * bố cục), không có nút tải/in trong trình đọc, có watermark tên người đọc.
 *
 * @package NNTM
 */

defined( 'ABSPATH' ) || exit;

/** Tên endpoint gắn sau permalink ấn phẩm. */
const NNTM_DOC_ENDPOINT = 'doc';

/**
 * Phiên bản luật đường dẫn của trình đọc — tăng lên khi đổi/thêm endpoint.
 */
const NNTM_DOC_REWRITE_VERSION = '1';

/**
 * Đăng ký endpoint /doc/ sau permalink.
 *
 * `add_rewrite_endpoint()` chỉ KHAI BÁO luật; luật đang chạy thật nằm trong
 * option `rewrite_rules` của cơ sở dữ liệu. Kéo code mới về là có hàm này
 * nhưng option vẫn là bản cũ, nên /an-pham/<slug>/doc/ trả 404 cho tới khi ai
 * đó vào Cài đặt → Đường dẫn tĩnh → Lưu. Không ai nhớ bước đó lúc deploy, và
 * khi quên thì lỗi trông y như trình đọc chưa được viết — nên tự dựng lại luật
 * đúng MỘT lần, canh bằng option version. Không flush mỗi lần init: flush là
 * ghi lại toàn bộ bảng luật, làm mọi lượt tải trang chậm đi.
 */
function nntm_doc_dang_ky_endpoint(): void {
	add_rewrite_endpoint( NNTM_DOC_ENDPOINT, EP_PERMALINK );

	if ( NNTM_DOC_REWRITE_VERSION !== get_option( 'nntm_doc_rewrite_version' ) ) {
		flush_rewrite_rules();
		update_option( 'nntm_doc_rewrite_version', NNTM_DOC_REWRITE_VERSION );
	}
}
add_action( 'init', 'nntm_doc_dang_ky_endpoint' );

/**
 * Đang ở trang đọc hay không.
 *
 * `get_query_var()` trả chuỗi rỗng cho cả hai trường hợp "endpoint có mặt mà
 * không kèm giá trị" (/doc/) và "không có endpoint" — nên phải kiểm bằng isset
 * trên query_vars, không dùng empty().
 *
 * @return bool
 */
function nntm_dang_o_trang_doc(): bool {
	global $wp_query;

	if ( ! is_singular( 'nntm_publication' ) ) {
		return false;
	}

	return isset( $wp_query->query_vars[ NNTM_DOC_ENDPOINT ] );
}

/**
 * URL trang đọc của một ấn phẩm.
 *
 * Ấn phẩm CHƯA gắn tệp PDF vẫn có trang đọc: mở ra đủ bộ khung (thanh trên, cột
 * giới thiệu, hai nút lật, thanh dưới), chỉ riêng chỗ trang sách để trống. Chủ
 * dự án chốt 22/08/2026 — thà một khung sách rỗng còn hơn hai lối đi khác nhau
 * tuỳ theo ấn phẩm đã có tệp hay chưa, vì người xem không biết trước điều đó.
 *
 * @param int|WP_Post|null $post Ấn phẩm.
 * @return string Rỗng nếu không tìm ra ấn phẩm.
 */
function nntm_doc_url( $post = null ): string {
	$post = get_post( $post );

	if ( ! $post ) {
		return '';
	}

	return trailingslashit( trailingslashit( (string) get_permalink( $post ) ) . NNTM_DOC_ENDPOINT );
}

/**
 * Chặn lối vào trang đọc khi chưa được phép.
 *
 * Hai trường hợp, hai xử lý khác nhau — gộp thành một câu "không xem được" là
 * làm người đọc bí, không biết phải làm gì tiếp:
 *   - ấn phẩm bị khoá   → về trang chi tiết (ở đó có khối mời thanh toán)
 *   - cổng quyền đòi đăng nhập → sang trang đăng nhập, kèm đường về
 *
 * CHƯA GẮN TỆP THÌ KHÔNG CÒN CHẶN. Trước đây trường hợp này bị đẩy về trang chi
 * tiết; giờ vào được, khung sách để trống (xem nntm_doc_url()). Quyền đọc và
 * việc có tệp là hai chuyện khác nhau — trộn vào một chỗ thì ấn phẩm mở nhưng
 * thiếu tệp lại bị xử như ấn phẩm bị khoá.
 */
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

/**
 * Vào trang chi tiết ấn phẩm thì sang thẳng trang đọc.
 *
 * Chủ dự án chốt 22/08/2026: ấn phẩm là để ĐỌC, không phải để xem giới thiệu
 * rồi bấm thêm một nút nữa. Phần giới thiệu (bìa, tên, mô tả) đã có sẵn ở cột
 * trái của trình đọc, nên trang chi tiết thành một chặng dừng vô nghĩa.
 *
 * BA CHỖ DỄ TỰ BẮN VÀO CHÂN, nên chốt rõ ở đây:
 *
 * 1. CHỈ chuyển khi người xem THẬT SỰ được đọc. Ấn phẩm bị khoá thì ở lại trang
 *    chi tiết — vì nntm_doc_chan_quyen() lại đẩy /doc/ về trang chi tiết, hai
 *    bên đá nhau thành vòng lặp chuyển hướng vô hạn, trình duyệt báo
 *    ERR_TOO_MANY_REDIRECTS. Đó cũng đúng nghiệp vụ: trang chi tiết là nơi đặt
 *    lời mời thanh toán.
 *
 * 2. Dùng 302, KHÔNG dùng 301. Việc chuyển hay không phụ thuộc trạng thái khoá
 *    và việc đã thanh toán — hai thứ thay đổi được. 301 bị trình duyệt nhớ vĩnh
 *    viễn, nên ngày ấn phẩm bị khoá lại thì máy người đã ghé vẫn lao vào /doc/
 *    mà không hỏi máy chủ nữa.
 *
 * 3. Chừa đường xem trang chi tiết: thêm `?chi-tiet=1`. Không có nó thì trang
 *    chi tiết của ấn phẩm mở thành ra không ai xem được nữa, kể cả người đang
 *    làm khối thanh toán trên chính trang đó.
 */
function nntm_an_pham_chuyen_sang_trang_doc(): void {
	if ( is_admin() || ! is_singular( 'nntm_publication' ) ) {
		return;
	}

	// Đang ở /doc/ rồi — chính nó cũng là is_singular( 'nntm_publication' ).
	if ( nntm_dang_o_trang_doc() ) {
		return;
	}

	if ( is_feed() || is_embed() || is_preview() || is_customize_preview() ) {
		return;
	}

	if ( isset( $_GET['chi-tiet'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- chi doc, khong doi du lieu.
		return;
	}

	$post = get_queried_object();

	if ( ! $post instanceof WP_Post ) {
		return;
	}

	/** Tắt hẳn hành vi này ở nơi khác nếu cần, không phải sửa file. */
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

/**
 * Dùng template riêng cho trang đọc.
 *
 * @param string $template Template WordPress đã chọn.
 * @return string
 */
function nntm_doc_chon_template( string $template ): string {
	if ( ! nntm_dang_o_trang_doc() ) {
		return $template;
	}

	$rieng = NNTM_THEME_DIR . '/template-doc-sach.php';

	return is_readable( $rieng ) ? $rieng : $template;
}
add_filter( 'template_include', 'nntm_doc_chon_template' );

/**
 * Nạp CSS/JS của trình đọc, chỉ ở trang đọc.
 */
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

	/*
	 * Hai thư viện đọc PDF nặng hơn 1,3 MB — chỉ nạp khi thật có tệp để đọc.
	 * Ấn phẩm chưa gắn tệp vẫn nạp doc-sach.js (không phụ thuộc thư viện nào) để
	 * thanh công cụ, đổi nền và toàn màn hình vẫn chạy như thường; nó tự nhận ra
	 * pdfUrl rỗng và để trống khung sách.
	 */
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
			// Rỗng = ấn phẩm chưa gắn tệp; doc-sach.js dựa vào đây để bỏ bước đọc tệp.
			'pdfUrl'    => $co_tep ? $pdf_url : '',
			'workerUrl' => NNTM_THEME_URI . '/assets/vendor/pdfjs/pdf.worker.min.js',
			'objectId'  => $post->ID,
			'ajaxUrl'   => admin_url( 'admin-ajax.php' ),
			'nonce'     => wp_create_nonce( 'nntm_doc_tien_do' ),
			'viTri'     => nntm_doc_lay_vi_tri( $post->ID ),
			'dangNhap'  => is_user_logged_in(),
			/*
			 * Watermark vẽ ở TRÌNH DUYỆT, không đóng dấu sẵn vào tệp trên máy
			 * chủ — kiến trúc mục 4: đóng dấu ở máy chủ thì mỗi người đọc sinh
			 * một bản PDF riêng, CDN không cache được gì, CPU tăng vọt.
			 */
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

/**
 * Bỏ CSS/JS chung của site ở trang đọc — trình đọc chiếm hết màn hình, không
 * dùng đến header/footer/block nào. Cùng khuôn với trang đăng nhập.
 */
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

/**
 * Ẩn thanh admin của WordPress ở trang đọc.
 *
 * Thanh đó `position: fixed` với z-index 99999 và ép `html { margin-top: 32px }`.
 * Trình đọc lại là một khối `position: fixed; inset: 0` — lề của html không đẩy
 * nó xuống, nên thanh admin nằm ĐÈ lên thanh công cụ của trình đọc, che mất nút
 * thoát và tên sách. Chỉ người đã đăng nhập thấy lỗi này, nên rất dễ bỏ sót khi
 * thử bằng cửa sổ ẩn danh.
 *
 * Toàn màn hình thì không nên có thanh của hệ thống — giống lúc mở một trình đọc
 * hay trình phát nào khác.
 *
 * @param bool $hien Giá trị WordPress đang định dùng.
 * @return bool
 */
function nntm_doc_an_admin_bar( $hien ) {
	return nntm_dang_o_trang_doc() ? false : $hien;
}
add_filter( 'show_admin_bar', 'nntm_doc_an_admin_bar' );

/* =========================================================================
 * Nhớ chỗ đang đọc — bảng wp_nntm_reading_progress do nntm-core tạo.
 * ========================================================================= */

/**
 * Tên bảng tiến độ đọc.
 *
 * @return string
 */
function nntm_doc_bang_tien_do(): string {
	global $wpdb;

	return $wpdb->prefix . 'nntm_reading_progress';
}

/**
 * Trang đang đọc dở của người hiện tại.
 *
 * Khách chưa đăng nhập thì KHÔNG tạo bản ghi nào ở máy chủ — JS tự nhớ trong
 * localStorage. Không sinh dữ liệu cho người vô danh.
 *
 * @param int $object_id ID ấn phẩm.
 * @return int Số trang, 0 nếu chưa có.
 */
function nntm_doc_lay_vi_tri( int $object_id ): int {
	global $wpdb;

	if ( get_current_user_id() <= 0 ) {
		return 0;
	}

	$bang = nntm_doc_bang_tien_do();

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	return (int) $wpdb->get_var(
		$wpdb->prepare(
			"SELECT position FROM {$bang} WHERE user_id = %d AND object_id = %d AND object_type = %s LIMIT 1",
			get_current_user_id(),
			$object_id,
			'publication'
		)
	);
}

/**
 * Ghi lại trang đang đọc.
 */
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

	/*
	 * Không ghi tiến độ cho ấn phẩm mà chính người này không được đọc. Thiếu
	 * dòng này thì bảng tiến độ thành nơi suy ra ai đã thử mở cuốn nào — rò
	 * gián tiếp, và ai đó có thể bơm bản ghi cho ấn phẩm họ chưa mua.
	 */
	if ( ! nntm_an_pham_can_access( $object_id ) ) {
		wp_send_json_error( array( 'message' => __( 'Không có quyền.', 'nntm' ) ), 403 );
	}

	global $wpdb;

	$bang    = nntm_doc_bang_tien_do();
	$user_id = get_current_user_id();

	// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
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
	// phpcs:enable

	wp_send_json_success( array( 'trang' => $trang ) );
}
add_action( 'wp_ajax_nntm_doc_tien_do', 'nntm_doc_ajax_luu_tien_do' );
