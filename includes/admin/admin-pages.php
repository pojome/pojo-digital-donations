<?php
/**
 * Admin Pages
 *
 * @package     PDD
 * @subpackage  Admin/Pages
 * @copyright   Copyright (c) 2014, Pippin Williamson
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Creates the admin submenu pages under the Downloads menu and assigns their
 * links to global variables
 *
 * @since 1.0
 * @global $pdd_discounts_page
 * @global $pdd_payments_page
 * @global $pdd_settings_page
 * @global $pdd_reports_page
 * @global $pdd_add_ons_page
 * @global $pdd_settings_export
 * @global $pdd_upgrades_screen
 * @return void
 */
function pdd_add_options_link() {
	global $pdd_discounts_page, $pdd_payments_page, $pdd_settings_page, $pdd_reports_page, $pdd_settings_export, $pdd_upgrades_screen, $pdd_tools_page;

	$pdd_payment            = get_post_type_object( 'pdd_payment' );

	$pdd_payments_page      = add_submenu_page( 'edit.php?post_type=pdd_camp', $pdd_payment->labels->name, $pdd_payment->labels->menu_name, 'edit_shop_payments', 'pdd-payment-history', 'pdd_payment_history_page' );
	$pdd_reports_page 	    = add_submenu_page( 'edit.php?post_type=pdd_camp', __( 'Earnings and Sales Reports', 'pdd' ), __( 'Reports', 'pdd' ), 'view_shop_reports', 'pdd-reports', 'pdd_reports_page' );
	$pdd_settings_page 	    = add_submenu_page( 'edit.php?post_type=pdd_camp', __( 'Pojo Digital Donations Settings', 'pdd' ), __( 'Settings', 'pdd' ), 'manage_shop_settings', 'pdd-settings', 'pdd_options_page' );
	$pdd_tools_page         = add_submenu_page( 'edit.php?post_type=pdd_camp', __( 'Pojo Digital Donations Info and Tools', 'pdd' ), __( 'Tools', 'pdd' ), 'install_plugins', 'pdd-tools', 'pdd_tools_page' );
	$pdd_upgrades_screen    = add_submenu_page( null, __( 'PDD Upgrades', 'pdd' ), __( 'PDD Upgrades', 'pdd' ), 'install_plugins', 'pdd-upgrades', 'pdd_upgrades_screen' );
}
add_action( 'admin_menu', 'pdd_add_options_link', 10 );

/**
 *  Determines whether the current admin page is an PDD admin page.
 *  
 *  Only works after the `wp_loaded` hook, & most effective 
 *  starting on `admin_menu` hook.
 *  
 *  @since 1.9.6
 *  @return bool True if PDD admin page.
 */
function pdd_is_admin_page() {

	if ( ! is_admin() || ! did_action( 'wp_loaded' ) ) {
		return false;
	}
	
	global $pagenow, $typenow, $pdd_payments_page, $pdd_settings_page, $pdd_reports_page, $pdd_system_info_page, $pdd_settings_export, $pdd_upgrades_screen;

	if ( 'pdd_camp' == $typenow || 'index.php' == $pagenow || 'post-new.php' == $pagenow || 'post.php' == $pagenow ) {
		return true;
	}
	
	$pdd_admin_pages = apply_filters( 'pdd_admin_pages', array( $pdd_payments_page, $pdd_settings_page, $pdd_reports_page, $pdd_system_info_page, $pdd_settings_export, $pdd_upgrades_screen, ) );
	
	if ( in_array( $pagenow, $pdd_admin_pages ) ) {
		return true;
	} else {
		return false;
	}
}
