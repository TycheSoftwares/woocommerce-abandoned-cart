<?php
/**
 * WP List Table Abandoned Orders.
 *
 * @package Abandoned-Cart-Lite-for-WooCommerce
 */

// Load WP_List_Table if not loaded.
if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Abandoned Cart Lite for WooCommerce
 *
 * It will handle the common action for the plugin.
 *
 * @author  Tyche Softwares
 * @package Abandoned-Cart-Lite-for-WooCommerce/Admin/List-Class
 * @since 2.5.2
 */
class WCAL_Abandoned_Orders_Table extends WP_List_Table {

	/**
	 * Number of results to show per page
	 *
	 * @var string
	 * @since 2.5.2
	 */
	public $per_page = 30;

	/**
	 * URL of this page
	 *
	 * @var string
	 * @since 2.5.2
	 */
	public $base_url;

	/**
	 * Total number of bookings
	 *
	 * @var int
	 * @since 2.5.2
	 */
	public $total_count;

	/**
	 * It will add the bulk action function and other variable needed for the class.
	 *
	 * @since 2.5.2
	 * @see WP_List_Table::__construct()
	 */
	public function __construct() {
		global $status, $page;
		// Set parent defaults.
		parent::__construct(
			array(
				'singular' => __( 'abandoned_order_id', 'woocommerce-abandoned-cart' ), // singular name of the listed records.
				'plural'   => __( 'abandoned_order_ids', 'woocommerce-abandoned-cart' ), // plural name of the listed records.
				'ajax'     => false,                         // Does this table support ajax?
			)
		);
		$this->process_bulk_action();
		$this->base_url = admin_url( 'admin.php?page=woocommerce_ac_page' );
	}

	/**
	 * It will prepare the list of the abandoned carts, like columns, pagination, sortable column, all data.
	 *
	 * @since 2.5.2
	 */
	public function wcal_abandoned_order_prepare_items() {
		$columns               = $this->get_columns();
		$hidden                = array(); // No hidden columns.
		$sortable              = $this->get_sortable_columns();
		$this->total_count     = $this->wcal_get_total_count();
		$data                  = $this->wcal_abandoned_cart_data();
		$this->_column_headers = array( $columns, $hidden, $sortable );
		$total_items           = $this->total_count;

		if ( count( $data ) > 0 ) {
			$this->items = $data;
		} else {
			$this->items = array();
		}
		$this->set_pagination_args(
			array(
				'total_items' => $total_items, // We have to calculate the total number of items.
				'per_page'    => $this->per_page, // We have to determine how many items to show on a page.
				'total_pages' => ceil( $total_items / $this->per_page ), // We have to calculate the total number of pages.
			)
		);
	}

	/**
	 * It will add the columns for abanodned orders list.
	 *
	 * @return array $columns All columns name.
	 * @since 2.5.2
	 */
	public function get_columns() {
		$columns = array();
		$columns = array(
			'cb'          => '<input type="checkbox" />',
			'id'          => __( 'Id', 'woocommerce-abandoned-cart' ),
			'email'       => __( 'Email Address', 'woocommerce-abandoned-cart' ),
			'customer'    => __( 'Customer', 'woocommerce-abandoned-cart' ),
			'order_total' => __( 'Order Total', 'woocommerce-abandoned-cart' ),
			'date'        => __( 'Abandoned Date', 'woocommerce-abandoned-cart' ),
			'status'      => __( 'Status of Cart', 'woocommerce-abandoned-cart' ),
		);
		return apply_filters( 'wcal_abandoned_orders_columns', $columns );
	}

	/**
	 * It is used to add the check box for the items.
	 *
	 * @param object $item - Item in the WP List.
	 * @return string html for checkbox.
	 * @since 2.5.2
	 */
	public function column_cb( $item ) {
		$abandoned_order_id = '';
		if ( isset( $item->id ) && $item->id > 0 ) {
			$abandoned_order_id = $item->id;
		}
		return sprintf(
			'<input type="checkbox" name="%1$s[]" value="%2$s" />',
			'abandoned_order_id',
			$abandoned_order_id
		);
	}

	/**
	 * We can mention on which column we need the sorting. Here, abandoned cart date, abandoned cart status
	 *
	 * @return array $columns Name of the column
	 * @since 2.5.2
	 */
	public function get_sortable_columns() {
		$columns = array(
			'date'   => array( 'date', false ),
			'status' => array( 'status', false ),
		);
		return apply_filters( 'wcal_abandoned_orders_sortable_columns', $columns );
	}

	/**
	 * Render the Email Column. So we will add the action on the hover affect.
	 * This function used for individual delete of row, It is for hover effect delete.
	 *
	 * @param array $abandoned_row_info Contains all the data of the abandoned order tabs row .
	 * @return string $value shown in the Email column.
	 * @since 2.5.2
	 */
	public function column_email( $abandoned_row_info ) {
		$row_actions        = array();
		$value              = '';
		$abandoned_order_id = 0;
		if ( isset( $abandoned_row_info->email ) ) {
			$abandoned_order_id = $abandoned_row_info->id;

			$wcal_array = array(
				'action'  => 'wcal_abandoned_cart_info',
				'cart_id' => $abandoned_order_id,
			);
			$wcal_url   = add_query_arg(
				$wcal_array,
				admin_url( 'admin-ajax.php' )
			);

			$row_actions['edit']   = '<a oncontextmenu="return false;" class="wcal-button-icon wcal-js-open-modal" data-modal-type="ajax" data-wcal-cart-id="' . $abandoned_order_id . '" href="' . $wcal_url . '">' . __( 'View order', 'woocommerce-abandoned-cart' ) . '</a>';
			$row_actions['delete'] = '<a href="' . wp_nonce_url(
				add_query_arg(
					array(
						'action'             => 'wcal_delete',
						'abandoned_order_id' => $abandoned_row_info->id,
					),
					$this->base_url
				),
				'abandoned_order_nonce'
			) . '">' . __( 'Delete', 'woocommerce-abandoned-cart' ) . '</a>';
			$email                 = $abandoned_row_info->email;
			$value                 = $email . $this->row_actions( $row_actions );
		}
		return apply_filters( 'wcal_abandoned_orders_single_column', $value, $abandoned_order_id, 'email' );
	}

	/**
	 * Return the total count of records for each view.
	 *
	 * @return int $count - Total number of records.
	 * @since 5.8.1
	 */
	public function wcal_get_total_count() {

		global $wpdb;
		$get_section_of_page   = self::wcal_get_current_section();
		$blank_cart_info       = '{"cart":[]}';
		$blank_cart_info_guest = '[]';
		$blank_cart            = '""';
		$count                 = 0;

		$ac_cutoff_time = get_option( 'ac_lite_cart_abandoned_time', 10 );
		$cut_off_time   = intval( $ac_cutoff_time ) * 60;
		$compare_time   = current_time( 'timestamp' ) - $cut_off_time; // phpcs:ignore

		switch ( $get_section_of_page ) {
			case 'wcal_all_abandoned':
				if ( is_multisite() ) {
					// Get main site's table prefix.
					$main_prefix = $wpdb->get_blog_prefix( 1 );
					$count       = $wpdb->get_var( // phpcs:ignore
						$wpdb->prepare(
							'SELECT count( wpac.id ) FROM `' . $wpdb->prefix . 'ac_abandoned_cart_history_lite` AS wpac LEFT JOIN ' . $main_prefix . "users AS wpu ON wpac.user_id = wpu.id WHERE wpac.recovered_cart='0' AND wpac.cart_ignored <> '1' AND wpac.abandoned_cart_info NOT LIKE %s AND wpac.abandoned_cart_info NOT LIKE %s AND wpac.abandoned_cart_info NOT LIKE %s AND wpac.abandoned_cart_time <= %d ORDER BY wpac.abandoned_cart_time DESC", // phpcs:ignore
							"%$blank_cart_info%",
							$blank_cart_info_guest,
							$blank_cart,
							$compare_time
						)
					);
				} else {
					// Single site - regular table name.
					$count = $wpdb->get_var( // phpcs:ignore
						$wpdb->prepare(
							'SELECT count( wpac.id ) FROM `' . $wpdb->prefix . 'ac_abandoned_cart_history_lite` AS wpac LEFT JOIN ' . $wpdb->prefix . "users AS wpu ON wpac.user_id = wpu.id WHERE wpac.recovered_cart='0' AND wpac.cart_ignored <> '1' AND wpac.abandoned_cart_info NOT LIKE %s AND wpac.abandoned_cart_info NOT LIKE %s AND wpac.abandoned_cart_info NOT LIKE %s AND wpac.abandoned_cart_time <= %d ORDER BY wpac.abandoned_cart_time DESC",
							"%$blank_cart_info%",
							$blank_cart_info_guest,
							$blank_cart,
							$compare_time
						)
					);
				}
				break;

			case 'wcal_all_registered':
				if ( is_multisite() ) {
					// Get main site's table prefix.
					$main_prefix = $wpdb->get_blog_prefix( 1 );
					$count       = $wpdb->get_var( // phpcs:ignore
						$wpdb->prepare(
							'SELECT count( wpac.id ) FROM `' . $wpdb->prefix . 'ac_abandoned_cart_history_lite` AS wpac LEFT JOIN ' . $main_prefix . "users AS wpu ON wpac.user_id = wpu.id WHERE wpac.recovered_cart='0' AND wpac.cart_ignored <> '1' AND wpac.abandoned_cart_info NOT LIKE %s AND wpac.abandoned_cart_info NOT LIKE %s AND wpac.abandoned_cart_info NOT LIKE %s AND wpac.abandoned_cart_time <= %d ORDER BY wpac.abandoned_cart_time DESC", // phpcs:ignore
							"%$blank_cart_info%",
							$blank_cart_info_guest,
							$blank_cart,
							$compare_time
						)
					);
				} else {
					// Single site - regular table name.
					$count = $wpdb->get_var( // phpcs:ignore
						$wpdb->prepare(
							'SELECT count( wpac.id ) FROM `' . $wpdb->prefix . 'ac_abandoned_cart_history_lite` AS wpac LEFT JOIN ' . $wpdb->prefix . "users AS wpu ON wpac.user_id = wpu.id WHERE wpac.recovered_cart='0' AND wpac.cart_ignored <> '1' AND wpac.abandoned_cart_info NOT LIKE %s AND wpac.user_type = 'REGISTERED' AND wpac.abandoned_cart_info NOT LIKE %s AND wpac.abandoned_cart_time <= %d AND wpac.abandoned_cart_info NOT LIKE %s ORDER BY wpac.abandoned_cart_time DESC",
							"%$blank_cart_info%",
							"%$blank_cart%",
							$blank_cart,
							$compare_time
						)
					);
				}
				break;

			case 'wcal_all_guest':
				if ( is_multisite() ) {
					// Get main site's table prefix.
					$main_prefix = $wpdb->get_blog_prefix( 1 );
					$count       = $wpdb->get_var( // phpcs:ignore
						$wpdb->prepare(
							'SELECT count( wpac.id ) FROM `' . $wpdb->prefix . 'ac_abandoned_cart_history_lite` AS wpac LEFT JOIN ' . $main_prefix . "users AS wpu ON wpac.user_id = wpu.id WHERE wpac.recovered_cart='0' AND wpac.cart_ignored <> '1' AND wpac.abandoned_cart_info NOT LIKE %s AND wpac.abandoned_cart_info NOT LIKE %s AND wpac.abandoned_cart_info NOT LIKE %s AND wpac.abandoned_cart_time <= %d AND wpac.user_id >= 63000000  ORDER BY wpac.abandoned_cart_time DESC", // phpcs:ignore
							$blank_cart_info_guest,
							$blank_cart_info,
							$blank_cart,
							$compare_time
						)
					);
				} else {
					// Single site - regular table name.
					$count = $wpdb->get_var( // phpcs:ignore
						$wpdb->prepare(
							'SELECT count( wpac.id ) FROM `' . $wpdb->prefix . 'ac_abandoned_cart_history_lite` AS wpac LEFT JOIN ' . $wpdb->prefix . "users AS wpu ON wpac.user_id = wpu.id WHERE wpac.recovered_cart='0' AND wpac.cart_ignored <> '1' AND wpac.abandoned_cart_info NOT LIKE %s AND wpac.abandoned_cart_info NOT LIKE %s AND wpac.abandoned_cart_info NOT LIKE %s AND wpac.abandoned_cart_time <= %d AND wpac.user_id >= 63000000 ORDER BY wpac.abandoned_cart_time DESC",
							$blank_cart_info_guest,
							$blank_cart_info,
							$blank_cart,
							$compare_time
						)
					);
				}
				break;

			case 'wcal_all_visitor':
				if ( is_multisite() ) {
					// Get main site's table prefix.
					$main_prefix = $wpdb->get_blog_prefix( 1 );
					$count       = $wpdb->get_var( // phpcs:ignore
						$wpdb->prepare(
							'SELECT count( wpac.id ) FROM `' . $wpdb->prefix . 'ac_abandoned_cart_history_lite` AS wpac LEFT JOIN ' . $main_prefix . "users AS wpu ON wpac.user_id = wpu.id WHERE wpac.recovered_cart='0' AND wpac.cart_ignored <> '1' AND wpac.abandoned_cart_info NOT LIKE %s AND wpac.abandoned_cart_info NOT LIKE %s AND wpac.abandoned_cart_info NOT LIKE %s AND wpac.abandoned_cart_time <= %d AND wpac.user_id >= 63000000 ORDER BY wpac.abandoned_cart_time DESC", // phpcs:ignore
							$blank_cart_info_guest,
							$blank_cart_info,
							$blank_cart,
							$compare_time
						)
					);
				} else {
					// Single site - regular table name.
					$count = $wpdb->get_var( // phpcs:ignore
						$wpdb->prepare(
							'SELECT count( wpac.id ) FROM `' . $wpdb->prefix . 'ac_abandoned_cart_history_lite` AS wpac LEFT JOIN ' . $wpdb->prefix . "users AS wpu ON wpac.user_id = wpu.id WHERE wpac.recovered_cart='0' AND wpac.cart_ignored <> '1' AND wpac.abandoned_cart_info NOT LIKE %s AND wpac.abandoned_cart_info NOT LIKE %s AND wpac.abandoned_cart_info NOT LIKE %s AND wpac.abandoned_cart_time <= %d AND wpac.user_id = 0 ORDER BY wpac.abandoned_cart_time DESC",
							$blank_cart_info_guest,
							$blank_cart_info,
							$blank_cart,
							$compare_time
						)
					);
				}
				break;

			default:
				break;
		}

		return $count;

	}

	/**
	 * It will generate the abandoned cart list data.
	 *
	 * @globals mixed $wpdb
	 * @return array $return_abandoned_orders_display Key and value of all the columns
	 * @since 2.5.2
	 */
	public function wcal_abandoned_cart_data() {
		global $wpdb;
		$return_abandoned_orders = array();
		$per_page                = $this->per_page;
		$results                 = array();
		$blank_cart_info         = '{"cart":[]}';
		$blank_cart_info_guest   = '[]';
		$blank_cart              = '""';
		$get_section_of_page     = self::wcal_get_current_section();
		$results                 = array();

		$ac_cutoff_time = get_option( 'ac_lite_cart_abandoned_time', 10 );
		$cut_off_time   = intval( $ac_cutoff_time ) * 60;
		$compare_time   = current_time( 'timestamp' ) - $cut_off_time; // phpcs:ignore

		$page_number    = isset( $_GET['paged'] ) && $_GET['paged'] > 1 ? sanitize_text_field( wp_unslash( $_GET['paged'] ) ) - 1 : 0; // phpcs:ignore
		if ( $page_number > 0 ) {
			$offset = $page_number * $per_page;
		} else {
			$offset = 0;
		}

		switch ( $get_section_of_page ) {
			case 'wcal_all_abandoned':
				if ( is_multisite() ) {
					// Get main site's table prefix.
					$main_prefix = $wpdb->get_blog_prefix( 1 );
					$results     = $wpdb->get_results( // phpcs:ignore
						$wpdb->prepare(
							'SELECT wpac . * , wpu.user_login, wpu.user_email FROM `' . $wpdb->prefix . 'ac_abandoned_cart_history_lite` AS wpac LEFT JOIN ' . $main_prefix . "users AS wpu ON wpac.user_id = wpu.id WHERE wpac.recovered_cart='0' AND wpac.cart_ignored <> '1' AND wpac.abandoned_cart_info NOT LIKE %s AND wpac.abandoned_cart_info NOT LIKE %s AND wpac.abandoned_cart_info NOT LIKE %s AND wpac.abandoned_cart_time <= %d ORDER BY wpac.abandoned_cart_time DESC LIMIT %d OFFSET %d", // phpcs:ignore
							"%$blank_cart_info%",
							$blank_cart_info_guest,
							$blank_cart,
							$compare_time,
							$per_page,
							$offset
						)
					);
				} else {
					// Single site - regular table name.
					$results = $wpdb->get_results( // phpcs:ignore
						$wpdb->prepare(
							'SELECT wpac . * , wpu.user_login, wpu.user_email FROM `' . $wpdb->prefix . 'ac_abandoned_cart_history_lite` AS wpac LEFT JOIN ' . $wpdb->prefix . "users AS wpu ON wpac.user_id = wpu.id WHERE wpac.recovered_cart='0' AND wpac.cart_ignored <> '1' AND wpac.abandoned_cart_info NOT LIKE %s AND wpac.abandoned_cart_info NOT LIKE %s AND wpac.abandoned_cart_info NOT LIKE %s AND wpac.abandoned_cart_time <= %d ORDER BY wpac.abandoned_cart_time DESC LIMIT %d OFFSET %d",
							"%$blank_cart_info%",
							$blank_cart_info_guest,
							$blank_cart,
							$compare_time,
							$per_page,
							$offset
						)
					);
				}
				break;

			case 'wcal_all_registered':
				if ( is_multisite() ) {
					// Get main site's table prefix.
					$main_prefix = $wpdb->get_blog_prefix( 1 );
					$results     = $wpdb->get_results( // phpcs:ignore
						$wpdb->prepare(
							'SELECT wpac . * , wpu.user_login, wpu.user_email FROM `' . $wpdb->prefix . 'ac_abandoned_cart_history_lite` AS wpac LEFT JOIN ' . $main_prefix . "users AS wpu ON wpac.user_id = wpu.id WHERE wpac.recovered_cart='0' AND wpac.cart_ignored <> '1' AND wpac.abandoned_cart_info NOT LIKE %s AND wpac.abandoned_cart_info NOT LIKE %s AND wpac.abandoned_cart_info NOT LIKE %s AND wpac.abandoned_cart_time <= %d ORDER BY wpac.abandoned_cart_time DESC LIMIT %d OFFSET %d", // phpcs:ignore
							"%$blank_cart_info%",
							$blank_cart_info_guest,
							$blank_cart,
							$compare_time,
							$per_page,
							$offset
						)
					);
				} else {
					// Single site - regular table name.
					$results = $wpdb->get_results( // phpcs:ignore
						$wpdb->prepare(
							'SELECT wpac . * , wpu.user_login, wpu.user_email FROM `' . $wpdb->prefix . 'ac_abandoned_cart_history_lite` AS wpac LEFT JOIN ' . $wpdb->prefix . "users AS wpu ON wpac.user_id = wpu.id WHERE wpac.recovered_cart='0' AND wpac.cart_ignored <> '1' AND wpac.abandoned_cart_info NOT LIKE %s AND wpac.user_type = 'REGISTERED' AND wpac.abandoned_cart_info NOT LIKE %s AND wpac.abandoned_cart_info NOT LIKE %s AND wpac.abandoned_cart_time <= %d ORDER BY wpac.abandoned_cart_time DESC LIMIT %d OFFSET %d",
							"%$blank_cart_info%",
							"%$blank_cart%",
							$blank_cart,
							$compare_time,
							$per_page,
							$offset
						)
					);
				}
				break;

			case 'wcal_all_guest':
				if ( is_multisite() ) {
					// Get main site's table prefix.
					$main_prefix = $wpdb->get_blog_prefix( 1 );
					$results     = $wpdb->get_results( // phpcs:ignore
						$wpdb->prepare(
							'SELECT wpac . * , wpu.user_login, wpu.user_email FROM `' . $wpdb->prefix . 'ac_abandoned_cart_history_lite` AS wpac LEFT JOIN ' . $main_prefix . "users AS wpu ON wpac.user_id = wpu.id WHERE wpac.recovered_cart='0' AND wpac.cart_ignored <> '1' AND wpac.abandoned_cart_info NOT LIKE %s AND wpac.abandoned_cart_info NOT LIKE %s AND wpac.abandoned_cart_info NOT LIKE %s AND wpac.abandoned_cart_time <= %d AND wpac.user_id >= 63000000  ORDER BY wpac.abandoned_cart_time DESC LIMIT %d OFFSET %d", // phpcs:ignore
							$blank_cart_info_guest,
							$blank_cart_info,
							$blank_cart,
							$compare_time,
							$per_page,
							$offset
						)
					);
				} else {
					// Single site - regular table name.
					$results = $wpdb->get_results( // phpcs:ignore
						$wpdb->prepare(
							'SELECT wpac . * , wpu.user_login, wpu.user_email FROM `' . $wpdb->prefix . 'ac_abandoned_cart_history_lite` AS wpac LEFT JOIN ' . $wpdb->prefix . "users AS wpu ON wpac.user_id = wpu.id WHERE wpac.recovered_cart='0' AND wpac.cart_ignored <> '1' AND wpac.abandoned_cart_info NOT LIKE %s AND wpac.abandoned_cart_info NOT LIKE %s AND wpac.abandoned_cart_info NOT LIKE %s AND wpac.abandoned_cart_time <= %d AND wpac.user_id >= 63000000 ORDER BY wpac.abandoned_cart_time DESC LIMIT %d OFFSET %d",
							$blank_cart_info_guest,
							$blank_cart_info,
							$blank_cart,
							$compare_time,
							$per_page,
							$offset
						)
					);
				}
				break;

			case 'wcal_all_visitor':
				if ( is_multisite() ) {
					// Get main site's table prefix.
					$main_prefix = $wpdb->get_blog_prefix( 1 );
					$results     = $wpdb->get_results( // phpcs:ignore
						$wpdb->prepare(
							'SELECT wpac . * , wpu.user_login, wpu.user_email FROM `' . $wpdb->prefix . 'ac_abandoned_cart_history_lite` AS wpac LEFT JOIN ' . $main_prefix . "users AS wpu ON wpac.user_id = wpu.id WHERE wpac.recovered_cart='0' AND wpac.cart_ignored <> '1' AND wpac.abandoned_cart_info NOT LIKE %s AND wpac.abandoned_cart_info NOT LIKE %s AND wpac.abandoned_cart_info NOT LIKE %s AND wpac.abandoned_cart_time <= %d AND wpac.user_id >= 63000000 ORDER BY wpac.abandoned_cart_time DESC LIMIT %d OFFSET %d", // phpcs:ignore
							$blank_cart_info_guest,
							$blank_cart_info,
							$blank_cart,
							$compare_time,
							$per_page,
							$offset
						)
					);
				} else {
					// Single site - regular table name.
					$results = $wpdb->get_results( // phpcs:ignore
						$wpdb->prepare(
							'SELECT wpac . * , wpu.user_login, wpu.user_email FROM `' . $wpdb->prefix . 'ac_abandoned_cart_history_lite` AS wpac LEFT JOIN ' . $wpdb->prefix . "users AS wpu ON wpac.user_id = wpu.id WHERE wpac.recovered_cart='0' AND wpac.cart_ignored <> '1' AND wpac.abandoned_cart_info NOT LIKE %s AND wpac.abandoned_cart_info NOT LIKE %s AND wpac.abandoned_cart_info NOT LIKE %s AND wpac.abandoned_cart_time <= %d AND wpac.user_id = 0 ORDER BY wpac.abandoned_cart_time DESC LIMIT %d OFFSET %d",
							$blank_cart_info_guest,
							$blank_cart_info,
							$blank_cart,
							$compare_time,
							$per_page,
							$offset
						)
					);
				}
				break;

			default:
				break;
		}
		$i = 0;

		$process_wcal_abandoned_orders = $results;
		foreach ( $process_wcal_abandoned_orders as $key => $value ) {
			if ( 'GUEST' === $value->user_type ) {
				$results_guest = $wpdb->get_results( // phpcs:ignore
					$wpdb->prepare(
						'SELECT * from `' . $wpdb->prefix . 'ac_guest_abandoned_cart_history_lite` WHERE id = %d',
						$value->user_id
					)
				);
			}
			$abandoned_order_id = $value->id;
			$user_id            = $value->user_id;
			$user_login         = $value->user_login;

			if ( 'GUEST' === $value->user_type ) {

				if ( isset( $results_guest[0]->email_id ) ) {
					$user_email = $results_guest[0]->email_id;
				} elseif ( '0' === $value->user_id ) {
					$user_email = '';
				} else {
					$user_email = '';
				}

				if ( isset( $results_guest[0]->billing_first_name ) ) {
					$user_first_name = $results_guest[0]->billing_first_name;
				} elseif ( '0' === $value->user_id ) {
					$user_first_name = 'Visitor';
				} else {
					$user_first_name = '';
				}

				if ( isset( $results_guest[0]->billing_last_name ) ) {
					$user_last_name = $results_guest[0]->billing_last_name;
				} elseif ( '0' === $value->user_id ) {
					$user_last_name = '';
				} else {
					$user_last_name = '';
				}
			} else {
				$user_email_biiling = get_user_meta( $user_id, 'billing_email', true );
				$user_email         = __( 'User Deleted', 'woocommerce-abandoned-cart' );
				if ( isset( $user_email_biiling ) && '' === $user_email_biiling ) {
					$user_data = get_userdata( $user_id );
					if ( isset( $user_data->user_email ) && '' !== $user_data->user_email ) {
						$user_email = $user_data->user_email;
					}
				} elseif ( '' !== $user_email_biiling ) {
					$user_email = $user_email_biiling;
				}
				$user_first_name_temp = get_user_meta( $user_id, 'billing_first_name', true );
				if ( isset( $user_first_name_temp ) && '' === $user_first_name_temp ) {
					$user_data = get_userdata( $user_id );
					if ( isset( $user_data->first_name ) && '' !== $user_data->first_name ) {
						$user_first_name = $user_data->first_name;
					} else {
						$user_first_name = '';
					}
				} else {
					$user_first_name = $user_first_name_temp;
				}

				$user_last_name_temp = get_user_meta( $user_id, 'billing_last_name', true );
				if ( isset( $user_last_name_temp ) && '' === $user_last_name_temp ) {
					$user_data = get_userdata( $user_id );
					if ( isset( $user_data->last_name ) && '' !== $user_data->last_name ) {
						$user_last_name = $user_data->last_name;
					} else {
						$user_last_name = '';
					}
				} else {
					$user_last_name = $user_last_name_temp;
				}
			}

			$cart_info        = json_decode( $value->abandoned_cart_info );
			$order_date       = '';
			$cart_update_time = $value->abandoned_cart_time;

			if ( $cart_update_time > 0 ) {
				$date_format = date_i18n( get_option( 'date_format' ), $cart_update_time );
				$time_format = date_i18n( get_option( 'time_format' ), $cart_update_time );
				$order_date  = $date_format . ' ' . $time_format;
			}

			$cart_details = new stdClass();
			if ( isset( $cart_info->cart ) ) {
				$cart_details = $cart_info->cart;
			}
			$line_total = 0;

			if ( count( get_object_vars( $cart_details ) ) > 0 ) {
				foreach ( $cart_details as $k => $v ) {
					if ( isset( $v->line_tax, $v->line_total ) && $v->line_tax > 0 && $v->line_tax > 0 ) {
						$line_total = $line_total + $v->line_total + $v->line_tax;
					} elseif ( isset( $v->line_total ) ) {
						$line_total = $line_total + $v->line_total;
					}
				}
			}

			$line_total     = wc_price( $line_total );
			$quantity_total = 0;

			if ( count( get_object_vars( $cart_details ) ) > 0 ) {
				foreach ( $cart_details as $k => $v ) {
					$quantity_total = $quantity_total + $v->quantity;
				}
			}

			if ( $quantity_total > 1 ) {
				$item_disp = __( 'items', 'woocommerce-abandoned-cart' );
			} else {
				$item_disp = __( 'item', 'woocommerce-abandoned-cart' );
			}

			if ( 1 === $value->unsubscribe_link ) {
				$ac_status = __( 'Unsubscribed', 'woocommerce-abandoned-cart' );
			} elseif ( '0' === $value->cart_ignored && '0' === $value->recovered_cart ) {
				$ac_status = __( 'Abandoned', 'woocommerce-abandoned-cart' );
			} else {
				$ac_status = '';
			}

			if ( '' !== $ac_status ) {
				$return_abandoned_orders[ $i ] = new stdClass();
				if ( $quantity_total > 0 ) {
					$abandoned_order_id                         = $abandoned_order_id;
					$customer_information                       = $user_first_name . ' ' . $user_last_name;
					$return_abandoned_orders[ $i ]->id          = $abandoned_order_id;
					$return_abandoned_orders[ $i ]->email       = $user_email;
					$return_abandoned_orders[ $i ]->customer    = $customer_information;
					$return_abandoned_orders[ $i ]->order_total = $line_total;
					$return_abandoned_orders[ $i ]->date        = $order_date;
					$return_abandoned_orders[ $i ]->status      = $ac_status;
				} else {
					$abandoned_order_id                    = $abandoned_order_id;
					$return_abandoned_orders[ $i ]->id     = $abandoned_order_id;
					$return_abandoned_orders[ $i ]->date   = $order_date;
					$return_abandoned_orders[ $i ]->status = $ac_status;
				}
				$i++;
			}
		}
		// Sort for order date.
		if ( isset( $_GET['orderby'] ) && 'date' === $_GET['orderby'] ) { // phpcs:ignore WordPress.Security.NonceVerification
			if ( isset( $_GET['order'] ) && 'asc' === $_GET['order'] ) { // phpcs:ignore WordPress.Security.NonceVerification
				usort( $return_abandoned_orders, array( __CLASS__, 'wcal_class_order_date_asc' ) );
			} else {
				usort( $return_abandoned_orders, array( __CLASS__, 'wcal_class_order_date_dsc' ) );
			}
		} elseif ( isset( $_GET['orderby'] ) && 'status' === $_GET['orderby'] ) { // phpcs:ignore WordPress.Security.NonceVerification
			if ( isset( $_GET['order'] ) && 'asc' === $_GET['order'] ) { // phpcs:ignore WordPress.Security.NonceVerification
					usort( $return_abandoned_orders, array( __CLASS__, 'wcal_class_status_asc' ) );
			} else {
				usort( $return_abandoned_orders, array( __CLASS__, 'wcal_class_status_dsc' ) );
			}
		}

		return apply_filters( 'wcal_abandoned_orders_table_data', $return_abandoned_orders );
	}

	/**
	 * It will sort the ascending data based on the abandoned cart date.
	 *
	 * @param array | object $value1 All data of the list.
	 * @param array | object $value2 All data of the list.
	 * @return timestamp
	 * @since 2.5.2
	 */
	public function wcal_class_order_date_asc( $value1, $value2 ) {
		$date_two           = '';
		$date_one           = '';
		$value_one          = $value1->date;
		$value_two          = $value2->date;
		$date_formatted_one = date_create_from_format( 'd M, Y h:i A', $value_one );
		if ( isset( $date_formatted_one ) && '' !== $date_formatted_one ) {
			$date_one = date_format( $date_formatted_one, 'Y-m-d h:i A' );
		}

		$date_formatted_two = date_create_from_format( 'd M, Y h:i A', $value_two );
		if ( isset( $date_formatted_two ) && '' !== $date_formatted_two ) {
			$date_two = date_format( $date_formatted_two, 'Y-m-d h:i A' );
		}
		return strtotime( $date_one ) - strtotime( $date_two );
	}

	/**
	 * It will sort the descending data based on the abandoned cart date.
	 *
	 * @param array | object $value1 All data of the list.
	 * @param array | object $value2 All data of the list.
	 * @return timestamp
	 * @since 2.5.2
	 */
	public function wcal_class_order_date_dsc( $value1, $value2 ) {
		$date_two           = '';
		$date_one           = '';
		$value_one          = $value1->date;
		$value_two          = $value2->date;
		$date_formatted_one = date_create_from_format( 'd M, Y h:i A', $value_one );
		if ( isset( $date_formatted_one ) && '' !== $date_formatted_one ) {
			$date_one = date_format( $date_formatted_one, 'Y-m-d h:i A' );
		}

		$date_formatted_two = date_create_from_format( 'd M, Y h:i A', $value_two );
		if ( isset( $date_formatted_two ) && '' !== $date_formatted_two ) {
			$date_two = date_format( $date_formatted_two, 'Y-m-d h:i A' );
		}
		return strtotime( $date_two ) - strtotime( $date_one );
	}

	/**
	 * It will sort the alphabetally ascending on the abandoned cart staus.
	 *
	 * @param array | object $value1 All data of the list.
	 * @param array | object $value2 All data of the list.
	 * @return sorted array
	 * @since 2.5.2
	 */
	public function wcal_class_status_asc( $value1, $value2 ) {
		return strcasecmp( $value1->status, $value2->status );
	}

	/**
	 * It will sort the alphabetally descending on the abandoned cart staus.
	 *
	 * @param array | object $value1 All data of the list.
	 * @param array | object $value2 All data of the list.
	 * @return sorted array
	 * @since 2.5.2
	 */
	public function wcal_class_status_dsc( $value1, $value2 ) {
		return strcasecmp( $value2->status, $value1->status );
	}

	/**
	 * It will display the data for the abanodned column
	 *
	 * @param array | object $wcal_abandoned_orders All data of the list.
	 * @param stirng         $column_name Name of the column.
	 * @return string $value Data of the column
	 * @since 2.5.2
	 */
	public function column_default( $wcal_abandoned_orders, $column_name ) {
		$value = '';
		switch ( $column_name ) {
			case 'id':
				if ( isset( $wcal_abandoned_orders->id ) ) {

					$abandoned_order_id = $wcal_abandoned_orders->id;
					$wcal_array         = array(
						'action'  => 'wcal_abandoned_cart_info',
						'cart_id' => $abandoned_order_id,
					);
					$wcal_url           = add_query_arg(
						$wcal_array,
						admin_url( 'admin-ajax.php' )
					);

					$row_actions['edit'] = '' . __( 'View order', 'woocommerce-abandoned-cart' ) . '</a>';

					$value = '<strong><a oncontextmenu="return false;" class="wcal-button-icon wcal-js-open-modal" data-modal-type="ajax" data-wcal-cart-id="' . $abandoned_order_id . '" href="' . $wcal_url . '">' . $abandoned_order_id . '</a> </strong>';
				}
				break;
			case 'customer':
				if ( isset( $wcal_abandoned_orders->customer ) ) {
					$value = $wcal_abandoned_orders->customer;
				}
				break;
			case 'order_total':
				if ( isset( $wcal_abandoned_orders->order_total ) ) {
					$value = $wcal_abandoned_orders->order_total;
				}
				break;
			case 'date':
				if ( isset( $wcal_abandoned_orders->date ) ) {
					$value = $wcal_abandoned_orders->date;
				}
				break;
			case 'status':
				if ( isset( $wcal_abandoned_orders->status ) ) {
					$value = $wcal_abandoned_orders->status;
				}
				break;
			default:
				$value = isset( $wcal_abandoned_orders->$column_name ) ? $wcal_abandoned_orders->$column_name : '';
				break;
		}
		return apply_filters( 'wcal_abandoned_orders_column_default', $value, $wcal_abandoned_orders, $column_name );
	}

	/**
	 * Add bulk actions in the Abandoned Order tab.
	 *
	 * @return array
	 * @since 2.5.2
	 */
	public function get_bulk_actions() {
		return array(
			'wcal_delete'                => __( 'Delete', 'woocommerce-abandoned-cart' ),
			'wcal_delete_all_registered' => __( 'Delete All Registered User Carts', 'woocommerce-abandoned-cart' ),
			'wcal_delete_all_guest'      => __( 'Delete All Guest User Carts', 'woocommerce-abandoned-cart' ),
			'wcal_delete_all_visitor'    => __( 'Delete All Visitor Carts', 'woocommerce-abandoned-cart' ),
			'wcal_delete_all'            => __( 'Delete All Carts', 'woocommerce-abandoned-cart' ),
		);
	}

	/**
	 * It will give the section name.
	 *
	 * @return string $section Name of the current section
	 * @since 2.5.2
	 */
	public function wcal_get_current_section() {
		$section = 'wcal_all_abandoned';
		if ( isset( $_GET['wcal_section'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			$section = sanitize_text_field( wp_unslash( $_GET['wcal_section'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
		}
		return $section;
	}
}

