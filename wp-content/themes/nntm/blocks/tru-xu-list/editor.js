/**
 * Editor script cho block nntm/tru-xu-list — JavaScript thuần, không build.
 * Dùng biến toàn cục wp.* theo đúng quy ước dự án (xem blocks/card-list/editor.js).
 * Khối này luôn lấy nội dung từ CPT nntm_abode (Trú Xứ) — không cho chọn
 * loại nội dung khác, vì đây là khối chuyên dụng cho SECTION 6 "Trú Xứ".
 */
( function ( wp ) {
	'use strict';

	var el = wp.element.createElement;
	var __ = wp.i18n.__;
	var registerBlockType = wp.blocks.registerBlockType;
	var useBlockProps = wp.blockEditor.useBlockProps;
	var InspectorControls = wp.blockEditor.InspectorControls;
	var RichText = wp.blockEditor.RichText;
	var PanelBody = wp.components.PanelBody;
	var SelectControl = wp.components.SelectControl;
	var RangeControl = wp.components.RangeControl;
	var ServerSideRender = wp.serverSideRender && wp.serverSideRender.default ? wp.serverSideRender.default : wp.serverSideRender;

	var ORDER_BY_OPTIONS = [
		{ label: __( 'Mới nhất trước', 'nntm' ), value: 'newest' },
		{ label: __( 'Cũ nhất trước', 'nntm' ), value: 'oldest' },
		{ label: __( 'Theo tên (A-Z)', 'nntm' ), value: 'title' },
	];
	var DISPLAY_MODE_OPTIONS = [
		{ label: __( 'Thẻ ảnh', 'nntm' ), value: 'cards' },
		{ label: __( 'Danh sách chữ theo trang Diệu Thượng', 'nntm' ), value: 'list' },
	];

	registerBlockType( 'nntm/tru-xu-list', {
		edit: function ( props ) {
			var attributes = props.attributes;
			var setAttributes = props.setAttributes;
			var blockProps = useBlockProps();

			var previewAttributes = Object.assign( {}, attributes, { heading: '' } ); // tranh hien tieu de 2 lan (RichText da hien o duoi)

			return el(
				'div',
				blockProps,
				el(
					InspectorControls,
					{},
					el(
						PanelBody,
						{ title: __( 'Cài đặt danh sách', 'nntm' ), initialOpen: true },
						el( RangeControl, {
							label: __( 'Hiển thị bao nhiêu Trú Xứ', 'nntm' ),
							value: attributes.postsPerPage,
							onChange: function ( value ) {
								setAttributes( { postsPerPage: value || 4 } );
							},
							min: 1,
							max: 12,
						} ),
						el( SelectControl, {
							label: __( 'Kiểu hiển thị', 'nntm' ),
							value: attributes.displayMode || 'cards',
							options: DISPLAY_MODE_OPTIONS,
							onChange: function ( value ) {
								setAttributes( { displayMode: value } );
							},
						} ),
						el( SelectControl, {
							label: __( 'Sắp xếp', 'nntm' ),
							value: attributes.orderBy,
							options: ORDER_BY_OPTIONS,
							onChange: function ( value ) {
								setAttributes( { orderBy: value } );
							},
						} )
					)
				),
				el( RichText, {
					tagName: 'h2',
					className: 'nntm-tru-xu-list__heading',
					value: attributes.heading,
					placeholder: __( 'Nhập tiêu đề mục…', 'nntm' ),
					onChange: function ( value ) {
						setAttributes( { heading: value } );
					},
				} ),
				el( ServerSideRender, {
					block: 'nntm/tru-xu-list',
					attributes: previewAttributes,
				} )
			);
		},
		save: function () {
			// Block động: PHP (render.php) tự chạy lại WP_Query mỗi lần tải trang.
			return null;
		},
	} );
} )( window.wp );
