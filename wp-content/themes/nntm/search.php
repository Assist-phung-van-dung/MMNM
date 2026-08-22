<?php

defined( 'ABSPATH' ) || exit;

$nntm_query = get_search_query();

$nntm_groups = nntm_result_groups();
$nntm_group  = isset( $_GET['group'] ) ? sanitize_key( wp_unslash( $_GET['group'] ) ) : 'all';  
$nntm_group  = isset( $nntm_groups[ $nntm_group ] ) ? $nntm_group : 'all';

$nntm_per_page = 10;
$nntm_page     = max( 1, (int) get_query_var( 'paged' ) );

$nntm_results = nntm_get_search_results( $nntm_query, $nntm_group, $nntm_page, $nntm_per_page );

$nntm_total_pages = (int) ceil( $nntm_results['total'] / $nntm_per_page );

get_header();
?>

<main id="nntm-noi-dung-chinh" class="nntm-main--full">

	<section class="nntm-article-rows nntm-search">
		<div class="nntm-article-rows__inner">

			<header class="nntm-search__head">
				<h1 class="nntm-article-rows__heading nntm-search__title">
					<?php esc_html_e( 'Kết quả tìm kiếm', 'nntm' ); ?>
					<?php if ( '' !== $nntm_query ) : ?>
						<span class="nntm-search__term"><?php echo esc_html( $nntm_query ); ?></span>
					<?php endif; ?>
				</h1>

				<?php if ( '' !== $nntm_query ) : ?>
					<p class="nntm-search__summary">
						<?php
						printf(
							 
							esc_html( _n( 'Tìm thấy %s nội dung.', 'Tìm thấy %s nội dung.', $nntm_results['total'], 'nntm' ) ),
							'<strong>' . esc_html( number_format_i18n( $nntm_results['total'] ) ) . '</strong>'
						);
						?>
					</p>
				<?php endif; ?>
			</header>

			<?php if ( '' !== $nntm_query ) : ?>
				<nav class="nntm-search__tabs" aria-label="<?php esc_attr_e( 'Lọc kết quả theo loại nội dung', 'nntm' ); ?>">
					<?php
					foreach ( $nntm_groups as $nntm_key => $nntm_config ) :
						$nntm_active = ( $nntm_key === $nntm_group );
						$nntm_url    = add_query_arg(
							array(
								's'     => $nntm_query,
								'group' => $nntm_key,
							),
							home_url( '/' )
						);
						?>
						<a
							class="nntm-search__tab<?php echo $nntm_active ? ' is-active' : ''; ?>"
							href="<?php echo esc_url( $nntm_url ); ?>"
							<?php echo $nntm_active ? 'aria-current="page"' : ''; ?>
						>
							<?php echo esc_html( $nntm_config['label'] ); ?>
							<span class="nntm-search__count">
								<?php echo esc_html( number_format_i18n( $nntm_results['counts'][ $nntm_key ] ?? 0 ) ); ?>
							</span>
						</a>
					<?php endforeach; ?>
				</nav>
			<?php endif; ?>

			<?php if ( ! empty( $nntm_results['rows'] ) ) : ?>

				<div class="nntm-article-rows__list">
					<?php foreach ( $nntm_results['rows'] as $nntm_index => $nntm_row ) : ?>
						<?php nntm_render_search_row( $nntm_row, 1 === $nntm_index % 2 ); ?>
					<?php endforeach; ?>
				</div>

				<?php if ( $nntm_total_pages > 1 ) : ?>
					<?php
					$nntm_page_url = static function ( int $number ) use ( $nntm_query, $nntm_group ): string {
						return add_query_arg(
							array(
								's'     => $nntm_query,
								'group' => $nntm_group,
								'paged' => $number,
							),
							home_url( '/' )
						);
					};
					?>
					<nav class="nntm-paging nntm-paging--center" aria-label="<?php esc_attr_e( 'Phân trang', 'nntm' ); ?>">
						<?php if ( $nntm_page > 1 ) : ?>
							<a class="nntm-paging__btn nntm-paging__btn--prev" href="<?php echo esc_url( $nntm_page_url( $nntm_page - 1 ) ); ?>">
								<span class="nntm-paging__icon" aria-hidden="true"></span>
								<span class="nntm-paging__label"><?php esc_html_e( 'Trước', 'nntm' ); ?></span>
							</a>
						<?php else : ?>
							<span class="nntm-paging__btn nntm-paging__btn--prev nntm-paging__btn--disabled" aria-disabled="true">
								<span class="nntm-paging__icon" aria-hidden="true"></span>
								<span class="nntm-paging__label"><?php esc_html_e( 'Trước', 'nntm' ); ?></span>
							</span>
						<?php endif; ?>

						<span class="nntm-search__page-of">
							<?php
							printf(
								 
								esc_html__( 'Trang %1$s / %2$s', 'nntm' ),
								esc_html( number_format_i18n( $nntm_page ) ),
								esc_html( number_format_i18n( $nntm_total_pages ) )
							);
							?>
						</span>

						<?php if ( $nntm_page < $nntm_total_pages ) : ?>
							<a class="nntm-paging__btn nntm-paging__btn--next" href="<?php echo esc_url( $nntm_page_url( $nntm_page + 1 ) ); ?>">
								<span class="nntm-paging__label"><?php esc_html_e( 'Sau', 'nntm' ); ?></span>
								<span class="nntm-paging__icon" aria-hidden="true"></span>
							</a>
						<?php else : ?>
							<span class="nntm-paging__btn nntm-paging__btn--next nntm-paging__btn--disabled" aria-disabled="true">
								<span class="nntm-paging__label"><?php esc_html_e( 'Sau', 'nntm' ); ?></span>
								<span class="nntm-paging__icon" aria-hidden="true"></span>
							</span>
						<?php endif; ?>
					</nav>
				<?php endif; ?>

			<?php else : ?>

				<div class="nntm-search__empty">
					<p class="nntm-article-rows__empty">
						<?php if ( '' === $nntm_query ) : ?>
							<?php esc_html_e( 'Nhập từ khoá để bắt đầu tìm.', 'nntm' ); ?>
						<?php else : ?>
							<?php esc_html_e( 'Không tìm thấy nội dung nào khớp từ khoá này.', 'nntm' ); ?>
						<?php endif; ?>
					</p>

					<?php if ( '' !== $nntm_query ) : ?>
						<ul class="nntm-search__hints">
							<li><?php esc_html_e( 'Thử từ khoá ngắn hơn, ví dụ một từ thay vì cả câu.', 'nntm' ); ?></li>
							<li><?php esc_html_e( 'Gõ có dấu hay không dấu đều được.', 'nntm' ); ?></li>
							<li><?php esc_html_e( 'Kiểm tra lại chính tả.', 'nntm' ); ?></li>
						</ul>

						<?php
						 
						?>
					<?php endif; ?>
				</div>

			<?php endif; ?>

		</div>
	</section>

</main>

<?php
get_footer();
