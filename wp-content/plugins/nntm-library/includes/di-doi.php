<?php
/**
 * Di dời tệp PDF từ uploads vào kho riêng.
 *
 * Chạy được nhiều lần: tệp nào đã ở trong kho thì bỏ qua, nên bấm nhầm hai lần
 * cũng không hỏng gì.
 *
 * KHÔNG ghi đường dẫn tuyệt đối vào _wp_attached_file. Bản ghi vẫn ở dạng
 * tương đối "2026/08/a.pdf" như mọi tệp khác, chỉ thêm cờ NNTM_LIB_META_RIENG;
 * bộ lọc get_attached_file trong kho-rieng.php lo việc đổi gốc. Lý do đầy đủ
 * nằm ở chỗ khai báo hằng đó — tóm tắt: update_post_meta() nuốt dấu chéo ngược
 * của đường dẫn Windows, và đường dẫn tuyệt đối trói CSDL vào một máy.
 *
 * Việc ghi lại đường dẫn tương đối ở đây còn CHỮA luôn dữ liệu cũ: bản ghi #463
 * đang chứa đường dẫn của một máy khác ("C:/xampp8_2/htdocs/NNTM/...").
 *
 * @package NNTM_Library
 */

defined( 'ABSPATH' ) || exit;

/**
 * Danh sách tệp PDF đang được ấn phẩm sử dụng.
 *
 * Một tệp có thể được nhiều ấn phẩm dùng chung nên phải lọc trùng, nếu không
 * lần chạy thứ hai sẽ đi tìm tệp đã dời và báo lỗi giả.
 *
 * @return int[] ID tệp đính kèm.
 */
function nntm_lib_danh_sach_pdf(): array {
	global $wpdb;

	$ids = $wpdb->get_col(
		"SELECT DISTINCT meta_value FROM {$wpdb->postmeta} WHERE meta_key = '_nntm_pdf_file' AND meta_value <> ''"
	);

	$ra = array();

	foreach ( $ids as $id ) {
		$id = absint( $id );

		if ( $id > 0 ) {
			$ra[] = $id;
		}
	}

	return array_values( array_unique( $ra ) );
}

/**
 * Đường dẫn thật của một tệp đính kèm, thử mọi khả năng.
 *
 * Cần hàm riêng vì dữ liệu cũ có bản ghi _wp_attached_file chứa đường dẫn tuyệt
 * đối của MÁY KHÁC (ví dụ "C:/xampp8_2/htdocs/..."). get_attached_file() gặp
 * loại đó sẽ ghép thêm uploads vào đầu và ra một đường dẫn không tồn tại.
 *
 * @param int $attachment_id ID tệp đính kèm.
 * @return string Đường dẫn đọc được, hoặc '' nếu không tìm ra.
 */
function nntm_lib_duong_dan_that( int $attachment_id ): string {
	$luu = (string) get_post_meta( $attachment_id, '_wp_attached_file', true );

	if ( '' === $luu ) {
		return '';
	}

	$con     = nntm_lib_duong_dan_tuong_doi( $attachment_id );
	$uploads = wp_get_upload_dir();
	$doi     = static function ( string $p ): string {
		return str_replace( '/', DIRECTORY_SEPARATOR, $p );
	};

	$thu = array();

	// Đã dời rồi thì tìm trong kho trước.
	if ( '' !== $con ) {
		$thu[] = nntm_lib_duong_dan_kho() . DIRECTORY_SEPARATOR . $doi( $con );
		$thu[] = $uploads['basedir'] . DIRECTORY_SEPARATOR . $doi( $con );
	}

	$duong = get_attached_file( $attachment_id );
	if ( $duong ) {
		$thu[] = $duong;
	}

	$thu[] = $luu;

	foreach ( $thu as $t ) {
		if ( $t && is_readable( $t ) && ! is_dir( $t ) ) {
			return $t;
		}
	}

	return '';
}

/**
 * Dời một tệp vào kho riêng và cập nhật bản ghi.
 *
 * @param int  $attachment_id ID tệp đính kèm.
 * @param bool $that          false thì chỉ thử, không đụng vào tệp nào.
 * @return array{id:int, ket_qua:string, chi_tiet:string}
 */
function nntm_lib_di_doi_mot( int $attachment_id, bool $that = false ): array {
	$ket = static function ( string $ket_qua, string $chi_tiet ) use ( $attachment_id ): array {
		return array(
			'id'       => $attachment_id,
			'ket_qua'  => $ket_qua,
			'chi_tiet' => $chi_tiet,
		);
	};

	if ( 'attachment' !== get_post_type( $attachment_id ) ) {
		return $ket( 'bo_qua', 'Không phải tệp đính kèm.' );
	}

	if ( nntm_lib_dang_o_kho_rieng( $attachment_id ) ) {
		return $ket( 'da_xong', 'Đã nằm trong kho riêng.' );
	}

	$nguon = nntm_lib_duong_dan_that( $attachment_id );

	if ( '' === $nguon ) {
		return $ket( 'hong', 'Không tìm thấy tệp trên đĩa (bản ghi _wp_attached_file rỗng hoặc trỏ sai).' );
	}

	if ( ! nntm_lib_dung_kho_rieng() ) {
		return $ket( 'hong', 'Không tạo được thư mục kho riêng.' );
	}

	/*
	 * Giữ lại cấu trúc năm/tháng để tên tệp trùng nhau ở hai tháng khác nhau
	 * không đè lên nhau.
	 */
	$con = nntm_lib_duong_dan_tuong_doi( $attachment_id );

	if ( '' === $con ) {
		return $ket( 'hong', 'Không suy ra được đường dẫn tương đối.' );
	}

	$dich = nntm_lib_duong_dan_kho() . DIRECTORY_SEPARATOR . str_replace( '/', DIRECTORY_SEPARATOR, $con );

	if ( ! $that ) {
		return $ket( 'se_doi', $nguon . '  →  ' . $dich );
	}

	if ( $nguon !== $dich ) {
		if ( ! wp_mkdir_p( dirname( $dich ) ) ) {
			return $ket( 'hong', 'Không tạo được thư mục ' . dirname( $dich ) );
		}

		if ( ! @rename( $nguon, $dich ) ) { // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, WordPress.WP.AlternativeFunctions.rename_rename -- doi cho tep, WP_Filesystem khong lam viec nay gon hon.
			if ( ! @copy( $nguon, $dich ) ) { // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, WordPress.WP.AlternativeFunctions.file_system_operations_copy
				return $ket( 'hong', 'Không chuyển được tệp sang ' . $dich );
			}

			@unlink( $nguon ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, WordPress.WP.AlternativeFunctions.file_system_operations_unlink
		}
	}

	/*
	 * Ghi lại đường dẫn tương đối ĐÃ DỌN — bước này cũng chữa luôn mấy bản ghi
	 * cũ còn dính đường dẫn tuyệt đối của máy khác. Chỉ có dấu chéo xuôi nên
	 * không dính bẫy wp_unslash của update_post_meta.
	 */
	update_post_meta( $attachment_id, '_wp_attached_file', $con );
	update_post_meta( $attachment_id, NNTM_LIB_META_RIENG, '1' );

	return $ket( 'da_doi', $dich );
}

/**
 * Dời tất cả tệp PDF của ấn phẩm vào kho riêng.
 *
 * @param bool $that false thì chỉ thử.
 * @return array Danh sách kết quả từng tệp.
 */
function nntm_lib_di_doi_tat_ca( bool $that = false ): array {
	$ra = array();

	foreach ( nntm_lib_danh_sach_pdf() as $id ) {
		$ra[] = nntm_lib_di_doi_mot( $id, $that );
	}

	return $ra;
}

/**
 * Tệp PDF mới gắn vào ấn phẩm thì dời ngay vào kho riêng.
 *
 * Không có bước này thì mỗi cuốn sách thêm về sau lại nằm hớ hênh trong uploads,
 * và người quản trị không có cách nào biết.
 *
 * @param int    $meta_id  Không dùng.
 * @param int    $post_id  ID bài viết.
 * @param string $meta_key Tên khoá meta.
 */
function nntm_lib_tu_dong_di_doi( $meta_id, $post_id, $meta_key ): void {
	if ( '_nntm_pdf_file' !== $meta_key ) {
		return;
	}

	$att_id = absint( get_post_meta( (int) $post_id, '_nntm_pdf_file', true ) );

	if ( $att_id > 0 ) {
		nntm_lib_di_doi_mot( $att_id, true );
	}
}
add_action( 'added_post_meta', 'nntm_lib_tu_dong_di_doi', 10, 3 );
add_action( 'updated_post_meta', 'nntm_lib_tu_dong_di_doi', 10, 3 );

/**
 * Trang công cụ để quản trị bấm chạy di dời.
 */
function nntm_lib_them_trang_cong_cu(): void {
	add_management_page(
		__( 'Kho PDF riêng', 'nntm' ),
		__( 'Kho PDF riêng', 'nntm' ),
		'manage_options',
		'nntm-kho-rieng',
		'nntm_lib_ve_trang_cong_cu'
	);
}
add_action( 'admin_menu', 'nntm_lib_them_trang_cong_cu' );

/**
 * Vẽ trang công cụ.
 */
function nntm_lib_ve_trang_cong_cu(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$kho    = nntm_lib_kho_rieng();
	$chay   = isset( $_POST['nntm_lib_chay'] ) && check_admin_referer( 'nntm_lib_di_doi' );
	$ket_qua = $chay ? nntm_lib_di_doi_tat_ca( true ) : nntm_lib_di_doi_tat_ca( false );

	echo '<div class="wrap"><h1>' . esc_html__( 'Kho PDF riêng', 'nntm' ) . '</h1>';

	printf(
		'<p><strong>%s</strong> <code>%s</code><br><strong>%s</strong> %s</p>',
		esc_html__( 'Thư mục kho:', 'nntm' ),
		esc_html( $kho['duong_dan'] ),
		esc_html__( 'Nằm ngoài thư mục web:', 'nntm' ),
		$kho['ngoai_web']
			? '<span style="color:#046b02">' . esc_html__( 'Có', 'nntm' ) . '</span>'
			: '<span style="color:#b32d2e">' . esc_html__( 'KHÔNG — chỉ chặn được bằng .htaccess trên Apache', 'nntm' ) . '</span>'
	);

	echo '<table class="widefat striped"><thead><tr><th>ID</th><th>' . esc_html__( 'Kết quả', 'nntm' ) . '</th><th>' . esc_html__( 'Chi tiết', 'nntm' ) . '</th></tr></thead><tbody>';

	foreach ( $ket_qua as $d ) {
		printf(
			'<tr><td>%d</td><td>%s</td><td><code>%s</code></td></tr>',
			(int) $d['id'],
			esc_html( $d['ket_qua'] ),
			esc_html( $d['chi_tiet'] )
		);
	}

	echo '</tbody></table>';

	echo '<form method="post" style="margin-top:16px">';
	wp_nonce_field( 'nntm_lib_di_doi' );
	echo '<button type="submit" name="nntm_lib_chay" value="1" class="button button-primary">'
		. esc_html__( 'Dời tất cả vào kho riêng', 'nntm' ) . '</button>';
	echo '</form></div>';
}
