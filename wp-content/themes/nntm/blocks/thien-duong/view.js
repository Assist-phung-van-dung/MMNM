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
 * CHỖ CẮM SOKETI (docs/04-kien-truc.md mục 5): phần kết nối kênh presence
 * "presence-thien-duong" thuộc plugin nntm-audio, làm ở giai đoạn sau vì
 * Soketi chưa dựng được ở máy local. File này chỉ expose một hàm toàn cục
 * để phần đó gọi vào — không cần sửa lại block khi ghép Soketi thật:
 *
 *     window.nntmThienDuongSetPresence( soNguoi )
 *
 * Gọi hàm này với một số nguyên >= 0 để cập nhật chữ "Đang có N người
 * cùng nghe" trên mọi khối Thiền Đường có trên trang. Gọi với giá trị
 * không hợp lệ (không phải số, âm) sẽ được hàm tự bỏ qua, không báo lỗi.
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
		var trackButtons = root.querySelectorAll( '.nntm-thien-duong__track' );

		if ( ! audio || ! playBtn || 0 === trackButtons.length ) {
			return;
		}

		var currentIndex = -1; // chua chon bai nao.
		var isScrubbing = false; // dang keo thanh tien do bang tay, tam ngung dong bo tu timeupdate.

		// Am luong khoi tao theo gia tri co san cua thanh truot (mac dinh 80%).
		if ( volumeRange ) {
			audio.volume = ( parseFloat( volumeRange.value ) || 80 ) / 100;
		}

		function trackLabel( button ) {
			return button.getAttribute( 'data-nntm-track-title' ) || '';
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

			currentIndex = index;
			audio.src = src;

			if ( nowTitleEl ) {
				nowTitleEl.textContent = trackLabel( button );
			}

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
		}

		function playCurrent() {
			if ( -1 === currentIndex ) {
				// Chua chon bai nao — bam nut Phat lan dau thi tu chon bai dau danh sach.
				loadTrack( 0 );
			}

			// audio.play() tra ve Promise, co the bi trinh duyet tu choi trong
			// vai truong hop hiem (vd tab bi thu nho) — bat loi de khong lam
			// vo script, khong can bao nguoi dung vi day khong phai loi cua ho.
			var playPromise = audio.play();
			if ( playPromise && 'function' === typeof playPromise.catch ) {
				playPromise.catch( function () {} );
			}
		}

		function goToOffset( offset ) {
			if ( 0 === trackButtons.length ) {
				return;
			}

			var base = -1 === currentIndex ? 0 : currentIndex;
			var nextIndex = ( base + offset + trackButtons.length ) % trackButtons.length;
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
			} );
		}

		// ---------- Đồng bộ trạng thái <audio> ngược lại giao diện ----------
		audio.addEventListener( 'play', function () {
			updatePlayButtonUI();
			updateTrackListUI();
		} );

		audio.addEventListener( 'pause', function () {
			updatePlayButtonUI();
			updateTrackListUI();
		} );

		audio.addEventListener( 'loadedmetadata', function () {
			if ( timeDurationEl ) {
				timeDurationEl.textContent = formatTime( audio.duration );
			}
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
			goToOffset( 1 );
			playCurrent();
		} );

		updatePlayButtonUI();
		updateTrackListUI();
	}

	function nntmInitAllThienDuongPlayers() {
		var players = document.querySelectorAll( '[data-nntm-thien-duong]' );

		for ( var i = 0; i < players.length; i++ ) {
			nntmInitThienDuongPlayer( players[ i ] );
		}
	}

	/**
	 * CHỖ CẮM SOKETI — chỗ duy nhất phần kết nối presence (plugin nntm-audio,
	 * giai đoạn sau) cần gọi vào. Cập nhật mọi khối Thiền Đường trên trang
	 * (thường chỉ có một, nhưng không giả định điều đó).
	 *
	 * @param {number} soNguoi Số người đang cùng nghe. Giá trị không hợp lệ bị bỏ qua im lặng.
	 */
	window.nntmThienDuongSetPresence = function ( soNguoi ) {
		if ( 'number' !== typeof soNguoi || ! isFinite( soNguoi ) || soNguoi < 0 ) {
			return;
		}

		var count = Math.floor( soNguoi );
		var presenceNodes = document.querySelectorAll( '.nntm-thien-duong__presence' );

		for ( var i = 0; i < presenceNodes.length; i++ ) {
			presenceNodes[ i ].textContent = window.nntmThienDuongI18n.dangCoNguoiCungNghe.replace( '%d', String( count ) );
		}
	};

	// Chuỗi tiếng Việt dùng lại nhiều nơi trong script — tránh lặp/lệch dấu,
	// gói gọn ở một chỗ. KHÔNG dùng wp_localize_script vì view.js phải chạy
	// được độc lập, không phụ thuộc bước enqueue nào khác ngoài khai báo
	// "viewScript" trong block.json.
	window.nntmThienDuongI18n = window.nntmThienDuongI18n || {
		phat: 'Phát',
		tamDung: 'Tạm dừng',
		dangPhat: 'Đang phát',
		dangCoNguoiCungNghe: 'Đang có %d người cùng nghe',
	};

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', nntmInitAllThienDuongPlayers );
	} else {
		nntmInitAllThienDuongPlayers();
	}
} )();
