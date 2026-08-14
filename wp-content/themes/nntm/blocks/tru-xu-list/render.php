<?php
/**
 * Render động cho block nntm/tru-xu-list — SECTION 6 "Trú Xứ".
 *
 * Không lưu HTML vào nội dung bài: mỗi lần tải trang, WP_Query chạy lại
 * từ $attributes hiện tại. Khách đổi số lượng / cách sắp xếp trên trang,
 * không cần lập trình viên.
 *
 * @package NNTM
 * @var array    $attributes Thuộc tính của block.
 * @var string   $content    Nội dung InnerBlocks (không dùng ở block này).
 * @var WP_Block $block      Instance block hiện tại.
 */

defined( 'ABSPATH' ) || exit;

/*
 * Trường `_nntm_abode_location` (địa điểm Trú Xứ) đã chuyển sang đăng ký ở
 * plugin: wp-content/plugins/nntm-core/includes/class-post-meta.php.
 *
 * Lý do (docs/04-kien-truc.md mục 1): dữ liệu thuộc plugin, không thuộc
 * theme. Đăng ký trong render.php chỉ chạy khi block thực sự vẽ ra HTML,
 * nên trang quản trị và REST API không thấy trường — trình soạn thảo không
 * đọc/ghi được. Đăng ký ở plugin trên hook init thì luôn chạy, mọi request.
 *
 * Chỗ này chỉ ĐỌC meta bằng get_post_meta() — không cần đăng ký trước.
 */

// ---------- Đọc & làm sạch thuộc tính ----------

$heading = isset( $attributes['heading'] ) ? (string) $attributes['heading'] : '';
$display_mode = isset( $attributes['displayMode'] ) ? sanitize_key( (string) $attributes['displayMode'] ) : 'cards';
if ( ! in_array( $display_mode, array( 'cards', 'list' ), true ) ) {
	$display_mode = 'cards';
}

$posts_per_page = isset( $attributes['postsPerPage'] ) ? absint( $attributes['postsPerPage'] ) : 4;
$posts_per_page = max( 1, min( 12, $posts_per_page ) ); // gioi han hop ly, khong bao gio truy van khong gioi han.

$allowed_order_by = array( 'newest', 'oldest', 'title' );
$order_by_choice  = isset( $attributes['orderBy'] ) ? sanitize_key( (string) $attributes['orderBy'] ) : 'newest';
if ( ! in_array( $order_by_choice, $allowed_order_by, true ) ) {
	$order_by_choice = 'newest';
}

$query_args = array(
	'post_type'           => 'nntm_abode',
	'post_status'         => 'publish',
	'posts_per_page'      => $posts_per_page,
	'ignore_sticky_posts' => true,
	'no_found_rows'       => true, // khong phan trang o khoi nay, khong can tinh tong so trang.
);

switch ( $order_by_choice ) {
	case 'oldest':
		$query_args['orderby'] = 'date';
		$query_args['order']   = 'ASC';
		break;

	case 'title':
		$query_args['orderby'] = 'title';
		$query_args['order']   = 'ASC';
		break;

	case 'newest':
	default:
		$query_args['orderby'] = 'date';
		$query_args['order']   = 'DESC';
		break;
}

$query = new WP_Query( $query_args );

$wrapper_attributes = get_block_wrapper_attributes( array( 'class' => 'nntm-tru-xu-list nntm-tru-xu-list--' . $display_mode ) );
?>
<section <?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput -- get_block_wrapper_attributes() da tu esc_attr() tung thuoc tinh. ?>>
	<div class="nntm-container">
		<?php if ( '' !== trim( wp_strip_all_tags( $heading ) ) ) : ?>
			<h2 class="nntm-tru-xu-list__heading"><?php echo wp_kses_post( $heading ); ?></h2>
		<?php endif; ?>

		<?php if ( $query->have_posts() && 'list' === $display_mode ) : ?>
			<ul class="nntm-tru-xu-list__plain-list">
				<?php foreach ( $query->posts as $index => $abode ) : ?>
					<?php $location = (string) get_post_meta( $abode->ID, '_nntm_abode_location', true ); ?>
					<li style="--nntm-item-index: <?php echo esc_attr( (string) $index ); ?>">
						<a href="<?php echo esc_url( get_permalink( $abode ) ); ?>">
							<?php echo esc_html( get_the_title( $abode ) ); ?><?php echo '' !== trim( $location ) ? ' (' . esc_html( $location ) . ')' : ''; ?>
						</a>
					</li>
				<?php endforeach; wp_reset_postdata(); ?>
			</ul>
		<?php elseif ( $query->have_posts() ) : ?>
			<div class="nntm-tru-xu-list__grid">
				<?php
				foreach ( $query->posts as $abode ) :
					$permalink = get_permalink( $abode );
					$title     = get_the_title( $abode );

					$location = get_post_meta( $abode->ID, '_nntm_abode_location', true );
					if ( ! is_string( $location ) || '' === trim( $location ) ) {
						// Chưa nhập meta địa điểm — rơi về post_excerpt theo đúng yêu cầu.
						$location = $abode->post_excerpt;
					}

					$thumbnail = get_the_post_thumbnail(
						$abode,
						'medium_large',
						array(
							'class'   => 'nntm-tru-xu-card__img-el',
							'loading' => 'lazy',
							'alt'     => $title,
						)
					);
					?>
					<a href="<?php echo esc_url( $permalink ); ?>" class="nntm-tru-xu-card">
						<span class="nntm-tru-xu-card__img">
							<?php
							if ( $thumbnail ) {
								echo wp_kses_post( $thumbnail );
							} else {
								echo '<span class="nntm-tru-xu-card__img-placeholder" aria-hidden="true"></span>';
							}
							?>
						</span>
						<span class="nntm-tru-xu-card__overlay">
							<span class="nntm-tru-xu-card__name"><?php echo esc_html( $title ); ?></span>
							<?php if ( '' !== trim( (string) $location ) ) : ?>
								<span class="nntm-tru-xu-card__location"><?php echo esc_html( $location ); ?></span>
							<?php endif; ?>
						</span>
					</a>
					<?php
				endforeach;
				wp_reset_postdata();
				?>
			</div>
		<?php else : ?>
			<p class="nntm-tru-xu-list__empty"><?php esc_html_e( 'Chưa có Trú Xứ nào được đăng.', 'nntm' ); ?></p>
		<?php endif; ?>
	</div>
</section>
