/*
 * Hai cửa sổ của khối Danh sách Trú Xứ.
 *
 *   1. Bấm TÊN     -> cửa sổ bộ ảnh (ảnh lớn + dải ảnh nhỏ, đi lại bằng phím mũi tên).
 *   2. Bấm ĐỊA CHỈ -> cửa sổ bản đồ. Bấm tiếp "Chỉ đường" thì đổi iframe sang
 *      chế độ directions của Maps Embed API — đường đi hiện NGAY TRONG trang,
 *      không nhảy sang cửa sổ Google Maps.
 *
 * Bản đồ dùng Maps Embed API (nhúng iframe): Google không tính phí, không giới
 * hạn lượt gọi, và không phải nạp thư viện JavaScript nào của Google.
 *
 * KHÔNG tự hỏi vị trí lúc tải trang — chỉ hỏi khi khách bấm "Chỉ đường".
 */
( function () {
	'use strict';

	var CH = window.nntmTruXu || {};
	var CHU = CH.chu || {};

	/* ------------------------------------------------------------------ */
	/* Dùng chung: mở/đóng cửa sổ, giữ tiêu điểm bàn phím                   */
	/* ------------------------------------------------------------------ */

	var nutMoCuoi = null;

	function motSo( khung ) {
		var nhan = khung.querySelectorAll(
			'a[href], button:not([disabled]):not([hidden]), input, select, textarea, [tabindex]:not([tabindex="-1"])'
		);

		return Array.prototype.filter.call( nhan, function ( e ) {
			return e.offsetWidth > 0 || e.offsetHeight > 0;
		} );
	}

	function bayTieuDiem( su, khung ) {
		var danh = motSo( khung );

		if ( ! danh.length ) {
			return;
		}

		var dau = danh[ 0 ];
		var cuoi = danh[ danh.length - 1 ];

		if ( su.shiftKey && document.activeElement === dau ) {
			su.preventDefault();
			cuoi.focus();
		} else if ( ! su.shiftKey && document.activeElement === cuoi ) {
			su.preventDefault();
			dau.focus();
		}
	}

	function moCuaSo( khung, nut ) {
		nutMoCuoi = nut || null;
		khung.hidden = false;
		document.body.classList.add( 'nntm-tx-dang-mo' );

		var nutDong = khung.querySelector( 'button[data-nntm-tx-anh-close], button[data-nntm-tx-map-close]' );
		if ( nutDong && nutDong.focus ) {
			nutDong.focus();
		}
	}

	function dongCuaSo( khung ) {
		if ( ! khung || khung.hidden ) {
			return;
		}

		khung.hidden = true;
		document.body.classList.remove( 'nntm-tx-dang-mo' );

		if ( nutMoCuoi && nutMoCuoi.focus && document.contains( nutMoCuoi ) ) {
			nutMoCuoi.focus();
		}
		nutMoCuoi = null;
	}

	/* ------------------------------------------------------------------ */
	/* Cửa sổ 1: bộ ảnh Trú Xứ                                             */
	/* ------------------------------------------------------------------ */

	var khungAnh = null;
	var danhAnh = [];
	var viTriAnh = 0;

	function sanAnh() {
		if ( ! khungAnh ) {
			khungAnh = document.getElementById( 'nntm-tx-anh' );
		}
		return !! khungAnh;
	}

	function veAnh() {
		if ( ! danhAnh.length ) {
			return;
		}

		var anh = danhAnh[ viTriAnh ];
		var oLon = khungAnh.querySelector( '[data-nntm-tx-anh-lon]' );
		var oChuThich = khungAnh.querySelector( '[data-nntm-tx-anh-chu-thich]' );
		var oDem = khungAnh.querySelector( '[data-nntm-tx-anh-dem]' );

		oLon.src = anh.full;
		oLon.alt = anh.alt || '';

		oChuThich.textContent = anh.caption || '';
		oChuThich.hidden = ! anh.caption;

		if ( oDem && CHU.anhSo ) {
			oDem.textContent = CHU.anhSo
				.replace( '%1$d', String( viTriAnh + 1 ) )
				.replace( '%2$d', String( danhAnh.length ) );
		}

		var nho = khungAnh.querySelectorAll( '[data-nntm-tx-anh-nho]' );
		for ( var i = 0; i < nho.length; i++ ) {
			var dang = i === viTriAnh;
			nho[ i ].classList.toggle( 'la-dang-xem', dang );
			nho[ i ].setAttribute( 'aria-current', dang ? 'true' : 'false' );
		}
	}

	function diAnh( buoc ) {
		if ( danhAnh.length < 2 ) {
			return;
		}

		viTriAnh = ( viTriAnh + buoc + danhAnh.length ) % danhAnh.length;
		veAnh();
	}

	function moBoAnh( nut ) {
		if ( ! sanAnh() ) {
			return;
		}

		try {
			danhAnh = JSON.parse( nut.getAttribute( 'data-anh' ) || '[]' );
		} catch ( loi ) {
			danhAnh = [];
		}

		if ( ! danhAnh.length ) {
			return;
		}

		viTriAnh = 0;
		khungAnh.querySelector( '[data-nntm-tx-anh-ten]' ).textContent = nut.getAttribute( 'data-ten' ) || '';

		/* Dựng lại dải ảnh nhỏ cho đúng Trú Xứ vừa bấm. */
		var dai = khungAnh.querySelector( '[data-nntm-tx-anh-dai]' );
		dai.innerHTML = '';
		dai.hidden = danhAnh.length < 2;

		danhAnh.forEach( function ( anh, i ) {
			var nutNho = document.createElement( 'button' );
			nutNho.type = 'button';
			nutNho.className = 'nntm-tx-anh__nho';
			nutNho.setAttribute( 'data-nntm-tx-anh-nho', String( i ) );

			var hinh = document.createElement( 'img' );
			hinh.src = anh.thumb || anh.full;
			hinh.alt = '';
			hinh.loading = 'lazy';
			nutNho.appendChild( hinh );

			nutNho.addEventListener( 'click', function () {
				viTriAnh = i;
				veAnh();
			} );

			dai.appendChild( nutNho );
		} );

		/* Một ảnh thì giấu luôn hai nút qua lại cho gọn. */
		var moiBen = khungAnh.querySelectorAll( '.nntm-tx-anh__nav' );
		for ( var j = 0; j < moiBen.length; j++ ) {
			moiBen[ j ].hidden = danhAnh.length < 2;
		}

		veAnh();
		moCuaSo( khungAnh, nut );
	}

	/* ------------------------------------------------------------------ */
	/* Cửa sổ 2: bản đồ (Maps Embed API)                                   */
	/* ------------------------------------------------------------------ */

	var khungMap = null;
	var truXuDangXem = null;

	function sanMap() {
		if ( ! khungMap ) {
			khungMap = document.getElementById( 'nntm-tx-map' );
		}
		return !! khungMap;
	}

	function baoMap( chu ) {
		var o = khungMap.querySelector( '[data-nntm-tx-map-nhan]' );
		if ( o ) {
			o.textContent = chu || '';
		}
	}

	function trangThaiMap( chu ) {
		var o = khungMap.querySelector( '[data-nntm-tx-map-trang-thai]' );
		if ( o ) {
			o.textContent = chu || '';
			o.hidden = ! chu;
		}
	}

	/**
	 * Dựng địa chỉ iframe của Maps Embed API.
	 *
	 * @param {Object|null} tuDau Toạ độ xuất phát; có thì dùng chế độ chỉ đường.
	 */
	function diaChiNhung( tuDau ) {
		var den = truXuDangXem.lat + ',' + truXuDangXem.lng;
		var goc = 'https://www.google.com/maps/embed/v1/';

		if ( tuDau ) {
			return goc + 'directions?key=' + encodeURIComponent( CH.apiKey ) +
				'&origin=' + encodeURIComponent( tuDau.lat + ',' + tuDau.lng ) +
				'&destination=' + encodeURIComponent( den ) +
				'&mode=driving';
		}

		return goc + 'place?key=' + encodeURIComponent( CH.apiKey ) +
			'&q=' + encodeURIComponent( den ) +
			'&zoom=16';
	}

	function veBanDo( tuDau ) {
		var khung = khungMap.querySelector( '[data-nntm-tx-map-khung]' );
		var cu = khung.querySelector( 'iframe' );

		if ( cu ) {
			cu.parentNode.removeChild( cu );
		}

		var iframe = document.createElement( 'iframe' );
		iframe.className = 'nntm-tx-map__iframe';
		iframe.src = diaChiNhung( tuDau );
		iframe.setAttribute( 'title', truXuDangXem.ten );
		iframe.setAttribute( 'loading', 'lazy' );
		iframe.setAttribute( 'referrerpolicy', 'no-referrer-when-downgrade' );
		iframe.setAttribute( 'allowfullscreen', '' );

		khung.appendChild( iframe );
	}

	function chiDuong() {
		var nut = khungMap.querySelector( '[data-nntm-tx-map-chi-duong]' );

		if ( ! navigator.geolocation ) {
			baoMap( CHU.khongHoTro );
			return;
		}

		baoMap( CHU.dangXinViTri );
		nut.disabled = true;

		navigator.geolocation.getCurrentPosition(
			function ( vt ) {
				nut.disabled = false;
				veBanDo( { lat: vt.coords.latitude, lng: vt.coords.longitude } );
				baoMap( CHU.dangChiDuong );
			},
			function () {
				/*
				 * Khách từ chối hoặc máy không lấy được vị trí: chỉ báo một
				 * dòng, bản đồ vẫn đang chỉ đúng Trú Xứ, cửa sổ không thành lỗi.
				 */
				nut.disabled = false;
				baoMap( CHU.tuChoiViTri );
			},
			{ enableHighAccuracy: true, timeout: 10000, maximumAge: 60000 }
		);
	}

	function moBanDo( nut ) {
		if ( ! sanMap() ) {
			return;
		}

		truXuDangXem = {
			ten: nut.getAttribute( 'data-ten' ) || '',
			diaChi: nut.getAttribute( 'data-dia-chi' ) || '',
			lat: nut.getAttribute( 'data-lat' ) || '',
			lng: nut.getAttribute( 'data-lng' ) || '',
		};

		khungMap.querySelector( '[data-nntm-tx-map-ten]' ).textContent = truXuDangXem.ten;

		var oDiaChi = khungMap.querySelector( '[data-nntm-tx-map-dia-chi]' );
		oDiaChi.textContent = truXuDangXem.diaChi;
		oDiaChi.hidden = ! truXuDangXem.diaChi;

		baoMap( '' );

		var nutChiDuong = khungMap.querySelector( '[data-nntm-tx-map-chi-duong]' );

		if ( ! CH.apiKey ) {
			var khung = khungMap.querySelector( '[data-nntm-tx-map-khung]' );
			var cu = khung.querySelector( 'iframe' );
			if ( cu ) {
				cu.parentNode.removeChild( cu );
			}

			trangThaiMap( CHU.thieuKey );
			nutChiDuong.hidden = true;
		} else {
			trangThaiMap( '' );
			nutChiDuong.hidden = false;
			veBanDo( null );
		}

		moCuaSo( khungMap, nut );
	}

	/* ------------------------------------------------------------------ */
	/* Bắt sự kiện                                                         */
	/* ------------------------------------------------------------------ */

	document.addEventListener( 'click', function ( su ) {
		if ( ! su.target.closest ) {
			return;
		}

		var nutAnh = su.target.closest( '[data-nntm-tru-xu-anh]' );
		if ( nutAnh ) {
			su.preventDefault();
			moBoAnh( nutAnh );
			return;
		}

		var nutMap = su.target.closest( '[data-nntm-tru-xu-map]' );
		if ( nutMap ) {
			su.preventDefault();
			su.stopPropagation();
			moBanDo( nutMap );
			return;
		}

		if ( sanAnh() && su.target.closest( '[data-nntm-tx-anh-close]' ) ) {
			su.preventDefault();
			dongCuaSo( khungAnh );
			return;
		}

		if ( sanAnh() && su.target.closest( '[data-nntm-tx-anh-truoc]' ) ) {
			su.preventDefault();
			diAnh( -1 );
			return;
		}

		if ( sanAnh() && su.target.closest( '[data-nntm-tx-anh-sau]' ) ) {
			su.preventDefault();
			diAnh( 1 );
			return;
		}

		if ( sanMap() && su.target.closest( '[data-nntm-tx-map-close]' ) ) {
			su.preventDefault();
			dongCuaSo( khungMap );
			return;
		}

		if ( sanMap() && su.target.closest( '[data-nntm-tx-map-chi-duong]' ) ) {
			su.preventDefault();
			chiDuong();
		}
	} );

	document.addEventListener( 'keydown', function ( su ) {
		if ( sanAnh() && ! khungAnh.hidden ) {
			if ( 'Escape' === su.key || 'Esc' === su.key ) {
				su.preventDefault();
				dongCuaSo( khungAnh );
			} else if ( 'ArrowLeft' === su.key ) {
				su.preventDefault();
				diAnh( -1 );
			} else if ( 'ArrowRight' === su.key ) {
				su.preventDefault();
				diAnh( 1 );
			} else if ( 'Tab' === su.key ) {
				bayTieuDiem( su, khungAnh );
			}
			return;
		}

		if ( sanMap() && ! khungMap.hidden ) {
			if ( 'Escape' === su.key || 'Esc' === su.key ) {
				su.preventDefault();
				dongCuaSo( khungMap );
			} else if ( 'Tab' === su.key ) {
				bayTieuDiem( su, khungMap );
			}
		}
	} );
} )();
