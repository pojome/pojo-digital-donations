<?php
/**
 * Admin Footer
 *
 * @package     PDD
 * @subpackage  Admin/Footer
 * @copyright   Copyright (c) 2014, Pippin Williamson
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.0
 */

// Exit if accessed directly
if( !defined( 'ABSPATH' ) ) exit;

/**
 * Add rating links to the admin dashboard
 *
 * @since	    1.8.5
 * @global		string $typenow
 * @param       string $footer_text The existing footer text
 * @return      string
 */
function pdd_admin_rate_us( $footer_text ) {
	global $typenow;

	if ( $typenow == 'pdd_camp' ) {
		$rate_text = sprintf( __( 'Thank you for using <a href="%1$s" target="_blank">Pojo Digital Donations</a>! Please <a href="%2$s" target="_blank">rate us</a> on <a href="%2$s" target="_blank">WordPress.org</a>', 'pdd' ),
			'https://easydigitaldownloads.com',
			'http://wordpress.org/support/view/plugin-reviews/easy-digital-downloads?filter=5#postform'
		);

		return str_replace( '</span>', '', $footer_text ) . ' | ' . $rate_text . '</span>';
	} else {
		return $footer_text;
	}
}
add_filter( 'admin_footer_text', 'pdd_admin_rate_us' );