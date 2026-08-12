<?php
/**
 * Template riêng cho trang có slug `r1` — dựng lại trang chủ theo bản
 * thiết kế Figma "DESKTOP - R1", khung 01_HOMEPAGE (4231:852, 1366x3745).
 *
 * WordPress tự chọn file này theo quy tắc page-{slug}.php nên KHÔNG phải
 * khai báo gì thêm và KHÔNG động tới trang chủ hiện tại (đang dựng theo
 * R4). Muốn bỏ bản R1 chỉ cần xoá trang `r1` là xong.
 *
 * Vì sao không dùng get_header()/get_footer(): đầu trang và chân trang
 * hiện có được dựng theo R3/R4, khác hẳn R1 (R1 dùng thanh menu kính mờ
 * nổi đè lên ảnh, chân trang nền rêu bo góc). Trang này tự dựng riêng
 * trong template-parts/r1/ để không phải sửa header.php/footer.php dùng
 * chung của cả site.
 *
 * Thứ tự khối theo toạ độ y trong Figma:
 *   y=20    BANNER        1326x700   -> block nntm/banner trong nội dung trang
 *   y=40    HEADER        1240x80    -> đè lên banner
 *   y=720   SECTION 1     1366x647   -> template-parts/r1/gioi-thieu.php
 *   y=1367  TONG CHI      1326x714   -> template-parts/r1/tong-chi.php
 *   y=2081  LIST ARTICLE  1366x1220  -> template-parts/r1/list-article.php
 *   y=3301  FOOTER        1326x424   -> template-parts/r1/footer.php
 *
 * @package NNTM
 */

defined( 'ABSPATH' ) || exit;
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>" />
	<meta name="viewport" content="width=device-width, initial-scale=1" />
	<?php wp_head(); ?>
</head>
<body <?php body_class( 'nntm-r1' ); ?>>
<?php wp_body_open(); ?>

<a class="nntm-sr-only nntm-skip-link" href="#nntm-noi-dung-chinh">
	<?php esc_html_e( 'Bỏ qua, tới nội dung chính', 'nntm' ); ?>
</a>

<?php
/*
 * Đầu trang nằm ĐÈ LÊN banner (Figma: HEADER y=40 trong khi BANNER y=20)
 * nên hai khối phải chung một khung định vị.
 */
?>
<div class="nntm-r1-hero">
	<?php get_template_part( 'template-parts/r1/header' ); ?>

	<main id="nntm-noi-dung-chinh" class="nntm-r1-main">
		<?php
		/*
		 * Tiêu đề thật của trang cho trình đọc màn hình và SEO. Thiết kế
		 * không có tiêu đề chữ nào ở cấp trang (chữ lớn trên banner là nội
		 * dung của tấm băng chuyền, đổi theo từng tấm, nên không dùng làm
		 * <h1> được) — vì vậy để ẩn về mặt thị giác.
		 */
		?>
		<h1 class="nntm-sr-only"><?php the_title(); ?></h1>

		<?php
		// Nội dung trang: block nntm/banner nằm ở đây, khách tự sửa được.
		while ( have_posts() ) :
			the_post();
			the_content();
		endwhile;
		?>
	</main>
</div>

<?php get_template_part( 'template-parts/r1/gioi-thieu' ); ?>
<?php get_template_part( 'template-parts/r1/tong-chi' ); ?>
<?php get_template_part( 'template-parts/r1/list-article' ); ?>
<?php get_template_part( 'template-parts/r1/footer' ); ?>

<?php wp_footer(); ?>
</body>
</html>
