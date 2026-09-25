<?php
/**
 * Dashboard cá nhân Cộng Tu — Phase 2 (khảo sát câu 26–28).
 *
 * Đọc sổ chuỗi trì của MỘT thành viên trong MỘT chương trình và dựng mọi con
 * số trang "Cộng tu của tôi" cần: tổng, hôm nay, tuần này, nhịp công phu, 14
 * ngày gần nhất, 8 tuần gần nhất, nhật ký theo tuần.
 *
 * MỘT truy vấn duy nhất (gom theo ngày, index user_date) rồi tính hết trong
 * PHP → mọi con số trên trang luôn khớp nhau. KHÔNG đệm: số của chính người
 * đang xem phải đổi ngay sau khi khai báo (docs/07-ban-giao.md mục 6).
 *
 * NGÀY: "hôm nay" = current_time('Y-m-d') — ĐÚNG hàm mà nntm_kpi_ghi_nhan()
 * dùng để ghi log_date. Không dùng CURDATE()/NOW() của MySQL (máy chủ CSDL có
 * thể chạy múi giờ khác PHP — đã đo thật: lệch 7 giờ ở máy dev). Cộng trừ ngày
 * làm bằng DateTimeImmutable ở UTC trên nhãn 'Y-m-d' — thuần lịch, không dính
 * giờ mùa hè hay múi giờ.
 *
 * TUẦN: bắt đầu theo Cài đặt → Tổng quan → "Tuần bắt đầu vào" (start_of_week,
 * đang là Thứ Hai). Gọi tên bằng khoảng ngày "21/09 – 27/09", không đánh số
 * tuần ISO.
 *
 * @package NNTM_Core
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'nntm_kpi_hom_nay' ) ) {
	/** Ngày hôm nay theo đúng cách log_date được ghi. */
	function nntm_kpi_hom_nay(): string {
		return current_time( 'Y-m-d' );
	}
}

if ( ! function_exists( 'nntm_kpi_ngay_hop_le' ) ) {
	/**
	 * Kiểm chặt một chuỗi ngày 'Y-m-d'. Meta ngày của chương trình chỉ qua
	 * sanitize_text_field nên có thể là '14/08/2026' hay '2026-8-14'.
	 *
	 * @return string|null Ngày hợp lệ, hoặc null.
	 */
	function nntm_kpi_ngay_hop_le( string $ngay ): ?string {
		$d = DateTimeImmutable::createFromFormat( '!Y-m-d', trim( $ngay ), new DateTimeZone( 'UTC' ) );

		return $d && $d->format( 'Y-m-d' ) === trim( $ngay ) ? $d->format( 'Y-m-d' ) : null;
	}
}

if ( ! function_exists( 'nntm_kpi_cong_ngay' ) ) {
	/** Cộng/trừ số ngày trên nhãn 'Y-m-d'. */
	function nntm_kpi_cong_ngay( string $ngay, int $so ): string {
		return ( new DateTimeImmutable( $ngay, new DateTimeZone( 'UTC' ) ) )->modify( ( $so >= 0 ? '+' : '' ) . $so . ' days' )->format( 'Y-m-d' );
	}
}

if ( ! function_exists( 'nntm_kpi_dau_tuan' ) ) {
	/** Ngày đầu tuần chứa $ngay, theo start_of_week (0 = Chủ Nhật … 6 = Thứ Bảy). */
	function nntm_kpi_dau_tuan( string $ngay ): string {
		$bat_dau = (int) apply_filters( 'nntm_kpi_bat_dau_tuan', (int) get_option( 'start_of_week', 1 ) );
		$thu     = (int) ( new DateTimeImmutable( $ngay, new DateTimeZone( 'UTC' ) ) )->format( 'w' );

		return nntm_kpi_cong_ngay( $ngay, -( ( $thu - $bat_dau + 7 ) % 7 ) );
	}
}

if ( ! function_exists( 'nntm_kpi_chuong_trinh_cua_nguoi' ) ) {
	/**
	 * Các chương trình người này có dòng sổ, hoạt động gần nhất trước.
	 *
	 * @return int[]
	 */
	function nntm_kpi_chuong_trinh_cua_nguoi( int $user_id ): array {
		global $wpdb;
		$table = \NNTM\Core\Schema::table( 'kpi_log' );

		return array_map(
			'intval',
			(array) $wpdb->get_col( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
				$wpdb->prepare(
					"SELECT program_id FROM {$table} WHERE user_id = %d AND program_id > 0 GROUP BY program_id ORDER BY MAX(log_date) DESC, program_id DESC", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					$user_id
				)
			)
		);
	}
}

if ( ! function_exists( 'nntm_kpi_theo_ngay_cua_nguoi' ) ) {
	/**
	 * Sổ của một người trong một chương trình, gom theo ngày, ngày cũ trước.
	 *
	 * @return array<string,array{thuc_hien:int,cam_ket:int,so_lan:int}> Khoá là 'Y-m-d'.
	 */
	function nntm_kpi_theo_ngay_cua_nguoi( int $program_id, int $user_id ): array {
		global $wpdb;
		$table = \NNTM\Core\Schema::table( 'kpi_log' );

		$dong = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- tên bảng do plugin sinh.
				"SELECT log_date,
					SUM( CASE WHEN metric = %s THEN value ELSE 0 END ) AS thuc_hien,
					SUM( CASE WHEN metric = %s THEN value ELSE 0 END ) AS cam_ket,
					SUM( CASE WHEN metric = %s THEN 1 ELSE 0 END ) AS so_lan
				FROM {$table}
				WHERE user_id = %d AND program_id = %d
				GROUP BY log_date
				ORDER BY log_date ASC",
				NNTM_KPI_METRIC_THUC_HIEN,
				NNTM_KPI_METRIC_CAM_KET,
				NNTM_KPI_METRIC_THUC_HIEN,
				$user_id,
				$program_id
			),
			ARRAY_A
		);

		$ket = array();
		foreach ( (array) $dong as $d ) {
			$ket[ (string) $d['log_date'] ] = array(
				'thuc_hien' => (int) $d['thuc_hien'],
				'cam_ket'   => (int) $d['cam_ket'],
				'so_lan'    => (int) $d['so_lan'],
			);
		}

		return $ket;
	}
}

if ( ! function_exists( 'nntm_kpi_bang_dieu_khien' ) ) {
	/**
	 * Mọi số liệu cho dashboard cá nhân.
	 *
	 * @param int         $program_id Chương trình.
	 * @param int         $user_id    Thành viên — nơi gọi PHẢI lấy từ get_current_user_id().
	 * @param string|null $hom_nay    'Y-m-d', chỉ truyền khi kiểm thử.
	 * @return array<string,mixed>
	 */
	function nntm_kpi_bang_dieu_khien( int $program_id, int $user_id, ?string $hom_nay = null ): array {
		$hom_nay = $hom_nay ?? nntm_kpi_hom_nay();
		$ngay    = nntm_kpi_theo_ngay_cua_nguoi( $program_id, $user_id );
		$mo      = nntm_program_dang_mo( $program_id );

		$cam_ket   = array_sum( array_column( $ngay, 'cam_ket' ) );
		$thuc_hien = array_sum( array_column( $ngay, 'thuc_hien' ) );
		$ngay_dau  = $ngay ? (string) array_key_first( $ngay ) : null;

		// Mốc cuối cửa sổ: hôm nay khi chương trình mở; đã đóng thì là ngày cuối có sổ.
		$cuoi = $mo || ! $ngay ? $hom_nay : (string) array_key_last( $ngay );
		$th   = static fn( string $d ): int => $ngay[ $d ]['thuc_hien'] ?? 0;

		/* --- Tuần này & cùng kỳ tuần trước (cùng số ngày, không so nửa tuần với cả tuần) --- */
		$dau_tuan = nntm_kpi_dau_tuan( $cuoi );
		$so_ngay  = (int) ( new DateTimeImmutable( $dau_tuan ) )->diff( new DateTimeImmutable( $cuoi ) )->days + 1;
		$tuan     = array( 'dau' => $dau_tuan, 'cuoi' => $cuoi, 'thuc_hien' => 0, 'ngay_co_khai' => 0, 'so_ngay' => 0 );
		$truoc    = array( 'dau' => nntm_kpi_cong_ngay( $dau_tuan, -7 ), 'cuoi' => nntm_kpi_cong_ngay( $cuoi, -7 ), 'thuc_hien' => 0, 'ngay_co_khai' => 0 );

		for ( $i = 0; $i < $so_ngay; $i++ ) {
			$d = nntm_kpi_cong_ngay( $dau_tuan, $i );
			$p = nntm_kpi_cong_ngay( $d, -7 );

			$tuan['thuc_hien'] += $th( $d );
			$tuan['ngay_co_khai'] += $th( $d ) > 0 ? 1 : 0;
			// Ngày đã qua tính từ lúc người này bắt đầu — người vào giữa tuần không bị tính thiếu.
			$tuan['so_ngay'] += null === $ngay_dau || $d >= $ngay_dau ? 1 : 0;
			$truoc['thuc_hien'] += $th( $p );
			$truoc['ngay_co_khai'] += $th( $p ) > 0 ? 1 : 0;
		}

		/* --- Nhịp công phu: số ngày liền có khai báo. Hôm nay chưa ghi thì chưa đứt — đếm từ hôm qua. --- */
		$nhip = 0;
		$d    = $cuoi;
		if ( $mo && 0 === $th( $d ) ) {
			$d = nntm_kpi_cong_ngay( $d, -1 );
		}
		while ( $th( $d ) > 0 ) {
			++$nhip;
			$d = nntm_kpi_cong_ngay( $d, -1 );
		}

		/* --- 14 ngày gần nhất --- */
		$chuoi_ngay = array();
		for ( $i = 13; $i >= 0; $i-- ) {
			$d            = nntm_kpi_cong_ngay( $cuoi, -$i );
			$chuoi_ngay[] = array(
				'ngay'      => $d,
				'thuc_hien' => $th( $d ),
				'so_lan'    => $ngay[ $d ]['so_lan'] ?? 0,
				'hom_nay'   => $mo && $d === $hom_nay,
				'truoc_dau' => null === $ngay_dau || $d < $ngay_dau,
			);
		}

		/* --- 8 tuần gần nhất (bỏ các tuần trước khi người này bắt đầu) --- */
		$chuoi_tuan = array();
		for ( $i = 7; $i >= 0; $i-- ) {
			$dau = nntm_kpi_cong_ngay( $dau_tuan, -7 * $i );
			$het = min( nntm_kpi_cong_ngay( $dau, 6 ), $cuoi );
			if ( null === $ngay_dau || $het < nntm_kpi_dau_tuan( $ngay_dau ) ) {
				continue;
			}
			$tong = 0;
			$co   = 0;
			for ( $d = $dau; $d <= $het; $d = nntm_kpi_cong_ngay( $d, 1 ) ) {
				$tong += $th( $d );
				$co   += $th( $d ) > 0 ? 1 : 0;
			}
			$chuoi_tuan[] = array(
				'dau'          => $dau,
				'cuoi'         => nntm_kpi_cong_ngay( $dau, 6 ),
				'thuc_hien'    => $tong,
				'ngay_co_khai' => $co,
				'dang_dien_ra' => $mo && $dau === $dau_tuan,
			);
		}

		/* --- Nhật ký: theo tuần, mới nhất trước; chỉ những ngày có dòng sổ --- */
		$lich_su = array();
		foreach ( array_reverse( $ngay, true ) as $d => $v ) {
			$k = nntm_kpi_dau_tuan( (string) $d );
			if ( ! isset( $lich_su[ $k ] ) ) {
				$lich_su[ $k ] = array( 'dau' => $k, 'cuoi' => nntm_kpi_cong_ngay( $k, 6 ), 'thuc_hien' => 0, 'cam_ket' => 0, 'ngay_co_khai' => 0, 'ngay' => array() );
			}
			$lich_su[ $k ]['thuc_hien']    += $v['thuc_hien'];
			$lich_su[ $k ]['cam_ket']      += $v['cam_ket'];
			$lich_su[ $k ]['ngay_co_khai'] += $v['thuc_hien'] > 0 ? 1 : 0;
			$lich_su[ $k ]['ngay'][]        = array( 'ngay' => (string) $d ) + $v;
		}

		/* --- Nhịp cần để tròn cam kết: chỉ khi chương trình có ngày kết thúc hợp lệ --- */
		$nhip_can  = null;
		$ket_thuc  = nntm_kpi_ngay_hop_le( (string) get_post_meta( $program_id, '_nntm_program_ket_thuc', true ) );
		$con_lai   = max( 0, $cam_ket - $thuc_hien );
		if ( $mo && $ket_thuc && $ket_thuc >= $hom_nay && $con_lai > 0 ) {
			$so_ngay_con = (int) ( new DateTimeImmutable( $hom_nay ) )->diff( new DateTimeImmutable( $ket_thuc ) )->days + 1;
			$nhip_can    = array(
				'ket_thuc'    => $ket_thuc,
				'so_ngay_con' => $so_ngay_con,
				'moi_ngay'    => (int) ceil( $con_lai / $so_ngay_con ),
			);
		}

		return array(
			'program_id' => $program_id,
			'mo'         => $mo,
			'hom_nay'    => $hom_nay,
			'ngay_cuoi'  => $cuoi,
			'ngay_dau'   => $ngay_dau,
			'co_so'      => (bool) $ngay,
			'tong'       => array(
				'cam_ket'    => $cam_ket,
				'thuc_hien'  => $thuc_hien,
				'tien_trinh' => nntm_kpi_tinh_tien_trinh( $thuc_hien, $cam_ket ),
				'con_lai'    => $con_lai,
				'so_ngay_co_khai' => count( array_filter( $ngay, static fn( $v ) => $v['thuc_hien'] > 0 ) ),
			),
			'hom_nay_th' => $mo ? $th( $hom_nay ) : 0,
			'tuan_nay'   => $tuan,
			'tuan_truoc' => $truoc,
			'nhip'       => $nhip,
			'nhip_can'   => $nhip_can,
			'ngay'       => $chuoi_ngay,
			'tuan'       => $chuoi_tuan,
			'lich_su'    => array_values( $lich_su ),
			'don_vi'     => (string) ( get_post_meta( $program_id, '_nntm_program_don_vi', true ) ?: __( 'chuỗi', 'nntm' ) ),
		);
	}
}
