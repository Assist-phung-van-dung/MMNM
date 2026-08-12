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
	}
}
