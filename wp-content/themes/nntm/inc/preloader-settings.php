<?php
/**
 * Cấu hình màn hình chờ: ảnh logo, thời gian tối thiểu, chọn hiệu ứng.
 *
 * Nằm chung trang quản trị với danh sách trích dẫn (Giao diện -> Trích dẫn màn
 * hình chờ), vì cùng nói về một thứ; tách ra tệp riêng để phần trích dẫn không
 * bị phình.
 */

defined( 'ABSPATH' ) || exit;

const NNTM_PRELOADER_LOGO_OPTION     = 'nntm_preloader_logo_id';
const NNTM_PRELOADER_GIAY_OPTION     = 'nntm_preloader_giay';
const NNTM_PRELOADER_HIEU_UNG_OPTION = 'nntm_preloader_hieu_ung';

const NNTM_PRELOADER_GROUP = 'nntm_preloader_cai_dat_group';

/** Slug trang quản trị — dùng để nhận ra đúng trang khi nạp tài nguyên. */
const NNTM_PRELOADER_TRANG = 'nntm-preloader-quotes';

/** Giây tối thiểu: mặc định, và khoảng cho phép. */
const NNTM_PRELOADER_GIAY_MAC_DINH = 2.0;
const NNTM_PRELOADER_GIAY_MIN      = 0.0;
const NNTM_PRELOADER_GIAY_MAX      = 15.0;

/**
 * Ảnh logo dùng cho hiệu ứng "Hào Quang".
 *
 * @return int ID tệp đính kèm, 0 nếu chưa đặt hoặc tệp đã bị xoá.
 */
function nntm_preloader_logo_id(): int {
	$id = (int) get_option( NNTM_PRELOADER_LOGO_OPTION, 0 );

	if ( $id < 1 ) {
		return 0;
	}

	/*
	 * Ảnh có thể đã bị xoá khỏi Thư viện sau khi chọn. Kiểm tra lại, nếu không
	 * hiệu ứng sẽ hiện một khoảng trống kèm vòng hào quang quay quanh hư không.
	 */
	if ( 'attachment' !== get_post_type( $id ) ) {
		return 0;
	}

	return $id;
}

/**
 * Số giây TỐI THIỂU màn hình chờ phải hiện.
 *
 * Đây là sàn, không phải hạn. Màn hình chờ tắt ở mốc muộn hơn giữa hai mốc:
 * đủ số giây này, và trang tải xong. Tải nhanh thì vẫn chờ đủ giây để khách kịp
 * đọc câu trích dẫn; tải chậm thì chờ tới lúc xong.
 */
function nntm_preloader_giay(): float {
	$giay = get_option( NNTM_PRELOADER_GIAY_OPTION, null );

	if ( null === $giay || '' === $giay ) {
		$giay = NNTM_PRELOADER_GIAY_MAC_DINH;
	}

	return nntm_preloader_loc_giay( $giay );
}

function nntm_preloader_loc_giay( $tho ): float {
	if ( is_string( $tho ) ) {
		$tho = str_replace( ',', '.', trim( $tho ) );
	}

	if ( ! is_numeric( $tho ) ) {
		return NNTM_PRELOADER_GIAY_MAC_DINH;
	}

	$giay = (float) $tho;
	$giay = max( NNTM_PRELOADER_GIAY_MIN, min( NNTM_PRELOADER_GIAY_MAX, $giay ) );

	return round( $giay, 1 );
}

/**
 * Những hiệu ứng admin cho phép chạy.
 *
 * Trả về mảng khoá hiệu ứng. Luôn bảo đảm còn ít nhất một hiệu ứng: bỏ chọn hết
 * thì quay về dùng tất cả, chứ không để màn hình chờ trống trơn.
 */
function nntm_preloader_hieu_ung_bat(): array {
	$tat_ca = array_keys( nntm_preloader_effects() );
	$luu    = get_option( NNTM_PRELOADER_HIEU_UNG_OPTION, null );

	// Chưa từng lưu -> bật hết.
	$chon = ( null === $luu ) ? $tat_ca : nntm_preloader_loc_hieu_ung( $luu );

	/*
	 * Hiệu ứng "logo" chỉ có nghĩa khi đã chọn ảnh. Chưa có ảnh mà vẫn để nó
	 * trong vòng ngẫu nhiên thì sẽ có lần khách gặp màn hình chờ trống.
	 */
	if ( ! nntm_preloader_logo_id() ) {
		$chon = array_values( array_diff( $chon, array( 'logo' ) ) );
	}

	if ( empty( $chon ) ) {
		$chon = array_values( array_diff( $tat_ca, array( 'logo' ) ) );
	}

	return $chon;
}

function nntm_preloader_loc_hieu_ung( $tho ): array {
	if ( ! is_array( $tho ) ) {
		return array();
	}

	$hop_le = array_keys( nntm_preloader_effects() );
	$ra     = array();

	foreach ( $tho as $khoa ) {
		$khoa = sanitize_key( (string) $khoa );

		if ( in_array( $khoa, $hop_le, true ) && ! in_array( $khoa, $ra, true ) ) {
			$ra[] = $khoa;
		}
	}

	return $ra;
}

/*
 * ---------------------------------------------------------------------------
 * Đăng ký thiết lập
 * ---------------------------------------------------------------------------
 */

function nntm_preloader_dang_ky_cai_dat(): void {
	register_setting(
		NNTM_PRELOADER_GROUP,
		NNTM_PRELOADER_LOGO_OPTION,
		array(
			'type'              => 'integer',
			'sanitize_callback' => 'absint',
			'default'           => 0,
			'show_in_rest'      => false,
		)
	);

	register_setting(
		NNTM_PRELOADER_GROUP,
		NNTM_PRELOADER_GIAY_OPTION,
		array(
			'type'              => 'number',
			'sanitize_callback' => 'nntm_preloader_loc_giay',
			'default'           => NNTM_PRELOADER_GIAY_MAC_DINH,
			'show_in_rest'      => false,
		)
	);

	register_setting(
		NNTM_PRELOADER_GROUP,
		NNTM_PRELOADER_HIEU_UNG_OPTION,
		array(
			'type'              => 'array',
			'sanitize_callback' => 'nntm_preloader_loc_hieu_ung',
			'default'           => array(),
			'show_in_rest'      => false,
		)
	);
}
add_action( 'admin_init', 'nntm_preloader_dang_ky_cai_dat' );

/**
 * Bộ chọn ảnh của WordPress — chỉ nạp đúng trang cấu hình màn hình chờ.
 */
function nntm_preloader_admin_assets( string $hook ): void {
	/*
	 * KHÔNG so cứng tên hook đầy đủ.
	 *
	 * Tiền tố của tên hook lấy từ TÊN MENU CHA ĐÃ DỊCH: sanitize_title() của
	 * "Appearance" ra "appearance", nhưng admin tiếng Việt thì menu tên "Giao
	 * diện" và tiền tố thành "giao-dien". Site này đặt WPLANG = 'vi', nên so với
	 * 'appearance_page_...' không bao giờ khớp — thư viện ảnh không được nạp và
	 * nút "Chọn ảnh" bấm vào không hiện gì.
	 *
	 * Slug trang thì không bị dịch, nên đối chiếu theo nó.
	 */
	if ( false === strpos( $hook, NNTM_PRELOADER_TRANG ) ) {
		return;
	}

	wp_enqueue_media();

	$duong_dan = '/assets/js/admin/preloader-logo.js';

	/*
	 * Phụ thuộc 'media-editor' là bắt buộc: đó là script WordPress đặt ở CUỐI
	 * trang. Viết <script> thẳng trong form thì nó chạy giữa trang, lúc wp.media
	 * chưa tồn tại — nút bấm không được gắn gì cả.
	 */
	wp_enqueue_script(
		'nntm-preloader-logo',
		NNTM_THEME_URI . $duong_dan,
		array( 'media-editor' ),
		nntm_asset_version( NNTM_THEME_DIR . $duong_dan ),
		true
	);

	wp_localize_script(
		'nntm-preloader-logo',
		'nntmPreloaderLogo',
		array(
			'tieuDe'     => __( 'Chọn ảnh logo cho màn hình chờ', 'nntm' ),
			'nutDung'    => __( 'Dùng ảnh này', 'nntm' ),
			'chonAnh'    => __( 'Chọn ảnh', 'nntm' ),
			'doiAnh'     => __( 'Đổi ảnh', 'nntm' ),
			'loiThuVien' => __( 'Không nạp được thư viện ảnh', 'nntm' ),
		)
	);
}
add_action( 'admin_enqueue_scripts', 'nntm_preloader_admin_assets' );

/*
 * ---------------------------------------------------------------------------
 * Giao diện quản trị (gắn vào trang trích dẫn sẵn có)
 * ---------------------------------------------------------------------------
 */

function nntm_preloader_cai_dat_form(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$logo_id  = nntm_preloader_logo_id();
	$logo_url = $logo_id ? (string) wp_get_attachment_image_url( $logo_id, 'medium' ) : '';
	$giay     = nntm_preloader_giay();
	$dang_bat = nntm_preloader_hieu_ung_bat();
	?>
	<hr />

	<h2><?php esc_html_e( 'Cấu hình màn hình chờ', 'nntm' ); ?></h2>

	<form method="post" action="options.php">
		<?php settings_fields( NNTM_PRELOADER_GROUP ); ?>

		<table class="form-table" role="presentation">
			<tr>
				<th scope="row">
					<label for="nntm-preloader-giay"><?php esc_html_e( 'Hiện tối thiểu', 'nntm' ); ?></label>
				</th>
				<td>
					<input
						type="number"
						id="nntm-preloader-giay"
						name="<?php echo esc_attr( NNTM_PRELOADER_GIAY_OPTION ); ?>"
						value="<?php echo esc_attr( (string) $giay ); ?>"
						step="0.1"
						min="<?php echo esc_attr( (string) NNTM_PRELOADER_GIAY_MIN ); ?>"
						max="<?php echo esc_attr( (string) NNTM_PRELOADER_GIAY_MAX ); ?>"
						class="small-text"
					/>
					<?php esc_html_e( 'giây', 'nntm' ); ?>

					<p class="description">
						<?php esc_html_e( 'Đây là SÀN, không phải hạn. Màn hình chờ tắt ở mốc muộn hơn giữa hai mốc: đủ số giây này, và trang tải xong.', 'nntm' ); ?>
					</p>
					<p class="description">
						<?php esc_html_e( 'Ví dụ đặt 3 giây: trang tải xong sau 1 giây thì vẫn chờ đủ 3 giây để khách kịp đọc câu trích dẫn; trang tải mất 8 giây thì chờ tới khi xong hẳn.', 'nntm' ); ?>
					</p>
					<p class="description">
						<?php
						printf(
							/* translators: 1: giá trị nhỏ nhất, 2: giá trị lớn nhất. */
							esc_html__( 'Nhận từ %1$s đến %2$s giây. Đặt 0 thì tắt ngay khi trang tải xong.', 'nntm' ),
							esc_html( (string) NNTM_PRELOADER_GIAY_MIN ),
							esc_html( (string) NNTM_PRELOADER_GIAY_MAX )
						);
						?>
					</p>
				</td>
			</tr>

			<tr>
				<th scope="row"><?php esc_html_e( 'Ảnh logo', 'nntm' ); ?></th>
				<td>
					<input
						type="hidden"
						id="nntm-preloader-logo-id"
						name="<?php echo esc_attr( NNTM_PRELOADER_LOGO_OPTION ); ?>"
						value="<?php echo esc_attr( (string) $logo_id ); ?>"
					/>

					<div id="nntm-preloader-logo-xem" style="margin-bottom:10px;">
						<?php if ( $logo_url ) : ?>
							<img
								src="<?php echo esc_url( $logo_url ); ?>"
								alt=""
								style="max-width:160px;height:auto;display:block;background:#1b1b1b;padding:14px;border-radius:8px;"
							/>
						<?php endif; ?>
					</div>

					<button type="button" class="button" id="nntm-preloader-logo-chon">
						<?php echo $logo_id ? esc_html__( 'Đổi ảnh', 'nntm' ) : esc_html__( 'Chọn ảnh', 'nntm' ); ?>
					</button>

					<button
						type="button"
						class="button-link delete"
						id="nntm-preloader-logo-go"
						<?php echo $logo_id ? '' : 'style="display:none"'; ?>
					><?php esc_html_e( 'Gỡ ảnh', 'nntm' ); ?></button>

					<p class="description">
						<?php esc_html_e( 'Dùng cho hiệu ứng "Hào Quang". Nên dùng ảnh PNG nền trong suốt, hình vuông, cạnh khoảng 400px.', 'nntm' ); ?>
					</p>
					<p class="description">
						<?php esc_html_e( 'Màn hình chờ có nền tối, nên logo màu sáng sẽ nổi rõ nhất.', 'nntm' ); ?>
					</p>
				</td>
			</tr>

			<tr>
				<th scope="row"><?php esc_html_e( 'Hiệu ứng được chạy', 'nntm' ); ?></th>
				<td>
					<fieldset>
						<?php foreach ( nntm_preloader_effects() as $khoa => $mo_ta ) : ?>
							<?php
							$la_logo   = ( 'logo' === $khoa );
							$thieu_anh = ( $la_logo && ! $logo_id );
							?>
							<label style="display:block;margin-bottom:6px;">
								<input
									type="checkbox"
									name="<?php echo esc_attr( NNTM_PRELOADER_HIEU_UNG_OPTION ); ?>[]"
									value="<?php echo esc_attr( $khoa ); ?>"
									<?php checked( in_array( $khoa, $dang_bat, true ) ); ?>
								/>
								<?php echo esc_html( $mo_ta['title'] ); ?>
								<?php if ( $thieu_anh ) : ?>
									<em>— <?php esc_html_e( 'chưa chọn ảnh logo nên hiệu ứng này đang bị bỏ qua', 'nntm' ); ?></em>
								<?php endif; ?>
							</label>
						<?php endforeach; ?>
					</fieldset>

					<p class="description">
						<?php esc_html_e( 'Mỗi lần tải trang, website chọn ngẫu nhiên một trong các hiệu ứng được tích, và tránh lặp lại đúng hiệu ứng của lần trước. Chỉ muốn dùng logo thì tích mỗi "Hào Quang".', 'nntm' ); ?>
					</p>
					<p class="description">
						<?php esc_html_e( 'Bỏ tích hết thì website tự dùng lại toàn bộ hiệu ứng, để màn hình chờ không bị trống.', 'nntm' ); ?>
					</p>
				</td>
			</tr>
		</table>

		<?php submit_button(); ?>
	</form>

	<?php
}
add_action( 'nntm_preloader_admin_sau_quote', 'nntm_preloader_cai_dat_form' );
