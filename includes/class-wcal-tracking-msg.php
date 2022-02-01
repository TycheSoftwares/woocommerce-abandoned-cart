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
				$enable_gdpr    = get_option( 'wcal_enable_gdpr_consent', '' );
				if ( '' === $gdpr_consent ) {
					$gdpr_consent = true;
				}

				if ( 'on' === $enable_gdpr && $gdpr_consent ) {
					wp_enqueue_script(
						'wcal_registered_capture',
						plugins_url( '../assets/js/wcal_registered_user_capture.js', __FILE__ ),
						'',
						WCAL_PLUGIN_VERSION,
						true
					);
					$opt_out_confirmation_msg = get_option( 'wcal_gdpr_opt_out_message', '' );
					$opt_out_confirmation_msg = apply_filters( 'wcal_gdpr_opt_out_confirmation_text', $opt_out_confirmation_msg );

					$vars = array(
						'_gdpr_after_no_thanks_msg' => htmlspecialchars( $opt_out_confirmation_msg, ENT_QUOTES ),
						'ajax_url'                  => admin_url( 'admin-ajax.php' ),
					);

					wp_localize_script(
						'wcal_registered_capture',
						'wcal_registered_capture_params',
						$vars
					);

					$display_msg = isset( $registered_msg ) && '' !== $registered_msg ? $registered_msg : __( 'Saving your email and cart details helps us keep you up to date with this order.', 'woocommerce-abandoned-cart' );
					$display_msg = apply_filters( 'wcal_gdpr_email_consent_registered_users', $display_msg );

					$no_thanks    = get_option( 'wcal_gdpr_allow_opt_out', '' );
					$no_thanks    = apply_filters( 'wcal_gdpr_opt_out_text', $no_thanks );
					$display_msg .= " <span id='wcal_gdpr_no_thanks'><a style='cursor: pointer; text-decoration:none;' id='wcal_gdpr_no_thanks'>" . htmlspecialchars( $no_thanks, ENT_QUOTES ) . '</a></span>';
					echo "<span id='wcal_gdpr_message_block'><p><small>" . wp_kses_post( $display_msg ) . '</small></p></span>';
				}
			}
		}

	} // end of class.
	$wcal_tracking_msg = new Wcal_Tracking_Msg();
} // end IF.
