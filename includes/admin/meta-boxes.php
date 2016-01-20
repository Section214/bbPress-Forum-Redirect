<?php
/**
 * Meta boxes
 *
 * @package         bbPress\ForumRedirect\Admin\MetaBoxes
 * @since           1.1.0
 */


// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Add metabox to forum and topic post types
 *
 * @since       1.1.0
 * @return      void
 */
function bbpress_forum_redirect_add_redirect_metabox() {
	add_meta_box( 'bbpress-forum-redirect', __( 'Forum Redirect', 'bbpress-forum-redirect' ), 'bbpress_forum_redirect_display_redirect_metabox', 'forum', 'normal', 'high' );
	add_meta_box( 'bbpress-forum-redirect', __( 'Topic Redirect', 'bbpress-forum-redirect' ), 'bbpress_forum_redirect_display_redirect_metabox', 'topic', 'normal', 'high' );
}
add_action( 'add_meta_boxes', 'bbpress_forum_redirect_add_redirect_metabox' );


/**
 * Display redirect metabox
 *
 * @since       1.1.0
 * @param       array $post
 * @return      void
 */
function bbpress_forum_redirect_display_redirect_metabox( $post ) {
	$meta = get_post_custom( $post->ID );

	// Update old meta data
	if( isset( $meta['bbp-redirect'] ) && ! isset( $meta['bbpress-forum-redirect'] ) ) {
		$meta['bbpress-forum-redirect'] = $meta['bbp-redirect'];
	}

	$redirect  = isset( $meta['bbpress-forum-redirect'] ) ? esc_attr( $meta['bbpress-forum-redirect'][0] ) : '';

	wp_nonce_field( basename( __FILE__ ), 'bbpress_forum_redirect_metabox_nonce' );

	echo '<p>' . sprintf( __( 'Enter a URL to forward this %s to.', 'bbpress-forum-redirect' ), $post->post_type ) . '</p>';
	echo '<p><input type="text" name="bbpress-forum-redirect" id="bbpress-forum-redirect" value="' . $redirect . '" style="width: 50% !important;" /></p>';
}


/**
 * Save redirect metabox
 *
 * @since       1.1.0
 * @param       int $post_id The ID of this post
 * @global      object $post The post we are saving
 * @return      void
 */
function bbpress_forum_redirect_save_redirect_metabox( $post_id ) {
	global $post;

	// Don't process if nonce can't be validated
	if( ! isset( $_POST['bbpress_forum_redirect_metabox_nonce'] ) || ! wp_verify_nonce( $_POST['bbpress_forum_redirect_metabox_nonce'], basename( __FILE__ ) ) ) return $post_id;

	// Don't process if this is an autosave
	if( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ( defined( 'DOING_AJAX' ) && DOING_AJAX ) || isset( $_REQUEST['bulk_edit'] ) ) return $post_id;

	// Don't process if this is a revision
	if( isset( $post->post_type ) && $post->post_type == 'revision' ) return $post_id;

	// Save new redirect, if set
	if( isset( $_POST['bbpress-forum-redirect'] ) ) {
	    update_post_meta( $post_id, 'bbpress-forum-redirect', $_POST['bbpress-forum-redirect'] );
	} else {
	    delete_post_meta( $post_id, 'bbpress-forum-redirect' );
	}
}
add_action( 'save_post', 'bbpress_forum_redirect_save_redirect_metabox' );