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
