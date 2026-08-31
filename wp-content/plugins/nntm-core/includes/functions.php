<?php
/**
 * Hàm tiện ích toàn cục dùng chung cho theme và các plugin con.
 *
 * File này ở namespace gốc (không có `namespace NNTM\Core;`) để theme gọi
 * thẳng không cần tiền tố. Đặt riêng thay vì nhét vào class-taxonomies.php
 * vì file đó khai báo namespace kiểu không ngoặc — PHP cấm trộn hai kiểu
 * khai báo namespace trong cùng một file.
 *
 * @package NNTM_Core
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'nntm_sort_terms_by_order' ) ) {
	/**
	 * Sắp danh sách chuyên mục theo trường "Thứ tự hiển thị" ban quản trị nhập.
	 *
	 * WordPress không có sẵn cách sắp xếp chuyên mục — mặc định chỉ xếp theo
	 * bảng chữ cái, trong khi thiết kế có thứ tự riêng (Nguyên Thuỷ → Đại Thừa
	 * → Tịnh Độ → Mật Tông). Trường số do class-term-meta.php cung cấp
	 * (term meta `_nntm_term_order`).
	 *
	 * VÌ SAO Ở PLUGIN: có HAI block cùng cần — `nntm/term-list` (trang Pháp Tòa)
	 * và `nntm/hero-slider` (dải liên kết nhanh ở trang chủ). Trước đây mỗi nơi
	 * tự sắp một kiểu nên cùng một dữ liệu mà hiện ra hai thứ tự khác nhau.
	 *
	 * ⚠️ KHÔNG truyền `meta_key` vào `get_terms()` để sắp: làm vậy sẽ nối bảng
	 * meta và làm BIẾN MẤT những chuyên mục chưa nhập số — thêm chuyên mục mới
	 * mà quên nhập là nó không hiện, rất khó đoán ra nguyên nhân. Số chuyên mục
	 * luôn rất ít nên sắp trong PHP không ảnh hưởng hiệu năng.
	 *
	 * Chuyên mục chưa nhập số thì xuống cuối, giữ nguyên thứ tự sẵn có.
	 *
	 * @param array $terms Danh sách WP_Term.
	 * @return array
	 */
	function nntm_sort_terms_by_order( array $terms ): array {
		$order = array();
		foreach ( $terms as $i => $term ) {
			if ( ! isset( $term->term_id ) ) {
				continue;
			}
			$order[ $term->term_id ] = array(
				absint( get_term_meta( $term->term_id, '_nntm_term_order', true ) ),
				$i,
			);
		}

		usort(
			$terms,
			static function ( $a, $b ) use ( $order ) {
				$oa = $order[ $a->term_id ] ?? array( 0, 0 );
				$ob = $order[ $b->term_id ] ?? array( 0, 0 );

				// Chưa nhập số (0) thì xuống cuối, nhường chỗ cho mục đã đánh số.
				$na = 0 === $oa[0] ? PHP_INT_MAX : $oa[0];
				$nb = 0 === $ob[0] ? PHP_INT_MAX : $ob[0];

				return $na === $nb ? $oa[1] <=> $ob[1] : $na <=> $nb;
			}
		);

		return $terms;
	}
}

if ( ! function_exists( 'nntm_core_validate_pinned_post' ) ) {
	/**
	 * Kiểm tra một ID bài "ghim tay" do ban quản trị chọn trong bảng điều
	 * khiển của block (ví dụ ô lớn của `nntm/article-mosaic`).
	 *
	 * VÌ SAO Ở PLUGIN: logic "bài ghim có còn hợp lệ không" giống nhau ở
	 * mọi block có tính năng ghim (article-mosaic, article-feature, sau
	 * này có thể thêm nơi khác) — đúng nguyên tắc "logic dữ liệu gom về
	 * plugin, không viết riêng trong từng block" (docs/04-kien-truc.md
	 * mục 9). Bài bị xoá / chuyển về nháp / đổi sang loại nội dung khác thì
	 * coi như chưa ghim gì, để khối tự rơi về bài mới nhất, không vỡ trang.
	 *
	 * @param int    $post_id   ID bài đã ghim (0 = chưa ghim).
	 * @param string $post_type Loại nội dung khối đang lấy — rỗng thì không so khớp.
	 * @return WP_Post|null
	 */
	function nntm_core_validate_pinned_post( int $post_id, string $post_type = '' ): ?WP_Post {
		if ( $post_id <= 0 ) {
			return null;
		}

		$pinned = get_post( $post_id );

		if ( ! ( $pinned instanceof WP_Post ) || 'publish' !== $pinned->post_status ) {
			return null;
		}

		if ( '' !== $post_type && $pinned->post_type !== $post_type ) {
			return null;
		}

		return $pinned;
	}
}

if ( ! function_exists( 'nntm_core_get_latest_posts' ) ) {
	/**
	 * Lấy N bài mới nhất của một nguồn (loại nội dung + taxonomy/term),
	 * có thể ghim sẵn một bài lên vị trí đầu tiên.
	 *
	 * VÌ SAO Ở PLUGIN: dùng chung cho thẻ nổi góc phải hero
	 * (`nntm/hero-slider`) và có thể tái dùng ở bất kỳ khối nào khác cần
	 * "N bài mới nhất, có thể ghim một bài" — tránh mỗi block tự viết lại
	 * một WP_Query giống nhau (docs/04-kien-truc.md mục 9).
	 *
	 * Luôn kèm `ID` làm tiêu chí sắp phụ — dữ liệu có ngày đăng trùng nhau
	 * (nhập hàng loạt) thì MySQL được phép trả thứ tự bất kỳ nếu chỉ sắp
	 * theo `date`; thêm `ID` thì thứ tự cố định giữa các lần tải trang.
	 *
	 * @param array $args {
	 *     @type string $post_type Loại nội dung. Mặc định 'post'.
	 *     @type string $taxonomy  Taxonomy lọc, rỗng = không lọc.
	 *     @type int    $term_id   Term lọc, 0 = không lọc.
	 *     @type int    $number    Số bài cần lấy (tính cả bài ghim). Mặc định 1.
	 *     @type int    $pinned_id ID bài ghim tay lên vị trí đầu, 0 = không ghim.
	 * }
	 * @return WP_Post[] Danh sách bài — có thể ngắn hơn $number nếu không đủ bài.
	 */
	function nntm_core_get_latest_posts( array $args = array() ): array {
		$post_type = isset( $args['post_type'] ) ? sanitize_key( (string) $args['post_type'] ) : 'post';
		$taxonomy  = isset( $args['taxonomy'] ) ? sanitize_key( (string) $args['taxonomy'] ) : '';
		$term_id   = isset( $args['term_id'] ) ? absint( $args['term_id'] ) : 0;
		$number    = isset( $args['number'] ) ? absint( $args['number'] ) : 1;
		$number    = max( 1, $number );
		$pinned_id = isset( $args['pinned_id'] ) ? absint( $args['pinned_id'] ) : 0;

		$posts = array();

		$pinned_post = nntm_core_validate_pinned_post( $pinned_id, $post_type );
		if ( $pinned_post ) {
			$posts[] = $pinned_post;
		}

		$remaining = $number - count( $posts );
		if ( $remaining > 0 ) {
			$query_args = array(
				'post_type'           => $post_type,
				'post_status'         => 'publish',
				'posts_per_page'      => $remaining,
				'ignore_sticky_posts' => true,
				'no_found_rows'       => true,
				'orderby'             => array(
					'date' => 'DESC',
					'ID'   => 'DESC',
				),
			);

			if ( $pinned_post ) {
				$query_args['post__not_in'] = array( $pinned_post->ID );
			}

			if ( '' !== $taxonomy && taxonomy_exists( $taxonomy ) && $term_id > 0 ) {
				$query_args['tax_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- can loc theo 1 term, khong tranh duoc.
					array(
						'taxonomy' => $taxonomy,
						'field'    => 'term_id',
						'terms'    => array( $term_id ),
					),
				);
			}

			$query = new WP_Query( $query_args );
			foreach ( $query->posts as $found_post ) {
				$posts[] = $found_post;
			}
		}

		return $posts;
	}
}

if ( ! function_exists( 'nntm_publication_music_tracks' ) ) {
	/**
	 * Lấy playlist nhạc nền dùng chung cho trình đọc Ấn phẩm và Nghi Quỹ.
	 *
	 * @return array<int,array{id:int,title:string,url:string,mime:string}>
	 */
	function nntm_publication_music_tracks(): array {
		if ( ! class_exists( '\\NNTM\\Core\\Publication_Music' ) ) {
			return array();
		}

		return \NNTM\Core\Publication_Music::get_tracks();
	}
}
