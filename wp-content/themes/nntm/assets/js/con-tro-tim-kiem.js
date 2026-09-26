/*
 * Đổi con trỏ chuột NGAY KHI khách đang gõ vào ô tìm kiếm ở header, nếu câu
 * gõ khớp một Từ khoá động đã gắn hiệu ứng (Từ khoá động -> Con trỏ chuột ->
 * "Theo từ khoá tìm kiếm").
 *
 * Chỉ được nạp khi có ít nhất 1 từ khoá gắn hiệu ứng (xem
 * inc/con-tro.php::nntm_con_tro_enqueue_assets() — kiểm bằng nntm_con_tro_tu_khoa_ban_do()).
 * Bộ máy vẽ (con-tro.js) được nạp LƯỜI: trang không hề bật hiệu ứng nào theo
 * cài đặt chung vẫn không tải engine cho tới khi khách thật sự gõ trúng một
 * từ khoá.
 *
 * QUY TẮC KHỚP phải giống HỆT plugin nntm-core/includes/con-tro-khop-tim-kiem.php
 * (nntm_tkd_khop_tim_kiem) — cùng một bộ ca thử chạy qua cả PHP lẫn JS, xem
 * scratchpad kiểm thử. Đổi một bên mà quên bên kia là hai nơi lệch nhau.
 */
( function () {
	'use strict';

	var CHU = window.nntmConTroTimKiem;

	if ( ! CHU || ! CHU.banDo || ! CHU.banDo.length ) {
		return;
	}

	var truong = document.querySelector( '.nntm-header__search-field' );

	if ( ! truong ) {
		return; // Không có ô tìm ở trang này (vd header thành viên đã đăng nhập).
	}

	/* ==========================================================================
	 * Bộ gấp dấu tiếng Việt — PHẢI khớp với hàm nntm_tkd_bo_dau() phía PHP.
	 * ========================================================================== */

	var BANG_GAP = {
		'à': 'a', 'á': 'a', 'ạ': 'a', 'ả': 'a', 'ã': 'a', 'â': 'a', 'ầ': 'a', 'ấ': 'a', 'ậ': 'a', 'ẩ': 'a', 'ẫ': 'a', 'ă': 'a', 'ằ': 'a', 'ắ': 'a', 'ặ': 'a', 'ẳ': 'a', 'ẵ': 'a',
		'è': 'e', 'é': 'e', 'ẹ': 'e', 'ẻ': 'e', 'ẽ': 'e', 'ê': 'e', 'ề': 'e', 'ế': 'e', 'ệ': 'e', 'ể': 'e', 'ễ': 'e',
		'ì': 'i', 'í': 'i', 'ị': 'i', 'ỉ': 'i', 'ĩ': 'i',
		'ò': 'o', 'ó': 'o', 'ọ': 'o', 'ỏ': 'o', 'õ': 'o', 'ô': 'o', 'ồ': 'o', 'ố': 'o', 'ộ': 'o', 'ổ': 'o', 'ỗ': 'o', 'ơ': 'o', 'ờ': 'o', 'ớ': 'o', 'ợ': 'o', 'ở': 'o', 'ỡ': 'o',
		'ù': 'u', 'ú': 'u', 'ụ': 'u', 'ủ': 'u', 'ũ': 'u', 'ư': 'u', 'ừ': 'u', 'ứ': 'u', 'ự': 'u', 'ử': 'u', 'ữ': 'u',
		'ỳ': 'y', 'ý': 'y', 'ỵ': 'y', 'ỷ': 'y', 'ỹ': 'y',
		'đ': 'd'
	};

	function boDau( s ) {
		s = String( s || '' ).toLowerCase();
		var ra = '';
		for ( var i = 0; i < s.length; i++ ) {
			ra += BANG_GAP[ s[ i ] ] || s[ i ];
		}
		return ra;
	}

	function coDau( s ) {
		return boDau( s ) !== String( s || '' ).toLowerCase();
	}

	function tachTu( s ) {
		var m = String( s || '' ).trim().match( /[\p{L}\p{N}]+/gu );
		return m || [];
	}

	/**
	 * Đối chiếu một dạng viết (từ khoá / biến thể) với câu tìm.
	 * Trả 0 (không khớp), 1 (nguyên văn), 2 (nằm trọn trong câu tìm), 3 (câu tìm là tiền tố).
	 */
	function doiChieuMotDang( tuCau, tuDang, cauCoDau ) {
		if ( ! tuCau.length || ! tuDang.length ) { return 0; }

		var chuan = function ( tu ) { return cauCoDau ? tu.toLowerCase() : boDau( tu ); };
		var a = tuCau.map( chuan );
		var b = tuDang.map( chuan );

		if ( a.join( ' \u0001 ' ) === b.join( ' \u0001 ' ) ) { return 1; }

		if ( b.length <= a.length ) {
			for ( var i = 0; i <= a.length - b.length; i++ ) {
				if ( a.slice( i, i + b.length ).join( ' \u0001 ' ) === b.join( ' \u0001 ' ) ) { return 2; }
			}
		}

		if ( a.length <= b.length && b.slice( 0, a.length ).join( ' \u0001 ' ) === a.join( ' \u0001 ' ) ) { return 3; }

		return 0;
	}

	/** Tìm từ khoá khớp tốt nhất — cùng thứ tự ưu tiên với PHP. */
	function khopTimKiem( cau ) {
		cau = ( cau || '' ).trim();
		if ( ! cau ) { return null; }

		var tuCau = tachTu( cau );
		if ( ! tuCau.length ) { return null; }

		var cauCoDau = coDau( cau );
		var ungVien = [];

		CHU.banDo.forEach( function ( tk ) {
			var cacDang = [ tk.ten ].concat( tk.bienThe || [] );
			var loaiTot = 0;
			var daiTot = 0;

			cacDang.forEach( function ( dang ) {
				var tuDang = tachTu( dang );
				var loai = doiChieuMotDang( tuCau, tuDang, cauCoDau );
				if ( 0 === loai ) { return; }
				if ( 0 === loaiTot || loai < loaiTot || ( loai === loaiTot && tuDang.length > daiTot ) ) {
					loaiTot = loai;
					daiTot = tuDang.length;
				}
			} );

			if ( loaiTot > 0 ) {
				ungVien.push( { kieu: tk.kieu, mau: tk.mau || '', loai: loaiTot, daiDang: daiTot } );
			}
		} );

		if ( ! ungVien.length ) { return null; }

		ungVien.sort( function ( a, b ) {
			if ( a.loai !== b.loai ) { return a.loai - b.loai; }
			if ( 2 === a.loai ) { return b.daiDang - a.daiDang; }
			if ( 3 === a.loai ) { return a.daiDang - b.daiDang; }
			return 0;
		} );

		return ungVien[ 0 ];
	}

	/* ==========================================================================
	 * Nạp con-tro.js LƯỜI + áp dụng / phục hồi cấu hình.
	 * ========================================================================== */

	var dangNapEngine = null;

	function napEngine() {
		if ( window.NNTMConTro ) { return Promise.resolve(); }
		if ( dangNapEngine ) { return dangNapEngine; }

		dangNapEngine = new Promise( function ( xong ) {
			if ( CHU.conTroCssUrl ) {
				var lk = document.createElement( 'link' );
				lk.rel = 'stylesheet';
				lk.href = CHU.conTroCssUrl;
				document.head.appendChild( lk );
			}

			var sc = document.createElement( 'script' );
			sc.src = CHU.conTroJsUrl;
			sc.onload = function () { xong(); };
			sc.onerror = function () { xong(); };
			document.body.appendChild( sc );
		} );

		return dangNapEngine;
	}

	var cauHinhGoc = undefined; // undefined = chưa từng ghi đè; null = trang gốc không có instance nào.
	var dangGhiDe = false;
	var hienTaiTuKhoa = CHU.hienTai || null; // {kieu,mau} nếu chính trang này server đã chọn theo từ khoá.

	function apDung( kieu, mau ) {
		napEngine().then( function () {
			if ( ! window.NNTMConTro ) { return; }

			if ( undefined === cauHinhGoc ) {
				cauHinhGoc = window.__nntmConTroChinh ? 'CO_SAN' : null;
			}

			var cfg = {
				kieu: kieu,
				mau: mau || '',
				mauPhu: '',
				doDai: CHU.chung.doDai,
				matDo: CHU.chung.matDo,
				co: CHU.chung.co
			};

			if ( window.__nntmConTroChinh ) {
				window.__nntmConTroChinh.doiCauHinh( cfg );
			} else {
				window.__nntmConTroChinh = window.NNTMConTro.khoiTao( cfg );
			}

			dangGhiDe = true;
			hienTaiTuKhoa = { kieu: kieu, mau: mau || '' };
		} );
	}

	function phucHoi() {
		if ( ! dangGhiDe ) { return; }
		dangGhiDe = false;
		hienTaiTuKhoa = CHU.hienTai || null;

		if ( 'CO_SAN' !== cauHinhGoc && window.__nntmConTroChinh ) {
			window.__nntmConTroChinh.huy();
			window.__nntmConTroChinh = null;
		}
		// 'CO_SAN': trang tự có cấu hình riêng đang chạy — không đụng vào, coi
		// như "quay lại cấu hình của trang hiện tại" (đã và đang chạy sẵn).
	}

	var demGio = null;

	truong.addEventListener( 'input', function () {
		clearTimeout( demGio );
		var cau = truong.value;

		demGio = setTimeout( function () {
			var khop = khopTimKiem( cau );

			if ( khop ) {
				apDung( khop.kieu, khop.mau );
			} else {
				phucHoi();

				// "Tự hết khi tìm một câu khác không còn khớp từ khoá nào".
				if ( cau.trim() && CHU.giuKhiBam ) {
					try { window.sessionStorage.removeItem( 'nntm_con_tro_giu' ); } catch ( e ) {}
				}
			}
		}, 150 );
	} );

	/* ==========================================================================
	 * "Giữ hiệu ứng khi bấm vào kết quả tìm kiếm".
	 * ========================================================================== */

	if ( CHU.giuKhiBam ) {
		document.addEventListener( 'click', function ( e ) {
			if ( ! hienTaiTuKhoa ) { return; }

			var lienKet = e.target && e.target.closest ? e.target.closest( 'a[href]' ) : null;
			if ( ! lienKet ) { return; }

			try {
				window.sessionStorage.setItem( 'nntm_con_tro_giu', JSON.stringify( hienTaiTuKhoa ) );
			} catch ( err ) {
				// Cửa sổ ẩn danh / bộ nhớ chặn: bỏ qua, không phải lỗi nghiêm trọng.
			}
		}, true );
	}

}() );
