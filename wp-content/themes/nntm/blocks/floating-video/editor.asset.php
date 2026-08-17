<?php
/**
 * Dependencies for nntm/floating-video editor script.
 * Theme has no JS build step, so this asset file is maintained by hand.
 */

defined( 'ABSPATH' ) || exit;

return array(
	'dependencies' => array(
		'wp-blocks',
		'wp-element',
		'wp-block-editor',
		'wp-components',
		'wp-i18n',
		'wp-api-fetch',
	),
	'version'      => (string) filemtime( __DIR__ . '/editor.js' ),
);
