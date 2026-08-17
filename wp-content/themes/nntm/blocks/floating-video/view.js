/**
 * Front-end behaviour for nntm/floating-video.
 * No network API calls and no persistent storage.
 */
( function () {
	'use strict';

	function initFloatingVideo( root ) {
		if ( ! root || root.dataset.nntmFloatingVideoReady === '1' ) {
			return;
		}
		root.dataset.nntmFloatingVideoReady = '1';

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
