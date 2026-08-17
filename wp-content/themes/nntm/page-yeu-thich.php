<?php
/**
 * Trang Yêu thích.
 * Hoạt động cho cả Page thật slug `yeu-thich` và route ảo /yeu-thich/.
 *
 * @package NNTM
 */

defined( 'ABSPATH' ) || exit;

get_header();

$current_page = function_exists( 'nntm_section_favorites_current_page' ) ? nntm_section_favorites_current_page() : 1;
?>
<main id="nntm-noi-dung-chinh" class="nntm-favorites-page nntm-article-rows">
	<div class="nntm-article-rows__inner nntm-favorites-page__inner">
		<h1 class="nntm-article-rows__heading nntm-favorites-page__heading"><?php esc_html_e( 'Yêu thích', 'nntm' ); ?></h1>

		<?php if ( ! is_user_logged_in() ) : ?>
			<div class="nntm-favorites-page__empty">
				<p><?php esc_html_e( 'Vui lòng đăng nhập để xem các bài viết bạn đã yêu thích.', 'nntm' ); ?></p>
				<button type="button" class="nntm-favorites-page__login" data-nntm-auth-modal="dang-nhap"><?php esc_html_e( 'Đăng nhập', 'nntm' ); ?></button>
			</div>
		<?php elseif ( ! nntm_section_favorites_table_exists() ) : ?>
			<p class="nntm-article-rows__empty"><?php esc_html_e( 'Dữ liệu Yêu thích chưa sẵn sàng.', 'nntm' ); ?></p>
		<?php else : ?>
			<?php $favorite_page = nntm_section_get_favorites_page( get_current_user_id(), $current_page, 5 ); ?>

			<?php if ( ! empty( $favorite_page['posts'] ) ) : ?>
				<div class="nntm-article-rows__list">
					<?php foreach ( $favorite_page['posts'] as $index => $favorite_post ) : ?>
						<?php
						echo nntm_render_section_article_row( // phpcs:ignore WordPress.Security.EscapeOutput -- helper tự escape.
							$favorite_post,
							(int) $index,
							array(
								'show_excerpt'  => true,
								'show_favorite' => true,
								'cta_label'     => __( 'Xem thêm', 'nntm' ),
								'start_side'    => 'left',
							)
						);
						?>
					<?php endforeach; ?>
				</div>

				<?php
				echo nntm_render_section_pagination( // phpcs:ignore WordPress.Security.EscapeOutput -- helper tự escape.
					(int) $favorite_page['current_page'],
					(int) $favorite_page['total_pages'],
					(string) apply_filters( 'nntm_account_favorites_url', home_url( '/yeu-thich/' ) )
				);
				?>
			<?php else : ?>
				<div class="nntm-favorites-page__empty">
					<p><?php esc_html_e( 'Bạn chưa có bài viết yêu thích nào.', 'nntm' ); ?></p>
				</div>
			<?php endif; ?>
		<?php endif; ?>
	</div>
</main>
<?php
get_footer();
