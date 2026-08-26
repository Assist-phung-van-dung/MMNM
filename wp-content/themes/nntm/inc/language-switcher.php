<?php

defined( 'ABSPATH' ) || exit;

/**
 * Nút đổi ngôn ngữ một nhấn: luôn hiện nhãn của ngôn ngữ *còn lại*.
 *
 * Đang tiếng Việt thì nút là "EN" và nhấn vào sang tiếng Anh, và ngược lại.
 */
function nntm_render_language_switcher(): void {
	$hien_tai = function_exists( 'pll_current_language' ) ? pll_current_language( 'slug' ) : '';
	$hien_tai = $hien_tai ?: ( 0 === strpos( get_locale(), 'vi' ) ? 'vi' : 'en' );
	$hien_tai = 'en' === $hien_tai ? 'en' : 'vi';

	$dich = 'vi' === $hien_tai ? 'en' : 'vi';

	$nhan = array(
		'vi' => 'VN',
		'en' => 'EN',
	);

	$mo_ta = array(
		'vi' => __( 'Chuyển sang tiếng Việt', 'nntm' ),
		'en' => __( 'Chuyển sang tiếng Anh', 'nntm' ),
	);

	$duong_dan = '';

	if ( function_exists( 'pll_the_languages' ) ) {
		$danh_sach = pll_the_languages(
			array(
				'raw'                    => 1,
				'hide_if_empty'          => 0,
				'hide_if_no_translation' => 0,
			)
		);

		if ( is_array( $danh_sach ) ) {
			foreach ( $danh_sach as $ngon_ngu ) {
				if ( isset( $ngon_ngu['slug'], $ngon_ngu['url'] ) && $dich === $ngon_ngu['slug'] ) {
					$duong_dan = (string) $ngon_ngu['url'];
					break;
				}
			}
		}
	}

	if ( '' === $duong_dan ) {
		$duong_dan = home_url( '/' );
	}
	?>
	<a
		class="nntm-lang-select nntm-lang-select__toggle"
		href="<?php echo esc_url( $duong_dan ); ?>"
		hreflang="<?php echo esc_attr( $dich ); ?>"
		lang="<?php echo esc_attr( $dich ); ?>"
		rel="alternate"
		title="<?php echo esc_attr( $mo_ta[ $dich ] ); ?>"
	>
		<span aria-hidden="true"><?php echo esc_html( $nhan[ $dich ] ); ?></span>
		<span class="nntm-sr-only"><?php echo esc_html( $mo_ta[ $dich ] ); ?></span>
	</a>
	<?php
}
