<?php
/**
 * Template Name: NNTM — Đăng ký thành viên
 *
 * Trang toàn màn hình, KHÔNG có đầu trang/chân trang của site — xem
 * page-dang-nhap.php để biết lý do (cùng khuôn mẫu).
 *
 * @package NNTM
 */

defined( 'ABSPATH' ) || exit;

if ( is_user_logged_in() ) {
	wp_safe_redirect( home_url( '/' ) );
	exit;
}

$nntm_redirect_to = isset( $_GET['redirect_to'] ) ? esc_url_raw( wp_unslash( $_GET['redirect_to'] ) ) : '';
$nntm_bg_url       = nntm_auth_background_url();
$nntm_bg_style     = $nntm_bg_url ? sprintf( ' style="--nntm-auth-bg: url(%s)"', esc_url( $nntm_bg_url ) ) : '';
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>" />
	<meta name="viewport" content="width=device-width, initial-scale=1" />
	<?php wp_head(); ?>
</head>
<body <?php body_class( 'nntm-auth-page' ); ?><?php echo $nntm_bg_style; /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- đã esc_url() ở trên */ ?>>
<?php wp_body_open(); ?>

<a class="nntm-auth-page__home" href="<?php echo esc_url( home_url( '/' ) ); ?>">
	&larr; <?php esc_html_e( 'Về trang chủ', 'nntm' ); ?>
</a>

<h1 class="nntm-sr-only"><?php esc_html_e( 'Đăng ký thành viên', 'nntm' ); ?></h1>

<?php
get_template_part(
	'template-parts/auth/form-dang-ky',
	null,
	array( 'redirect_to' => $nntm_redirect_to )
);
?>

<?php wp_footer(); ?>
</body>
</html>
