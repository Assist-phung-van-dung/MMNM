/*
 * Nhạc nền bài viết: tự phát khi mở bài, người đọc dừng được bất cứ lúc nào.
 *
 * Ba chuyện phải lo, vì thiếu cái nào là khách khó chịu:
 *
 * 1. Trình duyệt CHẶN tự phát có tiếng khi khách chưa từng bấm/chạm gì trên
 *    trang. Đây là luật của Chrome/Safari/Firefox, không có cách nào lách được
 *    bằng JS — trang nào "tự phát được" là vì khách đã tương tác với tên miền
 *    đó đủ nhiều nên trình duyệt cho phép. Vậy nên: thử phát ngay; bị chặn thì
 *    nạp sẵn tệp, hiện nhịp thở quanh nút để khách biết mà bấm, và tự phát
 *    ngay khi khách chạm/gõ phím lần đầu ở bất cứ đâu trên trang.
 * 2. Người đọc bấm dừng nghĩa là "thôi, không nghe nữa" — nhớ trong phiên làm
 *    việc để bài sau không tự phát lại, chứ không phải bắt tắt lại từng bài.
 * 3. Vào tiếng cái rụp thì giật mình. Mở/tắt tiếng đều vuốt dần.
 */
( function () {
	'use strict';

	var khung = document.querySelector( '[data-nntm-nhac]' );

	if ( ! khung ) {
		return;
	}

	var tep = khung.querySelector( '[data-nntm-nhac-tep]' );
	var nut = khung.querySelector( '[data-nntm-nhac-nut]' );
	var oTinhTrang = khung.querySelector( '[data-nntm-nhac-tinh-trang]' );

	if ( ! tep || ! nut ) {
		return;
	}

	/* Cờ "khách đã tắt nhạc" — sống trong phiên, không theo khách qua ngày sau. */
	var KHOA_TAT = 'nntm-nhac-tat';
	var VUOT_MS = 500;
	var itChuyenDong = window.matchMedia( '(prefers-reduced-motion: reduce)' );

	var dangVuot = null;
	var hetGio = null;
	var doiTuongTac = false;

	function docCoTat() {
		try {
			return '1' === window.sessionStorage.getItem( KHOA_TAT );
		} catch ( loi ) {
			/* Trình duyệt chặn sessionStorage (chế độ riêng tư) — coi như chưa tắt. */
			return false;
		}
	}

	function ghiCoTat( tat ) {
		try {
			if ( tat ) {
				window.sessionStorage.setItem( KHOA_TAT, '1' );
			} else {
				window.sessionStorage.removeItem( KHOA_TAT );
			}
		} catch ( loi ) {
			/* Không ghi được thì thôi, chỉ mất phần ghi nhớ. */
		}
	}

	function veTinhTrang( dangPhat ) {
		khung.classList.toggle( 'is-phat', dangPhat );

		/* Đang phát rồi thì bỏ lời nhắc bấm. */
		if ( dangPhat ) {
			khung.classList.remove( 'is-cho-cham' );
		}

		nut.setAttribute( 'aria-pressed', dangPhat ? 'true' : 'false' );
		nut.setAttribute(
			'aria-label',
			( dangPhat ? nut.getAttribute( 'data-nhan-dung' ) : nut.getAttribute( 'data-nhan-phat' ) ) || ''
		);

		if ( oTinhTrang ) {
			oTinhTrang.textContent = dangPhat ? 'Đang phát nhạc nền.' : 'Đã dừng nhạc nền.';
		}
	}

	function huyVuot() {
		if ( dangVuot ) {
			window.cancelAnimationFrame( dangVuot );
			dangVuot = null;
		}

		if ( hetGio ) {
			window.clearTimeout( hetGio );
			hetGio = null;
		}
	}

	/** Vuốt âm lượng từ mức hiện tại tới đích; tới nơi thì gọi $xong. */
	function vuotAmLuong( dich, xong ) {
		huyVuot();

		var dau = tep.volume;
		var moc = null;
		var xongRoi = false;

		/* Chốt lại đúng một lần, dù tới đây bằng đường nào. */
		function chot() {
			if ( xongRoi ) {
				return;
			}

			xongRoi = true;
			huyVuot();
			tep.volume = dich;

			if ( xong ) {
				xong();
			}
		}

		/*
		 * Tab ẩn thì requestAnimationFrame không chạy, mà cũng chẳng ai đang
		 * nghe để mà vuốt cho êm. Khách xin ít chuyển động cũng vậy.
		 */
		if ( itChuyenDong.matches || document.hidden ) {
			chot();
			return;
		}

		function buoc( nhip ) {
			if ( null === moc ) {
				moc = nhip;
			}

			var ti = Math.min( 1, ( nhip - moc ) / VUOT_MS );

			tep.volume = Math.max( 0, Math.min( 1, dau + ( dich - dau ) * ti ) );

			if ( ti < 1 ) {
				dangVuot = window.requestAnimationFrame( buoc );
				return;
			}

			chot();
		}

		dangVuot = window.requestAnimationFrame( buoc );

		/*
		 * Chốt chặn bằng hẹn giờ. Có khung nhúng (và tab bị hạ ưu tiên) không
		 * chạy requestAnimationFrame dù document.hidden vẫn báo false; lúc đó
		 * việc DỪNG nhạc nằm ở cuối lượt vuốt sẽ không bao giờ tới — khách bấm
		 * Dừng mà nhạc vẫn kêu. Hẹn giờ luôn nổ, nên chuyện dừng được bảo đảm.
		 */
		hetGio = window.setTimeout( chot, VUOT_MS + 250 );
	}

	/**
	 * Thử phát.
	 *
	 * @param {boolean} doKhachBam Khách tự bấm nút hay là lượt tự phát.
	 */
	function phat( doKhachBam ) {
		tep.volume = 0;

		var loi_hua = tep.play();

		/* Trình duyệt cũ trả về undefined thay vì Promise. */
		if ( ! loi_hua || ! loi_hua.then ) {
			veTinhTrang( true );
			vuotAmLuong( 1 );
			return;
		}

		loi_hua
			.then( function () {
				veTinhTrang( true );
				vuotAmLuong( 1 );
			} )
			.catch( function () {
				veTinhTrang( false );

				/*
				 * Khách tự bấm mà vẫn không phát được thì là lỗi tệp, không phải
				 * luật tự phát — đợi thêm cũng vô ích.
				 */
				if ( doKhachBam ) {
					return;
				}

				/*
				 * Bị chặn. Đổi preload sang 'auto' để tệp nằm sẵn trong bộ nhớ:
				 * lúc khách chạm là nhạc vào ngay, không phải chờ tải.
				 */
				tep.preload = 'auto';
				tep.load();

				khung.classList.add( 'is-cho-cham' );
				doiTuongTacDauTien();
			} );
	}

	function dung( doKhachBam ) {
		veTinhTrang( false );
		khung.classList.remove( 'is-cho-cham' );

		vuotAmLuong( 0, function () {
			tep.pause();
		} );

		if ( doKhachBam ) {
			ghiCoTat( true );
		}
	}

	/** Bị chặn tự phát: chờ đúng một cú chạm/gõ phím đầu tiên rồi thử lại. */
	function doiTuongTacDauTien() {
		if ( doiTuongTac ) {
			return;
		}

		doiTuongTac = true;

		function thuLai() {
			go();

			/* Khách đã bấm nút dừng trong lúc chờ thì tôn trọng, đừng phát nữa. */
			if ( ! docCoTat() && tep.paused ) {
				phat( false );
			}
		}

		function go() {
			document.removeEventListener( 'pointerdown', thuLai );
			document.removeEventListener( 'keydown', thuLai );
			document.removeEventListener( 'touchstart', thuLai );
		}

		document.addEventListener( 'pointerdown', thuLai, { once: true } );
		document.addEventListener( 'keydown', thuLai, { once: true } );
		document.addEventListener( 'touchstart', thuLai, { once: true } );
	}

	nut.addEventListener( 'click', function () {
		if ( tep.paused ) {
			ghiCoTat( false );
			phat( true );
			return;
		}

		dung( true );
	} );

	/* Hết bài thì trả nút về trạng thái "phát", không để sóng nhạc chạy tiếp. */
	tep.addEventListener( 'ended', function () {
		veTinhTrang( false );
	} );

	/*
	 * Đoạn mồi in thẳng trong trang (xem nntm_render_nhac_nen) thường đã cho
	 * nhạc chạy từ trước khi tệp này được nạp. Gặp trường hợp đó thì chỉ đồng
	 * bộ lại nút và sóng nhạc, đừng gọi phát lần nữa.
	 */
	if ( ! tep.paused ) {
		veTinhTrang( true );
	} else if ( ! docCoTat() ) {
		phat( false );
	}
} )();
