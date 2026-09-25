<?php
/**
 * Trang kết quả đầy đủ cho tìm bằng hình ảnh — Phase 2, khảo sát câu 15–16.
 *
 * Bảng gợi ý dưới thanh tìm chỉ chứa 6 kết quả, và "Xem tất cả" trước đây chỉ
 * tìm chữ theo TỪ KHOÁ ĐẦU TIÊN — mất các từ khoá còn lại lẫn phần "ảnh trông
 * giống". Trang này giữ đủ cả hai.
 *
 * Ảnh không đi qua URL được nên /image trả về một MÃ PHIÊN; máy chủ nhớ theo mã
 * đó (30 phút) đúng hai thứ: danh sách từ khoá và vector của ảnh. KHÔNG lưu ảnh —
 * ảnh xem trước trên trang kết quả do trình duyệt tự giữ trong sessionStorage.
 *
 * Kết quả KHÔNG được lưu sẵn mà tính lại cho người đang mở trang: gửi link cho
 * người chưa đăng nhập thì họ chỉ thấy phần công khai, dù người tìm là thành viên.
 *
 * @package NNTM_Search
 */

defined( 'ABSPATH' ) || exit;

/** Mã phiên hợp lệ: 24 ký tự chữ-số. */
function nntm_search_image_token_ok( string $token ): bool {
	return 1 === preg_match( '/^[A-Za-z0-9]{24}$/', $token );
}

/** Thời gian nhớ một phiên tìm bằng ảnh. */
function nntm_search_image_session_ttl(): int {
	return (int) apply_filters( 'nntm_search_image_session_ttl', 30 * MINUTE_IN_SECONDS );
}

/**
 * Lưu một phiên. Vector làm tròn 5 chữ số — đủ để xếp hạng, JSON nhẹ hơn một nửa.
 *
 * @param array<int,array{word:string,score:float}> $keywords Từ khoá đọc được.
 * @param float[]|null                              $vector   Vector ảnh (có thể không có).
 * @return string Mã phiên.
 */
function nntm_search_image_session_create( array $keywords, ?array $vector ): string {
	$token = wp_generate_password( 24, false, false );

	set_transient(
		'nntm_tim_anh_' . $token,
		array(
			'k' => array_values( $keywords ),
			'v' => $vector ? array_map( static fn( $x ) => round( (float) $x, 5 ), $vector ) : null,
			't' => time(),
		),
		nntm_search_image_session_ttl()
	);

	return $token;
}

/** @return array{k:array,v:?array,t:int}|null */
function nntm_search_image_session( string $token ): ?array {
	if ( ! nntm_search_image_token_ok( $token ) ) {
		return null;
	}

	$s = get_transient( 'nntm_tim_anh_' . $token );

	return is_array( $s ) && isset( $s['k'] ) ? $s : null;
}

/**
 * URL trang kết quả. Dùng trang tìm kiếm có sẵn (?s=…) để theme, CSS, thanh
 * tìm, tiêu đề trang đều hoạt động như tìm chữ; `s` là các từ khoá đọc được.
 */
function nntm_search_image_page_url( string $token, array $keywords ): string {
	$words = array_slice( array_column( $keywords, 'word' ), 0, 3 );

	return add_query_arg(
		array(
			's'        => rawurlencode( $words ? implode( ' ', $words ) : __( 'hình ảnh', 'nntm' ) ),
			'nntm_anh' => $token,
		),
		home_url( '/' )
	);
}

/** Đang ở trang kết quả tìm bằng ảnh không (mã lấy từ URL). */
function nntm_search_image_page_token(): string {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- chỉ đọc mã phiên để hiển thị.
	$token = isset( $_GET['nntm_anh'] ) ? sanitize_text_field( wp_unslash( $_GET['nntm_anh'] ) ) : '';

	return is_search() && nntm_search_image_token_ok( $token ) ? $token : '';
}

/**
 * Dữ liệu cho trang kết quả.
 *
 * @param string $token    Mã phiên.
 * @param int    $page     Trang (bắt đầu từ 1).
 * @param int    $per_page Số bài mỗi trang.
 * @return array{ok:bool,keywords:array,rows:array,total:int,similar:array}
 */
function nntm_search_image_page( string $token, int $page = 1, int $per_page = 10 ): array {
	$ket = array(
		'ok'       => false,
		'keywords' => array(),
		'rows'     => array(),
		'total'    => 0,
		'similar'  => array(),
	);

	$s = nntm_search_image_session( $token );
	if ( ! $s ) {
		return $ket;
	}

	$ket['ok']       = true;
	$ket['keywords'] = (array) $s['k'];

	/*
	 * Tìm TỪNG từ khoá rồi gộp, từ mạnh nhất trước — không AND tất cả lại: một
	 * trang hiếm khi chứa đồng thời "mặt trời", "sương mù" và "núi" (lý do đã ghi
	 * ở nntm_search_handle_image). Bài khớp nhiều từ khoá được đẩy lên đầu.
	 */
	$theo_link = array();
	$so_khop   = array();

	foreach ( $ket['keywords'] as $kw ) {
		$word = (string) ( $kw['word'] ?? '' );
		if ( '' === $word ) {
			continue;
		}

		foreach ( nntm_search_query( $word, 'all', 1, 30, false )['rows'] as $row ) {
			$link = (string) $row['permalink'];

			if ( ! isset( $theo_link[ $link ] ) ) {
				$theo_link[ $link ] = $row;
				$so_khop[ $link ]   = array();
			}
			$so_khop[ $link ][] = $word;
		}
	}

	// Sắp xếp ổn định: nhiều từ khoá khớp hơn lên trước, còn lại giữ thứ tự tìm.
	$thu_tu = array_flip( array_keys( $theo_link ) );
	uksort(
		$theo_link,
		static fn( $a, $b ) => ( count( $so_khop[ $b ] ) <=> count( $so_khop[ $a ] ) ) ?: ( $thu_tu[ $a ] <=> $thu_tu[ $b ] )
	);

	foreach ( $theo_link as $link => &$row ) {
		/* translators: %s: các từ khoá khớp, ví dụ “mặt trời”, “núi” */
		$khop         = sprintf( __( 'khớp %s', 'nntm' ), implode( ', ', array_map( static fn( $w ) => '“' . $w . '”', array_unique( $so_khop[ $link ] ) ) ) );
		$row['label'] = '' !== (string) ( $row['label'] ?? '' ) ? $row['label'] . ' · ' . $khop : $khop;
	}
	unset( $row );

	$tat_ca       = array_values( array_slice( $theo_link, 0, 60 ) );
	$ket['total'] = count( $tat_ca );
	$ket['rows']  = array_slice( $tat_ca, ( max( 1, $page ) - 1 ) * $per_page, $per_page );

	// "Ảnh trông giống" chỉ ở trang 1, bỏ những bài đã có trong danh sách trên.
	if ( 1 === max( 1, $page ) && ! empty( $s['v'] ) ) {
		$da_co = array_flip( array_column( $tat_ca, 'permalink' ) );

		foreach ( nntm_search_group_by_post( nntm_search_vector_search( (array) $s['v'], nntm_search_viewer_acl(), 60 ), 24 ) as $item ) {
			if ( ! isset( $da_co[ $item['permalink'] ] ) ) {
				$ket['similar'][] = $item;
			}
			if ( count( $ket['similar'] ) >= 8 ) {
				break;
			}
		}
	}

	return $ket;
}

/* ---------- Trang kết quả: không lập chỉ mục, nạp JS xem trước / tìm lại ---------- */

add_filter(
	'wp_robots',
	static function ( array $robots ): array {
		return '' !== nntm_search_image_page_token() ? wp_robots_no_robots( $robots ) : $robots;
	}
);

add_action(
	'wp_enqueue_scripts',
	static function (): void {
		if ( '' === nntm_search_image_page_token() ) {
			return;
		}

		$js = NNTM_SEARCH_DIR . '/assets/js/image-page.js';

		wp_enqueue_script(
			'nntm-search-image-page',
			NNTM_SEARCH_URI . 'assets/js/image-page.js',
			array( 'nntm-search-bar' ),
			(string) ( file_exists( $js ) ? filemtime( $js ) : NNTM_SEARCH_VERSION ),
			true
		);
	},
	20
);
