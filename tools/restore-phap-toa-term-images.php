<?php
if ( PHP_SAPI !== 'cli' ) exit( 'Chỉ chạy từ dòng lệnh.' );
$_SERVER['HTTP_HOST'] = 'nntm.com';
$_SERVER['REQUEST_URI'] = '/';
require dirname( __DIR__ ) . '/wp-load.php';

$map = array(
	'nguyen-thuy' => 193,
	'thien-tong'  => 192,
	'tinh-do'     => 191,
	'mat-tong'    => 190,
);

foreach ( $map as $slug => $image_id ) {
	$term = get_term_by( 'slug', $slug, 'nntm_section' );
	if ( ! $term instanceof WP_Term ) {
		echo "Không tìm thấy {$slug}.\n";
		continue;
	}
	update_term_meta( $term->term_id, '_nntm_term_image', $image_id );
	echo "{$term->name} => ảnh {$image_id}\n";
}
