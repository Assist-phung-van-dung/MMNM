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
	}
}
