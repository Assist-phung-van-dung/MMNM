<?php
/**
 * API rate limiting — against hammering and scraping.
 *
 * Transients rather than a custom table: the data lives for seconds, is written
 * constantly, and does not need to survive anything. With an object cache
 * (Redis) it never touches the database at all.
 *
 * @package NNTM_Search
 */

defined( 'ABSPATH' ) || exit;

/**
 * Caller identity: user id when logged in, hashed IP otherwise.
 *
 * The IP is salted and hashed before storage — raw IPs are personal data and
 * do not belong in the options table.
 *
 * @return string
 */
function nntm_search_rate_key(): string {
	$user_id = get_current_user_id();

	if ( $user_id > 0 ) {
		return 'u' . $user_id;
	}

	$ip = isset( $_SERVER['REMOTE_ADDR'] )
		? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) )
		: '0';

	return 'i' . substr( hash( 'sha256', $ip . wp_salt( 'nonce' ) ), 0, 16 );
}

/**
 * Consume one unit of quota; false when the caller is over the limit.
 *
 * The transient expires relative to the FIRST call of the window, so this is a
 * fixed window rather than a sliding one. Good enough for abuse control and far
 * cheaper than storing a timestamp per request.
 *
 * @param string $bucket  Bucket name, so each endpoint gets its own quota.
 * @param int    $limit   Max calls per window.
 * @param int    $window  Window length in seconds.
 * @return bool
 */
function nntm_search_rate_allow( string $bucket, int $limit, int $window ): bool {
	$key   = 'nntm_rl_' . $bucket . '_' . nntm_search_rate_key();
	$count = (int) get_transient( $key );

	if ( $count >= $limit ) {
		return false;
	}

	set_transient( $key, $count + 1, $window );

	return true;
}
