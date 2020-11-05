<?php
/**
 * This class will add messages as needed informing users of data being tracked.
 *
 * @author   Tyche Softwares
 * @package  Abandoned-Cart-Lite-for-WooCommerce/Tracking
 * @since    4.9
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}
if ( ! class_exists( 'Wcal_Tracking_Msg' ) ) {

	/**
	 * It will add messages as needed informing users of data being tracked.
	 *
	 * @since    4.9
	 */
	class Wcal_Tracking_Msg {

		/**
		 * Construct.
		 */
		public function __construct() {
			// Product page notice for logged in users.
			add_action( 'woocommerce_after_add_to_cart_button', array( &$this, 'wcal_add_logged_msg' ), 10 );
			add_action( 'wp_ajax_wcal_gdpr_refused', array( 'wcal_common', 'wcal_gdpr_refused' ) );
		}

		/**
		 * Adds a message to be displayed for logged in users
		 * Called on Shop & Product page
		 *
		 * @hook woocommerce_after_add_to_cart_button
		 *       woocommerce_before_shop_loop
		 * @since 4.9
		 */
		public static function wcal_add_logged_msg() {
			if ( is_user_logged_in() ) {

				$registered_msg = get_option( 'wcal_logged_cart_capture_msg', '' );
				$gdpr_consent   = get_user_meta( get_current_user_id(), 'wcal_gdpr_tracking_choice', true );

				if ( '' === $gdpr_consent ) {
					$gdpr_consent = true;
				}

				if ( isset( $registered_msg ) && '' !== $registered_msg && $gdpr_consent ) {
					wp_enqueue_script(
						'wcal_registered_capture',
						plugins_url( '../assets/js/wcal_registered_user_capture.js', __FILE__ ),
						'',
						WCAL_PLUGIN_VERSION,
						true
					);

					$vars = array(
						'_gdpr_after_no_thanks_msg' => htmlspecialchars( get_option( 'wcal_gdpr_opt_out_message' ), ENT_QUOTES ),
						'ajax_url'                  => admin_url( 'admin-ajax.php' ),
					);

					wp_localize_script(
						'wcal_registered_capture',
						'wcal_registered_capture_params',
						$vars
					);

					$registered_msg .= " <span id='wcal_gdpr_no_thanks'><a style='cursor: pointer' id='wcal_gdpr_no_thanks'>" . htmlspecialchars( get_option( 'wcal_gdpr_allow_opt_out' ), ENT_QUOTES ) . '</a></span>';
					echo wp_kses_post( "<span id='wcal_gdpr_message_block'><p><small>" . $registered_msg . '</small></p></span>' );
				}
			}
		}

	} // end of class.
	$wcal_tracking_msg = new Wcal_Tracking_Msg();
} // end IF.
