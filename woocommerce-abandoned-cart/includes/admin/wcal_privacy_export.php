<?php
/**
 * Export Abandoned Carts data in 
 * Dashboard->Tools->Export Personal Data
 * 
 * @since 4.9
 */
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists('Wcal_Personal_Data_Export' ) ) {

    /**
     * Export Abandoned Carts data in
     * Dashboard->Tools->Export Personal Data
     */
    class Wcal_Personal_Data_Export {
    
        /**
         * Construct
         * @since 7.8
         */ 
        public function __construct() {
            // Hook into the WP export process
            add_filter( 'wp_privacy_personal_data_exporters', array( &$this, 'wcal_exporter_array' ), 6 );
        }
    
        /**
         * Add our export and it's callback function
         * 
         * @param array $exporters - Any exportes that need to be added by 3rd party plugins
         * @param array $exporters - Exportes list containing our plugin details
         * 
         * @since 4.9
         */ 
        public static function wcal_exporter_array( $exporters = array() ) {
            
            $exporter_list = array();
            // Add our export and it's callback function
            $exporter_list[ 'wcal_carts' ] = array( 
                'exporter_friendly_name' => __( 'Abandoned & Recovered Carts', 'woocommerce-abandoned-cart' ),
                'callback'               => array( 'Wcal_Personal_Data_Export', 'wcal_data_exporter' )
            );
             
            $exporters = array_merge( $exporters, $exporter_list );

            return $exporters;
            
        }
        
        /**
         * Returns data to be displayed for exporting the 
         * cart details
         * 
         * @param string $email_address - EMail Address for which personal data is being exported
         * @param integer $page - The Export page number
         * @return array $data_to_export - Data to be exported
         * 
         * @hook wp_privacy_personal_data_exporters
         * @global $wpdb
         * @since  4.9
         */
        static function wcal_data_exporter( $email_address, $page ) {
            
            global $wpdb;
            
            $done                   = false;
            $page                   = (int) $page;
            $user                   = get_user_by( 'email', $email_address ); // Check if user has an ID in the DB to load stored personal data.
            $data_to_export         = array();
            $blank_cart_info        = '{"cart":[]}';
            $blank_cart_info_guest  = '[]';
            $blank_cart             = '""';   
            
            $user_id = $user ? (int) $user->ID : 0;
            
            if ( $user_id > 0 ) { // registered user
                
                $cart_query = "SELECT id FROM `" . $wpdb->prefix . 'ac_abandoned_cart_history_lite' . "`
                               WHERE user_id = %d AND
                               user_type = 'REGISTERED' AND abandoned_cart_info NOT LIKE '%$blank_cart_info%' AND abandoned_cart_info NOT LIKE '$blank_cart'";
                
                $cart_ids = $wpdb->get_results( $wpdb->prepare( $cart_query, $user_id ) );
            } else { // guest carts
                $guest_query = "SELECT id FROM `" . $wpdb->prefix . 'ac_guest_abandoned_cart_history_lite' . "`
                                WHERE email_id = %s";
                
                $guest_user_ids = $wpdb->get_results( $wpdb->prepare( $guest_query, $email_address ) );
                
                if ( 0 == count( $guest_user_ids ) ) 
                    return array(
                       'data' => array(),
                       'done' => true,
                    );
                
                $cart_ids = array();
                
                foreach ( $guest_user_ids as $ids ) {
                    // get the cart data
                    $cart_query = "SELECT id, abandoned_cart_info AS cart_info FROM `" . $wpdb->prefix . 'ac_abandoned_cart_history_lite' . "`
                                   WHERE user_id = %d AND
                                   user_type = 'GUEST'";
                    
                    $cart_data = $wpdb->get_results( $wpdb->prepare( $cart_query, $ids->id ) );
                    
                    $cart_ids = array_merge( $cart_ids, $cart_data );
                }
            }
            
            if ( 0 < count( $cart_ids ) ) {
                
                $cart_chunks = array_chunk( $cart_ids, 10, true );
                
                $cart_export = isset( $cart_chunks[ $page - 1 ] ) ? $cart_chunks[ $page - 1 ] : array();
                if ( count( $cart_export ) > 0 ) {
                    
                    foreach ( $cart_export as $abandoned_ids ) {
                    
                        $cart_id = $abandoned_ids->id;
                        if ( count( $abandoned_ids->id ) > 0 ) {
                            $data_to_export[] = array(
                                'group_id'    => 'wcal_carts',
                                'group_label' => __( 'Abandoned Carts', 'woocommerce-abandoned-cart' ),
                                'item_id'     => 'cart-' . $cart_id,
                                'data'        => self::get_cart_data( $cart_id ),
                            );
                        }
                    }
                    $done = $page > count( $cart_chunks );
                } else {
                    $done = true;
                }
            } else {
                $done = true;
            }
            
            return array(
                'data' => $data_to_export,
                'done' => $done,
            );
    
        }
        
        /**
         * Returns the personal data for each abandoned cart
         * 
         * @param integer $abandoned_id - Abandoned Cart ID
         * @return array $personal_data - Personal data to be displayed
         * @global $wpdb
         * @since  4.9
         */
        static function get_cart_data( $abandoned_id ) {
            $personal_data = array();
            
            global $wpdb;
            
            $cart_query   = "SELECT * FROM `" . $wpdb->prefix . 'ac_abandoned_cart_history_lite' . "`
                             WHERE id = %d";
            $cart_details = $wpdb->get_results( $wpdb->prepare( $cart_query, $abandoned_id ) );
            $cart_details = $cart_details[0];
            $user_id      = $cart_details->user_id;
            $user_type    = $cart_details->user_type;

           if ( $user_type == 'GUEST' ) {

            $cart_details_to_export = apply_filters( 'wcal_personal_export_cart_details_prop', array(
                'cart_id'                    => __( 'Abandoned Cart ID', 'woocommerce-abandoned-cart' ),
                'date_created'               => __( 'Abandoned Date', 'woocommerce-abandoned-cart' ),
                'cart_status'                => __( 'Abandoned Cart Status', 'woocommerce-abandoned-cart' ),
                'total'                      => __( 'Cart Total', 'woocommerce-abandoned-cart' ),
                'items'                      => __( 'Items Present', 'woocommerce-abandoned-cart' ),
                'session_id'                 => __( 'Session ID', 'woocommerce-abandoned-cart' ),
                'formatted_billing_address'  => __( 'Billing Address', 'woocommerce-abandoned-cart' ),
                'billing_email'              => __( 'Email Address', 'woocommerce-abandoned-cart' ),
            ), $abandoned_id );
           
           } else {
            $cart_details_to_export = apply_filters( 'wcal_personal_export_cart_details_prop', array(
                'cart_id'                    => __( 'Abandoned Cart ID', 'woocommerce-abandoned-cart' ),
                'date_created'               => __( 'Abandoned Date', 'woocommerce-abandoned-cart' ),
                'cart_status'                => __( 'Abandoned Cart Status', 'woocommerce-abandoned-cart' ),
                'total'                      => __( 'Cart Total', 'woocommerce-abandoned-cart' ),
                'items'                      => __( 'Items Present', 'woocommerce-abandoned-cart' ),
                'formatted_billing_address'  => __( 'Billing Address', 'woocommerce-abandoned-cart' ),
                'billing_email'              => __( 'Email Address', 'woocommerce-abandoned-cart' ),
            ), $abandoned_id );
            
           }         
            
            $cart_data = json_decode( stripslashes( $cart_details->abandoned_cart_info ) );
            $cart_info = $cart_data->cart;
            
            if ( count( $cart_info ) > 0 ) {
                $cart_details_formatted = self::wcal_get_cart_details_export( $cart_info );
            }
            
            if ( 'GUEST' == $user_type ) {
                $guest_details = self::wcal_get_guest_personal_info( $user_id );
            }
            foreach ( $cart_details_to_export as $prop => $name ) {
                
                switch ( $prop ) {
                    case 'cart_id':
                        $value = $cart_details->id;
                        break;
                    case 'date_created':
                        $value = date( 'Y-m-d H:i:s', $cart_details->abandoned_cart_time );
                        break;
                    case 'cart_status':
                        
                        $cart_ignored = $cart_details->cart_ignored;
                        
                        switch( $cart_ignored ) {
                            case '0':
                                $value =  $cart_details->recovered_cart > 0  ? __( "Cart Recovered - Order #", 'woocommerce-abandoned-cart' ) . $cart_details->recovered_cart : __( 'Abandoned', 'woocommerce-abandoned-cart' );
                                break;
                            case '1':
                                $value = $cart_details->recovered_cart > 0  ? __( "Cart Recovered - Order #", 'woocommerce-abandoned-cart' ) . $cart_details->recovered_cart : __( 'Abandoned but new cart created', 'woocommerce-abandoned-cart' );
                                break;
                            case '2':
                                $value = __( 'Abandoned - Order Unpaid (Order #', 'woocommerce-abandoned-cart') . $cart_details->recovered_cart . ")";
                                break;
                        }
                        break;
                    case 'total':
                        $total = 0;
                        
                        if ( count( $cart_info ) > 0 ) {
                            foreach ( $cart_info as $k => $v ) {
                        
                                $total += $cart_details_formatted[$k][ 'item_total' ];
                            }
                        }
                        $value = wc_price( $total );
                        break;
                    case 'items':
                        $value = '';
                        
                        if ( count( $cart_info ) > 0 ) {
                            foreach ( $cart_info as $k => $v ) {
                        
                                $product_name = $cart_details_formatted[$k][ 'product_name' ];
                                $qty = $cart_details_formatted[$k][ 'qty' ];
                        
                                $value .= ( $value == '' ) ? "$product_name x $qty" : ", $product_name x $qty";
                            }
                        }
                        break;
                    case 'formatted_billing_address':
                        
                        if ( $user_type == 'REGISTERED' ) { // registered user
                            
                            $billing = wcal_common::wcal_get_billing_details( $user_id );
                            $value = get_user_meta( $user_id, 'billing_first_name', true ); // First Name
                            $value .= ' ' . get_user_meta( $user_id, 'billing_last_name', true ); // Last Name 
                            if ( count( $billing ) > 0 ) {
                                foreach ( $billing as $details ) {
                                    if ( '' != $details ) {
                                        $value .= ",$details ";
                                    } 
                                }
                                
                            }
                        } elseif ( 'GUEST' == $user_type ) {
                            if ( count( $guest_details ) > 0 ) {
                                $value = $guest_details->billing_first_name; // First Name
                                $value .= ' ' . $guest_details->billing_last_name; // Last Name
                            }
                        }
                        break;
                    
                    case 'billing_email':
                        if ( 'REGISTERED' == $user_type ) { // registered user
                            $value = get_user_meta( $user_id, $prop, true );
                        } else if ( 'GUEST' == $user_type ) {
                            if ( count( $guest_details ) > 0 ) {
                                $value = $guest_details->$prop; 
                            }
                        }
                        break;
                    default:
                        $value = ( isset( $cart_details->$prop ) ) ? $cart_details->$prop : '';
                        break;
                }
                
                $value = apply_filters( 'wcal_personal_export_cart_details_prop_value', $value, $prop, $cart_details );
                
                $personal_data[] = array( 
                    'name'  => $name,
                    'value' => $value,
                );
            
            }
            $personal_data = apply_filters( 'wcal_personal_data_cart_details_export', $personal_data, $cart_details );
            
            return $personal_data;
        }
        
        /**
         * Returns the personal data from the plugin guest cart table
         * for guest abandoned carts
         * 
         * @param integer $user_id - User ID
         * @return array $guest_details - Guest personal details
         * @global $wpdb
         * @since  4.9
         */
        static function wcal_get_guest_personal_info( $user_id ) {
            global $wpdb;
            $guest_details = array();
            
            $guest_query   = "SELECT billing_first_name, billing_last_name, email_id AS billing_email, phone AS billing_phone FROM `" . $wpdb->prefix . 'ac_guest_abandoned_cart_history_lite' . "`
                              WHERE id = %d";
            
            $guest_details = $wpdb->get_results( $wpdb->prepare( $guest_query, $user_id ) );
            
            if ( is_array( $guest_details ) && count( $guest_details ) > 0 ) {
                $guest_details = $guest_details[0];
            }
            
            return $guest_details;
        }
        
        /**
         * Returns the Cart Details such as quantity, product name
         * etc.
         * 
         * @param object $cart_info - Abandoned Cart Information
         * @return array $cart_details - Array containing product, qty & total for each item
         * @since 4.9
         */
        static function wcal_get_cart_details_export( $cart_info ) {
            
            $cart_details = array();
             
            if ( count( $cart_info ) > 0 ) {
                    
                $cart_total = 0;
                foreach ( $cart_info as $k => $item_detail ) {
                        
                    // Qty
                    $qty = $item_detail->quantity;
                     
                    //Product Name
                    $product_id     = $item_detail->product_id;
                    $prod_obj       = wc_get_product( $product_id );
                    $product_name   = $prod_obj->get_name();
                     
                    // Variation Name
                    if ( isset( $item_detail->variation_id ) && $item_detail->variation_id > 0 ) {
                        $variation_id   = $item_detail->variation_id;
                        $variation      = wc_get_product( $variation_id );
                        $variation_name = $variation->get_name();
                         
                        $product_name = $variation_name;
                    }
                    
                    // Total
                    $item_total = $item_detail->line_total;
                    if ( $item_detail->line_subtotal_tax > 0 ) {
                        $item_total += $item_detail->line_subtotal_tax;
                    } 
                     
                    // Populate the array
                    $cart_details[ $k ][ 'qty' ]          = $qty;
                    $cart_details[ $k ][ 'product_name' ] = $product_name;
                    $cart_details[ $k ][ 'item_total' ]   = $item_total;
                     
                    $cart_total += $item_total;
                }
                    
                $cart_details[ 'total' ] = $cart_total;
            }
            
            return $cart_details;
        }
    } // end of class
    $Wcal_Personal_Data_Export = new Wcal_Personal_Data_Export();
} // end if
?>