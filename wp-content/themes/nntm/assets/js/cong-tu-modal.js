/**
 * Popup "Tham Gia Chuỗi Trì" / "Cập Nhật Chuỗi Trì" — mở từ bất kỳ phần tử
 * nào có [data-nntm-chuoi-tri="tham-gia"] hoặc [data-nntm-chuoi-tri="cap-nhat"]
 * (vd nút trên banner "Lễ Đàn Khổng Tước", xem blocks/banner/render.php +
 * inc/cong-tu.php::nntm_congtu_banner_btn_attrs()).
 *
 * Bắt chước ĐÚNG khuôn assets/js/auth-modal.js: thuần JS, không thư viện,
 * đóng bằng Esc / bấm ra ngoài / nút đóng, có bẫy tiêu điểm (focus trap) và
 * trả tiêu điểm về đúng phần tử trước khi mở sau khi đóng.
 *
 * Nếu không tìm thấy popup trong DOM (vd trang này chưa in modal vì chưa có
 * chương trình đang mở, xem nntm_congtu_co_modal_tren_trang()) thì KHÔNG
 * chặn click mặc định — để link (href dự phòng) chạy bình thường.
 */
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
			// Dự phòng: popup chưa được in ra DOM, để link chạy bình thường.
			return;
		}

		event.preventDefault();
		openModal( modal );
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

	/**
	 * @return {HTMLElement|null}
	 */
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

	/**
	 * @param {HTMLElement} modal
	 */
	function openModal( modal ) {
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

	/**
	 * @param {HTMLElement} modal
	 */
	function closeModal( modal ) {
		modal.hidden = true;

		if ( lastFocusedEl && typeof lastFocusedEl.focus === 'function' ) {
			lastFocusedEl.focus();
		}
		lastFocusedEl = null;
	}

	/**
	 * @param {KeyboardEvent} event
	 * @param {HTMLElement}   modal
	 */
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

	/**
	 * @param {HTMLElement} container
	 * @return {HTMLElement[]}
	 */
	function getFocusable( container ) {
		if ( ! container ) {
			return [];
		}

		var nodes = container.querySelectorAll(
			'a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])'
		);

		return Array.prototype.slice.call( nodes );
	}

	/**
	 * Mở lại đúng popup khi trang tải lại sau một lần POST từ popup (lỗi
	 * hoặc thành công) — xem nntm_congtu_body_class() trong inc/cong-tu.php
	 * gắn class "nntm-congtu-mo-lai--tham-gia"/"nntm-congtu-mo-lai--cap-nhat"
	 * lên <body>.
	 */
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
} )();
