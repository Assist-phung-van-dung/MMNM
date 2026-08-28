/*
 * Bảng điều khiển style dùng chung cho mọi block nntm/* (PROMPT 01).
 *
 * Một filter editor.BlockEdit duy nhất -> mọi block nntm/* đều có sẵn:
 *   - Chiều rộng: Full Width / Contained
 *   - Màu nền, màu chữ, màu tiêu đề (chỉ chọn trong bảng màu của website)
 *   - Font chữ (chỉ chọn trong danh sách font của website)
 *
 * Block mới thêm sau này tự động có đủ, không phải sửa editor.js của nó.
 */
( function ( wp ) {
	'use strict';

	if ( ! wp || ! wp.hooks || ! wp.element || ! wp.blockEditor ) {
		return;
	}

	var el = wp.element.createElement;
	var Fragment = wp.element.Fragment;
	var __ = wp.i18n.__;
	var InspectorControls = wp.blockEditor.InspectorControls;
	var PanelBody = wp.components.PanelBody;
	var SelectControl = wp.components.SelectControl;
	var BaseControl = wp.components.BaseControl;
	var ColorPalette = wp.components.ColorPalette;
	var Button = wp.components.Button;

	var CAU_HINH = window.nntmBlockStyle || {};
	var PALETTE = CAU_HINH.palette || [];
	var FONTS = CAU_HINH.fonts || [];
	var LOAI_TRU = CAU_HINH.excluded || [];

	function coHoTro( tenBlock ) {
		if ( ! tenBlock || tenBlock.indexOf( 'nntm/' ) !== 0 ) {
			return false;
		}

		return LOAI_TRU.indexOf( tenBlock ) === -1;
	}

	/* ColorPalette làm việc với mã màu, còn attribute lưu slug -> đổi qua lại. */
	function slugSangMau( slug ) {
		for ( var i = 0; i < PALETTE.length; i++ ) {
			if ( PALETTE[ i ].slug === slug ) {
				return PALETTE[ i ].color;
			}
		}

		return undefined;
	}

	function mauSangSlug( mau ) {
		if ( ! mau ) {
			return '';
		}

		var can = String( mau ).toLowerCase();

		for ( var i = 0; i < PALETTE.length; i++ ) {
			if ( String( PALETTE[ i ].color ).toLowerCase() === can ) {
				return PALETTE[ i ].slug;
			}
		}

		/* Không khớp bảng màu chuẩn thì bỏ, giữ mặc định. */
		return '';
	}

	function oMau( nhan, giaTriSlug, khiDoi ) {
		return el(
			BaseControl,
			{ label: nhan, __nextHasNoMarginBottom: true },
			el( ColorPalette, {
				colors: PALETTE,
				value: slugSangMau( giaTriSlug ),
				disableCustomColors: true,
				clearable: true,
				onChange: function ( mau ) {
					khiDoi( mauSangSlug( mau ) );
				},
			} ),
			giaTriSlug
				? el(
						Button,
						{
							variant: 'link',
							isDestructive: true,
							onClick: function () {
								khiDoi( '' );
							},
						},
						__( 'Trả về màu mặc định', 'nntm' )
				  )
				: null
		);
	}

	var LUA_CHON_FONT = [ { label: __( 'Mặc định của block', 'nntm' ), value: '' } ].concat(
		FONTS.map( function ( font ) {
			return { label: font.name, value: font.slug };
		} )
	);

	function themBangDieuKhien( BlockEdit ) {
		return function ( props ) {
			if ( ! coHoTro( props.name ) ) {
				return el( BlockEdit, props );
			}

			var attributes = props.attributes || {};
			var setAttributes = props.setAttributes;

			function dat( ten ) {
				return function ( giaTri ) {
					var thayDoi = {};
					thayDoi[ ten ] = giaTri;
					setAttributes( thayDoi );
				};
			}

			return el(
				Fragment,
				null,
				el( BlockEdit, props ),
				el(
					InspectorControls,
					null,
					el(
						PanelBody,
						{
							title: __( 'Bố cục & giao diện khối', 'nntm' ),
							initialOpen: false,
						},
						el( SelectControl, {
							label: __( 'Chiều rộng', 'nntm' ),
							help: __(
								'Full Width: khối trải hết bề ngang màn hình. Contained: giữ khung giới hạn như hiện tại.',
								'nntm'
							),
							value: attributes.nntmWidth || '',
							options: [
								{ label: __( 'Theo mặc định của khối', 'nntm' ), value: '' },
								{ label: __( 'Contained (khung giới hạn)', 'nntm' ), value: 'contained' },
								{ label: __( 'Full Width (hết màn hình)', 'nntm' ), value: 'full' },
							],
							onChange: dat( 'nntmWidth' ),
							__nextHasNoMarginBottom: true,
						} ),
						oMau( __( 'Màu nền', 'nntm' ), attributes.nntmBgColor || '', dat( 'nntmBgColor' ) ),
						oMau( __( 'Màu chữ', 'nntm' ), attributes.nntmTextColor || '', dat( 'nntmTextColor' ) ),
						oMau(
							__( 'Màu tiêu đề', 'nntm' ),
							attributes.nntmHeadingColor || '',
							dat( 'nntmHeadingColor' )
						),
						el( SelectControl, {
							label: __( 'Font chữ', 'nntm' ),
							help: __( 'Chỉ chọn trong danh sách font của website.', 'nntm' ),
							value: attributes.nntmFontFamily || '',
							options: LUA_CHON_FONT,
							onChange: dat( 'nntmFontFamily' ),
							__nextHasNoMarginBottom: true,
						} )
					)
				)
			);
		};
	}

	wp.hooks.addFilter(
		'editor.BlockEdit',
		'nntm/block-style-controls',
		wp.compose && wp.compose.createHigherOrderComponent
			? wp.compose.createHigherOrderComponent( themBangDieuKhien, 'nntmBlockStyleControls' )
			: themBangDieuKhien
	);
} )( window.wp );
