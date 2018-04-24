<?php
/**
 * Abandoned Cart Lite for WooCommerce
 *
 * @author  Tyche Softwares
 * @package Abandoned-Cart-Lite-for-WooCommerce/Common-Functions
 */

/**
 * It will have all the common funtions for the plugin.
 * @since 2.5.2
 */
class wcal_common {

    /**
	 * Get abandoned orders counts.
	 * @globals mixed $wpdb
	 * @return string | int $wcal_order_count
	 * @since 3.9
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
	 * @globals mixed $wpdb
	 * @return string | int $wcal_recovered_order_count
	 * @since 3.9
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

	/**
	 * Get Total abandoned orders amount.
	 * @globals mixed $wpdb
	 * @return string | int $wcal_abandoned_orders_amount
	 * @since 3.9  
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

	/**
	 * Get Total abandoned orders amount.
	 * @globals mixed $wpdb
	 * @param array | object $wcal_abandoned_query_result 
	 * @return string | int $wcal_abandoned_orders_amount
	 * @since 3.9  
	 */
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

	/**
	 * Get recovered orders total amount.
	 * @globals mixed $wpdb
	 * @return string | int $wcal_recovered_orders_amount
	 * @since 3.9 
	 */
	private static function	wcal_ts_get_recovered_order_total_amount() {

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

	/**
	 * Get recovered orders total amount.
	 * @globals mixed $wpdb
	 * @param array | object $wcal_data
	 * @return string | int $wcal_recovered_orders_amount
	 * @since 3.9 
	 */

	private static function wcal_get_recovered_amount ( $wcal_data ){

		$wcal_recovered_orders_amount = 0;

		foreach ($wcal_data as $wcal_data_key => $wcal_data_value) {

			$wcal_order_total 			 = get_post_meta( $wcal_data_value->recovered_cart , '_order_total', true);
			$wcal_recovered_orders_amount = $wcal_recovered_orders_amount + $wcal_order_total;
		}
		return $wcal_recovered_orders_amount;
	}

	/**
	 * Get sent email total count.
	 * @globals mixed $wpdb
	 * @return string | int $wcal_sent_emails_count
	 * @since 3.9 
	 */
	private static function wcal_ts_get_sent_emails_total_count(){

		global $wpdb;
		$wcal_sent_emails_count = 0;
		$wcal_sent_emails_query = "SELECT COUNT(id) FROM `" . $wpdb->prefix . "ac_sent_history_lite`";
		$wcal_sent_emails_count = $wpdb->get_var( $wcal_sent_emails_query );
		return $wcal_sent_emails_count;
	}

	/**
	 * Get email templates total count.
	 * @globals mixed $wpdb
	 * @return array $wcal_templates_data
	 * @since 3.9
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

	/**
	 * Get logged-in users total abandoned count.
     * @globals mixed $wpdb
	 * @return string | int $wcal_logged_in_user_query_count
	 * @since 3.9
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

	/**
	 * Get Guest users total abandoned count.
	 * @globals mixed $wpdb
	 * @return string | int $wcal_guest_user_query_count
	 * @since 3.9
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

	/**
	 * Get logged-in users total abandoned amount.
     * @globals mixed $wpdb
	 * @return string | int $wcal_abandoned_orders_amount
	 * @since 3.9
	 */
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

	/**
	 * Get Guest users total abandoned amount.
     * @globals mixed $wpdb
	 * @return string | int $wcal_abandoned_orders_amount
	 * @since 3.9
	 */
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

	/**
	 * Get logged-in users total recovered amount.
     * @globals mixed $wpdb
	 * @return string | int $wcal_recovered_orders_amount
	 * @since 3.9
	 */
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


	 /**
	 * Get Guest users total recovered amount.
     * @globals mixed $wpdb
	 * @return string | int $wcal_recovered_orders_amount
	 * @since 3.9
	 */
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
	 * Get all options of the plugin.
	 * @return array
	 * @since 3.9
	 */
	private static function wcal_ts_get_all_plugin_options_values() {
		
		return array(
			'wcal_cart_cut_off_time'                => get_option( 'ac_lite_cart_abandoned_time' ),
			'wcal_admin_recovery_email'             => get_option( 'ac_lite_email_admin_on_recovery' ),
			'wcal_capture_visitors_cart'            => get_option( 'ac_lite_track_guest_cart_from_cart_page' )
		 ); 
	}


    /**
	 * If admin allow to track the data the it will gather all information and return back.
	 * @hook ts_tracker_data
	 * @param array $data
	 * @return array $data
	 * @since 3.9
	 */
	public static function ts_add_plugin_tracking_data ( $data ){
        
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
			$plugin_data[ 'plugin_version' ]    				= self::wcal_get_version();
			$plugin_data[ 'wcal_allow_tracking' ]      			= get_option ('wcal_allow_tracking');
			
			$data [ 'plugin_data' ] = $plugin_data;
		}
		return $data;
	 }

	/**
	 * Get data when Admin dont want to share information.
	 * @param array $params
	 * @return array $params
	 * @since 3.9
	 */
	public static function  ts_get_data_for_opt_out( $params ){

		$plugin_data[ 'ts_meta_data_table_name']   = 'ts_wcal_tracking_meta_data';
		$plugin_data[ 'ts_plugin_name' ]		   = 'Abandoned Cart Lite for WooCommerce';
		$plugin_data[ 'abandoned_orders_amount' ]  = self::wcal_ts_get_abandoned_order_total_amount();
		// Store recovered count info
		$plugin_data[ 'recovered_orders_amount' ]  = self::wcal_ts_get_recovered_order_total_amount();
		
		$params[ 'plugin_data' ]  				   = $plugin_data;

		return $params;
	}

    /**
     * It will fetch the total count for the abandoned cart section.
     * @param string $get_section_result Name of the section for which we need result
     * @return string | int $return_abandoned_count
     * @globals mixed $wpdb
     * @since 2.5.2
     */    
    public static function wcal_get_abandoned_order_count( $get_section_result ){
        global $wpdb;
        $return_abandoned_count = 0;    
        $blank_cart_info        = '{"cart":[]}';
        $blank_cart_info_guest  = '[]';
        $blank_cart             = '""';      
        $ac_cutoff_time         = get_option( 'ac_lite_cart_abandoned_time' );
        $cut_off_time           = intval( $ac_cutoff_time ) * 60;
        $current_time           = current_time( 'timestamp' );
        $compare_time           = $current_time - $cut_off_time;
    
        switch ( $get_section_result ) {
            case 'wcal_all_abandoned':    
                $query_ac        = "SELECT COUNT(`id`) as cnt FROM `".$wpdb->prefix."ac_abandoned_cart_history_lite` WHERE ( user_type = 'REGISTERED' AND abandoned_cart_info NOT LIKE '%$blank_cart_info%' AND abandoned_cart_info NOT LIKE '%$blank_cart%' AND abandoned_cart_time <= '$compare_time' AND recovered_cart = 0 ) OR ( user_type = 'GUEST' AND abandoned_cart_info NOT LIKE '$blank_cart_info_guest' AND abandoned_cart_info NOT LIKE '%$blank_cart_info%' AND abandoned_cart_info NOT LIKE '$blank_cart' AND abandoned_cart_time <= '$compare_time' AND recovered_cart = 0 ) ORDER BY recovered_cart desc ";
                $return_abandoned_count  = $wpdb->get_var( $query_ac );
                break;
    
            case 'wcal_all_registered':    
                $query_ac        = "SELECT COUNT(`id`) FROM `".$wpdb->prefix."ac_abandoned_cart_history_lite` WHERE ( user_type = 'REGISTERED' AND abandoned_cart_info NOT LIKE '%$blank_cart_info%' AND abandoned_cart_info NOT LIKE '$blank_cart' AND abandoned_cart_time <= '$compare_time' AND recovered_cart = 0 ) ORDER BY recovered_cart desc ";
                $return_abandoned_count = $wpdb->get_var( $query_ac );
                break;
    
            case 'wcal_all_guest':
                $query_ac        = "SELECT COUNT(`id`) FROM `".$wpdb->prefix."ac_abandoned_cart_history_lite` WHERE ( user_type = 'GUEST' AND abandoned_cart_info NOT LIKE '$blank_cart_info_guest' AND abandoned_cart_info NOT LIKE '%$blank_cart_info%' AND abandoned_cart_info NOT LIKE '$blank_cart' AND abandoned_cart_time <= '$compare_time' AND recovered_cart = 0 AND user_id >= 63000000 ) ORDER BY recovered_cart desc ";
                $return_abandoned_count = $wpdb->get_var( $query_ac );
                break;
    
            case 'wcal_all_visitor':
                $query_ac        = "SELECT COUNT(`id`) FROM `".$wpdb->prefix."ac_abandoned_cart_history_lite` WHERE ( user_type = 'GUEST' AND abandoned_cart_info NOT LIKE '$blank_cart_info_guest' AND abandoned_cart_info NOT LIKE '%$blank_cart_info%' AND abandoned_cart_info NOT LIKE '$blank_cart' AND abandoned_cart_time <= '$compare_time' AND recovered_cart = 0  AND user_id = 0 ) ORDER BY recovered_cart desc ";
                $return_abandoned_count = $wpdb->get_var( $query_ac );   
                break;
    
            default:
                # code...
                break;
        }    
        return $return_abandoned_count;
    }


    /**
     * This function returns the Abandoned Cart Lite plugin version number.
     * @return string $plugin_version
     * @since 2.5.2
     */
    public static function wcal_get_version() {
        $plugin_version = '';
        $wcap_plugin_dir =  dirname ( dirname (__FILE__) );
        $wcap_plugin_dir .= '/woocommerce-ac.php';

        $plugin_data = get_file_data( $wcap_plugin_dir, array( 'Version' => 'Version' ) );
        if ( ! empty( $plugin_data['Version'] ) ) {
            $plugin_version = $plugin_data[ 'Version' ];
        }
        return $plugin_version;
    }

    /**
     * This function returns the plugin url.
     * @return string plugin url
     * @since 2.5.2 
     */
    public static function wcal_get_plugin_url() {
        return plugins_url() . '/woocommerce-abandoned-cart/';
    }

    /**
     * This function will alter Email Templates Table to include emojis
     * 
     * @return bool true if success else false
     * 
     * @since 4.8
     */
    public static function update_templates_table(){

        global $wpdb;
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        $query = "ALTER TABLE " . $wpdb->prefix . "ac_email_templates_lite" . " CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";

        return $wpdb->query( $query );
    }

    /**
     * This function will show a dismissible success message after DB update is completed
     * 
     * @since 4.8
     */
    public static function show_update_success() {
        ?>

        <div class="notice notice-success is-dismissible"> 
            <p><strong><?php _e( 'Database Updated Successfully', 'woocommerce-abandoned-cart');?></strong></p>
        </div>

        <?php
    }

    /**
     * This function will show a dismissible success message after DB update is completed
     * 
     * @since 4.8
     */
    public static function show_update_failure() {
        ?>

        <div class="notice notice-error is-dismissible"> 
            <p><strong><?php _e( 'Database Update Failed. Please try again after sometime', 'woocommerce-abandoned-cart');?></strong></p>
        </div>

        <?php
    }
}
?>