/**
 * Khung thanh toán.
 *
 * Mở khi: người đọc hết phần xem thử (sự kiện 'nntm:het-xem-thu' do trình đọc
 * bắn ra), hoặc bấm vào bất kỳ nút nào mang data-nntm-tt-mua.
 *
 * KHÔNG tự quyết định đã trả tiền hay chưa. Sau khi khách thanh toán, JS chỉ
 * hỏi lại máy chủ; máy chủ mới là nơi biết webhook của PayOS đã về hay chưa.
 */
( function () {
	'use strict';

	var CH = window.nntmPayos;

	if ( ! CH || ! CH.restTaoDon ) {
		return;
	}

	var khung = document.querySelector( '[data-nntm-tt]' );

	if ( ! khung ) {
		return;
	}

	var i18n = CH.i18n || {};
	var oQR = khung.querySelector( '[data-nntm-tt-qr]' );
	var oTrangThai = khung.querySelector( '[data-nntm-tt-trang-thai]' );
	var nutMo = khung.querySelector( '[data-nntm-tt-mo]' );
	var nutLai = khung.querySelector( '[data-nntm-tt-thu-lai]' );
	var oMa = khung.querySelector( '[data-nntm-tt-ma]' );
	var dongMa = khung.querySelector( '[data-nntm-tt-dong-ma]' );

	var maDon = 0;
	var dongHo = null;
	var dangTao = false;

	/* ------------------------------------------------------------------ */

	function datTrangThai( chu, loai ) {
		oTrangThai.textContent = chu || '';
		oTrangThai.className = 'nntm-tt__trang-thai' + ( loai ? ' nntm-tt__trang-thai--' + loai : '' );
	}

	function mo() {
		khung.hidden = false;
		document.documentElement.style.overflow = 'hidden';

		var dong = khung.querySelector( '[data-nntm-tt-dong]' );
		if ( dong && dong.focus ) { dong.focus(); }
	}

	function dong() {
		khung.hidden = true;
		document.documentElement.style.overflow = '';
		ngungDoi();
	}

	function ngungDoi() {
		if ( dongHo ) {
			clearInterval( dongHo );
			dongHo = null;
		}
	}

	/**
	 * Đặt ảnh mã QR vào ô bên trái.
	 *
	 * Máy chủ đã vẽ sẵn thành SVG (xem includes/qr.php) — ở đây chỉ gắn vào, KHÔNG
	 * tự dựng mã. Nhờ vậy chuỗi thanh toán không cần đi ra tới JavaScript.
	 *
	 * @param {string} svg Mã SVG do máy chủ trả về, hoặc rỗng.
	 */
	function veQR( svg ) {
		oQR.innerHTML = '';

		if ( svg ) {
			/*
			 * Chuỗi này do chính máy chủ dựng, chỉ gồm <svg>/<rect>/<g> — không có
			 * gì của người dùng lọt vào, nên gắn thẳng được.
			 */
			oQR.innerHTML = svg;
			return;
		}

		var p = document.createElement( 'div' );
		p.className = 'nntm-tt__qr-cho';
		p.textContent = 'Bấm “Mở trang thanh toán” để trả tiền.';
		oQR.appendChild( p );
	}

	/* ------------------------------------------------------------------ */

	function goi( url, tuyChon ) {
		return fetch( url, Object.assign( {
			credentials: 'same-origin',
			headers: { 'X-WP-Nonce': CH.nonce }
		}, tuyChon || {} ) ).then( function ( res ) {
			return res.json().then( function ( j ) {
				return { ok: res.ok, du_lieu: j };
			} );
		} );
	}

	function taoDon() {
		if ( dangTao ) { return; }

		if ( ! CH.dangNhap ) {
			datTrangThai( i18n.canDangNhap || '', 'loi' );
			nutMo.href = CH.urlDangNhap;
			nutMo.target = '_self';
			nutMo.textContent = 'Đăng nhập';
			nutMo.hidden = false;
			veQR( '' );
			return;
		}

		dangTao = true;
		nutLai.hidden = true;
		datTrangThai( i18n.dangTao || '' );

		goi( CH.restTaoDon, {
			method: 'POST',
			headers: { 'X-WP-Nonce': CH.nonce, 'Content-Type': 'application/json' },
			body: JSON.stringify( { pub: CH.pub } )
		} ).then( function ( kq ) {
			dangTao = false;

			if ( ! kq.ok ) {
				datTrangThai( ( kq.du_lieu && kq.du_lieu.message ) || i18n.loiMang, 'loi' );
				nutLai.hidden = false;
				return;
			}

			var d = kq.du_lieu || {};

			/* Đã mua từ trước (mở ở tab khác chẳng hạn) -> vào đọc luôn. */
			if ( d.daMua && d.url ) {
				window.location.href = d.url;
				return;
			}

			maDon = parseInt( d.ma, 10 ) || 0;

			if ( oMa && maDon ) {
				oMa.textContent = String( maDon );
				dongMa.hidden = false;
			}

			veQR( d.qrSvg || '' );

			if ( d.checkoutUrl ) {
				nutMo.href = d.checkoutUrl;
				nutMo.target = d.cheDoThu ? '_self' : '_blank';
				nutMo.hidden = false;
			}

			datTrangThai( i18n.dangCho || '' );
			batDauDoi();
		} ).catch( function () {
			dangTao = false;
			datTrangThai( i18n.loiMang, 'loi' );
			nutLai.hidden = false;
		} );
	}

	/**
	 * Hỏi máy chủ xem đơn đã được xác nhận chưa.
	 *
	 * Năm giây một lần: webhook của PayOS thường về trong vài giây, hỏi dày hơn
	 * chỉ tốn công máy chủ mà không nhanh hơn được.
	 */
	function batDauDoi() {
		ngungDoi();

		if ( ! maDon ) { return; }

		dongHo = setInterval( function () {
			goi( CH.restTrangThai + '?ma=' + encodeURIComponent( maDon ) ).then( function ( kq ) {
				if ( ! kq.ok || ! kq.du_lieu || ! kq.du_lieu.daTra ) { return; }

				ngungDoi();
				datTrangThai( i18n.xong || '', 'xong' );

				window.setTimeout( function () {
					window.location.href = kq.du_lieu.url || window.location.href;
				}, 800 );
			} ).catch( function () {} );
		}, 5000 );
	}

	/* ------------------------------------------------------------------ */

	document.addEventListener( 'click', function ( e ) {
		if ( ! e.target.closest ) { return; }

		if ( e.target.closest( '[data-nntm-tt-dong]' ) ) {
			e.preventDefault();
			dong();
			return;
		}

		if ( e.target.closest( '[data-nntm-tt-thu-lai]' ) ) {
			e.preventDefault();
			taoDon();
			return;
		}

		if ( e.target.closest( '[data-nntm-tt-mua]' ) ) {
			e.preventDefault();
			mo();
			taoDon();
		}
	} );

	document.addEventListener( 'keydown', function ( e ) {
		if ( 'Escape' === e.key && ! khung.hidden ) {
			e.preventDefault();
			dong();
		}
	} );

	/* Trình đọc báo đã hết phần xem thử. */
	document.addEventListener( 'nntm:het-xem-thu', function () {
		if ( khung.hidden ) {
			mo();
			taoDon();
		}
	} );

	/*
	 * Quay về từ trang PayOS: hỏi lại máy chủ một lần. Tuyệt đối KHÔNG tin
	 * ?status=PAID trên thanh địa chỉ — ai cũng gõ được.
	 */
	( function quayVe() {
		var m = window.location.search.match( /[?&]nntm_payos_ma=(\d+)/ );

		if ( ! m ) { return; }

		maDon = parseInt( m[ 1 ], 10 ) || 0;

		if ( ! maDon ) { return; }

		mo();
		datTrangThai( i18n.dangCho || '' );
		batDauDoi();
	} )();
} )();
