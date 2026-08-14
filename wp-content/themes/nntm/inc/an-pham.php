<?php
/**
 * Trang chi tiết ấn phẩm (CPT `nntm_publication`).
 *
 * Nạp CSS riêng cho single-nntm_publication.php, chỉ đúng trang cần —
 * cùng khuôn mẫu với nntm_hanh_gia_enqueue_assets() ở inc/hanh-gia.php
 * (KHÔNG sửa inc/enqueue.php theo đúng phạm vi việc được giao).
 *
 * @package NNTM
 */

defined( 'ABSPATH' ) || exit;

/* =========================================================================
 * 1. Cổng quyền đọc ấn phẩm — CHỪA SẴN, CHƯA BẬT.
 * ========================================================================= */

/**
 * Người đang xem có được đọc ấn phẩm này hay không.
 *
 * ⚠️ MẶC ĐỊNH TRẢ VỀ TRUE (ai cũng đọc được) — đây là lựa chọn CÓ CHỦ Ý,
 * KHÔNG PHẢI QUÊN BẬT. Mục A trong docs/03-chot-tu-khao-sat.md ghi rõ
 * đây là điểm khách CHƯA CHỐT: "Thư Viện PDF bắt buộc đăng nhập, hay cho
 * khách vãng lai đọc và chỉ đóng dấu khi có đăng nhập?". Không có quyền
 * tự quyết thay khách ở một ràng buộc kiến trúc ảnh hưởng tới toàn bộ
 * Thư Viện PDF — nên để cổng luôn mở, và để sẵn filter
 * `nntm_an_pham_can_access` cho phần việc khác (hoặc chính hàm này sau
 * khi khách chốt) đổi kết quả mà KHÔNG phải sửa lại các nơi đang gọi hàm
 * này (single-nntm_publication.php và bất kỳ chỗ nào sau này cần kiểm
 * tra quyền đọc ấn phẩm).
 *
 * @param WP_Post|null $post Ấn phẩm cần kiểm tra, mặc định bài hiện tại.
 * @return bool
 */
function nntm_an_pham_can_access( ?WP_Post $post = null ): bool {
	$post = get_post( $post );

	return (bool) apply_filters( 'nntm_an_pham_can_access', true, $post, get_current_user_id() );
}

/* =========================================================================
 * 2. CSS riêng cho trang ấn phẩm — chỉ nạp đúng trang cần.
 * ========================================================================= */

/**
 * Nạp an-pham.css chỉ khi đang xem một `nntm_publication`.
 */
function nntm_an_pham_enqueue_assets(): void {
	if ( ! is_singular( 'nntm_publication' ) ) {
		return;
	}

	$css_path = NNTM_THEME_DIR . '/assets/css/pages/an-pham.css';
	wp_enqueue_style(
		'nntm-an-pham',
		NNTM_THEME_URI . '/assets/css/pages/an-pham.css',
		array( 'nntm-tokens', 'nntm-base' ),
		nntm_asset_version( $css_path )
	);
}
add_action( 'wp_enqueue_scripts', 'nntm_an_pham_enqueue_assets' );
