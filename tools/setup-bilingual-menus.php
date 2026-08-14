<?php
/**
 * Khôi phục menu sau khi bật Polylang và tạo menu tiếng Anh tương ứng.
 */

$_SERVER['HTTP_HOST']   = $_SERVER['HTTP_HOST'] ?? 'nntm.com';
$_SERVER['REQUEST_URI'] = $_SERVER['REQUEST_URI'] ?? '/';
$_SERVER['SERVER_NAME'] = $_SERVER['SERVER_NAME'] ?? 'nntm.com';
$_SERVER['SERVER_PORT'] = $_SERVER['SERVER_PORT'] ?? '80';

require dirname( __DIR__ ) . '/wp-load.php';

$vi_primary = wp_get_nav_menu_object( 'Menu chính' );
$vi_footer  = wp_get_nav_menu_object( 'Menu chân trang' );
if ( ! $vi_primary || ! $vi_footer ) {
	fwrite( STDERR, "Không tìm thấy menu gốc tiếng Việt." . PHP_EOL );
	exit( 1 );
}

/**
 * Tạo/cập nhật menu tiếng Anh từ menu Việt, giữ cấu trúc và URL.
 */
function nntm_sync_english_menu( string $name, int $source_id, array $labels ): int {
	$menu = wp_get_nav_menu_object( $name );
	$id   = $menu ? (int) $menu->term_id : (int) wp_create_nav_menu( $name );

	foreach ( (array) wp_get_nav_menu_items( $id ) as $old_item ) {
		wp_delete_post( $old_item->ID, true );
	}

	$created = array();
	foreach ( (array) wp_get_nav_menu_items( $source_id ) as $item ) {
		$title  = $labels[ $item->title ] ?? $item->title;
		$parent = $item->menu_item_parent && isset( $created[ $item->menu_item_parent ] ) ? $created[ $item->menu_item_parent ] : 0;
		$new_id = wp_update_nav_menu_item(
			$id,
			0,
			array(
				'menu-item-title'     => $title,
				'menu-item-url'       => $item->url,
				'menu-item-status'    => 'publish',
				'menu-item-type'      => 'custom',
				'menu-item-parent-id' => $parent,
			)
		);
		if ( ! is_wp_error( $new_id ) ) {
			$created[ $item->ID ] = $new_id;
		}
	}

	return $id;
}

$en_primary = nntm_sync_english_menu(
	'Main menu — English',
	(int) $vi_primary->term_id,
	array(
		'Diệu Thượng'    => 'Dieu Thuong',
		'Pháp Toà'       => 'Dharma Talks',
		'Liên Đàn'       => 'Lotus Forum',
		'Hoa Khai'       => 'Awakening',
		'Tin Tức'        => 'News',
		'Vườn Xoài'      => 'Mango Garden',
		'Nhập Pháp Giới' => 'Dharma Realm',
	)
);
$en_footer = nntm_sync_english_menu(
	'Footer menu — English',
	(int) $vi_footer->term_id,
	array(
		'Về chúng tôi' => 'About us',
		'Liên hệ'      => 'Contact',
		'Chính sách'   => 'Policies',
	)
);

set_theme_mod(
	'nav_menu_locations',
	array(
		'primary' => (int) $vi_primary->term_id,
		'footer'  => (int) $vi_footer->term_id,
	)
);

$options = get_option( 'polylang', array() );
$theme   = get_option( 'stylesheet' );
$options['nav_menus'][ $theme ] = array(
	'primary' => array( 'vi' => (int) $vi_primary->term_id, 'en' => $en_primary ),
	'footer'  => array( 'vi' => (int) $vi_footer->term_id, 'en' => $en_footer ),
);
update_option( 'polylang', $options );

echo "Đã gán menu VN và EN cho header/footer." . PHP_EOL;
