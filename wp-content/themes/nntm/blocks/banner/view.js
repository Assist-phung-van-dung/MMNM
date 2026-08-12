/**
 * View script cho block nntm/banner — băng chuyền ảnh lớn đầu trang.
 * JavaScript thuần, không thư viện ngoài, không bước build. Khai qua
 * "viewScript": "file:./view.js" trong block.json.
 *
 * Tự chạy. Tự dừng khi:
 *   - rê chuột vào khối (mouseenter) — tiếp tục khi rê ra (mouseleave).
 *   - tiêu điểm bàn phím vào khối (focusin) — tiếp tục khi ra hẳn
 *     (focusout, kiểm tra relatedTarget để không dừng nhầm khi tiêu điểm
 *     chỉ nhảy giữa hai chấm).
 *   - tab bị ẩn (document.hidden).
 *   - prefers-reduced-motion: reduce — TẮT HẲN, không tạo bộ đếm giờ nào.
 *
 * Chuyển tấm bằng đổi class .is-active (làm mờ chồng qua CSS), không
 * trượt ngang. Figma R1 để nút mũi tên ở visible:false nên không có nút
 * trái/phải — vẫn hỗ trợ phím mũi tên khi băng chuyền có tiêu điểm.
 */
( function () {
	'use strict';

	function giamChuyenDong() {
		return !! ( window.matchMedia && window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches );
	}

	/**
	 * Khởi tạo một băng chuyền.
	 *
	 * @param {Element} root Phần tử ".nntm-banner".
	 */
	function khoiTaoBanner( root ) {
		var stage = root.querySelector( '.nntm-banner__stage' );
		var slides = root.querySelectorAll( '.nntm-banner__slide' );
		var dots = root.querySelectorAll( '.nntm-banner__dot' );

		if ( ! stage || slides.length < 2 ) {
			// Một tấm (hoặc không tìm thấy khung) thì không có gì để chuyển.
			return;
		}

		var tong = slides.length;
		var viTri = 0;
		var boDem = null;
		var dangDung = false;

		// render.php đã chặn biên 2–30; ở đây vẫn tự vệ thêm — không tin
		// tuyệt đối dữ liệu trong HTML.
		var batAutoplay = '1' === root.getAttribute( 'data-nntm-autoplay' ) ||
			'true' === String( root.getAttribute( 'data-nntm-autoplay' ) );
		var chuKy = parseFloat( root.getAttribute( 'data-nntm-interval' ) );
		if ( ! isFinite( chuKy ) || chuKy <= 0 ) {
			chuKy = 6;
		}
		chuKy = Math.max( 2, Math.min( 30, chuKy ) );

		// Tắt hẳn khi người dùng bật giảm chuyển động.
		var choPhepChay = batAutoplay && ! giamChuyenDong();

		function toi( chiSo ) {
			var ke = ( chiSo + tong ) % tong;

			for ( var i = 0; i < slides.length; i++ ) {
				slides[ i ].classList.toggle( 'is-active', i === ke );
			}
			for ( var d = 0; d < dots.length; d++ ) {
				var dangXem = d === ke;
				dots[ d ].classList.toggle( 'is-active', dangXem );
				if ( dangXem ) {
					dots[ d ].setAttribute( 'aria-current', 'true' );
				} else {
					dots[ d ].removeAttribute( 'aria-current' );
				}
			}

			viTri = ke;
		}

		function tamSau() {
			toi( viTri + 1 );
		}

		function tamTruoc() {
			toi( viTri - 1 );
		}

		function batDem() {
			if ( ! choPhepChay || dangDung || null !== boDem || document.hidden ) {
				return;
			}
			boDem = window.setInterval( tamSau, chuKy * 1000 );
		}

		function dungDem() {
			if ( null !== boDem ) {
				window.clearInterval( boDem );
				boDem = null;
			}
		}

		function tamNgung() {
			dangDung = true;
			dungDem();
		}

		function chayTiep() {
			dangDung = false;
			batDem();
		}

		// ---------- Dừng khi rê chuột vào ----------
		root.addEventListener( 'mouseenter', tamNgung );
		root.addEventListener( 'mouseleave', chayTiep );

		// ---------- Dừng khi tiêu điểm bàn phím vào khối ----------
		root.addEventListener( 'focusin', tamNgung );
		root.addEventListener( 'focusout', function ( su_kien ) {
			// relatedTarget là phần tử SẮP nhận tiêu điểm — còn nằm trong
			// root nghĩa là tiêu điểm chỉ chuyển giữa hai phần tử con.
			if ( ! su_kien.relatedTarget || ! root.contains( su_kien.relatedTarget ) ) {
				chayTiep();
			}
		} );

		// ---------- Dừng khi tab bị ẩn ----------
		document.addEventListener( 'visibilitychange', function () {
			if ( document.hidden ) {
				dungDem();
			} else {
				batDem();
			}
		} );

		// ---------- Chấm bấm chuyển tấm ----------
		for ( var t = 0; t < dots.length; t++ ) {
			( function ( chiSo ) {
				dots[ chiSo ].addEventListener( 'click', function () {
					toi( chiSo );
					// Bấm chấm là tương tác chủ động — đếm lại từ đầu để
					// không nhảy tấm ngay sau khi khách vừa tự chọn.
					if ( choPhepChay && ! dangDung ) {
						dungDem();
						batDem();
					}
				} );
			} )( t );
		}

		// ---------- Phím mũi tên khi băng chuyền có tiêu điểm ----------
		stage.addEventListener( 'keydown', function ( su_kien ) {
			if ( 'ArrowLeft' === su_kien.key ) {
				su_kien.preventDefault();
				tamTruoc();
			} else if ( 'ArrowRight' === su_kien.key ) {
				su_kien.preventDefault();
				tamSau();
			}
		} );

		toi( 0 );
		batDem();
	}

	function khoiTaoTatCa() {
		var ds = document.querySelectorAll( '.nntm-banner' );
		for ( var i = 0; i < ds.length; i++ ) {
			khoiTaoBanner( ds[ i ] );
		}
	}

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', khoiTaoTatCa );
	} else {
		khoiTaoTatCa();
	}
} )();
