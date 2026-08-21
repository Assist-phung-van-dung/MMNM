/**
 * Popup TOÀN MÀN HÌNH phát video YouTube — dùng chung cho mọi khối có video.
 *
 * Mở popup từ:
 *   - một thẻ của băng chạy trang chủ, ".nntm-card-list__yt-item" (dải "Xuyên
 *     Vạn Kiếp" / "GITA CENTER x NẴNG NHÂN TỊCH MẶC", block nntm/card-list);
 *   - một khung video của dải phim, ".nntm-engineering-earth__video-slot"
 *     (khung lớn + thẻ nhỏ tràn mép, block nntm/engineering-earth).
 *
 * Yêu cầu chủ dự án 21/08/2026: "khi nhấn vào sẽ mở 1 popup full màn hình và
 * xem hết video", rồi "2 video ở block Dải phim anh cũng muốn nhấn vào sẽ mở
 * popup".
 *
 * Khung popup do PHP in sẵn MỘT LẦN ở chân trang (xem inc/video-lightbox.php)
 * — file này chỉ chèn/tháo <iframe>. THÁO iframe khi đóng là cách chắc chắn
 * nhất để video dừng phát: YouTube không có cách dừng nào khác mà không phải
 * nạp thêm thư viện iframe_api.
 *
 * KHÁC hai kiểu phát tự động đã có sẵn trên trang: bản xem thử khi rê chuột
 * (blocks/card-list/view.js) và video nền của dải phim
 * (blocks/engineering-earth/view.js) đều CÂM TIẾNG, KHÔNG thanh điều khiển,
 * chỉ để nhá hình. Bản ở đây là bản ĐẦY ĐỦ — có tiếng, có thanh điều khiển,
 * cho phép bật fullscreen thật của YouTube.
 *
 * Ở dải phim, mỗi khung có sẵn một thẻ <a> phủ lên trỏ tới bài viết video
 * (".nntm-engineering-earth__video-link"). File này CHẶN cú nhấp đó lại để mở
 * popup, nhưng KHÔNG xoá thẻ <a> — tắt JS thì nó vẫn dẫn sang trang bài viết
 * như trước, và Enter trên bàn phím vẫn mở được popup vì <a> phát ra click
 * thật.
 *
 * Đóng bằng: nút ×, bấm ra ngoài khung phim, hoặc Esc — cùng ba lối đóng mà
 * các popup khác của theme đang dùng (assets/js/auth-modal.js,
 * assets/js/cong-tu-modal.js). Có bẫy tiêu điểm và trả tiêu điểm về đúng thẻ
 * vừa bấm sau khi đóng.
 *
 * JS thuần, không thư viện, không bước build.
 */
( function () {
	'use strict';

	var ID_POPUP  = 'nntm-yt-lightbox';
	var LOP_MO    = 'nntm-yt-lightbox-mo';
	var theTruocDo = null;

	/**
	 * @return {HTMLElement|null}
	 */
	function layPopup() {
		return document.getElementById( ID_POPUP );
	}

	/**
	 * URL nhúng bản ĐẦY ĐỦ (có tiếng, có thanh điều khiển).
	 *
	 * @param {string} videoId
	 * @return {string}
	 */
	function urlNhung( videoId ) {
		return 'https://www.youtube.com/embed/' + encodeURIComponent( videoId ) +
			'?autoplay=1&playsinline=1&rel=0&modestbranding=1';
	}

	/**
	 * @param {string}       videoId
	 * @param {string}       nhan     Chữ cho thuộc tính title của iframe.
	 * @param {Element|null} theGoc   Thẻ vừa bấm, để trả tiêu điểm về sau khi đóng.
	 */
	function mo( videoId, nhan, theGoc ) {
		var popup = layPopup();
		var khung = popup ? popup.querySelector( '[data-nntm-yt-lightbox-frame]' ) : null;

		if ( ! popup || ! khung || ! videoId ) {
			return;
		}

		theTruocDo = theGoc || document.activeElement;

		var iframe = document.createElement( 'iframe' );
		iframe.src = urlNhung( videoId );
		iframe.setAttribute( 'title', nhan || '' );
		iframe.setAttribute( 'frameborder', '0' );
		iframe.setAttribute( 'allow', 'autoplay; encrypted-media; fullscreen; picture-in-picture' );
		iframe.setAttribute( 'allowfullscreen', 'allowfullscreen' );
		iframe.setAttribute( 'referrerpolicy', 'strict-origin-when-cross-origin' );

		khung.textContent = '';
		khung.appendChild( iframe );

		popup.hidden = false;
		document.documentElement.classList.add( LOP_MO );

		var nutDong = popup.querySelector( '[data-nntm-yt-lightbox-close]' );
		if ( nutDong && 'function' === typeof nutDong.focus ) {
			nutDong.focus();
		}
	}

	function dong() {
		var popup = layPopup();
		if ( ! popup || popup.hidden ) {
			return;
		}

		var khung = popup.querySelector( '[data-nntm-yt-lightbox-frame]' );
		if ( khung ) {
			khung.textContent = ''; // Tháo iframe -> video dừng hẳn.
		}

		popup.hidden = true;
		document.documentElement.classList.remove( LOP_MO );

		if ( theTruocDo && 'function' === typeof theTruocDo.focus ) {
			theTruocDo.focus();
		}
		theTruocDo = null;
	}

	/**
	 * Mở popup từ một thẻ video của băng.
	 *
	 * @param {Element} the Phần tử ".nntm-card-list__yt-item".
	 */
	function moTuThe( the ) {
		mo( the.getAttribute( 'data-video-id' ), the.getAttribute( 'aria-label' ) || '', the );
	}

	document.addEventListener( 'click', function ( event ) {
		if ( ! event.target || ! event.target.closest ) {
			return;
		}

		if ( event.target.closest( '[data-nntm-yt-lightbox-close]' ) ) {
			event.preventDefault();
			dong();
			return;
		}

		/*
		 * Hai nguồn mở popup. Duyệt theo thứ tự này vì thẻ băng chạy không bao
		 * giờ nằm trong khung dải phim và ngược lại — không có trường hợp một
		 * cú nhấp khớp cả hai.
		 */
		var the = event.target.closest( '.nntm-card-list__yt-item' ) ||
			event.target.closest( '.nntm-engineering-earth__video-slot' );

		if ( ! the || ! the.getAttribute( 'data-video-id' ) ) {
			return; // Chưa dán link video -> để thẻ <a> phủ (nếu có) chạy như thường.
		}

		event.preventDefault();
		moTuThe( the );
	} );

	/*
	 * Thẻ là <div role="button" tabindex="0"> (xem
	 * nntm_card_list_render_youtube_item()) nên trình duyệt KHÔNG tự biến
	 * Enter/Space thành click như <button> thật — phải tự xử lý, nếu không
	 * người dùng bàn phím không mở được video.
	 */
	document.addEventListener( 'keydown', function ( event ) {
		var popup = layPopup();

		if ( popup && ! popup.hidden ) {
			if ( 'Escape' === event.key ) {
				dong();
			} else if ( 'Tab' === event.key ) {
				bayTieuDiem( event, popup );
			}
			return;
		}

		if ( 'Enter' !== event.key && ' ' !== event.key && 'Spacebar' !== event.key ) {
			return;
		}

		var the = event.target && event.target.closest ? event.target.closest( '.nntm-card-list__yt-item' ) : null;
		if ( ! the || ! the.getAttribute( 'data-video-id' ) ) {
			return;
		}

		event.preventDefault(); // Space cuộn trang nếu không chặn.
		moTuThe( the );
	} );

	/**
	 * Giữ tiêu điểm trong popup. Ở đây chỉ có nút đóng và chính iframe, nên
	 * vòng lại về nút đóng là đủ — không để Tab tuột ra trang phía sau đang
	 * bị lớp phủ che.
	 *
	 * @param {KeyboardEvent} event
	 * @param {HTMLElement}   popup
	 */
	function bayTieuDiem( event, popup ) {
		var nutDong = popup.querySelector( '[data-nntm-yt-lightbox-close]' );
		if ( ! nutDong ) {
			return;
		}

		event.preventDefault();
		nutDong.focus();
	}
} )();
