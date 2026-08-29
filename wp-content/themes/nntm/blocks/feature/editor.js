 
( function ( wp ) {
	'use strict';

	var el = wp.element.createElement;
	var Fragment = wp.element.Fragment;
	var __ = wp.i18n.__;
	var registerBlockType = wp.blocks.registerBlockType;
	var useBlockProps = wp.blockEditor.useBlockProps;
	var InspectorControls = wp.blockEditor.InspectorControls;
	var RichText = wp.blockEditor.RichText;
	var MediaUpload = wp.blockEditor.MediaUpload;
	var MediaUploadCheck = wp.blockEditor.MediaUploadCheck;
	var PanelBody = wp.components.PanelBody;
	var SelectControl = wp.components.SelectControl;
	var TextControl = wp.components.TextControl;
	var Button = wp.components.Button;

	var MEDIA_POSITION_OPTIONS = [
		{ label: __( 'Ảnh bên phải (mặc định)', 'nntm' ), value: 'right' },
		{ label: __( 'Ảnh bên trái', 'nntm' ), value: 'left' },
	];

	registerBlockType( 'nntm/feature', {
		edit: function ( props ) {
			var attributes = props.attributes;
			var setAttributes = props.setAttributes;
			var blockProps = useBlockProps( {
				className: 'nntm-feature nntm-feature--media-' + attributes.mediaPosition,
			} );

			function onSelectImage( media ) {
				setAttributes( {
					imageId: media && media.id ? media.id : 0,
					imageUrl: media && media.url ? media.url : '',
				} );
			}

			function onRemoveImage() {
				setAttributes( { imageId: 0, imageUrl: '' } );
			}

			return el(
				'div',
				blockProps,
				el(
					InspectorControls,
					{},
					el(
						PanelBody,
						{ title: __( 'Ảnh minh họa', 'nntm' ), initialOpen: true },
						el(
							MediaUploadCheck,
							{},
							el( MediaUpload, {
								onSelect: onSelectImage,
								allowedTypes: [ 'image' ],
								value: attributes.imageId,
								render: function ( mediaProps ) {
									return el(
										Fragment,
										{},
										el(
											Button,
											{
												onClick: mediaProps.open,
												variant: 'secondary',
											},
											attributes.imageUrl
												? __( 'Đổi ảnh khác', 'nntm' )
												: __( 'Chọn ảnh', 'nntm' )
										),
										attributes.imageUrl
											? el(
													Button,
													{
														onClick: onRemoveImage,
														variant: 'link',
														isDestructive: true,
													},
													__( 'Gỡ ảnh', 'nntm' )
											  )
											: null
									);
								},
							} )
						),
						el( TextControl, {
							label: __( 'Mô tả ảnh (alt)', 'nntm' ),
							help: __( 'Mô tả ngắn nội dung ảnh cho người khiếm thị. Để trống nếu ảnh chỉ để trang trí.', 'nntm' ),
							value: attributes.imageAlt,
							onChange: function ( value ) {
								setAttributes( { imageAlt: value } );
							},
						} ),
						el( SelectControl, {
							label: __( 'Vị trí ảnh', 'nntm' ),
							value: attributes.mediaPosition,
							options: MEDIA_POSITION_OPTIONS,
							onChange: function ( value ) {
								setAttributes( { mediaPosition: value } );
							},
						} )
					)
				),
				/*
				 * .nntm-container la lop bat buoc: render.php bao __content trong no,
				 * va feature/style.css dat max-width 1324px + flex + gap len chinh no.
				 * Thieu lop nay thi trong admin khoi noi dung trai het be ngang, khac
				 * han ngoai trang.
				 */
				el(
					'div',
					{ className: 'nntm-container' },
					el(
						'div',
						{ className: 'nntm-feature__content' },
						el(
							'div',
							{ className: 'nntm-feature__text' },
							el(
								'div',
								{ className: 'nntm-feature__text-inner' },
								el( RichText, {
									tagName: 'span',
									className: 'nntm-feature__eyebrow',
									value: attributes.eyebrow,
									placeholder: __( 'Cập nhật 15. 06. 2026', 'nntm' ),
									allowedFormats: [],
									onChange: function ( value ) {
										setAttributes( { eyebrow: value } );
									},
								} ),
								el( RichText, {
									tagName: 'h2',
									className: 'nntm-feature__heading',
									value: attributes.heading,
									placeholder: __( 'Nhập tiêu đề…', 'nntm' ),
									onChange: function ( value ) {
										setAttributes( { heading: value } );
									},
								} ),
								el( RichText, {
									tagName: 'div',
									className: 'nntm-feature__body',
									value: attributes.content,
									placeholder: __( 'Nhập nội dung…', 'nntm' ),
									multiline: 'p',
									onChange: function ( value ) {
										setAttributes( { content: value } );
									},
								} )
							)
						),
						el(
							'div',
							{ className: 'nntm-feature__media' },
							attributes.imageUrl
								? el( 'img', {
										className: 'nntm-feature__media-img',
										src: attributes.imageUrl,
										alt: attributes.imageAlt,
								  } )
								: el(
										'div',
										{ className: 'nntm-feature__media-placeholder' },
										__( 'Chưa chọn ảnh — mở bảng cài đặt bên phải để chọn.', 'nntm' )
								  )
							)
						)
				)
			);
		},
		save: function () {

			return null;
		},
	} );
} )( window.wp );
