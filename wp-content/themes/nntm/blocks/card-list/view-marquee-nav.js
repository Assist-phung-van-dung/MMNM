 
( function () {
	'use strict';

	var LOAI_BANG = [
		{
			bang: '.nntm-card-list__marquee',
			track: '.nntm-card-list__marquee-track',
			item: '.nntm-card-list__marquee-item',
			lopTay: 'nntm-card-list__marquee-track--tay'
		},
		{
			bang: '.nntm-card-list__yt-marquee',
			track: '.nntm-card-list__yt-track',
			item: '.nntm-card-list__yt-cell',
			lopTay: 'nntm-card-list__yt-track--tay'
		}
	];

	function docTranslateX( el ) {
		var chuoi = window.getComputedStyle( el ).transform;

		if ( ! chuoi || 'none' === chuoi ) {
			return 0;
		}

		var so = chuoi.match( /matrix(3d)?\(([^)]+)\)/ );
		if ( ! so ) {
			return 0;
		}

		var phan = so[ 2 ].split( ',' );

		var x = so[ 1 ] ? parseFloat( phan[ 12 ] ) : parseFloat( phan[ 4 ] );

		return isNaN( x ) ? 0 : x;
	}

	function ganNutChoBang( bang, caiDat ) {
		var track = bang.querySelector( caiDat.track );
		var nutLui = bang.querySelector( '.nntm-card-list__marquee-nav--prev' );
		var nutTien = bang.querySelector( '.nntm-card-list__marquee-nav--next' );

		if ( ! track || ! nutLui || ! nutTien || 'true' === bang.getAttribute( 'data-nntm-marquee-nav' ) ) {
			return;
		}

		bang.setAttribute( 'data-nntm-marquee-nav', 'true' );

		var viTri = null;

		function buocThe() {
			var the = track.querySelector( caiDat.item );
			if ( ! the ) {
				return 0;
			}

			var kieu = window.getComputedStyle( track );
			var khe  = parseFloat( kieu.columnGap || kieu.gap || '0' ) || 0;

			return the.offsetWidth + khe;
		}

		function chuKy() {
			var soThe = track.querySelectorAll( caiDat.item ).length;
			return ( soThe / 2 ) * buocThe();
		}

		function datX( x, muot ) {
			if ( ! muot ) {
				track.style.transitionProperty = 'none';
			}

			track.style.transform = 'translateX(' + x + 'px)';

			if ( ! muot ) {
				 
				void track.offsetWidth;
				track.style.transitionProperty = '';
			}
		}

		function tiepQuan() {
			if ( null !== viTri ) {
				return;
			}

			viTri = docTranslateX( track );
			track.classList.add( caiDat.lopTay );

			track.style.animation = 'none';

			datX( viTri, false );
		}

		function di( huong ) {
			tiepQuan();

			var buoc = buocThe();
			var ck   = chuKy();

			if ( buoc <= 0 || ck <= 0 ) {
				return;
			}

			var dich = viTri - ( huong * buoc );

			if ( dich <= -ck ) {
				viTri += ck;
				datX( viTri, false );
				dich += ck;
			} else if ( dich > 0 ) {
				viTri -= ck;
				datX( viTri, false );
				dich -= ck;
			}

			viTri = dich;
			datX( viTri, true );
		}

		nutLui.addEventListener( 'click', function () {
			di( -1 );
		} );

		nutTien.addEventListener( 'click', function () {
			di( 1 );
		} );
	}

	function ganNutChoMoiBang( root ) {
		var pham = root || document;

		for ( var i = 0; i < LOAI_BANG.length; i++ ) {
			var caiDat = LOAI_BANG[ i ];
			var bangs  = pham.querySelectorAll( caiDat.bang );

			for ( var j = 0; j < bangs.length; j++ ) {
				ganNutChoBang( bangs[ j ], caiDat );
			}
		}
	}

	document.addEventListener( 'nntm-card-list-refresh', function ( event ) {
		ganNutChoMoiBang( ( event.detail && event.detail.root ) || document );
	} );

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', function () {
			ganNutChoMoiBang();
		} );
	} else {
		ganNutChoMoiBang();
	}
} )();
