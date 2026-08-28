( function () {
	'use strict';

	var MODAL_ID = 'nntm-auth-modal';
	var lastFocusedEl = null;

	document.addEventListener( 'click', function ( event ) {
		var modal = document.getElementById( MODAL_ID );
		if ( ! modal ) {
			return;
		}

		/*
		 * Close controls must work even when PHP renders the modal already open
		 * after a failed login attempt.
		 */
		var closeButton = event.target.closest
			? event.target.closest( '[data-nntm-auth-modal-close]' )
			: null;

		if ( closeButton && modal.contains( closeButton ) ) {
			event.preventDefault();
			closeModal( modal );
			return;
		}

		var overlay = event.target.closest
			? event.target.closest( '[data-nntm-auth-modal-overlay]' )
			: null;

		if ( overlay && modal.contains( overlay ) ) {
			event.preventDefault();
			closeModal( modal );
			return;
		}

		var trigger = event.target.closest
			? event.target.closest( '[data-nntm-auth-modal]' )
			: null;

		if ( ! trigger ) {
			return;
		}

		event.preventDefault();
		openModal( modal, trigger );
	} );

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
			event.preventDefault();
			closeModal( modal );
			return;
		}

		if ( 'Tab' === event.key ) {
			trapFocus( event, modal );
		}
	} );

	function openModal( modal, trigger ) {
		lastFocusedEl = document.activeElement;

		/*
		 * Đăng nhập/đăng ký từ modal phải quay lại đúng trang đang đứng.
		 * Nút nào khai báo data-nntm-auth-redirect thì theo nút đó, còn lại thì
		 * lấy chính URL hiện tại.
		 */
		var redirectTo = ( trigger && trigger.getAttribute
			? trigger.getAttribute( 'data-nntm-auth-redirect' )
			: '' ) || window.location.href;

		var redirectInput = modal.querySelector( 'input[name="redirect_to"]' );

		if ( redirectInput && redirectTo ) {
			redirectInput.value = redirectTo;
		}

		capNhatLienKet( modal, redirectTo );

		modal.hidden = false;

		var focusable = getFocusable( modal );
		if ( focusable.length ) {
			focusable[ 0 ].focus();
		}
	}

	/*
	 * Gắn redirect_to vào các liên kết chuyển từ Đăng nhập sang Đăng ký / Quên
	 * mật khẩu, để chuỗi Đăng nhập -> Đăng ký -> thành công vẫn quay về đúng
	 * trang mà người dùng bắt đầu.
	 */
	function capNhatLienKet( modal, redirectTo ) {
		if ( ! redirectTo ) {
			return;
		}

		var links = modal.querySelectorAll( '[data-nntm-auth-link]' );

		for ( var i = 0; i < links.length; i++ ) {
			var href = links[ i ].getAttribute( 'href' );

			if ( ! href ) {
				continue;
			}

			try {
				var url = new URL( href, window.location.href );

				/* Chỉ đổi liên kết nội bộ. */
				if ( url.origin !== window.location.origin ) {
					continue;
				}

				url.searchParams.set( 'redirect_to', redirectTo );
				links[ i ].setAttribute( 'href', url.toString() );
			} catch ( error ) {
				/* URL không hợp lệ thì cứ để nguyên liên kết cũ. */
			}
		}
	}

	function closeModal( modal ) {
		modal.hidden = true;

		if (
			lastFocusedEl &&
			typeof lastFocusedEl.focus === 'function' &&
			document.contains( lastFocusedEl )
		) {
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

		return Array.prototype.slice.call( nodes );
	}
} )();
