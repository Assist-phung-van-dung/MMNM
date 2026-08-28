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

	$keys   = wp_json_encode( array_keys( nntm_preloader_effects() ) );
	$quotes = wp_json_encode( array_values( nntm_preloader_quotes() ), JSON_UNESCAPED_UNICODE );

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
			root.className += ' is-loading';

			window.setTimeout(function () {
				root.classList.remove('is-loading');
				root.classList.remove('is-revealing');
			}, 8000);
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
