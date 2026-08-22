<?php

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
<body <?php body_class( 'nntm-auth-page' ); ?><?php echo $nntm_bg_style;   ?>>
<?php wp_body_open(); ?>

<a class="nntm-auth-page__home" href="<?php echo esc_url( home_url( '/' ) ); ?>">
	&larr; <?php esc_html_e( 'Về trang chủ', 'nntm' ); ?>
</a>

<h1 class="nntm-sr-only"><?php esc_html_e( 'Quên mật khẩu', 'nntm' ); ?></h1>

<?php
get_template_part(
	'template-parts/auth/form-quen-mat-khau',
	null,
	array( 'redirect_to' => $nntm_redirect_to )
);
?>

<?php wp_footer(); ?>
</body>
</html>
