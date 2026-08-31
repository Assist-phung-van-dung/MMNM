 
( function () {
	'use strict';

	var header = document.querySelector( '.nntm-header' );
	if ( ! header || ! header.classList.contains( 'nntm-header--trong' ) ) {
		return; 
	}

	var THRESHOLD = 20; 
	var isDac      = false; 

	function getScrollY() {
		return window.scrollY || window.pageYOffset || document.documentElement.scrollTop || 0;
	}

	function syncState() {
		var shouldBeDac = getScrollY() > THRESHOLD;
		if ( shouldBeDac === isDac ) {
			return;
		}
		isDac = shouldBeDac;
		header.classList.toggle( 'nntm-header--dac', isDac );
		header.classList.toggle( 'nntm-header--trong', ! isDac );
	}

	var ticking = false;

	function onScroll() {
		if ( ticking ) {
			return;
		}
		ticking = true;
		window.requestAnimationFrame( function () {
			syncState();
			ticking = false;
		} );
	}

	window.addEventListener( 'scroll', onScroll, { passive: true } );



	syncState();
} )();
