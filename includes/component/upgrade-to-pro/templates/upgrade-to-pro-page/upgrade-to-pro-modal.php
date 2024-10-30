<?php
$ts_new_tab = $ts_upgrade_to_pro_images_path . '/icon-new-tab.png';
if ( '' === get_option( 'wcap_migrated_from_lite', '' ) ) {
	$modal_heading      = __( 'Abandoned Cart Pro for WooCommerce is CHEAPER than you think', 'woocommerce-abandoned-cart' );
	$modal_body_heading = __( 'Upgrade from Lite to PRO with our Super Saver PRO Access Deal!', 'woocommerce-abandoned-cart' );
	$modal_body_desc    = __( 'Unlock all premium features of Abandoned Cart Pro for WooCommerce without restrictions — all while staying within budget. Your store\'s success is one click away, so don\'t miss the Super Saver PRO Access Deal that can skyrocket your sales today!', 'woocommerce-abandoned-cart' );
	$modal_footer_desc  = __( 'Upgrade from lite to PRO at special pricing. Click the button below to <i>access the latest deals.</i>', 'woocommerce-abandoned-cart' );
} else {
	$ts_add_image       = $ts_upgrade_to_pro_images_path . '/delete.png';
	$modal_heading      = __( 'Your store was thriving with PRO - Why stop now?', 'woocommerce-abandoned-cart' );
	$modal_body_heading = __( 'Upgrade to Abandoned Cart Pro for WooCommerce', 'woocommerce-abandoned-cart' );
	$modal_body_desc    = __( 'Reclaim the 20+ powerful features that were driving your growth. Upgrade to PRO again and continue unlocking your store\'s full potential. Without PRO you are missing out on -', 'woocommerce-abandoned-cart' );
	$modal_footer_desc  = __( '<b><i>Don\'t miss out on what you were achieving - take action today! Click the button below to Upgrade to PRO.</i></b>', 'woocommerce-abandoned-cart' );
}
?>

<div class="ts-upsell-overlay">
	<div class="ts-upsell-top"><h2 style="font-size: 20px;"><?php echo $modal_heading; ?></h2></div>
	<div class="ts-upsell-content">
		<div class="ts-upsell-content__features">
			<h3><?php echo $modal_body_heading; ?></h3>
			<h4><?php echo $modal_body_desc; ?></h4>
			<ul class="ts-columns-2">
				<li><img src="<?php echo $ts_add_image; ?>">Access all the premium features (Add to cart & exit intent popups, recovery incentives like timed coupons, 3rd party integrations and much more)</li>
				<li><img src="<?php echo $ts_add_image; ?>">Early access to new features</li>
				<li><img src="<?php echo $ts_add_image; ?>">Dedicated Customer Support</li>
				<li><img src="<?php echo $ts_add_image; ?>">Priority access to our other offers/services</li>
			</ul>
			<div class="ts-upsell-content__features-cliff"><p>And more!</p></div>
			<p>
			<?php echo $modal_footer_desc; ?>
			</p>
			<a href="https://www.tychesoftwares.com/products/woocommerce-abandoned-cart-pro-plugin-trial/" target="_blank" class="ts-upgrade-button">Upgrade and Unlock<img height="24" width="24" src="<?php echo $ts_new_tab; ?>" style="margin-left: 10px;color: white;"></a>
		</div>
	</div>
</div>