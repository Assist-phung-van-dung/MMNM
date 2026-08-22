 
( function () {
	'use strict';

	var THAM_SO_AJAX = 'nntm_cardlist_ajax';
	var LOP_KHOI     = 'nntm-card-list';
	var dangTai      = false;

	if ( ! window.fetch || ! window.history || ! window.history.pushState ) {
		return; 
	}

	document.addEventListener( 'click', function ( event ) {

		if ( event.defaultPrevented || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey || 0 !== event.button ) {
			return;
		}

		var link = event.target.closest ? event.target.closest( '.nntm-card-list__paging a[href]' ) : null;
		if ( ! link ) {
			return;
		}

		var khoi = link.closest( '.' + LOP_KHOI );
		if ( ! khoi ) {
			return;
		}

		var dich = new window.URL( link.href, window.location.href );
		if ( dich.origin !== window.location.origin ) {
			return;
		}

		event.preventDefault();
		doiTrang( khoi, dich.href, true );
	} );

	window.addEventListener( 'popstate', function () {
		var khoi = document.querySelector( '.' + LOP_KHOI + ' .nntm-card-list__paging' );
		if ( ! khoi ) {
			return;
		}

		doiTrang( khoi.closest( '.' + LOP_KHOI ), window.location.href, false );
	} );

	function doiTrang( khoi, url, ghiSuKy ) {
		if ( dangTai || ! khoi ) {
			return;
		}

		dangTai = true;
		khoi.classList.add( 'nntm-card-list--dang-tai' );
		khoi.setAttribute( 'aria-busy', 'true' );

		window.fetch( themThamSo( url ), {
			credentials: 'same-origin',
			headers: { Accept: 'application/json' }
		} )
			.then( function ( response ) {
				if ( ! response.ok ) {
					throw new Error( 'HTTP ' + response.status );
				}
				return response.json();
			} )
			.then( function ( payload ) {
				if ( ! payload || ! payload.success || ! payload.data || ! payload.data.html ) {
					throw new Error( 'payload' );
				}

				var moi = thayKhoi( khoi, payload.data.html );
				if ( ! moi ) {
					throw new Error( 'markup' );
				}

				if ( ghiSuKy ) {
					window.history.pushState( { nntmCardList: true }, '', url );
				}

				dangTai = false;
				dungDauDanhSach( moi );
			} )
			.catch( function () {

				window.location.assign( url );
			} );
	}

	function themThamSo( url ) {
		var dich = new window.URL( url, window.location.href );
		dich.searchParams.set( THAM_SO_AJAX, '1' );
		return dich.href;
	}

	function thayKhoi( khoiCu, html ) {
		var khuon = document.createElement( 'template' );
		khuon.innerHTML = html.trim();

		var khoiMoi = khuon.content.querySelector( '.' + LOP_KHOI );
		if ( ! khoiMoi ) {
			return null;
		}

		khoiCu.replaceWith( khoiMoi );

		document.dispatchEvent(
			new window.CustomEvent( 'nntm-card-list-refresh', { detail: { root: khoiMoi } } )
		);

		return khoiMoi;
	}

	function dungDauDanhSach( khoi ) {
		var giamHoatHinh = !! ( window.matchMedia && window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches );

		khoi.scrollIntoView( {
			behavior: giamHoatHinh ? 'auto' : 'smooth',
			block: 'start'
		} );

		var luoi = khoi.querySelector( '.nntm-grid' );
		if ( luoi ) {
			luoi.setAttribute( 'tabindex', '-1' );
			luoi.focus( { preventScroll: true } );
		}
	}
} )();
