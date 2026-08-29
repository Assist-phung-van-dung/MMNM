<?php
/**
 * Hệ thống style dùng chung cho mọi block nntm/*.
 *
 * BƯỚC 1 — NỀN. BƯỚC 2 — CHỮ THEO VAI TRÒ. BƯỚC 3 — KHOẢNG CÁCH & THIẾT BỊ.
 *
 * Trước đây mỗi block chỉ chọn được một màu nền trong bảng màu cứng của
 * theme.json, và màu đó được gắn vào thẻ ngoài cùng bằng một biến CSS. Cách đó
 * hỏng ở hai chỗ:
 *
 *   1. Nhiều block có một lớp con phủ kín toàn bộ diện tích kèm nền cứng
 *      (article-mosaic__inner, engineering-earth__white, dt__story,
 *      card-list__band) — chọn màu ở thẻ ngoài xong không nhìn thấy gì.
 *   2. Chỉ có màu đặc. Không gradient, không ảnh nền, không lớp phủ.
 *
 * Bản này thay biến CSS inline bằng CSS sinh riêng cho từng khối: mỗi khối có
 * nền được cấp một class định danh `nntm-s-xxxxxxxx`, và một thẻ <style> đặt
 * ngay trước khối. Ba lần lặp class (0,3,0) đủ thắng mọi quy tắc nền có sẵn
 * trong CSS của block mà không cần !important.
 *
 * Bước 2 gỡ nốt màu chữ / màu tiêu đề / font ra khỏi cách cũ (một biến CSS,
 * nhắm theo thẻ h1..h6/p/li/a) và thay bằng năm vai trò nhắm theo class — vì
 * trong theme này cùng một vai trò được viết bằng đủ loại thẻ, nhãn và ngày
 * tháng nằm trong <span>, nên cách nhắm theo thẻ bỏ sót phần lớn chữ.
 *
 * Bước 3 thêm đệm/lề, và tách chữ lẫn khoảng cách theo ba mốc thiết bị.
 * Chỉ còn chiều rộng là đi bằng class tĩnh trong block-style.css.
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
 * Lớp con phủ kín diện tích của block kèm nền cứng.
 *
 * Khi admin đặt nền cho block, những lớp này phải trong suốt lại, nếu không
 * nền vừa chọn bị chúng che mất hoàn toàn.
 *
 * @return array<string, string[]> tên block => danh sách class con.
 */
function nntm_block_style_surface_map(): array {
	return (array) apply_filters(
		'nntm_block_style_surface_map',
		array(
			'nntm/article-mosaic'    => array( 'nntm-article-mosaic__inner' ),
			'nntm/engineering-earth' => array( 'nntm-engineering-earth__white' ),
			'nntm/dieu-thuong'       => array( 'nntm-dt__story' ),
			'nntm/card-list'         => array( 'nntm-card-list__band' ),
		)
	);
}

/**
 * Bảng màu gợi ý, đọc thẳng từ theme.json.
 *
 * Từ bước 1 trở đi admin được nhập màu tự do; bảng này chỉ còn là danh sách
 * bấm nhanh cho đúng nhận diện, không còn là giới hạn.
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
 * nntmStyle giữ toàn bộ cấu hình mới:
 *   bg     — nền (dùng chung, không tách theo thiết bị)
 *   typo   — chữ theo vai trò, nhánh Máy tính
 *   space  — đệm/lề, nhánh Máy tính
 *   tablet / mobile — { typo, space } riêng cho hai mốc còn lại
 *
 * Bốn thuộc tính cũ vẫn được đăng ký để nội dung đã lưu trong CSDL không vỡ;
 * chúng chỉ còn được ĐỌC để dựng lại cấu hình mới, không còn đường xử lý riêng.
 */
function nntm_block_style_attributes(): array {
	return array(
		'nntmStyle'        => array(
			'type'    => 'object',
			'default' => array(),
		),
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
 * Lọc một giá trị màu do admin nhập.
 *
 * Admin được chọn màu tự do nên không thể đối chiếu với danh sách cố định nữa.
 * Thay vào đó chỉ nhận đúng những dạng màu CSS hợp lệ và không chứa ký tự có
 * thể thoát ra khỏi khai báo (dấu ; { } " ' \ hay chuỗi url).
 *
 * @return string Màu đã lọc, hoặc '' nếu không hợp lệ.
 */
function nntm_block_style_sanitize_color( $mau ): string {
	if ( ! is_string( $mau ) ) {
		return '';
	}

	$mau = trim( $mau );
	if ( '' === $mau || strlen( $mau ) > 64 ) {
		return '';
	}

	// #rgb / #rgba / #rrggbb / #rrggbbaa
	if ( preg_match( '/^#(?:[0-9a-fA-F]{3,4}|[0-9a-fA-F]{6}|[0-9a-fA-F]{8})$/', $mau ) ) {
		return $mau;
	}

	// rgb() / rgba() / hsl() / hsla() — chỉ số, dấu phẩy, phần trăm, khoảng trắng, dấu chấm, dấu /.
	if ( preg_match( '/^(?:rgba?|hsla?)\(\s*[0-9.,%\s\/deg-]+\)$/i', $mau ) ) {
		return $mau;
	}

	// Từ khoá màu CSS: chỉ chữ cái (red, transparent, currentColor...).
	if ( preg_match( '/^[a-zA-Z]{3,20}$/', $mau ) ) {
		return $mau;
	}

	return '';
}

/**
 * Lọc một giá trị chỉ được nằm trong danh sách cho trước.
 */
function nntm_block_style_sanitize_enum( $gia_tri, array $cho_phep, string $mac_dinh ): string {
	if ( ! is_string( $gia_tri ) ) {
		return $mac_dinh;
	}

	return in_array( $gia_tri, $cho_phep, true ) ? $gia_tri : $mac_dinh;
}

/**
 * Đọc cấu hình nền đã được chuẩn hoá từ attributes của block.
 *
 * Trả về mảng đã lọc sạch, hoặc null nếu block không đặt nền.
 */
function nntm_block_style_doc_nen( array $attributes ): ?array {
	$style = ( isset( $attributes['nntmStyle'] ) && is_array( $attributes['nntmStyle'] ) )
		? $attributes['nntmStyle']
		: array();

	$bg = ( isset( $style['bg'] ) && is_array( $style['bg'] ) ) ? $style['bg'] : array();

	$loai = nntm_block_style_sanitize_enum(
		$bg['type'] ?? '',
		array( 'none', 'color', 'gradient', 'image' ),
		'none'
	);

	/*
	 * Tương thích ngược: nội dung lưu trước bước 1 chỉ có nntmBgColor (slug
	 * trong bảng màu theme.json). Nếu chưa có cấu hình nền mới thì dựng lại
	 * một nền màu đặc từ slug cũ.
	 */
	if ( 'none' === $loai && empty( $bg ) ) {
		$mau_cu = nntm_block_style_color_value( $attributes['nntmBgColor'] ?? '' );

		if ( '' !== $mau_cu ) {
			return array(
				'type'  => 'color',
				'color' => $mau_cu,
			);
		}
	}

	if ( 'none' === $loai ) {
		return null;
	}

	if ( 'color' === $loai ) {
		$mau = nntm_block_style_sanitize_color( $bg['color'] ?? '' );

		return ( '' === $mau ) ? null : array(
			'type'  => 'color',
			'color' => $mau,
		);
	}

	if ( 'gradient' === $loai ) {
		$grad = ( isset( $bg['grad'] ) && is_array( $bg['grad'] ) ) ? $bg['grad'] : array();

		$tu  = nntm_block_style_sanitize_color( $grad['from'] ?? '' );
		$den = nntm_block_style_sanitize_color( $grad['to'] ?? '' );

		if ( '' === $tu || '' === $den ) {
			return null;
		}

		return array(
			'type' => 'gradient',
			'grad' => array(
				'from'  => $tu,
				'to'    => $den,
				'angle' => max( 0, min( 360, (int) ( $grad['angle'] ?? 180 ) ) ),
				'kind'  => nntm_block_style_sanitize_enum( $grad['kind'] ?? '', array( 'linear', 'radial' ), 'linear' ),
			),
		);
	}

	// image
	$img = ( isset( $bg['img'] ) && is_array( $bg['img'] ) ) ? $bg['img'] : array();
	$url = isset( $img['url'] ) ? esc_url_raw( (string) $img['url'] ) : '';

	if ( '' === $url ) {
		return null;
	}

	$ov      = ( isset( $bg['ov'] ) && is_array( $bg['ov'] ) ) ? $bg['ov'] : array();
	$ov_mau  = nntm_block_style_sanitize_color( $ov['color'] ?? '' );
	$ov_dam  = max( 0, min( 100, (int) ( $ov['opacity'] ?? 0 ) ) );

	return array(
		'type' => 'image',
		'img'  => array(
			'url'    => $url,
			'pos'    => nntm_block_style_sanitize_enum(
				$img['pos'] ?? '',
				array( 'center center', 'center top', 'center bottom', 'left center', 'left top', 'left bottom', 'right center', 'right top', 'right bottom' ),
				'center center'
			),
			'size'   => nntm_block_style_sanitize_enum( $img['size'] ?? '', array( 'cover', 'contain', 'auto' ), 'cover' ),
			'repeat' => nntm_block_style_sanitize_enum( $img['repeat'] ?? '', array( 'no-repeat', 'repeat', 'repeat-x', 'repeat-y' ), 'no-repeat' ),
			'attach' => nntm_block_style_sanitize_enum( $img['attach'] ?? '', array( 'scroll', 'fixed' ), 'scroll' ),
		),
		'ov'   => array(
			'color'   => $ov_mau,
			'opacity' => $ov_dam,
		),
	);
}

/**
 * Đổi màu + độ mờ thành một màu có kênh alpha, dùng cho lớp phủ.
 *
 * color-mix() giữ nguyên được mọi dạng màu CSS (hex, rgb, hsl, từ khoá) mà
 * không phải tự phân tích chuỗi.
 */
function nntm_block_style_lop_phu( string $mau, int $do_mo ): string {
	if ( '' === $mau || $do_mo <= 0 ) {
		return '';
	}

	return sprintf( 'color-mix(in srgb, %s %d%%, transparent)', $mau, $do_mo );
}

/**
 * Sinh các khai báo CSS cho phần nền.
 *
 * @return string[] Danh sách khai báo, vd: array( 'background-color:#fff' ).
 */
function nntm_block_style_css_nen( array $nen ): array {
	$khai_bao = array();

	if ( 'color' === $nen['type'] ) {
		$khai_bao[] = 'background-color:' . $nen['color'];
		$khai_bao[] = 'background-image:none';

		return $khai_bao;
	}

	if ( 'gradient' === $nen['type'] ) {
		$g = $nen['grad'];

		$khai_bao[] = 'radial' === $g['kind']
			? sprintf( 'background-image:radial-gradient(circle at center, %s 0%%, %s 100%%)', $g['from'], $g['to'] )
			: sprintf( 'background-image:linear-gradient(%ddeg, %s 0%%, %s 100%%)', $g['angle'], $g['from'], $g['to'] );

		return $khai_bao;
	}

	// image
	$img = $nen['img'];
	$phu = nntm_block_style_lop_phu( $nen['ov']['color'], $nen['ov']['opacity'] );

	/*
	 * Lớp phủ được xếp thành một lớp gradient đặc nằm TRÊN ảnh, thay vì dùng
	 * ::before. Nhiều block đã dùng ::before của thẻ ngoài cùng cho việc khác,
	 * chèn thêm vào đó sẽ đụng nhau.
	 */
	$lop = array();
	if ( '' !== $phu ) {
		$lop[] = sprintf( 'linear-gradient(0deg, %1$s 0%%, %1$s 100%%)', $phu );
	}
	$lop[] = sprintf( 'url("%s")', $img['url'] );

	$khai_bao[] = 'background-image:' . implode( ',', $lop );
	$khai_bao[] = 'background-position:' . $img['pos'];
	$khai_bao[] = 'background-size:' . $img['size'];
	$khai_bao[] = 'background-repeat:' . $img['repeat'];
	$khai_bao[] = 'background-attachment:' . $img['attach'];

	return $khai_bao;
}

/* ====================================================================== *
 * BƯỚC 2 — TYPOGRAPHY THEO VAI TRÒ
 * ====================================================================== */

/**
 * Những mảnh class KHÔNG bao giờ là chữ.
 *
 * Vai trò được nhận ra qua hậu tố class (xem nntm_block_style_typo_roles), mà
 * cách đó sẽ quét trúng cả ảnh, biểu tượng và lớp bố cục — ví dụ
 * `__lead-img`, `__date-icon`, `__heading-group`, `__text-inner`. Danh sách này
 * loại chúng ra.
 *
 * Gói trong một :not(:is(...)) duy nhất để cả cụm chỉ nặng (0,1,0); viết thành
 * nhiều :not() liên tiếp thì độ ưu tiên cộng dồn, khó lường.
 */
function nntm_block_style_typo_loai_tru(): string {
	static $cache = null;

	if ( null !== $cache ) {
		return $cache;
	}

	$manh = array( '-img', 'icon', 'glyph', 'media', '-group', '-wrap', '-inner', '-stage', '-el', '-field', '-slot' );

	$dieu_kien = array();
	foreach ( $manh as $m ) {
		$dieu_kien[] = '[class*="' . $m . '"]';
	}

	$cache = ':not(:is(' . implode( ',', $dieu_kien ) . '))';

	return $cache;
}

/**
 * Năm vai trò chữ, và cách nhận ra chúng.
 *
 * Vì sao bám theo class chứ không theo thẻ HTML: trong theme này cùng một vai
 * trò được viết bằng đủ loại thẻ — `__heading` khi là <h2> khi là <p>, `__body`
 * khi là <div> khi là <span>. Cách cũ nhắm theo thẻ (h1..h6, p, li, a...) nên
 * bỏ sót mọi nhãn nằm trong <span>/<div>, và đó chính là lý do đổi font trước
 * đây chỉ ăn một nửa, một khối hiện ra hai font.
 *
 * 'tags'  — thẻ trần, cho phần nội dung do người viết gõ tự do.
 * 'manh'  — mảnh chuỗi tìm trong thuộc tính class.
 *
 * THỨ TỰ CÓ Ý NGHĨA. Một class có thể trúng nhiều vai trò (`__subheading` trúng
 * cả 'heading' lẫn 'sub'); các quy tắc sinh ra bằng độ ưu tiên nên cái đứng sau
 * thắng. Vì vậy 'sub' phải đứng sau 'heading', 'button' phải đứng sau 'label'
 * (để `nntm-cta__label` được tính là nút chứ không phải nhãn).
 *
 * @return array<string, array{ten: string, tags: string[], manh: string[]}>
 */
function nntm_block_style_typo_roles(): array {
	return (array) apply_filters(
		'nntm_block_style_typo_roles',
		array(
			'body'    => array(
				'ten'  => __( 'Nội dung', 'nntm' ),
				'tags' => array( 'p', 'li', 'blockquote', 'dd', 'dt', 'figcaption' ),
				'manh' => array( 'body', 'text', 'excerpt', 'desc', 'caption', 'content' ),
			),
			'heading' => array(
				'ten'  => __( 'Tiêu đề', 'nntm' ),
				'tags' => array( 'h1', 'h2', 'h3', 'h4', 'h5', 'h6' ),
				'manh' => array( 'heading', 'title', 'quote' ),
			),
			'sub'     => array(
				'ten'  => __( 'Tiêu đề phụ', 'nntm' ),
				'tags' => array(),
				'manh' => array( '__sub', 'subtitle', 'subheading' ),
			),
			'label'   => array(
				'ten'  => __( 'Nhãn nhỏ', 'nntm' ),
				'tags' => array(),
				'manh' => array( 'eyebrow', '__cat', 'date', 'label', 'meta', 'location', 'name', 'attribution', 'counter' ),
			),
			'button'  => array(
				'ten'  => __( 'Nút', 'nntm' ),
				'tags' => array(),
				'manh' => array( 'btn', 'cta', 'link', 'view-all', 'detail', 'arrow', 'quicklink' ),
			),
		)
	);
}

/**
 * Danh sách selector của một vai trò, đã gắn gốc là class định danh của khối.
 *
 * @return string Chuỗi selector ngăn bằng dấu phẩy.
 */
function nntm_block_style_typo_selector( string $goc, array $role ): string {
	$tru  = nntm_block_style_typo_loai_tru();
	$chon = array();

	foreach ( $role['tags'] as $tag ) {
		$chon[] = $goc . ' ' . $tag;
	}

	foreach ( $role['manh'] as $manh ) {
		$chon[] = $goc . ' [class*="' . $manh . '"]' . $tru;
	}

	return implode( ',', $chon );
}

/**
 * Đọc một con số trong khoảng cho phép.
 *
 * Trả về null khi admin để trống hoặc nhập thứ không phải số — lúc đó không
 * sinh khai báo nào, block giữ nguyên giá trị gốc trong CSS của nó.
 */
function nntm_block_style_so( $gia_tri, float $min, float $max ): ?float {
	if ( is_string( $gia_tri ) ) {
		$gia_tri = trim( $gia_tri );
	}

	if ( '' === $gia_tri || null === $gia_tri || is_bool( $gia_tri ) || ! is_numeric( $gia_tri ) ) {
		return null;
	}

	return max( $min, min( $max, (float) $gia_tri ) );
}

/**
 * Bỏ số 0 thừa ở đuôi: 1.500 -> 1.5, 20.000 -> 20.
 */
function nntm_block_style_so_gon( float $so ): string {
	$chuoi = number_format( $so, 3, '.', '' );

	if ( false !== strpos( $chuoi, '.' ) ) {
		$chuoi = rtrim( rtrim( $chuoi, '0' ), '.' );
	}

	return ( '' === $chuoi || '-0' === $chuoi ) ? '0' : $chuoi;
}

/**
 * Lọc cấu hình chữ của MỘT vai trò thành các khai báo CSS.
 *
 * Khoá nào admin để trống thì bỏ hẳn, không sinh khai báo — nguyên tắc xuyên
 * suốt: không chọn gì thì block giữ đúng thiết kế gốc.
 *
 * @return array<string,string> Ví dụ: array( 'font-size' => '20px' ).
 */
function nntm_block_style_sanitize_typo_role( $tho ): array {
	if ( ! is_array( $tho ) ) {
		return array();
	}

	$ra = array();

	$font = nntm_block_style_font_value( $tho['font'] ?? '' );
	if ( '' !== $font ) {
		$ra['font-family'] = $font;
	}

	$size = nntm_block_style_so( $tho['size'] ?? '', 8, 200 );
	if ( null !== $size ) {
		$ra['font-size'] = nntm_block_style_so_gon( $size ) . 'px';
	}

	$weight = nntm_block_style_sanitize_enum(
		(string) ( $tho['weight'] ?? '' ),
		array( '100', '200', '300', '400', '500', '600', '700', '800', '900' ),
		''
	);
	if ( '' !== $weight ) {
		$ra['font-weight'] = $weight;
	}

	$lh = nntm_block_style_so( $tho['lh'] ?? '', 0.8, 4 );
	if ( null !== $lh ) {
		// Không đơn vị: giãn dòng bám theo cỡ chữ của chính phần tử.
		$ra['line-height'] = nntm_block_style_so_gon( $lh );
	}

	$ls = nntm_block_style_so( $tho['ls'] ?? '', -5, 20 );
	if ( null !== $ls ) {
		$ra['letter-spacing'] = nntm_block_style_so_gon( $ls ) . 'px';
	}

	$tf = nntm_block_style_sanitize_enum(
		(string) ( $tho['tf'] ?? '' ),
		array( 'none', 'uppercase', 'lowercase', 'capitalize' ),
		''
	);
	if ( '' !== $tf ) {
		$ra['text-transform'] = $tf;
	}

	$color = nntm_block_style_sanitize_color( $tho['color'] ?? '' );
	if ( '' !== $color ) {
		$ra['color'] = $color;
	}

	return $ra;
}

/* ====================================================================== *
 * BƯỚC 3 — KHOẢNG CÁCH & THEO THIẾT BỊ
 * ====================================================================== */

/**
 * Ba mốc thiết bị, kèm điều kiện media query.
 *
 * Không tự đặt mốc mới: 767px và 1151px là hai mốc theme đang dùng nhiều nhất
 * (đếm trên toàn bộ CSS: 45 và 42 lần). Đặt mốc khác thì khoảng cách admin
 * chỉnh sẽ đổi lệch pha với chính bố cục của block.
 *
 * Máy tính không có điều kiện — nó là nền, hai mốc kia chỉ ghi đè lên. Thứ tự
 * xuất phải là máy tính -> máy tính bảng -> điện thoại: dưới 768px thì cả hai
 * media query cùng khớp, cái đứng sau thắng.
 *
 * @return array<string, array{ten: string, dieu_kien: string}>
 */
function nntm_block_style_thiet_bi(): array {
	return (array) apply_filters(
		'nntm_block_style_thiet_bi',
		array(
			'desktop' => array(
				'ten'       => __( 'Máy tính', 'nntm' ),
				'dieu_kien' => '',
			),
			'tablet'  => array(
				'ten'       => __( 'Máy tính bảng', 'nntm' ),
				'dieu_kien' => '(max-width: 1151px)',
			),
			'mobile'  => array(
				'ten'       => __( 'Điện thoại', 'nntm' ),
				'dieu_kien' => '(max-width: 767px)',
			),
		)
	);
}

/**
 * Lọc cấu hình khoảng cách thành các khai báo CSS.
 *
 * Đệm (padding): đủ bốn cạnh, 0–300px.
 * Lề (margin): CHỈ trên và dưới, -200–200px.
 *
 * Vì sao lề không có trái/phải: block đặt Full Width dùng
 * `margin-inline: calc(50% - var(--nntm-vw)/2)` để trải hết bề ngang. Quy tắc
 * sinh ở đây nặng (0,3,0), đè mất quy tắc full width (0,2,0) — cho chỉnh lề
 * ngang thì chỉ cần lỡ tay là khối full width co lại mà không rõ vì sao. Nhu
 * cầu thật gần như luôn là giãn cách trên/dưới giữa các khối.
 *
 * @return string[] Ví dụ: array( 'padding-top:20px' ).
 */
function nntm_block_style_doc_space( $tho ): array {
	if ( ! is_array( $tho ) ) {
		return array();
	}

	$ra = array();

	$dem  = ( isset( $tho['pad'] ) && is_array( $tho['pad'] ) ) ? $tho['pad'] : array();
	$canh = array( 't' => 'top', 'r' => 'right', 'b' => 'bottom', 'l' => 'left' );

	foreach ( $canh as $ma => $ten ) {
		$so = nntm_block_style_so( $dem[ $ma ] ?? '', 0, 300 );

		if ( null !== $so ) {
			$ra[] = 'padding-' . $ten . ':' . nntm_block_style_so_gon( $so ) . 'px';
		}
	}

	$le = ( isset( $tho['mar'] ) && is_array( $tho['mar'] ) ) ? $tho['mar'] : array();

	foreach ( array( 't' => 'top', 'b' => 'bottom' ) as $ma => $ten ) {
		$so = nntm_block_style_so( $le[ $ma ] ?? '', -200, 200 );

		if ( null !== $so ) {
			$ra[] = 'margin-' . $ten . ':' . nntm_block_style_so_gon( $so ) . 'px';
		}
	}

	return $ra;
}

/**
 * Nhánh cấu hình của một thiết bị.
 *
 * Máy tính nằm thẳng trong nntmStyle; hai thiết bị kia nằm trong nntmStyle.tablet
 * và nntmStyle.mobile. Nhờ vậy nội dung lưu từ bước 2 vẫn đọc được nguyên vẹn —
 * nó chính là nhánh máy tính.
 */
function nntm_block_style_nhanh_thiet_bi( array $attributes, string $thiet_bi ): array {
	$style = ( isset( $attributes['nntmStyle'] ) && is_array( $attributes['nntmStyle'] ) )
		? $attributes['nntmStyle']
		: array();

	if ( 'desktop' === $thiet_bi ) {
		return $style;
	}

	return ( isset( $style[ $thiet_bi ] ) && is_array( $style[ $thiet_bi ] ) ) ? $style[ $thiet_bi ] : array();
}

/**
 * Đọc toàn bộ cấu hình chữ đã chuẩn hoá của MỘT thiết bị.
 *
 * @return array<string, array<string,string>> Vai trò => khai báo CSS. Rỗng nếu không đặt gì.
 */
function nntm_block_style_doc_typo( array $attributes, string $thiet_bi = 'desktop' ): array {
	$nhanh = nntm_block_style_nhanh_thiet_bi( $attributes, $thiet_bi );

	$typo = ( isset( $nhanh['typo'] ) && is_array( $nhanh['typo'] ) ) ? $nhanh['typo'] : array();

	$ra = array();

	foreach ( array_keys( nntm_block_style_typo_roles() ) as $vai_tro ) {
		$khai_bao = nntm_block_style_sanitize_typo_role( $typo[ $vai_tro ] ?? null );

		if ( $khai_bao ) {
			$ra[ $vai_tro ] = $khai_bao;
		}
	}

	if ( $ra ) {
		return $ra;
	}

	/*
	 * Tương thích ngược: nội dung lưu trước bước 2 dùng ba thuộc tính rời —
	 * nntmFontFamily (một font cho cả khối), nntmTextColor, nntmHeadingColor.
	 * Dựng lại thành cấu hình theo vai trò để hiển thị không đổi.
	 *
	 * CHỈ cho nhánh máy tính. Ba thuộc tính cũ không có khái niệm thiết bị; nếu
	 * dựng lại cho cả máy tính bảng và điện thoại thì mỗi mốc lại sinh thêm một
	 * bản sao y hệt trong media query — thừa, và tệ hơn là khoá cứng giá trị cũ
	 * ở hai mốc đó, khiến sau này chỉnh riêng cho điện thoại không ăn.
	 */
	if ( 'desktop' !== $thiet_bi ) {
		return array();
	}

	$font_cu    = nntm_block_style_font_value( $attributes['nntmFontFamily'] ?? '' );
	$chu_cu     = nntm_block_style_color_value( $attributes['nntmTextColor'] ?? '' );
	$tieu_de_cu = nntm_block_style_color_value( $attributes['nntmHeadingColor'] ?? '' );

	if ( '' === $font_cu && '' === $chu_cu && '' === $tieu_de_cu ) {
		return array();
	}

	foreach ( array_keys( nntm_block_style_typo_roles() ) as $vai_tro ) {
		$khai_bao = array();

		if ( '' !== $font_cu ) {
			$khai_bao['font-family'] = $font_cu;
		}

		if ( 'heading' === $vai_tro || 'sub' === $vai_tro ) {
			if ( '' !== $tieu_de_cu ) {
				$khai_bao['color'] = $tieu_de_cu;
			} elseif ( '' !== $chu_cu ) {
				$khai_bao['color'] = $chu_cu;
			}
		} elseif ( '' !== $chu_cu ) {
			$khai_bao['color'] = $chu_cu;
		}

		if ( $khai_bao ) {
			$ra[ $vai_tro ] = $khai_bao;
		}
	}

	return $ra;
}

/**
 * Dựng toàn bộ CSS cho một khối, đã gắn class định danh.
 *
 * @param string $lop_dinh_danh Class định danh của khối, không kèm dấu chấm.
 * @param string $block_name    Tên block, để tra bản đồ lớp con che nền.
 * @param array  $attributes    Attributes của block.
 *
 * @return string CSS hoàn chỉnh, hoặc '' nếu khối không đặt gì.
 */
function nntm_block_style_build_css( string $lop_dinh_danh, string $block_name, array $attributes ): string {
	// Lặp ba lần class -> độ ưu tiên (0,3,0), thắng mọi quy tắc sẵn có trong CSS block.
	$goc = sprintf( '.%1$s.%1$s.%1$s', $lop_dinh_danh );
	$css = '';

	/* Nền không tách theo thiết bị — chỉ có một bản. */
	$nen = nntm_block_style_doc_nen( $attributes );

	if ( null !== $nen ) {
		$css .= $goc . '{' . implode( ';', nntm_block_style_css_nen( $nen ) ) . '}';

		$surface = nntm_block_style_surface_map();

		if ( isset( $surface[ $block_name ] ) ) {
			$con = array();

			foreach ( $surface[ $block_name ] as $class_con ) {
				$con[] = $goc . ' .' . $class_con;
			}

			if ( $con ) {
				$css .= implode( ',', $con ) . '{background-color:transparent;background-image:none}';
			}
		}
	}

	/*
	 * Chữ và khoảng cách: một lượt cho mỗi thiết bị, theo đúng thứ tự máy tính ->
	 * máy tính bảng -> điện thoại (xem nntm_block_style_thiet_bi).
	 */
	foreach ( nntm_block_style_thiet_bi() as $ma_thiet_bi => $thiet_bi ) {
		$phan = '';

		$space = nntm_block_style_doc_space( nntm_block_style_nhanh_thiet_bi( $attributes, $ma_thiet_bi )['space'] ?? null );

		if ( $space ) {
			$phan .= $goc . '{' . implode( ';', $space ) . '}';
		}

		$typo = nntm_block_style_doc_typo( $attributes, $ma_thiet_bi );

		/*
		 * Xuất theo đúng thứ tự khai báo trong nntm_block_style_typo_roles(): các
		 * quy tắc bằng độ ưu tiên nên vai trò đứng sau thắng ở những class trúng
		 * nhiều vai trò.
		 */
		foreach ( nntm_block_style_typo_roles() as $ma_vai_tro => $role ) {
			if ( empty( $typo[ $ma_vai_tro ] ) ) {
				continue;
			}

			$chon = nntm_block_style_typo_selector( $goc, $role );

			if ( '' === $chon ) {
				continue;
			}

			$khai_bao = array();
			foreach ( $typo[ $ma_vai_tro ] as $ten => $gia_tri ) {
				$khai_bao[] = $ten . ':' . $gia_tri;
			}

			$phan .= $chon . '{' . implode( ';', $khai_bao ) . '}';
		}

		if ( '' === $phan ) {
			continue;
		}

		$css .= ( '' === $thiet_bi['dieu_kien'] )
			? $phan
			: '@media ' . $thiet_bi['dieu_kien'] . '{' . $phan . '}';
	}

	return $css;
}

/**
 * Class định danh cho một khối.
 *
 * Băm theo nội dung cấu hình + số thứ tự khối trong trang: hai khối cùng cấu
 * hình vẫn nhận hai class khác nhau, nhưng mỗi khối luôn ổn định trong một lần
 * dựng trang.
 */
function nntm_block_style_dinh_danh( array $attributes ): string {
	static $dem = 0;

	++$dem;

	return 'nntm-s-' . substr( md5( wp_json_encode( $attributes ) . '|' . $dem ), 0, 8 );
}

/**
 * Từ attributes của block sang danh sách class — nay chỉ còn chiều rộng.
 *
 * Nền đã chuyển sang CSS sinh riêng ở bước 1; chữ và màu chữ chuyển sang engine
 * typography ở bước 2.
 *
 * Ba thuộc tính cũ (nntmTextColor, nntmHeadingColor, nntmFontFamily) trước đây
 * cũng sinh class ở đây. Đã gỡ: nntm_block_style_doc_typo() đã tự dựng lại
 * chúng thành cấu hình theo vai trò, nên để cả hai đường thì mỗi khối lưu từ
 * trước sẽ bị áp style HAI LẦN bằng hai bộ selector khác nhau. Bản thân
 * attribute vẫn được đăng ký để đọc nội dung cũ, chỉ bỏ đường xử lý trùng.
 *
 * @return array{classes: string[], vars: array<string,string>}
 */
function nntm_block_style_compute( array $attributes ): array {
	$classes = array();

	$width = isset( $attributes['nntmWidth'] ) ? sanitize_key( (string) $attributes['nntmWidth'] ) : '';
	if ( 'full' === $width ) {
		$classes[] = 'nntm-block--full';
	} elseif ( 'contained' === $width ) {
		$classes[] = 'nntm-block--contained';
	}

	if ( $classes ) {
		array_unshift( $classes, 'nntm-block-style' );
	}

	return array(
		'classes' => $classes,
		'vars'    => array(),
	);
}

/**
 * Gắn class + biến CSS + CSS nền vào khối khi render.
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

	/*
	 * Dựng CSS trước rồi mới quyết định có gắn class hay không.
	 *
	 * Trước đây chỗ này tự liệt kê "có nền HOẶC có chữ" để đoán xem khối có cần
	 * class định danh không — thêm nhóm tuỳ chọn nào là phải nhớ sửa cả ở đây,
	 * và quên đúng một lần là cả nhóm đó im lặng không hoạt động. Nay chỉ hỏi
	 * đúng một câu: build_css có sinh ra gì không.
	 */
	$lop_dinh_danh = nntm_block_style_dinh_danh( $attributes );
	$css_rieng     = nntm_block_style_build_css( $lop_dinh_danh, $block_name, $attributes );

	if ( '' === $css_rieng ) {
		$lop_dinh_danh = '';
	}

	if ( empty( $tinh['classes'] ) && '' === $css_rieng ) {
		return $block_content;
	}

	if ( ! class_exists( 'WP_HTML_Tag_Processor' ) ) {
		return $block_content;
	}

	$processor = new WP_HTML_Tag_Processor( $block_content );

	if ( ! $processor->next_tag() ) {
		return $block_content;
	}

	foreach ( $tinh['classes'] as $class ) {
		$processor->add_class( $class );
	}

	if ( '' !== $lop_dinh_danh ) {
		$processor->add_class( $lop_dinh_danh );
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

	$html = $processor->get_updated_html();

	if ( '' !== $css_rieng ) {
		$html = '<style>' . $css_rieng . '</style>' . $html;
	}

	return $html;
}
/*
 * Ưu tiên 99 — phải chạy SAU mọi bộ lọc render_block khác của theme.
 *
 * Hàm này chèn một thẻ <style> vào TRƯỚC khối. Các bộ lọc khác
 * (block-anchor ưu tiên 10, quay-lai 11, reveal 20) đều dùng
 * WP_HTML_Tag_Processor::next_tag() để gắn class/thuộc tính vào "thẻ đầu tiên".
 * Nếu chạy sớm, thẻ đầu tiên chúng gặp sẽ là <style> chứ không phải <section>
 * của block — hiệu ứng hiện dần và điểm neo đều rơi vào thẻ <style> vô hình.
 * Chạy cuối cùng thì chúng đã làm xong việc trên đúng thẻ của block.
 */
add_filter( 'render_block', 'nntm_block_style_render_block', 99, 2 );

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
		array( 'wp-hooks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n', 'wp-compose', 'wp-data' ),
		nntm_asset_version( $absolute ),
		true
	);

	$css_panel = '/assets/css/editor/block-style-panel.css';

	wp_enqueue_style(
		'nntm-block-style-panel',
		NNTM_THEME_URI . $css_panel,
		array( 'wp-components' ),
		nntm_asset_version( NNTM_THEME_DIR . $css_panel )
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

	/*
	 * Bản đồ vai trò chữ được đẩy từ PHP sang chứ không chép lại trong JS.
	 * Trình soạn thảo và trang thật vì thế luôn dựng ra cùng một chuỗi selector;
	 * sửa bản đồ một chỗ là cả hai bên đổi theo.
	 */
	$vai_tro = array();
	foreach ( nntm_block_style_typo_roles() as $ma => $role ) {
		$vai_tro[] = array(
			'ma'   => $ma,
			'ten'  => $role['ten'],
			'tags' => $role['tags'],
			'manh' => $role['manh'],
		);
	}

	$thiet_bi = array();
	foreach ( nntm_block_style_thiet_bi() as $ma => $tb ) {
		$thiet_bi[] = array(
			'ma'       => $ma,
			'ten'      => $tb['ten'],
			'dieuKien' => $tb['dieu_kien'],
		);
	}

	wp_localize_script(
		'nntm-block-style-editor',
		'nntmBlockStyle',
		array(
			'palette'   => $mau,
			'fonts'     => $font,
			'excluded'  => array_values( nntm_block_style_excluded_blocks() ),
			'surface'   => nntm_block_style_surface_map(),
			'typoRoles' => $vai_tro,
			'typoExcl'  => nntm_block_style_typo_loai_tru(),
			'thietBi'   => $thiet_bi,
		)
	);
}
add_action( 'enqueue_block_editor_assets', 'nntm_block_style_editor_assets' );
