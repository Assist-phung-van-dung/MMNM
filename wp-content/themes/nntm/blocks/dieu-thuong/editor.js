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
	var SelectControl = wp.components.SelectControl;
	var useState = wp.element.useState;
	var useEffect = wp.element.useEffect;
	var apiFetch = wp.apiFetch;
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

			var phanMucState = useState( [] );
			var phanMuc = phanMucState[ 0 ];
			var setPhanMuc = phanMucState[ 1 ];

			useEffect( function () {
				var conHieuLuc = true;

				apiFetch( { path: '/wp/v2/nntm_section?per_page=100&orderby=name&order=asc&_fields=id,name,parent' } )
					.then( function ( kq ) { if ( conHieuLuc ) { setPhanMuc( kq || [] ); } } )
					.catch( function () { if ( conHieuLuc ) { setPhanMuc( [] ); } } );

				return function () { conHieuLuc = false; };
			}, [] );

			var luaChonPhanMuc = [ { label: __( '— Dùng ảnh tự chọn bên dưới —', 'nntm' ), value: 0 } ].concat(
				phanMuc.map( function ( t ) {
					return { label: ( t.parent ? '— ' : '' ) + t.name, value: t.id };
				} )
			);

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
						el( SelectControl, {
							label: __( 'Dải giữa: lấy từ phân mục', 'nntm' ),
							help: __( 'Chọn một phân mục cha để hiện các phân mục con dạng thẻ trượt, BẤM VÀO THẺ SẼ CHUYỂN TRANG. Để "— Dùng ảnh tự chọn —" thì dùng 3 ảnh tĩnh bên dưới, bấm vào ảnh không chuyển đi đâu — chỉ nút CTA cuối khối mới điều hướng.', 'nntm' ),
							value: attributes.galleryTermId || 0,
							options: luaChonPhanMuc,
							onChange: function ( v ) { setAttributes( { galleryTermId: parseInt( v, 10 ) || 0 } ); }
						} ),
						attributes.galleryTermId ? el( RangeControl, {
							label: __( 'Số thẻ hiện cùng lúc', 'nntm' ),
							help: __( 'Các thẻ còn lại vẫn xem được bằng hai nút mũi tên.', 'nntm' ),
							min: 1,
							max: 6,
							value: attributes.galleryMax || 3,
							onChange: function ( v ) { setAttributes( { galleryMax: v } ); }
						} ) : null,
						attributes.galleryTermId ? el( ToggleControl, {
							label: __( 'Thẻ tự chạy', 'nntm' ),
							checked: attributes.galleryAutoplay !== false,
							onChange: function ( v ) { setAttributes( { galleryAutoplay: v } ); }
						} ) : null,
						attributes.galleryTermId ? el( RangeControl, {
							label: __( 'Chu kỳ thẻ (giây)', 'nntm' ),
							min: 2,
							max: 20,
							value: attributes.galleryInterval || 5,
							onChange: function ( v ) { setAttributes( { galleryInterval: v } ); }
						} ) : null,
						attributes.galleryTermId ? el( RangeControl, {
							label: __( 'Tới cuối, chờ mấy giây rồi quay lại đầu', 'nntm' ),
							help: __( '0 = dừng hẳn ở thẻ cuối, không quay lại.', 'nntm' ),
							min: 0,
							max: 60,
							value: attributes.galleryLoopDelay || 0,
							onChange: function ( v ) { setAttributes( { galleryLoopDelay: v } ); }
						} ) : null,
						attributes.galleryTermId ? null : galleryPanels,
						( ! attributes.galleryTermId && gallery.length < 3 ) ? el( Button, { variant: 'secondary', onClick: function () { setAttributes( { gallery: gallery.concat( [ { imageId: 0, imageUrl: '', imageAlt: '' } ] ) } ); } }, __( 'Thêm ảnh nội dung', 'nntm' ) ) : null,
						el( TextareaControl, { label: __( 'Đoạn dưới', 'nntm' ), value: attributes.storyTextBottom || '', onChange: function ( value ) { setAttributes( { storyTextBottom: value } ); } } ),
						singleMediaButton( __( 'Chọn / đổi ảnh cụm cuối', 'nntm' ), attributes.ctaImageId, function ( media ) {
							setAttributes( { ctaImageId: media.id || 0, ctaImageUrl: media.url || '' } );
						} ),
						el( TextControl, {
							label: __( 'Tiêu đề dưới ảnh', 'nntm' ),
							help: __( 'Xếp theo thứ tự: ảnh — tiêu đề — nút bấm. Để trống phần nào thì phần đó không hiện.', 'nntm' ),
							value: attributes.ctaTitle || '',
							onChange: function ( value ) { setAttributes( { ctaTitle: value } ); }
						} ),
						el( TextControl, { label: __( 'Nhãn nút', 'nntm' ), value: attributes.ctaLabel || '', onChange: function ( value ) { setAttributes( { ctaLabel: value } ); } } ),
						el( TextControl, { label: __( 'Liên kết nút', 'nntm' ), help: __( 'Ảnh và nút cùng trỏ về liên kết này.', 'nntm' ), type: 'url', value: attributes.ctaUrl || '', onChange: function ( value ) { setAttributes( { ctaUrl: value } ); } } )
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
