<?php
/**
 * Template cho trang kết quả tìm kiếm.
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>

<main id="nntm-noi-dung-chinh" class="nntm-container nntm-mt-8 nntm-mb-8">

	<header class="nntm-archive__header nntm-mb-7">
		<h1 class="nntm-archive__title">
			<?php
			printf(
				/* translators: %s: từ khóa tìm kiếm. */
				esc_html__( 'Kết quả tìm kiếm cho: %s', 'nntm' ),
				'<span>' . esc_html( get_search_query() ) . '</span>'
			);
			?>
		</h1>
	</header>

	<?php if ( have_posts() ) : ?>

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
