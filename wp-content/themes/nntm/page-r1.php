<?php

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
 
?>
<div class="nntm-r1-hero">
	<?php get_template_part( 'template-parts/r1/header' ); ?>

	<main id="nntm-noi-dung-chinh" class="nntm-r1-main">
		<?php
		 
		?>
		<h1 class="nntm-sr-only"><?php the_title(); ?></h1>

		<?php
		 
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
