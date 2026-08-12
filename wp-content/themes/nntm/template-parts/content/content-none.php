<?php
/**
 * Hiển thị khi không có bài viết nào (kết quả tìm kiếm rỗng, archive rỗng...).
 */

defined( 'ABSPATH' ) || exit;
?>
<section class="nntm-content-none">

	<?php if ( is_search() ) : ?>
		<h1 class="nntm-content-none__title">
			<?php esc_html_e( 'Không tìm thấy kết quả phù hợp', 'nntm' ); ?>
		</h1>
		<p><?php esc_html_e( 'Thử lại với từ khóa khác.', 'nntm' ); ?></p>
		<?php get_search_form(); ?>
	<?php else : ?>
		<h1 class="nntm-content-none__title">
			<?php esc_html_e( 'Chưa có nội dung', 'nntm' ); ?>
		</h1>
		<p><?php esc_html_e( 'Nội dung sẽ sớm được cập nhật.', 'nntm' ); ?></p>
	<?php endif; ?>

</section>
