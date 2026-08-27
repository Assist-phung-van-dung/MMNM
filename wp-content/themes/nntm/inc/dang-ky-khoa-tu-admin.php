<?php

defined( 'ABSPATH' ) || exit;

function nntm_dkkt_quyen(): string {
	return (string) apply_filters( 'nntm_dkkt_quyen', 'manage_options' );
}

function nntm_dkkt_dia_chi_trang(): string {
	return admin_url( 'edit.php?post_type=nntm_retreat&page=nntm-dang-ky-khoa-tu' );
}

function nntm_dkkt_them_menu(): void {
	$moc = add_submenu_page(
		'edit.php?post_type=nntm_retreat',
		__( 'Đăng ký khóa tu', 'nntm' ),
		__( 'Đăng ký khóa tu', 'nntm' ),
		nntm_dkkt_quyen(),
		'nntm-dang-ky-khoa-tu',
		'nntm_dkkt_ve_trang'
	);

	if ( $moc ) {
		add_action( 'load-' . $moc, 'nntm_dkkt_xu_ly' );
	}
}
add_action( 'admin_menu', 'nntm_dkkt_them_menu' );

/**
 * Đổi trạng thái hoặc xoá các đăng ký được chọn, rồi quay lại danh sách.
 */
function nntm_dkkt_xu_ly(): void {
	if ( ! current_user_can( nntm_dkkt_quyen() ) ) {
		return;
	}

	global $wpdb;

	$bang = nntm_dkkt_bang();

	// Tải về CSV.
	if ( isset( $_GET['nntm_dkkt_xuat'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		check_admin_referer( 'nntm_dkkt_xuat' );
		nntm_dkkt_xuat_csv();
	}

	$viec = '';

	if ( isset( $_REQUEST['action'] ) && '-1' !== $_REQUEST['action'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$viec = sanitize_key( wp_unslash( $_REQUEST['action'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	} elseif ( isset( $_REQUEST['action2'] ) && '-1' !== $_REQUEST['action2'] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$viec = sanitize_key( wp_unslash( $_REQUEST['action2'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	}

	if ( ! in_array( $viec, array( 'duyet', 'huy', 'cho', 'xoa' ), true ) ) {
		return;
	}

	$ids = array();

	if ( isset( $_REQUEST['dang_ky'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$tho = (array) wp_unslash( $_REQUEST['dang_ky'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$ids = array_values( array_filter( array_map( 'absint', $tho ) ) );
	}

	if ( empty( $ids ) ) {
		return;
	}

	// Một dòng thì dùng nonce riêng, nhiều dòng thì dùng nonce của bulk action.
	if ( 1 === count( $ids ) && isset( $_REQUEST['_nntm_dkkt'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		check_admin_referer( 'nntm_dkkt_' . $viec . '_' . $ids[0], '_nntm_dkkt' );
	} else {
		check_admin_referer( 'bulk-dang_ky' );
	}

	$cho_phep = implode( ',', array_fill( 0, count( $ids ), '%d' ) );
	$so       = 0;

	if ( 'xoa' === $viec ) {
		$so = (int) $wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL
			$wpdb->prepare( "DELETE FROM {$bang} WHERE id IN ({$cho_phep})", $ids ) // phpcs:ignore WordPress.DB.PreparedSQL
		);
	} else {
		$map = array(
			'duyet' => 'approved',
			'huy'   => 'cancelled',
			'cho'   => 'pending',
		);

		$tham_so = array_merge( array( $map[ $viec ] ), $ids );

		$so = (int) $wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL
			$wpdb->prepare( "UPDATE {$bang} SET status = %s WHERE id IN ({$cho_phep})", $tham_so ) // phpcs:ignore WordPress.DB.PreparedSQL
		);
	}

	$ve = add_query_arg(
		array(
			'nntm_dkkt_viec' => $viec,
			'nntm_dkkt_so'   => $so,
		),
		nntm_dkkt_dia_chi_trang()
	);

	wp_safe_redirect( $ve );
	exit;
}

function nntm_dkkt_xuat_csv(): void {
	global $wpdb;

	$bang = nntm_dkkt_bang();

	$dong = $wpdb->get_results( "SELECT * FROM {$bang} ORDER BY created_at DESC", ARRAY_A ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL

	nocache_headers();
	header( 'Content-Type: text/csv; charset=utf-8' );
	header( 'Content-Disposition: attachment; filename=dang-ky-khoa-tu-' . gmdate( 'Y-m-d' ) . '.csv' );

	$tep = fopen( 'php://output', 'w' );

	// BOM để Excel đọc đúng tiếng Việt.
	fwrite( $tep, chr( 0xEF ) . chr( 0xBB ) . chr( 0xBF ) );

	fputcsv( $tep, array( 'ID', 'Ngày gửi', 'Khóa tu', 'Họ tên', 'Điện thoại', 'Email', 'Ghi chú', 'Trạng thái', 'Tài khoản' ) );

	foreach ( (array) $dong as $d ) {
		fputcsv(
			$tep,
			array(
				$d['id'],
				$d['created_at'],
				get_the_title( (int) $d['retreat_id'] ),
				$d['full_name'],
				$d['phone'],
				$d['email'],
				$d['note'],
				nntm_dkkt_ten_trang_thai( (string) $d['status'] ),
				(int) $d['user_id'] > 0 ? (string) get_the_author_meta( 'user_login', (int) $d['user_id'] ) : '',
			)
		);
	}

	fclose( $tep );
	exit;
}

/**
 * Nạp lớp bảng danh sách. Khai báo trong hàm để chắc chắn WP_List_Table đã có.
 */
function nntm_dkkt_nap_lop(): void {
	if ( class_exists( 'NNTM_Dkkt_Bang_Danh_Sach' ) ) {
		return;
	}

	if ( ! class_exists( 'WP_List_Table' ) ) {
		require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
	}

	/**
	 * Bảng danh sách đăng ký khóa tu.
	 */
	class NNTM_Dkkt_Bang_Danh_Sach extends WP_List_Table {

		public function __construct() {
			parent::__construct(
				array(
					'singular' => 'dang_ky',
					'plural'   => 'dang_ky',
					'ajax'     => false,
				)
			);
		}

		public function get_columns() {
			return array(
				'cb'         => '<input type="checkbox" />',
				'full_name'  => __( 'Người đăng ký', 'nntm' ),
				'retreat_id' => __( 'Khóa tu', 'nntm' ),
				'phone'      => __( 'Điện thoại', 'nntm' ),
				'email'      => __( 'Email', 'nntm' ),
				'note'       => __( 'Ghi chú', 'nntm' ),
				'status'     => __( 'Trạng thái', 'nntm' ),
				'created_at' => __( 'Ngày gửi', 'nntm' ),
			);
		}

		protected function get_sortable_columns() {
			return array(
				'full_name'  => array( 'full_name', false ),
				'created_at' => array( 'created_at', true ),
				'status'     => array( 'status', false ),
			);
		}

		protected function get_bulk_actions() {
			return array(
				'duyet' => __( 'Duyệt', 'nntm' ),
				'cho'   => __( 'Chuyển về chờ duyệt', 'nntm' ),
				'huy'   => __( 'Huỷ', 'nntm' ),
				'xoa'   => __( 'Xoá vĩnh viễn', 'nntm' ),
			);
		}

		protected function column_cb( $item ) {
			return sprintf( '<input type="checkbox" name="dang_ky[]" value="%d" />', (int) $item['id'] );
		}

		protected function column_full_name( $item ) {
			$id = (int) $item['id'];

			$lien = static function ( string $viec ) use ( $id ): string {
				return wp_nonce_url(
					add_query_arg(
						array(
							'action'     => $viec,
							'dang_ky[]'  => $id,
						),
						nntm_dkkt_dia_chi_trang()
					),
					'nntm_dkkt_' . $viec . '_' . $id,
					'_nntm_dkkt'
				);
			};

			$viec = array();

			if ( 'approved' !== $item['status'] ) {
				$viec['duyet'] = '<a href="' . esc_url( $lien( 'duyet' ) ) . '">' . esc_html__( 'Duyệt', 'nntm' ) . '</a>';
			}

			if ( 'cancelled' !== $item['status'] ) {
				$viec['huy'] = '<a href="' . esc_url( $lien( 'huy' ) ) . '">' . esc_html__( 'Huỷ', 'nntm' ) . '</a>';
			} else {
				$viec['cho'] = '<a href="' . esc_url( $lien( 'cho' ) ) . '">' . esc_html__( 'Trả về chờ duyệt', 'nntm' ) . '</a>';
			}

			$viec['xoa'] = '<a href="' . esc_url( $lien( 'xoa' ) ) . '" class="submitdelete" onclick="return confirm(\'' . esc_js( __( 'Xoá vĩnh viễn đăng ký này?', 'nntm' ) ) . '\');">' . esc_html__( 'Xoá', 'nntm' ) . '</a>';

			$ten = '<strong>' . esc_html( (string) $item['full_name'] ) . '</strong>';

			if ( (int) $item['user_id'] > 0 ) {
				$tai_khoan = get_the_author_meta( 'user_login', (int) $item['user_id'] );

				if ( $tai_khoan ) {
					$ten .= '<br /><span class="description">' . esc_html( sprintf( /* translators: %s là tên tài khoản. */ __( 'tài khoản: %s', 'nntm' ), $tai_khoan ) ) . '</span>';
				}
			}

			return $ten . $this->row_actions( $viec );
		}

		protected function column_retreat_id( $item ) {
			$id  = (int) $item['retreat_id'];
			$ten = get_the_title( $id );

			if ( '' === trim( (string) $ten ) ) {
				return '<em>' . esc_html__( '(khóa tu đã bị xoá)', 'nntm' ) . '</em>';
			}

			return '<a href="' . esc_url( (string) get_edit_post_link( $id ) ) . '">' . esc_html( $ten ) . '</a>';
		}

		protected function column_email( $item ) {
			return '<a href="mailto:' . esc_attr( (string) $item['email'] ) . '">' . esc_html( (string) $item['email'] ) . '</a>';
		}

		protected function column_phone( $item ) {
			return '<a href="tel:' . esc_attr( preg_replace( '/[^0-9+]/', '', (string) $item['phone'] ) ) . '">' . esc_html( (string) $item['phone'] ) . '</a>';
		}

		protected function column_note( $item ) {
			$ghi = trim( (string) $item['note'] );

			if ( '' === $ghi ) {
				return '—';
			}

			return esc_html( wp_trim_words( $ghi, 18, '…' ) );
		}

		protected function column_status( $item ) {
			$ma  = (string) $item['status'];
			$mau = array(
				'pending'   => '#B26A00',
				'approved'  => '#1E7B34',
				'cancelled' => '#8A8A8A',
			);

			return '<span style="display:inline-block;padding:2px 10px;border-radius:999px;color:#fff;background:' . esc_attr( isset( $mau[ $ma ] ) ? $mau[ $ma ] : '#8A8A8A' ) . '">'
				. esc_html( nntm_dkkt_ten_trang_thai( $ma ) ) . '</span>';
		}

		protected function column_created_at( $item ) {
			$khi = strtotime( (string) $item['created_at'] );

			return $khi ? esc_html( wp_date( 'd/m/Y H:i', $khi ) ) : '—';
		}

		protected function column_default( $item, $column_name ) {
			return isset( $item[ $column_name ] ) ? esc_html( (string) $item[ $column_name ] ) : '';
		}

		protected function extra_tablenav( $which ) {
			if ( 'top' !== $which ) {
				return;
			}

			$trang_thai_dang_loc = isset( $_GET['trang_thai'] ) ? sanitize_key( wp_unslash( $_GET['trang_thai'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$khoa_tu_dang_loc    = isset( $_GET['khoa_tu'] ) ? absint( wp_unslash( $_GET['khoa_tu'] ) ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

			$khoa_tu = get_posts(
				array(
					'post_type'        => 'nntm_retreat',
					'post_status'      => array( 'publish', 'draft', 'pending', 'private' ),
					'posts_per_page'   => 200,
					'orderby'          => 'title',
					'order'            => 'ASC',
					'suppress_filters' => true,
				)
			);
			?>
			<div class="alignleft actions">
				<label class="screen-reader-text" for="nntm-dkkt-trang-thai"><?php esc_html_e( 'Lọc theo trạng thái', 'nntm' ); ?></label>
				<select name="trang_thai" id="nntm-dkkt-trang-thai">
					<option value=""><?php esc_html_e( 'Mọi trạng thái', 'nntm' ); ?></option>
					<?php foreach ( nntm_dkkt_trang_thai() as $ma => $ten ) : ?>
						<option value="<?php echo esc_attr( $ma ); ?>" <?php selected( $trang_thai_dang_loc, $ma ); ?>><?php echo esc_html( $ten ); ?></option>
					<?php endforeach; ?>
				</select>

				<label class="screen-reader-text" for="nntm-dkkt-khoa-tu"><?php esc_html_e( 'Lọc theo khóa tu', 'nntm' ); ?></label>
				<select name="khoa_tu" id="nntm-dkkt-khoa-tu">
					<option value="0"><?php esc_html_e( 'Mọi khóa tu', 'nntm' ); ?></option>
					<?php foreach ( $khoa_tu as $kt ) : ?>
						<option value="<?php echo esc_attr( (string) $kt->ID ); ?>" <?php selected( $khoa_tu_dang_loc, (int) $kt->ID ); ?>><?php echo esc_html( get_the_title( $kt ) ); ?></option>
					<?php endforeach; ?>
				</select>

				<?php submit_button( __( 'Lọc', 'nntm' ), '', 'filter_action', false ); ?>
			</div>
			<?php
		}

		public function no_items() {
			esc_html_e( 'Chưa có ai đăng ký.', 'nntm' );
		}

		public function prepare_items() {
			global $wpdb;

			$bang = nntm_dkkt_bang();

			$this->_column_headers = array( $this->get_columns(), array(), $this->get_sortable_columns() );

			$moi_trang = 20;
			$trang     = max( 1, (int) $this->get_pagenum() );

			$dieu_kien = array( '1=1' );
			$tham_so   = array();

			$trang_thai = isset( $_GET['trang_thai'] ) ? sanitize_key( wp_unslash( $_GET['trang_thai'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			if ( isset( nntm_dkkt_trang_thai()[ $trang_thai ] ) ) {
				$dieu_kien[] = 'status = %s';
				$tham_so[]   = $trang_thai;
			}

			$khoa_tu = isset( $_GET['khoa_tu'] ) ? absint( wp_unslash( $_GET['khoa_tu'] ) ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			if ( $khoa_tu > 0 ) {
				$dieu_kien[] = 'retreat_id = %d';
				$tham_so[]   = $khoa_tu;
			}

			$tim = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			if ( '' !== $tim ) {
				$nhu         = '%' . $wpdb->esc_like( $tim ) . '%';
				$dieu_kien[] = '( full_name LIKE %s OR phone LIKE %s OR email LIKE %s OR note LIKE %s )';
				array_push( $tham_so, $nhu, $nhu, $nhu, $nhu );
			}

			$loc = implode( ' AND ', $dieu_kien );

			$cot_sap = isset( $_GET['orderby'] ) ? sanitize_key( wp_unslash( $_GET['orderby'] ) ) : 'created_at'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			if ( ! in_array( $cot_sap, array( 'full_name', 'created_at', 'status' ), true ) ) {
				$cot_sap = 'created_at';
			}

			$huong = isset( $_GET['order'] ) && 'asc' === strtolower( (string) wp_unslash( $_GET['order'] ) ) ? 'ASC' : 'DESC'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

			$sql_dem = "SELECT COUNT(*) FROM {$bang} WHERE {$loc}";
			$tong    = (int) ( empty( $tham_so )
				? $wpdb->get_var( $sql_dem ) // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL
				: $wpdb->get_var( $wpdb->prepare( $sql_dem, $tham_so ) ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL

			$sql = "SELECT * FROM {$bang} WHERE {$loc} ORDER BY {$cot_sap} {$huong} LIMIT %d OFFSET %d";
			$dai = array_merge( $tham_so, array( $moi_trang, ( $trang - 1 ) * $moi_trang ) );

			$this->items = (array) $wpdb->get_results( $wpdb->prepare( $sql, $dai ), ARRAY_A ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL

			$this->set_pagination_args(
				array(
					'total_items' => $tong,
					'per_page'    => $moi_trang,
					'total_pages' => (int) ceil( $tong / $moi_trang ),
				)
			);
		}
	}
}

function nntm_dkkt_ve_trang(): void {
	if ( ! current_user_can( nntm_dkkt_quyen() ) ) {
		wp_die( esc_html__( 'Bạn không có quyền xem trang này.', 'nntm' ) );
	}

	if ( ! function_exists( 'nntm_retreat_signup_table_exists' ) || ! nntm_retreat_signup_table_exists() ) {
		echo '<div class="wrap"><h1>' . esc_html__( 'Đăng ký khóa tu', 'nntm' ) . '</h1>';
		echo '<div class="notice notice-error"><p>' . esc_html__( 'Chưa có bảng lưu đăng ký trong cơ sở dữ liệu. Tắt rồi bật lại plugin NNTM Core để tạo bảng.', 'nntm' ) . '</p></div></div>';
		return;
	}

	nntm_dkkt_nap_lop();

	$bang = new NNTM_Dkkt_Bang_Danh_Sach();
	$bang->prepare_items();

	$viec = isset( $_GET['nntm_dkkt_viec'] ) ? sanitize_key( wp_unslash( $_GET['nntm_dkkt_viec'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$so   = isset( $_GET['nntm_dkkt_so'] ) ? absint( wp_unslash( $_GET['nntm_dkkt_so'] ) ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	?>
	<div class="wrap">
		<h1 class="wp-heading-inline"><?php esc_html_e( 'Đăng ký khóa tu', 'nntm' ); ?></h1>

		<a class="page-title-action" href="<?php echo esc_url( wp_nonce_url( add_query_arg( 'nntm_dkkt_xuat', 1, nntm_dkkt_dia_chi_trang() ), 'nntm_dkkt_xuat' ) ); ?>">
			<?php esc_html_e( 'Tải về CSV', 'nntm' ); ?>
		</a>

		<hr class="wp-header-end" />

		<?php if ( '' !== $viec ) : ?>
			<div class="notice notice-success is-dismissible">
				<p>
					<?php
					$cau = array(
						'duyet' => __( 'Đã duyệt %d đăng ký.', 'nntm' ),
						'huy'   => __( 'Đã huỷ %d đăng ký.', 'nntm' ),
						'cho'   => __( 'Đã trả %d đăng ký về chờ duyệt.', 'nntm' ),
						'xoa'   => __( 'Đã xoá %d đăng ký.', 'nntm' ),
					);

					echo esc_html( sprintf( isset( $cau[ $viec ] ) ? $cau[ $viec ] : '%d', $so ) );
					?>
				</p>
			</div>
		<?php endif; ?>

		<form method="get">
			<input type="hidden" name="post_type" value="nntm_retreat" />
			<input type="hidden" name="page" value="nntm-dang-ky-khoa-tu" />
			<?php
			$bang->search_box( __( 'Tìm người đăng ký', 'nntm' ), 'nntm-dkkt-tim' );
			$bang->display();
			?>
		</form>
	</div>
	<?php
}
