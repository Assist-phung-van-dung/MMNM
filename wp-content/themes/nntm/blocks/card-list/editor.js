 
( function ( wp ) {
	'use strict';

	var el = wp.element.createElement;
	var useState = wp.element.useState;
	var useEffect = wp.element.useEffect;
	var __ = wp.i18n.__;
	var registerBlockType = wp.blocks.registerBlockType;
	var useBlockProps = wp.blockEditor.useBlockProps;
	var InspectorControls = wp.blockEditor.InspectorControls;
	var RichText = wp.blockEditor.RichText;
	var PanelBody = wp.components.PanelBody;
	var SelectControl = wp.components.SelectControl;
	var ToggleControl = wp.components.ToggleControl;
	var RangeControl = wp.components.RangeControl;
	var TextControl = wp.components.TextControl;
	var TextareaControl = wp.components.TextareaControl;
	var apiFetch = wp.apiFetch;
	var ServerSideRender = wp.serverSideRender && wp.serverSideRender.default ? wp.serverSideRender.default : wp.serverSideRender;

	var VARIANT_OPTIONS = [
		{ label: __( 'Bài viết lớn (Article)', 'nntm' ), value: 'article' },
		{ label: __( 'Bài viết vừa (Small)', 'nntm' ), value: 'small' },
		{ label: __( 'Bài viết nhỏ (XS)', 'nntm' ), value: 'xs' },
		{ label: __( 'Thẻ Đại Sĩ (chủ đề)', 'nntm' ), value: 'dai-si' },
		{ label: __( 'Thẻ Kim Cương (trắng, có đoạn trích)', 'nntm' ), value: 'kim-cuong' },
		{ label: __( 'Bài viết lớn — khi rê chuột (Hover)', 'nntm' ), value: 'article-hover' },
		{ label: __( 'Video', 'nntm' ), value: 'video' },
		{ label: __( 'Khóa Tu', 'nntm' ), value: 'khoa-tu' },
		{ label: __( 'Ấn phẩm / Sách (Books)', 'nntm' ), value: 'books' },
	];

	var POST_TYPE_OPTIONS = [
		{ label: __( 'Bài viết (6 phân mục)', 'nntm' ), value: 'nntm_article' },
		{ label: __( 'Ấn phẩm (PDF / Books)', 'nntm' ), value: 'nntm_publication' },
		{ label: __( 'Pháp Thoại', 'nntm' ), value: 'nntm_talk' },
		{ label: __( 'Khóa Tu', 'nntm' ), value: 'nntm_retreat' },
		{ label: __( 'Trú Xứ', 'nntm' ), value: 'nntm_abode' },
		{ label: __( 'Video', 'nntm' ), value: 'nntm_video' },
		{ label: __( 'Nhạc thiền', 'nntm' ), value: 'nntm_zen_track' },
		{ label: __( 'Chuyện về Thầy Tôi', 'nntm' ), value: 'nntm_chuyen_thay_toi' },
		{ label: __( 'Tin Tức / Hoằng Pháp', 'nntm' ), value: 'post' },
	];

	var ORDER_BY_OPTIONS = [
		{ label: __( 'Mới nhất trước', 'nntm' ), value: 'newest' },
		{ label: __( 'Cũ nhất trước', 'nntm' ), value: 'oldest' },
		{ label: __( 'Theo tên (A-Z)', 'nntm' ), value: 'title' },
		{ label: __( 'Thứ tự thủ công', 'nntm' ), value: 'manual' },
	];

	var COLUMN_OPTIONS = [
		{ label: __( '2 cột', 'nntm' ), value: 2 },
		{ label: __( '3 cột', 'nntm' ), value: 3 },
		{ label: __( '4 cột', 'nntm' ), value: 4 },
	];





	var LAYOUT_OPTIONS = [
		{ label: __( 'Lưới (Grid)', 'nntm' ), value: 'grid' },
		{ label: __( 'Băng cuộn ngang (Carousel)', 'nntm' ), value: 'carousel' },
		{ label: __( 'Băng tự chạy (không nút bấm)', 'nntm' ), value: 'marquee' },
	];



	var BACKGROUND_OPTIONS = [
		{ label: __( 'Không nền (mặc định)', 'nntm' ), value: 'none' },
		{ label: __( 'Nền kem', 'nntm' ), value: 'kem' },
		{ label: __( 'Nền cam', 'nntm' ), value: 'cam' },
		{ label: __( 'Nền tối', 'nntm' ), value: 'toi' },
		{ label: __( 'Nền navy (Đại Sĩ Hành Giả)', 'nntm' ), value: 'cham' },
		{ label: __( 'Nền vàng nghệ (Kim Cương Hành Giả)', 'nntm' ), value: 'vang' },
	];

	var TAXONOMY_LABELS = {
		nntm_section: __( 'Phân mục', 'nntm' ),
		nntm_topic: __( 'Chủ đề', 'nntm' ),
		nntm_series: __( 'Bộ / Series', 'nntm' ),
		category: __( 'Chuyên mục', 'nntm' ),
		post_tag: __( 'Thẻ', 'nntm' ),
	};

	var REST_BASE_OVERRIDES = {
		category: 'categories',
		post_tag: 'tags',
	};

	function restBaseFor( taxonomy ) {
		return REST_BASE_OVERRIDES[ taxonomy ] || taxonomy;
	}

	function taxonomyLabel( taxonomy ) {
		return TAXONOMY_LABELS[ taxonomy ] || taxonomy;
	}



	var VIDEO_SOURCE_OPTIONS = [
		{ label: __( 'Bài viết trong CSDL (mặc định)', 'nntm' ), value: 'posts' },
		{ label: __( 'Dán link YouTube', 'nntm' ), value: 'youtube' },
	];

	registerBlockType( 'nntm/card-list', {
		edit: function ( props ) {
			var attributes = props.attributes;
			var setAttributes = props.setAttributes;
			var blockProps = useBlockProps();

			var taxonomyState = useState( [] ); 
			var availableTaxonomies = taxonomyState[ 0 ];
			var setAvailableTaxonomies = taxonomyState[ 1 ];

			var termState = useState( [] ); 
			var availableTerms = termState[ 0 ];
			var setAvailableTerms = termState[ 1 ];

			useEffect(
				function () {
					var isCurrent = true;

					apiFetch( { path: '/wp/v2/types/' + attributes.postType } )
						.then( function ( typeInfo ) {
							if ( ! isCurrent ) {
								return;
							}
							var taxonomies = ( typeInfo && typeInfo.taxonomies ) || [];
							setAvailableTaxonomies( taxonomies );

							if ( attributes.taxonomy && taxonomies.indexOf( attributes.taxonomy ) === -1 ) {
								setAttributes( { taxonomy: '', termId: 0 } );
							}
						} )
						.catch( function () {
							if ( isCurrent ) {
								setAvailableTaxonomies( [] );
							}
						} );

					return function () {
						isCurrent = false;
					};
				},
				[ attributes.postType ]
			);

			useEffect(
				function () {
					var isCurrent = true;

					if ( ! attributes.taxonomy ) {
						setAvailableTerms( [] );
						return undefined;
					}

					apiFetch( {
						path: '/wp/v2/' + restBaseFor( attributes.taxonomy ) + '?per_page=100&orderby=name&order=asc&_fields=id,name',
					} )
						.then( function ( terms ) {
							if ( isCurrent ) {
								setAvailableTerms( terms || [] );
							}
						} )
						.catch( function () {
							if ( isCurrent ) {
								setAvailableTerms( [] );
							}
						} );

					return function () {
						isCurrent = false;
					};
				},
				[ attributes.taxonomy ]
			);

			var taxonomyOptions = [ { label: __( '— Không lọc theo mục nào —', 'nntm' ), value: '' } ].concat(
				availableTaxonomies.map( function ( taxonomy ) {
					return { label: taxonomyLabel( taxonomy ), value: taxonomy };
				} )
			);

			var termOptions = [ { label: __( 'Tất cả', 'nntm' ), value: 0 } ].concat(
				availableTerms.map( function ( term ) {
					return { label: term.name, value: term.id };
				} )
			);

			var previewAttributes = Object.assign( {}, attributes, { heading: '', subheading: '' } ); 

			return el(
				'div',
				blockProps,
				el(
					InspectorControls,
					{},
					el(
						PanelBody,
						{ title: __( 'Lấy nội dung từ đâu', 'nntm' ), initialOpen: true },
						el( SelectControl, {
							label: __( 'Nguồn video / bài viết', 'nntm' ),
							help: __( 'Dùng cho băng thẻ kiểu Netflix (dải "Gót Son"): chọn "Dán link YouTube" để tự nhập danh sách video, không lấy từ bài viết trong CSDL.', 'nntm' ),
							value: attributes.videoSource || 'posts',
							options: VIDEO_SOURCE_OPTIONS,
							onChange: function ( value ) {
								setAttributes( { videoSource: value } );
							},
						} ),
						'youtube' === attributes.videoSource
							? el( TextareaControl, {
									label: __( 'Danh sách link YouTube', 'nntm' ),
									help: __( 'Mỗi dòng một video. Dán link dạng youtube.com/watch?v=…, youtu.be/… hoặc chỉ ID video đều được. Muốn tự đặt tiêu đề hiện dưới thẻ thì gõ thêm " | Tiêu đề" ngay trên cùng dòng (ví dụ: https://youtu.be/abc123 | TẬP 18 - CHÂN SƯ HIỆN THÁNH TƯỚNG); không gõ thì tự lấy tên video từ YouTube. Ảnh nền thẻ lấy trực tiếp từ YouTube, không cần tải ảnh lên.', 'nntm' ),
									value: attributes.youtubeItems || '',
									rows: 6,
									onChange: function ( value ) {
										setAttributes( { youtubeItems: value } );
									},
							  } )
							: [
									el( SelectControl, {
										key: 'postType',
										label: __( 'Lấy bài từ loại nội dung nào', 'nntm' ),
										value: attributes.postType,
										options: POST_TYPE_OPTIONS,
										onChange: function ( value ) {
											setAttributes( { postType: value, taxonomy: '', termId: 0 } );
										},
									} ),
									el( SelectControl, {
										key: 'taxonomy',
										label: __( 'Lọc theo', 'nntm' ),
										help: __( 'Chỉ lấy bài thuộc một mục/chủ đề cụ thể. Để trống nếu muốn lấy tất cả.', 'nntm' ),
										value: attributes.taxonomy,
										options: taxonomyOptions,
										onChange: function ( value ) {
											setAttributes( { taxonomy: value, termId: 0 } );
										},
									} ),
									attributes.taxonomy
										? el( SelectControl, {
												key: 'termId',
												label: __( 'Lấy bài từ mục nào', 'nntm' ),
												value: attributes.termId,
												options: termOptions,
												onChange: function ( value ) {
													setAttributes( { termId: parseInt( value, 10 ) || 0 } );
												},
										  } )
										: null,
							  ]
					),
					el(
						PanelBody,
						{ title: __( 'Hiển thị', 'nntm' ), initialOpen: true },
						'youtube' === attributes.videoSource
							? el(
									'p',
									{ className: 'components-base-control__help' },
									__( 'Nguồn "Dán link YouTube" luôn hiện thành băng cuộn tự chạy kiểu Netflix — không dùng các tuỳ chọn "Kiểu thẻ hiển thị" / "Kiểu hiển thị" bên dưới.', 'nntm' )
							  )
							: null,
						'youtube' === attributes.videoSource
							? null
							: el( SelectControl, {
									label: __( 'Kiểu thẻ hiển thị', 'nntm' ),
									value: attributes.variant,
									options: VARIANT_OPTIONS,
									onChange: function ( value ) {
										setAttributes( { variant: value } );
									},
							  } ),
						'youtube' === attributes.videoSource
							? null
							: el( SelectControl, {
									label: __( 'Kiểu hiển thị', 'nntm' ),
									help: __( 'Lưới: xếp nhiều hàng, có thể phân trang. Băng cuộn ngang: một hàng duy nhất, khách cuộn bằng nút lùi/tiến hoặc bàn phím. Băng tự chạy: một hàng tự trôi liên tục, không có nút bấm và không có thanh cuộn nào — rê chuột hoặc bấm Tab vào thì băng dừng lại.', 'nntm' ),
									value: attributes.layout || 'grid',
									options: LAYOUT_OPTIONS,
									onChange: function ( value ) {
										setAttributes( { layout: value } );
									},
							  } ),
						el( SelectControl, {
							label: __( 'Màu nền khối', 'nntm' ),
							help: __( 'Nền tràn hết chiều rộng trang. Nền cam và nền tối tự đổi chữ tiêu đề sang màu kem cho đủ tương phản.', 'nntm' ),
							value: attributes.background || 'none',
							options: BACKGROUND_OPTIONS,
							onChange: function ( value ) {
								setAttributes( { background: value } );
							},
						} ),


						'youtube' === attributes.videoSource
							? null
							: el( ToggleControl, {
									label: __( 'Hiện ngày cập nhật trên thẻ', 'nntm' ),
									checked: attributes.showDate !== false,
									onChange: function ( value ) {
										setAttributes( { showDate: value } );
									},
							  } ),
						'youtube' === attributes.videoSource
							? null
							: el( ToggleControl, {
									label: __( 'Hiện nhãn chuyên mục trên thẻ', 'nntm' ),
									checked: attributes.showCategory !== false,
									onChange: function ( value ) {
										setAttributes( { showCategory: value } );
									},
							  } ),

						'youtube' === attributes.videoSource
							? null
							: el( ToggleControl, {
									label: __( 'Hiện nút "Xem thêm" trong từng thẻ', 'nntm' ),
									help: __( 'Kiểu thẻ Đại Sĩ luôn hiện dòng này dù bật hay tắt ở đây.', 'nntm' ),
									checked: !! attributes.showCardCta,
									onChange: function ( value ) {
										setAttributes( { showCardCta: value } );
									},
							  } ),
						'youtube' === attributes.videoSource || ( ! attributes.showCardCta && 'dai-si' !== attributes.variant && 'kim-cuong' !== attributes.variant )
							? null
							: el( TextControl, {
									label: __( 'Nhãn nút "Xem thêm"', 'nntm' ),
									value: attributes.cardCtaLabel || '',
									onChange: function ( value ) {
										setAttributes( { cardCtaLabel: value } );
									},
							  } ),


						el( TextControl, {
							label: __( 'Nhãn liên kết xem tất cả', 'nntm' ),
							help: __( 'Để trống nếu không cần hiện liên kết.', 'nntm' ),
							value: attributes.showViewAll ? ( attributes.viewAllLabel || 'Xem tất cả' ) : '',
							onChange: function ( value ) {
								setAttributes( { showViewAll: !! value, viewAllLabel: value } );
							},
						} ),
						attributes.showViewAll
							? el( TextControl, {
									label: __( 'Đường dẫn "Xem tất cả" (ghi đè)', 'nntm' ),
									help: __( 'Để trống thì tự lấy đường dẫn kho lưu trữ / chuyên mục đang lọc. Dùng khi muốn trỏ sang một Trang riêng, ví dụ /nghi-quy/.', 'nntm' ),
									type: 'url',
									placeholder: '/nghi-quy/',
									value: attributes.viewAllUrl || '',
									onChange: function ( value ) {
										setAttributes( { viewAllUrl: value } );
									},
							  } )
							: null,
						el( TextControl, {
							label: __( 'Dòng tiêu đề đặt PHÍA TRÊN dải nền', 'nntm' ),
							help: __( 'Dùng cho kiểu tiêu đề so le: dòng này nằm ngoài dải nền (chữ đậm màu), dòng tiêu đề chính nằm trong dải và thụt vào phải. Để trống thì tiêu đề hiện bình thường.', 'nntm' ),
							value: attributes.headingAbove || '',
							onChange: function ( value ) {
								setAttributes( { headingAbove: value } );
							},
						} ),
						el( TextareaControl, {
							label: __( 'Đoạn chữ nghiêng dưới dải nền', 'nntm' ),
							help: __( 'Nằm ngoài dải, căn giữa. Để trống thì không hiện.', 'nntm' ),
							value: attributes.captionBelow || '',
							onChange: function ( value ) {
								setAttributes( { captionBelow: value } );
							},
						} ),
						el( TextControl, {
							label: __( 'Đường dẫn Spotify', 'nntm' ),
							help: __( 'Để trống thì không hiện biểu tượng. Hàng biểu tượng nằm dưới cùng khối.', 'nntm' ),
							type: 'url',
							placeholder: 'https://open.spotify.com/…',
							value: attributes.spotifyUrl || '',
							onChange: function ( value ) {
								setAttributes( { spotifyUrl: value } );
							},
						} ),
						el( TextControl, {
							label: __( 'Đường dẫn YouTube', 'nntm' ),
							help: __( 'Để trống thì không hiện biểu tượng.', 'nntm' ),
							type: 'url',
							placeholder: 'https://www.youtube.com/…',
							value: attributes.youtubeUrl || '',
							onChange: function ( value ) {
								setAttributes( { youtubeUrl: value } );
							},
						} ),
						el( TextControl, {
							label: __( 'Đường dẫn Apple Music', 'nntm' ),
							help: __( 'Để trống thì không hiện biểu tượng Apple Music.', 'nntm' ),
							type: 'url',
							placeholder: 'https://music.apple.com/…',
							value: attributes.appleMusicUrl || '',
							onChange: function ( value ) {
								setAttributes( { appleMusicUrl: value } );
							},
						} ),



						'carousel' === attributes.layout
							? el( ToggleControl, {
									label: __( 'Tự động chạy băng cuộn', 'nntm' ),
									help: __( 'Băng thẻ tự chuyển sang thẻ kế tiếp, hết thẻ cuối quay lại đầu. Tự tạm dừng khi khách rê chuột vào hoặc bấm chọn trong băng.', 'nntm' ),
									checked: attributes.autoplay !== false,
									onChange: function ( value ) {
										setAttributes( { autoplay: value } );
									},
							  } )
							: null,
						'carousel' === attributes.layout && attributes.autoplay !== false
							? el( RangeControl, {
									label: __( 'Mỗi bao nhiêu giây chuyển một lần', 'nntm' ),
									value: attributes.autoplayInterval || 6,
									onChange: function ( value ) {
										setAttributes( { autoplayInterval: value || 6 } );
									},
									min: 2,
									max: 20,
							  } )
							: null,

						'grid' === ( attributes.layout || 'grid' )
							? el( SelectControl, {
									label: __( 'Số cột mỗi hàng', 'nntm' ),
									value: attributes.columns,
									options: COLUMN_OPTIONS,
									onChange: function ( value ) {
										setAttributes( { columns: parseInt( value, 10 ) || 3 } );
									},
							  } )
							: null,
						el( RangeControl, {
							label: __( 'Hiển thị bao nhiêu bài', 'nntm' ),
							value: attributes.postsPerPage,
							onChange: function ( value ) {
								setAttributes( { postsPerPage: value || 6 } );
							},
							min: 1,
							max: 24,
						} ),
						el( SelectControl, {
							label: __( 'Sắp xếp bài viết', 'nntm' ),
							value: attributes.orderBy,
							options: ORDER_BY_OPTIONS,
							onChange: function ( value ) {
								setAttributes( { orderBy: value } );
							},
						} ),
						'manual' === attributes.orderBy
							? el( TextControl, {
									label: __( 'Thứ tự thủ công', 'nntm' ),
									help: __( 'Nhập ID bài viết theo đúng thứ tự muốn hiển thị, cách nhau bằng dấu phẩy. Ví dụ: 12,45,78. Xem ID bài viết trên đường link (URL) khi sửa bài.', 'nntm' ),
									value: attributes.manualOrderIds,
									onChange: function ( value ) {
										setAttributes( { manualOrderIds: value } );
									},
							  } )
							: null,
						el( TextControl, {
							type: 'number',
							label: __( 'Loại trừ bài có ID', 'nntm' ),
							help: __( 'Để 0 nếu không loại trừ bài nào. Dùng cho dải "Bài viết liên quan" ở trang chi tiết, tránh bài đang xem tự liệt kê chính nó.', 'nntm' ),
							value: attributes.excludePostId || 0,
							onChange: function ( value ) {
								setAttributes( { excludePostId: parseInt( value, 10 ) || 0 } );
							},
						} ),

						'grid' === ( attributes.layout || 'grid' )
							? el( ToggleControl, {
									label: __( 'Hiện nút chuyển trang (BACK / NEXT)', 'nntm' ),
									checked: !! attributes.showPaging,
									onChange: function ( value ) {
										setAttributes( { showPaging: value } );
									},
							  } )
							: null
					)
				),
				el( RichText, {
					tagName: 'h2',
					className: 'nntm-card-list__heading',
					value: attributes.heading,
					placeholder: __( 'Nhập tiêu đề mục…', 'nntm' ),
					onChange: function ( value ) {
						setAttributes( { heading: value } );
					},
				} ),
				el( RichText, {
					tagName: 'p',
					className: 'nntm-card-list__subheading',
					value: attributes.subheading,
					placeholder: __( 'Nhập mô tả phụ (không bắt buộc)…', 'nntm' ),
					onChange: function ( value ) {
						setAttributes( { subheading: value } );
					},
				} ),
				el( ServerSideRender, {
					block: 'nntm/card-list',
					attributes: previewAttributes,
				} )
			);
		},
		save: function () {

			return null;
		},
	} );
} )( window.wp );
