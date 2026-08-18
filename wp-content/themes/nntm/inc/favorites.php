<?php
/**
 * Yêu thích dùng chung cho các nội dung của theme.
 *
 * Dữ liệu được lưu vào bảng `${prefix}nntm_favorites` do nntm-core tạo.
 * Theme chỉ cung cấp lớp giao diện + endpoint toggle + trang /yeu-thich/.
 *
 * @package NNTM
 */

defined( 'ABSPATH' ) || exit;

/**
 * Tên bảng favorites theo prefix WordPress hiện tại.
 */
function nntm_section_favorites_table_name(): string {
	global $wpdb;
	return $wpdb->prefix . 'nntm_favorites';
}

/**
 * Bảng favorites có tồn tại không.
 */
function nntm_section_favorites_table_exists(): bool {
	global $wpdb;

	static $exists = null;
	if ( null !== $exists ) {
		return $exists;
	}

	$table = nntm_section_favorites_table_name();
	$like  = $wpdb->esc_like( $table );
	$found = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $like ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- kiểm tra schema có sẵn.

	$exists = ( $found === $table );
	return $exists;
}

/**
 * Các post type được phép hiện trong trang Yêu thích.
 *
 * @return string[]
 */
function nntm_section_favorite_post_types(): array {
	$types = array( 'nntm_article', 'nntm_publication', 'nntm_talk', 'nntm_retreat', 'nntm_video', 'post' );
	return array_values( array_filter( array_map( 'sanitize_key', (array) apply_filters( 'nntm_section_favorite_post_types', $types ) ) ) );
}

/**
 * Kiểm tra một user đã yêu thích object chưa.
 */
function nntm_section_is_favorite( int $object_id, int $user_id = 0 ): bool {
	global $wpdb;

	$object_id = absint( $object_id );
	$user_id   = $user_id > 0 ? absint( $user_id ) : get_current_user_id();

	if ( $object_id <= 0 || $user_id <= 0 || ! nntm_section_favorites_table_exists() ) {
		return false;
	}

	$table = nntm_section_favorites_table_name();
	$found = $wpdb->get_var(
		$wpdb->prepare(
			"SELECT id FROM {$table} WHERE user_id = %d AND object_id = %d LIMIT 1", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- tên bảng từ $wpdb->prefix.
			$user_id,
			$object_id
		)
	); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- cần trạng thái hiện tại của user.

	return null !== $found;
}

/**
 * Dựng nút Yêu thích dùng chung.
 *
 * @param int    $object_id ID bài viết.
 * @param string $class     Class bổ sung cho vị trí cụ thể.
 * @return string
 */
function nntm_section_render_favorite_button( int $object_id, string $class = '' ): string {
	$post = get_post( $object_id );
	if ( ! $post instanceof WP_Post ) {
		return '';
	}

	$is_logged_in = is_user_logged_in();
	$is_favorite  = $is_logged_in && nntm_section_is_favorite( $object_id );
	$classes      = array( 'nntm-favorite-button' );

	if ( '' !== trim( $class ) ) {
		$classes[] = sanitize_html_class( $class );
	}
	if ( $is_favorite ) {
		$classes[] = 'is-active';
	}

	ob_start();
	?>
	<button
		type="button"
		class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>"
		data-nntm-favorite="<?php echo esc_attr( (string) $object_id ); ?>"
		aria-pressed="<?php echo esc_attr( $is_favorite ? 'true' : 'false' ); ?>"
		<?php echo $is_logged_in ? '' : 'data-nntm-auth-modal="dang-nhap"'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- chuỗi thuộc tính cố định. ?>
	>
		<svg class="nntm-favorite-button__icon" viewBox="0 0 24 24" width="22" height="22" aria-hidden="true" focusable="false">
			<path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78L12 21.23l8.84-8.84a5.5 5.5 0 0 0 0-7.78Z" />
		</svg>
		<span class="nntm-favorite-button__label"><?php esc_html_e( 'Yêu thích', 'nntm' ); ?></span>
		<span class="nntm-sr-only nntm-favorite-button__state">
			<?php echo esc_html( $is_favorite ? __( 'Đã yêu thích', 'nntm' ) : __( 'Chưa yêu thích', 'nntm' ) ); ?>
		</span>
	</button>
	<?php
	return trim( (string) ob_get_clean() );
}

/**
 * Endpoint AJAX lưu/bỏ yêu thích.
 */
function nntm_section_ajax_toggle_favorite(): void {
	check_ajax_referer( 'nntm_favorite_toggle', 'nonce' );

	if ( ! is_user_logged_in() ) {
		wp_send_json_error( array( 'message' => __( 'Vui lòng đăng nhập để sử dụng Yêu thích.', 'nntm' ) ), 401 );
	}

	$object_id = isset( $_POST['object_id'] ) ? absint( wp_unslash( $_POST['object_id'] ) ) : 0;
	$post      = $object_id > 0 ? get_post( $object_id ) : null;

	if ( ! $post instanceof WP_Post || 'publish' !== $post->post_status ) {
		wp_send_json_error( array( 'message' => __( 'Nội dung không hợp lệ.', 'nntm' ) ), 400 );
	}

	if ( ! nntm_section_favorites_table_exists() ) {
		wp_send_json_error( array( 'message' => __( 'Bảng dữ liệu Yêu thích chưa được cài đặt.', 'nntm' ) ), 503 );
	}

	global $wpdb;
	$table       = nntm_section_favorites_table_name();
	$user_id     = get_current_user_id();
	$is_favorite = nntm_section_is_favorite( $object_id, $user_id );

	if ( $is_favorite ) {
		$result = $wpdb->delete( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- bảng nghiệp vụ riêng của dự án.
			$table,
			array(
				'user_id'   => $user_id,
				'object_id' => $object_id,
			),
			array( '%d', '%d' )
		);
		$favorited = false;
	} else {
		$result = $wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- bảng nghiệp vụ riêng của dự án.
			$table,
			array(
				'user_id'     => $user_id,
				'object_id'   => $object_id,
				'object_type' => sanitize_key( $post->post_type ),
				'created_at'  => current_time( 'mysql' ),
			),
			array( '%d', '%d', '%s', '%s' )
		);
		$favorited = true;
	}

	if ( false === $result ) {
		wp_send_json_error( array( 'message' => __( 'Không thể cập nhật Yêu thích lúc này.', 'nntm' ) ), 500 );
	}

	wp_send_json_success(
		array(
			'favorited' => $favorited,
			'message'   => $favorited ? __( 'Đã thêm vào Yêu thích.', 'nntm' ) : __( 'Đã bỏ khỏi Yêu thích.', 'nntm' ),
		)
	);
}
add_action( 'wp_ajax_nntm_section_toggle_favorite', 'nntm_section_ajax_toggle_favorite' );

/**
 * Nhận diện URL /yeu-thich/ kể cả khi site chưa tạo Page vật lý.
 */
function nntm_section_is_favorites_request(): bool {
	if ( is_page( 'yeu-thich' ) ) {
		return true;
	}

	global $wp;
	$request = isset( $wp->request ) ? trim( (string) $wp->request, '/' ) : '';
	return (bool) preg_match( '#^yeu-thich(?:/page/[0-9]+)?$#', $request );
}

/**
 * Trang hiện tại có thể chứa nút favorite của layout phân mục không.
 */
function nntm_section_should_enqueue_favorite_assets(): bool {
	if ( is_category() || is_tax( 'nntm_section' ) || is_tax( 'nntm_topic', array( 'khoa-tu', 'lich-tu' ) ) || is_singular( array( 'post', 'nntm_article', 'nntm_publication', 'nntm_retreat' ) ) || nntm_section_is_favorites_request() ) {
		return true;
	}

	$post = get_queried_object();
	return $post instanceof WP_Post && has_block( 'nntm/article-rows', $post );
}

/**
 * CSS/JS nút favorite.
 */
function nntm_section_enqueue_favorite_assets(): void {
	if ( ! nntm_section_should_enqueue_favorite_assets() ) {
		return;
	}

	$css_path = NNTM_THEME_DIR . '/assets/css/favorites.css';
	wp_enqueue_style(
		'nntm-favorites',
		NNTM_THEME_URI . '/assets/css/favorites.css',
		array( 'nntm-tokens', 'nntm-base' ),
		nntm_asset_version( $css_path )
	);

	$js_path = NNTM_THEME_DIR . '/assets/js/favorites.js';
	wp_enqueue_script(
		'nntm-favorites',
		NNTM_THEME_URI . '/assets/js/favorites.js',
		array(),
		nntm_asset_version( $js_path ),
		true
	);

	wp_localize_script(
		'nntm-favorites',
		'nntmFavorites',
		array(
			'ajaxUrl'      => admin_url( 'admin-ajax.php' ),
			'nonce'        => wp_create_nonce( 'nntm_favorite_toggle' ),
			'isLoggedIn'   => is_user_logged_in(),
			'activeText'   => __( 'Đã yêu thích', 'nntm' ),
			'inactiveText' => __( 'Chưa yêu thích', 'nntm' ),
			'errorText'    => __( 'Không thể cập nhật Yêu thích. Vui lòng thử lại.', 'nntm' ),
		)
	);
}
add_action( 'wp_enqueue_scripts', 'nntm_section_enqueue_favorite_assets', 30 );

/**
 * Lấy một trang favorite đã lọc các bài publish và post type được hỗ trợ.
 *
 * @return array{posts: WP_Post[], total: int, total_pages: int, current_page: int}
 */
function nntm_section_get_favorites_page( int $user_id, int $page = 1, int $per_page = 5 ): array {
	global $wpdb;

	$user_id  = absint( $user_id );
	$page     = max( 1, absint( $page ) );
	$per_page = max( 1, min( 50, absint( $per_page ) ) );
	$empty    = array(
		'posts'        => array(),
		'total'        => 0,
		'total_pages'  => 0,
		'current_page' => $page,
	);

	if ( $user_id <= 0 || ! nntm_section_favorites_table_exists() ) {
		return $empty;
	}

	$post_types = nntm_section_favorite_post_types();
	if ( empty( $post_types ) ) {
		return $empty;
	}

	$table        = nntm_section_favorites_table_name();
	$placeholders = implode( ', ', array_fill( 0, count( $post_types ), '%s' ) );
	$where_sql    = "f.user_id = %d AND p.post_status = 'publish' AND p.post_type IN ({$placeholders})"; // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- placeholders được tạo nội bộ.
	$where_args   = array_merge( array( $user_id ), $post_types );

	$count_sql = "SELECT COUNT(*) FROM {$table} f INNER JOIN {$wpdb->posts} p ON p.ID = f.object_id WHERE {$where_sql}"; // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- tên bảng từ WordPress.
	$total     = (int) $wpdb->get_var( $wpdb->prepare( $count_sql, $where_args ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

	if ( $total <= 0 ) {
		return $empty;
	}

	$total_pages = max( 1, (int) ceil( $total / $per_page ) );
	$page        = min( $page, $total_pages );
	$offset      = ( $page - 1 ) * $per_page;

	$ids_sql  = "SELECT f.object_id FROM {$table} f INNER JOIN {$wpdb->posts} p ON p.ID = f.object_id WHERE {$where_sql} ORDER BY f.created_at DESC, f.id DESC LIMIT %d OFFSET %d"; // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- tên bảng từ WordPress.
	$ids_args = array_merge( $where_args, array( $per_page, $offset ) );
	$ids      = array_map( 'absint', (array) $wpdb->get_col( $wpdb->prepare( $ids_sql, $ids_args ) ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

	$posts = array();
	if ( ! empty( $ids ) ) {
		$posts = get_posts(
			array(
				'post_type'      => $post_types,
				'post_status'    => 'publish',
				'post__in'       => $ids,
				'orderby'        => 'post__in',
				'posts_per_page' => count( $ids ),
				'no_found_rows'  => true,
			)
		);
	}

	return array(
		'posts'        => $posts,
		'total'        => $total,
		'total_pages'  => $total_pages,
		'current_page' => $page,
	);
}

/**
 * Số trang hiện tại của URL /yeu-thich/page/N/ hoặc Page thật.
 */
function nntm_section_favorites_current_page(): int {
	$paged = max( 1, absint( get_query_var( 'paged' ) ) );
	if ( $paged > 1 ) {
		return $paged;
	}

	global $wp;
	$request = isset( $wp->request ) ? trim( (string) $wp->request, '/' ) : '';
	if ( preg_match( '#^yeu-thich/page/([0-9]+)$#', $request, $matches ) ) {
		return max( 1, absint( $matches[1] ) );
	}

	return 1;
}

/**
 * Chặn canonical redirect làm mất route ảo /yeu-thich/ khi chưa tạo Page.
 */
function nntm_section_favorites_disable_canonical( $redirect_url ) {
	return nntm_section_is_favorites_request() ? false : $redirect_url;
}
add_filter( 'redirect_canonical', 'nntm_section_favorites_disable_canonical' );

/**
 * Ép /yeu-thich/ dùng template riêng, kể cả chưa có Page trong wp_posts.
 */
function nntm_section_favorites_template_include( string $template ): string {
	if ( ! nntm_section_is_favorites_request() ) {
		return $template;
	}

	$favorite_template = NNTM_THEME_DIR . '/page-yeu-thich.php';
	if ( ! is_readable( $favorite_template ) ) {
		return $template;
	}

	global $wp_query;
	if ( $wp_query instanceof WP_Query ) {
		$wp_query->is_404  = false;
		$wp_query->is_page = true;
	}
	status_header( 200 );

	return $favorite_template;
}
add_filter( 'template_include', 'nntm_section_favorites_template_include', 99 );

/**
 * Body class rõ ràng cho route ảo.
 */
function nntm_section_favorites_body_class( array $classes ): array {
	if ( nntm_section_is_favorites_request() ) {
		$classes[] = 'page-yeu-thich';
	}
	return array_values( array_unique( $classes ) );
}
add_filter( 'body_class', 'nntm_section_favorites_body_class' );

/**
 * Tiêu đề trình duyệt cho route Yêu thích ảo.
 */
function nntm_section_favorites_document_title( string $title ): string {
	return nntm_section_is_favorites_request() ? __( 'Yêu thích', 'nntm' ) : $title;
}
add_filter( 'pre_get_document_title', 'nntm_section_favorites_document_title' );
