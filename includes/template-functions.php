<?php
/**
 * Template Functions
 *
 * @package     PDD
 * @subpackage  Functions/Templates
 * @copyright   Copyright (c) 2014, Pippin Williamson
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Append Purchase Link
 *
 * Automatically appends the purchase link to download content, if enabled.
 *
 * @since 1.0
 * @param int $download_id Download ID
 * @return void
 */

function pdd_append_purchase_link( $download_id ) {
	if ( ! get_post_meta( $download_id, '_pdd_hide_purchase_link', true ) ) {
		echo pdd_get_purchase_link( array( 'download_id' => $download_id ) );
	}
}
add_action( 'pdd_after_download_content', 'pdd_append_purchase_link' );


/**
 * Get Purchase Link
 *
 * Builds a Purchase link for a specified download based on arguments passed.
 * This function is used all over PDD to generate the Purchase or Add to Cart
 * buttons. If no arguments are passed, the function uses the defaults that have
 * been set by the plugin. The Purchase link is built for simple and variable
 * pricing and filters are available throughout the function to override
 * certain elements of the function.
 *
 * $download_id = null, $link_text = null, $style = null, $color = null, $class = null
 *
 * @since 1.0
 * @param array $args Arguments for display
 * @return string $purchase_form
 */
function pdd_get_purchase_link( $args = array() ) {
	global $pdd_options, $post;

	if ( ! isset( $pdd_options['purchase_page'] ) || $pdd_options['purchase_page'] == 0 ) {
		pdd_set_error( 'set_checkout', sprintf( __( 'No checkout page has been configured. Visit <a href="%s">Settings</a> to set one.', 'pdd' ), admin_url( 'edit.php?post_type=download&page=pdd-settings' ) ) );
		pdd_print_errors();
		return false;
	}

	$post_id = is_object( $post ) ? $post->ID : 0;

	$defaults = apply_filters( 'pdd_purchase_link_defaults', array(
		'download_id' => $post_id,
		'price'       => (bool) true,
		'direct'      => pdd_get_download_button_behavior( $post_id ) == 'direct' ? true : false,
		'text'        => ! empty( $pdd_options[ 'add_to_cart_text' ] ) ? $pdd_options[ 'add_to_cart_text' ] : __( 'Purchase', 'pdd' ),
		'style'       => isset( $pdd_options[ 'button_style' ] ) 	   ? $pdd_options[ 'button_style' ]     : 'button',
		'color'       => isset( $pdd_options[ 'checkout_color' ] ) 	   ? $pdd_options[ 'checkout_color' ] 	: 'blue',
		'class'       => 'pdd-submit'
	) );

	$args = wp_parse_args( $args, $defaults );

	if( 'publish' != get_post_field( 'post_status', $args['download_id'] ) && ! current_user_can( 'edit_product', $args['download_id'] ) ) {
		return false; // Product not published or user doesn't have permission to view drafts
	}

	// Override color if color == inherit
	$args['color'] = ( $args['color'] == 'inherit' ) ? '' : $args['color'];

	$variable_pricing = pdd_has_variable_prices( $args['download_id'] );
	$data_variable    = $variable_pricing ? ' data-variable-price=yes' : 'data-variable-price=no';
	$type             = pdd_single_price_option_mode( $args['download_id'] ) ? 'data-price-mode=multi' : 'data-price-mode=single';

	/*if ( $args['price'] && $args['price'] !== 'no' && ! $variable_pricing ) {
		$price = pdd_get_download_price( $args['download_id'] );

		$button_text = ! empty( $args['text'] ) ? '&nbsp;&ndash;&nbsp;' . $args['text'] : '';

		if ( 0 == $price ) {
			$args['text'] = __( 'Free', 'pdd' ) . $button_text;
		} else {
			$args['text'] = pdd_currency_filter( pdd_format_amount( $price ) ) . $button_text;
		}
	}*/

	if ( pdd_item_in_cart( $args['download_id'] ) && ! $variable_pricing ) {
		$button_display   = 'style="display:none;"';
		$checkout_display = '';
	} else {
		$button_display   = '';
		$checkout_display = 'style="display:none;"';
	}

	global $pdd_displayed_form_ids;
	// Collect any form IDs we've displayed already so we can avoid duplicate IDs
	if ( isset( $pdd_displayed_form_ids[$args['download_id']] ) ) {
		$pdd_displayed_form_ids[$args['download_id']]++;
	} else {
		$pdd_displayed_form_ids[$args['download_id']] = 1;
	}

	$form_id = ! empty( $args['form_id'] ) ? $args['form_id'] : 'pdd_purchase_' . $args['download_id'];

	// If we've already generated a form ID for this download ID, apped -#
	if ( $pdd_displayed_form_ids[$args['download_id']] > 1 ) {
		$form_id .= '-' . $pdd_displayed_form_ids[$args['download_id']];
	}

	$args = apply_filters( 'pdd_purchase_link_args', $args );

	ob_start();
?>
	<form id="<?php echo $form_id; ?>" class="pdd_download_purchase_form" method="post">

		<?php do_action( 'pdd_purchase_link_top', $args['download_id'] ); ?>

		<?php if ( pdd_has_custom_amount( $args['download_id'] ) ) :
			$min_amount = get_post_meta( $args['download_id'], 'pdd_min_custom_amount', true );
			$have_min_amount = ! empty( $min_amount ) && '0' !== $min_amount;
			?>
			<div class="pdd-custom-amount-wrapper">
				<?php _e( 'Enter a amount you\'d like to pay:', 'pdd' ); ?>
				<?php echo pdd_currency_filter( '' ); ?>
				<input type="text" value="" name="pdd_custom_amount" class="pdd_custom_amount" size="1"<?php if ( $have_min_amount ) echo ' data-min_amount="' . $min_amount . '"'; ?> />
				<?php if ( $have_min_amount ) : ?>
					(<?php _e( 'Min', 'pdd' ); ?> <?php echo pdd_currency_filter( '' ); ?><?php echo $min_amount; ?>)
				<?php endif; ?>
			</div>
		<?php endif; ?>

		<div class="pdd_purchase_submit_wrapper">
			<?php
			 if ( ! pdd_is_ajax_disabled() ) {
				printf(
					'<a href="#" class="pdd-add-to-cart %1$s" data-action="pdd_add_to_cart" data-download-id="%3$s" %4$s %5$s %6$s><span class="pdd-add-to-cart-label">%2$s</span> <span class="pdd-loading"><i class="pdd-icon-spinner pdd-icon-spin"></i></span></a>',
					implode( ' ', array( $args['style'], $args['color'], trim( $args['class'] ) ) ),
					esc_attr( $args['text'] ),
					esc_attr( $args['download_id'] ),
					esc_attr( $data_variable ),
					esc_attr( $type ),
					$button_display
				);
			}

			printf(
				'<input type="submit" class="pdd-add-to-cart pdd-no-js %1$s" name="pdd_purchase_download" value="%2$s" data-action="pdd_add_to_cart" data-download-id="%3$s" %4$s %5$s %6$s/>',
				implode( ' ', array( $args['style'], $args['color'], trim( $args['class'] ) ) ),
				esc_attr( $args['text'] ),
				esc_attr( $args['download_id'] ),
				esc_attr( $data_variable ),
				esc_attr( $type ),
				$button_display
			);

			printf(
				'<a href="%1$s" class="%2$s %3$s" %4$s>' . __( 'Checkout', 'pdd' ) . '</a>',
				esc_url( pdd_get_checkout_uri() ),
				esc_attr( 'pdd_go_to_checkout' ),
				implode( ' ', array( $args['style'], $args['color'], trim( $args['class'] ) ) ),
				$checkout_display
			);
			?>

			<?php if ( ! pdd_is_ajax_disabled() ) : ?>
				<span class="pdd-cart-ajax-alert">
					<span class="pdd-cart-added-alert" style="display: none;">
						<?php printf(
								__( '<i class="pdd-icon-ok"></i> Added to cart', 'pdd' ),
								'<a href="' . esc_url( pdd_get_checkout_uri() ) . '" title="' . __( 'Go to Checkout', 'pdd' ) . '">',
								'</a>'
							);
						?>
					</span>
				</span>
			<?php endif; ?>
			<?php if ( pdd_display_tax_rate() && pdd_prices_include_tax() ) {
				echo '<span class="pdd_purchase_tax_rate">' . sprintf( __( 'Includes %1$s&#37; tax', 'pdd' ), pdd_get_tax_rate() * 100 ) . '</span>';
			} elseif ( pdd_display_tax_rate() && ! pdd_prices_include_tax() ) {
				echo '<span class="pdd_purchase_tax_rate">' . sprintf( __( 'Excluding %1$s&#37; tax', 'pdd' ), pdd_get_tax_rate() * 100 ) . '</span>';
			} ?>
		</div><!--end .pdd_purchase_submit_wrapper-->

		<input type="hidden" name="download_id" value="<?php echo esc_attr( $args['download_id'] ); ?>">
		<?php if( ! empty( $args['direct'] ) ) { ?>
			<input type="hidden" name="pdd_action" class="pdd_action_input" value="straight_to_gateway">
		<?php } else { ?>
			<input type="hidden" name="pdd_action" class="pdd_action_input" value="add_to_cart">
		<?php } ?>

		<?php do_action( 'pdd_purchase_link_end', $args['download_id'] ); ?>

	</form><!--end #<?php echo esc_attr( $form_id ); ?>-->
<?php
	$purchase_form = ob_get_clean();


	return apply_filters( 'pdd_purchase_download_form', $purchase_form, $args );
}

/**
 * Variable price output
 *
 * Outputs variable pricing options for each download or a specified downloads in a list.
 * The output generated can be overridden by the filters provided or by removing
 * the action and adding your own custom action.
 *
 * @since 1.2.3
 * @param int $download_id Download ID
 * @return void
 */
function pdd_purchase_variable_pricing( $download_id = 0 ) {
	global $pdd_options;

	$variable_pricing = pdd_has_variable_prices( $download_id );

	if ( ! $variable_pricing )
		return;

	$prices = apply_filters( 'pdd_purchase_variable_prices', pdd_get_variable_prices( $download_id ), $download_id );

	$type = pdd_single_price_option_mode( $download_id ) ? 'checkbox' : 'radio';
	$custom_amount_key = '99999';

	do_action( 'pdd_before_price_options', $download_id ); ?>
	<div class="pdd_price_options">
		<ul>
			<?php
			if ( $prices ) :
				foreach ( $prices as $key => $price ) :
					echo '<li id="pdd_price_option_' . $download_id . '_' . sanitize_key( $price['name'] ) . '" itemprop="offers" itemscope itemtype="http://schema.org/Offer">';
					
					printf(
						'<label for="%3$s"><input type="%2$s" %1$s name="pdd_options[price_id][]" id="%3$s" class="%4$s" value="%5$s" %7$s/> %6$s</label>',
						checked( apply_filters( 'pdd_price_option_checked', 0, $download_id, $key ), $key, false ),
						$type,
						esc_attr( 'pdd_price_option_' . $download_id . '_' . $key ),
						esc_attr( 'pdd_price_option_' . $download_id ),
						esc_attr( $key ),
						'<span class="pdd_price_option_name" itemprop="description">' . esc_html( $price['name'] ) . '</span><span class="pdd_price_option_sep">&nbsp;&ndash;&nbsp;</span><span class="pdd_price_option_price" itemprop="price">' . pdd_currency_filter( pdd_format_amount( $price[ 'amount' ] ) ) . '</span>',
						checked( isset( $_GET['price_option'] ), $key, false )
					);
					do_action( 'pdd_after_price_option', $key, $price, $download_id );
					echo '</li>';
				endforeach;
			endif;

			if ( pdd_has_custom_amount( $download_id ) ) : ?>
				<li>
					<label>
						<input type="<?php echo $type; ?>" name="pdd_options[price_id][]" value="<?php echo esc_attr( $custom_amount_key ); ?>" class="<?php echo esc_attr( 'pdd_price_option_' . $download_id ); ?> pdd_custom_amount_radio" />
						<?php _e( 'Name your price', 'pdd' ); ?>
					</label>
				</li>
			<?php endif;
			
			do_action( 'pdd_after_price_options_list', $download_id, $prices, $type );
			?>
		</ul>
	</div><!--end .pdd_price_options-->
<?php
	do_action( 'pdd_after_price_options', $download_id );
}
add_action( 'pdd_purchase_link_top', 'pdd_purchase_variable_pricing', 10 );

/**
 * Before Download Content
 *
 * Adds an action to the beginning of download post content that can be hooked to
 * by other functions.
 *
 * @since 1.0.8
 * @global $post
 *
 * @param $content The the_content field of the download object
 * @return string the content with any additional data attached
 */
function pdd_before_download_content( $content ) {
	global $post;

	if ( $post && $post->post_type == 'download' && is_singular( 'download' ) && is_main_query() && !post_password_required() ) {
		ob_start();
		do_action( 'pdd_before_download_content', $post->ID );
		$content = ob_get_clean() . $content;
	}

	return $content;
}
add_filter( 'the_content', 'pdd_before_download_content' );

/**
 * After Download Content
 *
 * Adds an action to the end of download post content that can be hooked to by
 * other functions.
 *
 * @since 1.0.8
 * @global $post
 *
 * @param $content The the_content field of the download object
 * @return string the content with any additional data attached
 */
function pdd_after_download_content( $content ) {
	global $post;

	if ( $post && $post->post_type == 'download' && is_singular( 'download' ) && is_main_query() && !post_password_required() ) {
		ob_start();
		do_action( 'pdd_after_download_content', $post->ID );
		$content .= ob_get_clean();
	}

	return $content;
}
add_filter( 'the_content', 'pdd_after_download_content' );

/**
 * Get Button Colors
 *
 * Returns an array of button colors.
 *
 * @since 1.0
 * @return array $colors Button colors
 */
function pdd_get_button_colors() {
	$colors = array(
		'white'     => array(
			'label' => __( 'White', 'pdd' ),
			'hex'   => '#ffffff'
		),
		'gray'      => array(
			'label' => __( 'Gray', 'pdd' ),
			'hex'   => '#f0f0f0'
		),
		'blue'      => array(
			'label' => __( 'Blue', 'pdd' ),
			'hex'   => '#428bca'
		),
		'red'       => array(
			'label' => __( 'Red', 'pdd' ),
			'hex'   => '#d9534f'
		),
		'green'     => array(
			'label' => __( 'Green', 'pdd' ),
			'hex'   => '#5cb85c'
		),
		'yellow'    => array(
			'label' => __( 'Yellow', 'pdd' ),
			'hex'   => '#f0ad4e'
		),
		'orange'    => array(
			'label' => __( 'Orange', 'pdd' ),
			'hex'   => '#ed9c28'
		),
		'dark-gray' => array(
			'label' => __( 'Dark Gray', 'pdd' ),
			'hex'   => '#363636'
		),
		'inherit'	=> array(
			'label' => __( 'Inherit', 'pdd' ),
			'hex'   => ''
		)
	);

	return apply_filters( 'pdd_button_colors', $colors );
}

/**
 * Get Button Styles
 *
 * Returns an array of button styles.
 *
 * @since 1.2.2
 * @return array $styles Button styles
 */
function pdd_get_button_styles() {
	$styles = array(
		'button'	=> __( 'Button', 'pdd' ),
		'plain'     => __( 'Plain Text', 'pdd' )
	);

	return apply_filters( 'pdd_button_styles', $styles );
}

/**
 * Default formatting for download excerpts
 *
 * This excerpt is primarily used in the [downloads] short code
 *
 * @since 1.0.8.4
 * @param string $excerpt Content before filtering
 * @return string $excerpt Content after filtering
 * @return string
 */
function pdd_downloads_default_excerpt( $excerpt ) {
	return do_shortcode( wpautop( $excerpt ) );
}
add_filter( 'pdd_downloads_excerpt', 'pdd_downloads_default_excerpt' );

/**
 * Default formatting for full download content
 *
 * This is primarily used in the [downloads] short code
 *
 * @since 1.0.8.4
 * @param string $content Content before filtering
 * @return string $content Content after filtering
 */
function pdd_downloads_default_content( $content ) {
	return do_shortcode( wpautop( $content ) );
}
add_filter( 'pdd_downloads_content', 'pdd_downloads_default_content' );

/**
 * Gets the download links for each item purchased
 *
 * @since 1.1.5
 * @param int $payment_id The ID of the payment to retrieve download links for
 * @return string
 */
function pdd_get_purchase_download_links( $payment_id = 0 ) {

	$downloads   = pdd_get_payment_meta_cart_details( $payment_id, true );
	$payment_key = pdd_get_payment_key( $payment_id );
	$email       = pdd_get_payment_user_email( $payment_id );
	$links       = '<ul class="pdd_download_links">';

	foreach ( $downloads as $download ) {
		$links .= '<li>';
			$links .= '<h3 class="pdd_download_link_title">' . esc_html( get_the_title( $download['id'] ) ) . '</h3>';
			$price_id = isset( $download['options'] ) && isset( $download['options']['price_id'] ) ? $download['options']['price_id'] : null;
			$files    = pdd_get_download_files( $download['id'], $price_id );
			if ( is_array( $files ) ) {
				foreach ( $files as $filekey => $file ) {
					$links .= '<div class="pdd_download_link_file">';
						$links .= '<a href="' . esc_url( pdd_get_download_file_url( $payment_key, $email, $filekey, $download['id'], $price_id ) ) . '">';
							if ( isset( $file['name'] ) )
								$links .= esc_html( $file['name'] );
							else
								$links .= esc_html( $file['file'] );
						$links .= '</a>';
					$links .= '</div>';
				}
			}
		$links .= '</li>';
	}

	$links .= '</ul>';

	return $links;
}

/**
 * Returns the path to the PDD templates directory
 *
 * @since 1.2
 * @return string
 */
function pdd_get_templates_dir() {
	return PDD_PLUGIN_DIR . 'templates';
}

/**
 * Returns the URL to the PDD templates directory
 *
 * @since 1.3.2.1
 * @return string
 */
function pdd_get_templates_url() {
	return PDD_PLUGIN_URL . 'templates';
}

/**
 * Retrieves a template part
 *
 * @since v1.2
 *
 * Taken from bbPress
 *
 * @param string $slug
 * @param string $name Optional. Default null
 * @param bool   $load
 *
 * @return string
 *
 * @uses pdd_locate_template()
 * @uses load_template()
 * @uses get_template_part()
 */
function pdd_get_template_part( $slug, $name = null, $load = true ) {
	// Execute code for this part
	do_action( 'get_template_part_' . $slug, $slug, $name );

	// Setup possible parts
	$templates = array();
	if ( isset( $name ) )
		$templates[] = $slug . '-' . $name . '.php';
	$templates[] = $slug . '.php';

	// Allow template parts to be filtered
	$templates = apply_filters( 'pdd_get_template_part', $templates, $slug, $name );

	// Return the part that is found
	return pdd_locate_template( $templates, $load, false );
}

/**
 * Retrieve the name of the highest priority template file that exists.
 *
 * Searches in the STYLESHEETPATH before TEMPLATEPATH so that themes which
 * inherit from a parent theme can just overload one file. If the template is
 * not found in either of those, it looks in the theme-compat folder last.
 *
 * Taken from bbPress
 *
 * @since 1.2
 *
 * @param string|array $template_names Template file(s) to search for, in order.
 * @param bool $load If true the template file will be loaded if it is found.
 * @param bool $require_once Whether to require_once or require. Default true.
 *   Has no effect if $load is false.
 * @return string The template filename if one is located.
 */
function pdd_locate_template( $template_names, $load = false, $require_once = true ) {
	// No file found yet
	$located = false;

	// Try to find a template file
	foreach ( (array) $template_names as $template_name ) {

		// Continue if template is empty
		if ( empty( $template_name ) )
			continue;

		// Trim off any slashes from the template name
		$template_name = ltrim( $template_name, '/' );

		// try locating this template file by looping through the template paths
		foreach( pdd_get_theme_template_paths() as $template_path ) {
			if( file_exists( $template_path . $template_name ) ) {
				$located = $template_path . $template_name;
				break;
			}
		}
	}

	if ( ( true == $load ) && ! empty( $located ) )
		load_template( $located, $require_once );

	return $located;
}

/**
 * Returns a list of paths to check for template locations
 *
 * @since 1.8.5
 * @return mixed|void
 */
function pdd_get_theme_template_paths() {

	$template_dir = pdd_get_theme_template_dir_name();

	$file_paths = array(
		1 => trailingslashit( get_stylesheet_directory() ) . $template_dir,
		10 => trailingslashit( get_template_directory() ) . $template_dir,
		100 => pdd_get_templates_dir()
	);

	$file_paths = apply_filters( 'pdd_template_paths', $file_paths );

	// sort the file paths based on priority
	ksort( $file_paths, SORT_NUMERIC );

	return array_map( 'trailingslashit', $file_paths );
}

/**
 * Returns the template directory name.
 *
 * Themes can filter this by using the pdd_templates_dir filter.
 *
 * @since 1.6.2
 * @return string
*/
function pdd_get_theme_template_dir_name() {
	return trailingslashit( apply_filters( 'pdd_templates_dir', 'pdd_templates' ) );
}

/**
 * Should we add schema.org microdata?
 *
 * @since 1.7
 * @return bool
 */
function pdd_add_schema_microdata() {
	// Don't modify anything until after wp_head() is called
	$ret = did_action( 'wp_head' );
	return apply_filters( 'pdd_add_schema_microdata', $ret );
}

/**
 * Add Microdata to download titles
 *
 * @since 1.5
 * @author Sunny Ratilal
 * @param string $title Post Title
 * @param int $id Post ID
 * @return string $title New title
 */
function pdd_microdata_title( $title, $id = 0 ) {

	if( ! pdd_add_schema_microdata() ) {
		return $title;
	}

	if ( is_singular( 'download' ) && 'download' == get_post_type( intval( $id ) ) ) {
		$title = '<span itemprop="name">' . $title . '</span>';
	}

	return $title;
}
add_filter( 'the_title', 'pdd_microdata_title', 10, 2 );

/**
 * Add Microdata to download description
 *
 * @since 1.5
 * @author Sunny Ratilal
 *
 * @param $content
 * @return mixed|void New title
 */
function pdd_microdata_wrapper( $content ) {
	global $post;

	if( ! pdd_add_schema_microdata() ) {
		return $content;
	}

	if ( $post && $post->post_type == 'download' && is_singular() && is_main_query() ) {
		$content = apply_filters( 'pdd_microdata_wrapper', '<div itemscope itemtype="http://schema.org/Product" itemprop="description">' . $content . '</div>' );
	}
	return $content;
}
add_filter( 'the_content', 'pdd_microdata_wrapper', 10 );

/**
 * Add no-index and no-follow to PDD checkout and purchase confirmation pages
 *
 * @since 2.0
 *
 * @return void
 */
function pdd_checkout_meta_tags() {

	$pages   = array();
	$pages[] = pdd_get_option( 'success_page' );
	$pages[] = pdd_get_option( 'failure_page' );
	$pages[] = pdd_get_option( 'purchase_history_page' );

	if( ! pdd_is_checkout() && ! is_page( $pages ) ) {
		return;
	}

	echo '<meta name="robots" content="noindex,nofollow" />' . "\n";
}
add_action( 'wp_head', 'pdd_checkout_meta_tags' );

/**
 * Adds PDD Version to the <head> tag
 *
 * @since 1.4.2
 * @return void
*/
function pdd_version_in_header(){
	echo '<meta name="generator" content="Pojo Digital Donations v' . PDD_VERSION . '" />' . "\n";
}
add_action( 'wp_head', 'pdd_version_in_header' );
