<?php
/**
 * Abandoned Cart Lite for WooCommerce
 *
 * Class for abandon cart webhooks.
 *
 * @author   Tyche Softwares
 * @package  WCAL/webhooks
 * @category Classes
 */

/**
 * WCAL Webhooks
 *
 * @since 8.7.0
 */
class Wcal_Webhooks {

	/**
	 * Plugin hooks & functions.
	 *
	 * @since 5.12.0
	 */
	public static function init() {

		// Setup webhooks.
		add_filter( 'woocommerce_webhook_topics', array( __CLASS__, 'wcal_add_new_webhook_topics' ), 10, 1 );
		add_filter( 'woocommerce_webhook_topic_hooks', array( __CLASS__, 'wcal_add_topic_hooks' ), 10, 1 );
		add_filter( 'woocommerce_valid_webhook_resources', array( __CLASS__, 'wcal_add_cart_resources' ), 10, 1 );
		add_filter( 'woocommerce_valid_webhook_events', array( __CLASS__, 'wcal_add_cart_events' ), 10, 1 );
		add_filter( 'woocommerce_webhook_payload', array( __CLASS__, 'wcal_generate_payload' ), 10, 4 );
		add_filter( 'woocommerce_webhook_deliver_async', array( __CLASS__, 'wcal_deliver_sync' ), 10, 3 );
		// Process Webhook actions.
		add_action( 'wcal_cart_recovered', array( __CLASS__, 'wcal_cart_recovered' ), 10, 2 );
		add_action( 'wcap_webhook_after_cutoff', array( __CLASS__, 'wcal_cart_cutoff_reached' ), 10, 1 );
	}

	/**
	 * Add new list of events for webhooks in WC->Settings->Advanced->Webhooks.
	 *
	 * @param array $topics - Topic Hooks.
	 * @return array $topics - Topic Hooks including the ones from our plugin.
	 * @since 8.7.0
	 */
	public static function wcal_add_new_webhook_topics( $topics ) {

		$new_topics = array(
			// Cut off reached.
			'wcap_cart.cutoff'    => __( 'Cart Abandoned after cut-off time', 'woocommerce-ac' ),
			// Order Recovered.
			'wcap_cart.recovered' => __( 'Abandoned Order Recovered', 'woocommerce-ac' ),
		);
		return array_merge( $topics, $new_topics );
	}

	/**
	 * Trigger hooks for the plugin topics.
	 *
	 * @param array $topic_hooks - Topic Hooks.
	 * @return array $topic_hooks - Topic Hooks including the ones from our plugin.
	 * @since 8.7.0
	 */
	public static function wcal_add_topic_hooks( $topic_hooks ) {

		$new_hooks = array(
			'wcap_cart.cutoff'    => array( 'wcap_abandoned_cart_cutoff' ),
			'wcap_cart.recovered' => array( 'wcap_abandoned_cart_recovered' ),
		);

		return array_merge( $new_hooks, $topic_hooks );
	}

	/**
	 * Add webhook resources.
	 *
	 * @param array $topic_resources - Webhook Resources.
	 * @return array $topic_resources - Webhook Resources including the ones from our plugin.
	 * @since 8.7.0
	 */
	public static function wcal_add_cart_resources( $topic_resources ) {

		// Webhook resources for wcap.
		$new_resources = array(
			'wcap_cart',
		);

		return array_merge( $new_resources, $topic_resources );
	}

	/**
	 * Add webhook events.
	 *
	 * @param array $topic_events - List of events.
	 * @return array $topic_events - List of events including the ones from the plugin.
	 * @since 8.7.0
	 */
	public static function wcal_add_cart_events( $topic_events ) {

		// Webhook events for wcap.
		$new_events = array(
			'cutoff',
			'recovered',
		);

		return array_merge( $new_events, $topic_events );

	}

	/**
	 * Deliver the webhooks in background or realtime.
	 *
	 * @param bool   $value - true|false - deliver the webhook in background|deliver in realtime.
	 * @param object $webhook - WC Webhook object.
	 * @param array  $arg - Arguments.
	 * @return bool  $value - Return false causes the webhook to be delivered immediately.
	 *
	 * @since 8.7.0
	 */
	public static function wcal_deliver_sync( $value, $webhook, $arg ) {
		$wcal_webhook_topics = array(
			'wcap_cart.cutoff',
			'wcap_cart.recovered',
		);

		if ( in_array( $webhook->get_topic(), $wcal_webhook_topics, true ) ) {
			return false;
		}

		return $value;
	}

	/**
	 * Generate data for webhook delivery.
	 *
	 * @param array  $payload - Array of Data.
	 * @param string $resource - Resource.
	 * @param array  $resource_data - Resource Data.
	 * @param int    $id - Webhook ID.
	 * @return array $payload - Array of Data.
	 *
	 * @since 8.7.0
	 */
	public static function wcal_generate_payload( $payload, $resource, $resource_data, $id ) {

		switch ( $resource_data['action'] ) {
			case 'cutoff':
			case 'recovered':
				$webhook_meta = array(
					'webhook_id'          => $id,
					'webhook_action'      => $resource_data['action'],
					'webhook_resource'    => $resource,
					'webhook_resource_id' => $resource_data['id'],
				);

				$payload = array_merge( $webhook_meta, $resource_data['data'] );
				break;
		}

		return $payload;
	}

	/**
	 * Triggers a webhook when a cart is marked as recovered.
	 *
	 * @param int $abandoned_id - Abandoned Cart ID.
	 * @param int $order_id     - Order ID.
	 * @since 8.7.0
	 */
	public static function wcal_cart_recovered( $abandoned_id, $order_id ) {

		if ( $abandoned_id > 0 && $order_id > 0 ) {

			// Setup the data.
			$send_data = self::wcal_reminders_webhook_data( $abandoned_id );

			if ( is_array( $send_data ) ) {

				$order = wc_get_order( $order_id );

				$send_data['order_id']  = $order_id;
				$send_data['total']     = $order->get_total();
				$send_data['tax_total'] = $order->get_total_tax();
				$data                   = array(
					'id'     => $abandoned_id,
					'data'   => $send_data,
					'action' => 'recovered',
				);

				do_action( 'wcap_abandoned_cart_recovered', $data );
			}
		}
	}

	/**
	 * Triggers a webhook once cart cutoff is reached.
	 *
	 * @param int $abandoned_cart_id - Abandoned Cart ID.
	 * @since 8.7.0
	 */
	public static function wcal_cart_cutoff_reached( $abandoned_cart_id ) {
		if ( $abandoned_cart_id > 0 ) {

			$cart_data = wcal_get_data_cart_history( $abandoned_cart_id );

			if ( $cart_data ) {
				$user_id   = $cart_data->user_id;
				$user_type = $cart_data->user_type;

				$billing_first_name = '';
				$billing_last_name  = '';
				$email_id           = '';
				$phone              = '';
				$billing_country    = '';
				$billing_zipcode    = '';
				$coupon_code        = '';
				$checkout_link      = '';

				if ( 'GUEST' == $user_type && $user_id >= 63000000 ) { //phpcs:ignore
					$guest_data = wcal_get_data_guest_history( $user_id );

					if ( $guest_data ) {
						$billing_first_name = $guest_data->billing_first_name;
						$billing_last_name  = $guest_data->billing_last_name;
						$email_id           = $guest_data->email_id;
						$phone              = $guest_data->phone;
						$billing_zipcode    = $guest_data->billing_zipcode;
					}
				} elseif ( 'REGISTERED' == $user_type && $user_id > 0 ) { // phpcs:ignore
					$billing_first_name = get_user_meta( $user_id, 'billing_first_name', true );
					$billing_last_name  = get_user_meta( $user_id, 'billing_last_name', true );
					$email_id           = get_user_meta( $user_id, 'billing_email', true );
					$phone              = get_user_meta( $user_id, 'billing_phone', true );
					$billing_country    = get_user_meta( $user_id, 'billing_country', true );
					$billing_zipcode    = get_user_meta( $user_id, 'billing_zipcode', true );
				}

				$product_details = wcal_get_product_details( $cart_data->abandoned_cart_info );

				$total      = 0;
				$total_tax  = 0;
				$cart_value = json_decode( stripslashes( $cart_data->abandoned_cart_info ) );

				if ( isset( $cart_value->cart ) && count( get_object_vars( $cart_value->cart ) ) > 0 ) {
					foreach ( $cart_value->cart as $product_data ) {
						$total     += $product_data->line_subtotal;
						$total_tax += $product_data->line_tax;
					}
				}

				$coupon_meta = wcal_common::wcal_get_coupon_post_meta( $abandoned_cart_id );

				if ( is_array( $coupon_meta ) && count( $coupon_meta ) > 0 ) {
					$coupon_code = '';
					foreach ( $coupon_meta as $code => $msg ) {
						$coupon_code .= "$code,";
					}
					// Remove the last extra delimiter.
					$coupon_code = substr( $coupon_code, 0, -1 );
				}
				$checkout_link = $cart_data->checkout_link;

				$send_data = array(
					'id'                 => $abandoned_cart_id,
					'user_id'            => $user_id,
					'product_details'    => $product_details,
					'total'              => $total,
					'total_tax'          => $total_tax,
					'timestamp'          => $cart_data->abandoned_cart_time,
					'billing_first_name' => $billing_first_name,
					'billing_last_name'  => $billing_last_name,
					'billing_country'    => $billing_country,
					'billing_zipcode'    => $billing_zipcode,
					'email_id'           => $email_id,
					'phone'              => $phone,
					'user_type'          => $user_type,
					'coupon_code'        => $coupon_code,
					'checkout_link'      => $checkout_link,
				);

				$data = array(
					'id'     => $abandoned_cart_id,
					'data'   => $send_data,
					'action' => 'cutoff',
				);

				do_action( 'wcap_abandoned_cart_cutoff', $data );
			}
		}
	}

	/**
	 * Returns an array of cart data.
	 *
	 * @param int $abandoned_id - Abandoned Cart ID.
	 * @return array $send_data - Array of Cart Data.
	 * @since 8.7.0
	 */
	public static function wcal_reminders_webhook_data( $abandoned_id ) {

		$cart_history = wcal_get_data_cart_history( $abandoned_id );

		if ( $cart_history ) {
			$user_id   = $cart_history->user_id;
			$user_type = $cart_history->user_type;

			$billing_first_name = '';
			$billing_last_name  = '';
			$email_id           = '';
			$phone              = '';

			if ( $user_id >= 63000000 && 'GUEST' == $user_type ) { // phpcs:ignore
				$guest_data = wcal_get_data_guest_history( $user_id );

				if ( $guest_data ) {
					$billing_first_name = $guest_data->billing_first_name;
					$billing_last_name  = $guest_data->billing_last_name;
					$email_id           = $guest_data->email_id;
				}
			} elseif ( 'REGISTERED' == $user_type ) { // phpcs:ignore

				$billing_first_name = get_user_meta( $user_id, 'billing_first_name', true );
				$billing_last_name  = get_user_meta( $user_id, 'billing_last_name', true );
				$email_id           = get_user_meta( $user_id, 'billing_email', true );
				$phone              = get_user_meta( $user_id, 'billing_phone', true );
			}

			$product_details = wcal_get_product_details( $cart_history->abandoned_cart_info );

			$send_data = array(
				'id'                 => $abandoned_id,
				'product_details'    => $product_details,
				'timestamp'          => $cart_history->abandoned_cart_time,
				'billing_first_name' => $billing_first_name,
				'billing_last_name'  => $billing_last_name,
				'email_id'           => $email_id,
				'phone'              => $phone,
				'user_type'          => $user_type,
			);

			return $send_data;
		}
		return false;
	}

}
Wcal_Webhooks::init();
