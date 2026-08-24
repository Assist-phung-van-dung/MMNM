( function () {
	'use strict';

	var NGUONG = 320;

	var nut = document.querySelector( '.nntm-len-dau' );
	if ( ! nut ) {
		return;
	}

	var hienTai = false;
	var dangCho = false;

	function viTri() {
		return window.scrollY || window.pageYOffset || document.documentElement.scrollTop || 0;
	}

	function dongBo() {
		var nenHien = viTri() > NGUONG;

		if ( nenHien === hienTai ) {
			return;
		}

		hienTai = nenHien;
		nut.classList.toggle( 'is-hien', nenHien );
		nut.setAttribute( 'aria-hidden', nenHien ? 'false' : 'true' );
		nut.tabIndex = nenHien ? 0 : -1;
	}

	function khiCuon() {
		if ( dangCho ) {
			return;
		}

		dangCho = true;
		window.requestAnimationFrame( function () {
			dongBo();
			dangCho = false;
		} );
	}

	nut.addEventListener( 'click', function () {
		var giamHoatHinh = !! ( window.matchMedia && window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches );

		window.scrollTo( {
			top: 0,
			behavior: giamHoatHinh ? 'auto' : 'smooth'
		} );
	} );

	window.addEventListener( 'scroll', khiCuon, { passive: true } );
	dongBo();
} )();
