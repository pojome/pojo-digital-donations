<?php
/**
 * Plugin Name: Pojo Digital Donations
 * Plugin URI: http://pojo.me/
 * Description: Serve Digital Donations Through WordPress
 * Author: Pojo Team
 * Author URI: http://pojo.me/
 * Version: 1.0.0
 * Text Domain: pdd
 * Domain Path: languages
 *
 * Pojo Digital Donations is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 2 of the License, or
 * any later version.
 *
 * Pojo Digital Donations is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Pojo Digital Donations. If not, see <http://www.gnu.org/licenses/>.
 *
 * @package PDD
 * @category Core
 * @author Pippin Williamson
 * @version 2.0.4
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'Easy_Digital_Downloads' ) ) :

/**
 * Main Easy_Digital_Downloads Class
 *
 * @since 1.4
 */
final class Easy_Digital_Downloads {
	/** Singleton *************************************************************/

	/**
	 * @var Easy_Digital_Downloads The one true Easy_Digital_Downloads
	 * @since 1.4
	 */
	private static $instance;

	/**
	 * PDD Roles Object
	 *
	 * @var object
	 * @since 1.5
	 */
	public $roles;

	/**
	 * PDD Cart Fees Object
	 *
	 * @var object
	 * @since 1.5
	 */
	public $fees;

	/**
	 * PDD API Object
	 *
	 * @var object
	 * @since 1.5
	 */
	public $api;

	/**
	 * PDD HTML Session Object
	 *
	 * This holds cart items, purchase sessions, and anything else stored in the session
	 *
	 *
	 * @var object
	 * @since 1.5
	 */
	public $session;

	/**
	 * PDD HTML Element Helper Object
	 *
	 * @var PDD_HTML_Elements
	 * @since 1.5
	 */
	public $html;

	/**
	 * PDD Email Template Tags Object
	 *
	 * @var object
	 * @since 1.9
	 */
	public $email_tags;

	/**
	 * Main Easy_Digital_Downloads Instance
	 *
	 * Insures that only one instance of Easy_Digital_Downloads exists in memory at any one
	 * time. Also prevents needing to define globals all over the place.
	 *
	 * @since 1.4
	 * @static
	 * @staticvar array $instance
	 * @uses Easy_Digital_Downloads::setup_constants() Setup the constants needed
	 * @uses Easy_Digital_Downloads::includes() Include the required files
	 * @uses Easy_Digital_Downloads::load_textdomain() load the language files
	 * @see PDD()
	 * @return Easy_Digital_Downloads
	 */
	public static function instance() {
		if ( ! isset( self::$instance ) && ! ( self::$instance instanceof Easy_Digital_Downloads ) ) {
			self::$instance = new Easy_Digital_Downloads;
			self::$instance->setup_constants();
			self::$instance->includes();
			self::$instance->load_textdomain();
			self::$instance->roles      = new PDD_Roles();
			self::$instance->fees       = new PDD_Fees();
			self::$instance->api        = new PDD_API();
			self::$instance->session    = new PDD_Session();
			self::$instance->html       = new PDD_HTML_Elements();
			self::$instance->email_tags = new PDD_Email_Template_Tags();
		}
		return self::$instance;
	}

	/**
	 * Throw error on object clone
	 *
	 * The whole idea of the singleton design pattern is that there is a single
	 * object therefore, we don't want the object to be cloned.
	 *
	 * @since 1.6
	 * @access protected
	 * @return void
	 */
	public function __clone() {
		// Cloning instances of the class is forbidden
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'pdd' ), '1.6' );
	}

	/**
	 * Disable unserializing of the class
	 *
	 * @since 1.6
	 * @access protected
	 * @return void
	 */
	public function __wakeup() {
		// Unserializing instances of the class is forbidden
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'pdd' ), '1.6' );
	}

	/**
	 * Setup plugin constants
	 *
	 * @access private
	 * @since 1.4
	 * @return void
	 */
	private function setup_constants() {
		// Plugin version
		if ( ! defined( 'PDD_VERSION' ) ) {
			define( 'PDD_VERSION', '2.0.4' );
		}

		// Plugin Folder Path
		if ( ! defined( 'PDD_PLUGIN_DIR' ) ) {
			define( 'PDD_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
		}

		// Plugin Folder URL
		if ( ! defined( 'PDD_PLUGIN_URL' ) ) {
			define( 'PDD_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
		}

		// Plugin Root File
		if ( ! defined( 'PDD_PLUGIN_FILE' ) ) {
			define( 'PDD_PLUGIN_FILE', __FILE__ );
		}
	}

	/**
	 * Include required files
	 *
	 * @access private
	 * @since 1.4
	 * @return void
	 */
	private function includes() {
		global $pdd_options;

		require_once PDD_PLUGIN_DIR . 'includes/admin/settings/register-settings.php';
		$pdd_options = pdd_get_settings();

		require_once PDD_PLUGIN_DIR . 'includes/actions.php';
		require_once PDD_PLUGIN_DIR . 'includes/deprecated-functions.php';
		require_once PDD_PLUGIN_DIR . 'includes/ajax-functions.php';
		require_once PDD_PLUGIN_DIR . 'includes/template-functions.php';
		require_once PDD_PLUGIN_DIR . 'includes/checkout/template.php';
		require_once PDD_PLUGIN_DIR . 'includes/checkout/functions.php';
		require_once PDD_PLUGIN_DIR . 'includes/cart/functions.php';
		require_once PDD_PLUGIN_DIR . 'includes/cart/template.php';
		require_once PDD_PLUGIN_DIR . 'includes/cart/actions.php';
		require_once PDD_PLUGIN_DIR . 'includes/class-pdd-api.php';
		require_once PDD_PLUGIN_DIR . 'includes/class-pdd-cache-helper.php';
		require_once PDD_PLUGIN_DIR . 'includes/class-pdd-cron.php';
		require_once PDD_PLUGIN_DIR . 'includes/class-pdd-fees.php';
		require_once PDD_PLUGIN_DIR . 'includes/class-pdd-html-elements.php';
		require_once PDD_PLUGIN_DIR . 'includes/class-pdd-license-handler.php';
		require_once PDD_PLUGIN_DIR . 'includes/class-pdd-logging.php';
		require_once PDD_PLUGIN_DIR . 'includes/class-pdd-session.php';
		require_once PDD_PLUGIN_DIR . 'includes/class-pdd-stats.php';
		require_once PDD_PLUGIN_DIR . 'includes/class-pdd-roles.php';
		require_once PDD_PLUGIN_DIR . 'includes/country-functions.php';
		require_once PDD_PLUGIN_DIR . 'includes/formatting.php';
		require_once PDD_PLUGIN_DIR . 'includes/widgets.php';
		require_once PDD_PLUGIN_DIR . 'includes/mime-types.php';
		require_once PDD_PLUGIN_DIR . 'includes/gateways/actions.php';
		require_once PDD_PLUGIN_DIR . 'includes/gateways/functions.php';
		require_once PDD_PLUGIN_DIR . 'includes/gateways/paypal-standard.php';
		require_once PDD_PLUGIN_DIR . 'includes/gateways/manual.php';
		require_once PDD_PLUGIN_DIR . 'includes/discount-functions.php';
		require_once PDD_PLUGIN_DIR . 'includes/payments/functions.php';
		require_once PDD_PLUGIN_DIR . 'includes/payments/actions.php';
		require_once PDD_PLUGIN_DIR . 'includes/payments/class-payment-stats.php';
		require_once PDD_PLUGIN_DIR . 'includes/payments/class-payments-query.php';
		require_once PDD_PLUGIN_DIR . 'includes/misc-functions.php';
		require_once PDD_PLUGIN_DIR . 'includes/download-functions.php';
		require_once PDD_PLUGIN_DIR . 'includes/scripts.php';
		require_once PDD_PLUGIN_DIR . 'includes/post-types.php';
		require_once PDD_PLUGIN_DIR . 'includes/plugin-compatibility.php';
		require_once PDD_PLUGIN_DIR . 'includes/emails/functions.php';
		require_once PDD_PLUGIN_DIR . 'includes/emails/template.php';
		require_once PDD_PLUGIN_DIR . 'includes/emails/actions.php';
		require_once PDD_PLUGIN_DIR . 'includes/emails/email-tags.php';
		require_once PDD_PLUGIN_DIR . 'includes/error-tracking.php';
		require_once PDD_PLUGIN_DIR . 'includes/user-functions.php';
		require_once PDD_PLUGIN_DIR . 'includes/query-filters.php';
		require_once PDD_PLUGIN_DIR . 'includes/tax-functions.php';
		require_once PDD_PLUGIN_DIR . 'includes/process-purchase.php';
		require_once PDD_PLUGIN_DIR . 'includes/login-register.php';
		require_once PDD_PLUGIN_DIR . 'includes/shortcodes.php';

		if ( is_admin() || ( defined( 'WP_CLI' ) && WP_CLI ) ) {
			require_once PDD_PLUGIN_DIR . 'includes/admin/add-ons.php';
			require_once PDD_PLUGIN_DIR . 'includes/admin/admin-footer.php';
			require_once PDD_PLUGIN_DIR . 'includes/admin/admin-actions.php';
			require_once PDD_PLUGIN_DIR . 'includes/admin/admin-notices.php';
			require_once PDD_PLUGIN_DIR . 'includes/admin/admin-pages.php';
			require_once PDD_PLUGIN_DIR . 'includes/admin/dashboard-widgets.php';
			require_once PDD_PLUGIN_DIR . 'includes/admin/export-functions.php';
			require_once PDD_PLUGIN_DIR . 'includes/admin/thickbox.php';
			require_once PDD_PLUGIN_DIR . 'includes/admin/upload-functions.php';
			require_once PDD_PLUGIN_DIR . 'includes/admin/downloads/dashboard-columns.php';
			require_once PDD_PLUGIN_DIR . 'includes/admin/downloads/metabox.php';
			require_once PDD_PLUGIN_DIR . 'includes/admin/downloads/contextual-help.php';
			require_once PDD_PLUGIN_DIR . 'includes/admin/discounts/contextual-help.php';
			require_once PDD_PLUGIN_DIR . 'includes/admin/discounts/discount-actions.php';
			require_once PDD_PLUGIN_DIR . 'includes/admin/discounts/discount-codes.php';
			require_once PDD_PLUGIN_DIR . 'includes/admin/payments/actions.php';
			require_once PDD_PLUGIN_DIR . 'includes/admin/payments/payments-history.php';
			require_once PDD_PLUGIN_DIR . 'includes/admin/payments/contextual-help.php';
			require_once PDD_PLUGIN_DIR . 'includes/admin/reporting/contextual-help.php';
			require_once PDD_PLUGIN_DIR . 'includes/admin/reporting/reports.php';
			require_once PDD_PLUGIN_DIR . 'includes/admin/reporting/pdf-reports.php';
			require_once PDD_PLUGIN_DIR . 'includes/admin/reporting/class-pdd-graph.php';
			require_once PDD_PLUGIN_DIR . 'includes/admin/reporting/graphing.php';
			require_once PDD_PLUGIN_DIR . 'includes/admin/settings/display-settings.php';
			require_once PDD_PLUGIN_DIR . 'includes/admin/settings/contextual-help.php';
			require_once PDD_PLUGIN_DIR . 'includes/admin/tracking.php';
			require_once PDD_PLUGIN_DIR . 'includes/admin/tools.php';
			require_once PDD_PLUGIN_DIR . 'includes/admin/plugins.php';
			require_once PDD_PLUGIN_DIR . 'includes/admin/upgrades/upgrade-functions.php';
			require_once PDD_PLUGIN_DIR . 'includes/admin/upgrades/upgrades.php';
			require_once PDD_PLUGIN_DIR . 'includes/admin/welcome.php';
			require_once PDD_PLUGIN_DIR . 'includes/admin/class-pdd-heartbeat.php';
		} else {
			require_once PDD_PLUGIN_DIR . 'includes/process-download.php';
			require_once PDD_PLUGIN_DIR . 'includes/theme-compatibility.php';
		}

		require_once PDD_PLUGIN_DIR . 'includes/install.php';
	}

	/**
	 * Loads the plugin language files
	 *
	 * @access public
	 * @since 1.4
	 * @return void
	 */
	public function load_textdomain() {
		// Set filter for plugin's languages directory
		$pdd_lang_dir = dirname( plugin_basename( PDD_PLUGIN_FILE ) ) . '/languages/';
		$pdd_lang_dir = apply_filters( 'pdd_languages_directory', $pdd_lang_dir );

		// Traditional WordPress plugin locale filter
		$locale        = apply_filters( 'plugin_locale',  get_locale(), 'pdd' );
		$mofile        = sprintf( '%1$s-%2$s.mo', 'pdd', $locale );

		// Setup paths to current locale file
		$mofile_local  = $pdd_lang_dir . $mofile;
		$mofile_global = WP_LANG_DIR . '/pdd/' . $mofile;

		if ( file_exists( $mofile_global ) ) {
			// Look in global /wp-content/languages/pdd folder
			load_textdomain( 'pdd', $mofile_global );
		} elseif ( file_exists( $mofile_local ) ) {
			// Look in local /wp-content/plugins/easy-digital-downloads/languages/ folder
			load_textdomain( 'pdd', $mofile_local );
		} else {
			// Load the default language files
			load_plugin_textdomain( 'pdd', false, $pdd_lang_dir );
		}
	}
}

endif; // End if class_exists check


/**
 * The main function responsible for returning the one true Easy_Digital_Downloads
 * Instance to functions everywhere.
 *
 * Use this function like you would a global variable, except without needing
 * to declare the global.
 *
 * Example: <?php $pdd = PDD(); ?>
 *
 * @since 1.4
 * @return Easy_Digital_Downloads The one true Easy_Digital_Downloads Instance
 */
function PDD() {
	return Easy_Digital_Downloads::instance();
}

// Get PDD Running
PDD();
