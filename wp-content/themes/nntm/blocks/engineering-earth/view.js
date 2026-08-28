 
( function () {
	'use strict';

	function nntmPrefersReducedMotion() {
		return !! ( window.matchMedia && window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches );
	}

	function nntmYoutubeBgEmbedUrl( videoId ) {
		return 'https://www.youtube.com/embed/' + encodeURIComponent( videoId ) +
			'?autoplay=1&mute=1&loop=1&controls=0&disablekb=1&fs=0&iv_load_policy=3&modestbranding=1&playsinline=1&rel=0&showinfo=0&playlist=' +
			encodeURIComponent( videoId );
	}

	function nntmInsertEmbed( slot ) {
		var videoId = slot.getAttribute( 'data-video-id' );
		var embedHost = slot.querySelector( '.nntm-engineering-earth__video-embed' );

		if ( ! embedHost ) {
			return;
		}

		var mediaVideo = embedHost.querySelector( '[data-nntm-ee-media-video]' );
		if ( mediaVideo ) {
			if ( mediaVideo.getAttribute( 'data-nntm-ee-initialized' ) === '1' ) {
				return;
			}

			mediaVideo.setAttribute( 'data-nntm-ee-initialized', '1' );
			mediaVideo.addEventListener( 'playing', function () {
				slot.classList.add( 'is-loaded' );
			}, { once: true } );

			var playPromise = mediaVideo.play();
			if ( playPromise && 'function' === typeof playPromise.catch ) {
				playPromise.catch( function () {} );
			}
			return;
		}

		if ( ! videoId || embedHost.children.length ) {
			return;
		}

		var iframe = document.createElement( 'iframe' );
		iframe.src = nntmYoutubeBgEmbedUrl( videoId );
		iframe.setAttribute( 'title', slot.getAttribute( 'aria-label' ) || '' );
		iframe.setAttribute( 'frameborder', '0' );
		iframe.setAttribute( 'allow', 'autoplay; encrypted-media' );
		iframe.setAttribute( 'tabindex', '-1' );
		iframe.addEventListener( 'load', function () {
			window.setTimeout( function () {
				slot.classList.add( 'is-loaded' );
			}, 900 );
		} );
		embedHost.appendChild( iframe );
	}

	function nntmInitAllVideoSlots() {
		if ( nntmPrefersReducedMotion() ) {
			return; 
		}

		var slots = document.querySelectorAll( '.nntm-engineering-earth__video-slot' );

		for ( var i = 0; i < slots.length; i++ ) {
			nntmInsertEmbed( slots[ i ] );
		}
	}

	/*
	 * KHUNG VIDEO TROI NOI
	 *
	 * Cuon qua khoi dai phim thi khung video chinh tach ra, thu nho va noi o goc
	 * phai duoi man hinh; cuon nguoc len thi tra ve dung cho cu.
	 *
	 * Khong nhan ban (clone) node video: chi doi position cua chinh no, nho vay
	 * iframe/video khong bi tai lai va khong mat doan dang xem. Doi lai, khi no
	 * roi khoi dong thi phai chen mot o trong dung kich thuoc vao thay, neu
	 * khong bo cuc dai phim se sup xuong.
	 */
	function nntmTaoOTrong( slot ) {
		var khung = slot.getBoundingClientRect();
		var css = window.getComputedStyle( slot );
		var o = document.createElement( 'div' );

		o.className = 'nntm-engineering-earth__video-cho';
		o.setAttribute( 'aria-hidden', 'true' );

		o.style.width = khung.width + 'px';
		o.style.height = khung.height + 'px';
		o.style.maxWidth = css.maxWidth;

		/* Dai phim la flex o trang chu, la grid o cac trang con — do ca hai. */
		o.style.flex = '0 0 ' + khung.width + 'px';
		o.style.gridColumn = css.gridColumn;
		o.style.gridRow = css.gridRow;
		o.style.alignSelf = css.alignSelf;

		return o;
	}

	function nntmInitVideoNoi( slot ) {
		if ( slot.dataset.nntmEeNoiSan === '1' ) {
			return;
		}

		/*
		 * Moc do phai la mot phan tu LUON nam trong dong chay. Ban than khung
		 * video thi khong dung duoc: khi da noi len no la position: fixed, luc
		 * nao cung nam trong man hinh nen se tu tat ngay lap tuc.
		 */
		var moc = slot.closest( '.nntm-engineering-earth__band' );
		if ( ! moc ) {
			return;
		}

		slot.dataset.nntmEeNoiSan = '1';

		var dongBtn = slot.querySelector( '[data-nntm-ee-dong]' );
		var oTrong = null;
		var daTat = false;

		function bat() {
			if ( daTat || oTrong ) {
				return;
			}

			oTrong = nntmTaoOTrong( slot );
			slot.parentNode.insertBefore( oTrong, slot );
			slot.classList.add( 'la-noi' );
		}

		function tat() {
			if ( ! oTrong ) {
				return;
			}

			slot.classList.remove( 'la-noi' );
			oTrong.parentNode.removeChild( oTrong );
			oTrong = null;
		}

		if ( dongBtn ) {
			dongBtn.addEventListener( 'click', function ( su ) {
				/*
				 * Chan su kien lan ra ngoai: khung video co mot lop <a> phu kin
				 * va mot trinh bat click o cap document (video-lightbox.js) mo
				 * trinh phat khi bam vao bat cu dau trong khung.
				 */
				su.preventDefault();
				su.stopPropagation();

				daTat = true;
				tat();
			} );
		}

		function xet() {
			var khung = moc.getBoundingClientRect();

			/* Dai phim da troi len tren dinh man hinh -> cho video noi len. */
			if ( khung.bottom <= 0 ) {
				bat();
				return;
			}

			/* Quay lai vung dai phim -> tra khung ve cho, cho phep noi lai. */
			daTat = false;
			tat();
		}

		/*
		 * Han bot so lan do: moi lan cuon chi do lai nhieu nhat 1 lan / 80ms.
		 * Khong dung requestAnimationFrame de con chay dung ca khi tab dang
		 * khong ve khung hinh.
		 */
		var lanCuoi = 0;
		var hen = null;

		function theoCuon() {
			var gio = ( window.performance && window.performance.now )
				? window.performance.now()
				: +new Date();

			if ( gio - lanCuoi >= 80 ) {
				lanCuoi = gio;
				xet();
				return;
			}

			if ( hen ) {
				return;
			}

			hen = window.setTimeout( function () {
				hen = null;
				lanCuoi = ( window.performance && window.performance.now )
					? window.performance.now()
					: +new Date();
				xet();
			}, 80 );
		}

		window.addEventListener( 'scroll', theoCuon, { passive: true } );
		window.addEventListener( 'resize', theoCuon, { passive: true } );

		xet();
	}

	function nntmInitTatCaVideoNoi() {
		var slots = document.querySelectorAll( '.nntm-engineering-earth__video-slot--main' );

		for ( var i = 0; i < slots.length; i++ ) {
			nntmInitVideoNoi( slots[ i ] );
		}
	}

	function nntmKhoiDong() {
		nntmInitAllVideoSlots();
		nntmInitTatCaVideoNoi();
	}

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', nntmKhoiDong );
	} else {
		nntmKhoiDong();
	}
} )();
