<?php
/**
 * Contextual Help
 *
 * @package     PDD
 * @subpackage  Admin/Downloads
 * @copyright   Copyright (c) 2014, Pippin Williamson
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.2.3
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Adds the Contextual Help for the main Downloads page
 *
 * @since 1.2.3
 * @return void
 */
function pdd_camps_contextual_help() {
	$screen = get_current_screen();

	if ( $screen->id != 'pdd_camp' )
		return;

	$screen->set_help_sidebar(
		'<p><strong>' . sprintf( __( 'For more information:', 'pdd' ) . '</strong></p>' .
		'<p>' . sprintf( __( 'Visit the <a href="%s">documentation</a> on the Pojo Digital Donations website.', 'pdd' ), esc_url( 'https://easydigitaldownloads.com/documentation/' ) ) ) . '</p>' .
		'<p>' . sprintf(
					__( '<a href="%s">Post an issue</a> on <a href="%s">GitHub</a>. View <a href="%s">extensions</a> or <a href="%s">themes</a>.', 'pdd' ),
					esc_url( 'https://github.com/pojome/pojo-digital-donations/issues' ),
					esc_url( 'https://github.com/pojome/pojo-digital-donations' ),
					esc_url( 'https://easydigitaldownloads.com/extensions/' ),
					esc_url( 'https://easydigitaldownloads.com/themes/' )
				) . '</p>'
	);

	$screen->add_help_tab( array(
		'id'	    => 'pdd-download-configuration',
		'title'	    => sprintf( __( '%s Settings', 'pdd' ), pdd_get_label_singular() ),
		'content'	=>
			'<p>' . __( '<strong>File Download Limit</strong> - Define how many times customers are allowed to download their purchased files. Leave at 0 for unlimited. Resending the purchase receipt will permit the customer one additional download if their limit has already been reached.', 'pdd' ) . '</p>' .

			'<p>' . __( '<strong>Accounting Options</strong> - If enabled, define an individual SKU or product number for this download.', 'pdd' ) . '</p>' .

			'<p>' . __( '<strong>Button Options</strong> - Disable the automatic output the purchase button. If disabled, no button will be added to the download page unless the <code>[donate_link]</code> shortcode is used.', 'pdd' ) . '</p>'
	) );

	$screen->add_help_tab( array(
		'id'	    => 'pdd-download-prices',
		'title'	    => sprintf( __( '%s Prices', 'pdd' ), pdd_get_label_singular() ),
		'content'	=>
			'<p>' . __( '<strong>Enable variable pricing</strong> - By enabling variable pricing, multiple download options and prices can be configured.', 'pdd' ) . '</p>' .

			'<p>' . __( '<strong>Enable multi-option purchases</strong> - By enabling multi-option purchases customers can add multiple variable price items to their cart at once.', 'pdd' ) . '</p>'
	) );

	$screen->add_help_tab( array(
		'id'	    => 'pdd-download-files',
		'title'	    => sprintf( __( '%s Files', 'pdd' ), pdd_get_label_singular() ),
		'content'	=>
			'<p>' . __( '<strong>Product Type Options</strong> - Choose a default product type or a bundle. Bundled products automatically include access other download&#39;s files when purchased.', 'pdd' ) . '</p>' . 

			'<p>' . __( '<strong>File Downloads</strong> - Define download file names and their respsective file URL. Multiple files can be assigned to a single price, or variable prices.', 'pdd' ) . '</p>'
	) );


	$screen->add_help_tab( array(
		'id'	    => 'pdd-product-notes',
		'title'	    => sprintf( __( '%s Notes', 'pdd' ), pdd_get_label_singular() ),
		'content'	=> '<p>' . __( 'Special notes or instructions for the product. These notes will be added to the purchase receipt, and additionaly may be used by some extensions or themes on the frontend.', 'pdd' ) . '</p>'
	) );

	$colors = array(
		'gray', 'pink', 'blue', 'green', 'teal', 'black', 'dark gray', 'orange', 'purple', 'slate'
	);

	$screen->add_help_tab( array(
		'id'	    => 'pdd-purchase-shortcode',
		'title'	    => __( 'Purchase Shortcode', 'pdd' ),
		'content'	=>
			'<p>' . __( '<strong>Purchase Shortcode</strong> - If the automatic output of the purchase button has been disabled via the Download Configuration box, a shortcode can be used to output the button or link.', 'pdd' ) . '</p>' .
			'<p><code>[donate_link id="#" price="1" text="Add to Cart" color="blue"]</code></p>' .
			'<ul>
				<li><strong>id</strong> - ' . __( 'The ID of a specific download to purchase.', 'pdd' ) . '</li>
				<li><strong>price</strong> - ' . __( 'Whether to show the price on the purchase button. 1 to show the price, 0 to disable it.', 'pdd' ) . '</li>
				<li><strong>text</strong> - ' . __( 'The text to be displayed on the button or link.', 'pdd' ) . '</li>
				<li><strong>style</strong> - ' . __( '<em>button</em> | <em>text</em> - The style of the purchase link.', 'pdd' ) . '</li>
				<li><strong>color</strong> - <em>' . implode( '</em> | <em>', $colors ) . '</em></li>
				<li><strong>class</strong> - ' . __( 'One or more custom CSS classes you want applied to the button.', 'pdd' ) . '</li>
			</ul>' .
			'<p>' . sprintf( __( 'For more information, see <a href="%s">using Shortcodes</a> on the WordPress.org Codex or <a href="%s">Pojo Digital Donations Documentation</a>', 'pdd' ), 'http://codex.wordpress.org/Shortcode', 'https://easydigitaldownloads.com/docs/display-purchase-buttons-donate_link/' ) . '</p>'
	) );

	do_action( 'pdd_camps_contextual_help', $screen );
}
add_action( 'load-post.php', 'pdd_camps_contextual_help' );
add_action( 'load-post-new.php', 'pdd_camps_contextual_help' );
