/**
 * Chọn tệp PDF cho ấn phẩm trong meta box "Tệp PDF & Khoá xem".
 * Chỉ nạp trên màn sửa nntm_publication — xem enqueue_publication_admin_assets().
 */
( function () {
	'use strict';

	document.addEventListener( 'DOMContentLoaded', function () {
		var noiInput = document.getElementById( 'nntm_pdf_file_input' );
		var noiTen = document.getElementById( 'nntm-pdf-file-ten' );
		var nutChon = document.getElementById( 'nntm-pdf-file-chon' );
		var nutXoa = document.getElementById( 'nntm-pdf-file-xoa' );

		if ( ! noiInput || ! nutChon || 'undefined' === typeof wp || ! wp.media ) {
			return;
		}

		var khungChon = null;

		nutChon.addEventListener( 'click', function ( su ) {
			su.preventDefault();

			if ( khungChon ) {
				khungChon.open();
				return;
			}

			khungChon = wp.media( {
				title: nutChon.dataset.title || 'Chọn tệp PDF',
				library: { type: 'application/pdf' },
				multiple: false,
			} );

			khungChon.on( 'select', function () {
				var tep = khungChon.state().get( 'selection' ).first().toJSON();
				noiInput.value = tep.id;
				noiTen.textContent = tep.title || tep.filename;
				nutXoa.style.display = '';
			} );

			khungChon.open();
		} );

		if ( nutXoa ) {
			nutXoa.addEventListener( 'click', function ( su ) {
				su.preventDefault();
				noiInput.value = '0';
				noiTen.textContent = 'Chưa chọn tệp.';
				nutXoa.style.display = 'none';
			} );
		}
	} );
}() );
