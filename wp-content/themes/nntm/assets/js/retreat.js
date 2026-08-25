(function () {
	'use strict';

	document.addEventListener('click', function (event) {
		var open = event.target.closest('[data-nntm-retreat-open-register]');
		if (open) {
			var modal = document.querySelector('[data-nntm-retreat-modal]');
			if (!modal) return;
			modal.hidden = false;
			document.documentElement.classList.add('nntm-modal-open');
			var first = modal.querySelector('input:not([type="hidden"]):not([tabindex="-1"])');
			if (first) window.setTimeout(function () { first.focus(); }, 0);
			return;
		}

		if (event.target.closest('[data-nntm-retreat-close]')) {
			var currentModal = event.target.closest('[data-nntm-retreat-modal]');
			if (currentModal) currentModal.hidden = true;
			document.documentElement.classList.remove('nntm-modal-open');
		}
	});

	document.addEventListener('keydown', function (event) {
		if (event.key !== 'Escape') return;
		var modal = document.querySelector('[data-nntm-retreat-modal]:not([hidden])');
		if (modal) {
			modal.hidden = true;
			document.documentElement.classList.remove('nntm-modal-open');
		}
	});

	var form = document.querySelector('[data-nntm-retreat-form]');
	if (!form) return;

	form.addEventListener('submit', function (event) {
		event.preventDefault();
		if (!form.reportValidity()) return;

		var submit = form.querySelector('[type="submit"]');
		var message = form.querySelector('[data-nntm-retreat-message]');
		var data = new FormData(form);
		var ajaxUrl = window.nntmRetreat && nntmRetreat.ajaxUrl ? nntmRetreat.ajaxUrl : '/wp-admin/admin-ajax.php';

		if (submit) submit.disabled = true;
		if (message) {
			message.classList.remove('is-error', 'is-success');
			message.textContent = '';
		}

		fetch(ajaxUrl, {
			method: 'POST',
			credentials: 'same-origin',
			body: data
		}).then(function (response) {
			return response.json();
		}).then(function (payload) {
			var ok = !!(payload && payload.success);
			var text = payload && payload.data && payload.data.message
				? payload.data.message
				: ((window.nntmRetreat && nntmRetreat.signupError) || 'Không thể gửi đăng ký.');

			if (message) {
				message.classList.add(ok ? 'is-success' : 'is-error');
				message.textContent = text;
			}
			if (ok && submit) submit.disabled = true;
			else if (submit) submit.disabled = false;
		}).catch(function () {
			if (message) {
				message.classList.add('is-error');
				message.textContent = (window.nntmRetreat && nntmRetreat.signupError) || 'Không thể gửi đăng ký.';
			}
			if (submit) submit.disabled = false;
		});
	});
})();
