<?php 

static $wp_load; // Since this will be called twice, hold onto it.
if ( ! isset( $wp_load ) ) {
    $wp_load = false;
    $dir     = __FILE__;
    while( '/' != ( $dir = dirname( $dir ) ) ) {
        if( file_exists( $wp_load = "{$dir}/wp-load.php" ) ) {
            break;
        }
    }
}
$wcal_root = dirname( dirname(__FILE__) ); // go two level up for directory from this file.
require_once $wp_load;
require_once $wcal_root.'/includes/classes/class-wcal-aes.php';
require_once $wcal_root.'/includes/classes/class-wcal-aes-counter.php';
/**
 * woocommerce_abandon_cart_cron class
**/
if ( !class_exists( 'woocommerce_abandon_cart_cron' ) ) {

    class woocommerce_abandon_cart_cron {   
        var $cart_settings_cron;
        var $cart_abandon_cut_off_time_cron;        
        public function __construct() {         
            $this->cart_settings_cron = get_option( 'ac_lite_cart_abandoned_time' );            
            $this->cart_abandon_cut_off_time_cron = ( $this->cart_settings_cron ) * 60;             
        }
        /**
         * Function to send emails
         */
        function wcal_send_email_notification() {   
            global $wpdb, $woocommerce;         
            //Grab the cart abandoned cut-off time from database.
            $cart_settings             = get_option( 'ac_lite_cart_abandoned_time' );           
            $cart_abandon_cut_off_time = $cart_settings * 60;       
            //Fetch all active templates present in the system
            $query        = "SELECT wpet . * FROM `".$wpdb->prefix."ac_email_templates_lite` AS wpet
                             WHERE wpet.is_active = '1' ORDER BY `day_or_hour` DESC, `frequency` ASC ";
            $results      = $wpdb->get_results( $query );       
            $hour_seconds = 3600;   // 60 * 60
            $day_seconds  = 86400; // 24 * 60 * 60
            foreach ( $results as $key => $value ) {
                if ( $value->day_or_hour == 'Days' ) {
                    $time_to_send_template_after = $value->frequency * $day_seconds;
                } elseif ( $value->day_or_hour == 'Hours' ) {
                    $time_to_send_template_after = $value->frequency * $hour_seconds;
                }               
                $carts               = $this->wcal_get_carts( $time_to_send_template_after, $cart_abandon_cut_off_time );
                /**
                 * When there are 3 templates and for cart id 1 all template time has been reached. BUt all templates are deactivated.
                 * If we activate all 3 template then at a 1 time all 3 email templates send to the users.
                 * So below function check that after first email is sent time and then from that time it will send the 2nd template time.  ( It will not consider the cart abadoned time in this case. )
                 */
                $carts               = $this->wcal_remove_cart_for_mutiple_templates( $carts, $time_to_send_template_after, $value->id );                   
                $carts               = woocommerce_abandon_cart_cron ::wcal_update_abandoned_cart_status_for_placed_orders ( $carts, $time_to_send_template_after );
                $email_frequency     = $value->frequency;
                $email_body_template = $value->body;            
                $email_subject       = stripslashes  ( $value->subject );
                $email_subject       = convert_smilies ( $email_subject );
                $wcal_from_name      = get_option ( 'wcal_from_name' );
                $wcal_from_email     = get_option ( 'wcal_from_email' );
                $wcal_reply_email    = get_option ( 'wcal_reply_email' );
                if ( class_exists( 'WP_Better_Emails' ) ) {
                    $headers         = "From: " . $wcal_from_name . " <" . $wcal_from_email . ">" . "\r\n";
                    $headers         .= "Content-Type: text/plain"."\r\n";
                    $headers         .= "Reply-To:  " . $wcal_reply_email . " " . "\r\n";
                } else {
                    $headers         = "From: " . $wcal_from_name . " <" . $wcal_from_email . ">" . "\r\n";
                    $headers         .= "Content-Type: text/html"."\r\n";
                    $headers         .= "Reply-To:  " . $wcal_reply_email . " " . "\r\n";
                }
                $template_id         = $value->id;
                $is_wc_template      = $value->is_wc_template;
                $wc_template_header_text = $value->wc_email_header != '' ? $value->wc_email_header : __( 'Abandoned cart reminder', 'woocommerce-ac');
                $wc_template_header  = stripslashes( $wc_template_header_text );
                if ( '' != $email_body_template ) { 
                    foreach ( $carts as $key => $value ) {
                        if ( $value->user_type == "GUEST" && $value->user_id != '0' ) {
                            $value->user_login = "";
                            $query_guest       = "SELECT billing_first_name, billing_last_name, email_id FROM `".$wpdb->prefix."ac_guest_abandoned_cart_history_lite` 
                                                WHERE id = %d";
                            $results_guest     = $wpdb->get_results( $wpdb->prepare( $query_guest, $value->user_id ) );
                            $value->user_email = $results_guest[0]->email_id;
                        } else {
                            if( isset( $value->user_id ) ) {
                                $user_id            = $value->user_id;
                            }
                            $key                = 'billing_email';
                            $single             = true;
                            $user_biiling_email = get_user_meta( $user_id, $key, $single );                         
                            if ( isset( $user_biiling_email ) && $user_biiling_email != '' ) {
                               $value->user_email = $user_biiling_email;
                            }
                        }
                        if( isset( $value->abandoned_cart_info ) ) {
                           $cart_info_db_field = json_decode( $value->abandoned_cart_info );
                        }
                        if( count( $cart_info_db_field->cart ) > 0 && isset( $value->user_id ) && $value->user_id != '0') {
                            $cart_update_time = $value->abandoned_cart_time;
                            $new_user         = $this->wcal_check_sent_history( $value->user_id, $cart_update_time, $template_id, $value->id );                         
                            if ( $new_user == true ) {
                                $cart_info_db = $value->abandoned_cart_info;
                                $email_body   = $email_body_template;                               
                                 if ( $value->user_type == "GUEST" ) {
                                    if ( isset( $results_guest[0]->billing_first_name ) ) {
                                        $email_body    = str_replace( "{{customer.firstname}}", $results_guest[0]->billing_first_name, $email_body );
                                        $email_subject = str_replace( "{{customer.firstname}}", $results_guest[0]->billing_first_name, $email_subject );
                                    }                           
                                    if ( isset( $results_guest[0]->billing_last_name ) ) {
                                        $email_body = str_replace( "{{customer.lastname}}", $results_guest[0]->billing_last_name, $email_body );
                                    }                               
                                    if ( isset( $results_guest[0]->billing_first_name ) && isset( $results_guest[0]->billing_last_name ) ) {
                                        $email_body = str_replace( "{{customer.fullname}}", $results_guest[0]->billing_first_name." ".$results_guest[0]->billing_last_name, $email_body );
                                    }
                                    else if ( isset( $results_guest[0]->billing_first_name ) ) {
                                        $email_body = str_replace( "{{customer.fullname}}", $results_guest[0]->billing_first_name, $email_body );
                                    }
                                    else if ( isset( $results_guest[0]->billing_last_name ) ) {
                                        $email_body = str_replace( "{{customer.fullname}}", $results_guest[0]->billing_last_name, $email_body );
                                    }
                                } else {
                                    $user_first_name      = '';                                 
                                    $user_first_name_temp = get_user_meta( $value->user_id, 'billing_first_name', true );
                                    if( isset( $user_first_name_temp ) && "" == $user_first_name_temp ) {
                                        $user_data       = get_userdata( $user_id );
                                        $user_first_name = $user_data->first_name;
                                    } else {
                                        $user_first_name = $user_first_name_temp;
                                    }                                   
                                    $email_body          = str_replace( "{{customer.firstname}}", $user_first_name, $email_body );                              
                                    $email_subject       = str_replace( "{{customer.firstname}}", $user_first_name, $email_subject );                               
                                    $user_last_name      = '';
                                    $user_last_name_temp = get_user_meta( $value->user_id, 'billing_last_name', true );
                                    if( isset( $user_last_name_temp ) && "" == $user_last_name_temp ) {
                                        $user_data  = get_userdata( $user_id );
                                        $user_last_name = $user_data->last_name;
                                    } else {
                                        $user_last_name = $user_last_name_temp;
                                    }
                                    $email_body = str_replace( "{{customer.lastname}}", $user_last_name, $email_body );                             
                                    $email_body = str_replace( "{{customer.fullname}}", $user_first_name." ".$user_last_name, $email_body );
                                }                               
                                $order_date  = "";                        
                                if( $cart_update_time != "" && $cart_update_time != 0 ) {
                                	$date_format = date_i18n( get_option( 'date_format' ), $cart_update_time );
            						$time_format = date_i18n( get_option( 'time_format' ), $cart_update_time );
                                    $order_date = $date_format . ' ' . $time_format;
                                }                               
                                $email_body = str_replace( "{{cart.abandoned_date}}", $order_date, $email_body );                               
                                $query_sent = "INSERT INTO `".$wpdb->prefix."ac_sent_history_lite` ( template_id, abandoned_order_id, sent_time, sent_email_id )
                                               VALUES ( %s, %s, '".current_time( 'mysql' )."', %s )";
                                    
                                $wpdb->query( $wpdb->prepare( $query_sent, $template_id, $value->id, $value->user_email ) );
                                
                                $query_id = "SELECT * FROM `".$wpdb->prefix."ac_sent_history_lite` 
                                             WHERE template_id = %s AND abandoned_order_id = %s
                                             ORDER BY id DESC
                                             LIMIT 1 "; 
                                $results_sent  = $wpdb->get_results( $wpdb->prepare( $query_id, $template_id, $value->id ) );                                   
                                $email_sent_id = $results_sent[0]->id;
                                
                                if( $woocommerce->version < '2.3' ) {
                                    $cart_page_link = $woocommerce->cart->get_cart_url();
                                } else {
                                    $cart_page_id   = woocommerce_get_page_id( 'cart' );
                                    $cart_page_link = $cart_page_id ? get_permalink( $cart_page_id ) : '';
                                }
                                
                                $encoding_cart   = $email_sent_id.'&url='.$cart_page_link;
                                $validate_cart   = $this->wcal_encrypt_validate( $encoding_cart );
                                $cart_link_track = get_option('siteurl').'/?wcal_action=track_links&validate=' . $validate_cart;
                                $email_body      = str_replace( "{{cart.link}}", $cart_link_track, $email_body );
                                
                                $validate_unsubscribe          = $this->wcal_encrypt_validate( $email_sent_id );
                                $email_sent_id_address         = $results_sent[0]->sent_email_id;
                                $encrypt_email_sent_id_address = hash( 'sha256', $email_sent_id_address );
                                $plugins_url                   = get_option( 'siteurl' ) . "/?wcal_track_unsubscribe=wcal_unsubscribe&validate=" . $validate_unsubscribe . "&track_email_id=" . $encrypt_email_sent_id_address;
                                $unsubscribe_link_track        = $plugins_url;
                                $email_body                    = str_replace( "{{cart.unsubscribe}}" , $unsubscribe_link_track , $email_body );
                                $var = '';
                                if( preg_match( "{{products.cart}}", $email_body, $matched ) ) {
                                    if ( class_exists( 'WP_Better_Emails' ) ) {
                                        $var = '<table width = 100%>
                                                <tr> <td colspan="5"> <h3>'.__( "Your Shopping Cart", "woocommerce-ac" ).'</h3> </td></tr>
                                                <tr>
                                                <th>'.__( "Item", "woocommerce-ac" ).'</th>
                                                <th>'.__( "Name", "woocommerce-ac" ).'</th>
                                                <th>'.__( "Quantity", "woocommerce-ac" ).'</th>
                                                <th>'.__( "Price", "woocommerce-ac" ).'</th>
                                                <th>'.__( "Line Subtotal", "woocommerce-ac" ).'</th>
                                                </tr>';
                                    } else {
                                        $var = '<h3>'.__( "Your Shopping Cart", "woocommerce-ac" ).'</h3>
                                            <table border="0" cellpadding="10" cellspacing="0" class="templateDataTable">
                                                <tr>
                                                <th>'.__( "Item", "woocommerce-ac" ).'</th>
                                                <th>'.__( "Name", "woocommerce-ac" ).'</th>
                                                <th>'.__( "Quantity", "woocommerce-ac" ).'</th>
                                                <th>'.__( "Price", "woocommerce-ac" ).'</th>
                                                <th>'.__( "Line Subtotal", "woocommerce-ac" ).'</th>
                                                </tr>';
                                    }                   
                                    $cart_details = $cart_info_db_field->cart;
                                    $cart_total = $item_subtotal = $item_total = 0;
                                    $sub_line_prod_name = '';
                                    foreach ( $cart_details as $k => $v ) {
                                        $quantity_total     = $v->quantity;
                                        $product_id         = $v->product_id;
                                        $prod_name          = get_post( $product_id );
                                        $product_link_track = get_permalink( $product_id );
                                        $product_name = $prod_name->post_title;
                                        if( $sub_line_prod_name == '' ) {
                                            $sub_line_prod_name = $product_name;
                                        }
                                        // Item subtotal is calculated as product total including taxes
                                        if( $v->line_subtotal_tax != 0 && $v->line_subtotal_tax > 0 ) {
                                            $item_subtotal = $item_subtotal + $v->line_total + $v->line_subtotal_tax;
                                        } else {
                                            $item_subtotal = $item_subtotal + $v->line_total;
                                        }                               
                                        //  Line total
                                        $item_total         = $item_subtotal;
                                        $item_subtotal      = $item_subtotal / $quantity_total;
                                        $item_total_display = wc_price( $item_total );
                                        $item_subtotal      = wc_price( $item_subtotal );
                                        $product            = wc_get_product( $product_id );
                                        $prod_image         = $product->get_image();
                                        $image_url          = wp_get_attachment_url( get_post_thumbnail_id( $product_id ) );                                        
                                        if ( isset( $v->variation_id ) && '' != $v->variation_id ) {
                                            $variation_id               = $v->variation_id;
                                            $variation                  = wc_get_product( $variation_id );
                                            $name                       = $variation->get_formatted_name() ;
                                            $explode_all                = explode ( "&ndash;", $name );
                                            if( version_compare( $woocommerce->version, '3.0.0', ">=" ) ) {  
                                                    
                                                $attributes         = $explode_all[1];
                                                $explode_attributes = explode( "(#" , $attributes) ;
                                                if (isset($explode_attributes [0])){
                                                    $add_product_name   = $product_name . "," . $explode_attributes[0];
                                                    $pro_name_variation = (array) $add_product_name;
                                                }
                                            }else{
                                                $pro_name_variation         = array_slice( $explode_all, 1, -1 );
                                            }
                                            $product_name_with_variable = '';
                                            $explode_many_varaition     = array();                                           
                                            foreach ( $pro_name_variation as $pro_name_variation_key => $pro_name_variation_value ){
                                                $explode_many_varaition = explode ( ",", $pro_name_variation_value );
                                                if ( !empty( $explode_many_varaition ) ) {
                                                    foreach( $explode_many_varaition as $explode_many_varaition_key => $explode_many_varaition_value ){
                                                        $product_name_with_variable = $product_name_with_variable . "<br>". html_entity_decode ( $explode_many_varaition_value );
                                                    }
                                                } else {
                                                    $product_name_with_variable = $product_name_with_variable . "<br>". html_entity_decode ( $explode_many_varaition_value );
                                                }
                                            }                                            
                                            $product_name = $product_name_with_variable;
                                        }
                                        $var .='<tr align="center">
                                                    <td> <a href="'.$cart_link_track.'"> <img src="' . $image_url . '" alt="" height="42" width="42" /> </a></td>
                                                    <td> <a href="'.$cart_link_track.'">'.__( $product_name, "woocommerce-ac" ).'</a></td>
                                                    <td> '.$quantity_total.'</td>
                                                    <td> '.$item_subtotal.'</td>
                                                    <td> '.$item_total_display.'</td>
                                                </tr>';
                                        $cart_total += $item_total;
                                        $item_subtotal = $item_total = 0;
                                    }
                                    $cart_total = wc_price( $cart_total );
                                    $var .= '<tr align="center">
                                                <td> </td>
                                                <td> </td>
                                                <td> </td>
                                                <td>'.__( "Cart Total:", "woocommerce-ac" ).'</td>
                                                <td> '.$cart_total.'</td>
                                            </tr>';
                                    $var .= '</table>
                                                                ';
                                    $email_body    = str_replace( "{{products.cart}}", $var, $email_body );
                                    $email_subject = str_replace( "{{product.name}}", __( $sub_line_prod_name, "woocommerce-ac" ), $email_subject );
                                }
                                
                                $user_email       = $value->user_email;
                                $email_body_final = stripslashes( $email_body );
                                $email_body_final = convert_smilies( $email_body_final );
                                if ( isset( $is_wc_template ) && "1" == $is_wc_template ){
                                    ob_start();
                                                    
                                    wc_get_template( 'emails/email-header.php', array( 'email_heading' => $wc_template_header ) );
                                    $email_body_template_header = ob_get_clean();
                                
                                    ob_start();
                                
                                    wc_get_template( 'emails/email-footer.php' );  
                                    $email_body_template_footer = ob_get_clean();
                                
                                    $final_email_body =  $email_body_template_header . $email_body_final . $email_body_template_footer;
                                
                                    wc_mail( $user_email, $email_subject, $final_email_body, $headers );
                                
                                } else {
                                    wp_mail( $user_email, $email_subject, __( $email_body_final, 'woocommerce-ac' ), $headers );
                                }                                       
                            }
                        }
                    }
                }
            }                   
        }       
        /**
         * get all carts which have the creation time earlier than the one that is passed
         */
        function wcal_get_carts( $template_to_send_after_time, $cart_abandon_cut_off_time ) {   
            global $wpdb;       
            $cart_time = current_time( 'timestamp' ) - $template_to_send_after_time - $cart_abandon_cut_off_time;           
            $cart_ignored = 0;
            $unsubscribe  = 0;
            $query = "SELECT wpac . * , wpu.user_login, wpu.user_email FROM `".$wpdb->prefix."ac_abandoned_cart_history_lite` AS wpac
                      LEFT JOIN ".$wpdb->base_prefix."users AS wpu ON wpac.user_id = wpu.id
                      WHERE cart_ignored = %s AND unsubscribe_link = %s AND abandoned_cart_time < $cart_time
                      ORDER BY `id` ASC ";
            
            $results = $wpdb->get_results( $wpdb->prepare( $query, $cart_ignored, $unsubscribe ) );         
            return $results;        
            exit;
        }
        
        public static function wcal_update_abandoned_cart_status_for_placed_orders( $carts, $time_to_send_template_after ){
            global $wpdb;
            foreach( $carts as $carts_key => $carts_value ) {
                $abandoned_cart_time = $carts_value->abandoned_cart_time;
                $user_id             = $carts_value->user_id;
                $user_type           = $carts_value->user_type;
                $cart_id             = $carts_value->id;
                if( $user_id >= '63000000' &&  'GUEST' ==  $user_type ) {
                    $query_guest_records = "SELECT id FROM `" . $wpdb->prefix . "ac_guest_abandoned_cart_history_lite` ORDER BY id DESC LIMIT 1";
                    $results_guest_list  = $wpdb->get_results( $query_guest_records );
                    $last_guest_id       = 0 ;
                    $get_last_checked_id = get_option( 'wcal_guest_last_id_checked' );
                    if( isset( $results_guest_list ) && count( $results_guest_list ) > 0 ) {
                        $last_guest_id = $results_guest_list[0]->id;
                    }
                    if( false == $get_last_checked_id || $get_last_checked_id <= $last_guest_id ) {
                        $updated_value = woocommerce_abandon_cart_cron ::wcal_update_status_of_guest( $cart_id, $abandoned_cart_time , $time_to_send_template_after );
                        if( 1 == $updated_value ) {
                            unset ( $carts [ $carts_key ] );
                        }
                        update_option ( 'wcal_guest_last_id_checked' , $last_guest_id );
                    }
                } elseif ( $user_id < '63000000' &&  'REGISTERED' ==  $user_type ) {
                    $updated_value = woocommerce_abandon_cart_cron ::wcal_update_status_of_loggedin ( $cart_id, $abandoned_cart_time , $time_to_send_template_after );
                    if( 1 == $updated_value ) {
                        unset( $carts [ $carts_key ] );
                    }
                }
            }
            return $carts;
        }
        
        public static function wcal_update_status_of_guest( $cart_id, $abandoned_cart_time , $time_to_send_template_after ) {
            global $wpdb;
            $get_last_checked_id = get_option( 'wcal_guest_last_id_checked' );

            $query_guest_records = "SELECT wacgh.id, wacgh.email_id FROM `" . $wpdb->prefix . "ac_guest_abandoned_cart_history_lite` as wacgh LEFT JOIN " . $wpdb->prefix . "ac_abandoned_cart_history_lite AS wpac ON wacgh.id =  wpac.user_id WHERE wpac.id = $cart_id ";       
            $results_guest_list  = $wpdb->get_results( $query_guest_records );
            // This is to ensure that recovered guest carts r removed from the delete list
            
            if ( count( $results_guest_list ) > 0 ) {

                $wcal_user_email_address = $results_guest_list[0]->email_id;
                $current_time     = current_time( 'timestamp' );
                $todays_date      = date( 'Y-m-d', $current_time );
                $query_email_id   = "SELECT wpm.post_id, wpost.post_date, wpost.post_status  FROM `" . $wpdb->prefix . "postmeta` AS wpm LEFT JOIN `" . $wpdb->prefix . "posts` AS wpost ON wpm.post_id =  wpost.ID WHERE wpm.meta_key = '_billing_email' AND wpm.meta_value = %s AND wpm.post_id = wpost.ID Order BY wpm.post_id DESC LIMIT 1";

                $results_query_email = $wpdb->get_results( $wpdb->prepare( $query_email_id, $wcal_user_email_address  ) );

                /* This will check that For abc@abc.com email address we have order for todays date in WC post table */
                if ( count( $results_query_email ) > 0 ) {
                    $order_date_with_time = $results_query_email[0]->post_date;
                    $order_date           = substr( $order_date_with_time, 0, 10 );
                    
                    if ( $order_date == $todays_date ) {
                        $query_ignored = "UPDATE `" . $wpdb->prefix . "ac_abandoned_cart_history_lite` SET cart_ignored = '1' WHERE id ='" . $cart_id . "'";
                        $wpdb->query( $query_ignored );   
                        return 1;
                        } else if ( strtotime( $order_date_with_time ) > $abandoned_cart_time ) {
                            $query_ignored = "UPDATE `" . $wpdb->prefix . "ac_abandoned_cart_history_lite` SET cart_ignored = '1' WHERE id ='" . $cart_id . "'";
                            $wpdb->query( $query_ignored );
                            return 1;
                        } else if( $results_query_email[0]->post_status == "wc-pending" || $results_query_email[0]->post_status == "wc-failed" ) {
                            
                            /*  If the post status are pending or failed  the send them for abandoned cart reminder emails */
                            return 0;
                        }
                    
                    } 
                } 
            return 0;           
        }
        
        public static function wcal_update_status_of_loggedin( $cart_id, $abandoned_cart_time , $time_to_send_template_after ) {
            global $wpdb;    
            // Update the record of the loggedin user who had paid before the first abandoned cart reminder email send to customer.
            $query_records = "SELECT DISTINCT user_id FROM `" . $wpdb->prefix . "ac_abandoned_cart_history_lite` WHERE user_type = 'REGISTERED' AND id = $cart_id AND recovered_cart = '0'";
            $results_list  = $wpdb->get_results( $query_records );
            foreach( $results_list as $key => $value ) {    
                $user_id            = $value->user_id;
                $key                = 'billing_email';
                $single             = true;
                $user_billing_email = get_user_meta( $user_id, $key, $single );    
                if( isset( $user_billing_email ) && $user_billing_email == '' ) {
                    $user_id        = $value->user_id;
                    if( is_multisite() ) {
                        $main_prefix = $wpdb->get_blog_prefix(1);
                        $query_email = "SELECT user_email FROM `".$main_prefix."users` WHERE ID = %d";                     
                    } else {
                        $query_email = "SELECT user_email FROM `".$wpdb->prefix."users` WHERE ID = %d";
                    }
                    $results_email       = $wpdb->get_results( $wpdb->prepare( $query_email, $user_id ) );
                    if( !empty( $results_email[0]->user_email ) ) {
                        $user_billing_email  = $results_email[0]->user_email;
                    }
                }    
                $query_email_id      = "SELECT post_id FROM `" . $wpdb->prefix . "postmeta` WHERE meta_key = '_billing_email' AND meta_value = %s";
                $results_query_email = $wpdb->get_results( $wpdb->prepare( $query_email_id, $user_billing_email ) );    
                //if any orders are found with the same email addr..delete those ac records
                if( is_array( $results_query_email ) && count( $results_query_email ) > 0 ) {
                    for( $i = 0; $i < count( $results_query_email ); $i++ ) {    
                        $cart_abandoned_time = date ('Y-m-d h:i:s', $abandoned_cart_time);    
                        $query_post          = "SELECT post_date,post_status FROM `" . $wpdb->prefix . "posts` WHERE ID = %d AND post_date >= %s";
                        $results_post        = $wpdb->get_results( $wpdb->prepare( $query_post, $results_query_email[ $i ]->post_id, $cart_abandoned_time ) );    
                        if( count ($results_post) > 0 ) {
                            $current_time     = current_time( 'timestamp' );
                            $todays_date      = date( 'Y-m-d', $current_time );                            
                            $order_date_time = $results_post[0]->post_date;
                            $order_date      = substr( $order_date_time, 0, 10 );

                            if ( $order_date == $todays_date ) {
                                $query_ignored = "UPDATE `" . $wpdb->prefix . "ac_abandoned_cart_history_lite` SET cart_ignored = '1' WHERE id ='" . $cart_id . "'";
                                $wpdb->query( $query_ignored );                               
                                return 1;
                            } else if ( strtotime( $order_date_time ) >=  $abandoned_cart_time ) {    
                                $query_ignored = "UPDATE `" . $wpdb->prefix . "ac_abandoned_cart_history_lite` SET cart_ignored = '1' WHERE id ='" . $cart_id . "'";
                                $wpdb->query( $query_ignored );
                                return 1; //We return here 1 so it indicate that the cart has been modifed so do not sent email and delete from the array.
                            } else if( $results_post[0]->post_status == "wc-pending" || $results_post[0]->post_status == "wc-failed" ) {
                                return 0; //if status of the order is pending or falied then return 0 so it will not delete that cart and send reminder email
                            }                            
                        }
                    }
                }
            }
            return 0;
        }

        
        public static function wcal_remove_cart_for_mutiple_templates( $carts, $time_to_send_template_after, $template_id ) {
            global $wpdb;
            
            foreach( $carts as $carts_key => $carts_value ) {       
                $wcal_get_last_email_sent_time               = "SELECT * FROM `" . $wpdb->prefix . "ac_sent_history_lite` WHERE abandoned_order_id = $carts_value->id ORDER BY `sent_time` DESC LIMIT 1";
                $wcal_get_last_email_sent_time_results_list  = $wpdb->get_results( $wcal_get_last_email_sent_time );        
                if( count( $wcal_get_last_email_sent_time_results_list ) > 0 ) {
                    $last_template_send_time  = strtotime( $wcal_get_last_email_sent_time_results_list[0]->sent_time );
                    $second_template_send_time = $last_template_send_time + $time_to_send_template_after ;
                    $current_time_test         = current_time( 'timestamp' );       
                    if( $second_template_send_time > $current_time_test ) {
                        unset( $carts [ $carts_key ] );
                    }
                }
            }
            return $carts;
        }
        /******
        *  This function is used to encode the validate string.
        ******/
        function wcal_encrypt_validate( $validate ) {            
            $cryptKey         = get_option( 'wcal_security_key' );        
            $validate_encoded = Wcal_Aes_Ctr::encrypt( $validate, $cryptKey, 256 );
            return( $validate_encoded );
        }
        
        function wcal_check_sent_history( $user_id, $cart_update_time, $template_id, $id ) {            
            global $wpdb;           
            $query = "SELECT wpcs . * , wpac . abandoned_cart_time , wpac . user_id FROM `".$wpdb->prefix."ac_sent_history_lite` AS wpcs
                      LEFT JOIN ".$wpdb->prefix."ac_abandoned_cart_history_lite AS wpac ON wpcs.abandoned_order_id =  wpac.id
                      WHERE template_id = %s AND wpcs.abandoned_order_id = %d ORDER BY 'id' DESC LIMIT 1 ";
                
            $results = $wpdb->get_results( $wpdb->prepare( $query, $template_id, $id ) );
            if ( count( $results ) == 0 ) {
                return true;
            } elseif ( $results[0]->abandoned_cart_time < $cart_update_time ) {
                return true;
            } else {
                return false;
            }       
        }       
    }   
}   
$woocommerce_abandon_cart_cron = new woocommerce_abandon_cart_cron();
$woocommerce_abandon_cart_cron->wcal_send_email_notification();

?>