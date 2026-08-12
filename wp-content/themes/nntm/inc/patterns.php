<?php
/**
 * Đăng ký category pattern riêng của theme.
 *
 * Pattern cụ thể (ghép từ SECTION 1..6 theo Figma) sẽ đặt trong
 * patterns/ ở lần dựng sau — file này chỉ đăng ký category.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Đăng ký category pattern "Nẵng Nhân Tịch Mặc".
 */
function nntm_register_pattern_category(): void {
	register_block_pattern_category(
		'nntm',
		array(
			'label' => esc_html__( 'Nẵng Nhân Tịch Mặc', 'nntm' ),
		)
	);
}
add_action( 'init', 'nntm_register_pattern_category' );
