<?php
/**
 * Hai cửa sổ của khối Danh sách Trú Xứ (PROMPT 08 + bổ sung).
 *
 *   1. Bấm TÊN Trú Xứ  -> cửa sổ lớn xem bộ ảnh của Trú Xứ đó (không sang bài viết).
 *   2. Bấm nút ĐỊA CHỈ -> cửa sổ bản đồ, có nút "Chỉ đường" vẽ đường đi ngay
 *      trên trang, không nhảy sang cửa sổ Google Maps.
 *
 * VÌ SAO DÙNG MAPS EMBED API:
 * Bản đồ nhúng bằng <iframe> của Maps Embed API — Google không tính phí và
 * không giới hạn số lượt gọi. Nó có sẵn chế độ "directions" nên chỉ cần dựng
 * đúng địa chỉ iframe là có luôn đường đi, khỏi phải nạp thư viện JavaScript
 * của Google, khỏi phải bật Directions API (loại có tính phí theo lượt).
 *
 * PHÂN VAI:
 *   - Dữ liệu (địa chỉ, toạ độ, bộ ảnh) nằm ở plugin nntm-core, xem
 *     includes/class-post-meta.php — đổi theme không mất dữ liệu.
 *   - Toàn bộ phần nhìn nằm ở theme, chính là tệp này.
 *
 * CẦN BẬT API NÀO TRÊN GOOGLE CLOUD: chỉ một — Maps Embed API.
 *
 * VỀ API KEY: key của Embed API bắt buộc lộ ra trình duyệt (nó nằm trong địa
 * chỉ iframe), đó là thiết kế của Google. Cách bảo vệ là khoá key theo tên
 * miền — xem hướng dẫn ngay trong trang Giao diện → Bản đồ Trú Xứ.
 */

defined( 'ABSPATH' ) || exit;

const NNTM_TRU_XU_MAP_KEY_OPTION = 'nntm_google_maps_api_key';

/**
 * Lấy API key Google Maps Embed đã cấu hình.
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
 * Danh sách ảnh trong bộ ảnh của một Trú Xứ.
 *
 * @return array<int,array{full:string,thumb:string,alt:string,caption:string}>
 */
function nntm_tru_xu_bo_anh( int $post_id ): array {
	$ids = get_post_meta( $post_id, '_nntm_abode_gallery', true );

	if ( ! is_array( $ids ) ) {
		$ids = array();
	}

	$anh = array();

	foreach ( $ids as $id ) {
		$id = absint( $id );

		if ( $id < 1 ) {
			continue;
		}

		$lon = wp_get_attachment_image_url( $id, 'large' );

		if ( ! $lon ) {
			continue;
		}

		$anh[] = array(
			'full'    => $lon,
			'thumb'   => (string) wp_get_attachment_image_url( $id, 'medium' ),
			'alt'     => (string) get_post_meta( $id, '_wp_attachment_image_alt', true ),
			'caption' => (string) wp_get_attachment_caption( $id ),
		);
	}

	return $anh;
}

/**
 * Vẽ tên Trú Xứ — là nút mở bộ ảnh khi Trú Xứ đó có ảnh, còn không thì chỉ là chữ.
 *
 * Theo yêu cầu, bấm vào tên KHÔNG sang bài viết nữa.
 */
function nntm_tru_xu_ten( int $post_id, string $lop = '' ): string {
	$ten   = get_the_title( $post_id );
	$bo_anh = nntm_tru_xu_bo_anh( $post_id );

	if ( empty( $bo_anh ) ) {
		return '<span class="' . esc_attr( trim( 'nntm-tru-xu-ten ' . $lop ) ) . '">' . esc_html( $ten ) . '</span>';
	}

	ob_start();
	?>
	<button
		type="button"
		class="<?php echo esc_attr( trim( 'nntm-tru-xu-ten nntm-tru-xu-ten--co-anh ' . $lop ) ); ?>"
		data-nntm-tru-xu-anh
		data-ten="<?php echo esc_attr( $ten ); ?>"
		data-anh="<?php echo esc_attr( (string) wp_json_encode( $bo_anh, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) ); ?>"
	>
		<?php echo esc_html( $ten ); ?>
		<span class="nntm-sr-only">
			<?php
			printf(
				/* translators: %d: số ảnh. */
				esc_html__( '— mở bộ ảnh, %d ảnh', 'nntm' ),
				count( $bo_anh )
			);
			?>
		</span>
	</button>
	<?php

	return trim( (string) ob_get_clean() );
}

/**
 * Vẽ nút biểu tượng "Địa chỉ" cho một Trú Xứ.
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
		class="nntm-tru-xu-dia-chi"
		data-nntm-tru-xu-map
		data-ten="<?php echo esc_attr( $ten ); ?>"
		data-dia-chi="<?php echo esc_attr( $vi_tri['address'] ); ?>"
		data-lat="<?php echo esc_attr( $vi_tri['lat'] ); ?>"
		data-lng="<?php echo esc_attr( $vi_tri['lng'] ); ?>"
	>
		<span class="nntm-tru-xu-dia-chi__icon" aria-hidden="true">
			<svg width="16" height="16" viewBox="0 0 16 16" fill="none" focusable="false">
				<path d="M8 1.5c-2.5 0-4.5 2-4.5 4.5 0 3.2 4 8.5 4.5 8.5s4.5-5.3 4.5-8.5c0-2.5-2-4.5-4.5-4.5Z"
					stroke="currentColor" stroke-width="1.4" stroke-linejoin="round" />
				<circle cx="8" cy="6" r="1.7" stroke="currentColor" stroke-width="1.4" />
			</svg>
		</span>
		<span class="nntm-sr-only">
			<?php
			printf(
				/* translators: %s: tên Trú Xứ. */
				esc_html__( 'Địa chỉ và chỉ đường tới %s', 'nntm' ),
				esc_html( $ten )
			);
			?>
		</span>
	</button>
	<?php

	return trim( (string) ob_get_clean() );
}

/**
 * Có Trú Xứ nào trên trang cần hai cửa sổ này không?
 */
function nntm_tru_xu_can_dung(): bool {
	$post = get_post();

	if ( ! $post ) {
		return false;
	}

	if ( has_block( 'nntm/tru-xu-list', $post ) ) {
		return true;
	}

	return (bool) apply_filters( 'nntm_tru_xu_can_dung', false, $post );
}

/**
 * Nạp CSS/JS cho hai cửa sổ.
 *
 * Bản đồ Google KHÔNG nạp ở đây: iframe chỉ được tạo lúc khách thật sự bấm nút
 * "Địa chỉ" (xem assets/js/tru-xu.js).
 */
function nntm_tru_xu_assets(): void {
	if ( ! nntm_tru_xu_can_dung() ) {
		return;
	}

	$css = NNTM_THEME_DIR . '/assets/css/tru-xu.css';
	wp_enqueue_style(
		'nntm-tru-xu',
		NNTM_THEME_URI . '/assets/css/tru-xu.css',
		array( 'nntm-tokens', 'nntm-base' ),
		nntm_asset_version( $css )
	);

	$js = NNTM_THEME_DIR . '/assets/js/tru-xu.js';
	wp_enqueue_script(
		'nntm-tru-xu',
		NNTM_THEME_URI . '/assets/js/tru-xu.js',
		array(),
		nntm_asset_version( $js ),
		true
	);

	wp_localize_script(
		'nntm-tru-xu',
		'nntmTruXu',
		array(
			'apiKey' => nntm_tru_xu_map_api_key(),
			'chu'    => array(
				'thieuKey'     => __( 'Bản đồ chưa được cấu hình. Quản trị viên cần nhập Google Maps API key trong Giao diện → Bản đồ Trú Xứ.', 'nntm' ),
				'dangXinViTri' => __( 'Đang lấy vị trí của bạn…', 'nntm' ),
				'tuChoiViTri'  => __( 'Chưa lấy được vị trí của bạn nên chưa chỉ đường được. Bản đồ vẫn đang chỉ đúng Trú Xứ.', 'nntm' ),
				'khongHoTro'   => __( 'Trình duyệt của bạn không hỗ trợ lấy vị trí.', 'nntm' ),
				'dangChiDuong' => __( 'Đang hiện đường đi từ vị trí của bạn tới Trú Xứ.', 'nntm' ),
				'anhSo'        => __( 'Ảnh %1$d / %2$d', 'nntm' ),
			),
		)
	);
}
add_action( 'wp_enqueue_scripts', 'nntm_tru_xu_assets', 30 );

/**
 * Hai cửa sổ — vẽ một lần cho cả trang, dùng chung cho mọi Trú Xứ.
 */
function nntm_tru_xu_modal(): void {
	if ( ! nntm_tru_xu_can_dung() ) {
		return;
	}
	?>

	<?php   ?>
	<div class="nntm-tx-anh" id="nntm-tx-anh" hidden>
		<div class="nntm-tx-anh__overlay" data-nntm-tx-anh-close></div>

		<div class="nntm-tx-anh__panel" role="dialog" aria-modal="true" aria-labelledby="nntm-tx-anh-ten">
			<div class="nntm-tx-anh__head">
				<h2 class="nntm-tx-anh__ten" id="nntm-tx-anh-ten" data-nntm-tx-anh-ten></h2>

				<button type="button" class="nntm-tx-anh__close" data-nntm-tx-anh-close>
					<span class="nntm-sr-only"><?php esc_html_e( 'Đóng bộ ảnh', 'nntm' ); ?></span>
					<span aria-hidden="true">&times;</span>
				</button>
			</div>

			<div class="nntm-tx-anh__sanh">
				<button type="button" class="nntm-tx-anh__nav nntm-tx-anh__nav--truoc" data-nntm-tx-anh-truoc>
					<span class="nntm-sr-only"><?php esc_html_e( 'Ảnh trước', 'nntm' ); ?></span>
					<span aria-hidden="true">&larr;</span>
				</button>

				<figure class="nntm-tx-anh__khung">
					<img class="nntm-tx-anh__lon" data-nntm-tx-anh-lon src="" alt="" />
					<figcaption class="nntm-tx-anh__chu-thich" data-nntm-tx-anh-chu-thich></figcaption>
				</figure>

				<button type="button" class="nntm-tx-anh__nav nntm-tx-anh__nav--sau" data-nntm-tx-anh-sau>
					<span class="nntm-sr-only"><?php esc_html_e( 'Ảnh tiếp theo', 'nntm' ); ?></span>
					<span aria-hidden="true">&rarr;</span>
				</button>
			</div>

			<p class="nntm-tx-anh__dem" data-nntm-tx-anh-dem role="status" aria-live="polite"></p>

			<div class="nntm-tx-anh__dai" data-nntm-tx-anh-dai></div>
		</div>
	</div>

	<?php   ?>
	<div class="nntm-tx-map" id="nntm-tx-map" hidden>
		<div class="nntm-tx-map__overlay" data-nntm-tx-map-close></div>

		<div class="nntm-tx-map__panel" role="dialog" aria-modal="true" aria-labelledby="nntm-tx-map-ten">
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
				<p class="nntm-tx-map__trang-thai" data-nntm-tx-map-trang-thai hidden></p>
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
add_action( 'wp_footer', 'nntm_tru_xu_modal' );

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

		<h2><?php esc_html_e( 'Cần bật API nào trên Google Cloud', 'nntm' ); ?></h2>
		<p>
			<strong>Maps Embed API</strong> — <?php esc_html_e( 'chỉ một API này thôi.', 'nntm' ); ?>
		</p>
		<p>
			<?php esc_html_e( 'Bản đồ ở đây nhúng bằng iframe của Maps Embed API. Google không tính phí và không giới hạn số lượt gọi API này, kể cả chế độ chỉ đường. Không cần bật Maps JavaScript API, không cần Directions API, không cần Geocoding API.', 'nntm' ); ?>
		</p>

		<h2><?php esc_html_e( 'Khoá key theo tên miền — bắt buộc làm', 'nntm' ); ?></h2>
		<p>
			<?php esc_html_e( 'Key nằm trong địa chỉ iframe nên bắt buộc lộ ra trình duyệt, đó là thiết kế của Google, không giấu được. Cách bảo vệ duy nhất là khoá key theo tên miền:', 'nntm' ); ?>
		</p>
		<ol>
			<li><?php esc_html_e( 'Mở Google Cloud Console → APIs &amp; Services → Credentials.', 'nntm' ); ?></li>
			<li><?php esc_html_e( 'Chọn key vừa dán ở trên → mục "Application restrictions" → chọn "Websites".', 'nntm' ); ?></li>
			<li>
				<?php esc_html_e( 'Thêm tên miền của website này:', 'nntm' ); ?>
				<code><?php echo esc_html( wp_parse_url( home_url(), PHP_URL_HOST ) . '/*' ); ?></code>
			</li>
			<li><?php esc_html_e( 'Mục "API restrictions" → chọn "Restrict key" → chỉ tick đúng Maps Embed API.', 'nntm' ); ?></li>
		</ol>

		<h2><?php esc_html_e( 'Nhập vị trí và bộ ảnh cho từng Trú Xứ', 'nntm' ); ?></h2>
		<p>
			<?php esc_html_e( 'Vào Trú Xứ → mở một Trú Xứ → hộp "Vị trí trên bản đồ" để nhập địa chỉ, vĩ độ, kinh độ; và hộp "Bộ ảnh Trú Xứ" để chọn ảnh.', 'nntm' ); ?>
		</p>
		<ul>
			<li><?php esc_html_e( 'Chưa có toạ độ → không hiện nút biểu tượng địa chỉ.', 'nntm' ); ?></li>
			<li><?php esc_html_e( 'Chưa có ảnh → tên Trú Xứ chỉ là chữ, bấm vào không mở gì.', 'nntm' ); ?></li>
		</ul>
	</div>
	<?php
}
