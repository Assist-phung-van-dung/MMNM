<?php
/**
 * Render động cho block nntm/article-mosaic — bố cục tạp chí: 1 bài nổi
 * bật ở cột trái + 2 thẻ vừa và 3 thẻ nhỏ ở cột phải, tổng 6 bài. Dùng
 * cho SECTION "Hoằng Pháp" (leadMedia=tall) và SECTION "Tin tức"
 * (leadMedia=short) của trang Figma "05. HOA KHAI".
 *
 * Không lưu HTML vào nội dung bài: mỗi lần tải trang, WP_Query chạy lại
 * từ $attributes hiện tại — bắt chước đúng phong cách của
 * blocks/article-rows/render.php.
 *
 * XỬ LÝ THIẾU BÀI (0–6 bài), không được vỡ bố cục:
 *   0 bài  -> thông báo tiếng Việt thân thiện, không dựng lưới.
 *   1 bài  -> chỉ cột trái (bài nổi bật), cột phải bị bỏ hẳn (không
 *             render div rỗng) và cột trái giãn ra chiếm toàn bộ chiều
 *             rộng nhờ class bổ trợ ".nntm-article-mosaic__list--solo".
 *   2 bài  -> cột trái + 1 thẻ vừa (hàng thẻ vừa chỉ có 1 phần tử flex,
 *             tự giãn full hàng — không có ô trống).
 *   3 bài  -> cột trái + 2 thẻ vừa, hàng thẻ nhỏ bị bỏ hẳn.
 *   4–5 bài -> cột trái + 2 thẻ vừa + 1–2 thẻ nhỏ (hàng thẻ nhỏ tự giãn
 *             đều theo số thẻ thật có, xem style.css).
 *   6 bài  -> đủ bố cục như Figma.
 *
 * @package NNTM
 * @var array    $attributes Thuộc tính của block.
 * @var string   $content    Nội dung InnerBlocks (không dùng ở block này).
 * @var WP_Block $block      Instance block hiện tại.
 */

defined( 'ABSPATH' ) || exit;

// Dùng lại nntm_card_get_primary_term() đã có ở block nntm/card thay vì
// viết lại logic ưu tiên taxonomy (nntm_section -> nntm_topic ->
// nntm_series -> category) — đúng nguyên tắc "sửa một chỗ" ở
// docs/04-kien-truc.md mục 2. require_once tự đảm bảo hàm chỉ khai báo
// một lần dù render.php của nhiều block cùng require file này.
require_once get_template_directory() . '/blocks/card/inc/render-card.php';

// Hàm dựng HTML (thumb/date/thẻ phụ) tách riêng vì render.php của block
// bị WordPress core `require` (không phải `require_once`) mỗi lần
// render — xem chú thích đầy đủ trong file inc/ này. Block này chắc
// chắn xuất hiện hai lần trên cùng trang (Hoằng Pháp + Tin tức) nên
// không được khai hàm trực tiếp trong render.php.
require_once __DIR__ . '/inc/render-article-mosaic.php';

// ---------- Đọc & làm sạch thuộc tính (danh sách trắng) ----------

$allowed_post_types = array( 'post', 'nntm_article', 'nntm_publication', 'nntm_talk', 'nntm_video' );
$post_type          = isset( $attributes['postType'] ) ? sanitize_key( (string) $attributes['postType'] ) : 'post';
if ( ! in_array( $post_type, $allowed_post_types, true ) ) {
	$post_type = 'post';
}

$lead_media = isset( $attributes['leadMedia'] ) ? sanitize_key( (string) $attributes['leadMedia'] ) : 'tall';
if ( ! in_array( $lead_media, array( 'tall', 'short' ), true ) ) {
	$lead_media = 'tall';
}

/*
 * Bố cục cột phải.
 *
 *   mosaic — 2 thẻ vừa (370) + 3 thẻ nhỏ (227), tổng 6 bài. Đây là SECTION 1
 *            của trang chủ và các SECTION của "05. HOA KHAI".
 *   grid   — lưới 3 cột x 2 hàng, 6 thẻ BẰNG NHAU 227x282, tổng 7 bài.
 *            Đây là SECTION 4 "Hoạt động - Sự kiện" (Figma `6376:6425`):
 *            Frame 139 rộng 811, sáu CARD 227x282 đặt ở x=487/754/1021 và
 *            y=158/440 — tức cách ngang 40, cách dọc 0.
 *
 * Hai bố cục khác nhau CẢ SỐ BÀI (6 và 7), nên phải đọc thuộc tính này
 * trước khi dựng truy vấn.
 */
$secondary_layout = isset( $attributes['secondaryLayout'] ) ? sanitize_key( (string) $attributes['secondaryLayout'] ) : 'mosaic';
if ( ! in_array( $secondary_layout, array( 'mosaic', 'grid' ), true ) ) {
	$secondary_layout = 'mosaic';
}

$order_by_choice = isset( $attributes['orderBy'] ) ? sanitize_key( (string) $attributes['orderBy'] ) : 'newest';
if ( ! in_array( $order_by_choice, array( 'newest', 'oldest', 'title', 'manual' ), true ) ) {
	$order_by_choice = 'newest';
}

$show_category = ! isset( $attributes['showCategory'] ) || ! empty( $attributes['showCategory'] );
$show_excerpt  = ! isset( $attributes['showExcerpt'] ) || ! empty( $attributes['showExcerpt'] );
$show_date     = ! isset( $attributes['showDate'] ) || ! empty( $attributes['showDate'] );

$heading = isset( $attributes['heading'] ) ? (string) $attributes['heading'] : '';

$cta_label = isset( $attributes['ctaLabel'] ) && '' !== trim( (string) $attributes['ctaLabel'] )
	? (string) $attributes['ctaLabel']
	: __( 'Xem thêm', 'nntm' );

$taxonomy = isset( $attributes['taxonomy'] ) ? sanitize_key( (string) $attributes['taxonomy'] ) : '';
$term_id  = isset( $attributes['termId'] ) ? absint( $attributes['termId'] ) : 0;

// ---------- Truy vấn ----------
// Bố cục cần đúng 6 bài (1 nổi bật + 2 vừa + 3 nhỏ) — số lượng KHÔNG
// phải tham số của khách, cố định theo yêu cầu thiết kế. Không phân
// trang nên luôn bật no_found_rows để bớt một truy vấn COUNT không
// cần thiết.
$query_args = array(
	'post_type'           => $post_type,
	'post_status'         => 'publish',
	// mosaic can 6 bai (1 noi bat + 2 vua + 3 nho); grid can 7 (1 + luoi 3x2).
	'posts_per_page'      => 'grid' === $secondary_layout ? 7 : 6,
	'ignore_sticky_posts' => true,
	'no_found_rows'       => true,
	/*
	 * KHÔNG tắt update_post_meta_cache / update_post_term_cache ở đây.
	 * Mỗi hàng đọc cả ảnh đại diện (post meta `_thumbnail_id`) lẫn nhãn
	 * chuyên mục (get_the_terms() qua nntm_card_get_primary_term()).
	 * Tắt bộ nhớ đệm sẽ khiến mỗi bài sinh thêm truy vấn riêng — chậm
	 * hơn chứ không nhanh hơn.
	 */
);

/*
 * VI SAO MOI KIEU SAP XEP DEU KEM `ID` LAM TIEU CHI PHU:
 * du lieu nhap hang loat (seed) hay co ngay dang trung nhau tung giay. Khi
 * ORDER BY chi co mot cot ma cot do trung gia tri, MySQL duoc phep tra ve
 * thu tu bat ky — moi lan tai trang anh tu nhay cho. Them `ID` lam tieu chi
 * phu thi thu tu co dinh tuyet doi, vi ID khong bao gio trung.
 * WP_Query nhan orderby dang mang; khi dung mang thi tham so `order` rieng
 * bi bo qua, huong sap da nam trong gia tri cua tung khoa.
 */
switch ( $order_by_choice ) {
	case 'oldest':
		$query_args['orderby'] = array(
			'date' => 'ASC',
			'ID'   => 'ASC',
		);
		break;

	case 'title':
		$query_args['orderby'] = array(
			'title' => 'ASC',
			'ID'    => 'ASC',
		);
		break;

	case 'manual':
		/*
		 * Thu tu do ban quan tri tu chon, nhap danh sach ID bai o o
		 * "Thu tu thu cong" trong bang dieu khien.
		 *
		 * VI SAO CAN: khi cac bai co cung ngay dang (hay gap voi du lieu
		 * nhap hang loat), orderby=date khong quyet dinh duoc thu tu nen
		 * moi lan tai trang WordPress sap mot kieu — anh tu nhay cho. Chot
		 * bang post__in + orderby=post__in thi thu tu co dinh tuyet doi.
		 */
		$manual_ids = array();
		if ( isset( $attributes['manualOrderIds'] ) ) {
			foreach ( preg_split( '/[^0-9]+/', (string) $attributes['manualOrderIds'] ) as $one_id ) {
				$one_id = absint( $one_id );
				if ( $one_id > 0 ) {
					$manual_ids[] = $one_id;
				}
			}
		}
		if ( ! empty( $manual_ids ) ) {
			$query_args['post__in'] = $manual_ids;
			$query_args['orderby']  = 'post__in';
			unset( $query_args['order'] );
		} else {
			// Chua nhap ID nao thi roi ve moi nhat, khong de trang trong.
			$query_args['orderby'] = array(
				'date' => 'DESC',
				'ID'   => 'DESC',
			);
		}
		break;

	case 'newest':
	default:
		$query_args['orderby'] = array(
			'date' => 'DESC',
			'ID'   => 'DESC',
		);
		break;
}

if ( '' !== $taxonomy && taxonomy_exists( $taxonomy ) && $term_id > 0 ) {
	$query_args['tax_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- can loc theo 1 term, khong tranh duoc.
		array(
			'taxonomy' => $taxonomy,
			'field'    => 'term_id',
			'terms'    => array( $term_id ),
		),
	);
}

$query = new WP_Query( $query_args );

$mosaic_posts = $query->posts;
$total_posts  = count( $mosaic_posts );

// bài 1 = nổi bật, bài 2–3 = thẻ vừa, bài 4–6 = thẻ nhỏ. array_slice() tự
// trả mảng rỗng khi không đủ phần tử, không cần kiểm tra thủ công.
$lead_post = $total_posts > 0 ? $mosaic_posts[0] : null;

if ( 'grid' === $secondary_layout ) {
	// Luoi 3x2: sau the bang nhau, khong chia vua/nho.
	$medium_posts = array();
	$small_posts  = array();
	$grid_posts   = array_slice( $mosaic_posts, 1, 6 );
} else {
	$medium_posts = array_slice( $mosaic_posts, 1, 2 );
	$small_posts  = array_slice( $mosaic_posts, 3, 3 );
	$grid_posts   = array();
}

$has_secondary = ! empty( $medium_posts ) || ! empty( $small_posts ) || ! empty( $grid_posts );

// Ba hàm dựng HTML dùng ở dưới (nntm_article_mosaic_render_thumb,
// nntm_article_mosaic_render_date, nntm_article_mosaic_render_secondary_card)
// nằm ở inc/render-article-mosaic.php đã require_once phía trên.

// Class bien the theo leadMedia: CSS can biet cot trai dang dung anh cao hay
// anh thap thi moi canh duoc hai cot cho deu (xem .__secondary trong style.css).
$wrapper_attributes = get_block_wrapper_attributes(
	array(
		'class' => 'nntm-article-mosaic'
			. ' nntm-article-mosaic--lead-' . $lead_media
			. ' nntm-article-mosaic--phai-' . $secondary_layout,
	)
);
?>
<section <?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput -- get_block_wrapper_attributes() da tu esc_attr() tung thuoc tinh. ?>>
	<div class="nntm-article-mosaic__inner">
		<?php if ( '' !== $heading ) : ?>
			<h2 class="nntm-article-mosaic__heading"><?php echo wp_kses_post( $heading ); ?></h2>
		<?php endif; ?>

		<?php if ( null === $lead_post ) : ?>
			<p class="nntm-article-mosaic__empty"><?php esc_html_e( 'Chưa có bài viết nào phù hợp để hiển thị.', 'nntm' ); ?></p>
		<?php else : ?>
			<div class="nntm-article-mosaic__content">
				<div class="nntm-article-mosaic__list<?php echo $has_secondary ? '' : ' nntm-article-mosaic__list--solo'; ?>">
					<?php
					// ---------- Cột trái: bài nổi bật ----------
					$lead_permalink  = get_permalink( $lead_post );
					$lead_title      = get_the_title( $lead_post );
					$lead_img_class  = 'nntm-article-mosaic__lead-img nntm-article-mosaic__lead-img--' . $lead_media;
					?>
					<article class="nntm-article-mosaic__lead">
						<?php echo nntm_article_mosaic_render_thumb( $lead_post, $lead_img_class ); // phpcs:ignore WordPress.Security.EscapeOutput -- ham con da tu esc trong. ?>
						<div class="nntm-article-mosaic__lead-body">
							<?php if ( $show_date ) : ?>
								<?php echo nntm_article_mosaic_render_date( $lead_post ); // phpcs:ignore WordPress.Security.EscapeOutput -- ham con da tu esc trong. ?>
							<?php endif; ?>

							<div class="nntm-article-mosaic__lead-text">
								<?php
								if ( $show_category ) :
									$lead_term = nntm_card_get_primary_term( $lead_post->ID );
									if ( $lead_term ) :
										?>
										<span class="nntm-article-mosaic__cat nntm-article-mosaic__cat--lead"><?php echo esc_html( $lead_term->name ); ?></span>
										<?php
									endif;
								endif;
								?>

								<h3 class="nntm-article-mosaic__lead-title">
									<a href="<?php echo esc_url( $lead_permalink ); ?>"><?php echo esc_html( $lead_title ); ?></a>
								</h3>

								<?php if ( 'grid' === $secondary_layout && $show_excerpt ) : ?>
									<p class="nntm-article-mosaic__lead-excerpt">
										<?php echo esc_html( wp_trim_words( get_the_excerpt( $lead_post ), 28, '…' ) ); ?>
									</p>
								<?php endif; ?>
							</div>

							<a class="nntm-article-mosaic__lead-cta" href="<?php echo esc_url( $lead_permalink ); ?>">
								<?php echo esc_html( $cta_label ); ?>
							</a>
						</div>
					</article>

					<?php if ( $has_secondary ) : ?>
						<div class="nntm-article-mosaic__secondary">
							<?php if ( ! empty( $grid_posts ) ) : ?>
								<div class="nntm-article-mosaic__grid">
									<?php foreach ( $grid_posts as $grid_post ) : ?>
										<?php echo nntm_article_mosaic_render_secondary_card( $grid_post, 'small', $show_category, $show_date, $cta_label ); // phpcs:ignore WordPress.Security.EscapeOutput -- ham con da tu esc trong. ?>
									<?php endforeach; ?>
								</div>
							<?php endif; ?>

							<?php if ( ! empty( $medium_posts ) ) : ?>
								<div class="nntm-article-mosaic__medium-row">
									<?php foreach ( $medium_posts as $medium_post ) : ?>
										<?php echo nntm_article_mosaic_render_secondary_card( $medium_post, 'medium', $show_category, $show_date, $cta_label ); // phpcs:ignore WordPress.Security.EscapeOutput -- ham con da tu esc trong. ?>
									<?php endforeach; ?>
								</div>
							<?php endif; ?>

							<?php if ( ! empty( $small_posts ) ) : ?>
								<div class="nntm-article-mosaic__small-row">
									<?php foreach ( $small_posts as $small_post ) : ?>
										<?php echo nntm_article_mosaic_render_secondary_card( $small_post, 'small', $show_category, $show_date, $cta_label ); // phpcs:ignore WordPress.Security.EscapeOutput -- ham con da tu esc trong. ?>
									<?php endforeach; ?>
								</div>
							<?php endif; ?>
						</div>
					<?php endif; ?>
				</div>
			</div>
		<?php endif; ?>

		<?php
		/*
		 * Nut "Xem Tat ca" — Figma R4 SECTION 1 co nut nay canh giua duoi
		 * cung khoi. Chi hien khi khach da nhap nhan, de cac khoi khong can
		 * nut (nhu SECTION 4 Tin tuc) khong tu moc ra mot nut trong.
		 */
		$nntm_am_xem_nhan = isset( $attributes['viewAllLabel'] ) && '' !== trim( (string) $attributes['viewAllLabel'] )
			? sanitize_text_field( (string) $attributes['viewAllLabel'] )
			: __( 'Xem Tất cả', 'nntm' );
		$nntm_am_xem_url  = isset( $attributes['viewAllUrl'] ) ? esc_url_raw( (string) $attributes['viewAllUrl'] ) : '';

		if ( '' === $nntm_am_xem_url && '' !== $taxonomy && $term_id > 0 ) {
			$nntm_am_term_url = get_term_link( $term_id, $taxonomy );
			if ( ! is_wp_error( $nntm_am_term_url ) ) {
				$nntm_am_xem_url = $nntm_am_term_url;
			}
		}

		if ( '' === $nntm_am_xem_url ) {
			$nntm_am_xem_url = home_url( '/' );
		}

		if ( '' !== trim( $nntm_am_xem_nhan ) ) :
			?>
			<div class="nntm-article-mosaic__viewall-wrap">
				<a class="nntm-article-mosaic__viewall" href="<?php echo esc_url( $nntm_am_xem_url ); ?>">
					<?php echo esc_html( $nntm_am_xem_nhan ); ?>
				</a>
			</div>
			<?php
		endif;
		?>
	</div>
</section>
