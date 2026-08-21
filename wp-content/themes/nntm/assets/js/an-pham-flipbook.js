/**
 * Trình đọc ấn phẩm dạng 3D flip book trên trang chi tiết (single-nntm_publication.php).
 * pdf.js dựng từng trang PDF thành ảnh, page-flip (biến toàn cục `St`) tạo hiệu ứng lật.
 * Cấu hình (URL tệp PDF, chữ hiển thị…) đến từ `nntmAnPhamFlipbook`, xem inc/an-pham.php.
 */
( function () {
	'use strict';

	if ( 'undefined' === typeof nntmAnPhamFlipbook || 'undefined' === typeof pdfjsLib || 'undefined' === typeof St ) {
		return;
	}

	var nutMo    = document.querySelector( '[data-nntm-an-pham-doc]' );
	var modal    = null;
	var pageFlip = null;
	var daTaiXong = false;

	if ( ! nutMo ) {
		return;
	}

	pdfjsLib.GlobalWorkerOptions.workerSrc = nntmAnPhamFlipbook.workerUrl;

	function taoModal() {
		var el = document.createElement( 'div' );
		el.className = 'nntm-flipbook-modal';
		el.setAttribute( 'role', 'dialog' );
		el.setAttribute( 'aria-modal', 'true' );
		el.setAttribute( 'aria-label', nntmAnPhamFlipbook.title || '' );
		el.innerHTML =
			'<div class="nntm-flipbook-modal__khung">' +
				'<div class="nntm-flipbook-modal__thanh">' +
					'<span class="nntm-flipbook-modal__ten"></span>' +
					'<button type="button" class="nntm-flipbook-modal__dong" aria-label="' + nntmAnPhamFlipbook.dong + '">&times;</button>' +
				'</div>' +
				'<div class="nntm-flipbook-modal__than">' +
					'<p class="nntm-flipbook-modal__trang-thai">' + nntmAnPhamFlipbook.dangTai + '</p>' +
					'<div class="nntm-flipbook-modal__sach" hidden></div>' +
				'</div>' +
				'<div class="nntm-flipbook-modal__dieu-huong" hidden>' +
					'<button type="button" class="nntm-flipbook-modal__lui" aria-label="' + nntmAnPhamFlipbook.trangTruoc + '">&#8249;</button>' +
					'<button type="button" class="nntm-flipbook-modal__toi" aria-label="' + nntmAnPhamFlipbook.trangSau + '">&#8250;</button>' +
				'</div>' +
			'</div>';

		document.body.appendChild( el );

		el.querySelector( '.nntm-flipbook-modal__ten' ).textContent = nntmAnPhamFlipbook.title || '';
		el.querySelector( '.nntm-flipbook-modal__dong' ).addEventListener( 'click', dong );
		el.addEventListener( 'click', function ( su ) {
			if ( su.target === el ) {
				dong();
			}
		} );
		el.querySelector( '.nntm-flipbook-modal__lui' ).addEventListener( 'click', function () {
			if ( pageFlip ) {
				pageFlip.flipPrev();
			}
		} );
		el.querySelector( '.nntm-flipbook-modal__toi' ).addEventListener( 'click', function () {
			if ( pageFlip ) {
				pageFlip.flipNext();
			}
		} );

		return el;
	}

	function dong() {
		if ( modal ) {
			modal.classList.remove( 'nntm-flipbook-modal--mo' );
		}
		document.body.classList.remove( 'nntm-flipbook-modal-mo' );
	}

	function baoLoi( thongBao ) {
		var trangThai = modal.querySelector( '.nntm-flipbook-modal__trang-thai' );
		trangThai.hidden = false;
		trangThai.textContent = thongBao;
	}

	/** Dựng một trang PDF thành ảnh JPEG (data URL) ở độ phân giải vừa đủ nét. */
	function dungTrang( pdf, soTrang ) {
		return pdf.getPage( soTrang ).then( function ( trang ) {
			var scale    = 1.4;
			var viewport = trang.getViewport( { scale: scale } );
			var canvas   = document.createElement( 'canvas' );
			var ctx      = canvas.getContext( '2d' );

			canvas.width  = viewport.width;
			canvas.height = viewport.height;

			return trang.render( { canvasContext: ctx, viewport: viewport } ).promise.then( function () {
				var anh = canvas.toDataURL( 'image/jpeg', 0.85 );
				return { anh: anh, rong: viewport.width, cao: viewport.height };
			} );
		} );
	}

	function taiVaDungSach() {
		if ( daTaiXong ) {
			return;
		}

		pdfjsLib.getDocument( nntmAnPhamFlipbook.pdfUrl ).promise
			.then( function ( pdf ) {
				var soTrangDoc = [];
				for ( var i = 1; i <= pdf.numPages; i++ ) {
					soTrangDoc.push( i );
				}

				return soTrangDoc.reduce( function ( chuoi, soTrang ) {
					return chuoi.then( function ( ketQua ) {
						return dungTrang( pdf, soTrang ).then( function ( trangAnh ) {
							ketQua.push( trangAnh );
							return ketQua;
						} );
					} );
				}, Promise.resolve( [] ) );
			} )
			.then( function ( danhSachTrang ) {
				var trangThai = modal.querySelector( '.nntm-flipbook-modal__trang-thai' );
				var khungSach = modal.querySelector( '.nntm-flipbook-modal__sach' );
				var dieuHuong = modal.querySelector( '.nntm-flipbook-modal__dieu-huong' );

				trangThai.hidden = true;
				khungSach.hidden = false;
				dieuHuong.hidden = false;

				var trangDau = danhSachTrang[ 0 ];

				pageFlip = new St.PageFlip( khungSach, {
					width: trangDau.rong,
					height: trangDau.cao,
					size: 'stretch',
					minWidth: 280,
					maxWidth: 1200,
					minHeight: 400,
					maxHeight: 1600,
					maxShadowOpacity: 0.5,
					showCover: true,
					mobileScrollSupport: false,
				} );

				pageFlip.loadFromImages( danhSachTrang.map( function ( t ) {
					return t.anh;
				} ) );

				daTaiXong = true;
			} )
			.catch( function () {
				baoLoi( nntmAnPhamFlipbook.loi );
			} );
	}

	nutMo.addEventListener( 'click', function ( su ) {
		su.preventDefault();

		if ( ! modal ) {
			modal = taoModal();
		}

		modal.classList.add( 'nntm-flipbook-modal--mo' );
		document.body.classList.add( 'nntm-flipbook-modal-mo' );

		taiVaDungSach();
	} );

	document.addEventListener( 'keydown', function ( su ) {
		if ( 'Escape' === su.key && modal && modal.classList.contains( 'nntm-flipbook-modal--mo' ) ) {
			dong();
		}
	} );
}() );
