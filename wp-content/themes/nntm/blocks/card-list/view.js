/**
 * View script cho block nntm/card-list — chỉ có tác dụng khi layout="carousel"
 * (băng thẻ cuộn ngang, xem render.php + style.css). JavaScript thuần, không
 * thư viện ngoài, không bước build — khai qua "viewScript": "file:./view.js"
 * trong block.json.
 *
 * Việc của file này:
 *  - Nút lùi/tiến (<button type="button">) cuộn băng thẻ theo từng "trang thẻ".
 *  - Băng thẻ cuộn được bằng bàn phím: Tab tới rồi dùng phím mũi tên trái/phải.
 *  - Tự vô hiệu hóa (disabled) nút lùi khi đã ở đầu, nút tiến khi đã ở cuối
 *    (kiểu dáng "disabled" hiện rõ bằng mắt nằm ở style.css).
 *  - Nếu tổng số thẻ KHÔNG tràn khung nhìn (vừa đủ hoặc ít hơn số thẻ hiển
 *    thị được) thì ẨN HẲN cả hai nút (thuộc tính "hidden"), không tự chạy.
 *  - Tự chạy (autoplay): đọc data-autoplay / data-autoplay-interval do
 *    render.php in ra trên ".nntm-card-list__carousel" (KHÔNG có <script>
 *    nội tuyến) — sang thẻ kế tiếp theo chu kỳ, hết thẻ cuối quay lại đầu.
 *    Bắt buộc dừng khi: rê chuột vào băng, tiêu điểm bàn phím vào băng, tab
 *    bị ẩn (document.hidden) — ba trường hợp này CHỈ TẠM DỪNG, tự chạy lại
 *    khi hết. Người dùng tự cuộn tay (chuột/trackpad/chạm, không qua nút hay
 *    autoplay) thì DỪNG HẲN cho phiên xem đó — coi là họ đã tự điều khiển,
 *    không nên "giật" băng khỏi tay họ nữa.
 *  - Tôn trọng prefers-reduced-motion: bật thì cuộn không hoạt hình (nhảy
 *    thẳng, không animation) VÀ tắt hẳn tự chạy — giống quy ước đã dùng ở
 *    tokens.css / term-list.
 *
 * Một trang có thể có nhiều khối card-list carousel — hàm khởi tạo chạy
 * độc lập cho từng khối tìm thấy.
 */
( function () {
	'use strict';

	/**
	 * @param {Element} root Phần tử ".nntm-card-list__carousel".
	 */
	function nntmInitCardListCarousel( root ) {
		var track = root.querySelector( '.nntm-card-list__track' );
		var prevBtn = root.querySelector( '.nntm-card-list__nav--prev' );
		var nextBtn = root.querySelector( '.nntm-card-list__nav--next' );

		if ( ! track || ! prevBtn || ! nextBtn ) {
			return;
		}

		function prefersReducedMotion() {
			return !! ( window.matchMedia && window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches );
		}

		// Cuộn mỗi lần bằng đúng bề rộng một thẻ + khoảng cách (gap) giữa
		// các thẻ, để mỗi lần bấm nút/nhấn phím là "sang đúng một thẻ".
		function scrollStep() {
			var firstItem = track.querySelector( '.nntm-card-list__track-item' );
			var itemWidth = firstItem ? firstItem.getBoundingClientRect().width : track.clientWidth;
			var trackStyles = window.getComputedStyle( track );
			var gap = parseFloat( trackStyles.columnGap || trackStyles.gap || '0' ) || 0;

			return itemWidth + gap;
		}

		// true khi bang co the nhieu hon so the vua khung nhin — chi khi do
		// moi can nut lui/tien va moi cho phep tu chay.
		var hasOverflow = false;

		function updateButtonsState() {
			var maxScroll = track.scrollWidth - track.clientWidth;
			hasOverflow = maxScroll > 1;

			// Yeu cau: so the it hon hoac bang so the hien thi duoc (khong tran)
			// -> khong hien nut lui/tien (an han bang "hidden", khong chi disabled).
			prevBtn.hidden = ! hasOverflow;
			nextBtn.hidden = ! hasOverflow;

			if ( ! hasOverflow ) {
				prevBtn.disabled = true;
				nextBtn.disabled = true;
				nntmSyncAutoplay();
				return;
			}

			prevBtn.disabled = track.scrollLeft <= 1;
			nextBtn.disabled = track.scrollLeft >= maxScroll - 1;
			nntmSyncAutoplay();
		}

		// Danh dau cuon dang duoc GOI BANG JS (nut, phim mui ten, tu chay) de
		// phan biet voi cuon THUC SU do tay nguoi dung keo/lan (wheel/touch) —
		// chi loai sau moi tinh la "nguoi dung tu cuon tay" va lam dung tu chay han.
		var isProgrammaticScroll = false;

		function scrollByDirection( direction ) {
			isProgrammaticScroll = true;
			track.scrollBy( {
				left: direction * scrollStep(),
				behavior: prefersReducedMotion() ? 'auto' : 'smooth',
			} );
		}

		function scrollToStart() {
			isProgrammaticScroll = true;
			track.scrollTo( {
				left: 0,
				behavior: prefersReducedMotion() ? 'auto' : 'smooth',
			} );
		}

		prevBtn.addEventListener( 'click', function () {
			scrollByDirection( -1 );
		} );

		nextBtn.addEventListener( 'click', function () {
			scrollByDirection( 1 );
		} );

		// Bàn phím: Tab tới băng thẻ (tabindex="0" đặt sẵn trong render.php)
		// rồi dùng mũi tên trái/phải để cuộn — không phụ thuộc hành vi cuộn
		// mặc định (không đồng nhất) của trình duyệt trên phần tử overflow.
		track.addEventListener( 'keydown', function ( event ) {
			if ( 'ArrowRight' === event.key ) {
				event.preventDefault();
				scrollByDirection( 1 );
			} else if ( 'ArrowLeft' === event.key ) {
				event.preventDefault();
				scrollByDirection( -1 );
			}
		} );

		// Cuộn bằng chuột/cảm ứng/trackpad cũng phải cập nhật lại trạng thái nút,
		// và nếu KHÔNG phải do JS gọi (nút/phím/tự chạy) thì coi là người dùng
		// tự cuộn tay — dừng tự chạy hẳn cho phiên xem này.
		var scrollUpdateTimer = null;
		track.addEventListener(
			'scroll',
			function () {
				if ( scrollUpdateTimer ) {
					window.clearTimeout( scrollUpdateTimer );
				}
				scrollUpdateTimer = window.setTimeout( function () {
					updateButtonsState();

					if ( ! isProgrammaticScroll ) {
						userScrolledManually = true;
						stopAutoplayTimer();
					}
					isProgrammaticScroll = false;
				}, 50 );
			},
			{ passive: true }
		);

		window.addEventListener( 'resize', updateButtonsState );

		// ---------- Tự chạy (autoplay) ----------
		// Cấu hình do render.php in ra qua data-* trên .nntm-card-list__carousel
		// (đọc attribute autoplay / autoplayInterval của block) — không có
		// <script> nội tuyến, đúng quy ước dự án.
		var autoplayAttrEnabled = 'true' === root.getAttribute( 'data-autoplay' );
		var autoplayIntervalMs = ( function () {
			var seconds = parseFloat( root.getAttribute( 'data-autoplay-interval' ) );
			if ( ! seconds || isNaN( seconds ) ) {
				seconds = 6;
			}
			// Giới hạn phòng hộ 2–20s, khớp block.json — đề phòng thuộc tính bị
			// chỉnh trực tiếp qua REST/console ngoài phạm vi RangeControl.
			seconds = Math.max( 2, Math.min( 20, seconds ) );
			return seconds * 1000;
		} )();

		var userScrolledManually = false; // nguoi dung tu cuon tay -> dung han
		var isHovered = false;
		var isFocusedWithin = false;
		var autoplayTimerId = null;

		function autoplayEligible() {
			return autoplayAttrEnabled && hasOverflow && ! prefersReducedMotion() && ! userScrolledManually;
		}

		function autoplayShouldRunNow() {
			return autoplayEligible() && ! isHovered && ! isFocusedWithin && ! document.hidden;
		}

		function autoplayTick() {
			var maxScroll = track.scrollWidth - track.clientWidth;

			if ( track.scrollLeft >= maxScroll - 1 ) {
				// Da het the cuoi — quay lai the dau.
				scrollToStart();
			} else {
				scrollByDirection( 1 );
			}
		}

		function startAutoplayTimer() {
			if ( autoplayTimerId ) {
				return;
			}
			autoplayTimerId = window.setInterval( autoplayTick, autoplayIntervalMs );
		}

		function stopAutoplayTimer() {
			if ( autoplayTimerId ) {
				window.clearInterval( autoplayTimerId );
				autoplayTimerId = null;
			}
		}

		// Goi lai moi khi mot dieu kien co the thay doi (hover, focus, tab an,
		// resize lam mat/xuat hien do tran, nguoi dung tu cuon tay...) de quyet
		// dinh chay tiep hay tam dung/dung han.
		function nntmSyncAutoplay() {
			if ( autoplayShouldRunNow() ) {
				startAutoplayTimer();
			} else {
				stopAutoplayTimer();
			}
		}

		// Rê chuột vào băng -> tạm dừng; rời chuột -> chạy lại (nếu vẫn đủ điều kiện).
		root.addEventListener( 'mouseenter', function () {
			isHovered = true;
			nntmSyncAutoplay();
		} );
		root.addEventListener( 'mouseleave', function () {
			isHovered = false;
			nntmSyncAutoplay();
		} );

		// Tiêu điểm bàn phím vào bất kỳ phần tử nào trong băng (track hoặc nút)
		// -> tạm dừng; focusout khỏi cả băng -> chạy lại.
		root.addEventListener( 'focusin', function () {
			isFocusedWithin = true;
			nntmSyncAutoplay();
		} );
		root.addEventListener( 'focusout', function () {
			isFocusedWithin = false;
			nntmSyncAutoplay();
		} );

		// Tab bị ẩn (đổi tab/thu nhỏ trình duyệt) -> tạm dừng toàn site; hiện
		// lại -> mỗi băng tự kiểm tra lại điều kiện của mình.
		document.addEventListener( 'visibilitychange', nntmSyncAutoplay );

		updateButtonsState();
	}

	function nntmInitAllCardListCarousels() {
		var carousels = document.querySelectorAll( '.nntm-card-list__carousel' );

		for ( var i = 0; i < carousels.length; i++ ) {
			nntmInitCardListCarousel( carousels[ i ] );
		}
	}

	/**
	 * Băng "Netflix" nguồn YouTube (G1 — dải "Gót Son", xem render.php +
	 * inc/render-card-list-youtube.php + style.css khối .nntm-card-list__yt-*).
	 *
	 * Việc của khối dưới đây:
	 *  - Ảnh nền lấy trực tiếp từ img.youtube.com/…/maxresdefault.jpg — video
	 *    nào không có bản độ phân giải cao thì ảnh đó lỗi (404), tự đổi sang
	 *    hqdefault.jpg (luôn có).
	 *  - Rê chuột/focus liên tục vào một thẻ ĐÚNG 500ms mới chèn <iframe>
	 *    YouTube phát thử (autoplay, câm tiếng, không thanh điều khiển) —
	 *    tránh tạo hàng loạt iframe cùng lúc làm nặng trang. Rời chuột/blur
	 *    thì gỡ iframe ngay, trả lại ảnh tĩnh.
	 *  - prefers-reduced-motion: reduce -> KHÔNG chèn iframe (không tự phát
	 *    video gì cả), băng cũng không tự chạy (xử lý ở style.css).
	 *  - Băng tự chạy liên tục phải->trái bằng hoạt ảnh CSS (style.css); JS
	 *    ở đây KHÔNG đụng vào việc cuộn, chỉ lo ảnh + video từng thẻ.
	 */
	var NNTM_YT_HOVER_DELAY_MS = 500;

	function nntmPrefersReducedMotion() {
		return !! ( window.matchMedia && window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches );
	}

	function nntmYoutubeEmbedUrl( videoId ) {
		return 'https://www.youtube.com/embed/' + encodeURIComponent( videoId ) +
			'?autoplay=1&mute=1&controls=0&modestbranding=1&playsinline=1&rel=0';
	}

	/**
	 * @param {Element} item Phần tử ".nntm-card-list__yt-item".
	 */
	function nntmInitYoutubeItem( item ) {
		var videoId = item.getAttribute( 'data-video-id' );
		var frame   = item.querySelector( '.nntm-card-list__yt-frame' );
		var thumb   = item.querySelector( '.nntm-card-list__yt-thumb' );
		var hoverTimerId = null;

		// Anh maxresdefault.jpg khong ton tai voi video khong co ban do
		// phan giai cao -> tra ve 404. Chi doi mot lan, tranh vong lap loi
		// vo han neu ca hqdefault cung loi (rat hiem nhung phai chan).
		if ( thumb ) {
			var triedFallback = false;
			thumb.addEventListener( 'error', function () {
				if ( triedFallback ) {
					return;
				}
				triedFallback = true;
				var fallbackUrl = thumb.getAttribute( 'data-fallback' );
				if ( fallbackUrl ) {
					thumb.src = fallbackUrl;
				}
			} );
		}

		function clearHoverTimer() {
			if ( hoverTimerId ) {
				window.clearTimeout( hoverTimerId );
				hoverTimerId = null;
			}
		}

		function removeEmbed() {
			clearHoverTimer();
			item.classList.remove( 'is-playing' );
			if ( frame ) {
				frame.innerHTML = '';
			}
		}

		function insertEmbed() {
			if ( ! frame || ! videoId || nntmPrefersReducedMotion() ) {
				return;
			}
			if ( frame.firstChild ) {
				return; // da co iframe roi (vd focus roi hover them), khong tao trung.
			}

			var iframe = document.createElement( 'iframe' );
			iframe.src = nntmYoutubeEmbedUrl( videoId );
			iframe.setAttribute( 'title', item.getAttribute( 'aria-label' ) || '' );
			iframe.setAttribute( 'frameborder', '0' );
			iframe.setAttribute( 'allow', 'autoplay; encrypted-media' );
			iframe.setAttribute( 'tabindex', '-1' );
			frame.appendChild( iframe );
			item.classList.add( 'is-playing' );
		}

		function scheduleEmbed() {
			if ( nntmPrefersReducedMotion() ) {
				return;
			}
			clearHoverTimer();
			hoverTimerId = window.setTimeout( insertEmbed, NNTM_YT_HOVER_DELAY_MS );
		}

		// Chuot: rê vào bắt đầu đếm 500ms, rời ra là gỡ ngay (dù đã phát
		// hay chưa kịp phát).
		item.addEventListener( 'mouseenter', scheduleEmbed );
		item.addEventListener( 'mouseleave', removeEmbed );

		// Bàn phím: focus vào thẻ (Tab tới) phải chạy được như hover, theo
		// đúng yêu cầu khả năng tiếp cận của G1.
		item.addEventListener( 'focus', scheduleEmbed );
		item.addEventListener( 'blur', removeEmbed );
	}

	function nntmInitAllYoutubeMarquees() {
		var items = document.querySelectorAll( '.nntm-card-list__yt-item' );

		for ( var i = 0; i < items.length; i++ ) {
			nntmInitYoutubeItem( items[ i ] );
		}
	}

	function nntmInitCardListView() {
		nntmInitAllCardListCarousels();
		nntmInitAllYoutubeMarquees();
	}

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', nntmInitCardListView );
	} else {
		nntmInitCardListView();
	}
} )();
