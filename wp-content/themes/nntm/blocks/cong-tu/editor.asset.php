<?php
/**
 * Khai báo phụ thuộc script cho editor.js của block nntm/cong-tu.
 * Dự án không có bước build (webpack/wp-scripts) nên file .asset.php này
 * được viết tay thay cho file tự sinh, giống đúng khuôn
 * blocks/rank-card/editor.asset.php.
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
