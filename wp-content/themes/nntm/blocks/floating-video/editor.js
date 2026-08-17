/**
 * Gutenberg editor for nntm/floating-video.
 * Plain JavaScript, no build step, following the existing NNTM blocks.
 */
( function ( wp ) {
	'use strict';

	var el = wp.element.createElement;
	var Fragment = wp.element.Fragment;
	var useEffect = wp.element.useEffect;
	var useState = wp.element.useState;
	var __ = wp.i18n.__;
	var registerBlockType = wp.blocks.registerBlockType;
	var useBlockProps = wp.blockEditor.useBlockProps;
	var InspectorControls = wp.blockEditor.InspectorControls;
	var MediaUpload = wp.blockEditor.MediaUpload;
	var MediaUploadCheck = wp.blockEditor.MediaUploadCheck;
	var PanelBody = wp.components.PanelBody;
	var SelectControl = wp.components.SelectControl;
	var TextControl = wp.components.TextControl;
	var Button = wp.components.Button;
	var Notice = wp.components.Notice;
	var Spinner = wp.components.Spinner;
	var apiFetch = wp.apiFetch;

	var POST_TYPE_OPTIONS = [
		{ label: __( 'Bài viết (6 phân mục)', 'nntm' ), value: 'nntm_article' },
		{ label: __( 'Tin tức / Hoằng pháp', 'nntm' ), value: 'post' },
		{ label: __( 'Ấn phẩm', 'nntm' ), value: 'nntm_publication' },
		{ label: __( 'Pháp thoại', 'nntm' ), value: 'nntm_talk' },
		{ label: __( 'Khóa tu', 'nntm' ), value: 'nntm_retreat' },
		{ label: __( 'Đạo tràng', 'nntm' ), value: 'nntm_abode' },
		{ label: __( 'Video', 'nntm' ), value: 'nntm_video' },
		{ label: __( 'Thiền / Audio', 'nntm' ), value: 'nntm_zen_track' },
	];

	var SOURCE_OPTIONS = [
		{ label: __( 'Chọn video từ Thư viện Media', 'nntm' ), value: 'library' },
		{ label: __( 'Dán link video', 'nntm' ), value: 'url' },
	];

	function decodeTitle( post ) {
		var raw = ( post && post.title && post.title.rendered ) || '';
		var box = document.createElement( 'textarea' );
		box.innerHTML = raw;
		return box.value.trim() || __( '(bài không có tiêu đề)', 'nntm' );
	}

	function videoLabel( attributes ) {
		if ( attributes.sourceType === 'library' ) {
			return attributes.videoUrl
				? __( 'Đã chọn video trong Thư viện Media.', 'nntm' )
				: __( 'Chưa chọn video.', 'nntm' );
		}

		return attributes.videoUrl
			? __( 'Đã nhập link video.', 'nntm' )
			: __( 'Chưa nhập link video.', 'nntm' );
	}

	registerBlockType( 'nntm/floating-video', {
		edit: function ( props ) {
			var attributes = props.attributes;
			var setAttributes = props.setAttributes;
			var blockProps = useBlockProps( { className: 'nntm-floating-video-editor' } );

			var postsState = useState( [] );
			var posts = postsState[ 0 ];
			var setPosts = postsState[ 1 ];

			var loadingState = useState( false );
			var isLoadingPosts = loadingState[ 0 ];
			var setIsLoadingPosts = loadingState[ 1 ];

			var searchState = useState( '' );
			var postSearch = searchState[ 0 ];
			var setPostSearch = searchState[ 1 ];

			var errorState = useState( '' );
			var postsError = errorState[ 0 ];
			var setPostsError = errorState[ 1 ];

			useEffect(
				function () {
					var isCurrent = true;
					var timer = window.setTimeout( function () {
						setIsLoadingPosts( true );
						setPostsError( '' );

						apiFetch( { path: '/wp/v2/types/' + attributes.linkedPostType } )
							.then( function ( typeInfo ) {
								if ( ! isCurrent ) {
									return null;
								}

								var restBase = typeInfo && typeInfo.rest_base ? typeInfo.rest_base : 'posts';
								var trimmedSearch = String( postSearch || '' ).trim();
								var perPage = trimmedSearch ? 30 : 100;
								var path = '/wp/v2/' + restBase + '?per_page=' + perPage + '&orderby=date&order=desc&_fields=id,title';
								if ( trimmedSearch ) {
									path += '&search=' + encodeURIComponent( trimmedSearch );
								}

								return apiFetch( { path: path } );
							} )
							.then( function ( result ) {
								if ( ! isCurrent || result === null ) {
									return;
								}
								setPosts( result || [] );
							} )
							.catch( function () {
								if ( isCurrent ) {
									setPosts( [] );
									setPostsError( __( 'Không tải được danh sách bài viết của loại nội dung này.', 'nntm' ) );
								}
							} )
							.finally( function () {
								if ( isCurrent ) {
									setIsLoadingPosts( false );
								}
							} );
					}, 300 );

					return function () {
						isCurrent = false;
						window.clearTimeout( timer );
					};
				},
				[ attributes.linkedPostType, postSearch ]
			);

			var postOptions = [
				{ label: __( '— Chọn bài viết khi click video —', 'nntm' ), value: 0 },
			].concat(
				posts.map( function ( post ) {
					return { label: decodeTitle( post ), value: post.id };
				} )
			);

			var selectedPostExists = posts.some( function ( post ) {
				return post.id === attributes.linkedPostId;
			} );
			if ( attributes.linkedPostId && ! selectedPostExists ) {
				postOptions.push( {
					label: __( 'Bài số ', 'nntm' ) + attributes.linkedPostId + __( ' (ngoài danh sách hiện tại)', 'nntm' ),
					value: attributes.linkedPostId,
				} );
			}

			function selectLibraryVideo( media ) {
				if ( ! media || ! media.id || ! media.url ) {
					return;
				}

				setAttributes( {
					videoId: parseInt( media.id, 10 ) || 0,
					videoUrl: media.url || '',
				} );
			}

			function removeLibraryVideo() {
				setAttributes( { videoId: 0, videoUrl: '' } );
			}

			var hasVideo = Boolean( attributes.videoUrl );
			var hasLink = Boolean( attributes.linkedPostId );

			return el(
				'div',
				blockProps,
				el(
					InspectorControls,
					{},
					el(
						PanelBody,
						{ title: __( '1. Chọn video', 'nntm' ), initialOpen: true },
						el( SelectControl, {
							label: __( 'Nguồn video', 'nntm' ),
							value: attributes.sourceType,
							options: SOURCE_OPTIONS,
							onChange: function ( value ) {
								setAttributes( {
									sourceType: value,
									videoId: value === 'library' ? attributes.videoId : 0,
									videoUrl: '',
								} );
							},
						} ),
						attributes.sourceType === 'library'
							? el(
									Fragment,
									{},
									el(
										MediaUploadCheck,
										{},
										el( MediaUpload, {
											allowedTypes: [ 'video' ],
											multiple: false,
											value: attributes.videoId,
											onSelect: selectLibraryVideo,
											render: function ( mediaProps ) {
												return el(
													Button,
													{ variant: 'secondary', onClick: mediaProps.open },
													attributes.videoId
														? __( 'Đổi video', 'nntm' )
														: __( 'Chọn video từ Thư viện', 'nntm' )
												);
											},
										} )
									),
									attributes.videoId
										? el(
											Button,
											{ variant: 'link', isDestructive: true, onClick: removeLibraryVideo },
											__( 'Gỡ video', 'nntm' )
										  )
										: null
							  )
							: el( TextControl, {
									label: __( 'Link video', 'nntm' ),
									type: 'url',
									placeholder: 'https://…',
									help: __( 'Hỗ trợ link file video trực tiếp (MP4/WebM/Ogg) và link YouTube/Vimeo.', 'nntm' ),
									value: attributes.videoUrl || '',
									onChange: function ( value ) {
										setAttributes( { videoUrl: value, videoId: 0 } );
									},
							  } )
					),
					el(
						PanelBody,
						{ title: __( '2. Bài viết mở khi click', 'nntm' ), initialOpen: true },
						el( SelectControl, {
							label: __( 'Loại nội dung', 'nntm' ),
							value: attributes.linkedPostType,
							options: POST_TYPE_OPTIONS,
							onChange: function ( value ) {
								setPostSearch( '' );
								setAttributes( { linkedPostType: value, linkedPostId: 0 } );
							},
						} ),
						isLoadingPosts ? el( Spinner, {} ) : null,
						postsError ? el( Notice, { status: 'warning', isDismissible: false }, postsError ) : null,
						el( TextControl, {
							label: __( 'Tìm bài viết', 'nntm' ),
							help: __( 'Để trống sẽ hiện 100 bài mới nhất; nhập từ khóa để tìm các bài cũ hơn.', 'nntm' ),
							value: postSearch,
							onChange: function ( value ) {
								setPostSearch( value );
							},
						} ),
						el( SelectControl, {
							label: __( 'Bài viết liên quan', 'nntm' ),
							help: __( 'Click vào video ngoài website sẽ chuyển đến bài này trong cùng tab.', 'nntm' ),
							value: attributes.linkedPostId || 0,
							options: postOptions,
							onChange: function ( value ) {
								setAttributes( { linkedPostId: parseInt( value, 10 ) || 0 } );
							},
						} )
					),
					el(
						PanelBody,
						{ title: __( '3. Hiển thị', 'nntm' ), initialOpen: false },
						el(
							'p',
							{ className: 'components-base-control__help' },
							__( 'Desktop: 388 × 243 px. Video tự phát, tắt tiếng, lặp lại và chỉ hiển thị phần media; block không render nút đóng hay control riêng.', 'nntm' )
						),
						el(
							'p',
							{ className: 'components-base-control__help' },
							__( 'Muốn sạch 100% giao diện player nên dùng MP4/WebM từ Media Library. Với YouTube, block che phần giao diện lúc khởi tạo rồi mới hiện video đang chạy.', 'nntm' )
						)
					)
				),
				el(
					'div',
					{ className: 'nntm-floating-video-editor__preview' },
					el( 'span', { className: 'dashicons dashicons-video-alt3', 'aria-hidden': 'true' } ),
					el( 'strong', {}, __( 'Video nổi góc phải', 'nntm' ) ),
					el( 'p', {}, videoLabel( attributes ) ),
					el(
						'p',
						{},
						hasLink
							? __( 'Đã chọn bài viết đích.', 'nntm' )
							: __( 'Chưa chọn bài viết đích.', 'nntm' )
					),
					! hasVideo || ! hasLink
						? el(
								Notice,
								{ status: 'warning', isDismissible: false },
								__( 'Cần có cả video và bài viết đích để block hoạt động đúng yêu cầu.', 'nntm' )
						  )
						: el(
								Notice,
								{ status: 'success', isDismissible: false },
								__( 'Đã đủ cấu hình. Front-end sẽ autoplay muted và click video để mở bài viết.', 'nntm' )
						  )
				)
			);
		},
		save: function () {
			return null;
		},
	} );
} )( window.wp );
