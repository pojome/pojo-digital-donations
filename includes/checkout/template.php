<?php
/**
 * Checkout Template
 *
 * @package     PDD
 * @subpackage  Checkout
 * @copyright   Copyright (c) 2014, Pippin Williamson
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.0
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Get Checkout Form
 *
 * @since 1.0
 * @global $pdd_options Array of all the PDD options
 * @global $user_ID ID of current logged in user
 * @global $post Current Post Object
 * @return string
 */
function pdd_checkout_form() {
	global $pdd_options, $user_ID, $post;

	$payment_mode = pdd_get_chosen_gateway();
	$form_action  = esc_url( pdd_get_checkout_uri( 'payment-mode=' . $payment_mode ) );

	ob_start();
		echo '<div id="pdd_checkout_wrap">';
		if ( pdd_get_cart_contents() || pdd_get_cart_fees() ) :

			pdd_checkout_cart();
?>
			<div id="pdd_checkout_form_wrap" class="pdd_clearfix">
				<?php do_action( 'pdd_before_purchase_form' ); ?>
				<form id="pdd_purchase_form" class="pdd_form" action="<?php echo $form_action; ?>" method="POST">
					<?php
					do_action( 'pdd_checkout_form_top' );

					if ( pdd_show_gateways() ) {
						do_action( 'pdd_payment_mode_select'  );
					} else {
						do_action( 'pdd_purchase_form' );
					}

					do_action( 'pdd_checkout_form_bottom' )
					?>
				</form>
				<?php do_action( 'pdd_after_purchase_form' ); ?>
			</div><!--end #pdd_checkout_form_wrap-->
		<?php
		else:
			do_action( 'pdd_cart_empty' );
		endif;
		echo '</div><!--end #pdd_checkout_wrap-->';
	return ob_get_clean();
}

/**
 * Renders the Purchase Form, hooks are provided to add to the purchase form.
 * The default Purchase Form rendered displays a list of the enabled payment
 * gateways, a user registration form (if enable) and a credit card info form
 * if credit cards are enabled
 *
 * @since 1.4
 * @global $pdd_options Array of all the PDD options
 * @return string
 */
function pdd_show_purchase_form() {
	global $pdd_options;

	$payment_mode = pdd_get_chosen_gateway();

	do_action( 'pdd_purchase_form_top' );

	if ( pdd_can_checkout() ) {

		do_action( 'pdd_purchase_form_before_register_login' );

		$show_register_form = pdd_get_option( 'show_register_form', 'none' ) ;
		if( ( $show_register_form === 'registration' || ( $show_register_form === 'both' && ! isset( $_GET['login'] ) ) ) && ! is_user_logged_in() ) : ?>
			<div id="pdd_checkout_login_register">
				<?php do_action( 'pdd_purchase_form_register_fields' ); ?>
			</div>
		<?php elseif( ( $show_register_form === 'login' || ( $show_register_form === 'both' && isset( $_GET['login'] ) ) ) && ! is_user_logged_in() ) : ?>
			<div id="pdd_checkout_login_register">
				<?php do_action( 'pdd_purchase_form_login_fields' ); ?>
			</div>
		<?php endif; ?>

		<?php if( ( !isset( $_GET['login'] ) && is_user_logged_in() ) || ! isset( $pdd_options['show_register_form'] ) || 'none' === $show_register_form ) {
			do_action( 'pdd_purchase_form_after_user_info' );
		}

		do_action( 'pdd_purchase_form_before_cc_form' );

		if( pdd_get_cart_total() > 0 ) {

			// Load the credit card form and allow gateways to load their own if they wish
			if ( has_action( 'pdd_' . $payment_mode . '_cc_form' ) ) {
				do_action( 'pdd_' . $payment_mode . '_cc_form' );
			} else {
				do_action( 'pdd_cc_form' );
			}

		}

		do_action( 'pdd_purchase_form_after_cc_form' );

	} else {
		// Can't checkout
		do_action( 'pdd_purchase_form_no_access' );
	}

	do_action( 'pdd_purchase_form_bottom' );
}
add_action( 'pdd_purchase_form', 'pdd_show_purchase_form' );

/**
 * Shows the User Info fields in the Personal Info box, more fields can be added
 * via the hooks provided.
 *
 * @since 1.3.3
 * @return void
 */
function pdd_user_info_fields() {
	if ( is_user_logged_in() ) :
		$user_data = get_userdata( get_current_user_id() );
	endif;
	?>
	<fieldset id="pdd_checkout_user_info">
		<span><legend><?php echo apply_filters( 'pdd_checkout_personal_info_text', __( 'Personal Info', 'pdd' ) ); ?></legend></span>
		<?php do_action( 'pdd_purchase_form_before_email' ); ?>
		<p id="pdd-email-wrap">
			<label class="pdd-label" for="pdd-email">
				<?php _e( 'Email Address', 'pdd' ); ?>
				<?php if( pdd_field_is_required( 'pdd_email' ) ) { ?>
					<span class="pdd-required-indicator">*</span>
				<?php } ?>
			</label>
			<span class="pdd-description"><?php _e( 'We will send the purchase receipt to this address.', 'pdd' ); ?></span>
			<input class="pdd-input required" type="email" name="pdd_email" placeholder="<?php _e( 'Email address', 'pdd' ); ?>" id="pdd-email" value="<?php echo is_user_logged_in() ? $user_data->user_email : ''; ?>"/>
		</p>
		<?php do_action( 'pdd_purchase_form_after_email' ); ?>
		<p id="pdd-first-name-wrap">
			<label class="pdd-label" for="pdd-first">
				<?php _e( 'First Name', 'pdd' ); ?>
				<?php if( pdd_field_is_required( 'pdd_first' ) ) { ?>
					<span class="pdd-required-indicator">*</span>
				<?php } ?>
			</label>
			<span class="pdd-description"><?php _e( 'We will use this to personalize your account experience.', 'pdd' ); ?></span>
			<input class="pdd-input required" type="text" name="pdd_first" placeholder="<?php _e( 'First name', 'pdd' ); ?>" id="pdd-first" value="<?php echo is_user_logged_in() ? $user_data->first_name : ''; ?>"/>
		</p>
		<p id="pdd-last-name-wrap">
			<label class="pdd-label" for="pdd-last">
				<?php _e( 'Last Name', 'pdd' ); ?>
				<?php if( pdd_field_is_required( 'pdd_last' ) ) { ?>
					<span class="pdd-required-indicator">*</span>
				<?php } ?>
			</label>
			<span class="pdd-description"><?php _e( 'We will use this as well to personalize your account experience.', 'pdd' ); ?></span>
			<input class="pdd-input<?php if( pdd_field_is_required( 'pdd_last' ) ) { echo ' required'; } ?>" type="text" name="pdd_last" id="pdd-last" placeholder="<?php _e( 'Last name', 'pdd' ); ?>" value="<?php echo is_user_logged_in() ? $user_data->last_name : ''; ?>"/>
		</p>
		<?php do_action( 'pdd_purchase_form_user_info' ); ?>
	</fieldset>
	<?php
}
add_action( 'pdd_purchase_form_after_user_info', 'pdd_user_info_fields' );
add_action( 'pdd_register_fields_before', 'pdd_user_info_fields' );

/**
 * Renders the credit card info form.
 *
 * @since 1.0
 * @return void
 */
function pdd_get_cc_form() {
	ob_start(); ?>

	<?php do_action( 'pdd_before_cc_fields' ); ?>

	<fieldset id="pdd_cc_fields" class="pdd-do-validate">
		<span><legend><?php _e( 'Credit Card Info', 'pdd' ); ?></legend></span>
		<?php if( is_ssl() ) : ?>
			<div id="pdd_secure_site_wrapper">
				<span class="padlock"></span>
				<span><?php _e( 'This is a secure SSL encrypted payment.', 'pdd' ); ?></span>
			</div>
		<?php endif; ?>
		<p id="pdd-card-number-wrap">
			<label for="card_number" class="pdd-label">
				<?php _e( 'Card Number', 'pdd' ); ?>
				<span class="pdd-required-indicator">*</span>
				<span class="card-type"></span>
			</label>
			<span class="pdd-description"><?php _e( 'The (typically) 16 digits on the front of your credit card.', 'pdd' ); ?></span>
			<input type="text" autocomplete="off" name="card_number" id="card_number" class="card-number pdd-input required" placeholder="<?php _e( 'Card number', 'pdd' ); ?>" />
		</p>
		<p id="pdd-card-cvc-wrap">
			<label for="card_cvc" class="pdd-label">
				<?php _e( 'CVC', 'pdd' ); ?>
				<span class="pdd-required-indicator">*</span>
			</label>
			<span class="pdd-description"><?php _e( 'The 3 digit (back) or 4 digit (front) value on your card.', 'pdd' ); ?></span>
			<input type="text" size="4" autocomplete="off" name="card_cvc" id="card_cvc" class="card-cvc pdd-input required" placeholder="<?php _e( 'Security code', 'pdd' ); ?>" />
		</p>
		<p id="pdd-card-name-wrap">
			<label for="card_name" class="pdd-label">
				<?php _e( 'Name on the Card', 'pdd' ); ?>
				<span class="pdd-required-indicator">*</span>
			</label>
			<span class="pdd-description"><?php _e( 'The name printed on the front of your credit card.', 'pdd' ); ?></span>
			<input type="text" autocomplete="off" name="card_name" id="card_name" class="card-name pdd-input required" placeholder="<?php _e( 'Card name', 'pdd' ); ?>" />
		</p>
		<?php do_action( 'pdd_before_cc_expiration' ); ?>
		<p class="card-expiration">
			<label for="card_exp_month" class="pdd-label">
				<?php _e( 'Expiration (MM/YY)', 'pdd' ); ?>
				<span class="pdd-required-indicator">*</span>
			</label>
			<span class="pdd-description"><?php _e( 'The date your credit card expires, typically on the front of the card.', 'pdd' ); ?></span>
			<select id="card_exp_month" name="card_exp_month" class="card-expiry-month pdd-select pdd-select-small required">
				<?php for( $i = 1; $i <= 12; $i++ ) { echo '<option value="' . $i . '">' . sprintf ('%02d', $i ) . '</option>'; } ?>
			</select>
			<span class="exp-divider"> / </span>
			<select id="card_exp_year" name="card_exp_year" class="card-expiry-year pdd-select pdd-select-small required">
				<?php for( $i = date('Y'); $i <= date('Y') + 10; $i++ ) { echo '<option value="' . $i . '">' . substr( $i, 2 ) . '</option>'; } ?>
			</select>
		</p>
		<?php do_action( 'pdd_after_cc_expiration' ); ?>

	</fieldset>
	<?php
	do_action( 'pdd_after_cc_fields' );

	echo ob_get_clean();
}
add_action( 'pdd_cc_form', 'pdd_get_cc_form' );

/**
 * Outputs the default credit card address fields
 *
 * @since 1.0
 * @return void
 */
function pdd_default_cc_address_fields() {

	$logged_in = is_user_logged_in();

	if( $logged_in ) {
		$user_address = get_user_meta( get_current_user_id(), '_pdd_user_address', true );
	}
	$line1 = $logged_in && ! empty( $user_address['line1'] ) ? $user_address['line1'] : '';
	$line2 = $logged_in && ! empty( $user_address['line2'] ) ? $user_address['line2'] : '';
	$city  = $logged_in && ! empty( $user_address['city']  ) ? $user_address['city']  : '';
	$zip   = $logged_in && ! empty( $user_address['zip']   ) ? $user_address['zip']   : '';
	ob_start(); ?>
	<fieldset id="pdd_cc_address" class="cc-address">
		<span><legend><?php _e( 'Billing Details', 'pdd' ); ?></legend></span>
		<?php do_action( 'pdd_cc_billing_top' ); ?>
		<p id="pdd-card-address-wrap">
			<label for="card_address" class="pdd-label">
				<?php _e( 'Billing Address', 'pdd' ); ?>
				<?php if( pdd_field_is_required( 'card_address' ) ) { ?>
					<span class="pdd-required-indicator">*</span>
				<?php } ?>
			</label>
			<span class="pdd-description"><?php _e( 'The primary billing address for your credit card.', 'pdd' ); ?></span>
			<input type="text" id="card_address" name="card_address" class="card-address pdd-input<?php if( pdd_field_is_required( 'card_address' ) ) { echo ' required'; } ?>" placeholder="<?php _e( 'Address line 1', 'pdd' ); ?>" value="<?php echo $line1; ?>"/>
		</p>
		<p id="pdd-card-address-2-wrap">
			<label for="card_address_2" class="pdd-label">
				<?php _e( 'Billing Address Line 2 (optional)', 'pdd' ); ?>
				<?php if( pdd_field_is_required( 'card_address_2' ) ) { ?>
					<span class="pdd-required-indicator">*</span>
				<?php } ?>
			</label>
			<span class="pdd-description"><?php _e( 'The suite, apt no, PO box, etc, associated with your billing address.', 'pdd' ); ?></span>
			<input type="text" id="card_address_2" name="card_address_2" class="card-address-2 pdd-input<?php if( pdd_field_is_required( 'card_address_2' ) ) { echo ' required'; } ?>" placeholder="<?php _e( 'Address line 2', 'pdd' ); ?>" value="<?php echo $line2; ?>"/>
		</p>
		<p id="pdd-card-city-wrap">
			<label for="card_city" class="pdd-label">
				<?php _e( 'Billing City', 'pdd' ); ?>
				<?php if( pdd_field_is_required( 'card_city' ) ) { ?>
					<span class="pdd-required-indicator">*</span>
				<?php } ?>
			</label>
			<span class="pdd-description"><?php _e( 'The city for your billing address.', 'pdd' ); ?></span>
			<input type="text" id="card_city" name="card_city" class="card-city pdd-input<?php if( pdd_field_is_required( 'card_city' ) ) { echo ' required'; } ?>" placeholder="<?php _e( 'City', 'pdd' ); ?>" value="<?php echo $city; ?>"/>
		</p>
		<p id="pdd-card-zip-wrap">
			<label for="card_zip" class="pdd-label">
				<?php _e( 'Billing Zip / Postal Code', 'pdd' ); ?>
				<?php if( pdd_field_is_required( 'card_zip' ) ) { ?>
					<span class="pdd-required-indicator">*</span>
				<?php } ?>
			</label>
			<span class="pdd-description"><?php _e( 'The zip or postal code for your billing address.', 'pdd' ); ?></span>
			<input type="text" size="4" name="card_zip" class="card-zip pdd-input<?php if( pdd_field_is_required( 'card_zip' ) ) { echo ' required'; } ?>" placeholder="<?php _e( 'Zip / Postal code', 'pdd' ); ?>" value="<?php echo $zip; ?>"/>
		</p>
		<p id="pdd-card-country-wrap">
			<label for="billing_country" class="pdd-label">
				<?php _e( 'Billing Country', 'pdd' ); ?>
				<?php if( pdd_field_is_required( 'billing_country' ) ) { ?>
					<span class="pdd-required-indicator">*</span>
				<?php } ?>
			</label>
			<span class="pdd-description"><?php _e( 'The country for your billing address.', 'pdd' ); ?></span>
			<select name="billing_country" id="billing_country" class="billing_country pdd-select<?php if( pdd_field_is_required( 'billing_country' ) ) { echo ' required'; } ?>">
				<?php

				$selected_country = pdd_get_shop_country();

				if( $logged_in && ! empty( $user_address['country'] ) && '*' !== $user_address['country'] ) {
					$selected_country = $user_address['country'];
				}

				$countries = pdd_get_country_list();
				foreach( $countries as $country_code => $country ) {
				  echo '<option value="' . esc_attr( $country_code ) . '"' . selected( $country_code, $selected_country, false ) . '>' . $country . '</option>';
				}
				?>
			</select>
		</p>
		<p id="pdd-card-state-wrap">
			<label for="card_state" class="pdd-label">
				<?php _e( 'Billing State / Province', 'pdd' ); ?>
				<?php if( pdd_field_is_required( 'card_state' ) ) { ?>
					<span class="pdd-required-indicator">*</span>
				<?php } ?>
			</label>
			<span class="pdd-description"><?php _e( 'The state or province for your billing address.', 'pdd' ); ?></span>
            <?php
            $selected_state = pdd_get_shop_state();
            $states         = pdd_get_shop_states( $selected_country );

            if( $logged_in && ! empty( $user_address['state'] ) ) {
				$selected_state = $user_address['state'];
			}

            if( ! empty( $states ) ) : ?>
            <select name="card_state" id="card_state" class="card_state pdd-select<?php if( pdd_field_is_required( 'card_state' ) ) { echo ' required'; } ?>">
                <?php
                    foreach( $states as $state_code => $state ) {
                        echo '<option value="' . $state_code . '"' . selected( $state_code, $selected_state, false ) . '>' . $state . '</option>';
                    }
                ?>
            </select>
        	<?php else : ?>
			<input type="text" size="6" name="card_state" id="card_state" class="card_state pdd-input" placeholder="<?php _e( 'State / Province', 'pdd' ); ?>"/>
			<?php endif; ?>
		</p>
		<?php do_action( 'pdd_cc_billing_bottom' ); ?>
	</fieldset>
	<?php
	echo ob_get_clean();
}
add_action( 'pdd_after_cc_fields', 'pdd_default_cc_address_fields' );


/**
 * Renders the billing address fields for cart taxation
 *
 * @since 1.6
 * @return void
 */
function pdd_checkout_tax_fields() {
	if ( pdd_cart_needs_billing_address_fields() && pdd_get_cart_total() )
		pdd_default_cc_address_fields();
}
add_action( 'pdd_purchase_form_after_cc_form', 'pdd_checkout_tax_fields', 999 );


/**
 * Renders the user registration fields. If the user is logged in, a login
 * form is displayed other a registration form is provided for the user to
 * create an account.
 *
 * @since 1.0
 * @return string
 */
function pdd_get_register_fields() {
	global $pdd_options;
	global $user_ID;

	if ( is_user_logged_in() )
		$user_data = get_userdata( $user_ID );

	$show_register_form = pdd_get_option( 'show_register_form', 'none' );

	ob_start(); ?>
	<fieldset id="pdd_register_fields">

		<?php if( $show_register_form == 'both' ) { ?>
			<p id="pdd-login-account-wrap"><?php _e( 'Already have an account?', 'pdd' ); ?> <a href="<?php echo add_query_arg('login', 1); ?>" class="pdd_checkout_register_login" data-action="checkout_login"><?php _e( 'Login', 'pdd' ); ?></a></p>
		<?php } ?>
		
		<?php do_action('pdd_register_fields_before'); ?>

		<fieldset id="pdd_register_account_fields">
			<span><legend><?php _e( 'Create an account', 'pdd' ); if( !pdd_no_guest_checkout() ) { echo ' ' . __( '(optional)', 'pdd' ); } ?></legend></span>
			<?php do_action('pdd_register_account_fields_before'); ?>
			<p id="pdd-user-login-wrap">
				<label for="pdd_user_login">
					<?php _e( 'Username', 'pdd' ); ?>
					<?php if( pdd_no_guest_checkout() ) { ?>
					<span class="pdd-required-indicator">*</span>
					<?php } ?>
				</label>
				<span class="pdd-description"><?php _e( 'The username you will use to log into your account.', 'pdd' ); ?></span>
				<input name="pdd_user_login" id="pdd_user_login" class="<?php if(pdd_no_guest_checkout()) { echo 'required '; } ?>pdd-input" type="text" placeholder="<?php _e( 'Username', 'pdd' ); ?>" title="<?php _e( 'Username', 'pdd' ); ?>"/>
			</p>
			<p id="pdd-user-pass-wrap">
				<label for="password">
					<?php _e( 'Password', 'pdd' ); ?>
					<?php if( pdd_no_guest_checkout() ) { ?>
					<span class="pdd-required-indicator">*</span>
					<?php } ?>
				</label>
				<span class="pdd-description"><?php _e( 'The password used to access your account.', 'pdd' ); ?></span>
				<input name="pdd_user_pass" id="pdd_user_pass" class="<?php if(pdd_no_guest_checkout()) { echo 'required '; } ?>pdd-input" placeholder="<?php _e( 'Password', 'pdd' ); ?>" type="password"/>
			</p>
			<p id="pdd-user-pass-confirm-wrap" class="pdd_register_password">
				<label for="password_again">
					<?php _e( 'Password Again', 'pdd' ); ?>
					<?php if( pdd_no_guest_checkout() ) { ?>
					<span class="pdd-required-indicator">*</span>
					<?php } ?>
				</label>
				<span class="pdd-description"><?php _e( 'Confirm your password.', 'pdd' ); ?></span>
				<input name="pdd_user_pass_confirm" id="pdd_user_pass_confirm" class="<?php if(pdd_no_guest_checkout()) { echo 'required '; } ?>pdd-input" placeholder="<?php _e( 'Confirm password', 'pdd' ); ?>" type="password"/>
			</p>
			<?php do_action( 'pdd_register_account_fields_after' ); ?>
		</fieldset>

		<?php do_action('pdd_register_fields_after'); ?>

		<input type="hidden" name="pdd-purchase-var" value="needs-to-register"/>

		<?php do_action( 'pdd_purchase_form_user_info' ); ?>

	</fieldset>
	<?php
	echo ob_get_clean();
}
add_action( 'pdd_purchase_form_register_fields', 'pdd_get_register_fields' );

/**
 * Gets the login fields for the login form on the checkout. This function hooks
 * on the pdd_purchase_form_login_fields to display the login form if a user already
 * had an account.
 *
 * @since 1.0
 * @return string
 */
function pdd_get_login_fields() {
	global $pdd_options;

	$color = isset( $pdd_options[ 'checkout_color' ] ) ? $pdd_options[ 'checkout_color' ] : 'gray';
	$color = ( $color == 'inherit' ) ? '' : $color;
	$style = isset( $pdd_options[ 'button_style' ] ) ? $pdd_options[ 'button_style' ] : 'button';

	$show_register_form = pdd_get_option( 'show_register_form', 'none' );

	ob_start(); ?>
		<fieldset id="pdd_login_fields">
			<?php if( $show_register_form == 'both' ) { ?>
				<p id="pdd-new-account-wrap">
					<?php _e( 'Need to create an account?', 'pdd' ); ?>
					<a href="<?php echo remove_query_arg('login'); ?>" class="pdd_checkout_register_login" data-action="checkout_register">
						<?php _e( 'Register', 'pdd' ); if(!pdd_no_guest_checkout()) { echo ' ' . __( 'or checkout as a guest.', 'pdd' ); } ?>
					</a>
				</p>
			<?php } ?>
			<?php do_action('pdd_checkout_login_fields_before'); ?>
			<p id="pdd-user-login-wrap">
				<label class="pdd-label" for="pdd-username"><?php _e( 'Username', 'pdd' ); ?></label>
				<input class="<?php if(pdd_no_guest_checkout()) { echo 'required '; } ?>pdd-input" type="text" name="pdd_user_login" id="pdd_user_login" value="" placeholder="<?php _e( 'Your username', 'pdd' ); ?>"/>
			</p>
			<p id="pdd-user-pass-wrap" class="pdd_login_password">
				<label class="pdd-label" for="pdd-password"><?php _e( 'Password', 'pdd' ); ?></label>
				<input class="<?php if(pdd_no_guest_checkout()) { echo 'required '; } ?>pdd-input" type="password" name="pdd_user_pass" id="pdd_user_pass" placeholder="<?php _e( 'Your password', 'pdd' ); ?>"/>
				<input type="hidden" name="pdd-purchase-var" value="needs-to-login"/>
			</p>
			<p id="pdd-user-login-submit">
				<input type="submit" class="pdd-submit button <?php echo $color; ?>" name="pdd_login_submit" value="<?php _e( 'Login', 'pdd' ); ?>"/>
			</p>
			<?php do_action('pdd_checkout_login_fields_after'); ?>
		</fieldset><!--end #pdd_login_fields-->
	<?php
	echo ob_get_clean();
}
add_action( 'pdd_purchase_form_login_fields', 'pdd_get_login_fields' );

/**
 * Renders the payment mode form by getting all the enabled payment gateways and
 * outputting them as radio buttons for the user to choose the payment gateway. If
 * a default payment gateway has been chosen from the PDD Settings, it will be
 * automatically selected.
 *
 * @since 1.2.2
 * @return void
 */
function pdd_payment_mode_select() {
	$gateways = pdd_get_enabled_payment_gateways();
	$page_URL = pdd_get_current_page_url();
	do_action('pdd_payment_mode_top'); ?>
	<?php if( pdd_is_ajax_disabled() ) { ?>
	<form id="pdd_payment_mode" action="<?php echo $page_URL; ?>" method="GET">
	<?php } ?>
		<fieldset id="pdd_payment_mode_select">
			<?php do_action( 'pdd_payment_mode_before_gateways_wrap' ); ?>
			<div id="pdd-payment-mode-wrap">
				<span class="pdd-payment-mode-label"><?php _e( 'Select Payment Method', 'pdd' ); ?></span><br/>
				<?php

				do_action( 'pdd_payment_mode_before_gateways' );

				foreach ( $gateways as $gateway_id => $gateway ) :
					$checked = checked( $gateway_id, pdd_get_default_gateway(), false );
					$checked_class = $checked ? ' pdd-gateway-option-selected' : '';
					echo '<label for="pdd-gateway-' . esc_attr( $gateway_id ) . '" class="pdd-gateway-option' . $checked_class . '" id="pdd-gateway-option-' . esc_attr( $gateway_id ) . '">';
						echo '<input type="radio" name="payment-mode" class="pdd-gateway" id="pdd-gateway-' . esc_attr( $gateway_id ) . '" value="' . esc_attr( $gateway_id ) . '"' . $checked . '>' . esc_html( $gateway['checkout_label'] );
					echo '</label>';
				endforeach;

				do_action( 'pdd_payment_mode_after_gateways' );

				?>
			</div>
			<?php do_action( 'pdd_payment_mode_after_gateways_wrap' ); ?>
		</fieldset>
		<fieldset id="pdd_payment_mode_submit" class="pdd-no-js">
			<p id="pdd-next-submit-wrap">
				<?php echo pdd_checkout_button_next(); ?>
			</p>
		</fieldset>
	<?php if( pdd_is_ajax_disabled() ) { ?>
	</form>
	<?php } ?>
	<div id="pdd_purchase_form_wrap"></div><!-- the checkout fields are loaded into this-->
	<?php do_action('pdd_payment_mode_bottom');
}
add_action( 'pdd_payment_mode_select', 'pdd_payment_mode_select' );


/**
 * Show Payment Icons by getting all the accepted icons from the PDD Settings
 * then outputting the icons.
 *
 * @since 1.0
 * @global $pdd_options Array of all the PDD Options
 * @return void
*/
function pdd_show_payment_icons() {
	global $pdd_options;

	if( pdd_show_gateways() && did_action( 'pdd_payment_mode_top' ) )
		return;

	if ( isset( $pdd_options['accepted_cards'] ) ) {
		echo '<div class="pdd-payment-icons">';
		foreach( $pdd_options['accepted_cards'] as $key => $card ) {
			if( pdd_string_is_image_url( $key ) ) {
				echo '<img class="payment-icon" src="' . esc_url( $key ) . '"/>';
			} else {
                $image = pdd_locate_template( 'images' . DIRECTORY_SEPARATOR . 'icons' . DIRECTORY_SEPARATOR . strtolower( str_replace( ' ', '', $card ) ) . '.gif', false );
                $content_dir = WP_CONTENT_DIR;

				if( function_exists( 'wp_normalize_path' ) ) {
					// Replaces backslashes with forward slashes for Windows systems
					$image = wp_normalize_path( $image );
					$content_dir = wp_normalize_path( $content_dir );
				}
				$image = str_replace( $content_dir, WP_CONTENT_URL, $image );

				if( pdd_is_ssl_enforced() || is_ssl() ) {
					$image = pdd_enforced_ssl_asset_filter( $image );
				}

				echo '<img class="payment-icon" src="' . esc_url( $image ) . '"/>';
			}
		}
		echo '</div>';
	}
}
add_action( 'pdd_payment_mode_top', 'pdd_show_payment_icons' );
add_action( 'pdd_checkout_form_top', 'pdd_show_payment_icons' );

/**
 * Renders the Checkout Agree to Terms, this displays a checkbox for users to
 * agree the T&Cs set in the PDD Settings. This is only displayed if T&Cs are
 * set in the PDD Settings.
 *
 * @since 1.3.2
 * @global $pdd_options Array of all the PDD Options
 * @return void
 */
function pdd_terms_agreement() {
	global $pdd_options;
	if ( isset( $pdd_options['show_agree_to_terms'] ) ) {
?>
		<fieldset id="pdd_terms_agreement">
			<div id="pdd_terms" style="display:none;">
				<?php
					do_action( 'pdd_before_terms' );
					echo wpautop( stripslashes( $pdd_options['agree_text'] ) );
					do_action( 'pdd_after_terms' );
				?>
			</div>
			<div id="pdd_show_terms">
				<a href="#" class="pdd_terms_links"><?php _e( 'Show Terms', 'pdd' ); ?></a>
				<a href="#" class="pdd_terms_links" style="display:none;"><?php _e( 'Hide Terms', 'pdd' ); ?></a>
			</div>
			<label for="pdd_agree_to_terms"><?php echo isset( $pdd_options['agree_label'] ) ? stripslashes( $pdd_options['agree_label'] ) : __( 'Agree to Terms?', 'pdd' ); ?></label>
			<input name="pdd_agree_to_terms" class="required" type="checkbox" id="pdd_agree_to_terms" value="1"/>
		</fieldset>
<?php
	}
}
add_action( 'pdd_purchase_form_before_submit', 'pdd_terms_agreement' );

/**
 * Shows the final purchase total at the bottom of the checkout page
 *
 * @since 1.5
 * @return void
 */
function pdd_checkout_final_total() {
?>
<p id="pdd_final_total_wrap">
	<strong><?php _e( 'Purchase Total:', 'pdd' ); ?></strong>
	<span class="pdd_cart_amount" data-subtotal="<?php echo pdd_get_cart_subtotal(); ?>" data-total="<?php echo pdd_get_cart_subtotal(); ?>"><?php pdd_cart_total(); ?></span>
</p>
<?php
}
add_action( 'pdd_purchase_form_before_submit', 'pdd_checkout_final_total', 999 );


/**
 * Renders the Checkout Submit section
 *
 * @since 1.3.3
 * @return void
 */
function pdd_checkout_submit() {
?>
	<fieldset id="pdd_purchase_submit">
		<?php do_action( 'pdd_purchase_form_before_submit' ); ?>

		<?php pdd_checkout_hidden_fields(); ?>

		<?php echo pdd_checkout_button_purchase(); ?>

		<?php do_action( 'pdd_purchase_form_after_submit' ); ?>

		<?php if ( pdd_is_ajax_disabled() ) { ?>
			<p class="pdd-cancel"><a href="javascript:history.go(-1)"><?php _e( 'Go back', 'pdd' ); ?></a></p>
		<?php } ?>
	</fieldset>
<?php
}
add_action( 'pdd_purchase_form_after_cc_form', 'pdd_checkout_submit', 9999 );

/**
 * Renders the Next button on the Checkout
 *
 * @since 1.2
 * @global $pdd_options Array of all the PDD Options
 * @return string
 */
function pdd_checkout_button_next() {
	global $pdd_options;

	$color = isset( $pdd_options[ 'checkout_color' ] ) ? $pdd_options[ 'checkout_color' ] : 'blue';
	$color = ( $color == 'inherit' ) ? '' : $color;
	$style = isset( $pdd_options[ 'button_style' ] ) ? $pdd_options[ 'button_style' ] : 'button';

	ob_start();
?>
	<input type="hidden" name="pdd_action" value="gateway_select" />
	<input type="hidden" name="page_id" value="<?php echo absint( $pdd_options['purchase_page'] ); ?>"/>
	<input type="submit" name="gateway_submit" id="pdd_next_button" class="pdd-submit <?php echo $color; ?> <?php echo $style; ?>" value="<?php _e( 'Next', 'pdd' ); ?>"/>
<?php
	return apply_filters( 'pdd_checkout_button_next', ob_get_clean() );
}

/**
 * Renders the Purchase button on the Checkout
 *
 * @since 1.2
 * @global $pdd_options Array of all the PDD Options
 * @return string
 */
function pdd_checkout_button_purchase() {
	global $pdd_options;

	$color = isset( $pdd_options[ 'checkout_color' ] ) ? $pdd_options[ 'checkout_color' ] : 'blue';
	$color = ( $color == 'inherit' ) ? '' : $color;
	$style = isset( $pdd_options[ 'button_style' ] ) ? $pdd_options[ 'button_style' ] : 'button';

	if ( pdd_get_cart_total() ) {
		$complete_purchase = ! empty( $pdd_options['checkout_label'] ) ? $pdd_options['checkout_label'] : __( 'Purchase', 'pdd' );
	} else {
		$complete_purchase = ! empty( $pdd_options['checkout_label'] ) ? $pdd_options['checkout_label'] : __( 'Free Download', 'pdd' );
	}

	ob_start();
?>
	<input type="submit" class="pdd-submit <?php echo $color; ?> <?php echo $style; ?>" id="pdd-purchase-button" name="pdd-purchase" value="<?php echo $complete_purchase; ?>"/>
<?php
	return apply_filters( 'pdd_checkout_button_purchase', ob_get_clean() );
}

/**
 * Outputs the JavaScript code for the Agree to Terms section to toggle
 * the T&Cs text
 *
 * @since 1.0
 * @global $pdd_options Array of all the PDD Options
 * @return void
 */
function pdd_agree_to_terms_js() {
	global $pdd_options;

	if ( isset( $pdd_options['show_agree_to_terms'] ) ) {
?>
	<script type="text/javascript">
		jQuery(document).ready(function($){
			$('body').on('click', '.pdd_terms_links', function(e) {
				//e.preventDefault();
				$('#pdd_terms').slideToggle();
				$('.pdd_terms_links').toggle();
				return false;
			});
		});
	</script>
<?php
	}
}
add_action( 'pdd_checkout_form_top', 'pdd_agree_to_terms_js' );

/**
 * Renders the hidden Checkout fields
 *
 * @since 1.3.2
 * @return void
 */
function pdd_checkout_hidden_fields() {
?>
	<?php if ( is_user_logged_in() ) { ?>
	<input type="hidden" name="pdd-user-id" value="<?php echo get_current_user_id(); ?>"/>
	<?php } ?>
	<input type="hidden" name="pdd_action" value="purchase"/>
	<input type="hidden" name="pdd-gateway" value="<?php echo pdd_get_chosen_gateway(); ?>" />
<?php
}

/**
 * Filter Success Page Content
 *
 * Applies filters to the success page content.
 *
 * @since 1.0
 * @param string $content Content before filters
 * @return string $content Filtered content
 */
function pdd_filter_success_page_content( $content ) {
	global $pdd_options;

	if ( isset( $pdd_options['success_page'] ) && isset( $_GET['payment-confirmation'] ) && is_page( $pdd_options['success_page'] ) ) {
		if ( has_filter( 'pdd_payment_confirm_' . $_GET['payment-confirmation'] ) ) {
			$content = apply_filters( 'pdd_payment_confirm_' . $_GET['payment-confirmation'], $content );
		}
	}

	return $content;
}
add_filter( 'the_content', 'pdd_filter_success_page_content' );

/**
 * Show a download's files in the purchase receipt
 *
 * @since 1.8.6
 * @return boolean
*/
function pdd_receipt_show_download_files( $item_id, $receipt_args ) {
	return apply_filters( 'pdd_receipt_show_download_files', true, $item_id, $receipt_args );
}