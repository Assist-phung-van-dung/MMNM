/**
 * View script cho block nntm/engineering-earth — D1 "The Drum of the True
 * Dharma" (docs/spec-trang-chu.md mục D1). JavaScript thuần, không thư
 * viện ngoài, không bước build — khai qua "viewScript": "file:./view.js"
 * trong block.json.
 *
 * Việc của file này, cho MỖI sân khấu ".nntm-engineering-earth__video-stage"
 * tìm thấy trên trang:
 *  - Chèn <iframe> YouTube phát tự động, câm tiếng, lặp, không thanh điều
 *    khiển vào CẢ HAI khe (video lớn + video nền) ngay khi tải — kiểu
 *    "video nền awwwards.com" nêu trong yêu cầu, khác với G1 (card-list)
 *    vốn CHỈ phát khi rê chuột. Ở đây cả hai video đều chạy liên tục vì đó
 *    chính là hiệu ứng "video nền".
 *  - Nhấp vào khe NÀO cũng đổi vai trò cho khe kia: đổi class
 *    --main <-> --bg giữa hai phần tử, KHÔNG tháo/tạo lại iframe — video
 *    đang phát tiếp tục phát liền mạch, tự nhiên giữ trạng thái câm tiếng
 *    (vì cả hai luôn câm tiếng từ đầu).
 *  - prefers-reduced-motion: reduce -> KHÔNG chèn iframe (không tự phát gì
 *    cả), chỉ giữ ảnh giữ chỗ tĩnh. Nhấp đổi vai trò vẫn hoạt động (không
 *    phải "chuyển động", chỉ là điều hướng).
 *  - Bàn phím: mỗi khe có tabindex + role="button" (đặt sẵn ở render.php);
 *    Enter / Space cũng đổi vai trò như nhấp chuột.
 *
 * Một trang có thể có nhiều khối engineering-earth — hàm khởi tạo chạy
 * độc lập cho từng sân khấu tìm thấy.
 */
( function () {
	'use strict';

	function nntmPrefersReducedMotion() {
		return !! ( window.matchMedia && window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches );
	}

	function nntmYoutubeBgEmbedUrl( videoId ) {
		return 'https://www.youtube.com/embed/' + encodeURIComponent( videoId ) +
			'?autoplay=1&mute=1&loop=1&controls=0&modestbranding=1&playsinline=1&rel=0&playlist=' +
			encodeURIComponent( videoId );
	}

	/**
	 * @param {Element} stage Phần tử ".nntm-engineering-earth__video-stage".
	 */
	function nntmInitVideoStage( stage ) {
		var slots = stage.querySelectorAll( '.nntm-engineering-earth__video-slot' );

		if ( ! slots.length ) {
			return;
		}

		// Chèn iframe MỘT LẦN cho mỗi khe có video — cả hai chạy liên tục
		// làm nền, không tháo ra khi đổi vai trò (chỉ đổi class hiển thị).
		if ( ! nntmPrefersReducedMotion() ) {
			for ( var i = 0; i < slots.length; i++ ) {
				nntmInsertEmbed( slots[ i ] );
			}
		}

		function nntmInsertEmbed( slot ) {
			var videoId = slot.getAttribute( 'data-video-id' );
			var embedHost = slot.querySelector( '.nntm-engineering-earth__video-embed' );

			if ( ! videoId || ! embedHost || embedHost.firstChild ) {
				return;
			}

			var iframe = document.createElement( 'iframe' );
			iframe.src = nntmYoutubeBgEmbedUrl( videoId );
			iframe.setAttribute( 'title', slot.getAttribute( 'aria-label' ) || '' );
			iframe.setAttribute( 'frameborder', '0' );
			iframe.setAttribute( 'allow', 'autoplay; encrypted-media' );
			iframe.setAttribute( 'tabindex', '-1' );
			embedHost.appendChild( iframe );
			slot.classList.add( 'is-loaded' );
		}

		/**
		 * Đổi vai trò main<->bg giữa hai khe. KHÔNG đụng tới iframe đang
		 * chạy (video tiếp tục phát liền mạch, giữ trạng thái câm tiếng) —
		 * chỉ đổi class quyết định vị trí/kích thước (xem style.css: khe
		 * --main nằm trong luồng grid cột trái, khe --bg tách khỏi luồng,
		 * đè lên góc dưới-phải).
		 *
		 * grid-column/position không animate trực tiếp được, nên "mượt"
		 * bằng cách mờ dần (opacity, class .is-swapping) TRƯỚC khi đổi
		 * class vị trí, rồi hiện lại NGAY SAU — tạo cảm giác chuyển tiếp
		 * thay vì nhảy khung đột ngột.
		 *
		 * @param {Element} clickedSlot Khe vừa được nhấp/kích hoạt.
		 */
		function nntmSwapRoles( clickedSlot ) {
			if ( clickedSlot.classList.contains( 'nntm-engineering-earth__video-slot--main' ) ) {
				return; // da la video chinh, khong lam gi.
			}

			var reduceMotion = nntmPrefersReducedMotion();
			var fadeMs = reduceMotion ? 0 : 220; // ~var(--nntm-dur) — JS khong doc duoc bien CSS truc tiep nen ghi lai gia tri tuong duong.

			function applySwap() {
				for ( var i = 0; i < slots.length; i++ ) {
					var slot = slots[ i ];
					var isNowMain = ( slot === clickedSlot );

					slot.classList.remove(
						'nntm-engineering-earth__video-slot--main',
						'nntm-engineering-earth__video-slot--bg'
					);
					slot.classList.add(
						isNowMain
							? 'nntm-engineering-earth__video-slot--main'
							: 'nntm-engineering-earth__video-slot--bg'
					);
					slot.setAttribute( 'data-role', isNowMain ? 'main' : 'bg' );
				}
			}

			if ( 0 === fadeMs ) {
				applySwap();
				return;
			}

			for ( var j = 0; j < slots.length; j++ ) {
				slots[ j ].classList.add( 'is-swapping' );
			}

			window.setTimeout( function () {
				applySwap();
				window.setTimeout( function () {
					for ( var k = 0; k < slots.length; k++ ) {
						slots[ k ].classList.remove( 'is-swapping' );
					}
				}, 20 );
			}, fadeMs );
		}

		for ( var j = 0; j < slots.length; j++ ) {
			var slot = slots[ j ];

			if ( ! slot.getAttribute( 'data-video-id' ) ) {
				continue; // khe rong (chua dan link) khong the bam doi.
			}

			slot.addEventListener( 'click', function ( event ) {
				nntmSwapRoles( event.currentTarget );
			} );

			// Enter / Space kích hoạt giống nhấp chuột — role="button" đặt
			// sẵn ở render.php nhưng <div> không tự phản hồi bàn phím.
			slot.addEventListener( 'keydown', function ( event ) {
				if ( 'Enter' === event.key || ' ' === event.key || 'Spacebar' === event.key ) {
					event.preventDefault();
					nntmSwapRoles( event.currentTarget );
				}
			} );
		}
	}

	function nntmInitAllVideoStages() {
		var stages = document.querySelectorAll( '.nntm-engineering-earth__video-stage' );

		for ( var i = 0; i < stages.length; i++ ) {
			nntmInitVideoStage( stages[ i ] );
		}
	}

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', nntmInitAllVideoStages );
	} else {
		nntmInitAllVideoStages();
	}
} )();
