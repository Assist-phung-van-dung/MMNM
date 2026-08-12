<?php
/**
 * Hàm dựng HTML + truy vấn dùng chung cho render.php của block
 * nntm/thien-duong.
 *
 * Tách riêng ra file inc/ (bắt chước đúng blocks/article-mosaic/inc/) vì
 * render.php của block bị WordPress core `require` (KHÔNG PHẢI
 * `require_once`) mỗi lần block render. Nếu khai hàm thẳng trong
 * render.php, một trang gọi lại khối này lần thứ hai sẽ chết với lỗi
 * "Cannot redeclare function". File inc/ này được require_once từ
 * render.php nên chỉ khai báo đúng một lần dù render.php bị require lại
 * bao nhiêu lần.
 *
 * QUAN TRỌNG VỀ RÒ RỈ: hàm nntm_thien_duong_get_tracks() và
 * nntm_thien_duong_render_player() CHỈ được render.php gọi tới khi
 * is_user_logged_in() đã trả về true — bản thân các hàm này không tự
 * kiểm tra lại việc đăng nhập. Đường dẫn tệp âm thanh (wp_get_attachment_url)
 * chỉ xuất hiện trong HTML qua nhánh gọi này; nhánh chưa đăng nhập trong
 * render.php gọi nntm_thien_duong_render_login_invite() — hàm này không
 * đụng tới WP_Query hay meta âm thanh nào cả.
 *
 * @package NNTM
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'nntm_thien_duong_get_tracks' ) ) {
	/**
	 * Truy vấn CPT nntm_zen_track, chỉ lấy bài đã gán tệp âm thanh hợp lệ.
	 *
	 * Bài chưa nhập meta "_nntm_track_audio" (hoặc nhập 0) bị loại ngay ở
	 * lớp truy vấn (meta_query compare '>' tự INNER JOIN, bài không có
	 * dòng meta sẽ không khớp) — đúng yêu cầu "bỏ qua, không hiện, không
	 * gây lỗi". Bài có meta nhưng tệp đã bị xóa khỏi Media Library (ID cũ
	 * không còn attachment) được lọc thêm một lần nữa ở PHP vì
	 * wp_get_attachment_url() trả về false trong trường hợp đó.
	 *
	 * @param int    $posts_per_page Số bài tối đa (đã giới hạn 1–50 ở render.php).
	 * @param string $order_by       'newest' | 'oldest' | 'title'.
	 * @return array<int, array{id: int, title: string, audio_url: string}>
	 */
	function nntm_thien_duong_get_tracks( int $posts_per_page, string $order_by ): array {
		$query_args = array(
			'post_type'           => 'nntm_zen_track',
			'post_status'         => 'publish',
			'posts_per_page'      => $posts_per_page,
			'ignore_sticky_posts' => true,
			'no_found_rows'       => true, // khong phan trang o khoi nay.
			'meta_query'          => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- can loc bai co gan tep am thanh, khong tranh duoc.
				array(
					'key'     => '_nntm_track_audio',
					'value'   => 0,
					'compare' => '>',
					'type'    => 'NUMERIC',
				),
			),
		);

		switch ( $order_by ) {
			case 'oldest':
				$query_args['orderby'] = 'date';
				$query_args['order']   = 'ASC';
				break;

			case 'title':
				$query_args['orderby'] = 'title';
				$query_args['order']   = 'ASC';
				break;

			case 'newest':
			default:
				$query_args['orderby'] = 'date';
				$query_args['order']   = 'DESC';
				break;
		}

		$query = new WP_Query( $query_args );

		$tracks = array();

		foreach ( $query->posts as $track_post ) {
			$audio_id = absint( get_post_meta( $track_post->ID, '_nntm_track_audio', true ) );
			if ( ! $audio_id ) {
				continue;
			}

			$audio_url = wp_get_attachment_url( $audio_id );
			if ( ! $audio_url ) {
				// Meta con ID cu nhung tep da bi xoa khoi Media Library — bo qua,
				// khong bao loi, dung nhu yeu cau "bai chua co tep -> bo qua".
				continue;
			}

			$tracks[] = array(
				'id'        => $track_post->ID,
				'title'     => get_the_title( $track_post ),
				'audio_url' => $audio_url,
			);
		}

		wp_reset_postdata();

		return $tracks;
	}
}

if ( ! function_exists( 'nntm_thien_duong_render_login_invite' ) ) {
	/**
	 * Dựng HTML lời mời đăng nhập cho người chưa đăng nhập. KHÔNG chạy
	 * WP_Query, KHÔNG đọc meta âm thanh nào — đảm bảo tuyệt đối không có
	 * đường dẫn .mp3/.m4a/.ogg nào lọt ra HTML ở nhánh này.
	 *
	 * @return string HTML đã escape.
	 */
	function nntm_thien_duong_render_login_invite(): string {
		$login_url = wp_login_url( get_permalink() );

		ob_start();
		?>
		<div class="nntm-thien-duong__invite">
			<p class="nntm-thien-duong__invite-text">
				<?php esc_html_e( 'Kính mời quý đạo hữu đăng nhập để vào Thiền Đường nghe nhạc thiền.', 'nntm' ); ?>
			</p>
			<a class="nntm-thien-duong__invite-cta" href="<?php echo esc_url( $login_url ); ?>">
				<?php esc_html_e( 'Đăng nhập để nghe', 'nntm' ); ?>
			</a>
		</div>
		<?php
		return trim( (string) ob_get_clean() );
	}
}

if ( ! function_exists( 'nntm_thien_duong_render_track_item' ) ) {
	/**
	 * Dựng HTML một dòng trong danh sách bài — nút bấm chọn được, đường
	 * dẫn âm thanh nằm trong data-attribute để view.js đọc và gán vào
	 * thẻ <audio> khi được bấm.
	 *
	 * @param array $track Một phần tử trả về từ nntm_thien_duong_get_tracks().
	 * @param int   $index Vị trí trong danh sách (0-based), dùng để đánh số hiển thị.
	 * @return string HTML đã escape.
	 */
	function nntm_thien_duong_render_track_item( array $track, int $index ): string {
		$title = (string) $track['title'];

		ob_start();
		?>
		<li class="nntm-thien-duong__track-item">
			<button
				type="button"
				class="nntm-thien-duong__track"
				data-nntm-audio-src="<?php echo esc_url( $track['audio_url'] ); ?>"
				data-nntm-track-title="<?php echo esc_attr( $title ); ?>"
				aria-label="<?php echo esc_attr( sprintf( /* translators: %s: ten bai nhac thien */ __( 'Nghe bài "%s"', 'nntm' ), $title ) ); ?>"
			>
				<span class="nntm-thien-duong__track-index" aria-hidden="true"><?php echo esc_html( (string) ( $index + 1 ) ); ?></span>
				<span class="nntm-thien-duong__track-title"><?php echo esc_html( $title ); ?></span>
				<span class="nntm-thien-duong__track-status"></span>
			</button>
		</li>
		<?php
		return trim( (string) ob_get_clean() );
	}
}

if ( ! function_exists( 'nntm_thien_duong_render_player' ) ) {
	/**
	 * Dựng toàn bộ HTML trình phát (thẻ audio, nút điều khiển, thanh tiến
	 * độ/âm lượng, chỗ cắm hiển thị số người đang nghe, danh sách bài) —
	 * chỉ được gọi khi đã xác nhận is_user_logged_in() ở render.php.
	 *
	 * @param array $tracks Danh sách bài từ nntm_thien_duong_get_tracks().
	 * @return string HTML đã escape.
	 */
	function nntm_thien_duong_render_player( array $tracks ): string {
		// wp_unique_id() để nhãn <label for="…"> không đụng ID nếu khối này
		// (hiếm khi) xuất hiện nhiều lần trên cùng một trang.
		$uid = wp_unique_id( 'nntm-thien-duong-' );

		ob_start();
		?>
		<div class="nntm-thien-duong__player-inner" data-nntm-thien-duong="1">
			<?php // Một thẻ <audio> duy nhất, đổi src khi chọn bài — không có nguồn nào gán sẵn nên không tự phát khi tải trang. ?>
			<audio class="nntm-thien-duong__audio" preload="none"></audio>

			<p class="nntm-thien-duong__now-title" aria-live="polite">
				<?php esc_html_e( 'Chưa chọn bản nhạc — bấm vào một bài trong danh sách bên dưới để bắt đầu.', 'nntm' ); ?>
			</p>

			<div class="nntm-thien-duong__controls">
				<button type="button" class="nntm-thien-duong__btn nntm-thien-duong__btn--prev" aria-label="<?php esc_attr_e( 'Bài trước', 'nntm' ); ?>">
					<span class="nntm-thien-duong__btn-icon" aria-hidden="true">&#9198;</span>
				</button>

				<button type="button" class="nntm-thien-duong__btn nntm-thien-duong__btn--play" aria-pressed="false" aria-label="<?php esc_attr_e( 'Phát', 'nntm' ); ?>">
					<span class="nntm-thien-duong__btn-icon" aria-hidden="true">&#9654;</span>
				</button>

				<button type="button" class="nntm-thien-duong__btn nntm-thien-duong__btn--next" aria-label="<?php esc_attr_e( 'Bài sau', 'nntm' ); ?>">
					<span class="nntm-thien-duong__btn-icon" aria-hidden="true">&#9197;</span>
				</button>

				<div class="nntm-thien-duong__progress">
					<label class="nntm-thien-duong__range-label" for="<?php echo esc_attr( $uid ); ?>-progress">
						<?php esc_html_e( 'Tiến độ', 'nntm' ); ?>
					</label>
					<input
						type="range"
						class="nntm-thien-duong__range nntm-thien-duong__range--progress"
						id="<?php echo esc_attr( $uid ); ?>-progress"
						min="0"
						max="100"
						step="0.1"
						value="0"
					/>
					<span class="nntm-thien-duong__time" aria-hidden="true">
						<span class="nntm-thien-duong__time-current">0:00</span>&nbsp;/&nbsp;<span class="nntm-thien-duong__time-duration">0:00</span>
					</span>
				</div>

				<div class="nntm-thien-duong__volume">
					<label class="nntm-thien-duong__range-label" for="<?php echo esc_attr( $uid ); ?>-volume">
						<?php esc_html_e( 'Âm lượng', 'nntm' ); ?>
					</label>
					<input
						type="range"
						class="nntm-thien-duong__range nntm-thien-duong__range--volume"
						id="<?php echo esc_attr( $uid ); ?>-volume"
						min="0"
						max="100"
						step="1"
						value="80"
					/>
				</div>
			</div>

			<?php
			/*
			 * CHỖ CẮM SOKETI (docs/04-kien-truc.md mục 5) — phần kết nối kênh
			 * presence "presence-thien-duong" và gọi API Soketi thuộc plugin
			 * nntm-audio, làm ở giai đoạn sau (Soketi chưa dựng được ở local).
			 * Đây chỉ là chỗ hiển thị: text mặc định lịch sự, cập nhật qua hàm
			 * toàn cục window.nntmThienDuongSetPresence(soNguoi) khai trong
			 * view.js — phần Soketi sau này chỉ cần gọi hàm đó, không phải sửa
			 * lại block này.
			 */
			?>
			<p class="nntm-thien-duong__presence" data-nntm-presence-channel="presence-thien-duong" aria-live="polite">
				<?php esc_html_e( 'Đang kết nối…', 'nntm' ); ?>
			</p>

			<ol class="nntm-thien-duong__tracklist">
				<?php
				foreach ( $tracks as $index => $track ) {
					echo nntm_thien_duong_render_track_item( $track, $index ); // phpcs:ignore WordPress.Security.EscapeOutput -- ham con da tu esc trong.
				}
				?>
			</ol>
		</div>
		<?php
		return trim( (string) ob_get_clean() );
	}
}
