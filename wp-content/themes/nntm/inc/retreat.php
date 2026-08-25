<?php

defined( 'ABSPATH' ) || exit;

function nntm_retreat_topic_slugs(): array {
	return array( 'khoa-tu', 'lich-tu' );
}

function nntm_is_retreat_topic_archive(): bool {
	return is_tax( 'nntm_topic', nntm_retreat_topic_slugs() );
}

function nntm_retreat_topic_archive_query( WP_Query $query ): void {
	if ( is_admin() || ! $query->is_main_query() || ! $query->is_tax( 'nntm_topic', nntm_retreat_topic_slugs() ) ) {
		return;
	}

	$query->set( 'post_type', 'nntm_retreat' );
	$query->set( 'post_status', 'publish' );
	$query->set( 'posts_per_page', 5 );
	$query->set( 'orderby', 'date' );
	$query->set( 'order', 'DESC' );
}
add_action( 'pre_get_posts', 'nntm_retreat_topic_archive_query' );

function nntm_enqueue_retreat_assets(): void {
	if ( ! nntm_is_retreat_topic_archive() && ! is_singular( 'nntm_retreat' ) ) {
		return;
	}

	if ( nntm_is_retreat_topic_archive() ) {
		$rows_css = NNTM_THEME_DIR . '/blocks/article-rows/style.css';
		wp_enqueue_style(
			'nntm-retreat-article-rows',
			NNTM_THEME_URI . '/blocks/article-rows/style.css',
			array( 'nntm-tokens', 'nntm-base', 'nntm-layout' ),
			nntm_asset_version( $rows_css )
		);
	}

	if ( is_singular( 'nntm_retreat' ) ) {
		$article_css = NNTM_THEME_DIR . '/assets/css/pages/article-detail.css';
		wp_enqueue_style(
			'nntm-article-detail',
			NNTM_THEME_URI . '/assets/css/pages/article-detail.css',
			array( 'nntm-tokens', 'nntm-base', 'nntm-layout' ),
			nntm_asset_version( $article_css )
		);
	}

	$retreat_css = NNTM_THEME_DIR . '/assets/css/pages/retreat.css';
	wp_enqueue_style(
		'nntm-retreat',
		NNTM_THEME_URI . '/assets/css/pages/retreat.css',
		array( 'nntm-tokens', 'nntm-base', 'nntm-layout' ),
		nntm_asset_version( $retreat_css )
	);

	if ( is_singular( 'nntm_retreat' ) ) {
		$retreat_js = NNTM_THEME_DIR . '/assets/js/retreat.js';
		wp_enqueue_script(
			'nntm-retreat',
			NNTM_THEME_URI . '/assets/js/retreat.js',
			array(),
			nntm_asset_version( $retreat_js ),
			true
		);
		wp_localize_script(
			'nntm-retreat',
			'nntmRetreat',
			array(
				'ajaxUrl'       => admin_url( 'admin-ajax.php' ),
				'signupError'   => __( 'Không thể gửi đăng ký. Vui lòng thử lại.', 'nntm' ),
			)
		);
	}
}
add_action( 'wp_enqueue_scripts', 'nntm_enqueue_retreat_assets', 36 );

function nntm_render_retreat_topic_row( WP_Post $post, int $index ): string {
	$index          = max( 0, $index );
	$image_on_right = ( 1 === $index % 2 );
	$classes        = array( 'nntm-article-rows__row' );
	if ( $image_on_right ) {
		$classes[] = 'nntm-article-rows__row--reversed';
	}

	$permalink = get_permalink( $post );
	$title     = get_the_title( $post );
	$thumbnail = get_the_post_thumbnail(
		$post,
		'large',
		array(
			'class'   => 'nntm-article-rows__img-el',
			'loading' => 'lazy',
			'alt'     => $title,
		)
	);

	ob_start();
	?>
	<article class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>">
		<a class="nntm-article-rows__img" href="<?php echo esc_url( $permalink ); ?>" aria-label="<?php echo esc_attr( $title ); ?>">
			<?php
			if ( $thumbnail ) {
				echo wp_kses_post( $thumbnail );
			} else {
				echo '<span class="nntm-article-rows__img-placeholder" aria-hidden="true"></span>';
			}
			?>
		</a>

		<div class="nntm-article-rows__text">
			<h2 class="nntm-article-rows__title"><a href="<?php echo esc_url( $permalink ); ?>"><?php echo esc_html( $title ); ?></a></h2>
			<p class="nntm-article-rows__excerpt"><?php echo esc_html( wp_trim_words( get_the_excerpt( $post ), 34, '…' ) ); ?></p>

			<div class="nntm-article-rows__actions">
				<?php
				if ( function_exists( 'nntm_section_render_favorite_button' ) ) {
					echo nntm_section_render_favorite_button( $post->ID, 'nntm-article-rows__favorite' );  
				}
				?>
				<a class="nntm-article-rows__more" href="<?php echo esc_url( $permalink ); ?>"><?php esc_html_e( 'Xem thêm', 'nntm' ); ?></a>
			</div>
		</div>
	</article>
	<?php
	return trim( (string) ob_get_clean() );
}

function nntm_retreat_primary_topic( int $post_id ): ?WP_Term {
	$terms = get_the_terms( $post_id, 'nntm_topic' );
	if ( empty( $terms ) || is_wp_error( $terms ) ) {
		return null;
	}

	foreach ( nntm_retreat_topic_slugs() as $slug ) {
		foreach ( $terms as $term ) {
			if ( $term instanceof WP_Term && $slug === $term->slug ) {
				return $term;
			}
		}
	}

	return $terms[0] instanceof WP_Term ? $terms[0] : null;
}

function nntm_retreat_signup_table_exists(): bool {
	global $wpdb;
	$table = $wpdb->prefix . 'nntm_retreat_signup';
	$found = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table ) ) );  
	return $found === $table;
}

function nntm_ajax_retreat_signup(): void {
	check_ajax_referer( 'nntm_retreat_signup', 'nonce' );

	$retreat_id = isset( $_POST['retreat_id'] ) ? absint( wp_unslash( $_POST['retreat_id'] ) ) : 0;
	$retreat    = $retreat_id > 0 ? get_post( $retreat_id ) : null;
	if ( ! $retreat instanceof WP_Post || 'nntm_retreat' !== $retreat->post_type || 'publish' !== $retreat->post_status ) {
		wp_send_json_error( array( 'message' => __( 'Khóa tu không hợp lệ.', 'nntm' ) ), 400 );
	}

	$website = isset( $_POST['website'] ) ? trim( (string) wp_unslash( $_POST['website'] ) ) : '';
	if ( '' !== $website ) {
		wp_send_json_success( array( 'message' => __( 'Đăng ký đã được ghi nhận.', 'nntm' ) ) );
	}

	if ( ! nntm_retreat_signup_table_exists() ) {
		wp_send_json_error( array( 'message' => __( 'Hệ thống đăng ký khóa tu chưa sẵn sàng.', 'nntm' ) ), 503 );
	}

	$full_name = isset( $_POST['full_name'] ) ? sanitize_text_field( wp_unslash( $_POST['full_name'] ) ) : '';
	$phone     = isset( $_POST['phone'] ) ? sanitize_text_field( wp_unslash( $_POST['phone'] ) ) : '';
	$email     = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
	$note      = isset( $_POST['note'] ) ? sanitize_textarea_field( wp_unslash( $_POST['note'] ) ) : '';

	if ( '' === $full_name || '' === $phone || '' === $email || ! is_email( $email ) ) {
		wp_send_json_error( array( 'message' => __( 'Vui lòng nhập đầy đủ họ tên, số điện thoại và email hợp lệ.', 'nntm' ) ), 422 );
	}

	$remote_addr = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : 'unknown';
	$throttle    = 'nntm_retreat_signup_' . md5( $remote_addr . '|' . $retreat_id );
	if ( get_transient( $throttle ) ) {
		wp_send_json_error( array( 'message' => __( 'Bạn vừa gửi đăng ký. Vui lòng chờ một chút.', 'nntm' ) ), 429 );
	}
	set_transient( $throttle, 1, 20 );

	global $wpdb;
	$table   = $wpdb->prefix . 'nntm_retreat_signup';
	$user_id = get_current_user_id();

	if ( $user_id > 0 ) {
		$existing = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM {$table} WHERE retreat_id = %d AND user_id = %d AND status <> 'cancelled' LIMIT 1",  
				$retreat_id,
				$user_id
			)
		);  
	} else {
		$existing = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM {$table} WHERE retreat_id = %d AND email = %s AND phone = %s AND status <> 'cancelled' LIMIT 1",  
				$retreat_id,
				$email,
				$phone
			)
		);  
	}

	if ( $existing ) {
		wp_send_json_success(
			array(
				'already' => true,
				'message' => __( 'Bạn đã đăng ký khóa tu này trước đó.', 'nntm' ),
			)
		);
	}

	$result = $wpdb->insert(  
		$table,
		array(
			'retreat_id' => $retreat_id,
			'user_id'    => $user_id,
			'full_name'  => $full_name,
			'phone'      => $phone,
			'email'      => $email,
			'note'       => $note,
			'status'     => 'pending',
			'created_at' => current_time( 'mysql' ),
		),
		array( '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s' )
	);

	if ( false === $result ) {
		delete_transient( $throttle );
		wp_send_json_error( array( 'message' => __( 'Không thể lưu đăng ký lúc này.', 'nntm' ) ), 500 );
	}

	wp_send_json_success(
		array(
			'message' => __( 'Đăng ký đã được gửi. Ban quản trị sẽ liên hệ xác nhận.', 'nntm' ),
		)
	);
}
add_action( 'wp_ajax_nntm_retreat_signup', 'nntm_ajax_retreat_signup' );
add_action( 'wp_ajax_nopriv_nntm_retreat_signup', 'nntm_ajax_retreat_signup' );
