<?php
/**
 * Kiểm soát quyền xem Nghi Quỹ bằng bộ câu hỏi (quiz).
 *
 * VÌ SAO Ở PLUGIN, KHÔNG Ở THEME:
 * docs/04-kien-truc.md mục 1 — dữ liệu và nghiệp vụ ở plugin. Câu hỏi, đáp án
 * đúng, việc chấm bài và việc gác cửa nội dung đều là nghiệp vụ; đổi theme
 * không được làm mất cấu hình hay mở toang nội dung. Phần giao diện (popup,
 * CSS, JS) nằm ở theme: inc/nghi-quy-quiz.php.
 *
 * NGUYÊN TẮC AN TOÀN:
 *   1. Đáp án đúng KHÔNG BAO GIỜ ra khỏi máy chủ. AJAX lấy câu hỏi chỉ trả về
 *      nội dung câu hỏi và nhãn các đáp án, không trả về chỉ số đáp án đúng, và
 *      meta lưu đáp án cũng không mở ra REST.
 *   2. Việc chấm bài làm ở backend. Frontend không được tin.
 *   3. Trạng thái PASS chỉ ghi bằng chính handler chấm bài, sau khi đã đúng hết.
 *      Không đọc PASS từ query string / cookie tự đặt.
 *   4. PASS gắn theo ID từng Nghi Quỹ — đậu bài này không mở bài khác.
 *   5. PASS chỉ sống trong PHP session hiện tại, không ghi vào user meta hay DB.
 *
 * @package NNTM_Core
 */

namespace NNTM\Core;

defined( 'ABSPATH' ) || exit;

/**
 * Quiz gác cửa Nghi Quỹ.
 */
class Nghi_Quy_Quiz {

	/**
	 * Chủ đề (nntm_topic) đánh dấu một ấn phẩm là Nghi Quỹ.
	 *
	 * Nghi Quỹ KHÔNG phải một loại nội dung riêng — nó là một chủ đề trong
	 * taxonomy nntm_topic. Mọi ấn phẩm khác (Ấn Phẩm nói chung) không dính dáng
	 * gì tới bộ câu hỏi này, dù có ai lỡ bật công tắc trong meta box.
	 */
	public const TOPIC_TAXONOMY = 'nntm_topic';
	public const TOPIC_SLUG     = 'nghi-quy';

	/**
	 * Khoá meta: chế độ truy cập — 'public' hoặc 'quiz'.
	 */
	public const META_CHE_DO = '_nntm_pub_access_mode';

	/**
	 * Khoá meta: bộ câu hỏi RIÊNG của từng ấn phẩm — chỉ còn để đọc dữ liệu cũ.
	 *
	 * Từ 30/08/2026 câu hỏi dùng CHUNG một bộ cho mọi Nghi Quỹ (xem OPTION_QUIZ).
	 * Màn sửa ấn phẩm không còn ô nhập câu hỏi nữa, nhưng khoá này vẫn được đọc
	 * làm đường lui để không mất bộ câu hỏi ai đó đã nhập trước đó.
	 */
	public const META_QUIZ = '_nntm_pub_quiz';

	/**
	 * Tuỳ chọn: BỘ CÂU HỎI DÙNG CHUNG cho mọi Nghi Quỹ.
	 *
	 * VÌ SAO DÙNG CHUNG: câu hỏi ở đây là cửa ải về thái độ hành trì, không phải
	 * bài kiểm tra nội dung của từng cuốn — nên cùng một bộ cho mọi Nghi Quỹ.
	 * Trước đây mỗi cuốn một bộ riêng, tức là bật khoá cho mười cuốn thì phải
	 * gõ lại mười lần và sửa một chữ cũng phải sửa mười chỗ.
	 *
	 * KHÔNG bao giờ đẩy ra REST hay ra trình duyệt: bên trong có đáp án đúng.
	 */
	public const OPTION_QUIZ = 'nntm_bo_cau_hoi_nghi_quy';

	/**
	 * Khoá trong $_SESSION giữ danh sách Nghi Quỹ đã đậu.
	 */
	public const SESSION_KEY = 'nntm_quiz_passed';

	/**
	 * Tên action của nonce cho AJAX.
	 */
	public const NONCE = 'nntm_quiz';

	/**
	 * Câu trả lời sai — thông báo phải đúng từng chữ theo yêu cầu nghiệp vụ.
	 */
	public const LOI_SAI = 'Xin lỗi. Hiện tại bạn chưa được xem và thực hành Nghi Quỹ này.';

	/**
	 * Bản thân đối tượng.
	 *
	 * @var Nghi_Quy_Quiz|null
	 */
	private static ?Nghi_Quy_Quiz $instance = null;

	/**
	 * Bộ nhớ tạm cho dữ liệu session đã đọc trong request này.
	 *
	 * @var array<int,bool>|null
	 */
	private static ?array $da_doc_session = null;

	/**
	 * Lấy đối tượng dùng chung.
	 */
	public static function instance(): Nghi_Quy_Quiz {
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

		/*
		 * Đọc session SỚM, trước khi trang bắt đầu in ra. Thẻ Nghi Quỹ nằm giữa
		 * thân trang, lúc đó header đã gửi xong và không mở session được nữa —
		 * đọc trước ở đây rồi nhớ lại thì mọi chỗ sau đó đều hỏi được.
		 */
		add_action( 'init', array( $this, 'chuan_bi_session' ), 1 );

		add_action( 'add_meta_boxes_nntm_publication', array( $this, 'add_meta_box' ) );
		add_action( 'save_post_nntm_publication', array( $this, 'save_meta_box' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );

		// Bộ câu hỏi dùng chung có màn riêng, nằm dưới menu Ấn phẩm.
		add_action( 'admin_menu', array( $this, 'them_trang_cau_hoi' ) );

		// Gác cửa: dùng chính bộ lọc quyền xem ấn phẩm của theme nên mọi đường
		// vào nội dung (trang /doc/, nút "Đọc ấn phẩm", AJAX lưu tiến độ đọc)
		// đều đi qua đây, không phải vá từng nơi.
		add_filter( 'nntm_an_pham_can_access', array( $this, 'loc_quyen_xem' ), 10, 3 );

		add_action( 'wp_ajax_nntm_quiz_cau_hoi', array( $this, 'ajax_cau_hoi' ) );
		add_action( 'wp_ajax_nopriv_nntm_quiz_cau_hoi', array( $this, 'ajax_can_dang_nhap' ) );
		add_action( 'wp_ajax_nntm_quiz_nop', array( $this, 'ajax_nop' ) );
		add_action( 'wp_ajax_nopriv_nntm_quiz_nop', array( $this, 'ajax_can_dang_nhap' ) );
	}

	/* ---------------------------------------------------------------------
	 * Dữ liệu
	 * ------------------------------------------------------------------ */

	/**
	 * Khai báo hai trường meta.
	 */
	public function register_meta(): void {
		$chi_nguoi_sua = static function () {
			return current_user_can( 'edit_posts' );
		};

		register_post_meta(
			'nntm_publication',
			self::META_CHE_DO,
			array(
				'type'              => 'string',
				'single'            => true,
				'default'           => 'public',
				'show_in_rest'      => true,
				'sanitize_callback' => array( $this, 'sanitize_che_do' ),
				'auth_callback'     => $chi_nguoi_sua,
				'description'       => __( 'Chế độ xem Nghi Quỹ: public = đã đăng nhập là xem được; quiz = phải trả lời đúng bộ câu hỏi.', 'nntm' ),
			)
		);

		/*
		 * show_in_rest = false: bộ câu hỏi chứa chỉ số đáp án đúng. Mở ra REST là
		 * người dùng chỉ cần gọi /wp-json/wp/v2/... là thấy hết đáp án.
		 */
		register_post_meta(
			'nntm_publication',
			self::META_QUIZ,
			array(
				'type'              => 'array',
				'single'            => true,
				'default'           => array(),
				'show_in_rest'      => false,
				'sanitize_callback' => array( $this, 'sanitize_quiz' ),
				'auth_callback'     => $chi_nguoi_sua,
				'description'       => __( 'Bộ câu hỏi gác cửa Nghi Quỹ. Chỉ đọc ở phía máy chủ.', 'nntm' ),
			)
		);
	}

	/**
	 * Lọc chế độ về đúng hai giá trị cho phép.
	 *
	 * @param mixed $gia_tri Giá trị thô.
	 */
	public function sanitize_che_do( $gia_tri ): string {
		$gia_tri = is_string( $gia_tri ) ? sanitize_key( $gia_tri ) : '';

		return 'quiz' === $gia_tri ? 'quiz' : 'public';
	}

	/**
	 * Lọc bộ câu hỏi về đúng hình dạng: mỗi câu có nội dung, ≥2 đáp án và một
	 * đáp án đúng hợp lệ. Câu nào không đủ thì bỏ, không lưu rác.
	 *
	 * @param mixed $gia_tri Giá trị thô.
	 * @return array<int,array{hoi:string,dap_an:array<int,string>,dung:int}>
	 */
	public function sanitize_quiz( $gia_tri ): array {
		if ( ! is_array( $gia_tri ) ) {
			return array();
		}

		$sach = array();

		foreach ( $gia_tri as $cau ) {
			if ( ! is_array( $cau ) ) {
				continue;
			}

			$hoi = isset( $cau['hoi'] ) ? sanitize_textarea_field( (string) $cau['hoi'] ) : '';
			$hoi = trim( $hoi );

			$dap_an_tho = isset( $cau['dap_an'] ) && is_array( $cau['dap_an'] ) ? $cau['dap_an'] : array();
			$dap_an     = array();

			/*
			 * Quản trị xoá một đáp án giữa chừng thì các đáp án còn lại vẫn giữ
			 * chỉ số cũ (0, 2, 3...). Phải nhớ chỉ số cũ ứng với vị trí mới nào
			 * rồi mới quy đổi "đáp án đúng", nếu không câu hỏi sẽ bị coi là hỏng
			 * và biến mất khi lưu.
			 */
			$doi_chi_so = array();

			foreach ( $dap_an_tho as $khoa_cu => $mot ) {
				$mot = trim( sanitize_text_field( (string) $mot ) );

				if ( '' === $mot ) {
					continue;
				}

				$doi_chi_so[ (string) $khoa_cu ] = count( $dap_an );
				$dap_an[]                        = $mot;
			}

			$dung_cu = isset( $cau['dung'] ) ? (string) $cau['dung'] : '';
			$dung    = array_key_exists( $dung_cu, $doi_chi_so ) ? (int) $doi_chi_so[ $dung_cu ] : -1;

			if ( '' === $hoi || count( $dap_an ) < 2 || $dung < 0 ) {
				continue;
			}

			$sach[] = array(
				'hoi'    => $hoi,
				'dap_an' => array_values( $dap_an ),
				'dung'   => $dung,
			);
		}

		return array_values( $sach );
	}

	/**
	 * Chế độ truy cập của một ấn phẩm.
	 *
	 * @param int $post_id ID ấn phẩm.
	 */
	public static function che_do( int $post_id ): string {
		$che_do = (string) get_post_meta( $post_id, self::META_CHE_DO, true );

		return 'quiz' === $che_do ? 'quiz' : 'public';
	}

	/**
	 * Ấn phẩm này có thuộc chủ đề Nghi Quỹ không?
	 *
	 * Đây là ranh giới phân biệt Nghi Quỹ với Ấn Phẩm thường. Chỉ Nghi Quỹ mới
	 * đi qua bộ câu hỏi; Ấn Phẩm khác giữ nguyên hành vi cũ.
	 *
	 * @param int $post_id ID ấn phẩm.
	 */
	public static function la_nghi_quy( int $post_id ): bool {
		if ( $post_id <= 0 || 'nntm_publication' !== get_post_type( $post_id ) ) {
			return false;
		}

		$slug = (string) apply_filters( 'nntm_nghi_quy_topic_slug', self::TOPIC_SLUG, $post_id );

		if ( '' === $slug || ! taxonomy_exists( self::TOPIC_TAXONOMY ) ) {
			return false;
		}

		return (bool) has_term( $slug, self::TOPIC_TAXONOMY, $post_id );
	}

	/**
	 * Ấn phẩm này có bắt trả lời câu hỏi không?
	 *
	 * Hai điều kiện, thiếu một là không: phải thuộc chủ đề Nghi Quỹ, VÀ quản trị
	 * phải chọn "Quiz Required".
	 *
	 * Trả về true kể cả khi quản trị chưa nhập câu hỏi nào: chọn "Quiz Required"
	 * mà bỏ trống là lỗi cấu hình, và cửa phải khép lại — mở toang nội dung mới
	 * là hỏng. Meta box có cảnh báo cho quản trị thấy ngay.
	 *
	 * @param int $post_id ID ấn phẩm.
	 */
	public static function can_quiz( int $post_id ): bool {
		if ( ! self::la_nghi_quy( $post_id ) ) {
			return false;
		}

		return 'quiz' === self::che_do( $post_id );
	}

	/**
	 * Bộ câu hỏi dùng chung — CHỈ dùng ở phía máy chủ (có đáp án đúng).
	 *
	 * @return array<int,array{hoi:string,dap_an:array<int,string>,dung:int}>
	 */
	public static function bo_cau_hoi_chung(): array {
		$quiz = get_option( self::OPTION_QUIZ, array() );

		return is_array( $quiz ) ? array_values( $quiz ) : array();
	}

	/**
	 * Bộ câu hỏi áp cho một ấn phẩm — CHỈ dùng ở phía máy chủ.
	 *
	 * Lấy bộ dùng chung trước. Chỉ khi bộ chung còn trống mới lùi về bộ riêng
	 * của ấn phẩm — đường lui này giữ cho những cuốn đã nhập câu hỏi từ trước
	 * ngày đổi cách làm không bị mất, chứ không phải một cách dùng được khuyến
	 * khích. Nhập bộ chung xong là nó thay thế hoàn toàn.
	 *
	 * @param int $post_id ID ấn phẩm.
	 * @return array<int,array{hoi:string,dap_an:array<int,string>,dung:int}>
	 */
	public static function cau_hoi( int $post_id ): array {
		$chung = self::bo_cau_hoi_chung();

		if ( $chung ) {
			return $chung;
		}

		$quiz = get_post_meta( $post_id, self::META_QUIZ, true );

		return is_array( $quiz ) ? array_values( $quiz ) : array();
	}

	/**
	 * Ấn phẩm này còn giữ bộ câu hỏi riêng kiểu cũ không?
	 *
	 * Dùng để báo cho quản trị biết vì sao cuốn này lại hỏi khác các cuốn kia.
	 *
	 * @param int $post_id ID ấn phẩm.
	 */
	public static function con_bo_rieng_cu( int $post_id ): bool {
		$quiz = get_post_meta( $post_id, self::META_QUIZ, true );

		return is_array( $quiz ) && ! empty( $quiz );
	}

	/* ---------------------------------------------------------------------
	 * PHP session
	 * ------------------------------------------------------------------ */

	/**
	 * Nạp sẵn trạng thái PASS vào bộ nhớ tạm khi request bắt đầu.
	 *
	 * Chỉ làm với người đã đăng nhập: khách vãng lai không thể đậu quiz, mở
	 * session cho họ chỉ tổ vô hiệu hoá cache trang.
	 */
	public function chuan_bi_session(): void {
		if ( is_admin() && ! wp_doing_ajax() ) {
			return;
		}

		if ( ! is_user_logged_in() ) {
			return;
		}

		self::doc_session();
	}

	/**
	 * Có được phép mở session trong ngữ cảnh hiện tại không?
	 *
	 * Không mở ở CLI/cron/REST để khỏi phá các luồng đó, và không mở khi header
	 * đã gửi xong vì lúc ấy không đặt được cookie session nữa.
	 */
	private static function duoc_mo_session(): bool {
		if ( 'cli' === PHP_SAPI ) {
			return false;
		}

		if ( defined( 'DOING_CRON' ) && DOING_CRON ) {
			return false;
		}

		if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
			return false;
		}

		return ! headers_sent();
	}

	/**
	 * Đọc danh sách Nghi Quỹ đã đậu trong session.
	 *
	 * Dùng read_and_close để KHÔNG giữ khoá tệp session — nếu giữ, mọi request
	 * của cùng một người sẽ phải xếp hàng chờ nhau.
	 * Chưa có cookie session thì chắc chắn chưa đậu gì, khỏi tạo session mới cho
	 * từng khách ghé qua.
	 *
	 * @return array<int,bool>
	 */
	private static function doc_session(): array {
		if ( null !== self::$da_doc_session ) {
			return self::$da_doc_session;
		}

		if ( PHP_SESSION_ACTIVE === session_status() ) {
			self::$da_doc_session = self::loc_danh_sach( $_SESSION[ self::SESSION_KEY ] ?? array() );
			return self::$da_doc_session;
		}

		if ( ! isset( $_COOKIE[ session_name() ] ) || ! self::duoc_mo_session() ) {
			self::$da_doc_session = array();
			return self::$da_doc_session;
		}

		session_start( array( 'read_and_close' => true ) );

		self::$da_doc_session = self::loc_danh_sach( $_SESSION[ self::SESSION_KEY ] ?? array() );

		return self::$da_doc_session;
	}

	/**
	 * Chỉ nhận map "ID ấn phẩm => true"; mọi thứ khác trong session bỏ qua.
	 *
	 * @param mixed $tho Giá trị thô trong session.
	 * @return array<int,bool>
	 */
	private static function loc_danh_sach( $tho ): array {
		if ( ! is_array( $tho ) ) {
			return array();
		}

		$sach = array();

		foreach ( $tho as $id => $co ) {
			$id = absint( $id );
			if ( $id > 0 && true === $co ) {
				$sach[ $id ] = true;
			}
		}

		return $sach;
	}

	/**
	 * Người dùng hiện tại đã đậu quiz của Nghi Quỹ này trong session này chưa?
	 *
	 * @param int $post_id ID ấn phẩm.
	 */
	public static function da_pass( int $post_id ): bool {
		if ( $post_id <= 0 || ! is_user_logged_in() ) {
			return false;
		}

		$danh_sach = self::doc_session();

		return ! empty( $danh_sach[ $post_id ] );
	}

	/**
	 * Ghi PASS cho một Nghi Quỹ vào session.
	 *
	 * Chỉ được gọi từ handler chấm bài, sau khi đã xác nhận đúng hết.
	 *
	 * @param int $post_id ID ấn phẩm.
	 */
	private static function ghi_pass( int $post_id ): bool {
		if ( $post_id <= 0 ) {
			return false;
		}

		if ( PHP_SESSION_ACTIVE !== session_status() ) {
			if ( ! self::duoc_mo_session() ) {
				return false;
			}
			// doc_session() có thể đã đọc-rồi-đóng; mở lại ở đây để ghi được.
			session_start();
		}

		$hien_co = self::loc_danh_sach( $_SESSION[ self::SESSION_KEY ] ?? array() );

		$hien_co[ $post_id ]           = true;
		$_SESSION[ self::SESSION_KEY ] = $hien_co;

		session_write_close();

		self::$da_doc_session = $hien_co;

		return true;
	}

	/* ---------------------------------------------------------------------
	 * Gác cửa
	 * ------------------------------------------------------------------ */

	/**
	 * Chốt quyền xem: phải đăng nhập, và phải đậu quiz của chính Nghi Quỹ đó.
	 *
	 * @param bool         $mo      Kết luận trước đó.
	 * @param \WP_Post|null $post    Ấn phẩm.
	 * @param int          $user_id Người dùng hiện tại.
	 */
	public function loc_quyen_xem( $mo, $post, $user_id ): bool {
		if ( ! $post instanceof \WP_Post ) {
			return (bool) $mo;
		}

		if ( ! self::can_quiz( (int) $post->ID ) ) {
			return (bool) $mo;
		}

		if ( (int) $user_id <= 0 ) {
			return false;
		}

		return self::da_pass( (int) $post->ID );
	}

	/* ---------------------------------------------------------------------
	 * AJAX
	 * ------------------------------------------------------------------ */

	/**
	 * Khách chưa đăng nhập gọi vào endpoint quiz.
	 */
	public function ajax_can_dang_nhap(): void {
		wp_send_json_error(
			array(
				'need_login' => true,
				'message'    => __( 'Vui lòng đăng nhập để xem Nghi Quỹ.', 'nntm' ),
			),
			401
		);
	}

	/**
	 * Lấy ID ấn phẩm từ request, đã kiểm nonce và loại nội dung.
	 */
	private function pub_tu_request(): int {
		check_ajax_referer( self::NONCE, 'nonce' );

		$post_id = isset( $_POST['pub'] ) ? absint( wp_unslash( $_POST['pub'] ) ) : 0;

		if ( $post_id <= 0 || 'nntm_publication' !== get_post_type( $post_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Không tìm thấy Nghi Quỹ.', 'nntm' ) ), 400 );
		}

		$post = get_post( $post_id );

		if ( ! $post instanceof \WP_Post || 'publish' !== $post->post_status ) {
			wp_send_json_error( array( 'message' => __( 'Không tìm thấy Nghi Quỹ.', 'nntm' ) ), 400 );
		}

		return $post_id;
	}

	/**
	 * Trả về câu hỏi cho popup. KHÔNG kèm đáp án đúng.
	 */
	public function ajax_cau_hoi(): void {
		$post_id = $this->pub_tu_request();

		if ( ! self::can_quiz( $post_id ) || self::da_pass( $post_id ) ) {
			// Không cần hỏi nữa: hoặc Nghi Quỹ này để public, hoặc đã đậu trong
			// session này. Cho khách đi thẳng vào nội dung.
			wp_send_json_success(
				array(
					'passed' => true,
					'url'    => $this->url_doc( $post_id ),
				)
			);
		}

		$cau_hoi = self::cau_hoi( $post_id );

		if ( empty( $cau_hoi ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'Nghi Quỹ này đang được thiết lập câu hỏi. Vui lòng quay lại sau.', 'nntm' ),
				),
				409
			);
		}

		$ra = array();

		foreach ( $cau_hoi as $cau ) {
			$ra[] = array(
				'hoi'    => (string) $cau['hoi'],
				'dap_an' => array_map( 'strval', (array) $cau['dap_an'] ),
			);
		}

		wp_send_json_success(
			array(
				'passed'   => false,
				'title'    => get_the_title( $post_id ),
				'cauHoi'   => $ra,
			)
		);
	}

	/**
	 * Chấm bài. Đúng hết mới ghi PASS.
	 */
	public function ajax_nop(): void {
		$post_id = $this->pub_tu_request();

		if ( ! self::can_quiz( $post_id ) ) {
			wp_send_json_success(
				array(
					'pass' => true,
					'url'  => $this->url_doc( $post_id ),
				)
			);
		}

		$cau_hoi = self::cau_hoi( $post_id );

		if ( empty( $cau_hoi ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'Nghi Quỹ này đang được thiết lập câu hỏi. Vui lòng quay lại sau.', 'nntm' ),
				),
				409
			);
		}

		$tra_loi_tho = isset( $_POST['tra_loi'] ) ? wp_unslash( $_POST['tra_loi'] ) : array();
		$tra_loi     = array();

		if ( is_array( $tra_loi_tho ) ) {
			foreach ( $tra_loi_tho as $chi_so => $chon ) {
				$tra_loi[ absint( $chi_so ) ] = (int) $chon;
			}
		}

		$dung_het = true;

		foreach ( $cau_hoi as $chi_so => $cau ) {
			$chon = array_key_exists( $chi_so, $tra_loi ) ? $tra_loi[ $chi_so ] : -1;

			if ( $chon !== (int) $cau['dung'] ) {
				$dung_het = false;
				break;
			}
		}

		if ( ! $dung_het ) {
			// Sai: KHÔNG ghi PASS, chỉ trả về đúng câu thông báo nghiệp vụ.
			wp_send_json_success(
				array(
					'pass'    => false,
					'message' => self::LOI_SAI,
				)
			);
		}

		self::ghi_pass( $post_id );

		wp_send_json_success(
			array(
				'pass' => true,
				'url'  => $this->url_doc( $post_id ),
			)
		);
	}

	/**
	 * Đường đọc Nghi Quỹ sau khi đã được phép.
	 *
	 * @param int $post_id ID ấn phẩm.
	 */
	private function url_doc( int $post_id ): string {
		if ( function_exists( 'nntm_doc_url' ) ) {
			$url = (string) nntm_doc_url( $post_id );

			if ( '' !== $url ) {
				return $url;
			}
		}

		return (string) get_permalink( $post_id );
	}

	/* ---------------------------------------------------------------------
	 * Màn quản trị
	 * ------------------------------------------------------------------ */

	/**
	 * Thêm meta box cấu hình quyền xem + câu hỏi.
	 */
	public function add_meta_box(): void {
		add_meta_box(
			'nntm_nghi_quy_quiz',
			__( 'Nghi Quỹ — Quyền xem', 'nntm' ),
			array( $this, 'render_meta_box' ),
			'nntm_publication',
			'side',
			'default'
		);
	}

	/**
	 * Đường dẫn màn sửa bộ câu hỏi dùng chung.
	 */
	public static function url_trang_cau_hoi(): string {
		return admin_url( 'edit.php?post_type=nntm_publication&page=nntm-bo-cau-hoi' );
	}

	/**
	 * Vẽ meta box — chỉ còn MỘT công tắc.
	 *
	 * Câu hỏi không nằm ở đây nữa: cả site dùng chung một bộ, sửa ở màn
	 * "Bộ câu hỏi Nghi Quỹ". Ở màn sửa từng cuốn chỉ cần trả lời một câu:
	 * cuốn này có bắt trả lời trước khi xem không.
	 *
	 * @param \WP_Post $post Ấn phẩm đang sửa.
	 */
	public function render_meta_box( $post ): void {
		wp_nonce_field( 'nntm_nghi_quy_quiz', 'nntm_nghi_quy_quiz_nonce' );

		$bat         = 'quiz' === self::che_do( (int) $post->ID );
		$la_nghi_quy = self::la_nghi_quy( (int) $post->ID );
		$so_cau      = count( self::bo_cau_hoi_chung() );
		?>
		<p>
			<label>
				<input type="checkbox" name="nntm_access_mode" value="quiz" <?php checked( $bat ); ?> />
				<strong><?php esc_html_e( 'Bắt trả lời câu hỏi trước khi xem', 'nntm' ); ?></strong>
			</label>
		</p>

		<p class="description">
			<?php esc_html_e( 'Bỏ trống thì thành viên đã đăng nhập xem được ngay.', 'nntm' ); ?>
		</p>

		<?php if ( ! $la_nghi_quy ) : ?>
			<div class="notice notice-info inline" style="margin:8px 0;padding:6px 10px;">
				<p style="margin:.4em 0;">
					<?php esc_html_e( 'Công tắc này chỉ có tác dụng với ấn phẩm thuộc chủ đề "Nghi Quỹ". Cuốn này chưa gắn chủ đề đó nên vẫn mở bình thường — gắn ở hộp "Chủ đề" rồi lưu lại.', 'nntm' ); ?>
				</p>
			</div>
		<?php endif; ?>

		<?php if ( $bat && $la_nghi_quy && 0 === $so_cau && ! self::con_bo_rieng_cu( (int) $post->ID ) ) : ?>
			<div class="notice notice-warning inline" style="margin:8px 0;padding:6px 10px;">
				<p style="margin:.4em 0;">
					<?php esc_html_e( 'Đang bật nhưng bộ câu hỏi dùng chung chưa có câu nào — hiện KHÔNG ai đọc được cuốn này.', 'nntm' ); ?>
				</p>
			</div>
		<?php endif; ?>

		<?php if ( self::con_bo_rieng_cu( (int) $post->ID ) && 0 === $so_cau ) : ?>
			<div class="notice notice-info inline" style="margin:8px 0;padding:6px 10px;">
				<p style="margin:.4em 0;">
					<?php esc_html_e( 'Cuốn này còn bộ câu hỏi riêng nhập từ trước. Nó vẫn đang được dùng cho tới khi bộ chung có câu hỏi.', 'nntm' ); ?>
				</p>
			</div>
		<?php endif; ?>

		<p>
			<a href="<?php echo esc_url( self::url_trang_cau_hoi() ); ?>">
				<?php
				printf(
					/* translators: %d: số câu hỏi trong bộ dùng chung. */
					esc_html__( 'Sửa bộ câu hỏi dùng chung (%d câu) →', 'nntm' ),
					(int) $so_cau
				);
				?>
			</a>
		</p>
		<?php
	}

	/**
	 * Thêm màn "Bộ câu hỏi Nghi Quỹ" dưới menu Ấn phẩm.
	 */
	public function them_trang_cau_hoi(): void {
		/*
		 * Nhớ lại đúng hook suffix mà WordPress cấp, KHÔNG tự đoán chuỗi.
		 * Tên hook của trang con phụ thuộc vào menu cha đã được dựng hay chưa —
		 * đoán "nntm_publication_page_..." là sai trong nhiều ngữ cảnh.
		 */
		$this->hook_trang_cau_hoi = (string) add_submenu_page(
			'edit.php?post_type=nntm_publication',
			__( 'Bộ câu hỏi Nghi Quỹ', 'nntm' ),
			__( 'Bộ câu hỏi Nghi Quỹ', 'nntm' ),
			'manage_options',
			'nntm-bo-cau-hoi',
			array( $this, 'render_trang_cau_hoi' )
		);
	}

	/**
	 * Hook suffix của màn bộ câu hỏi, do WordPress cấp lúc đăng ký.
	 *
	 * @var string
	 */
	private string $hook_trang_cau_hoi = '';

	/**
	 * Vẽ màn sửa bộ câu hỏi dùng chung.
	 *
	 * Dùng lại đúng hai hàm dựng dòng của meta box cũ, nên chỉ có MỘT nguồn
	 * markup và JS thêm/bớt câu hỏi chạy y nguyên, không phải viết lại.
	 */
	public function render_trang_cau_hoi(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$da_luu = false;

		if ( isset( $_POST['nntm_luu_bo_cau_hoi'] ) && check_admin_referer( 'nntm_bo_cau_hoi' ) ) {
			$tho = isset( $_POST['nntm_quiz'] ) && is_array( $_POST['nntm_quiz'] ) ? wp_unslash( $_POST['nntm_quiz'] ) : array();

			// Bỏ hàng mẫu của JS nếu vì lý do nào đó nó bị gửi lên.
			unset( $tho['__i__'] );

			update_option( self::OPTION_QUIZ, $this->sanitize_quiz( array_values( $tho ) ) );
			$da_luu = true;
		}

		$cau_hoi = self::bo_cau_hoi_chung();
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Bộ câu hỏi Nghi Quỹ', 'nntm' ); ?></h1>

			<?php if ( $da_luu ) : ?>
				<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Đã lưu bộ câu hỏi.', 'nntm' ); ?></p></div>
			<?php endif; ?>

			<p class="description" style="max-width:60em;">
				<?php esc_html_e( 'Bộ câu hỏi này dùng chung cho MỌI Nghi Quỹ có bật công tắc "Bắt trả lời câu hỏi trước khi xem". Sửa ở đây là mọi Nghi Quỹ đổi theo.', 'nntm' ); ?>
				<br />
				<?php esc_html_e( 'Người đọc phải trả lời đúng tất cả câu mới được vào, và được làm lại không giới hạn số lần.', 'nntm' ); ?>
				<br />
				<?php esc_html_e( 'Mỗi câu cần ít nhất 2 đáp án và phải chọn đúng một đáp án đúng. Câu thiếu nội dung, thiếu đáp án hoặc chưa chọn đáp án đúng sẽ bị bỏ khi lưu.', 'nntm' ); ?>
			</p>

			<?php if ( empty( $cau_hoi ) ) : ?>
				<div class="notice notice-warning inline"><p>
					<?php esc_html_e( 'Bộ câu hỏi đang trống. Nghi Quỹ nào đã bật công tắc thì hiện KHÔNG ai đọc được — cửa đóng lại là đúng, nhưng hãy thêm câu hỏi.', 'nntm' ); ?>
				</p></div>
			<?php endif; ?>

			<form method="post" class="nntm-quiz-admin" data-nntm-quiz-admin>
				<?php wp_nonce_field( 'nntm_bo_cau_hoi' ); ?>

				<div data-nntm-quiz-list>
					<?php
					if ( empty( $cau_hoi ) ) {
						$this->render_cau_hoi_row( 0, array() );
					} else {
						foreach ( $cau_hoi as $chi_so => $cau ) {
							$this->render_cau_hoi_row( (int) $chi_so, $cau );
						}
					}
					?>
				</div>

				<p>
					<button type="button" class="button" data-nntm-quiz-add-question><?php esc_html_e( '+ Thêm câu hỏi', 'nntm' ); ?></button>
				</p>

				<template data-nntm-quiz-question-template>
					<?php $this->render_cau_hoi_row( -1, array() ); ?>
				</template>
				<template data-nntm-quiz-answer-template>
					<?php $this->render_dap_an_row( -1, -1, '', false ); ?>
				</template>

				<p class="submit">
					<button type="submit" name="nntm_luu_bo_cau_hoi" value="1" class="button button-primary">
						<?php esc_html_e( 'Lưu bộ câu hỏi', 'nntm' ); ?>
					</button>
				</p>
			</form>
		</div>
		<?php
	}

	/**
	 * Một khối câu hỏi trong màn sửa bộ câu hỏi dùng chung.
	 *
	 * @param int                  $chi_so Chỉ số câu hỏi (-1 = mẫu cho JS).
	 * @param array<string,mixed>  $cau    Dữ liệu câu hỏi.
	 */
	private function render_cau_hoi_row( int $chi_so, array $cau ): void {
		$khoa   = $chi_so >= 0 ? (string) $chi_so : '__i__';
		$hoi    = isset( $cau['hoi'] ) ? (string) $cau['hoi'] : '';
		$dap_an = isset( $cau['dap_an'] ) && is_array( $cau['dap_an'] ) ? array_values( $cau['dap_an'] ) : array( '', '' );
		$dung   = isset( $cau['dung'] ) ? (int) $cau['dung'] : -1;
		?>
		<div class="nntm-quiz-admin__cau" data-nntm-quiz-question data-index="<?php echo esc_attr( $khoa ); ?>" style="margin:0 0 18px;padding:12px 14px;border:1px solid #dcdcde;background:#fff;">
			<p style="margin-top:0;">
				<label style="display:block;font-weight:600;margin-bottom:4px;"><?php esc_html_e( 'Câu hỏi', 'nntm' ); ?></label>
				<textarea
					name="nntm_quiz[<?php echo esc_attr( $khoa ); ?>][hoi]"
					rows="2"
					class="large-text"
					data-nntm-quiz-field="hoi"
				><?php echo esc_textarea( $hoi ); ?></textarea>
			</p>

			<p style="margin-bottom:4px;font-weight:600;"><?php esc_html_e( 'Đáp án (đánh dấu ô tròn ở đáp án đúng)', 'nntm' ); ?></p>

			<div data-nntm-quiz-answers>
				<?php foreach ( $dap_an as $vi_tri => $nhan ) : ?>
					<?php $this->render_dap_an_row( $chi_so, (int) $vi_tri, (string) $nhan, (int) $vi_tri === $dung ); ?>
				<?php endforeach; ?>
			</div>

			<p>
				<button type="button" class="button button-small" data-nntm-quiz-add-answer><?php esc_html_e( '+ Thêm đáp án', 'nntm' ); ?></button>
				<button type="button" class="button button-small button-link-delete" data-nntm-quiz-remove-question style="float:right;"><?php esc_html_e( 'Xoá câu hỏi này', 'nntm' ); ?></button>
			</p>
		</div>
		<?php
	}

	/**
	 * Một dòng đáp án.
	 *
	 * @param int    $cau_index Chỉ số câu hỏi (-1 = mẫu).
	 * @param int    $vi_tri    Chỉ số đáp án (-1 = mẫu).
	 * @param string $nhan      Nội dung đáp án.
	 * @param bool   $la_dung   Có phải đáp án đúng.
	 */
	private function render_dap_an_row( int $cau_index, int $vi_tri, string $nhan, bool $la_dung ): void {
		$khoa_cau = $cau_index >= 0 ? (string) $cau_index : '__i__';
		$khoa_dap = $vi_tri >= 0 ? (string) $vi_tri : '__j__';
		?>
		<p data-nntm-quiz-answer style="display:flex;align-items:center;gap:8px;margin:0 0 6px;">
			<input
				type="radio"
				name="nntm_quiz[<?php echo esc_attr( $khoa_cau ); ?>][dung]"
				value="<?php echo esc_attr( $khoa_dap ); ?>"
				<?php checked( $la_dung ); ?>
				data-nntm-quiz-field="dung"
			/>
			<input
				type="text"
				name="nntm_quiz[<?php echo esc_attr( $khoa_cau ); ?>][dap_an][<?php echo esc_attr( $khoa_dap ); ?>]"
				value="<?php echo esc_attr( $nhan ); ?>"
				class="regular-text"
				style="flex:1 1 auto;"
				data-nntm-quiz-field="dap_an"
			/>
			<button type="button" class="button button-small" data-nntm-quiz-remove-answer aria-label="<?php esc_attr_e( 'Xoá đáp án', 'nntm' ); ?>">&times;</button>
		</p>
		<?php
	}

	/**
	 * Lưu cấu hình.
	 *
	 * @param int $post_id ID ấn phẩm.
	 */
	public function save_meta_box( int $post_id ): void {
		if ( ! isset( $_POST['nntm_nghi_quy_quiz_nonce'] )
			|| ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nntm_nghi_quy_quiz_nonce'] ) ), 'nntm_nghi_quy_quiz' )
		) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		/*
		 * Ô tích: không gửi lên nghĩa là tắt. Trước đây là hai nút tròn nên lúc
		 * nào cũng có giá trị; nay phải mặc định về 'public' khi vắng mặt.
		 */
		$bat = isset( $_POST['nntm_access_mode'] ) && 'quiz' === sanitize_key( wp_unslash( $_POST['nntm_access_mode'] ) );
		update_post_meta( $post_id, self::META_CHE_DO, $bat ? 'quiz' : 'public' );

		/*
		 * KHÔNG đụng vào META_QUIZ ở đây nữa. Câu hỏi đã chuyển sang bộ dùng
		 * chung; ghi đè bằng mảng rỗng mỗi lần lưu bài sẽ xoá mất bộ riêng cũ
		 * của những cuốn nhập từ trước — thứ đang được dùng làm đường lui.
		 */
	}

	/**
	 * Nạp JS thêm/bớt câu hỏi cho màn sửa ấn phẩm.
	 *
	 * @param string $hook Slug màn quản trị.
	 */
	public function enqueue_admin_assets( string $hook ): void {
		/*
		 * Từ khi câu hỏi dùng chung, bộ thêm/bớt câu hỏi nằm ở màn riêng chứ
		 * không còn trong màn sửa bài. Màn sửa bài giờ chỉ có một ô tích, không
		 * cần JS này nữa.
		 */
		if ( '' === $this->hook_trang_cau_hoi || $hook !== $this->hook_trang_cau_hoi ) {
			return;
		}

		$js_path = NNTM_CORE_DIR . 'assets/js/nghi-quy-quiz-admin.js';

		wp_enqueue_script(
			'nntm-nghi-quy-quiz-admin',
			NNTM_CORE_URL . 'assets/js/nghi-quy-quiz-admin.js',
			array(),
			file_exists( $js_path ) ? (string) filemtime( $js_path ) : NNTM_CORE_VERSION,
			true
		);
	}
}
