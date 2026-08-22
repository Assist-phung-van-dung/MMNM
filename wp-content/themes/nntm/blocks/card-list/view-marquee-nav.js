/**
 * Hai nút mũi tên cho MỌI băng TỰ CHẠY của block nntm/card-list:
 *   - băng danh sách BÀI (layout=marquee) — dải "Nghi Quỹ" trang Kim Cương;
 *   - băng nguồn YOUTUBE (videoSource=youtube) — dải "Xuyên Vạn Kiếp" (Gót
 *     Son) và "GITA CENTER x NẴNG NHÂN TỊCH MẶC" ở trang chủ.
 *
 * Yêu cầu chủ dự án 21/08/2026: "phần Nghi Quỹ thêm icon nhấn 2 bên để anh
 * có thể click chuyển qua lại các item trên slider" và "chỉnh luôn Xuyên Vạn
 * Kiếp và GITA CENTER x NẴNG NHÂN TỊCH MẶC ở home page anh cũng muốn có 2
 * icon cho slider".
 *
 * Hai loại băng có tên lớp CSS khác nhau (lịch sử: băng YouTube ra trước,
 * cố ý KHÔNG dùng chung lớp với băng bài) nhưng KỸ THUẬT giống hệt nhau, nên
 * ở đây chỉ khai bảng tên lớp rồi chạy chung một bộ mã — không có hai bản
 * logic để lệch nhau.
 *
 * CƠ CHẾ — vì sao không dùng scrollLeft như carousel:
 *   Băng này KHÔNG cuộn được (khung ngoài overflow:hidden, cố ý — xem
 *   inc/render-card-list-marquee.php), nó chạy bằng @keyframes dịch
 *   translateX(0 → -50%) trên track. Nên nút bấm ở đây làm ba việc:
 *     1. Lần bấm đầu: đọc vị trí track ĐANG ở đâu (ma trận transform mà
 *        animation đang áp), đóng băng nó thành transform nội tuyến rồi tắt
 *        animation — băng dừng đúng chỗ đang thấy, không giật về đầu.
 *     2. Mỗi lần bấm: dịch thêm/bớt đúng MỘT bước thẻ (bề rộng thẻ + khe),
 *        có transition nên mắt thấy nó trượt.
 *     3. Chạy vòng vô hạn: danh sách thẻ đã được render.php NHÂN ĐÔI, nên
 *        dịch đi đúng một chu kỳ (nửa số thẻ × bước thẻ) cho ra hình ảnh y
 *        hệt. Khi sắp ra khỏi khoảng (-chu kỳ, 0] thì nhảy KHÔNG hoạt hình
 *        về vị trí tương đương rồi mới trượt tiếp — người xem không thấy
 *        cú nhảy, và băng không bao giờ hết thẻ.
 *
 * Bấm nút là người xem tiếp quản: băng KHÔNG tự chạy lại nữa trong phiên đó
 * — cùng nguyên tắc đã chốt cho carousel ("người dùng tự điều khiển thì
 * không giật băng khỏi tay họ nữa", xem view.js).
 *
 * Đo bằng offsetWidth, KHÔNG dùng getBoundingClientRect().width:
 * assets/css/responsive.css thu nhỏ cả .nntm-site-frame bằng `zoom` ở màn
 * dưới 1366 nên rect trả về số ĐÃ thu nhỏ, trộn với px dàn trang của
 * transform sẽ lệch đúng bằng tỉ lệ zoom (bài học đã ghi trong view.js).
 *
 * JS thuần, không thư viện, không bước build — khai qua "viewScript" trong
 * block.json cùng view.js, view-paging.js và view-yt-lightbox.js.
 */
( function () {
	'use strict';

	/*
	 * Mỗi loại băng: lớp khung ngoài, lớp track (thứ bị dịch), lớp MỘT ĐƠN VỊ
	 * LẶP của track, và lớp gắn lên track khi người xem đã tiếp quản.
	 *
	 * Đơn vị lặp của băng YouTube là ".__yt-cell" (khung bọc ảnh + tiêu đề),
	 * KHÔNG phải ".__yt-item" (chỉ phần ảnh) — cell mới là flex-item thật của
	 * track nên bề rộng của nó mới là bước dịch đúng.
	 */
	var LOAI_BANG = [
		{
			bang: '.nntm-card-list__marquee',
			track: '.nntm-card-list__marquee-track',
			item: '.nntm-card-list__marquee-item',
			lopTay: 'nntm-card-list__marquee-track--tay'
		},
		{
			bang: '.nntm-card-list__yt-marquee',
			track: '.nntm-card-list__yt-track',
			item: '.nntm-card-list__yt-cell',
			lopTay: 'nntm-card-list__yt-track--tay'
		}
	];

	/**
	 * Đọc translateX hiện tại (px) của một phần tử, kể cả khi giá trị đang do
	 * @keyframes áp vào. Tự phân tích chuỗi "matrix(...)"/"matrix3d(...)" thay
	 * vì dùng DOMMatrix để giữ đúng lối viết ES5 của các file view khác.
	 *
	 * @param {Element} el
	 * @return {number}
	 */
	function docTranslateX( el ) {
		var chuoi = window.getComputedStyle( el ).transform;

		if ( ! chuoi || 'none' === chuoi ) {
			return 0;
		}

		var so = chuoi.match( /matrix(3d)?\(([^)]+)\)/ );
		if ( ! so ) {
			return 0;
		}

		var phan = so[ 2 ].split( ',' );

		// matrix(a,b,c,d,e,f) -> e là translateX; matrix3d 16 số -> phần tử 13.
		var x = so[ 1 ] ? parseFloat( phan[ 12 ] ) : parseFloat( phan[ 4 ] );

		return isNaN( x ) ? 0 : x;
	}

	/**
	 * @param {Element} bang   Khung ngoài của băng.
	 * @param {Object}  caiDat Một phần tử của LOAI_BANG.
	 */
	function ganNutChoBang( bang, caiDat ) {
		var track = bang.querySelector( caiDat.track );
		var nutLui = bang.querySelector( '.nntm-card-list__marquee-nav--prev' );
		var nutTien = bang.querySelector( '.nntm-card-list__marquee-nav--next' );

		if ( ! track || ! nutLui || ! nutTien || 'true' === bang.getAttribute( 'data-nntm-marquee-nav' ) ) {
			return;
		}

		bang.setAttribute( 'data-nntm-marquee-nav', 'true' );

		// null = chưa ai bấm, băng vẫn đang tự chạy bằng animation.
		var viTri = null;

		/**
		 * Bề rộng một bước = bề rộng thẻ + khe giữa hai thẻ.
		 *
		 * @return {number}
		 */
		function buocThe() {
			var the = track.querySelector( caiDat.item );
			if ( ! the ) {
				return 0;
			}

			var kieu = window.getComputedStyle( track );
			var khe  = parseFloat( kieu.columnGap || kieu.gap || '0' ) || 0;

			return the.offsetWidth + khe;
		}

		/**
		 * Một chu kỳ = nửa số thẻ (danh sách gốc trước khi nhân đôi) × bước thẻ.
		 * Dịch đi đúng chu kỳ này cho ra hình ảnh không phân biệt được.
		 *
		 * @return {number}
		 */
		function chuKy() {
			var soThe = track.querySelectorAll( caiDat.item ).length;
			return ( soThe / 2 ) * buocThe();
		}

		/**
		 * @param {number}  x
		 * @param {boolean} muot Có trượt (transition) hay nhảy thẳng.
		 */
		function datX( x, muot ) {
			if ( ! muot ) {
				track.style.transitionProperty = 'none';
			}

			track.style.transform = 'translateX(' + x + 'px)';

			if ( ! muot ) {
				/*
				 * Buộc trình duyệt chốt giá trị vừa đặt NGAY (đọc offsetWidth
				 * là một reflow đồng bộ) rồi mới trả transition lại. Không có
				 * dòng này thì lần datX(muot=true) ngay sau đó sẽ trượt từ vị
				 * trí CŨ — người xem thấy băng lao ngang cả nghìn px.
				 */
				void track.offsetWidth;
				track.style.transitionProperty = '';
			}
		}

		/**
		 * Lần bấm đầu tiên: đóng băng vị trí animation đang áp, tắt tự chạy.
		 */
		function tiepQuan() {
			if ( null !== viTri ) {
				return;
			}

			viTri = docTranslateX( track );
			track.classList.add( caiDat.lopTay );

			/*
			 * TẮT ANIMATION BẰNG STYLE NỘI TUYẾN, KHÔNG chỉ dựa vào lớp CSS
			 * ".*--tay". Hai lý do, cái thứ hai là lỗi đã ĐO THẬT 21/08/2026
			 * (chủ dự án: "nhấn icon ở slider homepage không nhảy qua item
			 * tiếp theo"):
			 *
			 *  1. Hoạt ảnh CSS đang chạy GHI ĐÈ style nội tuyến (animation
			 *     nằm ở tầng cascade cao hơn author style), nên còn animation
			 *     là track.style.transform bên dưới không có tác dụng gì.
			 *  2. Lớp ".*--tay" chỉ có MỘT class nên bị mọi quy tắc cụ thể hơn
			 *     đè: trang chủ khai lại animation riêng ở
			 *     assets/css/pages/homepage-figma.css bằng
			 *     ".home .nntm-card-list--nen-toi .nntm-card-list__yt-track"
			 *     (ba class) — "animation: none" của lớp --tay thua, băng vẫn
			 *     tự chạy và hai mũi tên trông như không làm gì cả.
			 *
			 * Style nội tuyến thắng mọi quy tắc trong stylesheet (trừ
			 * !important), nên cách này miễn nhiễm với các bản ghi đè theo
			 * trang, kể cả những bản viết thêm sau này. Lớp CSS vẫn giữ để
			 * mang `transition` và để đọc code hiểu ngay trạng thái.
			 */
			track.style.animation = 'none';

			datX( viTri, false );
		}

		/**
		 * @param {number} huong 1 = sang thẻ tiếp theo, -1 = thẻ trước.
		 */
		function di( huong ) {
			tiepQuan();

			var buoc = buocThe();
			var ck   = chuKy();

			if ( buoc <= 0 || ck <= 0 ) {
				return;
			}

			var dich = viTri - ( huong * buoc );

			/*
				Giữ vị trí trong khoảng (-ck, 0]. Chuẩn hoá TRƯỚC khi trượt
				(nhảy không hoạt hình sang vị trí tương đương) để cú nhảy
				không bao giờ lọt vào giữa một chuyển động đang chạy.
			*/
			if ( dich <= -ck ) {
				viTri += ck;
				datX( viTri, false );
				dich += ck;
			} else if ( dich > 0 ) {
				viTri -= ck;
				datX( viTri, false );
				dich -= ck;
			}

			viTri = dich;
			datX( viTri, true );
		}

		nutLui.addEventListener( 'click', function () {
			di( -1 );
		} );

		nutTien.addEventListener( 'click', function () {
			di( 1 );
		} );
	}

	/**
	 * @param {Element|Document} [root] Chỉ quét trong phạm vi này (dùng khi một
	 *        khối vừa được thay HTML — xem view-paging.js). Bản thân hàm còn
	 *        chống gắn trùng bằng data-nntm-marquee-nav.
	 */
	function ganNutChoMoiBang( root ) {
		var pham = root || document;

		for ( var i = 0; i < LOAI_BANG.length; i++ ) {
			var caiDat = LOAI_BANG[ i ];
			var bangs  = pham.querySelectorAll( caiDat.bang );

			for ( var j = 0; j < bangs.length; j++ ) {
				ganNutChoBang( bangs[ j ], caiDat );
			}
		}
	}

	document.addEventListener( 'nntm-card-list-refresh', function ( event ) {
		ganNutChoMoiBang( ( event.detail && event.detail.root ) || document );
	} );

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', function () {
			ganNutChoMoiBang();
		} );
	} else {
		ganNutChoMoiBang();
	}
} )();
