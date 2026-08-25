( function () {
	'use strict';

	function initCarousel( root ) {
		var slider = root.querySelector( '.nntm-feature-carousel__slider' );
		if ( ! slider ) {
			return;
		}

		var slides = Array.from( slider.querySelectorAll( '[data-fc-slide]' ) );
		if ( ! slides.length ) {
			return;
		}

		var current = 0;
		var timer = null;
		var pointerStartX = null;
		var reducedMotion = window.matchMedia( '(prefers-reduced-motion: reduce)' );
		var pendingVideo = null;
		var loadingObserver = null;

		function signedDistance( index ) {
			var total = slides.length;
			var distance = ( index - current + total ) % total;
			if ( distance > total / 2 ) {
				distance -= total;
			}
			return distance;
		}

		function playVideo( video ) {
			if ( reducedMotion.matches ) {
				return;
			}

			if ( document.documentElement.classList.contains( 'is-loading' ) ) {
				pendingVideo = video;

				if ( ! loadingObserver && window.MutationObserver ) {
					loadingObserver = new window.MutationObserver( function () {
						if ( document.documentElement.classList.contains( 'is-loading' ) ) {
							return;
						}

						loadingObserver.disconnect();
						loadingObserver = null;
						var videoCho = pendingVideo;
						pendingVideo = null;

						if ( videoCho && videoCho.closest( '[data-fc-slide]' ).getAttribute( 'data-position' ) === '0' ) {
							playVideo( videoCho );
						}
					} );
					loadingObserver.observe( document.documentElement, { attributes: true, attributeFilter: [ 'class' ] } );
				}
				return;
			}

			if ( video.ended ) {
				video.currentTime = 0;
			}

			var playPromise = video.play();
			if ( playPromise && 'function' === typeof playPromise.catch ) {
				playPromise.catch( function () {} );
			}
		}

		function paint() {
			slides.forEach( function ( slide, index ) {
				var distance = signedDistance( index );
				var visibleDistance = Math.max( -3, Math.min( 3, distance ) );

				var viTriCu = slide.getAttribute( 'data-position' );
				var vongQuaBia = null !== viTriCu && Math.abs( visibleDistance - parseInt( viTriCu, 10 ) ) > 1;

				if ( vongQuaBia ) {
					slide.classList.add( 'is-teleport' );
				}

				slide.setAttribute( 'data-position', String( visibleDistance ) );
				slide.setAttribute( 'aria-hidden', distance === 0 ? 'false' : 'true' );

				var video = slide.querySelector( '[data-fc-video]' );
				if ( video ) {
					if ( distance === 0 ) {
						video.removeAttribute( 'tabindex' );
						window.requestAnimationFrame( function () { playVideo( video ); } );
					} else {
						if ( pendingVideo === video ) {
							pendingVideo = null;
						}
						video.setAttribute( 'tabindex', '-1' );
						if ( ! video.paused ) {
							video.pause();
						}
						if ( video.currentTime > 0 ) {
							video.currentTime = 0;
						}
					}
				}

				if ( vongQuaBia ) {
					 
					window.requestAnimationFrame( function () {
						window.requestAnimationFrame( function () {
							slide.classList.remove( 'is-teleport' );
						} );
					} );
				}
			} );
		}

		function stop() {
			if ( timer ) {
				window.clearInterval( timer );
				timer = null;
			}
		}

		function start() {
			stop();
			if ( slides.length < 2 || root.dataset.autoplay !== '1' || reducedMotion.matches || pendingVideo ) {
				return;
			}

			var activeVideo = slides[ current ].querySelector( '[data-fc-video]' );
			if ( activeVideo && ! activeVideo.paused && ! activeVideo.ended ) {
				return;
			}

			var seconds = parseInt( root.dataset.interval || '6', 10 );
			seconds = Number.isFinite( seconds ) ? Math.max( 3, Math.min( 20, seconds ) ) : 6;
			timer = window.setInterval( function () { go( 1, false ); }, seconds * 1000 );
		}

		function go( direction, restart ) {
			if ( slides.length < 2 ) {
				return;
			}
			current = ( current + direction + slides.length ) % slides.length;
			paint();
			if ( restart !== false ) {
				start();
			}
		}

		var prev = slider.querySelector( '[data-fc-prev]' );
		var next = slider.querySelector( '[data-fc-next]' );
		if ( prev ) { prev.addEventListener( 'click', function () { go( -1, true ); } ); }
		if ( next ) { next.addEventListener( 'click', function () { go( 1, true ); } ); }

		var track = slider.querySelector( '[data-fc-track]' );
		if ( track ) {
			track.addEventListener( 'keydown', function ( event ) {
				if ( event.key === 'ArrowLeft' ) { event.preventDefault(); go( -1, true ); }
				if ( event.key === 'ArrowRight' ) { event.preventDefault(); go( 1, true ); }
			} );
		}

		slider.addEventListener( 'pointerdown', function ( event ) {
			if ( event.target.closest( 'video, button, a, input, select, textarea' ) ) {
				pointerStartX = null;
				return;
			}
			pointerStartX = event.clientX;
		} );
		slider.addEventListener( 'pointerup', function ( event ) {
			if ( pointerStartX === null ) { return; }
			var delta = event.clientX - pointerStartX;
			pointerStartX = null;
			if ( Math.abs( delta ) >= 45 ) { go( delta < 0 ? 1 : -1, true ); }
		} );
		slider.addEventListener( 'pointercancel', function () { pointerStartX = null; } );
		slider.addEventListener( 'mouseenter', stop );
		slider.addEventListener( 'mouseleave', start );
		slider.addEventListener( 'focusin', stop );
		slider.addEventListener( 'focusout', function ( event ) {
			if ( ! slider.contains( event.relatedTarget ) ) { start(); }
		} );

		slides.forEach( function ( slide ) {
			var video = slide.querySelector( '[data-fc-video]' );
			if ( ! video ) {
				return;
			}

			video.addEventListener( 'play', function () {
				if ( slide.getAttribute( 'data-position' ) === '0' ) {
					stop();
				}
			} );
			video.addEventListener( 'pause', function () {
				if (
					slide.getAttribute( 'data-position' ) === '0' &&
					! slider.matches( ':hover' ) &&
					! slider.contains( document.activeElement )
				) {
					start();
				}
			} );
			video.addEventListener( 'ended', function () {
				if ( slide.getAttribute( 'data-position' ) !== '0' ) {
					return;
				}

				if ( slides.length > 1 && root.dataset.autoplay === '1' && ! reducedMotion.matches ) {
					go( 1, true );
				}
			} );
		} );

		paint();

		window.requestAnimationFrame( function () {
			window.requestAnimationFrame( function () {
				root.classList.add( 'is-ready' );
			} );
		} );

		start();
	}

	document.querySelectorAll( '.nntm-feature-carousel' ).forEach( initCarousel );
} )();
