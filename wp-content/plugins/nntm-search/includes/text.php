<?php
/**
 * Vietnamese text helpers: diacritic folding, highlighting, excerpting.
 *
 * This is the canonical implementation. The theme delegates here when the
 * plugin is active, so a site never runs two different folding rules — if
 * they diverge, the search hit and the highlighted span stop lining up.
 *
 * @package NNTM_Search
 */

defined( 'ABSPATH' ) || exit;

/**
 * Map of accented Vietnamese characters to their base letter.
 *
 * A lookup table is used instead of Normalizer::NFD for two reasons:
 *   1. It replaces exactly one character with exactly one character, so
 *      offsets in the folded string line up with the original — which is
 *      what lets the highlighter cut at the right place. NFD splits one
 *      character into two and then drops one, shifting every offset after it.
 *   2. `đ` / `Đ` is its own letter and NFD does NOT decompose it. Miss that
 *      and typing "duong" never matches "đường".
 *
 * @return array<string, string>
 */
function nntm_search_fold_map(): array {
	static $map = null;

	if ( null !== $map ) {
		return $map;
	}

	$groups = array(
		'a' => 'àáạảãâầấậẩẫăằắặẳẵ',
		'e' => 'èéẹẻẽêềếệểễ',
		'i' => 'ìíịỉĩ',
		'o' => 'òóọỏõôồốộổỗơờớợởỡ',
		'u' => 'ùúụủũưừứựửữ',
		'y' => 'ỳýỵỷỹ',
		'd' => 'đ',
	);

	$map = array();

	foreach ( $groups as $base => $accented ) {
		foreach ( preg_split( '//u', $accented, -1, PREG_SPLIT_NO_EMPTY ) as $char ) {
			$map[ $char ]                            = $base;
			$map[ mb_strtoupper( $char, 'UTF-8' ) ]  = mb_strtoupper( $base, 'UTF-8' );
		}
	}

	return $map;
}

/**
 * Strip diacritics and lowercase, preserving character count.
 *
 * @param string $text Input.
 * @return string
 */
function nntm_search_fold( string $text ): string {
	return mb_strtolower( strtr( $text, nntm_search_fold_map() ), 'UTF-8' );
}

/**
 * Split a query into terms long enough to be worth matching.
 *
 * @param string $query Raw user query.
 * @return string[]
 */
function nntm_search_split_terms( string $query ): array {
	return array_values(
		array_filter(
			preg_split( '/\s+/u', trim( $query ) ) ?: array(),
			static fn( string $term ): bool => mb_strlen( $term ) >= 2
		)
	);
}

/**
 * Liệu gấp bỏ dấu có THỰC SỰ đổi từ này không — tức nó có dấu tiếng Việt,
 * chứ không phải đã ở dạng chữ thường/không dấu sẵn rồi.
 *
 * Dùng để quyết định: người dùng gõ CÓ dấu là tín hiệu họ muốn đúng từ đó,
 * không phải bất cứ từ nào gấp trùng (xem nntm_search_text_matches_terms()).
 * Gõ KHÔNG dấu thì giữ nguyên kiểu tìm không phân biệt dấu như đã cam kết.
 *
 * @param string $term Một từ trong câu tìm.
 * @return bool
 */
function nntm_search_term_has_diacritics( string $term ): bool {
	return mb_strtolower( $term, 'UTF-8' ) !== nntm_search_fold( $term );
}

/**
 * Lọc lại một đoạn chữ có thực sự chứa các từ CÓ DẤU người dùng đã gõ không.
 *
 * BUG có thật (báo 17/08/2026): tìm "rừng" ra cả "rụng", "rùng" — vì hai chỗ
 * đều gấp bỏ dấu trước khi so khớp: (1) FULLTEXT trên cột `folded` của trang
 * PDF (xem includes/pdf.php), (2) chính collation `utf8mb4_unicode_ci` của
 * CSDL coi ký tự có dấu ngang bằng ký tự gốc khi so `LIKE` — nên `WP_Query`
 * mặc định của WordPress (tìm bài viết thường) CŨNG bị, không cần dòng code
 * gấp dấu nào cả. Xác nhận bằng thực nghiệm: bài "...hoàn thành trùng tu..."
 * không hề có chữ "rừng" ở bất cứ đâu, vẫn bị `WP_Query` khớp.
 *
 * Không sửa được tầng collation (ảnh hưởng toàn site, rủi ro cao) nên chặn ở
 * đây: sau khi có danh sách ứng viên (từ FULLTEXT hoặc từ WP_Query), lọc lại
 * bằng chuỗi CÒN DẤU trên nội dung thật. Từ nào người dùng gõ không dấu thì bỏ
 * qua bước lọc này cho đúng từ đó — giữ nguyên khả năng "tìm không dấu vẫn ra".
 *
 * @param string   $haystack Nội dung thật (còn dấu) để so khớp.
 * @param string[] $terms    Các từ trong câu tìm, còn dấu.
 * @return bool
 */
function nntm_search_text_matches_terms( string $haystack, array $terms ): bool {
	$haystack = mb_strtolower( $haystack, 'UTF-8' );

	foreach ( $terms as $term ) {
		if ( ! nntm_search_term_has_diacritics( $term ) ) {
			continue; // Không dấu — không thu hẹp, giữ nguyên hành vi tìm không dấu.
		}

		if ( false === mb_strpos( $haystack, mb_strtolower( $term, 'UTF-8' ), 0, 'UTF-8' ) ) {
			return false;
		}
	}

	return true;
}

/**
 * "Một đoạn dài" — đủ dài để coi là một CỤM/CÂU chứ không phải vài từ khoá
 * rời rạc. Ngưỡng quyết định hành vi tìm: câu ngắn thì vẫn tìm kiểu "có đủ
 * các từ, bất kể vị trí" như trước; câu dài thì đòi chặt hơn — xem
 * nntm_search_content_matches_query().
 *
 * @param string[] $terms Các từ đã tách từ câu tìm.
 * @return bool
 */
function nntm_search_is_long_query( array $terms ): bool {
	return count( $terms ) > 3;
}

/**
 * Liệu CẢ CỤM câu tìm (không phải từng từ rời rạc) có xuất hiện liền mạch
 * trong nội dung không — so sau khi gấp bỏ dấu và gộp khoảng trắng thừa, để
 * chấp nhận xuống dòng/khoảng trắng do PDF tách trang hoặc HTML xuống dòng.
 *
 * @param string $haystack Nội dung thật.
 * @param string $query    Câu tìm gốc.
 * @return bool
 */
function nntm_search_folded_phrase_present( string $haystack, string $query ): bool {
	$needle = nntm_search_fold( trim( (string) preg_replace( '/\s+/u', ' ', $query ) ) );

	if ( '' === $needle ) {
		return true;
	}

	$haystack_folded = nntm_search_fold( trim( (string) preg_replace( '/\s+/u', ' ', $haystack ) ) );

	return false !== mb_strpos( $haystack_folded, $needle, 0, 'UTF-8' );
}

/**
 * Bộ lọc kết quả DÙNG CHUNG cho cả PDF (includes/pdf.php) và bài viết
 * thường (includes/engine.php) — một nơi, không hai bản dễ lệch.
 *
 * BUG có thật 17/08/2026 (thứ hai trong ngày, về câu tìm dài): gõ một câu
 * dài, hệ thống chỉ đòi "có đủ TỪNG TỪ ở đâu đó trong bài" (xem phần
 * required/optional trong nntm_search_pdf_pages(), và AND-theo-từ mặc định
 * của WP_Query) — nên bài hoặc trang PDF chỉ TÌNH CỜ chứa rải rác các từ
 * riêng lẻ, chẳng liên quan gì tới ý người tìm, vẫn lọt vào kết quả.
 *
 * Câu tìm dài (>3 từ) coi là một CỤM/CÂU: đòi hỏi cụm đó xuất hiện LIỀN
 * MẠCH (sau khi gấp dấu) trong nội dung — không chấp nhận khớp kiểu rải
 * rác từng từ nữa. Câu tìm ngắn (≤3 từ, tìm từ khoá thông thường) giữ
 * nguyên hành vi cũ, không thu hẹp gì.
 *
 * Kết hợp CẢ HAI bộ lọc: dấu (nntm_search_text_matches_terms(), sửa cùng
 * ngày cho bug "rừng" ra "rụng") và cụm liền mạch — một cái không thay cho
 * cái kia.
 *
 * @param string   $content Nội dung thật để so khớp.
 * @param string   $query   Câu tìm gốc.
 * @param string[] $terms   Các từ đã tách từ câu tìm.
 * @return bool
 */
function nntm_search_content_matches_query( string $content, string $query, array $terms ): bool {
	if ( ! nntm_search_text_matches_terms( $content, $terms ) ) {
		return false;
	}

	if ( nntm_search_is_long_query( $terms ) && ! nntm_search_folded_phrase_present( $content, $query ) ) {
		return false;
	}

	return true;
}

/**
 * Wrap `<mark>` around query matches inside a plain-text passage.
 *
 * Takes plain text and returns HTML in which every character has been through
 * esc_html() and the only injected tag is `<mark>`, so callers only need
 * wp_kses() with a one-tag allowlist.
 *
 * @param string $passage Plain text.
 * @param string $query   Search query.
 * @return string
 */
function nntm_search_highlight( string $passage, string $query ): string {
	$passage = wp_strip_all_tags( $passage );
	$terms   = nntm_search_split_terms( $query );

	if ( empty( $terms ) || '' === $passage ) {
		return esc_html( $passage );
	}

	$folded = nntm_search_fold( $passage );
	$spans  = array();

	foreach ( $terms as $term ) {
		$needle = nntm_search_fold( $term );
		$len    = mb_strlen( $needle );
		$from   = 0;

		while ( false !== ( $at = mb_strpos( $folded, $needle, $from, 'UTF-8' ) ) ) {
			$spans[] = array( $at, $at + $len );
			$from    = $at + $len;
		}
	}

	if ( empty( $spans ) ) {
		return esc_html( $passage );
	}

	// Merge overlapping spans so we never nest <mark> inside <mark>.
	usort( $spans, static fn( array $a, array $b ): int => $a[0] <=> $b[0] );

	$merged = array( array_shift( $spans ) );

	foreach ( $spans as $span ) {
		$last = count( $merged ) - 1;

		if ( $span[0] <= $merged[ $last ][1] ) {
			$merged[ $last ][1] = max( $merged[ $last ][1], $span[1] );
		} else {
			$merged[] = $span;
		}
	}

	$out    = '';
	$cursor = 0;

	foreach ( $merged as $span ) {
		$out   .= esc_html( mb_substr( $passage, $cursor, $span[0] - $cursor ) );
		$out   .= '<mark>' . esc_html( mb_substr( $passage, $span[0], $span[1] - $span[0] ) ) . '</mark>';
		$cursor = $span[1];
	}

	return $out . esc_html( mb_substr( $passage, $cursor ) );
}

/**
 * Cut an excerpt around the first match instead of always from the top.
 *
 * Someone searching wants to see WHERE the term appears, not to re-read the
 * opening sentence.
 *
 * @param string $content Full text.
 * @param string $query   Search query.
 * @param int    $length  Max characters.
 * @return string Plain text, not yet highlighted.
 */
function nntm_search_excerpt( string $content, string $query, int $length = 200 ): string {
	$content = trim( (string) preg_replace( '/\s+/u', ' ', wp_strip_all_tags( $content ) ) );

	if ( '' === $content ) {
		return '';
	}

	$first  = null;
	$folded = nntm_search_fold( $content );

	foreach ( nntm_search_split_terms( $query ) as $term ) {
		$at = mb_strpos( $folded, nntm_search_fold( $term ), 0, 'UTF-8' );

		if ( false !== $at && ( null === $first || $at < $first ) ) {
			$first = $at;
		}
	}

	if ( null === $first || $first < 60 ) {
		return mb_substr( $content, 0, $length ) . ( mb_strlen( $content ) > $length ? '…' : '' );
	}

	$start = max( 0, $first - 60 );

	return '…' . mb_substr( $content, $start, $length )
		. ( mb_strlen( $content ) > $start + $length ? '…' : '' );
}
