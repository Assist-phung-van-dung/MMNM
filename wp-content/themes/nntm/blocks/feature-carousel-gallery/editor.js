( function ( wp ) {
	'use strict';

	var el = wp.element.createElement;
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
	var useState = wp.element.useState;
	var useEffect = wp.element.useEffect;
	var useSelect = wp.data && wp.data.useSelect ? wp.data.useSelect : null;

	function emptyDetail() {
		return { imageId: 0, imageUrl: '', imageAlt: '', title: '', text: '' };
	}

	function emptySlide() {
		return {
			imageId: 0,
			imageUrl: '',
			imageAlt: '',
			title: 'Tiêu đề tác phẩm',
			detailLabel: 'Xem Chi Tiết',
			popupTitle: '',
			popupIntro: '',
			details: [],
		};
	}

	function collectMediaIds( slides ) {
		var ids = [];
		slides.forEach( function ( slide ) {
			if ( slide && slide.imageId ) { ids.push( slide.imageId ); }
			( Array.isArray( slide && slide.details ) ? slide.details : [] ).forEach( function ( detail ) {
				if ( detail && detail.imageId ) { ids.push( detail.imageId ); }
			} );
		} );
		return ids.filter( function ( id, index, list ) { return list.indexOf( id ) === index; } );
	}

	function MediaControl( props ) {
		var label = props.label;
		var value = props.value || 0;
		var previewUrl = props.previewUrl || '';
		var onSelect = props.onSelect;
		var onRemove = props.onRemove;
		return el(
			'div',
			{ className: 'nntm-fgc-editor-media-control' },
			previewUrl ? el( 'img', { className: 'nntm-fgc-editor-media-thumb', src: previewUrl, alt: '' } ) : el( 'div', { className: 'nntm-fgc-editor-media-empty' }, __( 'Chưa chọn ảnh', 'nntm' ) ),
			el( 'div', { className: 'nntm-fgc-editor-media-actions' },
				el( MediaUploadCheck, {}, el( MediaUpload, {
					allowedTypes: [ 'image' ],
					multiple: false,
					value: value,
					onSelect: onSelect,
					render: function ( mediaProps ) {
						return el( Button, { variant: 'secondary', onClick: mediaProps.open }, label );
					},
				} ) ),
				previewUrl && onRemove ? el( Button, { variant: 'tertiary', isDestructive: true, onClick: onRemove }, __( 'Bỏ ảnh', 'nntm' ) ) : null
			)
		);
	}

	function relativePosition( index, current, total ) {
		if ( total < 1 ) { return 0; }
		var distance = ( index - current + total ) % total;
		if ( distance > total / 2 ) { distance -= total; }
		return Math.max( -3, Math.min( 3, distance ) );
	}

	registerBlockType( 'nntm/feature-carousel-gallery', {
		edit: function ( props ) {
			var attributes = props.attributes;
			var setAttributes = props.setAttributes;
			var slides = Array.isArray( attributes.slides ) ? attributes.slides : [];
			var previewState = useState( 0 );
			var previewIndex = previewState[ 0 ];
			var setPreviewIndex = previewState[ 1 ];
			var popupPreviewState = useState( 0 );
			var popupPreviewIndex = popupPreviewState[ 0 ];
			var setPopupPreviewIndex = popupPreviewState[ 1 ];
			var mediaIds = collectMediaIds( slides );
			var mediaMap = useSelect ? useSelect( function ( select ) {
				var result = {};
				var core = select( 'core' );
				mediaIds.forEach( function ( id ) {
					var media = core && core.getMedia ? core.getMedia( id ) : null;
					if ( media ) { result[ id ] = media; }
				} );
				return result;
			}, [ mediaIds.join( ',' ) ] ) : {};

			function imageUrl( item ) {
				if ( ! item ) { return ''; }
				if ( item.imageUrl ) { return item.imageUrl; }
				var media = item.imageId ? mediaMap[ item.imageId ] : null;
				return media && media.source_url ? media.source_url : '';
			}

			function setSlides( next ) { setAttributes( { slides: next } ); }
			function updateSlide( index, patch ) {
				var next = slides.slice();
				next[ index ] = Object.assign( {}, next[ index ], patch );
				setSlides( next );
			}
			function moveSlide( index, direction ) {
				var target = index + direction;
				if ( target < 0 || target >= slides.length ) { return; }
				var next = slides.slice();
				var temp = next[ index ]; next[ index ] = next[ target ]; next[ target ] = temp;
				setSlides( next );
				setPreviewIndex( target );
			}
			function removeSlide( index ) {
				var next = slides.slice(); next.splice( index, 1 ); setSlides( next );
				setPreviewIndex( Math.max( 0, Math.min( previewIndex, next.length - 1 ) ) );
			}
			function updateDetail( slideIndex, detailIndex, patch ) {
				var slide = Object.assign( {}, slides[ slideIndex ] );
				var details = Array.isArray( slide.details ) ? slide.details.slice() : [];
				details[ detailIndex ] = Object.assign( {}, details[ detailIndex ], patch );
				updateSlide( slideIndex, { details: details } );
			}
			function removeDetail( slideIndex, detailIndex ) {
				var details = Array.isArray( slides[ slideIndex ].details ) ? slides[ slideIndex ].details.slice() : [];
				details.splice( detailIndex, 1 );
				updateSlide( slideIndex, { details: details } );
				setPopupPreviewIndex( Math.max( 0, Math.min( popupPreviewIndex, details.length - 1 ) ) );
			}
			function moveDetail( slideIndex, detailIndex, direction ) {
				var details = Array.isArray( slides[ slideIndex ].details ) ? slides[ slideIndex ].details.slice() : [];
				var target = detailIndex + direction;
				if ( target < 0 || target >= details.length ) { return; }
				var temp = details[ detailIndex ]; details[ detailIndex ] = details[ target ]; details[ target ] = temp;
				updateSlide( slideIndex, { details: details } );
			}

			useEffect( function () {
				if ( previewIndex >= slides.length ) { setPreviewIndex( Math.max( 0, slides.length - 1 ) ); }
				setPopupPreviewIndex( 0 );
			}, [ previewIndex, slides.length ] );

			var slidePanels = slides.map( function ( slide, index ) {
				var details = Array.isArray( slide.details ) ? slide.details : [];
				var detailPanels = details.map( function ( detail, detailIndex ) {
					return el(
						PanelBody,
						{ title: __( 'Slide popup ', 'nntm' ) + ( detailIndex + 1 ) + ( detail.title ? ' — ' + detail.title : '' ), initialOpen: false, key: 'detail-' + index + '-' + detailIndex },
						el( MediaControl, {
							label: __( 'Chọn / đổi ảnh popup', 'nntm' ),
							value: detail.imageId,
							previewUrl: imageUrl( detail ),
							onSelect: function ( media ) { updateDetail( index, detailIndex, { imageId: media.id || 0, imageUrl: media.url || '', imageAlt: media.alt || '' } ); },
							onRemove: function () { updateDetail( index, detailIndex, { imageId: 0, imageUrl: '', imageAlt: '' } ); },
						} ),
						el( TextControl, { label: __( 'Alt ảnh', 'nntm' ), value: detail.imageAlt || '', onChange: function ( value ) { updateDetail( index, detailIndex, { imageAlt: value } ); } } ),
						el( TextControl, { label: __( 'Tiêu đề slide popup', 'nntm' ), value: detail.title || '', onChange: function ( value ) { updateDetail( index, detailIndex, { title: value } ); } } ),
						el( TextareaControl, { label: __( 'Mô tả slide popup', 'nntm' ), rows: 5, value: detail.text || '', onChange: function ( value ) { updateDetail( index, detailIndex, { text: value } ); } } ),
						el( 'div', { style: { display: 'flex', gap: '8px', flexWrap: 'wrap', marginTop: '10px' } },
							el( Button, { variant: 'secondary', disabled: detailIndex === 0, onClick: function () { moveDetail( index, detailIndex, -1 ); } }, '← ' + __( 'Lùi', 'nntm' ) ),
							el( Button, { variant: 'secondary', disabled: detailIndex === details.length - 1, onClick: function () { moveDetail( index, detailIndex, 1 ); } }, __( 'Tiến', 'nntm' ) + ' →' ),
							el( Button, { variant: 'link', isDestructive: true, onClick: function () { removeDetail( index, detailIndex ); } }, __( 'Xóa slide popup', 'nntm' ) )
						)
					);
				} );

				return el(
					PanelBody,
					{ title: __( 'Slide ', 'nntm' ) + ( index + 1 ) + ( slide.title ? ' — ' + slide.title : '' ), initialOpen: index === 0, key: 'slide-' + index },
					el( MediaControl, {
						label: __( 'Chọn / đổi ảnh chính', 'nntm' ),
						value: slide.imageId,
						previewUrl: imageUrl( slide ),
						onSelect: function ( media ) { updateSlide( index, { imageId: media.id || 0, imageUrl: media.url || '', imageAlt: media.alt || '' } ); setPreviewIndex( index ); },
						onRemove: function () { updateSlide( index, { imageId: 0, imageUrl: '', imageAlt: '' } ); },
					} ),
					el( TextControl, { label: __( 'Alt ảnh chính', 'nntm' ), value: slide.imageAlt || '', onChange: function ( value ) { updateSlide( index, { imageAlt: value } ); } } ),
					el( TextControl, { label: __( 'Tiêu đề riêng của slide', 'nntm' ), value: slide.title || '', onChange: function ( value ) { updateSlide( index, { title: value } ); } } ),
					el( TextControl, { label: __( 'Nhãn nút chi tiết', 'nntm' ), value: slide.detailLabel || 'Xem Chi Tiết', onChange: function ( value ) { updateSlide( index, { detailLabel: value } ); } } ),
					el( TextControl, { label: __( 'Tiêu đề popup', 'nntm' ), value: slide.popupTitle || '', placeholder: slide.title || '', onChange: function ( value ) { updateSlide( index, { popupTitle: value } ); } } ),
					el( TextareaControl, { label: __( 'Mô tả / mở đầu popup', 'nntm' ), rows: 4, value: slide.popupIntro || '', onChange: function ( value ) { updateSlide( index, { popupIntro: value } ); } } ),
					el( 'div', { style: { marginTop: '14px', paddingTop: '14px', borderTop: '1px solid #ddd' } }, detailPanels ),
					el( Button, { variant: 'secondary', onClick: function () { updateSlide( index, { details: details.concat( [ emptyDetail() ] ) } ); setPreviewIndex( index ); setPopupPreviewIndex( details.length ); } }, __( '+ Thêm slide popup', 'nntm' ) ),
					el( 'div', { style: { display: 'flex', gap: '8px', flexWrap: 'wrap', marginTop: '16px' } },
						el( Button, { variant: 'secondary', disabled: index === 0, onClick: function () { moveSlide( index, -1 ); } }, '↑ Slide' ),
						el( Button, { variant: 'secondary', disabled: index === slides.length - 1, onClick: function () { moveSlide( index, 1 ); } }, '↓ Slide' ),
						el( Button, { variant: 'link', isDestructive: true, onClick: function () { removeSlide( index ); } }, __( 'Xóa slide', 'nntm' ) )
					)
				);
			} );

			var activeSlide = slides[ previewIndex ] || null;
			var activeDetails = activeSlide && Array.isArray( activeSlide.details ) ? activeSlide.details : [];
			var activePopupDetail = activeDetails[ Math.min( popupPreviewIndex, Math.max( 0, activeDetails.length - 1 ) ) ] || null;

			var previewSlides = slides.map( function ( slide, index ) {
				var url = imageUrl( slide );
				return el( 'figure', { className: 'nntm-feature-gallery-carousel__slide', 'data-position': String( relativePosition( index, previewIndex, slides.length ) ), key: 'preview-' + index },
					el( 'div', { className: 'nntm-feature-gallery-carousel__media' }, url ? el( 'img', { className: 'nntm-feature-gallery-carousel__image', src: url, alt: slide.imageAlt || '' } ) : el( 'div', { className: 'nntm-fgc-editor-placeholder' }, __( 'Chọn ảnh cho slide này', 'nntm' ) ) ),
					relativePosition( index, previewIndex, slides.length ) === 0 ? el( 'figcaption', { className: 'nntm-feature-gallery-carousel__copy' },
						slide.title ? el( 'h3', {}, slide.title ) : null,
						el( 'span', { className: 'nntm-feature-gallery-carousel__detail nntm-fgc-editor-fake-link' }, slide.detailLabel || __( 'Xem Chi Tiết', 'nntm' ) )
					) : null
				);
			} );

			return el( 'div', useBlockProps( { className: 'nntm-fgc-editor-root' } ),
				el( InspectorControls, {},
					el( PanelBody, { title: __( 'Tiêu đề section', 'nntm' ), initialOpen: true }, el( TextControl, { label: __( 'Tiêu đề nền kem phía trên', 'nntm' ), value: attributes.heading || '', onChange: function ( value ) { setAttributes( { heading: value } ); } } ) ),
					el( PanelBody, { title: __( 'Slides + Popup Slider', 'nntm' ), initialOpen: true }, slidePanels, el( Button, { variant: 'primary', onClick: function () { setSlides( slides.concat( [ emptySlide() ] ) ); setPreviewIndex( slides.length ); } }, __( '+ Thêm slide', 'nntm' ) ) ),
					el( PanelBody, { title: __( 'Slider chính', 'nntm' ), initialOpen: false },
						el( ToggleControl, { label: __( 'Tự động chạy', 'nntm' ), checked: !! attributes.autoplay, onChange: function ( value ) { setAttributes( { autoplay: value } ); } } ),
						el( RangeControl, { label: __( 'Chu kỳ (giây)', 'nntm' ), min: 3, max: 20, value: attributes.interval || 6, onChange: function ( value ) { setAttributes( { interval: value } ); } } ),
						el( ToggleControl, { label: __( 'Hiện mũi tên', 'nntm' ), checked: attributes.showArrows !== false, onChange: function ( value ) { setAttributes( { showArrows: value } ); } } ),
						el( SelectControl, { label: __( 'Nền section', 'nntm' ), value: attributes.backgroundStyle || 'white', options: [ { label: __( 'Trắng', 'nntm' ), value: 'white' }, { label: __( 'Kem', 'nntm' ), value: 'cream' } ], onChange: function ( value ) { setAttributes( { backgroundStyle: value } ); } } ),
						el( SelectControl, { label: __( 'Kiểu mũi tên', 'nntm' ), value: attributes.arrowStyle || 'plain', options: [ { label: __( 'Chỉ mũi tên', 'nntm' ), value: 'plain' }, { label: __( 'Ô vuông', 'nntm' ), value: 'boxed' } ], onChange: function ( value ) { setAttributes( { arrowStyle: value } ); } } )
					)
				),
				slides.length < 1 ? el( Notice, { status: 'info', isDismissible: false }, __( 'Thêm ít nhất một slide. Ảnh được preview ngay trong editor, không cần lưu trước.', 'nntm' ) ) : null,
				el( 'div', { className: 'nntm-feature-gallery-carousel nntm-feature-gallery-carousel--bg-' + ( attributes.backgroundStyle || 'white' ) + ' nntm-fgc-editor-main-preview' },
					attributes.heading ? el( 'header', { className: 'nntm-feature-gallery-carousel__header' }, el( 'h2', { className: 'nntm-feature-gallery-carousel__heading' }, el( 'span', {}, attributes.heading ) ) ) : null,
					slides.length ? el( 'div', { className: 'nntm-feature-gallery-carousel__slider' },
						el( Button, { className: 'nntm-feature-gallery-carousel__arrow nntm-feature-gallery-carousel__arrow--prev', disabled: slides.length < 2, onClick: function () { setPreviewIndex( ( previewIndex - 1 + slides.length ) % slides.length ); setPopupPreviewIndex( 0 ); } }, '←' ),
						el( 'div', { className: 'nntm-feature-gallery-carousel__track' }, previewSlides ),
						el( Button, { className: 'nntm-feature-gallery-carousel__arrow nntm-feature-gallery-carousel__arrow--next', disabled: slides.length < 2, onClick: function () { setPreviewIndex( ( previewIndex + 1 ) % slides.length ); setPopupPreviewIndex( 0 ); } }, '→' )
					) : null
				),
				activeSlide ? el( 'div', { className: 'nntm-fgc-editor-popup-preview' },
					el( 'div', { className: 'nntm-fgc-editor-popup-bar' },
						el( 'strong', {}, __( 'Preview popup slider:', 'nntm' ) + ' ' + ( activeSlide.popupTitle || activeSlide.title || '' ) ),
						el( 'span', {}, activeDetails.length ? ( Math.min( popupPreviewIndex, activeDetails.length - 1 ) + 1 ) + ' / ' + activeDetails.length : '0 / 0' )
					),
					activeDetails.length ? el( 'div', { className: 'nntm-fgc-editor-popup-stage' },
						el( Button, { className: 'nntm-fgc-editor-popup-arrow nntm-fgc-editor-popup-arrow--prev', disabled: activeDetails.length < 2, onClick: function () { setPopupPreviewIndex( ( popupPreviewIndex - 1 + activeDetails.length ) % activeDetails.length ); } }, '←' ),
						el( 'div', { className: 'nntm-fgc-editor-popup-card' },
							activePopupDetail && imageUrl( activePopupDetail ) ? el( 'img', { src: imageUrl( activePopupDetail ), alt: activePopupDetail.imageAlt || '' } ) : el( 'div', { className: 'nntm-fgc-editor-popup-empty' }, __( 'Chọn ảnh cho slide popup', 'nntm' ) ),
							activePopupDetail && ( activePopupDetail.title || activePopupDetail.text ) ? el( 'div', { className: 'nntm-fgc-editor-popup-copy' }, activePopupDetail.title ? el( 'h4', {}, activePopupDetail.title ) : null, activePopupDetail.text ? el( 'p', {}, activePopupDetail.text ) : null ) : null
						),
						el( Button, { className: 'nntm-fgc-editor-popup-arrow nntm-fgc-editor-popup-arrow--next', disabled: activeDetails.length < 2, onClick: function () { setPopupPreviewIndex( ( popupPreviewIndex + 1 ) % activeDetails.length ); } }, '→' )
					) : el( Notice, { status: 'info', isDismissible: false }, __( 'Slide này chưa có slide popup. Bấm “+ Thêm slide popup” ở sidebar.', 'nntm' ) )
				) : null
			);
		},
		save: function () { return null; },
	} );
} )( window.wp );
