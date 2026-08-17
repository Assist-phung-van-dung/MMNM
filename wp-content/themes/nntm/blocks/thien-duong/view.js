/**
 * View script cho block nntm/thien-duong — trình phát nhạc thiền, JavaScript
 * thuần, không thư viện ngoài, không bước build. Khai qua
 * "viewScript": "file:./view.js" trong block.json, chỉ tải khi trang có
 * khối này (và chỉ thực sự chạy nếu người dùng đã đăng nhập, vì render.php
 * chỉ in ra .nntm-thien-duong__player-inner khi is_user_logged_in()).
 *
 * KHÔNG TỰ PHÁT KHI TẢI TRANG: script này không gọi audio.play() ở bước
 * khởi tạo — chỉ phát khi người dùng tự bấm (chọn bài / nút phát), đúng
 * yêu cầu và đúng giới hạn autoplay của trình duyệt.
 *
 * Realtime Soketi được tách sang plugin NNTM Zen Track Manager. File này
 * chỉ phát custom event khi đổi bài / play / pause để plugin presence bám
 * đúng trạng thái audio mà không trộn transport WebSocket vào player.
 */
( function () {
	'use strict';

	/**
	 * @param {number} seconds Số giây (có thể là NaN/Infinity trước khi audio nạp xong metadata).
	 * @return {string} Định dạng "phút:giây", ví dụ "3:05".
	 */
	function formatTime( seconds ) {
		if ( ! isFinite( seconds ) || seconds < 0 ) {
			return '0:00';
		}

		var totalSeconds = Math.floor( seconds );
		var minutes = Math.floor( totalSeconds / 60 );
		var remainSeconds = totalSeconds % 60;

		return minutes + ':' + ( remainSeconds < 10 ? '0' : '' ) + remainSeconds;
	}

	function prefersReducedMotion() {
		return !! ( window.matchMedia && window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches );
	}

	/**
	 * Khởi tạo một khối trình phát Thiền Đường.
	 *
	 * @param {Element} root Phần tử ".nntm-thien-duong__player-inner".
	 */
	function nntmInitThienDuongPlayer( root ) {
		var audio = root.querySelector( '.nntm-thien-duong__audio' );
		var playBtn = root.querySelector( '.nntm-thien-duong__btn--play' );
		var prevBtn = root.querySelector( '.nntm-thien-duong__btn--prev' );
		var nextBtn = root.querySelector( '.nntm-thien-duong__btn--next' );
		var progressRange = root.querySelector( '.nntm-thien-duong__range--progress' );
		var volumeRange = root.querySelector( '.nntm-thien-duong__range--volume' );
		var timeCurrentEl = root.querySelector( '.nntm-thien-duong__time-current' );
		var timeDurationEl = root.querySelector( '.nntm-thien-duong__time-duration' );
		var nowTitleEl = root.querySelector( '.nntm-thien-duong__now-title' );
		var playbackErrorEl = root.querySelector( '.nntm-thien-duong__playback-error' );
		var avatarEl = root.querySelector( '.nntm-thien-duong__spotify-avatar' );
		var muteBtn = root.querySelector( '.nntm-thien-duong__mute' );
		var settingsToggle = root.querySelector( '.nntm-thien-duong__settings-toggle' );
		var speedMenu = root.querySelector( '.nntm-thien-duong__speed-menu' );
		var speedOptions = root.querySelectorAll( '.nntm-thien-duong__speed-option' );
		var trackButtons = root.querySelectorAll( '.nntm-thien-duong__track' );

		if ( ! audio || ! playBtn || 0 === trackButtons.length ) {
			return;
		}

		var currentIndex = -1; // chua chon bai nao.
		var isScrubbing = false; // dang keo thanh tien do bang tay, tam ngung dong bo tu timeupdate.
		var listenDelayMs = 5000;
		var listenTimer = null;
		var listenSessionToken = 0;
		var listenSessionId = '';
		var listenRecorded = false;
		var listenRequestInFlight = false;

		// Am luong khoi tao theo gia tri co san cua thanh truot (mac dinh 80%).
		var lastVolume = 0.8;
		if ( volumeRange ) {
			audio.volume = ( parseFloat( volumeRange.value ) || 80 ) / 100;
			lastVolume = audio.volume;
		}

		function updateVolumeUI() {
			var silent = audio.muted || audio.volume <= 0.001;
			if ( muteBtn ) {
				muteBtn.classList.toggle( 'is-muted', silent );
				muteBtn.setAttribute( 'aria-pressed', silent ? 'true' : 'false' );
				muteBtn.setAttribute( 'aria-label', silent ? 'Bật âm' : 'Tắt âm' );
			}
			if ( volumeRange ) {
				volumeRange.style.setProperty( '--nntm-volume', String( silent ? 0 : audio.volume * 100 ) + '%' );
			}
		}
		updateVolumeUI();

		function showPlaybackError( message ) {
			if ( ! playbackErrorEl ) {
				return;
			}
			playbackErrorEl.textContent = message || '';
			playbackErrorEl.hidden = ! message;
		}

		function trackLabel( button ) {
			return button.getAttribute( 'data-nntm-track-title' ) || '';
		}

		function updateAvatar( button ) {
			if ( ! avatarEl ) {
				return;
			}
			var imageUrl = button.getAttribute( 'data-nntm-track-image' );
			avatarEl.textContent = '';
			if ( imageUrl ) {
				var image = document.createElement( 'img' );
				image.src = imageUrl;
				image.alt = '';
				avatarEl.appendChild( image );
			} else {
				avatarEl.textContent = 'N';
			}
		}

		function clearListenTimer() {
			if ( listenTimer ) {
				window.clearTimeout( listenTimer );
				listenTimer = null;
			}
		}

		function createListenSessionId() {
			if ( window.crypto && 'function' === typeof window.crypto.randomUUID ) {
				return window.crypto.randomUUID().replace( /-/g, '' );
			}
			return String( Date.now() ) + '_' + Math.random().toString( 36 ).slice( 2 ) + Math.random().toString( 36 ).slice( 2 );
		}

		function resetListenSession() {
			clearListenTimer();
			listenSessionToken++;
			listenSessionId = createListenSessionId();
			listenRecorded = false;
			listenRequestInFlight = false;
		}

		function recordListen( button, token ) {
			var trackId = parseInt( button.getAttribute( 'data-nntm-track-id' ), 10 ) || 0;
			var nonce = root.getAttribute( 'data-listen-nonce' ) || '';
			var ajaxUrl = root.getAttribute( 'data-ajax-url' ) || '';
			if ( token !== listenSessionToken || listenRecorded || listenRequestInFlight || ! trackId || ! nonce || ! ajaxUrl ) {
				return;
			}

			listenRequestInFlight = true;
			var body = new URLSearchParams( { action: 'nntm_track_listen', nonce: nonce, track_id: String( trackId ), listen_session: listenSessionId } );
			window.fetch( ajaxUrl, {
				method: 'POST',
				credentials: 'same-origin',
				headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
				body: body.toString(),
			} ).then( function ( response ) { return response.json(); } ).then( function ( result ) {
				if ( token !== listenSessionToken ) {
					return;
				}
				if ( result && result.success && result.data ) {
					listenRecorded = true;
					var countEl = button.querySelector( '.nntm-thien-duong__track-listen-count' );
					if ( countEl ) { countEl.textContent = String( result.data.count ); }
				}
			} ).catch( function () {
				// Giữ listenRecorded=false để có thể thử lại nếu người dùng resume.
			} ).then( function () {
				if ( token === listenSessionToken ) {
					listenRequestInFlight = false;
				}
			} );
		}

		function scheduleListenCount() {
			if ( currentIndex < 0 || listenRecorded || listenRequestInFlight || audio.paused || audio.ended ) {
				return;
			}
			clearListenTimer();
			var token = listenSessionToken;
			listenTimer = window.setTimeout( function () {
				listenTimer = null;
				if ( token !== listenSessionToken || audio.paused || audio.ended || currentIndex < 0 ) {
					return;
				}
				recordListen( trackButtons[ currentIndex ], token );
			}, listenDelayMs );
		}

		function currentTrackId() {
			if ( currentIndex < 0 || ! trackButtons[ currentIndex ] ) {
				return 0;
			}
			return parseInt( trackButtons[ currentIndex ].getAttribute( 'data-nntm-track-id' ), 10 ) || 0;
		}

		function dispatchTrackChange() {
			var trackId = currentTrackId();
			root.setAttribute( 'data-current-track-id', String( trackId || '' ) );
			root.dispatchEvent( new CustomEvent( 'nntm:thien-duong-track-change', {
				detail: { trackId: trackId, playing: ! audio.paused && ! audio.ended },
			} ) );
		}

		function dispatchPlaybackState( playing, reason ) {
			root.dispatchEvent( new CustomEvent( 'nntm:thien-duong-playback-state', {
				detail: { trackId: currentTrackId(), playing: !! playing, reason: reason || '' },
			} ) );
		}

		/**
		 * Cập nhật dấu hiệu "đang phát" trên danh sách bài — KHÔNG chỉ dùng
		 * màu (yêu cầu bắt buộc): thêm class .is-playing cho CSS lẫn đổi hẳn
		 * chữ trong .nntm-thien-duong__track-status để người mù màu / dùng
		 * trình đọc màn hình vẫn nhận biết được.
		 */
		function updateTrackListUI() {
			for ( var i = 0; i < trackButtons.length; i++ ) {
				var button = trackButtons[ i ];
				var statusEl = button.querySelector( '.nntm-thien-duong__track-status' );
				var isActive = i === currentIndex;

				button.classList.toggle( 'is-playing', isActive && ! audio.paused );
				button.classList.toggle( 'is-current', isActive );

				if ( statusEl ) {
					statusEl.textContent = isActive && ! audio.paused ? window.nntmThienDuongI18n.dangPhat : '';
				}
			}
		}

		function updatePlayButtonUI() {
			var isPlaying = ! audio.paused && ! audio.ended;

			playBtn.setAttribute( 'aria-pressed', isPlaying ? 'true' : 'false' );
			playBtn.setAttribute( 'aria-label', isPlaying ? window.nntmThienDuongI18n.tamDung : window.nntmThienDuongI18n.phat );

			var icon = playBtn.querySelector( '.nntm-thien-duong__btn-icon' );
			if ( icon ) {
				// U+23F8 (tam dung) / U+25B6 (phat) — ky hieu, khong phai mau, de phan biet trang thai.
				icon.textContent = isPlaying ? '⏸' : '▶';
			}
		}

		/**
		 * Nạp bài theo vị trí trong danh sách. KHÔNG tự play() — chỉ nạp
		 * nguồn + cập nhật tiêu đề. Nơi gọi hàm này quyết định có play()
		 * tiếp theo hay không (luôn xuất phát từ một cử chỉ người dùng).
		 *
		 * @param {number} index Vị trí bài trong trackButtons (0-based).
		 */
		function loadTrack( index ) {
			if ( index < 0 || index >= trackButtons.length ) {
				return;
			}

			var button = trackButtons[ index ];
			var src = button.getAttribute( 'data-nntm-audio-src' );

			if ( ! src ) {
				return;
			}

			resetListenSession();
			currentIndex = index;
			showPlaybackError( '' );
			audio.src = src;
			audio.load();

			if ( nowTitleEl ) {
				nowTitleEl.textContent = trackLabel( button );
			}
			updateAvatar( button );

			if ( timeCurrentEl ) {
				timeCurrentEl.textContent = '0:00';
			}
			if ( timeDurationEl ) {
				timeDurationEl.textContent = '0:00';
			}
			if ( progressRange ) {
				progressRange.value = '0';
			}

			updateTrackListUI();
			updatePlayButtonUI();
			dispatchTrackChange();
		}

		function playCurrent() {
			if ( -1 === currentIndex ) {
				// Chua chon bai nao — bam nut Phat lan dau thi tu chon bai dau danh sach.
				loadTrack( 0 );
			}

			showPlaybackError( '' );
			var playPromise = audio.play();
			if ( playPromise && 'function' === typeof playPromise.catch ) {
				playPromise.catch( function () {
					showPlaybackError( window.nntmThienDuongI18n.khongThePhat );
					updatePlayButtonUI();
					updateTrackListUI();
				} );
			}
		}

		function goToOffset( offset ) {
			if ( 0 === trackButtons.length ) {
				return;
			}

			var nextIndex;
			if ( -1 === currentIndex ) {
				nextIndex = offset < 0 ? trackButtons.length - 1 : 0;
			} else {
				nextIndex = ( currentIndex + offset + trackButtons.length ) % trackButtons.length;
			}
			var wasPlaying = ! audio.paused;

			loadTrack( nextIndex );

			if ( wasPlaying ) {
				playCurrent();
			}
		}

		// ---------- Nút phát/tạm dừng ----------
		playBtn.addEventListener( 'click', function () {
			if ( ! audio.paused ) {
				audio.pause();
			} else {
				playCurrent();
			}
		} );

		// ---------- Bài trước / bài sau (quay vòng, khớp trải nghiệm nghe liên tục) ----------
		if ( prevBtn ) {
			prevBtn.addEventListener( 'click', function () {
				goToOffset( -1 );
			} );
		}
		if ( nextBtn ) {
			nextBtn.addEventListener( 'click', function () {
				goToOffset( 1 );
			} );
		}

		// ---------- Chọn bài trực tiếp trong danh sách ----------
		for ( var t = 0; t < trackButtons.length; t++ ) {
			( function ( index ) {
				trackButtons[ index ].addEventListener( 'click', function () {
					loadTrack( index );
					playCurrent(); // bam chon bai la mot cu chi nguoi dung ro rang -> duoc phep phat.
				} );
			} )( t );
		}

		// ---------- Thanh tiến độ kéo được ----------
		if ( progressRange ) {
			progressRange.addEventListener( 'pointerdown', function () {
				isScrubbing = true;
			} );

			var stopScrubbing = function () {
				isScrubbing = false;
			};
			progressRange.addEventListener( 'pointerup', stopScrubbing );
			progressRange.addEventListener( 'pointercancel', stopScrubbing );

			progressRange.addEventListener( 'input', function () {
				if ( audio.duration && isFinite( audio.duration ) ) {
					var ratio = parseFloat( progressRange.value ) / 100;
					audio.currentTime = ratio * audio.duration;
				}
			} );
		}

		// ---------- Âm lượng ----------
		if ( volumeRange ) {
			volumeRange.addEventListener( 'input', function () {
				audio.volume = parseFloat( volumeRange.value ) / 100;
				audio.muted = false;
				if ( audio.volume > 0 ) { lastVolume = audio.volume; }
				updateVolumeUI();
			} );
		}

		if ( muteBtn ) {
			muteBtn.addEventListener( 'click', function () {
				if ( audio.muted || audio.volume <= 0.001 ) {
					audio.muted = false;
					audio.volume = lastVolume || 0.8;
					if ( volumeRange ) { volumeRange.value = String( Math.round( audio.volume * 100 ) ); }
				} else {
					lastVolume = audio.volume;
					audio.muted = true;
				}
				updateVolumeUI();
			} );
		}

		function closeSpeedMenu() {
			if ( speedMenu && settingsToggle ) {
				speedMenu.hidden = true;
				settingsToggle.setAttribute( 'aria-expanded', 'false' );
			}
		}

		if ( settingsToggle && speedMenu ) {
			settingsToggle.addEventListener( 'click', function () {
				var willOpen = speedMenu.hidden;
				speedMenu.hidden = ! willOpen;
				settingsToggle.setAttribute( 'aria-expanded', willOpen ? 'true' : 'false' );
			} );
			document.addEventListener( 'click', function ( event ) {
				if ( ! root.contains( event.target ) || ( ! speedMenu.contains( event.target ) && ! settingsToggle.contains( event.target ) ) ) {
					closeSpeedMenu();
				}
			} );
		}

		for ( var s = 0; s < speedOptions.length; s++ ) {
			speedOptions[ s ].addEventListener( 'click', function () {
				var rate = parseFloat( this.getAttribute( 'data-rate' ) );
				audio.playbackRate = isFinite( rate ) ? Math.max( 0.5, Math.min( 2, rate ) ) : 1;
				for ( var i = 0; i < speedOptions.length; i++ ) {
					var active = speedOptions[ i ] === this;
					speedOptions[ i ].classList.toggle( 'is-active', active );
					speedOptions[ i ].setAttribute( 'aria-pressed', active ? 'true' : 'false' );
					var check = speedOptions[ i ].querySelector( '.nntm-thien-duong__speed-check' );
					if ( check ) { check.textContent = active ? '✓' : ''; }
				}
				closeSpeedMenu();
			} );
		}

		// ---------- Đồng bộ trạng thái <audio> ngược lại giao diện ----------
		audio.addEventListener( 'play', function () {
			showPlaybackError( '' );
			updatePlayButtonUI();
			updateTrackListUI();
		} );

		// `playing` chỉ nổ khi media thực sự chạy (khác `play`, có thể còn buffering).
		audio.addEventListener( 'playing', function () {
			showPlaybackError( '' );
			dispatchPlaybackState( true, 'playing' );
			scheduleListenCount();
		} );

		audio.addEventListener( 'pause', function () {
			clearListenTimer();
			dispatchPlaybackState( false, 'pause' );
			updatePlayButtonUI();
			updateTrackListUI();
		} );

		audio.addEventListener( 'waiting', function () {
			// Buffering không được tính vào 5 giây phát liên tục.
			clearListenTimer();
		} );

		audio.addEventListener( 'loadedmetadata', function () {
			showPlaybackError( '' );
			if ( timeDurationEl ) {
				timeDurationEl.textContent = formatTime( audio.duration );
			}
		} );

		audio.addEventListener( 'canplay', function () {
			showPlaybackError( '' );
		} );

		audio.addEventListener( 'error', function () {
			clearListenTimer();
			dispatchPlaybackState( false, 'error' );
			showPlaybackError( window.nntmThienDuongI18n.khongThePhat );
			updatePlayButtonUI();
			updateTrackListUI();
		} );

		audio.addEventListener( 'timeupdate', function () {
			if ( timeCurrentEl ) {
				timeCurrentEl.textContent = formatTime( audio.currentTime );
			}

			if ( ! isScrubbing && progressRange && audio.duration && isFinite( audio.duration ) ) {
				progressRange.value = String( ( audio.currentTime / audio.duration ) * 100 );
			}
		} );

		// ---------- Hết bài tự chuyển bài kế tiếp ----------
		audio.addEventListener( 'ended', function () {
			clearListenTimer();
			dispatchPlaybackState( false, 'ended' );
			var nextIndex = currentIndex < 0 ? 0 : ( currentIndex + 1 ) % trackButtons.length;
			loadTrack( nextIndex );
			playCurrent();
		} );

		// Nạp metadata bài đầu tiên để tiêu đề/thời lượng sẵn sàng, nhưng tuyệt
		// đối không autoplay. Phát chỉ xảy ra sau click/tap của người dùng.
		loadTrack( 0 );
		updatePlayButtonUI();
		updateTrackListUI();
	}

	function nntmInitAllThienDuongPlayers() {
		var players = document.querySelectorAll( '[data-nntm-thien-duong]' );

		for ( var i = 0; i < players.length; i++ ) {
			nntmInitThienDuongPlayer( players[ i ] );
		}
	}

	// Chuỗi tiếng Việt dùng lại nhiều nơi trong script — tránh lặp/lệch dấu,
	// gói gọn ở một chỗ. KHÔNG dùng wp_localize_script vì view.js phải chạy
	// được độc lập, không phụ thuộc bước enqueue nào khác ngoài khai báo
	// "viewScript" trong block.json.
	window.nntmThienDuongI18n = window.nntmThienDuongI18n || {
		phat: 'Phát',
		tamDung: 'Tạm dừng',
		dangPhat: 'Đang phát',
		khongThePhat: 'Không thể phát tệp âm thanh này. Hãy kiểm tra lại file trong Media Library.',
	};

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', nntmInitAllThienDuongPlayers );
	} else {
		nntmInitAllThienDuongPlayers();
	}
} )();
