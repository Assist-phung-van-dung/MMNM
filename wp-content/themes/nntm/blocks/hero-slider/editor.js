/**
 * Editor script cho block nntm/hero-slider — JavaScript thuần, không build.
 * Bắt chước phong cách blocks/thien-duong/editor.js (MediaUpload,
 * ServerSideRender) và blocks/article-mosaic/editor.js (tải danh sách
 * term qua REST theo tầng). Khác hai block đó ở một điểm: "slides" là một
 * mảng — bảng điều khiển phải tự vẽ phần thêm/xoá/sắp lại từng tấm
 * (repeater), Gutenberg không có control dựng sẵn cho việc này.
 *
 * Bản xem trước trên canvas dùng ServerSideRender (chạy đúng logic PHP
 * thật) — mọi ô nhập nằm ở InspectorControls, không có RichText trên
 * canvas nên không sợ hiện trùng nội dung.
 */
( function ( wp ) {
	'use strict';

	var el = wp.element.createElement;
	var Fragment = wp.element.Fragment;
	var useState = wp.element.useState;
	var useEffect = wp.element.useEffect;
	var __ = wp.i18n.__;
	var sprintf = wp.i18n.sprintf;
	var registerBlockType = wp.blocks.registerBlockType;
	var useBlockProps = wp.blockEditor.useBlockProps;
	var InspectorControls = wp.blockEditor.InspectorControls;
	var MediaUpload = wp.blockEditor.MediaUpload;
	var MediaUploadCheck = wp.blockEditor.MediaUploadCheck;
	var PanelBody = wp.components.PanelBody;
	var Button = wp.components.Button;
	var TextControl = wp.components.TextControl;
	var TextareaControl = wp.components.TextareaControl;
	var ToggleControl = wp.components.ToggleControl;
	var RangeControl = wp.components.RangeControl;
	var SelectControl = wp.components.SelectControl;
	var apiFetch = wp.apiFetch;
	var ServerSideRender = wp.serverSideRender && wp.serverSideRender.default ? wp.serverSideRender.default : wp.serverSideRender;

	/**
	 * Một tấm trống — dùng khi bấm "Thêm tấm". render.php tự bỏ qua tấm
	 * hoàn toàn trống (nntm_hero_slider_clean_slide()) nên không sợ hiện
	 * tấm rỗng ra trang thật nếu khách thêm rồi đổi ý không điền gì.
	 */
	function emptySlide() {
		return {
			imageId: 0,
			imageUrl: '',
			imageAlt: '',
			heading: '',
			text: '',
			ctaLabel: '',
			ctaUrl: '',
		};
	}

	registerBlockType( 'nntm/hero-slider', {
		edit: function ( props ) {
			var attributes = props.attributes;
			var setAttributes = props.setAttributes;
			var blockProps = useBlockProps();
			var slides = Array.isArray( attributes.slides ) ? attributes.slides : [];

			// Danh sách phân mục CHA (parent=0) trong nntm_section, để bảng
			// điều khiển chỉ cho chọn TÊN, không bắt nhập số ID.
			var parentTermState = useState( [] );
			var parentTerms = parentTermState[ 0 ];
			var setParentTerms = parentTermState[ 1 ];

			useEffect(
				function () {
					var isCurrent = true;

					apiFetch( {
						path: '/wp/v2/nntm_section?parent=0&per_page=100&orderby=name&order=asc&_fields=id,name',
					} )
						.then( function ( terms ) {
							if ( isCurrent ) {
								setParentTerms( terms || [] );
							}
						} )
						.catch( function () {
							if ( isCurrent ) {
								setParentTerms( [] );
							}
						} );

					return function () {
						isCurrent = false;
					};
				},
				[]
			);

			function updateSlide( index, patch ) {
				var next = slides.slice();
				next[ index ] = Object.assign( {}, next[ index ], patch );
				setAttributes( { slides: next } );
			}

			function addSlide() {
				setAttributes( { slides: slides.concat( [ emptySlide() ] ) } );
			}

			function removeSlide( index ) {
				var next = slides.slice();
				next.splice( index, 1 );
				setAttributes( { slides: next } );
			}

			function moveSlide( index, offset ) {
				var target = index + offset;
				if ( target < 0 || target >= slides.length ) {
					return;
				}
				var next = slides.slice();
				var tmp = next[ index ];
				next[ index ] = next[ target ];
				next[ target ] = tmp;
				setAttributes( { slides: next } );
			}

			var parentTermOptions = [ { label: __( '— Không hiện dải liên kết —', 'nntm' ), value: 0 } ].concat(
				parentTerms.map( function ( term ) {
					return { label: term.name, value: term.id };
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
						{ title: __( 'Các tấm băng chuyền', 'nntm' ), initialOpen: true },
						el(
							'p',
							{ className: 'components-base-control__help' },
							__( 'Thêm, xoá, sắp lại từng tấm. Tấm đầu tiên tải ảnh ngay (ảnh hưởng tốc độ tải trang); các tấm sau chỉ tải khi cần.', 'nntm' )
						),
						slides.map( function ( slide, index ) {
							return el(
								PanelBody,
								{
									key: 'slide-' + index,
									title: sprintf( __( 'Tấm %d', 'nntm' ), index + 1 ),
									initialOpen: 0 === index,
								},
								el(
									MediaUploadCheck,
									{},
									el( MediaUpload, {
										onSelect: function ( media ) {
											updateSlide( index, {
												imageId: media && media.id ? media.id : 0,
												imageUrl: media && media.url ? media.url : '',
												imageAlt: media && media.alt ? media.alt : slide.imageAlt,
											} );
										},
										allowedTypes: [ 'image' ],
										value: slide.imageId,
										render: function ( mediaProps ) {
											return el(
												Fragment,
												{},
												el(
													Button,
													{ onClick: mediaProps.open, variant: 'secondary' },
													slide.imageUrl ? __( 'Đổi ảnh khác', 'nntm' ) : __( 'Chọn ảnh nền', 'nntm' )
												),
												slide.imageUrl
													? el(
															Button,
															{
																onClick: function () {
																	updateSlide( index, { imageId: 0, imageUrl: '' } );
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
								slide.imageUrl
									? el( 'img', {
											src: slide.imageUrl,
											alt: '',
											style: { maxWidth: '100%', borderRadius: '6px', margin: '8px 0' },
									  } )
									: null,
								el( TextControl, {
									label: __( 'Mô tả ảnh (alt, cho người khiếm thị)', 'nntm' ),
									value: slide.imageAlt,
									onChange: function ( value ) {
										updateSlide( index, { imageAlt: value } );
									},
								} ),
								el( TextareaControl, {
									label: __( 'Tiêu đề tấm (Enter để xuống dòng đúng như thiết kế)', 'nntm' ),
									value: slide.heading,
									rows: 2,
									onChange: function ( value ) {
										updateSlide( index, { heading: value } );
									},
								} ),
								el( TextControl, {
									label: __( 'Mô tả ngắn', 'nntm' ),
									value: slide.text,
									onChange: function ( value ) {
										updateSlide( index, { text: value } );
									},
								} ),
								el( TextControl, {
									label: __( 'Nhãn nút (ví dụ "Xem thêm")', 'nntm' ),
									value: slide.ctaLabel,
									onChange: function ( value ) {
										updateSlide( index, { ctaLabel: value } );
									},
								} ),
								el( TextControl, {
									label: __( 'Đường dẫn nút', 'nntm' ),
									type: 'url',
									value: slide.ctaUrl,
									onChange: function ( value ) {
										updateSlide( index, { ctaUrl: value } );
									},
								} ),
								el(
									'div',
									{ style: { display: 'flex', gap: '8px', flexWrap: 'wrap', marginTop: '8px' } },
									el(
										Button,
										{
											variant: 'secondary',
											disabled: 0 === index,
											onClick: function () {
												moveSlide( index, -1 );
											},
										},
										__( '↑ Lên', 'nntm' )
									),
									el(
										Button,
										{
											variant: 'secondary',
											disabled: index === slides.length - 1,
											onClick: function () {
												moveSlide( index, 1 );
											},
										},
										__( '↓ Xuống', 'nntm' )
									),
									el(
										Button,
										{
											variant: 'secondary',
											isDestructive: true,
											onClick: function () {
												removeSlide( index );
											},
										},
										__( 'Xoá tấm này', 'nntm' )
									)
								)
							);
						} ),
						el(
							Button,
							{ variant: 'primary', onClick: addSlide, style: { marginTop: '8px' } },
							__( '+ Thêm tấm mới', 'nntm' )
						)
					),
					el(
						PanelBody,
						{ title: __( 'Tự động chạy', 'nntm' ), initialOpen: false },
						el( ToggleControl, {
							label: __( 'Tự động chuyển tấm', 'nntm' ),
							help: __( 'Tự dừng khi khách rê chuột/đưa tiêu điểm vào, khi ẩn tab, và tắt hẳn nếu khách đã bật "giảm chuyển động" trên máy.', 'nntm' ),
							checked: !! attributes.autoplay,
							onChange: function ( value ) {
								setAttributes( { autoplay: value } );
							},
						} ),
						el( RangeControl, {
							label: __( 'Chu kỳ tự chạy (giây)', 'nntm' ),
							value: attributes.interval,
							min: 2,
							max: 30,
							onChange: function ( value ) {
								setAttributes( { interval: value || 6 } );
							},
						} )
					),
					el(
						PanelBody,
						{ title: __( 'Thẻ nhỏ góc phải dưới', 'nntm' ), initialOpen: false },
						el( TextareaControl, {
							label: __( 'Tiêu đề thẻ (Enter để xuống dòng)', 'nntm' ),
							value: attributes.sideCardHeading,
							rows: 2,
							onChange: function ( value ) {
								setAttributes( { sideCardHeading: value } );
							},
						} ),
						el( TextControl, {
							label: __( 'Mô tả (hiện chữ nghiêng)', 'nntm' ),
							value: attributes.sideCardText,
							onChange: function ( value ) {
								setAttributes( { sideCardText: value } );
							},
						} ),
						el( TextControl, {
							label: __( 'Nhãn nút', 'nntm' ),
							value: attributes.sideCardCtaLabel,
							onChange: function ( value ) {
								setAttributes( { sideCardCtaLabel: value } );
							},
						} ),
						el( TextControl, {
							label: __( 'Đường dẫn nút', 'nntm' ),
							type: 'url',
							value: attributes.sideCardCtaUrl,
							onChange: function ( value ) {
								setAttributes( { sideCardCtaUrl: value } );
							},
						} )
					),
					el(
						PanelBody,
						{ title: __( 'Dải liên kết nhanh (đáy trái)', 'nntm' ), initialOpen: false },
						el(
							'p',
							{ className: 'components-base-control__help' },
							__( 'Chọn một chuyên mục CHA trong Phân mục (ví dụ "Pháp Tòa") — băng chuyền tự hiện các chuyên mục con của mục đó thành dải nút. Thêm chuyên mục con mới ở Phân mục thì dải nút tự cập nhật, không cần sửa lại khối này. Để trống nếu không cần dải này.', 'nntm' )
						),
						el( SelectControl, {
							label: __( 'Chuyên mục cha', 'nntm' ),
							value: attributes.quickLinksParentTermId,
							options: parentTermOptions,
							onChange: function ( value ) {
								setAttributes( { quickLinksParentTermId: parseInt( value, 10 ) || 0 } );
							},
						} )
					)
				),
				el( ServerSideRender, {
					block: 'nntm/hero-slider',
					attributes: attributes,
				} )
			);
		},
		save: function () {
			// Block động: PHP (render.php) tự dựng lại HTML mỗi lần tải trang.
			// Không lưu HTML tĩnh vào nội dung bài.
			return null;
		},
	} );
} )( window.wp );
