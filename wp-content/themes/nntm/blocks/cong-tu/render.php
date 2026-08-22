<?php

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/inc/render-cong-tu.php';

$nntm_ctb_program_id   = isset( $attributes['programId'] ) ? absint( $attributes['programId'] ) : 0;
$nntm_ctb_heading      = isset( $attributes['heading'] ) ? (string) $attributes['heading'] : '';
$nntm_ctb_bxh_heading  = isset( $attributes['bxhHeading'] ) ? (string) $attributes['bxhHeading'] : '';
$nntm_ctb_show_thongke = ! isset( $attributes['showThongKe'] ) || ! empty( $attributes['showThongKe'] );
$nntm_ctb_show_bxh     = ! isset( $attributes['showBxh'] ) || ! empty( $attributes['showBxh'] );
$nntm_ctb_bxh_limit    = isset( $attributes['bxhLimit'] ) ? absint( $attributes['bxhLimit'] ) : 50;
if ( $nntm_ctb_bxh_limit <= 0 ) {
	$nntm_ctb_bxh_limit = 50;
}

$nntm_ctb_background = isset( $attributes['background'] ) ? (string) $attributes['background'] : 'kem';
if ( ! in_array( $nntm_ctb_background, array( 'vang', 'kem', 'none' ), true ) ) {
	$nntm_ctb_background = 'kem';
}

$nntm_ctb_program = nntm_congtu_block_resolve_program( $nntm_ctb_program_id );
$nntm_ctb_data    = $nntm_ctb_program
	? nntm_congtu_block_lay_du_lieu_nhat_quan( $nntm_ctb_program, $nntm_ctb_bxh_limit )
	: array( 'tong' => array(), 'bxh' => array(), 'api_ok' => false );

$nntm_ctb_wrapper_attributes = get_block_wrapper_attributes(
	array(
		'class' => 'nntm-cong-tu nntm-cong-tu--nen-' . $nntm_ctb_background,
	)
);

?>
<section
	<?php echo $nntm_ctb_wrapper_attributes;  ?>
	data-nntm-congtu-block="1"
	data-nntm-congtu-program="<?php echo esc_attr( (string) ( $nntm_ctb_program ? $nntm_ctb_program->ID : 0 ) ); ?>"
	data-nntm-congtu-bxh-heading="<?php echo esc_attr( $nntm_ctb_bxh_heading ); ?>"
	data-nntm-congtu-bxh-limit="<?php echo esc_attr( (string) $nntm_ctb_bxh_limit ); ?>"
>
	<?php if ( ! $nntm_ctb_program ) : ?>
		<p class="nntm-cong-tu__rong">
			<?php esc_html_e( 'Hiện không có chương trình trì tụng nào đang mở để hiển thị thống kê.', 'nntm' ); ?>
		</p>
	<?php else : ?>
		<?php if ( $nntm_ctb_show_thongke ) : ?>
			<?php echo nntm_congtu_block_render_thong_ke( $nntm_ctb_program, $nntm_ctb_heading, $nntm_ctb_data['tong'] );  ?>
		<?php endif; ?>

		<?php if ( $nntm_ctb_show_bxh ) : ?>
			<?php echo nntm_congtu_block_render_bxh( $nntm_ctb_program, $nntm_ctb_bxh_heading, $nntm_ctb_bxh_limit, $nntm_ctb_data['bxh'] );  ?>
		<?php endif; ?>
	<?php endif; ?>
</section>
