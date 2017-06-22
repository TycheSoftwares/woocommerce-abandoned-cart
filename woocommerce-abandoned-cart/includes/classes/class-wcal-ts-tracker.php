<?php
/**
 * Abandoned cart data tracker
 *
 * The Abandoned Cart lite tracker class adds functionality to track Abandoned Cart lite Date usage based on if the customer opted in.
 * No personal information is tracked, only general Abandoned Cart lite settings, abandoned orders and recovered orders, abandoned orders amount, recovred orders amount, total templates, total email sent, logged-in users abandoned & recovered amount, guest users abandoned and admin email for discount code.
 *
 * @class 		Class_Wcal_Ts_Tracker
 * @version		6.8
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Class_Wcal_Ts_Tracker {

	/**
	 * URL to the  Tracker API endpoint.
	 * @var string
	 */
	private static $wcal_api_url = 'http://tracking.tychesoftwares.com/v1/';

	/**
	 * Hook into cron event.
	 */
	public static function init() {
		add_action( 'wcal_ts_tracker_send_event', array( __CLASS__, 'wcal_ts_send_tracking_data' ) );
		add_filter( 'ts_tracker_data',            array( __CLASS__, 'wcal_ts_add_plugin_tracking_data' ), 10, 1);
		add_filter( 'ts_tracker_opt_out_data',    array( __CLASS__, 'wcal_get_data_for_opt_out' ), 10, 1);
	}

	/**
	 * Decide whether to send tracking data or not.
	 *
	 * @param boolean $override
	 */
	public static function wcal_ts_send_tracking_data( $override = false ) {
		
		if ( ! apply_filters( 'wcal_ts_tracker_send_override', $override ) ) {
			// Send a maximum of once per week by default.
			$wcal_last_send = self::wcal_ts_get_last_send_time();
			if ( $wcal_last_send && $wcal_last_send > apply_filters( 'wcal_ts_tracker_last_send_interval', strtotime( '-1 week' ) ) ) {
				return;
			}
		} else {
			// Make sure there is at least a 1 hour delay between override sends, we don't want duplicate calls due to double clicking links.
			$wcal_last_send = self::wcal_ts_get_last_send_time();
			if ( $wcal_last_send && $wcal_last_send > strtotime( '-1 hours' ) ) {
				return;
			}
		}

		$allow_tracking =  get_option('wcal_allow_tracking');
		if ( 'yes' == $allow_tracking ){
			$override = true;			
		}

		// Update time first before sending to ensure it is set
		update_option( 'wcal_ts_tracker_last_send', time() );

		if( $override == false ) {
			$params   = array();
			$params[ 'tracking_usage' ] = 'no';
			$params[ 'url' ]            = home_url();
			$params[ 'email' ]          = apply_filters( 'wcal_ts_tracker_admin_email', get_option( 'admin_email' ) );

			$params 					= apply_filters( 'ts_tracker_opt_out_data', $params );
		} else {
			
			$params   = self::wcal_ts_get_tracking_data();
		}

		wp_safe_remote_post( self::$wcal_api_url, array(
				'method'      => 'POST',
				'timeout'     => 45,
				'redirection' => 5,
				'httpversion' => '1.0',
				'blocking'    => false,
				'headers'     => array( 'user-agent' => 'TSTracker/' . md5( esc_url( home_url( '/' ) ) ) . ';' ),
				'body'        => json_encode( $params ),
				'cookies'     => array(),
			)
		);	
	}

	/**
	 * Get the last time tracking data was sent.
	 * @return int|bool
	 */
	private static function wcal_ts_get_last_send_time() {
		return apply_filters( 'wcal_ts_tracker_last_send_time', get_option( 'wcal_ts_tracker_last_send', false ) );
	}

	/**
	 * Get all the tracking data.
	 * @return array
	 */
	private static function wcal_ts_get_tracking_data() {
		$data                        = array();

		// General site info
		$data[ 'url' ]               = home_url();
		$data[ 'email' ]             = apply_filters( 'wcal_ts_tracker_admin_email', get_option( 'admin_email' ) );

		// WordPress Info
		$data[ 'wp' ]                = self::wcal_ts_get_wordpress_info();

		$data[ 'theme_info' ]        = self::wcal_ts_get_theme_info();

		// Server Info
		$data[ 'server' ]            = self::wcal_ts_get_server_info();

		// Plugin info
		$all_plugins                 = self::wcal_ts_get_all_plugins();
		$data[ 'active_plugins' ]    = $all_plugins[ 'active_plugins' ];
		$data[ 'inactive_plugins' ]  = $all_plugins[ 'inactive_plugins' ];

		//WooCommerce version 
		$data[ 'wc_plugin_version' ] = self::wcal_ts_get_wc_plugin_version();
		return apply_filters( 'ts_tracker_data', $data );
	}

	/**
	 * Get plugin related data.
	 * @return array
	 */
	public static function wcal_ts_add_plugin_tracking_data ( $data ){


		if ( isset( $_GET[ 'wcal_tracker_optin' ] ) && isset( $_GET[ 'wcal_tracker_nonce' ] ) && wp_verify_nonce( $_GET[ 'wcal_tracker_nonce' ], 'wcal_tracker_optin' ) ) {

			$plugin_data[ 'ts_meta_data_table_name']            = 'ts_wcal_tracking_meta_data';

			$plugin_data[ 'ts_plugin_name' ]					= 'Abandoned Cart Lite for WooCommerce';

			// Store abandoned count info
			$plugin_data[ 'abandoned_orders' ]  				= self::wcal_ts_get_abandoned_order_counts();

			// Store recovred count info
			$plugin_data[ 'recovered_orders' ]  				= self::wcal_ts_get_recovered_order_counts();

			// store abandoned orders amount
			$plugin_data[ 'abandoned_orders_amount' ]    		= self::wcal_ts_get_abandoned_order_total_amount();

			// Store recovered count info
			$plugin_data[ 'recovered_orders_amount' ]    		= self::wcal_ts_get_recovered_order_total_amount();

			// Store abandoned cart emails sent count info
			$plugin_data[ 'sent_emails' ] 			      		= self::wcal_ts_get_sent_emails_total_count();

			// Store email template count info
			$plugin_data[ 'email_templates_data' ] 			    = self::wcal_ts_get_email_templates_data();

			// Store only logged-in users abandoned cart count info
			$plugin_data[ 'logged_in_abandoned_orders' ] 		= self::wcal_ts_get_logged_in_users_abandoned_cart_total_count();

			// Store only logged-in users abandoned cart count info
			$plugin_data[ 'guest_abandoned_orders' ] 			= self::wcal_ts_get_guest_users_abandoned_cart_total_count();

			// Store only logged-in users abandoned cart amount info
			$plugin_data[ 'logged_in_abandoned_orders_amount' ] = self::wcal_ts_get_logged_in_users_abandoned_cart_total_amount();

			// store only guest users abandoned cart amount
			$plugin_data[ 'guest_abandoned_orders_amount' ]     = self::wcal_ts_get_guest_users_abandoned_cart_total_amount();

			// Store only logged-in users recovered cart amount info
			$plugin_data[ 'logged_in_recovered_orders_amount' ] = self::wcal_ts_get_logged_in_users_recovered_cart_total_amount();

			// Store only guest users recovered cart amount 
			$plugin_data[ 'guest_recovered_orders_amount' ]     = self::wcal_ts_get_guest_users_recovered_cart_total_amount();

			// Get all plugin options info
			$plugin_data[ 'settings' ]          				= self::wcal_ts_get_all_plugin_options_values();
			$plugin_data[ 'plugin_version' ]    				= self::wcal_ts_get_plugin_version();
			$plugin_data[ 'wcal_allow_tracking' ]      			= get_option ('wcal_allow_tracking');
			
			$data [ 'plugin_data' ] = $plugin_data;
		}
		return $data;
	 }

	/**
	 * Get data when user dont want to share information.
	 * @return array
	 */
	public static function  wcal_get_data_for_opt_out( $params ){

		$plugin_data[ 'ts_meta_data_table_name']   = 'ts_wcal_tracking_meta_data';
		$plugin_data[ 'ts_plugin_name' ]		   = 'Abandoned Cart Lite for WooCommerce';
		$plugin_data[ 'abandoned_orders_amount' ]  = self::wcal_ts_get_abandoned_order_total_amount();
		// Store recovered count info
		$plugin_data[ 'recovered_orders_amount' ]  = self::wcal_ts_get_recovered_order_total_amount();
		
		$params[ 'plugin_data' ]  				   = $plugin_data;

		return $params;
	}


	/**
	 * Get WordPress related data.
	 * @return array
	 */
	private static function wcal_ts_get_wordpress_info() {
		$wp_data = array();

		$memory = wc_let_to_num( WP_MEMORY_LIMIT );

		if ( function_exists( 'memory_get_usage' ) ) {
			$system_memory = wc_let_to_num( @ini_get( 'memory_limit' ) );
			$memory        = max( $memory, $system_memory );
		}

		$wp_data[ 'memory_limit' ] = size_format( $memory );
		$wp_data[ 'debug_mode' ]   = ( defined( 'WP_DEBUG' ) && WP_DEBUG ) ? 'Yes' : 'No';
		$wp_data[ 'locale' ]       = get_locale();
		$wp_data[ 'wp_version' ]   = get_bloginfo( 'version' );
		$wp_data[ 'multisite' ]    = is_multisite() ? 'Yes' : 'No';

		return $wp_data;
	}

	/**
	 * Get the current theme info, theme name and version.
	 * @return array
	 */
	public static function wcal_ts_get_theme_info() {
		$theme_data        = wp_get_theme();
		$theme_child_theme = is_child_theme() ? 'Yes' : 'No';

		return array( 'theme_name'    => $theme_data->Name, 
					  'theme_version' => $theme_data->Version, 
					  'child_theme'   => $theme_child_theme );
	}

	/**
	 * Get server related info.
	 * @return array
	 */
	private static function wcal_ts_get_server_info() {
		$server_data = array();

		if ( isset( $_SERVER[ 'SERVER_SOFTWARE' ] ) && ! empty( $_SERVER[ 'SERVER_SOFTWARE' ] ) ) {
			$server_data[ 'software' ] = $_SERVER[ 'SERVER_SOFTWARE' ];
		}

		if ( function_exists( 'phpversion' ) ) {
			$server_data[ 'php_version' ] = phpversion();
		}

		if ( function_exists( 'ini_get' ) ) {
			$server_data[ 'php_post_max_size' ]  = size_format( wc_let_to_num( ini_get( 'post_max_size' ) ) );
			$server_data[ 'php_time_limt' ]      = ini_get( 'max_execution_time' );
			$server_data[ 'php_max_input_vars' ] = ini_get( 'max_input_vars' );
			$server_data[ 'php_suhosin' ]        = extension_loaded( 'suhosin' ) ? 'Yes' : 'No';
		}

		global $wpdb;
		$server_data[ 'mysql_version' ]        = $wpdb->db_version();

		$server_data[ 'php_max_upload_size' ]  = size_format( wp_max_upload_size() );
		$server_data[ 'php_default_timezone' ] = date_default_timezone_get();
		$server_data[ 'php_soap' ]             = class_exists( 'SoapClient' ) ? 'Yes' : 'No';
		$server_data[ 'php_fsockopen' ]        = function_exists( 'fsockopen' ) ? 'Yes' : 'No';
		$server_data[ 'php_curl' ]             = function_exists( 'curl_init' ) ? 'Yes' : 'No';

		return $server_data;
	}

	/**
	 * Get all plugins grouped into activated or not.
	 * @return array
	 */
	private static function wcal_ts_get_all_plugins() {
		// Ensure get_plugins function is loaded
		if ( ! function_exists( 'get_plugins' ) ) {
			include ABSPATH . '/wp-admin/includes/plugin.php';
		}

		$plugins        	 = get_plugins();
		$active_plugins_keys = get_option( 'active_plugins', array() );
		$active_plugins 	 = array();

		foreach ( $plugins as $k => $v ) {
			// Take care of formatting the data how we want it.
			$formatted = array();
			$formatted[ 'name' ] = strip_tags( $v[ 'Name' ] );
			if ( isset( $v[ 'Version' ] ) ) {
				$formatted[ 'version' ] = strip_tags( $v[ 'Version' ] );
			}
			if ( isset( $v[ 'Author' ] ) ) {
				$formatted[ 'author' ] = strip_tags( $v[ 'Author' ] );
			}
			if ( isset( $v[ 'Network' ] ) ) {
				$formatted[ 'network' ] = strip_tags( $v[ 'Network' ] );
			}
			if ( isset( $v[ 'PluginURI' ] ) ) {
				$formatted[ 'plugin_uri' ] = strip_tags( $v[ 'PluginURI' ] );
			}
			if ( in_array( $k, $active_plugins_keys ) ) {
				// Remove active plugins from list so we can show active and inactive separately
				unset( $plugins[ $k ] );
				$active_plugins[ $k ] = $formatted;
			} else {
				$plugins[ $k ] = $formatted;
			}
		}

		return array( 'active_plugins' => $active_plugins, 'inactive_plugins' => $plugins );
	}

	/**
	 * Get abandoned orders counts.
	 * @return string
	 */
	private static function wcal_ts_get_abandoned_order_counts() {
		global $wpdb;
		$wcal_order_count = 0;

		$ac_cutoff_time = get_option( 'ac_lite_cart_abandoned_time' );
	    $cut_off_time   = $ac_cutoff_time * 60;
	    $current_time   = current_time( 'timestamp' );
	    $compare_time   = $current_time - $cut_off_time;

	    $blank_cart_info       = '{"cart":[]}';
		$blank_cart_info_guest = '[]';

		$wcal_query = "SELECT COUNT(id) FROM `" . $wpdb->prefix . "ac_abandoned_cart_history_lite` WHERE abandoned_cart_time <= '$compare_time' AND abandoned_cart_info NOT LIKE '%$blank_cart_info%' AND abandoned_cart_info NOT LIKE '$blank_cart_info_guest'";

		$wcal_order_count = $wpdb->get_var( $wcal_query );
		
		return $wcal_order_count;
	}

	
	/**
	 * Get recovered orders counts.
	 * @return string
	 */
	private static function wcal_ts_get_recovered_order_counts(){

		global $wpdb;
		$wcal_recovered_order_count = 0;

		$ac_cutoff_time = get_option( 'ac_lite_cart_abandoned_time' );
	    $cut_off_time   = $ac_cutoff_time * 60;
	    $current_time   = current_time( 'timestamp' );
	    $compare_time   = $current_time - $cut_off_time;

	    $wcal_recovery_query = "SELECT COUNT(id) FROM `" . $wpdb->prefix . "ac_abandoned_cart_history_lite` WHERE recovered_cart > 0 AND abandoned_cart_time <= '$compare_time'";

		$wcal_recovered_order_count = $wpdb->get_var( $wcal_recovery_query );
		
		return $wcal_recovered_order_count;
	}

	/*
	* Get Total abandoned orders amount
	*   
	*/
	private static function wcal_ts_get_abandoned_order_total_amount(){
		global $wpdb;
		$wcal_abandoned_orders_amount = 0;

		$ac_cutoff_time = get_option( 'ac_lite_cart_abandoned_time' );
	    $cut_off_time   = $ac_cutoff_time * 60;
	    $current_time   = current_time( 'timestamp' );
	    $compare_time   = $current_time - $cut_off_time;

	    $blank_cart_info       = '{"cart":[]}';
		$blank_cart_info_guest = '[]';

		$wcal_abandoned_query = "SELECT abandoned_cart_info FROM `" . $wpdb->prefix . "ac_abandoned_cart_history_lite` WHERE abandoned_cart_time <= '$compare_time' AND abandoned_cart_info NOT LIKE '%$blank_cart_info%' AND abandoned_cart_info NOT LIKE '$blank_cart_info_guest'";

		$wcal_abandoned_query_result = $wpdb->get_results( $wcal_abandoned_query );

		$wcal_abandoned_orders_amount = self::wcal_get_abandoned_amount( $wcal_abandoned_query_result );
		
		return $wcal_abandoned_orders_amount;
	}

	private static function wcal_get_abandoned_amount( $wcal_abandoned_query_result ){

		$wcal_abandoned_orders_amount = 0;
		foreach ( $wcal_abandoned_query_result as $wcal_abandoned_query_key => $wcal_abandoned_query_value ) {
			# code...
			$cart_info        = json_decode( $wcal_abandoned_query_value->abandoned_cart_info );

			$cart_details   = array();
			if( isset( $cart_info->cart ) ){
		        $cart_details = $cart_info->cart;
		    }

		    if( count( $cart_details ) > 0 ) {    		
		        foreach( $cart_details as $k => $v ) {    		     
		            if( $v->line_subtotal_tax != 0 && $v->line_subtotal_tax > 0 ) {
		                $wcal_abandoned_orders_amount = $wcal_abandoned_orders_amount + $v->line_total + $v->line_subtotal_tax;
		            } else {
		                $wcal_abandoned_orders_amount = $wcal_abandoned_orders_amount + $v->line_total;
		            }
		        }
		    }
		}
		return $wcal_abandoned_orders_amount;
	}

	/*
	*  Get recovered orders total amount
	*/
	private static function	wcal_ts_get_recovered_order_total_amount(){

		global $wpdb;
		$wcal_recovered_orders_amount = 0;

		$ac_cutoff_time = get_option( 'ac_lite_cart_abandoned_time' );
	    $cut_off_time   = $ac_cutoff_time * 60;
	    $current_time   = current_time( 'timestamp' );
	    $compare_time   = $current_time - $cut_off_time;

	    $wcal_recovery_query_amount = "SELECT recovered_cart FROM `" . $wpdb->prefix . "ac_abandoned_cart_history_lite` WHERE recovered_cart > 0 AND abandoned_cart_time <= '$compare_time'";

		$wcal_recovered_order_amount_result = $wpdb->get_results( $wcal_recovery_query_amount );

		$wcal_recovered_orders_amount = self::wcal_get_recovered_amount ($wcal_recovered_order_amount_result );

		return $wcal_recovered_orders_amount;
	}

	private static function wcal_get_recovered_amount ( $wcal_data ){

		$wcal_recovered_orders_amount = 0;

		foreach ($wcal_data as $wcal_data_key => $wcal_data_value) {

			$wcal_order_total 			 = get_post_meta( $wcal_data_value->recovered_cart , '_order_total', true);
			$wcal_recovered_orders_amount = $wcal_recovered_orders_amount + $wcal_order_total;
		}
		return $wcal_recovered_orders_amount;
	}

	/*
	*  Get sent email total count
	*/
	private static function wcal_ts_get_sent_emails_total_count(){

		global $wpdb;
		$wcal_sent_emails_count = 0;
		$wcal_sent_emails_query = "SELECT COUNT(id) FROM `" . $wpdb->prefix . "ac_sent_history_lite`";
		$wcal_sent_emails_count = $wpdb->get_var( $wcal_sent_emails_query );
		return $wcal_sent_emails_count;
	}

	/*
	*  Get email templates total count
	*/
	private static function wcal_ts_get_email_templates_data(){

		global $wpdb;
		$wcal_email_templates_count   = 0;
		$wcal_email_templates_query   = "SELECT id, is_active, is_wc_template,frequency, day_or_hour FROM `" . $wpdb->prefix . "ac_email_templates_lite`";
		$wcal_email_templates_results = $wpdb->get_results( $wcal_email_templates_query );

		$wcal_email_templates_count   = count( $wcal_email_templates_results );

		$wcal_templates_data = array();
		$wcal_templates_data ['total_templates'] = $wcal_email_templates_count;

		foreach ($wcal_email_templates_results as $wcal_email_templates_results_key => $wcal_email_templates_results_value ) {

			$wcal_template_time = $wcal_email_templates_results_value->frequency . ' ' .$wcal_email_templates_results_value->day_or_hour ;

			$wcal_get_total_email_sent_for_template = "SELECT COUNT(id) FROM `" . $wpdb->prefix . "ac_sent_history_lite` WHERE template_id = ". $wcal_email_templates_results_value->id;
			$wcal_get_total_email_sent_for_template_count = $wpdb->get_var( $wcal_get_total_email_sent_for_template );

			$wcal_templates_data [ "template_id_" . $wcal_email_templates_results_value->id ] ['is_activate']      =  ( $wcal_email_templates_results_value->is_active == 1 ) ? 'Active' : 'Deactive';
			$wcal_templates_data [ "template_id_" . $wcal_email_templates_results_value->id ] ['is_wc_template']   = ( $wcal_email_templates_results_value->is_wc_template == 1 ) ? 'Yes' : 'No';
			$wcal_templates_data [ "template_id_" . $wcal_email_templates_results_value->id ] ['template_time']    = $wcal_template_time;
			$wcal_templates_data [ "template_id_" . $wcal_email_templates_results_value->id ] ['total_email_sent'] = $wcal_get_total_email_sent_for_template_count;
		}

		return $wcal_templates_data;
	}

	/*
	*  Get logged-in users total abandoned count
	*/
	private static function wcal_ts_get_logged_in_users_abandoned_cart_total_count (){

		global $wpdb;
		$wcal_logged_in_user_query_count = 0;

		$ac_cutoff_time = get_option( 'ac_lite_cart_abandoned_time' );
	    $cut_off_time   = $ac_cutoff_time * 60;
	    $current_time   = current_time( 'timestamp' );
	    $compare_time   = $current_time - $cut_off_time;

	    $blank_cart_info       = '{"cart":[]}';
		$blank_cart_info_guest = '[]';

		$wcal_logged_in_user_query = "SELECT COUNT(id) FROM `" . $wpdb->prefix . "ac_abandoned_cart_history_lite` WHERE abandoned_cart_time <= '$compare_time' AND abandoned_cart_info NOT LIKE '%$blank_cart_info%' AND abandoned_cart_info NOT LIKE '$blank_cart_info_guest' AND user_id < 63000000 AND user_id != 0";

		$wcal_logged_in_user_query_count = $wpdb->get_var( $wcal_logged_in_user_query );
		
		return $wcal_logged_in_user_query_count;
	}

	/*
	*  Get logged-in users total abandoned count
	*/
	private static function wcal_ts_get_guest_users_abandoned_cart_total_count(){
		global $wpdb;
		$wcal_guest_user_query_count = 0;

		$ac_cutoff_time = get_option( 'ac_lite_cart_abandoned_time' );
	    $cut_off_time   = $ac_cutoff_time * 60;
	    $current_time   = current_time( 'timestamp' );
	    $compare_time   = $current_time - $cut_off_time;

	    $blank_cart_info       = '{"cart":[]}';
		$blank_cart_info_guest = '[]';

		$wcal_guest_user_query = "SELECT COUNT(id) FROM `" . $wpdb->prefix . "ac_abandoned_cart_history_lite` WHERE abandoned_cart_time <= '$compare_time' AND abandoned_cart_info NOT LIKE '%$blank_cart_info%' AND abandoned_cart_info NOT LIKE '$blank_cart_info_guest' AND user_id >= 63000000 AND user_id != 0";

		$wcal_guest_user_query_count = $wpdb->get_var( $wcal_guest_user_query );
		
		return $wcal_guest_user_query_count;
	}

	private static function wcal_ts_get_logged_in_users_abandoned_cart_total_amount (){

		global $wpdb;
		$wcal_abandoned_orders_amount = 0;

		$ac_cutoff_time = get_option( 'ac_lite_cart_abandoned_time' );
	    $cut_off_time   = $ac_cutoff_time * 60;
	    $current_time   = current_time( 'timestamp' );
	    $compare_time   = $current_time - $cut_off_time;

	    $blank_cart_info       = '{"cart":[]}';
		$blank_cart_info_guest = '[]';

		$wcal_abandoned_query = "SELECT abandoned_cart_info FROM `" . $wpdb->prefix . "ac_abandoned_cart_history_lite` WHERE abandoned_cart_time <= '$compare_time' AND abandoned_cart_info NOT LIKE '%$blank_cart_info%' AND abandoned_cart_info NOT LIKE '$blank_cart_info_guest' AND user_id < 63000000 AND user_id != 0 ";

		$wcal_abandoned_query_result = $wpdb->get_results( $wcal_abandoned_query );

		$wcal_abandoned_orders_amount = self::wcal_get_abandoned_amount( $wcal_abandoned_query_result );
		
		return $wcal_abandoned_orders_amount;
	}

	private static function wcal_ts_get_guest_users_abandoned_cart_total_amount (){

		global $wpdb;
		$wcal_abandoned_orders_amount = 0;

		$ac_cutoff_time = get_option( 'ac_lite_cart_abandoned_time' );
	    $cut_off_time   = $ac_cutoff_time * 60;
	    $current_time   = current_time( 'timestamp' );
	    $compare_time   = $current_time - $cut_off_time;

	    $blank_cart_info       = '{"cart":[]}';
		$blank_cart_info_guest = '[]';

		$wcal_abandoned_query = "SELECT abandoned_cart_info FROM `" . $wpdb->prefix . "ac_abandoned_cart_history_lite` WHERE abandoned_cart_time <= '$compare_time' AND abandoned_cart_info NOT LIKE '%$blank_cart_info%' AND abandoned_cart_info NOT LIKE '$blank_cart_info_guest' AND user_id >= 63000000 AND user_id != 0 ";

		$wcal_abandoned_query_result = $wpdb->get_results( $wcal_abandoned_query );

		$wcal_abandoned_orders_amount = self::wcal_get_abandoned_amount( $wcal_abandoned_query_result );
		
		return $wcal_abandoned_orders_amount;
	}

	private static function wcal_ts_get_logged_in_users_recovered_cart_total_amount(){

		global $wpdb;
		$wcal_recovered_orders_amount = 0;

		$ac_cutoff_time = get_option( 'ac_lite_cart_abandoned_time' );
	    $cut_off_time   = $ac_cutoff_time * 60;
	    $current_time   = current_time( 'timestamp' );
	    $compare_time   = $current_time - $cut_off_time;

	    $wcal_recovery_query_amount = "SELECT recovered_cart FROM `" . $wpdb->prefix . "ac_abandoned_cart_history_lite` WHERE recovered_cart > 0 AND abandoned_cart_time <= '$compare_time' AND user_id < 63000000 AND user_id != 0 ";

		$wcal_recovered_order_amount_result = $wpdb->get_results( $wcal_recovery_query_amount );

		$wcal_recovered_orders_amount = self::wcal_get_recovered_amount ($wcal_recovered_order_amount_result );

		return $wcal_recovered_orders_amount;

	 }


	private static function wcal_ts_get_guest_users_recovered_cart_total_amount (){

		global $wpdb;
		$wcal_recovered_orders_amount = 0;

		$ac_cutoff_time = get_option( 'ac_lite_cart_abandoned_time' );
	    $cut_off_time   = $ac_cutoff_time * 60;
	    $current_time   = current_time( 'timestamp' );
	    $compare_time   = $current_time - $cut_off_time;

	    $wcal_recovery_query_amount = "SELECT recovered_cart FROM `" . $wpdb->prefix . "ac_abandoned_cart_history_lite` WHERE recovered_cart > 0 AND abandoned_cart_time <= '$compare_time' AND user_id >= 63000000 AND user_id != 0 ";

		$wcal_recovered_order_amount_result = $wpdb->get_results( $wcal_recovery_query_amount );

		$wcal_recovered_orders_amount = self::wcal_get_recovered_amount ($wcal_recovered_order_amount_result );

		return $wcal_recovered_orders_amount;

	}
	/**
	 * Get all options starting with woocommerce_ prefix.
	 * @return array
	 */
	private static function wcal_ts_get_all_plugin_options_values() {
		
		return array(
			'wcal_cart_cut_off_time'                => get_option( 'ac_lite_cart_abandoned_time' ),
			'wcal_admin_recovery_email'             => get_option( 'ac_lite_email_admin_on_recovery' ),
			'wcal_capture_visitors_cart'            => get_option( 'ac_lite_track_guest_cart_from_cart_page' )
		 ); 
	}

	private static function wcal_ts_get_wc_plugin_version() {
		return WC()->version;
	}

	private static function wcal_ts_get_plugin_license_key() {
		return 'Abandoned Cart Lite';
	}

	private static function wcal_ts_get_plugin_version() {
		$wcal_plugin_version = self::wcal_plugin_get_version();
		return $wcal_plugin_version;
	}

	/**
	 * @return string Plugin version
	 */

	public static function wcal_plugin_get_version() {

		
		$plugin_path    				 =  dirname ( dirname ( dirname ( __FILE__ ) ) )  ;
		$plugin_path_with_base_file_name =  $plugin_path . "/woocommerce-ac.php";
    	$plugin_data    = get_plugin_data( $plugin_path_with_base_file_name );
    	$plugin_version = $plugin_data['Version'];

	    return $plugin_version;
	}

}
Class_Wcal_Ts_Tracker::init();