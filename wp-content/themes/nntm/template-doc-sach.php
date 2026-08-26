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

?><!DOCTYPE html>
<html <?php language_attributes(); ?>>
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

		<aside class="nntm-doc__side">
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
				<button type="button" class="nntm-doc__swatch" data-nntm-doc="nen" data-nen="sang"><span class="nntm-doc__sr"><?php esc_html_e( 'Sáng', 'nntm' ); ?></span></button>
				<button type="button" class="nntm-doc__swatch" data-nntm-doc="nen" data-nen="nga"><span class="nntm-doc__sr"><?php esc_html_e( 'Vàng ngà', 'nntm' ); ?></span></button>
				<button type="button" class="nntm-doc__swatch is-active" data-nntm-doc="nen" data-nen="toi"><span class="nntm-doc__sr"><?php esc_html_e( 'Tối', 'nntm' ); ?></span></button>
			</div>

			<p class="nntm-doc__panel-head"><?php esc_html_e( 'Cách xem', 'nntm' ); ?></p>
			<div class="nntm-doc__panel-row">
				<button type="button" class="nntm-doc__step is-active" data-nntm-doc="xem-lat"><?php esc_html_e( "Lật", "nntm" ); ?></button>
				<button type="button" class="nntm-doc__step" data-nntm-doc="xem-cuon"><?php esc_html_e( "Cuộn", "nntm" ); ?></button>
			</div>
		</div>
	</div>

	<footer class="nntm-doc__foot">
		<span class="nntm-doc__chapter" data-nntm-doc="chuong">&nbsp;</span>
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
