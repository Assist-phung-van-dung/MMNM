
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
	var Button = wp.components.Button;
	var TextControl = wp.components.TextControl;
	var SelectControl = wp.components.SelectControl;
	var RangeControl = wp.components.RangeControl;



	var ACCESS_OPTIONS = [
		{ label: __( 'Ai cũng xem được', 'nntm' ), value: 'public' },
		{ label: __( 'Cần đăng nhập', 'nntm' ), value: 'login' },
		{ label: __( 'Cần cấp Đại Sĩ', 'nntm' ), value: 'dai_si' },
		{ label: __( 'Cần cấp Kim Cương', 'nntm' ), value: 'kim_cuong' },
	];

	function emptyCard() {
		return {
			imageId: 0,
			imageUrl: '',
			imageAlt: '',
			title: '',
			ctaLabel: __( 'Mời vào', 'nntm' ),
			targetUrl: '',
			requiredAccess: 'login',
		};
	}

	registerBlockType( 'nntm/rank-card', {
		edit: function ( props ) {
			var attributes = props.attributes;
			var setAttributes = props.setAttributes;
			var blockProps = useBlockProps( {
				className: 'nntm-rank-card',
				style: {
					minHeight: ( attributes.minHeight || 690 ) + 'px',
					backgroundImage: attributes.bgImageUrl && 'video' !== ( attributes.bgMediaType || 'image' ) ? 'url(' + attributes.bgImageUrl + ')' : undefined,
				},
			} );
			var cards = Array.isArray( attributes.cards ) ? attributes.cards : [];

			function onSelectBgImage( media ) {
				var mediaType = media && ( 'video' === media.type || ( media.mime && 0 === media.mime.indexOf( 'video/' ) ) ) ? 'video' : 'image';
				setAttributes( {
					bgMediaType: mediaType,
					bgImageId: media && media.id ? media.id : 0,
					bgImageUrl: media && media.url ? media.url : '',
					bgImageAlt: 'image' === mediaType && media && media.alt ? media.alt : attributes.bgImageAlt,
				} );
			}

			function onRemoveBgImage() {
				setAttributes( { bgMediaType: 'image', bgImageId: 0, bgImageUrl: '' } );
			}

			function updateCard( index, patch ) {
				var next = cards.slice();
				next[ index ] = Object.assign( {}, next[ index ], patch );
				setAttributes( { cards: next } );
			}

			function addCard() {
				setAttributes( { cards: cards.concat( [ emptyCard() ] ) } );
			}

			function removeCard( index ) {
				var next = cards.slice();
				next.splice( index, 1 );
				setAttributes( { cards: next } );
			}

			return el(
				'div',
				blockProps,
				el(
					InspectorControls,
					{},
					el(
						PanelBody,
						{ title: __( 'Nền hero: Ảnh / Video', 'nntm' ), initialOpen: true },
						el(
							MediaUploadCheck,
							{},
							el( MediaUpload, {
								onSelect: onSelectBgImage,
								allowedTypes: [ 'image', 'video' ],
								value: attributes.bgImageId,
								render: function ( mediaProps ) {
									return el(
										Fragment,
										{},
										el(
											Button,
											{ onClick: mediaProps.open, variant: 'secondary' },
											attributes.bgImageUrl ? __( 'Đổi ảnh / video', 'nntm' ) : __( 'Chọn ảnh hoặc video nền', 'nntm' )
										),
										attributes.bgImageUrl
											? el(
													Button,
													{
														onClick: onRemoveBgImage,
														variant: 'link',
														isDestructive: true,
													},
													__( 'Gỡ media', 'nntm' )
											  )
											: null
									);
								},
							} )
						),
						el( TextControl, {
							label: __( 'Mô tả nền (alt)', 'nntm' ),
							help: __( 'Dùng cho ảnh. Video nền được phát không tiếng và lặp liên tục.', 'nntm' ),
							value: attributes.bgImageAlt,
							onChange: function ( value ) {
								setAttributes( { bgImageAlt: value } );
							},
						} ),
						el( RangeControl, {
							label: __( 'Chiều cao tối thiểu (px)', 'nntm' ),
							value: attributes.minHeight || 690,
							min: 400,
							max: 900,
							onChange: function ( value ) {
								setAttributes( { minHeight: value || 690 } );
							},
						} )
					),
					el(
						PanelBody,
						{ title: __( 'Thẻ cấp bậc', 'nntm' ), initialOpen: true },
						el(
							'p',
							{ className: 'components-base-control__help' },
							__( 'Thêm, sửa, xoá từng thẻ cấp bậc hiện ngang hàng trong hero.', 'nntm' )
						),
						cards.map( function ( card, index ) {
							return el(
								PanelBody,
								{
									key: 'card-' + index,
									title: card.title || sprintfCardTitle( index ),
									initialOpen: 0 === index,
								},
								el(
									MediaUploadCheck,
									{},
									el( MediaUpload, {
										onSelect: function ( media ) {
											updateCard( index, {
												imageId: media && media.id ? media.id : 0,
												imageUrl: media && media.url ? media.url : '',
												imageAlt: media && media.alt ? media.alt : card.imageAlt,
											} );
										},
										allowedTypes: [ 'image' ],
										value: card.imageId,
										render: function ( mediaProps ) {
											return el(
												Fragment,
												{},
												el(
													Button,
													{ onClick: mediaProps.open, variant: 'secondary' },
													card.imageUrl ? __( 'Đổi ảnh khác', 'nntm' ) : __( 'Chọn ảnh thẻ', 'nntm' )
												),
												card.imageUrl
													? el(
															Button,
															{
																onClick: function () {
																	updateCard( index, { imageId: 0, imageUrl: '' } );
																},
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
								card.imageUrl
									? el( 'img', {
											src: card.imageUrl,
											alt: '',
											style: { maxWidth: '100%', margin: '8px 0' },
									  } )
									: null,
								el( TextControl, {
									label: __( 'Mô tả ảnh (alt)', 'nntm' ),
									value: card.imageAlt,
									onChange: function ( value ) {
										updateCard( index, { imageAlt: value } );
									},
								} ),
								el( TextControl, {
									label: __( 'Tiêu đề thẻ', 'nntm' ),
									value: card.title,
									onChange: function ( value ) {
										updateCard( index, { title: value } );
									},
								} ),
								el( TextControl, {
									label: __( 'Nhãn nút', 'nntm' ),
									value: card.ctaLabel,
									onChange: function ( value ) {
										updateCard( index, { ctaLabel: value } );
									},
								} ),
								el( TextControl, {
									label: __( 'Trang đích (khi đã đủ quyền)', 'nntm' ),
									type: 'url',
									value: card.targetUrl,
									onChange: function ( value ) {
										updateCard( index, { targetUrl: value } );
									},
								} ),
								el( SelectControl, {
									label: __( 'Quyền truy cập', 'nntm' ),
									value: card.requiredAccess || 'login',
									options: ACCESS_OPTIONS,
									onChange: function ( value ) {
										updateCard( index, { requiredAccess: value } );
									},
								} ),
								el(
									Button,
									{
										variant: 'secondary',
										isDestructive: true,
										style: { marginTop: '8px' },
										onClick: function () {
											removeCard( index );
										},
									},
									__( 'Xoá thẻ này', 'nntm' )
								)
							);
						} ),
						el(
							Button,
							{ variant: 'primary', onClick: addCard, style: { marginTop: '8px' } },
							__( '+ Thêm thẻ', 'nntm' )
						)
					)
				),
				attributes.bgImageUrl && 'video' === ( attributes.bgMediaType || 'image' )
					? el( 'video', {
						className: 'nntm-rank-card__bg-video',
						src: attributes.bgImageUrl,
						autoPlay: true,
						muted: true,
						loop: true,
						playsInline: true,
					} )
					: null,
				el(
					'div',
					{ className: 'nntm-rank-card__overlay' },
					el( RichText, {
						tagName: 'h2',
						className: 'nntm-rank-card__heading',
						value: attributes.heading,
						placeholder: __( 'Nhập tiêu đề…', 'nntm' ),
						onChange: function ( value ) {
							setAttributes( { heading: value } );
						},
					} ),
					el(
						'div',
						{ className: 'nntm-rank-card__row' },
						cards.length
							? cards.map( function ( card, index ) {
									return el(
										'div',
										{ className: 'nntm-rank-card__card', key: 'preview-' + index },
										card.imageUrl
											? el( 'img', {
													className: 'nntm-rank-card__card-img',
													src: card.imageUrl,
													alt: '',
											  } )
											: el( 'div', { className: 'nntm-rank-card__card-img nntm-rank-card__card-img--placeholder' } ),
										el(
											'p',
											{ className: 'nntm-rank-card__card-title' },
											card.title || __( '(chưa đặt tiêu đề)', 'nntm' )
										),
										el(
											'span',
											{ className: 'nntm-rank-card__cta' },
											( card.ctaLabel || __( 'Mời vào', 'nntm' ) ) + ' →'
										)
									);
							  } )
							: el(
									'p',
									{ className: 'nntm-rank-card__empty-notice' },
									__( 'Chưa có thẻ nào — mở bảng cài đặt bên phải để thêm.', 'nntm' )
							  )
					)
				)
			);
		},
		save: function () {

			return null;
		},
	} );

	function sprintfCardTitle( index ) {
		return __( 'Thẻ', 'nntm' ) + ' ' + ( index + 1 );
	}
} )( window.wp );
