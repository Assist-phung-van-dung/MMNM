(function () {
	'use strict';

	function copyText(text) {
		if (navigator.clipboard && navigator.clipboard.writeText) {
			return navigator.clipboard.writeText(text);
		}
		return new Promise(function (resolve, reject) {
			var input = document.createElement('textarea');
			input.value = text;
			input.setAttribute('readonly', '');
			input.style.position = 'fixed';
			input.style.opacity = '0';
			document.body.appendChild(input);
			input.select();
			try {
				document.execCommand('copy') ? resolve() : reject(new Error('copy failed'));
			} catch (error) {
				reject(error);
			}
			document.body.removeChild(input);
		});
	}

	document.addEventListener('click', function (event) {
		var share = event.target.closest('[data-nntm-share]');
		if (share) {
			var title = share.getAttribute('data-title') || document.title;
			var url = share.getAttribute('data-url') || window.location.href;
			var status = document.querySelector('[data-nntm-share-status]');

			if (navigator.share) {
				navigator.share({ title: title, url: url }).catch(function (error) {
					if (error && error.name === 'AbortError') return;
					if (status) status.textContent = (window.nntmRetreat && nntmRetreat.shareError) || 'Không thể chia sẻ.';
				});
			} else {
				copyText(url).then(function () {
					if (status) status.textContent = (window.nntmRetreat && nntmRetreat.shareCopied) || 'Đã sao chép liên kết.';
				}).catch(function () {
					if (status) status.textContent = (window.nntmRetreat && nntmRetreat.shareError) || 'Không thể chia sẻ.';
				});
			}
			return;
		}

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
