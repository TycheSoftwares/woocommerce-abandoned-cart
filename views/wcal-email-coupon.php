<?php
/**
 * Admin View: Abandoned Cart Coupon
 *
 * @package abandoned-cart-lite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Abandoned Cart Lite for WooCommerce
 *
 * It will handle the coupon inclusion in email templates and custom email template.
 *
 * @author  Tyche Softwares
 * @since 5.11
 */
?>
<tr>
<th>
	<label for="unique_coupon">                                                        
		<?php esc_html_e( 'Generate unique coupon codes:', 'woocommerce-abandoned-cart' ); ?>
	</label>
</th>
<td>
<?php
$is_unique_coupon = '';
if ( 'edittemplate' === $mode ) {
	$unique_coupon    = isset( $results[0]->generate_unique_coupon_code ) ? $results[0]->generate_unique_coupon_code : '';
	$is_unique_coupon = '';
	if ( '1' === $unique_coupon ) {
		$is_unique_coupon = 'checked';
	}
}
if ( 'copytemplate' === $mode ) {
	$unique_coupon    = isset( $results_copy[0]->generate_unique_coupon_code ) ? $results_copy[0]->generate_unique_coupon_code : '';
	$is_unique_coupon = '';
	if ( '1' === $unique_coupon ) {
		$is_unique_coupon = 'checked';
	}
}
	print '<input type="checkbox" name="unique_coupon" id="unique_coupon" ' . esc_attr( $is_unique_coupon ) . '>  </input>';
?>
	<img class="help_tip" width="16" height="16" data-tip='<?php esc_html_e( 'Replace this coupon with unique coupon codes for each customer', 'woocommerce' ); ?>' src="<?php echo esc_attr( plugins_url() ); ?>/woocommerce/assets/images/help.png" /></p>
</td>
</tr>

<!-- Below is the Coupon Code Options chnages -->

<?php
$show_row = 'display:none;';
if ( '' !== $is_unique_coupon ) {
	$show_row = '';
}
?>
<tr class="wcal_discount_options_rows" style="<?php echo esc_attr( $show_row ); ?>">
	<th>
		<label class="wcal_discount_options" for="wcal_discount_type">
			<?php esc_html_e( 'Discount Type:', 'woocommerce-abandoned-cart' ); ?>
		</label>
	</th>
	<td>
		<?php

		$discount_type = isset( $results[0]->discount_type ) ? $results[0]->discount_type : '';

		if ( 'copytemplate' === $mode ) {
			$discount_type = isset( $results_copy[0]->discount_type ) ? $results_copy[0]->discount_type : '';
		}

		$percent = 'percent' === $discount_type ? true : '';
		$fixed   = 'fixed' === $discount_type ? true : '';
		?>
		<select id="wcal_discount_type" name="wcal_discount_type">
			<option value="percent" <?php if( $percent) { esc_attr_e( 'selected' ); } //phpcs:ignore ?> ><?php esc_html_e( 'Percentage discount', 'woocommerce-abandoned-cart' ); ?></option>
			<option value="fixed" <?php if( $fixed) { esc_attr_e( 'selected' ); } //phpcs:ignore ?> ><?php esc_html_e( 'Fixed cart discount', 'woocommerce-abandoned-cart' ); ?></option>
		</select>                                                    
	</td>
</tr>
<tr class="wcal_discount_options_rows" style="<?php echo esc_attr( $show_row ); ?>">
<th>
	<label class="wcal_discount_options" for="wcal_coupon_amount">
		<?php esc_html_e( 'Coupon amount:', 'woocommerce-abandoned-cart' ); ?>
	</label>
</th>
<td>
<?php
$discount = 0;
if ( 'edittemplate' === $mode ) {
	$discount = isset( $results[0]->discount ) ? $results[0]->discount : '';
}

if ( 'copytemplate' === $mode ) {
	$discount = isset( $results_copy[0]->discount ) ? $results_copy[0]->discount : '';
}

	print '<input type="text" style="width:8%;" name="wcal_coupon_amount" id="wcal_coupon_amount" class="short" value="' . esc_attr( $discount ) . '">';
?>
	<img class="help_tip" width="16" height="16" data-tip='<?php esc_attr_e( 'Value of the coupon.', 'woocommerce' ); ?>' src="<?php echo esc_attr( plugins_url() ); ?>/woocommerce/assets/images/help.png" />
</td>
</tr>

<tr class="wcal_discount_options_rows" style="<?php echo esc_attr( $show_row ); ?>">
<th>
	<label class="wcal_discount_options" for="wcal_allow_free_shipping">
		<?php esc_html_e( 'Allow free shipping:', 'woocommerce-abandoned-cart' ); ?>
	</label>
</th>
<td>
<?php
$discount_shipping_check = '';
$discount_shipping       = '';
if ( 'edittemplate' === $mode ) {
	$discount_shipping = isset( $results[0]->discount_shipping ) ? $results[0]->discount_shipping : '';
}
if ( 'copytemplate' === $mode ) {
	$discount_shipping = isset( $results_copy[0]->discount_shipping ) ? $results_copy[0]->discount_shipping : '';
}
if ( 'yes' === $discount_shipping ) {
	$discount_shipping_check = 'checked';
}
print '<input type="checkbox" name="wcal_allow_free_shipping" id="wcal_allow_free_shipping" ' . esc_attr( $discount_shipping_check ) . '>  </input>';
?>
	<img class="help_tip" width="16" height="16" data-tip='<?php esc_html_e( 'Check this box if the coupon grants free shipping. A free shipping method must be enabled in your shipping zone and be set to require "a valid free shipping coupon" (see the "Free Shipping Requires" setting).', 'woocommerce-abandoned-cart' ); ?>' src="<?php echo esc_attr( plugins_url() ); ?>/woocommerce/assets/images/help.png" />

</td>
</tr>

<tr class="wcal_discount_options_rows" style="<?php echo esc_attr( $show_row ); ?>">
<th>
	<label class="wcal_discount_options" for="wcal_coupon_expiry">
		<?php esc_html_e( 'Coupon validity:', 'woocommerce-abandoned-cart' ); ?>
	</label>
</th>
<td>
	<?php
	$wcal_coupon_expiry   = '7-days';
	$expiry_days_or_hours = array(
		'hours' => 'Hour(s)',
		'days'  => 'Day(s)',
	);
	if ( 'edittemplate' === $mode ) {
		$wcal_coupon_expiry = isset( $results[0]->discount_expiry ) ? $results[0]->discount_expiry : '';
	}
	if ( 'copytemplate' === $mode ) {
		$wcal_coupon_expiry = isset( $results_copy[0]->discount_expiry ) ? $results_copy[0]->discount_expiry : '';
	}

	$wcal_coupon_expiry_explode = explode( '-', $wcal_coupon_expiry );
	$expiry_number              = isset( $wcal_coupon_expiry_explode[0] ) ? $wcal_coupon_expiry_explode[0] : 0;
	$expiry_freq                = isset( $wcal_coupon_expiry_explode[1] ) ? $wcal_coupon_expiry_explode[1] : 'hours';

	print '<input type="text" style="width:8%;" name="wcal_coupon_expiry" id="wcal_coupon_expiry" value="' . esc_attr( $expiry_number ) . '">  </input>';
	?>
	<select name="expiry_day_or_hour" id="expiry_day_or_hour">
	<?php
	foreach ( $expiry_days_or_hours as $k => $v ) {
		printf(
			"<option %s value='%s'>%s</option>\n",
			selected( $k, $expiry_freq, false ),
			esc_attr( $k ),
			esc_attr( $v )
		);
	}
	?>
	</select>

	<img class="help_tip" width="16" height="16" data-tip='<?php esc_html_e( 'The coupon code which will be sent in the reminder emails will be expired based the validity set here. E.g if the coupon code sent in the reminder email should be expired after 7 days then set 7 Day(s) for this option.', 'woocommerce-abandoned-cart' ); ?>' src="<?php echo esc_attr( plugins_url() ); ?>/woocommerce/assets/images/help.png" />
</td>
</tr>

<tr class='wcal_discount_options_rows' style='<?php echo esc_attr( $show_row ); ?>'>
<th>
	<label class='wcal_discount_options' for='individual_use'>                                                        
		<?php esc_html_e( 'Individual use only:', 'woocommerce-abandoned-cart' ); ?>
	</label>
</th>
<td>
<?php
$is_individual_use = 'checked';
if ( 'edittemplate' === $mode ) {
	$individual_use = isset( $results[0]->individual_use ) ? $results[0]->individual_use : '';
	if ( '1' !== $individual_use ) {
		$is_individual_use = '';
	}
}
if ( 'copytemplate' === $mode ) {
	$individual_use = isset( $results_copy[0]->individual_use ) ? $results_copy[0]->individual_use : '';
	if ( '1' !== $individual_use ) {
		$is_individual_use = '';
	}
}
	print '<input type="checkbox" name="individual_use" id="individual_use" ' . esc_attr( $is_individual_use ) . '>  </input>';
?>
	<img class="help_tip" width="16" height="16" data-tip='<?php esc_attr_e( 'Check this box if the coupon cannot be used in conjunction with other coupons.', 'woocommerce' ); ?>' src="<?php echo esc_attr( plugins_url() ); ?>/woocommerce/assets/images/help.png" /></p>
</td>
</tr>

<tr><th></th><td><b><?php esc_html_e( 'OR', 'woocommerce-abandoned-cart' ); ?></b></td></tr>
<tr>
	<th>
		<label for="woocommerce_ac_coupon_auto_complete">
			<?php esc_html_e( 'Enter a coupon code to add into email:', 'woocommerce-abandoned-cart' ); ?>
		</label>
	</th>
	<td>
		<!-- code started for woocommerce auto-complete coupons field emoved from class : woocommerce_options_panelfor WC 2.5 -->
		<div id="coupon_options" class="panel">
			<div class="options_group">
				<p class="form-field" style="padding-left:0px !important;">
				<?php

				$json_ids       = array();
				$coupon_ids     = array();
				$coupon_code_id = '';
				if ( 'edittemplate' === $mode ) {
					$coupon_code_id = $results[0]->coupon_code;
				}
				if ( 'copytemplate' === $mode ) {
					$coupon_code_id = $results_copy[0]->coupon_code;
				}
				if ( $coupon_code_id > 0 ) {
					if ( 'edittemplate' === $mode ) {
						$coupon_ids = explode( ',', $results[0]->coupon_code );
					}
					if ( 'copytemplate' === $mode ) {
						$coupon_ids = explode( ',', $results_copy[0]->coupon_code );
					}
					foreach ( $coupon_ids as $product_id ) {
						if ( $product_id > 0 ) {
							$product                 = get_the_title( $product_id );
							$json_ids[ $product_id ] = $product;
						}
					}
				}
				global $woocommerce;

				if ( version_compare( $woocommerce->version, '3.0.0', '>=' ) ) {
					?>
					<select id="coupon_ids" name="coupon_ids[]" class="wc-product-search" multiple="multiple" style="width: 50%;" data-placeholder="<?php esc_attr_e( 'Search for a Coupon&hellip;', 'woocommerce' ); ?>" data-action="wcal_json_find_coupons">
					<?php
					foreach ( $coupon_ids as $product_id ) {
						if ( $product_id > 0 ) {
							$product = get_the_title( $product_id );
							echo '<option value="' . esc_attr( $product_id ) . '"' . selected( true, true, false ) . '>' . wp_kses_post( $product ) . '</option>';
						}
					}
					?>
				</select>
					<?php
				} else {
					?>
					<input type="hidden" id="coupon_ids" name="coupon_ids[]" class="wc-product-search" style="width: 30%;" data-placeholder="<?php esc_attr_e( 'Search for a Coupon&hellip;', 'woocommerce' ); ?>" data-multiple="true" data-action="wcal_json_find_coupons"
						data-selected=" <?php echo esc_attr( wp_json_encode( $json_ids ) ); ?> " value="<?php echo esc_attr( implode( ',', array_keys( $json_ids ) ) ); ?>"
					/>
					<?php
				}
				?>
					<img class="help_tip" width="16" height="16" data-tip='<?php esc_html_e( 'Search & select one coupon code that customers should use to get a discount.  Generated coupon code which will be sent in email reminder will have the settings of coupon selected in this option.', 'woocommerce-abandoned-cart' ); ?>' src="<?php echo esc_attr( plugins_url() ); ?>/woocommerce/assets/images/help.png" />
				</p>
			</div>
		</div>
		<!-- code ended for woocommerce auto-complete coupons field -->
	</td>
</tr>

<!-- The Coupon Code Options chnages ends here -->
<script type="text/javascript">
jQuery( document ).ready(function (){
	/* Showing hiding discount options */
	jQuery('#unique_coupon').click(function(){
		if ( jQuery( "#unique_coupon" ).prop( "checked" ) == false ){
			jQuery('.wcal_discount_options_rows').hide();
		} else {
			jQuery('.wcal_discount_options_rows').removeAttr( "style" );
		}
	});
	jQuery( document.body ).trigger( 'wc-enhanced-select-init' );
})
</script>
