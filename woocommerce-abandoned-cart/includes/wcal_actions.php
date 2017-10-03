<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Trigger a abandoned cart Deletion
 *
 * @since 2.5.2
 * @param $abandoned_cart_id Arguments passed
 * @return void
 */

class wcal_delete_bulk_action_handler {

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
    function wcal_delete_template_bulk_action_handler_function( $template_id ) {
        global $wpdb;
        $id_remove    = $template_id;
        $query_remove = "DELETE FROM `" . $wpdb->prefix . "ac_email_templates_lite` 
                        WHERE id='" . $id_remove . "' ";
        $wpdb->query( $query_remove );
         
        wp_safe_redirect( admin_url( '/admin.php?page=woocommerce_ac_page&action=emailtemplates&wcal_template_deleted=YES' ) );
    }
}