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
