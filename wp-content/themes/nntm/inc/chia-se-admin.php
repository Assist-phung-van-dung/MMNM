<?php

defined( 'ABSPATH' ) || exit;

function nntm_chia_se_dang_ky_cai_dat(): void {
	register_setting(
		'nntm_chia_se',
		NNTM_CHIA_SE_OPTION,
		array(
			'type'              => 'array',
			'sanitize_callback' => 'nntm_chia_se_lam_sach',
			'default'           => array(),
		)
	);
}
add_action( 'admin_init', 'nntm_chia_se_dang_ky_cai_dat' );

/**
 * Chỉ nhận http, https và mailto. Giữ nguyên {url} và {tieu_de} nên không dùng
 * esc_url_raw được — hàm đó nuốt mất dấu ngoặc nhọn.
 */
function nntm_chia_se_lam_sach_mau_url( $gia_tri ): string {
	$mau = trim( wp_strip_all_tags( (string) $gia_tri ) );
	$mau = preg_replace( '/\s+/', '', $mau );

	if ( '' === $mau ) {
		return '';
	}

	if ( ! preg_match( '#^(https?://|mailto:)#i', $mau ) ) {
		return '';
	}

	return $mau;
}

/**
 * @param mixed $dau_vao
 * @return array<string,mixed>
 */
function nntm_chia_se_lam_sach( $dau_vao ): array {
	$danh_muc = nntm_chia_se_danh_muc();

	$sach = array(
		'mang' => array(),
		'them' => array(),
	);

	$mang = is_array( $dau_vao ) && isset( $dau_vao['mang'] ) && is_array( $dau_vao['mang'] ) ? $dau_vao['mang'] : array();
	$them = is_array( $dau_vao ) && isset( $dau_vao['them'] ) && is_array( $dau_vao['them'] ) ? $dau_vao['them'] : array();

	foreach ( $danh_muc as $khoa => $goc ) {
		$hang = isset( $mang[ $khoa ] ) && is_array( $mang[ $khoa ] ) ? $mang[ $khoa ] : array();

		$mau_url = nntm_chia_se_lam_sach_mau_url( isset( $hang['mau_url'] ) ? $hang['mau_url'] : '' );

		// Trùng mẫu gốc thì không cần lưu lại.
		if ( isset( $goc['mau_url'] ) && $mau_url === $goc['mau_url'] ) {
			$mau_url = '';
		}

		$sach['mang'][ $khoa ] = array(
			'bat'     => ! empty( $hang['bat'] ) ? 1 : 0,
			'thu_tu'  => isset( $hang['thu_tu'] ) ? max( 0, min( 999, (int) $hang['thu_tu'] ) ) : 999,
			'mau_url' => $mau_url,
		);
	}

	for ( $i = 0; $i < NNTM_CHIA_SE_SO_O_THEM; $i++ ) {
		$hang = isset( $them[ $i ] ) && is_array( $them[ $i ] ) ? $them[ $i ] : array();

		$ten = sanitize_text_field( (string) ( isset( $hang['ten'] ) ? $hang['ten'] : '' ) );
		$mau = sanitize_hex_color( (string) ( isset( $hang['mau'] ) ? $hang['mau'] : '' ) );

		$sach['them'][ $i ] = array(
			'ten'     => $ten,
			'mau_url' => nntm_chia_se_lam_sach_mau_url( isset( $hang['mau_url'] ) ? $hang['mau_url'] : '' ),
			'mau'     => is_string( $mau ) ? $mau : '',
			'thu_tu'  => isset( $hang['thu_tu'] ) ? max( 0, min( 999, (int) $hang['thu_tu'] ) ) : 900 + $i,
		);
	}

	return $sach;
}

function nntm_chia_se_them_trang(): void {
	add_options_page(
		__( 'Nút chia sẻ', 'nntm' ),
		__( 'Nút chia sẻ', 'nntm' ),
		'manage_options',
		'nntm-chia-se',
		'nntm_chia_se_ve_trang'
	);
}
add_action( 'admin_menu', 'nntm_chia_se_them_trang' );

function nntm_chia_se_ve_trang(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$danh_muc = nntm_chia_se_danh_muc();
	$tuy_chon = nntm_chia_se_tuy_chon();
	$mac_dinh = nntm_chia_se_mac_dinh();
	$chua_cai = empty( $tuy_chon['mang'] ) && empty( $tuy_chon['them'] );
	$ten_o    = NNTM_CHIA_SE_OPTION;
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Nút chia sẻ', 'nntm' ); ?></h1>

		<p>
			<?php esc_html_e( 'Chọn những mạng xã hội sẽ hiện ra khi khách bấm nút “Chia sẻ” ở cuối bài. Số thứ tự nhỏ thì đứng trước.', 'nntm' ); ?>
		</p>
		<p>
			<?php
			printf(
				/* translators: %1$s và %2$s là hai chỗ thay thế trong đường dẫn chia sẻ. */
				esc_html__( 'Trong ô đường dẫn, %1$s sẽ được thay bằng link bài viết và %2$s bằng tiêu đề bài.', 'nntm' ),
				'<code>{url}</code>',
				'<code>{tieu_de}</code>'
			);
			?>
		</p>

		<form method="post" action="options.php">
			<?php settings_fields( 'nntm_chia_se' ); ?>

			<h2><?php esc_html_e( 'Mạng dựng sẵn', 'nntm' ); ?></h2>

			<table class="widefat striped" style="max-width:1100px">
				<thead>
					<tr>
						<th style="width:70px"><?php esc_html_e( 'Hiện', 'nntm' ); ?></th>
						<th style="width:60px"><?php esc_html_e( 'Icon', 'nntm' ); ?></th>
						<th style="width:180px"><?php esc_html_e( 'Tên', 'nntm' ); ?></th>
						<th style="width:90px"><?php esc_html_e( 'Thứ tự', 'nntm' ); ?></th>
						<th><?php esc_html_e( 'Đường dẫn chia sẻ', 'nntm' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php
					foreach ( $danh_muc as $khoa => $mang ) :
						$hang = isset( $tuy_chon['mang'][ $khoa ] ) && is_array( $tuy_chon['mang'][ $khoa ] )
							? $tuy_chon['mang'][ $khoa ]
							: array();

						if ( $chua_cai ) {
							$bat    = isset( $mac_dinh[ $khoa ] );
							$thu_tu = $bat ? $mac_dinh[ $khoa ] : 999;
						} else {
							$bat    = ! empty( $hang['bat'] );
							$thu_tu = isset( $hang['thu_tu'] ) ? (int) $hang['thu_tu'] : 999;
						}

						$mau_url_goc = isset( $mang['mau_url'] ) ? (string) $mang['mau_url'] : '';
						$mau_url     = isset( $hang['mau_url'] ) && '' !== trim( (string) $hang['mau_url'] )
							? (string) $hang['mau_url']
							: $mau_url_goc;

						$la_sao_chep = isset( $mang['kieu'] ) && 'sao_chep' === $mang['kieu'];
						?>
						<tr>
							<td>
								<input type="checkbox" value="1" <?php checked( $bat ); ?>
									name="<?php echo esc_attr( $ten_o ); ?>[mang][<?php echo esc_attr( $khoa ); ?>][bat]" />
							</td>
							<td>
								<span style="display:inline-flex;align-items:center;justify-content:center;width:30px;height:30px;border-radius:50%;color:#fff;background-color:<?php echo esc_attr( isset( $mang['mau'] ) ? $mang['mau'] : '#8A6E3B' ); ?>">
									<span style="display:block;width:18px;height:18px">
										<?php echo nntm_chia_se_bieu_tuong( (string) $khoa, (string) $mang['ten'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
									</span>
								</span>
							</td>
							<td><strong><?php echo esc_html( (string) $mang['ten'] ); ?></strong></td>
							<td>
								<input type="number" min="0" max="999" style="width:80px"
									name="<?php echo esc_attr( $ten_o ); ?>[mang][<?php echo esc_attr( $khoa ); ?>][thu_tu]"
									value="<?php echo esc_attr( (string) $thu_tu ); ?>" />
							</td>
							<td>
								<?php if ( $la_sao_chep ) : ?>
									<em><?php esc_html_e( 'Không chia sẻ được bằng đường dẫn — bấm vào sẽ copy link để tự dán.', 'nntm' ); ?></em>
									<input type="hidden" name="<?php echo esc_attr( $ten_o ); ?>[mang][<?php echo esc_attr( $khoa ); ?>][mau_url]" value="" />
								<?php else : ?>
									<input type="text" class="large-text code"
										name="<?php echo esc_attr( $ten_o ); ?>[mang][<?php echo esc_attr( $khoa ); ?>][mau_url]"
										value="<?php echo esc_attr( $mau_url ); ?>" />
								<?php endif; ?>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>

			<h2><?php esc_html_e( 'Thêm mạng khác', 'nntm' ); ?></h2>

			<p><?php esc_html_e( 'Điền tên và đường dẫn chia sẻ là có thêm một mạng nữa. Bỏ trống thì bỏ qua. Icon là chữ cái đầu của tên.', 'nntm' ); ?></p>

			<table class="widefat striped" style="max-width:1100px">
				<thead>
					<tr>
						<th style="width:180px"><?php esc_html_e( 'Tên', 'nntm' ); ?></th>
						<th style="width:110px"><?php esc_html_e( 'Màu', 'nntm' ); ?></th>
						<th style="width:90px"><?php esc_html_e( 'Thứ tự', 'nntm' ); ?></th>
						<th><?php esc_html_e( 'Đường dẫn chia sẻ', 'nntm' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php
					for ( $i = 0; $i < NNTM_CHIA_SE_SO_O_THEM; $i++ ) :
						$hang = isset( $tuy_chon['them'][ $i ] ) && is_array( $tuy_chon['them'][ $i ] )
							? $tuy_chon['them'][ $i ]
							: array();
						?>
						<tr>
							<td>
								<input type="text" class="regular-text"
									name="<?php echo esc_attr( $ten_o ); ?>[them][<?php echo (int) $i; ?>][ten]"
									value="<?php echo esc_attr( (string) ( isset( $hang['ten'] ) ? $hang['ten'] : '' ) ); ?>" />
							</td>
							<td>
								<input type="text" placeholder="#0068FF" style="width:100px"
									name="<?php echo esc_attr( $ten_o ); ?>[them][<?php echo (int) $i; ?>][mau]"
									value="<?php echo esc_attr( (string) ( isset( $hang['mau'] ) ? $hang['mau'] : '' ) ); ?>" />
							</td>
							<td>
								<input type="number" min="0" max="999" style="width:80px"
									name="<?php echo esc_attr( $ten_o ); ?>[them][<?php echo (int) $i; ?>][thu_tu]"
									value="<?php echo esc_attr( (string) ( isset( $hang['thu_tu'] ) ? (int) $hang['thu_tu'] : 900 + $i ) ); ?>" />
							</td>
							<td>
								<input type="text" class="large-text code" placeholder="https://…?u={url}"
									name="<?php echo esc_attr( $ten_o ); ?>[them][<?php echo (int) $i; ?>][mau_url]"
									value="<?php echo esc_attr( (string) ( isset( $hang['mau_url'] ) ? $hang['mau_url'] : '' ) ); ?>" />
							</td>
						</tr>
					<?php endfor; ?>
				</tbody>
			</table>

			<?php submit_button(); ?>
		</form>
	</div>
	<?php
}
