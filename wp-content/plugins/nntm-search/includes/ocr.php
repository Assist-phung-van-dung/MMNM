<?php
/**
 * OCR tiếng Việt cho trang PDF scan — khảo sát câu 11–12.
 *
 * Trang không có lớp chữ (dịch vụ Python đánh dấu "trong") được xếp hàng ngay
 * lúc lập chỉ mục PDF, rồi WP-Cron gửi dần từng lô vài trang sang /pdf/ocr.
 * Không OCR ngay trong request tải lên: mỗi trang mất vài giây, cuốn sách scan
 * 300 trang là cả chục phút.
 *
 * Kết quả vào đúng bảng nntm_pdf_pages với source = 'ocr' — tìm kiếm không
 * phải sửa gì, kết quả vẫn trỏ đúng trang. Trang kết quả ghi thêm "chữ nhận
 * dạng từ bản scan" để người đọc biết vì sao đoạn trích có thể sai chính tả.
 *
 *   NNTM_SEARCH_OCR_ENABLED=false         tắt hẳn OCR (.env của plugin)
 *   NNTM_SEARCH_OCR_PAGES_PER_RUN=3       số trang mỗi lần cron (1–10)
 *
 * @package NNTM_Search
 */

defined( 'ABSPATH' ) || exit;

const NNTM_SEARCH_OCR_CRON  = 'nntm_search_ocr_lo';
const NNTM_SEARCH_OCR_KHOA  = 'nntm_search_ocr_dang_chay';
const NNTM_SEARCH_OCR_DUNG  = 'nntm_search_ocr_tam_dung';

/** OCR có bật không. Tắt tìm trong PDF thì OCR cũng tắt theo. */
function nntm_search_ocr_enabled(): bool {
	if ( ! nntm_search_pdf_enabled() ) {
		return false;
	}

	return defined( 'NNTM_SEARCH_OCR_ENABLED' )
		? (bool) NNTM_SEARCH_OCR_ENABLED
		: nntm_search_env_bool( 'NNTM_SEARCH_OCR_ENABLED' );
}

/** Số trang mỗi lần cron. Ít thôi: một lô phải xong trước khi PHP hết giờ. */
function nntm_search_ocr_so_trang_moi_lan(): int {
	$v = getenv( 'NNTM_SEARCH_OCR_PAGES_PER_RUN' );

	return max( 1, min( 10, false === $v || '' === trim( $v ) ? 3 : (int) $v ) );
}

/**
 * Độ tin cậy tối thiểu (0–100) của Tesseract để giữ chữ một trang. Dưới mức
 * này thường là ảnh minh hoạ, hoa văn, trang trắng lốm đốm — giữ lại chỉ làm
 * tìm kiếm ra kết quả rác.
 */
function nntm_search_ocr_nguong(): float {
	return (float) apply_filters( 'nntm_search_ocr_nguong_tin_cay', 30.0 );
}

/**
 * Đọc meta danh sách trang. get_post_meta() trả '' khi chưa có meta, mà
 * (array) '' là [''] — đếm ra 1 trang "đang chờ" không có thật.
 *
 * @return int[]
 */
function nntm_search_ocr_mang( int $att, string $khoa ): array {
	$v = get_post_meta( $att, $khoa, true );

	return is_array( $v ) ? array_values( array_map( 'intval', $v ) ) : array();
}

/**
 * Trang OCR có được khớp BỎ DẤU khi người dùng gõ có dấu không. Mặc định có:
 * dấu do máy đọc không đáng tin bằng dấu đánh máy (xem pdf.php,
 * nntm_search_pdf_filter_results). Tắt bằng
 * add_filter( 'nntm_search_ocr_khop_bo_dau', '__return_false' ).
 */
function nntm_search_ocr_khop_bo_dau(): bool {
	return (bool) apply_filters( 'nntm_search_ocr_khop_bo_dau', true );
}

/* ---------- Xếp hàng ---------- */

/**
 * Xếp các trang scan của một PDF vào hàng đợi OCR. Gọi từ nntm_search_index_pdf().
 * Trang đã có chữ OCR từ lần trước thì không làm lại.
 *
 * @param int   $attachment_id PDF.
 * @param int[] $trang         Số trang không có lớp chữ.
 */
function nntm_search_ocr_xep_hang( int $attachment_id, array $trang ): void {
	global $wpdb;

	if ( ! nntm_search_ocr_enabled() ) {
		return;
	}

	$trang = array_values( array_unique( array_filter( array_map( 'intval', $trang ), static fn( $n ) => $n > 0 ) ) );
	sort( $trang );

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	$da_co = array_map( 'intval', (array) $wpdb->get_col( $wpdb->prepare( 'SELECT page_no FROM ' . nntm_search_table_pdf_pages() . " WHERE attachment_id = %d AND source = 'ocr'", $attachment_id ) ) );
	$cho   = array_values( array_diff( $trang, $da_co ) );

	if ( ! $trang ) {
		// PDF chữ đánh máy hoàn toàn — không có gì để OCR, dọn dấu vết cũ nếu có.
		delete_post_meta( $attachment_id, '_nntm_ocr_cho' );
		delete_post_meta( $attachment_id, '_nntm_ocr_trang_thai' );
		return;
	}

	update_post_meta( $attachment_id, '_nntm_ocr_cho', $cho );
	update_post_meta( $attachment_id, '_nntm_ocr_tong', count( $trang ) );
	update_post_meta( $attachment_id, '_nntm_ocr_xong', count( $trang ) - count( $cho ) );
	update_post_meta( $attachment_id, '_nntm_ocr_loi', array() );
	update_post_meta( $attachment_id, '_nntm_ocr_trang_thai', $cho ? 'cho' : 'xong' );

	if ( $cho ) {
		nntm_search_ocr_hen();
	}
}

/* ---------- Cron ---------- */

add_filter(
	'cron_schedules',
	static function ( array $lich ): array {
		$lich['nntm_search_moi_phut'] = array(
			'interval' => MINUTE_IN_SECONDS,
			'display'  => 'Mỗi phút (NNTM OCR)',
		);
		return $lich;
	}
);

add_action( NNTM_SEARCH_OCR_CRON, 'nntm_search_ocr_chay_lo' );

function nntm_search_ocr_hen(): void {
	if ( ! wp_next_scheduled( NNTM_SEARCH_OCR_CRON ) ) {
		wp_schedule_event( time() + 10, 'nntm_search_moi_phut', NNTM_SEARCH_OCR_CRON );
	}
}

/** PDF kế tiếp còn trang chờ OCR. */
function nntm_search_ocr_pdf_ke_tiep(): int {
	$ids = get_posts(
		array(
			'post_type'        => 'attachment',
			'post_status'      => 'inherit',
			'post_mime_type'   => 'application/pdf',
			'posts_per_page'   => 1,
			'orderby'          => 'ID',
			'order'            => 'ASC',
			'fields'           => 'ids',
			'no_found_rows'    => true,
			'suppress_filters' => true,
			'meta_key'         => '_nntm_ocr_trang_thai', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_query_meta_key
			'meta_value'       => 'cho', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_query_meta_value
		)
	);

	return $ids ? (int) $ids[0] : 0;
}

/**
 * Tạm dừng OCR một lúc (dịch vụ chưa cài Tesseract, hoặc không phản hồi) — gọi
 * dồn mỗi phút vào một dịch vụ đang hỏng chỉ đầy log mà không được gì.
 */
function nntm_search_ocr_tam_dung( int $giay, string $ly_do ): void {
	update_option(
		NNTM_SEARCH_OCR_DUNG,
		array(
			'den'   => time() + $giay,
			'ly_do' => $ly_do,
		),
		false
	);
}

/** @return array{den:int,ly_do:string}|null */
function nntm_search_ocr_dang_tam_dung(): ?array {
	$d = get_option( NNTM_SEARCH_OCR_DUNG );

	return is_array( $d ) && (int) ( $d['den'] ?? 0 ) > time() ? $d : null;
}

/**
 * Xử lý một lô trang. Chạy từ cron mỗi phút.
 *
 * @return int Số trang đã xử lý (kể cả trang OCR ra không đủ chữ).
 */
function nntm_search_ocr_chay_lo(): int {
	global $wpdb;

	if ( ! nntm_search_ocr_enabled() ) {
		wp_clear_scheduled_hook( NNTM_SEARCH_OCR_CRON );
		return 0;
	}

	if ( nntm_search_ocr_dang_tam_dung() ) {
		return 0;
	}

	// Khoá nguyên tử bằng add_option: một lô OCR dài hơn một phút thì cron lượt
	// sau không được chạy chồng lên cùng những trang đó.
	if ( ! add_option( NNTM_SEARCH_OCR_KHOA, time(), '', false ) ) {
		if ( (int) get_option( NNTM_SEARCH_OCR_KHOA ) > time() - 15 * MINUTE_IN_SECONDS ) {
			return 0;
		}
		update_option( NNTM_SEARCH_OCR_KHOA, time(), false );
	}

	$da_xu_ly = 0;

	try {
		$att = nntm_search_ocr_pdf_ke_tiep();

		if ( ! $att ) {
			wp_clear_scheduled_hook( NNTM_SEARCH_OCR_CRON );
			return 0;
		}

		if ( function_exists( 'set_time_limit' ) ) {
			@set_time_limit( 600 ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- có host chặn hàm này.
		}

		$cho = nntm_search_ocr_mang( $att, '_nntm_ocr_cho' );
		$lo  = array_slice( $cho, 0, nntm_search_ocr_so_trang_moi_lan() );
		$loi = nntm_search_ocr_mang( $att, '_nntm_ocr_loi' );

		$path = get_attached_file( $att );
		if ( ! $path || ! is_readable( $path ) || ! $lo ) {
			update_post_meta( $att, '_nntm_ocr_loi', array_values( array_unique( array_merge( $loi, $cho ) ) ) );
			update_post_meta( $att, '_nntm_ocr_cho', array() );
			update_post_meta( $att, '_nntm_ocr_trang_thai', 'loi' );
			return 0;
		}

		$kq = nntm_search_post_file(
			'/pdf/ocr',
			$path,
			'tep',
			min( 540, 60 + 90 * count( $lo ) ),
			array( 'trang' => implode( ',', $lo ) )
		);

		if ( is_wp_error( $kq ) ) {
			$du_lieu = $kq->get_error_data();
			$status  = is_array( $du_lieu ) ? (int) ( $du_lieu['status'] ?? 0 ) : 0;

			if ( 503 === $status ) {
				nntm_search_ocr_tam_dung( 30 * MINUTE_IN_SECONDS, __( 'Dịch vụ báo OCR chưa sẵn sàng (thiếu Tesseract hoặc gói tiếng Việt).', 'nntm' ) );
			} elseif ( 0 === $status ) {
				nntm_search_ocr_tam_dung( 5 * MINUTE_IN_SECONDS, __( 'Dịch vụ Python không phản hồi.', 'nntm' ) );
			} else {
				// 400/413: bản thân file có vấn đề (hỏng, mã hoá, quá lớn) — bỏ lô này, đi tiếp.
				update_post_meta( $att, '_nntm_ocr_loi', array_values( array_unique( array_merge( $loi, $lo ) ) ) );
				update_post_meta( $att, '_nntm_ocr_cho', array_values( array_diff( $cho, $lo ) ) );
				nntm_search_ocr_cap_nhat_trang_thai( $att );
			}

			return 0;
		}

		$post_id = nntm_search_pdf_owner( $att );
		$xong    = (int) get_post_meta( $att, '_nntm_ocr_xong', true );
		$da_nhan = array();

		foreach ( (array) ( $kq['trang'] ?? array() ) as $r ) {
			$so  = (int) ( $r['trang'] ?? 0 );
			$chu = trim( (string) ( $r['chu'] ?? '' ) );

			if ( ! in_array( $so, $lo, true ) ) {
				continue;
			}

			$da_nhan[] = $so;
			++$da_xu_ly;

			$tin_cay = isset( $r['do_tin_cay'] ) ? (float) $r['do_tin_cay'] : 100.0;

			if ( mb_strlen( $chu ) < 20 || $tin_cay < nntm_search_ocr_nguong() ) {
				$loi[] = $so;
				continue;
			}

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->replace(
				nntm_search_table_pdf_pages(),
				array(
					'attachment_id' => $att,
					'post_id'       => $post_id,
					'page_no'       => $so,
					'content'       => $chu,
					'folded'        => nntm_search_fold( $chu ),
					'source'        => 'ocr',
					'updated_at'    => current_time( 'mysql' ),
				),
				array( '%d', '%d', '%d', '%s', '%s', '%s', '%s' )
			);

			++$xong;
		}

		// Trang có trong lô mà dịch vụ không trả về → tính là lỗi, không lặp mãi.
		$loi = array_values( array_unique( array_merge( $loi, array_diff( $lo, $da_nhan ) ) ) );

		update_post_meta( $att, '_nntm_ocr_xong', $xong );
		update_post_meta( $att, '_nntm_ocr_loi', $loi );
		update_post_meta( $att, '_nntm_ocr_cho', array_values( array_diff( $cho, $lo ) ) );
		nntm_search_ocr_cap_nhat_trang_thai( $att );

		delete_option( NNTM_SEARCH_OCR_DUNG );
	} finally {
		delete_option( NNTM_SEARCH_OCR_KHOA );
	}

	return $da_xu_ly;
}

function nntm_search_ocr_cap_nhat_trang_thai( int $att ): void {
	update_post_meta( $att, '_nntm_ocr_trang_thai', nntm_search_ocr_mang( $att, '_nntm_ocr_cho' ) ? 'cho' : 'xong' );
	update_post_meta( $att, '_nntm_ocr_cap_nhat', current_time( 'mysql' ) );
}

/**
 * Tình trạng chỉ mục của một PDF, cho màn quản trị.
 *
 * @return array{chu:int,ocr:int,cho:int,loi:int,trang_thai:string}
 */
function nntm_search_ocr_tinh_trang( int $att ): array {
	global $wpdb;

	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	$dem = $wpdb->get_results( $wpdb->prepare( 'SELECT source, COUNT(*) AS n FROM ' . nntm_search_table_pdf_pages() . ' WHERE attachment_id = %d GROUP BY source', $att ), OBJECT_K );

	return array(
		'chu'        => (int) ( $dem['text']->n ?? 0 ) + (int) ( $dem['trong']->n ?? 0 ),
		'ocr'        => (int) ( $dem['ocr']->n ?? 0 ),
		'cho'        => count( nntm_search_ocr_mang( $att, '_nntm_ocr_cho' ) ),
		'loi'        => count( nntm_search_ocr_mang( $att, '_nntm_ocr_loi' ) ),
		'trang_thai' => (string) get_post_meta( $att, '_nntm_ocr_trang_thai', true ),
	);
}

/* ---------- Màn quản trị: cột trong Thư viện Media + cảnh báo ---------- */

add_filter(
	'manage_media_columns',
	static function ( array $cot ): array {
		if ( nntm_search_pdf_enabled() ) {
			$cot['nntm_chi_muc_pdf'] = __( 'Chỉ mục tìm kiếm', 'nntm' );
		}
		return $cot;
	}
);

add_action(
	'manage_media_custom_column',
	static function ( string $cot, int $att ): void {
		if ( 'nntm_chi_muc_pdf' !== $cot || 'application/pdf' !== get_post_mime_type( $att ) ) {
			return;
		}

		$t    = nntm_search_ocr_tinh_trang( $att );
		$phan = array();

		if ( $t['chu'] ) {
			/* translators: %d: số trang */
			$phan[] = array( sprintf( __( '%d trang chữ', 'nntm' ), $t['chu'] ), '' );
		}
		if ( $t['ocr'] ) {
			/* translators: %d: số trang */
			$phan[] = array( sprintf( __( '%d trang OCR', 'nntm' ), $t['ocr'] ), '' );
		}
		if ( $t['cho'] ) {
			/* translators: %d: số trang */
			$phan[] = array( sprintf( __( 'đang chờ OCR %d trang', 'nntm' ), $t['cho'] ), 'font-weight:600' );
		}
		if ( $t['loi'] ) {
			/* translators: %d: số trang */
			$phan[] = array( sprintf( __( '%d trang không đọc được', 'nntm' ), $t['loi'] ), 'color:#b32d2e' );
		}

		if ( ! $phan ) {
			esc_html_e( 'Chưa lập chỉ mục', 'nntm' );
			return;
		}

		echo implode( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- từng phần đã escape bên dưới.
			' · ',
			array_map(
				static fn( $p ) => '' === $p[1] ? esc_html( $p[0] ) : '<span style="' . esc_attr( $p[1] ) . '">' . esc_html( $p[0] ) . '</span>',
				$phan
			)
		);
	},
	10,
	2
);

add_action(
	'admin_notices',
	static function (): void {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen || ! in_array( $screen->id, array( 'upload', 'nntm_publication', 'edit-nntm_publication' ), true ) ) {
			return;
		}

		$dung = nntm_search_ocr_dang_tam_dung();
		if ( ! $dung || ! current_user_can( 'upload_files' ) ) {
			return;
		}

		printf(
			'<div class="notice notice-warning"><p><strong>%1$s</strong> %2$s %3$s</p></div>',
			esc_html__( 'OCR PDF scan đang tạm dừng:', 'nntm' ),
			esc_html( (string) $dung['ly_do'] ),
			/* translators: %s: giờ thử lại */
			esc_html( sprintf( __( 'Tự thử lại lúc %s.', 'nntm' ), wp_date( 'H:i', (int) $dung['den'] ) ) )
		);
	}
);
