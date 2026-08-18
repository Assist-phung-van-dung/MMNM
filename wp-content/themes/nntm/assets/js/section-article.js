/**
 * Carousel "Bài viết liên quan": đúng 3 card desktop, chạy từng 1 card.
 */
( function () {
	'use strict';

	function initCarousel( root ) {
		var track = root.querySelector( '[data-nntm-related-track]' );
		var prev = root.querySelector( '[data-nntm-related-prev]' );
		var next = root.querySelector( '[data-nntm-related-next]' );
		var cards = track ? Array.prototype.slice.call( track.querySelectorAll( '.nntm-related-card' ) ) : [];
		if ( ! track || ! prev || ! next || ! cards.length ) {
			return;
		}

		var currentIndex = 0;
		var timer = null;
		var autoplayMs = parseInt( root.getAttribute( 'data-nntm-related-autoplay' ), 10 ) || 5000;
		var reduceMotion = window.matchMedia && window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;

		function gapSize() {
			var style = window.getComputedStyle( track );
			return parseFloat( style.columnGap || style.gap || '0' ) || 0;
		}

		function step() {
			return cards[ 0 ].getBoundingClientRect().width + gapSize();
		}

		function visibleCount() {
			var value = Math.round( ( track.clientWidth + gapSize() ) / step() );
			return Math.max( 1, value || 1 );
		}

		function maxIndex() {
			return Math.max( 0, cards.length - visibleCount() );
		}

		function hasOverflow() {
			return maxIndex() > 0;
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
			currentIndex = normalizeIndex( index );
			track.scrollTo( {
				left: currentIndex * step(),
				behavior: smooth && ! reduceMotion ? 'smooth' : 'auto',
			} );
		}

		function update() {
			var overflow = hasOverflow();
			prev.hidden = ! overflow;
			next.hidden = ! overflow;
			if ( ! overflow ) {
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
			if ( reduceMotion || ! hasOverflow() || autoplayMs < 1000 || document.hidden ) {
				return;
			}
			timer = window.setInterval( function () {
				goTo( currentIndex + 1, true );
			}, autoplayMs );
		}

		function resetAutoplay() {
			startAutoplay();
		}

		prev.addEventListener( 'click', function () {
			goTo( currentIndex - 1, true );
			resetAutoplay();
		} );
		next.addEventListener( 'click', function () {
			goTo( currentIndex + 1, true );
			resetAutoplay();
		} );

		track.addEventListener( 'keydown', function ( event ) {
			if ( 'ArrowLeft' === event.key ) {
				event.preventDefault();
				goTo( currentIndex - 1, true );
				resetAutoplay();
			} else if ( 'ArrowRight' === event.key ) {
				event.preventDefault();
				goTo( currentIndex + 1, true );
				resetAutoplay();
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
			update();
			goTo( Math.min( currentIndex, maxIndex() ), false );
			startAutoplay();
		} );

		update();
		goTo( 0, false );
		startAutoplay();
	}

	var carousels = document.querySelectorAll( '[data-nntm-related-carousel]' );
	for ( var i = 0; i < carousels.length; i++ ) {
		initCarousel( carousels[ i ] );
	}
} )();
