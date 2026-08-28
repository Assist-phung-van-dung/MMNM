<?php

defined( 'ABSPATH' ) || exit;

if ( is_user_logged_in() ) {
	wp_safe_redirect( home_url( '/' ) );
	exit;
}

$nntm_redirect_to = nntm_auth_redirect_from_request();
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
<body <?php body_class( 'nntm-auth-page' ); ?><?php echo $nntm_bg_style;   ?>>
<?php wp_body_open(); ?>

<a class="nntm-auth-page__home" href="<?php echo esc_url( home_url( '/' ) ); ?>">
	&larr; <?php esc_html_e( 'Về trang chủ', 'nntm' ); ?>
</a>

<h1 class="nntm-sr-only"><?php esc_html_e( 'Đăng nhập', 'nntm' ); ?></h1>

<?php
get_template_part(
	'template-parts/auth/form-dang-nhap',
	null,
	array( 'redirect_to' => $nntm_redirect_to )
);
?>

<?php wp_footer(); ?>
</body>
</html>
