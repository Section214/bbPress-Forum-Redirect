<?php
/**
 * Plugin Name:     bbPress Forum Redirect
 * Plugin URI:      http://www.ghost1227.com/go/bbpress-forum-redirect
 * Description:     Allows you to override the default behavior of bbPress forums, linking them to an external site
 * Author:          Daniel J Griffiths
 * Author URI:      http://www.ghost1227.com
 * Version:         1.0.0
 * Text Domain:     bbp-redirect
 *
 * @package         bbP_Redirect
 * @author          Daniel J Griffiths
 * @version         1.0.0
 */


// Exit if accessed directly
if( !defined( 'ABSPATH' ) ) exit;


if( !class_exists( 'bbP_Redirect' ) ) {

    /**
     * Main bbP_Redirect class
     *
     * @since       1.0.0
     */
    final class bbP_Redirect {

        private static $instance;

        /**
         * Main bbP_Redirect instance
         *
         * @since       1.0.0
         * @access      public
         * @staticvar   array $instance
         * @return      The one true bbP_Redirect
         */
        public static function instance() {
            if( !isset( self::$instance ) && !( self::$instance instanceof bbP_Redirect ) ) {
                self::$instance = new bbP_Redirect;
                self::$instance->setup_constants();
                self::$instance->load_textdomain();
                self::$instance->init();
            }

            return self::$instance;
        }


        /**
         * Setup plugin constants
         *
         * @since       1.0.0
         * @access      private
         * @return      void
         */
        private function setup_constants() {
            // Plugin version
            define( 'BBP_REDIRECT_VERSION', '1.0.0' );

            // Plugin folder URL
            define( 'BBP_REDIRECT_URL', plugin_dir_url( __FILE__ ) );

            // Plugin folder dir
            define( 'BBP_REDIRECT_DIR', plugin_dir_path( __FILE__ ) );

            // Plugin root file
            define( 'BBP_REDIRECT_FILE', __FILE__ );
        }


        /**
         * Load plugin language files
         *
         * @since       1.0.0
         * @access      public
         * @return      void
         */
        public function load_textdomain() {
            // Set filter for plugin language directory
            $bbp_redirect_lang_dir  = dirname( plugin_basename( BBP_REDIRECT_FILE ) ) . '/languages/';
            $bbp_redirect_lang_dir  = apply_filters( 'bbp_redirect_language_directory', $bbp_redirect_lang_dir );

            // WordPress plugin locale filter
            $locale         = apply_filters( 'plugin_locale', get_locale(), 'bbp-redirect' );
            $mofile         = sprintf( '%1$s-%2$s.mo', 'bbp-redirect', $locale );

            // Setup paths
            $mofile_local   = $bbp_redirect_lang_dir . $mofile;
            $mofile_global  = WP_LANG_DIR . '/bbp-redirect/' . $mofile;

            if( file_exists( $mofile_global ) ) {
                // Check global wp-content/languages/bbp-redirect folder
                load_textdomain( 'bbp-redirect', $mofile_global );
            } elseif( file_exists( $mofile_local ) ) {
                load_textdomain( 'bbp-redirect', $mofile_local );
            } else {
                load_plugin_textdomain( 'bbp-redirect', false, $bbp_redirect_lang_dir );
            }
        }


        /**
         * Run action and filter hooks
         *
         * @since       1.0.0
         * @access      private
         * @return      void
         */
        private function init() {
            // Modify plugin metalinks
            add_filter( 'plugin_row_meta', array( $this, 'modify_plugin_metalinks' ), null, 2 );

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
         * @since       1.0.0
         * @access      public
         * @param       array $links The current links array
         * @return      array $links The modified links array
         */
        public function modify_plugin_metalinks( $links, $file ) {
            if( $file == plugin_basename( __FILE__ ) ) {
                // Add help link
                $help_link = array( sprintf(
                    '<a href="%1$s" target="_blank">%2$s</a>',
                    'http://support.ghost1227.com/forums/forum/plugin-support/bbpress-forum-redirect/',
                    __( 'Support Forum', 'bbp-redirect' )
                ) );

                // Add docs link
                $docs_link = array( sprintf(
                    '<a href="%1$s" target="_blank">%2$s</a>',
                    'http://support.ghost1227.com/section/bbpress-forum-redirect/',
                    __( 'Docs', 'bbp-redirect' )
                ) );

                $links = array_merge( $links, $help_link, $docs_link );
            }

            return $links;
        }


        /**
         * Add metabox to forum post type
         *
         * @since       1.0.0
         * @access      public
         * @return      void
         */
        public function add_redirect_metabox() {
            // Nice and simple...
            add_meta_box( 'bbp-redirect', __( 'Forum Redirect', 'bbp-redirect' ), array( $this, 'display_redirect_metabox' ), 'forum', 'normal', 'high' );
        }


        /**
         * Display redirect metabox
         *
         * @since       1.0.0
         * @access      public
         * @param       array $post
         * @return      void
         */
        public function display_redirect_metabox( $post ) {
            $meta = get_post_custom( $post->ID );
            $redirect = isset( $meta['bbp-redirect'] ) ? esc_attr( $meta['bbp-redirect'][0] ) : '';
            
            wp_nonce_field( 'bbp_redirect_nonce', 'bbp_redirect_metabox_nonce' );

            echo '<p>' . __( 'Enter a URL to forward this forum to.', 'bbp-redirect' ) . '</p>';
            echo '<p><input type="text" name="bbp-redirect" id="bbp-redirect" value="' . $redirect . '" style="width: 50% !important;" /></p>';
        }


        /**
         * Save redirect metabox
         *
         * @since       1.0.0
         * @access      public
         * @param       int $post_id The ID of this post
         * @return      void
         */
        public function save_redirect_metabox( $post_id ) {
            // Don't update on autosave
            if( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;

            // Verify nonce
            if( !isset( $_POST['meta_box_nonce'] ) || !wp_verify_nonce( $_POST['bbp_redirect_metabox_nonce'], 'bbp_redirect_nonce' ) ) return;

            // Save new redirect, if set
            if( isset( $_POST['bbp-redirect'] ) )
                update_post_meta( $post_id, 'bbp-redirect', $_POST['bbp-redirect'] );
        }


        /**
         * Override forum permalink for redirects
         *
         * @since       1.0.0
         * @access      public
         * @param       string $forum_permalink
         * @param       int $forum_id
         * @return      string
         */
        public function override_forum_permalink( $forum_permalink, $forum_id ) {
            $meta = get_post_custom( $forum_id );

            if( !isset( $meta['bbp-redirect'] ) || empty( $meta['bbp-redirect'][0] ) )
                return $forum_permalink;

            return esc_attr( $meta['bbp-redirect'][0] );
        }
    }
}


/**
 * The main function responsible for returning the bbP_Redirect instance
 *
 * @since       1.0.0
 * @return      object bbP_Redirect instance
 */
function load_bbp_redirect() {
    return bbP_Redirect::instance();
}


// Off we go!
load_bbp_redirect();
