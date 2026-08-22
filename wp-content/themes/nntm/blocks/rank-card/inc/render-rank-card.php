<?php
/**
 * Hàm dùng chung cho render.php của block nntm/rank-card.
 *
 * Nạp bằng require_once ở render.php — tách riêng file này để tránh lỗi
 * "Cannot redeclare function" khi render.php của block bị WordPress core
 * `require` (không phải `require_once`) nhiều lần trong cùng một request
 * (ví dụ ServerSideRender trong trình soạn thảo gọi lại render_block()).
 *
 * @package NNTM
 */

defined( 'ABSPATH' ) || exit;

/**
 * Làm sạch một thẻ cấp bậc thô từ attributes, trả về mảng đã chuẩn hoá
 * hoặc null nếu thẻ hoàn toàn trống (không tiêu đề, không ảnh, không link).
 *
 * @param array $raw_card Dữ liệu thô của một thẻ.
 * @return array|null
 */
function nntm_rank_card_clean_card( array $raw_card ): ?array {
	$title           = isset( $raw_card['title'] ) ? trim( (string) $raw_card['title'] ) : '';
	$image_id        = isset( $raw_card['imageId'] ) ? absint( $raw_card['imageId'] ) : 0;
	$image_url       = isset( $raw_card['imageUrl'] ) ? esc_url_raw( (string) $raw_card['imageUrl'] ) : '';
	$image_alt       = isset( $raw_card['imageAlt'] ) ? trim( (string) $raw_card['imageAlt'] ) : '';
	$cta_label       = isset( $raw_card['ctaLabel'] ) && '' !== trim( (string) $raw_card['ctaLabel'] )
		? sanitize_text_field( (string) $raw_card['ctaLabel'] )
		: __( 'Mời vào', 'nntm' );
	$target_url      = isset( $raw_card['targetUrl'] ) ? esc_url_raw( (string) $raw_card['targetUrl'] ) : '';
	$required_access = isset( $raw_card['requiredAccess'] ) ? sanitize_key( (string) $raw_card['requiredAccess'] ) : 'login';

	if ( ! in_array( $required_access, array( 'public', 'login', 'dai_si', 'kim_cuong' ), true ) ) {
		$required_access = 'login';
	}

	// Thẻ hoàn toàn trống (chưa nhập gì) thì bỏ qua — tránh hiện thẻ rỗng
	// ngoài trang thật nếu khách bấm "Thêm thẻ" rồi đổi ý không điền gì.
	if ( '' === $title && 0 === $image_id && '' === $image_url && '' === $target_url ) {
		return null;
	}

	return array(
		'title'          => $title,
		'imageId'        => $image_id,
		'imageUrl'       => $image_url,
		'imageAlt'       => $image_alt,
		'ctaLabel'       => $cta_label,
		'targetUrl'      => $target_url,
		'requiredAccess' => $required_access,
	);
}

/**
 * Tính thẻ này có được truy cập hay không theo cấp thành viên hiện tại.
 *
 * @param array $card Thẻ đã làm sạch (xem nntm_rank_card_clean_card()).
 * @return bool
 */
function nntm_rank_card_can_access( array $card ): bool {
	// Ban quản trị luôn qua được, để xem thử mọi thẻ (yêu cầu nhiệm vụ).
	if ( current_user_can( 'manage_options' ) ) {
		$can_access = true;
	} else {
		$rank        = function_exists( 'nntm_user_rank' ) ? nntm_user_rank() : null;
		$logged_in   = is_user_logged_in();
		$required    = $card['requiredAccess'];

		switch ( $required ) {
			case 'public':
				$can_access = true;
				break;
			case 'dai_si':
				$can_access = $logged_in && in_array( $rank, array( 'dai_si', 'kim_cuong' ), true );
				break;
			case 'kim_cuong':
				$can_access = $logged_in && 'kim_cuong' === $rank;
				break;
			case 'login':
			default:
				$can_access = $logged_in;
				break;
		}
	}

	/*
	 * Bọc qua filter để sau này khách chốt lại mức quyền thì đổi một dòng
	 * (add_filter trong functions.php của một phần việc khác), không cần
	 * sửa code của block này.
	 */
	return (bool) apply_filters( 'nntm_rank_card_can_access', $can_access, $card, get_current_user_id() );
}

/**
 * Nơi một thẻ dẫn tới, tính MỘT LẦN rồi dùng cho CẢ nút "Mời vào" LẪN ảnh.
 *
 * Thêm 21/08/2026 theo yêu cầu chủ dự án: "nhấn vào ảnh cũng có link như mời
 * vào". Trước đó chỉ nút có link; tách ra hàm này để ảnh và nút không bao giờ
 * dẫn đi hai nơi khác nhau, và để ba trường hợp quyền chỉ được viết một lần.
 *
 * Trả null ở hai trường hợp KHÔNG có nơi nào để đi (khi đó ảnh giữ nguyên là
 * ảnh, không bọc link chết):
 *   - đủ quyền nhưng khách chưa nhập trang đích;
 *   - đã đăng nhập mà chưa đủ cấp (thẻ đang khoá).
 *
 * @param array $card       Thẻ đã làm sạch.
 * @param bool  $can_access Kết quả nntm_rank_card_can_access().
 * @return array{url:string,attrs:string}|null attrs là chuỗi thuộc tính đã
 *         esc_attr(), kèm khoảng trắng đầu, dán thẳng vào thẻ <a> được.
 */
function nntm_rank_card_lien_ket( array $card, bool $can_access ): ?array {
	// ---------- 1. Đủ quyền ----------
	if ( $can_access ) {
		if ( '' === $card['targetUrl'] ) {
			return null; // Không có trang đích: KHÔNG tạo link chết.
		}

		return array(
			'url'   => $card['targetUrl'],
			'attrs' => '',
		);
	}

	// ---------- 2. Chưa đăng nhập: mở popup đăng nhập ----------
	if ( ! is_user_logged_in() ) {
		$login_url = function_exists( 'nntm_login_url' )
			? nntm_login_url( $card['targetUrl'] )
			: wp_login_url( $card['targetUrl'] );

		/*
		 * assets/js/auth-modal.js bắt click theo [data-nntm-auth-modal] ở cấp
		 * document, nên gắn đúng hai thuộc tính này lên ảnh là ảnh cũng mở
		 * popup đăng nhập y như nút. href vẫn là dự phòng khi tắt JS.
		 */
		return array(
			'url'   => $login_url,
			'attrs' => sprintf(
				' data-nntm-auth-modal="dang-nhap" data-nntm-auth-redirect="%s"',
				esc_url( $card['targetUrl'] )
			),
		);
	}

	// ---------- 3. Đã đăng nhập nhưng chưa đủ cấp: thẻ khoá ----------
	return null;
}

/**
 * Vẽ HTML một thẻ cấp bậc (ảnh + tiêu đề + nút/khoá theo quyền).
 *
 * @param array $card Thẻ đã làm sạch (xem nntm_rank_card_clean_card()).
 * @return string
 */
function nntm_rank_card_render_card( array $card ): string {
	$can_access = nntm_rank_card_can_access( $card );
	$lien_ket   = nntm_rank_card_lien_ket( $card, $can_access );

	/*
	 * Ảnh có link thì chính thẻ bọc ảnh ĐỔI TỪ <div> SANG <a> — giữ nguyên
	 * class .nntm-rank-card__card-media nên toàn bộ CSS (display:flex,
	 * justify-content:center) áp dụng y như cũ, không sinh thêm một tầng thẻ.
	 *
	 * tabindex="-1": nút "Mời vào" ngay dưới đã trỏ ĐÚNG cùng một nơi, nên
	 * link ảnh chỉ dành cho chuột — để nó vào thứ tự Tab là mỗi thẻ có hai
	 * điểm dừng bàn phím trùng đích, đúng cái bẫy đã ghi ở
	 * blocks/card-list/inc/render-card-list-marquee.php.
	 *
	 * aria-hidden CHỈ khi ảnh là trang trí (không có mô tả): lúc đó link ảnh
	 * hoàn toàn không mang thông tin gì, ẩn đi để trình đọc màn hình không
	 * đọc thừa một liên kết rỗng. Ảnh CÓ mô tả thì KHÔNG ẩn — ẩn sẽ mất luôn
	 * phần mô tả ảnh.
	 */
	$media_the   = ( null !== $lien_ket ) ? 'a' : 'div';
	$media_attrs = '';
	if ( null !== $lien_ket ) {
		$media_attrs = ' href="' . esc_url( $lien_ket['url'] ) . '"' . $lien_ket['attrs'] . ' tabindex="-1"';
		if ( '' === $card['imageAlt'] ) {
			$media_attrs .= ' aria-hidden="true"';
		}
	}

	ob_start();
	?>
	<div class="nntm-rank-card__card">
		<<?php echo esc_html( $media_the ); ?> class="nntm-rank-card__card-media"<?php echo $media_attrs; // phpcs:ignore WordPress.Security.EscapeOutput -- tung gia tri da esc_url()/esc_attr() o tren, phan con lai la chuoi tinh. ?>>
			<?php
			$is_decorative = ( '' === $card['imageAlt'] );
			if ( $card['imageId'] > 0 ) :
				$image_attrs = array(
					'class'   => 'nntm-rank-card__card-img',
					'loading' => 'lazy',
					'alt'     => $card['imageAlt'],
				);
				if ( $is_decorative ) {
					$image_attrs['role'] = 'presentation';
				}
				echo wp_kses_post( wp_get_attachment_image( $card['imageId'], 'large', false, $image_attrs ) );
			elseif ( '' !== $card['imageUrl'] ) :
				?>
				<img
					class="nntm-rank-card__card-img"
					src="<?php echo esc_url( $card['imageUrl'] ); ?>"
					alt="<?php echo esc_attr( $card['imageAlt'] ); ?>"
					loading="lazy"
					<?php echo $is_decorative ? 'role="presentation"' : ''; // phpcs:ignore WordPress.Security.EscapeOutput -- gia tri co dinh, khong tu du lieu nguoi dung. ?>
				/>
				<?php
			else :
				?>
				<span class="nntm-rank-card__card-img nntm-rank-card__card-img--placeholder" aria-hidden="true"></span>
				<?php
			endif;
			?>
		</<?php echo esc_html( $media_the ); ?>>

		<?php if ( '' !== $card['title'] ) : ?>
			<p class="nntm-rank-card__card-title"><?php echo esc_html( $card['title'] ); ?></p>
		<?php endif; ?>

		<?php echo nntm_rank_card_render_cta( $card, $can_access ); // phpcs:ignore WordPress.Security.EscapeOutput -- ham con da tu esc trong. ?>
	</div>
	<?php
	return (string) ob_get_clean();
}

/**
 * Vẽ nút/khoá của một thẻ theo 3 trường hợp: đủ quyền, chưa đăng nhập,
 * hoặc đã đăng nhập nhưng chưa đủ cấp.
 *
 * Đích đến lấy từ nntm_rank_card_lien_ket() — CÙNG hàm mà ảnh của thẻ đang
 * dùng, nên nút và ảnh không thể dẫn đi hai nơi khác nhau.
 *
 * @param array $card       Thẻ đã làm sạch.
 * @param bool  $can_access Kết quả nntm_rank_card_can_access().
 * @return string
 */
function nntm_rank_card_render_cta( array $card, bool $can_access ): string {
	$lien_ket = nntm_rank_card_lien_ket( $card, $can_access );

	// ---------- 1 & 2. Có nơi để đi (đủ quyền, hoặc chưa đăng nhập -> popup) ----------
	if ( null !== $lien_ket ) {
		return sprintf(
			'<a class="nntm-rank-card__cta" href="%1$s"%2$s>%3$s &rarr;</a>',
			esc_url( $lien_ket['url'] ),
			$lien_ket['attrs'], // Đã esc_attr()/esc_url() bên trong nntm_rank_card_lien_ket().
			esc_html( $card['ctaLabel'] )
		);
	}

	// ---------- 3. Đã đăng nhập nhưng chưa đủ cấp: thẻ khoá ----------
	if ( ! $can_access ) {
		$locked_text = 'kim_cuong' === $card['requiredAccess']
			? __( 'Cần cấp Kim Cương', 'nntm' )
			: __( 'Cần cấp Đại Sĩ', 'nntm' );

		return sprintf(
			'<span class="nntm-rank-card__cta nntm-rank-card__cta--khoa" aria-disabled="true">%s</span>',
			esc_html( $locked_text )
		);
	}

	// ---------- 4. Đủ quyền nhưng khách chưa nhập trang đích ----------
	// Hiện chữ như cũ, KHÔNG tạo link chết.
	return sprintf(
		'<span class="nntm-rank-card__cta">%s &rarr;</span>',
		esc_html( $card['ctaLabel'] )
	);
}
