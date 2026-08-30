<?php
/**
 * Giá bán và màn chọn ấn phẩm phải mua.
 *
 * Hai đường vào cùng một dữ liệu:
 *   - Màn "Bán & Giá": bảng liệt kê mọi ấn phẩm, tick cuốn nào phải mua rồi
 *     điền giá — sửa hàng loạt trong một lần lưu.
 *   - Trong trang sửa từng ấn phẩm: ô giá nằm ngay cạnh ô tích "Khoá" sẵn có.
 *
 * @package NNTM_PayOS
 */

defined( 'ABSPATH' ) || exit;

/**
 * Khoá meta giữ giá bán, đơn vị ĐỒNG (số nguyên).
 *
 * Lưu số nguyên đồng chứ không lưu số thực: tiền Việt không có phần lẻ, và số
 * thực khi cộng dồn sẽ sai ở hàng đơn vị. PayOS cũng nhận số nguyên.
 */
const NNTM_PAYOS_META_GIA = '_nntm_pub_gia';

/**
 * Giá bán của một ấn phẩm (đồng). 0 = chưa đặt giá.
 *
 * @param int|WP_Post|null $post Ấn phẩm.
 */
function nntm_payos_gia( $post = null ): int {
	$post = get_post( $post );

	return $post ? max( 0, (int) get_post_meta( $post->ID, NNTM_PAYOS_META_GIA, true ) ) : 0;
}

/**
 * Ấn phẩm này có đang bán không: vừa bị khoá, vừa có giá.
 *
 * Khoá mà chưa đặt giá thì KHÔNG bán — vì không biết thu bao nhiêu. Lúc đó sách
 * vẫn đóng, và màn quản trị có cảnh báo để sửa.
 *
 * @param int|WP_Post|null $post Ấn phẩm.
 */
function nntm_payos_dang_ban( $post = null ): bool {
	$post = get_post( $post );

	if ( ! $post || ! function_exists( 'nntm_an_pham_bi_khoa' ) ) {
		return false;
	}

	return nntm_an_pham_bi_khoa( $post ) && nntm_payos_gia( $post ) > 0;
}

/**
 * Đăng ký meta giá.
 */
function nntm_payos_dang_ky_meta(): void {
	register_post_meta(
		'nntm_publication',
		NNTM_PAYOS_META_GIA,
		array(
			'type'              => 'integer',
			'single'            => true,
			'default'           => 0,
			'show_in_rest'      => false,
			'sanitize_callback' => static fn( $v ): int => max( 0, (int) $v ),
			'auth_callback'     => static fn(): bool => current_user_can( 'edit_posts' ),
			'description'       => __( 'Giá bán ấn phẩm, đơn vị đồng.', 'nntm' ),
		)
	);

	// Tệp PDF xem thử — nntm-library đọc khoá này để quyết định gửi byte nào.
	register_post_meta(
		'nntm_publication',
		'_nntm_pdf_xem_thu',
		array(
			'type'              => 'integer',
			'single'            => true,
			'default'           => 0,
			'show_in_rest'      => false,
			'sanitize_callback' => 'absint',
			'auth_callback'     => static fn(): bool => current_user_can( 'edit_posts' ),
			'description'       => __( 'Tệp PDF xem thử (vài trang đầu) cho người chưa mua.', 'nntm' ),
		)
	);
}
add_action( 'init', 'nntm_payos_dang_ky_meta' );

/* -------------------------------------------------------------------------
 * Màn "Bán & Giá"
 * ---------------------------------------------------------------------- */

/**
 * Thêm màn vào menu Ấn phẩm.
 */
function nntm_payos_them_trang_gia(): void {
	add_submenu_page(
		'edit.php?post_type=nntm_publication',
		__( 'Bán & Giá', 'nntm' ),
		__( 'Bán & Giá', 'nntm' ),
		'manage_options',
		'nntm-ban-gia',
		'nntm_payos_ve_trang_gia'
	);
}
add_action( 'admin_menu', 'nntm_payos_them_trang_gia' );

/**
 * Vẽ màn "Bán & Giá".
 */
function nntm_payos_ve_trang_gia(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$da_luu = 0;

	if ( isset( $_POST['nntm_payos_luu'] ) && check_admin_referer( 'nntm_payos_ban_gia' ) ) {
		$ban = isset( $_POST['ban'] ) && is_array( $_POST['ban'] ) ? array_map( 'absint', wp_unslash( $_POST['ban'] ) ) : array();
		$gia = isset( $_POST['gia'] ) && is_array( $_POST['gia'] ) ? wp_unslash( $_POST['gia'] ) : array();
		$xem = isset( $_POST['xem_thu'] ) && is_array( $_POST['xem_thu'] ) ? wp_unslash( $_POST['xem_thu'] ) : array();

		foreach ( (array) ( $_POST['co_mat'] ?? array() ) as $id ) {
			$id = absint( $id );

			if ( $id <= 0 || 'nntm_publication' !== get_post_type( $id ) ) {
				continue;
			}

			update_post_meta( $id, '_nntm_pub_khoa', in_array( $id, $ban, true ) );

			/*
			 * Người nhập hay gõ "150.000" hoặc "150,000" — bỏ hết ký tự không
			 * phải chữ số rồi mới ép kiểu, chứ (int) "150.000" ra 150.
			 */
			$so = isset( $gia[ $id ] ) ? preg_replace( '/\D/', '', (string) $gia[ $id ] ) : '';
			update_post_meta( $id, NNTM_PAYOS_META_GIA, max( 0, (int) $so ) );

			update_post_meta( $id, '_nntm_pdf_xem_thu', isset( $xem[ $id ] ) ? absint( $xem[ $id ] ) : 0 );

			++$da_luu;
		}
	}

	$ds = get_posts(
		array(
			'post_type'   => 'nntm_publication',
			'numberposts' => -1,
			'post_status' => array( 'publish', 'draft' ),
			'orderby'     => 'title',
			'order'       => 'ASC',
		)
	);
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Bán & Giá ấn phẩm', 'nntm' ); ?></h1>

		<?php if ( $da_luu ) : ?>
			<div class="notice notice-success is-dismissible"><p>
				<?php
				printf(
					/* translators: %d: số ấn phẩm đã lưu. */
					esc_html__( 'Đã lưu %d ấn phẩm.', 'nntm' ),
					(int) $da_luu
				);
				?>
			</p></div>
		<?php endif; ?>

		<p class="description" style="max-width:70em;">
			<?php esc_html_e( 'Tick "Phải mua" là cuốn đó đóng lại với người chưa trả tiền. Giá nhập theo đồng, không cần dấu chấm.', 'nntm' ); ?><br />
			<?php esc_html_e( 'Tệp xem thử là một tệp PDF riêng chỉ gồm vài trang đầu. Máy chủ này không có công cụ tách trang, nên phải có tệp riêng thì người chưa mua mới xem thử được — không đặt thì họ gặp thẳng khung thanh toán.', 'nntm' ); ?>
		</p>

		<form method="post">
			<?php wp_nonce_field( 'nntm_payos_ban_gia' ); ?>

			<table class="widefat striped">
				<thead>
					<tr>
						<th style="width:70px;"><?php esc_html_e( 'Phải mua', 'nntm' ); ?></th>
						<th><?php esc_html_e( 'Ấn phẩm', 'nntm' ); ?></th>
						<th style="width:170px;"><?php esc_html_e( 'Giá (đồng)', 'nntm' ); ?></th>
						<th style="width:320px;"><?php esc_html_e( 'Tệp PDF xem thử', 'nntm' ); ?></th>
						<th style="width:160px;"><?php esc_html_e( 'Tình trạng', 'nntm' ); ?></th>
					</tr>
				</thead>
				<tbody>
				<?php foreach ( $ds as $p ) : ?>
					<?php
					$khoa    = function_exists( 'nntm_an_pham_bi_khoa' ) && nntm_an_pham_bi_khoa( $p );
					$gia_ht  = nntm_payos_gia( $p );
					$xem_id  = absint( get_post_meta( $p->ID, '_nntm_pdf_xem_thu', true ) );
					$xem_ten = $xem_id ? get_the_title( $xem_id ) : '';
					?>
					<tr>
						<td>
							<input type="hidden" name="co_mat[]" value="<?php echo esc_attr( (string) $p->ID ); ?>" />
							<input type="checkbox" name="ban[]" value="<?php echo esc_attr( (string) $p->ID ); ?>" <?php checked( $khoa ); ?> />
						</td>
						<td>
							<strong><a href="<?php echo esc_url( (string) get_edit_post_link( $p->ID ) ); ?>"><?php echo esc_html( get_the_title( $p ) ); ?></a></strong>
							<?php if ( 'publish' !== $p->post_status ) : ?>
								<em>— <?php echo esc_html( $p->post_status ); ?></em>
							<?php endif; ?>
						</td>
						<td>
							<input type="text" inputmode="numeric" class="regular-text" style="width:150px;"
								name="gia[<?php echo esc_attr( (string) $p->ID ); ?>]"
								value="<?php echo esc_attr( $gia_ht ? (string) $gia_ht : '' ); ?>"
								placeholder="0" />
						</td>
						<td class="nntm-payos-xemthu" data-post="<?php echo esc_attr( (string) $p->ID ); ?>">
							<input type="hidden" name="xem_thu[<?php echo esc_attr( (string) $p->ID ); ?>]" value="<?php echo esc_attr( (string) $xem_id ); ?>" data-o-tep />
							<span data-ten-tep><?php echo $xem_ten ? esc_html( $xem_ten ) : esc_html__( 'Chưa chọn', 'nntm' ); ?></span><br />
							<button type="button" class="button button-small" data-chon-tep><?php esc_html_e( 'Chọn tệp', 'nntm' ); ?></button>
							<button type="button" class="button button-small" data-bo-tep <?php echo $xem_id ? '' : 'style="display:none"'; ?>><?php esc_html_e( 'Bỏ', 'nntm' ); ?></button>
						</td>
						<td><?php echo wp_kses_post( nntm_payos_nhan_tinh_trang( $p ) ); ?></td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>

			<p class="submit">
				<button type="submit" name="nntm_payos_luu" value="1" class="button button-primary"><?php esc_html_e( 'Lưu tất cả', 'nntm' ); ?></button>
			</p>
		</form>
	</div>
	<?php
}

/**
 * Nhãn mô tả tình trạng bán của một ấn phẩm.
 *
 * @param WP_Post $p Ấn phẩm.
 */
function nntm_payos_nhan_tinh_trang( WP_Post $p ): string {
	$khoa = function_exists( 'nntm_an_pham_bi_khoa' ) && nntm_an_pham_bi_khoa( $p );

	if ( ! $khoa ) {
		return '<span style="color:#646970;">' . esc_html__( 'Mở — ai cũng đọc', 'nntm' ) . '</span>';
	}

	if ( nntm_payos_gia( $p ) <= 0 ) {
		return '<span style="color:#b32d2e;font-weight:600;">' . esc_html__( 'Khoá nhưng CHƯA CÓ GIÁ', 'nntm' ) . '</span>';
	}

	$xem = absint( get_post_meta( $p->ID, '_nntm_pdf_xem_thu', true ) );

	return '<span style="color:#046b02;">' . esc_html( nntm_payos_dinh_dang_tien( nntm_payos_gia( $p ) ) ) . '</span>'
		. ( $xem ? '' : '<br /><span style="color:#996800;">' . esc_html__( 'chưa có tệp xem thử', 'nntm' ) . '</span>' );
}

/**
 * Nạp bộ chọn tệp của Thư viện Media cho màn "Bán & Giá".
 *
 * @param string $hook Slug màn quản trị.
 */
function nntm_payos_nap_js_gia( string $hook ): void {
	if ( ! isset( $_GET['page'] ) || 'nntm-ban-gia' !== $_GET['page'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- chi doc de biet dang o man nao.
		return;
	}

	wp_enqueue_media();

	$js = NNTM_PAYOS_PATH . 'assets/js/ban-gia.js';

	wp_enqueue_script(
		'nntm-payos-ban-gia',
		NNTM_PAYOS_URL . 'assets/js/ban-gia.js',
		array( 'jquery' ),
		file_exists( $js ) ? (string) filemtime( $js ) : NNTM_PAYOS_VER,
		true
	);

	wp_localize_script(
		'nntm-payos-ban-gia',
		'nntmPayosGia',
		array(
			'tieuDe'    => __( 'Chọn tệp PDF xem thử', 'nntm' ),
			'nutChon'   => __( 'Dùng tệp này', 'nntm' ),
			'chuaChon'  => __( 'Chưa chọn', 'nntm' ),
		)
	);
}
add_action( 'admin_enqueue_scripts', 'nntm_payos_nap_js_gia' );

/* -------------------------------------------------------------------------
 * Ô giá trong trang sửa từng ấn phẩm
 * ---------------------------------------------------------------------- */

/**
 * Thêm meta box giá.
 */
function nntm_payos_them_meta_box(): void {
	add_meta_box(
		'nntm_payos_gia',
		__( 'Bán ấn phẩm', 'nntm' ),
		'nntm_payos_ve_meta_box',
		'nntm_publication',
		'side',
		'default'
	);
}
add_action( 'add_meta_boxes_nntm_publication', 'nntm_payos_them_meta_box' );

/**
 * Vẽ meta box giá.
 *
 * @param WP_Post $post Ấn phẩm.
 */
function nntm_payos_ve_meta_box( WP_Post $post ): void {
	wp_nonce_field( 'nntm_payos_gia', 'nntm_payos_gia_nonce' );

	$gia  = nntm_payos_gia( $post );
	$khoa = function_exists( 'nntm_an_pham_bi_khoa' ) && nntm_an_pham_bi_khoa( $post );
	?>
	<p>
		<label for="nntm_payos_gia_input"><strong><?php esc_html_e( 'Giá bán (đồng)', 'nntm' ); ?></strong></label><br />
		<input type="text" inputmode="numeric" id="nntm_payos_gia_input" name="nntm_payos_gia"
			class="widefat" value="<?php echo esc_attr( $gia ? (string) $gia : '' ); ?>" placeholder="0" />
	</p>

	<p class="description">
		<?php esc_html_e( 'Chỉ có tác dụng khi ô "Khoá" ở hộp Tệp PDF được tick.', 'nntm' ); ?>
	</p>

	<?php if ( $khoa && $gia <= 0 ) : ?>
		<div class="notice notice-warning inline" style="margin:8px 0;padding:6px 10px;">
			<p style="margin:.4em 0;"><?php esc_html_e( 'Đang khoá nhưng chưa có giá — hiện không ai mua được, và cũng không ai đọc được.', 'nntm' ); ?></p>
		</div>
	<?php endif; ?>

	<p>
		<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=nntm_publication&page=nntm-ban-gia' ) ); ?>">
			<?php esc_html_e( 'Mở màn Bán & Giá →', 'nntm' ); ?>
		</a>
	</p>
	<?php
}

/**
 * Lưu giá.
 *
 * @param int $post_id ID ấn phẩm.
 */
function nntm_payos_luu_meta_box( int $post_id ): void {
	if ( ! isset( $_POST['nntm_payos_gia_nonce'] )
		|| ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nntm_payos_gia_nonce'] ) ), 'nntm_payos_gia' )
	) {
		return;
	}

	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	$so = isset( $_POST['nntm_payos_gia'] ) ? preg_replace( '/\D/', '', (string) wp_unslash( $_POST['nntm_payos_gia'] ) ) : '';
	update_post_meta( $post_id, NNTM_PAYOS_META_GIA, max( 0, (int) $so ) );
}
add_action( 'save_post_nntm_publication', 'nntm_payos_luu_meta_box' );
