/**
 * Trang "Tài khoản của tôi" — chỉ một việc: tô đậm mục đang xem trong điều
 * hướng khi cuộn trang (progressive enhancement). Không có JS thì các liên
 * kết #id vẫn nhảy neo bình thường, trang vẫn dùng được đầy đủ.
 */
( function () {
	'use strict';

	if ( ! ( 'IntersectionObserver' in window ) ) {
		return;
	}

	var nav = document.querySelector( '[data-nntm-tk-dieu-huong]' );
	if ( ! nav ) {
		return;
	}

	var lienKet = {};
	nav.querySelectorAll( '[data-nntm-tk-muc]' ).forEach( function ( a ) {
		lienKet[ a.getAttribute( 'data-nntm-tk-muc' ) ] = a;
	} );

	var muc = Object.keys( lienKet )
		.map( function ( id ) {
			return document.getElementById( id );
		} )
		.filter( Boolean );

	if ( ! muc.length ) {
		return;
	}

	function danhDauDangXem( id ) {
		Object.keys( lienKet ).forEach( function ( key ) {
			lienKet[ key ].classList.toggle( 'is-active', key === id );
		} );
	}

	var quanSat = new IntersectionObserver(
		function ( danhSach ) {
			var dangHien = danhSach.filter( function ( entry ) {
				return entry.isIntersecting;
			} );

			if ( ! dangHien.length ) {
				return;
			}

			// Nhiều khối cùng lọt khung hình cùng lúc: chọn khối ở cao nhất trên màn hình.
			dangHien.sort( function ( a, b ) {
				return a.boundingClientRect.top - b.boundingClientRect.top;
			} );

			danhDauDangXem( dangHien[ 0 ].target.id );
		},
		{
			rootMargin: '-45% 0px -50% 0px',
			threshold: 0,
		}
	);

	muc.forEach( function ( phanTu ) {
		quanSat.observe( phanTu );
	} );
} )();
