<?php
/**
 * Đổi ngày âm lịch Việt Nam sang dương lịch.
 *
 * Thuật toán thiên văn của Hồ Ngọc Đức (tính điểm sóc và trung khí theo
 * Jean Meeus), múi giờ +7. Dùng cho các dịp lễ Phật giáo tính theo âm lịch
 * (Phật Đản 15/4, Vu Lan 15/7…) — phiếu khảo sát câu 31.
 *
 * Kết quả đã đối chiếu với lịch Việt Nam, xem bảng kiểm trong
 * docs/14-ban-tin-email.md.
 *
 * @package NNTM_Core
 */

namespace NNTM\Core;

defined( 'ABSPATH' ) || exit;

/**
 * Chuyển đổi âm lịch → dương lịch.
 */
final class Am_Lich {

	/** Múi giờ Việt Nam. */
	private const MUI_GIO = 7.0;

	/**
	 * Ngày dương lịch của một ngày âm lịch.
	 *
	 * @param int  $ngay    Ngày âm (1–30).
	 * @param int  $thang   Tháng âm (1–12).
	 * @param int  $nam     Năm âm lịch (năm chứa tháng Giêng của nó).
	 * @param bool $nhuan   Tháng nhuận.
	 * @return string|null  'Y-m-d', hoặc null nếu ngày không tồn tại (vd. tháng nhuận không có năm đó).
	 */
	public static function sang_duong( int $ngay, int $thang, int $nam, bool $nhuan = false ): ?string {
		if ( $ngay < 1 || $ngay > 30 || $thang < 1 || $thang > 12 ) {
			return null;
		}

		$tz = self::MUI_GIO;

		if ( $thang < 11 ) {
			$a11 = self::thang_11( $nam - 1, $tz );
			$b11 = self::thang_11( $nam, $tz );
		} else {
			$a11 = self::thang_11( $nam, $tz );
			$b11 = self::thang_11( $nam + 1, $tz );
		}

		$k   = (int) floor( 0.5 + ( $a11 - 2415021.076998695 ) / 29.530588853 );
		$off = $thang - 11;
		if ( $off < 0 ) {
			$off += 12;
		}

		if ( $b11 - $a11 > 365 ) {
			$leap_off   = self::lech_thang_nhuan( $a11, $tz );
			$thang_nhuan = $leap_off - 2;
			if ( $thang_nhuan < 0 ) {
				$thang_nhuan += 12;
			}
			if ( $nhuan && $thang !== $thang_nhuan ) {
				return null;
			}
			if ( $nhuan || $off >= $leap_off ) {
				++$off;
			}
		} elseif ( $nhuan ) {
			return null;
		}

		$dau_thang = self::ngay_soc( $k + $off, $tz );
		$dau_sau   = self::ngay_soc( $k + $off + 1, $tz );

		// Tháng thiếu (29 ngày) thì không có ngày 30.
		if ( $dau_thang + $ngay - 1 >= $dau_sau ) {
			return null;
		}

		return self::jd_sang_ngay( $dau_thang + $ngay - 1 );
	}

	/**
	 * Các ngày dương lịch ứng với một ngày âm lịch "hằng năm", dùng khi chỉ biết
	 * ngày dương cần xét: ngày âm tháng 11–12 của năm âm Y rơi vào tháng 1–2
	 * dương của năm Y+1, nên xét cả năm âm trước.
	 *
	 * @param int    $ngay       Ngày âm.
	 * @param int    $thang      Tháng âm.
	 * @param string $ngay_duong Ngày dương cần kiểm, 'Y-m-d'.
	 */
	public static function trung_ngay( int $ngay, int $thang, string $ngay_duong ): bool {
		$nam = (int) substr( $ngay_duong, 0, 4 );

		foreach ( array( $nam - 1, $nam ) as $nam_am ) {
			if ( self::sang_duong( $ngay, $thang, $nam_am ) === $ngay_duong ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Lần kế tiếp (tính từ $tu_ngay, gồm cả ngày đó) một ngày âm lịch hằng năm rơi vào.
	 *
	 * @param int    $ngay    Ngày âm.
	 * @param int    $thang   Tháng âm.
	 * @param string $tu_ngay 'Y-m-d'.
	 */
	public static function lan_toi( int $ngay, int $thang, string $tu_ngay ): ?string {
		$nam = (int) substr( $tu_ngay, 0, 4 );

		foreach ( array( $nam - 1, $nam, $nam + 1 ) as $nam_am ) {
			$d = self::sang_duong( $ngay, $thang, $nam_am );
			if ( null !== $d && $d >= $tu_ngay ) {
				return $d;
			}
		}

		return null;
	}

	/* ---------- Thiên văn ---------- */

	private static function ngay_sang_jd( int $dd, int $mm, int $yy ): int {
		$a  = intdiv( 14 - $mm, 12 );
		$y  = $yy + 4800 - $a;
		$m  = $mm + 12 * $a - 3;
		$jd = $dd + intdiv( 153 * $m + 2, 5 ) + 365 * $y + intdiv( $y, 4 ) - intdiv( $y, 100 ) + intdiv( $y, 400 ) - 32045;

		if ( $jd < 2299161 ) {
			$jd = $dd + intdiv( 153 * $m + 2, 5 ) + 365 * $y + intdiv( $y, 4 ) - 32083;
		}

		return $jd;
	}

	private static function jd_sang_ngay( int $jd ): string {
		if ( $jd > 2299160 ) {
			$a = $jd + 32044;
			$b = intdiv( 4 * $a + 3, 146097 );
			$c = $a - intdiv( $b * 146097, 4 );
		} else {
			$b = 0;
			$c = $jd + 32082;
		}

		$d = intdiv( 4 * $c + 3, 1461 );
		$e = $c - intdiv( 1461 * $d, 4 );
		$m = intdiv( 5 * $e + 2, 153 );

		$ngay  = $e - intdiv( 153 * $m + 2, 5 ) + 1;
		$thang = $m + 3 - 12 * intdiv( $m, 10 );
		$nam   = $b * 100 + $d - 4800 + intdiv( $m, 10 );

		return sprintf( '%04d-%02d-%02d', $nam, $thang, $ngay );
	}

	/** Thời điểm sóc thứ k (tính từ 1/1/1900), theo ngày Julius. */
	private static function soc( int $k ): float {
		$t  = $k / 1236.85;
		$t2 = $t * $t;
		$t3 = $t2 * $t;
		$dr = M_PI / 180;

		$jd1 = 2415020.75933 + 29.53058868 * $k + 0.0001178 * $t2 - 0.000000155 * $t3;
		$jd1 += 0.00033 * sin( ( 166.56 + 132.87 * $t - 0.009173 * $t2 ) * $dr );

		$m   = 359.2242 + 29.10535608 * $k - 0.0000333 * $t2 - 0.00000347 * $t3;
		$mpr = 306.0253 + 385.81691806 * $k + 0.0107306 * $t2 + 0.00001236 * $t3;
		$f   = 21.2964 + 390.67050646 * $k - 0.0016528 * $t2 - 0.00000239 * $t3;

		$c1  = ( 0.1734 - 0.000393 * $t ) * sin( $m * $dr ) + 0.0021 * sin( 2 * $dr * $m );
		$c1 -= 0.4068 * sin( $mpr * $dr ) - 0.0161 * sin( $dr * 2 * $mpr );
		$c1 -= 0.0004 * sin( $dr * 3 * $mpr );
		$c1 += 0.0104 * sin( $dr * 2 * $f ) - 0.0051 * sin( $dr * ( $m + $mpr ) );
		$c1 -= 0.0074 * sin( $dr * ( $m - $mpr ) ) - 0.0004 * sin( $dr * ( 2 * $f + $m ) );
		$c1 -= 0.0004 * sin( $dr * ( 2 * $f - $m ) ) + 0.0006 * sin( $dr * ( 2 * $f + $mpr ) );
		$c1 += 0.0010 * sin( $dr * ( 2 * $f - $mpr ) ) + 0.0005 * sin( $dr * ( 2 * $mpr + $m ) );

		$delta = $t < -11
			? 0.001 + 0.000839 * $t + 0.0002261 * $t2 - 0.00000845 * $t3 - 0.000000081 * $t * $t3
			: -0.000278 + 0.000265 * $t + 0.000262 * $t2;

		return $jd1 + $c1 - $delta;
	}

	/** Kinh độ mặt trời (radian) tại ngày Julius. */
	private static function kinh_do_mat_troi( float $jdn ): float {
		$t  = ( $jdn - 2451545.0 ) / 36525;
		$t2 = $t * $t;
		$dr = M_PI / 180;

		$m  = 357.52910 + 35999.05030 * $t - 0.0001559 * $t2 - 0.00000048 * $t * $t2;
		$l0 = 280.46645 + 36000.76983 * $t + 0.0003032 * $t2;
		$dl = ( 1.914600 - 0.004817 * $t - 0.000014 * $t2 ) * sin( $dr * $m );
		$dl += ( 0.019993 - 0.000101 * $t ) * sin( $dr * 2 * $m ) + 0.000290 * sin( $dr * 3 * $m );

		$l = ( $l0 + $dl ) * $dr;

		return $l - M_PI * 2 * floor( $l / ( M_PI * 2 ) );
	}

	/** Cung trung khí (0–11) của ngày. */
	private static function cung_mat_troi( int $jd, float $tz ): int {
		return (int) floor( self::kinh_do_mat_troi( $jd - 0.5 - $tz / 24 ) / M_PI * 6 );
	}

	/** Ngày (Julius, nguyên) chứa điểm sóc thứ k. */
	private static function ngay_soc( int $k, float $tz ): int {
		return (int) floor( self::soc( $k ) + 0.5 + $tz / 24 );
	}

	/** Ngày bắt đầu tháng 11 âm lịch của năm dương $yy. */
	private static function thang_11( int $yy, float $tz ): int {
		$off = self::ngay_sang_jd( 31, 12, $yy ) - 2415021;
		$k   = (int) floor( $off / 29.530588853 );
		$nm  = self::ngay_soc( $k, $tz );

		if ( self::cung_mat_troi( $nm, $tz ) >= 9 ) {
			$nm = self::ngay_soc( $k - 1, $tz );
		}

		return $nm;
	}

	/** Vị trí tháng nhuận tính từ tháng 11 năm trước. */
	private static function lech_thang_nhuan( int $a11, float $tz ): int {
		$k    = (int) floor( ( $a11 - 2415021.076998695 ) / 29.530588853 + 0.5 );
		$i    = 1;
		$cung = self::cung_mat_troi( self::ngay_soc( $k + $i, $tz ), $tz );

		do {
			$truoc = $cung;
			++$i;
			$cung = self::cung_mat_troi( self::ngay_soc( $k + $i, $tz ), $tz );
		} while ( $cung !== $truoc && $i < 14 );

		return $i - 1;
	}
}
