<?php
/**
 * Trang "Cộng tu của tôi" (slug cong-tu-cua-toi) — dashboard cá nhân Cộng Tu.
 * Khách bị chuyển tới trang đăng nhập trước khi tới đây (inc/cong-tu.php,
 * nntm_congtu_yeu_cau_dang_nhap).
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>

<main id="nntm-noi-dung-chinh" class="nntm-ct-cn">
	<div class="nntm-ct-cn__khung">
		<?php
		while ( have_posts() ) :
			the_post();
			?>
			<h1 class="nntm-ct-cn__tieu-de"><?php the_title(); ?></h1>
			<?php if ( '' !== trim( (string) get_the_content() ) ) : ?>
				<div class="nntm-ct-cn__loi-mo"><?php the_content(); ?></div>
			<?php endif; ?>
			<?php
		endwhile;

		get_template_part( 'template-parts/cong-tu/ca-nhan' );
		?>
	</div>
</main>

<?php
get_footer();
