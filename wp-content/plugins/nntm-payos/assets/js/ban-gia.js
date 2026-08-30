/**
 * Chọn tệp PDF xem thử bằng Thư viện Media, cho màn "Bán & Giá".
 *
 * Mỗi dòng một khung chọn riêng vì wp.media nhớ lựa chọn trước đó — dùng chung
 * một khung thì chọn cho cuốn này xong, mở cuốn khác lại thấy tệp của cuốn cũ.
 */
( function ( $ ) {
	'use strict';

	var CH = window.nntmPayosGia || {};
	var khung = {};

	$( document ).on( 'click', '[data-chon-tep]', function ( e ) {
		e.preventDefault();

		var o = $( this ).closest( '.nntm-payos-xemthu' );
		var id = o.data( 'post' );

		if ( ! khung[ id ] ) {
			khung[ id ] = wp.media( {
				title: CH.tieuDe || 'Chọn tệp PDF',
				library: { type: 'application/pdf' },
				button: { text: CH.nutChon || 'Dùng tệp này' },
				multiple: false
			} );

			khung[ id ].on( 'select', function () {
				var tep = khung[ id ].state().get( 'selection' ).first().toJSON();

				o.find( '[data-o-tep]' ).val( tep.id );
				o.find( '[data-ten-tep]' ).text( tep.title || tep.filename );
				o.find( '[data-bo-tep]' ).show();
			} );
		}

		khung[ id ].open();
	} );

	$( document ).on( 'click', '[data-bo-tep]', function ( e ) {
		e.preventDefault();

		var o = $( this ).closest( '.nntm-payos-xemthu' );

		o.find( '[data-o-tep]' ).val( '' );
		o.find( '[data-ten-tep]' ).text( CH.chuaChon || 'Chưa chọn' );
		$( this ).hide();
	} );
} )( jQuery );
