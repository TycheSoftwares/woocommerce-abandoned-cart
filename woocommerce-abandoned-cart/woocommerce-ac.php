<?php 
/*
* Plugin Name: Abandoned Cart Lite for WooCommerce
* Plugin URI: http://www.tychesoftwares.com/store/premium-plugins/woocommerce-abandoned-cart-pro
* Description: This plugin captures abandoned carts by logged-in users & emails them about it. 
* <strong><a href="http://www.tychesoftwares.com/store/premium-plugins/woocommerce-abandoned-cart-pro">Click here to get the 
* PRO Version.</a></strong>
* Version: 5.3.1
* Author: Tyche Softwares
* Author URI: http://www.tychesoftwares.com/
* Text Domain: woocommerce-abandoned-cart
* Domain Path: /i18n/languages/
* Requires PHP: 5.6
* WC requires at least: 3.0.0
* WC tested up to: 3.5
*
* @package Abandoned-Cart-Lite-for-WooCommerce
*/

require_once( "includes/wcal_class-guest.php" );
require_once( "includes/wcal_default-settings.php" );
require_once( "includes/wcal_actions.php" );
require_once( "includes/classes/class-wcal-aes.php" );
require_once( "includes/classes/class-wcal-aes-counter.php" );
require_once( "includes/wcal-common.php" );

require_once( "includes/wcal_admin_notice.php");
require_once( 'includes/wcal_data_tracking_message.php' );
require_once( 'includes/admin/wcal_privacy_erase.php' );
require_once( 'includes/admin/wcal_privacy_export.php' );

// Add a new interval of 15 minutes
add_filter( 'cron_schedules', 'wcal_add_cron_schedule' );

/**
 * It will add a cron job for sending the Abandonend cart reminder emails.
 * By default it will set 15 minutes of interval.
 * @hook cron_schedules
 * @param array $schedules
 * @return array $schedules
 * @since 1.3
 * @package Abandoned-Cart-Lite-for-WooCommerce/Cron
 */
function wcal_add_cron_schedule( $schedules ) { 
    $schedules['15_minutes_lite'] = array(
                'interval'  => 900, // 15 minutes in seconds
                'display'   => __( 'Once Every Fifteen Minutes' ),
    );
    return $schedules;
}

/**
 * Schedule an action if it's not already scheduled.
 * @since 1.3
 * @package Abandoned-Cart-Lite-for-WooCommerce/Cron
 */ 
if ( ! wp_next_scheduled( 'woocommerce_ac_send_email_action' ) ) {
    wp_schedule_event( time(), '15_minutes_lite', 'woocommerce_ac_send_email_action' );
}

/**
 * Schedule an action to delete old carts once a day
 * @since 5.1
 * @package Abandoned-Cart-Lite-for-WooCommerce/Cron
 */
if( ! wp_next_scheduled( 'wcal_clear_carts' ) ) {
    wp_schedule_event( time(), 'daily', 'wcal_clear_carts' );
}
/**
 * Hook into that action that'll fire every 15 minutes 
 */
add_action( 'woocommerce_ac_send_email_action', 'wcal_send_email_cron' );

/**
 * It will add the wcal_send_email.php file which is responsible for sending the abandoned cart reminde emails.
 * @hook woocommerce_ac_send_email_action
 * @since 1.3
 * @package Abandoned-Cart-Lite-for-WooCommerce/Cron
 */
function wcal_send_email_cron() {
    //require_once( ABSPATH.'wp-content/plugins/woocommerce-abandoned-cart/cron/send_email.php' );
    $plugin_dir_path = plugin_dir_path( __FILE__ );
    require_once( $plugin_dir_path . 'cron/wcal_send_email.php' );
}
    /**
     * woocommerce_abandon_cart_lite class
     **/
if ( ! class_exists( 'woocommerce_abandon_cart_lite' ) ) {
    

    /**
     * It will add the hooks, filters, menu and the variables and all the necessary actions for the plguins which will be used 
     * all over the plugin.
     * @since 1.0
     * @package Abandoned-Cart-Lite-for-WooCommerce/Core
     */
    class woocommerce_abandon_cart_lite {
        var $one_hour;
        var $three_hours;
        var $six_hours;
        var $twelve_hours;
        var $one_day;
        var $one_week;          
        var $duration_range_select = array();
        var $start_end_dates       = array();
        /**
         * The constructor will add the hooks, filters and the variable which will be used all over the plugin.
         * @since 1.0
         * 
         */
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

            // Background Processing for Cron
            require_once( 'cron/wcal_send_email.php' );
            require_once( 'includes/background-processes/wcal_process_base.php' );
            
            // WordPress Administration Menu 
            add_action ( 'admin_menu',                                  array( &$this, 'wcal_admin_menu' ) );
            
            // Actions to be done on cart update
            add_action ( 'woocommerce_cart_updated',                    array( &$this, 'wcal_store_cart_timestamp' ) );

            add_action ( 'admin_init',                                  array( &$this, 'wcal_action_admin_init' ) );
            
            // Update the options as per settings API
            add_action ( 'admin_init',                                  array( &$this, 'wcal_update_db_check' ) );

            // Wordpress settings API
            add_action( 'admin_init',                                   array( &$this, 'wcal_initialize_plugin_options' ) );
            
            // Language Translation
            add_action ( 'init',                                        array( &$this, 'wcal_update_po_file' ) );

            add_action ( 'init',                                        array ( &$this, 'wcal_add_component_file')  );
            
            // track links
            add_filter( 'template_include',                             array( &$this, 'wcal_email_track_links' ), 99, 1 );
            
            //It will used to unsubcribe the emails.
            add_action( 'template_include',                             array( &$this, 'wcal_email_unsubscribe'),99, 1 );
            
            add_action ( 'admin_enqueue_scripts',                       array( &$this, 'wcal_enqueue_scripts_js' ) );
            add_action ( 'admin_enqueue_scripts',                       array( &$this, 'wcal_enqueue_scripts_css' ) );
			//delete abandoned order after X number of days
            if ( class_exists( 'wcal_delete_bulk_action_handler' ) ) {
                add_action( 'wcal_clear_carts',                         array( 'wcal_delete_bulk_action_handler', 'wcal_delete_abandoned_carts_after_x_days' ) );
            }
            
            if ( is_admin() ) {
                // Load "admin-only" scripts here               
                add_action ( 'admin_head',                              array( &$this, 'wcal_action_send_preview' ) );
                add_action ( 'wp_ajax_wcal_preview_email_sent',         array( &$this, 'wcal_preview_email_sent' ) );
                add_action ( 'wp_ajax_wcal_toggle_template_status',     array( &$this, 'wcal_toggle_template_status' ) );   

                add_filter( 'ts_tracker_data',                          array( 'wcal_common', 'ts_add_plugin_tracking_data' ), 10, 1 );
                add_filter( 'ts_tracker_opt_out_data',                  array( 'wcal_common', 'ts_get_data_for_opt_out' ), 10, 1 );
                add_filter( 'ts_deativate_plugin_questions',            array( &$this,  'wcal_deactivate_add_questions' ), 10, 1 );
            }
             
            // Plugin Settings link in WP->Plugins page
            $plugin = plugin_basename( __FILE__ );
            add_action( "plugin_action_links_$plugin",                  array( &$this, 'wcal_settings_link' ) );
            
            add_action( 'admin_init',                                                  array( $this,   'wcal_preview_emails' ) );
            add_action( 'init',                                                        array( $this,   'wcal_app_output_buffer') );         

            add_filter( 'admin_footer_text',                                           array( $this,   'wcal_admin_footer_text' ), 1 );

            add_action( 'admin_notices',                                               array( 'Wcal_Admin_Notice',   'wcal_show_db_update_notice' ) );

            include_once 'includes/frontend/wcal_frontend.php';
        }
	
        /**
         * Add Settings link to WP->Plugins page
         * @since 5.3.0
         */
        public static function wcal_settings_link( $links ) {
            $settings_link = '<a href="admin.php?page=woocommerce_ac_page&action=emailsettings">' . __( 'Settings', 'woocommerce-abandoned-cart' ) . '</a>';
            array_push( $links, $settings_link );
            return $links;
        }

	    /**
         * It will load the boilerplate components file. In this file we have included all boilerplate files.
         * We need to inlcude this file after the init hook.
         * @hook init
         */
        public static function wcal_add_component_file () {
            if ( is_admin() ) {
                require_once( 'includes/wcal_all_component.php' );
                
            }
        }
        /**
         * It will add the Questions while admin deactivate the plugin.
         * @hook ts_deativate_plugin_questions
         * @param array $wcal_add_questions Blank array
         * @return array $wcal_add_questions List of all questions.
         */
        public static function wcal_deactivate_add_questions ( $wcal_add_questions ) {

            $wcal_add_questions = array(
                0 => array(
                    'id'                => 4,
                    'text'              => __( "Emails are not being sent to customers.", "woocommerce-abandoned-cart" ),
                    'input_type'        => '',
                    'input_placeholder' => ''
                    ), 
                1 =>  array(
                    'id'                => 5,
                    'text'              => __( "Capturing of cart and other information was not satisfactory.", "woocommerce-abandoned-cart" ),
                    'input_type'        => '',
                    'input_placeholder' => ''
                ),
                2 => array(
                    'id'                => 6,
                    'text'              => __( "I cannot see abandoned cart reminder emails records.", "woocommerce-abandoned-cart" ),
                    'input_type'        => '',
                    'input_placeholder' => ''
                ),
                3 => array(
                    'id'                => 7,
                    'text'              => __( "I want to upgrade the plugin to the PRO version.", "woocommerce-abandoned-cart" ),
                    'input_type'        => '',
                    'input_placeholder' => ''
                )

            );
            return $wcal_add_questions;
        }

        /**
         * It will ganerate the preview email template.
         * @hook admin_init
         * @globals mixed $woocommerce
         * @since 2.5
         */
        public function wcal_preview_emails() {
            global $woocommerce;
            if ( isset( $_GET['wcal_preview_woocommerce_mail'] ) ) {
                if ( ! wp_verify_nonce( $_REQUEST['_wpnonce'], 'woocommerce-abandoned-cart' ) ) {
                    die( 'Security check' );
                }
                $message = '';
                // create a new email
                if ( $woocommerce->version < '2.3' ) {
                    global $email_heading;                   
                    ob_start();
                    
                    include( 'views/wcal-wc-email-template-preview.php' );
                    $mailer        = WC()->mailer();
                    $message       = ob_get_clean();
                    $email_heading = __( 'HTML Email Template', 'woocommerce-abandoned-cart' );
                    $message       =  $mailer->wrap_message( $email_heading, $message );
                } else {
                    // load the mailer class
                    $mailer        = WC()->mailer(); 
                    // get the preview email subject
                    $email_heading = __( 'Abandoned cart Email Template', 'woocommerce-abandoned-cart' );       
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
                if ( ! wp_verify_nonce( $_REQUEST['_wpnonce'], 'woocommerce-abandoned-cart' ) ) {
                    die( 'Security check' );
                }
                // get the preview email content
                ob_start();
                include( 'views/wcal-email-template-preview.php' );
                $message = ob_get_clean();        
                // print the preview email
                echo $message;
                exit;
            }
        }

        /**
         * In this version we have allowed customer to transalte the plugin string using .po and .pot file.
         * @hook init
         * @return $loaded
         * @since 1.6
         */
        function  wcal_update_po_file() {
            /*
            * Due to the introduction of language packs through translate.wordpress.org, loading our textdomain is complex.
            *
            * In v4.7, our textdomain changed from "woocommerce-ac" to "woocommerce-abandoned-cart".
            */
            $domain = 'woocommerce-abandoned-cart';
            $locale = apply_filters( 'plugin_locale', get_locale(), $domain );
            if ( $loaded = load_textdomain( $domain, trailingslashit( WP_LANG_DIR ) . $domain . '-' . $locale . '.mo' ) ) {
                return $loaded;
            } else {
                load_plugin_textdomain( $domain, FALSE, basename( dirname( __FILE__ ) ) . '/i18n/languages/' );
            }
        }
    
        /**
         * It will create the plugin tables & the options reqired for plugin.
         * @hook register_activation_hook
         * @globals mixed $wpdb
         * @since 1.0
         */                             
        function wcal_activate() {
            global $wpdb; 
            $wcap_collate = '';
            if ( $wpdb->has_cap( 'collation' ) ) {
                $wcap_collate = $wpdb->get_charset_collate();
            }
            $table_name = $wpdb->prefix . "ac_email_templates_lite";            
            $sql = "CREATE TABLE IF NOT EXISTS $table_name (
                    `id` int(11) NOT NULL AUTO_INCREMENT,
                    `subject` text NOT NULL,
                    `body` mediumtext NOT NULL,
                    `is_active` enum('0','1') NOT NULL,
                    `frequency` int(11) NOT NULL,
                    `day_or_hour` enum('Days','Hours') NOT NULL,
                    `template_name` text NOT NULL,
                    `is_wc_template` enum('0','1') NOT NULL,
                    `default_template` int(11) NOT NULL,
                    `wc_email_header` varchar(50) NOT NULL,
                    PRIMARY KEY (`id`)
                    ) $wcap_collate AUTO_INCREMENT=1 ";
        
            require_once ( ABSPATH . 'wp-admin/includes/upgrade.php' );
            dbDelta( $sql );
            
            // $table_name = $wpdb->prefix . "ac_email_templates_lite";
            // $check_template_table_query = "SHOW COLUMNS FROM $table_name LIKE 'is_wc_template' ";
            // $results = $wpdb->get_results( $check_template_table_query );
             
            // if ( count( $results ) == 0 ) {
            //     $alter_template_table_query = "ALTER TABLE $table_name
            //     ADD COLUMN `is_wc_template` enum('0','1') COLLATE utf8mb4_unicode_ci NOT NULL AFTER `template_name`,
            //     ADD COLUMN `default_template` int(11) NOT NULL AFTER `is_wc_template`";
                
            //     $wpdb->get_results( $alter_template_table_query );
            // }
            
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
                             `user_type` text,
                             `unsubscribe_link` enum('0','1') COLLATE utf8_unicode_ci NOT NULL,
                             `session_id` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
                             PRIMARY KEY (`id`)
                             ) $wcap_collate";
                     
            require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
            dbDelta( $history_query );
            // Default templates:  function call to create default templates.
            $check_table_empty  = $wpdb->get_var( "SELECT COUNT(*) FROM `" . $wpdb->prefix . "ac_email_templates_lite`" );
    
            if ( ! get_option( 'wcal_new_default_templates' ) ) {         
                if ( 0 == $check_table_empty ) {
                    $default_template = new wcal_default_template_settings;
                    $default_template->wcal_create_default_templates();
                    update_option( 'wcal_new_default_templates', "yes" );
                }
            }

            $guest_table        = $wpdb->prefix."ac_guest_abandoned_cart_history_lite" ;
            $query_guest_table  = "SHOW TABLES LIKE '$guest_table' ";
            $result_guest_table = $wpdb->get_results( $query_guest_table );          
            
            if ( 0 == count( $result_guest_table ) ) {
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
            if ( ! get_option( 'ac_lite_track_guest_cart_from_cart_page' ) ) {
                add_option( 'ac_lite_track_guest_cart_from_cart_page', 'on' );
            } 
            if ( ! get_option( 'wcal_from_name' ) ) {
                add_option( 'wcal_from_name', 'Admin' );
            }
            $wcal_get_admin_email = get_option( 'admin_email' );
            if ( ! get_option( 'wcal_from_email' ) ) {
                add_option( 'wcal_from_email', $wcal_get_admin_email );
            }
            
            if ( ! get_option( 'wcal_reply_email' ) ) {
                add_option( 'wcal_reply_email', $wcal_get_admin_email );
            }

            do_action( 'wcal_activate' );
        }     
    
        /**
         * It will add the section, field, & registres the plugin fields using Settings API.
         * @hook admin_init
         * @since 2.5
         */
        function wcal_initialize_plugin_options() {

            // First, we register a section. This is necessary since all future options must belong to a
            add_settings_section(
                'ac_lite_general_settings_section',                 // ID used to identify this section and with which to register options
                __( 'Settings', 'woocommerce-abandoned-cart' ),     // Title to be displayed on the administration page
                array( $this, 'ac_lite_general_options_callback' ), // Callback used to render the description of the section
                'woocommerce_ac_page'                               // Page on which to add this section of options
            );
        
            add_settings_field(
                'ac_lite_cart_abandoned_time',
                __( 'Cart abandoned cut-off time', 'woocommerce-abandoned-cart' ),
                array( $this, 'ac_lite_cart_abandoned_time_callback' ),
                'woocommerce_ac_page',
                'ac_lite_general_settings_section',
                array( __( 'Consider cart abandoned after X minutes of item being added to cart & order not placed.', 'woocommerce-abandoned-cart' ) )
            );

            add_settings_field(
                'ac_lite_delete_abandoned_order_days',
                __( 'Automatically Delete Abandoned Orders after X days', 'woocommerce-abandoned-cart' ),
                array( $this, 'wcal_delete_abandoned_orders_days_callback' ),
                'woocommerce_ac_page',
                'ac_lite_general_settings_section',
                array( __( 'Automatically delete abandoned cart orders after X days.', 'woocommerce-abandoned-cart' ) )
            );

            
            add_settings_field(
                'ac_lite_email_admin_on_recovery',
                __( 'Email admin On Order Recovery', 'woocommerce-abandoned-cart' ),
                array( $this, 'ac_lite_email_admin_on_recovery' ),
                'woocommerce_ac_page',
                'ac_lite_general_settings_section',
                array( __( 'Sends email to Admin if an Abandoned Cart Order is recovered.', 'woocommerce-abandoned-cart' ) )
            );

            
            add_settings_field(
            'ac_lite_track_guest_cart_from_cart_page',
            __( 'Start tracking from Cart Page', 'woocommerce-abandoned-cart' ),
            array( $this, 'wcal_track_guest_cart_from_cart_page_callback' ),
            'woocommerce_ac_page',
            'ac_lite_general_settings_section',
            array( __( 'Enable tracking of abandoned products & carts even if customer does not visit the checkout page or does not enter any details on the checkout page like Name or Email. Tracking will begin as soon as a visitor adds a product to their cart and visits the cart page.', 'woocommerce-abandoned-cart' ) )
            );

            add_settings_field(
                'wcal_guest_cart_capture_msg',
                __( 'Message to be displayed for Guest users when tracking their carts', 'woocommerce-abandoned-cart' ),
                array( $this, 'wcal_guest_cart_capture_msg_callback' ),
                'woocommerce_ac_page',
                'ac_lite_general_settings_section',
                array( __( '<br>In compliance with GDPR, add a message on the Checkout page to inform Guest users of how their data is being used.<br><i>For example: Your email address will help us support your shopping experience throughout the site. Please check our Privacy Policy to see how we use your personal data.</i>', 'woocommerce-abandoned-cart' ) )
            );
            
            add_settings_field(
                'wcal_logged_cart_capture_msg',
                __( 'Message to be displayed for registered users when tracking their carts.', 'woocommerce-abandoned-cart' ),
                array( $this, 'wcal_logged_cart_capture_msg_callback' ),
                'woocommerce_ac_page',
                'ac_lite_general_settings_section',
                array( __( '<br>In compliance with GDPR, add a message on the Shop & Product pages to inform Registered users of how their data is being used.<br><i>For example: Please check our Privacy Policy to see how we use your personal data.</i>', 'woocommerce-abandoned-cart' ) )
            );
            
            /**
             * New section for the Adding the abandoned cart setting.
             * @since  4.7
             */
            
            add_settings_section(
            'ac_email_settings_section',                                                       // ID used to identify this section and with which to register options
            __( 'Settings for abandoned cart recovery emails', 'woocommerce-abandoned-cart' ), // Title to be displayed on the administration page
            array( $this, 'wcal_email_callback' ),                                             // Callback used to render the description of the section
            'woocommerce_ac_email_page'                                                        // Page on which to add this section of options
            );
            
            add_settings_field(
            'wcal_from_name',
            __( '"From" Name', 'woocommerce-abandoned-cart'  ),
            array( $this, 'wcal_from_name_callback' ),
            'woocommerce_ac_email_page',
            'ac_email_settings_section',
            array( 'Enter the name that should appear in the email sent.', 'woocommerce-abandoned-cart' )
            );
            
            add_settings_field(
            'wcal_from_email',
            __( '"From" Address', 'woocommerce-abandoned-cart'  ),
            array( $this, 'wcal_from_email_callback' ),
            'woocommerce_ac_email_page',
            'ac_email_settings_section',
            array( 'Email address from which the reminder emails should be sent.', 'woocommerce-abandoned-cart' )
            );
            
            add_settings_field(
            'wcal_reply_email',
            __( 'Send Reply Emails to', 'woocommerce-abandoned-cart'  ),
            array( $this, 'wcal_reply_email_callback' ),
            'woocommerce_ac_email_page',
            'ac_email_settings_section',
            array( 'When a contact receives your email and clicks reply, which email address should that reply be sent to?', 'woocommerce-abandoned-cart' )
            );
            
            // Finally, we register the fields with WordPress
            register_setting(
                'woocommerce_ac_settings',
                'ac_lite_cart_abandoned_time',
                array ( $this, 'ac_lite_cart_time_validation' )
            );

            register_setting(
                'woocommerce_ac_settings',
                'ac_lite_delete_abandoned_order_days',
                array ( $this, 'wcal_delete_days_validation' )
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
                'woocommerce_ac_settings',
                'wcal_guest_cart_capture_msg'
            );
            
            register_setting(
                'woocommerce_ac_settings',
                'wcal_logged_cart_capture_msg'
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

            do_action ( "wcal_add_new_settings" );
        }
    
        /**
         * Settings API callback for section "ac_lite_general_settings_section".
         * @since 2.5
         */
        function ac_lite_general_options_callback() {
        
        }
    
        /**
         * Settings API callback for cart time field.
         * @param array $args Arguments
         * @since 2.5
         */
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
    
        /**
         * Settings API cart time field validation.
         * @param int | string $input
         * @return int | string $output
         * @since 2.5
         */
        function ac_lite_cart_time_validation( $input ) {
            $output = '';
            if ( '' != $input && ( is_numeric( $input) && $input > 0  ) ) {
                $output = stripslashes( $input) ;
            } else {
                add_settings_error( 'ac_lite_cart_abandoned_time', 'error found', __( 'Abandoned cart cut off time should be numeric and has to be greater than 0.', 'woocommerce-abandoned-cart' ) );
            }
            return $output;
        }

        /**
         * Validation for automatically delete abandoned carts after X days.
         * @param int | string $input input of the field Abandoned cart cut off time
         * @return int | string $output Error message or the input value
         * @since  5.0
         */
        public static function wcal_delete_days_validation( $input ) {
            $output = '';
            if ( '' == $input || ( is_numeric( $input ) && $input > 0 ) ) {
                $output = stripslashes( $input );
            } else {
                add_settings_error( 'ac_lite_delete_abandoned_order_days', 'error found', __( 'Automatically Delete Abandoned Orders after X days has to be greater than 0.', 'woocommerce-abandoned-cart' ) );
            }
            return $output;
        }

        /**
         * Callback for deleting abandoned order after X days field.
         * @param array $args Argument given while adding the field
         * @since 5.0
         */
        public static function wcal_delete_abandoned_orders_days_callback( $args ) {
            // First, we read the option
            $delete_abandoned_order_days = get_option( 'ac_lite_delete_abandoned_order_days' );
            // Next, we update the name attribute to access this element's ID in the context of the display options array
            // We also access the show_header element of the options collection in the call to the checked() helper function
            printf(
                '<input type="text" id="ac_lite_delete_abandoned_order_days" name="ac_lite_delete_abandoned_order_days" value="%s" />',
                isset( $delete_abandoned_order_days ) ? esc_attr( $delete_abandoned_order_days ) : ''
            );
            // Here, we'll take the first argument of the array and add it to a label next to the checkbox
            $html = '<label for="ac_lite_delete_abandoned_order_days"> ' . $args[0] . '</label>';
            echo $html;
        }
        
        /**
         * Settings API callback for email admin on cart recovery field.
         * @param array $args Arguments
         * @since 2.5
         */
        function ac_lite_email_admin_on_recovery( $args ) {     
            // First, we read the option
            $email_admin_on_recovery = get_option( 'ac_lite_email_admin_on_recovery' );
             
            // This condition added to avoid the notie displyed while Check box is unchecked.
            if ( isset( $email_admin_on_recovery ) && '' == $email_admin_on_recovery ) {
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
            $html .= '<label for="ac_lite_email_admin_on_recovery"> ' . $args[0] . '</label>';
            echo $html;
        }
        /**
         * Settings API callback for capturing guest cart which do not reach the checkout page.
         * @param array $args Arguments
         * @since 2.7
         */
        function wcal_track_guest_cart_from_cart_page_callback( $args ) {
            // First, we read the option
            $disable_guest_cart_from_cart_page = get_option( 'ac_lite_track_guest_cart_from_cart_page' );
           
            // This condition added to avoid the notice displyed while Check box is unchecked.
            if ( isset( $disable_guest_cart_from_cart_page ) && '' == $disable_guest_cart_from_cart_page ) {
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
        
        /**
         * Call back function for guest user cart capture message
         * @param array $args Argument for adding field details
         * @since 7.8
         */
        public static function wcal_guest_cart_capture_msg_callback( $args ) {
        
            $guest_msg = get_option( 'wcal_guest_cart_capture_msg' );
        
            $html = "<textarea rows='4' cols='80' id='wcal_guest_cart_capture_msg' name='wcal_guest_cart_capture_msg'>$guest_msg</textarea>";
        
            $html .= '<label for="wcal_guest_cart_capture_msg"> ' . $args[0] . '</label>';
            echo $html;
        }
        
        /**
         * Call back function for registered user cart capture message
         * @param array $args Argument for adding field details
         * @since 7.8
         */
        public static function wcal_logged_cart_capture_msg_callback( $args ) {
        
            $logged_msg = get_option( 'wcal_logged_cart_capture_msg' );
        
            $html = "<input type='text' class='regular-text' id='wcal_logged_cart_capture_msg' name='wcal_logged_cart_capture_msg' value='$logged_msg' />";
        
            $html .= '<label for="wcal_logged_cart_capture_msg"> ' . $args[0] . '</label>';
            echo $html;
        }

        /**
         * Settings API callback for Abandoned cart email settings of the plugin.
         * @since 3.5
         */
        function wcal_email_callback () {
        
        }
        
        /**
         * Settings API callback for from name used in Abandoned cart email.
         * @param array $args Arguments
         * @since 3.5
         */
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
        
        /**
         * Settings API callback for from email used in Abandoned cart email.
         * @param array $args Arguments
         * @since 3.5
         */
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
        
        /**
         * Settings API callback for reply email used in Abandoned cart email.
         * @param array $args Arguments
         * @since 3.5
         */
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

        /**
         * It will be executed when the plugin is upgraded.
         * @hook admin_init
         * @globals mixed $wpdb
         * @since 1.0
         */
        function wcal_update_db_check() {
            global $wpdb;
            
            $wcal_previous_version = get_option( 'wcal_previous_version' );

            if ( $wcal_previous_version != wcal_common::wcal_get_version() ) {
                update_option( 'wcal_previous_version', '5.3.1' );
            }

            /**
             * This is used to prevent guest users wrong Id. If guest users id is less then 63000000 then this code will
             * ensure that we will change the id of guest tables so it wont affect on the next guest users.
             */         
            if ( $wpdb->get_var( "SHOW TABLES LIKE '{$wpdb->prefix}ac_guest_abandoned_cart_history_lite';" )  && 'yes' != get_option( 'wcal_guest_user_id_altered' ) ) {
                $last_id = $wpdb->get_var( "SELECT max(id) FROM `{$wpdb->prefix}ac_guest_abandoned_cart_history_lite`;" );
                if ( NULL != $last_id && $last_id <= 63000000 ) {
                    $wpdb->query( "ALTER TABLE {$wpdb->prefix}ac_guest_abandoned_cart_history_lite AUTO_INCREMENT = 63000000;" );
                    update_option ( 'wcal_guest_user_id_altered', 'yes' );
                }
            }

            if( !get_option( 'wcal_new_default_templates' ) ) {          
                    $default_template = new wcal_default_template_settings;
                    $default_template->wcal_create_default_templates();
                    add_option( 'wcal_new_default_templates', "yes" );
                
            }
            if ( 'yes' != get_option( 'ac_lite_alter_table_queries' ) ) {
                $ac_history_table_name = $wpdb->prefix."ac_abandoned_cart_history_lite";
                $check_table_query     = "SHOW COLUMNS FROM $ac_history_table_name LIKE 'user_type'";
                $results               = $wpdb->get_results( $check_table_query );
                
                if ( 0 == count( $results ) ) {
                    $alter_table_query = "ALTER TABLE $ac_history_table_name ADD `user_type` text AFTER  `recovered_cart`";
                    $wpdb->get_results( $alter_table_query );
                }
        
                $table_name                 = $wpdb->prefix . "ac_email_templates_lite";
                $check_template_table_query = "SHOW COLUMNS FROM $table_name LIKE 'is_wc_template' ";
                $results                    = $wpdb->get_results( $check_template_table_query );
                 
                if ( 0 == count( $results ) ) {
                    $alter_template_table_query = "ALTER TABLE $table_name
                    ADD COLUMN `is_wc_template` enum('0','1') COLLATE utf8_unicode_ci NOT NULL AFTER `template_name`,
                    ADD COLUMN `default_template` int(11) NOT NULL AFTER `is_wc_template`";
                    $wpdb->get_results( $alter_template_table_query );
                }
                
                $table_name                       = $wpdb->prefix . "ac_email_templates_lite";
                $check_email_template_table_query = "SHOW COLUMNS FROM $table_name LIKE 'wc_email_header' ";
                $results_email                    = $wpdb->get_results( $check_email_template_table_query );
                
                if ( 0 == count( $results_email ) ) {
                    $alter_email_template_table_query = "ALTER TABLE $table_name
                    ADD COLUMN `wc_email_header` varchar(50) NOT NULL AFTER `default_template`";
                    $wpdb->get_results( $alter_email_template_table_query );
                }

                if ( $wpdb->get_var( "SHOW TABLES LIKE '{$wpdb->prefix}ac_abandoned_cart_history_lite';" ) ) {
                    if ( ! $wpdb->get_var( "SHOW COLUMNS FROM `{$wpdb->prefix}ac_abandoned_cart_history_lite` LIKE 'unsubscribe_link';" ) ) {
                        $wpdb->query( "ALTER TABLE {$wpdb->prefix}ac_abandoned_cart_history_lite ADD `unsubscribe_link` enum('0','1') COLLATE utf8_unicode_ci NOT NULL AFTER `user_type`;" );
                    }
                }
            
                if ( $wpdb->get_var( "SHOW TABLES LIKE '{$wpdb->prefix}ac_abandoned_cart_history_lite';" ) ) {
                    if ( ! $wpdb->get_var( "SHOW COLUMNS FROM `{$wpdb->prefix}ac_abandoned_cart_history_lite` LIKE 'session_id';" ) ) {
                        $wpdb->query( "ALTER TABLE {$wpdb->prefix}ac_abandoned_cart_history_lite ADD `session_id` varchar(50) COLLATE utf8_unicode_ci NOT NULL AFTER `unsubscribe_link`;" );
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
                        $get_email_template_result = $wpdb->get_results ( $get_email_template_query );
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
                        $get_email_template_from_name_result = $wpdb->get_results ( $get_email_template_from_name_query );
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
                        $get_email_template_reply_email_result = $wpdb->get_results ( $get_email_template_reply_email_query);
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
 
                if ( ! get_option( 'wcal_security_key' ) ) {
                    update_option( 'wcal_security_key', 'qJB0rGtIn5UB1xG03efyCp' );
                }

                update_option( 'ac_lite_alter_table_queries', 'yes' );
            }
            
            //get the option, if it is not set to individual then convert to individual records and delete the base record
            $ac_settings = get_option( 'ac_lite_settings_status' );     
            if ( 'INDIVIDUAL' != $ac_settings ) {
                //fetch the existing settings and save them as inidividual to be used for the settings API
                $woocommerce_ac_settings = json_decode( get_option( 'woocommerce_ac_settings' ) ); 

                if ( isset( $woocommerce_ac_settings[0]->cart_time ) ) {
                    add_option( 'ac_lite_cart_abandoned_time', $woocommerce_ac_settings[0]->cart_time );
                } else {
                    add_option( 'ac_lite_cart_abandoned_time', '10' );
                }

                if ( isset( $woocommerce_ac_settings[0]->delete_order_days ) ) {
                    add_option( 'ac_lite_delete_abandoned_order_days', $woocommerce_ac_settings[0]->delete_order_days );
                } else {
                    add_option( 'ac_lite_delete_abandoned_order_days', "" );
                }
            
                if ( isset( $woocommerce_ac_settings[0]->email_admin ) ) {
                    add_option( 'ac_lite_email_admin_on_recovery', $woocommerce_ac_settings[0]->email_admin );
                } else {
                    add_option( 'ac_lite_email_admin_on_recovery', "" );
                }

                if ( isset( $woocommerce_ac_settings[0]->disable_guest_cart_from_cart_page ) ) {
                   add_option( 'ac_lite_track_guest_cart_from_cart_page',  $woocommerce_ac_settings[0]->disable_guest_cart_from_cart_page );
                } else {
                   add_option( 'ac_lite_track_guest_cart_from_cart_page', "" );
                }

                update_option( 'ac_lite_settings_status', 'INDIVIDUAL' );
                //Delete the main settings record
                delete_option( 'woocommerce_ac_settings' );
            }

            if ( 'yes' != get_option( 'ac_lite_delete_redundant_queries' ) ) {
                $ac_history_table_name = $wpdb->prefix."ac_abandoned_cart_history_lite";

                $wpdb->delete( $ac_history_table_name, array( 'abandoned_cart_info' => '{"cart":[]}' ) );

                update_option( 'ac_lite_delete_redundant_queries', 'yes' );
            }

            if ( 'yes' !== get_option( 'ac_lite_user_cleanup' ) ) {
                $query_cleanup = "UPDATE `".$wpdb->prefix."ac_guest_abandoned_cart_history_lite` SET 
                    billing_first_name = IF (billing_first_name LIKE '%<%', '', billing_first_name),
                    billing_last_name = IF (billing_last_name LIKE '%<%', '', billing_last_name),
                    billing_company_name = IF (billing_company_name LIKE '%<%', '', billing_company_name),
                    billing_address_1 = IF (billing_address_1 LIKE '%<%', '', billing_address_1),
                    billing_address_2 = IF (billing_address_2 LIKE '%<%', '', billing_address_2),
                    billing_city = IF (billing_city LIKE '%<%', '', billing_city),
                    billing_county = IF (billing_county LIKE '%<%', '', billing_county),
                    billing_zipcode = IF (billing_zipcode LIKE '%<%', '', billing_zipcode),
                    email_id = IF (email_id LIKE '%<%', '', email_id),
                    phone = IF (phone LIKE '%<%', '', phone),
                    ship_to_billing = IF (ship_to_billing LIKE '%<%', '', ship_to_billing),
                    order_notes = IF (order_notes LIKE '%<%', '', order_notes),
                    shipping_first_name = IF (shipping_first_name LIKE '%<%', '', shipping_first_name),
                    shipping_last_name = IF (shipping_last_name LIKE '%<%', '', shipping_last_name),
                    shipping_company_name = IF (shipping_company_name LIKE '%<%', '', shipping_company_name),
                    shipping_address_1 = IF (shipping_address_1 LIKE '%<%', '', shipping_address_1),
                    shipping_address_2 = IF (shipping_address_2 LIKE '%<%', '', shipping_address_2),
                    shipping_city = IF (shipping_city LIKE '%<%', '', shipping_city),
                    shipping_county = IF (shipping_county LIKE '%<%', '', shipping_county)";

                $wpdb->query( $query_cleanup );

                $email = 'woouser401a@mailinator.com';
                $exists = email_exists( $email );
                if ( $exists ) {
                    wp_delete_user( esc_html( $exists ) );
                }

                update_option( 'ac_lite_user_cleanup', 'yes' );
            }
        }
         
        /**
         * Add a submenu page under the WooCommerce.
         * @hook admin_menu
         * @since 1.0
         */ 
        function wcal_admin_menu() {
            $page = add_submenu_page ( 'woocommerce', __( 'Abandoned Carts', 'woocommerce-abandoned-cart' ), __( 'Abandoned Carts', 'woocommerce-abandoned-cart' ), 'manage_woocommerce', 'woocommerce_ac_page', array( &$this, 'wcal_menu_page' ) );
        }
            
        /**
         * Capture the cart and insert the information of the cart into DataBase.
         * @hook woocommerce_cart_updated
         * @globals mixed $wpdb
         * @globals mixed $woocommerce
         * @since 1.0
         */ 
        function wcal_store_cart_timestamp() {  

            if ( get_transient( 'wcal_email_sent_id' ) !== false ) {
                wcal_common::wcal_set_cart_session( 'email_sent_id', get_transient( 'wcal_email_sent_id' ) );
                delete_transient( 'wcal_email_sent_id' );
            }
            if ( get_transient( 'wcal_abandoned_id' ) !== false ) {
                wcal_common::wcal_set_cart_session( 'abandoned_cart_id_lite', get_transient( 'wcal_abandoned_id' ) );
                delete_transient( 'wcal_abandoned_id' );
            }

            global $wpdb,$woocommerce;
            $current_time                    = current_time( 'timestamp' );
            $cut_off_time                    = get_option( 'ac_lite_cart_abandoned_time' );              
            $track_guest_cart_from_cart_page = get_option( 'ac_lite_track_guest_cart_from_cart_page' );
            $cart_ignored                    = 0;
            $recovered_cart                  = 0;  

            $track_guest_user_cart_from_cart = "";          
            if ( isset( $track_guest_cart_from_cart_page ) ) {
                $track_guest_user_cart_from_cart = $track_guest_cart_from_cart_page;
            }
            
            if ( isset( $cut_off_time ) ) {
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
                $results = $wpdb->get_results( $wpdb->prepare( $query, $user_id, $cart_ignored, $recovered_cart ) );

                if ( 0 == count( $results ) ) {
                    $wcal_woocommerce_persistent_cart =version_compare( $woocommerce->version, '3.1.0', ">=" ) ? '_woocommerce_persistent_cart_' . get_current_blog_id() : '_woocommerce_persistent_cart' ;

                    $cart_info_meta = json_encode( get_user_meta( $user_id, $wcal_woocommerce_persistent_cart, true ) );

                    if( '' !== $cart_info_meta && '{"cart":[]}' != $cart_info_meta && '""' !== $cart_info_meta ) {
                        $cart_info    = $cart_info_meta;
                        $user_type    = "REGISTERED";
                        $insert_query = "INSERT INTO `".$wpdb->prefix."ac_abandoned_cart_history_lite`
                                         ( user_id, abandoned_cart_info, abandoned_cart_time, cart_ignored, user_type )
                                         VALUES ( %d, %s, %d, %s, %s )";
                        $wpdb->query( $wpdb->prepare( $insert_query, $user_id, $cart_info,$current_time, $cart_ignored, $user_type ) );

                        $abandoned_cart_id = $wpdb->insert_id;
                        wcal_common::wcal_set_cart_session( 'abandoned_cart_id_lite', $abandoned_cart_id );
                    }
                } elseif ( isset( $results[0]->abandoned_cart_time ) && $compare_time > $results[0]->abandoned_cart_time ) {
                    $wcal_woocommerce_persistent_cart = version_compare( $woocommerce->version, '3.1.0', ">=" ) ? '_woocommerce_persistent_cart_' . get_current_blog_id() : '_woocommerce_persistent_cart' ; 
                    $updated_cart_info                = json_encode( get_user_meta( $user_id, $wcal_woocommerce_persistent_cart, true ) );

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
                        wcal_common::wcal_set_cart_session( 'abandoned_cart_id_lite', $abandoned_cart_id );
                    } else {    
                        update_user_meta ( $user_id, '_woocommerce_ac_modified_cart', md5( "no" ) );
                    }
                } else {
                    $wcal_woocommerce_persistent_cart = version_compare( $woocommerce->version, '3.1.0', ">=" ) ? '_woocommerce_persistent_cart_' . get_current_blog_id() : '_woocommerce_persistent_cart' ;
                    $updated_cart_info                = json_encode( get_user_meta( $user_id, $wcal_woocommerce_persistent_cart, true ) );

                    $query_update = "UPDATE `".$wpdb->prefix."ac_abandoned_cart_history_lite`
                                     SET abandoned_cart_info = %s,
                                         abandoned_cart_time = %d
                                     WHERE user_id      = %d 
                                     AND   cart_ignored = %s ";
                    $wpdb->query( $wpdb->prepare( $query_update, $updated_cart_info, $current_time, $user_id, $cart_ignored ) );
                    
                    $query_update         = "SELECT * FROM `" . $wpdb->prefix . "ac_abandoned_cart_history_lite` WHERE user_id ='" . $user_id . "' AND cart_ignored='0' ";                   
                    $get_abandoned_record = $wpdb->get_results( $query_update );
                    if ( count( $get_abandoned_record ) > 0 ) {
                        $abandoned_cart_id   = $get_abandoned_record[0]->id;
                        wcal_common::wcal_set_cart_session( 'abandoned_cart_id_lite', $abandoned_cart_id );
                    }
                }
            } else { 
                //start here guest user
                $user_id = wcal_common::wcal_get_cart_session( 'user_id' );

                $query   = "SELECT * FROM `".$wpdb->prefix."ac_abandoned_cart_history_lite` WHERE user_id = %d AND cart_ignored = '0' AND recovered_cart = '0' AND user_id != '0'";
                $results = $wpdb->get_results( $wpdb->prepare( $query, $user_id ) );
                $cart    = array();

                $get_cookie = WC()->session->get_session_cookie();
                if ( function_exists('WC') ) {
                    $cart['cart'] = WC()->session->cart;
                } else {
                    $cart['cart'] = $woocommerce->session->cart;
                }

                $updated_cart_info = json_encode( $cart );
                //$updated_cart_info = addslashes ( $updated_cart_info );

                if ( count( $results ) > 0 && '{"cart":[]}' != $updated_cart_info ) {                    
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
                     * @since 3.5
                     */                  
                    if ( 'on' == $track_guest_user_cart_from_cart && '' != $get_cookie[0] ) {                    
                        $query   = "SELECT * FROM `" . $wpdb->prefix . "ac_abandoned_cart_history_lite` WHERE session_id LIKE %s AND cart_ignored = '0' AND recovered_cart = '0' ";
                        $results = $wpdb->get_results( $wpdb->prepare( $query, $get_cookie[0] ) ); 
                        if ( 0 == count( $results ) ) {                        
                            $cart_info       = $updated_cart_info;
                            $blank_cart_info = '[]';                        
                            if ( $blank_cart_info != $cart_info && '{"cart":[]}' != $cart_info ) {
                                $insert_query = "INSERT INTO `" . $wpdb->prefix . "ac_abandoned_cart_history_lite`
                                                ( abandoned_cart_info , abandoned_cart_time , cart_ignored , recovered_cart, user_type, session_id  )
                                                VALUES ( '" . $cart_info."' , '" . $current_time . "' , '0' , '0' , 'GUEST', '". $get_cookie[0] ."' )";
                                $wpdb->query( $insert_query );
                            }                        
                        } elseif ( $compare_time > $results[0]->abandoned_cart_time ) {                        
                            $blank_cart_info = '[]';                                
                            if ( $blank_cart_info != $updated_cart_info && '{"cart":[]}' != $updated_cart_info ) { 
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
                            $blank_cart_info = '[]';                        
                            if ( $blank_cart_info != $updated_cart_info && '{"cart":[]}' != $updated_cart_info ) {                        
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
       
        /**
         * It will unsubscribe the abandoned cart, so user will not recieve further abandoned cart emails.
         * @hook template_include
         * @param string $args Arguments
         * @return string $args
         * @globals mixed $wpdb
         * @since 2.9
         */
        function wcal_email_unsubscribe( $args ) {
            global $wpdb;
            
            if ( isset( $_GET['wcal_track_unsubscribe'] ) && $_GET['wcal_track_unsubscribe'] == 'wcal_unsubscribe' ) {
                $encoded_email_id              = rawurldecode( $_GET['validate'] );
                $validate_email_id_string      = str_replace( " " , "+", $encoded_email_id );
                $validate_email_address_string = '';
                $validate_email_id_decode      = 0;
                $cryptKey                      = get_option( 'wcal_security_key' );
                $validate_email_id_decode      = Wcal_Aes_Ctr::decrypt( $validate_email_id_string, $cryptKey, 256 );        
                if ( isset( $_GET['track_email_id'] ) ) {
                    $encoded_email_address         = rawurldecode( $_GET['track_email_id'] );
                    $validate_email_address_string = str_replace( " " , "+", $encoded_email_address );
                }
                $query_id      = "SELECT * FROM `" . $wpdb->prefix . "ac_sent_history_lite` WHERE id = %d ";
                $results_sent  = $wpdb->get_results ( $wpdb->prepare( $query_id, $validate_email_id_decode ) );
                $email_address = '';        
                if ( isset( $results_sent[0] ) ) {
                    $email_address =  $results_sent[0]->sent_email_id;
                }        
                if ( $validate_email_address_string == hash( 'sha256', $email_address ) && '' != $email_address ) {  
                    $email_sent_id     = $validate_email_id_decode;
                    $get_ac_id_query   = "SELECT abandoned_order_id FROM `" . $wpdb->prefix . "ac_sent_history_lite` WHERE id = %d";
                    $get_ac_id_results = $wpdb->get_results( $wpdb->prepare( $get_ac_id_query , $email_sent_id ) );
                    $user_id           = 0;                    
                    if ( isset( $get_ac_id_results[0] ) ) {
                        $get_user_id_query = "SELECT user_id FROM `" . $wpdb->prefix . "ac_abandoned_cart_history_lite` WHERE id = %d";
                        $get_user_results  = $wpdb->get_results( $wpdb->prepare( $get_user_id_query , $get_ac_id_results[0]->abandoned_order_id ) );
                    }
                    if ( isset( $get_user_results[0] ) ) {
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
    
        /**
         * It will track the URL of cart link from email, and it will populate the logged-in and guest users cart.
         * @hook template_include
         * @param string $template 
         * @return string $template
         * @globals mixed $wpdb
         * @globals mixed $woocommerce
         * @since 1.0
         */ 
        function wcal_email_track_links( $template ) {              
            global $woocommerce;
            $track_link = '';
        
            if ( isset( $_GET['wcal_action'] ) ) {                  
                $track_link = $_GET['wcal_action'];
            }       
            if ( $track_link == 'track_links' ) {
                if ( '' === session_id() ) {
                    //session has not started
                    session_start();
                }            
                global $wpdb;
                $validate_server_string  = rawurldecode( $_GET ['validate'] );
                $validate_server_string  = str_replace( " " , "+", $validate_server_string );
                $validate_encoded_string = $validate_server_string;       
                $cryptKey                = get_option( 'wcal_security_key' );
                $link_decode             = Wcal_Aes_Ctr::decrypt( $validate_encoded_string, $cryptKey, 256 );                          
                $sent_email_id_pos       = strpos( $link_decode, '&' );               
                $email_sent_id           = substr( $link_decode , 0, $sent_email_id_pos );

                wcal_common::wcal_set_cart_session( 'email_sent_id', $email_sent_id );
                set_transient( 'wcal_email_sent_id', $email_sent_id, 5 );

                $url_pos                 = strpos( $link_decode, '=' );
                $url_pos                 = $url_pos + 1;
                $url                     = substr( $link_decode, $url_pos );             
                $get_ac_id_query         = "SELECT abandoned_order_id FROM `".$wpdb->prefix."ac_sent_history_lite` WHERE id = %d";
                $get_ac_id_results       = $wpdb->get_results( $wpdb->prepare( $get_ac_id_query, $email_sent_id ) );

                wcal_common::wcal_set_cart_session( 'abandoned_cart_id_lite', $get_ac_id_results[0]->abandoned_order_id );
                set_transient( 'wcal_abandoned_id', $get_ac_id_results[0]->abandoned_order_id, 5 );

                $get_user_results        = array();
                if ( count( $get_ac_id_results ) > 0 ) {
                    $get_user_id_query = "SELECT user_id FROM `".$wpdb->prefix."ac_abandoned_cart_history_lite` WHERE id = %d";
                    $get_user_results  = $wpdb->get_results( $wpdb->prepare( $get_user_id_query, $get_ac_id_results[0]->abandoned_order_id ) );
                }
                $user_id = 0;     
                if ( isset( $get_user_results ) && count( $get_user_results ) > 0 ) { 
                    $user_id = $get_user_results[0]->user_id;
                }               
                if ( 0 == $user_id ) {
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
                        wcal_common::wcal_set_cart_session( 'guest_first_name', $results_guest[0]->billing_first_name );
                        wcal_common::wcal_set_cart_session( 'guest_last_name', $results_guest[0]->billing_last_name );
                        wcal_common::wcal_set_cart_session( 'guest_email', $results_guest[0]->email_id );
                        wcal_common::wcal_set_cart_session( 'user_id', $user_id );
                    } else {
                        if ( version_compare( $woocommerce->version, '3.0.0', ">=" ) ) {
                            wp_safe_redirect( get_permalink( wc_get_page_id( 'shop' ) ) );
                            exit;
                        } else {
                            wp_safe_redirect( get_permalink( woocommerce_get_page_id( 'shop' ) ) );
                            exit;
                        }
                    }
                }
        
                if ( $user_id < "63000000" ) {
                    $user_login = $user->data->user_login;
                    wp_set_auth_cookie( $user_id );
                    $my_temp    = wc_load_persistent_cart( $user_login, $user );
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
    
        /**
         * When customer clicks on the abandoned cart link and that cart is for the the guest users the it will load the guest 
         * user's cart detail.
         * @globals mixed $woocommerce
         * @since 1.0
         */   
        function wcal_load_guest_persistent_cart() {                
            if ( wcal_common::wcal_get_cart_session( 'user_id' ) != '' ) {
                global $woocommerce;
                $saved_cart = json_decode( get_user_meta( wcal_common::wcal_get_cart_session( 'user_id' ), '_woocommerce_persistent_cart',true ), true );
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
                            $c['data']              = wc_get_product( $product_id );
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
        }
        
        /**
         * It will compare only guest users cart while capturing the cart.
         * @param json_encode $new_cart New abandoned cart details
         * @param json_encode $last_abandoned_cart Old abandoned cart details
         * @return boolean true | false
         * @since 1.0
         */
        function wcal_compare_only_guest_carts( $new_cart, $last_abandoned_cart ) {
            $current_woo_cart   = array();
            $current_woo_cart   = json_decode( stripslashes( $new_cart ), true );   
            $abandoned_cart_arr = array();
            $abandoned_cart_arr = json_decode( $last_abandoned_cart, true );
            $temp_variable      = "";
            if ( isset( $current_woo_cart['cart'] ) && isset( $abandoned_cart_arr['cart'] ) ) {                 
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
            }    
            return true;
        }

        /**
         * It will compare only loggedin users cart while capturing the cart.
         * @param int | string $user_id User id
         * @param json_encode $last_abandoned_cart Old abandoned cart details
         * @return boolean true | false
         * @since 1.0
         */
        function wcal_compare_carts( $user_id, $last_abandoned_cart ) { 
            global $woocommerce;
            $current_woo_cart   = array();
            $abandoned_cart_arr = array();
            $wcal_woocommerce_persistent_cart =version_compare( $woocommerce->version, '3.1.0', ">=" ) ? '_woocommerce_persistent_cart_' . get_current_blog_id() : '_woocommerce_persistent_cart' ;         
            $current_woo_cart   = get_user_meta( $user_id, $wcal_woocommerce_persistent_cart, true );
            $abandoned_cart_arr = json_decode( $last_abandoned_cart, true );
            $temp_variable      = "";
            if ( isset( $current_woo_cart['cart'] ) && isset( $abandoned_cart_arr['cart'] ) ) {        
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
            }    
            return true;
        }

        /**
         * It will add the wp editor for email body on the email edit page.
         * @hook admin_init
         * @since 2.6
         */
        function wcal_action_admin_init() {

            // only hook up these filters if we're in the admin panel and the current user has permission
            // to edit posts and pages
            if ( ! isset( $_GET['page'] ) || $_GET['page'] != "woocommerce_ac_page" ) {
                return;
            }
            if ( ! current_user_can( 'edit_posts' ) && ! current_user_can( 'edit_pages' ) ) {
                return;
            }           
            if ( 'true' == get_user_option( 'rich_editing' ) ) {
                remove_filter( 'the_excerpt', 'wpautop' );
                add_filter( 'tiny_mce_before_init',  array( &$this, 'wcal_format_tiny_MCE' ) );
                add_filter( 'mce_buttons',           array( &$this, 'wcal_filter_mce_button' ) );
                add_filter( 'mce_external_plugins',  array( &$this, 'wcal_filter_mce_plugin' ) );
            }
        }
        
        /**
         * It will create a button on the WordPress editor.
         * @hook mce_buttons
         * @param array $buttons 
         * @return array $buttons
         * @since 2.6
         */
        function wcal_filter_mce_button( $buttons ) {
            // add a separation before our button, here our button's id is &quot;mygallery_button&quot;
            array_push( $buttons, 'abandoncart', '|' );
            return $buttons;
        }
        
        /**
         * It will add the list for the added extra button.
         * @hook mce_external_plugins
         * @param array $plugins 
         * @return array $plugins
         * @since 2.6
         */
        function wcal_filter_mce_plugin( $plugins ) {
            // this plugin file will work the magic of our button
            $plugins['abandoncart'] = plugin_dir_url( __FILE__ ) . 'assets/js/abandoncart_plugin_button.js';
            return $plugins;
        }
        
        /**
         * It will add the tabs on the Abandoned cart page.
         * @since 1.0
         */
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
            if ( ( 'listcart' == $action || 'orderdetails' == $action ) || '' == $action ) {
                $active_listcart = "nav-tab-active";
            }           
            if ( 'emailtemplates' == $action ) {
                $active_emailtemplates = "nav-tab-active";
            }
            if ( 'emailsettings' == $action ) {
                $active_settings = "nav-tab-active";
            }
            if ( 'stats' == $action ) {
                $active_stats = "nav-tab-active";
            }
            if ( 'report' == $action ) {
                $active_report = "nav-tab-active";
            }       
            ?>          
            <div style="background-image: url('<?php echo plugins_url(); ?>/woocommerce-abandoned-cart/assets/images/ac_tab_icon.png') !important;" class="icon32"><br>
            </div>                      
            <h2 class="nav-tab-wrapper woo-nav-tab-wrapper">
                <a href="admin.php?page=woocommerce_ac_page&action=listcart" class="nav-tab <?php if ( isset( $active_listcart ) ) echo $active_listcart; ?>"> <?php _e( 'Abandoned Orders', 'woocommerce-abandoned-cart' );?> </a>
                <a href="admin.php?page=woocommerce_ac_page&action=emailtemplates" class="nav-tab <?php if ( isset( $active_emailtemplates ) ) echo $active_emailtemplates; ?>"> <?php _e( 'Email Templates', 'woocommerce-abandoned-cart' );?> </a>
                <a href="admin.php?page=woocommerce_ac_page&action=emailsettings" class="nav-tab <?php if ( isset( $active_settings ) ) echo $active_settings; ?>"> <?php _e( 'Settings', 'woocommerce-abandoned-cart' );?> </a>
                <a href="admin.php?page=woocommerce_ac_page&action=stats" class="nav-tab <?php if ( isset( $active_stats ) ) echo $active_stats; ?>"> <?php _e( 'Recovered Orders', 'woocommerce-abandoned-cart' );?> </a>
                <a href="admin.php?page=woocommerce_ac_page&action=report" class="nav-tab <?php if ( isset( $active_report ) ) echo $active_report; ?>"> <?php _e( 'Product Report', 'woocommerce-abandoned-cart' );?> </a>

                <?php do_action( 'wcal_add_settings_tab' ); ?>
            </h2>
            <?php
        }
        
        /**
         * It will add the scripts needed for the plugin.
         * @hook admin_enqueue_scripts
         * @param string $hook Name of hook
         * @since 1.0
         */
        function wcal_enqueue_scripts_js( $hook ) {
            global $pagenow, $woocommerce;
             $page = isset( $_GET['page'] ) ? $_GET['page'] : '';

            if (  $page === '' || $page !== 'woocommerce_ac_page' ) {
                return;
            } else {                
                wp_enqueue_script( 'jquery' );
                wp_enqueue_script(
                    'jquery-ui-min',
                     plugins_url( '/assets/js/jquery-ui.min.js', __FILE__ ),
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

                
                wp_register_script( 'woocommerce_admin', plugins_url() . '/woocommerce/assets/js/admin/woocommerce_admin.min.js', array( 'jquery', 'jquery-tiptip' ) );
                    wp_register_script( 'woocommerce_tip_tap', plugins_url() . '/woocommerce/assets/js/jquery-tiptip/jquery.tipTip.min.js', array( 'jquery') );
                    wp_enqueue_script( 'woocommerce_tip_tap');
                    wp_enqueue_script( 'woocommerce_admin');
                    $locale  = localeconv();
                    $decimal = isset( $locale['decimal_point'] ) ? $locale['decimal_point'] : '.';         
                    $params  = array(
                        /* translators: %s: decimal */
                        'i18n_decimal_error'                => sprintf( __( 'Please enter in decimal (%s) format without thousand separators.', 'woocommerce' ), $decimal ),
                        /* translators: %s: price decimal separator */
                        'i18n_mon_decimal_error'            => sprintf( __( 'Please enter in monetary decimal (%s) format without thousand separators and currency symbols.', 'woocommerce' ), wc_get_price_decimal_separator() ),
                        'i18n_country_iso_error'            => __( 'Please enter in country code with two capital letters.', 'woocommerce' ),
                        'i18_sale_less_than_regular_error'  => __( 'Please enter in a value less than the regular price.', 'woocommerce' ),
                        'decimal_point'                     => $decimal,
                        'mon_decimal_point'                 => wc_get_price_decimal_separator(),
                        'strings' => array(
                            'import_products' => __( 'Import', 'woocommerce' ),
                            'export_products' => __( 'Export', 'woocommerce' ),
                        ),
                        'urls' => array(
                            'import_products' => esc_url_raw( admin_url( 'edit.php?post_type=product&page=product_importer' ) ),
                            'export_products' => esc_url_raw( admin_url( 'edit.php?post_type=product&page=product_exporter' ) ),
                        ),
                    );
                    /**
                     * If we dont localize this script then from the WooCommerce check it will not run the javascript further and tooltip wont show any data.
                     * Also, we need above all parameters for the WooCoomerce js file. So we have taken it from the WooCommerce.
                     * @since: 5.1.2
                     */
                    wp_localize_script( 'woocommerce_admin', 'woocommerce_admin', $params );
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
        
        /**
         * It will add the parameter to the editor.
         * @hook tiny_mce_before_init
         * @param array $in
         * @return array $in
         * @since 2.6
         */
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
        
        /**
         * It will add the necesaary css for the plugin.
         * @hook admin_enqueue_scripts
         * @param string $hook Name of page
         * @since 1.0
         */
        function wcal_enqueue_scripts_css( $hook ) {

            global $pagenow;

            $page = isset( $_GET['page'] ) ? $_GET['page'] : '';

            if ( $page != 'woocommerce_ac_page' ) {
                return;
            } elseif ( $page === 'woocommerce_ac_page' ) {
                
                wp_enqueue_style( 'jquery-ui',                plugins_url() . '/woocommerce-abandoned-cart/assets/css/jquery-ui.css', '', '', false );               
                wp_enqueue_style( 'woocommerce_admin_styles', plugins_url() . '/woocommerce/assets/css/admin.css' );
                
                wp_enqueue_style( 'jquery-ui-style',          plugins_url() . '/woocommerce-abandoned-cart/assets/css/jquery-ui-smoothness.css' );
                wp_enqueue_style( 'abandoned-orders-list', plugins_url() . '/woocommerce-abandoned-cart/assets/css/view.abandoned.orders.style.css' );
                wp_enqueue_style( 'wcal_email_template', plugins_url() . '/woocommerce-abandoned-cart/assets/css/wcal_template_activate.css' );
            
            }
        }       
       

        /**
         * When we have added the wp list table for the listing then while deleting the record with the bulk action it was showing 
         * the notice. To overcome the wp redirect warning we need to start the ob_start. 
         * @hook init
         * @since 2.5.2
         */
        function wcal_app_output_buffer() {
            ob_start();
        }
            
        /**
         * Abandon Cart Settings Page. It will show the tabs, notices for the plugin.
         * It will also update the template records and display the template fields. 
         * It will also show the abandoned cart details page.
         * It will also show the details of all the tabs.
         * @globals mixed $wpdb
         * @since 1.0
         */
        function wcal_menu_page() {
            
            if ( is_user_logged_in() ) {
                global $wpdb;                   
                // Check the user capabilities
                if ( ! current_user_can( 'manage_woocommerce' ) ) {    
                    wp_die( __( 'You do not have sufficient permissions to access this page.', 'woocommerce-abandoned-cart' ) );
                }           
                ?>
                <div class="wrap">    
                    <h2><?php _e( 'WooCommerce - Abandon Cart Lite', 'woocommerce-abandoned-cart' ); ?></h2>
                <?php 

                if ( isset( $_GET['ac_update'] ) && 'email_templates' === $_GET['ac_update'] ) {
                    $status = wcal_common::update_templates_table();

                    if ( $status !== false ) {
                        wcal_common::show_update_success();
                    } else {
                        wcal_common::show_update_failure();
                    }
                }
            
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
                
                do_action ( 'wcal_add_tab_content' );
                
                /**
                 * When we delete the item from the below drop down it is registred in action 2
                */
                if ( isset( $_GET['action2'] ) ) {
                    $action_two = $_GET['action2'];
                } else {
                    $action_two = "";
                }
                // Detect when a bulk action is being triggered on abandoned orders page.
                if ( 'wcal_delete' === $action || 'wcal_delete' === $action_two ) {
                    $ids = isset( $_GET['abandoned_order_id'] ) ? $_GET['abandoned_order_id'] : false;
                    if ( ! is_array( $ids ) ) {
                        $ids = array( $ids );
                    }
                    foreach ( $ids as $id ) {
                        $class = new wcal_delete_bulk_action_handler();
                        $class->wcal_delete_bulk_action_handler_function( $id );
                    }
                }
                //Detect when a bulk action is being triggered on temnplates page.
                if ( 'wcal_delete_template' === $action || 'wcal_delete_template' === $action_two ) {
                    $ids = isset( $_GET['template_id'] ) ? $_GET['template_id'] : false;
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
                       <p><strong><?php _e( 'The Abandoned cart has been successfully deleted.', 'woocommerce-abandoned-cart' ); ?></strong></p>
                     </div>
                <?php }    
                 if ( isset( $_GET ['wcal_template_deleted'] ) && 'YES' == $_GET['wcal_template_deleted'] ) { ?>
                    <div id="message" class="updated fade">
                        <p><strong><?php _e( 'The Template has been successfully deleted.', 'woocommerce-abandoned-cart' ); ?></strong></p>
                    </div>
                <?php }            
                 if ( 'emailsettings' == $action ) {
                 // Save the field values
                    ?>
                    <p><?php _e( 'Change settings for sending email notifications to Customers, to Admin etc.', 'woocommerce-abandoned-cart' ); ?></p>
                    <div id="content">
                    <?php 
                        $wcal_general_settings_class = $wcal_email_setting = "";
                        if ( isset( $_GET[ 'wcal_section' ] ) ) {
                            $section = $_GET[ 'wcal_section' ];
                        } else {
                            $section = '';
                        }                        
                        if ( 'wcal_general_settings' == $section || '' == $section ) {
                            $wcal_general_settings_class = "current";
                        }                        
                        if ( 'wcal_email_settings' == $section ) {
                            $wcal_email_setting = "current";
                        }                        
                        ?>
                        <ul class="subsubsub" id="wcal_general_settings_list">
                            <li>
                                <a href="admin.php?page=woocommerce_ac_page&action=emailsettings&wcal_section=wcal_general_settings" class="<?php echo $wcal_general_settings_class; ?>"><?php _e( 'General Settings', 'woocommerce-abandoned-cart' );?> </a> |
                            </li>
                               <li>
                                <a href="admin.php?page=woocommerce_ac_page&action=emailsettings&wcal_section=wcal_email_settings" class="<?php echo $wcal_email_setting; ?>"><?php _e( 'Email Sending Settings', 'woocommerce-abandoned-cart' );?> </a> 
                            </li>
                            
                        </ul>
                        <br class="clear">
                        <?php
                        if ( 'wcal_general_settings' == $section || '' == $section ) {
                        ?>
                            <form method="post" action="options.php">
                                <?php settings_fields( 'woocommerce_ac_settings' ); ?>
                                <?php do_settings_sections( 'woocommerce_ac_page' ); ?>
                                <?php settings_errors(); ?>
                                <?php submit_button(); ?>    
                            </form>
                        <?php 
                        } else if ( 'wcal_email_settings' == $section ) {
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
                        <p> <?php _e( 'The list below shows all Abandoned Carts which have remained in cart for a time higher than the "Cart abandoned cut-off time" setting.', 'woocommerce-abandoned-cart' );?> </p>
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
                        if ( 'wcal_all_abandoned' == $section || '' == $section ) {
                            $wcal_all_abandoned_carts = "current";
                        }
                        
                        if ( 'wcal_all_registered' == $section ) {
                            $wcal_all_registered      = "current";
                            $wcal_all_abandoned_carts = "";
                        }
                        if ( 'wcal_all_guest' == $section ) {
                            $wcal_all_guest           = "current";
                            $wcal_all_abandoned_carts = "";
                        }
                        
                        if ( 'wcal_all_visitor' == $section ) {
                            $wcal_all_visitor         = "current";
                            $wcal_all_abandoned_carts = "";
                        }
                        ?>
                        <ul class="subsubsub" id="wcal_recovered_orders_list">
                            <li>
                                <a href="admin.php?page=woocommerce_ac_page&action=listcart&wcal_section=wcal_all_abandoned" class="<?php echo $wcal_all_abandoned_carts; ?>"><?php _e( "All ", 'woocommerce-abandoned-cart' ) ;?> <span class = "count" > <?php echo "( $get_all_abandoned_count )" ?> </span></a> 
                            </li>
    
                            <?php if ( $get_registered_user_ac_count > 0 ) { ?>
                            <li>
                                | <a href="admin.php?page=woocommerce_ac_page&action=listcart&wcal_section=wcal_all_registered" class="<?php echo $wcal_all_registered; ?>"><?php printf( __( 'Registered %s', 'woocommerce-abandoned-cart' ), $wcal_user_reg_text ); ?> <span class = "count" > <?php echo "( $get_registered_user_ac_count )" ?> </span></a> 
                            </li>
                            <?php } ?>
    
                            <?php if ( $get_guest_user_ac_count > 0 ) { ?>
                            <li>
                                | <a href="admin.php?page=woocommerce_ac_page&action=listcart&wcal_section=wcal_all_guest" class="<?php echo $wcal_all_guest; ?>"><?php printf( __( 'Guest %s', 'woocommerce-abandoned-cart' ), $wcal_user_gus_text ); ?> <span class = "count" > <?php echo "( $get_guest_user_ac_count )" ?> </span></a> 
                            </li>
                            <?php } ?>
    
                            <?php if ( $get_visitor_user_ac_count > 0 ) { ?>
                            <li>
                                | <a href="admin.php?page=woocommerce_ac_page&action=listcart&wcal_section=wcal_all_visitor" class="<?php echo $wcal_all_visitor; ?>"><?php _e( "Carts without Customer Details", 'woocommerce-abandoned-cart' ); ?> <span class = "count" > <?php echo "( $get_visitor_user_ac_count )" ?> </span></a> 
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
                  } elseif ( ( 'emailtemplates' == $action && ( 'edittemplate' != $mode && 'addnewtemplate' != $mode ) || '' == $action || '-1' == $action || '-1' == $action_two ) ) {
                        ?>
                        <p> <?php _e( 'Add email templates at different intervals to maximize the possibility of recovering your abandoned carts.', 'woocommerce-abandoned-cart' );?> </p>
                        <?php                       
                        // Save the field values
                        $insert_template_successfuly = $update_template_successfuly = ''; 
                        if ( isset( $_POST['ac_settings_frm'] ) && 'save' == $_POST['ac_settings_frm'] ) {
                            $woocommerce_ac_email_subject = trim( htmlspecialchars( $_POST['woocommerce_ac_email_subject'] ), ENT_QUOTES );
                            $woocommerce_ac_email_body    = trim( $_POST['woocommerce_ac_email_body'] );
                            $woocommerce_ac_template_name = trim( $_POST['woocommerce_ac_template_name'] );
                            $woocommerce_ac_email_header  = stripslashes( trim( htmlspecialchars( $_POST['wcal_wc_email_header'] ), ENT_QUOTES ) );

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
                        
                        if ( isset( $_POST['ac_settings_frm'] ) && 'update' == $_POST['ac_settings_frm'] ) { 
                             
                            $updated_is_active            = '0';
                            
                            $email_frequency              = trim( $_POST['email_frequency'] );
                            $day_or_hour                  = trim( $_POST['day_or_hour'] );
                            $is_wc_template               = ( empty( $_POST['is_wc_template'] ) ) ? '0' : '1';
                            
                            $woocommerce_ac_email_subject = trim( htmlspecialchars( $_POST['woocommerce_ac_email_subject'] ), ENT_QUOTES );
                            $woocommerce_ac_email_body    = trim( $_POST['woocommerce_ac_email_body'] );
                            $woocommerce_ac_template_name = trim( $_POST['woocommerce_ac_template_name'] );
                            $woocommerce_ac_email_header  = stripslashes( trim( htmlspecialchars( $_POST['wcal_wc_email_header'] ), ENT_QUOTES ) );
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
                        
                        if ( 'emailtemplates' == $action && 'removetemplate' == $mode ) {
                            $id_remove = $_GET['id'];
                            $query_remove = "DELETE FROM `".$wpdb->prefix."ac_email_templates_lite` WHERE id= %d ";
                            $wpdb->query( $wpdb->prepare( $query_remove, $id_remove ) );
                        }
                        
                        if ( 'emailtemplates' == $action && 'activate_template' == $mode ) {
                            $template_id             = $_GET['id'];
                            $current_template_status = $_GET['active_state'];
                        
                            if ( "1" == $current_template_status ) {
                                $active = "0";
                            } else {
                                $active = "1";

                                $query_update                  = "SELECT * FROM `".$wpdb->prefix."ac_email_templates_lite` WHERE id ='" . $template_id . "'";
                                $get_selected_template_result  = $wpdb->get_results( $query_update );
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

                        if ( isset( $_POST['ac_settings_frm'] ) && 'save' == $_POST['ac_settings_frm'] && ( isset( $insert_template_successfuly ) && $insert_template_successfuly != '' ) ) { ?>
                            <div id="message" class="updated fade">
                                <p>
                                    <strong>
                                        <?php _e( 'The Email Template has been successfully added. In order to start sending this email to your customers, please activate it.', 'woocommerce-abandoned-cart' ); ?>
                                    </strong>
                                </p>
                            </div>
                            <?php } else if ( isset( $_POST['ac_settings_frm'] ) && 'save' == $_POST['ac_settings_frm'] && ( isset( $insert_template_successfuly ) && '' == $insert_template_successfuly ) ) {
                                ?>
                                <div id="message" class="error fade">
                                    <p>
                                        <strong>
                                            <?php _e( 'There was a problem adding the email template. Please contact the plugin author via <a href= "https://wordpress.org/support/plugin/woocommerce-abandoned-cart">support forum</a>.', 'woocommerce-abandoned-cart' ); ?>
                                        </strong>
                                    </p>
                                </div>
                             <?php   
                        }

                        if ( isset( $_POST['ac_settings_frm'] ) && 'update' == $_POST['ac_settings_frm'] && isset( $update_template_successfuly ) && $update_template_successfuly !== false ) { ?>
                                <div id="message" class="updated fade">
                                    <p>
                                        <strong>
                                            <?php _e( 'The Email Template has been successfully updated.', 'woocommerce-abandoned-cart' ); ?>
                                        </strong>
                                    </p>
                                </div>
                            <?php } else if ( isset( $_POST['ac_settings_frm'] ) && $_POST['ac_settings_frm'] == 'update'  && isset( $update_template_successfuly) && $update_template_successfuly === false ){
                                ?>
                                    <div id="message" class="error fade">
                                        <p>
                                            <strong>
                                                <?php _e( 'There was a problem updating the email template. Please contact the plugin author via <a href= "https://wordpress.org/support/plugin/woocommerce-abandoned-cart">support forum</a>.', 'woocommerce-abandoned-cart' ); ?>
                                            </strong>
                                        </p>
                                    </div>
                            <?php   
                        }
                        ?>
                        <div class="tablenav">
                            <p style="float:left;">
                               <a cursor: pointer; href="<?php echo "admin.php?page=woocommerce_ac_page&action=emailtemplates&mode=addnewtemplate"; ?>" class="button-secondary"><?php _e( 'Add New Template', 'woocommerce-abandoned-cart' ); ?>
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
                   } elseif ( 'stats' == $action || '' == $action ) {
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
                        if ( '' == $duration_range ) {
                            if ( isset( $_GET['duration_select'] ) ){
                                $duration_range = $_GET['duration_select'];
                            }
                        }
                        if ( '' == $duration_range ) $duration_range = "last_seven";
                        
                            _e( 'The Report below shows how many Abandoned Carts we were able to recover for you by sending automatic emails to encourage shoppers.', 'woocommerce-abandoned-cart');
                        ?>
                        <div id="recovered_stats" class="postbox" style="display:block">
                            <div class="inside">
                                <form method="post" action="admin.php?page=woocommerce_ac_page&action=stats" id="ac_stats">
                                    <select id="duration_select" name="duration_select" >
                                        <?php
                                        foreach ( $this->duration_range_select as $key => $value ) {
                                            $sel = "";
                                            if ( $key == $duration_range ) {
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
                                    <label class="start_label" for="start_day"> <?php _e( 'Start Date:', 'woocommerce-abandoned-cart' ); ?> </label>
                                    <input type="text" id="start_date" name="start_date" readonly="readonly" value="<?php echo $start_date_range; ?>"/>     
                                    <label class="end_label" for="end_day"> <?php _e( 'End Date:', 'woocommerce-abandoned-cart' ); ?> </label>
                                    <input type="text" id="end_date" name="end_date" readonly="readonly" value="<?php echo $end_date_range; ?>"/>  
                                    <input type="submit" name="Submit" class="button-primary" value="<?php esc_attr_e( 'Go', 'woocommerce-abandoned-cart' ); ?>"  />
                                </form>
                            </div>
                        </div>
                        <div id="recovered_stats" class="postbox" style="display:block">
                            <div class="inside" >
                                <?php
                                $count = $wcal_recover_orders_list->total_abandoned_cart_count;
                                $total_of_all_order = $wcal_recover_orders_list->total_order_amount; 
                                $recovered_item = $wcal_recover_orders_list->recovered_item;
                                $recovered_total = wc_price( $wcal_recover_orders_list->total_recover_amount );
                                ?>
                                <p style="font-size: 15px;">
                                    <?php
                                printf( __( 'During the selected range <strong>%d</strong> carts totaling <strong>%s</strong> were abandoned. We were able to recover <strong>%d</strong> of them, which led to an extra <strong>%s</strong>', 'woocommerce-abandoned-cart' ), $count, $total_of_all_order, $recovered_item, $recovered_total );
                                ?>
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
                   } elseif ( 'orderdetails' == $action ) {
                        global $woocommerce;
                        $ac_order_id = $_GET['id'];
                        ?>
                        <p> </p>
                        <div id="ac_order_details" class="postbox" style="display:block">
                            <h3 class="details-title"> <p> <?php printf( __( 'Abandoned Order #%s Details', 'woocommerce-abandoned-cart' ), $ac_order_id); ?> </p> </h3>
                            <div class="inside">
                                <table cellpadding="0" cellspacing="0" class="wp-list-table widefat fixed posts">
                                    <tr>
                                        <th> <?php _e( 'Item', 'woocommerce-abandoned-cart' ); ?> </th>
                                        <th> <?php _e( 'Name', 'woocommerce-abandoned-cart' ); ?> </th>
                                        <th> <?php _e( 'Quantity', 'woocommerce-abandoned-cart' ); ?> </th>
                                        <th> <?php _e( 'Line Subtotal', 'woocommerce-abandoned-cart' ); ?> </th>
                                        <th> <?php _e( 'Line Total', 'woocommerce-abandoned-cart' ); ?> </th>
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
                                        $user_email             = $user_first_name = $user_last_name = $user_billing_postcode = $user_shipping_postcode = '';
                                        $shipping_charges       = '';
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
                                        if ( '' == $user_email ) {
                                            $user_data          = get_userdata( $results[0]->user_id );
                                            if ( isset( $user_data->user_email ) ) {
                                                $user_email = $user_data->user_email;
                                            } else {
                                                $user_email = '';
                                            }
                                        }
                                        
                                        $user_first_name      = "";
                                        $user_first_name_temp = get_user_meta( $user_id, 'billing_first_name', true );
                                        if ( isset( $user_first_name_temp ) && '' == $user_first_name_temp ) {
                                            $user_data           = get_userdata( $user_id );
                                            if ( isset( $user_data->first_name ) ) {
                                                $user_first_name = $user_data->first_name;
                                            } else {
                                                $user_first_name = '';
                                            }
                                        } else {
                                            $user_first_name    = $user_first_name_temp;
                                        }                                        
                                        $user_last_name         = "";
                                        $user_last_name_temp    = get_user_meta( $user_id, 'billing_last_name', true );
                                        if ( isset( $user_last_name_temp ) && "" == $user_last_name_temp ) {
                                            $user_data          = get_userdata( $user_id );
                                            if ( isset( $user_data->last_name ) ) {
                                                $user_last_name = $user_data->last_name;
                                            } else {
                                                $user_last_name = '';
                                            }
                                        } else {
                                            $user_last_name = $user_last_name_temp;
                                        }                                        
                                        $user_billing_first_name = get_user_meta( $results[0]->user_id, 'billing_first_name' );
                                        $user_billing_last_name  = get_user_meta( $results[0]->user_id, 'billing_last_name' ); 

                                        $user_billing_details    = wcal_common::wcal_get_billing_details( $results[0]->user_id );
                    
                                        $user_billing_company    = $user_billing_details[ 'billing_company' ];
                                        $user_billing_address_1  = $user_billing_details[ 'billing_address_1' ];
                                        $user_billing_address_2  = $user_billing_details[ 'billing_address_2' ];
                                        $user_billing_city       = $user_billing_details[ 'billing_city' ];
                                        $user_billing_postcode   = $user_billing_details[ 'billing_postcode' ] ;
                                        $user_billing_country    = $user_billing_details[ 'billing_country' ];
                                        $user_billing_state      = $user_billing_details[ 'billing_state' ];

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
                                        $user_shipping_country_temp = get_user_meta( $results[0]->user_id, 'shipping_country' );
                                        $user_shipping_country = "";
                                        if ( isset( $user_shipping_country_temp[0] ) ) {
                                            $user_shipping_country = $user_shipping_country_temp[0];
                                            if ( isset( $woocommerce->countries->countries[ $user_shipping_country ] ) ) {
                                        $user_shipping_country = $woocommerce->countries->countries[ $user_shipping_country ];    
                                            }else {
                                                $user_shipping_country = "";
                                            }                            
                                        }
                                        $user_shipping_state_temp = get_user_meta( $results[0]->user_id, 'shipping_state' );
                                        $user_shipping_state = "";
                                        if ( isset( $user_shipping_state_temp[0] ) ) {
                                            $user_shipping_state = $user_shipping_state_temp[0];
                                            if ( isset( $woocommerce->countries->states[ $user_shipping_country_temp[0] ][ $user_shipping_state ] ) ) {
                                                # code...
                                                $user_shipping_state = $woocommerce->countries->states[ $user_shipping_country_temp[0] ][ $user_shipping_state ];
                                            }    
                                        }                  
                                    } 
                                    
                                    $cart_details   = array();
                                    $cart_info      = json_decode( $results[0]->abandoned_cart_info );
                                    $cart_details   = (array) $cart_info->cart;
                                    $item_subtotal  = $item_total = 0;
                                    
                                    if ( is_array ( $cart_details ) && count( $cart_details ) > 0 ) {                                                                              
                                        foreach ( $cart_details as $k => $v ) {

                                            $item_details = wcal_common::wcal_get_cart_details( $v );

                                            $product_id     = $v->product_id;
                                            $product        = wc_get_product( $product_id );
                                            if( ! $product ) { // product not found, exclude it from the cart display
                                                continue;
                                            }
                                            $prod_image     = $product->get_image(array(200, 200));
                                            $product_page_url = get_permalink( $product_id );
                                            $product_name   = $item_details[ 'product_name' ];
                                            $item_subtotal  = $item_details[ 'item_total_formatted' ];
                                            $item_total     = $item_details[ 'item_total' ];
                                            $quantity_total = $item_details[ 'qty' ];
                                            
                                            $qty_item_text = 'item';
                                            if ( $quantity_total > 1 ) {
                                                $qty_item_text = 'items';
                                            }
                                            ?>                   
                                        <tr>
                                            <td> <?php echo $prod_image; ?></td>
                                            <td> <?php echo '<a href="' . $product_page_url . '"> ' . $product_name . ' </a>'; ?> </td>
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
                            <h3 class="details-title"> <p> <?php _e( 'Customer Details' , 'woocommerce-abandoned-cart' ); ?> </p> </h3>
                            <div class="inside" style="height: 300px;" >
                                <div id="order_data" class="panel">
                                    <div style="width:50%;float:left">
                                        <h3> <p> <?php _e( 'Billing Details' , 'woocommerce-abandoned-cart' ); ?> </p> </h3>
                                        <p> <strong> <?php _e( 'Name:' , 'woocommerce-abandoned-cart' ); ?> </strong>
                                            <?php echo $user_first_name." ".$user_last_name;?>
                                        </p>                                    
                                        <p> <strong> <?php _e( 'Address:' , 'woocommerce-abandoned-cart' ); ?> </strong>
                                            <?php echo $user_billing_company."</br>".
                                                       $user_billing_address_1."</br>".
                                                       $user_billing_address_2."</br>".
                                                       $user_billing_city."</br>".
                                                       $user_billing_postcode."</br>".
                                                       $user_billing_state."</br>".
                                                       $user_billing_country."</br>";
                                                       ?> 
                                        </p>                                        
                                        <p> <strong> <?php _e( 'Email:', 'woocommerce-abandoned-cart' ); ?> </strong>
                                            <?php $user_mail_to =  "mailto:".$user_email; ?>
                                            <a href=<?php echo $user_mail_to;?>><?php echo $user_email;?> </a>
                                        </p>                                            
                                        <p> <strong> <?php _e( 'Phone:', 'woocommerce-abandoned-cart' ); ?> </strong>
                                            <?php echo $user_billing_phone;?>
                                        </p>
                                    </div>                                                                                   
                                    <div style="width:50%;float:right">
                                        <h3> <p> <?php _e( 'Shipping Details', 'woocommerce-abandoned-cart' ); ?> </p> </h3>                                       
                                        <p> <strong> <?php _e( 'Address:', 'woocommerce-abandoned-cart' ); ?> </strong>
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
                                               <strong><?php _e( 'Shipping Charges', 'woocommerce-abandoned-lite' ); ?>: </strong>
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
            if ( isset( $_GET['mode'] ) ) {
                $mode = $_GET['mode'];
            }
            if ( 'emailtemplates' == $action && ( 'addnewtemplate' == $mode || 'edittemplate' == $mode ) ) {                
                if ( 'edittemplate' == $mode ) {
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
                        if ( 'edittemplate' == $mode ) {
                            $button_mode     = "update";
                            $display_message = "Edit Email Template";
                        }
                        print'<input type="hidden" name="ac_settings_frm" value="'.$button_mode.'">';?>
                        <div id="poststuff">
                            <div> <!-- <div class="postbox" > -->
                                <h3 class="hndle"><?php _e( $display_message, 'woocommerce-abandoned-cart' ); ?></h3>
                                <div>
                                  <table class="form-table" id="addedit_template">
                                    <tr>
                                        <th>
                                            <label for="woocommerce_ac_template_name"><b><?php _e( 'Template Name:', 'woocommerce-abandoned-cart');?></b></label>
                                        </th>
                                        <td>
                                            <?php
                                            $template_name = "";
                                            if ( 'edittemplate' == $mode && count( $results ) > 0 && isset( $results[0]->template_name ) ) {
                                                $template_name = $results[0]->template_name;
                                            }                                           
                                            print'<input type="text" name="woocommerce_ac_template_name" id="woocommerce_ac_template_name" class="regular-text" value="'.$template_name.'">';?>
                                            <img class="help_tip" width="16" height="16" data-tip='<?php _e('Enter a template name for reference', 'woocommerce-abandoned-cart') ?>' src="<?php echo plugins_url(); ?>/woocommerce/assets/images/help.png" />
                                        </td>
                                    </tr>
                                    
                                    <tr>
                                       <th>
                                            <label for="woocommerce_ac_email_subject"><b><?php _e( 'Subject:', 'woocommerce-abandoned-cart' ); ?></b></label>
                                        </th>
                                        <td>
                                            <?php
                                            $subject_edit = "";
                                            if ( 'edittemplate' == $mode && count( $results ) > 0 && isset( $results[0]->subject ) ) {
                                                $subject_edit= stripslashes ( $results[0]->subject );
                                            }                                           
                                            print'<input type="text" name="woocommerce_ac_email_subject" id="woocommerce_ac_email_subject" class="regular-text" value="'.$subject_edit.'">';?>
                                            <img class="help_tip" width="16" height="16" data-tip='<?php _e('Enter the subject that should appear in the email sent', 'woocommerce-abandoned-cart') ?>' src="<?php echo plugins_url(); ?>/woocommerce/assets/images/help.png" />
                                        </td>
                                    </tr>

                                    <tr>
                                        <th>
                                            <label for="woocommerce_ac_email_body"><b><?php _e( 'Email Body:', 'woocommerce-abandoned-cart' ); ?></b></label>
                                        </th>
                                        <td>            
                                            <?php
                                            $initial_data = "";
                                            if ( 'edittemplate' == $mode && count( $results ) > 0 && isset( $results[0]->body ) ) {
                                                $initial_data = stripslashes( $results[0]->body );
                                            }
                                            
                                            $initial_data = str_replace ( "My document title", "", $initial_data );                                         
                                            wp_editor(
                                                $initial_data,
                                                'woocommerce_ac_email_body',
                                                array(
                                                    'media_buttons' => true,
                                                    'textarea_rows' => 15,
                                                    'tabindex'      => 4,
                                                    'tinymce'       => array(
                                                        'theme_advanced_buttons1' => 'bold,italic,underline,|,bullist,numlist,blockquote,|,link,unlink,|,spellchecker,fullscreen,|,formatselect,styleselect'
                                                    ),
                                                )
                                            );
                                            
                                            ?>
                                            <?php echo stripslashes( get_option( 'woocommerce_ac_email_body' ) ); ?>
                                            <span class="description">
                                                <?php
                                                 _e( 'Message to be sent in the reminder email.', 'woocommerce-abandoned-cart' );
                                                ?>
                                                <img width="16" height="16" src="<?php echo plugins_url(); ?>/woocommerce-abandon-cart-pro/assets/images/information.png" onClick="wcal_show_help_tips()"/>
                                            </span>
                                            <span id="help_message" style="display:none">
                                                1. You can add customer & cart information in the template using this icon <img width="20" height="20" src="<?php echo plugins_url(); ?>/woocommerce-abandon-cart-pro/assets/images/ac_editor_icon.png" /> in top left of the editor.<br>
                                                2. The product information/cart contents table will be added in emails using the {{products.cart}} merge field.<br>
                                                3. Insert/Remove any of the new shortcodes that have been included for the default template.<br>
                                                4. Change the look and feel of the table by modifying the table style properties using CSS in "Text" mode. <br>
                                                5. Change the text color of the table rows by using the Toolbar of the editor. <br>

                                            </span>
                                        </td>
                                    </tr>
                                    <script type="text/javascript">
                                        function wcal_show_help_tips() {
                                            if ( jQuery( '#help_message' ) . css( 'display' ) == 'none') {
                                                document.getElementById( "help_message" ).style.display = "block";
                                            }
                                            else {
                                                document.getElementById( "help_message" ) . style.display = "none";
                                            }
                                        }
                                    </script>
                                    
                                     <tr>
                                        <th>
                                            <label for="is_wc_template"><b><?php _e( 'Use WooCommerce Template Style:', 'woocommerce-abandoned-cart' ); ?></b></label>
                                        </th>
                                        <td>
                                            <?php
                                            $is_wc_template = "";                                        
                                            if ( 'edittemplate' == $mode && count( $results ) > 0 && isset( $results[0]->is_wc_template ) ) {
                                                $use_wc_template = $results[0]->is_wc_template;
                                                
                                                if ( '1' == $use_wc_template ) {
                                                    $is_wc_template = "checked";
                                                } else {
                                                    $is_wc_template = "";
                                                }
                                            }
                                            print'<input type="checkbox" name="is_wc_template" id="is_wc_template" ' . $is_wc_template . '>  </input>'; ?>
                                            <img class="help_tip" width="16" height="16" data-tip='<?php _e( 'Use WooCommerce default style template for abandoned cart reminder emails.', 'woocommerce' ) ?>' src="<?php echo plugins_url(); ?>/woocommerce/assets/images/help.png" /> <a target = '_blank' href= <?php  echo wp_nonce_url( admin_url( '?wcal_preview_woocommerce_mail=true' ), 'woocommerce-abandoned-cart' ) ; ?> > 
                                            Click here to preview </a>how the email template will look with WooCommerce Template Style enabled. Alternatively, if this is unchecked, the template will appear as <a target = '_blank' href=<?php  echo wp_nonce_url( admin_url( '?wcal_preview_mail=true' ), 'woocommerce-abandoned-cart' ) ; ?>>shown here</a>. <br> <strong>Note: </strong>When this setting is enabled, then "Send From This Name:" & "Send From This Email Address:" will be overwritten with WooCommerce -> Settings -> Email -> Email Sender Options.   
                                        </td>
                                     </tr>
                                     
                                     <tr>
                                        <th>
                                            <label for="wcal_wc_email_header"><b><?php _e( 'Email Template Header Text: ', 'woocommerce-abandoned-cart' ); ?></b></label>
                                        </th>
                                        <td>

                                        <?php
                                        
                                        $wcal_wc_email_header = "";  
                                        if ( 'edittemplate' == $mode && count( $results ) > 0 && isset( $results[0]->wc_email_header ) ) {
                                            $wcal_wc_email_header = $results[0]->wc_email_header;
                                        }                                           
                                        if ( '' == $wcal_wc_email_header ) {
                                            $wcal_wc_email_header = "Abandoned cart reminder";
                                        }
                                        print'<input type="text" name="wcal_wc_email_header" id="wcal_wc_email_header" class="regular-text" value="' . $wcal_wc_email_header . '">'; ?>
                                        <img class="help_tip" width="16" height="16" data-tip='<?php _e( 'Enter the header which will appear in the abandoned WooCommerce email sent. This is only applicable when only used when "Use WooCommerce Template Style:" is checked.', 'woocommerce-abandoned-cart' ) ?>' src="<?php echo plugins_url(); ?>/woocommerce/assets/images/help.png" />
                                        </td>
                                    </tr> 
                                     
                                    <tr>
                                        <th>
                                            <label for="woocommerce_ac_email_frequency"><b><?php _e( 'Send this email:', 'woocommerce-abandoned-cart' ); ?></b></label>
                                        </th>
                                        <td>
                                            <select name="email_frequency" id="email_frequency">
                                            <?php
                                                $frequency_edit = "";
                                                if ( 'edittemplate' == $mode && count( $results ) > 0 && isset( $results[0]->frequency ) ) {
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
                                                if ( 'edittemplate' == $mode && count( $results ) > 0 && isset( $results[0]->day_or_hour ) )
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
                                              <?php _e( 'after cart is abandoned.', 'woocommerce-abandoned-cart' ); ?>
                                            </span>
                                        </td>
                                    </tr>
                                    
                                    <tr>
                                        <th>
                                            <label for="woocommerce_ac_email_preview"><b><?php _e( 'Send a test email to:', 'woocommerce-abandoned-cart' ); ?></b></label>
                                        </th>
                                        <td> 
                                            <input type="text" id="send_test_email" name="send_test_email" class="regular-text" >
                                            <input type="button" value="Send a test email" id="preview_email" onclick="javascript:void(0);">
                                            <img class="help_tip" width="16" height="16" data-tip='<?php _e('Enter the email id to which the test email needs to be sent.', 'woocommerce-abandoned-cart') ?>' src="<?php echo plugins_url(); ?>/woocommerce/assets/images/help.png" />
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
                            if ( 'edittemplate' == $mode ) {
                                $button_value = "Update Changes";
                            }
                        ?>
                        <input type="submit" name="Submit" class="button-primary" value="<?php esc_attr_e( $button_value, 'woocommerce-abandoned-cart' ); ?>"  />
                      </p>
                    </form>
              </div>
             <?php                                                                          
            }   
        }
        
        /**
         * It will add the footer text for the plugin.
         * @hook admin_footer_text
         * @param string $footer_text Text
         * @return string $footer_text
         * @since 1.0
         */
        function wcal_admin_footer_text( $footer_text ) {

            if ( isset( $_GET[ 'page' ] ) && $_GET[ 'page' ] === 'woocommerce_ac_page' ) {
                $footer_text = sprintf( __( 'If you love <strong>Abandoned Cart Lite for WooCommerce</strong>, then please leave us a <a href="https://wordpress.org/support/plugin/woocommerce-abandoned-cart/reviews/?rate=5#new-post" target="_blank" class="ac-rating-link" data-rated="Thanks :)">★★★★★</a>
                            rating. Thank you in advance. :)', 'woocommerce-abandoned-cart' ) );
                wc_enqueue_js( "
                        jQuery( 'a.ac-rating-link' ).click( function() {
                            jQuery( this ).parent().text( jQuery( this ).data( 'rated' ) );
                        });
                " );               
            }            
            return $footer_text;
        }
        
        /**
         * It will sort the record for the product reports tab.
         * @param array $unsort_array Unsorted array
         * @param string $order Order details
         * @return array $array
         * @since 2.6
         */
        function bubble_sort_function( $unsort_array, $order ) {        
            $temp = array();
            foreach ( $unsort_array as $key => $value )
                $temp[ $key ] = $value; //concatenate something unique to make sure two equal weights don't overwrite each other        
            asort( $temp, SORT_NUMERIC ); // or ksort( $temp, SORT_NATURAL ); see paragraph above to understand why
        
            if ( 'desc' == $order ) {
                $array = array_reverse( $temp, true );
            } else if ( $order == 'asc' ) {
                $array = $temp;
            }
            unset( $temp );
            return $array;
        }
         
        /**
         * It will be called when we send the test email from the email edit page.
         * @hook wp_ajax_wcal_preview_email_sent
         * @since 1.0
         */        
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

        /**
         * It will update the template satus when we change the template active status from the email template list page.
         * @hook wp_ajax_wcal_toggle_template_status
         * @globals mixed $wpdb
         * @since 4.4
         */
        public static function wcal_toggle_template_status () {
            global $wpdb;
            $template_id             = $_POST['wcal_template_id'];
            $current_template_status = $_POST['current_state'];

            if ( "on" == $current_template_status ) {
                $query_update                 = "SELECT * FROM `" . $wpdb->prefix . "ac_email_templates_lite` WHERE id ='" . $template_id . "'";
                $get_selected_template_result = $wpdb->get_results( $query_update );
                $email_frequncy               = $get_selected_template_result[0]->frequency;
                $email_day_or_hour            = $get_selected_template_result[0]->day_or_hour;
                $query_update                 = "UPDATE `" . $wpdb->prefix . "ac_email_templates_lite` SET is_active='0' WHERE frequency='" . $email_frequncy . "' AND day_or_hour='" . $email_day_or_hour . "' ";
                $wcal_updated                 = $wpdb->query( $query_update );

                if ( 1 == $wcal_updated ){
                    $query_update_get_id = "SELECT id FROM  `" . $wpdb->prefix . "ac_email_templates_lite` WHERE id != $template_id AND frequency='" . $email_frequncy . "' AND day_or_hour='" . $email_day_or_hour . "' ";
                    $wcal_updated_get_id = $wpdb->get_results( $query_update_get_id );
                    $wcal_all_ids = '';
                    foreach ( $wcal_updated_get_id as $wcal_updated_get_id_key => $wcal_updated_get_id_value ) {
                        # code...
                        if ( '' == $wcal_all_ids ){
                            $wcal_all_ids =  $wcal_updated_get_id_value->id;
                        } else {
                            $wcal_all_ids = $wcal_all_ids . ',' . $wcal_updated_get_id_value->id;
                        }
                    }
                    echo 'wcal-template-updated:'. $wcal_all_ids ;
                }

                $active = "1";

                update_option( 'wcal_template_' . $template_id . '_time', current_time( 'timestamp' ) );
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
        /**
         * It will replace the test email data with the static content.
         * @return string email sent | not sent
         * @since 1.0
         */      
        function wcal_preview_email_sent() {
            if ( '' != $_POST['body_email_preview'] ) {
                $from_email_name       = get_option ( 'wcal_from_name' );
                $reply_name_preview    = get_option ( 'wcal_from_email' );
                $from_email_preview    = get_option ( 'wcal_reply_email' );
                $subject_email_preview = stripslashes ( $_POST['subject_email_preview'] );
                $subject_email_preview = convert_smilies ( $subject_email_preview ); 
                $subject_email_preview    = str_replace( '{{customer.firstname}}', 'John', $subject_email_preview );                       
                $body_email_preview    = convert_smilies ( $_POST['body_email_preview'] );
                $is_wc_template        = $_POST['is_wc_template'];
                $wc_template_header    = stripslashes( $_POST['wc_template_header'] );

                $body_email_preview    = str_replace( '{{customer.firstname}}', 'John', $body_email_preview );                                                  
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
                                            <tr> <td colspan="5"> <h3>'.__( 'Your Shopping Cart', 'woocommerce-abandoned-cart' ).'</h3> </td></tr>
                                            <tr align="center">
                                               <th>'.__( 'Item', 'woocommerce-abandoned-cart' ).'</th>
                                               <th>'.__( 'Name', 'woocommerce-abandoned-cart' ).'</th>
                                               <th>'.__( 'Quantity', 'woocommerce-abandoned-cart' ).'</th>
                                               <th>'.__( 'Price', 'woocommerce-abandoned-cart' ).'</th>
                                               <th>'.__( 'Line Subtotal', 'woocommerce-abandoned-cart' ).'</th>
                                            </tr>
                                            <tr align="center">
                                               <td><img class="demo_img" width="42" height="42" src="'.plugins_url().'/woocommerce-abandoned-cart/assets/images/shoes.jpg"/></td>
                                               <td>'.__( "Men\'\s Formal Shoes", 'woocommerce-abandoned-cart' ).'</td>
                                               <td>1</td>
                                               <td>' . $wcal_price . '</td>
                                               <td>' . $wcal_price . '</td>
                                            </tr>
                                            <tr align="center">
                                               <td><img class="demo_img" width="42" height="42" src="'.plugins_url().'/woocommerce-abandoned-cart/assets/images/handbag.jpg"/></td>
                                               <td>'.__( "Woman\'\s Hand Bags", 'woocommerce-abandoned-cart' ).'</td>
                                               <td>1</td>
                                               <td>' . $wcal_price . '</td>
                                               <td>' . $wcal_price . '</td>
                                            </tr>
                                            <tr align="center">
                                               <td></td>
                                               <td></td>
                                               <td></td>
                                               <td>'.__( "Cart Total:", 'woocommerce-abandoned-cart' ).'</td>
                                               <td>' . $wcal_total_price . '</td>
                                            </tr>
                                        </table>';
                } else {
                    $headers           = "From: " . $from_email_name . " <" . $from_email_preview . ">" . "\r\n";
                    $headers          .= "Content-Type: text/html" . "\r\n";
                    $headers          .= "Reply-To:  " . $reply_name_preview . " " . "\r\n";
                    $var               = '<h3>'.__( "Your Shopping Cart", 'woocommerce-abandoned-cart' ).'</h3>
                                        <table border="0" cellpadding="10" cellspacing="0" class="templateDataTable">
                                            <tr align="center">
                                               <th>'.__( "Item", 'woocommerce-abandoned-cart' ).'</th>
                                               <th>'.__( "Name", 'woocommerce-abandoned-cart' ).'</th>
                                               <th>'.__( "Quantity", 'woocommerce-abandoned-cart' ).'</th>
                                               <th>'.__( "Price", 'woocommerce-abandoned-cart' ).'</th>
                                               <th>'.__( "Line Subtotal", 'woocommerce-abandoned-cart' ).'</th>
                                            </tr>
                                            <tr align="center">
                                               <td><img class="demo_img" width="42" height="42" src="'.plugins_url().'/woocommerce-abandoned-cart/assets/images/shoes.jpg"/></td>
                                               <td>'.__( "Men\'\s Formal Shoes", 'woocommerce-abandoned-cart' ).'</td>
                                               <td>1</td>
                                               <td>' . $wcal_price . '</td>
                                               <td>' . $wcal_price . '</td>
                                            </tr>
                                            <tr align="center">
                                               <td><img class="demo_img" width="42" height="42" src="'.plugins_url().'/woocommerce-abandoned-cart/assets/images/handbag.jpg"/></td>
                                               <td>'.__( "Woman\'\s Hand Bags", 'woocommerce-abandoned-cart' ).'</td>
                                               <td>1</td>
                                               <td>' . $wcal_price . '</td>
                                               <td>' . $wcal_price . '</td>
                                            </tr>
                                            <tr align="center">
                                               <td></td>
                                               <td></td>
                                               <td></td>
                                               <td>'.__( "Cart Total:", 'woocommerce-abandoned-cart' ).'</td>
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
                $user_email_from          = get_option( 'admin_email' );                
                $body_email_final_preview = stripslashes( $body_email_preview );
                
                if ( isset( $is_wc_template ) && 'true' == $is_wc_template ) {
                    ob_start();
                    // Get email heading
                    wc_get_template( 'emails/email-header.php', array( 'email_heading' => $wc_template_header ) );
                    $email_body_template_header = ob_get_clean();                           

                    ob_start();                         
                    wc_get_template( 'emails/email-footer.php' );                               
                    $email_body_template_footer = ob_get_clean();   
                                            
                    $final_email_body = $email_body_template_header . $body_email_final_preview . $email_body_template_footer;

                    $site_title                 = get_bloginfo( 'name' ); 
                    $email_body_template_footer = str_replace( '{site_title}', $site_title, $email_body_template_footer ); 
                    
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