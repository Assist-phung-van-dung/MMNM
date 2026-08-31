<?php
/**
 * Giá bán và màn chọn ấn phẩm phải mua.
 *
 * MỘT Ô DUY NHẤT QUYẾT ĐỊNH: nhập giá là bán, để trống là mở. Không phải tick
 * thêm ô nào ở đâu nữa.
 *
 * Hai đường vào cùng một dữ liệu:
 *   - Màn "Bán & Giá": bảng liệt kê mọi ấn phẩm, sửa hàng loạt trong một lần lưu.
 *   - Trong trang sửa từng ấn phẩm: hộp "Bán ấn phẩm".
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
 * Số trang được xem thử trước khi hiện khung thanh toán.
 */
const NNTM_PAYOS_META_TRANG_THU = '_nntm_pub_trang_xem_thu';

/**
 * Mặc định cho số trang xem thử.
 */
const NNTM_PAYOS_TRANG_THU_MAC_DINH = 2;

/**
 * Số trang được lật trước khi khung thanh toán hiện lên.
 *
 * @param int|WP_Post|null $post Ấn phẩm.
 */
function nntm_payos_so_trang_xem_thu( $post = null ): int {
	$post = get_post( $post );

	if ( ! $post ) {
		return NNTM_PAYOS_TRANG_THU_MAC_DINH;
	}

	$so = (int) get_post_meta( $post->ID, NNTM_PAYOS_META_TRANG_THU, true );

	return $so > 0 ? $so : NNTM_PAYOS_TRANG_THU_MAC_DINH;
}

/**
 * Ấn phẩm này có đang bán không: CHỈ CẦN CÓ GIÁ.
 *
 * ĐỔI TỪ 30/08/2026 — trước đây phải vừa tick ô "Khoá" ở hộp Tệp PDF vừa nhập
 * giá ở hộp khác. Hai ô ở hai nơi cho cùng một ý định, mà quên một cái là ra
 * trạng thái vô nghĩa: khoá mà chưa có giá thì không ai mua được và cũng không
 * ai đọc được. Nay chỉ còn một ô: có giá là bán.
 *
 * @param int|WP_Post|null $post Ấn phẩm.
 */
function nntm_payos_dang_ban( $post = null ): bool {
	$post = get_post( $post );

	return $post ? nntm_payos_gia( $post ) > 0 : false;
}

/**
 * Đặt giá là tự khoá — không phải tick thêm ô nào.
 *
 * Ô "Khoá" cũ ở hộp Tệp PDF vẫn còn tác dụng, nhưng giờ chỉ dùng cho trường hợp
 * muốn đóng cửa mà KHÔNG bán; đặt giá thì không cần đụng tới nó.
 *
 * @param bool         $khoa Giá trị trước đó.
 * @param WP_Post|null $post Ấn phẩm.
 */
function nntm_payos_gia_thi_khoa( $khoa, $post ): bool {
	if ( $khoa ) {
		return true;
	}

	return $post instanceof WP_Post && 'nntm_publication' === $post->post_type && nntm_payos_gia( $post ) > 0;
}
add_filter( 'nntm_an_pham_bi_khoa', 'nntm_payos_gia_thi_khoa', 10, 2 );

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
		$gia   = isset( $_POST['gia'] ) && is_array( $_POST['gia'] ) ? wp_unslash( $_POST['gia'] ) : array();
		$xem   = isset( $_POST['xem_thu'] ) && is_array( $_POST['xem_thu'] ) ? wp_unslash( $_POST['xem_thu'] ) : array();
		$trang = isset( $_POST['trang_thu'] ) && is_array( $_POST['trang_thu'] ) ? wp_unslash( $_POST['trang_thu'] ) : array();

		foreach ( (array) ( $_POST['co_mat'] ?? array() ) as $id ) {
			$id = absint( $id );

			if ( $id <= 0 || 'nntm_publication' !== get_post_type( $id ) ) {
				continue;
			}

			/*
			 * KHÔNG đụng vào _nntm_pub_khoa nữa. Giá quyết định việc bán; ô "Khoá"
			 * ở hộp Tệp PDF giờ chỉ dành cho trường hợp đóng cửa mà không bán, và
			 * màn này ghi đè lên nó thì người ta mất luôn lựa chọn đó.
			 *
			 * Người nhập hay gõ "150.000" — bỏ hết ký tự không phải chữ số rồi mới
			 * ép kiểu, chứ (int) "150.000" ra 150.
			 */
			$so = isset( $gia[ $id ] ) ? preg_replace( '/\D/', '', (string) $gia[ $id ] ) : '';
			update_post_meta( $id, NNTM_PAYOS_META_GIA, max( 0, (int) $so ) );

			update_post_meta( $id, '_nntm_pdf_xem_thu', isset( $xem[ $id ] ) ? absint( $xem[ $id ] ) : 0 );

			$st = isset( $trang[ $id ] ) ? absint( $trang[ $id ] ) : 0;
			update_post_meta( $id, NNTM_PAYOS_META_TRANG_THU, $st > 0 ? $st : NNTM_PAYOS_TRANG_THU_MAC_DINH );

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
			<?php esc_html_e( 'Nhập giá là cuốn đó phải mua mới đọc được. Để trống hoặc 0 là mở, ai cũng đọc. Giá theo đồng, không cần dấu chấm.', 'nntm' ); ?><br />
			<?php esc_html_e( 'Số trang xem thử: người chưa mua lật tới trang này thì khung thanh toán hiện lên. Mặc định 2.', 'nntm' ); ?><br />
			<?php esc_html_e( 'Tệp xem thử là một tệp PDF riêng chỉ gồm vài trang đầu. Máy chủ này không có công cụ tách trang, nên phải có tệp riêng thì người chưa mua mới xem thử được — không đặt thì họ gặp thẳng khung thanh toán.', 'nntm' ); ?>
		</p>

		<form method="post">
			<?php wp_nonce_field( 'nntm_payos_ban_gia' ); ?>

			<table class="widefat striped">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Ấn phẩm', 'nntm' ); ?></th>
						<th style="width:150px;"><?php esc_html_e( 'Giá (đồng)', 'nntm' ); ?></th>
						<th style="width:110px;"><?php esc_html_e( 'Trang xem thử', 'nntm' ); ?></th>
						<th style="width:300px;"><?php esc_html_e( 'Tệp PDF xem thử', 'nntm' ); ?></th>
						<th style="width:170px;"><?php esc_html_e( 'Tình trạng', 'nntm' ); ?></th>
					</tr>
				</thead>
				<tbody>
				<?php foreach ( $ds as $p ) : ?>
					<?php
					$gia_ht  = nntm_payos_gia( $p );
					$xem_id  = absint( get_post_meta( $p->ID, '_nntm_pdf_xem_thu', true ) );
					$xem_ten = $xem_id ? get_the_title( $xem_id ) : '';
					?>
					<tr>
						<td>
							<input type="hidden" name="co_mat[]" value="<?php echo esc_attr( (string) $p->ID ); ?>" />
							<strong><a href="<?php echo esc_url( (string) get_edit_post_link( $p->ID ) ); ?>"><?php echo esc_html( get_the_title( $p ) ); ?></a></strong>
							<?php if ( 'publish' !== $p->post_status ) : ?>
								<em>— <?php echo esc_html( $p->post_status ); ?></em>
							<?php endif; ?>
						</td>
						<td>
							<input type="text" inputmode="numeric" class="regular-text" style="width:130px;"
								name="gia[<?php echo esc_attr( (string) $p->ID ); ?>]"
								value="<?php echo esc_attr( $gia_ht ? (string) $gia_ht : '' ); ?>"
								placeholder="<?php esc_attr_e( 'để trống = mở', 'nntm' ); ?>" />
						</td>
						<td>
							<input type="number" min="1" max="99" style="width:80px;"
								name="trang_thu[<?php echo esc_attr( (string) $p->ID ); ?>]"
								value="<?php echo esc_attr( (string) nntm_payos_so_trang_xem_thu( $p ) ); ?>" />
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
	if ( ! nntm_payos_dang_ban( $p ) ) {
		/*
		 * Không có giá nhưng vẫn tick ô "Khoá" cũ: đóng cửa mà không bán. Trạng
		 * thái này hợp lệ nhưng hiếm — nói rõ ra để quản trị không tưởng là quên
		 * nhập giá.
		 */
		$khoa_tay = (bool) get_post_meta( $p->ID, '_nntm_pub_khoa', true );

		return $khoa_tay
			? '<span style="color:#996800;font-weight:600;">' . esc_html__( 'Đóng, không bán', 'nntm' ) . '</span>'
			: '<span style="color:#646970;">' . esc_html__( 'Mở — ai cũng đọc', 'nntm' ) . '</span>';
	}

	$xem_id_tho = absint( get_post_meta( $p->ID, '_nntm_pdf_xem_thu', true ) );
	$tep_goc    = function_exists( 'nntm_an_pham_pdf_id' ) ? nntm_an_pham_pdf_id( $p ) : 0;

	if ( $xem_id_tho > 0 && $xem_id_tho === $tep_goc ) {
		return '<span style="color:#046b02;font-weight:600;">' . esc_html( nntm_payos_dinh_dang_tien( nntm_payos_gia( $p ) ) ) . '</span>'
			. '<br /><span style="color:#b32d2e;font-weight:600;">' . esc_html__( 'tệp xem thử đang trùng tệp đầy đủ — hệ thống đã chặn', 'nntm' ) . '</span>';
	}

	$xem_id = function_exists( 'nntm_an_pham_pdf_xem_thu_id' )
		? nntm_an_pham_pdf_xem_thu_id( $p )
		: $xem_id_tho;
	$xem = function_exists( 'nntm_lib_tep_dung_duoc' )
		&& nntm_lib_tep_dung_duoc( $xem_id );

	return '<span style="color:#046b02;font-weight:600;">' . esc_html( nntm_payos_dinh_dang_tien( nntm_payos_gia( $p ) ) ) . '</span>'
		. ( $xem
			? ''
			: '<br /><span style="color:#996800;">' . esc_html__( 'chưa có tệp xem thử — khách gặp thẳng khung mua', 'nntm' ) . '</span>' );
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

	$gia = nntm_payos_gia( $post );
	?>
	<p>
		<label for="nntm_payos_gia_input"><strong><?php esc_html_e( 'Giá bán (đồng)', 'nntm' ); ?></strong></label><br />
		<input type="text" inputmode="numeric" id="nntm_payos_gia_input" name="nntm_payos_gia"
			class="widefat" value="<?php echo esc_attr( $gia ? (string) $gia : '' ); ?>" placeholder="0" />
	</p>

	<p class="description">
		<?php esc_html_e( 'Nhập giá là cuốn này phải mua mới đọc được. Để trống là mở, ai cũng đọc. Không phải tick thêm ô nào.', 'nntm' ); ?>
	</p>

	<p>
		<label for="nntm_payos_trang_thu"><strong><?php esc_html_e( 'Số trang cho xem thử', 'nntm' ); ?></strong></label><br />
		<input type="number" min="1" max="99" id="nntm_payos_trang_thu" name="nntm_payos_trang_thu"
			class="small-text" value="<?php echo esc_attr( (string) nntm_payos_so_trang_xem_thu( $post ) ); ?>" />
	</p>
	<p class="description">
		<?php esc_html_e( 'Lật tới trang này thì khung thanh toán hiện lên.', 'nntm' ); ?>
	</p>

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

	$st = isset( $_POST['nntm_payos_trang_thu'] ) ? absint( wp_unslash( $_POST['nntm_payos_trang_thu'] ) ) : 0;
	update_post_meta( $post_id, NNTM_PAYOS_META_TRANG_THU, $st > 0 ? $st : NNTM_PAYOS_TRANG_THU_MAC_DINH );
}
add_action( 'save_post_nntm_publication', 'nntm_payos_luu_meta_box' );
