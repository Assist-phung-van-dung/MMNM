<?php
/**
 * Kho riêng cho file PDF.
 *
 * VÌ SAO: trước đây PDF nằm thẳng trong wp-content/uploads nên có URL công
 * khai — bất kỳ ai đoán ra đường dẫn là tải được cả cuốn, không qua cổng quyền
 * nào. Cái paywall dựng trên nền đó chỉ là hàng rào giấy.
 *
 * docs/04-kien-truc.md mục 4 (chốt 06/08/2026) đã ghi: "File PDF gốc không bao
 * giờ lộ URL. Nằm ngoài thư mục web, phục vụ qua một endpoint PHP kiểm tra đăng
 * nhập và cấp phép." File này lo vế đầu, phuc-vu-pdf.php lo vế sau.
 *
 * @package NNTM_Library
 */

defined( 'ABSPATH' ) || exit;

const NNTM_LIB_TEN_KHO = 'nntm-kho-rieng';

/**
 * Cờ đánh dấu tệp đã nằm trong kho riêng.
 *
 * VÌ SAO DÙNG CỜ CHỨ KHÔNG LƯU ĐƯỜNG DẪN TUYỆT ĐỐI:
 *
 * Cách đầu tiên nghĩ ra là ghi thẳng đường dẫn tuyệt đối vào _wp_attached_file.
 * Hỏng vì hai lẽ, cả hai đều chỉ lộ ra khi chạy thật:
 *
 *   1. update_post_meta() gọi wp_unslash() lên giá trị, nên đường dẫn Windows
 *      "D:\kho\2026\08\a.pdf" bị nuốt sạch dấu chéo ngược thành "D:kho2026 8a.pdf".
 *   2. Ngay cả khi thoát được bẫy đó, đường dẫn tuyệt đối trói CSDL vào đúng
 *      một máy. Dự án này đã dính rồi: bản ghi #463 còn nguyên đường dẫn
 *      "C:/xampp8_2/htdocs/NNTM/..." của một máy khác, và WordPress ghép thêm
 *      thư mục uploads vào đầu thành một đường dẫn vô nghĩa.
 *
 * Nên: _wp_attached_file giữ nguyên dạng tương đối "2026/08/a.pdf" như mọi tệp
 * khác, chỉ thêm một cờ. Bộ lọc get_attached_file bên dưới thấy cờ thì đổi gốc
 * từ thư mục uploads sang thư mục kho. Dời kho đi đâu cũng không phải sửa CSDL.
 */
const NNTM_LIB_META_RIENG = '_nntm_lib_rieng';

/**
 * Đường dẫn kho riêng, và kho đó có thật sự nằm ngoài tầm với của web không.
 *
 * Ba mức, ưu tiên từ an toàn nhất:
 *
 *   1. Hằng NNTM_LIB_DIR khai trong wp-config.php — quản trị tự chỉ định. Đây
 *      là cách duy nhất chắc chắn đúng trên mọi kiểu máy chủ.
 *   2. Một cấp TRÊN thư mục WordPress. Đúng với phần lớn máy chủ vì thư mục
 *      web trỏ thẳng vào thư mục WordPress.
 *   3. Trong wp-content. Vẫn nằm trong tầm web nên phải chặn thêm bằng
 *      .htaccess — chỉ ăn trên Apache. Đây là đường lui, không phải đích đến.
 *
 * Trả về mảng để nơi gọi biết đang ở mức nào mà cảnh báo, thay vì tưởng đã an
 * toàn trong khi đang ở mức 3 trên nginx.
 *
 * @return array{duong_dan: string, muc: int, ngoai_web: bool}
 */
function nntm_lib_kho_rieng(): array {
	if ( defined( 'NNTM_LIB_DIR' ) && '' !== (string) NNTM_LIB_DIR ) {
		return array(
			'duong_dan' => rtrim( (string) NNTM_LIB_DIR, "/\\" ),
			'muc'       => 1,
			'ngoai_web' => true,
		);
	}

	$cha  = dirname( untrailingslashit( ABSPATH ) );
	$tren = $cha . DIRECTORY_SEPARATOR . NNTM_LIB_TEN_KHO;

	/*
	 * Mức 2 chỉ đúng khi WordPress nằm NGAY tại thư mục web. Nếu WordPress nằm
	 * trong thư mục con (ví dụ htdocs/MMNM) thì thư mục cha CHÍNH LÀ thư mục
	 * web — bỏ kho vào đó là lại phơi ra ngoài, tệ hơn cả chỗ cũ.
	 *
	 * Cách nhận biết: site_url() có phần đường dẫn thì WordPress đang ở thư mục
	 * con. Kèm điều kiện thư mục cha ghi được — hosting chia sẻ thường khoá,
	 * phải thử chứ đừng đoán.
	 */
	$duong_dan_site = (string) wp_parse_url( site_url(), PHP_URL_PATH );
	$o_thu_muc_con  = '' !== trim( $duong_dan_site, '/' );

	if ( ! $o_thu_muc_con && ( is_dir( $tren ) || wp_is_writable( $cha ) ) ) {
		return array(
			'duong_dan' => $tren,
			'muc'       => 2,
			'ngoai_web' => true,
		);
	}

	return array(
		'duong_dan' => WP_CONTENT_DIR . DIRECTORY_SEPARATOR . NNTM_LIB_TEN_KHO,
		'muc'       => 3,
		'ngoai_web' => false,
	);
}

/**
 * Đường dẫn thư mục kho riêng.
 */
function nntm_lib_duong_dan_kho(): string {
	$kho = nntm_lib_kho_rieng();

	return $kho['duong_dan'];
}

/**
 * Dựng kho riêng nếu chưa có, kèm hai lớp chặn cho trường hợp phải nằm trong web.
 *
 * .htaccess chặn Apache; index.php chặn việc liệt kê thư mục. Cả hai đều VÔ
 * DỤNG trên nginx — nên hàm nntm_lib_canh_bao_kho() còn nói thẳng điều đó ra
 * màn hình quản trị, thay vì để người dùng yên tâm nhầm.
 *
 * @return bool Dựng được hay không.
 */
function nntm_lib_dung_kho_rieng(): bool {
	$duong_dan = nntm_lib_duong_dan_kho();

	if ( ! is_dir( $duong_dan ) && ! wp_mkdir_p( $duong_dan ) ) {
		return false;
	}

	$htaccess = $duong_dan . DIRECTORY_SEPARATOR . '.htaccess';
	if ( ! file_exists( $htaccess ) ) {
		file_put_contents( // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- chay ca luc kich hoat, chua chac co WP_Filesystem.
			$htaccess,
			"# Kho rieng cua NNTM Library — khong phuc vu truc tiep.\n"
			. "<IfModule mod_authz_core.c>\n\tRequire all denied\n</IfModule>\n"
			. "<IfModule !mod_authz_core.c>\n\tOrder allow,deny\n\tDeny from all\n</IfModule>\n"
		);
	}

	$index = $duong_dan . DIRECTORY_SEPARATOR . 'index.php';
	if ( ! file_exists( $index ) ) {
		file_put_contents( $index, "<?php\n// Im lang la vang.\n" ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
	}

	return true;
}

/**
 * Tệp đính kèm này đã nằm trong kho riêng chưa.
 *
 * @param int $attachment_id ID tệp đính kèm.
 */
function nntm_lib_dang_o_kho_rieng( int $attachment_id ): bool {
	return (bool) get_post_meta( $attachment_id, NNTM_LIB_META_RIENG, true );
}

/**
 * Đường dẫn tương đối đã lưu của một tệp, dạng "2026/08/a.pdf".
 *
 * Dọn luôn dữ liệu cũ lỡ chứa đường dẫn tuyệt đối của máy khác: cắt lấy phần
 * sau "uploads/" rồi bỏ ký tự ổ đĩa.
 *
 * @param int $attachment_id ID tệp đính kèm.
 */
function nntm_lib_duong_dan_tuong_doi( int $attachment_id ): string {
	$luu = (string) get_post_meta( $attachment_id, '_wp_attached_file', true );
	$luu = ltrim( str_replace( '\\', '/', $luu ), '/' );

	if ( preg_match( '#uploads/(.+)$#', $luu, $m ) ) {
		$luu = $m[1];
	}

	return (string) preg_replace( '#^[A-Za-z]:/?#', '', $luu );
}

/**
 * Đổi gốc đường dẫn sang kho riêng cho những tệp đã dời.
 *
 * Chạy trên bộ lọc của chính WordPress nên MỌI hàm lõi đọc tệp qua
 * get_attached_file() đều tự đi đúng chỗ — kể cả endpoint tải về của
 * nntm-search, không phải sửa nó.
 *
 * @param string $file          Đường dẫn WordPress vừa tính.
 * @param int    $attachment_id ID tệp đính kèm.
 */
function nntm_lib_doi_goc_duong_dan( $file, $attachment_id ) {
	if ( ! nntm_lib_dang_o_kho_rieng( (int) $attachment_id ) ) {
		return $file;
	}

	$con = nntm_lib_duong_dan_tuong_doi( (int) $attachment_id );

	if ( '' === $con ) {
		return $file;
	}

	return nntm_lib_duong_dan_kho() . DIRECTORY_SEPARATOR . str_replace( '/', DIRECTORY_SEPARATOR, $con );
}
add_filter( 'get_attached_file', 'nntm_lib_doi_goc_duong_dan', 10, 2 );

/**
 * Cảnh báo cho quản trị khi kho riêng vẫn nằm trong tầm web.
 *
 * Nói rõ ràng chứ không im lặng: một cái paywall tưởng là kín mà thực ra hở
 * còn nguy hơn là biết mình đang hở.
 */
function nntm_lib_canh_bao_kho(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$kho = nntm_lib_kho_rieng();

	if ( $kho['ngoai_web'] ) {
		return;
	}

	echo '<div class="notice notice-warning"><p><strong>' . esc_html__( 'NNTM Library:', 'nntm' ) . '</strong> ';
	echo esc_html__( 'Kho PDF riêng đang nằm trong wp-content nên vẫn thuộc tầm phục vụ của web. Nó chỉ được chặn bằng .htaccess — cách này ăn trên Apache, KHÔNG ăn trên nginx.', 'nntm' );
	echo '<br>' . esc_html__( 'Cách chắc chắn: thêm vào wp-config.php một dòng chỉ tới thư mục nằm ngoài thư mục web, ví dụ:', 'nntm' );
	echo ' <code>define( \'NNTM_LIB_DIR\', \'/duong/dan/ngoai/web/nntm-kho-rieng\' );</code></p></div>';
}
add_action( 'admin_notices', 'nntm_lib_canh_bao_kho' );
