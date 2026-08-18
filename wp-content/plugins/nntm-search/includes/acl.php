<?php
/**
 * Access control for search results.
 *
 * ⚠️ THE MOST IMPORTANT FILE IN THIS PLUGIN. Read all of it before editing.
 *
 * The site's gate lives in `pre_get_posts` (theme, inc/hanh-gia.php, layer 2).
 * That covers search only while search runs through WP_Query. The moment an
 * external engine takes over, queries stop going through WP_Query and the gate
 * stops applying — the whole Hành Giả section leaks on the first search.
 *
 * Measured 14/08/2026 (docs/07-ban-giao.md section 5): before the gate existed,
 * one guest search returned 10 Đại Sĩ articles.
 *
 * So permission has to live IN THE INDEX, and the rules may only be written
 * once — this file calls the theme's own functions rather than restating them.
 *
 * The theme's own functions (nntm_term_khu_han_che,
 * nntm_duoc_xem_khu_han_che, nntm_trang_can_dang_nhap) are called
 * directly. If you ever find yourself copying one of those rules into this
 * file, stop — two copies of a permission rule drift, and drift here means a
 * leak.
 *
 * @package NNTM_Search
 */

defined( 'ABSPATH' ) || exit;

/**
 * Access level of a single post, computed at index time.
 *
 * @param WP_Post $post Post to classify.
 * @return string 'public' | 'member'
 */
function nntm_search_post_acl( WP_Post $post ): string {
	$level = 'public';

	// Articles inside the Hành Giả section are members-only. The slug list comes
	// from the theme rather than being hardcoded: the theme can change it through
	// the `nntm_term_khu_han_che` filter, and if the index disagrees, it leaks.
	if ( 'nntm_article' === $post->post_type ) {
		if ( function_exists( 'nntm_term_khu_han_che' ) ) {
			$restricted = nntm_term_khu_han_che();
			$terms      = get_the_terms( $post, 'nntm_section' );

			if ( is_array( $terms ) ) {
				foreach ( $terms as $term ) {
					if ( $term instanceof WP_Term && in_array( $term->slug, $restricted, true ) ) {
						$level = 'member';
						break;
					}
				}
			}
		} else {
			$level = 'member';

			static $da_canh_bao = false;

			if ( ! $da_canh_bao ) {
				$da_canh_bao = true;
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- one-time warning, no post/user data.
				error_log( '[nntm-search] nntm_term_khu_han_che() khong ton tai — moi nntm_article dang duoc lap chi muc la member, kiem tra theme dang kich hoat.' );
			}
		}
	}

	/*
	 * Pages that require login (dai-si-hanh-gia, kim-cuong-hanh-gia).
	 *
	 * MEASURED 15/08/2026: the theme blocks these at layer 1 (template_redirect
	 * sends a direct visit to 302), but layer 2 (pre_get_posts) only excludes
	 * `nntm_article` by taxonomy and never touches pages. Nobody noticed because
	 * search had never covered the `page` post type. The moment it does, both
	 * pages show up in guest results — exactly the class of leak section 5 of the
	 * handover describes: "restricted content leaks mostly in places nobody looks".
	 */
	if ( 'public' === $level && 'page' === $post->post_type && function_exists( 'nntm_trang_can_dang_nhap' ) ) {
		if ( in_array( $post->post_name, nntm_trang_can_dang_nhap(), true ) ) {
			$level = 'member';
		}
	}

	/*
	 * Publications: the gate is deliberately OPEN because the client has not
	 * decided yet (docs/03-chot section A: "must the PDF library require login,
	 * or may visitors read and only watermark when logged in?"). Not ours to
	 * decide. When they do decide, the theme's filter changes the answer and the
	 * only thing needed here is a reindex — no code change.
	 */
	if ( 'public' === $level && 'nntm_publication' === $post->post_type ) {
		/*
		 * Ask about an ANONYMOUS visitor (user id 0), not about whoever is running
		 * the job. Indexing runs from cron/CLI, so asking about the current session
		 * would return the cron user's permissions — completely wrong.
		 */
		$visitor_can_read = (bool) apply_filters( 'nntm_an_pham_can_access', true, $post, 0 );

		if ( ! $visitor_can_read ) {
			$level = 'member';
		}
	}

	return (string) apply_filters( 'nntm_search_post_acl', $level, $post );
}

/**
 * Access levels the current viewer is allowed to see.
 *
 * ⚠️ Reads the server-side session only. NEVER accept this from the request —
 * doing so lets a client grant itself permission.
 *
 * @return string[]
 */
function nntm_search_viewer_acl(): array {
	if ( function_exists( 'nntm_duoc_xem_khu_han_che' ) ) {
		/*
		 * The theme's function opens the gate when PHP_SAPI === 'cli' (the seed
		 * script trap from 14/08). Search always runs over the web so that branch
		 * should never fire here — but pin it down anyway, because WP-CLI can also
		 * invoke REST and then "open for CLI" would become a hole.
		 */
		$allowed = ( 'cli' !== PHP_SAPI ) && nntm_duoc_xem_khu_han_che();
	} else {
		// Theme has no such function (different theme) → fail closed.
		$allowed = is_user_logged_in();
	}

	return $allowed ? array( 'public', 'member' ) : array( 'public' );
}

/**
 * May the current viewer see this post?
 *
 * Used as a SECOND pass after the engine returns. The index can be seconds out
 * of date between an editor moving a post into the restricted section and the
 * reindex job running; in that window the first pass is open. This is the
 * suspenders to go with the belt.
 *
 * @param int $post_id Post ID.
 * @return bool
 */
function nntm_search_can_view( int $post_id ): bool {
	$post = get_post( $post_id );

	if ( ! $post instanceof WP_Post || 'publish' !== $post->post_status ) {
		return false;
	}

	if ( 'member' !== nntm_search_post_acl( $post ) ) {
		return true;
	}

	return in_array( 'member', nntm_search_viewer_acl(), true );
}
