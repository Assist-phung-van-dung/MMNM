(function () {
	'use strict';

	var KHOA = 'nntm:dkkt:';

	function modal() {
		return document.querySelector('[data-nntm-retreat-modal]');
	}

	function chu(khoa, macDinh) {
		return (window.nntmRetreat && nntmRetreat[khoa]) || macDinh;
	}

	function maKhoaTu() {
		var m = modal();
		return m ? m.getAttribute('data-nntm-retreat-id') || '' : '';
	}

	function nhoDaDangKy() {
		var ma = maKhoaTu();
		if (!ma) return;
		try { window.localStorage.setItem(KHOA + ma, 'pending'); } catch (e) {}
	}

	function daNhoTruocDo() {
		var ma = maKhoaTu();
		if (!ma) return false;
		try { return !!window.localStorage.getItem(KHOA + ma); } catch (e) { return false; }
	}

	// Ẩn form, hiện lời nhắn, đổi nhãn nút ngoài trang.
	function danhDauDaDangKy(loiNhan) {
		var m = modal();
		if (!m) return;

		var form = m.querySelector('[data-nntm-retreat-form]');
		var bao = m.querySelector('[data-nntm-retreat-xong]');
		var baoChu = m.querySelector('[data-nntm-retreat-xong-chu]');

		if (form) form.hidden = true;
		if (bao) bao.hidden = false;
		if (baoChu && loiNhan) baoChu.textContent = loiNhan;

		var nut = document.querySelector('[data-nntm-retreat-open-register]');
		if (nut) {
			nut.classList.add('is-da-dang-ky');
			var nhan = nut.querySelector('[data-nntm-retreat-nhan]') || nut;
			nhan.textContent = chu('nhanDaDangKy', 'Đang chờ duyệt');
		}
	}

	function moModal() {
		var m = modal();
		if (!m) return;

		m.hidden = false;
		document.documentElement.classList.add('nntm-modal-open');

		var form = m.querySelector('[data-nntm-retreat-form]');
		if (!form || form.hidden) return;

		var dau = form.querySelector('input:not([type="hidden"]):not([tabindex="-1"])');
		if (dau) window.setTimeout(function () { dau.focus(); }, 0);
	}

	function dongModal(m) {
		if (m) m.hidden = true;
		document.documentElement.classList.remove('nntm-modal-open');
	}

	document.addEventListener('click', function (event) {
		if (event.target.closest('[data-nntm-retreat-open-register]')) {
			moModal();
			return;
		}

		if (event.target.closest('[data-nntm-retreat-close]')) {
			dongModal(event.target.closest('[data-nntm-retreat-modal]') || modal());
		}
	});

	document.addEventListener('keydown', function (event) {
		if (event.key !== 'Escape') return;
		var dangMo = document.querySelector('[data-nntm-retreat-modal]:not([hidden])');
		if (dangMo) dongModal(dangMo);
	});

	// Khách chưa đăng nhập: máy chủ không biết họ đã đăng ký, nên trình duyệt tự nhớ.
	var bao = document.querySelector('[data-nntm-retreat-xong]');
	if (bao && bao.hidden && daNhoTruocDo()) {
		danhDauDaDangKy(chu('daDangKy', ''));
	}

	var form = document.querySelector('[data-nntm-retreat-form]');
	if (!form) return;

	form.addEventListener('submit', function (event) {
		event.preventDefault();
		if (!form.reportValidity()) return;

		var submit = form.querySelector('[type="submit"]');
		var message = form.querySelector('[data-nntm-retreat-message]');
		var data = new FormData(form);
		var ajaxUrl = chu('ajaxUrl', '/wp-admin/admin-ajax.php');

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
				: chu('signupError', 'Không thể gửi đăng ký.');

			if (ok) {
				nhoDaDangKy();
				danhDauDaDangKy(text);
				return;
			}

			if (message) {
				message.classList.add('is-error');
				message.textContent = text;
			}
			if (submit) submit.disabled = false;
		}).catch(function () {
			if (message) {
				message.classList.add('is-error');
				message.textContent = chu('signupError', 'Không thể gửi đăng ký.');
			}
			if (submit) submit.disabled = false;
		});
	});
})();
