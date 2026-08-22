 
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

	function nntmInitAllVideoSlots() {
		if ( nntmPrefersReducedMotion() ) {
			return; 
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
