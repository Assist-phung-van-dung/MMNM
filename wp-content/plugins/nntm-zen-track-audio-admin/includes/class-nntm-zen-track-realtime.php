<?php
/**
 * Soketi/Pusher-compatible presence cho block Thiền Đường.
 *
 * - presence-nntm-thien-duong: tổng user đang ở trang.
 * - presence-nntm-thien-duong-track-{ID}: user đang thực sự phát bài đó.
 *
 * Chỉ app key + host được gửi ra frontend. App secret chỉ dùng server-side
 * để ký channel authorization và tuyệt đối không xuất ra HTML/JS.
 *
 * @package NNTM
 */

defined( 'ABSPATH' ) || exit;

final class NNTM_Zen_Track_Realtime {
	private const OPTION_NAME = 'nntm_zen_track_realtime';
	private const SETTINGS_GROUP = 'nntm_zen_track_realtime_group';
	private const AUTH_ACTION = 'nntm_soketi_auth';
	private const AUTH_NONCE_ACTION = 'nntm_soketi_auth';
	private const PAGE_CHANNEL = 'presence-nntm-thien-duong';
	private const TRACK_CHANNEL_PREFIX = 'presence-nntm-thien-duong-track-';
	private const PUSHER_JS_VERSION = '8.6.0';

	private static bool $assets_enqueued = false;

	public static function init(): void {
		add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
		add_action( 'admin_menu', array( __CLASS__, 'register_settings_page' ) );
		add_action( 'wp_ajax_' . self::AUTH_ACTION, array( __CLASS__, 'ajax_authorize_presence' ) );
	}

	private static function defaults(): array {
		return array(
			'enabled'   => 0,
			'app_key'   => '',
			'app_secret' => '',
			'ws_host'   => '',
			'ws_port'   => 6001,
			'wss_port'  => 443,
			'force_tls' => 1,
			'ws_path'   => '',
		);
	}

	public static function get_settings(): array {
		$stored = get_option( self::OPTION_NAME, array() );
		return wp_parse_args( is_array( $stored ) ? $stored : array(), self::defaults() );
	}

	public static function is_ready(): bool {
		$settings = self::get_settings();
		return ! empty( $settings['enabled'] )
			&& '' !== trim( (string) $settings['app_key'] )
			&& '' !== trim( (string) $settings['app_secret'] )
			&& '' !== trim( (string) $settings['ws_host'] );
	}

	public static function register_settings(): void {
		register_setting(
			self::SETTINGS_GROUP,
			self::OPTION_NAME,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( __CLASS__, 'sanitize_settings' ),
				'default'           => self::defaults(),
			)
		);
	}

	public static function sanitize_settings( $input ): array {
		$input = is_array( $input ) ? $input : array();
		$old   = self::get_settings();

		$host = isset( $input['ws_host'] ) ? trim( sanitize_text_field( wp_unslash( $input['ws_host'] ) ) ) : '';
		$host = preg_replace( '#^wss?://#i', '', $host );
		$host = preg_replace( '#^https?://#i', '', $host );
		$host = trim( (string) $host, "/ \t\n\r\0\x0B" );
		// Chỉ lấy hostname/IP; path cấu hình riêng ở ws_path.
		if ( false !== strpos( $host, '/' ) ) {
			$host = strtok( $host, '/' );
		}

		$path = isset( $input['ws_path'] ) ? trim( sanitize_text_field( wp_unslash( $input['ws_path'] ) ) ) : '';
		if ( '' !== $path && '/' !== $path[0] ) {
			$path = '/' . $path;
		}
		$path = '/' === $path ? '' : rtrim( $path, '/' );

		$secret = isset( $input['app_secret'] ) ? trim( sanitize_text_field( wp_unslash( $input['app_secret'] ) ) ) : '';
		if ( '' === $secret && empty( $input['clear_secret'] ) ) {
			$secret = (string) $old['app_secret'];
		}
		if ( ! empty( $input['clear_secret'] ) ) {
			$secret = '';
		}

		$ws_port  = isset( $input['ws_port'] ) ? absint( $input['ws_port'] ) : 6001;
		$wss_port = isset( $input['wss_port'] ) ? absint( $input['wss_port'] ) : 443;
		$ws_port  = ( $ws_port >= 1 && $ws_port <= 65535 ) ? $ws_port : 6001;
		$wss_port = ( $wss_port >= 1 && $wss_port <= 65535 ) ? $wss_port : 443;

		return array(
			'enabled'    => empty( $input['enabled'] ) ? 0 : 1,
			'app_key'    => isset( $input['app_key'] ) ? sanitize_text_field( wp_unslash( $input['app_key'] ) ) : '',
			'app_secret' => $secret,
			'ws_host'    => $host,
			'ws_port'    => $ws_port,
			'wss_port'   => $wss_port,
			'force_tls'  => empty( $input['force_tls'] ) ? 0 : 1,
			'ws_path'    => $path,
		);
	}

	public static function register_settings_page(): void {
		add_submenu_page(
			'edit.php?post_type=nntm_zen_track',
			__( 'Realtime Thiền Đường', 'nntm-zen-track-audio' ),
			__( 'Realtime Thiền Đường', 'nntm-zen-track-audio' ),
			'manage_options',
			'nntm-zen-track-realtime',
			array( __CLASS__, 'render_settings_page' )
		);
	}

	public static function render_settings_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Bạn không có quyền cấu hình realtime.', 'nntm-zen-track-audio' ) );
		}
		$s = self::get_settings();
		$ready = self::is_ready();
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Realtime Thiền Đường — Soketi', 'nntm-zen-track-audio' ); ?></h1>
			<p><?php esc_html_e( 'Soketi dùng giao thức Pusher. App key/secret dưới đây phải trùng với app cấu hình trên Soketi. Secret chỉ được dùng ở server WordPress để ký Presence Channel.', 'nntm-zen-track-audio' ); ?></p>
			<p><strong><?php esc_html_e( 'Trạng thái cấu hình:', 'nntm-zen-track-audio' ); ?></strong> <?php echo $ready ? '<span style="color:#16803c">' . esc_html__( 'Sẵn sàng', 'nntm-zen-track-audio' ) . '</span>' : '<span style="color:#b32d2e">' . esc_html__( 'Chưa đủ thông tin', 'nntm-zen-track-audio' ) . '</span>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></p>
			<form method="post" action="options.php">
				<?php settings_fields( self::SETTINGS_GROUP ); ?>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php esc_html_e( 'Bật realtime', 'nntm-zen-track-audio' ); ?></th>
						<td><label><input type="checkbox" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[enabled]" value="1" <?php checked( ! empty( $s['enabled'] ) ); ?>> <?php esc_html_e( 'Hiển thị số người đang ở Thiền Đường và đang nghe bài hiện tại', 'nntm-zen-track-audio' ); ?></label></td>
					</tr>
					<tr>
						<th scope="row"><label for="nntm-rt-key">App key</label></th>
						<td><input id="nntm-rt-key" class="regular-text code" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[app_key]" value="<?php echo esc_attr( (string) $s['app_key'] ); ?>" autocomplete="off"></td>
					</tr>
					<tr>
						<th scope="row"><label for="nntm-rt-secret">App secret</label></th>
						<td>
							<input id="nntm-rt-secret" class="regular-text code" type="password" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[app_secret]" value="" autocomplete="new-password" placeholder="<?php echo esc_attr( empty( $s['app_secret'] ) ? __( 'Chưa cấu hình', 'nntm-zen-track-audio' ) : __( 'Đã lưu — để trống để giữ nguyên', 'nntm-zen-track-audio' ) ); ?>">
							<label style="display:block;margin-top:6px"><input type="checkbox" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[clear_secret]" value="1"> <?php esc_html_e( 'Xóa secret hiện tại', 'nntm-zen-track-audio' ); ?></label>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="nntm-rt-host">WebSocket host</label></th>
						<td><input id="nntm-rt-host" class="regular-text code" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[ws_host]" value="<?php echo esc_attr( (string) $s['ws_host'] ); ?>" placeholder="socket.nntm.com"><p class="description"><?php esc_html_e( 'Chỉ hostname/IP, không nhập https:// hoặc wss://.', 'nntm-zen-track-audio' ); ?></p></td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Cổng WebSocket', 'nntm-zen-track-audio' ); ?></th>
						<td>
							<label>WS <input class="small-text" type="number" min="1" max="65535" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[ws_port]" value="<?php echo esc_attr( (string) $s['ws_port'] ); ?>"></label>&nbsp;&nbsp;
							<label>WSS <input class="small-text" type="number" min="1" max="65535" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[wss_port]" value="<?php echo esc_attr( (string) $s['wss_port'] ); ?>"></label>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="nntm-rt-path">WebSocket path</label></th>
						<td><input id="nntm-rt-path" class="regular-text code" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[ws_path]" value="<?php echo esc_attr( (string) $s['ws_path'] ); ?>" placeholder=""><p class="description"><?php esc_html_e( 'Để trống với cấu hình Soketi chuẩn. Chỉ dùng khi reverse proxy đặt WebSocket dưới một prefix riêng.', 'nntm-zen-track-audio' ); ?></p></td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'TLS', 'nntm-zen-track-audio' ); ?></th>
						<td><label><input type="checkbox" name="<?php echo esc_attr( self::OPTION_NAME ); ?>[force_tls]" value="1" <?php checked( ! empty( $s['force_tls'] ) ); ?>> <?php esc_html_e( 'Dùng WSS/TLS (khuyên bật trên website HTTPS)', 'nntm-zen-track-audio' ); ?></label></td>
					</tr>
				</table>
				<?php submit_button(); ?>
			</form>
			<hr>
			<h2><?php esc_html_e( 'Kênh realtime sử dụng', 'nntm-zen-track-audio' ); ?></h2>
			<code><?php echo esc_html( self::PAGE_CHANNEL ); ?></code> — <?php esc_html_e( 'tổng user đang ở trang', 'nntm-zen-track-audio' ); ?><br>
			<code><?php echo esc_html( self::TRACK_CHANNEL_PREFIX ); ?>{POST_ID}</code> — <?php esc_html_e( 'user đang thực sự phát từng nntm_zen_track', 'nntm-zen-track-audio' ); ?>
		</div>
		<?php
	}

	public static function enqueue_assets(): void {
		if ( self::$assets_enqueued || ! is_user_logged_in() || ! self::is_ready() ) {
			return;
		}
		self::$assets_enqueued = true;

		$settings = self::get_settings();
		$pusher_src = (string) apply_filters( 'nntm_zen_track_pusher_js_src', 'https://js.pusher.com/' . self::PUSHER_JS_VERSION . '/pusher.min.js' );
		wp_enqueue_script( 'nntm-pusher-js', esc_url( $pusher_src ), array(), self::PUSHER_JS_VERSION, true );
		wp_enqueue_script(
			'nntm-zen-track-realtime',
			plugins_url( 'assets/realtime.js', defined( 'NNTM_ZEN_TRACK_MANAGER_FILE' ) ? NNTM_ZEN_TRACK_MANAGER_FILE : dirname( __DIR__ ) . '/nntm-zen-track-audio-admin.php' ),
			array( 'nntm-pusher-js' ),
			defined( 'NNTM_ZEN_TRACK_MANAGER_VERSION' ) ? NNTM_ZEN_TRACK_MANAGER_VERSION : null,
			true
		);

		$config = array(
			'enabled'       => true,
			'appKey'        => (string) $settings['app_key'],
			'wsHost'        => (string) $settings['ws_host'],
			'wsPort'        => absint( $settings['ws_port'] ),
			'wssPort'       => absint( $settings['wss_port'] ),
			'forceTLS'      => ! empty( $settings['force_tls'] ),
			'wsPath'        => (string) $settings['ws_path'],
			'authEndpoint'  => admin_url( 'admin-ajax.php' ),
			'authAction'    => self::AUTH_ACTION,
			'authNonce'     => wp_create_nonce( self::AUTH_NONCE_ACTION ),
			'pageChannel'   => self::PAGE_CHANNEL,
			'trackPrefix'   => self::TRACK_CHANNEL_PREFIX,
		);
		wp_add_inline_script( 'nntm-zen-track-realtime', 'window.NNTMZenTrackRealtime=' . wp_json_encode( $config ) . ';', 'before' );
	}

	public static function ajax_authorize_presence(): void {
		check_ajax_referer( self::AUTH_NONCE_ACTION, 'nonce' );

		if ( ! is_user_logged_in() || ! self::is_ready() ) {
			wp_send_json( array( 'error' => 'forbidden' ), 403 );
		}

		$socket_id    = isset( $_POST['socket_id'] ) ? sanitize_text_field( wp_unslash( $_POST['socket_id'] ) ) : '';
		$channel_name = isset( $_POST['channel_name'] ) ? sanitize_text_field( wp_unslash( $_POST['channel_name'] ) ) : '';
		if ( ! preg_match( '/^\d+\.\d+$/', $socket_id ) || ! self::channel_is_allowed( $channel_name ) ) {
			wp_send_json( array( 'error' => 'invalid_channel' ), 400 );
		}

		$settings = self::get_settings();
		$user_id  = get_current_user_id();
		$channel_data = wp_json_encode(
			array(
				'user_id'   => (string) $user_id,
				// Không gửi display_name/email/avatar vì giao diện hiện chỉ cần số lượng.
				'user_info' => (object) array(),
			),
			JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
		);
		$string_to_sign = $socket_id . ':' . $channel_name . ':' . $channel_data;
		$signature      = hash_hmac( 'sha256', $string_to_sign, (string) $settings['app_secret'] );

		wp_send_json(
			array(
				'auth'         => (string) $settings['app_key'] . ':' . $signature,
				'channel_data' => $channel_data,
			)
		);
	}

	private static function channel_is_allowed( string $channel_name ): bool {
		if ( self::PAGE_CHANNEL === $channel_name ) {
			return true;
		}

		$pattern = '/^' . preg_quote( self::TRACK_CHANNEL_PREFIX, '/' ) . '(\d+)$/';
		if ( ! preg_match( $pattern, $channel_name, $matches ) ) {
			return false;
		}
		$track_id = absint( $matches[1] ?? 0 );
		if ( ! $track_id || 'nntm_zen_track' !== get_post_type( $track_id ) || 'publish' !== get_post_status( $track_id ) ) {
			return false;
		}
		$audio_id = absint( get_post_meta( $track_id, '_nntm_track_audio', true ) );
		return $audio_id > 0 && 'attachment' === get_post_type( $audio_id ) && 0 === strpos( (string) get_post_mime_type( $audio_id ), 'audio/' );
	}
}
