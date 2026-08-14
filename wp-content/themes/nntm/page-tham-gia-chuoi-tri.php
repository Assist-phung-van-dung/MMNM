<?php
/**
 * Template Name: NNTM — Tham gia chuỗi trì
 *
 * Trang toàn màn hình, KHÔNG có đầu/chân trang site — đúng
 * docs/04-kien-truc.md mục 2 (dashboard Cộng Tu là PHP template, không phải
 * block). Cổng quyền (chưa đăng nhập → /dang-nhap/) đã chặn ở
 * inc/cong-tu.php (template_redirect ưu tiên 5), file này chỉ còn việc dựng
 * giao diện.
 *
 * Nền núi + thẻ kính mờ TÁI SỬ DỤNG nguyên lớp CSS auth.css
 * (.nntm-auth-page/.nntm-auth-card…) — đúng yêu cầu "dùng lại lớp CSS của
 * auth.css thay vì chép lại". WordPress tự chọn file này cho Page slug
 * `tham-gia-chuoi-tri` theo quy tắc page-{slug}.php.
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

<?php get_template_part( 'template-parts/cong-tu/form-tham-gia' ); ?>

<?php wp_footer(); ?>
</body>
</html>
