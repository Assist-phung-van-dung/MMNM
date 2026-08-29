 
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



	var VARIANT_OPTIONS = [
		{ label: __( 'Nút chính (Default)', 'nntm' ), value: 'default' },
		{ label: __( 'Nút viền rỗng (Ghost)', 'nntm' ), value: 'ghost' },
		{ label: __( 'Chữ có gạch dạng nút (CTA Text)', 'nntm' ), value: 'cta-text' },
		{ label: __( 'Nút Yêu thích (Fav Button)', 'nntm' ), value: 'fav-button' },
	];



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
				/*
				 * Ve dung trai tim nhu render.php.
				 *
				 * Truoc day cho nay chi la mot the <span> rong. Ma style.css lai dat
				 * kich thuoc qua `.nntm-cta__icon svg` — khong co <svg> thi o icon co
				 * be bang 0: trong admin nut Yeu thich mat hut trai tim, va nut gat
				 * "Xem truoc trang thai da yeu thich" o thanh ben khong doi gi ca vi
				 * mau to nam o `[aria-pressed="true"] .nntm-cta__icon svg path`.
				 */
				isFav
					? el(
							'span',
							{ className: 'nntm-cta__icon', 'aria-hidden': 'true' },
							el(
								'svg',
								{
									viewBox: '0 0 23 21',
									width: 23,
									height: 21,
									xmlns: 'http://www.w3.org/2000/svg',
									focusable: 'false',
								},
								el( 'path', {
									d: 'M11.5 19.3C7.9 16.6 2 12 2 7.3 2 4.4 4.3 2 7.2 2c1.8 0 3.4.9 4.3 2.3C12.4 2.9 14 2 15.8 2 18.7 2 21 4.4 21 7.3c0 4.7-5.9 9.3-9.5 12z',
									fill: 'none',
									stroke: 'currentColor',
									strokeWidth: '2',
									strokeLinejoin: 'round',
								} )
							)
					  )
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

			return null;
		},
	} );
} )( window.wp );
