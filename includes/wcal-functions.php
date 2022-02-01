<?php
/**
 * Abandoned Cart Lite for WooCommerce
 *
 * Reuseable functions
 *
 * @author  Tyche Softwares
 * @package abandoned-cart-lite
 * @since 5.12.0
 */

/**
 * Returns the Cart History Data.
 *
 * @param int $cart_id - Abandoned Cart ID.
 * @return object $cart_history - From the Abandoned Cart History table.
 * @since 8.7.0
 */
function wcal_get_data_cart_history( $cart_id ) {
	global $wpdb;

	$cart_history = $wpdb->get_results( // phpcs:ignore
		$wpdb->prepare(
			'SELECT id, user_id, abandoned_cart_info, abandoned_cart_time, cart_ignored, recovered_cart, user_type, checkout_link FROM `' . $wpdb->prefix . 'ac_abandoned_cart_history_lite` WHERE id = %d', // phpcs:ignore
			$cart_id
		)
	);

	if ( is_array( $cart_history ) && count( $cart_history ) > 0 ) {
		return $cart_history[0];
	} else {
		return false;
	}
}

/**
 * Returns the Guest Data.
 *
 * @param int $user_id - Guest User ID.
 * @return object $guest_data - From the Guest History table.
 * @since 8.7.0
 */
function wcal_get_data_guest_history( $user_id ) {

	global $wpdb;

	$guest_data = $wpdb->get_results( // phpcs:ignore
		$wpdb->prepare(
			'SELECT billing_first_name, billing_last_name, billing_zipcode, email_id, phone, shipping_zipcode, shipping_charges FROM `' . $wpdb->prefix . 'ac_guest_abandoned_cart_history_lite` WHERE id = %d', // phpcs:ignore
			$user_id
		)
	);

	if ( is_array( $guest_data ) && count( $guest_data ) > 0 ) {
		return $guest_data[0];
	} else {
		return false;
	}
}

/**
 * Return an array of product details.
 *
 * @param string $cart_data - Abandoned Cart Data frm the Cart History table.
 * @return array $product_details - Product Details.
 * @since 8.7.0
 */
function wcal_get_product_details( $cart_data ) {

	$product_details = array();
	$cart_value      = json_decode( stripslashes( $cart_data ) );

	if ( isset( $cart_value->cart ) && count( get_object_vars( $cart_value->cart ) ) > 0 ) {
		foreach ( $cart_value->cart as $product_data ) {
			$product_id = $product_data->variation_id > 0 ? $product_data->variation_id : $product_data->product_id;
			$details    = (object) array(
				'product_id'    => $product_data->product_id,
				'variation_id'  => $product_data->variation_id,
				'product_name'  => get_the_title( $product_id ),
				'line_subtotal' => $product_data->line_subtotal,
			);
			array_push( $product_details, $details );
		}
	}

	return $product_details;
}
