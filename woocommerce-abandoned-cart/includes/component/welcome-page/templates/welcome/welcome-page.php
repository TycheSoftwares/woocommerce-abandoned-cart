<?php
/**
 * Welcome page on activate or updation of the plugin
 */
?>
<style>
    .feature-section .feature-section-item {
        float:left;
        width:48%;
    }
</style>

<div class="wrap about-wrap">
    <?php echo $get_welcome_header; ?>
    <div style="float:left;width: 80%;">
        <p class="about-text" style="margin-right:20px;"><?php
            printf(
                __( "Thank you for activating or updating to the latest version of " . $plugin_name . "! If you're a first time user, welcome! You're well to accept deliveries with customer preferred delivery date." )
            );
        ?>
        </p>
    </div>
    
    <div class="wcal-badge"><img src="<?php echo $badge_url; ?>" style="width:150px;"/></div>

    <p>&nbsp;</p>

    <div class="feature-section clearfix introduction">

        <h3><?php esc_html_e( "Get Started with Abandoned Cart Lite", 'woocommerce-abandoned-cart' ); ?></h3>

        <div class="video feature-section-item" style="float:left;padding-right:10px;">
            <img src="<?php echo $ts_dir_image_path . 'abandoned-cart-lite-email-templates.png' ?>"
                    alt="<?php esc_attr_e( 'WooCommerce Abandoned Cart Lite', 'woocommerce-abandoned-cart' ); ?>" style="width:600px;">
        </div>

        <div class="content feature-section-item last-feature">
            <h3><?php esc_html_e( 'Activate Email Template', 'woocommerce-abandoned-cart' ); ?></h3>

            <p><?php esc_html_e( 'To start sending out abandoned cart notification emails, simply activate the email template from under WooCommerce -> Abandoned Carts -> Email Templates page.', 'woocommerce-abandoned-cart' ); ?></p>
            <a href="admin.php?page=woocommerce_ac_page&action=emailtemplates" target="_blank" class="button-secondary">
                <?php esc_html_e( 'Click Here to go to Email Templates page', 'woocommerce-abandoned-cart' ); ?>
                <span class="dashicons dashicons-external"></span>
            </a>
        </div>
    </div>

        <!-- /.intro-section -->

    <div class="content">

        <h3><?php esc_html_e( "Know more about Abandoned Cart Pro", 'woocommerce-abandoned-cart' ); ?></h3>

        <p><?php _e( 'The Abandoned Cart Pro plugin gives you features where you are able to recover more sales compared to the Lite plugin. Here are some notable features the Pro version provides.' ); ?></p>

        <div class="feature-section clearfix introduction">
            <div class="video feature-section-item" style="float:left;padding-right:10px;">
                <img src="https://www.tychesoftwares.com/wp-content/uploads/2017/08/atc_frontend.png"
                        alt="<?php esc_attr_e( 'WooCommerce Abandoned Cart Lite', 'woocommerce-abandoned-cart' ); ?>" style="width:500px;">
            </div>

            <div class="content feature-section-item last-feature">
                <h3><?php esc_html_e( 'Capture Visitor Emails on click of Add to Cart button', 'woocommerce-abandoned-cart' ); ?></h3>

                <p><?php esc_html_e( 'The ability to capture the email address early in the order process is very important to reduce cart abandonment by unknown users as well as to be able to recover their carts if they abandon it. This ultimately leads to increase in your store sales.', 'woocommerce-abandoned-cart' ); ?></p>
                <a href="https://www.tychesoftwares.com/capture-guest-user-email-address-before-checkout-page-with-woocommerce-abandoned-cart-pro/?utm_source=wpaboutpage&utm_medium=link&utm_campaign=AbandonedCartLitePlugin" target="_blank" class="button-secondary">
                    <?php esc_html_e( 'Learn More', 'woocommerce-abandoned-cart' ); ?>
                    <span class="dashicons dashicons-external"></span>
                </a>
            </div>
        </div>

        <div class="feature-section clearfix">
            <div class="content feature-section-item">

                <h3><?php esc_html_e( 'Set different cut-off times for visitors & logged-in users', 'woocommerce-abandoned-cart' ); ?></h3>

                    <p><?php esc_html_e( 'The provision for setting two separate cut-off times for different roles is mainly because sometimes if the store admin wants the visitor carts to be captured earlier than the registered user carts, then these different settings can play an important role.', 'woocommerce-abandoned-cart' ); ?></p>
                    <a href="https://www.tychesoftwares.com/docs/docs/abandoned-cart-pro-for-woocommerce/capturing-abandoned-carts/?utm_source=wpaboutpage&utm_medium=link&utm_campaign=AbandonedCartLitePlugin" target="_blank" class="button-secondary">
                        <?php esc_html_e( 'Learn More', 'woocommerce-abandoned-cart' ); ?>
                        <span class="dashicons dashicons-external"></span>
                    </a>
            </div>

            <div class="content feature-section-item last-feature">
                <img src="<?php echo $ts_dir_image_path . 'abandon-cart-cut-off-time.png'; ?>" alt="<?php esc_attr_e( 'WooCommerce Abandoned Cart Lite', 'woocommerce-abandoned-cart' ); ?>" style="width:450px;">
            </div>
        </div>


        <div class="feature-section clearfix introduction">
            <div class="video feature-section-item" style="float:left;padding-right:10px;">
                <img src="<?php echo $ts_dir_image_path . 'email-templates-send-time.png'; ?>" alt="<?php esc_attr_e( 'WooCommerce Abandoned Cart Lite', 'woocommerce-abandoned-cart' ); ?>" style="width:450px;">
            </div>

            <div class="content feature-section-item last-feature">
                <h3><?php esc_html_e( 'Send abandoned cart recovery email in minutes of cart being abandoned', 'woocommerce-abandoned-cart' ); ?></h3>

                <p><?php esc_html_e( 'The ability to send the abandoned cart recovery email within first few minutes of cart being abandoned is a big advantage. In the Lite plugin, the earliest an email can be sent is after 1 hour. Whereas in the Pro version, the first recovery email gets sent 15 minutes after the cart is abandoned. This increases the recovery chances manifold.', 'woocommerce-abandoned-cart' ); ?></p>
                <a href="https://www.tychesoftwares.com/docs/docs/abandoned-cart-pro-for-woocommerce/default-email-templates/?utm_source=wpaboutpage&utm_medium=link&utm_campaign=AbandonedCartLitePlugin" target="_blank" class="button-secondary">
                    <?php esc_html_e( 'Learn More', 'woocommerce-abandoned-cart' ); ?>
                    <span class="dashicons dashicons-external"></span>
                </a>
            </div>
        </div>
    </div>

    <div class="feature-section clearfix">
        <div class="content feature-section-item">
            <h3><?php esc_html_e( 'Getting to Know Tyche Softwares', 'woocommerce-ac' ); ?></h3>
            <ul class="ul-disc">
                <li><a href="https://tychesoftwares.com/?utm_source=wpaboutpage&utm_medium=link&utm_campaign=OrderDeliveryDateProPlugin" target="_blank"><?php esc_html_e( 'Visit the Tyche Softwares Website', 'woocommerce-ac' ); ?></a></li>
                <li><a href="https://tychesoftwares.com/premium-plugins/?utm_source=wpaboutpage&utm_medium=link&utm_campaign=OrderDeliveryDateProPlugin" target="_blank"><?php esc_html_e( 'View all Premium Plugins', 'woocommerce-ac' ); ?></a>
                <ul class="ul-disc">
                    <li><a href="https://www.tychesoftwares.com/store/premium-plugins/woocommerce-abandoned-cart-pro/?utm_source=wpaboutpage&utm_medium=link&utm_campaign=OrderDeliveryDateProPlugin" target="_blank">Abandoned Cart Pro Plugin for WooCommerce</a></li>
                    <li><a href="https://www.tychesoftwares.com/store/premium-plugins/woocommerce-booking-plugin/?utm_source=wpaboutpage&utm_medium=link&utm_campaign=OrderDeliveryDateProPlugin" target="_blank">Booking & Appointment Plugin for WooCommerce</a></li>
                    <li><a href="https://www.tychesoftwares.com/store/premium-plugins/order-delivery-date-for-woocommerce-pro-21/?utm_source=wpaboutpage&utm_medium=link&utm_campaign=OrderDeliveryDateProPlugin" target="_blank">Order Delivery Date for WooCommerce</a></li>
                    <li><a href="https://www.tychesoftwares.com/store/premium-plugins/product-delivery-date-pro-for-woocommerce/?utm_source=wpaboutpage&utm_medium=link&utm_campaign=OrderDeliveryDateProPlugin" target="_blank">Product Delivery Date for WooCommerce</a></li>
                    <li><a href="https://www.tychesoftwares.com/store/premium-plugins/deposits-for-woocommerce/?utm_source=wpaboutpage&utm_medium=link&utm_campaign=OrderDeliveryDateProPlugin" target="_blank">Deposits for WooCommerce</a></li>
                </ul>
                </li>
                <li><a href="https://tychesoftwares.com/about/?utm_source=wpaboutpage&utm_medium=link&utm_campaign=OrderDeliveryDateProPlugin" target="_blank"><?php esc_html_e( 'Meet the team', $plugin_context ); ?></a></li>
            </ul>

        </div>
        
        <div class="content feature-section-item">
            <h3><?php esc_html_e( 'Current Offers', $plugin_context ); ?></h3>
            <p>We do not have any offers going on right now</p>
        </div>

    </div>            
    <!-- /.feature-section -->
</div>