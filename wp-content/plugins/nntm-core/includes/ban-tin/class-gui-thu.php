<?php
/**
 * Hàng đợi và đường gửi thư của NNTM.
 *
 * Mọi thư (bản tin, dịp lễ, xác nhận Khóa Tu, thư thử) đi cùng một đường:
 * tạo một "campaign" (bảng nntm_mail_campaign) + một dòng hàng đợi cho mỗi
 * người nhận (nntm_mail_queue), rồi WP-Cron gửi dần theo lô mỗi phút. Nhờ đó
 * nhật ký gửi nằm một chỗ và gửi vài nghìn thư không làm treo request.
 *
 * ĐƯỜNG GỬI (khai trong wp-config.php, KHÔNG lưu trong database):
 *
 *   define( 'NNTM_SMTP_HOST', 'email-smtp.ap-southeast-1.amazonaws.com' ); // SES
 *   // hoặc 'smtp.sendgrid.net' (SendGrid: NNTM_SMTP_USER = 'apikey')
 *   define( 'NNTM_SMTP_PORT', 587 );
 *   define( 'NNTM_SMTP_USER', '...' );
 *   define( 'NNTM_SMTP_PASS', '...' );
 *   define( 'NNTM_SMTP_SECURE', 'tls' );
 *
 * Chưa khai NNTM_SMTP_HOST → CHẾ ĐỘ GHI LOG: thư được dựng đầy đủ, ghi vào
 * nhật ký, xem trước được, nhưng KHÔNG gửi đi. Khách chưa có tên miền (khảo sát
 * câu 38) nên chưa xác thực được domain với SES/SendGrid; gửi bằng mail() của
 * VPS thì gần như chắc chắn vào thư rác. Ép chế độ bằng
 * define( 'NNTM_MAIL_CHE_DO', 'gui_that' | 'ghi_log' ).
 *
 * @package NNTM_Core
 */

namespace NNTM\Core;

defined( 'ABSPATH' ) || exit;

/**
 * Gửi thư theo lô.
 */
final class Gui_Thu {

	/** Header đánh dấu thư của module này. */
	public const HEADER = 'X-NNTM-Mail';

	/** Sự kiện cron gửi một lô. */
	public const CRON_LO = 'nntm_mail_gui_lo';

	/** Khoá chống hai tiến trình cron gửi chồng nhau. */
	private const KHOA_CHAY = 'nntm_mail_dang_gui';

	/** Số lần thử lại tối đa cho một người nhận. */
	private const SO_LAN_TOI_DA = 3;

	/** Loại thư hàng loạt — cần link huỷ và kiểm lại đăng ký lúc gửi. */
	public const LOAI_HANG_LOAT = array( 'ban_tin', 'dip_le' );

	/** @var string|null Bản chữ thuần của thư đang gửi, gắn vào AltBody. */
	private static ?string $van_ban_dang_gui = null;

	/** Gắn hook. */
	public static function hooks(): void {
		add_filter( 'cron_schedules', array( __CLASS__, 'lich_moi_phut' ) );
		add_action( self::CRON_LO, array( __CLASS__, 'gui_lo' ) );
		add_action( 'phpmailer_init', array( __CLASS__, 'cau_hinh_phpmailer' ) );
	}

	/**
	 * @param array<string,array> $lich Lịch có sẵn.
	 * @return array<string,array>
	 */
	public static function lich_moi_phut( array $lich ): array {
		$lich['nntm_moi_phut'] = array(
			'interval' => MINUTE_IN_SECONDS,
			'display'  => __( 'Mỗi phút (NNTM gửi thư)', 'nntm' ),
		);

		return $lich;
	}

	/* ---------- Chế độ / đường gửi ---------- */

	/** 'gui_that' hoặc 'ghi_log'. */
	public static function che_do(): string {
		if ( defined( 'NNTM_MAIL_CHE_DO' ) && in_array( NNTM_MAIL_CHE_DO, array( 'gui_that', 'ghi_log' ), true ) ) {
			return NNTM_MAIL_CHE_DO;
		}

		return self::co_smtp() ? 'gui_that' : 'ghi_log';
	}

	public static function co_smtp(): bool {
		return defined( 'NNTM_SMTP_HOST' ) && '' !== (string) NNTM_SMTP_HOST;
	}

	/** Mô tả đường gửi cho màn quản trị — không bao giờ in mật khẩu. */
	public static function mo_ta_duong_gui(): string {
		if ( 'ghi_log' === self::che_do() ) {
			return __( 'Ghi log — thư KHÔNG được gửi đi', 'nntm' );
		}

		return self::co_smtp()
			/* translators: %s: máy chủ SMTP */
			? sprintf( __( 'SMTP %s', 'nntm' ), (string) NNTM_SMTP_HOST )
			: __( 'Hàm mail() của máy chủ (dễ vào thư rác)', 'nntm' );
	}

	/**
	 * Cấu hình SMTP cho MỌI thư của site (quên mật khẩu, liên hệ… cũng hưởng),
	 * và gắn bản chữ thuần cho thư của module này.
	 *
	 * @param \PHPMailer\PHPMailer\PHPMailer $mailer Đối tượng PHPMailer.
	 */
	public static function cau_hinh_phpmailer( $mailer ): void {
		if ( self::co_smtp() ) {
			$mailer->isSMTP();
			$mailer->Host       = (string) NNTM_SMTP_HOST;
			$mailer->Port       = defined( 'NNTM_SMTP_PORT' ) ? (int) NNTM_SMTP_PORT : 587;
			$mailer->SMTPSecure = defined( 'NNTM_SMTP_SECURE' ) ? (string) NNTM_SMTP_SECURE : 'tls';

			if ( defined( 'NNTM_SMTP_USER' ) && '' !== (string) NNTM_SMTP_USER ) {
				$mailer->SMTPAuth = true;
				$mailer->Username = (string) NNTM_SMTP_USER;
				$mailer->Password = defined( 'NNTM_SMTP_PASS' ) ? (string) NNTM_SMTP_PASS : '';
			}
		}

		if ( null !== self::$van_ban_dang_gui ) {
			$mailer->AltBody = self::$van_ban_dang_gui;
		}
	}

	/* ---------- Tạo campaign ---------- */

	/**
	 * Tạo campaign + hàng đợi. Khoá $khoa trùng thì không tạo lại (chống gửi đôi
	 * khi cron chạy chồng hoặc BQT bấm hai lần).
	 *
	 * @param string            $loai       ban_tin | dip_le | khoa_tu | thu.
	 * @param string            $khoa       Khoá duy nhất.
	 * @param int               $ref_id     ID đối tượng liên quan (dịp lễ, đăng ký…).
	 * @param string            $tieu_de    Tiêu đề thư.
	 * @param string            $html       HTML đầy đủ, có thể chứa {{ten}} / {{huy_url}}.
	 * @param string            $van_ban    Bản chữ thuần.
	 * @param array<int,array{user_id:int,email:string}> $nguoi_nhan Danh sách nhận.
	 * @return int|\WP_Error ID campaign.
	 */
	public static function tao( string $loai, string $khoa, int $ref_id, string $tieu_de, string $html, string $van_ban, array $nguoi_nhan ) {
		global $wpdb;

		$bang_c = Schema::table( 'mail_campaign' );
		$bang_q = Schema::table( 'mail_queue' );

		$sach = array();
		foreach ( $nguoi_nhan as $n ) {
			$email = sanitize_email( (string) ( $n['email'] ?? '' ) );
			if ( is_email( $email ) ) {
				$sach[ strtolower( $email ) ] = array( (int) ( $n['user_id'] ?? 0 ), $email );
			}
		}

		if ( ! $sach ) {
			return new \WP_Error( 'khong_co_nguoi_nhan', __( 'Không có người nhận hợp lệ.', 'nntm' ) );
		}

		$khoa   = mb_substr( $khoa, 0, 100 );
		$da_co  = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$bang_c} WHERE khoa = %s", $khoa ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		if ( $da_co ) {
			return new \WP_Error( 'da_co', __( 'Thư này đã được tạo trước đó.', 'nntm' ) );
		}

		// Hai tiến trình vẫn có thể cùng lọt qua bước kiểm trên — UNIQUE KEY chặn
		// kẻ đến sau; tắt in lỗi để lỗi trùng khoá không hiện ra màn hình.
		$an_loi = $wpdb->suppress_errors( true );
		$ok     = $wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$bang_c,
			array(
				'loai'       => $loai,
				'khoa'       => $khoa,
				'ref_id'     => $ref_id,
				'tieu_de'    => mb_substr( $tieu_de, 0, 255 ),
				'noi_dung'   => $html,
				'van_ban'    => $van_ban,
				'trang_thai' => 'dang_gui',
				'tong'       => count( $sach ),
				'che_do'     => self::che_do(),
				'created_at' => current_time( 'mysql' ),
			),
			array( '%s', '%s', '%d', '%s', '%s', '%s', '%s', '%d', '%s', '%s' )
		);
		$wpdb->suppress_errors( $an_loi );

		if ( ! $ok ) {
			// UNIQUE KEY khoa: đã có campaign cho kỳ/dịp này.
			return new \WP_Error( 'da_co', __( 'Thư này đã được tạo trước đó.', 'nntm' ) );
		}

		$campaign_id = (int) $wpdb->insert_id;

		foreach ( array_chunk( array_values( $sach ), 500 ) as $lo ) {
			$dong = array();
			$gia  = array();
			foreach ( $lo as list( $user_id, $email ) ) {
				$dong[] = '(%d, %d, %s)';
				array_push( $gia, $campaign_id, $user_id, $email );
			}
			$wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$wpdb->prepare( "INSERT IGNORE INTO {$bang_q} (campaign_id, user_id, email) VALUES " . implode( ',', $dong ), $gia ) // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
			);
		}

		self::hen_gui();

		return $campaign_id;
	}

	/** Đảm bảo có lịch gửi mỗi phút. */
	public static function hen_gui(): void {
		if ( ! wp_next_scheduled( self::CRON_LO ) ) {
			wp_schedule_event( time() + 5, 'nntm_moi_phut', self::CRON_LO );
		}
	}

	/* ---------- Gửi ---------- */

	/**
	 * Gửi một lô. Chạy từ cron mỗi phút; cũng gọi thẳng được với $campaign_id để
	 * gửi ngay thư lẻ (thư thử, xác nhận Khóa Tu).
	 *
	 * @param int $campaign_id 0 = mọi campaign đang gửi.
	 * @return int Số thư đã xử lý.
	 */
	public static function gui_lo( int $campaign_id = 0 ): int {
		global $wpdb;

		// Khoá bằng add_option: nguyên tử ở tầng DB, hai tiến trình không cùng lấy được.
		if ( ! add_option( self::KHOA_CHAY, time(), '', false ) ) {
			$tu = (int) get_option( self::KHOA_CHAY );
			if ( $tu > time() - 5 * MINUTE_IN_SECONDS ) {
				return 0;
			}
			// Tiến trình trước chết giữa chừng — giành lại khoá.
			update_option( self::KHOA_CHAY, time(), false );
		}

		$da_xu_ly = 0;

		try {
			$bang_c   = Schema::table( 'mail_campaign' );
			$bang_q   = Schema::table( 'mail_queue' );
			$gioi_han = max( 1, (int) Ban_Tin::cai_dat()['so_thu_moi_phut'] );

			$loc_campaign = $campaign_id > 0 ? $wpdb->prepare( ' AND q.campaign_id = %d', $campaign_id ) : '';

			$dong = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
				// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- tên bảng do plugin sinh, $loc_campaign đã prepare.
				$wpdb->prepare( "SELECT q.* FROM {$bang_q} q INNER JOIN {$bang_c} c ON c.id = q.campaign_id WHERE q.trang_thai = 'cho' AND c.trang_thai = 'dang_gui'{$loc_campaign} ORDER BY q.id ASC LIMIT %d", $gioi_han )
			);

			$campaigns = array();

			foreach ( (array) $dong as $d ) {
				$cid = (int) $d->campaign_id;
				if ( ! isset( $campaigns[ $cid ] ) ) {
					$campaigns[ $cid ] = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$bang_c} WHERE id = %d", $cid ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				}

				self::gui_mot( $d, $campaigns[ $cid ] );
				++$da_xu_ly;
			}

			foreach ( array_keys( $campaigns ) as $cid ) {
				self::cap_nhat_dem( $cid );
			}

			$con = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$bang_q} q INNER JOIN {$bang_c} c ON c.id = q.campaign_id WHERE q.trang_thai = 'cho' AND c.trang_thai = 'dang_gui'" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.PreparedSQL.InterpolatedNotPrepared

			if ( 0 === $con ) {
				wp_clear_scheduled_hook( self::CRON_LO );
			} else {
				self::hen_gui();
			}
		} finally {
			delete_option( self::KHOA_CHAY );
		}

		return $da_xu_ly;
	}

	/**
	 * Gửi cho một người nhận.
	 *
	 * @param object      $d        Dòng hàng đợi.
	 * @param object|null $campaign Campaign.
	 */
	private static function gui_mot( object $d, ?object $campaign ): void {
		global $wpdb;

		$bang_q = Schema::table( 'mail_queue' );
		$ghi    = static function ( string $trang_thai, ?string $loi = null, int $cong_lan = 1 ) use ( $wpdb, $bang_q, $d ): void {
			$wpdb->update( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
				$bang_q,
				array(
					'trang_thai' => $trang_thai,
					'so_lan'     => (int) $d->so_lan + $cong_lan,
					'loi_msg'    => null === $loi ? null : mb_substr( $loi, 0, 255 ),
					'sent_at'    => current_time( 'mysql' ),
				),
				array( 'id' => (int) $d->id )
			);
		};

		if ( ! $campaign ) {
			$ghi( 'loi', 'campaign không còn' );
			return;
		}

		$hang_loat = in_array( $campaign->loai, self::LOAI_HANG_LOAT, true );
		$user_id   = (int) $d->user_id;

		// Người đã huỷ SAU lúc xếp hàng thì không gửi nữa.
		if ( $hang_loat && $user_id > 0 && '1' !== (string) get_user_meta( $user_id, Ban_Tin::META_NHAN, true ) ) {
			$ghi( 'bo_qua', 'đã huỷ nhận bản tin', 0 );
			return;
		}

		$huy_url = $hang_loat && $user_id > 0 ? Ban_Tin::url_huy( $user_id, (string) $d->email ) : '';
		$ten     = Ban_Tin::ten_nguoi_nhan( $user_id );

		$html    = strtr(
			(string) $campaign->noi_dung,
			array(
				'{{ten}}'     => esc_html( $ten ),
				'{{huy_url}}' => esc_url( $huy_url ),
			)
		);
		$van_ban = strtr(
			(string) $campaign->van_ban,
			array(
				'{{ten}}'     => $ten,
				'{{huy_url}}' => $huy_url,
			)
		);

		if ( 'ghi_log' === $campaign->che_do ) {
			$ghi( 'da_gui', 'ghi_log' );
			return;
		}

		$cai_dat = Ban_Tin::cai_dat();
		$headers = array(
			'Content-Type: text/html; charset=UTF-8',
			sprintf( 'From: %s <%s>', self::ten_header( (string) $cai_dat['ten_gui'] ), sanitize_email( (string) $cai_dat['email_gui'] ) ),
			self::HEADER . ': ' . $campaign->loai,
		);

		if ( '' !== $huy_url ) {
			// Gmail/Yahoo bắt buộc với thư hàng loạt từ 2024 (RFC 8058: huỷ một chạm).
			$headers[] = 'List-Unsubscribe: <' . esc_url_raw( $huy_url ) . '>';
			$headers[] = 'List-Unsubscribe-Post: List-Unsubscribe=One-Click';
		}

		$loi     = '';
		$bat_loi = static function ( $err ) use ( &$loi ): void {
			if ( $err instanceof \WP_Error ) {
				$loi = $err->get_error_message();
			}
		};

		self::$van_ban_dang_gui = $van_ban;
		add_action( 'wp_mail_failed', $bat_loi );
		$gui_duoc = (bool) wp_mail( (string) $d->email, (string) $campaign->tieu_de, $html, $headers );
		remove_action( 'wp_mail_failed', $bat_loi );
		self::$van_ban_dang_gui = null;

		if ( $gui_duoc ) {
			$ghi( 'da_gui' );
		} elseif ( (int) $d->so_lan + 1 < self::SO_LAN_TOI_DA ) {
			$ghi( 'cho', $loi ?: 'wp_mail trả về false' );
		} else {
			$ghi( 'loi', $loi ?: 'wp_mail trả về false' );
		}
	}

	/** Tên người gửi an toàn cho header (bỏ ký tự xuống dòng, bọc ngoặc kép). */
	private static function ten_header( string $ten ): string {
		$ten = trim( str_replace( array( "\r", "\n", '"' ), ' ', $ten ) );

		return '' === $ten ? '' : '"' . $ten . '"';
	}

	/** Đếm lại số đã gửi/lỗi; hết hàng thì đóng campaign. */
	public static function cap_nhat_dem( int $campaign_id ): void {
		global $wpdb;

		$bang_c = Schema::table( 'mail_campaign' );
		$bang_q = Schema::table( 'mail_queue' );

		$dem = $wpdb->get_results( $wpdb->prepare( "SELECT trang_thai, COUNT(*) AS n FROM {$bang_q} WHERE campaign_id = %d GROUP BY trang_thai", $campaign_id ), OBJECT_K ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		$n = static function ( string $k ) use ( $dem ): int {
			return isset( $dem[ $k ] ) ? (int) $dem[ $k ]->n : 0;
		};

		$du_lieu = array(
			'da_gui' => $n( 'da_gui' ),
			'loi'    => $n( 'loi' ),
		);

		if ( 0 === $n( 'cho' ) ) {
			$du_lieu['trang_thai']  = 'xong';
			$du_lieu['finished_at'] = current_time( 'mysql' );
		}

		$wpdb->update( $bang_c, $du_lieu, array( 'id' => $campaign_id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
	}

	/** Dừng một campaign đang gửi. */
	public static function huy_campaign( int $campaign_id ): void {
		global $wpdb;

		$wpdb->update( Schema::table( 'mail_campaign' ), array( 'trang_thai' => 'huy', 'finished_at' => current_time( 'mysql' ) ), array( 'id' => $campaign_id, 'trang_thai' => 'dang_gui' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
	}

	/**
	 * Xoá dòng hàng đợi (có địa chỉ email) của campaign đã xong quá 180 ngày —
	 * giữ lại số đếm trong campaign, không giữ dữ liệu cá nhân lâu hơn cần.
	 */
	public static function don_dep(): void {
		global $wpdb;

		$bang_c = Schema::table( 'mail_campaign' );
		$bang_q = Schema::table( 'mail_queue' );
		$moc    = wp_date( 'Y-m-d H:i:s', time() - 180 * DAY_IN_SECONDS );

		$wpdb->query( $wpdb->prepare( "DELETE q FROM {$bang_q} q INNER JOIN {$bang_c} c ON c.id = q.campaign_id WHERE c.trang_thai <> 'dang_gui' AND c.finished_at IS NOT NULL AND c.finished_at < %s", $moc ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}
}
