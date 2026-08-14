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
 *  - Không xử lý hover hoặc đổi vai trò video. Liên kết phủ ở PHP dẫn người
 *    đọc tới bài viết video tương ứng khi nhấp vào mỗi khung.
 *  - prefers-reduced-motion: reduce -> KHÔNG chèn iframe (không tự phát gì
 *    cả), chỉ giữ ảnh giữ chỗ tĩnh.
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
			'?autoplay=1&mute=1&loop=1&controls=0&disablekb=1&fs=0&iv_load_policy=3&modestbranding=1&playsinline=1&rel=0&showinfo=0&playlist=' +
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
			iframe.addEventListener( 'load', function () {
				window.setTimeout( function () {
					slot.classList.add( 'is-loaded' );
				}, 900 );
			} );
			embedHost.appendChild( iframe );
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
