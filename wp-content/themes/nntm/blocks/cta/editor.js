/**
 * Editor script cho block nntm/cta — JavaScript thuần, không build.
 * Dùng biến toàn cục wp.blocks / wp.element / wp.blockEditor / wp.components
 * theo đúng quy ước dự án (chưa có bước build webpack/wp-scripts).
 *
 * Khác với nntm/card và nntm/card-list (dùng ServerSideRender vì hai block
 * đó lấy dữ liệu từ WP_Query/bài viết): nntm/cta KHÔNG phụ thuộc dữ liệu gì
 * từ máy chủ — nội dung nút chỉ là chữ + đường dẫn khách tự gõ. Nên ở đây
 * ta vẽ trực tiếp phần tử nút bằng RichText ngay trong khung soạn thảo,
 * dùng đúng class CSS như ngoài trang thật (style.css được nạp cho cả
 * editor lẫn front-end qua khai báo "style" trong block.json) để khách
 * "nhìn thấy đúng như trang thật" mà không cần gọi ngược về máy chủ.
 */
( function ( wp ) {
	'use strict';

	var el = wp.element.createElement;
	var __ = wp.i18n.__;
	var registerBlockType = wp.blocks.registerBlockType;
	var useBlockProps = wp.blockEditor.useBlockProps;
	var InspectorControls = wp.blockEditor.InspectorControls;
	var RichText = wp.blockEditor.RichText;
	var PanelBody = wp.components.PanelBody;
	var SelectControl = wp.components.SelectControl;
	var ToggleControl = wp.components.ToggleControl;
	var TextControl = wp.components.TextControl;

	// Nhãn tiếng Việt — "value" PHẢI trùng tên biến thể trong Figma
	// (component set CTA, node 6134:2330). HOVER / GHOST HOVER / TEXT HOVER
	// không có mặt ở đây vì đó là trạng thái rê chuột của Default/Ghost/CTA
	// Text, không phải biến thể người dùng tự chọn — xem ghi chú ở style.css.
	var VARIANT_OPTIONS = [
		{ label: __( 'Nút chính (Default)', 'nntm' ), value: 'default' },
		{ label: __( 'Nút viền rỗng (Ghost)', 'nntm' ), value: 'ghost' },
		{ label: __( 'Chữ có gạch dạng nút (CTA Text)', 'nntm' ), value: 'cta-text' },
		{ label: __( 'Nút Yêu thích (Fav Button)', 'nntm' ), value: 'fav-button' },
	];

	// Chữ mặc định gợi ý theo từng biến thể — chỉ tự động thay chữ khi
	// khách CHƯA sửa gì (giữ đúng chữ mặc định của biến thể cũ), tránh mất
	// nội dung khách đã gõ khi họ đổi qua lại giữa các biến thể.
	var VARIANT_DEFAULT_TEXT = {
		default: __( 'Xem thêm', 'nntm' ),
		ghost: __( 'Xem thêm', 'nntm' ),
		'cta-text': __( 'Xem thêm', 'nntm' ),
		'fav-button': __( 'Yêu thích', 'nntm' ),
	};

	registerBlockType( 'nntm/cta', {
		edit: function ( props ) {
			var attributes = props.attributes;
			var setAttributes = props.setAttributes;
			var variant = attributes.variant || 'default';
			var isFav = 'fav-button' === variant;
			var blockProps = useBlockProps( {
				className: 'nntm-cta nntm-cta--' + variant,
				'aria-pressed': isFav ? ( attributes.favorited ? 'true' : 'false' ) : undefined,
			} );

			return el(
				'div',
				blockProps,
				el(
					InspectorControls,
					{},
					el(
						PanelBody,
						{ title: __( 'Cài đặt nút CTA', 'nntm' ), initialOpen: true },
						el( SelectControl, {
							label: __( 'Kiểu nút', 'nntm' ),
							value: variant,
							options: VARIANT_OPTIONS,
							onChange: function ( value ) {
								var nextAttrs = { variant: value };
								if ( attributes.text === VARIANT_DEFAULT_TEXT[ variant ] ) {
									nextAttrs.text = VARIANT_DEFAULT_TEXT[ value ];
								}
								setAttributes( nextAttrs );
							},
						} ),
						! isFav &&
							el( TextControl, {
								label: __( 'Đường dẫn (URL)', 'nntm' ),
								help: __( 'Để trống thì nút hiển thị dạng <button> (dùng cho hành động JavaScript), có đường dẫn thì hiển thị dạng liên kết <a>.', 'nntm' ),
								value: attributes.url,
								onChange: function ( value ) {
									setAttributes( { url: value } );
								},
							} ),
						! isFav && attributes.url
							? el( ToggleControl, {
									label: __( 'Mở liên kết ở tab mới', 'nntm' ),
									checked: !! attributes.opensInNewTab,
									onChange: function ( value ) {
										setAttributes( { opensInNewTab: value } );
									},
							  } )
							: null,
						el( TextControl, {
							label: __( 'Nhãn trợ năng (aria-label)', 'nntm' ),
							help: __( 'Chỉ cần khi chữ hiển thị trên nút chưa đủ rõ nghĩa cho phần mềm đọc màn hình.', 'nntm' ),
							value: attributes.ariaLabel,
							onChange: function ( value ) {
								setAttributes( { ariaLabel: value } );
							},
						} ),
						isFav &&
							el( TextControl, {
								type: 'number',
								label: __( 'ID nội dung để lưu yêu thích', 'nntm' ),
								help: __( 'ID bài viết / ấn phẩm / pháp thoại... sẽ gắn vào nút để phần lưu dữ liệu (bảng wp_nntm_favorites, làm ở việc khác) nối vào sau. Chưa lưu được gì ở bước này.', 'nntm' ),
								value: attributes.objectId || '',
								onChange: function ( value ) {
									setAttributes( { objectId: parseInt( value, 10 ) || 0 } );
								},
							} ),
						isFav &&
							el( ToggleControl, {
								label: __( 'Xem trước trạng thái đã yêu thích', 'nntm' ),
								help: __( 'Chỉ đổi hình để xem trước trong khung soạn thảo — chưa nối với dữ liệu thật của từng thành viên.', 'nntm' ),
								checked: !! attributes.favorited,
								onChange: function ( value ) {
									setAttributes( { favorited: value } );
								},
							} )
					)
				),
				isFav
					? el( 'span', { className: 'nntm-cta__icon', 'aria-hidden': 'true' } )
					: null,
				el( RichText, {
					tagName: 'span',
					className: 'nntm-cta__label',
					value: attributes.text,
					placeholder: __( 'Nhập chữ trên nút…', 'nntm' ),
					allowedFormats: [],
					onChange: function ( value ) {
						setAttributes( { text: value } );
					},
				} )
			);
		},
		save: function () {
			// Block động: PHP (render.php) tự dựng thẻ <a>/<button> mỗi lần tải trang.
			return null;
		},
	} );
} )( window.wp );
