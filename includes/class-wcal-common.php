<?php
/**
 * Abandoned Cart Lite for WooCommerce
 *
 * @author  Tyche Softwares
 * @package abandoned-cart-lite
 */

/**
 * It will have all the common funtions for the plugin.
 *
 * @since 2.5.2
 */
class wcal_common { // phpcs:ignore

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

			$wcal_order_total             = get_post_meta( $wcal_data_value->recovered_cart, '_order_total', true );
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
		$wcal_sent_emails_count = $wpdb->get_var( 'SELECT COUNT(id) FROM `' . $wpdb->prefix . 'ac_sent_history_lite`' );
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
		$wcal_email_templates_results = $wpdb->get_results( 'SELECT id, is_active, is_wc_template,frequency, day_or_hour FROM `' . $wpdb->prefix . 'ac_email_templates_lite`' );

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

			$wcal_templates_data [ 'template_id_' . $wcal_email_templates_results_value->id ] ['is_activate']      = ( 1 === $wcal_email_templates_results_value->is_active ) ? 'Active' : 'Deactive';
			$wcal_templates_data [ 'template_id_' . $wcal_email_templates_results_value->id ] ['is_wc_template']   = ( 1 === $wcal_email_templates_results_value->is_wc_template ) ? 'Yes' : 'No';
			$wcal_templates_data [ 'template_id_' . $wcal_email_templates_results_value->id ] ['template_time']    = $wcal_template_time;
			$wcal_templates_data [ 'template_id_' . $wcal_email_templates_results_value->id ] ['total_email_sent'] = $wcal_template_sent_count;
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
			'wcal_cart_cut_off_time'     => get_option( 'ac_lite_cart_abandoned_time' ),
			'wcal_admin_recovery_email'  => get_option( 'ac_lite_email_admin_on_recovery' ),
			'wcal_capture_visitors_cart' => get_option( 'ac_lite_track_guest_cart_from_cart_page' ),
		);
	}


	/**
	 * If admin allow to track the data the it will gather all information and return back.
	 *
	 * @hook ts_tracker_data
	 * @param array $data Data to be tracked.
	 * @return array $data
	 * @since 3.9
	 */
	public static function ts_add_plugin_tracking_data( $data ) {

		if ( isset( $_GET['wcal_tracker_optin'] ) && isset( $_GET['wcal_tracker_nonce'] ) && wp_verify_nonce( sanitize_key( $_GET['wcal_tracker_nonce'] ), 'wcal_tracker_optin' ) ) {

			$plugin_data['ts_meta_data_table_name'] = 'ts_wcal_tracking_meta_data';

			$plugin_data['ts_plugin_name'] = 'Abandoned Cart Lite for WooCommerce';

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
			$plugin_data['plugin_version'] = self::wcal_get_version();
			$plugin_data['tracking_usage'] = get_option( 'wcal_allow_tracking' );

			$data ['plugin_data'] = $plugin_data;
		}
		return $data;
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

	/**
	 * It will fetch the total count for the abandoned cart section.
	 *
	 * @param string $get_section_result Name of the section for which we need result.
	 * @return string | int $return_abandoned_count
	 * @globals mixed $wpdb
	 * @since 2.5.2
	 */
	public static function wcal_get_abandoned_order_count( $get_section_result ) {
		global $wpdb;
		$return_abandoned_count = 0;
		$blank_cart_info        = '{"cart":[]}';
		$blank_cart_info_guest  = '[]';
		$blank_cart             = '""';
		$ac_cutoff_time         = get_option( 'ac_lite_cart_abandoned_time' );
		$cut_off_time           = intval( $ac_cutoff_time ) * 60;
		$current_time           = current_time( 'timestamp' ); // phpcs:ignore
		$compare_time           = $current_time - $cut_off_time;

		switch ( $get_section_result ) {
			case 'wcal_all_abandoned':
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery
				$return_abandoned_count = $wpdb->get_var(
					$wpdb->prepare(
						'SELECT COUNT(`id`) as cnt FROM `' . $wpdb->prefix . 'ac_abandoned_cart_history_lite` WHERE ( user_type = "REGISTERED" AND abandoned_cart_info NOT LIKE %s AND abandoned_cart_info NOT LIKE %s AND abandoned_cart_time <= %s AND recovered_cart = 0 AND cart_ignored <> "1") OR ( user_type = "GUEST" AND abandoned_cart_info NOT LIKE %s AND abandoned_cart_info NOT LIKE %s AND abandoned_cart_info NOT LIKE %s AND abandoned_cart_time <= %s AND recovered_cart = 0 AND cart_ignored <> "1") ORDER BY recovered_cart desc',
						'%' . $blank_cart_info . '%',
						$blank_cart,
						$compare_time,
						$blank_cart_info_guest,
						'%' . $blank_cart_info . '%',
						$blank_cart,
						$compare_time
					)
				);
				break;

			case 'wcal_all_registered':
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery
				$return_abandoned_count = $wpdb->get_var(
					$wpdb->prepare(
						'SELECT COUNT(`id`) FROM `' . $wpdb->prefix . 'ac_abandoned_cart_history_lite` WHERE ( user_type = "REGISTERED" AND abandoned_cart_info NOT LIKE %s AND abandoned_cart_info NOT LIKE %s AND abandoned_cart_time <= %s AND recovered_cart = 0 AND cart_ignored <> "1") ORDER BY recovered_cart desc',
						'%' . $blank_cart_info . '%',
						$blank_cart,
						$compare_time
					)
				);
				break;

			case 'wcal_all_guest':
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery
				$return_abandoned_count = $wpdb->get_var(
					$wpdb->prepare(
						'SELECT COUNT(`id`) FROM `' . $wpdb->prefix . 'ac_abandoned_cart_history_lite` WHERE ( user_type = "GUEST" AND abandoned_cart_info NOT LIKE %s AND abandoned_cart_info NOT LIKE %s AND abandoned_cart_info NOT LIKE %s AND abandoned_cart_time <= %s AND recovered_cart = 0 AND user_id >= 63000000 AND cart_ignored <> "1") ORDER BY recovered_cart desc',
						$blank_cart_info_guest,
						'%' . $blank_cart_info . '%',
						$blank_cart,
						$compare_time
					)
				);
				break;

			case 'wcal_all_visitor':
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery
				$return_abandoned_count = $wpdb->get_var(
					$wpdb->prepare(
						'SELECT COUNT(`id`) FROM `' . $wpdb->prefix . 'ac_abandoned_cart_history_lite` WHERE ( user_type = "GUEST" AND abandoned_cart_info NOT LIKE %s AND abandoned_cart_info NOT LIKE %s AND abandoned_cart_info NOT LIKE %s AND abandoned_cart_time <= %s AND recovered_cart = 0  AND user_id = 0 AND cart_ignored <> "1") ORDER BY recovered_cart desc',
						$blank_cart_info_guest,
						'%' . $blank_cart_info . '%',
						$blank_cart,
						$compare_time
					)
				);
				break;

			default:
				// code...
				break;
		}
		return $return_abandoned_count;
	}


	/**
	 * This function returns the Abandoned Cart Lite plugin version number.
	 *
	 * @return string $plugin_version
	 * @since 2.5.2
	 */
	public static function wcal_get_version() {
		$plugin_version   = '';
		$wcap_plugin_dir  = dirname( dirname( __FILE__ ) );
		$wcap_plugin_dir .= '/woocommerce-ac.php';

		$plugin_data = get_file_data( $wcap_plugin_dir, array( 'Version' => 'Version' ) );
		if ( ! empty( $plugin_data['Version'] ) ) {
			$plugin_version = $plugin_data['Version'];
		}
		return $plugin_version;
	}

	/**
	 * This function returns the plugin url.
	 *
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
	public static function update_templates_table() {

		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		return $wpdb->query( 'ALTER TABLE `' . $wpdb->prefix . 'ac_email_templates_lite` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci' );
	}

	/**
	 * This function will show a dismissible success message after DB update is completed.
	 *
	 * @since 4.8
	 */
	public static function show_update_success() {
		?>

		<div class="notice notice-success is-dismissible"> 
			<p><strong><?php esc_attr_e( 'Database Updated Successfully', 'woocommerce-abandoned-cart' ); ?></strong></p>
		</div>

		<?php
	}

	/**
	 * This function will show a dismissible success message after DB update is completed.
	 *
	 * @since 4.8
	 */
	public static function show_update_failure() {
		?>

		<div class="notice notice-error is-dismissible"> 
			<p><strong><?php esc_attr_e( 'Database Update Failed. Please try again after sometime', 'woocommerce-abandoned-cart' ); ?></strong></p>
		</div>

		<?php
	}
	/**
	 * Returns an array of customer billing information.
	 * Should be called only for registered users.
	 *
	 * @param integer $user_id - User ID.
	 * @return array $billing_details - Contains Billing Address Details
	 * @global $woocommerce
	 * @since  4.9
	 */
	public static function wcal_get_billing_details( $user_id ) {
		global $woocommerce;

		$billing_details = array();

		$user_billing_company_temp = get_user_meta( $user_id, 'billing_company' );
		$user_billing_company      = '';
		if ( isset( $user_billing_company_temp[0] ) ) {
			$user_billing_company = $user_billing_company_temp[0];
		}
		$billing_details['billing_company'] = $user_billing_company;

		$user_billing_address_1_temp = get_user_meta( $user_id, 'billing_address_1' );
		$user_billing_address_1      = '';
		if ( isset( $user_billing_address_1_temp[0] ) ) {
			$user_billing_address_1 = $user_billing_address_1_temp[0];
		}
		$billing_details['billing_address_1'] = $user_billing_address_1;

		$user_billing_address_2_temp = get_user_meta( $user_id, 'billing_address_2' );
		$user_billing_address_2      = '';
		if ( isset( $user_billing_address_2_temp[0] ) ) {
			$user_billing_address_2 = $user_billing_address_2_temp[0];
		}
		$billing_details['billing_address_2'] = $user_billing_address_2;

		$user_billing_city_temp = get_user_meta( $user_id, 'billing_city' );
		$user_billing_city      = '';
		if ( isset( $user_billing_city_temp[0] ) ) {
			$user_billing_city = $user_billing_city_temp[0];
		}
		$billing_details['billing_city'] = $user_billing_city;

		$user_billing_postcode_temp = get_user_meta( $user_id, 'billing_postcode' );
		$user_billing_postcode      = '';
		if ( isset( $user_billing_postcode_temp[0] ) ) {
			$user_billing_postcode = $user_billing_postcode_temp[0];
		}
		$billing_details['billing_postcode'] = $user_billing_postcode;

		$user_billing_country_temp = get_user_meta( $user_id, 'billing_country' );
		$user_billing_country      = '';
		if ( isset( $user_billing_country_temp[0] ) && '' !== $user_billing_country_temp[0] ) {
			$user_billing_country = $user_billing_country_temp[0];
			if ( isset( $woocommerce->countries->countries[ $user_billing_country ] ) || '' !== ( $woocommerce->countries->countries[ $user_billing_country ] ) ) {
				$user_billing_country = WC()->countries->countries[ $user_billing_country ];
			} else {
				$user_billing_country = '';
			}
		}
		$billing_details['billing_country'] = $user_billing_country;

		$user_billing_state_temp = get_user_meta( $user_id, 'billing_state' );
		$user_billing_state      = '';
		if ( isset( $user_billing_state_temp[0] ) ) {
			$user_billing_state = $user_billing_state_temp[0];
			if ( isset( $woocommerce->countries->states[ $user_billing_country_temp[0] ][ $user_billing_state ] ) ) {
				$user_billing_state = WC()->countries->states[ $user_billing_country_temp[0] ][ $user_billing_state ];
			} else {
				$user_billing_state = '';
			}
		}
		$billing_details['billing_state'] = $user_billing_state;

		return $billing_details;
	}


	/**
	 * Returns the Item Name, Qty and Total for any given product
	 * in the WC Cart.
	 *
	 * @param stdClass $v - Cart Information from WC()->cart.
	 * @return array $item_details - Item Data
	 * @global $woocommerce
	 * @since  4.9
	 */
	public static function wcal_get_cart_details( $v ) {
		global $woocommerce;

		$item_subtotal  = 0;
		$item_total     = 0;
		$quantity_total = 0;

		$item_details = array();

		$quantity_total = $v->quantity;
		$product_id     = $v->product_id;
		$product        = wc_get_product( $product_id );
		if ( $product ) {
			$prod_name    = get_post( $product_id );
			$product_name = $prod_name->post_title;

			if ( isset( $v->variation_id ) && '' !== $v->variation_id ) {
				$variation_id = $v->variation_id;
				$variation    = wc_get_product( $variation_id );

				if ( false !== $variation ) {
					$name        = $variation->get_formatted_name();
					$explode_all = explode( '&ndash;', $name );
					if ( version_compare( $woocommerce->version, '3.0.0', '>=' ) ) {
						$wcap_sku = '';
						if ( $variation->get_sku() ) {
							$wcap_sku = 'SKU: ' . $variation->get_sku() . '<br>';
						}
						$wcap_get_formatted_variation = wc_get_formatted_variation( $variation, true );

						$add_product_name = $product_name . ' - ' . $wcap_sku . $wcap_get_formatted_variation;

						$pro_name_variation = (array) $add_product_name;
					} else {
						$pro_name_variation = array_slice( $explode_all, 1, -1 );
					}
					$product_name_with_variable = '';
					$explode_many_varaition     = array();
					foreach ( $pro_name_variation as $pro_name_variation_key => $pro_name_variation_value ) {
						$explode_many_varaition = explode( ',', $pro_name_variation_value );
						if ( ! empty( $explode_many_varaition ) ) {
							foreach ( $explode_many_varaition as $explode_many_varaition_key => $explode_many_varaition_value ) {
								$product_name_with_variable = $product_name_with_variable . html_entity_decode( $explode_many_varaition_value ) . '<br>';
							}
						} else {
							$product_name_with_variable = $product_name_with_variable . html_entity_decode( $explode_many_varaition_value ) . '<br>';
						}
					}
					$product_name = $product_name_with_variable;
				}
			}
			$item_subtotal = 0;
			// Item subtotal is calculated as product total including taxes.
			if ( 0 !== $v->line_subtotal_tax && $v->line_subtotal_tax > 0 ) {
				$item_subtotal = $item_subtotal + $v->line_total + $v->line_subtotal_tax;
			} else {
				$item_subtotal = $item_subtotal + $v->line_total;
			}
			// Line total.
			$item_total    = $item_subtotal;
			$item_subtotal = $item_subtotal / $quantity_total;
			$item_total    = wc_price( $item_total );
			$item_subtotal = wc_price( $item_subtotal );

			$item_details['product_name']         = $product_name;
			$item_details['item_total_formatted'] = $item_subtotal;
			$item_details['item_total']           = $item_total;
			$item_details['qty']                  = $quantity_total;
		} else {
			$item_details['product_name']         = __( 'This product no longer exists', 'woocommerce-abandoned-cart' );
			$item_details['item_total_formatted'] = '';
			$item_details['item_total']           = '';
			$item_details['qty']                  = '';
		}

		return $item_details;
	}

	/**
	 * Set Cart Session variables
	 *
	 * @param string $session_key Key of the session.
	 * @param string $session_value Value of the session.
	 * @since 7.11.0
	 */
	public static function wcal_set_cart_session( $session_key, $session_value ) {
		WC()->session->set( $session_key, $session_value );
	}

	/**
	 * Get Cart Session variables
	 *
	 * @param string $session_key Key of the session.
	 * @return mixed Value of the session.
	 * @since 7.11.0
	 */
	public static function wcal_get_cart_session( $session_key ) {
		if ( ! is_object( WC()->session ) ) {
			return false;
		}
		return WC()->session->get( $session_key );
	}

	/**
	 * Delete Cart Session variables
	 *
	 * @param string $session_key Key of the session.
	 * @since 7.11.0
	 */
	public static function wcal_unset_cart_session( $session_key ) {
		WC()->session->__unset( $session_key );
	}

	/**
	 * Delete the AC record and create a user meta record (for registered users)
	 * when the user chooses to opt out of cart tracking.
	 *
	 * @since 5.5
	 */
	public static function wcal_gdpr_refused() {

		$abandoned_cart_id = self::wcal_get_cart_session( 'abandoned_cart_id_lite' );

		global $wpdb;

		if ( isset( $abandoned_cart_id ) && $abandoned_cart_id > 0 ) {
			// Fetch the user ID - if greater than 0, we need to check & delete guest table record is applicable.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$user_id = $wpdb->get_var(
				$wpdb->prepare(
					'SELECT user_id FROM `' . $wpdb->prefix . 'ac_abandoned_cart_history_lite` WHERE id = %d',
					$abandoned_cart_id
				)
			);

			if ( $user_id >= 63000000 ) { // Guest user.
				// Delete the guest record.
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery
				$wpdb->delete( $wpdb->prefix . 'ac_guest_abandoned_cart_history_lite', array( 'id' => $user_id ) );
			} else { // Registered cart.
				// Save the user choice of not being tracked.
				add_user_meta( $user_id, 'wcal_gdpr_tracking_choice', 0 );
			}
			// add in the session, that the user has refused tracking.
			self::wcal_set_cart_session( 'wcal_cart_tracking_refused', 'yes' );

			// Finally delete the cart history record.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->delete( $wpdb->prefix . 'ac_abandoned_cart_history_lite', array( 'id' => $abandoned_cart_id ) );
		}
	}

	/**
	 * Returns formatted price.
	 *
	 * @param float  $price - Price to be formatted.
	 * @param string $currency - Currency.
	 * @return string $price - Formatted price with currency symbol.
	 * @since 5.6
	 */
	public static function wcal_get_price( $price, $currency ) {

		if ( function_exists( 'icl_object_id' ) && isset( $currency ) && '' !== $currency ) {
			return wc_price( $price, array( 'currency' => $currency ) );
		} else {
			return wc_price( $price );
		}
	}

	/**
	 * Returns the user role for registered users.
	 *
	 * @param int $uid - user ID.
	 * @return array $roles - List of roles.
	 * @since 5.6
	 */
	public static function wcal_get_user_role( $uid ) {
		global $wpdb;
		$role = $wpdb->get_var( // phpcs:ignore
			$wpdb->prepare(
				'SELECT meta_value FROM ' . $wpdb->usermeta . ' WHERE meta_key = %s AND user_id = %d',
				'wp_capabilities',
				(int) $uid
			)
		);

		if ( ! $role ) {
			return '';
		}
		$rarr = unserialize( $role ); // phpcs:ignore

		$roles = is_array( $rarr ) ? array_keys( $rarr ) : array( 'non-user' );

		/**
		 * When store have the wpml it have so many user roles to fix the user role for admin we have applied this fix.
		 */
		if ( in_array( 'administrator', $roles, true ) ) {

			$roles[0] = 'administrator';
		}

		return ucfirst( $roles[0] );
	}

	/**
	 * Return the template ID & Frequency of the last active email reminder.
	 *
	 * @return array Last Active email template ID & frequency.
	 * @since 5.8.2
	 */
	public static function wcal_get_last_email_template() {
		global $wpdb;
		$get_active = $wpdb->get_results( // phpcs:ignore
			"SELECT id, frequency, day_or_hour FROM `" . $wpdb->prefix . "ac_email_templates_lite` WHERE is_active = '1' ORDER BY `day_or_hour` DESC, `frequency` ASC" //phpcs:ignore
		);
		$hour_seconds     = 3600; // 60 * 60
		$day_seconds      = 86400; // 24 * 60 * 60
		$list_frequencies = array();
		foreach ( $get_active as $active ) {
			switch ( $active->day_or_hour ) {
				case 'Days':
					$template_freq = $active->frequency * $day_seconds;
					break;
				case 'Hours':
					$template_freq = $active->frequency * $hour_seconds;
					break;
			}
			$list_frequencies[ $active->id ] = (int) $template_freq;
		}

		arsort( $list_frequencies, SORT_NUMERIC );
		reset( $list_frequencies );
		$template_id = key( $list_frequencies );

		return array(
			$template_id => array_shift( $list_frequencies ),
		);
	}
}
