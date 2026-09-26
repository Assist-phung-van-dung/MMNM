<?php
/**
 * Hiệu ứng con trỏ chuột cho toàn site.
 *
 * MẶC ĐỊNH KHÔNG DÙNG. Ban quản trị bật ở Giao diện -> Con trỏ chuột: chọn 1
 * trong 20 kiểu (hoặc "Không dùng"), đổi màu, độ dài vệt, mật độ hạt — áp dụng
 * chung toàn site — và có thể đặt RIÊNG cho từng trang/bài ngay trên cùng một
 * màn hình (bảng "Theo từng trang", ghi vào post meta).
 *
 * Đây là phần HIỂN THỊ nên nằm ở theme, không phải plugin (docs/04-kien-truc.md
 * mục 1: dữ liệu & nghiệp vụ ở plugin, hình ảnh ở theme — ở đây không có
 * "nghiệp vụ" gì ngoài vẽ, và post meta chỉ là một chỗ lưu tuỳ chọn hiển thị).
 *
 * Kiến trúc:
 *   - Cài đặt chung lưu trong MỘT option mảng (NNTM_CON_TRO_OPTION).
 *   - Cài đặt riêng từng trang lưu POST META (ba khoá, xem hằng số dưới) — một
 *     nguồn sự thật duy nhất, meta box ở màn sửa bài chỉ là lối tắt ghi vào
 *     đúng ba khoá này, không có kho dữ liệu riêng nào khác.
 *   - Màn quản trị "Con trỏ chuột" gộp cả hai: khung xem thử + form cài đặt
 *     chung + bảng cài đặt riêng, MỘT nút Lưu duy nhất (không dùng Settings
 *     API vì cần ghi post meta cùng lúc — tự xử lý nonce + lưu ở admin_init).
 *   - Bộ máy vẽ thật ở assets/js/con-tro.js (window.NNTMConTro.khoiTao), dùng
 *     chung cho khung xem thử admin (vùng) và trang thật (toàn màn hình).
 */

defined( 'ABSPATH' ) || exit;

const NNTM_CON_TRO_OPTION = 'nntm_con_tro';
const NNTM_CON_TRO_TRANG  = 'nntm-con-tro';

const NNTM_CON_TRO_META_KIEU   = '_nntm_con_tro_kieu';
const NNTM_CON_TRO_META_MAU    = '_nntm_con_tro_mau';
const NNTM_CON_TRO_META_DO_DAI = '_nntm_con_tro_do_dai';

const NNTM_CON_TRO_DO_DAI_MIN     = 10;
const NNTM_CON_TRO_DO_DAI_MAX     = 60;
const NNTM_CON_TRO_DO_DAI_MAC_DINH = 30;

const NNTM_CON_TRO_NONCE_HANH_DONG = 'nntm_con_tro_luu';

/**
 * URL màn quản trị "Con trỏ chuột".
 *
 * Màn này là SUBMENU của CPT Từ khoá động (edit.php?post_type=nntm_tu_khoa_dong)
 * — không phải Giao diện — vì giờ có cả một phần cấu hình theo từ khoá động,
 * để hai thứ liên quan nằm gần nhau thay vì tách hai nơi khó nhớ.
 */
function nntm_con_tro_url_quan_ly(): string {
	return admin_url( 'edit.php?post_type=' . \NNTM\Core\Tu_Khoa_Dong::POST_TYPE . '&page=' . NNTM_CON_TRO_TRANG );
}

/*
 * ---------------------------------------------------------------------------
 * Danh sách 20 kiểu — NGUỒN THẬT nằm ở plugin (nntm-core/includes/
 * con-tro-dung-chung.php) vì từ khoá động (một post type của plugin) cũng gắn
 * được hiệu ứng con trỏ, nên plugin cần đọc được danh sách này mà không phụ
 * thuộc ngược vào theme. Theme chỉ giữ hai hàm THIN WRAPPER dưới đây để không
 * phải sửa lại toàn bộ chỗ gọi `nntm_con_tro_danh_sach_kieu()` /
 * `nntm_con_tro_mau_mac_dinh_theo_kieu()` rải rác trong tệp này.
 * ---------------------------------------------------------------------------
 */

/** 20 kiểu hiệu ứng: mã => tên hiển thị + mô tả 1 dòng. Xem nntm_con_tro_ds_kieu() (plugin). */
function nntm_con_tro_danh_sach_kieu(): array {
	return nntm_con_tro_ds_kieu();
}

/** Bảng màu mặc định — khớp MAU_MAC_DINH trong assets/js/con-tro.js. Xem nntm_con_tro_mau_mac_dinh() (plugin). */
function nntm_con_tro_mau_mac_dinh_theo_kieu( string $kieu ): string {
	return nntm_con_tro_mau_mac_dinh( $kieu );
}

/**
 * Các loại nội dung được phép có cài đặt con trỏ riêng.
 */
function nntm_con_tro_post_types(): array {
	$mac_dinh = array( 'page', 'post', 'nntm_article', 'nntm_publication', 'nntm_retreat', 'nntm_program', 'nntm_abode', 'nntm_talk', 'nntm_video' );

	$loc = apply_filters( 'nntm_con_tro_post_types', $mac_dinh );

	if ( ! is_array( $loc ) ) {
		return $mac_dinh;
	}

	return array_values( array_filter( array_map( 'sanitize_key', $loc ), 'post_type_exists' ) );
}

/*
 * ---------------------------------------------------------------------------
 * Lọc / sanitize
 * ---------------------------------------------------------------------------
 */

function nntm_con_tro_sanitize_kieu( $tho ): string {
	$khoa = sanitize_key( (string) $tho );
	return array_key_exists( $khoa, nntm_con_tro_danh_sach_kieu() ) ? $khoa : '';
}

/** Như trên, nhưng còn nhận 'tat' (chỉ dùng cho cài đặt RIÊNG từng trang). */
function nntm_con_tro_sanitize_kieu_trang( $tho ): string {
	$khoa = sanitize_key( (string) $tho );

	if ( 'tat' === $khoa ) {
		return 'tat';
	}

	return array_key_exists( $khoa, nntm_con_tro_danh_sach_kieu() ) ? $khoa : '';
}

function nntm_con_tro_sanitize_mau( $tho ): string {
	$tho = trim( (string) $tho );

	if ( '' === $tho ) {
		return '';
	}

	$sach = sanitize_hex_color( $tho );

	return $sach ? $sach : '';
}

function nntm_con_tro_sanitize_mat_do( $tho ): string {
	$khoa = sanitize_key( (string) $tho );
	return in_array( $khoa, array( 'it', 'vua', 'nhieu' ), true ) ? $khoa : 'vua';
}

/** Cỡ hình con trỏ: nhỏ ×0.75, vừa ×1 (mặc định), lớn ×1.35 — khớp HE_SO_CO trong con-tro.js. */
function nntm_con_tro_sanitize_co( $tho ): string {
	$khoa = sanitize_key( (string) $tho );
	return in_array( $khoa, array( 'nho', 'vua', 'lon' ), true ) ? $khoa : 'vua';
}

/** Kẹp độ dài vệt vào khoảng cho phép. Chuỗi rỗng -> mặc định. */
function nntm_con_tro_kep_do_dai( $tho ): int {
	if ( '' === trim( (string) $tho ) || ! is_numeric( $tho ) ) {
		return NNTM_CON_TRO_DO_DAI_MAC_DINH;
	}

	return (int) max( NNTM_CON_TRO_DO_DAI_MIN, min( NNTM_CON_TRO_DO_DAI_MAX, round( (float) $tho ) ) );
}

/** Như trên nhưng giữ nguyên chuỗi rỗng = "theo cài đặt chung" (dùng cho theo-trang). */
function nntm_con_tro_kep_do_dai_trang( $tho ): string {
	if ( '' === trim( (string) $tho ) ) {
		return '';
	}

	if ( ! is_numeric( $tho ) ) {
		return '';
	}

	return (string) nntm_con_tro_kep_do_dai( $tho );
}

/**
 * Cài đặt chung hiện tại — luôn trả đủ 5 khoá, đã lọc sạch.
 */
function nntm_con_tro_cai_dat_chung(): array {
	$luu = get_option( NNTM_CON_TRO_OPTION, array() );
	$luu = is_array( $luu ) ? $luu : array();

	return array(
		'kieu'        => nntm_con_tro_sanitize_kieu( $luu['kieu'] ?? '' ),
		'mau'         => nntm_con_tro_sanitize_mau( $luu['mau'] ?? '' ),
		'mau_phu'     => nntm_con_tro_sanitize_mau( $luu['mau_phu'] ?? '' ),
		'do_dai'      => nntm_con_tro_kep_do_dai( $luu['do_dai'] ?? NNTM_CON_TRO_DO_DAI_MAC_DINH ),
		'mat_do'      => nntm_con_tro_sanitize_mat_do( $luu['mat_do'] ?? 'vua' ),
		'co'          => nntm_con_tro_sanitize_co( $luu['co'] ?? 'vua' ),
		/*
		 * Bật thì: bấm vào một kết quả trên trang tìm kiếm sẽ nhớ hiệu ứng
		 * đang hiện (sessionStorage phía trình duyệt) và mang sang trang kế
		 * tiếp NẾU trang đó không có cài đặt riêng. Mặc định TẮT — đây là một
		 * hiệu ứng phụ khó đoán nếu bật sẵn mà admin không để ý.
		 */
		'giu_khi_bam' => ! empty( $luu['giu_khi_bam'] ),
	);
}

/*
 * ---------------------------------------------------------------------------
 * Post meta (cài đặt riêng từng trang) — đăng ký để REST/trình soạn thảo
 * đọc/ghi được, dù màn quản trị chính không dùng REST để lưu.
 * ---------------------------------------------------------------------------
 */

function nntm_con_tro_dang_ky_meta(): void {
	foreach ( nntm_con_tro_post_types() as $loai ) {
		register_post_meta(
			$loai,
			NNTM_CON_TRO_META_KIEU,
			array(
				'type'              => 'string',
				'single'            => true,
				'default'           => '',
				'show_in_rest'      => true,
				'sanitize_callback' => 'nntm_con_tro_sanitize_kieu_trang',
				'auth_callback'     => function () {
					return current_user_can( 'edit_posts' );
				},
				'description'       => __( 'Kiểu con trỏ riêng cho bài này: rỗng = theo cài đặt chung, "tat" = không dùng.', 'nntm' ),
			)
		);

		register_post_meta(
			$loai,
			NNTM_CON_TRO_META_MAU,
			array(
				'type'              => 'string',
				'single'            => true,
				'default'           => '',
				'show_in_rest'      => true,
				'sanitize_callback' => 'nntm_con_tro_sanitize_mau',
				'auth_callback'     => function () {
					return current_user_can( 'edit_posts' );
				},
				'description'       => __( 'Màu con trỏ riêng cho bài này: rỗng = theo cài đặt chung.', 'nntm' ),
			)
		);

		register_post_meta(
			$loai,
			NNTM_CON_TRO_META_DO_DAI,
			array(
				'type'              => 'string',
				'single'            => true,
				'default'           => '',
				'show_in_rest'      => true,
				'sanitize_callback' => 'nntm_con_tro_kep_do_dai_trang',
				'auth_callback'     => function () {
					return current_user_can( 'edit_posts' );
				},
				'description'       => __( 'Độ dài vệt riêng cho bài này: rỗng = theo cài đặt chung.', 'nntm' ),
			)
		);
	}
}
add_action( 'init', 'nntm_con_tro_dang_ky_meta' );

/**
 * Danh sách các bài/trang đang có cài đặt riêng (có post meta kiểu).
 *
 * @return array<int, array{tieu_de:string, loai:string, sua_url:string, kieu:string, mau:string, do_dai:string}>
 */
function nntm_con_tro_lay_danh_sach_trang_rieng( int $gioi_han = 50 ): array {
	$bai_viet = get_posts(
		array(
			'post_type'              => nntm_con_tro_post_types(),
			'post_status'             => 'any',
			'posts_per_page'          => max( 1, $gioi_han ),
			'orderby'                 => 'modified',
			'order'                   => 'DESC',
			'no_found_rows'           => true,
			'update_post_term_cache'  => false,
			'meta_query'              => array(
				array(
					'key'     => NNTM_CON_TRO_META_KIEU,
					'compare' => 'EXISTS',
				),
			),
		)
	);

	$ra = array();

	foreach ( $bai_viet as $bai ) {
		$kieu = (string) get_post_meta( $bai->ID, NNTM_CON_TRO_META_KIEU, true );

		if ( '' === $kieu ) {
			continue; // Meta rỗng (đã bị xoá giá trị) — coi như không có cài đặt riêng.
		}

		$ra[ $bai->ID ] = array(
			'tieu_de' => get_the_title( $bai ) ?: '(không có tiêu đề)',
			'loai'    => $bai->post_type,
			'sua_url' => (string) get_edit_post_link( $bai->ID, 'raw' ),
			'kieu'    => $kieu,
			'mau'     => (string) get_post_meta( $bai->ID, NNTM_CON_TRO_META_MAU, true ),
			'do_dai'  => (string) get_post_meta( $bai->ID, NNTM_CON_TRO_META_DO_DAI, true ),
		);
	}

	return $ra;
}

/*
 * ---------------------------------------------------------------------------
 * Cấu hình quyết định cho TRANG ĐANG XEM (dùng ở frontend).
 * ---------------------------------------------------------------------------
 */

function nntm_con_tro_cau_hinh_trang(): array {
	$chung = nntm_con_tro_cai_dat_chung();

	$kieu    = $chung['kieu'];
	$mau     = $chung['mau'];
	$mau_phu = $chung['mau_phu'];
	$do_dai  = $chung['do_dai'];
	/*
	 * Nguồn cấu hình hiệu lực — dùng để: (1) biết có nên cho sessionStorage
	 * "giữ hiệu ứng khi bấm kết quả tìm kiếm" ghi đè hay không (chỉ khi 'chung',
	 * cài đặt riêng của TRANG luôn thắng); (2) hiển thị debug nếu cần sau này.
	 */
	$nguon = 'chung';

	if ( is_singular( nntm_con_tro_post_types() ) ) {
		$bai = get_queried_object();

		if ( $bai instanceof WP_Post ) {
			$kieu_rieng = (string) get_post_meta( $bai->ID, NNTM_CON_TRO_META_KIEU, true );

			if ( 'tat' === $kieu_rieng ) {
				$kieu  = '';
				$nguon = 'trang';
			} elseif ( '' !== $kieu_rieng && array_key_exists( $kieu_rieng, nntm_con_tro_danh_sach_kieu() ) ) {
				$kieu  = $kieu_rieng;
				$nguon = 'trang';

				$mau_rieng = (string) get_post_meta( $bai->ID, NNTM_CON_TRO_META_MAU, true );
				if ( '' !== $mau_rieng ) {
					$mau = $mau_rieng;
					// Không có màu phụ riêng: để JS tự pha sáng từ MÀU RIÊNG, không dùng màu phụ của cài đặt chung.
					$mau_phu = '';
				}

				$do_dai_rieng = (string) get_post_meta( $bai->ID, NNTM_CON_TRO_META_DO_DAI, true );
				if ( '' !== $do_dai_rieng ) {
					$do_dai = nntm_con_tro_kep_do_dai( $do_dai_rieng );
				}
			}
		}
	} elseif ( is_search() && function_exists( 'nntm_tkd_khop_tim_kiem' ) ) {
		/*
		 * Trang kết quả tìm kiếm (kể cả trang tìm bằng ảnh: nntm-search dựng
		 * URL /?s=<từ khoá đọc được>&nntm_anh=<mã phiên>, nên get_search_query()
		 * đã có sẵn từ khoá cần so khớp, không cần biết gì thêm về ảnh).
		 */
		$khop = nntm_tkd_khop_tim_kiem( get_search_query() );

		if ( is_array( $khop ) && '' !== $khop['kieu'] ) {
			$kieu  = $khop['kieu'];
			$nguon = 'tu-khoa';

			if ( '' !== $khop['mau'] ) {
				$mau     = $khop['mau'];
				$mau_phu = '';
			}
		}
	}

	$bat = ( '' !== $kieu );

	/**
	 * Bật/tắt hiệu ứng theo trang. Mặc định tắt ở trang đọc sách (xem
	 * nntm_con_tro_tat_trang_doc dưới), trừ khi chính trang đó tự chọn một
	 * kiểu cụ thể trong cài đặt riêng.
	 *
	 * @param bool $bat Có bật hiệu ứng ở trang này không.
	 */
	$bat = (bool) apply_filters( 'nntm_con_tro_bat', $bat );

	/*
	 * Có cho đổi con trỏ khi đang GÕ vào ô tìm ở trang này không: tắt khi trang
	 * tự chọn "Không dùng ở trang này", hoặc khi filter tắt hẳn trang (vd trang
	 * đọc sách) — hỏi filter với giá trị true để biết trang có bị cấm không.
	 */
	$cho_go = ! ( 'trang' === $nguon && '' === $kieu ) && (bool) apply_filters( 'nntm_con_tro_bat', true );

	return array(
		'bat'         => $bat,
		'cho_go'      => $cho_go,
		'kieu'        => $kieu,
		'mau'         => $mau,
		'mau_phu'     => $mau_phu,
		'do_dai'      => $do_dai,
		'mat_do'      => $chung['mat_do'],
		'co'          => $chung['co'],
		'nguon'       => $nguon,
		'giu_khi_bam' => $chung['giu_khi_bam'],
	);
}

/**
 * Vệt sáng làm rối mắt khi đang đọc — tắt mặc định ở trang đọc sách, trừ khi
 * chính bài đó đã tự chọn một kiểu cụ thể trong cài đặt riêng.
 */
function nntm_con_tro_tat_trang_doc( bool $bat ): bool {
	if ( ! $bat ) {
		return $bat;
	}

	if ( ! function_exists( 'nntm_theme_o_trang_doc' ) || ! nntm_theme_o_trang_doc() ) {
		return $bat;
	}

	$bai = get_queried_object();

	if ( $bai instanceof WP_Post ) {
		$kieu_rieng = (string) get_post_meta( $bai->ID, NNTM_CON_TRO_META_KIEU, true );

		if ( '' !== $kieu_rieng && 'tat' !== $kieu_rieng && array_key_exists( $kieu_rieng, nntm_con_tro_danh_sach_kieu() ) ) {
			return $bat;
		}
	}

	return false;
}
add_filter( 'nntm_con_tro_bat', 'nntm_con_tro_tat_trang_doc', 20 );

/*
 * ---------------------------------------------------------------------------
 * Nạp asset ở trang thật.
 * ---------------------------------------------------------------------------
 */

/**
 * Danh sách từ khoá động ĐANG có gắn hiệu ứng con trỏ — dữ liệu cho JS khớp
 * ngay khi gõ vào ô tìm kiếm (assets/js/con-tro-tim-kiem.js). Trả mảng RỖNG
 * nếu chưa có từ khoá nào gắn hiệu ứng — script chỉ được nạp khi mảng này
 * không rỗng (xem nntm_con_tro_enqueue_assets()).
 *
 * @return array<int, array{ten:string, bienThe:string[], kieu:string, mau:string}>
 */
function nntm_con_tro_tu_khoa_ban_do(): array {
	if ( ! class_exists( '\NNTM\Core\Tu_Khoa_Dong' ) ) {
		return array();
	}

	$ids = get_posts(
		array(
			'post_type'      => \NNTM\Core\Tu_Khoa_Dong::POST_TYPE,
			'post_status'    => 'publish',
			'posts_per_page' => 300,
			'fields'         => 'ids',
			'no_found_rows'  => true,
			'meta_query'     => array(
				array(
					'key'     => '_nntm_tkd_con_tro_kieu',
					'value'   => '',
					'compare' => '!=',
				),
			),
		)
	);

	$ra = array();

	foreach ( $ids as $id ) {
		$kieu = nntm_con_tro_sanitize_kieu( get_post_meta( $id, '_nntm_tkd_con_tro_kieu', true ) );

		if ( '' === $kieu ) {
			continue;
		}

		$bien_the = get_post_meta( $id, '_nntm_tkd_bien_the', true );

		$ra[] = array(
			'ten'     => html_entity_decode( get_the_title( $id ), ENT_QUOTES, 'UTF-8' ),
			'bienThe' => is_array( $bien_the ) ? array_values( array_map( 'strval', $bien_the ) ) : array(),
			'kieu'    => $kieu,
			'mau'     => nntm_con_tro_sanitize_mau( get_post_meta( $id, '_nntm_tkd_con_tro_mau', true ) ),
		);
	}

	return $ra;
}

function nntm_con_tro_enqueue_assets(): void {
	if ( is_admin() ) {
		return;
	}

	$cfg      = nntm_con_tro_cau_hinh_trang();
	$ban_do   = nntm_con_tro_tu_khoa_ban_do();
	$co_giu   = ! empty( $cfg['giu_khi_bam'] );

	/*
	 * Nạp engine + CSS khi: (a) trang này có hiệu ứng, HOẶC (b) "giữ khi bấm"
	 * đang bật — vì khi đó một trang tưởng như KHÔNG có hiệu ứng vẫn có thể
	 * cần áp dụng cấu hình nhớ từ sessionStorage của trang trước.
	 */
	$can_ban_do = ! empty( $ban_do ) && ! empty( $cfg['cho_go'] );

	if ( ! $cfg['bat'] && ! $co_giu && ! $can_ban_do ) {
		return;
	}

	// Trang không có hiệu ứng nhưng cần bản đồ gõ tìm: con-tro-tim-kiem.js tự nạp engine khi khớp.
	if ( ! $cfg['bat'] && ! $co_giu ) {
		nntm_con_tro_nap_ban_do_go( $cfg, $ban_do, $co_giu );
		return;
	}

	$css_path = NNTM_THEME_DIR . '/assets/css/con-tro.css';
	wp_enqueue_style( 'nntm-con-tro', NNTM_THEME_URI . '/assets/css/con-tro.css', array( 'nntm-tokens' ), nntm_asset_version( $css_path ) );

	$js_path = NNTM_THEME_DIR . '/assets/js/con-tro.js';
	wp_enqueue_script( 'nntm-con-tro', NNTM_THEME_URI . '/assets/js/con-tro.js', array(), nntm_asset_version( $js_path ), true );

	$boc = array();

	if ( $cfg['bat'] ) {
		$boc[] = sprintf(
			'window.__nntmConTroChinh = window.NNTMConTro && window.NNTMConTro.khoiTao(%s);',
			wp_json_encode(
				array(
					'kieu'   => $cfg['kieu'],
					'mau'    => $cfg['mau'],
					'mauPhu' => $cfg['mau_phu'],
					'doDai'  => $cfg['do_dai'],
					'matDo'  => $cfg['mat_do'],
					'co'     => $cfg['co'],
				)
			)
		);
	}

	/*
	 * "Giữ hiệu ứng khi bấm vào kết quả tìm kiếm": chỉ được ghi đè khi trang
	 * này KHÔNG có cài đặt riêng của chính nó ('nguon' === 'chung') — cài đặt
	 * riêng của trang, hoặc hiệu ứng khớp từ khoá NGAY TRÊN trang này, luôn
	 * thắng sessionStorage của trang TRƯỚC đó.
	 */
	if ( $co_giu && 'chung' === $cfg['nguon'] ) {
		$mac_dinh_giu = wp_json_encode(
			array(
				'doDai' => $cfg['do_dai'],
				'matDo' => $cfg['mat_do'],
				'co'    => $cfg['co'],
			)
		);

		$boc[] = '(function(){try{var d=JSON.parse(sessionStorage.getItem("nntm_con_tro_giu")||"null");'
			. 'if(d&&d.kieu){var nen=' . $mac_dinh_giu . ';var cf={kieu:d.kieu,mau:d.mau||"",mauPhu:"",doDai:nen.doDai,matDo:nen.matDo,co:nen.co};'
			. 'if(window.__nntmConTroChinh){window.__nntmConTroChinh.doiCauHinh(cf);}else{window.__nntmConTroChinh=window.NNTMConTro&&window.NNTMConTro.khoiTao(cf);}}}catch(e){}})();';
	}

	if ( ! empty( $boc ) ) {
		wp_add_inline_script( 'nntm-con-tro', implode( "\n", $boc ) );
	}

	if ( $can_ban_do ) {
		nntm_con_tro_nap_ban_do_go( $cfg, $ban_do, $co_giu );
	}
}
add_action( 'wp_enqueue_scripts', 'nntm_con_tro_enqueue_assets', 45 );

/**
 * Khớp con trỏ ngay khi gõ vào ô tìm — chỉ gọi khi có ít nhất 1 từ khoá gắn hiệu ứng.
 *
 * @param array $cfg    Kết quả nntm_con_tro_cau_hinh_trang().
 * @param array $ban_do Kết quả nntm_con_tro_tu_khoa_ban_do().
 * @param bool  $co_giu "Giữ hiệu ứng khi bấm kết quả" đang bật.
 */
function nntm_con_tro_nap_ban_do_go( array $cfg, array $ban_do, bool $co_giu ): void {
	$tim_js_path = NNTM_THEME_DIR . '/assets/js/con-tro-tim-kiem.js';
	wp_enqueue_script(
		'nntm-con-tro-tim-kiem',
		NNTM_THEME_URI . '/assets/js/con-tro-tim-kiem.js',
		array(),
		nntm_asset_version( $tim_js_path ),
		true
	);

	wp_localize_script(
		'nntm-con-tro-tim-kiem',
		'nntmConTroTimKiem',
		array(
			'banDo'        => $ban_do,
			'chung'        => array(
				'doDai' => $cfg['do_dai'],
				'matDo' => $cfg['mat_do'],
				'co'    => $cfg['co'],
			),
			'giuKhiBam'    => $co_giu,
			'hienTai'      => ( 'tu-khoa' === $cfg['nguon'] ) ? array(
				'kieu' => $cfg['kieu'],
				'mau'  => $cfg['mau'],
			) : null,
			// Kèm ver như asset nạp thường, để sửa file xong trình duyệt không dùng bản cũ trong cache.
			'conTroJsUrl'  => add_query_arg( 'ver', nntm_asset_version( NNTM_THEME_DIR . '/assets/js/con-tro.js' ), NNTM_THEME_URI . '/assets/js/con-tro.js' ),
			'conTroCssUrl' => add_query_arg( 'ver', nntm_asset_version( NNTM_THEME_DIR . '/assets/css/con-tro.css' ), NNTM_THEME_URI . '/assets/css/con-tro.css' ),
		)
	);
}

/*
 * ---------------------------------------------------------------------------
 * Meta box lối tắt ở màn sửa từng bài (không phải nguồn dữ liệu riêng — chỉ
 * ghi vào đúng 3 khoá post meta ở trên, y hệt bảng "Theo từng trang").
 * ---------------------------------------------------------------------------
 */

function nntm_con_tro_them_meta_box(): void {
	foreach ( nntm_con_tro_post_types() as $loai ) {
		add_meta_box(
			'nntm_con_tro_trang',
			__( 'Con trỏ chuột', 'nntm' ),
			'nntm_con_tro_ve_meta_box',
			$loai,
			'side',
			'default'
		);
	}
}
add_action( 'add_meta_boxes', 'nntm_con_tro_them_meta_box' );

function nntm_con_tro_ve_meta_box( $post ): void {
	wp_nonce_field( 'nntm_con_tro_meta_box', 'nntm_con_tro_meta_box_nonce' );

	$kieu_rieng = (string) get_post_meta( $post->ID, NNTM_CON_TRO_META_KIEU, true );
	$mau_rieng  = (string) get_post_meta( $post->ID, NNTM_CON_TRO_META_MAU, true );
	$do_dai_rieng = (string) get_post_meta( $post->ID, NNTM_CON_TRO_META_DO_DAI, true );

	$chung = nntm_con_tro_cai_dat_chung();
	$ten_chung = ( '' === $chung['kieu'] )
		? __( 'Không dùng', 'nntm' )
		: ( nntm_con_tro_danh_sach_kieu()[ $chung['kieu'] ]['ten'] ?? $chung['kieu'] );
	?>
	<p>
		<label for="nntm_con_tro_kieu"><strong><?php esc_html_e( 'Hiệu ứng riêng cho bài này', 'nntm' ); ?></strong></label>
		<select name="nntm_con_tro_kieu" id="nntm_con_tro_kieu" style="width:100%;">
			<option value="" <?php selected( '', $kieu_rieng ); ?>><?php esc_html_e( 'Theo cài đặt chung', 'nntm' ); ?></option>
			<option value="tat" <?php selected( 'tat', $kieu_rieng ); ?>><?php esc_html_e( 'Không dùng ở trang này', 'nntm' ); ?></option>
			<?php foreach ( nntm_con_tro_danh_sach_kieu() as $khoa => $du_lieu ) : ?>
				<option value="<?php echo esc_attr( $khoa ); ?>" <?php selected( $khoa, $kieu_rieng ); ?>><?php echo esc_html( $du_lieu['ten'] ); ?></option>
			<?php endforeach; ?>
		</select>
	</p>
	<p>
		<label for="nntm_con_tro_mau"><?php esc_html_e( 'Màu riêng (để trống = theo chung)', 'nntm' ); ?></label><br />
		<input type="text" id="nntm_con_tro_mau" name="nntm_con_tro_mau" value="<?php echo esc_attr( $mau_rieng ); ?>" placeholder="#D4AF37" class="small-text" maxlength="7" />
	</p>
	<p>
		<label for="nntm_con_tro_do_dai"><?php esc_html_e( 'Độ dài vệt riêng (10–60, để trống = theo chung)', 'nntm' ); ?></label><br />
		<input type="number" id="nntm_con_tro_do_dai" name="nntm_con_tro_do_dai" value="<?php echo esc_attr( $do_dai_rieng ); ?>" min="<?php echo esc_attr( (string) NNTM_CON_TRO_DO_DAI_MIN ); ?>" max="<?php echo esc_attr( (string) NNTM_CON_TRO_DO_DAI_MAX ); ?>" class="small-text" />
	</p>
	<p class="description">
		<?php
		printf(
			/* translators: %s: tên hiệu ứng đang dùng chung. */
			esc_html__( 'Cài đặt chung hiện là: %s.', 'nntm' ),
			esc_html( $ten_chung )
		);
		?>
		<br />
		<a href="<?php echo esc_url( nntm_con_tro_url_quan_ly() ); ?>"><?php esc_html_e( 'Quản lý tất cả ở Từ khoá động → Con trỏ chuột', 'nntm' ); ?></a>
	</p>
	<?php
}

function nntm_con_tro_luu_meta_box( int $post_id ): void {
	if ( ! isset( $_POST['nntm_con_tro_meta_box_nonce'] )
		|| ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nntm_con_tro_meta_box_nonce'] ) ), 'nntm_con_tro_meta_box' )
	) {
		return;
	}

	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	if ( isset( $_POST['nntm_con_tro_kieu'] ) ) {
		$kieu = nntm_con_tro_sanitize_kieu_trang( wp_unslash( $_POST['nntm_con_tro_kieu'] ) );
		if ( '' === $kieu ) {
			delete_post_meta( $post_id, NNTM_CON_TRO_META_KIEU );
		} else {
			update_post_meta( $post_id, NNTM_CON_TRO_META_KIEU, $kieu );
		}
	}

	if ( isset( $_POST['nntm_con_tro_mau'] ) ) {
		$mau = nntm_con_tro_sanitize_mau( wp_unslash( $_POST['nntm_con_tro_mau'] ) );
		if ( '' === $mau ) {
			delete_post_meta( $post_id, NNTM_CON_TRO_META_MAU );
		} else {
			update_post_meta( $post_id, NNTM_CON_TRO_META_MAU, $mau );
		}
	}

	if ( isset( $_POST['nntm_con_tro_do_dai'] ) ) {
		$do_dai = nntm_con_tro_kep_do_dai_trang( wp_unslash( $_POST['nntm_con_tro_do_dai'] ) );
		if ( '' === $do_dai ) {
			delete_post_meta( $post_id, NNTM_CON_TRO_META_DO_DAI );
		} else {
			update_post_meta( $post_id, NNTM_CON_TRO_META_DO_DAI, $do_dai );
		}
	}
}
add_action( 'save_post', 'nntm_con_tro_luu_meta_box' );

/*
 * ---------------------------------------------------------------------------
 * Màn quản trị "Giao diện -> Con trỏ chuột".
 * ---------------------------------------------------------------------------
 */

/**
 * Đăng ký màn "Con trỏ chuột" làm SUBMENU của CPT Từ khoá động — không phải
 * Giao diện. Quyền giữ nguyên edit_theme_options (không phải edit_posts của
 * từ khoá): đây là cấu hình hiển thị toàn site, không phải quản lý nội dung.
 *
 * Chạy sau khi Tu_Khoa_Dong::register() đã đăng ký CPT (add_action init mặc
 * định ưu tiên 10, admin_menu chạy độc lập nên không cần lo thứ tự ở đây —
 * add_submenu_page chỉ cần post type đã tồn tại vào lúc admin_menu chạy, và
 * CPT được đăng ký ở init ưu tiên 10, luôn trước admin_menu).
 */
function nntm_con_tro_admin_menu(): void {
	add_submenu_page(
		'edit.php?post_type=' . \NNTM\Core\Tu_Khoa_Dong::POST_TYPE,
		__( 'Con trỏ chuột', 'nntm' ),
		__( 'Con trỏ chuột', 'nntm' ),
		'edit_theme_options',
		NNTM_CON_TRO_TRANG,
		'nntm_con_tro_trang_admin'
	);
}
add_action( 'admin_menu', 'nntm_con_tro_admin_menu' );

/**
 * Xử lý lưu — chạy sớm ở admin_init (trước khi có HTML) để có thể
 * wp_safe_redirect (chống resubmit khi bấm F5).
 */
function nntm_con_tro_xu_ly_luu(): void {
	if ( ! isset( $_POST['nntm_con_tro_nonce'] ) ) {
		return;
	}

	if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nntm_con_tro_nonce'] ) ), NNTM_CON_TRO_NONCE_HANH_DONG ) ) {
		return;
	}

	if ( ! current_user_can( 'edit_theme_options' ) ) {
		return;
	}

	// 1) Cài đặt chung.
	$tho = ( isset( $_POST['nntm_con_tro'] ) && is_array( $_POST['nntm_con_tro'] ) ) ? wp_unslash( $_POST['nntm_con_tro'] ) : array();

	update_option(
		NNTM_CON_TRO_OPTION,
		array(
			'kieu'        => nntm_con_tro_sanitize_kieu( $tho['kieu'] ?? '' ),
			'mau'         => nntm_con_tro_sanitize_mau( $tho['mau'] ?? '' ),
			'mau_phu'     => nntm_con_tro_sanitize_mau( $tho['mau_phu'] ?? '' ),
			'do_dai'      => nntm_con_tro_kep_do_dai( $tho['do_dai'] ?? NNTM_CON_TRO_DO_DAI_MAC_DINH ),
			'mat_do'      => nntm_con_tro_sanitize_mat_do( $tho['mat_do'] ?? 'vua' ),
			'co'          => nntm_con_tro_sanitize_co( $tho['co'] ?? 'vua' ),
			'giu_khi_bam' => ! empty( $tho['giu_khi_bam'] ),
		)
	);

	// 2) Theo từng trang.
	$loai_cho_phep = nntm_con_tro_post_types();
	$hang_tho      = ( isset( $_POST['nntm_con_tro_trang'] ) && is_array( $_POST['nntm_con_tro_trang'] ) ) ? wp_unslash( $_POST['nntm_con_tro_trang'] ) : array();

	$da_dung = array();
	$bo_qua  = array();
	$moi     = array();

	foreach ( $hang_tho as $hang ) {
		if ( ! is_array( $hang ) ) {
			continue;
		}

		$post_id = absint( $hang['post_id'] ?? 0 );

		if ( $post_id < 1 ) {
			continue;
		}

		if ( isset( $da_dung[ $post_id ] ) ) {
			$bo_qua[] = $post_id; // Trùng trang — chỉ giữ dòng đầu tiên.
			continue;
		}

		$bai = get_post( $post_id );

		if ( ! $bai || ! in_array( $bai->post_type, $loai_cho_phep, true ) ) {
			$bo_qua[] = $post_id;
			continue;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			$bo_qua[] = $post_id;
			continue;
		}

		$da_dung[ $post_id ] = true;

		$moi[ $post_id ] = array(
			'kieu'   => nntm_con_tro_sanitize_kieu_trang( $hang['kieu'] ?? '' ),
			'mau'    => nntm_con_tro_sanitize_mau( $hang['mau'] ?? '' ),
			'do_dai' => nntm_con_tro_kep_do_dai_trang( $hang['do_dai'] ?? '' ),
		);
	}

	// Dòng đã có meta mà không còn trong danh sách gửi lên = đã bị xoá trên form.
	$dang_co = nntm_con_tro_lay_danh_sach_trang_rieng( 500 );

	foreach ( $dang_co as $bai_id => $du_lieu ) {
		if ( ! isset( $moi[ $bai_id ] ) ) {
			delete_post_meta( $bai_id, NNTM_CON_TRO_META_KIEU );
			delete_post_meta( $bai_id, NNTM_CON_TRO_META_MAU );
			delete_post_meta( $bai_id, NNTM_CON_TRO_META_DO_DAI );
		}
	}

	foreach ( $moi as $post_id => $d ) {
		if ( '' === $d['kieu'] ) {
			delete_post_meta( $post_id, NNTM_CON_TRO_META_KIEU );
		} else {
			update_post_meta( $post_id, NNTM_CON_TRO_META_KIEU, $d['kieu'] );
		}

		if ( '' === $d['mau'] ) {
			delete_post_meta( $post_id, NNTM_CON_TRO_META_MAU );
		} else {
			update_post_meta( $post_id, NNTM_CON_TRO_META_MAU, $d['mau'] );
		}

		if ( '' === $d['do_dai'] ) {
			delete_post_meta( $post_id, NNTM_CON_TRO_META_DO_DAI );
		} else {
			update_post_meta( $post_id, NNTM_CON_TRO_META_DO_DAI, $d['do_dai'] );
		}
	}

	// 3) Theo từ khoá tìm kiếm.
	if ( class_exists( '\NNTM\Core\Tu_Khoa_Dong' ) ) {
		$tu_khoa_tho = ( isset( $_POST['nntm_con_tro_tu_khoa'] ) && is_array( $_POST['nntm_con_tro_tu_khoa'] ) ) ? wp_unslash( $_POST['nntm_con_tro_tu_khoa'] ) : array();

		foreach ( $tu_khoa_tho as $tu_khoa_id => $d ) {
			$tu_khoa_id = absint( $tu_khoa_id );

			if ( $tu_khoa_id < 1 || ! is_array( $d ) ) {
				continue;
			}

			if ( \NNTM\Core\Tu_Khoa_Dong::POST_TYPE !== get_post_type( $tu_khoa_id ) ) {
				$bo_qua[] = $tu_khoa_id;
				continue;
			}

			if ( ! current_user_can( 'edit_post', $tu_khoa_id ) ) {
				$bo_qua[] = $tu_khoa_id;
				continue;
			}

			$kieu = nntm_con_tro_sanitize_kieu( $d['kieu'] ?? '' );
			$mau  = nntm_con_tro_sanitize_mau( $d['mau'] ?? '' );

			if ( '' === $kieu ) {
				delete_post_meta( $tu_khoa_id, '_nntm_tkd_con_tro_kieu' );
			} else {
				update_post_meta( $tu_khoa_id, '_nntm_tkd_con_tro_kieu', $kieu );
			}

			if ( '' === $mau ) {
				delete_post_meta( $tu_khoa_id, '_nntm_tkd_con_tro_mau' );
			} else {
				update_post_meta( $tu_khoa_id, '_nntm_tkd_con_tro_mau', $mau );
			}
		}
	}

	if ( ! empty( $bo_qua ) ) {
		set_transient( 'nntm_con_tro_bo_qua_' . get_current_user_id(), array_unique( $bo_qua ), 60 );
	}

	wp_safe_redirect( add_query_arg( 'da_luu', '1', nntm_con_tro_url_quan_ly() ) );
	exit;
}
add_action( 'admin_init', 'nntm_con_tro_xu_ly_luu' );

/**
 * Nạp wp-color-picker + JS/CSS riêng của màn quản trị — chỉ đúng trang này.
 *
 * Không so hook đầy đủ (xem lý do trong inc/preloader-settings.php: admin
 * tiếng Việt dịch "Appearance" -> "giao-dien", so 'appearance_page_...' không
 * bao giờ khớp) — so theo SLUG TRANG, không bị dịch.
 */
function nntm_con_tro_admin_assets( string $hook ): void {
	if ( false === strpos( $hook, NNTM_CON_TRO_TRANG ) ) {
		return;
	}

	wp_enqueue_style( 'wp-color-picker' );
	wp_enqueue_script( 'wp-color-picker' );

	$tokens_path = NNTM_THEME_DIR . '/assets/css/tokens.css';
	wp_enqueue_style( 'nntm-tokens', NNTM_THEME_URI . '/assets/css/tokens.css', array(), nntm_asset_version( $tokens_path ) );

	$css_path = NNTM_THEME_DIR . '/assets/css/con-tro.css';
	wp_enqueue_style( 'nntm-con-tro', NNTM_THEME_URI . '/assets/css/con-tro.css', array( 'nntm-tokens' ), nntm_asset_version( $css_path ) );

	$engine_path = NNTM_THEME_DIR . '/assets/js/con-tro.js';
	wp_enqueue_script( 'nntm-con-tro', NNTM_THEME_URI . '/assets/js/con-tro.js', array(), nntm_asset_version( $engine_path ), true );

	$admin_js_path = NNTM_THEME_DIR . '/assets/js/admin/con-tro-admin.js';
	wp_enqueue_script(
		'nntm-con-tro-admin',
		NNTM_THEME_URI . '/assets/js/admin/con-tro-admin.js',
		array( 'nntm-con-tro', 'wp-color-picker', 'jquery' ),
		nntm_asset_version( $admin_js_path ),
		true
	);

	wp_localize_script(
		'nntm-con-tro-admin',
		'nntmConTroAdmin',
		array(
			'restSearchUrl' => esc_url_raw( rest_url( 'wp/v2/search' ) ),
			'restNonce'     => wp_create_nonce( 'wp_rest' ),
			'postTypes'     => nntm_con_tro_post_types(),
			'mauMacDinh'    => array_map( 'nntm_con_tro_mau_mac_dinh_theo_kieu', array_keys( nntm_con_tro_danh_sach_kieu() ) ),
			'i18n'          => array(
				'khongDung'   => __( 'Không dùng', 'nntm' ),
				'tatOTrangNay' => __( 'Không dùng ở trang này', 'nntm' ),
				'theoChung'   => __( 'Theo cài đặt chung', 'nntm' ),
				'chuaChonTrang' => __( '— chưa chọn trang —', 'nntm' ),
				'dangTim'     => __( 'Đang tìm…', 'nntm' ),
				'khongThay'   => __( 'Không tìm thấy.', 'nntm' ),
				'trungTrang'  => __( 'Trang này đã có trong bảng rồi.', 'nntm' ),
				'veMacDinh'   => __( 'Về màu mặc định', 'nntm' ),
			),
		)
	);
}
add_action( 'admin_enqueue_scripts', 'nntm_con_tro_admin_assets' );

function nntm_con_tro_trang_admin(): void {
	if ( ! current_user_can( 'edit_theme_options' ) ) {
		return;
	}

	$chung  = nntm_con_tro_cai_dat_chung();
	$ds_kieu = nntm_con_tro_danh_sach_kieu();
	$trang_rieng = nntm_con_tro_lay_danh_sach_trang_rieng( 50 );

	$bo_qua_id = get_transient( 'nntm_con_tro_bo_qua_' . get_current_user_id() );
	delete_transient( 'nntm_con_tro_bo_qua_' . get_current_user_id() );
	?>
	<div class="wrap nntm-con-tro-admin">
		<h1><?php esc_html_e( 'Con trỏ chuột', 'nntm' ); ?></h1>

		<p>
			<?php esc_html_e( 'Đổi hình con trỏ chuột và vệt sáng đi theo trên toàn site. Mặc định KHÔNG DÙNG. Có thể chọn riêng một hiệu ứng khác cho từng trang ở bảng bên dưới.', 'nntm' ); ?>
		</p>

		<?php if ( isset( $_GET['da_luu'] ) ) : ?>
			<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Đã lưu cài đặt con trỏ chuột.', 'nntm' ); ?></p></div>
		<?php endif; ?>

		<?php if ( ! empty( $bo_qua_id ) && is_array( $bo_qua_id ) ) : ?>
			<div class="notice notice-warning is-dismissible">
				<p>
					<?php esc_html_e( 'Một số dòng đã bị bỏ qua (ID không hợp lệ, không thuộc loại nội dung cho phép, hoặc bạn không có quyền sửa bài đó):', 'nntm' ); ?>
					<?php echo esc_html( implode( ', ', array_map( 'absint', $bo_qua_id ) ) ); ?>
				</p>
			</div>
		<?php endif; ?>

		<h2><?php esc_html_e( 'Xem thử', 'nntm' ); ?></h2>
		<p class="description"><?php esc_html_e( 'Luôn phản ánh dòng đang được sửa: cài đặt chung, hoặc một dòng trong bảng "Theo từng trang" khi bấm "Xem thử" ở dòng đó.', 'nntm' ); ?></p>

		<div id="nntm-con-tro-khung-thu" class="nntm-con-tro-khung-thu" data-nen="toi">
			<div class="nntm-con-tro-khung-thu__vung"></div>
			<div class="nntm-con-tro-khung-thu__thanh">
				<span id="nntm-con-tro-khung-thu__nguon"><?php esc_html_e( 'Đang xem thử: Cài đặt chung', 'nntm' ); ?></span>
				<button type="button" class="button button-small" id="nntm-con-tro-doi-nen"><?php esc_html_e( 'Đổi nền sáng/tối', 'nntm' ); ?></button>
			</div>
		</div>

		<form method="post" action="<?php echo esc_url( nntm_con_tro_url_quan_ly() ); ?>">
			<?php wp_nonce_field( NNTM_CON_TRO_NONCE_HANH_DONG, 'nntm_con_tro_nonce' ); ?>

			<h2><?php esc_html_e( 'Cài đặt chung', 'nntm' ); ?></h2>
			<p class="description"><?php esc_html_e( 'Áp dụng cho MỌI trang không có cài đặt riêng ở bảng bên dưới.', 'nntm' ); ?></p>

			<div class="nntm-con-tro-luoi-kieu" data-vai-tro="chung">
				<label class="nntm-con-tro-the">
					<input type="radio" name="nntm_con_tro[kieu]" value="" <?php checked( '', $chung['kieu'] ); ?> />
					<span class="nntm-con-tro-the__ten"><?php esc_html_e( 'Không dùng', 'nntm' ); ?></span>
					<span class="nntm-con-tro-the__mo-ta"><?php esc_html_e( 'Mặc định — không nạp hiệu ứng nào.', 'nntm' ); ?></span>
				</label>
				<?php foreach ( $ds_kieu as $khoa => $d ) : ?>
					<label class="nntm-con-tro-the">
						<input type="radio" name="nntm_con_tro[kieu]" value="<?php echo esc_attr( $khoa ); ?>" <?php checked( $khoa, $chung['kieu'] ); ?> />
						<span class="nntm-con-tro-the__ten"><?php echo esc_html( $d['ten'] ); ?></span>
						<span class="nntm-con-tro-the__mo-ta"><?php echo esc_html( $d['mo_ta'] ); ?></span>
					</label>
				<?php endforeach; ?>
			</div>

			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><label for="nntm-con-tro-mau"><?php esc_html_e( 'Màu chính', 'nntm' ); ?></label></th>
					<td>
						<input type="text" id="nntm-con-tro-mau" class="nntm-con-tro-mau-picker" name="nntm_con_tro[mau]" value="<?php echo esc_attr( $chung['mau'] ); ?>" data-default-color="" />
						<button type="button" class="button nntm-con-tro-ve-mac-dinh" data-target="nntm-con-tro-mau"><?php esc_html_e( 'Về màu mặc định', 'nntm' ); ?></button>
						<p class="description"><?php esc_html_e( 'Để trống dùng màu mặc định của từng kiểu.', 'nntm' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="nntm-con-tro-mau-phu"><?php esc_html_e( 'Màu phụ', 'nntm' ); ?></label></th>
					<td>
						<input type="text" id="nntm-con-tro-mau-phu" class="nntm-con-tro-mau-picker" name="nntm_con_tro[mau_phu]" value="<?php echo esc_attr( $chung['mau_phu'] ); ?>" data-default-color="" />
						<button type="button" class="button nntm-con-tro-ve-mac-dinh" data-target="nntm-con-tro-mau-phu"><?php esc_html_e( 'Về màu mặc định', 'nntm' ); ?></button>
						<p class="description"><?php esc_html_e( 'Để trống thì tự pha sáng ~35% từ màu chính.', 'nntm' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="nntm-con-tro-do-dai"><?php esc_html_e( 'Độ dài vệt', 'nntm' ); ?></label></th>
					<td>
						<input type="range" id="nntm-con-tro-do-dai" name="nntm_con_tro[do_dai]" min="<?php echo esc_attr( (string) NNTM_CON_TRO_DO_DAI_MIN ); ?>" max="<?php echo esc_attr( (string) NNTM_CON_TRO_DO_DAI_MAX ); ?>" value="<?php echo esc_attr( (string) $chung['do_dai'] ); ?>" />
						<output id="nntm-con-tro-do-dai-so"><?php echo esc_html( (string) $chung['do_dai'] ); ?></output>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="nntm-con-tro-mat-do"><?php esc_html_e( 'Mật độ hạt', 'nntm' ); ?></label></th>
					<td>
						<select id="nntm-con-tro-mat-do" name="nntm_con_tro[mat_do]">
							<option value="it" <?php selected( 'it', $chung['mat_do'] ); ?>><?php esc_html_e( 'Ít', 'nntm' ); ?></option>
							<option value="vua" <?php selected( 'vua', $chung['mat_do'] ); ?>><?php esc_html_e( 'Vừa', 'nntm' ); ?></option>
							<option value="nhieu" <?php selected( 'nhieu', $chung['mat_do'] ); ?>><?php esc_html_e( 'Nhiều', 'nntm' ); ?></option>
						</select>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="nntm-con-tro-co"><?php esc_html_e( 'Cỡ con trỏ', 'nntm' ); ?></label></th>
					<td>
						<select id="nntm-con-tro-co" name="nntm_con_tro[co]">
							<option value="nho" <?php selected( 'nho', $chung['co'] ); ?>><?php esc_html_e( 'Nhỏ', 'nntm' ); ?></option>
							<option value="vua" <?php selected( 'vua', $chung['co'] ); ?>><?php esc_html_e( 'Vừa', 'nntm' ); ?></option>
							<option value="lon" <?php selected( 'lon', $chung['co'] ); ?>><?php esc_html_e( 'Lớn', 'nntm' ); ?></option>
						</select>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Khi bấm vào kết quả tìm kiếm', 'nntm' ); ?></th>
					<td>
						<label>
							<input type="checkbox" name="nntm_con_tro[giu_khi_bam]" value="1" <?php checked( $chung['giu_khi_bam'] ); ?> />
							<?php esc_html_e( 'Giữ hiệu ứng đang hiện (do khớp từ khoá tìm kiếm) sang trang kế tiếp', 'nntm' ); ?>
						</label>
						<p class="description">
							<?php esc_html_e( 'Mặc định TẮT. Bật thì: bấm vào một kết quả trên trang tìm kiếm sẽ mang hiệu ứng con trỏ đang hiện sang trang vừa mở, nếu trang đó chưa có cài đặt riêng. Tự hết khi đóng tab, hoặc khi tìm một câu khác không còn khớp từ khoá nào.', 'nntm' ); ?>
						</p>
					</td>
				</tr>
			</table>

			<h2><?php esc_html_e( 'Theo từng trang', 'nntm' ); ?></h2>
			<p class="description">
				<?php esc_html_e( 'Ghi đè cài đặt chung cho từng bài/trang cụ thể. Không được chọn trùng một trang hai lần.', 'nntm' ); ?>
			</p>

			<table class="widefat nntm-con-tro-bang-trang" id="nntm-con-tro-bang-trang">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Trang', 'nntm' ); ?></th>
						<th><?php esc_html_e( 'Hiệu ứng', 'nntm' ); ?></th>
						<th><?php esc_html_e( 'Màu', 'nntm' ); ?></th>
						<th><?php esc_html_e( 'Độ dài vệt', 'nntm' ); ?></th>
						<th></th>
					</tr>
				</thead>
				<tbody id="nntm-con-tro-bang-trang__than">
					<?php
					$chi_so = 0;
					foreach ( $trang_rieng as $post_id => $d ) :
						nntm_con_tro_ve_dong_bang( $chi_so, $post_id, get_the_title( $post_id ), $d['kieu'], $d['mau'], $d['do_dai'], $ds_kieu );
						++$chi_so;
					endforeach;
					?>
				</tbody>
			</table>

			<p>
				<button type="button" class="button" id="nntm-con-tro-them-trang"><?php esc_html_e( '+ Thêm trang', 'nntm' ); ?></button>
			</p>

			<?php if ( class_exists( '\NNTM\Core\Tu_Khoa_Dong' ) ) : ?>
				<h2><?php esc_html_e( 'Theo từ khoá tìm kiếm', 'nntm' ); ?></h2>
				<p class="description">
					<?php esc_html_e( 'Khi câu tìm kiếm của khách khớp đúng một từ khoá động (hoặc một cách viết khác của nó), trang kết quả tìm kiếm dùng hiệu ứng gắn ở đây thay vì cài đặt chung. Từ khoá không gắn hiệu ứng nào (để "Không gắn") thì không ảnh hưởng gì — vẫn dùng bình thường cho tính năng gợi ý khi rê chuột.', 'nntm' ); ?>
				</p>
				<?php
				$danh_sach_tu_khoa = get_posts(
					array(
						'post_type'      => \NNTM\Core\Tu_Khoa_Dong::POST_TYPE,
						'post_status'    => 'publish',
						'posts_per_page' => 200,
						'orderby'        => 'title',
						'order'          => 'ASC',
						'no_found_rows'  => true,
					)
				);
				?>
				<?php if ( empty( $danh_sach_tu_khoa ) ) : ?>
					<p><em><?php esc_html_e( 'Chưa có từ khoá động nào.', 'nntm' ); ?></em></p>
				<?php else : ?>
					<table class="widefat nntm-con-tro-bang-trang">
						<thead>
							<tr>
								<th><?php esc_html_e( 'Từ khoá', 'nntm' ); ?></th>
								<th><?php esc_html_e( 'Hiệu ứng', 'nntm' ); ?></th>
								<th><?php esc_html_e( 'Màu', 'nntm' ); ?></th>
								<th></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $danh_sach_tu_khoa as $tu_khoa ) : ?>
								<?php
								$ct_kieu = (string) get_post_meta( $tu_khoa->ID, '_nntm_tkd_con_tro_kieu', true );
								$ct_mau  = (string) get_post_meta( $tu_khoa->ID, '_nntm_tkd_con_tro_mau', true );
								$ten     = 'nntm_con_tro_tu_khoa[' . $tu_khoa->ID . ']';
								?>
								<tr class="nntm-con-tro-dong">
									<td>
										<a href="<?php echo esc_url( get_edit_post_link( $tu_khoa->ID ) ); ?>"><?php echo esc_html( get_the_title( $tu_khoa ) ); ?></a>
									</td>
									<td>
										<select class="nntm-con-tro-dong__kieu" name="<?php echo esc_attr( $ten ); ?>[kieu]">
											<option value="" <?php selected( '', $ct_kieu ); ?>><?php esc_html_e( 'Không gắn', 'nntm' ); ?></option>
											<?php foreach ( $ds_kieu as $khoa => $d ) : ?>
												<option value="<?php echo esc_attr( $khoa ); ?>" <?php selected( $khoa, $ct_kieu ); ?>><?php echo esc_html( $d['ten'] ); ?></option>
											<?php endforeach; ?>
										</select>
									</td>
									<td>
										<input type="text" class="nntm-con-tro-dong__mau nntm-con-tro-mau-picker" name="<?php echo esc_attr( $ten ); ?>[mau]" value="<?php echo esc_attr( $ct_mau ); ?>" data-default-color="" />
									</td>
									<td>
										<button type="button" class="button button-small nntm-con-tro-dong__xem-thu"><?php esc_html_e( 'Xem thử', 'nntm' ); ?></button>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				<?php endif; ?>
			<?php endif; ?>

			<?php submit_button( __( 'Lưu cài đặt', 'nntm' ) ); ?>
		</form>

		<template id="nntm-con-tro-mau-dong-trong">
			<?php nntm_con_tro_ve_dong_bang( '__CHI_SO__', 0, '', '', '', '', $ds_kieu ); ?>
		</template>
	</div>
	<?php
}

/**
 * Vẽ một <tr> của bảng "Theo từng trang" — dùng cả khi render sẵn (PHP) lẫn
 * làm mẫu <template> để JS nhân bản khi bấm "+ Thêm trang".
 *
 * @param int|string $chi_so   Số thứ tự dòng (dùng trong name="..."), hoặc chuỗi giữ chỗ cho mẫu.
 */
function nntm_con_tro_ve_dong_bang( $chi_so, int $post_id, string $tieu_de, string $kieu, string $mau, string $do_dai, array $ds_kieu ): void {
	$ten = 'nntm_con_tro_trang[' . $chi_so . ']';
	?>
	<tr class="nntm-con-tro-dong" data-post-id="<?php echo esc_attr( (string) $post_id ); ?>">
		<td>
			<input type="hidden" class="nntm-con-tro-dong__post-id" name="<?php echo esc_attr( $ten ); ?>[post_id]" value="<?php echo esc_attr( (string) $post_id ); ?>" />
			<input type="text" class="nntm-con-tro-dong__tim regular-text" placeholder="<?php esc_attr_e( 'Gõ tên bài/trang để tìm…', 'nntm' ); ?>" value="<?php echo esc_attr( $tieu_de ); ?>" autocomplete="off" />
			<div class="nntm-con-tro-dong__ket-qua" hidden></div>
		</td>
		<td>
			<select class="nntm-con-tro-dong__kieu" name="<?php echo esc_attr( $ten ); ?>[kieu]">
				<option value="" <?php selected( '', $kieu ); ?>><?php esc_html_e( 'Theo cài đặt chung', 'nntm' ); ?></option>
				<option value="tat" <?php selected( 'tat', $kieu ); ?>><?php esc_html_e( 'Không dùng ở trang này', 'nntm' ); ?></option>
				<?php foreach ( $ds_kieu as $khoa => $d ) : ?>
					<option value="<?php echo esc_attr( $khoa ); ?>" <?php selected( $khoa, $kieu ); ?>><?php echo esc_html( $d['ten'] ); ?></option>
				<?php endforeach; ?>
			</select>
		</td>
		<td>
			<input type="text" class="nntm-con-tro-dong__mau nntm-con-tro-mau-picker" name="<?php echo esc_attr( $ten ); ?>[mau]" value="<?php echo esc_attr( $mau ); ?>" data-default-color="" />
		</td>
		<td>
			<input type="number" class="nntm-con-tro-dong__do-dai small-text" name="<?php echo esc_attr( $ten ); ?>[do_dai]" min="<?php echo esc_attr( (string) NNTM_CON_TRO_DO_DAI_MIN ); ?>" max="<?php echo esc_attr( (string) NNTM_CON_TRO_DO_DAI_MAX ); ?>" value="<?php echo esc_attr( $do_dai ); ?>" placeholder="<?php esc_attr_e( 'chung', 'nntm' ); ?>" />
		</td>
		<td>
			<button type="button" class="button button-small nntm-con-tro-dong__xem-thu"><?php esc_html_e( 'Xem thử', 'nntm' ); ?></button>
			<button type="button" class="button-link-delete nntm-con-tro-dong__xoa"><?php esc_html_e( 'Xoá', 'nntm' ); ?></button>
		</td>
	</tr>
	<?php
}
