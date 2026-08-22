 
( function () {
	'use strict';

	var DISMISS_KEY = 'nntm-floating-video-dismissed';

	function dismissed( value ) {
		try {
			if ( undefined === value ) {
				return '1' === window.sessionStorage.getItem( DISMISS_KEY );
			}
			window.sessionStorage.setItem( DISMISS_KEY, value ? '1' : '0' );
		} catch ( e ) {

		}
		return !! value;
	}

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

		if ( dismissed() ) {
			dismissFloatingVideo( root );
			return;
		}

		var video = root.querySelector( '.nntm-floating-video__video' );
		var youtubeCover = root.querySelector( '.nntm-floating-video__youtube-cover' );

		if ( video ) {
			video.muted = true;
			video.defaultMuted = true;
			video.controls = false;

			var playAttempt = video.play();
			if ( playAttempt && typeof playAttempt.catch === 'function' ) {
				playAttempt.catch( function () {

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
