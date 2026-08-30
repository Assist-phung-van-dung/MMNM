<?php
/**
 * Bộ mã hoá mã QR — vẽ chuỗi VietQR của PayOS thành ảnh SVG.
 *
 * VÌ SAO PHẢI TỰ VIẾT: PayOS trả về chuỗi VietQR (theo chuẩn EMVCo), KHÔNG trả
 * ảnh. Dự án không có thư viện vẽ QR nào, và không được tải thêm phụ thuộc từ
 * ngoài vào. Không có bộ mã hoá thì ô QR trong khung thanh toán mãi trống.
 *
 * Vẽ ở MÁY CHỦ chứ không ở trình duyệt: chuỗi thanh toán không cần đi ra tới
 * JavaScript, và SVG thì nét ở mọi cỡ màn hình.
 *
 * PHẠM VI: chế độ byte, mức sửa lỗi M (tiêu chuẩn cho thanh toán), phiên bản
 * 1–10 (tối đa 213 byte — chuỗi VietQR thường dài 120–180 byte). Quá dài thì
 * trả về chuỗi rỗng để nơi gọi biết mà lui về nút bấm.
 *
 * KIỂM CHỨNG: xem nntm_payos_qr_tu_kiem() ở cuối tệp — nó đối chiếu bảng dung
 * lượng với bảng khối, và đếm số ô đặt được dữ liệu để so với tổng số từ mã.
 * Ba con số này suy ra từ ba nguồn độc lập nhau; khớp cả ba thì bảng và cách
 * dựng lưới đều đúng.
 *
 * @package NNTM_PayOS
 */

defined( 'ABSPATH' ) || exit;

/**
 * Bảng phiên bản cho mức sửa lỗi M.
 *
 * Mỗi dòng: [tổng từ mã, số từ mã sửa lỗi mỗi khối, nhóm1_số khối,
 * nhóm1_từ mã dữ liệu, nhóm2_số khối, nhóm2_từ mã dữ liệu].
 */
function nntm_payos_qr_bang_phien_ban(): array {
	return array(
		1  => array( 26, 10, 1, 16, 0, 0 ),
		2  => array( 44, 16, 1, 28, 0, 0 ),
		3  => array( 70, 26, 1, 44, 0, 0 ),
		4  => array( 100, 18, 2, 32, 0, 0 ),
		5  => array( 134, 24, 2, 43, 0, 0 ),
		6  => array( 172, 16, 4, 27, 0, 0 ),
		7  => array( 196, 18, 4, 31, 0, 0 ),
		8  => array( 242, 22, 2, 38, 2, 39 ),
		9  => array( 292, 22, 3, 36, 2, 37 ),
		10 => array( 346, 26, 4, 43, 1, 44 ),
	);
}

/**
 * Tâm của các hoa tiêu căn chỉnh theo phiên bản.
 */
function nntm_payos_qr_tam_can_chinh(): array {
	return array(
		1  => array(),
		2  => array( 6, 18 ),
		3  => array( 6, 22 ),
		4  => array( 6, 26 ),
		5  => array( 6, 30 ),
		6  => array( 6, 34 ),
		7  => array( 6, 22, 38 ),
		8  => array( 6, 24, 42 ),
		9  => array( 6, 26, 46 ),
		10 => array( 6, 28, 50 ),
	);
}

/**
 * Số từ mã dữ liệu của một phiên bản.
 *
 * @param int $ver Phiên bản.
 */
function nntm_payos_qr_so_tu_ma_du_lieu( int $ver ): int {
	$b = nntm_payos_qr_bang_phien_ban()[ $ver ];

	return $b[2] * $b[3] + $b[4] * $b[5];
}

/**
 * Số byte tối đa nhét được vào một phiên bản ở chế độ byte.
 *
 * 4 bit chỉ chế độ + 8 bit đếm ký tự (16 bit từ phiên bản 10) = phần đầu; phần
 * còn lại là dữ liệu.
 *
 * @param int $ver Phiên bản.
 */
function nntm_payos_qr_suc_chua( int $ver ): int {
	$bit_dem = $ver >= 10 ? 16 : 8;

	return (int) floor( ( nntm_payos_qr_so_tu_ma_du_lieu( $ver ) * 8 - 4 - $bit_dem ) / 8 );
}

/* -------------------------------------------------------------------------
 * Số học trên trường Galois GF(256) — nền của mã sửa lỗi Reed–Solomon
 * ---------------------------------------------------------------------- */

/**
 * Bảng luỹ thừa và logarit của GF(256), đa thức sinh 0x11D.
 *
 * @return array{0: int[], 1: int[]}
 */
function nntm_payos_qr_bang_gf(): array {
	static $exp = null;
	static $log = null;

	if ( null !== $exp ) {
		return array( $exp, $log );
	}

	$exp = array_fill( 0, 512, 0 );
	$log = array_fill( 0, 256, 0 );
	$x   = 1;

	for ( $i = 0; $i < 255; $i++ ) {
		$exp[ $i ]   = $x;
		$log[ $x ]   = $i;
		$x         <<= 1;

		if ( $x & 0x100 ) {
			$x ^= 0x11D;
		}
	}

	for ( $i = 255; $i < 512; $i++ ) {
		$exp[ $i ] = $exp[ $i - 255 ];
	}

	return array( $exp, $log );
}

/**
 * Nhân hai phần tử GF(256).
 */
function nntm_payos_qr_nhan( int $a, int $b ): int {
	if ( 0 === $a || 0 === $b ) {
		return 0;
	}

	list( $exp, $log ) = nntm_payos_qr_bang_gf();

	return $exp[ $log[ $a ] + $log[ $b ] ];
}

/**
 * Đa thức sinh cho $n từ mã sửa lỗi.
 *
 * @param int $n Số từ mã sửa lỗi.
 * @return int[]
 */
function nntm_payos_qr_da_thuc_sinh( int $n ): array {
	list( $exp ) = nntm_payos_qr_bang_gf();
	$g           = array( 1 );

	for ( $i = 0; $i < $n; $i++ ) {
		$moi = array_fill( 0, count( $g ) + 1, 0 );

		foreach ( $g as $j => $he_so ) {
			$moi[ $j ]     ^= nntm_payos_qr_nhan( $he_so, $exp[ $i ] );
			$moi[ $j + 1 ] ^= $he_so;
		}

		$g = $moi;
	}

	return $g;
}

/**
 * Tính từ mã sửa lỗi cho một khối dữ liệu.
 *
 * @param int[] $du_lieu Khối dữ liệu.
 * @param int   $n       Số từ mã sửa lỗi cần sinh.
 * @return int[]
 */
function nntm_payos_qr_sua_loi( array $du_lieu, int $n ): array {
	$g  = nntm_payos_qr_da_thuc_sinh( $n );
	$du = array_merge( array_values( $du_lieu ), array_fill( 0, $n, 0 ) );

	for ( $i = 0; $i < count( $du_lieu ); $i++ ) {
		$dau = $du[ $i ];

		if ( 0 === $dau ) {
			continue;
		}

		foreach ( $g as $j => $he_so ) {
			$du[ $i + $j ] ^= nntm_payos_qr_nhan( $he_so, $dau );
		}
	}

	return array_slice( $du, count( $du_lieu ) );
}

/* -------------------------------------------------------------------------
 * Dựng chuỗi bit
 * ---------------------------------------------------------------------- */

/**
 * Đóng gói dữ liệu thành mảng từ mã, đã chèn đệm theo chuẩn.
 *
 * @param string $chuoi Chuỗi cần mã hoá.
 * @param int    $ver   Phiên bản.
 * @return int[]
 */
function nntm_payos_qr_tu_ma_du_lieu( string $chuoi, int $ver ): array {
	$bit     = '';
	$bit_dem = $ver >= 10 ? 16 : 8;

	// 0100 = chế độ byte.
	$bit .= '0100';
	$bit .= str_pad( decbin( strlen( $chuoi ) ), $bit_dem, '0', STR_PAD_LEFT );

	for ( $i = 0, $n = strlen( $chuoi ); $i < $n; $i++ ) {
		$bit .= str_pad( decbin( ord( $chuoi[ $i ] ) ), 8, '0', STR_PAD_LEFT );
	}

	$tong_bit = nntm_payos_qr_so_tu_ma_du_lieu( $ver ) * 8;

	// Dấu kết thúc: tối đa bốn số 0.
	$bit .= str_repeat( '0', min( 4, $tong_bit - strlen( $bit ) ) );

	// Đệm cho tròn byte.
	if ( strlen( $bit ) % 8 ) {
		$bit .= str_repeat( '0', 8 - strlen( $bit ) % 8 );
	}

	$tu_ma = array();

	for ( $i = 0; $i < strlen( $bit ); $i += 8 ) {
		$tu_ma[] = bindec( substr( $bit, $i, 8 ) );
	}

	// Hai byte đệm luân phiên theo chuẩn cho tới khi đủ chỗ.
	$dem = array( 0xEC, 0x11 );
	$k   = 0;

	while ( count( $tu_ma ) < nntm_payos_qr_so_tu_ma_du_lieu( $ver ) ) {
		$tu_ma[] = $dem[ $k % 2 ];
		++$k;
	}

	return $tu_ma;
}

/**
 * Chia khối, tính sửa lỗi, rồi đan xen theo đúng thứ tự chuẩn quy định.
 *
 * @param int[] $tu_ma Từ mã dữ liệu.
 * @param int   $ver   Phiên bản.
 * @return int[]
 */
function nntm_payos_qr_dan_xen( array $tu_ma, int $ver ): array {
	$b   = nntm_payos_qr_bang_phien_ban()[ $ver ];
	$n_ec = $b[1];

	$khoi_du_lieu = array();
	$khoi_ec      = array();
	$vi_tri       = 0;

	foreach ( array( array( $b[2], $b[3] ), array( $b[4], $b[5] ) ) as $nhom ) {
		for ( $i = 0; $i < $nhom[0]; $i++ ) {
			$khoi           = array_slice( $tu_ma, $vi_tri, $nhom[1] );
			$vi_tri        += $nhom[1];
			$khoi_du_lieu[] = $khoi;
			$khoi_ec[]      = nntm_payos_qr_sua_loi( $khoi, $n_ec );
		}
	}

	$ra      = array();
	$dai_max = max( array_map( 'count', $khoi_du_lieu ) );

	for ( $i = 0; $i < $dai_max; $i++ ) {
		foreach ( $khoi_du_lieu as $k ) {
			if ( isset( $k[ $i ] ) ) {
				$ra[] = $k[ $i ];
			}
		}
	}

	for ( $i = 0; $i < $n_ec; $i++ ) {
		foreach ( $khoi_ec as $k ) {
			$ra[] = $k[ $i ];
		}
	}

	return $ra;
}

/* -------------------------------------------------------------------------
 * Dựng lưới
 * ---------------------------------------------------------------------- */

/**
 * Đặt sẵn các hoa tiêu, vạch nhịp và vùng dành riêng.
 *
 * Trả về hai lưới cùng cỡ: lưới giá trị (0/1) và lưới đánh dấu ô nào đã bị
 * chiếm (true = không được đặt dữ liệu vào).
 *
 * @param int $ver Phiên bản.
 * @return array{0: array, 1: array, 2: int}
 */
function nntm_payos_qr_khung( int $ver ): array {
	$n      = $ver * 4 + 17;
	$o      = array_fill( 0, $n, array_fill( 0, $n, 0 ) );
	$chiem  = array_fill( 0, $n, array_fill( 0, $n, false ) );

	$dat = static function ( int $x, int $y, int $v ) use ( &$o, &$chiem, $n ): void {
		if ( $x < 0 || $y < 0 || $x >= $n || $y >= $n ) {
			return;
		}

		$o[ $y ][ $x ]     = $v;
		$chiem[ $y ][ $x ] = true;
	};

	// Ba hoa tiêu định vị 7×7 kèm vạch ngăn.
	foreach ( array( array( 0, 0 ), array( $n - 7, 0 ), array( 0, $n - 7 ) ) as $goc ) {
		for ( $dy = -1; $dy <= 7; $dy++ ) {
			for ( $dx = -1; $dx <= 7; $dx++ ) {
				$trong_hoa_tieu = $dx >= 0 && $dx <= 6 && $dy >= 0 && $dy <= 6;
				$den            = $trong_hoa_tieu
					&& ( 0 === $dx || 6 === $dx || 0 === $dy || 6 === $dy
						|| ( $dx >= 2 && $dx <= 4 && $dy >= 2 && $dy <= 4 ) );

				$dat( $goc[0] + $dx, $goc[1] + $dy, $den ? 1 : 0 );
			}
		}
	}

	// Hai vạch nhịp, đen trắng xen kẽ.
	for ( $i = 8; $i < $n - 8; $i++ ) {
		$dat( $i, 6, ( $i % 2 ) ? 0 : 1 );
		$dat( 6, $i, ( $i % 2 ) ? 0 : 1 );
	}

	// Hoa tiêu căn chỉnh 5×5, bỏ những chỗ đè lên hoa tiêu định vị.
	$tam = nntm_payos_qr_tam_can_chinh()[ $ver ];

	foreach ( $tam as $cy ) {
		foreach ( $tam as $cx ) {
			$de_len_dinh_vi = ( $cx <= 8 && $cy <= 8 )
				|| ( $cx >= $n - 9 && $cy <= 8 )
				|| ( $cx <= 8 && $cy >= $n - 9 );

			if ( $de_len_dinh_vi ) {
				continue;
			}

			for ( $dy = -2; $dy <= 2; $dy++ ) {
				for ( $dx = -2; $dx <= 2; $dx++ ) {
					$den = 2 === max( abs( $dx ), abs( $dy ) ) || ( 0 === $dx && 0 === $dy );
					$dat( $cx + $dx, $cy + $dy, $den ? 1 : 0 );
				}
			}
		}
	}

	// Ô đen cố định.
	$dat( 8, $n - 8, 1 );

	// Giữ chỗ cho thông tin định dạng (ghi sau, khi đã chọn mặt nạ).
	for ( $i = 0; $i <= 8; $i++ ) {
		if ( 6 !== $i ) {
			$dat( $i, 8, 0 );
			$dat( 8, $i, 0 );
		}
	}

	for ( $i = 0; $i < 8; $i++ ) {
		$dat( $n - 1 - $i, 8, 0 );

		if ( $i < 7 ) {
			$dat( 8, $n - 1 - $i, 0 );
		}
	}

	/*
	 * Từ phiên bản 7 trở lên còn hai khối 6×3 ghi số hiệu phiên bản: một khối
	 * nằm trên hoa tiêu góc dưới bên trái, một khối bên trái hoa tiêu góc trên
	 * bên phải. Giữ chỗ ở đây, ghi bit ở nntm_payos_qr_ghi_phien_ban().
	 *
	 * Thiếu bước này thì lưới thừa đúng 36 ô, dữ liệu bị rải lệch và máy quét
	 * đọc ra rác — bộ tự kiểm bắt được bằng phép đếm ô trống.
	 */
	if ( $ver >= 7 ) {
		for ( $i = 0; $i < 18; $i++ ) {
			$a = intdiv( $i, 3 );
			$b = $i % 3;

			$dat( $a, $n - 11 + $b, 0 );
			$dat( $n - 11 + $b, $a, 0 );
		}
	}

	return array( $o, $chiem, $n );
}

/**
 * Ghi 18 bit thông tin phiên bản (chỉ phiên bản 7 trở lên), có mã BCH.
 *
 * @param array $o   Lưới (tham chiếu).
 * @param int   $n   Cạnh.
 * @param int   $ver Phiên bản.
 */
function nntm_payos_qr_ghi_phien_ban( array &$o, int $n, int $ver ): void {
	if ( $ver < 7 ) {
		return;
	}

	$bch = $ver << 12;

	for ( $i = 17; $i >= 12; $i-- ) {
		if ( $bch & ( 1 << $i ) ) {
			$bch ^= 0x1F25 << ( $i - 12 );
		}
	}

	$ma = ( $ver << 12 ) | $bch;

	for ( $i = 0; $i < 18; $i++ ) {
		$bit = ( $ma >> $i ) & 1;
		$a   = intdiv( $i, 3 );
		$b   = $i % 3;

		$o[ $n - 11 + $b ][ $a ] = $bit;
		$o[ $a ][ $n - 11 + $b ] = $bit;
	}
}

/**
 * Rải bit dữ liệu theo đường zigzag từ góc dưới bên phải.
 *
 * @param array  $o     Lưới giá trị (tham chiếu).
 * @param array  $chiem Lưới ô đã chiếm.
 * @param int    $n     Cạnh lưới.
 * @param string $bit   Chuỗi bit.
 */
function nntm_payos_qr_rai_bit( array &$o, array $chiem, int $n, string $bit ): void {
	$k    = 0;
	$len  = strlen( $bit );
	$len_ = $len;

	for ( $cot = $n - 1; $cot > 0; $cot -= 2 ) {
		// Cột số 6 là vạch nhịp dọc — bỏ qua, dịch sang trái một cột.
		if ( 6 === $cot ) {
			--$cot;
		}

		for ( $i = 0; $i < $n; $i++ ) {
			$len2 = ( ( ( $n - 1 - $cot ) >> 1 ) & 1 );
			$hang = $len2 ? $i : $n - 1 - $i;

			for ( $j = 0; $j < 2; $j++ ) {
				$x = $cot - $j;

				if ( $chiem[ $hang ][ $x ] ) {
					continue;
				}

				$o[ $hang ][ $x ] = ( $k < $len_ && '1' === $bit[ $k ] ) ? 1 : 0;
				++$k;
			}
		}
	}
}

/**
 * Giá trị mặt nạ tại một ô.
 */
function nntm_payos_qr_mat_na( int $ma, int $x, int $y ): bool {
	switch ( $ma ) {
		case 0:
			return 0 === ( $x + $y ) % 2;
		case 1:
			return 0 === $y % 2;
		case 2:
			return 0 === $x % 3;
		case 3:
			return 0 === ( $x + $y ) % 3;
		case 4:
			return 0 === ( intdiv( $y, 2 ) + intdiv( $x, 3 ) ) % 2;
		case 5:
			return 0 === ( $x * $y ) % 2 + ( $x * $y ) % 3;
		case 6:
			return 0 === ( ( $x * $y ) % 2 + ( $x * $y ) % 3 ) % 2;
		default:
			return 0 === ( ( $x + $y ) % 2 + ( $x * $y ) % 3 ) % 2;
	}
}

/**
 * Chấm điểm phạt của một lưới — điểm càng thấp càng dễ quét.
 *
 * @param array $o Lưới.
 * @param int   $n Cạnh.
 */
function nntm_payos_qr_diem_phat( array $o, int $n ): int {
	$diem = 0;

	// Quy tắc 1: dãy từ 5 ô cùng màu trở lên.
	for ( $lan = 0; $lan < 2; $lan++ ) {
		for ( $a = 0; $a < $n; $a++ ) {
			$dem   = 1;
			$truoc = -1;

			for ( $b = 0; $b < $n; $b++ ) {
				$v = $lan ? $o[ $b ][ $a ] : $o[ $a ][ $b ];

				if ( $v === $truoc ) {
					++$dem;
				} else {
					if ( $dem >= 5 ) {
						$diem += 3 + ( $dem - 5 );
					}
					$dem   = 1;
					$truoc = $v;
				}
			}

			if ( $dem >= 5 ) {
				$diem += 3 + ( $dem - 5 );
			}
		}
	}

	// Quy tắc 2: khối 2×2 cùng màu.
	for ( $y = 0; $y < $n - 1; $y++ ) {
		for ( $x = 0; $x < $n - 1; $x++ ) {
			$v = $o[ $y ][ $x ];

			if ( $v === $o[ $y ][ $x + 1 ] && $v === $o[ $y + 1 ][ $x ] && $v === $o[ $y + 1 ][ $x + 1 ] ) {
				$diem += 3;
			}
		}
	}

	// Quy tắc 3: hình giống hoa tiêu định vị (1:1:3:1:1) kèm bốn ô trắng.
	$mau = array(
		array( 1, 0, 1, 1, 1, 0, 1, 0, 0, 0, 0 ),
		array( 0, 0, 0, 0, 1, 0, 1, 1, 1, 0, 1 ),
	);

	for ( $y = 0; $y < $n; $y++ ) {
		for ( $x = 0; $x < $n; $x++ ) {
			foreach ( $mau as $m ) {
				$khop_ngang = $x + 11 <= $n;
				$khop_doc   = $y + 11 <= $n;

				for ( $i = 0; $i < 11; $i++ ) {
					if ( $khop_ngang && $o[ $y ][ $x + $i ] !== $m[ $i ] ) {
						$khop_ngang = false;
					}
					if ( $khop_doc && $o[ $y + $i ][ $x ] !== $m[ $i ] ) {
						$khop_doc = false;
					}
				}

				if ( $khop_ngang ) {
					$diem += 40;
				}
				if ( $khop_doc ) {
					$diem += 40;
				}
			}
		}
	}

	// Quy tắc 4: lệch tỉ lệ đen/trắng so với 50%.
	$den = 0;

	foreach ( $o as $hang ) {
		$den += array_sum( $hang );
	}

	$ty = (int) ( abs( $den * 100 / ( $n * $n ) - 50 ) / 5 );

	return $diem + $ty * 10;
}

/**
 * Ghi 15 bit thông tin định dạng (mức sửa lỗi + mặt nạ), có mã BCH.
 *
 * @param array $o  Lưới (tham chiếu).
 * @param int   $n  Cạnh.
 * @param int   $ma Số hiệu mặt nạ.
 */
function nntm_payos_qr_ghi_dinh_dang( array &$o, int $n, int $ma ): void {
	// 00 = mức M.
	$du_lieu = ( 0b00 << 3 ) | $ma;
	$bch     = $du_lieu << 10;

	for ( $i = 14; $i >= 10; $i-- ) {
		if ( $bch & ( 1 << $i ) ) {
			$bch ^= 0b10100110111 << ( $i - 10 );
		}
	}

	$dinh_dang = ( ( $du_lieu << 10 ) | $bch ) ^ 0b101010000010010;

	for ( $i = 0; $i < 15; $i++ ) {
		$bit = ( $dinh_dang >> $i ) & 1;

		// Bản sao thứ nhất: quanh hoa tiêu góc trên bên trái.
		if ( $i < 6 ) {
			$o[ $i ][8] = $bit;
		} elseif ( 6 === $i ) {
			$o[7][8] = $bit;
		} elseif ( 7 === $i ) {
			$o[8][8] = $bit;
		} elseif ( 8 === $i ) {
			$o[8][7] = $bit;
		} else {
			$o[8][ 14 - $i ] = $bit;
		}

		// Bản sao thứ hai: chia đôi sang hai hoa tiêu còn lại.
		if ( $i < 8 ) {
			$o[8][ $n - 1 - $i ] = $bit;
		} else {
			$o[ $n - 15 + $i ][8] = $bit;
		}
	}
}

/**
 * Dựng lưới QR hoàn chỉnh cho một chuỗi.
 *
 * @param string $chuoi Chuỗi cần mã hoá.
 * @return array<int,array<int,int>>|null Lưới, hoặc null nếu chuỗi quá dài.
 */
function nntm_payos_qr_ma_tran( string $chuoi ) {
	$ver = 0;

	foreach ( array_keys( nntm_payos_qr_bang_phien_ban() ) as $v ) {
		if ( strlen( $chuoi ) <= nntm_payos_qr_suc_chua( $v ) ) {
			$ver = $v;
			break;
		}
	}

	if ( ! $ver ) {
		return null;
	}

	$tu_ma = nntm_payos_qr_dan_xen( nntm_payos_qr_tu_ma_du_lieu( $chuoi, $ver ), $ver );

	$bit = '';
	foreach ( $tu_ma as $t ) {
		$bit .= str_pad( decbin( $t ), 8, '0', STR_PAD_LEFT );
	}

	list( $goc, $chiem, $n ) = nntm_payos_qr_khung( $ver );

	$tot_nhat = null;
	$diem_min = PHP_INT_MAX;

	for ( $ma = 0; $ma < 8; $ma++ ) {
		$o = $goc;
		nntm_payos_qr_rai_bit( $o, $chiem, $n, $bit );

		for ( $y = 0; $y < $n; $y++ ) {
			for ( $x = 0; $x < $n; $x++ ) {
				if ( ! $chiem[ $y ][ $x ] && nntm_payos_qr_mat_na( $ma, $x, $y ) ) {
					$o[ $y ][ $x ] ^= 1;
				}
			}
		}

		nntm_payos_qr_ghi_dinh_dang( $o, $n, $ma );
		nntm_payos_qr_ghi_phien_ban( $o, $n, $ver );

		$diem = nntm_payos_qr_diem_phat( $o, $n );

		if ( $diem < $diem_min ) {
			$diem_min = $diem;
			$tot_nhat = $o;
		}
	}

	return $tot_nhat;
}

/**
 * Vẽ mã QR ra SVG.
 *
 * Gộp các ô đen liền nhau trên cùng một hàng thành một hình chữ nhật — ảnh nhẹ
 * hơn nhiều so với vẽ từng ô, mà trông y hệt.
 *
 * @param string $chuoi Chuỗi cần mã hoá.
 * @param int    $vien  Số ô lề trắng quanh mã (chuẩn khuyên dùng 4).
 * @return string SVG, hoặc '' nếu không mã hoá được.
 */
function nntm_payos_qr_svg( string $chuoi, int $vien = 4 ): string {
	if ( '' === $chuoi ) {
		return '';
	}

	$o = nntm_payos_qr_ma_tran( $chuoi );

	if ( ! $o ) {
		return '';
	}

	$n     = count( $o );
	$canh  = $n + $vien * 2;
	$hinh  = '';

	foreach ( $o as $y => $hang ) {
		$x = 0;

		while ( $x < $n ) {
			if ( ! $hang[ $x ] ) {
				++$x;
				continue;
			}

			$dai = 0;
			while ( $x + $dai < $n && $hang[ $x + $dai ] ) {
				++$dai;
			}

			$hinh .= sprintf( '<rect x="%d" y="%d" width="%d" height="1"/>', $x + $vien, $y + $vien, $dai );
			$x    += $dai;
		}
	}

	return sprintf(
		'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 %1$d %1$d" shape-rendering="crispEdges" role="img" aria-label="%2$s">'
		. '<rect width="%1$d" height="%1$d" fill="#fff"/><g fill="#000">%3$s</g></svg>',
		$canh,
		esc_attr__( 'Mã QR thanh toán', 'nntm' ),
		$hinh
	);
}

/**
 * Tự kiểm bộ mã hoá — dùng cho bộ kiểm tra, không chạy lúc phục vụ trang.
 *
 * Ba phép đối chiếu độc lập:
 *   1. Sức chứa suy từ bảng khối phải khớp bảng dung lượng chuẩn.
 *   2. Số ô đặt được dữ liệu trên lưới chia 8 phải bằng tổng từ mã của bảng.
 *      Cái này suy từ hình học của lưới, không dính gì tới bảng — khớp nghĩa
 *      là cả bảng lẫn cách dựng hoa tiêu/vạch nhịp đều đúng.
 *   3. Từ mã dữ liệu ghép với từ mã sửa lỗi phải chia hết cho đa thức sinh
 *      (tính chất định nghĩa của Reed–Solomon).
 *
 * @return array<string,bool>
 */
function nntm_payos_qr_tu_kiem(): array {
	$suc_chua_chuan = array( 1 => 14, 2 => 26, 3 => 42, 4 => 62, 5 => 84, 6 => 106, 7 => 122, 8 => 152, 9 => 180, 10 => 213 );
	$ra             = array();
	$ok_suc_chua    = true;
	$ok_o_trong     = true;

	foreach ( nntm_payos_qr_bang_phien_ban() as $ver => $b ) {
		if ( nntm_payos_qr_suc_chua( $ver ) !== $suc_chua_chuan[ $ver ] ) {
			$ok_suc_chua = false;
		}

		list( , $chiem, $n ) = nntm_payos_qr_khung( $ver );
		$trong               = 0;

		for ( $y = 0; $y < $n; $y++ ) {
			for ( $x = 0; $x < $n; $x++ ) {
				if ( ! $chiem[ $y ][ $x ] ) {
					++$trong;
				}
			}
		}

		if ( intdiv( $trong, 8 ) !== $b[0] ) {
			$ok_o_trong = false;
		}
	}

	$ra['suc_chua_khop_bang_chuan'] = $ok_suc_chua;
	$ra['so_o_trong_khop_tong_tu_ma'] = $ok_o_trong;

	// Reed–Solomon: phần dư khi chia cho đa thức sinh phải bằng 0.
	$du_lieu = array( 32, 91, 11, 120, 209, 114, 220, 77, 67, 64, 236, 17, 236, 17 );
	$ec      = nntm_payos_qr_sua_loi( $du_lieu, 10 );
	$ghep    = array_merge( $du_lieu, $ec );
	$g       = nntm_payos_qr_da_thuc_sinh( 10 );

	for ( $i = 0; $i < count( $du_lieu ); $i++ ) {
		$dau = $ghep[ $i ];

		if ( 0 === $dau ) {
			continue;
		}

		foreach ( $g as $j => $he_so ) {
			$ghep[ $i + $j ] ^= nntm_payos_qr_nhan( $he_so, $dau );
		}
	}

	$ra['sua_loi_chia_het_da_thuc_sinh'] = 0 === array_sum( array_slice( $ghep, count( $du_lieu ) ) );

	return $ra;
}
