( function () {
	'use strict';

	function initPopupCarousel( dialog ) {
		var carousel = dialog.querySelector( '[data-fgc-popup-carousel]' );
		if ( ! carousel ) {
			return { reset: function () {} };
		}

		var slides = Array.from( carousel.querySelectorAll( '[data-fgc-popup-slide]' ) );
		var dots = Array.from( dialog.querySelectorAll( '[data-fgc-popup-dot]' ) );
		var currentNode = dialog.querySelector( '[data-fgc-popup-current]' );
		var current = 0;
		var pointerStartX = null;

		function normalize( index ) {
			if ( ! slides.length ) { return 0; }
			return ( index + slides.length ) % slides.length;
		}

		function paint( nextIndex ) {
			current = normalize( typeof nextIndex === 'number' ? nextIndex : current );
			slides.forEach( function ( slide, index ) {
				var active = index === current;
				slide.classList.toggle( 'is-active', active );
				slide.setAttribute( 'aria-hidden', active ? 'false' : 'true' );
			} );
			dots.forEach( function ( dot, index ) {
				var active = index === current;
				dot.classList.toggle( 'is-active', active );
				dot.setAttribute( 'aria-selected', active ? 'true' : 'false' );
			} );
			if ( currentNode ) { currentNode.textContent = String( current + 1 ); }
		}

		function go( direction ) {
			if ( slides.length < 2 ) { return; }
			paint( current + direction );
		}

		var prev = dialog.querySelector( '[data-fgc-popup-prev]' );
		var next = dialog.querySelector( '[data-fgc-popup-next]' );
		var viewport = dialog.querySelector( '[data-fgc-popup-viewport]' );

		if ( prev ) { prev.addEventListener( 'click', function () { go( -1 ); } ); }
		if ( next ) { next.addEventListener( 'click', function () { go( 1 ); } ); }
		dots.forEach( function ( dot ) {
			dot.addEventListener( 'click', function () {
				var index = parseInt( dot.getAttribute( 'data-fgc-popup-dot' ) || '0', 10 );
				paint( Number.isFinite( index ) ? index : 0 );
			} );
		} );

		if ( viewport ) {
			viewport.addEventListener( 'keydown', function ( event ) {
				if ( event.key === 'ArrowLeft' ) { event.preventDefault(); go( -1 ); }
				if ( event.key === 'ArrowRight' ) { event.preventDefault(); go( 1 ); }
			} );
			viewport.addEventListener( 'pointerdown', function ( event ) {
				pointerStartX = event.clientX;
			} );
			viewport.addEventListener( 'pointerup', function ( event ) {
				if ( pointerStartX === null ) { return; }
				var delta = event.clientX - pointerStartX;
				pointerStartX = null;
				if ( Math.abs( delta ) >= 45 ) { go( delta < 0 ? 1 : -1 ); }
			} );
			viewport.addEventListener( 'pointercancel', function () { pointerStartX = null; } );
		}

		paint( 0 );
		return {
			reset: function () { paint( 0 ); },
		};
	}

	function focusableElements( dialog ) {
		return Array.from( dialog.querySelectorAll( 'button:not([disabled]), [href], input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])' ) ).filter( function ( node ) {
			return ! node.hidden && node.getAttribute( 'aria-hidden' ) !== 'true';
		} );
	}

	function initCarousel( root ) {
		var slider = root.querySelector( '.nntm-feature-gallery-carousel__slider' );
		if ( ! slider ) { return; }
		var slides = Array.from( slider.querySelectorAll( '[data-fgc-slide]' ) );
		if ( ! slides.length ) { return; }

		var current = 0;
		var timer = null;
		var pointerStartX = null;
		var hovered = false;
		var focused = false;
		var dragging = false;
		var modalOpen = false;
		var lastTrigger = null;
		var activeDialog = null;
		var reducedMotion = window.matchMedia( '(prefers-reduced-motion: reduce)' );
		var popupCarousels = new Map();

		/*
		 * Khung xem tác phẩm phải phủ kín màn hình, nhưng nó được in ra bên
		 * trong <section> của khối — mà section này mang lớp .nntm-reveal, tức
		 * có transform: translateY(...) cho tới khi cuộn tới. Phần tử tổ tiên có
		 * transform sẽ trở thành khung tham chiếu của position:fixed, nên khung
		 * xem bị neo vào section thay vì vào màn hình: mở ra là nó nằm lệch hẳn
		 * xuống dưới, không che kín trang.
		 *
		 * Chuyển hẳn các khung xem ra thẳng <body> khi khởi tạo là hết cửa gặp
		 * lại chuyện này, kể cả sau này có ai thêm transform/filter vào section.
		 * Đổi lại, sự kiện đóng và phím ESC không còn nổi bọt lên root nữa nên
		 * phải gắn trực tiếp vào từng khung xem và vào document (xem bên dưới).
		 */
		var dialogs = Array.from( root.querySelectorAll( '[data-fgc-dialog]' ) );

		dialogs.forEach( function ( dialog ) {
			popupCarousels.set( dialog, initPopupCarousel( dialog ) );
			document.body.appendChild( dialog );

			dialog.addEventListener( 'click', function ( event ) {
				if ( ! event.target.closest ) { return; }
				var closer = event.target.closest( '[data-fgc-close]' );
				if ( closer && dialog.contains( closer ) ) {
					event.preventDefault();
					closeDialog( dialog );
				}
			} );
		} );

		function signedDistance( index ) {
			var total = slides.length;
			var distance = ( index - current + total ) % total;
			if ( distance > total / 2 ) { distance -= total; }
			return distance;
		}

		function paint() {
			slides.forEach( function ( slide, index ) {
				var distance = signedDistance( index );
				var visibleDistance = Math.max( -3, Math.min( 3, distance ) );
				slide.setAttribute( 'data-position', String( visibleDistance ) );
				slide.setAttribute( 'aria-hidden', distance === 0 ? 'false' : 'true' );
				if ( distance === 0 ) { slide.setAttribute( 'aria-current', 'true' ); }
				else { slide.removeAttribute( 'aria-current' ); }

				/*
				 * Ảnh là nút bấm mở khung xem tác phẩm. Chỉ ảnh ở giữa mới bấm
				 * và Tab tới được — các slide hai bên đang aria-hidden, để chúng
				 * nhận Tab thì bàn phím sẽ rơi vào phần đã ẩn với trình đọc màn hình.
				 */
				var media = slide.querySelector( '[data-fgc-media]' );
				if ( media ) { media.tabIndex = distance === 0 ? 0 : -1; }
			} );
		}

		function stop() {
			if ( timer ) { window.clearTimeout( timer ); timer = null; }
		}

		function canAutoplay() {
			return slides.length > 1 && root.dataset.autoplay === '1' && ! reducedMotion.matches && ! document.hidden && ! hovered && ! focused && ! dragging && ! modalOpen;
		}

		function start() {
			stop();
			if ( ! canAutoplay() ) { return; }
			var seconds = parseInt( root.dataset.interval || '5', 10 );
			seconds = Number.isFinite( seconds ) ? Math.max( 3, Math.min( 20, seconds ) ) : 5;
			timer = window.setTimeout( function () { go( 1 ); }, seconds * 1000 );
		}

		function go( direction ) {
			if ( slides.length < 2 ) { return; }
			current = ( current + direction + slides.length ) % slides.length;
			paint();
			start();
		}

		var prev = slider.querySelector( '[data-fgc-prev]' );
		var next = slider.querySelector( '[data-fgc-next]' );
		if ( prev ) { prev.addEventListener( 'click', function () { go( -1 ); } ); }
		if ( next ) { next.addEventListener( 'click', function () { go( 1 ); } ); }

		var track = slider.querySelector( '[data-fgc-track]' );
		if ( track ) {
			track.addEventListener( 'keydown', function ( event ) {
				if ( event.key === 'ArrowLeft' ) { event.preventDefault(); go( -1 ); }
				if ( event.key === 'ArrowRight' ) { event.preventDefault(); go( 1 ); }
			} );
		}

		slider.addEventListener( 'pointerdown', function ( event ) { pointerStartX = event.clientX; dragging = true; stop(); } );
		slider.addEventListener( 'pointerup', function ( event ) {
			if ( pointerStartX !== null ) {
				var delta = event.clientX - pointerStartX;
				pointerStartX = null;
				if ( Math.abs( delta ) >= 45 ) {
					current = ( current + ( delta < 0 ? 1 : -1 ) + slides.length ) % slides.length;
					paint();
				}
			}
			dragging = false;
			start();
		} );
		slider.addEventListener( 'pointercancel', function () { pointerStartX = null; dragging = false; start(); } );
		slider.addEventListener( 'mouseenter', function () { hovered = true; stop(); } );
		slider.addEventListener( 'mouseleave', function () { hovered = false; start(); } );
		slider.addEventListener( 'focusin', function () { focused = true; stop(); } );
		slider.addEventListener( 'focusout', function ( event ) {
			if ( ! slider.contains( event.relatedTarget ) ) { focused = false; start(); }
		} );

		function closeDialog( dialog ) {
			if ( ! dialog || dialog.hidden ) { return; }
			dialog.classList.remove( 'is-open' );
			dialog.setAttribute( 'aria-hidden', 'true' );
			window.setTimeout( function () { dialog.hidden = true; }, reducedMotion.matches ? 0 : 240 );
			modalOpen = false;
			activeDialog = null;
			document.documentElement.classList.remove( 'nntm-fgc-modal-open' );
			document.body.classList.remove( 'nntm-fgc-modal-open' );
			if ( lastTrigger && typeof lastTrigger.focus === 'function' ) { lastTrigger.focus(); }
			start();
		}

		function openDialog( id, trigger, cheDo ) {
			var dialog = document.getElementById( id );
			if ( ! dialog ) { return; }
			lastTrigger = trigger || null;
			modalOpen = true;
			activeDialog = dialog;
			stop();

			/*
			 * Mot khung, hai che do:
			 *   anh      — bam vao anh: xem anh to, luot qua ca cac anh phu cua slide
			 *   chi-tiet — bam "Xem Chi Tiet": anh ben trai, tieu de + noi dung ben phai
			 * Class quyet dinh phan nao hien; CSS lo phan con lai.
			 */
			dialog.classList.remove( 'nntm-feature-gallery-modal--che-do-anh' );
			dialog.classList.remove( 'nntm-feature-gallery-modal--che-do-chi-tiet' );
			dialog.classList.add(
				'chi-tiet' === cheDo
					? 'nntm-feature-gallery-modal--che-do-chi-tiet'
					: 'nntm-feature-gallery-modal--che-do-anh'
			);

			/* Bang anh chi co nghia o che do xem anh — dua ve tam dau moi lan mo. */
			var popup = popupCarousels.get( dialog );
			if ( popup && 'chi-tiet' !== cheDo ) { popup.reset(); }

			dialog.hidden = false;
			dialog.setAttribute( 'aria-hidden', 'false' );
			document.documentElement.classList.add( 'nntm-fgc-modal-open' );
			document.body.classList.add( 'nntm-fgc-modal-open' );
			window.requestAnimationFrame( function () {
				dialog.classList.add( 'is-open' );
				var close = dialog.querySelector( '[data-fgc-close]' );
				if ( close ) { close.focus(); }
			} );
		}

		root.addEventListener( 'click', function ( event ) {
			if ( ! event.target.closest ) { return; }
			var opener = event.target.closest( '[data-fgc-open]' );
			if ( opener && root.contains( opener ) ) {
				event.preventDefault();
				openDialog(
					opener.getAttribute( 'data-fgc-open' ),
					opener,
					opener.getAttribute( 'data-fgc-mode' ) || 'anh'
				);
			}
		} );

		/*
		 * Khung xem nay nằm ngoài root nên phím bấm trong đó không nổi bọt tới
		 * root nữa — nghe ở document, và chỉ xử lý khi chính khung xem của
		 * carousel này đang mở (activeDialog).
		 */
		document.addEventListener( 'keydown', function ( event ) {
			if ( event.key === 'Escape' && activeDialog ) {
				event.preventDefault();
				closeDialog( activeDialog );
				return;
			}
			if ( event.key === 'Tab' && activeDialog ) {
				var focusable = focusableElements( activeDialog );
				if ( ! focusable.length ) { return; }
				var first = focusable[ 0 ];
				var last = focusable[ focusable.length - 1 ];
				if ( event.shiftKey && document.activeElement === first ) {
					event.preventDefault(); last.focus();
				} else if ( ! event.shiftKey && document.activeElement === last ) {
					event.preventDefault(); first.focus();
				}
			}
		} );

		document.addEventListener( 'visibilitychange', start );
		if ( reducedMotion.addEventListener ) { reducedMotion.addEventListener( 'change', start ); }
		paint();
		start();
	}

	function boot() {
		document.querySelectorAll( '.nntm-feature-gallery-carousel' ).forEach( initCarousel );
	}
	if ( document.readyState === 'loading' ) { document.addEventListener( 'DOMContentLoaded', boot ); }
	else { boot(); }
} )();
