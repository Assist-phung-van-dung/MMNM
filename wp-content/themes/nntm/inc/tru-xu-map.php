<?php
/**
 * Bản đồ Trú Xứ (PROMPT 08).
 *
 * Khách bấm nút "Địa chỉ" trên thẻ Trú Xứ thì mở một cửa sổ ngay trên trang,
 * KHÔNG chuyển sang Google Maps. Trong cửa sổ có bản đồ, địa chỉ, và nút xin
 * vị trí hiện tại để chỉ đường tới Trú Xứ.
 *
 * PHÂN VAI:
 *   - Dữ liệu vị trí (địa chỉ, vĩ độ, kinh độ) nằm ở plugin nntm-core, xem
 *     includes/class-post-meta.php — đổi theme không mất dữ liệu.
 *   - Toàn bộ phần nhìn (nút, cửa sổ, bản đồ) nằm ở theme, chính là tệp này.
 *
 * CẦN BẬT NHỮNG API NÀO TRÊN GOOGLE CLOUD:
 *   1. Maps JavaScript API  — vẽ bản đồ.
 *   2. Directions API       — tính đường đi từ vị trí khách tới Trú Xứ.
 *   (Không cần Geocoding API vì toạ độ do quản trị viên nhập sẵn.)
 *
 * VỀ API KEY:
 *   Key của Maps JavaScript API BẮT BUỘC phải lộ ra trình duyệt — đó là thiết
 *   kế của Google, không có cách nào giấu. Bù lại phải khoá key theo tên miền:
 *   Google Cloud Console > Credentials > chọn key > Application restrictions >
 *   "Websites" > thêm đúng các tên miền của website. Khoá xong thì key bị lấy
 *   cắp cũng không dùng được ở nơi khác. Ngoài key này KHÔNG có bí mật nào
 *   khác được đưa xuống trình duyệt.
 */

defined( 'ABSPATH' ) || exit;

const NNTM_TRU_XU_MAP_KEY_OPTION = 'nntm_google_maps_api_key';

/**
 * Lấy API key Google Maps đã cấu hình.
 */
function nntm_tru_xu_map_api_key(): string {
	$key = (string) get_option( NNTM_TRU_XU_MAP_KEY_OPTION, '' );

	return (string) apply_filters( 'nntm_google_maps_api_key', trim( $key ) );
}

/**
 * Đọc vị trí bản đồ của một Trú Xứ.
 *
 * @return array{address:string,lat:string,lng:string,co_toa_do:bool}
 */
function nntm_tru_xu_vi_tri( int $post_id ): array {
	$lat = trim( (string) get_post_meta( $post_id, '_nntm_abode_lat', true ) );
	$lng = trim( (string) get_post_meta( $post_id, '_nntm_abode_lng', true ) );

	$dia_chi = trim( (string) get_post_meta( $post_id, '_nntm_abode_address', true ) );
	if ( '' === $dia_chi ) {
		// Chưa nhập địa chỉ đầy đủ thì tạm dùng địa điểm ngắn trên thẻ.
		$dia_chi = trim( (string) get_post_meta( $post_id, '_nntm_abode_location', true ) );
	}

	return array(
		'address'   => $dia_chi,
		'lat'       => $lat,
		'lng'       => $lng,
		'co_toa_do' => ( '' !== $lat && '' !== $lng && is_numeric( $lat ) && is_numeric( $lng ) ),
	);
}

/**
 * Vẽ nút "Địa chỉ" cho một Trú Xứ.
 *
 * Trả về chuỗi rỗng nếu Trú Xứ chưa có toạ độ — không hiện nút chết.
 */
function nntm_tru_xu_nut_dia_chi( int $post_id ): string {
	$vi_tri = nntm_tru_xu_vi_tri( $post_id );

	if ( ! $vi_tri['co_toa_do'] ) {
		return '';
	}

	$ten = get_the_title( $post_id );

	ob_start();
	?>
	<button
		type="button"
		class="nntm-tru-xu-card__dia-chi"
		data-nntm-tru-xu-map
		data-ten="<?php echo esc_attr( $ten ); ?>"
		data-dia-chi="<?php echo esc_attr( $vi_tri['address'] ); ?>"
		data-lat="<?php echo esc_attr( $vi_tri['lat'] ); ?>"
		data-lng="<?php echo esc_attr( $vi_tri['lng'] ); ?>"
	>
		<span class="nntm-tru-xu-card__dia-chi-icon" aria-hidden="true">
			<svg width="14" height="14" viewBox="0 0 16 16" fill="none" focusable="false">
				<path d="M8 1.5c-2.5 0-4.5 2-4.5 4.5 0 3.2 4 8.5 4.5 8.5s4.5-5.3 4.5-8.5c0-2.5-2-4.5-4.5-4.5Z"
					stroke="currentColor" stroke-width="1.4" stroke-linejoin="round" />
				<circle cx="8" cy="6" r="1.6" stroke="currentColor" stroke-width="1.4" />
			</svg>
		</span>
		<?php
		printf(
			/* translators: %s: tên Trú Xứ. */
			esc_html__( 'Địa chỉ', 'nntm' )
		);
		?>
		<span class="nntm-sr-only"><?php echo esc_html( ' — ' . $ten ); ?></span>
	</button>
	<?php

	return trim( (string) ob_get_clean() );
}

/**
 * Có Trú Xứ nào trên trang cần bản đồ không? Dùng để chỉ nạp tài nguyên khi cần.
 */
function nntm_tru_xu_map_can_dung(): bool {
	$post = get_post();

	if ( ! $post ) {
		return false;
	}

	if ( has_block( 'nntm/tru-xu-list', $post ) ) {
		return true;
	}

	return (bool) apply_filters( 'nntm_tru_xu_map_can_dung', false, $post );
}

/**
 * Nạp CSS/JS cho cửa sổ bản đồ.
 *
 * Thư viện Google Maps KHÔNG nạp ở đây: nó chỉ được tải lúc khách thật sự bấm
 * nút "Địa chỉ" (xem assets/js/tru-xu-map.js). Nhờ vậy trang không tốn thêm
 * request nào và không gọi hạn ngạch Google cho người chỉ lướt qua.
 */
function nntm_tru_xu_map_assets(): void {
	if ( ! nntm_tru_xu_map_can_dung() ) {
		return;
	}

	$css = NNTM_THEME_DIR . '/assets/css/tru-xu-map.css';
	wp_enqueue_style(
		'nntm-tru-xu-map',
		NNTM_THEME_URI . '/assets/css/tru-xu-map.css',
		array( 'nntm-tokens', 'nntm-base' ),
		nntm_asset_version( $css )
	);

	$js = NNTM_THEME_DIR . '/assets/js/tru-xu-map.js';
	wp_enqueue_script(
		'nntm-tru-xu-map',
		NNTM_THEME_URI . '/assets/js/tru-xu-map.js',
		array(),
		nntm_asset_version( $js ),
		true
	);

	wp_localize_script(
		'nntm-tru-xu-map',
		'nntmTruXuMap',
		array(
			'apiKey' => nntm_tru_xu_map_api_key(),
			'chu'    => array(
				'thieuKey'     => __( 'Bản đồ chưa được cấu hình. Quản trị viên cần nhập Google Maps API key trong Giao diện → Bản đồ Trú Xứ.', 'nntm' ),
				'dangTai'      => __( 'Đang tải bản đồ…', 'nntm' ),
				'loiTai'       => __( 'Không tải được bản đồ. Vui lòng thử lại sau.', 'nntm' ),
				'dangXinViTri' => __( 'Đang lấy vị trí của bạn…', 'nntm' ),
				'tuChoiViTri'  => __( 'Chưa lấy được vị trí của bạn nên chưa chỉ đường được. Bản đồ vẫn đang chỉ đúng Trú Xứ.', 'nntm' ),
				'khongHoTro'   => __( 'Trình duyệt của bạn không hỗ trợ lấy vị trí.', 'nntm' ),
				'loiChiDuong'  => __( 'Không tìm được đường đi tới Trú Xứ này.', 'nntm' ),
				'viTriCuaBan'  => __( 'Vị trí của bạn', 'nntm' ),
			),
		)
	);
}
add_action( 'wp_enqueue_scripts', 'nntm_tru_xu_map_assets', 30 );

/**
 * Cửa sổ bản đồ — vẽ một lần cho cả trang, dùng chung cho mọi Trú Xứ.
 */
function nntm_tru_xu_map_modal(): void {
	if ( ! nntm_tru_xu_map_can_dung() ) {
		return;
	}
	?>
	<div class="nntm-tx-map" id="nntm-tx-map" hidden>
		<div class="nntm-tx-map__overlay" data-nntm-tx-map-close></div>

		<div
			class="nntm-tx-map__panel"
			role="dialog"
			aria-modal="true"
			aria-labelledby="nntm-tx-map-ten"
		>
			<div class="nntm-tx-map__head">
				<div>
					<h2 class="nntm-tx-map__ten" id="nntm-tx-map-ten" data-nntm-tx-map-ten></h2>
					<p class="nntm-tx-map__dia-chi" data-nntm-tx-map-dia-chi></p>
				</div>

				<button type="button" class="nntm-tx-map__close" data-nntm-tx-map-close>
					<span class="nntm-sr-only"><?php esc_html_e( 'Đóng bản đồ', 'nntm' ); ?></span>
					<span aria-hidden="true">&times;</span>
				</button>
			</div>

			<div class="nntm-tx-map__khung" data-nntm-tx-map-khung>
				<p class="nntm-tx-map__trang-thai" data-nntm-tx-map-trang-thai></p>
			</div>

			<div class="nntm-tx-map__chan">
				<button type="button" class="nntm-tx-map__chi-duong" data-nntm-tx-map-chi-duong>
					<?php esc_html_e( 'Chỉ đường từ vị trí của tôi', 'nntm' ); ?>
				</button>

				<p class="nntm-tx-map__nhan" data-nntm-tx-map-nhan role="status" aria-live="polite"></p>
			</div>
		</div>
	</div>
	<?php
}
add_action( 'wp_footer', 'nntm_tru_xu_map_modal' );

/*
 * ---------------------------------------------------------------------------
 * Trang cấu hình API key
 * ---------------------------------------------------------------------------
 */

function nntm_tru_xu_map_dang_ky_setting(): void {
	register_setting(
		'nntm_tru_xu_map_group',
		NNTM_TRU_XU_MAP_KEY_OPTION,
		array(
			'type'              => 'string',
			'sanitize_callback' => 'sanitize_text_field',
			'default'           => '',
			'show_in_rest'      => false,
		)
	);
}
add_action( 'admin_init', 'nntm_tru_xu_map_dang_ky_setting' );

function nntm_tru_xu_map_menu(): void {
	add_theme_page(
		__( 'Bản đồ Trú Xứ', 'nntm' ),
		__( 'Bản đồ Trú Xứ', 'nntm' ),
		'manage_options',
		'nntm-tru-xu-map',
		'nntm_tru_xu_map_trang_quan_tri'
	);
}
add_action( 'admin_menu', 'nntm_tru_xu_map_menu' );

function nntm_tru_xu_map_trang_quan_tri(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$key = nntm_tru_xu_map_api_key();
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Bản đồ Trú Xứ', 'nntm' ); ?></h1>

		<form method="post" action="options.php">
			<?php settings_fields( 'nntm_tru_xu_map_group' ); ?>

			<table class="form-table" role="presentation">
				<tr>
					<th scope="row">
						<label for="nntm-gmaps-key"><?php esc_html_e( 'Google Maps API key', 'nntm' ); ?></label>
					</th>
					<td>
						<input
							type="text"
							id="nntm-gmaps-key"
							class="regular-text code"
							name="<?php echo esc_attr( NNTM_TRU_XU_MAP_KEY_OPTION ); ?>"
							value="<?php echo esc_attr( $key ); ?>"
							autocomplete="off"
							spellcheck="false"
						/>
						<p class="description">
							<?php esc_html_e( 'Để trống thì nút "Địa chỉ" vẫn hiện nhưng cửa sổ chỉ báo là bản đồ chưa được cấu hình.', 'nntm' ); ?>
						</p>
					</td>
				</tr>
			</table>

			<?php submit_button(); ?>
		</form>

		<h2><?php esc_html_e( 'Cần bật những API nào trên Google Cloud', 'nntm' ); ?></h2>
		<ol>
			<li><strong>Maps JavaScript API</strong> — <?php esc_html_e( 'vẽ bản đồ trong cửa sổ.', 'nntm' ); ?></li>
			<li><strong>Directions API</strong> — <?php esc_html_e( 'tính đường đi từ vị trí khách tới Trú Xứ.', 'nntm' ); ?></li>
		</ol>
		<p><?php esc_html_e( 'Không cần Geocoding API, vì toạ độ do bạn nhập sẵn cho từng Trú Xứ.', 'nntm' ); ?></p>

		<h2><?php esc_html_e( 'Khoá key theo tên miền — bắt buộc làm', 'nntm' ); ?></h2>
		<p>
			<?php esc_html_e( 'Key của Maps JavaScript API bắt buộc phải lộ ra trình duyệt, đó là thiết kế của Google, không giấu được. Cách bảo vệ duy nhất là khoá key theo tên miền:', 'nntm' ); ?>
		</p>
		<ol>
			<li><?php esc_html_e( 'Mở Google Cloud Console → APIs &amp; Services → Credentials.', 'nntm' ); ?></li>
			<li><?php esc_html_e( 'Chọn key vừa dán ở trên → mục "Application restrictions" → chọn "Websites".', 'nntm' ); ?></li>
			<li>
				<?php esc_html_e( 'Thêm các tên miền của website này:', 'nntm' ); ?>
				<code><?php echo esc_html( wp_parse_url( home_url(), PHP_URL_HOST ) . '/*' ); ?></code>
			</li>
			<li><?php esc_html_e( 'Mục "API restrictions" → chọn "Restrict key" → chỉ tick đúng hai API kể trên.', 'nntm' ); ?></li>
			<li><?php esc_html_e( 'Nên đặt thêm hạn mức chi tiêu (Budget & alerts) để không bị phát sinh chi phí ngoài ý muốn.', 'nntm' ); ?></li>
		</ol>

		<h2><?php esc_html_e( 'Nhập vị trí cho từng Trú Xứ', 'nntm' ); ?></h2>
		<p>
			<?php esc_html_e( 'Vào Trú Xứ → mở một Trú Xứ → hộp "Vị trí trên bản đồ" và nhập địa chỉ, vĩ độ, kinh độ. Trú Xứ chưa có toạ độ thì thẻ của nó không hiện nút "Địa chỉ".', 'nntm' ); ?>
		</p>
	</div>
	<?php
}
