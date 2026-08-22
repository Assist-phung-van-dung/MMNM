 
( function ( wp ) {
	'use strict';

	var el = wp.element.createElement;
	var useState = wp.element.useState;
	var useEffect = wp.element.useEffect;
	var __ = wp.i18n.__;
	var registerBlockType = wp.blocks.registerBlockType;
	var useBlockProps = wp.blockEditor.useBlockProps;
	var InspectorControls = wp.blockEditor.InspectorControls;
	var MediaUpload = wp.blockEditor.MediaUpload;
	var MediaUploadCheck = wp.blockEditor.MediaUploadCheck;
	var PanelBody = wp.components.PanelBody;
	var Button = wp.components.Button;
	var TextControl = wp.components.TextControl;
	var SelectControl = wp.components.SelectControl;
	var ServerSideRender = wp.serverSideRender && wp.serverSideRender.default ? wp.serverSideRender.default : wp.serverSideRender;
	var apiFetch = wp.apiFetch;

	registerBlockType( 'nntm/engineering-earth', {
		edit: function ( props ) {
			var attributes = props.attributes;
			var setAttributes = props.setAttributes;
			var blockProps = useBlockProps();
			var videoPostsState = useState( [] );
			var videoPosts = videoPostsState[ 0 ];
			var setVideoPosts = videoPostsState[ 1 ];

			useEffect( function () {
				var isCurrent = true;
				apiFetch( { path: '/wp/v2/nntm_video?per_page=100&orderby=date&order=desc&_fields=id,title' } )
					.then( function ( posts ) {
						if ( ! isCurrent ) {
							return;
						}
						setVideoPosts( posts || [] );
					} )
					.catch( function () {
						if ( isCurrent ) {
							setVideoPosts( [] );
						}
					} );

				return function () {
					isCurrent = false;
				};
			}, [] );

			var videoPostOptions = [ { label: __( '— Không liên kết bài viết —', 'nntm' ), value: 0 } ].concat(
				videoPosts.map( function ( post ) {
					var box = document.createElement( 'textarea' );
					box.innerHTML = ( post.title && post.title.rendered ) || '';
					return { label: box.value || __( '(bài không có tiêu đề)', 'nntm' ), value: post.id };
				} )
			);

			return el(
				'div',
				blockProps,
				el(
					InspectorControls,
					{},
					el(
						PanelBody,
						{ title: __( 'Chữ hiển thị', 'nntm' ), initialOpen: true },
						el( TextControl, {
							label: __( 'Tiêu đề lớn', 'nntm' ),
							value: attributes.heading,
							onChange: function ( value ) {
								setAttributes( { heading: value } );
							},
						} ),
						el( TextControl, {
							label: __( 'Dòng phụ dưới tiêu đề', 'nntm' ),
							value: attributes.subheading,
							onChange: function ( value ) {
								setAttributes( { subheading: value } );
							},
						} ),
						el( TextControl, {
							label: __( 'Tiêu đề trên dải đen', 'nntm' ),
							value: attributes.bandTitle,
							onChange: function ( value ) {
								setAttributes( { bandTitle: value } );
							},
						} ),
						el( TextControl, {
							label: __( 'Dòng phụ trên dải đen', 'nntm' ),
							value: attributes.bandSubtitle,
							onChange: function ( value ) {
								setAttributes( { bandSubtitle: value } );
							},
						} ),
					),
					el(
						PanelBody,
						{ title: __( 'Video (D1 — dán link YouTube)', 'nntm' ), initialOpen: true },
						el(
							'p',
							{ className: 'components-base-control__help' },
							__( 'Anh Úy chốt: dán link/ID YouTube trực tiếp, KHÔNG dùng YouTube Data API. Ảnh giữ chỗ lấy trực tiếp từ YouTube, không cần tải ảnh lên.', 'nntm' )
						),
						el( TextControl, {
							label: __( 'Video chính (khung media lớn)', 'nntm' ),
							help: __( 'Dán link dạng youtube.com/watch?v=…, youtu.be/… hoặc chỉ ID video. Chưa dán thì khung lớn hiện ảnh dự phòng ở panel bên dưới.', 'nntm' ),
							value: attributes.mainVideoUrl || '',
							onChange: function ( value ) {
								setAttributes( { mainVideoUrl: value } );
							},
						} ),
						el( TextControl, {
							label: __( 'Video nền (thẻ nhỏ tràn mép, nhấp để đổi làm video chính)', 'nntm' ),
							help: __( 'Cùng định dạng như trên. Video chỉ phát nền; nhấp vào thẻ sẽ mở bài viết video bên dưới.', 'nntm' ),
							value: attributes.bgVideoUrl || '',
							onChange: function ( value ) {
								setAttributes( { bgVideoUrl: value } );
							},
						} ),
						el( SelectControl, {
							label: __( 'Bài viết mở khi nhấp', 'nntm' ),
							help: __( 'Cả khung lớn lẫn thẻ nhỏ sẽ dẫn đến bài video này.', 'nntm' ),
							value: attributes.videoId || 0,
							options: videoPostOptions,
							onChange: function ( value ) {
								setAttributes( { videoId: parseInt( value, 10 ) || 0 } );
							},
						} )
					),
					el(
						PanelBody,
						{ title: __( 'Ảnh dự phòng khung lớn', 'nntm' ), initialOpen: false },
						el(
							'p',
							{ className: 'components-base-control__help' },
							__( 'Chỉ hiện khi CHƯA dán "Video chính" ở panel trên — tránh khung media lớn trống trơn trước khi có link YouTube.', 'nntm' )
						),
						el(
							MediaUploadCheck,
							{},
							el( MediaUpload, {
								allowedTypes: [ 'image' ],
								value: attributes.mainImageId,
								onSelect: function ( media ) {
									setAttributes( {
										mainImageId: media.id,
										mainImageUrl: media.url,
										mainImageAlt: media.alt || attributes.mainImageAlt || '',
									} );
								},
								render: function ( o ) {
									return el(
										Button,
										{ variant: 'secondary', onClick: o.open },
										attributes.mainImageId
											? __( 'Đổi ảnh', 'nntm' )
											: __( 'Chọn ảnh từ Thư viện', 'nntm' )
									);
								},
							} )
						),
						attributes.mainImageId
							? el(
									Button,
									{
										variant: 'tertiary',
										isDestructive: true,
										onClick: function () {
											setAttributes( { mainImageId: 0, mainImageUrl: '', mainImageAlt: '' } );
										},
									},
									__( 'Bỏ ảnh', 'nntm' )
							  )
							: null,
						el( TextControl, {
							label: __( 'Chữ thay ảnh (cho người khiếm thị)', 'nntm' ),
							value: attributes.mainImageAlt || '',
							onChange: function ( value ) {
								setAttributes( { mainImageAlt: value } );
							},
						} ),
						el( TextControl, {
							label: __( 'Hoặc dán đường dẫn ảnh ngoài', 'nntm' ),
							help: __( 'Chỉ dùng khi ảnh không nằm trong Thư viện. Chọn ảnh ở trên thì bỏ qua ô này.', 'nntm' ),
							type: 'url',
							placeholder: 'https://…',
							value: attributes.mainImageUrl || '',
							onChange: function ( value ) {
								setAttributes( { mainImageUrl: value } );
							},
						} )
					)
				),
				el( ServerSideRender, {
					block: 'nntm/engineering-earth',
					attributes: attributes,
				} )
			);
		},
		save: function () {

			return null;
		},
	} );
} )( window.wp );
