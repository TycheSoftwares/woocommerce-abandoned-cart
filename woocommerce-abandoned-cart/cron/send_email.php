<?php 

if(defined('WP_CONTENT_FOLDERNAME')){
    $wp_content_dir_name = WP_CONTENT_FOLDERNAME;
}else{
    $wp_content_dir_name = "wp-content";
}



$url = dirname( __FILE__ );
$my_url = explode( $wp_content_dir_name , $url );
$path = $my_url[0];

require_once $path . 'wp-load.php';
	/**
	 * woocommerce_abandon_cart_cron class
	 **/
	if ( !class_exists( 'woocommerce_abandon_cart_cron' ) ) {
	
		class woocommerce_abandon_cart_cron {
			
			var $cart_settings_cron;
			var $cart_abandon_cut_off_time_cron;
			
			public function __construct() {
				
				$this->cart_settings_cron = json_decode( get_option( 'woocommerce_ac_settings' ) );
				
				$this->cart_abandon_cut_off_time_cron = ( $this->cart_settings_cron[0]->cart_time ) * 60;				
			}
			
			/*-----------------------------------------------------------------------------------*/
			/* Class Functions */
			/*-----------------------------------------------------------------------------------*/
			
			/**
			 * Function to send emails
			 */
			function woocommerce_ac_send_email() {
				
				global $wpdb, $woocommerce;
			
				//Grab the cart abandoned cut-off time from database.
				$cart_settings = json_decode( get_option( 'woocommerce_ac_settings' ) );
				
				$cart_abandon_cut_off_time = ( $cart_settings[0]->cart_time ) * 60;
			
				//Fetch all active templates present in the system
				$query = "SELECT wpet . *
            		      FROM `".$wpdb->prefix."ac_email_templates_lite` AS wpet
            			  WHERE wpet.is_active = '1'
            			  ORDER BY `day_or_hour` DESC, `frequency` ASC ";
				$results = $wpdb->get_results( $query );
			
				$hour_seconds = 3600;   // 60 * 60
				$day_seconds  = 86400; // 24 * 60 * 60
				foreach ( $results as $key => $value )
				{
					if ( $value->day_or_hour == 'Days' )
					{
						$time_to_send_template_after = $value->frequency * $day_seconds;
					}
					elseif ( $value->day_or_hour == 'Hours' )
					{
						$time_to_send_template_after = $value->frequency * $hour_seconds;
					}
			
					$carts = $this->get_carts( $time_to_send_template_after, $cart_abandon_cut_off_time );
			
					$email_frequency     = $value->frequency;
					$email_body_template = $value->body;			
					$email_subject       = $value->subject;
					$user_email_from     = get_option( 'admin_email' );
					$headers[]           = "From: ".$value->from_name." <".$user_email_from.">"."\r\n";
					$headers[]           = "Content-Type: text/html"."\r\n";
					$template_id         = $value->id;
					
					foreach ( $carts as $key => $value )
					{
					    if ( $value->user_type == "GUEST" ) {
					        $value->user_login = "";
					        $query_guest = "SELECT billing_first_name, billing_last_name, email_id FROM `".$wpdb->prefix."ac_guest_abandoned_cart_history_lite` WHERE id = %d";
					        $results_guest = $wpdb->get_results( $wpdb->prepare( $query_guest, $value->user_id ) );
					        $value->user_email = $results_guest[0]->email_id;
					    }
					    
						$cart_info_db_field = json_decode( $value->abandoned_cart_info );
						if ( count( $cart_info_db_field->cart ) > 0 )
						{
							$cart_update_time = $value->abandoned_cart_time;
			
							$new_user = $this->check_sent_history( $value->user_id, $cart_update_time, $template_id, $value->id );
							if ( $new_user == true )
							{
								$cart_info_db = $value->abandoned_cart_info;
			
								$email_body = $email_body_template;
								
							     if ( $value->user_type == "GUEST" ) {
								    if ( isset( $results_guest[0]->billing_first_name ) ) {
								        $email_body = str_replace( "{{customer.firstname}}", $results_guest[0]->billing_first_name, $email_body );
								        $email_subject = str_replace( "{{customer.firstname}}", $results_guest[0]->billing_first_name, $email_subject );
								    }
								
								    if ( isset( $results_guest[0]->billing_last_name ) ) $email_body = str_replace( "{{customer.lastname}}", $results_guest[0]->billing_last_name, $email_body );
								
								    if ( isset( $results_guest[0]->billing_first_name ) && isset( $results_guest[0]->billing_last_name ) ) $email_body = str_replace( "{{customer.fullname}}", $results_guest[0]->billing_first_name." ".$results_guest[0]->billing_last_name, $email_body );
								    else if ( isset( $results_guest[0]->billing_first_name ) ) $email_body = str_replace( "{{customer.fullname}}", $results_guest[0]->billing_first_name, $email_body );
								    else if ( isset( $results_guest[0]->billing_last_name)) $email_body = str_replace( "{{customer.fullname}}", $results_guest[0]->billing_last_name, $email_body );
								} else {
								    $email_body = str_replace( "{{customer.firstname}}", get_user_meta( $value->user_id, 'first_name', true ), $email_body );
								    $email_subject = str_replace( "{{customer.firstname}}", get_user_meta( $value->user_id, 'first_name', true ), $email_subject );
								    $email_body = str_replace( "{{customer.lastname}}", get_user_meta( $value->user_id, 'last_name', true ), $email_body );
								    $email_body = str_replace( "{{customer.fullname}}", get_user_meta( $value->user_id, 'first_name', true )." ".get_user_meta( $value->user_id, 'last_name', true ), $email_body );
								}
								
								$query_sent = "INSERT INTO `".$wpdb->prefix."ac_sent_history_lite` ( template_id, abandoned_order_id, sent_time, sent_email_id )
								               VALUES ( %s, %s, '".current_time( 'mysql' )."', %s )";
									
								$wpdb->query( $wpdb->prepare( $query_sent, $template_id, $value->id, $value->user_email ) );
								
								$query_id = "SELECT * FROM `".$wpdb->prefix."ac_sent_history_lite` 
								             WHERE template_id = %s AND abandoned_order_id = %s
								             ORDER BY id DESC
								             LIMIT 1 ";
									
								$results_sent = $wpdb->get_results( $wpdb->prepare( $query_id, $template_id, $value->id ) );
									
								
								$email_sent_id = $results_sent[0]->id;
								
								$var = '';
								if( preg_match( "{{products.cart}}", $email_body, $matched ) ) {
								    $var = '<h3>'.__( "Your Shopping Cart", "woocommerce-ac" ).'</h3>
                                            <table border="0" cellpadding="10" cellspacing="0" class="templateDataTable">
                                                                <tr>
                                                                <th>'.__( "Item", "woocommerce-ac" ).'</th>
                                                                <th>'.__( "Name", "woocommerce-ac" ).'</th>
                                                                <th>'.__( "Quantity", "woocommerce-ac" ).'</th>
                                                                <th>'.__( "Price", "woocommerce-ac" ).'</th>
                                                                <th>'.__( "Line Subtotal", "woocommerce-ac" ).'</th>
                                                                </tr>';                    
								    
								    $cart_details = $cart_info_db_field->cart;
								    $cart_total = $item_subtotal = $item_total = 0;
								    $sub_line_prod_name = '';
								    foreach ( $cart_details as $k => $v ) {
								        $quantity_total	= $v->quantity;
								        $product_id	        = $v->product_id;
								        $prod_name          = get_post( $product_id );
								        $product_link_track = get_permalink( $product_id );
								        $product_name = $prod_name->post_title;
								        if ( $sub_line_prod_name == '' ) {
								            $sub_line_prod_name = $product_name;
								        }
								        // Item subtotal is calculated as product total including taxes
								        if( $v->line_subtotal_tax != 0 && $v->line_subtotal_tax > 0 ) {
								            $item_subtotal = $item_subtotal + $v->line_total + $v->line_subtotal_tax;
								        } else {
								            $item_subtotal = $item_subtotal + $v->line_total;
								        }
								
								        //	Line total
								        $item_total         = $item_subtotal;
								        $item_subtotal	    = $item_subtotal / $quantity_total;
								        $item_total_display = number_format( $item_total, 2 );
								        $item_subtotal	    = number_format( $item_subtotal, 2 );
								        $product            = get_product( $product_id );
								        $prod_image         = $product->get_image();
								        $image_url          =  wp_get_attachment_url( get_post_thumbnail_id( $product_id ) );
								        $var .='<tr align="center">
                                                                        <td> <a href="'.$product_link_track.'"> <img src="' . $image_url . '" alt="" height="42" width="42" /> </a></td>
                                                                        <td> <a href="'.$product_link_track.'">'.__( $product_name, "woocommerce-ac" ).'</a></td>
                                                                        <td> '.$quantity_total.'</td>
                                                                        <td> '.get_woocommerce_currency_symbol()."".$item_subtotal.'</td>
                                                                        <td> '.get_woocommerce_currency_symbol()."".$item_total_display.'</td>
                                                                        </tr>';
								        $cart_total += $item_total;
								        $item_subtotal = $item_total = 0;
								    }
								    $cart_total = number_format( $cart_total, 2 );
								    $var .= '<tr align="center">
                                                                <td> </td>
                                                                <td> </td>
                                                                <td> </td>
                                                                <td>'.__( "Cart Total:", "woocommerce-ac" ).'</td>
                                                                <td> '.get_woocommerce_currency_symbol()."".$cart_total.'</td>
                                                                </tr>';
								    $var .= '</table>
                                                                ';
								    $email_body    = str_replace( "{{products.cart}}", $var, $email_body );
								    $email_subject = str_replace( "{{product.name}}", __( $sub_line_prod_name, "woocommerce-ac" ), $email_subject );
								}
								
								if ( $woocommerce->version < '2.3' ) {
								    $cart_page_link	= $woocommerce->cart->get_cart_url();
								} else {
								    $cart_page_id = woocommerce_get_page_id( 'cart' );
								    $cart_page_link = $cart_page_id ? get_permalink( $cart_page_id ) : '';
								}
								
								$encoding_cart = $email_sent_id.'&url='.$cart_page_link;
								
								$validate_cart = $this->encrypt_validate ($encoding_cart);
								$cart_link_track = get_option('siteurl').'/?wacp_action=track_links&validate=' . $validate_cart;
								$email_body	= str_replace( "{{cart.link}}", $cart_link_track, $email_body );
								
								$user_email = $value->user_email;
			
								$email_body_final = stripslashes( $email_body );
								wp_mail( $user_email, $email_subject, __( $email_body_final, 'woocommerce-ac' ), $headers );
																		
							}
			
						}
					}
			
				}
				
			}
			
			/**
			 * get all carts which have the creation time earlier than the one that is passed
			 *
			 */
			function get_carts( $template_to_send_after_time, $cart_abandon_cut_off_time ) {
				
				global $wpdb;
			
				$cart_time = current_time( 'timestamp' ) - $template_to_send_after_time - $cart_abandon_cut_off_time;
			    
				$cart_ignored = 0;
				$query = "SELECT wpac . * , wpu.user_login, wpu.user_email
            			  FROM `".$wpdb->prefix."ac_abandoned_cart_history_lite` AS wpac
            			  LEFT JOIN ".$wpdb->base_prefix."users AS wpu ON wpac.user_id = wpu.id
				            			  WHERE cart_ignored = %s
				            			  AND abandoned_cart_time < $cart_time
				            			  ORDER BY `id` ASC ";
					
				$results = $wpdb->get_results( $wpdb->prepare( $query, $cart_ignored ) );
				
				return $results;
			
				exit;
			}
			
			/******
			*  This function is used to encode the validate string.
			******/
			function encrypt_validate( $validate ) {
			     
			    $cryptKey  = 'qJB0rGtIn5UB1xG03efyCp';
			    $validate_encoded = base64_encode( mcrypt_encrypt( MCRYPT_RIJNDAEL_256, md5( $cryptKey ), $validate, MCRYPT_MODE_CBC, md5( md5( $cryptKey ) ) ) );
			    return $validate_encoded;
			}
			
			function check_sent_history( $user_id, $cart_update_time, $template_id, $id ) {
				
				global $wpdb;
				
				$query = "SELECT wpcs . * , wpac . abandoned_cart_time , wpac . user_id
        				  FROM `".$wpdb->prefix."ac_sent_history_lite` AS wpcs
        				  LEFT JOIN ".$wpdb->prefix."ac_abandoned_cart_history_lite AS wpac ON wpcs.abandoned_order_id =  wpac.id
        				  WHERE
        				  template_id = %s
        				  AND
        				  wpcs.abandoned_order_id = %d
        				  ORDER BY 'id' DESC
        				  LIMIT 1 ";
					
				$results = $wpdb->get_results( $wpdb->prepare( $query, $template_id, $id ) );
				if ( count( $results ) == 0 )
				{
					return true;
				}
				elseif ( $results[0]->abandoned_cart_time < $cart_update_time )
				{
					return true;
				}
				else
				{
					return false;
				}
			
			}
			
			
		}
		
	}
	
	$woocommerce_abandon_cart_cron = new woocommerce_abandon_cart_cron();

	$woocommerce_abandon_cart_cron->woocommerce_ac_send_email();

?>