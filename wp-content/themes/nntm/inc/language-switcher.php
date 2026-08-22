<?php

defined( 'ABSPATH' ) || exit;

function nntm_render_language_switcher(): void {
	$current = function_exists( 'pll_current_language' ) ? pll_current_language( 'slug' ) : '';
	$current = $current ?: ( 0 === strpos( get_locale(), 'vi' ) ? 'vi' : 'en' );

	$languages = array();
	if ( function_exists( 'pll_the_languages' ) ) {
		$raw = pll_the_languages(
			array(
				'raw'                    => 1,
				'hide_if_empty'          => 0,
				'hide_if_no_translation' => 0,
			)
		);
		if ( is_array( $raw ) ) {
			foreach ( $raw as $language ) {
				$languages[ $language['slug'] ] = $language;
			}
		}
	}

	$labels = array( 'vi' => 'VN', 'en' => 'EN' );
	?>
	<details class="nntm-lang-select">
		<summary class="nntm-lang-select__toggle" aria-label="<?php esc_attr_e( 'Chọn ngôn ngữ', 'nntm' ); ?>">
			<span><?php echo esc_html( $labels[ $current ] ?? 'VN' ); ?></span>
		</summary>
		<div class="nntm-lang-select__options">
	<?php
	foreach ( $labels as $slug => $label ) {
		$url      = isset( $languages[ $slug ]['url'] ) ? $languages[ $slug ]['url'] : home_url( '/' );
		$is_active = $slug === $current;
		?>
		<a
			class="nntm-lang-select__option <?php echo $is_active ? 'is-active' : ''; ?>"
			href="<?php echo esc_url( $url ); ?>"
			hreflang="<?php echo esc_attr( $slug ); ?>"
			lang="<?php echo esc_attr( $slug ); ?>"
			<?php echo $is_active ? 'aria-current="page"' : '';  ?>
		><?php echo esc_html( $label ); ?></a>
		<?php
	}
	?>
		</div>
	</details>
	<?php
}
