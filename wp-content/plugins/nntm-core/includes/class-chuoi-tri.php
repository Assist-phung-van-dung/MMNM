<?php
/**
 * Nghiệp vụ Cộng Tu "chuỗi trì" — CPT nntm_program + bảng nntm_kpi_log.
 *
 * Bối cảnh nghiệp vụ chốt 14/08/2026 (xem docs/07-ban-giao.md mục "Đang làm
 * dở — Cộng Tu chuỗi trì"): mỗi thành viên giữ HAI dòng số độc lập, CHỈ
 * CỘNG THÊM, KHÔNG BAO GIỜ GHI ĐÈ —
 *   - CAM KẾT: cộng từ đăng ký ban đầu + cam kết thêm.
 *   - THỰC TẾ: cộng từ mỗi lần khai báo trong ngày (nhiều lần/ngày, cộng dồn).
 * Tiến trình = thực tế / cam kết. Vượt cam kết thì số thật KHÔNG bị cắt, việc
 * chặn thanh tiến độ ở 100% thuộc về giao diện, không phải ở đây.
 *
 * File này ở namespace gốc (không có `namespace ...;`), giống
 * includes/functions.php, để theme gọi thẳng `nntm_kpi_...()` không cần
 * tiền tố namespace.
 *
 * VÌ SAO Ở PLUGIN, KHÔNG Ở THEME: đúng docs/04-kien-truc.md mục 1 — dữ liệu
 * và nghiệp vụ Cộng Tu (Phase 2) ở plugin, đổi theme sau này không mất số
 * liệu chuỗi trì của thành viên.
 *
 * ⚠️ NGÀY GIỜ: dự án từng dính lỗi `gmdate()` làm bài viết lệch múi giờ rơi
 * vào trạng thái `future`. File này CHỈ dùng `current_time()` của WordPress
 * (tự áp dụng múi giờ site), không bao giờ dùng `gmdate()`/`date()` trần.
 *
 * @package NNTM_Core
 */

defined( 'ABSPATH' ) || exit;

/* =====================================================================
 * Hằng số & hàm nội bộ dùng chung trong file này.
 * ===================================================================== */

if ( ! defined( 'NNTM_KPI_METRIC_CAM_KET' ) ) {
	define( 'NNTM_KPI_METRIC_CAM_KET', 'cam_ket' );
}
if ( ! defined( 'NNTM_KPI_METRIC_THUC_HIEN' ) ) {
	define( 'NNTM_KPI_METRIC_THUC_HIEN', 'thuc_hien' );
}

if ( ! function_exists( 'nntm_kpi_ten_option_tong' ) ) {
	/**
	 * Tên option lưu đệm ba con số tổng của một chương trình.
	 * Cộng dồn ngay khi ghi (xem nntm_kpi_cong_don_dem()) để trang thống kê
	 * không phải quét lại cả bảng nntm_kpi_log mỗi lần tải trang.
	 *
	 * @param int $program_id ID chương trình.
	 */
	function nntm_kpi_ten_option_tong( int $program_id ): string {
		return 'nntm_kpi_tong_' . $program_id;
	}
}

if ( ! function_exists( 'nntm_kpi_ten_transient_bxh' ) ) {
	/**
	 * Tên transient cache BXH của một chương trình — chốt mỗi ngày (24 giờ).
	 *
	 * @param int $program_id ID chương trình.
	 */
	function nntm_kpi_ten_transient_bxh( int $program_id ): string {
		return 'nntm_kpi_bxh_' . $program_id;
	}
}

if ( ! function_exists( 'nntm_kpi_tinh_tien_trinh' ) ) {
	/**
	 * Tiến trình = thực tế / cam kết. Cam kết = 0 thì tiến trình = 0 (tránh
	 * chia cho 0). KHÔNG chặn ở 1.0 — vượt cam kết là việc thật, giao diện
	 * tự quyết định có chặn thanh hiển thị hay không.
	 *
	 * @param int $thuc_hien Tổng số chuỗi đã thực hiện.
	 * @param int $cam_ket   Tổng số chuỗi đã cam kết.
	 */
	function nntm_kpi_tinh_tien_trinh( int $thuc_hien, int $cam_ket ): float {
		if ( $cam_ket <= 0 ) {
			return 0.0;
		}
		return $thuc_hien / $cam_ket;
	}
}

/* =====================================================================
 * Chương trình (nntm_program).
 * ===================================================================== */

if ( ! function_exists( 'nntm_program_dang_mo' ) ) {
	/**
	 * Chương trình có đang mở nhận cam kết/khai báo hay không.
	 *
	 * true khi ĐỦ CẢ BỐN điều kiện:
	 *   1. Post tồn tại và đúng post_type nntm_program.
	 *   2. post_status = publish.
	 *   3. Post meta _nntm_program_dang_mo bật (công tắc ban quản trị).
	 *   4. Hôm nay (theo múi giờ site) nằm trong [bat_dau, ket_thuc]; ket_thuc
	 *      rỗng nghĩa là không giới hạn ngày kết thúc.
	 *
	 * @param int $program_id ID bài nntm_program.
	 */
	function nntm_program_dang_mo( int $program_id ): bool {
		if ( $program_id <= 0 ) {
			return false;
		}

		$post = get_post( $program_id );
		if ( ! ( $post instanceof WP_Post ) || 'nntm_program' !== $post->post_type || 'publish' !== $post->post_status ) {
			return false;
		}

		if ( ! (bool) get_post_meta( $program_id, '_nntm_program_dang_mo', true ) ) {
			return false;
		}

		$hom_nay   = current_time( 'Y-m-d' );
		$bat_dau   = (string) get_post_meta( $program_id, '_nntm_program_bat_dau', true );
		$ket_thuc  = (string) get_post_meta( $program_id, '_nntm_program_ket_thuc', true );

		if ( '' !== $bat_dau && $hom_nay < $bat_dau ) {
			return false;
		}

		if ( '' !== $ket_thuc && $hom_nay > $ket_thuc ) {
			return false;
		}

		return true;
	}
}

if ( ! function_exists( 'nntm_program_hien_tai' ) ) {
	/**
	 * Chương trình đang mở, mới nhất (theo ngày đăng). Không có thì null.
	 *
	 * Không lọc thẳng bằng meta_query _nntm_program_dang_mo vì công tắc bật
	 * chưa đủ — còn phải khớp khoảng ngày [bat_dau, ket_thuc], nên lấy một lô
	 * ứng viên đã bật công tắc rồi kiểm lại bằng nntm_program_dang_mo() cho
	 * chắc, số chương trình luôn rất ít nên không ảnh hưởng hiệu năng.
	 */
	function nntm_program_hien_tai(): ?WP_Post {
		$query = new WP_Query(
			array(
				'post_type'           => 'nntm_program',
				'post_status'         => 'publish',
				'posts_per_page'      => 50,
				'ignore_sticky_posts' => true,
				'no_found_rows'       => true,
				'orderby'             => array(
					'date' => 'DESC',
					'ID'   => 'DESC',
				),
				'meta_query'          => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- so chuong trinh rat it, khong anh huong hieu nang.
					array(
						'key'     => '_nntm_program_dang_mo',
						'value'   => '1',
						'compare' => '=',
					),
				),
			)
		);

		$hien_tai = null;
		foreach ( $query->posts as $post ) {
			if ( nntm_program_dang_mo( $post->ID ) ) {
				$hien_tai = $post;
				break;
			}
		}

		/**
		 * Cho phép nơi khác (ví dụ trang quản trị thử nghiệm) đổi chương
		 * trình hiện tại mà không cần sửa code.
		 *
		 * @param WP_Post|null $hien_tai Chương trình đang mở, mới nhất.
		 */
		return apply_filters( 'nntm_program_hien_tai', $hien_tai );
	}
}

/* =====================================================================
 * Kiểm tra đầu vào dùng chung cho hai hàm ghi dữ liệu.
 * ===================================================================== */

if ( ! function_exists( 'nntm_kpi_kiem_tra_dau_vao' ) ) {
	/**
	 * Kiểm tra đầu vào chung cho nntm_kpi_cam_ket() và nntm_kpi_ghi_nhan().
	 *
	 * @param int $program_id ID chương trình.
	 * @param int $user_id    ID thành viên.
	 * @param int $so_chuoi   Số chuỗi định ghi.
	 * @return true|WP_Error
	 */
	function nntm_kpi_kiem_tra_dau_vao( int $program_id, int $user_id, int $so_chuoi ) {
		if ( $user_id <= 0 || ! get_userdata( $user_id ) ) {
			return new WP_Error(
				'nguoi_dung_khong_hop_le',
				__( 'Không tìm thấy thành viên để ghi nhận.', 'nntm' )
			);
		}

		// BQT (manage_options) được ghi kể cả khi chương trình đã đóng —
		// dùng để chỉnh sửa/chữa dữ liệu khi cần.
		if ( ! current_user_can( 'manage_options' ) && ! nntm_program_dang_mo( $program_id ) ) {
			return new WP_Error(
				'chuong_trinh_da_dong',
				__( 'Chương trình trì tụng này hiện không mở nhận cam kết/khai báo.', 'nntm' )
			);
		}

		/**
		 * Trần số chuỗi tối đa cho MỘT lần ghi — chặn người gõ nhầm số quá
		 * lớn làm vỡ bảng xếp hạng.
		 *
		 * @param int $tran Trần mặc định.
		 */
		$tran = (int) apply_filters( 'nntm_kpi_tran_moi_lan', 100000 );

		if ( $so_chuoi <= 0 || $so_chuoi > $tran ) {
			return new WP_Error(
				'so_chuoi_khong_hop_le',
				sprintf(
					/* translators: %d: trần tối đa cho một lần ghi */
					__( 'Số chuỗi phải lớn hơn 0 và không vượt quá %d cho một lần ghi.', 'nntm' ),
					$tran
				)
			);
		}

		return true;
	}
}

/* =====================================================================
 * Bộ đếm đệm (option riêng theo chương trình) — cộng dồn ngay khi ghi.
 * ===================================================================== */

if ( ! function_exists( 'nntm_kpi_cong_don_dem' ) ) {
	/**
	 * Cộng dồn NGAY vào bộ đếm đệm của chương trình sau khi ghi thành công
	 * một dòng — tránh phải quét lại cả bảng nntm_kpi_log mỗi lần tải trang
	 * thống kê. Nếu đệm chưa từng có (option chưa tồn tại), tính lại từ đầu
	 * bằng nntm_kpi_tinh_lai_tong() — dòng vừa ghi đã có trong bảng nên số
	 * tính lại vẫn đúng, không cần cộng thêm lần nữa.
	 *
	 * @param int    $program_id  ID chương trình.
	 * @param string $metric      NNTM_KPI_METRIC_CAM_KET hoặc NNTM_KPI_METRIC_THUC_HIEN.
	 * @param int    $so_chuoi    Số chuỗi vừa ghi thêm.
	 * @param bool   $nguoi_moi   true nếu đây là dòng cam_ket ĐẦU TIÊN của người này
	 *                            trong chương trình (tăng so_nguoi thêm 1).
	 */
	function nntm_kpi_cong_don_dem( int $program_id, string $metric, int $so_chuoi, bool $nguoi_moi = false ): void {
		$ten_option = nntm_kpi_ten_option_tong( $program_id );
		$tong       = get_option( $ten_option, false );

		if ( false === $tong || ! is_array( $tong ) ) {
			// Chưa có đệm: dòng vừa ghi đã nằm trong bảng, quét lại là đủ,
			// không cộng thêm để tránh đếm dòng đó hai lần.
			nntm_kpi_tinh_lai_tong( $program_id );
			return;
		}

		$tong['cam_ket']   = (int) ( $tong['cam_ket'] ?? 0 );
		$tong['thuc_hien'] = (int) ( $tong['thuc_hien'] ?? 0 );
		$tong['so_nguoi']  = (int) ( $tong['so_nguoi'] ?? 0 );

		if ( NNTM_KPI_METRIC_CAM_KET === $metric ) {
			$tong['cam_ket'] += $so_chuoi;
			if ( $nguoi_moi ) {
				++$tong['so_nguoi'];
			}
		} else {
			$tong['thuc_hien'] += $so_chuoi;
		}

		update_option( $ten_option, $tong, false );
	}
}

/* =====================================================================
 * Ghi dữ liệu — hai hàm duy nhất được phép INSERT vào nntm_kpi_log.
 * ===================================================================== */

if ( ! function_exists( 'nntm_kpi_cam_ket' ) ) {
	/**
	 * Ghi MỘT dòng metric 'cam_ket'. Dùng chung cho đăng ký lần đầu
	 * (+100 chẳng hạn) lẫn "cam kết thêm" (+200) — luôn CỘNG THÊM, không ghi
	 * đè, đúng sơ đồ chủ dự án chốt.
	 *
	 * @param int $program_id ID chương trình.
	 * @param int $user_id    ID thành viên.
	 * @param int $so_chuoi   Số chuỗi cam kết thêm.
	 * @return bool|WP_Error
	 */
	function nntm_kpi_cam_ket( int $program_id, int $user_id, int $so_chuoi ) {
		$kiem = nntm_kpi_kiem_tra_dau_vao( $program_id, $user_id, $so_chuoi );
		if ( is_wp_error( $kiem ) ) {
			return $kiem;
		}

		global $wpdb;
		$table = \NNTM\Core\Schema::table( 'kpi_log' );

		// Xác định TRƯỚC khi ghi xem đây có phải lần cam kết đầu tiên của
		// người này hay không, để cộng đúng vào so_nguoi.
		$la_nguoi_moi = ! nntm_kpi_da_tham_gia( $program_id, $user_id );

		$ghi = $wpdb->insert(
			$table,
			array(
				'program_id' => $program_id,
				'user_id'    => $user_id,
				'log_date'   => current_time( 'Y-m-d' ),
				'metric'     => NNTM_KPI_METRIC_CAM_KET,
				'value'      => $so_chuoi,
				'created_at' => current_time( 'mysql' ),
			),
			array( '%d', '%d', '%s', '%s', '%d', '%s' )
		);

		if ( false === $ghi ) {
			return new WP_Error( 'loi_ghi_du_lieu', __( 'Không ghi được cam kết, vui lòng thử lại.', 'nntm' ) );
		}

		/*
		 * CỐ Ý KHÔNG xoá cache bảng xếp hạng ở đây.
		 *
		 * Chủ dự án chốt 14/08/2026: "BXH cập nhật mỗi ngày", ba con số
		 * thống kê thì tươi ngay. Xoá cache mỗi lần ghi sẽ biến BXH thành
		 * gần-realtime: mùa cao điểm hàng nghìn người khai báo liên tục thì
		 * cache bị huỷ suốt, mỗi lượt xem trang lại phải gộp + sắp xếp lại
		 * toàn bộ bảng — đúng cái mà bản chốt-mỗi-ngày sinh ra để tránh.
		 * Thứ hạng nhảy loạn trong lúc người ta đang nhìn cũng là trải
		 * nghiệm tệ.
		 *
		 * Người vừa khai báo VẪN thấy số của mình đổi ngay, vì
		 * nntm_kpi_tong_cua_nguoi() và nntm_kpi_hang_cua_nguoi() luôn truy
		 * vấn thẳng CSDL, không đọc cache này.
		 *
		 * Số đệm ba con số tổng (nntm_kpi_cong_don_dem ở trên) thì cộng dồn
		 * ngay — nên phần "thống kê tươi" không bị ảnh hưởng.
		 */
		nntm_kpi_cong_don_dem( $program_id, NNTM_KPI_METRIC_CAM_KET, $so_chuoi, $la_nguoi_moi );

		return true;
	}
}

if ( ! function_exists( 'nntm_kpi_ghi_nhan' ) ) {
	/**
	 * Ghi MỘT dòng metric 'thuc_hien' vào NGÀY HIỆN TẠI (theo múi giờ site).
	 * Không cho khai lùi ngày — mọi lần ghi đều rơi vào `current_time( 'Y-m-d' )`
	 * tại thời điểm gọi hàm, không nhận tham số ngày từ nơi gọi.
	 *
	 * @param int $program_id ID chương trình.
	 * @param int $user_id    ID thành viên.
	 * @param int $so_chuoi   Số chuỗi vừa thực hiện.
	 * @return bool|WP_Error
	 */
	function nntm_kpi_ghi_nhan( int $program_id, int $user_id, int $so_chuoi ) {
		$kiem = nntm_kpi_kiem_tra_dau_vao( $program_id, $user_id, $so_chuoi );
		if ( is_wp_error( $kiem ) ) {
			return $kiem;
		}

		global $wpdb;
		$table = \NNTM\Core\Schema::table( 'kpi_log' );

		$ghi = $wpdb->insert(
			$table,
			array(
				'program_id' => $program_id,
				'user_id'    => $user_id,
				'log_date'   => current_time( 'Y-m-d' ),
				'metric'     => NNTM_KPI_METRIC_THUC_HIEN,
				'value'      => $so_chuoi,
				'created_at' => current_time( 'mysql' ),
			),
			array( '%d', '%d', '%s', '%s', '%d', '%s' )
		);

		if ( false === $ghi ) {
			return new WP_Error( 'loi_ghi_du_lieu', __( 'Không ghi nhận được, vui lòng thử lại.', 'nntm' ) );
		}

		// Không xoá cache BXH — xem giải thích dài trong nntm_kpi_cam_ket().
		// BXH chốt mỗi ngày; số của chính người vừa ghi vẫn tươi vì
		// nntm_kpi_tong_cua_nguoi()/nntm_kpi_hang_cua_nguoi() truy vấn thẳng.
		nntm_kpi_cong_don_dem( $program_id, NNTM_KPI_METRIC_THUC_HIEN, $so_chuoi );

		return true;
	}
}

/* =====================================================================
 * Đọc dữ liệu — số của một người (LUÔN tính tươi, không cache).
 * ===================================================================== */

if ( ! function_exists( 'nntm_kpi_tong_cua_nguoi' ) ) {
	/**
	 * Tổng cam kết / thực hiện / tiến trình của MỘT người trong MỘT chương
	 * trình. Luôn truy vấn thẳng (không cache) để khai báo xong là thấy số
	 * mình đổi ngay trên trang.
	 *
	 * @param int $program_id ID chương trình.
	 * @param int $user_id    ID thành viên.
	 * @return array{cam_ket:int,thuc_hien:int,tien_trinh:float}
	 */
	function nntm_kpi_tong_cua_nguoi( int $program_id, int $user_id ): array {
		global $wpdb;
		$table = \NNTM\Core\Schema::table( 'kpi_log' );

		$hang = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT
					SUM( CASE WHEN metric = %s THEN value ELSE 0 END ) AS cam_ket,
					SUM( CASE WHEN metric = %s THEN value ELSE 0 END ) AS thuc_hien
				FROM {$table}
				WHERE program_id = %d AND user_id = %d",
				NNTM_KPI_METRIC_CAM_KET,
				NNTM_KPI_METRIC_THUC_HIEN,
				$program_id,
				$user_id
			),
			ARRAY_A
		);

		$cam_ket   = (int) ( $hang['cam_ket'] ?? 0 );
		$thuc_hien = (int) ( $hang['thuc_hien'] ?? 0 );

		return array(
			'cam_ket'    => $cam_ket,
			'thuc_hien'  => $thuc_hien,
			'tien_trinh' => nntm_kpi_tinh_tien_trinh( $thuc_hien, $cam_ket ),
		);
	}
}

if ( ! function_exists( 'nntm_kpi_da_tham_gia' ) ) {
	/**
	 * Người này đã có ít nhất một dòng 'cam_ket' trong chương trình chưa —
	 * giao diện dựa vào đây để hiện nút "Tham gia" hay "Cam kết thêm".
	 *
	 * @param int $program_id ID chương trình.
	 * @param int $user_id    ID thành viên.
	 */
	function nntm_kpi_da_tham_gia( int $program_id, int $user_id ): bool {
		global $wpdb;
		$table = \NNTM\Core\Schema::table( 'kpi_log' );

		$dem = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$table} WHERE program_id = %d AND user_id = %d AND metric = %s LIMIT 1",
				$program_id,
				$user_id,
				NNTM_KPI_METRIC_CAM_KET
			)
		);

		return ( (int) $dem ) > 0;
	}
}

if ( ! function_exists( 'nntm_kpi_ghi_hom_nay' ) ) {
	/**
	 * Tổng 'thuc_hien' đã ghi trong NGÀY HÔM NAY (theo múi giờ site) của
	 * một người trong một chương trình.
	 *
	 * @param int $program_id ID chương trình.
	 * @param int $user_id    ID thành viên.
	 */
	function nntm_kpi_ghi_hom_nay( int $program_id, int $user_id ): int {
		global $wpdb;
		$table = \NNTM\Core\Schema::table( 'kpi_log' );

		$tong = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT SUM( value ) FROM {$table}
				WHERE program_id = %d AND user_id = %d AND metric = %s AND log_date = %s",
				$program_id,
				$user_id,
				NNTM_KPI_METRIC_THUC_HIEN,
				current_time( 'Y-m-d' )
			)
		);

		return (int) $tong;
	}
}

if ( ! function_exists( 'nntm_kpi_hang_cua_nguoi' ) ) {
	/**
	 * Thứ hạng THẬT của một người, kể cả khi rơi ngoài giới hạn $limit của
	 * nntm_kpi_bang_xep_hang(). Chưa tham gia thì null. Luôn truy vấn thẳng
	 * (không cache) — cùng lý do với nntm_kpi_tong_cua_nguoi().
	 *
	 * Xếp hạng theo thuc_hien giảm dần: hạng = 1 + số người có thuc_hien
	 * NHIỀU HƠN người này (kiểu "competition ranking" — người bằng điểm
	 * nhau thì cùng hạng).
	 *
	 * @param int $program_id ID chương trình.
	 * @param int $user_id    ID thành viên.
	 */
	function nntm_kpi_hang_cua_nguoi( int $program_id, int $user_id ): ?int {
		if ( ! nntm_kpi_da_tham_gia( $program_id, $user_id ) ) {
			return null;
		}

		global $wpdb;
		$table = \NNTM\Core\Schema::table( 'kpi_log' );

		$tong_cua_toi = nntm_kpi_tong_cua_nguoi( $program_id, $user_id );

		$so_nguoi_cao_hon = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM (
					SELECT user_id, SUM( CASE WHEN metric = %s THEN value ELSE 0 END ) AS thuc_hien
					FROM {$table}
					WHERE program_id = %d
					GROUP BY user_id
					HAVING thuc_hien > %d
				) t",
				NNTM_KPI_METRIC_THUC_HIEN,
				$program_id,
				$tong_cua_toi['thuc_hien']
			)
		);

		return ( (int) $so_nguoi_cao_hon ) + 1;
	}
}

/* =====================================================================
 * Đọc dữ liệu — tổng chương trình (đệm) + BXH (transient 24 giờ).
 * ===================================================================== */

if ( ! function_exists( 'nntm_kpi_tong_chuong_trinh' ) ) {
	/**
	 * Ba (bốn) con số tổng của cả chương trình: cam_ket, thuc_hien, so_nguoi,
	 * tien_trinh. Đọc từ option đệm (không quét lại bảng mỗi lần tải trang);
	 * option chưa từng có thì tính lại một lần và lưu đệm.
	 *
	 * @param int $program_id ID chương trình.
	 * @return array{cam_ket:int,thuc_hien:int,so_nguoi:int,tien_trinh:float}
	 */
	function nntm_kpi_tong_chuong_trinh( int $program_id ): array {
		$tong = get_option( nntm_kpi_ten_option_tong( $program_id ), false );

		if ( false === $tong || ! is_array( $tong ) ) {
			$tong = nntm_kpi_tinh_lai_tong( $program_id );
		}

		$cam_ket   = (int) ( $tong['cam_ket'] ?? 0 );
		$thuc_hien = (int) ( $tong['thuc_hien'] ?? 0 );
		$so_nguoi  = (int) ( $tong['so_nguoi'] ?? 0 );

		return array(
			'cam_ket'    => $cam_ket,
			'thuc_hien'  => $thuc_hien,
			'so_nguoi'   => $so_nguoi,
			'tien_trinh' => nntm_kpi_tinh_tien_trinh( $thuc_hien, $cam_ket ),
		);
	}
}

if ( ! function_exists( 'nntm_kpi_tinh_lai_tong' ) ) {
	/**
	 * Quét lại TOÀN BỘ bảng nntm_kpi_log của một chương trình để tính lại ba
	 * con số tổng — dùng để chữa cháy khi số đệm lệch (ví dụ ban quản trị
	 * sửa tay dữ liệu trực tiếp trong CSDL). Luôn lưu lại vào option đệm để
	 * lần đọc kế tiếp không phải quét lại.
	 *
	 * @param int $program_id ID chương trình.
	 * @return array{cam_ket:int,thuc_hien:int,so_nguoi:int}
	 */
	function nntm_kpi_tinh_lai_tong( int $program_id ): array {
		global $wpdb;
		$table = \NNTM\Core\Schema::table( 'kpi_log' );

		$hang = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT
					SUM( CASE WHEN metric = %s THEN value ELSE 0 END ) AS cam_ket,
					SUM( CASE WHEN metric = %s THEN value ELSE 0 END ) AS thuc_hien,
					COUNT( DISTINCT CASE WHEN metric = %s THEN user_id ELSE NULL END ) AS so_nguoi
				FROM {$table}
				WHERE program_id = %d",
				NNTM_KPI_METRIC_CAM_KET,
				NNTM_KPI_METRIC_THUC_HIEN,
				NNTM_KPI_METRIC_CAM_KET,
				$program_id
			),
			ARRAY_A
		);

		$tong = array(
			'cam_ket'   => (int) ( $hang['cam_ket'] ?? 0 ),
			'thuc_hien' => (int) ( $hang['thuc_hien'] ?? 0 ),
			'so_nguoi'  => (int) ( $hang['so_nguoi'] ?? 0 ),
		);

		update_option( nntm_kpi_ten_option_tong( $program_id ), $tong, false );

		return $tong;
	}
}

if ( ! function_exists( 'nntm_kpi_phap_danh_va_vung_mien' ) ) {
	/**
	 * Lấy pháp danh + nhãn vùng miền cho một danh sách user_id bằng MỘT
	 * truy vấn gộp (get_users() với 'include' primes cả cache user object
	 * lẫn user meta qua cache_users()/update_meta_cache() của lõi WordPress)
	 * — tuyệt đối không gọi get_user_meta() trong vòng lặp (N+1).
	 *
	 * Pháp danh: user meta `nntm_phap_danh` → rơi về `display_name`.
	 * Vùng miền: user meta `nntm_vung_mien`, đổi nhãn qua
	 * `nntm_vung_mien_options()` của theme nếu hàm đó tồn tại, không thì trả
	 * thẳng khóa.
	 *
	 * @param int[] $user_ids Danh sách ID thành viên.
	 * @return array<int,array{phap_danh:string,vung_mien:string}> Khóa theo user_id.
	 */
	function nntm_kpi_phap_danh_va_vung_mien( array $user_ids ): array {
		$user_ids = array_values( array_unique( array_filter( array_map( 'absint', $user_ids ) ) ) );
		$ket_qua  = array();

		if ( empty( $user_ids ) ) {
			return $ket_qua;
		}

		// MỘT truy vấn gộp: nạp cả object user lẫn mồi cache user meta cho
		// toàn bộ danh sách, để get_user_meta() bên dưới chạy từ cache.
		$users    = get_users(
			array(
				'include' => $user_ids,
				'fields'  => 'all',
			)
		);
		$theo_id  = array();
		foreach ( $users as $user ) {
			$theo_id[ $user->ID ] = $user;
		}

		$tuy_chon_vung_mien = function_exists( 'nntm_vung_mien_options' ) ? nntm_vung_mien_options() : array();

		foreach ( $user_ids as $user_id ) {
			$user = $theo_id[ $user_id ] ?? null;

			$phap_danh = trim( (string) get_user_meta( $user_id, 'nntm_phap_danh', true ) );
			if ( '' === $phap_danh ) {
				$phap_danh = $user ? $user->display_name : '';
			}

			$vung_mien_key = (string) get_user_meta( $user_id, 'nntm_vung_mien', true );
			$vung_mien     = $vung_mien_key;
			if ( '' !== $vung_mien_key && is_array( $tuy_chon_vung_mien ) && isset( $tuy_chon_vung_mien[ $vung_mien_key ] ) ) {
				$vung_mien = $tuy_chon_vung_mien[ $vung_mien_key ];
			}

			$ket_qua[ $user_id ] = array(
				'phap_danh' => $phap_danh,
				'vung_mien' => $vung_mien,
			);
		}

		return $ket_qua;
	}
}

if ( ! function_exists( 'nntm_kpi_bang_xep_hang' ) ) {
	/**
	 * Bảng xếp hạng của một chương trình, xếp GIẢM DẦN theo thuc_hien.
	 * Chốt mỗi ngày — cache transient 24 giờ theo chương trình (không cần
	 * tươi từng giây, chủ dự án đã chốt "BXH chốt mỗi ngày").
	 *
	 * @param int $program_id ID chương trình.
	 * @param int $limit      Số dòng tối đa trả về. Mặc định 200.
	 * @return array<int,array{hang:int,user_id:int,phap_danh:string,vung_mien:string,avatar:string,thuc_hien:int,cam_ket:int,tien_trinh:float}>
	 */
	function nntm_kpi_bang_xep_hang( int $program_id, int $limit = 200 ): array {
		$limit = max( 1, $limit );
		$khoa  = nntm_kpi_ten_transient_bxh( $program_id ) . '_' . $limit;

		$cache = get_transient( $khoa );
		if ( is_array( $cache ) ) {
			return $cache;
		}

		global $wpdb;
		$table = \NNTM\Core\Schema::table( 'kpi_log' );

		$dong = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT
					user_id,
					SUM( CASE WHEN metric = %s THEN value ELSE 0 END ) AS cam_ket,
					SUM( CASE WHEN metric = %s THEN value ELSE 0 END ) AS thuc_hien
				FROM {$table}
				WHERE program_id = %d
				GROUP BY user_id
				ORDER BY thuc_hien DESC, user_id ASC
				LIMIT %d",
				NNTM_KPI_METRIC_CAM_KET,
				NNTM_KPI_METRIC_THUC_HIEN,
				$program_id,
				$limit
			),
			ARRAY_A
		);

		$user_ids  = wp_list_pluck( $dong, 'user_id' );
		$thong_tin = nntm_kpi_phap_danh_va_vung_mien( $user_ids );

		$bang = array();
		$hang = 0;
		foreach ( $dong as $dong_du_lieu ) {
			++$hang;
			$user_id   = (int) $dong_du_lieu['user_id'];
			$cam_ket   = (int) $dong_du_lieu['cam_ket'];
			$thuc_hien = (int) $dong_du_lieu['thuc_hien'];

			$bang[] = array(
				'hang'       => $hang,
				'user_id'    => $user_id,
				'phap_danh'  => $thong_tin[ $user_id ]['phap_danh'] ?? '',
				'vung_mien'  => $thong_tin[ $user_id ]['vung_mien'] ?? '',
				'avatar'     => get_avatar_url( $user_id ),
				'thuc_hien'  => $thuc_hien,
				'cam_ket'    => $cam_ket,
				'tien_trinh' => nntm_kpi_tinh_tien_trinh( $thuc_hien, $cam_ket ),
			);
		}

		set_transient( $khoa, $bang, DAY_IN_SECONDS );

		return $bang;
	}
}
