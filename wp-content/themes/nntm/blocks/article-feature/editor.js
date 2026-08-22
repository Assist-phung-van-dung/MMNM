 
( function ( wp ) {
	'use strict';

	var el = wp.element.createElement;
	var useState = wp.element.useState;
	var useEffect = wp.element.useEffect;
	var __ = wp.i18n.__;
	var registerBlockType = wp.blocks.registerBlockType;
	var useBlockProps = wp.blockEditor.useBlockProps;
	var InspectorControls = wp.blockEditor.InspectorControls;
	var PanelBody = wp.components.PanelBody;
	var SelectControl = wp.components.SelectControl;
	var ToggleControl = wp.components.ToggleControl;
	var TextControl = wp.components.TextControl;
	var RangeControl = wp.components.RangeControl;
	var apiFetch = wp.apiFetch;
	var ServerSideRender = wp.serverSideRender && wp.serverSideRender.default ? wp.serverSideRender.default : wp.serverSideRender;

	var POST_TYPE_OPTIONS = [
		{ label: __( 'Tin Tức / Hoằng Pháp', 'nntm' ), value: 'post' },
		{ label: __( 'Bài viết (6 phân mục)', 'nntm' ), value: 'nntm_article' },
		{ label: __( 'Ấn phẩm (PDF / Books)', 'nntm' ), value: 'nntm_publication' },
		{ label: __( 'Pháp Thoại', 'nntm' ), value: 'nntm_talk' },
	];

	var MEDIA_POSITION_OPTIONS = [
		{ label: __( 'Ảnh bên phải', 'nntm' ), value: 'right' },
		{ label: __( 'Ảnh bên trái', 'nntm' ), value: 'left' },
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


	function plainTitle( post ) {
		var raw = ( post && post.title && post.title.rendered ) || '';
		var box = document.createElement( 'textarea' );
		box.innerHTML = raw;
		return box.value.trim() || __( '(bài không có tiêu đề)', 'nntm' );
	}

	registerBlockType( 'nntm/article-feature', {
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

			var restBaseState = useState( 'posts' );
			var postRestBase = restBaseState[ 0 ];
			var setPostRestBase = restBaseState[ 1 ];

			var postsState = useState( [] );
			var availablePosts = postsState[ 0 ];
			var setAvailablePosts = postsState[ 1 ];

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
								setAvailablePosts(
									( posts || [] ).map( function ( one ) {
										return { id: one.id, title: plainTitle( one ) };
									} )
								);
							}
						} )
						.catch( function () {
							if ( isCurrent ) {
								setAvailablePosts( [] );
							}
						} );

					return function () {
						isCurrent = false;
					};
				},
				[ postRestBase, attributes.taxonomy, attributes.termId ]
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



			var postOptions = [ { label: __( '— Tự lấy bài mới nhất —', 'nntm' ), value: 0 } ].concat(
				availablePosts.map( function ( one ) {
					return { label: one.title, value: one.id };
				} )
			);
			var daBietBaiDaChon = availablePosts.some( function ( one ) {
				return one.id === attributes.postId;
			} );
			if ( attributes.postId && ! daBietBaiDaChon ) {
				postOptions.push( {
					label: __( 'Bài số ', 'nntm' ) + attributes.postId + __( ' (ngoài bộ lọc hiện tại)', 'nntm' ),
					value: attributes.postId,
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
						{ title: __( 'Lấy bài từ đâu', 'nntm' ), initialOpen: true },
						el( SelectControl, {
							label: __( 'Lấy bài từ loại nội dung nào', 'nntm' ),
							value: attributes.postType,
							options: POST_TYPE_OPTIONS,
							onChange: function ( value ) {
								setAttributes( { postType: value, taxonomy: '', termId: 0, postId: 0 } );
							},
						} ),
						el( SelectControl, {
							label: __( 'Lọc theo', 'nntm' ),
							help: __( 'Thu hẹp danh sách bài bên dưới. Để trống nếu muốn chọn từ tất cả.', 'nntm' ),
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
						el( SelectControl, {
							label: __( 'Bài hiển thị', 'nntm' ),
							help: __( 'Chọn đúng một bài. Để "Tự lấy bài mới nhất" thì khối luôn hiện bài mới nhất của nguồn ở trên, không phải sửa lại trang chủ mỗi lần đăng bài.', 'nntm' ),
							value: attributes.postId,
							options: postOptions,
							onChange: function ( value ) {
								setAttributes( { postId: parseInt( value, 10 ) || 0 } );
							},
						} )
					),
					el(
						PanelBody,
						{ title: __( 'Hiển thị', 'nntm' ), initialOpen: true },
						el( SelectControl, {
							label: __( 'Vị trí ảnh', 'nntm' ),
							value: attributes.mediaPosition,
							options: MEDIA_POSITION_OPTIONS,
							onChange: function ( value ) {
								setAttributes( { mediaPosition: value } );
							},
						} ),
						el( ToggleControl, {
							label: __( 'Hiện dấu ngoặc kép lớn', 'nntm' ),
							checked: !! attributes.showQuoteMark,
							onChange: function ( value ) {
								setAttributes( { showQuoteMark: value } );
							},
						} ),
						el( RangeControl, {
							label: __( 'Hiện mấy đoạn đầu của bài', 'nntm' ),
							help: __( 'Phần còn lại người đọc xem tiếp trong trang bài viết.', 'nntm' ),
							value: attributes.maxParagraphs,
							min: 1,
							max: 30,
							onChange: function ( value ) {
								setAttributes( { maxParagraphs: parseInt( value, 10 ) || 1 } );
							},
						} ),
						el( TextControl, {
							label: __( 'Dòng trích nguồn (in nghiêng)', 'nntm' ),
							help: __( 'Để trống thì tự lấy phần tóm tắt của chính bài viết — sửa trong màn hình sửa bài. Nhập vào đây khi muốn đặt câu khác cho riêng chỗ này.', 'nntm' ),
							value: attributes.attribution || '',
							onChange: function ( value ) {
								setAttributes( { attribution: value } );
							},
						} ),
						el( TextControl, {
							label: __( 'Nhãn nút xem tiếp', 'nntm' ),
							value: attributes.ctaLabel,
							onChange: function ( value ) {
								setAttributes( { ctaLabel: value } );
							},
						} )
					)
				),
				el( ServerSideRender, {
					block: 'nntm/article-feature',
					attributes: attributes,
				} )
			);
		},
		save: function () {

			return null;
		},
	} );
} )( window.wp );
