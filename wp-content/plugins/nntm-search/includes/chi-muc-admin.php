<?php
/**
 * Công cụ → Chỉ mục tìm kiếm: xem tình trạng + lập chỉ mục kho nội dung.
 *
 * Khách tự vận hành, không có người kỹ thuật (khảo sát câu 37, 39) — mọi việc
 * phải làm được bằng nút bấm, và màn hình phải NÓI RÕ đang thiếu gì: bao nhiêu
 * ảnh chưa có chỉ mục, bao nhiêu ảnh chưa gắn được vào bài nào, dịch vụ Python
 * có sống không, OCR có sẵn sàng không.
 *
 * @package NNTM_Search
 */

defined( 'ABSPATH' ) || exit;

const NNTM_SEARCH_CM_TRANG = 'nntm-chi-muc-tim-kiem';

add_action(
	'admin_menu',
	static function (): void {
		add_management_page(
			__( 'Chỉ mục tìm kiếm', 'nntm' ),
			__( 'Chỉ mục tìm kiếm', 'nntm' ),
			'manage_options',
			NNTM_SEARCH_CM_TRANG,
			'nntm_search_cm_ve_trang'
		);
	}
);

/**
 * Tình trạng dịch vụ Python, nhớ 60 giây (mỗi lần mở trang không gọi lại).
 *
 * @return array{song:bool,ocr:?array}
 */
function nntm_search_cm_trang_thai_dich_vu( bool $lam_moi = false ): array {
	$dem = get_transient( 'nntm_search_cm_dich_vu' );
	if ( ! $lam_moi && is_array( $dem ) ) {
		return $dem;
	}

	$song = wp_remote_get( nntm_search_service_url() . '/khoe', array( 'timeout' => 3 ) );
	$ocr  = wp_remote_get( nntm_search_service_url() . '/ocr/khoe', array( 'timeout' => 10 ) );

	$kq = array(
		'song' => ! is_wp_error( $song ) && 200 === (int) wp_remote_retrieve_response_code( $song ),
		'ocr'  => is_wp_error( $ocr ) ? null : json_decode( (string) wp_remote_retrieve_body( $ocr ), true ),
	);

	set_transient( 'nntm_search_cm_dich_vu', $kq, MINUTE_IN_SECONDS );

	return $kq;
}

function nntm_search_cm_url( string $viec ): string {
	return wp_nonce_url( add_query_arg( array( 'action' => 'nntm_cm', 'viec' => $viec ), admin_url( 'admin-post.php' ) ), 'nntm_cm_' . $viec );
}

add_action(
	'admin_post_nntm_cm',
	static function (): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Bạn không có quyền.', 'nntm' ), 403 );
		}

		$viec = isset( $_GET['viec'] ) ? sanitize_key( wp_unslash( $_GET['viec'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- kiểm ngay dưới.
		check_admin_referer( 'nntm_cm_' . $viec );

		$so = 0;
		switch ( $viec ) {
			case 'thieu':
			case 'quyen':
			case 'tat_ca':
				delete_option( NNTM_SEARCH_CM_DUNG );
				$so = nntm_search_cm_xep_hang_loat( $viec );
				break;
			case 'chay':
				// Cho trường hợp WP-Cron không chạy (site ít người vào, cron hệ
				// thống chưa đặt): BQT bấm để xử lý ngay một lượt ~25 giây.
				delete_option( NNTM_SEARCH_CM_DUNG );
				$so = nntm_search_cm_chay_lo( 25 );
				break;
			case 'dung':
				$so = nntm_search_cm_thong_ke()['cho'];
				// delete_all = true: xoá cho mọi file VÀ dọn cache meta từng file —
				// xoá bằng SQL trần thì Redis (có trong báo giá VPS) giữ bản cũ.
				delete_metadata( 'post', 0, NNTM_SEARCH_CM_CHO, '', true );
				wp_clear_scheduled_hook( NNTM_SEARCH_CM_CRON );
				break;
			case 'kiem_dv':
				nntm_search_cm_trang_thai_dich_vu( true );
				break;
		}

		wp_safe_redirect( add_query_arg( array( 'page' => NNTM_SEARCH_CM_TRANG, 'xong' => $viec, 'so' => $so ), admin_url( 'tools.php' ) ) );
		exit;
	}
);

function nntm_search_cm_ve_trang(): void {
	global $wpdb;

	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$tk   = nntm_search_cm_thong_ke();
	$dv   = nntm_search_cm_trang_thai_dich_vu();
	$dung = nntm_search_cm_dang_tam_dung();
	$toi  = wp_next_scheduled( NNTM_SEARCH_CM_CRON );

	// phpcs:disable WordPress.Security.NonceVerification.Recommended -- chỉ đọc để hiện thông báo.
	$xong = isset( $_GET['xong'] ) ? sanitize_key( wp_unslash( $_GET['xong'] ) ) : '';
	$so   = isset( $_GET['so'] ) ? absint( $_GET['so'] ) : 0;
	// phpcs:enable

	$thong_bao = array(
		'thieu'   => sprintf( __( 'Đã xếp hàng %d file còn thiếu chỉ mục.', 'nntm' ), $so ),
		'quyen'   => sprintf( __( 'Đã xếp hàng %d file để gắn lại bài chứa và quyền xem.', 'nntm' ), $so ),
		'tat_ca'  => sprintf( __( 'Đã xếp hàng %d file để lập chỉ mục lại từ đầu.', 'nntm' ), $so ),
		'chay'    => sprintf( __( 'Đã xử lý %d file.', 'nntm' ), $so ),
		'dung'    => sprintf( __( 'Đã bỏ %d file khỏi hàng đợi.', 'nntm' ), $so ),
		'kiem_dv' => __( 'Đã kiểm tra lại dịch vụ.', 'nntm' ),
	);

	$ocr      = is_array( $dv['ocr'] ) ? $dv['ocr'] : null;
	$loi_gan  = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->prepare(
			"SELECT m.post_id, m.meta_value FROM {$wpdb->postmeta} m WHERE m.meta_key = %s AND NOT EXISTS ( SELECT 1 FROM {$wpdb->postmeta} c WHERE c.post_id = m.post_id AND c.meta_key = %s ) ORDER BY m.post_id DESC LIMIT 20",
			NNTM_SEARCH_CM_LOI,
			NNTM_SEARCH_CM_CHO
		)
	);

	$dong = static function ( string $nhan, string $gia_tri, string $ghi_chu = '', bool $canh_bao = false ): void {
		printf(
			'<tr><th style="width:280px">%1$s</th><td><strong%4$s>%2$s</strong>%3$s</td></tr>',
			esc_html( $nhan ),
			esc_html( $gia_tri ),
			'' !== $ghi_chu ? ' <span class="description">— ' . esc_html( $ghi_chu ) . '</span>' : '',
			$canh_bao ? ' style="color:#b32d2e"' : ''
		);
	};
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Chỉ mục tìm kiếm', 'nntm' ); ?></h1>
		<p class="description"><?php esc_html_e( 'Tìm bằng hình ảnh và tìm trong nội dung PDF chỉ thấy được những file đã có chỉ mục. File mới tải lên tự vào hàng đợi; trang này dùng để lập chỉ mục kho có sẵn và theo dõi tiến độ.', 'nntm' ); ?></p>

		<?php if ( isset( $thong_bao[ $xong ] ) ) : ?>
			<div class="notice notice-success is-dismissible"><p><?php echo esc_html( $thong_bao[ $xong ] ); ?></p></div>
		<?php endif; ?>
		<?php if ( $dung ) : ?>
			<div class="notice notice-warning"><p><strong><?php esc_html_e( 'Hàng đợi đang tạm dừng:', 'nntm' ); ?></strong> <?php echo esc_html( $dung['ly_do'] . ' ' . sprintf( __( 'Tự thử lại lúc %s.', 'nntm' ), wp_date( 'H:i', (int) $dung['den'] ) ) ); ?></p></div>
		<?php endif; ?>

		<h2><?php esc_html_e( 'Dịch vụ Python', 'nntm' ); ?></h2>
		<table class="widefat striped" style="max-width:900px"><tbody>
			<?php
			$dong( __( 'Địa chỉ', 'nntm' ), nntm_search_service_url() );
			$dong( __( 'Trạng thái', 'nntm' ), $dv['song'] ? __( 'Đang chạy', 'nntm' ) : __( 'KHÔNG PHẢN HỒI', 'nntm' ), $dv['song'] ? '' : __( 'mọi việc lập chỉ mục sẽ chờ tới khi dịch vụ chạy lại', 'nntm' ), ! $dv['song'] );
			$dong(
				__( 'OCR PDF scan', 'nntm' ),
				$ocr && ! empty( $ocr['san_sang'] ) ? __( 'Sẵn sàng', 'nntm' ) . ' (Tesseract ' . ( $ocr['phien_ban'] ?? '?' ) . ')' : __( 'Chưa sẵn sàng', 'nntm' ),
				$ocr && empty( $ocr['san_sang'] ) ? (string) ( $ocr['ly_do'] ?? '' ) : '',
				! ( $ocr && ! empty( $ocr['san_sang'] ) )
			);
			?>
		</tbody></table>
		<p><a class="button" href="<?php echo esc_url( nntm_search_cm_url( 'kiem_dv' ) ); ?>"><?php esc_html_e( 'Kiểm tra lại dịch vụ', 'nntm' ); ?></a></p>

		<h2><?php esc_html_e( 'Tình trạng', 'nntm' ); ?></h2>
		<table class="widefat striped" style="max-width:900px"><tbody>
			<?php
			if ( nntm_search_image_enabled() ) {
				$thieu = max( 0, $tk['anh_tong'] - $tk['anh_co'] );
				$dong( __( 'Ảnh có chỉ mục', 'nntm' ), sprintf( '%d / %d', $tk['anh_co'], $tk['anh_tong'] ), $thieu ? sprintf( __( 'thiếu %d ảnh', 'nntm' ), $thieu ) : __( 'đủ', 'nntm' ), $thieu > 0 );
				$dong( __( 'Ảnh chưa gắn được vào bài nào', 'nntm' ), (string) $tk['anh_mo_coi'], __( 'tìm bằng ảnh không dẫn tới bài được — bấm "Gắn lại bài chứa" sau khi nhập nội dung', 'nntm' ), $tk['anh_mo_coi'] > 0 );
			} else {
				$dong( __( 'Tìm bằng hình ảnh', 'nntm' ), __( 'Đang tắt', 'nntm' ), 'NNTM_SEARCH_IMAGE_ENABLED' );
			}

			if ( nntm_search_pdf_enabled() ) {
				$thieu = max( 0, $tk['pdf_tong'] - $tk['pdf_co'] );
				$dong( __( 'PDF có chỉ mục', 'nntm' ), sprintf( '%d / %d', $tk['pdf_co'], $tk['pdf_tong'] ), sprintf( __( '%d trang', 'nntm' ), $tk['pdf_trang'] ) . ( $thieu ? ' · ' . sprintf( __( 'thiếu %d file', 'nntm' ), $thieu ) : '' ), $thieu > 0 );
				$dong( __( 'PDF chưa gắn vào ấn phẩm nào', 'nntm' ), (string) $tk['pdf_mo_coi'], __( 'kết quả tìm hiện tên file thay vì tên ấn phẩm', 'nntm' ), $tk['pdf_mo_coi'] > 0 );
				$dong( __( 'Trang PDF scan chờ OCR', 'nntm' ), (string) $tk['ocr_cho'], __( 'xem cột "Chỉ mục tìm kiếm" trong Thư viện Media', 'nntm' ) );
			} else {
				$dong( __( 'Tìm trong PDF', 'nntm' ), __( 'Đang tắt', 'nntm' ), 'NNTM_SEARCH_PDF_ENABLED' );
			}

			$dong( __( 'Hàng đợi', 'nntm' ), sprintf( __( '%d file', 'nntm' ), $tk['cho'] ), $tk['cho'] ? ( $toi ? sprintf( __( 'lượt kế tiếp %s', 'nntm' ), wp_date( 'H:i:s', $toi ) ) : __( 'chưa có lịch chạy', 'nntm' ) ) : '' );
			$dong( __( 'File lỗi (đã thử 3 lần)', 'nntm' ), (string) $tk['loi'], '', $tk['loi'] > 0 );
			?>
		</tbody></table>

		<h2><?php esc_html_e( 'Thao tác', 'nntm' ); ?></h2>
		<p>
			<a class="button button-primary" href="<?php echo esc_url( nntm_search_cm_url( 'thieu' ) ); ?>"><?php esc_html_e( 'Lập chỉ mục phần còn thiếu', 'nntm' ); ?></a>
			<a class="button" href="<?php echo esc_url( nntm_search_cm_url( 'quyen' ) ); ?>"><?php esc_html_e( 'Gắn lại bài chứa & quyền xem', 'nntm' ); ?></a>
			<a class="button" href="<?php echo esc_url( nntm_search_cm_url( 'chay' ) ); ?>"><?php esc_html_e( 'Xử lý hàng đợi ngay', 'nntm' ); ?></a>
		</p>
		<p>
			<a class="button" href="<?php echo esc_url( nntm_search_cm_url( 'tat_ca' ) ); ?>" onclick="return confirm(<?php echo esc_attr( wp_json_encode( __( 'Lập chỉ mục lại TOÀN BỘ ảnh và PDF? Kho lớn có thể mất vài giờ. Tìm kiếm vẫn chạy bằng chỉ mục cũ trong lúc chờ.', 'nntm' ) ) ); ?>);"><?php esc_html_e( 'Lập chỉ mục lại toàn bộ', 'nntm' ); ?></a>
			<a class="button button-link-delete" href="<?php echo esc_url( nntm_search_cm_url( 'dung' ) ); ?>" onclick="return confirm(<?php echo esc_attr( wp_json_encode( __( 'Bỏ hết file đang chờ khỏi hàng đợi?', 'nntm' ) ) ); ?>);"><?php esc_html_e( 'Dừng & xoá hàng đợi', 'nntm' ); ?></a>
		</p>
		<p class="description">
			<?php esc_html_e( '"Gắn lại bài chứa" không gọi dịch vụ Python, chạy nhanh — nên bấm sau mỗi đợt nhập nội dung lớn. Lưu một bài thì ảnh/PDF của bài đó tự được gắn lại.', 'nntm' ); ?>
			<?php esc_html_e( 'Kho lớn nên chạy bằng dòng lệnh: tools/lap-chi-muc.php --thieu --chay', 'nntm' ); ?>
		</p>

		<?php if ( $loi_gan ) : ?>
			<h2><?php esc_html_e( 'File lỗi gần đây', 'nntm' ); ?></h2>
			<table class="widefat striped" style="max-width:900px">
				<thead><tr><th><?php esc_html_e( 'File', 'nntm' ); ?></th><th><?php esc_html_e( 'Lỗi', 'nntm' ); ?></th></tr></thead>
				<tbody>
				<?php foreach ( $loi_gan as $l ) : ?>
					<tr>
						<td><a href="<?php echo esc_url( (string) get_edit_post_link( (int) $l->post_id ) ); ?>"><?php echo esc_html( get_the_title( (int) $l->post_id ) ?: '#' . $l->post_id ); ?></a></td>
						<td><?php echo esc_html( (string) $l->meta_value ); ?></td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
		<?php endif; ?>
	</div>
	<?php
}
