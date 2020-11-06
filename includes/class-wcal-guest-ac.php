<?php
/**
 * Abandoned Cart Lite for WooCommerce
 *
 * It will capture the guest users data.
 *
 * @author  Tyche Softwares
 * @package abandoned-cart-lite
 */

if ( ! class_exists( 'Wcal_Guest_Ac' ) ) {

	/**
	 * It will add the js, ajax for capturing the guest cart.
	 * It will add an action for populating the guest data when user comes from the abandoned cart reminder emails.
	 *
	 * @since 2.2
	 */
	class Wcal_Guest_Ac {

		/**
		 * Default Constructor function for guest tracking.
		 */
		public function __construct() {
			add_action( 'woocommerce_after_checkout_billing_form', 'user_side_js' );
			add_action( 'wfacp_footer_before_print_scripts', 'user_side_js' ); // Compatibility with Aero Checkout.
			add_action( 'init', 'load_ac_ajax' );
			add_action( 'wp_ajax_nopriv_wcal_gdpr_refused', array( 'wcal_common', 'wcal_gdpr_refused' ) );
			add_filter( 'woocommerce_checkout_fields', 'guest_checkout_fields' );
		}
	}

	/**
	 * It will add the ajax for capturing the guest record.
	 *
	 * @hook init
	 * @since 2.2
	 */
	function load_ac_ajax() {
		if ( ! is_user_logged_in() ) {
			add_action( 'wp_ajax_nopriv_save_data', 'save_data' );
		}
	}

	/**
	 * It will add the js for capturing the guest cart.
	 *
	 * @hook woocommerce_after_checkout_billing_form
	 * @since 2.2
	 */
	function user_side_js() {

		if ( ! is_user_logged_in() ) {
			wp_nonce_field( 'save_data', 'wcal_guest_capture_nonce' );

			wp_enqueue_script(
				'wcal_guest_capture',
				plugins_url( '../assets/js/wcal_guest_capture.min.js', __FILE__ ),
				'',
				WCAL_PLUGIN_VERSION,
				true
			);

			$guest_msg = get_option( 'wcal_guest_cart_capture_msg' );

			$session_gdpr = wcal_common::wcal_get_cart_session( 'wcal_cart_tracking_refused' );
			$show_gdpr    = isset( $session_gdpr ) && 'yes' == $session_gdpr ? false : true; // phpcs:ignore

			$vars = array();
			if ( isset( $guest_msg ) && '' !== $guest_msg ) {
				$vars = array(
					'_show_gdpr_message'        => $show_gdpr,
					'_gdpr_message'             => htmlspecialchars( get_option( 'wcal_guest_cart_capture_msg' ), ENT_QUOTES ),
					'_gdpr_nothanks_msg'        => htmlspecialchars( get_option( 'wcal_gdpr_allow_opt_out' ), ENT_QUOTES ),
					'_gdpr_after_no_thanks_msg' => htmlspecialchars( get_option( 'wcal_gdpr_opt_out_message' ), ENT_QUOTES ),
					'enable_ca_tracking'        => true,
				);
			}

			$vars['ajax_url'] = admin_url( 'admin-ajax.php' );

			wp_localize_script(
				'wcal_guest_capture',
				'wcal_guest_capture_params',
				$vars
			);
		}
	}

	/**
	 * It will add the guest users data in the database.
	 *
	 * @hook wp_ajax_nopriv_save_data
	 * @globals mixed $wpdb
	 * @globals mixed $woocommerce
	 * @since 2.2
	 */
	function save_data() {
		if ( ! is_user_logged_in() ) {

			if ( ! isset( $_POST['wcal_guest_capture_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['wcal_guest_capture_nonce'] ) ), 'save_data' ) ) {
				die();
			}

			global $wpdb, $woocommerce;
			if ( isset( $_POST['billing_first_name'] ) && '' !== $_POST['billing_first_name'] ) {
				wcal_common::wcal_set_cart_session( 'billing_first_name', sanitize_text_field( wp_unslash( $_POST['billing_first_name'] ) ) );
			}
			if ( isset( $_POST['billing_last_name'] ) && '' !== $_POST['billing_last_name'] ) {
				wcal_common::wcal_set_cart_session( 'billing_last_name', sanitize_text_field( wp_unslash( $_POST['billing_last_name'] ) ) );
			}
			if ( isset( $_POST['billing_company'] ) && '' !== $_POST['billing_company'] ) {
				wcal_common::wcal_set_cart_session( 'billing_company', sanitize_text_field( wp_unslash( $_POST['billing_company'] ) ) );
			}
			if ( isset( $_POST['billing_address_1'] ) && '' !== $_POST['billing_address_1'] ) {
				wcal_common::wcal_set_cart_session( 'billing_address_1', sanitize_text_field( wp_unslash( $_POST['billing_address_1'] ) ) );
			}
			if ( isset( $_POST['billing_address_2'] ) && '' !== $_POST['billing_address_2'] ) {
				wcal_common::wcal_set_cart_session( 'billing_address_2', sanitize_text_field( wp_unslash( $_POST['billing_address_2'] ) ) );
			}
			if ( isset( $_POST['billing_city'] ) && '' !== $_POST['billing_city'] ) {
				wcal_common::wcal_set_cart_session( 'billing_city', sanitize_text_field( wp_unslash( $_POST['billing_city'] ) ) );
			}
			if ( isset( $_POST['billing_state'] ) && '' !== $_POST['billing_state'] ) {
				wcal_common::wcal_set_cart_session( 'billing_state', sanitize_text_field( wp_unslash( $_POST['billing_state'] ) ) );
			}
			if ( isset( $_POST['billing_postcode'] ) && '' !== $_POST['billing_postcode'] ) {
				wcal_common::wcal_set_cart_session( 'billing_postcode', sanitize_text_field( wp_unslash( $_POST['billing_postcode'] ) ) );
			}
			if ( isset( $_POST['billing_country'] ) && '' !== $_POST['billing_country'] ) {
				wcal_common::wcal_set_cart_session( 'billing_country', sanitize_text_field( wp_unslash( $_POST['billing_country'] ) ) );
			}
			if ( isset( $_POST['billing_email'] ) && '' !== $_POST['billing_email'] ) {
				wcal_common::wcal_set_cart_session( 'billing_email', sanitize_text_field( wp_unslash( $_POST['billing_email'] ) ) );
			}
			if ( isset( $_POST['billing_phone'] ) && '' !== $_POST['billing_phone'] ) {
				wcal_common::wcal_set_cart_session( 'billing_phone', sanitize_text_field( wp_unslash( $_POST['billing_phone'] ) ) );
			}
			if ( isset( $_POST['order_notes'] ) && '' !== $_POST['order_notes'] ) {
				wcal_common::wcal_set_cart_session( 'order_notes', sanitize_text_field( wp_unslash( $_POST['order_notes'] ) ) );
			}
			if ( isset( $_POST['ship_to_billing'] ) && '' !== $_POST['ship_to_billing'] ) {
				wcal_common::wcal_set_cart_session( 'ship_to_billing', sanitize_text_field( wp_unslash( $_POST['ship_to_billing'] ) ) );
			}
			if ( isset( $_POST['shipping_first_name'] ) && '' !== $_POST['shipping_first_name'] ) {
				wcal_common::wcal_set_cart_session( 'shipping_first_name', sanitize_text_field( wp_unslash( $_POST['shipping_first_name'] ) ) );
			}
			if ( isset( $_POST['shipping_last_name'] ) && '' !== $_POST['shipping_last_name'] ) {
				wcal_common::wcal_set_cart_session( 'shipping_last_name', sanitize_text_field( wp_unslash( $_POST['shipping_last_name'] ) ) );
			}
			if ( isset( $_POST['shipping_company'] ) && '' !== $_POST['shipping_company'] ) {
				wcal_common::wcal_set_cart_session( 'shipping_company', sanitize_text_field( wp_unslash( $_POST['shipping_company'] ) ) );
			}
			if ( isset( $_POST['shipping_address_1'] ) && '' !== $_POST['shipping_address_1'] ) {
				wcal_common::wcal_set_cart_session( 'shipping_address_1', sanitize_text_field( wp_unslash( $_POST['shipping_address_1'] ) ) );
			}
			if ( isset( $_POST['shipping_address_2'] ) && '' !== $_POST['shipping_address_2'] ) {
				wcal_common::wcal_set_cart_session( 'shipping_address_2', sanitize_text_field( wp_unslash( $_POST['shipping_address_2'] ) ) );
			}
			if ( isset( $_POST['shipping_city'] ) && '' !== $_POST['shipping_city'] ) {
				wcal_common::wcal_set_cart_session( 'shipping_city', sanitize_text_field( wp_unslash( $_POST['shipping_city'] ) ) );
			}
			if ( isset( $_POST['shipping_state'] ) && '' !== $_POST['shipping_state'] ) {
				wcal_common::wcal_set_cart_session( 'shipping_state', sanitize_text_field( wp_unslash( $_POST['shipping_state'] ) ) );
			}
			if ( isset( $_POST['shipping_postcode'] ) && '' !== $_POST['shipping_postcode'] ) {
				wcal_common::wcal_set_cart_session( 'shipping_postcode', sanitize_text_field( wp_unslash( $_POST['shipping_postcode'] ) ) );
			}
			if ( isset( $_POST['shipping_country'] ) && '' !== $_POST['shipping_country'] ) {
				wcal_common::wcal_set_cart_session( 'shipping_country', sanitize_text_field( wp_unslash( $_POST['shipping_country'] ) ) );
			}
			// If a record is present in the guest cart history table for the same email id, then delete the previous records.
			$results_guest = $wpdb->get_results( // phpcs:ignore
				$wpdb->prepare(
					'SELECT id FROM `' . $wpdb->prefix . 'ac_guest_abandoned_cart_history_lite` WHERE email_id = %s',
					wcal_common::wcal_get_cart_session( 'billing_email' )
				)
			);

			if ( $results_guest ) {
				foreach ( $results_guest as $key => $value ) {
					// phpcs:ignore WordPress.DB.DirectDatabaseQuery
					$result = $wpdb->get_results(
						$wpdb->prepare(
							'SELECT * FROM `' . $wpdb->prefix . 'ac_abandoned_cart_history_lite` WHERE  user_id = %d AND recovered_cart = %s',
							$value->id,
							0
						)
					);
					// update existing record and create new record if guest cart history table will have the same email id.

					if ( count( $result ) ) {
						$wpdb->query( // phpcs:ignore
							$wpdb->prepare(
								'UPDATE `' . $wpdb->prefix . 'ac_abandoned_cart_history_lite` SET cart_ignored = %s WHERE user_id = %s',
								1,
								$value->id
							)
						);
					}
				}
			}
			// Insert record in guest table.
			$billing_first_name = wcal_common::wcal_get_cart_session( 'billing_first_name' );

			$billing_last_name = wcal_common::wcal_get_cart_session( 'billing_last_name' );

			$shipping_zipcode = '';
			$billing_zipcode  = '';

			if ( '' != wcal_common::wcal_get_cart_session( 'shipping_postcode' ) ) { // phpcs:ignore
				$shipping_zipcode = wcal_common::wcal_get_cart_session( 'shipping_postcode' );
			} elseif ( '' != wcal_common::wcal_get_cart_session( 'billing_postcode' ) ) { // phpcs:ignore
				$billing_zipcode  = wcal_common::wcal_get_cart_session( 'billing_postcode' );
				$shipping_zipcode = $billing_zipcode;
			}
			$shipping_charges = $woocommerce->cart->shipping_total;
			$wpdb->query( // phpcs:ignore
				$wpdb->prepare(
					'INSERT INTO `' . $wpdb->prefix . 'ac_guest_abandoned_cart_history_lite`( billing_first_name, billing_last_name, email_id, billing_zipcode, shipping_zipcode, shipping_charges ) VALUES ( %s, %s, %s, %s, %s, %s )',
					$billing_first_name,
					$billing_last_name,
					wcal_common::wcal_get_cart_session( 'billing_email' ),
					$billing_zipcode,
					$shipping_zipcode,
					$shipping_charges
				)
			);

			// Insert record in abandoned cart table for the guest user.
			$user_id = $wpdb->insert_id;
			wcal_common::wcal_set_cart_session( 'user_id', $user_id );
			$current_time      = current_time( 'timestamp' ); // phpcs:ignore
			$cut_off_time      = get_option( 'ac_cart_abandoned_time' );
			$cart_cut_off_time = $cut_off_time * 60;
			$compare_time      = $current_time - $cart_cut_off_time;

			$results = $wpdb->get_results( // phpcs:ignore
				$wpdb->prepare(
					'SELECT * FROM `' . $wpdb->prefix . 'ac_abandoned_cart_history_lite` WHERE user_id = %d AND cart_ignored = %s AND recovered_cart = %s AND user_type = %s',
					$user_id,
					0,
					0,
					'GUEST'
				)
			);

			$cart = array();

			if ( function_exists( 'WC' ) ) {
				$cart['cart'] = WC()->session->cart;
			} else {
				$cart['cart'] = $woocommerce->session->cart;
			}

			if ( 0 === count( $results ) ) {
				$get_cookie = WC()->session->get_session_cookie();

				$cart_info = wp_json_encode( $cart );
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery
				$results = $wpdb->get_results(
					$wpdb->prepare(
						'SELECT * FROM `' . $wpdb->prefix . 'ac_abandoned_cart_history_lite` WHERE session_id LIKE %s AND cart_ignored = %s AND recovered_cart = %s',
						$get_cookie[0],
						0,
						0
					)
				);
				if ( 0 === count( $results ) ) {
					// phpcs:ignore WordPress.DB.DirectDatabaseQuery
					$wpdb->query(
						$wpdb->prepare(
							'INSERT INTO `' . $wpdb->prefix . 'ac_abandoned_cart_history_lite`( user_id, abandoned_cart_info, abandoned_cart_time, cart_ignored, recovered_cart, user_type, session_id ) VALUES ( %s, %s, %s, %s, %s, %s, %s )',
							$user_id,
							$cart_info,
							$current_time,
							0,
							0,
							'GUEST',
							$get_cookie[0]
						)
					);

					$abandoned_cart_id = $wpdb->insert_id;
					wcal_common::wcal_set_cart_session( 'abandoned_cart_id_lite', $abandoned_cart_id );

					if ( is_multisite() ) {
						// get main site's table prefix.
						$main_prefix = $wpdb->get_blog_prefix( 1 );
						// phpcs:ignore WordPress.DB.DirectDatabaseQuery
						$wpdb->query(
							$wpdb->prepare(
								'INSERT INTO `' . $main_prefix . 'usermeta`( user_id, meta_key, meta_value ) VALUES ( %s, %s, %s )', // phpcs:ignore
								$user_id,
								'_woocommerce_persistent_cart',
								$cart_info
							)
						);

					} else {
						$wpdb->query( // phpcs:ignore
							$wpdb->prepare(
								'INSERT INTO `' . $wpdb->prefix . 'usermeta`( user_id, meta_key, meta_value ) VALUES ( %s, %s, %s )',
								$user_id,
								'_woocommerce_persistent_cart',
								$cart_info
							)
						);
					}
				} else {
					$wpdb->query( // phpcS:ignore
						$wpdb->prepare(
							'UPDATE `' . $wpdb->prefix . 'ac_abandoned_cart_history_lite` SET user_id = %s, abandoned_cart_info = %s, abandoned_cart_time = %s WHERE session_id = %s AND cart_ignored = %s',
							$user_id,
							$cart_info,
							$current_time,
							$get_cookie[0],
							0
						)
					);
					$get_abandoned_record = $wpdb->get_results( // phpcS:ignore
						$wpdb->prepare(
							'SELECT * FROM `' . $wpdb->prefix . 'ac_abandoned_cart_history_lite` WHERE user_id = %d AND cart_ignored = %s AND session_id = %s',
							$user_id,
							0,
							$get_cookie[0]
						)
					);

					if ( count( $get_abandoned_record ) > 0 ) {
						$abandoned_cart_id = $get_abandoned_record[0]->id;
						wcal_common::wcal_set_cart_session( 'abandoned_cart_id_lite', $abandoned_cart_id );
					}

					$wpdb->query( // phpcs:ignore
						$wpdb->prepare(
							'INSERT INTO `' . $wpdb->prefix . 'usermeta`( user_id, meta_key, meta_value ) VALUES ( %s, %s, %s )',
							$user_id,
							'_woocommerce_persistent_cart',
							$cart_info
						)
					);
					if ( is_multisite() ) {
						// get main site's table prefix.
						$main_prefix = $wpdb->get_blog_prefix( 1 );
						// phpcs:ignore WordPress.DB.DirectDatabaseQuery
						$wpdb->query(
							$wpdb->prepare(
								'INSERT INTO `' . $main_prefix . 'usermeta`( user_id, meta_key, meta_value ) VALUES ( %s, %s, %s )', // phpcs:ignore
								$user_id,
								'_woocommerce_persistent_cart',
								$cart_info
							)
						);

					} else {
						$wpdb->query( // phpcs:ignore
							$wpdb->prepare(
								'INSERT INTO `' . $wpdb->prefix . 'usermeta`( user_id, meta_key, meta_value ) VALUES ( %s, %s, %s )',
								$user_id,
								'_woocommerce_persistent_cart',
								$cart_info
							)
						);
					}
				}
			}
		}
	}

	/**
	 * It will populate the data on the chekout field if user comes from the abandoned cart reminder emails.
	 *
	 * @hook woocommerce_checkout_fields
	 * @param array $fields All fields of checkout page.
	 * @return array $fields
	 * @since 2.2
	 */
	function guest_checkout_fields( $fields ) {
		if ( '' != wcal_common::wcal_get_cart_session( 'guest_first_name' ) ) { // phpcs:ignore
			$_POST['billing_first_name'] = wcal_common::wcal_get_cart_session( 'guest_first_name' );
		}
		if ( '' != wcal_common::wcal_get_cart_session( 'guest_last_name' ) ) { // phpcs:ignore
			$_POST['billing_last_name'] = wcal_common::wcal_get_cart_session( 'guest_last_name' );
		}
		if ( '' != wcal_common::wcal_get_cart_session( 'guest_email' ) ) { // phpcs:ignore
			$_POST['billing_email'] = wcal_common::wcal_get_cart_session( 'guest_email' );
		}
		if ( '' != wcal_common::wcal_get_cart_session( 'guest_phone' ) ) { // phpcs:ignore
			$_POST['billing_phone'] = wcal_common::wcal_get_cart_session( 'guest_phone' );
		}
		return $fields;
	}
}
$woocommerce_guest_ac = new Wcal_Guest_Ac();
