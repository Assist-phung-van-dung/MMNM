<?php

defined( 'ABSPATH' ) || exit;

function nntm_card_list_extract_youtube_id( string $raw_value ): string {
	$value = trim( $raw_value );

	if ( '' === $value ) {
		return '';
	}

	if ( preg_match( '/^[A-Za-z0-9_-]{11}$/', $value ) ) {
		return $value;
	}

	if ( preg_match( '#(?:youtube(?:-nocookie)?\.com/(?:watch\?v=|embed/|shorts/|v/)|youtu\.be/)([A-Za-z0-9_-]{11})#i', $value, $matches ) ) {
		return $matches[1];
	}

	return '';
}

function nntm_card_list_split_youtube_line( string $raw_line ): array {
	$line = trim( $raw_line );

	if ( '' === $line ) {
		return array(
			'id'    => '',
			'title' => '',
		);
	}

	$parts     = explode( '|', $line, 2 );
	$link_part = trim( $parts[0] );
	$title     = isset( $parts[1] ) ? sanitize_text_field( trim( $parts[1] ) ) : '';

	return array(
		'id'    => nntm_card_list_extract_youtube_id( $link_part ),
		'title' => $title,
	);
}

function nntm_card_list_parse_youtube_items( string $raw ): array {
	if ( '' === trim( $raw ) ) {
		return array();
	}

	$lines = preg_split( '/[\r\n]+/', $raw );
	$items = array();
	$seen  = array();

	foreach ( (array) $lines as $line ) {
		$parsed = nntm_card_list_split_youtube_line( (string) $line );

		if ( '' === $parsed['id'] || isset( $seen[ $parsed['id'] ] ) ) {
			continue;
		}

		$seen[ $parsed['id'] ] = true;
		$items[]               = $parsed;
	}

	return array_slice( $items, 0, 30 );
}

function nntm_card_list_get_video_title( string $video_id, string $explicit_title ): string {
	if ( '' !== $explicit_title ) {
		return $explicit_title;
	}

	if ( '' === $video_id ) {
		return '';
	}

	$transient_key = 'nntm_yt_title_' . $video_id;
	$cached        = get_transient( $transient_key );

	if ( false !== $cached ) {

		return (string) $cached;
	}

	$oembed_url = add_query_arg(
		array(
			'url'    => 'https://www.youtube.com/watch?v=' . rawurlencode( $video_id ),
			'format' => 'json',
		),
		'https://www.youtube.com/oembed'
	);

	$response = wp_remote_get(
		$oembed_url,
		array(
			'timeout'    => 3,  
			'redirection' => 2,
		)
	);

	if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {

		 
		set_transient( $transient_key, '', HOUR_IN_SECONDS );
		return '';
	}

	$body = json_decode( (string) wp_remote_retrieve_body( $response ), true );
	$title = ( is_array( $body ) && isset( $body['title'] ) ) ? sanitize_text_field( (string) $body['title'] ) : '';

	set_transient( $transient_key, $title, WEEK_IN_SECONDS );

	return $title;
}

function nntm_card_list_render_youtube_item( string $video_id, string $title, bool $aria_hidden_dup = false, bool $framed = false ): string {
	$id_attr  = esc_attr( $video_id );
	$max_url  = esc_url( 'https://img.youtube.com/vi/' . $video_id . '/maxresdefault.jpg' );
	$fallback = esc_url( 'https://img.youtube.com/vi/' . $video_id . '/hqdefault.jpg' );

	$tabindex    = $aria_hidden_dup ? '-1' : '0';
	$aria_hidden = $aria_hidden_dup ? ' aria-hidden="true"' : '';

	$cell_class  = 'nntm-card-list__yt-cell' . ( $framed ? ' nntm-card-list__yt-cell--framed' : '' );
	$item_class  = 'nntm-card-list__yt-item' . ( $framed ? ' nntm-card-list__yt-item--framed' : '' );
	$title_class = 'nntm-card-list__yt-title' . ( $framed ? ' nntm-card-list__yt-title--3-dong' : ' nntm-cat-2-dong' );

	 
	ob_start();
	?>
	<img class="nntm-card-list__yt-thumb" src="<?php echo $max_url; ?>" data-fallback="<?php echo $fallback; ?>" alt="" loading="lazy" decoding="async" />
	<span class="nntm-card-list__yt-play" aria-hidden="true">
		<svg viewBox="0 0 48 48" width="36" height="36" fill="none" focusable="false">
			<circle cx="24" cy="24" r="23" stroke="currentColor" stroke-width="2" />
			<path d="M19 15.5 L34 24 L19 32.5 Z" fill="currentColor" />
		</svg>
	</span>
	<div class="nntm-card-list__yt-frame" aria-hidden="true"></div>
	<?php
	$media_html = (string) ob_get_clean();

	ob_start();
	if ( $framed ) :

		 
		?>
		<div class="<?php echo esc_attr( $cell_class ); ?>">
			<div class="<?php echo esc_attr( $item_class ); ?>" data-video-id="<?php echo $id_attr; ?>" tabindex="<?php echo esc_attr( $tabindex ); ?>" role="button"<?php echo $aria_hidden;  ?> aria-label="<?php esc_attr_e( 'Xem thử video', 'nntm' ); ?>">
				<div class="nntm-card-list__yt-media"><?php echo $media_html;  ?></div>
				<?php if ( '' !== $title ) : ?>
					<p class="<?php echo esc_attr( $title_class ); ?>"><?php echo esc_html( $title ); ?></p>
				<?php endif; ?>
			</div>
		</div>
		<?php
	else :
		 
		?>
		<div class="<?php echo esc_attr( $cell_class ); ?>">
			<div class="<?php echo esc_attr( $item_class ); ?>" data-video-id="<?php echo $id_attr; ?>" tabindex="<?php echo esc_attr( $tabindex ); ?>" role="button"<?php echo $aria_hidden;  ?> aria-label="<?php esc_attr_e( 'Xem thử video', 'nntm' ); ?>">
				<?php echo $media_html;  ?>
			</div>
			<?php if ( '' !== $title ) : ?>
				<p class="<?php echo esc_attr( $title_class ); ?>"<?php echo $aria_hidden;  ?>><?php echo esc_html( $title ); ?></p>
			<?php endif; ?>
		</div>
		<?php
	endif;
	return trim( (string) ob_get_clean() );
}

function nntm_card_list_repeat_youtube_items_for_width( array $items, bool $framed = false ): array {
	$count = count( $items );
	if ( 0 === $count ) {
		return $items;
	}

	 
	 
	$card_width = $framed ? 388 : 348;
	$gap        = $framed ? 20 : 60;

	$assumed_max_container_width = 2600;
	$min_strip_width             = 2 * $assumed_max_container_width;

	$target_item_count = (int) ceil( ( $min_strip_width + $gap ) / ( $card_width + $gap ) );
	$repeats           = max( 1, (int) ceil( $target_item_count / $count ) );

	 
	$repeats = min( $repeats, 40 );

	$result = array();
	for ( $i = 0; $i < $repeats; $i++ ) {
		array_push( $result, ...$items );
	}

	return $result;
}

function nntm_card_list_render_youtube_marquee( array $items, bool $framed = false ): string {
	if ( empty( $items ) ) {
		return '';
	}

	 
	 
	$titles_by_id = array();
	foreach ( $items as $item ) {
		if ( ! isset( $titles_by_id[ $item['id'] ] ) ) {
			$titles_by_id[ $item['id'] ] = nntm_card_list_get_video_title( $item['id'], $item['title'] );
		}
	}

	$unique_count = count( $items );
	$filled_items = nntm_card_list_repeat_youtube_items_for_width( $items, $framed );

	 
	 
	$duration_seconds = max( 20, count( $filled_items ) * 5 );

	$marquee_class = 'nntm-card-list__yt-marquee' . ( $framed ? ' nntm-card-list__yt-marquee--framed' : '' );

	ob_start();
	?>
	<div class="<?php echo esc_attr( $marquee_class ); ?>">
		<div class="nntm-card-list__yt-track" style="--nntm-yt-duration: <?php echo esc_attr( (string) $duration_seconds ); ?>s;">
			<?php foreach ( $filled_items as $i => $item ) : ?>
				<?php echo nntm_card_list_render_youtube_item( $item['id'], $titles_by_id[ $item['id'] ], $i >= $unique_count, $framed );  ?>
			<?php endforeach; ?>
			<?php foreach ( $filled_items as $item ) : ?>
				<?php echo nntm_card_list_render_youtube_item( $item['id'], $titles_by_id[ $item['id'] ], true, $framed );  ?>
			<?php endforeach; ?>
		</div>

		<?php
		 
		if ( count( $filled_items ) > 1 && function_exists( 'nntm_card_list_render_marquee_nav' ) ) {
			echo nntm_card_list_render_marquee_nav();  
		}
		?>
	</div>
	<?php

	return trim( (string) ob_get_clean() );
}

