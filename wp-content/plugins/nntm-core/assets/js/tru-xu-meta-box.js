/**
 * Chọn bộ ảnh cho Trú Xứ trong meta box "Vị trí trên bản đồ".
 *
 * Chỉ nạp trên màn sửa nntm_abode — xem enqueue_publication_admin_assets().
 * Danh sách ID ảnh lưu vào một ô ẩn dạng "12,34,56"; phía PHP lọc lại bằng
 * sanitize_bo_anh().
 */
( function () {
	'use strict';

	document.addEventListener( 'DOMContentLoaded', function () {
		var oAn = document.getElementById( 'nntm_abode_gallery' );
		var oXem = document.getElementById( 'nntm-abode-gallery-xem' );
		var nutChon = document.getElementById( 'nntm-abode-gallery-chon' );
		var nutXoa = document.getElementById( 'nntm-abode-gallery-xoa' );

		if ( ! oAn || ! oXem || ! nutChon || 'undefined' === typeof wp || ! wp.media ) {
			return;
		}

		function layIds() {
			return oAn.value
				.split( ',' )
				.map( function ( v ) { return parseInt( v, 10 ); } )
				.filter( function ( v ) { return v > 0; } );
		}

		function datIds( ids ) {
			oAn.value = ids.join( ',' );
			nutXoa.style.display = ids.length ? '' : 'none';
		}

		/*
		 * Vẽ lại phần xem trước. Ảnh lấy qua wp.media.attachment nên không phải
		 * gọi thêm REST: dữ liệu đã có trong bộ đệm của trình soạn thảo.
		 */
		function veXem( ids ) {
			oXem.innerHTML = '';

			ids.forEach( function ( id ) {
				var tep = wp.media.attachment( id );

				var hinh = document.createElement( 'img' );
				hinh.alt = '';
				oXem.appendChild( hinh );

				function ganNguon() {
					var d = tep.toJSON();
					var nho = d.sizes && d.sizes.thumbnail ? d.sizes.thumbnail.url : d.url;
					if ( nho ) {
						hinh.src = nho;
					}
				}

				if ( tep.get( 'url' ) ) {
					ganNguon();
				} else {
					tep.fetch().done( ganNguon );
				}
			} );
		}

		var khungChon = null;

		nutChon.addEventListener( 'click', function ( su ) {
			su.preventDefault();

			if ( ! khungChon ) {
				khungChon = wp.media( {
					title: 'Chọn ảnh cho bộ ảnh Trú Xứ',
					library: { type: 'image' },
					multiple: 'add',
					button: { text: 'Dùng những ảnh này' },
				} );

				khungChon.on( 'select', function () {
					var chon = khungChon.state().get( 'selection' ).toJSON();
					var dangCo = layIds();

					chon.forEach( function ( tep ) {
						if ( dangCo.indexOf( tep.id ) === -1 ) {
							dangCo.push( tep.id );
						}
					} );

					datIds( dangCo );
					veXem( dangCo );
				} );
			}

			/* Mở lại thì tick sẵn những ảnh đang chọn. */
			khungChon.on( 'open', function () {
				var chon = khungChon.state().get( 'selection' );
				chon.reset( layIds().map( function ( id ) {
					return wp.media.attachment( id );
				} ) );
			} );

			khungChon.open();
		} );

		nutXoa.addEventListener( 'click', function ( su ) {
			su.preventDefault();
			datIds( [] );
			veXem( [] );
		} );

		veXem( layIds() );
	} );
}() );
