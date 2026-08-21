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

	/* ---------- Menu di động (hamburger + panel trượt) ---------- */
	/*
	 * SUA 20/08/2026: bẫy tiêu điểm và nút đóng chuyển từ <nav> sang
	 * .nntm-header__panel. Panel giờ bọc CẢ menu chính lẫn khu công cụ (tìm
	 * kiếm / đăng nhập / ngôn ngữ) — xem chú thích trong header.php. Bẫy
	 * theo <nav> như cũ thì ô tìm kiếm và nút đăng nhập nằm ngay trong
	 * drawer lại KHÔNG bấm tới được bằng bàn phím.
	 *
	 * Điểm ngắt 1151px phải KHỚP với @media trong header.css — khai một
	 * chỗ ở đây rồi dùng matchMedia, không rải số 1151 ra nhiều nơi.
	 */
	var MENU_MQ = '(max-width: 1151px)';

	var toggle = header.querySelector( '.nntm-header__menu-toggle' );
	var panel  = header.querySelector( '.nntm-header__panel' );

	if ( toggle && panel ) {

		/**
		 * Mở menu di động: hiện panel, khoá cuộn trang, đưa tiêu điểm vào mục đầu tiên.
		 */
		function openMenu() {
			header.classList.add( 'nntm-header--menu-open' );
			toggle.setAttribute( 'aria-expanded', 'true' );
			/* Khoá cuộn trang phía sau — CSS lo phần overflow, xem header.css. */
			document.documentElement.classList.add( 'nntm-menu-dang-mo' );
			document.addEventListener( 'keydown', onMenuKeydown, true );
			document.addEventListener( 'focus', trapFocus, true );

			var firstFocusable = panel.querySelector( FOCUSABLE_SELECTOR );
			if ( firstFocusable ) {
				firstFocusable.focus();
			}
		}

		/**
		 * Đóng menu di động: ẩn panel, mở lại cuộn trang, trả tiêu điểm về nút hamburger.
		 *
		 * @param {boolean} restoreFocus Có đưa tiêu điểm về nút hamburger hay không.
		 */
		function closeMenu( restoreFocus ) {
			header.classList.remove( 'nntm-header--menu-open' );
			toggle.setAttribute( 'aria-expanded', 'false' );
			document.documentElement.classList.remove( 'nntm-menu-dang-mo' );
			document.removeEventListener( 'keydown', onMenuKeydown, true );
			document.removeEventListener( 'focus', trapFocus, true );

			if ( restoreFocus ) {
				toggle.focus();
			}
		}

		/**
		 * Bẫy tiêu điểm bên trong panel khi menu di động đang mở — tiêu điểm rơi
		 * ra ngoài panel (kể cả nút hamburger) sẽ bị kéo về mục đầu tiên.
		 *
		 * @param {FocusEvent} event Sự kiện focus (bắt ở pha capture).
		 */
		function trapFocus( event ) {
			if ( panel.contains( event.target ) ) {
				return;
			}

			var focusableItems = panel.querySelectorAll( FOCUSABLE_SELECTOR );
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

		/*
		 * Bấm lớp phủ hoặc nút "×" trong panel thì đóng. Cả hai đều mang
		 * data-nntm-menu-close nên chỉ cần một vòng gắn sự kiện.
		 */
		header.querySelectorAll( '[data-nntm-menu-close]' ).forEach( function ( el ) {
			el.addEventListener( 'click', function () {
				closeMenu( true );
			} );
		} );

		/*
		 * Bấm một mục menu là điều hướng sang trang khác, nhưng nếu là liên
		 * kết neo (#...) trên cùng trang thì trang không tải lại — drawer sẽ
		 * nằm mở che mất chỗ vừa nhảy tới. Đóng luôn cho chắc.
		 */
		panel.addEventListener( 'click', function ( event ) {
			var link = event.target.closest ? event.target.closest( 'a[href]' ) : null;
			if ( link && ! link.closest( '.nntm-header__account-panel' ) ) {
				closeMenu( false );
			}
		} );

		/*
		 * Kéo cửa sổ rộng trở lại quá điểm ngắt: panel quay về là menu ngang
		 * trong dòng chảy, nhưng lớp --menu-open + khoá cuộn + aria-expanded
		 * vẫn còn treo (trang hoá ra không cuộn được nữa). Dọn khi thoát dải.
		 */
		if ( window.matchMedia ) {
			var menuQuery = window.matchMedia( MENU_MQ );
			var onQueryChange = function ( event ) {
				if ( ! event.matches ) {
					closeMenu( false );
				}
			};

			if ( menuQuery.addEventListener ) {
				menuQuery.addEventListener( 'change', onQueryChange );
			} else if ( menuQuery.addListener ) {
				menuQuery.addListener( onQueryChange );
			}
		}

		// Enter đã kích hoạt click mặc định trên <button>, không cần xử lý riêng.
		// Chỉ còn khoảng trắng (Space) cũng là hành vi mặc định của trình duyệt.

		/* ---------- Submenu cấp 2 trên desktop (menu chính depth=2) ---------- */
		/* Con trỏ chuột: mở khi hover (CSS :hover). Bàn phím: mở khi mục cha nhận
		   focus, đóng khi Escape hoặc khi tiêu điểm rời khỏi toàn bộ mục cha+con. */
		var parentItems = panel.querySelectorAll( '.menu-item-has-children' );

		parentItems.forEach( function ( item ) {
			var link = item.querySelector( ':scope > a' );
			if ( link ) {
				link.addEventListener( 'focus', function () {
					item.classList.add( 'nntm-main-nav__item--open' );
				} );

				/*
				 * Trong drawer, chạm vào mục cha phải MỞ submenu chứ không
				 * điều hướng ngay — trên cảm ứng không có `hover`, còn mở bằng
				 * `focus` thì chạm cũng đã kèm điều hướng nên submenu không
				 * bao giờ xem được. Lần chạm thứ hai mới đi tới trang cha.
				 * (Menu hiện tại đang phẳng, không có mục con — luật này để
				 * ban quản trị thêm mục con về sau vẫn dùng được ngay.)
				 */
				link.addEventListener( 'click', function ( event ) {
					if ( ! window.matchMedia || ! window.matchMedia( MENU_MQ ).matches ) {
						return;
					}
					if ( item.classList.contains( 'nntm-main-nav__item--open' ) ) {
						return;
					}
					event.preventDefault();
					event.stopPropagation();
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
