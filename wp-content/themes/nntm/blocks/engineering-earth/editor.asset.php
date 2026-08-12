<?php
/**
 * Khai báo phụ thuộc script cho editor.js của block nntm/engineering-earth.
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
		'wp-server-side-render',
		'wp-api-fetch',
	),
	// Phai lay mtime cua editor.js, KHONG phai cua file nay — lay nham thi
	// sua editor.js xong so phien ban van y nguyen va trinh duyet giu ban cu.
	'version'      => (string) filemtime( __DIR__ . '/editor.js' ),
);
