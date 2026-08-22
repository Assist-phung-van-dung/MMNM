/**
 * Trình đọc ấn phẩm — /an-pham/{slug}/doc/
 *
 * BA CÁCH XEM, chọn ở bảng `Aa`:
 *   lát  — lật 3D như sách thật (mặc định)
 *   cuộn — chữ chảy liên tục, cuộn dọc; nhẹ và thân thiện với màn nhỏ
 *   gốc  — ảnh trang PDF đúng bản in, cho trang có bảng/sơ đồ
 *
 * ĐIỀU KHÓ NHẤT LÀ LẬT 3D VỚI CHỮ CHẢY:
 * page-flip cần những "tờ" có kích thước cố định, còn chữ chảy thì không biết
 * trước bao nhiêu chữ vừa một tờ. Nên phải TỰ CHIA TỜ: dựng một hộp đo thầm
 * đúng bằng khổ tờ, cùng cỡ chữ cùng giãn dòng, rồi nhồi từng khối chữ vào cho
 * đến khi tràn thì cắt sang tờ mới. Đoạn nào một mình đã tràn thì cắt theo từ,
 * dò bằng chia đôi để không phải thử từng từ một.
 *
 * Đổi cỡ chữ, đổi khổ cửa sổ hay đổi nền đều phải chia tờ lại — số chữ vừa một
 * tờ đã khác. Vì vậy mọi lần đổi đều đi qua `veLai()`.
 */
( function () {
	'use strict';

	if ( 'undefined' === typeof nntmDocSach ) {
		return;
	}

	var CFG = nntmDocSach;

	/*
	 * Ấn phẩm chưa gắn tệp thì inc/doc-sach.php truyền pdfUrl rỗng và KHÔNG nạp
	 * pdf.js — nên không được coi thiếu pdfjsLib là lỗi mà thoát sớm. Bộ khung
	 * vẫn phải dựng đủ, chỉ khung sách để trống.
	 */
	var coTep = !! CFG.pdfUrl && 'undefined' !== typeof pdfjsLib;

	if ( coTep ) {
		pdfjsLib.GlobalWorkerOptions.workerSrc = CFG.workerUrl;
	}

	// Chỉ dùng khi hỏi tỉ lệ trang PDF thất bại. Bình thường lấy tỉ lệ thật của
	// trang, vì sách khổ vuông hay khổ ngang mà áp con số này thì méo.
	var TY_LE_TO = 1.42;
	// Ruột sân khấu rộng từ mức này trở lên thì mở hai tờ. Dưới mức đó, mỗi tờ
	// hẹp quá, dòng chữ ngắn tới mức khó đọc, nên mở một tờ cho lành.
	var NGUONG_DOI_TO = 760;
	var CUA_SO   = 6;      // số trang dựng sẵn quanh chỗ đang đọc, cho chế độ cuộn
	var GAN      = 4;      // vẽ sẵn bao nhiêu tờ mỗi bên chỗ đang đọc
	var XA       = 10;     // ra ngoài khoảng này thì xoá ảnh tờ cho nhẹ máy

	var el = {
		stage:    document.querySelector( '[data-nntm-doc="stage"]' ),
		loading:  document.querySelector( '[data-nntm-doc="dang-tai"]' ),
		text:     document.querySelector( '[data-nntm-doc="chu-sach"]' ),
		toc:      document.getElementById( 'nntm-doc-toc' ),
		tocBody:  document.querySelector( '[data-nntm-doc="toc-body"]' ),
		tocBtn:   document.querySelector( '[data-nntm-doc="muc-luc"]' ),
		panel:    document.getElementById( 'nntm-doc-hien' ),
		panelBtn: document.querySelector( '[data-nntm-doc="hien"]' ),
		mark:     document.querySelector( '[data-nntm-doc="danh-dau"]' ),
		prev:     document.querySelector( '[data-nntm-doc="truoc"]' ),
		next:     document.querySelector( '[data-nntm-doc="sau"]' ),
		slider:   document.querySelector( '[data-nntm-doc="thanh-truot"]' ),
		percent:  document.querySelector( '[data-nntm-doc="phan-tram"]' ),
		chapter:  document.querySelector( '[data-nntm-doc="chuong"]' ),
		water:    document.querySelector( '[data-nntm-doc="watermark"]' )
	};

	var pdf     = null;
	var soTrang = 0;
	var trangHT = 1;         // trang PDF đang đọc
	var cheDo   = 'lat';     // 'lat' | 'cuon'
	var mucLuc  = [];
	var khoiTheoTrang = {};  // trang PDF -> [{loai, chu}] , bóc một lần rồi dùng lại
	var to      = [];        // danh sách tờ: { trang, el, xong, dangVe, viec }
	var tyLeTrang = 0;       // cao / rộng của trang PDF thật, đo một lần ở trang 1
	var toHT    = 0;         // chỉ số tờ đang mở
	var pageFlip = null;
	var tuTrang = 1;
	var denTrang = 0;

	/* =====================================================================
	 * Bóc chữ một trang PDF thành các khối
	 * ===================================================================== */

	/**
	 * Gom các mẩu chữ của một trang thành danh sách dòng.
	 *
	 * pdf.js trả từng mẩu rời kèm ma trận biến đổi: `transform[4]` là hoành độ,
	 * `transform[5]` là tung độ (gốc toạ độ PDF ở đáy trang). Mẩu nào tung độ
	 * gần nhau thì cùng một dòng; sai số lấy theo chiều cao chữ chứ không phải
	 * một số cố định, vì cỡ chữ mỗi sách mỗi khác.
	 *
	 * @param {Object} noiDung Kết quả page.getTextContent().
	 * @return {Array} Danh sách dòng.
	 */
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

	/**
	 * Gộp dòng thành đoạn, nhận diện tiêu đề.
	 *
	 * Hai dấu hiệu mở đoạn dùng cùng nhau vì mỗi cái lẻ đều sai: dòng thụt vào
	 * so với lề chung, và khoảng trắng dọc lớn hơn hẳn giãn dòng thường.
	 *
	 * @param {Array} dong Danh sách dòng.
	 * @return {Array} [{ loai: 'p'|'h', chu }]
	 */
	function gopDoan( dong ) {
		if ( ! dong.length ) {
			return [];
		}

		// Lề chung = hoành độ HAY GẶP NHẤT, không phải nhỏ nhất: dòng thụt đầu
		// đoạn hay số trang lẻ có thể lệch hẳn ra ngoài.
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

				// Dòng trên kết thúc bằng gạch nối là từ bị cắt ngang dòng —
				// bỏ gạch, dán liền.
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

	/**
	 * Khối chữ của một trang PDF, bóc một lần rồi giữ lại.
	 *
	 * @param {number} so Số trang.
	 * @return {Promise<Array>}
	 */
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

	/* =====================================================================
	 * Khổ tờ
	 * ===================================================================== */

	/**
	 * Tỉ lệ cao/rộng của trang PDF, hỏi một lần ở trang đầu rồi giữ lại.
	 *
	 * Tờ trong reader phải cùng tỉ lệ với trang in, nếu không thì ảnh trang co
	 * lại để vừa và chừa hai vệt trống hai bên — trông như dán ảnh chứ không
	 * còn ra quyển sách.
	 *
	 * @return {Promise<number>}
	 */
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

	/**
	 * Khổ một tờ, tính từ khung đọc.
	 *
	 * Trên màn rộng thì mở hai tờ cạnh nhau như sách thật; màn hẹp thì một tờ,
	 * vì hai tờ trên điện thoại thì chữ nhỏ đến mức không đọc được.
	 *
	 * @return {Object} { rong, cao, doi }
	 */
	function khoTo() {
		// LỖI ĐÃ SỬA 21/08/2026: bản trước lấy clientWidth, tức là ĐÃ TÍNH CẢ
		// padding của sân khấu (96px mỗi bên, chỗ để hai mũi tên). Quyển sách vì
		// thế rộng hơn phần ruột thật, tràn ra ngoài rồi bị `overflow: hidden`
		// cắt mất một khúc trang bên phải, và còn đè lên hai nút lật. Phải trừ
		// padding ra để lấy đúng khổ ruột.
		var cs = window.getComputedStyle( el.stage );
		var W  = el.stage.clientWidth  - ( parseFloat( cs.paddingLeft ) || 0 ) - ( parseFloat( cs.paddingRight ) || 0 );
		var H  = el.stage.clientHeight - ( parseFloat( cs.paddingTop )  || 0 ) - ( parseFloat( cs.paddingBottom ) || 0 );
		var doi = W >= NGUONG_DOI_TO;
		var ty  = tyLeTrang || TY_LE_TO;

		var rong = doi ? ( W - 24 ) / 2 : W - 16;
		var cao  = rong * ty;

		// Cao quá khung thì lấy chiều cao làm chuẩn rồi suy ngược ra chiều rộng.
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

	/* =====================================================================
	 * Vẽ — ba chế độ
	 * ===================================================================== */

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

	/**
	 * Dải trang cần chia tờ quanh chỗ đang đọc.
	 *
	 * @return {number[]}
	 */
	function daiTrang() {
		tuTrang  = Math.max( 1, Math.min( soTrang, trangHT ) );
		denTrang = Math.min( soTrang, tuTrang + CUA_SO - 1 );

		var ds = [];

		for ( var i = tuTrang; i <= denTrang; i++ ) {
			ds.push( i );
		}

		return ds;
	}

	/**
	 * Vẽ chế độ lật 3D — mỗi tờ là ẢNH của đúng một trang PDF.
	 *
	 * ĐỔI CÁCH LÀM 21/08/2026: bản trước bóc chữ ra rồi chảy lại thành tờ. Cách
	 * đó có ba tật mà người dùng chỉ ra: hình vẽ trong sách mất sạch (getTextContent
	 * chỉ trả về chữ), một trang PDF bị xé thành nhiều tờ, và tờ thì ngắn.
	 *
	 * Giờ vẽ thẳng trang PDF lên canvas: hình với chữ hiện đúng như bản in, một
	 * trang PDF nằm gọn trong một tờ, và tờ lấy đúng tỉ lệ trang in. Chữ chảy lại
	 * đổi được cỡ vẫn còn ở chế độ cuộn.
	 *
	 * Dựng đủ số tờ ngay từ đầu — tờ rỗng thì nhẹ — nên bỏ luôn được cái cửa sổ
	 * trang và hàm nối trang, thứ vốn hay làm lệch chỉ số. Chỉ số tờ giờ đúng
	 * bằng số trang trừ một.
	 *
	 * @return {Promise}
	 */
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
				// Sách chữ không có bìa cứng ở giữa nội dung — showCover chỉ
				// đúng khi tờ đầu thật là bìa.
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

	/**
	 * Biến danh sách tờ thành node cho page-flip.
	 *
	 * Tờ tạo ra là tờ rỗng: chỗ đặt ảnh để trống, tới gần lúc đọc mới vẽ. Dựng
	 * sẵn cả trăm canvas thì tốn bộ nhớ vô ích và treo máy lúc mở sách.
	 *
	 * @param {Array} ds Danh sách tờ.
	 * @return {NodeList}
	 */
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

		// Giữ tham chiếu thẻ để lát vẽ ảnh vào đúng tờ. page-flip bốc mấy thẻ này
		// sang khung của nó nhưng vẫn là cùng một node, nên tham chiếu còn dùng được.
		ds.forEach( function ( t, i ) { t.el = ds2[ i ] || null; } );

		// page-flip bốc xong thì để lại cái hộp tạm rỗng. Không dọn thì mỗi lần
		// dựng lại sách lại thừa một hộp nằm trong sân khấu.
		window.setTimeout( function () {
			if ( kho.parentNode ) { kho.parentNode.removeChild( kho ); }
		}, 0 );

		return ds2;
	}

	/**
	 * Vẽ ảnh cho mấy tờ quanh chỗ đang đọc, và xoá ảnh mấy tờ đã đi xa.
	 *
	 * @return {Promise}
	 */
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

		// Một trang khổ A4 vẽ ở 2x là hơn chục megabyte bộ nhớ ảnh. Sách vài trăm
		// trang mà giữ hết thì máy đứng, nên tờ nào đi xa là xoá ảnh, lúc quay lại
		// vẽ lại — vẽ một trang chỉ mất chừng trăm mili giây.
		to.forEach( function ( t, i ) {
			if ( i < toHT - XA || i > toHT + XA ) { xoaAnhTo( t ); }
		} );

		return Promise.all( viec );
	}

	/**
	 * Vẽ một trang PDF lên canvas rồi đặt vào tờ.
	 *
	 * @param {Object} t Tờ.
	 * @return {Promise}
	 */
	function veToCanvas( t ) {
		if ( ! t || ! t.el || t.xong || t.dangVe ) {
			return Promise.resolve();
		}

		t.dangVe = true;

		return pdf.getPage( t.trang ).then( function ( trang ) {
			var kho = khoTo();
			var goc = trang.getViewport( { scale: 1 } );
			// Vẽ theo mật độ điểm của màn, chặn ở 2x: 3x trên màn điện thoại chỉ
			// tốn thêm bộ nhớ chứ mắt không thấy khác.
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

	/**
	 * Bỏ ảnh của một tờ, trả bộ nhớ lại cho máy.
	 *
	 * @param {Object} t Tờ.
	 */
	function xoaAnhTo( t ) {
		if ( ! t || ! t.el || ! t.xong ) {
			return;
		}

		var hop = t.el.querySelector( '[data-anh]' );

		if ( hop ) { hop.textContent = ''; }

		t.xong = false;
	}

	/**
	 * Huỷ mọi việc vẽ đang dở và bỏ tham chiếu thẻ.
	 */
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

	/**
	 * Vẽ chế độ cuộn.
	 *
	 * @return {Promise}
	 */
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

	/**
	 * Vẽ lại theo chế độ đang chọn.
	 *
	 * BỎ 21/08/2026: từng có chế độ thứ ba là "bản gốc" — vẽ ảnh trang PDF ra
	 * giữa khung, để đọc mấy trang mà bóc chữ không ra. Từ lúc chế độ lật vẽ
	 * thẳng trang PDF thì hai chế độ làm y hệt một việc, chỉ khác là bản gốc
	 * không lật được. Bỏ đi cho gọn.
	 *
	 * @return {Promise}
	 */
	function veLai() {
		/*
		 * Không có tệp thì không có gì để chia tờ. Vẫn ghi data-xem để bố cục
		 * theo cách xem đang chọn, rồi trả về Promise đã xong — doiCheDo() nối
		 * .then() vào đây, trả undefined là vỡ.
		 */
		if ( ! pdf ) {
			document.body.dataset.xem = cheDo;
			return Promise.resolve();
		}

		document.body.dataset.xem = cheDo;

		el.text.hidden = 'cuon' !== cheDo;

		return 'cuon' === cheDo ? veCuon() : veLat();
	}

	/* =====================================================================
	 * Chuyển trang
	 * ===================================================================== */

	function toiTrang( so ) {
		if ( ! pdf ) { return; }

		so = Math.max( 1, Math.min( soTrang, so | 0 ) );

		if ( 'lat' === cheDo ) {
			// Một trang PDF là một tờ, nên chỉ số tờ đúng bằng số trang trừ một —
			// không phải dò trong danh sách như hồi còn xé trang thành nhiều tờ.
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

	/**
	 * Số tờ mỗi lần lật: mở hai tờ cạnh nhau thì lật cả cặp, một tờ thì lật một.
	 *
	 * @return {number}
	 */
	function buocTo() {
		if ( pageFlip && 'function' === typeof pageFlip.getOrientation ) {
			return 'landscape' === pageFlip.getOrientation() ? 2 : 1;
		}

		return khoTo().doi ? 2 : 1;
	}

	/**
	 * Lật một bước, tự tính tờ đích.
	 *
	 * ĐO THẬT 21/08/2026: `flipNext()` ở cặp trang ĐẦU không làm gì cả — bấm lần
	 * một không đổi trang, từ lần hai mới nhảy (1 → 1 → 3 → 5). Đó là tật bên
	 * trong page-flip khi đang mở hai tờ và chỉ số hiện tại là 0.
	 *
	 * Nên không dùng flipNext/flipPrev nữa: tự tính chỉ số tờ đích rồi gọi
	 * `flip()`, thứ vốn vẫn chạy đúng. Sau khi lật còn hỏi lại thư viện xem nó
	 * đang ở tờ nào để đồng bộ — có lần nó lật mà không phát sự kiện.
	 *
	 * Ghi thêm: tật "bấm lần đầu không đổi trang" quan sát được hôm đó lại còn do
	 * tab chạy ẩn — requestAnimationFrame đứng hẳn nên hoạt cảnh lật treo giữa
	 * đường. Trình duyệt thật không bị.
	 *
	 * @param {number} huong -1 lùi, 1 tiến.
	 */
	function latTo( huong ) {
		if ( ! pageFlip || ! to.length ) {
			return;
		}

		var dich = Math.max( 0, Math.min( to.length - 1, toHT + huong * buocTo() ) );

		// Đã ở tờ đầu hoặc tờ cuối. Mọi trang đều dựng sẵn nên hết là hết thật.
		if ( dich === toHT ) {
			return;
		}

		try {
			pageFlip.flip( dich );
		} catch ( e ) {}

		window.setTimeout( dongBoTo, 850 );
	}

	/**
	 * Hỏi lại page-flip đang ở tờ nào rồi cập nhật thanh trạng thái.
	 */
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
			latTo( 1 );
			return;
		}

		toiTrang( trangHT + 1 );
	}

	/* =====================================================================
	 * Thanh trạng thái
	 * ===================================================================== */

	function capNhat() {
		// Không có tệp: soTrang = 0, trangHT/soTrang ra NaN nên thanh dưới sẽ
		// ghi "NaN%". Giữ nguyên "0%" và ô chương trống mà template đã in.
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
				// Đang mở hai tờ thì nói cả hai trang. Ghi một số trong khi mắt
				// đang đọc hai trang là nói không đúng thứ người ta thấy.
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

	/* =====================================================================
	 * Nhớ chỗ đang đọc và đánh dấu
	 * ===================================================================== */

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

	/* =====================================================================
	 * Mục lục
	 * ===================================================================== */

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

			// Mục lục PDF trỏ tới "đích" nội bộ, không kèm số trang — phải hỏi
			// pdf.js từng cái. Làm một lần lúc mở sách rồi dùng lại.
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

	/* =====================================================================
	 * Watermark
	 * ===================================================================== */

	function veWatermark() {
		if ( ! el.water || ! CFG.watermark ) { return; }

		var dong = ( CFG.watermark + '   ·   ' ).repeat( 16 );
		var khoi = [];

		for ( var i = 0; i < 30; i++ ) { khoi.push( dong ); }

		el.water.textContent = khoi.join( '\n' );
	}

	/* =====================================================================
	 * Nối các nút
	 * ===================================================================== */

	function moDong( btn, hop ) {
		if ( ! btn || ! hop ) { return; }

		btn.addEventListener( 'click', function () {
			var mo = hop.hidden;

			[ el.toc, el.panel ].forEach( function ( h ) { if ( h ) { h.hidden = true; } } );
			[ el.tocBtn, el.panelBtn ].forEach( function ( b ) {
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

	function noiNut() {
		if ( el.prev ) { el.prev.addEventListener( 'click', truoc ); }
		if ( el.next ) { el.next.addEventListener( 'click', sau ); }
		if ( el.mark ) { el.mark.addEventListener( 'click', doiDanhDau ); }

		moDong( el.tocBtn, el.toc );
		moDong( el.panelBtn, el.panel );

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

		// Nền đọc. Không cần chia tờ lại — chỉ đổi màu, không đổi khổ chữ.
		document.querySelectorAll( '[data-nntm-doc="nen"]' ).forEach( function ( b ) {
			b.addEventListener( 'click', function () {
				document.body.dataset.nen = b.dataset.nen;

				document.querySelectorAll( '[data-nntm-doc="nen"]' ).forEach( function ( x ) {
					x.classList.toggle( 'is-active', x === b );
				} );

				try { window.localStorage.setItem( 'nntm-doc-nen', b.dataset.nen ); } catch ( e ) {}
			} );
		} );

		// Hai chế độ xem.
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

		// Toàn màn hình.
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
				[ el.toc, el.panel ].forEach( function ( h ) { if ( h ) { h.hidden = true; } } );
				[ el.tocBtn, el.panelBtn ].forEach( function ( b ) {
					if ( b ) {
						b.setAttribute( 'aria-expanded', 'false' );
						b.classList.remove( 'is-active' );
					}
				} );
			}
		} );

		// Cuộn: chỉ có nghĩa ở chế độ cuộn.
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

		// Đổi khổ cửa sổ: khổ tờ đổi theo, phải chia lại. Hoãn để không chia
		// hàng chục lần trong lúc người dùng đang kéo.
		var henResize = 0;

		window.addEventListener( 'resize', function () {
			window.clearTimeout( henResize );

			henResize = window.setTimeout( function () {
				hienDangTai( true );
				veLai().then( function () { hienDangTai( false ); } );
			}, 400 );
		} );
	}

	/* =====================================================================
	 * Khởi động
	 * ===================================================================== */

	try {
		var nenLuu = window.localStorage.getItem( 'nntm-doc-nen' );
		var xemLuu = window.localStorage.getItem( 'nntm-doc-xem' );

		// Cỡ chữ đã bỏ, nên khoá 'nntm-doc-chu' của bản cũ giờ vô nghĩa. Dọn luôn
		// để máy ai từng đọc rồi không giữ rác trong localStorage.
		window.localStorage.removeItem( 'nntm-doc-chu' );

		if ( nenLuu ) {
			document.body.dataset.nen = nenLuu;
			document.querySelectorAll( '[data-nntm-doc="nen"]' ).forEach( function ( x ) {
				x.classList.toggle( 'is-active', x.dataset.nen === nenLuu );
			} );
		}

		// Máy ai từng chọn 'goc' thì rơi về 'lat' — chế độ lật giờ cũng vẽ đúng
		// trang PDF nên không mất gì.
		if ( xemLuu && [ 'lat', 'cuon' ].indexOf( xemLuu ) >= 0 ) {
			cheDo = xemLuu;
		}
	} catch ( e ) {}

	/*
	 * Ấn phẩm chưa gắn tệp: dựng đúng bộ khung như lúc có tệp, chỗ trang sách để
	 * TRỐNG — không câu báo lỗi, không ảnh thay thế. Chủ dự án chốt 22/08/2026.
	 *
	 * Vẫn nối nút để đổi nền, đổi cách xem, mục lục, toàn màn hình chạy như
	 * thường; riêng những nút chỉ có nghĩa khi có trang (lật trước/sau, thanh
	 * trượt, đánh dấu) thì khoá lại — để đó bấm được mà không xảy ra gì là nói
	 * dối với người dùng.
	 */
	if ( ! coTep ) {
		document.body.dataset.xem = cheDo;

		document.querySelectorAll( '[data-nntm-doc^="xem-"]' ).forEach( function ( b ) {
			b.classList.toggle( 'is-active', b.dataset.nntmDoc === 'xem-' + cheDo );
		} );

		veWatermark();
		noiNut();
		hienDangTai( false );

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
	} ).catch( function () {
		if ( el.loading ) {
			el.loading.hidden = false;
			el.loading.className = 'nntm-doc__error';
			el.loading.textContent = CFG.i18n.loi;
		}
	} );
}() );
