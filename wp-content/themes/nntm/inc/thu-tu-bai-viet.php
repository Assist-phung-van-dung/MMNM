<?php
/**
 * Sắp xếp thứ tự bài viết trong từng danh mục.
 *
 * Mặc định trang danh mục xếp mới nhất trước, quản trị không đổi được. Tệp này
 * cho phép kéo thả để chốt thứ tự riêng cho TỪNG danh mục.
 *
 * Vì sao lưu theo term chứ không dùng menu_order: menu_order là MỘT con số gắn
 * vào bài, trong khi một bài nằm được ở nhiều danh mục. Xếp bài A thứ ba ở mục
 * X thì nó cũng thành thứ ba ở mục Y — không tách riêng được. Lưu danh sách ID
 * vào term meta thì mỗi danh mục có thứ tự độc lập.
 *
 * Bài chưa được xếp KHÔNG biến mất: chúng nối vào sau danh sách đã xếp, vẫn
 * theo ngày mới nhất trước. Nhờ vậy đăng bài mới không phải vào đây xếp lại.
 */

defined( 'ABSPATH' ) || exit;

/** Khoá term meta giữ danh sách ID đã xếp. */
const NNTM_THU_TU_META = '_nntm_thu_tu_bai';

/** Slug trang quản trị. */
const NNTM_THU_TU_TRANG = 'nntm-thu-tu-bai-viet';

/**
 * Các taxonomy được sắp thứ tự, kèm loại nội dung tương ứng.
 *
 * @return array<string,string> taxonomy => post_type
 */
function nntm_thu_tu_cac_taxonomy(): array {
	$ds = array(
		'nntm_section' => 'nntm_article',
		'category'     => 'post',
	);

	/**
	 * Thêm/bớt taxonomy được sắp thứ tự.
	 *
	 * @param array<string,string> $ds taxonomy => post_type.
	 */
	$ds = (array) apply_filters( 'nntm_thu_tu_cac_taxonomy', $ds );

	$ra = array();

	foreach ( $ds as $taxonomy => $post_type ) {
		$taxonomy  = sanitize_key( (string) $taxonomy );
		$post_type = sanitize_key( (string) $post_type );

		if ( '' === $taxonomy || '' === $post_type ) {
			continue;
		}

		if ( ! taxonomy_exists( $taxonomy ) || ! post_type_exists( $post_type ) ) {
			continue;
		}

		$ra[ $taxonomy ] = $post_type;
	}

	return $ra;
}

/** Taxonomy này có sắp thứ tự được không? */
function nntm_thu_tu_ho_tro( string $taxonomy ): bool {
	return array_key_exists( $taxonomy, nntm_thu_tu_cac_taxonomy() );
}

/**
 * Danh sách ID đã xếp của một danh mục.
 *
 * Chỉ lọc dạng dữ liệu, KHÔNG kiểm bài còn tồn tại hay không — việc đó để lúc
 * dựng truy vấn lo, vì ở đây chưa biết đang cần bài công khai hay mọi trạng
 * thái.
 *
 * @return int[]
 */
function nntm_thu_tu_lay( int $term_id ): array {
	$term_id = absint( $term_id );

	if ( $term_id < 1 ) {
		return array();
	}

	$tho = get_term_meta( $term_id, NNTM_THU_TU_META, true );

	if ( ! is_array( $tho ) ) {
		return array();
	}

	$ra = array();

	foreach ( $tho as $id ) {
		$id = absint( $id );

		if ( $id > 0 && ! in_array( $id, $ra, true ) ) {
			$ra[] = $id;
		}
	}

	return $ra;
}

/**
 * Lưu thứ tự cho một danh mục.
 *
 * Danh sách rỗng thì XOÁ hẳn meta thay vì lưu mảng rỗng: như vậy trạng thái
 * "chưa xếp gì" chỉ có một cách biểu diễn duy nhất, và trang danh mục quay về
 * đúng hành vi mặc định.
 *
 * @param int   $term_id Danh mục.
 * @param int[] $ids     Thứ tự mới.
 */
function nntm_thu_tu_luu( int $term_id, array $ids ): void {
	$term_id = absint( $term_id );

	if ( $term_id < 1 ) {
		return;
	}

	$sach = array();

	foreach ( $ids as $id ) {
		$id = absint( $id );

		if ( $id > 0 && ! in_array( $id, $sach, true ) ) {
			$sach[] = $id;
		}
	}

	if ( empty( $sach ) ) {
		delete_term_meta( $term_id, NNTM_THU_TU_META );
		return;
	}

	update_term_meta( $term_id, NNTM_THU_TU_META, $sach );
}

/*
 * ---------------------------------------------------------------------------
 * Áp thứ tự ra ngoài web
 * ---------------------------------------------------------------------------
 */

/**
 * Đổi mệnh đề ORDER BY khi truy vấn có mang theo thứ tự đã xếp.
 *
 * Dùng FIELD() thay vì nạp toàn bộ ID vào post__in: post__in với vài trăm ID
 * sinh một mệnh đề IN khổng lồ và phải chạy thêm một truy vấn lấy hết ID
 * trước đó. FIELD() chỉ cần đúng danh sách đã xếp, phân trang vẫn chạy y như
 * thường.
 *
 * Mẹo đảo ngược: FIELD() trả 0 cho bài không có trong danh sách, và trả vị trí
 * 1,2,3… cho bài có. Muốn bài đầu danh sách đứng trước thì phải cho nó số lớn
 * nhất, nên danh sách được lật ngược rồi mới sắp giảm dần. Bài không nằm trong
 * danh sách nhận 0, tự rơi xuống cuối và xếp tiếp theo ngày.
 */
function nntm_thu_tu_orderby( $orderby, $query ) {
	if ( ! $query instanceof WP_Query ) {
		return $orderby;
	}

	$ids = $query->get( 'nntm_thu_tu' );

	if ( empty( $ids ) || ! is_array( $ids ) ) {
		return $orderby;
	}

	$ids = array_values( array_filter( array_map( 'absint', $ids ) ) );

	if ( empty( $ids ) ) {
		return $orderby;
	}

	global $wpdb;

	// Đã qua absint nên chỉ còn số nguyên, ghép thẳng vào SQL là an toàn.
	$danh_sach = implode( ',', array_reverse( $ids ) );

	return "FIELD( {$wpdb->posts}.ID, {$danh_sach} ) DESC, {$wpdb->posts}.post_date DESC";
}
add_filter( 'posts_orderby', 'nntm_thu_tu_orderby', 10, 2 );

/**
 * Gắn thứ tự đã xếp vào truy vấn chính của trang danh mục.
 *
 * Chỉ đụng truy vấn chính ngoài web. Trang quản trị giữ nguyên thứ tự mặc
 * định, nếu không bảng danh sách bài cũng bị xáo theo và quản trị không tìm
 * được bài vừa sửa.
 */
function nntm_thu_tu_ap_vao_truy_van( $query ): void {
	if ( ! $query instanceof WP_Query || is_admin() || ! $query->is_main_query() ) {
		return;
	}

	$term = null;

	foreach ( array_keys( nntm_thu_tu_cac_taxonomy() ) as $taxonomy ) {
		if ( 'category' === $taxonomy ) {
			if ( $query->is_category() ) {
				$term = $query->get_queried_object();
				break;
			}
			continue;
		}

		if ( $query->is_tax( $taxonomy ) ) {
			$term = $query->get_queried_object();
			break;
		}
	}

	if ( ! $term instanceof WP_Term ) {
		return;
	}

	$ids = nntm_thu_tu_lay( (int) $term->term_id );

	if ( ! empty( $ids ) ) {
		$query->set( 'nntm_thu_tu', $ids );
	}
}
add_action( 'pre_get_posts', 'nntm_thu_tu_ap_vao_truy_van' );

/*
 * ---------------------------------------------------------------------------
 * Trang quản trị
 * ---------------------------------------------------------------------------
 */

/**
 * Bài của một danh mục, xếp theo đúng thứ tự đang lưu.
 *
 * Bài đã xếp lên trước theo thứ tự đã chốt; bài chưa xếp nối vào sau theo ngày
 * mới nhất trước — giống hệt cách trang danh mục ngoài web hiển thị, để quản
 * trị kéo thả đúng cái mình đang nhìn thấy.
 *
 * @return WP_Post[]
 */
function nntm_thu_tu_danh_sach( string $taxonomy, int $term_id ): array {
	$cac_loai = nntm_thu_tu_cac_taxonomy();

	if ( ! isset( $cac_loai[ $taxonomy ] ) || $term_id < 1 ) {
		return array();
	}

	$bai = get_posts(
		array(
			'post_type'        => $cac_loai[ $taxonomy ],
			'post_status'      => 'publish',
			'numberposts'      => -1,
			'orderby'          => 'date',
			'order'            => 'DESC',
			'suppress_filters' => false,
			'tax_query'        => array(  // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
				array(
					'taxonomy' => $taxonomy,
					'field'    => 'term_id',
					'terms'    => array( $term_id ),
				),
			),
		)
	);

	if ( empty( $bai ) ) {
		return array();
	}

	$thu_tu = nntm_thu_tu_lay( $term_id );

	if ( empty( $thu_tu ) ) {
		return $bai;
	}

	$theo_id = array();

	foreach ( $bai as $b ) {
		$theo_id[ (int) $b->ID ] = $b;
	}

	$ra = array();

	// Bài đã xếp, bỏ qua bài đã xoá hoặc đã rút khỏi danh mục này.
	foreach ( $thu_tu as $id ) {
		if ( isset( $theo_id[ $id ] ) ) {
			$ra[] = $theo_id[ $id ];
			unset( $theo_id[ $id ] );
		}
	}

	// Bài chưa xếp, giữ nguyên thứ tự ngày của truy vấn ban đầu.
	foreach ( $theo_id as $b ) {
		$ra[] = $b;
	}

	return $ra;
}

/**
 * Mã màn hình do add_submenu_page trả về.
 *
 * Giữ lại thay vì tự ghép chuỗi: cách WordPress đặt tên mã màn hình cho submenu
 * của một loại nội dung không có gì bảo đảm sẽ giữ nguyên, ghép tay là nạp
 * nhầm tài nguyên mà không báo lỗi.
 */
function nntm_thu_tu_ma_man_hinh( ?string $dat = null ): string {
	static $ma = '';

	if ( null !== $dat ) {
		$ma = $dat;
	}

	return $ma;
}

function nntm_thu_tu_them_menu(): void {
	$ma = add_submenu_page(
		'edit.php?post_type=nntm_article',
		__( 'Sắp xếp bài viết theo danh mục', 'nntm' ),
		__( 'Sắp xếp bài viết', 'nntm' ),
		'edit_others_posts',
		NNTM_THU_TU_TRANG,
		'nntm_thu_tu_ve_trang'
	);

	if ( ! is_string( $ma ) || '' === $ma ) {
		return;
	}

	nntm_thu_tu_ma_man_hinh( $ma );

	/*
	 * Nhận POST ở load-<màn hình>, TRƯỚC khi trang quản trị in ra bất cứ thứ
	 * gì. Đặt trong hàm vẽ trang thì đầu trang đã gửi đi rồi, wp_safe_redirect
	 * không còn tác dụng và người dùng bấm F5 là lưu lại lần nữa.
	 */
	add_action( 'load-' . $ma, 'nntm_thu_tu_xu_ly_luu' );
}
add_action( 'admin_menu', 'nntm_thu_tu_them_menu' );

/** Taxonomy và term đang chọn trên URL. */
function nntm_thu_tu_lua_chon_hien_tai(): array {
	$cac_loai = nntm_thu_tu_cac_taxonomy();

	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- chỉ đọc để dựng màn hình.
	$taxonomy = isset( $_GET['taxonomy'] ) ? sanitize_key( wp_unslash( $_GET['taxonomy'] ) ) : '';

	if ( ! isset( $cac_loai[ $taxonomy ] ) ) {
		$taxonomy = (string) array_key_first( $cac_loai );
	}

	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$term_id = isset( $_GET['term_id'] ) ? absint( wp_unslash( $_GET['term_id'] ) ) : 0;

	/*
	 * Danh mục phải thuộc đúng loại đang chọn. Đổi loại rồi bấm "Xem danh sách"
	 * thì ô danh mục vẫn đang giữ term của loại cũ và gửi kèm theo; không kiểm
	 * thì màn hình dựng ra một danh sách rỗng khó hiểu.
	 */
	if ( $term_id > 0 ) {
		$term = get_term( $term_id );

		if ( ! $term instanceof WP_Term || $term->taxonomy !== $taxonomy ) {
			$term_id = 0;
		}
	}

	return array( $taxonomy, $term_id );
}

/** Nhận và lưu thứ tự mới. */
function nntm_thu_tu_xu_ly_luu(): void {
	if ( ! isset( $_POST['nntm_thu_tu_nonce'] ) ) {
		return;
	}

	if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nntm_thu_tu_nonce'] ) ), 'nntm_thu_tu_luu' ) ) {
		wp_die( esc_html__( 'Phiên làm việc đã hết hạn. Tải lại trang rồi thử lại.', 'nntm' ) );
	}

	if ( ! current_user_can( 'edit_others_posts' ) ) {
		wp_die( esc_html__( 'Bạn không có quyền sắp xếp bài viết.', 'nntm' ) );
	}

	$taxonomy = isset( $_POST['taxonomy'] ) ? sanitize_key( wp_unslash( $_POST['taxonomy'] ) ) : '';
	$term_id  = isset( $_POST['term_id'] ) ? absint( wp_unslash( $_POST['term_id'] ) ) : 0;

	if ( ! nntm_thu_tu_ho_tro( $taxonomy ) || $term_id < 1 ) {
		return;
	}

	$tho = isset( $_POST['thu_tu'] ) ? sanitize_text_field( wp_unslash( $_POST['thu_tu'] ) ) : '';
	$ids = array_filter( array_map( 'absint', explode( ',', $tho ) ) );

	nntm_thu_tu_luu( $term_id, $ids );

	wp_safe_redirect(
		add_query_arg(
			array(
				'post_type' => 'nntm_article',
				'page'      => NNTM_THU_TU_TRANG,
				'taxonomy'  => $taxonomy,
				'term_id'   => $term_id,
				'da-luu'    => '1',
			),
			admin_url( 'edit.php' )
		)
	);
	exit;
}

function nntm_thu_tu_ve_trang(): void {
	if ( ! current_user_can( 'edit_others_posts' ) ) {
		wp_die( esc_html__( 'Bạn không có quyền xem trang này.', 'nntm' ) );
	}

	$cac_loai = nntm_thu_tu_cac_taxonomy();

	if ( empty( $cac_loai ) ) {
		echo '<div class="wrap"><h1>' . esc_html__( 'Sắp xếp bài viết theo danh mục', 'nntm' ) . '</h1>';
		echo '<p>' . esc_html__( 'Chưa có loại danh mục nào được bật để sắp xếp.', 'nntm' ) . '</p></div>';
		return;
	}

	list( $taxonomy, $term_id ) = nntm_thu_tu_lua_chon_hien_tai();
	$doi_tuong_tax              = get_taxonomy( $taxonomy );
	?>
	<div class="wrap nntm-thu-tu">
		<h1><?php esc_html_e( 'Sắp xếp bài viết theo danh mục', 'nntm' ); ?></h1>

		<?php // phpcs:ignore WordPress.Security.NonceVerification.Recommended ?>
		<?php if ( isset( $_GET['da-luu'] ) ) : ?>
			<div class="notice notice-success is-dismissible">
				<p><?php esc_html_e( 'Đã lưu thứ tự. Mở trang danh mục ngoài web để xem kết quả.', 'nntm' ); ?></p>
			</div>
		<?php endif; ?>

		<form method="get" action="<?php echo esc_url( admin_url( 'edit.php' ) ); ?>" class="nntm-thu-tu__chon">
			<input type="hidden" name="post_type" value="nntm_article">
			<input type="hidden" name="page" value="<?php echo esc_attr( NNTM_THU_TU_TRANG ); ?>">

			<label for="nntm-thu-tu-taxonomy"><?php esc_html_e( 'Loại danh mục', 'nntm' ); ?></label>
			<select name="taxonomy" id="nntm-thu-tu-taxonomy">
				<?php foreach ( $cac_loai as $slug => $post_type ) : ?>
					<?php $t = get_taxonomy( $slug ); ?>
					<option value="<?php echo esc_attr( $slug ); ?>" <?php selected( $slug, $taxonomy ); ?>>
						<?php echo esc_html( $t instanceof WP_Taxonomy ? $t->labels->name : $slug ); ?>
					</option>
				<?php endforeach; ?>
			</select>

			<label for="nntm-thu-tu-term"><?php esc_html_e( 'Danh mục', 'nntm' ); ?></label>
			<?php
			wp_dropdown_categories(
				array(
					'taxonomy'          => $taxonomy,
					'name'              => 'term_id',
					'id'                => 'nntm-thu-tu-term',
					'selected'          => $term_id,
					'show_option_none'  => __( '— Chọn danh mục —', 'nntm' ),
					'option_none_value' => 0,
					'hide_empty'        => false,
					'hierarchical'      => $doi_tuong_tax instanceof WP_Taxonomy ? (bool) $doi_tuong_tax->hierarchical : false,
					'orderby'           => 'name',
				)
			);
			?>

			<?php submit_button( __( 'Xem danh sách', 'nntm' ), 'secondary', '', false ); ?>
		</form>

		<hr>

		<?php
		if ( $term_id < 1 ) {
			echo '<p>' . esc_html__( 'Chọn một danh mục ở trên để bắt đầu sắp xếp.', 'nntm' ) . '</p>';
			echo '</div>';
			return;
		}

		$bai = nntm_thu_tu_danh_sach( $taxonomy, $term_id );

		if ( empty( $bai ) ) {
			echo '<p>' . esc_html__( 'Danh mục này chưa có bài nào đã đăng.', 'nntm' ) . '</p>';
			echo '</div>';
			return;
		}
		?>

		<form method="post">
			<?php wp_nonce_field( 'nntm_thu_tu_luu', 'nntm_thu_tu_nonce' ); ?>
			<input type="hidden" name="taxonomy" value="<?php echo esc_attr( $taxonomy ); ?>">
			<input type="hidden" name="term_id" value="<?php echo esc_attr( (string) $term_id ); ?>">
			<input type="hidden" name="thu_tu" id="nntm-thu-tu-gia-tri" value="">

			<p class="description">
				<?php esc_html_e( 'Kéo thả để đổi thứ tự. Thứ tự trên xuống ở đây chính là thứ tự hiện ngoài web.', 'nntm' ); ?>
			</p>

			<ol class="nntm-thu-tu__ds" id="nntm-thu-tu-ds">
				<?php foreach ( $bai as $b ) : ?>
					<li class="nntm-thu-tu__muc" data-id="<?php echo esc_attr( (string) $b->ID ); ?>">
						<span class="nntm-thu-tu__tay" aria-hidden="true"></span>
						<span class="nntm-thu-tu__ten"><?php echo esc_html( get_the_title( $b ) ); ?></span>
						<a class="nntm-thu-tu__sua" href="<?php echo esc_url( (string) get_edit_post_link( $b->ID ) ); ?>">
							<?php esc_html_e( 'Sửa', 'nntm' ); ?>
						</a>
					</li>
				<?php endforeach; ?>
			</ol>

			<?php submit_button( __( 'Lưu thứ tự', 'nntm' ) ); ?>
		</form>
	</div>
	<?php
}

function nntm_thu_tu_tai_tai_nguyen( string $hook ): void {
	if ( '' === nntm_thu_tu_ma_man_hinh() || $hook !== nntm_thu_tu_ma_man_hinh() ) {
		return;
	}

	$js  = NNTM_THEME_DIR . '/assets/js/admin/thu-tu-bai-viet.js';
	$css = NNTM_THEME_DIR . '/assets/css/admin/thu-tu-bai-viet.css';

	wp_enqueue_script(
		'nntm-thu-tu-bai-viet',
		NNTM_THEME_URI . '/assets/js/admin/thu-tu-bai-viet.js',
		array( 'jquery', 'jquery-ui-sortable' ),
		nntm_asset_version( $js ),
		true
	);

	wp_enqueue_style(
		'nntm-thu-tu-bai-viet',
		NNTM_THEME_URI . '/assets/css/admin/thu-tu-bai-viet.css',
		array(),
		nntm_asset_version( $css )
	);
}
add_action( 'admin_enqueue_scripts', 'nntm_thu_tu_tai_tai_nguyen' );
