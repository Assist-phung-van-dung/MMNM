( function () {
	'use strict';

	document.querySelectorAll( '.nntm-term-list--phap-toa' ).forEach( function ( root ) {
		var track = root.querySelector( '[data-term-track]' );
		var prev = root.querySelector( '[data-term-prev]' );
		var next = root.querySelector( '[data-term-next]' );

		if ( ! track || ! prev || ! next ) {
			return;
		}

		var timer = null;
		var henQuayLai = null;
		var cards = Array.prototype.slice.call( track.querySelectorAll( '.nntm-term-card' ) );

		if ( ! cards.length ) {
			return;
		}

		var manDoc = window.matchMedia( '(max-width: 767px)' );

		function xepDoc() {
			return manDoc.matches;
		}

		function buoc() {
			var khe = parseFloat( window.getComputedStyle( track ).gap ) || 20;

			return cards[ 0 ].offsetWidth + khe;
		}

		function toiDa() {
			return Math.max( 0, track.scrollWidth - track.clientWidth );
		}

		function tranKhung() {
			return toiDa() > 1;
		}

		function dongBoNut() {
			if ( xepDoc() ) {
				prev.hidden = true;
				next.hidden = true;

				return;
			}

			var co = tranKhung();

			prev.hidden = ! co;
			next.hidden = ! co;

			if ( ! co ) {
				return;
			}

			prev.disabled = track.scrollLeft <= 1;
			next.disabled = track.scrollLeft >= toiDa() - 1;
		}

		function di( huong ) {
			if ( xepDoc() ) {
				return;
			}

			var dich = Math.max( 0, Math.min( toiDa(), track.scrollLeft + huong * buoc() ) );
			var muot = ! window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;

			track.scrollTo( { left: dich, behavior: muot ? 'smooth' : 'auto' } );
		}

		function dung() {
			if ( timer ) {
				window.clearInterval( timer );
				timer = null;
			}

			if ( henQuayLai ) {
				window.clearTimeout( henQuayLai );
				henQuayLai = null;
			}
		}

		function giayQuayLai() {
			return Math.max( 0, Math.min( 60, parseInt( root.dataset.loopDelay, 10 ) || 0 ) );
		}

		function toiCuoi() {
			var giay = giayQuayLai();

			if ( giay <= 0 || henQuayLai ) {
				return;
			}

			henQuayLai = window.setTimeout( function () {
				henQuayLai = null;

				var muot = ! window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;
				track.scrollTo( { left: 0, behavior: muot ? 'smooth' : 'auto' } );
				chay();
			}, giay * 1000 );
		}

		function chay() {
			dung();

			if ( xepDoc() ) {
				return;
			}

			if ( '1' !== root.dataset.autoplay || window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches ) {
				return;
			}

			if ( ! tranKhung() || track.scrollLeft >= toiDa() - 1 ) {
				return;
			}

			var giay = Math.max( 2, Math.min( 20, parseInt( root.dataset.interval, 10 ) || 5 ) );

			timer = window.setInterval( function () {
				if ( track.scrollLeft >= toiDa() - 1 ) {
					dung();
					toiCuoi();
					return;
				}

				di( 1 );
			}, giay * 1000 );
		}

		var dangCho = false;

		track.addEventListener( 'scroll', function () {
			if ( dangCho ) {
				return;
			}

			dangCho = true;
			window.requestAnimationFrame( function () {
				dongBoNut();

				if ( track.scrollLeft >= toiDa() - 1 ) {
					toiCuoi();
				}

				dangCho = false;
			} );
		}, { passive: true } );

		prev.addEventListener( 'click', function () {
			di( -1 );
			dung();
		} );

		next.addEventListener( 'click', function () {
			di( 1 );
			chay();
		} );

		root.addEventListener( 'mouseenter', dung );
		root.addEventListener( 'mouseleave', chay );
		root.addEventListener( 'focusin', dung );
		root.addEventListener( 'focusout', function ( event ) {
			if ( ! event.relatedTarget || ! root.contains( event.relatedTarget ) ) {
				chay();
			}
		} );

		document.addEventListener( 'visibilitychange', function () {
			if ( document.hidden ) {
				dung();
			} else {
				chay();
			}
		} );

		if ( window.ResizeObserver ) {
			new window.ResizeObserver( dongBoNut ).observe( track );
		} else {
			window.addEventListener( 'resize', dongBoNut );
		}

		function doiKhung() {
			if ( xepDoc() ) {
				track.scrollLeft = 0;
			}

			dongBoNut();
			chay();
		}

		if ( manDoc.addEventListener ) {
			manDoc.addEventListener( 'change', doiKhung );
		} else if ( manDoc.addListener ) {
			manDoc.addListener( doiKhung );
		}

		dongBoNut();
		chay();
	} );
} )();
