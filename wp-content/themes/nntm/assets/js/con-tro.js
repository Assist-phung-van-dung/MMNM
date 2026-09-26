/*
 * Hiệu ứng con trỏ chuột cho toàn site (Giao diện -> Con trỏ chuột).
 *
 * Một tệp, không build, IIFE. Đăng ký sổ HIEU_UNG (20 kiểu + tiện ích màu),
 * và một bộ máy vẽ dùng canvas 2D chạy MỘT vòng requestAnimationFrame duy
 * nhất cho mỗi phiên (mỗi lần gọi window.NNTMConTro.khoiTao() là một phiên).
 *
 * Dùng cho cả trang thật (vung = null, canvas phủ toàn màn hình, ẩn con trỏ
 * hệ thống trên <html>) LẪN khung xem thử ở màn cài đặt admin (vung = một
 * phần tử DOM, canvas chỉ phủ bên trong phần tử đó).
 *
 * Quy tắc bắt buộc (xem docs/19-con-tro-chuot.md):
 *   - Chỉ chạy khi sự kiện rê đến từ chuột (pointerType) — chạm bằng tay không chạy.
 *   - prefers-reduced-motion: reduce -> chỉ vẽ hình con trỏ, không hạt/vệt.
 *   - Ẩn con trỏ hệ thống CHỈ sau khi nhận pointermove đầu tiên.
 *   - Giữ con trỏ gõ chữ ở input/textarea/select/[contenteditable].
 *   - Một vòng rAF, dừng khi hết chuyển động + hết hạt, dừng khi tab ẩn.
 */
( function ( global ) {
	'use strict';

	if ( 'undefined' === typeof window || 'undefined' === typeof document ) {
		return;
	}

	/* ==========================================================================
	 * Hằng số
	 * ========================================================================== */

	var TRAN_HAT       = 300;   // Trần số hạt sống cùng lúc — tiết kiệm CPU.
	var LOP_AN_CON_TRO = 'nntm-con-tro-bat';
	var LOP_CANVAS     = 'nntm-con-tro-canvas';
	var LOP_VUNG       = 'nntm-con-tro-vung';

	var HE_SO_MAT_DO = { it: 0.5, vua: 1, nhieu: 1.8 };
	var HE_SO_CO     = { nho: 0.75, vua: 1, lon: 1.35 };

	/*
	 * Hình con trỏ mặc định (~14-16px) quá nhỏ để thấy rõ trên màn thật — phóng
	 * đều lên một lượt, độc lập với cài đặt "Cỡ con trỏ" (hệ số ở trên) và
	 * trạng thái "đang ở trên link/nút" (x1.3, xem tt.ty khi dựng ở vongLap).
	 */
	var TY_LE_PHONG_CON_TRO = 1.8;
	/** Hạt/vệt phóng ít hơn hình con trỏ chính — đủ đậm mà không rối mắt. */
	var TY_LE_PHONG_HAT = 1.45;

	/* ==========================================================================
	 * Tiện ích màu: hex -> rgb, rgba(alpha), pha sáng/tối
	 * ========================================================================== */

	function docBienCss( ten, duPhong ) {
		try {
			var gt = getComputedStyle( document.documentElement ).getPropertyValue( ten );
			gt = gt ? gt.trim() : '';
			return gt || duPhong;
		} catch ( loi ) {
			return duPhong;
		}
	}

	function hexRaRgb( hex ) {
		hex = ( hex || '' ).replace( '#', '' ).trim();

		if ( 3 === hex.length ) {
			hex = hex.split( '' ).map( function ( k ) { return k + k; } ).join( '' );
		}

		var so = parseInt( hex, 16 );

		if ( isNaN( so ) || 6 !== hex.length ) {
			return { r: 212, g: 175, b: 55 }; // vàng nghệ — không bao giờ nên tới đây.
		}

		return { r: ( so >> 16 ) & 255, g: ( so >> 8 ) & 255, b: so & 255 };
	}

	function rgba( hex, alpha ) {
		var m = hexRaRgb( hex );
		return 'rgba(' + m.r + ',' + m.g + ',' + m.b + ',' + alpha + ')';
	}

	function so2Hex( so ) {
		var s = Math.max( 0, Math.min( 255, Math.round( so ) ) ).toString( 16 );
		return 1 === s.length ? '0' + s : s;
	}

	function phaMau( hexGoc, hexDich, tiLe ) {
		var a = hexRaRgb( hexGoc );
		var b = hexRaRgb( hexDich );

		return '#' +
			so2Hex( a.r + ( b.r - a.r ) * tiLe ) +
			so2Hex( a.g + ( b.g - a.g ) * tiLe ) +
			so2Hex( a.b + ( b.b - a.b ) * tiLe );
	}

	function phaSang( hex, phanTram ) {
		return phaMau( hex, '#FFFFFF', phanTram / 100 );
	}

	function phaToi( hex, phanTram ) {
		return phaMau( hex, '#000000', phanTram / 100 );
	}

	function laHex( gt ) {
		return 'string' === typeof gt && /^#[0-9a-fA-F]{6}$/.test( gt.trim() );
	}

	/** Độ sáng cảm nhận (0 = đen, 1 = trắng) — dùng để tự thích nghi theo nền. */
	function doSang( hex ) {
		var m = hexRaRgb( hex );
		return ( 0.299 * m.r + 0.587 * m.g + 0.114 * m.b ) / 255;
	}

	/* ==========================================================================
	 * Bảng màu mặc định theo từng kiểu — [biến-css, dự-phòng-hex].
	 * ========================================================================== */

	var MAU_MAC_DINH = {
		'mat-troi':        [ '--nntm-vang-nghe', '#D4AF37' ],
		'hoa-sen':         [ '--nntm-hong-dao', '#E9B9A5' ],
		'dom-sang':        [ '--nntm-tai-vang-nhat', '#EAD79B' ],
		'hao-quang':       [ '--nntm-vang-nghe', '#D4AF37' ],
		'sao-choi':        [ '--nntm-vang-nghe', '#D4AF37' ],
		'gon-nuoc':        [ '--nntm-reu', '#747766' ],
		'dom-dom':         [ '--nntm-tai-vang-nhat', '#EAD79B' ],
		'trang-khuyet':    [ '--nntm-nga', '#F0EEE9' ],
		'banh-xe-phap':    [ '--nntm-vang-nghe', '#D4AF37' ],
		'la-bo-de':        [ '--nntm-reu', '#747766' ],
		'ngon-nen':        [ '--nntm-dao', '#FEBE98' ],
		'khoi-huong':      [ '--nntm-reu', '#747766' ],
		'chuoi-hat':       [ '--nntm-nau-dat', '#A47764' ],
		'bui-vang':        [ '--nntm-vang-nghe', '#D4AF37' ],
		'vien-tron-thien': [ '--nntm-muc', '#3F3B3B' ],
		'net-muc':         [ '--nntm-muc', '#3F3B3B' ],
		'bong-bong':       [ '--nntm-trang-lanh', '#FCFDFE' ],
		'hoa-mai':         [ '--nntm-tai-vang', '#DEC378' ],
		'sao-bang':        [ '--nntm-tai-nhat-1', '#FFF8D9' ],
		'cham-vong':       [ '--nntm-reu', '#747766' ]
	};

	/*
	 * Màu phụ riêng cho vài kiểu cần một tông khác hẳn (không phải pha sáng của
	 * màu chính) — vd khói hương cần lõi đỏ trong khi vệt khói thì xám.
	 */
	var MAU_PHU_RIENG = {
		'hoa-sen':    [ '--nntm-vang-nghe', '#D4AF37' ],
		'khoi-huong': [ '--nntm-do-tham', '#8B1E2D' ],
		'la-bo-de':   [ '--nntm-tai-vang', '#DEC378' ],
		'bong-bong':  [ '--nntm-cham', '#1F4E79' ],
		'cham-vong':  [ '--nntm-kem', '#F7F1DE' ],
		'dom-dom':    [ '--nntm-reu', '#747766' ]
	};

	function layMauKieu( kieu, mauTuyChinh, mauPhuTuyChinh ) {
		var mac_dinh = MAU_MAC_DINH[ kieu ] || MAU_MAC_DINH[ 'mat-troi' ];
		var chinh    = laHex( mauTuyChinh ) ? mauTuyChinh : docBienCss( mac_dinh[ 0 ], mac_dinh[ 1 ] );

		var phu;
		if ( laHex( mauPhuTuyChinh ) ) {
			phu = mauPhuTuyChinh;
		} else if ( laHex( mauTuyChinh ) ) {
			// Có màu chính tuỳ chỉnh nhưng không có màu phụ -> pha sáng ~35% về trắng.
			phu = phaSang( mauTuyChinh, 35 );
		} else {
			var rieng = MAU_PHU_RIENG[ kieu ];
			phu = rieng ? docBienCss( rieng[ 0 ], rieng[ 1 ] ) : phaSang( chinh, 35 );
		}

		return { chinh: chinh, phu: phu };
	}

	/* ==========================================================================
	 * Vẽ hình học dùng chung
	 * ========================================================================== */

	function veTron( g, x, y, r, mau ) {
		g.beginPath();
		g.arc( x, y, Math.max( 0.1, r ), 0, Math.PI * 2 );
		g.fillStyle = mau;
		g.fill();
	}

	function veVienToi( g, x, y, r, alpha ) {
		g.beginPath();
		g.arc( x, y, Math.max( 0.1, r ), 0, Math.PI * 2 );
		g.strokeStyle = 'rgba(63,59,59,' + alpha + ')';
		g.lineWidth = 1;
		g.stroke();
	}

	/** Vẽ ngôi sao / tia N cánh — dùng cho mặt trời, đốm sáng, bánh xe pháp. */
	function veTia( g, x, y, soTia, rNgoai, rTrong, goc, mauTia, doDay ) {
		g.save();
		g.translate( x, y );
		g.rotate( goc );
		g.strokeStyle = mauTia;
		g.lineWidth = doDay || 2;
		g.lineCap = 'round';

		for ( var i = 0; i < soTia; i++ ) {
			var a = ( Math.PI * 2 / soTia ) * i;
			g.beginPath();
			g.moveTo( Math.cos( a ) * rTrong, Math.sin( a ) * rTrong );
			g.lineTo( Math.cos( a ) * rNgoai, Math.sin( a ) * rNgoai );
			g.stroke();
		}

		g.restore();
	}

	/** Một cánh hoa hình giọt nước, hướng ra từ tâm theo góc `goc`. */
	function veCanhHoa( g, cx, cy, dai, rong, goc, mau ) {
		g.save();
		g.translate( cx, cy );
		g.rotate( goc );
		g.beginPath();
		g.moveTo( 0, 0 );
		g.quadraticCurveTo( rong, dai * 0.35, 0, dai );
		g.quadraticCurveTo( -rong, dai * 0.35, 0, 0 );
		g.closePath();
		g.fillStyle = mau;
		g.fill();
		g.restore();
	}

	/** Lá bồ đề: hình tim thuôn có đuôi nhọn + gân giữa. */
	function veLaBoDe( g, cx, cy, kichThuoc, goc, mau, mauGan ) {
		g.save();
		g.translate( cx, cy );
		g.rotate( goc );

		var s = kichThuoc;
		g.beginPath();
		g.moveTo( 0, -s * 1.3 );
		g.bezierCurveTo( s, -s * 0.6, s * 0.9, s * 0.5, 0, s * 0.75 );
		g.bezierCurveTo( -s * 0.9, s * 0.5, -s, -s * 0.6, 0, -s * 1.3 );
		g.closePath();
		g.fillStyle = mau;
		g.fill();

		g.beginPath();
		g.moveTo( 0, -s * 1.1 );
		g.lineTo( 0, s * 0.6 );
		g.strokeStyle = mauGan;
		g.lineWidth = Math.max( 0.5, s * 0.08 );
		g.stroke();

		g.restore();
	}

	function easeOutCubic( t ) {
		return 1 - Math.pow( 1 - t, 3 );
	}

	/* ==========================================================================
	 * Sổ đăng ký hiệu ứng.
	 *
	 * Mỗi kiểu triển khai một tập con các hàm:
	 *   veConTro( g, x, y, tt )      — vẽ hình con trỏ tại đầu ngón.
	 *   phatKhiDi( ds, x, y, vx, vy, heSoMatDo, tt ) — phát hạt khi rê chuột.
	 *   phatKhiNhap( ds, x, y, tt )  — phát hạt khi bấm chuột (pointerdown).
	 *   veHat( g, h, tt )            — vẽ một hạt.
	 *   capNhatHat( h, dt )          — cập nhật vật lý riêng của hạt (tuỳ chọn).
	 *
	 * tt (trạng thái) = { mau, mauPhu, t, giamChuyenDong, banBam, treClickable, dt }
	 * ========================================================================== */

	function taoHatMacDinh( x, y, vx, vy, doiSong, size ) {
		return {
			x: x, y: y, vx: vx, vy: vy,
			tuoi: 0, doiSong: doiSong,
			size: size,
			goc: Math.random() * Math.PI * 2,
			data: {}
		};
	}

	function capNhatHatMacDinh( h, dt ) {
		h.x += h.vx * dt;
		h.y += h.vy * dt;
		h.tuoi += dt;
	}

	function capNhatHatRoi( h, dt ) {
		// Có trọng lực nhẹ — dùng cho bụi vàng / lá / cánh hoa rơi.
		h.vy += 40 * dt;
		capNhatHatMacDinh( h, dt );
	}

	function veHatTronMem( g, h, tt ) {
		var tiLe = 1 - h.tuoi / h.doiSong;
		if ( tiLe <= 0 ) { return; }

		var r = h.size * tiLe;
		var mau = h.data.mau || tt.mau;

		g.save();
		g.globalAlpha = Math.max( 0, tiLe ) * 0.9;
		veTron( g, h.x, h.y, r, mau );
		g.restore();
	}

	function phatHatToaTron( ds, x, y, so, tocDo, doDaiVet, mau, kichThuoc ) {
		for ( var i = 0; i < so; i++ ) {
			if ( ds.length >= TRAN_HAT ) { break; }
			var a = Math.random() * Math.PI * 2;
			var v = tocDo * ( 0.4 + Math.random() * 0.6 );
			var h = taoHatMacDinh(
				x, y,
				Math.cos( a ) * v, Math.sin( a ) * v,
				( doDaiVet / 30 ) * ( 0.5 + Math.random() * 0.6 ),
				kichThuoc * ( 0.6 + Math.random() * 0.6 )
			);
			h.data.mau = mau;
			ds.push( h );
		}
	}

	var HIEU_UNG = {};

	/* --- 1. Mặt trời ---------------------------------------------------- */
	HIEU_UNG[ 'mat-troi' ] = {
		veConTro: function ( g, x, y, tt ) {
			var r = 7 * tt.ty;
			veTia( g, x, y, 12, r + 9 * tt.ty, r + 3 * tt.ty, tt.t * 0.6, rgba( tt.mau, 0.85 ), 2 );
			veTron( g, x, y, r, tt.mau );
			veVienToi( g, x, y, r, 0.5 );
		},
		phatKhiDi: function ( ds, x, y, vx, vy, heSo, tt ) {
			if ( tt.giamChuyenDong ) { return; }
			var so = Math.round( heSo * ( 0.5 + Math.min( 2, Math.hypot( vx, vy ) / 300 ) ) );
			phatHatToaTron( ds, x, y, so, 25, tt.doDai, tt.mau, 3 );
		},
		phatKhiNhap: function ( ds, x, y, tt ) {
			phatHatToaTron( ds, x, y, Math.round( 14 * tt.heSoMatDo ), 90, tt.doDai, tt.mauPhu, 3 );
		},
		veHat: veHatTronMem,
		capNhatHat: capNhatHatMacDinh
	};

	/* --- 2. Hoa sen ------------------------------------------------------- */
	HIEU_UNG[ 'hoa-sen' ] = {
		veConTro: function ( g, x, y, tt ) {
			var r = 6 * tt.ty;
			for ( var i = 0; i < 6; i++ ) {
				veCanhHoa( g, x, y, r * 2.4, r * 1.1, tt.t * 0.25 + ( Math.PI * 2 / 6 ) * i, rgba( tt.mau, 0.85 ) );
			}
			veTron( g, x, y, r * 0.6, tt.mauPhu );
			veVienToi( g, x, y, r * 0.6, 0.45 );
		},
		phatKhiDi: function ( ds, x, y, vx, vy, heSo, tt ) {
			if ( tt.giamChuyenDong || Math.hypot( vx, vy ) < 40 ) { return; }
			if ( Math.random() > 0.5 ) { return; }
			if ( ds.length >= TRAN_HAT ) { return; }
			var h = taoHatMacDinh( x, y, ( Math.random() - 0.5 ) * 20, 25 + Math.random() * 20, tt.doDai / 22, 4 );
			h.data.mau = tt.mau;
			h.rotSpeed = ( Math.random() - 0.5 ) * 4;
			ds.push( h );
		},
		phatKhiNhap: function ( ds, x, y, tt ) {
			for ( var i = 0; i < 6; i++ ) {
				if ( ds.length >= TRAN_HAT ) { break; }
				var h = taoHatMacDinh( x, y, 0, 0, tt.doDai / 30, 3.5 );
				h.data.mau = tt.mauPhu;
				h.data.goc = ( Math.PI * 2 / 6 ) * i;
				h.data.la_canh = true;
				ds.push( h );
			}
		},
		veHat: function ( g, h, tt ) {
			var tiLe = 1 - h.tuoi / h.doiSong;
			if ( tiLe <= 0 ) { return; }
			g.save();
			g.globalAlpha = tiLe;
			if ( h.data.la_canh ) {
				var d = 16 * ( 1 - tiLe );
				veCanhHoa( g, h.x + Math.cos( h.data.goc ) * d, h.y + Math.sin( h.data.goc ) * d, h.size * 2, h.size, h.data.goc + h.tuoi, h.data.mau );
			} else {
				h.goc += ( h.rotSpeed || 0 ) * 0.05;
				veCanhHoa( g, h.x, h.y, h.size * 1.6, h.size * 0.8, h.goc, h.data.mau );
			}
			g.restore();
		},
		capNhatHat: capNhatHatRoi
	};

	/* --- 3. Đốm sáng (sao 4 cánh) ----------------------------------------- */
	HIEU_UNG[ 'dom-sang' ] = {
		veConTro: function ( g, x, y, tt ) {
			var r = 6 * tt.ty;
			veTia( g, x, y, 4, r + 8 * tt.ty, 0, tt.t * 0.8, rgba( tt.mau, 0.9 ), 2.4 );
			veTron( g, x, y, r * 0.55, '#FFFFFF' );
			veVienToi( g, x, y, r * 0.55, 0.4 );
		},
		phatKhiDi: function ( ds, x, y, vx, vy, heSo, tt ) {
			if ( tt.giamChuyenDong || Math.random() > 0.6 ) { return; }
			var so = Math.round( heSo );
			for ( var i = 0; i < so; i++ ) {
				if ( ds.length >= TRAN_HAT ) { break; }
				var h = taoHatMacDinh( x + ( Math.random() - 0.5 ) * 24, y + ( Math.random() - 0.5 ) * 24, 0, 0, tt.doDai / 24, 2.4 );
				h.data.mau = tt.mau;
				h.data.nhapNhay = Math.random() * Math.PI * 2;
				ds.push( h );
			}
		},
		phatKhiNhap: function ( ds, x, y, tt ) {
			phatHatToaTron( ds, x, y, Math.round( 10 * tt.heSoMatDo ), 60, tt.doDai, tt.mauPhu, 2.2 );
		},
		veHat: function ( g, h, tt ) {
			var tiLe = 1 - h.tuoi / h.doiSong;
			if ( tiLe <= 0 ) { return; }
			var nhap = 0.4 + 0.6 * Math.abs( Math.sin( ( h.data.nhapNhay || 0 ) + h.tuoi * 10 ) );
			g.save();
			g.globalAlpha = tiLe * nhap;
			veTia( g, h.x, h.y, 4, h.size * 2, 0, 0, h.data.mau, 1.2 );
			g.restore();
		},
		capNhatHat: capNhatHatMacDinh
	};

	/* --- 4. Hào quang ------------------------------------------------------ */
	HIEU_UNG[ 'hao-quang' ] = {
		veConTro: function ( g, x, y, tt, viTriTre ) {
			var r = 6 * tt.ty;
			veTron( g, x, y, r, tt.mau );
			veVienToi( g, x, y, r, 0.5 );
			if ( viTriTre ) {
				g.save();
				g.globalAlpha = 0.55;
				g.beginPath();
				g.arc( viTriTre.x, viTriTre.y, r + 10 * tt.ty, 0, Math.PI * 2 );
				g.strokeStyle = rgba( tt.mauPhu, 0.8 );
				g.lineWidth = 2;
				g.stroke();
				g.restore();
			}
		},
		phatKhiDi: function () {},
		phatKhiNhap: function ( ds, x, y, tt ) {
			phatHatToaTron( ds, x, y, Math.round( 10 * tt.heSoMatDo ), 40, tt.doDai, tt.mauPhu, 3 );
		},
		veHat: veHatTronMem,
		capNhatHat: capNhatHatMacDinh,
		canViTriTre: true
	};

	/* --- 5. Sao chổi (vệt polyline mờ dần) --------------------------------- */
	HIEU_UNG[ 'sao-choi' ] = {
		veConTro: function ( g, x, y, tt ) {
			veTron( g, x, y, 5 * tt.ty, tt.mau );
			veVienToi( g, x, y, 5 * tt.ty, 0.5 );
		},
		veDuongDi: function ( g, duongDi, tt ) {
			if ( tt.giamChuyenDong || duongDi.length < 2 ) { return; }
			g.save();
			for ( var i = 1; i < duongDi.length; i++ ) {
				var tiLe = i / duongDi.length;
				g.globalAlpha = tiLe * 0.6;
				g.lineWidth = Math.max( 0.5, tt.doDai / 12 * tiLe );
				g.strokeStyle = tiLe > 0.6 ? tt.mau : tt.mauPhu;
				g.beginPath();
				g.moveTo( duongDi[ i - 1 ].x, duongDi[ i - 1 ].y );
				g.lineTo( duongDi[ i ].x, duongDi[ i ].y );
				g.stroke();
			}
			g.restore();
		},
		phatKhiDi: function () {},
		phatKhiNhap: function ( ds, x, y, tt ) {
			phatHatToaTron( ds, x, y, Math.round( 8 * tt.heSoMatDo ), 70, tt.doDai, tt.mau, 2.5 );
		},
		veHat: veHatTronMem,
		capNhatHat: capNhatHatMacDinh
	};

	/* --- 6. Gợn nước (vòng mở rộng) ----------------------------------------- */
	HIEU_UNG[ 'gon-nuoc' ] = {
		veConTro: function ( g, x, y, tt ) {
			veTron( g, x, y, 5 * tt.ty, tt.mau );
			veVienToi( g, x, y, 5 * tt.ty, 0.4 );
		},
		phatKhiDi: function ( ds, x, y, vx, vy, heSo, tt ) {
			if ( tt.giamChuyenDong || Math.hypot( vx, vy ) < 60 || Math.random() > 0.3 * heSo ) { return; }
			if ( ds.length >= TRAN_HAT ) { return; }
			var h = taoHatMacDinh( x, y, 0, 0, tt.doDai / 16, 4 );
			h.data.mau = tt.mau;
			h.data.gon = true;
			ds.push( h );
		},
		phatKhiNhap: function ( ds, x, y, tt ) {
			var h = taoHatMacDinh( x, y, 0, 0, tt.doDai / 10, 4 );
			h.data.mau = tt.mauPhu;
			h.data.gon = true;
			ds.push( h );
		},
		veHat: function ( g, h, tt ) {
			var tiLe = h.tuoi / h.doiSong;
			if ( tiLe >= 1 ) { return; }
			g.save();
			g.globalAlpha = ( 1 - tiLe ) * 0.7;
			g.lineWidth = 1.5;
			g.strokeStyle = h.data.mau;
			g.beginPath();
			g.arc( h.x, h.y, h.size + tiLe * 26, 0, Math.PI * 2 );
			g.stroke();
			g.restore();
		},
		capNhatHat: capNhatHatMacDinh
	};

	/* --- 7. Đom đóm (bay lượn quanh con trỏ) --------------------------------- */
	HIEU_UNG[ 'dom-dom' ] = {
		veConTro: function ( g, x, y, tt ) {
			veTron( g, x, y, 4 * tt.ty, tt.mau );
			veVienToi( g, x, y, 4 * tt.ty, 0.4 );
		},
		phatKhiDi: function ( ds, x, y, vx, vy, heSo, tt, dem ) {
			var muc = Math.round( 6 * heSo );
			var conThieu = muc - dem.domDom;
			if ( conThieu <= 0 || ds.length >= TRAN_HAT ) { return; }
			var h = taoHatMacDinh( x + ( Math.random() - 0.5 ) * 40, y + ( Math.random() - 0.5 ) * 40, 0, 0, 999, 2.4 );
			h.data.mau = tt.mauPhu;
			h.data.goc = Math.random() * Math.PI * 2;
			h.data.banKinh = 18 + Math.random() * 22;
			h.data.tocDoGoc = ( Math.random() < 0.5 ? -1 : 1 ) * ( 0.8 + Math.random() );
			h.data.pha = Math.random() * Math.PI * 2;
			h.data.domDom = true;
			ds.push( h );
			dem.domDom++;
		},
		phatKhiNhap: function () {},
		veHat: function ( g, h, tt, tam ) {
			if ( tt.giamChuyenDong ) { return; }
			var nhap = 0.3 + 0.7 * Math.abs( Math.sin( h.data.pha + h.tuoi * 3 ) );
			g.save();
			g.globalAlpha = nhap;
			veTron( g, h.x, h.y, h.size, h.data.mau );
			g.restore();
		},
		capNhatHat: function ( h, dt, tam ) {
			h.goc = h.data.goc += h.data.tocDoGoc * dt;
			h.x = tam.x + Math.cos( h.data.goc ) * h.data.banKinh;
			h.y = tam.y + Math.sin( h.data.goc ) * h.data.banKinh;
		},
		theoTam: true,
		demRieng: 'domDom'
	};

	/* --- 8. Trăng khuyết ---------------------------------------------------- */
	HIEU_UNG[ 'trang-khuyet' ] = {
		veConTro: function ( g, x, y, tt ) {
			var r = 8 * tt.ty;
			g.save();
			g.beginPath();
			g.arc( x, y, r, 0, Math.PI * 2 );
			g.fillStyle = tt.mau;
			g.fill();
			g.globalCompositeOperation = 'destination-out';
			g.beginPath();
			g.arc( x + r * 0.55, y - r * 0.25, r * 0.92, 0, Math.PI * 2 );
			g.fill();
			g.restore();
			/*
			 * Màu mặc định của kiểu này là trắng ngà — gần trùng nền kem của
			 * khung xem thử/site. Viền mờ thường (veVienToi) không đủ nổi,
			 * nên vẽ thêm một nét viền RIÊNG đậm và dày hơn cho kiểu này.
			 */
			g.save();
			g.beginPath();
			g.arc( x, y, r, 0, Math.PI * 2 );
			g.strokeStyle = 'rgba(63,59,59,0.85)';
			g.lineWidth = 1.4;
			g.stroke();
			g.restore();
		},
		phatKhiDi: function ( ds, x, y, vx, vy, heSo, tt ) {
			if ( tt.giamChuyenDong || Math.hypot( vx, vy ) < 30 ) { return; }
			if ( ds.length >= TRAN_HAT ) { return; }
			var h = taoHatMacDinh( x, y, 0, 0, tt.doDai / 20, 2 );
			h.data.mau = tt.mauPhu;
			ds.push( h );
		},
		phatKhiNhap: function ( ds, x, y, tt ) {
			phatHatToaTron( ds, x, y, Math.round( 8 * tt.heSoMatDo ), 30, tt.doDai, tt.mauPhu, 2 );
		},
		veHat: veHatTronMem,
		capNhatHat: capNhatHatMacDinh
	};

	/* --- 9. Bánh xe pháp ----------------------------------------------------- */
	HIEU_UNG[ 'banh-xe-phap' ] = {
		veConTro: function ( g, x, y, tt ) {
			var r = 8 * tt.ty;
			g.save();
			g.beginPath();
			g.arc( x, y, r, 0, Math.PI * 2 );
			g.strokeStyle = tt.mau;
			g.lineWidth = 1.6;
			g.stroke();
			g.restore();
			veTia( g, x, y, 8, r, 1, tt.t * 0.4, tt.mau, 1.6 );
			veTron( g, x, y, 1.6, tt.mau );
			veVienToi( g, x, y, r, 0.35 );
		},
		phatKhiDi: function ( ds, x, y, vx, vy, heSo, tt ) {
			if ( tt.giamChuyenDong || Math.hypot( vx, vy ) < 50 || Math.random() > 0.4 * heSo ) { return; }
			if ( ds.length >= TRAN_HAT ) { return; }
			var h = taoHatMacDinh( x, y, 0, 0, tt.doDai / 20, 3 );
			h.data.mau = tt.mau;
			h.data.gon = true;
			ds.push( h );
		},
		phatKhiNhap: function ( ds, x, y, tt ) {
			var h = taoHatMacDinh( x, y, 0, 0, tt.doDai / 12, 4 );
			h.data.mau = tt.mauPhu;
			h.data.gon = true;
			ds.push( h );
		},
		veHat: function ( g, h, tt ) {
			var tiLe = h.tuoi / h.doiSong;
			if ( tiLe >= 1 ) { return; }
			g.save();
			g.globalAlpha = ( 1 - tiLe ) * 0.7;
			g.lineWidth = 1.2;
			g.strokeStyle = h.data.mau;
			g.beginPath();
			g.arc( h.x, h.y, h.size + tiLe * 14, 0, Math.PI * 2 );
			g.stroke();
			g.restore();
		},
		capNhatHat: capNhatHatMacDinh
	};

	/* --- 10. Lá bồ đề -------------------------------------------------------- */
	HIEU_UNG[ 'la-bo-de' ] = {
		veConTro: function ( g, x, y, tt ) {
			veLaBoDe( g, x, y, 7 * tt.ty, tt.t * 0.3, rgba( tt.mau, 0.9 ), rgba( tt.mauPhu, 0.8 ) );
		},
		phatKhiDi: function ( ds, x, y, vx, vy, heSo, tt ) {
			if ( tt.giamChuyenDong || Math.hypot( vx, vy ) < 50 || Math.random() > 0.35 * heSo ) { return; }
			if ( ds.length >= TRAN_HAT ) { return; }
			var h = taoHatMacDinh( x, y, ( Math.random() - 0.5 ) * 16, 18 + Math.random() * 16, tt.doDai / 15, 3.5 );
			h.rotSpeed = ( Math.random() - 0.5 ) * 3;
			ds.push( h );
		},
		phatKhiNhap: function ( ds, x, y, tt ) {
			for ( var i = 0; i < 4; i++ ) {
				if ( ds.length >= TRAN_HAT ) { break; }
				var a = Math.random() * Math.PI * 2;
				var h = taoHatMacDinh( x, y, Math.cos( a ) * 40, Math.sin( a ) * 40 - 10, tt.doDai / 18, 4 );
				h.rotSpeed = ( Math.random() - 0.5 ) * 4;
				ds.push( h );
			}
		},
		veHat: function ( g, h, tt ) {
			var tiLe = 1 - h.tuoi / h.doiSong;
			if ( tiLe <= 0 ) { return; }
			h.goc += ( h.rotSpeed || 1 ) * 0.05;
			g.save();
			g.globalAlpha = tiLe;
			veLaBoDe( g, h.x, h.y, h.size, h.goc, rgba( tt.mau, 0.85 ), rgba( tt.mauPhu, 0.75 ) );
			g.restore();
		},
		capNhatHat: capNhatHatRoi
	};

	/* --- 11. Ngọn nến -------------------------------------------------------- */
	HIEU_UNG[ 'ngon-nen' ] = {
		veConTro: function ( g, x, y, tt ) {
			var dao = Math.sin( tt.t * 9 ) * 1.6;
			g.save();
			g.translate( x, y );
			g.beginPath();
			g.moveTo( -3.4, 4 );
			g.quadraticCurveTo( -5 + dao, -4, 0 + dao * 0.6, -12 );
			g.quadraticCurveTo( 5 + dao, -4, 3.4, 4 );
			g.closePath();
			var grad = g.createLinearGradient( 0, 4, 0, -12 );
			grad.addColorStop( 0, tt.mauPhu );
			grad.addColorStop( 1, tt.mau );
			g.fillStyle = grad;
			g.fill();
			g.strokeStyle = 'rgba(63,59,59,0.5)';
			g.lineWidth = 0.8;
			g.stroke();
			g.restore();
		},
		phatKhiDi: function ( ds, x, y, vx, vy, heSo, tt ) {
			if ( tt.giamChuyenDong || Math.random() > 0.4 * heSo ) { return; }
			if ( ds.length >= TRAN_HAT ) { return; }
			var h = taoHatMacDinh( x + ( Math.random() - 0.5 ) * 6, y - 8, ( Math.random() - 0.5 ) * 8, -30 - Math.random() * 20, tt.doDai / 18, 2 );
			h.data.mau = tt.mau;
			ds.push( h );
		},
		phatKhiNhap: function ( ds, x, y, tt ) {
			phatHatToaTron( ds, x, y - 8, Math.round( 8 * tt.heSoMatDo ), 40, tt.doDai, tt.mauPhu, 2 );
		},
		veHat: veHatTronMem,
		capNhatHat: capNhatHatMacDinh
	};

	/* --- 12. Khói hương -------------------------------------------------------- */
	HIEU_UNG[ 'khoi-huong' ] = {
		veConTro: function ( g, x, y, tt ) {
			veTron( g, x, y, 3.4 * tt.ty, tt.mauPhu );
			veVienToi( g, x, y, 3.4 * tt.ty, 0.5 );
		},
		phatKhiDi: function ( ds, x, y, vx, vy, heSo, tt ) {
			if ( tt.giamChuyenDong || Math.random() > 0.5 * heSo ) { return; }
			if ( ds.length >= TRAN_HAT ) { return; }
			var h = taoHatMacDinh( x, y - 6, ( Math.random() - 0.5 ) * 6, -14 - Math.random() * 8, tt.doDai / 10, 3 );
			h.data.uon = Math.random() * Math.PI * 2;
			ds.push( h );
		},
		phatKhiNhap: function ( ds, x, y, tt ) {
			for ( var i = 0; i < Math.round( 3 * tt.heSoMatDo ); i++ ) {
				if ( ds.length >= TRAN_HAT ) { break; }
				var h = taoHatMacDinh( x, y, ( Math.random() - 0.5 ) * 10, -20, tt.doDai / 8, 4 );
				h.data.uon = Math.random() * Math.PI * 2;
				ds.push( h );
			}
		},
		veHat: function ( g, h, tt ) {
			var tiLe = 1 - h.tuoi / h.doiSong;
			if ( tiLe <= 0 ) { return; }
			var lech = Math.sin( h.data.uon + h.tuoi * 3 ) * 10 * ( h.tuoi );
			g.save();
			g.globalAlpha = tiLe * 0.5;
			veTron( g, h.x + lech, h.y, h.size * ( 1 + h.tuoi ), tt.mau );
			g.restore();
		},
		capNhatHat: capNhatHatMacDinh
	};

	/* --- 13. Chuỗi hạt (lò xo bám theo) --------------------------------------- */
	HIEU_UNG[ 'chuoi-hat' ] = {
		soLuongChuoi: 12,
		veConTro: function ( g, x, y, tt ) {
			veTron( g, x, y, 4.4 * tt.ty, tt.mau );
			veVienToi( g, x, y, 4.4 * tt.ty, 0.5 );
		},
		veHatChuoi: function ( g, chuoi, tt ) {
			for ( var i = 0; i < chuoi.length; i++ ) {
				var tiLe = 1 - i / chuoi.length;
				g.save();
				g.globalAlpha = 0.55 + tiLe * 0.35;
				veTron( g, chuoi[ i ].x, chuoi[ i ].y, 3.6 * tiLe + 1.4, i % 2 ? tt.mau : tt.mauPhu );
				g.restore();
			}
		},
		phatKhiDi: function () {},
		phatKhiNhap: function ( ds, x, y, tt ) {
			phatHatToaTron( ds, x, y, Math.round( 6 * tt.heSoMatDo ), 40, tt.doDai, tt.mauPhu, 2.5 );
		},
		veHat: veHatTronMem,
		capNhatHat: capNhatHatMacDinh
	};

	/* --- 14. Bụi vàng ---------------------------------------------------------- */
	HIEU_UNG[ 'bui-vang' ] = {
		veConTro: function ( g, x, y, tt ) {
			veTron( g, x, y, 3.6 * tt.ty, tt.mau );
			veVienToi( g, x, y, 3.6 * tt.ty, 0.5 );
		},
		phatKhiDi: function ( ds, x, y, vx, vy, heSo, tt ) {
			if ( tt.giamChuyenDong ) { return; }
			var so = Math.round( heSo * ( 0.5 + Math.min( 2, Math.hypot( vx, vy ) / 250 ) ) );
			for ( var i = 0; i < so; i++ ) {
				if ( ds.length >= TRAN_HAT ) { break; }
				var h = taoHatMacDinh( x + ( Math.random() - 0.5 ) * 10, y, ( Math.random() - 0.5 ) * 16, Math.random() * 10, tt.doDai / 16, 1.8 );
				h.data.mau = Math.random() > 0.5 ? tt.mau : tt.mauPhu;
				ds.push( h );
			}
		},
		phatKhiNhap: function ( ds, x, y, tt ) {
			phatHatToaTron( ds, x, y, Math.round( 16 * tt.heSoMatDo ), 60, tt.doDai, tt.mauPhu, 2 );
		},
		veHat: veHatTronMem,
		capNhatHat: capNhatHatRoi
	};

	/* --- 15. Viền tròn thiền (Ensō) -------------------------------------------- */
	HIEU_UNG[ 'vien-tron-thien' ] = {
		veConTro: function ( g, x, y, tt ) {
			var r = 9 * tt.ty;
			g.save();
			g.translate( x, y );
			g.rotate( tt.t * 0.35 );
			g.beginPath();
			g.arc( 0, 0, r, 0.35, Math.PI * 2 - 0.15 );
			g.strokeStyle = tt.mau;
			g.lineWidth = 2.2;
			g.lineCap = 'round';
			g.stroke();
			g.restore();
			veTron( g, x, y, 1.6, tt.mau );
		},
		phatKhiDi: function ( ds, x, y, vx, vy, heSo, tt ) {
			if ( tt.giamChuyenDong || Math.hypot( vx, vy ) < 60 || Math.random() > 0.3 * heSo ) { return; }
			if ( ds.length >= TRAN_HAT ) { return; }
			var h = taoHatMacDinh( x, y, 0, 0, tt.doDai / 18, 2 );
			h.data.mau = tt.mau;
			ds.push( h );
		},
		phatKhiNhap: function ( ds, x, y, tt ) {
			var h = taoHatMacDinh( x, y, 0, 0, tt.doDai / 10, 4 );
			h.data.mau = tt.mau;
			h.data.gon = true;
			ds.push( h );
		},
		veHat: function ( g, h, tt ) {
			var tiLe = h.tuoi / h.doiSong;
			if ( tiLe >= 1 ) { return; }
			g.save();
			g.globalAlpha = ( 1 - tiLe ) * 0.5;
			g.lineWidth = 1;
			g.strokeStyle = h.data.mau;
			g.beginPath();
			g.arc( h.x, h.y, h.size + tiLe * 16, 0, Math.PI * 2 );
			g.stroke();
			g.restore();
		},
		capNhatHat: capNhatHatMacDinh
	};

	/* --- 16. Nét mực (bút lông) ------------------------------------------------- */
	HIEU_UNG[ 'net-muc' ] = {
		veConTro: function ( g, x, y, tt ) {
			veTron( g, x, y, 3.2 * tt.ty, tt.mau );
		},
		veDuongDi: function ( g, duongDi, tt, tocDo ) {
			if ( tt.giamChuyenDong || duongDi.length < 2 ) { return; }
			var nhanh = Math.min( 1, tocDo / 500 );
			g.save();
			for ( var i = 1; i < duongDi.length; i++ ) {
				var tiLe = i / duongDi.length;
				g.globalAlpha = tiLe * 0.5;
				g.lineWidth = Math.max( 0.6, ( tt.doDai / 10 ) * ( 1 - nhanh ) * tiLe + 0.6 );
				g.strokeStyle = tt.mau;
				g.beginPath();
				g.moveTo( duongDi[ i - 1 ].x, duongDi[ i - 1 ].y );
				g.lineTo( duongDi[ i ].x, duongDi[ i ].y );
				g.stroke();
			}
			g.restore();
		},
		phatKhiDi: function () {},
		phatKhiNhap: function ( ds, x, y, tt ) {
			phatHatToaTron( ds, x, y, Math.round( 6 * tt.heSoMatDo ), 30, tt.doDai, tt.mau, 2 );
		},
		veHat: veHatTronMem,
		capNhatHat: capNhatHatMacDinh
	};

	/* --- 17. Bong bóng ------------------------------------------------------------ */
	HIEU_UNG[ 'bong-bong' ] = {
		veConTro: function ( g, x, y, tt ) {
			veTron( g, x, y, 4.4 * tt.ty, rgba( tt.mau, 0.35 ) );
			g.save();
			g.beginPath();
			g.arc( x, y, 4.4 * tt.ty, 0, Math.PI * 2 );
			g.strokeStyle = rgba( tt.mauPhu, 0.8 );
			g.lineWidth = 1;
			g.stroke();
			g.restore();
		},
		phatKhiDi: function ( ds, x, y, vx, vy, heSo, tt ) {
			if ( tt.giamChuyenDong || Math.random() > 0.25 * heSo ) { return; }
			if ( ds.length >= TRAN_HAT ) { return; }
			var h = taoHatMacDinh( x, y, ( Math.random() - 0.5 ) * 8, -18 - Math.random() * 10, tt.doDai / 12, 3 + Math.random() * 2 );
			ds.push( h );
		},
		phatKhiNhap: function ( ds, x, y, tt ) {
			for ( var i = 0; i < Math.round( 4 * tt.heSoMatDo ); i++ ) {
				if ( ds.length >= TRAN_HAT ) { break; }
				var h = taoHatMacDinh( x + ( Math.random() - 0.5 ) * 12, y, ( Math.random() - 0.5 ) * 20, -20 - Math.random() * 20, tt.doDai / 10, 3 + Math.random() * 3 );
				ds.push( h );
			}
		},
		veHat: function ( g, h, tt ) {
			var tiLe = 1 - h.tuoi / h.doiSong;
			if ( tiLe <= 0 ) { return; }
			g.save();
			g.globalAlpha = tiLe * 0.8;
			veTron( g, h.x, h.y, h.size, rgba( tt.mau, 0.25 ) );
			g.beginPath();
			g.arc( h.x, h.y, h.size, 0, Math.PI * 2 );
			g.strokeStyle = rgba( tt.mauPhu, 0.9 );
			g.lineWidth = 0.8;
			g.stroke();
			g.restore();
		},
		capNhatHat: capNhatHatMacDinh
	};

	/* --- 18. Hoa mai ----------------------------------------------------------------- */
	HIEU_UNG[ 'hoa-mai' ] = {
		veConTro: function ( g, x, y, tt ) {
			var r = 6.4 * tt.ty;
			for ( var i = 0; i < 5; i++ ) {
				veCanhHoa( g, x, y, r * 1.7, r * 0.9, tt.t * 0.2 + ( Math.PI * 2 / 5 ) * i, rgba( tt.mau, 0.9 ) );
			}
			veTron( g, x, y, r * 0.35, '#8B1E2D' );
		},
		phatKhiDi: function ( ds, x, y, vx, vy, heSo, tt ) {
			if ( tt.giamChuyenDong || Math.hypot( vx, vy ) < 50 || Math.random() > 0.35 * heSo ) { return; }
			if ( ds.length >= TRAN_HAT ) { return; }
			var h = taoHatMacDinh( x, y, ( Math.random() - 0.5 ) * 18, 16 + Math.random() * 14, tt.doDai / 16, 3.4 );
			h.rotSpeed = ( Math.random() - 0.5 ) * 3;
			ds.push( h );
		},
		phatKhiNhap: function ( ds, x, y, tt ) {
			for ( var i = 0; i < 5; i++ ) {
				if ( ds.length >= TRAN_HAT ) { break; }
				var a = ( Math.PI * 2 / 5 ) * i;
				var h = taoHatMacDinh( x, y, Math.cos( a ) * 40, Math.sin( a ) * 40, tt.doDai / 16, 3.6 );
				h.rotSpeed = ( Math.random() - 0.5 ) * 3;
				ds.push( h );
			}
		},
		veHat: function ( g, h, tt ) {
			var tiLe = 1 - h.tuoi / h.doiSong;
			if ( tiLe <= 0 ) { return; }
			h.goc += ( h.rotSpeed || 1 ) * 0.06;
			g.save();
			g.globalAlpha = tiLe;
			veCanhHoa( g, h.x, h.y, h.size * 1.5, h.size * 0.8, h.goc, rgba( tt.mau, 0.85 ) );
			g.restore();
		},
		capNhatHat: capNhatHatRoi
	};

	/* --- 19. Sao băng ----------------------------------------------------------------- */
	HIEU_UNG[ 'sao-bang' ] = {
		veConTro: function ( g, x, y, tt ) {
			veTron( g, x, y, 3.6 * tt.ty, tt.mau );
			veVienToi( g, x, y, 3.6 * tt.ty, 0.5 );
		},
		phatKhiDi: function ( ds, x, y, vx, vy, heSo, tt ) {
			if ( tt.giamChuyenDong ) { return; }
			var tocDo = Math.hypot( vx, vy );
			if ( tocDo < 260 ) {
				if ( ds.length >= TRAN_HAT || Math.random() > 0.4 ) { return; }
				var h0 = taoHatMacDinh( x, y, 0, 0, tt.doDai / 30, 1.6 );
				h0.data.mau = tt.mau;
				ds.push( h0 );
				return;
			}
			var so = Math.round( heSo * Math.min( 3, tocDo / 260 ) );
			for ( var i = 0; i < so; i++ ) {
				if ( ds.length >= TRAN_HAT ) { break; }
				var goc = Math.atan2( -vy, -vx ) + ( Math.random() - 0.5 ) * 0.5;
				var v = 120 + Math.random() * 140;
				var h = taoHatMacDinh( x, y, Math.cos( goc ) * v, Math.sin( goc ) * v, tt.doDai / 26, 2 );
				h.data.mau = Math.random() > 0.5 ? tt.mau : tt.mauPhu;
				ds.push( h );
			}
		},
		phatKhiNhap: function ( ds, x, y, tt ) {
			phatHatToaTron( ds, x, y, Math.round( 10 * tt.heSoMatDo ), 120, tt.doDai, tt.mauPhu, 2 );
		},
		veHat: veHatTronMem,
		capNhatHat: capNhatHatMacDinh
	};

	/* --- 20. Chấm vòng (tối giản) ------------------------------------------------------ */
	HIEU_UNG[ 'cham-vong' ] = {
		veConTro: function ( g, x, y, tt, viTriTre ) {
			veTron( g, x, y, 3.6 * tt.ty, tt.mau );
			veVienToi( g, x, y, 3.6 * tt.ty, 0.5 );
			if ( viTriTre ) {
				g.save();
				g.beginPath();
				g.arc( viTriTre.x, viTriTre.y, ( tt.banBam ? 20 : 13 ) * tt.ty, 0, Math.PI * 2 );
				g.strokeStyle = rgba( tt.mauPhu, 0.8 );
				g.lineWidth = 1.4;
				g.stroke();
				g.restore();
			}
		},
		phatKhiDi: function () {},
		phatKhiNhap: function ( ds, x, y, tt ) {
			var h = taoHatMacDinh( x, y, 0, 0, tt.doDai / 14, 4 );
			h.data.mau = tt.mauPhu;
			h.data.gon = true;
			ds.push( h );
		},
		veHat: function ( g, h, tt ) {
			var tiLe = h.tuoi / h.doiSong;
			if ( tiLe >= 1 ) { return; }
			g.save();
			g.globalAlpha = ( 1 - tiLe ) * 0.6;
			g.lineWidth = 1.2;
			g.strokeStyle = h.data.mau;
			g.beginPath();
			g.arc( h.x, h.y, h.size + tiLe * 20, 0, Math.PI * 2 );
			g.stroke();
			g.restore();
		},
		capNhatHat: capNhatHatMacDinh,
		canViTriTre: true
	};

	/* ==========================================================================
	 * Bộ máy — một phiên cho mỗi lần khoiTao().
	 * ========================================================================== */

	function chuanHoaCauHinh( tho ) {
		tho = tho || {};

		var kieu = HIEU_UNG.hasOwnProperty( tho.kieu ) ? tho.kieu : 'mat-troi';
		var doDai = parseFloat( tho.doDai );
		if ( isNaN( doDai ) ) { doDai = 30; }
		doDai = Math.max( 10, Math.min( 60, doDai ) );

		var matDo = HE_SO_MAT_DO.hasOwnProperty( tho.matDo ) ? tho.matDo : 'vua';
		var co    = HE_SO_CO.hasOwnProperty( tho.co ) ? tho.co : 'vua';
		var mau   = layMauKieu( kieu, tho.mau, tho.mauPhu );

		return {
			kieu: kieu,
			doDai: doDai,
			matDo: matDo,
			co: co,
			mau: mau.chinh,
			mauPhu: mau.phu,
			// Chỉ tự thích nghi theo nền khi BQT CHƯA chọn màu riêng — chọn rồi thì
			// tôn trọng đúng màu đó, chỉ có viền tương phản lo phần dễ đọc.
			mauLaMacDinh: ! laHex( tho.mau ),
			doSangMau: doSang( mau.chinh ),
			doSangMauPhu: doSang( mau.phu ),
			vung: tho.vung || null
		};
	}

	function taoPhien( thoCauHinh ) {
		var cfg = chuanHoaCauHinh( thoCauHinh );

		var vungGoc  = cfg.vung; // phần tử DOM khi ở chế độ "vùng", null = toàn trang.
		var dichSuKien = vungGoc || document;

		var canvas = document.createElement( 'canvas' );
		canvas.className = LOP_CANVAS;
		canvas.setAttribute( 'aria-hidden', 'true' );

		var ctx = canvas.getContext( '2d' );
		var dpr = Math.max( 1, Math.min( 2, global.devicePixelRatio || 1 ) );

		if ( vungGoc ) {
			vungGoc.classList.add( LOP_VUNG );
			vungGoc.appendChild( canvas );
		} else {
			document.body.appendChild( canvas );
		}

		/*
		 * Có đang dùng CHUỘT không — xét theo pointerType của chính sự kiện, KHÔNG
		 * theo media query (hover: hover) and (pointer: fine): laptop Windows có
		 * màn cảm ứng hay báo thiết bị chính là cảm ứng dù người dùng đang cầm
		 * chuột, làm hiệu ứng không bao giờ bật. Chạm bằng tay (pointerType
		 * 'touch') thì tắt ngay, trả lại con trỏ hệ thống — điện thoại vẫn không chạy.
		 */
		var dangDungChuot = false;
		var hoTroChuot    = { get matches() { return dangDungChuot; } };

		function laSuKienChuot( e ) {
			// Trình duyệt cũ không có pointerType ('' / undefined) → coi là chuột.
			return ! e.pointerType || 'mouse' === e.pointerType;
		}
		var giamChuyenDong = global.matchMedia( '(prefers-reduced-motion: reduce)' );

		var hat      = [];
		var duongDi  = [];
		var demRieng = { domDom: 0 };

		var x = -9999, y = -9999, vx = 0, vy = 0, xCu = -9999, yCu = -9999;
		var thoiGianCuoi   = 0;
		var batDauLuc      = ( global.performance && performance.now ) ? performance.now() : Date.now();
		var dangChay       = false;
		var rafId          = null;
		var daNhanDiChuyen = false;
		var dangAn         = true; // ẩn cho tới khi đủ điều kiện (hover chuột + đã di chuyển).
		var choTre         = []; // vị trí trễ 1 nhịp, dùng cho hào quang/chấm vòng.
		var treClickable   = false;
		var treONhap       = false;
		var dangBam        = false;
		var daHuy          = false;

		/*
		 * Tự thích nghi theo nền: nền site vốn sáng (kem) nên giả định ban đầu
		 * là sáng — tránh một nhịp màu sai trước khi có mẫu đầu tiên.
		 */
		var nenLumHienTai  = 0.85;
		var lanCuoiDoNen   = 0;
		var xClientHienTai = -9999;
		var yClientHienTai = -9999;

		/**
		 * Lấy độ sáng nền THẬT ngay dưới con trỏ: dò từ phần tử trên cùng
		 * (elementFromPoint bỏ qua canvas của chính ta vì nó pointer-events:none)
		 * ngược lên tổ tiên tới khi gặp một nền không trong suốt, hoặc ảnh/video.
		 */
		function layDoSangNen( xC, yC ) {
			if ( ! document.elementFromPoint || xC < 0 || yC < 0 ) { return null; }

			try {
				var el = document.elementFromPoint( xC, yC );
				var buoc = 0;

				while ( el && 1 === el.nodeType && buoc < 8 ) {
					if ( 'IMG' === el.tagName || 'VIDEO' === el.tagName || 'CANVAS' === el.tagName ) {
						return 0.35; // Coi ảnh/video là nền trung tối — an toàn hơn giả định sáng.
					}

					var kieuDang = global.getComputedStyle( el );
					var bg = kieuDang.backgroundColor;

					if ( bg && ! /rgba?\([^)]*,?\s*0\s*\)$/.test( bg ) && 'transparent' !== bg ) {
						var m = bg.match( /rgba?\(\s*([\d.]+)[,\s]+([\d.]+)[,\s]+([\d.]+)/ );
						if ( m ) {
							return ( 0.299 * m[ 1 ] + 0.587 * m[ 2 ] + 0.114 * m[ 3 ] ) / 255;
						}
					}

					if ( kieuDang.backgroundImage && 'none' !== kieuDang.backgroundImage ) {
						return 0.35;
					}

					el = el.parentElement;
					buoc++;
				}
			} catch ( loi ) {
				return null;
			}

			return null;
		}

		function capNhatNenNeuCan( tMs ) {
			if ( tMs - lanCuoiDoNen < 150 ) { return; }
			lanCuoiDoNen = tMs;

			var ket = layDoSangNen( xClientHienTai, yClientHienTai );
			if ( null !== ket ) { nenLumHienTai = ket; }
		}

		function laDoTiepXuc( yeuTo ) {
			if ( ! yeuTo || 1 !== yeuTo.nodeType ) { return false; }
			return !! yeuTo.closest( 'input, textarea, select, [contenteditable], [contenteditable="true"]' );
		}

		function laCoTheBam( yeuTo ) {
			if ( ! yeuTo || 1 !== yeuTo.nodeType ) { return false; }
			return !! yeuTo.closest( 'a, button, [role="button"], label, summary' );
		}

		function capNhatKichThuoc() {
			var w, h;
			if ( vungGoc ) {
				var r = vungGoc.getBoundingClientRect();
				w = r.width;
				h = r.height;
			} else {
				w = global.innerWidth;
				h = global.innerHeight;
			}
			canvas.width = Math.max( 1, Math.round( w * dpr ) );
			canvas.height = Math.max( 1, Math.round( h * dpr ) );
			canvas.style.width = w + 'px';
			canvas.style.height = h + 'px';
			ctx.setTransform( dpr, 0, 0, dpr, 0, 0 );
		}

		function capNhatDangAn() {
			var moiPhaiAn = hoTroChuot.matches && daNhanDiChuyen;

			if ( moiPhaiAn === ! dangAn ) { return; }

			dangAn = ! moiPhaiAn;

			var dich = vungGoc || document.documentElement;
			if ( moiPhaiAn ) {
				dich.classList.add( LOP_AN_CON_TRO );
			} else {
				dich.classList.remove( LOP_AN_CON_TRO );
			}
		}

		function toaDoTuSuKien( e ) {
			if ( vungGoc ) {
				var r = vungGoc.getBoundingClientRect();
				return { x: e.clientX - r.left, y: e.clientY - r.top };
			}
			return { x: e.clientX, y: e.clientY };
		}

		function batDauVong() {
			if ( dangChay || daHuy ) { return; }
			dangChay = true;
			rafId = global.requestAnimationFrame( vongLap );
		}

		function dungVong() {
			dangChay = false;
			if ( null !== rafId ) {
				global.cancelAnimationFrame( rafId );
				rafId = null;
			}
		}

		// Chuyển sang chạm: dừng vòng thôi chưa đủ — khung vẽ cuối vẫn đọng trên màn hình.
		function tatVaXoa() {
			dungVong();
			hat.length = 0;
			duongDi.length = 0;
			ctx.clearRect( 0, 0, canvas.width, canvas.height );
		}

		function traiDaiToaDo( x1, y1 ) {
			duongDi.push( { x: x1, y: y1 } );
			if ( duongDi.length > 34 ) { duongDi.shift(); }
		}

		function vongLap( tMs ) {
			rafId = null;

			if ( ! hoTroChuot.matches || document.hidden ) {
				dangChay = false;
				return;
			}

			var t0 = ( tMs || 0 ) / 1000;
			var dt = thoiGianCuoi ? Math.min( 0.05, t0 - thoiGianCuoi ) : 0.016;
			thoiGianCuoi = t0;

			var tGiay = ( tMs - batDauLuc ) / 1000;

			var w, h;
			if ( vungGoc ) {
				w = canvas.width / dpr;
				h = canvas.height / dpr;
			} else {
				w = global.innerWidth;
				h = global.innerHeight;
			}
			ctx.clearRect( 0, 0, w, h );

			var dinhNghia = HIEU_UNG[ cfg.kieu ];
			var reduced = giamChuyenDong.matches;

			capNhatNenNeuCan( tMs );

			var heSoCo = HE_SO_CO[ cfg.co ];

			// Tự thích nghi theo nền — CHỈ khi màu đang dùng là màu MẶC ĐỊNH của kiểu.
			var mauHieuUng = cfg.mau;
			var mauPhuHieuUng = cfg.mauPhu;

			if ( cfg.mauLaMacDinh ) {
				if ( cfg.doSangMau < 0.45 && nenLumHienTai < 0.45 ) {
					// Màu mặc định tối (vd mực) trên nền cũng tối -> đổi sang biến thể sáng.
					mauHieuUng = phaSang( cfg.mau, 55 );
					mauPhuHieuUng = phaSang( cfg.mauPhu, 55 );
				} else if ( cfg.doSangMau > 0.6 && nenLumHienTai > 0.6 ) {
					// Màu mặc định gần trắng trên nền cũng sáng -> đổi sang biến thể tối hơn.
					mauHieuUng = phaToi( cfg.mau, 40 );
					mauPhuHieuUng = phaToi( cfg.mauPhu, 40 );
				}
			}

			var tt = {
				mau: mauHieuUng,
				mauPhu: mauPhuHieuUng,
				t: tGiay,
				dt: dt,
				giamChuyenDong: reduced,
				banBam: dangBam,
				ty: ( treClickable ? 1.3 : 1 ) * heSoCo * TY_LE_PHONG_CON_TRO,
				doDai: cfg.doDai,
				heSoMatDo: HE_SO_MAT_DO[ cfg.matDo ]
			};

			// Cập nhật + vẽ hạt.
			var i, con = 0;
			for ( i = hat.length - 1; i >= 0; i-- ) {
				var hh = hat[ i ];
				if ( dinhNghia.capNhatHat ) {
					dinhNghia.capNhatHat( hh, dt, { x: x, y: y } );
				}
				if ( hh.tuoi >= hh.doiSong ) {
					if ( hh.data && hh.data.domDom ) { demRieng.domDom--; }
					hat.splice( i, 1 );
					continue;
				}
				con++;
			}

			if ( ! reduced ) {
				var tyLeHat = heSoCo * TY_LE_PHONG_HAT;
				for ( i = 0; i < hat.length; i++ ) {
					var hp = hat[ i ];
					// Phóng hạt quanh CHÍNH TÂM của nó — không cần sửa số đo bên trong
					// từng veHat() của 20 kiểu, chỉ phóng đều lúc vẽ.
					ctx.save();
					ctx.translate( hp.x, hp.y );
					ctx.scale( tyLeHat, tyLeHat );
					ctx.translate( -hp.x, -hp.y );
					dinhNghia.veHat( ctx, hp, tt );
					ctx.restore();
				}
			} else {
				hat.length = 0;
				con = 0;
			}

			// Vệt đường đi (sao chổi, nét mực).
			if ( dinhNghia.veDuongDi && x > -999 ) {
				dinhNghia.veDuongDi( ctx, duongDi, tt, Math.hypot( vx, vy ) );
			}

			// Chuỗi hạt bám theo (kiểu chuoi-hat).
			if ( 'chuoi-hat' === cfg.kieu && x > -999 ) {
				capNhatChuoiHat( dt );
				dinhNghia.veHatChuoi( ctx, chuoiViTri, tt );
			}

			// Con trỏ chính — không vẽ khi đang trên ô nhập liệu.
			if ( ! treONhap && daNhanDiChuyen && x > -999 ) {
				choTre.push( { x: x, y: y } );
				if ( choTre.length > 6 ) { choTre.shift(); }
				var viTriTre = choTre.length ? choTre[ 0 ] : null;
				dinhNghia.veConTro( ctx, x, y, tt, viTriTre );
			}

			var conChuyenDong = ( Math.abs( vx ) > 1 || Math.abs( vy ) > 1 );
			vx *= 0.85;
			vy *= 0.85;

			if ( con > 0 || conChuyenDong || dangBam ) {
				rafId = global.requestAnimationFrame( vongLap );
			} else {
				dangChay = false;
			}
		}

		// --- Chuỗi hạt: mảng vị trí lò xo bám theo con trỏ. ---
		var chuoiViTri = [];
		( function khoiTaoChuoi() {
			for ( var i = 0; i < 12; i++ ) { chuoiViTri.push( { x: -9999, y: -9999 } ); }
		}() );

		function capNhatChuoiHat( dt ) {
			var muc = chuoiViTri;
			var tx = x, ty = y;
			for ( var i = 0; i < muc.length; i++ ) {
				var do_cung = 10 - i * 0.4;
				muc[ i ].x += ( tx - muc[ i ].x ) * Math.min( 1, do_cung * dt );
				muc[ i ].y += ( ty - muc[ i ].y ) * Math.min( 1, do_cung * dt );
				tx = muc[ i ].x;
				ty = muc[ i ].y;
			}
		}

		var lanCuoiDiTs = 0;

		function xuLyDi( e ) {
			var laChuot = laSuKienChuot( e );
			if ( laChuot !== dangDungChuot ) {
				dangDungChuot = laChuot;
				capNhatDangAn();
				if ( ! laChuot ) { tatVaXoa(); }
			}
			if ( ! hoTroChuot.matches ) { return; }

			var p = toaDoTuSuKien( e );
			xCu = x; yCu = y;
			x = p.x; y = p.y;
			// Toạ độ MÀN HÌNH thật (không lệch theo vùng) — dùng để dò nền qua elementFromPoint.
			xClientHienTai = e.clientX;
			yClientHienTai = e.clientY;

			var tsMoi = e.timeStamp || ( global.performance ? performance.now() : Date.now() );
			var dtDi = lanCuoiDiTs ? Math.max( 0.008, Math.min( 0.1, ( tsMoi - lanCuoiDiTs ) / 1000 ) ) : 0.016;
			lanCuoiDiTs = tsMoi;

			if ( xCu > -999 ) {
				vx = ( x - xCu ) / dtDi;
				vy = ( y - yCu ) / dtDi;
			}

			if ( ! daNhanDiChuyen ) {
				daNhanDiChuyen = true;
				capNhatDangAn();
				for ( var i = 0; i < chuoiViTri.length; i++ ) { chuoiViTri[ i ].x = x; chuoiViTri[ i ].y = y; }
			}

			treONhap = laDoTiepXuc( e.target );
			treClickable = laCoTheBam( e.target );

			var dinhNghia = HIEU_UNG[ cfg.kieu ];
			if ( dinhNghia.phatKhiDi && ! treONhap ) {
				dinhNghia.phatKhiDi( hat, x, y, vx, vy, HE_SO_MAT_DO[ cfg.matDo ], {
					mau: cfg.mau, mauPhu: cfg.mauPhu, doDai: cfg.doDai,
					giamChuyenDong: giamChuyenDong.matches, heSoMatDo: HE_SO_MAT_DO[ cfg.matDo ]
				}, demRieng );
			}

			traiDaiToaDo( x, y );
			batDauVong();
		}

		function xuLyBam( e ) {
			if ( ! laSuKienChuot( e ) ) {
				if ( dangDungChuot ) { dangDungChuot = false; capNhatDangAn(); tatVaXoa(); }
				return;
			}
			if ( ! hoTroChuot.matches || 0 !== e.button ) { return; }
			dangBam = true;
			var p = toaDoTuSuKien( e );
			var dinhNghia = HIEU_UNG[ cfg.kieu ];
			if ( dinhNghia.phatKhiNhap && ! laDoTiepXuc( e.target ) ) {
				dinhNghia.phatKhiNhap( hat, p.x, p.y, {
					mau: cfg.mau, mauPhu: cfg.mauPhu, doDai: cfg.doDai, heSoMatDo: HE_SO_MAT_DO[ cfg.matDo ]
				} );
			}
			batDauVong();
		}

		function xuLyNha() {
			dangBam = false;
		}

		function xuLyRoi( e ) {
			// pointerleave document, hoặc mouseout với relatedTarget null (rời cửa sổ / vào iframe).
			x = -9999; y = -9999;
			daNhanDiChuyen = false;
			capNhatDangAn();
		}

		function xuLyMouseOut( e ) {
			if ( null === e.relatedTarget || undefined === e.relatedTarget ) {
				xuLyRoi( e );
			}
		}

		function xuLyHienThi() {
			if ( document.hidden ) {
				dungVong();
			} else if ( x > -999 ) {
				batDauVong();
			}
		}

		function xuLyMediaThayDoi() {
			capNhatDangAn();
			if ( hoTroChuot.matches && x > -999 ) {
				batDauVong();
			} else {
				dungVong();
			}
		}

		function xuLyResize() {
			capNhatKichThuoc();
		}

		capNhatKichThuoc();

		dichSuKien.addEventListener( 'pointermove', xuLyDi, { passive: true } );
		dichSuKien.addEventListener( 'pointerdown', xuLyBam, { passive: true } );
		global.addEventListener( 'pointerup', xuLyNha, { passive: true } );
		dichSuKien.addEventListener( 'pointerleave', xuLyRoi, { passive: true } );
		document.addEventListener( 'mouseout', xuLyMouseOut, { passive: true } );
		document.addEventListener( 'visibilitychange', xuLyHienThi );

		if ( hoTroChuot.addEventListener ) {
			hoTroChuot.addEventListener( 'change', xuLyMediaThayDoi );
		}
		if ( giamChuyenDong.addEventListener ) {
			giamChuyenDong.addEventListener( 'change', function () { batDauVong(); } );
		}

		global.addEventListener( 'resize', xuLyResize );

		return {
			doiCauHinh: function ( choTro ) {
				cfg = chuanHoaCauHinh( Object.assign( {}, thoCauHinh, choTro, { vung: vungGoc } ) );
				thoCauHinh = Object.assign( {}, thoCauHinh, choTro );
				hat.length = 0;
				demRieng.domDom = 0;
				batDauVong();
			},
			huy: function () {
				daHuy = true;
				dungVong();
				dichSuKien.removeEventListener( 'pointermove', xuLyDi );
				dichSuKien.removeEventListener( 'pointerdown', xuLyBam );
				global.removeEventListener( 'pointerup', xuLyNha );
				dichSuKien.removeEventListener( 'pointerleave', xuLyRoi );
				document.removeEventListener( 'mouseout', xuLyMouseOut );
				document.removeEventListener( 'visibilitychange', xuLyHienThi );
				if ( hoTroChuot.removeEventListener ) { hoTroChuot.removeEventListener( 'change', xuLyMediaThayDoi ); }
				global.removeEventListener( 'resize', xuLyResize );

				var dich = vungGoc || document.documentElement;
				dich.classList.remove( LOP_AN_CON_TRO );
				if ( vungGoc ) { vungGoc.classList.remove( LOP_VUNG ); }

				if ( canvas.parentNode ) { canvas.parentNode.removeChild( canvas ); }
			}
		};
	}

	global.NNTMConTro = {
		khoiTao: taoPhien,
		HIEU_UNG: HIEU_UNG,
		_tienIch: { rgba: rgba, phaSang: phaSang, phaToi: phaToi, docBienCss: docBienCss, layMauKieu: layMauKieu }
	};

}( window ) );
