<?php
/**
 * Template Name: NNTM — Khai báo chuỗi trì
 *
 * Trang toàn màn hình, KHÔNG có đầu/chân trang site. SUY DOAN về bố cục
 * (chưa có thiết kế Figma/ảnh cho màn này) nhưng vẫn dùng đúng khuôn
 * "template riêng toàn màn hình" như page-tham-gia-chuoi-tri.php/
 * page-dang-nhap.php — xem docs/04-kien-truc.md mục 2.
 *
 * @package NNTM
 */

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
<body <?php body_class( 'nntm-auth-page nntm-cong-tu-page' ); ?><?php echo $nntm_ct_bg_style; /* phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- da esc_url() o tren */ ?>>
<?php wp_body_open(); ?>

<a class="nntm-auth-page__home" href="<?php echo esc_url( home_url( '/' ) ); ?>">
	&larr; <?php esc_html_e( 'Về trang chủ', 'nntm' ); ?>
</a>

<?php get_template_part( 'template-parts/cong-tu/form-khai-bao' ); ?>

<?php wp_footer(); ?>
</body>
</html>
