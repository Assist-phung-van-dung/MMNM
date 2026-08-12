/**
 * Editor script cho block nntm/term-list — JavaScript thuần, không build.
 * Dùng biến toàn cục wp.* theo đúng quy ước dự án (xem blocks/tru-xu-list/editor.js
 * và blocks/card-list/editor.js).
 *
 * Khối này liệt kê các TERM CON của một term cha trong taxonomy nntm_section.
 * Bảng điều khiển không bắt khách nhập ID: dùng wp.apiFetch lấy danh sách
 * term thật của nntm_section qua REST rồi hiển thị tên tiếng Việt trong
 * SelectControl, giống hệt cách blocks/card-list/editor.js lấy danh sách term.
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
	var RangeControl = wp.components.RangeControl;
	var apiFetch = wp.apiFetch;
	var ServerSideRender = wp.serverSideRender && wp.serverSideRender.default ? wp.serverSideRender.default : wp.serverSideRender;

	// Taxonomy nntm_section không khai báo "rest_base" riêng (xem
	// class-taxonomies.php) nên REST base mặc định trùng tên taxonomy.
	var TAXONOMY_REST_BASE = 'nntm_section';

	registerBlockType( 'nntm/term-list', {
		edit: function ( props ) {
			var attributes = props.attributes;
			var setAttributes = props.setAttributes;
			var blockProps = useBlockProps();

			var termState = useState( [] ); // danh sách term thật của nntm_section
			var terms = termState[ 0 ];
			var setTerms = termState[ 1 ];

			var loadingState = useState( true );
			var isLoading = loadingState[ 0 ];
			var setIsLoading = loadingState[ 1 ];

			// Tải danh sách term của nntm_section một lần khi mở khối —
			// dùng để dựng SelectControl chọn "term cha", không bắt khách
			// gõ ID tay.
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
					// Thụt lề nhẹ cho term con để dễ phân biệt cấp bậc trong danh sách thả xuống.
					var prefix = term.parent ? '— ' : '';
					return { label: prefix + term.name, value: term.id };
				} )
			);

			var previewAttributes = Object.assign( {}, attributes, { heading: '' } ); // tranh hien tieu de 2 lan (RichText da hien o duoi)

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
					)
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
			// Block động: PHP (render.php) tự chạy lại get_terms() mỗi lần tải trang.
			return null;
		},
	} );
} )( window.wp );
