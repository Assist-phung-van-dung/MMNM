<?php
/**
 * Danh sách nhạc nền dùng chung cho trình đọc Ấn phẩm và Nghi Quỹ.
 *
 * Dữ liệu nằm trong plugin để đổi theme không làm mất danh sách. Theme chỉ
 * chịu trách nhiệm hiển thị và phát nhạc ở trang đọc.
 *
 * @package NNTM_Core
 */

namespace NNTM\Core;

defined( 'ABSPATH' ) || exit;

/**
 * Quản lý thư viện nhạc nền của ấn phẩm.
 */
class Publication_Music {

	/** Khoá option lưu danh sách theo đúng thứ tự quản trị đã sắp. */
	public const OPTION = 'nntm_publication_music';

	/** @var Publication_Music|null */
	private static ?Publication_Music $instance = null;

	/** @var string Hook của trang quản trị để chỉ nạp asset đúng nơi. */
	private string $admin_hook = '';

	/** Lấy đối tượng dùng chung. */
	public static function instance(): Publication_Music {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/** Gắn hook WordPress. */
	public function hooks(): void {
		add_action( 'admin_menu', array( $this, 'add_admin_page' ), 30 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
	}

	/** Thêm trang List nhạc vào menu Ấn phẩm. */
	public function add_admin_page(): void {
		$this->admin_hook = (string) add_submenu_page(
			'edit.php?post_type=nntm_publication',
			__( 'List nhạc nền ấn phẩm', 'nntm' ),
			__( 'List nhạc', 'nntm' ),
			'manage_options',
			'nntm-list-nhac',
			array( $this, 'render_admin_page' )
		);
	}

	/**
	 * Chuẩn hoá dữ liệu trước khi lưu hoặc đưa ra giao diện.
	 *
	 * Chỉ attachment audio có thật mới được giữ lại. URL không nhận trực tiếp
	 * từ biểu mẫu để tránh một quản trị viên vô tình lưu liên kết không an toàn.
	 *
	 * @param mixed $raw Dữ liệu thô.
	 * @return array<int,array{id:int,title:string,url:string,mime:string}>
	 */
	public static function sanitize_tracks( $raw ): array {
		if ( ! is_array( $raw ) ) {
			return array();
		}

		$tracks = array();

		foreach ( $raw as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}

			$id = isset( $row['attachment_id'] )
				? absint( $row['attachment_id'] )
				: ( isset( $row['id'] ) ? absint( $row['id'] ) : 0 );
			if ( $id <= 0 || 'attachment' !== get_post_type( $id ) ) {
				continue;
			}

			$mime = (string) get_post_mime_type( $id );
			$url  = (string) wp_get_attachment_url( $id );
			if ( '' === $url || 0 !== strpos( $mime, 'audio/' ) ) {
				continue;
			}

			$title = isset( $row['title'] ) ? sanitize_text_field( (string) $row['title'] ) : '';
			if ( '' === $title ) {
				$title = get_the_title( $id );
			}
			if ( '' === $title ) {
				$title = wp_basename( (string) get_attached_file( $id ) );
			}

			$tracks[] = array(
				'id'    => $id,
				'title' => $title,
				'url'   => esc_url_raw( $url ),
				'mime'  => sanitize_mime_type( $mime ),
			);
		}

		return $tracks;
	}

	/**
	 * Lấy danh sách sạch để frontend dùng.
	 *
	 * @return array<int,array{id:int,title:string,url:string,mime:string}>
	 */
	public static function get_tracks(): array {
		return self::sanitize_tracks( get_option( self::OPTION, array() ) );
	}

	/** Nạp bộ chọn Media và asset riêng của trang List nhạc. */
	public function enqueue_admin_assets( string $hook ): void {
		if ( '' === $this->admin_hook || $hook !== $this->admin_hook ) {
			return;
		}

		wp_enqueue_media();

		$css = NNTM_CORE_DIR . 'assets/css/publication-music-admin.css';
		$js  = NNTM_CORE_DIR . 'assets/js/publication-music-admin.js';

		wp_enqueue_style(
			'nntm-publication-music-admin',
			NNTM_CORE_URL . 'assets/css/publication-music-admin.css',
			array(),
			is_readable( $css ) ? (string) filemtime( $css ) : NNTM_CORE_VERSION
		);

		wp_enqueue_script(
			'nntm-publication-music-admin',
			NNTM_CORE_URL . 'assets/js/publication-music-admin.js',
			array(),
			is_readable( $js ) ? (string) filemtime( $js ) : NNTM_CORE_VERSION,
			true
		);

		wp_localize_script(
			'nntm-publication-music-admin',
			'nntmPublicationMusicAdmin',
			array(
				'chooseTitle'  => __( 'Chọn tệp nhạc', 'nntm' ),
				'chooseButton' => __( 'Dùng tệp nhạc này', 'nntm' ),
				'empty'        => __( 'Chưa có bài nhạc nào. Bấm “Thêm bài nhạc” để bắt đầu.', 'nntm' ),
			)
		);
	}

	/** In một dòng nhạc trong biểu mẫu quản trị. */
	private function render_track_row( string $index, array $track = array() ): void {
		$id       = isset( $track['id'] ) ? absint( $track['id'] ) : 0;
		$title    = isset( $track['title'] ) ? (string) $track['title'] : '';
		$url      = isset( $track['url'] ) ? (string) $track['url'] : '';
		$file     = $id > 0 ? wp_basename( (string) get_attached_file( $id ) ) : '';
		$co_nhac  = $id > 0 && '' !== $url;
		?>
		<li class="nntm-music-admin__row" data-nntm-music-row>
			<span class="nntm-music-admin__handle" aria-hidden="true">☰</span>
			<div class="nntm-music-admin__fields">
				<input type="hidden" name="nntm_music[<?php echo esc_attr( $index ); ?>][attachment_id]" value="<?php echo esc_attr( (string) $id ); ?>" data-nntm-music-id />
				<label>
					<span><?php esc_html_e( 'Tên hiển thị', 'nntm' ); ?></span>
					<input type="text" class="regular-text" name="nntm_music[<?php echo esc_attr( $index ); ?>][title]" value="<?php echo esc_attr( $title ); ?>" placeholder="<?php esc_attr_e( 'Ví dụ: Suối nguồn tĩnh lặng', 'nntm' ); ?>" data-nntm-music-title />
				</label>
				<div class="nntm-music-admin__file<?php echo $co_nhac ? ' has-audio' : ''; ?>" data-nntm-music-file>
					<audio controls preload="metadata"<?php echo $co_nhac ? '' : ' hidden'; ?> data-nntm-music-audio>
						<source src="<?php echo esc_url( $url ); ?>" />
					</audio>
					<span data-nntm-music-filename><?php echo $co_nhac ? esc_html( $file ) : esc_html__( 'Chưa chọn tệp nhạc', 'nntm' ); ?></span>
					<button type="button" class="button" data-nntm-music-choose><?php echo $co_nhac ? esc_html__( 'Đổi tệp', 'nntm' ) : esc_html__( 'Chọn tệp nhạc', 'nntm' ); ?></button>
				</div>
			</div>
			<div class="nntm-music-admin__actions">
				<button type="button" class="button" data-nntm-music-up title="<?php esc_attr_e( 'Đưa lên', 'nntm' ); ?>">↑</button>
				<button type="button" class="button" data-nntm-music-down title="<?php esc_attr_e( 'Đưa xuống', 'nntm' ); ?>">↓</button>
				<button type="button" class="button-link-delete" data-nntm-music-remove><?php esc_html_e( 'Xoá', 'nntm' ); ?></button>
			</div>
		</li>
		<?php
	}

	/** Hiển thị và xử lý trang quản trị. */
	public function render_admin_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Bạn không có quyền quản lý danh sách nhạc.', 'nntm' ) );
		}

		$saved = false;
		if ( isset( $_POST['nntm_luu_list_nhac'] ) ) {
			check_admin_referer( 'nntm_luu_list_nhac' );

			$raw    = isset( $_POST['nntm_music'] ) ? wp_unslash( $_POST['nntm_music'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- từng trường được lọc trong sanitize_tracks().
			$tracks = self::sanitize_tracks( $raw );
			update_option( self::OPTION, $tracks, false );
			$saved = true;
		}

		$tracks = self::get_tracks();
		?>
		<div class="wrap nntm-music-admin" data-nntm-music-admin>
			<h1><?php esc_html_e( 'List nhạc nền ấn phẩm', 'nntm' ); ?></h1>
			<p class="description"><?php esc_html_e( 'Danh sách này dùng chung trong trình đọc của mọi Ấn phẩm và Nghi Quỹ. Kéo thứ tự bằng nút lên/xuống; bài đầu tiên được chọn mặc định.', 'nntm' ); ?></p>

			<?php if ( $saved ) : ?>
				<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Đã lưu danh sách nhạc.', 'nntm' ); ?></p></div>
			<?php endif; ?>

			<form method="post">
				<?php wp_nonce_field( 'nntm_luu_list_nhac' ); ?>
				<p class="nntm-music-admin__empty" data-nntm-music-empty<?php echo $tracks ? ' hidden' : ''; ?>><?php esc_html_e( 'Chưa có bài nhạc nào. Bấm “Thêm bài nhạc” để bắt đầu.', 'nntm' ); ?></p>
				<ol class="nntm-music-admin__list" data-nntm-music-list>
					<?php
					foreach ( $tracks as $index => $track ) {
						$this->render_track_row( (string) $index, $track );
					}
					?>
				</ol>

				<div class="nntm-music-admin__footer">
					<button type="button" class="button button-secondary" data-nntm-music-add><?php esc_html_e( 'Thêm bài nhạc', 'nntm' ); ?></button>
					<button type="submit" class="button button-primary" name="nntm_luu_list_nhac" value="1"><?php esc_html_e( 'Lưu danh sách', 'nntm' ); ?></button>
				</div>
			</form>

			<template data-nntm-music-template><?php $this->render_track_row( '__INDEX__' ); ?></template>
		</div>
		<?php
	}
}
