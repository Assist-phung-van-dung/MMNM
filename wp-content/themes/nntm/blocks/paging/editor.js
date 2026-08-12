/**
 * Editor script cho block nntm/paging — JavaScript thuần, không build.
 * Dùng biến toàn cục wp.blocks / wp.element / wp.blockEditor / wp.components
 * theo đúng quy ước dự án (chưa có bước build webpack/wp-scripts).
 *
 * Không dùng ServerSideRender ở đây: nút BACK/NEXT thật sự có hiện ra hay
 * không phụ thuộc vào truy vấn CHÍNH của trang (global $wp_query — xem
 * render.php), mà trong khung soạn thảo (REST request riêng) không có
 * truy vấn danh sách bài thật để so sánh — gọi ServerSideRender ở đây sẽ
 * chỉ luôn ra rỗng, không giúp ích gì cho việc canh layout. Vì vậy khung
 * soạn thảo LUÔN hiện đủ hai nút (để khách canh vị trí/màu/chữ), còn việc
 * ẩn nút khi không có trang trước/sau chỉ xảy ra thật ở ngoài trang.
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

	var ALIGNMENT_OPTIONS = [
		{ label: __( 'Canh trái', 'nntm' ), value: 'left' },
		{ label: __( 'Canh giữa', 'nntm' ), value: 'center' },
		{ label: __( 'Canh phải', 'nntm' ), value: 'right' },
	];

	registerBlockType( 'nntm/paging', {
		edit: function ( props ) {
			var attributes = props.attributes;
			var setAttributes = props.setAttributes;
			var alignment = attributes.alignment || 'center';
			var blockProps = useBlockProps( {
				className: 'nntm-paging nntm-paging--' + alignment,
			} );

			return el(
				'div',
				blockProps,
				el(
					InspectorControls,
					{},
					el(
						PanelBody,
						{ title: __( 'Cài đặt phân trang', 'nntm' ), initialOpen: true },
						el( SelectControl, {
							label: __( 'Vị trí thanh phân trang', 'nntm' ),
							value: alignment,
							options: ALIGNMENT_OPTIONS,
							onChange: function ( value ) {
								setAttributes( { alignment: value } );
							},
						} )
					)
				),
				el(
					'span',
					{ className: 'nntm-paging__btn nntm-paging__btn--prev' },
					el( 'span', { className: 'nntm-paging__icon', 'aria-hidden': 'true' } ),
					el( RichText, {
						tagName: 'span',
						className: 'nntm-paging__label',
						value: attributes.labelPrev,
						placeholder: __( 'BACK', 'nntm' ),
						allowedFormats: [],
						onChange: function ( value ) {
							setAttributes( { labelPrev: value } );
						},
					} )
				),
				el(
					'span',
					{ className: 'nntm-paging__btn nntm-paging__btn--next' },
					el( RichText, {
						tagName: 'span',
						className: 'nntm-paging__label',
						value: attributes.labelNext,
						placeholder: __( 'NEXT', 'nntm' ),
						allowedFormats: [],
						onChange: function ( value ) {
							setAttributes( { labelNext: value } );
						},
					} ),
					el( 'span', { className: 'nntm-paging__icon', 'aria-hidden': 'true' } )
				)
			);
		},
		save: function () {
			// Block động: PHP (render.php) tự tính trang trước/sau theo truy vấn
			// chính của trang mỗi lần tải, không lưu HTML tĩnh vào nội dung bài.
			return null;
		},
	} );
} )( window.wp );
