 
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
	var apiFetch = wp.apiFetch;
	var ServerSideRender = wp.serverSideRender && wp.serverSideRender.default ? wp.serverSideRender.default : wp.serverSideRender;

	var POST_TYPE_OPTIONS = [
		{ label: __( 'Bài viết (6 phân mục)', 'nntm' ), value: 'nntm_article' },
		{ label: __( 'Ấn phẩm (PDF / Books)', 'nntm' ), value: 'nntm_publication' },
		{ label: __( 'Pháp Thoại', 'nntm' ), value: 'nntm_talk' },
		{ label: __( 'Video', 'nntm' ), value: 'nntm_video' },
		{ label: __( 'Tin Tức / Hoằng Pháp', 'nntm' ), value: 'post' },
	];

	var ORDER_BY_OPTIONS = [
		{ label: __( 'Mới nhất trước', 'nntm' ), value: 'newest' },
		{ label: __( 'Cũ nhất trước', 'nntm' ), value: 'oldest' },
		{ label: __( 'Theo tên (A-Z)', 'nntm' ), value: 'title' },
	];

	var START_SIDE_OPTIONS = [
		{ label: __( 'Hàng đầu: ảnh bên trái', 'nntm' ), value: 'left' },
		{ label: __( 'Hàng đầu: ảnh bên phải', 'nntm' ), value: 'right' },
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

	registerBlockType( 'nntm/article-rows', {
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

			var previewAttributes = Object.assign( {}, attributes, { heading: '' } ); 

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
							: null
					),
					el(
						PanelBody,
						{ title: __( 'Hiển thị', 'nntm' ), initialOpen: true },
						el( RangeControl, {
							label: __( 'Hiển thị bao nhiêu bài (mỗi bài 1 hàng)', 'nntm' ),
							value: attributes.postsPerPage,
							onChange: function ( value ) {
								setAttributes( { postsPerPage: value || 5 } );
							},
							min: 1,
							max: 12,
						} ),
						el( SelectControl, {
							label: __( 'Sắp xếp bài viết', 'nntm' ),
							value: attributes.orderBy,
							options: ORDER_BY_OPTIONS,
							onChange: function ( value ) {
								setAttributes( { orderBy: value } );
							},
						} ),
						el( SelectControl, {
							label: __( 'Hàng đầu tiên đặt ảnh bên nào', 'nntm' ),
							help: __( 'Các hàng tiếp theo tự đảo bên luân phiên.', 'nntm' ),
							value: attributes.startSide,
							options: START_SIDE_OPTIONS,
							onChange: function ( value ) {
								setAttributes( { startSide: value } );
							},
						} ),
						el( ToggleControl, {
							label: __( 'Hiện đoạn mô tả ngắn', 'nntm' ),
							checked: !! attributes.showExcerpt,
							onChange: function ( value ) {
								setAttributes( { showExcerpt: value } );
							},
						} ),
						el( ToggleControl, {
							label: __( 'Hiện nút Yêu thích', 'nntm' ),
							checked: !! attributes.showFavorite,
							onChange: function ( value ) {
								setAttributes( { showFavorite: value } );
							},
						} ),
						el( ToggleControl, {
							label: __( 'Hiện phân trang', 'nntm' ),
							help: __( 'Dùng khi block là danh sách chính của một trang. Nếu trang có nhiều block phân trang thì chúng dùng chung ?paged=.', 'nntm' ),
							checked: !! attributes.showPaging,
							onChange: function ( value ) {
								setAttributes( { showPaging: value } );
							},
						} ),
						el( TextControl, {
							label: __( 'Nhãn liên kết xem bài', 'nntm' ),
							value: attributes.secondaryCtaLabel,
							onChange: function ( value ) {
								setAttributes( { secondaryCtaLabel: value } );
							},
						} )
					)
				),
				el( RichText, {
					tagName: 'h2',
					className: 'nntm-article-rows__heading',
					value: attributes.heading,
					placeholder: __( 'Nhập tiêu đề mục…', 'nntm' ),
					onChange: function ( value ) {
						setAttributes( { heading: value } );
					},
				} ),
				el( ServerSideRender, {
					block: 'nntm/article-rows',
					attributes: previewAttributes,
				} )
			);
		},
		save: function () {

			return null;
		},
	} );
} )( window.wp );
