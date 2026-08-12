/**
 * NNTM — Header: menu di động (hamburger), submenu bàn phím, menu tài
 * khoản, header dính khi cuộn (IntersectionObserver).
 *
 * SUY DOAN: chua co Figma mobile — hành vi menu thu gọn dưới đây (nút hamburger
 * mở/đóng, bẫy tiêu điểm) là suy đoán hoàn toàn vì Figma chỉ có khung desktop
 * 1366px. Khi có bản thiết kế mobile thật, đối chiếu lại toàn bộ phần này.
 *
 * JavaScript thuần, không phụ thuộc thư viện ngoài.
 */
( function () {
	'use strict';

	var header = document.querySelector( '.nntm-header' );
	if ( ! header ) {
		return;
	}

	var FOCUSABLE_SELECTOR = [
		'a[href]',
		'button:not([disabled])',
		'input:not([disabled])',
		'select:not([disabled])',
		'textarea:not([disabled])',
		'[tabindex]:not([tabindex="-1"])'
	].join( ',' );

	/* ---------- Menu di động (hamburger) ---------- */
	var toggle = header.querySelector( '.nntm-header__menu-toggle' );
	var nav    = header.querySelector( '.nntm-header__nav' );

	if ( toggle && nav ) {

		/**
		 * Mở menu di động: hiện nav, cập nhật aria-expanded, đưa tiêu điểm vào mục đầu tiên.
		 */
		function openMenu() {
			header.classList.add( 'nntm-header--menu-open' );
			toggle.setAttribute( 'aria-expanded', 'true' );
			document.addEventListener( 'keydown', onMenuKeydown, true );
			document.addEventListener( 'focus', trapFocus, true );

			var firstFocusable = nav.querySelector( FOCUSABLE_SELECTOR );
			if ( firstFocusable ) {
				firstFocusable.focus();
			}
		}

		/**
		 * Đóng menu di động: ẩn nav, cập nhật aria-expanded, trả tiêu điểm về nút hamburger.
		 *
		 * @param {boolean} restoreFocus Có đưa tiêu điểm về nút hamburger hay không.
		 */
		function closeMenu( restoreFocus ) {
			header.classList.remove( 'nntm-header--menu-open' );
			toggle.setAttribute( 'aria-expanded', 'false' );
			document.removeEventListener( 'keydown', onMenuKeydown, true );
			document.removeEventListener( 'focus', trapFocus, true );

			if ( restoreFocus ) {
				toggle.focus();
			}
		}

		/**
		 * Bẫy tiêu điểm bên trong nav khi menu di động đang mở — tiêu điểm rơi ra
		 * ngoài nav (kể cả nút hamburger) sẽ bị kéo về mục đầu tiên trong nav.
		 *
		 * @param {FocusEvent} event Sự kiện focus (bắt ở pha capture).
		 */
		function trapFocus( event ) {
			if ( nav.contains( event.target ) ) {
				return;
			}

			var focusableItems = nav.querySelectorAll( FOCUSABLE_SELECTOR );
			if ( focusableItems.length ) {
				focusableItems[ 0 ].focus();
			}
		}

		/**
		 * Xử lý phím Escape (đóng menu) khi menu di động đang mở.
		 *
		 * @param {KeyboardEvent} event Sự kiện bàn phím.
		 */
		function onMenuKeydown( event ) {
			if ( 'Escape' === event.key || 'Esc' === event.key ) {
				closeMenu( true );
			}
		}

		toggle.addEventListener( 'click', function () {
			var isOpen = header.classList.contains( 'nntm-header--menu-open' );
			if ( isOpen ) {
				closeMenu( false );
			} else {
				openMenu();
			}
		} );

		// Enter đã kích hoạt click mặc định trên <button>, không cần xử lý riêng.
		// Chỉ còn khoảng trắng (Space) cũng là hành vi mặc định của trình duyệt.

		/* ---------- Submenu cấp 2 trên desktop (menu chính depth=2) ---------- */
		/* Con trỏ chuột: mở khi hover (CSS :hover). Bàn phím: mở khi mục cha nhận
		   focus, đóng khi Escape hoặc khi tiêu điểm rời khỏi toàn bộ mục cha+con. */
		var parentItems = nav.querySelectorAll( '.menu-item-has-children' );

		parentItems.forEach( function ( item ) {
			var link = item.querySelector( ':scope > a' );
			if ( link ) {
				link.addEventListener( 'focus', function () {
					item.classList.add( 'nntm-main-nav__item--open' );
				} );
			}

			item.addEventListener( 'focusout', function () {
				// requestAnimationFrame để activeElement kịp cập nhật sau khi tiêu điểm chuyển.
				window.requestAnimationFrame( function () {
					if ( ! item.contains( document.activeElement ) ) {
						item.classList.remove( 'nntm-main-nav__item--open' );
					}
				} );
			} );

			item.addEventListener( 'keydown', function ( event ) {
				if ( 'Escape' === event.key || 'Esc' === event.key ) {
					item.classList.remove( 'nntm-main-nav__item--open' );
					if ( link ) {
						link.focus();
					}
				}
			} );
		} );
	}

	/* ---------- Menu tài khoản (chỉ hiện ở trạng thái đã đăng nhập) ---------- */
	var accountToggle = header.querySelector( '.nntm-header__account-toggle' );
	var accountPanel  = header.querySelector( '.nntm-header__account-panel' );

	if ( accountToggle && accountPanel ) {

		/**
		 * Đóng menu tài khoản.
		 *
		 * @param {boolean} restoreFocus Có đưa tiêu điểm về nút icon người hay không.
		 */
		function closeAccountMenu( restoreFocus ) {
			accountToggle.setAttribute( 'aria-expanded', 'false' );
			accountPanel.hidden = true;
			document.removeEventListener( 'click', onOutsideClick, true );
			document.removeEventListener( 'keydown', onAccountKeydown, true );

			if ( restoreFocus ) {
				accountToggle.focus();
			}
		}

		/**
		 * Mở menu tài khoản.
		 */
		function openAccountMenu() {
			accountToggle.setAttribute( 'aria-expanded', 'true' );
			accountPanel.hidden = false;
			document.addEventListener( 'click', onOutsideClick, true );
			document.addEventListener( 'keydown', onAccountKeydown, true );
		}

		/**
		 * Bấm ra ngoài nút/panel thì đóng menu tài khoản.
		 *
		 * @param {MouseEvent} event Sự kiện click (bắt ở pha capture).
		 */
		function onOutsideClick( event ) {
			if ( accountToggle.contains( event.target ) || accountPanel.contains( event.target ) ) {
				return;
			}
			closeAccountMenu( false );
		}

		/**
		 * Phím Escape đóng menu tài khoản.
		 *
		 * @param {KeyboardEvent} event Sự kiện bàn phím.
		 */
		function onAccountKeydown( event ) {
			if ( 'Escape' === event.key || 'Esc' === event.key ) {
				closeAccountMenu( true );
			}
		}

		accountToggle.addEventListener( 'click', function () {
			var isOpen = 'true' === accountToggle.getAttribute( 'aria-expanded' );
			if ( isOpen ) {
				closeAccountMenu( false );
			} else {
				openAccountMenu();
			}
		} );
	}

	/* ---------- Trạng thái C: dính trên cùng khi cuộn ---------- */
	/*
	 * Dùng IntersectionObserver thay vì lắng nghe sự kiện "scroll" (tốn hiệu
	 * năng, chạy liên tục trên main thread). Chèn một "lính canh" cao 1px
	 * ngay trước header; khi lính canh rời khỏi khung nhìn (cuộn qua khỏi
	 * đỉnh trang) tức là header đã bắt đầu dính — gắn class --stuck để
	 * header.css thêm bóng nhẹ. Class chỉ ảnh hưởng CSS, không đổi bố cục.
	 */
	if ( header.classList.contains( 'nntm-header--sticky' ) && 'IntersectionObserver' in window ) {
		var sentinel = document.createElement( 'div' );
		sentinel.className = 'nntm-header__sentinel';
		sentinel.setAttribute( 'aria-hidden', 'true' );
		sentinel.style.position = 'absolute';
		sentinel.style.top = '0';
		sentinel.style.height = '1px';
		sentinel.style.width = '1px';
		sentinel.style.pointerEvents = 'none';
		sentinel.style.visibility = 'hidden';

		if ( header.parentNode ) {
			header.parentNode.insertBefore( sentinel, header );
		}

		var stuckObserver = new IntersectionObserver(
			function ( entries ) {
				entries.forEach( function ( entry ) {
					header.classList.toggle( 'nntm-header--stuck', ! entry.isIntersecting );
				} );
			},
			{ threshold: 0 }
		);

		stuckObserver.observe( sentinel );
	}
} )();
