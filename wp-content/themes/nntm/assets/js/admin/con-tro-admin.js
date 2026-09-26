/*
 * Màn quản trị "Giao diện -> Con trỏ chuột".
 *
 * Ba việc trên cùng một trang:
 *   1. Khung xem thử — chạy CHÍNH assets/js/con-tro.js ở chế độ "vùng", luôn
 *      vẽ theo NGUỒN đang chọn (cài đặt chung, hoặc một dòng trong bảng).
 *   2. Cài đặt chung — lưới thẻ chọn kiểu + màu (wp-color-picker) + độ dài
 *      vệt + mật độ. Đổi cái nào, khung thử cập nhật ngay nếu đang xem "chung".
 *   3. Bảng "Theo từng trang" — thêm/xoá dòng, tìm bài qua REST
 *      /wp/v2/search, mỗi dòng có nút "Xem thử" đưa dòng đó lên khung xem thử.
 *
 * Dùng jQuery vì wp-color-picker (Iris) đòi hỏi — trang quản trị WordPress
 * luôn có sẵn jQuery nên không phát sinh phụ thuộc mới.
 */
( function ( $ ) {
	'use strict';

	var CHU = window.nntmConTroAdmin || {};
	var i18n = CHU.i18n || {};

	var $khungThu   = $( '#nntm-con-tro-khung-thu' );
	var $vungThu    = $khungThu.find( '.nntm-con-tro-khung-thu__vung' );
	var $nhanNguon  = $( '#nntm-con-tro-khung-thu__nguon' );
	var $bangThan   = $( '#nntm-con-tro-bang-trang__than' );

	var phienThu   = null; // instance NNTMConTro hiện tại của khung thử.
	var $dongDangXem = null; // $tr đang được xem thử, null = đang xem "chung".
	var demChiSo   = $bangThan.find( 'tr' ).length;
	var thoiGianTim = null;

	/* ==========================================================================
	 * Đọc cấu hình hiệu lực (chung / một dòng) để đưa vào khung thử.
	 * ========================================================================== */

	function chungCauHinh() {
		return {
			kieu: $( 'input[name="nntm_con_tro[kieu]"]:checked' ).val() || '',
			mau: $( '#nntm-con-tro-mau' ).val() || '',
			mauPhu: $( '#nntm-con-tro-mau-phu' ).val() || '',
			doDai: parseInt( $( '#nntm-con-tro-do-dai' ).val(), 10 ) || 30,
			matDo: $( '#nntm-con-tro-mat-do' ).val() || 'vua',
			co: $( '#nntm-con-tro-co' ).val() || 'vua'
		};
	}

	function dongCauHinh( $tr ) {
		var chung = chungCauHinh();
		var kieuDong = $tr.find( '.nntm-con-tro-dong__kieu' ).val();
		var mauDong = $tr.find( '.nntm-con-tro-dong__mau' ).val();
		var doDaiDong = $tr.find( '.nntm-con-tro-dong__do-dai' ).val();

		return {
			kieu: '' === kieuDong ? chung.kieu : kieuDong,
			mau: mauDong || chung.mau,
			mauPhu: mauDong ? '' : chung.mauPhu,
			doDai: doDaiDong ? parseInt( doDaiDong, 10 ) : chung.doDai,
			matDo: chung.matDo,
			co: chung.co
		};
	}

	function tieuDeDong( $tr ) {
		var t = $tr.find( '.nntm-con-tro-dong__tim' ).val();
		if ( ! t ) { t = $tr.find( 'td' ).first().text().trim(); } // dòng "theo từ khoá": tên nằm trong <a>, không có ô tìm.
		return t || ( i18n.chuaChonTrang || '' );
	}

	/* ==========================================================================
	 * Khung xem thử
	 * ========================================================================== */

	function capNhatXemThu() {
		var hieuLuc = $dongDangXem ? dongCauHinh( $dongDangXem ) : chungCauHinh();
		var dangTat = ( '' === hieuLuc.kieu || 'tat' === hieuLuc.kieu );

		$nhanNguon.text(
			( $dongDangXem
				? 'Đang xem thử: ' + tieuDeDong( $dongDangXem )
				: 'Đang xem thử: Cài đặt chung' )
		);

		if ( dangTat ) {
			if ( phienThu ) {
				phienThu.huy();
				phienThu = null;
			}
			$vungThu.attr( 'data-tat', '1' );
			return;
		}

		$vungThu.removeAttr( 'data-tat' );

		if ( ! phienThu ) {
			phienThu = window.NNTMConTro.khoiTao( {
				vung: $vungThu.get( 0 ),
				kieu: hieuLuc.kieu,
				mau: hieuLuc.mau,
				mauPhu: hieuLuc.mauPhu,
				doDai: hieuLuc.doDai,
				matDo: hieuLuc.matDo,
				co: hieuLuc.co
			} );
		} else {
			phienThu.doiCauHinh( {
				kieu: hieuLuc.kieu,
				mau: hieuLuc.mau,
				mauPhu: hieuLuc.mauPhu,
				doDai: hieuLuc.doDai,
				matDo: hieuLuc.matDo,
				co: hieuLuc.co
			} );
		}
	}

	$( '#nntm-con-tro-doi-nen' ).on( 'click', function () {
		var moi = 'toi' === $khungThu.attr( 'data-nen' ) ? 'kem' : 'toi';
		$khungThu.attr( 'data-nen', moi );
	} );

	/* ==========================================================================
	 * Cài đặt chung
	 * ========================================================================== */

	function veLaiTheChon() {
		$( '.nntm-con-tro-the' ).each( function () {
			var $the = $( this );
			$the.toggleClass( 'la-dang-chon', $the.find( 'input' ).is( ':checked' ) );
		} );
	}

	$( document ).on( 'change', 'input[name="nntm_con_tro[kieu]"]', function () {
		veLaiTheChon();
		if ( ! $dongDangXem ) { capNhatXemThu(); }
	} );
	veLaiTheChon();

	$( '#nntm-con-tro-do-dai' ).on( 'input change', function () {
		$( '#nntm-con-tro-do-dai-so' ).text( $( this ).val() );
		if ( ! $dongDangXem ) { capNhatXemThu(); }
	} );

	$( '#nntm-con-tro-mat-do' ).on( 'change', function () {
		if ( ! $dongDangXem ) { capNhatXemThu(); }
	} );

	$( '#nntm-con-tro-co' ).on( 'change', function () {
		if ( ! $dongDangXem ) { capNhatXemThu(); }
	} );

	/* ==========================================================================
	 * wp-color-picker — khởi tạo cho ô có sẵn LẪN ô mới sinh ra (dòng thêm sau).
	 * ========================================================================== */

	function khoiTaoBangMau( $pham_vi ) {
		$pham_vi.find( '.nntm-con-tro-mau-picker' ).each( function () {
			var $o = $( this );
			if ( $o.data( 'wpColorPicker' ) ) { return; }

			$o.wpColorPicker( {
				change: function () {
					// wpColorPicker cập nhật giá trị input SAU sự kiện này một nhịp.
					setTimeout( function () {
						if ( $o.closest( 'tr' ).length ) {
							if ( $o.closest( 'tr' ).is( $dongDangXem ) ) { capNhatXemThu(); }
						} else if ( ! $dongDangXem ) {
							capNhatXemThu();
						}
					}, 0 );
				},
				clear: function () {
					setTimeout( function () {
						if ( $o.closest( 'tr' ).length ) {
							if ( $o.closest( 'tr' ).is( $dongDangXem ) ) { capNhatXemThu(); }
						} else if ( ! $dongDangXem ) {
							capNhatXemThu();
						}
					}, 0 );
				}
			} );
		} );
	}

	khoiTaoBangMau( $( document ) );

	$( '.nntm-con-tro-ve-mac-dinh' ).on( 'click', function () {
		var dich = $( this ).data( 'target' );
		var $o = $( '#' + dich );
		$o.val( '' );
		if ( $o.data( 'wpColorPicker' ) ) {
			$o.wpColorPicker( 'color', '' );
		}
		if ( ! $dongDangXem ) { capNhatXemThu(); }
	} );

	/* ==========================================================================
	 * Bảng "Theo từng trang"
	 * ========================================================================== */

	function danhSachPostIdDangDung( boQua$tr ) {
		var ds = [];
		$bangThan.find( 'tr' ).each( function () {
			if ( boQua$tr && this === boQua$tr.get( 0 ) ) { return; }
			var id = parseInt( $( this ).find( '.nntm-con-tro-dong__post-id' ).val(), 10 );
			if ( id > 0 ) { ds.push( id ); }
		} );
		return ds;
	}

	$( '#nntm-con-tro-them-trang' ).on( 'click', function () {
		var mauHtml = document.getElementById( 'nntm-con-tro-mau-dong-trong' ).innerHTML;
		mauHtml = mauHtml.split( '__CHI_SO__' ).join( String( demChiSo++ ) );

		var $moi = $( mauHtml );
		$bangThan.append( $moi );
		khoiTaoBangMau( $moi );
	} );

	$bangThan.on( 'click', '.nntm-con-tro-dong__xoa', function () {
		var $tr = $( this ).closest( 'tr' );
		var laDangXem = $dongDangXem && $dongDangXem.is( $tr );
		$tr.remove();
		if ( laDangXem ) {
			$dongDangXem = null;
			capNhatXemThu();
		}
	} );

	$bangThan.on( 'click', '.nntm-con-tro-dong__xem-thu', function () {
		$dongDangXem = $( this ).closest( 'tr' );
		capNhatXemThu();
	} );

	$bangThan.on( 'change', '.nntm-con-tro-dong__kieu, .nntm-con-tro-dong__do-dai', function () {
		var $tr = $( this ).closest( 'tr' );
		if ( $dongDangXem && $dongDangXem.is( $tr ) ) { capNhatXemThu(); }
	} );

	/* --- Tìm trang (REST /wp/v2/search) --- */

	function timTrang( $tr, tuKhoa ) {
		var $ketQua = $tr.find( '.nntm-con-tro-dong__ket-qua' );

		if ( ! tuKhoa || tuKhoa.length < 2 ) {
			$ketQua.hide().empty();
			return;
		}

		$ketQua.show().text( i18n.dangTim || 'Đang tìm…' );

		var url = CHU.restSearchUrl + '?search=' + encodeURIComponent( tuKhoa ) +
			'&subtype=' + encodeURIComponent( ( CHU.postTypes || [] ).join( ',' ) ) +
			'&per_page=10';

		fetch( url, {
			headers: { 'X-WP-Nonce': CHU.restNonce || '' },
			credentials: 'same-origin'
		} )
			.then( function ( r ) { return r.ok ? r.json() : []; } )
			.then( function ( ds ) {
				if ( ! ds || ! ds.length ) {
					$ketQua.text( i18n.khongThay || 'Không tìm thấy.' );
					return;
				}

				$ketQua.empty();
				var dangDung = danhSachPostIdDangDung( $tr );

				ds.forEach( function ( m ) {
					var $dong = $( '<div class="nntm-con-tro-dong__ket-qua-dong"></div>' )
						.text( m.title || ( '#' + m.id ) )
						.attr( 'data-id', m.id )
						.attr( 'data-title', m.title || '' );

					if ( dangDung.indexOf( m.id ) > -1 ) {
						$dong.addClass( 'la-dang-dung' ).append( $( '<em></em>' ).text( ' — ' + ( i18n.trungTrang || '' ) ) );
					}

					$ketQua.append( $dong );
				} );
			} )
			.catch( function () {
				$ketQua.text( i18n.khongThay || 'Không tìm thấy.' );
			} );
	}

	$bangThan.on( 'input', '.nntm-con-tro-dong__tim', function () {
		var $tr = $( this ).closest( 'tr' );
		var tuKhoa = $( this ).val();

		$tr.find( '.nntm-con-tro-dong__post-id' ).val( 0 );

		clearTimeout( thoiGianTim );
		thoiGianTim = setTimeout( function () { timTrang( $tr, tuKhoa ); }, 300 );
	} );

	$bangThan.on( 'click', '.nntm-con-tro-dong__ket-qua-dong', function () {
		var $dong = $( this );

		if ( $dong.hasClass( 'la-dang-dung' ) ) { return; }

		var $tr = $dong.closest( 'tr' );
		$tr.find( '.nntm-con-tro-dong__post-id' ).val( $dong.data( 'id' ) );
		$tr.find( '.nntm-con-tro-dong__tim' ).val( $dong.data( 'title' ) );
		$tr.find( '.nntm-con-tro-dong__ket-qua' ).hide().empty();

		if ( $dongDangXem && $dongDangXem.is( $tr ) ) { capNhatXemThu(); }
	} );

	$( document ).on( 'click', function ( e ) {
		if ( ! $( e.target ).closest( '.nntm-con-tro-dong__tim, .nntm-con-tro-dong__ket-qua' ).length ) {
			$( '.nntm-con-tro-dong__ket-qua' ).hide();
		}
	} );

	/* ==========================================================================
	 * Chặn trùng trang trước khi gửi form (kiểm phía JS — PHP kiểm lại lần nữa).
	 * ========================================================================== */

	$( 'form' ).on( 'submit', function ( e ) {
		var thay = {};
		var trung = false;

		$bangThan.find( 'tr' ).each( function () {
			var id = parseInt( $( this ).find( '.nntm-con-tro-dong__post-id' ).val(), 10 );
			if ( id > 0 ) {
				if ( thay[ id ] ) { trung = true; }
				thay[ id ] = true;
			}
		} );

		if ( trung ) {
			e.preventDefault();
			window.alert( i18n.trungTrang || 'Có trang bị trùng trong bảng.' );
		}
	} );

	capNhatXemThu();

}( window.jQuery ) );
