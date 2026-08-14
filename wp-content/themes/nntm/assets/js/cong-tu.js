/**
 * Ba nút bấm nhanh 10/20/50 ở màn "Khai Báo Chuỗi Trì" — JS thuần, không thư
 * viện. Bấm nút thì điền số vào ô nhập; TẮT JS vẫn gõ tay được vào ô số bình
 * thường (yêu cầu bắt buộc, xem docs/07-ban-giao.md).
 *
 * @package NNTM
 */
( function () {
	'use strict';

	function ganSuKienNutNhanh() {
		var nodeList = document.querySelectorAll( '[data-nntm-congtu-quick]' );

		Array.prototype.forEach.call( nodeList, function ( nut ) {
			nut.addEventListener( 'click', function () {
				var gia_tri  = nut.getAttribute( 'data-nntm-congtu-quick' );
				var id_o_nhap = nut.getAttribute( 'data-nntm-congtu-target' );
				var o_nhap   = id_o_nhap ? document.getElementById( id_o_nhap ) : null;

				if ( ! o_nhap ) {
					return;
				}

				o_nhap.value = gia_tri;
				o_nhap.focus();
			} );
		} );
	}

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', ganSuKienNutNhanh );
	} else {
		ganSuKienNutNhanh();
	}
} )();
