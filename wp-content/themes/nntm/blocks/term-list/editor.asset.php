<?php
/**
 * Khai báo phụ thuộc script cho editor.js của block nntm/term-list.
 * Viết tay thay cho file .asset.php tự sinh vì dự án chưa có bước build.
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
		'wp-server-side-render',
	),
	'version'      => (string) filemtime( __DIR__ . '/editor.js' ),
);
