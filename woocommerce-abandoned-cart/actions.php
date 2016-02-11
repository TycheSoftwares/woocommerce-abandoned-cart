<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Trigger a abadoned cart Deletion
 *
 * @since 2.5.2
 * @param $abadoned_cart_id Arguments passed
 * @return void
 */

class wcap_delete_bulk_action_handler{

    function wcap_delete_bulk_action_handler_function_lite( $abadoned_cart_id ) {
        global $wpdb;
        
        $get_user_id         = "SELECT user_id FROM `" . $wpdb->prefix . "ac_abandoned_cart_history_lite` WHERE id = '$abadoned_cart_id' ";
        $results_get_user_id = $wpdb->get_results( $get_user_id );
        $user_id_of_guest    = $results_get_user_id[0]->user_id;
        
        $query_delete        = "DELETE FROM `" . $wpdb->prefix . "ac_abandoned_cart_history_lite` WHERE id = '$abadoned_cart_id' ";
        $results_delete      = $wpdb->get_results( $query_delete );
        
        
        if ( $user_id_of_guest >= '63000000' ) {
            $guest_query_delete   = "DELETE FROM `" . $wpdb->prefix . "ac_abandoned_cart_history_lite` WHERE id = '" . $user_id_of_guest . "'";
            $results_guest = $wpdb->get_results( $guest_query_delete );
            //guest user
        }
    
        wp_safe_redirect( admin_url( '/admin.php?page=woocommerce_ac_page&wcap_deleted=YES' ) );
        
    }
    function wcap_delete_template_bulk_action_handler_function_lite( $template_id ) {
        global $wpdb;
    
        $id_remove    = $template_id;
        $query_remove = "DELETE FROM `" . $wpdb->prefix . "ac_email_templates_lite` WHERE id='" . $id_remove . "' ";
        $wpdb->query( $query_remove );
         
        wp_safe_redirect( admin_url( '/admin.php?page=woocommerce_ac_page&action=emailtemplates&wcap_template_deleted=YES' ) );
    
    }
}