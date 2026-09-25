<?php
/**
 * Từ khoá động — Phase 2, phiếu khảo sát câu 33–34.
 *
 * Ban quản trị khai báo danh sách từ khoá kèm hình minh hoạ, rồi tự chọn
 * trang trọng điểm nào được bật hiệu ứng (ô tích trong trình soạn thảo trang).
 * Rê chuột / chạm vào từ khoá trong nội dung trang đó thì hiện hình minh hoạ.
 *
 * Plugin chỉ giữ DỮ LIỆU: danh sách từ khoá, cờ bật theo trang. Việc dò chữ
 * và vẽ hiệu ứng nằm ở theme (inc/tu-khoa-dong.php) — đổi theme không mất danh
 * sách khách đã nhập.
 *
 * CPT để public = false: Polylang chỉ quản ngôn ngữ cho post type public
 * (tools/setup-polylang.php), nên danh sách dùng chung mọi ngôn ngữ. Từ khoá
 * tiếng Việt tự nhiên không khớp vào bài tiếng Anh.
 *
 * @package NNTM_Core
 */

namespace NNTM\Core;

defined( 'ABSPATH' ) || exit;

/**
 * Quản lý danh sách từ khoá động và cờ bật theo trang.
 */
class Tu_Khoa_Dong {

	/** Post type chứa danh sách từ khoá. */
	public const POST_TYPE = 'nntm_tu_khoa_dong';

	/** Meta trên trang/bài: có bật hiệu ứng từ khoá động hay không. */
	public const META_BAT = '_nntm_tu_khoa_dong';

	/** Meta của từ khoá. */
	public const META_BIEN_THE = '_nntm_tkd_bien_the';
	public const META_MO_TA    = '_nntm_tkd_mo_ta';
	public const META_KIEU     = '_nntm_tkd_kieu';
	public const META_LIEN_KET = '_nntm_tkd_lien_ket';

	/** Transient đệm danh sách đã chuẩn hoá cho frontend. */
	private const TRANSIENT = 'nntm_tkd_du_lieu';

	/** Giới hạn nhập liệu, tránh một từ khoá phình to làm nặng mọi trang. */
	private const TOI_DA_BIEN_THE = 20;
	private const TOI_DA_KY_TU    = 80;

	/** @var Tu_Khoa_Dong|null */
	private static ?Tu_Khoa_Dong $instance = null;

	/** Lấy đối tượng dùng chung. */
	public static function instance(): Tu_Khoa_Dong {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/** Gắn hook WordPress. */
	public function hooks(): void {
		add_action( 'init', array( $this, 'register' ) );
		// Sau các CPT của Post_Types để post_type_supports() thấy được chúng.
		add_action( 'init', array( $this, 'register_meta_trang' ), 20 );
		add_action( 'add_meta_boxes_' . self::POST_TYPE, array( $this, 'add_meta_box' ) );
		add_action( 'save_post_' . self::POST_TYPE, array( $this, 'save_meta_box' ), 10, 2 );
		add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_editor_panel' ) );

		// Danh sách đổi ở bất kỳ đâu thì bỏ bản đệm.
		add_action( 'save_post_' . self::POST_TYPE, array( __CLASS__, 'xoa_dem' ), 20 );
		// before_: sau khi xoá thì get_post_type() không còn biết đó là từ khoá.
		add_action( 'before_delete_post', array( $this, 'xoa_dem_neu_la_tu_khoa' ) );
		add_action( 'trashed_post', array( $this, 'xoa_dem_neu_la_tu_khoa' ) );
		add_action( 'untrashed_post', array( $this, 'xoa_dem_neu_la_tu_khoa' ) );

		add_filter( 'manage_' . self::POST_TYPE . '_posts_columns', array( $this, 'columns' ) );
		add_action( 'manage_' . self::POST_TYPE . '_posts_custom_column', array( $this, 'render_column' ), 10, 2 );
		add_action( 'admin_notices', array( $this, 'notice_trang_dang_bat' ) );
		add_filter( 'enter_title_here', array( $this, 'placeholder_tieu_de' ), 10, 2 );
	}

	/**
	 * Post type được phép bật hiệu ứng. Mặc định: Trang, Tin tức, Bài viết phân mục.
	 *
	 * @return string[]
	 */
	public static function post_types_ap_dung(): array {
		return (array) apply_filters( 'nntm_tu_khoa_dong_post_types', array( 'page', 'post', 'nntm_article' ) );
	}

	/** Đăng ký CPT chứa danh sách từ khoá. */
	public function register(): void {
		register_post_type(
			self::POST_TYPE,
			array(
				'labels'              => array(
					'name'               => __( 'Từ khoá động', 'nntm' ),
					'singular_name'      => __( 'Từ khoá động', 'nntm' ),
					'menu_name'          => __( 'Từ khoá động', 'nntm' ),
					'add_new'            => __( 'Thêm từ khoá', 'nntm' ),
					'add_new_item'       => __( 'Thêm từ khoá động mới', 'nntm' ),
					'edit_item'          => __( 'Sửa từ khoá động', 'nntm' ),
					'new_item'           => __( 'Từ khoá mới', 'nntm' ),
					'search_items'       => __( 'Tìm từ khoá', 'nntm' ),
					'not_found'          => __( 'Chưa có từ khoá nào', 'nntm' ),
					'not_found_in_trash' => __( 'Không có từ khoá nào trong thùng rác', 'nntm' ),
					'all_items'          => __( 'Tất cả từ khoá', 'nntm' ),
					'featured_image'     => __( 'Hình minh hoạ', 'nntm' ),
					'set_featured_image' => __( 'Chọn hình minh hoạ', 'nntm' ),
					'remove_featured_image' => __( 'Gỡ hình minh hoạ', 'nntm' ),
					'use_featured_image' => __( 'Dùng làm hình minh hoạ', 'nntm' ),
				),
				'public'              => false,
				'show_ui'             => true,
				'show_in_menu'        => true,
				'show_in_admin_bar'   => false,
				'show_in_nav_menus'   => false,
				'exclude_from_search' => true,
				'publicly_queryable'  => false,
				// Form cổ điển: chỉ vài ô nhập, BQT không cần trình soạn thảo khối.
				'show_in_rest'        => false,
				'menu_icon'           => 'dashicons-format-image',
				'menu_position'       => 39,
				'supports'            => array( 'title', 'thumbnail' ),
				'has_archive'         => false,
				'rewrite'             => false,
				'capability_type'     => 'post',
				'map_meta_cap'        => true,
			)
		);
	}

	/** Meta cờ bật trên từng trang/bài. */
	public function register_meta_trang(): void {
		foreach ( self::post_types_ap_dung() as $post_type ) {
			// Meta chỉ ra REST (trình soạn thảo khối đọc/ghi qua REST) khi post type
			// hỗ trợ custom-fields — nntm_article đăng ký không có mục này.
			if ( post_type_exists( $post_type ) && ! post_type_supports( $post_type, 'custom-fields' ) ) {
				add_post_type_support( $post_type, 'custom-fields' );
			}

			register_post_meta(
				$post_type,
				self::META_BAT,
				array(
					'type'              => 'boolean',
					'single'            => true,
					'default'           => false,
					'show_in_rest'      => true,
					'sanitize_callback' => static function ( $value ): bool {
						return (bool) rest_sanitize_boolean( $value );
					},
					'auth_callback'     => static function ( $allowed, $meta_key, $post_id ): bool {
						$post_id = absint( $post_id );

						return $post_id > 0
							? current_user_can( 'edit_post', $post_id )
							: current_user_can( 'edit_pages' );
					},
				)
			);
		}
	}

	/** Ô tích "Bật từ khoá động" trong thanh bên trình soạn thảo. */
	public function enqueue_editor_panel(): void {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

		if ( ! $screen || ! in_array( $screen->post_type, self::post_types_ap_dung(), true ) ) {
			return;
		}

		$path = NNTM_CORE_DIR . 'assets/js/tu-khoa-dong-editor.js';

		wp_enqueue_script(
			'nntm-tu-khoa-dong-editor',
			NNTM_CORE_URL . 'assets/js/tu-khoa-dong-editor.js',
			array( 'wp-components', 'wp-data', 'wp-editor', 'wp-edit-post', 'wp-element', 'wp-i18n', 'wp-plugins' ),
			is_readable( $path ) ? (string) filemtime( $path ) : NNTM_CORE_VERSION,
			true
		);

		wp_localize_script(
			'nntm-tu-khoa-dong-editor',
			'nntmTuKhoaDongEditor',
			array(
				'postTypes' => array_values( self::post_types_ap_dung() ),
				'soTuKhoa'  => count( self::du_lieu() ),
				'quanLyUrl' => admin_url( 'edit.php?post_type=' . self::POST_TYPE ),
			)
		);
	}

	/** Thêm meta box cài đặt cho từ khoá. */
	public function add_meta_box(): void {
		add_meta_box(
			'nntm-tu-khoa-dong',
			__( 'Cài đặt từ khoá động', 'nntm' ),
			array( $this, 'render_meta_box' ),
			self::POST_TYPE,
			'normal',
			'high'
		);
	}

	/** Chữ gợi ý trong ô tiêu đề. */
	public function placeholder_tieu_de( string $text, \WP_Post $post ): string {
		return self::POST_TYPE === $post->post_type
			? __( 'Từ khoá, ví dụ: Hoa sen', 'nntm' )
			: $text;
	}

	/**
	 * Các kiểu hiệu ứng.
	 *
	 * @return array<string,string>
	 */
	public static function cac_kieu(): array {
		return array(
			'the' => __( 'Thẻ minh hoạ — hình + mô tả ngắn', 'nntm' ),
			'anh' => __( 'Chỉ hình — hình lớn nổi lên', 'nntm' ),
		);
	}

	/** In meta box. */
	public function render_meta_box( \WP_Post $post ): void {
		wp_nonce_field( 'nntm_luu_tu_khoa_dong', 'nntm_tkd_nonce' );

		$bien_the = implode( "\n", self::doc_bien_the( $post->ID ) );
		$mo_ta    = (string) get_post_meta( $post->ID, self::META_MO_TA, true );
		$kieu     = self::doc_kieu( $post->ID );
		$lien_ket = (string) get_post_meta( $post->ID, self::META_LIEN_KET, true );
		?>
		<p class="description" style="margin-top:0">
			<?php esc_html_e( 'Tiêu đề phía trên chính là từ khoá. Hình minh hoạ chọn ở ô "Hình minh hoạ" bên phải. Hiệu ứng chỉ chạy trên những trang đã tích "Bật từ khoá động" trong trình soạn thảo trang.', 'nntm' ); ?>
		</p>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row"><label for="nntm-tkd-bien-the"><?php esc_html_e( 'Cách viết khác', 'nntm' ); ?></label></th>
				<td>
					<textarea id="nntm-tkd-bien-the" name="nntm_tkd_bien_the" rows="4" class="large-text" placeholder="<?php esc_attr_e( "Mỗi dòng một cách viết, ví dụ:\nsen\nđoá sen", 'nntm' ); ?>"><?php echo esc_textarea( $bien_the ); ?></textarea>
					<p class="description"><?php esc_html_e( 'Không phân biệt chữ hoa/thường. Chỉ khớp nguyên từ: "sen" không khớp vào giữa "senior".', 'nntm' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="nntm-tkd-mo-ta"><?php esc_html_e( 'Mô tả ngắn', 'nntm' ); ?></label></th>
				<td>
					<textarea id="nntm-tkd-mo-ta" name="nntm_tkd_mo_ta" rows="3" class="large-text" maxlength="300"><?php echo esc_textarea( $mo_ta ); ?></textarea>
					<p class="description"><?php esc_html_e( 'Một hai câu hiện dưới hình. Để trống nếu chỉ cần hình.', 'nntm' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Kiểu hiệu ứng', 'nntm' ); ?></th>
				<td>
					<fieldset>
						<?php foreach ( self::cac_kieu() as $gia_tri => $nhan ) : ?>
							<label style="display:block;margin-bottom:4px">
								<input type="radio" name="nntm_tkd_kieu" value="<?php echo esc_attr( $gia_tri ); ?>" <?php checked( $kieu, $gia_tri ); ?> />
								<?php echo esc_html( $nhan ); ?>
							</label>
						<?php endforeach; ?>
					</fieldset>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="nntm-tkd-lien-ket"><?php esc_html_e( 'Liên kết "Xem thêm"', 'nntm' ); ?></label></th>
				<td>
					<input type="url" id="nntm-tkd-lien-ket" name="nntm_tkd_lien_ket" class="large-text" value="<?php echo esc_attr( $lien_ket ); ?>" placeholder="https://" />
					<p class="description"><?php esc_html_e( 'Không bắt buộc. Có liên kết thì thẻ minh hoạ hiện thêm nút "Xem thêm".', 'nntm' ); ?></p>
				</td>
			</tr>
		</table>
		<?php
	}

	/** Lưu meta box. */
	public function save_meta_box( int $post_id, \WP_Post $post ): void {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! isset( $_POST['nntm_tkd_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['nntm_tkd_nonce'] ) ), 'nntm_luu_tu_khoa_dong' ) ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$bien_the = isset( $_POST['nntm_tkd_bien_the'] )
			? self::lam_sach_bien_the( (string) wp_unslash( $_POST['nntm_tkd_bien_the'] ), $post->post_title ) // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- lọc trong lam_sach_bien_the().
			: array();
		update_post_meta( $post_id, self::META_BIEN_THE, $bien_the );

		$mo_ta = isset( $_POST['nntm_tkd_mo_ta'] ) ? sanitize_textarea_field( wp_unslash( $_POST['nntm_tkd_mo_ta'] ) ) : '';
		update_post_meta( $post_id, self::META_MO_TA, mb_substr( $mo_ta, 0, 300 ) );

		$kieu = isset( $_POST['nntm_tkd_kieu'] ) ? sanitize_key( wp_unslash( $_POST['nntm_tkd_kieu'] ) ) : 'the';
		update_post_meta( $post_id, self::META_KIEU, array_key_exists( $kieu, self::cac_kieu() ) ? $kieu : 'the' );

		$lien_ket = isset( $_POST['nntm_tkd_lien_ket'] ) ? esc_url_raw( wp_unslash( $_POST['nntm_tkd_lien_ket'] ), array( 'http', 'https' ) ) : '';
		update_post_meta( $post_id, self::META_LIEN_KET, $lien_ket );
	}

	/**
	 * Tách ô "Cách viết khác" thành mảng sạch, bỏ trùng với chính từ khoá.
	 *
	 * @param string $raw     Nội dung textarea.
	 * @param string $tieu_de Từ khoá chính.
	 * @return string[]
	 */
	public static function lam_sach_bien_the( string $raw, string $tieu_de = '' ): array {
		$da_co = array( self::khoa_so_sanh( $tieu_de ) => true );
		$ket   = array();

		foreach ( preg_split( '/\R/u', $raw ) ?: array() as $dong ) {
			$dong = trim( preg_replace( '/\s+/u', ' ', sanitize_text_field( $dong ) ) ?? '' );
			$khoa = self::khoa_so_sanh( $dong );

			if ( mb_strlen( $dong ) < 2 || mb_strlen( $dong ) > self::TOI_DA_KY_TU || isset( $da_co[ $khoa ] ) ) {
				continue;
			}

			$da_co[ $khoa ] = true;
			$ket[]          = $dong;

			if ( count( $ket ) >= self::TOI_DA_BIEN_THE ) {
				break;
			}
		}

		return $ket;
	}

	/** Khoá so trùng: thường hoá, NFC. */
	private static function khoa_so_sanh( string $s ): string {
		if ( class_exists( '\Normalizer' ) ) {
			$s = (string) \Normalizer::normalize( $s, \Normalizer::FORM_C );
		}

		return mb_strtolower( trim( $s ), 'UTF-8' );
	}

	/**
	 * @return string[]
	 */
	private static function doc_bien_the( int $post_id ): array {
		$raw = get_post_meta( $post_id, self::META_BIEN_THE, true );

		return is_array( $raw ) ? array_values( array_filter( array_map( 'strval', $raw ) ) ) : array();
	}

	private static function doc_kieu( int $post_id ): string {
		$kieu = (string) get_post_meta( $post_id, self::META_KIEU, true );

		return array_key_exists( $kieu, self::cac_kieu() ) ? $kieu : 'the';
	}

	/**
	 * Trang/bài này có bật từ khoá động không.
	 *
	 * @param int $post_id ID trang.
	 */
	public static function dang_bat( int $post_id ): bool {
		$post = get_post( $post_id );
		$bat  = $post
			&& in_array( $post->post_type, self::post_types_ap_dung(), true )
			&& rest_sanitize_boolean( get_post_meta( $post_id, self::META_BAT, true ) );

		return (bool) apply_filters( 'nntm_tu_khoa_dong_bat', $bat, $post_id );
	}

	/**
	 * Danh sách từ khoá đã chuẩn hoá cho frontend. Đệm bằng transient, xoá khi
	 * có từ khoá được lưu/xoá.
	 *
	 * Từ khoá không có hình lẫn mô tả thì bỏ — rê chuột vào không có gì để xem.
	 *
	 * @return array<int,array{id:int,ten:string,tu:string[],mo_ta:string,kieu:string,lien_ket:string,anh:?array}>
	 */
	public static function du_lieu(): array {
		$dem = get_transient( self::TRANSIENT );
		if ( is_array( $dem ) ) {
			return $dem;
		}

		$ids = get_posts(
			array(
				'post_type'        => self::POST_TYPE,
				'post_status'      => 'publish',
				'posts_per_page'   => 500,
				'orderby'          => 'title',
				'order'            => 'ASC',
				'fields'           => 'ids',
				'no_found_rows'    => true,
				'suppress_filters' => true,
			)
		);

		$ket = array();

		foreach ( $ids as $id ) {
			$id  = (int) $id;
			$ten = trim( get_the_title( $id ) );
			if ( '' === $ten ) {
				continue;
			}

			$anh    = null;
			$anh_id = (int) get_post_thumbnail_id( $id );
			if ( $anh_id > 0 ) {
				$src = wp_get_attachment_image_src( $anh_id, 'medium_large' );
				if ( $src ) {
					$alt = trim( (string) get_post_meta( $anh_id, '_wp_attachment_image_alt', true ) );
					$anh = array(
						'src' => esc_url_raw( $src[0] ),
						'w'   => (int) $src[1],
						'h'   => (int) $src[2],
						'alt' => '' !== $alt ? $alt : $ten,
					);
				}
			}

			$mo_ta = (string) get_post_meta( $id, self::META_MO_TA, true );
			$kieu  = self::doc_kieu( $id );

			if ( null === $anh && '' === $mo_ta ) {
				continue;
			}

			// Kiểu "chỉ hình" mà chưa có hình thì lui về thẻ để mô tả vẫn hiện.
			if ( 'anh' === $kieu && null === $anh ) {
				$kieu = 'the';
			}

			$ket[] = array(
				'id'       => $id,
				'ten'      => html_entity_decode( $ten, ENT_QUOTES, 'UTF-8' ),
				'tu'       => array_values( array_unique( array_merge( array( html_entity_decode( $ten, ENT_QUOTES, 'UTF-8' ) ), self::doc_bien_the( $id ) ) ) ),
				'mo_ta'    => $mo_ta,
				'kieu'     => $kieu,
				'lien_ket' => (string) get_post_meta( $id, self::META_LIEN_KET, true ),
				'anh'      => $anh,
			);
		}

		set_transient( self::TRANSIENT, $ket, DAY_IN_SECONDS );

		return $ket;
	}

	/** Bỏ bản đệm danh sách. */
	public static function xoa_dem(): void {
		delete_transient( self::TRANSIENT );
	}

	/** Bỏ đệm khi một từ khoá bị xoá / đưa vào / lấy ra thùng rác. */
	public function xoa_dem_neu_la_tu_khoa( int $post_id ): void {
		if ( self::POST_TYPE === get_post_type( $post_id ) ) {
			self::xoa_dem();
		}
	}

	/**
	 * Cột danh sách từ khoá.
	 *
	 * @param array<string,string> $cols Cột mặc định.
	 * @return array<string,string>
	 */
	public function columns( array $cols ): array {
		$moi = array();

		foreach ( $cols as $khoa => $nhan ) {
			if ( 'title' === $khoa ) {
				$moi['nntm_tkd_anh'] = __( 'Hình', 'nntm' );
				$moi[ $khoa ]        = __( 'Từ khoá', 'nntm' );
				$moi['nntm_tkd_bien_the'] = __( 'Cách viết khác', 'nntm' );
				$moi['nntm_tkd_kieu']     = __( 'Kiểu', 'nntm' );
				continue;
			}
			$moi[ $khoa ] = $nhan;
		}

		return $moi;
	}

	/** In ô trong cột. */
	public function render_column( string $col, int $post_id ): void {
		switch ( $col ) {
			case 'nntm_tkd_anh':
				echo has_post_thumbnail( $post_id )
					? get_the_post_thumbnail( $post_id, array( 48, 48 ), array( 'style' => 'width:48px;height:48px;object-fit:cover;border-radius:4px' ) )
					: '<span style="color:#b32d2e">' . esc_html__( 'Chưa có hình', 'nntm' ) . '</span>';
				break;
			case 'nntm_tkd_bien_the':
				$bien_the = self::doc_bien_the( $post_id );
				echo $bien_the ? esc_html( implode( ', ', $bien_the ) ) : '—';
				break;
			case 'nntm_tkd_kieu':
				$cac_kieu = self::cac_kieu();
				echo esc_html( strtok( $cac_kieu[ self::doc_kieu( $post_id ) ], '—' ) );
				break;
		}
	}

	/** Ở màn danh sách từ khoá: cho BQT thấy trang nào đang bật hiệu ứng. */
	public function notice_trang_dang_bat(): void {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

		if ( ! $screen || 'edit-' . self::POST_TYPE !== $screen->id ) {
			return;
		}

		$trang = get_posts(
			array(
				'post_type'        => self::post_types_ap_dung(),
				'post_status'      => array( 'publish', 'draft', 'pending', 'private', 'future' ),
				'posts_per_page'   => 50,
				'meta_key'         => self::META_BAT, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_query_meta_key -- màn quản trị, ít bản ghi.
				'meta_value'       => '1', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_query_meta_value
				'no_found_rows'    => true,
				'suppress_filters' => true,
			)
		);
		?>
		<div class="notice notice-info">
			<p>
				<strong><?php esc_html_e( 'Trang đang bật từ khoá động:', 'nntm' ); ?></strong>
				<?php
				if ( ! $trang ) {
					esc_html_e( 'chưa có trang nào. Mở trang cần bật → thanh bên phải → mục "Từ khoá động" → tích ô.', 'nntm' );
				} else {
					$links = array();
					foreach ( $trang as $p ) {
						$links[] = '<a href="' . esc_url( (string) get_edit_post_link( $p->ID ) ) . '">' . esc_html( get_the_title( $p ) ?: '#' . $p->ID ) . '</a>';
					}
					echo wp_kses( implode( ', ', $links ), array( 'a' => array( 'href' => array() ) ) );
				}
				?>
			</p>
		</div>
		<?php
	}
}
