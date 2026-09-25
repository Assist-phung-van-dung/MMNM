<?php
/**
 * Bản tin định kỳ + email dịp đặc biệt — Phase 2, khảo sát câu 30–31.
 *
 * - Câu 30: bản tin TỔNG HỢP định kỳ (tuần/tháng). KHÔNG gửi mỗi khi có bài
 *   mới, KHÔNG cho tự chọn chuyên mục theo dõi.
 * - Câu 31: 6–8 thư dịp đặc biệt mỗi năm (Phật Đản, Vu Lan, ngày vía…). Mỗi
 *   dịp là một bài CPT nntm_dip_le, ngày tính theo âm hoặc dương lịch.
 * - Kèm: thư xác nhận đăng ký Khóa Tu (câu 29).
 *
 * Người nhận: thành viên có meta nntm_nhan_ban_tin = '1' (ô "Nhận thông tin của
 * trang" lúc đăng ký, hoặc ô bản tin ở form Cộng Tu).
 *
 * Lịch: một sự kiện WP-Cron mỗi giờ xét xem đã tới kỳ bản tin / ngày lễ chưa;
 * tới thì dựng thư và xếp hàng (Gui_Thu gửi dần mỗi phút).
 *
 * @package NNTM_Core
 */

namespace NNTM\Core;

defined( 'ABSPATH' ) || exit;

/**
 * Nghiệp vụ bản tin.
 */
final class Ban_Tin {

	public const OPTION        = 'nntm_ban_tin_cai_dat';
	public const OPT_KY_CUOI   = 'nntm_ban_tin_ky_cuoi';
	public const OPT_MOC       = 'nntm_ban_tin_moc';
	public const OPT_LAN_KIEM  = 'nntm_ban_tin_lan_kiem';
	public const META_NHAN     = 'nntm_nhan_ban_tin';
	public const CRON_KIEM_TRA = 'nntm_ban_tin_kiem_tra';

	public const CPT_DIP           = 'nntm_dip_le';
	public const META_DIP_LICH     = '_nntm_dip_lich';
	public const META_DIP_NGAY     = '_nntm_dip_ngay';
	public const META_DIP_THANG    = '_nntm_dip_thang';
	public const META_DIP_TIEU_DE  = '_nntm_dip_tieu_de';

	/** Gắn hook. */
	public static function hooks(): void {
		Gui_Thu::hooks();

		add_action( 'init', array( __CLASS__, 'dang_ky_cpt' ) );
		add_action( 'init', array( __CLASS__, 'hen_kiem_tra' ) );
		add_action( self::CRON_KIEM_TRA, array( __CLASS__, 'kiem_tra' ) );
		add_action( 'template_redirect', array( __CLASS__, 'xu_ly_huy' ), 0 );
		add_action( 'nntm_retreat_signup_created', array( __CLASS__, 'gui_xac_nhan_khoa_tu' ), 10, 2 );

		register_deactivation_hook( NNTM_CORE_FILE, array( __CLASS__, 'go_lich' ) );
	}

	/* ---------- Cài đặt ---------- */

	/** @return array<string,mixed> */
	public static function mac_dinh(): array {
		$host = wp_parse_url( home_url(), PHP_URL_HOST );

		return array(
			'tan_suat'          => 'tat',  // tat | tuan | thang — mặc định TẮT, BQT tự bật.
			'thu'               => 7,      // 1 = Thứ Hai … 7 = Chủ Nhật.
			'ngay'              => 1,      // Ngày trong tháng (1–28).
			'gio'               => 7,      // Giờ gửi bản tin.
			'dip_gio'           => 6,      // Giờ gửi thư dịp lễ.
			'loai_bai'          => array( 'post', 'nntm_article', 'nntm_publication', 'nntm_talk', 'nntm_video', 'nntm_retreat' ),
			'so_bai'            => 10,
			'tieu_de'           => '{site} — Bản tin {ky}',
			'loi_mo_dau'        => "Kính gửi {{ten}},\n\nXin gửi đến quý vị những bài viết, pháp thoại và ấn phẩm mới trên trang trong thời gian qua.",
			'ten_gui'           => wp_specialchars_decode( (string) get_bloginfo( 'name' ), ENT_QUOTES ),
			'email_gui'         => 'no-reply@' . ( $host ? preg_replace( '/^www\./', '', (string) $host ) : 'localhost' ),
			'so_thu_moi_phut'   => 40,
			'xac_nhan_khoa_tu'  => 1,
		);
	}

	/** @return array<string,mixed> */
	public static function cai_dat(): array {
		$luu = get_option( self::OPTION, array() );

		return array_merge( self::mac_dinh(), is_array( $luu ) ? $luu : array() );
	}

	/**
	 * Lọc dữ liệu form cài đặt.
	 *
	 * @param array<string,mixed> $raw Dữ liệu thô.
	 * @return array<string,mixed>
	 */
	public static function lam_sach( array $raw ): array {
		$md = self::mac_dinh();
		$so = static function ( $v, int $min, int $max, int $mac ): int {
			$v = is_numeric( $v ) ? (int) $v : $mac;
			return max( $min, min( $max, $v ) );
		};

		$loai = array_values(
			array_intersect(
				array_map( 'sanitize_key', (array) ( $raw['loai_bai'] ?? array() ) ),
				array_keys( self::loai_bai_co_the() )
			)
		);

		$email = sanitize_email( (string) ( $raw['email_gui'] ?? '' ) );

		return array(
			'tan_suat'         => in_array( $raw['tan_suat'] ?? '', array( 'tat', 'tuan', 'thang' ), true ) ? $raw['tan_suat'] : 'tat',
			'thu'              => $so( $raw['thu'] ?? null, 1, 7, $md['thu'] ),
			'ngay'             => $so( $raw['ngay'] ?? null, 1, 28, $md['ngay'] ),
			'gio'              => $so( $raw['gio'] ?? null, 0, 23, $md['gio'] ),
			'dip_gio'          => $so( $raw['dip_gio'] ?? null, 0, 23, $md['dip_gio'] ),
			'loai_bai'         => $loai ? $loai : $md['loai_bai'],
			'so_bai'           => $so( $raw['so_bai'] ?? null, 1, 30, $md['so_bai'] ),
			'tieu_de'          => mb_substr( sanitize_text_field( (string) ( $raw['tieu_de'] ?? '' ) ), 0, 150 ) ?: $md['tieu_de'],
			'loi_mo_dau'       => mb_substr( sanitize_textarea_field( (string) ( $raw['loi_mo_dau'] ?? '' ) ), 0, 1000 ),
			'ten_gui'          => mb_substr( sanitize_text_field( (string) ( $raw['ten_gui'] ?? '' ) ), 0, 80 ) ?: $md['ten_gui'],
			'email_gui'        => is_email( $email ) ? $email : $md['email_gui'],
			'so_thu_moi_phut'  => $so( $raw['so_thu_moi_phut'] ?? null, 1, 500, $md['so_thu_moi_phut'] ),
			'xac_nhan_khoa_tu' => empty( $raw['xac_nhan_khoa_tu'] ) ? 0 : 1,
		);
	}

	/**
	 * Lưu cài đặt. Lần đầu bật (hoặc đổi tần suất) mà giờ gửi của kỳ hiện tại đã
	 * qua thì coi kỳ này là xong — bật vào thứ Ba không được bắn ngay một bản tin
	 * bất ngờ cho "Thứ Hai vừa rồi"; kỳ đầu là kỳ sau. Giờ gửi chưa tới thì vẫn
	 * gửi đúng kỳ này.
	 *
	 * @param array<string,mixed> $moi Cài đặt đã lọc.
	 */
	public static function luu( array $moi ): void {
		$cu = self::cai_dat();
		update_option( self::OPTION, $moi, false );

		if ( 'tat' !== $moi['tan_suat'] && $moi['tan_suat'] !== $cu['tan_suat'] ) {
			$bay_gio = current_datetime();
			if ( self::moc_gui_ky( $moi, $bay_gio ) <= $bay_gio ) {
				update_option( self::OPT_KY_CUOI, self::khoa_ky( $moi['tan_suat'], $bay_gio ), false );
			}
			if ( ! get_option( self::OPT_MOC ) ) {
				update_option( self::OPT_MOC, current_time( 'mysql' ), false );
			}
		}
	}

	/** @return array<string,string> post type => nhãn */
	public static function loai_bai_co_the(): array {
		$ket = array();
		foreach ( self::mac_dinh()['loai_bai'] as $pt ) {
			$obj = get_post_type_object( $pt );
			if ( $obj ) {
				$ket[ $pt ] = (string) $obj->labels->name;
			}
		}

		return $ket;
	}

	/* ---------- CPT dịp đặc biệt ---------- */

	public static function dang_ky_cpt(): void {
		register_post_type(
			self::CPT_DIP,
			array(
				'labels'              => array(
					'name'               => __( 'Dịp đặc biệt', 'nntm' ),
					'singular_name'      => __( 'Dịp đặc biệt', 'nntm' ),
					'add_new'            => __( 'Thêm dịp', 'nntm' ),
					'add_new_item'       => __( 'Thêm dịp đặc biệt', 'nntm' ),
					'edit_item'          => __( 'Sửa thư dịp đặc biệt', 'nntm' ),
					'all_items'          => __( 'Dịp đặc biệt', 'nntm' ),
					'not_found'          => __( 'Chưa có dịp nào', 'nntm' ),
					'featured_image'     => __( 'Ảnh đầu thư', 'nntm' ),
					'set_featured_image' => __( 'Chọn ảnh đầu thư', 'nntm' ),
				),
				'public'              => false,
				'show_ui'             => true,
				'show_in_menu'        => Ban_Tin_Admin::MENU,
				'show_in_admin_bar'   => false,
				'show_in_nav_menus'   => false,
				'exclude_from_search' => true,
				'publicly_queryable'  => false,
				// Trình soạn thảo cổ điển: nội dung là THÂN THƯ, cần HTML đơn giản
				// mà trình đọc thư hiểu được, không phải block của theme.
				'show_in_rest'        => false,
				'supports'            => array( 'title', 'editor', 'thumbnail' ),
				'rewrite'             => false,
				'capability_type'     => 'post',
				'map_meta_cap'        => true,
			)
		);
	}

	/**
	 * Thông tin ngày của một dịp.
	 *
	 * @return array{lich:string,ngay:int,thang:int}
	 */
	public static function ngay_cua_dip( int $post_id ): array {
		$lich = (string) get_post_meta( $post_id, self::META_DIP_LICH, true );

		return array(
			'lich'  => 'duong' === $lich ? 'duong' : 'am',
			'ngay'  => max( 1, min( 30, (int) get_post_meta( $post_id, self::META_DIP_NGAY, true ) ) ),
			'thang' => max( 1, min( 12, (int) get_post_meta( $post_id, self::META_DIP_THANG, true ) ) ),
		);
	}

	/** Ngày dương lịch kế tiếp của dịp, tính từ hôm nay (gồm hôm nay). */
	public static function lan_toi_cua_dip( int $post_id, ?string $tu_ngay = null ): ?string {
		$d       = self::ngay_cua_dip( $post_id );
		$tu_ngay = $tu_ngay ?? current_datetime()->format( 'Y-m-d' );

		if ( 'am' === $d['lich'] ) {
			return Am_Lich::lan_toi( $d['ngay'], $d['thang'], $tu_ngay );
		}

		$nam = (int) substr( $tu_ngay, 0, 4 );
		foreach ( array( $nam, $nam + 1 ) as $n ) {
			if ( checkdate( $d['thang'], $d['ngay'], $n ) ) {
				$ngay = sprintf( '%04d-%02d-%02d', $n, $d['thang'], $d['ngay'] );
				if ( $ngay >= $tu_ngay ) {
					return $ngay;
				}
			}
		}

		return null;
	}

	/* ---------- Lịch kiểm tra ---------- */

	public static function hen_kiem_tra(): void {
		if ( ! wp_next_scheduled( self::CRON_KIEM_TRA ) ) {
			// Đầu mỗi giờ + 2 phút: giờ gửi cài theo giờ tròn.
			$gio_toi = (int) ( ceil( time() / HOUR_IN_SECONDS ) * HOUR_IN_SECONDS ) + 2 * MINUTE_IN_SECONDS;
			wp_schedule_event( $gio_toi, 'hourly', self::CRON_KIEM_TRA );
		}
	}

	public static function go_lich(): void {
		wp_clear_scheduled_hook( self::CRON_KIEM_TRA );
		wp_clear_scheduled_hook( Gui_Thu::CRON_LO );
	}

	/** Chạy mỗi giờ. */
	public static function kiem_tra(): void {
		$bay_gio = current_datetime();

		self::kiem_tra_ban_tin( $bay_gio );
		self::kiem_tra_dip_le( $bay_gio );
		Gui_Thu::don_dep();
	}

	/** Khoá kỳ: 'ban_tin:2026-W39' hoặc 'ban_tin:2026-09'. */
	public static function khoa_ky( string $tan_suat, \DateTimeImmutable $luc ): string {
		return 'ban_tin:' . ( 'thang' === $tan_suat ? $luc->format( 'Y-m' ) : $luc->format( 'o-\WW' ) );
	}

	/** Mốc giờ gửi của kỳ chứa $luc. */
	public static function moc_gui_ky( array $cd, \DateTimeImmutable $luc ): \DateTimeImmutable {
		if ( 'thang' === $cd['tan_suat'] ) {
			return $luc->setDate( (int) $luc->format( 'Y' ), (int) $luc->format( 'n' ), (int) $cd['ngay'] )->setTime( (int) $cd['gio'], 0 );
		}

		return $luc->setISODate( (int) $luc->format( 'o' ), (int) $luc->format( 'W' ), (int) $cd['thu'] )->setTime( (int) $cd['gio'], 0 );
	}

	/** Lần gửi bản tin kế tiếp dự kiến (để hiện trên màn cài đặt). */
	public static function lan_gui_toi(): ?\DateTimeImmutable {
		$cd = self::cai_dat();
		if ( 'tat' === $cd['tan_suat'] ) {
			return null;
		}

		$bay_gio = current_datetime();
		$moc     = self::moc_gui_ky( $cd, $bay_gio );

		if ( $moc <= $bay_gio && get_option( self::OPT_KY_CUOI ) === self::khoa_ky( $cd['tan_suat'], $bay_gio ) ) {
			$sau = 'thang' === $cd['tan_suat'] ? $bay_gio->modify( 'first day of next month' ) : $bay_gio->modify( '+1 week' );
			$moc = self::moc_gui_ky( $cd, $sau );
		}

		return $moc;
	}

	private static function kiem_tra_ban_tin( \DateTimeImmutable $bay_gio ): void {
		$cd = self::cai_dat();
		if ( 'tat' === $cd['tan_suat'] ) {
			return;
		}

		$khoa = self::khoa_ky( $cd['tan_suat'], $bay_gio );
		if ( get_option( self::OPT_KY_CUOI ) === $khoa || $bay_gio < self::moc_gui_ky( $cd, $bay_gio ) ) {
			return;
		}

		$kq = self::tao_ban_tin( $khoa, self::nhan_ky( $cd['tan_suat'], $bay_gio ) );

		// Dù gửi hay bỏ qua (không có bài mới) cũng đánh dấu kỳ này đã xét xong.
		update_option( self::OPT_KY_CUOI, $khoa, false );
		update_option(
			self::OPT_LAN_KIEM,
			array(
				'luc'  => current_time( 'mysql' ),
				'khoa' => $khoa,
				'kq'   => is_wp_error( $kq ) ? $kq->get_error_message() : sprintf( 'campaign #%d', $kq ),
			),
			false
		);
	}

	/** Nhãn kỳ hiển thị trong thư. */
	public static function nhan_ky( string $tan_suat, \DateTimeImmutable $luc ): string {
		if ( 'thang' === $tan_suat ) {
			/* translators: %s: tháng/năm */
			return sprintf( __( 'tháng %s', 'nntm' ), $luc->format( 'n/Y' ) );
		}

		$dau = $luc->setISODate( (int) $luc->format( 'o' ), (int) $luc->format( 'W' ), 1 );

		return sprintf( __( 'tuần %1$s – %2$s', 'nntm' ), $dau->format( 'd/m' ), $dau->modify( '+6 days' )->format( 'd/m/Y' ) );
	}

	/**
	 * Dựng và xếp hàng một bản tin gồm bài đăng từ mốc lần trước tới giờ.
	 *
	 * @param string $khoa Khoá campaign.
	 * @param string $ky   Nhãn kỳ.
	 * @return int|\WP_Error
	 */
	public static function tao_ban_tin( string $khoa, string $ky ) {
		$ban = self::dung_ban_tin( $ky );
		if ( is_wp_error( $ban ) ) {
			return $ban;
		}

		$id = Gui_Thu::tao( 'ban_tin', $khoa, 0, $ban['tieu_de'], $ban['html'], $ban['van_ban'], self::nguoi_nhan() );

		if ( ! is_wp_error( $id ) ) {
			update_option( self::OPT_MOC, $ban['den'], false );
		}

		return $id;
	}

	/**
	 * Nội dung bản tin kỳ hiện tại (dùng cho cả gửi thật lẫn xem trước).
	 *
	 * @return array{tieu_de:string,html:string,van_ban:string,den:string,so_bai:int}|\WP_Error
	 */
	public static function dung_ban_tin( string $ky ) {
		$cd  = self::cai_dat();
		$den = current_time( 'mysql' );
		$tu  = (string) get_option( self::OPT_MOC, '' );

		if ( '' === $tu ) {
			$so_ngay = 'thang' === $cd['tan_suat'] ? 31 : 7;
			$tu      = wp_date( 'Y-m-d H:i:s', time() - $so_ngay * DAY_IN_SECONDS );
		}

		$bai = Noi_Dung_Thu::bai_moi( $tu, $den, (array) $cd['loai_bai'], (int) $cd['so_bai'] );
		if ( ! $bai ) {
			/* translators: %s: thời điểm */
			return new \WP_Error( 'khong_co_bai', sprintf( __( 'Không có bài công khai mới kể từ %s — bỏ qua kỳ này.', 'nntm' ), mysql2date( 'd/m/Y H:i', $tu ) ) );
		}

		return Noi_Dung_Thu::ban_tin( $bai, $cd, $ky ) + array(
			'den'    => $den,
			'so_bai' => count( $bai ),
		);
	}

	private static function kiem_tra_dip_le( \DateTimeImmutable $bay_gio ): void {
		$cd = self::cai_dat();
		if ( (int) $bay_gio->format( 'G' ) < (int) $cd['dip_gio'] ) {
			return;
		}

		$hom_nay = $bay_gio->format( 'Y-m-d' );
		$ids     = get_posts(
			array(
				'post_type'        => self::CPT_DIP,
				'post_status'      => 'publish',
				'posts_per_page'   => 100,
				'fields'           => 'ids',
				'no_found_rows'    => true,
				'suppress_filters' => true,
			)
		);

		foreach ( $ids as $id ) {
			if ( self::lan_toi_cua_dip( (int) $id, $hom_nay ) === $hom_nay ) {
				self::tao_dip_le( (int) $id, 'dip_le:' . $id . ':' . $hom_nay );
			}
		}
	}

	/** @return int|\WP_Error */
	public static function tao_dip_le( int $dip_id, string $khoa ) {
		$dip = get_post( $dip_id );
		if ( ! $dip || self::CPT_DIP !== $dip->post_type ) {
			return new \WP_Error( 'khong_co', __( 'Không tìm thấy dịp.', 'nntm' ) );
		}

		$thu = Noi_Dung_Thu::dip_le( $dip );

		return Gui_Thu::tao( 'dip_le', $khoa, $dip_id, $thu['tieu_de'], $thu['html'], $thu['van_ban'], self::nguoi_nhan() );
	}

	/* ---------- Gửi thử ---------- */

	/**
	 * Gửi một thư thử cho người đang đăng nhập, gửi NGAY (không chờ cron).
	 *
	 * @param string $loai ban_tin | dip_le.
	 * @param int    $id   ID dịp (khi loai = dip_le).
	 * @return int|\WP_Error ID campaign.
	 */
	public static function gui_thu_cho_toi( string $loai, int $id = 0 ) {
		$user = wp_get_current_user();
		if ( ! $user->exists() || ! is_email( $user->user_email ) ) {
			return new \WP_Error( 'khong_co_email', __( 'Tài khoản của bạn chưa có email hợp lệ.', 'nntm' ) );
		}

		if ( 'dip_le' === $loai ) {
			$dip = get_post( $id );
			if ( ! $dip || self::CPT_DIP !== $dip->post_type ) {
				return new \WP_Error( 'khong_co', __( 'Không tìm thấy dịp.', 'nntm' ) );
			}
			$thu = Noi_Dung_Thu::dip_le( $dip );
		} else {
			$cd  = self::cai_dat();
			$thu = self::dung_ban_tin( self::nhan_ky( 'tat' === $cd['tan_suat'] ? 'tuan' : $cd['tan_suat'], current_datetime() ) );
			if ( is_wp_error( $thu ) ) {
				return $thu;
			}
		}

		$cid = Gui_Thu::tao(
			'thu',
			'thu:' . $loai . ':' . $user->ID . ':' . microtime( true ),
			$id,
			'[THỬ] ' . $thu['tieu_de'],
			// Thư thử vẫn có chân "huỷ nhận" để xem đúng bố cục, nhưng link là '#'.
			str_replace( '{{huy_url}}', '#', $thu['html'] ),
			str_replace( '{{huy_url}}', '#', $thu['van_ban'] ),
			array( array( 'user_id' => $user->ID, 'email' => $user->user_email ) )
		);

		if ( ! is_wp_error( $cid ) ) {
			Gui_Thu::gui_lo( $cid );
		}

		return $cid;
	}

	/* ---------- Khóa Tu ---------- */

	/**
	 * Thư xác nhận đã nhận đăng ký Khóa Tu. Theme gọi
	 * do_action( 'nntm_retreat_signup_created', $signup_id, $du_lieu ).
	 *
	 * @param int                  $signup_id ID dòng đăng ký.
	 * @param array<string,mixed>  $dk        retreat_id, full_name, phone, email, note.
	 */
	public static function gui_xac_nhan_khoa_tu( int $signup_id, array $dk ): void {
		if ( empty( self::cai_dat()['xac_nhan_khoa_tu'] ) ) {
			return;
		}

		$khoa_tu = get_post( (int) ( $dk['retreat_id'] ?? 0 ) );
		$email   = sanitize_email( (string) ( $dk['email'] ?? '' ) );
		if ( ! $khoa_tu || ! is_email( $email ) ) {
			return;
		}

		$thu = Noi_Dung_Thu::xac_nhan_khoa_tu(
			$khoa_tu,
			array_map( 'strval', array_intersect_key( $dk, array_flip( array( 'full_name', 'phone', 'email', 'note' ) ) ) )
		);

		// Không gửi ngay trong request đăng ký: SMTP chậm sẽ bắt người dùng chờ.
		// Cron mỗi phút gửi đi.
		Gui_Thu::tao(
			'khoa_tu',
			'khoa_tu:' . $signup_id,
			$signup_id,
			$thu['tieu_de'],
			$thu['html'],
			$thu['van_ban'],
			array( array( 'user_id' => (int) ( $dk['user_id'] ?? 0 ), 'email' => $email ) )
		);
	}

	/* ---------- Người nhận ---------- */

	/** @return array<int,array{user_id:int,email:string}> */
	public static function nguoi_nhan(): array {
		global $wpdb;

		$dong = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$wpdb->prepare(
				"SELECT u.ID AS user_id, u.user_email AS email FROM {$wpdb->users} u INNER JOIN {$wpdb->usermeta} m ON m.user_id = u.ID AND m.meta_key = %s AND m.meta_value = '1' ORDER BY u.ID",
				self::META_NHAN
			),
			ARRAY_A
		);

		return (array) apply_filters( 'nntm_ban_tin_nguoi_nhan', is_array( $dong ) ? $dong : array() );
	}

	public static function so_nguoi_nhan(): int {
		return count( self::nguoi_nhan() );
	}

	/** Tên hiển thị trong thư: pháp danh, không có thì tên hiển thị. */
	public static function ten_nguoi_nhan( int $user_id ): string {
		if ( $user_id > 0 ) {
			$phap_danh = trim( (string) get_user_meta( $user_id, 'nntm_phap_danh', true ) );
			if ( '' !== $phap_danh ) {
				return $phap_danh;
			}
			$u = get_userdata( $user_id );
			if ( $u && '' !== trim( (string) $u->display_name ) ) {
				return (string) $u->display_name;
			}
		}

		return __( 'quý đạo hữu', 'nntm' );
	}

	/* ---------- Huỷ nhận bản tin ---------- */

	/** Chữ ký của link huỷ. Gắn với email: đổi email thì link cũ hết hiệu lực. */
	public static function chu_ky( int $user_id, string $email ): string {
		return substr( hash_hmac( 'sha256', 'nntm-huy-ban-tin|' . $user_id . '|' . strtolower( $email ), wp_salt( 'auth' ) ), 0, 32 );
	}

	public static function url_huy( int $user_id, string $email ): string {
		return add_query_arg(
			array(
				'nntm_ban_tin' => 'huy',
				'u'            => $user_id,
				't'            => self::chu_ky( $user_id, $email ),
			),
			home_url( '/' )
		);
	}

	/**
	 * Trang huỷ nhận bản tin.
	 *
	 * GET chỉ hiện nút xác nhận — trình quét link của hộp thư (Outlook, antivirus)
	 * tự mở mọi link trong thư, huỷ ngay bằng GET là huỷ nhầm hàng loạt. Huỷ thật
	 * bằng POST: nút trên trang, hoặc "huỷ một chạm" của Gmail (RFC 8058).
	 */
	public static function xu_ly_huy(): void {
		// phpcs:disable WordPress.Security.NonceVerification -- chữ ký HMAC trong link thay cho nonce.
		if ( ! isset( $_GET['nntm_ban_tin'] ) || 'huy' !== $_GET['nntm_ban_tin'] ) {
			return;
		}

		$user_id = isset( $_GET['u'] ) ? absint( $_GET['u'] ) : 0;
		$token   = isset( $_GET['t'] ) ? sanitize_key( wp_unslash( $_GET['t'] ) ) : '';
		$user    = $user_id > 0 ? get_userdata( $user_id ) : false;
		$hop_le  = $user && hash_equals( self::chu_ky( $user_id, (string) $user->user_email ), $token );

		$la_post  = isset( $_SERVER['REQUEST_METHOD'] ) && 'POST' === $_SERVER['REQUEST_METHOD'];
		$mot_cham = $la_post && isset( $_POST['List-Unsubscribe'] ) && 'One-Click' === $_POST['List-Unsubscribe'];
		$hanh_dong = $la_post && isset( $_POST['hanh_dong'] ) ? sanitize_key( wp_unslash( $_POST['hanh_dong'] ) ) : '';
		// phpcs:enable

		$trang_thai = 'hoi';
		if ( ! $hop_le ) {
			$trang_thai = 'sai';
		} elseif ( $mot_cham || 'huy' === $hanh_dong ) {
			update_user_meta( $user_id, self::META_NHAN, '0' );
			$trang_thai = 'da_huy';
		} elseif ( 'dang_ky_lai' === $hanh_dong ) {
			update_user_meta( $user_id, self::META_NHAN, '1' );
			$trang_thai = 'da_dang_ky_lai';
		}

		if ( $mot_cham ) {
			status_header( $hop_le ? 200 : 400 );
			header( 'Content-Type: text/plain; charset=UTF-8' );
			echo $hop_le ? 'OK' : 'Invalid'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			exit;
		}

		nocache_headers();
		status_header( $hop_le ? 200 : 400 );
		add_filter( 'wp_robots', 'wp_robots_no_robots' );
		add_filter(
			'pre_get_document_title',
			static function (): string {
				return __( 'Huỷ nhận bản tin', 'nntm' ) . ' – ' . Noi_Dung_Thu::ten_site();
			}
		);

		self::ve_trang_huy( $trang_thai, $hop_le ? (string) $user->user_email : '' );
		exit;
	}

	private static function ve_trang_huy( string $trang_thai, string $email ): void {
		$noi_dung = array(
			'hoi'            => array( __( 'Huỷ nhận bản tin', 'nntm' ), sprintf( __( 'Quý vị muốn ngừng nhận bản tin định kỳ và thư các dịp lễ gửi tới %s?', 'nntm' ), $email ), 'huy', __( 'Xác nhận huỷ', 'nntm' ) ),
			'da_huy'         => array( __( 'Đã huỷ nhận bản tin', 'nntm' ), sprintf( __( '%s sẽ không nhận bản tin nữa. Thư về tài khoản và đăng ký Khóa Tu vẫn được gửi bình thường.', 'nntm' ), $email ), 'dang_ky_lai', __( 'Tôi bấm nhầm — nhận lại bản tin', 'nntm' ) ),
			'da_dang_ky_lai' => array( __( 'Đã nhận lại bản tin', 'nntm' ), sprintf( __( 'Bản tin sẽ tiếp tục gửi tới %s.', 'nntm' ), $email ), '', '' ),
			'sai'            => array( __( 'Liên kết không hợp lệ', 'nntm' ), __( 'Liên kết đã hết hiệu lực hoặc bị cắt ngắn. Quý vị vui lòng dùng liên kết trong thư mới nhất.', 'nntm' ), '', '' ),
		);

		list( $tieu_de, $loi, $hanh_dong, $nut ) = $noi_dung[ $trang_thai ];

		get_header();
		?>
		<main id="nntm-noi-dung-chinh" class="nntm-container nntm-mt-8 nntm-mb-8">
			<div style="max-width:560px;margin:0 auto;padding:48px 16px;text-align:center;">
				<h1 style="font-size:32px;line-height:1.3;margin:0 0 16px;"><?php echo esc_html( $tieu_de ); ?></h1>
				<p style="font-size:18px;line-height:1.6;margin:0 0 24px;"><?php echo esc_html( $loi ); ?></p>
				<?php if ( '' !== $hanh_dong ) : ?>
					<form method="post">
						<input type="hidden" name="hanh_dong" value="<?php echo esc_attr( $hanh_dong ); ?>" />
						<button type="submit" class="nntm-auth-btn nntm-auth-btn--dac"><?php echo esc_html( $nut ); ?></button>
					</form>
				<?php endif; ?>
				<p style="margin-top:24px;"><a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Về trang chủ', 'nntm' ); ?></a></p>
			</div>
		</main>
		<?php
		get_footer();
	}
}
