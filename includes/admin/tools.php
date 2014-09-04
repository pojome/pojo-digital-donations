<?php
/**
 * Tools
 *
 * These are functions used for displaying PDD tools such as the import/export system.
 *
 * @package     PDD
 * @subpackage  Admin/Tools
 * @copyright   Copyright (c) 2014, Pippin Williamson
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Tools
 *
 * Shows the tools panel which contains PDD-specific tools including the
 * built-in import/export system.
 *
 * @since       1.8
 * @author      Daniel J Griffiths
 * @return      void
 */
function pdd_tools_page() {
	$active_tab = isset( $_GET['tab'] ) ? $_GET['tab'] : 'general';
?>
	<div class="wrap">
		<?php screen_icon(); ?>
		<h2 class="nav-tab-wrapper">
			<?php
			foreach( pdd_get_tools_tabs() as $tab_id => $tab_name ) {

				$tab_url = add_query_arg( array(
					'tab' => $tab_id
				) );

				$tab_url = remove_query_arg( array(
					'pdd-message'
				), $tab_url );

				$active = $active_tab == $tab_id ? ' nav-tab-active' : '';
				echo '<a href="' . esc_url( $tab_url ) . '" title="' . esc_attr( $tab_name ) . '" class="nav-tab' . $active . '">' . esc_html( $tab_name ) . '</a>';

			}
			?>
		</h2>
		<div class="metabox-holder">
			<?php
			do_action( 'pdd_tools_tab_' . $active_tab );
			?>
		</div><!-- .metabox-holder -->
	</div><!-- .wrap -->
<?php
}


/**
 * Retrieve tools tabs
 *
 * @since       2.0
 * @return      array
 */
function pdd_get_tools_tabs() {

	$tabs                  = array();
	$tabs['general']       = __( 'General', 'pdd' );
	$tabs['system_info']   = __( 'System Info', 'pdd' );
	$tabs['import_export'] = __( 'Import/Export', 'pdd' );

	return apply_filters( 'pdd_tools_tabs', $tabs );
}


/**
 * Display the ban emails tab
 *
 * @since       2.0
 * @return      void
 */
function pdd_tools_banned_emails_display() {
	do_action( 'pdd_tools_banned_emails_before' );
?>
	<div class="postbox">
		<h3><span><?php _e( 'Banned Emails', 'pdd' ); ?></span></h3>
		<div class="inside">
			<p><?php _e( 'Emails placed in the box below will not be allowed to make purchases.', 'pdd' ); ?></p>
			<form method="post" action="<?php echo admin_url( 'edit.php?post_type=pdd_camp&page=pdd-tools&tab=general' ); ?>">
				<p>
					<textarea name="banned_emails" rows="10" class="large-text"><?php echo implode( "\n", pdd_get_banned_emails() ); ?></textarea>
					<span class="description"><?php _e( 'Enter emails to disallow, one per line', 'pdd' ); ?></span>
				</p>
				<p>
					<input type="hidden" name="pdd_action" value="save_banned_emails" />
					<?php wp_nonce_field( 'pdd_banned_emails_nonce', 'pdd_banned_emails_nonce' ); ?>
					<?php submit_button( __( 'Save', 'pdd' ), 'secondary', 'submit', false ); ?>
				</p>
			</form>
		</div><!-- .inside -->
	</div><!-- .postbox -->
<?php
	do_action( 'pdd_tools_banned_emails_after' );
	do_action( 'pdd_tools_after' );
}
add_action( 'pdd_tools_tab_general', 'pdd_tools_banned_emails_display' );


/**
 * Save banned emails
 *
 * @since       2.0
 * @return      void
 */
function pdd_tools_banned_emails_save() {
	if( !wp_verify_nonce( $_POST['pdd_banned_emails_nonce'], 'pdd_banned_emails_nonce' ) )
		return;

	global $pdd_options;

	// Sanitize the input
	$emails = array_map( 'trim', explode( "\n", $_POST['banned_emails'] ) );
	$emails = array_filter( array_map( 'is_email', $emails ) );

	$pdd_options['banned_emails'] = $emails;
	update_option( 'pdd_settings', $pdd_options );
}
add_action( 'pdd_save_banned_emails', 'pdd_tools_banned_emails_save' );


/**
 * Display the tools import/export tab
 *
 * @since       2.0
 * @return      void
 */
function pdd_tools_import_export_display() {
	do_action( 'pdd_tools_import_export_before' );
?>
	<div class="postbox">
		<h3><span><?php _e( 'Export Settings', 'pdd' ); ?></span></h3>
		<div class="inside">
			<p><?php _e( 'Export the Pojo Digital Donations settings for this site as a .json file. This allows you to easily import the configuration into another site.', 'pdd' ); ?></p>
			<p><?php printf( __( 'To export shop data (purchases, customers, etc), visit the <a href="%s">Reports</a> page.', 'pdd' ), admin_url( 'edit.php?post_type=pdd_camp&page=pdd-reports&tab=export' ) ); ?></p>
			<form method="post" action="<?php echo admin_url( 'edit.php?post_type=pdd_camp&page=pdd-tools&tab=import_export' ); ?>">
				<p><input type="hidden" name="pdd_action" value="export_settings" /></p>
				<p>
					<?php wp_nonce_field( 'pdd_export_nonce', 'pdd_export_nonce' ); ?>
					<?php submit_button( __( 'Export', 'pdd' ), 'secondary', 'submit', false ); ?>
				</p>
			</form>
		</div><!-- .inside -->
	</div><!-- .postbox -->

	<div class="postbox">
		<h3><span><?php _e( 'Import Settings', 'pdd' ); ?></span></h3>
		<div class="inside">
			<p><?php _e( 'Import the Pojo Digital Donations settings from a .json file. This file can be obtained by exporting the settings on another site using the form above.', 'pdd' ); ?></p>
			<form method="post" enctype="multipart/form-data" action="<?php echo admin_url( 'edit.php?post_type=pdd_camp&page=pdd-tools&tab=import_export' ); ?>">
				<p>
					<input type="file" name="import_file"/>
				</p>
				<p>
					<input type="hidden" name="pdd_action" value="import_settings" />
					<?php wp_nonce_field( 'pdd_import_nonce', 'pdd_import_nonce' ); ?>
					<?php submit_button( __( 'Import', 'pdd' ), 'secondary', 'submit', false ); ?>
				</p>
			</form>
		</div><!-- .inside -->
	</div><!-- .postbox -->
<?php
	do_action( 'pdd_tools_import_export_after' );
}
add_action( 'pdd_tools_tab_import_export', 'pdd_tools_import_export_display' );


/**
 * Process a settings export that generates a .json file of the shop settings
 *
 * @since       1.7
 * @return      void
 */
function pdd_tools_import_export_process_export() {

	if( empty( $_POST['pdd_export_nonce'] ) )
		return;

	if( ! wp_verify_nonce( $_POST['pdd_export_nonce'], 'pdd_export_nonce' ) )
		return;

	if( ! current_user_can( 'manage_shop_settings' ) )
		return;

	$settings = array();
	$settings = get_option( 'pdd_settings' );

	ignore_user_abort( true );

	if ( ! pdd_is_func_disabled( 'set_time_limit' ) && ! ini_get( 'safe_mode' ) )
		set_time_limit( 0 );

	nocache_headers();
	header( 'Content-Type: application/json; charset=utf-8' );
	header( 'Content-Disposition: attachment; filename=' . apply_filters( 'pdd_settings_export_filename', 'pdd-settings-export-' . date( 'm-d-Y' ) ) . '.json' );
	header( "Expires: 0" );

	echo json_encode( $settings );
	exit;
}
add_action( 'pdd_export_settings', 'pdd_tools_import_export_process_export' );


/**
 * Process a settings import from a json file
 *
 * @since 1.7
 * @return void
 */
function pdd_tools_import_export_process_import() {

	if( empty( $_POST['pdd_import_nonce'] ) )
		return;

	if( ! wp_verify_nonce( $_POST['pdd_import_nonce'], 'pdd_import_nonce' ) )
		return;

	if( ! current_user_can( 'manage_shop_settings' ) )
		return;

    if( pdd_get_file_extension( $_FILES['import_file']['name'] ) != 'json' ) {
        wp_die( __( 'Please upload a valid .json file', 'pdd' ) );
    }

	$import_file = $_FILES['import_file']['tmp_name'];

	if( empty( $import_file ) ) {
		wp_die( __( 'Please upload a file to import', 'pdd' ) );
	}

	// Retrieve the settings from the file and convert the json object to an array
	$settings = pdd_object_to_array( json_decode( file_get_contents( $import_file ) ) );

	update_option( 'pdd_settings', $settings );

	wp_safe_redirect( admin_url( 'edit.php?post_type=pdd_camp&page=pdd-tools&pdd-message=settings-imported' ) ); exit;

}
add_action( 'pdd_import_settings', 'pdd_tools_import_export_process_import' );


/**
 * Display the system info tab
 *
 * @since       2.0
 * @return      void
 */
function pdd_tools_sysinfo_display() {
?>
	<form action="<?php echo esc_url( admin_url( 'edit.php?post_type=pdd_camp&page=pdd-tools&tab=system_info' ) ); ?>" method="post" dir="ltr">
		<textarea readonly="readonly" onclick="this.focus(); this.select()" id="system-info-textarea" name="pdd-sysinfo" title="To copy the system info, click below then press Ctrl + C (PC) or Cmd + C (Mac)."><?php echo pdd_tools_sysinfo_get(); ?></textarea>
		<p class="submit">
			<input type="hidden" name="pdd-action" value="download_sysinfo" />
			<?php submit_button( 'Download System Info File', 'primary', 'pdd-download-sysinfo', false ); ?>
		</p>
	</form>
<?php
}
add_action( 'pdd_tools_tab_system_info', 'pdd_tools_sysinfo_display' );


/**
 * Get system info
 *
 * @since       2.0
 * @access      public
 * @global      object $wpdb Used to query the database using the WordPress Database API
 * @global      array $pdd_options Array of all PDD options
 * @return      string $return A string containing the info to output
 */
function pdd_tools_sysinfo_get() {
	global $wpdb, $pdd_options;

	if( !class_exists( 'Browser' ) )
		require_once PDD_PLUGIN_DIR . 'includes/libraries/browser.php';

	$browser = new Browser();

	// Get theme info
	if( get_bloginfo( 'version' ) < '3.4' ) {
		$theme_data = get_theme_data( get_stylesheet_directory() . '/style.css' );
		$theme      = $theme_data['Name'] . ' ' . $theme_data['Version'];
	} else {
		$theme_data = wp_get_theme();
		$theme      = $theme_data->Name . ' ' . $theme_data->Version;
	}

	// Try to identify the hosting provider
	$host = pdd_get_host();

	$return  = '### Begin System Info ###' . "\n\n";

	// Start with the basics...
	$return .= '-- Site Info' . "\n\n";
	$return .= 'Site URL:                 ' . site_url() . "\n";
	$return .= 'Home URL:                 ' . home_url() . "\n";
	$return .= 'Multisite:                ' . ( is_multisite() ? 'Yes' : 'No' ) . "\n";

	$return  = apply_filters( 'pdd_sysinfo_after_site_info', $return );

	// Can we determine the site's host?
	if( $host ) {
		$return .= "\n" . '-- Hosting Provider' . "\n\n";
		$return .= 'Host:                     ' . $host . "\n";

		$return  = apply_filters( 'pdd_sysinfo_after_host_info', $return );
	}

	// The local users' browser information, handled by the Browser class
	$return .= "\n" . '-- User Browser' . "\n\n";
	$return .= $browser;

	$return  = apply_filters( 'pdd_sysinfo_after_user_browser', $return );

	// WordPress configuration
	$return .= "\n" . '-- WordPress Configuration' . "\n\n";
	$return .= 'Version:                  ' . get_bloginfo( 'version' ) . "\n";
	$return .= 'Language:                 ' . ( defined( 'WPLANG' ) && WPLANG ? WPLANG : 'en_US' ) . "\n";
	$return .= 'Permalink Structure:      ' . ( get_option( 'permalink_structure' ) ? get_option( 'permalink_structure' ) : 'Default' ) . "\n";
	$return .= 'Active Theme:             ' . $theme . "\n";
	$return .= 'Show On Front:            ' . get_option( 'show_on_front' ) . "\n";

	// Only show page specs if frontpage is set to 'page'
	if( get_option( 'show_on_front' ) == 'page' ) {
		$front_page_id = get_option( 'page_on_front' );
		$blog_page_id = get_option( 'page_for_posts' );

		$return .= 'Page On Front:            ' . ( $front_page_id != 0 ? get_the_title( $front_page_id ) . ' (#' . $front_page_id . ')' : 'Unset' ) . "\n";
		$return .= 'Page For Posts:           ' . ( $blog_page_id != 0 ? get_the_title( $blog_page_id ) . ' (#' . $blog_page_id . ')' : 'Unset' ) . "\n";
	}

	// Make sure wp_remote_post() is working
	$request['cmd'] = '_notify-validate';

	$params = array(
		'sslverify'     => false,
		'timeout'       => 60,
		'user-agent'    => 'PDD/' . PDD_VERSION,
		'body'          => $request
	);

	$response = wp_remote_post( 'https://www.paypal.com/cgi-bin/webscr', $params );

	if( !is_wp_error( $response ) && $response['response']['code'] >= 200 && $response['response']['code'] < 300 ) {
		$WP_REMOTE_POST = 'wp_remote_post() works';
	} else {
		$WP_REMOTE_POST = 'wp_remote_post() does not work';
	}

	$return .= 'Remote Post:              ' . $WP_REMOTE_POST . "\n";
	$return .= 'Table Prefix:             ' . 'Length: ' . strlen( $wpdb->prefix ) . '   Status: ' . ( strlen( $wpdb->prefix ) > 16 ? 'ERROR: Too long' : 'Acceptable' ) . "\n";
	$return .= 'WP_DEBUG:                 ' . ( defined( 'WP_DEBUG' ) ? WP_DEBUG ? 'Enabled' : 'Disabled' : 'Not set' ) . "\n";
	$return .= 'Memory Limit:             ' . WP_MEMORY_LIMIT . "\n";
	$return .= 'Registered Post Stati:    ' . implode( ', ', get_post_stati() ) . "\n";

	$return  = apply_filters( 'pdd_sysinfo_after_wordpress_config', $return );

	// PDD configuration
	$return .= "\n" . '-- PDD Configuration' . "\n\n";
	$return .= 'Version:                  ' . PDD_VERSION . "\n";
	$return .= 'Upgraded From:            ' . get_option( 'pdd_version_upgraded_from', 'None' ) . "\n";
	$return .= 'Test Mode:                ' . ( pdd_is_test_mode() ? "Enabled\n" : "Disabled\n" );
	$return .= 'Ajax:                     ' . ( ! pdd_is_ajax_disabled() ? "Enabled\n" : "Disabled\n" );
	$return .= 'Guest Checkout:           ' . ( pdd_no_guest_checkout() ? "Disabled\n" : "Enabled\n" );
	$return .= 'Symlinks:                 ' . ( apply_filters( 'pdd_symlink_file_downloads', isset( $pdd_options['symlink_file_downloads'] ) ) && function_exists( 'symlink' ) ? "Enabled\n" : "Disabled\n" );
	$return .= 'Download Method:          ' . ucfirst( pdd_get_file_download_method() ) . "\n";

	$return  = apply_filters( 'pdd_sysinfo_after_pdd_config', $return );

	// PDD pages
	$return .= "\n" . '-- PDD Page Configuration' . "\n\n";
	$return .= 'Checkout:                 ' . ( !empty( $pdd_options['purchase_page'] ) ? "Valid\n" : "Invalid\n" );
	$return .= 'Checkout Page:            ' . ( !empty( $pdd_options['purchase_page'] ) ? get_permalink( $pdd_options['purchase_page'] ) . "\n" : "Unset\n" );
	$return .= 'Success Page:             ' . ( !empty( $pdd_options['success_page'] ) ? get_permalink( $pdd_options['success_page'] ) . "\n" : "Unset\n" );
	$return .= 'Failure Page:             ' . ( !empty( $pdd_options['failure_page'] ) ? get_permalink( $pdd_options['failure_page'] ) . "\n" : "Unset\n" );
	$return .= 'Downloads Slug:           ' . ( defined( 'PDD_SLUG' ) ? '/' . PDD_SLUG . "\n" : "/downloads\n" );

	$return  = apply_filters( 'pdd_sysinfo_after_pdd_pages', $return );

	// PDD gateways
	$return .= "\n" . '-- PDD Gateway Configuration' . "\n\n";

	$active_gateways = pdd_get_enabled_payment_gateways();
	if( $active_gateways ) {
		$default_gateway_is_active = pdd_is_gateway_active( pdd_get_default_gateway() );
		if( $default_gateway_is_active ) {
			$default_gateway = pdd_get_default_gateway();
			$default_gateway = $active_gateways[$default_gateway]['admin_label'];
		} else {
			$default_gateway = 'Test Payment';
		}

		$gateways        = array();
		foreach( $active_gateways as $gateway ) {
			$gateways[] = $gateway['admin_label'];
		}

		$return .= 'Enabled Gateways:         ' . implode( ', ', $gateways ) . "\n";
		$return .= 'Default Gateway:          ' . $default_gateway . "\n";
	} else {
		$return .= 'Enabled Gateways:         None' . "\n";
	}

	$return  = apply_filters( 'pdd_sysinfo_after_pdd_gateways', $return );

	$return  = apply_filters( 'pdd_sysinfo_after_pdd_taxes', $return );

	// PDD Templates
	$dir = get_stylesheet_directory() . '/pdd_templates/*';
	if( is_dir( $dir ) && ( count( glob( "$dir/*" ) ) !== 0 ) ) {
		$return .= "\n" . '-- PDD Template Overrides' . "\n\n";

		foreach( glob( $dir ) as $file ) {
			$return .= 'Filename:                 ' . basename( $file ) . "\n";
		}
		
		$return  = apply_filters( 'pdd_sysinfo_after_pdd_templates', $return );
	}

	// WordPress active plugins
	$return .= "\n" . '-- WordPress Active Plugins' . "\n\n";
	
	$plugins = get_plugins();
	$active_plugins = get_option( 'active_plugins', array() );

	foreach( $plugins as $plugin_path => $plugin ) {
		if( !in_array( $plugin_path, $active_plugins ) )
			continue;

		$return .= $plugin['Name'] . ': ' . $plugin['Version'] . "\n";
	}

	$return  = apply_filters( 'pdd_sysinfo_after_wordpress_plugins', $return );

	// WordPress inactive plugins
	$return .= "\n" . '-- WordPress Inactive Plugins' . "\n\n";

	foreach( $plugins as $plugin_path => $plugin ) {
		if( in_array( $plugin_path, $active_plugins ) )
			continue;

		$return .= $plugin['Name'] . ': ' . $plugin['Version'] . "\n";
	}

	$return  = apply_filters( 'pdd_sysinfo_after_wordpress_plugins_inactive', $return );

	if( is_multisite() ) {
		// WordPress Multisite active plugins
		$return .= "\n" . '-- Network Active Plugins' . "\n\n";

		$plugins = wp_get_active_network_plugins();
		$active_plugins = get_site_option( 'active_sitewide_plugins', array() );

		foreach( $plugins as $plugin_path ) {
			$plugin_base = plugin_basename( $plugin_path );

			if( !array_key_exists( $plugin_base, $active_plugins ) )
				continue;

			$plugin  = get_plugin_data( $plugin_path );
			$return .= $plugin['Name'] . ': ' . $plugin['Version'] . "\n";
		}

		$return  = apply_filters( 'pdd_sysinfo_after_wordpress_ms_plugins', $return );
	}

	// Server configuration (really just versioning)
	$return .= "\n" . '-- Webserver Configuration' . "\n\n";
	$return .= 'PHP Version:              ' . PHP_VERSION . "\n";
	$return .= 'MySQL Version:            ' . $wpdb->db_version() . "\n";
	$return .= 'Webserver Info:           ' . $_SERVER['SERVER_SOFTWARE'] . "\n";

	$return  = apply_filters( 'pdd_sysinfo_after_webserver_config', $return );

	// PHP configs... now we're getting to the important stuff
	$return .= "\n" . '-- PHP Configuration' . "\n\n";
	$return .= 'Safe Mode:                ' . ( ini_get( 'safe_mode' ) ? 'Enabled' : 'Disabled' . "\n" );
	$return .= 'Memory Limit:             ' . ini_get( 'memory_limit' ) . "\n";
	$return .= 'Upload Max Size:          ' . ini_get( 'upload_max_filesize' ) . "\n";
	$return .= 'Post Max Size:            ' . ini_get( 'post_max_size' ) . "\n";
	$return .= 'Upload Max Filesize:      ' . ini_get( 'upload_max_filesize' ) . "\n";
	$return .= 'Time Limit:               ' . ini_get( 'max_execution_time' ) . "\n";
	$return .= 'Max Input Vars:           ' . ini_get( 'max_input_vars' ) . "\n";
	$return .= 'Display Errors:           ' . ( ini_get( 'display_errors' ) ? 'On (' . ini_get( 'display_errors' ) . ')' : 'N/A' ) . "\n";

	$return  = apply_filters( 'pdd_sysinfo_after_php_config', $return );

	// PHP extensions and such
	$return .= "\n" . '-- PHP Extensions' . "\n\n";
	$return .= 'cURL:                     ' . ( function_exists( 'curl_init' ) ? 'Supported' : 'Not Supported' ) . "\n";
	$return .= 'fsockopen:                ' . ( function_exists( 'fsockopen' ) ? 'Supported' : 'Not Supported' ) . "\n";
	$return .= 'SOAP Client:              ' . ( class_exists( 'SoapClient' ) ? 'Installed' : 'Not Installed' ) . "\n";
	$return .= 'Suhosin:                  ' . ( extension_loaded( 'suhosin' ) ? 'Installed' : 'Not Installed' ) . "\n";

	$return  = apply_filters( 'pdd_sysinfo_after_php_ext', $return );

	// Session stuff
	$return .= "\n" . '-- Session Configuration' . "\n\n";
	$return .= 'PDD Use Sessions:         ' . ( defined( 'PDD_USE_PHP_SESSIONS' ) ? 'Enabled' : 'Disabled' ) . "\n";
	$return .= 'Session:                  ' . ( isset( $_SESSION ) ? 'Enabled' : 'Disabled' ) . "\n";

	// The rest of this is only relevant is session is enabled
	if( isset( $_SESSION ) ) {
		$return .= 'Session Name:             ' . esc_html( ini_get( 'session.name' ) ) . "\n";
		$return .= 'Cookie Path:              ' . esc_html( ini_get( 'session.cookie_path' ) ) . "\n";
		$return .= 'Save Path:                ' . esc_html( ini_get( 'session.save_path' ) ) . "\n";
		$return .= 'Use Cookies:              ' . ( ini_get( 'session.use_cookies' ) ? 'On' : 'Off' ) . "\n";
		$return .= 'Use Only Cookies:         ' . ( ini_get( 'session.use_only_cookies' ) ? 'On' : 'Off' ) . "\n";
	}

	$return  = apply_filters( 'pdd_sysinfo_after_session_config', $return );

	$return .= "\n" . '### End System Info ###';

	return $return;
}


/**
 * Generates a System Info download file
 *
 * @since       2.0
 * @return      void
 */
function pdd_tools_sysinfo_download() {
	nocache_headers();

	header( 'Content-Type: text/plain' );
	header( 'Content-Disposition: attachment; filename="pdd-system-info.txt"' );

	echo wp_strip_all_tags( $_POST['pdd-sysinfo'] );
	pdd_die();
}
add_action( 'pdd_camp_sysinfo', 'pdd_tools_sysinfo_download' );
