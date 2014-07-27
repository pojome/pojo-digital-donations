<?php if ( ! pdd_has_variable_prices( get_the_ID() ) ) : ?>
	<div itemprop="price" class="pdd_price">
		<?php pdd_price( get_the_ID() ); ?>
	</div>
<?php endif; ?>