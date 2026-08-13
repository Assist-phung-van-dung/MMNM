/**
 * NNTM — Đầu trang đổi màu theo cuộn (H1, chỉ đạo 12/08/2026).
 *
 * header.php chỉ gắn class `.nntm-header--trong` cho trang có banner ảnh
 * ngay dưới đầu trang (dùng lại nntm_page_starts_with_hero() — xem
 * inc/setup.php). Ở đây chỉ cần: nếu header đang ở trạng thái đó, theo
 * dõi khi người dùng cuộn quá 80px thì đổi sang `.nntm-header--dac` (nền
 * trắng); cuộn lại lên đỉnh thì đổi ngược lại. Trang không có banner đã
 * nhận `.nntm-header--dac` thẳng từ PHP nên không việc gì phải làm — thoát
 * ngay để không tốn một trình theo dõi nào trên các trang đó.
 *
 * SỬA 12/08/2026 (báo lỗi của người điều phối, đo bằng trình duyệt thật):
 * bản trước dùng IntersectionObserver + một "lính canh" tuyệt đối định vị
 * cách đỉnh tài liệu 80px — về lý thuyết đúng, nhưng không kiểm chứng
 * được là có chạy đúng trong trình duyệt thật hay không (công cụ trình
 * duyệt tự động ở đây không dựng khung hình nên IntersectionObserver /
 * requestAnimationFrame không bao giờ bắn callback, không thể xác nhận).
 * Đổi sang cách trực tiếp và dễ suy luận hơn: so sánh window.scrollY với
 * ngưỡng ngay trong một vòng lặp cuộn có tiết chế bằng
 * requestAnimationFrame — đúng phương án thay thế mà spec-trang-chu.md
 * mục H1 cho phép ("IntersectionObserver HOẶC scroll có
 * requestAnimationFrame"), không còn phụ thuộc hình học của một phần tử
 * lính canh nằm trong tài liệu khi đầu trang lại đang `position: fixed`.
 *
 * Chuyển tiếp màu (background-color/color) khai trong header.css bằng
 * var(--nntm-dur)/var(--nntm-ease); người dùng bật "giảm chuyển động" thì
 * --nntm-dur tự động = 0ms (xem tokens.css) nên tự đổi màu tức thì, không
 * cần xử lý prefers-reduced-motion riêng ở đây.
 */
( function () {
	'use strict';

	var header = document.querySelector( '.nntm-header' );
	if ( ! header || ! header.classList.contains( 'nntm-header--trong' ) ) {
		return; // Trang không có banner: đã trắng từ đầu, không cần theo dõi cuộn.
	}

	var THRESHOLD = 80; // px — theo đề xuất H1 trong spec-trang-chu.md.
	var isDac      = false; // trạng thái hiện tại đã áp dụng lên class, tránh ghi DOM thừa mỗi frame.

	/**
	 * Đọc vị trí cuộn hiện tại, tương thích trình duyệt cũ không có window.scrollY.
	 *
	 * @return {number}
	 */
	function getScrollY() {
		return window.scrollY || window.pageYOffset || document.documentElement.scrollTop || 0;
	}

	/**
	 * Đối chiếu vị trí cuộn với ngưỡng, chỉ đổi class khi trạng thái thực sự
	 * thay đổi.
	 */
	function syncState() {
		var shouldBeDac = getScrollY() > THRESHOLD;
		if ( shouldBeDac === isDac ) {
			return;
		}
		isDac = shouldBeDac;
		header.classList.toggle( 'nntm-header--dac', isDac );
		header.classList.toggle( 'nntm-header--trong', ! isDac );
	}

	var ticking = false;

	/**
	 * Lắng nghe sự kiện cuộn nhưng CHỈ xử lý một lần mỗi khung hình
	 * (requestAnimationFrame) — đúng yêu cầu "không nghe scroll trực tiếp
	 * không tiết chế" của H1.
	 */
	function onScroll() {
		if ( ticking ) {
			return;
		}
		ticking = true;
		window.requestAnimationFrame( function () {
			syncState();
			ticking = false;
		} );
	}

	window.addEventListener( 'scroll', onScroll, { passive: true } );

	// Đồng bộ ngay khi nạp trang — người dùng có thể tải trang khi đã cuộn
	// sẵn (ví dụ bấm Back của trình duyệt, trình duyệt tự khôi phục vị trí
	// cuộn trước khi script này chạy).
	syncState();
} )();
