 
( function () {
	'use strict';

	function initCarousel( root ) {
		var track = root.querySelector( '[data-nntm-an-pham-track]' );
		var prev = root.querySelector( '[data-nntm-an-pham-prev]' );
		var next = root.querySelector( '[data-nntm-an-pham-next]' );
		var cards = track ? Array.prototype.slice.call( track.querySelectorAll( '.nntm-an-pham-card' ) ) : [];
		if ( ! track || ! prev || ! next || ! cards.length ) {
			return;
		}

		var currentIndex = 0;
		var timer = null;
		var autoplayMs = parseInt( root.getAttribute( 'data-nntm-an-pham-autoplay' ), 10 ) || 5000;
		var reduceMotion = window.matchMedia && window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;

		function gapSize() {
			var style = window.getComputedStyle( track );
			return parseFloat( style.columnGap || style.gap || '0' ) || 0;
		}

		function step() {
			if ( ! cards[ 0 ] ) {
				return 0;
			}

			return cards[ 0 ].offsetWidth + gapSize();
		}

		function visibleCount() {
			var itemStep = step();
			if ( itemStep <= 0 ) {
				return 1;
			}
			return Math.max( 1, Math.round( ( track.clientWidth + gapSize() ) / itemStep ) );
		}

		function maxIndex() {
			return Math.max( 0, cards.length - visibleCount() );
		}

		function normalizeIndex( index ) {
			var max = maxIndex();
			if ( max <= 0 ) {
				return 0;
			}
			if ( index > max ) {
				return 0;
			}
			if ( index < 0 ) {
				return max;
			}
			return index;
		}

		function goTo( index, smooth ) {
			var itemStep = step();
			currentIndex = normalizeIndex( index );
			track.scrollTo( {
				left: currentIndex * itemStep,
				behavior: smooth && ! reduceMotion ? 'smooth' : 'auto',
			} );
		}

		function updateNav() {
			var hasOverflow = maxIndex() > 0;
			prev.hidden = ! hasOverflow;
			next.hidden = ! hasOverflow;
			if ( ! hasOverflow ) {
				currentIndex = 0;
			}
		}

		function stopAutoplay() {
			if ( timer ) {
				window.clearInterval( timer );
				timer = null;
			}
		}

		function startAutoplay() {
			stopAutoplay();
			if ( reduceMotion || maxIndex() <= 0 || autoplayMs < 1000 || document.hidden ) {
				return;
			}
			timer = window.setInterval( function () {
				goTo( currentIndex + 1, true );
			}, autoplayMs );
		}

		prev.addEventListener( 'click', function () {
			goTo( currentIndex - 1, true );
			startAutoplay();
		} );

		next.addEventListener( 'click', function () {
			goTo( currentIndex + 1, true );
			startAutoplay();
		} );

		track.addEventListener( 'keydown', function ( event ) {
			if ( 'ArrowLeft' === event.key ) {
				event.preventDefault();
				goTo( currentIndex - 1, true );
				startAutoplay();
			} else if ( 'ArrowRight' === event.key ) {
				event.preventDefault();
				goTo( currentIndex + 1, true );
				startAutoplay();
			}
		} );

		root.addEventListener( 'mouseenter', stopAutoplay );
		root.addEventListener( 'mouseleave', startAutoplay );
		root.addEventListener( 'focusin', stopAutoplay );
		root.addEventListener( 'focusout', function ( event ) {
			if ( ! root.contains( event.relatedTarget ) ) {
				startAutoplay();
			}
		} );

		document.addEventListener( 'visibilitychange', function () {
			if ( document.hidden ) {
				stopAutoplay();
			} else {
				startAutoplay();
			}
		} );

		window.addEventListener( 'resize', function () {
			updateNav();
			goTo( Math.min( currentIndex, maxIndex() ), false );
			startAutoplay();
		} );

		updateNav();
		goTo( 0, false );
		startAutoplay();
	}

	var carousels = document.querySelectorAll( '[data-nntm-an-pham-carousel]' );
	for ( var i = 0; i < carousels.length; i++ ) {
		initCarousel( carousels[ i ] );
	}
} )();
