
( function ( wp ) {
	'use strict';

	var el = wp.element.createElement;
	var __ = wp.i18n.__;
	var registerBlockType = wp.blocks.registerBlockType;
	var useBlockProps = wp.blockEditor.useBlockProps;
	var InspectorControls = wp.blockEditor.InspectorControls;
	var PanelBody = wp.components.PanelBody;
	var TextControl = wp.components.TextControl;
	var ToggleControl = wp.components.ToggleControl;
	var RangeControl = wp.components.RangeControl;
	var SelectControl = wp.components.SelectControl;
	var ServerSideRender = wp.serverSideRender && wp.serverSideRender.default ? wp.serverSideRender.default : wp.serverSideRender;

	registerBlockType( 'nntm/cong-tu', {
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
						{ title: __( 'Chương trình', 'nntm' ), initialOpen: true },
						el( TextControl, {
							label: __( 'ID chương trình (0 = tự lấy chương trình đang mở)', 'nntm' ),
							type: 'number',
							value: attributes.programId,
							onChange: function ( value ) {
								setAttributes( { programId: parseInt( value, 10 ) || 0 } );
							},
						} )
					),
					el(
						PanelBody,
						{ title: __( 'Thống Kê Của Đạo Tràng', 'nntm' ), initialOpen: true },
						el( ToggleControl, {
							label: __( 'Hiện khối Thống Kê', 'nntm' ),
							checked: !! attributes.showThongKe,
							onChange: function ( value ) {
								setAttributes( { showThongKe: value } );
							},
						} ),
						el( TextControl, {
							label: __( 'Tiêu đề khối Thống Kê', 'nntm' ),
							value: attributes.heading,
							onChange: function ( value ) {
								setAttributes( { heading: value } );
							},
						} )
					),
					el(
						PanelBody,
						{ title: __( 'Bảng Xếp Hạng', 'nntm' ), initialOpen: true },
						el( ToggleControl, {
							label: __( 'Hiện Bảng Xếp Hạng', 'nntm' ),
							checked: !! attributes.showBxh,
							onChange: function ( value ) {
								setAttributes( { showBxh: value } );
							},
						} ),
						el( TextControl, {
							label: __( 'Tiêu đề Bảng Xếp Hạng', 'nntm' ),
							value: attributes.bxhHeading,
							onChange: function ( value ) {
								setAttributes( { bxhHeading: value } );
							},
						} ),
						el( RangeControl, {
							label: __( 'Số dòng tối đa hiển thị', 'nntm' ),
							value: attributes.bxhLimit || 50,
							min: 5,
							max: 200,
							onChange: function ( value ) {
								setAttributes( { bxhLimit: value || 50 } );
							},
						} )
					),
					el(
						PanelBody,
						{ title: __( 'Nền', 'nntm' ), initialOpen: false },
						el( SelectControl, {
							label: __( 'Màu nền dải', 'nntm' ),
							value: attributes.background || 'kem',
							options: [
								{ label: __( 'Vàng', 'nntm' ), value: 'vang' },
								{ label: __( 'Kem', 'nntm' ), value: 'kem' },
								{ label: __( 'Không nền (trong suốt)', 'nntm' ), value: 'none' },
							],
							onChange: function ( value ) {
								setAttributes( { background: value } );
							},
						} )
					)
				),
				el( ServerSideRender, {
					block: 'nntm/cong-tu',
					attributes: attributes,
				} )
			);
		},
		save: function () {

			return null;
		},
	} );
} )( window.wp );
