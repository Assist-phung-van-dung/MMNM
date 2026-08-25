( function () {
	'use strict';

	var GIU_MS = 2200;

	function saoBangClipboard( chuoi ) {
		if ( ! window.navigator || ! window.navigator.clipboard || ! window.navigator.clipboard.writeText ) {
			return null;
		}

		try {
			return window.navigator.clipboard.writeText( chuoi );
		} catch ( e ) {
			return null;
		}
	}

	function saoBangOTam( chuoi ) {
		var o = document.createElement( 'textarea' );

		o.value = chuoi;
		o.setAttribute( 'readonly', 'readonly' );
		o.style.position = 'fixed';
		o.style.top = '0';
		o.style.left = '-9999px';
		o.style.opacity = '0';

		document.body.appendChild( o );

		var chon = document.getSelection();
		var pham = chon && chon.rangeCount > 0 ? chon.getRangeAt( 0 ) : null;

		o.select();
		o.setSelectionRange( 0, o.value.length );

		var xong = false;

		try {
			xong = document.execCommand( 'copy' );
		} catch ( e ) {
			xong = false;
		}

		document.body.removeChild( o );

		if ( pham && chon ) {
			chon.removeAllRanges();
			chon.addRange( pham );
		}

		return xong;
	}

	function baoKetQua( nut, xong ) {
		var nhan = nut.querySelector( '.nntm-sao-link__nhan' ) || nut;

		if ( ! nut.getAttribute( 'data-nntm-sao-link-goc' ) ) {
			nut.setAttribute( 'data-nntm-sao-link-goc', nhan.textContent );
		}

		var goc = nut.getAttribute( 'data-nntm-sao-link-goc' );
		var chu = xong
			? ( nut.getAttribute( 'data-nntm-sao-link-xong' ) || 'Đã copy link' )
			: ( nut.getAttribute( 'data-nntm-sao-link-loi' ) || 'Không copy được' );

		nhan.textContent = chu;
		nut.classList.add( xong ? 'is-xong' : 'is-loi' );
		nut.setAttribute( 'aria-live', 'polite' );

		if ( nut._nntmHen ) {
			window.clearTimeout( nut._nntmHen );
		}

		nut._nntmHen = window.setTimeout( function () {
			nhan.textContent = goc;
			nut.classList.remove( 'is-xong' );
			nut.classList.remove( 'is-loi' );
		}, GIU_MS );
	}

	document.addEventListener( 'click', function ( event ) {
		var nut = event.target.closest ? event.target.closest( '[data-nntm-sao-link]' ) : null;

		if ( ! nut ) {
			return;
		}

		event.preventDefault();

		var chuoi = nut.getAttribute( 'data-nntm-sao-link' ) || window.location.href;
		var hua = saoBangClipboard( chuoi );

		if ( hua && hua.then ) {
			hua.then( function () {
				baoKetQua( nut, true );
			} ).catch( function () {
				baoKetQua( nut, saoBangOTam( chuoi ) );
			} );
			return;
		}

		baoKetQua( nut, saoBangOTam( chuoi ) );
	} );
} )();
