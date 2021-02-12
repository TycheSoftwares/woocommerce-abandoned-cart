<?php
/**
 * Checkout Process for Abandoned Cart Lite
 *
 * @since 5.3.0
 * @package Abandoned-Cart-Lite-for-WooCommerce/Checkout Process
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Wcal_Checkout_Process' ) ) {

	/**
	 * Process recovered orders.
	 */
	class Wcal_Checkout_Process {

		/**
		 * Constructor.
		 */
		public function __construct() {

			// Delete added temp fields after order is placed.
			add_filter( 'woocommerce_order_details_after_order_table', array( &$this, 'wcal_action_after_delivery_session' ) );

			add_action( 'woocommerce_order_status_changed', array( &$this, 'wcal_update_cart_details' ), 10, 3 );
			add_action( 'woocommerce_order_status_changed', array( &$this, 'wcal_send_recovery_email' ), 10, 3 );

			add_action( 'woocommerce_checkout_order_processed', array( &$this, 'wcal_order_placed' ), 10, 1 );
			add_filter( 'woocommerce_payment_complete_order_status', array( &$this, 'wcal_order_complete_action' ), 10, 2 );
		}

		/**
		 * When user places the order and reach the order recieved page, then it will check if it is abandoned cart and subsequently
		 * recovered or not.
		 *
		 * @hook woocommerce_order_details_after_order_table
		 * @param array | object $order Order details.
		 * @globals mixed $wpdb
		 * @globals mixed $woocommerce
		 * @since 1.0
		 */
		public function wcal_action_after_delivery_session( $order ) {

			$order_id = $order->get_id();

			$wcal_get_order_status = $order->get_status();

			$get_abandoned_id_of_order  = get_post_meta( $order_id, 'wcal_recover_order_placed', true );
			$get_sent_email_id_of_order = get_post_meta( $order_id, 'wcal_recover_order_placed_sent_id', true );

			if ( isset( $get_sent_email_id_of_order ) && '' !== $get_sent_email_id_of_order ) {

				// When Placed order button is clicked, we create post meta for that order, If that meta is found then update our plugin table for recovered cart.
				$this->wcal_updated_recovered_cart_table( $get_abandoned_id_of_order, $order_id, $get_sent_email_id_of_order, $order );
			} elseif ( '' !== $get_abandoned_id_of_order && isset( $get_abandoned_id_of_order ) ) {

				// If order status is not pending or failed then, we  will delete the abandoned cart record. Post meta will be created only if the cut off time has been reached.
				$this->wcal_delete_abanadoned_data_on_order_status( $order_id, $get_abandoned_id_of_order, $wcal_get_order_status );
			}

			if ( '' != wcal_common::wcal_get_cart_session( 'email_sent_id' ) ) { // phpcs:ignore
				wcal_common::wcal_unset_cart_session( 'email_sent_id' );
			}
		}

		/**
		 * If customer had placed the order after cut off time and reached the order recived page then it will also delete the abandoned cart if the order status is not pending or failed.
		 *
		 * @param int | string $order_id Order id.
		 * @param int | string $get_abandoned_id_of_order Abandoned cart id.
		 * @param string       $wcal_get_order_status Order status.
		 * @globals mixed $wpdb
		 * @globals mixed $woocommerce
		 * @since 5.0
		 */
		public function wcal_delete_abanadoned_data_on_order_status( $order_id, $get_abandoned_id_of_order, $wcal_get_order_status ) {

			global $wpdb, $woocommerce;

			$wcal_history_table_name    = $wpdb->prefix . 'ac_abandoned_cart_history_lite';
			$wcal_guest_table_name      = $wpdb->prefix . 'ac_guest_abandoned_cart_history_lite';
			$wcal_sent_email_table_name = $wpdb->prefix . 'ac_sent_history_lite';

			if ( 'pending' !== $wcal_get_order_status || 'failed' !== $wcal_get_order_status ) {
				if ( isset( $get_abandoned_id_of_order ) && '' !== $get_abandoned_id_of_order ) {
					// phpcs:ignore WordPress.DB.DirectDatabaseQuery
					$user_id_results = $wpdb->get_results(
						$wpdb->prepare(
							'SELECT user_id FROM `' . $wcal_history_table_name . '` WHERE id = %d', // phpcs:ignore
							$get_abandoned_id_of_order
						)
					);

					if ( count( $user_id_results ) > 0 ) {
						$wcal_user_id = $user_id_results[0]->user_id;

						if ( $wcal_user_id >= 63000000 ) {
							$wpdb->delete( // phpcs:ignore
								$wcal_guest_table_name,
								array( 'id' => $wcal_user_id )
							);
						}

						$wpdb->delete( // phpcs:ignore
							$wcal_history_table_name,
							array( 'id' => $get_abandoned_id_of_order )
						);
						delete_post_meta( $order_id, 'wcal_recover_order_placed', $get_abandoned_id_of_order );
					}
				}
			}
		}

		/**
		 * Updates the Abandoned Cart History table as well as the
		 * Email Sent History table to indicate the order has been
		 * recovered
		 *
		 * @param integer  $cart_id - ID of the Abandoned Cart.
		 * @param integer  $order_id - Recovered Order ID.
		 * @param integer  $wcal_check_email_sent_to_cart - ID of the record in the Email Sent History table.
		 * @param WC_Order $order - Order Details.
		 *
		 * @since 7.7
		 */
		public function wcal_updated_recovered_cart_table( $cart_id, $order_id, $wcal_check_email_sent_to_cart, $order ) {

			global $wpdb;

			$wcal_history_table_name    = $wpdb->prefix . 'ac_abandoned_cart_history_lite';
			$wcal_guest_table_name      = $wpdb->prefix . 'ac_guest_abandoned_cart_history_lite';
			$wcal_sent_email_table_name = $wpdb->prefix . 'ac_sent_history_lite';

			// Check & make sure that the recovered cart details are not already updated.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$get_status = $wpdb->get_col(
				$wpdb->prepare(
					'SELECT recovered_cart FROM `' . $wcal_history_table_name . '` WHERE id = %d', // phpcs:ignore
					$cart_id
				)
			);

			$recovered_status = isset( $get_status[0] ) ? $get_status[0] : '';

			if ( 0 == $recovered_status ) { // phpcs:ignore

				// Update the cart history table.
				$update_details = array(
					'recovered_cart' => $order_id,
					'cart_ignored'   => '1',
				);

				$current_user_id = get_current_user_id();

				if ( wcal_common::wcal_get_cart_session( 'user_id' ) != $current_user_id && 0 != $current_user_id ) { // phpcs:ignore
					$update_details['user_id'] = $current_user_id;
				}

				// check if more than one reminder email has been sent.
				$get_old_cart_id = $wpdb->get_col( // phpcs:ignore
					$wpdb->prepare(
						'SELECT abandoned_order_id FROM `$wcal_sent_email_table_name` WHERE id = %d',
						$wcal_check_email_sent_to_cart
					)
				);

				$get_ids = array();
				if ( isset( $get_old_cart_id ) ) {
					// phpcs:ignore WordPress.DB.DirectDatabaseQuery
					$get_ids = $wpdb->get_col(
						$wpdb->prepare(
							'SELECT id FROM `' . $wcal_sent_email_table_name . '` WHERE abandoned_order_id = %d', // phpcs:ignore
							$get_old_cart_id
						)
					);
				}

				$update_sent_history = array();

				if ( '' !== get_post_meta( $order_id, 'wcal_abandoned_timestamp', true ) ) {
					$update_details['abandoned_cart_time'] = get_post_meta( $order_id, 'wcal_abandoned_timestamp', true );

					$update_sent_history['abandoned_order_id'] = $cart_id;

					delete_post_meta( $order_id, 'wcal_abandoned_timestamp', $update_details['abandoned_cart_time'] );
				}
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery
				$wpdb->update(
					$wcal_history_table_name,
					$update_details,
					array(
						'id' => $cart_id,
					)
				);

				// update the email sent history table.
				if ( is_array( $get_ids ) && count( $get_ids ) > 1 ) {
					$list_ids = implode( ',', $get_ids );
					// phpcs:ignore WordPress.DB.DirectDatabaseQuery
					$wpdb->query(
						'UPDATE `' . $wcal_sent_email_table_name . '` SET abandoned_order_id = %d WHERE id IN (%s)', // phpcs:ignore
						(int) $cart_id,
						$list_ids
					);
				} elseif ( isset( $update_sent_history['abandoned_order_id'] ) ) {
					$wpdb->update( // phpcs:ignore
						$wcal_sent_email_table_name,
						$update_sent_history,
						array(
							'id' => $wcal_check_email_sent_to_cart,
						)
					);
				}

				// Add Order Note.
				$order->add_order_note( __( 'This order was abandoned & subsequently recovered.', 'woocommerce-abandoned-cart' ) );
				delete_post_meta( $order_id, 'wcal_abandoned_cart_id' );
				delete_post_meta( $order_id, 'wcal_recover_order_placed' );
				delete_post_meta( $order_id, 'wcal_recover_order_placed_sent_id' );
				delete_post_meta( $order_id, 'wcal_recovered_email_sent' );
			}
		}

		/**
		 * Send email to admin when cart is recovered only via PayPal.
		 *
		 * @hook woocommerce_order_status_changed
		 * @param int | string $order_id Order id.
		 * @param string       $wc_old_status Old status.
		 * @param string       $wc_new_status New status.
		 * @globals mixed $wpdb
		 * @globals mixed $woocommerce
		 * @since 2.9
		 */
		public function wcal_update_cart_details( $order_id, $wc_old_status, $wc_new_status ) {

			if ( 'pending' !== $wc_new_status && 'failed' !== $wc_new_status && 'cancelled' !== $wc_new_status && 'trash' !== $wc_new_status ) {

				global $wpdb;

				$wcal_history_table_name    = $wpdb->prefix . 'ac_abandoned_cart_history_lite';
				$wcal_guest_table_name      = $wpdb->prefix . 'ac_guest_abandoned_cart_history_lite';
				$wcal_sent_email_table_name = $wpdb->prefix . 'ac_sent_history_lite';

				if ( $order_id > 0 ) {
					$get_abandoned_id_of_order = get_post_meta( $order_id, 'wcal_recover_order_placed', true );

					if ( $get_abandoned_id_of_order > 0 || '' != wcal_common::wcal_get_cart_session( 'email_sent_id' ) ) { // phpcs:ignore
						// recovered order.
					} else {

						$wcal_abandoned_id = get_post_meta( $order_id, 'wcal_abandoned_cart_id', true );
						// check if it's a guest cart.
						// phpcs:ignore WordPress.DB.DirectDatabaseQuery
						$get_cart_data = $wpdb->get_results(
							$wpdb->prepare(
								'SELECT user_id, user_type FROM `' . $wcal_history_table_name . '` WHERE id = %d', // phpcs:ignore
								$wcal_abandoned_id
							)
						);

						if ( is_array( $get_cart_data ) && count( $get_cart_data ) > 0 ) {
							$user_type = $get_cart_data[0]->user_type;
							$user_id   = $get_cart_data[0]->user_id;

							if ( 'GUEST' === $user_type && $user_id >= 63000000 ) {
								$wpdb->delete( // phpcs:ignore
									$wcal_guest_table_name,
									array( 'id' => $user_id )
								);
							}
						}
						$wpdb->delete( $wcal_history_table_name, array( 'id' => $wcal_abandoned_id ) ); // phpcs:ignore
					}
				}
			} elseif ( 'pending' === $wc_old_status && 'cancelled' === $wc_new_status ) {
				global $wpdb;

				$wcal_history_table_name = $wpdb->prefix . 'ac_abandoned_cart_history_lite';
				$wcal_abandoned_id       = get_post_meta( $order_id, 'wcal_abandoned_cart_id', true );

				$wpdb->update(  // phpcs:ignore
					$wcal_history_table_name,
					array( 'cart_ignored' => '1' ),
					array( 'id' => $wcal_abandoned_id )
				);
			}
		}

		/**
		 * This function will send the email to the store admin when any abandoned cart email recovered.
		 *
		 * @hook woocommerce_order_status_changed
		 * @param int | string $order_id Order id.
		 * @param string       $wcap_old_status Old status of the order.
		 * @param string       $wcap_new_status New status of the order.
		 * @globals mixed $woocommerce
		 * @since 1.0
		 */
		public function wcal_send_recovery_email( $order_id, $wcap_old_status, $wcap_new_status ) {
			global $woocommerce;

			if ( ( 'pending' === $wcap_old_status && 'processing' === $wcap_new_status )
				|| ( 'pending' === $wcap_old_status && 'completed' === $wcap_new_status )
				|| ( 'pending' === $wcap_old_status && 'on-hold' === $wcap_new_status )
				|| ( 'failed' === $wcap_old_status && 'completed' === $wcap_new_status )
				|| ( 'failed' === $wcap_old_status && 'processing' === $wcap_new_status )
			) {
				$user_id                 = get_current_user_id();
				$ac_email_admin_recovery = get_option( 'ac_lite_email_admin_on_recovery' );
				$order                   = wc_get_order( $order_id );
				if ( version_compare( $woocommerce->version, '3.0.0', '>=' ) ) {
					$user_id = $order->get_user_id();
				} else {
					$user_id = $order->user_id;
				}
				if ( 'on' === $ac_email_admin_recovery ) {
					$recovered_email_sent          = get_post_meta( $order_id, 'wcal_recovered_email_sent', true );
					$wcal_check_order_is_recovered = $this->wcal_check_order_is_recovered( $order_id );

					if ( 'yes' !== $recovered_email_sent && true === $wcal_check_order_is_recovered ) { // indicates cart is abandoned.
						$order         = wc_get_order( $order_id );
						$email_heading = __( 'New Customer Order - Recovered', 'woocommerce' );
						$blogname      = wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES );
						$email_subject = __( 'New Customer Order - Recovered', 'woocommerce' );
						$user_email    = get_option( 'admin_email' );
						$headers[]     = 'From: Admin <' . $user_email . '>';
						$headers[]     = 'Content-Type: text/html';
						$user_email    = apply_filters( 'wcal_send_recovery_email_to', $user_email );
						// Buffer.
						ob_start();
						// Get mail template.
						wc_get_template(
							'emails/admin-new-order.php',
							array(
								'order'         => $order,
								'email_heading' => $email_heading,
								'sent_to_admin' => false,
								'plain_text'    => false,
								'email'         => true,
							)
						);
						// Get contents.
						$email_body = ob_get_clean();
						wc_mail( $user_email, $email_subject, $email_body, $headers );

						update_post_meta( $order_id, 'wcal_recovered_email_sent', 'yes' );
					}
				}
			}
		}

		/**
		 * For sending Recovery Email to Admin, we will check that order is recovered or not.
		 *
		 * @param int | string $wcal_order_id Order id.
		 * @return boolean true | false
		 * @globals mixed $wpdb
		 * @since 2.3
		 */
		public function wcal_check_order_is_recovered( $wcal_order_id ) {
			global $wpdb;

			$wcal_recover_order_query_result = $wpdb->get_results( // phpcs:ignore
				$wpdb->prepare(
					'SELECT recovered_cart FROM `' . $wpdb->prefix . 'ac_abandoned_cart_history_lite` WHERE recovered_cart = %d', // phpcs:ignore
					$wcal_order_id
				)
			);
			if ( count( $wcal_recover_order_query_result ) > 0 ) {
				return true;
			}
			return false;
		}

		/**
		 * It will check the WooCommerce order status. If the order status is pending or failed the we will keep that cart record
		 * as an abandoned cart.
		 * It will be executed after order placed.
		 *
		 * @hook woocommerce_payment_complete_order_status
		 * @param string       $woo_order_status Order Status.
		 * @param int | string $order_id Order Id.
		 * @return string $order_status
		 * @globals mixed $wpdb
		 * @since 3.4
		 */
		public function wcal_order_complete_action( $woo_order_status, $order_id ) {

			global $wpdb;

			if ( $order_id > 0 ) {
				$order = wc_get_order( $order_id );

				$get_abandoned_id_of_order  = get_post_meta( $order_id, 'wcal_recover_order_placed', true );
				$get_sent_email_id_of_order = get_post_meta( $order_id, 'wcal_recover_order_placed_sent_id', true );

				// Order Status passed in the function is either 'processing' or 'complete' and may or may not reflect the actual order status.
				// Hence, always use the status fetched from the order object.
				$order_status = ( $order ) ? $order->get_status() : '';

				$wcal_ac_table_name                 = $wpdb->prefix . 'ac_abandoned_cart_history_lite';
				$wcal_email_sent_history_table_name = $wpdb->prefix . 'ac_sent_history_lite';
				$wcal_guest_ac_table_name           = $wpdb->prefix . 'ac_guest_abandoned_cart_history_lite';

				if ( 'pending' !== $order_status && 'failed' !== $order_status && 'cancelled' !== $order_status && 'trash' !== $order_status ) {
					global $wpdb;

					if ( isset( $get_abandoned_id_of_order ) && '' !== $get_abandoned_id_of_order ) {

						$ac_user_id_result = $wpdb->get_results( // phpcs:ignore
							$wpdb->prepare(
								'SELECT user_id, abandoned_cart_time FROM `' . $wcal_ac_table_name . '` WHERE id = %d', // phpcs:ignore
								$get_abandoned_id_of_order
							)
						);

						if ( count( $ac_user_id_result ) > 0 ) {
							$wcal_user_id = $ac_user_id_result[0]->user_id;

							if ( $wcal_user_id >= 63000000 ) {
								add_post_meta( $order_id, 'wcal_abandoned_timestamp', $ac_user_id_result[0]->abandoned_cart_time );

								$wpdb->delete( // phpcs:ignore
									$wcal_guest_ac_table_name,
									array( 'id' => $wcal_user_id )
								);
							}

							$wpdb->delete( // phpcs:ignore
								$wcal_ac_table_name,
								array( 'id' => $get_abandoned_id_of_order )
							);
							delete_post_meta( $order_id, 'wcal_recover_order_placed', $get_abandoned_id_of_order );
						}
					}
				}

				if ( 'pending' !== $woo_order_status && 'failed' !== $woo_order_status && 'cancelled' !== $woo_order_status && 'trash' !== $woo_order_status ) {

					if ( isset( $get_sent_email_id_of_order ) && '' !== $get_sent_email_id_of_order ) {
						$this->wcal_updated_recovered_cart( $get_abandoned_id_of_order, $order_id, $get_sent_email_id_of_order, $order );
					}
				}
			}

			return $woo_order_status;
		}

		/**
		 * Updates the Abandoned Cart History table as well as the
		 * Email Sent History table to indicate the order has been
		 * recovered
		 *
		 * @param integer  $cart_id - ID of the Abandoned Cart.
		 * @param integer  $order_id - Recovered Order ID.
		 * @param integer  $wcal_check_email_sent_to_cart - ID of the record in the Email Sent History table.
		 * @param WC_Order $order - Order Details.
		 *
		 * @since 5.3.0
		 */
		public function wcal_updated_recovered_cart( $cart_id, $order_id, $wcal_check_email_sent_to_cart, $order ) {

			global $wpdb;

			$wcal_ac_table_name    = $wpdb->prefix . 'ac_abandoned_cart_history_lite';
			$wcal_email_sent_table = $wpdb->prefix . 'ac_sent_history_lite';
			$wcal_guest_ac_table   = $wpdb->prefix . 'ac_guest_abandoned_cart_history_lite';

			// check & make sure that the recovered cart details are not already updated.
			$get_status = $wpdb->get_col( // phpcs:ignore
				$wpdb->prepare(
					'SELECT recovered_cart FROM `' . $wcal_ac_table_name . '` WHERE id = %d', // phpcs:ignore
					$cart_id
				)
			);

			$recovered_status = isset( $get_status[0] ) ? $get_status[0] : '';

			if ( 0 == $recovered_status ) { // phpcs:ignore
				// Update the cart history table.
				$update_details = array(
					'recovered_cart' => $order_id,
					'cart_ignored'   => '1',
				);

				$current_user_id = get_current_user_id();

				if ( wcal_common::wcal_get_cart_session( 'user_id' ) != $current_user_id && 0 != $current_user_id ) { // phpcs:ignore
					$update_details['user_id'] = $current_user_id;
				}

				// check if more than one reminder email has been sent.
				$get_old_cart_id = $wpdb->get_col( // phpcs:ignore
					$wpdb->prepare(
						'SELECT abandoned_order_id FROM `' . $wcal_email_sent_table . '` WHERE id = %d', // phpcs:ignore
						$wcal_check_email_sent_to_cart
					)
				);

				$get_ids = array();
				if ( isset( $get_old_cart_id ) ) {
					$get_ids = $wpdb->get_col( // phpcs:ignore
						$wpdb->prepare(
							'SELECT id FROM `' . $wcal_email_sent_table . '` WHERE abandoned_order_id = %d', // phpcs:ignore
							$get_old_cart_id
						)
					);
				}

				$update_sent_history = array();

				if ( '' !== get_post_meta( $order_id, 'wcal_abandoned_timestamp', true ) ) {
					$update_details['abandoned_cart_time'] = get_post_meta( $order_id, 'wcal_abandoned_timestamp', true );

					$update_sent_history['abandoned_order_id'] = $cart_id;

					delete_post_meta( $order_id, 'wcal_abandoned_timestamp', $update_details['abandoned_cart_time'] );
				}

				$wpdb->update( // phpcs:ignore
					$wcal_ac_table_name,
					$update_details,
					array( 'id' => $cart_id )
				);

				// update the email sent history table.
				if ( is_array( $get_ids ) && count( $get_ids ) > 1 ) {
					$list_ids = implode( ',', $get_ids );
					$wpdb->query( // phpcs:ignore
						$wpdb->prepare(
							'UPDATE `' . $wcal_email_sent_table . '` SET abandoned_order_id = %d WHERE id IN (%s)', // phpcs:ignore
							$cart_id,
							$list_ids
						)
					);
				} elseif ( isset( $update_sent_history['abandoned_order_id'] ) ) {
					$wpdb->update( // phpcs:ignore
						$wcal_email_sent_table,
						$update_sent_history,
						array( 'id' => $wcal_check_email_sent_to_cart )
					);
				}

				// Add Order Note.
				$order->add_order_note( __( 'This order was abandoned & subsequently recovered.', 'woocommerce-abandoned-cart' ) );
				delete_post_meta( $order_id, 'wcal_abandoned_cart_id' );
				delete_post_meta( $order_id, 'wcal_recover_order_placed' );
				delete_post_meta( $order_id, 'wcal_recover_order_placed_sent_id' );
				delete_post_meta( $order_id, 'wcal_recovered_email_sent' );
			}
		}

		/**
		 * When customer clicks on the "Place Order" button on the checkout page, it will identify if we need to keep that cart or
		 * delete it.
		 *
		 * @hook woocommerce_checkout_order_processed
		 * @param int | string $order_id Order id.
		 * @globals mixed $wpdb
		 * @globals mixed $woocommerce
		 *
		 * @since 3.4
		 */
		public function wcal_order_placed( $order_id ) {

			global $wpdb;
			$email_sent_id         = wcal_common::wcal_get_cart_session( 'email_sent_id' );
			$abandoned_order_id    = wcal_common::wcal_get_cart_session( 'abandoned_cart_id_lite' );
			$wcal_user_id_of_guest = wcal_common::wcal_get_cart_session( 'user_id' );

			$wcal_history_table_name    = $wpdb->prefix . 'ac_abandoned_cart_history_lite';
			$wcal_guest_table_name      = $wpdb->prefix . 'ac_guest_abandoned_cart_history_lite';
			$wcal_sent_email_table_name = $wpdb->prefix . 'ac_sent_history_lite';

			$abandoned_order_id_to_save = $abandoned_order_id;
			if ( (int) $email_sent_id > 0 ) { // recovered cart.

				if ( '' == $abandoned_order_id || $abandoned_order_id == false ) { // phpcs:ignore

					$get_ac_id_results = $wpdb->get_results( // phpcs:ignore
						$wpdb->prepare(
							'SELECT abandoned_order_id FROM `' . $wcal_sent_email_table_name . '` WHERE id = %d', // phpcs:ignore
							$email_sent_id
						)
					);

					$abandoned_order_id_to_save = $get_ac_id_results[0]->abandoned_order_id;
				}

				// if user becomes the registered user.
				if ( ( isset( $_POST['account_password'] ) && '' !== $_POST['account_password'] ) || // phpcs:ignore WordPress.Security.NonceVerification
					( isset( $_POST['createaccount'] ) && '' !== $_POST['createaccount'] ) || // phpcs:ignore WordPress.Security.NonceVerification
					( ! isset( $_POST['createaccount'] ) && 'no' === get_option( 'woocommerce_enable_guest_checkout', '' ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification

					if ( '' != $abandoned_order_id && '' != $wcal_user_id_of_guest ) { // phpcs:ignore
						$abandoned_cart_id_new_user = $abandoned_order_id;

						// delete the guest record. As it become the logged in user.
						$get_ac_id_guest_results = $wpdb->get_results( // phpcs:ignore
							$wpdb->prepare(
								'SELECT id, abandoned_cart_time FROM `' . $wcal_history_table_name . '` WHERE user_id = %d ORDER BY id DESC', // phpcs:ignore
								(int) $wcal_user_id_of_guest
							)
						);

						if ( is_array( $get_ac_id_guest_results ) && count( $get_ac_id_guest_results ) > 0 ) {
							$abandoned_order_id_of_guest = $get_ac_id_guest_results[0]->id;

							add_post_meta( $order_id, 'wcal_abandoned_timestamp', $get_ac_id_guest_results[0]->abandoned_cart_time );

							$wpdb->delete( // phpcs:ignore
								$wcal_guest_table_name,
								array( 'id' => $wcal_user_id_of_guest )
							);
							$wpdb->delete( // phpcs:ignore
								$wcal_history_table_name,
								array( 'id' => $get_ac_id_guest_results[0]->id )
							);
						}
						// it is the new registered users cart id.
						$abandoned_order_id_to_save = $abandoned_cart_id_new_user;
					}
				}

				add_post_meta( $order_id, 'wcal_recover_order_placed_sent_id', $email_sent_id );
				add_post_meta( $order_id, 'wcal_recover_order_placed', $abandoned_order_id );
			} elseif ( '' !== $abandoned_order_id ) {

				if ( ( isset( $_POST['account_password'] ) && '' !== $_POST['account_password'] ) || // phpcs:ignore WordPress.Security.NonceVerification
					( isset( $_POST['createaccount'] ) && '' !== $_POST['createaccount'] ) || // phpcs:ignore WordPress.Security.NonceVerification
					( ! isset( $_POST['createaccount'] ) && 'no' === get_option( 'woocommerce_enable_guest_checkout', '' ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification

					if ( '' !== $abandoned_order_id && '' !== $wcal_user_id_of_guest ) {
						$abandoned_cart_id_new_user = $abandoned_order_id;

						// delete the guest record. As it become the logged in user.
						$wpdb->delete( $wcal_history_table_name, array( 'user_id' => $wcal_user_id_of_guest ) ); // phpcs:ignore
						$wpdb->delete( $wcal_guest_table_name, array( 'id' => $wcal_user_id_of_guest ) ); // phpcs:ignore

						// it is the new registered users cart id.
						$abandoned_order_id_to_save = $abandoned_cart_id_new_user;
					}
				}
			}

			add_post_meta( $order_id, 'wcal_abandoned_cart_id', $abandoned_order_id_to_save );
		}
	}
}

return new Wcal_Checkout_Process();
