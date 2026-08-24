( function () {
	'use strict';

	var LOP_JS = 'nntm-co-js';
	var LOP = 'nntm-reveal';
	var LOP_HIEN = 'is-hien';
	var LOP_DANG_TAI = 'is-loading';
	var BUOC_TRE = 80;
	var TRE_TOI_DA = 320;
	var CHO_TOI_DA = 9000;

	var root = document.documentElement;

	if ( ! window.IntersectionObserver ) {
		root.classList.remove( LOP_JS );
		return;
	}

	function hien( el, tre ) {
		if ( el.classList.contains( LOP_HIEN ) ) {
			return;
		}

		if ( tre > 0 ) {
			el.style.transitionDelay = tre + 'ms';
			el.addEventListener( 'transitionend', function () {
				el.style.transitionDelay = '';
			}, { once: true } );
		}

		el.classList.add( LOP_HIEN );
	}

	var theoDoi = new window.IntersectionObserver(
		function ( entries ) {
			var thuTu = 0;

			for ( var i = 0; i < entries.length; i++ ) {
				if ( ! entries[ i ].isIntersecting ) {
					continue;
				}

				theoDoi.unobserve( entries[ i ].target );
				hien( entries[ i ].target, Math.min( TRE_TOI_DA, thuTu * BUOC_TRE ) );
				thuTu++;
			}
		},
		{
			rootMargin: '0px 0px -10% 0px',
			threshold: 0.05
		}
	);

	function ganTheoDoi( pham ) {
		var ds = [];

		if ( pham && pham.classList && pham.classList.contains( LOP ) ) {
			ds.push( pham );
		}

		var trong = ( pham || document ).querySelectorAll( '.' + LOP );
		for ( var i = 0; i < trong.length; i++ ) {
			ds.push( trong[ i ] );
		}

		for ( var j = 0; j < ds.length; j++ ) {
			if ( ds[ j ].classList.contains( LOP_HIEN ) || khoiLong( ds[ j ] ) ) {
				continue;
			}

			theoDoi.observe( ds[ j ] );
		}
	}

	function khoiLong( el ) {
		if ( ! el.parentElement || ! el.parentElement.closest ) {
			return false;
		}

		return null !== el.parentElement.closest( '.' + LOP );
	}

	function batDau() {
		ganTheoDoi( document );
	}

	function choManTaiXong() {
		if ( ! root.classList.contains( LOP_DANG_TAI ) ) {
			batDau();
			return;
		}

		if ( ! window.MutationObserver ) {
			window.setTimeout( batDau, 700 );
			return;
		}

		var doi = new window.MutationObserver( function () {
			if ( ! root.classList.contains( LOP_DANG_TAI ) ) {
				doi.disconnect();
				batDau();
			}
		} );

		doi.observe( root, { attributes: true, attributeFilter: [ 'class' ] } );

		window.setTimeout( function () {
			doi.disconnect();
			batDau();
		}, CHO_TOI_DA );
	}

	document.addEventListener( 'nntm-card-list-refresh', function ( event ) {
		var moi = event.detail && event.detail.root;

		if ( ! moi || ! moi.classList ) {
			return;
		}

		if ( moi.classList.contains( LOP ) ) {
			window.requestAnimationFrame( function () {
				hien( moi, 0 );
			} );
			return;
		}

		ganTheoDoi( moi );
	} );

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', choManTaiXong );
	} else {
		choManTaiXong();
	}
} )();
