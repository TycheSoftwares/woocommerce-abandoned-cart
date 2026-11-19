<?php
/**
 * Abandoned Cart Lite for WooCommerce
 *
 * It will handle the common action for the plugin.
 *
 * @author  Tyche Softwares
 * @package abandoned-cart-lite
 * @since 2.5.2
 * @since 5.6 file name changed from wcal_actions.php to class-wcal-delete-handler
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Bulk Delete handler class.
 *
 * @since 5.6 class name changed from wcal_delete_bulk_action_handler to Wcal_Delete_Handler
 */
class Wcal_Delete_Handler {
	/**
	 * Trigger when we delete the abandoned cart.
	 *
	 * @param int | string $abandoned_cart_id Abandoned cart id.
	 * @globals mixed $wpdb
	 * @since 2.5.2
	 */
	public function wcal_delete_bulk_action_handler_function( $abandoned_cart_id ) {
		global $wpdb;

		$abandoned_cart_id = absint( $abandoned_cart_id );
		if ( ! $abandoned_cart_id ) {
			return;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$user_id = (int) $wpdb->get_var(
			$wpdb->prepare(
				'SELECT user_id FROM `' . $wpdb->prefix . 'ac_abandoned_cart_history_lite` WHERE id = %d',
				$abandoned_cart_id
			)
		);

		// Reminder history holds the customer's email address.
		$wpdb->delete( $wpdb->prefix . 'ac_sent_history_lite', array( 'abandoned_order_id' => $abandoned_cart_id ), array( '%d' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->delete( $wpdb->prefix . 'ac_abandoned_cart_history_lite', array( 'id' => $abandoned_cart_id ), array( '%d' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery

		// Guest name, email and phone live in the guest table. Keep the record while another cart still points to it.
		if ( $user_id >= 63000000 ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$in_use = (int) $wpdb->get_var(
				$wpdb->prepare(
					'SELECT COUNT(id) FROM `' . $wpdb->prefix . 'ac_abandoned_cart_history_lite` WHERE user_id = %d',
					$user_id
				)
			);
			if ( ! $in_use ) {
				$wpdb->delete( $wpdb->prefix . 'ac_guest_abandoned_cart_history_lite', array( 'id' => $user_id ), array( '%d' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			}
		}
	}

	/**
	 * Delete the reminder history of the carts matching a condition, before the carts themselves are deleted.
	 *
	 * @param string $where Condition on the cart table (alias c). Must not contain user input.
	 * @since 6.9.0
	 */
	private function delete_sent_history_for_carts( $where ) {
		global $wpdb;

		$wpdb->query( // phpcs:ignore
			'DELETE sh FROM `' . $wpdb->prefix . 'ac_sent_history_lite` AS sh
			INNER JOIN `' . $wpdb->prefix . 'ac_abandoned_cart_history_lite` AS c ON sh.abandoned_order_id = c.id
			WHERE ' . $where
		);
	}

	/**
	 * Delete all registered user carts from the Bulk Actions menu in Abandoned Orders page.
	 *
	 * @since 5.8.0
	 */
	public function wcal_bulk_action_delete_registered_carts_handler() {
		global $wpdb;

		$this->delete_sent_history_for_carts( 'c.user_id < 63000000 AND c.user_id > 0' );

		$wpdb->query( // phpcs:ignore
			'DELETE FROM `' . $wpdb->prefix . 'ac_abandoned_cart_history_lite`
			WHERE user_id < 63000000
			AND user_id > 0'
		);
	}

	/**
	 * Delete all guest user carts from the Bulk Actions menu in Abandoned Orders page.
	 *
	 * @since 5.8.0
	 */
	public function wcal_bulk_action_delete_guest_carts_handler() {
		global $wpdb;

		$this->delete_sent_history_for_carts( 'c.user_id >= 63000000' );

		$wpdb->query( // phpcs:ignore
			'DELETE FROM `' . $wpdb->prefix . 'ac_abandoned_cart_history_lite`
			WHERE user_id >= 63000000'
		);

		$wpdb->query( // phpcs:ignore
			'DELETE FROM `' . $wpdb->prefix . 'ac_guest_abandoned_cart_history_lite`'
		);
	}

	/**
	 * Delete all visitor user carts from the Bulk Actions menu in Abandoned Orders page.
	 *
	 * @since 5.8.0
	 */
	public function wcal_bulk_action_delete_visitor_carts_handler() {
		global $wpdb;

		$this->delete_sent_history_for_carts( 'c.user_id = 0' );

		$wpdb->query( // phpcs:ignore
			'DELETE FROM `' . $wpdb->prefix . 'ac_abandoned_cart_history_lite`
			WHERE user_id = 0'
		);
	}

	/**
	 * Delete all carts from the Bulk Actions menu in Abandoned Orders page.
	 *
	 * @since 5.8.0
	 */
	public function wcal_bulk_action_delete_all_carts_handler() {
		global $wpdb;

		$this->delete_sent_history_for_carts( '1 = 1' );

		$wpdb->query( // phpcs:ignore
			'DELETE FROM `' . $wpdb->prefix . 'ac_abandoned_cart_history_lite`'
		);

		$wpdb->query( // phpcs:ignore
			'DELETE FROM `' . $wpdb->prefix . 'ac_guest_abandoned_cart_history_lite`'
		);
	}

	/**
	 * It will delete cart automatically after X days.
	 *
	 * @hook admin_init
	 * @globals mixed $wpdb
	 * @since 5.0
	 */
	public static function wcal_delete_abandoned_carts_after_x_days() {
		global $wpdb;

		$delete_ac_after_days = get_option( 'ac_lite_delete_abandoned_order_days', 365 );
		if ( 0 === $delete_ac_after_days || '' === $delete_ac_after_days ) { // If for some reason, it is blanks or 0, reset it to 365.
			$delete_ac_after_days = '365';
		}
		if ( '' !== $delete_ac_after_days && 0 !== $delete_ac_after_days ) {

			$delete_ac_after_days_time = $delete_ac_after_days * 86400;
			$current_time              = current_time( 'timestamp' ); // phpcs:ignore
			$check_time                = $current_time - $delete_ac_after_days_time;

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$carts = $wpdb->get_results(
				$wpdb->prepare(
					'SELECT id, user_id, user_type FROM `' . $wpdb->prefix . 'ac_abandoned_cart_history_lite` WHERE recovered_cart = "0" AND abandoned_cart_time < %s',
					$check_time
				)
			);
			foreach ( $carts as $cart_key => $cart_value ) {
				self::wcal_delete_ac_carts( $cart_value );
			}
		}
	}

	/**
	 * It will delete the abandoned cart data from database.
	 * It will also delete the email history for that abandoned cart.
	 * If the user id guest user then it will delete the record from users table.
	 *
	 * @param object $value Value of cart.
	 * @globals mixed $wpdb
	 * @since 5.0
	 */
	public static function wcal_delete_ac_carts( $value ) {
		global $wpdb;

		$abandoned_id = $value->id;
		$user_id      = $value->user_id;
		$user_type    = $value->user_type;

		if ( $abandoned_id > 0 && '' !== $user_type ) {
			// Delete the sent history for reminder emails for the cart.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$query_delete_sent_history = $wpdb->delete(
				$wpdb->prefix . 'ac_sent_history_lite',
				array( 'abandoned_order_id' => $abandoned_id )
			);

			// Delete the user meta for the user.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$query_delete_cart = $wpdb->delete(
				$wpdb->prefix . 'usermeta',
				array(
					'user_id'  => $user_id,
					'meta_key' => '_woocommerce_persistent_cart', // phpcs:ignore WordPress.DB.SlowDBQuery
				)
			);

			// Delete the cart history table record.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$query = $wpdb->delete(
				$wpdb->prefix . 'ac_abandoned_cart_history_lite',
				array(
					'user_id' => $user_id,
					'id'      => $abandoned_id,
				)
			);

			// Delete the guest cart record if applicable.
			if ( 'GUEST' === $user_type && $user_id >= 63000000 ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery
				$guest_query = $wpdb->delete( $wpdb->prefix . 'ac_guest_abandoned_cart_history_lite', array( 'id' => $user_id ) );
			}
		}
	}
}
