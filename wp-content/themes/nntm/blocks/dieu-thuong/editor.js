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
	var Notice = wp.components.Notice;
	var ServerSideRender = wp.serverSideRender && wp.serverSideRender.default ? wp.serverSideRender.default : wp.serverSideRender;
	var createBlock = wp.blocks.createBlock;
	var dataSelect = wp.data.select;
	var dataDispatch = wp.data.dispatch;

	function imageItem( media ) {
		return {
			imageId: media.id || 0,
			imageUrl: media.url || '',
			imageAlt: media.alt || '',
		};
	}

	function singleMediaButton( label, value, onSelect ) {
		return el(
			MediaUploadCheck,
			{},
			el( MediaUpload, {
				allowedTypes: [ 'image' ],
				multiple: false,
				value: value || 0,
				onSelect: onSelect,
				render: function ( mediaProps ) { return el( Button, { variant: 'secondary', onClick: mediaProps.open }, label ); },
			} )
		);
	}

	registerBlockType( 'nntm/dieu-thuong', {
		edit: function ( props ) {
			var attributes = props.attributes;
			var setAttributes = props.setAttributes;
			var gallery = Array.isArray( attributes.gallery ) ? attributes.gallery : [];
			var bannerImages = Array.isArray( attributes.bannerImages ) ? attributes.bannerImages.slice() : [];

			if ( ! bannerImages.length && ( attributes.bannerImageId || attributes.bannerImageUrl ) ) {
				bannerImages = [ {
					imageId: attributes.bannerImageId || 0,
					imageUrl: attributes.bannerImageUrl || '',
					imageAlt: '',
				} ];
			}

			function setBannerImages( next ) {
				var patch = { bannerImages: next };
				if ( ! next.length ) {
					patch.bannerImageId = 0;
					patch.bannerImageUrl = '';
				}
				setAttributes( patch );
			}

			function migrateLegacyCarousel() {
				var legacySlides = Array.isArray( attributes.slides ) ? attributes.slides : [];
				if ( ! legacySlides.length ) { return; }

				var blockEditorStore = dataSelect( 'core/block-editor' );
				var rootClientId = blockEditorStore.getBlockRootClientId( props.clientId );
				var currentIndex = blockEditorStore.getBlockIndex( props.clientId, rootClientId );
				var featureBlock = createBlock( 'nntm/feature-carousel', {
					heading: attributes.heading || 'Tông Chỉ',
					headingStyle: 'plain',
					introTitle: attributes.introTitle || '',
					introText: attributes.introText || '',
					slides: legacySlides,
					autoplay: attributes.autoplay !== false,
					interval: attributes.interval || 6,
					showArrows: true,
					backgroundStyle: 'cream',
					arrowStyle: 'boxed',
				} );

				dataDispatch( 'core/block-editor' ).insertBlock( featureBlock, currentIndex, rootClientId );
				setAttributes( { legacyCarouselMigrated: true } );
			}

			function moveItem( list, index, direction ) {
				var target = index + direction;
				if ( target < 0 || target >= list.length ) { return list; }
				var next = list.slice();
				var temp = next[ index ];
				next[ index ] = next[ target ];
				next[ target ] = temp;
				return next;
			}

			var bannerPanels = bannerImages.map( function ( item, index ) {
				return el(
					PanelBody,
					{ title: __( 'Ảnh banner ', 'nntm' ) + ( index + 1 ), initialOpen: index === 0, key: 'banner-' + index },
					singleMediaButton( __( 'Đổi ảnh', 'nntm' ), item.imageId, function ( media ) {
						var next = bannerImages.slice();
						next[ index ] = imageItem( media );
						setBannerImages( next );
					} ),
					el( 'div', { style: { display: 'flex', gap: '8px', flexWrap: 'wrap', marginTop: '12px' } },
						el( Button, { variant: 'secondary', disabled: index === 0, onClick: function () { setBannerImages( moveItem( bannerImages, index, -1 ) ); } }, '↑' ),
						el( Button, { variant: 'secondary', disabled: index === bannerImages.length - 1, onClick: function () { setBannerImages( moveItem( bannerImages, index, 1 ) ); } }, '↓' ),
						el( Button, { variant: 'link', isDestructive: true, onClick: function () { var next = bannerImages.slice(); next.splice( index, 1 ); setBannerImages( next ); } }, __( 'Xóa ảnh', 'nntm' ) )
					)
				);
			} );

			var galleryPanels = gallery.map( function ( item, index ) {
				return el(
					PanelBody,
					{ title: __( 'Ảnh nội dung ', 'nntm' ) + ( index + 1 ), initialOpen: index === 0, key: 'gallery-' + index },
					singleMediaButton( __( 'Chọn / đổi ảnh', 'nntm' ), item.imageId, function ( media ) {
						var next = gallery.slice();
						next[ index ] = imageItem( media );
						setAttributes( { gallery: next } );
					} ),
					el( Button, { variant: 'link', isDestructive: true, onClick: function () { var next = gallery.slice(); next.splice( index, 1 ); setAttributes( { gallery: next } ); } }, __( 'Xóa ảnh', 'nntm' ) )
				);
			} );

			var hasLegacyCarousel = Array.isArray( attributes.slides ) && attributes.slides.length > 0 && ! attributes.legacyCarouselMigrated;

			return el(
				'div',
				useBlockProps(),
				el(
					InspectorControls,
					{},
					el(
						PanelBody,
						{ title: __( 'Banner tự chạy', 'nntm' ), initialOpen: true },
						bannerPanels,
						el( MediaUploadCheck, {}, el( MediaUpload, {
							allowedTypes: [ 'image' ],
							multiple: true,
							onSelect: function ( mediaList ) {
								var selected = Array.isArray( mediaList ) ? mediaList : [ mediaList ];
								setBannerImages( bannerImages.concat( selected.map( imageItem ) ) );
							},
							render: function ( mediaProps ) { return el( Button, { variant: 'primary', onClick: mediaProps.open }, __( 'Thêm ảnh banner', 'nntm' ) ); },
						} ) ),
						el( ToggleControl, { label: __( 'Tự động chạy', 'nntm' ), checked: attributes.bannerAutoplay !== false, onChange: function ( value ) { setAttributes( { bannerAutoplay: value } ); } } ),
						el( RangeControl, { label: __( 'Chu kỳ (giây)', 'nntm' ), min: 3, max: 20, value: attributes.bannerInterval || 6, onChange: function ( value ) { setAttributes( { bannerInterval: value } ); } } )
					),
					el(
						PanelBody,
						{ title: __( 'Câu chuyện', 'nntm' ), initialOpen: true },
						singleMediaButton( __( 'Chọn ảnh tròn', 'nntm' ), attributes.portraitImageId, function ( media ) { setAttributes( { portraitImageId: media.id || 0, portraitImageUrl: media.url || '' } ); } ),
						el( TextControl, { label: __( 'Tiêu đề', 'nntm' ), value: attributes.storyHeading || '', onChange: function ( value ) { setAttributes( { storyHeading: value } ); } } ),
						el( TextareaControl, { label: __( 'Đoạn trên', 'nntm' ), value: attributes.storyTextTop || '', onChange: function ( value ) { setAttributes( { storyTextTop: value } ); } } ),
						galleryPanels,
						gallery.length < 3 ? el( Button, { variant: 'secondary', onClick: function () { setAttributes( { gallery: gallery.concat( [ { imageId: 0, imageUrl: '', imageAlt: '' } ] ) } ); } }, __( 'Thêm ảnh nội dung', 'nntm' ) ) : null,
						el( TextareaControl, { label: __( 'Đoạn dưới', 'nntm' ), value: attributes.storyTextBottom || '', onChange: function ( value ) { setAttributes( { storyTextBottom: value } ); } } ),
						el( TextControl, { label: __( 'Nhãn nút', 'nntm' ), value: attributes.ctaLabel || '', onChange: function ( value ) { setAttributes( { ctaLabel: value } ); } } ),
						el( TextControl, { label: __( 'Liên kết nút', 'nntm' ), type: 'url', value: attributes.ctaUrl || '', onChange: function ( value ) { setAttributes( { ctaUrl: value } ); } } )
					)
				),
				hasLegacyCarousel ? el( Notice, { status: 'warning', isDismissible: false, actions: [ { label: __( 'Tách slider cũ thành Feature Carousel', 'nntm' ), onClick: migrateLegacyCarousel } ] }, __( 'Block cũ vẫn đang giữ dữ liệu Tông Chỉ. Bấm nút để chèn Feature Carousel ngay phía trên và chuyển toàn bộ slide/text cũ sang block mới, không phải nhập lại.', 'nntm' ) ) : null,
				bannerImages.length < 1 ? el( Notice, { status: 'info', isDismissible: false }, __( 'Banner chưa có ảnh. Có thể thêm nhiều ảnh; frontend chỉ tự fade, không hiện arrow/dot/icon.', 'nntm' ) ) : null,
				el( ServerSideRender, { block: 'nntm/dieu-thuong', attributes: attributes } )
			);
		},
		save: function () { return null; },
	} );
} )( window.wp );
