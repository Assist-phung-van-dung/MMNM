( function ( wp ) {
	'use strict';

	var el = wp.element.createElement;
	var Fragment = wp.element.Fragment;
	var __ = wp.i18n.__;
	var registerBlockType = wp.blocks.registerBlockType;
	var useBlockProps = wp.blockEditor.useBlockProps;
	var InspectorControls = wp.blockEditor.InspectorControls;
	var PanelBody = wp.components.PanelBody;
	var Button = wp.components.Button;
	var TextControl = wp.components.TextControl;
	var SelectControl = wp.components.SelectControl;

	function oTrong() {
		return { label: '', url: '' };
	}

	registerBlockType( 'nntm/floating-bar', {
		edit: function ( props ) {
			var attrs = props.attributes;
			var items = Array.isArray( attrs.items ) ? attrs.items : [];

			function capNhat( index, khoa, giaTri ) {
				var moi = items.map( function ( o, i ) {
					if ( i !== index ) {
						return o;
					}

					var sao = Object.assign( {}, o );
					sao[ khoa ] = giaTri;
					return sao;
				} );

				props.setAttributes( { items: moi } );
			}

			function doiCho( index, buoc ) {
				var dich = index + buoc;

				if ( dich < 0 || dich >= items.length ) {
					return;
				}

				var moi = items.slice();
				var tam = moi[ index ];
				moi[ index ] = moi[ dich ];
				moi[ dich ] = tam;
				props.setAttributes( { items: moi } );
			}

			var hangItems = items.map( function ( o, i ) {
				return el(
					'div',
					{ key: 'fb-' + i, style: { borderTop: '1px solid #ddd', paddingTop: '12px', marginTop: '12px' } },
					el( TextControl, {
						label: __( 'Chữ hiện trên ô', 'nntm' ),
						value: o.label || '',
						onChange: function ( v ) { capNhat( i, 'label', v ); }
					} ),
					el( TextControl, {
						label: __( 'Link (dán URL, hoặc #id-section)', 'nntm' ),
						value: o.url || '',
						placeholder: 'https://  hoặc  #got-son',
						onChange: function ( v ) { capNhat( i, 'url', v ); }
					} ),
					el(
						'div',
						{ style: { display: 'flex', gap: '6px' } },
						el( Button, { variant: 'secondary', disabled: 0 === i, onClick: function () { doiCho( i, -1 ); } }, __( 'Lên', 'nntm' ) ),
						el( Button, { variant: 'secondary', disabled: i === items.length - 1, onClick: function () { doiCho( i, 1 ); } }, __( 'Xuống', 'nntm' ) ),
						el( Button, { isDestructive: true, onClick: function () {
							props.setAttributes( { items: items.filter( function ( x, j ) { return j !== i; } ) } );
						} }, __( 'Xoá ô', 'nntm' ) )
					)
				);
			} );

			return el(
				Fragment,
				{},
				el(
					InspectorControls,
					{},
					el(
						PanelBody,
						{ title: __( 'Các ô trong thanh', 'nntm' ), initialOpen: true },
						hangItems,
						el(
							Button,
							{
								variant: 'primary',
								style: { marginTop: '14px' },
								onClick: function () { props.setAttributes( { items: items.concat( [ oTrong() ] ) } ); }
							},
							__( 'Thêm ô', 'nntm' )
						)
					),
					el(
						PanelBody,
						{ title: __( 'Khi nào hiện', 'nntm' ), initialOpen: false },
						el( SelectControl, {
							label: __( 'Thanh hiện lên khi', 'nntm' ),
							value: attrs.moKhi || 'qua-banner',
							options: [
								{ label: __( 'Đã cuộn qua dải banner đầu trang', 'nntm' ), value: 'qua-banner' },
								{ label: __( 'Vừa bắt đầu cuộn', 'nntm' ), value: 'cuon-ngay' }
							],
							onChange: function ( v ) { props.setAttributes( { moKhi: v } ); }
						} )
					)
				),
				el(
					'div',
					useBlockProps( { className: 'nntm-floating-bar-editor' } ),
					el(
						'p',
						{ style: { margin: '0 0 8px', fontWeight: 700 } },
						__( 'Thanh Nổi', 'nntm' )
					),
					el(
						'p',
						{ style: { margin: '0 0 10px', fontSize: '12px', color: '#666' } },
						__( 'Ngoài trang thật, thanh này nổi ở đáy màn hình và chỉ hiện khi người xem cuộn qua dải banner. Sửa các ô ở bảng bên phải.', 'nntm' )
					),
					el(
						'div',
						{ style: { display: 'flex', gap: '1px', background: '#4c4c4c', padding: '10px' } },
						items.map( function ( o, i ) {
							return el(
								'span',
								{
									key: 'xem-' + i,
									style: {
										flex: '1 1 0',
										padding: '10px 8px',
										background: '#d8d3c4',
										fontSize: '12px',
										textAlign: 'center',
										whiteSpace: 'nowrap',
										overflow: 'hidden',
										textOverflow: 'ellipsis'
									}
								},
								o.label || __( '(chưa có chữ)', 'nntm' )
							);
						} )
					)
				)
			);
		},
		save: function () {
			return null;
		}
	} );
} )( window.wp );
