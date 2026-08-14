<?php
$_SERVER['HTTP_HOST'] = 'nntm.com';
$_SERVER['REQUEST_URI'] = '/';
require dirname( __DIR__ ) . '/wp-load.php';
require_once ABSPATH . 'wp-admin/includes/file.php';
require_once ABSPATH . 'wp-admin/includes/media.php';
require_once ABSPATH . 'wp-admin/includes/image.php';

$dir = dirname( __DIR__ ) . '/design/generated/dieu-thuong';
$assets = array();
foreach ( glob( $dir . '/*.webp' ) as $path ) {
  $slug = basename( $path, '.webp' );
  $existing = get_posts( array( 'post_type' => 'attachment', 'post_status' => 'inherit', 'meta_key' => '_nntm_source_asset', 'meta_value' => $slug, 'posts_per_page' => 1, 'fields' => 'ids' ) );
  if ( $existing ) { $id = (int) $existing[0]; }
  else {
    $upload = wp_upload_bits( basename( $path ), null, file_get_contents( $path ) );
    if ( $upload['error'] ) { throw new RuntimeException( $upload['error'] ); }
    $id = wp_insert_attachment( array( 'post_mime_type' => 'image/webp', 'post_title' => ucwords( str_replace( '-', ' ', $slug ) ), 'post_status' => 'inherit' ), $upload['file'] );
    wp_update_attachment_metadata( $id, wp_generate_attachment_metadata( $id, $upload['file'] ) );
    update_post_meta( $id, '_nntm_source_asset', $slug );
  }
  $assets[ $slug ] = array( 'imageId' => $id, 'imageUrl' => wp_get_attachment_url( $id ), 'imageAlt' => ucwords( str_replace( '-', ' ', $slug ) ) );
}

$slide_slugs = array( 'tong-chi-dia-tang', 'thap-den-tam', 'thuong-nguoi', 'thuyet-phap-bo-de', 'phat-thuyet-phap' );
$slide_titles = array( 'Hạnh Nguyện Địa Tạng', 'Thắp Đèn Tâm', 'Lắng Nghe Bằng Từ Bi', 'Nương Dưới Cội Bồ Đề', 'Con Đường Tỉnh Thức' );
$slide_texts = array( 'Phát tâm rộng lớn, lấy lợi lạc chúng sinh làm con đường tu học.', 'Một niệm sáng trong có thể soi rọi cả hành trình trở về.', 'Từ bi bắt đầu khi ta thật sự có mặt và lắng nghe.', 'Giới, định và tuệ là nền đất vững chắc của người học đạo.', 'Mỗi bước chân tỉnh thức đều đưa ta trở về với chính mình.' );
$slides = array();
foreach ( $slide_slugs as $i => $slug ) { $slides[] = array_merge( $assets[ $slug ], array( 'heading' => $slide_titles[$i], 'text' => $slide_texts[$i] ) ); }
$gallery = array( $assets['thuong-nguoi'], $assets['thuyet-phap-bo-de'], $assets['phat-thuyet-phap'] );
$attrs = array(
 'heading' => 'Tông Chỉ', 'introTitle' => 'Phát Bồ Đề Tâm', 'introText' => 'Trên Sáu Xúc Xứ/ Ngũ Thủ Uẩn Tu Tứ Chánh Cần, Lấy Bát Chánh Đạo Làm Nền Tảng.',
 'slides' => $slides, 'autoplay' => true, 'interval' => 6,
 'bannerImageId' => $assets['banner-nui-tu-vien']['imageId'], 'bannerImageUrl' => $assets['banner-nui-tu-vien']['imageUrl'],
 'portraitImageId' => $assets['chan-dung-thien']['imageId'], 'portraitImageUrl' => $assets['chan-dung-thien']['imageUrl'],
 'storyHeading' => 'Hành Trình Tỉnh Thức',
 'storyTextTop' => 'Giữa nhịp sống vô thường, mỗi phút giây quay về soi sáng chính mình là một bước trên con đường tỉnh thức. Sự thực tập được nuôi dưỡng bằng lòng từ bi, sự kiên nhẫn và cái thấy sâu sắc.',
 'gallery' => $gallery,
 'storyTextBottom' => 'Từ những lời dạy mộc mạc đến từng việc làm chân thành, con đường ấy được tiếp nối trong đời sống hằng ngày — nơi mỗi người tự thắp lên ánh sáng bình an cho mình và cho người.',
 'ctaLabel' => 'Chuyện Về Thầy Tôi', 'ctaUrl' => get_permalink( 143 ),
);
$content = '<!-- wp:nntm/dieu-thuong ' . wp_json_encode( $attrs, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . ' /-->' . "\n\n" . '<!-- wp:nntm/tru-xu-list {"heading":"Trú Xứ","postsPerPage":4,"orderBy":"oldest","displayMode":"list"} /-->';
$result = wp_update_post( array( 'ID' => 15, 'post_content' => $content ), true );
if ( is_wp_error( $result ) ) { throw new RuntimeException( $result->get_error_message() ); }
echo wp_json_encode( array( 'page' => $result, 'assets' => $assets ), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT );
