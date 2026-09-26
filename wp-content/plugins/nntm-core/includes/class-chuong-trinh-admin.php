<?php
/**
 * Màn quản trị cho CPT nntm_program (Cộng Tu — chuỗi trì).
 *
 * Trước đây 5 meta của chương trình (_nntm_program_bat_dau, _ket_thuc,
 * _dang_mo, _don_vi, _muc_tieu — đăng ký ở class-post-meta.php) chỉ đặt
 * được bằng script tools/seed-cong-tu.php. File này thêm meta box
 * "Thiết lập chương trình" (theo đúng mẫu add_publication_meta_box() /
 * render_publication_meta_box() / save_publication_meta_box() của
 * class-post-meta.php) để BQT tự đặt qua wp-admin.
 *
 * Tách riêng khỏi class-post-meta.php (thay vì nhét thêm vào) vì file đó đã
 * dài, và đây là nghiệp vụ MÀN QUẢN TRỊ (meta box + cột danh sách), khác với
 * việc chỉ đăng ký meta cho REST/trình soạn thảo.
 *
 * Nghiệp vụ đọc (nntm_program_dang_mo(), nntm_program_hien_tai(),
 * nntm_kpi_ngay_hop_le()) nằm ở includes/class-chuoi-tri.php và
 * includes/class-chuoi-tri-ca-nhan.php — file này CHỈ vẽ màn quản trị và lưu
 * meta, không được đụng tới logic mở/đóng ở hai file kia.
 *
 * @package NNTM_Core
 */

namespace NNTM\Core;

defined( 'ABSPATH' ) || exit;

/**
 * Meta box + cột danh sách quản trị cho chương trình trì tụng.
 */
class Chuong_Trinh_Admin {

	/**
	 * Bản thân đối tượng.
	 *
	 * @var Chuong_Trinh_Admin|null
	 */
	private static ?Chuong_Trinh_Admin $instance = null;

	/**
	 * Đệm ID chương trình đang hiện trên trang Cộng Tu, tính MỘT LẦN cho cả
	 * màn danh sách (nntm_program_hien_tai() chạy một WP_Query riêng, gọi lại
	 * mỗi dòng là thừa — nntm_kpi_phap_danh_va_vung_mien() ở nơi khác trong dự
	 * án cũng tránh N+1 theo đúng tinh thần này).
	 *
	 * @var int|null
	 */
	private ?int $id_hien_tai_dem = null;

	/**
	 * Lấy đối tượng dùng chung.
	 */
	public static function instance(): Chuong_Trinh_Admin {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Gắn hook.
	 */
	public function hooks(): void {
		add_action( 'add_meta_boxes_nntm_program', array( $this, 'add_meta_box' ) );
		add_action( 'save_post_nntm_program', array( $this, 'save_meta_box' ) );

		add_filter( 'manage_nntm_program_posts_columns', array( $this, 'columns' ) );
		add_action( 'manage_nntm_program_posts_custom_column', array( $this, 'render_column' ), 10, 2 );
	}

	/* =====================================================================
	 * Meta box "Thiết lập chương trình".
	 * ===================================================================== */

	/** Thêm meta box vào màn sửa chương trình. */
	public function add_meta_box(): void {
		add_meta_box(
			'nntm_chuong_trinh_thiet_lap',
			__( 'Thiết lập chương trình', 'nntm' ),
			array( $this, 'render_meta_box' ),
			'nntm_program',
			'side',
			'high'
		);
	}

	/**
	 * Vẽ meta box: công tắc mở/đóng, ngày bắt đầu/kết thúc, đơn vị đếm, mục
	 * tiêu chung, và dòng hiện trạng tính ngay lúc tải màn.
	 *
	 * @param \WP_Post $post Chương trình đang sửa.
	 */
	public function render_meta_box( $post ): void {
		wp_nonce_field( 'nntm_chuong_trinh_thiet_lap', 'nntm_chuong_trinh_thiet_lap_nonce' );

		// Lỗi lưu từ lượt trước (xem save_meta_box(): màn dùng trình soạn
		// thảo khối nên meta box được POST SAU lượt lưu REST, admin_notices
		// không kịp hiện — để tạm vào transient rồi hiện ngay ở đây).
		$khoa_loi = $this->khoa_transient_loi( get_current_user_id(), $post->ID );
		$loi      = get_transient( $khoa_loi );
		if ( $loi ) {
			delete_transient( $khoa_loi );
			echo '<div class="notice notice-error inline"><p>' . esc_html( (string) $loi ) . '</p></div>';
		}

		$dang_mo  = (bool) get_post_meta( $post->ID, '_nntm_program_dang_mo', true );
		$bat_dau  = (string) get_post_meta( $post->ID, '_nntm_program_bat_dau', true );
		$ket_thuc = (string) get_post_meta( $post->ID, '_nntm_program_ket_thuc', true );
		$don_vi   = (string) get_post_meta( $post->ID, '_nntm_program_don_vi', true );
		$muc_tieu = (int) get_post_meta( $post->ID, '_nntm_program_muc_tieu', true );
		?>
		<p>
			<label for="nntm_program_dang_mo_input">
				<input type="checkbox" id="nntm_program_dang_mo_input" name="nntm_program_dang_mo" value="1" <?php checked( $dang_mo ); ?> />
				<?php esc_html_e( 'Mở nhận cam kết & khai báo', 'nntm' ); ?>
			</label>
		</p>

		<p>
			<label for="nntm_program_bat_dau_input"><strong><?php esc_html_e( 'Ngày bắt đầu', 'nntm' ); ?></strong></label><br />
			<input type="date" id="nntm_program_bat_dau_input" name="nntm_program_bat_dau" value="<?php echo esc_attr( $bat_dau ); ?>" />
		</p>

		<p>
			<label for="nntm_program_ket_thuc_input"><strong><?php esc_html_e( 'Ngày kết thúc', 'nntm' ); ?></strong></label><br />
			<input type="date" id="nntm_program_ket_thuc_input" name="nntm_program_ket_thuc" value="<?php echo esc_attr( $ket_thuc ); ?>" /><br />
			<span class="description"><?php esc_html_e( 'Để trống = không giới hạn.', 'nntm' ); ?></span>
		</p>

		<p>
			<label for="nntm_program_don_vi_input"><strong><?php esc_html_e( 'Đơn vị đếm', 'nntm' ); ?></strong></label><br />
			<input type="text" id="nntm_program_don_vi_input" name="nntm_program_don_vi" value="<?php echo esc_attr( $don_vi ); ?>" placeholder="<?php echo esc_attr( _x( 'chuỗi', 'placeholder đơn vị đếm mặc định', 'nntm' ) ); ?>" maxlength="30" />
		</p>

		<p>
			<label for="nntm_program_muc_tieu_input"><strong><?php esc_html_e( 'Mục tiêu chung của đạo tràng', 'nntm' ); ?></strong></label><br />
			<input type="number" min="0" step="1" id="nntm_program_muc_tieu_input" name="nntm_program_muc_tieu" value="<?php echo esc_attr( (string) $muc_tieu ); ?>" /><br />
			<span class="description"><?php esc_html_e( '0 = không đặt. Hiện chưa hiển thị trên trang.', 'nntm' ); ?></span>
		</p>

		<hr />
		<?php
		$tt = $this->tinh_trang_thai( $post->ID );
		?>
		<p>
			<strong><?php esc_html_e( 'Hiện trạng:', 'nntm' ); ?></strong> <?php echo esc_html( $this->nhan_dai( $tt ) ); ?><br />
			<span class="description">
				<?php
				echo esc_html(
					sprintf(
						/* translators: %s: hôm nay dd/mm/yyyy theo giờ site */
						__( 'Hôm nay theo giờ site: %s', 'nntm' ),
						$this->ngay_hien_thi( $tt['hom_nay'] )
					)
				);
				?>
			</span>
		</p>
		<?php
		$hien_tai = function_exists( 'nntm_program_hien_tai' ) ? \nntm_program_hien_tai() : null;

		if ( 'mo' === $tt['ma'] && $hien_tai instanceof \WP_Post && (int) $hien_tai->ID !== (int) $post->ID ) :
			?>
			<div class="notice notice-warning inline">
				<p>
					<?php
					echo esc_html(
						sprintf(
							/* translators: %s: tên chương trình đang hiện trên trang Cộng Tu */
							// Form cam kết/khai báo (themes/nntm/inc/cong-tu.php) luôn ghi vào
							// nntm_program_hien_tai(), kể cả nút trên trang của chương trình này.
							__( 'Trang Cộng Tu đang hiện chương trình “%s” (mới đăng hơn). Mọi cam kết và khai báo đều ghi vào chương trình đó — chương trình này không nhận khai báo mới dù đang mở.', 'nntm' ),
							get_the_title( $hien_tai )
						)
					);
					?>
				</p>
			</div>
			<?php
		endif;
	}

	/**
	 * Lưu meta box "Thiết lập chương trình".
	 *
	 * Hook save_post_nntm_program chạy HAI lần với trình soạn thảo khối: một
	 * lần từ REST (không có $_POST nonce của form này — kiểm nonce bên dưới
	 * tự bỏ qua lượt đó), một lần từ chính form POST của meta box.
	 *
	 * @param int $post_id ID chương trình.
	 */
	public function save_meta_box( int $post_id ): void {
		if ( ! isset( $_POST['nntm_chuong_trinh_thiet_lap_nonce'] )
			|| ! wp_verify_nonce( wp_unslash( $_POST['nntm_chuong_trinh_thiet_lap_nonce'] ), 'nntm_chuong_trinh_thiet_lap' )
		) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$loi = array();

		$bat_dau_cu  = (string) get_post_meta( $post_id, '_nntm_program_bat_dau', true );
		$ket_thuc_cu = (string) get_post_meta( $post_id, '_nntm_program_ket_thuc', true );

		$bat_dau_moi  = $this->doc_ngay_gui_len(
			'nntm_program_bat_dau',
			$bat_dau_cu,
			__( 'Ngày bắt đầu không đúng định dạng, đã giữ nguyên giá trị cũ.', 'nntm' ),
			$loi
		);
		$ket_thuc_moi = $this->doc_ngay_gui_len(
			'nntm_program_ket_thuc',
			$ket_thuc_cu,
			__( 'Ngày kết thúc không đúng định dạng, đã giữ nguyên giá trị cũ.', 'nntm' ),
			$loi
		);

		// Cả hai có giá trị mà kết thúc trước bắt đầu: không lưu HAI ngày,
		// giữ nguyên cả hai giá trị cũ (không chỉ riêng ngày sai).
		if ( '' !== $bat_dau_moi && '' !== $ket_thuc_moi && $ket_thuc_moi < $bat_dau_moi ) {
			$loi[]        = __( 'Ngày kết thúc phải sau ngày bắt đầu, đã giữ nguyên hai ngày cũ.', 'nntm' );
			$bat_dau_moi  = $bat_dau_cu;
			$ket_thuc_moi = $ket_thuc_cu;
		}

		update_post_meta( $post_id, '_nntm_program_bat_dau', $bat_dau_moi );
		update_post_meta( $post_id, '_nntm_program_ket_thuc', $ket_thuc_moi );

		// Công tắc: như mẫu ấn phẩm (save_publication_meta_box) — boolean ra
		// đúng '1' / '' khi lưu, khớp meta_query value '1' của
		// nntm_program_hien_tai().
		update_post_meta( $post_id, '_nntm_program_dang_mo', isset( $_POST['nntm_program_dang_mo'] ) );

		if ( isset( $_POST['nntm_program_don_vi'] ) ) {
			$don_vi = mb_substr( trim( sanitize_text_field( wp_unslash( $_POST['nntm_program_don_vi'] ) ) ), 0, 30 );

			if ( '' === $don_vi ) {
				// Xoá hẳn để giá trị default 'chuỗi' của register_post_meta áp dụng.
				delete_post_meta( $post_id, '_nntm_program_don_vi' );
			} else {
				update_post_meta( $post_id, '_nntm_program_don_vi', $don_vi );
			}
		}

		if ( isset( $_POST['nntm_program_muc_tieu'] ) ) {
			update_post_meta( $post_id, '_nntm_program_muc_tieu', absint( wp_unslash( $_POST['nntm_program_muc_tieu'] ) ) );
		}

		if ( ! empty( $loi ) ) {
			set_transient(
				$this->khoa_transient_loi( get_current_user_id(), $post_id ),
				implode( ' ', $loi ),
				60
			);
		}
	}

	/**
	 * Đọc một trường ngày từ $_POST: rỗng → '', sai định dạng → giữ giá trị
	 * cũ + góp lỗi vào $loi, hợp lệ → ngày đã chuẩn hoá Y-m-d.
	 *
	 * @param string   $ten_truong    Tên input HTML.
	 * @param string   $gia_tri_cu    Giá trị đang lưu, dùng khi sai định dạng.
	 * @param string   $thong_bao_loi Câu báo lỗi khi sai định dạng.
	 * @param string[] $loi           Mảng lỗi, góp thêm bằng tham chiếu.
	 */
	private function doc_ngay_gui_len( string $ten_truong, string $gia_tri_cu, string $thong_bao_loi, array &$loi ): string {
		if ( ! isset( $_POST[ $ten_truong ] ) ) {
			return $gia_tri_cu;
		}

		$tho = trim( sanitize_text_field( wp_unslash( $_POST[ $ten_truong ] ) ) );

		if ( '' === $tho ) {
			return '';
		}

		$hop_le = function_exists( 'nntm_kpi_ngay_hop_le' ) ? nntm_kpi_ngay_hop_le( $tho ) : null;

		if ( null === $hop_le ) {
			$loi[] = $thong_bao_loi;
			return $gia_tri_cu;
		}

		return $hop_le;
	}

	/** Tên transient chứa lỗi lưu meta box, theo user + bài — sống 60 giây. */
	private function khoa_transient_loi( int $user_id, int $post_id ): string {
		return sprintf( 'nntm_ct_loi_%d_%d', $user_id, $post_id );
	}

	/* =====================================================================
	 * Hiện trạng dùng chung cho cả meta box lẫn cột danh sách.
	 * ===================================================================== */

	/**
	 * Tính hiện trạng của một chương trình — DÙNG CHUNG cho meta box (câu dài
	 * kèm ngày) và cột "Trạng thái" ở màn danh sách (nhãn ngắn), tránh viết
	 * logic hai lần và lệch nhau. Thứ tự kiểm ĐÚNG như nntm_program_dang_mo():
	 * chưa đăng > công tắc tắt > chưa tới ngày > hết hạn > đang mở.
	 *
	 * @param int $post_id ID chương trình.
	 * @return array{ma:string,bat_dau:?string,ket_thuc:?string,hom_nay:string}
	 */
	private function tinh_trang_thai( int $post_id ): array {
		$post    = get_post( $post_id );
		$da_dang = ( $post instanceof \WP_Post ) && 'publish' === $post->post_status;

		$cong_tac = (bool) get_post_meta( $post_id, '_nntm_program_dang_mo', true );

		$bat_dau = function_exists( 'nntm_kpi_ngay_hop_le' )
			? nntm_kpi_ngay_hop_le( (string) get_post_meta( $post_id, '_nntm_program_bat_dau', true ) )
			: null;
		$ket_thuc = function_exists( 'nntm_kpi_ngay_hop_le' )
			? nntm_kpi_ngay_hop_le( (string) get_post_meta( $post_id, '_nntm_program_ket_thuc', true ) )
			: null;

		$hom_nay = current_time( 'Y-m-d' );

		if ( ! $da_dang ) {
			$ma = 'chua_dang';
		} elseif ( ! $cong_tac ) {
			$ma = 'tat_cong_tac';
		} elseif ( null !== $bat_dau && $hom_nay < $bat_dau ) {
			$ma = 'chua_toi_ngay';
		} elseif ( null !== $ket_thuc && $hom_nay > $ket_thuc ) {
			$ma = 'het_han';
		} else {
			$ma = 'mo';
		}

		return array(
			'ma'       => $ma,
			'bat_dau'  => $bat_dau,
			'ket_thuc' => $ket_thuc,
			'hom_nay'  => $hom_nay,
		);
	}

	/** Câu hiện trạng đầy đủ cho meta box. */
	private function nhan_dai( array $tt ): string {
		switch ( $tt['ma'] ) {
			case 'mo':
				return __( 'Đang mở nhận khai báo', 'nntm' );
			case 'tat_cong_tac':
				return __( 'Đang đóng — công tắc tắt', 'nntm' );
			case 'chua_dang':
				return __( 'Đang đóng — chưa đăng', 'nntm' );
			case 'chua_toi_ngay':
				return sprintf(
					/* translators: %s: ngày bắt đầu dd/mm/yyyy */
					__( 'Chưa tới ngày bắt đầu (%s)', 'nntm' ),
					$this->ngay_hien_thi( (string) $tt['bat_dau'] )
				);
			case 'het_han':
				return sprintf(
					/* translators: %s: ngày kết thúc dd/mm/yyyy */
					__( 'Đã qua ngày kết thúc (%s)', 'nntm' ),
					$this->ngay_hien_thi( (string) $tt['ket_thuc'] )
				);
			default:
				return '';
		}
	}

	/** Nhãn ngắn cho cột "Trạng thái" ở màn danh sách. */
	private function nhan_ngan( array $tt ): string {
		switch ( $tt['ma'] ) {
			case 'mo':
				return __( 'Đang mở', 'nntm' );
			case 'chua_toi_ngay':
				return __( 'Chưa tới ngày', 'nntm' );
			case 'het_han':
				return __( 'Hết hạn', 'nntm' );
			default: // tat_cong_tac hoặc chua_dang — màn danh sách gộp chung "Đóng".
				return __( 'Đóng', 'nntm' );
		}
	}

	/** Đổi 'Y-m-d' sang 'd/m/Y' để BQT quen mắt; chuỗi không hợp lệ trả nguyên văn. */
	private function ngay_hien_thi( string $ngay_ymd ): string {
		$d = \DateTimeImmutable::createFromFormat( '!Y-m-d', $ngay_ymd, new \DateTimeZone( 'UTC' ) );

		return $d ? $d->format( 'd/m/Y' ) : $ngay_ymd;
	}

	/* =====================================================================
	 * Cột "Trạng thái" + "Thời gian" ở màn danh sách Chương trình trì tụng.
	 * ===================================================================== */

	/**
	 * Thêm hai cột ngay sau cột tiêu đề.
	 *
	 * @param array<string,string> $cols Cột gốc.
	 * @return array<string,string>
	 */
	public function columns( array $cols ): array {
		$moi = array();

		foreach ( $cols as $khoa => $nhan ) {
			$moi[ $khoa ] = $nhan;

			if ( 'title' === $khoa ) {
				$moi['nntm_ct_trang_thai'] = __( 'Trạng thái', 'nntm' );
				$moi['nntm_ct_thoi_gian']  = __( 'Thời gian', 'nntm' );
			}
		}

		return $moi;
	}

	/**
	 * In nội dung một ô của hai cột trên.
	 *
	 * @param string $col     Khoá cột.
	 * @param int    $post_id ID chương trình của dòng đang vẽ.
	 */
	public function render_column( string $col, int $post_id ): void {
		switch ( $col ) {
			case 'nntm_ct_trang_thai':
				$tt = $this->tinh_trang_thai( $post_id );
				echo esc_html( $this->nhan_ngan( $tt ) );

				if ( $post_id === $this->id_chuong_trinh_hien_tai() ) {
					echo ' <span class="description">· ' . esc_html__( 'đang hiện trên trang Cộng Tu', 'nntm' ) . '</span>';
				}
				break;

			case 'nntm_ct_thoi_gian':
				echo esc_html( $this->khoang_thoi_gian_hien_thi( $post_id ) );
				break;
		}
	}

	/** Chuỗi "dd/mm/yyyy – dd/mm/yyyy", thiếu ngày nào thì bên đó ghi "không giới hạn". */
	private function khoang_thoi_gian_hien_thi( int $post_id ): string {
		$bat_dau = function_exists( 'nntm_kpi_ngay_hop_le' )
			? nntm_kpi_ngay_hop_le( (string) get_post_meta( $post_id, '_nntm_program_bat_dau', true ) )
			: null;
		$ket_thuc = function_exists( 'nntm_kpi_ngay_hop_le' )
			? nntm_kpi_ngay_hop_le( (string) get_post_meta( $post_id, '_nntm_program_ket_thuc', true ) )
			: null;

		$khong_gioi_han = __( 'không giới hạn', 'nntm' );

		return sprintf(
			'%1$s – %2$s',
			$bat_dau ? $this->ngay_hien_thi( $bat_dau ) : $khong_gioi_han,
			$ket_thuc ? $this->ngay_hien_thi( $ket_thuc ) : $khong_gioi_han
		);
	}

	/**
	 * ID chương trình đang hiện trên trang Cộng Tu — gọi nntm_program_hien_tai()
	 * (một WP_Query riêng) MỘT LẦN cho cả màn danh sách, đệm lại cho các dòng sau.
	 */
	private function id_chuong_trinh_hien_tai(): int {
		if ( null === $this->id_hien_tai_dem ) {
			$hien_tai              = function_exists( 'nntm_program_hien_tai' ) ? \nntm_program_hien_tai() : null;
			$this->id_hien_tai_dem = $hien_tai instanceof \WP_Post ? (int) $hien_tai->ID : 0;
		}

		return $this->id_hien_tai_dem;
	}
}
