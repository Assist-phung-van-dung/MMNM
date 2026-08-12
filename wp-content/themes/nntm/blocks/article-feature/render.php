<?php
/**
 * Render động cho block nntm/article-feature — "bài nổi bật toàn văn".
 *
 * Figma R4, khung 01_HOMEPAGE 6376:6322, khối bắt đầu ở y=1602 cao 1097.
 * Bố cục: cột trái là dấu ngoặc kép lớn + tiêu đề + dòng trích nguồn in
 * nghiêng, rồi một khung nền kem chứa mấy đoạn đầu của bài kèm liên kết
 * "Xem tiếp"; cột phải là một ảnh lớn dựng đứng.
 *
 * Không lưu HTML vào nội dung bài: mỗi lần tải trang đọc lại bài thật từ
 * $attributes, nên ban quản trị sửa bài là trang chủ đổi theo ngay.
 *
 * XỬ LÝ THIẾU DỮ LIỆU, không được vỡ bố cục:
 *   không tìm ra bài  -> thông báo tiếng Việt thân thiện, không dựng lưới.
 *   bài không có ảnh  -> bỏ hẳn cột ảnh, cột chữ giãn hết chiều rộng
 *                        (class bổ trợ "--khong-anh"), không để ô xám trống.
 *   bài không có đoạn -> vẫn hiện tiêu đề + nút, khung kem tự thu lại.
 *
 * @package NNTM
 * @var array    $attributes Thuộc tính của block.
 * @var string   $content    Nội dung InnerBlocks (không dùng ở block này).
 * @var WP_Block $block      Instance block hiện tại.
 */

defined( 'ABSPATH' ) || exit;

// Hàm dựng nội dung tách sang inc/ vì render.php bị core `require` (không
// phải require_once) mỗi lần render — xem chú thích đầy đủ trong file đó.
require_once __DIR__ . '/inc/render-article-feature.php';

// ---------- Đọc & làm sạch thuộc tính (danh sách trắng) ----------

$allowed_post_types = array( 'post', 'nntm_article', 'nntm_publication', 'nntm_talk' );
$post_type          = isset( $attributes['postType'] ) ? sanitize_key( (string) $attributes['postType'] ) : 'post';
if ( ! in_array( $post_type, $allowed_post_types, true ) ) {
	$post_type = 'post';
}

$media_position = isset( $attributes['mediaPosition'] ) ? sanitize_key( (string) $attributes['mediaPosition'] ) : 'right';
if ( ! in_array( $media_position, array( 'left', 'right' ), true ) ) {
	$media_position = 'right';
}

$post_id  = isset( $attributes['postId'] ) ? absint( $attributes['postId'] ) : 0;
$taxonomy = isset( $attributes['taxonomy'] ) ? sanitize_key( (string) $attributes['taxonomy'] ) : '';
$term_id  = isset( $attributes['termId'] ) ? absint( $attributes['termId'] ) : 0;

// Chặn trên 30 đoạn: ô nhập là số tự do nên phải có trần, tránh ban quản
// trị gõ nhầm 9999 rồi khối dài vô tận đẩy vỡ cả trang chủ.
$max_paragraphs = isset( $attributes['maxParagraphs'] ) ? absint( $attributes['maxParagraphs'] ) : 8;
$max_paragraphs = min( 30, max( 1, $max_paragraphs ) );

$cta_label = isset( $attributes['ctaLabel'] ) && '' !== trim( (string) $attributes['ctaLabel'] )
	? (string) $attributes['ctaLabel']
	: __( 'Xem tiếp', 'nntm' );

$show_quote_mark = ! isset( $attributes['showQuoteMark'] ) || ! empty( $attributes['showQuoteMark'] );

$attribution_override = isset( $attributes['attribution'] ) ? (string) $attributes['attribution'] : '';

// ---------- Tìm bài ----------

$article = nntm_article_feature_find_post( $post_id, $post_type, $taxonomy, $term_id );

if ( null === $article ) {
	$wrapper_attributes = get_block_wrapper_attributes( array( 'class' => 'nntm-article-feature' ) );
	?>
	<section <?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput -- get_block_wrapper_attributes() da tu esc_attr(). ?>>
		<div class="nntm-article-feature__inner">
			<p class="nntm-article-feature__empty"><?php esc_html_e( 'Chưa chọn được bài viết nào để hiển thị.', 'nntm' ); ?></p>
		</div>
	</section>
	<?php
	return;
}

$permalink = get_permalink( $article );
$title     = get_the_title( $article );

/*
 * Dòng trích nguồn in nghiêng. Ưu tiên ô nhập của block, không có thì lấy
 * phần tóm tắt NHẬP TAY của bài (`post_excerpt`).
 *
 * CỐ Ý không dùng get_the_excerpt(): hàm đó tự cắt mấy chục chữ đầu THÂN
 * BÀI khi bài chưa nhập tóm tắt — dòng trích nguồn sẽ lặp lại y nguyên
 * đoạn đầu hiện ngay bên dưới, nhìn như lỗi. Thà để trống còn hơn hiện
 * hai lần cùng một câu.
 */
$attribution = '' !== trim( $attribution_override )
	? $attribution_override
	: (string) $article->post_excerpt;

$paragraphs = nntm_article_feature_get_paragraphs( $article, $max_paragraphs );

$thumbnail = get_the_post_thumbnail(
	$article,
	'large',
	array(
		'class'   => 'nntm-article-feature__img-el',
		'loading' => 'lazy',
		'alt'     => $title,
	)
);

$wrapper_classes = 'nntm-article-feature nntm-article-feature--media-' . $media_position;
if ( ! $thumbnail ) {
	$wrapper_classes .= ' nntm-article-feature--khong-anh';
}

$wrapper_attributes = get_block_wrapper_attributes( array( 'class' => $wrapper_classes ) );
?>
<section <?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput -- get_block_wrapper_attributes() da tu esc_attr() tung thuoc tinh. ?>>
	<div class="nntm-article-feature__inner">

		<?php if ( $show_quote_mark ) : ?>
			<?php
			/*
			 * Dấu ngoặc kép chỉ là hình trang trí, KHÔNG phải chữ để đọc.
			 * aria-hidden để trình đọc màn hình không đọc ra "dấu ngoặc kép
			 * mở" trước mỗi tiêu đề — vô nghĩa với người khiếm thị.
			 *
			 * Nằm NGOÀI .__text và là con trực tiếp của .__inner: nó chiếm
			 * riêng hàng đầu của lưới, nhờ đó mép trên cột ảnh trùng mép
			 * trên tiêu đề đúng như ảnh thiết kế, thay vì bị đẩy lên ngang
			 * dấu ngoặc kép. Xem style.css phần lưới.
			 */
			?>
			<span class="nntm-article-feature__quote" aria-hidden="true"><span class="nntm-article-feature__quote-glyph">&ldquo;</span></span>
		<?php endif; ?>

		<div class="nntm-article-feature__text">
			<h2 class="nntm-article-feature__title">
				<a href="<?php echo esc_url( $permalink ); ?>"><?php echo esc_html( $title ); ?></a>
			</h2>

			<?php if ( '' !== trim( (string) $attribution ) ) : ?>
				<p class="nntm-article-feature__attribution"><?php echo wp_kses_post( $attribution ); ?></p>
			<?php endif; ?>

			<div class="nntm-article-feature__box">
				<?php if ( '' !== trim( $paragraphs ) ) : ?>
					<div class="nntm-article-feature__body">
						<?php echo $paragraphs; // phpcs:ignore WordPress.Security.EscapeOutput -- da qua wp_kses_post() trong nntm_article_feature_get_paragraphs(). ?>
					</div>
				<?php endif; ?>

				<a class="nntm-article-feature__cta" href="<?php echo esc_url( $permalink ); ?>">
					<?php echo esc_html( $cta_label ); ?>
				</a>
			</div>
		</div>

		<?php if ( $thumbnail ) : ?>
			<div class="nntm-article-feature__media">
				<?php echo wp_kses_post( $thumbnail ); ?>
			</div>
		<?php endif; ?>

	</div>
</section>
