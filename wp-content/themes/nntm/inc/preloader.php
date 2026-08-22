<?php

defined( 'ABSPATH' ) || exit;

function nntm_preloader_effects(): array {
	return array(
		'halo'    => array(
			'title'    => __( 'Tịnh Tâm', 'nntm' ),
			'subtitle' => __( 'An nhiên trong từng khoảnh khắc', 'nntm' ),
		),
		'mandala' => array(
			'title'    => __( 'Tĩnh Niệm', 'nntm' ),
			'subtitle' => __( 'Tĩnh lặng để nhìn sâu hơn', 'nntm' ),
		),
		'moon'    => array(
			'title'    => __( 'Nguyệt Tĩnh', 'nntm' ),
			'subtitle' => __( 'Ánh sáng dẫn lối trong tĩnh lặng', 'nntm' ),
		),
		'sun'     => array(
			'title'    => __( 'Nhật Quang', 'nntm' ),
			'subtitle' => __( 'Khai mở nguồn sinh khí an lành', 'nntm' ),
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

	$keys = wp_json_encode( array_keys( nntm_preloader_effects() ) );

	?>
	<script>
		(function () {
			var EFFECTS = <?php echo $keys;  ?>;
			var KEY = 'nntm-preloader-last';
			var last = null;

			try {
				last = window.sessionStorage.getItem(KEY);
			} catch (error) {
				last = null;
			}

			var pool = EFFECTS.filter(function (name) {
				return name !== last;
			});

			var picked = pool[Math.floor(Math.random() * pool.length)];

			try {
				window.sessionStorage.setItem(KEY, picked);
			} catch (error) {
			}

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
				<p class="nntm-tai__phu"><?php echo esc_html( $copy['subtitle'] ); ?></p>
				<span class="nntm-tai__vach"></span>
			</div>
		<?php endforeach; ?>

	</div>
	<?php
}
add_action( 'wp_body_open', 'nntm_preloader_markup', 1 );
