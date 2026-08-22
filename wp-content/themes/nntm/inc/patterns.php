<?php

defined( 'ABSPATH' ) || exit;

function nntm_register_pattern_category(): void {
	register_block_pattern_category(
		'nntm',
		array(
			'label' => esc_html__( 'Nẵng Nhân Tịch Mặc', 'nntm' ),
		)
	);
}
add_action( 'init', 'nntm_register_pattern_category' );
