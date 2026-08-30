<?php
/**
 * Khung thanh toán: dựng HTML, nạp CSS/JS, và mở khi người đọc hết phần xem thử.
 *
 * Bố cục theo mẫu khách gửi: nền tối, hai cột — mã QR bên trái, thông tin đơn và
 * nút thanh toán bên phải, dải chân khung ở dưới.
 *
 * @package NNTM_PayOS
 */

defined( 'ABSPATH' ) || exit;

/**
 * Trang hiện tại có cần khung thanh toán không.
 *
 * Cần khi: đang ở trang đọc hoặc trang chi tiết của một ấn phẩm đang bán mà
 * người xem chưa mua.
 */
function nntm_payos_can_khung(): bool {
	if ( is_admin() || ! is_singular( 'nntm_publication' ) ) {
		return false;
	}

	$post = get_queried_object();

	if ( ! $post instanceof WP_Post || ! nntm_payos_dang_ban( $post ) ) {
		return false;
	}

	return ! ( function_exists( 'nntm_an_pham_can_access' ) && nntm_an_pham_can_access( $post ) );
}

/**
 * Nạp CSS/JS của khung thanh toán.
 */
function nntm_payos_nap_asset(): void {
	if ( ! nntm_payos_can_khung() ) {
		return;
	}

	$post = get_queried_object();
	$css  = NNTM_PAYOS_PATH . 'assets/css/thanh-toan.css';
	$js   = NNTM_PAYOS_PATH . 'assets/js/thanh-toan.js';

	wp_enqueue_style(
		'nntm-payos',
		NNTM_PAYOS_URL . 'assets/css/thanh-toan.css',
		array(),
		file_exists( $css ) ? (string) filemtime( $css ) : NNTM_PAYOS_VER
	);

	wp_enqueue_script(
		'nntm-payos',
		NNTM_PAYOS_URL . 'assets/js/thanh-toan.js',
		array(),
		file_exists( $js ) ? (string) filemtime( $js ) : NNTM_PAYOS_VER,
		true
	);

	/*
	 * KHÔNG đẩy khoá PayOS nào ra đây. Chỉ có ID ấn phẩm, giá đã định dạng để
	 * hiển thị, và mấy câu chữ.
	 */
	wp_localize_script(
		'nntm-payos',
		'nntmPayos',
		array(
			'restTaoDon'    => rest_url( NNTM_PAYOS_NS . '/tao-don' ),
			'restTrangThai' => rest_url( NNTM_PAYOS_NS . '/trang-thai' ),
			'nonce'         => wp_create_nonce( 'wp_rest' ),
			'pub'           => (int) $post->ID,
			'tenSach'       => get_the_title( $post ),
			'tien'          => nntm_payos_dinh_dang_tien( nntm_payos_gia( $post ) ),
			'dangNhap'      => is_user_logged_in(),
			'urlDangNhap'   => function_exists( 'nntm_login_url' ) ? nntm_login_url( (string) get_permalink( $post ) ) : wp_login_url( (string) get_permalink( $post ) ),
			'i18n'          => array(
				'dangTao'   => __( 'Đang tạo đơn…', 'nntm' ),
				'loiMang'   => __( 'Không kết nối được. Thử lại giúp tôi.', 'nntm' ),
				'dangCho'   => __( 'Đang chờ xác nhận từ ngân hàng…', 'nntm' ),
				'xong'      => __( 'Đã nhận thanh toán. Đang mở sách…', 'nntm' ),
				'canDangNhap' => __( 'Đăng nhập để mua ấn phẩm này.', 'nntm' ),
			),
		)
	);
}
add_action( 'wp_enqueue_scripts', 'nntm_payos_nap_asset', 40 );

/**
 * In khung thanh toán ra chân trang.
 */
function nntm_payos_ve_khung(): void {
	if ( ! nntm_payos_can_khung() ) {
		return;
	}

	$post = get_queried_object();
	$gia  = nntm_payos_gia( $post );
	?>
	<div class="nntm-tt" id="nntm-tt" data-nntm-tt hidden>
		<div class="nntm-tt__nen" data-nntm-tt-dong></div>

		<div class="nntm-tt__khung" role="dialog" aria-modal="true" aria-labelledby="nntm-tt-tieu-de">
			<button type="button" class="nntm-tt__dong" data-nntm-tt-dong aria-label="<?php esc_attr_e( 'Đóng', 'nntm' ); ?>">
				<span aria-hidden="true">&times;</span>
			</button>

			<header class="nntm-tt__dau">
				<h2 class="nntm-tt__tieu-de" id="nntm-tt-tieu-de"><?php esc_html_e( 'Thanh toán', 'nntm' ); ?></h2>
				<p class="nntm-tt__phu"><?php esc_html_e( 'Chọn phương thức thanh toán', 'nntm' ); ?></p>
			</header>

			<div class="nntm-tt__than">
				<section class="nntm-tt__cot nntm-tt__cot--qr">
					<h3 class="nntm-tt__cot-tieu-de"><?php esc_html_e( 'Quét mã để thanh toán', 'nntm' ); ?></h3>

					<div class="nntm-tt__qr" data-nntm-tt-qr>
						<div class="nntm-tt__qr-cho" data-nntm-tt-qr-cho><?php esc_html_e( 'Đang tạo mã…', 'nntm' ); ?></div>
					</div>

					<p class="nntm-tt__qr-chu">
						<?php esc_html_e( 'Mở ứng dụng ngân hàng và quét mã QR để thanh toán. Sách mở ra ngay khi ngân hàng báo nhận được tiền.', 'nntm' ); ?>
					</p>
				</section>

				<section class="nntm-tt__cot nntm-tt__cot--don">
					<h3 class="nntm-tt__cot-tieu-de"><?php esc_html_e( 'Nội dung đơn hàng', 'nntm' ); ?></h3>

					<dl class="nntm-tt__bang">
						<div class="nntm-tt__dong-tin">
							<dt><?php esc_html_e( 'Ấn phẩm', 'nntm' ); ?></dt>
							<dd><?php echo esc_html( get_the_title( $post ) ); ?></dd>
						</div>
						<div class="nntm-tt__dong-tin">
							<dt><?php esc_html_e( 'Số tiền', 'nntm' ); ?></dt>
							<dd class="nntm-tt__tien"><?php echo esc_html( nntm_payos_dinh_dang_tien( $gia ) ); ?></dd>
						</div>
						<div class="nntm-tt__dong-tin" data-nntm-tt-dong-ma hidden>
							<dt><?php esc_html_e( 'Mã đơn', 'nntm' ); ?></dt>
							<dd><code data-nntm-tt-ma></code></dd>
						</div>
					</dl>

					<p class="nntm-tt__trang-thai" data-nntm-tt-trang-thai role="status" aria-live="polite"></p>

					<a class="nntm-tt__nut" data-nntm-tt-mo href="#" target="_blank" rel="noopener" hidden>
						<?php esc_html_e( 'Mở trang thanh toán', 'nntm' ); ?>
					</a>

					<button type="button" class="nntm-tt__nut nntm-tt__nut--lai" data-nntm-tt-thu-lai hidden>
						<?php esc_html_e( 'Thử lại', 'nntm' ); ?>
					</button>
				</section>
			</div>

			<footer class="nntm-tt__chan">
				<?php esc_html_e( 'Mua một lần, đọc mãi mãi trên tài khoản này.', 'nntm' ); ?>
			</footer>
		</div>
	</div>

	<?php
	/*
	 * Trên TRANG ĐỌC, theme đã gỡ hết giao diện chung để trình đọc chiếm trọn
	 * màn hình — không còn chỗ nào đặt nút mua. Thả một dải nổi ở đáy để người
	 * đang đọc thử luôn có đường mua, không phải quay ngược ra trang chi tiết.
	 */
	if ( function_exists( 'nntm_dang_o_trang_doc' ) && nntm_dang_o_trang_doc() ) :
		?>
		<div class="nntm-tt-dai">
			<span class="nntm-tt-dai__chu">
				<?php esc_html_e( 'Bạn đang đọc thử vài trang đầu.', 'nntm' ); ?>
			</span>
			<button type="button" class="nntm-tt-dai__nut" data-nntm-tt-mua>
				<?php
				printf(
					/* translators: %s: giá bán đã định dạng. */
					esc_html__( 'Mua trọn cuốn — %s', 'nntm' ),
					esc_html( nntm_payos_dinh_dang_tien( $gia ) )
				);
				?>
			</button>
		</div>
		<?php
	endif;
}
add_action( 'wp_footer', 'nntm_payos_ve_khung' );
