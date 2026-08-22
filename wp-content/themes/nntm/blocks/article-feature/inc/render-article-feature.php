<?php

defined( 'ABSPATH' ) || exit;

function nntm_article_feature_get_paragraphs( WP_Post $article, int $max_paragraphs ): string {
	$max_paragraphs = max( 1, $max_paragraphs );

	$raw = strip_shortcodes( $article->post_content );
	$raw = has_blocks( $article ) ? do_blocks( $raw ) : wpautop( $raw );

	 
	if ( false === strpos( $raw, '<p' ) ) {
		$raw = wpautop( $raw );
	}

	 
	if ( ! preg_match_all( '#<p\b[^>]*>.*?</p>#is', $raw, $matches ) || empty( $matches[0] ) ) {
		return wp_kses_post( $raw );
	}

	$keep = array_slice( $matches[0], 0, $max_paragraphs );

	return wp_kses_post( implode( "\n", $keep ) );
}

function nntm_article_feature_find_post( int $post_id, string $post_type, string $taxonomy, int $term_id ): ?WP_Post {
	if ( $post_id > 0 ) {
		$chosen = get_post( $post_id );
		if ( $chosen instanceof WP_Post && 'publish' === $chosen->post_status ) {
			return $chosen;
		}

	}

	$args = array(
		'post_type'           => $post_type,
		'post_status'         => 'publish',
		'posts_per_page'      => 1,
		'ignore_sticky_posts' => true,
		'no_found_rows'       => true,
		'orderby'             => array(
			'date' => 'DESC',
			'ID'   => 'DESC',
		),
	);

	if ( '' !== $taxonomy && taxonomy_exists( $taxonomy ) && $term_id > 0 ) {
		$args['tax_query'] = array(  
			array(
				'taxonomy' => $taxonomy,
				'field'    => 'term_id',
				'terms'    => array( $term_id ),
			),
		);
	}

	$query = new WP_Query( $args );

	return empty( $query->posts ) ? null : $query->posts[0];
}
