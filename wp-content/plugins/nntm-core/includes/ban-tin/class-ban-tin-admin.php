<?php
/**
 * Màn quản trị "Bản tin & Email": cài đặt, dịp đặc biệt, nhật ký gửi.
 *
 * Khách tự vận hành, không có người kỹ thuật (khảo sát câu 37, 39) → mọi thao
 * tác phải làm được bằng nút bấm: xem trước, gửi thử cho mình, gửi ngay, dừng.
 *
 * @package NNTM_Core
 */

namespace NNTM\Core;

defined( 'ABSPATH' ) || exit;

/**
 * Giao diện quản trị bản tin.
 */
final class Ban_Tin_Admin {

	public const MENU      = 'nntm-ban-tin';
	public const NHAT_KY   = 'nntm-ban-tin-nhat-ky';
	private const QUYEN    = 'manage_options';

	public static function hooks(): void {
		// Ưu tiên 9: menu cha phải có trước khi WP gắn menu con của CPT (ưu tiên 10).
		add_action( 'admin_menu', array( __CLASS__, 'menu' ), 9 );

		add_action( 'admin_post_nntm_ban_tin_luu', array( __CLASS__, 'xu_ly_luu' ) );
		add_action( 'admin_post_nntm_ban_tin_xem', array( __CLASS__, 'xu_ly_xem' ) );
		add_action( 'admin_post_nntm_ban_tin_thu', array( __CLASS__, 'xu_ly_thu' ) );
		add_action( 'admin_post_nntm_ban_tin_gui_ngay', array( __CLASS__, 'xu_ly_gui_ngay' ) );
		add_action( 'admin_post_nntm_ban_tin_dung', array( __CLASS__, 'xu_ly_dung' ) );

		add_action( 'add_meta_boxes_' . Ban_Tin::CPT_DIP, array( __CLASS__, 'them_meta_box' ) );
		add_action( 'save_post_' . Ban_Tin::CPT_DIP, array( __CLASS__, 'luu_meta_box' ) );
		add_filter( 'manage_' . Ban_Tin::CPT_DIP . '_posts_columns', array( __CLASS__, 'cot_dip' ) );
		add_action( 'manage_' . Ban_Tin::CPT_DIP . '_posts_custom_column', array( __CLASS__, 've_cot_dip' ), 10, 2 );
		add_action( 'admin_notices', array( __CLASS__, 'thong_bao' ) );
	}

	public static function menu(): void {
		add_menu_page(
			__( 'Bản tin & Email', 'nntm' ),
			__( 'Bản tin & Email', 'nntm' ),
			self::QUYEN,
			self::MENU,
			array( __CLASS__, 've_cai_dat' ),
			'dashicons-email-alt',
			41
		);
		add_submenu_page( self::MENU, __( 'Cài đặt bản tin', 'nntm' ), __( 'Cài đặt bản tin', 'nntm' ), self::QUYEN, self::MENU, array( __CLASS__, 've_cai_dat' ) );
		add_submenu_page( self::MENU, __( 'Nhật ký gửi', 'nntm' ), __( 'Nhật ký gửi', 'nntm' ), self::QUYEN, self::NHAT_KY, array( __CLASS__, 've_nhat_ky' ) );
	}

	/* ---------- Tiện ích ---------- */

	private static function url_hanh_dong( string $action, array $them = array() ): string {
		return wp_nonce_url( add_query_arg( array_merge( array( 'action' => $action ), $them ), admin_url( 'admin-post.php' ) ), $action );
	}

	/** Quay lại trang trước kèm mã thông báo. */
	private static function quay_lai( string $ma, string $chi_tiet = '', string $den = '' ): void {
		$den = $den ?: ( wp_get_referer() ?: admin_url( 'admin.php?page=' . self::MENU ) );
		wp_safe_redirect( add_query_arg( array( 'nntm_bt' => $ma, 'nntm_bt_ct' => rawurlencode( $chi_tiet ) ), $den ) );
		exit;
	}

	public static function thong_bao(): void {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- chỉ đọc mã thông báo để hiển thị.
		if ( empty( $_GET['nntm_bt'] ) ) {
			return;
		}
		$ma       = sanitize_key( wp_unslash( $_GET['nntm_bt'] ) );
		$chi_tiet = isset( $_GET['nntm_bt_ct'] ) ? sanitize_text_field( rawurldecode( wp_unslash( $_GET['nntm_bt_ct'] ) ) ) : '';
		// phpcs:enable

		$ghi_log = 'ghi_log' === Gui_Thu::che_do();
		$bang    = array(
			'da_luu'   => array( 'success', __( 'Đã lưu cài đặt bản tin.', 'nntm' ) ),
			'thu_ok'   => array( 'success', $ghi_log ? __( 'Đã dựng thư thử và ghi vào Nhật ký gửi (chế độ ghi log — chưa gửi thật).', 'nntm' ) : __( 'Đã gửi thư thử tới email của bạn.', 'nntm' ) ),
			'gui_ok'   => array( 'success', __( 'Đã xếp hàng bản tin. Thư được gửi dần mỗi phút — theo dõi ở Nhật ký gửi.', 'nntm' ) ),
			'da_dung'  => array( 'warning', __( 'Đã dừng gửi. Những thư đã đi thì không thu hồi được.', 'nntm' ) ),
			'loi'      => array( 'error', __( 'Không thực hiện được:', 'nntm' ) ),
		);

		if ( ! isset( $bang[ $ma ] ) ) {
			return;
		}

		printf(
			'<div class="notice notice-%1$s is-dismissible"><p>%2$s %3$s</p></div>',
			esc_attr( $bang[ $ma ][0] ),
			esc_html( $bang[ $ma ][1] ),
			esc_html( $chi_tiet )
		);
	}

	/* ---------- Trang cài đặt ---------- */

	public static function ve_cai_dat(): void {
		if ( ! current_user_can( self::QUYEN ) ) {
			return;
		}

		$cd       = Ban_Tin::cai_dat();
		$so_nhan  = Ban_Tin::so_nguoi_nhan();
		$lan_toi  = Ban_Tin::lan_gui_toi();
		$lan_kiem = get_option( Ban_Tin::OPT_LAN_KIEM );
		$ghi_log  = 'ghi_log' === Gui_Thu::che_do();
		$thu_ten  = array( 1 => 'Thứ Hai', 'Thứ Ba', 'Thứ Tư', 'Thứ Năm', 'Thứ Sáu', 'Thứ Bảy', 'Chủ Nhật' );
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Bản tin & Email', 'nntm' ); ?></h1>

			<?php if ( $ghi_log ) : ?>
				<div class="notice notice-warning inline"><p>
					<strong><?php esc_html_e( 'Chế độ ghi log — thư KHÔNG được gửi đi.', 'nntm' ); ?></strong>
					<?php esc_html_e( 'Mọi thư vẫn được dựng và ghi vào Nhật ký gửi để xem trước. Muốn gửi thật: khai báo máy chủ SMTP (Amazon SES / SendGrid) trong wp-config.php — xem docs/14-ban-tin-email.md.', 'nntm' ); ?>
				</p></div>
			<?php endif; ?>

			<table class="widefat striped" style="max-width:860px;margin:16px 0">
				<tbody>
					<tr><th style="width:240px"><?php esc_html_e( 'Đường gửi', 'nntm' ); ?></th><td><?php echo esc_html( Gui_Thu::mo_ta_duong_gui() ); ?></td></tr>
					<tr><th><?php esc_html_e( 'Người nhận bản tin', 'nntm' ); ?></th><td><?php echo esc_html( sprintf( _n( '%d thành viên', '%d thành viên', $so_nhan, 'nntm' ), $so_nhan ) ); ?></td></tr>
					<tr><th><?php esc_html_e( 'Bản tin kế tiếp', 'nntm' ); ?></th><td><?php echo $lan_toi ? esc_html( wp_date( 'H:i', $lan_toi->getTimestamp() ) . ' — ' . wp_date( 'd/m/Y', $lan_toi->getTimestamp() ) ) : esc_html__( 'Đang tắt', 'nntm' ); ?></td></tr>
					<?php if ( is_array( $lan_kiem ) ) : ?>
						<tr><th><?php esc_html_e( 'Kỳ xét gần nhất', 'nntm' ); ?></th><td><?php echo esc_html( mysql2date( 'd/m/Y H:i', (string) $lan_kiem['luc'] ) . ' — ' . $lan_kiem['khoa'] . ': ' . $lan_kiem['kq'] ); ?></td></tr>
					<?php endif; ?>
					<?php if ( defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON ) : ?>
						<tr><th><?php esc_html_e( 'Lịch chạy', 'nntm' ); ?></th><td><?php esc_html_e( 'WP-Cron nội bộ đang tắt — máy chủ phải có cron gọi wp-cron.php mỗi phút, nếu không thư sẽ không đi.', 'nntm' ); ?></td></tr>
					<?php endif; ?>
				</tbody>
			</table>

			<p>
				<a class="button" target="_blank" rel="noopener" href="<?php echo esc_url( self::url_hanh_dong( 'nntm_ban_tin_xem', array( 'loai' => 'ban_tin' ) ) ); ?>"><?php esc_html_e( 'Xem trước bản tin kỳ này', 'nntm' ); ?></a>
				<a class="button" href="<?php echo esc_url( self::url_hanh_dong( 'nntm_ban_tin_thu', array( 'loai' => 'ban_tin' ) ) ); ?>"><?php esc_html_e( 'Gửi thử cho tôi', 'nntm' ); ?></a>
				<a class="button button-secondary" href="<?php echo esc_url( self::url_hanh_dong( 'nntm_ban_tin_gui_ngay' ) ); ?>"
					onclick="return confirm(<?php echo esc_attr( wp_json_encode( sprintf( __( 'Gửi bản tin ngay tới %d thành viên?', 'nntm' ), $so_nhan ) ) ); ?>);"><?php esc_html_e( 'Gửi bản tin ngay', 'nntm' ); ?></a>
			</p>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="nntm_ban_tin_luu" />
				<?php wp_nonce_field( 'nntm_ban_tin_luu' ); ?>

				<h2><?php esc_html_e( 'Bản tin định kỳ', 'nntm' ); ?></h2>
				<p class="description"><?php esc_html_e( 'Tổng hợp các bài công khai mới đăng kể từ kỳ trước. Kỳ nào không có bài mới thì không gửi. Bài khu Hành Giả không bao giờ nằm trong thư.', 'nntm' ); ?></p>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php esc_html_e( 'Tần suất', 'nntm' ); ?></th>
						<td>
							<?php foreach ( array( 'tat' => __( 'Tắt', 'nntm' ), 'tuan' => __( 'Hằng tuần', 'nntm' ), 'thang' => __( 'Hằng tháng', 'nntm' ) ) as $k => $nhan ) : ?>
								<label style="margin-right:16px"><input type="radio" name="tan_suat" value="<?php echo esc_attr( $k ); ?>" <?php checked( $cd['tan_suat'], $k ); ?> /> <?php echo esc_html( $nhan ); ?></label>
							<?php endforeach; ?>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Ngày gửi', 'nntm' ); ?></th>
						<td>
							<label><?php esc_html_e( 'Hằng tuần vào', 'nntm' ); ?>
								<select name="thu"><?php foreach ( $thu_ten as $k => $nhan ) : ?><option value="<?php echo esc_attr( (string) $k ); ?>" <?php selected( (int) $cd['thu'], $k ); ?>><?php echo esc_html( $nhan ); ?></option><?php endforeach; ?></select>
							</label>
							&nbsp;·&nbsp;
							<label><?php esc_html_e( 'Hằng tháng vào ngày', 'nntm' ); ?>
								<select name="ngay"><?php for ( $i = 1; $i <= 28; $i++ ) : ?><option value="<?php echo esc_attr( (string) $i ); ?>" <?php selected( (int) $cd['ngay'], $i ); ?>><?php echo esc_html( (string) $i ); ?></option><?php endfor; ?></select>
							</label>
							&nbsp;·&nbsp;
							<label><?php esc_html_e( 'lúc', 'nntm' ); ?>
								<select name="gio"><?php for ( $i = 0; $i <= 23; $i++ ) : ?><option value="<?php echo esc_attr( (string) $i ); ?>" <?php selected( (int) $cd['gio'], $i ); ?>><?php echo esc_html( sprintf( '%02d:00', $i ) ); ?></option><?php endfor; ?></select>
							</label>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Loại nội dung', 'nntm' ); ?></th>
						<td>
							<?php foreach ( Ban_Tin::loai_bai_co_the() as $pt => $nhan ) : ?>
								<label style="display:inline-block;margin:0 16px 6px 0"><input type="checkbox" name="loai_bai[]" value="<?php echo esc_attr( $pt ); ?>" <?php checked( in_array( $pt, (array) $cd['loai_bai'], true ) ); ?> /> <?php echo esc_html( $nhan ); ?></label>
							<?php endforeach; ?>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="nntm-bt-so-bai"><?php esc_html_e( 'Số bài tối đa', 'nntm' ); ?></label></th>
						<td><input id="nntm-bt-so-bai" type="number" min="1" max="30" name="so_bai" value="<?php echo esc_attr( (string) $cd['so_bai'] ); ?>" class="small-text" /></td>
					</tr>
					<tr>
						<th scope="row"><label for="nntm-bt-tieu-de"><?php esc_html_e( 'Tiêu đề thư', 'nntm' ); ?></label></th>
						<td>
							<input id="nntm-bt-tieu-de" type="text" name="tieu_de" value="<?php echo esc_attr( (string) $cd['tieu_de'] ); ?>" class="large-text" />
							<p class="description"><?php esc_html_e( '{site} = tên trang, {ky} = kỳ bản tin (ví dụ "tuần 21/09 – 27/09/2026").', 'nntm' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="nntm-bt-mo-dau"><?php esc_html_e( 'Lời mở đầu', 'nntm' ); ?></label></th>
						<td>
							<textarea id="nntm-bt-mo-dau" name="loi_mo_dau" rows="4" class="large-text"><?php echo esc_textarea( (string) $cd['loi_mo_dau'] ); ?></textarea>
							<p class="description"><?php esc_html_e( '{{ten}} = pháp danh người nhận.', 'nntm' ); ?></p>
						</td>
					</tr>
				</table>

				<h2><?php esc_html_e( 'Thư dịp đặc biệt', 'nntm' ); ?></h2>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php esc_html_e( 'Giờ gửi trong ngày lễ', 'nntm' ); ?></th>
						<td>
							<select name="dip_gio"><?php for ( $i = 0; $i <= 23; $i++ ) : ?><option value="<?php echo esc_attr( (string) $i ); ?>" <?php selected( (int) $cd['dip_gio'], $i ); ?>><?php echo esc_html( sprintf( '%02d:00', $i ) ); ?></option><?php endfor; ?></select>
							<p class="description">
								<?php
								printf(
									/* translators: %s: liên kết tới danh sách dịp */
									esc_html__( 'Nội dung từng dịp soạn ở %s. Chỉ dịp ở trạng thái "Đã đăng" mới được gửi.', 'nntm' ),
									'<a href="' . esc_url( admin_url( 'edit.php?post_type=' . Ban_Tin::CPT_DIP ) ) . '">' . esc_html__( 'Dịp đặc biệt', 'nntm' ) . '</a>'
								);
								?>
							</p>
						</td>
					</tr>
				</table>

				<h2><?php esc_html_e( 'Người gửi & tốc độ', 'nntm' ); ?></h2>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><label for="nntm-bt-ten-gui"><?php esc_html_e( 'Tên người gửi', 'nntm' ); ?></label></th>
						<td><input id="nntm-bt-ten-gui" type="text" name="ten_gui" value="<?php echo esc_attr( (string) $cd['ten_gui'] ); ?>" class="regular-text" /></td>
					</tr>
					<tr>
						<th scope="row"><label for="nntm-bt-email-gui"><?php esc_html_e( 'Email người gửi', 'nntm' ); ?></label></th>
						<td>
							<input id="nntm-bt-email-gui" type="email" name="email_gui" value="<?php echo esc_attr( (string) $cd['email_gui'] ); ?>" class="regular-text" />
							<p class="description"><?php esc_html_e( 'Phải thuộc tên miền đã xác thực với SES/SendGrid, nếu không thư bị từ chối hoặc vào thư rác.', 'nntm' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="nntm-bt-toc-do"><?php esc_html_e( 'Số thư mỗi phút', 'nntm' ); ?></label></th>
						<td>
							<input id="nntm-bt-toc-do" type="number" min="1" max="500" name="so_thu_moi_phut" value="<?php echo esc_attr( (string) $cd['so_thu_moi_phut'] ); ?>" class="small-text" />
							<p class="description"><?php esc_html_e( 'Tài khoản SES mới bị giới hạn khoảng 1 thư/giây; để 40 là an toàn.', 'nntm' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Khóa Tu', 'nntm' ); ?></th>
						<td><label><input type="checkbox" name="xac_nhan_khoa_tu" value="1" <?php checked( ! empty( $cd['xac_nhan_khoa_tu'] ) ); ?> /> <?php esc_html_e( 'Gửi thư xác nhận "đã nhận đăng ký" cho người đăng ký Khóa Tu', 'nntm' ); ?></label></td>
					</tr>
				</table>

				<?php submit_button( __( 'Lưu cài đặt', 'nntm' ) ); ?>
			</form>
		</div>
		<?php
	}

	public static function xu_ly_luu(): void {
		if ( ! current_user_can( self::QUYEN ) ) {
			wp_die( esc_html__( 'Bạn không có quyền.', 'nntm' ), 403 );
		}
		check_admin_referer( 'nntm_ban_tin_luu' );

		Ban_Tin::luu( Ban_Tin::lam_sach( wp_unslash( $_POST ) ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- lọc trong lam_sach().

		self::quay_lai( 'da_luu' );
	}

	/* ---------- Xem trước / gửi thử / gửi ngay / dừng ---------- */

	public static function xu_ly_xem(): void {
		check_admin_referer( 'nntm_ban_tin_xem' );

		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- đã check_admin_referer ở trên.
		$loai = isset( $_GET['loai'] ) ? sanitize_key( wp_unslash( $_GET['loai'] ) ) : '';
		$id   = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
		// phpcs:enable

		$html = '';

		if ( 'dip_le' === $loai ) {
			$dip = get_post( $id );
			if ( ! $dip || Ban_Tin::CPT_DIP !== $dip->post_type || ! current_user_can( 'edit_post', $id ) ) {
				wp_die( esc_html__( 'Không xem được dịp này.', 'nntm' ), 403 );
			}
			$html = Noi_Dung_Thu::dip_le( $dip )['html'];
		} elseif ( 'campaign' === $loai ) {
			if ( ! current_user_can( self::QUYEN ) ) {
				wp_die( esc_html__( 'Bạn không có quyền.', 'nntm' ), 403 );
			}
			global $wpdb;
			$html = (string) $wpdb->get_var( $wpdb->prepare( 'SELECT noi_dung FROM ' . Schema::table( 'mail_campaign' ) . ' WHERE id = %d', $id ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.PreparedSQL.NotPrepared
		} else {
			if ( ! current_user_can( self::QUYEN ) ) {
				wp_die( esc_html__( 'Bạn không có quyền.', 'nntm' ), 403 );
			}
			$cd  = Ban_Tin::cai_dat();
			$thu = Ban_Tin::dung_ban_tin( Ban_Tin::nhan_ky( 'tat' === $cd['tan_suat'] ? 'tuan' : $cd['tan_suat'], current_datetime() ) );
			if ( is_wp_error( $thu ) ) {
				wp_die( esc_html( $thu->get_error_message() ), esc_html__( 'Xem trước bản tin', 'nntm' ), array( 'response' => 200, 'back_link' => true ) );
			}
			$html = $thu['html'];
		}

		if ( '' === $html ) {
			wp_die( esc_html__( 'Không có nội dung.', 'nntm' ), 404 );
		}

		nocache_headers();
		header( 'Content-Type: text/html; charset=UTF-8' );
		// HTML do chính plugin dựng từ dữ liệu đã lọc (esc_* / wp_kses_post) — in nguyên văn để xem đúng như thư.
		echo strtr( $html, array( '{{ten}}' => esc_html( Ban_Tin::ten_nguoi_nhan( get_current_user_id() ) ), '{{huy_url}}' => '#' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		exit;
	}

	public static function xu_ly_thu(): void {
		check_admin_referer( 'nntm_ban_tin_thu' );

		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$loai = isset( $_GET['loai'] ) && 'dip_le' === $_GET['loai'] ? 'dip_le' : 'ban_tin';
		$id   = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
		// phpcs:enable

		$duoc = 'dip_le' === $loai ? current_user_can( 'edit_post', $id ) : current_user_can( self::QUYEN );
		if ( ! $duoc ) {
			wp_die( esc_html__( 'Bạn không có quyền.', 'nntm' ), 403 );
		}

		$kq = Ban_Tin::gui_thu_cho_toi( $loai, $id );

		is_wp_error( $kq ) ? self::quay_lai( 'loi', $kq->get_error_message() ) : self::quay_lai( 'thu_ok' );
	}

	public static function xu_ly_gui_ngay(): void {
		if ( ! current_user_can( self::QUYEN ) ) {
			wp_die( esc_html__( 'Bạn không có quyền.', 'nntm' ), 403 );
		}
		check_admin_referer( 'nntm_ban_tin_gui_ngay' );

		$cd = Ban_Tin::cai_dat();
		$kq = Ban_Tin::tao_ban_tin(
			'ban_tin:tay:' . current_datetime()->format( 'Y-m-d-His' ),
			Ban_Tin::nhan_ky( 'tat' === $cd['tan_suat'] ? 'tuan' : $cd['tan_suat'], current_datetime() )
		);

		is_wp_error( $kq )
			? self::quay_lai( 'loi', $kq->get_error_message() )
			: self::quay_lai( 'gui_ok', '', admin_url( 'admin.php?page=' . self::NHAT_KY ) );
	}

	public static function xu_ly_dung(): void {
		if ( ! current_user_can( self::QUYEN ) ) {
			wp_die( esc_html__( 'Bạn không có quyền.', 'nntm' ), 403 );
		}
		check_admin_referer( 'nntm_ban_tin_dung' );

		Gui_Thu::huy_campaign( isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0 ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		self::quay_lai( 'da_dung' );
	}

	/* ---------- Nhật ký ---------- */

	public static function ve_nhat_ky(): void {
		global $wpdb;

		if ( ! current_user_can( self::QUYEN ) ) {
			return;
		}

		$bang_c = Schema::table( 'mail_campaign' );
		$bang_q = Schema::table( 'mail_queue' );
		$trang  = max( 1, isset( $_GET['paged'] ) ? absint( $_GET['paged'] ) : 1 ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$moi    = 30;

		$tong  = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$bang_c}" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$dong  = $wpdb->get_results( $wpdb->prepare( "SELECT id, loai, tieu_de, trang_thai, tong, da_gui, loi, che_do, created_at, finished_at FROM {$bang_c} ORDER BY id DESC LIMIT %d OFFSET %d", $moi, ( $trang - 1 ) * $moi ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$cho   = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$bang_q} q INNER JOIN {$bang_c} c ON c.id = q.campaign_id WHERE q.trang_thai = 'cho' AND c.trang_thai = 'dang_gui'" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$toi   = wp_next_scheduled( Gui_Thu::CRON_LO );

		$ten_loai = array(
			'ban_tin' => __( 'Bản tin', 'nntm' ),
			'dip_le'  => __( 'Dịp đặc biệt', 'nntm' ),
			'khoa_tu' => __( 'Xác nhận Khóa Tu', 'nntm' ),
			'thu'     => __( 'Thư thử', 'nntm' ),
		);
		$ten_tt   = array(
			'dang_gui' => __( 'Đang gửi', 'nntm' ),
			'xong'     => __( 'Xong', 'nntm' ),
			'huy'      => __( 'Đã dừng', 'nntm' ),
		);
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Nhật ký gửi', 'nntm' ); ?></h1>
			<p>
				<?php
				echo esc_html(
					$cho > 0
						/* translators: 1: số thư chờ, 2: giờ lô kế tiếp */
						? sprintf( __( 'Còn %1$d thư trong hàng đợi. Lô kế tiếp: %2$s.', 'nntm' ), $cho, $toi ? wp_date( 'H:i:s', $toi ) : '—' )
						: __( 'Hàng đợi trống.', 'nntm' )
				);
				?>
			</p>
			<table class="widefat striped">
				<thead><tr>
					<th><?php esc_html_e( 'Thời gian', 'nntm' ); ?></th>
					<th><?php esc_html_e( 'Loại', 'nntm' ); ?></th>
					<th><?php esc_html_e( 'Tiêu đề', 'nntm' ); ?></th>
					<th><?php esc_html_e( 'Người nhận', 'nntm' ); ?></th>
					<th><?php esc_html_e( 'Đã gửi', 'nntm' ); ?></th>
					<th><?php esc_html_e( 'Lỗi', 'nntm' ); ?></th>
					<th><?php esc_html_e( 'Trạng thái', 'nntm' ); ?></th>
				</tr></thead>
				<tbody>
				<?php if ( ! $dong ) : ?>
					<tr><td colspan="7"><?php esc_html_e( 'Chưa gửi thư nào.', 'nntm' ); ?></td></tr>
				<?php endif; ?>
				<?php foreach ( (array) $dong as $c ) : ?>
					<tr>
						<td><?php echo esc_html( mysql2date( 'd/m/Y H:i', (string) $c->created_at ) ); ?></td>
						<td><?php echo esc_html( $ten_loai[ $c->loai ] ?? $c->loai ); ?></td>
						<td><a target="_blank" rel="noopener" href="<?php echo esc_url( self::url_hanh_dong( 'nntm_ban_tin_xem', array( 'loai' => 'campaign', 'id' => (int) $c->id ) ) ); ?>"><?php echo esc_html( (string) $c->tieu_de ); ?></a></td>
						<td><?php echo esc_html( (string) $c->tong ); ?></td>
						<td>
							<?php echo esc_html( (string) $c->da_gui ); ?>
							<?php if ( 'ghi_log' === $c->che_do ) : ?><span class="description"><?php esc_html_e( '(ghi log, chưa gửi thật)', 'nntm' ); ?></span><?php endif; ?>
						</td>
						<td><?php echo (int) $c->loi > 0 ? '<strong style="color:#b32d2e">' . esc_html( (string) $c->loi ) . '</strong>' : '0'; ?></td>
						<td>
							<?php echo esc_html( $ten_tt[ $c->trang_thai ] ?? $c->trang_thai ); ?>
							<?php if ( 'dang_gui' === $c->trang_thai ) : ?>
								— <a href="<?php echo esc_url( self::url_hanh_dong( 'nntm_ban_tin_dung', array( 'id' => (int) $c->id ) ) ); ?>" onclick="return confirm(<?php echo esc_attr( wp_json_encode( __( 'Dừng gửi thư này?', 'nntm' ) ) ); ?>);"><?php esc_html_e( 'Dừng', 'nntm' ); ?></a>
							<?php endif; ?>
						</td>
					</tr>
				<?php endforeach; ?>
				</tbody>
			</table>
			<?php
			$so_trang = (int) ceil( $tong / $moi );
			if ( $so_trang > 1 ) {
				echo '<div class="tablenav"><div class="tablenav-pages">' . wp_kses_post(
					(string) paginate_links(
						array(
							'base'    => add_query_arg( 'paged', '%#%' ),
							'format'  => '',
							'current' => $trang,
							'total'   => $so_trang,
						)
					)
				) . '</div></div>';
			}
			?>
		</div>
		<?php
	}

	/* ---------- Dịp đặc biệt: meta box + cột ---------- */

	public static function them_meta_box(): void {
		add_meta_box( 'nntm-dip-le', __( 'Ngày gửi & thư', 'nntm' ), array( __CLASS__, 've_meta_box' ), Ban_Tin::CPT_DIP, 'side', 'high' );
	}

	public static function ve_meta_box( \WP_Post $post ): void {
		wp_nonce_field( 'nntm_luu_dip_le', 'nntm_dip_nonce' );

		$d       = Ban_Tin::ngay_cua_dip( $post->ID );
		$tieu_de = (string) get_post_meta( $post->ID, Ban_Tin::META_DIP_TIEU_DE, true );
		$lan_toi = metadata_exists( 'post', $post->ID, Ban_Tin::META_DIP_THANG ) ? Ban_Tin::lan_toi_cua_dip( $post->ID ) : null;
		?>
		<p>
			<label><input type="radio" name="nntm_dip_lich" value="am" <?php checked( $d['lich'], 'am' ); ?> /> <?php esc_html_e( 'Âm lịch', 'nntm' ); ?></label>
			&nbsp;
			<label><input type="radio" name="nntm_dip_lich" value="duong" <?php checked( $d['lich'], 'duong' ); ?> /> <?php esc_html_e( 'Dương lịch', 'nntm' ); ?></label>
		</p>
		<p>
			<label><?php esc_html_e( 'Ngày', 'nntm' ); ?>
				<select name="nntm_dip_ngay"><?php for ( $i = 1; $i <= 30; $i++ ) : ?><option value="<?php echo esc_attr( (string) $i ); ?>" <?php selected( $d['ngay'], $i ); ?>><?php echo esc_html( (string) $i ); ?></option><?php endfor; ?></select>
			</label>
			<label><?php esc_html_e( 'tháng', 'nntm' ); ?>
				<select name="nntm_dip_thang"><?php for ( $i = 1; $i <= 12; $i++ ) : ?><option value="<?php echo esc_attr( (string) $i ); ?>" <?php selected( $d['thang'], $i ); ?>><?php echo esc_html( (string) $i ); ?></option><?php endfor; ?></select>
			</label>
		</p>
		<p>
			<?php if ( $lan_toi ) : ?>
				<strong><?php echo esc_html( sprintf( __( 'Lần gửi tới: %s', 'nntm' ), mysql2date( 'd/m/Y', $lan_toi ) ) ); ?></strong><br />
				<span class="description"><?php echo esc_html( sprintf( __( 'lúc %02d:00, nếu dịp ở trạng thái "Đã đăng".', 'nntm' ), (int) Ban_Tin::cai_dat()['dip_gio'] ) ); ?></span>
			<?php else : ?>
				<span class="description"><?php esc_html_e( 'Lưu để xem ngày gửi.', 'nntm' ); ?></span>
			<?php endif; ?>
		</p>
		<p>
			<label for="nntm-dip-tieu-de"><?php esc_html_e( 'Tiêu đề email (để trống = tên dịp)', 'nntm' ); ?></label>
			<input type="text" id="nntm-dip-tieu-de" name="nntm_dip_tieu_de" class="widefat" value="<?php echo esc_attr( $tieu_de ); ?>" />
		</p>
		<?php if ( 'auto-draft' !== $post->post_status ) : ?>
			<p>
				<a class="button" target="_blank" rel="noopener" href="<?php echo esc_url( self::url_hanh_dong( 'nntm_ban_tin_xem', array( 'loai' => 'dip_le', 'id' => $post->ID ) ) ); ?>"><?php esc_html_e( 'Xem trước', 'nntm' ); ?></a>
				<a class="button" href="<?php echo esc_url( self::url_hanh_dong( 'nntm_ban_tin_thu', array( 'loai' => 'dip_le', 'id' => $post->ID ) ) ); ?>"><?php esc_html_e( 'Gửi thử cho tôi', 'nntm' ); ?></a>
			</p>
			<p class="description"><?php esc_html_e( 'Xem trước / gửi thử dùng nội dung ĐÃ LƯU — bấm Cập nhật trước.', 'nntm' ); ?></p>
		<?php endif; ?>
		<?php
	}

	public static function luu_meta_box( int $post_id ): void {
		if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ! isset( $_POST['nntm_dip_nonce'] ) ) {
			return;
		}
		if ( ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['nntm_dip_nonce'] ) ), 'nntm_luu_dip_le' ) || ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$lich = isset( $_POST['nntm_dip_lich'] ) && 'duong' === $_POST['nntm_dip_lich'] ? 'duong' : 'am';
		update_post_meta( $post_id, Ban_Tin::META_DIP_LICH, $lich );
		update_post_meta( $post_id, Ban_Tin::META_DIP_NGAY, max( 1, min( 30, isset( $_POST['nntm_dip_ngay'] ) ? absint( $_POST['nntm_dip_ngay'] ) : 1 ) ) );
		update_post_meta( $post_id, Ban_Tin::META_DIP_THANG, max( 1, min( 12, isset( $_POST['nntm_dip_thang'] ) ? absint( $_POST['nntm_dip_thang'] ) : 1 ) ) );
		update_post_meta( $post_id, Ban_Tin::META_DIP_TIEU_DE, isset( $_POST['nntm_dip_tieu_de'] ) ? mb_substr( sanitize_text_field( wp_unslash( $_POST['nntm_dip_tieu_de'] ) ), 0, 150 ) : '' );
	}

	/**
	 * @param array<string,string> $cot Cột.
	 * @return array<string,string>
	 */
	public static function cot_dip( array $cot ): array {
		$moi = array();
		foreach ( $cot as $k => $v ) {
			$moi[ $k ] = $v;
			if ( 'title' === $k ) {
				$moi['nntm_dip_ngay']    = __( 'Ngày', 'nntm' );
				$moi['nntm_dip_lan_toi'] = __( 'Lần gửi tới', 'nntm' );
			}
		}
		unset( $moi['date'] );

		return $moi;
	}

	public static function ve_cot_dip( string $cot, int $post_id ): void {
		if ( 'nntm_dip_ngay' === $cot ) {
			$d = Ban_Tin::ngay_cua_dip( $post_id );
			echo esc_html( sprintf( '%d/%d %s', $d['ngay'], $d['thang'], 'am' === $d['lich'] ? __( 'âm lịch', 'nntm' ) : __( 'dương lịch', 'nntm' ) ) );
		} elseif ( 'nntm_dip_lan_toi' === $cot ) {
			$ngay = Ban_Tin::lan_toi_cua_dip( $post_id );
			echo esc_html( $ngay ? mysql2date( 'd/m/Y', $ngay ) : '—' );
			if ( 'publish' !== get_post_status( $post_id ) ) {
				echo ' <span class="description">' . esc_html__( '(nháp — sẽ không gửi)', 'nntm' ) . '</span>';
			}
		}
	}
}
