<div id="pdd-payment-processing">
	<p><?php printf( __( 'Your purchase is processing. This page will reload automatically in 8 seconds. If it does not, click <a href="%s">here</a>.', 'pdd' ), pdd_get_success_page_uri() ); ?>
	<span class="pdd-cart-ajax"><i class="pdd-icon-spinner pdd-icon-spin"></i></span>
	<script type="text/javascript">setTimeout(function(){ window.location = '<?php echo pdd_get_success_page_uri(); ?>'; }, 8000);</script>
</div>