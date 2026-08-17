<?php
/**
 * Plugin Name: NNTM Zen Track Manager
 * Description: Quản lý audio, thống kê lượt nghe và realtime Soketi Presence cho CPT nntm_zen_track.
 * Version: 2.1.0
 * Author: NNTM
 * Text Domain: nntm-zen-track-audio
 */

defined( 'ABSPATH' ) || exit;

define( 'NNTM_ZEN_TRACK_MANAGER_VERSION', '2.1.0' );
define( 'NNTM_ZEN_TRACK_MANAGER_FILE', __FILE__ );
require_once __DIR__ . '/includes/class-nntm-zen-track-realtime.php';

final class NNTM_Zen_Track_Audio_Admin {
	private const VERSION          = '2.1.0';
	private const POST_TYPE        = 'nntm_zen_track';
	private const AUDIO_META       = '_nntm_track_audio';
	private const LEGACY_COUNT_META = '_nntm_track_listen_count';
	private const NONCE            = 'nntm_zen_track_audio_nonce';
	private const SAVE_ACTION      = 'nntm_save_zen_track_audio';
	private const LISTEN_ACTION    = 'nntm_track_listen';

	private static ?bool $table_exists = null;

	public static function init(): void {
		add_action( 'init', array( __CLASS__, 'register_meta_and_supports' ), 30 );
		add_action( 'admin_init', array( __CLASS__, 'maybe_upgrade' ) );
		add_action( 'add_meta_boxes_' . self::POST_TYPE, array( __CLASS__, 'add_fallback_meta_box' ) );
		add_action( 'save_post_' . self::POST_TYPE, array( __CLASS__, 'save_fallback_meta_box' ), 10, 3 );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_admin_assets' ) );
		add_action( 'enqueue_block_editor_assets', array( __CLASS__, 'enqueue_block_editor_assets' ) );

		add_filter( 'manage_' . self::POST_TYPE . '_posts_columns', array( __CLASS__, 'add_admin_columns' ) );
		add_action( 'manage_' . self::POST_TYPE . '_posts_custom_column', array( __CLASS__, 'render_admin_column' ), 10, 2 );
		add_action( 'admin_menu', array( __CLASS__, 'register_stats_page' ) );

		// Player chỉ cho thành viên đã đăng nhập nghe, nên không mở wp_ajax_nopriv.
		add_action( 'wp_ajax_' . self::LISTEN_ACTION, array( __CLASS__, 'ajax_record_listen' ) );
	}

	public static function activate(): void {
		self::create_stats_table();
		self::migrate_legacy_counts();
		update_option( 'nntm_zen_track_manager_version', self::VERSION, false );
	}

	public static function maybe_upgrade(): void {
		$installed = (string) get_option( 'nntm_zen_track_manager_version', '' );
		if ( self::VERSION === $installed && self::has_stats_table() ) {
			return;
		}
		self::create_stats_table();
		self::migrate_legacy_counts();
		update_option( 'nntm_zen_track_manager_version', self::VERSION, false );
	}

	private static function table_name(): string {
		global $wpdb;
		return $wpdb->prefix . 'nntm_zen_track_stats';
	}

	private static function create_stats_table(): void {
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$table   = self::table_name();
		$charset = $wpdb->get_charset_collate();
		$sql     = "CREATE TABLE {$table} (
			track_id BIGINT(20) UNSIGNED NOT NULL,
			listen_count BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
			last_listened_at DATETIME NULL DEFAULT NULL,
			updated_at DATETIME NOT NULL,
			PRIMARY KEY  (track_id),
			KEY listen_count (listen_count),
			KEY last_listened_at (last_listened_at)
		) {$charset};";

		dbDelta( $sql );
		self::$table_exists = null;
	}

	private static function has_stats_table(): bool {
		if ( null !== self::$table_exists ) {
			return self::$table_exists;
		}

		global $wpdb;
		$table = self::table_name();
		$found = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) );
		self::$table_exists = ( $found === $table );
		return self::$table_exists;
	}

	private static function migrate_legacy_counts(): void {
		if ( ! self::has_stats_table() ) {
			return;
		}

		global $wpdb;
		$table = self::table_name();
		$rows  = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT pm.post_id, MAX(CAST(pm.meta_value AS UNSIGNED)) AS legacy_count
				 FROM {$wpdb->postmeta} pm
				 INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
				 WHERE p.post_type = %s
				   AND pm.meta_key = %s
				   AND CAST(pm.meta_value AS UNSIGNED) > 0
				 GROUP BY pm.post_id",
				self::POST_TYPE,
				self::LEGACY_COUNT_META
			)
		);

		$now = current_time( 'mysql' );
		foreach ( $rows as $row ) {
			$track_id = absint( $row->post_id ?? 0 );
			$count    = absint( $row->legacy_count ?? 0 );
			if ( ! $track_id || ! $count ) {
				continue;
			}

			$wpdb->query(
				$wpdb->prepare(
					"INSERT INTO {$table} (track_id, listen_count, last_listened_at, updated_at)
					 VALUES (%d, %d, NULL, %s)
					 ON DUPLICATE KEY UPDATE
					 listen_count = GREATEST(listen_count, VALUES(listen_count)),
					 updated_at = VALUES(updated_at)",
					$track_id,
					$count,
					$now
				)
			);
		}
	}

	public static function register_meta_and_supports(): void {
		if ( post_type_exists( self::POST_TYPE ) ) {
			// Featured Image là ảnh đại diện của từng bài nhạc.
			add_post_type_support( self::POST_TYPE, 'thumbnail' );

			/*
			 * BẮT BUỘC với post meta được lưu qua Gutenberg/REST.
			 * Nếu CPT không support `custom-fields`, REST controller không persist
			 * _nntm_track_audio: editor vẫn hiện file vừa chọn nhưng reload sẽ mất.
			 */
			add_post_type_support( self::POST_TYPE, 'custom-fields' );
		}

		register_post_meta(
			self::POST_TYPE,
			self::AUDIO_META,
			array(
				'type'         => 'integer',
				'single'       => true,
				'default'      => 0,
				'show_in_rest' => array(
					'schema' => array(
						'type'    => 'integer',
						'minimum' => 0,
					),
				),
				'sanitize_callback' => static function ( $value ): int {
					$attachment_id = absint( $value );
					if ( 0 === $attachment_id ) {
						return 0;
					}

					if ( 'attachment' !== get_post_type( $attachment_id ) ) {
						return 0;
					}

					$mime = (string) get_post_mime_type( $attachment_id );
					return 0 === strpos( $mime, 'audio/' ) ? $attachment_id : 0;
				},
				'auth_callback' => static function ( $allowed, $meta_key, $post_id ): bool {
					unset( $allowed, $meta_key );
					$post_id = absint( $post_id );
					return $post_id > 0 && current_user_can( 'edit_post', $post_id );
				},
			)
		);
	}

	public static function add_fallback_meta_box( WP_Post $post ): void {
		unset( $post );

		if ( function_exists( 'use_block_editor_for_post_type' ) && use_block_editor_for_post_type( self::POST_TYPE ) ) {
			return;
		}

		add_meta_box(
			'nntm-zen-track-audio',
			__( 'Tệp âm thanh', 'nntm-zen-track-audio' ),
			array( __CLASS__, 'render_fallback_meta_box' ),
			self::POST_TYPE,
			'side',
			'high'
		);
	}

	public static function render_fallback_meta_box( WP_Post $post ): void {
		$audio_id = absint( get_post_meta( $post->ID, self::AUDIO_META, true ) );
		$audio    = self::get_audio_attachment( $audio_id );

		wp_nonce_field( self::SAVE_ACTION, self::NONCE );
		?>
		<div class="nntm-zen-audio-field" data-nntm-zen-audio-field>
			<input type="hidden" name="nntm_zen_track_audio_id" value="<?php echo esc_attr( (string) $audio_id ); ?>" data-nntm-audio-id>
			<div class="nntm-zen-audio-current" data-nntm-audio-current <?php echo $audio ? '' : 'hidden'; ?>>
				<strong data-nntm-audio-name><?php echo $audio ? esc_html( self::attachment_label( $audio ) ) : ''; ?></strong>
				<audio controls preload="metadata" src="<?php echo $audio ? esc_url( wp_get_attachment_url( $audio->ID ) ) : ''; ?>" data-nntm-audio-preview></audio>
			</div>
			<p class="description" data-nntm-audio-empty <?php echo $audio ? 'hidden' : ''; ?>><?php esc_html_e( 'Chưa chọn tệp âm thanh.', 'nntm-zen-track-audio' ); ?></p>
			<p>
				<button type="button" class="button button-secondary" data-nntm-audio-select><?php echo $audio ? esc_html__( 'Đổi tệp âm thanh', 'nntm-zen-track-audio' ) : esc_html__( 'Chọn tệp âm thanh', 'nntm-zen-track-audio' ); ?></button>
				<button type="button" class="button-link-delete" data-nntm-audio-remove <?php echo $audio ? '' : 'hidden'; ?>><?php esc_html_e( 'Gỡ', 'nntm-zen-track-audio' ); ?></button>
			</p>
			<p class="description"><?php esc_html_e( 'Tên bài lấy từ Tiêu đề post. Ảnh bài lấy từ Ảnh đại diện. Tệp này được block Thiền Đường phát.', 'nntm-zen-track-audio' ); ?></p>
		</div>
		<?php
	}

	public static function save_fallback_meta_box( int $post_id, WP_Post $post, bool $update ): void {
		unset( $update );

		if ( wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) ) {
			return;
		}
		if ( self::POST_TYPE !== $post->post_type || ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$nonce = isset( $_POST[ self::NONCE ] ) ? sanitize_text_field( wp_unslash( $_POST[ self::NONCE ] ) ) : '';
		if ( ! $nonce || ! wp_verify_nonce( $nonce, self::SAVE_ACTION ) ) {
			return;
		}

		$audio_id = isset( $_POST['nntm_zen_track_audio_id'] ) ? absint( wp_unslash( $_POST['nntm_zen_track_audio_id'] ) ) : 0;
		if ( 0 === $audio_id ) {
			delete_post_meta( $post_id, self::AUDIO_META );
			return;
		}
		if ( self::get_audio_attachment( $audio_id ) ) {
			update_post_meta( $post_id, self::AUDIO_META, $audio_id );
		}
	}

	public static function enqueue_admin_assets( string $hook_suffix ): void {
		if ( ! in_array( $hook_suffix, array( 'post.php', 'post-new.php' ), true ) ) {
			return;
		}
		$screen = get_current_screen();
		if ( ! $screen || self::POST_TYPE !== $screen->post_type ) {
			return;
		}
		if ( function_exists( 'use_block_editor_for_post_type' ) && use_block_editor_for_post_type( self::POST_TYPE ) ) {
			return;
		}

		wp_enqueue_media();
		wp_enqueue_script( 'nntm-zen-track-audio-admin', plugins_url( 'assets/admin.js', __FILE__ ), array( 'jquery' ), self::VERSION, true );
		wp_enqueue_style( 'nntm-zen-track-audio-admin', plugins_url( 'assets/admin.css', __FILE__ ), array(), self::VERSION );
	}

	public static function enqueue_block_editor_assets(): void {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen || self::POST_TYPE !== $screen->post_type ) {
			return;
		}

		wp_enqueue_media();
		wp_enqueue_script(
			'nntm-zen-track-audio-sidebar',
			plugins_url( 'assets/editor-sidebar.js', __FILE__ ),
			array( 'wp-block-editor', 'wp-components', 'wp-core-data', 'wp-data', 'wp-edit-post', 'wp-element', 'wp-i18n', 'wp-plugins' ),
			self::VERSION,
			true
		);
		wp_enqueue_style( 'nntm-zen-track-audio-admin', plugins_url( 'assets/admin.css', __FILE__ ), array(), self::VERSION );
	}

	public static function add_admin_columns( array $columns ): array {
		$result = array();
		foreach ( $columns as $key => $label ) {
			$result[ $key ] = $label;
			if ( 'title' === $key ) {
				$result['nntm_track_image']   = __( 'Ảnh', 'nntm-zen-track-audio' );
				$result['nntm_track_audio']   = __( 'Tệp âm thanh', 'nntm-zen-track-audio' );
				$result['nntm_track_listens'] = __( 'Lượt nghe', 'nntm-zen-track-audio' );
			}
		}
		return $result;
	}

	public static function render_admin_column( string $column, int $post_id ): void {
		if ( 'nntm_track_image' === $column ) {
			if ( has_post_thumbnail( $post_id ) ) {
				echo wp_kses_post( get_the_post_thumbnail( $post_id, array( 56, 56 ), array( 'style' => 'width:56px;height:56px;object-fit:cover;border-radius:6px;' ) ) );
			} else {
				echo '<span aria-hidden="true">—</span>';
			}
			return;
		}

		if ( 'nntm_track_audio' === $column ) {
			$audio_id = absint( get_post_meta( $post_id, self::AUDIO_META, true ) );
			$audio    = self::get_audio_attachment( $audio_id );
			if ( ! $audio ) {
				echo '<span aria-hidden="true">—</span><span class="screen-reader-text">' . esc_html__( 'Chưa có tệp âm thanh', 'nntm-zen-track-audio' ) . '</span>';
				return;
			}
			$url = wp_get_attachment_url( $audio->ID );
			if ( $url ) {
				echo '<a href="' . esc_url( $url ) . '" target="_blank" rel="noopener noreferrer">' . esc_html( self::attachment_label( $audio ) ) . '</a>';
			} else {
				echo esc_html( self::attachment_label( $audio ) );
			}
			return;
		}

		if ( 'nntm_track_listens' === $column ) {
			echo esc_html( number_format_i18n( self::get_listen_count( $post_id ) ) );
		}
	}

	public static function register_stats_page(): void {
		add_submenu_page(
			'edit.php?post_type=' . self::POST_TYPE,
			__( 'Thống kê lượt nghe', 'nntm-zen-track-audio' ),
			__( 'Thống kê lượt nghe', 'nntm-zen-track-audio' ),
			'edit_posts',
			'nntm-zen-track-stats',
			array( __CLASS__, 'render_stats_page' )
		);
	}

	public static function render_stats_page(): void {
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_die( esc_html__( 'Bạn không có quyền xem trang này.', 'nntm-zen-track-audio' ) );
		}

		global $wpdb;
		$table = self::table_name();
		$rows  = array();

		if ( self::has_stats_table() ) {
			$rows = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT p.ID, p.post_title, p.post_status,
					 COALESCE(s.listen_count, legacy.legacy_count, 0) AS listen_count,
					 s.last_listened_at
					 FROM {$wpdb->posts} p
					 LEFT JOIN {$table} s ON s.track_id = p.ID
					 LEFT JOIN (
						SELECT post_id, MAX(CAST(meta_value AS UNSIGNED)) AS legacy_count
						FROM {$wpdb->postmeta}
						WHERE meta_key = %s
						GROUP BY post_id
					 ) legacy ON legacy.post_id = p.ID
					 WHERE p.post_type = %s AND p.post_status NOT IN ('trash','auto-draft')
					 ORDER BY listen_count DESC, p.post_title ASC",
					self::LEGACY_COUNT_META,
					self::POST_TYPE
				)
			);
		}

		$total = 0;
		foreach ( $rows as $row ) {
			$total += absint( $row->listen_count ?? 0 );
		}
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Thống kê lượt nghe — Nhạc Thiền', 'nntm-zen-track-audio' ); ?></h1>
			<p><strong><?php esc_html_e( 'Tổng lượt nghe:', 'nntm-zen-track-audio' ); ?></strong> <?php echo esc_html( number_format_i18n( $total ) ); ?></p>
			<table class="widefat striped">
				<thead><tr>
					<th><?php esc_html_e( 'Bài nhạc', 'nntm-zen-track-audio' ); ?></th>
					<th><?php esc_html_e( 'Tệp âm thanh', 'nntm-zen-track-audio' ); ?></th>
					<th><?php esc_html_e( 'Lượt nghe', 'nntm-zen-track-audio' ); ?></th>
					<th><?php esc_html_e( 'Nghe gần nhất', 'nntm-zen-track-audio' ); ?></th>
				</tr></thead>
				<tbody>
				<?php if ( empty( $rows ) ) : ?>
					<tr><td colspan="4"><?php esc_html_e( 'Chưa có dữ liệu.', 'nntm-zen-track-audio' ); ?></td></tr>
				<?php else : foreach ( $rows as $row ) :
					$track_id = absint( $row->ID );
					$audio_id = absint( get_post_meta( $track_id, self::AUDIO_META, true ) );
					$audio    = self::get_audio_attachment( $audio_id );
					$edit_url = get_edit_post_link( $track_id );
				?>
					<tr>
						<td>
							<?php if ( has_post_thumbnail( $track_id ) ) { echo wp_kses_post( get_the_post_thumbnail( $track_id, array( 40, 40 ), array( 'style' => 'width:40px;height:40px;object-fit:cover;border-radius:5px;vertical-align:middle;margin-right:8px;' ) ) ); } ?>
							<a href="<?php echo esc_url( $edit_url ?: '#' ); ?>"><strong><?php echo esc_html( $row->post_title ?: sprintf( '#%d', $track_id ) ); ?></strong></a>
						</td>
						<td><?php echo $audio ? esc_html( self::attachment_label( $audio ) ) : '—'; ?></td>
						<td><strong><?php echo esc_html( number_format_i18n( absint( $row->listen_count ) ) ); ?></strong></td>
						<td><?php echo ! empty( $row->last_listened_at ) ? esc_html( mysql2date( 'd/m/Y H:i', $row->last_listened_at ) ) : '—'; ?></td>
					</tr>
				<?php endforeach; endif; ?>
				</tbody>
			</table>
			<p class="description"><?php esc_html_e( 'Một lượt được ghi sau khi bài thực sự phát liên tục ít nhất 5 giây. Pause/Play tiếp cùng phiên không cộng lại; khi chuyển sang bài khác (kể cả auto-next), bài mới sẽ có phiên nghe riêng và được cộng sau 5 giây.', 'nntm-zen-track-audio' ); ?></p>
		</div>
		<?php
	}

	public static function ajax_record_listen(): void {
		check_ajax_referer( self::LISTEN_ACTION, 'nonce' );

		if ( ! is_user_logged_in() ) {
			wp_send_json_error( array( 'message' => __( 'Vui lòng đăng nhập.', 'nntm-zen-track-audio' ) ), 401 );
		}

		$track_id = isset( $_POST['track_id'] ) ? absint( wp_unslash( $_POST['track_id'] ) ) : 0;
		if ( ! $track_id || self::POST_TYPE !== get_post_type( $track_id ) || 'publish' !== get_post_status( $track_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Bài nhạc không hợp lệ.', 'nntm-zen-track-audio' ) ), 400 );
		}

		$audio_id = absint( get_post_meta( $track_id, self::AUDIO_META, true ) );
		if ( ! self::get_audio_attachment( $audio_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Bài nhạc chưa có tệp âm thanh hợp lệ.', 'nntm-zen-track-audio' ) ), 400 );
		}

		// Mỗi lần load/chuyển bài ở frontend có một listen_session riêng.
		// Cùng một session chỉ được ghi một lần, nhưng nghe lại bài sau khi tạo
		// session mới vẫn được tính. Rate-limit 3 giây chỉ để chặn request dồn dập.
		$user_id        = get_current_user_id();
		$listen_session = isset( $_POST['listen_session'] ) ? sanitize_text_field( wp_unslash( $_POST['listen_session'] ) ) : '';
		if ( ! preg_match( '/^[A-Za-z0-9_-]{12,80}$/', $listen_session ) ) {
			wp_send_json_error( array( 'message' => __( 'Phiên nghe không hợp lệ.', 'nntm-zen-track-audio' ) ), 400 );
		}

		$session_key = 'nntm_track_session_' . md5( $user_id . '|' . $track_id . '|' . $listen_session );
		if ( get_transient( $session_key ) ) {
			wp_send_json_success( array( 'track_id' => $track_id, 'count' => self::get_listen_count( $track_id ), 'deduped' => true ) );
		}

		$rate_key = 'nntm_track_rate_' . $user_id . '_' . $track_id;
		if ( get_transient( $rate_key ) ) {
			wp_send_json_success( array( 'track_id' => $track_id, 'count' => self::get_listen_count( $track_id ), 'rate_limited' => true ) );
		}

		set_transient( $session_key, 1, 2 * HOUR_IN_SECONDS );
		set_transient( $rate_key, 1, 3 );

		$count = self::increment_listen_count( $track_id );
		wp_send_json_success( array( 'track_id' => $track_id, 'count' => $count ) );
	}

	private static function increment_listen_count( int $track_id ): int {
		if ( ! self::has_stats_table() ) {
			self::create_stats_table();
		}
		if ( ! self::has_stats_table() ) {
			return self::get_listen_count( $track_id );
		}

		global $wpdb;
		$table = self::table_name();
		$now   = current_time( 'mysql' );

		$wpdb->query(
			$wpdb->prepare(
				"INSERT INTO {$table} (track_id, listen_count, last_listened_at, updated_at)
				 VALUES (%d, 1, %s, %s)
				 ON DUPLICATE KEY UPDATE
				 listen_count = listen_count + 1,
				 last_listened_at = VALUES(last_listened_at),
				 updated_at = VALUES(updated_at)",
				$track_id,
				$now,
				$now
			)
		);

		return self::get_listen_count( $track_id );
	}

	public static function get_listen_count( int $track_id ): int {
		$track_id = absint( $track_id );
		if ( ! $track_id ) {
			return 0;
		}

		if ( self::has_stats_table() ) {
			global $wpdb;
			$table = self::table_name();
			$value = $wpdb->get_var( $wpdb->prepare( "SELECT listen_count FROM {$table} WHERE track_id = %d", $track_id ) );
			if ( null !== $value ) {
				return absint( $value );
			}
		}

		return absint( get_post_meta( $track_id, self::LEGACY_COUNT_META, true ) );
	}

	private static function get_audio_attachment( int $attachment_id ): ?WP_Post {
		if ( $attachment_id <= 0 || 'attachment' !== get_post_type( $attachment_id ) ) {
			return null;
		}
		$mime = (string) get_post_mime_type( $attachment_id );
		if ( 0 !== strpos( $mime, 'audio/' ) ) {
			return null;
		}
		$attachment = get_post( $attachment_id );
		return $attachment instanceof WP_Post ? $attachment : null;
	}

	private static function attachment_label( WP_Post $attachment ): string {
		$path = get_attached_file( $attachment->ID );
		if ( $path ) {
			return wp_basename( $path );
		}
		return $attachment->post_title ?: sprintf( '#%d', $attachment->ID );
	}
}

register_activation_hook( __FILE__, array( 'NNTM_Zen_Track_Audio_Admin', 'activate' ) );
NNTM_Zen_Track_Audio_Admin::init();
NNTM_Zen_Track_Realtime::init();

/**
 * API nhẹ để theme/block lấy tổng lượt nghe mà không phụ thuộc nơi lưu trữ.
 */
if ( ! function_exists( 'nntm_zen_track_get_listen_count' ) ) {
	function nntm_zen_track_get_listen_count( int $track_id ): int {
		return NNTM_Zen_Track_Audio_Admin::get_listen_count( $track_id );
	}
}


/**
 * Bridge nhẹ để block theme chỉ enqueue realtime khi player thực sự render.
 */
if ( ! function_exists( 'nntm_zen_track_realtime_is_ready' ) ) {
	function nntm_zen_track_realtime_is_ready(): bool {
		return NNTM_Zen_Track_Realtime::is_ready();
	}
}

if ( ! function_exists( 'nntm_zen_track_enqueue_realtime_assets' ) ) {
	function nntm_zen_track_enqueue_realtime_assets(): void {
		NNTM_Zen_Track_Realtime::enqueue_assets();
	}
}
