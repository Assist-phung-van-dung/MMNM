<?php
/**
 * Hàm dựng "băng Netflix" cho block nntm/card-list khi videoSource=youtube
 * (G1 — dải "Gót Son", xem docs/spec-trang-chu.md mục G1).
 *
 * Nguồn video KHÔNG dùng YouTube Data API (anh Úy chốt 12/08/2026): admin
 * chỉ dán link/ID YouTube vào một ô textarea trong trình soạn thảo, mỗi
 * dòng một video, dạng:
 *
 *     https://www.youtube.com/watch?v=abc123 | TẬP 18 - CHÂN SƯ HIỆN THÁNH TƯỚNG
 *
 * Phần sau dấu "|" là tiêu đề hiện dưới thẻ — TÙY CHỌN. Không gõ tiêu đề
 * thì tự lấy qua oEmbed công khai của YouTube (KHÔNG cần API key, không
 * tốn quota), có lưu đệm bằng transient — xem
 * nntm_card_list_get_video_title().
 *
 * Tách sang inc/ vì render.php bị WordPress core `require` (không phải
 * `require_once`) mỗi lần render — khai hàm thẳng trong render.php sẽ vỡ
 * khi block xuất hiện lần thứ hai trên cùng trang (docs/04-kien-truc.md
 * mục 9).
 *
 * @package NNTM
 */

defined( 'ABSPATH' ) || exit;

/**
 * Tách ID YouTube (11 ký tự) từ một chuỗi (URL hoặc ID trần) — chấp nhận:
 *   - https://www.youtube.com/watch?v=ID (kèm tham số khác, ví dụ &t=10)
 *   - https://youtu.be/ID
 *   - https://www.youtube.com/embed/ID hoặc /shorts/ID
 *   - ID trần (đúng 11 ký tự, chữ/số/-/_)
 *
 * @param string $raw_value Chuỗi đã trim (URL hoặc ID).
 * @return string ID hợp lệ, hoặc chuỗi rỗng nếu không nhận diện được.
 */
function nntm_card_list_extract_youtube_id( string $raw_value ): string {
	$value = trim( $raw_value );

	if ( '' === $value ) {
		return '';
	}

	// ID trần — không phải URL.
	if ( preg_match( '/^[A-Za-z0-9_-]{11}$/', $value ) ) {
		return $value;
	}

	// Mọi dạng URL youtube.com / youtu.be.
	if ( preg_match( '#(?:youtube(?:-nocookie)?\.com/(?:watch\?v=|embed/|shorts/|v/)|youtu\.be/)([A-Za-z0-9_-]{11})#i', $value, $matches ) ) {
		return $matches[1];
	}

	return '';
}

/**
 * Tách MỘT dòng nhập của admin thành { link/ID, tiêu đề tuỳ chọn }.
 * Cú pháp: "<link hoặc ID> | <tiêu đề>" — dấu "|" và phần sau đều tuỳ chọn.
 *
 * @param string $raw_line Một dòng dán vào (chưa trim).
 * @return array{id:string,title:string} id rỗng nếu dòng không hợp lệ.
 */
function nntm_card_list_split_youtube_line( string $raw_line ): array {
	$line = trim( $raw_line );

	if ( '' === $line ) {
		return array(
			'id'    => '',
			'title' => '',
		);
	}

	$parts     = explode( '|', $line, 2 );
	$link_part = trim( $parts[0] );
	$title     = isset( $parts[1] ) ? sanitize_text_field( trim( $parts[1] ) ) : '';

	return array(
		'id'    => nntm_card_list_extract_youtube_id( $link_part ),
		'title' => $title,
	);
}

/**
 * Tách danh sách { ID, tiêu đề } từ nội dung textarea (mỗi dòng một video).
 *
 * @param string $raw Nội dung thô của ô "Danh sách link YouTube".
 * @return array<int, array{id:string,title:string}> Đã loại trùng theo ID, giữ thứ tự nhập.
 */
function nntm_card_list_parse_youtube_items( string $raw ): array {
	if ( '' === trim( $raw ) ) {
		return array();
	}

	$lines = preg_split( '/[\r\n]+/', $raw );
	$items = array();
	$seen  = array();

	foreach ( (array) $lines as $line ) {
		$parsed = nntm_card_list_split_youtube_line( (string) $line );

		if ( '' === $parsed['id'] || isset( $seen[ $parsed['id'] ] ) ) {
			continue;
		}

		$seen[ $parsed['id'] ] = true;
		$items[]               = $parsed;
	}

	// Giới hạn hợp lý — tránh admin dán hàng trăm dòng làm băng quá nặng.
	return array_slice( $items, 0, 30 );
}

/**
 * Lấy tiêu đề hiển thị dưới thẻ cho một video.
 *
 * Thứ tự ưu tiên:
 *   1. Tiêu đề admin gõ kèm sau dấu "|" (không gọi mạng).
 *   2. oEmbed công khai của YouTube (`youtube.com/oembed`) — không cần API
 *      key, không tốn quota. Kết quả lưu transient TỐI THIỂU 1 tuần theo
 *      từng ID để không gọi lại mỗi lần tải trang.
 *
 * AN TOÀN BẮT BUỘC: gọi mạng có timeout 3 giây, và nếu hỏng (không có
 * internet, YouTube chặn, v.v.) thì ÂM THẦM bỏ qua — trả chuỗi rỗng, KHÔNG
 * được làm trang chậm hay lỗi. Kết quả rỗng (thất bại) chỉ cache NGẮN
 * (1 giờ, không phải 1 tuần) để tự thử lại sớm hơn khi mạng có lại — cache
 * dài 1 tuần chỉ áp dụng cho kết quả THÀNH CÔNG.
 *
 * @param string $video_id       ID YouTube.
 * @param string $explicit_title Tiêu đề admin gõ tay (đã trim), rỗng nếu không gõ.
 * @return string Tiêu đề hiển thị (đã qua sanitize_text_field), có thể rỗng.
 */
function nntm_card_list_get_video_title( string $video_id, string $explicit_title ): string {
	if ( '' !== $explicit_title ) {
		return $explicit_title;
	}

	if ( '' === $video_id ) {
		return '';
	}

	$transient_key = 'nntm_yt_title_' . $video_id;
	$cached        = get_transient( $transient_key );

	if ( false !== $cached ) {
		// Chuỗi rỗng cũng là giá trị cache hợp lệ (lần gọi trước thất bại) —
		// tôn trọng luôn, không gọi lại cho tới khi transient hết hạn.
		return (string) $cached;
	}

	$oembed_url = add_query_arg(
		array(
			'url'    => 'https://www.youtube.com/watch?v=' . rawurlencode( $video_id ),
			'format' => 'json',
		),
		'https://www.youtube.com/oembed'
	);

	$response = wp_remote_get(
		$oembed_url,
		array(
			'timeout'    => 3, // BẮT BUỘC: ngắn, không được làm trang chậm khi YouTube/mạng không phản hồi.
			'redirection' => 2,
		)
	);

	if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
		// Thất bại (mất mạng, timeout, video riêng tư/đã xoá...) — cache NGẮN
		// để không dội lại yêu cầu mạng mỗi lần tải trang, nhưng đủ ngắn để
		// tự thử lại khi có mạng.
		set_transient( $transient_key, '', HOUR_IN_SECONDS );
		return '';
	}

	$body = json_decode( (string) wp_remote_retrieve_body( $response ), true );
	$title = ( is_array( $body ) && isset( $body['title'] ) ) ? sanitize_text_field( (string) $body['title'] ) : '';

	set_transient( $transient_key, $title, WEEK_IN_SECONDS );

	return $title;
}

/**
 * Dựng một thẻ video trong băng (ảnh nền lấy trực tiếp từ img.youtube.com,
 * không gọi API). JS (view.js) sẽ:
 *   - đổi sang hqdefault.jpg nếu maxresdefault.jpg lỗi (video không có bản
 *     độ phân giải cao);
 *   - sau ~500ms rê chuột/focus liên tục thì chèn <iframe> phát thử, gỡ khi
 *     rời chuột/blur.
 *
 * SỬA 13/08/2026: Gót Son và GITA CENTER dùng CHUNG hàm này nhưng hình
 * dạng thẻ KHÁC NHAU (đối chiếu lại Figma của điều phối viên) —
 *   - $framed = false (Gót Son): ảnh 348×198 đặt thẳng trên nền đen, tiêu
 *     đề 2 dòng bên dưới, KHÔNG có khung thẻ riêng.
 *   - $framed = true (GITA CENTER): MỘT khung thẻ nền tối 388×360, ảnh
 *     348×196 thụt vào đều 20px từ mép thẻ, tiêu đề tối đa 3 dòng bên
 *     trong thẻ (không phải 2).
 *
 * @param string $video_id ID YouTube (đã tách, đúng 11 ký tự).
 * @param string $title    Tiêu đề hiển thị dưới thẻ (đã resolve), rỗng thì không hiện dòng tiêu đề.
 * @param bool   $aria_hidden_dup Đánh dấu thẻ nhân bản (nửa sau của băng, chỉ để marquee liền mạch) — ẩn với trình đọc màn hình và bỏ khỏi thứ tự Tab.
 * @param bool   $framed Đúng nếu dựng kiểu "thẻ khung nền tối" (GITA CENTER).
 * @return string HTML đã escape.
 */
function nntm_card_list_render_youtube_item( string $video_id, string $title, bool $aria_hidden_dup = false, bool $framed = false ): string {
	$id_attr  = esc_attr( $video_id );
	$max_url  = esc_url( 'https://img.youtube.com/vi/' . $video_id . '/maxresdefault.jpg' );
	$fallback = esc_url( 'https://img.youtube.com/vi/' . $video_id . '/hqdefault.jpg' );

	$tabindex    = $aria_hidden_dup ? '-1' : '0';
	$aria_hidden = $aria_hidden_dup ? ' aria-hidden="true"' : '';

	$cell_class  = 'nntm-card-list__yt-cell' . ( $framed ? ' nntm-card-list__yt-cell--framed' : '' );
	$item_class  = 'nntm-card-list__yt-item' . ( $framed ? ' nntm-card-list__yt-item--framed' : '' );
	$title_class = 'nntm-card-list__yt-title' . ( $framed ? ' nntm-card-list__yt-title--3-dong' : ' nntm-cat-2-dong' );

	// Phần media (ảnh + nút play + chỗ chèn iframe) — dùng chung cho cả
	// hai kiểu, chỉ khác lớp bọc ngoài (xem style.css).
	ob_start();
	?>
	<img class="nntm-card-list__yt-thumb" src="<?php echo $max_url; ?>" data-fallback="<?php echo $fallback; ?>" alt="" loading="lazy" decoding="async" />
	<span class="nntm-card-list__yt-play" aria-hidden="true">
		<svg viewBox="0 0 48 48" width="36" height="36" fill="none" focusable="false">
			<circle cx="24" cy="24" r="23" stroke="currentColor" stroke-width="2" />
			<path d="M19 15.5 L34 24 L19 32.5 Z" fill="currentColor" />
		</svg>
	</span>
	<div class="nntm-card-list__yt-frame" aria-hidden="true"></div>
	<?php
	$media_html = (string) ob_get_clean();

	ob_start();
	if ( $framed ) :
		// GITA CENTER: khung thẻ nền tối 388x360 bọc NGOÀI ảnh (thụt 20px,
		// dùng padding của chính khung) và tiêu đề — cả hai đều bên TRONG
		// khung, không tách rời như Gót Son.
		?>
		<div class="<?php echo esc_attr( $cell_class ); ?>">
			<div class="<?php echo esc_attr( $item_class ); ?>" data-video-id="<?php echo $id_attr; ?>" tabindex="<?php echo esc_attr( $tabindex ); ?>" role="button"<?php echo $aria_hidden; // phpcs:ignore WordPress.Security.EscapeOutput -- da esc_attr() truoc do. ?> aria-label="<?php esc_attr_e( 'Xem thử video', 'nntm' ); ?>">
				<div class="nntm-card-list__yt-media"><?php echo $media_html; // phpcs:ignore WordPress.Security.EscapeOutput -- da escape ben trong. ?></div>
				<?php if ( '' !== $title ) : ?>
					<p class="<?php echo esc_attr( $title_class ); ?>"><?php echo esc_html( $title ); ?></p>
				<?php endif; ?>
			</div>
		</div>
		<?php
	else :
		// Gót Son: ảnh KHÔNG có khung riêng, tiêu đề nằm NGOÀI, dưới ảnh.
		?>
		<div class="<?php echo esc_attr( $cell_class ); ?>">
			<div class="<?php echo esc_attr( $item_class ); ?>" data-video-id="<?php echo $id_attr; ?>" tabindex="<?php echo esc_attr( $tabindex ); ?>" role="button"<?php echo $aria_hidden; // phpcs:ignore WordPress.Security.EscapeOutput -- da esc_attr() truoc do. ?> aria-label="<?php esc_attr_e( 'Xem thử video', 'nntm' ); ?>">
				<?php echo $media_html; // phpcs:ignore WordPress.Security.EscapeOutput -- da escape ben trong. ?>
			</div>
			<?php if ( '' !== $title ) : ?>
				<p class="<?php echo esc_attr( $title_class ); ?>"<?php echo $aria_hidden; // phpcs:ignore WordPress.Security.EscapeOutput -- da esc_attr() truoc do. ?>><?php echo esc_html( $title ); ?></p>
			<?php endif; ?>
		</div>
		<?php
	endif;
	return trim( (string) ob_get_clean() );
}

/**
 * Lặp lại danh sách item (đã lọc trùng) cho tới khi tổng bề rộng dải thẻ đạt
 * tối thiểu một ngưỡng an toàn — TRÁNH băng nhìn "rỗng"/thưa khi số ID thật
 * (sau lọc trùng) ít. KHÔNG bỏ lọc trùng — hàm này chạy SAU khi đã lọc
 * trùng ở nntm_card_list_parse_youtube_items(), chỉ lặp lại để lấp đầy bề
 * rộng, việc nhân đôi để chạy vòng liên tục vẫn làm riêng ở
 * nntm_card_list_render_youtube_marquee().
 *
 * @param array<int, array{id:string,title:string}> $items Danh sách item đã lọc trùng, giữ thứ tự nhập.
 * @param bool $framed Đúng nếu tính theo kích thước khung thẻ GITA CENTER (388, gap 20) thay vì Gót Son (348, gap 60).
 * @return array<int, array{id:string,title:string}> Danh sách đã lặp lại (bằng hoặc dài hơn $items gốc).
 */
function nntm_card_list_repeat_youtube_items_for_width( array $items, bool $framed = false ): array {
	$count = count( $items );
	if ( 0 === $count ) {
		return $items;
	}

	// Kích thước THẬT lấy từ style.css (đo lại theo Figma 13/08/2026):
	// Gót Son: .yt-item rộng 348px, track gap 60px.
	// GITA CENTER (framed): khung thẻ rộng 388px, track gap 20px.
	$card_width = $framed ? 388 : 348;
	$gap        = $framed ? 20 : 60;

	/*
	 * SUY ĐOÁN: băng này tràn viền (full-bleed, xem style.css phần "HAI KHỐI
	 * TRÀN VIỀN CỦA TRANG CHỦ"), nên khung chứa thực tế rộng bằng màn hình
	 * người dùng — chưa có số đo Figma cho màn rộng nhất. Ước lượng an toàn
	 * 2600px (bao trùm các màn hình phổ biến tới ~2560px) để đảm bảo dải thẻ
	 * luôn đạt tối thiểu 2 lần bề rộng khung chứa trên mọi kích thước màn
	 * hình thực tế, tránh vòng lặp marquee bị "giật"/có khoảng trống.
	 */
	$assumed_max_container_width = 2600;
	$min_strip_width             = 2 * $assumed_max_container_width;

	$target_item_count = (int) ceil( ( $min_strip_width + $gap ) / ( $card_width + $gap ) );
	$repeats           = max( 1, (int) ceil( $target_item_count / $count ) );

	// Giới hạn hợp lý — tránh HTML phình to bất thường nếu đầu vào bất
	// thường (ví dụ danh sách rỗng lọt qua kiểm tra ở trên).
	$repeats = min( $repeats, 40 );

	$result = array();
	for ( $i = 0; $i < $repeats; $i++ ) {
		array_push( $result, ...$items );
	}

	return $result;
}

/**
 * Dựng cả băng marquee (chạy liên tục phải→trái, xem style.css + view.js).
 *
 * Kỹ thuật: LẶP danh sách đã lọc trùng cho đủ bề rộng (xem
 * nntm_card_list_repeat_youtube_items_for_width()), rồi NHÂN ĐÔI toàn bộ
 * danh sách đã lấp đầy và dịch `translateX(-50%)` bằng hoạt ảnh CSS tuyến
 * tính, lặp vô hạn — KHÔNG dùng scroll-snap (bài học cũ: scroll-snap từng
 * làm băng tự nhảy 110px khi tải trang, xem style.css của chính block này
 * ở phần carousel).
 *
 * Chỉ LƯỢT ĐẦU TIÊN của danh sách gốc (đã lọc trùng, chưa lặp lấp đầy) là
 * nội dung thật — mọi bản lặp lại sau đó (cả phần lấp đầy bề rộng lẫn phần
 * nhân đôi để chạy vòng) đều là TRANG TRÍ nên đánh dấu `aria-hidden="true"`
 * và `tabindex="-1"`, không cho trình đọc màn hình / phím Tab đi qua nội
 * dung trùng lặp.
 *
 * @param array<int, array{id:string,title:string}> $items Danh sách { ID, tiêu đề } (đã lọc, tối đa 30).
 * @param bool $framed Đúng nếu dựng kiểu "thẻ khung nền tối" (GITA CENTER) thay vì kiểu Gót Son.
 * @return string HTML đã escape.
 */
function nntm_card_list_render_youtube_marquee( array $items, bool $framed = false ): string {
	if ( empty( $items ) ) {
		return '';
	}

	// Resolve tiêu đề (gõ tay hoặc oEmbed) MỘT LẦN cho từng ID duy nhất,
	// trước khi lặp lấp đầy/nhân đôi — tránh gọi oEmbed nhiều lần thừa cho
	// cùng một video khi nó xuất hiện lặp lại trong dải.
	$titles_by_id = array();
	foreach ( $items as $item ) {
		if ( ! isset( $titles_by_id[ $item['id'] ] ) ) {
			$titles_by_id[ $item['id'] ] = nntm_card_list_get_video_title( $item['id'], $item['title'] );
		}
	}

	$unique_count = count( $items );
	$filled_items = nntm_card_list_repeat_youtube_items_for_width( $items, $framed );

	// Tốc độ marquee: SUY ĐOÁN — chưa có số đo Figma cho tốc độ cuộn, ước
	// lượng ~5s/thẻ (tính trên danh sách ĐÃ lấp đầy) để "mượt", không quá
	// chậm cũng không quá gấp.
	$duration_seconds = max( 20, count( $filled_items ) * 5 );

	$marquee_class = 'nntm-card-list__yt-marquee' . ( $framed ? ' nntm-card-list__yt-marquee--framed' : '' );

	ob_start();
	?>
	<div class="<?php echo esc_attr( $marquee_class ); ?>">
		<div class="nntm-card-list__yt-track" style="--nntm-yt-duration: <?php echo esc_attr( (string) $duration_seconds ); ?>s;">
			<?php foreach ( $filled_items as $i => $item ) : ?>
				<?php echo nntm_card_list_render_youtube_item( $item['id'], $titles_by_id[ $item['id'] ], $i >= $unique_count, $framed ); // phpcs:ignore WordPress.Security.EscapeOutput -- da escape ben trong. ?>
			<?php endforeach; ?>
			<?php foreach ( $filled_items as $item ) : ?>
				<?php echo nntm_card_list_render_youtube_item( $item['id'], $titles_by_id[ $item['id'] ], true, $framed ); // phpcs:ignore WordPress.Security.EscapeOutput -- da escape ben trong, ban nhan doi chi de marquee lien mach. ?>
			<?php endforeach; ?>
		</div>
	</div>
	<?php
	return trim( (string) ob_get_clean() );
}
