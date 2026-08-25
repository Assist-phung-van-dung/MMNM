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

	function initGallery( root ) {
		var gallery = root.querySelector( '[data-dt-gallery]' );
		if ( ! gallery ) { return; }

		var track = gallery.querySelector( '[data-dt-gallery-track]' );
		var prev = gallery.querySelector( '[data-dt-gallery-prev]' );
		var next = gallery.querySelector( '[data-dt-gallery-next]' );
		if ( ! track || ! prev || ! next ) { return; }

		var cards = Array.from( track.querySelectorAll( '.nntm-dt__gallery-card' ) );
		if ( ! cards.length ) { return; }

		var timer = null;
		var restartTimer = null;

		function stepWidth() {
			var gap = parseFloat( window.getComputedStyle( track ).gap ) || 20;
			return cards[ 0 ].offsetWidth + gap;
		}

		function maxScroll() {
			return Math.max( 0, track.scrollWidth - track.clientWidth );
		}

		function hasOverflow() {
			return maxScroll() > 1;
		}

		function syncButtons() {
			var visible = hasOverflow();
			prev.hidden = ! visible;
			next.hidden = ! visible;

			if ( visible ) {
				prev.disabled = track.scrollLeft <= 1;
				next.disabled = track.scrollLeft >= maxScroll() - 1;
			}
		}

		function move( direction ) {
			var target = Math.max( 0, Math.min( maxScroll(), track.scrollLeft + direction * stepWidth() ) );
			var behavior = window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches ? 'auto' : 'smooth';
			track.scrollTo( { left: target, behavior: behavior } );
		}

		function stop() {
			if ( timer ) {
				window.clearInterval( timer );
				timer = null;
			}

			if ( restartTimer ) {
				window.clearTimeout( restartTimer );
				restartTimer = null;
			}
		}

		function scheduleRestart() {
			var seconds = Math.max( 0, Math.min( 60, parseInt( gallery.dataset.galleryLoopDelay || '0', 10 ) ) );
			if ( seconds <= 0 || restartTimer ) { return; }

			restartTimer = window.setTimeout( function () {
				restartTimer = null;
				var behavior = window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches ? 'auto' : 'smooth';
				track.scrollTo( { left: 0, behavior: behavior } );
				start();
			}, seconds * 1000 );
		}

		function start() {
			stop();

			if ( gallery.dataset.galleryAutoplay !== '1' || window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches ) {
				return;
			}

			if ( ! hasOverflow() || track.scrollLeft >= maxScroll() - 1 ) {
				return;
			}

			var seconds = Math.max( 2, Math.min( 20, parseInt( gallery.dataset.galleryInterval || '5', 10 ) ) );
			timer = window.setInterval( function () {
				if ( track.scrollLeft >= maxScroll() - 1 ) {
					stop();
					scheduleRestart();
					return;
				}
				move( 1 );
			}, seconds * 1000 );
		}

		var scrollPending = false;
		track.addEventListener( 'scroll', function () {
			if ( scrollPending ) { return; }
			scrollPending = true;
			window.requestAnimationFrame( function () {
				syncButtons();
				if ( track.scrollLeft >= maxScroll() - 1 ) {
					scheduleRestart();
				}
				scrollPending = false;
			} );
		}, { passive: true } );

		prev.addEventListener( 'click', function () {
			move( -1 );
			stop();
		} );

		next.addEventListener( 'click', function () {
			move( 1 );
			start();
		} );

		gallery.addEventListener( 'mouseenter', stop );
		gallery.addEventListener( 'mouseleave', start );
		gallery.addEventListener( 'focusin', stop );
		gallery.addEventListener( 'focusout', function ( event ) {
			if ( ! event.relatedTarget || ! gallery.contains( event.relatedTarget ) ) {
				start();
			}
		} );

		document.addEventListener( 'visibilitychange', function () {
			if ( document.hidden ) {
				stop();
			} else {
				start();
			}
		} );

		if ( window.ResizeObserver ) {
			new window.ResizeObserver( syncButtons ).observe( track );
		} else {
			window.addEventListener( 'resize', syncButtons );
		}

		syncButtons();
		start();
	}

	document.querySelectorAll( '.nntm-dt' ).forEach( function ( root ) {
		initBanner( root );
		initGallery( root );
	} );
} )();
