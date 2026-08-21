( function () {
	'use strict';

	function initCarousel( root ) {
		var slider = root.querySelector( '.nntm-feature-carousel__slider' );
		if ( ! slider ) {
			return;
		}

		var slides = Array.from( slider.querySelectorAll( '[data-fc-slide]' ) );
		if ( ! slides.length ) {
			return;
		}

		var current = 0;
		var timer = null;
		var pointerStartX = null;
		var reducedMotion = window.matchMedia( '(prefers-reduced-motion: reduce)' );

		function signedDistance( index ) {
			var total = slides.length;
			var distance = ( index - current + total ) % total;
			if ( distance > total / 2 ) {
				distance -= total;
			}
			return distance;
		}

		function paint() {
			slides.forEach( function ( slide, index ) {
				var distance = signedDistance( index );
				var visibleDistance = Math.max( -3, Math.min( 3, distance ) );

				/*
				 * ẢNH "VÒNG QUA BÌA" — mỗi lần chuyển, đúng một ảnh phải đi từ
				 * khe ngoài cùng bên này sang khe ngoài cùng bên kia (ví dụ
				 * dải 5 ảnh: khe -2 -> khe +2). Đó là bản chất của băng chạy
				 * vòng, không phải lỗi tính toán.
				 *
				 * Để nó CHUYỂN như các ảnh khác thì nó sẽ bay ngang qua cả sân
				 * khấu, cắt mặt ảnh đang ở giữa — thứ ồn nhất trong cả khối, và
				 * càng lộ khi chuyển động chậm lại (1250ms). Nên riêng ảnh này
				 * được nhấc đi tức thì (không transition) rồi HIỆN DẦN tại chỗ
				 * mới: cú biến mất ở mép ngoài gần như không ai để ý (ảnh đó
				 * nhỏ nhất, mờ nhất, mắt đang theo ảnh giữa), còn lúc trở lại
				 * thì mềm.
				 *
				 * Nhận ra bằng cách so với vị trí CŨ: chuyển bình thường chỉ
				 * dịch một khe, vòng qua bìa thì nhảy nhiều khe.
				 */
				var viTriCu = slide.getAttribute( 'data-position' );
				var vongQuaBia = null !== viTriCu && Math.abs( visibleDistance - parseInt( viTriCu, 10 ) ) > 1;

				if ( vongQuaBia ) {
					slide.classList.add( 'is-teleport' );
				}

				slide.setAttribute( 'data-position', String( visibleDistance ) );
				slide.setAttribute( 'aria-hidden', distance === 0 ? 'false' : 'true' );

				if ( vongQuaBia ) {
					/*
					 * Bỏ lớp ở khung hình SAU — lúc đó vị trí mới đã thành
					 * trạng thái nền, nên chỉ còn opacity chuyển từ 0 lên,
					 * không kèm cú trượt nào. Hai lần rAF lồng nhau vì một lần
					 * vẫn nằm trong cùng khung hình với các thay đổi vừa rồi.
					 */
					window.requestAnimationFrame( function () {
						window.requestAnimationFrame( function () {
							slide.classList.remove( 'is-teleport' );
						} );
					} );
				}
			} );
		}

		function stop() {
			if ( timer ) {
				window.clearInterval( timer );
				timer = null;
			}
		}

		function start() {
			stop();
			if ( slides.length < 2 || root.dataset.autoplay !== '1' || reducedMotion.matches ) {
				return;
			}
			var seconds = parseInt( root.dataset.interval || '6', 10 );
			seconds = Number.isFinite( seconds ) ? Math.max( 3, Math.min( 20, seconds ) ) : 6;
			timer = window.setInterval( function () { go( 1, false ); }, seconds * 1000 );
		}

		function go( direction, restart ) {
			if ( slides.length < 2 ) {
				return;
			}
			current = ( current + direction + slides.length ) % slides.length;
			paint();
			if ( restart !== false ) {
				start();
			}
		}

		var prev = slider.querySelector( '[data-fc-prev]' );
		var next = slider.querySelector( '[data-fc-next]' );
		if ( prev ) { prev.addEventListener( 'click', function () { go( -1, true ); } ); }
		if ( next ) { next.addEventListener( 'click', function () { go( 1, true ); } ); }

		var track = slider.querySelector( '[data-fc-track]' );
		if ( track ) {
			track.addEventListener( 'keydown', function ( event ) {
				if ( event.key === 'ArrowLeft' ) { event.preventDefault(); go( -1, true ); }
				if ( event.key === 'ArrowRight' ) { event.preventDefault(); go( 1, true ); }
			} );
		}

		slider.addEventListener( 'pointerdown', function ( event ) { pointerStartX = event.clientX; } );
		slider.addEventListener( 'pointerup', function ( event ) {
			if ( pointerStartX === null ) { return; }
			var delta = event.clientX - pointerStartX;
			pointerStartX = null;
			if ( Math.abs( delta ) >= 45 ) { go( delta < 0 ? 1 : -1, true ); }
		} );
		slider.addEventListener( 'pointercancel', function () { pointerStartX = null; } );
		slider.addEventListener( 'mouseenter', stop );
		slider.addEventListener( 'mouseleave', start );
		slider.addEventListener( 'focusin', stop );
		slider.addEventListener( 'focusout', function ( event ) {
			if ( ! slider.contains( event.relatedTarget ) ) { start(); }
		} );

		paint();

		/*
		 * Bật transition SAU khi lần xếp chỗ đầu tiên đã được trình duyệt
		 * chốt. requestAnimationFrame lồng hai lần là cách chắc chắn: lần
		 * đầu chạy trước khi khung hình hiện tại được vẽ, lần thứ hai chắc
		 * chắn nằm ở khung hình SAU khi các data-position vừa đặt đã thành
		 * trạng thái nền. Bật sớm hơn thì lúc tải trang cả dải ảnh bay từ
		 * giữa ra chỗ của mình — xem chú thích ".is-ready" trong style.css.
		 */
		window.requestAnimationFrame( function () {
			window.requestAnimationFrame( function () {
				root.classList.add( 'is-ready' );
			} );
		} );

		start();
	}

	document.querySelectorAll( '.nntm-feature-carousel' ).forEach( initCarousel );
} )();
