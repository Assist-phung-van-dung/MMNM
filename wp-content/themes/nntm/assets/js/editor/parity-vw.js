/*
 * Đo bề ngang khung soạn thảo, đưa vào biến --nntm-vw.
 *
 * Block đặt Full Width dùng `margin-inline: calc(50% - var(--nntm-vw)/2)`.
 * Ngoài trang, --nntm-vw là bề ngang viewport đã trừ thanh cuộn dọc, do một
 * đoạn script trong <head> đo (xem inc/block-style.php).
 *
 * Trong admin, khung soạn thảo là một <iframe> riêng. Nếu không đặt biến này
 * thì CSS rơi về fallback 100vw — tính cả chỗ thanh cuộn của iframe — nên block
 * full width rộng hơn khung khoảng 15px, lệch tâm và sinh thanh cuộn ngang.
 *
 * Script này chạy ở tài liệu ngoài (wp-admin), với tay vào tài liệu bên trong
 * iframe. Hai bên cùng nguồn nên truy cập được.
 */
( function () {
	'use strict';

	var TEN_BIEN = '--nntm-vw';

	/* Tài liệu đang được theo dõi, để không gắn ResizeObserver trùng lặp. */
	var taiLieuDangTheoDoi = null;
	var doTheoDoi = null;

	function timTaiLieuKhung() {
		var khung = document.querySelector( 'iframe[name="editor-canvas"]' );

		if ( ! khung ) {
			/*
			 * Một số ngữ cảnh (trình soạn thảo chưa iframe hoá, hoặc bảng sửa
			 * mẫu) vẽ thẳng vào tài liệu admin — lúc đó đo ngay trên nó.
			 */
			return document.querySelector( '.editor-styles-wrapper' ) ? document : null;
		}

		try {
			return khung.contentDocument || null;
		} catch ( loi ) {
			/* Khác nguồn thì thôi, để CSS dùng fallback 100vw. */
			return null;
		}
	}

	function doVw( tai ) {
		if ( ! tai || ! tai.body ) {
			return;
		}

		/*
		 * Đo bằng <body>: bề ngang của nó là bề ngang thật sự dùng được, đã trừ
		 * thanh cuộn dọc. clientWidth của documentElement thì còn tính cả chỗ
		 * thanh cuộn.
		 */
		var rong = tai.body.clientWidth;

		/* 0 vẫn là giá trị CSS hợp lệ nên sẽ đè mất fallback — bỏ qua. */
		if ( rong > 0 ) {
			tai.documentElement.style.setProperty( TEN_BIEN, rong + 'px' );
		}
	}

	function gan() {
		var tai = timTaiLieuKhung();

		if ( ! tai || ! tai.body ) {
			return;
		}

		doVw( tai );

		if ( tai === taiLieuDangTheoDoi ) {
			return;
		}

		/*
		 * Khung soạn thảo bị dựng lại mỗi khi đổi chế độ xem trước (máy tính /
		 * máy tính bảng / điện thoại) — lúc đó là một tài liệu khác hẳn, phải
		 * gắn lại bộ theo dõi.
		 */
		if ( doTheoDoi ) {
			doTheoDoi.disconnect();
			doTheoDoi = null;
		}

		taiLieuDangTheoDoi = tai;

		if ( window.ResizeObserver ) {
			doTheoDoi = new window.ResizeObserver( function () {
				doVw( tai );
			} );

			doTheoDoi.observe( tai.body );
		}
	}

	/*
	 * Iframe được React dựng ra sau khi script này chạy, và dựng lại mỗi lần
	 * đổi chế độ xem trước. Theo dõi cây DOM để bắt được cả hai lần đó.
	 *
	 * Trình soạn thảo đổi DOM liên tục lúc gõ chữ, nên gom lại 250ms một lần;
	 * gan() cũng thoát sớm khi tài liệu không đổi.
	 */
	var hen = null;

	function henGan() {
		if ( hen ) {
			window.clearTimeout( hen );
		}

		hen = window.setTimeout( gan, 250 );
	}

	function batDau() {
		gan();

		if ( window.MutationObserver ) {
			new window.MutationObserver( henGan ).observe( document.body, {
				childList: true,
				subtree: true,
			} );
		}

		window.addEventListener( 'resize', henGan, { passive: true } );
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', batDau );
	} else {
		batDau();
	}
} )();
