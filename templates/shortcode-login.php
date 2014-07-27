<?php
global $pdd_login_redirect;
if ( ! is_user_logged_in() ) :

	// Show any error messages after form submission
	pdd_print_errors(); ?>
	<form id="pdd_login_form" class="pdd_form" action="" method="post">
		<fieldset>
			<span><legend><?php _e( 'Log into Your Account', 'pdd' ); ?></legend></span>
			<?php do_action( 'pdd_login_fields_before' ); ?>
			<p>
				<label for="pdd_user_Login"><?php _e( 'Username', 'pdd' ); ?></label>
				<input name="pdd_user_login" id="pdd_user_login" class="required pdd-input" type="text" title="<?php _e( 'Username', 'pdd' ); ?>"/>
			</p>
			<p>
				<label for="pdd_user_pass"><?php _e( 'Password', 'pdd' ); ?></label>
				<input name="pdd_user_pass" id="pdd_user_pass" class="password required pdd-input" type="password"/>
			</p>
			<p>
				<input type="hidden" name="pdd_redirect" value="<?php echo esc_url( $pdd_login_redirect ); ?>"/>
				<input type="hidden" name="pdd_login_nonce" value="<?php echo wp_create_nonce( 'pdd-login-nonce' ); ?>"/>
				<input type="hidden" name="pdd_action" value="user_login"/>
				<input id="pdd_login_submit" type="submit" class="pdd_submit" value="<?php _e( 'Log In', 'pdd' ); ?>"/>
			</p>
			<p class="pdd-lost-password">
				<a href="<?php echo wp_lostpassword_url(); ?>" title="<?php _e( 'Lost Password', 'pdd' ); ?>">
					<?php _e( 'Lost Password?', 'pdd' ); ?>
				</a>
			</p>
			<?php do_action( 'pdd_login_fields_after' ); ?>
		</fieldset>
	</form>
<?php else : ?>
	<p class="pdd-logged-in"><?php _e( 'You are already logged in', 'pdd' ); ?></p>
<?php endif; ?>