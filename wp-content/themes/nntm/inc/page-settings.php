<?php

defined( 'ABSPATH' ) || exit;

function nntm_register_page_settings_meta(): void {
	register_post_meta(
		'page',
		'_nntm_hide_page_title',
		array(
			'type'              => 'boolean',
			'single'            => true,
			'default'           => false,
			'show_in_rest'      => true,
			'sanitize_callback' => static function ( $value ): bool {
				return (bool) rest_sanitize_boolean( $value );
			},
			'auth_callback'     => static function ( $allowed, $meta_key, $post_id ): bool {
				$post_id = absint( $post_id );

				if ( $post_id < 1 ) {
					return current_user_can( 'edit_pages' );
				}

				return current_user_can( 'edit_post', $post_id );
			},
		)
	);
}
add_action( 'init', 'nntm_register_page_settings_meta' );

function nntm_migrate_page_title_settings(): void {
	if ( get_option( 'nntm_page_title_settings_migrated_1' ) ) {
		return;
	}

	$page_ids = get_posts(
		array(
			'post_type'              => 'page',
			'post_status'            => 'any',
			'posts_per_page'         => -1,
			'fields'                 => 'ids',
			'orderby'                => 'ID',
			'order'                  => 'ASC',
			'no_found_rows'          => true,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
		)
	);

	foreach ( $page_ids as $page_id ) {
		$page_id = absint( $page_id );
		if ( $page_id < 1 || metadata_exists( 'post', $page_id, '_nntm_hide_page_title' ) ) {
			continue;
		}

		$page = get_post( $page_id );
		if ( $page && function_exists( 'nntm_page_has_own_heading' ) && nntm_page_has_own_heading( $page ) ) {
			update_post_meta( $page_id, '_nntm_hide_page_title', true );
		}
	}

	update_option( 'nntm_page_title_settings_migrated_1', 1, false );
}
add_action( 'admin_init', 'nntm_migrate_page_title_settings' );

function nntm_enqueue_page_settings_editor_script(): void {
	$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

	if ( ! $screen || 'page' !== $screen->post_type ) {
		return;
	}

	$relative_path = '/assets/js/page-settings.js';
	$absolute_path = NNTM_THEME_DIR . $relative_path;
	$version       = file_exists( $absolute_path )
		? (string) filemtime( $absolute_path )
		: NNTM_THEME_VERSION;

	wp_enqueue_script(
		'nntm-page-settings',
		NNTM_THEME_URI . $relative_path,
		array(
			'wp-components',
			'wp-data',
			'wp-edit-post',
			'wp-element',
			'wp-i18n',
			'wp-plugins',
		),
		$version,
		true
	);
}
add_action( 'enqueue_block_editor_assets', 'nntm_enqueue_page_settings_editor_script' );

function nntm_should_hide_page_title( ?WP_Post $post = null ): bool {
	$post = $post ?: get_post();

	if ( ! $post || 'page' !== $post->post_type ) {
		return false;
	}

	$hide_title = rest_sanitize_boolean(
		get_post_meta( $post->ID, '_nntm_hide_page_title', true )
	);

	if ( get_option( 'nntm_page_title_settings_migrated_1' ) ) {
		return (bool) $hide_title;
	}

	return (bool) $hide_title
		|| ( function_exists( 'nntm_page_has_own_heading' ) && nntm_page_has_own_heading( $post ) );
}
