<?php
/**
 * Trang kết quả tìm bằng hình ảnh (Phase 2, khảo sát câu 15–16).
 *
 * MÀN TỰ DỰNG — Figma chưa có thiết kế. Dựng lại từ các khối có sẵn của trang
 * tìm kiếm (nntm-search, nntm-article-rows); phần riêng chỉ ở search.css mục
 * "Tìm bằng hình ảnh".
 *
 * Dữ liệu: plugin nntm-search (includes/image-page.php). Ảnh xem trước không
 * nằm ở máy chủ — image-page.js lấy từ sessionStorage của trình duyệt.
 */

defined( 'ABSPATH' ) || exit;

$nntm_token    = nntm_search_image_page_token();
$nntm_per_page = 10;
// Tham số trang RIÊNG, không dùng `paged`: truy vấn chính của WordPress tìm
// chuỗi `s` (các từ khoá ghép lại) thường ra ít bài hơn danh sách đã gộp ở đây,
// và WordPress trả 404 khi `paged` vượt số trang của NÓ — trang 2 chết (đã gặp).
$nntm_page     = max( 1, isset( $_GET['nntm_trang'] ) ? absint( $_GET['nntm_trang'] ) : 1 ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$nntm_kq       = nntm_search_image_page( $nntm_token, $nntm_page, $nntm_per_page );
$nntm_so_trang = (int) ceil( $nntm_kq['total'] / $nntm_per_page );

get_header();
?>

<main id="nntm-noi-dung-chinh" class="nntm-main--full">

	<section class="nntm-article-rows nntm-search nntm-search-anh" data-nntm-anh="<?php echo esc_attr( $nntm_token ); ?>">
		<div class="nntm-article-rows__inner">

			<header class="nntm-search__head nntm-search-anh__head">
				<figure class="nntm-search-anh__xem-truoc" hidden>
					<img alt="<?php esc_attr_e( 'Ảnh bạn đã tải lên', 'nntm' ); ?>" />
				</figure>

				<div class="nntm-search-anh__mo-ta">
					<h1 class="nntm-article-rows__heading nntm-search__title"><?php esc_html_e( 'Kết quả tìm bằng hình ảnh', 'nntm' ); ?></h1>

					<?php if ( ! $nntm_kq['ok'] ) : ?>
						<p class="nntm-search__summary"><?php esc_html_e( 'Phiên tìm bằng ảnh này đã hết hạn (sau 30 phút). Quý vị vui lòng chọn lại ảnh.', 'nntm' ); ?></p>
					<?php elseif ( $nntm_kq['keywords'] ) : ?>
						<p class="nntm-search__summary"><?php esc_html_e( 'Ảnh này có:', 'nntm' ); ?></p>
						<ul class="nntm-search-anh__tu-khoa">
							<?php foreach ( $nntm_kq['keywords'] as $nntm_kw ) : ?>
								<li>
									<a class="nntm-search-anh__chip" href="<?php echo esc_url( add_query_arg( 's', rawurlencode( (string) $nntm_kw['word'] ), home_url( '/' ) ) ); ?>" title="<?php esc_attr_e( 'Tìm riêng từ khoá này', 'nntm' ); ?>">
										<?php echo esc_html( (string) $nntm_kw['word'] ); ?>
										<span class="nntm-search-anh__diem"><?php echo esc_html( (string) round( (float) $nntm_kw['score'] * 100 ) ); ?>%</span>
									</a>
								</li>
							<?php endforeach; ?>
						</ul>
					<?php else : ?>
						<p class="nntm-search__summary"><?php esc_html_e( 'Không đọc được từ khoá nào từ ảnh này — dưới đây là nội dung có ảnh trông giống nhất.', 'nntm' ); ?></p>
					<?php endif; ?>

					<p class="nntm-search-anh__tim-lai">
						<button type="button" class="nntm-article-rows__cta nntm-article-rows__cta--secondary" data-nntm-anh-chon><?php esc_html_e( 'Tìm bằng ảnh khác', 'nntm' ); ?></button>
						<input type="file" accept="image/jpeg,image/png,image/webp,image/gif" hidden data-nntm-anh-tep />
						<span class="nntm-search-anh__trang-thai" role="status" aria-live="polite"></span>
					</p>
				</div>
			</header>

			<?php if ( $nntm_kq['ok'] ) : ?>

				<?php if ( $nntm_kq['rows'] ) : ?>
					<h2 class="nntm-search-anh__muc">
						<?php
						printf(
							/* translators: %s: số bài */
							esc_html__( 'Bài viết nhắc tới những gì trong ảnh (%s)', 'nntm' ),
							esc_html( number_format_i18n( $nntm_kq['total'] ) )
						);
						?>
					</h2>
					<div class="nntm-article-rows__list">
						<?php foreach ( $nntm_kq['rows'] as $nntm_i => $nntm_row ) : ?>
							<?php nntm_render_search_row( $nntm_row, 1 === $nntm_i % 2 ); ?>
						<?php endforeach; ?>
					</div>

					<?php if ( $nntm_so_trang > 1 ) : ?>
						<?php
						$nntm_url_trang = static function ( int $so ): string {
							return add_query_arg( 'nntm_trang', $so );
						};
						?>
						<nav class="nntm-paging nntm-paging--center" aria-label="<?php esc_attr_e( 'Phân trang', 'nntm' ); ?>">
							<?php if ( $nntm_page > 1 ) : ?>
								<a class="nntm-paging__btn nntm-paging__btn--prev" href="<?php echo esc_url( $nntm_url_trang( $nntm_page - 1 ) ); ?>"><span class="nntm-paging__icon" aria-hidden="true"></span><span class="nntm-paging__label"><?php esc_html_e( 'Trước', 'nntm' ); ?></span></a>
							<?php endif; ?>
							<span class="nntm-search__page-of">
								<?php
								/* translators: 1: trang hiện tại, 2: tổng số trang */
								printf( esc_html__( 'Trang %1$s / %2$s', 'nntm' ), esc_html( number_format_i18n( $nntm_page ) ), esc_html( number_format_i18n( $nntm_so_trang ) ) );
								?>
							</span>
							<?php if ( $nntm_page < $nntm_so_trang ) : ?>
								<a class="nntm-paging__btn nntm-paging__btn--next" href="<?php echo esc_url( $nntm_url_trang( $nntm_page + 1 ) ); ?>"><span class="nntm-paging__label"><?php esc_html_e( 'Sau', 'nntm' ); ?></span><span class="nntm-paging__icon" aria-hidden="true"></span></a>
							<?php endif; ?>
						</nav>
					<?php endif; ?>
				<?php endif; ?>

				<?php if ( $nntm_kq['similar'] ) : ?>
					<h2 class="nntm-search-anh__muc"><?php esc_html_e( 'Nội dung có ảnh trông giống', 'nntm' ); ?></h2>
					<ul class="nntm-search-anh__luoi">
						<?php foreach ( $nntm_kq['similar'] as $nntm_item ) : ?>
							<li class="nntm-search-anh__the">
								<a href="<?php echo esc_url( (string) $nntm_item['permalink'] ); ?>">
									<span class="nntm-search-anh__khung">
										<?php if ( ! empty( $nntm_item['thumb'] ) ) : ?>
											<img src="<?php echo esc_url( (string) $nntm_item['thumb'] ); ?>" alt="" loading="lazy" />
										<?php endif; ?>
									</span>
									<?php // title đã esc_html ở nntm_search_group_by_post(). ?>
									<span class="nntm-search-anh__ten"><?php echo esc_html( wp_specialchars_decode( (string) $nntm_item['title'], ENT_QUOTES ) ); ?></span>
								</a>
							</li>
						<?php endforeach; ?>
					</ul>
				<?php endif; ?>

				<?php if ( ! $nntm_kq['rows'] && ! $nntm_kq['similar'] ) : ?>
					<div class="nntm-search__empty">
						<p class="nntm-article-rows__empty"><?php esc_html_e( 'Chưa thấy nội dung nào hợp với ảnh này.', 'nntm' ); ?></p>
						<ul class="nntm-search__hints">
							<li><?php esc_html_e( 'Thử ảnh rõ chủ thể hơn — một vật, một cảnh, chụp gần.', 'nntm' ); ?></li>
							<li><?php esc_html_e( 'Bấm vào một từ khoá phía trên để tìm riêng từ đó.', 'nntm' ); ?></li>
						</ul>
					</div>
				<?php endif; ?>

			<?php endif; ?>

		</div>
	</section>

</main>

<?php
get_footer();
