/*
 * Chọn ảnh logo cho màn hình chờ (Giao diện → Trích dẫn màn hình chờ).
 *
 * Vì sao là tệp riêng chứ không phải <script> viết thẳng trong form: thư viện
 * media của WordPress (media-editor) là script đặt ở CUỐI trang. Đoạn script
 * nằm giữa trang sẽ chạy trước khi wp.media tồn tại, thấy nó chưa có rồi thoát
 * luôn — nút bấm không được gắn gì cả, bấm vào không hiện gì.
 *
 * Khai báo phụ thuộc 'media-editor' thì WordPress bảo đảm tệp này chỉ chạy sau
 * khi thư viện đã sẵn sàng.
 */
( function () {
	'use strict';

	var CHU = window.nntmPreloaderLogo || {};

	var nutChon = document.getElementById( 'nntm-preloader-logo-chon' );
	var nutGo = document.getElementById( 'nntm-preloader-logo-go' );
	var oId = document.getElementById( 'nntm-preloader-logo-id' );
	var khungXem = document.getElementById( 'nntm-preloader-logo-xem' );

	if ( ! nutChon || ! nutGo || ! oId || ! khungXem ) {
		return;
	}

	if ( ! window.wp || ! window.wp.media ) {
		/*
		 * Không im lặng bỏ qua: nút vẫn nằm đó và bấm không ra gì, người dùng
		 * không biết vì sao. Báo rõ ra màn hình.
		 */
		nutChon.disabled = true;
		nutChon.textContent = CHU.loiThuVien || 'Không nạp được thư viện ảnh';
		return;
	}

	var KIEU_ANH =
		'max-width:160px;height:auto;display:block;' +
		'background:#1b1b1b;padding:14px;border-radius:8px;';

	var khung = null;

	function veAnh( url ) {
		khungXem.innerHTML = '';

		var img = document.createElement( 'img' );
		img.src = url;
		img.alt = '';
		img.style.cssText = KIEU_ANH;

		khungXem.appendChild( img );
	}

	nutChon.addEventListener( 'click', function () {
		if ( ! khung ) {
			khung = window.wp.media( {
				title: CHU.tieuDe || '',
				library: { type: 'image' },
				button: { text: CHU.nutDung || '' },
				multiple: false,
			} );

			khung.on( 'select', function () {
				var anh = khung.state().get( 'selection' ).first().toJSON();

				oId.value = anh.id;

				/* Ảnh cỡ medium cho nhẹ; ảnh nhỏ quá thì không có bản medium. */
				veAnh( ( anh.sizes && anh.sizes.medium ) ? anh.sizes.medium.url : anh.url );

				nutChon.textContent = CHU.doiAnh || '';
				nutGo.style.display = '';
			} );
		}

		khung.open();
	} );

	nutGo.addEventListener( 'click', function () {
		oId.value = '';
		khungXem.innerHTML = '';
		nutChon.textContent = CHU.chonAnh || '';
		nutGo.style.display = 'none';
	} );
} )();
