<?php

defined( 'ABSPATH' ) || exit;

$nntm_search_id = wp_unique_id( 'nntm-search-' );
?>
<form role="search" method="get" class="nntm-search-form" action="<?php echo esc_url( home_url( '/' ) ); ?>">
	<label for="<?php echo esc_attr( $nntm_search_id ); ?>" class="nntm-sr-only">
		<?php esc_html_e( 'Tìm kiếm', 'nntm' ); ?>
	</label>
	<input
		type="search"
		id="<?php echo esc_attr( $nntm_search_id ); ?>"
		class="nntm-search-form__field"
		placeholder="<?php esc_attr_e( 'Tìm bài viết, pháp thoại…', 'nntm' ); ?>"
		value="<?php echo esc_attr( get_search_query() ); ?>"
		name="s"
	/>
	<button type="submit" class="nntm-search-form__submit">
		<span class="nntm-sr-only"><?php esc_html_e( 'Tìm kiếm', 'nntm' ); ?></span>
		<span aria-hidden="true">&#128269;</span>
	</button>
</form>
