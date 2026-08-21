<?php
/**
 * Băng TỰ CHẠY (marquee) cho danh sách BÀI của block nntm/card-list
 * (layout=marquee) — thêm 14/08/2026 vì dự án CHƯA HỀ có băng tự chạy cho
 * danh sách bài, chỉ có `layout=carousel` (khung cuộn tay CÓ nút mũi tên +
 * thanh cuộn) và marquee riêng cho nguồn YouTube
 * (xem inc/render-card-list-youtube.php). Chủ dự án chốt: dải "Nghi Quỹ"
 * trên trang Kim Cương Hành Giả (ID 243) phải là băng tự chạy, KHÔNG thanh
 * cuộn — xem docs/07-ban-giao.md.
 *
 * SỬA 21/08/2026 — CÓ NÚT MŨI TÊN: chủ dự án đổi quyết định "phần Nghi Quỹ
 * thêm icon nhấn 2 bên để anh có thể click chuyển qua lại các item trên
 * slider". Băng vẫn TỰ CHẠY như cũ khi chưa ai bấm; bấm mũi tên là người xem
 * tiếp quản, băng dừng tự chạy và đi từng thẻ theo nút (đúng nguyên tắc đã
 * dùng ở carousel: "người dùng tự điều khiển thì không giật băng khỏi tay
 * họ nữa" — xem view.js). Vẫn KHÔNG có thanh cuộn: overflow:hidden giữ
 * nguyên, hai nút chỉ nằm đè lên hai mép băng.
 *
 * KỸ THUẬT DÙNG LẠI Y HỆT nntm_card_list_render_youtube_marquee() ở
 * inc/render-card-list-youtube.php (ĐỪNG đụng file đó — băng Gót Son/GITA
 * trang chủ đang chạy bằng nó):
 *   - Lặp danh sách (đã lấp đầy bề rộng) HAI LẦN trong một track, dịch
 *     translateX(-50%) bằng @keyframes tuyến tính vô hạn — KHÔNG JavaScript.
 *   - CỐ Ý KHÔNG dùng scroll-snap / overflow-x:auto (bài học cũ đã ghi ở
 *     style.css phần carousel: scroll-snap từng làm băng tự nhảy 110px lúc
 *     tải trang) — track chỉ dịch bằng animation, không cho cuộn tay.
 *   - Rê chuột / focus vào băng thì animation-play-state: paused
 *     (xem style.css khối "Băng tự chạy — danh sách BÀI").
 *   - Tôn trọng prefers-reduced-motion: reduce (style.css).
 *
 * KHÁC với nntm_card_list_render_youtube_marquee(): thẻ ở đây là BÀI VIẾT
 * dựng bằng nntm_render_card_markup() (blocks/card/inc/render-card.php,
 * KHÔNG được sửa file đó) — hàm đó không có tham số "aria_hidden_dup"/
 * "tabindex" như nntm_card_list_render_youtube_item(), nên bản sao trang trí
 * (nửa sau của track, và phần lặp lại để lấp đầy bề rộng) được xử lý bằng
 * cách BỌC THÊM một <div> ngoài mỗi thẻ: gắn aria-hidden="true" lên div bọc,
 * và chèn tabindex="-1" thẳng vào thẻ <a> bên trong bằng preg_replace — để
 * bản sao vừa ẩn khỏi trình đọc màn hình vừa KHÔNG lọt vào thứ tự Tab (chỉ
 * aria-hidden ở ancestor KHÔNG đủ, thẻ <a> con vẫn bắt được Tab nếu không tự
 * đặt tabindex="-1" — đây là bẫy WCAG hay gặp với kỹ thuật lặp DOM).
 *
 * @package NNTM
 */

defined( 'ABSPATH' ) || exit;

/**
 * Hai nút mũi tên của MỘT băng tự chạy — dùng chung cho băng danh sách BÀI
 * (hàm nntm_card_list_render_posts_marquee() dưới đây) và băng nguồn YOUTUBE
 * (nntm_card_list_render_youtube_marquee() ở inc/render-card-list-youtube.php).
 *
 * Thêm 21/08/2026. Một hàm duy nhất để hai băng không bao giờ lệch nhau về
 * markup/lớp CSS — hình dạng lấy theo mũi tên chuẩn của site, xem khối
 * "MŨI TÊN DÙNG CHUNG" trong blocks/card-list/style.css.
 *
 * Ký tự ← / → do CSS vẽ qua ::before (giống .nntm-an-pham-carousel__nav-icon)
 * chứ không gõ thẳng vào HTML — nút đã có aria-label riêng, in thêm ký tự
 * vào DOM chỉ làm trình đọc màn hình đọc dư.
 *
 * @return string HTML đã escape.
 */
function nntm_card_list_render_marquee_nav(): string {
	ob_start();
	?>
	<button type="button" class="nntm-card-list__marquee-nav nntm-card-list__marquee-nav--prev" aria-label="<?php esc_attr_e( 'Xem thẻ trước', 'nntm' ); ?>">
		<span class="nntm-card-list__marquee-nav-icon" aria-hidden="true"></span>
	</button>
	<button type="button" class="nntm-card-list__marquee-nav nntm-card-list__marquee-nav--next" aria-label="<?php esc_attr_e( 'Xem thẻ tiếp theo', 'nntm' ); ?>">
		<span class="nntm-card-list__marquee-nav-icon" aria-hidden="true"></span>
	</button>
	<?php
	return trim( (string) ob_get_clean() );
}

/**
 * Lặp lại danh sách ID bài (giữ thứ tự) cho tới khi tổng bề rộng ước lượng
 * của dải thẻ đạt tối thiểu một ngưỡng an toàn — cùng mục đích và công thức
 * với nntm_card_list_repeat_youtube_items_for_width() ở
 * inc/render-card-list-youtube.php, chỉ khác đơn vị lặp là ID bài thay vì
 * { id, title } của YouTube.
 *
 * @param array<int, int> $post_ids  Danh sách ID bài (đã lấy từ WP_Query, thứ tự hiển thị).
 * @param int              $card_width Bề rộng ước lượng của MỘT thẻ (px).
 * @param int              $gap        Khoảng cách ước lượng giữa hai thẻ (px).
 * @return array<int, int> Danh sách ID đã lặp lại (bằng hoặc dài hơn $post_ids gốc).
 */
function nntm_card_list_repeat_posts_for_width( array $post_ids, int $card_width, int $gap ): array {
	$count = count( $post_ids );
	if ( 0 === $count ) {
		return $post_ids;
	}

	/*
	 * SUY ĐOÁN (giống nntm_card_list_repeat_youtube_items_for_width()): băng
	 * này có thể tràn viền tuỳ nơi dùng, chưa có số đo cho màn hình rộng
	 * nhất — ước lượng an toàn 2600px, đủ lấp đầy gấp đôi trên hầu hết màn
	 * hình phổ biến (tới ~2560px) để marquee không bao giờ hở khoảng trống.
	 */
	$assumed_max_container_width = 2600;
	$min_strip_width             = 2 * $assumed_max_container_width;

	$target_item_count = (int) ceil( ( $min_strip_width + $gap ) / ( $card_width + $gap ) );
	$repeats           = max( 1, (int) ceil( $target_item_count / $count ) );

	// Giới hạn hợp lý — tránh HTML phình to bất thường (vd danh sách rỗng lọt qua kiểm tra ở trên).
	$repeats = min( $repeats, 40 );

	$result = array();
	for ( $i = 0; $i < $repeats; $i++ ) {
		array_push( $result, ...$post_ids );
	}

	return $result;
}

/**
 * Ước lượng bề rộng + khoảng cách MỘT thẻ theo variant — chỉ dùng để TÍNH
 * SỐ LẦN LẶP cho đủ bề rộng marquee (nntm_card_list_repeat_posts_for_width()),
 * KHÔNG ảnh hưởng kích thước hiển thị thật (kích thước thật do style.css —
 * kể cả style scoped riêng theo "Additional CSS class" như .nntm-kc-nghi-quy
 * — quyết định, render.php không biết được class riêng đó).
 *
 * SUY ĐOÁN: variant "books" khớp số đo đã có trong style.css cho dải Nghi
 * Quỹ (346px, khe 14px — xem khối ".nntm-kc-nghi-quy .nntm-card-list__marquee-item").
 * Mọi variant khác CHƯA có dải marquee nào dùng tới lúc viết hàm này nên
 * dùng số ước lượng chung theo bề rộng lưới mặc định (~320px, khe 20 =
 * --nntm-sp-5) — đủ an toàn để không bao giờ lặp THIẾU (lặp thừa một chút
 * chỉ tốn thêm vài thẻ HTML, không hại hiển thị).
 *
 * @param string $variant Biến thể thẻ, xem nntm_card_allowed_variants().
 * @return array{width:int,gap:int}
 */
function nntm_card_list_estimate_marquee_card_metrics( string $variant ): array {
	if ( 'books' === $variant ) {
		return array(
			'width' => 346,
			'gap'   => 14,
		);
	}

	return array(
		'width' => 320,
		'gap'   => 20,
	);
}

/**
 * Bọc một thẻ đã dựng sẵn (HTML trả về từ nntm_render_card_markup(), đã
 * escape) thành một item của track marquee. Bản THẬT (đọc được) không đổi
 * gì; bản TRANG TRÍ (aria_hidden_dup=true) được gắn aria-hidden="true" lên
 * div bọc VÀ tabindex="-1" ngay trên thẻ <a> bên trong (xem lý do "bẫy
 * WCAG" ở đầu file) để không lọt vào thứ tự Tab.
 *
 * @param string $card_html      HTML thẻ đã dựng (từ nntm_render_card_markup()), đã escape.
 * @param bool   $aria_hidden_dup Đúng nếu đây là bản lặp/nhân đôi chỉ để marquee chạy liền mạch.
 * @return string HTML đã escape (chỉ bọc thêm thẻ div tĩnh, không nhận input người dùng ở đây).
 */
function nntm_card_list_wrap_marquee_item( string $card_html, bool $aria_hidden_dup ): string {
	if ( $aria_hidden_dup ) {
		// Chi thay THE DAU TIEN "<a " (mo dau lien ket cua chinh the nay) —
		// limit 1 de khong dung cham vao html con lai ben trong.
		$card_html = preg_replace( '/<a /', '<a tabindex="-1" ', $card_html, 1 );
	}

	$aria_attr = $aria_hidden_dup ? ' aria-hidden="true"' : '';

	return '<div class="nntm-card-list__marquee-item"' . $aria_attr . '>' . $card_html . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput -- $card_html da duoc nntm_render_card_markup() escape, $aria_attr la chuoi tinh co dinh.
}

/**
 * Dựng cả băng marquee cho danh sách BÀI (layout=marquee).
 *
 * Kỹ thuật y hệt nntm_card_list_render_youtube_marquee(): lặp danh sách ID
 * cho đủ bề rộng, rồi NHÂN ĐÔI toàn bộ để track dịch translateX(-50%) chạy
 * vòng liên tục không giật. Chỉ $unique_count thẻ ĐẦU TIÊN (danh sách gốc từ
 * WP_Query, trước khi lặp lấp đầy) là nội dung thật/có thể Tab tới — mọi bản
 * sao còn lại (lặp lấp đầy + nhân đôi) đều đánh dấu trang trí.
 *
 * Tốc độ (thời lượng một vòng): CÙNG CÔNG THỨC với băng YouTube — ước lượng
 * ~5 giây/thẻ tính trên danh sách ĐÃ lấp đầy (trước khi nhân đôi), tối
 * thiểu 20 giây. Chưa có yêu cầu cụ thể nào khác cho tốc độ băng danh sách
 * bài nên tái dùng đúng số đã "chạy êm" của băng YouTube thay vì suy đoán số
 * mới; admin cũng KHÔNG cần tự chỉnh tốc độ ở đây (khác `carousel`, layout
 * này không có ô "Mỗi bao nhiêu giây chuyển một lần" vì marquee chạy liên
 * tục chứ không "chuyển từng bước").
 *
 * @param array<int, int> $post_ids       Danh sách ID bài theo thứ tự hiển thị (từ WP_Query).
 * @param string          $variant        Biến thể thẻ.
 * @param bool            $show_date      Có hiện ngày cập nhật trên thẻ không.
 * @param bool            $show_category  Có hiện nhãn phân mục trên thẻ không.
 * @param bool            $show_card_cta  Ép hiện "Xem thêm" cho variant chưa tự có sẵn.
 * @param string          $card_cta_label Nhãn "Xem thêm".
 * @return string HTML đã escape.
 */
function nntm_card_list_render_posts_marquee( array $post_ids, string $variant, bool $show_date, bool $show_category, bool $show_card_cta, string $card_cta_label ): string {
	$post_ids = array_values( array_filter( array_map( 'absint', $post_ids ) ) );

	if ( empty( $post_ids ) ) {
		return '';
	}

	$unique_count = count( $post_ids );
	$metrics      = nntm_card_list_estimate_marquee_card_metrics( $variant );
	$filled_ids   = nntm_card_list_repeat_posts_for_width( $post_ids, $metrics['width'], $metrics['gap'] );

	// SUY DOAN: cung cong thuc ~5s/the da "chay em" o bang YouTube (xem
	// nntm_card_list_render_youtube_marquee()), tinh tren danh sach DA lap
	// day (truoc khi nhan doi de chay vong).
	$duration_seconds = max( 20, count( $filled_ids ) * 5 );

	ob_start();
	?>
	<div class="nntm-card-list__marquee">
		<div class="nntm-card-list__marquee-track" style="--nntm-marquee-duration: <?php echo esc_attr( (string) $duration_seconds ); ?>s;">
			<?php foreach ( $filled_ids as $i => $post_id ) : ?>
				<?php
				$card_html = nntm_render_card_markup( $post_id, $variant, $show_date, true, $show_category, $show_card_cta, $card_cta_label );
				echo nntm_card_list_wrap_marquee_item( $card_html, $i >= $unique_count ); // phpcs:ignore WordPress.Security.EscapeOutput -- da escape ben trong.
				?>
			<?php endforeach; ?>
			<?php foreach ( $filled_ids as $post_id ) : ?>
				<?php
				$card_html = nntm_render_card_markup( $post_id, $variant, $show_date, true, $show_category, $show_card_cta, $card_cta_label );
				echo nntm_card_list_wrap_marquee_item( $card_html, true ); // phpcs:ignore WordPress.Security.EscapeOutput -- da escape ben trong, ban nhan doi chi de marquee lien mach.
				?>
			<?php endforeach; ?>
		</div>

		<?php
		/*
		 * Hai nút mũi tên (21/08/2026) — hình dạng theo ĐÚNG mũi tên chuẩn của
		 * site (ô vuông trắng 58×58, ký tự ← / →) mà chủ dự án chỉ định làm mẫu
		 * qua .nntm-an-pham-carousel__nav; xem khối CSS "MŨI TÊN DÙNG CHUNG"
		 * trong blocks/card-list/style.css. Dùng CHUNG lớp
		 * .nntm-card-list__marquee-nav với băng nguồn YouTube (Gót Son / GITA
		 * CENTER) để cả site chỉ có MỘT bộ số đo cho mũi tên băng tự chạy.
		 *
		 * Nằm TRONG .nntm-card-list__marquee (không phải trong track đang bị
		 * animation dịch chuyển) và đè lên hai mép băng bằng position:absolute —
		 * cách này không thêm thẻ bọc nào, nên KHÔNG phá các quy tắc CSS đang
		 * nhắm thẳng vào .nntm-card-list__marquee như flex "order: 2" của dải
		 * Nghi Quỹ.
		 *
		 * <button type="button"> thật (không phải <a>): đây là hành động CUỘN
		 * băng, không điều hướng. Băng chạy vòng vô hạn nên hai nút không bao
		 * giờ bị vô hiệu hoá. Tắt JS thì băng vẫn tự chạy như trước và hai nút
		 * không làm gì — nên chỉ in ra khi dải thật sự có nhiều hơn một thẻ.
		 *
		 * Đếm trên danh sách ĐÃ LẤP ĐẦY, không phải số bài duy nhất: dải chạy
		 * vòng qua chính các bản lặp, nên chỉ cần dải dài hơn một thẻ là hai
		 * nút đã có việc để làm.
		 */
		if ( count( $filled_ids ) > 1 ) :
			echo nntm_card_list_render_marquee_nav(); // phpcs:ignore WordPress.Security.EscapeOutput -- da escape ben trong.
		endif;
		?>
	</div>
	<?php
	return trim( (string) ob_get_clean() );
}
