<?php

defined( 'ABSPATH' ) || exit;

$nntm_pub = get_queried_object();

$nntm_kho_sach = home_url( '/hoa-khai' );

if ( '' === $nntm_kho_sach ) {
	$nntm_kho_sach = home_url( '/' );
}

$nntm_tieu_de    = get_the_title( $nntm_pub );
$nntm_tac_gia    = (string) get_post_meta( $nntm_pub->ID, '_nntm_pub_tac_gia', true );
$nntm_bia        = get_the_post_thumbnail_url( $nntm_pub, 'medium' );
$nntm_gioi_thieu = has_excerpt( $nntm_pub )
	? get_the_excerpt( $nntm_pub )
	: wp_trim_words( wp_strip_all_tags( (string) $nntm_pub->post_content ), 120, '…' );
$nntm_nhac = function_exists( 'nntm_publication_music_tracks' )
	? nntm_publication_music_tracks()
	: array();

?><!DOCTYPE html>
<html <?php language_attributes(); ?> class="nntm-doc-html">
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>" />
	<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover" />
	<?php
	 
	?>
	<meta name="robots" content="noindex, nofollow" />
	<?php wp_head(); ?>
</head>

<body class="nntm-doc" data-nen="toi">

<div class="nntm-doc__app">

	<header class="nntm-doc__bar">
		<a class="nntm-doc__icon nntm-doc__icon--back" data-nntm-doc="quay-lai" href="<?php echo esc_url( $nntm_kho_sach ); ?>" title="<?php esc_attr_e( 'Thoát', 'nntm' ); ?>">
			<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M15 5l-7 7 7 7" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
			<span class="nntm-doc__sr"><?php esc_html_e( 'Thoát', 'nntm' ); ?></span>
		</a>

		<h1 class="nntm-doc__title"><?php echo esc_html( $nntm_tieu_de ); ?></h1>

		<div class="nntm-doc__actions">
			<button type="button" class="nntm-doc__icon" data-nntm-doc="muc-luc" aria-expanded="false" aria-controls="nntm-doc-toc" title="<?php esc_attr_e( 'Mục lục', 'nntm' ); ?>">
				<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M4 6h16M4 12h16M4 18h10" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
				<span class="nntm-doc__sr"><?php esc_html_e( 'Mục lục', 'nntm' ); ?></span>
			</button>

			<button type="button" class="nntm-doc__icon" data-nntm-doc="danh-dau" aria-pressed="false" title="<?php esc_attr_e( 'Đánh dấu trang này', 'nntm' ); ?>">
				<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M7 4h10a1 1 0 011 1v15l-6-4-6 4V5a1 1 0 011-1z" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linejoin="round"/><path d="M12 9v4M10 11h4" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
				<span class="nntm-doc__sr"><?php esc_html_e( 'Đánh dấu trang này', 'nntm' ); ?></span>
			</button>

			<?php if ( $nntm_nhac ) : ?>
				<button type="button" class="nntm-doc__icon" data-nntm-doc="nhac" aria-expanded="false" aria-controls="nntm-doc-nhac" title="<?php esc_attr_e( 'Nhạc nền', 'nntm' ); ?>">
					<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M9 18V6l10-2v12M9 10l10-2" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/><circle cx="6.5" cy="18" r="2.5" fill="none" stroke="currentColor" stroke-width="1.7"/><circle cx="16.5" cy="16" r="2.5" fill="none" stroke="currentColor" stroke-width="1.7"/></svg>
					<span class="nntm-doc__sr"><?php esc_html_e( 'Nhạc nền', 'nntm' ); ?></span>
				</button>
			<?php endif; ?>

			<?php
			 
			?>
			<button type="button" class="nntm-doc__icon" data-nntm-doc="hien" aria-expanded="false" aria-controls="nntm-doc-hien" title="<?php esc_attr_e( 'Nền và cách xem', 'nntm' ); ?>">
				<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><circle cx="12" cy="12" r="8.4" fill="none" stroke="currentColor" stroke-width="1.7"/><path d="M12 3.6a8.4 8.4 0 010 16.8z" fill="currentColor"/></svg>
				<span class="nntm-doc__sr"><?php esc_html_e( 'Nền và cách xem', 'nntm' ); ?></span>
			</button>

			<button type="button" class="nntm-doc__icon" data-nntm-doc="toan-man-hinh" title="<?php esc_attr_e( 'Toàn màn hình (F)', 'nntm' ); ?>">
				<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M4 9V4h5M20 9V4h-5M4 15v5h5M20 15v5h-5" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
				<span class="nntm-doc__sr"><?php esc_html_e( 'Toàn màn hình', 'nntm' ); ?></span>
			</button>
		</div>
	</header>

	<div class="nntm-doc__body">

		<aside class="nntm-doc__side" id="nntm-doc-side">
			<button type="button" class="nntm-doc__side-close" data-nntm-doc="dong-ben" aria-controls="nntm-doc-side" aria-expanded="true" title="<?php esc_attr_e( 'Thu gọn khung sách', 'nntm' ); ?>">
				<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M6 6l12 12M18 6L6 18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
				<span class="nntm-doc__sr"><?php esc_html_e( 'Thu gọn khung sách', 'nntm' ); ?></span>
			</button>

			<?php if ( $nntm_bia ) : ?>
				<img class="nntm-doc__cover" src="<?php echo esc_url( $nntm_bia ); ?>" alt="" />
			<?php endif; ?>

			<div class="nntm-doc__meta">
				<p class="nntm-doc__book-title"><?php echo esc_html( $nntm_tieu_de ); ?></p>
				<?php if ( '' !== $nntm_tac_gia ) : ?>
					<p class="nntm-doc__author"><?php echo esc_html( $nntm_tac_gia ); ?></p>
				<?php endif; ?>
			</div>

			<?php
			 
			if ( ! is_user_logged_in() ) :
				?>
				<div class="nntm-doc__cta">
					<p class="nntm-doc__cta-note"><?php esc_html_e( 'Đăng nhập để lưu chỗ đang đọc', 'nntm' ); ?></p>
					<a class="nntm-doc__cta-btn" href="<?php echo esc_url( function_exists( 'nntm_login_url' ) ? nntm_login_url( nntm_doc_url( $nntm_pub ) ) : wp_login_url( nntm_doc_url( $nntm_pub ) ) ); ?>">
						<?php esc_html_e( 'Đăng nhập', 'nntm' ); ?>
					</a>
				</div>
			<?php endif; ?>

			<?php if ( '' !== $nntm_gioi_thieu ) : ?>
				<div class="nntm-doc__intro">
					<p class="nntm-doc__intro-head"><?php esc_html_e( 'Giới thiệu sách', 'nntm' ); ?></p>
					<div class="nntm-doc__intro-body"><?php echo wp_kses_post( wpautop( $nntm_gioi_thieu ) ); ?></div>
				</div>
			<?php endif; ?>
		</aside>

		<button type="button" class="nntm-doc__side-open" data-nntm-doc="mo-ben" aria-controls="nntm-doc-side" aria-expanded="false" title="<?php esc_attr_e( 'Mở khung sách', 'nntm' ); ?>">
			<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M9 6l6 6-6 6" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"/></svg>
			<span class="nntm-doc__sr"><?php esc_html_e( 'Mở khung sách', 'nntm' ); ?></span>
		</button>

		<main class="nntm-doc__stage" data-nntm-doc="stage">
			<p class="nntm-doc__loading" data-nntm-doc="dang-tai">
				<span class="nntm-doc__spinner" aria-hidden="true"></span>
				<?php esc_html_e( 'Đang mở sách…', 'nntm' ); ?>
			</p>

			<article class="nntm-doc__text" data-nntm-doc="chu-sach" hidden></article>

		</main>

		<button type="button" class="nntm-doc__flip nntm-doc__flip--prev" data-nntm-doc="truoc" title="<?php esc_attr_e( 'Trang trước', 'nntm' ); ?>">
			<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M14 6l-6 6 6 6" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"/></svg>
			<span class="nntm-doc__sr"><?php esc_html_e( 'Trang trước', 'nntm' ); ?></span>
		</button>

		<button type="button" class="nntm-doc__flip nntm-doc__flip--next" data-nntm-doc="sau" title="<?php esc_attr_e( 'Trang sau', 'nntm' ); ?>">
			<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M10 6l6 6-6 6" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"/></svg>
			<span class="nntm-doc__sr"><?php esc_html_e( 'Trang sau', 'nntm' ); ?></span>
		</button>

		<nav class="nntm-doc__toc" id="nntm-doc-toc" hidden aria-label="<?php esc_attr_e( 'Mục lục', 'nntm' ); ?>">
			<p class="nntm-doc__toc-head"><?php esc_html_e( 'Mục lục', 'nntm' ); ?></p>
			<div class="nntm-doc__toc-body" data-nntm-doc="toc-body"></div>
		</nav>

		<div class="nntm-doc__panel" id="nntm-doc-hien" hidden>
			<p class="nntm-doc__panel-head"><?php esc_html_e( 'Nền', 'nntm' ); ?></p>
			<div class="nntm-doc__panel-row">
				<!-- <button type="button" class="nntm-doc__swatch" data-nntm-doc="nen" data-nen="sang"><span class="nntm-doc__sr"><?php esc_html_e( 'Sáng', 'nntm' ); ?></span></button> -->
				<button type="button" class="nntm-doc__swatch" data-nntm-doc="nen" data-nen="nga"><span class="nntm-doc__sr"><?php esc_html_e( 'Vàng ngà', 'nntm' ); ?></span></button>
				<button type="button" class="nntm-doc__swatch is-active" data-nntm-doc="nen" data-nen="toi"><span class="nntm-doc__sr"><?php esc_html_e( 'Tối', 'nntm' ); ?></span></button>
			</div>

			<p class="nntm-doc__panel-head"><?php esc_html_e( 'Cách xem', 'nntm' ); ?></p>
			<div class="nntm-doc__panel-row">
				<button type="button" class="nntm-doc__step is-active" data-nntm-doc="xem-lat"><?php esc_html_e( "Lật", "nntm" ); ?></button>
				<button type="button" class="nntm-doc__step" data-nntm-doc="xem-cuon"><?php esc_html_e( "Cuộn", "nntm" ); ?></button>
			</div>
		</div>

		<?php if ( $nntm_nhac ) : ?>
			<section class="nntm-doc__music" id="nntm-doc-nhac" hidden aria-label="<?php esc_attr_e( 'Nhạc nền', 'nntm' ); ?>">
				<div class="nntm-doc__music-head">
					<p><?php esc_html_e( 'Nhạc nền', 'nntm' ); ?></p>
					<button type="button" class="nntm-doc__music-close" data-nntm-doc="nhac-dong" title="<?php esc_attr_e( 'Đóng danh sách nhạc', 'nntm' ); ?>">
						<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M6 6l12 12M18 6L6 18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
						<span class="nntm-doc__sr"><?php esc_html_e( 'Đóng danh sách nhạc', 'nntm' ); ?></span>
					</button>
				</div>

				<audio data-nntm-doc="nhac-audio" preload="metadata" src="<?php echo esc_url( $nntm_nhac[0]['url'] ); ?>"></audio>

				<?php
				/*
				 * Đĩa nhạc quay khi đang phát. Thuần CSS, ăn theo class .is-playing
				 * mà trình phát đã gắn lên panel — không cần thêm dòng JS nào.
				 *
				 * Nhãn giữa đĩa là bìa sách nếu ấn phẩm có bìa; không có thì để
				 * vòng tròn màu nhấn, vẫn ra hình cái đĩa.
				 */
				?>
				<div class="nntm-doc__music-disc" aria-hidden="true">
					<span class="nntm-doc__music-disc-label"<?php echo $nntm_bia ? ' style="background-image:url(' . esc_url( $nntm_bia ) . ')"' : ''; ?>></span>
				</div>

				<p class="nntm-doc__music-now" data-nntm-doc="nhac-ten"><?php echo esc_html( $nntm_nhac[0]['title'] ); ?></p>

				<div class="nntm-doc__music-seek">
					<label class="nntm-doc__music-bar">
						<span class="nntm-doc__sr"><?php esc_html_e( 'Kéo để nghe từ chỗ khác trong bài', 'nntm' ); ?></span>
						<input type="range" min="0" max="1000" step="1" value="0" data-nntm-doc="nhac-tua" />
					</label>
					<span class="nntm-doc__music-time">
						<span data-nntm-doc="nhac-da-nghe">0:00</span>
						<span aria-hidden="true">/</span>
						<span data-nntm-doc="nhac-dai">0:00</span>
					</span>
				</div>

				<div class="nntm-doc__music-controls">
					<button type="button" data-nntm-doc="nhac-truoc" title="<?php esc_attr_e( 'Bài trước', 'nntm' ); ?>">
						<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M6 5v14M18 6l-9 6 9 6z" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
						<span class="nntm-doc__sr"><?php esc_html_e( 'Bài trước', 'nntm' ); ?></span>
					</button>
					<button type="button" class="nntm-doc__music-play" data-nntm-doc="nhac-phat" data-label-play="<?php esc_attr_e( 'Phát nhạc', 'nntm' ); ?>" data-label-pause="<?php esc_attr_e( 'Tạm dừng', 'nntm' ); ?>" aria-pressed="false" title="<?php esc_attr_e( 'Phát nhạc', 'nntm' ); ?>">
						<svg class="nntm-doc__music-icon--play" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M8 5l11 7-11 7z" fill="currentColor"/></svg>
						<svg class="nntm-doc__music-icon--pause" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M8 5h3v14H8zM14 5h3v14h-3z" fill="currentColor"/></svg>
						<span class="nntm-doc__sr"><?php esc_html_e( 'Phát hoặc tạm dừng nhạc', 'nntm' ); ?></span>
					</button>
					<button type="button" data-nntm-doc="nhac-sau" title="<?php esc_attr_e( 'Bài sau', 'nntm' ); ?>">
						<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M18 5v14M6 6l9 6-9 6z" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
						<span class="nntm-doc__sr"><?php esc_html_e( 'Bài sau', 'nntm' ); ?></span>
					</button>
				</div>

				<div class="nntm-doc__music-volume">
					<button type="button" class="nntm-doc__music-mute" data-nntm-doc="nhac-tat-tieng" data-label-tat="<?php esc_attr_e( 'Tắt tiếng', 'nntm' ); ?>" data-label-mo="<?php esc_attr_e( 'Bật tiếng', 'nntm' ); ?>" aria-pressed="false" title="<?php esc_attr_e( 'Tắt tiếng', 'nntm' ); ?>">
						<svg class="nntm-doc__music-icon--loa" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M4 9.5h3.2L12 5.5v13l-4.8-4H4z" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/><path d="M16 9.2a4 4 0 010 5.6" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg>
						<svg class="nntm-doc__music-icon--im" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M4 9.5h3.2L12 5.5v13l-4.8-4H4z" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"/><path d="M16 9.5l5 5M21 9.5l-5 5" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/></svg>
						<span class="nntm-doc__sr"><?php esc_html_e( 'Tắt hoặc bật tiếng nhạc nền', 'nntm' ); ?></span>
					</button>
					<label class="nntm-doc__music-bar">
						<span class="nntm-doc__sr"><?php esc_html_e( 'Âm lượng nhạc nền', 'nntm' ); ?></span>
						<input type="range" min="0" max="100" step="1" value="70" data-nntm-doc="nhac-am-luong" />
					</label>
				</div>

				<ol class="nntm-doc__music-list">
					<?php foreach ( $nntm_nhac as $nntm_so_nhac => $nntm_bai_nhac ) : ?>
						<li>
							<button type="button" data-nntm-doc-nhac-bai="<?php echo esc_attr( (string) $nntm_so_nhac ); ?>" data-nntm-doc-nhac-url="<?php echo esc_url( $nntm_bai_nhac['url'] ); ?>" data-nntm-doc-nhac-ten="<?php echo esc_attr( $nntm_bai_nhac['title'] ); ?>"<?php echo 0 === $nntm_so_nhac ? ' class="is-active" aria-current="true"' : ''; ?>>
								<span class="nntm-doc__music-number"><?php echo esc_html( str_pad( (string) ( $nntm_so_nhac + 1 ), 2, '0', STR_PAD_LEFT ) ); ?></span>
								<span><?php echo esc_html( $nntm_bai_nhac['title'] ); ?></span>
								<?php
								/*
								 * Tệp tải lên bằng FTP thì WordPress không có ID3 để biết
								 * thời lượng. Để ô trống chứ không in "0:00": nghe tới bài
								 * đó là JS điền số thật vào.
								 */
								?>
								<span class="nntm-doc__music-len" data-nntm-doc-nhac-dai><?php echo esc_html( isset( $nntm_bai_nhac['length'] ) ? (string) $nntm_bai_nhac['length'] : '' ); ?></span>
							</button>
						</li>
					<?php endforeach; ?>
				</ol>
			</section>
		<?php endif; ?>
	</div>

	<footer class="nntm-doc__foot">
		<span class="nntm-doc__chapter" data-nntm-doc="chuong">&nbsp;</span>
		<div class="nntm-doc__page-jump" role="group" aria-label="<?php esc_attr_e( 'Chuyển tới trang', 'nntm' ); ?>">
			<label for="nntm-doc-page-input"><?php esc_html_e( 'Trang', 'nntm' ); ?></label>
			<input
				type="text"
				id="nntm-doc-page-input"
				min="1"
				max="1"
				step="1"
				value="1"
				inputmode="numeric"
				data-nntm-doc="nhap-trang"
				aria-label="<?php esc_attr_e( 'Nhập số trang muốn tới', 'nntm' ); ?>"
			/>
			<span aria-hidden="true">/</span>
			<output data-nntm-doc="tong-trang">1</output>
			<button type="button" data-nntm-doc="toi-trang"><?php esc_html_e( 'Đi', 'nntm' ); ?></button>
		</div>
		<label class="nntm-doc__slider">
			<span class="nntm-doc__sr"><?php esc_html_e( 'Kéo để chuyển trang', 'nntm' ); ?></span>
			<input type="range" min="1" max="1" value="1" data-nntm-doc="thanh-truot" />
		</label>
		<span class="nntm-doc__percent" data-nntm-doc="phan-tram">0%</span>
	</footer>

	<?php
	 
	if ( is_user_logged_in() ) :
		?>
		<div class="nntm-doc__watermark" aria-hidden="true"><span class="nntm-doc__watermark-in" data-nntm-doc="watermark"></span></div>
	<?php endif; ?>

</div>

<?php wp_footer(); ?>
</body>
</html>
