<?php
/**
 * Filters
 *
 * @package         bbPress\ForumRedirect\Filters
 * @since           1.1.0
 */


// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Override forum permalink for redirects
 *
 * @since       1.1.0
 * @param       string $forum_permalink The current permalink
 * @param       int $post_id The ID of this forum/topic
 * @return      string $forum_permalink The updated permalink
 */
function bbpress_forum_redirect_override_permalink( $permalink, $post_id ) {
	$redirect = get_post_meta( $post_id, 'bbpress-forum-redirect', true );

	if( $redirect ) {
		$permalink = esc_url( $redirect );
	}

	return $permalink;
}
add_filter( 'bbp_get_forum_permalink', 'bbpress_forum_redirect_override_permalink', 10, 2 );
add_filter( 'bbp_get_topic_permalink', 'bbpress_forum_redirect_override_permalink', 10, 2 );


/**
 * Override template redirect on forum/topic direct access
 *
 * @since       1.1.0
 * @return      void
 */
function bbpress_forum_redirect_template_redirect() {
	global $post;

	if( $post && ( bbp_is_single_forum() || bbp_is_single_topic() ) ) {
		$redirect = get_post_meta( $post->ID, 'bbpress-forum-redirect', true );

		if( $redirect ) {
			wp_redirect( esc_url( $redirect ) );
			die();
		}
	}
}
add_action( 'template_redirect', 'bbpress_forum_redirect_template_redirect' );