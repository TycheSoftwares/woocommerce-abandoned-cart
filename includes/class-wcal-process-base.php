<?php
/**
 * Abandoned Cart Lite for WooCommerce
 *
 * @author   Tyche Softwares
 * @package  Abandoned-Cart-Lite-for-WooCommerce/Background Processing
 * @since    4.9
 */

/**
 * This class will help run automated scheduler actions to send reminders.
 */
class Wcal_Process_Base {

	/**
	 * Construct.
	 */
	public function __construct() {
		// Hook into that action that'll fire every 15 minutes.
		add_action( 'woocommerce_ac_send_email_action', array( &$this, 'wcal_process_handler' ), 11 );

		// Initate a scheduled action for abandonment notification.
		add_action( 'wcal_webhook_initiated', array( &$this, 'wcal_schedule_email_notification' ), 10, 1 );
	}

	/**
	 * Initiate scheduled action for abandonment notification.
	 *
	 * @param int $cart_id - Cart ID.
	 * @since 6.7.0
	 */
	public function wcal_schedule_email_notification( $cart_id ) {

		$admin_notification_status = get_option( 'wcap_email_admin_on_abandonment', '' );

		// Check if feature is enabled.
		if ( 'on' === $admin_notification_status && $cart_id > 0 ) {

			$cart_history = wcal_get_data_cart_history( $cart_id );

			if ( $cart_history ) {
				$user_id   = $cart_history->user_id;
				$user_type = $cart_history->user_type;

				$billing_first_name = '';
				$billing_last_name  = '';
				$email_id           = '';
				$phone              = '';

				if ( $user_id >= 63000000 && 'GUEST' === $user_type ) {
					$guest_data = wcal_get_data_guest_history( $user_id );

					if ( $guest_data ) {
						$billing_first_name = $guest_data->billing_first_name;
						$billing_last_name  = $guest_data->billing_last_name;
						$email_id           = $guest_data->email_id;
						$phone              = $guest_data->phone;
					}
				} elseif ( 'REGISTERED' === $user_type ) {
					$billing_first_name = get_user_meta( $user_id, 'billing_first_name', true );
					$billing_last_name  = get_user_meta( $user_id, 'billing_last_name', true );
					$email_id           = get_user_meta( $user_id, 'billing_email', true );
					$phone              = get_user_meta( $user_id, 'billing_phone', true );
				}

				$email_id = apply_filters( 'wcap_cart_abandoned_alter_email_id', $email_id );

				// At the minimum a phone number or email address should've been captured.
				if ( ( '' !== $email_id && null !== $email_id ) || ( '' !== $phone && null !== $phone ) ) {

					$cart_details = json_decode( $cart_history->abandoned_cart_info );
					$cut_off      = 0;
					$cut_off      = is_numeric( get_option( 'ac_lite_cart_abandoned_time', 10 ) ) ? get_option( 'ac_lite_cart_abandoned_time', 10 ) : 10;

					$notification_buffer = apply_filters( 'wcal_admin_notification_buffer', 0 );
					$cut_off            += (int) $notification_buffer;

					$cut_off   = $cut_off * 60; // convert to seconds.
					$cart_id   = (int) $cart_id;
					$scheduled = as_next_scheduled_action( 'wcap_send_admin_notification', array( 'id' => $cart_id ) );

					if ( $cut_off > 0 && ! $scheduled ) {
						as_schedule_single_action( time() + $cut_off, 'wcap_send_admin_notification', array( 'id' => $cart_id ) );
					}
				}
			}
		}
	}

	/**
	 * Process handler. Call the reminder functions.
	 */
	public function wcal_process_handler() {
		// Add any new reminder methods added in the future for cron here.
		$reminders_list = array( 'emails' );

		if ( is_array( $reminders_list ) && count( $reminders_list ) > 0 ) {
			foreach ( $reminders_list as $reminder_type ) {
				switch ( $reminder_type ) {
					case 'emails':
						$wcal_cron = new Wcal_Cron();
						$wcal_cron->wcal_send_email_notification();
						break;
				}
			}
		}

	}

}
new Wcal_Process_Base();
