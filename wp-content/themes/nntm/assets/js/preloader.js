/**
 * NNTM — Màn chờ tải trang: mở trang khi đã tải xong.
 *
 * Việc BỐC hiệu ứng không nằm ở đây mà ở một script inline in ra trong
 * <head> (xem nntm_preloader_head_script() trong inc/preloader.php). Lý do:
 * `is-loading` và `data-effect` phải có mặt ngay từ khung hình đầu tiên,
 * còn file này thì nạp ở chân trang nên đã quá muộn.
 *
 * File này chỉ lo một việc: quyết định KHI NÀO gỡ lớp phủ.
 *
 * JavaScript thuần, không phụ thuộc thư viện ngoài.
 */
( function () {
	'use strict';

	var root = document.documentElement;
	var loader = document.querySelector( '.nntm-tai' );

	if ( ! loader ) {
		// Không có lớp phủ (trang tắt màn chờ) — dọn lớp khoá cuộn cho chắc.
		root.classList.remove( 'is-loading', 'is-revealing' );
		return;
	}

	/*
	 * Thời gian tối thiểu hiệu ứng được hiện, tính từ lúc bắt đầu điều hướng.
	 * Trang trong cache tải gần như tức thì; không có mốc này thì hiệu ứng chỉ
	 * nhá một cái rồi tắt. Số lấy từ bản gốc anh Úy chọn.
	 */
	var THOI_LUONG = {
		halo: 1800,
		mandala: 1900,
		moon: 1900,
		sun: 1900
	};

	/* Khớp transition dài nhất lúc tan (.__trang / .__nhat 1s, lớp phủ 0.55s). */
	var THOI_GIAN_TAN = 850;

	/*
	 * Lưới an toàn: một ảnh hoặc iframe treo là sự kiện `load` không bao giờ
	 * bắn, mà lớp phủ thì đang che cả trang. Quá mốc này thì mở trang bất chấp.
	 */
	var LUOI_AN_TOAN = 6000;

	var giamChuyenDong = window.matchMedia
		&& window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;

	var boDem = [];
	var daMo = false;

	/**
	 * Hẹn một việc và ghi lại để còn huỷ được.
	 *
	 * @param {number}   cho Số milli-giây chờ.
	 * @param {Function} fn  Việc cần làm.
	 */
	function hen( cho, fn ) {
		boDem.push( window.setTimeout( fn, cho ) );
	}

	/**
	 * Huỷ mọi hẹn còn treo.
	 */
	function huyHen() {
		boDem.forEach( window.clearTimeout );
		boDem = [];
	}

	/**
	 * Gỡ lớp phủ và trả lại quyền cuộn trang. Gọi bao nhiêu lần cũng chỉ
	 * chạy một lần.
	 */
	function moTrang() {
		if ( daMo ) {
			return;
		}

		daMo = true;
		huyHen();

		root.classList.add( 'is-revealing' );

		hen( giamChuyenDong ? 120 : THOI_GIAN_TAN, function () {
			root.classList.remove( 'is-loading' );
			root.classList.remove( 'is-revealing' );
		} );
	}

	var hieuUng = root.getAttribute( 'data-effect' );
	var toiThieu = giamChuyenDong ? 300 : ( THOI_LUONG[ hieuUng ] || 1800 );

	/**
	 * Chờ cho đủ thời lượng tối thiểu rồi mới mở.
	 *
	 * performance.now() đếm từ lúc bắt đầu điều hướng, nên nó CHÍNH LÀ "trang
	 * đã mở bao lâu rồi" — không cần tự đánh mốc thời gian.
	 */
	function choDuThoiLuong() {
		var conLai = toiThieu - window.performance.now();

		hen( conLai > 0 ? conLai : 0, moTrang );
	}

	if ( 'complete' === document.readyState ) {
		choDuThoiLuong();
	} else {
		window.addEventListener( 'load', choDuThoiLuong, { once: true } );
	}

	hen( LUOI_AN_TOAN, moTrang );

	/*
	 * Thoát nhanh bằng Escape. Giữ lại từ bản gốc: nếu vì lý do gì lớp phủ
	 * không tự tan, người dùng vẫn có đường vào nội dung.
	 */
	document.addEventListener( 'keydown', function ( event ) {
		if ( 'Escape' === event.key || 'Esc' === event.key ) {
			huyHen();
			daMo = true;
			root.classList.remove( 'is-loading' );
			root.classList.remove( 'is-revealing' );
		}
	} );
} )();
