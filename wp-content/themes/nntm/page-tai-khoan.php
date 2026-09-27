<?php
/**
 * Trang "Tài khoản của tôi" (/tai-khoan/) — đường dẫn ảo, không phải Page
 * thật trong DB (xem inc/tai-khoan.php). Khách bị chặn từ trước, tới được
 * đây là đã đăng nhập (nntm_tk_yeu_cau_dang_nhap, template_redirect ưu tiên 5).
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>

<main id="nntm-noi-dung-chinh" class="nntm-tk">
	<?php get_template_part( 'template-parts/tai-khoan/hero' ); ?>

	<div class="nntm-tk__khung nntm-tk__bo-cuc">
		<?php get_template_part( 'template-parts/tai-khoan/dieu-huong' ); ?>

		<div class="nntm-tk__noi-dung">
			<?php
			get_template_part( 'template-parts/tai-khoan/dang-doc' );
			get_template_part( 'template-parts/tai-khoan/thu-vien' );
			get_template_part( 'template-parts/tai-khoan/yeu-thich' );
			get_template_part( 'template-parts/tai-khoan/cong-tu' );
			get_template_part( 'template-parts/tai-khoan/khoa-tu' );
			get_template_part( 'template-parts/tai-khoan/ho-so' );
			get_template_part( 'template-parts/tai-khoan/bao-mat' );
			?>
		</div>
	</div>
</main>

<?php
get_footer();
