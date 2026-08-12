/**
 * JS riêng cho trang R1 — hiện chỉ phục vụ băng cuộn "Tổng Chỉ".
 * JavaScript thuần, không thư viện ngoài, không bước build.
 *
 * Băng cuộn dùng overflow-x THẬT (không phải transform) nên vuốt được
 * trên cảm ứng và cuộn được bằng bàn phím. Hai nút chỉ gọi scrollBy().
 *
 * KHÔNG dùng scroll-snap — đã từng làm băng cuộn tự nhảy 110px, xem
 * docs/07-ban-giao.md mục bài học.
 *
 * Băng chuyền ảnh lớn đầu trang KHÔNG nằm ở đây: nó là block
 * nntm/banner, có view.js riêng do WordPress tự nạp.
 */
( function () {
	'use strict';

	/**
	 * Khởi tạo một băng cuộn Tổng Chỉ.
	 *
	 * @param {Element} root Phần tử "[data-nntm-tongchi]".
	 */
	function khoiTaoTongChi( root ) {
		var track = root.querySelector( '[data-nntm-tongchi-track]' );
		var nutTruoc = root.querySelector( '[data-nntm-tongchi-truoc]' );
		var nutSau = root.querySelector( '[data-nntm-tongchi-sau]' );

		if ( ! track ) {
			return;
		}

		/**
		 * Quãng cuộn mỗi lần bấm = bề rộng một thẻ + khoảng cách giữa hai thẻ.
		 * Đọc từ DOM thật thay vì viết cứng, để CSS đổi kích thước thẻ
		 * (kể cả ở màn hình hẹp) thì bước cuộn tự khớp theo.
		 *
		 * @return {number} Số pixel cần cuộn.
		 */
		function buocCuon() {
			var the = track.querySelector( '.nntm-r1-tong-chi__the' );
			if ( ! the ) {
				return track.clientWidth;
			}
			var khoangCach = parseFloat( window.getComputedStyle( track ).columnGap );
			if ( ! isFinite( khoangCach ) ) {
				khoangCach = 0;
			}
			return the.getBoundingClientRect().width + khoangCach;
		}

		/** Bật/tắt nút khi đã cuộn tới hai đầu. */
		function capNhatNut() {
			// Cộng trừ 2px cho sai số làm tròn của trình duyệt khi thu phóng.
			var toiDa = track.scrollWidth - track.clientWidth;
			if ( nutTruoc ) {
				nutTruoc.disabled = track.scrollLeft <= 2;
			}
			if ( nutSau ) {
				nutSau.disabled = track.scrollLeft >= toiDa - 2;
			}
		}

		function cuon( huong ) {
			track.scrollBy( { left: huong * buocCuon(), behavior: 'smooth' } );
		}

		if ( nutTruoc ) {
			nutTruoc.addEventListener( 'click', function () {
				cuon( -1 );
			} );
		}
		if ( nutSau ) {
			nutSau.addEventListener( 'click', function () {
				cuon( 1 );
			} );
		}

		track.addEventListener( 'scroll', capNhatNut, { passive: true } );
		window.addEventListener( 'resize', capNhatNut );

		capNhatNut();
	}

	function khoiTaoTatCa() {
		var ds = document.querySelectorAll( '[data-nntm-tongchi]' );
		for ( var i = 0; i < ds.length; i++ ) {
			khoiTaoTongChi( ds[ i ] );
		}
	}

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', khoiTaoTatCa );
	} else {
		khoiTaoTatCa();
	}
} )();
