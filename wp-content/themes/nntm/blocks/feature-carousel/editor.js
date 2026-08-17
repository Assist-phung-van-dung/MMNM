( function ( wp ) {
	'use strict';

	var el = wp.element.createElement;
	var Fragment = wp.element.Fragment;
	var __ = wp.i18n.__;
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
	var Notice = wp.components.Notice;
	var ServerSideRender = wp.serverSideRender && wp.serverSideRender.default ? wp.serverSideRender.default : wp.serverSideRender;

	function emptySlide() {
		return {
			imageId: 0,
			imageUrl: '',
			imageAlt: '',
			heading: 'Tiêu đề banner',
			text: '',
			ctaLabel: '',
			ctaUrl: '',
		};
	}

	function mediaButton( label, value, onSelect ) {
		return el(
			MediaUploadCheck,
			{},
			el( MediaUpload, {
				allowedTypes: [ 'image' ],
				multiple: false,
				value: value || 0,
				onSelect: onSelect,
				render: function ( mediaProps ) {
					return el( Button, { variant: 'secondary', onClick: mediaProps.open }, label );
				},
			} )
		);
	}

	registerBlockType( 'nntm/feature-carousel', {
		edit: function ( props ) {
			var attributes = props.attributes;
			var setAttributes = props.setAttributes;
			var slides = Array.isArray( attributes.slides ) ? attributes.slides : [];

			function updateSlide( index, patch ) {
				var next = slides.slice();
				next[ index ] = Object.assign( {}, next[ index ], patch );
				setAttributes( { slides: next } );
			}

			function moveSlide( index, direction ) {
				var target = index + direction;
				if ( target < 0 || target >= slides.length ) {
					return;
				}
				var next = slides.slice();
				var temp = next[ index ];
				next[ index ] = next[ target ];
				next[ target ] = temp;
				setAttributes( { slides: next } );
			}

			function removeSlide( index ) {
				var next = slides.slice();
				next.splice( index, 1 );
				setAttributes( { slides: next } );
			}

			var slidePanels = slides.map( function ( slide, index ) {
				return el(
					PanelBody,
					{ title: __( 'Slide ', 'nntm' ) + ( index + 1 ), initialOpen: index === 0, key: 'slide-' + index },
					mediaButton( __( 'Chọn / đổi ảnh', 'nntm' ), slide.imageId, function ( media ) {
						updateSlide( index, {
							imageId: media.id || 0,
							imageUrl: media.url || '',
							imageAlt: media.alt || '',
						} );
					} ),
					el( TextControl, {
						label: __( 'Tiêu đề slide', 'nntm' ),
						value: slide.heading || '',
						onChange: function ( value ) { updateSlide( index, { heading: value } ); },
					} ),
					el( TextareaControl, {
						label: __( 'Mô tả', 'nntm' ),
						value: slide.text || '',
						onChange: function ( value ) { updateSlide( index, { text: value } ); },
					} ),
					el( TextControl, {
						label: __( 'Nhãn liên kết', 'nntm' ),
						value: slide.ctaLabel || '',
						onChange: function ( value ) { updateSlide( index, { ctaLabel: value } ); },
					} ),
					el( TextControl, {
						label: __( 'URL liên kết', 'nntm' ),
						type: 'url',
						value: slide.ctaUrl || '',
						onChange: function ( value ) { updateSlide( index, { ctaUrl: value } ); },
					} ),
					el(
						'div',
						{ style: { display: 'flex', gap: '8px', flexWrap: 'wrap', marginTop: '12px' } },
						el( Button, { variant: 'secondary', disabled: index === 0, onClick: function () { moveSlide( index, -1 ); } }, '↑' ),
						el( Button, { variant: 'secondary', disabled: index === slides.length - 1, onClick: function () { moveSlide( index, 1 ); } }, '↓' ),
						el( Button, { variant: 'link', isDestructive: true, onClick: function () { removeSlide( index ); } }, __( 'Xóa slide', 'nntm' ) )
					)
				);
			} );

			return el(
				'div',
				useBlockProps(),
				el(
					InspectorControls,
					{},
					el(
						PanelBody,
						{ title: __( 'Tiêu đề section', 'nntm' ), initialOpen: true },
						el( TextControl, { label: __( 'Tiêu đề', 'nntm' ), value: attributes.heading || '', onChange: function ( value ) { setAttributes( { heading: value } ); } } ),
						el( SelectControl, {
							label: __( 'Kiểu tiêu đề', 'nntm' ),
							value: attributes.headingStyle || 'plain',
							options: [
								{ label: __( 'Chữ thường / Tông Chỉ', 'nntm' ), value: 'plain' },
								{ label: __( 'Nền kem / Hư Không và Vỏ Ốc', 'nntm' ), value: 'badge' },
							],
							onChange: function ( value ) { setAttributes( { headingStyle: value } ); },
						} ),
						el( TextControl, { label: __( 'Dòng phụ (có thể để trống)', 'nntm' ), value: attributes.introTitle || '', onChange: function ( value ) { setAttributes( { introTitle: value } ); } } ),
						el( TextareaControl, { label: __( 'Mô tả đầu section (có thể để trống)', 'nntm' ), value: attributes.introText || '', onChange: function ( value ) { setAttributes( { introText: value } ); } } )
					),
					el( PanelBody, { title: __( 'Slides', 'nntm' ), initialOpen: true }, slidePanels, el( Button, { variant: 'primary', onClick: function () { setAttributes( { slides: slides.concat( [ emptySlide() ] ) } ); } }, __( 'Thêm slide', 'nntm' ) ) ),
					el(
						PanelBody,
						{ title: __( 'Hiển thị & tự chạy', 'nntm' ), initialOpen: false },
						el( ToggleControl, { label: __( 'Tự động chạy', 'nntm' ), checked: !! attributes.autoplay, onChange: function ( value ) { setAttributes( { autoplay: value } ); } } ),
						el( RangeControl, { label: __( 'Chu kỳ (giây)', 'nntm' ), min: 3, max: 20, value: attributes.interval || 6, onChange: function ( value ) { setAttributes( { interval: value } ); } } ),
						el( ToggleControl, { label: __( 'Hiện mũi tên trái / phải', 'nntm' ), checked: attributes.showArrows !== false, onChange: function ( value ) { setAttributes( { showArrows: value } ); } } ),
						el( SelectControl, {
							label: __( 'Nền section', 'nntm' ),
							value: attributes.backgroundStyle || 'cream',
							options: [ { label: __( 'Kem', 'nntm' ), value: 'cream' }, { label: __( 'Trắng', 'nntm' ), value: 'white' } ],
							onChange: function ( value ) { setAttributes( { backgroundStyle: value } ); },
						} ),
						el( SelectControl, {
							label: __( 'Kiểu mũi tên', 'nntm' ),
							value: attributes.arrowStyle || 'boxed',
							options: [ { label: __( 'Ô vuông trắng', 'nntm' ), value: 'boxed' }, { label: __( 'Chỉ mũi tên', 'nntm' ), value: 'plain' } ],
							onChange: function ( value ) { setAttributes( { arrowStyle: value } ); },
						} )
					)
				),
				slides.length < 1 ? el( Notice, { status: 'info', isDismissible: false }, __( 'Thêm ít nhất một slide trong bảng điều khiển bên phải.', 'nntm' ) ) : null,
				el( ServerSideRender, { block: 'nntm/feature-carousel', attributes: attributes } )
			);
		},
		save: function () { return null; },
	} );
} )( window.wp );
