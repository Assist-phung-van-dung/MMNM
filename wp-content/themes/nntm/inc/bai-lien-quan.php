<?php
/**
 * Chọn tay các bài liên quan cho từng bài.
 *
 * Mặc định dải "Bài viết liên quan" ở trang chi tiết tự lấy bài cùng phân mục,
 * mới nhất trước. Quản trị không can thiệp được, nên bài quan trọng có thể
 * không bao giờ xuất hiện. Tệp này cho phép chọn đích danh từng bài và xếp
 * đúng thứ tự muốn hiện.
 *
 * Nguyên tắc: CHỌN THÌ ĐÈ, KHÔNG CHỌN THÌ GIỮ NGUYÊN NHƯ CŨ. Bài nào chưa đụng
 * tới vẫn chạy cơ chế tự động y như trước, không phải đi sửa lại từng bài.
 *
 * Lưu bằng post meta dạng mảng ID, có thứ tự. Không lưu đường dẫn: đổi slug
 * hay đổi tên miền là đường dẫn cũ chết, còn ID thì luôn tra ra bài thật.
 */

defined( 'ABSPATH' ) || exit;

/** Khoá meta giữ danh sách ID. Gạch dưới đầu để ẩn khỏi bảng Custom Fields. */
const NNTM_LIEN_QUAN_META = '_nntm_bai_lien_quan';

/** Nhiều nhất bấy nhiêu bài — đúng bằng trần của khối card-list. */
const NNTM_LIEN_QUAN_TOI_DA = 24;

/**
 * Những loại nội dung có dải "liên quan" ở trang chi tiết.
 *
 * Lấy đúng các template thật sự dựng dải đó: single-nntm_article.php,
 * single.php, single-nntm_retreat.php và template dùng chung cpt-detail.php.
 * Loại nào không có dải thì thêm bảng chọn vào chỉ tổ rối mắt.
 *
 * @return string[]
 */
function nntm_lien_quan_cac_loai(): array {
	$loai = array( 'nntm_article', 'post', 'nntm_retreat' );

	if ( function_exists( 'nntm_cpt_shared_single_post_types' ) ) {
		$loai = array_merge( $loai, (array) nntm_cpt_shared_single_post_types() );
	}

	$loai = array_values( array_unique( array_filter( array_map( 'sanitize_key', $loai ) ) ) );

	/**
	 * Thêm/bớt loại nội dung được chọn bài liên quan.
	 *
	 * @param string[] $loai Danh sách loại nội dung.
	 */
	return array_values( (array) apply_filters( 'nntm_lien_quan_cac_loai', $loai ) );
}

/** Loại nội dung này có chọn bài liên quan được không? */
function nntm_lien_quan_ho_tro( string $post_type ): bool {
	return in_array( $post_type, nntm_lien_quan_cac_loai(), true );
}

/**
 * Lọc một danh sách ID thô thành danh sách dùng được.
 *
 * Bỏ: số không hợp lệ, trùng lặp, chính bài đang xem, bài đã xoá, và bài chưa
 * đăng. Vì sao phải lọc kỹ: quản trị chọn xong rồi xoá bài kia đi là chuyện
 * thường; không lọc thì khối card-list nhận một ID chết và dải liên quan hụt
 * mất một thẻ mà không ai hiểu vì sao.
 *
 * @param mixed $tho     Giá trị thô trong meta.
 * @param int   $tru_ra  ID tự loại khỏi kết quả (bài đang xem).
 * @return int[] Danh sách ID đã lọc, giữ nguyên thứ tự quản trị đã xếp.
 */
function nntm_lien_quan_loc_ids( $tho, int $tru_ra = 0 ): array {
	if ( ! is_array( $tho ) ) {
		return array();
	}

	$ra = array();

	foreach ( $tho as $id ) {
		$id = absint( $id );

		if ( $id < 1 || $id === $tru_ra || in_array( $id, $ra, true ) ) {
			continue;
		}

		if ( 'publish' !== get_post_status( $id ) ) {
			continue;
		}

		$ra[] = $id;

		if ( count( $ra ) >= NNTM_LIEN_QUAN_TOI_DA ) {
			break;
		}
	}

	return $ra;
}

/**
 * Danh sách bài liên quan do quản trị chọn tay cho một bài.
 *
 * Trả về mảng rỗng nghĩa là "chưa chọn gì" — nơi gọi phải hiểu đó là tín hiệu
 * dùng lại cơ chế tự động cũ, chứ không phải "không có bài liên quan nào".
 *
 * @return int[]
 */
function nntm_lien_quan_ids( int $post_id ): array {
	$post_id = absint( $post_id );

	if ( $post_id < 1 ) {
		return array();
	}

	return nntm_lien_quan_loc_ids( get_post_meta( $post_id, NNTM_LIEN_QUAN_META, true ), $post_id );
}

/*
 * ---------------------------------------------------------------------------
 * Đăng ký meta
 * ---------------------------------------------------------------------------
 */

function nntm_lien_quan_dang_ky_meta(): void {
	foreach ( nntm_lien_quan_cac_loai() as $loai ) {
		/*
		 * BẮT BUỘC khi lưu meta qua trình soạn thảo khối/REST. Thiếu dòng này
		 * thì bảng bên phải vẫn hiện danh sách vừa chọn nhưng tải lại là mất.
		 */
		add_post_type_support( $loai, 'custom-fields' );

		register_post_meta(
			$loai,
			NNTM_LIEN_QUAN_META,
			array(
				'type'              => 'array',
				'single'            => true,
				'default'           => array(),
				'show_in_rest'      => array(
					'schema' => array(
						'type'  => 'array',
						'items' => array(
							'type'    => 'integer',
							'minimum' => 0,
						),
					),
				),
				/*
				 * Lọc lúc lưu KHÔNG trừ bài đang sửa: hàm sanitize không biết
				 * mình đang ở bài nào. Việc tự loại chính nó để lúc đọc lo.
				 */
				'sanitize_callback' => static function ( $tho ): array {
					return nntm_lien_quan_loc_ids( $tho );
				},
				'auth_callback'     => static function ( $cho_phep, $khoa, $post_id ): bool {
					unset( $cho_phep, $khoa );

					$post_id = absint( $post_id );

					return $post_id > 0 && current_user_can( 'edit_post', $post_id );
				},
			)
		);
	}
}
/*
 * Ưu tiên 30: các loại nội dung do nntm-core đăng ký ở init mặc định (10).
 * Chạy sớm hơn thì danh sách loại còn rỗng, không đăng ký được gì.
 */
add_action( 'init', 'nntm_lien_quan_dang_ky_meta', 30 );

/*
 * ---------------------------------------------------------------------------
 * Dựng thuộc tính cho khối card-list
 * ---------------------------------------------------------------------------
 */

/**
 * Ghép phần "chọn tay" vào bộ thuộc tính của khối card-list.
 *
 * Nơi gọi cứ dựng bộ thuộc tính tự động như cũ rồi đưa qua đây. Có chọn tay
 * thì hàm đè lên; không có thì trả lại y nguyên. Nhờ vậy năm chỗ dựng dải
 * liên quan trong theme chỉ phải thêm đúng một lời gọi.
 *
 * @param array $attrs   Thuộc tính đã dựng theo cơ chế tự động.
 * @param int   $post_id Bài đang xem.
 * @return array Thuộc tính cuối cùng.
 */
function nntm_lien_quan_ap_vao_attrs( array $attrs, int $post_id ): array {
	$ids = nntm_lien_quan_ids( $post_id );

	if ( empty( $ids ) ) {
		return $attrs;
	}

	$attrs['orderBy']        = 'manual';
	$attrs['manualOrderIds'] = implode( ',', $ids );

	/*
	 * Nhánh 'manual' của card-list cắt danh sách theo postsPerPage, nên phải
	 * nới đủ rộng, nếu không quản trị chọn 8 bài mà chỉ hiện 6.
	 */
	$attrs['postsPerPage'] = max( 1, min( NNTM_LIEN_QUAN_TOI_DA, count( $ids ) ) );

	/*
	 * Bỏ ràng buộc phân mục: đã chọn đích danh thì không lọc theo term nữa,
	 * nếu không bài liên quan khác phân mục sẽ bị loại ngay.
	 */
	unset( $attrs['taxonomy'], $attrs['termId'] );

	return $attrs;
}

/*
 * ---------------------------------------------------------------------------
 * Quản trị: bảng chọn bài
 * ---------------------------------------------------------------------------
 */

/** Loại nội dung của màn hình quản trị đang mở. */
function nntm_lien_quan_loai_man_hinh(): string {
	$man_hinh = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

	return $man_hinh instanceof WP_Screen ? (string) $man_hinh->post_type : '';
}

function nntm_lien_quan_tai_bang_khoi(): void {
	$loai = nntm_lien_quan_loai_man_hinh();

	if ( '' === $loai || ! nntm_lien_quan_ho_tro( $loai ) ) {
		return;
	}

	$js  = NNTM_THEME_DIR . '/assets/js/admin/bai-lien-quan-panel.js';
	$css = NNTM_THEME_DIR . '/assets/css/admin/bai-lien-quan.css';

	wp_enqueue_script(
		'nntm-bai-lien-quan-panel',
		NNTM_THEME_URI . '/assets/js/admin/bai-lien-quan-panel.js',
		array( 'wp-components', 'wp-core-data', 'wp-data', 'wp-editor', 'wp-element', 'wp-i18n', 'wp-plugins' ),
		nntm_asset_version( $js ),
		true
	);

	/*
	 * Cùng một tệp JS chạy trên mọi màn hình sửa bài, nên nó phải tự biết mình
	 * được phép hiện ở những loại nội dung nào.
	 */
	wp_add_inline_script(
		'nntm-bai-lien-quan-panel',
		'window.nntmLienQuanLoaiBai = ' . wp_json_encode( nntm_lien_quan_cac_loai() ) . ';',
		'before'
	);

	wp_enqueue_style(
		'nntm-bai-lien-quan-admin',
		NNTM_THEME_URI . '/assets/css/admin/bai-lien-quan.css',
		array(),
		nntm_asset_version( $css )
	);
}
add_action( 'enqueue_block_editor_assets', 'nntm_lien_quan_tai_bang_khoi' );
