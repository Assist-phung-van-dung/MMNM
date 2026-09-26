<?php
/**
 * Bảng xếp hạng Cộng Tu trong wp-admin, xem THEO TỪNG chương trình (nntm_program).
 *
 * VÌ SAO KHÁC BXH NGOÀI TRANG (nntm_kpi_bang_xep_hang() ở class-chuoi-tri.php):
 * ngoài trang cố ý cache transient 24 giờ (chốt mỗi ngày, đỡ tải mùa cao điểm —
 * xem chú thích dài trong nntm_kpi_cam_ket()). Màn quản trị này ngược lại: BQT
 * cần số THẬT ngay khi vừa sửa dữ liệu, nên KHÔNG dùng transient/option đệm,
 * luôn truy vấn thẳng bảng nntm_kpi_log.
 *
 * Cách xếp hạng cũng khác: BXH ngoài trang đánh số hạng TUẦN TỰ theo thứ tự
 * dòng trả về (1,2,3,4…), còn màn này dùng kiểu "competition ranking" giống
 * nntm_kpi_hang_cua_nguoi() — bằng điểm thì cùng hạng, hạng kế nhảy đúng số
 * người phía trước (1,2,2,4). Đây là chênh lệch có sẵn giữa hai nơi, xem
 * docs/18-dashboard-cong-tu.md mục 6 để chủ dự án chọn thống nhất sau.
 *
 * File này KHÔNG đổi gì ở class-chuoi-tri.php hay frontend — chỉ đọc.
 *
 * @package NNTM_Core
 */

namespace NNTM\Core;

defined( 'ABSPATH' ) || exit;

/**
 * Màn quản trị "Bảng xếp hạng".
 */
final class Bxh_Admin {

	/** Slug trang con dưới edit.php?post_type=nntm_program. */
	public const MENU_SLUG = 'nntm-bxh';

	/**
	 * Bản thân đối tượng (khuôn singleton giống Post_Meta/Tu_Khoa_Dong).
	 *
	 * @var Bxh_Admin|null
	 */
	private static ?Bxh_Admin $instance = null;

	/** Lấy đối tượng dùng chung. */
	public static function instance(): Bxh_Admin {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/** Gắn hook WordPress. */
	public function hooks(): void {
		add_action( 'admin_menu', array( $this, 'them_menu' ) );
		add_filter( 'post_row_actions', array( $this, 'them_row_action' ), 10, 2 );
		add_action( 'admin_post_nntm_bxh_lam_moi', array( $this, 'xu_ly_lam_moi' ) );
		add_action( 'admin_post_nntm_bxh_xuat_csv', array( $this, 'xu_ly_xuat_csv' ) );
	}

	/**
	 * Quyền xem/thao tác màn này — lọc được qua filter 'nntm_bxh_quyen'.
	 */
	public static function quyen(): string {
		return (string) apply_filters( 'nntm_bxh_quyen', 'manage_options' );
	}

	/** Đăng ký submenu dưới CPT nntm_program. */
	public function them_menu(): void {
		add_submenu_page(
			'edit.php?post_type=nntm_program',
			__( 'Bảng xếp hạng', 'nntm' ),
			__( 'Bảng xếp hạng', 'nntm' ),
			self::quyen(),
			self::MENU_SLUG,
			array( $this, 've_trang' )
		);
	}

	/**
	 * Thêm hàng thao tác "Xếp hạng" ở màn danh sách chương trình.
	 *
	 * @param array<string,string> $actions Các hành động sẵn có.
	 * @param \WP_Post              $post    Bài đang xét.
	 * @return array<string,string>
	 */
	public function them_row_action( array $actions, \WP_Post $post ): array {
		if ( 'nntm_program' !== $post->post_type || ! current_user_can( self::quyen() ) ) {
			return $actions;
		}

		$url = add_query_arg(
			array(
				'post_type'    => 'nntm_program',
				'page'         => self::MENU_SLUG,
				'chuong-trinh' => $post->ID,
			),
			admin_url( 'edit.php' )
		);

		$actions['nntm_bxh'] = '<a href="' . esc_url( $url ) . '">' . esc_html__( 'Xếp hạng', 'nntm' ) . '</a>';

		return $actions;
	}

	/* =====================================================================
	 * Dữ liệu — chọn chương trình, truy vấn tươi, tính tổng.
	 * ===================================================================== */

	/**
	 * Mọi chương trình chưa vào thùng rác, mới nhất trước.
	 *
	 * @return \WP_Post[]
	 */
	public static function danh_sach_chuong_trinh(): array {
		return get_posts(
			array(
				'post_type'        => 'nntm_program',
				'post_status'      => array( 'publish', 'draft', 'private', 'future' ),
				'posts_per_page'   => -1,
				'orderby'          => array(
					'date' => 'DESC',
					'ID'   => 'DESC',
				),
				'suppress_filters' => true,
			)
		);
	}

	/**
	 * Chương trình đang được chọn để xem: ?chuong-trinh=ID hợp lệ → chương
	 * trình đang mở (nntm_program_hien_tai()) → chương trình mới nhất.
	 *
	 * @param \WP_Post[] $ds_chuong_trinh Danh sách từ danh_sach_chuong_trinh(), không rỗng.
	 */
	public static function chuong_trinh_dang_chon( array $ds_chuong_trinh ): int {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- chỉ đọc để CHỌN dữ liệu hiển thị, không phải hành động.
		$yeu_cau = isset( $_GET['chuong-trinh'] ) ? absint( wp_unslash( $_GET['chuong-trinh'] ) ) : 0;

		if ( $yeu_cau > 0 ) {
			foreach ( $ds_chuong_trinh as $ct ) {
				if ( $yeu_cau === $ct->ID ) {
					return $yeu_cau;
				}
			}
		}

		$hien_tai = function_exists( 'nntm_program_hien_tai' ) ? nntm_program_hien_tai() : null;
		if ( $hien_tai instanceof \WP_Post ) {
			return (int) $hien_tai->ID;
		}

		return (int) $ds_chuong_trinh[0]->ID;
	}

	/**
	 * Số liệu TƯƠI theo từng người của một chương trình — MỘT truy vấn gộp,
	 * kèm hạng kiểu competition ranking (bằng điểm cùng hạng: 1,2,2,4).
	 *
	 * Không cache — xem chú thích đầu file lý do khác BXH ngoài trang.
	 *
	 * @param int $program_id ID chương trình.
	 * @return array<int,array{user_id:int,phap_danh:string,vung_mien:string,ten_dang_nhap:string,email:string,tai_khoan_da_xoa:bool,cam_ket:int,thuc_hien:int,tien_trinh:float,so_ngay:int,gan_nhat:?string,tham_gia_tu:?string,hang:int}>
	 */
	public static function lay_du_lieu_tho( int $program_id ): array {
		global $wpdb;

		if ( $program_id <= 0 ) {
			return array();
		}

		$table = \NNTM\Core\Schema::table( 'kpi_log' );

		$dong = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT
					user_id,
					SUM( CASE WHEN metric = %s THEN value ELSE 0 END ) AS cam_ket,
					SUM( CASE WHEN metric = %s THEN value ELSE 0 END ) AS thuc_hien,
					COUNT( DISTINCT CASE WHEN metric = %s THEN log_date ELSE NULL END ) AS so_ngay,
					MAX( CASE WHEN metric = %s THEN log_date ELSE NULL END ) AS gan_nhat,
					MIN( log_date ) AS tham_gia_tu
				FROM {$table}
				WHERE program_id = %d
				GROUP BY user_id",
				NNTM_KPI_METRIC_CAM_KET,
				NNTM_KPI_METRIC_THUC_HIEN,
				NNTM_KPI_METRIC_THUC_HIEN,
				NNTM_KPI_METRIC_THUC_HIEN,
				$program_id
			),
			ARRAY_A
		);

		if ( empty( $dong ) ) {
			return array();
		}

		$user_ids = array_map( 'intval', wp_list_pluck( $dong, 'user_id' ) );

		// Pháp danh + vùng miền: một truy vấn gộp có sẵn ở class-chuoi-tri.php.
		$thong_tin_phap_danh = function_exists( 'nntm_kpi_phap_danh_va_vung_mien' ) ? nntm_kpi_phap_danh_va_vung_mien( $user_ids ) : array();

		// Tài khoản (user_login/email): một truy vấn gộp riêng, không lặp trong vòng lặp.
		$users   = get_users(
			array(
				'include' => $user_ids,
				'fields'  => array( 'ID', 'user_login', 'user_email' ),
			)
		);
		$theo_id = array();
		foreach ( $users as $u ) {
			$theo_id[ (int) $u->ID ] = $u;
		}

		$hang_moi = array();
		foreach ( $dong as $d ) {
			$user_id   = (int) $d['user_id'];
			$cam_ket   = (int) $d['cam_ket'];
			$thuc_hien = (int) $d['thuc_hien'];
			$ton_tai   = isset( $theo_id[ $user_id ] );

			$hang_moi[] = array(
				'user_id'          => $user_id,
				'phap_danh'        => (string) ( $thong_tin_phap_danh[ $user_id ]['phap_danh'] ?? '' ),
				'vung_mien'        => (string) ( $thong_tin_phap_danh[ $user_id ]['vung_mien'] ?? '' ),
				'ten_dang_nhap'    => $ton_tai ? (string) $theo_id[ $user_id ]->user_login : '',
				'email'            => $ton_tai ? (string) $theo_id[ $user_id ]->user_email : '',
				'tai_khoan_da_xoa' => ! $ton_tai,
				'cam_ket'          => $cam_ket,
				'thuc_hien'        => $thuc_hien,
				'tien_trinh'       => function_exists( 'nntm_kpi_tinh_tien_trinh' ) ? nntm_kpi_tinh_tien_trinh( $thuc_hien, $cam_ket ) : ( $cam_ket > 0 ? $thuc_hien / $cam_ket : 0.0 ),
				'so_ngay'          => (int) $d['so_ngay'],
				'gan_nhat'         => $d['gan_nhat'] ? (string) $d['gan_nhat'] : null,
				'tham_gia_tu'      => $d['tham_gia_tu'] ? (string) $d['tham_gia_tu'] : null,
				'hang'             => 0,
			);
		}

		// Competition ranking theo thuc_hien giảm dần — khớp nntm_kpi_hang_cua_nguoi().
		usort(
			$hang_moi,
			static function ( array $a, array $b ): int {
				if ( $a['thuc_hien'] === $b['thuc_hien'] ) {
					return $a['user_id'] <=> $b['user_id'];
				}
				return $b['thuc_hien'] <=> $a['thuc_hien'];
			}
		);

		$hang  = 0;
		$truoc = null;
		foreach ( $hang_moi as $i => &$r ) {
			if ( null === $truoc || $r['thuc_hien'] !== $truoc ) {
				$hang = $i + 1;
			}
			$r['hang'] = $hang;
			$truoc     = $r['thuc_hien'];
		}
		unset( $r );

		return $hang_moi;
	}

	/**
	 * Tổng số người tham gia, tổng cam kết, tổng đã trì, tiến trình % — tính
	 * thẳng từ kết quả lay_du_lieu_tho(), không đọc option đệm.
	 *
	 * @param array<int,array<string,mixed>> $rows Kết quả lay_du_lieu_tho().
	 * @return array{so_nguoi:int,cam_ket:int,thuc_hien:int,tien_trinh:float}
	 */
	public static function tinh_tong( array $rows ): array {
		$cam_ket   = 0;
		$thuc_hien = 0;

		foreach ( $rows as $r ) {
			$cam_ket   += (int) $r['cam_ket'];
			$thuc_hien += (int) $r['thuc_hien'];
		}

		return array(
			'so_nguoi'   => count( $rows ),
			'cam_ket'    => $cam_ket,
			'thuc_hien'  => $thuc_hien,
			'tien_trinh' => function_exists( 'nntm_kpi_tinh_tien_trinh' ) ? nntm_kpi_tinh_tien_trinh( $thuc_hien, $cam_ket ) : ( $cam_ket > 0 ? $thuc_hien / $cam_ket : 0.0 ),
		);
	}

	/* =====================================================================
	 * Xoá cache BXH ngoài trang — KHÔNG gọi hàm theme (plugin không phụ
	 * thuộc theme), tự lặp lại cách quét transient bằng LIKE trên wp_options.
	 * ===================================================================== */

	/**
	 * Xoá mọi transient BXH ngoài trang của một chương trình rồi tính lại ba
	 * con số tổng đệm — dùng khi BQT vừa sửa dữ liệu và muốn ngoài trang cập
	 * nhật ngay, không đợi hết 24 giờ.
	 *
	 * @param int $program_id ID chương trình.
	 */
	public static function xoa_cache_bxh( int $program_id ): void {
		if ( $program_id <= 0 ) {
			return;
		}

		global $wpdb;

		// Các limit hay dùng nhất (nntm_kpi_bang_xep_hang() mặc định 200, theme dùng thêm 50).
		foreach ( array( 50, 200 ) as $limit ) {
			delete_transient( 'nntm_kpi_bxh_' . $program_id . '_' . $limit );
		}

		// Quét thêm mọi transient còn sót (limit khác filter nntm_congtu_bxh_cache_limits)
		// bằng LIKE trên wp_options — cùng kỹ thuật theme dùng, lặp lại ở đây vì plugin
		// không được phụ thuộc theme.
		$tien_to = '_transient_nntm_kpi_bxh_' . $program_id . '_';
		$like    = $wpdb->esc_like( $tien_to ) . '%';
		$names   = $wpdb->get_col(
			$wpdb->prepare( "SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s", $like )
		);

		foreach ( (array) $names as $option_name ) {
			$ten_transient = substr( (string) $option_name, strlen( '_transient_' ) );
			if ( '' !== $ten_transient ) {
				delete_transient( $ten_transient );
			}
		}

		// Theme có thể đã đăng ký thêm limit riêng qua filter — gọi thêm cho chắc
		// nếu hàm đó tồn tại, không coi là bắt buộc (plugin không phụ thuộc theme).
		if ( function_exists( 'nntm_congtu_xoa_cache_bxh' ) ) {
			nntm_congtu_xoa_cache_bxh( $program_id );
		}

		if ( function_exists( 'nntm_kpi_tinh_lai_tong' ) ) {
			nntm_kpi_tinh_lai_tong( $program_id );
		}
	}

	/* =====================================================================
	 * CSV — tách hàm dựng nội dung khỏi hàm gửi header + exit để dễ kiểm.
	 * ===================================================================== */

	/**
	 * Chặn CSV injection: ô bắt đầu bằng = + - @ thì thêm dấu ' phía trước.
	 *
	 * @param string $gia_tri Giá trị gốc.
	 */
	public static function an_toan_csv( string $gia_tri ): string {
		if ( '' !== $gia_tri && in_array( $gia_tri[0], array( '=', '+', '-', '@' ), true ) ) {
			return "'" . $gia_tri;
		}

		return $gia_tri;
	}

	/**
	 * Dựng nội dung CSV (kèm BOM UTF-8) từ dữ liệu đã có hạng — KHÔNG gửi
	 * header/exit, để script kiểm thử gọi thẳng và so khớp chuỗi trả về.
	 *
	 * @param array<int,array<string,mixed>> $rows Kết quả lay_du_lieu_tho(), đã có 'hang'.
	 */
	public static function xuat_csv_noi_dung( array $rows ): string {
		usort(
			$rows,
			static function ( array $a, array $b ): int {
				return $a['hang'] <=> $b['hang'];
			}
		);

		$tep = fopen( 'php://temp', 'w+' );

		fwrite( $tep, chr( 0xEF ) . chr( 0xBB ) . chr( 0xBF ) );

		fputcsv(
			$tep,
			array_map(
				array( __CLASS__, 'an_toan_csv' ),
				array(
					__( 'Hạng', 'nntm' ),
					__( 'Pháp danh', 'nntm' ),
					__( 'Tài khoản', 'nntm' ),
					__( 'Email', 'nntm' ),
					__( 'Vùng miền', 'nntm' ),
					__( 'Đã trì', 'nntm' ),
					__( 'Cam kết', 'nntm' ),
					__( 'Tiến trình %', 'nntm' ),
					__( 'Số ngày khai báo', 'nntm' ),
					__( 'Khai báo gần nhất', 'nntm' ),
					__( 'Tham gia từ', 'nntm' ),
				)
			)
		);

		foreach ( $rows as $r ) {
			$da_xoa = ! empty( $r['tai_khoan_da_xoa'] );

			fputcsv(
				$tep,
				array_map(
					array( __CLASS__, 'an_toan_csv' ),
					array(
						(string) $r['hang'],
						$da_xoa ? sprintf( '(tài khoản đã xoá) #%d', $r['user_id'] ) : (string) $r['phap_danh'],
						$da_xoa ? sprintf( '(tài khoản đã xoá) #%d', $r['user_id'] ) : (string) $r['ten_dang_nhap'],
						$da_xoa ? '' : (string) $r['email'],
						(string) $r['vung_mien'],
						(string) $r['thuc_hien'],
						(string) $r['cam_ket'],
						$r['cam_ket'] > 0 ? (string) (int) round( $r['tien_trinh'] * 100 ) : '—',
						(string) $r['so_ngay'],
						$r['gan_nhat'] ? mysql2date( 'd/m/Y', (string) $r['gan_nhat'] ) : '—',
						$r['tham_gia_tu'] ? mysql2date( 'd/m/Y', (string) $r['tham_gia_tu'] ) : '—',
					)
				)
			);
		}

		rewind( $tep );
		$noi_dung = (string) stream_get_contents( $tep );
		fclose( $tep );

		return $noi_dung;
	}

	/** URL tải CSV (đã kèm nonce) cho một chương trình. */
	public static function url_xuat_csv( int $program_id ): string {
		return wp_nonce_url(
			add_query_arg(
				array(
					'action'       => 'nntm_bxh_xuat_csv',
					'chuong-trinh' => $program_id,
				),
				admin_url( 'admin-post.php' )
			),
			'nntm_bxh_xuat_csv'
		);
	}

	/** Xử lý tải CSV — kiểm quyền + nonce rồi gửi header, gọi xuat_csv_noi_dung() để dựng nội dung. */
	public function xu_ly_xuat_csv(): void {
		if ( ! current_user_can( self::quyen() ) ) {
			wp_die( esc_html__( 'Bạn không có quyền.', 'nntm' ), 403 );
		}

		check_admin_referer( 'nntm_bxh_xuat_csv' );

		$program_id = isset( $_GET['chuong-trinh'] ) ? absint( wp_unslash( $_GET['chuong-trinh'] ) ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- đã check_admin_referer ở trên.
		$post       = $program_id > 0 ? get_post( $program_id ) : null;

		if ( ! ( $post instanceof \WP_Post ) || 'nntm_program' !== $post->post_type ) {
			wp_die( esc_html__( 'Chương trình không hợp lệ.', 'nntm' ), 404 );
		}

		$rows     = self::lay_du_lieu_tho( $program_id );
		$noi_dung = self::xuat_csv_noi_dung( $rows );
		$slug     = '' !== $post->post_name ? $post->post_name : sanitize_title( $post->post_title );

		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=bang-xep-hang-' . $slug . '-' . current_time( 'Y-m-d' ) . '.csv' );

		// Nội dung CSV thuần văn bản đã qua an_toan_csv(), không phải HTML — in nguyên văn.
		echo $noi_dung; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		exit;
	}

	/* =====================================================================
	 * Nút "Làm mới BXH ngoài trang".
	 * ===================================================================== */

	/** Xử lý nút làm mới: xoá cache transient + tính lại tổng đệm rồi quay lại màn. */
	public function xu_ly_lam_moi(): void {
		if ( ! current_user_can( self::quyen() ) ) {
			wp_die( esc_html__( 'Bạn không có quyền.', 'nntm' ), 403 );
		}

		check_admin_referer( 'nntm_bxh_lam_moi' );

		$program_id = isset( $_POST['chuong-trinh'] ) ? absint( wp_unslash( $_POST['chuong-trinh'] ) ) : 0;
		$post       = $program_id > 0 ? get_post( $program_id ) : null;

		if ( ! ( $post instanceof \WP_Post ) || 'nntm_program' !== $post->post_type ) {
			wp_die( esc_html__( 'Chương trình không hợp lệ.', 'nntm' ), 404 );
		}

		self::xoa_cache_bxh( $program_id );

		wp_safe_redirect(
			add_query_arg(
				array(
					'post_type'         => 'nntm_program',
					'page'              => self::MENU_SLUG,
					'chuong-trinh'      => $program_id,
					'nntm_bxh_lam_moi'  => 1,
				),
				admin_url( 'edit.php' )
			)
		);
		exit;
	}

	/* =====================================================================
	 * Vẽ trang.
	 * ===================================================================== */

	/** Vẽ màn "Bảng xếp hạng". */
	public function ve_trang(): void {
		if ( ! current_user_can( self::quyen() ) ) {
			wp_die( esc_html__( 'Bạn không có quyền xem trang này.', 'nntm' ) );
		}

		echo '<div class="wrap">';
		echo '<h1 class="wp-heading-inline">' . esc_html__( 'Bảng xếp hạng', 'nntm' ) . '</h1>';

		$ds_chuong_trinh = self::danh_sach_chuong_trinh();

		if ( empty( $ds_chuong_trinh ) ) {
			echo '<div class="notice notice-warning"><p>' . esc_html__( 'Chưa có chương trình trì tụng nào.', 'nntm' ) . '</p></div></div>';
			return;
		}

		$program_id = self::chuong_trinh_dang_chon( $ds_chuong_trinh );
		$program    = get_post( $program_id );

		if ( ! ( $program instanceof \WP_Post ) ) {
			echo '<div class="notice notice-error"><p>' . esc_html__( 'Không tìm thấy chương trình.', 'nntm' ) . '</p></div></div>';
			return;
		}

		echo ' <a class="page-title-action" href="' . esc_url( self::url_xuat_csv( $program_id ) ) . '">' . esc_html__( 'Tải về CSV', 'nntm' ) . '</a>';
		echo '<hr class="wp-header-end" />';

		if ( isset( $_GET['nntm_bxh_lam_moi'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- chỉ hiển thị thông báo, hành động thật đã kiểm nonce ở xu_ly_lam_moi().
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Đã làm mới BXH ngoài trang.', 'nntm' ) . '</p></div>';
		}

		$rows = self::lay_du_lieu_tho( $program_id );
		$tong = self::tinh_tong( $rows );
		?>
		<table class="widefat striped" style="max-width:860px;margin:16px 0">
			<tbody>
				<tr><th style="width:240px"><?php esc_html_e( 'Số người tham gia', 'nntm' ); ?></th><td><?php echo esc_html( number_format_i18n( $tong['so_nguoi'] ) ); ?></td></tr>
				<tr><th><?php esc_html_e( 'Tổng cam kết', 'nntm' ); ?></th><td><?php echo esc_html( number_format_i18n( $tong['cam_ket'] ) ); ?></td></tr>
				<tr><th><?php esc_html_e( 'Tổng đã trì', 'nntm' ); ?></th><td><?php echo esc_html( number_format_i18n( $tong['thuc_hien'] ) ); ?></td></tr>
				<tr><th><?php esc_html_e( 'Tiến trình chung', 'nntm' ); ?></th><td><?php echo esc_html( $tong['cam_ket'] > 0 ? number_format_i18n( (int) round( $tong['tien_trinh'] * 100 ) ) . '%' : '—' ); ?></td></tr>
			</tbody>
		</table>
		<p class="description"><?php esc_html_e( 'Số liệu ở đây luôn tươi (đọc thẳng cơ sở dữ liệu). BXH ngoài trang được chốt mỗi ngày nên có thể lệch với bảng này tới 24 giờ.', 'nntm' ); ?></p>
		<?php

		nap_lop_bang_xep_hang();

		$bang = new Bxh_Bang_Danh_Sach( $rows, $ds_chuong_trinh, $program_id );
		$bang->prepare_items();

		echo '<form method="get">';
		echo '<input type="hidden" name="post_type" value="nntm_program" />';
		echo '<input type="hidden" name="page" value="' . esc_attr( self::MENU_SLUG ) . '" />';
		$bang->search_box( __( 'Tìm pháp danh / tài khoản / email', 'nntm' ), 'nntm-bxh-tim' );
		$bang->display();
		echo '</form>';

		echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" style="margin-top:16px">';
		echo '<input type="hidden" name="action" value="nntm_bxh_lam_moi" />';
		echo '<input type="hidden" name="chuong-trinh" value="' . esc_attr( (string) $program_id ) . '" />';
		wp_nonce_field( 'nntm_bxh_lam_moi' );
		submit_button( __( 'Làm mới BXH ngoài trang', 'nntm' ), 'secondary', 'submit', false );
		echo ' <span class="description">' . esc_html__( 'Dùng khi vừa sửa dữ liệu và muốn ngoài trang cập nhật ngay, không cần đợi hết 24 giờ.', 'nntm' ) . '</span>';
		echo '</form>';

		echo '</div>';
	}
}

/* =========================================================================
 * Bảng danh sách (WP_List_Table) — khai báo NGOÀI class Bxh_Admin vì PHP
 * không cho khai báo class lồng bên trong thân một class khác (kể cả trong
 * thân một phương thức của class đó). Cùng khuôn với nntm_dkkt_nap_lop()
 * ở theme (wp-content/themes/nntm/inc/dang-ky-khoa-tu-admin.php).
 * ========================================================================= */

/** Nạp lớp bảng danh sách. Khai báo trong hàm để chắc chắn WP_List_Table đã có. */
function nap_lop_bang_xep_hang(): void {
	if ( class_exists( __NAMESPACE__ . '\\Bxh_Bang_Danh_Sach' ) ) {
		return;
	}

	if ( ! class_exists( 'WP_List_Table' ) ) {
		require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
	}

	/**
	 * Bảng danh sách xếp hạng — dữ liệu đã được lay_du_lieu_tho() truyền
	 * sẵn (đã có hạng), lớp này chỉ lọc/sắp xếp/phân trang trong PHP.
	 */
	class Bxh_Bang_Danh_Sach extends \WP_List_Table {

		/** @var array<int,array<string,mixed>> Toàn bộ dữ liệu chương trình, đã có hạng. */
		private array $du_lieu_day_du;

		/** @var \WP_Post[] Danh sách chương trình cho dropdown chọn. */
		private array $ds_chuong_trinh;

		/** @var int Chương trình đang xem. */
		private int $program_id_dang_chon;

		/**
		 * @param array<int,array<string,mixed>> $du_lieu_day_du       Kết quả lay_du_lieu_tho().
		 * @param \WP_Post[]                       $ds_chuong_trinh      Danh sách chương trình cho dropdown.
		 * @param int                              $program_id_dang_chon Chương trình đang xem.
		 */
		public function __construct( array $du_lieu_day_du, array $ds_chuong_trinh, int $program_id_dang_chon ) {
			parent::__construct(
				array(
					'singular' => 'thanh_vien',
					'plural'   => 'thanh_vien',
					'ajax'     => false,
				)
			);

			$this->du_lieu_day_du       = $du_lieu_day_du;
			$this->ds_chuong_trinh      = $ds_chuong_trinh;
			$this->program_id_dang_chon = $program_id_dang_chon;
		}

		public function get_columns() {
			return array(
				'hang'        => __( 'Hạng', 'nntm' ),
				'phap_danh'   => __( 'Pháp danh', 'nntm' ),
				'tai_khoan'   => __( 'Tài khoản', 'nntm' ),
				'vung_mien'   => __( 'Vùng miền', 'nntm' ),
				'thuc_hien'   => __( 'Đã trì', 'nntm' ),
				'cam_ket'     => __( 'Cam kết', 'nntm' ),
				'tien_trinh'  => __( 'Tiến trình %', 'nntm' ),
				'so_ngay'     => __( 'Số ngày khai báo', 'nntm' ),
				'gan_nhat'    => __( 'Khai báo gần nhất', 'nntm' ),
				'tham_gia_tu' => __( 'Tham gia từ', 'nntm' ),
			);
		}

		protected function get_sortable_columns() {
			return array(
				'hang'       => array( 'hang', false ),
				'thuc_hien'  => array( 'thuc_hien', true ),
				'cam_ket'    => array( 'cam_ket', true ),
				'tien_trinh' => array( 'tien_trinh', true ),
				'so_ngay'    => array( 'so_ngay', true ),
				'gan_nhat'   => array( 'gan_nhat', true ),
			);
		}

		public function no_items() {
			esc_html_e( 'Chưa có ai tham gia chương trình này.', 'nntm' );
		}

		protected function column_hang( $item ) {
			return number_format_i18n( (int) $item['hang'] );
		}

		protected function column_phap_danh( $item ) {
			if ( ! empty( $item['tai_khoan_da_xoa'] ) ) {
				return '<em>' . esc_html( sprintf( /* translators: %d: ID tài khoản đã xoá. */ __( '(tài khoản đã xoá) #%d', 'nntm' ), (int) $item['user_id'] ) ) . '</em>';
			}

			$ten = '' !== $item['phap_danh'] ? (string) $item['phap_danh'] : sprintf( '#%d', (int) $item['user_id'] );

			return '<a href="' . esc_url( admin_url( 'user-edit.php?user_id=' . (int) $item['user_id'] ) ) . '">' . esc_html( $ten ) . '</a>';
		}

		protected function column_tai_khoan( $item ) {
			if ( ! empty( $item['tai_khoan_da_xoa'] ) ) {
				return '<em>' . esc_html( sprintf( /* translators: %d: ID tài khoản đã xoá. */ __( '(tài khoản đã xoá) #%d', 'nntm' ), (int) $item['user_id'] ) ) . '</em>';
			}

			$html = esc_html( (string) $item['ten_dang_nhap'] );
			if ( '' !== (string) $item['email'] ) {
				$html .= '<br /><span class="description">' . esc_html( (string) $item['email'] ) . '</span>';
			}

			return $html;
		}

		protected function column_vung_mien( $item ) {
			return '' !== (string) $item['vung_mien'] ? esc_html( (string) $item['vung_mien'] ) : '—';
		}

		protected function column_thuc_hien( $item ) {
			return number_format_i18n( (int) $item['thuc_hien'] );
		}

		protected function column_cam_ket( $item ) {
			return number_format_i18n( (int) $item['cam_ket'] );
		}

		protected function column_tien_trinh( $item ) {
			if ( (int) $item['cam_ket'] <= 0 ) {
				return '—';
			}

			return number_format_i18n( (int) round( $item['tien_trinh'] * 100 ) ) . '%';
		}

		protected function column_so_ngay( $item ) {
			return number_format_i18n( (int) $item['so_ngay'] );
		}

		protected function column_gan_nhat( $item ) {
			return $item['gan_nhat'] ? esc_html( mysql2date( 'd/m/Y', (string) $item['gan_nhat'] ) ) : '—';
		}

		protected function column_tham_gia_tu( $item ) {
			return $item['tham_gia_tu'] ? esc_html( mysql2date( 'd/m/Y', (string) $item['tham_gia_tu'] ) ) : '—';
		}

		protected function column_default( $item, $column_name ) {
			return isset( $item[ $column_name ] ) ? esc_html( (string) $item[ $column_name ] ) : '';
		}

		protected function extra_tablenav( $which ) {
			if ( 'top' !== $which ) {
				return;
			}
			?>
			<div class="alignleft actions">
				<label class="screen-reader-text" for="nntm-bxh-chuong-trinh"><?php esc_html_e( 'Chọn chương trình', 'nntm' ); ?></label>
				<select name="chuong-trinh" id="nntm-bxh-chuong-trinh">
					<?php foreach ( $this->ds_chuong_trinh as $ct ) : ?>
						<?php
						$dang_mo = function_exists( 'nntm_program_dang_mo' ) && nntm_program_dang_mo( $ct->ID );
						$nhan    = get_the_title( $ct ) . ( $dang_mo ? ' ' . __( '(đang mở)', 'nntm' ) : '' );
						?>
						<option value="<?php echo esc_attr( (string) $ct->ID ); ?>" <?php selected( $this->program_id_dang_chon, $ct->ID ); ?>><?php echo esc_html( $nhan ); ?></option>
					<?php endforeach; ?>
				</select>
				<?php submit_button( __( 'Xem', 'nntm' ), '', 'nntm_bxh_xem', false ); ?>
			</div>
			<?php
		}

		/**
		 * Không truy vấn CSDL ở đây — dữ liệu tươi đã được lay_du_lieu_tho()
		 * truyền sẵn qua constructor. Hàm này chỉ lọc theo ô tìm, sắp xếp
		 * theo cột đang chọn (KHÔNG đổi hạng) rồi phân trang trong PHP.
		 */
		public function prepare_items() {
			$this->_column_headers = array( $this->get_columns(), array(), $this->get_sortable_columns() );

			$rows = $this->du_lieu_day_du;

			$tim = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

			if ( '' !== $tim ) {
				$tim_chuan = strtolower( remove_accents( $tim ) );
				$rows      = array_values(
					array_filter(
						$rows,
						static function ( array $r ) use ( $tim_chuan ): bool {
							foreach ( array( $r['phap_danh'], $r['ten_dang_nhap'], $r['email'] ) as $gt ) {
								if ( '' !== (string) $gt && str_contains( strtolower( remove_accents( (string) $gt ) ), $tim_chuan ) ) {
									return true;
								}
							}
							return false;
						}
					)
				);
			}

			$cot_hop_le = array( 'hang', 'thuc_hien', 'cam_ket', 'tien_trinh', 'so_ngay', 'gan_nhat' );
			$cot_sap    = isset( $_GET['orderby'] ) ? sanitize_key( wp_unslash( $_GET['orderby'] ) ) : 'hang'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			if ( ! in_array( $cot_sap, $cot_hop_le, true ) ) {
				$cot_sap = 'hang';
			}

			$mac_dinh_giam = ( 'hang' !== $cot_sap );
			$huong_yc      = isset( $_GET['order'] ) ? strtolower( sanitize_key( wp_unslash( $_GET['order'] ) ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$huong         = in_array( $huong_yc, array( 'asc', 'desc' ), true ) ? $huong_yc : ( $mac_dinh_giam ? 'desc' : 'asc' );

			usort(
				$rows,
				static function ( array $a, array $b ) use ( $cot_sap, $huong ): int {
					$av = $a[ $cot_sap ] ?? 0;
					$bv = $b[ $cot_sap ] ?? 0;

					if ( 'gan_nhat' === $cot_sap ) {
						$av = $av ?? '';
						$bv = $bv ?? '';
					}

					if ( $av === $bv ) {
						return $a['user_id'] <=> $b['user_id'];
					}

					$ketqua = $av <=> $bv;
					return 'asc' === $huong ? $ketqua : -$ketqua;
				}
			);

			$tong      = count( $rows );
			$moi_trang = max( 1, (int) apply_filters( 'nntm_bxh_so_dong_moi_trang', 50 ) );
			$trang     = max( 1, (int) $this->get_pagenum() );

			$this->items = array_slice( $rows, ( $trang - 1 ) * $moi_trang, $moi_trang );

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

