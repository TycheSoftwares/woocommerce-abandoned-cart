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

			$wcal_templates_data [ 'template_id_' . $wcal_email_templates_results_value->id ] ['is_activate']                 = ( 1 === $wcal_email_templates_results_value->is_active ) ? 'Active' : 'Deactive';
			$wcal_templates_data [ 'template_id_' . $wcal_email_templates_results_value->id ] ['is_wc_template']              = ( 1 === $wcal_email_templates_results_value->is_wc_template ) ? 'Yes' : 'No';
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
				case 'Minutes':
					$template_freq = $active->frequency * 60;
					break;
				case 'Days':
					$template_freq = $active->frequency * $day_seconds;
					break;
				case 'Hours':
					$template_freq = $active->frequency * $hour_seconds;
					break;
			}

			if ( ! isset( $template_freq ) ) {
				continue;
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
	/**
	 * Check if the template has coupon code tag and merge
	 *
	 * @param string $email_body_template  - the content to be replaced.
	 * @param object $results_template_value - the template that's being sent.
	 */
	public static function wcal_check_and_replace_email_tag( $email_body_template, $results_template_value ) {
		$coupon_code_to_apply = '';
		if ( stripos( $email_body_template, '{{coupon.code}}' ) ) {
			$discount_details['discount_expiry']      = $results_template_value->discount_expiry;
			$discount_details['discount_type']        = $results_template_value->discount_type;
			$discount_details['discount_shipping']    = $results_template_value->discount_shipping;
			$discount_details['individual_use']       = $results_template_value->individual_use;
			$discount_details['discount_amount']      = $results_template_value->discount;
			$discount_details['generate_unique_code'] = $results_template_value->generate_unique_coupon_code;
			$default_template                         = $results_template_value->default_template;

			$coupon_id   = isset( $results_template_value->coupon_code ) ? $results_template_value->coupon_code : '';
			$coupon_code = '';
			if ( '' !== $coupon_id ) {
				$coupon_to_apply = get_post( $coupon_id, ARRAY_A );
				$coupon_code     = $coupon_to_apply['post_title'];
			}

			$coupon_code_to_apply = self::wcal_get_coupon_email( $discount_details, $coupon_code, $default_template );
			$email_body_template  = str_ireplace( '{{coupon.code}}', $coupon_code_to_apply, $email_body_template );
		}
		return array( $email_body_template, $coupon_code_to_apply );
	}
	/**
	 * Get the coupon which will be added in the email template.
	 *
	 * @param array  $discount_details - Contains coupon details such as discount amount, type etc.
	 * @param string $coupon_code - Parent coupon code.
	 * @param int    $default_template - Email template is default - 1|0.
	 * @return string $coupon_code_to_apply
	 * @since 8.9.0
	 */
	public static function wcal_get_coupon_email( $discount_details, $coupon_code, $default_template ) {

		$discount_expiry         = $discount_details['discount_expiry'];
		$discount_expiry_explode = explode( '-', $discount_expiry );
		$expiry_date_extend      = '';
		if ( '' !== $discount_expiry_explode[0] && '0' !== $discount_expiry_explode[0] ) {
			$discount_expiry    = str_replace( '-', ' ', $discount_expiry );
			$discount_expiry    = ' +' . $discount_expiry;
			$expiry_date_extend = strtotime( $discount_expiry );
		}

		$coupon_post_meta     = '';
		$discount_type        = $discount_details['discount_type'];
		$expiry_date          = apply_filters( 'wcal_coupon_expiry_date', $expiry_date_extend );
		$coupon_code_to_apply = '';
		$discount_shipping    = $discount_details['discount_shipping'];
		$individual_use       = '1' === $discount_details['individual_use'] ? 'yes' : 'no';
		$discount_amount      = $discount_details['discount_amount'];
		$generate_unique_code = $discount_details['generate_unique_code'];

		if ( '1' === $generate_unique_code && '' === $coupon_code ) {
				$coupon_post_meta     = apply_filters( 'wcal_update_unique_coupon_post_meta_email', $coupon_code, $coupon_post_meta );
				$coupon_code_to_apply = self::wcal_wp_coupon_code( $discount_amount, $discount_type, $expiry_date, $discount_shipping, $coupon_post_meta, $individual_use );
		} else {
			$coupon_code_to_apply = $coupon_code;
		}
		return $coupon_code_to_apply;
	}

	/**
	 * It will create the unique coupon code.
	 *
	 * @param int            $discount_amt - Discount amount.
	 * @param string         $get_discount_type - Discount type.
	 * @param date           $get_expiry_date - Expiry date.
	 * @param string         $discount_shipping - Shipping dicsount.
	 * @param array | object $coupon_post_meta - Data of Parent coupon.
	 * @param string         $individual_use - Force individual use.
	 * @return string $final_string 12 Digit unique coupon code name
	 * @since 2.3.6
	 */
	public static function wcal_wp_coupon_code( $discount_amt, $get_discount_type, $get_expiry_date, $discount_shipping = 'no', $coupon_post_meta = array(), $individual_use = 'yes' ) {
		$ten_random_string         = self::wp_random_string();
		$first_two_digit           = wp_rand( 0, 99 );
		$final_string              = $first_two_digit . $ten_random_string;
		$datetime                  = $get_expiry_date;
		$coupon_code               = $final_string;
		$coupon_product_categories = isset( $coupon_post_meta['product_categories'][0] ) && '' !== $coupon_post_meta['product_categories'][0] ? unserialize( $coupon_post_meta['product_categories'] [0] ) : array(); //phpcs:ignore

		$coupon_exculde_product_categories = isset( $coupon_post_meta['exclude_product_categories'][0] ) && '' !== $coupon_post_meta['exclude_product_categories'][0] ? unserialize( $coupon_post_meta['exclude_product_categories'][0] ) : array(); //phpcs:ignore

		$coupon_product_ids = isset( $coupon_post_meta['product_ids'][0] ) && '' !== $coupon_post_meta['product_ids'][0] ? $coupon_post_meta['product_ids'][0] : '';

		$coupon_exclude_product_ids = isset( $coupon_post_meta['exclude_product_ids'][0] ) && '' !== $coupon_post_meta['exclude_product_ids'][0] ? $coupon_post_meta['exclude_product_ids'][0] : '';

		$coupon_free_shipping = isset( $coupon_post_meta['free_shipping'][0] ) && '' !== $coupon_post_meta['free_shipping'][0] ? $coupon_post_meta['free_shipping'][0] : $discount_shipping;

		$coupon_minimum_amount = isset( $coupon_post_meta['minimum_amount'][0] ) && '' !== $coupon_post_meta['minimum_amount'][0] ? $coupon_post_meta['minimum_amount'][0] : '';

		$coupon_maximum_amount = isset( $coupon_post_meta['maximum_amount'][0] ) && '' !== $coupon_post_meta['maximum_amount'][0] ? $coupon_post_meta['maximum_amount'][0] : '';

		$coupon_exclude_sale_items = isset( $coupon_post_meta['exclude_sale_items'][0] ) && '' !== $coupon_post_meta['exclude_sale_items'][0] ? $coupon_post_meta['exclude_sale_items'] [0] : 'no';

		$use_limit = isset( $coupon_post_meta['usage_limit'][0] ) && '' !== $coupon_post_meta['usage_limit'][0] ? $coupon_post_meta['usage_limit'][0] : '';

		$use_limit_user = isset( $coupon_post_meta['usage_limit_per_user'][0] ) && '' !== $coupon_post_meta['usage_limit_per_user'][0] ? $coupon_post_meta['usage_limit_per_user'][0] : '';

		$atc_unique = isset( $coupon_post_meta['atc_unique_coupon'][0] ) && '' !== $coupon_post_meta['atc_unique_coupon'][0] ? $coupon_post_meta['atc_unique_coupon'][0] : false;

		if ( class_exists( 'WC_Free_Gift_Coupons' ) ) {
			$free_gift_coupon   = isset( $coupon_post_meta['gift_ids'][0] ) && '' !== $coupon_post_meta['gift_ids'][0] ? $coupon_post_meta['gift_ids'][0] : '';
			$free_gift_shipping = isset( $coupon_post_meta['free_gift_shipping'][0] ) && '' !== $coupon_post_meta['free_gift_shipping'][0] ? $coupon_post_meta['free_gift_shipping'][0] : 'no';
		}
		if ( is_plugin_active( 'yith-woocommerce-brands-add-on/init.php' ) ) {
			$coupon_brand = isset( $coupon_post_meta['brand'][0] ) && '' !== $coupon_post_meta['brand'][0] ? unserialize( $coupon_post_meta['brand'][0] ) : array(); //phpcs:ignore
		}
		$amount        = $discount_amt;
		$discount_type = $get_discount_type;

		// Add coupon meta.
		$coupon_meta = array(
			'discount_type'              => $discount_type,
			'coupon_amount'              => $amount,
			'minimum_amount'             => $coupon_minimum_amount,
			'maximum_amount'             => $coupon_maximum_amount,
			'individual_use'             => $individual_use,
			'free_shipping'              => $coupon_free_shipping,
			'product_ids'                => '',
			'exclude_product_ids'        => '',
			'usage_limit'                => $use_limit,
			'usage_limit_per_user'       => $use_limit_user,
			'date_expires'               => $datetime,
			'apply_before_tax'           => 'yes',
			'product_ids'                => $coupon_product_ids,
			'exclude_sale_items'         => $coupon_exclude_sale_items,
			'exclude_product_ids'        => $coupon_exclude_product_ids,
			'product_categories'         => $coupon_product_categories,
			'exclude_product_categories' => $coupon_exculde_product_categories,
			'wcal_created_by'            => 'wcal',
			'atc_unique_coupon'          => $atc_unique,
		);

		if ( class_exists( 'WC_Free_Gift_Coupons' ) ) {
			$coupon_meta['gif_ids']            = $free_gift_coupon;
			$coupon_meta['free_gift_shipping'] = $free_gift_shipping;
		}
		if ( is_plugin_active( 'yith-woocommerce-brands-add-on/init.php' ) ) {
			$coupon_meta['brand'] = $coupon_brand;
		}

		$coupon        = apply_filters(
			'wcal_cron_before_shop_coupon_create',
			array(
				'post_title'       => $coupon_code,
				'post_content'     => 'This coupon provides 5% discount on cart price.',
				'post_status'      => 'publish',
				'post_author'      => 1,
				'post_type'        => 'shop_coupon',
				'post_expiry_date' => $datetime,
				'meta_input'       => $coupon_meta,
			)
		);
		$new_coupon_id = wp_insert_post( $coupon );

		return $final_string;
	}

	/**
	 * It will generate 12 digit unique string for coupon code.
	 *
	 * @return string $temp_array 12 digit unique string
	 * @since 2.3.6
	 */
	public static function wp_random_string() {
		$character_set_array   = array();
		$character_set_array[] = array(
			'count'      => 5,
			'characters' => 'abcdefghijklmnopqrstuvwxyz',
		);
		$character_set_array[] = array(
			'count'      => 5,
			'characters' => '0123456789',
		);
		$temp_array            = array();
		foreach ( $character_set_array as $character_set ) {
			for ( $i = 0; $i < $character_set['count']; $i++ ) {
					$temp_array[] = $character_set['characters'][ wp_rand( 0, strlen( $character_set['characters'] ) - 1 ) ];
			}
		}
		shuffle( $temp_array );
		return implode( '', $temp_array );
	}

	/**
	 * It will captures the coupon code used by the customers.
	 * It will store the coupon code for the specific abandoned cart.
	 *
	 * @hook woocommerce_applied_coupon
	 * @param string $valid Coupon code.
	 * @return string $valid Coupon code.
	 * @globals mixed $wpdb
	 * @since 5.11.0
	 */
	public static function wcal_capture_applied_coupon( $valid ) {

		global $wpdb;

		$coupon_code = self::wcal_get_cart_session( 'wcal_c' );

		$user_id = self::wcal_get_cart_session( 'wcal_user_id' );

		$user_id = '' !== $user_id ? $user_id : get_current_user_id();

		if ( '' === $coupon_code && isset( $_POST['coupon_code'] ) ) { //phpcs:ignore
			$coupon_code = $_POST['coupon_code']; //phpcs:ignore
		} elseif ( isset( $valid ) ) {
			$coupon_code = $valid;
		}

		if ( '' !== $valid ) {
			if ( is_user_logged_in() ) {

				$abandoned_cart_id_query   = 'SELECT id FROM `' . $wpdb->prefix . "ac_abandoned_cart_history_lite` WHERE user_id = %d AND cart_ignored = '0' AND recovered_cart = '0'";
				$abandoned_cart_id_results = $wpdb->get_results( $wpdb->prepare( $abandoned_cart_id_query, $user_id ) ); //phpcs:ignore
			} elseif ( ! is_user_logged_in() ) {
					$abandoned_cart_id_query   = 'SELECT id FROM `' . $wpdb->prefix . "ac_abandoned_cart_history_lite` WHERE user_id = %d AND cart_ignored = '0' AND recovered_cart = '0' ORDER BY id DESC LIMIT 1";
					$abandoned_cart_id_results = $wpdb->get_results( $wpdb->prepare( $abandoned_cart_id_query, $user_id ) ); //phpcs:ignore
			}

			$abandoned_cart_id = '0';
			if ( isset( $abandoned_cart_id_results ) && ! empty( $abandoned_cart_id_results ) ) {
				$abandoned_cart_id = $abandoned_cart_id_results[0]->id;
			}
			$existing_coupon = ( get_user_meta( $user_id, '_woocommerce_ac_coupon', true ) );
			$applied         = wcal_Common::wcal_update_coupon_post_meta( $abandoned_cart_id, $coupon_code );
			if ( $applied ) {
				return $valid;
			}
			if ( is_array( $existing_coupon ) && count( $existing_coupon ) > 0 ) {
				foreach ( $existing_coupon as $key => $value ) {
					if ( isset( $existing_coupon[ $key ]['coupon_code'] ) && $existing_coupon[ $key ]['coupon_code'] !== $coupon_code ) {
						$existing_coupon[] = array(
							'coupon_code'    => $coupon_code,
							'coupon_message' => __(
								'Discount code applied successfully.',
								'woocommerce-ac'
							),
						);
						update_user_meta(
							$user_id,
							'_woocommerce_ac_coupon',
							$existing_coupon
						);
						return $valid;
					}
				}
			} else {
				$coupon_details[] = array(
					'coupon_code'    => $coupon_code,
					'coupon_message' => __(
						'Discount code applied successfully.',
						'woocommerce-ac'
					),
				);
				update_user_meta( $user_id, '_woocommerce_ac_coupon', $coupon_details );
				return $valid;
			}
		}

		return $valid;
	}

	/**
	 * Update the Coupon data in post meta table.
	 *
	 * @param int    $cart_id - Abandoned Cart ID.
	 * @param string $coupon_code - Coupon code to be updated.
	 * @param string $msg - Msg to be added for the coupon.
	 * @since 5.11
	 */
	public static function wcal_update_coupon_post_meta( $cart_id, $coupon_code, $msg = '' ) {

		// Set default.
		$msg = '' !== $msg ? $msg : __( 'Discount code applied successfully.', 'woocommerce-ac' );
		// Fetch the record from the DB.
		$get_coupons = get_post_meta( $cart_id, '_woocommerce_ac_coupon', true );

		// Create a return array.
		$return_coupons = array();

		// If any coupon have been applied, populate them in the return array.
		if ( is_array( $get_coupons ) && count( $get_coupons ) > 0 ) {
			$exists = false;
			foreach ( $get_coupons as $coupon_data ) {
				if ( isset( $coupon_data['coupon_code'] ) && $coupon_code === $coupon_data['coupon_code'] ) {
					$exists = true;
				}
			}

			if ( ! $exists ) {
				$get_coupons[] = array(
					'coupon_code'    => $coupon_code,
					'coupon_message' => $msg,
				);
				update_post_meta( $cart_id, '_woocommerce_ac_coupon', $get_coupons );
				return true;
			}
		} else {
			$get_coupons   = array();
			$get_coupons[] = array(
				'coupon_code'    => $coupon_code,
				'coupon_message' => $msg,
			);
			update_post_meta( $cart_id, '_woocommerce_ac_coupon', $get_coupons );
			return true;
		}
		return false;
	}

	/**
	 * It will captures the coupon code errors specific to the abandoned carts.
	 *
	 * @hook woocommerce_coupon_error.
	 * @param string $valid Error.
	 * @param string $new Error code.
	 * @globals mixed $wpdb .
	 * @return string $valid Error.
	 * @since 2.4.3
	 */
	public static function wcal_capture_coupon_error( $valid, $new ) {

		global $wpdb;
		$coupon_code = self::wcal_get_cart_session( 'wcal_c' );

		$user_id = self::wcal_get_cart_session( 'wcal_user_id' );

		$user_id = '' !== $user_id ? $user_id : get_current_user_id();

		$abandoned_cart_id_query   = 'SELECT id FROM `' . $wpdb->prefix . "ac_abandoned_cart_history_lite` WHERE user_id = %d AND cart_ignored = '0' AND recovered_cart = '0' ORDER BY id DESC LIMIT 1";
		$abandoned_cart_id_results = $wpdb->get_results( $wpdb->prepare( $abandoned_cart_id_query, $user_id ) ); //phpcs:ignore
		$abandoned_cart_id         = '0';

		if ( isset( $abandoned_cart_id_results ) && count( $abandoned_cart_id_results ) > 0 ) {
			$abandoned_cart_id = $abandoned_cart_id_results[0]->id;
		}

		if ( $coupon_code == '' && isset( $_POST['coupon_code'] ) ) { //phpcs:ignore
			$coupon_code = $_POST['coupon_code']; //phpcs:ignore
		}

		if ( '' !== $coupon_code ) {
			$existing_coupon        = get_user_meta( $user_id, '_woocommerce_ac_coupon', false );
			$existing_coupon[]      = array(
				'coupon_code'    => $coupon_code,
				'coupon_message' => $valid,
			);
			$post_meta_coupon_array = array(
				'coupon_code'    => $coupon_code,
				'coupon_message' => $valid,
			);
			if ( $user_id > 0 ) {
				$updated = wcal_Common::wcal_update_coupon_post_meta( $abandoned_cart_id, $coupon_code, $valid );
			}
			update_user_meta( $user_id, '_woocommerce_ac_coupon', $existing_coupon );
		}
		return $valid;
	}

	/**
	 * It will directly apply the coupon code if the coupon code present in the abandoned cart reminder email link.
	 * It will apply direct coupon on cart and checkout page.
	 *
	 * @hook woocommerce_before_cart_table
	 * @hook woocommerce_before_checkout_form
	 *
	 * @param string $coupon_code Name of coupon.
	 * @since 5.11
	 */
	public static function wcal_apply_direct_coupon_code( $coupon_code ) {
		global $woocommerce_abandon_cart;
		remove_action( 'woocommerce_cart_updated', array( $woocommerce_abandon_cart, 'wcal_store_cart_timestamp' ) );

		$wcal_language = self::wcal_get_cart_session( 'wcal_selected_language' );
		if ( '' !== $wcal_language && function_exists( 'icl_register_string' ) ) {
			global $sitepress;
			if ( null !== $sitepress ) {
				$sitepress->switch_lang( $wcal_language );
			}
		}

		$coupon_code = self::wcal_get_cart_session( 'wcal_c' );

		if ( isset( $coupon_code ) && '' !== $coupon_code ) {

			// If coupon has been already been added remove it.
			if ( WC()->cart->has_discount( sanitize_text_field( $coupon_code ) ) ) {
				if ( ! WC()->cart->remove_coupons( sanitize_text_field( $coupon_code ) ) ) {
					wc_print_notices();
				}
			}
			// Add coupon.
			if ( ! WC()->cart->add_discount( sanitize_text_field( $coupon_code ) ) ) {
				wc_print_notices();
			} else {
				wc_print_notices();
			}
			// Manually recalculate totals.  If you do not do this, a refresh is required before user will see updated totals when discount is removed.
			WC()->cart->calculate_totals();
			// need to clear the coupon code from session.
			self::wcal_unset_cart_session( 'wcal_c' );
		}
	}

	/**
	 * Get the post meta data for AC Coupons.
	 *
	 * @param int $cart_id - Abandoned Cart ID.
	 * @return array $return_coupons Return Coupon data.
	 * @since 5.12.0
	 */
	public static function wcal_get_coupon_post_meta( $cart_id ) {

		// Fetch the record from the DB.
		$get_coupons = get_post_meta( $cart_id, '_woocommerce_ac_coupon', true );

		// Create a return array.
		$return_coupons = array();

		// If any coupon have been applied, populate them in the return array.
		if ( is_array( $get_coupons ) && count( $get_coupons ) > 0 ) {
			foreach ( $get_coupons as $coupon_data ) {
				$coupon_msg  = '';
				$coupon_code = '';
				if ( isset( $coupon_data['coupon_code'] ) ) {
					$coupon_code = $coupon_data['coupon_code'];
				}
				if ( isset( $coupon_data['coupon_message'] ) ) {
					$coupon_msg = $coupon_data['coupon_message'];
				}

				if ( '' !== $coupon_code && ! in_array( $coupon_code, $return_coupons, true ) ) {
					$return_coupons[ $coupon_code ] = $coupon_msg;
				}
			}
		}
		return $return_coupons;
	}

	/**
	 * Add a scheduled action for the webhok to be delivered once the cart cut off is reached.
	 *
	 * @param int $cart_id - Abandoned Cart ID.
	 * @since 5.12.0
	 */
	public static function wcal_run_webhook_after_cutoff( $cart_id ) {
		// check if the Webhook is present & active.
		global $wpdb;

		$get_webhook_status = $wpdb->get_var( // phpcs:ignore
			$wpdb->prepare(
				'SELECT status FROM `' . $wpdb->prefix . 'wc_webhooks` WHERE topic = %s',
				'wcap_cart.cutoff'
			)
		);

		if ( isset( $get_webhook_status ) && 'active' === $get_webhook_status ) {
			// Reconfirm that the cart is either a registered user cart or a guest cart. The webhook will not be run for visitor carts.
			$cart_data = $wpdb->get_results( // phpcs:ignore
				$wpdb->prepare(
					'SELECT user_id, user_type, cart_ignored, recovered_cart FROM `' . $wpdb->prefix . 'ac_abandoned_cart_history_lite` WHERE id = %d',
					$cart_id
				)
			);

			$user_id   = isset( $cart_data[0]->user_id ) ? $cart_data[0]->user_id : 0;
			$user_type = isset( $cart_data[0]->user_type ) ? $cart_data[0]->user_type : '';

			if ( $user_id > 0 && '' != $user_type && '0' == $cart_data[0]->cart_ignored && $cart_data[0]->recovered_cart <= 0 ) { // phpcs:ignore
				$cut_off = is_numeric( get_option( 'ac_lite_cart_abandoned_time', 10 ) ) ? get_option( 'ac_lite_cart_abandoned_time', 10 ) * 60 : 10 * 60;

				if ( $cut_off > 0 && ! as_has_scheduled_action( 'wcap_webhook_after_cutoff', array( 'id' => (int) $cart_id ) ) ) {
					// run the hook.
					as_schedule_single_action( time() + $cut_off, 'wcap_webhook_after_cutoff', array( 'id' => (int) $cart_id ) );
				}
			}
		}
	}

	/**
	 * Update Checkout Link in cart history table.
	 *
	 * @param int $cart_id - Cart ID.
	 * @since 8.7.0
	 */
	public static function wcal_add_checkout_link( $cart_id ) {

		global $wpdb;
		if ( version_compare( WOOCOMMERCE_VERSION, '2.3' ) < 0 ) {
			global $woocommerce;
			$checkout_page_link = $woocommerce->cart->get_checkout_url();
		} else {
			$checkout_page_id   = wc_get_page_id( 'checkout' );
			$checkout_page_link = $checkout_page_id ? get_permalink( $checkout_page_id ) : '';
		}

		// Force SSL if needed.
		$ssl_is_used = is_ssl() ? true : false;

		if ( true === $ssl_is_used || 'yes' === get_option( 'woocommerce_force_ssl_checkout' ) ) {
			$checkout_page_link = str_ireplace( 'http:', 'https:', $checkout_page_link );
		}

		$encoding_checkout = $cart_id . '&url=' . $checkout_page_link;
		$validate_checkout = Wcal_Common::wcal_encrypt_validate( $encoding_checkout );

		$checkout_link = get_option( 'siteurl' ) . '/?wcal_action=checkout_link&validate=' . $validate_checkout;

		$wpdb->update( // phpcs:ignore
			$wpdb->prefix . 'ac_abandoned_cart_history_lite',
			array(
				'checkout_link' => $checkout_link,
			),
			array(
				'id' => $cart_id,
			)
		);
	}

	/**
	 * This function is used to encode the string.
	 *
	 * @param string $validate String need to encrypt.
	 * @return string $validate_encoded Encrypted string.
	 * @since 1.3
	 */
	public static function wcal_encrypt_validate( $validate ) {
		$crypt_key        = get_option( 'wcal_security_key' );
		$validate_encoded = Wcal_Aes_Ctr::encrypt( $validate, $crypt_key, 256 );
		return( $validate_encoded );
	}
}
