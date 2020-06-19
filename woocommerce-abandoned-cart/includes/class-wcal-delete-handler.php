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
		if ( $abandoned_cart_id > 0 ) {
			global $wpdb;
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$results_get_user_id = $wpdb->get_results(
				$wpdb->prepare(
					'SELECT user_id FROM `' . $wpdb->prefix . 'ac_abandoned_cart_history_lite` 
					WHERE id = %s',
					$abandoned_cart_id
				)
			);
			$user_id_of_guest    = isset( $results_get_user_id[0]->user_id ) ? $results_get_user_id[0]->user_id : 0;

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$results_delete = $wpdb->get_results(
				$wpdb->prepare(
					'DELETE FROM `' . $wpdb->prefix . 'ac_abandoned_cart_history_lite`
					WHERE id = %s',
					$abandoned_cart_id
				)
			);

			if ( $user_id_of_guest >= '63000000' ) {
				// Guest user.
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery
				$results_guest = $wpdb->get_results(
					$wpdb->prepare(
						'DELETE FROM `' . $wpdb->prefix . 'ac_abandoned_cart_history_lite` 
						WHERE id = %s',
						$user_id_of_guest
					)
				);
			}
			wp_safe_redirect( admin_url( '/admin.php?page=woocommerce_ac_page&action=listcart&wcal_deleted=YES' ) );
		}
	}

	/**
	 * Delete all registered user carts from the Bulk Actions menu in Abandoned Orders page.
	 *
	 * @since 5.8.0
	 */
	public function wcal_bulk_action_delete_registered_carts_handler() {
		global $wpdb;

		$wpdb->query( // phpcs:ignore
			'DELETE FROM `' . $wpdb->prefix . 'ac_abandoned_cart_history_lite`
			WHERE user_id < 63000000
			AND user_id > 0'
		);

		wp_safe_redirect( admin_url( '/admin.php?page=woocommerce_ac_page&action=listcart&wcal_deleted=YES&wcal_deleted_all_registered=YES' ) );
	}

	/**
	 * Delete all guest user carts from the Bulk Actions menu in Abandoned Orders page.
	 *
	 * @since 5.8.0
	 */
	public function wcal_bulk_action_delete_guest_carts_handler() {
		global $wpdb;

		$wpdb->query( // phpcs:ignore
			'DELETE FROM `' . $wpdb->prefix . 'ac_abandoned_cart_history_lite`
			WHERE user_id >= 63000000'
		);

		$wpdb->query( // phpcs:ignore
			'DELETE FROM `' . $wpdb->prefix . 'ac_guest_abandoned_cart_history_lite`'
		);

		wp_safe_redirect( admin_url( '/admin.php?page=woocommerce_ac_page&action=listcart&wcal_deleted=YES&wcal_deleted_all_guest=YES' ) );
	}

	/**
	 * Delete all visitor user carts from the Bulk Actions menu in Abandoned Orders page.
	 *
	 * @since 5.8.0
	 */
	public function wcal_bulk_action_delete_visitor_carts_handler() {
		global $wpdb;

		$wpdb->query( // phpcs:ignore
			'DELETE FROM `' . $wpdb->prefix . 'ac_abandoned_cart_history_lite`
			WHERE user_id = 0'
		);

		wp_safe_redirect( admin_url( '/admin.php?page=woocommerce_ac_page&action=listcart&wcal_deleted=YES&wcal_deleted_all_visitor=YES' ) );
	}

	/**
	 * Delete all carts from the Bulk Actions menu in Abandoned Orders page.
	 *
	 * @since 5.8.0
	 */
	public function wcal_bulk_action_delete_all_carts_handler() {
		global $wpdb;

		$wpdb->query( // phpcs:ignore
			'DELETE FROM `' . $wpdb->prefix . 'ac_abandoned_cart_history_lite`'
		);

		$wpdb->query( // phpcs:ignore
			'DELETE FROM `' . $wpdb->prefix . 'ac_guest_abandoned_cart_history_lite`'
		);

		wp_safe_redirect( admin_url( '/admin.php?page=woocommerce_ac_page&action=listcart&wcal_deleted=YES&wcal_deleted_all=YES' ) );
	}

	/**
	 * Trigger when we delete the template.
	 *
	 * @param int | string $template_id Template id.
	 * @globals mixed $wpdb
	 * @since 2.5.2
	 */
	public function wcal_delete_template_bulk_action_handler_function( $template_id ) {
		global $wpdb;
		$id_remove = $template_id;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->query(
			$wpdb->prepare(
				'DELETE FROM `' . $wpdb->prefix . 'ac_email_templates_lite` 
				WHERE id = %s',
				$id_remove
			)
		);

		wp_safe_redirect( admin_url( '/admin.php?page=woocommerce_ac_page&action=emailtemplates&wcal_template_deleted=YES' ) );
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

		$delete_ac_after_days = get_option( 'ac_lite_delete_abandoned_order_days' );
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
