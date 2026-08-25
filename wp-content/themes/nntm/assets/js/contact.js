( function () {
	'use strict';

	var form = document.getElementById( 'nntm-contact-form' );
	if ( ! form || typeof NNTMContact === 'undefined' ) {
		return;
	}

	var alertBox = document.querySelector( '.nntm-contact-form__alert' );
	var submitButton = form.querySelector( '[data-nntm-contact-submit]' );
	var modal = document.getElementById( 'nntm-contact-success-modal' );
	var successMessage = modal ? modal.querySelector( '[data-nntm-contact-success-message]' ) : null;
	var lastFocusedElement = null;

	form.addEventListener( 'submit', function ( event ) {
		event.preventDefault();
		clearErrors();

		var validation = validateForm();
		if ( ! validation.valid ) {
			showError( validation.message, validation.field );
			return;
		}

		setSubmitting( true );

		var data = new FormData( form );
		data.append( 'action', 'nntm_contact_submit' );
		data.append( 'nonce', NNTMContact.nonce );

		fetch( NNTMContact.ajaxUrl, {
			method: 'POST',
			credentials: 'same-origin',
			body: data
		} )
			.then( function ( response ) {
				return response.json().catch( function () {
					throw new Error( NNTMContact.i18n.genericError );
				} );
			} )
			.then( function ( response ) {
				if ( ! response || ! response.success ) {
					var payload = response && response.data ? response.data : {};
					throw {
						message: payload.message || NNTMContact.i18n.genericError,
						field: payload.field || ''
					};
				}

				form.reset();
				openSuccessModal( response.data && response.data.message ? response.data.message : '' );
			} )
			.catch( function ( error ) {
				showError(
					error && error.message ? error.message : NNTMContact.i18n.genericError,
					error && error.field ? error.field : ''
				);
			} )
			.finally( function () {
				setSubmitting( false );
			} );
	} );

	document.addEventListener( 'click', function ( event ) {
		if ( ! modal || modal.hidden ) {
			return;
		}

		var closeButton = event.target.closest ? event.target.closest( '[data-nntm-contact-close]' ) : null;
		if ( closeButton && modal.contains( closeButton ) ) {
			event.preventDefault();
			closeSuccessModal();
		}
	} );

	document.addEventListener( 'keydown', function ( event ) {
		if ( modal && ! modal.hidden && event.key === 'Escape' ) {
			event.preventDefault();
			closeSuccessModal();
		}
	} );

	function validateForm() {
		var nameField = form.elements.ho_ten;
		var emailField = form.elements.email;
		var questionField = form.elements.cau_hoi;
		var emailValue = emailField.value.trim();

		if ( ! nameField.value.trim() ) {
			return { valid: false, message: NNTMContact.i18n.nameRequired, field: 'ho_ten' };
		}

		if ( ! emailValue ) {
			return { valid: false, message: NNTMContact.i18n.emailRequired, field: 'email' };
		}

		if ( ! /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test( emailValue ) ) {
			return { valid: false, message: NNTMContact.i18n.emailInvalid, field: 'email' };
		}

		if ( ! questionField.value.trim() ) {
			return { valid: false, message: NNTMContact.i18n.question, field: 'cau_hoi' };
		}

		return { valid: true };
	}

	function showError( message, fieldName ) {
		if ( alertBox ) {
			alertBox.textContent = message;
			alertBox.hidden = false;
		}

		if ( fieldName && form.elements[ fieldName ] ) {
			form.elements[ fieldName ].setAttribute( 'aria-invalid', 'true' );
			form.elements[ fieldName ].focus();
		}
	}

	function clearErrors() {
		if ( alertBox ) {
			alertBox.hidden = true;
			alertBox.textContent = '';
		}

		var invalidFields = form.querySelectorAll( '[aria-invalid="true"]' );
		for ( var i = 0; i < invalidFields.length; i++ ) {
			invalidFields[ i ].removeAttribute( 'aria-invalid' );
		}
	}

	function setSubmitting( isSubmitting ) {
		if ( ! submitButton ) {
			return;
		}

		submitButton.disabled = isSubmitting;
		submitButton.textContent = isSubmitting ? NNTMContact.i18n.sending : NNTMContact.i18n.submit;
	}

	function openSuccessModal( message ) {
		if ( ! modal ) {
			return;
		}

		lastFocusedElement = document.activeElement;
		if ( successMessage && message ) {
			successMessage.textContent = message;
		}

		modal.hidden = false;
		var close = modal.querySelector( '.nntm-contact-modal__close' );
		if ( close ) {
			close.focus();
		}
	}

	function closeSuccessModal() {
		if ( ! modal ) {
			return;
		}

		modal.hidden = true;
		if ( lastFocusedElement && typeof lastFocusedElement.focus === 'function' ) {
			lastFocusedElement.focus();
		}
	}
} )();
