<?php
defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'nntm_fgc_doc_chi_tiet' ) ) {
	/**
	 * Tiêu đề và nội dung cho khung "Xem Chi Tiết" của một slide.
	 *
	 * Mỗi slide đúng MỘT bộ tiêu đề + nội dung. Trước đây chữ nằm rải rác ở
	 * từng ảnh phụ, khiến phần quản trị rối và cùng một slide có thể ra nhiều
	 * trang chữ khác nhau.
	 *
	 * Đọc luôn cả dữ liệu cũ: slide nào đã nhập chữ vào ảnh phụ đầu tiên từ
	 * trước thì vẫn dùng lại được, không mất nội dung đã gõ.
	 *
	 * @return array{tieu_de: string, noi_dung: string}
	 */
	function nntm_fgc_doc_chi_tiet( array $slide, string $title_du_phong = '' ): array {
		$tieu_de  = sanitize_text_field( (string) ( $slide['popupTitle'] ?? '' ) );
		$noi_dung = sanitize_textarea_field( (string) ( $slide['popupIntro'] ?? '' ) );

		// Chuyển tiếp: lấy từ ảnh phụ đầu tiên nếu ô mới còn trống.
		$anh_phu = ( isset( $slide['details'] ) && is_array( $slide['details'] ) ) ? array_values( $slide['details'] ) : array();
		$dau     = ( isset( $anh_phu[0] ) && is_array( $anh_phu[0] ) ) ? $anh_phu[0] : array();

		if ( '' === $tieu_de ) {
			$tieu_de = sanitize_text_field( (string) ( $dau['title'] ?? '' ) );
		}

		if ( '' === $noi_dung ) {
			$noi_dung = sanitize_textarea_field( (string) ( $dau['text'] ?? '' ) );
		}

		/*
		 * Tiêu đề dưới ảnh chỉ dùng làm đường lui cho tiêu đề khung chi tiết khi
		 * đã có nội dung — không tự bịa ra nội dung cho slide chưa nhập gì, nếu
		 * không nút "Xem Chi Tiết" sẽ hiện ở mọi slide.
		 */
		if ( '' === $tieu_de && '' !== $noi_dung ) {
			$tieu_de = $title_du_phong;
		}

		return array(
			'tieu_de'  => $tieu_de,
			'noi_dung' => $noi_dung,
		);
	}
}

if ( ! function_exists( 'nntm_fgc_gom_anh' ) ) {
	/**
	 * Danh sách ảnh cho khung xem ảnh: ảnh của slide trước, rồi tới các ảnh phụ.
	 *
	 * Bấm vào ảnh nào thì thấy đúng ảnh đó trước, rồi lướt tiếp sang những ảnh
	 * còn lại của cùng slide.
	 */
	function nntm_fgc_gom_anh( array $slide ): array {
		$co_anh = static function ( $m ): bool {
			return is_array( $m ) && ( ! empty( $m['imageId'] ) || ! empty( $m['imageUrl'] ) );
		};

		$ra = array();

		if ( $co_anh( $slide ) ) {
			$ra[] = array(
				'imageId'  => $slide['imageId'] ?? 0,
				'imageUrl' => $slide['imageUrl'] ?? '',
				'imageAlt' => $slide['imageAlt'] ?? '',
			);
		}

		$anh_phu = ( isset( $slide['details'] ) && is_array( $slide['details'] ) ) ? array_values( $slide['details'] ) : array();

		foreach ( $anh_phu as $m ) {
			if ( $co_anh( $m ) ) {
				$ra[] = array(
					'imageId'  => $m['imageId'] ?? 0,
					'imageUrl' => $m['imageUrl'] ?? '',
					'imageAlt' => $m['imageAlt'] ?? '',
				);
			}
		}

		return $ra;
	}
}

$render_image = static function ( array $item, string $class, bool $eager = false ): string {
	$id  = isset( $item['imageId'] ) ? absint( $item['imageId'] ) : 0;
	$url = isset( $item['imageUrl'] ) ? esc_url_raw( (string) $item['imageUrl'] ) : '';
	$alt = isset( $item['imageAlt'] ) ? sanitize_text_field( (string) $item['imageAlt'] ) : '';
	$attrs = array(
		'class'    => $class,
		'alt'      => $alt,
		'loading'  => $eager ? 'eager' : 'lazy',
		'decoding' => 'async',
	);
	if ( $eager ) {
		$attrs['fetchpriority'] = 'high';
	}
	if ( $id ) {
		return (string) wp_get_attachment_image( $id, 'full', false, $attrs );
	}
	if ( ! $url ) {
		return '';
	}
	$extra = $eager ? ' loading="eager" fetchpriority="high"' : ' loading="lazy"';
	return '<img class="' . esc_attr( $class ) . '" src="' . esc_url( $url ) . '" alt="' . esc_attr( $alt ) . '" decoding="async"' . $extra . '>';
};

$slides = isset( $attributes['slides'] ) && is_array( $attributes['slides'] ) ? $attributes['slides'] : array();
$slides = array_values(
	array_filter(
		$slides,
		static fn( $slide ): bool => is_array( $slide ) && ( ! empty( $slide['imageId'] ) || ! empty( $slide['imageUrl'] ) )
	)
);
$heading          = sanitize_text_field( (string) ( $attributes['heading'] ?? '' ) );
$background_style = in_array( $attributes['backgroundStyle'] ?? 'white', array( 'white', 'cream' ), true ) ? (string) $attributes['backgroundStyle'] : 'white';
$arrow_style      = in_array( $attributes['arrowStyle'] ?? 'plain', array( 'plain', 'boxed' ), true ) ? (string) $attributes['arrowStyle'] : 'plain';
$interval         = max( 3, min( 20, absint( $attributes['interval'] ?? 5 ) ) );

/*
 * Nền của khung xem tác phẩm — quản trị chọn từ bảng màu của dự án, mặc định đen.
 * Danh sách này phải trùng enum trong block.json và lớp CSS trong style.css.
 */
$viewer_bg_allowed = array( 'den', 'muc', 'mem', 'cham', 'reu', 'kem' );
$viewer_bg         = isset( $attributes['viewerBackground'] ) ? sanitize_key( (string) $attributes['viewerBackground'] ) : 'den';
if ( ! in_array( $viewer_bg, $viewer_bg_allowed, true ) ) {
	$viewer_bg = 'den';
}
$show_arrows      = ! array_key_exists( 'showArrows', $attributes ) || ! empty( $attributes['showArrows'] );
$autoplay         = ! empty( $attributes['autoplay'] );
$uid              = 'nntm-fgc-' . wp_unique_id();

$classes = array(
	'nntm-feature-gallery-carousel',
	'nntm-feature-gallery-carousel--bg-' . $background_style,
	'nntm-feature-gallery-carousel--arrows-' . $arrow_style,
);
$wrapper = get_block_wrapper_attributes(
	array(
		'class'         => implode( ' ', $classes ),
		'data-autoplay' => $autoplay ? '1' : '0',
		'data-interval' => (string) $interval,
		'id'            => $uid,
	)
);
?>
<section <?php echo $wrapper;  ?>>
	<?php if ( $heading ) : ?>
		<header class="nntm-feature-gallery-carousel__header">
			<h2 class="nntm-feature-gallery-carousel__heading"><span><?php echo esc_html( $heading ); ?></span></h2>
		</header>
	<?php endif; ?>

	<?php if ( $slides ) : ?>
		<div class="nntm-feature-gallery-carousel__slider" aria-roledescription="carousel" aria-label="<?php echo esc_attr( $heading ?: __( 'Thư viện ảnh nổi bật', 'nntm' ) ); ?>">
			<?php if ( $show_arrows && count( $slides ) > 1 ) : ?>
				<button class="nntm-feature-gallery-carousel__arrow nntm-feature-gallery-carousel__arrow--prev" type="button" aria-label="<?php esc_attr_e( 'Ảnh trước', 'nntm' ); ?>" data-fgc-prev><span aria-hidden="true">←</span></button>
			<?php endif; ?>

			<div class="nntm-feature-gallery-carousel__track" data-fgc-track tabindex="0">
				<?php foreach ( $slides as $index => $slide ) :
					$title        = sanitize_text_field( (string) ( $slide['title'] ?? '' ) );
					$detail_label = sanitize_text_field( (string) ( $slide['detailLabel'] ?? __( 'Xem Chi Tiết', 'nntm' ) ) );
					$modal_id     = $uid . '-dialog-' . $index;
					$chi_tiet     = nntm_fgc_doc_chi_tiet( $slide, $title );
					$co_chi_tiet  = ( '' !== $chi_tiet['tieu_de'] || '' !== $chi_tiet['noi_dung'] );
					?>
					<figure class="nntm-feature-gallery-carousel__slide" data-fgc-slide data-index="<?php echo esc_attr( (string) $index ); ?>">
						<?php
						/*
						 * Chính tấm ảnh là nút mở khung xem tác phẩm: bấm thẳng vào ảnh
						 * là xem được, không bắt phải bấm CTA. Dùng <button> thật nên
						 * Tab/Enter/Space đều dùng được; view.js gỡ Tab khỏi các ảnh
						 * không nằm giữa để bàn phím không lạc vào slide đang bị ẩn.
						 */
						?>
						<button
							class="nntm-feature-gallery-carousel__media"
							type="button"
							data-fgc-open="<?php echo esc_attr( $modal_id ); ?>"
							data-fgc-mode="anh"
							data-fgc-media
							aria-label="<?php echo esc_attr( $title ? sprintf( __( 'Xem ảnh: %s', 'nntm' ), $title ) : __( 'Xem ảnh', 'nntm' ) ); ?>"
						>
							<?php echo wp_kses_post( $render_image( $slide, 'nntm-feature-gallery-carousel__image', 0 === $index ) ); ?>
						</button>
						<figcaption class="nntm-feature-gallery-carousel__copy">
							<?php if ( $title ) : ?><h3><?php echo esc_html( $title ); ?></h3><?php endif; ?>
							<?php
							/*
							 * Chua nhap tieu de lan noi dung thi khong hien nut: bam vao
							 * chi gap mot khung rong, khach khong hieu vi sao.
							 */
							if ( $detail_label && $co_chi_tiet ) :
								?>
								<button
									class="nntm-feature-gallery-carousel__detail"
									type="button"
									data-fgc-open="<?php echo esc_attr( $modal_id ); ?>"
									data-fgc-mode="chi-tiet"
								><?php echo esc_html( $detail_label ); ?></button>
								<?php
							endif;
							?>
						</figcaption>
					</figure>
				<?php endforeach; ?>
			</div>

			<?php if ( $show_arrows && count( $slides ) > 1 ) : ?>
				<button class="nntm-feature-gallery-carousel__arrow nntm-feature-gallery-carousel__arrow--next" type="button" aria-label="<?php esc_attr_e( 'Ảnh tiếp theo', 'nntm' ); ?>" data-fgc-next><span aria-hidden="true">→</span></button>
			<?php endif; ?>
		</div>

		<div class="nntm-feature-gallery-carousel__dialogs">
			<?php
			foreach ( $slides as $index => $slide ) :
				$title       = sanitize_text_field( (string) ( $slide['title'] ?? '' ) );
				$chi_tiet    = nntm_fgc_doc_chi_tiet( $slide, $title );
				$co_chi_tiet = ( '' !== $chi_tiet['tieu_de'] || '' !== $chi_tiet['noi_dung'] );
				$anh         = nntm_fgc_gom_anh( $slide );
				$modal_id    = $uid . '-dialog-' . $index;
				?>
				<?php
				/*
				 * MOT khung, HAI che do — view.js gan class theo nut nao duoc bam:
				 *   --che-do-anh     bam vao anh   -> xem anh to, luot qua ca cac anh phu
				 *   --che-do-chi-tiet bam "Xem Chi Tiet" -> anh ben trai, chu ben phai
				 * Dung chung mot khung de khong phai nhan doi phan dong/ban phim/bay
				 * tieu diem cua view.js.
				 */
				?>
				<div
					class="nntm-feature-gallery-modal nntm-feature-gallery-modal--nen-<?php echo esc_attr( $viewer_bg ); ?>"
					id="<?php echo esc_attr( $modal_id ); ?>"
					role="dialog"
					aria-modal="true"
					aria-hidden="true"
					<?php
					/*
					 * Dat aria-label thay vi aria-labelledby: tieu de chi ton tai o
					 * che do chi tiet, tro toi mot id khong co that o che do xem anh
					 * la tro doc man hinh khong biet doc gi.
					 */
					?>
					aria-label="<?php echo esc_attr( $title ?: __( 'Xem tác phẩm', 'nntm' ) ); ?>"
					data-fgc-dialog
					hidden
				>
					<div class="nntm-feature-gallery-modal__backdrop" data-fgc-close></div>

					<div class="nntm-feature-gallery-modal__panel" role="document">
						<button class="nntm-feature-gallery-modal__close" type="button" aria-label="<?php esc_attr_e( 'Đóng', 'nntm' ); ?>" data-fgc-close><span aria-hidden="true">×</span></button>

						<?php /* ---------- Che do XEM ANH ---------- */ ?>
						<div class="nntm-feature-gallery-modal__xem-anh" data-fgc-pane="anh">
							<?php if ( $anh ) : ?>
								<div class="nntm-feature-gallery-modal__carousel" data-fgc-popup-carousel>
									<?php if ( count( $anh ) > 1 ) : ?>
										<button class="nntm-feature-gallery-modal__arrow nntm-feature-gallery-modal__arrow--prev" type="button" aria-label="<?php esc_attr_e( 'Ảnh trước', 'nntm' ); ?>" data-fgc-popup-prev><span aria-hidden="true">←</span></button>
									<?php endif; ?>

									<div class="nntm-feature-gallery-modal__viewport" data-fgc-popup-viewport tabindex="0">
										<?php foreach ( $anh as $k => $m ) : ?>
											<article
												class="nntm-feature-gallery-modal__slide nntm-feature-gallery-modal__slide--anh-doc"
												data-fgc-popup-slide
												data-popup-index="<?php echo esc_attr( (string) $k ); ?>"
												aria-hidden="<?php echo 0 === $k ? 'false' : 'true'; ?>"
											>
												<div class="nntm-feature-gallery-modal__media">
													<?php echo wp_kses_post( $render_image( $m, 'nntm-feature-gallery-modal__image', 0 === $k ) ); ?>
												</div>
											</article>
										<?php endforeach; ?>
									</div>

									<?php if ( count( $anh ) > 1 ) : ?>
										<button class="nntm-feature-gallery-modal__arrow nntm-feature-gallery-modal__arrow--next" type="button" aria-label="<?php esc_attr_e( 'Ảnh tiếp theo', 'nntm' ); ?>" data-fgc-popup-next><span aria-hidden="true">→</span></button>
									<?php endif; ?>
								</div>

								<?php if ( count( $anh ) > 1 ) : ?>
									<footer class="nntm-feature-gallery-modal__footer">
										<div class="nntm-feature-gallery-modal__dots" role="tablist" aria-label="<?php esc_attr_e( 'Chọn ảnh', 'nntm' ); ?>">
											<?php foreach ( $anh as $k => $_m ) : ?>
												<button class="nntm-feature-gallery-modal__dot" type="button" aria-label="<?php echo esc_attr( sprintf( __( 'Ảnh %d', 'nntm' ), $k + 1 ) ); ?>" aria-selected="<?php echo 0 === $k ? 'true' : 'false'; ?>" data-fgc-popup-dot="<?php echo esc_attr( (string) $k ); ?>"></button>
											<?php endforeach; ?>
										</div>
										<div class="nntm-feature-gallery-modal__counter" aria-live="polite"><span data-fgc-popup-current>1</span><span aria-hidden="true"> / </span><span><?php echo esc_html( (string) count( $anh ) ); ?></span></div>
									</footer>
								<?php endif; ?>
							<?php else : ?>
								<p class="nntm-feature-gallery-modal__empty"><?php esc_html_e( 'Slide này chưa có ảnh.', 'nntm' ); ?></p>
							<?php endif; ?>
						</div>

						<?php /* ---------- Che do XEM CHI TIET ---------- */ ?>
						<div class="nntm-feature-gallery-modal__chi-tiet" data-fgc-pane="chi-tiet">
							<?php if ( $co_chi_tiet ) : ?>
								<?php if ( $anh ) : ?>
									<div class="nntm-feature-gallery-modal__ct-anh">
										<?php
										/*
										 * Dấu ngoặc kép nằm TRONG khung bọc sát tấm ảnh chứ không
										 * nằm ở cột chữ: cột ảnh rộng hơn ảnh khi ảnh dựng đứng,
										 * neo vào cột thì dấu trôi ra xa mép ảnh. Lớp bọc co đúng
										 * bằng ảnh nên dấu thẳng mép trái ảnh.
										 *
										 * Đặt TRƯỚC thẻ ảnh: lớp bọc xếp dọc nên thứ tự trong HTML
										 * chính là thứ tự trên xuống — để sau thì dấu rơi xuống
										 * dưới ảnh.
										 */
										?>
										<div class="nntm-feature-gallery-modal__ct-khung">
											<span class="nntm-feature-gallery-modal__quote" aria-hidden="true">&ldquo;</span>
											<?php echo wp_kses_post( $render_image( $anh[0], 'nntm-feature-gallery-modal__ct-hinh', false ) ); ?>
										</div>
									</div>
								<?php endif; ?>

								<div class="nntm-feature-gallery-modal__ct-chu">
									<?php if ( '' !== $chi_tiet['tieu_de'] ) : ?>
										<h3 class="nntm-feature-gallery-modal__ct-tieu-de"><?php echo esc_html( $chi_tiet['tieu_de'] ); ?></h3>
									<?php endif; ?>

									<?php if ( '' !== $chi_tiet['noi_dung'] ) : ?>
										<p class="nntm-feature-gallery-modal__ct-noi-dung"><?php echo nl2br( esc_html( $chi_tiet['noi_dung'] ) ); ?></p>
									<?php endif; ?>
								</div>
							<?php else : ?>
								<p class="nntm-feature-gallery-modal__empty"><?php esc_html_e( 'Chưa có nội dung chi tiết.', 'nntm' ); ?></p>
							<?php endif; ?>
						</div>
					</div>
				</div>
			<?php endforeach; ?>
		</div>
	<?php endif; ?>
</section>
