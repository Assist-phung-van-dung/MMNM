( function () {
	'use strict';

	function bang( khoi ) {
		return khoi.querySelector( '.nntm-chia-se__bang' );
	}

	function nut( khoi ) {
		return khoi.querySelector( '[data-nntm-chia-se-nut]' );
	}

	function dong( khoi ) {
		var b = bang( khoi );
		var n = nut( khoi );

		if ( b ) {
			b.hidden = true;
		}

		if ( n ) {
			n.setAttribute( 'aria-expanded', 'false' );
		}

		khoi.classList.remove( 'is-mo' );
	}

	function dongTatCa( tru ) {
		var ds = document.querySelectorAll( '[data-nntm-chia-se]' );

		for ( var i = 0; i < ds.length; i++ ) {
			if ( ds[ i ] !== tru ) {
				dong( ds[ i ] );
			}
		}
	}

	function mo( khoi ) {
		var b = bang( khoi );
		var n = nut( khoi );

		dongTatCa( khoi );

		if ( b ) {
			b.hidden = false;
		}

		if ( n ) {
			n.setAttribute( 'aria-expanded', 'true' );
		}

		khoi.classList.add( 'is-mo' );
	}

	document.addEventListener( 'click', function ( su ) {
		var goc = su.target;

		if ( ! goc || ! goc.closest ) {
			return;
		}

		var camNut = goc.closest( '[data-nntm-chia-se-nut]' );

		if ( camNut ) {
			var khoi = camNut.closest( '[data-nntm-chia-se]' );

			if ( ! khoi ) {
				return;
			}

			su.preventDefault();

			if ( 'true' === camNut.getAttribute( 'aria-expanded' ) ) {
				dong( khoi );
			} else {
				mo( khoi );
			}

			return;
		}

		var lien = goc.closest( '[data-nntm-chia-se-mo]' );

		if ( lien ) {
			var dia = lien.getAttribute( 'href' ) || '';

			// mailto để trình duyệt tự lo, còn lại mở cửa sổ nhỏ.
			if ( 0 !== dia.toLowerCase().indexOf( 'mailto:' ) ) {
				su.preventDefault();

				var rong = 620;
				var cao  = 640;
				var trai = Math.max( 0, Math.round( ( window.screen.width - rong ) / 2 ) );
				var tren = Math.max( 0, Math.round( ( window.screen.height - cao ) / 2 ) );

				var cua = window.open(
					dia,
					'nntm-chia-se',
					'noopener,noreferrer,width=' + rong + ',height=' + cao + ',left=' + trai + ',top=' + tren
				);

				// Trình duyệt chặn cửa sổ bật lên thì mở tab thường.
				if ( ! cua ) {
					window.open( dia, '_blank', 'noopener,noreferrer' );
				}
			}

			var khoiLien = lien.closest( '[data-nntm-chia-se]' );

			if ( khoiLien ) {
				dong( khoiLien );
			}

			return;
		}

		// Bấm nút copy thì giữ bảng mở để còn thấy chữ báo "Đã copy".
		if ( goc.closest( '[data-nntm-chia-se]' ) ) {
			return;
		}

		dongTatCa( null );
	} );

	document.addEventListener( 'keydown', function ( su ) {
		if ( 'Escape' !== su.key && 'Esc' !== su.key ) {
			return;
		}

		var dangMo = document.querySelector( '[data-nntm-chia-se].is-mo' );

		if ( ! dangMo ) {
			return;
		}

		dong( dangMo );

		var n = nut( dangMo );

		if ( n ) {
			n.focus();
		}
	} );
}() );
