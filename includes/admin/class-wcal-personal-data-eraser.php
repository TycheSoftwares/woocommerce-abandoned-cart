<?php
/**
 * Export Abandoned Carts data in
 * Dashboard->Tools->Erase Personal Data
 *
 * @since 4.9
 * @package Abandoned-Cart-Lite-for-WooCommerce\Data-Eraser
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Wcal_Personal_Data_Eraser' ) ) {

	/**
	 * Export Abandoned Carts data in
	 * Dashboard->Tools->Erase Personal Data
	 */
	class Wcal_Personal_Data_Eraser {

		/**
		 * Construct.
		 *
		 * @since 4.9
		 */
		public function __construct() {
			// Hook into the WP erase process.
			add_filter( 'wp_privacy_personal_data_erasers', array( &$this, 'wcal_eraser_array' ), 6 );
		}

		/**
		 * Add our eraser and it's callback function
		 *
		 * @param array $erasers - Any erasers that need to be added by 3rd party plugins.
		 * @return array $erasers - Erasers list containing our plugin details.
		 *
		 * @since 4.9
		 */
		public static function wcal_eraser_array( $erasers = array() ) {

			$eraser_list = array();
			// Add our eraser and it's callback function.
			$eraser_list['wcal_carts'] = array(
				'eraser_friendly_name' => __( 'Abandoned & Recovered Carts', 'woocommerce-abandoned-cart' ),
				'callback'             => array( 'Wcal_Personal_Data_Eraser', 'wcal_data_eraser' ),
			);

			$erasers = array_merge( $erasers, $eraser_list );

			return $erasers;

		}

		/**
		 * Erases personal data for abandoned carts.
		 *
		 * @param string  $email_address - EMail Address for which personal data is being exported.
		 * @param integer $page - The Eraser page number.
		 * @return array $reponse - Whether the process was successful or no.
		 *
		 * @hook wp_privacy_personal_data_erasers
		 * @global $wpdb
		 * @since  4.9
		 */
		public static function wcal_data_eraser( $email_address, $page ) {
			global $wpdb;

			$page            = (int) $page;
			$user            = get_user_by( 'email', $email_address ); // Check if user has an ID in the DB to load stored personal data.
			$erasure_enabled = wc_string_to_bool( get_option( 'woocommerce_erasure_request_removes_order_data', 'no' ) );
			$response        = array(
				'items_removed'  => false,
				'items_retained' => false,
				'messages'       => array(),
				'done'           => true,
			);

			$user_id = $user ? (int) $user->ID : 0;

			if ( $user_id > 0 ) { // registered user.
				$cart_ids = $wpdb->get_results( // phpcs:ignore
					$wpdb->prepare(
						"SELECT id FROM `" . $wpdb->prefix . "ac_abandoned_cart_history_lite` WHERE user_id = %d AND user_type = 'REGISTERED'", // phpcs:ignore
						$user_id
					)
				);
			} else { // guest carts.
				$guest_user_ids = $wpdb->get_results( // phpcs:ignore
					$wpdb->prepare(
						'SELECT id FROM `' . $wpdb->prefix . 'ac_guest_abandoned_cart_history_lite` WHERE email_id = %s', // phpcs:ignore
						$email_address
					)
				);

				if ( 0 === count( $guest_user_ids ) ) {
					return array(
						'messages'       => array( __( 'No personal data found for any abandoned carts.', 'woocommerce-abandoned-cart' ) ),
						'items_removed'  => false,
						'items_retained' => true,
						'done'           => true,
					);
				}
				$cart_ids = array();

				foreach ( $guest_user_ids as $ids ) {
					// Get the cart data.
					$cart_data = $wpdb->get_results( // phpcs:ignore
						$wpdb->prepare(
							"SELECT id FROM `" . $wpdb->prefix . "ac_abandoned_cart_history_lite` WHERE user_id = %d AND user_type = 'GUEST'", // phpcs:ignore
							$ids->id
						)
					);

					$cart_ids = array_merge( $cart_ids, $cart_data );
				}
			}

			if ( 0 < count( $cart_ids ) ) {

				$cart_chunks = array_chunk( $cart_ids, 10, true );

				$cart_export = isset( $cart_chunks[ $page - 1 ] ) ? $cart_chunks[ $page - 1 ] : array();
				if ( count( $cart_export ) > 0 ) {
					foreach ( $cart_export as $abandoned_ids ) {
						$cart_id = $abandoned_ids->id;

						if ( apply_filters( 'wcal_privacy_erase_cart_personal_data', $erasure_enabled, $cart_id ) ) {
							self::remove_cart_personal_data( $cart_id );

							// Translators: %s Abandoned Cart ID.
							$response['messages'][]    = sprintf( __( 'Removed personal data from cart %s.', 'woocommerce-abandoned-cart' ), $cart_id );
							$response['items_removed'] = true;
						} else {
							// Translators: %s Abandoned Cart ID.
							$response['messages'][]     = sprintf( __( 'Personal data within cart %s has been retained.', 'woocommerce-abandoned-cart' ), $cart_id );
							$response['items_retained'] = true;
						}
					}
					$response['done'] = $page > count( $cart_chunks );
				} else {
					$response['done'] = true;
				}
			} else {
				$response['done'] = true;
			}
			return $response;
		}

		/**
		 * Erases the personal data for each abandoned cart.
		 *
		 * @param integer $abandoned_id - Abandoned Cart ID.
		 * @global $wpdb
		 * @since  4.9
		 */
		public static function remove_cart_personal_data( $abandoned_id ) {
			global $wpdb;

			$anonymized_cart  = array();
			$anonymized_guest = array();

			do_action( 'wcal_privacy_before_remove_cart_personal_data', $abandoned_id );

			// list the props we'll be anonymizing for cart history table.
			$props_to_remove_cart = apply_filters(
				'wcal_privacy_remove_cart_personal_data_props',
				array(
					'session_id' => 'numeric_id',
				),
				$abandoned_id
			);

			// list the props we'll be anonymizing for guest cart history table.
			$props_to_remove_guest = apply_filters(
				'wcal_privacy_remove_cart_personal_data_props_guest',
				array(
					'billing_first_name' => 'text',
					'billing_last_name'  => 'text',
					'phone'              => 'phone',
					'email_id'           => 'email',
				),
				$abandoned_id
			);

			if ( ! empty( $props_to_remove_cart ) && is_array( $props_to_remove_cart ) ) {

				// get the data from cart history.
				$cart_details = $wpdb->get_results( // phpcs:ignore
					$wpdb->prepare(
						'SELECT session_id, user_type, user_id FROM `' . $wpdb->prefix . 'ac_abandoned_cart_history_lite` WHERE id = %d',
						$abandoned_id
					)
				);

				if ( count( $cart_details ) > 0 ) {
					$cart_details = $cart_details[0];
				} else {
					return;
				}

				$user_id   = $cart_details->user_id;
				$user_type = $cart_details->user_type;

				foreach ( $props_to_remove_cart as $prop => $data_type ) {

					$value = $cart_details->$prop;

					if ( empty( $value ) || empty( $data_type ) ) {
						continue;
					}

					if ( function_exists( 'wp_privacy_anonymize_data' ) ) {
						$anon_value = wp_privacy_anonymize_data( $data_type, $value );
					} else {
						$anon_value = '';
					}

					$anonymized_cart[ $prop ] = apply_filters( 'wcal_privacy_remove_cart_personal_data_prop_value', $anon_value, $prop, $value, $data_type, $abandoned_id );
				}
				$anonymized_cart['user_type'] = __( 'ANONYMIZED', 'woocommerce-abandoned-cart' );
				// update the DB.
				$wpdb->update( // phpcs:ignore
					$wpdb->prefix . 'ac_abandoned_cart_history_lite',
					$anonymized_cart,
					array( 'id' => $abandoned_id )
				);
			}

			// check whether it's a guest user.
			if ( 'GUEST' === $user_type && ! empty( $props_to_remove_guest ) && is_array( $props_to_remove_guest ) ) {

				// get the data from guest cart history.
				$guest_details = $wpdb->get_results( // phpcs:ignore
					$wpdb->prepare(
						'SELECT billing_first_name, billing_last_name, phone, email_id FROM `' . $wpdb->prefix . 'ac_guest_abandoned_cart_history_lite` WHERE id = %d',
						$user_id
					)
				);

				if ( count( $guest_details ) > 0 ) {
					$guest_details = $guest_details[0];
				} else {
					return;
				}

				foreach ( $props_to_remove_guest as $prop => $data_type ) {
					$value = $guest_details->$prop;

					if ( empty( $value ) || empty( $data_type ) ) {
						continue;
					}

					if ( function_exists( 'wp_privacy_anonymize_data' ) ) {
						$anon_value = wp_privacy_anonymize_data( $data_type, $value );
					} else {
						$anon_value = '';
					}

					$anonymized_guest[ $prop ] = apply_filters( 'wcal_privacy_remove_cart_personal_data_prop_value_guest', $anon_value, $prop, $value, $data_type, $abandoned_id );
				}
				// update the DB.
				$wpdb->update( // phpcs:ignore
					$wpdb->prefix . 'ac_guest_abandoned_cart_history_lite',
					$anonymized_guest,
					array( 'id' => $user_id )
				);

			}

		}

	} // end of class
	$wcal_personal_data_eraser = new Wcal_Personal_Data_Eraser();
} // end if

