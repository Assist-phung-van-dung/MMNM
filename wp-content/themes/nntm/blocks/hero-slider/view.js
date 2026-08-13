/**
 * View script cho block nntm/hero-slider — băng chuyền đầu trang chủ,
 * JavaScript thuần, không thư viện ngoài, không bước build. Khai qua
 * "viewScript": "file:./view.js" trong block.json.
 *
 * Chủ dự án yêu cầu rõ: TỰ CHẠY. Mặc định bật, chu kỳ đọc từ
 * data-nntm-interval (giây, render.php đã chặn biên 2–30). Tự dừng khi:
 *   - rê chuột vào khối (mouseenter) — tiếp tục khi rê ra (mouseleave).
 *   - đưa tiêu điểm bàn phím vào bất kỳ phần tử bấm được trong khối
 *     (focusin) — tiếp tục khi tiêu điểm ra khỏi khối hẳn (focusout, kiểm
 *     tra relatedTarget để không dừng nhầm khi tiêu điểm chỉ chuyển giữa
 *     hai phần tử con).
 *   - tab bị ẩn (document.hidden qua visibilitychange).
 *   - prefers-reduced-motion: reduce — TẮT HẲN, không tạo bộ đếm giờ nào,
 *     kiểm tra một lần khi khởi tạo (không cần theo dõi đổi động vì đây
 *     là quyết định "có autoplay hay không" ngay từ đầu).
 *
 * Chuyển tấm bằng đổi class .is-active (làm mờ chồng qua CSS transition
 * opacity ở style.css) — không trượt ngang, đúng yêu cầu nhiệm vụ.
 */
( function () {
	'use strict';

	function prefersReducedMotion() {
		return !! ( window.matchMedia && window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches );
	}

	/**
	 * Khởi tạo một khối băng chuyền.
	 *
	 * @param {Element} root Phần tử ".nntm-hero-slider".
	 */
	function nntmInitHeroSlider( root ) {
		var stage = root.querySelector( '.nntm-hero-slider__stage' );
		var slides = root.querySelectorAll( '.nntm-hero-slider__slide' );
		var dotsWrap = root.querySelector( '[data-nntm-hero-dots]' );
		var dots = root.querySelectorAll( '.nntm-hero-slider__dot' );
		var statusEl = root.querySelector( '[data-nntm-hero-status]' );
		var prevBtn = root.querySelector( '[data-nntm-hero-prev]' );
		var nextBtn = root.querySelector( '[data-nntm-hero-next]' );

		if ( ! stage || slides.length < 2 ) {
			// Chỉ 1 tấm (hoặc không tìm thấy khung) -> không có gì để tự
			// chạy/chuyển, đúng yêu cầu "chỉ 1 tấm thì không tự chạy".
			return;
		}

		var total = slides.length;
		var currentIndex = 0;
		var timerId = null;
		var isPaused = false;

		// Đọc lại từ thuộc tính data- (render.php đã chặn biên 2–30, ở đây
		// vẫn tự vệ thêm một lần cho chắc — không tin dữ liệu HTML tuyệt đối).
		var autoplayEnabled = 'true' === String( root.getAttribute( 'data-nntm-autoplay' ) ) || '1' === root.getAttribute( 'data-nntm-autoplay' );
		var intervalSeconds = parseFloat( root.getAttribute( 'data-nntm-interval' ) );
		if ( ! isFinite( intervalSeconds ) || intervalSeconds <= 0 ) {
			intervalSeconds = 6;
		}
		intervalSeconds = Math.max( 2, Math.min( 30, intervalSeconds ) );

		// Tắt hẳn khi giảm chuyển động — không tạo bộ đếm giờ nào cả.
		var autoplayAllowed = autoplayEnabled && ! prefersReducedMotion();

		function statusText( index ) {
			// Khớp đúng chuỗi PHP nntm_hero_slider_status_text() — giữ dịch
			// riêng ở đây để view.js chạy độc lập, không phụ thuộc bước
			// enqueue wp_localize_script nào khác ngoài "viewScript".
			return ( window.nntmHeroSliderI18n && window.nntmHeroSliderI18n.tamTrenTong )
				? window.nntmHeroSliderI18n.tamTrenTong.replace( '%1$d', String( index + 1 ) ).replace( '%2$d', String( total ) )
				: 'Tấm ' + ( index + 1 ) + ' trên ' + total;
		}

		function goTo( index ) {
			var nextIndex = ( index + total ) % total;

			for ( var i = 0; i < slides.length; i++ ) {
				slides[ i ].classList.toggle( 'is-active', i === nextIndex );
			}
			for ( var d = 0; d < dots.length; d++ ) {
				var isCurrent = d === nextIndex;
				dots[ d ].classList.toggle( 'is-active', isCurrent );
				if ( isCurrent ) {
					dots[ d ].setAttribute( 'aria-current', 'true' );
				} else {
					dots[ d ].removeAttribute( 'aria-current' );
				}
			}

			currentIndex = nextIndex;

			if ( statusEl ) {
				statusEl.textContent = statusText( currentIndex );
			}
		}

		function goNext() {
			goTo( currentIndex + 1 );
		}

		function goPrev() {
			goTo( currentIndex - 1 );
		}

		// Bấm chấm/mũi tên là một tương tác chủ động — khởi động lại đếm
		// giờ tự chạy từ đầu để không nhảy tấm quá gần ngay sau khi khách
		// vừa tự chọn.
		function restartTimerAfterInteraction() {
			if ( autoplayAllowed && ! isPaused ) {
				stopTimer();
				startTimer();
			}
		}

		function startTimer() {
			if ( ! autoplayAllowed || isPaused || null !== timerId || document.hidden ) {
				return;
			}
			timerId = window.setInterval( goNext, intervalSeconds * 1000 );
		}

		function stopTimer() {
			if ( null !== timerId ) {
				window.clearInterval( timerId );
				timerId = null;
			}
		}

		function pause() {
			isPaused = true;
			stopTimer();
		}

		function resume() {
			isPaused = false;
			startTimer();
		}

		// ---------- Dừng khi rê chuột vào ----------
		root.addEventListener( 'mouseenter', pause );
		root.addEventListener( 'mouseleave', resume );

		// ---------- Dừng khi tiêu điểm bàn phím vào khối ----------
		root.addEventListener( 'focusin', pause );
		root.addEventListener( 'focusout', function ( event ) {
			// relatedTarget là phần tử SẮP nhận tiêu điểm — còn nằm trong
			// root thì tiêu điểm chỉ chuyển giữa hai phần tử con, chưa
			// thực sự "ra khỏi" băng chuyền.
			if ( ! event.relatedTarget || ! root.contains( event.relatedTarget ) ) {
				resume();
			}
		} );

		// ---------- Dừng khi tab bị ẩn ----------
		document.addEventListener( 'visibilitychange', function () {
			if ( document.hidden ) {
				stopTimer();
			} else {
				startTimer();
			}
		} );

		// ---------- Chấm bấm chuyển tấm ----------
		for ( var t = 0; t < dots.length; t++ ) {
			( function ( index ) {
				dots[ index ].addEventListener( 'click', function () {
					goTo( index );
					restartTimerAfterInteraction();
				} );
			} )( t );
		}

		// ---------- Nút mũi tên trái/phải bấm chuyển tấm ----------
		if ( prevBtn ) {
			prevBtn.addEventListener( 'click', function () {
				goPrev();
				restartTimerAfterInteraction();
			} );
		}

		if ( nextBtn ) {
			nextBtn.addEventListener( 'click', function () {
				goNext();
				restartTimerAfterInteraction();
			} );
		}

		// ---------- Phím mũi tên trái/phải khi băng chuyền có tiêu điểm ----------
		stage.addEventListener( 'keydown', function ( event ) {
			if ( 'ArrowLeft' === event.key ) {
				event.preventDefault();
				goPrev();
			} else if ( 'ArrowRight' === event.key ) {
				event.preventDefault();
				goNext();
			}
		} );

		if ( dotsWrap ) {
			dotsWrap.setAttribute( 'tabindex', dotsWrap.getAttribute( 'tabindex' ) || '-1' );
		}

		goTo( 0 );
		startTimer();
	}

	function nntmInitAllHeroSliders() {
		var sliders = document.querySelectorAll( '.nntm-hero-slider' );
		for ( var i = 0; i < sliders.length; i++ ) {
			nntmInitHeroSlider( sliders[ i ] );
		}
	}

	// Chuỗi tiếng Việt dùng lại — tránh lặp/lệch dấu, gói gọn ở một chỗ.
	// KHÔNG dùng wp_localize_script vì view.js phải chạy được độc lập,
	// không phụ thuộc bước enqueue nào khác ngoài khai báo "viewScript".
	window.nntmHeroSliderI18n = window.nntmHeroSliderI18n || {
		tamTrenTong: 'Tấm %1$d trên %2$d',
	};

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', nntmInitAllHeroSliders );
	} else {
		nntmInitAllHeroSliders();
	}
} )();
