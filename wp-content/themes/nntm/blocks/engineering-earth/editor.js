/**
 * Editor script cho block nntm/engineering-earth — JavaScript thuần,
 * không build. Cùng phong cách các block khác của theme.
 *
 * MỌI thuộc tính khai trong block.json đều phải có ô điều khiển ở đây —
 * thiếu một ô là thuộc tính đó không có cách nào sửa từ giao diện, ban
 * quản trị phải gọi lập trình viên. Đây là ràng buộc mạnh nhất của dự án
 * (docs/04-kien-truc.md mục 0.3).
 */
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
	var TextareaControl = wp.components.TextareaControl;
	var SelectControl = wp.components.SelectControl;
	var apiFetch = wp.apiFetch;
	var ServerSideRender = wp.serverSideRender && wp.serverSideRender.default ? wp.serverSideRender.default : wp.serverSideRender;

	// REST trả tiêu đề dạng HTML đã mã hoá. Giải mã để danh sách thả xuống
	// hiện đúng chữ như trên trang.
	function plainTitle( post ) {
		var raw = ( post && post.title && post.title.rendered ) || '';
		var box = document.createElement( 'textarea' );
		box.innerHTML = raw;
		return box.value.trim() || __( '(bài không có tiêu đề)', 'nntm' );
	}

	registerBlockType( 'nntm/engineering-earth', {
		edit: function ( props ) {
			var attributes = props.attributes;
			var setAttributes = props.setAttributes;
			var blockProps = useBlockProps();

			var videoState = useState( [] );
			var availableVideos = videoState[ 0 ];
			var setAvailableVideos = videoState[ 1 ];

			// Danh sách video để chọn. Lấy CPT nntm_video; chưa có bài nào
			// thì danh sách rỗng và ô chỉ còn "Không hiện thẻ video".
			useEffect( function () {
				var isCurrent = true;

				apiFetch( { path: '/wp/v2/nntm_video?per_page=100&orderby=date&order=desc&_fields=id,title' } )
					.then( function ( posts ) {
						if ( isCurrent ) {
							setAvailableVideos(
								( posts || [] ).map( function ( one ) {
									return { id: one.id, title: plainTitle( one ) };
								} )
							);
						}
					} )
					.catch( function () {
						if ( isCurrent ) {
							setAvailableVideos( [] );
						}
					} );

				return function () {
					isCurrent = false;
				};
			}, [] );

			var videoOptions = [ { label: __( '— Không hiện thẻ video —', 'nntm' ), value: 0 } ].concat(
				availableVideos.map( function ( one ) {
					return { label: one.title, value: one.id };
				} )
			);
			// Video đã chọn có thể đã bị xoá hoặc chuyển nháp — vẫn phải hiện
			// trong ô, nếu không ban quản trị mở ra tưởng mình chưa chọn gì.
			var daBiet = availableVideos.some( function ( one ) {
				return one.id === attributes.videoId;
			} );
			if ( attributes.videoId && ! daBiet ) {
				videoOptions.push( {
					label: __( 'Video số ', 'nntm' ) + attributes.videoId + __( ' (không còn trong danh sách)', 'nntm' ),
					value: attributes.videoId,
				} );
			}

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
						el( TextareaControl, {
							label: __( 'Đoạn chú thích dưới dải', 'nntm' ),
							help: __( 'Để trống thì không hiện đoạn này.', 'nntm' ),
							value: attributes.caption,
							onChange: function ( value ) {
								setAttributes( { caption: value } );
							},
						} )
					),
					el(
						PanelBody,
						{ title: __( 'Ảnh lớn trên dải đen', 'nntm' ), initialOpen: true },
						el(
							MediaUploadCheck,
							{},
							el( MediaUpload, {
								allowedTypes: [ 'image' ],
								value: attributes.imageId,
								onSelect: function ( media ) {
									setAttributes( {
										imageId: media.id,
										imageUrl: media.url,
										// Lấy sẵn chữ thay ảnh đã nhập trong Thư viện;
										// chưa có thì để ban quản trị nhập ở ô dưới.
										imageAlt: media.alt || attributes.imageAlt || '',
									} );
								},
								render: function ( o ) {
									return el(
										Button,
										{ variant: 'secondary', onClick: o.open },
										attributes.imageId
											? __( 'Đổi ảnh', 'nntm' )
											: __( 'Chọn ảnh từ Thư viện', 'nntm' )
									);
								},
							} )
						),
						attributes.imageId
							? el(
									Button,
									{
										variant: 'tertiary',
										isDestructive: true,
										onClick: function () {
											setAttributes( { imageId: 0, imageUrl: '', imageAlt: '' } );
										},
									},
									__( 'Bỏ ảnh', 'nntm' )
							  )
							: null,
						el( TextControl, {
							label: __( 'Chữ thay ảnh (cho người khiếm thị)', 'nntm' ),
							help: __( 'Mô tả ngắn nội dung ảnh. Ảnh chỉ để trang trí thì để trống.', 'nntm' ),
							value: attributes.imageAlt || '',
							onChange: function ( value ) {
								setAttributes( { imageAlt: value } );
							},
						} ),
						el( TextControl, {
							label: __( 'Hoặc dán đường dẫn ảnh ngoài', 'nntm' ),
							help: __( 'Chỉ dùng khi ảnh không nằm trong Thư viện. Chọn ảnh ở trên thì bỏ qua ô này.', 'nntm' ),
							type: 'url',
							placeholder: 'https://…',
							value: attributes.imageUrl || '',
							onChange: function ( value ) {
								setAttributes( { imageUrl: value } );
							},
						} )
					),
					el(
						PanelBody,
						{ title: __( 'Thẻ video nổi', 'nntm' ), initialOpen: true },
						el( SelectControl, {
							label: __( 'Video hiển thị', 'nntm' ),
							help: __( 'Thẻ chỉ hiện ảnh đại diện và nút phát, đúng theo thiết kế — không hiện tên hay ngày.', 'nntm' ),
							value: attributes.videoId,
							options: videoOptions,
							onChange: function ( value ) {
								setAttributes( { videoId: parseInt( value, 10 ) || 0 } );
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
			// Block động: PHP (render.php) tự dựng lại mỗi lần tải trang.
			return null;
		},
	} );
} )( window.wp );
