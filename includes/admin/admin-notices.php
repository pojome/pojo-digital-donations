<?php
/**
 * Admin Notices
 *
 * @package     PDD
 * @subpackage  Admin/Notices
 * @copyright   Copyright (c) 2014, Pippin Williamson
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.0
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Admin Messages
 *
 * @since 1.0
 * @global $pdd_options Array of all the PDD Options
 * @return void
 */
function pdd_admin_messages() {
	global $pdd_options;

	if ( isset( $_GET['pdd-message'] ) && 'discount_added' == $_GET['pdd-message'] && current_user_can( 'manage_shop_discounts' ) ) {
		 add_settings_error( 'pdd-notices', 'pdd-discount-added', __( 'Discount code added.', 'pdd' ), 'updated' );
	}

	if ( isset( $_GET['pdd-message'] ) && 'discount_add_failed' == $_GET['pdd-message'] && current_user_can( 'manage_shop_discounts' ) ) {
		add_settings_error( 'pdd-notices', 'pdd-discount-add-fail', __( 'There was a problem adding your discount code, please try again.', 'pdd' ), 'error' );
	}

	if ( isset( $_GET['pdd-message'] ) && 'discount_updated' == $_GET['pdd-message'] && current_user_can( 'manage_shop_discounts' ) ) {
		 add_settings_error( 'pdd-notices', 'pdd-discount-updated', __( 'Discount code updated.', 'pdd' ), 'updated' );
	}

	if ( isset( $_GET['pdd-message'] ) && 'discount_update_failed' == $_GET['pdd-message'] && current_user_can( 'manage_shop_discounts' ) ) {
		add_settings_error( 'pdd-notices', 'pdd-discount-updated-fail', __( 'There was a problem updating your discount code, please try again.', 'pdd' ), 'error' );
	}

	if ( isset( $_GET['pdd-message'] ) && 'payment_deleted' == $_GET['pdd-message'] && current_user_can( 'view_shop_reports' ) ) {
		add_settings_error( 'pdd-notices', 'pdd-payment-deleted', __( 'The payment has been deleted.', 'pdd' ), 'updated' );
	}

	if ( isset( $_GET['pdd-message'] ) && 'email_sent' == $_GET['pdd-message'] && current_user_can( 'view_shop_reports' ) ) {
		add_settings_error( 'pdd-notices', 'pdd-payment-sent', __( 'The purchase receipt has been resent.', 'pdd' ), 'updated' );
    }

    if ( isset( $_GET['pdd-message'] ) && 'payment-note-deleted' == $_GET['pdd-message'] && current_user_can( 'view_shop_reports' ) ) {
        add_settings_error( 'pdd-notices', 'pdd-payment-note-deleted', __( 'The payment note has been deleted.', 'pdd' ), 'updated' );
    }

	if ( isset( $_GET['page'] ) && 'pdd-payment-history' == $_GET['page'] && current_user_can( 'view_shop_reports' ) && pdd_is_test_mode() ) {
		add_settings_error( 'pdd-notices', 'pdd-payment-sent', sprintf( __( 'Note: Test Mode is enabled, only test payments are shown below. <a href="%s">Settings</a>.', 'pdd' ), admin_url( 'edit.php?post_type=pdd_camp&page=pdd-settings' ) ), 'updated' );
	}

	if ( ( empty( $pdd_options['purchase_page'] ) || 'trash' == get_post_status( $pdd_options['purchase_page'] ) ) && current_user_can( 'edit_pages' ) && ! get_user_meta( get_current_user_id(), '_pdd_set_checkout_dismissed' ) ) {
		echo '<div class="error">';
			echo '<p>' . sprintf( __( 'No checkout page has been configured. Visit <a href="%s">Settings</a> to set one.', 'pdd' ), admin_url( 'edit.php?post_type=pdd_camp&page=pdd-settings' ) ) . '</p>';
			echo '<p><a href="' . add_query_arg( array( 'pdd_action' => 'dismiss_notices', 'pdd_notice' => 'set_checkout' ) ) . '">' . __( 'Dismiss Notice', 'pdd' ) . '</a></p>';
		echo '</div>';
	}

	if ( isset( $_GET['pdd-message'] ) && 'settings-imported' == $_GET['pdd-message'] && current_user_can( 'manage_shop_settings' ) ) {
		add_settings_error( 'pdd-notices', 'pdd-settings-imported', __( 'The settings have been imported.', 'pdd' ), 'updated' );
	}

	if ( isset( $_GET['pdd-message'] ) && 'note-added' == $_GET['pdd-message'] && current_user_can( 'edit_shop_payments' ) ) {
		add_settings_error( 'pdd-notices', 'pdd-note-added', __( 'The payment note has been added successfully.', 'pdd' ), 'updated' );
	}

	if ( isset( $_GET['pdd-message'] ) && 'payment-updated' == $_GET['pdd-message'] && current_user_can( 'edit_shop_payments' ) ) {
		add_settings_error( 'pdd-notices', 'pdd-payment-updated', __( 'The payment has been successfully updated.', 'pdd' ), 'updated' );
	}

	if ( isset( $_GET['pdd-message'] ) && 'api-key-generated' == $_GET['pdd-message'] && current_user_can( 'manage_shop_settings' ) ) {
		add_settings_error( 'pdd-notices', 'pdd-api-key-generated', __( 'API keys successfully generated.', 'pdd' ), 'updated' );
	}

	if ( isset( $_GET['pdd-message'] ) && 'api-key-exists' == $_GET['pdd-message'] && current_user_can( 'manage_shop_settings' ) ) {
		add_settings_error( 'pdd-notices', 'pdd-api-key-exists', __( 'The specified user already has API keys.', 'pdd' ), 'error' );
	}

	if ( isset( $_GET['pdd-message'] ) && 'api-key-regenerated' == $_GET['pdd-message'] && current_user_can( 'manage_shop_settings' ) ) {
		add_settings_error( 'pdd-notices', 'pdd-api-key-regenerated', __( 'API keys successfully regenerated.', 'pdd' ), 'updated' );
	}

	if ( isset( $_GET['pdd-message'] ) && 'api-key-revoked' == $_GET['pdd-message'] && current_user_can( 'manage_shop_settings' ) ) {
		add_settings_error( 'pdd-notices', 'pdd-api-key-revoked', __( 'API keys successfully revoked.', 'pdd' ), 'updated' );
	}

    if( ! pdd_htaccess_exists() && ! get_user_meta( get_current_user_id(), '_pdd_htaccess_missing_dismissed', true ) ) {
        if( ! stristr( $_SERVER['SERVER_SOFTWARE'], 'apache' ) )
            return; // Bail if we aren't using Apache... nginx doesn't use htaccess!

		echo '<div class="error">';
			echo '<p>' . sprintf( __( 'The Pojo Digital Donations .htaccess file is missing from <strong>%s</strong>!', 'pdd' ), pdd_get_upload_dir() ) . '</p>';
			echo '<p>' . sprintf( __( 'First, please resave the Misc settings tab a few times. If this warning continues to appear, create a file called ".htaccess" in the <strong>%s</strong> directory, and copy the following into it:', 'pdd' ), pdd_get_upload_dir() ) . '</p>';
			echo '<p><pre>' . pdd_get_htaccess_rules() . '</pre>';
			echo '<p><a href="' . add_query_arg( array( 'pdd_action' => 'dismiss_notices', 'pdd_notice' => 'htaccess_missing' ) ) . '">' . __( 'Dismiss Notice', 'pdd' ) . '</a></p>';
		echo '</div>';
	}

	settings_errors( 'pdd-notices' );
}
add_action( 'admin_notices', 'pdd_admin_messages' );

/**
 * Admin Add-ons Notices
 *
 * @since 1.0
 * @return void
*/
function pdd_admin_addons_notices() {
	add_settings_error( 'pdd-notices', 'pdd-addons-feed-error', __( 'There seems to be an issue with the server. Please try again in a few minutes.', 'pdd' ), 'error' );
	settings_errors( 'pdd-notices' );
}

/**
 * Dismisses admin notices when Dismiss links are clicked
 *
 * @since 1.8
 * @return void
*/
function pdd_dismiss_notices() {

	$notice = isset( $_GET['pdd_notice'] ) ? $_GET['pdd_notice'] : false;

	if( ! $notice )
		return; // No notice, so get out of here

	update_user_meta( get_current_user_id(), '_pdd_' . $notice . '_dismissed', 1 );

	wp_redirect( remove_query_arg( array( 'pdd_action', 'pdd_notice' ) ) ); exit;

}
add_action( 'pdd_dismiss_notices', 'pdd_dismiss_notices' );
