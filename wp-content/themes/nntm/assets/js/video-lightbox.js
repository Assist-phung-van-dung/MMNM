 
( function () {
	'use strict';

	var ID_POPUP  = 'nntm-yt-lightbox';
	var LOP_MO    = 'nntm-yt-lightbox-mo';
	var theTruocDo = null;

	function layPopup() {
		return document.getElementById( ID_POPUP );
	}

	function urlNhung( videoId ) {
		return 'https://www.youtube.com/embed/' + encodeURIComponent( videoId ) +
			'?autoplay=1&playsinline=1&rel=0&modestbranding=1';
	}

	function mo( videoId, nhan, theGoc ) {
		var popup = layPopup();
		var khung = popup ? popup.querySelector( '[data-nntm-yt-lightbox-frame]' ) : null;

		if ( ! popup || ! khung || ! videoId ) {
			return;
		}

		theTruocDo = theGoc || document.activeElement;

		var iframe = document.createElement( 'iframe' );
		iframe.src = urlNhung( videoId );
		iframe.setAttribute( 'title', nhan || '' );
		iframe.setAttribute( 'frameborder', '0' );
		iframe.setAttribute( 'allow', 'autoplay; encrypted-media; fullscreen; picture-in-picture' );
		iframe.setAttribute( 'allowfullscreen', 'allowfullscreen' );
		iframe.setAttribute( 'referrerpolicy', 'strict-origin-when-cross-origin' );

		khung.textContent = '';
		khung.appendChild( iframe );

		popup.hidden = false;
		document.documentElement.classList.add( LOP_MO );

		var nutDong = popup.querySelector( '[data-nntm-yt-lightbox-close]' );
		if ( nutDong && 'function' === typeof nutDong.focus ) {
			nutDong.focus();
		}
	}

	function dong() {
		var popup = layPopup();
		if ( ! popup || popup.hidden ) {
			return;
		}

		var khung = popup.querySelector( '[data-nntm-yt-lightbox-frame]' );
		if ( khung ) {
			khung.textContent = ''; 
		}

		popup.hidden = true;
		document.documentElement.classList.remove( LOP_MO );

		if ( theTruocDo && 'function' === typeof theTruocDo.focus ) {
			theTruocDo.focus();
		}
		theTruocDo = null;
	}

	function moTuThe( the ) {
		mo( the.getAttribute( 'data-video-id' ), the.getAttribute( 'aria-label' ) || '', the );
	}

	document.addEventListener( 'click', function ( event ) {
		if ( ! event.target || ! event.target.closest ) {
			return;
		}

		if ( event.target.closest( '[data-nntm-yt-lightbox-close]' ) ) {
			event.preventDefault();
			dong();
			return;
		}

		/*
		 * Nút đóng khung video nổi nằm NGAY TRONG khung video, nên nếu không
		 * chừa ra thì cú bấm đó cũng bị hiểu là "mở trình phát video".
		 */
		if ( event.target.closest( '[data-nntm-ee-dong]' ) ) {
			return;
		}

		var the = event.target.closest( '.nntm-card-list__yt-item' ) ||
			event.target.closest( '.nntm-engineering-earth__video-slot' );

		if ( ! the || ! the.getAttribute( 'data-video-id' ) ) {
			return; 
		}

		event.preventDefault();
		moTuThe( the );
	} );

	document.addEventListener( 'keydown', function ( event ) {
		var popup = layPopup();

		if ( popup && ! popup.hidden ) {
			if ( 'Escape' === event.key ) {
				dong();
			} else if ( 'Tab' === event.key ) {
				bayTieuDiem( event, popup );
			}
			return;
		}

		if ( 'Enter' !== event.key && ' ' !== event.key && 'Spacebar' !== event.key ) {
			return;
		}

		var the = event.target && event.target.closest ? event.target.closest( '.nntm-card-list__yt-item' ) : null;
		if ( ! the || ! the.getAttribute( 'data-video-id' ) ) {
			return;
		}

		event.preventDefault(); 
		moTuThe( the );
	} );

	function bayTieuDiem( event, popup ) {
		var nutDong = popup.querySelector( '[data-nntm-yt-lightbox-close]' );
		if ( ! nutDong ) {
			return;
		}

		event.preventDefault();
		nutDong.focus();
	}
} )();
