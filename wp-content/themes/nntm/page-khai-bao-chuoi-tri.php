<?php

defined( 'ABSPATH' ) || exit;

$nntm_ct_bg_url   = function_exists( 'nntm_auth_background_url' ) ? nntm_auth_background_url() : '';
$nntm_ct_bg_style = $nntm_ct_bg_url ? sprintf( ' style="--nntm-auth-bg: url(%s)"', esc_url( $nntm_ct_bg_url ) ) : '';
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>" />
	<meta name="viewport" content="width=device-width, initial-scale=1" />
	<?php wp_head(); ?>
</head>
<body <?php body_class( 'nntm-auth-page nntm-cong-tu-page' ); ?><?php echo $nntm_ct_bg_style;   ?>>
<?php wp_body_open(); ?>

<a class="nntm-auth-page__home" href="<?php echo esc_url( home_url( '/' ) ); ?>">
	&larr; <?php esc_html_e( 'Về trang chủ', 'nntm' ); ?>
</a>

<?php get_template_part( 'template-parts/cong-tu/form-khai-bao' ); ?>

<?php wp_footer(); ?>
</body>
</html>
