 
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
	var RangeControl = wp.components.RangeControl;
	var apiFetch = wp.apiFetch;
	var ServerSideRender = wp.serverSideRender && wp.serverSideRender.default ? wp.serverSideRender.default : wp.serverSideRender;


	var TAXONOMY_REST_BASE = 'nntm_section';

	registerBlockType( 'nntm/term-list', {
		edit: function ( props ) {
			var attributes = props.attributes;
			var setAttributes = props.setAttributes;
			var blockProps = useBlockProps();

			var termState = useState( [] ); 
			var terms = termState[ 0 ];
			var setTerms = termState[ 1 ];

			var loadingState = useState( true );
			var isLoading = loadingState[ 0 ];
			var setIsLoading = loadingState[ 1 ];



			useEffect( function () {
				var isCurrent = true;

				apiFetch( {
					path: '/wp/v2/' + TAXONOMY_REST_BASE + '?per_page=100&orderby=name&order=asc&_fields=id,name,parent',
				} )
					.then( function ( result ) {
						if ( isCurrent ) {
							setTerms( result || [] );
							setIsLoading( false );
						}
					} )
					.catch( function () {
						if ( isCurrent ) {
							setTerms( [] );
							setIsLoading( false );
						}
					} );

				return function () {
					isCurrent = false;
				};
			}, [] );

			var parentOptions = [ { label: __( '— Chọn phân mục cha —', 'nntm' ), value: 0 } ].concat(
				terms.map( function ( term ) {

					var prefix = term.parent ? '— ' : '';
					return { label: prefix + term.name, value: term.id };
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
						{ title: __( 'Nguồn dữ liệu', 'nntm' ), initialOpen: true },
						el( SelectControl, {
							label: __( 'Phân mục cha', 'nntm' ),
							help: isLoading
								? __( 'Đang tải danh sách phân mục…', 'nntm' )
								: __( 'Khối sẽ hiển thị các phân mục con của lựa chọn này.', 'nntm' ),
							value: attributes.parentTermId,
							options: parentOptions,
							onChange: function ( value ) {
								setAttributes( { parentTermId: parseInt( value, 10 ) || 0 } );
							},
						} )
					),
					el(
						PanelBody,
						{ title: __( 'Hiển thị', 'nntm' ), initialOpen: true },
						el( SelectControl, {
							label: __( 'Kiểu bố cục', 'nntm' ),
							value: attributes.layout || 'overlay',
							options: [
								{ label: __( 'Thẻ phủ chữ trên ảnh', 'nntm' ), value: 'overlay' },
								{ label: __( 'Pháp Tòa – ảnh trên, chữ dưới', 'nntm' ), value: 'phap-toa' },
							],
							onChange: function ( value ) { setAttributes( { layout: value } ); },
						} ),
						el( ToggleControl, {
							label: __( 'Hiện mô tả trên thẻ', 'nntm' ),
							checked: !! attributes.showDescription,
							onChange: function ( value ) {
								setAttributes( { showDescription: value } );
							},
						} ),
						el( TextControl, {
							label: __( 'Nhãn nút trên thẻ', 'nntm' ),
							value: attributes.ctaLabel,
							onChange: function ( value ) {
								setAttributes( { ctaLabel: value } );
							},
						} ),
						el( RangeControl, {
							label: __( 'Hiển thị tối đa bao nhiêu phân mục con', 'nntm' ),
							value: attributes.maxItems,
							onChange: function ( value ) {
								setAttributes( { maxItems: value || 8 } );
							},
							min: 1,
							max: 20,
						} )
					),
					attributes.layout === 'phap-toa' ? el(
						PanelBody,
						{ title: __( 'Tự động chạy slider', 'nntm' ), initialOpen: true },
						el( ToggleControl, {
							label: __( 'Tự động chuyển từng thẻ', 'nntm' ),
							checked: attributes.autoplay !== false,
							onChange: function ( value ) { setAttributes( { autoplay: value } ); },
						} ),
						el( RangeControl, {
							label: __( 'Thời gian mỗi thẻ (giây)', 'nntm' ),
							value: attributes.interval || 5,
							min: 2,
							max: 20,
							onChange: function ( value ) { setAttributes( { interval: value || 5 } ); },
						} )
					)
					: null
				),
				el( RichText, {
					tagName: 'h2',
					className: 'nntm-term-list__heading',
					value: attributes.heading,
					placeholder: __( 'Nhập tiêu đề mục…', 'nntm' ),
					onChange: function ( value ) {
						setAttributes( { heading: value } );
					},
				} ),
				el( ServerSideRender, {
					block: 'nntm/term-list',
					attributes: previewAttributes,
				} )
			);
		},
		save: function () {

			return null;
		},
	} );
} )( window.wp );
