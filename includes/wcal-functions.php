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

use Automattic\WooCommerce\Utilities\OrderUtil;

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

/**
 * Return a Random key which can be used for encryption.
 *
 * @param string $user_email - User EMail Address.
 * @param bool   $insert - Insert and save the crypt key in the sent history table.
 * @param int    $cart_id - Abandoned Cart ID.
 * @return string $crypt_key - Key to be used for encryption.
 *
 * @since 5.14.0
 */
function wcal_get_crypt_key( $user_email, $insert = false, $cart_id = 0 ) {
	global $wpdb;

	$crypt_key = $wpdb->get_var( // phpcs:ignore
		$wpdb->prepare(
			'SELECT encrypt_key FROM `' . $wpdb->prefix . 'ac_sent_history_lite` WHERE sent_email_id = %s ORDER BY id DESC',
			$user_email
		)
	);

	if ( null === $crypt_key || empty( $crypt_key ) || '' === $crypt_key ) {
		$crypt_key = wcal_generate_random_key();
		if ( $insert ) { // This is true when the checkout link is generated and simply saved in the cart history table.
			$wpdb->insert( // phpcs:ignore
				$wpdb->prefix . 'ac_sent_history_lite',
				array(
					'template_id'        => 0,
					'abandoned_order_id' => $cart_id,
					'sent_time'          => current_time( 'mysql' ),
					'sent_email_id'      => $user_email,
					'encrypt_key'        => $crypt_key,
				)
			);

		}
	}
	return $crypt_key;
}

/**
 * Generate a 16 digit random key.
 *
 * @return string $random_string - Random String.
 * @since 5.14.0
 */
function wcal_generate_random_key() {
	$characters    = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
	$random_string = '';
	$n             = 16;
	for ( $i = 0; $i < $n; $i++ ) {
		$index          = wp_rand( 0, strlen( $characters ) - 1 );
		$random_string .= $characters[ $index ];
	}

	return $random_string;
}

/**
 * Return personal details for user.
 *
 * @param int $abandoned_id - Cart ID.
 * @return array $customer_data - Customer Details.
 *
 * @since 5.14.0
 */
function wcal_get_contact_data( $abandoned_id ) {
	// Fetch the contact data.
	$cart_history  = wcal_get_data_cart_history( $abandoned_id );
	$customer_data = array();
	if ( $cart_history ) {
		// Defaults.
		$email     = '';
		$firstname = '';
		$lastname  = '';
		$user_id   = $cart_history->user_id;
		$user_type = $cart_history->user_type;

		if ( $user_id >= 63000000 && 'GUEST' === $user_type ) {
			$guest_data = wcal_get_data_guest_history( $user_id );
			if ( isset( $cart_history ) && isset( $guest_data ) ) {
				$email     = $guest_data->email_id;
				$firstname = $guest_data->billing_first_name;
				$lastname  = $guest_data->billing_last_name;
			}
		} elseif ( $user_id > 0 ) {
			// Get the first & last name from the user data.
			$user_info = new WP_User( $user_id );
			$firstname = $user_info->first_name;
			$lastname  = $user_info->last_name;
			$email     = $user_info->user_email;
		}
		$customer_data['firstname'] = $firstname;
		$customer_data['lastname']  = $lastname;
		$customer_data['email']     = $email;
		$customer_data['user_id']   = $user_id;
	}
	return $customer_data;
}

/**
 * Returns if HPOS is enabled
 *
 * @return bool
 * @since  5.14.2
 */
function wcal_is_hpos_enabled() {
	if ( version_compare( WOOCOMMERCE_VERSION, '7.1.0' ) < 0 ) {
		return false;
	}
	if ( OrderUtil::custom_orders_table_usage_is_enabled() ) {
		return true;
	}
	return false;
}
