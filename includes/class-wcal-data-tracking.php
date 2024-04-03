<?php
/**
 * Abandon Cart Lite for WooCommerce - Data Tracking Class
 *
 * @version 1.0.0
 * @since   5.20.0
 * @package Abandon Cart Lite/Data Tracking
 * @author  Tyche Softwares
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'Wcal_Data_Tracking' ) ) :

	/**
	 * Abandon Cart Lite Data Tracking Core.
	 */
	class Wcal_Data_Tracking {

		/**
		 * Construct.
		 *
		 * @since 5.20.0
		 */
		public function __construct() {

			// Include JS script for the notice.
			add_filter( 'ts_tracker_data', array( __CLASS__, 'wcal_ts_add_plugin_tracking_data' ), 10, 1 );
			add_action( 'admin_footer', array( __CLASS__, 'ts_admin_notices_scripts' ) );
			// Send Tracker Data.
			add_action( 'wcal_init_tracker_completed', array( __CLASS__, 'init_tracker_completed' ), 10, 2 );
			add_filter( 'wcal_ts_tracker_display_notice', array( __CLASS__, 'wcal_ts_tracker_display_notice' ), 10, 1 );
			add_filter( 'wcal_ts_tracker_data', array( __CLASS__, 'wcal_plugin_tracking_data' ), 10, 1 );
			add_filter( 'ts_tracker_opt_out_data', array( __CLASS__, 'ts_get_data_for_opt_out' ), 10, 1 );
		}

		/**
		 * Send the plugin data when the user has opted in
		 *
		 * @hook ts_tracker_data
		 * @param array $data All data to send to server.
		 *
		 * @return array $plugin_data All data to send to server.
		 */
		public static function wcal_ts_add_plugin_tracking_data( $data ) {

			$plugin_short_name = 'wcal';
			if ( ! isset( $_GET[ $plugin_short_name . '_tracker_nonce' ] ) ) {
				return $data;
			}

			$tracker_option = isset( $_GET[ $plugin_short_name . '_tracker_optin' ] ) ? $plugin_short_name . '_tracker_optin' : ( isset( $_GET[ $plugin_short_name . '_tracker_optout' ] ) ? $plugin_short_name . '_tracker_optout' : '' ); // phpcs:ignore
			if ( '' === $tracker_option || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET[ $plugin_short_name . '_tracker_nonce' ] ) ), $tracker_option ) ) {
				return $data;
			}

			$data = self::wcal_plugin_tracking_data( $data );
			return $data;
		}

		/**
		 * Add admin notice script.
		 */
		public static function ts_admin_notices_scripts() {
			$nonce      = wp_create_nonce( 'tracking_notice' );
			$plugin_url = plugins_url() . '/woocommerce-abandoned-cart';

			wp_enqueue_script(
				'wcal_ts_dismiss_notice',
				$plugin_url . '/assets/js/admin/tyche-dismiss-tracking-notice.js',
				'',
				WCAL_PLUGIN_VERSION . '_' . time(),
				false
			);

			wp_localize_script(
				'wcal_ts_dismiss_notice',
				'wcal_ts_dismiss_notice_params',
				array(
					'ts_prefix_of_plugin' => 'wcal',
					'ts_admin_url'        => admin_url( 'admin-ajax.php' ),
					'tracking_notice'     => $nonce,
				)
			);
		}

		/**
		 * Add tracker completed.
		 */
		public static function init_tracker_completed() {
			header( 'Location: ' . admin_url( 'admin.php?page=woocommerce_ac_page' ) );
			exit;
		}

		/**
		 * Display admin notice on specific page.
		 *
		 * @param array $is_flag Is Flag defailt value true.
		 */
		public static function wcal_ts_tracker_display_notice( $is_flag ) {
			global $current_section;
			if ( isset( $_GET['page'] ) && 'woocommerce_ac_page' === $_GET['page'] ) { // phpcs:ignore
				$is_flag = true;
			}
			return $is_flag;
		}

		/**
		 * Returns plugin data for tracking.
		 *
		 * @param array $data - Generic data related to WP, WC, Theme, Server and so on.
		 * @return array $data - Plugin data included in the original data received.
		 * @since 1.3.0
		 */
		public static function wcal_plugin_tracking_data( $data ) {

			$plugin_data = array(
				'ts_meta_data_table_name' => 'ts_wcal_tracking_meta_data',
				'ts_plugin_name'          => 'Abandoned Cart Lite for WooCommerce',
			);
			// Store abandoned count info.
			$plugin_data['abandoned_orders'] = self::wcal_ts_get_abandoned_order_counts();

			// Store recovred count info.
			$plugin_data['recovered_orders'] = self::wcal_ts_get_recovered_order_counts();

			// store abandoned orders amount.
			$plugin_data['abandoned_orders_amount'] = self::wcal_ts_get_abandoned_order_total_amount();

			// Store recovered count info.
			$plugin_data['recovered_orders_amount'] = self::wcal_ts_get_recovered_order_total_amount();

			// Store abandoned cart emails sent count info.
			$plugin_data['sent_emails'] = self::wcal_ts_get_sent_emails_total_count();

			// Store email template count info.
			$plugin_data['email_templates_data'] = self::wcal_ts_get_email_templates_data();

			// Store only logged-in users abandoned cart count info.
			$plugin_data['logged_in_abandoned_orders'] = self::wcal_ts_get_logged_in_users_abandoned_cart_total_count();

			// Store only logged-in users abandoned cart count info.
			$plugin_data['guest_abandoned_orders'] = self::wcal_ts_get_guest_users_abandoned_cart_total_count();

			// Store only logged-in users abandoned cart amount info.
			$plugin_data['logged_in_abandoned_orders_amount'] = self::wcal_ts_get_logged_in_users_abandoned_cart_total_amount();

			// store only guest users abandoned cart amount.
			$plugin_data['guest_abandoned_orders_amount'] = self::wcal_ts_get_guest_users_abandoned_cart_total_amount();

			// Store only logged-in users recovered cart amount info.
			$plugin_data['logged_in_recovered_orders_amount'] = self::wcal_ts_get_logged_in_users_recovered_cart_total_amount();

			// Store only guest users recovered cart amount.
			$plugin_data['guest_recovered_orders_amount'] = self::wcal_ts_get_guest_users_recovered_cart_total_amount();

			// Get all plugin options info.
			$plugin_data['settings']       = self::wcal_ts_get_all_plugin_options_values();
			$plugin_data['plugin_version'] = WCAL_PLUGIN_VERSION;
			$plugin_data['tracking_usage'] = get_option( 'wcal_allow_tracking' );

			$data ['plugin_data'] = $plugin_data;

			return $data;
		}

		/**
		 * Get abandoned orders counts.
		 *
		 * @globals mixed $wpdb
		 * @return string | int $wcal_order_count
		 * @since 3.9
		 */
		private static function wcal_ts_get_abandoned_order_counts() {
			global $wpdb;
			$wcal_order_count = 0;

			$ac_cutoff_time = get_option( 'ac_lite_cart_abandoned_time' );
			$cut_off_time   = $ac_cutoff_time * 60;
			$current_time   = current_time( 'timestamp' ); // phpcs:ignore
			$compare_time   = $current_time - $cut_off_time;

			$blank_cart_info       = '{"cart":[]}';
			$blank_cart_info_guest = '[]';

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wcal_order_count = $wpdb->get_var(
				$wpdb->prepare(
					'SELECT COUNT(id) FROM `' . $wpdb->prefix . 'ac_abandoned_cart_history_lite` WHERE abandoned_cart_time <= %s AND abandoned_cart_info NOT LIKE %s AND abandoned_cart_info NOT LIKE %s',
					$compare_time,
					'%' . $wpdb->esc_like( $blank_cart_info ) . '%',
					$blank_cart_info_guest
				)
			);

			return $wcal_order_count;
		}

		/**
		 * Get recovered orders counts.
		 *
		 * @globals mixed $wpdb
		 * @return string | int $wcal_recovered_order_count
		 * @since 3.9
		 */
		private static function wcal_ts_get_recovered_order_counts() {

			global $wpdb;
			$wcal_recovered_order_count = 0;

			$ac_cutoff_time = get_option( 'ac_lite_cart_abandoned_time' );
			$cut_off_time   = $ac_cutoff_time * 60;
			$current_time   = current_time( 'timestamp' ); // phpcs:ignore
			$compare_time   = $current_time - $cut_off_time;

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wcal_recovered_order_count = $wpdb->get_var(
				$wpdb->prepare(
					'SELECT COUNT(id) FROM `' . $wpdb->prefix . 'ac_abandoned_cart_history_lite` WHERE recovered_cart > 0 AND abandoned_cart_time <= %s',
					$compare_time
				)
			);

			return $wcal_recovered_order_count;
		}

		/**
		 * Get Total abandoned orders amount.
		 *
		 * @globals mixed $wpdb
		 * @return string | int $wcal_abandoned_orders_amount
		 * @since 3.9
		 */
		private static function wcal_ts_get_abandoned_order_total_amount() {
			global $wpdb;
			$wcal_abandoned_orders_amount = 0;

			$ac_cutoff_time = get_option( 'ac_lite_cart_abandoned_time' );
			$cut_off_time   = $ac_cutoff_time * 60;
			$current_time   = current_time( 'timestamp' ); // phpcs:ignore
			$compare_time   = $current_time - $cut_off_time;

			$blank_cart_info       = '{"cart":[]}';
			$blank_cart_info_guest = '[]';

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wcal_abandoned_query_result = $wpdb->get_results(
				$wpdb->prepare(
					'SELECT abandoned_cart_info FROM `' . $wpdb->prefix . 'ac_abandoned_cart_history_lite` WHERE abandoned_cart_time <= %s AND abandoned_cart_info NOT LIKE %s AND abandoned_cart_info NOT LIKE %s',
					$compare_time,
					'%' . $wpdb->esc_like( $blank_cart_info ) . '%',
					$blank_cart_info_guest
				)
			);

			$wcal_abandoned_orders_amount = self::wcal_get_abandoned_amount( $wcal_abandoned_query_result );

			return $wcal_abandoned_orders_amount;
		}

		/**
		 * Get recovered orders total amount.
		 *
		 * @globals mixed $wpdb
		 * @return string | int $wcal_recovered_orders_amount
		 * @since 3.9
		 */
		private static function wcal_ts_get_recovered_order_total_amount() {

			global $wpdb;
			$wcal_recovered_orders_amount = 0;

			$ac_cutoff_time = get_option( 'ac_lite_cart_abandoned_time' );
			$cut_off_time   = $ac_cutoff_time * 60;
			$current_time   = current_time( 'timestamp' ); // phpcs:ignore
			$compare_time   = $current_time - $cut_off_time;

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wcal_recovered_result = $wpdb->get_results(
				$wpdb->prepare(
					'SELECT recovered_cart FROM `' . $wpdb->prefix . 'ac_abandoned_cart_history_lite` WHERE recovered_cart > 0 AND abandoned_cart_time <= %s',
					$compare_time
				)
			);

			$wcal_recovered_orders_amount = self::wcal_get_recovered_amount( $wcal_recovered_result );

			return $wcal_recovered_orders_amount;
		}

		/**
		 * Get Total abandoned orders amount.
		 *
		 * @globals mixed $wpdb
		 * @param array | object $wcal_abandoned_query_result Abandoned Query Results.
		 * @return string | int $wcal_abandoned_orders_amount
		 * @since 3.9
		 */
		private static function wcal_get_abandoned_amount( $wcal_abandoned_query_result ) {

			$wcal_abandoned_orders_amount = 0;
			foreach ( $wcal_abandoned_query_result as $wcal_abandoned_query_key => $wcal_abandoned_query_value ) {
				$cart_info = json_decode( $wcal_abandoned_query_value->abandoned_cart_info );

				$cart_details = array();
				if ( isset( $cart_info->cart ) ) {
					$cart_details = $cart_info->cart;
				}

				if ( count( get_object_vars( $cart_details ) ) > 0 ) {
					foreach ( $cart_details as $k => $v ) {
						if ( 0 !== $v->line_subtotal_tax && $v->line_subtotal_tax > 0 ) {
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
		 *
		 * @globals mixed $wpdb
		 * @param array | object $wcal_data Data to calculate recovered amount.
		 * @return string | int $wcal_recovered_orders_amount
		 * @since 3.9
		 */
		private static function wcal_get_recovered_amount( $wcal_data ) {

			$wcal_recovered_orders_amount = 0;

			foreach ( $wcal_data as $wcal_data_key => $wcal_data_value ) {

				$order                        = wc_get_order( $wcal_data_value->recovered_cart );
				$wcal_order_total             = $order ? $order->get_total() : 0;
				$wcal_recovered_orders_amount = $wcal_recovered_orders_amount + $wcal_order_total;
			}
			return $wcal_recovered_orders_amount;
		}

		/**
		 * Get sent email total count.
		 *
		 * @globals mixed $wpdb
		 * @return string | int $wcal_sent_emails_count
		 * @since 3.9
		 */
		private static function wcal_ts_get_sent_emails_total_count() {

			global $wpdb;
			$wcal_sent_emails_count = 0;
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wcal_sent_emails_count = $wpdb->get_var( 'SELECT COUNT(id) FROM `' . $wpdb->prefix . 'ac_sent_history_lite` WHERE tem[plate_id <> 0' );
			return $wcal_sent_emails_count;
		}

		/**
		 * Get email templates total count.
		 *
		 * @globals mixed $wpdb
		 * @return array $wcal_templates_data
		 * @since 3.9
		 */
		private static function wcal_ts_get_email_templates_data() {

			global $wpdb;
			$wcal_email_templates_count = 0;
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wcal_email_templates_results = $wpdb->get_results( 'SELECT id, is_active, is_wc_template,frequency, day_or_hour, coupon_code, generate_unique_coupon_code FROM `' . $wpdb->prefix . 'ac_email_templates_lite`' );

			$wcal_email_templates_count = count( $wcal_email_templates_results );

			$wcal_templates_data                     = array();
			$wcal_templates_data ['total_templates'] = $wcal_email_templates_count;

			foreach ( $wcal_email_templates_results as $wcal_email_templates_results_key => $wcal_email_templates_results_value ) {

				$wcal_template_time = $wcal_email_templates_results_value->frequency . ' ' . $wcal_email_templates_results_value->day_or_hour;

				// phpcs:ignore WordPress.DB.DirectDatabaseQuery
				$wcal_template_sent_count = $wpdb->get_var(
					$wpdb->prepare(
						'SELECT COUNT(id) FROM `' . $wpdb->prefix . 'ac_sent_history_lite` WHERE template_id = %s',
						$wcal_email_templates_results_value->id
					)
				);

				$wcal_templates_data [ 'template_id_' . $wcal_email_templates_results_value->id ] ['is_activate']                 = ( '1' === $wcal_email_templates_results_value->is_active ) ? 'Active' : 'Deactive';
				$wcal_templates_data [ 'template_id_' . $wcal_email_templates_results_value->id ] ['is_wc_template']              = ( '1' === $wcal_email_templates_results_value->is_wc_template ) ? 'Yes' : 'No';
				$wcal_templates_data [ 'template_id_' . $wcal_email_templates_results_value->id ] ['template_time']               = $wcal_template_time;
				$wcal_templates_data [ 'template_id_' . $wcal_email_templates_results_value->id ] ['total_email_sent']            = $wcal_template_sent_count;
				$wcal_templates_data [ 'template_id_' . $wcal_email_templates_results_value->id ] ['coupon_code']                 = $wcal_email_templates_results_value->coupon_code;
				$wcal_templates_data [ 'template_id_' . $wcal_email_templates_results_value->id ] ['generate_unique_coupon_code'] = $wcal_email_templates_results_value->generate_unique_coupon_code;
			}

			return $wcal_templates_data;
		}

		/**
		 * Get logged-in users total abandoned count.
		 *
		 * @globals mixed $wpdb
		 * @return string | int $wcal_logged_in_user_query_count
		 * @since 3.9
		 */
		private static function wcal_ts_get_logged_in_users_abandoned_cart_total_count() {

			global $wpdb;
			$wcal_logged_in_user_query_count = 0;

			$ac_cutoff_time = get_option( 'ac_lite_cart_abandoned_time' );
			$cut_off_time   = $ac_cutoff_time * 60;
			$current_time   = current_time( 'timestamp' ); // phpcs:ignore
			$compare_time   = $current_time - $cut_off_time;

			$blank_cart_info       = '{"cart":[]}';
			$blank_cart_info_guest = '[]';

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wcal_logged_in_user_query_count = $wpdb->get_var(
				$wpdb->prepare(
					'SELECT COUNT(id) FROM `' . $wpdb->prefix . 'ac_abandoned_cart_history_lite` WHERE abandoned_cart_time <= %s AND abandoned_cart_info NOT LIKE %s AND abandoned_cart_info NOT LIKE %s AND user_id < 63000000 AND user_id != 0',
					$compare_time,
					'%' . $wpdb->esc_like( $blank_cart_info ) . '%',
					$blank_cart_info_guest
				)
			);

			return $wcal_logged_in_user_query_count;
		}

		/**
		 * Get Guest users total abandoned count.
		 *
		 * @globals mixed $wpdb
		 * @return string | int $wcal_guest_user_query_count
		 * @since 3.9
		 */
		private static function wcal_ts_get_guest_users_abandoned_cart_total_count() {
			global $wpdb;
			$wcal_guest_user_query_count = 0;

			$ac_cutoff_time = get_option( 'ac_lite_cart_abandoned_time' );
			$cut_off_time   = $ac_cutoff_time * 60;
			$current_time   = current_time( 'timestamp' ); // phpcs:ignore
			$compare_time   = $current_time - $cut_off_time;

			$blank_cart_info       = '{"cart":[]}';
			$blank_cart_info_guest = '[]';

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wcal_guest_user_query_count = $wpdb->get_var(
				$wpdb->prepare(
					'SELECT COUNT(id) FROM `' . $wpdb->prefix . 'ac_abandoned_cart_history_lite` WHERE abandoned_cart_time <= %s AND abandoned_cart_info NOT LIKE %s AND abandoned_cart_info NOT LIKE %s AND user_id >= 63000000 AND user_id != 0',
					$compare_time,
					'%' . $wpdb->esc_like( $blank_cart_info ) . '%',
					$blank_cart_info_guest
				)
			);

			return $wcal_guest_user_query_count;
		}

		/**
		 * Get logged-in users total abandoned amount.
		 *
		 * @globals mixed $wpdb
		 * @return string | int $wcal_abandoned_orders_amount
		 * @since 3.9
		 */
		private static function wcal_ts_get_logged_in_users_abandoned_cart_total_amount() {

			global $wpdb;
			$wcal_abandoned_orders_amount = 0;

			$ac_cutoff_time = get_option( 'ac_lite_cart_abandoned_time' );
			$cut_off_time   = $ac_cutoff_time * 60;
			$current_time   = current_time( 'timestamp' ); // phpcs:ignore
			$compare_time   = $current_time - $cut_off_time;

			$blank_cart_info       = '{"cart":[]}';
			$blank_cart_info_guest = '[]';

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wcal_abandoned_query_result = $wpdb->get_results(
				$wpdb->prepare(
					'SELECT abandoned_cart_info FROM `' . $wpdb->prefix . 'ac_abandoned_cart_history_lite` WHERE abandoned_cart_time <= %s AND abandoned_cart_info NOT LIKE %s AND abandoned_cart_info NOT LIKE %s AND user_id < 63000000 AND user_id != 0',
					$compare_time,
					'%' . $wpdb->esc_like( $blank_cart_info ) . '%',
					$blank_cart_info_guest
				)
			);

			$wcal_abandoned_orders_amount = self::wcal_get_abandoned_amount( $wcal_abandoned_query_result );

			return $wcal_abandoned_orders_amount;
		}

		/**
		 * Get Guest users total abandoned amount.
		 *
		 * @globals mixed $wpdb
		 * @return string | int $wcal_abandoned_orders_amount
		 * @since 3.9
		 */
		private static function wcal_ts_get_guest_users_abandoned_cart_total_amount() {

			global $wpdb;
			$wcal_abandoned_orders_amount = 0;

			$ac_cutoff_time = get_option( 'ac_lite_cart_abandoned_time' );
			$cut_off_time   = $ac_cutoff_time * 60;
			$current_time   = current_time( 'timestamp' ); // phpcs:ignore
			$compare_time   = $current_time - $cut_off_time;

			$blank_cart_info       = '{"cart":[]}';
			$blank_cart_info_guest = '[]';

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wcal_abandoned_query_result = $wpdb->get_results(
				$wpdb->prepare(
					'SELECT abandoned_cart_info FROM `' . $wpdb->prefix . 'ac_abandoned_cart_history_lite` WHERE abandoned_cart_time <= %s AND abandoned_cart_info NOT LIKE %s AND abandoned_cart_info NOT LIKE %s AND user_id >= 63000000 AND user_id != 0',
					$compare_time,
					'%' . $blank_cart_info . '%',
					$blank_cart_info_guest
				)
			);

			$wcal_abandoned_orders_amount = self::wcal_get_abandoned_amount( $wcal_abandoned_query_result );

			return $wcal_abandoned_orders_amount;
		}

		/**
		 * Get logged-in users total recovered amount.
		 *
		 * @globals mixed $wpdb
		 * @return string | int $wcal_recovered_orders_amount
		 * @since 3.9
		 */
		private static function wcal_ts_get_logged_in_users_recovered_cart_total_amount() {

			global $wpdb;
			$wcal_recovered_orders_amount = 0;

			$ac_cutoff_time = get_option( 'ac_lite_cart_abandoned_time' );
			$cut_off_time   = $ac_cutoff_time * 60;
			$current_time   = current_time( 'timestamp' ); // phpcs:ignore
			$compare_time   = $current_time - $cut_off_time;

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wcal_recovered_order_amount_result = $wpdb->get_results(
				$wpdb->prepare(
					'SELECT recovered_cart FROM `' . $wpdb->prefix . 'ac_abandoned_cart_history_lite` WHERE recovered_cart > 0 AND abandoned_cart_time <= %s AND user_id < 63000000 AND user_id != 0',
					$compare_time
				)
			);

			$wcal_recovered_orders_amount = self::wcal_get_recovered_amount( $wcal_recovered_order_amount_result );

			return $wcal_recovered_orders_amount;
		}

		/**
		 * Get Guest users total recovered amount.
		 *
		 * @globals mixed $wpdb
		 * @return string | int $wcal_recovered_orders_amount
		 * @since 3.9
		 */
		private static function wcal_ts_get_guest_users_recovered_cart_total_amount() {

			global $wpdb;
			$wcal_recovered_orders_amount = 0;

			$ac_cutoff_time = get_option( 'ac_lite_cart_abandoned_time' );
			$cut_off_time   = $ac_cutoff_time * 60;
			$current_time   = current_time( 'timestamp' ); // phpcs:ignore
			$compare_time   = $current_time - $cut_off_time;

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wcal_recovered_result = $wpdb->get_results(
				$wpdb->prepare(
					'SELECT recovered_cart FROM `' . $wpdb->prefix . 'ac_abandoned_cart_history_lite` WHERE recovered_cart > 0 AND abandoned_cart_time <= %s AND user_id >= 63000000 AND user_id != 0',
					$compare_time
				)
			);

			$wcal_recovered_orders_amount = self::wcal_get_recovered_amount( $wcal_recovered_result );

			return $wcal_recovered_orders_amount;
		}

		/**
		 * Get all options of the plugin.
		 *
		 * @return array
		 * @since 3.9
		 */
		private static function wcal_ts_get_all_plugin_options_values() {

			return array(
				'wcal_cart_cut_off_time'           => get_option( 'ac_lite_cart_abandoned_time' ),
				'wcal_admin_recovery_email'        => get_option( 'ac_lite_email_admin_on_recovery' ),
				'wcal_capture_visitors_cart'       => get_option( 'ac_lite_track_guest_cart_from_cart_page' ),
				'wcal_delete_abandoned_order_days' => get_option( 'ac_lite_delete_abandoned_order_days' ),
				'wcal_enable_gdpr_consent'         => get_option( 'wcal_enable_gdpr_consent' ),
				'wcal_delete_coupon_data'          => get_option( 'wcal_delete_coupon_data' ),
				'wcal_auto_login_users'            => get_option( 'wcal_auto_login_users' ),
			);
		}

		/**
		 * Get data when Admin dont want to share information.
		 *
		 * @param array $params Data when user opts out.
		 * @return array $params
		 * @since 3.9
		 */
		public static function ts_get_data_for_opt_out( $params ) {

			$plugin_data['ts_meta_data_table_name'] = 'ts_wcal_tracking_meta_data';
			$plugin_data['ts_plugin_name']          = 'Abandoned Cart Lite for WooCommerce';

			$params['plugin_data'] = $plugin_data;

			return $params;
		}
	}

endif;

$wcal_data_tracking = new Wcal_Data_Tracking();
