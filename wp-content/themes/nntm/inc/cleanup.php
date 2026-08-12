<?php
/**
 * Dọn dẹp phần thừa WordPress mặc định — không phá chức năng lõi.
 */

defined( 'ABSPATH' ) || exit;

// Gỡ script/style emoji — site không dùng, giảm request thừa.
remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
remove_action( 'wp_print_styles', 'print_emoji_styles' );
remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
remove_action( 'admin_print_styles', 'print_emoji_styles' );

// Gỡ meta "generator" — không lộ phiên bản WordPress đang chạy.
remove_action( 'wp_head', 'wp_generator' );
add_filter( 'the_generator', '__return_empty_string' );

// Tắt XML-RPC — giảm bề mặt tấn công, site không dùng ứng dụng ngoài cần nó.
add_filter( 'xmlrpc_enabled', '__return_false' );

// Gỡ link RSD và wlwmanifest trong <head> — phục vụ công cụ soạn thảo
// ngoài (Windows Live Writer...) mà site không dùng.
remove_action( 'wp_head', 'rsd_link' );
remove_action( 'wp_head', 'wlwmanifest_link' );
