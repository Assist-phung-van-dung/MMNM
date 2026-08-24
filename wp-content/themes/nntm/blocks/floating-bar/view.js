( function () {
	'use strict';

	var CHON_BANNER = '.nntm-hero-slider, .nntm-banner, .nntm-rank-card, .nntm-feature-carousel';
	var CHON_CUOI = '.nntm-footer';
	var NGUONG_CUOI = 80;

	function timTruoc( thanh, chon ) {
		var ds = document.querySelectorAll( chon );

		for ( var i = 0; i < ds.length; i++ ) {
			if ( ds[ i ] !== thanh && ! thanh.contains( ds[ i ] ) ) {
				return ds[ i ];
			}
		}

		return null;
	}

	function viTri() {
		return window.scrollY || window.pageYOffset || document.documentElement.scrollTop || 0;
	}

	function ganThanh( thanh ) {
		if ( 'true' === thanh.getAttribute( 'data-nntm-fb-san-sang' ) ) {
			return;
		}

		thanh.setAttribute( 'data-nntm-fb-san-sang', 'true' );

		var quaBanner = ( 'cuon-ngay' === thanh.getAttribute( 'data-mo-khi' ) );
		var toiCuoi = false;
		var dangHien = null;

		function ve() {
			var nenHien = quaBanner && ! toiCuoi;

			if ( nenHien === dangHien ) {
				return;
			}

			dangHien = nenHien;
			thanh.classList.toggle( 'is-hien', nenHien );
			thanh.setAttribute( 'aria-hidden', nenHien ? 'false' : 'true' );
		}

		ve();

		var banner = quaBanner ? null : timTruoc( thanh, CHON_BANNER );
		var cuoi = timTruoc( thanh, CHON_CUOI );

		if ( window.IntersectionObserver && banner ) {
			new window.IntersectionObserver(
				function ( entries ) {
					for ( var i = 0; i < entries.length; i++ ) {
						quaBanner = ! entries[ i ].isIntersecting && entries[ i ].boundingClientRect.top < 0;
					}
					ve();
				},
				{ threshold: 0 }
			).observe( banner );
		}

		if ( window.IntersectionObserver && cuoi ) {
			new window.IntersectionObserver(
				function ( entries ) {
					for ( var i = 0; i < entries.length; i++ ) {
						toiCuoi = entries[ i ].isIntersecting;
					}
					ve();
				},
				{ threshold: 0 }
			).observe( cuoi );
		}

		if ( window.IntersectionObserver && banner && cuoi ) {
			return;
		}

		var dangCho = false;

		function theoCuon() {
			if ( dangCho ) {
				return;
			}

			dangCho = true;
			window.requestAnimationFrame( function () {
				var y = viTri();

				if ( ! window.IntersectionObserver || ! banner ) {
					quaBanner = quaBanner || y > ( banner ? banner.offsetHeight * 0.6 : 240 );
				}

				if ( ! window.IntersectionObserver || ! cuoi ) {
					var cao = document.documentElement.scrollHeight;
					toiCuoi = ( y + window.innerHeight ) >= ( cao - NGUONG_CUOI );
				}

				ve();
				dangCho = false;
			} );
		}

		window.addEventListener( 'scroll', theoCuon, { passive: true } );
		theoCuon();
	}

	function ganTatCa() {
		var ds = document.querySelectorAll( '.nntm-floating-bar' );

		for ( var i = 0; i < ds.length; i++ ) {
			ganThanh( ds[ i ] );
		}
	}

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', ganTatCa );
	} else {
		ganTatCa();
	}
} )();
