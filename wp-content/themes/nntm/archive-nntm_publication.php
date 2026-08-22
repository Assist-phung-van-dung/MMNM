<?php

defined( 'ABSPATH' ) || exit;

get_header();
?>

<main id="nntm-noi-dung-chinh" class="nntm-an-pham-kho">

	<div class="nntm-container">

		<header class="nntm-an-pham-kho__dau">
			<h1 class="nntm-an-pham-kho__tieu-de"><?php post_type_archive_title(); ?></h1>
		</header>

		<?php if ( have_posts() ) : ?>

			<ul class="nntm-an-pham-kho__luoi">
				<?php
				while ( have_posts() ) :
					the_post();

					$nntm_dich = get_permalink();

					if ( function_exists( 'nntm_doc_url' ) && nntm_an_pham_can_access( get_post() ) ) {
						$nntm_doc = nntm_doc_url( get_post() );

						if ( '' !== $nntm_doc ) {
							$nntm_dich = $nntm_doc;
						}
					}
					?>
					<li class="nntm-an-pham-kho__o">
						<a class="nntm-an-pham-kho__the" href="<?php echo esc_url( $nntm_dich ); ?>">
							<span class="nntm-an-pham-kho__bia">
								<?php
								if ( has_post_thumbnail() ) {
									the_post_thumbnail(
										'medium_large',
										array(
											'class'   => 'nntm-an-pham-kho__anh',
											'loading' => 'lazy',
											'alt'     => the_title_attribute( array( 'echo' => false ) ),
										)
									);
								} else {
									 
									echo '<span class="nntm-an-pham-kho__bia-trong" aria-hidden="true"></span>';
								}
								?>
							</span>
							<span class="nntm-an-pham-kho__ten"><?php the_title(); ?></span>
						</a>
					</li>
					<?php
				endwhile;
				?>
			</ul>

			<?php
			the_posts_pagination(
				array(
					'class'              => 'nntm-an-pham-kho__phan-trang',
					'mid_size'           => 2,
					'prev_text'          => esc_html__( 'Trước', 'nntm' ),
					'next_text'          => esc_html__( 'Sau', 'nntm' ),
					'screen_reader_text' => esc_html__( 'Chuyển trang kho ấn phẩm', 'nntm' ),
				)
			);
			?>

		<?php else : ?>

			<p class="nntm-an-pham-kho__trong"><?php esc_html_e( 'Chưa có ấn phẩm nào.', 'nntm' ); ?></p>

		<?php endif; ?>

	</div>

</main>

<?php
get_footer();
