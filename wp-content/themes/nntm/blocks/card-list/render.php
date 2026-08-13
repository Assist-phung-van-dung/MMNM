<?php
/**
 * Render động cho block nntm/card-list — khối xương sống ghép nên
 * 6 trang phân mục (xem docs/04-kien-truc.md mục 2).
 *
 * Không lưu HTML vào nội dung bài: mỗi lần tải trang, WP_Query chạy
 * lại từ $attributes hiện tại. Khách đổi nguồn bài / số cột / biến thể
 * trên trang, không cần lập trình viên.
 *
 * HƯỚNG DẪN ADMIN DÁN LINK YOUTUBE (G1 — băng "Gót Son", chưa ai dán link
 * nào tính đến 12/08/2026, xem docs/spec-trang-chu.md mục G1):
 *   1. Mở block này trong trình soạn thảo (Gutenberg) → panel bên phải
 *      "Lấy nội dung từ đâu" → ô "Nguồn video / bài viết" → chọn
 *      "Dán link YouTube".
 *   2. Một ô nhập nhiều dòng ("Danh sách link YouTube") sẽ hiện ra — dán
 *      MỖI VIDEO MỘT DÒNG, chấp nhận cả 3 dạng: link đầy đủ
 *      (youtube.com/watch?v=…), link rút gọn (youtu.be/…), hoặc chỉ ID
 *      video (11 ký tự sau "v=" hoặc sau "youtu.be/").
 *   3. TIÊU ĐỀ hiện dưới mỗi thẻ (tối đa 2 dòng, thừa thì "…") — gõ kèm
 *      trên CÙNG một dòng, ngăn với link bằng dấu "|", ví dụ:
 *        https://www.youtube.com/watch?v=abc123 | TẬP 18 - CHÂN SƯ HIỆN THÁNH TƯỚNG
 *      Không gõ tiêu đề thì tự lấy qua oEmbed công khai của YouTube (không
 *      cần API key), có lưu đệm 1 tuần — xem
 *      nntm_card_list_get_video_title() trong inc/render-card-list-youtube.php.
 *   4. Không cần tải ảnh lên — ảnh nền thẻ tự lấy từ img.youtube.com.
 *   5. KHÔNG dùng YouTube Data API (anh Úy chốt 12/08/2026) — mọi thứ xử
 *      lý bằng cách tách ID từ chuỗi dán vào, xem
 *      inc/render-card-list-youtube.php.
 *
 * @package NNTM
 * @var array    $attributes Thuộc tính của block.
 * @var string   $content    Nội dung InnerBlocks (không dùng ở block này).
 * @var WP_Block $block      Instance block hiện tại.
 */

defined( 'ABSPATH' ) || exit;

require_once get_template_directory() . '/blocks/card/inc/render-card.php';
require_once __DIR__ . '/inc/render-card-list-youtube.php';

// ---------- Đọc & làm sạch thuộc tính ----------

$allowed_post_types = array( 'nntm_article', 'nntm_publication', 'nntm_talk', 'nntm_retreat', 'nntm_abode', 'nntm_video', 'nntm_zen_track', 'post' );
$post_type          = isset( $attributes['postType'] ) ? sanitize_key( (string) $attributes['postType'] ) : 'nntm_article';
if ( ! in_array( $post_type, $allowed_post_types, true ) ) {
	$post_type = 'nntm_article';
}

$variant = isset( $attributes['variant'] ) ? sanitize_key( (string) $attributes['variant'] ) : 'article';
if ( ! in_array( $variant, nntm_card_allowed_variants(), true ) ) {
	$variant = 'article';
}

$columns = isset( $attributes['columns'] ) ? absint( $attributes['columns'] ) : 3;
if ( ! in_array( $columns, array( 2, 3, 4 ), true ) ) {
	$columns = 3;
}

// Giới hạn hợp lý — không bao giờ truy vấn không giới hạn.
$posts_per_page = isset( $attributes['postsPerPage'] ) ? absint( $attributes['postsPerPage'] ) : 6;
$posts_per_page = max( 1, min( 24, $posts_per_page ) );

$order_by_choice = isset( $attributes['orderBy'] ) ? sanitize_key( (string) $attributes['orderBy'] ) : 'newest';
$show_paging      = ! empty( $attributes['showPaging'] );
$heading          = isset( $attributes['heading'] ) ? (string) $attributes['heading'] : '';
$subheading       = isset( $attributes['subheading'] ) ? (string) $attributes['subheading'] : '';
$has_subheading   = '' !== trim( wp_strip_all_tags( $subheading ) );

// layout: "grid" (mac dinh, hanh vi cu — moi khoi card-list dang co tren site
// khong luu attribute nay nen luon nhan default "grid" va giu nguyen hinh dang cu)
// hoac "carousel" (bang the cuon ngang, but lui/tien — xem blocks/term-list/style.css
// cho ky thuat cuon dung chung).
$layout = isset( $attributes['layout'] ) ? sanitize_key( (string) $attributes['layout'] ) : 'grid';
if ( ! in_array( $layout, array( 'grid', 'carousel' ), true ) ) {
	$layout = 'grid';
}
$is_carousel = ( 'carousel' === $layout );

// Tu chay bang cuon — chi co tac dung khi $is_carousel. Gio giua moi lan
// chuyen gioi han 2-20s (khop min/max o editor.js RangeControl va o
// block.json) de tranh gia tri bat thuong tu API/REST truc tiep.
$autoplay          = isset( $attributes['autoplay'] ) ? (bool) $attributes['autoplay'] : true;
$autoplay_interval = isset( $attributes['autoplayInterval'] ) ? absint( $attributes['autoplayInterval'] ) : 6;
$autoplay_interval = max( 2, min( 20, $autoplay_interval ? $autoplay_interval : 6 ) );

$taxonomy = isset( $attributes['taxonomy'] ) ? sanitize_key( (string) $attributes['taxonomy'] ) : '';
$term_id  = isset( $attributes['termId'] ) ? absint( $attributes['termId'] ) : 0;

/*
 * Nguồn video cho biến thể "băng Netflix" (G1 — dải "Gót Son"). Anh Úy
 * chốt 12/08/2026: admin dán link/ID YouTube trực tiếp vào block, KHÔNG
 * dùng YouTube Data API. Khi videoSource=youtube, khối này bỏ hẳn WP_Query
 * và hiện băng cuộn tự chạy dựng từ danh sách ID YouTube — xem
 * inc/render-card-list-youtube.php.
 */
$video_source = isset( $attributes['videoSource'] ) ? sanitize_key( (string) $attributes['videoSource'] ) : 'posts';
if ( ! in_array( $video_source, array( 'posts', 'youtube' ), true ) ) {
	$video_source = 'posts';
}
$is_youtube_source  = ( 'youtube' === $video_source );
$youtube_items_raw  = isset( $attributes['youtubeItems'] ) ? (string) $attributes['youtubeItems'] : '';
$youtube_video_items = $is_youtube_source ? nntm_card_list_parse_youtube_items( $youtube_items_raw ) : array();

// Trang hiện tại cho phân trang — dùng query var "paged" chuẩn của WordPress.
// Giới hạn đã biết: nếu một trang có NHIỀU hơn một card-list đang bật phân
// trang cùng lúc, cả hai sẽ dùng chung tham số ?paged= và lệch nhau. Đây là
// đánh đổi chấp nhận được cho Phase 1 vì mỗi trang phân mục theo Figma chỉ có
// một section chính cần phân trang; các section khác dùng showPaging=false.
$paged = get_query_var( 'paged' );
if ( ! $paged ) {
	$paged = isset( $_GET['paged'] ) ? absint( wp_unslash( $_GET['paged'] ) ) : 1; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- chi doc de phan trang, khong ghi du lieu.
}
$paged = max( 1, absint( $paged ) );

/*
 * Chế độ carousel KHÔNG phân trang theo trang — nút lùi/tiến ở chế độ này là
 * để CUỘN băng thẻ (xử lý bằng CSS/JS phía dưới), không phải để đổi trang
 * WP_Query. Vì vậy ở carousel luôn lấy đúng $posts_per_page bài từ trang 1,
 * bỏ qua "paged" và "showPaging" — không tính tổng số trang vì không cần.
 */
$query_args = array(
	'post_type'              => $post_type,
	'post_status'            => 'publish',
	'posts_per_page'         => $posts_per_page,
	'paged'                  => $is_carousel ? 1 : $paged,
	'ignore_sticky_posts'    => true,
	'no_found_rows'       => $is_carousel ? true : ! $show_paging, // chi tinh tong so trang khi thuc su can phan trang, do bot truy van.
	/*
	 * KHÔNG tắt update_post_meta_cache / update_post_term_cache ở đây.
	 * Thẻ đọc cả hai: ảnh đại diện lấy ID từ post meta `_thumbnail_id`,
	 * nhãn phân mục lấy qua get_the_terms(). Tắt bộ nhớ đệm sẽ khiến mỗi
	 * bài sinh thêm truy vấn riêng — chậm hơn chứ không nhanh hơn.
	 */
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

	case 'manual':
		$manual_ids_raw = isset( $attributes['manualOrderIds'] ) ? (string) $attributes['manualOrderIds'] : '';
		$manual_ids     = array_values( array_filter( array_map( 'absint', explode( ',', $manual_ids_raw ) ) ) );

		if ( ! empty( $manual_ids ) ) {
			// Giới hạn cùng mức postsPerPage để không truy vấn post__in không giới hạn.
			$query_args['post__in'] = array_slice( $manual_ids, 0, $posts_per_page );
			$query_args['orderby']  = 'post__in';
		} else {
			$query_args['orderby'] = 'date';
			$query_args['order']   = 'DESC';
		}
		break;

	case 'newest':
	default:
		$query_args['orderby'] = 'date';
		$query_args['order']   = 'DESC';
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

// Nguồn YouTube không cần WP_Query — bỏ hẳn để không truy vấn CSDL vô ích.
$query = $is_youtube_source ? null : new WP_Query( $query_args );

/*
 * Màu nền khối. Chỉ nhận bốn giá trị đã khai trong block.json — khách
 * chọn trong bảng điều khiển, KHÔNG được nhập mã màu tự do, để không phá
 * được bảng màu thương hiệu (docs/04-kien-truc.md mục 2).
 *
 * Sinh ra từ khối GITA CENTER trên trang chủ R4 (Figma 6376:6322): khối
 * đó cần nền cam #FB5102 mà băng cuộn trước đây chưa có tuỳ chọn nào.
 */
$background = isset( $attributes['background'] ) ? sanitize_key( (string) $attributes['background'] ) : 'none';
if ( ! in_array( $background, array( 'none', 'kem', 'cam', 'toi' ), true ) ) {
	$background = 'none';
}

/*
 * Tiêu đề đặt NGOÀI dải nền, và đoạn chữ nghiêng đặt DƯỚI dải nền.
 *
 * tu Figma SECTION 3 `6376:6399` (băng video "Gót Son"): tiêu đề vẽ so le
 * làm hai phần — "Gót Son" ở y=-68 tức NẰM TRÊN dải đen, chữ màu #000000
 * trên nền trang; "Xuyên Vạn Kiếp" ở y=0 tức TRONG dải, chữ #FCFDFE và
 * thụt vào phải. Đoạn chữ nghiêng (Frame 151) ở y=554, tức DƯỚI đáy dải
 * (544), chữ #3F3B3B trên nền trang.
 *
 * Cả hai đều để trống mặc định nên mọi khối card-list đang dùng ở các
 * trang phân mục giữ nguyên như cũ, không mọc thêm gì.
 */
$heading_above = isset( $attributes['headingAbove'] ) ? (string) $attributes['headingAbove'] : '';
$caption_below = isset( $attributes['captionBelow'] ) ? (string) $attributes['captionBelow'] : '';

/*
 * Ngày cập nhật và nhãn chuyên mục trên từng thẻ.
 *
 * nntm_render_card_markup() vốn đã nhận hai tham số này nhưng card-list
 * luôn truyền cứng `true` — nên không có cách nào tắt từ giao diện. Figma
 * SECTION 3 và SECTION 5 đều TẮT cả hai lớp (`DATE` và `ARTICLE CAT`
 * visible=false), chỉ còn tiêu đề; để bật thì mỗi thẻ cao thêm 81px và
 * cả dải cao vượt thiết kế.
 *
 * Mặc định vẫn `true` nên các trang phân mục đang dùng không đổi gì.
 */
$show_date     = ! isset( $attributes['showDate'] ) || ! empty( $attributes['showDate'] );
$show_category = ! isset( $attributes['showCategory'] ) || ! empty( $attributes['showCategory'] );

$wrapper_attributes = get_block_wrapper_attributes(
	array(
		'class' => 'nntm-card-list' . ( 'none' !== $background ? ' nntm-card-list--nen-' . $background : '' ),
	)
);
?>
<section <?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput -- get_block_wrapper_attributes() da tu esc_attr() tung thuoc tinh. ?>>

	<?php if ( '' !== trim( $heading_above ) ) : ?>
		<p class="nntm-card-list__heading-above"><?php echo wp_kses_post( $heading_above ); ?></p>
	<?php endif; ?>

	<?php
	/*
	 * Lớp mang nền. Trước đây nền tô thẳng lên thẻ <section>, nên không có
	 * cách nào đặt chữ ra NGOÀI dải mà vẫn nằm trong block. Tách ra một lớp
	 * riêng thì phần trên và phần dưới dải nằm ngoài nó được.
	 */
	?>
	<div class="nntm-card-list__band">
	<div class="nntm-container">
		<?php if ( '' !== $heading ) : ?>
			<h2 class="nntm-card-list__heading<?php echo $has_subheading ? ' nntm-card-list__heading--with-sub' : ''; ?>"><?php echo wp_kses_post( $heading ); ?></h2>
		<?php endif; ?>

		<?php if ( $has_subheading ) : ?>
			<p class="nntm-card-list__subheading"><?php echo wp_kses_post( $subheading ); ?></p>
		<?php endif; ?>

		<?php if ( $is_youtube_source ) : ?>

			<?php if ( ! empty( $youtube_video_items ) ) : ?>
				<?php
				/*
				 * Gót Son (nền tối) và GITA CENTER (nền cam) dùng CHUNG hàm
				 * render nhưng thẻ KHÁC HÌNH DẠNG (điều phối viên đối chiếu
				 * lại Figma 13/08/2026): GITA CENTER có khung thẻ nền tối
				 * 388×360 bọc ảnh 348×196 thụt 20px + tiêu đề 3 dòng bên
				 * trong; Gót Son ảnh 348×198 trần, tiêu đề 2 dòng nằm ngoài.
				 */
				$framed_cards = ( 'cam' === $background );
				echo nntm_card_list_render_youtube_marquee( $youtube_video_items, $framed_cards ); // phpcs:ignore WordPress.Security.EscapeOutput -- da escape ben trong.
				?>
			<?php else : ?>
				<p class="nntm-card-list__empty"><?php esc_html_e( 'Chưa dán đường dẫn YouTube nào — vào trình soạn thảo, ô "Danh sách link YouTube" để thêm.', 'nntm' ); ?></p>
			<?php endif; ?>

		<?php elseif ( $query->have_posts() ) : ?>

			<?php if ( $is_carousel ) : ?>

				<?php
				// Bang the cuon ngang — tai su dung dung ky thuat cuon cua
				// blocks/term-list/style.css (flex nowrap + overflow-x auto +
				// scroll-snap). Nut lui/tien la <button> that, hanh vi cuon +
				// tu vo hieu hoa dau/cuoi + phim mui ten + tu chay (autoplay)
				// xu ly boi view.js. Truyen cau hinh autoplay xuong JS bang
				// data-*, KHONG nhung <script> noi tuyen (dung quy uoc du an).
				?>
				<div class="nntm-card-list__carousel" data-autoplay="<?php echo esc_attr( $autoplay ? 'true' : 'false' ); ?>" data-autoplay-interval="<?php echo esc_attr( (string) $autoplay_interval ); ?>">
					<button type="button" class="nntm-card-list__nav nntm-card-list__nav--prev" aria-label="<?php esc_attr_e( 'Xem thẻ trước', 'nntm' ); ?>">
						<span class="nntm-card-list__nav-icon" aria-hidden="true"></span>
					</button>

					<div class="nntm-card-list__track" tabindex="0" role="group" aria-label="<?php esc_attr_e( 'Danh sách cuộn ngang, dùng phím mũi tên trái/phải để cuộn', 'nntm' ); ?>">
						<?php foreach ( $query->posts as $queried_post ) : ?>
							<div class="nntm-card-list__track-item">
								<?php echo nntm_render_card_markup( $queried_post->ID, $variant, $show_date, true, $show_category ); // phpcs:ignore WordPress.Security.EscapeOutput -- da escape ben trong nntm_render_card_markup(). ?>
							</div>
						<?php endforeach; ?>
					</div>

					<button type="button" class="nntm-card-list__nav nntm-card-list__nav--next" aria-label="<?php esc_attr_e( 'Xem thẻ tiếp theo', 'nntm' ); ?>">
						<span class="nntm-card-list__nav-icon" aria-hidden="true"></span>
					</button>
				</div>

			<?php else : ?>

				<div class="nntm-grid nntm-grid--<?php echo esc_attr( (string) $columns ); ?>">
					<?php foreach ( $query->posts as $queried_post ) : ?>
						<?php echo nntm_render_card_markup( $queried_post->ID, $variant, $show_date, true, $show_category ); // phpcs:ignore WordPress.Security.EscapeOutput -- da escape ben trong nntm_render_card_markup(). ?>
					<?php endforeach; ?>
				</div>

				<?php if ( $show_paging && $query->max_num_pages > 1 ) : ?>
					<?php
					$prev_url = null;
					$next_url = null;

					if ( $paged > 1 ) {
						$prev_url = ( 1 === $paged - 1 ) ? remove_query_arg( 'paged' ) : add_query_arg( 'paged', $paged - 1 );
					}

					if ( $paged < $query->max_num_pages ) {
						$next_url = add_query_arg( 'paged', $paged + 1 );
					}
					?>
					<nav class="nntm-card-list__paging" aria-label="<?php esc_attr_e( 'Phân trang danh sách', 'nntm' ); ?>">
						<?php if ( $prev_url ) : ?>
							<a class="nntm-card-list__paging-btn nntm-card-list__paging-btn--prev" href="<?php echo esc_url( $prev_url ); ?>">
								<?php esc_html_e( 'BACK', 'nntm' ); ?>
							</a>
						<?php endif; ?>
						<?php if ( $next_url ) : ?>
							<a class="nntm-card-list__paging-btn nntm-card-list__paging-btn--next" href="<?php echo esc_url( $next_url ); ?>">
								<?php esc_html_e( 'NEXT', 'nntm' ); ?>
							</a>
						<?php endif; ?>
					</nav>
				<?php endif; ?>

			<?php endif; ?>

		<?php else : ?>
			<p class="nntm-card-list__empty"><?php esc_html_e( 'Chưa có nội dung nào phù hợp.', 'nntm' ); ?></p>
		<?php endif; ?>

		<?php
		/*
		 * Hàng biểu tượng nghe nhạc — tu Figma SECTION 5 `6376:6450`, khung
		 * ICON: y=566, cao 50, hai biểu tượng cách nhau 30, căn giữa.
		 *
		 * Chỉ hiện khi ban quản trị nhập đường dẫn, nên mọi khối card-list
		 * đang dùng ở các trang phân mục KHÔNG bị mọc thêm hàng này.
		 */
		$nntm_cl_spotify = isset( $attributes['spotifyUrl'] ) ? esc_url_raw( (string) $attributes['spotifyUrl'] ) : '';
		$nntm_cl_youtube = isset( $attributes['youtubeUrl'] ) ? esc_url_raw( (string) $attributes['youtubeUrl'] ) : '';

		if ( '' !== $nntm_cl_spotify || '' !== $nntm_cl_youtube ) :
			?>
			<div class="nntm-card-list__icons">
				<?php if ( '' !== $nntm_cl_spotify ) : ?>
					<a class="nntm-card-list__icon nntm-card-list__icon--spotify" href="<?php echo esc_url( $nntm_cl_spotify ); ?>" target="_blank" rel="noopener noreferrer">
						<span class="nntm-sr-only"><?php esc_html_e( 'Nghe trên Spotify (mở tab mới)', 'nntm' ); ?></span>
						<svg viewBox="0 0 50 50" width="50" height="50" aria-hidden="true" focusable="false">
							<circle cx="25" cy="25" r="25" fill="currentColor" />
							<path d="M13 19c8-2.4 16.5-1.8 23.5 2.2M15 26c6.6-2 13.6-1.5 19.4 1.8M17 33c5.3-1.6 10.9-1.2 15.5 1.4"
								stroke="var(--nntm-cl-icon-nen)" stroke-width="3.4" stroke-linecap="round" fill="none" />
						</svg>
					</a>
				<?php endif; ?>

				<?php if ( '' !== $nntm_cl_youtube ) : ?>
					<a class="nntm-card-list__icon nntm-card-list__icon--youtube" href="<?php echo esc_url( $nntm_cl_youtube ); ?>" target="_blank" rel="noopener noreferrer">
						<span class="nntm-sr-only"><?php esc_html_e( 'Xem trên YouTube (mở tab mới)', 'nntm' ); ?></span>
						<svg viewBox="0 0 60 47" width="60" height="47" aria-hidden="true" focusable="false">
							<rect x="0" y="0" width="60" height="47" rx="12" fill="currentColor" />
							<path d="M24 14 L42 23.5 L24 33 Z" fill="var(--nntm-cl-icon-nen)" />
						</svg>
					</a>
				<?php endif; ?>
			</div>
			<?php
		endif;
		?>
	</div>
	</div>

	<?php if ( '' !== trim( $caption_below ) ) : ?>
		<p class="nntm-card-list__caption-below"><?php echo wp_kses_post( $caption_below ); ?></p>
	<?php endif; ?>
</section>
