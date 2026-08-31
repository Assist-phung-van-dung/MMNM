/*
 * Bảng "Nhạc nền bài viết" bên phải trình soạn thảo khối.
 *
 * Chỉ giữ MỘT ID tệp đính kèm trong meta _nntm_nhac_nen. Không lưu đường dẫn:
 * đổi tên miền hay đổi cấu trúc thư mục uploads là đường dẫn cũ chết, còn ID
 * thì luôn tra ra được tệp thật.
 */
( function ( wp ) {
	'use strict';

	if ( ! wp || ! wp.plugins || ! wp.data || ! wp.element || ! wp.components || ! wp.blockEditor ) {
		return;
	}

	/*
	 * PluginDocumentSettingPanel chuyển từ gói edit-post sang gói editor ở
	 * WordPress 6.6. Lấy ở chỗ mới trước, chỗ cũ chỉ là đường lui cho bản cũ.
	 */
	var PluginDocumentSettingPanel =
		( wp.editor && wp.editor.PluginDocumentSettingPanel ) ||
		( wp.editPost && wp.editPost.PluginDocumentSettingPanel );

	if ( ! PluginDocumentSettingPanel ) {
		return;
	}

	var el = wp.element.createElement;
	var useSelect = wp.data.useSelect;
	var useDispatch = wp.data.useDispatch;
	var Button = wp.components.Button;
	var Notice = wp.components.Notice;
	var Spinner = wp.components.Spinner;
	var MediaUpload = wp.blockEditor.MediaUpload;
	var MediaUploadCheck = wp.blockEditor.MediaUploadCheck;
	var __ = wp.i18n.__;

	var KHOA_META = '_nntm_nhac_nen';

	/*
	 * Danh sách loại nội dung được chọn nhạc do PHP truyền sang (xem
	 * nntm_nhac_cac_loai). Cùng một tệp JS chạy trên mọi màn hình sửa bài, nên
	 * phải tự kiểm loại của bài đang mở rồi mới hiện bảng.
	 */
	var LOAI_BAI = window.nntmNhacLoaiBai || [];

	function BangNhacNen() {
		var bai = useSelect( function ( select ) {
			var editor = select( 'core/editor' );
			var meta = editor.getEditedPostAttribute( 'meta' ) || {};

			return {
				loaiBai: editor.getCurrentPostType(),
				meta: meta,
				nhacId: parseInt( meta[ KHOA_META ] || 0, 10 ) || 0,
			};
		}, [] );

		var tep = useSelect(
			function ( select ) {
				return bai.nhacId ? select( 'core' ).getMedia( bai.nhacId ) : null;
			},
			[ bai.nhacId ]
		);

		var editPost = useDispatch( 'core/editor' ).editPost;

		if ( -1 === LOAI_BAI.indexOf( bai.loaiBai ) ) {
			return null;
		}

		function datNhac( tepChon ) {
			var id = tepChon && tepChon.id ? parseInt( tepChon.id, 10 ) : 0;
			var metaMoi = Object.assign( {}, bai.meta );

			metaMoi[ KHOA_META ] = id;
			editPost( { meta: metaMoi } );
		}

		var noiDung = [];

		if ( bai.nhacId && ! tep ) {
			noiDung.push( el( 'div', { key: 'cho', className: 'nntm-nhac-o__cho' }, el( Spinner ) ) );
		}

		if ( tep ) {
			var kieu = tep.mime_type || '';
			var nguon = tep.source_url || '';
			var ten =
				( tep.title && tep.title.rendered ) ||
				( tep.media_details && tep.media_details.file ? tep.media_details.file.split( '/' ).pop() : '#' + tep.id );

			if ( kieu && 0 !== kieu.indexOf( 'audio/' ) ) {
				noiDung.push(
					el(
						Notice,
						{ key: 'sai-kieu', status: 'error', isDismissible: false },
						__( 'Tệp đã chọn không phải tệp âm thanh.', 'nntm' )
					)
				);
			} else {
				noiDung.push(
					el(
						'div',
						{ key: 'dang-chon', className: 'nntm-nhac-o__dang-chon' },
						el( 'strong', null, ten ),
						nguon ? el( 'audio', { controls: true, preload: 'metadata', src: nguon } ) : null
					)
				);
			}
		} else if ( ! bai.nhacId ) {
			noiDung.push(
				el(
					'p',
					{ key: 'trong', className: 'components-base-control__help' },
					__( 'Chưa chọn nhạc nền.', 'nntm' )
				)
			);
		}

		noiDung.push(
			el(
				MediaUploadCheck,
				{ key: 'chon' },
				el( MediaUpload, {
					onSelect: datNhac,
					allowedTypes: [ 'audio' ],
					multiple: false,
					value: bai.nhacId || undefined,
					render: function ( doi_so ) {
						return el(
							Button,
							{ variant: 'secondary', onClick: doi_so.open },
							bai.nhacId ? __( 'Đổi nhạc nền', 'nntm' ) : __( 'Chọn nhạc nền', 'nntm' )
						);
					},
				} )
			)
		);

		if ( bai.nhacId ) {
			noiDung.push(
				el(
					Button,
					{
						key: 'go',
						isDestructive: true,
						variant: 'tertiary',
						onClick: function () {
							datNhac( null );
						},
					},
					__( 'Gỡ nhạc nền', 'nntm' )
				)
			);
		}

		noiDung.push(
			el(
				'p',
				{ key: 'giai-thich', className: 'components-base-control__help nntm-nhac-o__giai-thich' },
				__(
					'Bài có nhạc thì trang chi tiết hiện thanh nhạc dưới tiêu đề và tự phát. Bỏ trống là không có gì.',
					'nntm'
				)
			)
		);

		return el(
			PluginDocumentSettingPanel,
			{
				name: 'nntm-nhac-nen',
				title: __( 'Nhạc nền bài viết', 'nntm' ),
				className: 'nntm-nhac-o',
			},
			noiDung
		);
	}

	wp.plugins.registerPlugin( 'nntm-nhac-nen', {
		render: BangNhacNen,
		icon: 'format-audio',
	} );
} )( window.wp );
