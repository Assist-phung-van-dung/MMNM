/**
 * Trang kết quả tìm bằng hình ảnh: hiện ảnh xem trước, "Tìm bằng ảnh khác".
 *
 * Ảnh xem trước lấy từ sessionStorage (search-bar.js lưu lúc tải ảnh lên) —
 * máy chủ không giữ ảnh. Mở link ở máy khác / tab khác thì không có ảnh, trang
 * vẫn đủ kết quả.
 */
( function () {
	'use strict';

	var goc = document.querySelector( '.nntm-search-anh' );

	if ( ! goc || typeof nntmSearch === 'undefined' ) {
		return;
	}

	var i18n = nntmSearch.i18n || {};
	var anhLuu = window.nntmAnhXemTruoc;

	/* ---------- Ảnh xem trước ---------- */

	var khung = goc.querySelector( '.nntm-search-anh__xem-truoc' );
	var duLieu = anhLuu ? anhLuu.lay( goc.getAttribute( 'data-nntm-anh' ) || '' ) : '';

	if ( khung && duLieu ) {
		khung.querySelector( 'img' ).src = duLieu;
		khung.hidden = false;
	}

	/* ---------- Tìm bằng ảnh khác ---------- */

	var nut = goc.querySelector( '[data-nntm-anh-chon]' );
	var tep = goc.querySelector( '[data-nntm-anh-tep]' );
	var trangThai = goc.querySelector( '.nntm-search-anh__trang-thai' );

	if ( ! nut || ! tep ) {
		return;
	}

	if ( ! nntmSearch.imageEnabled ) {
		nut.hidden = true;
		return;
	}

	function bao( chu ) {
		if ( trangThai ) {
			trangThai.textContent = chu || '';
		}
	}

	nut.addEventListener( 'click', function () {
		tep.click();
	} );

	tep.addEventListener( 'change', function () {
		var file = tep.files && tep.files[ 0 ];

		if ( ! file ) {
			return;
		}

		// Kiểm cho đỡ một lượt gửi; máy chủ kiểm lại bằng finfo.
		if ( file.size > 5 * 1024 * 1024 ) {
			bao( i18n.imageTooBig );
			return;
		}

		nut.disabled = true;
		bao( i18n.readingImage );

		var goi = new FormData();
		goi.append( 'anh', file );

		fetch( nntmSearch.root + 'image', {
			method: 'POST',
			body: goi,
			credentials: 'same-origin',
			headers: { 'X-WP-Nonce': nntmSearch.nonce }
		} )
			.then( function ( res ) {
				var loi = { 429: i18n.tooFast, 413: i18n.imageTooBig, 415: i18n.imageBadType }[ res.status ];

				if ( loi || ! res.ok ) {
					throw new Error( loi || i18n.failed );
				}
				return res.json();
			} )
			.then( function ( data ) {
				if ( ! data || ! data.trang ) {
					throw new Error( i18n.failed );
				}

				return ( anhLuu ? anhLuu.luu( file, data.token ) : Promise.resolve() ).then( function () {
					window.location.href = data.trang;
				} );
			} )
			.catch( function ( err ) {
				nut.disabled = false;
				tep.value = '';
				bao( err && err.message ? err.message : i18n.failed );
			} );
	} );
}() );
