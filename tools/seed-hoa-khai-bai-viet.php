<?php
/**
 * Seed bài viết mẫu cho phân mục Hoa Khai (nntm_section) — môi trường
 * phát triển.
 *
 * VÌ SAO CẦN: spec trang chủ 12/08/2026 (mục M1/F1) chốt nguồn của khối
 * "Bố cục tạp chí" (nntm/article-mosaic) và "Bài nổi bật toàn văn"
 * (nntm/article-feature) trên trang chủ là phân mục Hoa Khai (term
 * `hoa-khai` của taxonomy `nntm_section`, CPT `nntm_article`) — KHÔNG
 * phải category `post` như dữ liệu mẫu cũ. Trước khi chạy script này,
 * Hoa Khai chỉ có ĐÚNG 1 bài, khiến khối mosaic rơi vào bố cục "solo" và
 * lộ ra lỗi phình chiều cao (đã sửa riêng ở style.css). Cần thêm bài để
 * kiểm chứng bố cục ĐẦY ĐỦ (1 nổi bật + 2 vừa + 3 nhỏ) hoạt động đúng.
 *
 * SỬA 12/08/2026 lần 2: mỗi bài giờ có THÂN BÀI DÀI (8 đoạn) thay vì chỉ
 * một câu — đối chiếu ảnh Figma thật cho thấy khung nền kem của khối
 * "Bài nổi bật toàn văn" (nntm/article-feature) chứa một đoạn trích dài,
 * không phải một câu ngắn. Chạy lại script này (upsert theo tiêu đề) để
 * CẬP NHẬT thân bài cho các bài đã tồn tại, không chỉ tạo mới.
 *
 * Dùng lại ảnh ĐÃ CÓ trong thư viện (không tải ảnh mới) — các ảnh phong
 * cảnh/thiền định upload cùng lô với hero-slider (ID 118-123).
 *
 * Có 1 bài tiêu đề CỐ Ý DÀI để kiểm chứng việc cắt 2 dòng bằng "…"
 * (.nntm-cat-2-dong, assets/css/base.css) hoạt động với dữ liệu thật.
 *
 *   "C:/xampp8_2/php/php.exe" tools/seed-hoa-khai-bai-viet.php
 *
 * Chạy nhiều lần được, không tạo trùng (so khớp theo tiêu đề) — bài đã có
 * thì CẬP NHẬT lại thân bài/tóm tắt/ảnh đại diện theo dữ liệu mới nhất
 * trong file này. CHỈ dùng ở local/staging để dựng dữ liệu mẫu, không
 * chạy trên production.
 *
 * @package NNTM
 */

if ( PHP_SAPI !== 'cli' ) {
	exit( 'Chi chay tu dong lenh.' );
}

$_SERVER['HTTP_HOST'] = 'localhost:8080';
require_once __DIR__ . '/../wp-load.php';

/* ---------- 1. Term "Hoa Khai" (nntm_section) đã có sẵn từ activator ---------- */

$term = get_term_by( 'slug', 'hoa-khai', 'nntm_section' );
if ( ! $term ) {
	exit( "Khong tim thay term 'hoa-khai' trong taxonomy nntm_section. Kich hoat lai plugin nntm-core truoc.\n" );
}
echo "Term: {$term->name} (ID {$term->term_id})\n";

/*
 * ---------- 2. Kho đoạn văn dùng chung ----------
 * Giọng văn giảng pháp — dùng để nối vào SAU đoạn mở đầu riêng của từng
 * bài, cho đủ dài lấp khung nền kem (Figma: cao 657px). Không phải trích
 * dẫn kinh điển thật, chỉ là văn mẫu để kiểm thử bố cục.
 */
$doan_chung = array(
	'Người tu không cầu cảnh thuận, chỉ cầu tâm an — vì cảnh thuận rồi cũng qua, còn tâm an mới là chỗ nương tựa lâu dài.',
	'Có người hỏi: tu để làm gì, khi khổ vẫn còn đó? Đáp rằng: tu không phải để khổ biến mất, mà để nhận ra khổ không còn trói buộc được mình nữa.',
	'Một hơi thở vào, một hơi thở ra, nếu biết rõ đang thở thì đó đã là một bước trên đường tỉnh thức, không cần tìm đâu xa.',
	'Người đời thường lấy được – mất, hơn – thua để đo hạnh phúc; người tu học lấy tâm có an hay không an để tự biết mình đang ở đâu trên đường tu.',
	'Nghịch cảnh không phải để tránh, mà để nhìn thấy phản ứng của chính mình trong đó — nhìn thấy rồi thì mới có chỗ mà sửa.',
	'Buông không có nghĩa là bỏ mặc, mà là không còn nắm giữ bằng chấp thủ; việc vẫn làm, người vẫn thương, chỉ là tâm không còn bị trói vào kết quả.',
	'Có những ngày công phu thấy nhẹ nhàng, có những ngày thấy nặng nề — cả hai đều là bài học, không ngày nào thừa, không ngày nào phí.',
	'Tin sâu nhân quả không phải để sợ hãi, mà để sống có trách nhiệm với từng ý nghĩ, từng lời nói, từng việc làm của chính mình.',
);

/* ---------- 3. Bài mẫu — đủ 6 bài để bố cục tạp chí có cả 3 tầng ---------- */
/* attachment_id: dùng lại ảnh có sẵn trong thư viện (media library ID 118-123,
   upload cùng lô với hero-slider — xem tools/list media hoặc Thư viện > Ảnh). */

$articles = array(
	array(
		'title'         => 'Hoa sen nở giữa bùn nhơ',
		'excerpt'       => 'Bùn nhơ không làm hoa sen ô uế, cũng như nghịch cảnh không làm tâm tỉnh thức vấy bẩn.',
		'attachment_id' => 123,
		'mo_dau'        => array(
			'Bùn nhơ không làm hoa sen ô uế, cũng như nghịch cảnh không làm tâm tỉnh thức vấy bẩn. Hoa sen không chọn mọc ở nơi sạch sẽ mới nở, mà chính từ bùn lầy tăm tối, nó vươn lên và toả hương thanh khiết.',
			'Người tu tập cũng vậy — không đợi hoàn cảnh yên ổn mới bắt đầu tu, mà tu ngay giữa những xáo trộn của đời sống, lấy chính nghịch duyên làm nơi rèn luyện tâm.',
		),
	),
	array(
		// Tiêu đề CỐ Ý DÀI để kiểm chứng cắt 2 dòng bằng "…" hoạt động thật
		// với dữ liệu thật (không chỉ với chữ mẫu ngắn như trước đây).
		'title'         => 'Vì sao tâm an tĩnh giữa đời nhiều biến động lại chính là cánh cửa đầu tiên bước vào con đường tu tập chân thật của người con Phật',
		'excerpt'       => 'Không phải trốn khỏi biến động mà tìm được an tĩnh, mà an tĩnh ngay giữa biến động mới là tu tập thật sự.',
		'attachment_id' => 122,
		'mo_dau'        => array(
			'Không phải trốn khỏi biến động mà tìm được an tĩnh, mà an tĩnh ngay giữa biến động mới là tu tập thật sự. Đời sống vốn không hứa hẹn một ngày nào hoàn toàn yên lặng — được mất, khen chê, thành bại vẫn xoay vần không ngớt.',
			'Nếu đợi cho ngoại cảnh lặng yên rồi mới an tâm tu tập, e rằng cả đời cũng không tìm được thời điểm ấy. Cho nên người con Phật học cách an trú ngay trong lòng biến động, xem đó là bài thi hằng ngày của sự tỉnh thức.',
		),
	),
	array(
		'title'         => 'Lễ khai hoa đầu xuân tại Trú Xứ',
		'excerpt'       => 'Nghi thức khai hoa mở đầu năm mới, cầu cho muôn loài đều được an lành.',
		'attachment_id' => 121,
		'mo_dau'        => array(
			'Nghi thức khai hoa mở đầu năm mới, cầu cho muôn loài đều được an lành. Từng đoá hoa được dâng lên không chỉ là hình thức trang nghiêm đạo tràng, mà còn là lời nhắc mỗi người tự làm mới tâm mình theo từng mùa xuân đi qua.',
			'Trong không khí trang nghiêm của buổi lễ, đại chúng cùng nhau phát nguyện sống tỉnh thức hơn, bớt một phần sân si, thêm một phần từ bi trong năm mới.',
		),
	),
	array(
		'title'         => 'Bàn tay chắp lại, tâm mới lặng yên',
		'excerpt'       => 'Cử chỉ nhỏ nhắc nhở hành giả quay về với chính mình giữa những bận rộn thường nhật.',
		'attachment_id' => 119,
		'mo_dau'        => array(
			'Cử chỉ nhỏ nhắc nhở hành giả quay về với chính mình giữa những bận rộn thường nhật. Chỉ một cái chắp tay, một hơi thở chậm lại, cũng đủ để tâm thức tạm dừng cơn cuốn trôi của suy nghĩ.',
			'Nhiều người tìm sự bình an ở những nơi xa xôi, mà không hay chính đôi bàn tay chắp lại trước ngực, chính giây phút đứng lặng trước tôn tượng, đã là cánh cửa trở về gần nhất.',
		),
	),
	array(
		'title'         => 'Rừng thông và một buổi công phu sớm',
		'excerpt'       => 'Sương còn đọng trên lá, tiếng chuông đã vang lên gọi người tỉnh giấc.',
		'attachment_id' => 120,
		'mo_dau'        => array(
			'Sương còn đọng trên lá, tiếng chuông đã vang lên gọi người tỉnh giấc. Buổi sớm nơi rừng thông tĩnh lặng là khoảng thời gian quý giá nhất trong ngày để hành giả trở về với hơi thở của chính mình.',
			'Không có tiếng ồn của phố thị, không có ánh sáng chói của màn hình, chỉ có tiếng chuông, tiếng chim và một tâm đang tập quán sát chính nó — đơn sơ mà sâu lắng.',
		),
	),
	array(
		'title'         => 'Lá bồ đề và bài học vô thường',
		'excerpt'       => 'Một chiếc lá rụng xuống cũng đủ để thấy hết một đời sinh diệt.',
		'attachment_id' => 118,
		'mo_dau'        => array(
			'Một chiếc lá rụng xuống cũng đủ để thấy hết một đời sinh diệt. Từ lúc còn là chồi non đến khi úa vàng lìa cành, chiếc lá bồ đề đi trọn một vòng sinh — trụ — dị — diệt mà không một lời than trách.',
			'Nhìn lá rụng mà quán chiếu vô thường, hành giả không sinh tâm buồn bã, mà càng thêm trân quý từng khoảnh khắc đang sống, bởi biết rằng mọi pháp hữu vi đều như vậy — có rồi mất, không gì thường tại.',
		),
	),
);

foreach ( $articles as $article ) {
	$paragraphs = array_merge( $article['mo_dau'], $doan_chung );

	$content_html = '';
	foreach ( $paragraphs as $doan ) {
		$content_html .= '<!-- wp:paragraph --><p>' . esc_html( $doan ) . '</p><!-- /wp:paragraph -->' . "\n";
	}

	$found = get_posts(
		array(
			'post_type'      => 'nntm_article',
			'post_status'    => 'any',
			'title'          => $article['title'],
			'posts_per_page' => 1,
			'no_found_rows'  => true,
		)
	);

	$post_args = array(
		'post_type'    => 'nntm_article',
		'post_status'  => 'publish',
		'post_title'   => $article['title'],
		'post_excerpt' => $article['excerpt'],
		'post_content' => $content_html,
	);

	if ( $found ) {
		$post_args['ID'] = (int) $found[0]->ID;
		$id              = wp_update_post( $post_args, true );
		if ( is_wp_error( $id ) ) {
			echo "LOI cap nhat bai '{$article['title']}': " . $id->get_error_message() . "\n";
			continue;
		}
		echo "Cap nhat bai (them than bai dai): {$article['title']}\n";
	} else {
		$id = wp_insert_post( $post_args, true );
		if ( is_wp_error( $id ) ) {
			echo "LOI tao bai '{$article['title']}': " . $id->get_error_message() . "\n";
			continue;
		}
		echo "Tao bai: {$article['title']}\n";
	}

	wp_set_object_terms( $id, array( (int) $term->term_id ), 'nntm_section' );

	if ( ! get_post_thumbnail_id( $id ) && get_post( $article['attachment_id'] ) ) {
		set_post_thumbnail( $id, $article['attachment_id'] );
	}
}

echo "\nXong. Tong bai trong Hoa Khai (nntm_section): ";

$count_query = new WP_Query(
	array(
		'post_type'      => 'nntm_article',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'fields'         => 'ids',
		'no_found_rows'  => true,
		'tax_query'      => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- script CLI, chay mot lan, khong anh huong trang thuc.
			array(
				'taxonomy' => 'nntm_section',
				'field'    => 'term_id',
				'terms'    => array( $term->term_id ),
			),
		),
	)
);
echo count( $count_query->posts ) . "\n";
