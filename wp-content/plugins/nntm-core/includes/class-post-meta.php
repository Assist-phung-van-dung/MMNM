<?php
/**
 * Đăng ký post meta dùng chung cho các loại nội dung của dự án.
 *
 * VÌ SAO Ở ĐÂY, KHÔNG Ở THEME:
 * Theo docs/04-kien-truc.md mục 1 — dữ liệu và nghiệp vụ ở plugin, hình ảnh
 * ở theme. Đổi theme sau này không được mất dữ liệu.
 *
 * Trước đây hai trường này được đăng ký ngay trong render.php của block ở
 * theme. Cách đó có hai vấn đề:
 *   1. render.php chỉ chạy KHI block thực sự vẽ ra HTML, nên trang quản trị
 *      và REST API không thấy trường — trình soạn thảo không đọc/ghi được.
 *   2. Cách lách bằng việc nhờ WordPress require file editor.asset.php bám
 *      vào chi tiết nội bộ của lõi (register_block_script_handle), lõi đổi
 *      là hỏng âm thầm mà không báo lỗi.
 * Đăng ký ở plugin trên hook init thì luôn chạy, mọi request, đúng chuẩn.
 *
 * @package NNTM_Core
 */

namespace NNTM\Core;

defined( 'ABSPATH' ) || exit;

/**
 * Đăng ký post meta cho các CPT của dự án.
 */
class Post_Meta {

	/**
	 * Bản thân đối tượng.
	 *
	 * @var Post_Meta|null
	 */
	private static ?Post_Meta $instance = null;

	/**
	 * Lấy đối tượng dùng chung.
	 */
	public static function instance(): Post_Meta {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Gắn hook.
	 */
	public function hooks(): void {
		add_action( 'init', array( $this, 'register_meta' ) );
		add_action( 'wp_ajax_nntm_track_listen', array( $this, 'record_track_listen' ) );

		add_action( 'add_meta_boxes_nntm_publication', array( $this, 'add_publication_meta_box' ) );
		add_action( 'save_post_nntm_publication', array( $this, 'save_publication_meta_box' ) );

		add_action( 'add_meta_boxes_nntm_abode', array( $this, 'add_abode_meta_box' ) );
		add_action( 'add_meta_boxes_nntm_abode', array( $this, 'add_abode_gallery_meta_box' ) );
		add_action( 'save_post_nntm_abode', array( $this, 'save_abode_meta_box' ) );
		add_action( 'save_post_nntm_abode', array( $this, 'save_abode_gallery_meta_box' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_publication_admin_assets' ) );
	}

	/** Ghi nhận một lượt nghe từ thành viên đã đăng nhập. */
	public function record_track_listen(): void {
		check_ajax_referer( 'nntm_track_listen', 'nonce' );
		$track_id = isset( $_POST['track_id'] ) ? absint( wp_unslash( $_POST['track_id'] ) ) : 0;
		if ( ! is_user_logged_in() || 'nntm_zen_track' !== get_post_type( $track_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Bản nhạc không hợp lệ.', 'nntm' ) ), 403 );
		}
		$count = absint( get_post_meta( $track_id, '_nntm_track_listen_count', true ) ) + 1;
		update_post_meta( $track_id, '_nntm_track_listen_count', $count );
		wp_send_json_success( array( 'count' => $count ) );
	}

	/**
	 * Khai báo các trường.
	 */
	public function register_meta(): void {

		/*
		 * Tệp âm thanh của bài nhạc thiền (Thiền Đường).
		 * Lưu ID tệp đính kèm chứ không lưu đường dẫn: đổi tên miền hay dời
		 * thư mục uploads thì link vẫn đúng, và quan trọng hơn là đường dẫn
		 * thật chỉ được sinh ra ở phía máy chủ sau khi đã kiểm tra quyền —
		 * người chưa đăng nhập không bao giờ thấy link trong HTML.
		 */
		register_post_meta(
			'nntm_zen_track',
			'_nntm_track_audio',
			array(
				'type'              => 'integer',
				'single'            => true,
				'default'           => 0,
				'show_in_rest'      => true,
				'sanitize_callback' => 'absint',
				'auth_callback'     => function () {
					return current_user_can( 'edit_posts' );
				},
				'description'       => __( 'ID tệp âm thanh của bài nhạc thiền.', 'nntm' ),
			)
		);

		register_post_meta(
			'nntm_zen_track',
			'_nntm_track_listen_count',
			array(
				'type'              => 'integer',
				'single'            => true,
				'default'           => 0,
				'show_in_rest'      => true,
				'sanitize_callback' => 'absint',
				'auth_callback'     => function () {
					return current_user_can( 'edit_posts' );
				},
				'description'       => __( 'Tổng lượt nghe của bản nhạc thiền.', 'nntm' ),
			)
		);

		/*
		 * Địa điểm hiển thị trên thẻ Trú Xứ, ví dụ "Việt Nam - Nha Trang".
		 * Chưa nhập thì thẻ tự rơi về phần mô tả ngắn của bài.
		 */
		register_post_meta(
			'nntm_abode',
			'_nntm_abode_location',
			array(
				'type'              => 'string',
				'single'            => true,
				'default'           => '',
				'show_in_rest'      => true,
				'sanitize_callback' => 'sanitize_text_field',
				'auth_callback'     => function () {
					return current_user_can( 'edit_posts' );
				},
				'description'       => __( 'Địa điểm hiển thị trên thẻ Trú Xứ, ví dụ "Việt Nam - Nha Trang".', 'nntm' ),
			)
		);

		$this->register_abode_map_meta();

		/*
		 * Post meta của nntm_program (Cộng Tu "chuỗi trì") — xem
		 * docs/07-ban-giao.md mục "Đang làm dở". Nghiệp vụ đọc các trường
		 * này nằm ở includes/class-chuoi-tri.php, file này chỉ khai báo để
		 * trình soạn thảo block và REST API đọc/ghi được.
		 */

		register_post_meta(
			'nntm_program',
			'_nntm_program_bat_dau',
			array(
				'type'              => 'string',
				'single'            => true,
				'default'           => '',
				'show_in_rest'      => true,
				'sanitize_callback' => 'sanitize_text_field',
				'auth_callback'     => function () {
					return current_user_can( 'edit_posts' );
				},
				'description'       => __( 'Ngày bắt đầu chương trình, định dạng Y-m-d.', 'nntm' ),
			)
		);

		register_post_meta(
			'nntm_program',
			'_nntm_program_ket_thuc',
			array(
				'type'              => 'string',
				'single'            => true,
				'default'           => '',
				'show_in_rest'      => true,
				'sanitize_callback' => 'sanitize_text_field',
				'auth_callback'     => function () {
					return current_user_can( 'edit_posts' );
				},
				'description'       => __( 'Ngày kết thúc chương trình, định dạng Y-m-d. Để trống = không giới hạn.', 'nntm' ),
			)
		);

		register_post_meta(
			'nntm_program',
			'_nntm_program_dang_mo',
			array(
				'type'              => 'boolean',
				'single'            => true,
				'default'           => false,
				'show_in_rest'      => true,
				'sanitize_callback' => 'rest_sanitize_boolean',
				'auth_callback'     => function () {
					return current_user_can( 'edit_posts' );
				},
				'description'       => __( 'Công tắc ban quản trị đóng/mở nhận cam kết cho chương trình.', 'nntm' ),
			)
		);

		register_post_meta(
			'nntm_program',
			'_nntm_program_don_vi',
			array(
				'type'              => 'string',
				'single'            => true,
				'default'           => 'chuỗi',
				'show_in_rest'      => true,
				'sanitize_callback' => 'sanitize_text_field',
				'auth_callback'     => function () {
					return current_user_can( 'edit_posts' );
				},
				'description'       => __( 'Đơn vị đếm hiển thị, ví dụ "chuỗi".', 'nntm' ),
			)
		);

		register_post_meta(
			'nntm_program',
			'_nntm_program_muc_tieu',
			array(
				'type'              => 'integer',
				'single'            => true,
				'default'           => 0,
				'show_in_rest'      => true,
				'sanitize_callback' => 'absint',
				'auth_callback'     => function () {
					return current_user_can( 'edit_posts' );
				},
				'description'       => __( 'Mục tiêu chung của đạo tràng cho chương trình, 0 = không đặt.', 'nntm' ),
			)
		);

		/*
		 * Thư Viện PDF — ấn phẩm (nntm_publication).
		 * Hai trường này có ô điều khiển riêng trong meta box "Tệp PDF & Khoá xem"
		 * (xem add_publication_meta_box() / save_publication_meta_box() dưới), không
		 * dùng panel Custom Fields mặc định của WordPress.
		 */

		register_post_meta(
			'nntm_publication',
			'_nntm_pdf_file',
			array(
				'type'              => 'integer',
				'single'            => true,
				'default'           => 0,
				'show_in_rest'      => true,
				'sanitize_callback' => 'absint',
				'auth_callback'     => function () {
					return current_user_can( 'edit_posts' );
				},
				'description'       => __( 'ID tệp đính kèm PDF của ấn phẩm.', 'nntm' ),
			)
		);

		register_post_meta(
			'nntm_publication',
			'_nntm_pub_khoa',
			array(
				'type'              => 'boolean',
				'single'            => true,
				'default'           => false,
				'show_in_rest'      => true,
				'sanitize_callback' => 'rest_sanitize_boolean',
				'auth_callback'     => function () {
					return current_user_can( 'edit_posts' );
				},
				'description'       => __( 'Ấn phẩm bị khoá, yêu cầu thanh toán mới xem được. Mặc định false (mở).', 'nntm' ),
			)
		);
	}

	/**
	 * Thêm meta box "Tệp PDF & Khoá xem" vào màn sửa ấn phẩm.
	 * Dùng meta box PHP cổ điển (không phải panel sidebar của block editor) vì
	 * đây là dữ liệu quản trị nội bộ, không phải nội dung khách kéo thả trên trang.
	 */
	public function add_publication_meta_box(): void {
		add_meta_box(
			'nntm_an_pham_pdf_khoa',
			__( 'Tệp PDF & Khoá xem', 'nntm' ),
			array( $this, 'render_publication_meta_box' ),
			'nntm_publication',
			'side',
			'high'
		);
	}

	/**
	 * Vẽ meta box: chọn tệp PDF từ Thư viện Media + công tắc khoá xem.
	 *
	 * @param WP_Post $post Ấn phẩm đang sửa.
	 */
	public function render_publication_meta_box( $post ): void {
		wp_nonce_field( 'nntm_an_pham_pdf_khoa', 'nntm_an_pham_pdf_khoa_nonce' );

		$att_id = absint( get_post_meta( $post->ID, '_nntm_pdf_file', true ) );
		$khoa   = (bool) get_post_meta( $post->ID, '_nntm_pub_khoa', true );
		$ten    = $att_id ? get_the_title( $att_id ) : '';
		?>
		<p>
			<label for="nntm_pdf_file_input"><strong><?php esc_html_e( 'Tệp PDF', 'nntm' ); ?></strong></label><br />
			<input type="hidden" id="nntm_pdf_file_input" name="nntm_pdf_file" value="<?php echo esc_attr( (string) $att_id ); ?>" />
			<span id="nntm-pdf-file-ten"><?php echo $ten ? esc_html( $ten ) : esc_html__( 'Chưa chọn tệp.', 'nntm' ); ?></span>
		</p>
		<p>
			<button type="button" class="button" id="nntm-pdf-file-chon"><?php esc_html_e( 'Chọn tệp PDF', 'nntm' ); ?></button>
			<button type="button" class="button" id="nntm-pdf-file-xoa" <?php echo $att_id ? '' : 'style="display:none"'; ?>><?php esc_html_e( 'Bỏ chọn', 'nntm' ); ?></button>
		</p>
		<hr />
		<p>
			<label for="nntm_pub_khoa_input">
				<input type="checkbox" id="nntm_pub_khoa_input" name="nntm_pub_khoa" value="1" <?php checked( $khoa ); ?> />
				<?php esc_html_e( 'Khoá — yêu cầu thanh toán mới xem được', 'nntm' ); ?>
			</label><br />
			<span class="description"><?php esc_html_e( 'Mặc định không tick (mở, ai cũng xem được).', 'nntm' ); ?></span>
		</p>
		<?php
	}

	/**
	 * Lưu tệp PDF + trạng thái khoá khi lưu ấn phẩm.
	 *
	 * @param int $post_id ID ấn phẩm.
	 */
	public function save_publication_meta_box( int $post_id ): void {
		if ( ! isset( $_POST['nntm_an_pham_pdf_khoa_nonce'] )
			|| ! wp_verify_nonce( wp_unslash( $_POST['nntm_an_pham_pdf_khoa_nonce'] ), 'nntm_an_pham_pdf_khoa' )
		) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		if ( isset( $_POST['nntm_pdf_file'] ) ) {
			update_post_meta( $post_id, '_nntm_pdf_file', absint( wp_unslash( $_POST['nntm_pdf_file'] ) ) );
		}

		update_post_meta( $post_id, '_nntm_pub_khoa', isset( $_POST['nntm_pub_khoa'] ) );
	}

	/**
	 * Nạp JS chọn tệp PDF (wp.media) chỉ trên màn sửa ấn phẩm.
	 *
	 * @param string $hook Slug màn quản trị hiện tại.
	 */
	public function enqueue_publication_admin_assets( string $hook ): void {
		if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
			return;
		}

		$screen = get_current_screen();

		if ( $screen && 'nntm_abode' === $screen->post_type ) {
			wp_enqueue_media();

			$abode_js = NNTM_CORE_DIR . 'assets/js/tru-xu-meta-box.js';
			wp_enqueue_script(
				'nntm-tru-xu-meta-box',
				NNTM_CORE_URL . 'assets/js/tru-xu-meta-box.js',
				array(),
				file_exists( $abode_js ) ? (string) filemtime( $abode_js ) : NNTM_CORE_VERSION,
				true
			);

			return;
		}

		if ( ! $screen || 'nntm_publication' !== $screen->post_type ) {
			return;
		}

		wp_enqueue_media();

		$js_path = NNTM_CORE_DIR . 'assets/js/an-pham-meta-box.js';
		wp_enqueue_script(
			'nntm-an-pham-meta-box',
			NNTM_CORE_URL . 'assets/js/an-pham-meta-box.js',
			array(),
			file_exists( $js_path ) ? (string) filemtime( $js_path ) : NNTM_CORE_VERSION,
			true
		);
	}

	/**
	 * Đăng ký vị trí bản đồ cho Trú Xứ: địa chỉ + toạ độ.
	 *
	 * Dữ liệu nên nằm ở plugin (docs/04-kien-truc.md mục 1) để đổi theme không
	 * mất. Phần giao diện bản đồ nằm ở theme: inc/tru-xu-map.php.
	 */
	private function register_abode_map_meta(): void {
		$chi_nguoi_sua = function () {
			return current_user_can( 'edit_posts' );
		};

		register_post_meta(
			'nntm_abode',
			'_nntm_abode_address',
			array(
				'type'              => 'string',
				'single'            => true,
				'default'           => '',
				'show_in_rest'      => true,
				'sanitize_callback' => 'sanitize_text_field',
				'auth_callback'     => $chi_nguoi_sua,
				'description'       => __( 'Địa chỉ đầy đủ của Trú Xứ, hiện trong cửa sổ bản đồ.', 'nntm' ),
			)
		);

		/*
		 * Bộ ảnh của Trú Xứ. Bấm tên Trú Xứ trên trang sẽ mở cửa sổ xem bộ ảnh
		 * này (xem theme inc/tru-xu.php). Lưu mảng ID tệp đính kèm.
		 */
		register_post_meta(
			'nntm_abode',
			'_nntm_abode_gallery',
			array(
				'type'              => 'array',
				'single'            => true,
				'default'           => array(),
				'show_in_rest'      => array(
					'schema' => array(
						'type'  => 'array',
						'items' => array( 'type' => 'integer' ),
					),
				),
				'sanitize_callback' => array( $this, 'sanitize_bo_anh' ),
				'auth_callback'     => $chi_nguoi_sua,
				'description'       => __( 'Danh sách ID ảnh trong bộ ảnh của Trú Xứ.', 'nntm' ),
			)
		);

		/*
		 * Toạ độ lưu dạng chuỗi chứ không phải số: để trống là "chưa nhập", còn
		 * kiểu number thì ô trống bị hiểu thành 0,0 — một điểm ngoài khơi châu Phi.
		 */
		foreach ( array(
			'_nntm_abode_lat' => __( 'Vĩ độ (latitude) của Trú Xứ, ví dụ 12.238791.', 'nntm' ),
			'_nntm_abode_lng' => __( 'Kinh độ (longitude) của Trú Xứ, ví dụ 109.196749.', 'nntm' ),
		) as $khoa => $mo_ta ) {
			register_post_meta(
				'nntm_abode',
				$khoa,
				array(
					'type'              => 'string',
					'single'            => true,
					'default'           => '',
					'show_in_rest'      => true,
					'sanitize_callback' => array( $this, 'sanitize_toa_do' ),
					'auth_callback'     => $chi_nguoi_sua,
					'description'       => $mo_ta,
				)
			);
		}
	}

	/**
	 * Lọc một giá trị toạ độ: chỉ nhận số thực trong khoảng hợp lệ của kinh/vĩ độ.
	 *
	 * @param mixed $gia_tri Giá trị thô từ form hoặc REST.
	 */
	public function sanitize_toa_do( $gia_tri ): string {
		$chuoi = trim( (string) $gia_tri );

		if ( '' === $chuoi ) {
			return '';
		}

		// Đổi dấu phẩy thập phân kiểu Việt Nam sang dấu chấm.
		$chuoi = str_replace( ',', '.', $chuoi );

		if ( ! is_numeric( $chuoi ) ) {
			return '';
		}

		$so = (float) $chuoi;

		// Vĩ độ tối đa 90, kinh độ tối đa 180 — lấy mức rộng hơn rồi chặn ở đây.
		if ( $so < -180 || $so > 180 ) {
			return '';
		}

		return (string) $so;
	}

	/**
	 * Lọc danh sách ID ảnh: chỉ giữ số dương, bỏ trùng, giới hạn 40 ảnh.
	 *
	 * @param mixed $gia_tri Giá trị thô từ form hoặc REST.
	 */
	public function sanitize_bo_anh( $gia_tri ): array {
		if ( is_string( $gia_tri ) ) {
			$gia_tri = explode( ',', $gia_tri );
		}

		if ( ! is_array( $gia_tri ) ) {
			return array();
		}

		$sach = array();

		foreach ( $gia_tri as $id ) {
			/*
			 * Không dùng absint(): nó lấy trị tuyệt đối nên -5 lại thành 5, tức
			 * một giá trị rác lọt qua thành ID hợp lệ. Ở đây đòi đúng số nguyên
			 * dương, còn lại bỏ hết.
			 */
			$tho = trim( (string) $id );

			if ( ! ctype_digit( $tho ) ) {
				continue;
			}

			$id = (int) $tho;

			if ( $id < 1 || in_array( $id, $sach, true ) ) {
				continue;
			}

			$sach[] = $id;

			if ( count( $sach ) >= 40 ) {
				break;
			}
		}

		return $sach;
	}

	/**
	 * Lưu bộ ảnh Trú Xứ.
	 *
	 * @param int $post_id ID Trú Xứ.
	 */
	public function save_abode_gallery_meta_box( int $post_id ): void {
		if ( ! isset( $_POST['nntm_tru_xu_bo_anh_nonce'] )
			|| ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nntm_tru_xu_bo_anh_nonce'] ) ), 'nntm_tru_xu_bo_anh' )
		) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		if ( ! isset( $_POST['nntm_abode_gallery'] ) ) {
			return;
		}

		update_post_meta(
			$post_id,
			'_nntm_abode_gallery',
			$this->sanitize_bo_anh( wp_unslash( $_POST['nntm_abode_gallery'] ) )
		);
	}

	/** Thêm meta box "Vị trí trên bản đồ" vào màn sửa Trú Xứ. */
	public function add_abode_meta_box(): void {
		add_meta_box(
			'nntm_tru_xu_vi_tri',
			__( 'Vị trí trên bản đồ', 'nntm' ),
			array( $this, 'render_abode_meta_box' ),
			'nntm_abode',
			'normal',
			'default'
		);
	}

	/**
	 * Vẽ meta box vị trí Trú Xứ.
	 *
	 * @param \WP_Post $post Trú Xứ đang sửa.
	 */
	public function render_abode_meta_box( $post ): void {
		wp_nonce_field( 'nntm_tru_xu_vi_tri', 'nntm_tru_xu_vi_tri_nonce' );

		$dia_diem = (string) get_post_meta( $post->ID, '_nntm_abode_location', true );
		$dia_chi  = (string) get_post_meta( $post->ID, '_nntm_abode_address', true );
		$lat      = (string) get_post_meta( $post->ID, '_nntm_abode_lat', true );
		$lng      = (string) get_post_meta( $post->ID, '_nntm_abode_lng', true );
		?>
		<style>
			.nntm-vi-tri-o { margin-bottom: 12px; }
			.nntm-vi-tri-o label { display: block; font-weight: 600; margin-bottom: 4px; }
			.nntm-vi-tri-o input[type="text"] { width: 100%; max-width: 560px; }
			.nntm-vi-tri-doi { display: flex; gap: 16px; flex-wrap: wrap; }
			.nntm-vi-tri-doi .nntm-vi-tri-o { flex: 1 1 220px; }
		</style>

		<div class="nntm-vi-tri-o">
			<label for="nntm_abode_location"><?php esc_html_e( 'Địa điểm ngắn (hiện trên thẻ)', 'nntm' ); ?></label>
			<input type="text" id="nntm_abode_location" name="nntm_abode_location"
				value="<?php echo esc_attr( $dia_diem ); ?>"
				placeholder="<?php esc_attr_e( 'Việt Nam - Nha Trang', 'nntm' ); ?>" />
		</div>

		<div class="nntm-vi-tri-o">
			<label for="nntm_abode_address"><?php esc_html_e( 'Địa chỉ đầy đủ', 'nntm' ); ?></label>
			<input type="text" id="nntm_abode_address" name="nntm_abode_address"
				value="<?php echo esc_attr( $dia_chi ); ?>"
				placeholder="<?php esc_attr_e( 'Số nhà, đường, phường, tỉnh/thành', 'nntm' ); ?>" />
			<p class="description"><?php esc_html_e( 'Hiện trong cửa sổ bản đồ khi khách bấm nút "Địa chỉ".', 'nntm' ); ?></p>
		</div>

		<div class="nntm-vi-tri-doi">
			<div class="nntm-vi-tri-o">
				<label for="nntm_abode_lat"><?php esc_html_e( 'Vĩ độ (latitude)', 'nntm' ); ?></label>
				<input type="text" id="nntm_abode_lat" name="nntm_abode_lat"
					value="<?php echo esc_attr( $lat ); ?>" placeholder="12.238791" />
			</div>
			<div class="nntm-vi-tri-o">
				<label for="nntm_abode_lng"><?php esc_html_e( 'Kinh độ (longitude)', 'nntm' ); ?></label>
				<input type="text" id="nntm_abode_lng" name="nntm_abode_lng"
					value="<?php echo esc_attr( $lng ); ?>" placeholder="109.196749" />
			</div>
		</div>

		<p class="description">
			<?php esc_html_e( 'Lấy toạ độ: mở Google Maps, bấm chuột phải đúng vị trí Trú Xứ, dòng số đầu tiên hiện ra chính là vĩ độ và kinh độ. Bấm vào đó là chép được.', 'nntm' ); ?>
			<br />
			<?php esc_html_e( 'Chưa nhập toạ độ thì nút biểu tượng địa chỉ không hiện trên thẻ Trú Xứ.', 'nntm' ); ?>
		</p>
		<?php
	}

	/**
	 * Meta box RIÊNG cho bộ ảnh Trú Xứ.
	 *
	 * Để riêng chứ không nhét vào hộp "Vị trí trên bản đồ": người dùng đi tìm
	 * chỗ thêm ảnh sẽ đọc tiêu đề hộp, mà tiêu đề kia nói về bản đồ nên không
	 * ai đoán được ảnh nằm trong đó.
	 */
	public function add_abode_gallery_meta_box(): void {
		add_meta_box(
			'nntm_tru_xu_bo_anh',
			__( 'Bộ ảnh Trú Xứ', 'nntm' ),
			array( $this, 'render_abode_gallery_meta_box' ),
			'nntm_abode',
			'normal',
			'high'
		);
	}

	/**
	 * Vẽ meta box bộ ảnh Trú Xứ.
	 *
	 * @param \WP_Post $post Trú Xứ đang sửa.
	 */
	public function render_abode_gallery_meta_box( $post ): void {
		wp_nonce_field( 'nntm_tru_xu_bo_anh', 'nntm_tru_xu_bo_anh_nonce' );

		$bo_anh = get_post_meta( $post->ID, '_nntm_abode_gallery', true );
		$bo_anh = is_array( $bo_anh ) ? array_map( 'absint', $bo_anh ) : array();
		?>
		<p class="description" style="margin-top:0;">
			<?php esc_html_e( 'Khách bấm vào TÊN Trú Xứ trên trang sẽ mở cửa sổ xem bộ ảnh này. Chưa chọn ảnh nào thì tên chỉ là chữ thường, bấm vào không mở gì.', 'nntm' ); ?>
		</p>

		<input type="hidden" id="nntm_abode_gallery" name="nntm_abode_gallery"
			value="<?php echo esc_attr( implode( ',', $bo_anh ) ); ?>" />

		<div id="nntm-abode-gallery-xem" class="nntm-abode-gallery-xem"></div>

		<p>
			<button type="button" class="button button-primary" id="nntm-abode-gallery-chon"><?php esc_html_e( 'Chọn / thêm ảnh', 'nntm' ); ?></button>
			<button type="button" class="button" id="nntm-abode-gallery-xoa" <?php echo $bo_anh ? '' : 'style="display:none"'; ?>><?php esc_html_e( 'Bỏ hết ảnh', 'nntm' ); ?></button>
		</p>

		<style>
			.nntm-abode-gallery-xem { display: flex; flex-wrap: wrap; gap: 8px; margin: 10px 0; }
			.nntm-abode-gallery-xem img { width: 92px; height: 62px; object-fit: cover; border: 1px solid #c3c4c7; border-radius: 3px; }
		</style>
		<?php
	}

	/**
	 * Lưu meta box vị trí Trú Xứ.
	 *
	 * @param int $post_id ID Trú Xứ.
	 */
	public function save_abode_meta_box( int $post_id ): void {
		if ( ! isset( $_POST['nntm_tru_xu_vi_tri_nonce'] )
			|| ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nntm_tru_xu_vi_tri_nonce'] ) ), 'nntm_tru_xu_vi_tri' )
		) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		if ( isset( $_POST['nntm_abode_location'] ) ) {
			update_post_meta( $post_id, '_nntm_abode_location', sanitize_text_field( wp_unslash( $_POST['nntm_abode_location'] ) ) );
		}

		if ( isset( $_POST['nntm_abode_address'] ) ) {
			update_post_meta( $post_id, '_nntm_abode_address', sanitize_text_field( wp_unslash( $_POST['nntm_abode_address'] ) ) );
		}

		foreach ( array( 'lat', 'lng' ) as $truc ) {
			$o = 'nntm_abode_' . $truc;

			if ( ! isset( $_POST[ $o ] ) ) {
				continue;
			}

			update_post_meta(
				$post_id,
				'_nntm_abode_' . $truc,
				$this->sanitize_toa_do( wp_unslash( $_POST[ $o ] ) )
			);
		}
	}
}
