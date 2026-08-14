/**
 * Editor script cho block nntm/article-mosaic — JavaScript thuần, không
 * build. Bắt chước y hệt phong cách blocks/article-rows/editor.js: cùng
 * cơ chế REST tầng nối tầng (postType -> taxonomy -> term) để bảng điều
 * khiển hiện danh sách thả xuống tên tiếng Việt, không bắt nhập số ID.
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
	var RichText = wp.blockEditor.RichText;
	var PanelBody = wp.components.PanelBody;
	var SelectControl = wp.components.SelectControl;
	var ToggleControl = wp.components.ToggleControl;
	var TextControl = wp.components.TextControl;
	var FormTokenField = wp.components.FormTokenField;
	var apiFetch = wp.apiFetch;
	var ServerSideRender = wp.serverSideRender && wp.serverSideRender.default ? wp.serverSideRender.default : wp.serverSideRender;

	// Danh sách trắng loại nội dung — trùng với block.json và render.php.
	var POST_TYPE_OPTIONS = [
		{ label: __( 'Tin Tức / Hoằng Pháp', 'nntm' ), value: 'post' },
		{ label: __( 'Bài viết (6 phân mục)', 'nntm' ), value: 'nntm_article' },
		{ label: __( 'Ấn phẩm (PDF / Books)', 'nntm' ), value: 'nntm_publication' },
		{ label: __( 'Pháp Thoại', 'nntm' ), value: 'nntm_talk' },
		{ label: __( 'Video', 'nntm' ), value: 'nntm_video' },
	];

	var ORDER_BY_OPTIONS = [
		{ label: __( 'Mới nhất trước', 'nntm' ), value: 'newest' },
		{ label: __( 'Cũ nhất trước', 'nntm' ), value: 'oldest' },
		{ label: __( 'Theo tên (A-Z)', 'nntm' ), value: 'title' },
		{ label: __( 'Tự chọn thứ tự từng bài', 'nntm' ), value: 'manual' },
	];

	var SECONDARY_LAYOUT_OPTIONS = [
		{ label: __( '2 thẻ vừa + 3 thẻ nhỏ (6 bài)', 'nntm' ), value: 'mosaic' },
		{ label: __( 'Lưới 3 cột x 2 hàng, 6 thẻ bằng nhau (7 bài)', 'nntm' ), value: 'grid' },
	];

	var LEAD_MEDIA_OPTIONS = [
		{ label: __( 'Ảnh cao (Hoằng Pháp)', 'nntm' ), value: 'tall' },
		{ label: __( 'Ảnh thấp (Tin Tức)', 'nntm' ), value: 'short' },
	];

	// Nhãn tiếng Việt cho taxonomy — khớp với class-taxonomies.php.
	var TAXONOMY_LABELS = {
		nntm_section: __( 'Phân mục', 'nntm' ),
		nntm_topic: __( 'Chủ đề', 'nntm' ),
		nntm_series: __( 'Bộ / Series', 'nntm' ),
		category: __( 'Chuyên mục', 'nntm' ),
		post_tag: __( 'Thẻ', 'nntm' ),
	};

	// Taxonomy lõi của WordPress có rest_base khác tên taxonomy.
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

	// REST tra tieu de dang HTML da ma hoa ("Vi sao &#8230;"). Giai ma bang
	// textarea de o chon hien dung chu nhu tren trang.
	function plainTitle( post ) {
		var raw = ( post && post.title && post.title.rendered ) || '';
		var box = document.createElement( 'textarea' );
		box.innerHTML = raw;
		return box.value.trim() || __( '(bài không có tiêu đề)', 'nntm' );
	}

	// "28,29,30" -> [ 28, 29, 30 ]. Cat theo moi ky tu khong phai so, giong
	// het cach render.php doc lai chuoi nay — hai ben phai hieu nhu nhau.
	function parseIdList( value ) {
		return String( value || '' )
			.split( /[^0-9]+/ )
			.map( function ( one ) {
				return parseInt( one, 10 );
			} )
			.filter( function ( one ) {
				return one > 0;
			} );
	}

	registerBlockType( 'nntm/article-mosaic', {
		edit: function ( props ) {
			var attributes = props.attributes;
			var setAttributes = props.setAttributes;
			var blockProps = useBlockProps();

			var taxonomyState = useState( [] ); // danh sách taxonomy hợp lệ cho postType hiện tại
			var availableTaxonomies = taxonomyState[ 0 ];
			var setAvailableTaxonomies = taxonomyState[ 1 ];

			var termState = useState( [] ); // danh sách term của taxonomy đang chọn
			var availableTerms = termState[ 0 ];
			var setAvailableTerms = termState[ 1 ];

			var restBaseState = useState( 'posts' ); // rest_base của postType hiện tại
			var postRestBase = restBaseState[ 0 ];
			var setPostRestBase = restBaseState[ 1 ];

			var candidateState = useState( [] ); // [{ id, title }] để chọn thứ tự thủ công
			var candidatePosts = candidateState[ 0 ];
			var setCandidatePosts = candidateState[ 1 ];

			// Khi đổi loại nội dung: hỏi REST xem loại đó gắn được taxonomy nào.
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
							// rest_base khác tên post type ở lõi WP (post -> posts).
							setPostRestBase( ( typeInfo && typeInfo.rest_base ) || 'posts' );

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

			// Khi đổi taxonomy (hoặc loại nội dung làm mất taxonomy cũ): tải danh sách term.
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

			// Tầng thứ ba: tải danh sách bài thật để ô "Tự chọn thứ tự" gợi ý
			// theo TÊN BÀI. Ban quản trị không phải đi tra số ID ở đâu khác —
			// đúng ràng buộc "admin sửa được, không cần lập trình viên"
			// (docs/04-kien-truc.md mục 0.3). Lọc theo đúng nguồn nội dung mà
			// khối đang dùng nên danh sách gợi ý luôn sát với bài sẽ hiện ra.
			useEffect(
				function () {
					var isCurrent = true;

					var path = '/wp/v2/' + postRestBase + '?per_page=100&orderby=date&order=desc&_fields=id,title';
					if ( attributes.taxonomy && attributes.termId ) {
						path += '&' + restBaseFor( attributes.taxonomy ) + '=' + attributes.termId;
					}

					apiFetch( { path: path } )
						.then( function ( posts ) {
							if ( isCurrent ) {
								setCandidatePosts(
									( posts || [] ).map( function ( one ) {
										return { id: one.id, title: plainTitle( one ) };
									} )
								);
							}
						} )
						.catch( function () {
							if ( isCurrent ) {
								setCandidatePosts( [] );
							}
						} );

					return function () {
						isCurrent = false;
					};
				},
				[ postRestBase, attributes.taxonomy, attributes.termId ]
			);

			// Hai bảng tra ngược nhau giữa ID (thứ lưu vào block) và tiêu đề
			// (thứ ban quản trị nhìn thấy).
			var titleById = {};
			var idByTitle = {};
			candidatePosts.forEach( function ( one ) {
				titleById[ one.id ] = one.title;
				if ( ! idByTitle[ one.title ] ) {
					idByTitle[ one.title ] = one.id; // trùng tên thì giữ bài mới nhất
				}
			} );

			var manualIds = parseIdList( attributes.manualOrderIds );

			// Bài nằm ngoài bộ lọc hiện tại vẫn giữ nguyên dạng "#28" thay vì
			// biến mất — đổi bộ lọc rồi đổi lại không được làm mất thứ tự đã chọn.
			var manualTokens = manualIds.map( function ( id ) {
				return titleById[ id ] || '#' + id;
			} );

			var manualSuggestions = candidatePosts
				.map( function ( one ) {
					return one.title;
				} )
				.filter( function ( title ) {
					return manualTokens.indexOf( title ) === -1;
				} );

			function onManualOrderChange( tokens ) {
				var ids = [];
				tokens.forEach( function ( token ) {
					var text = String( token ).trim();
					var id = idByTitle[ text ];
					if ( ! id ) {
						// Thẻ dạng "#28" của bài ngoài bộ lọc, hoặc người dùng gõ thẳng số.
						id = parseInt( text.replace( /^#/, '' ), 10 );
					}
					if ( id > 0 && ids.indexOf( id ) === -1 ) {
						ids.push( id );
					}
				} );
				setAttributes( { manualOrderIds: ids.join( ',' ) } );
			}

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

			// Danh sách chọn "Ghim tay một bài lên ô lớn" — cùng nguồn dữ liệu
			// candidatePosts đã tải cho ô "Tự chọn thứ tự" ở dưới. Bài đã ghim
			// nằm ngoài bộ lọc hiện tại vẫn hiện dạng "#28" thay vì biến mất.
			var pinnedPostOptions = [ { label: __( '— Tự động (mới nhất) —', 'nntm' ), value: 0 } ].concat(
				candidatePosts.map( function ( one ) {
					return { label: one.title, value: one.id };
				} )
			);
			if ( attributes.pinnedPostId && candidatePosts.every( function ( one ) { return one.id !== attributes.pinnedPostId; } ) ) {
				pinnedPostOptions.push( {
					label: __( 'Bài số ', 'nntm' ) + attributes.pinnedPostId + __( ' (ngoài bộ lọc hiện tại)', 'nntm' ),
					value: attributes.pinnedPostId,
				} );
			}

			var previewAttributes = Object.assign( {}, attributes, { heading: '' } ); // tranh hien tieu de 2 lan (RichText da hien o duoi)

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
							label: __( 'Lấy bài từ loại nội dung nào', 'nntm' ),
							value: attributes.postType,
							options: POST_TYPE_OPTIONS,
							onChange: function ( value ) {
								setAttributes( { postType: value, taxonomy: '', termId: 0 } );
							},
						} ),
						el( SelectControl, {
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
									label: __( 'Lấy bài từ mục nào', 'nntm' ),
									value: attributes.termId,
									options: termOptions,
									onChange: function ( value ) {
										setAttributes( { termId: parseInt( value, 10 ) || 0 } );
									},
							  } )
							: null,
						// Ghim tay MOT bai len o lon (yeu cau anh Uy 12/08/2026, muc M1.3).
						// Dung lai danh sach candidatePosts da tai theo dung nguon dang
						// chon o tren — admin khong phai di tra so ID o dau khac.
						'manual' !== attributes.orderBy
							? el( SelectControl, {
									label: __( 'Ghim tay một bài lên ô lớn', 'nntm' ),
									help: __( 'Bài này luôn hiện ở ô nổi bật, các ô còn lại vẫn tự lấy tin mới nhất. Để "Tự động" thì ô lớn cũng lấy bài mới nhất như các ô khác.', 'nntm' ),
									value: attributes.pinnedPostId || 0,
									options: pinnedPostOptions,
									onChange: function ( value ) {
										setAttributes( { pinnedPostId: parseInt( value, 10 ) || 0 } );
									},
							  } )
							: el(
									'p',
									{ className: 'components-base-control__help' },
									__( 'Không dùng ghim tay khi đang "Tự chọn thứ tự từng bài" — muốn ghim thì đặt bài đó lên đầu danh sách thủ công bên dưới.', 'nntm' )
							  )
					),
					el(
						PanelBody,
						{ title: __( 'Hiển thị', 'nntm' ), initialOpen: true },
						el( 'p', { className: 'components-base-control__help' }, __( 'Luôn lấy đúng 6 bài: bài 1 làm bài nổi bật, bài 2–3 là thẻ vừa, bài 4–6 là thẻ nhỏ. Thiếu bài thì tự rút gọn bố cục, không vỡ.', 'nntm' ) ),
						el( SelectControl, {
							label: __( 'Kiểu ảnh bài nổi bật', 'nntm' ),
							help: __( 'Ảnh cao dùng cho Hoằng Pháp, ảnh thấp dùng cho Tin Tức.', 'nntm' ),
							value: attributes.leadMedia,
							options: LEAD_MEDIA_OPTIONS,
							onChange: function ( value ) {
								setAttributes( { leadMedia: value } );
							},
						} ),
						el( SelectControl, {
							label: __( 'Bố cục cột phải', 'nntm' ),
							help: __( 'Lưới 3x2 dùng cho khối "Hoạt động - Sự kiện" và lấy 7 bài; kiểu còn lại lấy 6 bài.', 'nntm' ),
							value: attributes.secondaryLayout || 'mosaic',
							options: SECONDARY_LAYOUT_OPTIONS,
							onChange: function ( value ) {
								setAttributes( { secondaryLayout: value } );
							},
						} ),
						el( SelectControl, {
							label: __( 'Sắp xếp bài viết', 'nntm' ),
							value: attributes.orderBy,
							options: ORDER_BY_OPTIONS,
							onChange: function ( value ) {
								setAttributes( { orderBy: value } );
							},
						} ),
						// Chỉ hiện khi chọn "Tự chọn thứ tự từng bài". Bắt buộc phải
						// có ô này: thiếu nó thì thuộc tính manualOrderIds trong
						// block.json không có cách nào sửa từ giao diện.
						'manual' === attributes.orderBy
							? el(
									wp.element.Fragment,
									{},
									el( FormTokenField, {
										label: __( 'Chọn bài và thứ tự hiển thị', 'nntm' ),
										value: manualTokens,
										suggestions: manualSuggestions,
										onChange: onManualOrderChange,
										__experimentalExpandOnFocus: true,
										__nextHasNoMarginBottom: true,
									} ),
									el(
										'p',
										{ className: 'components-base-control__help' },
										__( 'Bài thứ 1 là bài nổi bật cột trái, bài 2–3 là hai thẻ vừa, bài 4–6 là ba thẻ nhỏ. Muốn đổi vị trí thì xoá thẻ đó rồi thêm lại — thẻ mới luôn nối vào cuối.', 'nntm' )
									)
							  )
							: null,
						el( ToggleControl, {
							label: __( 'Hiện nhãn chuyên mục', 'nntm' ),
							checked: !! attributes.showCategory,
							onChange: function ( value ) {
								setAttributes( { showCategory: value } );
							},
						} ),
						el( ToggleControl, {
							label: __( 'Hiện ngày cập nhật', 'nntm' ),
							checked: !! attributes.showDate,
							onChange: function ( value ) {
								setAttributes( { showDate: value } );
							},
						} ),
						el( ToggleControl, {
							label: __( 'Hiện đoạn mô tả ngắn (chỉ bài nổi bật)', 'nntm' ),
							checked: !! attributes.showExcerpt,
							onChange: function ( value ) {
								setAttributes( { showExcerpt: value } );
							},
						} ),
						el( TextControl, {
							label: __( 'Nhãn liên kết dưới mỗi bài', 'nntm' ),
							help: __( 'Mặc định là “Xem thêm”. Để trống nếu không muốn hiện.', 'nntm' ),
							value: attributes.cardCtaLabel || '',
							onChange: function ( value ) {
								setAttributes( { cardCtaLabel: value } );
							},
						} ),
						// Nut "Xem Tat ca" o duoi cung khoi (Figma R4 SECTION 1).
						// Chi hien tren trang khi nhap DU ca nhan va duong dan.
						el( TextControl, {
							label: __( 'Nhãn nút "Xem Tất cả"', 'nntm' ),
							help: __( 'Để trống thì không hiện nút. Cần nhập cả nhãn và đường dẫn bên dưới.', 'nntm' ),
							value: attributes.viewAllLabel || '',
							onChange: function ( value ) {
								setAttributes( { viewAllLabel: value } );
							},
						} ),
						el( TextControl, {
							label: __( 'Đường dẫn nút "Xem Tất cả"', 'nntm' ),
							type: 'url',
							placeholder: 'https://…',
							value: attributes.viewAllUrl || '',
							onChange: function ( value ) {
								setAttributes( { viewAllUrl: value } );
							},
						} )
					)
				),
				el( RichText, {
					tagName: 'h2',
					className: 'nntm-article-mosaic__heading',
					value: attributes.heading,
					placeholder: __( 'Nhập tiêu đề mục…', 'nntm' ),
					onChange: function ( value ) {
						setAttributes( { heading: value } );
					},
				} ),
				el( ServerSideRender, {
					block: 'nntm/article-mosaic',
					attributes: previewAttributes,
				} )
			);
		},
		save: function () {
			// Block động: PHP (render.php) tự chạy lại WP_Query mỗi lần tải trang.
			return null;
		},
	} );
} )( window.wp );
