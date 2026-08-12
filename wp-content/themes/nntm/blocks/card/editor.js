/**
 * Editor script cho block nntm/card — JavaScript thuần, không build.
 * Dùng biến toàn cục wp.blocks / wp.element / wp.blockEditor / wp.components
 * theo đúng quy ước dự án (chưa có bước build webpack/wp-scripts).
 */
( function ( wp ) {
	'use strict';

	var el = wp.element.createElement;
	var __ = wp.i18n.__;
	var registerBlockType = wp.blocks.registerBlockType;
	var useBlockProps = wp.blockEditor.useBlockProps;
	var InspectorControls = wp.blockEditor.InspectorControls;
	var PanelBody = wp.components.PanelBody;
	var SelectControl = wp.components.SelectControl;
	var ToggleControl = wp.components.ToggleControl;
	var TextControl = wp.components.TextControl;
	var ServerSideRender = wp.serverSideRender && wp.serverSideRender.default ? wp.serverSideRender.default : wp.serverSideRender;

	// Nhãn tiếng Việt cho từng variant — PHẢI giữ đúng "value" trùng tên
	// variant trong Figma (component set CARD), chỉ "label" là tiếng Việt
	// dễ hiểu cho người dùng không rành kỹ thuật.
	var VARIANT_OPTIONS = [
		{ label: __( 'Bài viết lớn (Article)', 'nntm' ), value: 'article' },
		{ label: __( 'Bài viết vừa (Small)', 'nntm' ), value: 'small' },
		{ label: __( 'Bài viết nhỏ (XS)', 'nntm' ), value: 'xs' },
		{ label: __( 'Thẻ Đại Sĩ (chủ đề)', 'nntm' ), value: 'dai-si' },
		{ label: __( 'Bài viết lớn — khi rê chuột (Hover)', 'nntm' ), value: 'article-hover' },
		{ label: __( 'Video', 'nntm' ), value: 'video' },
		{ label: __( 'Khóa Tu', 'nntm' ), value: 'khoa-tu' },
		{ label: __( 'Ấn phẩm / Sách (Books)', 'nntm' ), value: 'books' },
	];

	registerBlockType( 'nntm/card', {
		edit: function ( props ) {
			var attributes = props.attributes;
			var setAttributes = props.setAttributes;
			var blockProps = useBlockProps();

			return el(
				'div',
				blockProps,
				el(
					InspectorControls,
					{},
					el(
						PanelBody,
						{ title: __( 'Cài đặt thẻ', 'nntm' ), initialOpen: true },
						el( SelectControl, {
							label: __( 'Kiểu thẻ hiển thị', 'nntm' ),
							value: attributes.variant,
							options: VARIANT_OPTIONS,
							onChange: function ( value ) {
								setAttributes( { variant: value } );
							},
						} ),
						el( TextControl, {
							type: 'number',
							label: __( 'ID bài viết cần hiển thị', 'nntm' ),
							help: __( 'Vào trang sửa bài viết, xem số ID trong đường link (URL) của trình soạn thảo.', 'nntm' ),
							value: attributes.postId || '',
							onChange: function ( value ) {
								setAttributes( { postId: parseInt( value, 10 ) || 0 } );
							},
						} ),
						el( ToggleControl, {
							label: __( 'Hiện ngày cập nhật', 'nntm' ),
							checked: !! attributes.showDate,
							onChange: function ( value ) {
								setAttributes( { showDate: value } );
							},
						} ),
						el( ToggleControl, {
							label: __( 'Hiện đoạn mô tả ngắn', 'nntm' ),
							checked: !! attributes.showExcerpt,
							onChange: function ( value ) {
								setAttributes( { showExcerpt: value } );
							},
						} ),
						el( ToggleControl, {
							label: __( 'Hiện nhãn phân mục', 'nntm' ),
							checked: !! attributes.showCategory,
							onChange: function ( value ) {
								setAttributes( { showCategory: value } );
							},
						} )
					)
				),
				el( ServerSideRender, {
					block: 'nntm/card',
					attributes: attributes,
				} )
			);
		},
		save: function () {
			// Block động: PHP (render.php) tự vẽ lại nội dung mỗi lần tải trang.
			return null;
		},
	} );
} )( window.wp );
