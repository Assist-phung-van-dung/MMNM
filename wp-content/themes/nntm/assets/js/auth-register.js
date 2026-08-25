( function () {
	'use strict';

	var messages = window.nntmAuthRegister || {};

	document.addEventListener( 'DOMContentLoaded', function () {
		var actionInput = document.querySelector(
			'.nntm-auth-form input[name="nntm_auth_action"][value="dang-ky"]'
		);

		if ( ! actionInput ) {
			return;
		}

		var form = actionInput.closest( 'form' );
		if ( ! form ) {
			return;
		}

		var fields = {
			name: form.querySelector( '[name="ho_ten"]' ),
			email: form.querySelector( '[name="user_email"]' ),
			dharma: form.querySelector( '[name="user_login"]' ),
			password: form.querySelector( '[name="user_password"]' ),
			confirm: form.querySelector( '[name="user_password_2"]' ),
			terms: form.querySelector( '[name="nntm_dong_y_dieu_khoan"]' )
		};

		if ( fields.dharma ) {
			fields.dharma.minLength = 2;
		}

		form.addEventListener( 'input', function ( event ) {
			var field = event.target;

			if ( field && typeof field.setCustomValidity === 'function' ) {
				field.setCustomValidity( '' );
			}

			if ( field === fields.password || field === fields.confirm ) {
				updatePasswordMatch( fields );
			}
		} );

		form.addEventListener( 'invalid', function ( event ) {
			var field = event.target;

			if ( ! field || typeof field.setCustomValidity !== 'function' ) {
				return;
			}

			/*
			 * Clear the browser's previous custom message before evaluating
			 * the current native validity state.
			 */
			field.setCustomValidity( '' );

			if ( field === fields.name && field.validity.valueMissing ) {
				field.setCustomValidity( getMessage( 'requiredName', 'Vui lòng nhập Họ và Tên.' ) );
				return;
			}

			if ( field === fields.email ) {
				if ( field.validity.valueMissing ) {
					field.setCustomValidity( getMessage( 'requiredEmail', 'Vui lòng nhập Email.' ) );
				} else if ( field.validity.typeMismatch ) {
					field.setCustomValidity( getMessage( 'invalidEmail', 'Email không hợp lệ. Vui lòng kiểm tra lại.' ) );
				}
				return;
			}

			if ( field === fields.dharma ) {
				if ( field.validity.valueMissing ) {
					field.setCustomValidity( getMessage( 'requiredDharma', 'Vui lòng nhập Pháp danh.' ) );
				} else if ( field.validity.tooShort ) {
					field.setCustomValidity( getMessage( 'shortDharma', 'Pháp danh phải có ít nhất 2 ký tự.' ) );
				}
				return;
			}

			if ( field === fields.password ) {
				if ( field.validity.valueMissing ) {
					field.setCustomValidity( getMessage( 'requiredPassword', 'Vui lòng nhập mật khẩu.' ) );
				} else if ( field.validity.tooShort ) {
					field.setCustomValidity( getMessage( 'shortPassword', 'Mật khẩu phải có ít nhất 8 ký tự.' ) );
				}
				return;
			}

			if ( field === fields.confirm ) {
				if ( field.validity.valueMissing ) {
					field.setCustomValidity( getMessage( 'requiredConfirm', 'Vui lòng nhập lại mật khẩu.' ) );
				} else if ( field.validity.tooShort ) {
					field.setCustomValidity( getMessage( 'shortPassword', 'Mật khẩu phải có ít nhất 8 ký tự.' ) );
				} else if (
					fields.password &&
					field.value !== fields.password.value
				) {
					field.setCustomValidity( getMessage( 'passwordMismatch', 'Hai mật khẩu không khớp.' ) );
				}
			}

			if ( field === fields.terms && field.validity.valueMissing ) {
				field.setCustomValidity( getMessage( 'requiredTerms', 'Vui lòng đồng ý với Điều khoản sử dụng.' ) );
			}
		}, true );

		form.addEventListener( 'submit', function ( event ) {
			updatePasswordMatch( fields );

			if ( fields.confirm && ! fields.confirm.checkValidity() ) {
				event.preventDefault();
				fields.confirm.reportValidity();
			}
		} );
	} );

	function updatePasswordMatch( fields ) {
		if ( ! fields.password || ! fields.confirm ) {
			return;
		}

		fields.confirm.setCustomValidity( '' );

		if (
			fields.confirm.value &&
			fields.password.value !== fields.confirm.value
		) {
			fields.confirm.setCustomValidity(
				getMessage( 'passwordMismatch', 'Hai mật khẩu không khớp.' )
			);
		}
	}

	function getMessage( key, fallback ) {
		return typeof messages[ key ] === 'string' && messages[ key ]
			? messages[ key ]
			: fallback;
	}
} )();
