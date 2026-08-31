 
( function () {
	'use strict';

	if ( 'undefined' === typeof nntmDocSach ) {
		return;
	}

	var CFG = nntmDocSach;

	var coTep = !! CFG.pdfUrl && 'undefined' !== typeof pdfjsLib;

	if ( coTep ) {
		pdfjsLib.GlobalWorkerOptions.workerSrc = CFG.workerUrl;
	}


	var TY_LE_TO = 1.42;

	var NGUONG_DOI_TO = 760;
	var CUA_SO   = 6;      
	var GAN      = 4;      
	var XA       = 10;     

	var el = {
		stage:    document.querySelector( '[data-nntm-doc="stage"]' ),
		loading:  document.querySelector( '[data-nntm-doc="dang-tai"]' ),
		text:     document.querySelector( '[data-nntm-doc="chu-sach"]' ),
		toc:      document.getElementById( 'nntm-doc-toc' ),
		tocBody:  document.querySelector( '[data-nntm-doc="toc-body"]' ),
		tocBtn:   document.querySelector( '[data-nntm-doc="muc-luc"]' ),
		panel:    document.getElementById( 'nntm-doc-hien' ),
		panelBtn: document.querySelector( '[data-nntm-doc="hien"]' ),
		music:    document.getElementById( 'nntm-doc-nhac' ),
		musicBtn: document.querySelector( '[data-nntm-doc="nhac"]' ),
		musicAudio: document.querySelector( '[data-nntm-doc="nhac-audio"]' ),
		musicPlay:  document.querySelector( '[data-nntm-doc="nhac-phat"]' ),
		musicPrev:  document.querySelector( '[data-nntm-doc="nhac-truoc"]' ),
		musicNext:  document.querySelector( '[data-nntm-doc="nhac-sau"]' ),
		musicTitle: document.querySelector( '[data-nntm-doc="nhac-ten"]' ),
		musicClose: document.querySelector( '[data-nntm-doc="nhac-dong"]' ),
		musicSeek:  document.querySelector( '[data-nntm-doc="nhac-tua"]' ),
		musicAt:    document.querySelector( '[data-nntm-doc="nhac-da-nghe"]' ),
		musicLen:   document.querySelector( '[data-nntm-doc="nhac-dai"]' ),
		musicVol:   document.querySelector( '[data-nntm-doc="nhac-am-luong"]' ),
		musicMute:  document.querySelector( '[data-nntm-doc="nhac-tat-tieng"]' ),
		mark:     document.querySelector( '[data-nntm-doc="danh-dau"]' ),
		prev:     document.querySelector( '[data-nntm-doc="truoc"]' ),
		next:     document.querySelector( '[data-nntm-doc="sau"]' ),
		slider:   document.querySelector( '[data-nntm-doc="thanh-truot"]' ),
		percent:  document.querySelector( '[data-nntm-doc="phan-tram"]' ),
		chapter:  document.querySelector( '[data-nntm-doc="chuong"]' ),
		water:    document.querySelector( '[data-nntm-doc="watermark"]' ),
		benDong:  document.querySelector( '[data-nntm-doc="dong-ben"]' ),
		benMo:    document.querySelector( '[data-nntm-doc="mo-ben"]' )
	};

	var pdf     = null;
	var soTrang = 0;
	var trangHT = 1;         
	var cheDo   = 'lat';     
	var mucLuc  = [];
	var khoiTheoTrang = {};  
	var to      = [];        
	var tyLeTrang = 0;       
	var toHT    = 0;         
	var pageFlip = null;
	var tuTrang = 1;
	var denTrang = 0;

	 
	function gomDong( noiDung ) {
		var dong = [];

		noiDung.items.forEach( function ( m ) {
			if ( ! m.str || ! m.str.trim() ) {
				return;
			}

			var x   = m.transform[ 4 ];
			var y   = m.transform[ 5 ];
			var cao = Math.abs( m.transform[ 3 ] ) || m.height || 10;
			var hop = null;

			for ( var i = dong.length - 1; i >= 0 && i > dong.length - 4; i-- ) {
				if ( Math.abs( dong[ i ].y - y ) <= cao * 0.5 ) {
					hop = dong[ i ];
					break;
				}
			}

			if ( hop ) {
				hop.manh.push( { x: x, chu: m.str } );
				hop.cao = Math.max( hop.cao, cao );
				hop.x   = Math.min( hop.x, x );
			} else {
				dong.push( { y: y, x: x, cao: cao, manh: [ { x: x, chu: m.str } ] } );
			}
		} );

		dong.sort( function ( a, b ) { return b.y - a.y; } );

		dong.forEach( function ( d ) {
			d.manh.sort( function ( a, b ) { return a.x - b.x; } );
			d.chu = d.manh.map( function ( m ) { return m.chu; } ).join( '' ).replace( /\s+/g, ' ' ).trim();
		} );

		return dong.filter( function ( d ) { return d.chu.length > 0; } );
	}

	function gopDoan( dong ) {
		if ( ! dong.length ) {
			return [];
		}


		var demX = {};

		dong.forEach( function ( d ) {
			var k = Math.round( d.x );
			demX[ k ] = ( demX[ k ] || 0 ) + 1;
		} );

		var le = Number( Object.keys( demX ).sort( function ( a, b ) { return demX[ b ] - demX[ a ]; } )[ 0 ] );
		var caoVua = dong.map( function ( d ) { return d.cao; } ).sort( function ( a, b ) { return a - b; } )[ Math.floor( dong.length / 2 ) ];

		var khoi  = [];
		var truoc = null;

		dong.forEach( function ( d ) {
			var laTieuDe = d.cao > caoVua * 1.22 && d.chu.length < 120;
			var thutVao  = d.x > le + caoVua * 0.8;
			var cachXa   = truoc && ( truoc.y - d.y ) > caoVua * 2.1;

			if ( laTieuDe ) {
				khoi.push( { loai: 'h', chu: d.chu } );
				truoc = d;
				return;
			}

			if ( ! khoi.length || 'h' === khoi[ khoi.length - 1 ].loai || thutVao || cachXa ) {
				khoi.push( { loai: 'p', chu: d.chu } );
			} else {
				var cuoi = khoi[ khoi.length - 1 ];


				if ( /-$/.test( cuoi.chu ) ) {
					cuoi.chu = cuoi.chu.replace( /-$/, '' ) + d.chu;
				} else {
					cuoi.chu += ' ' + d.chu;
				}
			}

			truoc = d;
		} );

		return khoi.filter( function ( k ) {
			return ! /^[\s\d\-–—.]+$/.test( k.chu ) && k.chu.length > 1;
		} );
	}

	function layKhoi( so ) {
		if ( khoiTheoTrang[ so ] ) {
			return Promise.resolve( khoiTheoTrang[ so ] );
		}

		return pdf.getPage( so ).then( function ( page ) {
			return page.getTextContent().then( function ( nd ) {
				khoiTheoTrang[ so ] = gopDoan( gomDong( nd ) );
				return khoiTheoTrang[ so ];
			} );
		} );
	}

	 
	function layTyLeTrang() {
		if ( tyLeTrang ) {
			return Promise.resolve( tyLeTrang );
		}

		return pdf.getPage( 1 ).then( function ( trang ) {
			var vp = trang.getViewport( { scale: 1 } );

			tyLeTrang = vp.height / vp.width;

			return tyLeTrang;
		} ).catch( function () {
			tyLeTrang = TY_LE_TO;

			return tyLeTrang;
		} );
	}

	function khoTo() {



		var cs = window.getComputedStyle( el.stage );
		var W  = el.stage.clientWidth  - ( parseFloat( cs.paddingLeft ) || 0 ) - ( parseFloat( cs.paddingRight ) || 0 );
		var H  = el.stage.clientHeight - ( parseFloat( cs.paddingTop )  || 0 ) - ( parseFloat( cs.paddingBottom ) || 0 );
		var doi = W >= NGUONG_DOI_TO;
		var ty  = tyLeTrang || TY_LE_TO;

		var rong = doi ? ( W - 24 ) / 2 : W - 16;
		var cao  = rong * ty;

		if ( cao > H - 12 ) {
			cao  = H - 12;
			rong = cao / ty;
		}

		return { rong: Math.floor( rong ), cao: Math.floor( cao ), doi: doi };
	}

	function esc( s ) {
		var d = document.createElement( 'div' );
		d.textContent = s || '';
		return d.innerHTML;
	}

	function dep() {
		huyVeTo();

		if ( pageFlip ) {
			try { pageFlip.destroy(); } catch ( e ) {}
			pageFlip = null;
		}

		el.stage.querySelectorAll( '.nntm-doc__book, .nntm-doc__ruler' ).forEach( function ( n ) {
			if ( n.parentNode ) { n.parentNode.removeChild( n ); }
		} );

		el.text.textContent = '';
	}

	function daiTrang() {
		tuTrang  = Math.max( 1, Math.min( soTrang, trangHT ) );
		denTrang = Math.min( soTrang, tuTrang + CUA_SO - 1 );

		var ds = [];

		for ( var i = tuTrang; i <= denTrang; i++ ) {
			ds.push( i );
		}

		return ds;
	}

	function veLat() {
		dep();

		return layTyLeTrang().then( function () {
			var kho = khoTo();

			to = [];

			for ( var i = 1; i <= soTrang; i++ ) {
				to.push( { trang: i, el: null, xong: false, dangVe: false, viec: null } );
			}

			tuTrang  = 1;
			denTrang = soTrang;

			if ( ! to.length ) {
				return;
			}

			var boc = document.createElement( 'div' );
			boc.className = 'nntm-doc__book';
			el.stage.appendChild( boc );

			pageFlip = new St.PageFlip( boc, {
				width: kho.rong,
				height: kho.cao,
				size: 'fixed',

				showCover: false,
				usePortrait: ! kho.doi,
				maxShadowOpacity: 0.5,
				mobileScrollSupport: false,
				useMouseEvents: true,
				flippingTime: 700
			} );

			pageFlip.loadFromHTML( taoTo( to ) );

			pageFlip.on( 'flip', function ( e ) {
				toHT    = e.data | 0;
				trangHT = toHT + 1;

				capNhat();
				luuViTri();
				veToQuanh();
			} );

			toHT = Math.max( 0, Math.min( to.length - 1, trangHT - 1 ) );

			if ( toHT ) {
				try { pageFlip.turnToPage( toHT ); } catch ( e ) {}
			}

			capNhat();

			return veToQuanh();
		} );
	}

	function taoTo( ds ) {
		var kho = document.createElement( 'div' );
		kho.className = 'nntm-doc__ruler';

		kho.innerHTML = ds.map( function ( t ) {
			return '<div class="nntm-doc__sheet" data-trang="' + t.trang + '">' +
				'<div class="nntm-doc__anh" data-anh="1"></div>' +
				'<span class="nntm-doc__sheet-no">' + esc( CFG.i18n.trang + ' ' + t.trang ) + '</span>' +
				'</div>';
		} ).join( '' );

		el.stage.appendChild( kho );

		var ds2 = kho.querySelectorAll( '.nntm-doc__sheet' );


		ds.forEach( function ( t, i ) { t.el = ds2[ i ] || null; } );


		window.setTimeout( function () {
			if ( kho.parentNode ) { kho.parentNode.removeChild( kho ); }
		}, 0 );

		return ds2;
	}

	function veToQuanh() {
		if ( ! to.length ) {
			return Promise.resolve();
		}

		var dau  = Math.max( 0, toHT - GAN );
		var cuoi = Math.min( to.length - 1, toHT + GAN + 1 );
		var viec = [];

		for ( var i = dau; i <= cuoi; i++ ) {
			viec.push( veToCanvas( to[ i ] ) );
		}



		to.forEach( function ( t, i ) {
			if ( i < toHT - XA || i > toHT + XA ) { xoaAnhTo( t ); }
		} );

		return Promise.all( viec );
	}

	function veToCanvas( t ) {
		if ( ! t || ! t.el || t.xong || t.dangVe ) {
			return Promise.resolve();
		}

		t.dangVe = true;

		return pdf.getPage( t.trang ).then( function ( trang ) {
			var kho = khoTo();
			var goc = trang.getViewport( { scale: 1 } );

			var net = Math.min( window.devicePixelRatio || 1, 2 );
			var ti  = Math.max( ( kho.rong / goc.width ) * net, 0.2 );
			var vp  = trang.getViewport( { scale: ti } );

			var canvas = document.createElement( 'canvas' );
			canvas.className = 'nntm-doc__canvas';
			canvas.width  = Math.floor( vp.width );
			canvas.height = Math.floor( vp.height );

			t.viec = trang.render( { canvasContext: canvas.getContext( '2d' ), viewport: vp } );

			return t.viec.promise.then( function () {
				var hop = t.el ? t.el.querySelector( '[data-anh]' ) : null;

				if ( hop ) {
					hop.textContent = '';
					hop.appendChild( canvas );
				}

				t.viec   = null;
				t.xong   = true;
				t.dangVe = false;
			} );
		} ).catch( function () {
			t.viec   = null;
			t.dangVe = false;
		} );
	}

	function xoaAnhTo( t ) {
		if ( ! t || ! t.el || ! t.xong ) {
			return;
		}

		var hop = t.el.querySelector( '[data-anh]' );

		if ( hop ) { hop.textContent = ''; }

		t.xong = false;
	}

	function huyVeTo() {
		to.forEach( function ( t ) {
			if ( t.viec ) {
				try { t.viec.cancel(); } catch ( e ) {}
			}

			t.viec   = null;
			t.el     = null;
			t.xong   = false;
			t.dangVe = false;
		} );
	}

	function veCuon() {
		dep();

		var ds = daiTrang();

		return ds.reduce( function ( chuoi, so ) {
			return chuoi.then( function () {
				return layKhoi( so ).then( function ( khoi ) {
					var boc = document.createElement( 'section' );
					boc.dataset.trang = so;

					var moc = document.createElement( 'p' );
					moc.className = 'nntm-doc__mark';
					moc.textContent = CFG.i18n.trang + ' ' + so;
					boc.appendChild( moc );

					if ( ! khoi.length ) {
						var trong = document.createElement( 'p' );
						trong.dataset.moTrang = '1';
						trong.textContent = CFG.i18n.trangAnh;
						boc.appendChild( trong );
					}

					khoi.forEach( function ( k, i ) {
						var node = document.createElement( 'h' === k.loai ? 'h2' : 'p' );
						node.textContent = k.chu;

						if ( 0 === i ) {
							node.dataset.moTrang = '1';
						}

						boc.appendChild( node );
					} );

					el.text.appendChild( boc );
				} );
			} );
		}, Promise.resolve() ).then( function () {
			var o = el.text.querySelector( '[data-trang="' + trangHT + '"]' );
			el.stage.scrollTop = o ? o.offsetTop - 12 : 0;
			capNhat();
		} );
	}

	function veLai() {
		 
		if ( ! pdf ) {
			document.body.dataset.xem = cheDo;
			return Promise.resolve();
		}

		document.body.dataset.xem = cheDo;

		el.text.hidden = 'cuon' !== cheDo;

		return 'cuon' === cheDo ? veCuon() : veLat();
	}

	/*
	 * Đang đọc bản XEM THỬ và người đọc muốn đi quá trang cuối — báo ra ngoài để
	 * plugin thanh toán mở khung mua.
	 *
	 * Bắn sự kiện chứ không gọi thẳng hàm của plugin: trình đọc không được biết
	 * gì về việc bán sách, và gỡ plugin đi thì chỗ này chỉ là một sự kiện không
	 * ai nghe, không vỡ.
	 */
	function baoHetXemThu() {
		document.dispatchEvent( new CustomEvent( 'nntm:het-xem-thu', {
			detail: { objectId: CFG.objectId, soTrang: soTrang }
		} ) );
	}

	/*
	 * Trang cuối cùng người chưa mua được lật tới.
	 *
	 * Lấy số nhỏ hơn giữa "số trang quản trị cho xem" và "số trang tệp xem thử
	 * thật sự có" — đặt cho xem 5 trang mà tệp chỉ có 3 thì vẫn dừng ở 3.
	 *
	 * Đây là hàng rào TRẢI NGHIỆM, không phải hàng rào bảo mật: chốt chặn thật
	 * là máy chủ chỉ gửi đúng tệp xem thử, không bao giờ gửi tệp gốc.
	 */
	function gioiHanXemThu() {
		var dat = parseInt( CFG.trangXemThu, 10 );

		if ( ! dat || dat < 1 ) { return soTrang; }

		return Math.min( dat, soTrang );
	}

	function toiTrang( so ) {
		if ( ! pdf ) { return; }

		if ( CFG.xemThu && ( so | 0 ) > gioiHanXemThu() ) {
			baoHetXemThu();
			return;
		}

		so = Math.max( 1, Math.min( soTrang, so | 0 ) );

		if ( 'lat' === cheDo ) {

			var i = so - 1;

			if ( ! pageFlip || ! to.length ) {
				trangHT = so;
				hienDangTai( true );
				veLat().then( function () { hienDangTai( false ); luuViTri(); } );
				return;
			}

			if ( i === toHT ) {
				return;
			}

			try {
				pageFlip.flip( i );
			} catch ( e ) {
				toHT    = i;
				trangHT = so;
				capNhat();
				veToQuanh();
			}

			window.setTimeout( dongBoTo, 850 );
			return;
		}

		var o = el.text.querySelector( '[data-trang="' + so + '"]' );

		if ( o ) {
			trangHT = so;
			el.stage.scrollTop = o.offsetTop - 12;
			capNhat();
			luuViTri();
			return;
		}

		trangHT = so;
		hienDangTai( true );
		veCuon().then( function () { hienDangTai( false ); luuViTri(); } );
	}

	function buocTo() {
		if ( pageFlip && 'function' === typeof pageFlip.getOrientation ) {
			return 'landscape' === pageFlip.getOrientation() ? 2 : 1;
		}

		return khoTo().doi ? 2 : 1;
	}

	function latTo( huong ) {
		if ( ! pageFlip || ! to.length ) {
			return;
		}

		var dich = Math.max( 0, Math.min( to.length - 1, toHT + huong * buocTo() ) );

		if ( dich === toHT ) {
			return;
		}

		try {
			pageFlip.flip( dich );
		} catch ( e ) {}

		window.setTimeout( dongBoTo, 850 );
	}

	function dongBoTo() {
		if ( ! pageFlip || 'function' !== typeof pageFlip.getCurrentPageIndex ) {
			return;
		}

		var thuc = pageFlip.getCurrentPageIndex();

		if ( 'number' !== typeof thuc || thuc === toHT ) {
			return;
		}

		toHT    = thuc;
		trangHT = toHT + 1;

		capNhat();
		luuViTri();
		veToQuanh();
	}

	function truoc() {
		if ( ! pdf ) { return; }

		if ( 'lat' === cheDo ) {
			latTo( -1 );
			return;
		}

		toiTrang( trangHT - 1 );
	}

	function sau() {
		if ( ! pdf ) { return; }

		if ( 'lat' === cheDo ) {
			if ( CFG.xemThu && trangHT >= gioiHanXemThu() ) {
				baoHetXemThu();
				return;
			}

			latTo( 1 );
			return;
		}

		toiTrang( trangHT + 1 );
	}

	function capNhat() {

		if ( ! pdf ) { return; }

		if ( el.slider ) { el.slider.value = trangHT; }

		if ( el.percent ) {
			el.percent.textContent = Math.round( trangHT / soTrang * 100 ) + '%';
		}

		if ( el.chapter ) {
			var ten = tenChuong( trangHT );

			if ( ten ) {
				el.chapter.textContent = ten;
			} else if ( 'lat' === cheDo && 2 === buocTo() && to[ toHT + 1 ] && to[ toHT + 1 ].trang !== trangHT ) {

				el.chapter.textContent = CFG.i18n.trang + ' ' + trangHT + '–' + to[ toHT + 1 ].trang + ' / ' + soTrang;
			} else {
				el.chapter.textContent = CFG.i18n.trang + ' ' + trangHT + ' / ' + soTrang;
			}
		}

		if ( el.prev ) { el.prev.disabled = 'lat' !== cheDo && trangHT <= 1; }
		if ( el.next ) { el.next.disabled = 'lat' !== cheDo && trangHT >= soTrang; }

		if ( el.tocBody ) {
			el.tocBody.querySelectorAll( 'a' ).forEach( function ( a ) {
				a.classList.toggle( 'is-here', Number( a.dataset.trang ) === trangHT );
			} );
		}

		if ( el.mark ) {
			el.mark.setAttribute( 'aria-pressed', String( danhDauHienTai() === trangHT ) );
		}
	}

	function tenChuong( so ) {
		var ten = '';

		mucLuc.forEach( function ( m ) {
			if ( m.trang && m.trang <= so ) { ten = m.tieuDe; }
		} );

		return ten;
	}

	function hienDangTai( bat ) {
		if ( el.loading ) { el.loading.hidden = ! bat; }
	}

	var henLuu = 0;

	function luuViTri() {
		window.clearTimeout( henLuu );

		henLuu = window.setTimeout( function () {
			if ( ! CFG.dangNhap ) {
				try { window.localStorage.setItem( 'nntm-doc-' + CFG.objectId, String( trangHT ) ); } catch ( e ) {}
				return;
			}

			var body = new URLSearchParams();
			body.set( 'action', 'nntm_doc_tien_do' );
			body.set( 'nonce', CFG.nonce );
			body.set( 'object_id', CFG.objectId );
			body.set( 'trang', trangHT );

			fetch( CFG.ajaxUrl, { method: 'POST', credentials: 'same-origin', body: body } ).catch( function () {} );
		}, 1500 );
	}

	function viTriBanDau() {
		var tuServer = parseInt( CFG.viTri, 10 ) || 0;

		if ( tuServer > 0 ) { return tuServer; }

		try {
			return parseInt( window.localStorage.getItem( 'nntm-doc-' + CFG.objectId ), 10 ) || 1;
		} catch ( e ) {
			return 1;
		}
	}

	function danhDauHienTai() {
		try {
			return parseInt( window.localStorage.getItem( 'nntm-dau-' + CFG.objectId ), 10 ) || 0;
		} catch ( e ) {
			return 0;
		}
	}

	function doiDanhDau() {
		if ( ! pdf ) { return; }

		try {
			if ( danhDauHienTai() === trangHT ) {
				window.localStorage.removeItem( 'nntm-dau-' + CFG.objectId );
			} else {
				window.localStorage.setItem( 'nntm-dau-' + CFG.objectId, String( trangHT ) );
			}
		} catch ( e ) {}

		capNhat();
	}

	function dungMucLuc() {
		if ( ! el.tocBody ) { return Promise.resolve(); }

		return pdf.getOutline().then( function ( ds ) {
			if ( ! ds || ! ds.length ) {
				el.tocBody.innerHTML = '<p class="nntm-doc__toc-empty">' + esc( CFG.i18n.khongMucLuc ) + '</p>';
				return null;
			}

			var phang = [];

			( function di( ml, cap ) {
				ml.forEach( function ( m ) {
					phang.push( { tieuDe: m.title || '', dest: m.dest, cap: cap } );

					if ( m.items && m.items.length && cap < 2 ) { di( m.items, cap + 1 ); }
				} );
			}( ds, 0 ) );


			return Promise.all( phang.map( function ( m ) {
				return soTrangCuaDich( m.dest ).then( function ( so ) {
					m.trang = so;
					return m;
				} );
			} ) ).then( function ( xong ) {
				mucLuc = xong;

				el.tocBody.innerHTML = xong.map( function ( m ) {
					return '<a href="#" data-cap="' + m.cap + '" data-trang="' + ( m.trang || 1 ) + '">' + esc( m.tieuDe ) + '</a>';
				} ).join( '' );

				return xong;
			} );
		} ).catch( function () {
			el.tocBody.innerHTML = '<p class="nntm-doc__toc-empty">' + esc( CFG.i18n.khongMucLuc ) + '</p>';
			return null;
		} );
	}

	function soTrangCuaDich( dest ) {
		var lay = function ( d ) {
			if ( ! d || ! d[ 0 ] ) { return Promise.resolve( 0 ); }

			return pdf.getPageIndex( d[ 0 ] ).then( function ( i ) { return i + 1; } ).catch( function () { return 0; } );
		};

		if ( 'string' === typeof dest ) {
			return pdf.getDestination( dest ).then( lay ).catch( function () { return 0; } );
		}

		return lay( dest );
	}

	function veWatermark() {
		if ( ! el.water || ! CFG.watermark ) { return; }

		var dong = ( CFG.watermark + '   ·   ' ).repeat( 16 );
		var khoi = [];

		for ( var i = 0; i < 30; i++ ) { khoi.push( dong ); }

		el.water.textContent = khoi.join( '\n' );
	}

	function moDong( btn, hop ) {
		if ( ! btn || ! hop ) { return; }

		btn.addEventListener( 'click', function () {
			var mo = hop.hidden;

			[ el.toc, el.panel, el.music ].forEach( function ( h ) { if ( h ) { h.hidden = true; } } );
			[ el.tocBtn, el.panelBtn, el.musicBtn ].forEach( function ( b ) {
				if ( b ) {
					b.setAttribute( 'aria-expanded', 'false' );
					b.classList.remove( 'is-active' );
				}
			} );

			hop.hidden = ! mo;
			btn.setAttribute( 'aria-expanded', mo ? 'true' : 'false' );
			btn.classList.toggle( 'is-active', mo );
		} );
	}

	/*
	 * Bộ nhớ nhỏ của trình phát nhạc.
	 *
	 * Gói localStorage lại một chỗ vì trình phát đọc/ghi tới năm khoá (bài đang
	 * nghe, chỗ đang nghe, đang phát hay không, âm lượng, tắt tiếng). Chế độ ẩn
	 * danh của Safari làm setItem quăng lỗi; ở đây bỏ qua lặng lẽ để nhạc vẫn
	 * chạy, chỉ mất phần ghi nhớ.
	 */
	function nhoNhac( khoa, giaTri ) {
		try {
			if ( undefined === giaTri ) {
				return window.localStorage.getItem( 'nntm-doc-nhac-' + khoa );
			}

			window.localStorage.setItem( 'nntm-doc-nhac-' + khoa, String( giaTri ) );
		} catch ( e ) {}

		return null;
	}

	/** Số giây thành dạng 3:07 để in cạnh thanh tua. */
	function dongHo( giay ) {
		if ( ! isFinite( giay ) || giay < 0 ) { giay = 0; }

		var phut = Math.floor( giay / 60 );
		var le   = Math.floor( giay % 60 );

		return phut + ':' + ( le < 10 ? '0' : '' ) + le;
	}

	function noiNhac() {
		if ( ! el.music || ! el.musicAudio || ! el.musicPlay ) { return; }

		var tracks = Array.prototype.slice.call( el.music.querySelectorAll( '[data-nntm-doc-nhac-bai]' ) );
		if ( ! tracks.length ) { return; }

		var current = 0;

		/* Đang kéo thanh tua: lúc đó đừng để timeupdate giật con trượt về. */
		var dangKeo = false;

		/*
		 * Số bài lỗi liền nhau. Tệp nhạc bị xoá khỏi Thư viện là chuyện thường;
		 * không đếm thì cú "lỗi -> nhảy bài sau" thành vòng lặp vô tận khi cả
		 * danh sách đều hỏng.
		 */
		var loiLien = 0;

		/* Chỗ đang nghe của lần vào trước, chỉ dùng cho bài mở đầu tiên. */
		var viTriCu = 0;

		var luuBai = parseInt( nhoNhac( 'bai' ), 10 );
		if ( ! isNaN( luuBai ) && luuBai >= 0 && luuBai < tracks.length ) { current = luuBai; }

		var luuViTri = parseFloat( nhoNhac( 'vi-tri' ) );
		if ( ! isNaN( luuViTri ) && luuViTri > 0 ) { viTriCu = luuViTri; }

		var luuAm = parseFloat( nhoNhac( 'am' ) );
		el.musicAudio.volume = ( ! isNaN( luuAm ) && luuAm >= 0 && luuAm <= 1 ) ? luuAm : 0.7;
		el.musicAudio.muted  = '1' === nhoNhac( 'tat' );

		function playingState( isPlaying ) {
			var label = isPlaying ? el.musicPlay.dataset.labelPause : el.musicPlay.dataset.labelPlay;

			el.music.classList.toggle( 'is-playing', isPlaying );
			el.musicPlay.setAttribute( 'aria-pressed', isPlaying ? 'true' : 'false' );
			el.musicPlay.title = label || '';

			if ( el.musicBtn ) { el.musicBtn.classList.toggle( 'is-playing', isPlaying ); }

			nhoNhac( 'phat', isPlaying ? '1' : '0' );
		}

		/** Con trượt âm lượng và nút tắt tiếng vẽ theo đúng trạng thái của thẻ audio. */
		function veAmLuong() {
			var im = el.musicAudio.muted || 0 === el.musicAudio.volume;

			if ( el.musicVol ) { el.musicVol.value = String( Math.round( el.musicAudio.volume * 100 ) ); }

			if ( el.musicMute ) {
				var nhan = im ? el.musicMute.dataset.labelMo : el.musicMute.dataset.labelTat;

				el.musicMute.classList.toggle( 'is-muted', im );
				el.musicMute.setAttribute( 'aria-pressed', im ? 'true' : 'false' );
				el.musicMute.title = nhan || '';
			}
		}

		/** Thanh tua và hai mốc thời gian đi theo bài đang nghe. */
		function veThoiGian() {
			var dai = el.musicAudio.duration;
			var tai = el.musicAudio.currentTime;

			if ( el.musicAt ) { el.musicAt.textContent = dongHo( tai ); }
			if ( el.musicLen ) { el.musicLen.textContent = isFinite( dai ) ? dongHo( dai ) : '0:00'; }

			if ( el.musicSeek && ! dangKeo ) {
				el.musicSeek.value = String( isFinite( dai ) && dai > 0 ? Math.round( ( tai / dai ) * 1000 ) : 0 );
			}
		}

		function selectTrack( index, shouldPlay ) {
			current = ( index + tracks.length ) % tracks.length;

			var track = tracks[ current ];
			var wasPlaying = ! el.musicAudio.paused;
			var url = track.getAttribute( 'data-nntm-doc-nhac-url' ) || '';

			el.musicAudio.pause();
			el.musicAudio.src = url;
			el.musicAudio.load();

			tracks.forEach( function ( item, itemIndex ) {
				var active = itemIndex === current;
				item.classList.toggle( 'is-active', active );
				if ( active ) {
					item.setAttribute( 'aria-current', 'true' );
				} else {
					item.removeAttribute( 'aria-current' );
				}
			} );

			/*
			 * Tên bài lấy từ thuộc tính data thay vì cắt textContent: nút trong
			 * danh sách có kèm số thứ tự "01", mà tên bài cũng có thể bắt đầu bằng
			 * số, nên cắt bằng regex là trò may rủi.
			 */
			if ( el.musicTitle ) {
				el.musicTitle.textContent = track.getAttribute( 'data-nntm-doc-nhac-ten' ) || track.textContent.trim();
			}

			nhoNhac( 'bai', current );
			nhoNhac( 'vi-tri', 0 );

			if ( el.musicSeek ) { el.musicSeek.value = '0'; }
			veThoiGian();

			if ( shouldPlay || wasPlaying ) {
				var promise = el.musicAudio.play();
				if ( promise && promise.catch ) { promise.catch( function () { playingState( false ); } ); }
			} else {
				playingState( false );
			}
		}

		tracks.forEach( function ( track, index ) {
			track.addEventListener( 'click', function () { selectTrack( index, true ); } );
		} );

		el.musicPlay.addEventListener( 'click', function () {
			if ( el.musicAudio.paused ) {
				var promise = el.musicAudio.play();
				if ( promise && promise.catch ) { promise.catch( function () { playingState( false ); } ); }
			} else {
				el.musicAudio.pause();
			}
		} );

		if ( el.musicPrev ) { el.musicPrev.addEventListener( 'click', function () { selectTrack( current - 1, true ); } ); }
		if ( el.musicNext ) { el.musicNext.addEventListener( 'click', function () { selectTrack( current + 1, true ); } ); }

		if ( el.musicSeek ) {
			el.musicSeek.addEventListener( 'pointerdown', function () { dangKeo = true; } );
			el.musicSeek.addEventListener( 'pointerup', function () { dangKeo = false; } );

			/* Kéo tới đâu in mốc thời gian tới đó, chưa nhảy tiếng. */
			el.musicSeek.addEventListener( 'input', function () {
				var dai = el.musicAudio.duration;

				if ( ! isFinite( dai ) || dai <= 0 ) { return; }

				dangKeo = true;

				if ( el.musicAt ) { el.musicAt.textContent = dongHo( ( Number( el.musicSeek.value ) / 1000 ) * dai ); }
			} );

			/* Thả tay mới nhảy thật — tua liên tục trong lúc kéo là nghe rất rối. */
			el.musicSeek.addEventListener( 'change', function () {
				var dai = el.musicAudio.duration;

				dangKeo = false;

				if ( ! isFinite( dai ) || dai <= 0 ) { return; }

				try { el.musicAudio.currentTime = ( Number( el.musicSeek.value ) / 1000 ) * dai; } catch ( e ) {}

				veThoiGian();
			} );
		}

		if ( el.musicVol ) {
			el.musicVol.addEventListener( 'input', function () {
				var muc = Math.min( 100, Math.max( 0, Number( el.musicVol.value ) ) ) / 100;

				el.musicAudio.volume = muc;

				/* Kéo âm lượng lên thì hiểu là muốn nghe, tự bỏ tắt tiếng. */
				if ( muc > 0 && el.musicAudio.muted ) { el.musicAudio.muted = false; }

				nhoNhac( 'am', muc );
				nhoNhac( 'tat', el.musicAudio.muted ? '1' : '0' );
				veAmLuong();
			} );
		}

		if ( el.musicMute ) {
			el.musicMute.addEventListener( 'click', function () {
				el.musicAudio.muted = ! el.musicAudio.muted;

				/*
				 * Bật tiếng lại mà âm lượng đang ở 0 thì vẫn im — kéo lên một mức
				 * nghe được để nút không hoá ra vô tác dụng.
				 */
				if ( ! el.musicAudio.muted && 0 === el.musicAudio.volume ) {
					el.musicAudio.volume = 0.7;
					nhoNhac( 'am', 0.7 );
				}

				nhoNhac( 'tat', el.musicAudio.muted ? '1' : '0' );
				veAmLuong();
			} );
		}

		el.musicAudio.addEventListener( 'play', function () { playingState( true ); } );

		el.musicAudio.addEventListener( 'pause', function () {
			playingState( false );
			nhoNhac( 'vi-tri', el.musicAudio.currentTime );
		} );

		el.musicAudio.addEventListener( 'playing', function () { loiLien = 0; } );

		el.musicAudio.addEventListener( 'loadedmetadata', function () {
			var dai = el.musicAudio.duration;

			/*
			 * Bài không có ID3 nên máy chủ không biết thời lượng: điền vào lúc
			 * nghe tới nó, chứ không tải trước cả danh sách chỉ để lấy con số.
			 */
			var oDai = tracks[ current ].querySelector( '[data-nntm-doc-nhac-dai]' );

			if ( oDai && '' === oDai.textContent.trim() && isFinite( dai ) ) {
				oDai.textContent = dongHo( dai );
			}

			/*
			 * Nghe tiếp đúng chỗ của lần vào trước. Chừa 2 giây cuối: nối lại ngay
			 * sát cuối bài thì vừa mở đã hết, người ta tưởng nhạc hỏng.
			 */
			if ( viTriCu > 0 && isFinite( dai ) && viTriCu < dai - 2 ) {
				try { el.musicAudio.currentTime = viTriCu; } catch ( e ) {}
			}

			viTriCu = 0;
			veThoiGian();
		} );

		var mocLuu = 0;

		el.musicAudio.addEventListener( 'timeupdate', function () {
			veThoiGian();

			/* Ghi chỗ đang nghe mỗi 5 giây, đủ để nối lại mà không quần localStorage. */
			if ( Math.abs( el.musicAudio.currentTime - mocLuu ) >= 5 ) {
				mocLuu = el.musicAudio.currentTime;
				nhoNhac( 'vi-tri', mocLuu );
			}
		} );

		el.musicAudio.addEventListener( 'ended', function () { selectTrack( current + 1, true ); } );

		el.musicAudio.addEventListener( 'error', function () {
			playingState( false );

			/*
			 * Tệp đã bị xoá khỏi Thư viện thì bỏ qua, nghe bài sau. Dừng lại khi đã
			 * thử hết danh sách để không quay vòng vô tận.
			 */
			if ( tracks.length < 2 || loiLien >= tracks.length - 1 ) { return; }

			loiLien++;
			selectTrack( current + 1, true );
		} );

		if ( el.musicClose ) {
			el.musicClose.addEventListener( 'click', function () {
				el.music.hidden = true;
				if ( el.musicBtn ) {
					el.musicBtn.setAttribute( 'aria-expanded', 'false' );
					el.musicBtn.classList.remove( 'is-active' );
					el.musicBtn.focus();
				}
			} );
		}

		/*
		 * Đọc cờ TRƯỚC khi gọi selectTrack: selectTrack kết thúc bằng
		 * playingState( false ), tức là ghi cờ về '0' — đọc sau thì lần nào cũng
		 * thấy "không phát".
		 */
		var phatTiep = '1' === nhoNhac( 'phat' );

		veAmLuong();
		selectTrack( current, false );

		/*
		 * Lần trước đang nghe thì nghe tiếp. Trình duyệt có quyền chặn vì trang
		 * vừa tải chưa có cú bấm nào; bị chặn thì cứ nằm ở trạng thái tạm dừng,
		 * người đọc bấm phát là chạy — không nhảy hộp báo lỗi.
		 */
		if ( phatTiep ) {
			var tiep = el.musicAudio.play();
			if ( tiep && tiep.catch ) { tiep.catch( function () { playingState( false ); } ); }
		}
	}

	function noiNut() {
		if ( el.prev ) { el.prev.addEventListener( 'click', truoc ); }
		if ( el.next ) { el.next.addEventListener( 'click', sau ); }
		if ( el.mark ) { el.mark.addEventListener( 'click', doiDanhDau ); }

		moDong( el.tocBtn, el.toc );
		moDong( el.panelBtn, el.panel );
		moDong( el.musicBtn, el.music );
		noiNhac();

		/*
		 * Đóng/mở khung sách bên trái.
		 *
		 * Ở chế độ "lật", khổ trang được tính theo bề ngang khung đọc nên sau khi
		 * đóng hay mở phải vẽ lại, y như lúc đổi kích thước cửa sổ.
		 */
		function doiBen( dong ) {
			document.body.classList.toggle( 'is-ben-dong', dong );

			if ( el.benDong ) { el.benDong.setAttribute( 'aria-expanded', dong ? 'false' : 'true' ); }
			if ( el.benMo ) { el.benMo.setAttribute( 'aria-expanded', dong ? 'false' : 'true' ); }

			try { window.localStorage.setItem( 'nntm-doc-ben', dong ? 'dong' : 'mo' ); } catch ( e ) {}

			if ( ! pdf ) { return; }

			hienDangTai( true );
			veLai().then( function () { hienDangTai( false ); } );
		}

		if ( el.benDong ) {
			el.benDong.addEventListener( 'click', function () { doiBen( true ); } );
		}

		if ( el.benMo ) {
			el.benMo.addEventListener( 'click', function () { doiBen( false ); } );
		}

		if ( el.toc ) {
			el.toc.addEventListener( 'click', function ( e ) {
				var a = e.target.closest( 'a[data-trang]' );

				if ( ! a ) { return; }

				e.preventDefault();
				toiTrang( Number( a.dataset.trang ) );
				el.toc.hidden = true;
				el.tocBtn.setAttribute( 'aria-expanded', 'false' );
				el.tocBtn.classList.remove( 'is-active' );
			} );
		}

		if ( el.slider ) {
			el.slider.addEventListener( 'change', function () {
				toiTrang( parseInt( el.slider.value, 10 ) );
			} );
		}

		document.querySelectorAll( '[data-nntm-doc="nen"]' ).forEach( function ( b ) {
			b.addEventListener( 'click', function () {
				document.body.dataset.nen = b.dataset.nen;

				document.querySelectorAll( '[data-nntm-doc="nen"]' ).forEach( function ( x ) {
					x.classList.toggle( 'is-active', x === b );
				} );

				try { window.localStorage.setItem( 'nntm-doc-nen', b.dataset.nen ); } catch ( e ) {}
			} );
		} );

		var nutXem = {
			lat:  document.querySelector( '[data-nntm-doc="xem-lat"]' ),
			cuon: document.querySelector( '[data-nntm-doc="xem-cuon"]' )
		};

		function doiCheDo( moi ) {
			if ( cheDo === moi ) { return; }

			cheDo = moi;

			Object.keys( nutXem ).forEach( function ( k ) {
				if ( nutXem[ k ] ) { nutXem[ k ].classList.toggle( 'is-active', k === moi ); }
			} );

			try { window.localStorage.setItem( 'nntm-doc-xem', moi ); } catch ( e ) {}

			hienDangTai( true );
			veLai().then( function () { hienDangTai( false ); } );
		}

		Object.keys( nutXem ).forEach( function ( k ) {
			if ( nutXem[ k ] ) {
				nutXem[ k ].addEventListener( 'click', function () { doiCheDo( k ); } );
			}
		} );

		function doiFull() {
			if ( document.fullscreenElement ) {
				document.exitFullscreen();
			} else if ( document.documentElement.requestFullscreen ) {
				document.documentElement.requestFullscreen();
			}
		}

		var btnFull = document.querySelector( '[data-nntm-doc="toan-man-hinh"]' );

		if ( btnFull ) { btnFull.addEventListener( 'click', doiFull ); }

		document.addEventListener( 'keydown', function ( e ) {
			if ( /^(INPUT|TEXTAREA|SELECT)$/.test( e.target.tagName ) ) { return; }

			if ( 'ArrowLeft' === e.key || 'PageUp' === e.key ) { e.preventDefault(); truoc(); }
			if ( 'ArrowRight' === e.key || 'PageDown' === e.key ) { e.preventDefault(); sau(); }
			if ( 'Home' === e.key ) { e.preventDefault(); toiTrang( 1 ); }
			if ( 'End' === e.key ) { e.preventDefault(); toiTrang( soTrang ); }
			if ( 'f' === e.key || 'F' === e.key ) { doiFull(); }

			if ( 'Escape' === e.key ) {
				[ el.toc, el.panel, el.music ].forEach( function ( h ) { if ( h ) { h.hidden = true; } } );
				[ el.tocBtn, el.panelBtn, el.musicBtn ].forEach( function ( b ) {
					if ( b ) {
						b.setAttribute( 'aria-expanded', 'false' );
						b.classList.remove( 'is-active' );
					}
				} );
			}
		} );

		var henCuon = 0;

		el.stage.addEventListener( 'scroll', function () {
			if ( 'cuon' !== cheDo ) { return; }

			window.clearTimeout( henCuon );

			henCuon = window.setTimeout( function () {
				var moc = el.text.querySelectorAll( '[data-trang]' );
				var y   = el.stage.scrollTop + 80;

				for ( var i = moc.length - 1; i >= 0; i-- ) {
					if ( moc[ i ].offsetTop <= y ) {
						trangHT = Number( moc[ i ].dataset.trang );
						break;
					}
				}

				capNhat();
				luuViTri();

				if ( el.stage.scrollTop + el.stage.clientHeight > el.stage.scrollHeight - 600 && denTrang < soTrang ) {
					trangHT = Math.min( soTrang, denTrang );
					veCuon();
				}
			}, 120 );
		} );


		var henResize = 0;

		window.addEventListener( 'resize', function () {
			window.clearTimeout( henResize );

			henResize = window.setTimeout( function () {
				hienDangTai( true );
				veLai().then( function () { hienDangTai( false ); } );
			}, 400 );
		} );
	}

	try {
		var nenLuu = window.localStorage.getItem( 'nntm-doc-nen' );
		var xemLuu = window.localStorage.getItem( 'nntm-doc-xem' );
		var benLuu = window.localStorage.getItem( 'nntm-doc-ben' );

		if ( 'dong' === benLuu ) {
			document.body.classList.add( 'is-ben-dong' );

			var nutDongBen = document.querySelector( '[data-nntm-doc="dong-ben"]' );
			var nutMoBen   = document.querySelector( '[data-nntm-doc="mo-ben"]' );

			if ( nutDongBen ) { nutDongBen.setAttribute( 'aria-expanded', 'false' ); }
			if ( nutMoBen ) { nutMoBen.setAttribute( 'aria-expanded', 'false' ); }
		}


		window.localStorage.removeItem( 'nntm-doc-chu' );

		if ( nenLuu ) {
			document.body.dataset.nen = nenLuu;
			document.querySelectorAll( '[data-nntm-doc="nen"]' ).forEach( function ( x ) {
				x.classList.toggle( 'is-active', x.dataset.nen === nenLuu );
			} );
		}


		if ( xemLuu && [ 'lat', 'cuon' ].indexOf( xemLuu ) >= 0 ) {
			cheDo = xemLuu;
		}
	} catch ( e ) {}

	if ( ! coTep ) {
		document.body.dataset.xem = cheDo;

		document.querySelectorAll( '[data-nntm-doc^="xem-"]' ).forEach( function ( b ) {
			b.classList.toggle( 'is-active', b.dataset.nntmDoc === 'xem-' + cheDo );
		} );

		veWatermark();
		noiNut();
		hienDangTai( false );

		/*
		 * Không có tệp để mở thì nói rõ VÌ SAO ngay giữa khung đọc.
		 *
		 * Trước đây chỗ này để trống trơn — người chưa mua hoặc chưa trả lời câu
		 * hỏi vào tới nơi chỉ thấy một khung xám, không hiểu chuyện gì. Popup câu
		 * hỏi / khung thanh toán vẫn tự mở đè lên, nhưng đóng nó đi là lại trống.
		 */
		( function veLyDoKhoa() {
			var chu = {
				quiz: CFG.i18n.khoaQuiz,
				mua: CFG.i18n.khoaMua,
				'thieu-tep': CFG.i18n.khoaThieu
			}[ CFG.lyDoKhoa ];

			if ( ! chu || ! el.stage ) { return; }

			var hop = document.createElement( 'div' );
			hop.className = 'nntm-doc__khoa';

			var p = document.createElement( 'p' );
			p.className = 'nntm-doc__khoa-chu';
			p.textContent = chu;
			hop.appendChild( p );

			/*
			 * Chỉ chế độ "mua" mới có nút ở đây. Popup câu hỏi tự mở sẵn nên thêm
			 * nút nữa là thừa; còn "thiếu tệp" thì người đọc chẳng làm gì được.
			 */
			if ( 'mua' === CFG.lyDoKhoa ) {
				var nut = document.createElement( 'button' );
				nut.type = 'button';
				nut.className = 'nntm-doc__khoa-nut';
				nut.setAttribute( 'data-nntm-tt-mua', '' );
				nut.textContent = CFG.i18n.khoaMua;
				hop.appendChild( nut );
			}

			el.stage.appendChild( hop );
		} )();

		if ( el.tocBody ) {
			el.tocBody.textContent = CFG.i18n.khongMucLuc;
		}

		[ el.prev, el.next, el.slider, el.mark ].forEach( function ( x ) {
			if ( x ) { x.disabled = true; }
		} );

		return;
	}

	pdfjsLib.getDocument( { url: CFG.pdfUrl } ).promise.then( function ( tep ) {
		pdf     = tep;
		soTrang = tep.numPages;
		trangHT = Math.min( viTriBanDau(), soTrang );

		if ( el.slider ) { el.slider.max = soTrang; }

		document.querySelectorAll( '[data-nntm-doc^="xem-"]' ).forEach( function ( b ) {
			b.classList.toggle( 'is-active', b.dataset.nntmDoc === 'xem-' + cheDo );
		} );

		veWatermark();
		noiNut();

		return dungMucLuc().then( veLai ).then( function () {
			hienDangTai( false );
			capNhat();
		} );
	} ).catch( function ( loi ) {
		/*
		 * Khối catch này bọc CẢ chuỗi: mở tệp, dựng mục lục, vẽ trang. Trước đây
		 * nó nuốt lỗi không dấu vết, nên hỏng ở bước vẽ cũng hiện đúng câu "không
		 * mở được tệp" — đi tìm nguyên nhân rất mất công. Ghi lỗi thật ra console,
		 * còn người đọc vẫn chỉ thấy câu tử tế.
		 */
		if ( window.console && console.error ) {
			console.error( '[nntm-doc-sach] khong dung duoc trinh doc:', loi );
		}

		if ( el.loading ) {
			el.loading.hidden = false;
			el.loading.className = 'nntm-doc__error';
			el.loading.textContent = CFG.i18n.loi;
		}
	} );
}() );
