<?php

defined( 'ABSPATH' ) || exit;

const NNTM_LH_LOAI = 'nntm_lien_he';

/**
 * Lời nhắn liên hệ được lưu thành một loại nội dung riêng, không công khai.
 *
 * Trước đây biểu mẫu chỉ gửi email rồi thôi — email hỏng là mất luôn lời nhắn,
 * và trong admin không có chỗ nào xem lại.
 */
function nntm_lh_dang_ky_loai(): void {
	register_post_type(
		NNTM_LH_LOAI,
		array(
			'labels'              => array(
				'name'               => __( 'Liên hệ', 'nntm' ),
				'singular_name'      => __( 'Lời nhắn', 'nntm' ),
				'menu_name'          => __( 'Liên hệ', 'nntm' ),
				'all_items'          => __( 'Tất cả lời nhắn', 'nntm' ),
				'edit_item'          => __( 'Xem lời nhắn', 'nntm' ),
				'view_item'          => __( 'Xem lời nhắn', 'nntm' ),
				'search_items'       => __( 'Tìm lời nhắn', 'nntm' ),
				'not_found'          => __( 'Chưa có lời nhắn nào.', 'nntm' ),
				'not_found_in_trash' => __( 'Thùng rác trống.', 'nntm' ),
			),
			'public'              => false,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'show_in_rest'        => false,
			'menu_position'       => 26,
			'menu_icon'           => 'dashicons-email-alt',
			'supports'            => array( 'title', 'editor' ),
			'has_archive'         => false,
			'rewrite'             => false,
			'exclude_from_search' => true,
			'map_meta_cap'        => true,
			'capability_type'     => 'post',
			// Không cho tự tay tạo lời nhắn trong admin; chỉ biểu mẫu ngoài trang mới sinh ra.
			'capabilities'        => array( 'create_posts' => 'do_not_allow' ),
		)
	);
}
add_action( 'init', 'nntm_lh_dang_ky_loai' );

/**
 * Quyền cần có để xem lời nhắn. Khớp với quyền của loại nội dung ở trên, tức là
 * Biên tập viên trở lên. Muốn siết lại chỉ quản trị viên thì lọc thành
 * 'manage_options' và đổi capability_type cho khớp.
 */
function nntm_lh_quyen(): string {
	return (string) apply_filters( 'nntm_lh_quyen', 'edit_others_posts' );
}

/**
 * Lưu một lời nhắn. Trả về ID bài, 0 nếu hỏng.
 *
 * @param array<string,string> $du_lieu ho_ten, email, dien_thoai, cau_hoi.
 */
function nntm_lh_luu( array $du_lieu ): int {
	$ho_ten = isset( $du_lieu['ho_ten'] ) ? (string) $du_lieu['ho_ten'] : '';
	$cau_hoi = isset( $du_lieu['cau_hoi'] ) ? (string) $du_lieu['cau_hoi'] : '';

	$id = wp_insert_post(
		array(
			'post_type'    => NNTM_LH_LOAI,
			'post_status'  => 'publish',
			'post_title'   => sprintf(
				/* translators: 1: tên người gửi, 2: thời điểm gửi. */
				__( '%1$s — %2$s', 'nntm' ),
				'' !== $ho_ten ? $ho_ten : __( 'Không rõ tên', 'nntm' ),
				wp_date( 'd/m/Y H:i' )
			),
			'post_content' => $cau_hoi,
		),
		true
	);

	if ( is_wp_error( $id ) || ! $id ) {
		return 0;
	}

	update_post_meta( $id, '_nntm_lh_ho_ten', $ho_ten );
	update_post_meta( $id, '_nntm_lh_email', isset( $du_lieu['email'] ) ? (string) $du_lieu['email'] : '' );
	update_post_meta( $id, '_nntm_lh_dien_thoai', isset( $du_lieu['dien_thoai'] ) ? (string) $du_lieu['dien_thoai'] : '' );
	update_post_meta( $id, '_nntm_lh_da_doc', '0' );

	return (int) $id;
}

/* -------------------------------------------------------------------------
 * Màn hình quản trị
 * ---------------------------------------------------------------------- */

function nntm_lh_cot( array $cot ): array {
	return array(
		'cb'          => isset( $cot['cb'] ) ? $cot['cb'] : '',
		'title'       => __( 'Người gửi', 'nntm' ),
		'lh_email'    => __( 'Email', 'nntm' ),
		'lh_dien_thoai' => __( 'Điện thoại', 'nntm' ),
		'lh_cau_hoi'  => __( 'Câu hỏi', 'nntm' ),
		'lh_mail'     => __( 'Email báo', 'nntm' ),
		'lh_ngay'     => __( 'Ngày gửi', 'nntm' ),
	);
}
add_filter( 'manage_' . NNTM_LH_LOAI . '_posts_columns', 'nntm_lh_cot' );

function nntm_lh_ve_cot( string $cot, int $post_id ): void {
	switch ( $cot ) {
		case 'lh_email':
			$email = (string) get_post_meta( $post_id, '_nntm_lh_email', true );
			echo '' !== $email
				? '<a href="' . esc_attr( 'mailto:' . $email ) . '">' . esc_html( $email ) . '</a>'
				: '—';
			break;

		case 'lh_dien_thoai':
			$dt = (string) get_post_meta( $post_id, '_nntm_lh_dien_thoai', true );
			echo '' !== $dt
				? '<a href="' . esc_attr( 'tel:' . preg_replace( '/[^0-9+]/', '', $dt ) ) . '">' . esc_html( $dt ) . '</a>'
				: '—';
			break;

		case 'lh_cau_hoi':
			$bai = get_post( $post_id );
			echo $bai instanceof WP_Post
				? esc_html( wp_trim_words( (string) $bai->post_content, 20, '…' ) )
				: '—';
			break;

		case 'lh_mail':
			$da_gui = (string) get_post_meta( $post_id, '_nntm_lh_da_gui_mail', true );

			if ( '1' === $da_gui ) {
				echo '<span style="color:#1E7B34">' . esc_html__( 'Đã gửi', 'nntm' ) . '</span>';
			} else {
				$loi = (string) get_post_meta( $post_id, '_nntm_lh_loi_mail', true );
				echo '<span style="color:#B26A00" title="' . esc_attr( $loi ) . '">' . esc_html__( 'Không gửi được', 'nntm' ) . '</span>';
			}
			break;

		case 'lh_ngay':
			echo esc_html( (string) get_the_date( 'd/m/Y H:i', $post_id ) );
			break;
	}
}
add_action( 'manage_' . NNTM_LH_LOAI . '_posts_custom_column', 'nntm_lh_ve_cot', 10, 2 );

function nntm_lh_cot_sap_xep( array $cot ): array {
	$cot['lh_ngay'] = 'date';

	return $cot;
}
add_filter( 'manage_edit-' . NNTM_LH_LOAI . '_sortable_columns', 'nntm_lh_cot_sap_xep' );

/**
 * Hộp thông tin người gửi ở màn hình xem một lời nhắn.
 */
function nntm_lh_hop_thong_tin(): void {
	add_meta_box(
		'nntm-lh-nguoi-gui',
		__( 'Người gửi', 'nntm' ),
		'nntm_lh_ve_hop',
		NNTM_LH_LOAI,
		'side',
		'high'
	);
}
add_action( 'add_meta_boxes', 'nntm_lh_hop_thong_tin' );

function nntm_lh_ve_hop( WP_Post $post ): void {
	$ho_ten = (string) get_post_meta( $post->ID, '_nntm_lh_ho_ten', true );
	$email  = (string) get_post_meta( $post->ID, '_nntm_lh_email', true );
	$dt     = (string) get_post_meta( $post->ID, '_nntm_lh_dien_thoai', true );
	$da_gui = '1' === (string) get_post_meta( $post->ID, '_nntm_lh_da_gui_mail', true );
	$loi    = (string) get_post_meta( $post->ID, '_nntm_lh_loi_mail', true );
	?>
	<p><strong><?php esc_html_e( 'Họ và tên', 'nntm' ); ?>:</strong><br /><?php echo esc_html( '' !== $ho_ten ? $ho_ten : '—' ); ?></p>

	<p>
		<strong><?php esc_html_e( 'Email', 'nntm' ); ?>:</strong><br />
		<?php if ( '' !== $email ) : ?>
			<a href="<?php echo esc_attr( 'mailto:' . $email ); ?>"><?php echo esc_html( $email ); ?></a>
		<?php else : ?>
			—
		<?php endif; ?>
	</p>

	<p>
		<strong><?php esc_html_e( 'Điện thoại', 'nntm' ); ?>:</strong><br />
		<?php if ( '' !== $dt ) : ?>
			<a href="<?php echo esc_attr( 'tel:' . preg_replace( '/[^0-9+]/', '', $dt ) ); ?>"><?php echo esc_html( $dt ); ?></a>
		<?php else : ?>
			—
		<?php endif; ?>
	</p>

	<p>
		<strong><?php esc_html_e( 'Email báo cho ban quản trị', 'nntm' ); ?>:</strong><br />
		<?php if ( $da_gui ) : ?>
			<span style="color:#1E7B34"><?php esc_html_e( 'Đã gửi', 'nntm' ); ?></span>
		<?php else : ?>
			<span style="color:#B26A00"><?php esc_html_e( 'Không gửi được', 'nntm' ); ?></span>
			<?php if ( '' !== $loi ) : ?>
				<br /><span class="description"><?php echo esc_html( $loi ); ?></span>
			<?php endif; ?>
			<br /><span class="description"><?php esc_html_e( 'Lời nhắn vẫn được lưu đầy đủ ở đây.', 'nntm' ); ?></span>
		<?php endif; ?>
	</p>
	<?php
}

/**
 * Đếm lời nhắn chưa đọc, hiện bong bóng đỏ cạnh menu.
 */
function nntm_lh_so_chua_doc(): int {
	$so = get_posts(
		array(
			'post_type'      => NNTM_LH_LOAI,
			'post_status'    => 'publish',
			'posts_per_page' => 50,
			'fields'         => 'ids',
			'meta_query'     => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				array(
					'key'     => '_nntm_lh_da_doc',
					'value'   => '1',
					'compare' => '!=',
				),
			),
		)
	);

	return count( $so );
}

function nntm_lh_bong_bong(): void {
	global $menu;

	if ( ! is_array( $menu ) || ! current_user_can( nntm_lh_quyen() ) ) {
		return;
	}

	$so = nntm_lh_so_chua_doc();

	if ( $so < 1 ) {
		return;
	}

	$dich = 'edit.php?post_type=' . NNTM_LH_LOAI;

	foreach ( $menu as $vi_tri => $muc ) {
		if ( isset( $muc[2] ) && $dich === $muc[2] ) {
			$menu[ $vi_tri ][0] .= ' <span class="awaiting-mod"><span class="pending-count">' . (int) $so . '</span></span>'; // phpcs:ignore WordPress.WP.GlobalVariablesOverride
			break;
		}
	}
}
add_action( 'admin_menu', 'nntm_lh_bong_bong', 99 );

/**
 * Mở một lời nhắn ra xem là coi như đã đọc.
 */
function nntm_lh_danh_dau_da_doc(): void {
	if ( ! is_admin() || ! function_exists( 'get_current_screen' ) ) {
		return;
	}

	$man = get_current_screen();

	if ( ! $man instanceof WP_Screen || NNTM_LH_LOAI !== $man->post_type || 'post' !== $man->base ) {
		return;
	}

	$id = isset( $_GET['post'] ) ? absint( wp_unslash( $_GET['post'] ) ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

	if ( $id > 0 ) {
		update_post_meta( $id, '_nntm_lh_da_doc', '1' );
	}
}
add_action( 'current_screen', 'nntm_lh_danh_dau_da_doc' );
