/**
 * Từ khoá động (phiếu khảo sát câu 33–34).
 *
 * Dò các từ khoá BQT khai báo trong vùng nội dung chính, bọc lại bằng
 * <span class="nntm-tkd">. Rê chuột / focus bàn phím / chạm vào thì hiện thẻ
 * minh hoạ nổi cạnh chữ. Chỉ một thẻ dùng chung cho cả trang.
 *
 * Không đụng vào chữ nằm trong liên kết, nút, tiêu đề, ô nhập, mã — bọc chữ
 * trong liên kết sẽ làm hỏng việc bấm, bọc tiêu đề làm rối thiết kế.
 *
 * Mọi nội dung đưa vào thẻ dùng textContent / thuộc tính, không dùng innerHTML.
 */
( function () {
	'use strict';

	var cfg = window.nntmTuKhoaDong;

	if ( ! cfg || ! cfg.tuKhoa || ! cfg.tuKhoa.length ) {
		return;
	}

	var vung = document.querySelector( cfg.vungDo || '#nntm-noi-dung-chinh' ) || document.querySelector( 'main' );

	if ( ! vung || ! window.TreeWalker ) {
		return;
	}

	var SO_LAN = Math.max( 1, parseInt( cfg.soLan, 10 ) || 1 );
	var TRE_AN = 160;
	var LE = 16;
	var KHOANG = 12;

	var BO_QUA = {
		A: 1, BUTTON: 1, INPUT: 1, TEXTAREA: 1, SELECT: 1, OPTION: 1, LABEL: 1,
		SCRIPT: 1, STYLE: 1, NOSCRIPT: 1, TEMPLATE: 1, IFRAME: 1, SVG: 1,
		CODE: 1, PRE: 1, KBD: 1,
		H1: 1, H2: 1, H3: 1, H4: 1, H5: 1, H6: 1,
		NAV: 1, HEADER: 1, FOOTER: 1, FORM: 1, DIALOG: 1
	};

	/* ---------- Dựng biểu thức dò ---------- */

	var theoChu = Object.create( null );
	var cacChu = [];

	function thuong( s ) {
		return String( s ).normalize ? String( s ).normalize( 'NFC' ).toLowerCase() : String( s ).toLowerCase();
	}

	function thoat( s ) {
		return s.replace( /[.*+?^${}()|[\]\\]/g, '\\$&' );
	}

	cfg.tuKhoa.forEach( function ( muc ) {
		( muc.tu || [] ).forEach( function ( tu ) {
			tu = String( tu || '' ).trim();
			if ( tu.length < 2 ) {
				return;
			}

			var khoa = thuong( tu );
			if ( theoChu[ khoa ] ) {
				return;
			}

			theoChu[ khoa ] = muc;
			cacChu.push( tu.normalize ? tu.normalize( 'NFC' ) : tu );

			// Nội dung dán từ máy Mac đôi khi ở dạng dựng sẵn dấu rời (NFD).
			if ( tu.normalize ) {
				var nfd = tu.normalize( 'NFD' );
				if ( nfd !== cacChu[ cacChu.length - 1 ] ) {
					theoChu[ thuong( nfd ) ] = muc;
					cacChu.push( nfd );
				}
			}
		} );
	} );

	if ( ! cacChu.length ) {
		return;
	}

	// Dài trước ngắn sau: "Đức Phật" phải thắng "Phật".
	cacChu.sort( function ( a, b ) {
		return b.length - a.length;
	} );

	var bieuThuc;

	try {
		// Nhóm 1 là ký tự đứng trước (hoặc đầu chuỗi) để giả lập ranh giới từ
		// cho chữ có dấu — \b của JS chỉ hiểu chữ Latin không dấu.
		bieuThuc = new RegExp(
			'(^|[^\\p{L}\\p{M}\\p{N}])(' + cacChu.map( thoat ).join( '|' ) + ')(?=[^\\p{L}\\p{M}\\p{N}]|$)',
			'giu'
		);
	} catch ( e ) {
		return;
	}

	/* ---------- Gom các nút chữ cần xử lý ---------- */

	var walker = document.createTreeWalker( vung, NodeFilter.SHOW_ELEMENT | NodeFilter.SHOW_TEXT, {
		acceptNode: function ( node ) {
			if ( node.nodeType === 1 ) {
				if (
					BO_QUA[ node.nodeName.toUpperCase() ] ||
					node.isContentEditable ||
					node.hasAttribute( 'data-nntm-tkd-bo-qua' ) ||
					node.getAttribute( 'aria-hidden' ) === 'true' ||
					node.classList.contains( 'nntm-tkd' )
				) {
					return NodeFilter.FILTER_REJECT;
				}
				return NodeFilter.FILTER_SKIP;
			}

			return node.nodeValue && node.nodeValue.trim().length > 1
				? NodeFilter.FILTER_ACCEPT
				: NodeFilter.FILTER_REJECT;
		}
	} );

	var nutChu = [];
	while ( walker.nextNode() ) {
		nutChu.push( walker.currentNode );
	}

	/* ---------- Bọc từ khoá ---------- */

	var daGan = Object.create( null );
	var coDung = [];

	function boc( nut ) {
		var chu = nut.nodeValue;
		var m;
		var viTri = 0;
		var manh = null;

		bieuThuc.lastIndex = 0;

		while ( ( m = bieuThuc.exec( chu ) ) !== null ) {
			var muc = theoChu[ thuong( m[ 2 ] ) ];
			var dem = muc ? ( daGan[ muc.id ] || 0 ) : SO_LAN;

			if ( dem >= SO_LAN ) {
				continue;
			}

			daGan[ muc.id ] = dem + 1;

			if ( ! manh ) {
				manh = document.createDocumentFragment();
			}

			var batDau = m.index + m[ 1 ].length;
			manh.appendChild( document.createTextNode( chu.slice( viTri, batDau ) ) );

			var span = document.createElement( 'span' );
			span.className = 'nntm-tkd nntm-tkd--' + ( muc.kieu === 'anh' ? 'anh' : 'the' );
			span.textContent = m[ 2 ];
			span.tabIndex = 0;
			// aria-expanded chỉ hợp lệ trên phần tử có vai trò tương tác.
			span.setAttribute( 'role', 'button' );
			span.setAttribute( 'data-nntm-tkd', String( muc.id ) );
			span.setAttribute( 'aria-expanded', 'false' );
			manh.appendChild( span );

			if ( coDung.indexOf( muc ) === -1 ) {
				coDung.push( muc );
			}

			viTri = batDau + m[ 2 ].length;
		}

		if ( manh ) {
			manh.appendChild( document.createTextNode( chu.slice( viTri ) ) );
			nut.parentNode.replaceChild( manh, nut );
		}
	}

	nutChu.forEach( boc );

	if ( ! coDung.length ) {
		return;
	}

	var theoId = Object.create( null );
	cfg.tuKhoa.forEach( function ( muc ) {
		theoId[ String( muc.id ) ] = muc;
	} );

	/* ---------- Thẻ minh hoạ dùng chung ---------- */

	var the = document.createElement( 'div' );
	the.className = 'nntm-tkd-the';
	the.id = 'nntm-tkd-the';
	the.setAttribute( 'role', 'tooltip' );
	the.hidden = true;
	document.body.appendChild( the );

	var dangMo = null;
	var henAn = 0;
	var loaiTro = 'mouse';

	function dungThe( muc ) {
		the.textContent = '';
		the.className = 'nntm-tkd-the nntm-tkd-the--' + ( muc.kieu === 'anh' ? 'anh' : 'the' );

		if ( muc.anh && muc.anh.src ) {
			var khung = document.createElement( 'div' );
			khung.className = 'nntm-tkd-the__khung';

			var img = document.createElement( 'img' );
			img.className = 'nntm-tkd-the__anh';
			img.src = muc.anh.src;
			img.alt = muc.anh.alt || muc.ten || '';
			img.decoding = 'async';
			if ( muc.anh.w && muc.anh.h ) {
				img.width = muc.anh.w;
				img.height = muc.anh.h;
			}
			khung.appendChild( img );
			the.appendChild( khung );
		}

		var than = document.createElement( 'div' );
		than.className = 'nntm-tkd-the__than';

		var ten = document.createElement( 'p' );
		ten.className = 'nntm-tkd-the__ten';
		ten.textContent = muc.ten || '';
		than.appendChild( ten );

		if ( muc.kieu !== 'anh' && muc.mo_ta ) {
			var moTa = document.createElement( 'p' );
			moTa.className = 'nntm-tkd-the__mo-ta';
			moTa.textContent = muc.mo_ta;
			than.appendChild( moTa );
		}

		if ( muc.lien_ket && /^https?:\/\//i.test( muc.lien_ket ) ) {
			var a = document.createElement( 'a' );
			a.className = 'nntm-tkd-the__xem-them';
			a.href = muc.lien_ket;
			a.textContent = cfg.xemThem || 'Xem thêm';
			than.appendChild( a );
		}

		the.appendChild( than );
	}

	function datViTri( span ) {
		var r = span.getBoundingClientRect();
		var rongMan = document.documentElement.clientWidth;
		var caoMan = window.innerHeight;
		var rong = the.offsetWidth;
		var cao = the.offsetHeight;

		var trai = r.left + r.width / 2 - rong / 2;
		trai = Math.max( LE, Math.min( trai, rongMan - rong - LE ) );

		// Ưu tiên nổi phía trên chữ; không đủ chỗ thì xuống dưới.
		var tren = r.top - cao - KHOANG;
		var phia = 'tren';
		if ( tren < LE && r.bottom + KHOANG + cao <= caoMan - LE ) {
			tren = r.bottom + KHOANG;
			phia = 'duoi';
		}

		the.style.left = Math.round( trai + window.scrollX ) + 'px';
		the.style.top = Math.round( Math.max( LE, tren ) + window.scrollY ) + 'px';
		the.setAttribute( 'data-phia', phia );
		the.style.setProperty( '--nntm-tkd-mui', Math.round( r.left + r.width / 2 - trai ) + 'px' );
	}

	function mo( span ) {
		window.clearTimeout( henAn );

		var muc = theoId[ span.getAttribute( 'data-nntm-tkd' ) ];
		if ( ! muc ) {
			return;
		}

		if ( dangMo === span && ! the.hidden ) {
			return;
		}

		if ( dangMo ) {
			dong( true );
		}

		dungThe( muc );
		the.hidden = false;
		the.classList.remove( 'is-hien' );
		datViTri( span );

		// Khung đầu: thẻ đã có kích thước để đặt vị trí; khung sau mới chạy hiệu ứng.
		window.requestAnimationFrame( function () {
			the.classList.add( 'is-hien' );
		} );

		dangMo = span;
		span.classList.add( 'is-mo' );
		span.setAttribute( 'aria-expanded', 'true' );
		span.setAttribute( 'aria-describedby', the.id );
	}

	function dong( ngay ) {
		window.clearTimeout( henAn );

		if ( ! dangMo ) {
			return;
		}

		dangMo.classList.remove( 'is-mo' );
		dangMo.setAttribute( 'aria-expanded', 'false' );
		dangMo.removeAttribute( 'aria-describedby' );
		dangMo = null;

		the.classList.remove( 'is-hien' );
		if ( ngay ) {
			the.hidden = true;
		} else {
			henAn = window.setTimeout( function () {
				the.hidden = true;
			}, 200 );
		}
	}

	function henDong() {
		window.clearTimeout( henAn );
		henAn = window.setTimeout( function () {
			dong( false );
		}, TRE_AN );
	}

	function spanTu( el ) {
		return el && el.closest ? el.closest( '.nntm-tkd' ) : null;
	}

	vung.addEventListener( 'pointerover', function ( e ) {
		if ( e.pointerType !== 'mouse' ) {
			return;
		}
		var span = spanTu( e.target );
		if ( span ) {
			mo( span );
		}
	} );

	vung.addEventListener( 'pointerout', function ( e ) {
		if ( e.pointerType !== 'mouse' ) {
			return;
		}
		var span = spanTu( e.target );
		if ( span && ! span.contains( e.relatedTarget ) ) {
			henDong();
		}
	} );

	vung.addEventListener( 'pointerdown', function ( e ) {
		loaiTro = e.pointerType || 'mouse';
	} );

	// Chạm (điện thoại / máy tính bảng): chạm lần một mở, chạm lại đóng.
	vung.addEventListener( 'click', function ( e ) {
		var span = spanTu( e.target );
		if ( ! span || loaiTro === 'mouse' ) {
			return;
		}
		if ( dangMo === span ) {
			dong( false );
		} else {
			mo( span );
		}
	} );

	// Chỉ mở khi focus bằng bàn phím. Chạm vào chữ cũng làm chữ nhận focus —
	// nếu mở ở đây thì ngay sau đó sự kiện click lại đóng mất.
	function focusBanPhim( el ) {
		try {
			return el.matches( ':focus-visible' );
		} catch ( err ) {
			return loaiTro === 'mouse';
		}
	}

	vung.addEventListener( 'focusin', function ( e ) {
		var span = spanTu( e.target );
		if ( span && focusBanPhim( span ) ) {
			mo( span );
		}
	} );

	vung.addEventListener( 'focusout', function ( e ) {
		if ( spanTu( e.target ) && ! the.contains( e.relatedTarget ) ) {
			henDong();
		}
	} );

	vung.addEventListener( 'keydown', function ( e ) {
		var span = spanTu( e.target );
		if ( span && ( e.key === 'Enter' || e.key === ' ' ) ) {
			e.preventDefault();
			if ( dangMo === span ) {
				dong( false );
			} else {
				mo( span );
			}
		}
	} );

	// Rê từ chữ sang thẻ (để bấm "Xem thêm") thì giữ thẻ mở.
	the.addEventListener( 'pointerenter', function () {
		window.clearTimeout( henAn );
	} );
	the.addEventListener( 'pointerleave', function ( e ) {
		if ( e.pointerType === 'mouse' ) {
			henDong();
		}
	} );
	the.addEventListener( 'focusout', function ( e ) {
		if ( ! the.contains( e.relatedTarget ) && spanTu( e.relatedTarget ) !== dangMo ) {
			henDong();
		}
	} );

	document.addEventListener( 'keydown', function ( e ) {
		if ( e.key === 'Escape' && dangMo ) {
			var span = dangMo;
			dong( true );
			span.focus();
		}
	} );

	document.addEventListener( 'pointerdown', function ( e ) {
		if ( dangMo && ! the.contains( e.target ) && ! spanTu( e.target ) ) {
			dong( false );
		}
	} );

	window.addEventListener( 'resize', function () {
		if ( dangMo ) {
			datViTri( dangMo );
		}
	} );

	/* ---------- Nạp sẵn hình của những từ khoá có mặt trên trang ---------- */

	function napSan() {
		coDung.forEach( function ( muc ) {
			if ( muc.anh && muc.anh.src ) {
				var img = new window.Image();
				img.decoding = 'async';
				img.src = muc.anh.src;
			}
		} );
	}

	if ( window.requestIdleCallback ) {
		window.requestIdleCallback( napSan, { timeout: 3000 } );
	} else {
		window.setTimeout( napSan, 1500 );
	}
} )();
