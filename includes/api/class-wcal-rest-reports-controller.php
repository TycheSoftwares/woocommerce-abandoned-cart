<?php
/**
 * REST endpoints for the Dashboard, Product Report and Reminders Sent screens.
 *
 * Routes and envelope follow the Pro plugin's controllers. The figures come from
 * the same queries the PHP screens of this plugin used.
 *
 * @package Abandoned-Cart-Lite-for-WooCommerce/Admin/API
 * @since 6.10.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WCAL_REST_Reports_Controller class.
 */
class WCAL_REST_Reports_Controller extends WCAL_REST_Settings_Controller {

	/**
	 * Register the routes.
	 */
	public static function register_routes() {
		$read = array(
			'methods'             => WP_REST_Server::READABLE,
			'permission_callback' => array( __CLASS__, 'get_read_permission' ),
		);

		register_rest_route(
			self::REST_NAMESPACE,
			'/dashboard',
			array_merge(
				$read,
				array(
					'callback' => array( __CLASS__, 'get_dashboard' ),
					'args'     => array(
						'duration'   => array(
							'type'    => 'string',
							'default' => 'this_month',
							'enum'    => array( 'this_month', 'last_month', 'this_quarter', 'last_quarter', 'this_year', 'last_year', 'custom' ),
						),
						'start_date' => array(
							'type'              => 'string',
							'default'           => '',
							'validate_callback' => array( __CLASS__, 'validate_date' ),
						),
						'end_date'   => array(
							'type'              => 'string',
							'default'           => '',
							'validate_callback' => array( __CLASS__, 'validate_date' ),
						),
					),
				)
			)
		);

		register_rest_route(
			self::REST_NAMESPACE,
			'/dashboard/monthly-chart',
			array_merge( $read, array( 'callback' => array( __CLASS__, 'get_monthly_chart' ) ) )
		);

		register_rest_route(
			self::REST_NAMESPACE,
			'/product-report',
			array_merge(
				$read,
				array(
					'callback' => array( __CLASS__, 'get_product_report' ),
					'args'     => array(
						'page'     => array(
							'type'    => 'integer',
							'default' => 1,
							'minimum' => 1,
						),
						'per_page' => array(
							'type'    => 'integer',
							'default' => 30,
							'minimum' => 1,
							'maximum' => 100,
						),
						'order'    => array(
							'type'    => 'string',
							'default' => 'desc',
							'enum'    => array( 'asc', 'desc' ),
						),
					),
				)
			)
		);

		register_rest_route(
			self::REST_NAMESPACE,
			'/reminders-sent',
			array_merge(
				$read,
				array(
					'callback' => array( __CLASS__, 'get_reminders_sent' ),
					'args'     => array(
						'page'       => array(
							'type'    => 'integer',
							'default' => 1,
							'minimum' => 1,
						),
						'per_page'   => array(
							'type'    => 'integer',
							'default' => 20,
							'minimum' => 1,
							'maximum' => 100,
						),
						'start_date' => array(
							'type'              => 'string',
							'default'           => '',
							'validate_callback' => array( __CLASS__, 'validate_date' ),
						),
						'end_date'   => array(
							'type'              => 'string',
							'default'           => '',
							'validate_callback' => array( __CLASS__, 'validate_date' ),
						),
					),
				)
			)
		);
	}

	/**
	 * Accept an empty value or a Y-m-d date.
	 *
	 * @param string $value Date.
	 * @return bool
	 */
	public static function validate_date( $value ) {
		if ( '' === $value ) {
			return true;
		}
		$date = DateTime::createFromFormat( 'Y-m-d', (string) $value );
		return $date && $date->format( 'Y-m-d' ) === $value;
	}

	/**
	 * Dashboard statistics for the selected range.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public static function get_dashboard( $request ) {
		include_once WCAL_PLUGIN_PATH . '/includes/classes/class-wcal-dashboard-report.php';

		$duration = $request->get_param( 'duration' );

		Wcal_Dashboard_Report::wcal_setup_filter_parms( $duration, $request->get_param( 'start_date' ), $request->get_param( 'end_date' ) );

		$guest_emails     = Wcal_Dashboard_Report::wcal_get_collected_email_count();
		$emails_sent      = Wcal_Dashboard_Report::wcal_get_emails_sent_count();
		$abandoned_carts  = Wcal_Dashboard_Report::wcal_get_abandoned_cart_count();
		$recovered_amount = Wcal_Dashboard_Report::wcal_get_recovered_amount();
		$abandoned_amount = Wcal_Dashboard_Report::wcal_abandoned_orders_amount();
		$placed_amount    = Wcal_Dashboard_Report::wcal_placed_orders_amount();

		$percent_recovered = 0;
		$percent_of_sales  = 0;
		if ( Wcal_Dashboard_Report::$recovered_count > 0 && Wcal_Dashboard_Report::$abandoned_count > 0 ) {
			$percent_recovered = round( ( Wcal_Dashboard_Report::$recovered_count * 100 ) / Wcal_Dashboard_Report::$abandoned_count, 2 );
		}
		if ( $placed_amount > 0 && $recovered_amount > 0 ) {
			$percent_of_sales = round( ( $recovered_amount * 100 ) / $placed_amount, 2 );
		}

		return self::envelope(
			array(
				'recovered_amount'     => (float) $recovered_amount,
				'recovered_orders'     => (int) Wcal_Dashboard_Report::$recovered_count,
				'recovery_rate_orders' => $percent_recovered,
				'percent_of_sales'     => $percent_of_sales,
				'abandoned_amount'     => (float) $abandoned_amount,
				'abandoned_orders'     => (int) $abandoned_carts,
				'emails_sent'          => (int) $emails_sent,
				'guest_emails'         => (int) $guest_emails,
				'graph_data'           => Wcal_Dashboard_Report::get_abandoned_data( $duration ),
			)
		);
	}

	/**
	 * Abandoned and recovered amounts for the last twelve months.
	 *
	 * @return WP_REST_Response
	 */
	public static function get_monthly_chart() {
		include_once WCAL_PLUGIN_PATH . '/includes/classes/class-wcal-dashboard-report.php';

		$now   = current_time( 'timestamp' ); // phpcs:ignore
		$start = strtotime( gmdate( 'Y-m-01 00:00:00', $now ) . ' -11 months' );

		return rest_ensure_response(
			array(
				'graph_data' => Wcal_Dashboard_Report::wcap_get_graph_data( 'monthly', $start, $now, 'monthly' ),
			)
		);
	}

	/**
	 * Number of times each product was abandoned and recovered.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public static function get_product_report( $request ) {
		global $wpdb;

		$carts = $wpdb->get_results( // phpcs:ignore
			$wpdb->prepare(
				'SELECT abandoned_cart_time, abandoned_cart_info, recovered_cart FROM `' . $wpdb->prefix . 'ac_abandoned_cart_history_lite` WHERE abandoned_cart_info NOT LIKE %s AND abandoned_cart_info NOT LIKE %s AND abandoned_cart_info NOT LIKE %s ORDER BY recovered_cart DESC', // phpcs:ignore
				'%{"cart":[]}%',
				'[]',
				'""'
			)
		);

		$cut_off      = (int) get_option( 'ac_lite_cart_abandoned_time' ) * 60;
		$current_time = current_time( 'timestamp' ); // phpcs:ignore
		$abandoned    = array();
		$recovered    = array();

		foreach ( $carts as $cart ) {
			$info = json_decode( $cart->abandoned_cart_info );
			if ( ! is_object( $info ) && ! is_array( $info ) ) {
				continue;
			}
			foreach ( $info as $group ) {
				if ( ! is_object( $group ) && ! is_array( $group ) ) {
					continue;
				}
				foreach ( $group as $item ) {
					if ( ! isset( $item->product_id ) ) {
						continue;
					}
					if ( ( $current_time - $cart->abandoned_cart_time ) > $cut_off ) {
						$abandoned[] = $item->product_id;
					}
					if ( 0 != $cart->recovered_cart ) { // phpcs:ignore
						$recovered[] = $item->product_id;
					}
				}
			}
		}

		$abandoned_counts = array_count_values( $abandoned );
		$recovered_counts = array_count_values( $recovered );

		asort( $abandoned_counts, SORT_NUMERIC );
		if ( 'desc' === $request->get_param( 'order' ) ) {
			$abandoned_counts = array_reverse( $abandoned_counts, true );
		}

		$report = array();
		foreach ( $abandoned_counts as $product_id => $count ) {
			$product = get_post( $product_id );
			if ( $product ) {
				$report[] = (object) array(
					'product_name'     => $product->post_title,
					'abandoned_number' => $count,
					'recover_number'   => isset( $recovered_counts[ $product_id ] ) ? $recovered_counts[ $product_id ] : 0,
					'product_id'       => $product_id,
				);
			}
		}

		$total    = count( $report );
		$per_page = (int) $request->get_param( 'per_page' );
		$page     = (int) $request->get_param( 'page' );
		$rows     = apply_filters( 'wcal_product_report_table_data', array_slice( $report, ( $page - 1 ) * $per_page, $per_page ) );

		$data = array();
		foreach ( $rows as $row ) {
			$data[] = array(
				'product_id'      => (int) $row->product_id,
				'name'            => $row->product_name,
				'times_abandoned' => (int) $row->abandoned_number,
				'times_recovered' => (int) $row->recover_number,
			);
		}

		return rest_ensure_response(
			array(
				'data' => $data,
				'meta' => array(
					'total'    => $total,
					'page'     => $page,
					'per_page' => $per_page,
					'pages'    => (int) max( 1, ceil( $total / $per_page ) ),
				),
			)
		);
	}

	/**
	 * Reminder emails sent in the selected range.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public static function get_reminders_sent( $request ) {
		global $wpdb;

		$history = $wpdb->prefix . 'ac_sent_history_lite';
		$where   = 'wsht.template_id > 0';
		$args    = array();

		if ( '' !== $request->get_param( 'start_date' ) ) {
			$where .= ' AND wsht.sent_time >= %s';
			$args[] = $request->get_param( 'start_date' ) . ' 00:00:00';
		}
		if ( '' !== $request->get_param( 'end_date' ) ) {
			$where .= ' AND wsht.sent_time <= %s';
			$args[] = $request->get_param( 'end_date' ) . ' 23:59:59';
		}

		$count_query = "SELECT COUNT(wsht.id) FROM `$history` AS wsht WHERE $where";
		$total       = (int) $wpdb->get_var( $args ? $wpdb->prepare( $count_query, $args ) : $count_query ); // phpcs:ignore

		$per_page = (int) $request->get_param( 'per_page' );
		$page     = (int) $request->get_param( 'page' );

		$rows = $wpdb->get_results( // phpcs:ignore
			$wpdb->prepare(
				"SELECT wsht.id, wsht.abandoned_order_id, wsht.sent_time, wsht.sent_email_id, wcet.template_name, cart.id AS cart_id, cart.recovered_cart
				FROM `$history` AS wsht
				LEFT JOIN `{$wpdb->prefix}ac_email_templates_lite` AS wcet ON wsht.template_id = wcet.id
				LEFT JOIN `{$wpdb->prefix}ac_abandoned_cart_history_lite` AS cart ON wsht.abandoned_order_id = cart.id
				WHERE $where ORDER BY wsht.sent_time DESC, wsht.id DESC LIMIT %d OFFSET %d", // phpcs:ignore
				array_merge( $args, array( $per_page, ( $page - 1 ) * $per_page ) )
			)
		);

		$data = array();
		foreach ( $rows as $row ) {
			$data[] = array(
				'id'            => (int) $row->id,
				'email_id'      => $row->sent_email_id,
				'sent_at'       => gmdate( 'c', strtotime( $row->sent_time ) ),
				'template_name' => $row->template_name,
				'cart_id'       => $row->cart_id ? (int) $row->cart_id : 0,
				'recovered'     => (int) $row->recovered_cart > 0,
			);
		}

		return rest_ensure_response(
			array(
				'data' => $data,
				'meta' => array(
					'total'    => $total,
					'page'     => $page,
					'per_page' => $per_page,
					'pages'    => (int) max( 1, ceil( $total / $per_page ) ),
				),
			)
		);
	}
}
