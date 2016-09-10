<?php
/**
 * Plugin Name:     bbPress Forum Redirect
 * Plugin URI:      http://wordpress.org/plugins/bbpress-forum-redirect/
 * Description:     Allows you to override the default behavior of bbPress forums, linking them to an external site
 * Author:          Daniel J Griffiths
 * Author URI:      http://www.ghost1227.com
 * Version:         1.1.1
 * Text Domain:     bbpress-forum-redirect
 *
 * @package         bbPress\ForumRedirect
 * @author          Daniel J Griffiths
 */


// Exit if accessed directly
if( ! defined( 'ABSPATH' ) ) {
	exit;
}


if( ! class_exists( 'bbPress_Forum_Redirect' ) ) {

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
			if( ! isset( self::$instance ) ) {
				self::$instance = new bbPress_Forum_Redirect;
				self::$instance->setup_constants();
				self::$instance->includes();
				self::$instance->load_textdomain();
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
			define( 'BBPRESS_FORUM_REDIRECT_VER', '1.1.1' );

			// Plugin folder URL
			define( 'BBPRESS_FORUM_REDIRECT_URL', plugin_dir_url( __FILE__ ) );

			// Plugin folder dir
			define( 'BBPRESS_FORUM_REDIRECT_DIR', plugin_dir_path( __FILE__ ) );
		}


		/**
		 * Include reqired files
		 *
		 * @access      private
		 * @since       1.1.0
		 * @return      void
		 */
		private function includes() {
			require_once BBPRESS_FORUM_REDIRECT_DIR . 'includes/filters.php';

			if( is_admin() ) {
				require_once BBPRESS_FORUM_REDIRECT_DIR . 'includes/admin/meta-boxes.php';
			}
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
			$lang_dir  = apply_filters( 'bbpress_forum_redirect_lang_dir', $lang_dir );

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
    }
}


/**
 * The main function responsible for returning the bbPress_Forum_Redirect instance
 *
 * @since       1.0.0
 * @return      bbPress_Forum_Redirect The one true bbPress_Forum_Redirect
 */
function bbpress_forum_redirect() {
	if( ! class_exists( 'bbPress' ) ) {
		deactivate_plugins( __FILE__ );
		unset( $_GET['activate'] );

		// Display notice
		add_action( 'admin_notices', 'bbpress_forum_redirect_missing_bbpress_notice' );
	} else {
		return bbPress_Forum_Redirect::instance();
	}
}
add_action( 'plugins_loaded', 'bbpress_forum_redirect' );


/**
 * We need bbPress... if it isn't present, notify the user!
 *
 * @since       1.0.1
 * @return      void
 */
function bbpress_forum_redirect_missing_bbpress_notice() {
	echo '<div class="error"><p>' . __( 'bbPress Forum Redirect requires bbPress! Please install it to continue!', 'bbpress-forum-redirect' ) . '</p></div>';
}
