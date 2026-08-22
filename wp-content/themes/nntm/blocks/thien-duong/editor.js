 
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
	var RangeControl = wp.components.RangeControl;
	var Button = wp.components.Button;
	var ServerSideRender = wp.serverSideRender && wp.serverSideRender.default ? wp.serverSideRender.default : wp.serverSideRender;

	var ORDER_BY_OPTIONS = [
		{ label: __( 'Mới nhất trước', 'nntm' ), value: 'newest' },
		{ label: __( 'Cũ nhất trước', 'nntm' ), value: 'oldest' },
		{ label: __( 'Theo tên (A-Z)', 'nntm' ), value: 'title' },
	];

	registerBlockType( 'nntm/thien-duong', {
		edit: function ( props ) {
			var attributes = props.attributes;
			var setAttributes = props.setAttributes;
			var blockProps = useBlockProps();

			function onSelectCover( media ) {
				setAttributes( {
					coverImageId: media && media.id ? media.id : 0,
					coverImageUrl: media && media.url ? media.url : '',
				} );
			}

			function onRemoveCover() {
				setAttributes( { coverImageId: 0, coverImageUrl: '' } );
			}


			var previewAttributes = Object.assign( {}, attributes, { heading: '', subheading: '' } );

			return el(
				'div',
				blockProps,
				el(
					InspectorControls,
					{},
					el(
						PanelBody,
						{ title: __( 'Ảnh bìa (453×416)', 'nntm' ), initialOpen: true },
						el(
							MediaUploadCheck,
							{},
							el( MediaUpload, {
								onSelect: onSelectCover,
								allowedTypes: [ 'image' ],
								value: attributes.coverImageId,
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
											attributes.coverImageUrl
												? __( 'Đổi ảnh khác', 'nntm' )
												: __( 'Chọn ảnh bìa', 'nntm' )
										),
										attributes.coverImageUrl
											? el(
													Button,
													{
														onClick: onRemoveCover,
														variant: 'link',
														isDestructive: true,
													},
													__( 'Gỡ ảnh', 'nntm' )
											  )
											: null
									);
								},
							} )
						)
					),
					el(
						PanelBody,
						{ title: __( 'Danh sách bài', 'nntm' ), initialOpen: true },
						el(
							'p',
							{ className: 'components-base-control__help' },
							__( 'Nguồn cố định: Nhạc Thiền (nntm_zen_track). Mỗi post dùng Tiêu đề làm tên bài, Ảnh đại diện làm hình và _nntm_track_audio làm file nhạc. Bài chưa có audio sẽ không xuất hiện.', 'nntm' )
						),
						el( RangeControl, {
							label: __( 'Số bài tối đa', 'nntm' ),
							value: attributes.tracksPerPage,
							onChange: function ( value ) {
								setAttributes( { tracksPerPage: value || 20 } );
							},
							min: 1,
							max: 50,
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
					className: 'nntm-thien-duong__heading',
					value: attributes.heading,
					placeholder: __( 'Nhập tiêu đề…', 'nntm' ),
					onChange: function ( value ) {
						setAttributes( { heading: value } );
					},
				} ),
				el( RichText, {
					tagName: 'p',
					className: 'nntm-thien-duong__subheading',
					value: attributes.subheading,
					placeholder: __( 'Nhập mô tả phụ…', 'nntm' ),
					onChange: function ( value ) {
						setAttributes( { subheading: value } );
					},
				} ),
				el( ServerSideRender, {
					block: 'nntm/thien-duong',
					attributes: previewAttributes,
				} )
			);
		},
		save: function () {

			return null;
		},
	} );
} )( window.wp );
