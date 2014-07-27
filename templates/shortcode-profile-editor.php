<?php
/**
 * This template is used to display the profile editor with [pdd_profile_editor]
 */
global $current_user;

if ( is_user_logged_in() ):
	$user_id      = get_current_user_id();
	$first_name   = get_user_meta( $user_id, 'first_name', true );
	$last_name    = get_user_meta( $user_id, 'last_name', true );
	$display_name = $current_user->display_name;
	$address      = pdd_get_customer_address( $user_id );

	if ( pdd_is_cart_saved() ): ?>
		<?php $restore_url = add_query_arg( array( 'pdd_action' => 'restore_cart', 'pdd_cart_token' => pdd_get_cart_token() ), pdd_get_checkout_uri() ); ?>
		<p class="pdd_success"><strong><?php _e( 'Saved cart', 'pdd'); ?>:</strong> <?php printf( __( 'You have a saved cart, <a href="%s">click here</a> to restore it.', 'pdd' ), $restore_url ); ?></p>
	<?php endif; ?>

	<?php if ( isset( $_GET['updated'] ) && $_GET['updated'] == true && ! pdd_get_errors() ): ?>
		<p class="pdd_success"><strong><?php _e( 'Success', 'pdd'); ?>:</strong> <?php _e( 'Your profile has been edited successfully.', 'pdd' ); ?></p>
	<?php endif; ?>

	<?php pdd_print_errors(); ?>

	<form id="pdd_profile_editor_form" class="pdd_form" action="<?php echo pdd_get_current_page_url(); ?>" method="post">
		<fieldset>
			<span id="pdd_profile_name_label"><legend><?php _e( 'Change your Name', 'pdd' ); ?></legend></span>
			<p id="pdd_profile_name_wrap">
				<label for="pdd_first_name"><?php _e( 'First Name', 'pdd' ); ?></label>
				<input name="pdd_first_name" id="pdd_first_name" class="text pdd-input" type="text" value="<?php echo $first_name; ?>" />
				<br />
				<label for="pdd_last_name"><?php _e( 'Last Name', 'pdd' ); ?></label>
				<input name="pdd_last_name" id="pdd_last_name" class="text pdd-input" type="text" value="<?php echo $last_name; ?>" />
			</p>
			<p id="pdd_profile_display_name_wrap">
				<label for="pdd_display_name"><?php _e( 'Display Name', 'pdd' ); ?></label>
				<select name="pdd_display_name" id="pdd_display_name" class="select pdd-select">
					<?php if ( ! empty( $current_user->first_name ) ): ?>
					<option <?php selected( $display_name, $current_user->first_name ); ?> value="<?php echo $current_user->first_name; ?>"><?php echo $current_user->first_name; ?></option>
					<?php endif; ?>
					<option <?php selected( $display_name, $current_user->user_nicename ); ?> value="<?php echo $current_user->user_nicename; ?>"><?php echo $current_user->user_nicename; ?></option>
					<?php if ( ! empty( $current_user->last_name ) ): ?>
					<option <?php selected( $display_name, $current_user->last_name ); ?> value="<?php echo $current_user->last_name; ?>"><?php echo $current_user->last_name; ?></option>
					<?php endif; ?>
					<?php if ( ! empty( $current_user->first_name ) && ! empty( $current_user->last_name ) ): ?>
					<option <?php selected( $display_name, $current_user->first_name . ' ' . $current_user->last_name ); ?> value="<?php echo $current_user->first_name . ' ' . $current_user->last_name; ?>"><?php echo $current_user->first_name . ' ' . $current_user->last_name; ?></option>
					<option <?php selected( $display_name, $current_user->last_name . ' ' . $current_user->first_name ); ?> value="<?php echo $current_user->last_name . ' ' . $current_user->first_name; ?>"><?php echo $current_user->last_name . ' ' . $current_user->first_name; ?></option>
					<?php endif; ?>
				</select>
			</p>
			<p>
				<label for="pdd_email"><?php _e( 'Email Address', 'pdd' ); ?></label>
				<input name="pdd_email" id="pdd_email" class="text pdd-input required" type="email" value="<?php echo $current_user->user_email; ?>" />
			</p>
			<span id="pdd_profile_billing_address_label"><legend><?php _e( 'Change your Billing Address', 'pdd' ); ?></legend></span>
			<p id="pdd_profile_billing_address_wrap">
				<label for="pdd_address_line1"><?php _e( 'Line 1', 'pdd' ); ?></label>
				<input name="pdd_address_line1" id="pdd_address_line1" class="text pdd-input" type="text" value="<?php echo $address['line1']; ?>" />
				<br/>
				<label for="pdd_address_line2"><?php _e( 'Line 2', 'pdd' ); ?></label>
				<input name="pdd_address_line2" id="pdd_address_line2" class="text pdd-input" type="text" value="<?php echo $address['line2']; ?>" />
				<br/>
				<label for="pdd_address_city"><?php _e( 'City', 'pdd' ); ?></label>
				<input name="pdd_address_city" id="pdd_address_city" class="text pdd-input" type="text" value="<?php echo $address['city']; ?>" />
				<br/>
				<label for="pdd_address_zip"><?php _e( 'Zip / Postal Code', 'pdd' ); ?></label>
				<input name="pdd_address_zip" id="pdd_address_zip" class="text pdd-input" type="text" value="<?php echo $address['zip']; ?>" />
				<br/>
				<label for="pdd_address_country"><?php _e( 'Country', 'pdd' ); ?></label>
				<select name="pdd_address_country" id="pdd_address_country" class="select pdd-select">
					<?php foreach( pdd_get_country_list() as $key => $country ) : ?>
					<option value="<?php echo $key; ?>"<?php selected( $address['country'], $key ); ?>><?php echo $country; ?></option>
					<?php endforeach; ?>
				</select>
				<br/>
				<label for="pdd_address_state"><?php _e( 'State / Province', 'pdd' ); ?></label>
				<input name="pdd_address_state" id="pdd_address_state" class="text pdd-input" type="text" value="<?php echo $address['state']; ?>" />
				<br/>
			</p>
			<span id="pdd_profile_password_label"><legend><?php _e( 'Change your Password', 'pdd' ); ?></legend></span>
			<p id="pdd_profile_password_wrap">
				<label for="pdd_user_pass"><?php _e( 'New Password', 'pdd' ); ?></label>
				<input name="pdd_new_user_pass1" id="pdd_new_user_pass1" class="password pdd-input" type="password"/>
				<br />
				<label for="pdd_user_pass"><?php _e( 'Re-enter Password', 'pdd' ); ?></label>
				<input name="pdd_new_user_pass2" id="pdd_new_user_pass2" class="password pdd-input" type="password"/>
			</p>
			<p class="pdd_password_change_notice"><?php _e( 'Please note after changing your password, you must log back in.', 'pdd' ); ?></p>
			<p id="pdd_profile_submit_wrap">
				<input type="hidden" name="pdd_profile_editor_nonce" value="<?php echo wp_create_nonce( 'pdd-profile-editor-nonce' ); ?>"/>
				<input type="hidden" name="pdd_action" value="edit_user_profile" />
				<input type="hidden" name="pdd_redirect" value="<?php echo esc_url( pdd_get_current_page_url() ); ?>" />
				<input name="pdd_profile_editor_submit" id="pdd_profile_editor_submit" type="submit" class="pdd_submit" value="<?php _e( 'Save Changes', 'pdd' ); ?>"/>
			</p>
		</fieldset>
	</form><!-- #pdd_profile_editor_form -->
	<?php
else:
	echo __( 'You need to login to edit your profile.', 'pdd' );
	echo pdd_login_form();
endif;
