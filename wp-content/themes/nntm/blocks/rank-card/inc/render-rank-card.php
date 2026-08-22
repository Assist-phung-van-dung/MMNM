<?php

defined( 'ABSPATH' ) || exit;

function nntm_rank_card_clean_card( array $raw_card ): ?array {
	$title           = isset( $raw_card['title'] ) ? trim( (string) $raw_card['title'] ) : '';
	$image_id        = isset( $raw_card['imageId'] ) ? absint( $raw_card['imageId'] ) : 0;
	$image_url       = isset( $raw_card['imageUrl'] ) ? esc_url_raw( (string) $raw_card['imageUrl'] ) : '';
	$image_alt       = isset( $raw_card['imageAlt'] ) ? trim( (string) $raw_card['imageAlt'] ) : '';
	$cta_label       = isset( $raw_card['ctaLabel'] ) && '' !== trim( (string) $raw_card['ctaLabel'] )
		? sanitize_text_field( (string) $raw_card['ctaLabel'] )
		: __( 'Mời vào', 'nntm' );
	$target_url      = isset( $raw_card['targetUrl'] ) ? esc_url_raw( (string) $raw_card['targetUrl'] ) : '';
	$required_access = isset( $raw_card['requiredAccess'] ) ? sanitize_key( (string) $raw_card['requiredAccess'] ) : 'login';

	if ( ! in_array( $required_access, array( 'public', 'login', 'dai_si', 'kim_cuong' ), true ) ) {
		$required_access = 'login';
	}

	 
	if ( '' === $title && 0 === $image_id && '' === $image_url && '' === $target_url ) {
		return null;
	}

	return array(
		'title'          => $title,
		'imageId'        => $image_id,
		'imageUrl'       => $image_url,
		'imageAlt'       => $image_alt,
		'ctaLabel'       => $cta_label,
		'targetUrl'      => $target_url,
		'requiredAccess' => $required_access,
	);
}

function nntm_rank_card_can_access( array $card ): bool {
	 
	if ( current_user_can( 'manage_options' ) ) {
		$can_access = true;
	} else {
		$rank        = function_exists( 'nntm_user_rank' ) ? nntm_user_rank() : null;
		$logged_in   = is_user_logged_in();
		$required    = $card['requiredAccess'];

		switch ( $required ) {
			case 'public':
				$can_access = true;
				break;
			case 'dai_si':
				$can_access = $logged_in && in_array( $rank, array( 'dai_si', 'kim_cuong' ), true );
				break;
			case 'kim_cuong':
				$can_access = $logged_in && 'kim_cuong' === $rank;
				break;
			case 'login':
			default:
				$can_access = $logged_in;
				break;
		}
	}

	return (bool) apply_filters( 'nntm_rank_card_can_access', $can_access, $card, get_current_user_id() );
}

function nntm_rank_card_lien_ket( array $card, bool $can_access ): ?array {
	 
	if ( $can_access ) {
		if ( '' === $card['targetUrl'] ) {
			return null;  
		}

		return array(
			'url'   => $card['targetUrl'],
			'attrs' => '',
		);
	}

	if ( ! is_user_logged_in() ) {
		$login_url = function_exists( 'nntm_login_url' )
			? nntm_login_url( $card['targetUrl'] )
			: wp_login_url( $card['targetUrl'] );

		return array(
			'url'   => $login_url,
			'attrs' => sprintf(
				' data-nntm-auth-modal="dang-nhap" data-nntm-auth-redirect="%s"',
				esc_url( $card['targetUrl'] )
			),
		);
	}

	return null;
}

function nntm_rank_card_render_card( array $card ): string {
	$can_access = nntm_rank_card_can_access( $card );
	$lien_ket   = nntm_rank_card_lien_ket( $card, $can_access );

	$media_the   = ( null !== $lien_ket ) ? 'a' : 'div';
	$media_attrs = '';
	if ( null !== $lien_ket ) {
		$media_attrs = ' href="' . esc_url( $lien_ket['url'] ) . '"' . $lien_ket['attrs'] . ' tabindex="-1"';
		if ( '' === $card['imageAlt'] ) {
			$media_attrs .= ' aria-hidden="true"';
		}
	}

	ob_start();
	?>
	<div class="nntm-rank-card__card">
		<<?php echo esc_html( $media_the ); ?> class="nntm-rank-card__card-media"<?php echo $media_attrs;  ?>>
			<?php
			$is_decorative = ( '' === $card['imageAlt'] );
			if ( $card['imageId'] > 0 ) :
				$image_attrs = array(
					'class'   => 'nntm-rank-card__card-img',
					'loading' => 'lazy',
					'alt'     => $card['imageAlt'],
				);
				if ( $is_decorative ) {
					$image_attrs['role'] = 'presentation';
				}
				echo wp_kses_post( wp_get_attachment_image( $card['imageId'], 'large', false, $image_attrs ) );
			elseif ( '' !== $card['imageUrl'] ) :
				?>
				<img
					class="nntm-rank-card__card-img"
					src="<?php echo esc_url( $card['imageUrl'] ); ?>"
					alt="<?php echo esc_attr( $card['imageAlt'] ); ?>"
					loading="lazy"
					<?php echo $is_decorative ? 'role="presentation"' : '';  ?>
				/>
				<?php
			else :
				?>
				<span class="nntm-rank-card__card-img nntm-rank-card__card-img--placeholder" aria-hidden="true"></span>
				<?php
			endif;
			?>
		</<?php echo esc_html( $media_the ); ?>>

		<?php if ( '' !== $card['title'] ) : ?>
			<p class="nntm-rank-card__card-title"><?php echo esc_html( $card['title'] ); ?></p>
		<?php endif; ?>

		<?php echo nntm_rank_card_render_cta( $card, $can_access );  ?>
	</div>
	<?php
	return (string) ob_get_clean();
}

function nntm_rank_card_render_cta( array $card, bool $can_access ): string {
	$lien_ket = nntm_rank_card_lien_ket( $card, $can_access );

	if ( null !== $lien_ket ) {
		return sprintf(
			'<a class="nntm-rank-card__cta" href="%1$s"%2$s>%3$s &rarr;</a>',
			esc_url( $lien_ket['url'] ),
			$lien_ket['attrs'],  
			esc_html( $card['ctaLabel'] )
		);
	}

	if ( ! $can_access ) {
		$locked_text = 'kim_cuong' === $card['requiredAccess']
			? __( 'Cần cấp Kim Cương', 'nntm' )
			: __( 'Cần cấp Đại Sĩ', 'nntm' );

		return sprintf(
			'<span class="nntm-rank-card__cta nntm-rank-card__cta--khoa" aria-disabled="true">%s</span>',
			esc_html( $locked_text )
		);
	}

	 
	return sprintf(
		'<span class="nntm-rank-card__cta">%s &rarr;</span>',
		esc_html( $card['ctaLabel'] )
	);
}
