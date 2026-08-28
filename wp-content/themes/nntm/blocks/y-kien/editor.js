( function ( wp ) {
	'use strict';

	var el = wp.element.createElement;
	var __ = wp.i18n.__;
	var registerBlockType = wp.blocks.registerBlockType;
	var useBlockProps = wp.blockEditor.useBlockProps;
	var InspectorControls = wp.blockEditor.InspectorControls;
	var PanelBody = wp.components.PanelBody;
	var TextControl = wp.components.TextControl;
	var TextareaControl = wp.components.TextareaControl;
	var SelectControl = wp.components.SelectControl;
	var ServerSideRender = wp.serverSideRender && wp.serverSideRender.default
		? wp.serverSideRender.default
		: wp.serverSideRender;

	var NEN_OPTIONS = [
		{ label: __( 'Nền tối', 'nntm' ), value: 'toi' },
		{ label: __( 'Nền kem', 'nntm' ), value: 'kem' },
		{ label: __( 'Không nền', 'nntm' ), value: 'trong' },
	];

	registerBlockType( 'nntm/y-kien', {
		edit: function ( props ) {
			var attributes = props.attributes;
			var setAttributes = props.setAttributes;
			var blockProps = useBlockProps();

			function dat( ten ) {
				return function ( giaTri ) {
					var thayDoi = {};
					thayDoi[ ten ] = giaTri;
					setAttributes( thayDoi );
				};
			}

			return el(
				'div',
				blockProps,
				el(
					InspectorControls,
					null,
					el(
						PanelBody,
						{ title: __( 'Nội dung lời mời', 'nntm' ), initialOpen: true },
						el( TextControl, {
							label: __( 'Chữ phía trước', 'nntm' ),
							value: attributes.textTruoc || '',
							onChange: dat( 'textTruoc' ),
							__nextHasNoMarginBottom: true,
						} ),
						el( TextControl, {
							label: __( 'Nhãn trên nút', 'nntm' ),
							help: __( 'Phần nổi lên thành nút bấm giữa câu.', 'nntm' ),
							value: attributes.nhan || '',
							onChange: dat( 'nhan' ),
							__nextHasNoMarginBottom: true,
						} ),
						el( TextControl, {
							label: __( 'Chữ phía sau', 'nntm' ),
							value: attributes.textSau || '',
							onChange: dat( 'textSau' ),
							__nextHasNoMarginBottom: true,
						} ),
						el( TextControl, {
							label: __( 'Đường dẫn', 'nntm' ),
							help: __( 'Để trống thì tự trỏ về trang /y-kien/.', 'nntm' ),
							type: 'url',
							placeholder: 'https://…/y-kien/',
							value: attributes.url || '',
							onChange: dat( 'url' ),
							__nextHasNoMarginBottom: true,
						} ),
						el( TextareaControl, {
							label: __( 'Dòng phụ bên dưới', 'nntm' ),
							help: __( 'Để trống thì không hiện dòng này.', 'nntm' ),
							value: attributes.moTa || '',
							onChange: dat( 'moTa' ),
							__nextHasNoMarginBottom: true,
						} )
					),
					el(
						PanelBody,
						{ title: __( 'Nền', 'nntm' ), initialOpen: false },
						el( SelectControl, {
							label: __( 'Kiểu nền', 'nntm' ),
							value: attributes.nen || 'toi',
							options: NEN_OPTIONS,
							onChange: dat( 'nen' ),
							__nextHasNoMarginBottom: true,
						} )
					)
				),
				el( ServerSideRender, {
					block: 'nntm/y-kien',
					attributes: attributes,
				} )
			);
		},

		save: function () {
			return null;
		},
	} );
} )( window.wp );
