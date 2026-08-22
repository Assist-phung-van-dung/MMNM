 
( function () {
	'use strict';

	function khoiTaoTongChi( root ) {
		var track = root.querySelector( '[data-nntm-tongchi-track]' );
		var nutTruoc = root.querySelector( '[data-nntm-tongchi-truoc]' );
		var nutSau = root.querySelector( '[data-nntm-tongchi-sau]' );

		if ( ! track ) {
			return;
		}

		function buocCuon() {
			var the = track.querySelector( '.nntm-r1-tong-chi__the' );
			if ( ! the ) {
				return track.clientWidth;
			}
			var khoangCach = parseFloat( window.getComputedStyle( track ).columnGap );
			if ( ! isFinite( khoangCach ) ) {
				khoangCach = 0;
			}
			return the.getBoundingClientRect().width + khoangCach;
		}

		function capNhatNut() {

			var toiDa = track.scrollWidth - track.clientWidth;
			if ( nutTruoc ) {
				nutTruoc.disabled = track.scrollLeft <= 2;
			}
			if ( nutSau ) {
				nutSau.disabled = track.scrollLeft >= toiDa - 2;
			}
		}

		function cuon( huong ) {
			track.scrollBy( { left: huong * buocCuon(), behavior: 'smooth' } );
		}

		if ( nutTruoc ) {
			nutTruoc.addEventListener( 'click', function () {
				cuon( -1 );
			} );
		}
		if ( nutSau ) {
			nutSau.addEventListener( 'click', function () {
				cuon( 1 );
			} );
		}

		track.addEventListener( 'scroll', capNhatNut, { passive: true } );
		window.addEventListener( 'resize', capNhatNut );

		capNhatNut();
	}

	function khoiTaoTatCa() {
		var ds = document.querySelectorAll( '[data-nntm-tongchi]' );
		for ( var i = 0; i < ds.length; i++ ) {
			khoiTaoTongChi( ds[ i ] );
		}
	}

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', khoiTaoTatCa );
	} else {
		khoiTaoTatCa();
	}
} )();
