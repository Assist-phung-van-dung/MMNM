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
