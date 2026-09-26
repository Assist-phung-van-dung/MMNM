<?php
/**
 * Khớp câu tìm kiếm với một Từ khoá động có gắn hiệu ứng con trỏ.
 *
 * Khách gõ "hoa" vào ô tìm, nếu có từ khoá động "Hoa sen" đã gắn hiệu ứng
 * "hoa-sen" thì trang kết quả tìm kiếm dùng luôn hiệu ứng đó thay vì cài đặt
 * chung — một mặt trang trí nhỏ giúp trang kết quả "có hồn" hơn theo đúng câu
 * khách vừa gõ.
 *
 * Quy tắc so khớp GIỐNG tìm kiếm chữ (nntm-search/includes/text.php) để không
 * lệch: câu tìm có dấu thì đòi đúng dấu, không dấu thì so bỏ dấu; luôn khớp
 * theo NGUYÊN TỪ (không khớp giữa từ, "hoa" không khớp "hoàng").
 *
 * @package NNTM_Core
 */

defined( 'ABSPATH' ) || exit;

/**
 * Tách một chuỗi thành mảng "từ" (chuỗi ký tự chữ/số liên tục), bỏ dấu câu.
 *
 * @return string[]
 */
function nntm_tkd_tach_tu( string $s ): array {
	return array_values(
		array_filter(
			preg_split( '/[^\p{L}\p{N}]+/u', trim( $s ) ) ?: array(),
			static fn( string $tu ): bool => '' !== $tu
		)
	);
}

/** Câu có dấu tiếng Việt không — dùng hàm chuẩn của nntm-search nếu có. */
function nntm_tkd_co_dau( string $s ): bool {
	if ( function_exists( 'nntm_search_term_has_diacritics' ) ) {
		return nntm_search_term_has_diacritics( $s );
	}

	// Không có plugin tìm kiếm: tự so đơn giản (thường hoá rồi so với bản đã bỏ dấu).
	$khong_dau = strtr(
		mb_strtolower( $s, 'UTF-8' ),
		array(
			'à' => 'a', 'á' => 'a', 'ạ' => 'a', 'ả' => 'a', 'ã' => 'a', 'â' => 'a', 'ầ' => 'a', 'ấ' => 'a', 'ậ' => 'a', 'ẩ' => 'a', 'ẫ' => 'a', 'ă' => 'a', 'ằ' => 'a', 'ắ' => 'a', 'ặ' => 'a', 'ẳ' => 'a', 'ẵ' => 'a',
			'è' => 'e', 'é' => 'e', 'ẹ' => 'e', 'ẻ' => 'e', 'ẽ' => 'e', 'ê' => 'e', 'ề' => 'e', 'ế' => 'e', 'ệ' => 'e', 'ể' => 'e', 'ễ' => 'e',
			'ì' => 'i', 'í' => 'i', 'ị' => 'i', 'ỉ' => 'i', 'ĩ' => 'i',
			'ò' => 'o', 'ó' => 'o', 'ọ' => 'o', 'ỏ' => 'o', 'õ' => 'o', 'ô' => 'o', 'ồ' => 'o', 'ố' => 'o', 'ộ' => 'o', 'ổ' => 'o', 'ỗ' => 'o', 'ơ' => 'o', 'ờ' => 'o', 'ớ' => 'o', 'ợ' => 'o', 'ở' => 'o', 'ỡ' => 'o',
			'ù' => 'u', 'ú' => 'u', 'ụ' => 'u', 'ủ' => 'u', 'ũ' => 'u', 'ư' => 'u', 'ừ' => 'u', 'ứ' => 'u', 'ự' => 'u', 'ử' => 'u', 'ữ' => 'u',
			'ỳ' => 'y', 'ý' => 'y', 'ỵ' => 'y', 'ỷ' => 'y', 'ỹ' => 'y',
			'đ' => 'd',
		)
	);

	return $khong_dau !== mb_strtolower( $s, 'UTF-8' );
}

/** Bỏ dấu + thường hoá — dùng hàm chuẩn của nntm-search nếu có. */
function nntm_tkd_bo_dau( string $s ): string {
	if ( function_exists( 'nntm_search_fold' ) ) {
		return nntm_search_fold( $s );
	}

	return mb_strtolower( $s, 'UTF-8' );
}

/**
 * Đối chiếu MỘT cách viết (từ khoá hoặc biến thể) với câu tìm.
 *
 * @return int 0 = không khớp, 1 = khớp nguyên văn cả cụm, 2 = từ khoá nằm trọn
 *             trong câu tìm, 3 = câu tìm là tiền tố theo từ của từ khoá.
 */
function nntm_tkd_doi_chieu_mot_dang( array $tu_cau, array $tu_dang, bool $cau_co_dau ): int {
	if ( empty( $tu_cau ) || empty( $tu_dang ) ) {
		return 0;
	}

	$chuan = static function ( string $tu ) use ( $cau_co_dau ): string {
		return $cau_co_dau ? mb_strtolower( $tu, 'UTF-8' ) : nntm_tkd_bo_dau( $tu );
	};

	$a = array_map( $chuan, $tu_cau );  // từ trong câu tìm.
	$b = array_map( $chuan, $tu_dang ); // từ trong dạng viết của từ khoá.

	// 1) Khớp nguyên văn cả cụm.
	if ( $a === $b ) {
		return 1;
	}

	// 2) Từ khoá ($b) nằm trọn trong câu tìm ($a), liên tục, đúng thứ tự.
	$dai_a = count( $a );
	$dai_b = count( $b );

	if ( $dai_b <= $dai_a ) {
		for ( $i = 0; $i <= $dai_a - $dai_b; $i++ ) {
			if ( array_slice( $a, $i, $dai_b ) === $b ) {
				return 2;
			}
		}
	}

	// 3) Câu tìm là tiền tố theo từ của từ khoá (vd "hoa" trong "hoa sen").
	if ( $dai_a <= $dai_b && array_slice( $b, 0, $dai_a ) === $a ) {
		return 3;
	}

	return 0;
}

/**
 * Tìm một Từ khoá động có gắn hiệu ứng con trỏ khớp với câu tìm kiếm.
 *
 * @param string $cau Câu tìm kiếm thô (get_search_query()).
 * @return array{post_id:int, kieu:string, mau:string}|null
 */
function nntm_tkd_khop_tim_kiem( string $cau ): ?array {
	$cau = trim( wp_strip_all_tags( $cau ) );

	if ( '' === $cau || ! class_exists( '\NNTM\Core\Tu_Khoa_Dong' ) ) {
		return null;
	}

	if ( class_exists( '\Normalizer' ) ) {
		$cau = (string) \Normalizer::normalize( $cau, \Normalizer::FORM_C );
	}

	$tu_cau = nntm_tkd_tach_tu( $cau );

	if ( empty( $tu_cau ) ) {
		return null;
	}

	$cau_co_dau = nntm_tkd_co_dau( $cau );

	$ids = get_posts(
		array(
			'post_type'      => \NNTM\Core\Tu_Khoa_Dong::POST_TYPE,
			'post_status'    => 'publish',
			'posts_per_page' => 300,
			'fields'         => 'ids',
			'no_found_rows'  => true,
			'meta_query'     => array(
				array(
					'key'     => '_nntm_tkd_con_tro_kieu',
					'value'   => '',
					'compare' => '!=',
				),
			),
		)
	);

	$ung_vien = array();

	foreach ( $ids as $id ) {
		$id   = (int) $id;
		$kieu = nntm_con_tro_sanitize_kieu_dung_chung( get_post_meta( $id, '_nntm_tkd_con_tro_kieu', true ) );

		if ( '' === $kieu ) {
			continue; // Meta còn giá trị rác từ trước — bỏ qua, không dùng làm cấu hình.
		}

		$ten      = get_the_title( $id );
		$bien_the = get_post_meta( $id, '_nntm_tkd_bien_the', true );
		$bien_the = is_array( $bien_the ) ? $bien_the : array();
		$cac_dang = array_merge( array( $ten ), $bien_the );

		$loai_tot_nhat = 0;
		$dai_dang_tot_nhat = 0;

		foreach ( $cac_dang as $dang ) {
			$tu_dang = nntm_tkd_tach_tu( (string) $dang );
			$loai    = nntm_tkd_doi_chieu_mot_dang( $tu_cau, $tu_dang, $cau_co_dau );

			if ( 0 === $loai ) {
				continue;
			}

			// Ưu tiên loại nhỏ hơn (1 tốt hơn 2 tốt hơn 3); cùng loại thì lấy dạng dài hơn.
			if ( 0 === $loai_tot_nhat || $loai < $loai_tot_nhat || ( $loai === $loai_tot_nhat && count( $tu_dang ) > $dai_dang_tot_nhat ) ) {
				$loai_tot_nhat     = $loai;
				$dai_dang_tot_nhat = count( $tu_dang );
			}
		}

		if ( 0 === $loai_tot_nhat ) {
			continue;
		}

		$bai = get_post( $id );

		$ung_vien[] = array(
			'post_id'     => $id,
			'kieu'        => $kieu,
			'mau'         => nntm_con_tro_sanitize_mau_dung_chung( get_post_meta( $id, '_nntm_tkd_con_tro_mau', true ) ),
			'loai'        => $loai_tot_nhat,
			'do_dai'      => $dai_dang_tot_nhat,
			'menu_order'  => $bai ? (int) $bai->menu_order : 0,
			'ngay'        => $bai ? strtotime( (string) $bai->post_date_gmt ) : 0,
		);
	}

	if ( empty( $ung_vien ) ) {
		$ket = null;
	} else {
		usort(
			$ung_vien,
			static function ( array $a, array $b ): int {
				if ( $a['loai'] !== $b['loai'] ) {
					return $a['loai'] <=> $b['loai']; // 1 (nguyên văn) trước, rồi 2, rồi 3.
				}

				if ( 2 === $a['loai'] ) {
					// Nằm trọn trong câu tìm: từ khoá DÀI HƠN ưu tiên hơn.
					if ( $a['do_dai'] !== $b['do_dai'] ) {
						return $b['do_dai'] <=> $a['do_dai'];
					}
				} elseif ( 3 === $a['loai'] ) {
					// Tiền tố: từ khoá NGẮN HƠN ưu tiên hơn.
					if ( $a['do_dai'] !== $b['do_dai'] ) {
						return $a['do_dai'] <=> $b['do_dai'];
					}
				}

				if ( $a['menu_order'] !== $b['menu_order'] ) {
					return $a['menu_order'] <=> $b['menu_order'];
				}

				return $b['ngay'] <=> $a['ngay'];
			}
		);

		$thang = $ung_vien[0];
		$ket   = array(
			'post_id' => $thang['post_id'],
			'kieu'    => $thang['kieu'],
			'mau'     => $thang['mau'],
		);
	}

	/**
	 * Chỉnh lại kết quả khớp từ khoá <-> câu tìm kiếm.
	 *
	 * @param array{post_id:int,kieu:string,mau:string}|null $ket Kết quả khớp được (có thể null).
	 * @param string                                          $cau Câu tìm kiếm gốc.
	 */
	return apply_filters( 'nntm_tkd_khop_tim_kiem', $ket, $cau );
}
