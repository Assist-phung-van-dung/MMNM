
( function () {
	'use strict';

	var root = document.documentElement;
	var loader = document.querySelector( '.nntm-tai' );

	if ( ! loader ) {

		root.classList.remove( 'is-loading', 'is-revealing' );
		return;
	}

	var THOI_GIAN_TAN = 850;

	/*
	 * Thoi luong TOI THIEU, do admin dat o Giao dien -> Trich dan man hinh cho,
	 * duoc doan script trong <head> gan vao data-preload-min.
	 *
	 * Day la SAN chu khong phai HAN: man hinh cho tat o moc muon hon giua hai
	 * moc — du so giay nay, va trang tai xong. Tai nhanh thi van cho du giay de
	 * khach kip doc cau trich dan; tai cham thi cho toi luc xong.
	 *
	 * Truoc day moi hieu ung ghim cung 1800-1900ms, khong chinh duoc.
	 */
	function docToiThieu() {
		var tho = parseFloat( root.getAttribute( 'data-preload-min' ) );

		if ( ! isFinite( tho ) || tho < 0 ) {
			tho = 2;
		}

		return Math.min( 15, tho ) * 1000;
	}

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

	/*
	 * Nguoi dat may o che do giam chuyen dong thi khong nen giu ho lai de xem
	 * hieu ung — cat xuong con 300ms.
	 */
	var toiThieu = giamChuyenDong ? 300 : docToiThieu();

	/*
	 * Luoi an toan phai tinh TU toiThieu. Ghim cung 6000 thi admin dat 10 giay
	 * la man hinh cho bi cat ngang o giay thu 6.
	 */
	var LUOI_AN_TOAN = toiThieu + 6000;

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
