<?php
/**
 * Khai bao phu thuoc script cho editor.js cua block nntm/thien-duong.
 * Viet tay thay cho file .asset.php tu sinh vi du an chua co buoc build.
 *
 * Ghi chu: truong `_nntm_track_audio` da chuyen sang dang ky o plugin
 * nntm-core (includes/class-post-meta.php) — xem docs/04-kien-truc.md muc 1.
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
