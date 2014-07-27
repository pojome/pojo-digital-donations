<?php
global $pdd_register_redirect;

pdd_print_errors(); ?>

<form id="pdd_register_form" class="pdd_form" action="" method="post">
	<?php do_action( 'pdd_register_form_fields_top' ); ?>

	<fieldset>
		<legend><?php _e( 'Register New Account', 'pdd' ); ?></legend>

		<?php do_action( 'pdd_register_form_fields_before' ); ?>

		<p>
			<label for="pdd-user-login"><?php _e( 'Username', 'pdd' ); ?></label>
			<input id="pdd-user-login" class="required pdd-input" type="text" name="pdd_user_login" title="<?php esc_attr_e( 'Username', 'pdd' ); ?>" />
		</p>

		<p>
			<label for="pdd-user-email"><?php _e( 'Email', 'pdd' ); ?></label>
			<input id="pdd-user-email" class="required pdd-input" type="email" name="pdd_user_email" title="<?php esc_attr_e( 'Email Address', 'pdd' ); ?>" />
		</p>

		<p>
			<label for="pdd-user-pass"><?php _e( 'Password', 'pdd' ); ?></label>
			<input id="pdd-user-pass" class="password required pdd-input" type="password" name="pdd_user_pass" />
		</p>

		<p>
			<label for="pdd-user-pass2"><?php _e( 'Confirm Password', 'pdd' ); ?></label>
			<input id="pdd-user-pass2" class="password required pdd-input" type="password" name="pdd_user_pass2" />
		</p>


		<?php do_action( 'pdd_register_form_fields_before_submit' ); ?>

		<p>
			<input type="hidden" name="pdd_honeypot" value="" />
			<input type="hidden" name="pdd_action" value="user_register" />
			<input type="hidden" name="pdd_redirect" value="<?php echo esc_url( $pdd_register_redirect ); ?>"/>
			<input class="button" name="pdd_register_submit" type="submit" value="<?php esc_attr_e( 'Register', 'pdd' ); ?>" />
		</p>

		<?php do_action( 'pdd_register_form_fields_after' ); ?>
	</fieldset>

	<?php do_action( 'pdd_register_form_fields_bottom' ); ?>
</form>
