( function () {
	'use strict';

	var KHOA   = 'nntm:quay-lai';
	var HAN_MS = 6 * 60 * 60 * 1000;

	function boHash( url ) {
		var i = String( url ).indexOf( '#' );

		return -1 === i ? String( url ) : String( url ).slice( 0, i );
	}

	function chuanHoa( url ) {
		return boHash( url ).replace( /\/+$/, '' ).toLowerCase();
	}

	function cungMien( url ) {
		return 0 === String( url ).indexOf( window.location.origin + '/' );
	}

	function docVet() {
		var tho;

		try {
			tho = window.sessionStorage.getItem( KHOA );
		} catch ( e ) {
			return null;
		}

		if ( ! tho ) {
			return null;
		}

		var vet;

		try {
			vet = JSON.parse( tho );
		} catch ( e ) {
			return null;
		}

		if ( ! vet || ! vet.nguon || ! vet.neo || ! vet.dich || ! vet.luc ) {
			return null;
		}

		if ( ( new Date() ).getTime() - vet.luc > HAN_MS ) {
			return null;
		}

		if ( ! cungMien( vet.nguon ) ) {
			return null;
		}

		return vet;
	}

	function ghiVet() {
		document.addEventListener(
			'click',
			function ( su ) {
				if ( su.defaultPrevented || su.button > 0 || su.metaKey || su.ctrlKey || su.shiftKey || su.altKey ) {
					return;
				}

				var goc = su.target;

				if ( ! goc || ! goc.closest ) {
					return;
				}

				var lien = goc.closest( 'a[href]' );

				if ( ! lien || '_blank' === lien.target || ! cungMien( lien.href ) ) {
					return;
				}

				var muc = lien.closest( '.nntm-card-list[id], section[id]' );

				if ( ! muc || ! muc.id ) {
					return;
				}

				try {
					window.sessionStorage.setItem(
						KHOA,
						JSON.stringify( {
							nguon: boHash( window.location.href ),
							neo:   muc.id,
							dich:  boHash( lien.href ),
							luc:   ( new Date() ).getTime()
						} )
					);
				} catch ( e ) {}
			},
			true
		);
	}

	function ganNutQuayLai() {
		var nut = document.querySelector( '[data-nntm-doc="quay-lai"]' );

		if ( ! nut ) {
			return;
		}

		var vet = docVet();

		if ( ! vet ) {
			return;
		}

		var dich = chuanHoa( vet.dich );
		var day  = chuanHoa( window.location.href );

		// Sách khoá cửa đi vòng qua trang đăng nhập rồi mới tới /doc/, nên đích đã lưu
		// có thể chỉ là phần đầu của địa chỉ hiện tại.
		if ( dich !== day && 0 !== day.indexOf( dich + '/' ) ) {
			return;
		}

		nut.href = vet.nguon + '#' + vet.neo;
	}

	function veDungCho() {
		var hash = window.location.hash;

		if ( ! hash || hash.length < 2 ) {
			return;
		}

		var dich;

		try {
			dich = document.querySelector( hash );
		} catch ( e ) {
			return;
		}

		if ( ! dich || ! dich.matches( '.nntm-card-list, section' ) ) {
			return;
		}

		var canh = function () {
			dich.scrollIntoView( { block: 'start' } );
		};

		canh();
		window.addEventListener( 'load', function () {
			window.setTimeout( canh, 60 );
		} );
	}

	function khoiDong() {
		ghiVet();
		ganNutQuayLai();
		veDungCho();
	}

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', khoiDong );
	} else {
		khoiDong();
	}
}() );
