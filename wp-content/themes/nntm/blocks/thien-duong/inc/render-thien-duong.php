<?php

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'nntm_thien_duong_get_tracks' ) ) {
	 
	function nntm_thien_duong_get_tracks( int $posts_per_page, string $order_by ): array {
		$query_args = array(
			'post_type'           => 'nntm_zen_track',
			'post_status'         => 'publish',
			'posts_per_page'      => $posts_per_page,
			'ignore_sticky_posts' => true,
			'no_found_rows'       => true,  
			'meta_query'          => array(  
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

			if ( 'attachment' !== get_post_type( $audio_id ) || 0 !== strpos( (string) get_post_mime_type( $audio_id ), 'audio/' ) ) {
				continue;
			}

			$audio_url = wp_get_attachment_url( $audio_id );
			if ( ! $audio_url ) {

				continue;
			}

			$audio_meta = wp_get_attachment_metadata( $audio_id );
			$duration = isset( $audio_meta['length_formatted'] ) ? (string) $audio_meta['length_formatted'] : '—:—';
			$image_url = get_the_post_thumbnail_url( $track_post, 'thumbnail' );
			$tracks[] = array(
				'id'        => $track_post->ID,
				'title'     => get_the_title( $track_post ),
				'audio_url' => $audio_url,
				'image_url' => $image_url ? $image_url : '',
				'duration'  => $duration,
				'listen_count' => function_exists( 'nntm_zen_track_get_listen_count' )
					? nntm_zen_track_get_listen_count( (int) $track_post->ID )
					: absint( get_post_meta( $track_post->ID, '_nntm_track_listen_count', true ) ),
			);
		}

		wp_reset_postdata();

		return $tracks;
	}
}

if ( ! function_exists( 'nntm_thien_duong_render_login_invite' ) ) {
	 
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

if ( ! function_exists( 'nntm_thien_duong_render_guest_preview' ) ) {
	 
	function nntm_thien_duong_render_guest_preview( int $limit, string $order_by ): string {
		$args = array(
			'post_type'              => 'nntm_zen_track',
			'post_status'            => 'publish',
			'posts_per_page'         => min( 8, max( 1, $limit ) ),
			'ignore_sticky_posts'    => true,
			'no_found_rows'          => true,
			'update_post_meta_cache' => false,
			'update_post_term_cache' => false,
		);
		if ( 'title' === $order_by ) {
			$args['orderby'] = 'title';
			$args['order']   = 'ASC';
		} else {
			$args['orderby'] = 'date';
			$args['order']   = 'oldest' === $order_by ? 'ASC' : 'DESC';
		}
		$posts = get_posts( $args );
		$login_url = wp_login_url( get_permalink() );

		ob_start();
		?>
		<div class="nntm-thien-duong__spotify-preview">
			<div class="nntm-thien-duong__spotify-hero">
				<span class="nntm-thien-duong__spotify-avatar" aria-hidden="true">N</span>
				<div><span class="nntm-thien-duong__spotify-type"><?php esc_html_e( 'Playlist', 'nntm' ); ?></span><strong>Năng Nhân Tịch Mặc</strong><small><?php echo esc_html( sprintf( __( '%d bản thiền ca', 'nntm' ), count( $posts ) ) ); ?></small></div>
			</div>
			<div class="nntm-thien-duong__spotify-toolbar"><a href="<?php echo esc_url( $login_url ); ?>" aria-label="<?php esc_attr_e( 'Đăng nhập để nghe', 'nntm' ); ?>">&#9654;</a><span><?php esc_html_e( 'Danh sách phổ biến', 'nntm' ); ?></span></div>
			<ol class="nntm-thien-duong__spotify-list">
				<?php foreach ( $posts as $index => $track_post ) : ?>
					<li><span><?php echo esc_html( (string) ( $index + 1 ) ); ?></span><div><strong><?php echo esc_html( get_the_title( $track_post ) ); ?></strong><small>Năng Nhân Tịch Mặc</small></div><span aria-hidden="true">—:—</span></li>
				<?php endforeach; ?>
			</ol>
			<a class="nntm-thien-duong__spotify-login" href="<?php echo esc_url( $login_url ); ?>"><?php esc_html_e( 'Đăng nhập để nghe trọn vẹn', 'nntm' ); ?></a>
		</div>
		<?php
		return trim( (string) ob_get_clean() );
	}
}

if ( ! function_exists( 'nntm_thien_duong_render_track_item' ) ) {
	 
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
				data-nntm-track-id="<?php echo esc_attr( (string) $track['id'] ); ?>"
				data-nntm-track-image="<?php echo esc_url( $track['image_url'] ); ?>"
				aria-label="<?php echo esc_attr( sprintf(   __( 'Nghe bài "%s"', 'nntm' ), $title ) ); ?>"
			>
				<span class="nntm-thien-duong__track-index" aria-hidden="true"><?php echo esc_html( (string) ( $index + 1 ) ); ?></span>
				<span class="nntm-thien-duong__track-title"><?php echo esc_html( $title ); ?></span>
				<span class="nntm-thien-duong__track-listens"><span class="nntm-thien-duong__track-listen-count"><?php echo esc_html( number_format_i18n( $track['listen_count'] ) ); ?></span> <?php esc_html_e( 'lượt nghe', 'nntm' ); ?></span>
				<span class="nntm-thien-duong__track-duration"><?php echo esc_html( $track['duration'] ); ?></span>
			</button>
		</li>
		<?php
		return trim( (string) ob_get_clean() );
	}
}

if ( ! function_exists( 'nntm_thien_duong_render_player' ) ) {
	 
	function nntm_thien_duong_render_player( array $tracks ): string {
		$realtime_ready = function_exists( 'nntm_zen_track_realtime_is_ready' ) && nntm_zen_track_realtime_is_ready();
		if ( $realtime_ready && function_exists( 'nntm_zen_track_enqueue_realtime_assets' ) ) {
			nntm_zen_track_enqueue_realtime_assets();
		}

		 
		$uid = wp_unique_id( 'nntm-thien-duong-' );

		ob_start();
		?>
		<div class="nntm-thien-duong__player-inner" data-nntm-thien-duong="1" data-listen-nonce="<?php echo esc_attr( has_action( 'wp_ajax_nntm_track_listen' ) ? wp_create_nonce( 'nntm_track_listen' ) : '' ); ?>" data-ajax-url="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>" data-current-track-id="">
			<audio class="nntm-thien-duong__audio" preload="none"></audio>
			<div class="nntm-thien-duong__live-status" aria-live="polite" <?php echo $realtime_ready ? '' : 'hidden'; ?>>
				<span class="nntm-thien-duong__live-item nntm-thien-duong__live-item--track" title="<?php esc_attr_e( 'Người đang nghe bài hiện tại', 'nntm' ); ?>">
					<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 14v-2a8 8 0 0 1 16 0v2"/><path d="M4 14h3v6H5a1 1 0 0 1-1-1v-5zM20 14h-3v6h2a1 1 0 0 0 1-1v-5z"/></svg>
					<strong data-nntm-track-presence-count>—</strong>
					<span class="screen-reader-text"><?php esc_html_e( 'người đang nghe bài hiện tại', 'nntm' ); ?></span>
				</span>
				<span class="nntm-thien-duong__live-item nntm-thien-duong__live-item--page" title="<?php esc_attr_e( 'Người đang ở Thiền Đường', 'nntm' ); ?>">
					<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M16 20v-1.5A3.5 3.5 0 0 0 12.5 15h-5A3.5 3.5 0 0 0 4 18.5V20"/><circle cx="10" cy="8" r="3"/><path d="M18 11a2.5 2.5 0 1 0-1.5-4.5M17 15h.5A3.5 3.5 0 0 1 21 18.5V20"/></svg>
					<strong data-nntm-page-presence-count>—</strong>
					<span class="screen-reader-text"><?php esc_html_e( 'người đang ở Thiền Đường', 'nntm' ); ?></span>
				</span>
			</div>
			<div class="nntm-thien-duong__spotify-hero nntm-thien-duong__spotify-hero--member">
				<span class="nntm-thien-duong__spotify-avatar" aria-hidden="true">
					<?php if ( ! empty( $tracks[0]['image_url'] ) ) : ?><img src="<?php echo esc_url( $tracks[0]['image_url'] ); ?>" alt="" /><?php else : ?>N<?php endif; ?>
				</span>
				<div>
					<span class="nntm-thien-duong__spotify-type"><?php esc_html_e( 'Playlist', 'nntm' ); ?></span>
					<strong>Năng Nhân Tịch Mặc</strong>
					<small><?php echo esc_html( sprintf( __( '%d bản thiền ca', 'nntm' ), count( $tracks ) ) ); ?></small>
				</div>
			</div>

			<div class="nntm-thien-duong__controls nntm-thien-duong__spotify-controls">
				<button type="button" class="nntm-thien-duong__btn nntm-thien-duong__btn--prev" aria-label="<?php esc_attr_e( 'Bài trước', 'nntm' ); ?>">
					<span class="nntm-thien-duong__btn-icon" aria-hidden="true">&#9664;</span>
				</button>

				<button type="button" class="nntm-thien-duong__btn nntm-thien-duong__btn--play" aria-pressed="false" aria-label="<?php esc_attr_e( 'Phát', 'nntm' ); ?>">
					<span class="nntm-thien-duong__btn-icon" aria-hidden="true">&#9654;</span>
				</button>

				<button type="button" class="nntm-thien-duong__btn nntm-thien-duong__btn--next" aria-label="<?php esc_attr_e( 'Bài sau', 'nntm' ); ?>">
					<span class="nntm-thien-duong__btn-icon" aria-hidden="true">&#9654;</span>
				</button>
				<p class="nntm-thien-duong__now-title" aria-live="polite"><?php esc_html_e( 'Chọn một bản thiền ca để bắt đầu', 'nntm' ); ?></p>
				<p class="nntm-thien-duong__playback-error" hidden aria-live="assertive"></p>

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

				<div class="nntm-thien-duong__player-tools">
				<div class="nntm-thien-duong__volume">
					<button type="button" class="nntm-thien-duong__tool-btn nntm-thien-duong__mute" aria-pressed="false" aria-label="<?php esc_attr_e( 'Tắt âm', 'nntm' ); ?>">
						<svg viewBox="0 0 24 24" aria-hidden="true"><path class="nntm-speaker-body" d="M4 9v6h4l5 4V5L8 9H4z"/><path class="nntm-speaker-wave nntm-speaker-wave--one" d="M16 9.5c.8.7 1.2 1.5 1.2 2.5s-.4 1.8-1.2 2.5"/><path class="nntm-speaker-wave nntm-speaker-wave--two" d="M18.5 7c1.5 1.3 2.3 3 2.3 5s-.8 3.7-2.3 5"/><path class="nntm-speaker-mute-line" d="M16 9l5 6M21 9l-5 6"/></svg>
					</button>
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
				<div class="nntm-thien-duong__settings">
					<button type="button" class="nntm-thien-duong__tool-btn nntm-thien-duong__settings-toggle" aria-expanded="false" aria-controls="<?php echo esc_attr( $uid ); ?>-speed-menu" aria-label="<?php esc_attr_e( 'Cài đặt tốc độ phát', 'nntm' ); ?>">
						<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 8.5a3.5 3.5 0 1 0 0 7 3.5 3.5 0 0 0 0-7z"/><path d="M19.4 13.5a7.6 7.6 0 0 0 0-3l2-1.5-2-3.5-2.5 1a8.8 8.8 0 0 0-2.6-1.5L14 2h-4l-.4 3A8.8 8.8 0 0 0 7 6.5l-2.5-1-2 3.5 2 1.5a7.6 7.6 0 0 0 0 3l-2 1.5 2 3.5 2.5-1a8.8 8.8 0 0 0 2.6 1.5l.4 3h4l.4-3a8.8 8.8 0 0 0 2.6-1.5l2.5 1 2-3.5-2.1-1.5z"/></svg>
					</button>
					<div class="nntm-thien-duong__speed-menu" id="<?php echo esc_attr( $uid ); ?>-speed-menu" hidden>
						<strong><?php esc_html_e( 'Tốc độ phát', 'nntm' ); ?></strong>
						<?php foreach ( array( '0.75' => '0.75×', '1' => 'Bình thường', '1.25' => '1.25×', '1.5' => '1.5×', '2' => '2×' ) as $rate => $label ) : ?>
							<button type="button" class="nntm-thien-duong__speed-option<?php echo '1' === $rate ? ' is-active' : ''; ?>" data-rate="<?php echo esc_attr( $rate ); ?>" aria-pressed="<?php echo '1' === $rate ? 'true' : 'false'; ?>"><span class="nntm-thien-duong__speed-check" aria-hidden="true"><?php echo '1' === $rate ? '&#10003;' : ''; ?></span><?php echo esc_html( $label ); ?></button>
						<?php endforeach; ?>
					</div>
				</div>
				</div>
			</div>

			<div class="nntm-thien-duong__playlist-title">
				<strong><?php esc_html_e( 'Phổ biến', 'nntm' ); ?></strong>
			</div>

			<ol class="nntm-thien-duong__tracklist">
				<?php
				foreach ( $tracks as $index => $track ) {
					echo nntm_thien_duong_render_track_item( $track, $index );  
				}
				?>
			</ol>
		</div>
		<?php
		return trim( (string) ob_get_clean() );
	}
}
