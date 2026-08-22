 
( function () {
	'use strict';

	function nntmInitCardListCarousel( root ) {
		var track = root.querySelector( '.nntm-card-list__track' );
		var prevBtn = root.querySelector( '.nntm-card-list__nav--prev' );
		var nextBtn = root.querySelector( '.nntm-card-list__nav--next' );

		if ( ! track || ! prevBtn || ! nextBtn ) {
			return;
		}

		function prefersReducedMotion() {
			return !! ( window.matchMedia && window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches );
		}


		function scrollStep() {
			var firstItem = track.querySelector( '.nntm-card-list__track-item' );

			var itemWidth = firstItem ? firstItem.offsetWidth : track.clientWidth;
			var trackStyles = window.getComputedStyle( track );
			var gap = parseFloat( trackStyles.columnGap || trackStyles.gap || '0' ) || 0;

			return itemWidth + gap;
		}


		var hasOverflow = false;

		function updateButtonsState() {
			var maxScroll = track.scrollWidth - track.clientWidth;
			hasOverflow = maxScroll > 1;


			prevBtn.hidden = ! hasOverflow;
			nextBtn.hidden = ! hasOverflow;

			if ( ! hasOverflow ) {
				prevBtn.disabled = true;
				nextBtn.disabled = true;
				nntmSyncAutoplay();
				return;
			}

			prevBtn.disabled = track.scrollLeft <= 1;
			nextBtn.disabled = track.scrollLeft >= maxScroll - 1;
			nntmSyncAutoplay();
		}



		var isProgrammaticScroll = false;

		function scrollByDirection( direction ) {
			isProgrammaticScroll = true;
			track.scrollBy( {
				left: direction * scrollStep(),
				behavior: prefersReducedMotion() ? 'auto' : 'smooth',
			} );
		}

		function scrollToStart() {
			isProgrammaticScroll = true;
			track.scrollTo( {
				left: 0,
				behavior: prefersReducedMotion() ? 'auto' : 'smooth',
			} );
		}

		prevBtn.addEventListener( 'click', function () {
			scrollByDirection( -1 );
		} );

		nextBtn.addEventListener( 'click', function () {
			scrollByDirection( 1 );
		} );



		track.addEventListener( 'keydown', function ( event ) {
			if ( 'ArrowRight' === event.key ) {
				event.preventDefault();
				scrollByDirection( 1 );
			} else if ( 'ArrowLeft' === event.key ) {
				event.preventDefault();
				scrollByDirection( -1 );
			}
		} );



		var scrollUpdateTimer = null;
		track.addEventListener(
			'scroll',
			function () {
				if ( scrollUpdateTimer ) {
					window.clearTimeout( scrollUpdateTimer );
				}
				scrollUpdateTimer = window.setTimeout( function () {
					updateButtonsState();

					if ( ! isProgrammaticScroll ) {
						userScrolledManually = true;
						stopAutoplayTimer();
					}
					isProgrammaticScroll = false;
				}, 50 );
			},
			{ passive: true }
		);

		window.addEventListener( 'resize', updateButtonsState );



		var autoplayAttrEnabled = 'true' === root.getAttribute( 'data-autoplay' );
		var autoplayIntervalMs = ( function () {
			var seconds = parseFloat( root.getAttribute( 'data-autoplay-interval' ) );
			if ( ! seconds || isNaN( seconds ) ) {
				seconds = 6;
			}

			seconds = Math.max( 2, Math.min( 20, seconds ) );
			return seconds * 1000;
		} )();

		var userScrolledManually = false; 
		var isHovered = false;
		var isFocusedWithin = false;
		var autoplayTimerId = null;

		function autoplayEligible() {
			return autoplayAttrEnabled && hasOverflow && ! prefersReducedMotion() && ! userScrolledManually;
		}

		function autoplayShouldRunNow() {
			return autoplayEligible() && ! isHovered && ! isFocusedWithin && ! document.hidden;
		}

		function autoplayTick() {
			var maxScroll = track.scrollWidth - track.clientWidth;

			if ( track.scrollLeft >= maxScroll - 1 ) {

				scrollToStart();
			} else {
				scrollByDirection( 1 );
			}
		}

		function startAutoplayTimer() {
			if ( autoplayTimerId ) {
				return;
			}
			autoplayTimerId = window.setInterval( autoplayTick, autoplayIntervalMs );
		}

		function stopAutoplayTimer() {
			if ( autoplayTimerId ) {
				window.clearInterval( autoplayTimerId );
				autoplayTimerId = null;
			}
		}



		function nntmSyncAutoplay() {
			if ( autoplayShouldRunNow() ) {
				startAutoplayTimer();
			} else {
				stopAutoplayTimer();
			}
		}

		root.addEventListener( 'mouseenter', function () {
			isHovered = true;
			nntmSyncAutoplay();
		} );
		root.addEventListener( 'mouseleave', function () {
			isHovered = false;
			nntmSyncAutoplay();
		} );


		root.addEventListener( 'focusin', function () {
			isFocusedWithin = true;
			nntmSyncAutoplay();
		} );
		root.addEventListener( 'focusout', function () {
			isFocusedWithin = false;
			nntmSyncAutoplay();
		} );


		document.addEventListener( 'visibilitychange', nntmSyncAutoplay );

		updateButtonsState();
	}

	function nntmInitAllCardListCarousels( root ) {
		var carousels = ( root || document ).querySelectorAll( '.nntm-card-list__carousel' );

		for ( var i = 0; i < carousels.length; i++ ) {
			nntmInitCardListCarousel( carousels[ i ] );
		}
	}

	var NNTM_YT_HOVER_DELAY_MS = 350;

	function nntmPrefersReducedMotion() {
		return !! ( window.matchMedia && window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches );
	}

	function nntmYoutubeEmbedUrl( videoId ) {
		return 'https://www.youtube.com/embed/' + encodeURIComponent( videoId ) +
			'?autoplay=1&mute=1&controls=0&modestbranding=1&playsinline=1&rel=0';
	}

	function nntmInitYoutubeItem( item ) {
		var videoId = item.getAttribute( 'data-video-id' );
		var frame   = item.querySelector( '.nntm-card-list__yt-frame' );
		var thumb   = item.querySelector( '.nntm-card-list__yt-thumb' );
		var hoverTimerId = null;



		if ( thumb ) {
			var triedFallback = false;
			thumb.addEventListener( 'error', function () {
				if ( triedFallback ) {
					return;
				}
				triedFallback = true;
				var fallbackUrl = thumb.getAttribute( 'data-fallback' );
				if ( fallbackUrl ) {
					thumb.src = fallbackUrl;
				}
			} );
		}

		function clearHoverTimer() {
			if ( hoverTimerId ) {
				window.clearTimeout( hoverTimerId );
				hoverTimerId = null;
			}
		}

		var embedGeneration = 0;

		function removeEmbed() {
			clearHoverTimer();
			embedGeneration++;
			item.classList.remove( 'is-playing' );
			if ( frame ) {
				frame.innerHTML = '';
			}
		}

		function insertEmbed() {
			if ( ! frame || ! videoId || nntmPrefersReducedMotion() ) {
				return;
			}
			if ( frame.firstChild ) {
				return; 
			}

			var iframe = document.createElement( 'iframe' );
			var generation = ++embedGeneration;

			iframe.src = nntmYoutubeEmbedUrl( videoId );
			iframe.setAttribute( 'title', item.getAttribute( 'aria-label' ) || '' );
			iframe.setAttribute( 'frameborder', '0' );
			iframe.setAttribute( 'allow', 'autoplay; encrypted-media' );
			iframe.setAttribute( 'tabindex', '-1' );
			iframe.setAttribute( 'referrerpolicy', 'strict-origin-when-cross-origin' );



			iframe.addEventListener( 'load', function () {
				if ( generation !== embedGeneration || ! frame.contains( iframe ) ) {
					return;
				}
				item.classList.add( 'is-playing' );
			}, { once: true } );

			frame.appendChild( iframe );
		}

		function scheduleEmbed() {
			if ( nntmPrefersReducedMotion() ) {
				return;
			}
			clearHoverTimer();
			hoverTimerId = window.setTimeout( insertEmbed, NNTM_YT_HOVER_DELAY_MS );
		}


		item.addEventListener( 'mouseenter', scheduleEmbed );
		item.addEventListener( 'mouseleave', removeEmbed );


		item.addEventListener( 'focus', scheduleEmbed );
		item.addEventListener( 'blur', removeEmbed );
	}

	function nntmInitAllYoutubeMarquees( root ) {
		var items = ( root || document ).querySelectorAll( '.nntm-card-list__yt-item' );

		for ( var i = 0; i < items.length; i++ ) {
			nntmInitYoutubeItem( items[ i ] );
		}
	}

	function nntmInitCardListView( root ) {
		nntmInitAllCardListCarousels( root );
		nntmInitAllYoutubeMarquees( root );
	}

	document.addEventListener( 'nntm-card-list-refresh', function ( event ) {
		nntmInitCardListView( ( event.detail && event.detail.root ) || document );
	} );

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', function () {
			nntmInitCardListView();
		} );
	} else {
		nntmInitCardListView();
	}
} )();
