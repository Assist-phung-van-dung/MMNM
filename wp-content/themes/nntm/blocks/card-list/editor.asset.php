<?php

defined( 'ABSPATH' ) || exit;

return array(
	'dependencies' => array(
		'wp-blocks',
		'wp-element',
		'wp-block-editor',
		'wp-components',
		'wp-i18n',
		'wp-server-side-render',
		'wp-api-fetch',
	),
	'version'      => (string) filemtime( __DIR__ . '/editor.js' ),
);
