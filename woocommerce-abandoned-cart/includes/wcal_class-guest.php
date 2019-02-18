<?php 
/**
 * Abandoned Cart Lite for WooCommerce
 *
 * It will capture the guest users data.
 *
 * @author  Tyche Softwares
 * @package Abandoned-Cart-Lite-for-WooCommerce/Frontend/Capture-Guest-Cart
 */

if ( ! class_exists( 'woocommerce_guest_ac' ) ) {

    /**
     * It will add the js, ajax for capturing the guest cart.
     * It will add an action for populating the guest data when user comes from the abandoned cart reminder emails.
     * @since 2.2
     */
	class woocommerce_guest_ac {		
		var $a;		
		public function __construct() {
			add_action( 'woocommerce_after_checkout_billing_form', 'user_side_js' );
			add_action( 'init','load_ac_ajax' );
			add_filter( 'woocommerce_checkout_fields', 'guest_checkout_fields' );
		}
	}			
	
    /**
     * It will add the ajax for capturing the guest record.
     * @hook init
     * @since 2.2
     */
	function load_ac_ajax() {
        if ( ! is_user_logged_in() ) {
			add_action( 'wp_ajax_nopriv_save_data', 'save_data' );
		} 
	}

    /**
     * It will add the js for capturing the guest cart.
     * @hook woocommerce_after_checkout_billing_form
     * @since 2.2
     */
	function user_side_js() {

        wp_enqueue_script( 
            'wcal_guest_capture',
            plugins_url( '../assets/js/wcal_guest_capture.min.js', __FILE__ ),
            '',
            '',
            true
        );

        wp_localize_script( 
            'wcal_guest_capture', 
            'wcal_guest_capture_params', 
            array(
                'ajax_url'  =>  admin_url( 'admin-ajax.php' ) 
            ) 
        );
	}
	
    /**
     * It will add the guest users data in the database.
     * @hook wp_ajax_nopriv_save_data
     * @globals mixed $wpdb
     * @globals mixed $woocommerce
     * @since 2.2
     */
	function save_data() {
        if ( ! is_user_logged_in() ) {
            global $wpdb, $woocommerce;    
            if ( isset($_POST['billing_first_name']) && $_POST['billing_first_name'] != '' ){
                wcal_common::wcal_set_cart_session( 'billing_first_name', sanitize_text_field( $_POST['billing_first_name'] ) );
            }
            if ( isset($_POST['billing_last_name']) && $_POST['billing_last_name'] != '' ) {
                wcal_common::wcal_set_cart_session( 'billing_last_name', sanitize_text_field( $_POST['billing_last_name'] ) );
            }            
            if ( isset($_POST['billing_company']) && $_POST['billing_company'] != '' ) {
                wcal_common::wcal_set_cart_session( 'billing_company', sanitize_text_field( $_POST['billing_company'] ) );
            }            
            if ( isset($_POST['billing_address_1']) && $_POST['billing_address_1'] != '' ) {
                wcal_common::wcal_set_cart_session( 'billing_address_1', sanitize_text_field( $_POST['billing_address_1'] ) );
            }    
            if ( isset($_POST['billing_address_2']) && $_POST['billing_address_2'] != '' ) {
                wcal_common::wcal_set_cart_session( 'billing_address_2', sanitize_text_field( $_POST['billing_address_2'] ) );
            }            
            if ( isset($_POST['billing_city']) && $_POST['billing_city'] != '' ) {
                wcal_common::wcal_set_cart_session( 'billing_city', sanitize_text_field( $_POST['billing_city'] ) );
            }            
            if ( isset($_POST['billing_state']) && $_POST['billing_state'] != '' ) {
                wcal_common::wcal_set_cart_session( 'billing_state', sanitize_text_field( $_POST['billing_state'] ) );
            }            
            if ( isset($_POST['billing_postcode']) && $_POST['billing_postcode'] != '' ) {
                wcal_common::wcal_set_cart_session( 'billing_postcode', sanitize_text_field( $_POST['billing_postcode'] ) );
            }            
            if ( isset($_POST['billing_country']) && $_POST['billing_country'] != '' ) {
                wcal_common::wcal_set_cart_session( 'billing_country', sanitize_text_field( $_POST['billing_country'] ) );
            }            
            if ( isset($_POST['billing_email']) && $_POST['billing_email'] != '' ) {
                wcal_common::wcal_set_cart_session( 'billing_email', sanitize_text_field( $_POST['billing_email'] ) );
            }            
            if ( isset($_POST['billing_phone']) && $_POST['billing_phone'] != '' ) {
                wcal_common::wcal_set_cart_session( 'billing_phone', sanitize_text_field( $_POST['billing_phone'] ) );
            }            
            if ( isset($_POST['order_notes']) && $_POST['order_notes'] != '' ) {
                wcal_common::wcal_set_cart_session( 'order_notes', sanitize_text_field( $_POST['order_notes'] ) );
            }           
            if( isset( $_POST['ship_to_billing'] ) && $_POST['ship_to_billing'] != '' ) {
                wcal_common::wcal_set_cart_session( 'ship_to_billing', sanitize_text_field( $_POST['ship_to_billing'] ) );
            }            
            if ( isset($_POST['shipping_first_name']) && $_POST['shipping_first_name'] != '' ) {
                wcal_common::wcal_set_cart_session( 'shipping_first_name', sanitize_text_field( $_POST['shipping_first_name'] ) );
            }            
            if ( isset($_POST['shipping_last_name']) && $_POST['shipping_last_name'] != '' ) {
                wcal_common::wcal_set_cart_session( 'shipping_last_name', sanitize_text_field( $_POST['shipping_last_name'] ) );
            }            
            if ( isset($_POST['shipping_company']) && $_POST['shipping_company'] != '' ) {
                wcal_common::wcal_set_cart_session( 'shipping_company', sanitize_text_field( $_POST['shipping_company'] ) );
            }            
            if ( isset($_POST['shipping_address_1']) && $_POST['shipping_address_1'] != '' ) {
                wcal_common::wcal_set_cart_session( 'shipping_address_1', sanitize_text_field( $_POST['shipping_address_1'] ) );
            }            
            if ( isset($_POST['shipping_address_2']) && $_POST['shipping_address_2'] != '' ) {
                wcal_common::wcal_set_cart_session( 'shipping_address_2', sanitize_text_field( $_POST['shipping_address_2'] ) );
            }            
            if ( isset($_POST['shipping_city']) && $_POST['shipping_city'] != '' ) {
                wcal_common::wcal_set_cart_session( 'shipping_city', sanitize_text_field( $_POST['shipping_city'] ) );
            }            
            if ( isset($_POST['shipping_state']) && $_POST['shipping_state'] != '' ) {
                wcal_common::wcal_set_cart_session( 'shipping_state', sanitize_text_field( $_POST['shipping_state'] ) );
            }            
            if ( isset($_POST['shipping_postcode']) && $_POST['shipping_postcode'] != '' ) {
                wcal_common::wcal_set_cart_session( 'shipping_postcode', sanitize_text_field( $_POST['shipping_postcode'] ) );
            }            
            if ( isset($_POST['shipping_country']) && $_POST['shipping_country'] != '' ) {
                wcal_common::wcal_set_cart_session( 'shipping_country', sanitize_text_field( $_POST['shipping_country'] ) );
            }
            // If a record is present in the guest cart history table for the same email id, then delete the previous records
            $query_guest = "SELECT id FROM `".$wpdb->prefix."ac_guest_abandoned_cart_history_lite` 
                            WHERE email_id = %s";						
            $results_guest = $wpdb->get_results( $wpdb->prepare( $query_guest, wcal_common::wcal_get_cart_session( 'billing_email' ) ) );
    
            if ( $results_guest ) {    
                foreach ( $results_guest as $key => $value ) {
                    $query = "SELECT * FROM `".$wpdb->prefix."ac_abandoned_cart_history_lite`
                              WHERE  user_id = %d AND recovered_cart = '0'" ;
                    $result = $wpdb->get_results( $wpdb->prepare( $query, $value->id ) ); 
                    // update existing record and create new record if guest cart history table will have the same email id. 
                    
                    if ( count( $result ) ) {
                         $update_mobile_info = "UPDATE `" .$wpdb->prefix."ac_abandoned_cart_history_lite` SET cart_ignored = '1' WHERE user_id = '".$value->id."'";
                        $wpdb->query( $update_mobile_info );
                        
                    }
                }
            }
            // Insert record in guest table
            $billing_first_name = wcal_common::wcal_get_cart_session( 'billing_first_name' );

            $billing_last_name = wcal_common::wcal_get_cart_session( 'billing_last_name' );

            $shipping_zipcode = $billing_zipcode = '';

            if ( wcal_common::wcal_get_cart_session( 'shipping_postcode' ) != "" ) {
                $shipping_zipcode = wcal_common::wcal_get_cart_session( 'shipping_postcode' );
            } elseif( wcal_common::wcal_get_cart_session( 'billing_postcode' ) != "" ) {
                $shipping_zipcode = $billing_zipcode = wcal_common::wcal_get_cart_session( 'billing_postcode' );
            }			
            $shipping_charges = $woocommerce->cart->shipping_total;			
            $insert_guest = "INSERT INTO `".$wpdb->prefix . "ac_guest_abandoned_cart_history_lite`( billing_first_name, billing_last_name, email_id, billing_zipcode, shipping_zipcode, shipping_charges ) 
                            VALUES ( '".$billing_first_name."', '".$billing_last_name."', '".wcal_common::wcal_get_cart_session( 'billing_email' )."', '".$billing_zipcode."', '".$shipping_zipcode."', '".$shipping_charges."' )";
            $wpdb->query( $insert_guest );
    
            //Insert record in abandoned cart table for the guest user
            $user_id			 = $wpdb->insert_id;
            wcal_common::wcal_set_cart_session( 'user_id', $user_id );
            $current_time        = current_time( 'timestamp' );
            $cut_off_time        = get_option( 'ac_cart_abandoned_time' );
            $cart_cut_off_time   = $cut_off_time * 60;
            $compare_time        = $current_time - $cart_cut_off_time;
    
            $query   = "SELECT * FROM `".$wpdb->prefix."ac_abandoned_cart_history_lite` 
                        WHERE user_id = %d AND cart_ignored = '0' AND recovered_cart = '0' AND user_type = 'GUEST'";			
            $results = $wpdb->get_results( $wpdb->prepare( $query, $user_id ) );
    
            $cart = array();
    
            if ( function_exists( 'WC' ) ) {
                $cart['cart'] = WC()->session->cart;
            } else {
                $cart['cart'] = $woocommerce->session->cart;
            }
    
        if ( 0 == count( $results ) ) {
                $get_cookie = WC()->session->get_session_cookie();
                $cart_info  = addslashes( json_encode( $cart ) );
                
                $query      = "SELECT * FROM `" . $wpdb->prefix . "ac_abandoned_cart_history_lite`
                               WHERE session_id LIKE %s AND cart_ignored = '0' AND recovered_cart = '0' ";
                $results    = $wpdb->get_results( $wpdb->prepare( $query, $get_cookie[0] ) );
                
                if ( 0 == count( $results ) ) {
                    $insert_query = "INSERT INTO `".$wpdb->prefix."ac_abandoned_cart_history_lite`( user_id, abandoned_cart_info, abandoned_cart_time, cart_ignored, recovered_cart, user_type, session_id )
                                     VALUES ( '".$user_id."', '".$cart_info."', '".$current_time."', '0', '0', 'GUEST', '".$get_cookie[0] ."' )";
                    $wpdb->query( $insert_query );
                    
                    $abandoned_cart_id = $wpdb->insert_id;
                    wcal_common::wcal_set_cart_session( 'abandoned_cart_id_lite', $abandoned_cart_id );
                    
                    // $insert_persistent_cart = "INSERT INTO `".$wpdb->prefix."usermeta`( user_id, meta_key, meta_value )
                    //                            VALUES ( '".$user_id."', '_woocommerce_persistent_cart', '".$cart_info."' )";
                    // $wpdb->query( $insert_persistent_cart );
                    if ( is_multisite() ) {
                        // get main site's table prefix
                        $main_prefix            = $wpdb->get_blog_prefix(1);
                        $insert_persistent_cart = "INSERT INTO `" . $main_prefix . "usermeta`( user_id, meta_key, meta_value )
                                                   VALUES ( '".$user_id."', '_woocommerce_persistent_cart', '".$cart_info."' )";
                        $wpdb->query( $insert_persistent_cart );

                    } else {
                        $insert_persistent_cart = "INSERT INTO `" . $wpdb->prefix . "usermeta`( user_id, meta_key, meta_value )
                                                   VALUES ( '".$user_id."', '_woocommerce_persistent_cart', '".$cart_info."' )";
                        $wpdb->query( $insert_persistent_cart );
                    }
                } else {
                    $query_update         = "UPDATE `" . $wpdb->prefix . "ac_abandoned_cart_history_lite` SET user_id = '" . $user_id . "', abandoned_cart_info = '" . $cart_info . "', abandoned_cart_time  = '" . $current_time . "' WHERE session_id ='" . $get_cookie[0] . "' AND cart_ignored='0' ";
                    $wpdb->query( $query_update );
                    $query_update_get     = "SELECT * FROM `" . $wpdb->prefix . "ac_abandoned_cart_history_lite`
                                             WHERE user_id ='" . $user_id . "' AND cart_ignored='0' AND session_id ='" . $get_cookie[0] . "' ";
                    $get_abandoned_record = $wpdb->get_results( $query_update_get );
                    
                    if ( count( $get_abandoned_record ) > 0 ) {
                        $abandoned_cart_id = $get_abandoned_record[0]->id;
                        wcal_common::wcal_set_cart_session( 'abandoned_cart_id_lite', $abandoned_cart_id );
                    }
                    
                    $insert_persistent_cart = "INSERT INTO `".$wpdb->prefix."usermeta`( user_id, meta_key, meta_value )
                                               VALUES ( '".$user_id."', '_woocommerce_persistent_cart', '".$cart_info."' )";
                    $wpdb->query( $insert_persistent_cart );   
                    if ( is_multisite() ) {
                     // get main site's table prefix
                     $main_prefix            = $wpdb->get_blog_prefix(1);
                     $insert_persistent_cart = "INSERT INTO `" . $main_prefix . "usermeta`( user_id, meta_key, meta_value )
                                                VALUES ( '".$user_id."', '_woocommerce_persistent_cart', '".$cart_info."' )";
                      $wpdb->query( $insert_persistent_cart );

                    } else { 
                      $insert_persistent_cart = "INSERT INTO `" . $wpdb->prefix . "usermeta`( user_id, meta_key, meta_value )
                        VALUES ( '".$user_id."', '_woocommerce_persistent_cart', '".$cart_info."' )";
                       $wpdb->query( $insert_persistent_cart );
                    }                 
                }                 
            }
        }
    }

    /**
     * It will populate the data on the chekout field if user comes from the abandoned cart reminder emails.
     * @hook woocommerce_checkout_fields
     * @param array $fields All fields of checkout page
     * @return array $fields
     * @since 2.2
     */
    function guest_checkout_fields( $fields ) {
        if ( wcal_common::wcal_get_cart_session( 'guest_first_name' ) != "" ) {
            $_POST['billing_first_name'] = wcal_common::wcal_get_cart_session( 'guest_first_name' );
        }
        if ( wcal_common::wcal_get_cart_session( 'guest_last_name' ) != "" ) {
            $_POST['billing_last_name'] = wcal_common::wcal_get_cart_session( 'guest_last_name' );
        }
        if ( wcal_common::wcal_get_cart_session( 'guest_email' ) != "" ) {
            $_POST['billing_email'] = wcal_common::wcal_get_cart_session( 'guest_email' );
        }
        if ( wcal_common::wcal_get_cart_session( 'guest_phone' ) != "" ) {
            $_POST['billing_phone'] = wcal_common::wcal_get_cart_session( 'guest_phone' );
        }
    return $fields;
    }
}
$woocommerce_guest_ac = new woocommerce_guest_ac();					
?>