<?php
if ( PHP_SAPI !== 'cli' ) exit( 'Chỉ chạy từ dòng lệnh.' );
$_SERVER['HTTP_HOST'] = 'nntm.com';
$_SERVER['REQUEST_URI'] = '/';
require dirname( __DIR__ ) . '/wp-load.php';
$page = get_page_by_path( 'phap-toa' );
$parent = get_term_by( 'slug', 'phap-toa', 'nntm_section' );
if ( ! $page || ! $parent ) exit( "Thiếu trang hoặc term Pháp Tòa.\n" );
$content = '<!-- wp:nntm/term-list {"heading":"Pháp Toà","parentTermId":' . (int) $parent->term_id . ',"showDescription":true,"ctaLabel":"Xem thêm","maxItems":8,"layout":"phap-toa","autoplay":true,"interval":5} /-->';
$result = wp_update_post( array( 'ID' => $page->ID, 'post_content' => $content ), true );
if ( is_wp_error( $result ) ) exit( $result->get_error_message() . "\n" );
echo "Đã cập nhật trang Pháp Tòa ID {$result}.\n";
