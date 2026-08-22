
( function () {
	'use strict';

	var root = document.documentElement;
	var loader = document.querySelector( '.nntm-tai' );

	if ( ! loader ) {

		root.classList.remove( 'is-loading', 'is-revealing' );
		return;
	}

	var THOI_LUONG = {
		halo: 1800,
		mandala: 1900,
		moon: 1900,
		sun: 1900
	};

	var THOI_GIAN_TAN = 850;

	var LUOI_AN_TOAN = 6000;

	var giamChuyenDong = window.matchMedia
		&& window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;

	var boDem = [];
	var daMo = false;

	function hen( cho, fn ) {
		boDem.push( window.setTimeout( fn, cho ) );
	}

	function huyHen() {
		boDem.forEach( window.clearTimeout );
		boDem = [];
	}

	function moTrang() {
		if ( daMo ) {
			return;
		}

		daMo = true;
		huyHen();

		root.classList.add( 'is-revealing' );

		hen( giamChuyenDong ? 120 : THOI_GIAN_TAN, function () {
			root.classList.remove( 'is-loading' );
			root.classList.remove( 'is-revealing' );
		} );
	}

	var hieuUng = root.getAttribute( 'data-effect' );
	var toiThieu = giamChuyenDong ? 300 : ( THOI_LUONG[ hieuUng ] || 1800 );

	function choDuThoiLuong() {
		var conLai = toiThieu - window.performance.now();

		hen( conLai > 0 ? conLai : 0, moTrang );
	}

	if ( 'complete' === document.readyState ) {
		choDuThoiLuong();
	} else {
		window.addEventListener( 'load', choDuThoiLuong, { once: true } );
	}

	hen( LUOI_AN_TOAN, moTrang );

	document.addEventListener( 'keydown', function ( event ) {
		if ( 'Escape' === event.key || 'Esc' === event.key ) {
			huyHen();
			daMo = true;
			root.classList.remove( 'is-loading' );
			root.classList.remove( 'is-revealing' );
		}
	} );
} )();
