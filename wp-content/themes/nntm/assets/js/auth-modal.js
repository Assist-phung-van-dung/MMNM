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

		var redirectInput = modal.querySelector( 'input[name="redirect_to"]' );
		var redirectTo = trigger && trigger.getAttribute
			? trigger.getAttribute( 'data-nntm-auth-redirect' )
			: '';

		if ( redirectInput && redirectTo ) {
			redirectInput.value = redirectTo;
		}

		modal.hidden = false;

		var focusable = getFocusable( modal );
		if ( focusable.length ) {
			focusable[ 0 ].focus();
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
