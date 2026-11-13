<?php
/**
 * REST endpoints for the Abandoned Orders screen.
 *
 * Routes, envelope and response fields follow the Pro plugin's cart controller.
 * The rows come from the same conditions the PHP Abandoned Orders and Recovered
 * Orders screens of this plugin used.
 *
 * @package Abandoned-Cart-Lite-for-WooCommerce/Admin/API
 * @since 6.10.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WCAL_REST_Carts_Controller class.
 */
class WCAL_REST_Carts_Controller extends WCAL_REST_Settings_Controller {

	/**
	 * Register the routes.
	 */
	public static function register_routes() {
		$filters = array(
			'status'     => array(
				'type'    => 'string',
				'default' => 'all',
				'enum'    => array( 'all', 'abandoned', 'recovered' ),
			),
			'start_date' => array(
				'type'              => 'string',
				'default'           => '',
				'validate_callback' => array( 'WCAL_REST_Reports_Controller', 'validate_date' ),
			),
			'end_date'   => array(
				'type'              => 'string',
				'default'           => '',
				'validate_callback' => array( 'WCAL_REST_Reports_Controller', 'validate_date' ),
			),
		);

		register_rest_route(
			self::REST_NAMESPACE,
			'/abandoned-carts',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( __CLASS__, 'get_items' ),
				'permission_callback' => array( __CLASS__, 'get_read_permission' ),
				'args'                => array_merge(
					$filters,
					array(
						'page'     => array(
							'type'    => 'integer',
							'default' => 1,
							'minimum' => 1,
						),
						'per_page' => array(
							'type'    => 'integer',
							'default' => 20,
							'minimum' => 1,
							'maximum' => 100,
						),
						'view'     => array(
							'type'    => 'string',
							'default' => 'all',
							'enum'    => array( 'all', 'registered', 'guest', 'visitor' ),
						),
					)
				),
			)
		);

		register_rest_route(
			self::REST_NAMESPACE,
			'/abandoned-carts/counts',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( __CLASS__, 'get_counts' ),
				'permission_callback' => array( __CLASS__, 'get_read_permission' ),
				'args'                => $filters,
			)
		);

		register_rest_route(
			self::REST_NAMESPACE,
			'/abandoned-carts/bulk',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( __CLASS__, 'bulk_action' ),
				'permission_callback' => array( __CLASS__, 'get_read_permission' ),
				'args'                => array(
					'action' => array(
						'type'     => 'string',
						'required' => true,
						'enum'     => array( 'delete', 'delete_registered', 'delete_guest', 'delete_visitor', 'delete_all' ),
					),
					'ids'    => array(
						'type'    => 'array',
						'items'   => array( 'type' => 'integer' ),
						'default' => array(),
					),
				),
			)
		);

		register_rest_route(
			self::REST_NAMESPACE,
			'/abandoned-carts/(?P<id>\d+)',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( __CLASS__, 'get_item' ),
					'permission_callback' => array( __CLASS__, 'get_read_permission' ),
				),
				array(
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => array( __CLASS__, 'delete_item' ),
					'permission_callback' => array( __CLASS__, 'get_read_permission' ),
				),
			)
		);
	}

	/**
	 * WHERE clause shared by the list, the counts and the recovered totals.
	 *
	 * @param string $status abandoned, recovered or all.
	 * @param string $view   all, registered, guest or visitor.
	 * @param string $start  Start date (Y-m-d) or empty.
	 * @param string $end    End date (Y-m-d) or empty.
	 * @return string Prepared clause.
	 */
	protected static function where( $status, $view, $start, $end ) {
		global $wpdb;

		$compare_time = current_time( 'timestamp' ) - ( intval( get_option( 'ac_lite_cart_abandoned_time', 10 ) ) * 60 ); // phpcs:ignore

		$where = $wpdb->prepare(
			'abandoned_cart_info NOT LIKE %s AND abandoned_cart_info NOT LIKE %s AND abandoned_cart_info NOT LIKE %s AND abandoned_cart_time <= %d',
			'%{"cart":[]}%',
			'[]',
			'""',
			$compare_time
		);

		$abandoned = "( recovered_cart = '0' AND cart_ignored <> '1' )";
		$recovered = 'recovered_cart > 0';
		if ( 'abandoned' === $status ) {
			$where .= " AND $abandoned";
		} elseif ( 'recovered' === $status ) {
			$where .= " AND $recovered";
		} else {
			$where .= " AND ( $abandoned OR $recovered )";
		}

		if ( 'registered' === $view ) {
			$where .= " AND user_type = 'REGISTERED'";
		} elseif ( 'guest' === $view ) {
			$where .= ' AND user_id >= 63000000';
		} elseif ( 'visitor' === $view ) {
			$where .= ' AND user_id = 0';
		}

		// The cart time is stored as the site's local time, like strtotime() output under WordPress' UTC default.
		if ( '' !== $start ) {
			$where .= $wpdb->prepare( ' AND abandoned_cart_time >= %d', strtotime( $start . ' 00:00:00' ) );
		}
		if ( '' !== $end ) {
			$where .= $wpdb->prepare( ' AND abandoned_cart_time <= %d', strtotime( $end . ' 23:59:59' ) );
		}

		return $where;
	}

	/**
	 * List of carts.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public static function get_items( $request ) {
		global $wpdb;

		$table    = $wpdb->prefix . 'ac_abandoned_cart_history_lite';
		$status   = $request->get_param( 'status' );
		$view     = $request->get_param( 'view' );
		$start    = $request->get_param( 'start_date' );
		$end      = $request->get_param( 'end_date' );
		$per_page = (int) $request->get_param( 'per_page' );
		$page     = (int) $request->get_param( 'page' );
		$where    = self::where( $status, $view, $start, $end );

		$total = (int) $wpdb->get_var( "SELECT COUNT(id) FROM `$table` WHERE $where" ); // phpcs:ignore
		$rows  = $wpdb->get_results( // phpcs:ignore
			$wpdb->prepare(
				"SELECT * FROM `$table` WHERE $where ORDER BY abandoned_cart_time DESC, id DESC LIMIT %d OFFSET %d", // phpcs:ignore
				$per_page,
				( $page - 1 ) * $per_page
			)
		);

		$data = array();
		foreach ( $rows as $row ) {
			$data[] = self::format_row( $row );
		}

		$recovered_count = 0;
		$recovered_total = 0;
		$recovered_ids   = $wpdb->get_col( "SELECT recovered_cart FROM `$table` WHERE " . self::where( 'recovered', $view, $start, $end ) ); // phpcs:ignore
		foreach ( $recovered_ids as $order_id ) {
			$order = wc_get_order( $order_id );
			if ( $order ) {
				$recovered_count++;
				$recovered_total += $order->get_total();
			}
		}

		return rest_ensure_response(
			array(
				'data' => $data,
				'meta' => array(
					'total'                     => $total,
					'page'                      => $page,
					'per_page'                  => $per_page,
					'pages'                     => (int) max( 1, ceil( $total / $per_page ) ),
					'recovered_count'           => $recovered_count,
					'recovered_total'           => round( $recovered_total, wc_get_price_decimals() ),
					'recovered_total_formatted' => wc_price( $recovered_total ),
				),
			)
		);
	}

	/**
	 * Number of carts on each tab.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public static function get_counts( $request ) {
		global $wpdb;

		$table  = $wpdb->prefix . 'ac_abandoned_cart_history_lite';
		$counts = array();
		foreach ( array( 'all', 'registered', 'guest', 'visitor' ) as $view ) {
			$where           = self::where( $request->get_param( 'status' ), $view, $request->get_param( 'start_date' ), $request->get_param( 'end_date' ) );
			$counts[ $view ] = (int) $wpdb->get_var( "SELECT COUNT(id) FROM `$table` WHERE $where" ); // phpcs:ignore
		}
		return self::envelope( $counts );
	}

	/**
	 * Fetch a stored cart row.
	 *
	 * @param int $id Cart ID.
	 * @return object|null
	 */
	protected static function find( $id ) {
		global $wpdb;
		return $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM `' . $wpdb->prefix . 'ac_abandoned_cart_history_lite` WHERE id = %d', $id ) ); // phpcs:ignore
	}

	/**
	 * Not found error.
	 *
	 * @return WP_Error
	 */
	protected static function not_found() {
		return new WP_Error( 'wcal_cart_not_found', __( 'Cart not found.', 'woocommerce-abandoned-cart' ), array( 'status' => 404 ) );
	}

	/**
	 * Cart details for the popup.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function get_item( $request ) {
		global $wpdb;

		$cart_id = (int) $request['id'];
		$row     = self::find( $cart_id );
		if ( ! $row ) {
			return self::not_found();
		}

		$data                 = self::format_row( $row );
		$data['billing']      = array_merge( $data['billing'], self::billing_address( $row ) );
		$data['shipping']     = self::shipping_address( $row );
		$data['user_role']    = ( $row->user_id > 0 && $row->user_id < 63000000 ) ? wcal_common::wcal_get_user_role( $row->user_id ) : 'Guest';
		$data['status_label'] = self::status_label( $row );

		$info     = self::cart_info( $row );
		$items    = isset( $info->cart ) ? $info->cart : array();
		$items    = isset( $items->cart_contents ) ? $items->cart_contents : $items;
		$currency = isset( $info->currency ) ? $info->currency : '';

		$data['products'] = array();
		$shipping         = self::shipping_charges( $row, $info );
		$data['totals']   = array_merge(
			$data['totals'],
			array(
				'shipping'           => $shipping,
				'shipping_formatted' => wc_price( $shipping ),
			)
		);

		if ( ! empty( $items ) ) {
			$details = Wcal_Abandoned_Cart_Details::wcal_get_cart_details( $items, $cart_id, 'wcal_ajax', $currency );
			foreach ( $items as $key => $item ) {
				$product            = wc_get_product( $details[ $key ]['product_id'] );
				$image_id           = $product ? $product->get_image_id() : 0;
				$data['products'][] = array(
					'product_id' => (int) $details[ $key ]['product_id'],
					'exists'     => (bool) $product,
					'name'       => wp_kses_post( $details[ $key ]['product_name'] ),
					'image_url'  => $image_id ? wp_get_attachment_image_url( $image_id, 'thumbnail' ) : '',
					'formatted'  => wp_kses_post( $details[ $key ]['item_total_formatted'] ),
					'quantity'   => (int) $details[ $key ]['qty'],
				);
			}
			$data['totals']['formatted'] = wp_kses_post( self::total_with_tax( $details, $cart_id, $currency ) );
		}

		$sent = $wpdb->get_results( // phpcs:ignore
			$wpdb->prepare(
				'SELECT wcet.`template_name`, wsht.`sent_time`, wsht.`sent_email_id` FROM `' . $wpdb->prefix . 'ac_sent_history_lite` AS wsht LEFT JOIN `' . $wpdb->prefix . 'ac_email_templates_lite` AS wcet ON wsht.template_id = wcet.id WHERE abandoned_order_id = %d AND wsht.template_id > 0', // phpcs:ignore
				$cart_id
			)
		);

		$notifications = array();
		foreach ( $sent as $email ) {
			$notifications[] = array(
				'type'     => 'email',
				'template' => $email->template_name,
				'contact'  => $email->sent_email_id,
				'sent_at'  => gmdate( 'c', strtotime( $email->sent_time ) ),
			);
		}
		$data['reminder_activity'] = array( 'notifications' => $notifications );

		return self::envelope( $data );
	}

	/**
	 * Delete one cart.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function delete_item( $request ) {
		$cart_id = (int) $request['id'];
		if ( ! self::find( $cart_id ) ) {
			return self::not_found();
		}
		( new Wcal_Delete_Handler() )->wcal_delete_bulk_action_handler_function( $cart_id );
		return self::envelope( array( 'deleted' => true ) );
	}

	/**
	 * Run a bulk delete.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function bulk_action( $request ) {
		$handler = new Wcal_Delete_Handler();

		switch ( $request->get_param( 'action' ) ) {
			case 'delete':
				$ids = array_filter( array_map( 'absint', (array) $request->get_param( 'ids' ) ) );
				if ( empty( $ids ) ) {
					return self::invalid( 'ids', __( 'Select at least one cart.', 'woocommerce-abandoned-cart' ) );
				}
				foreach ( $ids as $id ) {
					$handler->wcal_delete_bulk_action_handler_function( $id );
				}
				break;
			case 'delete_registered':
				$handler->wcal_bulk_action_delete_registered_carts_handler();
				break;
			case 'delete_guest':
				$handler->wcal_bulk_action_delete_guest_carts_handler();
				break;
			case 'delete_visitor':
				$handler->wcal_bulk_action_delete_visitor_carts_handler();
				break;
			case 'delete_all':
				$handler->wcal_bulk_action_delete_all_carts_handler();
				break;
		}

		return self::envelope( array( 'done' => true ) );
	}

	/**
	 * Decode the stored cart.
	 *
	 * @param object $row Cart row.
	 * @return object|array|null
	 */
	protected static function cart_info( $row ) {
		$info = json_decode( stripslashes( $row->abandoned_cart_info ) );
		return null === $info ? json_decode( $row->abandoned_cart_info ) : $info;
	}

	/**
	 * Row data shown in the list.
	 *
	 * @param object $row Cart row.
	 * @return array
	 */
	protected static function format_row( $row ) {
		$customer = self::customer( $row );
		$info     = self::cart_info( $row );
		$items    = isset( $info->cart ) ? $info->cart : new stdClass();

		$total    = 0;
		$quantity = 0;
		if ( is_object( $items ) ) {
			foreach ( $items as $item ) {
				if ( isset( $item->line_total ) ) {
					$total += $item->line_total;
				}
				if ( isset( $item->line_tax ) && $item->line_tax > 0 ) {
					$total += $item->line_tax;
				}
				$quantity += isset( $item->quantity ) ? $item->quantity : 0;
			}
		}

		$recovered_id = (int) $row->recovered_cart;
		$formatted    = wc_price( $total );
		$recovered    = null;
		if ( $recovered_id > 0 ) {
			$recovered = array(
				'order_id'    => $recovered_id,
				'order_url'   => '',
				'date'        => '',
				'order_total' => '',
			);
			$order     = wc_get_order( $recovered_id );
			if ( $order ) {
				$created   = $order->get_date_created();
				$timestamp = $created ? strtotime( $created->date( 'Y-m-d H:i:s' ) ) : (int) $row->abandoned_cart_time;
				$formatted = wc_price( $order->get_total() );

				$recovered['order_url']   = $order->get_edit_order_url();
				$recovered['date']        = date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $timestamp );
				$recovered['order_total'] = $formatted;
			}
		}

		return array(
			'id'                      => (int) $row->id,
			'email_id'                => $customer['email'],
			'user_type'               => $row->user_type,
			'billing'                 => array(
				'first_name' => $customer['first_name'],
				'last_name'  => $customer['last_name'],
				'phone'      => $customer['phone'],
			),
			'totals'                  => array(
				'total'     => (float) $total,
				'formatted' => $formatted,
			),
			'item_count'              => (int) $quantity,
			'abandoned_cart_time_iso' => gmdate( 'c', (int) $row->abandoned_cart_time ),
			'status'                  => $recovered_id > 0 ? 'recovered' : ( '1' === (string) $row->cart_ignored ? 'abandoned_new' : 'abandoned' ),
			'unsubscribed'            => '1' === (string) $row->unsubscribe_link,
			'recovered'               => $recovered,
		);
	}

	/**
	 * Customer email, name and phone.
	 *
	 * @param object $row Cart row.
	 * @return array
	 */
	protected static function customer( $row ) {
		global $wpdb;

		$user_id = (int) $row->user_id;

		if ( 'GUEST' === $row->user_type ) {
			if ( 0 === $user_id ) {
				return array(
					'email'      => '',
					'first_name' => __( 'Visitor', 'woocommerce-abandoned-cart' ),
					'last_name'  => '',
					'phone'      => '',
				);
			}
			$guest = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM `' . $wpdb->prefix . 'ac_guest_abandoned_cart_history_lite` WHERE id = %d', $user_id ) ); // phpcs:ignore
			return array(
				'email'      => isset( $guest->email_id ) ? $guest->email_id : '',
				'first_name' => isset( $guest->billing_first_name ) ? $guest->billing_first_name : '',
				'last_name'  => isset( $guest->billing_last_name ) ? $guest->billing_last_name : '',
				'phone'      => isset( $guest->phone ) ? $guest->phone : '',
			);
		}

		$user_data = get_userdata( $user_id );
		$email     = get_user_meta( $user_id, 'billing_email', true );
		if ( '' === $email || false === $email ) {
			$email = ! empty( $user_data->user_email ) ? $user_data->user_email : __( 'User Deleted', 'woocommerce-abandoned-cart' );
		}
		$first_name = get_user_meta( $user_id, 'billing_first_name', true );
		$last_name  = get_user_meta( $user_id, 'billing_last_name', true );

		return array(
			'email'      => $email,
			'first_name' => '' !== (string) $first_name ? $first_name : ( ! empty( $user_data->first_name ) ? $user_data->first_name : '' ),
			'last_name'  => '' !== (string) $last_name ? $last_name : ( ! empty( $user_data->last_name ) ? $user_data->last_name : '' ),
			'phone'      => (string) get_user_meta( $user_id, 'billing_phone', true ),
		);
	}

	/**
	 * Status text shown on the popup.
	 *
	 * @param object $row Cart row.
	 * @return string
	 */
	protected static function status_label( $row ) {
		if ( $row->recovered_cart > 0 ) {
			return __( 'Recovered', 'woocommerce-abandoned-cart' );
		}
		return '1' === (string) $row->cart_ignored ? __( 'Abandoned but new cart created', 'woocommerce-abandoned-cart' ) : __( 'Abandoned', 'woocommerce-abandoned-cart' );
	}

	/**
	 * Billing address.
	 *
	 * @param object $row Cart row.
	 * @return array
	 */
	protected static function billing_address( $row ) {
		global $wpdb;

		$user_id = (int) $row->user_id;
		$address = array(
			'company'   => '',
			'address_1' => '',
			'address_2' => '',
			'city'      => '',
			'state'     => '',
			'postcode'  => '',
			'country'   => '',
		);

		if ( 'GUEST' === $row->user_type ) {
			if ( $user_id > 0 ) {
				$guest               = $wpdb->get_row( $wpdb->prepare( 'SELECT billing_zipcode FROM `' . $wpdb->prefix . 'ac_guest_abandoned_cart_history_lite` WHERE id = %d', $user_id ) ); // phpcs:ignore
				$address['postcode'] = isset( $guest->billing_zipcode ) ? $guest->billing_zipcode : '';
			}
			return $address;
		}

		if ( $user_id > 0 ) {
			$billing = Wcal_Abandoned_Cart_Details::wcal_get_billing_details( $user_id );
			foreach ( array_keys( $address ) as $field ) {
				$address[ $field ] = $billing[ 'billing_' . $field ];
			}
		}
		return $address;
	}

	/**
	 * Shipping address.
	 *
	 * @param object $row Cart row.
	 * @return array
	 */
	protected static function shipping_address( $row ) {
		global $wpdb;

		$user_id = (int) $row->user_id;
		$address = array(
			'company'   => '',
			'address_1' => '',
			'address_2' => '',
			'city'      => '',
			'state'     => '',
			'postcode'  => '',
			'country'   => '',
		);

		if ( 'GUEST' === $row->user_type ) {
			if ( $user_id > 0 ) {
				$guest               = $wpdb->get_row( $wpdb->prepare( 'SELECT shipping_zipcode FROM `' . $wpdb->prefix . 'ac_guest_abandoned_cart_history_lite` WHERE id = %d', $user_id ) ); // phpcs:ignore
				$address['postcode'] = isset( $guest->shipping_zipcode ) ? $guest->shipping_zipcode : '';
			}
			return $address;
		}

		if ( $user_id > 0 ) {
			foreach ( array_keys( $address ) as $field ) {
				$address[ $field ] = (string) get_user_meta( $user_id, 'shipping_' . $field, true );
			}
			$country_code = $address['country'];
			if ( isset( WC()->countries->countries[ $country_code ] ) ) {
				$address['country'] = WC()->countries->countries[ $country_code ];
			}
			if ( isset( WC()->countries->states[ $country_code ][ $address['state'] ] ) ) {
				$address['state'] = WC()->countries->states[ $country_code ][ $address['state'] ];
			}
		}
		return $address;
	}

	/**
	 * Shipping charges stored with the cart.
	 *
	 * @param object       $row  Cart row.
	 * @param object|array $info Decoded cart.
	 * @return float
	 */
	protected static function shipping_charges( $row, $info ) {
		global $wpdb;

		$user_id = (int) $row->user_id;
		if ( 'GUEST' === $row->user_type ) {
			if ( 0 === $user_id ) {
				return 0;
			}
			$guest = $wpdb->get_row( $wpdb->prepare( 'SELECT shipping_charges FROM `' . $wpdb->prefix . 'ac_guest_abandoned_cart_history_lite` WHERE id = %d', $user_id ) ); // phpcs:ignore
			return isset( $guest->shipping_charges ) ? (float) $guest->shipping_charges : 0;
		}
		return isset( $info->shipping_charges ) ? (float) $info->shipping_charges : 0;
	}

	/**
	 * Cart total with the tax text, as the popup has always shown it.
	 *
	 * @param array  $details  Output of wcal_get_cart_details().
	 * @param int    $cart_id  Cart ID.
	 * @param string $currency Currency.
	 * @return string
	 */
	protected static function total_with_tax( $details, $cart_id, $currency ) {
		$price = function ( $amount ) use ( $cart_id, $currency ) {
			return apply_filters( 'acfac_change_currency', wcal_common::wcal_get_price( $amount, $currency ), $cart_id, $amount, 'wcal_ajax' );
		};

		$cart_total = $details['cart_total'] > 0 ? $details['cart_total'] : 0;
		$tax_total  = $details['line_subtotal_tax_total'] > 0 ? $details['line_subtotal_tax_total'] : 0;
		$output     = $price( $cart_total );

		if ( 'yes' === get_option( 'woocommerce_calc_taxes' ) && apply_filters( 'wcal_show_taxes', true ) ) {
			if ( 'no' === get_option( 'woocommerce_prices_include_tax' ) ) {
				/* translators: %s: tax amount */
				$output .= '<br>' . sprintf( __( 'Tax: %s', 'woocommerce-abandoned-cart' ), $price( $tax_total ) );
			} elseif ( 'yes' === get_option( 'woocommerce_prices_include_tax' ) ) {
				/* translators: %s: tax amount */
				$output .= ' (' . sprintf( __( 'includes Tax: %s', 'woocommerce-abandoned-cart' ), $price( $tax_total ) ) . ')';
			}
		}
		return $output;
	}
}
