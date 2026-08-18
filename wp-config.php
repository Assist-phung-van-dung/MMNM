<?php
/**
 * Cau hinh WordPress cho moi truong LOCAL (XAMPP).
 * KHONG commit file nay. Staging/production dung file rieng.
 */

define( 'DB_NAME', 'nntm_dev' );
define( 'DB_USER', 'root' );
define( 'DB_PASSWORD', '' );
define( 'DB_HOST', '127.0.0.1' );
define( 'DB_CHARSET', 'utf8mb4' );
define( 'DB_COLLATE', '' );

$table_prefix = 'wp_';

define('AUTH_KEY',         '!Z{+ABXU*w6gIqAH_&sL;wp3}D5:g?<X10vd&)2PcTXO?{Rz ;=yb_s3py^<%kv=');
define('SECURE_AUTH_KEY',  '>lAO+-:A2]i3GRlLlXv^cC@L%JV78C/GgPAp4x@w +>m@wggagPcFHHOd2:+6iuN');
define('LOGGED_IN_KEY',    '3z^UNU{O]U !Ds<LY#+|-i|+z#^Lp8F7hWD$k]Ou|KkxK~;|XH>-*YJKz3_M>WW)');
define('NONCE_KEY',        'HJY)C-!J*0y->uEW]~-,|8C-;)Fgjt mK+ G%9Gr>+8lu=RPd0f/ScYX+<XDC+:B');
define('AUTH_SALT',        'qR2--Xz:9SvZIBuXs<V,duY1 cVzv {5 uZHC2|pI<p%[?;N,(C<11aAFX&y(`$4');
define('SECURE_AUTH_SALT', 'p3bk06?aNR$-pc6Wt|F9yY>]uej(+S@@vTeh 5=#vo>L3Ri2I]WEyp-K}3}ZN]-2');
define('LOGGED_IN_SALT',   '.+q{i`0wU?.z0oXcfR1R8*Ix-aE81_#<[m5x*:;P Qfcycx>sk/$;ig94eqvC!q%');
define('NONCE_SALT',       'mos)W`-ucd[93HQi[_Et2K00;:..J*D%Q+/N7BU^1vm(:`TG*IT-AnehB;vW2qko');

/*
 * LOCAL: tu suy ra dia chi tu request nen chay duoc ca hai cach ma khong
 * phai sua database — qua Apache (http://localhost/NNTM) hoac qua PHP
 * server (http://localhost:8080). Staging/production dat cung gia tri.
 */
if ( ! empty( $_SERVER['HTTP_HOST'] ) ) {
	$nntm_base = 'http://' . $_SERVER['HTTP_HOST'];
	if ( ! empty( $_SERVER['SCRIPT_NAME'] ) && 0 === strpos( $_SERVER['SCRIPT_NAME'], '/NNTM/' ) ) {
		$nntm_base .= '/NNTM';
	}
	define( 'WP_HOME', $nntm_base );
	define( 'WP_SITEURL', $nntm_base );
	unset( $nntm_base );
}

/* Moi truong phat trien: hien loi ra man hinh va ghi log. */
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', true );
define( 'WP_DEBUG_DISPLAY', true );
define( 'SCRIPT_DEBUG', true );

/* Ngon ngu quan tri: tieng Viet (khao sat cau 37 - khach tu nhap noi dung). */
define( 'WPLANG', 'vi' );

/* Tat trinh sua file trong admin: khach khong duoc sua code tu giao dien. */
define( 'DISALLOW_FILE_EDIT', true );

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}
require_once ABSPATH . 'wp-settings.php';
