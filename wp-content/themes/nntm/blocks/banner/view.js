 
( function () {
	'use strict';

	function giamChuyenDong() {
		return !! ( window.matchMedia && window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches );
	}

	function khoiTaoBanner( root ) {
		var stage = root.querySelector( '.nntm-banner__stage' );
		var slides = root.querySelectorAll( '.nntm-banner__slide' );
		var dots = root.querySelectorAll( '.nntm-banner__dot' );

		if ( ! stage || slides.length < 2 ) {

			return;
		}

		var tong = slides.length;
		var viTri = 0;
		var boDem = null;
		var dangDung = false;


		var batAutoplay = '1' === root.getAttribute( 'data-nntm-autoplay' ) ||
			'true' === String( root.getAttribute( 'data-nntm-autoplay' ) );
		var chuKy = parseFloat( root.getAttribute( 'data-nntm-interval' ) );
		if ( ! isFinite( chuKy ) || chuKy <= 0 ) {
			chuKy = 6;
		}
		chuKy = Math.max( 2, Math.min( 30, chuKy ) );

		var choPhepChay = batAutoplay && ! giamChuyenDong();

		function toi( chiSo ) {
			var ke = ( chiSo + tong ) % tong;

			for ( var i = 0; i < slides.length; i++ ) {
				slides[ i ].classList.toggle( 'is-active', i === ke );
			}
			for ( var d = 0; d < dots.length; d++ ) {
				var dangXem = d === ke;
				dots[ d ].classList.toggle( 'is-active', dangXem );
				if ( dangXem ) {
					dots[ d ].setAttribute( 'aria-current', 'true' );
				} else {
					dots[ d ].removeAttribute( 'aria-current' );
				}
			}

			viTri = ke;
		}

		function tamSau() {
			toi( viTri + 1 );
		}

		function tamTruoc() {
			toi( viTri - 1 );
		}

		function batDem() {
			if ( ! choPhepChay || dangDung || null !== boDem || document.hidden ) {
				return;
			}
			boDem = window.setInterval( tamSau, chuKy * 1000 );
		}

		function dungDem() {
			if ( null !== boDem ) {
				window.clearInterval( boDem );
				boDem = null;
			}
		}

		function tamNgung() {
			dangDung = true;
			dungDem();
		}

		function chayTiep() {
			dangDung = false;
			batDem();
		}

		root.addEventListener( 'mouseenter', tamNgung );
		root.addEventListener( 'mouseleave', chayTiep );

		root.addEventListener( 'focusin', tamNgung );
		root.addEventListener( 'focusout', function ( su_kien ) {

			if ( ! su_kien.relatedTarget || ! root.contains( su_kien.relatedTarget ) ) {
				chayTiep();
			}
		} );

		document.addEventListener( 'visibilitychange', function () {
			if ( document.hidden ) {
				dungDem();
			} else {
				batDem();
			}
		} );

		for ( var t = 0; t < dots.length; t++ ) {
			( function ( chiSo ) {
				dots[ chiSo ].addEventListener( 'click', function () {
					toi( chiSo );

					if ( choPhepChay && ! dangDung ) {
						dungDem();
						batDem();
					}
				} );
			} )( t );
		}

		stage.addEventListener( 'keydown', function ( su_kien ) {
			if ( 'ArrowLeft' === su_kien.key ) {
				su_kien.preventDefault();
				tamTruoc();
			} else if ( 'ArrowRight' === su_kien.key ) {
				su_kien.preventDefault();
				tamSau();
			}
		} );

		toi( 0 );
		batDem();
	}

	function khoiTaoTatCa() {
		var ds = document.querySelectorAll( '.nntm-banner' );
		for ( var i = 0; i < ds.length; i++ ) {
			khoiTaoBanner( ds[ i ] );
		}
	}

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', khoiTaoTatCa );
	} else {
		khoiTaoTatCa();
	}
} )();
