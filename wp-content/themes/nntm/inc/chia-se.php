<?php

defined( 'ABSPATH' ) || exit;

const NNTM_CHIA_SE_OPTION    = 'nntm_chia_se_mang';
const NNTM_CHIA_SE_SO_O_THEM = 3;

/**
 * Danh mục mạng xã hội dựng sẵn.
 *
 * kieu = 'mo'       → mở cửa sổ chia sẻ theo mau_url
 * kieu = 'sao_chep' → chép link vào bộ nhớ tạm; Instagram không cho chia sẻ link
 *                     qua đường dẫn nên chỉ chép để người dùng tự dán
 *
 * Chỗ {url} và {tieu_de} trong mau_url sẽ được thay bằng link và tiêu đề bài.
 *
 * @return array<string,array<string,mixed>>
 */
function nntm_chia_se_danh_muc(): array {
	return (array) apply_filters(
		'nntm_chia_se_danh_muc',
		array(
			'zalo'      => array(
				'ten'     => 'Zalo',
				'mau'     => '#0068FF',
				'kieu'    => 'mo',
				'mau_url' => 'https://sp.zalo.me/plugins/share?u={url}',
			),
			'facebook'  => array(
				'ten'     => 'Facebook',
				'mau'     => '#1877F2',
				'kieu'    => 'mo',
				'mau_url' => 'https://www.facebook.com/sharer/sharer.php?u={url}',
			),
			'instagram' => array(
				'ten'  => 'Instagram',
				'mau'  => '#E1306C',
				'kieu' => 'sao_chep',
				'xong' => 'Đã copy — dán vào Instagram',
			),
			'x'         => array(
				'ten'     => 'X (Twitter)',
				'mau'     => '#111111',
				'kieu'    => 'mo',
				'mau_url' => 'https://twitter.com/intent/tweet?url={url}&text={tieu_de}',
			),
			'telegram'  => array(
				'ten'     => 'Telegram',
				'mau'     => '#229ED9',
				'kieu'    => 'mo',
				'mau_url' => 'https://t.me/share/url?url={url}&text={tieu_de}',
			),
			'email'     => array(
				'ten'     => 'Email',
				'mau'     => '#6B7280',
				'kieu'    => 'mo',
				'mau_url' => 'mailto:?subject={tieu_de}&body={url}',
			),
			'sao-chep'  => array(
				'ten'  => 'Sao chép link',
				'mau'  => '#8A6E3B',
				'kieu' => 'sao_chep',
			),
		)
	);
}

/**
 * Các mạng bật sẵn khi chưa ai vào cài đặt.
 *
 * @return array<string,int>
 */
function nntm_chia_se_mac_dinh(): array {
	return array(
		'zalo'      => 10,
		'facebook'  => 20,
		'instagram' => 30,
	);
}

/**
 * @return array<string,mixed>
 */
function nntm_chia_se_tuy_chon(): array {
	$luu = get_option( NNTM_CHIA_SE_OPTION, null );

	if ( ! is_array( $luu ) ) {
		return array(
			'mang' => array(),
			'them' => array(),
		);
	}

	return array(
		'mang' => isset( $luu['mang'] ) && is_array( $luu['mang'] ) ? $luu['mang'] : array(),
		'them' => isset( $luu['them'] ) && is_array( $luu['them'] ) ? $luu['them'] : array(),
	);
}

/**
 * Danh sách mạng đang bật, đã xếp theo thứ tự, sẵn sàng để vẽ.
 *
 * @return array<int,array<string,mixed>>
 */
function nntm_chia_se_dang_bat(): array {
	$danh_muc = nntm_chia_se_danh_muc();
	$tuy_chon = nntm_chia_se_tuy_chon();
	$mac_dinh = nntm_chia_se_mac_dinh();

	$chua_cai = empty( $tuy_chon['mang'] ) && empty( $tuy_chon['them'] );

	$ket_qua = array();

	foreach ( $danh_muc as $khoa => $mang ) {
		$rieng = isset( $tuy_chon['mang'][ $khoa ] ) && is_array( $tuy_chon['mang'][ $khoa ] )
			? $tuy_chon['mang'][ $khoa ]
			: array();

		if ( $chua_cai ) {
			$bat    = isset( $mac_dinh[ $khoa ] );
			$thu_tu = $bat ? $mac_dinh[ $khoa ] : 999;
		} else {
			$bat    = ! empty( $rieng['bat'] );
			$thu_tu = isset( $rieng['thu_tu'] ) ? (int) $rieng['thu_tu'] : 999;
		}

		if ( ! $bat ) {
			continue;
		}

		$mau_url = isset( $rieng['mau_url'] ) && '' !== trim( (string) $rieng['mau_url'] )
			? (string) $rieng['mau_url']
			: (string) ( isset( $mang['mau_url'] ) ? $mang['mau_url'] : '' );

		$ket_qua[] = array(
			'khoa'    => $khoa,
			'ten'     => (string) $mang['ten'],
			'mau'     => (string) ( isset( $mang['mau'] ) ? $mang['mau'] : '#8A6E3B' ),
			'kieu'    => (string) ( isset( $mang['kieu'] ) ? $mang['kieu'] : 'mo' ),
			'mau_url' => $mau_url,
			'xong'    => (string) ( isset( $mang['xong'] ) ? $mang['xong'] : __( 'Đã copy link', 'nntm' ) ),
			'thu_tu'  => $thu_tu,
		);
	}

	foreach ( $tuy_chon['them'] as $i => $them ) {
		if ( ! is_array( $them ) ) {
			continue;
		}

		$ten     = trim( (string) ( isset( $them['ten'] ) ? $them['ten'] : '' ) );
		$mau_url = trim( (string) ( isset( $them['mau_url'] ) ? $them['mau_url'] : '' ) );

		if ( '' === $ten || '' === $mau_url ) {
			continue;
		}

		$mau = trim( (string) ( isset( $them['mau'] ) ? $them['mau'] : '' ) );

		$ket_qua[] = array(
			'khoa'    => 'them-' . (int) $i,
			'ten'     => $ten,
			'mau'     => '' !== $mau ? $mau : '#8A6E3B',
			'kieu'    => 'mo',
			'mau_url' => $mau_url,
			'xong'    => __( 'Đã copy link', 'nntm' ),
			'thu_tu'  => isset( $them['thu_tu'] ) ? (int) $them['thu_tu'] : 900 + (int) $i,
		);
	}

	usort(
		$ket_qua,
		static function ( array $a, array $b ): int {
			return $a['thu_tu'] <=> $b['thu_tu'];
		}
	);

	return $ket_qua;
}

/**
 * Biểu tượng trắng đặt trên nền tròn màu thương hiệu.
 *
 * Đây là hình vẽ giản lược, không phải logo gốc. Muốn thay bằng SVG chính chủ
 * thì móc vào bộ lọc nntm_chia_se_bieu_tuong.
 */
function nntm_chia_se_bieu_tuong( string $khoa, string $ten ): string {
	$rieng = apply_filters( 'nntm_chia_se_bieu_tuong', '', $khoa, $ten );

	if ( is_string( $rieng ) && '' !== trim( $rieng ) ) {
		return $rieng;
	}

	$mo  = '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">';
	$het = '</svg>';

	switch ( $khoa ) {
		case 'facebook':
			return '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path fill="currentColor" d="M13.6 21v-7.3h2.5l.4-2.9h-2.9V8.9c0-.8.2-1.4 1.4-1.4h1.6V4.9c-.3 0-1.2-.1-2.2-.1-2.2 0-3.8 1.4-3.8 3.9v2.1H8.1v2.9h2.5V21h3z"/></svg>';

		case 'instagram':
			return $mo . '<rect x="4" y="4" width="16" height="16" rx="5"/><circle cx="12" cy="12" r="3.5"/><circle cx="16.7" cy="7.3" r="1" fill="currentColor" stroke="none"/>' . $het;

		case 'x':
			return $mo . '<path d="M5 5l14 14M19 5L5 19"/>' . $het;

		case 'telegram':
			return '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path fill="currentColor" d="M21.2 4.3 2.9 11.2c-.9.3-.9 1.6.1 1.9l4.5 1.4 1.7 5c.3.8 1.3 1 1.9.3l2.3-2.4 4.4 3.2c.7.5 1.7.1 1.9-.7l3-14.2c.2-.9-.7-1.7-1.5-1.4zM9.4 14.5l-.4 3.2-1.1-3.3 8.6-5.6-7.1 5.7z"/></svg>';

		case 'email':
			return $mo . '<rect x="3.5" y="6" width="17" height="12" rx="2"/><path d="M4 7.6l8 5.4 8-5.4"/>' . $het;

		case 'sao-chep':
			return $mo . '<path d="M10.2 13.8a3.9 3.9 0 0 0 5.6 0l2.3-2.3a3.9 3.9 0 1 0-5.6-5.6l-1.3 1.3"/><path d="M13.8 10.2a3.9 3.9 0 0 0-5.6 0l-2.3 2.3a3.9 3.9 0 1 0 5.6 5.6l1.3-1.3"/>' . $het;

		case 'zalo':
		default:
			$chu = function_exists( 'mb_substr' ) ? mb_substr( $ten, 0, 1, 'UTF-8' ) : substr( $ten, 0, 1 );
			$chu = function_exists( 'mb_strtoupper' ) ? mb_strtoupper( $chu, 'UTF-8' ) : strtoupper( $chu );

			return '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><text x="12" y="12" text-anchor="middle" dominant-baseline="central" font-size="12" font-weight="700" fill="currentColor">' . esc_html( $chu ) . '</text></svg>';
	}
}

function nntm_chia_se_dung_mau( string $mau_url, string $url, string $tieu_de ): string {
	return str_replace(
		array( '{url}', '{tieu_de}' ),
		array( rawurlencode( $url ), rawurlencode( $tieu_de ) ),
		$mau_url
	);
}

/**
 * Vẽ nút Chia sẻ kèm bảng chọn mạng xã hội.
 *
 * @param int                  $post_id  Bài cần chia sẻ, 0 là bài đang xem.
 * @param array<string,string> $tuy_chon class_nut, nhan.
 */
function nntm_render_chia_se( int $post_id = 0, array $tuy_chon = array() ): string {
	$post = get_post( $post_id > 0 ? $post_id : null );

	if ( ! $post instanceof WP_Post ) {
		return '';
	}

	$mang = nntm_chia_se_dang_bat();

	if ( empty( $mang ) ) {
		return '';
	}

	$url     = (string) get_permalink( $post );
	$tieu_de = (string) get_the_title( $post );
	$ma      = wp_unique_id( 'nntm-chia-se-' );

	$class_nut = isset( $tuy_chon['class_nut'] ) ? trim( (string) $tuy_chon['class_nut'] ) : '';
	$nhan      = isset( $tuy_chon['nhan'] ) && '' !== trim( (string) $tuy_chon['nhan'] )
		? (string) $tuy_chon['nhan']
		: __( 'Chia sẻ', 'nntm' );

	ob_start();
	?>
	<div class="nntm-chia-se" data-nntm-chia-se>
		<button
			type="button"
			class="nntm-chia-se__nut<?php echo '' !== $class_nut ? ' ' . esc_attr( $class_nut ) : ''; ?>"
			aria-expanded="false"
			aria-haspopup="true"
			aria-controls="<?php echo esc_attr( $ma ); ?>"
			data-nntm-chia-se-nut
		>
			<span class="nntm-chia-se__nhan"><?php echo esc_html( $nhan ); ?></span>
		</button>

		<div class="nntm-chia-se__bang" id="<?php echo esc_attr( $ma ); ?>" hidden>
			<ul class="nntm-chia-se__ds">
				<?php foreach ( $mang as $m ) : ?>
					<li class="nntm-chia-se__o">
						<?php if ( 'sao_chep' === $m['kieu'] ) : ?>
							<button
								type="button"
								class="nntm-chia-se__muc nntm-sao-link"
								data-nntm-sao-link="<?php echo esc_url( $url ); ?>"
								data-nntm-sao-link-xong="<?php echo esc_attr( $m['xong'] ); ?>"
								data-nntm-sao-link-loi="<?php esc_attr_e( 'Không copy được', 'nntm' ); ?>"
							>
								<span class="nntm-chia-se__icon" style="background-color:<?php echo esc_attr( $m['mau'] ); ?>">
									<?php echo nntm_chia_se_bieu_tuong( $m['khoa'], $m['ten'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
								</span>
								<span class="nntm-chia-se__ten nntm-sao-link__nhan"><?php echo esc_html( $m['ten'] ); ?></span>
							</button>
						<?php else : ?>
							<a
								class="nntm-chia-se__muc"
								href="<?php echo esc_url( nntm_chia_se_dung_mau( $m['mau_url'], $url, $tieu_de ) ); ?>"
								target="_blank"
								rel="noopener noreferrer nofollow"
								data-nntm-chia-se-mo
							>
								<span class="nntm-chia-se__icon" style="background-color:<?php echo esc_attr( $m['mau'] ); ?>">
									<?php echo nntm_chia_se_bieu_tuong( $m['khoa'], $m['ten'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
								</span>
								<span class="nntm-chia-se__ten"><?php echo esc_html( $m['ten'] ); ?></span>
							</a>
						<?php endif; ?>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>
	</div>
	<?php
	return trim( (string) ob_get_clean() );
}

function nntm_chia_se_enqueue(): void {
	if ( ! is_singular() ) {
		return;
	}

	$css = NNTM_THEME_DIR . '/assets/css/chia-se.css';
	$js  = NNTM_THEME_DIR . '/assets/js/chia-se.js';

	wp_enqueue_style( 'nntm-chia-se', NNTM_THEME_URI . '/assets/css/chia-se.css', array( 'nntm-tokens' ), nntm_asset_version( $css ) );
	wp_enqueue_script( 'nntm-chia-se', NNTM_THEME_URI . '/assets/js/chia-se.js', array(), nntm_asset_version( $js ), true );
}
add_action( 'wp_enqueue_scripts', 'nntm_chia_se_enqueue', 41 );

require_once __DIR__ . '/chia-se-admin.php';
