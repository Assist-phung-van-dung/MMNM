<?php
/**
 * Nhạc nền cho từng bài.
 *
 * Quản trị chọn MỘT tệp âm thanh trong Thư viện cho một bài; trang chi tiết
 * hiện một thanh nhạc nhỏ ngay dưới tiêu đề và tự phát. Bài nào không chọn thì
 * không in ra gì cả — không thanh nhạc, không CSS, không JS.
 *
 * Lưu bằng post meta chứ không phải taxonomy hay option: mỗi bài một bản nhạc
 * riêng, và bản nhạc đi theo bài khi nhân bản/khôi phục bản nháp.
 */

defined( 'ABSPATH' ) || exit;

/** Khoá meta giữ ID tệp đính kèm. Có gạch dưới đầu để ẩn khỏi bảng Custom Fields. */
const NNTM_NHAC_META = '_nntm_nhac_nen';

/** Nonce cho ô nhập ở trình soạn thảo cũ. */
const NNTM_NHAC_NONCE     = 'nntm_nhac_nen_nonce';
const NNTM_NHAC_HANH_DONG = 'nntm_luu_nhac_nen';

/**
 * Những loại nội dung được chọn nhạc nền.
 *
 * Lấy hết loại công khai có màn hình quản trị, rồi bỏ ra hai chỗ:
 *
 * - nntm_publication — ấn phẩm có trình nghe nhạc thiết kế riêng, thêm thanh
 *   nhạc nữa là hai trình phát cùng kêu một lúc.
 * - nntm_zen_track   — bài nhạc của Thiền Đường vốn ĐÃ là một tệp âm thanh
 *   (meta _nntm_track_audio); gắn thêm "nhạc nền" ở đó là vô nghĩa.
 * - page             — trang tĩnh phần lớn dựng bằng khối tràn viền và giấu
 *   tiêu đề (xem nntm_should_hide_page_title), nên không có chỗ nào tử tế để
 *   đặt thanh nhạc. Cần bật thì thêm lại bằng bộ lọc dưới đây.
 *
 * Đi qua bộ lọc để sau này thêm/bớt mà không phải sửa tệp này:
 *
 *     add_filter( 'nntm_nhac_cac_loai', fn( $loai ) => array_merge( $loai, array( 'page' ) ) );
 *
 * @return string[]
 */
function nntm_nhac_cac_loai(): array {
	$tat_ca = get_post_types(
		array(
			'public'  => true,
			'show_ui' => true,
		),
		'names'
	);

	$bo_ra = array( 'attachment', 'page', 'nntm_publication', 'nntm_zen_track' );

	return array_values( (array) apply_filters( 'nntm_nhac_cac_loai', array_diff( $tat_ca, $bo_ra ) ) );
}

/** Loại nội dung này có chọn nhạc nền được không? */
function nntm_nhac_ho_tro( string $post_type ): bool {
	return in_array( $post_type, nntm_nhac_cac_loai(), true );
}

/**
 * Tệp đính kèm hợp lệ, hay null.
 *
 * Kiểm cả ba thứ: còn tồn tại, đúng là tệp đính kèm, và đúng là âm thanh. Chọn
 * xong rồi xoá tệp trong Thư viện là chuyện thường; không kiểm thì trang chi
 * tiết in ra một thanh nhạc phát vào hư không.
 */
function nntm_nhac_tep( int $attachment_id ): ?WP_Post {
	if ( $attachment_id < 1 || 'attachment' !== get_post_type( $attachment_id ) ) {
		return null;
	}

	if ( 0 !== strpos( (string) get_post_mime_type( $attachment_id ), 'audio/' ) ) {
		return null;
	}

	$tep = get_post( $attachment_id );

	return $tep instanceof WP_Post ? $tep : null;
}

/** ID bản nhạc của một bài, 0 nếu chưa chọn hoặc tệp đã mất. */
function nntm_nhac_id( int $post_id ): int {
	$id = absint( get_post_meta( $post_id, NNTM_NHAC_META, true ) );

	return nntm_nhac_tep( $id ) ? $id : 0;
}

/**
 * Tên hiện dưới tiêu đề bài.
 *
 * Ưu tiên tiêu đề người dùng đặt trong Thư viện; chưa đặt thì lấy tên tệp, vẫn
 * hơn là để trống.
 */
function nntm_nhac_ten( WP_Post $tep ): string {
	$ten = trim( (string) $tep->post_title );

	if ( '' !== $ten ) {
		return $ten;
	}

	$duong_dan = get_attached_file( $tep->ID );

	return $duong_dan ? wp_basename( $duong_dan ) : sprintf( '#%d', $tep->ID );
}

/*
 * ---------------------------------------------------------------------------
 * Đăng ký meta
 * ---------------------------------------------------------------------------
 */

function nntm_nhac_dang_ky_meta(): void {
	foreach ( nntm_nhac_cac_loai() as $loai ) {
		/*
		 * BẮT BUỘC khi lưu meta qua trình soạn thảo khối/REST. Thiếu dòng này
		 * thì bảng bên phải vẫn hiện tệp vừa chọn nhưng tải lại trang là mất.
		 */
		add_post_type_support( $loai, 'custom-fields' );

		register_post_meta(
			$loai,
			NNTM_NHAC_META,
			array(
				'type'              => 'integer',
				'single'            => true,
				'default'           => 0,
				'show_in_rest'      => array(
					'schema' => array(
						'type'    => 'integer',
						'minimum' => 0,
					),
				),
				'sanitize_callback' => static function ( $tho ): int {
					$id = absint( $tho );

					return nntm_nhac_tep( $id ) ? $id : 0;
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
 * Ưu tiên 30: các loại nội dung do plugin nntm-core đăng ký ở init mặc định
 * (10). Chạy sớm hơn thì danh sách loại còn rỗng, không đăng ký được gì.
 */
add_action( 'init', 'nntm_nhac_dang_ky_meta', 30 );

/*
 * ---------------------------------------------------------------------------
 * Quản trị: bảng chọn tệp
 * ---------------------------------------------------------------------------
 */

/** Loại nội dung của màn hình quản trị đang mở. */
function nntm_nhac_loai_man_hinh(): string {
	$man_hinh = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

	return $man_hinh instanceof WP_Screen ? (string) $man_hinh->post_type : '';
}

/** Trình soạn thảo khối đang bật cho loại nội dung này? */
function nntm_nhac_dung_khoi( string $post_type ): bool {
	return function_exists( 'use_block_editor_for_post_type' )
		&& use_block_editor_for_post_type( $post_type );
}

function nntm_nhac_tai_bang_khoi(): void {
	$loai = nntm_nhac_loai_man_hinh();

	if ( '' === $loai || ! nntm_nhac_ho_tro( $loai ) ) {
		return;
	}

	$js  = NNTM_THEME_DIR . '/assets/js/admin/nhac-bai-viet-panel.js';
	$css = NNTM_THEME_DIR . '/assets/css/admin/nhac-bai-viet.css';

	wp_enqueue_media();
	wp_enqueue_script(
		'nntm-nhac-bai-viet-panel',
		NNTM_THEME_URI . '/assets/js/admin/nhac-bai-viet-panel.js',
		array( 'wp-block-editor', 'wp-components', 'wp-core-data', 'wp-data', 'wp-editor', 'wp-element', 'wp-i18n', 'wp-plugins' ),
		nntm_asset_version( $js ),
		true
	);

	/*
	 * Bảng bên phải phải biết nó được phép hiện ở những loại nội dung nào —
	 * cùng một tệp JS chạy trên mọi màn hình sửa bài.
	 */
	wp_add_inline_script(
		'nntm-nhac-bai-viet-panel',
		'window.nntmNhacLoaiBai = ' . wp_json_encode( nntm_nhac_cac_loai() ) . ';',
		'before'
	);

	wp_enqueue_style(
		'nntm-nhac-bai-viet-admin',
		NNTM_THEME_URI . '/assets/css/admin/nhac-bai-viet.css',
		array(),
		nntm_asset_version( $css )
	);
}
add_action( 'enqueue_block_editor_assets', 'nntm_nhac_tai_bang_khoi' );

/**
 * Trình soạn thảo cũ: nạp thư viện media và tệp chọn nhạc.
 *
 * Vì sao vẫn giữ đường lui này: site có thể tắt trình soạn thảo khối cho một
 * vai trò hay một loại nội dung; lúc đó bảng bên phải không tồn tại, mất luôn
 * chỗ chọn nhạc.
 */
function nntm_nhac_tai_bang_cu( string $hook ): void {
	if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
		return;
	}

	$loai = nntm_nhac_loai_man_hinh();

	if ( '' === $loai || ! nntm_nhac_ho_tro( $loai ) || nntm_nhac_dung_khoi( $loai ) ) {
		return;
	}

	$js  = NNTM_THEME_DIR . '/assets/js/admin/nhac-bai-viet.js';
	$css = NNTM_THEME_DIR . '/assets/css/admin/nhac-bai-viet.css';

	wp_enqueue_media();
	wp_enqueue_script(
		'nntm-nhac-bai-viet-admin',
		NNTM_THEME_URI . '/assets/js/admin/nhac-bai-viet.js',
		array( 'media-editor' ),
		nntm_asset_version( $js ),
		true
	);
	wp_enqueue_style(
		'nntm-nhac-bai-viet-admin',
		NNTM_THEME_URI . '/assets/css/admin/nhac-bai-viet.css',
		array(),
		nntm_asset_version( $css )
	);
}
add_action( 'admin_enqueue_scripts', 'nntm_nhac_tai_bang_cu' );

function nntm_nhac_them_o_nhap( string $post_type ): void {
	if ( ! nntm_nhac_ho_tro( $post_type ) || nntm_nhac_dung_khoi( $post_type ) ) {
		return;
	}

	add_meta_box(
		'nntm-nhac-nen',
		__( 'Nhạc nền', 'nntm' ),
		'nntm_nhac_ve_o_nhap',
		$post_type,
		'side',
		'default'
	);
}
add_action( 'add_meta_boxes', 'nntm_nhac_them_o_nhap' );

function nntm_nhac_ve_o_nhap( WP_Post $post ): void {
	$id  = nntm_nhac_id( (int) $post->ID );
	$tep = $id ? nntm_nhac_tep( $id ) : null;

	wp_nonce_field( NNTM_NHAC_HANH_DONG, NNTM_NHAC_NONCE );
	?>
	<div class="nntm-nhac-o" data-nntm-nhac-o>
		<input type="hidden" name="nntm_nhac_nen_id" value="<?php echo esc_attr( (string) $id ); ?>" data-nntm-nhac-id>

		<div class="nntm-nhac-o__dang-chon" data-nntm-nhac-dang-chon <?php echo $tep ? '' : 'hidden'; ?>>
			<strong data-nntm-nhac-ten><?php echo $tep ? esc_html( nntm_nhac_ten( $tep ) ) : ''; ?></strong>
			<audio controls preload="metadata" src="<?php echo $tep ? esc_url( (string) wp_get_attachment_url( $tep->ID ) ) : ''; ?>" data-nntm-nhac-nghe-thu></audio>
		</div>

		<p class="description" data-nntm-nhac-trong <?php echo $tep ? 'hidden' : ''; ?>>
			<?php esc_html_e( 'Chưa chọn nhạc nền.', 'nntm' ); ?>
		</p>

		<p>
			<button type="button" class="button button-secondary" data-nntm-nhac-chon>
				<?php echo $tep ? esc_html__( 'Đổi nhạc nền', 'nntm' ) : esc_html__( 'Chọn nhạc nền', 'nntm' ); ?>
			</button>
			<button type="button" class="button-link-delete" data-nntm-nhac-go <?php echo $tep ? '' : 'hidden'; ?>>
				<?php esc_html_e( 'Gỡ', 'nntm' ); ?>
			</button>
		</p>

		<p class="description"><?php esc_html_e( 'Bài có nhạc thì trang chi tiết hiện thanh nhạc dưới tiêu đề và tự phát. Bỏ trống là không có gì.', 'nntm' ); ?></p>
	</div>
	<?php
}

function nntm_nhac_luu_o_nhap( int $post_id, WP_Post $post ): void {
	if ( wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) ) {
		return;
	}

	if ( ! nntm_nhac_ho_tro( (string) $post->post_type ) || ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	$nonce = isset( $_POST[ NNTM_NHAC_NONCE ] ) ? sanitize_text_field( wp_unslash( $_POST[ NNTM_NHAC_NONCE ] ) ) : '';

	/*
	 * Không có nonce nghĩa là lần lưu này không đi qua ô nhập cũ (ví dụ lưu từ
	 * trình soạn thảo khối, hoặc sửa nhanh ngoài danh sách). Im lặng bỏ qua,
	 * KHÔNG xoá meta — xoá thì mỗi lần sửa nhanh là bay mất bản nhạc.
	 */
	if ( '' === $nonce || ! wp_verify_nonce( $nonce, NNTM_NHAC_HANH_DONG ) ) {
		return;
	}

	$id = isset( $_POST['nntm_nhac_nen_id'] ) ? absint( wp_unslash( $_POST['nntm_nhac_nen_id'] ) ) : 0;

	if ( nntm_nhac_tep( $id ) ) {
		update_post_meta( $post_id, NNTM_NHAC_META, $id );
		return;
	}

	delete_post_meta( $post_id, NNTM_NHAC_META );
}
add_action( 'save_post', 'nntm_nhac_luu_o_nhap', 10, 2 );

/**
 * Cột "Nhạc nền" ngoài danh sách bài, để soát nhanh bài nào đã có.
 *
 * WordPress dùng hai bộ móc khác nhau cho loại phẳng (posts) và loại phân cấp
 * (pages), nên phải gắn cả hai.
 */
function nntm_nhac_them_cot( array $cot ): array {
	$loai = nntm_nhac_loai_man_hinh();

	if ( '' === $loai || ! nntm_nhac_ho_tro( $loai ) ) {
		return $cot;
	}

	$cot['nntm_nhac'] = __( 'Nhạc nền', 'nntm' );

	return $cot;
}
add_filter( 'manage_posts_columns', 'nntm_nhac_them_cot' );
add_filter( 'manage_pages_columns', 'nntm_nhac_them_cot' );

function nntm_nhac_ve_cot( string $cot, int $post_id ): void {
	if ( 'nntm_nhac' !== $cot ) {
		return;
	}

	$id  = nntm_nhac_id( $post_id );
	$tep = $id ? nntm_nhac_tep( $id ) : null;

	echo $tep ? esc_html( nntm_nhac_ten( $tep ) ) : '<span aria-hidden="true">—</span>';
}
add_action( 'manage_posts_custom_column', 'nntm_nhac_ve_cot', 10, 2 );
add_action( 'manage_pages_custom_column', 'nntm_nhac_ve_cot', 10, 2 );

/*
 * ---------------------------------------------------------------------------
 * Ngoài trang: thanh nhạc dưới tiêu đề
 * ---------------------------------------------------------------------------
 */

/**
 * Thanh nhạc của một bài. Chuỗi rỗng nếu bài không có nhạc.
 */
function nntm_render_nhac_nen( int $post_id ): string {
	$id  = nntm_nhac_id( $post_id );
	$tep = $id ? nntm_nhac_tep( $id ) : null;

	if ( ! $tep ) {
		return '';
	}

	$url = (string) wp_get_attachment_url( $tep->ID );

	if ( '' === $url ) {
		return '';
	}

	$ten = nntm_nhac_ten( $tep );

	ob_start();
	?>
	<div class="nntm-nhac" data-nntm-nhac>
		<?php
		/*
		 * preload="none": nhạc nền không được giành băng thông với chữ và ảnh
		 * của bài. Trình duyệt tự tải khi JS gọi play().
		 */
		?>
		<audio class="nntm-nhac__nguon" src="<?php echo esc_url( $url ); ?>" preload="none" data-nntm-nhac-tep></audio>

		<button
			type="button"
			class="nntm-nhac__nut"
			aria-pressed="false"
			data-nntm-nhac-nut
			data-nhan-phat="<?php echo esc_attr( sprintf( __( 'Phát nhạc nền: %s', 'nntm' ), $ten ) ); ?>"
			data-nhan-dung="<?php echo esc_attr( sprintf( __( 'Dừng nhạc nền: %s', 'nntm' ), $ten ) ); ?>"
			aria-label="<?php echo esc_attr( sprintf( __( 'Phát nhạc nền: %s', 'nntm' ), $ten ) ); ?>"
		>
			<span class="nntm-nhac__bieu-tuong" aria-hidden="true">
				<svg class="nntm-nhac__hinh nntm-nhac__hinh--phat" viewBox="0 0 24 24" width="14" height="14" focusable="false"><path d="M8 5.5v13l11-6.5-11-6.5z" fill="currentColor"/></svg>
				<svg class="nntm-nhac__hinh nntm-nhac__hinh--dung" viewBox="0 0 24 24" width="14" height="14" focusable="false"><path d="M8 5h3v14H8V5zm5 0h3v14h-3V5z" fill="currentColor"/></svg>
			</span>
		</button>

		<?php /* Sóng nhạc chỉ là trang trí — trình đọc màn hình bỏ qua. */ ?>
		<span class="nntm-nhac__song" aria-hidden="true">
			<i></i><i></i><i></i><i></i><i></i>
		</span>

		<span class="nntm-nhac__ten"><?php echo esc_html( $ten ); ?></span>

		<span class="nntm-sr-only" aria-live="polite" data-nntm-nhac-tinh-trang></span>
	</div>
	<?php

	return trim( (string) ob_get_clean() );
}

function nntm_nhac_tai_tep_ngoai_trang(): void {
	if ( ! is_singular( nntm_nhac_cac_loai() ) ) {
		return;
	}

	// Bài không có nhạc thì không tải gì cả.
	if ( ! nntm_nhac_id( (int) get_queried_object_id() ) ) {
		return;
	}

	$css = NNTM_THEME_DIR . '/assets/css/nhac-bai-viet.css';
	$js  = NNTM_THEME_DIR . '/assets/js/nhac-bai-viet.js';

	wp_enqueue_style( 'nntm-nhac', NNTM_THEME_URI . '/assets/css/nhac-bai-viet.css', array( 'nntm-tokens' ), nntm_asset_version( $css ) );
	wp_enqueue_script( 'nntm-nhac', NNTM_THEME_URI . '/assets/js/nhac-bai-viet.js', array(), nntm_asset_version( $js ), true );
}
add_action( 'wp_enqueue_scripts', 'nntm_nhac_tai_tep_ngoai_trang', 42 );
