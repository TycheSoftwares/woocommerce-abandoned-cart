<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Wcal_Admin_Notice {

    public static function wcal_pro_notice () {
        
        $wcal_activate_time = get_option ( 'wcal_activate_time' );
        $wcal_sixty_days    = strtotime( '+60 Days', $wcal_activate_time );
        $wcal_current_time  = current_time( 'timestamp' );

        if( !is_plugin_active( 'woocommerce-abandon-cart-pro/woocommerce-ac.php' ) && 
            ( false === $wcal_activate_time || ( $wcal_activate_time > 0 && $wcal_current_time >= $wcal_sixty_days ) ) ) {
            global $current_user ;
            $user_id = $current_user->ID;
            $wcal_current_time = current_time( 'timestamp' );
            
            if ( ! get_user_meta( get_current_user_id(), 'wcal_pro_first_notice_ignore' ) ) {
            
                $class = 'updated notice-info point-notice';
                $style = 'position:relative';
                
                $wcal_ac_pro_link = 'https://www.tychesoftwares.com/store/premium-plugins/woocommerce-abandoned-cart-pro/checkout?edd_action=add_to_cart&download_id=20&utm_source=wpnotice&utm_medium=first&utm_campaign=AbandonedCartLitePlugin';

                $message = wp_kses_post ( __( 'Thank you for using Abandoned Cart Lite for WooCommerce! You can use the Pro version for recovering more sales with some additional features. <strong><a target="_blank" href= "'.$wcal_ac_pro_link.'">Get it now!</a></strong>', 'woocommerce-ac' ) );

                $add_query_arguments = add_query_arg( 'wcal_pro_first_notice_ignore', '0' );
                $cancel_button = '<a href="'.$add_query_arguments.'" class="dashicons dashicons-dismiss dashicons-dismiss-icon" style="position: absolute; top: 8px; right: 8px; color: #222; opacity: 0.4; text-decoration: none !important;"></a>';
                printf( '<div class="%1$s" style="%2$s"><p>%3$s %4$s</p></div>', $class, $style, $message, $cancel_button );
            }

            if ( get_user_meta( get_current_user_id(), 'wcal_pro_first_notice_ignore' ) &&  ! get_user_meta( get_current_user_id(), 'wcal_pro_second_notice_ignore' ) ) {

                $wcal_first_ignore_time = get_user_meta( get_current_user_id(), 'wcal_pro_first_notice_ignore_time' );
                $wcal_fifteen_days      = strtotime( '+15 Days', $wcal_first_ignore_time[0]);
                
                if ( $wcal_current_time >= $wcal_fifteen_days ){
                    $class = 'updated notice-info point-notice';
                    $style = 'position:relative';

                    $wcal_ac_pro_link = 'https://www.tychesoftwares.com/store/premium-plugins/woocommerce-abandoned-cart-pro/checkout?edd_action=add_to_cart&download_id=20&utm_source=wpnotice&utm_medium=second&utm_campaign=AbandonedCartLitePlugin';

                    $message = wp_kses_post ( __( 'Abandoned Cart Pro plugin allows you to recover more revenue by offering discount coupons in the abandoned cart email notifications. <strong><a target="_blank" href= "'.$wcal_ac_pro_link.'">Grab it now!</a></strong>', 'woocommerce-ac' ) );

                    $add_query_arguments = add_query_arg( 'wcal_pro_second_notice_ignore', '0' );
                    $cancel_button = '<a href="'.$add_query_arguments.'" class="dashicons dashicons-dismiss dashicons-dismiss-icon" style="position: absolute; top: 8px; right: 8px; color: #222; opacity: 0.4; text-decoration: none !important;"></a>';
                    printf( '<div class="%1$s" style="%2$s"><p>%3$s %4$s</p></div>', $class, $style, $message, $cancel_button );
                }
            }
            
            if ( get_user_meta( get_current_user_id(), 'wcal_pro_first_notice_ignore' ) &&
                 get_user_meta( get_current_user_id(), 'wcal_pro_second_notice_ignore' ) &&
                 ! get_user_meta( get_current_user_id(), 'wcal_pro_third_notice_ignore' ) &&
                 ! is_plugin_active( 'order-delivery-date/order_delivery_date.php' )  && 
                 ! is_plugin_active( 'order-delivery-date-for-woocommerce/order_delivery_date.php' ) ) {

                $wcal_second_ignore_time = get_user_meta( get_current_user_id(), 'wcal_pro_second_notice_ignore_time' );
                $wcal_seven_days         = strtotime( '+7 Days', $wcal_second_ignore_time[0] );

                if ( $wcal_current_time >= $wcal_seven_days ){
                    $class = 'updated notice-info point-notice';
                    $style = 'position:relative';

                    $wcal_ordd_lite_link = admin_url( '/plugin-install.php?s=order+delivery+date+tyche+softwares&tab=search&type=term' );

                    $message = wp_kses_post ( __( 'Reduce cart abandonment rate by 57% with our FREE Order Delivery Date plugin. Also increase customer satisfaction with this simple plugin. <strong><a target="_blank" href= "'.$wcal_ordd_lite_link.'">Install Now</a></strong>.', 'woocommerce-ac' ) );
                    $add_query_arguments = add_query_arg( 'wcal_pro_third_notice_ignore', '0' );
                    $cancel_button = '<a href="'.$add_query_arguments.'" class="dashicons dashicons-dismiss dashicons-dismiss-icon" style="position: absolute; top: 8px; right: 8px; color: #222; opacity: 0.4; text-decoration: none !important;"></a>';
                    printf( '<div class="%1$s" style="%2$s"><p>%3$s %4$s</p></div>', $class, $style, $message, $cancel_button );
                }
            }

            if (  get_user_meta( get_current_user_id(), 'wcal_pro_first_notice_ignore' ) &&
                 get_user_meta( get_current_user_id(), 'wcal_pro_second_notice_ignore' ) &&
                 ! get_user_meta( get_current_user_id(), 'wcal_pro_fourth_notice_ignore' ) &&
                 ( is_plugin_active( 'order-delivery-date/order_delivery_date.php' ) ||
                 is_plugin_active( 'order-delivery-date-for-woocommerce/order_delivery_date.php' ) ) ) {

                $wcal_third_ignore_time = get_user_meta( get_current_user_id(), 'wcal_pro_second_notice_ignore_time' );
                $wcal_seven_days        = strtotime( '+15 Days', $wcal_third_ignore_time[0] );

                if ( $wcal_current_time >=  $wcal_seven_days ){
                    $class = 'updated notice-info point-notice';
                    $style = 'position:relative';

                    $wcal_ac_pro_link = 'https://www.tychesoftwares.com/store/premium-plugins/woocommerce-abandoned-cart-pro/checkout?edd_action=add_to_cart&download_id=20&utm_source=wpnotice&utm_medium=fourth&utm_campaign=AbandonedCartLitePlugin';

                    $wcal_pro_diff = 'https://www.tychesoftwares.com/differences-between-pro-and-lite-versions-of-abandoned-cart-for-woocommerce-plugin/';

                    $message = wp_kses_post ( __( 'Using Abandoned Cart Pro plugin, you can add more merge tags, one-click Cart & Checkout page button, send customised abandoned cart reminder email to specific customers & <strong><a target="_blank" href= "'.$wcal_pro_diff.'">much more</a></strong>. <br>Grab 20% discount on the purchase using ACPRO20 discount code and save $24. Coupon is limited to first 20 customers only. <strong><a target="_blank" href= "'.$wcal_ac_pro_link.'">Purchase now</a></strong>.', 'woocommerce-ac' ) );

                    $add_query_arguments = add_query_arg( 'wcal_pro_fourth_notice_ignore', '0' );
                    
                    $cancel_button = '<a href="'.$add_query_arguments.'" class="dashicons dashicons-dismiss dashicons-dismiss-icon" style="position: absolute; top: 8px; right: 8px; color: #222; opacity: 0.4; text-decoration: none !important    ;"></a>';
                    printf( '<div class="%1$s" style="%2$s"><p>%3$s %4$s</p></div>', $class, $style, $message, $cancel_button );
                }

            }else if ( get_user_meta( get_current_user_id(), 'wcal_pro_first_notice_ignore' ) &&
                 get_user_meta( get_current_user_id(), 'wcal_pro_second_notice_ignore' ) &&
                 get_user_meta( get_current_user_id(), 'wcal_pro_third_notice_ignore' ) &&
                 ! get_user_meta( get_current_user_id(), 'wcal_pro_fourth_notice_ignore' ) &&
                 ( ! is_plugin_active( 'order-delivery-date/order_delivery_date.php' ) || 
                   ! is_plugin_active( 'order-delivery-date-for-woocommerce/order_delivery_date.php' ) ) ) {

                $wcal_third_ignore_time = get_user_meta( get_current_user_id(), 'wcal_pro_third_notice_ignore_time' );
                $wcal_seven_days        = strtotime( '+7 Days', $wcal_third_ignore_time[0] );
                

                if ( $wcal_current_time >= $wcal_seven_days ) {
                    $class = 'updated notice-info point-notice';
                    $style = 'position:relative';

                    $wcal_ac_pro_link = 'https://www.tychesoftwares.com/store/premium-plugins/woocommerce-abandoned-cart-pro/checkout?edd_action=add_to_cart&download_id=20&utm_source=wpnotice&utm_medium=fourth&utm_campaign=AbandonedCartLitePlugin';

                    $wcal_pro_diff = 'https://www.tychesoftwares.com/differences-between-pro-and-lite-versions-of-abandoned-cart-for-woocommerce-plugin/';

                    $message = wp_kses_post ( __( 'Using Abandoned Cart Pro plugin, you can add more merge tags, one-click Cart & Checkout page button, send customised abandoned cart reminder email to specific customers & <strong><a target="_blank" href= "'.$wcal_pro_diff.'">much more</a></strong>. <br>Grab 20% discount on the purchase using ABPRO20 discount code and save $24. Coupon is limited to first 20 customers only. <strong><a target="_blank" href= "'.$wcal_ac_pro_link.'">Purchase now</a></strong>.', 'woocommerce-ac' ) );

                    $add_query_arguments = add_query_arg( 'wcal_pro_fourth_notice_ignore', '0' );
                    
                    $cancel_button = '<a href="'.$add_query_arguments.'" class="dashicons dashicons-dismiss dashicons-dismiss-icon" style="position: absolute; top: 8px; right: 8px; color: #222; opacity: 0.4; text-decoration: none !important    ;"></a>';
                    printf( '<div class="%1$s" style="%2$s"><p>%3$s %4$s</p></div>', $class, $style, $message, $cancel_button );
                }
            } 
        }
    }

    /**
     * Ignore pro notice
     */
    public static function wcal_pro_notice_ignore() {

        // If user clicks to ignore the notice, add that to their user meta
        if ( isset( $_GET['wcal_pro_first_notice_ignore'] ) && '0' === $_GET['wcal_pro_first_notice_ignore'] ) {
            add_user_meta( get_current_user_id(), 'wcal_pro_first_notice_ignore', 'true', true );
            add_user_meta( get_current_user_id(), 'wcal_pro_first_notice_ignore_time', current_time( 'timestamp' ), true );
            wp_safe_redirect( remove_query_arg( 'wcal_pro_first_notice_ignore' ) );

        }

        if ( isset( $_GET['wcal_pro_second_notice_ignore'] ) && '0' === $_GET['wcal_pro_second_notice_ignore'] ) {
            add_user_meta( get_current_user_id(), 'wcal_pro_second_notice_ignore', 'true', true );
            add_user_meta( get_current_user_id(), 'wcal_pro_second_notice_ignore_time', current_time( 'timestamp' ), true );
            wp_safe_redirect( remove_query_arg( 'wcal_pro_second_notice_ignore' )  );
        }

        if ( isset( $_GET['wcal_pro_third_notice_ignore'] ) && '0' === $_GET['wcal_pro_third_notice_ignore'] ) {
            add_user_meta( get_current_user_id(), 'wcal_pro_third_notice_ignore', 'true', true );
            add_user_meta( get_current_user_id(), 'wcal_pro_third_notice_ignore_time', current_time( 'timestamp' ), true );
            wp_safe_redirect( remove_query_arg( 'wcal_pro_third_notice_ignore' ) );
        }

        if ( isset( $_GET['wcal_pro_fourth_notice_ignore'] ) && '0' === $_GET['wcal_pro_fourth_notice_ignore'] ) {
            add_user_meta( get_current_user_id(), 'wcal_pro_fourth_notice_ignore', 'true', true );
            add_user_meta( get_current_user_id(), 'wcal_pro_fourth_notice_ignore_time', current_time( 'timestamp' ), true );
            wp_safe_redirect( remove_query_arg( 'wcal_pro_fourth_notice_ignore' ) );
        }
    }
}