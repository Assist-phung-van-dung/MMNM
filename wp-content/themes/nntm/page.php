<?php
/**
 * Template cho Page — nội dung chủ yếu tới từ block/pattern khách tự sửa
 * (xem docs/04-kien-truc.md mục 2). Template chỉ dựng khung.
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>

<?php
/*
 * Trang ghép từ block section thì <main> để toàn chiều rộng — mỗi block đã
 * tự mang đệm ngoài đúng Figma. Bọc thêm container ở đây sẽ thành đệm chồng
 * đệm và làm nội dung hẹp hơn thiết kế (xem nntm_page_uses_section_blocks()).
 */
$nntm_main_class = nntm_page_uses_section_blocks( get_queried_object() )
	? 'nntm-main--full'
	: 'nntm-container nntm-mt-8 nntm-mb-8';
?>
<main id="nntm-noi-dung-chinh" class="<?php echo esc_attr( $nntm_main_class ); ?>">

	<?php
	while ( have_posts() ) :
		the_post();
		?>
		<article id="post-<?php the_ID(); ?>" <?php post_class( 'nntm-page' ); ?>>
			<?php if ( ! is_front_page() && ! nntm_should_hide_page_title( get_post() ) ) : ?>
				<h1 class="nntm-page__title"><?php the_title(); ?></h1>
			<?php endif; ?>
			<div class="nntm-page__content">
				<?php the_content(); ?>
			</div>
		</article>
		<?php
	endwhile;
	?>

</main>

<?php
get_footer();
