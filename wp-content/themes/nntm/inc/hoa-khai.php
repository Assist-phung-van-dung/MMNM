<?php
/**
 * Trang Hoa Khai — chốt hai thuộc tính mà bố cục dải "Ấn Phẩm" bắt buộc phải
 * có, để bản cơ sở dữ liệu cũ vẫn ra đúng thiết kế ngay khi kéo code mới về.
 *
 * VÌ SAO CẦN FILE NÀY:
 * dải "Ấn Phẩm" đổi từ băng cuộn có nút (layout "carousel") sang băng TỰ CHẠY
 * (layout "marquee") — kèm bìa sách nghiêng xen kẽ, nền đen, khung 650px. Toàn
 * bộ luật mới trong assets/css/pages/hoa-khai-figma.css nhắm vào
 * `.nntm-hk-publications .nntm-card-list__marquee`, và luật carousel dành riêng
 * cho dải này (31 luật ở blocks/card-list/style.css + 2 ở hoa-khai-figma.css)
 * đã bị bỏ cùng lúc.
 *
 * Nhưng `layout` là THUỘC TÍNH BLOCK, nằm trong post_content của Trang — tức là
 * trong cơ sở dữ liệu, không nằm trong code. Cơ sở dữ liệu nào còn ghi
 * "carousel" thì sau khi kéo code mới về, dải đó rơi vào khoảng trống: không
 * còn luật carousel riêng, cũng chưa chạm được luật marquee mới. Kết quả là một
 * băng thẻ trắng trơn, không nền đen, không nghiêng, cao sai — trông như code
 * mới bị lỗi, trong khi code không sai chỗ nào.
 *
 * Ép ở tầng render thay vì đi sửa post_content của từng Trang: sửa cơ sở dữ liệu
 * chỉ vá được máy đang sửa, còn máy khác, staging và production vẫn nguyên bản
 * cũ. Ép trong code thì bản nào cũng đúng ngay lần tải trang đầu.
 *
 * KHÔNG xoá, KHÔNG đảo thứ tự block nào; chỉ bồi thuộc tính trước khi hàm render
 * của block chạy. Người soạn nội dung vẫn thấy khối y như cũ trong trình sửa.
 * Cùng khuôn với nntm_kchg_semantic_block_classes() ở inc/kim-cuong-hanh-gia.php.
 *
 * @package NNTM
 */

defined( 'ABSPATH' ) || exit;

/**
 * Lớp CSS đánh dấu dải "Ấn Phẩm" của trang Hoa Khai — cũng chính là móc mà
 * hoa-khai-figma.css dùng, nên hai bên không thể lệch nhau.
 */
const NNTM_HK_LOP_DAI_AN_PHAM = 'nntm-hk-publications';

/**
 * Ép dải "Ấn Phẩm" trang Hoa Khai về đúng bố cục mà CSS đang nhắm tới.
 *
 * @param array $parsed_block Block đã phân tích.
 * @return array
 */
function nntm_hk_chot_bo_cuc_dai_an_pham( array $parsed_block ): array {
	if ( is_admin() || empty( $parsed_block['blockName'] ) ) {
		return $parsed_block;
	}

	if ( 'nntm/card-list' !== $parsed_block['blockName'] || ! is_page( 'hoa-khai' ) ) {
		return $parsed_block;
	}

	$attrs      = isset( $parsed_block['attrs'] ) && is_array( $parsed_block['attrs'] ) ? $parsed_block['attrs'] : array();
	$class_name = isset( $attrs['className'] ) ? (string) $attrs['className'] : '';

	if ( false === strpos( $class_name, NNTM_HK_LOP_DAI_AN_PHAM ) ) {
		return $parsed_block;
	}

	/*
	 * Hai thuộc tính, vì CSS mới cần cả hai: `layout` quyết định dải dựng bằng
	 * inc/render-card-list-marquee.php (lớp .nntm-card-list__marquee), còn
	 * `variant` quyết định thẻ mang lớp .nntm-card--books — luật bìa sách
	 * nghiêng bám vào cả hai lớp đó.
	 */
	$parsed_block['attrs']['layout']  = 'marquee';
	$parsed_block['attrs']['variant'] = 'books';

	return $parsed_block;
}
add_filter( 'render_block_data', 'nntm_hk_chot_bo_cuc_dai_an_pham', 10, 1 );
