/*
 * Cửa sổ bản đồ Trú Xứ (PROMPT 08).
 *
 * - Bấm "Địa chỉ" -> mở cửa sổ ngay trên trang, KHÔNG chuyển sang Google Maps.
 * - Thư viện Google Maps chỉ được tải ở lần mở cửa sổ đầu tiên, không tải sẵn
 *   lúc vào trang.
 * - KHÔNG tự động hỏi vị trí lúc tải trang: chỉ hỏi khi khách bấm "Chỉ đường".
 * - Khách từ chối chia sẻ vị trí thì bản đồ vẫn hiện đúng Trú Xứ, chỉ báo thêm
 *   một dòng, cửa sổ không vào trạng thái lỗi.
 */
( function () {
	'use strict';

	var CH = window.nntmTruXuMap || {};
	var CHU = CH.chu || {};

	var modal = null;
	var oTen = null;
	var oDiaChi = null;
	var oKhung = null;
	var oTrangThai = null;
	var oNhan = null;
	var nutChiDuong = null;

	var truXuDangXem = null;
	var nutMoCuoi = null;

	/* Bản đồ dùng lại giữa các lần mở, khỏi dựng mới mỗi lần. */
	var banDo = null;
	var diemTruXu = null;
	var veDuong = null;

	var dangTaiThuVien = null;

	function san() {
		if ( modal ) {
			return true;
		}

		modal = document.getElementById( 'nntm-tx-map' );
		if ( ! modal ) {
			return false;
		}

		oTen = modal.querySelector( '[data-nntm-tx-map-ten]' );
		oDiaChi = modal.querySelector( '[data-nntm-tx-map-dia-chi]' );
		oKhung = modal.querySelector( '[data-nntm-tx-map-khung]' );
		oTrangThai = modal.querySelector( '[data-nntm-tx-map-trang-thai]' );
		oNhan = modal.querySelector( '[data-nntm-tx-map-nhan]' );
		nutChiDuong = modal.querySelector( '[data-nntm-tx-map-chi-duong]' );

		return true;
	}

	function bao( chu ) {
		if ( oNhan ) {
			oNhan.textContent = chu || '';
		}
	}

	function trangThai( chu ) {
		if ( oTrangThai ) {
			oTrangThai.textContent = chu || '';
			oTrangThai.hidden = ! chu;
		}
	}

	/* ------------------------------------------------------------------ */
	/* Tải thư viện Google Maps — chỉ một lần, chỉ khi thật sự cần          */
	/* ------------------------------------------------------------------ */

	function taiThuVien() {
		if ( window.google && window.google.maps ) {
			return Promise.resolve();
		}

		if ( dangTaiThuVien ) {
			return dangTaiThuVien;
		}

		if ( ! CH.apiKey ) {
			return Promise.reject( new Error( 'thieu-key' ) );
		}

		dangTaiThuVien = new Promise( function ( xong, hong ) {
			var ten = 'nntmTruXuMapSan';

			window[ ten ] = function () {
				delete window[ ten ];
				xong();
			};

			var the = document.createElement( 'script' );
			the.src = 'https://maps.googleapis.com/maps/api/js?key=' +
				encodeURIComponent( CH.apiKey ) +
				'&libraries=routes&loading=async&callback=' + ten;
			the.async = true;
			the.onerror = function () {
				dangTaiThuVien = null;
				hong( new Error( 'loi-tai' ) );
			};

			document.head.appendChild( the );
		} );

		return dangTaiThuVien;
	}

	/* ------------------------------------------------------------------ */
	/* Vẽ bản đồ                                                           */
	/* ------------------------------------------------------------------ */

	function veBanDo( truXu ) {
		var toaDo = { lat: parseFloat( truXu.lat ), lng: parseFloat( truXu.lng ) };

		if ( ! banDo ) {
			banDo = new window.google.maps.Map( oKhung, {
				center: toaDo,
				zoom: 16,
				mapTypeControl: false,
				streetViewControl: false,
				fullscreenControl: false,
			} );

			diemTruXu = new window.google.maps.Marker( { map: banDo } );

			veDuong = new window.google.maps.DirectionsRenderer( {
				map: banDo,
				suppressMarkers: false,
			} );
		}

		/* Mở Trú Xứ khác thì xoá đường đi của Trú Xứ trước. */
		if ( veDuong ) {
			veDuong.setMap( null );
			veDuong.setDirections( { routes: [] } );
			veDuong.setMap( banDo );
		}

		diemTruXu.setPosition( toaDo );
		diemTruXu.setTitle( truXu.ten );
		banDo.setCenter( toaDo );
		banDo.setZoom( 16 );

		/*
		 * Bản đồ dựng lúc cửa sổ vừa hiện ra nên đôi khi Google đo sai kích
		 * thước khung. Báo cho nó vẽ lại một nhịp sau khi cửa sổ đã bày xong.
		 */
		window.setTimeout( function () {
			window.google.maps.event.trigger( banDo, 'resize' );
			banDo.setCenter( toaDo );
		}, 120 );

		trangThai( '' );
	}

	/* ------------------------------------------------------------------ */
	/* Chỉ đường                                                           */
	/* ------------------------------------------------------------------ */

	function chiDuong() {
		if ( ! truXuDangXem || ! banDo ) {
			return;
		}

		if ( ! navigator.geolocation ) {
			bao( CHU.khongHoTro );
			return;
		}

		bao( CHU.dangXinViTri );
		nutChiDuong.disabled = true;

		navigator.geolocation.getCurrentPosition(
			function ( vt ) {
				var tu = { lat: vt.coords.latitude, lng: vt.coords.longitude };
				var den = { lat: parseFloat( truXuDangXem.lat ), lng: parseFloat( truXuDangXem.lng ) };

				new window.google.maps.DirectionsService().route(
					{ origin: tu, destination: den, travelMode: window.google.maps.TravelMode.DRIVING },
					function ( ketQua, trangThaiTraVe ) {
						nutChiDuong.disabled = false;

						if ( 'OK' !== trangThaiTraVe || ! ketQua ) {
							bao( CHU.loiChiDuong );
							return;
						}

						veDuong.setDirections( ketQua );

						var chang = ketQua.routes[ 0 ] && ketQua.routes[ 0 ].legs[ 0 ];
						if ( chang ) {
							bao( chang.distance.text + ' · ' + chang.duration.text );
						} else {
							bao( '' );
						}
					}
				);
			},
			function () {
				/*
				 * Khách từ chối hoặc máy không lấy được vị trí: chỉ báo một
				 * dòng, bản đồ vẫn đang chỉ đúng Trú Xứ, cửa sổ không thành lỗi.
				 */
				nutChiDuong.disabled = false;
				bao( CHU.tuChoiViTri );
			},
			{ enableHighAccuracy: true, timeout: 10000, maximumAge: 60000 }
		);
	}

	/* ------------------------------------------------------------------ */
	/* Mở / đóng cửa sổ                                                    */
	/* ------------------------------------------------------------------ */

	function mo( nut ) {
		if ( ! san() ) {
			return;
		}

		nutMoCuoi = nut;

		truXuDangXem = {
			ten: nut.getAttribute( 'data-ten' ) || '',
			diaChi: nut.getAttribute( 'data-dia-chi' ) || '',
			lat: nut.getAttribute( 'data-lat' ) || '',
			lng: nut.getAttribute( 'data-lng' ) || '',
		};

		oTen.textContent = truXuDangXem.ten;
		oDiaChi.textContent = truXuDangXem.diaChi;
		oDiaChi.hidden = ! truXuDangXem.diaChi;

		bao( '' );
		trangThai( CHU.dangTai );

		modal.hidden = false;
		document.body.classList.add( 'nntm-tx-map-dang-mo' );

		/*
		 * Lop phu nen cung mang data-nntm-tx-map-close de bam ra ngoai la dong,
		 * nhung no khong nhan duoc tieu diem — phai chi dich danh the <button>.
		 */
		var nutDong = modal.querySelector( 'button[data-nntm-tx-map-close]' );
		if ( nutDong && nutDong.focus ) {
			nutDong.focus();
		}

		taiThuVien().then(
			function () {
				veBanDo( truXuDangXem );
				nutChiDuong.hidden = false;
			},
			function ( loi ) {
				nutChiDuong.hidden = true;
				trangThai( 'thieu-key' === loi.message ? CHU.thieuKey : CHU.loiTai );
			}
		);
	}

	function dong() {
		if ( ! modal || modal.hidden ) {
			return;
		}

		modal.hidden = true;
		document.body.classList.remove( 'nntm-tx-map-dang-mo' );
		bao( '' );

		if ( nutMoCuoi && nutMoCuoi.focus && document.contains( nutMoCuoi ) ) {
			nutMoCuoi.focus();
		}
		nutMoCuoi = null;
	}

	/* Giữ tiêu điểm bàn phím trong cửa sổ khi đang mở. */
	function bayTieuDiem( su ) {
		var nhan = modal.querySelectorAll(
			'a[href], button:not([disabled]):not([hidden]), input, select, textarea, [tabindex]:not([tabindex="-1"])'
		);
		var danh = Array.prototype.filter.call( nhan, function ( e ) {
			return e.offsetWidth > 0 || e.offsetHeight > 0;
		} );

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

	document.addEventListener( 'click', function ( su ) {
		var nut = su.target.closest ? su.target.closest( '[data-nntm-tru-xu-map]' ) : null;

		if ( nut ) {
			/* Nút nằm chồng lên thẻ Trú Xứ (chính nó là một liên kết). */
			su.preventDefault();
			su.stopPropagation();
			mo( nut );
			return;
		}

		if ( san() && su.target.closest( '[data-nntm-tx-map-close]' ) ) {
			su.preventDefault();
			dong();
			return;
		}

		if ( san() && su.target.closest( '[data-nntm-tx-map-chi-duong]' ) ) {
			su.preventDefault();
			chiDuong();
		}
	} );

	document.addEventListener( 'keydown', function ( su ) {
		if ( ! san() || modal.hidden ) {
			return;
		}

		if ( 'Escape' === su.key || 'Esc' === su.key ) {
			su.preventDefault();
			dong();
			return;
		}

		if ( 'Tab' === su.key ) {
			bayTieuDiem( su );
		}
	} );
} )();
