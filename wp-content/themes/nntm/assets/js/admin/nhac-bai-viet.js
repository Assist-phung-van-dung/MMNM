/*
 * Chọn nhạc nền ở ô nhập bên phải của trình soạn thảo CŨ.
 *
 * Là tệp riêng chứ không phải <script> viết thẳng trong ô nhập: thư viện media
 * của WordPress nằm ở cuối trang, đoạn script giữa trang sẽ chạy trước khi
 * wp.media tồn tại và không gắn được gì vào nút. Khai báo phụ thuộc
 * 'media-editor' thì WordPress bảo đảm tệp này chạy sau.
 */
( function () {
	'use strict';

	var o = document.querySelector( '[data-nntm-nhac-o]' );

	if ( ! o ) {
		return;
	}

	var oId = o.querySelector( '[data-nntm-nhac-id]' );
	var dangChon = o.querySelector( '[data-nntm-nhac-dang-chon]' );
	var oTen = o.querySelector( '[data-nntm-nhac-ten]' );
	var ngheThu = o.querySelector( '[data-nntm-nhac-nghe-thu]' );
	var oTrong = o.querySelector( '[data-nntm-nhac-trong]' );
	var nutChon = o.querySelector( '[data-nntm-nhac-chon]' );
	var nutGo = o.querySelector( '[data-nntm-nhac-go]' );

	if ( ! oId || ! nutChon || ! nutGo ) {
		return;
	}

	if ( ! window.wp || ! window.wp.media ) {
		/*
		 * Không im lặng bỏ qua: nút vẫn nằm đó, bấm không ra gì, người dùng
		 * không biết vì sao. Báo thẳng ra nút.
		 */
		nutChon.disabled = true;
		nutChon.textContent = 'Không nạp được Thư viện';
		return;
	}

	var khung = null;

	function veTrangThai( ten, url ) {
		var coNhac = !! url;

		if ( oTen ) {
			oTen.textContent = ten || '';
		}

		if ( ngheThu ) {
			ngheThu.src = url || '';
		}

		if ( dangChon ) {
			dangChon.hidden = ! coNhac;
		}

		if ( oTrong ) {
			oTrong.hidden = coNhac;
		}

		nutChon.textContent = coNhac ? 'Đổi nhạc nền' : 'Chọn nhạc nền';
		nutGo.hidden = ! coNhac;
	}

	nutChon.addEventListener( 'click', function () {
		if ( ! khung ) {
			khung = window.wp.media( {
				title: 'Chọn nhạc nền cho bài viết',
				library: { type: 'audio' },
				button: { text: 'Dùng tệp này' },
				multiple: false,
			} );

			khung.on( 'select', function () {
				var tep = khung.state().get( 'selection' ).first().toJSON();

				oId.value = tep.id;
				veTrangThai( tep.title || tep.filename || '', tep.url || '' );
			} );
		}

		khung.open();
	} );

	nutGo.addEventListener( 'click', function () {
		oId.value = '';

		/* Dừng hẳn tệp đang nghe thử, nếu không nhạc vẫn chạy sau khi gỡ. */
		if ( ngheThu && ! ngheThu.paused ) {
			ngheThu.pause();
		}

		veTrangThai( '', '' );
	} );
} )();
