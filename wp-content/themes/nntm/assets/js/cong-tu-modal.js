 
( function () {
	'use strict';

	var MODAL_IDS = {
		'tham-gia': 'nntm-cong-tu-modal-tham-gia',
		'cap-nhat': 'nntm-cong-tu-modal-cap-nhat'
	};
	var lastFocusedEl = null;

	document.addEventListener( 'click', function ( event ) {
		var trigger = event.target.closest ? event.target.closest( '[data-nntm-chuoi-tri]' ) : null;
		if ( ! trigger ) {
			return;
		}

		var key     = trigger.getAttribute( 'data-nntm-chuoi-tri' );
		var modalId = Object.prototype.hasOwnProperty.call( MODAL_IDS, key ) ? MODAL_IDS[ key ] : null;
		var modal   = modalId ? document.getElementById( modalId ) : null;

		if ( ! modal ) {

			return;
		}

		event.preventDefault();


		openModal( modal, true );
	} );

	document.addEventListener( 'keydown', function ( event ) {
		var modal = timModalDangMo();
		if ( ! modal ) {
			return;
		}

		if ( 'Escape' === event.key ) {
			closeModal( modal );
			return;
		}

		if ( 'Tab' === event.key ) {
			trapFocus( event, modal );
		}
	} );

	function timModalDangMo() {
		var keys = Object.keys( MODAL_IDS );
		for ( var i = 0; i < keys.length; i++ ) {
			var modal = document.getElementById( MODAL_IDS[ keys[ i ] ] );
			if ( modal && ! modal.hidden ) {
				return modal;
			}
		}
		return null;
	}

	function openModal( modal, datLai ) {
		if ( datLai ) {
			datLaiForm( modal );
		}

		lastFocusedEl = document.activeElement;
		modal.hidden = false;

		var closeButtons = modal.querySelectorAll( '[data-nntm-congtu-modal-close]' );
		for ( var i = 0; i < closeButtons.length; i++ ) {
			closeButtons[ i ].addEventListener( 'click', function () {
				closeModal( modal );
			} );
		}

		var overlay = modal.querySelector( '[data-nntm-congtu-modal-overlay]' );
		if ( overlay ) {
			overlay.addEventListener( 'click', function () {
				closeModal( modal );
			} );
		}

		var focusable = getFocusable( modal );
		if ( focusable.length ) {
			focusable[ 0 ].focus();
		}
	}

	function closeModal( modal ) {
		modal.hidden = true;

		if ( lastFocusedEl && typeof lastFocusedEl.focus === 'function' ) {
			lastFocusedEl.focus();
		}
		lastFocusedEl = null;
	}

	function trapFocus( event, modal ) {
		var focusable = getFocusable( modal );
		if ( ! focusable.length ) {
			return;
		}

		var first = focusable[ 0 ];
		var last  = focusable[ focusable.length - 1 ];

		if ( event.shiftKey && document.activeElement === first ) {
			event.preventDefault();
			last.focus();
		} else if ( ! event.shiftKey && document.activeElement === last ) {
			event.preventDefault();
			first.focus();
		}
	}

	function getFocusable( container ) {
		if ( ! container ) {
			return [];
		}

		var nodes = container.querySelectorAll(
			'a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])'
		);

		return Array.prototype.filter.call( nodes, function ( node ) {
			return null !== node.offsetParent;
		} );
	}

	function moLaiPopupTheoBodyClass() {
		var keys = Object.keys( MODAL_IDS );
		for ( var i = 0; i < keys.length; i++ ) {
			if ( document.body.classList.contains( 'nntm-congtu-mo-lai--' + keys[ i ] ) ) {
				var modal = document.getElementById( MODAL_IDS[ keys[ i ] ] );
				if ( modal ) {
					openModal( modal );
				}
				return;
			}
		}
	}

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', moLaiPopupTheoBodyClass );
	} else {
		moLaiPopupTheoBodyClass();
	}

	var CAU_HINH = window.nntmCongTu || null;

	document.addEventListener( 'submit', function ( event ) {
		var form = event.target;

		if ( ! form || ! form.matches || ! form.matches( 'form[data-nntm-congtu-ajax]' ) ) {
			return;
		}

		if ( ! CAU_HINH || ! CAU_HINH.ajaxUrl || ! CAU_HINH.action || ! window.fetch || ! window.FormData ) {
			return; 
		}

		event.preventDefault();
		guiForm( form );
	} );

	function guiForm( form ) {
		if ( 'true' === form.getAttribute( 'data-nntm-dang-gui' ) ) {
			return;
		}

		var nutGui = form.querySelector( '[type="submit"]' );
		var duLieu = new window.FormData( form );

		duLieu.append( 'action', CAU_HINH.action );


		var khoi = document.querySelector( '[data-nntm-congtu-block]' );
		if ( khoi ) {
			duLieu.append( 'lam_moi_khoi', '1' );
			duLieu.append( 'khoi_program_id', khoi.getAttribute( 'data-nntm-congtu-program' ) || '0' );
			duLieu.append( 'khoi_bxh_heading', khoi.getAttribute( 'data-nntm-congtu-bxh-heading' ) || '' );
			duLieu.append( 'khoi_bxh_limit', khoi.getAttribute( 'data-nntm-congtu-bxh-limit' ) || '50' );
		}

		form.setAttribute( 'data-nntm-dang-gui', 'true' );
		if ( nutGui ) {
			nutGui.disabled = true;
		}

		window.fetch( CAU_HINH.ajaxUrl, {
			method: 'POST',
			credentials: 'same-origin',
			body: duLieu
		} )
			.then( function ( response ) {
				return response.json();
			} )
			.then( function ( payload ) {
				var duLieuTraVe = payload && payload.data ? payload.data : {};

				if ( ! payload || ! payload.success ) {
					hienThongBao( form, [ duLieuTraVe.message || CAU_HINH.errorText ], false );
					return;
				}

				hoanTatForm( form, duLieuTraVe );
				baoDuoiNutBanner( duLieuTraVe.tong_ket );
				capNhatKhoiThongKe( khoi, duLieuTraVe );
				doiNutBanner( duLieuTraVe.nhan_nut_banner );

				var modalDangMo = form.closest( '.nntm-auth-modal' );
				if ( modalDangMo ) {
					closeModal( modalDangMo );
				}
			} )
			.catch( function () {
				hienThongBao( form, [ CAU_HINH.errorText ], false );
			} )
			.finally( function () {
				form.removeAttribute( 'data-nntm-dang-gui' );
				if ( nutGui ) {
					nutGui.disabled = false;
				}
			} );
	}

	function hienThongBao( form, dongChu, thanhCong ) {
		var the = form.closest( '.nntm-auth-card' );
		var o   = the ? the.querySelector( '[data-nntm-congtu-thong-bao]' ) : null;

		if ( ! o ) {
			return;
		}

		var hop = document.createElement( 'div' );
		hop.className = 'nntm-auth-alert ' + ( thanhCong ? 'nntm-auth-alert--ok' : 'nntm-auth-alert--loi' );
		hop.setAttribute( 'role', thanhCong ? 'status' : 'alert' );

		var soDong = 0;
		for ( var i = 0; i < dongChu.length; i++ ) {
			if ( ! dongChu[ i ] ) {
				continue;
			}

			var doan = document.createElement( 'p' );
			doan.textContent = dongChu[ i ];
			hop.appendChild( doan );
			++soDong;
		}

		if ( ! soDong ) {
			return;
		}

		o.textContent = '';
		o.appendChild( hop );
	}

	function hoanTatForm( form, duLieu ) {
		var the = form.closest( '.nntm-auth-card' );

		if ( the ) {
			datChu( the.querySelector( '.nntm-cong-tu__hien-trang' ), duLieu.hien_trang );

			var tongKet = the.querySelector( '.nntm-cong-tu__tong-ket' );
			if ( tongKet ) {
				datChu( tongKet, duLieu.tong_ket );
				tongKet.hidden = true;
			}
		}

		form.hidden = true;


		var modal   = form.closest( '.nntm-auth-modal' );
		var nutDong = modal ? modal.querySelector( '[data-nntm-congtu-modal-close]' ) : null;
		if ( nutDong ) {
			nutDong.focus();
		}
	}

	function datLaiForm( modal ) {
		var forms = modal.querySelectorAll( 'form[data-nntm-congtu-ajax]' );
		for ( var i = 0; i < forms.length; i++ ) {
			forms[ i ].hidden = false;
		}

		var o = modal.querySelector( '[data-nntm-congtu-thong-bao]' );
		if ( o ) {
			o.textContent = '';
		}

		var tongKet = modal.querySelector( '.nntm-cong-tu__tong-ket' );
		if ( tongKet ) {
			tongKet.hidden = false;
		}
	}

	function datChu( el, chu ) {
		if ( el && 'string' === typeof chu ) {
			el.textContent = chu;
		}
	}

	function capNhatKhoiThongKe( khoi, duLieu ) {
		if ( ! khoi ) {
			return;
		}

		thayThe( khoi.querySelector( '.nntm-cong-tu__thong-ke' ), duLieu.thong_ke_html );
		thayThe( khoi.querySelector( '.nntm-cong-tu__bxh' ), duLieu.bxh_html );
	}

	function thayThe( cu, html ) {
		if ( ! cu || 'string' !== typeof html || '' === html.trim() ) {
			return;
		}

		var khuon = document.createElement( 'template' );
		khuon.innerHTML = html.trim();

		var moi = khuon.content.firstElementChild;
		if ( moi ) {
			cu.replaceWith( moi );
		}
	}

	 
	function baoDuoiNutBanner( chu ) {
		if ( ! chu ) {
			return;
		}

		var nut = document.querySelector( '[data-nntm-chuoi-tri]' );
		if ( ! nut || ! nut.parentNode ) {
			return;
		}

		var o = document.querySelector( '[data-nntm-congtu-bao-nut]' );

		if ( ! o ) {
			o = document.createElement( 'p' );
			o.className = 'nntm-cong-tu__tong-ket nntm-cong-tu__tong-ket--duoi-nut';
			o.setAttribute( 'data-nntm-congtu-bao-nut', '' );
			o.setAttribute( 'role', 'status' );
			nut.parentNode.insertBefore( o, nut.nextSibling );
		}

		o.textContent = chu;
	}

	function doiNutBanner( nhanMoi ) {
		if ( ! nhanMoi ) {
			return;
		}

		var nutList = document.querySelectorAll( '[data-nntm-chuoi-tri="tham-gia"]' );
		for ( var i = 0; i < nutList.length; i++ ) {
			nutList[ i ].setAttribute( 'data-nntm-chuoi-tri', 'cap-nhat' );
			nutList[ i ].textContent = nhanMoi;
		}
	}
} )();
