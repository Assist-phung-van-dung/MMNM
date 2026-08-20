/**
 * Front-end behaviour for nntm/floating-video.
 * No network API calls and no persistent storage.
 *
 * BỔ SUNG 20/08/2026 — nút đóng (chỉ có tác dụng dưới 1152px, xem
 * style.css). Trạng thái "đã đóng" ghi vào sessionStorage: nó tự mất khi
 * đóng tab nên KHÔNG phải "persistent storage" như ràng buộc ở trên, mà
 * người đọc cũng không phải tắt lại thẻ video trên từng trang.
 */
( function () {
	'use strict';

	var DISMISS_KEY = 'nntm-floating-video-dismissed';

	/**
	 * Đọc/ghi cờ đã-đóng. Bọc try/catch vì chế độ riêng tư của một số trình
	 * duyệt làm sessionStorage ném lỗi ngay khi truy cập.
	 *
	 * @param {boolean} [value] Truyền vào để ghi; bỏ trống để đọc.
	 * @return {boolean}
	 */
	function dismissed( value ) {
		try {
			if ( undefined === value ) {
				return '1' === window.sessionStorage.getItem( DISMISS_KEY );
			}
			window.sessionStorage.setItem( DISMISS_KEY, value ? '1' : '0' );
		} catch ( e ) {
			// Không dùng được sessionStorage thì chỉ đóng trong trang hiện tại.
		}
		return !! value;
	}

	/**
	 * Đóng thẻ video: ẩn hẳn và DỪNG phát. Chỉ ẩn thôi thì iframe YouTube
	 * vẫn chạy nền, tốn băng thông di động dù không ai xem.
	 *
	 * @param {Element} root Khối .nntm-floating-video.
	 */
	function dismissFloatingVideo( root ) {
		root.classList.add( 'is-dismissed' );
		dismissed( true );

		var video = root.querySelector( '.nntm-floating-video__video' );
		if ( video ) {
			video.pause();
		}

		var iframe = root.querySelector( '.nntm-floating-video__iframe' );
		if ( iframe ) {
			iframe.removeAttribute( 'src' );
		}
	}

	function initFloatingVideo( root ) {
		if ( ! root || root.dataset.nntmFloatingVideoReady === '1' ) {
			return;
		}
		root.dataset.nntmFloatingVideoReady = '1';

		var closeBtn = root.querySelector( '[data-nntm-floating-video-close]' );
		if ( closeBtn ) {
			closeBtn.addEventListener( 'click', function () {
				dismissFloatingVideo( root );
			} );
		}

		/*
		 * Đã đóng ở trang trước trong cùng phiên: ẩn ngay và KHÔNG khởi động
		 * trình phát bên dưới nữa.
		 */
		if ( dismissed() ) {
			dismissFloatingVideo( root );
			return;
		}

		var video = root.querySelector( '.nntm-floating-video__video' );
		var youtubeCover = root.querySelector( '.nntm-floating-video__youtube-cover' );

		// Browsers only allow reliable autoplay when the HTML5 video is muted.
		if ( video ) {
			video.muted = true;
			video.defaultMuted = true;
			video.controls = false;

			var playAttempt = video.play();
			if ( playAttempt && typeof playAttempt.catch === 'function' ) {
				playAttempt.catch( function () {
					// Browser/user policy can still block autoplay. Fail silently.
				} );
			}
		}

		if ( youtubeCover ) {
			var fallbackSrc = youtubeCover.getAttribute( 'data-fallback-src' );
			var fallbackUsed = false;

			youtubeCover.addEventListener( 'error', function () {
				if ( ! fallbackUsed && fallbackSrc ) {
					fallbackUsed = true;
					youtubeCover.src = fallbackSrc;
				}
			} );

			// Hide YouTube's initial title/logo/control flash. The iframe is
			// already autoplaying underneath this clean thumbnail.
			window.setTimeout( function () {
				root.classList.add( 'is-player-ready' );
			}, 2200 );
		}
	}

	function boot() {
		document.querySelectorAll( '.nntm-floating-video' ).forEach( initFloatingVideo );
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', boot );
	} else {
		boot();
	}
} )();
