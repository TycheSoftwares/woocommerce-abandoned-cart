<?php
/**
 * It will send the automatic reminder emails to the customers.
 *
 * @author  Tyche Softwares
 * @package Abandoned-Cart-Lite-for-WooCommerce/Cron
 */


static $wp_load; // Since this will be called twice, hold onto it.
if ( ! isset( $wp_load ) ) {
    $wp_load = false;
    $dir     = __FILE__;
    while( '/' != ( $dir = dirname( $dir ) ) ) {
        /**
         * Comment this "If" condition for generating the developer documentations.
         */
        if( file_exists( $wp_load = "{$dir}/wp-load.php" ) ) {
            break;
        }
    }
    /*
     * In case wp-content folder is seperated from WP core folders (like Bedrock setup from Roots.io) the above while loop will not find wp-load correctly, so we must use ABSPATH
     */
    if ( ! file_exists( $wp_load ) ) {
        $wp_load = trailingslashit( ABSPATH ) . 'wp-load.php';
    }
}
$wcal_root = dirname( dirname(__FILE__) ); // go two level up for directory from this file.
require_once $wp_load;
require_once $wcal_root.'/includes/classes/class-wcal-aes.php';
require_once $wcal_root.'/includes/classes/class-wcal-aes-counter.php';


if ( !class_exists( 'woocommerce_abandon_cart_cron' ) ) {

    /**
     * It will send the automatic reminder emails to the customers
     * @since 1.3
     */
    class woocommerce_abandon_cart_cron {
        var $cart_settings_cron;
        var $cart_abandon_cut_off_time_cron;
        public function __construct() {
            $this->cart_settings_cron = get_option( 'ac_lite_cart_abandoned_time' );
            $this->cart_abandon_cut_off_time_cron = ( $this->cart_settings_cron ) * 60;
        }
        /**
         * It will send the reminder emails to the cutomers.
         * It will also replace the merge code to its original data.
         * it will also check if guest id is valid, and check if the order is placed before sending the reminder email.
         * @globals mixed $wpdb
         * @globals mixed $woocommerce
         * @since 1.3
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
                
                $carts               = $this->wcal_get_carts( $time_to_send_template_after, $cart_abandon_cut_off_time, $value->id );
                $email_frequency     = $value->frequency;
                $email_body_template = $value->body;
                $template_email_subject       = stripslashes  ( $value->subject );
                $template_email_subject       = convert_smilies ( $template_email_subject );
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
                $wc_template_header_text = $value->wc_email_header != '' ? $value->wc_email_header : __( 'Abandoned cart reminder', 'woocommerce-abandoned-cart');
                $wc_template_header  = stripslashes( $wc_template_header_text );
                if ( '' != $email_body_template ) {
                    foreach ( $carts as $key => $value ) {

                        $wcal_is_guest_id_correct = $this->wcal_get_is_guest_valid ( $value->user_id, $value->user_type ) ;
                        if ( true === $wcal_is_guest_id_correct ) {

                            if ( $value->user_type == "GUEST" && $value->user_id != '0' ) {
                                $value->user_login = "";
                                $query_guest       = "SELECT billing_first_name, billing_last_name, email_id FROM `".$wpdb->prefix."ac_guest_abandoned_cart_history_lite`
                                                    WHERE id = %d";
                                $results_guest     = $wpdb->get_results( $wpdb->prepare( $query_guest, $value->user_id ) );
                                if ( count( $results_guest ) > 0 && isset( $results_guest[0]->email_id ) ) {
                                    $value->user_email = $results_guest[0]->email_id;
                                }
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
                            $cart = new stdClass();
                            if( !empty( $cart_info_db_field->cart ) ) {
                                $cart           = $cart_info_db_field->cart;
                            }
                            if( count( get_object_vars( $cart ) ) > 0 && isset( $value->user_id ) && '0' != $value->user_id && isset( $value->id ) ) {
                                $cart_update_time = $value->abandoned_cart_time;
                                $new_user         = $this->wcal_check_sent_history( $value->user_id, $cart_update_time, $template_id, $value->id );
                                if ( $new_user == true ) {

                                 /**
                                  * When there are 3 templates and for cart id 1 all template time has been reached. BUt all templates
                                  * are deactivated.
                                  * If we activate all 3 template then at a 1 time all 3 email templates send to the users.
                                  * So below function check that after first email is sent time and then from that time it will send the
                                  * 2nd template time.  ( It will not consider the cart abadoned time in this case. )
                                  */

                                $wcal_check_cart_needed_for_multiple_template = $this->wcal_remove_cart_for_mutiple_templates( $value->id, $time_to_send_template_after, $template_id );

                                /**
                                 * When we click on the place order button, we check if the order is placed after the
                                 * cut off time. And if yes then if the status of the order is pending or falied then
                                 * we keep it as the abandonoed and we need to send reminder emails. So in below function
                                 * we first check if any order is placed with todays date then we do not send the
                                 * reminder email. But what if placed order status is pending or falied? So this
                                 * condition will not call that function andthe reminder email will be sent.
                                 */

                                $wcal_check_if_cart_is_present_in_post_meta   = "SELECT wpm.post_id, wpost.post_date, wpost.post_status  FROM `" . $wpdb->prefix . "postmeta` AS wpm
                                    LEFT JOIN `" . $wpdb->prefix . "posts` AS wpost
                                    ON wpm.post_id = wpost.ID
                                    WHERE wpm.meta_key = 'wcal_recover_order_placed' AND
                                    wpm.meta_value = %s AND wpm.post_id = wpost.ID AND
                                    wpost.post_type = 'shop_order'
                                    ORDER BY wpm.post_id   DESC LIMIT 1";

                                $results_wcal_check_if_cart_is_present_in_post_meta = $wpdb->get_results( $wpdb->prepare( $wcal_check_if_cart_is_present_in_post_meta, $value->id  ) );

                                $wcap_check_cart_staus_need_to_update = false;

                                if ( empty ($results_wcal_check_if_cart_is_present_in_post_meta) ||
                                ( isset( $results_wcal_check_if_cart_is_present_in_post_meta[0] ) &&
                                    $results_wcal_check_if_cart_is_present_in_post_meta[0]->post_status != "wc-failed" &&
                                    $results_wcal_check_if_cart_is_present_in_post_meta [0]->post_status != "wc-pending" ) ){

                                    $wcap_check_cart_staus_need_to_update = woocommerce_abandon_cart_cron::wcal_update_abandoned_cart_status_for_placed_orders ( $time_to_send_template_after,  $cart_update_time, $value->user_id, $value->user_type, $value->id, $value->user_email );
                                }

                                if ( false == $wcal_check_cart_needed_for_multiple_template &&
                                     false == $wcap_check_cart_staus_need_to_update ) {

                                    $cart_info_db = $value->abandoned_cart_info;
                                    $email_subject = $template_email_subject;
                                    $email_body   = $email_body_template;
                                    $wcal_check_cart_total = $this->wcal_check_cart_total( $cart );
                                    if( true == $wcal_check_cart_total ) {
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
                                                if ( isset( $user_data->first_name ) ) {
                                                    $user_first_name = $user_data->first_name;
                                                } else {
                                                    $user_first_name = '';
                                                }
                                            } else {
                                                $user_first_name = $user_first_name_temp;
                                            }
                                            $email_body          = str_replace( "{{customer.firstname}}", $user_first_name, $email_body );
                                            $email_subject       = str_replace( "{{customer.firstname}}", $user_first_name, $email_subject );
                                            $user_last_name      = '';
                                            $user_last_name_temp = get_user_meta( $value->user_id, 'billing_last_name', true );
                                            if( isset( $user_last_name_temp ) && "" == $user_last_name_temp ) {
                                                $user_data  = get_userdata( $user_id );
                                                if ( isset( $user_data->last_name) ) {
                                                    $user_last_name = $user_data->last_name;
                                                } else {
                                                    $user_last_name = '';
                                                }
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
                                        if ( count( $results_sent ) > 0 ) {
                                            $email_sent_id = $results_sent[0]->id;
                                        } else {
                                            $email_sent_id = '';
                                        }

                                        if ( '' != $email_sent_id ) {

                                            if( $woocommerce->version < '2.3' ) {
                                                $cart_page_link = $woocommerce->cart->get_cart_url();
                                            } else {
                                                $cart_page_id   = wc_get_page_id( 'cart' );
                                                $cart_page_link = $cart_page_id ? get_permalink( $cart_page_id ) : '';
                                            }

                                            $encoding_cart   = $email_sent_id.'&url='.$cart_page_link;
                                            $validate_cart   = $this->wcal_encrypt_validate( $encoding_cart );
                                            $cart_link_track = get_option('siteurl').'/?wcal_action=track_links&validate=' . $validate_cart;
                                            $email_body      = str_replace( "{{cart.link}}", $cart_link_track, $email_body );

                                            $validate_unsubscribe          = $this->wcal_encrypt_validate( $email_sent_id );
                                            if ( count( $results_sent ) > 0 && isset( $results_sent[0]->sent_email_id ) ) {
                                                $email_sent_id_address         = $results_sent[0]->sent_email_id;
                                            }
                                            $encrypt_email_sent_id_address = hash( 'sha256', $email_sent_id_address );
                                            $plugins_url                   = get_option( 'siteurl' ) . "/?wcal_track_unsubscribe=wcal_unsubscribe&validate=" . $validate_unsubscribe . "&track_email_id=" . $encrypt_email_sent_id_address;
                                            $unsubscribe_link_track        = $plugins_url;
                                            $email_body                    = str_replace( "{{cart.unsubscribe}}" , $unsubscribe_link_track , $email_body );
                                            $var = '';
                                            if( preg_match( "{{products.cart}}", $email_body, $matched ) ) {
                                                if ( class_exists( 'WP_Better_Emails' ) ) {
                                                    $var = '<table width = 100%>
                                                            <tr> <td colspan="5"> <h3>'.__( "Your Shopping Cart", "woocommerce-abandoned-cart" ).'</h3> </td></tr>
                                                            <tr>
                                                            <th>'.__( "Item", "woocommerce-abandoned-cart" ).'</th>
                                                            <th>'.__( "Name", "woocommerce-abandoned-cart" ).'</th>
                                                            <th>'.__( "Quantity", "woocommerce-abandoned-cart" ).'</th>
                                                            <th>'.__( "Price", "woocommerce-abandoned-cart" ).'</th>
                                                            <th>'.__( "Line Subtotal", "woocommerce-abandoned-cart" ).'</th>
                                                            </tr>';
                                                } else {
                                                    $var = '<h3>'.__( "Your Shopping Cart", "woocommerce-abandoned-cart" ).'</h3>
                                                        <table border="0" cellpadding="10" cellspacing="0" class="templateDataTable">
                                                            <tr>
                                                            <th>'.__( "Item", 'woocommerce-abandoned-cart' ).'</th>
                                                            <th>'.__( "Name", 'woocommerce-abandoned-cart' ).'</th>
                                                            <th>'.__( "Quantity", 'woocommerce-abandoned-cart' ).'</th>
                                                            <th>'.__( "Price", 'woocommerce-abandoned-cart' ).'</th>
                                                            <th>'.__( "Line Subtotal", 'woocommerce-abandoned-cart' ).'</th>
                                                            </tr>';
                                                }
                                                $cart_details = $cart_info_db_field->cart;
                                                $cart_total = $item_subtotal = $item_total = 0;
                                                $sub_line_prod_name = '';
                                                foreach ( $cart_details as $k => $v ) {
                                                    $quantity_total     = $v->quantity;
                                                    $product_id         = $v->product_id;
                                                    $product            = wc_get_product( $product_id );
                                                    if ( $product ) {
                                                        $prod_name          = get_post( $product_id );
                                                        $product_link_track = get_permalink( $product_id );
                                                        $product_name = $prod_name->post_title;
                                                        if( $sub_line_prod_name == '' ) {
                                                            $sub_line_prod_name = $product_name;
                                                        }
                                                        // Item subtotal is calculated as product total including taxes
                                                        if( $v->line_tax != 0 && $v->line_tax > 0 ) {
                                                            $item_subtotal = $item_subtotal + $v->line_total + $v->line_tax;
                                                        } else {
                                                            $item_subtotal = $item_subtotal + $v->line_total;
                                                        }
                                                        //  Line total
                                                        $item_total         = $item_subtotal;
                                                        $item_subtotal      = $item_subtotal / $quantity_total;
                                                        $item_total_display = wc_price( $item_total );
                                                        $item_subtotal      = wc_price( $item_subtotal );
                                                        //$product            = wc_get_product( $product_id );
                                                        $prod_image         = $product->get_image();
                                                        $image_url          = wp_get_attachment_url( get_post_thumbnail_id( $product_id ) );
                                                        if ( strpos( $image_url, '/' ) === 0 ) {
                                                            $image_url = get_option('siteurl') . $image_url;
                                                        }
                                                        if ( isset( $v->variation_id ) && '' != $v->variation_id ) {
                                                            $variation_id               = $v->variation_id;
                                                            $variation                  = wc_get_product( $variation_id );
                                                            $name                       = $variation->get_formatted_name() ;
                                                            $explode_all                = explode ( "&ndash;", $name );
                                                            if( version_compare( $woocommerce->version, '3.0.0', ">=" ) ) {
                                                                $wcap_sku = '';
                                                                if ( $variation->get_sku() ) {
                                                                    $wcap_sku = "SKU: " . $variation->get_sku() . "<br>";
                                                                }
                                                                $wcap_get_formatted_variation  =  wc_get_formatted_variation( $variation, true );

                                                                $add_product_name = $product_name . ' - ' . $wcap_sku . $wcap_get_formatted_variation;

                                                                $pro_name_variation = (array) $add_product_name;
                                                            }else{
                                                                $pro_name_variation = array_slice( $explode_all, 1, -1 );
                                                            }
                                                            $product_name_with_variable = '';
                                                            $explode_many_varaition     = array();
                                                            foreach( $pro_name_variation as $pro_name_variation_key => $pro_name_variation_value ) {
                                                            $explode_many_varaition = explode ( ",", $pro_name_variation_value );
                                                                if( !empty( $explode_many_varaition ) ) {
                                                                    foreach( $explode_many_varaition as $explode_many_varaition_key => $explode_many_varaition_value ) {
                                                                        $product_name_with_variable = $product_name_with_variable .  html_entity_decode ( $explode_many_varaition_value ) . "<br>";
                                                                    }
                                                                } else {
                                                                    $product_name_with_variable = $product_name_with_variable .  html_entity_decode ( $explode_many_varaition_value ) . "<br>";
                                                                }
                                                            }
                                                            $product_name = $product_name_with_variable;
                                                        }
                                                        $var .='<tr align="center">
                                                                <td> <a href="'.$cart_link_track.'"> <img src="' . $image_url . '" alt="" height="42" width="42" /> </a></td>
                                                                <td> <a href="'.$cart_link_track.'">'.__( $product_name, 'woocommerce-abandoned-cart' ).'</a></td>
                                                                <td> '.$quantity_total.'</td>
                                                                <td> '.$item_subtotal.'</td>
                                                                <td> '.$item_total_display.'</td>
                                                            </tr>';
                                                        $cart_total += $item_total;
                                                        $item_subtotal = $item_total = 0;
                                                        $p_exists = true;
                                                    }else {
                                                        $cart_total = 0;
                                                        $item_subtotal = $item_total = 0;

                                                        $p_exists = false;
                                                    }
                                                }

                                                if ( $p_exists ) {
                                                    $cart_total = wc_price( $cart_total );
                                                    $var .= '<tr align="center">
                                                            <td> </td>
                                                            <td> </td>
                                                            <td> </td>
                                                            <td>'.__( "Cart Total:", 'woocommerce-abandoned-cart' ).'</td>
                                                            <td> '.$cart_total.'</td>
                                                        </tr>';
                                                    $var .= '</table>
                                                                            ';
                                                    $email_body    = str_replace( "{{products.cart}}", $var, $email_body );
                                                    $email_subject = str_replace( "{{product.name}}", __( $sub_line_prod_name, 'woocommerce-abandoned-cart' ), $email_subject );
                                                }else {
                                                    $email_body    = str_replace( "{{products.cart}}", 'Product no longer exists', $email_body );
                                                    $email_subject = str_replace( "{{product.name}}", __( $sub_line_prod_name, 'woocommerce-abandoned-cart' ), $email_subject );
                                                }
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

                                                    $site_title = get_bloginfo( 'name' );
                                                    $email_body_template_footer     = str_replace( '{site_title}', $site_title, $email_body_template_footer );

                                                    $final_email_body =  $email_body_template_header . $email_body_final . $email_body_template_footer;

                                                    wc_mail( $user_email, $email_subject, $final_email_body, $headers );

                                                } else {
                                                    wp_mail( $user_email, $email_subject, __( $email_body_final, 'woocommerce-abandoned-cart' ), $headers );
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        /**
         * This function will check if the user type is Guest and the id is greater than 63000000
         * Then conider that as a correct guest user, if is not then do not send the emails
         * @param strinng | int $wcal_user_id User id
         * @param strinng $wcal_user_type User Type
         * @return boolean true | false
         * @since 4.4
         */
        public static function wcal_get_is_guest_valid ( $wcal_user_id, $wcal_user_type ) {

            if ( 'REGISTERED' == $wcal_user_type ){
                return true;
            }

            if ( 'GUEST' == $wcal_user_type && $wcal_user_id >= 63000000 ) {
                return true;
            }

            /**
             * It indicates that the user type is guest but the id for them is wrong.
             */
            return false;
        }

        /**
         * It will check the cart total.
         * @param array | object $cart Cart details
         * @return boolean true | false
         * @since 4.3
         */
        function wcal_check_cart_total ( $cart ){
            foreach( $cart as $k => $v ) {
                if( $v->line_total != 0 && $v->line_total > 0 ) {
                    return true;
                }
            }
            return false;
        }
        /**
         * Get all carts which have the creation time earlier than the one that is passed.
         * @param string | timestamp $template_to_send_after_time Template time
         * @param string | timestamp $cart_abandon_cut_off_time Cutoff time
         * @globals mixed $wpdb
         * @return array | object $results
         * @since 1.3
         */
        function wcal_get_carts( $template_to_send_after_time, $cart_abandon_cut_off_time, $template_id ) {
            global $wpdb;
            $cart_time = current_time( 'timestamp' ) - $template_to_send_after_time - $cart_abandon_cut_off_time;

            $wcal_template_time = get_option( 'wcal_template_' . $template_id . '_time' );
            $wcal_add_template_condition = '';
            if ( $wcal_template_time > 0 ) {
                $wcal_add_template_condition = ' AND abandoned_cart_time > ' . $wcal_template_time;
            }
            $cart_ignored = 0;
            $unsubscribe  = 0;
            $query = "SELECT wpac . * , wpu.user_login, wpu.user_email 
                FROM `".$wpdb->prefix."ac_abandoned_cart_history_lite` AS wpac
                LEFT JOIN ".$wpdb->base_prefix."users AS wpu ON wpac.user_id = wpu.id
                WHERE cart_ignored = %s AND unsubscribe_link = %s AND abandoned_cart_time < $cart_time
                $wcal_add_template_condition
                ORDER BY `id` ASC ";

            $results = $wpdb->get_results( $wpdb->prepare( $query, $cart_ignored, $unsubscribe ) );
            return $results;
            exit;
        }

        /**
         * It will update the abandoned cart staus if the order has been placed before sending the reminder emails.
         * @param string | timestamp $time_to_send_template_after Template time
         * @param string | timestamp $wcal_cart_time Abandoned time
         * @param string | int $wcal_user_id User id
         * @param string  $wcal_user_type User type
         * @param string | int $wcal_cart_id Abandoned cart id
         * @param string  $wcal_user_email User Email
         * @globals mixed $wpdb
         * @return boolean true | false
         * @since 4.3
         */
        public static function wcal_update_abandoned_cart_status_for_placed_orders( $time_to_send_template_after, $wcal_cart_time, $wcal_user_id, $wcal_user_type, $wcal_cart_id, $wcal_user_email ){
            global $wpdb;

            if( $wcal_user_id >= '63000000' &&  'GUEST' ==  $wcal_user_type ) {
                $updated_value = woocommerce_abandon_cart_cron::wcal_update_status_of_guest( $wcal_cart_id, $wcal_cart_time , $time_to_send_template_after, $wcal_user_email );
                if( 1 == $updated_value ) {
                    return true;
                }
            } elseif ( $wcal_user_id < '63000000' && 'REGISTERED' == $wcal_user_type ) {
                $updated_value = woocommerce_abandon_cart_cron::wcal_update_status_of_loggedin ( $wcal_cart_id, $wcal_cart_time , $time_to_send_template_after, $wcal_user_email );
                if( 1 == $updated_value ) {
                    return true;
                }
            }
            return false;
        }

        /**
         * It will update the Guest users abandoned cart staus if the order has been placed before sending the reminder emails.
         * @param string | int $cart_id Abandoned cart id
         * @param string | timestamp $time_to_send_template_after Template time
         * @param string | timestamp $abandoned_cart_time Abandoned time
         * @param string  $wcal_user_email_address User Email
         * @globals mixed $wpdb
         * @return int 0 | 1
         * @since 4.3
         */
        public static function wcal_update_status_of_guest ( $cart_id, $abandoned_cart_time , $time_to_send_template_after, $wcal_user_email_address ) {
            global $wpdb;

            $query_email_id   = "SELECT wpm.post_id, wpost.post_date, wpost.post_status  FROM `" . $wpdb->prefix . "postmeta` AS wpm LEFT JOIN `" . $wpdb->prefix . "posts` AS wpost ON wpm.post_id = wpost.ID
                    WHERE wpm.meta_key = '_billing_email' AND wpm.meta_value = %s AND wpm.post_id = wpost.ID AND wpost.post_type = 'shop_order' Order BY wpm.post_id   DESC LIMIT 1";

            $results_query_email = $wpdb->get_results( $wpdb->prepare( $query_email_id, $wcal_user_email_address  ) );

            /* This will check that For abc@abc.com email address we have order for todays date in WC post table */
            if ( count( $results_query_email ) > 0 ) {

                $current_time     = current_time( 'timestamp' );
                $todays_date      = date( 'Y-m-d', $current_time );

                $order_date_with_time = $results_query_email[0]->post_date;
                $order_date           = substr( $order_date_with_time, 0, 10 );

                if ( $order_date == $todays_date ) {

                    /**
                     * in some case the cart is recovered but it is not marked as the recovered. So here we check if any
                     * record is found for that cart id if yes then update the record respectively.
                     */
                    $wcal_check_email_sent_to_cart = woocommerce_abandon_cart_cron::wcal_get_cart_sent_data ( $cart_id );

                    if ( 0 !=  $wcal_check_email_sent_to_cart ) {

                        $wcal_query   = "SELECT `post_id` FROM `" . $wpdb->prefix . "postmeta` WHERE  meta_value = %s";
                        $wcal_results = $wpdb->get_results ( $wpdb->prepare( $wcal_query , $cart_id ) );

                        if ( count( $wcal_results ) > 0 ) {

                            $order_id = $wcal_results[0]->post_id;

                            $order    = wc_get_order( $order_id );

                            $query_order = "UPDATE `" . $wpdb->prefix . "ac_abandoned_cart_history_lite` SET recovered_cart= %s, cart_ignored = '1' WHERE id = %s";
                            $wpdb->query( $wpdb->prepare( $query_order, $order_id, $cart_id ) );

                            $order->add_order_note( __( 'This order was abandoned & subsequently recovered.', 'woocommerce-abandoned-cart' ) );

                            delete_post_meta( $order_id,  'wcal_recover_order_placed',         $cart_id );
                            delete_post_meta( $order_id , 'wcal_recover_order_placed_sent_id', $wcal_check_email_sent_to_cart );
                        }
                    }else{

                        $query_ignored = "UPDATE `" . $wpdb->prefix."ac_abandoned_cart_history_lite` SET cart_ignored = '1' WHERE id = %s";
                        $wpdb->query( $wpdb->prepare( $query_ignored, $cart_id ) );
                    }
                    return 1;
                }else if ( strtotime( $order_date_with_time ) > $abandoned_cart_time ) {
                    $query_ignored = "UPDATE `" . $wpdb->prefix."ac_abandoned_cart_history_lite` SET cart_ignored = '1' WHERE id = %s ";
                    $wpdb->query( $wpdb->prepare( $query_ignored, $cart_id ) );
                    return 1;
                } else if( "wc-pending" == $results_query_email[0]->post_status || "wc-failed" == $results_query_email[0]->post_status ) {

                    /**
                     * If the post status are pending or failed  the send them for abandoned cart reminder emails.
                     */
                    return 0;
                }
            }
            return 0;
        }

        /**
         * It will update the Loggedin users abandoned cart staus if the order has been placed before sending the reminder emails.
         * @param string | int $cart_id Abandoned cart id
         * @param string | timestamp $time_to_send_template_after Template time
         * @param string | timestamp $abandoned_cart_time Abandoned time
         * @param string  $user_billing_email User Email
         * @globals mixed $wpdb
         * @return int 0 | 1
         * @since 4.3
         */
        public static function wcal_update_status_of_loggedin( $cart_id, $abandoned_cart_time , $time_to_send_template_after, $user_billing_email ) {
            global $wpdb;

            $query_email_id   = "SELECT wpm.post_id, wpost.post_date, wpost.post_status  FROM `" . $wpdb->prefix . "postmeta` AS wpm LEFT JOIN `" . $wpdb->prefix . "posts` AS wpost ON wpm.post_id =  wpost.ID WHERE wpm.meta_key = '_billing_email' AND wpm.meta_value = %s AND wpm.post_id = wpost.ID Order BY wpm.post_id DESC LIMIT 1";

            $results_query_email = $wpdb->get_results( $wpdb->prepare( $query_email_id, $user_billing_email  ) );

            if ( count ( $results_query_email ) > 0 ) {
                $current_time     = current_time( 'timestamp' );
                $todays_date      = date( 'Y-m-d', $current_time );
                $order_date_time  = $results_query_email[0]->post_date;
                $order_date       = substr( $order_date_time, 0, 10 );

                if ( $order_date == $todays_date ){

                    $wcal_check_email_sent_to_cart = woocommerce_abandon_cart_cron::wcal_get_cart_sent_data ( $cart_id );

                    if ( 0 != $wcal_check_email_sent_to_cart ) {

                        $wcal_query   = "SELECT `post_id` FROM `" . $wpdb->prefix . "postmeta` WHERE  meta_value = %s";
                        $wcal_results = $wpdb->get_results ( $wpdb->prepare( $wcal_query , $cart_id ) );

                        if ( count( $wcal_results ) > 0 ) {

                            $order_id = $wcal_results[0]->post_id;

                            $order    = wc_get_order( $order_id );

                            $query_order = "UPDATE `" . $wpdb->prefix."ac_abandoned_cart_history_lite` SET recovered_cart= '" . $order_id . "', cart_ignored = '1' WHERE id = '".$cart_id."' ";
                            $wpdb->query( $query_order );

                            $order->add_order_note( __( 'This order was abandoned & subsequently recovered.', 'woocommerce-abandoned-cart' ) );

                            delete_post_meta( $order_id,  'wcal_recover_order_placed',         $cart_id );
                            delete_post_meta( $order_id , 'wcal_recover_order_placed_sent_id', $wcal_check_email_sent_to_cart );
                        }
                    }else {

                        $query_ignored = "UPDATE `" . $wpdb->prefix."ac_abandoned_cart_history_lite` SET cart_ignored = '1' WHERE id ='" . $cart_id . "'";
                        $wpdb->query( $query_ignored );
                    }
                    return 1;
                }else if ( strtotime( $order_date_time ) >=  $abandoned_cart_time ) {
                    $query_ignored = "UPDATE `" . $wpdb->prefix."ac_abandoned_cart_history_lite` SET cart_ignored = '1' WHERE id ='" . $cart_id . "'";
                    $wpdb->query( $query_ignored );
                    return 1; //We return here 1 so it indicate that the cart has been modifed so do not sent email and delete from the array.
                }else if( "wc-pending" == $results_query_email[0]->post_status || "wc-failed" == $results_query_email[0]->post_status ) {
                    return 0; //if status of the order is pending or falied then return 0 so it will not delete that cart and send reminder email
                }
            }
            return 0; // it means there are no record found to be update it.
        }

        /**
         * It will check that for abandoned cart remider email has been sent.
         * @param string | int $wcal_cart_id Abandoned cart id
         * @return int  $wcal_sent_id | 0 Email sent id
         * @globals mixed $wpdb
         * @since 4.3
         */
        public static function wcal_get_cart_sent_data ( $wcal_cart_id ) {
            global $wpdb;

            $wcal_query   = "SELECT id FROM `" . $wpdb->prefix."ac_sent_history_lite` WHERE abandoned_order_id = %d ORDER BY 'id' DESC LIMIT 1 ";
            $wcal_results = $wpdb->get_results ( $wpdb->prepare( $wcal_query , $wcal_cart_id ) );

            if ( count( $wcal_results ) > 0 ) {
                $wcal_sent_id = $wcal_results[0]->id;
                return $wcal_sent_id;
            }
            return 0;
        }

        /**
         * If none of the email has been sent for some time then from the time where it send the first email template it will consider
         * the time and further email will sent from first email sent time. So all template will not sent at the same time.
         * @param string | int $wcal_cart_id Abandoned cart id
         * @param string | timestamp $time_to_send_template_after Template time
         * @param string | int $template_id Template id
         * @return boolean true | false
         * @globals mixed $wpdb
         * @since 3.1
         */
        public static function wcal_remove_cart_for_mutiple_templates( $wcal_cart_id, $time_to_send_template_after, $template_id ) {
            global $wpdb;

            $wcal_get_last_email_sent_time              = "SELECT * FROM `" . $wpdb->prefix . "ac_sent_history_lite` WHERE abandoned_order_id = $wcal_cart_id ORDER BY `sent_time` DESC LIMIT 1";
            $wcal_get_last_email_sent_time_results_list = $wpdb->get_results( $wcal_get_last_email_sent_time );

            if( count( $wcal_get_last_email_sent_time_results_list ) > 0 ) {
                $last_template_send_time   = strtotime( $wcal_get_last_email_sent_time_results_list[0]->sent_time );
                $second_template_send_time = $last_template_send_time + $time_to_send_template_after ;
                $current_time_test         = current_time( 'timestamp' );
                if( $second_template_send_time > $current_time_test ) {
                    return true;
                }
            }

            return false;
        }
        /**
         * This function is used to encode the validate string.
         * @param string $validate
         * @return encoded data $validate_encoded
         * @since 1.3
         */
        function wcal_encrypt_validate( $validate ) {
            $cryptKey         = get_option( 'wcal_security_key' );
            $validate_encoded = Wcal_Aes_Ctr::encrypt( $validate, $cryptKey, 256 );
            return( $validate_encoded );
        }

        /**
         * It will check if the reminder email has been sent to the abandoned cart.
         * @param string | int $user_id User id
         * @param string | timestamp $cart_update_time Abandoned cart time
         * @param string | int $template_id Template id
         * @param string | int $id Abandoned cart id
         * @globals mixed $wpdb
         * @return boolean true | false
         * @since 1.3
         */
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
?>