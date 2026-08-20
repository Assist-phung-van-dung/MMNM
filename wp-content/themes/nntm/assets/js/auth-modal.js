/**
 * Modal đăng nhập dùng chung — mở từ bất kỳ phần tử nào có
 * [data-nntm-auth-modal] (vd nút "Mời vào" ở trang Nhập Pháp Giới).
 *
 * Thuần JS, không thư viện. Đóng bằng Esc / bấm ra ngoài / nút đóng.
 * Có bẫy tiêu điểm (focus trap) đơn giản trong lúc modal mở, và trả
 * tiêu điểm về đúng phần tử trước khi mở sau khi đóng.
 *
 * Nếu không tìm thấy modal trong DOM (vd template-parts/auth/modal-dang-nhap.php
 * chưa được in ra vì đã đăng nhập) thì KHÔNG chặn click mặc định — để
 * link chạy bình thường, tránh khoá cứng nút bấm.
 */
( function () {
	'use strict';

	var MODAL_ID = 'nntm-auth-modal';
	var lastFocusedEl = null;

	document.addEventListener( 'click', function ( event ) {
		var trigger = event.target.closest ? event.target.closest( '[data-nntm-auth-modal]' ) : null;
		if ( ! trigger ) {
			return;
		}

		var modal = document.getElementById( MODAL_ID );
		if ( ! modal ) {
			// Dự phòng: không có modal trong DOM, để link chạy bình thường.
			return;
		}

		event.preventDefault();
		openModal( modal, trigger );
	} );


	// Khi submit sai tài khoản/mật khẩu, PHP render lại modal ở trạng thái
	// mở để người dùng thấy lỗi ngay. Đặt focus vào field đầu tiên sau khi DOM
	// sẵn sàng, không buộc họ bấm "Đăng nhập" lần nữa.
	document.addEventListener( 'DOMContentLoaded', function () {
		var modal = document.getElementById( MODAL_ID );
		if ( ! modal || modal.hidden ) {
			return;
		}
		var focusable = getFocusable( modal );
		if ( focusable.length ) {
			focusable[ 0 ].focus();
		}
	} );

	document.addEventListener( 'keydown', function ( event ) {
		var modal = document.getElementById( MODAL_ID );
		if ( ! modal || modal.hidden ) {
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
	 * @param {HTMLElement} modal
	 */
	function openModal( modal, trigger ) {
		lastFocusedEl = document.activeElement;

		// Mỗi trigger có thể yêu cầu quay lại một URL khác sau khi đăng nhập.
		// Header quay lại trang đang xem; thẻ Kim Cương quay thẳng tới trang
		// đích. Secret/cookie không đi qua JS, đây chỉ là URL redirect.
		var redirectInput = modal.querySelector( 'input[name="redirect_to"]' );
		var redirectTo = trigger && trigger.getAttribute
			? trigger.getAttribute( 'data-nntm-auth-redirect' )
			: '';
		if ( redirectInput && redirectTo ) {
			redirectInput.value = redirectTo;
		}

		modal.hidden = false;

		var closeButtons = modal.querySelectorAll( '[data-nntm-auth-modal-close]' );
		for ( var i = 0; i < closeButtons.length; i++ ) {
			closeButtons[ i ].addEventListener( 'click', function () {
				closeModal( modal );
			} );
		}

		var overlay = modal.querySelector( '[data-nntm-auth-modal-overlay]' );
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
} )();
