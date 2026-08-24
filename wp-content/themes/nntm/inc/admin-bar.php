<?php

defined( 'ABSPATH' ) || exit;

function nntm_la_administrator(): bool {
	if ( ! is_user_logged_in() ) {
		return false;
	}

	if ( is_multisite() && function_exists( 'is_super_admin' ) && is_super_admin() ) {
		return true;
	}

	$user = wp_get_current_user();

	return $user instanceof WP_User && in_array( 'administrator', (array) $user->roles, true );
}

function nntm_admin_bar_duoc_thay( $hien ) {
	if ( is_admin() ) {
		return $hien;
	}

	return nntm_la_administrator();
}
add_filter( 'show_admin_bar', 'nntm_admin_bar_duoc_thay', 20 );
