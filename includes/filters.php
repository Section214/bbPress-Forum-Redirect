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
