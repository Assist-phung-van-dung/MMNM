/*
 * Bảng "Bài viết liên quan" bên phải trình soạn thảo khối.
 *
 * Giữ một MẢNG ID có thứ tự trong meta _nntm_bai_lien_quan. Thứ tự trong bảng
 * chính là thứ tự hiện ngoài web, nên có nút lên/xuống chứ không chỉ thêm/bớt.
 *
 * Chỉ tìm trong CÙNG loại nội dung với bài đang sửa: khối card-list ngoài web
 * chỉ truy vấn được một post_type, chọn khác loại thì thẻ sẽ không bao giờ
 * hiện ra mà chẳng báo lỗi gì.
 */
( function ( wp ) {
	'use strict';

	if ( ! wp || ! wp.plugins || ! wp.data || ! wp.element || ! wp.components ) {
		return;
	}

	/*
	 * PluginDocumentSettingPanel chuyển từ gói edit-post sang gói editor ở
	 * WordPress 6.6. Lấy chỗ mới trước, chỗ cũ chỉ là đường lui cho bản cũ.
	 */
	var PluginDocumentSettingPanel =
		( wp.editor && wp.editor.PluginDocumentSettingPanel ) ||
		( wp.editPost && wp.editPost.PluginDocumentSettingPanel );

	if ( ! PluginDocumentSettingPanel ) {
		return;
	}

	var el = wp.element.createElement;
	var useState = wp.element.useState;
	var useSelect = wp.data.useSelect;
	var useDispatch = wp.data.useDispatch;
	var Button = wp.components.Button;
	var TextControl = wp.components.TextControl;
	var Spinner = wp.components.Spinner;
	var __ = wp.i18n.__;

	var KHOA_META = '_nntm_bai_lien_quan';
	var TOI_DA = 24;

	/* Danh sách loại nội dung được chọn bài liên quan, do PHP truyền sang. */
	var LOAI_BAI = window.nntmLienQuanLoaiBai || [];

	/** Lọc mảng thô thành mảng số nguyên dương, bỏ trùng, giữ thứ tự. */
	function locIds( tho ) {
		var ra = [];
		( tho || [] ).forEach( function ( v ) {
			var n = parseInt( v, 10 );
			if ( n > 0 && -1 === ra.indexOf( n ) ) {
				ra.push( n );
			}
		} );
		return ra;
	}

	/** Tiêu đề hiển thị của một bản ghi, lui về #ID khi chưa có tiêu đề. */
	function tenBai( p ) {
		if ( ! p ) {
			return '';
		}
		var t = p.title && p.title.rendered ? p.title.rendered.trim() : '';
		return t !== '' ? t : '#' + p.id;
	}

	function BangLienQuan() {
		var oTim = useState( '' );
		var tuKhoa = oTim[ 0 ];
		var datTuKhoa = oTim[ 1 ];

		var bai = useSelect( function ( select ) {
			var editor = select( 'core/editor' );
			var meta = editor.getEditedPostAttribute( 'meta' ) || {};

			return {
				loaiBai: editor.getCurrentPostType(),
				idHienTai: editor.getCurrentPostId(),
				daChon: locIds( meta[ KHOA_META ] ),
			};
		}, [] );

		/*
		 * Bài đã chọn — tải về để hiện tiêu đề thay vì trơ ra con số ID.
		 * Phụ thuộc theo chuỗi join để mảng mới cùng nội dung không kích hoạt
		 * gọi lại API một cách vô ích.
		 */
		var dsDaChon = useSelect(
			function ( select ) {
				if ( ! bai.daChon.length ) {
					return [];
				}
				return (
					select( 'core' ).getEntityRecords( 'postType', bai.loaiBai, {
						include: bai.daChon,
						per_page: TOI_DA,
						context: 'view',
					} ) || []
				);
			},
			[ bai.daChon.join( ',' ), bai.loaiBai ]
		);

		/* Kết quả tìm. Chưa gõ đủ 2 ký tự thì không gọi API. */
		var ketQua = useSelect(
			function ( select ) {
				if ( tuKhoa.trim().length < 2 ) {
					return null;
				}
				return select( 'core' ).getEntityRecords( 'postType', bai.loaiBai, {
					search: tuKhoa.trim(),
					per_page: 10,
					status: 'publish',
					context: 'view',
				} );
			},
			[ tuKhoa, bai.loaiBai ]
		);

		var editPost = useDispatch( 'core/editor' ).editPost;

		if ( -1 === LOAI_BAI.indexOf( bai.loaiBai ) ) {
			return null;
		}

		function luu( ids ) {
			var moi = {};
			moi[ KHOA_META ] = locIds( ids );
			editPost( { meta: moi } );
		}

		function them( id ) {
			if ( bai.daChon.length >= TOI_DA || -1 !== bai.daChon.indexOf( id ) ) {
				return;
			}
			luu( bai.daChon.concat( [ id ] ) );
		}

		function bo( id ) {
			luu(
				bai.daChon.filter( function ( v ) {
					return v !== id;
				} )
			);
		}

		function doiCho( tu, den ) {
			if ( den < 0 || den >= bai.daChon.length ) {
				return;
			}
			var ds = bai.daChon.slice();
			var tam = ds[ tu ];
			ds[ tu ] = ds[ den ];
			ds[ den ] = tam;
			luu( ds );
		}

		function tieuDeTheoId( id ) {
			var found = null;
			( dsDaChon || [] ).forEach( function ( p ) {
				if ( p.id === id ) {
					found = p;
				}
			} );
			return found ? tenBai( found ) : '#' + id;
		}

		var noiDung = [];

		if ( bai.daChon.length ) {
			noiDung.push(
				el(
					'ol',
					{ key: 'da-chon', className: 'nntm-lq__ds' },
					bai.daChon.map( function ( id, i ) {
						return el(
							'li',
							{ key: id, className: 'nntm-lq__muc' },
							el( 'span', { className: 'nntm-lq__ten' }, tieuDeTheoId( id ) ),
							el(
								'span',
								{ className: 'nntm-lq__nut' },
								el( Button, {
									icon: 'arrow-up-alt2',
									label: __( 'Lên trên', 'nntm' ),
									disabled: 0 === i,
									onClick: function () {
										doiCho( i, i - 1 );
									},
								} ),
								el( Button, {
									icon: 'arrow-down-alt2',
									label: __( 'Xuống dưới', 'nntm' ),
									disabled: i === bai.daChon.length - 1,
									onClick: function () {
										doiCho( i, i + 1 );
									},
								} ),
								el( Button, {
									icon: 'no-alt',
									isDestructive: true,
									label: __( 'Bỏ khỏi danh sách', 'nntm' ),
									onClick: function () {
										bo( id );
									},
								} )
							)
						);
					} )
				)
			);
		} else {
			noiDung.push(
				el(
					'p',
					{ key: 'trong', className: 'components-base-control__help' },
					__(
						'Chưa chọn bài nào. Để trống thì trang chi tiết vẫn tự lấy bài cùng phân mục như trước.',
						'nntm'
					)
				)
			);
		}

		noiDung.push(
			el( TextControl, {
				key: 'tim',
				label: __( 'Tìm bài để thêm', 'nntm' ),
				value: tuKhoa,
				onChange: datTuKhoa,
				placeholder: __( 'Gõ ít nhất 2 ký tự…', 'nntm' ),
				disabled: bai.daChon.length >= TOI_DA,
			} )
		);

		if ( bai.daChon.length >= TOI_DA ) {
			noiDung.push(
				el(
					'p',
					{ key: 'day', className: 'components-base-control__help' },
					__( 'Đã đủ 24 bài — bỏ bớt mới thêm được.', 'nntm' )
				)
			);
		} else if ( tuKhoa.trim().length >= 2 ) {
			if ( null === ketQua ) {
				noiDung.push( el( Spinner, { key: 'cho' } ) );
			} else {
				var conLai = ketQua.filter( function ( p ) {
					return p.id !== bai.idHienTai && -1 === bai.daChon.indexOf( p.id );
				} );

				if ( ! conLai.length ) {
					noiDung.push(
						el(
							'p',
							{ key: 'khong', className: 'components-base-control__help' },
							__( 'Không còn bài nào khớp để thêm.', 'nntm' )
						)
					);
				} else {
					noiDung.push(
						el(
							'ul',
							{ key: 'ket-qua', className: 'nntm-lq__ket-qua' },
							conLai.map( function ( p ) {
								return el(
									'li',
									{ key: p.id },
									el(
										Button,
										{
											variant: 'link',
											onClick: function () {
												them( p.id );
											},
										},
										tenBai( p )
									)
								);
							} )
						)
					);
				}
			}
		}

		noiDung.push(
			el(
				'p',
				{ key: 'giai-thich', className: 'components-base-control__help' },
				__(
					'Có chọn thì dải liên quan hiện đúng những bài này, đúng thứ tự trên xuống. Chỉ tìm được bài cùng loại với bài đang sửa.',
					'nntm'
				)
			)
		);

		return el(
			PluginDocumentSettingPanel,
			{
				name: 'nntm-bai-lien-quan',
				title: __( 'Bài viết liên quan', 'nntm' ),
				className: 'nntm-lq',
			},
			noiDung
		);
	}

	wp.plugins.registerPlugin( 'nntm-bai-lien-quan', {
		render: BangLienQuan,
		icon: 'admin-links',
	} );
} )( window.wp );
