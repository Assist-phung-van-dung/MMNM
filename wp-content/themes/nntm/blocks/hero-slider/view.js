 
( function () {
	'use strict';

	function prefersReducedMotion() {
		return !! ( window.matchMedia && window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches );
	}

	function nntmInitHeroEntrance( root ) {
		if ( root.classList.contains( 'is-entrance-complete' ) ) {
			return;
		}

		var selectors = [
			'.nntm-hero-slider__slide.is-active .nntm-hero-slider__heading',
			'.nntm-hero-slider__slide.is-active .nntm-hero-slider__text',
			'.nntm-hero-slider__slide.is-active .nntm-hero-slider__cta',
			'.nntm-hero-slider__dots',
			'.nntm-hero-slider__sidecard-heading',
			'.nntm-hero-slider__sidecard-text',
			'.nntm-hero-slider__sidecard-cta',
			'.nntm-hero-slider__quicklink'
		];
		var items = Array.from( root.querySelectorAll( selectors.join( ',' ) ) );

		if ( ! items.length || prefersReducedMotion() ) {
			root.classList.add( 'is-entrance-complete' );
			return;
		}

		items.sort( function ( first, second ) {
			var firstRect = first.getBoundingClientRect();
			var secondRect = second.getBoundingClientRect();
			var verticalDifference = firstRect.top - secondRect.top;

			return Math.abs( verticalDifference ) > 4 ? verticalDifference : firstRect.left - secondRect.left;
		} );

		for ( var i = 0; i < items.length; i++ ) {
			items[ i ].classList.add( 'nntm-hero-slider__entrance-item' );
			items[ i ].style.setProperty( '--nntm-hero-entrance-order', String( i ) );
		}

		root.classList.add( 'nntm-hero-slider--entrance-pending' );

		var pageRoot = document.documentElement;
		var observer = null;
		var safetyTimer = null;
		var hasStarted = false;

		function finishEntrance() {
			root.classList.remove( 'nntm-hero-slider--entrance-running' );
			root.classList.add( 'is-entrance-complete' );

			for ( var index = 0; index < items.length; index++ ) {
				items[ index ].classList.remove( 'nntm-hero-slider__entrance-item' );
				items[ index ].style.removeProperty( '--nntm-hero-entrance-order' );
			}
		}

		function playEntrance() {
			if ( hasStarted ) {
				return;
			}

			hasStarted = true;

			if ( observer ) {
				observer.disconnect();
			}

			if ( safetyTimer ) {
				window.clearTimeout( safetyTimer );
			}

			window.requestAnimationFrame( function () {
				window.requestAnimationFrame( function () {
					root.classList.remove( 'nntm-hero-slider--entrance-pending' );
					root.classList.add( 'nntm-hero-slider--entrance-running' );

					var totalDuration = 760 + Math.max( 0, items.length - 1 ) * 110;
					window.setTimeout( finishEntrance, totalDuration );
				} );
			} );
		}

		if ( ! pageRoot.classList.contains( 'is-loading' ) ) {
			playEntrance();
			return;
		}

		if ( window.MutationObserver ) {
			observer = new window.MutationObserver( function () {
				if ( ! pageRoot.classList.contains( 'is-loading' ) ) {
					playEntrance();
				}
			} );
			observer.observe( pageRoot, { attributes: true, attributeFilter: [ 'class' ] } );
		}

		safetyTimer = window.setTimeout( playEntrance, 9500 );
	}

	function nntmInitHeroSlider( root ) {
		var stage = root.querySelector( '.nntm-hero-slider__stage' );
		var slides = root.querySelectorAll( '.nntm-hero-slider__slide' );
		var dotsWrap = root.querySelector( '[data-nntm-hero-dots]' );
		var dots = root.querySelectorAll( '.nntm-hero-slider__dot' );
		var statusEl = root.querySelector( '[data-nntm-hero-status]' );
		var prevBtn = root.querySelector( '[data-nntm-hero-prev]' );
		var nextBtn = root.querySelector( '[data-nntm-hero-next]' );

		if ( ! stage || slides.length < 2 ) {

			return;
		}

		var total = slides.length;
		var currentIndex = 0;
		var timerId = null;
		var isPaused = false;


		var autoplayEnabled = 'true' === String( root.getAttribute( 'data-nntm-autoplay' ) ) || '1' === root.getAttribute( 'data-nntm-autoplay' );
		var intervalSeconds = parseFloat( root.getAttribute( 'data-nntm-interval' ) );
		if ( ! isFinite( intervalSeconds ) || intervalSeconds <= 0 ) {
			intervalSeconds = 6;
		}
		intervalSeconds = Math.max( 2, Math.min( 30, intervalSeconds ) );

		var autoplayAllowed = autoplayEnabled && ! prefersReducedMotion();

		function statusText( index ) {


			return ( window.nntmHeroSliderI18n && window.nntmHeroSliderI18n.tamTrenTong )
				? window.nntmHeroSliderI18n.tamTrenTong.replace( '%1$d', String( index + 1 ) ).replace( '%2$d', String( total ) )
				: 'Tấm ' + ( index + 1 ) + ' trên ' + total;
		}

		function goTo( index ) {
			var nextIndex = ( index + total ) % total;

			for ( var i = 0; i < slides.length; i++ ) {
				slides[ i ].classList.toggle( 'is-active', i === nextIndex );
			}
			for ( var d = 0; d < dots.length; d++ ) {
				var isCurrent = d === nextIndex;
				dots[ d ].classList.toggle( 'is-active', isCurrent );
				if ( isCurrent ) {
					dots[ d ].setAttribute( 'aria-current', 'true' );
				} else {
					dots[ d ].removeAttribute( 'aria-current' );
				}
			}

			currentIndex = nextIndex;

			if ( statusEl ) {
				statusEl.textContent = statusText( currentIndex );
			}
		}

		function goNext() {
			goTo( currentIndex + 1 );
		}

		function goPrev() {
			goTo( currentIndex - 1 );
		}



		function restartTimerAfterInteraction() {
			if ( autoplayAllowed && ! isPaused ) {
				stopTimer();
				startTimer();
			}
		}

		function startTimer() {
			if ( ! autoplayAllowed || isPaused || null !== timerId || document.hidden ) {
				return;
			}
			timerId = window.setInterval( goNext, intervalSeconds * 1000 );
		}

		function stopTimer() {
			if ( null !== timerId ) {
				window.clearInterval( timerId );
				timerId = null;
			}
		}

		function pause() {
			isPaused = true;
			stopTimer();
		}

		function resume() {
			isPaused = false;
			startTimer();
		}

		root.addEventListener( 'mouseenter', pause );
		root.addEventListener( 'mouseleave', resume );

		root.addEventListener( 'focusin', pause );
		root.addEventListener( 'focusout', function ( event ) {


			if ( ! event.relatedTarget || ! root.contains( event.relatedTarget ) ) {
				resume();
			}
		} );

		document.addEventListener( 'visibilitychange', function () {
			if ( document.hidden ) {
				stopTimer();
			} else {
				startTimer();
			}
		} );

		for ( var t = 0; t < dots.length; t++ ) {
			( function ( index ) {
				dots[ index ].addEventListener( 'click', function () {
					goTo( index );
					restartTimerAfterInteraction();
				} );
			} )( t );
		}

		if ( prevBtn ) {
			prevBtn.addEventListener( 'click', function () {
				goPrev();
				restartTimerAfterInteraction();
			} );
		}

		if ( nextBtn ) {
			nextBtn.addEventListener( 'click', function () {
				goNext();
				restartTimerAfterInteraction();
			} );
		}

		stage.addEventListener( 'keydown', function ( event ) {
			if ( 'ArrowLeft' === event.key ) {
				event.preventDefault();
				goPrev();
			} else if ( 'ArrowRight' === event.key ) {
				event.preventDefault();
				goNext();
			}
		} );

		if ( dotsWrap ) {
			dotsWrap.setAttribute( 'tabindex', dotsWrap.getAttribute( 'tabindex' ) || '-1' );
		}

		goTo( 0 );
		startTimer();
	}

	function nntmInitAllHeroSliders() {
		var sliders = document.querySelectorAll( '.nntm-hero-slider' );
		for ( var i = 0; i < sliders.length; i++ ) {
			nntmInitHeroEntrance( sliders[ i ] );
			nntmInitHeroSlider( sliders[ i ] );
		}
	}



	window.nntmHeroSliderI18n = window.nntmHeroSliderI18n || {
		tamTrenTong: 'Tấm %1$d trên %2$d',
	};

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', nntmInitAllHeroSliders );
	} else {
		nntmInitAllHeroSliders();
	}
} )();
