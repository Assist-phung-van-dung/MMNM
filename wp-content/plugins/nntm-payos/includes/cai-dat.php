<?php
/**
 * Khoá PayOS: nhập trong admin, lưu ĐÃ MÃ HOÁ.
 *
 * VÌ SAO KHÔNG LƯU TRẦN: mọi bản xuất cơ sở dữ liệu (sao lưu, đưa cho lập trình
 * viên, chuyển máy chủ) đều kéo theo bảng wp_options. Kho GitHub của dự án lại
 * đang CÔNG KHAI và từng có bản xuất CSDL trong lịch sử. Khoá trần nằm trong
 * wp_options là khoá đi theo tất cả những đường đó.
 *
 * CÁCH LÀM: khoá được mã hoá bằng AES-256-GCM, chìa dẫn xuất từ muối bảo mật
 * trong wp-config.php. Lấy được CSDL mà không có wp-config.php thì đọc ra chuỗi
 * vô nghĩa. Lấy được cả hai thì vẫn đọc được — đây là mức trần khả thi cho một
 * site WordPress, không phải bảo mật tuyệt đối.
 *
 * ĐIỀU KIỆN KÈM THEO: muối của WordPress phải còn bí mật. Dự án từng để lộ 8
 * khoá/muối trong lịch sử kho công khai; chưa đổi muối thì lớp mã hoá này vô
 * nghĩa. Xem ghi chú ở nntm_payos_bi_mat().
 *
 * BA ĐIỀU KHÔNG BAO GIỜ LÀM:
 *   - in nguyên khoá ra HTML (kể cả trong ô type="password" — xem mã nguồn
 *     trang là đọc được);
 *   - đẩy khoá ra JavaScript;
 *   - ghi khoá vào nhật ký.
 *
 * Hằng trong wp-config.php vẫn được ưu tiên hơn khoá nhập trong admin, để máy
 * chủ thật khoá cứng được nếu muốn.
 *
 * @package NNTM_PayOS
 */

defined( 'ABSPATH' ) || exit;

/**
 * Tuỳ chọn giữ ba khoá ĐÃ MÃ HOÁ.
 */
const NNTM_PAYOS_OPTION_KHOA = 'nntm_payos_khoa';

/**
 * Tên ba khoá.
 *
 * @return string[]
 */
function nntm_payos_ten_khoa(): array {
	return array( 'client_id', 'api_key', 'checksum_key' );
}

/**
 * Khoá dùng để mã hoá, dẫn xuất từ bí mật của chính site.
 *
 * Ưu tiên hằng NNTM_PAYOS_SECRET nếu quản trị khai riêng; không có thì lấy tổ
 * hợp muối của WordPress trong wp-config.php.
 *
 * ĐIỀU KIỆN ĐỂ CÁCH NÀY CÓ NGHĨA: muối của WordPress phải còn bí mật. Dự án này
 * từng để lộ 8 khoá/muối trong lịch sử kho GitHub công khai — nếu chưa đổi muối
 * thì kẻ có bản sao lưu CSDL cũng có luôn khoá mã hoá. Đổi muối trước.
 */
function nntm_payos_bi_mat(): string {
	if ( defined( 'NNTM_PAYOS_SECRET' ) && '' !== (string) NNTM_PAYOS_SECRET ) {
		return (string) NNTM_PAYOS_SECRET;
	}

	$phan = '';

	foreach ( array( 'AUTH_KEY', 'SECURE_AUTH_KEY', 'LOGGED_IN_KEY', 'NONCE_KEY' ) as $h ) {
		if ( defined( $h ) ) {
			$phan .= (string) constant( $h );
		}
	}

	return '' !== $phan ? $phan : 'nntm-payos-khong-co-muoi';
}

/**
 * Mã hoá một chuỗi để cất vào CSDL.
 *
 * Dùng AES-256-GCM: vừa giấu nội dung vừa có thẻ xác thực, nên ai sửa một byte
 * trong CSDL là lúc giải mã phát hiện ra ngay, không âm thầm ra chuỗi rác.
 *
 * @param string $chuoi Chuỗi gốc.
 */
function nntm_payos_ma_hoa( string $chuoi ): string {
	if ( '' === $chuoi ) {
		return '';
	}

	$khoa = hash( 'sha256', nntm_payos_bi_mat(), true );
	$iv   = random_bytes( 12 );
	$the  = '';

	$ma = openssl_encrypt( $chuoi, 'aes-256-gcm', $khoa, OPENSSL_RAW_DATA, $iv, $the );

	if ( false === $ma ) {
		return '';
	}

	return base64_encode( $iv . $the . $ma );
}

/**
 * Giải mã chuỗi lấy từ CSDL.
 *
 * @param string $cat Chuỗi đã mã hoá.
 */
function nntm_payos_giai_ma( string $cat ): string {
	if ( '' === $cat ) {
		return '';
	}

	$tho = base64_decode( $cat, true );

	if ( false === $tho || strlen( $tho ) < 29 ) {
		return '';
	}

	$khoa = hash( 'sha256', nntm_payos_bi_mat(), true );
	$iv   = substr( $tho, 0, 12 );
	$the  = substr( $tho, 12, 16 );
	$ma   = substr( $tho, 28 );

	$ra = openssl_decrypt( $ma, 'aes-256-gcm', $khoa, OPENSSL_RAW_DATA, $iv, $the );

	return false === $ra ? '' : $ra;
}

/**
 * Ba khoá PayOS đang dùng.
 *
 * Thứ tự ưu tiên: hằng trong wp-config.php TRƯỚC, rồi mới tới khoá nhập trong
 * admin. Nhờ vậy máy chủ thật khai bằng hằng thì không ai đổi được từ giao diện,
 * còn nơi nào không khai hằng thì quản trị tự nhập được.
 *
 * @return array{client_id: string, api_key: string, checksum_key: string}
 */
function nntm_payos_khoa(): array {
	$hang = array(
		'client_id'    => defined( 'PAYOS_CLIENT_ID' ) ? (string) PAYOS_CLIENT_ID : '',
		'api_key'      => defined( 'PAYOS_API_KEY' ) ? (string) PAYOS_API_KEY : '',
		'checksum_key' => defined( 'PAYOS_CHECKSUM_KEY' ) ? (string) PAYOS_CHECKSUM_KEY : '',
	);

	$cat = get_option( NNTM_PAYOS_OPTION_KHOA, array() );
	$cat = is_array( $cat ) ? $cat : array();

	$ra = array();

	foreach ( nntm_payos_ten_khoa() as $ten ) {
		$ra[ $ten ] = '' !== $hang[ $ten ]
			? $hang[ $ten ]
			: nntm_payos_giai_ma( (string) ( $cat[ $ten ] ?? '' ) );
	}

	return $ra;
}

/**
 * Khoá này đang lấy từ đâu: 'wp-config' | 'admin' | 'trong'.
 *
 * @param string $ten Tên khoá.
 */
function nntm_payos_nguon_khoa( string $ten ): string {
	$hang = array(
		'client_id'    => 'PAYOS_CLIENT_ID',
		'api_key'      => 'PAYOS_API_KEY',
		'checksum_key' => 'PAYOS_CHECKSUM_KEY',
	);

	if ( isset( $hang[ $ten ] ) && defined( $hang[ $ten ] ) && '' !== (string) constant( $hang[ $ten ] ) ) {
		return 'wp-config';
	}

	$cat = get_option( NNTM_PAYOS_OPTION_KHOA, array() );

	return is_array( $cat ) && '' !== (string) ( $cat[ $ten ] ?? '' ) ? 'admin' : 'trong';
}

/**
 * Che khoá để hiện ra màn hình: chỉ để lộ 4 ký tự cuối.
 *
 * KHÔNG BAO GIỜ in nguyên khoá ra HTML, kể cả trong ô nhập dạng password —
 * xem mã nguồn trang là thấy.
 *
 * @param string $khoa Khoá gốc.
 */
function nntm_payos_che_khoa( string $khoa ): string {
	if ( '' === $khoa ) {
		return '';
	}

	$duoi = strlen( $khoa ) > 4 ? substr( $khoa, -4 ) : '';

	return str_repeat( '•', 12 ) . $duoi;
}

/**
 * Lưu khoá do quản trị nhập.
 *
 * Ô để trống nghĩa là GIỮ NGUYÊN khoá cũ, không phải xoá — vì màn hình không
 * hiện khoá cũ ra, người sửa một ô mà bị xoá hai ô kia thì mất cả.
 *
 * @param array $moi Mảng khoá thô từ biểu mẫu.
 */
function nntm_payos_luu_khoa( array $moi ): void {
	$cat = get_option( NNTM_PAYOS_OPTION_KHOA, array() );
	$cat = is_array( $cat ) ? $cat : array();

	foreach ( nntm_payos_ten_khoa() as $ten ) {
		$gia_tri = isset( $moi[ $ten ] ) ? trim( (string) $moi[ $ten ] ) : '';

		if ( '' === $gia_tri ) {
			continue;
		}

		$cat[ $ten ] = nntm_payos_ma_hoa( $gia_tri );
	}

	update_option( NNTM_PAYOS_OPTION_KHOA, $cat, false );
}

/**
 * Xoá sạch khoá đã nhập trong admin.
 */
function nntm_payos_xoa_khoa(): void {
	delete_option( NNTM_PAYOS_OPTION_KHOA );
}

/**
 * Đã khai đủ ba khoá chưa.
 */
function nntm_payos_da_cau_hinh(): bool {
	foreach ( nntm_payos_khoa() as $v ) {
		if ( '' === $v ) {
			return false;
		}
	}

	return true;
}

/**
 * Chế độ thử: cho phép "thanh toán" mà không gọi PayOS.
 *
 * Dùng để chạy thử toàn bộ luồng trên máy dev khi chưa có khoá thật. Phải khai
 * tay trong wp-config.php nên không bao giờ tự bật, và admin có dải cảnh báo đỏ
 * suốt thời gian bật để không ai quên tắt.
 */
function nntm_payos_che_do_thu(): bool {
	return defined( 'NNTM_PAYOS_THU' ) && NNTM_PAYOS_THU;
}

/**
 * Đơn vị tiền tệ hiển thị.
 */
function nntm_payos_dinh_dang_tien( int $so_tien ): string {
	return number_format_i18n( $so_tien ) . ' ₫';
}

/**
 * Dải cảnh báo khi đang ở chế độ thử.
 *
 * Chế độ thử mở khoá sách mà không thu tiền — để sót trên máy chủ thật là cho
 * không toàn bộ thư viện. Cảnh báo phải chói và không tắt được.
 */
function nntm_payos_canh_bao_che_do_thu(): void {
	if ( ! nntm_payos_che_do_thu() || ! current_user_can( 'manage_options' ) ) {
		return;
	}

	echo '<div class="notice notice-error"><p><strong>'
		. esc_html__( 'NNTM PayOS đang ở CHẾ ĐỘ THỬ.', 'nntm' ) . '</strong> '
		. esc_html__( 'Mọi đơn hàng được đánh dấu đã thanh toán mà KHÔNG thu tiền. Chỉ dùng trên máy phát triển — gỡ dòng NNTM_PAYOS_THU trong wp-config.php trước khi chạy thật.', 'nntm' )
		. '</p></div>';
}
add_action( 'admin_notices', 'nntm_payos_canh_bao_che_do_thu' );

/**
 * Màn "Thanh toán": nhập khoá, xem trạng thái, xem đơn gần đây.
 */
function nntm_payos_them_trang_cai_dat(): void {
	add_submenu_page(
		'edit.php?post_type=nntm_publication',
		__( 'Thanh toán (PayOS)', 'nntm' ),
		__( 'Thanh toán (PayOS)', 'nntm' ),
		'manage_options',
		'nntm-payos',
		'nntm_payos_ve_trang_cai_dat'
	);
}
add_action( 'admin_menu', 'nntm_payos_them_trang_cai_dat' );

/**
 * Vẽ màn thanh toán: nhập khoá + trạng thái + đơn gần đây.
 */
function nntm_payos_ve_trang_cai_dat(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$thong_bao = '';

	if ( isset( $_POST['nntm_payos_luu_khoa'] ) && check_admin_referer( 'nntm_payos_khoa' ) ) {
		$tho = array();

		foreach ( nntm_payos_ten_khoa() as $ten ) {
			/*
			 * Khoá PayOS là chuỗi chữ-số-gạch. sanitize_text_field() đủ dùng và
			 * không làm hỏng giá trị hợp lệ; wp_unslash() phải gọi trước vì
			 * WordPress thêm dấu chéo vào mọi thứ trong $_POST.
			 */
			$tho[ $ten ] = isset( $_POST[ 'khoa_' . $ten ] )
				? sanitize_text_field( wp_unslash( $_POST[ 'khoa_' . $ten ] ) )
				: '';
		}

		nntm_payos_luu_khoa( $tho );
		$thong_bao = __( 'Đã lưu khoá.', 'nntm' );
	}

	if ( isset( $_POST['nntm_payos_xoa_khoa'] ) && check_admin_referer( 'nntm_payos_khoa' ) ) {
		nntm_payos_xoa_khoa();
		$thong_bao = __( 'Đã xoá khoá đã nhập trong admin.', 'nntm' );
	}

	$khoa  = nntm_payos_khoa();
	$nhan  = array(
		'client_id'    => __( 'Client ID', 'nntm' ),
		'api_key'      => __( 'API Key', 'nntm' ),
		'checksum_key' => __( 'Checksum Key', 'nntm' ),
	);
	$nguon_chu = array(
		'wp-config' => __( 'đang lấy từ wp-config.php (ô nhập bên dưới không có tác dụng)', 'nntm' ),
		'admin'     => __( 'đã nhập ở đây', 'nntm' ),
		'trong'     => __( 'chưa có', 'nntm' ),
	);
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Thanh toán (PayOS)', 'nntm' ); ?></h1>

		<?php if ( $thong_bao ) : ?>
			<div class="notice notice-success is-dismissible"><p><?php echo esc_html( $thong_bao ); ?></p></div>
		<?php endif; ?>

		<h2><?php esc_html_e( 'Khoá kết nối', 'nntm' ); ?></h2>

		<div class="notice notice-info inline" style="max-width:64em;">
			<p>
				<strong><?php esc_html_e( 'Khoá được mã hoá trước khi lưu.', 'nntm' ); ?></strong>
				<?php esc_html_e( 'Bản sao lưu cơ sở dữ liệu lấy được cũng không đọc ra khoá, vì chìa mã hoá nằm trong wp-config.php chứ không nằm trong cơ sở dữ liệu.', 'nntm' ); ?>
			</p>
			<p>
				<?php esc_html_e( 'Điều kiện để việc đó có nghĩa: muối bảo mật của WordPress phải còn bí mật. Kho mã của dự án này từng để lộ muối cũ — nếu chưa đổi thì đổi trước ở', 'nntm' ); ?>
				<a href="https://api.wordpress.org/secret-key/1.1/salt/" target="_blank" rel="noopener">api.wordpress.org/secret-key</a>.
			</p>
		</div>

		<form method="post">
			<?php wp_nonce_field( 'nntm_payos_khoa' ); ?>

			<table class="form-table" role="presentation">
				<tbody>
				<?php foreach ( nntm_payos_ten_khoa() as $ten ) : ?>
					<?php $nguon = nntm_payos_nguon_khoa( $ten ); ?>
					<tr>
						<th scope="row">
							<label for="khoa_<?php echo esc_attr( $ten ); ?>"><?php echo esc_html( $nhan[ $ten ] ); ?></label>
						</th>
						<td>
							<?php
							/*
							 * Ô nhập LUÔN để trống, không đổ khoá cũ vào value —
							 * type="password" chỉ che ở màn hình, xem mã nguồn trang
							 * là đọc được nguyên văn.
							 */
							?>
							<input type="password" class="regular-text" autocomplete="new-password"
								id="khoa_<?php echo esc_attr( $ten ); ?>"
								name="khoa_<?php echo esc_attr( $ten ); ?>"
								value=""
								placeholder="<?php echo esc_attr( '' !== $khoa[ $ten ] ? nntm_payos_che_khoa( $khoa[ $ten ] ) : __( 'chưa có', 'nntm' ) ); ?>"
								<?php echo 'wp-config' === $nguon ? 'disabled' : ''; ?> />
							<p class="description">
								<?php echo esc_html( $nguon_chu[ $nguon ] ); ?>
								<?php if ( 'wp-config' !== $nguon ) : ?>
									— <?php esc_html_e( 'để trống là giữ nguyên khoá cũ.', 'nntm' ); ?>
								<?php endif; ?>
							</p>
						</td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>

			<p class="submit">
				<button type="submit" name="nntm_payos_luu_khoa" value="1" class="button button-primary"><?php esc_html_e( 'Lưu khoá', 'nntm' ); ?></button>
				<button type="submit" name="nntm_payos_xoa_khoa" value="1" class="button button-link-delete"
					onclick="return confirm('<?php echo esc_js( __( 'Xoá cả ba khoá đã nhập? Site sẽ ngừng nhận thanh toán cho tới khi nhập lại.', 'nntm' ) ); ?>');">
					<?php esc_html_e( 'Xoá khoá', 'nntm' ); ?>
				</button>
			</p>
		</form>

		<h2><?php esc_html_e( 'Địa chỉ webhook', 'nntm' ); ?></h2>
		<p class="description">
			<?php esc_html_e( 'Dán địa chỉ này vào mục Webhook trong bảng điều khiển PayOS. Đây mới là nguồn xác nhận đã trả tiền — trình duyệt quay về không được tin.', 'nntm' ); ?>
		</p>
		<p><input type="text" readonly class="large-text code" value="<?php echo esc_attr( rest_url( 'nntm-payos/v1/webhook' ) ); ?>" onfocus="this.select()" /></p>

		<h2><?php esc_html_e( 'Tình trạng', 'nntm' ); ?></h2>
		<p>
			<?php if ( nntm_payos_che_do_thu() ) : ?>
				<strong style="color:#b32d2e;"><?php esc_html_e( 'CHẾ ĐỘ THỬ — không thu tiền thật.', 'nntm' ); ?></strong>
			<?php elseif ( nntm_payos_da_cau_hinh() ) : ?>
				<strong style="color:#046b02;"><?php esc_html_e( 'Sẵn sàng nhận thanh toán.', 'nntm' ); ?></strong>
			<?php else : ?>
				<strong style="color:#b32d2e;"><?php esc_html_e( 'Chưa nhận thanh toán được — còn thiếu khoá.', 'nntm' ); ?></strong>
				<?php esc_html_e( 'Khách bấm "Mua" sẽ nhận thông báo tạm thời chưa thanh toán được, chứ không mở khoá sách.', 'nntm' ); ?>
			<?php endif; ?>
		</p>

		<h2><?php esc_html_e( 'Đơn hàng', 'nntm' ); ?></h2>
		<p>
			<?php esc_html_e( 'Danh sách đơn đã tách sang màn riêng để dễ theo dõi hằng ngày.', 'nntm' ); ?>
			<a class="button" href="<?php echo esc_url( admin_url( 'edit.php?post_type=nntm_publication&page=nntm-don-hang' ) ); ?>">
				<?php esc_html_e( 'Mở màn Đơn hàng →', 'nntm' ); ?>
			</a>
		</p>
	</div>
	<?php
}
