<?php
/**
 * Scripts
 *
 * @package     PDD
 * @subpackage  Functions
 * @copyright   Copyright (c) 2014, Pippin Williamson
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Load Scripts
 *
 * Enqueues the required scripts.
 *
 * @since 1.0
 * @global $pdd_options
 * @global $post
 * @return void
 */
function pdd_load_scripts() {
	global $pdd_options, $post;

	$js_dir = PDD_PLUGIN_URL . 'assets/js/';

	// Use minified libraries if SCRIPT_DEBUG is turned off
	//$suffix = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';
	$suffix = '';

	// Get position in cart of current download
	if ( isset( $post->ID ) ) {
		$position = pdd_get_item_position_in_cart( $post->ID );
	}

	if ( pdd_is_checkout() ) {
		if ( pdd_is_cc_verify_enabled() ) {
			wp_enqueue_script( 'creditCardValidator', $js_dir . 'jquery.creditCardValidator' . $suffix . '.js', array( 'jquery' ), PDD_VERSION );
		}
		wp_enqueue_script( 'pdd-checkout-global', $js_dir . 'pdd-checkout-global' . $suffix . '.js', array( 'jquery' ), PDD_VERSION );
		wp_localize_script( 'pdd-checkout-global', 'pdd_global_vars', array(
			'ajaxurl' => pdd_get_ajax_url(),
			'checkout_nonce' => wp_create_nonce( 'pdd_checkout_nonce' ),
			'currency_sign' => pdd_currency_filter( '' ),
			'currency_pos' => isset( $pdd_options['currency_position'] ) ? $pdd_options['currency_position'] : 'before',
			'no_gateway' => __( 'Please select a payment method', 'pdd' ),
			'no_discount' => __( 'Please enter a discount code', 'pdd' ), // Blank discount code message
			'enter_discount' => __( 'Enter discount', 'pdd' ),
			'discount_applied' => __( 'Discount Applied', 'pdd' ), // Discount verified message
			'no_email' => __( 'Please enter an email address before applying a discount code', 'pdd' ),
			'no_username' => __( 'Please enter a username before applying a discount code', 'pdd' ),
			'purchase_loading' => __( 'Please Wait...', 'pdd' ),
			'complete_purchasse' => __( 'Purchase', 'pdd' ),
			'taxes_enabled' => pdd_use_taxes() ? '1' : '0',
			'pdd_version' => PDD_VERSION,
		) );
	}

	// Load AJAX scripts, if enabled
	if ( ! pdd_is_ajax_disabled() ) {
		wp_enqueue_script( 'pdd-ajax', $js_dir . 'pdd-ajax' . $suffix . '.js', array( 'jquery' ), PDD_VERSION );
		wp_localize_script( 'pdd-ajax', 'pdd_scripts', array(
				'ajaxurl'                 => pdd_get_ajax_url(),
				'position_in_cart'        => isset( $position ) ? $position : -1,
				'already_in_cart_message' => __('You have already added this item to your cart', 'pdd'), // Item already in the cart message
				'empty_cart_message'      => __('Your cart is empty', 'pdd'), // Item already in the cart message
				'loading'                 => __('Loading', 'pdd') , // General loading message
				'select_option'           => __('Please select an option', 'pdd') , // Variable pricing error with multi-purchase option enabled
				'ajax_loader'             => PDD_PLUGIN_URL . 'assets/images/loading.gif', // Ajax loading image
				'is_checkout'             => pdd_is_checkout() ? '1' : '0',
				'default_gateway'         => pdd_get_default_gateway(),
				'redirect_to_checkout'    => ( pdd_straight_to_checkout() || pdd_is_checkout() ) ? '1' : '0',
				'checkout_page'           => pdd_get_checkout_uri(),
				'permalinks'              => get_option( 'permalink_structure' ) ? '1' : '0',
			)
		);
	}
}
add_action( 'wp_enqueue_scripts', 'pdd_load_scripts' );

/**
 * Register Styles
 *
 * Checks the styles option and hooks the required filter.
 *
 * @since 1.0
 * @global $pdd_options
 * @return void
 */
function pdd_register_styles() {
	global $pdd_options;

	if ( isset( $pdd_options['disable_styles'] ) ) {
		return;
	}

	// Use minified libraries if SCRIPT_DEBUG is turned off
	$suffix = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';

	$file          = 'pdd' . $suffix . '.css';
	$templates_dir = pdd_get_theme_template_dir_name();

	$child_theme_style_sheet    = trailingslashit( get_stylesheet_directory() ) . $templates_dir . $file;
	$child_theme_style_sheet_2  = trailingslashit( get_stylesheet_directory() ) . $templates_dir . 'pdd.css';
	$parent_theme_style_sheet   = trailingslashit( get_template_directory()   ) . $templates_dir . $file;
	$parent_theme_style_sheet_2 = trailingslashit( get_template_directory()   ) . $templates_dir . 'pdd.css';
	$pdd_plugin_style_sheet     = trailingslashit( pdd_get_templates_dir()    ) . $file;

	// Look in the child theme directory first, followed by the parent theme, followed by the PDD core templates directory
	// Also look for the min version first, followed by non minified version, even if SCRIPT_DEBUG is not enabled.
	// This allows users to copy just pdd.css to their theme
	if ( file_exists( $child_theme_style_sheet ) || ( ! empty( $suffix ) && ( $nonmin = file_exists( $child_theme_style_sheet_2 ) ) ) ) {
		if( ! empty( $nonmin ) ) {
			$url = trailingslashit( get_stylesheet_directory_uri() ) . $templates_dir . 'pdd.css';
		} else {
			$url = trailingslashit( get_stylesheet_directory_uri() ) . $templates_dir . $file;
		}
	} elseif ( file_exists( $parent_theme_style_sheet ) || ( ! empty( $suffix ) && ( $nonmin = file_exists( $parent_theme_style_sheet_2 ) ) ) ) {
		if( ! empty( $nonmin ) ) {
			$url = trailingslashit( get_template_directory_uri() ) . $templates_dir . 'pdd.css';
		} else {
			$url = trailingslashit( get_template_directory_uri() ) . $templates_dir . $file;
		}
	} elseif ( file_exists( $pdd_plugin_style_sheet ) || file_exists( $pdd_plugin_style_sheet ) ) {
		$url = trailingslashit( pdd_get_templates_url() ) . $file;
	}

	wp_enqueue_style( 'pdd-styles', $url, array(), PDD_VERSION );
}
add_action( 'wp_enqueue_scripts', 'pdd_register_styles' );

/**
 * Load Admin Scripts
 *
 * Enqueues the required admin scripts.
 *
 * @since 1.0
 * @global $post
 * @param string $hook Page hook
 * @return void
 */
function pdd_load_admin_scripts( $hook ) {

	if ( ! apply_filters( 'pdd_load_admin_scripts', pdd_is_admin_page(), $hook ) ) {
		return;
	}

	global $wp_version, $post;

	$js_dir  = PDD_PLUGIN_URL . 'assets/js/';
	$css_dir = PDD_PLUGIN_URL . 'assets/css/';

	// Use minified libraries if SCRIPT_DEBUG is turned off
	//$suffix  = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';
	$suffix  = '';

	// These have to be global
	wp_enqueue_style( 'jquery-chosen', $css_dir . 'chosen' . $suffix . '.css', array(), PDD_VERSION );
	wp_enqueue_script( 'jquery-chosen', $js_dir . 'chosen.jquery' . $suffix . '.js', array( 'jquery' ), PDD_VERSION );
	wp_enqueue_script( 'pdd-admin-scripts', $js_dir . 'admin-scripts' . $suffix . '.js', array( 'jquery' ), PDD_VERSION, false );
	wp_localize_script( 'pdd-admin-scripts', 'pdd_vars', array(
		'post_id'                 => isset( $post->ID ) ? $post->ID : null,
		'pdd_version'             => PDD_VERSION,
		'add_new_download'        => __( 'Add New Download', 'pdd' ), 									// Thickbox title
		'use_this_file'           => __( 'Use This File','pdd' ), 										// "use this file" button
		'quick_edit_warning'      => __( 'Sorry, not available for variable priced products.', 'pdd' ),
		'delete_payment'          => __( 'Are you sure you wish to delete this payment?', 'pdd' ),
		'delete_payment_note'     => __( 'Are you sure you wish to delete this note?', 'pdd' ),
		'delete_tax_rate'         => __( 'Are you sure you wish to delete this tax rate?', 'pdd' ),
		'resend_receipt'          => __( 'Are you sure you wish to resend the purchase receipt?', 'pdd' ),
		'copy_download_link_text' => __( 'Copy these links to your clip board and give them to your customer', 'pdd' ),
		'delete_payment_download' => sprintf( __( 'Are you sure you wish to delete this %s?', 'pdd' ), pdd_get_label_singular() ),
		'one_price_min'           => __( 'You must have at least one price', 'pdd' ),
		'one_file_min'            => __( 'You must have at least one file', 'pdd' ),
		'one_field_min'           => __( 'You must have at least one field', 'pdd' ),
		'one_option'              => sprintf( __( 'Choose a %s', 'pdd' ), pdd_get_label_singular() ),
		'one_or_more_option'      => sprintf( __( 'Choose one or more %s', 'pdd' ), pdd_get_label_plural() ),
		'currency_sign'           => pdd_currency_filter(''),
		'currency_pos'            => isset( $pdd_options['currency_position'] ) ? $pdd_options['currency_position'] : 'before',
		'new_media_ui'            => apply_filters( 'pdd_use_35_media_ui', 1 ),
		'remove_text'             => __( 'Remove', 'pdd' ),
	));

	wp_enqueue_style( 'wp-color-picker' );
	wp_enqueue_script( 'wp-color-picker' );
	wp_enqueue_style( 'colorbox', $css_dir . 'colorbox' . $suffix . '.css', array(), '1.3.20' );
	wp_enqueue_script( 'colorbox', $js_dir . 'jquery.colorbox-min.js', array( 'jquery' ), '1.3.20' );
	if( function_exists( 'wp_enqueue_media' ) && version_compare( $wp_version, '3.5', '>=' ) ) {
		//call for new media manager
		wp_enqueue_media();
	}
	wp_enqueue_script( 'jquery-flot', $js_dir . 'jquery.flot' . $suffix . '.js' );
	wp_enqueue_script( 'jquery-ui-datepicker' );
	wp_enqueue_script( 'jquery-ui-dialog' );
	$ui_style = ( 'classic' == get_user_option( 'admin_color' ) ) ? 'classic' : 'fresh';
	wp_enqueue_style( 'jquery-ui-css', $css_dir . 'jquery-ui-' . $ui_style . $suffix . '.css' );
	wp_enqueue_script( 'media-upload' );
	wp_enqueue_script( 'thickbox' );
	wp_enqueue_style( 'thickbox' );
	wp_enqueue_style( 'pdd-admin', $css_dir . 'pdd-admin' . $suffix . '.css', PDD_VERSION );
}
add_action( 'admin_enqueue_scripts', 'pdd_load_admin_scripts', 100 );

/**
 * Admin Downloads Icon
 *
 * Echoes the CSS for the downloads post type icon.
 *
 * @since 1.0
 * @global $post_type
 * @global $wp_version
 * @return void
*/
function pdd_admin_downloads_icon() {
	global $post_type, $wp_version;

    $images_url      = PDD_PLUGIN_URL . 'assets/images/';
    $menu_icon       = '\f316';
	$icon_url        = $images_url . 'pdd-icon.png';
	$icon_cpt_url    = $images_url . 'pdd-cpt.png';
	$icon_2x_url     = $images_url . 'pdd-icon-2x.png';
	$icon_cpt_2x_url = $images_url . 'pdd-cpt-2x.png';
	?>
    <style type="text/css" media="screen">
        <?php if( version_compare( $wp_version, '3.8-RC', '>=' ) || version_compare( $wp_version, '3.8', '>=' ) ) { ?>
            #adminmenu #menu-posts-download .wp-menu-image:before {
                content: '<?php echo $menu_icon; ?>';
            }
        <?php } else { ?>
            /** Fallback for outdated WP installations */
		    #adminmenu #menu-posts-download div.wp-menu-image {
			    background: url(<?php echo $icon_url; ?>) no-repeat 7px -17px;
            }
	    	#adminmenu #menu-posts-download:hover div.wp-menu-image,
		    #adminmenu #menu-posts-download.wp-has-current-submenu div.wp-menu-image {
			    background-position: 7px 6px;
            }
        <?php } ?>
		#icon-edit.icon32-posts-download {
			background: url(<?php echo $icon_cpt_url; ?>) -7px -5px no-repeat;
		}
		#pdd-media-button {
			background: url(<?php echo $icon_url; ?>) 0 -16px no-repeat;
			background-size: 12px 30px;
		}
		@media
		only screen and (-webkit-min-device-pixel-ratio: 1.5),
		only screen and (   min--moz-device-pixel-ratio: 1.5),
		only screen and (     -o-min-device-pixel-ratio: 3/2),
		only screen and (        min-device-pixel-ratio: 1.5),
		only screen and (        		 min-resolution: 1.5dppx) {
            <?php if( version_compare( $wp_version, '3.7', '<=' ) ) { ?>
	    		#adminmenu #menu-posts-download div.wp-menu-image {
		    		background-image: url(<?php echo $icon_2x_url; ?>);
			    	background-position: 7px -18px;
				    background-size: 16px 40px;
    			}
	    		#adminmenu #menu-posts-download:hover div.wp-menu-image,
		    	#adminmenu #menu-posts-download.wp-has-current-submenu div.wp-menu-image {
			    	background-position: 7px 6px;
                }
            <?php } ?>
			#icon-edit.icon32-posts-download {
				background: url(<?php echo $icon_cpt_2x_url; ?>) no-repeat -7px -5px !important;
				background-size: 55px 45px !important;
			}
			#pdd-media-button {
				background-image: url(<?php echo $icon_2x_url; ?>);
				background-position: 0 -17px;
			}
		}
	</style>
	<?php
}
add_action( 'admin_head','pdd_admin_downloads_icon' );
