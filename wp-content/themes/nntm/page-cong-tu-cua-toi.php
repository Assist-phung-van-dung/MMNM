<?php
/**
 * Trang "Cộng tu của tôi" (slug cong-tu-cua-toi) — dashboard cá nhân Cộng Tu.
 * Khách bị chuyển tới trang đăng nhập trước khi tới đây (inc/cong-tu.php,
 * nntm_congtu_yeu_cau_dang_nhap). Tiêu đề và đoạn giới thiệu của Page (BQT
 * sửa trong trình soạn thảo) hiện trong dải đầu của template-parts/cong-tu/ca-nhan.php.
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>

<main id="nntm-noi-dung-chinh" class="nntm-ct-cn">
	<?php get_template_part( 'template-parts/cong-tu/ca-nhan' ); ?>
</main>

<?php
get_footer();
