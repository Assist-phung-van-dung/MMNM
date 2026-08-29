<?php

defined( 'ABSPATH' ) || exit;

function nntm_preloader_effects(): array {
	return array(
		'halo'    => array(
			'title' => __( 'Tịnh Tâm', 'nntm' ),
		),
		'mandala' => array(
			'title' => __( 'Tĩnh Niệm', 'nntm' ),
		),
		'moon'    => array(
			'title' => __( 'Nguyệt Tĩnh', 'nntm' ),
		),
		'sun'     => array(
			'title' => __( 'Nhật Quang', 'nntm' ),
		),
		/*
		 * Hào Quang: logo của website đặt giữa, có vầng sáng toả và vòng quay
		 * quanh. Ảnh do admin chọn ở Giao diện -> Trích dẫn màn hình chờ. Chưa
		 * chọn ảnh thì hiệu ứng này tự bị loại khỏi vòng ngẫu nhiên, xem
		 * nntm_preloader_hieu_ung_bat().
		 */
		'logo'    => array(
			'title' => __( 'Hào Quang', 'nntm' ),
		),
	);
}

function nntm_preloader_enabled(): bool {
	if ( is_admin() || ( function_exists( 'wp_is_json_request' ) && wp_is_json_request() ) ) {
		return false;
	}

	return (bool) apply_filters( 'nntm_preloader_enabled', true );
}

function nntm_preloader_head_script(): void {
	if ( ! nntm_preloader_enabled() ) {
		return;
	}

	$keys   = wp_json_encode( array_values( nntm_preloader_hieu_ung_bat() ) );
	$quotes = wp_json_encode( array_values( nntm_preloader_quotes() ), JSON_UNESCAPED_UNICODE );

	$giay_toi_thieu = nntm_preloader_giay();

	/* Lưới an toàn = thời gian tối thiểu + 8 giây dự phòng cho lúc mạng chậm. */
	$luoi_cuoi = (int) round( $giay_toi_thieu * 1000 ) + 8000;

	?>
	<script>
		(function () {
			var EFFECTS = <?php echo $keys;  ?>;
			var QUOTES = <?php echo $quotes;  ?>;
			var KEY = 'nntm-preloader-last';
			var KEY_QUOTE = 'nntm-preloader-last-quote';

			/* Bốc ngẫu nhiên một phần tử, tránh đúng phần tử của lần tải trước. */
			function boc( danhSach, khoaNho ) {
				if ( ! danhSach || ! danhSach.length ) {
					return null;
				}

				var truoc = null;

				try {
					truoc = window.sessionStorage.getItem( khoaNho );
				} catch ( error ) {
					truoc = null;
				}

				var con = danhSach.filter( function ( item ) {
					return String( item ) !== truoc;
				} );

				if ( ! con.length ) {
					con = danhSach;
				}

				var chon = con[ Math.floor( Math.random() * con.length ) ];

				try {
					window.sessionStorage.setItem( khoaNho, String( chon ) );
				} catch ( error ) {
				}

				return chon;
			}

			var picked = boc( EFFECTS, KEY );

			window.NNTM_TAI_QUOTE = boc( QUOTES, KEY_QUOTE );

			var root = document.documentElement;

			root.setAttribute('data-effect', picked);

			/*
			 * Số giây TỐI THIỂU do admin đặt, đưa xuống đây để preloader.js đọc.
			 * Đặt ngay trong <head> chứ không chờ localize: script này chạy trước
			 * mọi thứ, còn preloader.js nằm cuối trang.
			 */
			root.setAttribute('data-preload-min', '<?php echo esc_js( (string) $giay_toi_thieu ); ?>');

			root.className += ' is-loading';

			/*
			 * Lưới an toàn cuối cùng: nếu preloader.js không chạy được (lỗi JS,
			 * chặn script), màn hình chờ vẫn phải tự mở ra. Phải tính từ số giây
			 * admin đặt — ghim cứng 8000 thì admin đặt 10 giây là bị cắt ngang.
			 */
			window.setTimeout(function () {
				root.classList.remove('is-loading');
				root.classList.remove('is-revealing');
			}, <?php echo (int) $luoi_cuoi;  ?>);
		})();
	</script>
	<?php
}
add_action( 'wp_head', 'nntm_preloader_head_script', 1 );

function nntm_preloader_markup(): void {
	if ( ! nntm_preloader_enabled() ) {
		return;
	}

	$effects = nntm_preloader_effects();



	$quotes    = nntm_preloader_quotes();
	$quote_dau = $quotes ? (string) $quotes[ wp_rand( 0, count( $quotes ) - 1 ) ] : '';
	?>
	<div class="nntm-tai" aria-hidden="true">

		<?php   ?>
		<div class="nntm-tai__hieu-ung nntm-tai__hieu-ung--halo">
			<span class="nntm-tai__halo-quang"></span>
			<span class="nntm-tai__halo-tam"></span>
			<span class="nntm-tai__halo-vong"></span>
			<span class="nntm-tai__halo-ky">✦</span>
		</div>

		<?php   ?>
		<div class="nntm-tai__hieu-ung nntm-tai__hieu-ung--mandala">
			<span class="nntm-tai__mandala">
				<span class="nntm-tai__mandala-hoa"></span>
				<span class="nntm-tai__mandala-tam">✦</span>
			</span>
		</div>

		<?php   ?>
		<div class="nntm-tai__hieu-ung nntm-tai__hieu-ung--moon">
			<span class="nntm-tai__troi"></span>
			<span class="nntm-tai__song"></span>
			<span class="nntm-tai__song nntm-tai__song--2"></span>
			<span class="nntm-tai__trang"></span>
		</div>

		<?php
		/*
		 * Hào Quang — logo của website. Chỉ dựng khi admin đã chọn ảnh; chưa có
		 * ảnh thì hiệu ứng cũng đã bị loại khỏi vòng ngẫu nhiên nên không bao giờ
		 * được chọn tới.
		 */
		$nntm_tai_logo_id = nntm_preloader_logo_id();

		if ( $nntm_tai_logo_id ) :
			?>
			<div class="nntm-tai__hieu-ung nntm-tai__hieu-ung--logo">
				<span class="nntm-tai__logo-quang"></span>
				<span class="nntm-tai__logo-toa"></span>
				<span class="nntm-tai__logo-vong"></span>
				<span class="nntm-tai__logo-vong nntm-tai__logo-vong--2"></span>
				<?php
				/*
				 * alt rỗng có chủ ý: cả màn hình chờ đã aria-hidden, logo ở đây
				 * chỉ là trang trí, đọc tên nó lên chẳng giúp gì cho người dùng
				 * trình đọc màn hình.
				 */
				echo wp_get_attachment_image(
					$nntm_tai_logo_id,
					'medium',
					false,
					array(
						'class'    => 'nntm-tai__logo',
						'alt'      => '',
						'decoding' => 'async',
						'loading'  => 'eager',
					)
				);
				?>
			</div>
			<?php
		endif;
		?>

		<?php   ?>
		<div class="nntm-tai__hieu-ung nntm-tai__hieu-ung--sun">
			<span class="nntm-tai__nhat-quang"></span>
			<span class="nntm-tai__nhat-tia"></span>
			<span class="nntm-tai__nhat-vong"></span>
			<span class="nntm-tai__nhat"></span>
		</div>

		<?php foreach ( $effects as $key => $copy ) : ?>
			<div class="nntm-tai__copy nntm-tai__copy--<?php echo esc_attr( $key ); ?>">
				<p class="nntm-tai__tieu-de"><?php echo esc_html( $copy['title'] ); ?></p>
				<p class="nntm-tai__phu" data-nntm-quote><?php echo esc_html( $quote_dau ); ?></p>
				<span class="nntm-tai__vach"></span>
			</div>
		<?php endforeach; ?>

	</div>
	<?php


	?>
	<script>
		(function () {
			var quote = window.NNTM_TAI_QUOTE;

			if ( ! quote ) {
				return;
			}

			var o = document.querySelectorAll( '[data-nntm-quote]' );

			for ( var i = 0; i < o.length; i++ ) {

				o[ i ].textContent = quote;
			}
		})();
	</script>
	<?php
}
add_action( 'wp_body_open', 'nntm_preloader_markup', 1 );
