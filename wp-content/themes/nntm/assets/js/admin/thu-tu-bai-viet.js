/*
 * Kéo thả sắp xếp bài viết trong một danh mục.
 *
 * Thứ tự thật nằm ở ô ẩn #nntm-thu-tu-gia-tri dưới dạng "12,45,78". Danh sách
 * <li> chỉ là mặt hiện; mỗi lần thả xong thì đọc lại DOM rồi ghi vào ô ẩn.
 *
 * Ghi ngay lúc nạp trang chứ không đợi kéo: quản trị mở lên, không đổi gì, bấm
 * "Lưu thứ tự" — vẫn phải lưu đúng thứ tự đang nhìn thấy, vì đó là lúc họ chốt
 * thứ tự mặc định (mới nhất trước) thành thứ tự cố định.
 */
( function ( $ ) {
	'use strict';

	$( function () {
		var $ds = $( '#nntm-thu-tu-ds' );
		var $o = $( '#nntm-thu-tu-gia-tri' );

		if ( ! $ds.length || ! $o.length ) {
			return;
		}

		function capNhat() {
			var ids = $ds
				.children( '.nntm-thu-tu__muc' )
				.map( function () {
					return $( this ).data( 'id' );
				} )
				.get();

			$o.val( ids.join( ',' ) );
		}

		capNhat();

		$ds.sortable( {
			handle: '.nntm-thu-tu__tay',
			axis: 'y',
			cursor: 'grabbing',
			placeholder: 'nntm-thu-tu__cho-trong',
			forcePlaceholderSize: true,
			update: capNhat,
		} );
	} );
} )( window.jQuery );
