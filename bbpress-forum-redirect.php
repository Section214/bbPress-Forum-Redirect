<?php
/**
 * Plugin Name:     bbPress Forum Redirect
 * Plugin URI:      http://wordpress.org/plugins/bbpress-forum-redirect/
 * Description:     Allows you to override the default behavior of bbPress forums, linking them to an external site
 * Author:          Daniel J Griffiths
 * Author URI:      http://www.ghost1227.com
 * Version:         1.0.1
 * Text Domain:     bbpress-forum-redirect
 *
 * @package         bbPress\ForumRedirect
 * @author          Daniel J Griffiths
 * @version         1.0.0
 */


// Exit if accessed directly
if( !defined( 'ABSPATH' ) ) exit;


if( !class_exists( 'bbPress_Forum_Redirect' ) ) {

    /**
     * Main bbPress_Forum_Redirect class
     *
     * @since       1.0.0
     */
    final class bbPress_Forum_Redirect {

        /**
         * @var         bbPress_Forum_Redirect $instance The one true bbPress_Forum_Redirect
         * @since       1.0.0
         */
        private static $instance;

        /**
         * Get active instance
         *
         * @access      public
         * @since       1.0.0
         * @return      object self::$instance The one true bbPress_Forum_Redirect
         */
        public static function instance() {
            if( !isset( self::$instance ) ) {
                self::$instance = new bbPress_Forum_Redirect;
                self::$instance->setup_constants();
                self::$instance->load_textdomain();
                self::$instance->hooks();
            }

            return self::$instance;
        }


        /**
         * Setup plugin constants
         *
         * @access      private
         * @since       1.0.0
         * @return      void
         */
        private function setup_constants() {
            // Plugin version
            define( 'BBPRESS_FORUM_REDIRECT_VER', '1.0.1' );

            // Plugin folder URL
            define( 'BBPRESS_FORUM_REDIRECT_URL', plugin_dir_url( __FILE__ ) );

            // Plugin folder dir
            define( 'BBPRESS_FORUM_REDIRECT_DIR', plugin_dir_path( __FILE__ ) );
        }


        /**
         * Load plugin language files
         *
         * @access      public
         * @since       1.0.0
         * @return      void
         */
        public function load_textdomain() {
            // Set filter for plugin language directory
            $lang_dir  = dirname( plugin_basename( __FILE__ ) ) . '/languages/';
            $lang_dir  = apply_filters( 'bbPress_Forum_Redirect_lang_dir', $lang_dir );

            // WordPress plugin locale filter
            $locale         = apply_filters( 'plugin_locale', get_locale(), 'bbpress-forum-redirect' );
            $mofile         = sprintf( '%1$s-%2$s.mo', 'bbpress-forum-redirect', $locale );

            // Setup paths
            $mofile_local   = $lang_dir . $mofile;
            $mofile_global  = WP_LANG_DIR . '/bbpress-forum-redirect/' . $mofile;

            if( file_exists( $mofile_global ) ) {
                // Check global wp-content/languages/bbpress-forum-redirect folder
                load_textdomain( 'bbpress-forum-redirect', $mofile_global );
            } elseif( file_exists( $mofile_local ) ) {
                load_textdomain( 'bbpress-forum-redirect', $mofile_local );
            } else {
                load_plugin_textdomain( 'bbpress-forum-redirect', false, $lang_dir );
            }
        }


        /**
         * Run action and filter hooks
         *
         * @access      private
         * @since       1.0.0
         * @return      void
         */
        private function hooks() {
            // Modify plugin metalinks
            add_filter( 'plugin_row_meta', array( $this, 'plugin_metalinks' ), null, 2 );

            // Add metabox to forum post type
            add_action( 'add_meta_boxes', array( $this, 'add_redirect_metabox' ) );

            // Save metabox
            add_action( 'save_post', array( $this, 'save_redirect_metabox' ) );

            // Override forum permalinks
            add_filter( 'bbp_get_forum_permalink', array( $this, 'override_forum_permalink' ), null, 2 );
        }


        /**
         * Modify plugin metalinks
         *
         * @access      public
         * @since       1.0.0
         * @param       array $links The current links array
         * @param       string $file A specific plugin table entry
         * @return      array $links The modified links array
         */
        public function plugin_metalinks( $links, $file ) {
            if( $file == plugin_basename( __FILE__ ) ) {
                $help_link = array(
                    '<a href="http://section214.com/support/forum/bbpress-forum-redirect/" target="_blank">' . __( 'Support Forum', 'edd-balanced-gateway' ) . '</a>'
                );

                $docs_link = array(
                    '<a href="http://section214.com/docs/category/bbpress-forum-redirect/" target="_blank">' . __( 'Docs', 'edd-balanced-gateway' ) . '</a>'
                );

                $links = array_merge( $links, $help_link, $docs_link );
            }

            return $links;
        }


        /**
         * Add metabox to forum post type
         *
         * @access      public
         * @since       1.0.0
         * @return      void
         */
        public function add_redirect_metabox() {
            // Nice and simple...
            add_meta_box( 'bbpress-forum-redirect', __( 'Forum Redirect', 'bbpress-forum-redirect' ), array( $this, 'display_redirect_metabox' ), 'forum', 'normal', 'high' );
        }


        /**
         * Display redirect metabox
         *
         * @access      public
         * @since       1.0.0
         * @param       array $post
         * @return      void
         */
        public function display_redirect_metabox( $post ) {
            $meta = get_post_custom( $post->ID );

            // Update old meta data
            if( isset( $meta['bbp-redirect'] ) && !isset( $meta['bbpress-forum-redirect'] ) ) {
                $meta['bbpress-forum-redirect'] = $meta['bbp-redirect'];
            }

            $redirect = isset( $meta['bbpress-forum-redirect'] ) ? esc_attr( $meta['bbpress-forum-redirect'][0] ) : '';
            
            wp_nonce_field( basename( __FILE__ ), 'bbpress_forum_redirect_metabox_nonce' );

            echo '<p>' . __( 'Enter a URL to forward this forum to.', 'bbpress-forum-redirect' ) . '</p>';
            echo '<p><input type="text" name="bbpress-forum-redirect" id="bbpress-forum-redirect" value="' . $redirect . '" style="width: 50% !important;" /></p>';
        }


        /**
         * Save redirect metabox
         *
         * @access      public
         * @since       1.0.0
         * @param       int $post_id The ID of this post
         * @global      object $post The post we are saving
         * @return      void
         */
        public function save_redirect_metabox( $post_id ) {
            global $post;

            // Don't process if nonce can't be validated
            if( !isset( $_POST['bbpress_forum_redirect_metabox_nonce'] ) || !wp_verify_nonce( $_POST['bbpress_forum_redirect_metabox_nonce'], basename( __FILE__ ) ) ) return $post_id;

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


        /**
         * Override forum permalink for redirects
         *
         * @access      public
         * @since       1.0.0
         * @param       string $forum_permalink
         * @param       int $forum_id
         * @return      string
         */
        public function override_forum_permalink( $forum_permalink, $forum_id ) {
            $meta = get_post_custom( $forum_id );

            if( !isset( $meta['bbpress-forum-redirect'] ) || empty( $meta['bbpress-forum-redirect'][0] ) )
                return $forum_permalink;

            return esc_attr( $meta['bbpress-forum-redirect'][0] );
        }
    }
}


/**
 * The main function responsible for returning the bbPress_Forum_Redirect instance
 *
 * @since       1.0.0
 * @return      bbPress_Forum_Redirect The one true bbPress_Forum_Redirect
 */
function bbPress_Forum_Redirect_load() {
    if( !class_exists( 'bbPress' ) ) {
        deactivate_plugins( __FILE__ );
        unset( $_GET['activate'] );

        // Display notice
        add_action( 'admin_notices', 'bbPress_Forum_Redirect_missing_bbpress_notice' );
    } else {
        return bbPress_Forum_Redirect::instance();
    }
}
add_action( 'plugins_loaded', 'bbPress_Forum_Redirect_load' );


/**
 * We need bbPress... if it isn't present, notify the user!
 *
 * @since       1.0.1
 * @return      void
 */
function bbPress_Forum_Redirect_missing_bbpress_notice() {
    echo '<div class="error"><p>' . __( 'bbPress Forum Redirect requires bbPress! Please install it to continue!', 'bbpress-forum-redirect' ) . '</p></div>';
}
