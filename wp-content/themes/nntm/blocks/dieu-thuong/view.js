( function () {
	'use strict';

	function initBanner( root ) {
		var banner = root.querySelector( '[data-dt-banner]' );
		if ( ! banner ) { return; }

		var slides = Array.from( banner.querySelectorAll( '[data-dt-banner-slide]' ) );
		if ( slides.length < 2 || root.dataset.bannerAutoplay !== '1' || window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches ) {
			return;
		}

		var current = 0;
		var seconds = parseInt( root.dataset.bannerInterval || '6', 10 );
		seconds = Number.isFinite( seconds ) ? Math.max( 3, Math.min( 20, seconds ) ) : 6;

		window.setInterval( function () {
			slides[ current ].classList.remove( 'is-active' );
			slides[ current ].setAttribute( 'aria-hidden', 'true' );
			current = ( current + 1 ) % slides.length;
			slides[ current ].classList.add( 'is-active' );
			slides[ current ].setAttribute( 'aria-hidden', 'false' );
		}, seconds * 1000 );
	}

	document.querySelectorAll( '.nntm-dt' ).forEach( initBanner );
} )();
