/*
 * Bảng điều khiển style dùng chung cho mọi block nntm/*.
 *
 * BƯỚC 1 — NỀN. BƯỚC 2 — CHỮ. BƯỚC 3 — KHOẢNG CÁCH & THEO THIẾT BỊ.
 *
 * Một filter editor.BlockEdit duy nhất -> mọi block nntm/* đều có sẵn:
 *   - "Nền khối": màu tự do, chuyển sắc, ảnh nền kèm lớp phủ.
 *   - "Chữ trong khối": năm vai trò (Nội dung / Tiêu đề / Tiêu đề phụ / Nhãn
 *     nhỏ / Nút), mỗi vai trò chỉnh riêng font, cỡ, độ đậm, giãn dòng, giãn
 *     chữ, viết hoa và màu.
 *   - "Khoảng cách": đệm bốn cạnh + lề trên/dưới.
 *   - "Bố cục khối": chiều rộng.
 *
 * Chữ và khoảng cách tách riêng cho ba mốc Máy tính / Máy tính bảng / Điện
 * thoại; nền dùng chung một bản.
 * Block mới thêm sau này tự động có đủ, không phải sửa editor.js của nó.
 *
 * Một filter editor.BlockListBlock thứ hai lo phần xem trước: nó dựng đúng
 * đoạn CSS mà PHP sẽ sinh ra ngoài trang thật, rồi nhét vào trình soạn thảo —
 * nhờ vậy trong admin nhìn thấy y như ngoài trang.
 *
 * Bản đồ vai trò chữ KHÔNG chép lại ở đây mà nhận từ PHP qua wp_localize_script,
 * nên trình soạn thảo và trang thật không thể lệch nhau.
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
	var MediaUpload = wp.blockEditor.MediaUpload;
	var MediaUploadCheck = wp.blockEditor.MediaUploadCheck;
	var C = wp.components;
	var PanelBody = C.PanelBody;
	var SelectControl = C.SelectControl;
	var BaseControl = C.BaseControl;
	var ColorPalette = C.ColorPalette;
	var RangeControl = C.RangeControl;
	var Button = C.Button;
	var Flex = C.Flex;
	var FlexItem = C.FlexItem;

	/* ToggleGroupControl vẫn còn tiền tố __experimental ở một số bản WordPress. */
	var ToggleGroup = C.__experimentalToggleGroupControl || C.ToggleGroupControl || null;
	var ToggleGroupOption = C.__experimentalToggleGroupControlOption || C.ToggleGroupControlOption || null;

	var CAU_HINH = window.nntmBlockStyle || {};
	var PALETTE = CAU_HINH.palette || [];
	var FONTS = CAU_HINH.fonts || [];
	var LOAI_TRU = CAU_HINH.excluded || [];
	var SURFACE = CAU_HINH.surface || {};
	/*
	 * Bản đồ vai trò chữ + chuỗi loại trừ đều do PHP đẩy sang, không chép lại.
	 *
	 * Tên phải khác LOAI_TRU ở trên: đó là DANH SÁCH BLOCK không nhận bảng điều
	 * khiển, còn đây là mảnh SELECTOR loại ảnh/biểu tượng ra khỏi vai trò chữ.
	 * Trùng tên thì coHoTro() gọi .indexOf trên một chuỗi và bốn block bị loại
	 * trừ lại có bảng điều khiển.
	 */
	var VAI_TRO = CAU_HINH.typoRoles || [];
	var TYPO_TRU = CAU_HINH.typoExcl || '';
	var THIET_BI = CAU_HINH.thietBi || [ { ma: 'desktop', ten: 'Máy tính', dieuKien: '' } ];

	function coHoTro( tenBlock ) {
		if ( ! tenBlock || tenBlock.indexOf( 'nntm/' ) !== 0 ) {
			return false;
		}

		return LOAI_TRU.indexOf( tenBlock ) === -1;
	}

	/* ------------------------------------------------------------------ *
	 * Đọc / ghi cấu hình
	 * ------------------------------------------------------------------ */

	var NEN_MAC_DINH = {
		type: 'none',
		color: '',
		grad: { from: '#F7F1DE', to: '#747766', angle: 180, kind: 'linear' },
		img: { id: 0, url: '', pos: 'center center', size: 'cover', repeat: 'no-repeat', attach: 'scroll' },
		ov: { color: '#000000', opacity: 0 },
	};

	function gop( mac_dinh, co_san ) {
		var ra = {};
		var khoa;

		for ( khoa in mac_dinh ) {
			if ( ! Object.prototype.hasOwnProperty.call( mac_dinh, khoa ) ) {
				continue;
			}

			if ( mac_dinh[ khoa ] && typeof mac_dinh[ khoa ] === 'object' ) {
				ra[ khoa ] = gop( mac_dinh[ khoa ], ( co_san && co_san[ khoa ] ) || {} );
			} else {
				ra[ khoa ] = co_san && co_san[ khoa ] !== undefined ? co_san[ khoa ] : mac_dinh[ khoa ];
			}
		}

		return ra;
	}

	/*
	 * Nội dung lưu trước bước 1 chỉ có nntmBgColor là slug trong bảng màu.
	 * Dựng lại thành một nền màu đặc để admin thấy đúng cái đang hiển thị.
	 */
	function slugSangMau( slug ) {
		for ( var i = 0; i < PALETTE.length; i++ ) {
			if ( PALETTE[ i ].slug === slug ) {
				return PALETTE[ i ].color;
			}
		}

		return '';
	}

	function docNen( attributes ) {
		var style = attributes.nntmStyle || {};
		var bg = style.bg;

		if ( ! bg || ! bg.type ) {
			var mauCu = slugSangMau( attributes.nntmBgColor || '' );

			if ( mauCu ) {
				return gop( NEN_MAC_DINH, { type: 'color', color: mauCu } );
			}
		}

		return gop( NEN_MAC_DINH, bg || {} );
	}

	/* ------------------------------------------------------------------ *
	 * Dựng CSS — bản sao đúng logic của inc/block-style.php
	 * ------------------------------------------------------------------ */

	function lopPhu( mau, doMo ) {
		if ( ! mau || doMo <= 0 ) {
			return '';
		}

		return 'color-mix(in srgb, ' + mau + ' ' + doMo + '%, transparent)';
	}

	function khaiBaoNen( nen ) {
		if ( nen.type === 'color' ) {
			if ( ! nen.color ) {
				return null;
			}

			return [ 'background-color:' + nen.color, 'background-image:none' ];
		}

		if ( nen.type === 'gradient' ) {
			if ( ! nen.grad.from || ! nen.grad.to ) {
				return null;
			}

			if ( nen.grad.kind === 'radial' ) {
				return [
					'background-image:radial-gradient(circle at center, ' + nen.grad.from + ' 0%, ' + nen.grad.to + ' 100%)',
				];
			}

			return [
				'background-image:linear-gradient(' + nen.grad.angle + 'deg, ' + nen.grad.from + ' 0%, ' + nen.grad.to + ' 100%)',
			];
		}

		if ( nen.type === 'image' ) {
			if ( ! nen.img.url ) {
				return null;
			}

			var phu = lopPhu( nen.ov.color, nen.ov.opacity );
			var lop = [];

			if ( phu ) {
				lop.push( 'linear-gradient(0deg, ' + phu + ' 0%, ' + phu + ' 100%)' );
			}

			lop.push( 'url("' + nen.img.url + '")' );

			return [
				'background-image:' + lop.join( ',' ),
				'background-position:' + nen.img.pos,
				'background-size:' + nen.img.size,
				'background-repeat:' + nen.img.repeat,
				'background-attachment:' + nen.img.attach,
			];
		}

		return null;
	}

	/*
	 * Trong trình soạn thảo, class định danh nằm ở thẻ bọc ngoài của trình soạn
	 * thảo chứ không phải thẻ <section> của chính block — nên phải nhắm cả thẻ
	 * bọc lẫn con đầu tiên của nó thì mới thấy giống ngoài trang.
	 */
	function dungCss( lop, tenBlock, attributes ) {
		var goc = '.' + lop + '.' + lop + '.' + lop;

		/*
		 * Liệt kê đúng các thẻ bố cục thay vì dùng *:first-child. Với những
		 * block render qua ServerSideRender, PHP đã chèn sẵn một thẻ <style>
		 * đứng trước thẻ <section> — *:first-child sẽ trúng thẻ <style> đó và
		 * nền không hiện ra.
		 */
		var nhamVao = goc + ',' + goc + ' > :is(section, div, nav, aside, article, header, footer, main)';
		var css = '';

		/* Nền không tách theo thiết bị — chỉ có một bản. */
		var khai = khaiBaoNen( docNen( attributes ) );

		if ( khai ) {
			css += nhamVao + '{' + khai.join( ';' ) + '}';

			var conCheNen = SURFACE[ tenBlock ];

			if ( conCheNen && conCheNen.length ) {
				var chon = conCheNen.map( function ( classCon ) {
					return goc + ' .' + classCon;
				} );

				css += chon.join( ',' ) + '{background-color:transparent;background-image:none}';
			}
		}

		/*
		 * Chữ và khoảng cách: một lượt cho mỗi thiết bị, theo đúng thứ tự máy
		 * tính -> máy tính bảng -> điện thoại. Dưới 768px cả hai media query
		 * cùng khớp nên cái đứng sau thắng.
		 */
		THIET_BI.forEach( function ( tb ) {
			var phan = '';

			var space = khaiBaoSpace( nhanhThietBi( attributes, tb.ma ).space );

			if ( space.length ) {
				phan += goc + '{' + space.join( ';' ) + '}';
			}

			var typo = docTypo( attributes, tb.ma );

			/*
			 * Xuất theo đúng thứ tự VAI_TRO do PHP đẩy sang: quy tắc bằng độ ưu
			 * tiên nên vai trò đứng sau thắng ở class trúng nhiều vai trò
			 * (`__subheading` trúng cả "heading" lẫn "sub").
			 */
			VAI_TRO.forEach( function ( role ) {
				var khaiBao = typo[ role.ma ];

				if ( ! khaiBao ) {
					return;
				}

				var dong = [];
				var ten;
				for ( ten in khaiBao ) {
					if ( Object.prototype.hasOwnProperty.call( khaiBao, ten ) ) {
						dong.push( ten + ':' + khaiBao[ ten ] );
					}
				}

				if ( ! dong.length ) {
					return;
				}

				phan += selectorVaiTro( goc, role ) + '{' + dong.join( ';' ) + '}';
			} );

			if ( ! phan ) {
				return;
			}

			css += tb.dieuKien === '' ? phan : '@media ' + tb.dieuKien + '{' + phan + '}';
		} );

		return css;
	}

	/* ------------------------------------------------------------------ *
	 * Thiết bị & khoảng cách — bản sao đúng logic của inc/block-style.php
	 * ------------------------------------------------------------------ */

	/*
	 * Máy tính nằm thẳng trong nntmStyle; máy tính bảng và điện thoại nằm trong
	 * nntmStyle.tablet / nntmStyle.mobile. Nhờ vậy nội dung lưu từ bước 2 đọc
	 * vẫn nguyên vẹn — nó chính là nhánh máy tính.
	 */
	function nhanhThietBi( attributes, thietBi ) {
		var style = attributes.nntmStyle || {};

		if ( thietBi === 'desktop' ) {
			return style;
		}

		return style[ thietBi ] || {};
	}

	var CANH_DEM = [
		[ 't', 'top' ],
		[ 'r', 'right' ],
		[ 'b', 'bottom' ],
		[ 'l', 'left' ],
	];

	var CANH_LE = [
		[ 't', 'top' ],
		[ 'b', 'bottom' ],
	];

	function khaiBaoSpace( tho ) {
		if ( ! tho ) {
			return [];
		}

		var ra = [];
		var dem = tho.pad || {};
		var le = tho.mar || {};

		CANH_DEM.forEach( function ( c ) {
			var n = so( dem[ c[ 0 ] ], 0, 300 );

			if ( n !== null ) {
				ra.push( 'padding-' + c[ 1 ] + ':' + soGon( n ) + 'px' );
			}
		} );

		CANH_LE.forEach( function ( c ) {
			var n = so( le[ c[ 0 ] ], -200, 200 );

			if ( n !== null ) {
				ra.push( 'margin-' + c[ 1 ] + ':' + soGon( n ) + 'px' );
			}
		} );

		return ra;
	}

	function dinhDanh( clientId ) {
		return 'nntm-s-' + String( clientId || '' ).replace( /-/g, '' ).slice( 0, 8 );
	}

	/* ------------------------------------------------------------------ *
	 * Chữ theo vai trò — bản sao đúng logic của inc/block-style.php
	 * ------------------------------------------------------------------ */

	/*
	 * Selector của một vai trò. Cùng công thức với
	 * nntm_block_style_typo_selector() bên PHP, và dùng chung bản đồ vai trò do
	 * PHP đẩy sang, nên hai bên không thể lệch nhau.
	 */
	function selectorVaiTro( goc, role ) {
		var chon = [];

		( role.tags || [] ).forEach( function ( tag ) {
			chon.push( goc + ' ' + tag );
		} );

		( role.manh || [] ).forEach( function ( manh ) {
			chon.push( goc + ' [class*="' + manh + '"]' + TYPO_TRU );
		} );

		return chon.join( ',' );
	}

	/* Trả về null khi để trống hoặc không phải số — lúc đó không sinh khai báo. */
	function so( giaTri, min, max ) {
		if ( giaTri === '' || giaTri === null || giaTri === undefined ) {
			return null;
		}

		var n = Number( giaTri );

		if ( isNaN( n ) ) {
			return null;
		}

		return Math.max( min, Math.min( max, n ) );
	}

	/* Bỏ số 0 thừa ở đuôi: 1.500 -> 1.5 */
	function soGon( n ) {
		return String( Math.round( n * 1000 ) / 1000 );
	}

	function fontTuSlug( slug ) {
		for ( var i = 0; i < FONTS.length; i++ ) {
			if ( FONTS[ i ].slug === slug ) {
				return FONTS[ i ].fontFamily;
			}
		}

		return '';
	}

	var DO_DAM = [ '100', '200', '300', '400', '500', '600', '700', '800', '900' ];
	var VIET_HOA = [ 'none', 'uppercase', 'lowercase', 'capitalize' ];

	function khaiBaoVaiTro( tho ) {
		if ( ! tho ) {
			return null;
		}

		var ra = {};
		var co = false;

		var font = fontTuSlug( tho.font || '' );
		if ( font ) {
			ra[ 'font-family' ] = font;
			co = true;
		}

		var size = so( tho.size, 8, 200 );
		if ( size !== null ) {
			ra[ 'font-size' ] = soGon( size ) + 'px';
			co = true;
		}

		if ( DO_DAM.indexOf( String( tho.weight || '' ) ) !== -1 ) {
			ra[ 'font-weight' ] = String( tho.weight );
			co = true;
		}

		var lh = so( tho.lh, 0.8, 4 );
		if ( lh !== null ) {
			ra[ 'line-height' ] = soGon( lh );
			co = true;
		}

		var ls = so( tho.ls, -5, 20 );
		if ( ls !== null ) {
			ra[ 'letter-spacing' ] = soGon( ls ) + 'px';
			co = true;
		}

		if ( VIET_HOA.indexOf( String( tho.tf || '' ) ) !== -1 ) {
			ra[ 'text-transform' ] = String( tho.tf );
			co = true;
		}

		if ( tho.color ) {
			ra.color = tho.color;
			co = true;
		}

		return co ? ra : null;
	}

	/* Đọc cấu hình chữ đã lọc, kèm chuyển tiếp từ ba thuộc tính cũ. */
	function docTypo( attributes, thietBi ) {
		thietBi = thietBi || 'desktop';

		var tho = nhanhThietBi( attributes, thietBi ).typo || {};
		var ra = {};
		var co = false;

		VAI_TRO.forEach( function ( role ) {
			var kb = khaiBaoVaiTro( tho[ role.ma ] );

			if ( kb ) {
				ra[ role.ma ] = kb;
				co = true;
			}
		} );

		if ( co ) {
			return ra;
		}

		/*
		 * Chỉ nhánh máy tính mới dựng lại từ ba thuộc tính cũ. Dựng cho cả ba mốc
		 * thì mỗi media query lại có một bản sao y hệt — thừa, và khoá cứng giá
		 * trị cũ ở hai mốc kia khiến sau này chỉnh riêng cho điện thoại không ăn.
		 */
		if ( thietBi !== 'desktop' ) {
			return {};
		}

		/* Nội dung lưu trước bước 2: một font cho cả khối + hai màu chữ rời. */
		var fontCu = fontTuSlug( attributes.nntmFontFamily || '' );
		var chuCu = slugSangMau( attributes.nntmTextColor || '' );
		var tieuDeCu = slugSangMau( attributes.nntmHeadingColor || '' );

		if ( ! fontCu && ! chuCu && ! tieuDeCu ) {
			return {};
		}

		VAI_TRO.forEach( function ( role ) {
			var kb = {};

			if ( fontCu ) {
				kb[ 'font-family' ] = fontCu;
			}

			if ( role.ma === 'heading' || role.ma === 'sub' ) {
				if ( tieuDeCu ) {
					kb.color = tieuDeCu;
				} else if ( chuCu ) {
					kb.color = chuCu;
				}
			} else if ( chuCu ) {
				kb.color = chuCu;
			}

			if ( Object.keys( kb ).length ) {
				ra[ role.ma ] = kb;
			}
		} );

		return ra;
	}

	/* Giá trị thô đang lưu của một vai trò, để đổ vào các ô điều khiển. */
	/*
	 * Cấu hình chữ THÔ để đổ vào các ô điều khiển và để ghi đè lên.
	 *
	 * Với nội dung lưu trước bước 2, nntmStyle.typo còn rỗng nhưng ba thuộc
	 * tính cũ vẫn đang điều khiển hiển thị. Nếu trả về rỗng thì:
	 *   - Bảng điều khiển báo "Mặc định" trong khi ngoài trang đang là Lora.
	 *   - Vừa chỉnh một ô bất kỳ là ghi đè typo mới + xoá ba thuộc tính cũ,
	 *     làm font cũ biến mất mà admin không hề đụng vào nó.
	 * Nên ở đây dựng sẵn giá trị tương đương từ ba thuộc tính cũ.
	 */
	function typoTho( attributes, thietBi ) {
		thietBi = thietBi || 'desktop';

		var typo = nhanhThietBi( attributes, thietBi ).typo || {};

		if ( Object.keys( typo ).length ) {
			return typo;
		}

		/* Ba thuộc tính cũ không có khái niệm thiết bị — chỉ dựng cho máy tính. */
		if ( thietBi !== 'desktop' ) {
			return {};
		}

		var fontCu = attributes.nntmFontFamily || '';
		var chuCu = slugSangMau( attributes.nntmTextColor || '' );
		var tieuDeCu = slugSangMau( attributes.nntmHeadingColor || '' );

		if ( ! fontCu && ! chuCu && ! tieuDeCu ) {
			return {};
		}

		var ra = {};

		VAI_TRO.forEach( function ( role ) {
			var kb = {};

			if ( fontCu ) {
				kb.font = fontCu;
			}

			if ( role.ma === 'heading' || role.ma === 'sub' ) {
				if ( tieuDeCu ) {
					kb.color = tieuDeCu;
				} else if ( chuCu ) {
					kb.color = chuCu;
				}
			} else if ( chuCu ) {
				kb.color = chuCu;
			}

			if ( Object.keys( kb ).length ) {
				ra[ role.ma ] = kb;
			}
		} );

		return ra;
	}

	function thoVaiTro( attributes, maVaiTro, thietBi ) {
		return typoTho( attributes, thietBi )[ maVaiTro ] || {};
	}

	/* ------------------------------------------------------------------ *
	 * Các ô điều khiển
	 * ------------------------------------------------------------------ */

	/* Bảng màu của website làm gợi ý, nhưng admin vẫn nhập được màu bất kỳ. */
	function oMau( nhan, giaTri, khiDoi, ghiChu ) {
		return el(
			BaseControl,
			{ label: nhan, help: ghiChu, __nextHasNoMarginBottom: true, className: 'nntm-bs__mau' },
			el( ColorPalette, {
				colors: PALETTE,
				value: giaTri || undefined,
				disableCustomColors: false,
				enableAlpha: true,
				clearable: true,
				onChange: function ( mau ) {
					khiDoi( mau || '' );
				},
			} )
		);
	}

	function oChon( nhan, giaTri, luaChon, khiDoi ) {
		return el( SelectControl, {
			label: nhan,
			value: giaTri,
			options: luaChon,
			onChange: khiDoi,
			__nextHasNoMarginBottom: true,
			__next40pxDefaultSize: true,
		} );
	}

	/* Bốn kiểu nền, hiển thị thành một hàng nút bấm cho gọn. */
	var KIEU_NEN = [
		{ value: 'none', label: __( 'Không', 'nntm' ) },
		{ value: 'color', label: __( 'Màu', 'nntm' ) },
		{ value: 'gradient', label: __( 'Chuyển sắc', 'nntm' ) },
		{ value: 'image', label: __( 'Ảnh', 'nntm' ) },
	];

	function oKieuNen( giaTri, khiDoi ) {
		if ( ! ToggleGroup || ! ToggleGroupOption ) {
			return oChon( __( 'Kiểu nền', 'nntm' ), giaTri, KIEU_NEN.map( function ( m ) {
				return { label: m.label, value: m.value };
			} ), khiDoi );
		}

		return el(
			ToggleGroup,
			{
				label: __( 'Kiểu nền', 'nntm' ),
				value: giaTri,
				isBlock: true,
				onChange: khiDoi,
				__nextHasNoMarginBottom: true,
				__next40pxDefaultSize: true,
				className: 'nntm-bs__kieu',
			},
			KIEU_NEN.map( function ( muc ) {
				return el( ToggleGroupOption, { key: muc.value, value: muc.value, label: muc.label } );
			} )
		);
	}

	var VI_TRI_ANH = [
		{ label: __( 'Giữa', 'nntm' ), value: 'center center' },
		{ label: __( 'Giữa trên', 'nntm' ), value: 'center top' },
		{ label: __( 'Giữa dưới', 'nntm' ), value: 'center bottom' },
		{ label: __( 'Trái', 'nntm' ), value: 'left center' },
		{ label: __( 'Trái trên', 'nntm' ), value: 'left top' },
		{ label: __( 'Trái dưới', 'nntm' ), value: 'left bottom' },
		{ label: __( 'Phải', 'nntm' ), value: 'right center' },
		{ label: __( 'Phải trên', 'nntm' ), value: 'right top' },
		{ label: __( 'Phải dưới', 'nntm' ), value: 'right bottom' },
	];

	var CO_ANH = [
		{ label: __( 'Phủ kín (cover)', 'nntm' ), value: 'cover' },
		{ label: __( 'Vừa khung (contain)', 'nntm' ), value: 'contain' },
		{ label: __( 'Kích thước gốc', 'nntm' ), value: 'auto' },
	];

	var LAP_ANH = [
		{ label: __( 'Không lặp', 'nntm' ), value: 'no-repeat' },
		{ label: __( 'Lặp cả hai chiều', 'nntm' ), value: 'repeat' },
		{ label: __( 'Lặp ngang', 'nntm' ), value: 'repeat-x' },
		{ label: __( 'Lặp dọc', 'nntm' ), value: 'repeat-y' },
	];

	var BAM_ANH = [
		{ label: __( 'Cuộn cùng trang', 'nntm' ), value: 'scroll' },
		{ label: __( 'Đứng yên (parallax)', 'nntm' ), value: 'fixed' },
	];

	function bangNen( nen, dat ) {
		var phan = [ oKieuNen( nen.type, function ( kieu ) {
			dat( 'type', kieu || 'none' );
		} ) ];

		if ( nen.type === 'color' ) {
			phan.push(
				oMau( __( 'Màu nền', 'nntm' ), nen.color, function ( mau ) {
					dat( 'color', mau );
				} )
			);
		}

		if ( nen.type === 'gradient' ) {
			phan.push(
				el(
					'div',
					{ className: 'nntm-bs__doi', key: 'grad-mau' },
					oMau( __( 'Màu đầu', 'nntm' ), nen.grad.from, function ( mau ) {
						dat( 'grad', { from: mau } );
					} ),
					oMau( __( 'Màu cuối', 'nntm' ), nen.grad.to, function ( mau ) {
						dat( 'grad', { to: mau } );
					} )
				)
			);

			phan.push(
				oChon(
					__( 'Kiểu chuyển sắc', 'nntm' ),
					nen.grad.kind,
					[
						{ label: __( 'Thẳng (linear)', 'nntm' ), value: 'linear' },
						{ label: __( 'Toả tròn (radial)', 'nntm' ), value: 'radial' },
					],
					function ( kind ) {
						dat( 'grad', { kind: kind } );
					}
				)
			);

			if ( nen.grad.kind === 'linear' ) {
				phan.push(
					el( RangeControl, {
						key: 'grad-goc',
						label: __( 'Góc nghiêng', 'nntm' ),
						value: nen.grad.angle,
						min: 0,
						max: 360,
						step: 5,
						onChange: function ( goc ) {
							dat( 'grad', { angle: goc === undefined ? 180 : goc } );
						},
						__nextHasNoMarginBottom: true,
						__next40pxDefaultSize: true,
					} )
				);
			}
		}

		if ( nen.type === 'image' ) {
			phan.push(
				el(
					MediaUploadCheck,
					{ key: 'anh-chon' },
					el( MediaUpload, {
						allowedTypes: [ 'image' ],
						value: nen.img.id,
						onSelect: function ( media ) {
							dat( 'img', { id: media.id, url: media.url } );
						},
						render: function ( o ) {
							return el(
								BaseControl,
								{ label: __( 'Ảnh nền', 'nntm' ), __nextHasNoMarginBottom: true },
								nen.img.url
									? el(
											'div',
											{ className: 'nntm-bs__anh' },
											el( 'img', { src: nen.img.url, alt: '' } )
									  )
									: null,
								el(
									Flex,
									{ gap: 2, justify: 'flex-start' },
									el(
										FlexItem,
										null,
										el(
											Button,
											{ variant: 'secondary', size: 'compact', onClick: o.open },
											nen.img.url ? __( 'Đổi ảnh', 'nntm' ) : __( 'Chọn ảnh', 'nntm' )
										)
									),
									nen.img.url
										? el(
												FlexItem,
												null,
												el(
													Button,
													{
														variant: 'tertiary',
														size: 'compact',
														isDestructive: true,
														onClick: function () {
															dat( 'img', { id: 0, url: '' } );
														},
													},
													__( 'Gỡ ảnh', 'nntm' )
												)
										  )
										: null
								)
							);
						},
					} )
				)
			);

			phan.push( oChon( __( 'Vị trí', 'nntm' ), nen.img.pos, VI_TRI_ANH, function ( v ) {
				dat( 'img', { pos: v } );
			} ) );

			phan.push( oChon( __( 'Kích thước', 'nntm' ), nen.img.size, CO_ANH, function ( v ) {
				dat( 'img', { size: v } );
			} ) );

			phan.push( oChon( __( 'Lặp ảnh', 'nntm' ), nen.img.repeat, LAP_ANH, function ( v ) {
				dat( 'img', { repeat: v } );
			} ) );

			phan.push( oChon( __( 'Cách bám', 'nntm' ), nen.img.attach, BAM_ANH, function ( v ) {
				dat( 'img', { attach: v } );
			} ) );

			phan.push(
				el(
					'div',
					{ className: 'nntm-bs__nhom', key: 'lop-phu' },
					el( 'p', { className: 'nntm-bs__nhom-ten' }, __( 'Lớp phủ trên ảnh', 'nntm' ) ),
					oMau( __( 'Màu phủ', 'nntm' ), nen.ov.color, function ( mau ) {
						dat( 'ov', { color: mau } );
					} ),
					el( RangeControl, {
						label: __( 'Độ đậm lớp phủ', 'nntm' ),
						value: nen.ov.opacity,
						min: 0,
						max: 100,
						step: 1,
						onChange: function ( v ) {
							dat( 'ov', { opacity: v === undefined ? 0 : v } );
						},
						help: __( 'Để 0 nếu không cần lớp phủ. Tăng lên khi chữ trên ảnh khó đọc.', 'nntm' ),
						__nextHasNoMarginBottom: true,
						__next40pxDefaultSize: true,
					} )
				)
			);
		}

		if ( nen.type !== 'none' ) {
			phan.push(
				el(
					'div',
					{ className: 'nntm-bs__dat-lai', key: 'dat-lai' },
					el(
						Button,
						{
							variant: 'tertiary',
							size: 'compact',
							isDestructive: true,
							onClick: function () {
								dat( 'type', 'none' );
							},
						},
						__( 'Trả về nền mặc định của khối', 'nntm' )
					)
				)
			);
		}

		return phan;
	}

	/* ------------------------------------------------------------------ *
	 * Bảng CHỮ — tách theo vai trò
	 * ------------------------------------------------------------------ */

	var LUA_CHON_FONT = [ { label: __( 'Mặc định của block', 'nntm' ), value: '' } ].concat(
		FONTS.map( function ( font ) {
			return { label: font.name, value: font.slug };
		} )
	);


	var LUA_CHON_DO_DAM = [ { label: __( 'Mặc định', 'nntm' ), value: '' } ].concat(
		[
			[ '300', __( '300 — Mảnh', 'nntm' ) ],
			[ '400', __( '400 — Thường', 'nntm' ) ],
			[ '500', __( '500 — Vừa', 'nntm' ) ],
			[ '600', __( '600 — Đậm vừa', 'nntm' ) ],
			[ '700', __( '700 — Đậm', 'nntm' ) ],
			[ '800', __( '800 — Rất đậm', 'nntm' ) ],
			[ '900', __( '900 — Đậm nhất', 'nntm' ) ],
		].map( function ( m ) {
			return { label: m[ 1 ], value: m[ 0 ] };
		} )
	);

	var LUA_CHON_VIET_HOA = [
		{ label: __( 'Mặc định', 'nntm' ), value: '' },
		{ label: __( 'Giữ nguyên như nhập', 'nntm' ), value: 'none' },
		{ label: __( 'IN HOA TẤT CẢ', 'nntm' ), value: 'uppercase' },
		{ label: __( 'in thường tất cả', 'nntm' ), value: 'lowercase' },
		{ label: __( 'Hoa Đầu Mỗi Từ', 'nntm' ), value: 'capitalize' },
	];

	/* RangeControl coi undefined là "chưa đặt"; kho lưu dùng chuỗi rỗng. */
	function raSo( giaTri ) {
		return giaTri === '' || giaTri === null || giaTri === undefined ? undefined : Number( giaTri );
	}

	function vaoSo( giaTri ) {
		return giaTri === undefined || giaTri === null || giaTri === '' ? '' : giaTri;
	}

	function oSo( nhan, giaTri, min, max, step, ghiChu, khiDoi ) {
		return el( RangeControl, {
			label: nhan,
			value: raSo( giaTri ),
			min: min,
			max: max,
			step: step,
			help: ghiChu,
			allowReset: true,
			resetFallbackValue: undefined,
			onChange: function ( v ) {
				khiDoi( vaoSo( v ) );
			},
			__nextHasNoMarginBottom: true,
			__next40pxDefaultSize: true,
		} );
	}

	/* Vai trò đã được chỉnh thì chấm dấu lên con chip, để nhìn là biết. */
	function daChinh( tho ) {
		var k;

		for ( k in tho ) {
			if ( Object.prototype.hasOwnProperty.call( tho, k ) && tho[ k ] !== '' && tho[ k ] !== undefined && tho[ k ] !== null ) {
				return true;
			}
		}

		return false;
	}

	/*
	 * Hàng chọn thiết bị, dùng chung cho cả bảng chữ lẫn bảng khoảng cách.
	 *
	 * Không cần đồng bộ với nút xem trước của WordPress: CSS sinh ra dùng media
	 * query thật, nên khi anh bấm xem trước Máy tính bảng / Điện thoại thì khung
	 * soạn thảo hẹp lại và quy tắc tự khớp. Hàng này chỉ quyết định đang SỬA giá
	 * trị của mốc nào.
	 */
	function hangThietBi( attributes, thietBiHienTai, doiThietBi ) {
		return [
			el(
				'div',
				{ className: 'nntm-bs__thietbi', key: 'tb' },
				THIET_BI.map( function ( tb ) {
					var chon = tb.ma === thietBiHienTai;
					var nhanh = nhanhThietBi( attributes, tb.ma );
					var coDat =
						tb.ma !== 'desktop' &&
						( khaiBaoSpace( nhanh.space ).length > 0 ||
							Object.keys( docTypo( attributes, tb.ma ) ).length > 0 );

					return el(
						'button',
						{
							key: tb.ma,
							type: 'button',
							className:
								'nntm-bs__thietbi-nut' + ( chon ? ' is-chon' : '' ) + ( coDat ? ' co-chinh' : '' ),
							'aria-pressed': chon ? 'true' : 'false',
							onClick: function () {
								doiThietBi( tb.ma );
							},
						},
						tb.ten
					);
				} )
			),
			thietBiHienTai === 'desktop'
				? null
				: el(
						'p',
						{ className: 'nntm-bs__thietbi-mota', key: 'tb-mota' },
						__(
							'Để trống là dùng lại giá trị của Máy tính. Chỉ điền khi mốc này cần khác.',
							'nntm'
						)
				  ),
		];
	}

	/* ------------------------------------------------------------------ *
	 * Bảng KHOẢNG CÁCH
	 * ------------------------------------------------------------------ */

	var TEN_CANH = {
		t: 'Trên',
		r: 'Phải',
		b: 'Dưới',
		l: 'Trái',
	};

	function bangKhoangCach( attributes, thietBiHienTai, doiThietBi, datSpace, xoaSpace ) {
		var space = nhanhThietBi( attributes, thietBiHienTai ).space || {};
		var dem = space.pad || {};
		var le = space.mar || {};

		var phan = hangThietBi( attributes, thietBiHienTai, doiThietBi );

		phan.push(
			el(
				'div',
				{ className: 'nntm-bs__nhom', key: 'dem' },
				[ el( 'p', { className: 'nntm-bs__nhom-ten', key: 'ten' }, __( 'Đệm trong (padding)', 'nntm' ) ) ].concat(
					[ 't', 'r', 'b', 'l' ].map( function ( c ) {
						return oSo( __( TEN_CANH[ c ], 'nntm' ), dem[ c ], 0, 300, 1, null, function ( v ) {
							datSpace( 'pad', c, v );
						} );
					} )
				)
			)
		);

		phan.push(
			el(
				'div',
				{ className: 'nntm-bs__nhom', key: 'le' },
				el( 'p', { className: 'nntm-bs__nhom-ten' }, __( 'Lề ngoài (margin)', 'nntm' ) ),
				oSo( __( 'Trên', 'nntm' ), le.t, -200, 200, 1, null, function ( v ) {
					datSpace( 'mar', 't', v );
				} ),
				oSo(
					__( 'Dưới', 'nntm' ),
					le.b,
					-200,
					200,
					1,
					__( 'Số âm để kéo khối sau chồng lên khối này.', 'nntm' ),
					function ( v ) {
						datSpace( 'mar', 'b', v );
					}
				),
				el(
					'p',
					{ className: 'nntm-bs__thietbi-mota' },
					__(
						'Không có lề trái/phải: khối đặt Full Width dùng chính lề ngang để trải hết màn hình, chỉnh vào đó là khối co lại.',
						'nntm'
					)
				)
			)
		);

		if ( khaiBaoSpace( space ).length ) {
			phan.push(
				el(
					'div',
					{ className: 'nntm-bs__dat-lai', key: 'xoa' },
					el(
						Button,
						{
							variant: 'tertiary',
							size: 'compact',
							isDestructive: true,
							onClick: xoaSpace,
						},
						__( 'Xoá khoảng cách của mốc này', 'nntm' )
					)
				)
			);
		}

		return phan;
	}

	function bangChu( attributes, thietBiHienTai, doiThietBi, vaiTroHienTai, doiVaiTro, datChu, xoaVaiTro ) {
		var tho = thoVaiTro( attributes, vaiTroHienTai, thietBiHienTai );

		var chips = el(
			'div',
			{ className: 'nntm-bs__vaitro', key: 'chip' },
			VAI_TRO.map( function ( role ) {
				var chon = role.ma === vaiTroHienTai;

				return el(
					'button',
					{
						key: role.ma,
						type: 'button',
						className:
							'nntm-bs__vaitro-nut' +
							( chon ? ' is-chon' : '' ) +
							( daChinh( thoVaiTro( attributes, role.ma, thietBiHienTai ) ) ? ' co-chinh' : '' ),
						'aria-pressed': chon ? 'true' : 'false',
						onClick: function () {
							doiVaiTro( role.ma );
						},
					},
					role.ten
				);
			} )
		);

		return hangThietBi( attributes, thietBiHienTai, doiThietBi ).concat( [
			chips,
			el(
				'p',
				{ className: 'nntm-bs__vaitro-mota', key: 'mota' },
				__( 'Mỗi mục chỉ đổi phần chữ mang vai trò đó trong khối. Để trống là giữ đúng thiết kế gốc.', 'nntm' )
			),
			el( SelectControl, {
				key: 'font',
				label: __( 'Font chữ', 'nntm' ),
				help: __( 'Chỉ chọn trong danh sách font của website.', 'nntm' ),
				value: tho.font || '',
				options: LUA_CHON_FONT,
				onChange: function ( v ) {
					datChu( { font: v } );
				},
				__nextHasNoMarginBottom: true,
				__next40pxDefaultSize: true,
			} ),
			oSo( __( 'Cỡ chữ (px)', 'nntm' ), tho.size, 8, 200, 1, null, function ( v ) {
				datChu( { size: v } );
			} ),
			el( SelectControl, {
				key: 'weight',
				label: __( 'Độ đậm', 'nntm' ),
				value: tho.weight || '',
				options: LUA_CHON_DO_DAM,
				onChange: function ( v ) {
					datChu( { weight: v } );
				},
				__nextHasNoMarginBottom: true,
				__next40pxDefaultSize: true,
			} ),
			oSo(
				__( 'Giãn dòng', 'nntm' ),
				tho.lh,
				0.8,
				4,
				0.05,
				__( 'Bội số của cỡ chữ. 1.6 nghĩa là dòng cao gấp 1,6 lần cỡ chữ.', 'nntm' ),
				function ( v ) {
					datChu( { lh: v } );
				}
			),
			oSo( __( 'Giãn chữ (px)', 'nntm' ), tho.ls, -5, 20, 0.1, null, function ( v ) {
				datChu( { ls: v } );
			} ),
			el( SelectControl, {
				key: 'tf',
				label: __( 'Viết hoa', 'nntm' ),
				value: tho.tf || '',
				options: LUA_CHON_VIET_HOA,
				onChange: function ( v ) {
					datChu( { tf: v } );
				},
				__nextHasNoMarginBottom: true,
				__next40pxDefaultSize: true,
			} ),
			oMau( __( 'Màu chữ', 'nntm' ), tho.color || '', function ( mau ) {
				datChu( { color: mau } );
			} ),
			daChinh( tho )
				? el(
						'div',
						{ className: 'nntm-bs__dat-lai', key: 'xoa' },
						el(
							Button,
							{
								variant: 'tertiary',
								size: 'compact',
								isDestructive: true,
								onClick: xoaVaiTro,
							},
							__( 'Trả mục này về mặc định', 'nntm' )
						)
				  )
				: null,
		] );
	}

	/* ------------------------------------------------------------------ *
	 * Gắn bảng điều khiển vào mọi block nntm/*
	 * ------------------------------------------------------------------ */

	function themBangDieuKhien( BlockEdit ) {
		return function ( props ) {
			if ( ! coHoTro( props.name ) ) {
				return el( BlockEdit, props );
			}

			var attributes = props.attributes || {};
			var setAttributes = props.setAttributes;
			var nen = docNen( attributes );

			/* Vai trò chữ và mốc thiết bị đang mở — chỉ là trạng thái giao diện. */
			var vaiTroState = wp.element.useState( VAI_TRO.length ? VAI_TRO[ 0 ].ma : 'body' );
			var vaiTroHienTai = vaiTroState[ 0 ];
			var doiVaiTro = vaiTroState[ 1 ];

			var thietBiState = wp.element.useState( 'desktop' );
			var thietBiHienTai = thietBiState[ 0 ];
			var doiThietBi = thietBiState[ 1 ];

			/*
			 * Ghi vào đúng nhánh thiết bị. Máy tính nằm thẳng trong nntmStyle,
			 * hai mốc kia nằm trong nntmStyle.tablet / nntmStyle.mobile.
			 */
			function ghiNhanh( khoa, giaTri ) {
				var style = Object.assign( {}, attributes.nntmStyle || {} );

				if ( thietBiHienTai === 'desktop' ) {
					style[ khoa ] = giaTri;
				} else {
					style[ thietBiHienTai ] = Object.assign( {}, style[ thietBiHienTai ] || {} );
					style[ thietBiHienTai ][ khoa ] = giaTri;
				}

				return style;
			}

			function datSpace( nhom, canh, giaTri ) {
				var space = Object.assign( {}, nhanhThietBi( attributes, thietBiHienTai ).space || {} );

				space[ nhom ] = Object.assign( {}, space[ nhom ] || {} );
				space[ nhom ][ canh ] = giaTri;

				setAttributes( { nntmStyle: ghiNhanh( 'space', space ) } );
			}

			function xoaSpace() {
				setAttributes( { nntmStyle: ghiNhanh( 'space', {} ) } );
			}

			/* Ghi vài khoá của MỘT vai trò, giữ nguyên các vai trò khác. */
			function ghiTypo( typoMoi ) {
				setAttributes( {
					nntmStyle: ghiNhanh( 'typo', typoMoi ),
					/*
					 * Dọn ba thuộc tính cũ. Còn giữ thì mỗi lần đọc lại sẽ có hai
					 * nguồn chọi nhau, và admin không gỡ được font/màu cũ.
					 */
					nntmFontFamily: '',
					nntmTextColor: '',
					nntmHeadingColor: '',
				} );
			}

			/*
				 * Lay tu typoTho() chu khong tu nntmStyle.typo: voi noi dung cu,
				 * typo con rong nhung ba thuoc tinh cu dang dieu khien hien thi.
				 * Ghi thang len typo rong se xoa mat font cu.
				 */
			function datChu( thayDoi ) {
				var typo = Object.assign( {}, typoTho( attributes, thietBiHienTai ) );

				typo[ vaiTroHienTai ] = Object.assign( {}, typo[ vaiTroHienTai ] || {}, thayDoi );

				ghiTypo( typo );
			}

			function xoaVaiTro() {
				var typo = Object.assign( {}, typoTho( attributes, thietBiHienTai ) );

				delete typo[ vaiTroHienTai ];

				ghiTypo( typo );
			}

			/* Ghi một khoá con của nntmStyle.bg, giữ nguyên phần còn lại. */
			function datNen( khoa, giaTri ) {
				var bgMoi = gop( NEN_MAC_DINH, nen );

				if ( giaTri && typeof giaTri === 'object' ) {
					bgMoi[ khoa ] = Object.assign( {}, bgMoi[ khoa ], giaTri );
				} else {
					bgMoi[ khoa ] = giaTri;
				}

				setAttributes( {
					nntmStyle: Object.assign( {}, attributes.nntmStyle || {}, { bg: bgMoi } ),
					/*
					 * Xoá luôn giá trị cũ: nếu còn, mỗi lần đọc lại sẽ có hai
					 * nguồn nền chọi nhau và admin không gỡ được nền cũ.
					 */
					nntmBgColor: '',
				} );
			}

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
							title: __( 'Nền khối', 'nntm' ),
							initialOpen: false,
							className: 'nntm-bs',
						},
						bangNen( nen, datNen )
					),
					el(
						PanelBody,
						{
							title: __( 'Chữ trong khối', 'nntm' ),
							initialOpen: false,
							className: 'nntm-bs',
						},
						bangChu(
							attributes,
							thietBiHienTai,
							doiThietBi,
							vaiTroHienTai,
							doiVaiTro,
							datChu,
							xoaVaiTro
						)
					),
					el(
						PanelBody,
						{
							title: __( 'Khoảng cách', 'nntm' ),
							initialOpen: false,
							className: 'nntm-bs',
						},
						bangKhoangCach( attributes, thietBiHienTai, doiThietBi, datSpace, xoaSpace )
					),
					el(
						PanelBody,
						{
							title: __( 'Bố cục khối', 'nntm' ),
							initialOpen: false,
							className: 'nntm-bs',
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
							__next40pxDefaultSize: true,
						} )
						/*
						 * Ô "Font chữ" cũ đã bỏ khỏi đây: nó áp một font cho cả
						 * khối, nay thuộc bảng "Chữ trong khối" và chọn được
						 * riêng cho từng vai trò.
						 */
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

	/* ------------------------------------------------------------------ *
	 * Xem trước trong trình soạn thảo
	 * ------------------------------------------------------------------ */

	function themXemTruoc( BlockListBlock ) {
		return function ( props ) {
			if ( ! coHoTro( props.name ) ) {
				return el( BlockListBlock, props );
			}

			var lop = dinhDanh( props.clientId );
			var css = dungCss( lop, props.name, props.attributes || {} );

			if ( ! css ) {
				return el( BlockListBlock, props );
			}

			return el(
				Fragment,
				null,
				el( 'style', null, css ),
				el( BlockListBlock, Object.assign( {}, props, {
					className: [ props.className || '', lop ].join( ' ' ).trim(),
				} ) )
			);
		};
	}

	wp.hooks.addFilter(
		'editor.BlockListBlock',
		'nntm/block-style-preview',
		wp.compose && wp.compose.createHigherOrderComponent
			? wp.compose.createHigherOrderComponent( themXemTruoc, 'nntmBlockStylePreview' )
			: themXemTruoc
	);
} )( window.wp );
