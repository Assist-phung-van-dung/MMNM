<?php

defined( 'ABSPATH' ) || exit;

const NNTM_CHIA_SE_META = '_nntm_link_chia_se';
const NNTM_CHIA_SE_OPTION = 'nntm_link_chia_se_chung';

function nntm_chia_se_post_types(): array {
	return (array) apply_filters( 'nntm_chia_se_post_types', array( 'nntm_article' ) );
}

function nntm_chia_se_url( int $post_id ): string {
	$rieng = trim( (string) get_post_meta( $post_id, NNTM_CHIA_SE_META, true ) );
	$url   = '' !== $rieng ? $rieng : trim( (string) get_option( NNTM_CHIA_SE_OPTION, '' ) );

	return (string) apply_filters( 'nntm_chia_se_url', esc_url_raw( $url ), $post_id );
}

function nntm_chia_se_dang_ky_meta(): void {
	foreach ( nntm_chia_se_post_types() as $post_type ) {
		register_post_meta(
			$post_type,
			NNTM_CHIA_SE_META,
			array(
				'type'              => 'string',
				'single'            => true,
				'default'           => '',
				'show_in_rest'      => true,
				'sanitize_callback' => static function ( $value ): string {
					return esc_url_raw( trim( (string) $value ) );
				},
				'auth_callback'     => static function ( $allowed, $meta_key, $post_id ): bool {
					$post_id = absint( $post_id );

					return $post_id > 0 ? current_user_can( 'edit_post', $post_id ) : current_user_can( 'edit_posts' );
				},
			)
		);
	}
}
add_action( 'init', 'nntm_chia_se_dang_ky_meta' );

function nntm_chia_se_them_o_nhap(): void {
	foreach ( nntm_chia_se_post_types() as $post_type ) {
		add_meta_box(
			'nntm-chia-se',
			__( 'Link chia sẻ', 'nntm' ),
			'nntm_chia_se_ve_o_nhap',
			$post_type,
			'side',
			'default'
		);
	}
}
add_action( 'add_meta_boxes', 'nntm_chia_se_them_o_nhap' );

function nntm_chia_se_ve_o_nhap( WP_Post $post ): void {
	$gia_tri = (string) get_post_meta( $post->ID, NNTM_CHIA_SE_META, true );
	$chung   = (string) get_option( NNTM_CHIA_SE_OPTION, '' );

	wp_nonce_field( 'nntm_chia_se_luu', 'nntm_chia_se_nonce' );
	?>
	<p>
		<label for="nntm-chia-se-url"><?php esc_html_e( 'Nút "Chia sẻ" ở cuối bài sẽ dẫn tới đường dẫn này.', 'nntm' ); ?></label>
	</p>
	<p>
		<input
			type="url"
			id="nntm-chia-se-url"
			name="nntm_chia_se_url"
			class="widefat"
			value="<?php echo esc_attr( $gia_tri ); ?>"
			placeholder="https://"
		/>
	</p>
	<p class="description">
		<?php
		if ( '' !== $chung ) {
			printf(
				/* translators: %s: link chia se chung */
				esc_html__( 'Để trống thì dùng link chung: %s', 'nntm' ),
				'<code>' . esc_html( $chung ) . '</code>'
			);
		} else {
			printf(
				/* translators: %s: duong dan trang cai dat */
				esc_html__( 'Để trống thì lấy link chung ở %s. Chưa đặt link nào thì nút Chia sẻ được ẩn.', 'nntm' ),
				'<a href="' . esc_url( admin_url( 'options-general.php' ) ) . '">' . esc_html__( 'Cài đặt → Tổng quan', 'nntm' ) . '</a>'
			);
		}
		?>
	</p>
	<?php
}

function nntm_chia_se_luu( int $post_id ): void {
	if ( ! isset( $_POST['nntm_chia_se_nonce'] ) ) {
		return;
	}

	if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nntm_chia_se_nonce'] ) ), 'nntm_chia_se_luu' ) ) {
		return;
	}

	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	$url = isset( $_POST['nntm_chia_se_url'] ) ? esc_url_raw( trim( (string) wp_unslash( $_POST['nntm_chia_se_url'] ) ) ) : '';

	if ( '' === $url ) {
		delete_post_meta( $post_id, NNTM_CHIA_SE_META );
		return;
	}

	update_post_meta( $post_id, NNTM_CHIA_SE_META, $url );
}
add_action( 'save_post', 'nntm_chia_se_luu' );

function nntm_chia_se_dang_ky_cai_dat(): void {
	register_setting(
		'general',
		NNTM_CHIA_SE_OPTION,
		array(
			'type'              => 'string',
			'default'           => '',
			'sanitize_callback' => static function ( $value ): string {
				return esc_url_raw( trim( (string) $value ) );
			},
		)
	);

	add_settings_field(
		NNTM_CHIA_SE_OPTION,
		__( 'Link chia sẻ (nút Chia sẻ cuối bài)', 'nntm' ),
		static function (): void {
			?>
			<input
				type="url"
				name="<?php echo esc_attr( NNTM_CHIA_SE_OPTION ); ?>"
				id="<?php echo esc_attr( NNTM_CHIA_SE_OPTION ); ?>"
				class="regular-text"
				value="<?php echo esc_attr( (string) get_option( NNTM_CHIA_SE_OPTION, '' ) ); ?>"
				placeholder="https://"
			/>
			<p class="description">
				<?php esc_html_e( 'Dùng cho mọi bài chưa đặt link riêng. Để trống thì nút Chia sẻ không hiện.', 'nntm' ); ?>
			</p>
			<?php
		},
		'general'
	);
}
add_action( 'admin_init', 'nntm_chia_se_dang_ky_cai_dat' );
