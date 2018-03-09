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
}