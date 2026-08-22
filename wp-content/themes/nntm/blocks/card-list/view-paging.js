/**
 * Đổi trang khối nntm/card-list ngay trên giao diện hiện tại — KHÔNG tải lại
 * trang (yêu cầu chủ dự án 21/08/2026, dải "Kim Cương Hành Giả").
 *
 * Cách chạy: bấm số trang / mũi tên trong .nntm-card-list__paging thì tải
 * CHÍNH đường dẫn của thẻ <a> đó, chỉ thêm ?nntm_cardlist_ajax=1 — máy chủ
 * trả về JSON chứa HTML của đúng khối này (xem inc/card-list-ajax.php) và ở
 * đây chỉ việc thay thẻ <section> cũ bằng thẻ mới.
 *
 * Vì HTML do CHÍNH render.php của khối dựng ra ở đúng ngữ cảnh trang đó, giao
 * diện sau khi đổi trang giống hệt như tải lại trang thật — không có bản
 * markup thứ hai viết bằng JS để lệch nhau.
 *
 * Nguyên tắc giữ nguyên:
 *   - Không thư viện ngoài, không bước build (khai qua "viewScript" trong
 *     block.json cùng với view.js).
 *   - Liên kết vẫn là <a href> thật: giữa chuột / mở tab mới / tắt JS vẫn
 *     chuyển trang bình thường. Chỉ click thường bị tiếp quản.
 *   - Lỗi mạng / máy chủ trả gì lạ → tự chuyển trang theo cách cũ, người
 *     dùng không bao giờ bị kẹt.
 *   - Địa chỉ trên thanh URL đổi theo (history.pushState) và nút Lùi/Tiến
 *     của trình duyệt vẫn đúng trang.
 */
( function () {
	'use strict';

	var THAM_SO_AJAX = 'nntm_cardlist_ajax';
	var LOP_KHOI     = 'nntm-card-list';
	var dangTai      = false;

	if ( ! window.fetch || ! window.history || ! window.history.pushState ) {
		return; // Trình duyệt quá cũ: để liên kết chạy như thường.
	}

	document.addEventListener( 'click', function ( event ) {
		// Bấm kèm phím tổ hợp / chuột giữa là ý muốn mở tab mới — không chặn.
		if ( event.defaultPrevented || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey || 0 !== event.button ) {
			return;
		}

		var link = event.target.closest ? event.target.closest( '.nntm-card-list__paging a[href]' ) : null;
		if ( ! link ) {
			return;
		}

		var khoi = link.closest( '.' + LOP_KHOI );
		if ( ! khoi ) {
			return;
		}

		// Chỉ tiếp quản liên kết cùng nguồn gốc (same-origin).
		var dich = new window.URL( link.href, window.location.href );
		if ( dich.origin !== window.location.origin ) {
			return;
		}

		event.preventDefault();
		doiTrang( khoi, dich.href, true );
	} );

	/**
	 * Nút Lùi/Tiến của trình duyệt: dựng lại khối theo URL đang đứng. Không
	 * còn khối nào trên trang (ví dụ đã điều hướng đi nơi khác) thì để trình
	 * duyệt tự lo.
	 */
	window.addEventListener( 'popstate', function () {
		var khoi = document.querySelector( '.' + LOP_KHOI + ' .nntm-card-list__paging' );
		if ( ! khoi ) {
			return;
		}

		doiTrang( khoi.closest( '.' + LOP_KHOI ), window.location.href, false );
	} );

	/**
	 * @param {Element} khoi     Thẻ <section class="nntm-card-list"> đang hiện.
	 * @param {string}  url      Đường dẫn trang cần lấy (URL thật, chưa thêm tham số).
	 * @param {boolean} ghiSuKy  Có đẩy URL mới vào history hay không (false khi
	 *                           chính history vừa gọi tới đây).
	 */
	function doiTrang( khoi, url, ghiSuKy ) {
		if ( dangTai || ! khoi ) {
			return;
		}

		dangTai = true;
		khoi.classList.add( 'nntm-card-list--dang-tai' );
		khoi.setAttribute( 'aria-busy', 'true' );

		window.fetch( themThamSo( url ), {
			credentials: 'same-origin',
			headers: { Accept: 'application/json' }
		} )
			.then( function ( response ) {
				if ( ! response.ok ) {
					throw new Error( 'HTTP ' + response.status );
				}
				return response.json();
			} )
			.then( function ( payload ) {
				if ( ! payload || ! payload.success || ! payload.data || ! payload.data.html ) {
					throw new Error( 'payload' );
				}

				var moi = thayKhoi( khoi, payload.data.html );
				if ( ! moi ) {
					throw new Error( 'markup' );
				}

				if ( ghiSuKy ) {
					window.history.pushState( { nntmCardList: true }, '', url );
				}

				dangTai = false;
				dungDauDanhSach( moi );
			} )
			.catch( function () {
				// Không đoán mò: trả về đúng hành vi cũ (tải lại trang).
				window.location.assign( url );
			} );
	}

	/**
	 * @param {string} url
	 * @return {string} URL đã thêm ?nntm_cardlist_ajax=1.
	 */
	function themThamSo( url ) {
		var dich = new window.URL( url, window.location.href );
		dich.searchParams.set( THAM_SO_AJAX, '1' );
		return dich.href;
	}

	/**
	 * Thay cả thẻ <section> của khối bằng HTML mới, rồi báo cho view.js khởi
	 * tạo lại các thành phần động (băng cuộn, thẻ YouTube) NẰM TRONG phần vừa
	 * thay — không chạm tới các khối khác trên trang để không gắn trùng sự
	 * kiện cho chúng.
	 *
	 * @param {Element} khoiCu
	 * @param {string}  html
	 * @return {Element|null} Khối mới đã nằm trong DOM.
	 */
	function thayKhoi( khoiCu, html ) {
		var khuon = document.createElement( 'template' );
		khuon.innerHTML = html.trim();

		var khoiMoi = khuon.content.querySelector( '.' + LOP_KHOI );
		if ( ! khoiMoi ) {
			return null;
		}

		khoiCu.replaceWith( khoiMoi );

		document.dispatchEvent(
			new window.CustomEvent( 'nntm-card-list-refresh', { detail: { root: khoiMoi } } )
		);

		return khoiMoi;
	}

	/**
	 * Đưa mắt người dùng về đầu danh sách vừa đổi (bấm "trang 3" mà màn hình
	 * đứng ở giữa dải thì không biết nội dung đã đổi) và đặt tiêu điểm bàn
	 * phím vào khối mới để người dùng bàn phím không bị mất chỗ.
	 *
	 * @param {Element} khoi
	 */
	function dungDauDanhSach( khoi ) {
		var giamHoatHinh = !! ( window.matchMedia && window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches );

		khoi.scrollIntoView( {
			behavior: giamHoatHinh ? 'auto' : 'smooth',
			block: 'start'
		} );

		var luoi = khoi.querySelector( '.nntm-grid' );
		if ( luoi ) {
			luoi.setAttribute( 'tabindex', '-1' );
			luoi.focus( { preventScroll: true } );
		}
	}
} )();
