<?php
/**
 * Khối "Mời góp ý (Ý kiến)".
 *
 * Tách ra từ dòng "Hãy chia sẻ Ý kiến của bạn" vốn nằm cứng trong chân trang
 * riêng của trang chủ. Nay là khối độc lập, chữ căn giữa, đặt được ở mọi trang.
 */

defined( 'ABSPATH' ) || exit;

$nntm_yk_truoc = isset( $attributes['textTruoc'] ) ? trim( wp_strip_all_tags( (string) $attributes['textTruoc'] ) ) : '';
$nntm_yk_nhan  = isset( $attributes['nhan'] ) ? trim( wp_strip_all_tags( (string) $attributes['nhan'] ) ) : '';
$nntm_yk_sau   = isset( $attributes['textSau'] ) ? trim( wp_strip_all_tags( (string) $attributes['textSau'] ) ) : '';
$nntm_yk_mo_ta = isset( $attributes['moTa'] ) ? trim( wp_strip_all_tags( (string) $attributes['moTa'] ) ) : '';

if ( '' === $nntm_yk_nhan ) {
	$nntm_yk_nhan = __( 'Ý kiến', 'nntm' );
}

// Để trống đường dẫn thì tự trỏ về trang /y-kien/ như trước đây.
$nntm_yk_url = isset( $attributes['url'] ) ? esc_url_raw( (string) $attributes['url'] ) : '';
if ( '' === $nntm_yk_url ) {
	$nntm_yk_url = home_url( '/y-kien/' );
}

$nntm_yk_nen = isset( $attributes['nen'] ) ? sanitize_key( (string) $attributes['nen'] ) : 'toi';
if ( ! in_array( $nntm_yk_nen, array( 'toi', 'kem', 'trong' ), true ) ) {
	$nntm_yk_nen = 'toi';
}

$nntm_yk_wrapper = get_block_wrapper_attributes(
	array(
		'class' => 'nntm-y-kien nntm-y-kien--nen-' . $nntm_yk_nen,
	)
);
?>
<section <?php echo $nntm_yk_wrapper;  ?>>
	<div class="nntm-container nntm-y-kien__inner">

		<?php   ?>
		<span class="nntm-y-kien__net" aria-hidden="true"></span>

		<p class="nntm-y-kien__loi">
			<?php if ( '' !== $nntm_yk_truoc ) : ?>
				<span class="nntm-y-kien__chu"><?php echo esc_html( $nntm_yk_truoc ); ?></span>
			<?php endif; ?>

			<a class="nntm-y-kien__nut" href="<?php echo esc_url( $nntm_yk_url ); ?>">
				<?php echo esc_html( $nntm_yk_nhan ); ?>
			</a>

			<?php if ( '' !== $nntm_yk_sau ) : ?>
				<span class="nntm-y-kien__chu"><?php echo esc_html( $nntm_yk_sau ); ?></span>
			<?php endif; ?>
		</p>

		<?php if ( '' !== $nntm_yk_mo_ta ) : ?>
			<p class="nntm-y-kien__mo-ta"><?php echo esc_html( $nntm_yk_mo_ta ); ?></p>
		<?php endif; ?>

	</div>
</section>
