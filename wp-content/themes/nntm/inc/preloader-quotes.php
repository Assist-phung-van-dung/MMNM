<?php
/**
 * Trích dẫn ngẫu nhiên cho màn hình chờ (PROMPT 02).
 *
 * - Danh sách quote do admin quản lý (Giao diện -> Trích dẫn màn hình chờ).
 * - Chưa cấu hình gì thì dùng danh sách mặc định bên dưới.
 * - Quote là plain text: lọc sạch khi lưu, escape khi in ra, chọn ngẫu nhiên
 *   phía trình duyệt nên không phát sinh thêm request nào.
 */

defined( 'ABSPATH' ) || exit;

const NNTM_PRELOADER_QUOTES_OPTION = 'nntm_preloader_quotes';

/** Số quote tối đa và độ dài tối đa mỗi quote. */
const NNTM_PRELOADER_QUOTE_MAX_SO   = 200;
const NNTM_PRELOADER_QUOTE_MAX_DAI  = 160;

/**
 * Danh sách quote mặc định — dùng khi admin chưa cấu hình gì.
 */
function nntm_preloader_default_quotes(): array {
	return array(
		'Thân này là vô thường',
		'An lạc trong từng hơi thở',
		'Thân này là người lạ',
		'Anh Thấy chăng?',
		'Tâm an thì cảnh an',
		'Buông một niệm, nhẹ một đời',
		'Đi chậm lại để thấy rõ hơn',
		'Hơi thở vào, biết mình đang sống',
		'Hơi thở ra, biết mình đang buông',
		'Không có gì mất đi, chỉ là đổi hình',
		'Lặng nghe, sẽ nghe được rất nhiều',
		'Nơi nào có chánh niệm, nơi đó có bình an',
		'Khổ đau cũng là một người thầy',
		'Việc gì đến, cứ để nó đến',
		'Việc gì đi, cứ để nó đi',
		'Một ngày mới, một cơ hội mới',
		'Sống chậm để thương nhiều hơn',
		'Nhìn sâu để hiểu, hiểu rồi thì thương',
		'Bình an không ở đâu xa',
		'Trở về với chính mình',
		'Ngồi yên cũng là một việc lớn',
		'Đừng vội, hoa nở đúng mùa của hoa',
		'Cái gì cũng qua, kể cả hôm nay',
		'Tỉnh thức trong từng bước chân',
		'Thở vào cho sâu, thở ra cho nhẹ',
		'Ta là những gì ta nghĩ',
		'Giận là tự phạt mình vì lỗi người khác',
		'Cho đi là còn mãi',
		'Ít muốn thì biết đủ',
		'Biết đủ là giàu',
		'Không tranh với ai, không hơn thua với đời',
		'Nước lặng thì trăng hiện',
		'Gió qua rồi, cây vẫn đứng đó',
		'Mỗi ngày một chút, đủ thành đường dài',
		'Người hiền như đất, chở được muôn loài',
		'Nói ít, nghe nhiều, hiểu sâu',
		'Đóa sen mọc lên từ bùn',
		'Có sinh thì có diệt, đó là lẽ thường',
		'Nhìn mây bay, thấy lòng mình rộng',
		'Chấp một chữ, khổ một đời',
		'Tâm rộng thì việc nhỏ lại',
		'Không có con đường dẫn tới an lạc, an lạc chính là con đường',
		'Đứng dậy nhẹ nhàng như chưa từng ngã',
		'Trong tĩnh lặng có câu trả lời',
		'Tha thứ là món quà tự tặng mình',
		'Điều quý nhất là giây phút này',
		'Học cách ở yên với chính mình',
		'Một nụ cười là một lời kinh',
		'Đi qua bão giông mới quý ngày nắng',
		'Sống sao cho nhẹ, đi sao cho thanh',
		'Đường xa vạn dặm bắt đầu từ một bước',
		'Trăng vẫn tròn dù mây có che',
	);
}

/**
 * Lọc sạch một danh sách quote thô (mảng hoặc chuỗi mỗi dòng một quote).
 *
 * Bỏ hết thẻ HTML/script, bỏ dòng trống, bỏ trùng, giới hạn số lượng và độ dài.
 */
function nntm_preloader_sanitize_quotes( $raw ): array {
	if ( is_string( $raw ) ) {
		$raw = preg_split( '/[\r\n]+/', $raw );
	}

	if ( ! is_array( $raw ) ) {
		return array();
	}

	$sach = array();
	$da   = array();

	foreach ( $raw as $dong ) {
		if ( ! is_string( $dong ) ) {
			continue;
		}

		// wp_strip_all_tags bỏ luôn nội dung <script>/<style>; sanitize_text_field lo phần còn lại.
		$quote = sanitize_text_field( wp_strip_all_tags( $dong, true ) );
		$quote = trim( $quote );

		if ( '' === $quote ) {
			continue;
		}

		if ( mb_strlen( $quote ) > NNTM_PRELOADER_QUOTE_MAX_DAI ) {
			$quote = trim( mb_substr( $quote, 0, NNTM_PRELOADER_QUOTE_MAX_DAI ) );
		}

		$khoa = mb_strtolower( $quote );
		if ( isset( $da[ $khoa ] ) ) {
			continue;
		}

		$da[ $khoa ] = true;
		$sach[]      = $quote;

		if ( count( $sach ) >= NNTM_PRELOADER_QUOTE_MAX_SO ) {
			break;
		}
	}

	return $sach;
}

/**
 * Danh sách quote đang dùng: của admin nếu có, không thì lấy mặc định.
 */
function nntm_preloader_quotes(): array {
	$luu = get_option( NNTM_PRELOADER_QUOTES_OPTION, array() );
	$luu = nntm_preloader_sanitize_quotes( $luu );

	if ( empty( $luu ) ) {
		$luu = nntm_preloader_default_quotes();
	}

	return (array) apply_filters( 'nntm_preloader_quotes', $luu );
}

/*
 * ---------------------------------------------------------------------------
 * Trang quản trị
 * ---------------------------------------------------------------------------
 */

function nntm_preloader_quotes_register_setting(): void {
	register_setting(
		'nntm_preloader_quotes_group',
		NNTM_PRELOADER_QUOTES_OPTION,
		array(
			'type'              => 'array',
			'sanitize_callback' => 'nntm_preloader_sanitize_quotes',
			'default'           => array(),
			'show_in_rest'      => false,
		)
	);
}
add_action( 'admin_init', 'nntm_preloader_quotes_register_setting' );

function nntm_preloader_quotes_admin_menu(): void {
	add_theme_page(
		__( 'Trích dẫn màn hình chờ', 'nntm' ),
		__( 'Trích dẫn màn hình chờ', 'nntm' ),
		'manage_options',
		'nntm-preloader-quotes',
		'nntm_preloader_quotes_admin_page'
	);
}
add_action( 'admin_menu', 'nntm_preloader_quotes_admin_menu' );

function nntm_preloader_quotes_admin_page(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$luu     = nntm_preloader_sanitize_quotes( get_option( NNTM_PRELOADER_QUOTES_OPTION, array() ) );
	$dang_ap = nntm_preloader_quotes();
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Trích dẫn màn hình chờ', 'nntm' ); ?></h1>

		<p>
			<?php esc_html_e( 'Mỗi dòng là một câu trích dẫn. Mỗi lần tải trang, website chọn ngẫu nhiên một câu và tránh lặp lại đúng câu vừa hiện ở lần tải trước.', 'nntm' ); ?>
		</p>
		<p>
			<?php
			printf(
				/* translators: 1: số quote tối đa, 2: số ký tự tối đa mỗi quote. */
				esc_html__( 'Tối đa %1$d câu, mỗi câu tối đa %2$d ký tự. Chỉ nhận chữ thường (plain text) — thẻ HTML và mã script sẽ bị loại bỏ khi lưu.', 'nntm' ),
				(int) NNTM_PRELOADER_QUOTE_MAX_SO,
				(int) NNTM_PRELOADER_QUOTE_MAX_DAI
			);
			?>
		</p>
		<p>
			<?php
			if ( empty( $luu ) ) {
				printf(
					/* translators: %d: số quote mặc định. */
					esc_html__( 'Hiện đang dùng danh sách mặc định (%d câu). Để trống ô bên dưới thì vẫn tiếp tục dùng danh sách mặc định.', 'nntm' ),
					count( $dang_ap )
				);
			} else {
				printf(
					/* translators: %d: số quote đang lưu. */
					esc_html__( 'Đang dùng danh sách riêng của bạn (%d câu).', 'nntm' ),
					count( $luu )
				);
			}
			?>
		</p>

		<form method="post" action="options.php">
			<?php settings_fields( 'nntm_preloader_quotes_group' ); ?>

			<textarea
				name="<?php echo esc_attr( NNTM_PRELOADER_QUOTES_OPTION ); ?>"
				id="nntm-preloader-quotes"
				rows="24"
				class="large-text code"
				placeholder="<?php esc_attr_e( 'Thân này là vô thường', 'nntm' ); ?>"
			><?php echo esc_textarea( implode( "\n", $luu ) ); ?></textarea>

			<?php submit_button(); ?>
		</form>

		<h2><?php esc_html_e( 'Danh sách mặc định', 'nntm' ); ?></h2>
		<p><?php esc_html_e( 'Dùng khi ô trên để trống. Có thể sao chép xuống ô trên rồi sửa lại theo ý.', 'nntm' ); ?></p>
		<textarea rows="10" class="large-text code" readonly><?php echo esc_textarea( implode( "\n", nntm_preloader_default_quotes() ) ); ?></textarea>
	</div>
	<?php
}
