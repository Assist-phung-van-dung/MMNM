<?php
/**
 * Màn chờ tải trang (preloader) — bốn hiệu ứng, mỗi lần tải bốc random một cái.
 *
 * Ghép 20/08/2026 từ bản anh Úy chọn:
 * C:\AI4\mmtn\spiritual-preloader-all-effects-mouse.html
 *
 * Ba phần:
 *   1. Script inline in ra trong <head> — bốc hiệu ứng và gắn `is-loading`
 *      TRƯỚC KHI trang vẽ khung đầu tiên.
 *   2. Markup lớp phủ, in ra ở wp_body_open (phần tử đầu tiên trong <body>,
 *      NGOÀI .nntm-site-frame — khung đó có `overflow-x: clip` nên lớp phủ
 *      nằm trong sẽ bị cắt, và lúc mở trang khung mờ đi thì lớp phủ mờ theo).
 *   3. Nạp CSS/JS — xem inc/enqueue.php.
 *
 * Tắt màn chờ ở một số trang:
 *     add_filter( 'nntm_preloader_enabled', function ( $bat ) {
 *         return ! is_page( 'lien-he' ) && $bat;
 *     } );
 */

defined( 'ABSPATH' ) || exit;

/**
 * Danh sách hiệu ứng kèm chữ hiện dưới hình.
 *
 * Khoá của mảng chính là giá trị `data-effect` mà CSS/JS đọc — đổi khoá thì
 * phải đổi cả assets/css/preloader.css và assets/js/preloader.js.
 *
 * @return array<string, array{title: string, subtitle: string}>
 */
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

/**
 * Có bật màn chờ cho lần xem trang này hay không.
 *
 * Trang quản trị và trình soạn thảo khối thì không — lớp phủ che cả giao
 * diện sửa bài.
 *
 * @return bool
 */
function nntm_preloader_enabled(): bool {
	if ( is_admin() || ( function_exists( 'wp_is_json_request' ) && wp_is_json_request() ) ) {
		return false;
	}

	return (bool) apply_filters( 'nntm_preloader_enabled', true );
}

/**
 * Script bốc hiệu ứng — in NGAY trong <head>, không defer/async.
 *
 * Hai thứ phải có mặt từ khung hình đầu tiên, nếu không sẽ nhá:
 *   - `is-loading` : thiếu thì nội dung trang hiện lên một nhịp rồi mới bị
 *                    lớp phủ che (FOUC).
 *   - `data-effect`: thiếu thì hiện hiệu ứng mặc định rồi mới đổi sang hiệu
 *                    ứng random.
 *
 * KHÔNG lặp lại hiệu ứng của lần tải trước: nhớ lần cuối vào sessionStorage
 * rồi bốc trong ba cái còn lại. Random thuần vẫn có 25% ra lại đúng cái vừa
 * xem, mà yêu cầu là "mỗi lần tải lại trang là một hiệu ứng khác".
 *
 * Dùng sessionStorage (không phải localStorage) vì phạm vi đúng bằng một
 * tab: reload bao nhiêu lần cũng không lặp, mà hai tab thì độc lập.
 */
function nntm_preloader_head_script(): void {
	if ( ! nntm_preloader_enabled() ) {
		return;
	}

	$keys = wp_json_encode( array_keys( nntm_preloader_effects() ) );

	/*
	 * In thẳng bằng echo chứ không qua wp_add_inline_script(): script đó bị
	 * gắn vào một handle và đưa xuống chân trang hoặc sau các file CSS/JS
	 * khác, còn đoạn này bắt buộc phải chạy trước khi trang vẽ.
	 */
	?>
	<script>
		(function () {
			var EFFECTS = <?php echo $keys; // phpcs:ignore WordPress.Security.EscapeOutput -- wp_json_encode. ?>;
			var KEY = 'nntm-preloader-last';
			var last = null;

			/*
			 * Chế độ riêng tư của một số trình duyệt làm storage ném lỗi ngay
			 * lúc đọc — bọc lại để màn chờ không bao giờ chết vì việc này.
			 */
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
				/* Không nhớ được thì thôi, vẫn random bình thường. */
			}

			var root = document.documentElement;

			root.setAttribute('data-effect', picked);
			root.className += ' is-loading';

			/*
			 * LƯỚI AN TOÀN ĐỘC LẬP. Chính đoạn script này khoá cuộn trang, mà
			 * người gỡ khoá lại là assets/js/preloader.js — nếu file đó 404,
			 * bị chặn hoặc lỗi cú pháp thì trang nằm dưới lớp phủ vĩnh viễn,
			 * không cuộn được. Nên tự đặt một mốc ở đây, không phụ thuộc file
			 * kia. Bình thường preloader.js mở trang từ giây thứ ~2, mốc này
			 * không bao giờ tới lượt.
			 */
			window.setTimeout(function () {
				root.classList.remove('is-loading');
				root.classList.remove('is-revealing');
			}, 8000);
		})();
	</script>
	<?php
}
add_action( 'wp_head', 'nntm_preloader_head_script', 1 );

/**
 * Markup lớp phủ.
 *
 * In CẢ BỐN khối chữ, CSS chỉ hiện khối khớp `data-effect`. Làm vậy để chữ
 * đi qua hàm dịch của WordPress được, không phải nhồi chuỗi tiếng Việt vào
 * JavaScript.
 */
function nntm_preloader_markup(): void {
	if ( ! nntm_preloader_enabled() ) {
		return;
	}

	$effects = nntm_preloader_effects();
	?>
	<div class="nntm-tai" aria-hidden="true">

		<?php /* 01 — HÀO QUANG */ ?>
		<div class="nntm-tai__hieu-ung nntm-tai__hieu-ung--halo">
			<span class="nntm-tai__halo-quang"></span>
			<span class="nntm-tai__halo-tam"></span>
			<span class="nntm-tai__halo-vong"></span>
			<span class="nntm-tai__halo-ky">✦</span>
		</div>

		<?php /* 02 — MANDALA */ ?>
		<div class="nntm-tai__hieu-ung nntm-tai__hieu-ung--mandala">
			<span class="nntm-tai__mandala">
				<span class="nntm-tai__mandala-hoa"></span>
				<span class="nntm-tai__mandala-tam">✦</span>
			</span>
		</div>

		<?php /* 03 — ÁNH TRĂNG */ ?>
		<div class="nntm-tai__hieu-ung nntm-tai__hieu-ung--moon">
			<span class="nntm-tai__troi"></span>
			<span class="nntm-tai__song"></span>
			<span class="nntm-tai__song nntm-tai__song--2"></span>
			<span class="nntm-tai__trang"></span>
		</div>

		<?php /* 04 — MẶT TRỜI */ ?>
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
