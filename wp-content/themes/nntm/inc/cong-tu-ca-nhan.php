<?php
/**
 * Trang "Cộng tu của tôi" — dashboard cá nhân (Phase 2, khảo sát câu 26–28).
 *
 * Số liệu: plugin nntm-core (includes/class-chuoi-tri-ca-nhan.php). File này
 * chỉ lo: URL, chọn chương trình để xem, chặn khách, không cache, nạp CSS/JS.
 *
 * Trang là một Page thật (slug cong-tu-cua-toi, tạo bởi tools/seed-cong-tu.php)
 * dùng template page-cong-tu-cua-toi.php — BQT sửa được tiêu đề và đoạn giới
 * thiệu trong trình soạn thảo; bố cục số liệu là màn chức năng, không sửa
 * (docs/04-kien-truc.md mục 2).
 */

defined( 'ABSPATH' ) || exit;

function nntm_congtu_slug_ca_nhan(): string {
	return (string) apply_filters( 'nntm_congtu_slug_ca_nhan', 'cong-tu-cua-toi' );
}

/** URL trang, '' khi trang chưa được tạo (đừng in link chết). */
function nntm_congtu_url_ca_nhan(): string {
	$trang = get_page_by_path( nntm_congtu_slug_ca_nhan() );

	return (string) apply_filters( 'nntm_congtu_url_ca_nhan', $trang ? (string) get_permalink( $trang ) : '' );
}

function nntm_congtu_la_trang_ca_nhan(): bool {
	return is_page( nntm_congtu_slug_ca_nhan() );
}

/**
 * Chương trình đang xem. ?chuong-trinh=ID chỉ được nhận khi người này có sổ ở
 * đó hoặc đó là chương trình đang mở — không xem trộm được chương trình khác.
 *
 * @return array{program_id:int,ds:int[],co_the_ghi:bool}
 */
function nntm_congtu_chon_chuong_trinh( int $user_id ): array {
	$ds      = function_exists( 'nntm_kpi_chuong_trinh_cua_nguoi' ) ? nntm_kpi_chuong_trinh_cua_nguoi( $user_id ) : array();
	$hien_tai = function_exists( 'nntm_program_hien_tai' ) ? nntm_program_hien_tai() : null;
	$id_ht    = $hien_tai ? (int) $hien_tai->ID : 0;

	if ( $id_ht && ! in_array( $id_ht, $ds, true ) ) {
		array_unshift( $ds, $id_ht );
	}

	$muon = isset( $_GET['chuong-trinh'] ) ? absint( $_GET['chuong-trinh'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- chỉ đọc để chọn nội dung hiển thị.
	$id   = in_array( $muon, $ds, true ) ? $muon : ( $id_ht ?: ( $ds[0] ?? 0 ) );

	return array(
		'program_id' => $id,
		'ds'         => $ds,
		// Nút ghi chỉ hiện khi đang xem ĐÚNG chương trình mà form ghi vào.
		'co_the_ghi' => $id > 0 && $id === $id_ht && nntm_program_dang_mo( $id ),
	);
}

/** Trang cá nhân: không cho máy tìm kiếm, không cho cache (số của chính người xem). */
add_action(
	'template_redirect',
	static function (): void {
		if ( nntm_congtu_la_trang_ca_nhan() && is_user_logged_in() ) {
			nocache_headers();
		}
	},
	6
);

add_filter(
	'wp_robots',
	static function ( array $robots ): array {
		return nntm_congtu_la_trang_ca_nhan() ? wp_robots_no_robots( $robots ) : $robots;
	}
);

add_action(
	'wp_enqueue_scripts',
	static function (): void {
		if ( ! nntm_congtu_la_trang_ca_nhan() ) {
			return;
		}

		$css = NNTM_THEME_DIR . '/assets/css/pages/cong-tu-ca-nhan.css';
		wp_enqueue_style(
			'nntm-cong-tu-ca-nhan',
			NNTM_THEME_URI . '/assets/css/pages/cong-tu-ca-nhan.css',
			array( 'nntm-tokens', 'nntm-base' ),
			nntm_asset_version( $css )
		);

		$js = NNTM_THEME_DIR . '/assets/js/cong-tu-ca-nhan.js';
		wp_enqueue_script(
			'nntm-cong-tu-ca-nhan',
			NNTM_THEME_URI . '/assets/js/cong-tu-ca-nhan.js',
			array(),
			nntm_asset_version( $js ),
			true
		);
	}
);

/* ---------- Định dạng dùng trong template ---------- */

/** 'T6, 25/09' hoặc 'Thứ Sáu, 25/09/2026'. Dựng từ nhãn 'Y-m-d', không qua timestamp. */
function nntm_congtu_nhan_ngay( string $ngay, bool $day_du = false ): string {
	$d   = new DateTimeImmutable( $ngay, new DateTimeZone( 'UTC' ) );
	$thu = (int) $d->format( 'w' );

	$ngan = array( 'CN', 'T2', 'T3', 'T4', 'T5', 'T6', 'T7' );
	$dai  = array( 'Chủ Nhật', 'Thứ Hai', 'Thứ Ba', 'Thứ Tư', 'Thứ Năm', 'Thứ Sáu', 'Thứ Bảy' );

	return $day_du
		? $dai[ $thu ] . ', ' . $d->format( 'd/m/Y' )
		: $ngan[ $thu ] . ', ' . $d->format( 'd/m' );
}

/** '21/09 – 27/09' (thêm năm khi tuần vắt qua năm mới). */
function nntm_congtu_nhan_tuan( string $dau, string $cuoi ): string {
	$a = new DateTimeImmutable( $dau, new DateTimeZone( 'UTC' ) );
	$b = new DateTimeImmutable( $cuoi, new DateTimeZone( 'UTC' ) );

	return $a->format( 'Y' ) === $b->format( 'Y' )
		? $a->format( 'd/m' ) . ' – ' . $b->format( 'd/m' )
		: $a->format( 'd/m/Y' ) . ' – ' . $b->format( 'd/m/Y' );
}

function nntm_congtu_so( int $n ): string {
	return function_exists( 'nntm_congtu_dinh_dang_so' ) ? nntm_congtu_dinh_dang_so( $n ) : number_format_i18n( $n );
}
