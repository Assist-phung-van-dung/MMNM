<?php
/**
 * Điền LINK YOUTUBE MẪU (tạm) vào các khối video của trang chủ (page ID 110),
 * theo yêu cầu anh Úy chốt 13/08/2026: dùng CHUNG một link mẫu cho mọi chỗ
 * cần video, cho tới khi khách gửi danh sách thật.
 *
 * Link mẫu (giữ nguyên tham số thừa list=/start_radio= để tự kiểm chứng hàm
 * tách ID YouTube bỏ qua được các tham số này — xem
 * blocks/card-list/inc/render-card-list-youtube.php và
 * blocks/engineering-earth/inc/render-engineering-earth.php):
 *   https://www.youtube.com/watch?v=gJAbDSse5WM&list=RDgJAbDSse5WM&start_radio=1
 *
 * Áp dụng cho:
 *   - Khối nntm/card-list nền tối ("Gót Son"): videoSource=youtube,
 *     youtubeItems = 8 dòng link mẫu (anh Úy dặn ~8 thẻ cho băng marquee
 *     chạy đủ — LƯU Ý: nntm_card_list_parse_youtube_ids() loại trùng theo
 *     ID đã tách, nên 8 dòng CÙNG một ID sẽ chỉ còn 1 thẻ duy nhất sau khi
 *     lọc trùng — đây là hành vi có sẵn của block, không phải lỗi của
 *     script này. Băng marquee sẽ hiện đúng 1 thẻ mẫu (nhân đôi cho chạy
 *     liền mạch); khi khách gửi 8 link THẬT khác nhau, đủ 8 thẻ sẽ hiện.)
 *   - Khối nntm/card-list nền cam ("GITA CENTER"): tương tự, cũng chuyển
 *     sang youtube + 8 dòng link mẫu (anh Úy chốt "dùng cho tất cả các
 *     chỗ").
 *   - Khối nntm/engineering-earth: mainVideoUrl + bgVideoUrl = link mẫu.
 *
 * Cách chạy (chạy lại được nhiều lần, không tạo trùng — luôn ghi đè về
 * đúng trạng thái mong muốn):
 *   "C:/xampp8_2/php/php.exe" tools/seed-video-mau-trang-chu.php
 *
 * CHỈ dùng ở local/dev. KHÔNG sửa tay chuỗi block trong CSDL bằng SQL thô —
 * script này dùng parse_blocks()/serialize_blocks() của WordPress để không
 * làm hỏng cú pháp comment block.
 *
 * @package NNTM
 */

if ( PHP_SAPI !== 'cli' ) {
	exit( 'Chi chay tu dong lenh.' );
}

$_SERVER['HTTP_HOST'] = 'localhost:8080';
require_once __DIR__ . '/../wp-load.php';

/**
 * Link YouTube mẫu (TẠM) do anh Úy chốt 13/08/2026 — dùng chung cho mọi
 * chỗ cần video cho tới khi có danh sách thật.
 */
const NNTM_SAMPLE_YT_URL = 'https://www.youtube.com/watch?v=gJAbDSse5WM&list=RDgJAbDSse5WM&start_radio=1';

const NNTM_HOME_PAGE_ID = 110;

$post = get_post( NNTM_HOME_PAGE_ID );

if ( ! $post || 'page' !== $post->post_type ) {
	echo "Khong tim thay trang chu (ID " . NNTM_HOME_PAGE_ID . ").\n";
	exit( 1 );
}

$blocks  = parse_blocks( (string) $post->post_content );
$changed = false;

// 8 dòng link mẫu — mỗi dòng một video, khớp định dạng ô "Danh sách link
// YouTube" (mỗi dòng một video) mà editor.js của card-list sinh ra.
$eight_lines = implode( "\n", array_fill( 0, 8, NNTM_SAMPLE_YT_URL ) );

foreach ( $blocks as &$block ) {
	if ( 'nntm/card-list' !== ( $block['blockName'] ?? '' ) ) {
		continue;
	}

	$background = $block['attrs']['background'] ?? '';

	if ( 'toi' === $background || 'cam' === $background ) {
		$block['attrs']['videoSource']  = 'youtube';
		$block['attrs']['youtubeItems'] = $eight_lines;
		$changed                        = true;

		$label = ( 'toi' === $background ) ? 'Got Son (nen toi)' : 'GITA CENTER (nen cam)';
		echo "Da cap nhat khoi card-list [{$label}]: videoSource=youtube, 8 dong link mau.\n";
	}
}
unset( $block );

foreach ( $blocks as &$block ) {
	if ( 'nntm/engineering-earth' !== ( $block['blockName'] ?? '' ) ) {
		continue;
	}

	$block['attrs']['mainVideoUrl'] = NNTM_SAMPLE_YT_URL;
	$block['attrs']['bgVideoUrl']   = NNTM_SAMPLE_YT_URL;
	$changed                        = true;

	echo "Da cap nhat khoi engineering-earth: mainVideoUrl + bgVideoUrl = link mau.\n";
}
unset( $block );

if ( ! $changed ) {
	echo "Khong tim thay khoi nao can cap nhat (kiem tra lai noi dung trang chu).\n";
	exit( 1 );
}

$new_content = serialize_blocks( $blocks );

/*
 * QUAN TRỌNG (2 vấn đề khi ghi post_content bằng CLI, không phải request
 * có người dùng đăng nhập):
 *
 * 1. wp_update_post()/wp_insert_post() luôn stripslashes() dữ liệu đầu
 *    vào (giả định caller đã wp_slash() trước, theo quy ước xử lý
 *    magic-quotes cũ của lõi WordPress). Nếu truyền thẳng JSON đã
 *    serialize_blocks() (chứa &, \n, …) mà không wp_slash() trước, các
 *    dấu backslash sẽ bị ăn mất và làm hỏng cú pháp comment block
 *    (& -> u0026). Phải wp_slash() trước khi gọi wp_update_post().
 *
 * 2. CLI không có người dùng đăng nhập nên current_user_can( 'unfiltered_html' )
 *    trả về false -> content_save_pre chạy wp_filter_post_kses(), tự động
 *    đổi ký tự "&" trần trong nội dung thành "&amp;" — làm sai lệch chuỗi
 *    URL YouTube lưu trong thuộc tính youtubeItems/mainVideoUrl/bgVideoUrl
 *    (vẫn tách được ID vì regex không quan tâm phần sau "v=...", nhưng URL
 *    lưu lại không còn nguyên vẹn). Tạm tắt bộ lọc kses quanh lệnh ghi để
 *    giữ nguyên URL gốc, giống cách các script tools/seed-*.php khác nên
 *    làm khi ghi nội dung có ký tự đặc biệt.
 */
kses_remove_filters();

wp_update_post(
	wp_slash(
		array(
			'ID'           => NNTM_HOME_PAGE_ID,
			'post_content' => $new_content,
		)
	)
);

kses_init_filters();

echo "\nHoan tat. Trang chu: " . get_permalink( NNTM_HOME_PAGE_ID ) . "\n";
