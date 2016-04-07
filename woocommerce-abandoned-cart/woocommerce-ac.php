<?php 
/*
Plugin Name: Abandoned Cart Lite for WooCommerce
Plugin URI: http://www.tychesoftwares.com/store/premium-plugins/woocommerce-abandoned-cart-pro
Description: This plugin captures abandoned carts by logged-in users & emails them about it. <strong><a href="http://www.tychesoftwares.com/store/premium-plugins/woocommerce-abandoned-cart-pro">Click here to get the PRO Version.</a></strong>
Version: 2.6
Author: Tyche Softwares
Author URI: http://www.tychesoftwares.com/
*/

if( session_id() === '' ){
    //session has not started
    session_start();
}
// Deletion Settings
register_uninstall_hook( __FILE__, 'woocommerce_ac_delete_lite' );

include_once( "woocommerce_guest_ac.class.php" );
include_once( "default-settings.php" );
require_once( "actions.php" );
// Add a new interval of 5 minutes
add_filter( 'cron_schedules', 'woocommerce_ac_add_cron_schedule_lite' );

function woocommerce_ac_add_cron_schedule_lite( $schedules ) {
	
    $schedules['15_minutes_lite'] = array(
                'display'   => __( 'Once Every Fifteen Minutes' ),
    );
    return $schedules;
}

// Schedule an action if it's not already scheduled
if ( ! wp_next_scheduled( 'woocommerce_ac_send_email_action' ) ) {
    wp_schedule_event( time(), '15_minutes_lite', 'woocommerce_ac_send_email_action' );
}

// Hook into that action that'll fire every 5 minutes
add_action( 'woocommerce_ac_send_email_action', 'woocommerce_ac_send_email_cron_lite' );
function woocommerce_ac_send_email_cron_lite() {
    //require_once( ABSPATH.'wp-content/plugins/woocommerce-abandoned-cart/cron/send_email.php' );
    $plugin_dir_path = plugin_dir_path( __FILE__ );
    require_once( $plugin_dir_path . 'cron/send_email.php' );
}

function woocommerce_ac_delete_lite(){
	
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
	
	$sql_table_user_meta_cart = "DELETE FROM `" . $wpdb->prefix . "usermeta` WHERE meta_key = '_woocommerce_persistent_cart'";
	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	$wpdb->get_results( $sql_table_user_meta_cart );
	
	$sql_table_user_meta_cart_modified = "DELETE FROM `" . $wpdb->prefix . "usermeta` WHERE meta_key = '_woocommerce_ac_modified_cart'";
	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	$wpdb->get_results( $sql_table_user_meta_cart_modified );
	
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
	    
	    $sql_table_user_meta_cart = "DELETE FROM `" . $wpdb->prefix.$value->blog_id."_"."usermeta` WHERE meta_key = '_woocommerce_persistent_cart'";
	    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	    $wpdb->get_results( $sql_table_user_meta_cart );
	    
	    $sql_table_user_meta_cart_modified = "DELETE FROM `" . $wpdb->prefix.$value->blog_id."_"."usermeta` WHERE meta_key = '_woocommerce_ac_modified_cart'";
	    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	    $wpdb->get_results( $sql_table_user_meta_cart_modified );
	     
	}
	
	delete_option ( 'woocommerce_ac_email_body' );
	delete_option( 'ac_lite_cart_abandoned_time' );
	delete_option( 'ac_lite_email_admin_on_recovery' );
	delete_option( 'ac_lite_settings_status' );
	delete_option( 'woocommerce_ac_default_templates_installed' );
	
	
}
	/**
	 * woocommerce_abandon_cart_lite class
	 **/
	if ( !class_exists( 'woocommerce_abandon_cart_lite' ) ) {
	
		class woocommerce_abandon_cart_lite {
			
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
				
				// Update the options as per settings API
				add_action ( 'admin_init', array( &$this, 'ac_lite_update_db_check' ) );

				// Wordpress settings API
				add_action( 'admin_init', array( &$this, 'ac_lite_initialize_plugin_options' ) );
				
				// Language Translation
				add_action ( 'init', array( &$this, 'update_po_file' ) );
				
				// track links
				add_filter( 'template_include', array( &$this, 'email_track_links_lite' ), 99, 1 );
				
				//Discount Coupon Notice
				add_action ( 'admin_notices', array( &$this, 'ac_lite_coupon_notice' ) );
				
				add_action ( 'admin_enqueue_scripts', array( &$this, 'my_enqueue_scripts_js' ) );
				add_action ( 'admin_enqueue_scripts', array( &$this, 'my_enqueue_scripts_css' ) );
				
				if ( is_admin() ) {
					// Load "admin-only" scripts here
					add_action ( 'admin_head', array( &$this, 'my_action_javascript' ) );
					add_action ( 'wp_ajax_remove_cart_data', array( &$this, 'remove_cart_data' ) );
					
					add_action ( 'admin_head', array( &$this, 'my_action_send_preview' ) );
					add_action ( 'wp_ajax_preview_email_sent', array( &$this, 'preview_email_sent' ) );
					
				}
				
				// Send Email on order recovery
				add_action('woocommerce_order_status_pending_to_processing_notification', array(&$this, 'ac_email_admin_recovery'));
				add_action('woocommerce_order_status_pending_to_completed_notification', array(&$this, 'ac_email_admin_recovery'));
				add_action('woocommerce_order_status_pending_to_on-hold_notification', array(&$this, 'ac_email_admin_recovery'));
				add_action('woocommerce_order_status_failed_to_processing_notification', array(&$this, 'ac_email_admin_recovery'));
				add_action('woocommerce_order_status_failed_to_completed_notification', array(&$this, 'ac_email_admin_recovery'));
				add_action( 'admin_init', array( $this, 'wcap_preview_emails' ) );
				add_action('init', array( $this, 'app_output_buffer') );
				
				add_action( 'admin_init', array( &$this, 'wcap_check_pro_activated' ) );
			}
			
			
			
			/**
			 * Check If Pro is activated along with Lite version.
			 */
			public static function wcap_check_pro_activated() {
			
			    if ( is_plugin_active( 'woocommerce-abandon-cart-pro/woocommerce-ac.php' ) && class_exists( 'woocommerce_abandon_cart' ) ) {
			         add_action( 'admin_notices', array( 'woocommerce_abandon_cart_lite', 'wcap_check_pro_notice' ) );
			    }
			}
			
			/**
			 * Display a notice in the admin Plugins page if the LITE version is
			 * activated whith PRO version is activated.
			 */
			public static function wcap_check_pro_notice() {

			    $class = 'notice notice-error is-dismissible';
			    
			    $message = __( 'The Lite & Pro version of Abandoned Cart plugin for WooCommerce (from Tyche Softwares) are active on your website. <br> In this case, the abandoned carts will be captured in both plugins & email reminders will also be sent from both plugins. <br> It is recommended that you deactivate the Lite version & keep the Pro version active.', 'woocommerce-ac' );
			
			    printf( '<div class="%1$s"><p>%2$s</p></div>', $class, $message );
			}
			
			/*-----------------------------------------------------------------------------------*/
			/* Class Functions */
			/*-----------------------------------------------------------------------------------*/
			/**
			 * Preview email template
			 *
			 * @return string
			 */
			public function wcap_preview_emails() {
			
			    global $woocommerce;
			
			    if ( isset( $_GET['wacp_preview_woocommerce_mail'] ) ) {
			        if ( ! wp_verify_nonce( $_REQUEST['_wpnonce'], 'woocommerce-ac') ) {
			            die( 'Security check' );
			        }
			
			        $message = '';
			        // create a new email
			
			        if ( $woocommerce->version < '2.3' ) {
			            global $email_heading;
			             
			            ob_start();
			
			            include( 'views/wacp-wc-email-template-preview.php' );
			
			            $mailer        = WC()->mailer();
			            $message       = ob_get_clean();
			            $email_heading = __( 'HTML Email Template', 'woocommerce' );
			
			            $message =  $mailer->wrap_message( $email_heading, $message );
			        }else{
			
			            // load the mailer class
			            $mailer        = WC()->mailer();
			
			            // get the preview email subject
			            $email_heading = __( 'Abandoned cart Email Template', 'woocommerce-ac' );
			
			            // get the preview email content
			            ob_start();
			            include( 'views/wacp-wc-email-template-preview.php' );
			            $message       = ob_get_clean();
			
			            // create a new email
			            $email         = new WC_Email();
			
			            // wrap the content with the email template and then add styles
			            $message       = $email->style_inline( $mailer->wrap_message( $email_heading, $message ) );
			        }
			
			        echo $message;
			        exit;
			    }
			
			    if ( isset( $_GET['wacp_preview_mail'] ) ) {
			        if ( ! wp_verify_nonce( $_REQUEST['_wpnonce'], 'woocommerce-ac') ) {
			            die( 'Security check' );
			        }
			
			        // get the preview email content
			        ob_start();
			        include( 'views/wacp-email-template-preview.php' );
			        $message       = ob_get_clean();
			
			        // print the preview email
			        echo $message;
			        exit;
			    }
			}
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
				

				$table_name = $wpdb->prefix . "ac_email_templates_lite";
				$check_template_table_query = "SHOW COLUMNS FROM $table_name LIKE 'is_wc_template' ";
				$results = $wpdb->get_results( $check_template_table_query );
				 
				if ( count( $results ) == 0 ) {
				    $alter_template_table_query = "ALTER TABLE $table_name
				    ADD COLUMN `is_wc_template` enum('0','1') COLLATE utf8_unicode_ci NOT NULL AFTER `from_name`,
				    ADD COLUMN `default_template` int(11) NOT NULL AFTER `is_wc_template`";
				    
				    $wpdb->get_results( $alter_template_table_query );
				}
                $table_name = $wpdb->prefix . "ac_email_templates_lite";
			    $check_email_template_table_query = "SHOW COLUMNS FROM $table_name LIKE 'reply_email' ";
			    $results_email = $wpdb->get_results( $check_email_template_table_query );
			    
			    if ( count(  $results_email ) == 0 ) {
			        $alter_email_template_table_query = "ALTER TABLE $table_name
			        ADD COLUMN `reply_email` varchar(50) COLLATE utf8_unicode_ci NOT NULL AFTER `default_template`,
			        ADD COLUMN `from_email` varchar(50) COLLATE utf8_unicode_ci NOT NULL AFTER `reply_email`";
			        $wpdb->get_results( $alter_email_template_table_query );
			    }
			    
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
				
				// Default templates:  function call to create default templates.
				$check_table_empty  = $wpdb->get_var( "SELECT COUNT(*) FROM `" . $wpdb->prefix . "ac_email_templates_lite`" );
				
				if( !get_option( 'woocommerce_ac_default_templates_installed' ) ) {
				
				    if( 0 == $check_table_empty ) {
				        $default_template = new default_template_settings_lite;
				        $default_template->create_default_templates_lite();
				        update_option( 'woocommerce_ac_default_templates_installed', "yes" );
				    }
				}
			 }	
	           
			
			/***************************************************************
			 * WP Settings API
			 **************************************************************/
			function ac_lite_initialize_plugin_options() {
			    // First, we register a section. This is necessary since all future options must belong to a
			    add_settings_section(
			    'ac_lite_general_settings_section',         // ID used to identify this section and with which to register options
			    __( 'Settings', 'woocommerce-ac' ),                  // Title to be displayed on the administration page
			    array($this, 'ac_lite_general_options_callback' ), // Callback used to render the description of the section
			    'woocommerce_ac_page'     // Page on which to add this section of options
			    );
			
			    add_settings_field(
			    'ac_lite_cart_abandoned_time',
			    __( 'Cart abandoned cut-off time', 'woocommerce-ac' ),
			    array( $this, 'ac_lite_cart_abandoned_time_callback' ),
			    'woocommerce_ac_page',
			    'ac_lite_general_settings_section',
			    array( __( 'Consider cart abandoned after X minutes of item being added to cart & order not placed.', 'woocommerce-ac' ) )
			    );
			    
			    add_settings_field(
			    'ac_lite_email_admin_on_recovery',
			    __( 'Email admin On Order Recovery', 'woocommerce-ac' ),
			    array( $this, 'ac_lite_email_admin_on_recovery' ),
			    'woocommerce_ac_page',
			    'ac_lite_general_settings_section',
			    array( __( 'Sends email to Admin if an Abandoned Cart Order is recovered.', 'woocommerce-ac' ) )
			    );
			
			    // Finally, we register the fields with WordPress
			    register_setting(
		        'woocommerce_ac_settings',
		        'ac_lite_cart_abandoned_time',
		        array ( $this, 'ac_lite_cart_time_validation' )
		        );
		        
		        register_setting(
		        'woocommerce_ac_settings',
		        'ac_lite_email_admin_on_recovery'
	            );
	            
			}
			
			/***************************************************************
			 * WP Settings API callback for section
			 **************************************************************/
			function ac_lite_general_options_callback() {
			
			}
			
			/***************************************************************
			 * WP Settings API callback for cart time field
			 **************************************************************/
			function ac_lite_cart_abandoned_time_callback($args) {
			
			    // First, we read the option
			    $cart_abandoned_time = get_option( 'ac_lite_cart_abandoned_time' );
			     
			    // Next, we update the name attribute to access this element's ID in the context of the display options array
			    // We also access the show_header element of the options collection in the call to the checked() helper function
			    printf(
			    '<input type="text" id="ac_lite_cart_abandoned_time" name="ac_lite_cart_abandoned_time" value="%s" />',
			    isset( $cart_abandoned_time ) ? esc_attr( $cart_abandoned_time ) : ''
			        );
			     
			    // Here, we'll take the first argument of the array and add it to a label next to the checkbox
			    $html = '<label for="ac_lite_cart_abandoned_time"> '  . $args[0] . '</label>';
			    echo $html;
			}
			
			/***************************************************************
			 * WP Settings API cart time field validation
			 **************************************************************/
			function ac_lite_cart_time_validation( $input ) {
			    $output = '';
			    if ( $input == '' || is_numeric( $input) ) {
			        $output = stripslashes( $input) ;
			    } else {
			        add_settings_error( 'ac_lite_cart_abandoned_time', 'error found', __( 'Abandoned cart cut off time should be numeric.', 'woocommerce-ac' ) );
			    }
			    return $output;
			}
			
			/***************************************************************
			 * WP Settings API callback for email admin on cart recovery field
			 **************************************************************/
			function ac_lite_email_admin_on_recovery( $args ) {
			
			    // First, we read the option
			    $email_admin_on_recovery = get_option( 'ac_lite_email_admin_on_recovery' );
			     
			    // This condition added to avoid the notie displyed while Check box is unchecked.
			    if ( isset( $email_admin_on_recovery ) && $email_admin_on_recovery == '' ) {
			        $email_admin_on_recovery = 'off';
			    }
			     
			    // Next, we update the name attribute to access this element's ID in the context of the display options array
			    // We also access the show_header element of the options collection in the call to the checked() helper function
			    $html='';
			    printf(
			    '<input type="checkbox" id="ac_lite_email_admin_on_recovery" name="ac_lite_email_admin_on_recovery" value="on"
            			' . checked('on', $email_admin_on_recovery, false).' />'
			            			    );
			     
			    // Here, we'll take the first argument of the array and add it to a label next to the checkbox
			    $html .= '<label for="ac_lite_email_admin_on_recovery"> '  . $args[0] . '</label>';
			    echo $html;
			}
			
			/**************************************************
			 * This function is run when the plugin is upgraded
			 *************************************************/
			
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

			    $table_name = $wpdb->prefix . "ac_email_templates_lite";
			    $check_template_table_query = "SHOW COLUMNS FROM $table_name LIKE 'is_wc_template' ";
			    $results = $wpdb->get_results( $check_template_table_query );
			     
			    if ( count( $results ) == 0 ) {
			        $alter_template_table_query = "ALTER TABLE $table_name
			        ADD COLUMN `is_wc_template` enum('0','1') COLLATE utf8_unicode_ci NOT NULL AFTER `from_name`,
			        ADD COLUMN `default_template` int(11) NOT NULL AFTER `is_wc_template`";
			        $wpdb->get_results( $alter_template_table_query );
			    }
			    $table_name = $wpdb->prefix . "ac_email_templates_lite";
			    $check_email_template_table_query = "SHOW COLUMNS FROM $table_name LIKE 'reply_email' ";
			    $results_email = $wpdb->get_results( $check_email_template_table_query );
			    
			    if ( count(  $results_email ) == 0 ) {
			        $alter_email_template_table_query = "ALTER TABLE $table_name
			        ADD COLUMN `reply_email` varchar(50) COLLATE utf8_unicode_ci NOT NULL AFTER `default_template`,
			        ADD COLUMN `from_email` varchar(50) COLLATE utf8_unicode_ci NOT NULL AFTER `reply_email`";
			        $wpdb->get_results( $alter_email_template_table_query );
			    }
			    
			    $table_name = $wpdb->prefix . "ac_email_templates_lite";
			    $check_email_template_table_query = "SHOW COLUMNS FROM $table_name LIKE 'wc_email_header' ";
			    $results_email = $wpdb->get_results( $check_email_template_table_query );
			    
			    if ( count(  $results_email ) == 0 ) {
			        $alter_email_template_table_query = "ALTER TABLE $table_name
			        ADD COLUMN `wc_email_header` varchar(50) COLLATE utf8_unicode_ci NOT NULL AFTER `from_email`";
			        $wpdb->get_results( $alter_email_template_table_query );
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
			     
			    //get the option, if it is not set to individual then convert to individual records and delete the base record
			    $ac_settings = get_option( 'ac_lite_settings_status' );
			    if ( $ac_settings != 'INDIVIDUAL' ) {
			        //fetch the existing settings and save them as inidividual to be used for the settings API
			        $woocommerce_ac_settings = json_decode( get_option( 'woocommerce_ac_settings' ) );
			        if( isset($woocommerce_ac_settings[0]->cart_time) ){
			            add_option( 'ac_lite_cart_abandoned_time', $woocommerce_ac_settings[0]->cart_time );
			        }else{
			            add_option( 'ac_lite_cart_abandoned_time', '60' );
			        }
			    
			        if( isset($woocommerce_ac_settings[0]->email_admin) ){
			            add_option( 'ac_lite_email_admin_on_recovery', $woocommerce_ac_settings[0]->email_admin );
			        }else{
			            add_option( 'ac_lite_email_admin_on_recovery', "" );
			        }
			         
			        update_option( 'ac_lite_settings_status', 'INDIVIDUAL' );
			        //Delete the main settings record
			        delete_option( 'woocommerce_ac_settings' );
			    }
			     
			}
			
			/******
			 * Send email to admin when cart is recover.
			 * @since 2.3 version
			 */
			
			function ac_email_admin_recovery ($order_id) {
			
			    $user_id = get_current_user_id();
			    
			    $ac_email_admin_recovery = get_option( 'ac_lite_email_admin_on_recovery' );
			    
			    if( $ac_email_admin_recovery == 'on' ){
			        if ( get_user_meta($user_id, '_woocommerce_ac_modified_cart', true) == md5("yes") || get_user_meta($user_id, '_woocommerce_ac_modified_cart', true) == md5("no") ){ // indicates cart is abandoned
			            $order = new WC_Order( $order_id );
			
			            $email_heading = __('New Customer Order - Recovered', 'woocommerce-ac');
			
			            $blogname = wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);
			
			            $email_subject = "New Customer Order - Recovered";
			
			            $user_email = get_option('admin_email');
			            $headers[] = "From: Admin <".$user_email.">";
			            $headers[] = "Content-Type: text/html";
			
			            // Buffer
			            ob_start();
			
			            // Get mail template
			            woocommerce_get_template('emails/admin-new-order.php', array(
			            'order' => $order,
			            'email_heading' => $email_heading,
			            'sent_to_admin' => false,
			            'plain_text'    => false
			            ));
			
			            // Get contents
			            $email_body = ob_get_clean();
			
			            //$email_body .= "Recovered Order";
			            woocommerce_mail( $user_email, $email_subject, $email_body, $headers );
			        }
			    }
			
			}
			
			function woocommerce_ac_admin_menu() {
			
				$page = add_submenu_page ( 'woocommerce', __( 'Abandoned Carts', 'woocommerce-ac' ), __( 'Abandoned Carts', 'woocommerce-ac' ), 'manage_woocommerce', 'woocommerce_ac_page', array( &$this, 'woocommerce_ac_page' ) );
			
			}
			
			function woocommerce_ac_store_cart_timestamp() {
			    
			    global $wpdb,$woocommerce;
			    
			    $current_time = current_time( 'timestamp' );
			    $cut_off_time = get_option( 'ac_lite_cart_abandoned_time' );
			    
			    $cart_ignored   = 0;
			    $recovered_cart = 0;
			    
			    if( isset( $cut_off_time ) ) {
			        $cart_cut_off_time = $cut_off_time * 60;
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
			
			        $validate_server_string  = rawurldecode ( $_SERVER["QUERY_STRING"] );
                    $validate_server_string = str_replace ( " " , "+", $validate_server_string);
                    
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
			
			    
			    $saved_cart = json_decode( get_user_meta( $_SESSION['user_id'], '_woocommerce_persistent_cart',true ), true );
			    
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
				$sent_email = '';
				if ( isset( $_SESSION[ 'email_sent_id' ] ) ){
				    $sent_email = $_SESSION[ 'email_sent_id' ];
				}
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
			    global $typenow;
				// only hook up these filters if we're in the admin panel, and the current user has permission
				// to edit posts and pages
			    if ( !current_user_can( 'edit_posts' ) && !current_user_can( 'edit_pages' ) ) {
			        return;
			    }
			    
			    if ( !isset( $_GET['page'] ) || $_GET['page'] != "woocommerce_ac_page" ) {
			        return;
			    }
			    				
				if ( get_user_option( 'rich_editing' ) == 'true' ) {
				    remove_filter( 'the_excerpt', 'wpautop' );
				    add_filter('tiny_mce_before_init', array( &$this, 'myformatTinyMCE_ac'));
					add_filter( 'mce_buttons', array( &$this, 'filter_mce_button' ) );
					add_filter( 'mce_external_plugins', array( &$this, 'filter_mce_plugin' ) );
				}
			}
			
			function filter_mce_button( $buttons ) {
				// add a separation before our button, here our button's id is &quot;mygallery_button&quot;
				array_push( $buttons, 'abandoncart', '|' );
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
				if ( $action == 'report' ) {
				    $active_report = "nav-tab-active";
				}
			
				?>
				
				<div style="background-image: url('<?php echo plugins_url(); ?>/woocommerce-abandoned-cart/images/ac_tab_icon.png') !important;" class="icon32"><br></div>				
				
				<h2 class="nav-tab-wrapper woo-nav-tab-wrapper">
				<a href="admin.php?page=woocommerce_ac_page&action=listcart" class="nav-tab <?php if (isset($active_listcart)) echo $active_listcart; ?>"> <?php _e( 'Abandoned Orders', 'woocommerce-ac' );?> </a>
				<a href="admin.php?page=woocommerce_ac_page&action=emailtemplates" class="nav-tab <?php if (isset($active_emailtemplates)) echo $active_emailtemplates; ?>"> <?php _e( 'Email Templates', 'woocommerce-ac' );?> </a>
				<a href="admin.php?page=woocommerce_ac_page&action=emailsettings" class="nav-tab <?php if (isset($active_settings)) echo $active_settings; ?>"> <?php _e( 'Settings', 'woocommerce-ac' );?> </a>
				<a href="admin.php?page=woocommerce_ac_page&action=stats" class="nav-tab <?php if (isset($active_stats)) echo $active_stats; ?>"> <?php _e( 'Recovered Orders', 'woocommerce-ac' );?> </a>
				<a href="admin.php?page=woocommerce_ac_page&action=report" class="nav-tab <?php if( isset( $active_report ) ) echo $active_report; ?>"> <?php _e( 'Product Report', 'woocommerce-ac' );?> </a>
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

				    function activate_email_template( template_id, active_state ) {
                        
                        location.href = 'admin.php?page=woocommerce_ac_page&action=emailtemplates&mode=activate_template&id='+template_id+'&active_state='+active_state ;
                    }
				    </script>
				    
				    <?php
				    $js_src = includes_url('js/tinymce/') . 'tinymce.min.js';
				    
				    wp_enqueue_script( 'tinyMce_ac',$js_src );
				    wp_enqueue_script( 'ac_email_variables', plugins_url() . '/woocommerce-abandoned-cart/js/abandoncart_plugin_button.js' );
				    
				}
			
			}
			
			function myformatTinyMCE_ac( $in ) {
			    
			    $in['force_root_block']             = false;
			    $in['valid_children']               = '+body[style]';
			    $in['remove_linebreaks']            = false;
			    $in['gecko_spellcheck']             = false;
			    $in['keep_styles']                  = true;
			    $in['accessibility_focus']          = true;
			    $in['tabfocus_elements']            = 'major-publishing-actions';
			    $in['media_strict']                 = false;
			    $in['paste_remove_styles']          = false;
			    $in['paste_remove_spans']           = false;
			    $in['paste_strip_class_attributes'] = 'none';
			    $in['paste_text_use_dialog']        = true;
			    $in['wpeditimage_disable_captions'] = true;
			    $in['wpautop']                      = false;
			    $in['apply_source_formatting']      = true;
			    $in['cleanup']                      = true;
			    $in['convert_newlines_to_brs']      = FALSE; 
			    $in['fullpage_default_xml_pi']      = false; 
			    $in['convert_urls']                 = false;
			    // Do not remove redundant BR tags
			    $in['remove_redundant_brs']         = false;
			    
			    return $in;
			}
			
			function my_enqueue_scripts_css( $hook ) {
				
				if ( $hook != 'woocommerce_page_woocommerce_ac_page' ) {
					return;
				} else {
					wp_enqueue_style( 'jquery-ui', "http://ajax.googleapis.com/ajax/libs/jqueryui/1.8/themes/base/jquery-ui.css" , '', '', false );					
					wp_enqueue_style( 'woocommerce_admin_styles', plugins_url() . '/woocommerce/assets/css/admin.css' );
					wp_enqueue_style( 'jquery-ui-style', 'http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.2/themes/smoothness/jquery-ui.css' );
					wp_enqueue_style( 'abandoned-orders-list', plugins_url() . '/woocommerce-abandoned-cart/css/view.abadoned.orders.style.css' );
				
				}
			}
			
			//bulk action
			// to over come the wp redirect warning while deleting
			function app_output_buffer() {
			    ob_start();
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
				 /**
				  * When we delete the item from the below drop down it is registred in action 2
				  */
				 if ( isset( $_GET['action2'] ) ) $action_two = $_GET['action2'];
				 else $action_two = "";
				 
				 // Detect when a bulk action is being triggered on abadoned orders page.
				 if( 'wcap_delete' === $action || 'wcap_delete' === $action_two  ){
				 
				     $ids    = isset( $_GET['abandoned_order_id'] ) ? $_GET['abandoned_order_id'] : false;
				     if ( ! is_array( $ids ) ){
				         $ids = array( $ids );
				     }
				 
				     foreach ( $ids as $id ) {
				         $class = new wcap_delete_bulk_action_handler_lite();
				         $class->wcap_delete_bulk_action_handler_function_lite( $id );
				     }
				 }
				 
				 //Detect when a bulk action is being triggered on temnplates page.
				 if( 'wcap_delete_template' === $action || 'wcap_delete_template' === $action_two  ){
				 
				     $ids    = isset( $_GET['template_id'] ) ? $_GET['template_id'] : false;
				 
				     if ( ! is_array( $ids ) ){
				         $ids = array( $ids );
				     }
				 
				     foreach ( $ids as $id ) {
				         $class = new wcap_delete_bulk_action_handler_lite();
				         $class->wcap_delete_template_bulk_action_handler_function_lite( $id );
				     }
				 }
				 
                if ( isset($_GET ['wcap_deleted']) && 'YES' == $_GET['wcap_deleted'] ) { ?>
                 <div id="message" class="updated fade"><p><strong><?php _e( 'The Abadoned cart has been successfully deleted.', 'woocommerce-ac' ); ?></strong></p></div>
                 <?php }

                 if ( isset($_GET ['wcap_template_deleted']) && 'YES' == $_GET['wcap_template_deleted'] ) { ?>
                    <div id="message" class="updated fade"><p><strong><?php _e( 'The Template has been successfully deleted.', 'woocommerce-ac' ); ?></strong></p></div>
                   <?php }
                
					if ( $action == 'emailsettings' ) {
						// Save the field values
                    ?>
					    <p><?php _e( 'Change settings for sending email notifications to Customers after X minute.', 'woocommerce-ac' ); ?></p>
                        <div id="content">

							<form method="post" action="options.php">
                                <?php settings_fields( 'woocommerce_ac_settings' ); ?>
                                <?php do_settings_sections( 'woocommerce_ac_page' ); ?>
								<?php settings_errors(); ?>
								<?php submit_button(); ?>

                            </form>
                        </div>
						<?php 
			      } elseif ( $action == 'listcart' || $action == '' ) {
			?>
						
			<p> <?php _e( 'The list below shows all Abandoned Carts which have remained in cart for a time higher than the "Cart abandoned cut-off time" setting.', 'woocommerce-ac' );?> </p>
			
			<?php
            global $wpdb;
            
            include_once('class-abandoned-orders-table.php');
            $wcap_abandoned_order_list = new WACP_Abandoned_Orders_Table();
            $wcap_abandoned_order_list->wcap_abadoned_order_prepare_items();
            ?>
            <div class="wrap">
                <form id="wacp-abandoned-orders" method="get" >
                    <input type="hidden" name="page" value="woocommerce_ac_page" />
                    <?php $wcap_abandoned_order_list->display(); ?>
                </form>
            </div>
            
            <?php 
					} elseif ( $action == 'emailtemplates' && ( $mode != 'edittemplate' && $mode != 'addnewtemplate' ) ) {
							?>													
							<p> <?php _e( 'Add email templates at different intervals to maximize the possibility of recovering your abandoned carts.', 'woocommerce-ac' );?> </p>
							<?php
							
							// Save the field values
							$insert_template_successfuly = $update_template_successfuly = ''; 
							if ( isset( $_POST[ 'ac_settings_frm' ] ) && $_POST[ 'ac_settings_frm' ] == 'save' ) {	
							    						
								   
								   $active_post    = ( empty( $_POST['is_active'] ) ) ? '0' : '1';
								   $is_wc_template = ( empty( $_POST['is_wc_template'] ) ) ? '0' : '1';     
								   
								if ( $active_post == 1 ) { 								    
								    								
									$is_active       = ( empty( $_POST['is_active'] ) ) ? '0' : '1';
									$email_frequency = trim( $_POST[ 'email_frequency' ] );
									$day_or_hour     = trim( $_POST[ 'day_or_hour' ] );
									
								    $check_query = "SELECT * FROM `".$wpdb->prefix."ac_email_templates_lite`
													WHERE is_active = %s 
	                                                AND frequency   = %d 
	                                                AND day_or_hour = %s ";
								    $check_results = $wpdb->get_results( $wpdb->prepare( $check_query, $is_active, $email_frequency, $day_or_hour ) );
								    
								    $default_value =  0 ;
								     
								    
									if ( count( $check_results ) == 0 ) {
									    
									    
									     $active_post                  = ( empty( $_POST['is_active'] ) ) ? '0' : '1';
									     $woocommerce_ac_email_subject = trim( $_POST[ 'woocommerce_ac_email_subject' ] );
									     $woocommerce_ac_email_body    = trim( $_POST[ 'woocommerce_ac_email_body' ] );
									     $woocommerce_ac_template_name = trim( $_POST[ 'woocommerce_ac_template_name' ] );
									     $woocommerce_ac_from_name     = trim( $_POST[ 'woocommerce_ac_from_name' ] );
									     $woocommerce_ac_email_reply   = trim( $_POST['woocommerce_ac_email_reply'] );
									     $woocommerce_ac_email_from   = trim( $_POST[ 'woocommerce_ac_email_from' ] );
									     $woocommerce_ac_email_header   = trim( $_POST[ 'wcap_wc_email_header' ] );
									     $query = "INSERT INTO `".$wpdb->prefix."ac_email_templates_lite`
										           (subject, body, is_active, frequency, day_or_hour, template_name, from_name, is_wc_template, default_template, reply_email, from_email, wc_email_header )      
										           VALUES ( %s, %s, %s, %d, %s, %s, %s, %s, %d, %s, %s, %s )";        //It  is fix
									     
									    $insert_template_successfuly = $wpdb->query( $wpdb->prepare( $query, 
									                                  $woocommerce_ac_email_subject,
									                                  $woocommerce_ac_email_body, 
									                                  $active_post, 
									                                  $email_frequency, 
									                                  $day_or_hour, 
									                                  $woocommerce_ac_template_name, 
									                                  $woocommerce_ac_from_name,
									                                  $is_wc_template,
									                                  $default_value,
									                                  $woocommerce_ac_email_reply,
									                                  $woocommerce_ac_email_from,
									                                  $woocommerce_ac_email_header)       
	                                      );
									   
									}
									else {
									    
									    $update_is_active = 0;
									    $query_update = "UPDATE `".$wpdb->prefix."ac_email_templates_lite`
										                 SET
										                 is_active       = %s
										                 WHERE frequency = %d
	                                                     AND day_or_hour = %s ";
									    $update_template_successfuly = $wpdb->query($wpdb->prepare( $query_update, $update_is_active, $email_frequency, $day_or_hour ) );
									    
									    
									    $woocommerce_ac_email_subject = trim( $_POST[ 'woocommerce_ac_email_subject' ] );
									    $woocommerce_ac_email_body    = trim( $_POST[ 'woocommerce_ac_email_body' ] );
									    $woocommerce_ac_template_name = trim( $_POST[ 'woocommerce_ac_template_name' ] );
									    $woocommerce_ac_from_name     = trim( $_POST[ 'woocommerce_ac_from_name' ] );
									    $woocommerce_ac_email_from     = trim( $_POST['woocommerce_ac_email_from'] );
									    $woocommerce_ac_email_reply   = trim( $_POST[ 'woocommerce_ac_email_reply' ] );
									    $woocommerce_ac_email_header   = trim( $_POST[ 'wcap_wc_email_header' ] );
									    $query_insert_new = "INSERT INTO `".$wpdb->prefix."ac_email_templates_lite`
										                    (subject, body, is_active, frequency, day_or_hour, template_name, from_name, is_wc_template, default_template, reply_email, from_email, wc_email_header )
										                    VALUES ( %s, %s, %s, %d, %s, %s, %s, %s, %d, %s, %s, %s )";
    												
									    $insert_template_successfuly = $wpdb->query( $wpdb->prepare( $query_insert_new, 
									                                  $woocommerce_ac_email_subject,
									                                  $woocommerce_ac_email_body, 
									                                  $active_post, 
									                                  $email_frequency, 
									                                  $day_or_hour, 
									                                  $woocommerce_ac_template_name, 
									                                  $woocommerce_ac_from_name,
									                                  $is_wc_template,
									                                  $woocommerce_ac_email_reply,
									                                  $woocommerce_ac_email_from,
									                                  $woocommerce_ac_email_header )
	                                   );
									}
								}else{
								    
								    
								    $woocommerce_ac_email_subject = trim( $_POST[ 'woocommerce_ac_email_subject' ] );
								    $woocommerce_ac_email_body    = trim( $_POST[ 'woocommerce_ac_email_body' ] );
								    $woocommerce_ac_template_name = trim( $_POST[ 'woocommerce_ac_template_name' ] );
								    $woocommerce_ac_from_name     = trim( $_POST[ 'woocommerce_ac_from_name' ] );  
								    $woocommerce_ac_email_reply   = trim( $_POST[ 'woocommerce_ac_email_reply' ] );
								    $woocommerce_ac_email_from     = trim( $_POST['woocommerce_ac_email_from'] );
								    $woocommerce_ac_email_header   = trim( $_POST[ 'wcap_wc_email_header' ] );
								    
								    $active_post                  = ( empty( $_POST['is_active'] ) ) ? '0' : '1';
								    $email_frequency              = trim( $_POST[ 'email_frequency' ] );
								    $day_or_hour                  = trim( $_POST[ 'day_or_hour' ] );
								    $is_wc_template               = ( empty( $_POST['is_wc_template'] ) ) ? '0' : '1';
								    $default_value                =  0 ;
								    
								    
								    $query = "INSERT INTO `".$wpdb->prefix."ac_email_templates_lite`
										           (subject, body, is_active, frequency, day_or_hour, template_name, from_name, is_wc_template, default_template, reply_email, from_email, wc_email_header )
										           VALUES ( %s, %s, %s, %d, %s, %s, %s, %s, %d, %s, %s, %s )";
								    
								    $insert_template_successfuly = $wpdb->query( $wpdb->prepare( $query,
                            								        $woocommerce_ac_email_subject,
                            								        $woocommerce_ac_email_body,
                            								        $active_post,
                            								        $email_frequency,
                            								        $day_or_hour,
                            								        $woocommerce_ac_template_name,
                            								        $woocommerce_ac_from_name,
                            								        $is_wc_template,
                            								        $default_value,
                            								        $woocommerce_ac_email_reply,
								                                    $woocommerce_ac_email_from,
								                                    $woocommerce_ac_email_header )        
								     );
								    
								}
							}
							
							if ( isset( $_POST[ 'ac_settings_frm' ] ) && $_POST[ 'ac_settings_frm' ] == 'update' )
							{
								
								$active         = ( empty( $_POST['is_active'] ) ) ? '0' : '1';
								$is_wc_template = ( empty( $_POST['is_wc_template'] ) ) ? '0' : '1';
								if ( $active == 1 )
								{   
								    $is_active       = ( empty( $_POST['is_active'] ) ) ? '0' : '1';
								    $email_frequency = trim( $_POST[ 'email_frequency' ] );
								    $day_or_hour     = trim( $_POST[ 'day_or_hour' ] );
								    $check_query = "SELECT * FROM `".$wpdb->prefix."ac_email_templates_lite`
									                WHERE is_active= %s 
						                            AND frequency  = %d 
						                            AND day_or_hour= %s ";
								    $check_results = $wpdb->get_results( $wpdb->prepare( $check_query, $is_active, $email_frequency, $day_or_hour ) );
								    
								    $default_value = '';
								    foreach($check_results as $result_key => $result_value) {
								        $default_value = ( empty( $result_value->default_template ) ) ? 0 : $result_value->default_template;
								        	
								    }
								    
									if (count($check_results) == 0 )
									{
                                        
									    $woocommerce_ac_email_subject = trim( $_POST[ 'woocommerce_ac_email_subject' ] );
									    $woocommerce_ac_email_body    = trim( $_POST[ 'woocommerce_ac_email_body' ] );									    
									    $woocommerce_ac_template_name = trim( $_POST[ 'woocommerce_ac_template_name' ] );
									    $woocommerce_ac_from_name     = trim( $_POST[ 'woocommerce_ac_from_name' ] );
									    $woocommerce_ac_email_from    = trim( $_POST['woocommerce_ac_email_from'] );
									    $woocommerce_ac_email_reply   = trim( $_POST[ 'woocommerce_ac_email_reply' ] );
									    $woocommerce_ac_email_header  = trim( $_POST[ 'wcap_wc_email_header' ] );
									    $id                           = trim( $_POST[ 'id' ] );
									    
									    $query_update = "UPDATE `".$wpdb->prefix."ac_email_templates_lite`
										                SET
                										subject       = %s,
                										body          = %s,
                										is_active     = %s, 
				                                        frequency     = %d,
                										day_or_hour   = %s,
                										template_name = %s,
                										from_name     = %s,
						                                is_wc_template = %s,
								                        default_template = %d,
						                                reply_email   = %s,
				                                        from_email    = %s,
					                                    wc_email_header = %s
                										WHERE id      = %d ";
									    $update_template_successfuly = $wpdb->query($wpdb->prepare( $query_update,
									                                 $woocommerce_ac_email_subject,
									                                 $woocommerce_ac_email_body,
									                                 $active,
                        									         $email_frequency,
                        									         $day_or_hour,
                        									         $woocommerce_ac_template_name,
                        									         $woocommerce_ac_from_name,
									                                 $is_wc_template,
									                                 $default_value,
									                                 $woocommerce_ac_email_from,
									                                 $woocommerce_ac_email_reply,
									                                 $woocommerce_ac_email_header,
                        									         $id )
									        
									     );
									}
									else {
									    
									    $updated_is_active = 0;
									    $query_update_new = "UPDATE `".$wpdb->prefix."ac_email_templates_lite`
										                     SET is_active   = %s
										                     WHERE frequency = %d
			                                                 AND day_or_hour = %s ";
									    $update_template_successfuly = $wpdb->query( $wpdb->prepare( $query_update_new, $updated_is_active, $email_frequency, $day_or_hour ) );
									    
									    $woocommerce_ac_email_subject = trim( $_POST[ 'woocommerce_ac_email_subject' ] );
									    $woocommerce_ac_email_body    = trim( $_POST[ 'woocommerce_ac_email_body' ] );									    
									    $woocommerce_ac_template_name = trim( $_POST[ 'woocommerce_ac_template_name' ] );
									    $woocommerce_ac_from_name     = trim( $_POST[ 'woocommerce_ac_from_name' ] );
									    $woocommerce_ac_email_from    = trim( $_POST['woocommerce_ac_email_from'] );
									    $woocommerce_ac_email_reply   = trim( $_POST[ 'woocommerce_ac_email_reply' ] );
									   $woocommerce_ac_email_header  = trim( $_POST[ 'wcap_wc_email_header' ] );
									    $id                           = trim( $_POST[ 'id' ] );
									    
									    $query_update_latest = "UPDATE `".$wpdb->prefix."ac_email_templates_lite`
										                SET
                										subject       = %s,
                										body          = %s,
                										is_active     = %s, 
				                                        frequency     = %d,
                										day_or_hour   = %s,
                										template_name = %s,
                										from_name     = %s,
				                                        is_wc_template = %s,
				                                        default_template = %d,
			                                            reply_email   = %s,
			                                            from_email    = %s,
			                                            wc_email_header = %s
                										WHERE id      = %d ";
									    $update_template_successfuly = $wpdb->query($wpdb->prepare( $query_update_latest,
									                                 $woocommerce_ac_email_subject,
									                                 $woocommerce_ac_email_body,
									                                 $active,
                        									         $email_frequency,
                        									         $day_or_hour,
                        									         $woocommerce_ac_template_name,
                        									         $woocommerce_ac_from_name,
									                                 $is_wc_template,
									                                 $default_value,
									                                 $woocommerce_ac_email_reply,
									                                 $woocommerce_ac_email_from,
									                                 $woocommerce_ac_email_header,
                        									         $id )
									        
									     );
									    
									}
								}else{
								    
								    $updated_is_active            = '0';
								    $is_active                    = ( empty( $_POST['is_active'] ) ) ? '0' : '1';
								    $email_frequency              = trim( $_POST[ 'email_frequency' ] );
								    $day_or_hour                  = trim( $_POST[ 'day_or_hour' ] );
								    $is_wc_template               = ( empty( $_POST['is_wc_template'] ) ) ? '0' : '1';
								    
								    $query_update_new = "UPDATE `".$wpdb->prefix."ac_email_templates_lite`
										                     SET is_active   = %s
										                     WHERE frequency = %d
			                                                 AND day_or_hour = %s ";
								    $wpdb->query( $wpdb->prepare( $query_update_new, $updated_is_active, $email_frequency, $day_or_hour ) );
								    
								    $woocommerce_ac_email_subject = trim( $_POST[ 'woocommerce_ac_email_subject' ] );
								    $woocommerce_ac_email_body    = trim( $_POST[ 'woocommerce_ac_email_body' ] );
								    $woocommerce_ac_template_name = trim( $_POST[ 'woocommerce_ac_template_name' ] );
								    $woocommerce_ac_from_name     = trim( $_POST[ 'woocommerce_ac_from_name' ] );
								    $woocommerce_ac_email_from    = trim( $_POST['woocommerce_ac_email_from'] );
								    $woocommerce_ac_email_reply   = trim( $_POST[ 'woocommerce_ac_email_reply' ] );
								    $woocommerce_ac_email_header  = trim( $_POST[ 'wcap_wc_email_header' ] );
									$id                           = trim( $_POST[ 'id' ] );
								    	
								    
								    $check_query = "SELECT * FROM `".$wpdb->prefix."ac_email_templates_lite`
									                WHERE is_active= %s
						                            AND frequency  = %d
						                            AND day_or_hour= %s ";
								    $check_results = $wpdb->get_results( $wpdb->prepare( $check_query, $is_active, $email_frequency, $day_or_hour ) );
								    
								    $default_value = '';
								    foreach($check_results as $result_key => $result_value) {
								        $default_value = ( empty( $result_value->default_template ) ) ? 0 : $result_value->default_template;
								         
								    }
								    	
								    $query_update_latest = "UPDATE `".$wpdb->prefix."ac_email_templates_lite`
										                SET
                										subject       = %s,
                										body          = %s,
                										is_active     = %s,
				                                        frequency     = %d,
                										day_or_hour   = %s,
                										template_name = %s,
                										from_name     = %s,
				                                        is_wc_template = %s,
				                                        default_template = %d,
				                                        reply_email   = %s,
            				                            from_email    = %s,
            				                            wc_email_header = %s
                										WHERE id      = %d ";
								    
								    $update_template_successfuly = $wpdb->query($wpdb->prepare( $query_update_latest,
								        $woocommerce_ac_email_subject,
								        $woocommerce_ac_email_body,
								        $is_active,
								        $email_frequency,
								        $day_or_hour,
								        $woocommerce_ac_template_name,
								        $woocommerce_ac_from_name,
								        $is_wc_template,
								        $default_value,
								        $woocommerce_ac_email_reply,
								        $woocommerce_ac_email_from,
								        $woocommerce_ac_email_header,
								        $id )
								         
								    );
								    
								}
							}
							
							if ( $action == 'emailtemplates' && $mode == 'removetemplate' ){
								$id_remove = $_GET[ 'id' ];
								
								$query_remove = "DELETE FROM `".$wpdb->prefix."ac_email_templates_lite` WHERE id= %d ";
								$wpdb->query( $wpdb->prepare( $query_remove, $id_remove ) );
							}
							
							if ( $action == 'emailtemplates' && $mode == 'activate_template' ) {
							    $template_id             = $_GET['id'];
							    $current_template_status = $_GET['active_state'];
							
							    if( "1" == $current_template_status ) {
							        $active = "0";
							    } else {
							        $active = "1";
							    }
							    $query_update = "UPDATE `" . $wpdb->prefix . "ac_email_templates_lite`
                                        SET
                                        is_active       = '" . $active . "'
                                        WHERE id        = '" . $template_id . "' ";
							    $wpdb->query( $query_update );
							
							    wp_safe_redirect( admin_url( '/admin.php?page=woocommerce_ac_page&action=emailtemplates' ) );
							}
							
							if ( isset( $_POST[ 'ac_settings_frm' ] ) && $_POST[ 'ac_settings_frm' ] == 'save' && (isset($insert_template_successfuly) && $insert_template_successfuly != '')) { ?>
							<div id="message" class="updated fade"><p><strong><?php _e( 'The Email Template has been successfully added.', 'woocommerce-ac' ); ?></strong></p></div>
							<?php } else if ( isset( $_POST[ 'ac_settings_frm' ] ) && $_POST[ 'ac_settings_frm' ] == 'save' && (isset($insert_template_successfuly) && $insert_template_successfuly == '')){
							    ?>
							   <div id="message" class="error fade"><p><strong><?php _e( ' There was a problem adding the email template. Please contact the plugin author via <a href= "https://wordpress.org/support/plugin/woocommerce-abandoned-cart">support forum</a>.', 'woocommerce-ac' ); ?></strong></p></div>
							 <?php   
							}
							
							
							if ( isset( $_POST[ 'ac_settings_frm' ] ) && $_POST[ 'ac_settings_frm' ] == 'update'  && isset($update_template_successfuly) && $update_template_successfuly >= 0 ) { ?>
							<div id="message" class="updated fade"><p><strong><?php _e( 'The Email Template has been successfully updated.', 'woocommerce-ac' ); ?></strong></p></div>
							<?php } else if ( isset( $_POST[ 'ac_settings_frm' ] ) && $_POST[ 'ac_settings_frm' ] == 'update'  && isset($update_template_successfuly) && $update_template_successfuly === false ){
							    ?>
							   <div id="message" class="error fade"><p><strong><?php _e( ' There was a problem updating the email template. Please contact the plugin author via <a href= "https://wordpress.org/support/plugin/woocommerce-abandoned-cart">support forum</a>.', 'woocommerce-ac' ); ?></strong></p></div>
							 <?php   
							}
							?>
							<div class="tablenav">
							<p style="float:left;">
							<a cursor: pointer; href="<?php echo "admin.php?page=woocommerce_ac_page&action=emailtemplates&mode=addnewtemplate"; ?>" class="button-secondary"><?php _e( 'Add New Template', 'woocommerce-ac' ); ?></a>							
							</p>
							
				<?php
				/* From here you can do whatever you want with the data from the $result link. */
                include_once('class-templates-table.php');
                $wcap_template_list = new WACP_Templates_Table();
                $wcap_template_list->wcap_templates_prepare_items();
                ?>
                <div class="wrap">
                    <form id="wacp-abandoned-templates" method="get" >
                        <input type="hidden" name="page" value="woocommerce_ac_page" />
                        <?php $wcap_template_list->display(); ?>
                    </form>
                </div>
                <?php 
              
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
						
                    include_once('class-recover-orders-table.php');
                    
                    $wcap_recover_orders_list = new WACP_Recover_Orders_Table();
                    $wcap_recover_orders_list->wcap_recovered_orders_prepare_items_lite();
                    
                    if ( isset( $_POST['start_date'] ) ) $start_date_range = $_POST['start_date'];
                    else $start_date_range = "";
    
                    if ( $start_date_range == "" ) {
                        $start_date_range = $date_sett['start_date'];
                    }
    
                    if ( isset( $_POST['end_date'] ) ) $end_date_range = $_POST['end_date'];
                    else $end_date_range = "";
                    
                    if ( $end_date_range == "" ) {
                        $end_date_range = $date_sett['end_date'];
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
                <div id="recovered_stats" class="postbox" style="display:block">
                    <div class="inside" >
                    <p style="font-size: 15px"><?php  _e( 'During the selected range ', 'woocommerce-ac' ); ?>
                        <strong>
                            <?php $count = $wcap_recover_orders_list->total_abandoned_cart_count; 
                                  echo $count; ?> 
                        </strong>
                        <?php _e( 'carts totaling', 'woocommerce-ac' ); ?> 
                        <strong> 
                            <?php $total_of_all_order = $wcap_recover_orders_list->total_order_amount; 
                                   
                            echo get_woocommerce_currency_symbol().$total_of_all_order; ?>
                         </strong>
                         <?php _e( ' were abandoned. We were able to recover', 'woocommerce-ac' ); ?> 
                         <strong>
                            <?php 
                            $recovered_item = $wcap_recover_orders_list->recovered_item;
                            
                            echo $recovered_item; ?>
                         </strong>
                         <?php _e( ' of them, which led to an extra', 'woocommerce-ac' ); ?> 
                         <strong>
                            <?php 
                                $recovered_total = $wcap_recover_orders_list->total_recover_amount;
                                echo get_woocommerce_currency_symbol().$recovered_total; ?>
                         </strong>
                         <?php //_e( ' in sales', 'woocommerce-ac' ); ?>
                     </p>
                    </div>
                </div>
              
                <div class="wrap">
                    <form id="wacp-recover-orders" method="get" >
                        <input type="hidden" name="page" value="woocommerce_ac_page" />
                        <?php $wcap_recover_orders_list->display(); ?>
                    </form>
                </div>
                <?php
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
                                
                                if($user_email == ""){
                                    $user_data = get_userdata( $results[0]->user_id );
                                    $user_email = $user_data->user_email;
                                }
                                
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
                            $cart_details   = array();
                            $cart_info      = json_decode( $results[0]->abandoned_cart_info );
                            $cart_details   = (array) $cart_info->cart;
                            $item_subtotal  = $item_total = 0;
                            
                            if ( is_array ( $cart_details ) && count($cart_details) > 0 ) {
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
                                    <?php $user_mail_to =  "mailto:".$user_email; ?>
                                    <a href=<?php echo $user_mail_to;?>><?php echo $user_email;?> </a>
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
                            <?php } elseif ( $action == 'report' ) {
                                include_once('class-product-report-table.php');
                                
                                $wcap_product_report_list = new WACP_Product_Report_Table();
                                $wcap_product_report_list->wcap_product_report_prepare_items_lite();
                                
                                ?>
    						    <div class="wrap">
            					    <form id="wacp-sent-emails" method="get" >
            					        <input type="hidden" name="page" value="woocommerce_ac_page" />
                                                <?php $wcap_product_report_list->display(); ?>
                                    </form>
                                </div>
    						                            
                            <?php }
					}
                         echo( "</table>" );
							
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
										<div> <!-- <div class="postbox" > -->
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
                                                        <label for="woocommerce_ac_email_from"><b><?php _e( 'Send From This Email Address:', 'woocommerce-ac' ); ?></b></label>
                                                    </th>
                                                    <td>

                                                    <?php
                                                    $from_edit = get_option( 'admin_email' );
                                                    
                                                    if ( $mode == 'edittemplate' && $results[0]->from_email != '') { // this is the fix
                                                        $from_edit = $results[0]->from_email;
                                                    }
                                                   print'<input type="text" name="woocommerce_ac_email_from" id="woocommerce_ac_email_from" class="regular-text" value="' . $from_edit . '">'; ?>
                                                   <img class="help_tip" width="16" height="16" data-tip='<?php _e( 'Which email address should be shown in the "From    Email" field for this email?', 'woocommerce' ) ?>' src="<?php echo plugins_url(); ?>/woocommerce/assets/images/help.png" /></p>
                                                   <?php ?></textarea>
                                                    </td>
                                                </tr>                                                
                                                <tr>
                                                   <th>
                                                        <label for="woocommerce_ac_email_reply"><b><?php _e( 'Send Reply Emails to:', 'woocommerce-ac' ); ?></b></label>
                                                    </th>
                                                    <td>

                                                    <?php
                                                    $reply_edit = get_option( 'admin_email' );
                                                    
                                                    if ( $mode == 'edittemplate' && $results[0]->reply_email != '' ) { // this is the fix
                                                        $reply_edit = $results[0]->reply_email;
                                                    }

                                                    print'<input type="text" name="woocommerce_ac_email_reply" id="woocommerce_ac_email_reply" class="regular-text" value="' . $reply_edit . '">'; ?>
                                                    <img class="help_tip" width="16" height="16" data-tip='<?php _e( 'When a contact receives your email and clicks reply, which email address should that reply be sent to?', 'woocommerce' ) ?>' src="<?php echo plugins_url(); ?>/woocommerce/assets/images/help.png" /></p>
                                                    <?php ?></textarea>
                                                    </td>
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
													
													$initial_data = str_replace ( "My document title", "", $initial_data );
													
													wp_editor(
													$initial_data,
													'woocommerce_ac_email_body',
													array(
													'media_buttons' => true,
													'textarea_rows' => 15,
													'tabindex' => 4,
													'tinymce' => array(
													'theme_advanced_buttons1' => 'bold,italic,underline,|,bullist,numlist,blockquote,|,link,unlink,|,spellchecker,fullscreen,|,formatselect,styleselect'
													
													    ),
													)
													);
													
													?>
													<?php echo stripslashes(get_option( 'woocommerce_ac_email_body' )); ?>
				    									<span class="description"><?php
				    										echo __( 'Message to be sent in the reminder email.', 'woocommerce-ac' );
				    									?></span>
				    								</td>
				    							</tr>
				    							
				    							 <tr>
                                                    <th>
                                                        <label for="is_wc_template"><b><?php _e( 'Use WooCommerce Template Style:', 'woocommerce-ac' ); ?></b></label>
                                                    </th>
                                                    <td>

                                                    <?php
                                                    $is_wc_template="";
                                                    
                                                    if ( $mode == 'edittemplate' ) {
                                                        $use_wc_template = $results[0]->is_wc_template;
                                                        
                                                        if ( $use_wc_template == '1' ) {
                                                            $is_wc_template = "checked";
                                                        } else {
                                                            $is_wc_template = "";
                                                        }
                                                    }
                                                    print'<input type="checkbox" name="is_wc_template" id="is_wc_template" ' . $is_wc_template . '>  </input>'; ?>
                                                    <img class="help_tip" width="16" height="16" data-tip='<?php _e( 'Use WooCommerce default style template for abandoned cart reminder emails.', 'woocommerce' ) ?>' src="<?php echo plugins_url(); ?>/woocommerce/assets/images/help.png" /> <a target = '_blank' href= <?php  echo wp_nonce_url( admin_url( '?wacp_preview_woocommerce_mail=true' ), 'woocommerce-ac' ) ; ?> > 
                                                    Click here to preview </a>how the email template will look with WooCommerce Template Style enabled. Alternatively, if this is unchecked, the template will appear as <a target = '_blank' href=<?php  echo wp_nonce_url( admin_url( '?wacp_preview_mail=true' ), 'woocommerce-ac' ) ; ?>>shown here</a>. <br> <strong>Note: </strong>When this setting is enabled, then "Send From This Name:" & "Send From This Email Address:" will be overwritten with WooCommerce -> Settings -> Email -> Email Sender Options.   
                                                    </p>
                                                    </td>
                                                
			                                     </tr>
			                                     
			                                     <tr>
                                                    <th>
                                                        <label for="wcap_wc_email_header"><b><?php _e( 'Email Template Header Text: ', 'woocommerce-ac' ); ?></b></label>
                                                    </th>
                                                    <td>

                                                    <?php
                                                    
                                                    $wcap_wc_email_header = "";  
                                                    if ( $mode == 'edittemplate'  ) {
                                                        $wcap_wc_email_header = $results[0]->wc_email_header;
                                                    }   
                                                    
                                                    if ( $wcap_wc_email_header == ""){
                                                        $wcap_wc_email_header = "Abandoned cart reminder";
                                                    }
                                                    print'<input type="text" name="wcap_wc_email_header" id="wcap_wc_email_header" class="regular-text" value="' . $wcap_wc_email_header . '">'; ?>
                                                    <img class="help_tip" width="16" height="16" data-tip='<?php _e( 'Enter the header which will appear in the abandoned WooCommerce email sent. This is only applicable when only used when "Use WooCommerce Template Style:" is checked.', 'woocommerce-ac' ) ?>' src="<?php echo plugins_url(); ?>/woocommerce/assets/images/help.png" /></p>
                                                    <?php ?>
                                                    </td>
                                                </tr> 
			                                     
			                                     
			                                     <tr>
                                                    <th>
                                                        <label for="is_active"><b><?php _e( 'Active:', 'woocommerce-ac' );  ?></b></label>
                                                    </th>
                                                    <td>

                                                    <?php
                                                    $is_active_edit="";
                                                    
                                                    if ( $mode == 'edittemplate' ) {
                                                        $active_edit = $results[0]->is_active;
                                                        
                                                        if ( $active_edit == '1' ) {
                                                            $is_active_edit = "checked";
                                                        } else {
                                                            $is_active_edit = "";
                                                        }
                                                    }

                                                    if ( $mode == 'copytemplate' ) {
                                                        $active_edit = $results_copy[0]->is_active;
                                                        
                                                        if($active_edit == '1') {
                                                            $is_active_edit = "checked";
                                                        } else {
                                                            $is_active_edit = "";
                                                        }
                                                    }
                                                        print'<input type="checkbox" name="is_active" id="is_active" ' . $is_active_edit . '>  </input>'; ?>
                                                        <img class="help_tip" width="16" height="16" data-tip='<?php _e( 'Yes, This email should be sent to shoppers with abandoned carts', 'woocommerce' ) ?>' src="<?php echo plugins_url(); ?>/woocommerce/assets/images/help.png" /></p>
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

			function bubble_sort_function( $unsort_array, $order ) {
			
			    $temp = array();
			    foreach ( $unsort_array as $key => $value )
			        $temp[$key] = $value; //concatenate something unique to make sure two equal weights don't overwrite each other
			
			    asort( $temp, SORT_NUMERIC ); // or ksort($temp, SORT_NATURAL); see paragraph above to understand why
			
			    if( $order == 'desc' ) {
			        $array = array_reverse( $temp, true );
			    }
			    else if($order == 'asc') {
			        $array = $temp;
			    }
			    unset( $temp );
			
			    return $array;
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
									var reply_name_preview      = $( '#woocommerce_ac_email_reply' ).val();
									var from_email_preview      = $( '#woocommerce_ac_email_from' ).val();
									var subject_email_preview = $( '#woocommerce_ac_email_subject' ).val();
									var body_email_preview    = tinyMCE.activeEditor.getContent();
									var send_email_id         = $( '#send_test_email' ).val();	
									var is_wc_template        = document.getElementById("is_wc_template").checked;	
									var wc_template_header      = $( '#wcap_wc_email_header' ).val() != '' ? $( '#wcap_wc_email_header' ).val() : 'Abandoned cart reminder';
																									
									var data                  = {
                            										from_name_preview    : from_name_preview,
                            										reply_name_preview   : reply_name_preview,
                            										from_email_preview   : from_email_preview,
                            										subject_email_preview: subject_email_preview,
                            										body_email_preview   : body_email_preview,
                            										send_email_id        : send_email_id,
                            										is_wc_template       : is_wc_template,
                            										wc_template_header   : wc_template_header,
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
						$reply_name_preview    = $_POST['reply_name_preview'];
						$from_email_preview    = $_POST['from_email_preview'];
						$subject_email_preview = $_POST[ 'subject_email_preview' ];						
						$body_email_preview    = $_POST[ 'body_email_preview' ];
						$is_wc_template        = $_POST['is_wc_template'];
						$wc_template_header    = stripslashes( $_POST[ 'wc_template_header' ] );
						
						$headers                = "From: " . $from_email_name . " <" . $from_email_preview . ">" . "\r\n";
						$headers               .= "Content-Type: text/html" . "\r\n";
						$headers               .= "Reply-To:  " . $reply_name_preview . " " . "\r\n";				
						
						$body_email_preview    = str_replace( '{{customer.firstname}}', 'John', $body_email_preview );
						$body_email_preview    = str_replace( '{{customer.lastname}}', 'Doe', $body_email_preview );
						$body_email_preview    = str_replace( '{{customer.fullname}}', 'John'." ".'Doe', $body_email_preview );
						$current_time_stamp    = current_time( 'timestamp' );
						$test_date             = date( 'd M, Y h:i A', $current_time_stamp );
						$body_email_preview    = str_replace( '{{cart.abandoned_date}}', $test_date, $body_email_preview );
						
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
						                
						$body_email_final_preview = stripslashes( $body_email_preview );
					    if ( isset( $is_wc_template ) && "true" == $is_wc_template ){
						
						    //$email_heading = __( 'Abandoned cart reminder', 'woocommerce-ac' );
						    
						    ob_start();
						    
						    wc_get_template( 'emails/email-header.php', array( 'email_heading' => $wc_template_header ) );
						    
						    $email_body_template_header = ob_get_clean();
						    
						    ob_start();
						    
						    wc_get_template( 'emails/email-footer.php' );
						    	
						    $email_body_template_footer = ob_get_clean();
						    
						    $final_email_body =  $email_body_template_header . $body_email_final_preview . $email_body_template_footer;
						    wc_mail( $to_email_preview, $subject_email_preview, $final_email_body , $headers );
						    
						}else{
						    wp_mail( $to_email_preview, $subject_email_preview, stripslashes( $body_email_preview ), $headers );
						}	
				
						echo "email sent";
						
						die();
					}					
		}
			
		}
		
		$woocommerce_abandon_cart = new woocommerce_abandon_cart_lite();
?>