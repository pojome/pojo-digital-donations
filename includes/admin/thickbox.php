<?php
/**
 * Thickbox
 *
 * @package     PDD
 * @subpackage  Admin
 * @copyright   Copyright (c) 2014, Pippin Williamson
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Adds an "Insert Download" button above the TinyMCE Editor on add/edit screens.
 *
 * @since 1.0
 * @return string "Insert Download" Button
 */
function pdd_media_button() {
	global $pagenow, $typenow, $wp_version;
	$output = '';

	/** Only run in post/page creation and edit screens */
	if ( in_array( $pagenow, array( 'post.php', 'page.php', 'post-new.php', 'post-edit.php' ) ) && $typenow != 'pdd_camp' ) {
		/* check current WP version */
		if ( version_compare( $wp_version, '3.5', '<' ) ) {
			$img = '<img src="' . PDD_PLUGIN_URL . 'assets/images/pdd-media.png" alt="' . sprintf( __( 'Insert %s', 'pdd' ), pdd_get_label_singular() ) . '"/>';
			$output = '<a href="#TB_inline?width=640&inlineId=choose-download" class="thickbox" title="' . __( 'Insert Download', 'pdd' ) . '">' . $img . '</a>';
		} else {
			$img = '<span class="wp-media-buttons-icon" id="pdd-media-button"></span>';
			$output = '<a href="#TB_inline?width=640&inlineId=choose-download" class="thickbox button pdd-thickbox" title="' . sprintf( __( 'Insert %s', 'pdd' ), strtolower ( pdd_get_label_singular() ) ) . '" style="padding-left: .4em;">' . $img . sprintf( __( 'Insert %s', 'pdd' ), strtolower( pdd_get_label_singular() ) ) . '</a>';
		}
	}
	echo $output;
}
add_action( 'media_buttons', 'pdd_media_button', 11 );

/**
 * Admin Footer For Thickbox
 *
 * Prints the footer code needed for the Insert Download
 * TinyMCE button.
 *
 * @since 1.0
 * @global $pagenow
 * @global $typenow
 * @return void
 */
function pdd_admin_footer_for_thickbox() {
	global $pagenow, $typenow;

	// Only run in post/page creation and edit screens
	if ( in_array( $pagenow, array( 'post.php', 'page.php', 'post-new.php', 'post-edit.php' ) ) && $typenow != 'pdd_camp' ) { ?>
		<script type="text/javascript">
            function insertDownload() {
                var id = jQuery('#products').val(),
                    direct = jQuery('#select-pdd-direct').val(),
                    style = jQuery('#select-pdd-style').val(),
                    color = jQuery('#select-pdd-color').is(':visible') ? jQuery('#select-pdd-color').val() : '',
                    text = jQuery('#pdd-text').val() || '<?php _e( "Purchase", "pdd" ); ?>';

                // Return early if no download is selected
                if ('' === id) {
                    alert('<?php _e( "You must choose a download", "pdd" ); ?>');
                    return;
                }

                if( '2' == direct ) {
                	direct = ' direct="true"';
                } else {
                	direct = '';
                }

                // Send the shortcode to the editor
                window.send_to_editor('[donate_link id="' + id + '" style="' + style + '" color="' + color + '" text="' + text + '"' + direct +']');
            }
            jQuery(document).ready(function ($) {
                $('#select-pdd-style').change(function () {
                    if ($(this).val() === 'button') {
                        $('#pdd-color-choice').slideDown();
                    } else {
                        $('#pdd-color-choice').slideUp();
                    }
                });
            });
		</script>

		<div id="choose-download" style="display: none;">
			<div class="wrap" style="font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;">
				<p><?php echo sprintf( __( 'Use the form below to insert the short code for purchasing a %s', 'pdd' ), pdd_get_label_singular() ); ?></p>
				<div>
					<?php echo PDD()->html->product_dropdown( array( 'chosen' => true )); ?>
				</div>
				<?php if( pdd_shop_supports_buy_now() ) : ?>
					<div>
						<select id="select-pdd-direct" style="clear: both; display: block; margin-bottom: 1em; margin-top: 1em;">
							<option value="0"><?php _e( 'Choose the button behavior', 'pdd' ); ?></option>
							<option value="1"><?php _e( 'Add to Cart', 'pdd' ); ?></option>
							<option value="2"><?php _e( 'Direct Purchase Link', 'pdd' ); ?></option>
						</select>
					</div>
				<?php endif; ?>
				<div>
					<select id="select-pdd-style" style="clear: both; display: block; margin-bottom: 1em; margin-top: 1em;">
						<option value=""><?php _e( 'Choose a style', 'pdd' ); ?></option>
						<?php
							$styles = array( 'button', 'text link' );
							foreach ( $styles as $style ) {
								echo '<option value="' . $style . '">' . $style . '</option>';
							}
						?>
					</select>
				</div>
				<?php
				$colors = pdd_get_button_colors();
				if( $colors ) { ?>
				<div id="pdd-color-choice" style="display: none;">
					<select id="select-pdd-color" style="clear: both; display: block; margin-bottom: 1em;">
						<option value=""><?php _e('Choose a button color', 'pdd'); ?></option>
						<?php
							foreach ( $colors as $key => $color ) {
								echo '<option value="' . str_replace( ' ', '_', $key ) . '">' . $color['label'] . '</option>';
							}
						?>
					</select>
				</div>
				<?php } ?>
				<div>
					<input type="text" class="regular-text" id="pdd-text" value="" placeholder="<?php _e( 'Link text . . .', 'pdd' ); ?>"/>
				</div>
				<p class="submit">
					<input type="button" id="pdd-insert-download" class="button-primary" value="<?php echo sprintf( __( 'Insert %s', 'pdd' ), pdd_get_label_singular() ); ?>" onclick="insertDownload();" />
					<a id="pdd-cancel-download-insert" class="button-secondary" onclick="tb_remove();" title="<?php _e( 'Cancel', 'pdd' ); ?>"><?php _e( 'Cancel', 'pdd' ); ?></a>
				</p>
			</div>
		</div>
	<?php
	}
}
add_action( 'admin_footer', 'pdd_admin_footer_for_thickbox' );