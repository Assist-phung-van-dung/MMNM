<?php
/**
 * Template cho archive (chuyên mục, taxonomy, ngày tháng, CPT...).
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>

<main id="nntm-noi-dung-chinh" class="nntm-container nntm-mt-8 nntm-mb-8">

	<?php if ( have_posts() ) : ?>

		<header class="nntm-archive__header nntm-mb-7">
			<h1 class="nntm-archive__title"><?php the_archive_title(); ?></h1>
			<?php the_archive_description( '<div class="nntm-archive__description">', '</div>' ); ?>
		</header>

		<div class="nntm-grid nntm-grid--2">
			<?php
			while ( have_posts() ) :
				the_post();
				get_template_part( 'template-parts/content/content', 'article' );
			endwhile;
			?>
		</div>

		<?php the_posts_pagination(); ?>

	<?php else : ?>

		<?php get_template_part( 'template-parts/content/content', 'none' ); ?>

	<?php endif; ?>

</main>

<?php
get_footer();
