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
 * Vẽ HTML một thẻ cấp bậc (ảnh + tiêu đề + nút/khoá theo quyền).
 *
 * @param array $card Thẻ đã làm sạch (xem nntm_rank_card_clean_card()).
 * @return string
 */
function nntm_rank_card_render_card( array $card ): string {
	$can_access = nntm_rank_card_can_access( $card );

	ob_start();
	?>
	<div class="nntm-rank-card__card">
		<div class="nntm-rank-card__card-media">
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
		</div>

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
 * @param array $card       Thẻ đã làm sạch.
 * @param bool  $can_access Kết quả nntm_rank_card_can_access().
 * @return string
 */
function nntm_rank_card_render_cta( array $card, bool $can_access ): string {
	// ---------- 1. Đủ quyền ----------
	if ( $can_access ) {
		if ( '' !== $card['targetUrl'] ) {
			return sprintf(
				'<a class="nntm-rank-card__cta" href="%1$s">%2$s &rarr;</a>',
				esc_url( $card['targetUrl'] ),
				esc_html( $card['ctaLabel'] )
			);
		}

		// Không có trang đích: hiện chữ, KHÔNG tạo link chết.
		return sprintf(
			'<span class="nntm-rank-card__cta">%s &rarr;</span>',
			esc_html( $card['ctaLabel'] )
		);
	}

	// ---------- 2. Chưa đăng nhập ----------
	if ( ! is_user_logged_in() ) {
		$login_url = function_exists( 'nntm_login_url' )
			? nntm_login_url( $card['targetUrl'] )
			: wp_login_url( $card['targetUrl'] );

		return sprintf(
			'<a class="nntm-rank-card__cta" href="%1$s" data-nntm-auth-modal="dang-nhap" data-nntm-auth-redirect="%2$s">%3$s &rarr;</a>',
			esc_url( $login_url ),
			esc_url( $card['targetUrl'] ),
			esc_html( $card['ctaLabel'] )
		);
	}

	// ---------- 3. Đã đăng nhập nhưng chưa đủ cấp ----------
	$locked_text = 'kim_cuong' === $card['requiredAccess']
		? __( 'Cần cấp Kim Cương', 'nntm' )
		: __( 'Cần cấp Đại Sĩ', 'nntm' );

	return sprintf(
		'<span class="nntm-rank-card__cta nntm-rank-card__cta--khoa" aria-disabled="true">%s</span>',
		esc_html( $locked_text )
	);
}
