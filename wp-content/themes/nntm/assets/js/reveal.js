( function () {
	'use strict';

	var LOP_JS = 'nntm-co-js';
	var LOP = 'nntm-reveal';
	var LOP_HIEN = 'is-hien';
	var LOP_DANG_TAI = 'is-loading';
	var BUOC_TRE = 80;
	var TRE_TOI_DA = 320;
	var CHO_TOI_DA = 9000;
	var LOP_CHI_TIET = 'nntm-reveal--chi-tiet';
	var LOP_MUC = 'nntm-reveal-item';

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

			chuanBiChiTiet( ds[ j ] );
			theoDoi.observe( ds[ j ] );
		}
	}

	function khoiLong( el ) {
		if ( ! el.parentElement || ! el.parentElement.closest ) {
			return false;
		}

		return null !== el.parentElement.closest( '.' + LOP );
	}

	function chuanBiChiTiet( el ) {
		var coChiTiet = false;

		function lay( selector ) {
			var ketQua = [];
			var ds = el.querySelectorAll( selector );

			for ( var i = 0; i < ds.length; i++ ) {
				if ( ds[ i ].closest( '.' + LOP ) === el ) {
					ketQua.push( ds[ i ] );
				}
			}

			return ketQua;
		}

		function danhDau( ds, hieuUng, batDauTu, buoc, treThem ) {
			batDauTu = Number.isFinite( batDauTu ) ? batDauTu : 0;
			buoc = Number.isFinite( buoc ) ? buoc : 1;
			treThem = Number.isFinite( treThem ) ? treThem : 0;

			for ( var i = 0; i < ds.length; i++ ) {
				var ten = Array.isArray( hieuUng ) ? hieuUng[ i % hieuUng.length ] : hieuUng;
				var thuTu = batDauTu + ( i * buoc );

				ds[ i ].classList.add( LOP_MUC, LOP_MUC + '--' + ten );
				ds[ i ].style.setProperty( '--nntm-reveal-item-delay', ( treThem + ( thuTu * 95 ) ) + 'ms' );
				coChiTiet = true;
			}
		}

		if ( el.classList.contains( 'nntm-rank-card' ) ) {
			danhDau( lay( '.nntm-rank-card__heading' ), 'tu-tren', 0, 1, 0 );
			danhDau( lay( '.nntm-rank-card__card-media' ), [ 'tu-trai', 'tu-phai' ], 1, 0, 80 );
			danhDau( lay( '.nntm-rank-card__card-title, .nntm-rank-card__cta' ), 'nhich-len', 2, 1, 100 );
		} else if ( el.classList.contains( 'nntm-article-mosaic' ) ) {
			danhDau( lay( '.nntm-article-mosaic__heading' ), 'tu-tren', 0, 1, 0 );
			danhDau( lay( '.nntm-article-mosaic__media-link' ), [ 'tu-trai', 'tu-tren', 'tu-phai', 'tu-duoi' ], 1, 1, 40 );
			danhDau( lay( '.nntm-article-mosaic__viewall-wrap' ), 'nhich-len', 3, 1, 160 );
		} else if ( el.classList.contains( 'nntm-article-feature' ) ) {
			danhDau( lay( '.nntm-article-feature__quote, .nntm-article-feature__text' ), 'nhich-len', 0, 1, 0 );
			danhDau(
				lay( '.nntm-article-feature__media-link' ),
				el.classList.contains( 'nntm-article-feature--media-left' ) ? 'tu-trai' : 'tu-phai',
				2,
				1,
				320
			);
		} else if ( el.classList.contains( 'nntm-card-list' ) ) {
			danhDau( lay( '.nntm-card-list__heading-above, .nntm-card-list__header-row, .nntm-card-list__subheading' ), 'tu-tren', 0, 1, 0 );

			if ( lay( '.nntm-card-list__carousel' ).length ) {
				danhDau( lay( '.nntm-card-list__carousel' ), 'tu-phai', 1, 1, 80 );
			} else if ( lay( '.nntm-card-list__marquee, .nntm-card-list__yt-marquee' ).length ) {
				danhDau( lay( '.nntm-card-list__marquee, .nntm-card-list__yt-marquee' ), 'phong-to', 1, 1, 80 );
			} else {
				danhDau( lay( '.nntm-grid > .nntm-card' ), [ 'nghieng-trai', 'nhich-len', 'nghieng-phai' ], 1, 1, 40 );
			}

			danhDau( lay( '.nntm-card-list__paging, .nntm-card-list__view-all-wrap, .nntm-card-list__icons, .nntm-card-list__caption-below' ), 'nhich-len', 3, 1, 120 );
		} else if ( el.classList.contains( 'nntm-engineering-earth' ) ) {
			danhDau(
				lay( '.nntm-engineering-earth__heading-group, .nntm-engineering-earth__video-stage, .nntm-engineering-earth__band-text, .nntm-engineering-earth__caption, .nntm-engineering-earth__figma-pip' ),
				[ 'tu-tren', 'phong-to', 'tu-phai', 'nhich-len', 'tu-trai' ],
				0,
				1,
				0
			);
		} else if ( el.classList.contains( 'nntm-feature-carousel' ) ) {
			danhDau( lay( '.nntm-feature-carousel__header' ), 'tu-tren', 0, 1, 0 );
			danhDau( lay( '.nntm-feature-carousel__slider' ), 'phong-to', 1, 1, 100 );
		} else if ( el.classList.contains( 'nntm-feature-gallery-carousel' ) ) {
			danhDau( lay( '.nntm-feature-gallery-carousel__header' ), 'tu-tren', 0, 1, 0 );
			danhDau( lay( '.nntm-feature-gallery-carousel__slider' ), 'phong-to', 1, 1, 100 );
		} else if ( el.classList.contains( 'nntm-banner' ) ) {
			danhDau( lay( '.nntm-banner__stage' ), 'phong-to', 0, 1, 0 );
			danhDau( lay( '.nntm-banner__slide.is-active .nntm-banner__emblem, .nntm-banner__slide.is-active .nntm-banner__text-inner, .nntm-banner__dots' ), 'nhich-len', 1, 1, 100 );
		} else if ( el.classList.contains( 'nntm-article-rows' ) ) {
			danhDau( lay( '.nntm-article-rows__heading' ), 'tu-tren', 0, 1, 0 );
			danhDau( lay( '.nntm-article-rows__row' ), [ 'tu-trai', 'tu-phai' ], 1, 1, 20 );
		} else if ( el.classList.contains( 'nntm-feature' ) ) {
			danhDau( lay( '.nntm-feature__text-inner, .nntm-feature__text' ), 'tu-trai', 0, 1, 0 );
			danhDau( lay( '.nntm-feature__media' ), 'tu-phai', 1, 1, 80 );
		} else if ( el.classList.contains( 'nntm-term-list' ) ) {
			danhDau( lay( '.nntm-term-list__heading' ), 'tu-tren', 0, 1, 0 );
			danhDau( lay( '.nntm-term-card' ), [ 'nghieng-trai', 'nhich-len', 'nghieng-phai' ], 1, 1, 40 );
		} else if ( el.classList.contains( 'nntm-tru-xu-list' ) ) {
			danhDau( lay( '.nntm-tru-xu-list__heading' ), 'tu-tren', 0, 1, 0 );
			danhDau( lay( '.nntm-tru-xu-card' ), [ 'tu-trai', 'nhich-len', 'tu-phai' ], 1, 1, 40 );
		} else if ( el.classList.contains( 'nntm-cong-tu' ) ) {
			danhDau( lay( '.nntm-cong-tu__thong-ke-heading, .nntm-cong-tu__bxh-heading' ), 'tu-tren', 0, 1, 0 );
			danhDau( lay( '.nntm-cong-tu__o, .nntm-cong-tu__bxh-row' ), 'nhich-len', 1, 1, 20 );
		} else {
			var slider = lay( '[aria-roledescription="carousel"], [class$="__slider"]' );
			if ( slider.length ) {
				danhDau( slider, 'phong-to', 0, 1, 80 );
			}
		}

		if ( coChiTiet ) {
			el.classList.add( LOP_CHI_TIET );
		}
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
			chuanBiChiTiet( moi );
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
