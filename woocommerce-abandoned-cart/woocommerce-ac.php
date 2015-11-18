<?php 
/*
Plugin Name: WooCommerce Abandon Cart Lite Plugin
Plugin URI: http://www.tychesoftwares.com/store/premium-plugins/woocommerce-abandoned-cart-pro
Description: This plugin captures abandoned carts by logged-in users & emails them about it. <strong><a href="http://www.tychesoftwares.com/store/premium-plugins/woocommerce-abandoned-cart-pro">Click here to get the PRO Version.</a></strong>
Version: 2.2
Author: Tyche Softwares
Author URI: http://www.tychesoftwares.com/
*/

if( session_id() === '' ){
    //session has not started
    session_start();
}
// Deletion Settings
register_uninstall_hook( __FILE__, 'woocommerce_ac_delete' );

include_once( "woocommerce_guest_ac.class.php" );

// Add a new interval of 5 minutes
add_filter( 'cron_schedules', 'woocommerce_ac_add_cron_schedule' );

function woocommerce_ac_add_cron_schedule( $schedules ) {
	
    $schedules['5_minutes'] = array(
                'interval'  => 300 , // 5 minutes in seconds
                'display'   => __( 'Once Every Five Minutes' ),
    );
    return $schedules;
}

// Schedule an action if it's not already scheduled
if ( ! wp_next_scheduled( 'woocommerce_ac_send_email_action' ) ) {
    wp_schedule_event( time(), '5_minutes', 'woocommerce_ac_send_email_action' );
}

// Hook into that action that'll fire every 5 minutes
add_action( 'woocommerce_ac_send_email_action', 'woocommerce_ac_send_email_cron' );
function woocommerce_ac_send_email_cron() {
    require_once( ABSPATH.'wp-content/plugins/woocommerce-abandoned-cart/cron/send_email.php' );
}

function woocommerce_ac_delete(){
	
	global $wpdb;
	$table_name_ac_abandoned_cart_history = $wpdb->prefix . "ac_abandoned_cart_history_lite";
	$sql_ac_abandoned_cart_history = "DROP TABLE " . $table_name_ac_abandoned_cart_history ;
	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	$wpdb->get_results( $sql_ac_abandoned_cart_history );

	$table_name_ac_email_templates = $wpdb->prefix . "ac_email_templates_lite";
	$sql_ac_email_templates = "DROP TABLE " . $table_name_ac_email_templates ;
	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	$wpdb->get_results( $sql_ac_email_templates );

	$table_name_ac_sent_history = $wpdb->prefix . "ac_sent_history_lite";
	$sql_ac_sent_history = "DROP TABLE " . $table_name_ac_sent_history ;
	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	$wpdb->get_results( $sql_ac_sent_history );
	
	$table_name_ac_guest_abandoned_cart_history = $wpdb->prefix . "ac_guest_abandoned_cart_history_lite";
	$sql_ac_abandoned_cart_history = "DROP TABLE " . $table_name_ac_guest_abandoned_cart_history ;
	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	$wpdb->get_results( $sql_ac_abandoned_cart_history );
	
	$query   = "SELECT blog_id FROM `".$wpdb->prefix."blogs`";
	$results = $wpdb->get_results( $query );
	
	foreach( $results as $key => $value ) {
	     
	    $table_name_ac_abandoned_cart_history = $wpdb->prefix .$value->blog_id."_"."ac_abandoned_cart_history_lite";
	    $sql_ac_abandoned_cart_history = "DROP TABLE " . $table_name_ac_abandoned_cart_history ;
	    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	    $wpdb->get_results( $sql_ac_abandoned_cart_history );
	     
	    $table_name_ac_email_templates = $wpdb->prefix .$value->blog_id."_"."ac_email_templates_lite";
	    $sql_ac_email_templates = "DROP TABLE " . $table_name_ac_email_templates ;
	    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	    $wpdb->get_results( $sql_ac_email_templates );
	     
	    $table_name_ac_sent_history = $wpdb->prefix .$value->blog_id."_"."ac_sent_history_lite";
	    $sql_ac_sent_history = "DROP TABLE " . $table_name_ac_sent_history ;
	    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	    $wpdb->get_results( $sql_ac_sent_history );
	    
	    $table_name_ac_guest_abandoned_cart_history = $wpdb->prefix . "ac_guest_abandoned_cart_history_lite";
	    $sql_ac_abandoned_cart_history = "DROP TABLE " . $table_name_ac_guest_abandoned_cart_history ;
	    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	    $wpdb->get_results( $sql_ac_abandoned_cart_history );
	     
	}
	
	delete_option ( 'woocommerce_ac_email_body' );
	delete_option ( 'woocommerce_ac_settings' );

}
	/**
	 * woocommerce_abandon_cart class
	 **/
	if ( !class_exists( 'woocommerce_abandon_cart' ) ) {
	
		class woocommerce_abandon_cart {
			
			var $one_hour;
			var $three_hours;
			var $six_hours;
			var $twelve_hours;
			var $one_day;
			var $one_week;
			
			var $duration_range_select = array();
			var $start_end_dates       = array();
			
			public function __construct() {
				
				$this->one_hour     = 60 * 60;
				$this->three_hours  = 3 * $this->one_hour;
				$this->six_hours    = 6 * $this->one_hour;
				$this->twelve_hours = 12 * $this->one_hour;
				$this->one_day      = 24 * $this->one_hour;
				$this->one_week     = 7 * $this->one_day;
				
				$this->duration_range_select = array( 'yesterday'      => 'Yesterday',
						                              'today'          => 'Today',
                                					  'last_seven'     => 'Last 7 days',
                                					  'last_fifteen'   => 'Last 15 days',
                                					  'last_thirty'    => 'Last 30 days',
                                					  'last_ninety'    => 'Last 90 days',
                                					  'last_year_days' => 'Last 365'    
				                                     );
				
				$this->start_end_dates = array( 'yesterday'      => array( 'start_date' => date( "d M Y", ( current_time( 'timestamp' ) - 24*60*60 ) ),
						                        'end_date'       => date( "d M Y", ( current_time( 'timestamp' ) - 7*24*60*60 ) ) ),
						                        'today'          => array( 'start_date' => date( "d M Y", ( current_time( 'timestamp' ) ) ),
								                'end_date'       => date( "d M Y", ( current_time( 'timestamp' ) ) ) ),
						                        'last_seven'     => array( 'start_date' => date( "d M Y", ( current_time( 'timestamp' ) - 7*24*60*60 ) ),
								                'end_date'       => date( "d M Y", ( current_time( 'timestamp' ) ) ) ),
						                        'last_fifteen'   => array( 'start_date' => date( "d M Y", ( current_time( 'timestamp' ) - 15*24*60*60 ) ),
								                'end_date'       => date( "d M Y", ( current_time( 'timestamp' ) ) ) ),
						                        'last_thirty'    => array( 'start_date' => date( "d M Y", ( current_time( 'timestamp' ) - 30*24*60*60 ) ),
								                'end_date'       => date( "d M Y", ( current_time( 'timestamp' ) ) ) ),
						                        'last_ninety'    => array( 'start_date' => date( "d M Y", ( current_time( 'timestamp' ) - 90*24*60*60 ) ),
								                'end_date'       => date( "d M Y", ( current_time( 'timestamp' ) ) ) ),
						                        'last_year_days' => array( 'start_date' => date( "d M Y", ( current_time( 'timestamp' ) - 365*24*60*60 ) ),
								                'end_date'       => date( "d M Y", ( current_time( 'timestamp' ) ) ) ) 				    
				                               );
				
				
				// Initialize settings
				register_activation_hook ( __FILE__, array( &$this, 'woocommerce_ac_activate' ) );
				
				// WordPress Administration Menu 
				add_action ( 'admin_menu', array( &$this, 'woocommerce_ac_admin_menu' ) );
				
				// Actions to be done on cart update
				add_action ( 'woocommerce_cart_updated', array( &$this, 'woocommerce_ac_store_cart_timestamp' ) );
				
				// delete added temp fields after order is placed 
				add_filter ( 'woocommerce_order_details_after_order_table', array( &$this, 'action_after_delivery_session' ) );
				
				add_action ( 'admin_init', array( &$this, 'action_admin_init' ) );
				add_action ( 'admin_init', array( &$this, 'ac_lite_update_db_check' ) );
				
				// Language Translation
				add_action ( 'init', array( &$this, 'update_po_file' ) );
				
				// track links
				add_filter( 'template_include', array( &$this, 'email_track_links_lite' ), 99, 1 );
				
				//Discount Coupon Notice
				add_action ( 'admin_notices', array( &$this, 'ac_lite_coupon_notice' ) );
				
				add_action ( 'admin_enqueue_scripts', array( &$this, 'my_enqueue_scripts_js' ) );
				add_action ( 'admin_enqueue_scripts', array( &$this, 'my_enqueue_scripts_css' ) );
				
				if ( is_admin() ) {
					if ( isset( $_GET[ 'page' ] ) && $_GET[ 'page' ] == "woocommerce_ac_page" )	{
						add_action ( 'admin_head', array( &$this, 'tinyMCE_ac' ) );
					}
					
					// Load "admin-only" scripts here
					add_action ( 'admin_head', array( &$this, 'my_action_javascript' ) );
					add_action ( 'wp_ajax_remove_cart_data', array( &$this, 'remove_cart_data' ) );
					
					add_action ( 'admin_head', array( &$this, 'my_action_send_preview' ) );
					add_action ( 'wp_ajax_preview_email_sent', array( &$this, 'preview_email_sent' ) );
					
				}
				
			}
			
			/*-----------------------------------------------------------------------------------*/
			/* Class Functions */
			/*-----------------------------------------------------------------------------------*/
			
			// Language Translation
			function  update_po_file() {
			    $domain = 'woocommerce-ac';
			    $locale = apply_filters( 'plugin_locale', get_locale(), $domain );
			    if ( $loaded = load_textdomain( $domain, trailingslashit( WP_LANG_DIR ) . $domain . '-' . $locale . '.mo' ) ) {
			        return $loaded;
			    } else {
				    load_plugin_textdomain( $domain, FALSE, basename( dirname( __FILE__ ) ) . '/languages/' );
			           }
			}
			
		    function ac_lite_coupon_notice() {
			     
			     if ( isset( $_GET[ 'page' ] ) && $_GET[ 'page' ] == "woocommerce_ac_page" ) {
?> 
			     <div class = "updated">
			         <p><?php _e( 'You can upgrade to the <a href = "https://www.tychesoftwares.com/store/premium-plugins/woocommerce-abandoned-cart-pro/">PRO version of WooCommerce Abandoned Cart plugin</a> at a <b>20% discount</b>. Use the coupon code: <b>ACPRO20</b>.<a href="https://www.tychesoftwares.com/store/premium-plugins/woocommerce-abandoned-cart-pro/"> Purchase now </a> & save $24!', 'woocommerce-ac' ); ?></p>
			     </div>   
			     <?php
			     }
			 }
			/*-----------------------------------------------------------------------------------*/
			/* Class Functions */
			/*-----------------------------------------------------------------------------------*/					
						
			function woocommerce_ac_activate() {
			
				global $wpdb;
				 
				$table_name = $wpdb->prefix . "ac_email_templates_lite";
			
				$sql = "CREATE TABLE IF NOT EXISTS $table_name (
        				`id` int(11) NOT NULL AUTO_INCREMENT,
        				`subject` text COLLATE utf8_unicode_ci NOT NULL,
        				`body` mediumtext COLLATE utf8_unicode_ci NOT NULL,
        				`is_active` enum('0','1') COLLATE utf8_unicode_ci NOT NULL,
        				`frequency` int(11) NOT NULL,
        				`day_or_hour` enum('Days','Hours') COLLATE utf8_unicode_ci NOT NULL,
        				`template_name` text COLLATE utf8_unicode_ci NOT NULL,
        				`from_name` text COLLATE utf8_unicode_ci NOT NULL,
          				PRIMARY KEY (`id`)
				        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ";
			
				require_once ( ABSPATH . 'wp-admin/includes/upgrade.php' );
				dbDelta( $sql );
			
				$sent_table_name = $wpdb->prefix . "ac_sent_history_lite";
			
				$sql_query = "CREATE TABLE IF NOT EXISTS $sent_table_name (
            				`id` int(11) NOT NULL auto_increment,
            				`template_id` varchar(40) collate utf8_unicode_ci NOT NULL,
            				`abandoned_order_id` int(11) NOT NULL,
            				`sent_time` datetime NOT NULL,
            				`sent_email_id` text COLLATE utf8_unicode_ci NOT NULL,
            				PRIMARY KEY  (`id`)
				            ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ";
				 
				require_once ( ABSPATH . 'wp-admin/includes/upgrade.php' );
				dbDelta ( $sql_query );
						 
				$ac_history_table_name = $wpdb->prefix . "ac_abandoned_cart_history_lite";
				 
				$history_query = "CREATE TABLE IF NOT EXISTS $ac_history_table_name (
                				 `id` int(11) NOT NULL AUTO_INCREMENT,
                				 `user_id` int(11) NOT NULL,
                				 `abandoned_cart_info` text COLLATE utf8_unicode_ci NOT NULL,
                				 `abandoned_cart_time` int(11) NOT NULL,
                				 `cart_ignored` enum('0','1') COLLATE utf8_unicode_ci NOT NULL,
                				 `recovered_cart` int(11) NOT NULL,
                				 PRIMARY KEY (`id`)
				                 ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci";
						 
				require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
				dbDelta( $history_query );
				
				$ac_settings             = new stdClass();
				$ac_settings->cart_time  = '60';
				$woo_ac_settings[]       = $ac_settings;
				$woocommerce_ac_settings = json_encode( $woo_ac_settings );
				add_option  ( 'woocommerce_ac_settings', $woocommerce_ac_settings );
			}
			
			function ac_lite_update_db_check() {
			    global $wpdb;
			    
			    if( get_option( 'ac_lite_delete_alter_table_queries' ) != 'yes' ) {
			        update_option( 'ac_lite_alter_table_queries', '' );
			        update_option( 'ac_lite_delete_alter_table_queries', 'yes' );
			    }
			    if( get_option( 'ac_lite_alter_table_queries' ) != 'yes' ) {     
			        if( $wpdb->get_var( "SHOW TABLES LIKE '" . $wpdb->prefix . "ac_email_templates'" ) === $wpdb->prefix . 'ac_email_templates' ) {
			             $old_table_name = $wpdb->prefix . "ac_email_templates";
			             $table_name     = $wpdb->prefix . "ac_email_templates_lite";
			        
			             $alter_ac_email_table_query = "ALTER TABLE $old_table_name
			                                            RENAME TO $table_name";
			             $wpdb->get_results ( $alter_ac_email_table_query );
			        
			        }
			        
			        if( $wpdb->get_var( "SHOW TABLES LIKE '" . $wpdb->prefix . "ac_sent_history'" ) === $wpdb->prefix . 'ac_sent_history' ) { 
			             $old_sent_table_name = $wpdb->prefix . "ac_sent_history";
			             $sent_table_name     = $wpdb->prefix . "ac_sent_history_lite";
			             $alter_ac_sent_history_table_query = "ALTER TABLE $old_sent_table_name
			                                                   RENAME TO $sent_table_name";
			             $wpdb->get_results ( $alter_ac_sent_history_table_query );
			        }
			        
			        if( $wpdb->get_var( "SHOW TABLES LIKE '" . $wpdb->prefix . "ac_abandoned_cart_history'" ) === $wpdb->prefix . 'ac_abandoned_cart_history' ) {
			             $old_ac_history_table_name = $wpdb->prefix . "ac_abandoned_cart_history";
			             $ac_history_table_name     = $wpdb->prefix . "ac_abandoned_cart_history_lite";
			        
			             $alter_ac_abandoned_cart_history_table_query = "ALTER TABLE $old_ac_history_table_name
			                                                             RENAME TO $ac_history_table_name";
			             $wpdb->get_results ( $alter_ac_abandoned_cart_history_table_query );
			        }
			         
			        update_option( 'ac_lite_alter_table_queries', 'yes' );
			    }
			    
			    $ac_history_table_name = $wpdb->prefix."ac_abandoned_cart_history_lite";
			    $check_table_query = "SHOW COLUMNS FROM $ac_history_table_name LIKE 'user_type'";
			    $results = $wpdb->get_results( $check_table_query );
			    
			    if ( count( $results ) == 0 ) {
			        $alter_table_query = "ALTER TABLE $ac_history_table_name ADD `user_type` text AFTER  `recovered_cart`";
			        $wpdb->get_results( $alter_table_query );
			    }
			    
			    $guest_table = $wpdb->prefix."ac_guest_abandoned_cart_history_lite" ;
			    $query_guest_table = "SHOW TABLES LIKE '$guest_table' ";
			    $result_guest_table = $wpdb->get_results( $query_guest_table );
			    
			    if ( count( $result_guest_table ) == 0 ) {
			        
    			    $ac_guest_history_table_name = $wpdb->prefix . "ac_guest_abandoned_cart_history_lite";
    			    $ac_guest_history_query = "CREATE TABLE IF NOT EXISTS $ac_guest_history_table_name (
    			    `id` int(15) NOT NULL AUTO_INCREMENT,
    			    `billing_first_name` text,
    			    `billing_last_name` text,
    			    `billing_company_name` text,
    			    `billing_address_1` text,
    			    `billing_address_2` text,
    			    `billing_city` text,
    			    `billing_county` text,
    			    `billing_zipcode` text,
    			    `email_id` text,
    			    `phone` text,
    			    `ship_to_billing` text,
    			    `order_notes` text,
    			    `shipping_first_name` text,
    			    `shipping_last_name` text,
    			    `shipping_company_name` text,
    			    `shipping_address_1` text,
    			    `shipping_address_2` text,
    			    `shipping_city` text,
    			    `shipping_county` text,
    			    `shipping_zipcode` double,
    			    `shipping_charges` double,
    			    PRIMARY KEY (`id`)
    			    ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=63000000";
    			    require_once( ABSPATH . 'wp-admin/includes/upgrade.php');
    			    $wpdb->query( $ac_guest_history_query );
			    }
			     
			}
			
			function woocommerce_ac_admin_menu() {
			
				$page = add_submenu_page ( 'woocommerce', __( 'Abandoned Carts', 'woocommerce-ac' ), __( 'Abandoned Carts', 'woocommerce-ac' ), 'manage_woocommerce', 'woocommerce_ac_page', array( &$this, 'woocommerce_ac_page' ) );
			
			}
			
			function woocommerce_ac_store_cart_timestamp() {
			    
			    global $wpdb,$woocommerce;
			    
			    $current_time = current_time( 'timestamp' );
			    $cut_off_time = json_decode( get_option( 'woocommerce_ac_settings' ) );
			    
			    $cart_ignored   = 0;
			    $recovered_cart = 0;
			    
			    if( isset( $cut_off_time[0]->cart_time ) ) {
			        $cart_cut_off_time = $cut_off_time[0]->cart_time * 60;
			    } else {
			        $cart_cut_off_time = 60 * 60;
			    }
			    
			    $compare_time = $current_time - $cart_cut_off_time;
				
				if ( is_user_logged_in() ) {
				    
				    $user_id      = get_current_user_id();
				    $query = "SELECT * FROM `".$wpdb->prefix."ac_abandoned_cart_history_lite`
    							WHERE user_id      = %d
    							AND cart_ignored   = %s
    							AND recovered_cart = %d ";
    				$results = $wpdb->get_results($wpdb->prepare( $query, $user_id, $cart_ignored, $recovered_cart ) );
    				
    				if ( count($results) == 0 ) {
    				    
    					$cart_info = json_encode( get_user_meta( $user_id, '_woocommerce_persistent_cart', true ) );
    					$user_type = "REGISTERED";
    					$insert_query = "INSERT INTO `".$wpdb->prefix."ac_abandoned_cart_history_lite`
    					                 ( user_id, abandoned_cart_info, abandoned_cart_time, cart_ignored, user_type )
    					                 VALUES ( %d, %s, %d, %s, %s )";
    					$wpdb->query( $wpdb->prepare( $insert_query, $user_id, $cart_info,$current_time, $cart_ignored, $user_type ) );
    				}
    				elseif ( isset( $results[0]->abandoned_cart_time ) && $compare_time > $results[0]->abandoned_cart_time ) {
    					
    				    $updated_cart_info = json_encode( get_user_meta( $user_id, '_woocommerce_persistent_cart', true ) );
    					
    					if ( ! $this->compare_carts( $user_id, $results[0]->abandoned_cart_info ) ) {  
    					    
    					    $updated_cart_ignored = 1;
    					    $query_ignored = "UPDATE `".$wpdb->prefix."ac_abandoned_cart_history_lite`
                    						  SET cart_ignored = %s
                    						  WHERE user_id    = %d ";
    					    $wpdb->query( $wpdb->prepare( $query_ignored, $updated_cart_ignored, $user_id ) );
    					    
    					    $user_type = "REGISTERED";
    					    
    					    $query_update = "INSERT INTO `".$wpdb->prefix."ac_abandoned_cart_history_lite`
    						                 (user_id, abandoned_cart_info, abandoned_cart_time, cart_ignored, user_type)
    						                 VALUES (%d, %s, %d, %s, %s)";
    					    $wpdb->query( $wpdb->prepare( $query_update, $user_id, $updated_cart_info, $current_time, $cart_ignored, $user_type ) );
    					    
    						update_user_meta ( $user_id, '_woocommerce_ac_modified_cart', md5( "yes" ) );
    					} else {
    						update_user_meta ( $user_id, '_woocommerce_ac_modified_cart', md5( "no" ) );
    				  }
    				} else {
    					$updated_cart_info = json_encode( get_user_meta( $user_id, '_woocommerce_persistent_cart', true ) );
    					
    					$query_update = "UPDATE `".$wpdb->prefix."ac_abandoned_cart_history_lite`
                    					 SET abandoned_cart_info = %s,
                    					     abandoned_cart_time = %d
                    					 WHERE user_id      = %d 
    			                         AND   cart_ignored = %s ";
    					$wpdb->query( $wpdb->prepare( $query_update, $updated_cart_info, $current_time, $user_id, $cart_ignored ) );
				       }
				} else{ //start here guest user
				    
				    if ( isset( $_SESSION['user_id'] ) ) $user_id = $_SESSION['user_id'];
				    else $user_id = "";
				    
				    $query = "SELECT * FROM `".$wpdb->prefix."ac_abandoned_cart_history_lite` WHERE user_id = %d AND cart_ignored = '0' AND recovered_cart = '0'";
				    $results = $wpdb->get_results( $wpdb->prepare( $query, $user_id ) );
				    $cart = array();
				    
				    foreach ( $woocommerce->cart->cart_contents as $cart_id => $value ) {
				        $cart['cart'][$cart_id] = array();
				    
				        foreach ( $value as $k=>$v ) {
				            $cart['cart'][$cart_id][$k] = $v;
				    
				            if ( $k == "quantity" ) {
				                $price = get_post_meta( $cart['cart'][$cart_id]['product_id'], '_price', true );
				                $cart['cart'][$cart_id]['line_total'] = $cart['cart'][$cart_id]['quantity'] * $price;
				                $cart['cart'][$cart_id]['line_tax'] = '0';
				                $cart['cart'][$cart_id]['line_subtotal'] = $cart['cart'][$cart_id]['line_total'];
				                $cart['cart'][$cart_id]['line_subtotal_tax'] = $cart['cart'][$cart_id]['line_tax'];
				                break;
				            }
				        }
				    }
				    $updated_cart_info = json_encode($cart);
				    
				    if ( $results ) {
				        
				            if ( $compare_time > $results[0]->abandoned_cart_time ) {
				                	
				                if ( $updated_cart_info != $results[0]->abandoned_cart_info ) {
				                    $query_ignored = "UPDATE `".$wpdb->prefix."ac_abandoned_cart_history_lite` SET cart_ignored = '1' WHERE user_id ='".$user_id."'";
				    
				                    $wpdb->query( $query_ignored );
				                    $user_type = 'GUEST';
				                    
				                    $query_update = "INSERT INTO `".$wpdb->prefix."ac_abandoned_cart_history_lite`
    						                 (user_id, abandoned_cart_info, abandoned_cart_time, cart_ignored, user_type)
    						                 VALUES (%d, %s, %d, %s, %s)";
				                    $wpdb->query( $wpdb->prepare( $query_update, $user_id, $updated_cart_info, $current_time, $cart_ignored, $user_type ) );
				                    
				                    
				                    $wpdb->query( $query_update );
				                    update_user_meta( $user_id, '_woocommerce_ac_modified_cart', md5("yes") );
				                } else {
				                    update_user_meta( $user_id, '_woocommerce_ac_modified_cart', md5("no") );
				                }
				            } else {
				                $query_update = "UPDATE `".$wpdb->prefix."ac_abandoned_cart_history_lite` SET abandoned_cart_info = '".$updated_cart_info."', abandoned_cart_time = '".$current_time."' WHERE user_id='".$user_id."' AND cart_ignored='0' ";
				                $wpdb->query( $query_update );
				            }
				        }
				    }
				    
				
			}

			function decrypt_validate( $validate ) {
			    $cryptKey  = 'qJB0rGtIn5UB1xG03efyCp';
			    $validate_decoded      = rtrim( mcrypt_decrypt( MCRYPT_RIJNDAEL_256, md5( $cryptKey ), base64_decode( $validate ), MCRYPT_MODE_CBC, md5( md5( $cryptKey ) ) ), "\0");
			    return( $validate_decoded );
			}
			
			function email_track_links_lite( $template ) {
			    global $woocommerce;
			    $track_link = '';
			
			    if ( isset( $_GET['wacp_action'] ) ) $track_link = $_GET['wacp_action'];
			
			    if ( $track_link == 'track_links' ) {
			        global $wpdb;
			
			        $validate_server_string = $_SERVER["QUERY_STRING"];
			        $validate_server_arr = explode("validate=", $validate_server_string);
			        $validate_encoded_string = end($validate_server_arr);
			
			        $link_decode_test = base64_decode( $validate_encoded_string );
			
			        if ( preg_match( '/&url=/', $link_decode_test ) ){ // it will check if any old email have open the link
			            $link_decode = $link_decode_test;
			        }else{
			            $link_decode = $this->decrypt_validate( $validate_encoded_string );
			        }
			        $sent_email_id_pos = strpos( $link_decode, '&' );
			        $email_sent_id = substr( $link_decode , 0, $sent_email_id_pos );
			        $_SESSION[ 'email_sent_id' ] = $email_sent_id;
			        $url_pos = strpos( $link_decode, '=' );
			        $url_pos = $url_pos + 1;
			        $url = substr( $link_decode, $url_pos );
			        $get_ac_id_query = "SELECT abandoned_order_id FROM `".$wpdb->prefix."ac_sent_history_lite` WHERE id = %d";
			        $get_ac_id_results = $wpdb->get_results( $wpdb->prepare( $get_ac_id_query, $email_sent_id ) );
			        $get_user_id_query = "SELECT user_id FROM `".$wpdb->prefix."ac_abandoned_cart_history_lite` WHERE id = %d";
			        $get_user_results = $wpdb->get_results( $wpdb->prepare( $get_user_id_query, $get_ac_id_results[0]->abandoned_order_id ) );
			        $user_id = 0;
			
			        if ( isset( $get_user_results ) && count( $get_user_results ) > 0 ) {
			            $user_id = $get_user_results[0]->user_id;
			        }
			
			        if ( $user_id == 0 ) {
			            echo "Link expired";
			            exit;
			        }
			        $user = wp_set_current_user( $user_id );
			
			        if ( $user_id >= "63000000" ) {
			            $query_guest = "SELECT * from `". $wpdb->prefix."ac_guest_abandoned_cart_history_lite` WHERE id = %d";
			            $results_guest = $wpdb->get_results( $wpdb->prepare( $query_guest, $user_id ) );
			            $query_cart = "SELECT recovered_cart FROM `".$wpdb->prefix."ac_abandoned_cart_history_lite` WHERE user_id = %d";
			            $results = $wpdb->get_results( $wpdb->prepare( $query_cart, $user_id ) );
			
			            if ( $results_guest  && $results[0]->recovered_cart == '0' ) {
			                $_SESSION[ 'guest_first_name' ] = $results_guest[0]->billing_first_name;
			                $_SESSION[ 'guest_last_name' ] = $results_guest[0]->billing_last_name;
			                $_SESSION[ 'guest_email' ] = $results_guest[0]->email_id;
			                $_SESSION[ 'user_id' ] = $user_id;
			            } else {
			                wp_redirect( get_permalink( woocommerce_get_page_id( 'shop' ) ) );
			            }
			        }
			
			        if ( $user_id < "63000000" ) {
			            $user_login = $user->data->user_login;
			            wp_set_auth_cookie( $user_id );
			            $my_temp = woocommerce_load_persistent_cart( $user_login, $user );
			            do_action( 'wp_login', $user_login, $user );
			
			            if ( isset( $sign_in ) && is_wp_error( $sign_in ) ) {
			                echo $sign_in->get_error_message();
			                exit;
			            }
			        } else
			            $my_temp = $this->woocommerce_load_guest_persistent_cart( $user_id );
			
			        if ( $email_sent_id > 0 && is_numeric( $email_sent_id ) ) {
			            
			            header( "Location: $url" );
			        }
			    } else
			        return $template;
			}
			
			function woocommerce_load_guest_persistent_cart() {
			    global $woocommerce;
			
			    $saved_cart = json_decode( get_user_meta( $_SESSION['user_id'], '_woocommerce_persistent_cart', true ), true );
			    $c = array();
			    $cart_contents_total = $cart_contents_weight = $cart_contents_count = $cart_contents_tax = $total = $subtotal = $subtotal_ex_tax = $tax_total = 0;
			
			    foreach ( $saved_cart as $key => $value ) {
			
			        foreach ( $value as $a => $b ) {
			            $c['product_id']        = $b['product_id'];
			            $c['variation_id']      = $b['variation_id'];
			            $c['variation']         = $b['variation'];
			            $c['quantity']          = $b['quantity'];
			            $product_id             = $b['product_id'];
			            $c['data']              = get_product($product_id);
			            $c['line_total']        = $b['line_total'];
			            $c['line_tax']          = $cart_contents_tax;
			            $c['line_subtotal']     = $b['line_subtotal'];
			            $c['line_subtotal_tax'] = $cart_contents_tax;
			            $value_new[$a]          = $c;
			            $cart_contents_total    = $b['line_subtotal'] + $cart_contents_total;
			            $cart_contents_count    = $cart_contents_count + $b['quantity'];
			            $total                  = $total + $b['line_total'];
			            $subtotal               = $subtotal + $b['line_subtotal'];
			            $subtotal_ex_tax        = $subtotal_ex_tax + $b['line_subtotal'];
			        }
			        $saved_cart_data[$key]      = $value_new;
			        $woocommerce_cart_hash      = $a;
			    }
			
			    if ( $saved_cart ) {
			
			        if ( empty( $woocommerce->session->cart ) || ! is_array( $woocommerce->session->cart ) || sizeof( $woocommerce->session->cart ) == 0 ) {
			            $woocommerce->session->cart                 = $saved_cart['cart'];
			            $woocommerce->session->cart_contents_total  = $cart_contents_total;
			            $woocommerce->session->cart_contents_weight = $cart_contents_weight;
			            $woocommerce->session->cart_contents_count  = $cart_contents_count;
			            $woocommerce->session->cart_contents_tax    = $cart_contents_tax;
			            $woocommerce->session->total                = $total;
			            $woocommerce->session->subtotal             = $subtotal;
			            $woocommerce->session->subtotal_ex_tax      = $subtotal_ex_tax;
			            $woocommerce->session->tax_total            = $tax_total;
			            $woocommerce->session->shipping_taxes       = array();
			            $woocommerce->session->taxes                = array();
			            $woocommerce->session->ac_customer          = array();
			            $woocommerce->cart->cart_contents           = $saved_cart_data['cart'];
			            $woocommerce->cart->cart_contents_total     = $cart_contents_total;
			            $woocommerce->cart->cart_contents_weight    = $cart_contents_weight;
			            $woocommerce->cart->cart_contents_count     = $cart_contents_count;
			            $woocommerce->cart->cart_contents_tax       = $cart_contents_tax;
			            $woocommerce->cart->total                   = $total;
			            $woocommerce->cart->subtotal                = $subtotal;
			            $woocommerce->cart->subtotal_ex_tax         = $subtotal_ex_tax;
			            $woocommerce->cart->tax_total               = $tax_total;
			        }
			    }
			}
			
			
			function compare_carts( $user_id, $last_abandoned_cart )
			{
				$current_woo_cart   = get_user_meta( $user_id, '_woocommerce_persistent_cart', true );
				$abandoned_cart_arr = json_decode( $last_abandoned_cart, true );
			
				$temp_variable = "";
				if ( count( $current_woo_cart['cart'] ) >= count( $abandoned_cart_arr['cart'] ) ) {
					//do nothing
				} else {
					$temp_variable      = $current_woo_cart;
					$current_woo_cart   = $abandoned_cart_arr;
					$abandoned_cart_arr = $temp_variable;
				}
				foreach ( $current_woo_cart as $key => $value )
				{
					foreach ( $value as $item_key => $item_value )
					{
						$current_cart_product_id   = $item_value['product_id'];
						$current_cart_variation_id = $item_value['variation_id'];
						$current_cart_quantity     = $item_value['quantity'];

						if ( isset( $abandoned_cart_arr[ $key ][ $item_key ][ 'product_id' ] ) ) {
							$abandoned_cart_product_id = $abandoned_cart_arr[$key][$item_key]['product_id'];
						} else {
							$abandoned_cart_product_id = "";
						}
						if ( isset( $abandoned_cart_arr[$key][$item_key]['variation_id'] ) ) {
						     $abandoned_cart_variation_id = $abandoned_cart_arr[$key][$item_key]['variation_id']; 
						} else {
						    $abandoned_cart_variation_id = "";
						}
						if ( isset( $abandoned_cart_arr[$key][$item_key]['quantity'] ) ) {
						     $abandoned_cart_quantity = $abandoned_cart_arr[$key][$item_key]['quantity'];
						} else {
						     $abandoned_cart_quantity = "";
						}
						if ( ( $current_cart_product_id != $abandoned_cart_product_id ) ||
							 ( $current_cart_variation_id != $abandoned_cart_variation_id ) ||
							 ( $current_cart_quantity != $abandoned_cart_quantity ) )
						{
							return false;
						}
					}
				}
				return true;
			}
			
			function action_after_delivery_session( $order ) {
				
				global $wpdb;
				$user_id = get_current_user_id();
				
				$sent_email = $_SESSION[ 'email_sent_id' ];
				
				if ( $user_id == "" ) {
				    $user_id = $_SESSION['user_id'];
				    //  Set the session variables to blanks
				    $_SESSION['guest_first_name'] = $_SESSION['guest_last_name'] = $_SESSION['guest_email'] = $_SESSION['user_id'] = "";
				}
				
				delete_user_meta( $user_id, '_woocommerce_ac_persistent_cart_time' );
				delete_user_meta( $user_id, '_woocommerce_ac_persistent_cart_temp_time' );
			
				// get all latest abandoned carts that were modified
				
				$cart_ignored   = 0;
				$recovered_cart = 0;
				$query = "SELECT * FROM `".$wpdb->prefix."ac_abandoned_cart_history_lite`
            			  WHERE user_id      = %d
            		      AND cart_ignored   = %s
            			  AND recovered_cart = %d
            			  ORDER BY id DESC
            			  LIMIT 1";
				$results = $wpdb->get_results( $wpdb->prepare( $query, $user_id, $cart_ignored, $recovered_cart ) );
				if ( count( $results ) > 0 ) {
				    if ( get_user_meta( $user_id, '_woocommerce_ac_modified_cart', true ) == md5( "yes" ) || 
						 get_user_meta( $user_id, '_woocommerce_ac_modified_cart', true ) == md5( "no" ) )
				        {
					        $order_id = $order->id;
        					
					        $updated_cart_ignored = 1;
        					$query_order = "UPDATE `".$wpdb->prefix."ac_abandoned_cart_history_lite`
                        					SET recovered_cart = %d,
                        					    cart_ignored   = %s
                        					WHERE id = %d ";
        					$wpdb->query( $wpdb->prepare( $query_order, $order_id, $updated_cart_ignored, $results[0]->id ) );
        					delete_user_meta( $user_id, '_woocommerce_ac_modified_cart' );
        				} else { 
				            $delete_query = "DELETE FROM `".$wpdb->prefix."ac_abandoned_cart_history_lite`
					        				 WHERE
					        				 id= %d ";
				            $wpdb->query( $wpdb->prepare( $delete_query, $results[0]->id ) );
        				}
 			       }else {
                    $email_id = $order->billing_email;
                    $query = "SELECT * FROM `".$wpdb->prefix."ac_guest_abandoned_cart_history_lite` WHERE email_id = %s";
                    $results_id = $wpdb->get_results( $wpdb->prepare( $query, $email_id ) );
                    
                    if ( $results_id ) {
                        $record_status = "SELECT * FROM `".$wpdb->prefix."ac_abandoned_cart_history_lite` WHERE user_id = %d AND recovered_cart = '0'";
                        $results_status = $wpdb->get_results( $wpdb->prepare( $record_status, $results_id[0]->id ) );
                            
                        if ( $results_status ) {
                            
                            if ( get_user_meta( $results_id[0]->id, '_woocommerce_ac_modified_cart', true ) == md5("yes") ||
                                    get_user_meta( $results_id[0]->id, '_woocommerce_ac_modified_cart', true ) == md5("no") ) {
                                    
                                $order_id = $order->id;
                                $query_order = "UPDATE `".$wpdb->prefix."ac_abandoned_cart_history_lite` SET recovered_cart= '".$order_id."', cart_ignored = '1' WHERE id='".$results_status[0]->id."' ";
                                $wpdb->query( $query_order );
                                delete_user_meta( $results_id[0]->id, '_woocommerce_ac_modified_cart' );

								$sent_email = $_SESSION[ 'email_sent_id' ];
								$recover_order = "UPDATE `".$wpdb->prefix."ac_sent_history` SET recovered_order = '1' 
								WHERE id ='".$sent_email."' ";
								$wpdb->query( $recover_order );
                            } else {
                                $delete_guest = "DELETE FROM `".$wpdb->prefix."ac_guest_abandoned_cart_history_lite` WHERE id = '".$results_id[0]->id."'";
                                $wpdb->query( $delete_guest );
                                
                                $delete_query = "DELETE FROM `".$wpdb->prefix."ac_abandoned_cart_history_lite` WHERE user_id='".$results_id[0]->id."' ";
                                $wpdb->query( $delete_query );
                            }
                        }       
                    }
                }
 			}
			
			function action_admin_init() {
				// only hook up these filters if we're in the admin panel, and the current user has permission
				// to edit posts and pages				
				if ( current_user_can( 'edit_posts' ) && current_user_can( 'edit_pages' ) ) {
					add_filter( 'mce_buttons', array( &$this, 'filter_mce_button' ) );
					add_filter( 'mce_external_plugins', array( &$this, 'filter_mce_plugin' ) );
				}
			}
			
			function filter_mce_button( $buttons ) {
				// add a separation before our button, here our button's id is &quot;mygallery_button&quot;
				array_unshift( $buttons, 'abandoncart_email_variables', '|' );
				return $buttons;
			}
			
			function filter_mce_plugin( $plugins ) {
				// this plugin file will work the magic of our button
				$plugins['abandoncart'] = plugin_dir_url( __FILE__ ) . 'js/abandoncart_plugin_button.js';
				return $plugins;
			}
			
			function display_tabs() {
			
				if ( isset( $_GET[ 'action' ] ) ) {
				    $action = $_GET[ 'action' ];
				} else {
				    $action                = "";			
				    $active_listcart       = "";
				    $active_emailtemplates = "";
				    $active_settings       = "";
				    $active_stats          = "";
				}			
				if ( ( $action == 'listcart' || $action == 'orderdetails' ) || $action == '' ) {
					$active_listcart = "nav-tab-active";
				}			
				if ( $action == 'emailtemplates' ) {
					$active_emailtemplates = "nav-tab-active";
				}
			    if ( $action == 'emailsettings' ) {
					$active_settings = "nav-tab-active";
				}
			    if ( $action == 'stats' ) {
					$active_stats = "nav-tab-active";
				}
			
				?>
				
				<div style="background-image: url('<?php echo plugins_url(); ?>/woocommerce-abandoned-cart/images/ac_tab_icon.png') !important;" class="icon32"><br></div>				
				
				<h2 class="nav-tab-wrapper woo-nav-tab-wrapper">
				<a href="admin.php?page=woocommerce_ac_page&action=listcart" class="nav-tab <?php if (isset($active_listcart)) echo $active_listcart; ?>"> <?php _e( 'Abandoned Orders', 'woocommerce-ac' );?> </a>
				<a href="admin.php?page=woocommerce_ac_page&action=emailtemplates" class="nav-tab <?php if (isset($active_emailtemplates)) echo $active_emailtemplates; ?>"> <?php _e( 'Email Templates', 'woocommerce-ac' );?> </a>
				<a href="admin.php?page=woocommerce_ac_page&action=emailsettings" class="nav-tab <?php if (isset($active_settings)) echo $active_settings; ?>"> <?php _e( 'Settings', 'woocommerce-ac' );?> </a>
				<a href="admin.php?page=woocommerce_ac_page&action=stats" class="nav-tab <?php if (isset($active_stats)) echo $active_stats; ?>"> <?php _e( 'Recovered Orders', 'woocommerce-ac' );?> </a>
				</h2>
				
				<?php
			}
			
			function my_enqueue_scripts_js( $hook ) {
				
				if ( $hook != 'woocommerce_page_woocommerce_ac_page' ) {
					return;
				} else {				
					wp_enqueue_script( 'jquery' );
                    wp_enqueue_script( 
                                       'jquery-ui-min',
            						   '//ajax.googleapis.com/ajax/libs/jqueryui/1.9.2/jquery-ui.min.js',
            						   '',
            						   '',
            					       false
					);
					wp_enqueue_script( 'jquery-ui-datepicker' );
					
					wp_enqueue_script(
            						   'jquery-tip',
            						   plugins_url( '/js/jquery.tipTip.minified.js', __FILE__ ),
            						   '',
            						   '',
            						   false
					);
					wp_register_script( 'woocommerce_admin', plugins_url() . '/woocommerce/assets/js/admin/woocommerce_admin.js', array( 'jquery', 'jquery-ui-widget', 'jquery-ui-core' ) );
					wp_enqueue_script( 'woocommerce_admin' );
					
					?>
					<script type="text/javascript" >
					function delete_email_template( id )
					{
						var y=confirm( 'Are you sure you want to delete this Email Template' );
						if( y==true ) {
							location.href='admin.php?page=woocommerce_ac_page&action=emailtemplates&mode=removetemplate&id='+id;
					    }
					}
				    </script>
				    
				    <?php
				    wp_enqueue_script( 'tinyMCE_ac', plugins_url() . '/woocommerce-abandoned-cart/js/tinymce/jscripts/tiny_mce/tiny_mce.js' );
				    wp_enqueue_script( 'ac_email_variables', plugins_url() . '/woocommerce-abandoned-cart/js/abandoncart_plugin_button.js' );
				    ?>
				    
				    <?php
				}
			
			}
			
			function tinyMCE_ac(){
			
				?>
				<script language="javascript" type="text/javascript">
				tinyMCE.init({
					theme : "advanced",
					mode: "exact",
					elements : "woocommerce_ac_email_body",
					theme_advanced_toolbar_location : "top",
					theme_advanced_buttons1 : "abandoncart_email_variables,separator,code,separator,preview,separator,bold,italic,underline,strikethrough,separator,"
					+ "justifyleft,justifycenter,justifyright,justifyfull,formatselect,"
					+ "bullist,numlist,outdent,indent,separator,"
					+ "cut,copy,paste,separator,sub,sup,charmap",
					theme_advanced_buttons2 : "formatselect,fontselect,fontsizeselect,styleselect,forecolor,backcolor,forecolorpicker,backcolorpicker,separator,link,unlink,anchor,image,separator,"
					+"undo,redo,cleanup"
					+"image", 
					height:"500px",
					width:"1000px",
					apply_source_formatting : true,
					cleanup: true,
					plugins : "advhr,emotions,fullpage,fullscreen,iespell,media,paste,nonbreaking,pagebreak,preview,print,spellchecker,visualchars,searchreplace,insertdatetime,table,directionality,layer,style,xhtmlxtras,abandoncart",
			        theme_advanced_buttons4 : "advhr,emotions,fullpage,fullscreen,iespell,media,nonbreaking,pagebreak,print,spellchecker,visualchars,searchreplace,insertdatetime,directionality,layer,style,xhtmlxtras,insertlayer,moveforward,movebackward,absolute,cite,ins,del,abbr,acronym,attribs,help,hr,removeformat",
			        theme_advanced_buttons3 : "tablecontrols,search,replace,pastetext,pasteword,selectall,styleprops,ltr,rtl,visualaid,newdocument,blockquote",
			        extended_valid_elements : "hr[class|width|size|noshade]",
			        fullpage_fontsizes : '13px,14px,15px,18pt,xx-large',
			        fullpage_default_xml_pi : false,
			        fullpage_default_langcode : 'en',
			        fullpage_default_title : "My document title",
			        table_styles : "Header 1=header1;Header 2=header2;Header 3=header3",
			        table_cell_styles : "Header 1=header1;Header 2=header2;Header 3=header3;Table Cell=tableCel1",
			        table_row_styles : "Header 1=header1;Header 2=header2;Header 3=header3;Table Row=tableRow1",
			        table_cell_limit : 100,
			        table_row_limit : 5,
			        table_col_limit : 5,
                    convert_urls : false
				});
					
				</script>
				<?php
			}
			
			function my_enqueue_scripts_css( $hook ) {
				
				if ( $hook != 'woocommerce_page_woocommerce_ac_page' ) {
					return;
				} else {
					wp_enqueue_style( 'jquery-ui', "http://ajax.googleapis.com/ajax/libs/jqueryui/1.8/themes/base/jquery-ui.css" , '', '', false );					
					wp_enqueue_style( 'woocommerce_admin_styles', plugins_url() . '/woocommerce/assets/css/admin.css' );
					wp_enqueue_style( 'jquery-ui-style', 'http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.2/themes/smoothness/jquery-ui.css' );
					
				 ?>
						
					<style>
					span.mce_abandoncart_email_variables 
					{
					    background-image: url("<?php echo plugins_url(); ?>/woocommerce-abandoned-cart/images/ac_editor_icon.png") !important;
					    background-position: center center !important;
					    background-repeat: no-repeat !important;
					}
					</style>
				
				<?php 
				}
			}
				
			/**
			 * Abandon Cart Settings Page
			 */
			function woocommerce_ac_page()
			{
				if ( is_user_logged_in() ) {
				global $wpdb;
					
				// Check the user capabilities
				if ( !current_user_can( 'manage_woocommerce' ) ) {
					wp_die( __( 'You do not have sufficient permissions to access this page.', 'woocommerce-ac' ) );
				}			
				?>
			
					<div class="wrap">
						<div class="icon32" style="background-image: url('<?php echo plugins_url(); ?>/woocommerce-abandoned-cart/images/abandoned_cart_small.png') !important;">
							<br />
						</div>
							<h2><?php _e( 'WooCommerce - Abandon Cart Lite', 'woocommerce-ac' ); ?></h2>
					<?php 
					
                 if ( isset( $_GET[ 'action' ] ) ) {
                     $action = $_GET[ 'action' ];
                 } else {
                     $action = "";
                 }
				 if ( isset( $_GET[ 'mode' ] ) ) {
					 $mode = $_GET[ 'mode' ];
				 } else {
				     $mode = "";
				 }                       
					
				 $this->display_tabs();
					
					if ( $action == 'emailsettings' ) {
						// Save the field values
						if ( isset( $_POST[ 'ac_settings_frm' ] ) && $_POST[ 'ac_settings_frm' ] == 'save' ) {
							
						    $ac_settings             = new stdClass();
							$ac_settings->cart_time  = $_POST[ 'cart_abandonment_time' ];
							$woo_ac_settings[]       = $ac_settings;
							$woocommerce_ac_settings = json_encode( $woo_ac_settings );
							
							update_option( 'woocommerce_ac_settings', $woocommerce_ac_settings );
						}
						?>
			
							<?php if ( isset( $_POST[ 'ac_settings_frm' ] ) && $_POST[ 'ac_settings_frm' ] == 'save' ) { ?>
							<div id="message" class="updated fade"><p><strong><?php _e( 'Your settings have been saved.', 'woocommerce-ac' ); ?></strong></p></div>
							<?php } ?>
							
							<?php
								
							     $enable_email_sett_arr = array();
							     $enable_email_sett     = get_option( 'woocommerce_ac_settings' );
							     if ( $enable_email_sett != '' && $enable_email_sett != '{}' && $enable_email_sett != '[]' && $enable_email_sett != 'null' ) {
							         $enable_email_sett_arr = json_decode( $enable_email_sett );
							     }
							         
							?>
							<div id="content">
							  <form method="post" action="" id="ac_settings">
								  <input type="hidden" name="ac_settings_frm" value="save">
								  <div id="poststuff">
										<div class="postbox">
											<h3 class="hndle"><?php _e( 'Settings', 'woocommerce-ac' ); ?></h3>
											<div>
											  <table class="form-table">
			
				    							<tr>
				    								<th>
				    									<label for="woocommerce_ac_email_frequency"><b><?php _e( 'Cart abandoned cut-off time', 'woocommerce-ac' ); ?></b></label>
				    								</th>
				    								<td>
														<?php
														
														$cart_time = "";
														
														if ( count( $enable_email_sett_arr ) > 0 ) {
														
														    if ( ( isset( $enable_email_sett_arr[0]->cart_time ) ) && ( $enable_email_sett_arr[0]->cart_time != '' || $enable_email_sett_arr[0]->cart_time != 'null' ) ) {
														
														        $cart_time = $enable_email_sett_arr[0]->cart_time;
														    
														    } else {
														        
														        $cart_time = 60;
														    }
														} else {														    	
														    $cart_time = 60;
														}
				    									
														?>
				    									<input type="text" name="cart_abandonment_time" id="cart_abandonment_time" size="5" value="<?php echo $cart_time; ?> "> <?php _e( 'minutes', 'woocommerce-ac' );?>
				    									<img class="help_tip" width="16" height="16" data-tip='<?php _e( 'Consider cart abandoned after X minutes of item being added to cart & order not placed', 'woocommerce-ac') ?>' src="<?php echo plugins_url(); ?>/woocommerce/assets/images/help.png" /></p>				    									
				    								</td>
				    							</tr>
				    							
												</table>
											</div>
										</div>
									</div>
							   <p class="submit">
								<input type="submit" name="Submit" class="button-primary" value="<?php esc_attr_e( 'Save Changes', 'woocommerce-ac' ); ?>" />
							   </p>
						    </form>
						  </div>
						<?php 
			      } elseif ( $action == 'listcart' || $action == '' ) {
			?>
						
			<p> <?php _e( 'The list below shows all Abandoned Carts which have remained in cart for a time higher than the "Cart abandoned cut-off time" setting.', 'woocommerce-ac' );?> </p>
			
			<?php

			include_once(  "pagination.class.php");
			 
			/* Find the number of rows returned from a query; Note: Do NOT use a LIMIT clause in this query */
			
			$recoverd_cart = 0;
			$query = "SELECT wpac . * , wpu.user_login, wpu.user_email
					  FROM `".$wpdb->prefix."ac_abandoned_cart_history_lite` AS wpac
					  LEFT JOIN ".$wpdb->base_prefix."users AS wpu ON wpac.user_id = wpu.id
					  WHERE recovered_cart= %d ";
			$results = $wpdb->get_results( $wpdb->prepare( $query, $recoverd_cart ) );
			
            $count = $wpdb->num_rows;
            			
			if( $count > 0 ) {
				$p = new pagination;
				$p->items( $count );
				$p->limit( 10 ); // Limit entries per page
				$p->target( "admin.php?page=woocommerce_ac_page&action=listcart" );
				
                if ( isset( $p->paging ) ) {
                      if ( isset( $_GET[ $p->paging ] ) ) $p->currentPage( $_GET[ $p->paging ] ); // Gets and validates the current page
                }
				$p->calculate(); // Calculates what to show
				$p->parameterName( 'paging' );
				$p->adjacents( 1 ); //No. of page away from the current page
				$p->showCounter( true );
				 
				if( !isset( $_GET[ 'paging' ] ) ) {
					$p->page = 1;
				} else {
					$p->page = $_GET[ 'paging' ];
				}
				 
				//Query for limit paging
				$limit = "LIMIT " . ($p->page - 1) * $p->limit  . ", " . $p->limit;
				 
			} else 
			    $limit = "";
			
			?>
			  
			<div class="tablenav">
			    <div class='tablenav-pages'>
			    	<?php if ( $count > 0 ) echo $p->show();  // Echo out the list of paging. ?>
			    </div>
			</div>
			
			<?php 
			
			$order = "";
            if( isset( $_GET[ 'order' ] ) ){
			      $order = $_GET[ 'order' ];
            }
			if ( $order == "" ) {
				 $order      = "desc";
				 $order_next = "asc";
			}
			elseif ( $order == "asc" ) {
				$order_next = "desc";
			} elseif ( $order == "desc" ) {
				$order_next = "asc";
			}
			
			$order_by = "";
            if( isset( $_GET[ 'orderby' ] ) ){
			      $order_by = $_GET[ 'orderby' ];
            }
            if ( $order_by == "" ) {
				 $order_by = "abandoned_cart_time";
			}
			/* Now we use the LIMIT clause to grab a range of rows */
			
		    $query = "SELECT wpac . * , wpu.user_login, wpu.user_email
					  FROM `".$wpdb->prefix."ac_abandoned_cart_history_lite` AS wpac
					  LEFT JOIN ".$wpdb->base_prefix."users AS wpu ON wpac.user_id = wpu.id
					  WHERE recovered_cart = %d
					  ORDER BY %s %s
					  $limit";
					   
				      $results = $wpdb->get_results( $wpdb->prepare( $query, $recoverd_cart, $order_by, $order ) );
			
			/* From here you can do whatever you want with the data from the $result link. */
			
			$ac_cutoff_time = json_decode(get_option('woocommerce_ac_settings'));
			
			?> 						
            <table class='wp-list-table widefat fixed posts' cellspacing='0' id='cart_data'>
            	<tr>
            	   <th> <?php _e( 'Customer', 'woocommerce-ac' ); ?> </th>
            	   <th> <?php _e( 'Order Total', 'woocommerce-ac' ); ?> </th>
            	   <th scope="col" id="date_ac" class="manage-column column-date_ac sorted <?php echo $order;?>" style="">
            		<a href="admin.php?page=woocommerce_ac_page&action=listcart&orderby=abandoned_cart_time&order=<?php echo $order_next;?>">
            		   <span> <?php _e( 'Date', 'woocommerce-ac' ); ?> </span>
            		   <span class="sorting-indicator"></span>
            		</a>
            	   </th>
            	   <th scope="col" id="status_ac" class="manage-column column-status_ac sorted <?php echo $order;?>" style="">
            		 <a href="admin.php?page=woocommerce_ac_page&action=listcart&orderby=cart_ignored&order=<?php echo $order_next;?>">
            			<span> <?php _e( 'Status', 'woocommerce-ac' ); ?> </span>
            			<span class="sorting-indicator"></span>
            		 </a>
            	   </th>
            	   <th> <?php _e( 'Actions', 'woocommerce-ac' ); ?> </th>
            	</tr>
			
				<?php 
				        $results_guest = '';
				        
				        foreach ( $results as $key => $value ) {
						    
						    if ( $value->user_type == "GUEST" ) {
						        $query_guest = "SELECT * from `". $wpdb->prefix."ac_guest_abandoned_cart_history_lite` WHERE id = %d";
						        $results_guest = $wpdb->get_results( $wpdb->prepare( $query_guest, $value->user_id ) );
						        
						    }
						    $abandoned_order_id = $value->id;
						    $user_id            = $value->user_id;
						    $user_login         = $value->user_login;
						    
        					if ( $value->user_type == "GUEST" ) {
        					    
                                    if ( isset( $results_guest[0]->email_id ) ) $user_email = $results_guest[0]->email_id;
                                    
                                    if ( isset( $results_guest[0]->billing_first_name ) ) $user_first_name = $results_guest[0]->billing_first_name;
                                    else $user_first_name = "";
                                    
                                    if ( isset( $results_guest[0]->billing_last_name ) ) $user_last_name = $results_guest[0]->billing_last_name;
                                    else $user_last_name = "";
                            } else {
                                $user_email = $value->user_email;
                                $user_first_name_temp = get_user_meta($value->user_id, 'first_name');
                                if ( isset( $user_first_name_temp[0] )) $user_first_name = $user_first_name_temp[0];
                                else $user_first_name = "";
                                
                                $user_last_name_temp = get_user_meta($value->user_id, 'last_name');
                                if ( isset( $user_last_name_temp[0] )) $user_last_name = $user_last_name_temp[0];
                                else $user_last_name = "";
                            }
													
							$cart_info          = json_decode( $value->abandoned_cart_info );
							
							$order_date = "";
							$cart_update_time = $value->abandoned_cart_time;
							if ( $cart_update_time != "" && $cart_update_time != 0 ) {
								 $order_date = date( 'd M, Y h:i A', $cart_update_time );
							}
							
							$ac_cutoff_time = json_decode( get_option( 'woocommerce_ac_settings' ) );
							if ( isset( $ac_cutoff_time[0]->cart_time ) ) {
							     $cut_off_time = $ac_cutoff_time[0]->cart_time * 60;
							} else {
							     $cut_off_time = 60 * 60;
							}
							$current_time = current_time( 'timestamp' );							
							$compare_time = $current_time - $cart_update_time;							
							$cart_details = $cart_info->cart;							
							
							$line_total = 0;
							foreach ( $cart_details as $k => $v )
							{
								$line_total = $line_total + $v->line_total;
							}
							
							if( $value->cart_ignored == 0 && $value->recovered_cart == 0 ) {
								$ac_status = "Abandoned";
							}
							elseif( $value->cart_ignored == 1 && $value->recovered_cart == 0 ) {
								$ac_status = "Abandoned but new </br>cart created after this";
							} else {
								$ac_status = "";
							}
							
							?>
							
							<?php 
							if ( $compare_time > $cut_off_time && $ac_status != "" )
							{
							?>
							<tr id="row_<?php echo $abandoned_order_id; ?>">
								<td><strong> <a href="admin.php?page=woocommerce_ac_page&action=orderdetails&id=<?php echo $value->id;?>"><?php echo "Abandoned Order #".$abandoned_order_id;?></a></strong><?php  if( isset( $user_first_name[0] ) && isset( $user_last_name[0] ) ) { $user_name =  $user_first_name." ".$user_last_name; } echo "</br>Name: ".$user_name." <br><a href='mailto:$user_email'>".$user_email."</a>"; ?></td>
								<td><?php echo get_woocommerce_currency_symbol()." ".$line_total; ?></td>
								<td><?php echo $order_date; ?></td>
								<td><?php echo $ac_status; ?>
								<td id="<?php echo $abandoned_order_id; ?>">
								<?php echo "<a href='#' id='$abandoned_order_id-$user_id' class='remove_cart'> <img src='".plugins_url()."/woocommerce-abandoned-cart/images/delete.png' alt='Remove Cart Data' title='Remove Cart Data'></a>"; ?>
								&nbsp;
								
							</tr>
							
							<?php 
							}
						}
						echo "</table>";
					} elseif ( $action == 'emailtemplates' && ( $mode != 'edittemplate' && $mode != 'addnewtemplate' ) ) {
							?>													
							<p> <?php _e( 'Add email templates at different intervals to maximize the possibility of recovering your abandoned carts.', 'woocommerce-ac' );?> </p>
							<?php
							
							// Save the field values
							if ( isset( $_POST[ 'ac_settings_frm' ] ) && $_POST[ 'ac_settings_frm' ] == 'save' ) {	
							    						
								   $active_post = 1;
								   
								   
								if ( $active_post == 1 ) { 								    
								    								
									$is_active       = 1;
									$email_frequency = trim( $_POST[ 'email_frequency' ] );
									$day_or_hour     = trim( $_POST[ 'day_or_hour' ] );
									
								    $check_query = "SELECT * FROM `".$wpdb->prefix."ac_email_templates_lite`
													WHERE is_active = %s 
	                                                AND frequency   = %d 
	                                                AND day_or_hour = %s ";
								    $check_results = $wpdb->get_results( $wpdb->prepare( $check_query, $is_active, $email_frequency, $day_or_hour ) );
								    
									
									if ( count( $check_results ) == 0 ) {
									    
									     $active_post = 1;
									     $woocommerce_ac_email_subject = trim( $_POST[ 'woocommerce_ac_email_subject' ] );
									     $woocommerce_ac_email_body    = trim( $_POST[ 'woocommerce_ac_email_body' ] );
									     $woocommerce_ac_template_name = trim( $_POST[ 'woocommerce_ac_template_name' ] );
									     $woocommerce_ac_from_name     = trim( $_POST[ 'woocommerce_ac_from_name' ] );
									     
									     $query = "INSERT INTO `".$wpdb->prefix."ac_email_templates_lite`
										           (subject, body, is_active, frequency, day_or_hour, template_name, from_name)
										           VALUES ( %s, %s, %s, %d, %s, %s, %s )";
    												
									    $wpdb->query( $wpdb->prepare( $query, 
									                                  $woocommerce_ac_email_subject,
									                                  $woocommerce_ac_email_body, 
									                                  $active_post, 
									                                  $email_frequency, 
									                                  $day_or_hour, 
									                                  $woocommerce_ac_template_name, 
									                                  $woocommerce_ac_from_name )
									     );
									      
									}
									else {
									    
									    $update_is_active = 0;
									    $query_update = "UPDATE `".$wpdb->prefix."ac_email_templates_lite`
										                 SET
										                 is_active       = %s
										                 WHERE frequency = %d
	                                                     AND day_or_hour = %s ";
									    $wpdb->query($wpdb->prepare( $query_update, $update_is_active, $email_frequency, $day_or_hour ) );
									    
									    $woocommerce_ac_email_subject = trim( $_POST[ 'woocommerce_ac_email_subject' ] );
									    $woocommerce_ac_email_body    = trim( $_POST[ 'woocommerce_ac_email_body' ] );
									    $woocommerce_ac_template_name = trim( $_POST[ 'woocommerce_ac_template_name' ] );
									    $woocommerce_ac_from_name     = trim( $_POST[ 'woocommerce_ac_from_name' ] );
									    
									    $query_insert_new = "INSERT INTO `".$wpdb->prefix."ac_email_templates_lite`
										                    (subject, body, is_active, frequency, day_or_hour, template_name, from_name)
										                    VALUES ( %s, %s, %s, %d, %s, %s, %s )";
    												
									    $wpdb->query( $wpdb->prepare( $query_insert_new, 
									                                  $woocommerce_ac_email_subject,
									                                  $woocommerce_ac_email_body, 
									                                  $active_post, 
									                                  $email_frequency, 
									                                  $day_or_hour, 
									                                  $woocommerce_ac_template_name, 
									                                  $woocommerce_ac_from_name )
									     );
									}
								}
							}
							
							if ( isset( $_POST[ 'ac_settings_frm' ] ) && $_POST[ 'ac_settings_frm' ] == 'update' )
							{
								$active = 1;
								if ( $active == 1 )
								{   
								    $is_active       = 1;
								    $email_frequency = trim( $_POST[ 'email_frequency' ] );
								    $day_or_hour     = trim( $_POST[ 'day_or_hour' ] );
								    $check_query = "SELECT * FROM `".$wpdb->prefix."ac_email_templates_lite`
									                WHERE is_active= %s 
						                            AND frequency  = %d 
						                            AND day_or_hour= %s ";
								    $check_results = $wpdb->get_results( $wpdb->prepare( $check_query, $is_active, $email_frequency, $day_or_hour ) );
								    
									if (count($check_results) == 0 )
									{
                                        
									    $woocommerce_ac_email_subject = trim( $_POST[ 'woocommerce_ac_email_subject' ] );
									    $woocommerce_ac_email_body    = trim( $_POST[ 'woocommerce_ac_email_body' ] );									    
									    $woocommerce_ac_template_name = trim( $_POST[ 'woocommerce_ac_template_name' ] );
									    $woocommerce_ac_from_name     = trim( $_POST[ 'woocommerce_ac_from_name' ] );
									    $id                           = trim( $_POST[ 'id' ] );
									    
									    $query_update = "UPDATE `".$wpdb->prefix."ac_email_templates_lite`
										                SET
                										subject       = %s,
                										body          = %s,
                										is_active     = %s, 
				                                        frequency     = %d,
                										day_or_hour   = %s,
                										template_name = %s,
                										from_name     = %s
                										WHERE id      = %d ";
									    $wpdb->query($wpdb->prepare( $query_update,
									                                 $woocommerce_ac_email_subject,
									                                 $woocommerce_ac_email_body,
									                                 $active,
                        									         $email_frequency,
                        									         $day_or_hour,
                        									         $woocommerce_ac_template_name,
                        									         $woocommerce_ac_from_name,
                        									         $id )
									        
									     );
									}
									else {
									    
									    $updated_is_active = 0;
									    $query_update_new = "UPDATE `".$wpdb->prefix."ac_email_templates_lite`
										                     SET is_active   = %s
										                     WHERE frequency = %d
			                                                 AND day_or_hour = %s ";
									    $wpdb->query( $wpdb->prepare( $query_update_new, $updated_is_active, $email_frequency, $day_or_hour ) );
									    
									    $woocommerce_ac_email_subject = trim( $_POST[ 'woocommerce_ac_email_subject' ] );
									    $woocommerce_ac_email_body    = trim( $_POST[ 'woocommerce_ac_email_body' ] );									    
									    $woocommerce_ac_template_name = trim( $_POST[ 'woocommerce_ac_template_name' ] );
									    $woocommerce_ac_from_name     = trim( $_POST[ 'woocommerce_ac_from_name' ] );
									    $id                           = trim( $_POST[ 'id' ] );
									    
									    $query_update_latest = "UPDATE `".$wpdb->prefix."ac_email_templates_lite`
										                SET
                										subject       = %s,
                										body          = %s,
                										is_active     = %s, 
				                                        frequency     = %d,
                										day_or_hour   = %s,
                										template_name = %s,
                										from_name     = %s
                										WHERE id      = %d ";
									    $wpdb->query($wpdb->prepare( $query_update_latest,
									                                 $woocommerce_ac_email_subject,
									                                 $woocommerce_ac_email_body,
									                                 $active,
                        									         $email_frequency,
                        									         $day_or_hour,
                        									         $woocommerce_ac_template_name,
                        									         $woocommerce_ac_from_name,
                        									         $id )
									        
									     );
									    
									}
								}
							}
							
							if ( $action == 'emailtemplates' && $mode == 'removetemplate' ){
								$id_remove = $_GET[ 'id' ];
								
								$query_remove = "DELETE FROM `".$wpdb->prefix."ac_email_templates_lite` WHERE id= %d ";
								$wpdb->query( $wpdb->prepare( $query_remove, $id_remove ) );
							}
							
							if ( isset( $_POST[ 'ac_settings_frm' ] ) && $_POST[ 'ac_settings_frm' ] == 'save' ) { ?>
							<div id="message" class="updated fade"><p><strong><?php _e( 'The Email Template has been successfully added.', 'woocommerce-ac' ); ?></strong></p></div>
							<?php } 
							if ( isset( $_POST[ 'ac_settings_frm' ] ) && $_POST[ 'ac_settings_frm' ] == 'update' ) { ?>
							<div id="message" class="updated fade"><p><strong><?php _e( 'The Email Template has been successfully updated.', 'woocommerce-ac' ); ?></strong></p></div>
							<?php }?>
							
							<div class="tablenav">
							<p style="float:left;">
							<input type="button" value="+ Add New Template" id="add_new_template" onclick="location.href='admin.php?page=woocommerce_ac_page&action=emailtemplates&mode=addnewtemplate';" style="font-weight: bold; color: green; font-size: 18px; cursor: pointer;">							
							</p>
							
				<?php
				include_once( "pagination.class.php" ); 
				 
				/* Find the number of rows returned from a query; Note: Do NOT use a LIMIT clause in this query */
				$wpdb->get_results( "SELECT wpet . *   
									 FROM `".$wpdb->prefix."ac_email_templates_lite` AS wpet  
								   "); 
                                
                $count = $wpdb->num_rows;

				if( $count > 0 ) {
					$p = new pagination;
					$p->items( $count );
					$p->limit( 10 ); // Limit entries per page
					$p->target( "admin.php?page=woocommerce_ac_page&action=emailtemplates" );
					if ( isset( $p->paging ) ) {
						if ( isset( $_GET[ $p->paging ] ) ){
						    $p->currentPage( $_GET[ $p->paging ] ); // Gets and validates the current page
					    }
					}    
					$p->calculate(); // Calculates what to show
					$p->parameterName( 'paging' );
					$p->adjacents( 1 ); //No. of page away from the current page
					$p->showCounter( true );
						
					if( !isset( $_GET[ 'paging' ] ) ) {
						$p->page = 1;
					} else {
						$p->page = $_GET[ 'paging' ];
					}
						
					//Query for limit paging
					$limit = "LIMIT " . ( $p->page - 1 ) * $p->limit  . ", " . $p->limit;
						
				} 
                else $limit = "";
					
				?>
							  
				    <div class='tablenav-pages'>
				    	<?php if ( $count>0 ) echo $p->show();  // Echo out the list of paging. ?>
				    </div>
				</div>
				
				<?php 

				$order = "";
				if ( isset( $_GET[ 'order' ] ) ) {
				    $order = $_GET[ 'order' ];
				}    
				if ( $order == "" ) {
					$order      = "asc";
					$order_next = "desc";
				} elseif ( $order == "asc" ) {
					$order_next = "desc";
				} elseif ( $order == "desc" ) {
					$order_next = "asc";
				}
					
				$order_by = "";
				if ( isset($_GET[ 'orderby' ] ) ) {
				     $order_by = $_GET[ 'orderby' ];
				}     
				if ( $order_by == "" ) {
					 $order_by = "frequency";
				}
				
				$query = "SELECT wpet . *
						  FROM `".$wpdb->prefix."ac_email_templates_lite` AS wpet
						  ORDER BY %s %s
						  $limit";
				$results = $wpdb->get_results($wpdb->prepare( $query, $order_by, $order ) );
				/* From here you can do whatever you want with the data from the $result link. */
				?> 
		
			            <table class='wp-list-table widefat fixed posts' cellspacing='0' id='email_templates'>
						<tr>
							<th> <?php _e( 'Sr', 'woocommerce-ac' ); ?> </th>
							<th scope="col" id="temp_name" class="manage-column column-temp_name sorted <?php echo $order;?>" style="">
								<a href="admin.php?page=woocommerce_ac_page&action=emailtemplates&orderby=template_name&order=<?php echo $order_next;?>">
									<span> <?php _e( 'Template Name', 'woocommerce-ac' ); ?> </span>
									<span class="sorting-indicator"></span>
								</a>
							</th>
							<th scope="col" id="sent" class="manage-column column-sent sorted <?php echo $order;?>" style="">
								<a href="admin.php?page=woocommerce_ac_page&action=emailtemplates&orderby=frequency&order=<?php echo $order_next;?>">
									<span> <?php _e( 'Sent', 'woocommerce-ac' ); ?> </span>
									<span class="sorting-indicator"></span>
								</a>
							</th>
							<th> <?php _e( 'Active ?', 'woocommerce-ac' ); ?> </th>
							<th> <?php _e( 'Actions', 'woocommerce-ac' ); ?> </th>
						</tr>
							
							<?php 
						if ( isset( $_GET[ 'pageno' ] ) ){
						     $add_var = ($_GET['pageno'] - 1) * $limit; 
						} else {
						     $add_var = "";
						}    
						$i = 1 + $add_var;
						foreach ( $results as $key => $value )
						{
								$id = $value->id;
								
								$is_active = $value->is_active;
								if ( $is_active == '1' )
								{
									$active = "Yes";
								}
								else
								{
									$active = "No";
								}
								$frequency   = $value->frequency;
								$day_or_hour = $value->day_or_hour;
								?>
			
								<tr id="row_<?php echo $id; ?>">
								   <td><?php echo $i; ?></td>
								   <td><?php echo $value->template_name; ?></td>
								   <td><?php echo $frequency." ".$day_or_hour." After Abandonment";?></td>
								   <td><?php echo $active; ?></td>
								   <td>
									<a href="admin.php?page=woocommerce_ac_page&action=emailtemplates&mode=edittemplate&id=<?php echo $id; ?>"> <img src="<?php echo plugins_url(); ?>/woocommerce-abandoned-cart/images/edit.png" alt="Edit" title="Edit" width="20" height="20"> </a>&nbsp;
									<a href="#" onclick="delete_email_template( <?php echo $id; ?> )" > <img src="<?php echo plugins_url(); ?>/woocommerce-abandoned-cart/images/delete.png" alt="Delete" title="Delete" width="20" height="20"> </a>&nbsp;
								   </td>											
							    </tr>
			
							<?php 
							$i++;
						}
						echo "</table>";
			
					}
					elseif ($action == 'stats' || $action == '')
					{						
						?>
						<p>
						<script language='javascript'>
						jQuery( document ).ready( function()
						{
							jQuery( '#duration_select' ).change( function()
							{
								var group_name = jQuery( '#duration_select' ).val();
								var today      = new Date();
								var start_date = "";
								var end_date   = "";
								if ( group_name == "yesterday" )
								{
									start_date = new Date( today.getFullYear(), today.getMonth(), today.getDate() - 1 );
									end_date   = new Date( today.getFullYear(), today.getMonth(), today.getDate() - 1 );
								}
								else if ( group_name == "today")
								{
									start_date = new Date( today.getFullYear(), today.getMonth(), today.getDate() );
									end_date   = new Date( today.getFullYear(), today.getMonth(), today.getDate() );
								}
								else if ( group_name == "last_seven" )
								{
									start_date = new Date( today.getFullYear(), today.getMonth(), today.getDate() - 7 );
									end_date   = new Date( today.getFullYear(), today.getMonth(), today.getDate() );
								}
								else if ( group_name == "last_fifteen" )
								{
									start_date = new Date( today.getFullYear(), today.getMonth(), today.getDate() - 15 );
									end_date   = new Date( today.getFullYear(), today.getMonth(), today.getDate() );
								}
								else if ( group_name == "last_thirty" )
								{
									start_date = new Date( today.getFullYear(), today.getMonth(), today.getDate() - 30 );
									end_date   = new Date( today.getFullYear(), today.getMonth(), today.getDate() );
								}
								else if ( group_name == "last_ninety" )
								{
									start_date = new Date( today.getFullYear(), today.getMonth(), today.getDate() - 90 );
									end_date   = new Date( today.getFullYear(), today.getMonth(), today.getDate() );
								}
								else if ( group_name == "last_year_days" )
								{
									start_date = new Date( today.getFullYear(), today.getMonth(), today.getDate() - 365 );
									end_date   = new Date( today.getFullYear(), today.getMonth(), today.getDate() );
								}
		
								var monthNames = [ "Jan", "Feb", "Mar", "Apr", "May", "Jun",
								                   "Jul", "Aug", "Sep", "Oct", "Nov", "Dec" ];
								               
								var start_date_value = start_date.getDate() + " " + monthNames[start_date.getMonth()] + " " + start_date.getFullYear();
								var end_date_value   = end_date.getDate() + " " + monthNames[end_date.getMonth()] + " " + end_date.getFullYear();
		
								jQuery( '#start_date' ).val( start_date_value );
								jQuery( '#end_date' ).val( end_date_value );
								
							});
						});
						</script>
						<?php
						
						if ( isset( $_POST[ 'duration_select' ] ) ){
						       $duration_range = $_POST['duration_select'];
						} else {
						       $duration_range = "";
						}       
						if ( $duration_range == "" ) {
							if ( isset( $_GET[ 'duration_select' ] ) ){
							    $duration_range = $_GET[ 'duration_select' ];
							}    
						}
						if ($duration_range == "") $duration_range = "last_seven";
						
						
						_e( 'The Report below shows how many Abandoned Carts we were able to recover for you by sending automatic emails to encourage shoppers.', 'woocommerce-ac');
						?>
						<div id="recovered_stats" class="postbox" style="display:block">
						
							<div class="inside">
							<form method="post" action="admin.php?page=woocommerce_ac_page&action=stats" id="ac_stats">
							<select id="duration_select" name="duration_select" >
							<?php
							foreach ( $this->duration_range_select as $key => $value )
							{
								$sel = "";
								if ($key == $duration_range) {
									$sel = " selected ";
								} 
								echo"<option value='$key' $sel> $value </option>";
							}
							
							$date_sett = $this->start_end_dates[ $duration_range ];
							
							?>
							</select>
							
							<script type="text/javascript">
							jQuery( document ).ready( function()
							{
    							var formats = [ "d.m.y", "d M yy","MM d, yy" ];
    							jQuery( "#start_date" ).datepicker( { dateFormat: formats[ 1 ] } );
							});
			
							jQuery( document ).ready( function()
							{
    							var formats = [ "d.m.y", "d M yy","MM d, yy" ];
    							jQuery( "#end_date" ).datepicker( { dateFormat: formats[ 1 ] } );
							});
							</script>
														
							<?php 
			
							if ( isset( $_POST[ 'start_date' ] ) ){
							       $start_date_range = $_POST[ 'start_date' ];
							} else {
							       $start_date_range = "";
							}       
							if ( $start_date_range == "" ){
								 $start_date_range = $date_sett[ 'start_date' ];
							}
							if ( isset( $_POST[ 'end_date' ] ) ){
							       $end_date_range = $_POST[ 'end_date' ];
							} else {
							       $end_date_range = "";
							}       
							if ( $end_date_range == "" ){
								 $end_date_range = $date_sett[ 'end_date' ];
							}
							
							?>
							
							<label class="start_label" for="start_day"> <?php _e( 'Start Date:', 'woocommerce-ac' ); ?> </label>
							<input type="text" id="start_date" name="start_date" readonly="readonly" value="<?php echo $start_date_range; ?>"/>
							 
							<label class="end_label" for="end_day"> <?php _e( 'End Date:', 'woocommerce-ac' ); ?> </label>
							<input type="text" id="end_date" name="end_date" readonly="readonly" value="<?php echo $end_date_range; ?>"/>
							
							<input type="submit" name="Submit" class="button-primary" value="<?php esc_attr_e( 'Go', 'woocommerce-ac' ); ?>"  />
							</form>
							</div>
						</div>
						<?php 
						
						global $wpdb;
						$start_date = strtotime( $start_date_range." 00:01:01" );
						$end_date   = strtotime( $end_date_range." 23:59:59" );
						
						include_once( "pagination.class.php" );
						
						/* Find the number of rows returned from a query; Note: Do NOT use a LIMIT clause in this query */
					             $recoverd_cart = 0;      
                                 $wpdb->get_results( $wpdb->prepare( "SELECT * FROM " . $wpdb->prefix . "ac_abandoned_cart_history_lite
								                                      WHERE abandoned_cart_time >= %d
								                                      AND abandoned_cart_time <= %d
								                                      AND recovered_cart > %d 
								                                    ",$start_date,$end_date,$recoverd_cart ) );
                                 $count = $wpdb->num_rows;
						
						if ( $count > 0 ) {
							$p = new pagination;
							$p->items( $count );
							$p->limit( 10 ); // Limit entries per page
							$p->target( "admin.php?page=woocommerce_ac_page&action=stats&duration_select=$duration_range" );
							
                            if ( isset( $p->paging ) ) {
                                if ( isset( $_GET[ $p->paging ] ) ) $p->currentPage( $_GET[$p->paging ] ); // Gets and validates the current page
                            }
							$p->calculate(); // Calculates what to show
							$p->parameterName( 'paging' );
							$p->adjacents( 1 ); //No. of page away from the current page
							$p->showCounter( true );
						
							if ( !isset( $_GET[ 'paging' ] ) ) {
								$p->page = 1;
							} else {
								$p->page = $_GET[ 'paging' ];
							}
						    //Query for limit paging
							$limit = "LIMIT " . ($p->page - 1) * $p->limit  . ", " . $p->limit;						
						}
						else
							$limit = "";	
						?>
															  
						<div class="tablenav">
						    <div class='tablenav-pages'>
						    	<?php if ( $count>0 ) echo $p->show();  // Echo out the list of paging. ?>
						    </div>
						</div>
						
						<?php 
						
						$order = "";
						if ( isset( $_GET[ 'order' ] ) ){
						       $order = $_GET[ 'order' ];
						}       
						if ( $order == "" ){
							 $order      = "desc";
							 $order_next = "asc";
						} elseif ( $order == "asc" ){
							 $order_next = "desc";
						} elseif ( $order == "desc" )
						{
							 $order_next = "asc";
						}
						
						$order_by = "";
						if ( isset( $_GET[ 'orderby' ] ) ){
						       $order_by = $_GET[ 'orderby' ];
						}       
						if ( $order_by == "" ){
							   $order_by = "recovered_cart";
						}
						
						$recoverd_cart = 0;
						$query_ac = "SELECT * FROM " . $wpdb->prefix . "ac_abandoned_cart_history_lite
									 WHERE abandoned_cart_time >= %d
									 AND abandoned_cart_time <= %d
									 AND recovered_cart > %d
								     ORDER BY  %s %s $limit";
						$ac_results = $wpdb->get_results( $wpdb->prepare( $query_ac, $start_date, $end_date, $recoverd_cart, $order_by,$order ) );						
												
						$query_ac_carts = "SELECT * FROM " . $wpdb->prefix . "ac_abandoned_cart_history_lite
										   WHERE abandoned_cart_time >= %d
									 	   AND abandoned_cart_time <= %d ";
						$ac_carts_results = $wpdb->get_results($wpdb->prepare($query_ac_carts, $start_date, $end_date) );												
						
						$recovered_item = $recovered_total = $count_carts = $total_value = $order_total = 0;
						foreach ( $ac_carts_results as $key => $value )
						{
							 							
							{
								$count_carts += 1;
									
								$cart_detail     = json_decode( $value->abandoned_cart_info );
								$product_details = $cart_detail->cart;
								
								$line_total = 0;
								foreach ( $product_details as $k => $v )
								{
									$line_total = $line_total + $v->line_total;
								}
								
								$total_value += $line_total;
							}
						}
						$table_data = "";
						foreach ( $ac_results as $key => $value )
						{	
							if( $value->recovered_cart != 0 )
							{
								$recovered_id       = $value->recovered_cart;
								$rec_order          = get_post_meta( $recovered_id );
								$woo_order          = new WC_Order( $recovered_id );
								$recovered_date     = strtotime( $woo_order->order_date );
								$recovered_date_new = date( 'd M, Y h:i A', $recovered_date );
								$recovered_item     += 1;
								if ( isset( $rec_order[ '_order_total' ][ 0 ] ) ) {							
								$recovered_total    += $rec_order[ '_order_total' ][ 0 ];
								}
								
								$abandoned_date            = date( 'd M, Y h:i A', $value->abandoned_cart_time );								
								$abandoned_order_id        = $value->id;                                                                
                                $billing_first_name        = $billing_last_name = $billing_email = ''; 
								$recovered_order_total     = 0;
								if ( isset( $rec_order[ '_billing_first_name' ][ 0 ] ) ) {
									$billing_first_name    = $rec_order[ '_billing_first_name' ][ 0 ];
								}
								if ( isset( $rec_order[ '_billing_last_name' ][ 0 ] ) ) {
									$billing_last_name     = $rec_order[ '_billing_last_name' ][ 0 ];
								}
								if ( isset( $rec_order[ '_billing_email' ][ 0 ] ) ) {
									$billing_email         = $rec_order[ '_billing_email' ][ 0 ];
								}
								if ( isset( $rec_order[ '_order_total' ][ 0 ] ) ) {
									$recovered_order_total = $rec_order[ '_order_total' ][ 0 ];
								}
								
								$table_data .="<tr>
											  <td>Name: ".$billing_first_name." ".$billing_last_name."</br><a href='mailto:'".$billing_email."'>".$billing_email."</td>
											  <td>".$abandoned_date."</td>
											  <td>".$recovered_date_new."</td>
											  <td>".get_woocommerce_currency_symbol()." ".$recovered_order_total."</td>
											  <td> <a href=\"post.php?post=". $recovered_id."&action=edit\">View Details</td>";
							}
						}
						
						?>
						<div id="recovered_stats" class="postbox" style="display:block">
						<div class="inside" >
						  <p style="font-size: 15px"> <?php _e('During the selected range', 'woocommerce-ac');?> <strong> <?php echo $count_carts; ?> </strong> <?php _e('carts totaling', 'woocommerce-ac');?> <strong> <?php echo get_woocommerce_currency_symbol()." ".$total_value; ?> </strong> <?php _e('were abandoned. We were able to recover', 'woocommerce-ac');?> <strong> <?php echo $recovered_item; ?> </strong> <?php _e('of them, which led to an extra', 'woocommerce-ac');?> <strong> <?php echo get_woocommerce_currency_symbol()." ".$recovered_total; ?> </strong> <?php _e('in sales', 'woocommerce-ac');?></p>
						</div>
						</div>
						
						<table class='wp-list-table widefat fixed posts' cellspacing='0' id='cart_data'>
												<tr>
												<th> <?php _e( 'Customer', 'woocommerce-ac' ); ?> </th>
												<th scope="col" id="created_date" class="manage-column column-created_date sorted <?php echo $order;?>" style="">
													<a href="admin.php?page=woocommerce_ac_page&action=stats&orderby=abandoned_cart_time&order=<?php echo $order_next;?>&durationselect=<?php echo $duration_range;?>">
														<span> <?php _e( 'Created On', 'woocommerce-ac' ); ?> </span>
														<span class="sorting-indicator"></span>
													</a>
												</th>
												<th scope="col" id="rec_order" class="manage-column column-rec_order sorted <?php echo $order;?>" style="">
													<a href="admin.php?page=woocommerce_ac_page&action=stats&orderby=recovered_cart&order=<?php echo $order_next;?>&durationselect=<?php echo $duration_range;?>">
														<span> <?php _e( 'Recovered Date', 'woocommerce-ac' ); ?> </span>
														<span class="sorting-indicator"></span>
													</a>
												</th>
												<th> <?php _e( 'Order Total', 'woocommerce-ac' ); ?> </th>
												<th></th>
												</tr>
						<?php
						echo $table_data;
						print ('</table>');
					}elseif ( $action == 'orderdetails' ) {
                            $ac_order_id = $_GET['id'];
                            ?>
                            <p> </p>
                            <div id="ac_order_details" class="postbox" style="display:block">
                            <h3> <p> <?php _e( "Abandoned Order #$ac_order_id Details", "woocommerce-ac" ); ?> </p> </h3>
                                <div class="inside">
                                    <table cellpadding="0" cellspacing="0" class="wp-list-table widefat fixed posts">
                                    <tr>
                                    <th> <?php _e( 'Item', 'woocommerce-ac' ); ?> </th>
                                    <th> <?php _e( 'Id', 'woocommerce-ac' ); ?> </th>
                                    <th> <?php _e( 'Name', 'woocommerce-ac' ); ?> </th>
                                    <th> <?php _e( 'Quantity', 'woocommerce-ac' ); ?> </th>
                                    <th> <?php _e( 'Line Subtotal', 'woocommerce-ac' ); ?> </th>
                                    <th> <?php _e( 'Line Total', 'woocommerce-ac' ); ?> </th>
                                    </tr>                                           
                    <?php 
                    $query = "SELECT * FROM `".$wpdb->prefix."ac_abandoned_cart_history_lite` WHERE id = %d ";
                    $results = $wpdb->get_results( $wpdb->prepare( $query,$_GET['id'] ) );                         
                    $shipping_charges = 0;
                    $currency_symbol = get_woocommerce_currency_symbol();

                            if ( $results[0]->user_type == "GUEST" ) {
                                $query_guest            = "SELECT * FROM `".$wpdb->prefix."ac_guest_abandoned_cart_history_lite` WHERE id = %d";  
                                $results_guest          = $wpdb->get_results( $wpdb->prepare( $query_guest, $results[0]->user_id ) );
                                $user_email             = $results_guest[0]->email_id;
                                $user_first_name        = $results_guest[0]->billing_first_name;
                                $user_last_name         = $results_guest[0]->billing_last_name;
                                $user_billing_postcode  = $results_guest[0]->billing_zipcode;
                                $user_shipping_postcode = $results_guest[0]->shipping_zipcode;
                                $shipping_charges       = $results_guest[0]->shipping_charges;
                                $user_billing_company   = $user_billing_address_1 = $user_billing_address_2 = $user_billing_city = $user_billing_state = $user_billing_country  = $user_billing_phone = "";
                                $user_shipping_company  = $user_shipping_address_1 = $user_shipping_address_2 = $user_shipping_city = $user_shipping_state = $user_shipping_country = "";  
                            } else {
                                $user_id = $results[0]->user_id;                                
                                if ( isset( $results[0]->user_login ) ) $user_login = $results[0]->user_login;
                                $user_email = get_user_meta( $results[0]->user_id, 'billing_email', true );
                                
                                $user_first_name_temp = get_user_meta( $results[0]->user_id, 'first_name');
                                if ( isset( $user_first_name_temp[0] ) ) $user_first_name = $user_first_name_temp[0];
                                else $user_first_name = "";
                                
                                $user_last_name_temp = get_user_meta($results[0]->user_id, 'last_name');
                                if ( isset( $user_last_name_temp[0] ) ) $user_last_name = $user_last_name_temp[0];
                                else $user_last_name = "";
                                
                                $user_billing_first_name = get_user_meta( $results[0]->user_id, 'billing_first_name' );
                                $user_billing_last_name = get_user_meta( $results[0]->user_id, 'billing_last_name' );
                                
                                $user_billing_company_temp = get_user_meta( $results[0]->user_id, 'billing_company' );
                                if ( isset( $user_billing_company_temp[0] ) ) $user_billing_company = $user_billing_company_temp[0];
                                else $user_billing_company = "";
                                
                                $user_billing_address_1_temp = get_user_meta( $results[0]->user_id, 'billing_address_1' );
                                if ( isset( $user_billing_address_1_temp[0] ) ) $user_billing_address_1 = $user_billing_address_1_temp[0];
                                else $user_billing_address_1 = "";
                                
                                $user_billing_address_2_temp = get_user_meta( $results[0]->user_id, 'billing_address_2' );
                                if ( isset( $user_billing_address_2_temp[0] ) ) $user_billing_address_2 = $user_billing_address_2_temp[0];
                                else $user_billing_address_2 = "";
                                
                                $user_billing_city_temp = get_user_meta( $results[0]->user_id, 'billing_city' );
                                if ( isset( $user_billing_city_temp[0] ) ) $user_billing_city = $user_billing_city_temp[0];
                                else $user_billing_city = "";
                                
                                $user_billing_postcode_temp = get_user_meta( $results[0]->user_id, 'billing_postcode' );
                                if ( isset( $user_billing_postcode_temp[0] ) ) $user_billing_postcode = $user_billing_postcode_temp[0];
                                else $user_billing_postcode = "";
                                
                                $user_billing_state_temp = get_user_meta( $results[0]->user_id, 'billing_state' );
                                if ( isset( $user_billing_state_temp[0] ) ) $user_billing_state = $user_billing_state_temp[0];
                                else $user_billing_state = "";
                                
                                $user_billing_country_temp = get_user_meta( $results[0]->user_id, 'billing_country' );
                                if ( isset( $user_billing_country_temp[0] ) ) $user_billing_country = $user_billing_country_temp[0];
                                else $user_billing_country = "";
                                
                                $user_billing_phone_temp = get_user_meta( $results[0]->user_id, 'billing_phone' );
                                if ( isset( $user_billing_phone_temp[0] ) ) $user_billing_phone = $user_billing_phone_temp[0];
                                else $user_billing_phone = "";
                                
                                $user_shipping_first_name = get_user_meta( $results[0]->user_id, 'shipping_first_name' );
                                $user_shipping_last_name = get_user_meta( $results[0]->user_id, 'shipping_last_name' );
                                
                                $user_shipping_company_temp = get_user_meta( $results[0]->user_id, 'shipping_company' );
                                if ( isset( $user_shipping_company_temp[0] ) ) $user_shipping_company = $user_shipping_company_temp[0];
                                else $user_shipping_company = "";
                                
                                $user_shipping_address_1_temp = get_user_meta( $results[0]->user_id, 'shipping_address_1' );
                                if ( isset( $user_shipping_address_1_temp[0] ) ) $user_shipping_address_1 = $user_shipping_address_1_temp[0];
                                else $user_shipping_address_1 = "";
                                
                                $user_shipping_address_2_temp = get_user_meta( $results[0]->user_id, 'shipping_address_2' );
                                if ( isset( $user_shipping_address_2_temp[0] ) ) $user_shipping_address_2 = $user_shipping_address_2_temp[0];
                                else $user_shipping_address_2 = "";
                                
                                $user_shipping_city_temp = get_user_meta( $results[0]->user_id, 'shipping_city' );
                                if ( isset( $user_shipping_city_temp[0] ) ) $user_shipping_city = $user_shipping_city_temp[0];
                                else $user_shipping_city = "";
                                
                                $user_shipping_postcode_temp = get_user_meta( $results[0]->user_id, 'shipping_postcode' );
                                if ( isset( $user_shipping_postcode_temp[0] ) ) $user_shipping_postcode = $user_shipping_postcode_temp[0];
                                else $user_shipping_postcode = "";
                                
                                $user_shipping_state_temp = get_user_meta( $results[0]->user_id, 'shipping_state' );
                                if ( isset( $user_shipping_state_temp[0] ) ) $user_shipping_state = $user_shipping_state_temp[0];
                                else $user_shipping_state = "";
                                
                                $user_shipping_country_temp = get_user_meta( $results[0]->user_id, 'shipping_country' );
                                if ( isset( $user_shipping_country_temp[0] ) ) $user_shipping_country = $user_shipping_country_temp[0];
                                else $user_shipping_country = "";
                            }                            
                            $cart_info      = json_decode( $results[0]->abandoned_cart_info );
                            $cart_details   = $cart_info->cart;
                            $item_subtotal  = $item_total = 0;
                            
                            foreach ( $cart_details as $k => $v ) {
                                $quantity_total = $v->quantity;
                                $product_id     = $v->product_id;
                                $prod_name      = get_post($product_id);
                                $product_name   = $prod_name->post_title;
                                
                                // Item subtotal is calculated as product total including taxes
                                if ( $v->line_subtotal_tax != 0 && $v->line_subtotal_tax > 0 ) {
                                    $item_subtotal = $item_subtotal + $v->line_total + $v->line_subtotal_tax;
                                } else {
                                    $item_subtotal = $item_subtotal + $v->line_total;
                                }

                        //  Line total
                        $item_total = $item_subtotal;
                        $item_subtotal = $item_subtotal / $quantity_total;
                        $item_total = number_format( $item_total, 2 );
                        $item_subtotal = number_format( $item_subtotal, 2 );                               
                        $product = get_product( $product_id );
                        $prod_image = $product->get_image();
                    ?>                   
                        <tr>
                        <td> <?php echo $prod_image; ?></td>
                        <td> <?php echo $product->id; ?> </td>
                        <td> <?php echo $product_name; ?></td>
                        <td> <?php echo $quantity_total; ?></td>
                        <td> <?php echo get_woocommerce_currency_symbol()." ".$item_subtotal; ?></td>
                        <td> <?php echo get_woocommerce_currency_symbol()." ".$item_total; ?></td>
                        </tr>
                            
                <?php 
                $item_subtotal = $item_total = 0;
                    }
                      ?>
                    </table>
                        </div>  
                            </div>
                            <div id="ac_order_customer_details" class="postbox" style="display:block">
                            <h3> <p> <?php _e( 'Customer Details' , 'woocommerce-ac' ); ?> </p> </h3>
                            <div class="inside" style="height: 300px;" >                                       
                            <div id="order_data" class="panel">
                            <div style="width:500px;float:left">
                            <h3> <p> <?php _e( 'Billing Details' , 'woocommerce-ac' ); ?> </p> </h3>
                                <p> <strong> <?php _e( 'Name:' , 'woocommerce-ac' ); ?> </strong>
                                <?php echo $user_first_name." ".$user_last_name;?>
                                </p>                                    
                                    <p> <strong> <?php _e( 'Address:' , 'woocommerce-ac' ); ?> </strong>
                                    <?php echo $user_billing_company."</br>".
                                               $user_billing_address_1."</br>".
                                               $user_billing_address_2."</br>".
                                               $user_billing_city."</br>".
                                               $user_billing_postcode."</br>".
                                               $user_billing_state."</br>".
                                               $user_billing_country."</br>";
                                               ?> 
                                    </p>                                        
                                    <p> <strong> <?php _e( 'Email:', 'woocommerce-ac' ); ?> </strong>
                                    <a href='mailto:$user_email'><?php echo $user_email;?> </a>
                                    </p>                                            
                                    <p> <strong> <?php _e( 'Phone:', 'woocommerce-ac' ); ?> </strong>
                                    <?php echo $user_billing_phone;?>
                                    </p>
                                        </div>                                                                                   
                                        <div style="width:500px;float:right">
                                        <h3> <p> <?php _e( 'Shipping Details', 'woocommerce-ac' ); ?> </p> </h3>                                       
                                    <p> <strong> <?php _e( 'Address:', 'woocommerce-ac' ); ?> </strong>
                                    
                                    <?php 
                                        if ( $user_shipping_company     == '' &&
                                             $user_shipping_address_1   == '' &&
                                             $user_shipping_address_2   == '' &&
                                             $user_shipping_city        == '' &&
                                             $user_shipping_postcode    == '' &&
                                             $user_shipping_state       == '' &&
                                             $user_shipping_country     == '') {
                                            echo "Shipping Address same as Billing Address";
                                            } else { ?>                                
                                        <?php echo $user_shipping_company."</br>".
                                               $user_shipping_address_1."</br>".
                                               $user_shipping_address_2."</br>".
                                               $user_shipping_city."</br>".
                                               $user_shipping_postcode."</br>".
                                               $user_shipping_state."</br>".
                                               $user_shipping_country."</br>";
                                               ?> 
                                               <br><br>
                                               <strong> Shipping Charges: </strong>
                                               <?php if ( $shipping_charges != 0 ) echo $currency_symbol . $shipping_charges;?>
                                    </p>
                                    <?php }?>                            
                                        </div>
                                    </div>
                                </div>
                             </div>                
                            <?php }
							
				if ( isset( $_GET[ 'action' ] ) ){
				       $action = $_GET[ 'action' ];
				}       
				if ( isset( $_GET[ 'mode' ] ) ){
				       $mode = $_GET[ 'mode' ];
				}
				if ( $action == 'emailtemplates' && ( $mode == 'addnewtemplate' || $mode == 'edittemplate' ) )
				{
					if( $mode=='edittemplate' )
					{ 
					$edit_id = $_GET[ 'id' ];
					
					$query="SELECT wpet . *  FROM `".$wpdb->prefix."ac_email_templates_lite` AS wpet WHERE id = %d ";
					$results = $wpdb->get_results( $wpdb->prepare( $query, $edit_id ) );
					}
					
					$active_post = ( empty( $_POST[ 'is_active' ] ) ) ? '0' : '1';
						
						?>
			
							<div id="content">
							  <form method="post" action="admin.php?page=woocommerce_ac_page&action=emailtemplates" id="ac_settings">
							     <input type="hidden" name="mode" value="<?php echo $mode;?>" />
							  <?php
							  $id_by = "";
							  if ( isset( $_GET[ 'id' ] ) ){
							        $id_by = $_GET[ 'id' ];
							  }       
							  ?>							  
							  <input type="hidden" name="id" value="<?php echo $id_by ;?>" />
							  
							  <?php
								$button_mode     = "save";
								$display_message = "Add Email Template";
								if ( $mode == 'edittemplate' )
								{
									$button_mode     = "update";
									$display_message = "Edit Email Template";
								}
								  print'<input type="hidden" name="ac_settings_frm" value="'.$button_mode.'">';?>
								  <div id="poststuff">
										<div class="postbox">
											<h3 class="hndle"><?php _e( $display_message, 'woocommerce-ac' ); ?></h3>
											<div>
											  <table class="form-table" id="addedit_template">
												
												<tr>
													<th>
														<label for="woocommerce_ac_template_name"><b><?php _e( 'Template Name:', 'woocommerce-ac');?></b></label>
													</th>
													<td>
													<?php
													$template_name = "";
													if( $mode == 'edittemplate' )
													{
														$template_name = $results[0]->template_name;
													}
													
													print'<input type="text" name="woocommerce_ac_template_name" id="woocommerce_ac_template_name" class="regular-text" value="'.$template_name.'">';?>
													<img class="help_tip" width="16" height="16" data-tip='<?php _e('Enter a template name for reference', 'woocommerce-ac') ?>' src="<?php echo plugins_url(); ?>/woocommerce/assets/images/help.png" /></p>
												</tr>
												
											    <tr>
											       <th>
				    									<label for="woocommerce_ac_from_name"><b><?php _e( 'Send From This Name:', 'woocommerce-ac' ); ?></b></label>
				    								</th>
				    								<td>
													<?php
													$from_name = "Admin";
													if ( $mode == 'edittemplate' )
													{
														$from_name=$results[0]->from_name;
													}
													
													print'<input type="text" name="woocommerce_ac_from_name" id="woocommerce_ac_from_name" class="regular-text" value="'.$from_name.'">';?>
													<img class="help_tip" width="16" height="16" data-tip='<?php _e('Enter the name that should appear in the email sent', 'woocommerce-ac') ?>' src="<?php echo plugins_url(); ?>/woocommerce/assets/images/help.png" /></p>
													
												</tr>
												
												<tr>
											       <th>
				    									<label for="woocommerce_ac_email_subject"><b><?php _e( 'Subject:', 'woocommerce-ac' ); ?></b></label>
				    								</th>
				    								<td>
													<?php
													$subject_edit = "";
													if ( $mode == 'edittemplate' )
													{
														$subject_edit=$results[0]->subject;
													}
													
													print'<input type="text" name="woocommerce_ac_email_subject" id="woocommerce_ac_email_subject" class="regular-text" value="'.$subject_edit.'">';?>
													<img class="help_tip" width="16" height="16" data-tip='<?php _e('Enter the subject that should appear in the email sent', 'woocommerce-ac') ?>' src="<?php echo plugins_url(); ?>/woocommerce/assets/images/help.png" /></p>
													
												</tr>
			
				    							<tr>
				    								<th>
				    									<label for="woocommerce_ac_email_body"><b><?php _e( 'Email Body:', 'woocommerce-ac' ); ?></b></label>
				    								</th>
				    								<td>
			
													<?php
													$initial_data = "";
													if ( $mode == 'edittemplate' )
													{
														$initial_data = stripslashes( $results[0]->body );
													}
																										
													echo "<textarea id='woocommerce_ac_email_body' name='woocommerce_ac_email_body' rows='15'' cols='80'>".$initial_data."</textarea>";
													?>
				    								
				    									<?php echo stripslashes(get_option( 'woocommerce_ac_email_body' )); ?>
				    									<span class="description"><?php
				    										echo __( 'Message to be sent in the reminder email.', 'woocommerce-ac' );
				    									?></span>
				    								</td>
				    							</tr>
			
				    							<tr>
				    								<th>
				    									<label for="woocommerce_ac_email_frequency"><b><?php _e( 'Send this email:', 'woocommerce-ac' ); ?></b></label>
				    								</th>
				    								<td>
				    								
				    									<select name="email_frequency" id="email_frequency">
				    									
				    									<?php
															$frequency_edit = "";
															if(	$mode == 'edittemplate')
															{
																$frequency_edit = $results[0]->frequency;
															}
															
				    										for ( $i = 1; $i < 4; $i++ )
				    										{
																printf( "<option %s value='%s'>%s</option>\n",
																	selected( $i, $frequency_edit, false ),
																	esc_attr( $i ),
																	$i
																);
				    										}
				    									
				    									?>
				    										
				    									</select>
			
														<select name="day_or_hour" id="day_or_hour">
			
														<?php
														$days_or_hours_edit = "";
														if ( $mode == 'edittemplate')
														{
															$days_or_hours_edit = $results[0]->day_or_hour;
														}
														
														$days_or_hours = array(
																		   'Days'  => 'Day(s)',
																		   'Hours' => 'Hour(s)'														    
														);
														foreach( $days_or_hours as $k => $v )
														{
															printf( "<option %s value='%s'>%s</option>\n",
																selected( $k, $days_or_hours_edit, false ),
																esc_attr( $k ),
																$v
															);
				    									}
														?>
			
														</select>
							    									
				    									<span class="description"><?php
				    									echo __( 'after cart is abandoned.', 'woocommerce-ac' );
				    									?></span>
				    								</td>
				    							</tr>
				    							
				    							<tr>
				    							<th>
				    								<label for="woocommerce_ac_email_preview"><b><?php _e( 'Send a test email to:', 'woocommerce-ac' ); ?></b></label>
				    							</th>
				    							<td> 
				    							
				    							<input type="text" id="send_test_email" name="send_test_email" class="regular-text" >
				    							<input type="button" value="Send a test email" id="preview_email" onclick="javascript:void(0);">
				    							<img class="help_tip" width="16" height="16" data-tip='<?php _e('Enter the email id to which the test email needs to be sent.', 'woocommerce-ac') ?>' src="<?php echo plugins_url(); ?>/woocommerce/assets/images/help.png" />
				    							<div id="preview_email_sent_msg" style="display:none;"></div>
				    							</p>
				    							
				    							</td>
				    							</tr>				    							
												</table>
											</div>
										</div>
									</div>
							  <p class="submit">
								<?php
									$button_value = "Save Changes";
									if ( $mode == 'edittemplate' )
									{
										$button_value = "Update Changes";
									}?>
								<input type="submit" name="Submit" class="button-primary" value="<?php esc_attr_e( $button_value, 'woocommerce-ac' ); ?>"  />
							  </p>
						    </form>
						  </div>
						<?php 
						}																	
				}
				
			}				
				
				function my_action_javascript()
				{
					?>
						<script type="text/javascript" >
						jQuery( document ).ready( function($)
						{
							$( "table#cart_data a.remove_cart" ).click( function()
							{
								var y = confirm( 'Are you sure you want to delete this Abandoned Order' );
								if( y == true )
								{
									var passed_id          = this.id;
									var arr                = passed_id.split('-');
									var abandoned_order_id = arr[0];
									var user_id            = arr[1];
									var data               = {
                        										abandoned_order_id: abandoned_order_id,
                        										user_id           : user_id,
                        										action            : 'remove_cart_data'
                      				};
							
								// since 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php
								$.post( ajaxurl, data, function( response )
								{
									//alert('Got this from the server: ' + response);
									$( "#row_" + abandoned_order_id ).hide();
								});
							    }
							});
						});
						</script>
						<?php
						
					}
				
					function remove_cart_data() {
						
						global $wpdb; // this is how you get access to the database
					
						$abandoned_order_id = $_POST[ 'abandoned_order_id' ];
						$user_id            = $_POST[ 'user_id' ];
						$action             = $_POST[ 'action' ];
						
						$query = "DELETE FROM `".$wpdb->prefix."ac_abandoned_cart_history_lite`
						          WHERE
						          id = '%d' ";
						
						$results = $wpdb->get_results( $wpdb->prepare( $query, $abandoned_order_id ) );
						
						if ( $user_id >= '63000000' ) {
						    $guest_query = "DELETE FROM `".$wpdb->prefix."ac_guest_abandoned_cart_history_lite` WHERE id = '".$user_id."'";
						    $results_guest = $wpdb->get_results( $guest_query );
						}
						
						die();
					}
					
					function my_action_send_preview()
					{
						?>
							<script type="text/javascript" >
							
							jQuery( document ).ready( function( $ )
							{
								$( "table#addedit_template input#preview_email" ).click( function()
								{
								
									var from_name_preview     = $( '#woocommerce_ac_from_name' ).val();
									var subject_email_preview = $( '#woocommerce_ac_email_subject' ).val();
									var body_email_preview    = tinyMCE.activeEditor.getContent();
									var send_email_id         = $( '#send_test_email' ).val();																		
									var data                  = {
                            										from_name_preview    : from_name_preview,
                            										subject_email_preview: subject_email_preview,
                            										body_email_preview   : body_email_preview,
                            										send_email_id        : send_email_id,
                            										action               : 'preview_email_sent'
									};									
									
									// since 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php
									$.post( ajaxurl, data, function( response )
									{
										$( "#preview_email_sent_msg" ).html( "<img src='<?php echo plugins_url(); ?>/woocommerce-abandoned-cart/images/check.jpg'>&nbsp;Email has been sent successfully." );
										$( "#preview_email_sent_msg" ).fadeIn();
										setTimeout( function(){$( "#preview_email_sent_msg" ).fadeOut();}, 3000 );
										//alert('Got this from the server: ' + response);
									});
								});
							});
							</script>
							<?php
					}
					
					function preview_email_sent() {
						
						$from_email_name       = $_POST[ 'from_name_preview' ];
						$subject_email_preview = $_POST[ 'subject_email_preview' ];						
						$body_email_preview    = $_POST[ 'body_email_preview' ];
						$body_email_preview    = str_replace( '{{customer.firstname}}', 'John', $body_email_preview );
						$body_email_preview    = str_replace( '{{customer.lastname}}', 'Doe', $body_email_preview );
						$body_email_preview    = str_replace( '{{customer.fullname}}', 'John'." ".'Doe', $body_email_preview );
						
						$var =  '<h3>'.__( "Your Shopping Cart", "woocommerce-ac" ).'</h3>
                                 <table border="0" cellpadding="10" cellspacing="0" class="templateDataTable">
                                    <tr align="center">
                                       <th>'.__( "Item", "woocommerce-ac" ).'</th>
                                       <th>'.__( "Name", "woocommerce-ac" ).'</th>
                                       <th>'.__( "Quantity", "woocommerce-ac" ).'</th>
                                       <th>'.__( "Price", "woocommerce-ac" ).'</th>
                                       <th>'.__( "Line Subtotal", "woocommerce-ac" ).'</th>
                                    </tr>
						            <tr align="center">
                                       <td><img class="demo_img" width="42" height="42" src="'.plugins_url().'/woocommerce-abandoned-cart/images/shoes.jpg"/></td>                                                                  
                                       <td>'.__( "Men\'\s Formal Shoes", "woocommerce-ac" ).'</td>
                                       <td>1</td>
                                       <td>$100</td>
                                       <td>$100</td>
                                    </tr>
                                    <tr align="center">
                                       <td><img class="demo_img" width="42" height="42" src="'.plugins_url().'/woocommerce-abandoned-cart/images/handbag.jpg"/></td>                                                                  
                                       <td>'.__( "Woman\'\s Hand Bags", "woocommerce-ac" ).'</td>
                                       <td>1</td>
                                       <td>$100</td>
                                       <td>$100</td>
                                    </tr>
	                                <tr align="center">
	                                   <td></td>
	                                   <td></td>
	                                   <td></td>
	                                   <td>'.__( "Cart Total:", "woocommerce-ac" ).'</td>
	                                   <td>$200</td>
	                                </tr>
                                 </table>';
				                
						$body_email_preview = str_replace( '{{products.cart}}', $var, $body_email_preview );
					    if ( isset( $_POST[ 'send_email_id' ] ) ) {
						      $to_email_preview = $_POST[ 'send_email_id' ];
						} else {
						      $to_email_preview = "";
						}
						$user_email_from = get_option( 'admin_email' );
						$headers[] = "From: ".$from_email_name." <".$user_email_from.">"."\r\n";
						$headers[] = "Content-Type: text/html"."\r\n";                                                                                               
				                        
						$body_email_final_preview = stripslashes( $body_email_preview );
						wp_mail( $to_email_preview, $subject_email_preview, __( $body_email_final_preview, 'woocommerce-ac' ), $headers );	
				
						echo "email sent";
						
						die();
					}					
		}
			
		}
		
		$woocommerce_abandon_cart = new woocommerce_abandon_cart();
		


?>