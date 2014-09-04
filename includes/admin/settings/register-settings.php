<?php
/**
 * Register Settings
 *
 * @package     PDD
 * @subpackage  Admin/Settings
 * @copyright   Copyright (c) 2014, Pippin Williamson
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.0
*/

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;


/**
 * Get an option
 *
 * Looks to see if the specified setting exists, returns default if not
 *
 * @since 1.8.4
 * @return mixed
 */
function pdd_get_option( $key = '', $default = false ) {
	global $pdd_options;
	$value = ! empty( $pdd_options[ $key ] ) ? $pdd_options[ $key ] : $default;
	$value = apply_filters( 'pdd_get_option', $value, $key, $default );
	return apply_filters( 'pdd_get_option_' . $key, $value, $key, $default );
}

/**
 * Get Settings
 *
 * Retrieves all plugin settings
 *
 * @since 1.0
 * @return array PDD settings
 */
function pdd_get_settings() {

	$settings = get_option( 'pdd_settings' );

	if( empty( $settings ) ) {

		// Update old settings with new single option

		$general_settings = is_array( get_option( 'pdd_settings_general' ) )    ? get_option( 'pdd_settings_general' )  	: array();
		$gateway_settings = is_array( get_option( 'pdd_settings_gateways' ) )   ? get_option( 'pdd_settings_gateways' ) 	: array();
		$email_settings   = is_array( get_option( 'pdd_settings_emails' ) )     ? get_option( 'pdd_settings_emails' )   	: array();
		$style_settings   = is_array( get_option( 'pdd_settings_styles' ) )     ? get_option( 'pdd_settings_styles' )   	: array();
		$tax_settings     = is_array( get_option( 'pdd_settings_taxes' ) )      ? get_option( 'pdd_settings_taxes' )    	: array();
		$ext_settings     = is_array( get_option( 'pdd_settings_extensions' ) ) ? get_option( 'pdd_settings_extensions' )	: array();
		$license_settings = is_array( get_option( 'pdd_settings_licenses' ) )   ? get_option( 'pdd_settings_licenses' )		: array();
		$misc_settings    = is_array( get_option( 'pdd_settings_misc' ) )       ? get_option( 'pdd_settings_misc' )			: array();

		$settings = array_merge( $general_settings, $gateway_settings, $email_settings, $style_settings, $tax_settings, $ext_settings, $license_settings, $misc_settings );

		update_option( 'pdd_settings', $settings );

	}
	return apply_filters( 'pdd_get_settings', $settings );
}

/**
 * Add all settings sections and fields
 *
 * @since 1.0
 * @return void
*/
function pdd_register_settings() {

	if ( false == get_option( 'pdd_settings' ) ) {
		add_option( 'pdd_settings' );
	}

	foreach( pdd_get_registered_settings() as $tab => $settings ) {

		add_settings_section(
			'pdd_settings_' . $tab,
			__return_null(),
			'__return_false',
			'pdd_settings_' . $tab
		);

		foreach ( $settings as $option ) {

			$name = isset( $option['name'] ) ? $option['name'] : '';

			add_settings_field(
				'pdd_settings[' . $option['id'] . ']',
				$name,
				function_exists( 'pdd_' . $option['type'] . '_callback' ) ? 'pdd_' . $option['type'] . '_callback' : 'pdd_missing_callback',
				'pdd_settings_' . $tab,
				'pdd_settings_' . $tab,
				array(
					'id'      => isset( $option['id'] ) ? $option['id'] : null,
					'desc'    => ! empty( $option['desc'] ) ? $option['desc'] : '',
					'name'    => isset( $option['name'] ) ? $option['name'] : null,
					'section' => $tab,
					'size'    => isset( $option['size'] ) ? $option['size'] : null,
					'options' => isset( $option['options'] ) ? $option['options'] : '',
					'std'     => isset( $option['std'] ) ? $option['std'] : ''
				)
			);
		}

	}

	// Creates our settings in the options table
	register_setting( 'pdd_settings', 'pdd_settings', 'pdd_settings_sanitize' );

}
add_action('admin_init', 'pdd_register_settings');

/**
 * Retrieve the array of plugin settings
 *
 * @since 1.8
 * @return array
*/
function pdd_get_registered_settings() {

	/**
	 * 'Whitelisted' PDD settings, filters are provided for each settings
	 * section to allow extensions and other plugins to add their own settings
	 */
	$pdd_settings = array(
		/** General Settings */
		'general' => apply_filters( 'pdd_settings_general',
			array(
				'test_mode' => array(
					'id' => 'test_mode',
					'name' => __( 'Test Mode', 'pdd' ),
					'desc' => __( 'While in test mode no live transactions are processed. To fully use test mode, you must have a sandbox (test) account for the payment gateway you are testing.', 'pdd' ),
					'type' => 'checkbox'
				),
				'purchase_page' => array(
					'id' => 'purchase_page',
					'name' => __( 'Checkout Page', 'pdd' ),
					'desc' => __( 'This is the checkout page where buyers will complete their purchases. The [donation_checkout] short code must be on this page.', 'pdd' ),
					'type' => 'select',
					'options' => pdd_get_pages()
				),
				'success_page' => array(
					'id' => 'success_page',
					'name' => __( 'Success Page', 'pdd' ),
					'desc' => __( 'This is the page buyers are sent to after completing their purchases. The [pdd_receipt] short code should be on this page.', 'pdd' ),
					'type' => 'select',
					'options' => pdd_get_pages()
				),
				'failure_page' => array(
					'id' => 'failure_page',
					'name' => __( 'Failed Transaction Page', 'pdd' ),
					'desc' => __( 'This is the page buyers are sent to if their transaction is cancelled or fails', 'pdd' ),
					'type' => 'select',
					'options' => pdd_get_pages()
				),
				'purchase_history_page' => array(
					'id' => 'purchase_history_page',
					'name' => __( 'Purchase History Page', 'pdd' ),
					'desc' => __( 'This page shows a complete purchase history for the current user, including download links', 'pdd' ),
					'type' => 'select',
					'options' => pdd_get_pages()
				),
				'currency_settings' => array(
					'id' => 'currency_settings',
					'name' => '<strong>' . __( 'Currency Settings', 'pdd' ) . '</strong>',
					'desc' => __( 'Configure the currency options', 'pdd' ),
					'type' => 'header'
				),
				'currency' => array(
					'id' => 'currency',
					'name' => __( 'Currency', 'pdd' ),
					'desc' => __( 'Choose your currency. Note that some payment gateways have currency restrictions.', 'pdd' ),
					'type' => 'select',
					'options' => pdd_get_currencies()
				),
				'currency_position' => array(
					'id' => 'currency_position',
					'name' => __( 'Currency Position', 'pdd' ),
					'desc' => __( 'Choose the location of the currency sign.', 'pdd' ),
					'type' => 'select',
					'options' => array(
						'before' => __( 'Before - $10', 'pdd' ),
						'after' => __( 'After - 10$', 'pdd' )
					)
				),
				'thousands_separator' => array(
					'id' => 'thousands_separator',
					'name' => __( 'Thousands Separator', 'pdd' ),
					'desc' => __( 'The symbol (usually , or .) to separate thousands', 'pdd' ),
					'type' => 'text',
					'size' => 'small',
					'std' => ','
				),
				'decimal_separator' => array(
					'id' => 'decimal_separator',
					'name' => __( 'Decimal Separator', 'pdd' ),
					'desc' => __( 'The symbol (usually , or .) to separate decimal points', 'pdd' ),
					'type' => 'text',
					'size' => 'small',
					'std' => '.'
				),
				'tracking_settings' => array(
					'id' => 'tracking_settings',
					'name' => '<strong>' . __( 'Tracking Settings', 'pdd' ) . '</strong>',
					'desc' => '',
					'type' => 'header'
				),
				'allow_tracking' => array(
					'id' => 'allow_tracking',
					'name' => __( 'Allow Usage Tracking?', 'pdd' ),
					'desc' => __( 'Allow Pojo Digital Donations to anonymously track how this plugin is used and help us make the plugin better. Opt-in and receive a 20% discount code for any purchase from the <a href="https://easydigitaldownloads.com/extensions" target="_blank">Pojo Digital Donations store</a>. Your discount code will be emailed to you.', 'pdd' ),
					'type' => 'checkbox'
				),
				'uninstall_on_delete' => array(
					'id' => 'uninstall_on_delete',
					'name' => __( 'Remove Data on Uninstall?', 'pdd' ),
					'desc' => __( 'Check this box if you would like PDD to completely remove all of its data when the plugin is deleted.', 'pdd' ),
					'type' => 'checkbox'
				)
			)
		),
		/** Payment Gateways Settings */
		'gateways' => apply_filters('pdd_settings_gateways',
			array(
				'gateways' => array(
					'id' => 'gateways',
					'name' => __( 'Payment Gateways', 'pdd' ),
					'desc' => __( 'Choose the payment gateways you want to enable.', 'pdd' ),
					'type' => 'gateways',
					'options' => pdd_get_payment_gateways()
				),
				'default_gateway' => array(
					'id' => 'default_gateway',
					'name' => __( 'Default Gateway', 'pdd' ),
					'desc' => __( 'This gateway will be loaded automatically with the checkout page.', 'pdd' ),
					'type' => 'gateway_select',
					'options' => pdd_get_payment_gateways()
				),
				'accepted_cards' => array(
					'id' => 'accepted_cards',
					'name' => __( 'Accepted Payment Method Icons', 'pdd' ),
					'desc' => __( 'Display icons for the selected payment methods', 'pdd' ) . '<br/>' . __( 'You will also need to configure your gateway settings if you are accepting credit cards', 'pdd' ),
					'type' => 'multicheck',
					'options' => apply_filters('pdd_accepted_payment_icons', array(
							'mastercard' => 'Mastercard',
							'visa' => 'Visa',
							'americanexpress' => 'American Express',
							'discover' => 'Discover',
							'paypal' => 'PayPal'
						)
					)
				),
				'paypal' => array(
					'id' => 'paypal',
					'name' => '<strong>' . __( 'PayPal Settings', 'pdd' ) . '</strong>',
					'desc' => __( 'Configure the PayPal settings', 'pdd' ),
					'type' => 'header'
				),
				'paypal_email' => array(
					'id' => 'paypal_email',
					'name' => __( 'PayPal Email', 'pdd' ),
					'desc' => __( 'Enter your PayPal account\'s email', 'pdd' ),
					'type' => 'text',
					'size' => 'regular'
				),
				'paypal_page_style' => array(
					'id' => 'paypal_page_style',
					'name' => __( 'PayPal Page Style', 'pdd' ),
					'desc' => __( 'Enter the name of the page style to use, or leave blank for default', 'pdd' ),
					'type' => 'text',
					'size' => 'regular'
				),
				'disable_paypal_verification' => array(
					'id' => 'disable_paypal_verification',
					'name' => __( 'Disable PayPal IPN Verification', 'pdd' ),
					'desc' => __( 'If payments are not getting marked as complete, then check this box. This forces the site to use a slightly less secure method of verifying payments.', 'pdd' ),
					'type' => 'checkbox'
				)
			)
		),
		/** Emails Settings */
		'emails' => apply_filters('pdd_settings_emails',
			array(
				'email_template' => array(
					'id' => 'email_template',
					'name' => __( 'Email Template', 'pdd' ),
					'desc' => __( 'Choose a template. Click "Save Changes" then "Preview Donation Receipt" to see the new template.', 'pdd' ),
					'type' => 'select',
					'options' => pdd_get_email_templates()
				),
				'email_settings' => array(
					'id' => 'email_settings',
					'name' => '',
					'desc' => '',
					'type' => 'hook'
				),
				'from_name' => array(
					'id' => 'from_name',
					'name' => __( 'From Name', 'pdd' ),
					'desc' => __( 'The name donation receipts are said to come from. This should probably be your site or shop name.', 'pdd' ),
					'type' => 'text',
					'std'  => get_bloginfo( 'name' )
				),
				'from_email' => array(
					'id' => 'from_email',
					'name' => __( 'From Email', 'pdd' ),
					'desc' => __( 'Email to send donation receipts from. This will act as the "from" and "reply-to" address.', 'pdd' ),
					'type' => 'text',
					'std'  => get_bloginfo( 'admin_email' )
				),
				'purchase_subject' => array(
					'id' => 'purchase_subject',
					'name' => __( 'Donation Email Subject', 'pdd' ),
					'desc' => __( 'Enter the subject line for the donation receipt email', 'pdd' ),
					'type' => 'text',
					'std'  => __( 'Donation Receipt', 'pdd' )
				),
				'purchase_receipt' => array(
					'id' => 'purchase_receipt',
					'name' => __( 'Donation Receipt', 'pdd' ),
					'desc' => __('Enter the email that is sent to users after completing a successful donation. HTML is accepted. Available template tags:', 'pdd') . '<br/>' . pdd_get_emails_tags_list(),
					'type' => 'rich_editor',
					'std'  => __( "Dear", "pdd" ) . " {name},\n\n" . __( "Thank you for your donation.", "pdd" ) . "\n\n{sitename}"
				),
				'sale_notification_header' => array(
					'id' => 'sale_notification_header',
					'name' => '<strong>' . __('New Sale Notifications', 'pdd') . '</strong>',
					'desc' => __('Configure new sale notification emails', 'pdd'),
					'type' => 'header'
				),
				'sale_notification_subject' => array(
					'id' => 'sale_notification_subject',
					'name' => __( 'Sale Notification Subject', 'pdd' ),
					'desc' => __( 'Enter the subject line for the sale notification email', 'pdd' ),
					'type' => 'text',
					'std' => 'New donation submitted - Order #{payment_id}'
				),
				'sale_notification' => array(
					'id' => 'sale_notification',
					'name' => __( 'Sale Notification', 'pdd' ),
					'desc' => __( 'Enter the email that is sent to sale notification emails after completion of a donation. HTML is accepted. Available template tags:', 'pdd' ) . '<br/>' . pdd_get_emails_tags_list(),
					'type' => 'rich_editor',
					'std' => pdd_get_default_sale_notification_email()
				),
				'admin_notice_emails' => array(
					'id' => 'admin_notice_emails',
					'name' => __( 'Sale Notification Emails', 'pdd' ),
					'desc' => __( 'Enter the email address(es) that should receive a notification anytime a sale is made, one per line', 'pdd' ),
					'type' => 'textarea',
					'std'  => get_bloginfo( 'admin_email' )
				),
				'disable_admin_notices' => array(
					'id' => 'disable_admin_notices',
					'name' => __( 'Disable Admin Notifications', 'pdd' ),
					'desc' => __( 'Check this box if you do not want to receive emails when new sales are made.', 'pdd' ),
					'type' => 'checkbox'
				)
			)
		),
		/** Styles Settings */
		'styles' => apply_filters('pdd_settings_styles',
			array(
				'disable_styles' => array(
					'id' => 'disable_styles',
					'name' => __( 'Disable Styles', 'pdd' ),
					'desc' => __( 'Check this to disable all included styling of buttons, checkout fields, and all other elements.', 'pdd' ),
					'type' => 'checkbox'
				),
				'button_header' => array(
					'id' => 'button_header',
					'name' => '<strong>' . __( 'Buttons', 'pdd' ) . '</strong>',
					'desc' => __( 'Options for add to cart and donation buttons', 'pdd' ),
					'type' => 'header'
				),
				'button_style' => array(
					'id' => 'button_style',
					'name' => __( 'Default Button Style', 'pdd' ),
					'desc' => __( 'Choose the style you want to use for the buttons.', 'pdd' ),
					'type' => 'select',
					'options' => pdd_get_button_styles()
				),
				'checkout_color' => array(
					'id' => 'checkout_color',
					'name' => __( 'Default Button Color', 'pdd' ),
					'desc' => __( 'Choose the color you want to use for the buttons.', 'pdd' ),
					'type' => 'color_select',
					'options' => pdd_get_button_colors()
				)
			)
		),
		/** Extension Settings */
		'extensions' => apply_filters('pdd_settings_extensions',
			array()
		),
		'licenses' => apply_filters('pdd_settings_licenses',
			array()
		),
		/** Misc Settings */
		'misc' => apply_filters('pdd_settings_misc',
			array(
				'enable_billing_address' => array(
					'id' => 'enable_billing_address',
					'name' => __( 'Enable Billing Address', 'pdd' ),
					'desc' => __( 'Check this to enable billing address in shopping cart.', 'pdd' ),
					'type' => 'checkbox',
					'std'  => '1',
				),
				'disable_cart' => array(
					'id' => 'disable_cart',
					'name' => __( 'Disable Cart', 'pdd' ),
					'desc' => __( 'Check this to disable shopping cart.', 'pdd' ),
					'type' => 'checkbox',
				),
				'enable_ajax_cart' => array(
					'id' => 'enable_ajax_cart',
					'name' => __( 'Enable Ajax', 'pdd' ),
					'desc' => __( 'Check this to enable AJAX for the shopping cart.', 'pdd' ),
					'type' => 'checkbox',
					'std'  => '1',
				),
				'redirect_on_add' => array(
					'id' => 'redirect_on_add',
					'name' => __( 'Redirect to Checkout', 'pdd' ),
					'desc' => __( 'Immediately redirect to checkout after adding an item to the cart?', 'pdd' ),
					'type' => 'checkbox',
				),
				'enforce_ssl' => array(
					'id' => 'enforce_ssl',
					'name' => __( 'Enforce SSL on Checkout', 'pdd' ),
					'desc' => __( 'Check this to force users to be redirected to the secure checkout page. You must have an SSL certificate installed to use this option.', 'pdd' ),
					'type' => 'checkbox',
				),
				'logged_in_only' => array(
					'id' => 'logged_in_only',
					'name' => __( 'Disable Guest Checkout', 'pdd' ),
					'desc' => __( 'Require that users be logged-in to donation files.', 'pdd' ),
					'type' => 'checkbox',
				),
				'show_register_form' => array(
					'id' => 'show_register_form',
					'name' => __( 'Show Register / Login Form?', 'pdd' ),
					'desc' => __( 'Display the registration and login forms on the checkout page for non-logged-in users.', 'pdd' ),
					'type' => 'select',
					'options' => array(
						'both' => __( 'Registration and Login Forms', 'pdd' ),
						'registration' => __( 'Registration Form Only', 'pdd' ),
						'login' => __( 'Login Form Only', 'pdd' ),
						'none' => __( 'None', 'pdd' )
					),
					'std' => 'none',
				),
				'item_quantities' => array(
					'id' => 'item_quantities',
					'name' => __('Item Quantities', 'pdd'),
					'desc' => __('Allow item quantities to be changed at checkout.', 'pdd'),
					'type' => 'checkbox',
				),
				'allow_multiple_discounts' => array(
					'id' => 'allow_multiple_discounts',
					'name' => __('Multiple Discounts', 'pdd'),
					'desc' => __('Allow customers to use multiple discounts on the same donation?', 'pdd'),
					'type' => 'checkbox',
				),
				'enable_cart_saving' => array(
					'id' => 'enable_cart_saving',
					'name' => __( 'Enable Cart Saving', 'pdd' ),
					'desc' => __( 'Check this to enable cart saving on the checkout', 'pdd' ),
					'type' => 'checkbox',
				),
				'accounting_settings' => array(
					'id' => 'accounting_settings',
					'name' => '<strong>' . __( 'Accounting Settings', 'pdd' ) . '</strong>',
					'desc' => '',
					'type' => 'header',
				),
				'enable_skus' => array(
					'id' => 'enable_skus',
					'name' => __( 'Enable SKU Entry', 'pdd' ),
					'desc' => __( 'Check this box to allow entry of product SKUs. SKUs will be shown on donation receipt and exported payments histories.', 'pdd' ),
					'type' => 'checkbox',
				),
				'enable_sequential' => array(
					'id' => 'enable_sequential',
					'name' => __( 'Sequential Order Numbers', 'pdd' ),
					'desc' => __( 'Check this box to sequential order numbers.', 'pdd' ),
					'type' => 'checkbox'
				),
				'sequential_start' => array(
					'id' => 'sequential_start',
					'name' => __( 'Sequential Starting Number', 'pdd' ),
					'desc' => __( 'The number that sequential order numbers should start at.', 'pdd' ),
					'type' => 'number',
					'size' => 'small',
					'std'  => '1',
				),
				'sequential_prefix' => array(
					'id' => 'sequential_prefix',
					'name' => __( 'Sequential Number Prefix', 'pdd' ),
					'desc' => __( 'A prefix to prepend to all sequential order numbers.', 'pdd' ),
					'type' => 'text'
				),
				'sequential_postfix' => array(
					'id' => 'sequential_postfix',
					'name' => __( 'Sequential Number Postfix', 'pdd' ),
					'desc' => __( 'A postfix to append to all sequential order numbers.', 'pdd' ),
					'type' => 'text',
				),
				'terms' => array(
					'id' => 'terms',
					'name' => '<strong>' . __( 'Terms of Agreement', 'pdd' ) . '</strong>',
					'desc' => '',
					'type' => 'header'
				),
				'show_agree_to_terms' => array(
					'id' => 'show_agree_to_terms',
					'name' => __( 'Agree to Terms', 'pdd' ),
					'desc' => __( 'Check this to show an agree to terms on the checkout that users must agree to before purchasing.', 'pdd' ),
					'type' => 'checkbox'
				),
				'agree_label' => array(
					'id' => 'agree_label',
					'name' => __( 'Agree to Terms Label', 'pdd' ),
					'desc' => __( 'Label shown next to the agree to terms check box.', 'pdd' ),
					'type' => 'text',
					'size' => 'regular'
				),
				'agree_text' => array(
					'id' => 'agree_text',
					'name' => __( 'Agreement Text', 'pdd' ),
					'desc' => __( 'If Agree to Terms is checked, enter the agreement terms here.', 'pdd' ),
					'type' => 'rich_editor'
				),
				'checkout_label' => array(
					'id' => 'checkout_label',
					'name' => __( 'Complete Donate Now Text', 'pdd' ),
					'desc' => __( 'The button label for completing a donation.', 'pdd' ),
					'type' => 'text',
					'std' => __( 'Donate Now', 'pdd' )
				),
				'add_to_cart_text' => array(
					'id' => 'add_to_cart_text',
					'name' => __( 'Add to Cart Text', 'pdd' ),
					'desc' => __( 'Text shown on the Add to Cart Buttons', 'pdd' ),
					'type' => 'text',
					'std'  => __( 'Donate', 'pdd' )
				),
			)
		)
	);

	return $pdd_settings;
}

/**
 * Settings Sanitization
 *
 * Adds a settings error (for the updated message)
 * At some point this will validate input
 *
 * @since 1.0.8.2
 *
 * @param array $input The value inputted in the field
 *
 * @return string $input Sanitizied value
 */
function pdd_settings_sanitize( $input = array() ) {

	global $pdd_options;

	if ( empty( $_POST['_wp_http_referer'] ) ) {
		return $input;
	}

	parse_str( $_POST['_wp_http_referer'], $referrer );

	$settings = pdd_get_registered_settings();
	$tab      = isset( $referrer['tab'] ) ? $referrer['tab'] : 'general';

	$input = $input ? $input : array();
	$input = apply_filters( 'pdd_settings_' . $tab . '_sanitize', $input );

	// Loop through each setting being saved and pass it through a sanitization filter
	foreach ( $input as $key => $value ) {

		// Get the setting type (checkbox, select, etc)
		$type = isset( $settings[$tab][$key]['type'] ) ? $settings[$tab][$key]['type'] : false;

		if ( $type ) {
			// Field type specific filter
			$input[$key] = apply_filters( 'pdd_settings_sanitize_' . $type, $value, $key );
		}

		// General filter
		$input[$key] = apply_filters( 'pdd_settings_sanitize', $value, $key );
	}

	// Loop through the whitelist and unset any that are empty for the tab being saved
	if ( ! empty( $settings[$tab] ) ) {
		foreach ( $settings[$tab] as $key => $value ) {

			// settings used to have numeric keys, now they have keys that match the option ID. This ensures both methods work
			if ( is_numeric( $key ) ) {
				$key = $value['id'];
			}

			if ( empty( $input[$key] ) ) {
				unset( $pdd_options[$key] );
			}

		}
	}

	// Merge our new settings with the existing
	$output = array_merge( $pdd_options, $input );

	add_settings_error( 'pdd-notices', '', __( 'Settings updated.', 'pdd' ), 'updated' );

	return $output;
}

/**
 * Misc Settings Sanitization
 *
 * @since 1.6
 * @param array $input The value inputted in the field
 * @return string $input Sanitizied value
 */
function pdd_settings_sanitize_misc( $input ) {

	if( ! empty( $input['enable_sequential'] ) && ! pdd_get_option( 'enable_sequential' ) ) {

		// Shows an admin notice about upgrading previous order numbers
		PDD()->session->set( 'upgrade_sequential', '1' );

	}

	return $input;
}
add_filter( 'pdd_settings_misc_sanitize', 'pdd_settings_sanitize_misc' );

/**
 * Taxes Settings Sanitization
 *
 * Adds a settings error (for the updated message)
 * This also saves the tax rates table
 *
 * @since 1.6
 * @param array $input The value inputted in the field
 * @return string $input Sanitizied value
 */
function pdd_settings_sanitize_taxes( $input ) {

	$new_rates = ! empty( $_POST['tax_rates'] ) ? array_values( $_POST['tax_rates'] ) : array();

	update_option( 'pdd_tax_rates', $new_rates );

	return $input;
}
add_filter( 'pdd_settings_taxes_sanitize', 'pdd_settings_sanitize_taxes' );

/**
 * Sanitize text fields
 *
 * @since 1.8
 * @param array $input The field value
 * @return string $input Sanitizied value
 */
function pdd_sanitize_text_field( $input ) {
	return trim( $input );
}
add_filter( 'pdd_settings_sanitize_text', 'pdd_sanitize_text_field' );

/**
 * Retrieve settings tabs
 *
 * @since 1.8
 * @return array $tabs
 */
function pdd_get_settings_tabs() {

	$settings = pdd_get_registered_settings();

	$tabs             = array();
	$tabs['general']  = __( 'General', 'pdd' );
	$tabs['gateways'] = __( 'Payment Gateways', 'pdd' );
	$tabs['emails']   = __( 'Emails', 'pdd' );
	$tabs['styles']   = __( 'Styles', 'pdd' );

	if( ! empty( $settings['extensions'] ) ) {
		$tabs['extensions'] = __( 'Extensions', 'pdd' );
	}
	if( ! empty( $settings['licenses'] ) ) {
		$tabs['licenses'] = __( 'Licenses', 'pdd' );
	}

	$tabs['misc']      = __( 'Misc', 'pdd' );

	return apply_filters( 'pdd_settings_tabs', $tabs );
}

/**
 * Retrieve a list of all published pages
 *
 * On large sites this can be expensive, so only load if on the settings page or $force is set to true
 *
 * @since 1.9.5
 * @param bool $force Force the pages to be loaded even if not on settings
 * @return array $pages_options An array of the pages
 */
function pdd_get_pages( $force = false ) {

	$pages_options = array( 0 => '' ); // Blank option

	if( ( ! isset( $_GET['page'] ) || 'pdd-settings' != $_GET['page'] ) && ! $force ) {
		return $pages_options;
	}

	$pages = get_pages();
	if ( $pages ) {
		foreach ( $pages as $page ) {
			$pages_options[ $page->ID ] = $page->post_title;
		}
	}

	return $pages_options;
}

/**
 * Header Callback
 *
 * Renders the header.
 *
 * @since 1.0
 * @param array $args Arguments passed by the setting
 * @return void
 */
function pdd_header_callback( $args ) {
	echo '<hr/>';
}

/**
 * Checkbox Callback
 *
 * Renders checkboxes.
 *
 * @since 1.0
 * @param array $args Arguments passed by the setting
 * @global $pdd_options Array of all the PDD Options
 * @return void
 */
function pdd_checkbox_callback( $args ) {
	global $pdd_options;

	$checked = isset( $pdd_options[ $args[ 'id' ] ] ) ? checked( 1, $pdd_options[ $args[ 'id' ] ], false ) : '';
	$html = '<input type="checkbox" id="pdd_settings[' . $args['id'] . ']" name="pdd_settings[' . $args['id'] . ']" value="1" ' . $checked . '/>';
	$html .= '<label for="pdd_settings[' . $args['id'] . ']"> '  . $args['desc'] . '</label>';

	echo $html;
}

/**
 * Multicheck Callback
 *
 * Renders multiple checkboxes.
 *
 * @since 1.0
 * @param array $args Arguments passed by the setting
 * @global $pdd_options Array of all the PDD Options
 * @return void
 */
function pdd_multicheck_callback( $args ) {
	global $pdd_options;

	if ( ! empty( $args['options'] ) ) {
		foreach( $args['options'] as $key => $option ):
			if( isset( $pdd_options[$args['id']][$key] ) ) { $enabled = $option; } else { $enabled = NULL; }
			echo '<input name="pdd_settings[' . $args['id'] . '][' . $key . ']" id="pdd_settings[' . $args['id'] . '][' . $key . ']" type="checkbox" value="' . $option . '" ' . checked($option, $enabled, false) . '/>&nbsp;';
			echo '<label for="pdd_settings[' . $args['id'] . '][' . $key . ']">' . $option . '</label><br/>';
		endforeach;
		echo '<p class="description">' . $args['desc'] . '</p>';
	}
}

/**
 * Radio Callback
 *
 * Renders radio boxes.
 *
 * @since 1.3.3
 * @param array $args Arguments passed by the setting
 * @global $pdd_options Array of all the PDD Options
 * @return void
 */
function pdd_radio_callback( $args ) {
	global $pdd_options;

	foreach ( $args['options'] as $key => $option ) :
		$checked = false;

		if ( isset( $pdd_options[ $args['id'] ] ) && $pdd_options[ $args['id'] ] == $key )
			$checked = true;
		elseif( isset( $args['std'] ) && $args['std'] == $key && ! isset( $pdd_options[ $args['id'] ] ) )
			$checked = true;

		echo '<input name="pdd_settings[' . $args['id'] . ']"" id="pdd_settings[' . $args['id'] . '][' . $key . ']" type="radio" value="' . $key . '" ' . checked(true, $checked, false) . '/>&nbsp;';
		echo '<label for="pdd_settings[' . $args['id'] . '][' . $key . ']">' . $option . '</label><br/>';
	endforeach;

	echo '<p class="description">' . $args['desc'] . '</p>';
}

/**
 * Gateways Callback
 *
 * Renders gateways fields.
 *
 * @since 1.0
 * @param array $args Arguments passed by the setting
 * @global $pdd_options Array of all the PDD Options
 * @return void
 */
function pdd_gateways_callback( $args ) {
	global $pdd_options;

	foreach ( $args['options'] as $key => $option ) :
		if ( isset( $pdd_options['gateways'][ $key ] ) )
			$enabled = '1';
		else
			$enabled = null;

		echo '<input name="pdd_settings[' . $args['id'] . '][' . $key . ']"" id="pdd_settings[' . $args['id'] . '][' . $key . ']" type="checkbox" value="1" ' . checked('1', $enabled, false) . '/>&nbsp;';
		echo '<label for="pdd_settings[' . $args['id'] . '][' . $key . ']">' . $option['admin_label'] . '</label><br/>';
	endforeach;
}

/**
 * Gateways Callback (drop down)
 *
 * Renders gateways select menu
 *
 * @since 1.5
 * @param array $args Arguments passed by the setting
 * @global $pdd_options Array of all the PDD Options
 * @return void
 */
function pdd_gateway_select_callback($args) {
	global $pdd_options;

	echo '<select name="pdd_settings[' . $args['id'] . ']"" id="pdd_settings[' . $args['id'] . ']">';

	foreach ( $args['options'] as $key => $option ) :
		$selected = isset( $pdd_options[ $args['id'] ] ) ? selected( $key, $pdd_options[$args['id']], false ) : '';
		echo '<option value="' . esc_attr( $key ) . '"' . $selected . '>' . esc_html( $option['admin_label'] ) . '</option>';
	endforeach;

	echo '</select>';
	echo '<label for="pdd_settings[' . $args['id'] . ']"> '  . $args['desc'] . '</label>';
}

/**
 * Text Callback
 *
 * Renders text fields.
 *
 * @since 1.0
 * @param array $args Arguments passed by the setting
 * @global $pdd_options Array of all the PDD Options
 * @return void
 */
function pdd_text_callback( $args ) {
	global $pdd_options;

	if ( isset( $pdd_options[ $args['id'] ] ) )
		$value = $pdd_options[ $args['id'] ];
	else
		$value = isset( $args['std'] ) ? $args['std'] : '';

	$size = ( isset( $args['size'] ) && ! is_null( $args['size'] ) ) ? $args['size'] : 'regular';
	$html = '<input type="text" class="' . $size . '-text" id="pdd_settings[' . $args['id'] . ']" name="pdd_settings[' . $args['id'] . ']" value="' . esc_attr( stripslashes( $value ) ) . '"/>';
	$html .= '<label for="pdd_settings[' . $args['id'] . ']"> '  . $args['desc'] . '</label>';

	echo $html;
}

/**
 * Number Callback
 *
 * Renders number fields.
 *
 * @since 1.9
 * @param array $args Arguments passed by the setting
 * @global $pdd_options Array of all the PDD Options
 * @return void
 */
function pdd_number_callback( $args ) {
	global $pdd_options;

	if ( isset( $pdd_options[ $args['id'] ] ) )
		$value = $pdd_options[ $args['id'] ];
	else
		$value = isset( $args['std'] ) ? $args['std'] : '';

	$max  = isset( $args['max'] ) ? $args['max'] : 999999;
	$min  = isset( $args['min'] ) ? $args['min'] : 0;
	$step = isset( $args['step'] ) ? $args['step'] : 1;

	$size = ( isset( $args['size'] ) && ! is_null( $args['size'] ) ) ? $args['size'] : 'regular';
	$html = '<input type="number" step="' . esc_attr( $step ) . '" max="' . esc_attr( $max ) . '" min="' . esc_attr( $min ) . '" class="' . $size . '-text" id="pdd_settings[' . $args['id'] . ']" name="pdd_settings[' . $args['id'] . ']" value="' . esc_attr( stripslashes( $value ) ) . '"/>';
	$html .= '<label for="pdd_settings[' . $args['id'] . ']"> '  . $args['desc'] . '</label>';

	echo $html;
}

/**
 * Textarea Callback
 *
 * Renders textarea fields.
 *
 * @since 1.0
 * @param array $args Arguments passed by the setting
 * @global $pdd_options Array of all the PDD Options
 * @return void
 */
function pdd_textarea_callback( $args ) {
	global $pdd_options;

	if ( isset( $pdd_options[ $args['id'] ] ) )
		$value = $pdd_options[ $args['id'] ];
	else
		$value = isset( $args['std'] ) ? $args['std'] : '';

	$size = ( isset( $args['size'] ) && ! is_null( $args['size'] ) ) ? $args['size'] : 'regular';
	$html = '<textarea class="large-text" cols="50" rows="5" id="pdd_settings[' . $args['id'] . ']" name="pdd_settings[' . $args['id'] . ']">' . esc_textarea( stripslashes( $value ) ) . '</textarea>';
	$html .= '<label for="pdd_settings[' . $args['id'] . ']"> '  . $args['desc'] . '</label>';

	echo $html;
}

/**
 * Password Callback
 *
 * Renders password fields.
 *
 * @since 1.3
 * @param array $args Arguments passed by the setting
 * @global $pdd_options Array of all the PDD Options
 * @return void
 */
function pdd_password_callback( $args ) {
	global $pdd_options;

	if ( isset( $pdd_options[ $args['id'] ] ) )
		$value = $pdd_options[ $args['id'] ];
	else
		$value = isset( $args['std'] ) ? $args['std'] : '';

	$size = ( isset( $args['size'] ) && ! is_null( $args['size'] ) ) ? $args['size'] : 'regular';
	$html = '<input type="password" class="' . $size . '-text" id="pdd_settings[' . $args['id'] . ']" name="pdd_settings[' . $args['id'] . ']" value="' . esc_attr( $value ) . '"/>';
	$html .= '<label for="pdd_settings[' . $args['id'] . ']"> '  . $args['desc'] . '</label>';

	echo $html;
}

/**
 * Missing Callback
 *
 * If a function is missing for settings callbacks alert the user.
 *
 * @since 1.3.1
 * @param array $args Arguments passed by the setting
 * @return void
 */
function pdd_missing_callback($args) {
	printf( __( 'The callback function used for the <strong>%s</strong> setting is missing.', 'pdd' ), $args['id'] );
}

/**
 * Select Callback
 *
 * Renders select fields.
 *
 * @since 1.0
 * @param array $args Arguments passed by the setting
 * @global $pdd_options Array of all the PDD Options
 * @return void
 */
function pdd_select_callback($args) {
	global $pdd_options;

	if ( isset( $pdd_options[ $args['id'] ] ) )
		$value = $pdd_options[ $args['id'] ];
	else
		$value = isset( $args['std'] ) ? $args['std'] : '';

	$html = '<select id="pdd_settings[' . $args['id'] . ']" name="pdd_settings[' . $args['id'] . ']"/>';

	foreach ( $args['options'] as $option => $name ) :
		$selected = selected( $option, $value, false );
		$html .= '<option value="' . $option . '" ' . $selected . '>' . $name . '</option>';
	endforeach;

	$html .= '</select>';
	$html .= '<label for="pdd_settings[' . $args['id'] . ']"> '  . $args['desc'] . '</label>';

	echo $html;
}

/**
 * Color select Callback
 *
 * Renders color select fields.
 *
 * @since 1.8
 * @param array $args Arguments passed by the setting
 * @global $pdd_options Array of all the PDD Options
 * @return void
 */
function pdd_color_select_callback( $args ) {
	global $pdd_options;

	if ( isset( $pdd_options[ $args['id'] ] ) )
		$value = $pdd_options[ $args['id'] ];
	else
		$value = isset( $args['std'] ) ? $args['std'] : '';

	$html = '<select id="pdd_settings[' . $args['id'] . ']" name="pdd_settings[' . $args['id'] . ']"/>';

	foreach ( $args['options'] as $option => $color ) :
		$selected = selected( $option, $value, false );
		$html .= '<option value="' . $option . '" ' . $selected . '>' . $color['label'] . '</option>';
	endforeach;

	$html .= '</select>';
	$html .= '<label for="pdd_settings[' . $args['id'] . ']"> '  . $args['desc'] . '</label>';

	echo $html;
}

/**
 * Rich Editor Callback
 *
 * Renders rich editor fields.
 *
 * @since 1.0
 * @param array $args Arguments passed by the setting
 * @global $pdd_options Array of all the PDD Options
 * @global $wp_version WordPress Version
 */
function pdd_rich_editor_callback( $args ) {
	global $pdd_options, $wp_version;

	if ( isset( $pdd_options[ $args['id'] ] ) )
		$value = $pdd_options[ $args['id'] ];
	else
		$value = isset( $args['std'] ) ? $args['std'] : '';

	if ( $wp_version >= 3.3 && function_exists( 'wp_editor' ) ) {
		ob_start();
		wp_editor( stripslashes( $value ), 'pdd_settings_' . $args['id'], array( 'textarea_name' => 'pdd_settings[' . $args['id'] . ']' ) );
		$html = ob_get_clean();
	} else {
		$html = '<textarea class="large-text" rows="10" id="pdd_settings[' . $args['id'] . ']" name="pdd_settings[' . $args['id'] . ']">' . esc_textarea( stripslashes( $value ) ) . '</textarea>';
	}

	$html .= '<br/><label for="pdd_settings[' . $args['id'] . ']"> '  . $args['desc'] . '</label>';

	echo $html;
}

/**
 * Upload Callback
 *
 * Renders upload fields.
 *
 * @since 1.0
 * @param array $args Arguments passed by the setting
 * @global $pdd_options Array of all the PDD Options
 * @return void
 */
function pdd_upload_callback( $args ) {
	global $pdd_options;

	if ( isset( $pdd_options[ $args['id'] ] ) )
		$value = $pdd_options[$args['id']];
	else
		$value = isset($args['std']) ? $args['std'] : '';

	$size = ( isset( $args['size'] ) && ! is_null( $args['size'] ) ) ? $args['size'] : 'regular';
	$html = '<input type="text" class="' . $size . '-text pdd_upload_field" id="pdd_settings[' . $args['id'] . ']" name="pdd_settings[' . $args['id'] . ']" value="' . esc_attr( stripslashes( $value ) ) . '"/>';
	$html .= '<span>&nbsp;<input type="button" class="pdd_settings_upload_button button-secondary" value="' . __( 'Upload File', 'pdd' ) . '"/></span>';
	$html .= '<label for="pdd_settings[' . $args['id'] . ']"> '  . $args['desc'] . '</label>';

	echo $html;
}


/**
 * Color picker Callback
 *
 * Renders color picker fields.
 *
 * @since 1.6
 * @param array $args Arguments passed by the setting
 * @global $pdd_options Array of all the PDD Options
 * @return void
 */
function pdd_color_callback( $args ) {
	global $pdd_options;

	if ( isset( $pdd_options[ $args['id'] ] ) )
		$value = $pdd_options[ $args['id'] ];
	else
		$value = isset( $args['std'] ) ? $args['std'] : '';

	$default = isset( $args['std'] ) ? $args['std'] : '';

	$size = ( isset( $args['size'] ) && ! is_null( $args['size'] ) ) ? $args['size'] : 'regular';
	$html = '<input type="text" class="pdd-color-picker" id="pdd_settings[' . $args['id'] . ']" name="pdd_settings[' . $args['id'] . ']" value="' . esc_attr( $value ) . '" data-default-color="' . esc_attr( $default ) . '" />';
	$html .= '<label for="pdd_settings[' . $args['id'] . ']"> '  . $args['desc'] . '</label>';

	echo $html;
}

/**
 * Shop States Callback
 *
 * Renders states drop down based on the currently selected country
 *
 * @since 1.6
 * @param array $args Arguments passed by the setting
 * @global $pdd_options Array of all the PDD Options
 * @return void
 */
function pdd_shop_states_callback($args) {
	global $pdd_options;

	$states = pdd_get_shop_states();
	$class  = empty( $states ) ? ' class="pdd-no-states"' : '';
	$html   = '<select id="pdd_settings[' . $args['id'] . ']" name="pdd_settings[' . $args['id'] . ']"' . $class . '/>';

	foreach ( $states as $option => $name ) :
		$selected = isset( $pdd_options[ $args['id'] ] ) ? selected( $option, $pdd_options[$args['id']], false ) : '';
		$html .= '<option value="' . $option . '" ' . $selected . '>' . $name . '</option>';
	endforeach;

	$html .= '</select>';
	$html .= '<label for="pdd_settings[' . $args['id'] . ']"> '  . $args['desc'] . '</label>';

	echo $html;
}

/**
 * Tax Rates Callback
 *
 * Renders tax rates table
 *
 * @since 1.6
 * @param array $args Arguments passed by the setting
 * @global $pdd_options Array of all the PDD Options
 * @return void
 */
function pdd_tax_rates_callback($args) {
	global $pdd_options;
	$rates = pdd_get_tax_rates();
	ob_start(); ?>
	<p><?php echo $args['desc']; ?></p>
	<table id="pdd_tax_rates" class="wp-list-table widefat fixed posts">
		<thead>
			<tr>
				<th scope="col" class="pdd_tax_country"><?php _e( 'Country', 'pdd' ); ?></th>
				<th scope="col" class="pdd_tax_state"><?php _e( 'State / Province', 'pdd' ); ?></th>
				<th scope="col" class="pdd_tax_global" title="<?php _e( 'Apply rate to whole country, regardless of state / province', 'pdd' ); ?>"><?php _e( 'Country Wide', 'pdd' ); ?></th>
				<th scope="col" class="pdd_tax_rate"><?php _e( 'Rate', 'pdd' ); ?></th>
				<th scope="col"><?php _e( 'Remove', 'pdd' ); ?></th>
			</tr>
		</thead>
		<?php if( ! empty( $rates ) ) : ?>
			<?php foreach( $rates as $key => $rate ) : ?>
			<tr>
				<td class="pdd_tax_country">
					<?php
					echo PDD()->html->select( array(
						'options'          => pdd_get_country_list(),
						'name'             => 'tax_rates[' . $key . '][country]',
						'selected'         => $rate['country'],
						'show_option_all'  => false,
						'show_option_none' => false,
						'class'            => 'pdd-select pdd-tax-country'
					) );
					?>
				</td>
				<td class="pdd_tax_state">
					<?php
					$states = pdd_get_shop_states( $rate['country'] );
					if( ! empty( $states ) ) {
						echo PDD()->html->select( array(
							'options'          => $states,
							'name'             => 'tax_rates[' . $key . '][state]',
							'selected'         => $rate['state'],
							'show_option_all'  => false,
							'show_option_none' => false
						) );
					} else {
						echo PDD()->html->text( array(
							'name'             => 'tax_rates[' . $key . '][state]', $rate['state']
						) );
					}
					?>
				</td>
				<td class="pdd_tax_global">
					<input type="checkbox" name="tax_rates[<?php echo $key; ?>][global]" id="tax_rates[<?php echo $key; ?>][global]" value="1"<?php checked( true, ! empty( $rate['global'] ) ); ?>/>
					<label for="tax_rates[<?php echo $key; ?>][global]"><?php _e( 'Apply to whole country', 'pdd' ); ?></label>
				</td>
				<td class="pdd_tax_rate"><input type="number" class="small-text" step="0.0001" min="0.0" max="99" name="tax_rates[<?php echo $key; ?>][rate]" value="<?php echo $rate['rate']; ?>"/></td>
				<td><span class="pdd_remove_tax_rate button-secondary"><?php _e( 'Remove Rate', 'pdd' ); ?></span></td>
			</tr>
			<?php endforeach; ?>
		<?php else : ?>
			<tr>
				<td class="pdd_tax_country">
					<?php
					echo PDD()->html->select( array(
						'options'          => pdd_get_country_list(),
						'name'             => 'tax_rates[0][country]',
						'show_option_all'  => false,
						'show_option_none' => false,
						'class'            => 'pdd-select pdd-tax-country'
					) ); ?>
				</td>
				<td class="pdd_tax_state">
					<?php echo PDD()->html->text( array(
						'name'             => 'tax_rates[0][state]'
					) ); ?>
				</td>
				<td class="pdd_tax_global">
					<input type="checkbox" name="tax_rates[0][global]" value="1"/>
					<label for="tax_rates[0][global]"><?php _e( 'Apply to whole country', 'pdd' ); ?></label>
				</td>
				<td class="pdd_tax_rate"><input type="number" class="small-text" step="0.0001" min="0.0" name="tax_rates[0][rate]" value=""/></td>
				<td><span class="pdd_remove_tax_rate button-secondary"><?php _e( 'Remove Rate', 'pdd' ); ?></span></td>
			</tr>
		<?php endif; ?>
	</table>
	<p>
		<span class="button-secondary" id="pdd_add_tax_rate"><?php _e( 'Add Tax Rate', 'pdd' ); ?></span>
	</p>
	<?php
	echo ob_get_clean();
}


/**
 * Registers the license field callback for Software Licensing
 *
 * @since 1.5
 * @param array $args Arguments passed by the setting
 * @global $pdd_options Array of all the PDD Options
 * @return void
 */
if ( ! function_exists( 'pdd_license_key_callback' ) ) {
	function pdd_license_key_callback( $args ) {
		global $pdd_options;

		if ( isset( $pdd_options[ $args['id'] ] ) )
			$value = $pdd_options[ $args['id'] ];
		else
			$value = isset( $args['std'] ) ? $args['std'] : '';

		$size = ( isset( $args['size'] ) && ! is_null( $args['size'] ) ) ? $args['size'] : 'regular';
		$html = '<input type="text" class="' . $size . '-text" id="pdd_settings[' . $args['id'] . ']" name="pdd_settings[' . $args['id'] . ']" value="' . esc_attr( $value ) . '"/>';

		if ( 'valid' == get_option( $args['options']['is_valid_license_option'] ) ) {
			$html .= '<input type="submit" class="button-secondary" name="' . $args['id'] . '_deactivate" value="' . __( 'Deactivate License',  'pdd' ) . '"/>';
		}
		$html .= '<label for="pdd_settings[' . $args['id'] . ']"> '  . $args['desc'] . '</label>';

		echo $html;
	}
}

/**
 * Hook Callback
 *
 * Adds a do_action() hook in place of the field
 *
 * @since 1.0.8.2
 * @param array $args Arguments passed by the setting
 * @return void
 */
function pdd_hook_callback( $args ) {
	do_action( 'pdd_' . $args['id'] );
}

/**
 * Set manage_shop_settings as the cap required to save PDD settings pages
 *
 * @since 1.9
 * @return string capability required
 */
function pdd_set_settings_cap() {
	return 'manage_shop_settings';
}
add_filter( 'option_page_capability_pdd_settings', 'pdd_set_settings_cap' );
