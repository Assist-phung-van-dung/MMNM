<?php
/**
 * Hệ thống style dùng chung cho mọi block nntm/*.
 *
 * Mục tiêu (PROMPT 01):
 * - Mỗi block section tự chọn được chiều rộng Full Width / Contained.
 * - Mỗi block tự chọn được màu nền, màu chữ, màu tiêu đề, font chữ — nhưng CHỈ
 *   trong bảng màu và danh sách font chuẩn của website (không cho nhập CSS tuỳ ý).
 *
 * Cách làm: thay vì sửa 21 block.json + 21 editor.js + 21 render.php, ta bơm
 * thuộc tính chung vào lúc đăng ký block (register_block_type_args), thêm bảng
 * điều khiển chung vào trình soạn thảo bằng một filter JS duy nhất
 * (editor.BlockEdit), và gắn class/biến CSS vào thẻ ngoài cùng lúc render
 * (render_block + WP_HTML_Tag_Processor). Block mới thêm sau này tự động có
 * đủ các tuỳ chọn này, không cần viết lại.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Những block KHÔNG nhận bảng điều khiển style chung.
 *
 * - nntm/card: thẻ con, luôn nằm trong một block cha.
 * - nntm/paging: chỉ là cụm phân trang.
 * - nntm/floating-bar, nntm/floating-video: phần tử nổi, không phải section.
 */
function nntm_block_style_excluded_blocks(): array {
	return (array) apply_filters(
		'nntm_block_style_excluded_blocks',
		array(
			'nntm/card',
			'nntm/paging',
			'nntm/floating-bar',
			'nntm/floating-video',
		)
	);
}

function nntm_block_style_supports( string $block_name ): bool {
	if ( 0 !== strpos( $block_name, 'nntm/' ) ) {
		return false;
	}

	return ! in_array( $block_name, nntm_block_style_excluded_blocks(), true );
}

/**
 * Bảng màu chuẩn của website, đọc thẳng từ theme.json.
 *
 * Admin chỉ được chọn trong danh sách này; giá trị khác bị bỏ qua khi render.
 */
function nntm_block_style_palette(): array {
	static $cache = null;

	if ( null !== $cache ) {
		return $cache;
	}

	$palette = array();

	if ( function_exists( 'wp_get_global_settings' ) ) {
		$theme_palette = wp_get_global_settings( array( 'color', 'palette', 'theme' ) );

		if ( is_array( $theme_palette ) ) {
			foreach ( $theme_palette as $mau ) {
				if ( ! is_array( $mau ) || empty( $mau['slug'] ) || empty( $mau['color'] ) ) {
					continue;
				}

				$slug = sanitize_key( (string) $mau['slug'] );
				if ( '' === $slug ) {
					continue;
				}

				$palette[ $slug ] = array(
					'name'  => isset( $mau['name'] ) ? (string) $mau['name'] : $slug,
					'color' => (string) $mau['color'],
				);
			}
		}
	}

	$cache = $palette;

	return $cache;
}

/**
 * Danh sách font được hệ thống cho phép, đọc từ theme.json.
 *
 * Mỗi mục đã có sẵn fallback an toàn trong chuỗi fontFamily (vd: system-ui, serif).
 */
function nntm_block_style_fonts(): array {
	static $cache = null;

	if ( null !== $cache ) {
		return $cache;
	}

	$fonts = array();

	if ( function_exists( 'wp_get_global_settings' ) ) {
		$families = wp_get_global_settings( array( 'typography', 'fontFamilies', 'theme' ) );

		if ( is_array( $families ) ) {
			foreach ( $families as $font ) {
				if ( ! is_array( $font ) || empty( $font['slug'] ) || empty( $font['fontFamily'] ) ) {
					continue;
				}

				$slug = sanitize_key( (string) $font['slug'] );
				if ( '' === $slug ) {
					continue;
				}

				$fonts[ $slug ] = array(
					'name'       => isset( $font['name'] ) ? (string) $font['name'] : $slug,
					'fontFamily' => (string) $font['fontFamily'],
				);
			}
		}
	}

	$cache = $fonts;

	return $cache;
}

/**
 * Các thuộc tính chung được bơm vào mọi block nntm/* phù hợp.
 *
 * Để trống ('') nghĩa là "giữ mặc định của block" — không sinh CSS nào cả.
 */
function nntm_block_style_attributes(): array {
	return array(
		'nntmWidth'        => array(
			'type'    => 'string',
			'enum'    => array( '', 'contained', 'full' ),
			'default' => '',
		),
		'nntmBgColor'      => array(
			'type'    => 'string',
			'default' => '',
		),
		'nntmTextColor'    => array(
			'type'    => 'string',
			'default' => '',
		),
		'nntmHeadingColor' => array(
			'type'    => 'string',
			'default' => '',
		),
		'nntmFontFamily'   => array(
			'type'    => 'string',
			'default' => '',
		),
	);
}

function nntm_block_style_register_attributes( array $args, string $block_name ): array {
	if ( ! nntm_block_style_supports( $block_name ) ) {
		return $args;
	}

	$args['attributes'] = isset( $args['attributes'] ) && is_array( $args['attributes'] )
		? $args['attributes']
		: array();

	foreach ( nntm_block_style_attributes() as $ten => $dinh_nghia ) {
		if ( ! isset( $args['attributes'][ $ten ] ) ) {
			$args['attributes'][ $ten ] = $dinh_nghia;
		}
	}

	return $args;
}
add_filter( 'register_block_type_args', 'nntm_block_style_register_attributes', 10, 2 );

/**
 * Đổi slug màu sang mã màu thật. Trả về '' nếu slug không có trong bảng màu.
 */
function nntm_block_style_color_value( $slug ): string {
	if ( ! is_string( $slug ) ) {
		return '';
	}

	$slug = sanitize_key( $slug );
	if ( '' === $slug ) {
		return '';
	}

	$palette = nntm_block_style_palette();

	return isset( $palette[ $slug ] ) ? $palette[ $slug ]['color'] : '';
}

function nntm_block_style_font_value( $slug ): string {
	if ( ! is_string( $slug ) ) {
		return '';
	}

	$slug = sanitize_key( $slug );
	if ( '' === $slug ) {
		return '';
	}

	$fonts = nntm_block_style_fonts();

	return isset( $fonts[ $slug ] ) ? $fonts[ $slug ]['fontFamily'] : '';
}

/**
 * Từ attributes của block sang danh sách class + các biến CSS cần gắn.
 *
 * @return array{classes: string[], vars: array<string,string>}
 */
function nntm_block_style_compute( array $attributes ): array {
	$classes = array();
	$vars    = array();

	$width = isset( $attributes['nntmWidth'] ) ? sanitize_key( (string) $attributes['nntmWidth'] ) : '';
	if ( 'full' === $width ) {
		$classes[] = 'nntm-block--full';
	} elseif ( 'contained' === $width ) {
		$classes[] = 'nntm-block--contained';
	}

	$bg = nntm_block_style_color_value( $attributes['nntmBgColor'] ?? '' );
	if ( '' !== $bg ) {
		$classes[]                = 'nntm-block--co-nen';
		$vars['--nntm-block-nen'] = $bg;
	}

	$text = nntm_block_style_color_value( $attributes['nntmTextColor'] ?? '' );
	if ( '' !== $text ) {
		$classes[]                = 'nntm-block--co-chu';
		$vars['--nntm-block-chu'] = $text;
	}

	$heading = nntm_block_style_color_value( $attributes['nntmHeadingColor'] ?? '' );
	if ( '' !== $heading ) {
		$classes[]                    = 'nntm-block--co-tieu-de';
		$vars['--nntm-block-tieu-de'] = $heading;
	}

	$font = nntm_block_style_font_value( $attributes['nntmFontFamily'] ?? '' );
	if ( '' !== $font ) {
		$classes[]                 = 'nntm-block--co-font';
		$vars['--nntm-block-font'] = $font;
	}

	if ( $classes ) {
		array_unshift( $classes, 'nntm-block-style' );
	}

	return array(
		'classes' => $classes,
		'vars'    => $vars,
	);
}

/**
 * Gắn class + biến CSS vào thẻ ngoài cùng của block khi render.
 *
 * Dùng WP_HTML_Tag_Processor để không phải sửa render.php của từng block.
 */
function nntm_block_style_render_block( string $block_content, array $block ): string {
	if ( '' === trim( $block_content ) ) {
		return $block_content;
	}

	$block_name = isset( $block['blockName'] ) ? (string) $block['blockName'] : '';
	if ( ! nntm_block_style_supports( $block_name ) ) {
		return $block_content;
	}

	$attributes = ( isset( $block['attrs'] ) && is_array( $block['attrs'] ) ) ? $block['attrs'] : array();
	$tinh       = nntm_block_style_compute( $attributes );

	if ( empty( $tinh['classes'] ) || ! class_exists( 'WP_HTML_Tag_Processor' ) ) {
		return $block_content;
	}

	$processor = new WP_HTML_Tag_Processor( $block_content );

	if ( ! $processor->next_tag() ) {
		return $block_content;
	}

	foreach ( $tinh['classes'] as $class ) {
		$processor->add_class( $class );
	}

	if ( ! empty( $tinh['vars'] ) ) {
		$khai_bao = array();

		foreach ( $tinh['vars'] as $ten => $gia_tri ) {
			// $gia_tri luôn đến từ theme.json (bảng màu / danh sách font), không phải input admin.
			$khai_bao[] = $ten . ':' . $gia_tri;
		}

		$style_cu  = (string) $processor->get_attribute( 'style' );
		$style_moi = implode( ';', $khai_bao ) . ';' . $style_cu;

		$processor->set_attribute( 'style', $style_moi );
	}

	return $processor->get_updated_html();
}
add_filter( 'render_block', 'nntm_block_style_render_block', 10, 2 );

/**
 * CSS dùng chung cho hệ thống style block.
 */
function nntm_block_style_enqueue(): void {
	$css_path = NNTM_THEME_DIR . '/assets/css/block-style.css';

	/*
	 * Nạp SAU các tệp CSS theo trang. Lựa chọn của admin trong trình soạn thảo
	 * phải thắng những dòng CSS ghim cứng theo trang (vd .home .nntm-...) khi hai
	 * bên bằng độ ưu tiên; bằng nhau thì tệp nạp sau thắng.
	 */
	wp_enqueue_style(
		'nntm-block-style',
		NNTM_THEME_URI . '/assets/css/block-style.css',
		array( 'nntm-tokens', 'nntm-base', 'nntm-layout' ),
		nntm_asset_version( $css_path )
	);
}
add_action( 'wp_enqueue_scripts', 'nntm_block_style_enqueue', 100 );

/**
 * Đo bề ngang viewport KHÔNG tính thanh cuộn, đưa vào biến --nntm-vw.
 *
 * Full Width dùng calc(50% - var(--nntm-vw)/2). Nếu dùng thẳng 100vw thì trên
 * Windows (thanh cuộn chiếm chỗ) block sẽ rộng hơn viewport ~15px và lệch tâm.
 */
function nntm_block_style_viewport_script(): void {
	?>
	<script>
		(function () {
			var root = document.documentElement;

			function doVw() {
				/*
				 * Đo bằng <body> khi đã có: bề ngang của nó luôn là bề ngang thật
				 * sự dùng được, đã trừ thanh cuộn dọc. documentElement.clientWidth
				 * trong <head> (lúc chưa có body) còn tính cả chỗ thanh cuộn nên
				 * chỉ dùng tạm cho lần đo đầu.
				 */
				var rong = ( document.body && document.body.clientWidth ) || root.clientWidth;

				/*
				 * Số 0 vẫn là giá trị hợp lệ với CSS nên sẽ đè mất fallback 100vw
				 * — bỏ qua luôn cho chắc.
				 */
				if ( rong > 0 ) {
					root.style.setProperty( '--nntm-vw', rong + 'px' );
				}
			}

			doVw();

			/*
			 * Thanh cuộn dọc xuất hiện muộn: lúc màn hình chờ còn khoá cuộn thì
			 * chưa có, khoá xong mới có, và bề ngang khả dụng tụt đi đúng bề rộng
			 * thanh cuộn. Bám theo <body> vì bề ngang của nó chính là bề ngang khả
			 * dụng, đổi lúc nào ResizeObserver báo lúc đó — không đoán theo mốc
			 * thời gian.
			 */
			function theoDoi() {
				doVw();

				if ( window.ResizeObserver && document.body ) {
					new window.ResizeObserver( doVw ).observe( document.body );
				}
			}

			if ( 'loading' === document.readyState ) {
				document.addEventListener( 'DOMContentLoaded', theoDoi );
			} else {
				theoDoi();
			}

			window.addEventListener( 'load', doVw );

			var hen = null;
			window.addEventListener( 'resize', function () {
				if ( hen ) {
					window.clearTimeout( hen );
				}
				hen = window.setTimeout( doVw, 100 );
			}, { passive: true } );
		})();
	</script>
	<?php
}
/*
 * Chay CUOI <head>, tuc la sau moi the <link rel="stylesheet">. Trinh duyet
 * doi CSS tai xong moi chay script dat sau no, nho vay clientWidth doc duoc da
 * tinh ca cho thanh cuon ma base.css da chua san (scrollbar-gutter: stable).
 * Neu chay som hon thi so do bi du dung be rong thanh cuon.
 */
add_action( 'wp_head', 'nntm_block_style_viewport_script', 999 );

/**
 * Bảng điều khiển chung trong trình soạn thảo.
 */
function nntm_block_style_editor_assets(): void {
	$relative = '/assets/js/editor/block-style.js';
	$absolute = NNTM_THEME_DIR . $relative;

	wp_enqueue_script(
		'nntm-block-style-editor',
		NNTM_THEME_URI . $relative,
		array( 'wp-hooks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n', 'wp-compose' ),
		nntm_asset_version( $absolute ),
		true
	);

	$mau = array();
	foreach ( nntm_block_style_palette() as $slug => $item ) {
		$mau[] = array(
			'slug'  => $slug,
			'name'  => $item['name'],
			'color' => $item['color'],
		);
	}

	$font = array();
	foreach ( nntm_block_style_fonts() as $slug => $item ) {
		$font[] = array(
			'slug'       => $slug,
			'name'       => $item['name'],
			'fontFamily' => $item['fontFamily'],
		);
	}

	wp_localize_script(
		'nntm-block-style-editor',
		'nntmBlockStyle',
		array(
			'palette'  => $mau,
			'fonts'    => $font,
			'excluded' => array_values( nntm_block_style_excluded_blocks() ),
		)
	);
}
add_action( 'enqueue_block_editor_assets', 'nntm_block_style_editor_assets' );
