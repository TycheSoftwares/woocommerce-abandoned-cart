<?php 
/* Woocommerce Abandoned Cart Plugin Addon - Saves guest cart information
* 
*/
{
	/**
	 * Localisation
	 **/

	/**
	 * woocommerce_abandon_cart class
	 **/
	if ( ! class_exists( 'woocommerce_guest_ac' ) ) {

		class woocommerce_guest_ac {		
			var $a;		
			public function __construct() {
				add_action( 'woocommerce_after_checkout_billing_form', 'user_side_js' );
				add_action( 'init','load_ac_ajax' );
				add_filter( 'woocommerce_checkout_fields', 'guest_checkout_fields' );
				}
			}
				
			/*-----------------------------------------------------------------------------------*/
			/* Class Functions */
			/*-----------------------------------------------------------------------------------*/
			function load_ac_ajax() {

                    if ( ! is_user_logged_in() ) {
						add_action( 'wp_ajax_nopriv_save_data', 'save_data' );
					} else {
						add_action( 'wp_ajax_save_data', 'save_data' );
					}
			}

			function user_side_js() {
				?>
				<script type="text/javascript">
				jQuery( 'input#billing_email' ).on( 'change', function() {
                        var data = {
                                        billing_first_name	: jQuery('#billing_first_name').val(),
                                        billing_last_name	: jQuery('#billing_last_name').val(),
                                        billing_company		: jQuery('#billing_company').val(),
                                        billing_address_1	: jQuery('#billing_address_1').val(),
                                        billing_address_2	: jQuery('#billing_address_2').val(),
                                        billing_city		: jQuery('#billing_city').val(),
                                        billing_state		: jQuery('#billing_state').val(),
                                        billing_postcode	: jQuery('#billing_postcode').val(),
                                        billing_country		: jQuery('#billing_country').val(),
                                        billing_phone		: jQuery('#billing_phone').val(),
                                        billing_email		: jQuery('#billing_email').val(),
                                        order_notes			: jQuery('#order_comments').val(),
                                        shipping_first_name	: jQuery('#shipping_first_name').val(),
                                        shipping_last_name	: jQuery('#shipping_last_name').val(),
                                        shipping_company	: jQuery('#shipping_company').val(),
                                        shipping_address_1	: jQuery('#shipping_address_1').val(),
                                        shipping_address_2	: jQuery('#shipping_address_2').val(),
                                        shipping_city		: jQuery('#shipping_city').val(),
                                        shipping_state		: jQuery('#shipping_state').val(),
                                        shipping_postcode	: jQuery('#shipping_postcode').val(),
                                        shipping_country	: jQuery('#shipping_country').val(),
                                        ship_to_billing		: jQuery('#shiptobilling-checkbox').val(),
                                        action: 'save_data'
                                };					
                                jQuery.post( "<?php echo get_home_url();?>/wp-admin/admin-ajax.php", data, function(response) {
                                });					
                    });
				</script>			
			<?php
			}
			
			function save_data() {
				
                        if ( ! is_user_logged_in() ) {
                            global $wpdb, $woocommerce;

                            $_SESSION['billing_first_name'] = $_POST['billing_first_name'];
                            $_SESSION['billing_last_name'] = $_POST['billing_last_name'];
                            $_SESSION['billing_company'] = $_POST['billing_company'];
                            $_SESSION['billing_address_1'] = $_POST['billing_address_1'];
                            $_SESSION['billing_address_2'] = $_POST['billing_address_2'];
                            $_SESSION['billing_city'] = $_POST['billing_city'];
                            $_SESSION['billing_state'] = $_POST['billing_state'];
                            $_SESSION['billing_postcode'] = $_POST['billing_postcode'];
                            $_SESSION['billing_country'] = $_POST['billing_country'];
                            $_SESSION['billing_email'] = $_POST['billing_email'];
                            $_SESSION['billing_phone'] = $_POST['billing_phone'];
                            $_SESSION['order_notes'] = $_POST['order_notes'];
                            $_SESSION['ship_to_billing'] = $_POST['ship_to_billing'];
                            $_SESSION['shipping_first_name'] = $_POST['shipping_first_name'];
                            $_SESSION['shipping_last_name'] = $_POST['shipping_last_name'];
                            $_SESSION['shipping_company'] = $_POST['shipping_company'];
                            $_SESSION['shipping_address_1'] = $_POST['shipping_address_1'];
                            $_SESSION['shipping_address_2']	= $_POST['shipping_address_2'];
                            $_SESSION['shipping_city'] = $_POST['shipping_city'];
                            $_SESSION['shipping_state'] = $_POST['shipping_state'];
                            $_SESSION['shipping_postcode'] = $_POST['shipping_postcode'];
                            $_SESSION['shipping_country'] = $_POST['shipping_country'];

                            // If a record is present in the guest cart history table for the same email id, then delete the previous records
                            $query_guest = "SELECT id FROM `".$wpdb->prefix."ac_guest_abandoned_cart_history_lite` WHERE email_id = %s";						
                            $results_guest = $wpdb->get_results( $wpdb->prepare( $query_guest, $_SESSION['billing_email'] ) );

                            if ( $results_guest ) {

                                foreach ( $results_guest as $key => $value ) {
                                        $query = "SELECT * FROM `".$wpdb->prefix."ac_abandoned_cart_history_lite`
                                                        WHERE  user_id = %d AND recovered_cart = '0'" ;
                                        $result = $wpdb->get_results( $wpdb->prepare( $query, $value->id ) );

                                        if ( $result ) {
                                                $delete_sent_email = "DELETE FROM `".$wpdb->prefix."ac_sent_history` WHERE abandoned_order_id = '".$result[0]->id."'";
                                                $wpdb->query( $delete_sent_email );						
                                                $delete_query = "DELETE FROM `".$wpdb->prefix."ac_abandoned_cart_history_lite` WHERE user_id = '".$value->id."'";
                                                $wpdb->query( $delete_query );
                                        }
                                        $guest_delete = "DELETE FROM `".$wpdb->prefix."ac_guest_abandoned_cart_history_lite` WHERE id = '".$value->id."'";
                                        $wpdb->query( $guest_delete );
                                }
                            }

                                // Insert record in guest table
                            if ( isset( $_SESSION['billing_first_name'] ) ) $billing_first_name = $_SESSION['billing_first_name'];
                            else $billing_first_name = '';

                            if ( isset( $_SESSION['billing_last_name'] ) ) $billing_last_name = $_SESSION['billing_last_name'];
                            else $billing_last_name = '';

                            $shipping_zipcode = $billing_zipcode = '';

                            if ( isset( $_SESSION['shipping_postcode'] ) && $_SESSION['shipping_postcode'] != "" ) $shipping_zipcode = $_SESSION['shipping_postcode'];
                            else $shipping_zipcode = $billing_zipcode = $_SESSION['billing_postcode'];			
                            $shipping_charges = $woocommerce->cart->shipping_total;			
                            $insert_guest = "INSERT INTO `".$wpdb->prefix . "ac_guest_abandoned_cart_history_lite`( billing_first_name, billing_last_name, email_id, billing_zipcode, shipping_zipcode, shipping_charges ) 
                                                            VALUES ( '".$billing_first_name."', '".$billing_last_name."', '".$_SESSION['billing_email']."', '".$billing_zipcode."', '".$shipping_zipcode."', '".$shipping_charges."' )";
                            $wpdb->query( $insert_guest );

                            //Insert record in abandoned cart table for the guest user
                            $user_id				= $wpdb->insert_id;
                            $_SESSION['user_id'] = $user_id;
                            $current_time = current_time('timestamp');
                            $cut_off_time = get_option('ac_cart_abandoned_time');
                            $cart_cut_off_time = $cut_off_time * 60;
                            $compare_time = $current_time - $cart_cut_off_time;

                            $query = "SELECT * FROM `".$wpdb->prefix."ac_abandoned_cart_history_lite` WHERE user_id = %d AND cart_ignored = '0' AND recovered_cart = '0' AND user_type = 'GUEST'";			
                            $results = $wpdb->get_results( $wpdb->prepare( $query, $user_id ) );		
                            $cart = array();

                            foreach ( $woocommerce->cart->cart_contents as $cart_id => $value ) {
                                    $cart['cart'][$cart_id] = array();
                                    foreach ( $value as $k=>$v ) {
                                            $cart['cart'][$cart_id][$k] = $v;
                                            if ( $k == "quantity" ) {
                                                    $product = get_product( $cart['cart'][$cart_id]['product_id'] );
                                                    $product_type = $product->product_type;

                                                    if ( $product_type == "variable" ) {
                                                            if(is_plugin_active('woocommerce-dynamic-pricing/woocommerce-dynamic-pricing.php')) {
                                                                $price = floatval( preg_replace( '#[^\d.]#', '', $woocommerce->cart->total ) );
                                                            }  else {
                                                                $price = get_post_meta( $cart['cart'][$cart_id]['variation_id'], '_price', true);
                                                            }
                                                    } else {
                                                            if(is_plugin_active('woocommerce-dynamic-pricing/woocommerce-dynamic-pricing.php')) {
                                                                $price = floatval( preg_replace( '#[^\d.]#', '', $woocommerce->cart->total ) );
                                                            }  else {
                                                                $price = get_post_meta( $cart['cart'][$cart_id]['product_id'], '_price', true);
                                                            }
                                                    }
                                                    if(is_plugin_active('woocommerce-dynamic-pricing/woocommerce-dynamic-pricing.php')) {
                                                        $cart['cart'][$cart_id]['line_total'] = $price;
                                                    }else {
                                                        $cart['cart'][$cart_id]['line_total'] = $cart['cart'][$cart_id]['quantity'] * $price;
                                                    }
                                                    $cart['cart'][$cart_id]['line_tax']	= '0';
                                                    $cart['cart'][$cart_id]['line_subtotal'] = $cart['cart'][$cart_id]['line_total'];
                                                    $cart['cart'][$cart_id]['line_subtotal_tax'] = $cart['cart'][$cart_id]['line_tax'];
                                                    break;
                                            } 
                                    }
                            }

                            if ( count( $results ) == 0 ) {
                                    $cart_info = json_encode( $cart );
                                    $insert_query = "INSERT INTO `".$wpdb->prefix."ac_abandoned_cart_history_lite`( user_id, abandoned_cart_info, abandoned_cart_time, cart_ignored, recovered_cart, user_type )
                                                                            VALUES ( '".$user_id."', '".$cart_info."', '".$current_time."', '0', '0', 'GUEST' )";	
                                    $wpdb->query( $insert_query );	
                                    $insert_persistent_cart = "INSERT INTO `".$wpdb->prefix."usermeta`( user_id, meta_key, meta_value )
                                                                                    VALUES ( '".$user_id."', '_woocommerce_persistent_cart', '".$cart_info."' )";								
                                    $wpdb->query( $insert_persistent_cart );
                            }
                        }
                    }
			
                    function guest_checkout_fields( $fields ) {

                            if ( isset( $_SESSION['guest_first_name']) && $_SESSION['guest_first_name'] != "" ) $_POST['billing_first_name'] = $_SESSION['guest_first_name'];

                            if ( isset( $_SESSION['guest_last_name']) && $_SESSION['guest_last_name'] != "" ) $_POST['billing_last_name'] = $_SESSION['guest_last_name'];

                            if ( isset( $_SESSION['guest_email']) && $_SESSION['guest_email'] != "" ) $_POST['billing_email'] = $_SESSION['guest_email'];				
                            return $fields;
                    }
	}
	$woocommerce_guest_ac = new woocommerce_guest_ac();		
}				
?>