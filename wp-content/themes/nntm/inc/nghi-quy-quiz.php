<?php
/**
 * Giao diện cho quiz gác cửa Nghi Quỹ.
 *
 * Nghiệp vụ (câu hỏi, đáp án đúng, chấm bài, session PASS, chặn nội dung) nằm ở
 * plugin: nntm-core/includes/class-nghi-quy-quiz.php. File này chỉ lo phần nhìn:
 * gắn dấu vào thẻ Nghi Quỹ, dựng popup, nạp CSS/JS. Gỡ plugin thì mọi thứ ở đây
 * tự tắt chứ không vỡ trang.
 *
 * @package NNTM
 */

defined( 'ABSPATH' ) || exit;

/**
 * Plugin nghiệp vụ có đang chạy không?
 */
function nntm_quiz_kha_dung(): bool {
	return class_exists( '\NNTM\Core\Nghi_Quy_Quiz' );
}

/**
 * Ấn phẩm này có bị khoá bằng bộ câu hỏi không?
 *
 * @param int $post_id ID ấn phẩm.
 */
function nntm_quiz_can_hoi( int $post_id ): bool {
	if ( ! nntm_quiz_kha_dung() ) {
		return false;
	}

	return \NNTM\Core\Nghi_Quy_Quiz::can_quiz( $post_id );
}

/**
 * Người dùng hiện tại đã đậu quiz của Nghi Quỹ này trong session này chưa?
 *
 * @param int $post_id ID ấn phẩm.
 */
function nntm_quiz_da_pass( int $post_id ): bool {
	if ( ! nntm_quiz_kha_dung() ) {
		return false;
	}

	return \NNTM\Core\Nghi_Quy_Quiz::da_pass( $post_id );
}

/**
 * Còn phải hỏi người dùng hiện tại không? (bị khoá và chưa đậu)
 *
 * @param int $post_id ID ấn phẩm.
 */
function nntm_quiz_con_chan( int $post_id ): bool {
	return nntm_quiz_can_hoi( $post_id ) && ! nntm_quiz_da_pass( $post_id );
}

/**
 * Chuỗi thuộc tính data gắn vào thẻ/nút dẫn tới một Nghi Quỹ bị khoá.
 *
 * Chưa đăng nhập  -> mượn modal đăng nhập sẵn có, kèm đích quay về đúng Nghi Quỹ.
 * Đã đăng nhập    -> đánh dấu để JS chặn lại và mở popup câu hỏi.
 * Không bị khoá   -> trả về chuỗi rỗng, thẻ giữ nguyên hành vi cũ.
 *
 * @param int $post_id ID ấn phẩm.
 */
function nntm_quiz_thuoc_tinh_the( int $post_id ): string {
	if ( ! nntm_quiz_con_chan( $post_id ) ) {
		return '';
	}

	$permalink = (string) get_permalink( $post_id );

	if ( ! is_user_logged_in() ) {
		return ' data-nntm-auth-modal="dang-nhap" data-nntm-auth-redirect="' . esc_url( $permalink ) . '"';
	}

	return ' data-nntm-quiz="' . esc_attr( (string) $post_id ) . '"';
}

/**
 * Trang đang xem có cần tới popup quiz không?
 *
 * Khách chưa đăng nhập không cần: họ gặp modal đăng nhập trước đã.
 */
function nntm_quiz_can_tai_asset(): bool {
	return nntm_quiz_kha_dung() && ! is_admin() && is_user_logged_in();
}

/**
 * Nghi Quỹ cần mở popup ngay khi trang tải xong (0 = không mở sẵn).
 *
 * Đây là trường hợp người dùng vừa bấm vào Nghi Quỹ rồi bị đưa về trang chi
 * tiết vì chưa đậu: hỏi luôn, khỏi bắt bấm thêm một lần nữa.
 */
function nntm_quiz_tu_mo(): int {
	if ( ! is_singular( 'nntm_publication' ) ) {
		return 0;
	}

	$post_id = (int) get_queried_object_id();

	return nntm_quiz_con_chan( $post_id ) && is_user_logged_in() ? $post_id : 0;
}

/**
 * Nạp CSS/JS của popup.
 */
function nntm_quiz_enqueue_assets(): void {
	if ( ! nntm_quiz_can_tai_asset() ) {
		return;
	}

	$css = NNTM_THEME_DIR . '/assets/css/nghi-quy-quiz.css';
	$js  = NNTM_THEME_DIR . '/assets/js/nghi-quy-quiz.js';

	if ( ! is_readable( $css ) || ! is_readable( $js ) ) {
		return;
	}

	wp_enqueue_style(
		'nntm-nghi-quy-quiz',
		NNTM_THEME_URI . '/assets/css/nghi-quy-quiz.css',
		array( 'nntm-tokens', 'nntm-base' ),
		nntm_asset_version( $css )
	);

	wp_enqueue_script(
		'nntm-nghi-quy-quiz',
		NNTM_THEME_URI . '/assets/js/nghi-quy-quiz.js',
		array(),
		nntm_asset_version( $js ),
		true
	);

	wp_localize_script(
		'nntm-nghi-quy-quiz',
		'nntmNghiQuyQuiz',
		array(
			'ajaxUrl'  => admin_url( 'admin-ajax.php' ),
			'nonce'    => wp_create_nonce( \NNTM\Core\Nghi_Quy_Quiz::NONCE ),
			'autoOpen' => nntm_quiz_tu_mo(),
			'i18n'     => array(
				'dangTai'  => __( 'Đang tải câu hỏi…', 'nntm' ),
				'loiMang'  => __( 'Không kết nối được. Vui lòng thử lại.', 'nntm' ),
				'chuaChon' => __( 'Vui lòng trả lời tất cả câu hỏi.', 'nntm' ),
				'dangCham' => __( 'Đang kiểm tra…', 'nntm' ),
				'tieuDe'   => __( 'Trước khi xem Nghi Quỹ', 'nntm' ),
				/* translators: %1$d: số câu đã trả lời, %2$d: tổng số câu. */
				'tienDo'   => __( 'Đã trả lời %1$d/%2$d', 'nntm' ),
			),
		)
	);
}
add_action( 'wp_enqueue_scripts', 'nntm_quiz_enqueue_assets', 32 );

/**
 * Khung popup — dựng sẵn rỗng, JS đổ câu hỏi vào sau khi hỏi máy chủ.
 *
 * Câu hỏi KHÔNG in sẵn vào HTML: có in cũng chẳng lộ đáp án, nhưng để máy chủ
 * trả về theo từng lần mở thì trạng thái PASS và cấu hình mới nhất luôn đúng.
 */
function nntm_quiz_render_modal(): void {
	if ( ! nntm_quiz_can_tai_asset() ) {
		return;
	}
	?>
	<div class="nntm-quiz-modal" id="nntm-quiz-modal" data-nntm-quiz-modal hidden>
		<div class="nntm-quiz-modal__overlay" data-nntm-quiz-close></div>

		<div class="nntm-quiz-modal__panel" role="dialog" aria-modal="true" aria-labelledby="nntm-quiz-modal-title">
			<button type="button" class="nntm-quiz-modal__close" data-nntm-quiz-close>
				<span class="nntm-sr-only"><?php esc_html_e( 'Đóng', 'nntm' ); ?></span>
				<span aria-hidden="true">&times;</span>
			</button>

			<?php
			/*
			 * Đầu khung đứng yên, chỉ vùng câu hỏi ở giữa cuộn. Bộ ba câu hỏi trên
			 * màn hình điện thoại dài hơn một màn, để cuộn cả khung thì tiêu đề và
			 * nút gửi trôi mất.
			 */
			?>
			<header class="nntm-quiz-modal__header">
				<p class="nntm-quiz-modal__eyebrow"><?php esc_html_e( 'Nghi Quỹ', 'nntm' ); ?></p>
				<h2 class="nntm-quiz-modal__title" id="nntm-quiz-modal-title"><?php esc_html_e( 'Trước khi xem Nghi Quỹ', 'nntm' ); ?></h2>
				<p class="nntm-quiz-modal__phu"><?php esc_html_e( 'Xin trả lời đúng tất cả câu hỏi để vào phần hành trì. Trả lời chưa đúng thì làm lại được, không giới hạn số lần.', 'nntm' ); ?></p>
			</header>

			<form class="nntm-quiz-modal__form" id="nntm-quiz-form" data-nntm-quiz-form hidden>
				<div class="nntm-quiz-modal__than">
					<div data-nntm-quiz-questions></div>
				</div>

				<div class="nntm-quiz-modal__actions">
					<span class="nntm-quiz-modal__tien-do" data-nntm-quiz-tien-do aria-live="polite"></span>
					<button type="submit" class="nntm-quiz-modal__submit" data-nntm-quiz-submit><?php esc_html_e( 'Gửi câu trả lời', 'nntm' ); ?></button>
				</div>
			</form>

			<?php
			/*
			 * Ô trạng thái nằm NGOÀI form: lúc trả lời sai, JS gỡ hết câu hỏi và
			 * giấu form đi, chỉ còn lại thông báo — nếu ô này ở trong form thì nó
			 * biến mất theo và người dùng không thấy vì sao mình bị chặn.
			 */
			?>
			<div class="nntm-quiz-modal__khay-trang-thai">
				<p class="nntm-quiz-modal__status" data-nntm-quiz-status role="status" aria-live="polite"></p>

				<button type="button" class="nntm-quiz-modal__lam-lai" data-nntm-quiz-retry hidden>
					<?php esc_html_e( 'Trả lời lại', 'nntm' ); ?>
				</button>
			</div>
		</div>
	</div>
	<?php
}
add_action( 'wp_footer', 'nntm_quiz_render_modal' );
