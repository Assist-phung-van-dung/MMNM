/**
 * Popup câu hỏi gác cửa Nghi Quỹ.
 *
 * Luồng: bấm vào Nghi Quỹ bị khoá -> chặn điều hướng -> hỏi máy chủ lấy câu hỏi
 * -> người dùng chọn đáp án -> gửi lên máy chủ chấm -> đúng hết thì đi thẳng vào
 * nội dung, sai thì báo đúng một câu và đóng bộ câu hỏi lại.
 *
 * Ở đây KHÔNG có đáp án đúng. Máy chủ chấm, JS chỉ hỏi và hiển thị.
 */
( function () {
	'use strict';

	var CAU_HINH = window.nntmNghiQuyQuiz;

	if ( ! CAU_HINH || ! CAU_HINH.ajaxUrl ) {
		return;
	}

	var modal = document.querySelector( '[data-nntm-quiz-modal]' );

	if ( ! modal ) {
		return;
	}

	var form = modal.querySelector( '[data-nntm-quiz-form]' );
	var vungCauHoi = modal.querySelector( '[data-nntm-quiz-questions]' );
	var vungTrangThai = modal.querySelector( '[data-nntm-quiz-status]' );
	var nutGui = modal.querySelector( '[data-nntm-quiz-submit]' );
	var nutLamLai = modal.querySelector( '[data-nntm-quiz-retry]' );
	var vungTienDo = modal.querySelector( '[data-nntm-quiz-tien-do]' );
	var i18n = CAU_HINH.i18n || {};

	var pubDangHoi = 0;
	var nutMoCuoi = null;
	var dangGui = false;

	/* ------------------------------------------------------------------ */

	function datTrangThai( text, loai, choLamLai ) {
		vungTrangThai.textContent = text || '';
		vungTrangThai.className = 'nntm-quiz-modal__status'
			+ ( loai ? ' nntm-quiz-modal__status--' + loai : '' );
		vungTrangThai.hidden = ! text;

		if ( nutLamLai ) {
			nutLamLai.hidden = ! choLamLai;
		}
	}

	/*
	 * Đếm số câu đã chọn đáp án và tô đậm ô đang chọn.
	 *
	 * Ô chọn thật bị CSS ẩn đi để vẽ nhãn A/B/C cho đẹp, nên trạng thái "đang
	 * chọn" phải tự gắn bằng class — không trông chờ vào :checked của trình duyệt.
	 */
	function capNhatChon() {
		var cacCau = vungCauHoi.querySelectorAll( '.nntm-quiz-modal__cau' );
		var daChon = 0;

		Array.prototype.forEach.call( cacCau, function ( fs ) {
			var co = false;

			Array.prototype.forEach.call( fs.querySelectorAll( '.nntm-quiz-modal__dap-an' ), function ( label ) {
				var o = label.querySelector( 'input[type="radio"]' );
				var chon = !! ( o && o.checked );

				label.classList.toggle( 'is-chon', chon );

				if ( chon ) {
					co = true;
				}
			} );

			if ( co ) {
				daChon++;
			}
		} );

		if ( vungTienDo ) {
			vungTienDo.textContent = cacCau.length
				? ( i18n.tienDo || 'Đã trả lời %1$d/%2$d' )
					.replace( '%1$d', daChon )
					.replace( '%2$d', cacCau.length )
				: '';
		}
	}

	function oCoTheFocus() {
		return Array.prototype.slice.call(
			modal.querySelectorAll(
				'a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])'
			)
		);
	}

	function mo( trigger ) {
		nutMoCuoi = trigger || document.activeElement;
		modal.hidden = false;
		document.documentElement.classList.add( 'nntm-quiz-open' );
		document.body.classList.add( 'nntm-quiz-open' );

		var canFocus = oCoTheFocus();
		if ( canFocus.length ) {
			canFocus[ 0 ].focus();
		}
	}

	function dong() {
		modal.hidden = true;
		document.documentElement.classList.remove( 'nntm-quiz-open' );
		document.body.classList.remove( 'nntm-quiz-open' );
		pubDangHoi = 0;

		if ( nutMoCuoi && typeof nutMoCuoi.focus === 'function' && document.contains( nutMoCuoi ) ) {
			nutMoCuoi.focus();
		}
		nutMoCuoi = null;
	}

	function xoaCauHoi() {
		vungCauHoi.innerHTML = '';
		form.hidden = true;

		if ( vungTienDo ) {
			vungTienDo.textContent = '';
		}
	}

	function gui( action, duLieu ) {
		var body = new URLSearchParams();
		body.set( 'action', action );
		body.set( 'nonce', CAU_HINH.nonce );

		Object.keys( duLieu ).forEach( function ( khoa ) {
			var gia_tri = duLieu[ khoa ];

			if ( Array.isArray( gia_tri ) ) {
				gia_tri.forEach( function ( phan_tu, i ) {
					body.append( khoa + '[' + i + ']', phan_tu );
				} );
				return;
			}

			body.set( khoa, gia_tri );
		} );

		return fetch( CAU_HINH.ajaxUrl, {
			method: 'POST',
			credentials: 'same-origin',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
			body: body.toString()
		} ).then( function ( res ) {
			return res.json().catch( function () {
				return { success: false, data: { message: i18n.loiMang } };
			} );
		} );
	}

	/* ------------------------------------------------------------------ */

	function veCauHoi( danhSach ) {
		var mảnh = document.createDocumentFragment();

		danhSach.forEach( function ( cau, chiSo ) {
			var fieldset = document.createElement( 'fieldset' );
			fieldset.className = 'nntm-quiz-modal__cau';

			var legend = document.createElement( 'legend' );
			legend.className = 'nntm-quiz-modal__hoi';
			legend.textContent = ( chiSo + 1 ) + '. ' + cau.hoi;
			fieldset.appendChild( legend );

			( cau.dap_an || [] ).forEach( function ( nhan, viTri ) {
				var label = document.createElement( 'label' );
				label.className = 'nntm-quiz-modal__dap-an';

				var radio = document.createElement( 'input' );
				radio.type = 'radio';
				radio.name = 'cau-' + chiSo;
				radio.value = String( viTri );

				/* Ô vuông chữ A/B/C — nội dung do CSS đếm ra, ở đây chỉ dựng chỗ. */
				var kyHieu = document.createElement( 'span' );
				kyHieu.className = 'nntm-quiz-modal__ky-hieu';
				kyHieu.setAttribute( 'aria-hidden', 'true' );

				var span = document.createElement( 'span' );
				span.className = 'nntm-quiz-modal__nhan';
				span.textContent = nhan;

				label.appendChild( radio );
				label.appendChild( kyHieu );
				label.appendChild( span );
				fieldset.appendChild( label );
			} );

			mảnh.appendChild( fieldset );
		} );

		vungCauHoi.innerHTML = '';
		vungCauHoi.appendChild( mảnh );
		form.hidden = false;
		capNhatChon();
	}

	function nap( pubId, trigger ) {
		pubDangHoi = pubId;
		xoaCauHoi();
		datTrangThai( i18n.dangTai || '' );
		mo( trigger );

		gui( 'nntm_quiz_cau_hoi', { pub: pubId } ).then( function ( ketQua ) {
			if ( pubDangHoi !== pubId ) {
				return;
			}

			if ( ! ketQua || ! ketQua.success ) {
				datTrangThai( ( ketQua && ketQua.data && ketQua.data.message ) || i18n.loiMang, 'loi' );
				return;
			}

			/* Đã đậu từ trước trong session này -> vào đọc luôn, không hỏi lại. */
			if ( ketQua.data.passed ) {
				window.location.href = ketQua.data.url;
				return;
			}

			datTrangThai( '' );
			veCauHoi( ketQua.data.cauHoi || [] );
		} ).catch( function () {
			datTrangThai( i18n.loiMang, 'loi' );
		} );
	}

	function nop( event ) {
		event.preventDefault();

		if ( dangGui || ! pubDangHoi ) {
			return;
		}

		var fieldsets = vungCauHoi.querySelectorAll( '.nntm-quiz-modal__cau' );
		var traLoi = [];
		var thieu = false;

		Array.prototype.forEach.call( fieldsets, function ( fs, chiSo ) {
			var chon = fs.querySelector( 'input[type="radio"]:checked' );

			if ( ! chon ) {
				thieu = true;
				traLoi[ chiSo ] = '';
				return;
			}

			traLoi[ chiSo ] = chon.value;
		} );

		if ( thieu ) {
			datTrangThai( i18n.chuaChon, 'loi' );
			return;
		}

		dangGui = true;
		nutGui.disabled = true;
		datTrangThai( i18n.dangCham || '' );

		var pubId = pubDangHoi;

		gui( 'nntm_quiz_nop', { pub: pubId, tra_loi: traLoi } ).then( function ( ketQua ) {
			dangGui = false;
			nutGui.disabled = false;

			if ( ! ketQua || ! ketQua.success ) {
				datTrangThai( ( ketQua && ketQua.data && ketQua.data.message ) || i18n.loiMang, 'loi' );
				return;
			}

			if ( ketQua.data.pass ) {
				window.location.href = ketQua.data.url;
				return;
			}

			/*
			 * Sai: đóng bộ câu hỏi lại, chỉ để lại đúng câu thông báo. Người dùng
			 * muốn làm lại thì bấm vào Nghi Quỹ lần nữa — không giới hạn số lần.
			 */
			xoaCauHoi();
			datTrangThai( ketQua.data.message, 'loi', true );
		} ).catch( function () {
			dangGui = false;
			nutGui.disabled = false;
			datTrangThai( i18n.loiMang, 'loi' );
		} );
	}

	/* ------------------------------------------------------------------ */

	document.addEventListener( 'click', function ( event ) {
		if ( ! event.target.closest ) {
			return;
		}

		var dong_nut = event.target.closest( '[data-nntm-quiz-close]' );
		if ( dong_nut && modal.contains( dong_nut ) ) {
			event.preventDefault();
			dong();
			return;
		}

		var trigger = event.target.closest( '[data-nntm-quiz]' );
		if ( ! trigger ) {
			return;
		}

		var pubId = parseInt( trigger.getAttribute( 'data-nntm-quiz' ), 10 );
		if ( ! pubId ) {
			return;
		}

		event.preventDefault();
		nap( pubId, trigger );
	} );

	document.addEventListener( 'keydown', function ( event ) {
		if ( modal.hidden ) {
			return;
		}

		if ( 'Escape' === event.key ) {
			event.preventDefault();
			dong();
			return;
		}

		if ( 'Tab' !== event.key ) {
			return;
		}

		var canFocus = oCoTheFocus();
		if ( ! canFocus.length ) {
			return;
		}

		var dau = canFocus[ 0 ];
		var cuoi = canFocus[ canFocus.length - 1 ];

		if ( event.shiftKey && document.activeElement === dau ) {
			event.preventDefault();
			cuoi.focus();
		} else if ( ! event.shiftKey && document.activeElement === cuoi ) {
			event.preventDefault();
			dau.focus();
		}
	} );

	form.addEventListener( 'submit', nop );

	/* Chọn đáp án: tô đậm ô vừa chọn và đếm lại số câu đã trả lời. */
	vungCauHoi.addEventListener( 'change', function ( event ) {
		if ( event.target && 'radio' === event.target.type ) {
			capNhatChon();
		}
	} );

	/*
	 * "Trả lời lại" — hỏi lại đúng Nghi Quỹ vừa rồi, không bắt người dùng đóng
	 * popup rồi bấm vào thẻ một lần nữa. Số lần làm lại không giới hạn.
	 */
	if ( nutLamLai ) {
		nutLamLai.addEventListener( 'click', function () {
			if ( pubDangHoi > 0 ) {
				nap( pubDangHoi, nutMoCuoi );
			}
		} );
	}

	/*
	 * wp_localize_script biến số thành chuỗi, nên "0" vẫn là truthy trong JS —
	 * phải so sánh sau khi ép về số, không được kiểm tra trực tiếp.
	 */
	var tuMo = parseInt( CAU_HINH.autoOpen, 10 );

	if ( tuMo > 0 ) {
		nap( tuMo, null );
	}
} )();
