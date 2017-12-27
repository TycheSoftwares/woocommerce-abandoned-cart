<?php 
/*
* Plugin Name: Abandoned Cart Lite for WooCommerce
* Plugin URI: http://www.tychesoftwares.com/store/premium-plugins/woocommerce-abandoned-cart-pro
* Description: This plugin captures abandoned carts by logged-in users & emails them about it. <strong><a href="http://www.tychesoftwares.com/store/premium-plugins/woocommerce-abandoned-cart-pro">Click here to get the PRO Version.</a></strong>
* Version: 4.5
* Author: Tyche Softwares
* Author URI: http://www.tychesoftwares.com/
* Text Domain: woocommerce-ac
* Domain Path: /i18n/languages/
* Requires PHP: 5.6
* WC requires at least: 3.0.0
* WC tested up to: 3.2.0
*/

// Deletion Settings
register_uninstall_hook( __FILE__, 'woocommerce_ac_delete_lite' );

require_once( "includes/wcal_class-guest.php" );
require_once( "includes/wcal_default-settings.php" );
require_once( "includes/wcal_actions.php" );
require_once( "includes/classes/class-wcal-aes.php" );
require_once( "includes/classes/class-wcal-aes-counter.php" );
require_once( "includes/wcal-common.php" );
require_once( "includes/wcal_ts_tracking.php");
require_once( "includes/wcal_admin_notice.php");

if ( is_admin() ) {
    require_once( 'includes/welcome.php' );

    define( 'WCAL_VERSION', wcal_common::wcal_get_version() );

    define( 'WCAL_PLUGIN_URL', wcal_common::wcal_get_plugin_url() );
}

// Add a new interval of 15 minutes
add_filter( 'cron_schedules', 'wcal_add_cron_schedule' );

function wcal_add_cron_schedule( $schedules ) { 
    $schedules['15_minutes_lite'] = array(
                'interval'  => 900, // 15 minutes in seconds
                'display'   => __( 'Once Every Fifteen Minutes' ),
    );
    return $schedules;
}
// Schedule an action if it's not already scheduled
if ( ! wp_next_scheduled( 'woocommerce_ac_send_email_action' ) ) {
    wp_schedule_event( time(), '15_minutes_lite', 'woocommerce_ac_send_email_action' );
}

/**
 * Run a cron once in week to delete old records for lockout
 */
function wcal_add_tracking_cron_schedule( $schedules ) {
    $schedules[ 'daily_once' ] = array(
        'interval' => 604800,  // one week in seconds
        'display'  => __( 'Once in a Week', 'woocommerce-ac' )
    );
    return $schedules;
}

/* To capture the data from the client site */
if ( ! wp_next_scheduled( 'wcal_ts_tracker_send_event' ) ) {
    wp_schedule_event( time(), 'daily_once', 'wcal_ts_tracker_send_event' );
}

// Hook into that action that'll fire every 15 minutes
add_action( 'woocommerce_ac_send_email_action', 'wcal_send_email_cron' );

function wcal_send_email_cron() {
    //require_once( ABSPATH.'wp-content/plugins/woocommerce-abandoned-cart/cron/send_email.php' );
    $plugin_dir_path = plugin_dir_path( __FILE__ );
    require_once( $plugin_dir_path . 'cron/wcal_send_email.php' );
}

function woocommerce_ac_delete_lite() { 
    global $wpdb;
    if ( ! is_multisite() ) {
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
    } else {    
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
    }   
    delete_option( 'woocommerce_ac_email_body' );
    delete_option( 'ac_lite_cart_abandoned_time' );
    delete_option( 'ac_lite_email_admin_on_recovery' );
    delete_option( 'ac_lite_settings_status' );
    delete_option( 'woocommerce_ac_default_templates_installed' );  
    delete_option( 'wcal_security_key' );
    delete_option( 'ac_lite_track_guest_cart_from_cart_page' );
    delete_option( 'wcal_from_name' );
    delete_option( 'wcal_from_email' );
    delete_option( 'wcal_reply_email' );

    delete_option( 'ac_security_key' );
    delete_option( 'wcal_activate_time' );
    delete_option( 'ac_lite_alter_table_queries' );
    delete_option( 'ac_lite_delete_alter_table_queries' );
    delete_option( 'wcal_allow_tracking' );
    delete_option( 'wcal_ts_tracker_last_send' );

    delete_option( 'wcal_welcome_page_shown_time' );
    delete_option( 'wcal_welcome_page_shown' );
}
    /**
     * woocommerce_abandon_cart_lite class
     **/
if( !class_exists( 'woocommerce_abandon_cart_lite' ) ) {
    
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
            register_activation_hook ( __FILE__,                        array( &$this, 'wcal_activate' ) );
            
            // WordPress Administration Menu 
            add_action ( 'admin_menu',                                  array( &$this, 'wcal_admin_menu' ) );
            
            // Actions to be done on cart update
            add_action ( 'woocommerce_cart_updated',                    array( &$this, 'wcal_store_cart_timestamp' ) );
            
            // delete added temp fields after order is placed 
            add_filter ( 'woocommerce_order_details_after_order_table', array( &$this, 'wcal_action_after_delivery_session' ) );
            
            add_action ( 'admin_init',                                  array( &$this, 'wcal_action_admin_init' ) );
            
            // Update the options as per settings API
            add_action ( 'admin_init',                                  array( &$this, 'wcal_update_db_check' ) );

            // Wordpress settings API
            add_action( 'admin_init',                                   array( &$this, 'wcal_initialize_plugin_options' ) );
            
            // Language Translation
            add_action ( 'init',                                        array( &$this, 'wcal_update_po_file' ) );
            
            // track links
            add_filter( 'template_include',                             array( &$this, 'wcal_email_track_links' ), 99, 1 );
            
            //It will used to unsubcribe the emails.
            add_action( 'template_include',                             array( &$this, 'wcal_email_unsubscribe'),99, 1 );
            
            add_action ( 'admin_enqueue_scripts',                       array( &$this, 'wcal_enqueue_scripts_js' ) );
            add_action ( 'admin_enqueue_scripts',                       array( &$this, 'wcal_enqueue_scripts_css' ) );
            
            if ( is_admin() ) {
                // Load "admin-only" scripts here               
                add_action ( 'admin_head',                              array( &$this, 'wcal_action_send_preview' ) );
                add_action ( 'wp_ajax_wcal_preview_email_sent',         array( &$this, 'wcal_preview_email_sent' ) );
                add_action ( 'wp_ajax_wcal_toggle_template_status',     array( &$this, 'wcal_toggle_template_status' ) );   
            }
                
            // Send Email on order recovery
            add_action( 'woocommerce_order_status_pending_to_processing_notification', array( &$this, 'wcal_email_admin_recovery' ) );
            add_action( 'woocommerce_order_status_pending_to_completed_notification',  array( &$this, 'wcal_email_admin_recovery' ) );
            add_action( 'woocommerce_order_status_pending_to_on-hold_notification',    array( &$this, 'wcal_email_admin_recovery' ) );
            add_action( 'woocommerce_order_status_failed_to_processing_notification',  array( &$this, 'wcal_email_admin_recovery' ) );
            add_action( 'woocommerce_order_status_failed_to_completed_notification',   array( &$this, 'wcal_email_admin_recovery' ) );
            
            add_action('woocommerce_order_status_changed',                             array( &$this, 'wcal_email_admin_recovery_for_paypal' ), 10, 3);
            
            add_action( 'admin_init',                                                  array( $this,   'wcal_preview_emails' ) );
            add_action( 'init',                                                        array( $this,   'wcal_app_output_buffer') );
            add_action( 'admin_init',                                                  array( &$this,  'wcal_check_pro_activated' ) );          
            add_action( 'woocommerce_checkout_order_processed',                        array( &$this,  'wcal_order_placed' ), 10 , 1 );         
            add_filter( 'woocommerce_payment_complete_order_status',                   array( &$this,  'wcal_order_complete_action' ), 10 , 2 );        
            add_filter( 'admin_footer_text',                                           array( $this,   'wcal_admin_footer_text' ), 1 );

            add_action( 'admin_notices',                                               array( 'Wcal_Admin_Notice',   'wcal_pro_notice' ) );
            add_action( 'admin_init',                                                  array( 'Wcal_Admin_Notice',   'wcal_pro_notice_ignore' ) );

            /** 
             *  @since: 4.2 
             *  Check if WC is enabled or not. 
             */
            add_action( 'admin_init',                                                  array( &$this,  'wcal_wc_check_compatibility' ) ); 
        }

        /**
         * @since: 4.2
         * Check if WC is active or not.
         */
        public static function wcal_wc_check_ac_installed() {
        
            if ( is_plugin_active( 'woocommerce/woocommerce.php' ) && class_exists( 'WooCommerce' ) ) {
                return true;
            } else {
                return false;
            }
        }
            
        /**
         * @since: 4.2
         * Ensure that the Abandoned cart lite get deactivated when WC is deactivated.
         */
        public static function wcal_wc_check_compatibility() {
                
            if ( ! self::wcal_wc_check_ac_installed() ) {
                    
                if ( is_plugin_active( plugin_basename( __FILE__ ) ) ) {
                    deactivate_plugins( plugin_basename( __FILE__ ) );
                        
                    add_action( 'admin_notices', array( 'woocommerce_abandon_cart_lite', 'wcal_wc_disabled_notice' ) );
                    if ( isset( $_GET['activate'] ) ) {
                        unset( $_GET['activate'] );
                    }
                        
                }
                    
            }
        }
        /**
         * @since: 4.2
         * Display a notice in the admin Plugins page if the Abandoned cart lite is
         * activated while WC is deactivated.
         */
        public static function wcal_wc_disabled_notice() {
                
            $class = 'notice notice-error is-dismissible';
            $message = __( 'Abandoned Cart Lite for WooCommerce requires WooCommerce installed and activate.', 'woocommerce-ac' );
                
            printf( '<div class="%1$s"><p>%2$s</p></div>', $class, $message );
        }
            
        public static function wcal_order_placed( $order_id ) {
            if( session_id() === '' ) {
                //session has not started
                session_start();
            }
            
            /**
             * When user comes from the abandoned cart reminder email this conditon will be executed.
             * It will check the guest uers data in further 3 conditions.
             * 1. When WC setting mandatory to become the logged in users. And places the order
             * 2. When WC setting is non - mandatory to become the logged in users. And user choose to be the loggedin 
             * to the site And places the order
             * 3. When user places the orde as guest user. 
             * It will consider the old cart of the customer as the recovered order and delete the unwanted new records.
             */
            if ( isset( $_SESSION['email_sent_id'] ) && $_SESSION['email_sent_id'] !='' ) {
                global $woocommerce, $wpdb;

                $wcal_history_table_name    = $wpdb->prefix . 'ac_abandoned_cart_history_lite';
                $wcal_guest_table_name      = $wpdb->prefix . 'ac_guest_abandoned_cart_history_lite';
                $wcal_sent_email_table_name = $wpdb->prefix . 'ac_sent_history_lite';

                $email_sent_id      = $_SESSION['email_sent_id'];
                
                $get_ac_id_query    = "SELECT abandoned_order_id FROM ". $wcal_sent_email_table_name ." WHERE id = %d";
                $get_ac_id_results  = $wpdb->get_results( $wpdb->prepare( $get_ac_id_query, $email_sent_id ) );
                
                $abandoned_order_id = '';
                if ( count( $get_ac_id_results ) > 0 ) {
                    $abandoned_order_id = $get_ac_id_results[0]->abandoned_order_id;
                }

                $wcal_account_password_check = 'no';

                /*if user becomes the registered user */
                if ( isset( $_POST['account_password'] ) && $_POST['account_password'] != '' ) {

                    $abandoned_cart_id_new_user = '';
                    if ( isset( $_SESSION['abandoned_cart_id_lite'] ) && '' != $_SESSION['abandoned_cart_id_lite'] ) { 
                        $abandoned_cart_id_new_user = $_SESSION['abandoned_cart_id_lite'];
                    }

                    $wcal_user_id_of_guest = '';
                    if ( isset( $_SESSION['user_id'] ) && '' != $_SESSION['user_id'] ) {
                        $wcal_user_id_of_guest      = $_SESSION['user_id'];
                    }

                    /* delete the guest record. As it become the logged in user */

                    $get_ac_id_guest_results = array();
                    if ( isset( $wcal_user_id_of_guest ) && '' != $wcal_user_id_of_guest ) { 
                        $get_ac_id_guest_query    = "SELECT id FROM `" . $wcal_history_table_name ."` WHERE user_id = %d ORDER BY id DESC";
                        $get_ac_id_guest_results  = $wpdb->get_results( $wpdb->prepare( $get_ac_id_guest_query, $wcal_user_id_of_guest ) );
                    }
                    
                    if ( count ($get_ac_id_guest_results) > 1 ) {
                        $abandoned_order_id_of_guest = $get_ac_id_guest_results[0]->id;
                        $wpdb->delete( $wcal_history_table_name , array( 'id' => $abandoned_order_id_of_guest ) );
                    }
                    if ( isset( $abandoned_cart_id_new_user ) && '' != $abandoned_cart_id_new_user ) {
                        /* it is the new registered users cart id */
                        $wpdb->delete( $wcal_history_table_name , array( 'id' => $abandoned_cart_id_new_user ) );
                    }

                    $wcal_account_password_check = 'yes';
                }

                $wcap_create_account = 'no';
                /*if user becomes the registred user */
                if ( isset( $_POST['createaccount'] ) && 
                     $_POST['createaccount'] != ''    && 
                     'no' == $wcal_account_password_check ) {

                    $abandoned_cart_id_new_user = '';
                    if ( isset ( $_SESSION['abandoned_cart_id_lite'] ) && '' != $_SESSION['abandoned_cart_id_lite'] ) {
                        $abandoned_cart_id_new_user = $_SESSION['abandoned_cart_id_lite'];
                    }
                    $wcal_user_id_of_guest = '';
                    if ( isset( $_SESSION['user_id'] ) && '' != $_SESSION['user_id'] ) {
                        $wcal_user_id_of_guest      = $_SESSION['user_id'];
                    }


                    /* delete the guest record. As it become the logged in user */
                    $get_ac_id_guest_results = array();
                    if ( isset( $wcal_user_id_of_guest ) && '' != $wcal_user_id_of_guest ) {
                        $get_ac_id_guest_query    = "SELECT id FROM `" . $wcal_history_table_name ."` WHERE user_id = %d ORDER BY id DESC";
                        $get_ac_id_guest_results  = $wpdb->get_results( $wpdb->prepare( $get_ac_id_guest_query, $wcal_user_id_of_guest ) );
                    }
                    if ( count ($get_ac_id_guest_results) > 1 ){
                        $abandoned_order_id_of_guest = $get_ac_id_guest_results[0]->id;
                        $wpdb->delete( $wcal_history_table_name , array( 'id' => $abandoned_order_id_of_guest ) );
                    }

                    /* it is the new registered users cart id */
                    if ( isset( $wcal_user_id_of_guest ) && '' != $wcal_user_id_of_guest ) {
                        $wpdb->delete( $wcal_history_table_name , array( 'id' => $abandoned_cart_id_new_user ) );
                    }

                    $wcap_create_account = 'yes';
                }

                if ( 'no' == $wcal_account_password_check && 'no' == $wcap_create_account ) {
                    
                    $wcal_user_id_of_guest = '';
                    if ( isset( $_SESSION['user_id'] ) && '' != $_SESSION['user_id'] ) {
                        $wcal_user_id_of_guest    = $_SESSION['user_id'];
                        $get_ac_id_guest_query    = "SELECT id FROM `" . $wcal_history_table_name ."` WHERE user_id = %d ORDER BY id DESC";
                        $get_ac_id_guest_results  = $wpdb->get_results( $wpdb->prepare( $get_ac_id_guest_query, $wcal_user_id_of_guest ) );

                        if ( count ($get_ac_id_guest_results) > 1 ) {
                            $abandoned_order_id_of_guest = $get_ac_id_guest_results[0]->id;            
                            $wpdb->delete( $wcal_history_table_name, array( 'id' => $abandoned_order_id_of_guest ) );
                        }
                    }
                }

                add_post_meta( $order_id , 'wcal_recover_order_placed_sent_id', $email_sent_id );
                if ( isset( $abandoned_order_id ) && '' != $abandoned_order_id ) {
                    add_post_meta( $order_id , 'wcal_recover_order_placed', $abandoned_order_id );
                }

            }else if ( isset( $_SESSION['abandoned_cart_id_lite'] ) && $_SESSION['abandoned_cart_id_lite'] != '' ) {

                /**
                 * In this codition we are cheking that if the order is placed before the cart cut off time then we 
                 * will delete the abandond cart records.
                 * If the order is placed after the cart cutoff time then we will create the post meta with 
                 * the abandoned cart id. So we will refer this abandoned cart id when order staus is changed
                 * while placing the order.
                 */
                if( session_id() === '' ){
                    //session has not started
                    session_start();
                }

                global $woocommerce, $wpdb;

                $wcal_history_table_name    = $wpdb->prefix . 'ac_abandoned_cart_history_lite';
                $wcal_guest_table_name      = $wpdb->prefix . 'ac_guest_abandoned_cart_history_lite';
                $wcal_sent_email_table_name = $wpdb->prefix . 'ac_sent_history_lite';

                $current_time   = current_time( 'timestamp' );
                $wcal_cart_abandoned_time = '';
                if ( isset( $_SESSION['abandoned_cart_id_lite'] ) && '' != $_SESSION['abandoned_cart_id_lite'] ) {
                    $wcal_abandoned_cart_id   = $_SESSION['abandoned_cart_id_lite'];

                    $get_abandoned_cart_query   = "SELECT abandoned_cart_time FROM `" . $wcal_history_table_name . "` WHERE id = %d ";
                    $get_abandoned_cart_results = $wpdb->get_results( $wpdb->prepare( $get_abandoned_cart_query, $wcal_abandoned_cart_id ) );

                    if ( count( $get_abandoned_cart_results ) > 0 ){
                        $wcal_cart_abandoned_time = $get_abandoned_cart_results[0]->abandoned_cart_time;
                    }

                    $ac_cutoff_time = get_option( 'ac_lite_cart_abandoned_time' );
                    $cut_off_time   = $ac_cutoff_time * 60;
                    $compare_time   = $current_time - $cut_off_time;
                
                    if ( $compare_time >  $wcal_cart_abandoned_time ) {
                        /* cart is declared as adandoned */
                        add_post_meta( $order_id , 'wcal_recover_order_placed', $wcal_abandoned_cart_id );
                    }else {
                        /* cart order is placed within the cutoff time.
                        we will delete that abandoned cart */
                  
                        /* if user becomes the registred user */

                        if ( isset( $_POST['account_password'] ) && $_POST['account_password'] != '' ) {

                            $abandoned_cart_id_new_user = $_SESSION['abandoned_cart_id_lite'];
                            $wcal_user_id_of_guest      = $_SESSION['user_id'];

                            /* delete the guest record. As it become the logged in user */

                            $wpdb->delete( $wcal_history_table_name , array( 'user_id' => $wcal_user_id_of_guest ) );
                            $wpdb->delete( $wcal_guest_table_name ,   array( 'id' => $wcal_user_id_of_guest ) );

                            /* it is the new registered users cart id */
                            $wpdb->delete( $wcal_history_table_name , array( 'id' => $abandoned_cart_id_new_user ) );
                        }else {

                            /**
                             * It will delete the order from history table if the order is placed before any email sent to
                             * the user.
                             */
                            $wpdb->delete( $wcal_history_table_name , array( 'id' => $wcap_abandoned_cart_id ) );

                            /* this user id is set for the guest uesrs. */
                            if ( isset( $_SESSION['user_id'] ) && $_SESSION['user_id'] != '' ) {

                                $wcal_user_id_of_guest = $_SESSION['user_id'];
                                $wpdb->delete( $wcal_guest_table_name,  array( 'id' => $wcal_user_id_of_guest ) );
                            }
                        } 
                    }
                }
            }
        }
        
        public function wcal_order_complete_action( $order_status, $order_id ) {                    
            
            /**
             * If the order status is not pending or failed then we will check the order and its respective abandoned
             * cart data. 
             */
            if ( 'pending' != $order_status && 'failed' != $order_status ) {
                global $woocommerce, $wpdb;
                $order = new WC_Order( $order_id );

                $get_abandoned_id_of_order  = get_post_meta( $order_id, 'wcal_recover_order_placed', true );
                $get_sent_email_id_of_order = get_post_meta( $order_id, 'wcal_recover_order_placed_sent_id', true );

                $wcal_ac_table_name                 = $wpdb->prefix . "ac_abandoned_cart_history_lite";
                $wcal_email_sent_history_table_name = $wpdb->prefix . "ac_sent_history_lite";
                $wcal_guest_ac_table_name           = $wpdb->prefix . "ac_guest_abandoned_cart_history_lite";
                
                /**
                 * Here, in this condition we are checking that if abadoned cart id has any record for the reminder
                 * email is sent or not.
                 * If the reminde email is sent to the abandoned cart id the mark that cart as a recovered.
                 */
                if ( isset( $get_sent_email_id_of_order ) && '' != $get_sent_email_id_of_order ) {

                    $query_order = "UPDATE $wcal_ac_table_name SET recovered_cart = '" . $order_id . "', cart_ignored = '1' WHERE id = '".$get_abandoned_id_of_order."' ";
                    $wpdb->query( $query_order );
        
                    $order->add_order_note( __( 'This order was abandoned & subsequently recovered.', 'woocommerce-ac' ) );

                    delete_post_meta( $order_id,  'wcal_recover_order_placed',         $get_abandoned_id_of_order );
                    delete_post_meta( $order_id , 'wcal_recover_order_placed_sent_id', $get_sent_email_id_of_order );
                } else if ( isset( $get_abandoned_id_of_order ) && '' != $get_abandoned_id_of_order ) {
                    
                    /**
                     * If the recover email has not sent then we will delete the abandoned cart data.
                     */
                    $get_abandoned_cart_user_id_query   = "SELECT user_id FROM  $wcal_ac_table_name  WHERE id = %d ";
                    $get_abandoned_cart_user_id_results = $wpdb->get_results( $wpdb->prepare( $get_abandoned_cart_user_id_query, $get_abandoned_id_of_order ) );

                    $var = $wpdb->prepare( $get_abandoned_cart_user_id_query, $get_abandoned_id_of_order );
                  
                    if ( count( $get_abandoned_cart_user_id_results ) > 0 ) {
                        $wcap_user_id = $get_abandoned_cart_user_id_results[0]->user_id;

                        if ( $wcap_user_id >= 63000000 ){
                            $wpdb->delete( $wcal_guest_ac_table_name ,   array( 'id' => $wcap_user_id ) );
                        }

                        $wpdb->delete( $wcal_ac_table_name  , array( 'id' => $get_abandoned_id_of_order ) );
                        delete_post_meta( $order_id,  'wcap_recover_order_placed', $get_abandoned_id_of_order );
                    }
                }
            }
            return $order_status;
        }
            
        /**
         * Check If Pro is activated along with Lite version.
         */
        public static function wcal_check_pro_activated() {
            if( is_plugin_active( 'woocommerce-abandon-cart-pro/woocommerce-ac.php' ) && class_exists( 'woocommerce_abandon_cart' ) ) {
                 add_action( 'admin_notices', array( 'woocommerce_abandon_cart_lite', 'wcal_check_pro_notice' ) );
            }
        }
            
        /**
         * Display a notice in the admin Plugins page if the LITE version is
         * activated with PRO version is activated.
         */
        public static function wcal_check_pro_notice() {
            $class   = 'notice notice-error is-dismissible';
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
        public function wcal_preview_emails() {
            global $woocommerce;
            if( isset( $_GET['wcal_preview_woocommerce_mail'] ) ) {
                if( ! wp_verify_nonce( $_REQUEST['_wpnonce'], 'woocommerce-ac') ) {
                    die( 'Security check' );
                }
                $message = '';
                // create a new email
                if( $woocommerce->version < '2.3' ) {
                    global $email_heading;                   
                    ob_start();
                    
                    include( 'views/wcal-wc-email-template-preview.php' );
                    $mailer        = WC()->mailer();
                    $message       = ob_get_clean();
                    $email_heading = __( 'HTML Email Template', 'woocommerce-ac' );
                    $message       =  $mailer->wrap_message( $email_heading, $message );
                } else {
                    // load the mailer class
                    $mailer        = WC()->mailer(); 
                    // get the preview email subject
                    $email_heading = __( 'Abandoned cart Email Template', 'woocommerce-ac' );       
                    // get the preview email content
                    ob_start();
                    include( 'views/wcal-wc-email-template-preview.php' );
                    $message       = ob_get_clean();        
                    // create a new email
                    $email         = new WC_Email();        
                    // wrap the content with the email template and then add styles
                    $message       = $email->style_inline( $mailer->wrap_message( $email_heading, $message ) );
                }       
                echo $message;
                exit;
            }
        
            if ( isset( $_GET['wcal_preview_mail'] ) ) {
                if ( ! wp_verify_nonce( $_REQUEST['_wpnonce'], 'woocommerce-ac') ) {
                    die( 'Security check' );
                }
                // get the preview email content
                ob_start();
                include( 'views/wcal-email-template-preview.php' );
                $message       = ob_get_clean();        
                // print the preview email
                echo $message;
                exit;
            }
        }

        // Language Translation
        function  wcal_update_po_file() {
            $domain = 'woocommerce-ac';
            $locale = apply_filters( 'plugin_locale', get_locale(), $domain );
            if ( $loaded = load_textdomain( $domain, trailingslashit( WP_LANG_DIR ) . $domain . '-' . $locale . '.mo' ) ) {
                return $loaded;
            } else {
                load_plugin_textdomain( $domain, FALSE, basename( dirname( __FILE__ ) ) . '/i18n/languages/' );
            }
        }
    
        /*-----------------------------------------------------------------------------------*/
        /* Class Functions */
        /*-----------------------------------------------------------------------------------*/                             
        function wcal_activate() {
            global $wpdb; 
            $wcap_collate = '';
            if ( $wpdb->has_cap( 'collation' ) ) {
                $wcap_collate = $wpdb->get_charset_collate();
            }
            $table_name = $wpdb->prefix . "ac_email_templates_lite";            
            $sql = "CREATE TABLE IF NOT EXISTS $table_name (
                    `id` int(11) NOT NULL AUTO_INCREMENT,
                    `subject` text COLLATE utf8_unicode_ci NOT NULL,
                    `body` mediumtext COLLATE utf8_unicode_ci NOT NULL,
                    `is_active` enum('0','1') COLLATE utf8_unicode_ci NOT NULL,
                    `frequency` int(11) NOT NULL,
                    `day_or_hour` enum('Days','Hours') COLLATE utf8_unicode_ci NOT NULL,
                    `template_name` text COLLATE utf8_unicode_ci NOT NULL,
                    PRIMARY KEY (`id`)
                    ) $wcap_collate AUTO_INCREMENT=1 ";
        
            require_once ( ABSPATH . 'wp-admin/includes/upgrade.php' );
            dbDelta( $sql );
            
            $table_name = $wpdb->prefix . "ac_email_templates_lite";
            $check_template_table_query = "SHOW COLUMNS FROM $table_name LIKE 'is_wc_template' ";
            $results = $wpdb->get_results( $check_template_table_query );
             
            if ( count( $results ) == 0 ) {
                $alter_template_table_query = "ALTER TABLE $table_name
                ADD COLUMN `is_wc_template` enum('0','1') COLLATE utf8_unicode_ci NOT NULL AFTER `template_name`,
                ADD COLUMN `default_template` int(11) NOT NULL AFTER `is_wc_template`";
                
                $wpdb->get_results( $alter_template_table_query );
            }
            
            $sent_table_name = $wpdb->prefix . "ac_sent_history_lite";
        
            $sql_query = "CREATE TABLE IF NOT EXISTS $sent_table_name (
                        `id` int(11) NOT NULL auto_increment,
                        `template_id` varchar(40) collate utf8_unicode_ci NOT NULL,
                        `abandoned_order_id` int(11) NOT NULL,
                        `sent_time` datetime NOT NULL,
                        `sent_email_id` text COLLATE utf8_unicode_ci NOT NULL,
                        PRIMARY KEY  (`id`)
                        ) $wcap_collate AUTO_INCREMENT=1 ";
             
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
                             ) $wcap_collate";
                     
            require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
            dbDelta( $history_query );
            
            // Default templates:  function call to create default templates.
            $check_table_empty  = $wpdb->get_var( "SELECT COUNT(*) FROM `" . $wpdb->prefix . "ac_email_templates_lite`" );
            
            if( !get_option( 'woocommerce_ac_default_templates_installed' ) ) {         
                if( 0 == $check_table_empty ) {
                    $default_template = new wcal_default_template_settings;
                    $default_template->wcal_create_default_templates();
                    update_option( 'woocommerce_ac_default_templates_installed', "yes" );
                }
            }

            $guest_table        = $wpdb->prefix."ac_guest_abandoned_cart_history_lite" ;
            $query_guest_table  = "SHOW TABLES LIKE '$guest_table' ";
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
                ) $wcap_collate AUTO_INCREMENT=63000000";
                require_once( ABSPATH . 'wp-admin/includes/upgrade.php');
                $wpdb->query( $ac_guest_history_query );
            } 

            /**
             * This is add for thos user who Install the plguin first time.
             * So for them this option will be cheked.
             */
            if( !get_option( 'ac_lite_track_guest_cart_from_cart_page' ) ) {
                add_option( 'ac_lite_track_guest_cart_from_cart_page', 'on' );
            } 
            if( !get_option( 'wcal_from_name' ) ) {
                add_option( 'wcal_from_name', 'Admin' );
            }
            $wcal_get_admin_email = get_option( 'admin_email' );
            if( !get_option( 'wcal_from_email' ) ) {
                add_option( 'wcal_from_email', $wcal_get_admin_email );
            }
            
            if( !get_option( 'wcal_reply_email' ) ) {
                add_option( 'wcal_reply_email', $wcal_get_admin_email );
            }

            if( !get_option( 'wcal_activate_time' ) ) {
                add_option( 'wcal_activate_time', current_time( 'timestamp' ) );
            }
       }     
    
        /***************************************************************
         * WP Settings API
         **************************************************************/
        function wcal_initialize_plugin_options() {

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
            
            add_settings_field(
            'ac_lite_track_guest_cart_from_cart_page',
            __( 'Start tracking from Cart Page', 'woocommerce-ac' ),
            array( $this, 'wcal_track_guest_cart_from_cart_page_callback' ),
            'woocommerce_ac_page',
            'ac_lite_general_settings_section',
            array( __( 'Enable tracking of abandoned products & carts even if customer does not visit the checkout page or does not enter any details on the checkout page like Name or Email. Tracking will begin as soon as a visitor adds a product to their cart and visits the cart page.', 'woocommerce-ac' ) )
            );
            /*
             * New section for the Adding the abandoned cart setting.
             * Since @: 4.7
             */
            
            add_settings_section(
            'ac_email_settings_section',           // ID used to identify this section and with which to register options
            __( 'Settings for abandoned cart recovery emails', 'woocommerce-ac' ),      // Title to be displayed on the administration page
            array($this, 'wcal_email_callback' ),// Callback used to render the description of the section
            'woocommerce_ac_email_page'     // Page on which to add this section of options
            );
            
            add_settings_field(
            'wcal_from_name',
            __( '"From" Name', 'woocommerce-ac'  ),
            array( $this, 'wcal_from_name_callback' ),
            'woocommerce_ac_email_page',
            'ac_email_settings_section',
            array( 'Enter the name that should appear in the email sent.', 'woocommerce-ac' )
            );
            
            add_settings_field(
            'wcal_from_email',
            __( '"From" Address', 'woocommerce-ac'  ),
            array( $this, 'wcal_from_email_callback' ),
            'woocommerce_ac_email_page',
            'ac_email_settings_section',
            array( 'Email address from which the reminder emails should be sent.', 'woocommerce-ac' )
            );
            
            add_settings_field(
            'wcal_reply_email',
            __( 'Send Reply Emails to', 'woocommerce-ac'  ),
            array( $this, 'wcal_reply_email_callback' ),
            'woocommerce_ac_email_page',
            'ac_email_settings_section',
            array( 'When a contact receives your email and clicks reply, which email address should that reply be sent to?', 'woocommerce-ac' )
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
             
            register_setting(
                'woocommerce_ac_settings',
                'ac_lite_track_guest_cart_from_cart_page'
            );
            
            register_setting(
                'woocommerce_ac_email_settings',
                'wcal_from_name'
                );
            register_setting(
                'woocommerce_ac_email_settings',
                'wcal_from_email'
            );
            register_setting(
                'woocommerce_ac_email_settings',
                'wcal_reply_email'
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
        function ac_lite_cart_abandoned_time_callback( $args ) {
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
            if ( $input != '' && ( is_numeric( $input) && $input > 0  ) ) {
                $output = stripslashes( $input) ;
            } else {
                add_settings_error( 'ac_lite_cart_abandoned_time', 'error found', __( 'Abandoned cart cut off time should be numeric and has to be greater than 0.', 'woocommerce-ac' ) );
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
            if( isset( $email_admin_on_recovery ) && $email_admin_on_recovery == '' ) {
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
        /***************************************************************
         * @since : 2.7
         * WP Settings API callback for capturing guest cart which do not reach the checkout page.
         **************************************************************/
        function wcal_track_guest_cart_from_cart_page_callback( $args ) {
            // First, we read the option
            $disable_guest_cart_from_cart_page = get_option( 'ac_lite_track_guest_cart_from_cart_page' );
           
            // This condition added to avoid the notice displyed while Check box is unchecked.
            if ( isset( $disable_guest_cart_from_cart_page ) && $disable_guest_cart_from_cart_page == '' ) {
                $disable_guest_cart_from_cart_page = 'off';
            }
            // Next, we update the name attribute to access this element's ID in the context of the display options array
            // We also access the show_header element of the options collection in the call to the checked() helper function
            $html     = '';
            
            printf(
            '<input type="checkbox" id="ac_lite_track_guest_cart_from_cart_page" name="ac_lite_track_guest_cart_from_cart_page" value="on"
                '.checked( 'on', $disable_guest_cart_from_cart_page, false ) . ' />' );
            // Here, we'll take the first argument of the array and add it to a label next to the checkbox
            $html .= '<label for="ac_lite_track_guest_cart_from_cart_page"> ' . $args[0] . '</label>';
            echo $html;
        }
        
        /***************************************************************
         * WP Settings API callback for Abandoned cart email settings of the plugin
         **************************************************************/
        function wcal_email_callback () {
        
        }
        
        public static function wcal_from_name_callback( $args ) {
            // First, we read the option
            $wcal_from_name = get_option( 'wcal_from_name' );
            // Next, we update the name attribute to access this element's ID in the context of the display options array
            // We also access the show_header element of the options collection in the call to the checked() helper function
            printf(
            '<input type="text" id="wcal_from_name" name="wcal_from_name" value="%s" />',
            isset( $wcal_from_name ) ? esc_attr( $wcal_from_name ) : ''
                );
                // Here, we'll take the first argument of the array and add it to a label next to the checkbox
                $html = '<label for="wcal_from_name_label"> '  . $args[0] . '</label>';
                echo $html;
        }
        
        public static function wcal_from_email_callback( $args ) {
            // First, we read the option
            $wcal_from_email = get_option( 'wcal_from_email' );
            // Next, we update the name attribute to access this element's ID in the context of the display options array
            // We also access the show_header element of the options collection in the call to the checked() helper function
            printf(
            '<input type="text" id="wcal_from_email" name="wcal_from_email" value="%s" />',
            isset( $wcal_from_email ) ? esc_attr( $wcal_from_email ) : ''
                );
                // Here, we'll take the first argument of the array and add it to a label next to the checkbox
                $html = '<label for="wcal_from_email_label"> '  . $args[0] . '</label>';
                echo $html;
        }
        
        public static function wcal_reply_email_callback( $args ) {
            // First, we read the option
            $wcal_reply_email = get_option( 'wcal_reply_email' );
            // Next, we update the name attribute to access this element's ID in the context of the display options array
            // We also access the show_header element of the options collection in the call to the checked() helper function
            printf(
            '<input type="text" id="wcal_reply_email" name="wcal_reply_email" value="%s" />',
            isset( $wcal_reply_email ) ? esc_attr( $wcal_reply_email ) : ''
                );
                // Here, we'll take the first argument of the array and add it to a label next to the checkbox
                $html = '<label for="wcal_reply_email_label"> '  . $args[0] . '</label>';
                echo $html;
        }
        /**************************************************
         * This function is run when the plugin is upgraded
         *************************************************/
        function wcal_update_db_check() {
            global $wpdb;
            
            if( get_option( 'ac_lite_alter_table_queries' ) != 'yes' ) {

                $ac_history_table_name = $wpdb->prefix."ac_abandoned_cart_history_lite";
                $check_table_query     = "SHOW COLUMNS FROM $ac_history_table_name LIKE 'user_type'";
                $results               = $wpdb->get_results( $check_table_query );
                
                if ( count( $results ) == 0 ) {
                    $alter_table_query = "ALTER TABLE $ac_history_table_name ADD `user_type` text AFTER  `recovered_cart`";
                    $wpdb->get_results( $alter_table_query );
                }
        
                $table_name                 = $wpdb->prefix . "ac_email_templates_lite";
                $check_template_table_query = "SHOW COLUMNS FROM $table_name LIKE 'is_wc_template' ";
                $results                    = $wpdb->get_results( $check_template_table_query );
                 
                if ( count( $results ) == 0 ) {
                    $alter_template_table_query = "ALTER TABLE $table_name
                    ADD COLUMN `is_wc_template` enum('0','1') COLLATE utf8_unicode_ci NOT NULL AFTER `template_name`,
                    ADD COLUMN `default_template` int(11) NOT NULL AFTER `is_wc_template`";
                    $wpdb->get_results( $alter_template_table_query );
                }
               
                
                $table_name                       = $wpdb->prefix . "ac_email_templates_lite";
                $check_email_template_table_query = "SHOW COLUMNS FROM $table_name LIKE 'wc_email_header' ";
                $results_email                    = $wpdb->get_results( $check_email_template_table_query );
                
                if ( count(  $results_email ) == 0 ) {
                    $alter_email_template_table_query = "ALTER TABLE $table_name
                    ADD COLUMN `wc_email_header` varchar(50) COLLATE utf8_unicode_ci NOT NULL AFTER `default_template`";
                    $wpdb->get_results( $alter_email_template_table_query );
                }

                if ( $wpdb->get_var( "SHOW TABLES LIKE '{$wpdb->prefix}ac_abandoned_cart_history_lite';" ) ) {
                    if ( ! $wpdb->get_var( "SHOW COLUMNS FROM `{$wpdb->prefix}ac_abandoned_cart_history_lite` LIKE 'unsubscribe_link';" ) ) {
                        $wpdb->query( "ALTER TABLE {$wpdb->prefix}ac_abandoned_cart_history_lite ADD `unsubscribe_link` enum('0','1') COLLATE utf8_unicode_ci NOT NULL AFTER  `user_type`;" );
                    }
                }
            
                if ( $wpdb->get_var( "SHOW TABLES LIKE '{$wpdb->prefix}ac_abandoned_cart_history_lite';" ) ) {
                    if ( ! $wpdb->get_var( "SHOW COLUMNS FROM `{$wpdb->prefix}ac_abandoned_cart_history_lite` LIKE 'session_id';" ) ) {
                        $wpdb->query( "ALTER TABLE {$wpdb->prefix}ac_abandoned_cart_history_lite ADD `session_id` varchar(50) COLLATE utf8_unicode_ci NOT NULL AFTER `unsubscribe_link`;" );
                    }
                }           
                /**
                 *  
                 * This is used to prevent guest users wrong Id. If guest users id is less then 63000000 then this code will ensure that we 
                 * will change the id of guest tables so it wont affect on the next guest users.
                 */         
                if ( $wpdb->get_var( "SHOW TABLES LIKE '{$wpdb->prefix}ac_guest_abandoned_cart_history_lite';" )  && 'yes' != get_option( 'wcal_guest_user_id_altered' ) ) {
                    $last_id = $wpdb->get_var( "SELECT max(id) FROM `{$wpdb->prefix}ac_guest_abandoned_cart_history_lite`;" );
                    if ( NULL != $last_id && $last_id <= 63000000 ) {
                        $wpdb->query( "ALTER TABLE {$wpdb->prefix}ac_guest_abandoned_cart_history_lite AUTO_INCREMENT = 63000000;" );
                        update_option ( 'wcal_guest_user_id_altered', 'yes' );
                    }
                }
                
                /**
                 * We have moved email templates fields in the setings section. SO to remove that fields column fro the db we need it.
                 * For existing user we need to fill this setting with the first template. 
                 * @since 4.7
                 */
                if ( $wpdb->get_var( "SHOW TABLES LIKE '{$wpdb->prefix}ac_email_templates_lite';" ) ) {
                    if ( $wpdb->get_var( "SHOW COLUMNS FROM `{$wpdb->prefix}ac_email_templates_lite` LIKE 'from_email';" ) ) {
                        $get_email_template_query  = "SELECT `from_email` FROM {$wpdb->prefix}ac_email_templates_lite WHERE `is_active` = '1' ORDER BY `id` ASC LIMIT 1";
                        $get_email_template_result = $wpdb->get_results ($get_email_template_query);
                        $wcal_from_email = '';
                        if ( isset( $get_email_template_result ) && count ( $get_email_template_result ) > 0 ){
                            $wcal_from_email =  $get_email_template_result[0]->from_email;          
                            /* Store data in setings api*/
                            update_option ( 'wcal_from_email', $wcal_from_email );          
                            /* Delete table from the Db*/
                            $wpdb->query( "ALTER TABLE {$wpdb->prefix}ac_email_templates_lite DROP COLUMN `from_email`;" );
                        }
                    }
                
                    if ( $wpdb->get_var( "SHOW COLUMNS FROM `{$wpdb->prefix}ac_email_templates_lite` LIKE 'from_name';" ) ) {
                        $get_email_template_from_name_query  = "SELECT `from_name` FROM {$wpdb->prefix}ac_email_templates_lite WHERE `is_active` = '1' ORDER BY `id` ASC LIMIT 1";
                        $get_email_template_from_name_result = $wpdb->get_results ($get_email_template_from_name_query);            
                        $wcal_from_name = '';
                        if ( isset( $get_email_template_from_name_result ) && count ( $get_email_template_from_name_result ) > 0 ){
                            $wcal_from_name =  $get_email_template_from_name_result[0]->from_name;          
                            /* Store data in setings api*/
                            add_option ( 'wcal_from_name', $wcal_from_name );           
                            /* Delete table from the Db*/
                            $wpdb->query( "ALTER TABLE {$wpdb->prefix}ac_email_templates_lite DROP COLUMN `from_name`;" );
                        }
                    }
                
                    if ( $wpdb->get_var( "SHOW COLUMNS FROM `{$wpdb->prefix}ac_email_templates_lite` LIKE 'reply_email';" ) ) {
                        $get_email_template_reply_email_query  = "SELECT `reply_email` FROM {$wpdb->prefix}ac_email_templates_lite WHERE `is_active` = '1' ORDER BY `id` ASC LIMIT 1";
                        $get_email_template_reply_email_result = $wpdb->get_results ($get_email_template_reply_email_query);            
                        $wcal_reply_email = '';
                        if ( isset( $get_email_template_reply_email_result ) && count ( $get_email_template_reply_email_result ) > 0 ){
                            $wcal_reply_email =  $get_email_template_reply_email_result[0]->reply_email;            
                            /* Store data in setings api*/
                            update_option ( 'wcal_reply_email', $wcal_reply_email );            
                            /* Delete table from the Db*/
                            $wpdb->query( "ALTER TABLE {$wpdb->prefix}ac_email_templates_lite DROP COLUMN `reply_email`;" );
                        }
                    }
                }
 
                if ( !get_option( 'wcal_security_key' ) ){
                    update_option( 'wcal_security_key', 'qJB0rGtIn5UB1xG03efyCp' );
                }

                update_option( 'ac_lite_alter_table_queries', 'yes' );
            }
            
            //get the option, if it is not set to individual then convert to individual records and delete the base record
            $ac_settings = get_option( 'ac_lite_settings_status' );     
            if ( $ac_settings != 'INDIVIDUAL' ) {
                //fetch the existing settings and save them as inidividual to be used for the settings API
                $woocommerce_ac_settings = json_decode( get_option( 'woocommerce_ac_settings' ) );              
                if( isset( $woocommerce_ac_settings[0]->cart_time ) ) {
                    add_option( 'ac_lite_cart_abandoned_time', $woocommerce_ac_settings[0]->cart_time );
                } else {
                    add_option( 'ac_lite_cart_abandoned_time', '10' );
                }
            
                if( isset( $woocommerce_ac_settings[0]->email_admin ) ) {
                    add_option( 'ac_lite_email_admin_on_recovery', $woocommerce_ac_settings[0]->email_admin );
                } else {
                    add_option( 'ac_lite_email_admin_on_recovery', "" );
                } 
                 
                if( isset( $woocommerce_ac_settings[0]->disable_guest_cart_from_cart_page ) ) {
                   add_option( 'ac_lite_track_guest_cart_from_cart_page',  $woocommerce_ac_settings[0]->disable_guest_cart_from_cart_page );
                } else {
                   add_option( 'ac_lite_track_guest_cart_from_cart_page', "" );
                }
                
                update_option( 'ac_lite_settings_status', 'INDIVIDUAL' );
                //Delete the main settings record
                delete_option( 'woocommerce_ac_settings' );
            }
        }
    
        /**
         * Send email to admin when cart is recovered only via PayPal.
         * @since 2.9 version
         */
        public static function wcal_email_admin_recovery_for_paypal ( $order_id, $old, $new_status ) {           
            if ( 'pending' == $old && 'processing' == $new_status ) {
                global $wpdb, $woocommerce;
                $user_id                 = get_current_user_id();
                $ac_email_admin_recovery = get_option( 'ac_lite_email_admin_on_recovery' );     
                $order                   = new WC_Order( $order_id );
                if( version_compare( $woocommerce->version, '3.0.0', ">=" ) ) {            
                    $user_id              = $order->get_user_id();          
                }else{
                    $user_id              = $order->user_id; 
                }
               
                if( $ac_email_admin_recovery == 'on' ) {
                    $recovered_email_sent = get_post_meta( $order_id, 'wcap_recovered_email_sent', true );
                    $check_abandoned_cart = get_user_meta( $user_id, '_woocommerce_ac_modified_cart', true );
                    $created_via   = get_post_meta ( $order_id, '_created_via', true );
                    
                    if ( 'checkout' == $created_via && 'yes' != $recovered_email_sent && ( $check_abandoned_cart == md5( "yes" ) || $check_abandoned_cart == md5( "no" ) ) ) { // indicates cart is abandoned
                        $order          = new WC_Order( $order_id );
                        $email_heading  = __( 'New Customer Order - Recovered', 'woocommerce-ac' );
                        $blogname       = wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES );
                        $email_subject  = "New Customer Order - Recovered";
                        $user_email     = get_option( 'admin_email' );
                        $headers[]      = "From: Admin <".$user_email.">";
                        $headers[]      = "Content-Type: text/html";
                        // Buffer
                        ob_start();
                        // Get mail template
                        wc_get_template( 'emails/admin-new-order.php', array(
                            'order'         => $order,
                            'email_heading' => $email_heading,
                            'sent_to_admin' => false,
                            'plain_text'    => false,
                            'email'         => true
                            )
                        );
                        // Get contents
                        $email_body = ob_get_clean();
                        wc_mail( $user_email, $email_subject, $email_body, $headers );                      
                        update_post_meta( $order_id, 'wcap_recovered_email_sent', 'yes' );                      
                    }
                }
            }
        }
    
        /**
         * Send email to admin when cart is recovered via any other payment gateway other than PayPal.
         * @since 2.3 version
         */
        function wcal_email_admin_recovery ( $order_id ) { 
            global $wpdb, $woocommerce;
                
            $user_id                 = get_current_user_id();  
            $ac_email_admin_recovery = get_option( 'ac_lite_email_admin_on_recovery' );         
            if( $ac_email_admin_recovery == 'on' ) {
                $order                = new WC_Order( $order_id );
                
                if( version_compare( $woocommerce->version, '3.0.0', ">=" ) ) {            
                    $user_id              = $order->get_user_id();          
                }else{
                    $user_id              = $order->user_id; 
                }
                $recovered_email_sent = get_post_meta( $order_id, 'wcap_recovered_email_sent', true );
                $check_abandoned_cart = get_user_meta( $user_id, '_woocommerce_ac_modified_cart', true );
                $created_via          = get_post_meta( $order_id, '_created_via', true );               
                if ( 'checkout' == $created_via && 'yes' != $recovered_email_sent && ( $check_abandoned_cart == md5( "yes" ) || $check_abandoned_cart == md5( "no" ) ) ) { // indicates cart is abandoned                           
                    $email_heading = __( 'New Customer Order - Recovered', 'woocommerce-ac' );          
                    $blogname      = wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES );
                    $email_subject = "New Customer Order - Recovered";
                    $user_email    = get_option( 'admin_email' );
                    $headers[]     = "From: Admin <".$user_email.">";
                    $headers[]     = "Content-Type: text/html";
                    // Buffer
                    ob_start();
                    // Get mail template
                    wc_get_template( 'emails/admin-new-order.php', array(
                    'order'         => $order,
                    'email_heading' => $email_heading,
                    'sent_to_admin' => false,
                    'plain_text'    => false,
                    'email'         => true
                    ) );
                    // Get contents
                    $email_body = ob_get_clean();
                    
                    wc_mail( $user_email, $email_subject, $email_body, $headers );
                    
                    update_post_meta( $order_id, 'wcap_recovered_email_sent', 'yes' );
                }
            }
        }
            
        // Add a submenu page.
        function wcal_admin_menu() {
            $page = add_submenu_page ( 'woocommerce', __( 'Abandoned Carts', 'woocommerce-ac' ), __( 'Abandoned Carts', 'woocommerce-ac' ), 'manage_woocommerce', 'woocommerce_ac_page', array( &$this, 'wcal_menu_page' ) );
        }
            
        // Capture the cart and insert the information of the cart into DataBase
        function wcal_store_cart_timestamp() {  
            
            if( session_id() === '' ){
                //session has not started
                session_start();
            } 
            global $wpdb,$woocommerce;
            $current_time   = current_time( 'timestamp' );
            $cut_off_time   = get_option( 'ac_lite_cart_abandoned_time' );              
            $track_guest_cart_from_cart_page = get_option( 'ac_lite_track_guest_cart_from_cart_page' );
            $cart_ignored   = 0;
            $recovered_cart = 0;  
            
            $track_guest_user_cart_from_cart = "";          
            if ( isset( $track_guest_cart_from_cart_page ) ) {
                $track_guest_user_cart_from_cart = $track_guest_cart_from_cart_page;
            }
            
            if( isset( $cut_off_time ) ) {
                $cart_cut_off_time = intval( $cut_off_time ) * 60;
            } else {
                $cart_cut_off_time = 60 * 60;
            }           
            $compare_time = $current_time - $cart_cut_off_time;
            
            if ( is_user_logged_in() ) {    
                $user_id = get_current_user_id();
                $query   = "SELECT * FROM `".$wpdb->prefix."ac_abandoned_cart_history_lite`
                            WHERE user_id      = %d
                            AND cart_ignored   = %s
                            AND recovered_cart = %d ";
                $results = $wpdb->get_results($wpdb->prepare( $query, $user_id, $cart_ignored, $recovered_cart ) );
                
                if ( count($results) == 0 ) {                   
                    $wcal_woocommerce_persistent_cart =version_compare( $woocommerce->version, '3.1.0', ">=" ) ? '_woocommerce_persistent_cart_' . get_current_blog_id() : '_woocommerce_persistent_cart' ;
                                    
                    $cart_info = json_encode( get_user_meta( $user_id, $wcal_woocommerce_persistent_cart, true ) );                 
                    $user_type = "REGISTERED";
                    $insert_query = "INSERT INTO `".$wpdb->prefix."ac_abandoned_cart_history_lite`
                                     ( user_id, abandoned_cart_info, abandoned_cart_time, cart_ignored, user_type )
                                     VALUES ( %d, %s, %d, %s, %s )";
                    $wpdb->query( $wpdb->prepare( $insert_query, $user_id, $cart_info,$current_time, $cart_ignored, $user_type ) );
                    
                    $abandoned_cart_id              = $wpdb->insert_id;                 
                    $_SESSION['abandoned_cart_id_lite'] = $abandoned_cart_id;
                } elseif ( isset( $results[0]->abandoned_cart_time ) && $compare_time > $results[0]->abandoned_cart_time ) {
                    
                    $wcal_woocommerce_persistent_cart = version_compare( $woocommerce->version, '3.1.0', ">=" ) ? '_woocommerce_persistent_cart_' . get_current_blog_id() : '_woocommerce_persistent_cart' ; 
                    $updated_cart_info = json_encode( get_user_meta( $user_id, $wcal_woocommerce_persistent_cart, true ) );
                    
                    if ( ! $this->wcal_compare_carts( $user_id, $results[0]->abandoned_cart_info ) ) {                          
                        $updated_cart_ignored = 1;
                        $query_ignored = "UPDATE `".$wpdb->prefix."ac_abandoned_cart_history_lite`
                                          SET cart_ignored = %s
                                          WHERE user_id    = %d ";
                        $wpdb->query( $wpdb->prepare( $query_ignored, $updated_cart_ignored, $user_id ) );
                        
                        $user_type    = "REGISTERED";
                        $query_update = "INSERT INTO `".$wpdb->prefix."ac_abandoned_cart_history_lite`
                                         (user_id, abandoned_cart_info, abandoned_cart_time, cart_ignored, user_type)
                                         VALUES (%d, %s, %d, %s, %s)";
                        $wpdb->query( $wpdb->prepare( $query_update, $user_id, $updated_cart_info, $current_time, $cart_ignored, $user_type ) );
                        
                        update_user_meta ( $user_id, '_woocommerce_ac_modified_cart', md5( "yes" ) );
                        
                        $abandoned_cart_id                  = $wpdb->insert_id;                     
                        $_SESSION['abandoned_cart_id_lite'] = $abandoned_cart_id;                       
                    } else {    
                        update_user_meta ( $user_id, '_woocommerce_ac_modified_cart', md5( "no" ) );
                    }
                } else {                    
                    $wcal_woocommerce_persistent_cart =version_compare( $woocommerce->version, '3.1.0', ">=" ) ? '_woocommerce_persistent_cart_' . get_current_blog_id() : '_woocommerce_persistent_cart' ;
                    $updated_cart_info = json_encode( get_user_meta( $user_id, $wcal_woocommerce_persistent_cart, true ) );
                    
                    $query_update = "UPDATE `".$wpdb->prefix."ac_abandoned_cart_history_lite`
                                     SET abandoned_cart_info = %s,
                                         abandoned_cart_time = %d
                                     WHERE user_id      = %d 
                                     AND   cart_ignored = %s ";
                    $wpdb->query( $wpdb->prepare( $query_update, $updated_cart_info, $current_time, $user_id, $cart_ignored ) );
                    
                    $query_update        = "SELECT * FROM `" . $wpdb->prefix . "ac_abandoned_cart_history_lite` WHERE user_id ='" . $user_id . "' AND cart_ignored='0' ";                   
                    $get_abandoned_record = $wpdb->get_results( $query_update );
                    if ( count( $get_abandoned_record ) > 0 ) {
                        $abandoned_cart_id   = $get_abandoned_record[0]->id;
                        $_SESSION['abandoned_cart_id_lite'] = $abandoned_cart_id;
                    }
                }
            } else { 
                //start here guest user                 
                if ( isset( $_SESSION['user_id'] ) ) {                  
                    $user_id = $_SESSION['user_id'];
                } else {
                    $user_id = "";
                }
                
                $query   = "SELECT * FROM `".$wpdb->prefix."ac_abandoned_cart_history_lite` WHERE user_id = %d AND cart_ignored = '0' AND recovered_cart = '0' AND user_id != '0'";
                $results = $wpdb->get_results( $wpdb->prepare( $query, $user_id ) );
                $cart    = array();
                $get_cookie = WC()->session->get_session_cookie();
                if ( function_exists('WC') ) {
                    $cart['cart'] = WC()->session->cart;
                } else {
                    $cart['cart'] = $woocommerce->session->cart;
                }
                
                $updated_cart_info = json_encode($cart);
                $updated_cart_info = addslashes ( $updated_cart_info );
                
                if ( count($results) > 0 ) {                    
                    if ( $compare_time > $results[0]->abandoned_cart_time ) {                                                          
                        if ( ! $this->wcal_compare_only_guest_carts( $updated_cart_info, $results[0]->abandoned_cart_info ) ) {
                            
                            $query_ignored = "UPDATE `".$wpdb->prefix."ac_abandoned_cart_history_lite` 
                                             SET cart_ignored = '1' 
                                             WHERE user_id ='".$user_id."'";
                            $wpdb->query( $query_ignored );
                            $user_type    = 'GUEST';                
                            $query_update = "INSERT INTO `".$wpdb->prefix."ac_abandoned_cart_history_lite`
                                     (user_id, abandoned_cart_info, abandoned_cart_time, cart_ignored, user_type)
                                     VALUES (%d, %s, %d, %s, %s)";
                            $wpdb->query( $wpdb->prepare( $query_update, $user_id, $updated_cart_info, $current_time, $cart_ignored, $user_type ) );                                                                       
                            update_user_meta( $user_id, '_woocommerce_ac_modified_cart', md5("yes") );     
                        } else {
                            update_user_meta( $user_id, '_woocommerce_ac_modified_cart', md5("no") );
                        }  
                    } else {
                        $query_update = "UPDATE `".$wpdb->prefix."ac_abandoned_cart_history_lite` 
                                         SET abandoned_cart_info = '".$updated_cart_info."', abandoned_cart_time = '".$current_time."' 
                                         WHERE user_id='".$user_id."' AND cart_ignored='0' ";
                        $wpdb->query( $query_update );
                    }
                } else {                   
                    /**
                     * Here we capture the guest cart from the cart page.
                     * @since: 3.5
                     * 
                     */                  
                    if ( $track_guest_user_cart_from_cart == "on" &&  $get_cookie[0] != '' ) {                    
                        $query   = "SELECT * FROM `" . $wpdb->prefix . "ac_abandoned_cart_history_lite` WHERE session_id LIKE %s AND cart_ignored = '0' AND recovered_cart = '0' ";
                        $results = $wpdb->get_results( $wpdb->prepare( $query, $get_cookie[0] ) ); 
                        if ( count( $results ) == 0 ) {                        
                            $cart_info        = $updated_cart_info;
                            $blank_cart_info  = '[]';                        
                            if ( $blank_cart_info != $cart_info ) {
                                $insert_query = "INSERT INTO `" . $wpdb->prefix . "ac_abandoned_cart_history_lite`
                                                ( abandoned_cart_info , abandoned_cart_time , cart_ignored , recovered_cart, user_type, session_id  )
                                                VALUES ( '" . $cart_info."' , '" . $current_time . "' , '0' , '0' , 'GUEST', '". $get_cookie[0] ."' )";
                                $wpdb->query( $insert_query );
                            }                        
                        } elseif ( $compare_time > $results[0]->abandoned_cart_time ) {                        
                            $blank_cart_info  = '[]';                                
                            if ( $blank_cart_info != $updated_cart_info ) { 
                                if ( ! $this->wcal_compare_only_guest_carts( $updated_cart_info, $results[0]->abandoned_cart_info ) ) {                        
                                    $query_ignored = "UPDATE `" . $wpdb->prefix . "ac_abandoned_cart_history_lite` SET cart_ignored = '1' WHERE session_id ='" . $get_cookie[0] . "'";
                                    $wpdb->query( $query_ignored );
                                    $query_update = "INSERT INTO `" . $wpdb->prefix . "ac_abandoned_cart_history_lite`
                                                    ( abandoned_cart_info, abandoned_cart_time, cart_ignored, recovered_cart, user_type, session_id )
                                                    VALUES ( '" . $updated_cart_info . "', '" . $current_time . "', '0', '0', 'GUEST', '". $get_cookie[0] ."' )";
                                    $wpdb->query( $query_update );
                                }
                            }
                        } else {                        
                            $blank_cart_info   = '[]';                        
                            if ( $blank_cart_info != $updated_cart_info ) {                        
                                if ( ! $this->wcal_compare_only_guest_carts( $updated_cart_info, $results[0]->abandoned_cart_info ) ) {
                                    $query_update = "UPDATE `" . $wpdb->prefix . "ac_abandoned_cart_history_lite` SET abandoned_cart_info = '" . $updated_cart_info . "', abandoned_cart_time  = '" . $current_time . "' WHERE session_id ='" . $get_cookie[0] . "' AND cart_ignored='0' ";
                                    $wpdb->query( $query_update );
                                }
                            }
                        }
                    }
                }                               
            }
        }
        
        // Decrypt Function
        function wcal_decrypt_validate( $validate ) {
            $validate_decoded = '';
            if( function_exists( "mcrypt_encrypt" ) ) {                
                $cryptKey         = get_option( 'wcal_security_key' );
                $validate_decoded = rtrim( mcrypt_decrypt( MCRYPT_RIJNDAEL_256, md5( $cryptKey ), base64_decode( $validate ), MCRYPT_MODE_CBC, md5( md5( $cryptKey ) ) ), "\0");
            }else {
                $validate_decoded = base64_decode ( $validate );
            }
            return( $validate_decoded );
        }

        function wcal_email_unsubscribe( $args ) {
            global $wpdb;
            
            if ( isset( $_GET['wcal_track_unsubscribe'] ) && $_GET['wcal_track_unsubscribe'] == 'wcal_unsubscribe' ) {
                $encoded_email_id              = rawurldecode( $_GET['validate'] );
                $validate_email_id_string      = str_replace( " " , "+", $encoded_email_id);
                $validate_email_address_string = '';
                $validate_email_id_decode      = 0;        
                if( isset( $_GET['track_email_id'] ) ) {
                    $encoded_email_address         = rawurldecode( $_GET['track_email_id'] );
                    $validate_email_address_string = str_replace( " " , "+", $encoded_email_address );
                    if( isset( $validate_email_id_string ) ) {
                        if( function_exists( "mcrypt_encrypt" ) ) {
                            $validate_email_id_decode  = $this->wcal_decrypt_validate( $validate_email_id_string );                    
                        } else {
                            $validate_email_id_decode = base64_decode( $validate_email_id_string );
                        }
                    }   
                    $validate_email_address_string = $validate_email_address_string;
                }       
                if( !preg_match('/^[1-9][0-9]*$/', $validate_email_id_decode ) ) { // This will decrypt more security
                    $cryptKey                 = get_option( 'wcal_security_key' );
                    $validate_email_id_decode = Wcal_Aes_Ctr::decrypt( $validate_email_id_string, $cryptKey, 256 );
                }        
                $query_id      = "SELECT * FROM `" . $wpdb->prefix . "ac_sent_history_lite` WHERE id = %d ";
                $results_sent  = $wpdb->get_results ( $wpdb->prepare( $query_id, $validate_email_id_decode ) );
                $email_address = '';        
                if( isset( $results_sent[0] ) ) {
                    $email_address =  $results_sent[0]->sent_email_id;
                }        
                if( $validate_email_address_string == hash( 'sha256', $email_address ) && '' != $email_address ) {  
                    $email_sent_id     = $validate_email_id_decode;
                    $get_ac_id_query   = "SELECT abandoned_order_id FROM `" . $wpdb->prefix . "ac_sent_history_lite` WHERE id = %d";
                    $get_ac_id_results = $wpdb->get_results( $wpdb->prepare( $get_ac_id_query , $email_sent_id ) );
                    $user_id           = 0;                    
                    if( isset( $get_ac_id_results[0] ) ) {
                        $get_user_id_query = "SELECT user_id FROM `" . $wpdb->prefix . "ac_abandoned_cart_history_lite` WHERE id = %d";
                        $get_user_results  = $wpdb->get_results( $wpdb->prepare( $get_user_id_query , $get_ac_id_results[0]->abandoned_order_id ) );
                    }
                    if( isset( $get_user_results[0] ) ) {
                        $user_id = $get_user_results[0]->user_id;
                    }
                     
                    $unsubscribe_query = "UPDATE `" . $wpdb->prefix . "ac_abandoned_cart_history_lite`
                                            SET unsubscribe_link = '1' 
                                            WHERE user_id= %d AND cart_ignored='0' ";
                    $wpdb->query( $wpdb->prepare( $unsubscribe_query , $user_id ) ); 
                    echo "Unsubscribed Successfully";
                    sleep( 2 );
                    $url = get_option( 'siteurl' );
                    ?>
                   <script>
                        location.href = "<?php echo $url; ?>";
                   </script>
                   <?php 
                }
            } else {
               return $args; 
            }
        }
    
        // It will track the URL of cart link from email  
        function wcal_email_track_links( $template ) {              
            global $woocommerce;
            $track_link = '';
        
            if ( isset( $_GET['wcal_action'] ) ) {                  
                $track_link = $_GET['wcal_action'];
            }       
            if ( $track_link == 'track_links' ) {
                if( session_id() === '' ) {
                    //session has not started
                    session_start();
                }            
                global $wpdb;
                $validate_server_string  = rawurldecode( $_GET ['validate'] );
                $validate_server_string  = str_replace( " " , "+", $validate_server_string );
                $validate_encoded_string = $validate_server_string;
                $link_decode_test        = base64_decode( $validate_encoded_string );       
                // it will check if any old email have open the link
                if ( preg_match( '/&url=/', $link_decode_test ) ) {                         
                    $link_decode = $link_decode_test;
                } else {
                    if( function_exists( "mcrypt_encrypt" ) ) {                     
                        $link_decode = $this->wcal_decrypt_validate( $validate_encoded_string );
                    } else {
                        $link_decode = base64_decode( $validate_encoded_string );
                    }
                }               
                if ( !preg_match( '/&url=/', $link_decode ) ) { // This will decrypt more security
                    $cryptKey    = get_option( 'wcal_security_key' );
                    $link_decode = Wcal_Aes_Ctr::decrypt( $validate_encoded_string, $cryptKey, 256 );
                }                             
                $sent_email_id_pos = strpos( $link_decode, '&' );               
                $email_sent_id     = substr( $link_decode , 0, $sent_email_id_pos );
                $_SESSION['email_sent_id'] = $email_sent_id;
                $url_pos           = strpos( $link_decode, '=' );
                $url_pos           = $url_pos + 1;
                $url               = substr( $link_decode, $url_pos );             
                $get_ac_id_query   = "SELECT abandoned_order_id FROM `".$wpdb->prefix."ac_sent_history_lite` WHERE id = %d";
                $get_ac_id_results = $wpdb->get_results( $wpdb->prepare( $get_ac_id_query, $email_sent_id ) );
                $get_user_results  = array();
                if ( count( $get_ac_id_results ) > 0 ) {
                    $get_user_id_query = "SELECT user_id FROM `".$wpdb->prefix."ac_abandoned_cart_history_lite` WHERE id = %d";
                    $get_user_results  = $wpdb->get_results( $wpdb->prepare( $get_user_id_query, $get_ac_id_results[0]->abandoned_order_id ) );
                }
                $user_id           = 0;     
                if ( isset( $get_user_results ) && count( $get_user_results ) > 0 ) { 
                    $user_id = $get_user_results[0]->user_id;
                }               
                if ( $user_id == 0 ) {
                    echo "Link expired";
                    exit;
                }               
                $user = wp_set_current_user( $user_id );                
                if ( $user_id >= "63000000" ) {
                    $query_guest   = "SELECT * from `". $wpdb->prefix."ac_guest_abandoned_cart_history_lite` WHERE id = %d";
                    $results_guest = $wpdb->get_results( $wpdb->prepare( $query_guest, $user_id ) );
                    $query_cart    = "SELECT recovered_cart FROM `".$wpdb->prefix."ac_abandoned_cart_history_lite` WHERE user_id = %d";
                    $results       = $wpdb->get_results( $wpdb->prepare( $query_cart, $user_id ) );                 
                    if ( $results_guest  && $results[0]->recovered_cart == '0' ) {
                        $_SESSION['guest_first_name'] = $results_guest[0]->billing_first_name;
                        $_SESSION['guest_last_name']  = $results_guest[0]->billing_last_name;
                        $_SESSION['guest_email']      = $results_guest[0]->email_id;
                        $_SESSION['user_id']          = $user_id;
                    } else {
                        wp_redirect( get_permalink( woocommerce_get_page_id( 'shop' ) ) );
                    }
                }
        
                if ( $user_id < "63000000" ) {
                    $user_login = $user->data->user_login;
                    wp_set_auth_cookie( $user_id );
                    $my_temp    = woocommerce_load_persistent_cart( $user_login, $user );
                    do_action( 'wp_login', $user_login, $user );        
                    if ( isset( $sign_in ) && is_wp_error( $sign_in ) ) {
                        echo $sign_in->get_error_message();
                        exit;
                    }
                } else  
                    $my_temp = $this->wcal_load_guest_persistent_cart( $user_id );
                   
                if ( $email_sent_id > 0 && is_numeric( $email_sent_id ) ) {                     
                    header( "Location: $url" );
                }
            } else
                return $template;
        }
    
        // load the information of the guest user
        function wcal_load_guest_persistent_cart() {                
            if ( isset( $_SESSION['user_id'] ) && '' != $_SESSION['user_id'] ) {
                global $woocommerce;
                $saved_cart = json_decode( get_user_meta( $_SESSION['user_id'], '_woocommerce_persistent_cart',true ), true );
                $c          = array();
                $cart_contents_total = $cart_contents_weight = $cart_contents_count = $cart_contents_tax = $total = $subtotal = $subtotal_ex_tax = $tax_total = 0;
                if ( count( $saved_cart ) > 0 ) {
                    foreach ( $saved_cart as $key => $value ) {
                        foreach ( $value as $a => $b ) {  
                            $c['product_id']        = $b['product_id'];
                            $c['variation_id']      = $b['variation_id'];
                            $c['variation']         = $b['variation'];
                            $c['quantity']          = $b['quantity'];
                            $product_id             = $b['product_id'];
                            $c['data']              = wc_get_product($product_id);
                            $c['line_total']        = $b['line_total'];
                            $c['line_tax']          = $cart_contents_tax;
                            $c['line_subtotal']     = $b['line_subtotal'];
                            $c['line_subtotal_tax'] = $cart_contents_tax;
                            $value_new[ $a ]        = $c;
                            $cart_contents_total    = $b['line_subtotal'] + $cart_contents_total;
                            $cart_contents_count    = $cart_contents_count + $b['quantity'];
                            $total                  = $total + $b['line_total'];
                            $subtotal               = $subtotal + $b['line_subtotal'];
                            $subtotal_ex_tax        = $subtotal_ex_tax + $b['line_subtotal'];
                        }
                        $saved_cart_data[ $key ]    = $value_new;
                        $woocommerce_cart_hash      = $a;
                    }
                }
            
                if( $saved_cart ) {
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
        }
        
        function wcal_compare_only_guest_carts( $new_cart, $last_abandoned_cart ) {
            $current_woo_cart   = array();
            $current_woo_cart   = json_decode( stripslashes( $new_cart ), true );   
            $abandoned_cart_arr = array();
            $abandoned_cart_arr = json_decode( $last_abandoned_cart, true );
            $temp_variable      = "";
        
            if ( count( $current_woo_cart['cart'] ) >= count( $abandoned_cart_arr['cart'] ) ) {
                //do nothing
            } else {
                $temp_variable      = $current_woo_cart;
                $current_woo_cart   = $abandoned_cart_arr;
                $abandoned_cart_arr = $temp_variable;
            }
            if ( is_array( $current_woo_cart ) || is_object( $current_woo_cart ) ) {
                foreach( $current_woo_cart as $key => $value ) {
                    foreach( $value as $item_key => $item_value ) {
                        $current_cart_product_id   = $item_value['product_id'];
                        $current_cart_variation_id = $item_value['variation_id'];
                        $current_cart_quantity     = $item_value['quantity'];
        
                        if ( isset( $abandoned_cart_arr[$key][$item_key]['product_id'] ) ){
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
                        if ( ( $current_cart_product_id   != $abandoned_cart_product_id ) ||
                            ( $current_cart_variation_id != $abandoned_cart_variation_id ) ||
                            ( $current_cart_quantity     != $abandoned_cart_quantity ) ) {
                                return false;
                        }
                    }
                }
            }
            return true;
        }

        // Compare the existing cart with new cart
        function wcal_compare_carts( $user_id, $last_abandoned_cart ) { 
            global $woocommerce;
            
            $wcal_woocommerce_persistent_cart =version_compare( $woocommerce->version, '3.1.0', ">=" ) ? '_woocommerce_persistent_cart_' . get_current_blog_id() : '_woocommerce_persistent_cart' ;         
            $current_woo_cart   = get_user_meta( $user_id, $wcal_woocommerce_persistent_cart, true );
            $abandoned_cart_arr = json_decode( $last_abandoned_cart, true );
            $temp_variable      = "";       
            if ( count( $current_woo_cart['cart'] ) >= count( $abandoned_cart_arr['cart'] ) ) {
                //do nothing
            } else {
                $temp_variable      = $current_woo_cart;
                $current_woo_cart   = $abandoned_cart_arr;
                $abandoned_cart_arr = $temp_variable;
            }
            if ( is_array( $current_woo_cart ) && is_array( $abandoned_cart_arr ) ) {
                foreach ( $current_woo_cart as $key => $value ) {
                    
                    foreach ( $value as $item_key => $item_value ) {
                        $current_cart_product_id   = $item_value['product_id'];
                        $current_cart_variation_id = $item_value['variation_id'];
                        $current_cart_quantity     = $item_value['quantity'];
        
                        if ( isset( $abandoned_cart_arr[$key][$item_key]['product_id'] ) ) {
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
            }
            return true;
        }
    
        // function is call when order is recovered
        function wcal_action_after_delivery_session( $order ) {
            
            if( session_id() === '' ){
                //session has not started
                session_start();
            } 
            global $wpdb, $woocommerce;
            if( version_compare( $woocommerce->version, '3.0.0', ">=" ) ) {            
                $order_id                   = $order->get_id(); 
            }else{
                $order_id                   = $order->id; 
            }
            $get_abandoned_id_of_order  = '';
            $get_sent_email_id_of_order = '';
            $get_abandoned_id_of_order  = get_post_meta( $order_id, 'wcal_recover_order_placed', true );         
            if( isset( $get_abandoned_id_of_order ) && $get_abandoned_id_of_order != '' ){  
                $get_abandoned_id_of_order  = get_post_meta( $order_id, 'wcal_recover_order_placed', true );
                $get_sent_email_id_of_order = get_post_meta( $order_id, 'wcal_recover_order_placed_sent_id', true );
            
                $query_order = "UPDATE `" . $wpdb->prefix . "ac_abandoned_cart_history_lite` SET recovered_cart= '" . $order_id . "', cart_ignored = '1'
                                    WHERE id = '".$get_abandoned_id_of_order."' ";
                $wpdb->query( $query_order );
            
                $order->add_order_note( __( 'This order was abandoned & subsequently recovered.', 'woocommerce-ac' ) );
                 
                delete_post_meta( $order_id, 'wcal_recover_order_placed', $get_abandoned_id_of_order );
                delete_post_meta( $order_id , 'wcal_recover_order_placed_sent_id', $get_sent_email_id_of_order );
            }
            $user_id    = get_current_user_id();
            $sent_email = '';
            if( isset( $_SESSION['email_sent_id'] ) ){
                $sent_email = $_SESSION['email_sent_id'];
            }
            if( $user_id == "" ) {
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
                    get_user_meta( $user_id, '_woocommerce_ac_modified_cart', true ) == md5( "no" ) ) {
                         
                    if( version_compare( $woocommerce->version, '3.0.0', ">=" ) ) {            
                        $order_id                   = $order->get_id(); 
                    }else{
                        $order_id                   = $order->id; 
                    }
                    $updated_cart_ignored = 1;
                    $query_order = "UPDATE `".$wpdb->prefix."ac_abandoned_cart_history_lite`
                                    SET recovered_cart = %d,
                                        cart_ignored   = %s
                                    WHERE id = %d ";
                    $wpdb->query( $wpdb->prepare( $query_order, $order_id, $updated_cart_ignored, $results[0]->id ) );
                    delete_user_meta( $user_id, '_woocommerce_ac_modified_cart' );
                    delete_post_meta( $order_id, 'wcap_recovered_email_sent', 'yes' );                      
                } else { 
                    $delete_query = "DELETE FROM `".$wpdb->prefix."ac_abandoned_cart_history_lite`
                                     WHERE id= %d ";
                    $wpdb->query( $wpdb->prepare( $delete_query, $results[0]->id ) );
                }
            } else {
                if( version_compare( $woocommerce->version, '3.0.0', ">=" ) ) {            
                    
                    $email_id   = $order->get_billing_email();
                }else{
                    $email_id   = $order->billing_email;
                }
                $query      = "SELECT * FROM `".$wpdb->prefix."ac_guest_abandoned_cart_history_lite` WHERE email_id = %s";
                $results_id = $wpdb->get_results( $wpdb->prepare( $query, $email_id ) );
                
                if ( $results_id ) {
                    $record_status = "SELECT * FROM `".$wpdb->prefix."ac_abandoned_cart_history_lite` 
                                      WHERE user_id = %d AND recovered_cart = '0'";
                    $results_status = $wpdb->get_results( $wpdb->prepare( $record_status, $results_id[0]->id ) );
                        
                    if ( $results_status ) {                             
                        if ( get_user_meta( $results_id[0]->id, '_woocommerce_ac_modified_cart', true ) == md5("yes") ||
                                get_user_meta( $results_id[0]->id, '_woocommerce_ac_modified_cart', true ) == md5("no") ) {
                                
                            if( version_compare( $woocommerce->version, '3.0.0', ">=" ) ) {            
                                $order_id                   = $order->get_id(); 
                            }else{
                                $order_id                   = $order->id; 
                            }
                            $query_order = "UPDATE `".$wpdb->prefix."ac_abandoned_cart_history_lite` 
                                            SET recovered_cart= '".$order_id."', cart_ignored = '1' 
                                            WHERE id='".$results_status[0]->id."' ";
                            $wpdb->query( $query_order );
                            delete_user_meta( $results_id[0]->id, '_woocommerce_ac_modified_cart' );
                            delete_post_meta( $order_id, 'wcap_recovered_email_sent', 'yes' );
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
    
        function wcal_action_admin_init() {

            // only hook up these filters if we're in the admin panel and the current user has permission
            // to edit posts and pages
            if ( !isset( $_GET['page'] ) || $_GET['page'] != "woocommerce_ac_page" ) {
                return;
            }
            if ( !current_user_can( 'edit_posts' ) && !current_user_can( 'edit_pages' ) ) {
                return;
            }           
            if ( get_user_option( 'rich_editing' ) == 'true' ) {
                remove_filter( 'the_excerpt', 'wpautop' );
                add_filter( 'tiny_mce_before_init',  array( &$this, 'wcal_format_tiny_MCE' ) );
                add_filter( 'mce_buttons',          array( &$this, 'wcal_filter_mce_button' ) );
                add_filter( 'mce_external_plugins', array( &$this, 'wcal_filter_mce_plugin' ) );
            }
            if ( isset( $_GET['page'] ) && 'woocommerce_ac_page' == $_GET['page'] ) {
                if( session_id() === '' ){
                    //session has not started
                    session_start();
                }
            }
        }
        
        function wcal_filter_mce_button( $buttons ) {
            // add a separation before our button, here our button's id is &quot;mygallery_button&quot;
            array_push( $buttons, 'abandoncart', '|' );
            return $buttons;
        }
        
        function wcal_filter_mce_plugin( $plugins ) {
            // this plugin file will work the magic of our button
            $plugins['abandoncart'] = plugin_dir_url( __FILE__ ) . 'assets/js/abandoncart_plugin_button.js';
            return $plugins;
        }
        
        function wcal_display_tabs() {
        
            if ( isset( $_GET['action'] ) ) {
                $action = $_GET['action'];
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
            <div style="background-image: url('<?php echo plugins_url(); ?>/woocommerce-abandoned-cart/assets/images/ac_tab_icon.png') !important;" class="icon32"><br>
            </div>                      
            <h2 class="nav-tab-wrapper woo-nav-tab-wrapper">
                <a href="admin.php?page=woocommerce_ac_page&action=listcart" class="nav-tab <?php if (isset($active_listcart)) echo $active_listcart; ?>"> <?php _e( 'Abandoned Orders', 'woocommerce-ac' );?> </a>
                <a href="admin.php?page=woocommerce_ac_page&action=emailtemplates" class="nav-tab <?php if (isset($active_emailtemplates)) echo $active_emailtemplates; ?>"> <?php _e( 'Email Templates', 'woocommerce-ac' );?> </a>
                <a href="admin.php?page=woocommerce_ac_page&action=emailsettings" class="nav-tab <?php if (isset($active_settings)) echo $active_settings; ?>"> <?php _e( 'Settings', 'woocommerce-ac' );?> </a>
                <a href="admin.php?page=woocommerce_ac_page&action=stats" class="nav-tab <?php if (isset($active_stats)) echo $active_stats; ?>"> <?php _e( 'Recovered Orders', 'woocommerce-ac' );?> </a>
                <a href="admin.php?page=woocommerce_ac_page&action=report" class="nav-tab <?php if( isset( $active_report ) ) echo $active_report; ?>"> <?php _e( 'Product Report', 'woocommerce-ac' );?> </a>
            </h2>
            <?php
        }
        
        function wcal_enqueue_scripts_js( $hook ) {
            
            if ( $hook != 'woocommerce_page_woocommerce_ac_page' ) {
                return;
            } else {                
                wp_enqueue_script( 'jquery' );
                wp_enqueue_script( 
                                   'jquery-ui-min',
                                   '//ajax.googleapis.com/ajax/libs/jqueryui/1.11.4/jquery-ui.min.js',
                                   '',
                                   '',
                                   false
                );
                wp_enqueue_script( 'jquery-ui-datepicker' );
                
                wp_enqueue_script(
                                   'jquery-tip',
                                   plugins_url( '/assets/js/jquery.tipTip.minified.js', __FILE__ ),
                                   '',
                                   '',
                                   false
                );
                wp_register_script( 'woocommerce_admin', plugins_url() . '/woocommerce/assets/js/admin/woocommerce_admin.js', array( 'jquery', 'jquery-ui-widget', 'jquery-ui-core' ) );
                wp_enqueue_script( 'woocommerce_admin' );
                ?>
                <script type="text/javascript" >                
                    function wcal_activate_email_template( template_id, active_state ) {
                        location.href = 'admin.php?page=woocommerce_ac_page&action=emailtemplates&mode=activate_template&id='+template_id+'&active_state='+active_state ;
                    }
                </script>
                <?php 
                $js_src = includes_url('js/tinymce/') . 'tinymce.min.js'; 
                wp_enqueue_script( 'tinyMce_ac',$js_src );
                wp_enqueue_script( 'ac_email_variables', plugins_url() . '/woocommerce-abandoned-cart/assets/js/abandoncart_plugin_button.js' ); 
                wp_enqueue_script( 'wcal_activate_template', plugins_url() . '/woocommerce-abandoned-cart/assets/js/wcal_template_activate.js' ); 
            }
        }
    
        function wcal_format_tiny_MCE( $in ) {      
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
        
        function wcal_enqueue_scripts_css( $hook ) {
            if ( $hook != 'woocommerce_page_woocommerce_ac_page' ) {
                return;
            } else {
                wp_enqueue_style( 'jquery-ui', "//ajax.googleapis.com/ajax/libs/jqueryui/1.8/themes/base/jquery-ui.css" , '', '', false );                  
                wp_enqueue_style( 'woocommerce_admin_styles', plugins_url() . '/woocommerce/assets/css/admin.css' );
                wp_enqueue_style( 'jquery-ui-style', '//ajax.googleapis.com/ajax/libs/jqueryui/1.8.2/themes/smoothness/jquery-ui.css' );
                wp_enqueue_style( 'abandoned-orders-list', plugins_url() . '/woocommerce-abandoned-cart/assets/css/view.abandoned.orders.style.css' );
                wp_enqueue_style( 'wcal_email_template', plugins_url() . '/woocommerce-abandoned-cart/assets/css/wcal_template_activate.css' );
            
            }
        }       
        //bulk action
        // to over come the wp redirect warning while deleting
        function wcal_app_output_buffer() {
            ob_start();
        }
            
        /**
         * Abandon Cart Settings Page
         */
        function wcal_menu_page() {
            
            if ( is_user_logged_in() ) {
                global $wpdb;                   
                // Check the user capabilities
                if ( !current_user_can( 'manage_woocommerce' ) ) {    
                    wp_die( __( 'You do not have sufficient permissions to access this page.', 'woocommerce-ac' ) );
                }           
                ?>
                <div class="wrap">    
                    <h2><?php _e( 'WooCommerce - Abandon Cart Lite', 'woocommerce-ac' ); ?></h2>
                <?php 
                
                 if ( isset( $_GET['action'] ) ) {
                     $action = $_GET['action'];
                 } else {
                     $action = "";
                 }
                 if ( isset( $_GET['mode'] ) ) {
                     $mode = $_GET['mode'];
                 } else {
                     $mode = "";
                 }                                          
                 $this->wcal_display_tabs();
                 
                 /**
                  * When we delete the item from the below drop down it is registred in action 2
                  */
                 if ( isset( $_GET['action2'] ) ) {
                     $action_two = $_GET['action2'];
                 } else {
                     $action_two = "";
                 }
                 // Detect when a bulk action is being triggered on abandoned orders page.
                 if( 'wcal_delete' === $action || 'wcal_delete' === $action_two ) {
                     $ids    = isset( $_GET['abandoned_order_id'] ) ? $_GET['abandoned_order_id'] : false;
                     if ( ! is_array( $ids ) ) {
                         $ids = array( $ids );
                     }
                     foreach ( $ids as $id ) {
                         $class = new wcal_delete_bulk_action_handler();
                         $class->wcal_delete_bulk_action_handler_function( $id );
                     }
                 }
                 //Detect when a bulk action is being triggered on temnplates page.
                 if( 'wcal_delete_template' === $action || 'wcal_delete_template' === $action_two ) {
                     $ids    = isset( $_GET['template_id'] ) ? $_GET['template_id'] : false;
                     if ( ! is_array( $ids ) ) {
                         $ids = array( $ids );
                     }
                     foreach ( $ids as $id ) {
                         $class = new wcal_delete_bulk_action_handler();
                         $class->wcal_delete_template_bulk_action_handler_function( $id );
                     }
                 }
             
                if ( isset( $_GET['wcal_deleted'] ) && 'YES' == $_GET['wcal_deleted'] ) { ?>
                     <div id="message" class="updated fade">
                       <p><strong><?php _e( 'The Abandoned cart has been successfully deleted.', 'woocommerce-ac' ); ?></strong></p>
                     </div>
          <?php }    
                 if ( isset( $_GET ['wcal_template_deleted'] ) && 'YES' == $_GET['wcal_template_deleted'] ) { ?>
                    <div id="message" class="updated fade">
                        <p><strong><?php _e( 'The Template has been successfully deleted.', 'woocommerce-ac' ); ?></strong></p>
                    </div>
           <?php }            
                 if ( $action == 'emailsettings' ) {
                 // Save the field values
                    ?>
                    <p><?php _e( 'Change settings for sending email notifications to Customers, to Admin etc.', 'woocommerce-ac' ); ?></p>
                    <div id="content">
                    <?php 
                        $wcal_general_settings_class = $wcal_email_setting = "";
                        if ( isset( $_GET[ 'wcal_section' ] ) ) {
                            $section = $_GET[ 'wcal_section' ];
                        } else {
                            $section = '';
                        }                        
                        if ( $section == 'wcal_general_settings' || $section == '' ) {
                            $wcal_general_settings_class = "current";
                        }                        
                        if( $section == 'wcal_email_settings' ) {
                            $wcal_email_setting = "current";
                        }                        
                        
                        ?>
                        <ul class="subsubsub" id="wcal_general_settings_list">
                            <li>
                                <a href="admin.php?page=woocommerce_ac_page&action=emailsettings&wcal_section=wcal_general_settings" class="<?php echo $wcal_general_settings_class; ?>"><?php _e( 'General Settings', 'woocommerce-ac' );?> </a> |
                            </li>
                               <li>
                                <a href="admin.php?page=woocommerce_ac_page&action=emailsettings&wcal_section=wcal_email_settings" class="<?php echo $wcal_email_setting; ?>"><?php _e( 'Email Sending Settings', 'woocommerce-ac' );?> </a> 
                            </li>
                            
                        </ul>
                        <br class="clear">
                        <?php
                        if ( $section == 'wcal_general_settings' || $section == '' ) {
                        ?>
                            <form method="post" action="options.php">
                                <?php settings_fields( 'woocommerce_ac_settings' ); ?>
                                <?php do_settings_sections( 'woocommerce_ac_page' ); ?>
                                <?php settings_errors(); ?>
                                <?php submit_button(); ?>    
                            </form>
                        <?php 
                        } else if ( $section == 'wcal_email_settings' ) {
                        ?>
                            <form method="post" action="options.php">
                                <?php settings_fields     ( 'woocommerce_ac_email_settings' ); ?>
                                <?php do_settings_sections( 'woocommerce_ac_email_page' ); ?>
                                <?php settings_errors(); ?>
                                <?php submit_button(); ?>
                            </form>
                          <?php 
                        }
                        ?>
                    </div>
                  <?php 
                  } elseif ( $action == 'listcart' || '' == $action || '-1' == $action || '-1' == $action_two ) {
                  ?>    
                        <p> <?php _e( 'The list below shows all Abandoned Carts which have remained in cart for a time higher than the "Cart abandoned cut-off time" setting.', 'woocommerce-ac' );?> </p>
                        <?php
                        $get_all_abandoned_count      = wcal_common::wcal_get_abandoned_order_count( 'wcal_all_abandoned' );
                        $get_registered_user_ac_count = wcal_common::wcal_get_abandoned_order_count( 'wcal_all_registered' );
                        $get_guest_user_ac_count      = wcal_common::wcal_get_abandoned_order_count( 'wcal_all_guest' );
                        $get_visitor_user_ac_count    = wcal_common::wcal_get_abandoned_order_count( 'wcal_all_visitor' );                      
                        
                        $wcal_user_reg_text = 'User';
                        if ( $get_registered_user_ac_count > 1 ) {
                            $wcal_user_reg_text = 'Users';
                        }                    
                        $wcal_user_gus_text = 'User';
                        if ( $get_guest_user_ac_count > 1 ) {
                            $wcal_user_gus_text = 'Users';
                        }                                                                    
                        $wcal_all_abandoned_carts  = $section = $wcal_all_registered = $wcal_all_guest = $wcal_all_visitor = "" ;
                        
                        if ( isset( $_GET[ 'wcal_section' ] ) ) {
                            $section = $_GET[ 'wcal_section' ];
                        } else {
                            $section = '';
                        }
                        if ( $section == 'wcal_all_abandoned' || $section == '' ) {
                            $wcal_all_abandoned_carts = "current";
                        }
                        
                        if( $section == 'wcal_all_registered' ) {
                            $wcal_all_registered = "current";
                            $wcal_all_abandoned_carts = "";
                        }
                        if( $section == 'wcal_all_guest' ) {
                            $wcal_all_guest = "current";
                            $wcal_all_abandoned_carts = "";
                        }
                        
                        if( $section == 'wcal_all_visitor' ) {
                            $wcal_all_visitor = "current";
                            $wcal_all_abandoned_carts = "";
                        }
                        ?>
                        <ul class="subsubsub" id="wcal_recovered_orders_list">
                            <li>
                                <a href="admin.php?page=woocommerce_ac_page&action=listcart&wcal_section=wcal_all_abandoned" class="<?php echo $wcal_all_abandoned_carts; ?>"><?php _e( "All ", 'woocommerce-ac' ) ;?> <span class = "count" > <?php echo "( $get_all_abandoned_count )" ?> </span></a> 
                            </li>
    
                            <?php if ($get_registered_user_ac_count > 0 ) { ?>
                            <li>
                                | <a href="admin.php?page=woocommerce_ac_page&action=listcart&wcal_section=wcal_all_registered" class="<?php echo $wcal_all_registered; ?>"><?php _e( " Registered $wcal_user_reg_text ", 'woocommerce-ac' ) ;?> <span class = "count" > <?php echo "( $get_registered_user_ac_count )" ?> </span></a> 
                            </li>
                            <?php } ?>
    
                            <?php if ($get_guest_user_ac_count > 0 ) { ?>
                            <li>
                                | <a href="admin.php?page=woocommerce_ac_page&action=listcart&wcal_section=wcal_all_guest" class="<?php echo $wcal_all_guest; ?>"><?php _e( " Guest $wcal_user_gus_text ", 'woocommerce-ac' ) ;?> <span class = "count" > <?php echo "( $get_guest_user_ac_count )" ?> </span></a> 
                            </li>
                            <?php } ?>
    
                            <?php if ($get_visitor_user_ac_count > 0 ) { ?>
                            <li>
                                | <a href="admin.php?page=woocommerce_ac_page&action=listcart&wcal_section=wcal_all_visitor" class="<?php echo $wcal_all_visitor; ?>"><?php _e( " Carts without Customer Details ", 'woocommerce-ac' ) ;?> <span class = "count" > <?php echo "( $get_visitor_user_ac_count )" ?> </span></a> 
                            </li>
                            <?php } ?>
                        </ul>
                        
                        <?php 
                        global $wpdb;
                        include_once( 'includes/classes/class-wcal-abandoned-orders-table.php' );
                        $wcal_abandoned_order_list = new WCAL_Abandoned_Orders_Table();
                        $wcal_abandoned_order_list->wcal_abandoned_order_prepare_items();
                        ?>
                        <div class="wrap">
                            <form id="wcal-abandoned-orders" method="get" >
                                <input type="hidden" name="page" value="woocommerce_ac_page" />
                                 <input type="hidden" name="action" value="listcart" />
                                <?php $wcal_abandoned_order_list->display(); ?>
                            </form>
                        </div>                        
                        <?php 
                  } elseif ( $action == 'emailtemplates' && ( $mode != 'edittemplate' && $mode != 'addnewtemplate' ) ) {
                        ?>                                                  
                        <p> <?php _e( 'Add email templates at different intervals to maximize the possibility of recovering your abandoned carts.', 'woocommerce-ac' );?> </p>
                        <?php                       
                        // Save the field values
                        $insert_template_successfuly = $update_template_successfuly = ''; 
                        if( isset( $_POST['ac_settings_frm'] ) && $_POST['ac_settings_frm'] == 'save' ) {                                                                  
                            $woocommerce_ac_email_subject = trim( $_POST['woocommerce_ac_email_subject'] );
                            $woocommerce_ac_email_body    = trim( $_POST['woocommerce_ac_email_body'] );
                            $woocommerce_ac_template_name = trim( $_POST['woocommerce_ac_template_name'] );
                            $woocommerce_ac_email_header  = trim( $_POST['wcal_wc_email_header'] );
                           
                            $email_frequency              = trim( $_POST['email_frequency'] );
                            $day_or_hour                  = trim( $_POST['day_or_hour'] );
                            $is_wc_template               = ( empty( $_POST['is_wc_template'] ) ) ? '0' : '1';
                            $default_value                =  0 ;
                            
                            $query = "INSERT INTO `".$wpdb->prefix."ac_email_templates_lite`
                                      (subject, body, frequency, day_or_hour, template_name, is_wc_template, default_template, wc_email_header )
                                      VALUES ( %s, %s, %d, %s, %s, %s, %d, %s )";
                            
                            $insert_template_successfuly = $wpdb->query( $wpdb->prepare( $query,
                                                            $woocommerce_ac_email_subject,
                                                            $woocommerce_ac_email_body,
                                                            $email_frequency,
                                                            $day_or_hour,
                                                            $woocommerce_ac_template_name,
                                                            $is_wc_template,
                                                            $default_value,
                                                            $woocommerce_ac_email_header )        
                            );                                 
                        }
                        
                        if( isset( $_POST['ac_settings_frm'] ) && $_POST['ac_settings_frm'] == 'update' ) { 
                             
                            $updated_is_active            = '0';
                            
                            $email_frequency              = trim( $_POST['email_frequency'] );
                            $day_or_hour                  = trim( $_POST['day_or_hour'] );
                            $is_wc_template               = ( empty( $_POST['is_wc_template'] ) ) ? '0' : '1';
                            
                            $woocommerce_ac_email_subject = trim( $_POST['woocommerce_ac_email_subject'] );
                            $woocommerce_ac_email_body    = trim( $_POST['woocommerce_ac_email_body'] );
                            $woocommerce_ac_template_name = trim( $_POST['woocommerce_ac_template_name'] );
                            $woocommerce_ac_email_header  = trim( $_POST['wcal_wc_email_header'] );
                            $id                           = trim( $_POST['id'] );
                            
                            $check_query = "SELECT * FROM `".$wpdb->prefix."ac_email_templates_lite`
                                            WHERE id = %d ";
                            $check_results = $wpdb->get_results( $wpdb->prepare( $check_query, $id ) );
                            $default_value = '';

                            if ( count( $check_results ) > 0 ) { 
                                if ( isset( $check_results[0]->default_template ) && $check_results[0]->default_template == '1' ) {
                                    $default_value = '1';
                                }
                            }
                            
                            $query_update_latest = "UPDATE `".$wpdb->prefix."ac_email_templates_lite`
                                                    SET
                                                    subject       = %s,
                                                    body          = %s,
                                                    frequency     = %d,
                                                    day_or_hour   = %s,
                                                    template_name = %s,
                                                    is_wc_template = %s,
                                                    default_template = %d,
                                                    wc_email_header = %s
                                                    WHERE id      = %d ";
                                
                            $update_template_successfuly = $wpdb->query( $wpdb->prepare( $query_update_latest,
                                                            $woocommerce_ac_email_subject,
                                                            $woocommerce_ac_email_body,
                                                            $email_frequency,
                                                            $day_or_hour,
                                                            $woocommerce_ac_template_name,
                                                            $is_wc_template,
                                                            $default_value,
                                                            $woocommerce_ac_email_header,
                                                            $id )
                            );   
                            
                        }
                        
                        if ( $action == 'emailtemplates' && $mode == 'removetemplate' ) {
                            $id_remove = $_GET['id'];
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

                                $query_update                 = "SELECT * FROM `".$wpdb->prefix."ac_email_templates_lite` WHERE id ='" . $template_id . "'";
                                $get_selected_template_result = $wpdb->get_results( $query_update );
                                $email_frequncy                = $get_selected_template_result[0]->frequency;
                                $email_day_or_hour             = $get_selected_template_result[0]->day_or_hour;
                                
                                $query_update = "UPDATE `".$wpdb->prefix."ac_email_templates_lite` SET is_active='0' WHERE frequency='" . $email_frequncy . "' AND day_or_hour='" . $email_day_or_hour . "' ";
                                $wcap_updated = $wpdb->query( $query_update );
                            }
                            $query_update = "UPDATE `" . $wpdb->prefix . "ac_email_templates_lite`
                                    SET
                                    is_active       = '" . $active . "'
                                    WHERE id        = '" . $template_id . "' ";
                            $wpdb->query( $query_update );
                        
                            wp_safe_redirect( admin_url( '/admin.php?page=woocommerce_ac_page&action=emailtemplates' ) );
                        }
                        
                        if( isset( $_POST['ac_settings_frm'] ) && $_POST['ac_settings_frm'] == 'save' && ( isset( $insert_template_successfuly ) && $insert_template_successfuly != '' ) ) { ?>
                            <div id="message" class="updated fade">
                                <p>
                                    <strong>
                                        <?php _e( 'The Email Template has been successfully added.', 'woocommerce-ac' ); ?>
                                    </strong>
                                </p>
                            </div>
                            <?php } else if ( isset( $_POST['ac_settings_frm'] ) && $_POST['ac_settings_frm'] == 'save' && ( isset( $insert_template_successfuly ) && $insert_template_successfuly == '' ) ) {
                                ?>
                                <div id="message" class="error fade">
                                    <p>
                                        <strong>
                                            <?php _e( ' There was a problem adding the email template. Please contact the plugin author via <a href= "https://wordpress.org/support/plugin/woocommerce-abandoned-cart">support forum</a>.', 'woocommerce-ac' ); ?>
                                        </strong>
                                    </p>
                                </div>
                             <?php   
                        }

                        if ( isset( $_POST['ac_settings_frm'] ) && $_POST['ac_settings_frm'] == 'update'  && isset($update_template_successfuly) && $update_template_successfuly >= 0 ) { ?>
                                <div id="message" class="updated fade">
                                    <p>
                                        <strong>
                                            <?php _e( 'The Email Template has been successfully updated.', 'woocommerce-ac' ); ?>
                                        </strong>
                                    </p>
                                </div>
                            <?php } else if ( isset( $_POST['ac_settings_frm'] ) && $_POST['ac_settings_frm'] == 'update'  && isset($update_template_successfuly) && $update_template_successfuly === false ){
                                ?>
                                    <div id="message" class="error fade">
                                        <p>
                                            <strong>
                                                <?php _e( ' There was a problem updating the email template. Please contact the plugin author via <a href= "https://wordpress.org/support/plugin/woocommerce-abandoned-cart">support forum</a>.', 'woocommerce-ac' ); ?>
                                            </strong>
                                        </p>
                                    </div>
                            <?php   
                        }
                        ?>
                        <div class="tablenav">
                            <p style="float:left;">
                               <a cursor: pointer; href="<?php echo "admin.php?page=woocommerce_ac_page&action=emailtemplates&mode=addnewtemplate"; ?>" class="button-secondary"><?php _e( 'Add New Template', 'woocommerce-ac' ); ?>
                               </a>                           
                            </p>
                    
                            <?php
                            /* From here you can do whatever you want with the data from the $result link. */
                            include_once('includes/classes/class-wcal-templates-table.php');
                            $wcal_template_list = new WCAL_Templates_Table();
                            $wcal_template_list->wcal_templates_prepare_items();
                            ?>
                            <div class="wrap">
                                <form id="wcal-abandoned-templates" method="get" >
                                    <input type="hidden" name="page" value="woocommerce_ac_page" />
                                    <input type="hidden" name="action" value="emailtemplates" />
                                    <?php $wcal_template_list->display(); ?>
                                </form>
                            </div>
                        </div>
                        <?php 
                   } elseif ($action == 'stats' || $action == '') {                     
                        ?>
                        <p>
                        <script language='javascript'>
                            jQuery( document ).ready( function() {
                                jQuery( '#duration_select' ).change( function() {
                                    var group_name = jQuery( '#duration_select' ).val();
                                    var today      = new Date();
                                    var start_date = "";
                                    var end_date   = "";
                                    if ( group_name == "yesterday" ) {
                                        start_date = new Date( today.getFullYear(), today.getMonth(), today.getDate() - 1 );
                                        end_date   = new Date( today.getFullYear(), today.getMonth(), today.getDate() - 1 );
                                    } else if ( group_name == "today") {
                                        start_date = new Date( today.getFullYear(), today.getMonth(), today.getDate() );
                                        end_date   = new Date( today.getFullYear(), today.getMonth(), today.getDate() );
                                    } else if ( group_name == "last_seven" ) {
                                        start_date = new Date( today.getFullYear(), today.getMonth(), today.getDate() - 7 );
                                        end_date   = new Date( today.getFullYear(), today.getMonth(), today.getDate() );
                                    } else if ( group_name == "last_fifteen" ) {
                                        start_date = new Date( today.getFullYear(), today.getMonth(), today.getDate() - 15 );
                                        end_date   = new Date( today.getFullYear(), today.getMonth(), today.getDate() );
                                    } else if ( group_name == "last_thirty" ) {
                                        start_date = new Date( today.getFullYear(), today.getMonth(), today.getDate() - 30 );
                                        end_date   = new Date( today.getFullYear(), today.getMonth(), today.getDate() );
                                    } else if ( group_name == "last_ninety" ) {
                                        start_date = new Date( today.getFullYear(), today.getMonth(), today.getDate() - 90 );
                                        end_date   = new Date( today.getFullYear(), today.getMonth(), today.getDate() );
                                    } else if ( group_name == "last_year_days" ) {
                                        start_date = new Date( today.getFullYear(), today.getMonth(), today.getDate() - 365 );
                                        end_date   = new Date( today.getFullYear(), today.getMonth(), today.getDate() );
                                    }
            
                                    var monthNames = ["Jan", "Feb", "Mar", "Apr", "May", "Jun",
                                                       "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
                                                   
                                    var start_date_value = start_date.getDate() + " " + monthNames[start_date.getMonth()] + " " + start_date.getFullYear();
                                    var end_date_value   = end_date.getDate() + " " + monthNames[end_date.getMonth()] + " " + end_date.getFullYear();
            
                                    jQuery( '#start_date' ).val( start_date_value );
                                    jQuery( '#end_date' ).val( end_date_value );
                               } );
                            });
                        </script>
                        <?php
                        
                        if ( isset( $_POST['duration_select'] ) ){
                               $duration_range = $_POST['duration_select'];
                        } else {
                               $duration_range = "";
                        }       
                        if ( $duration_range == "" ) {
                            if ( isset( $_GET['duration_select'] ) ){
                                $duration_range = $_GET['duration_select'];
                            }    
                        }
                        if ($duration_range == "") $duration_range = "last_seven";
                        
                            _e( 'The Report below shows how many Abandoned Carts we were able to recover for you by sending automatic emails to     encourage shoppers.', 'woocommerce-ac');
                        ?>
                        <div id="recovered_stats" class="postbox" style="display:block">
                            <div class="inside">
                                <form method="post" action="admin.php?page=woocommerce_ac_page&action=stats" id="ac_stats">
                                    <select id="duration_select" name="duration_select" >
                                        <?php
                                        foreach ( $this->duration_range_select as $key => $value ) {
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
                                            var formats = ["d.m.y", "d M yy","MM d, yy"];
                                            jQuery( "#start_date" ).datepicker( { dateFormat: formats[1] } );
                                        });
                        
                                        jQuery( document ).ready( function()
                                        {
                                            var formats = ["d.m.y", "d M yy","MM d, yy"];
                                            jQuery( "#end_date" ).datepicker( { dateFormat: formats[1] } );
                                        });
                                    </script>                                                       
                                    <?php 
                                    include_once('includes/classes/class-wcal-recover-orders-table.php');                  
                                    $wcal_recover_orders_list = new WCAL_Recover_Orders_Table();
                                    $wcal_recover_orders_list->wcal_recovered_orders_prepare_items();
                                    
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
                                        <?php $count = $wcal_recover_orders_list->total_abandoned_cart_count; 
                                              echo $count; ?> 
                                    </strong>
                                    <?php _e( 'carts totaling', 'woocommerce-ac' ); ?> 
                                    <strong> 
                                        <?php $total_of_all_order = $wcal_recover_orders_list->total_order_amount; 
                                               
                                        echo $total_of_all_order; ?>
                                     </strong>
                                     <?php _e( ' were abandoned. We were able to recover', 'woocommerce-ac' ); ?> 
                                     <strong>
                                        <?php 
                                        $recovered_item = $wcal_recover_orders_list->recovered_item;
                                        
                                        echo $recovered_item; ?>
                                     </strong>
                                     <?php _e( ' of them, which led to an extra', 'woocommerce-ac' ); ?> 
                                     <strong>
                                        <?php 
                                            $recovered_total = $wcal_recover_orders_list->total_recover_amount;
                                            echo wc_price( $recovered_total ); ?>
                                     </strong>
                                 </p>
                            </div>
                        </div>
                        <div class="wrap">
                            <form id="wcal-recover-orders" method="get" >
                                <input type="hidden" name="page" value="woocommerce_ac_page" />
                                <input type="hidden" name="action" value="stats" />
                                <?php $wcal_recover_orders_list->display(); ?>
                            </form>
                        </div>
                        <?php
                   } elseif ( $action == 'orderdetails' ) {
                        global $woocommerce;
                        $ac_order_id = $_GET['id'];
                        ?>
                        <p> </p>
                        <div id="ac_order_details" class="postbox" style="display:block">
                            <h3> <p> <?php _e( "Abandoned Order #$ac_order_id Details", "woocommerce-ac" ); ?> </p> </h3>
                            <div class="inside">
                                <table cellpadding="0" cellspacing="0" class="wp-list-table widefat fixed posts">
                                    <tr>
                                        <th> <?php _e( 'Item', 'woocommerce-ac' ); ?> </th>
                                        <th> <?php _e( 'Name', 'woocommerce-ac' ); ?> </th>
                                        <th> <?php _e( 'Quantity', 'woocommerce-ac' ); ?> </th>
                                        <th> <?php _e( 'Line Subtotal', 'woocommerce-ac' ); ?> </th>
                                        <th> <?php _e( 'Line Total', 'woocommerce-ac' ); ?> </th>
                                    </tr>                                           
                                    <?php 
                                    $query = "SELECT * FROM `".$wpdb->prefix."ac_abandoned_cart_history_lite` WHERE id = %d ";
                                    $results = $wpdb->get_results( $wpdb->prepare( $query,$_GET['id'] ) );                         
                                    
                                    $shipping_charges = 0;
                                    $currency_symbol  = get_woocommerce_currency_symbol();
                                    $number_decimal   = wc_get_price_decimals();                                    
                                    if ( $results[0]->user_type == "GUEST" && "0" != $results[0]->user_id ) {
                                        $query_guest            = "SELECT * FROM `".$wpdb->prefix."ac_guest_abandoned_cart_history_lite` WHERE id = %d";  
                                        $results_guest          = $wpdb->get_results( $wpdb->prepare( $query_guest, $results[0]->user_id ) );
                                        $user_email = $user_first_name = $user_last_name = $user_billing_postcode = $user_shipping_postcode = '';
                                        $shipping_charges = '';
                                        if ( count( $results_guest ) > 0 ) {
                                            $user_email             = $results_guest[0]->email_id;
                                            $user_first_name        = $results_guest[0]->billing_first_name;
                                            $user_last_name         = $results_guest[0]->billing_last_name;
                                            $user_billing_postcode  = $results_guest[0]->billing_zipcode;
                                            $user_shipping_postcode = $results_guest[0]->shipping_zipcode;
                                            $shipping_charges       = $results_guest[0]->shipping_charges;
                                        }
                                        $user_billing_company   = $user_billing_address_1 = $user_billing_address_2 = $user_billing_city = $user_billing_state = $user_billing_country  = $user_billing_phone = "";
                                        $user_shipping_company  = $user_shipping_address_1 = $user_shipping_address_2 = $user_shipping_city = $user_shipping_state = $user_shipping_country = "";  
                                    } else if ( $results[0]->user_type == "GUEST" && $results[0]->user_id == "0" ) {
                                        $user_email             = '';
                                        $user_first_name        = "Visitor";
                                        $user_last_name         = "";
                                        $user_billing_postcode  = '';
                                        $user_shipping_postcode = '';
                                        $shipping_charges       = '';
                                        $user_billing_phone     = '';
                                        $user_billing_company   = $user_billing_address_1 = $user_billing_address_2 = $user_billing_city = $user_billing_state = $user_billing_country  = "";
                                        $user_shipping_company  = $user_shipping_address_1 = $user_shipping_address_2 = $user_shipping_city = $user_shipping_state = $user_shipping_country = "";                                       
                                    } else {
                                        $user_id                = $results[0]->user_id;                                
                                        if ( isset( $results[0]->user_login ) ) {
                                            $user_login         = $results[0]->user_login;
                                        }
                                        $user_email             = get_user_meta( $results[0]->user_id, 'billing_email', true );                                        
                                        if( "" == $user_email ) {
                                            $user_data          = get_userdata( $results[0]->user_id );
                                            if ( isset( $user_data->user_email ) ) {
                                                $user_email         = $user_data->user_email;
                                            } else {
                                                $user_email = '';
                                            }
                                        }
                                        
                                        $user_first_name        = "";
                                        $user_first_name_temp   = get_user_meta( $user_id, 'billing_first_name', true );
                                        if( isset( $user_first_name_temp ) && "" == $user_first_name_temp ) {
                                            $user_data          = get_userdata( $user_id );
                                            if ( isset( $user_data->first_name ) ) {
                                                $user_first_name    = $user_data->first_name;
                                            } else {
                                                $user_first_name = '';
                                            }
                                        } else {
                                            $user_first_name    = $user_first_name_temp;
                                        }                                        
                                        $user_last_name         = "";
                                        $user_last_name_temp    = get_user_meta( $user_id, 'billing_last_name', true );
                                        if( isset( $user_last_name_temp ) && "" == $user_last_name_temp ) {
                                            $user_data          = get_userdata( $user_id );
                                            if ( isset( $user_data->last_name ) ) {
                                                $user_last_name = $user_data->last_name;
                                            } else {
                                                $user_last_name = '';
                                            }
                                        } else {
                                            $user_last_name     = $user_last_name_temp;
                                        }                                        
                                        $user_billing_first_name = get_user_meta( $results[0]->user_id, 'billing_first_name' );
                                        $user_billing_last_name  = get_user_meta( $results[0]->user_id, 'billing_last_name' );                                        
                                        $user_billing_company_temp = get_user_meta( $results[0]->user_id, 'billing_company' );
                                        if ( isset( $user_billing_company_temp[0] ) ) {
                                            $user_billing_company = $user_billing_company_temp[0];
                                        } else {
                                            $user_billing_company = "";
                                        }                                  
                                        $user_billing_address_1_temp = get_user_meta( $results[0]->user_id, 'billing_address_1' );
                                        if ( isset( $user_billing_address_1_temp[0] ) ) {
                                            $user_billing_address_1  = $user_billing_address_1_temp[0];
                                        } else {
                                            $user_billing_address_1  = "";
                                        }                                        
                                        $user_billing_address_2_temp = get_user_meta( $results[0]->user_id, 'billing_address_2' );
                                        if ( isset( $user_billing_address_2_temp[0] ) ) {
                                            $user_billing_address_2 = $user_billing_address_2_temp[0];
                                        } else {
                                            $user_billing_address_2 = "";
                                        }                                        
                                        $user_billing_city_temp = get_user_meta( $results[0]->user_id, 'billing_city' );
                                        if ( isset( $user_billing_city_temp[0] ) ) {
                                            $user_billing_city = $user_billing_city_temp[0];
                                        } else {
                                            $user_billing_city = "";
                                        }                                        
                                        $user_billing_postcode_temp = get_user_meta( $results[0]->user_id, 'billing_postcode' );
                                        if ( isset( $user_billing_postcode_temp[0] ) ) {
                                            $user_billing_postcode = $user_billing_postcode_temp[0];
                                        } else {
                                            $user_billing_postcode = "";
                                        }                                        
                                        $user_billing_state_temp = get_user_meta( $results[0]->user_id, 'billing_state' );
                                        if ( isset( $user_billing_state_temp[0] ) ) {
                                            $user_billing_state = $user_billing_state_temp[0];
                                        } else {
                                            $user_billing_state = "";
                                        }                                        
                                        $user_billing_country_temp = get_user_meta( $results[0]->user_id, 'billing_country' );
                                        if ( isset( $user_billing_country_temp[0] ) ) {
                                            $user_billing_country = $user_billing_country_temp[0];
                                        } else {
                                            $user_billing_country = "";
                                        }                                        
                                        $user_billing_phone_temp = get_user_meta( $results[0]->user_id, 'billing_phone' );
                                        if ( isset( $user_billing_phone_temp[0] ) ) {
                                            $user_billing_phone = $user_billing_phone_temp[0];
                                        } else {
                                            $user_billing_phone = "";
                                        }                                        
                                        $user_shipping_first_name   = get_user_meta( $results[0]->user_id, 'shipping_first_name' );
                                        $user_shipping_last_name    = get_user_meta( $results[0]->user_id, 'shipping_last_name' );                                        
                                        $user_shipping_company_temp = get_user_meta( $results[0]->user_id, 'shipping_company' );                                        
                                        if ( isset( $user_shipping_company_temp[0] ) ) {
                                            $user_shipping_company  = $user_shipping_company_temp[0];
                                        } else {
                                            $user_shipping_company  = "";
                                        }                                        
                                        $user_shipping_address_1_temp = get_user_meta( $results[0]->user_id, 'shipping_address_1' );
                                        if ( isset( $user_shipping_address_1_temp[0] ) ) {
                                            $user_shipping_address_1 = $user_shipping_address_1_temp[0];
                                        } else {
                                            $user_shipping_address_1 = "";
                                        }                                        
                                        $user_shipping_address_2_temp = get_user_meta( $results[0]->user_id, 'shipping_address_2' );
                                        if ( isset( $user_shipping_address_2_temp[0] ) ) {
                                            $user_shipping_address_2 = $user_shipping_address_2_temp[0];
                                        } else {
                                            $user_shipping_address_2 = "";
                                        }                                        
                                        $user_shipping_city_temp = get_user_meta( $results[0]->user_id, 'shipping_city' );
                                        if ( isset( $user_shipping_city_temp[0] ) ) {
                                            $user_shipping_city = $user_shipping_city_temp[0];
                                        } else {
                                            $user_shipping_city = "";
                                        }                                        
                                        $user_shipping_postcode_temp = get_user_meta( $results[0]->user_id, 'shipping_postcode' );
                                        if ( isset( $user_shipping_postcode_temp[0] ) ) {
                                            $user_shipping_postcode = $user_shipping_postcode_temp[0];
                                        } else {
                                            $user_shipping_postcode = "";
                                        }                                        
                                        $user_shipping_state_temp = get_user_meta( $results[0]->user_id, 'shipping_state' );
                                        if ( isset( $user_shipping_state_temp[0] ) ) {
                                            $user_shipping_state = $user_shipping_state_temp[0];
                                        } else {
                                            $user_shipping_state = "";
                                        }                                        
                                        $user_shipping_country_temp = get_user_meta( $results[0]->user_id, 'shipping_country' );
                                        if ( isset( $user_shipping_country_temp[0] ) ) {
                                            $user_shipping_country = $user_shipping_country_temp[0];
                                        } else {
                                            $user_shipping_country = "";
                                        }
                                    } 
                                    $cart_details   = array();
                                    $cart_info      = json_decode( $results[0]->abandoned_cart_info );
                                    $cart_details   = (array) $cart_info->cart;
                                    $item_subtotal  = $item_total = 0;
                                    
                                    if ( is_array ( $cart_details ) && count( $cart_details ) > 0 ) {                                                                              
                                        foreach ( $cart_details as $k => $v ) {
                                            $quantity_total = $v->quantity;
                                            $product_id     = $v->product_id;
                                            $prod_name      = get_post($product_id);
                                            $product_name   = $prod_name->post_title;                                            
                                            if ( isset( $v->variation_id ) && '' != $v->variation_id ){
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
                                            // Item subtotal is calculated as product total including taxes
                                            if ( $v->line_subtotal_tax != 0 && $v->line_subtotal_tax > 0 ) {
                                                $item_subtotal = $item_subtotal + $v->line_total + $v->line_subtotal_tax;
                                            } else {
                                                $item_subtotal = $item_subtotal + $v->line_total;
                                            }            
                                            //  Line total
                                            $item_total    = $item_subtotal;
                                            $item_subtotal = $item_subtotal / $quantity_total;
                                            $item_total    = wc_price( $item_total );
                                            $item_subtotal = wc_price( $item_subtotal );                               
                                            $product       = wc_get_product( $product_id );
                                            $prod_image    = $product->get_image();
                                        ?>                   
                                        <tr>
                                            <td> <?php echo $prod_image; ?></td>
                                            <td> <?php echo $product_name; ?></td>
                                            <td> <?php echo $quantity_total; ?></td>
                                            <td> <?php echo $item_subtotal; ?></td>
                                            <td> <?php echo $item_total; ?></td>
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
                                    <div style="width:50%;float:left">
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
                                    <div style="width:50%;float:right">
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
                        include_once('includes/classes/class-wcal-product-report-table.php');
                        $wcal_product_report_list = new WCAL_Product_Report_Table();
                        $wcal_product_report_list->wcal_product_report_prepare_items(); 
                        ?>
                        <div class="wrap">
                            <form id="wcal-sent-emails" method="get" >
                                <input type="hidden" name="page" value="woocommerce_ac_page" />
                                <input type="hidden" name="action" value="report" />
                                <?php $wcal_product_report_list->display(); ?>
                            </form>
                        </div>                           
            <?php }
            }
            echo( "</table>" );
                        
            if ( isset( $_GET['action'] ) ) {
                   $action = $_GET['action'];
            }       
            if ( isset( $_GET['mode'] ) ){
                   $mode = $_GET['mode'];
            }
            if ( $action == 'emailtemplates' && ( $mode == 'addnewtemplate' || $mode == 'edittemplate' ) ) {                
                if ( $mode=='edittemplate' ) {
                    $results = array();
                    if ( isset( $_GET['id'] ) ) { 
                        $edit_id = $_GET['id'];

                        $query   = "SELECT wpet . *  FROM `".$wpdb->prefix."ac_email_templates_lite` AS wpet WHERE id = %d ";
                        $results = $wpdb->get_results( $wpdb->prepare( $query, $edit_id ) );
                    }
                }
                $active_post = ( empty( $_POST['is_active'] ) ) ? '0' : '1';    
                ?>
                <div id="content">
                  <form method="post" action="admin.php?page=woocommerce_ac_page&action=emailtemplates" id="ac_settings">
                    <input type="hidden" name="mode" value="<?php echo $mode;?>" />
                        <?php
                        $id_by = "";
                        if ( isset( $_GET['id'] ) ) {
                            $id_by = $_GET['id'];
                        }       
                        ?>                              
                        <input type="hidden" name="id" value="<?php echo $id_by ;?>" />
                        <?php
                        $button_mode     = "save";
                        $display_message = "Add Email Template";
                        if ( $mode == 'edittemplate' ) {
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
                                            if( $mode == 'edittemplate' && count( $results ) > 0 && isset( $results[0]->template_name ) ) {
                                                $template_name = $results[0]->template_name;
                                            }                                           
                                            print'<input type="text" name="woocommerce_ac_template_name" id="woocommerce_ac_template_name" class="regular-text" value="'.$template_name.'">';?>
                                            <img class="help_tip" width="16" height="16" data-tip='<?php _e('Enter a template name for reference', 'woocommerce-ac') ?>' src="<?php echo plugins_url(); ?>/woocommerce/assets/images/help.png" />
                                        </td>
                                    </tr>
                                    
                                    <tr>
                                       <th>
                                            <label for="woocommerce_ac_email_subject"><b><?php _e( 'Subject:', 'woocommerce-ac' ); ?></b></label>
                                        </th>
                                        <td>
                                            <?php
                                            $subject_edit = "";
                                            if ( $mode == 'edittemplate' && count( $results ) > 0 && isset( $results[0]->subject ) ) {
                                                $subject_edit= stripslashes ( $results[0]->subject );
                                            }                                           
                                            print'<input type="text" name="woocommerce_ac_email_subject" id="woocommerce_ac_email_subject" class="regular-text" value="'.$subject_edit.'">';?>
                                            <img class="help_tip" width="16" height="16" data-tip='<?php _e('Enter the subject that should appear in the email sent', 'woocommerce-ac') ?>' src="<?php echo plugins_url(); ?>/woocommerce/assets/images/help.png" />
                                        </td>
                                    </tr>

                                    <tr>
                                        <th>
                                            <label for="woocommerce_ac_email_body"><b><?php _e( 'Email Body:', 'woocommerce-ac' ); ?></b></label>
                                        </th>
                                        <td>            
                                            <?php
                                            $initial_data = "";
                                            if ( $mode == 'edittemplate' && count( $results ) > 0 && isset( $results[0]->body ) ) {
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
                                            <?php echo stripslashes( get_option( 'woocommerce_ac_email_body' ) ); ?>
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
                                            $is_wc_template = "";                                        
                                            if ( $mode == 'edittemplate' && count( $results ) > 0 && isset( $results[0]->is_wc_template ) ) {
                                                $use_wc_template = $results[0]->is_wc_template;
                                                
                                                if ( $use_wc_template == '1' ) {
                                                    $is_wc_template = "checked";
                                                } else {
                                                    $is_wc_template = "";
                                                }
                                            }
                                            print'<input type="checkbox" name="is_wc_template" id="is_wc_template" ' . $is_wc_template . '>  </input>'; ?>
                                            <img class="help_tip" width="16" height="16" data-tip='<?php _e( 'Use WooCommerce default style template for abandoned cart reminder emails.', 'woocommerce' ) ?>' src="<?php echo plugins_url(); ?>/woocommerce/assets/images/help.png" /> <a target = '_blank' href= <?php  echo wp_nonce_url( admin_url( '?wcal_preview_woocommerce_mail=true' ), 'woocommerce-ac' ) ; ?> > 
                                            Click here to preview </a>how the email template will look with WooCommerce Template Style enabled. Alternatively, if this is unchecked, the template will appear as <a target = '_blank' href=<?php  echo wp_nonce_url( admin_url( '?wcal_preview_mail=true' ), 'woocommerce-ac' ) ; ?>>shown here</a>. <br> <strong>Note: </strong>When this setting is enabled, then "Send From This Name:" & "Send From This Email Address:" will be overwritten with WooCommerce -> Settings -> Email -> Email Sender Options.   
                                        </td>
                                     </tr>
                                     
                                     <tr>
                                        <th>
                                            <label for="wcal_wc_email_header"><b><?php _e( 'Email Template Header Text: ', 'woocommerce-ac' ); ?></b></label>
                                        </th>
                                        <td>

                                        <?php
                                        
                                        $wcal_wc_email_header = "";  
                                        if ( $mode == 'edittemplate' && count( $results ) > 0 && isset( $results[0]->wc_email_header ) ) {
                                            $wcal_wc_email_header = $results[0]->wc_email_header;
                                        }                                           
                                        if ( $wcal_wc_email_header == "" ) {
                                            $wcal_wc_email_header = "Abandoned cart reminder";
                                        }
                                        print'<input type="text" name="wcal_wc_email_header" id="wcal_wc_email_header" class="regular-text" value="' . $wcal_wc_email_header . '">'; ?>
                                        <img class="help_tip" width="16" height="16" data-tip='<?php _e( 'Enter the header which will appear in the abandoned WooCommerce email sent. This is only applicable when only used when "Use WooCommerce Template Style:" is checked.', 'woocommerce-ac' ) ?>' src="<?php echo plugins_url(); ?>/woocommerce/assets/images/help.png" />
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
                                                if( $mode == 'edittemplate' && count( $results ) > 0 && isset( $results[0]->frequency ) ) {
                                                    $frequency_edit = $results[0]->frequency;
                                                }                                               
                                                for ( $i = 1; $i < 4; $i++ ) {
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
                                                if ( $mode == 'edittemplate' && count( $results ) > 0 && isset( $results[0]->day_or_hour ) )
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
                                            <span class="description">
                                              <?php echo __( 'after cart is abandoned.', 'woocommerce-ac' ); ?>
                                            </span>
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
        
        function wcal_admin_footer_text( $footer_text ) {

            if ( isset( $_GET[ 'page' ] ) && $_GET[ 'page' ] === 'woocommerce_ac_page' ) {
                $footer_text = sprintf( __( 'If you love <strong>Abandoned Cart Lite for WooCommerce</strong>, then please leave us a <a href="https://wordpress.org/support/plugin/woocommerce-abandoned-cart/reviews/?rate=5#new-post" target="_blank" class="ac-rating-link" data-rated="Thanks :)">★★★★★</a>
                            rating. Thank you in advance. :)', 'woocommerce-ac' ) );
                wc_enqueue_js( "
                        jQuery( 'a.ac-rating-link' ).click( function() {
                            jQuery( this ).parent().text( jQuery( this ).data( 'rated' ) );
                        });
                " );               
            }            
            return $footer_text;
        }
        
        function bubble_sort_function( $unsort_array, $order ) {        
            $temp = array();
            foreach ( $unsort_array as $key => $value )
                $temp[ $key ] = $value; //concatenate something unique to make sure two equal weights don't overwrite each other        
            asort( $temp, SORT_NUMERIC ); // or ksort($temp, SORT_NATURAL); see paragraph above to understand why
        
            if( $order == 'desc' ) {
                $array = array_reverse( $temp, true );
            } else if( $order == 'asc' ) {
                $array = $temp;
            }
            unset( $temp );
            return $array;
        }
                
        function wcal_action_send_preview() {
            ?>
            <script type="text/javascript" >
                jQuery( document ).ready( function( $ )
                {
                    $( "table#addedit_template input#preview_email" ).click( function()
                    {   
                        var email_body = '';
                        if ( jQuery("#wp-woocommerce_ac_email_body-wrap").hasClass( "tmce-active" ) ) {
                            email_body = tinyMCE.get('woocommerce_ac_email_body').getContent();
                        } else {
                            email_body = jQuery('#woocommerce_ac_email_body').val();
                        }
                        var subject_email_preview = $( '#woocommerce_ac_email_subject' ).val();
                        var body_email_preview    = email_body;
                        var send_email_id         = $( '#send_test_email' ).val();  
                        var is_wc_template        = document.getElementById( "is_wc_template" ).checked;    
                        var wc_template_header    = $( '#wcal_wc_email_header' ).val() != '' ? $( '#wcal_wc_email_header' ).val() : 'Abandoned cart reminder';                                                              
                        var data                  = {                                                       
                                                        subject_email_preview: subject_email_preview,
                                                        body_email_preview   : body_email_preview,
                                                        send_email_id        : send_email_id,
                                                        is_wc_template       : is_wc_template,
                                                        wc_template_header   : wc_template_header,
                                                        action               : 'wcal_preview_email_sent'
                                                    };                                  
                        
                        // since 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php
                        $.post( ajaxurl, data, function( response ) {
                            if ( 'not sent' == response ) {
                                $( "#preview_email_sent_msg" ).html( "Test email is not sent as the Email body is empty." );
                                $( "#preview_email_sent_msg" ).fadeIn();
                                    setTimeout( function(){$( "#preview_email_sent_msg" ).fadeOut();}, 4000 );
                             } else {
                                $( "#preview_email_sent_msg" ).html( "<img src='<?php echo plugins_url(); ?>/woocommerce-abandoned-cart/assets/images/check.jpg'>&nbsp;Email has been sent successfully." );
                                    $( "#preview_email_sent_msg" ).fadeIn();
                                    setTimeout( function(){$( "#preview_email_sent_msg" ).fadeOut();}, 3000 );
                             }
                            //alert('Got this from the server: ' + response);
                        });
                    });
                });
            </script>
                <?php
        }
        public static function wcal_toggle_template_status () {
            global $wpdb;
            $template_id             = $_POST['wcal_template_id'];
            $current_template_status = $_POST['current_state'];

            if( "on" == $current_template_status ) {
                $query_update                 = "SELECT * FROM `" . $wpdb->prefix . "ac_email_templates_lite` WHERE id ='" . $template_id . "'";
                $get_selected_template_result = $wpdb->get_results( $query_update );
                $email_frequncy                = $get_selected_template_result[0]->frequency;
                $email_day_or_hour             = $get_selected_template_result[0]->day_or_hour;
                $query_update = "UPDATE `" . $wpdb->prefix . "ac_email_templates_lite` SET is_active='0' WHERE frequency='" . $email_frequncy . "' AND day_or_hour='" . $email_day_or_hour . "' ";
                $wcal_updated = $wpdb->query( $query_update );

                if ( 1 == $wcal_updated ){
                    $query_update_get_id = "SELECT id FROM  `" . $wpdb->prefix . "ac_email_templates_lite` WHERE id != $template_id AND frequency='" . $email_frequncy . "' AND day_or_hour='" . $email_day_or_hour . "' ";
                    $wcal_updated_get_id = $wpdb->get_results( $query_update_get_id );
                    $wcal_all_ids = '';
                    foreach ($wcal_updated_get_id as $wcal_updated_get_id_key => $wcal_updated_get_id_value ) {
                        # code...
                        if ( '' == $wcal_all_ids ){
                            $wcal_all_ids =  $wcal_updated_get_id_value->id;
                        }else{
                            $wcal_all_ids = $wcal_all_ids . ',' .$wcal_updated_get_id_value->id;
                        }
                    }
                    echo 'wcal-template-updated:'. $wcal_all_ids ;
                }

                $active = "1";
            } else {
                $active = "0";
            }
            $query_update = "UPDATE `" . $wpdb->prefix . "ac_email_templates_lite`
                    SET
                    is_active = '" . $active . "'
                    WHERE id  = '" . $template_id . "' ";
            $wpdb->query( $query_update );
            wp_die();

        }
        // Send Test Email      
        function wcal_preview_email_sent() {
            if ( '' != $_POST['body_email_preview'] ) {
                $from_email_name       = get_option ( 'wcal_from_name' );
                $reply_name_preview    = get_option ( 'wcal_from_email' );
                $from_email_preview    = get_option ( 'wcal_reply_email' );
                $subject_email_preview = stripslashes ( $_POST['subject_email_preview'] );
                $subject_email_preview = convert_smilies ( $subject_email_preview );                        
                $body_email_preview    = convert_smilies ( $_POST['body_email_preview'] );
                $is_wc_template        = $_POST['is_wc_template'];
                $wc_template_header    = stripslashes( $_POST['wc_template_header'] );                                                  
                $body_email_preview    = str_replace( '{{customer.firstname}}', 'John', $body_email_preview );
                $body_email_preview    = str_replace( '{{customer.lastname}}', 'Doe', $body_email_preview );
                $body_email_preview    = str_replace( '{{customer.fullname}}', 'John'." ".'Doe', $body_email_preview );
                $current_time_stamp    = current_time( 'timestamp' );
                $date_format           = date_i18n( get_option( 'date_format' ), $current_time_stamp );
                $time_format           = date_i18n( get_option( 'time_format' ), $current_time_stamp );
                $test_date             = $date_format . ' ' . $time_format;
                $body_email_preview    = str_replace( '{{cart.abandoned_date}}', $test_date, $body_email_preview );             
                $cart_url              = wc_get_page_permalink( 'cart' );
                $body_email_preview    = str_replace( '{{cart.link}}', $cart_url, $body_email_preview );
                $body_email_preview    = str_replace( '{{cart.unsubscribe}}', '<a href=#>unsubscribe</a>', $body_email_preview );               
                $wcal_price            = wc_price( '100' );
                $wcal_total_price      = wc_price( '200' );
                if ( class_exists( 'WP_Better_Emails' ) ) {
                    $headers           = "From: " . $from_email_name . " <" . $from_email_preview . ">" . "\r\n";
                    $headers          .= "Content-Type: text/plain" . "\r\n";
                    $headers          .= "Reply-To:  " . $reply_name_preview . " " . "\r\n";
                    $var               =  '<table width = 100%>
                                            <tr> <td colspan="5"> <h3>'.__( "Your Shopping Cart", "woocommerce-ac" ).'</h3> </td></tr>
                                            <tr align="center">
                                               <th>'.__( "Item", "woocommerce-ac" ).'</th>
                                               <th>'.__( "Name", "woocommerce-ac" ).'</th>
                                               <th>'.__( "Quantity", "woocommerce-ac" ).'</th>
                                               <th>'.__( "Price", "woocommerce-ac" ).'</th>
                                               <th>'.__( "Line Subtotal", "woocommerce-ac" ).'</th>
                                            </tr>
                                            <tr align="center">
                                               <td><img class="demo_img" width="42" height="42" src="'.plugins_url().'/woocommerce-abandoned-cart/assets/images/shoes.jpg"/></td>
                                               <td>'.__( "Men\'\s Formal Shoes", "woocommerce-ac" ).'</td>
                                               <td>1</td>
                                               <td>' . $wcal_price . '</td>
                                               <td>' . $wcal_price . '</td>
                                            </tr>
                                            <tr align="center">
                                               <td><img class="demo_img" width="42" height="42" src="'.plugins_url().'/woocommerce-abandoned-cart/assets/images/handbag.jpg"/></td>
                                               <td>'.__( "Woman\'\s Hand Bags", "woocommerce-ac" ).'</td>
                                               <td>1</td>
                                               <td>' . $wcal_price . '</td>
                                               <td>' . $wcal_price . '</td>
                                            </tr>
                                            <tr align="center">
                                               <td></td>
                                               <td></td>
                                               <td></td>
                                               <td>'.__( "Cart Total:", "woocommerce-ac" ).'</td>
                                               <td>' . $wcal_total_price . '</td>
                                            </tr>
                                        </table>';
                } else {
                    $headers           = "From: " . $from_email_name . " <" . $from_email_preview . ">" . "\r\n";
                    $headers          .= "Content-Type: text/html" . "\r\n";
                    $headers          .= "Reply-To:  " . $reply_name_preview . " " . "\r\n";
                    $var               = '<h3>'.__( "Your Shopping Cart", "woocommerce-ac" ).'</h3>
                                        <table border="0" cellpadding="10" cellspacing="0" class="templateDataTable">
                                            <tr align="center">
                                               <th>'.__( "Item", "woocommerce-ac" ).'</th>
                                               <th>'.__( "Name", "woocommerce-ac" ).'</th>
                                               <th>'.__( "Quantity", "woocommerce-ac" ).'</th>
                                               <th>'.__( "Price", "woocommerce-ac" ).'</th>
                                               <th>'.__( "Line Subtotal", "woocommerce-ac" ).'</th>
                                            </tr>
                                            <tr align="center">
                                               <td><img class="demo_img" width="42" height="42" src="'.plugins_url().'/woocommerce-abandoned-cart/assets/images/shoes.jpg"/></td>
                                               <td>'.__( "Men\'\s Formal Shoes", "woocommerce-ac" ).'</td>
                                               <td>1</td>
                                               <td>' . $wcal_price . '</td>
                                               <td>' . $wcal_price . '</td>
                                            </tr>
                                            <tr align="center">
                                               <td><img class="demo_img" width="42" height="42" src="'.plugins_url().'/woocommerce-abandoned-cart/assets/images/handbag.jpg"/></td>
                                               <td>'.__( "Woman\'\s Hand Bags", "woocommerce-ac" ).'</td>
                                               <td>1</td>
                                               <td>' . $wcal_price . '</td>
                                               <td>' . $wcal_price . '</td>
                                            </tr>
                                            <tr align="center">
                                               <td></td>
                                               <td></td>
                                               <td></td>
                                               <td>'.__( "Cart Total:", "woocommerce-ac" ).'</td>
                                               <td>' . $wcal_total_price . '</td>
                                            </tr>
                                         </table>';
                }                       
                $body_email_preview     = str_replace( '{{products.cart}}', $var, $body_email_preview );                
                if ( isset( $_POST['send_email_id'] ) ) {
                      $to_email_preview = $_POST['send_email_id'];
                } else {
                      $to_email_preview = "";
                }                
                $user_email_from        = get_option( 'admin_email' );                
                $body_email_final_preview = stripslashes( $body_email_preview );
                
                if ( isset( $is_wc_template ) && "true" == $is_wc_template ) {
                    ob_start();
                    // Get email heading
                    wc_get_template( 'emails/email-header.php', array( 'email_heading' => $wc_template_header ) );
                    $email_body_template_header = ob_get_clean();                           

                    ob_start();                         
                    wc_get_template( 'emails/email-footer.php' );                               
                    $email_body_template_footer = ob_get_clean();   
                                            
                    $final_email_body =  $email_body_template_header . $body_email_final_preview . $email_body_template_footer;
                    
                    wc_mail( $to_email_preview, $subject_email_preview, $final_email_body , $headers );    
                }
                else {
                    wp_mail( $to_email_preview, $subject_email_preview, stripslashes( $body_email_preview ), $headers );
                }                   
                echo "email sent";
                die();
            } else {
                echo "not sent";
                die();
            }
        }                   
    }   
}       
$woocommerce_abandon_cart = new woocommerce_abandon_cart_lite();
?>