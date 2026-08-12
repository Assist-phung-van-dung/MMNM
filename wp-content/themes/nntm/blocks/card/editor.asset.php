<?php
/**
 * Khai báo phụ thuộc script cho editor.js của block nntm/card.
 * Dự án không có bước build (webpack/wp-scripts) nên file .asset.php
 * này được viết tay thay cho file tự sinh, để WordPress nạp đúng
 * thứ tự các gói @wordpress/* mà editor.js cần trước khi chạy.
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
	),
	'version'      => (string) filemtime( __DIR__ . '/editor.js' ),
);
