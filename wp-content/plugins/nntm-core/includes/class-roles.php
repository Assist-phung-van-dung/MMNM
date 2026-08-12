<?php
/**
 * Vai trò thành viên: Đại Sĩ, Kim Cương Hành Giả.
 * Nâng cấp là THỦ CÔNG do Ban quản trị (khảo sát câu 8) — không có cơ chế tự động.
 *
 * @package NNTM_Core
 */

namespace NNTM\Core {

	// Chống truy cập trực tiếp file.
	if ( ! defined( 'ABSPATH' ) ) {
		exit;
	}

	/**
	 * Class Roles
	 */
	class Roles {

		const ROLE_DAI_SI    = 'nntm_dai_si';
		const ROLE_KIM_CUONG = 'nntm_kim_cuong';

		/**
		 * Instance duy nhất (singleton).
		 *
		 * @var Roles|null
		 */
		private static ?Roles $instance = null;

		/**
		 * Lấy instance duy nhất.
		 */
		public static function instance(): Roles {
			if ( null === self::$instance ) {
				self::$instance = new self();
			}
			return self::$instance;
		}

		/**
		 * Gắn các hook: body_class, cột + bộ lọc + bulk action ở màn danh sách người dùng.
		 */
		public function hooks(): void {
			add_filter( 'body_class', array( $this, 'add_rank_body_class' ) );

			// Cột "Cấp" + bộ lọc + thao tác hàng loạt ở wp-admin/users.php.
			add_filter( 'manage_users_columns', array( $this, 'add_rank_column' ) );
			add_filter( 'manage_users_custom_column', array( $this, 'render_rank_column' ), 10, 3 );
			add_action( 'restrict_manage_users', array( $this, 'render_rank_filter' ) );
			add_filter( 'bulk_actions-users', array( $this, 'add_bulk_actions' ) );
			add_filter( 'handle_bulk_actions-users', array( $this, 'handle_bulk_action' ), 10, 3 );
			add_action( 'admin_notices', array( $this, 'bulk_action_admin_notice' ) );
		}

		/**
		 * Tạo (hoặc cập nhật lại) 2 role cùng capability.
		 * Gọi lúc kích hoạt plugin (Activator) — không gọi trên mỗi request để tránh phá capability
		 * mà admin có thể đã tự chỉnh bằng plugin quản lý quyền khác.
		 */
		public static function create_roles(): void {
			$subscriber = get_role( 'subscriber' );
			$base_caps  = $subscriber ? $subscriber->capabilities : array( 'read' => true );

			$dai_si_caps = array_merge(
				$base_caps,
				array(
					'nntm_read_library'      => true,
					'nntm_access_meditation' => true,
					'nntm_join_congtu'       => true,
				)
			);

			$kim_cuong_caps = array_merge(
				$dai_si_caps,
				array(
					'nntm_read_exclusive' => true,
				)
			);

			// Xóa trước khi tạo lại để cập nhật đúng danh sách capability khi plugin nâng cấp version.
			remove_role( self::ROLE_DAI_SI );
			remove_role( self::ROLE_KIM_CUONG );

			add_role( self::ROLE_DAI_SI, __( 'Đại Sĩ', 'nntm' ), $dai_si_caps );
			add_role( self::ROLE_KIM_CUONG, __( 'Kim Cương Hành Giả', 'nntm' ), $kim_cuong_caps );
		}

		/**
		 * Thêm class is-dai-si / is-kim-cuong lên thẻ <body> để theme đảo biến CSS theo cấp.
		 *
		 * @param array $classes Danh sách class hiện có.
		 */
		public function add_rank_body_class( array $classes ): array {
			$rank = nntm_user_rank();

			if ( 'kim_cuong' === $rank ) {
				$classes[] = 'is-kim-cuong';
			} elseif ( 'dai_si' === $rank ) {
				$classes[] = 'is-dai-si';
			}

			return $classes;
		}

		/**
		 * Thêm cột "Cấp" vào bảng danh sách người dùng trong admin.
		 *
		 * @param array $columns Danh sách cột hiện có.
		 */
		public function add_rank_column( array $columns ): array {
			$columns['nntm_rank'] = __( 'Cấp', 'nntm' );
			return $columns;
		}

		/**
		 * Hiển thị nội dung cột "Cấp" cho từng người dùng.
		 *
		 * @param string $value       Giá trị mặc định (rỗng).
		 * @param string $column_name Tên cột đang render.
		 * @param int    $user_id     ID người dùng.
		 */
		public function render_rank_column( $value, string $column_name, int $user_id ) {
			if ( 'nntm_rank' !== $column_name ) {
				return $value;
			}

			$rank = nntm_user_rank( $user_id );

			if ( 'kim_cuong' === $rank ) {
				return esc_html__( 'Kim Cương Hành Giả', 'nntm' );
			}

			if ( 'dai_si' === $rank ) {
				return esc_html__( 'Đại Sĩ', 'nntm' );
			}

			return '&#8212;';
		}

		/**
		 * Dropdown lọc theo cấp trên màn users.php.
		 * Dùng name="role" để tận dụng luôn cơ chế lọc theo role có sẵn của WP_Users_List_Table.
		 */
		public function render_rank_filter(): void {
			global $pagenow;

			if ( 'users.php' !== $pagenow || ! current_user_can( 'list_users' ) ) {
				return;
			}

			$current = isset( $_GET['role'] ) ? sanitize_text_field( wp_unslash( $_GET['role'] ) ) : '';
			?>
			<label class="screen-reader-text" for="nntm-rank-filter"><?php esc_html_e( 'Lọc theo cấp', 'nntm' ); ?></label>
			<select name="role" id="nntm-rank-filter">
				<option value=""><?php esc_html_e( 'Tất cả các cấp', 'nntm' ); ?></option>
				<option value="<?php echo esc_attr( self::ROLE_DAI_SI ); ?>" <?php selected( $current, self::ROLE_DAI_SI ); ?>>
					<?php esc_html_e( 'Đại Sĩ', 'nntm' ); ?>
				</option>
				<option value="<?php echo esc_attr( self::ROLE_KIM_CUONG ); ?>" <?php selected( $current, self::ROLE_KIM_CUONG ); ?>>
					<?php esc_html_e( 'Kim Cương Hành Giả', 'nntm' ); ?>
				</option>
			</select>
			<?php
		}

		/**
		 * Thêm thao tác hàng loạt: nâng cấp / hạ cấp thủ công.
		 *
		 * @param array $bulk_actions Danh sách thao tác hàng loạt hiện có.
		 */
		public function add_bulk_actions( array $bulk_actions ): array {
			$bulk_actions['nntm_promote_dai_si']    = __( 'Nâng lên Đại Sĩ', 'nntm' );
			$bulk_actions['nntm_promote_kim_cuong'] = __( 'Nâng lên Kim Cương Hành Giả', 'nntm' );
			$bulk_actions['nntm_demote_subscriber'] = __( 'Hạ về Thành viên thường', 'nntm' );
			return $bulk_actions;
		}

		/**
		 * Xử lý thao tác hàng loạt nâng/hạ cấp.
		 * Kiểm tra nonce + capability edit_users trước khi ghi bất kỳ thay đổi nào (bắt buộc theo quy ước code).
		 *
		 * @param string $sendback URL redirect sau khi xử lý.
		 * @param string $doaction Tên thao tác được chọn.
		 * @param array  $user_ids Danh sách ID người dùng được chọn.
		 */
		public function handle_bulk_action( string $sendback, string $doaction, array $user_ids ): string {
			$valid_actions = array( 'nntm_promote_dai_si', 'nntm_promote_kim_cuong', 'nntm_demote_subscriber' );

			if ( ! in_array( $doaction, $valid_actions, true ) ) {
				return $sendback;
			}

			// Chỉ Ban quản trị có quyền edit_users mới được nâng/hạ cấp — đúng khảo sát câu 8 (thủ công).
			if ( ! current_user_can( 'edit_users' ) ) {
				return $sendback;
			}

			check_admin_referer( 'bulk-users' );

			$target_role = null;
			if ( 'nntm_promote_dai_si' === $doaction ) {
				$target_role = self::ROLE_DAI_SI;
			} elseif ( 'nntm_promote_kim_cuong' === $doaction ) {
				$target_role = self::ROLE_KIM_CUONG;
			} elseif ( 'nntm_demote_subscriber' === $doaction ) {
				$target_role = 'subscriber';
			}

			$count   = 0;
			$skipped = 0;
			foreach ( $user_ids as $user_id ) {
				$user_id = absint( $user_id );
				if ( ! $user_id || ! current_user_can( 'edit_user', $user_id ) ) {
					continue;
				}
				$user = get_userdata( $user_id );
				if ( ! $user ) {
					continue;
				}

				/*
				 * set_role() THAY THẾ toàn bộ vai trò hiện có. Nếu vô tình chọn phải một
				 * quản trị viên rồi nâng/hạ cấp, tài khoản đó mất sạch quyền quản trị —
				 * chọn nhầm chính mình là tự khóa cửa. Bỏ qua mọi tài khoản có quyền
				 * quản trị và báo lại số lượng đã bỏ qua.
				 */
				if ( user_can( $user, 'manage_options' ) ) {
					++$skipped;
					continue;
				}

				$user->set_role( $target_role );
				++$count;
			}

			$sendback = add_query_arg( 'nntm_rank_changed', $count, $sendback );
			if ( $skipped > 0 ) {
				$sendback = add_query_arg( 'nntm_rank_skipped', $skipped, $sendback );
			}

			return $sendback;
		}

		/**
		 * Hiển thị thông báo sau khi xử lý thao tác hàng loạt.
		 */
		public function bulk_action_admin_notice(): void {
			// phpcs:disable WordPress.Security.NonceVerification.Recommended -- chỉ đọc để hiển thị thông báo, không ghi dữ liệu.
			$changed = isset( $_REQUEST['nntm_rank_changed'] ) ? absint( $_REQUEST['nntm_rank_changed'] ) : null;
			$skipped = isset( $_REQUEST['nntm_rank_skipped'] ) ? absint( $_REQUEST['nntm_rank_skipped'] ) : 0;
			// phpcs:enable WordPress.Security.NonceVerification.Recommended

			if ( null === $changed ) {
				return;
			}

			printf(
				'<div id="message" class="updated notice is-dismissible"><p>%s</p></div>',
				esc_html(
					sprintf(
						/* translators: %d: số lượng thành viên đã đổi cấp */
						_n( 'Đã cập nhật cấp cho %d thành viên.', 'Đã cập nhật cấp cho %d thành viên.', $changed, 'nntm' ),
						$changed
					)
				)
			);

			// Cảnh báo riêng: có tài khoản quản trị bị bỏ qua để tránh mất quyền.
			if ( $skipped > 0 ) {
				printf(
					'<div class="notice notice-warning is-dismissible"><p>%s</p></div>',
					esc_html(
						sprintf(
							/* translators: %d: số lượng tài khoản quản trị bị bỏ qua */
							_n(
								'Đã bỏ qua %d tài khoản quản trị. Đổi cấp cho tài khoản quản trị sẽ làm mất quyền quản trị, nên phải thao tác thủ công trong trang hồ sơ của tài khoản đó.',
								'Đã bỏ qua %d tài khoản quản trị. Đổi cấp cho tài khoản quản trị sẽ làm mất quyền quản trị, nên phải thao tác thủ công trong trang hồ sơ của tài khoản đó.',
								$skipped,
								'nntm'
							),
							$skipped
						)
					)
				);
			}
		}
	}
}

namespace {

	// Chống truy cập trực tiếp file.
	if ( ! defined( 'ABSPATH' ) ) {
		exit;
	}

	/**
	 * Hàm tiện ích toàn cục: lấy cấp thành viên hiện tại.
	 * Theme và các plugin khác (nntm-library, nntm-audio, nntm-congtu...) gọi hàm này để kiểm tra quyền hiển thị.
	 *
	 * @param int $user_id ID người dùng, để trống (0) thì lấy người dùng đang đăng nhập.
	 * @return string|null 'kim_cuong' | 'dai_si' | null.
	 */
	function nntm_user_rank( int $user_id = 0 ): ?string {
		if ( ! $user_id ) {
			$user_id = get_current_user_id();
		}

		if ( ! $user_id ) {
			return null;
		}

		$user = get_userdata( $user_id );
		if ( ! $user ) {
			return null;
		}

		$roles = (array) $user->roles;

		if ( in_array( 'nntm_kim_cuong', $roles, true ) ) {
			return 'kim_cuong';
		}

		if ( in_array( 'nntm_dai_si', $roles, true ) ) {
			return 'dai_si';
		}

		return null;
	}
}
