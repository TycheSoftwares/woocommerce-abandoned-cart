<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
/**
 * Abandoned Cart Lite for WooCommerce
 *
 * It will handle the common action for the plugin.
 *
 * @author  Tyche Softwares
 * @package Abandoned-Cart-Lite-for-WooCommerce/Admin/Admin-Action
 * @since 2.5.2
 */

class wcal_delete_bulk_action_handler {
    /**
     * Trigger when we delete the abandoned cart.
     * @param int | string  $abandoned_cart_id Abandoned cart id
     * @globals mixed $wpdb
     * @since 2.5.2
     */
    function wcal_delete_bulk_action_handler_function( $abandoned_cart_id ) {
        global $wpdb;
        $get_user_id         = "SELECT user_id FROM `" . $wpdb->prefix . "ac_abandoned_cart_history_lite` 
                                WHERE id = '$abandoned_cart_id' ";
        $results_get_user_id = $wpdb->get_results( $get_user_id );
        $user_id_of_guest    = $results_get_user_id[0]->user_id;
        
        $query_delete        = "DELETE FROM `" . $wpdb->prefix . "ac_abandoned_cart_history_lite` 
                                WHERE id = '$abandoned_cart_id' ";
        $results_delete      = $wpdb->get_results( $query_delete );
               
        if ( $user_id_of_guest >= '63000000' ) {
            $guest_query_delete   = "DELETE FROM `" . $wpdb->prefix . "ac_abandoned_cart_history_lite` 
                                    WHERE id = '" . $user_id_of_guest . "'";
            $results_guest = $wpdb->get_results( $guest_query_delete );
            //guest user
        }
        wp_safe_redirect( admin_url( '/admin.php?page=woocommerce_ac_page&wcal_deleted=YES' ) );  
    }

    /**
     * Trigger when we delete the template.
     * @param int | string  $template_id Template id
     * @globals mixed $wpdb
     * @since 2.5.2
     */
    function wcal_delete_template_bulk_action_handler_function( $template_id ) {
        global $wpdb;
        $id_remove    = $template_id;
        $query_remove = "DELETE FROM `" . $wpdb->prefix . "ac_email_templates_lite` 
                        WHERE id='" . $id_remove . "' ";
        $wpdb->query( $query_remove );
         
        wp_safe_redirect( admin_url( '/admin.php?page=woocommerce_ac_page&action=emailtemplates&wcal_template_deleted=YES' ) );
    }

        /**
         * It will delete cart automatically after X days
         * @hook admin_init
         * @globals mixed $wpdb
         * @since 5.0
         */
        public static function wcal_delete_abandoned_carts_after_x_days() {
            global $wpdb;
            $query = "SELECT * FROM `" . $wpdb->prefix . "ac_abandoned_cart_history_lite" . "` WHERE recovered_cart = '0' ";
            $carts = $wpdb->get_results ( $query );
            foreach( $carts as $cart_key => $cart_value ) {
                $cart_update_time = $cart_value->abandoned_cart_time;
                wcal_delete_bulk_action_handler::wcal_delete_ac_carts( $cart_value, $cart_update_time );
            }
        }
    /**
         * It will delete the abandoned cart data from database.
         * It will also delete the email history for that abandoned cart.
         * If the user id guest user then it will delete the record from users table.
         * @param object $value Value of cart.
         * @param timestamp $cart_update_time Cart abandoned time
         * @globals mixed $wpdb
         * @since 5.0
         */
        public static function wcal_delete_ac_carts( $value, $cart_update_time ) {
            global $wpdb;
            $delete_ac_after_days      = get_option( 'ac_lite_delete_abandoned_order_days' );
            if ( '' != $delete_ac_after_days ){
                $delete_ac_after_days_time = $delete_ac_after_days * 86400;
                $current_time              = current_time( 'timestamp' );
                $check_time                = $current_time - $cart_update_time;

                if ( $check_time > $delete_ac_after_days_time && $delete_ac_after_days_time != 0 && $delete_ac_after_days_time != "" ) {
                    $abandoned_id                = $value->id;
                    $query_delete_sent_history   = "DELETE FROM `" . $wpdb->prefix . "ac_sent_history_lite" . "` WHERE abandoned_order_id = '$abandoned_id' ";
                    $delete_sent_history         = $wpdb->get_results( $query_delete_sent_history );

                    $user_id               = $value->user_id;
                    $query                 = "DELETE FROM `" .  $wpdb->prefix . "ac_abandoned_cart_history_lite" . "` WHERE user_id = '$user_id' AND abandoned_cart_time = '$cart_update_time'";
                    $results2              = $wpdb->get_results ( $query );

                    $query_delete_cart     = "DELETE FROM `" . $wpdb->prefix."usermeta` WHERE user_id = '$user_id' AND meta_key = '_woocommerce_persistent_cart' ";
                    $results_delete        = $wpdb->get_results ( $query_delete_cart );
                    if ( $user_id >= '63000000' ) {
                        $guest_query   = "DELETE FROM `" . $wpdb->prefix . "ac_guest_abandoned_cart_history_lite" . "` WHERE id = '" . $user_id . "'";
                        $results_guest = $wpdb->get_results ( $guest_query );
                    }
                }
            }
        }
}