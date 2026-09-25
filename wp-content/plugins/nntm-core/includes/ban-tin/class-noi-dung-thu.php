<?php
/**
 * Dựng nội dung thư: bản tin định kỳ, thư dịp lễ, xác nhận Khóa Tu.
 *
 * Khung thư viết bằng bảng + style nội dòng — trình đọc thư (Gmail, Outlook)
 * bỏ qua CSS ngoài và phần lớn CSS trong <style>. Màu lấy từ tokens.css của
 * theme nhưng phải chép cứng: thư không đọc được biến CSS.
 *
 * Nằm trong plugin chứ không ở theme: đổi theme không được làm hỏng thư.
 *
 * @package NNTM_Core
 */

namespace NNTM\Core;

defined( 'ABSPATH' ) || exit;

/**
 * Nội dung + khung thư.
 */
final class Noi_Dung_Thu {

	/* Màu chép từ themes/nntm/assets/css/tokens.css. */
	private const MAU_NEN    = '#F7F1DE'; // --nntm-kem
	private const MAU_VIEN   = '#F0E7C9'; // --nntm-kem-dam
	private const MAU_CHU    = '#3F3B3B'; // --nntm-muc
	private const MAU_PHU    = '#747766'; // --nntm-reu
	private const MAU_NHAN   = '#A47764'; // --nntm-nau-dat
	// KHÔNG dùng Georgia: thiếu glyph tiếng Việt dựng sẵn (ế, ầ, ẩ…) nên dấu bị
	// tách rời khỏi chữ. Trình đọc thư không tải web font, chữ thật sự hiện ra là
	// font dự phòng — Times New Roman (Windows/macOS/iOS) và Noto Serif (Android)
	// đều đủ tiếng Việt.
	private const FONT       = "'EB Garamond', 'Times New Roman', 'Noto Serif', Times, serif";

	/* ---------- Chọn bài cho bản tin ---------- */

	/**
	 * Bài mới đăng trong khoảng [$tu, $den), CHỈ nội dung công khai.
	 *
	 * Người nhận là thành viên, nhưng thư bị chuyển tiếp là chuyện thường — tiêu
	 * đề + trích đoạn bài khu Hành Giả không được nằm trong thư. Lọc tường minh ở
	 * đây vì cron chạy bằng dòng lệnh thì cổng quyền của theme mở (PHP_SAPI cli).
	 *
	 * @param string   $tu         'Y-m-d H:i:s' giờ site.
	 * @param string   $den        'Y-m-d H:i:s' giờ site.
	 * @param string[] $post_types Loại nội dung.
	 * @param int      $toi_da     Số bài tối đa.
	 * @return \WP_Post[]
	 */
	public static function bai_moi( string $tu, string $den, array $post_types, int $toi_da ): array {
		$post_types = array_values( array_filter( $post_types, 'post_type_exists' ) );
		if ( ! $post_types ) {
			return array();
		}

		$args = array(
			'post_type'           => $post_types,
			'post_status'         => 'publish',
			'posts_per_page'      => $toi_da * 3,
			'orderby'             => 'date',
			'order'               => 'DESC',
			'ignore_sticky_posts' => true,
			'no_found_rows'       => true,
			'date_query'          => array(
				array(
					'after'     => $tu,
					'before'    => $den,
					'inclusive' => false,
					'column'    => 'post_date',
				),
			),
		);

		// Bản tin viết bằng tiếng Việt — chỉ lấy bài ngôn ngữ mặc định.
		if ( function_exists( 'pll_default_language' ) ) {
			$args['lang'] = pll_default_language();
		}

		$ket = array();
		foreach ( ( new \WP_Query( $args ) )->posts as $post ) {
			if ( $post instanceof \WP_Post && self::la_cong_khai( $post ) ) {
				$ket[] = $post;
			}
			if ( count( $ket ) >= $toi_da ) {
				break;
			}
		}

		return $ket;
	}

	/** Bài có được đưa vào thư không (khách vãng lai xem được). */
	public static function la_cong_khai( \WP_Post $post ): bool {
		if ( '' !== (string) $post->post_password ) {
			return false;
		}

		// Dùng chung phán quyết với chỉ mục tìm kiếm — một nguồn sự thật cho "khu hạn chế".
		if ( function_exists( 'nntm_search_post_acl' ) ) {
			$cong_khai = 'public' === nntm_search_post_acl( $post );
		} else {
			$cong_khai = true;

			if ( 'nntm_article' === $post->post_type ) {
				$han_che = function_exists( 'nntm_term_khu_han_che' ) ? nntm_term_khu_han_che() : null;
				if ( null === $han_che ) {
					$cong_khai = false; // Không biết khu nào hạn chế → đóng.
				} elseif ( has_term( $han_che, 'nntm_section', $post ) ) {
					$cong_khai = false;
				}
			}
		}

		return (bool) apply_filters( 'nntm_ban_tin_bai_cong_khai', $cong_khai, $post );
	}

	/* ---------- Bản tin ---------- */

	/**
	 * @param \WP_Post[]           $bai     Bài.
	 * @param array<string,mixed>  $cai_dat Cài đặt bản tin.
	 * @param string               $ky      Nhãn kỳ, ví dụ "tuần 21/09 – 27/09/2026".
	 * @return array{tieu_de:string,html:string,van_ban:string}
	 */
	public static function ban_tin( array $bai, array $cai_dat, string $ky ): array {
		$site    = self::ten_site();
		$tieu_de = strtr( (string) $cai_dat['tieu_de'], array( '{site}' => $site, '{ky}' => $ky ) );

		$mo_dau_html = wpautop( esc_html( (string) $cai_dat['loi_mo_dau'] ) );
		$mo_dau_text = (string) $cai_dat['loi_mo_dau'];

		$hang = '';
		$text = array();

		foreach ( $bai as $p ) {
			$link    = get_permalink( $p );
			$ten     = html_entity_decode( get_the_title( $p ), ENT_QUOTES, 'UTF-8' );
			$loai    = get_post_type_object( $p->post_type );
			$nhan    = $loai ? $loai->labels->singular_name : '';
			$trich   = self::trich_doan( $p, 32 );
			$anh_url = get_the_post_thumbnail_url( $p, 'medium' );

			$o_anh = $anh_url
				? '<td width="132" valign="top" style="padding:0 16px 0 0;"><a href="' . esc_url( $link ) . '"><img src="' . esc_url( $anh_url ) . '" width="116" alt="" style="display:block;width:116px;max-width:116px;height:auto;border-radius:6px;border:0;"></a></td>'
				: '';

			$hang .= '<tr><td style="padding:0 0 22px;">'
				. '<table role="presentation" width="100%" cellpadding="0" cellspacing="0"><tr>'
				. $o_anh
				. '<td valign="top" style="font-family:' . self::FONT . ';">'
				. ( '' !== $nhan ? '<div style="font-size:12px;letter-spacing:1px;text-transform:uppercase;color:' . self::MAU_PHU . ';">' . esc_html( $nhan ) . '</div>' : '' )
				. '<a href="' . esc_url( $link ) . '" style="display:block;margin:2px 0 6px;font-size:19px;line-height:1.3;color:' . self::MAU_NHAN . ';text-decoration:none;font-weight:600;">' . esc_html( $ten ) . '</a>'
				. ( '' !== $trich ? '<div style="font-size:15px;line-height:1.55;color:' . self::MAU_CHU . ';">' . esc_html( $trich ) . '</div>' : '' )
				. '<a href="' . esc_url( $link ) . '" style="display:inline-block;margin-top:6px;font-size:14px;color:' . self::MAU_PHU . ';">' . esc_html__( 'Đọc tiếp →', 'nntm' ) . '</a>'
				. '</td></tr></table>'
				. '</td></tr>';

			$text[] = ( '' !== $nhan ? '[' . $nhan . '] ' : '' ) . $ten . "\n" . ( '' !== $trich ? $trich . "\n" : '' ) . $link;
		}

		$than = $mo_dau_html
			/* translators: %s: nhãn kỳ */
			. '<h1 style="margin:18px 0 20px;font-family:' . self::FONT . ';font-size:24px;line-height:1.3;font-weight:600;color:' . self::MAU_CHU . ';">' . esc_html( sprintf( __( 'Bài mới %s', 'nntm' ), $ky ) ) . '</h1>'
			. '<table role="presentation" width="100%" cellpadding="0" cellspacing="0">' . $hang . '</table>'
			. '<p style="margin:8px 0 0;"><a href="' . esc_url( home_url( '/' ) ) . '" style="color:' . self::MAU_NHAN . ';">' . esc_html( sprintf( __( 'Xem thêm tại %s', 'nntm' ), $site ) ) . '</a></p>';

		$van_ban = $mo_dau_text . "\n\n"
			. sprintf( __( 'Bài mới %s', 'nntm' ), $ky ) . "\n"
			. str_repeat( '-', 40 ) . "\n\n"
			. implode( "\n\n", $text ) . "\n\n"
			. home_url( '/' );

		return array(
			'tieu_de' => $tieu_de,
			'html'    => self::khung( $tieu_de, $than, true ),
			'van_ban' => $van_ban . self::chan_van_ban( true ),
		);
	}

	/* ---------- Dịp lễ ---------- */

	/**
	 * @param \WP_Post $dip Bài "dịp đặc biệt".
	 * @return array{tieu_de:string,html:string,van_ban:string}
	 */
	public static function dip_le( \WP_Post $dip ): array {
		$ten     = html_entity_decode( get_the_title( $dip ), ENT_QUOTES, 'UTF-8' );
		$tieu_de = trim( (string) get_post_meta( $dip->ID, Ban_Tin::META_DIP_TIEU_DE, true ) );
		$tieu_de = '' !== $tieu_de ? $tieu_de : $ten;

		$noi_dung = (string) $dip->post_content;
		$noi_dung = has_blocks( $noi_dung ) ? do_blocks( $noi_dung ) : wpautop( wptexturize( $noi_dung ) );
		$noi_dung = wp_kses_post( $noi_dung );
		// Ảnh trong thư phải co theo màn hình điện thoại.
		$noi_dung = preg_replace( '/<img\b/i', '<img style="max-width:100%;height:auto;border:0;border-radius:6px;"', $noi_dung ) ?? $noi_dung;

		$anh     = get_the_post_thumbnail_url( $dip, 'large' );
		$o_anh   = $anh ? '<p style="margin:0 0 20px;"><img src="' . esc_url( $anh ) . '" alt="" width="536" style="display:block;width:100%;max-width:536px;height:auto;border:0;border-radius:8px;"></p>' : '';

		$than = $o_anh
			. '<h1 style="margin:0 0 16px;font-family:' . self::FONT . ';font-size:26px;line-height:1.3;font-weight:600;color:' . self::MAU_NHAN . ';">' . esc_html( $ten ) . '</h1>'
			. '<div style="font-family:' . self::FONT . ';font-size:17px;line-height:1.65;color:' . self::MAU_CHU . ';">' . $noi_dung . '</div>';

		$van_ban = $ten . "\n\n" . trim( html_entity_decode( wp_strip_all_tags( $noi_dung ), ENT_QUOTES, 'UTF-8' ) );

		return array(
			'tieu_de' => $tieu_de,
			'html'    => self::khung( $tieu_de, $than, true ),
			'van_ban' => $van_ban . self::chan_van_ban( true ),
		);
	}

	/* ---------- Xác nhận Khóa Tu ---------- */

	/**
	 * @param \WP_Post            $khoa_tu Khóa tu.
	 * @param array<string,string> $dk     full_name, phone, email, note.
	 * @return array{tieu_de:string,html:string,van_ban:string}
	 */
	public static function xac_nhan_khoa_tu( \WP_Post $khoa_tu, array $dk ): array {
		$ten_khoa = html_entity_decode( get_the_title( $khoa_tu ), ENT_QUOTES, 'UTF-8' );
		/* translators: %s: tên khóa tu */
		$tieu_de  = sprintf( __( 'Đã nhận đăng ký: %s', 'nntm' ), $ten_khoa );
		$link     = get_permalink( $khoa_tu );

		$dong = array(
			__( 'Họ và tên', 'nntm' )    => $dk['full_name'] ?? '',
			__( 'Điện thoại', 'nntm' )   => $dk['phone'] ?? '',
			__( 'Email', 'nntm' )        => $dk['email'] ?? '',
			__( 'Ghi chú', 'nntm' )      => $dk['note'] ?? '',
		);

		$bang = '';
		$text = '';
		foreach ( $dong as $nhan => $gia_tri ) {
			if ( '' === trim( (string) $gia_tri ) ) {
				continue;
			}
			$bang .= '<tr><td style="padding:6px 12px 6px 0;color:' . self::MAU_PHU . ';white-space:nowrap;vertical-align:top;">' . esc_html( $nhan ) . '</td><td style="padding:6px 0;">' . nl2br( esc_html( (string) $gia_tri ) ) . '</td></tr>';
			$text .= $nhan . ': ' . $gia_tri . "\n";
		}

		$ten = (string) ( $dk['full_name'] ?? '' );

		$than = '<p style="margin:0 0 14px;">' . esc_html( sprintf( __( 'Kính gửi %s,', 'nntm' ), $ten ) ) . '</p>'
			. '<p style="margin:0 0 14px;">' . sprintf(
				/* translators: %s: tên khóa tu có liên kết */
				esc_html__( 'Chúng tôi đã nhận được đăng ký tham dự %s của quý vị.', 'nntm' ),
				'<a href="' . esc_url( $link ) . '" style="color:' . self::MAU_NHAN . ';font-weight:600;">' . esc_html( $ten_khoa ) . '</a>'
			) . '</p>'
			. '<table role="presentation" cellpadding="0" cellspacing="0" style="margin:0 0 16px;font-family:' . self::FONT . ';font-size:16px;color:' . self::MAU_CHU . ';">' . $bang . '</table>'
			. '<p style="margin:0;">' . esc_html__( 'Ban quản trị sẽ liên hệ để xác nhận. Đây chưa phải thư xác nhận chính thức giữ chỗ.', 'nntm' ) . '</p>';

		$van_ban = sprintf( __( 'Kính gửi %s,', 'nntm' ), $ten ) . "\n\n"
			. sprintf( __( 'Chúng tôi đã nhận được đăng ký tham dự %s của quý vị.', 'nntm' ), $ten_khoa ) . "\n" . $link . "\n\n"
			. $text . "\n"
			. __( 'Ban quản trị sẽ liên hệ để xác nhận. Đây chưa phải thư xác nhận chính thức giữ chỗ.', 'nntm' );

		return array(
			'tieu_de' => $tieu_de,
			'html'    => self::khung( $tieu_de, $than, false ),
			'van_ban' => $van_ban . self::chan_van_ban( false ),
		);
	}

	/* ---------- Khung chung ---------- */

	/**
	 * Khung thư. $hang_loat = true thì chân thư có link huỷ {{huy_url}}.
	 */
	public static function khung( string $tieu_de, string $than, bool $hang_loat ): string {
		$site = self::ten_site();

		$chan = $hang_loat
			? sprintf(
				/* translators: 1: tên site, 2: liên kết huỷ */
				esc_html__( 'Quý vị nhận thư này vì đã đăng ký nhận thông tin từ %1$s. %2$s', 'nntm' ),
				esc_html( $site ),
				'<a href="{{huy_url}}" style="color:' . self::MAU_PHU . ';">' . esc_html__( 'Huỷ nhận bản tin', 'nntm' ) . '</a>'
			)
			/* translators: %s: tên site */
			: esc_html( sprintf( __( 'Thư được gửi tự động từ %s.', 'nntm' ), $site ) );

		return '<!DOCTYPE html><html lang="vi"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">'
			. '<title>' . esc_html( $tieu_de ) . '</title></head>'
			. '<body style="margin:0;padding:0;background:' . self::MAU_NEN . ';">'
			. '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:' . self::MAU_NEN . ';"><tr><td align="center" style="padding:24px 12px;">'
			. '<table role="presentation" width="600" cellpadding="0" cellspacing="0" style="width:100%;max-width:600px;background:#FFFFFF;border:1px solid ' . self::MAU_VIEN . ';border-radius:10px;">'
			. '<tr><td align="center" style="padding:28px 32px 14px;font-family:' . self::FONT . ';font-size:22px;letter-spacing:3px;color:' . self::MAU_NHAN . ';">'
			. '<a href="' . esc_url( home_url( '/' ) ) . '" style="color:' . self::MAU_NHAN . ';text-decoration:none;">' . esc_html( mb_strtoupper( $site, 'UTF-8' ) ) . '</a></td></tr>'
			. '<tr><td style="padding:0 32px;"><div style="height:1px;line-height:1px;background:' . self::MAU_VIEN . ';">&nbsp;</div></td></tr>'
			. '<tr><td style="padding:24px 32px 30px;font-family:' . self::FONT . ';font-size:17px;line-height:1.6;color:' . self::MAU_CHU . ';">' . $than . '</td></tr>'
			. '</table>'
			. '<table role="presentation" width="600" cellpadding="0" cellspacing="0" style="width:100%;max-width:600px;"><tr><td align="center" style="padding:16px 32px;font-family:' . self::FONT . ';font-size:13px;line-height:1.5;color:' . self::MAU_PHU . ';">' . $chan . '</td></tr></table>'
			. '</td></tr></table></body></html>';
	}

	private static function chan_van_ban( bool $hang_loat ): string {
		$site = self::ten_site();

		return "\n\n--\n" . ( $hang_loat
			/* translators: %s: tên site */
			? sprintf( __( 'Quý vị nhận thư này vì đã đăng ký nhận thông tin từ %s.', 'nntm' ), $site ) . "\n" . __( 'Huỷ nhận bản tin:', 'nntm' ) . ' {{huy_url}}'
			: sprintf( __( 'Thư được gửi tự động từ %s.', 'nntm' ), $site ) );
	}

	public static function ten_site(): string {
		return wp_specialchars_decode( (string) get_bloginfo( 'name' ), ENT_QUOTES );
	}

	/** Trích đoạn chữ thuần, không chạy filter của theme (tránh chèn nút "Đọc tiếp"). */
	private static function trich_doan( \WP_Post $p, int $so_tu ): string {
		$nguon = '' !== trim( (string) $p->post_excerpt )
			? (string) $p->post_excerpt
			: excerpt_remove_blocks( strip_shortcodes( (string) $p->post_content ) );

		return trim( html_entity_decode( wp_trim_words( wp_strip_all_tags( $nguon ), $so_tu, '…' ), ENT_QUOTES, 'UTF-8' ) );
	}
}
