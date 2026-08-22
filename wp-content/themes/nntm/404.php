<?php

defined( 'ABSPATH' ) || exit;

get_header();
?>

<main id="nntm-noi-dung-chinh" class="nntm-container nntm-mt-8 nntm-mb-8">

	<section class="nntm-error-404">
		<h1 class="nntm-error-404__title">
			<?php esc_html_e( 'Không tìm thấy trang', 'nntm' ); ?>
		</h1>
		<p>
			<?php esc_html_e( 'Trang bạn tìm không tồn tại hoặc đã được di chuyển.', 'nntm' ); ?>
		</p>

		<?php get_search_form(); ?>
	</section>

</main>

<?php
get_footer();
