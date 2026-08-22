/**
 * Popup "Tham Gia Chuỗi Trì" / "Cập Nhật Chuỗi Trì" — mở từ bất kỳ phần tử
 * nào có [data-nntm-chuoi-tri="tham-gia"] hoặc [data-nntm-chuoi-tri="cap-nhat"]
 * (vd nút trên banner "Lễ Đàn Khổng Tước", xem blocks/banner/render.php +
 * inc/cong-tu.php::nntm_congtu_banner_btn_attrs()).
 *
 * Bắt chước ĐÚNG khuôn assets/js/auth-modal.js: thuần JS, không thư viện,
 * đóng bằng Esc / bấm ra ngoài / nút đóng, có bẫy tiêu điểm (focus trap) và
 * trả tiêu điểm về đúng phần tử trước khi mở sau khi đóng.
 *
 * Nếu không tìm thấy popup trong DOM (vd trang này chưa in modal vì chưa có
 * chương trình đang mở, xem nntm_congtu_co_modal_tren_trang()) thì KHÔNG
 * chặn click mặc định — để link (href dự phòng) chạy bình thường.
 */
( function () {
	'use strict';

	var MODAL_IDS = {
		'tham-gia': 'nntm-cong-tu-modal-tham-gia',
		'cap-nhat': 'nntm-cong-tu-modal-cap-nhat'
	};
	var lastFocusedEl = null;

	document.addEventListener( 'click', function ( event ) {
		var trigger = event.target.closest ? event.target.closest( '[data-nntm-chuoi-tri]' ) : null;
		if ( ! trigger ) {
			return;
		}

		var key     = trigger.getAttribute( 'data-nntm-chuoi-tri' );
		var modalId = Object.prototype.hasOwnProperty.call( MODAL_IDS, key ) ? MODAL_IDS[ key ] : null;
		var modal   = modalId ? document.getElementById( modalId ) : null;

		if ( ! modal ) {
			// Dự phòng: popup chưa được in ra DOM, để link chạy bình thường.
			return;
		}

		event.preventDefault();

		// Mở bằng tay thì luôn bắt đầu lại từ đầu: hiện lại form, xoá thông
		// báo của lần ghi trước (xem datLaiForm()).
		openModal( modal, true );
	} );

	document.addEventListener( 'keydown', function ( event ) {
		var modal = timModalDangMo();
		if ( ! modal ) {
			return;
		}

		if ( 'Escape' === event.key ) {
			closeModal( modal );
			return;
		}

		if ( 'Tab' === event.key ) {
			trapFocus( event, modal );
		}
	} );

	/**
	 * @return {HTMLElement|null}
	 */
	function timModalDangMo() {
		var keys = Object.keys( MODAL_IDS );
		for ( var i = 0; i < keys.length; i++ ) {
			var modal = document.getElementById( MODAL_IDS[ keys[ i ] ] );
			if ( modal && ! modal.hidden ) {
				return modal;
			}
		}
		return null;
	}

	/**
	 * @param {HTMLElement} modal
	 * @param {boolean}     [datLai] Xoá dấu vết lần ghi trước trước khi mở.
	 *        CHỈ truyền true khi người dùng tự bấm mở — moLaiPopupTheoBodyClass()
	 *        KHÔNG được truyền, nếu không sẽ xoá mất thông báo mà PHP vừa in
	 *        ra sau một lần POST thường (trường hợp tắt JS / nonce hết hạn).
	 */
	function openModal( modal, datLai ) {
		if ( datLai ) {
			datLaiForm( modal );
		}

		lastFocusedEl = document.activeElement;
		modal.hidden = false;

		var closeButtons = modal.querySelectorAll( '[data-nntm-congtu-modal-close]' );
		for ( var i = 0; i < closeButtons.length; i++ ) {
			closeButtons[ i ].addEventListener( 'click', function () {
				closeModal( modal );
			} );
		}

		var overlay = modal.querySelector( '[data-nntm-congtu-modal-overlay]' );
		if ( overlay ) {
			overlay.addEventListener( 'click', function () {
				closeModal( modal );
			} );
		}

		var focusable = getFocusable( modal );
		if ( focusable.length ) {
			focusable[ 0 ].focus();
		}
	}

	/**
	 * @param {HTMLElement} modal
	 */
	function closeModal( modal ) {
		modal.hidden = true;

		if ( lastFocusedEl && typeof lastFocusedEl.focus === 'function' ) {
			lastFocusedEl.focus();
		}
		lastFocusedEl = null;
	}

	/**
	 * @param {KeyboardEvent} event
	 * @param {HTMLElement}   modal
	 */
	function trapFocus( event, modal ) {
		var focusable = getFocusable( modal );
		if ( ! focusable.length ) {
			return;
		}

		var first = focusable[ 0 ];
		var last  = focusable[ focusable.length - 1 ];

		if ( event.shiftKey && document.activeElement === first ) {
			event.preventDefault();
			last.focus();
		} else if ( ! event.shiftKey && document.activeElement === last ) {
			event.preventDefault();
			first.focus();
		}
	}

	/**
	 * @param {HTMLElement} container
	 * @return {HTMLElement[]}
	 */
	function getFocusable( container ) {
		if ( ! container ) {
			return [];
		}

		var nodes = container.querySelectorAll(
			'a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])'
		);

		/*
		 * Bỏ phần tử đang bị ẩn: từ 21/08/2026 form được CẤT ĐI sau khi ghi
		 * xong (xem hoanTatForm()), nên ô nhập/nút gửi vẫn nằm trong DOM mà
		 * không còn nhận tiêu điểm được. Trình duyệt tự bỏ qua chúng khi Tab,
		 * nhưng bẫy tiêu điểm ở đây thì không — tính "phần tử đầu/cuối" theo
		 * danh sách còn lẫn phần tử ẩn sẽ làm Tab vòng sai chỗ.
		 */
		return Array.prototype.filter.call( nodes, function ( node ) {
			return null !== node.offsetParent;
		} );
	}

	/**
	 * Mở lại đúng popup khi trang tải lại sau một lần POST từ popup (lỗi
	 * hoặc thành công) — xem nntm_congtu_body_class() trong inc/cong-tu.php
	 * gắn class "nntm-congtu-mo-lai--tham-gia"/"nntm-congtu-mo-lai--cap-nhat"
	 * lên <body>.
	 */
	function moLaiPopupTheoBodyClass() {
		var keys = Object.keys( MODAL_IDS );
		for ( var i = 0; i < keys.length; i++ ) {
			if ( document.body.classList.contains( 'nntm-congtu-mo-lai--' + keys[ i ] ) ) {
				var modal = document.getElementById( MODAL_IDS[ keys[ i ] ] );
				if ( modal ) {
					openModal( modal );
				}
				return;
			}
		}
	}

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', moLaiPopupTheoBodyClass );
	} else {
		moLaiPopupTheoBodyClass();
	}

	/* =====================================================================
	 * GỬI FORM NGAY TRONG POPUP — KHÔNG TẢI LẠI TRANG.
	 *
	 * Yêu cầu chủ dự án 21/08/2026 (trang Kim Cương Hành Giả): "khi nhấn Cập
	 * nhật chuỗi trì sẽ có form nhập, sau khi nhập xong anh không muốn load
	 * lại page mà muốn cập nhật luôn và có 1 thông báo, và load lại bảng xếp
	 * hạng và Thống Kê Của Đạo Tràng luôn."
	 *
	 * Không có window.nntmCongTu (chưa localize) thì KHÔNG chặn gì cả — form
	 * POST như cũ và trang tải lại, đúng hành vi trước 21/08/2026. Cùng lý
	 * lẽ: form vẫn giữ method="post" và action mặc định, nên tắt JS vẫn gửi
	 * được bình thường.
	 * ===================================================================== */

	var CAU_HINH = window.nntmCongTu || null;

	document.addEventListener( 'submit', function ( event ) {
		var form = event.target;

		if ( ! form || ! form.matches || ! form.matches( 'form[data-nntm-congtu-ajax]' ) ) {
			return;
		}

		if ( ! CAU_HINH || ! CAU_HINH.ajaxUrl || ! CAU_HINH.action || ! window.fetch || ! window.FormData ) {
			return; // Dự phòng: để form POST thường lo.
		}

		event.preventDefault();
		guiForm( form );
	} );

	/**
	 * @param {HTMLFormElement} form
	 */
	function guiForm( form ) {
		if ( 'true' === form.getAttribute( 'data-nntm-dang-gui' ) ) {
			return;
		}

		var nutGui = form.querySelector( '[type="submit"]' );
		var duLieu = new window.FormData( form );

		duLieu.append( 'action', CAU_HINH.action );

		// Khối "Thống Kê Của Đạo Tràng" + "Bảng Xếp Hạng Cá Nhân" chỉ có ở
		// trang Kim Cương Hành Giả; trang khác không cần dựng lại gì.
		var khoi = document.querySelector( '[data-nntm-congtu-block]' );
		if ( khoi ) {
			duLieu.append( 'lam_moi_khoi', '1' );
			duLieu.append( 'khoi_program_id', khoi.getAttribute( 'data-nntm-congtu-program' ) || '0' );
			duLieu.append( 'khoi_bxh_heading', khoi.getAttribute( 'data-nntm-congtu-bxh-heading' ) || '' );
			duLieu.append( 'khoi_bxh_limit', khoi.getAttribute( 'data-nntm-congtu-bxh-limit' ) || '50' );
		}

		form.setAttribute( 'data-nntm-dang-gui', 'true' );
		if ( nutGui ) {
			nutGui.disabled = true;
		}

		window.fetch( CAU_HINH.ajaxUrl, {
			method: 'POST',
			credentials: 'same-origin',
			body: duLieu
		} )
			.then( function ( response ) {
				return response.json();
			} )
			.then( function ( payload ) {
				var duLieuTraVe = payload && payload.data ? payload.data : {};

				if ( ! payload || ! payload.success ) {
					hienThongBao( form, [ duLieuTraVe.message || CAU_HINH.errorText ], false );
					return;
				}

				/*
				 * ĐỔI 22/08/2026 (chủ dự án): ghi xong thì ĐÓNG popup luôn và
				 * chỉ để lại MỘT dòng chữ ngay dưới nút "Cập nhật chuỗi trì".
				 *
				 * Bản trước hiện hai dòng (lời cảm ơn + dòng số) BÊN TRONG
				 * popup rồi giữ popup mở — người dùng phải tự bấm đóng, mà lời
				 * cảm ơn với dòng số nói cùng một việc. Giờ bỏ lời cảm ơn, giữ
				 * dòng số, và đưa ra ngoài popup để đọc được ngay chỗ vừa bấm.
				 *
				 * Vẫn gọi hoanTatForm() trước khi đóng: nó cập nhật dòng hiện
				 * trạng và cất form, nên lần mở popup sau đã ở trạng thái đúng.
				 * Thứ tự này cũng để closeModal() chạy SAU — nó trả tiêu điểm
				 * về đúng nút vừa bấm, ngay trên dòng thông báo mới.
				 */
				hoanTatForm( form, duLieuTraVe );
				baoDuoiNutBanner( duLieuTraVe.tong_ket );
				capNhatKhoiThongKe( khoi, duLieuTraVe );
				doiNutBanner( duLieuTraVe.nhan_nut_banner );

				var modalDangMo = form.closest( '.nntm-auth-modal' );
				if ( modalDangMo ) {
					closeModal( modalDangMo );
				}
			} )
			.catch( function () {
				hienThongBao( form, [ CAU_HINH.errorText ], false );
			} )
			.finally( function () {
				form.removeAttribute( 'data-nntm-dang-gui' );
				if ( nutGui ) {
					nutGui.disabled = false;
				}
			} );
	}

	/**
	 * Thay RUỘT ô [data-nntm-congtu-thong-bao] — dùng đúng lớp .nntm-auth-alert
	 * mà bản POST thường đang in, nên không đẻ thêm kiểu dáng mới. Chữ gán
	 * bằng textContent (không bao giờ nhét HTML từ máy chủ vào innerHTML).
	 *
	 * @param {HTMLFormElement}      form
	 * @param {Array<string|undefined>} dongChu  Mỗi phần tử là một dòng <p>;
	 *        phần tử rỗng/thiếu bị bỏ qua.
	 * @param {boolean}              thanhCong
	 */
	function hienThongBao( form, dongChu, thanhCong ) {
		var the = form.closest( '.nntm-auth-card' );
		var o   = the ? the.querySelector( '[data-nntm-congtu-thong-bao]' ) : null;

		if ( ! o ) {
			return;
		}

		var hop = document.createElement( 'div' );
		hop.className = 'nntm-auth-alert ' + ( thanhCong ? 'nntm-auth-alert--ok' : 'nntm-auth-alert--loi' );
		hop.setAttribute( 'role', thanhCong ? 'status' : 'alert' );

		var soDong = 0;
		for ( var i = 0; i < dongChu.length; i++ ) {
			if ( ! dongChu[ i ] ) {
				continue;
			}

			var doan = document.createElement( 'p' );
			doan.textContent = dongChu[ i ];
			hop.appendChild( doan );
			++soDong;
		}

		if ( ! soDong ) {
			return;
		}

		o.textContent = '';
		o.appendChild( hop );
	}

	/**
	 * Ghi xong thì KẾT THÚC luôn: cập nhật dòng hiện trạng, rồi CẤT form đi —
	 * yêu cầu chủ dự án 21/08/2026 "không cần hiện form nhập lại nữa". Con số
	 * tổng kết ở chân thẻ cũng ẩn theo vì thông báo vừa hiện đã nói đúng câu
	 * đó, để lại là đọc hai lần cùng một dòng.
	 *
	 * Vẫn ghi tiếp được: đóng popup rồi bấm nút mở lại là form hiện lại
	 * nguyên vẹn (datLaiForm() gọi từ openModal()) — một ngày khai báo nhiều
	 * lần, cộng dồn, đúng chốt nghiệp vụ 14/08/2026.
	 *
	 * @param {HTMLFormElement} form
	 * @param {Object}          duLieu
	 */
	function hoanTatForm( form, duLieu ) {
		var the = form.closest( '.nntm-auth-card' );

		if ( the ) {
			datChu( the.querySelector( '.nntm-cong-tu__hien-trang' ), duLieu.hien_trang );

			var tongKet = the.querySelector( '.nntm-cong-tu__tong-ket' );
			if ( tongKet ) {
				datChu( tongKet, duLieu.tong_ket );
				tongKet.hidden = true;
			}
		}

		form.hidden = true;

		// Tiêu điểm đang ở nút gửi vừa bị ẩn — trả về nút đóng để người dùng
		// bàn phím không bị rơi ra ngoài hộp thoại.
		var modal   = form.closest( '.nntm-auth-modal' );
		var nutDong = modal ? modal.querySelector( '[data-nntm-congtu-modal-close]' ) : null;
		if ( nutDong ) {
			nutDong.focus();
		}
	}

	/**
	 * Trả popup về trạng thái sạch: hiện lại form, xoá thông báo, hiện lại
	 * dòng tổng kết chân thẻ. Gọi khi người dùng tự bấm mở popup.
	 *
	 * @param {HTMLElement} modal
	 */
	function datLaiForm( modal ) {
		var forms = modal.querySelectorAll( 'form[data-nntm-congtu-ajax]' );
		for ( var i = 0; i < forms.length; i++ ) {
			forms[ i ].hidden = false;
		}

		var o = modal.querySelector( '[data-nntm-congtu-thong-bao]' );
		if ( o ) {
			o.textContent = '';
		}

		var tongKet = modal.querySelector( '.nntm-cong-tu__tong-ket' );
		if ( tongKet ) {
			tongKet.hidden = false;
		}
	}

	/**
	 * @param {Element|null} el
	 * @param {string}       chu
	 */
	function datChu( el, chu ) {
		if ( el && 'string' === typeof chu ) {
			el.textContent = chu;
		}
	}

	/**
	 * Thay tại chỗ thẻ .nntm-cong-tu__thong-ke và .nntm-cong-tu__bxh bằng
	 * HTML máy chủ vừa dựng lại (cùng hai hàm render của block, xem
	 * nntm_congtu_ajax_html_khoi()). Thẻ <section> bọc ngoài KHÔNG bị thay —
	 * nó mang các class riêng của trang Kim Cương do PHP gắn theo ngữ cảnh
	 * trang, thay đi là mất kiểu dáng cả dải.
	 *
	 * @param {Element|null} khoi
	 * @param {Object}       duLieu
	 */
	function capNhatKhoiThongKe( khoi, duLieu ) {
		if ( ! khoi ) {
			return;
		}

		thayThe( khoi.querySelector( '.nntm-cong-tu__thong-ke' ), duLieu.thong_ke_html );
		thayThe( khoi.querySelector( '.nntm-cong-tu__bxh' ), duLieu.bxh_html );
	}

	/**
	 * @param {Element|null} cu
	 * @param {string}       html
	 */
	function thayThe( cu, html ) {
		if ( ! cu || 'string' !== typeof html || '' === html.trim() ) {
			return;
		}

		var khuon = document.createElement( 'template' );
		khuon.innerHTML = html.trim();

		var moi = khuon.content.firstElementChild;
		if ( moi ) {
			cu.replaceWith( moi );
		}
	}

	/**
	 * Sau lần CAM KẾT đầu tiên, nút trên banner phải đổi thành "Cập nhật
	 * chuỗi trì" và mở popup cập nhật — đúng bảng trạng thái ở
	 * inc/cong-tu.php::nntm_congtu_banner_btn_attrs(). href dự phòng (khi tắt
	 * JS) vẫn trỏ trang tham gia cho tới lần tải trang sau; ở đây JS đã tiếp
	 * quản click nên href không được dùng tới.
	 *
	 * @param {string|undefined} nhanMoi
	 */
	/**
	 * Đặt MỘT dòng chữ ngay dưới nút "Cập nhật chuỗi trì" / "Tham gia" trên
	 * banner. Dòng này do JS dựng ra chứ không có sẵn trong HTML: nó chỉ có
	 * nghĩa NGAY SAU khi vừa ghi, tải lại trang là hết — in sẵn trong markup
	 * thì thành một dòng số nằm đó vĩnh viễn, không ai hiểu vì sao.
	 *
	 * Chèn làm em kế tiếp của chính cái nút, nên nó luôn nằm đúng dưới nút bất
	 * kể banner nào, không phải đi tìm chỗ neo riêng cho từng trang.
	 *
	 * role="status" để trình đọc màn hình đọc lên khi chữ đổi — closeModal()
	 * vừa trả tiêu điểm về đúng cái nút ngay phía trên nó.
	 *
	 * @param {string|undefined} chu
	 */
	function baoDuoiNutBanner( chu ) {
		if ( ! chu ) {
			return;
		}

		var nut = document.querySelector( '[data-nntm-chuoi-tri]' );
		if ( ! nut || ! nut.parentNode ) {
			return;
		}

		var o = document.querySelector( '[data-nntm-congtu-bao-nut]' );

		if ( ! o ) {
			o = document.createElement( 'p' );
			o.className = 'nntm-cong-tu__tong-ket nntm-cong-tu__tong-ket--duoi-nut';
			o.setAttribute( 'data-nntm-congtu-bao-nut', '' );
			o.setAttribute( 'role', 'status' );
			nut.parentNode.insertBefore( o, nut.nextSibling );
		}

		o.textContent = chu;
	}

	function doiNutBanner( nhanMoi ) {
		if ( ! nhanMoi ) {
			return;
		}

		var nutList = document.querySelectorAll( '[data-nntm-chuoi-tri="tham-gia"]' );
		for ( var i = 0; i < nutList.length; i++ ) {
			nutList[ i ].setAttribute( 'data-nntm-chuoi-tri', 'cap-nhat' );
			nutList[ i ].textContent = nhanMoi;
		}
	}
} )();
