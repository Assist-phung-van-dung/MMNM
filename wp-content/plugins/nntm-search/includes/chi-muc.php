<?php
/**
 * Hàng đợi lập chỉ mục chạy nền + làm mới "bài chứa" — Phase 2.
 *
 * Trước đây ảnh/PDF được lập chỉ mục NGAY trong request tải lên. Ổn với vài
 * chục file; khách nhập hàng nghìn ảnh một lượt thì request treo, và kho nội
 * dung đã nhập ở Phase 1 thì chưa ai lập chỉ mục cả (docs/10 mục 10 việc 3).
 * Giờ: tải lên chỉ xếp hàng, WP-Cron mỗi phút xử lý trong khoảng 40 giây.
 *
 * Việc thứ hai, quan trọng không kém: GẮN LẠI BÀI CHỨA. Ảnh gần như luôn được
 * tải lên TRƯỚC rồi mới chèn vào bài, nên lúc lập chỉ mục chưa có bài nào dùng
 * nó → post_id = 0. Tìm bằng ảnh bỏ qua mọi vector post_id = 0
 * (nntm_search_group_by_post), tức là ảnh đó KHÔNG BAO GIỜ dẫn tới bài của nó.
 * PDF cũng vậy: gắn vào Ấn phẩm sau khi tải lên thì kết quả hiện tên file thay
 * vì tên ấn phẩm. Mỗi lần lưu bài giờ cập nhật lại post_id + quyền cho các ảnh,
 * PDF bài đó dùng — chỉ là UPDATE, không gọi lại dịch vụ Python.
 *
 * Mỗi file đang chờ mang meta _nntm_cm_cho = 'day_du' (lập chỉ mục đầy đủ, gọi
 * dịch vụ) hoặc 'quyen' (chỉ làm mới bài chứa + quyền).
 *
 * @package NNTM_Search
 */

defined( 'ABSPATH' ) || exit;

const NNTM_SEARCH_CM_CRON = 'nntm_search_chi_muc_lo';
const NNTM_SEARCH_CM_KHOA = 'nntm_search_chi_muc_dang_chay';
const NNTM_SEARCH_CM_DUNG = 'nntm_search_chi_muc_tam_dung';
const NNTM_SEARCH_CM_CHO  = '_nntm_cm_cho';
const NNTM_SEARCH_CM_LOI  = '_nntm_cm_loi';
const NNTM_SEARCH_CM_LAN  = '_nntm_cm_lan';

/** Định dạng ảnh dịch vụ Python đọc được (Pillow). SVG, AVIF, HEIC thì không. */
function nntm_search_cm_mime_anh(): array {
	return array( 'image/jpeg', 'image/png', 'image/webp', 'image/gif' );
}

/** 'anh' | 'pdf' | '' (không thuộc diện lập chỉ mục, hoặc tính năng đang tắt). */
function nntm_search_cm_loai( int $att ): string {
	$mime = (string) get_post_mime_type( $att );

	if ( in_array( $mime, nntm_search_cm_mime_anh(), true ) ) {
		return nntm_search_image_enabled() ? 'anh' : '';
	}

	if ( 'application/pdf' === $mime ) {
		return nntm_search_pdf_enabled() ? 'pdf' : '';
	}

	return '';
}

/* ---------- Xếp hàng ---------- */

/**
 * Đưa một file vào hàng đợi. 'day_du' thắng 'quyen' (lập chỉ mục đầy đủ đã
 * gồm luôn việc gắn bài chứa).
 *
 * @param int    $att  Attachment ID.
 * @param string $viec 'day_du' | 'quyen'.
 */
function nntm_search_cm_xep_hang( int $att, string $viec = 'day_du' ): bool {
	if ( '' === nntm_search_cm_loai( $att ) ) {
		return false;
	}

	if ( 'quyen' === $viec && 'day_du' === get_post_meta( $att, NNTM_SEARCH_CM_CHO, true ) ) {
		return true;
	}

	update_post_meta( $att, NNTM_SEARCH_CM_CHO, 'day_du' === $viec ? 'day_du' : 'quyen' );

	if ( 'day_du' === $viec ) {
		delete_post_meta( $att, NNTM_SEARCH_CM_LOI );
		delete_post_meta( $att, NNTM_SEARCH_CM_LAN );
	}

	nntm_search_cm_hen();

	return true;
}

/**
 * Xếp hàng hàng loạt.
 *
 * @param string $pham_vi 'thieu' (chưa có chỉ mục, kể cả lần trước lỗi) |
 *                        'quyen' (mọi file đã có chỉ mục: gắn lại bài chứa) |
 *                        'tat_ca' (làm lại toàn bộ — vd. sau khi đổi model).
 * @return int Số file đã xếp.
 */
function nntm_search_cm_xep_hang_loat( string $pham_vi ): int {
	$ids = nntm_search_cm_chon( $pham_vi );
	$viec = 'quyen' === $pham_vi ? 'quyen' : 'day_du';

	if ( function_exists( 'set_time_limit' ) ) {
		@set_time_limit( 300 ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
	}

	$n = 0;
	foreach ( $ids as $id ) {
		$n += nntm_search_cm_xep_hang( (int) $id, $viec ) ? 1 : 0;
	}

	return $n;
}

/**
 * @return int[]
 */
function nntm_search_cm_chon( string $pham_vi ): array {
	global $wpdb;

	$mime_anh = nntm_search_image_enabled() ? nntm_search_cm_mime_anh() : array();
	$mime_pdf = nntm_search_pdf_enabled() ? array( 'application/pdf' ) : array();
	$v        = nntm_search_table_vectors();
	$p        = nntm_search_table_pdf_pages();
	$ids      = array();

	// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
	if ( $mime_anh ) {
		$in   = implode( ',', array_fill( 0, count( $mime_anh ), '%s' ) );
		$loc  = "a.post_type = 'attachment' AND a.post_mime_type IN ({$in})";
		$cau  = array(
			// Chưa có vector của model HIỆN TẠI — đổi model thì ảnh cũ tính là thiếu.
			'thieu'  => array( "SELECT a.ID FROM {$wpdb->posts} a LEFT JOIN {$v} v ON v.attachment_id = a.ID AND v.model = %s WHERE {$loc} AND v.attachment_id IS NULL", array_merge( array( nntm_search_model() ), $mime_anh ) ),
			'quyen'  => array( "SELECT a.ID FROM {$wpdb->posts} a INNER JOIN {$v} v ON v.attachment_id = a.ID WHERE {$loc}", $mime_anh ),
			'tat_ca' => array( "SELECT a.ID FROM {$wpdb->posts} a WHERE {$loc}", $mime_anh ),
		);
		list( $sql, $tham_so ) = $cau[ $pham_vi ] ?? $cau['thieu'];
		$ids = array_merge( $ids, $wpdb->get_col( $wpdb->prepare( $sql, $tham_so ) ) );
	}

	if ( $mime_pdf ) {
		$sql = array(
			'thieu'  => "SELECT a.ID FROM {$wpdb->posts} a WHERE a.post_type = 'attachment' AND a.post_mime_type = 'application/pdf' AND NOT EXISTS ( SELECT 1 FROM {$p} p WHERE p.attachment_id = a.ID )",
			'quyen'  => "SELECT DISTINCT a.ID FROM {$wpdb->posts} a INNER JOIN {$p} p ON p.attachment_id = a.ID WHERE a.post_type = 'attachment' AND a.post_mime_type = 'application/pdf'",
			'tat_ca' => "SELECT a.ID FROM {$wpdb->posts} a WHERE a.post_type = 'attachment' AND a.post_mime_type = 'application/pdf'",
		);
		$ids = array_merge( $ids, $wpdb->get_col( $sql[ $pham_vi ] ?? $sql['thieu'] ) );
	}
	// phpcs:enable

	return array_values( array_unique( array_map( 'intval', $ids ) ) );
}

/* ---------- Cron ---------- */

add_action( NNTM_SEARCH_CM_CRON, 'nntm_search_cm_chay_lo' );

function nntm_search_cm_hen(): void {
	if ( ! wp_next_scheduled( NNTM_SEARCH_CM_CRON ) ) {
		// Lịch 'nntm_search_moi_phut' khai trong ocr.php.
		wp_schedule_event( time() + 5, 'nntm_search_moi_phut', NNTM_SEARCH_CM_CRON );
	}
}

function nntm_search_cm_ke_tiep(): int {
	$ids = get_posts(
		array(
			'post_type'        => 'attachment',
			'post_status'      => 'any',
			'posts_per_page'   => 1,
			'orderby'          => 'ID',
			'order'            => 'ASC',
			'fields'           => 'ids',
			'no_found_rows'    => true,
			'suppress_filters' => true,
			'meta_key'         => NNTM_SEARCH_CM_CHO, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_query_meta_key
		)
	);

	return $ids ? (int) $ids[0] : 0;
}

/** @return array{den:int,ly_do:string}|null */
function nntm_search_cm_dang_tam_dung(): ?array {
	$d = get_option( NNTM_SEARCH_CM_DUNG );

	return is_array( $d ) && (int) ( $d['den'] ?? 0 ) > time() ? $d : null;
}

/**
 * Xử lý hàng đợi trong một khoảng thời gian.
 *
 * @param int $giay Ngân sách thời gian (giây). 0 = chạy tới hết hàng (dòng lệnh).
 * @return int Số file đã xử lý xong (thành công hoặc hết lượt thử).
 */
function nntm_search_cm_chay_lo( int $giay = 40 ): int {
	if ( ! nntm_search_pdf_enabled() && ! nntm_search_image_enabled() ) {
		wp_clear_scheduled_hook( NNTM_SEARCH_CM_CRON );
		return 0;
	}

	if ( nntm_search_cm_dang_tam_dung() ) {
		return 0;
	}

	if ( ! add_option( NNTM_SEARCH_CM_KHOA, time(), '', false ) ) {
		if ( (int) get_option( NNTM_SEARCH_CM_KHOA ) > time() - 10 * MINUTE_IN_SECONDS ) {
			return 0;
		}
		update_option( NNTM_SEARCH_CM_KHOA, time(), false );
	}

	$het_gio  = $giay > 0 ? microtime( true ) + $giay : PHP_FLOAT_MAX;
	$da_xu_ly = 0;
	$da_thu   = array();

	try {
		if ( function_exists( 'set_time_limit' ) ) {
			@set_time_limit( $giay > 0 ? $giay + 150 : 0 ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		}

		while ( microtime( true ) < $het_gio ) {
			$att = nntm_search_cm_ke_tiep();

			// Cùng một file quay lại trong một lượt = nó vừa được xếp lại (vd. lỗi
			// tạm) — dừng lượt này thay vì quay vòng.
			if ( ! $att || isset( $da_thu[ $att ] ) ) {
				break;
			}
			$da_thu[ $att ] = true;

			$kq = nntm_search_cm_xu_ly( $att );

			if ( 'tam_dung' === $kq ) {
				break;
			}
			if ( 'xong' === $kq || 'loi' === $kq ) {
				++$da_xu_ly;
			}
		}

		if ( ! nntm_search_cm_ke_tiep() ) {
			wp_clear_scheduled_hook( NNTM_SEARCH_CM_CRON );
		}
	} finally {
		delete_option( NNTM_SEARCH_CM_KHOA );
	}

	return $da_xu_ly;
}

/**
 * Xử lý một file.
 *
 * @return string 'xong' | 'thu_lai' | 'loi' | 'tam_dung' | 'bo'.
 */
function nntm_search_cm_xu_ly( int $att ): string {
	$viec = (string) get_post_meta( $att, NNTM_SEARCH_CM_CHO, true );
	$loai = nntm_search_cm_loai( $att );

	// Tính năng vừa bị tắt, hoặc đổi mime — bỏ khỏi hàng, không coi là lỗi.
	if ( '' === $loai ) {
		delete_post_meta( $att, NNTM_SEARCH_CM_CHO );
		return 'bo';
	}

	if ( 'quyen' === $viec ) {
		nntm_search_cm_lam_moi_so_huu( $att );
		delete_post_meta( $att, NNTM_SEARCH_CM_CHO );
		return 'xong';
	}

	$kq = 'anh' === $loai ? nntm_search_index_image( $att ) : nntm_search_index_pdf( $att );

	if ( ! is_wp_error( $kq ) && false !== $kq ) {
		delete_post_meta( $att, NNTM_SEARCH_CM_CHO );
		delete_post_meta( $att, NNTM_SEARCH_CM_LOI );
		delete_post_meta( $att, NNTM_SEARCH_CM_LAN );
		return 'xong';
	}

	$ma     = is_wp_error( $kq ) ? $kq->get_error_code() : 'nntm_store_failed';
	$status = is_wp_error( $kq ) ? (int) ( ( (array) $kq->get_error_data() )['status'] ?? 0 ) : 0;
	$loi_mt = in_array( $ma, array( 'nntm_service_failed', 'nntm_pdf_service' ), true );

	// Dịch vụ tắt / quá tải / chưa nạp xong model: lỗi của MÁY, không phải của
	// file — dừng cả hàng 5 phút, file giữ nguyên chỗ, không tính lượt thử.
	if ( $loi_mt && ( 0 === $status || $status >= 500 ) ) {
		update_option(
			NNTM_SEARCH_CM_DUNG,
			array(
				'den'   => time() + 5 * MINUTE_IN_SECONDS,
				/* translators: %d: mã HTTP */
				'ly_do' => 0 === $status ? __( 'Dịch vụ Python không phản hồi.', 'nntm' ) : sprintf( __( 'Dịch vụ Python báo lỗi %d.', 'nntm' ), $status ),
			),
			false
		);
		return 'tam_dung';
	}

	// Lỗi của chính file (hỏng, mất, dịch vụ trả 4xx): thử tối đa 3 lần.
	$lan = (int) get_post_meta( $att, NNTM_SEARCH_CM_LAN, true ) + 1;
	update_post_meta( $att, NNTM_SEARCH_CM_LAN, $lan );
	update_post_meta( $att, NNTM_SEARCH_CM_LOI, is_wp_error( $kq ) ? $kq->get_error_message() : __( 'Không lưu được chỉ mục.', 'nntm' ) );

	if ( $lan >= 3 || in_array( $ma, array( 'nntm_file_missing', 'nntm_pdf_unreadable', 'nntm_pdf_empty' ), true ) ) {
		delete_post_meta( $att, NNTM_SEARCH_CM_CHO );
		return 'loi';
	}

	return 'thu_lai';
}

/* ---------- Gắn lại bài chứa + quyền ---------- */

/**
 * Cập nhật post_id / quyền / ngôn ngữ trong chỉ mục của một file, KHÔNG gọi
 * dịch vụ Python. File chưa có chỉ mục thì không làm gì.
 */
function nntm_search_cm_lam_moi_so_huu( int $att ): void {
	global $wpdb;

	$mime = (string) get_post_mime_type( $att );

	// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	if ( 'application/pdf' === $mime ) {
		$wpdb->update( nntm_search_table_pdf_pages(), array( 'post_id' => nntm_search_pdf_owner( $att ) ), array( 'attachment_id' => $att ), array( '%d' ), array( '%d' ) );
	} elseif ( in_array( $mime, nntm_search_cm_mime_anh(), true ) ) {
		$chu = nntm_search_image_owner( $att );
		$wpdb->update( nntm_search_table_vectors(), $chu, array( 'attachment_id' => $att ), array( '%d', '%s', '%s' ), array( '%d' ) );
	}
	// phpcs:enable
}

/**
 * Ảnh và PDF mà một bài đang dùng, hoặc chỉ mục đang ghi là của bài này
 * (để ảnh đã bị gỡ khỏi bài được gắn lại chỗ khác / về mồ côi).
 *
 * Cùng quy tắc với nntm_search_post_using_image(): ảnh đại diện, block
 * `"imageId":N`, class `wp-image-N` của trình soạn thảo cổ điển.
 *
 * @return int[]
 */
function nntm_search_cm_file_cua_bai( WP_Post $post ): array {
	global $wpdb;

	$ids = array( (int) get_post_thumbnail_id( $post ), (int) get_post_meta( $post->ID, '_nntm_pdf_file', true ) );

	if ( preg_match_all( '/"imageId":(\d+)|wp-image-(\d+)/', (string) $post->post_content, $m ) ) {
		foreach ( array_merge( $m[1], $m[2] ) as $so ) {
			$ids[] = (int) $so;
		}
	}

	// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	$ids = array_merge(
		$ids,
		$wpdb->get_col( $wpdb->prepare( 'SELECT attachment_id FROM ' . nntm_search_table_vectors() . ' WHERE post_id = %d', $post->ID ) ),
		$wpdb->get_col( $wpdb->prepare( 'SELECT DISTINCT attachment_id FROM ' . nntm_search_table_pdf_pages() . ' WHERE post_id = %d', $post->ID ) )
	);
	// phpcs:enable

	return array_values( array_filter( array_unique( array_map( 'intval', $ids ) ) ) );
}

/**
 * Sau khi lưu bài (kể cả term + meta — wp_after_insert_post chạy SAU khi REST
 * đã gán chuyên mục, còn save_post thì chạy trước): gắn lại bài chứa cho các
 * file bài dùng. Ít file thì làm ngay; nhiều (bộ sưu tập ảnh) thì đẩy vào hàng
 * đợi, không bắt người soạn bài chờ.
 *
 * @param int     $post_id Post ID.
 * @param WP_Post $post    Post.
 */
function nntm_search_cm_sau_khi_luu( int $post_id, $post ): void {
	if ( ! $post instanceof WP_Post || wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
		return;
	}
	if ( in_array( $post->post_type, array( 'attachment', 'revision', 'nav_menu_item', 'customize_changeset' ), true ) ) {
		return;
	}

	$files = nntm_search_cm_file_cua_bai( $post );

	foreach ( $files as $att ) {
		if ( count( $files ) > 10 ) {
			nntm_search_cm_xep_hang( $att, 'quyen' );
		} else {
			nntm_search_cm_lam_moi_so_huu( $att );
		}
	}
}
add_action( 'wp_after_insert_post', 'nntm_search_cm_sau_khi_luu', 20, 2 );

/** Bài bị xoá / bỏ vào thùng rác: file của nó cần tìm chủ mới (hoặc về mồ côi). */
function nntm_search_cm_bai_bi_go( int $post_id ): void {
	$post = get_post( $post_id );

	if ( ! $post instanceof WP_Post || 'attachment' === $post->post_type ) {
		return;
	}

	foreach ( nntm_search_cm_file_cua_bai( $post ) as $att ) {
		nntm_search_cm_xep_hang( $att, 'quyen' );
	}
}
add_action( 'wp_trash_post', 'nntm_search_cm_bai_bi_go' );
add_action( 'before_delete_post', 'nntm_search_cm_bai_bi_go' );

/* ---------- Thống kê cho màn quản trị ---------- */

/**
 * @return array<string,int>
 */
function nntm_search_cm_thong_ke(): array {
	global $wpdb;

	$v        = nntm_search_table_vectors();
	$p        = nntm_search_table_pdf_pages();
	$mime_anh = nntm_search_cm_mime_anh();
	$in       = implode( ',', array_fill( 0, count( $mime_anh ), '%s' ) );

	// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
	$tk = array(
		'anh_tong'     => (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'attachment' AND post_mime_type IN ({$in})", $mime_anh ) ),
		'anh_co'       => (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$v} WHERE model = %s", nntm_search_model() ) ),
		'anh_mo_coi'   => (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$v} WHERE model = %s AND post_id = 0", nntm_search_model() ) ),
		'pdf_tong'     => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'attachment' AND post_mime_type = 'application/pdf'" ),
		'pdf_co'       => (int) $wpdb->get_var( "SELECT COUNT(DISTINCT attachment_id) FROM {$p}" ),
		'pdf_trang'    => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$p}" ),
		'pdf_mo_coi'   => (int) $wpdb->get_var( "SELECT COUNT(DISTINCT attachment_id) FROM {$p} WHERE post_id = 0" ),
		'cho'          => (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key = %s", NNTM_SEARCH_CM_CHO ) ),
		'loi'          => (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->postmeta} m WHERE m.meta_key = %s AND NOT EXISTS ( SELECT 1 FROM {$wpdb->postmeta} c WHERE c.post_id = m.post_id AND c.meta_key = %s )", NNTM_SEARCH_CM_LOI, NNTM_SEARCH_CM_CHO ) ),
		'ocr_cho'      => 0,
	);

	foreach ( $wpdb->get_col( "SELECT meta_value FROM {$wpdb->postmeta} WHERE meta_key = '_nntm_ocr_cho'" ) as $raw ) {
		$ds             = maybe_unserialize( $raw );
		$tk['ocr_cho'] += is_array( $ds ) ? count( $ds ) : 0;
	}
	// phpcs:enable

	return $tk;
}
