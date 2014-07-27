<?php
/**
 * Email Template
 *
 * @package     PDD
 * @subpackage  Emails
 * @copyright   Copyright (c) 2014, Pippin Williamson
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.0
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

/**
 * Gets all the email templates that have been registerd. The list is extendable
 * and more templates can be added.
 *
 * @since 1.0.8.2
 * @return array $templates All the registered email templates
 */
function pdd_get_email_templates() {
	$templates = array(
		'default' => __( 'Default Template', 'pdd' ),
		'none'    => __( 'No template, plain text only', 'pdd' )
	);

	return apply_filters( 'pdd_email_templates', $templates );
}

/**
 * Email Template Tags
 *
 * @since 1.0
 *
 * @param string $message Message with the template tags
 * @param array $payment_data Payment Data
 * @param int $payment_id Payment ID
 * @param bool $admin_notice Whether or not this is a notification email
 *
 * @return string $message Fully formatted message
 */
function pdd_email_template_tags( $message, $payment_data, $payment_id, $admin_notice = false ) {
	return pdd_do_email_tags( $message, $payment_id );
}

/**
 * Email Preview Template Tags
 *
 * @since 1.0
 * @global $pdd_options Array of all the PDD Options
 * @param string $message Email message with template tags
 * @return string $message Fully formatted message
 */
function pdd_email_preview_template_tags( $message ) {
	global $pdd_options;

	$download_list = '<ul>';
	$download_list .= '<li>' . __( 'Sample Product Title', 'pdd' ) . '<br />';
	$download_list .= '<ul>';
	$download_list .= '<li>';
	$download_list .= '<a href="#">' . __( 'Sample Download File Name', 'pdd' ) . '</a> - <small>' . __( 'Optional notes about this download.', 'pdd' ) . '</small>';
	$download_list .= '</li>';
	$download_list .= '</ul></li>';
	$download_list .= '</ul>';

	$file_urls = esc_html( trailingslashit( get_site_url() ) . 'test.zip?test=key&key=123' );

	$price = pdd_currency_filter( pdd_format_amount( 10.50 ) );

	$gateway = 'PayPal';

	$receipt_id = strtolower( md5( uniqid() ) );

	$notes = __( 'These are some sample notes added to a product.', 'pdd' );

	$tax = pdd_currency_filter( pdd_format_amount( 1.00 ) );

	$sub_total = pdd_currency_filter( pdd_format_amount( 9.50 ) );

	$payment_id = rand(1, 100);

	$user     = wp_get_current_user();
	$usermeta = get_user_meta( get_current_user_id() );

	$message = str_replace( '{download_list}', $download_list, $message );
	$message = str_replace( '{file_urls}', $file_urls, $message );
	$message = str_replace( '{name}', ( !empty( $usermeta['first_name'][0] ) ? $usermeta['first_name'][0] : 'John' ), $message );
 	$message = str_replace( '{fullname}', ( !empty( $usermeta['first_name'][0] ) ? $usermeta['first_name'][0] : 'John' ) . ( !empty( $usermeta['last_name'][0] ) ? ' ' . $usermeta['last_name'][0] : ' Doe' ), $message );
 	$message = str_replace( '{username}', $user->user_login, $message );
	$message = str_replace( '{date}', date( get_option( 'date_format' ), current_time( 'timestamp' ) ), $message );
	$message = str_replace( '{subtotal}', $sub_total, $message );
	$message = str_replace( '{tax}', $tax, $message );
	$message = str_replace( '{price}', $price, $message );
	$message = str_replace( '{receipt_id}', $receipt_id, $message );
	$message = str_replace( '{payment_method}', $gateway, $message );
	$message = str_replace( '{sitename}', get_bloginfo( 'name' ), $message );
	$message = str_replace( '{product_notes}', $notes, $message );
	$message = str_replace( '{payment_id}', $payment_id, $message );
	$message = str_replace( '{receipt_link}', sprintf( __( '%1$sView it in your browser.%2$s', 'pdd' ), '<a href="' . add_query_arg( array ( 'payment_key' => $receipt_id, 'pdd_action' => 'view_receipt' ), home_url() ) . '">', '</a>' ), $message );

	return wpautop( apply_filters( 'pdd_email_preview_template_tags', $message ) );
}

/**
 * Email Default Formatting
 *
 * @since 1.0
 * @param string $message Message without <p> tags
 * @return string $message Formatted message with <p> tags added
 */
function pdd_email_default_formatting( $message ) {
	return wpautop( stripslashes( $message ) );
}
add_filter( 'pdd_purchase_receipt', 'pdd_email_default_formatting' );

/**
 * Email Template Preview
 *
 * @access private
 * @global $pdd_options Array of all the PDD Options
 * @since 1.0.8.2
 */
function pdd_email_template_preview() {
	global $pdd_options;

	$default_email_body = __( "Dear", "pdd" ) . " {name},\n\n";
	$default_email_body .= __( "Thank you for your purchase. Please click on the link(s) below to download your files.", "pdd" ) . "\n\n";
	$default_email_body .= "{download_list}\n\n";
	$default_email_body .= "{sitename}";

	$email_body = isset( $pdd_options['purchase_receipt'] ) ? stripslashes( $pdd_options['purchase_receipt'] ) : $default_email_body;
	ob_start();
	?>
	<a href="#email-preview" id="open-email-preview" class="button-secondary" title="<?php _e( 'Purchase Receipt Preview', 'pdd' ); ?> "><?php _e( 'Preview Purchase Receipt', 'pdd' ); ?></a>
	<a href="<?php echo wp_nonce_url( add_query_arg( array( 'pdd_action' => 'send_test_email' ) ), 'pdd-test-email' ); ?>" title="<?php _e( 'This will send a demo purchase receipt to the emails listed below.', 'pdd' ); ?>" class="button-secondary"><?php _e( 'Send Test Email', 'pdd' ); ?></a>

	<div id="email-preview-wrap" style="display:none;">
		<div id="email-preview">
			<?php echo pdd_apply_email_template( $email_body, null, null ); ?>
		</div>
	</div>
	<?php
	echo ob_get_clean();
}
add_action( 'pdd_email_settings', 'pdd_email_template_preview' );

/**
 * Email Template Header
 *
 * @access private
 * @since 1.0.8.2
 * @return string Email template header
 */
function pdd_get_email_body_header() {
	ob_start();
	?>
	<html>
	<head>
		<style type="text/css">#outlook a { padding: 0; }</style>
	</head>
	<body dir="<?php echo is_rtl() ? 'rtl' : 'ltr'; ?>">
	<?php
	do_action( 'pdd_email_body_header' );
	return ob_get_clean();
}

/**
 * Email Template Body
 *
 * @since 1.0.8.2
 * @param int $payment_id Payment ID
 * @param array $payment_data Payment Data
 * @return string $email_body Body of the email
 */
function pdd_get_email_body_content( $payment_id = 0, $payment_data = array() ) {
	global $pdd_options;

	$default_email_body = __( "Dear", "pdd" ) . " {name},\n\n";
	$default_email_body .= __( "Thank you for your purchase. Please click on the link(s) below to download your files.", "pdd" ) . "\n\n";
	$default_email_body .= "{download_list}\n\n";
	$default_email_body .= "{sitename}";

	$email = isset( $pdd_options['purchase_receipt'] ) ? stripslashes( $pdd_options['purchase_receipt'] ) : $default_email_body;

	$email_body = pdd_do_email_tags( $email, $payment_id );

	return apply_filters( 'pdd_purchase_receipt', $email_body, $payment_id, $payment_data );
}

/**
 * Sale Notification Template Body
 *
 * @since 1.7
 * @author Daniel J Griffiths
 * @param int $payment_id Payment ID
 * @param array $payment_data Payment Data
 * @return string $email_body Body of the email
 */
function pdd_get_sale_notification_body_content( $payment_id = 0, $payment_data = array() ) {
	global $pdd_options;

	$user_info = maybe_unserialize( $payment_data['user_info'] );
	$email = pdd_get_payment_user_email( $payment_id );

	if( isset( $user_info['id'] ) && $user_info['id'] > 0 ) {
		$user_data = get_userdata( $user_info['id'] );
		$name = $user_data->display_name;
	} elseif( isset( $user_info['first_name'] ) && isset( $user_info['last_name'] ) ) {
		$name = $user_info['first_name'] . ' ' . $user_info['last_name'];
	} else {
		$name = $email;
	}

	$download_list = '';
	$downloads = maybe_unserialize( $payment_data['downloads'] );

	if( is_array( $downloads ) ) {
		foreach( $downloads as $download ) {
			$id = isset( $payment_data['cart_details'] ) ? $download['id'] : $download;
			$title = get_the_title( $id );
			if( isset( $download['options'] ) ) {
				if( isset( $download['options']['price_id'] ) ) {
					$title .= ' - ' . pdd_get_price_option_name( $id, $download['options']['price_id'], $payment_id );
				}
			}
			$download_list .= html_entity_decode( $title, ENT_COMPAT, 'UTF-8' ) . "\n";
		}
	}

	$gateway = pdd_get_gateway_admin_label( get_post_meta( $payment_id, '_pdd_payment_gateway', true ) );

	$default_email_body = __( 'Hello', 'pdd' ) . "\n\n" . sprintf( __( 'A %s purchase has been made', 'pdd' ), pdd_get_label_plural() ) . ".\n\n";
	$default_email_body .= sprintf( __( '%s sold:', 'pdd' ), pdd_get_label_plural() ) . "\n\n";
	$default_email_body .= $download_list . "\n\n";
	$default_email_body .= __( 'Purchased by: ', 'pdd' ) . " " . html_entity_decode( $name, ENT_COMPAT, 'UTF-8' ) . "\n";
	$default_email_body .= __( 'Amount: ', 'pdd' ) . " " . html_entity_decode( pdd_currency_filter( pdd_format_amount( pdd_get_payment_amount( $payment_id ) ) ), ENT_COMPAT, 'UTF-8' ) . "\n";
	$default_email_body .= __( 'Payment Method: ', 'pdd' ) . " " . $gateway . "\n\n";
	$default_email_body .= __( 'Thank you', 'pdd' );

	$email = isset( $pdd_options['sale_notification'] ) ? stripslashes( $pdd_options['sale_notification'] ) : $default_email_body;

	//$email_body = pdd_email_template_tags( $email, $payment_data, $payment_id, true );
	$email_body = pdd_do_email_tags( $email, $payment_id );

	return apply_filters( 'pdd_sale_notification', wpautop( $email_body ), $payment_id, $payment_data );
}

/**
 * Email Template Footer
 *
 * @since 1.0.8.2
 * @return string Email template footer
 */
function pdd_get_email_body_footer() {
	ob_start();
	do_action( 'pdd_email_body_footer' );
	?>
	</body>
	</html>
	<?php
	return ob_get_clean();
}

/**
 * Applies the Chosen Email Template
 *
 * @since 1.0.8.2
 * @param string $body The contents of the receipt email
 * @param int $payment_id The ID of the payment we are sending a receipt for
 * @param array $payment_data An array of meta information for the payment
 * @return string $email Formatted email with the template applied
 */
function pdd_apply_email_template( $body, $payment_id, $payment_data=array() ) {
	global $pdd_options;

	$template_name = isset( $pdd_options['email_template'] ) ? $pdd_options['email_template'] : 'default';
	$template_name = apply_filters( 'pdd_email_template', $template_name, $payment_id );

	if ( $template_name == 'none' ) {
		if ( is_admin() )
			$body = pdd_email_preview_template_tags( $body );

		return $body; // Return the plain email with no template
	}

	ob_start();

	do_action( 'pdd_email_template_' . $template_name );

	$template = ob_get_clean();

	if ( is_admin() )
		$body = pdd_email_preview_template_tags( $body );

	$body = apply_filters( 'pdd_purchase_receipt_' . $template_name, $body );

	$email = str_replace( '{email}', $body, $template );

	return $email;
}
add_filter( 'pdd_purchase_receipt', 'pdd_apply_email_template', 20, 3 );

/**
 * Default Email Template
 *
 * @access private
 * @since 1.0.8.2
 */
function pdd_default_email_template() {
	$text_align = is_rtl() ? 'right' : 'left';
	echo '<div style="margin: 0; background-color: #fafafa; width: auto; padding: 30px;"><center>';
		echo '<div style="border: 1px solid #ddd; width: 660px; background: #f0f0f0; padding: 8px; margin: 0;">';
			echo '<div id="pdd-email-content" style="background: #fff; border: 1px solid #ddd; padding: 15px; text-align: ' . $text_align . ' !important;">';
				echo '{email}'; // This tag is required in order for the contents of the email to be shown
			echo '</div>';
		echo '</div>';
	echo '</center></div>';
}
add_action( 'pdd_email_template_default', 'pdd_default_email_template' );

/**
 * Default Email Template Styling Extras
 *
 * @since 1.0.9.1
 * @param string $email_body Email template without styling
 * @return string $email_body Email template with styling
 */
function pdd_default_email_styling( $email_body ) {
	$first_p  = strpos( $email_body, '<p style="font-size: 14px;">' );
	if( $first_p ) {
		$email_body = substr_replace( $email_body, '<p style="font-size: 14px; margin-top:0;">', $first_p, 3 );
	}
	$email_body = str_replace( '<p>', '<p style="font-size: 14px; line-height: 150%">', $email_body );
	$email_body = str_replace( '<ul>', '<ul style="margin: 0 0 10px 0; padding: 0;">', $email_body );
	$email_body = str_replace( '<li>', '<li style="font-size: 14px; line-height: 150%; display:block; margin: 0 0 4px 0;">', $email_body );

	return $email_body;
}
add_filter( 'pdd_purchase_receipt_default', 'pdd_default_email_styling' );

/**
 * Render Receipt in the Browser
 *
 * A link is added to the Purchase Receipt to view the email in the browser and
 * this function renders the Purchase Receipt in the browser. It overrides the
 * Purchase Receipt template and provides its only styling.
 *
 * @since 1.5
 * @author Sunny Ratilal
 */
function pdd_render_receipt_in_browser() {
	if ( ! isset( $_GET['payment_key'] ) )
		wp_die( __( 'Missing purchase key.', 'pdd' ), __( 'Error', 'pdd' ) );

	$key = urlencode( $_GET['payment_key'] );

	ob_start();
?>
<!DOCTYPE html>
<html lang="en">
	<title><?php _e( 'Receipt', 'pdd' ); ?></title>
	<meta charset="utf-8" />
	<?php wp_head(); ?>
</html>
<body class="<?php echo apply_filters('pdd_receipt_page_body_class', 'pdd_receipt_page' ); ?>">
	<div id="pdd_receipt_wrapper">
		<?php do_action( 'pdd_render_receipt_in_browser_before' ); ?>
		<?php echo do_shortcode('[pdd_receipt payment_key='. $key .']'); ?>
		<?php do_action( 'pdd_render_receipt_in_browser_after' ); ?>
	</div>
<?php wp_footer(); ?>
</body>
<?php
	echo ob_get_clean();
	die();
}
add_action( 'pdd_view_receipt', 'pdd_render_receipt_in_browser' );
