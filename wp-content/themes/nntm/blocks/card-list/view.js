 
( function () {
	'use strict';

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


		function scrollStep() {
			var firstItem = track.querySelector( '.nntm-card-list__track-item' );

			var itemWidth = firstItem ? firstItem.offsetWidth : track.clientWidth;
			var trackStyles = window.getComputedStyle( track );
			var gap = parseFloat( trackStyles.columnGap || trackStyles.gap || '0' ) || 0;

			return itemWidth + gap;
		}


		var hasOverflow = false;

		function updateButtonsState() {
			var maxScroll = track.scrollWidth - track.clientWidth;
			hasOverflow = maxScroll > 1;


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



		/*
		 * Moc thoi gian cua lan cuon do CHINH carousel goi.
		 *
		 * Truoc day cho nay la mot co boolean, va no chi duoc dat lai false BEN
		 * TRONG bo xu ly su kien 'scroll'. Neu mot lan tu cuon khong sinh ra su
		 * kien 'scroll' — vi du bang da o cuoi nen scrollBy khong doi gi, hoac
		 * trinh duyet gop su kien — thi co ket 'true' vinh vien, va tu do MOI cu
		 * cuon tay cua nguoi dung deu bi hieu nham la do may tu cuon, nen autoplay
		 * khong bao gio chiu dung lai khi nguoi dung gianh quyen.
		 *
		 * Moc thoi gian thi tu lanh: qua cua so ben duoi la coi nhu khong phai minh.
		 */
		var lucTuCuon = 0;

		/* Cuon muot keo dai khoang 300-500ms; lay 1200ms cho du hao. */
		var CUA_SO_TU_CUON = 1200;

		function doMinhTuCuon() {
			return ( Date.now() - lucTuCuon ) < CUA_SO_TU_CUON;
		}

		function scrollByDirection( direction ) {
			lucTuCuon = Date.now();
			track.scrollBy( {
				left: direction * scrollStep(),
				behavior: prefersReducedMotion() ? 'auto' : 'smooth',
			} );
		}

		function scrollToStart() {
			lucTuCuon = Date.now();
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



		track.addEventListener( 'keydown', function ( event ) {
			if ( 'ArrowRight' === event.key ) {
				event.preventDefault();
				scrollByDirection( 1 );
			} else if ( 'ArrowLeft' === event.key ) {
				event.preventDefault();
				scrollByDirection( -1 );
			}
		} );



		/*
		 * Moc thoi gian cua thao tac NGUOI DUNG that su tren bang cuon.
		 *
		 * Truoc day chi can mot su kien "scroll" khong phai do carousel tu goi la
		 * userScrolledManually bat len — va co do TAT AUTOPLAY VINH VIEN, khong co
		 * duong quay lai. Nhung tren trinh duyet that co day su kien "scroll" chang
		 * lien quan gi toi nguoi dung:
		 *   - Chrome khoi phuc vi tri cuon khi tai lai trang hoac bam Back
		 *   - anh trong bang tai xong lam do lai bo cuc
		 *   - man hinh cho (preloader) nha ra, ca trang reflow
		 *   - hieu ung hien dan nntm-reveal chay vao
		 * Chi mot trong nhung thu do xay ra la bang khong bao gio tu chay nua.
		 *
		 * Nay phai hoi du HAI dieu kien moi coi la nguoi dung gianh quyen: cu cuon
		 * khong phai do minh goi, VA co thao tac that tren bang trong 1 giay truoc do.
		 */
		var lucNguoiDungCham = 0;

		[ 'wheel', 'touchstart', 'pointerdown', 'keydown' ].forEach( function ( ten ) {
			track.addEventListener(
				ten,
				function () {
					lucNguoiDungCham = Date.now();
				},
				{ passive: true }
			);
		} );

		var scrollUpdateTimer = null;
		var coThaoTacNguoiDung = false;

		track.addEventListener(
			'scroll',
			function () {
				/*
				 * Quyet dinh "co phai nguoi dung khong" NGAY luc su kien no, khong doi
				 * debounce. Trinh duyet bop setTimeout xuong toi thieu 1000ms o tab nen,
				 * du de mot thao tac that cua nguoi dung roi ra ngoai cua so thoi gian
				 * neu do o trong callback — luc do carousel se khong chiu dung lai.
				 */
				if ( ! doMinhTuCuon() && ( Date.now() - lucNguoiDungCham ) < 500 ) {
					coThaoTacNguoiDung = true;
				}

				if ( scrollUpdateTimer ) {
					window.clearTimeout( scrollUpdateTimer );
				}
				scrollUpdateTimer = window.setTimeout( function () {
					updateButtonsState();

					if ( coThaoTacNguoiDung ) {
						userScrolledManually = true;
						stopAutoplayTimer();
					}
				}, 50 );
			},
			{ passive: true }
		);

		window.addEventListener( 'resize', updateButtonsState );

		/*
		 * Do lai be ngang khi bang thay doi kich thuoc.
		 *
		 * hasOverflow duoc tinh mot lan luc khoi tao. Neu luc do anh trong the chua
		 * tai xong thi scrollWidth con nho, hasOverflow = false — nut mui ten bi an
		 * va autoplay khong du dieu kien chay. Truoc day chi co su kien "resize" cua
		 * cua so moi tinh lai, tuc la phai doi nguoi dung keo cua so.
		 */
		if ( window.ResizeObserver ) {
			new window.ResizeObserver( updateButtonsState ).observe( track );
		} else {
			window.addEventListener( 'load', updateButtonsState );
		}



		var autoplayAttrEnabled = 'true' === root.getAttribute( 'data-autoplay' );
		var autoplayIntervalMs = ( function () {
			var seconds = parseFloat( root.getAttribute( 'data-autoplay-interval' ) );
			if ( ! seconds || isNaN( seconds ) ) {
				seconds = 6;
			}

			seconds = Math.max( 2, Math.min( 20, seconds ) );
			return seconds * 1000;
		} )();

		var userScrolledManually = false; 
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



		function nntmSyncAutoplay() {
			if ( autoplayShouldRunNow() ) {
				startAutoplayTimer();
			} else {
				stopAutoplayTimer();
			}
		}

		root.addEventListener( 'mouseenter', function () {
			isHovered = true;
			nntmSyncAutoplay();
		} );
		root.addEventListener( 'mouseleave', function () {
			isHovered = false;
			nntmSyncAutoplay();
		} );


		root.addEventListener( 'focusin', function () {
			isFocusedWithin = true;
			nntmSyncAutoplay();
		} );
		root.addEventListener( 'focusout', function () {
			isFocusedWithin = false;
			nntmSyncAutoplay();
		} );


		document.addEventListener( 'visibilitychange', nntmSyncAutoplay );

		updateButtonsState();
	}

	function nntmInitAllCardListCarousels( root ) {
		var carousels = ( root || document ).querySelectorAll( '.nntm-card-list__carousel' );

		for ( var i = 0; i < carousels.length; i++ ) {
			nntmInitCardListCarousel( carousels[ i ] );
		}
	}

	var NNTM_YT_HOVER_DELAY_MS = 350;

	function nntmPrefersReducedMotion() {
		return !! ( window.matchMedia && window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches );
	}

	function nntmYoutubeEmbedUrl( videoId ) {
		return 'https://www.youtube.com/embed/' + encodeURIComponent( videoId ) +
			'?autoplay=1&mute=1&controls=0&modestbranding=1&playsinline=1&rel=0';
	}

	function nntmInitYoutubeItem( item ) {
		var videoId = item.getAttribute( 'data-video-id' );
		var frame   = item.querySelector( '.nntm-card-list__yt-frame' );
		var thumb   = item.querySelector( '.nntm-card-list__yt-thumb' );
		var hoverTimerId = null;



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

		var embedGeneration = 0;

		function removeEmbed() {
			clearHoverTimer();
			embedGeneration++;
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
				return; 
			}

			var iframe = document.createElement( 'iframe' );
			var generation = ++embedGeneration;

			iframe.src = nntmYoutubeEmbedUrl( videoId );
			iframe.setAttribute( 'title', item.getAttribute( 'aria-label' ) || '' );
			iframe.setAttribute( 'frameborder', '0' );
			iframe.setAttribute( 'allow', 'autoplay; encrypted-media' );
			iframe.setAttribute( 'tabindex', '-1' );
			iframe.setAttribute( 'referrerpolicy', 'strict-origin-when-cross-origin' );



			iframe.addEventListener( 'load', function () {
				if ( generation !== embedGeneration || ! frame.contains( iframe ) ) {
					return;
				}
				item.classList.add( 'is-playing' );
			}, { once: true } );

			frame.appendChild( iframe );
		}

		function scheduleEmbed() {
			if ( nntmPrefersReducedMotion() ) {
				return;
			}
			clearHoverTimer();
			hoverTimerId = window.setTimeout( insertEmbed, NNTM_YT_HOVER_DELAY_MS );
		}


		item.addEventListener( 'mouseenter', scheduleEmbed );
		item.addEventListener( 'mouseleave', removeEmbed );


		item.addEventListener( 'focus', scheduleEmbed );
		item.addEventListener( 'blur', removeEmbed );
	}

	function nntmInitAllYoutubeMarquees( root ) {
		var items = ( root || document ).querySelectorAll( '.nntm-card-list__yt-item' );

		for ( var i = 0; i < items.length; i++ ) {
			nntmInitYoutubeItem( items[ i ] );
		}
	}

	function nntmStarRandom( min, max ) {
		return Math.random() * ( max - min ) + min;
	}

	function nntmInitCardListStarField( field ) {
		if ( 'true' === field.getAttribute( 'data-nntm-stars-ready' ) ) {
			return;
		}

		field.setAttribute( 'data-nntm-stars-ready', 'true' );

		var isMobile = window.matchMedia && window.matchMedia( '(max-width: 767px)' ).matches;
		var starCount = isMobile ? 48 : 84;
		var fragment = document.createDocumentFragment();

		for ( var i = 0; i < starCount; i++ ) {
			var star = document.createElement( 'span' );
			var isSparkle = Math.random() > 0.9;
			var size = isSparkle ? nntmStarRandom( 3.5, 5.5 ) : nntmStarRandom( 1.6, 3.8 );

			star.className = 'nntm-card-list__star' + ( isSparkle ? ' nntm-card-list__star--sparkle' : '' );
			star.style.setProperty( '--nntm-star-size', size.toFixed( 2 ) + 'px' );
			star.style.setProperty( '--nntm-star-left', nntmStarRandom( 0, 100 ).toFixed( 2 ) + '%' );
			star.style.setProperty( '--nntm-star-top', nntmStarRandom( 0, 100 ).toFixed( 2 ) + '%' );
			star.style.setProperty( '--nntm-star-duration', nntmStarRandom( 2.2, 6 ).toFixed( 2 ) + 's' );
			star.style.setProperty( '--nntm-star-delay', nntmStarRandom( -5, 0 ).toFixed( 2 ) + 's' );
			fragment.appendChild( star );
		}

		field.appendChild( fragment );
	}

	function nntmInitAllCardListStarFields( root ) {
		var scope = root || document;
		var fields = [];

		if ( scope.matches && scope.matches( '.nntm-card-list__star-field' ) ) {
			fields.push( scope );
		}

		var descendants = scope.querySelectorAll( '.nntm-card-list__star-field' );
		for ( var i = 0; i < descendants.length; i++ ) {
			fields.push( descendants[ i ] );
		}

		for ( var j = 0; j < fields.length; j++ ) {
			nntmInitCardListStarField( fields[ j ] );
		}
	}

	function nntmInitCardListView( root ) {
		nntmInitAllCardListCarousels( root );
		nntmInitAllYoutubeMarquees( root );
		nntmInitAllCardListStarFields( root );
	}

	document.addEventListener( 'nntm-card-list-refresh', function ( event ) {
		nntmInitCardListView( ( event.detail && event.detail.root ) || document );
	} );

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', function () {
			nntmInitCardListView();
		} );
	} else {
		nntmInitCardListView();
	}
} )();
