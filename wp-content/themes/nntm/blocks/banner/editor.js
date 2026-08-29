
( function ( wp ) {
	'use strict';

	var el = wp.element.createElement;
	var Fragment = wp.element.Fragment;
	var __ = wp.i18n.__;
	var sprintf = wp.i18n.sprintf;
	var registerBlockType = wp.blocks.registerBlockType;
	var useBlockProps = wp.blockEditor.useBlockProps;
	var InspectorControls = wp.blockEditor.InspectorControls;
	var MediaUpload = wp.blockEditor.MediaUpload;
	var MediaUploadCheck = wp.blockEditor.MediaUploadCheck;
	var PanelBody = wp.components.PanelBody;
	var TextControl = wp.components.TextControl;
	var TextareaControl = wp.components.TextareaControl;
	var ToggleControl = wp.components.ToggleControl;
	var RangeControl = wp.components.RangeControl;
	var Button = wp.components.Button;
	var ServerSideRender = wp.serverSideRender && wp.serverSideRender.default ? wp.serverSideRender.default : wp.serverSideRender;

	function tamRong() {
		return { mediaType: 'image', imageId: 0, imageUrl: '', imageAlt: '', heading: '', text: '' };
	}

	registerBlockType( 'nntm/banner', {
		edit: function ( props ) {
			var attributes = props.attributes;
			var setAttributes = props.setAttributes;
			var slides = Array.isArray( attributes.slides ) ? attributes.slides : [];

			var blockProps = useBlockProps();

			function capNhatTam( chiSo, thayDoi ) {
				var moi = slides.map( function ( tam, i ) {
					return i === chiSo ? Object.assign( {}, tam, thayDoi ) : tam;
				} );
				setAttributes( { slides: moi } );
			}

			function themTam() {
				setAttributes( { slides: slides.concat( [ tamRong() ] ) } );
			}

			function xoaTam( chiSo ) {
				setAttributes( {
					slides: slides.filter( function ( tam, i ) {
						return i !== chiSo;
					} ),
				} );
			}

			function doiCho( chiSo, buoc ) {
				var dich = chiSo + buoc;
				if ( dich < 0 || dich >= slides.length ) {
					return;
				}
				var moi = slides.slice();
				var tam = moi[ chiSo ];
				moi[ chiSo ] = moi[ dich ];
				moi[ dich ] = tam;
				setAttributes( { slides: moi } );
			}

			var bangTam = slides.map( function ( tam, chiSo ) {
				return el(
					PanelBody,
					{
						key: 'tam-' + chiSo,
						title: sprintf( __( 'Tấm %d', 'nntm' ), chiSo + 1 ),
						initialOpen: 0 === chiSo,
					},
					el(
						MediaUploadCheck,
						{},
						el( MediaUpload, {
							onSelect: function ( media ) {
								var mediaType = media && ( 'video' === media.type || ( media.mime && 0 === media.mime.indexOf( 'video/' ) ) ) ? 'video' : 'image';
								capNhatTam( chiSo, {
									mediaType: mediaType,
									imageId: media && media.id ? media.id : 0,
									imageUrl: media && media.url ? media.url : '',
									imageAlt: 'image' === mediaType && media && media.alt ? media.alt : ( tam.imageAlt || '' ),
								} );
							},
							allowedTypes: [ 'image', 'video' ],
							value: tam.imageId,
							render: function ( mediaProps ) {
								return el(
									Fragment,
									{},
									el(
										Button,
										{ onClick: mediaProps.open, variant: 'secondary' },
										tam.imageUrl ? __( 'Đổi ảnh / video', 'nntm' ) : __( 'Chọn ảnh hoặc video', 'nntm' )
									),
									tam.imageUrl
										? el(
												Button,
												{
													onClick: function () {
														capNhatTam( chiSo, { mediaType: 'image', imageId: 0, imageUrl: '' } );
													},
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
						label: __( 'Mô tả media (alt)', 'nntm' ),
						help: __( 'Ảnh dùng làm alt; video nền thường để trống vì chỉ mang tính trang trí.', 'nntm' ),
						value: tam.imageAlt || '',
						onChange: function ( gt ) {
							capNhatTam( chiSo, { imageAlt: gt } );
						},
					} ),
					el( TextareaControl, {
						label: __( 'Tiêu đề', 'nntm' ),
						help: __( 'Xuống dòng bằng phím Enter — thiết kế để tiêu đề hai dòng.', 'nntm' ),
						value: tam.heading || '',
						onChange: function ( gt ) {
							capNhatTam( chiSo, { heading: gt } );
						},
					} ),
					el( TextareaControl, {
						label: __( 'Phụ đề', 'nntm' ),
						value: tam.text || '',
						onChange: function ( gt ) {
							capNhatTam( chiSo, { text: gt } );
						},
					} ),
					el( ToggleControl, {
						label: __( 'Hiện nút hành động (vd: "Tham gia")', 'nntm' ),
						help: __( 'Dùng cho dải "Lễ Đàn Khổng Tước" (Cộng Tu chuỗi trì). Đường dẫn nút lấy tự động từ phần Cộng Tu — chưa có phần đó thì nút vẫn hiện nhưng vô hiệu hoá.', 'nntm' ),
						checked: !! tam.showButton,
						onChange: function ( gt ) {
							capNhatTam( chiSo, { showButton: gt } );
						},
					} ),
					tam.showButton
						? el( TextControl, {
								label: __( 'Nhãn nút', 'nntm' ),
								value: tam.buttonLabel || '',
								placeholder: __( 'Tham gia', 'nntm' ),
								onChange: function ( gt ) {
									capNhatTam( chiSo, { buttonLabel: gt } );
								},
						  } )
						: null,
					el(
						'div',
						{ style: { display: 'flex', gap: '8px', flexWrap: 'wrap' } },
						el(
							Button,
							{
								variant: 'secondary',
								disabled: 0 === chiSo,
								onClick: function () {
									doiCho( chiSo, -1 );
								},
							},
							__( 'Đẩy lên', 'nntm' )
						),
						el(
							Button,
							{
								variant: 'secondary',
								disabled: chiSo === slides.length - 1,
								onClick: function () {
									doiCho( chiSo, 1 );
								},
							},
							__( 'Đẩy xuống', 'nntm' )
						),
						el(
							Button,
							{
								variant: 'link',
								isDestructive: true,
								onClick: function () {
									xoaTam( chiSo );
								},
							},
							__( 'Xoá tấm này', 'nntm' )
						)
					)
				);
			} );

			/*
			 * Xem truoc bang chinh render.php, khong ve lai bang tay.
			 *
			 * Truoc day cho nay dung mot bo class song song .nntm-banner-editor__*
			 * — khong trung mot class nao voi ban that — nen banner trong admin luon
			 * khac ngoai trang. render.php da co san nhanh REST_REQUEST cho truong
			 * hop chua co tam nao, nen chuyen sang ServerSideRender la du.
			 */
			var xemTruoc = el( ServerSideRender, {
				block: 'nntm/banner',
				attributes: attributes,
			} );

			return el(
				'div',
				blockProps,
				el(
					InspectorControls,
					{},
					el(
						PanelBody,
						{ title: __( 'Bố cục', 'nntm' ), initialOpen: false },
						el( ToggleControl, {
							label: __( 'Tràn hết chiều rộng màn hình', 'nntm' ),
							help: __( 'Bật cho các trang cần banner tràn sát mép, góc vuông (vd Kim Cương Hành Giả). Mặc định TẮT — trang chủ dùng khung có đệm + bo góc theo thiết kế, bật nhầm sẽ đổi hình dạng banner trang chủ.', 'nntm' ),
							checked: !! attributes.tranVien,
							onChange: function ( gt ) {
								setAttributes( { tranVien: gt } );
							},
						} )
					),
					el(
						PanelBody,
						{ title: __( 'Các tấm băng chuyền', 'nntm' ), initialOpen: true },
						el(
							'p',
							{ style: { marginTop: 0 } },
							sprintf( __( 'Đang có %d tấm.', 'nntm' ), slides.length )
						),
						el( Button, { variant: 'primary', onClick: themTam }, __( 'Thêm một tấm', 'nntm' ) )
					),
					bangTam,
					el(
						PanelBody,
						{ title: __( 'Biểu tượng trang trí', 'nntm' ), initialOpen: false },
						el(
							'p',
							{ style: { marginTop: 0 } },
							__( 'Hiện phía trên tiêu đề trên mọi tấm. Thiết kế dùng biểu tượng cây bồ đề, cao khoảng 134px.', 'nntm' )
						),
						el(
							MediaUploadCheck,
							{},
							el( MediaUpload, {
								onSelect: function ( media ) {
									setAttributes( {
										emblemId: media && media.id ? media.id : 0,
										emblemUrl: media && media.url ? media.url : '',
									} );
								},
								allowedTypes: [ 'image' ],
								value: attributes.emblemId,
								render: function ( mediaProps ) {
									return el(
										Fragment,
										{},
										el(
											Button,
											{ onClick: mediaProps.open, variant: 'secondary' },
											attributes.emblemUrl ? __( 'Đổi biểu tượng', 'nntm' ) : __( 'Chọn biểu tượng', 'nntm' )
										),
										attributes.emblemUrl
											? el(
													Button,
													{
														onClick: function () {
															setAttributes( { emblemId: 0, emblemUrl: '' } );
														},
														variant: 'link',
														isDestructive: true,
													},
													__( 'Gỡ biểu tượng', 'nntm' )
											  )
											: null
									);
								},
							} )
						),
						el( TextControl, {
							label: __( 'Mô tả biểu tượng (alt)', 'nntm' ),
							help: __( 'Để trống nếu chỉ để trang trí.', 'nntm' ),
							value: attributes.emblemAlt || '',
							onChange: function ( gt ) {
								setAttributes( { emblemAlt: gt } );
							},
						} )
					),
					el(
						PanelBody,
						{ title: __( 'Tự chạy', 'nntm' ), initialOpen: false },
						el( ToggleControl, {
							label: __( 'Tự chuyển tấm', 'nntm' ),
							help: __( 'Tự dừng khi rê chuột vào, khi dùng bàn phím, và khi người xem đã bật chế độ giảm chuyển động.', 'nntm' ),
							checked: !! attributes.autoplay,
							onChange: function ( gt ) {
								setAttributes( { autoplay: gt } );
							},
						} ),
						el( RangeControl, {
							label: __( 'Chu kỳ (giây)', 'nntm' ),
							value: attributes.interval,
							min: 2,
							max: 30,
							onChange: function ( gt ) {
								setAttributes( { interval: gt } );
							},
						} )
					)
				),
				xemTruoc
			);
		},
		save: function () {

			return null;
		},
	} );
} )( window.wp );
