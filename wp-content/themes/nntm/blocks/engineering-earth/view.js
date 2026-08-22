/**
 * View script cho block nntm/engineering-earth — D1 "The Drum of the True
 * Dharma" (docs/spec-trang-chu.md mục D1). JavaScript thuần, không thư
 * viện ngoài, không bước build — khai qua "viewScript": "file:./view.js"
 * trong block.json.
 *
 * Việc của file này, cho MỖI khe video ".nntm-engineering-earth__video-slot"
 * tìm thấy trên trang:
 *  - Chèn <iframe> YouTube phát tự động, câm tiếng, lặp, không thanh điều
 *    khiển ngay khi tải — kiểu "video nền awwwards.com" nêu trong yêu cầu,
 *    khác với G1 (card-list) vốn CHỈ phát khi rê chuột. Ở đây mọi video đều
 *    chạy liên tục vì đó chính là hiệu ứng "video nền".
 *  - Không xử lý hover hoặc đổi vai trò video. Liên kết phủ ở PHP dẫn người
 *    đọc tới bài viết video tương ứng khi nhấp vào mỗi khung.
 *  - prefers-reduced-motion: reduce -> KHÔNG chèn iframe (không tự phát gì
 *    cả), chỉ giữ ảnh giữ chỗ tĩnh.
 *
 * Quét theo KHE, không theo "sân khấu" (đổi 21/08/2026): thẻ nhỏ tràn mép của
 * bản trang chủ nằm ngoài sân khấu — xem chú thích ở nntmInitAllVideoSlots().
 * Một trang có thể có nhiều khối engineering-earth; mọi khe đều được xử lý,
 * và việc chèn iframe là idempotent nên không sợ trùng.
 */
( function () {
	'use strict';

	function nntmPrefersReducedMotion() {
		return !! ( window.matchMedia && window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches );
	}

	function nntmYoutubeBgEmbedUrl( videoId ) {
		return 'https://www.youtube.com/embed/' + encodeURIComponent( videoId ) +
			'?autoplay=1&mute=1&loop=1&controls=0&disablekb=1&fs=0&iv_load_policy=3&modestbranding=1&playsinline=1&rel=0&showinfo=0&playlist=' +
			encodeURIComponent( videoId );
	}

	/**
	 * Chèn iframe MỘT LẦN cho một khe có video. Idempotent — gọi lại không tạo
	 * iframe thứ hai (chặn bằng embedHost.firstChild).
	 *
	 * @param {Element} slot Phần tử ".nntm-engineering-earth__video-slot".
	 */
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
		iframe.addEventListener( 'load', function () {
			window.setTimeout( function () {
				slot.classList.add( 'is-loaded' );
			}, 900 );
		} );
		embedHost.appendChild( iframe );
	}

	/*
	 * SỬA 21/08/2026: quét THẲNG mọi ".__video-slot" trên trang, không đi qua
	 * ".__video-stage" nữa. Lý do: thẻ nhỏ tràn mép của bản trang chủ
	 * (".__figma-pip") nằm NGOÀI sân khấu — nó là em ruột của cả dải đen, chứ
	 * không nằm trong grid của sân khấu (xem render.php). Trước đây thẻ đó là
	 * ảnh tĩnh nên không cần; nay nó là video thật (bgVideoUrl) và phải được
	 * chèn iframe như mọi khe khác. Lớp bọc sân khấu không mang thông tin gì
	 * cho việc chèn iframe, nên bỏ hẳn một tầng lặp.
	 */
	function nntmInitAllVideoSlots() {
		if ( nntmPrefersReducedMotion() ) {
			return; // Không tự phát gì cả, chỉ giữ ảnh giữ chỗ tĩnh.
		}

		var slots = document.querySelectorAll( '.nntm-engineering-earth__video-slot' );

		for ( var i = 0; i < slots.length; i++ ) {
			nntmInsertEmbed( slots[ i ] );
		}
	}

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', nntmInitAllVideoSlots );
	} else {
		nntmInitAllVideoSlots();
	}
} )();
