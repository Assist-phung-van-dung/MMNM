<?php
/**
 * Template cho bài viết đơn (post và các CPT chưa có template riêng).
 * Các CPT có nghiệp vụ nặng (PDF, Pháp Thoại, Thiền Đường...) sẽ có
 * single-{cpt}.php riêng ở phần plugin/theme sau — file này là khung
 * chung tối thiểu.
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>

<main id="nntm-noi-dung-chinh" class="nntm-container nntm-mt-8 nntm-mb-8">

	<?php
	while ( have_posts() ) :
		the_post();
		get_template_part( 'template-parts/content/content', 'article' );
	endwhile;
	?>

</main>

<?php
get_footer();
