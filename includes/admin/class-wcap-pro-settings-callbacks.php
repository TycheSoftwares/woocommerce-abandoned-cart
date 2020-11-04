<?php
/**
 * Callbacks for all the settings present in the PRO version
 *
 * @package Abandoned-Cart-Pro-for-WooCommerce/Admin/Settings
 * @since 2.4
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WCAP_Pro_Settings_Callbacks' ) ) {

	/**
	 * AC pro Settings callback functions.
	 */
	class WCAP_Pro_Settings_Callbacks {

		/**
		 * Construct
		 *
		 * @since 4.9
		 */
		public function __construct() {
		}

		/**
		 * SMS Settings section.
		 */
		public static function wcap_sms_settings_section_callback() {
			echo esc_html_e( 'Configure your Twilio account settings below. Please note that due to some restrictions from Twilio, customers <i>may sometimes</i> receive delayed messages', 'woocommerce-abandoned-cart' );
		}

		/**
		 * Callback for enable SMS reminders
		 *
		 * @param array $args Argument given while adding the field.
		 * @since 7.9
		 */
		public static function wcap_enable_sms_reminders_callback( $args ) {

			$wcap_enable_sms = 'off';

			printf(
				'<input type="checkbox" id="wcap_enable_sms_reminders" name="wcap_enable_sms_reminders" value="on" ' . checked( 'on', $wcap_enable_sms, false ) . ' readonly disabled/>'
			);

			$html = '<label for="wcap_enable_sms_reminders"> ' . $args[0] . '</label>';
			echo wp_kses_post( $html );
		}

		/**
		 * Callback for From Phone Number
		 *
		 * @param array $args Argument given while adding the field.
		 * @since 7.9
		 */
		public static function wcap_sms_from_phone_callback( $args ) {

			print(
				"<input type='text' id='wcap_sms_from_phone' name='wcap_sms_from_phone' value='' readonly />"
			);

			$html = '<label for="wcap_from_phone"> ' . $args[0] . '</label>';
			echo wp_kses_post( $html );
		}

		/**
		 * Callback for Account SID.
		 *
		 * @param array $args Argument given while adding the field.
		 * @since 7.9
		 */
		public static function wcap_sms_account_sid_callback( $args ) {

			print(
				"<input type='text' style='width:60%;' id='wcap_sms_account_sid' name='wcap_sms_account_sid' value='' readonly />"
			);

			$html = '<label for="wcap_sms_account_sid"> ' . $args[0] . '</label>';
			echo wp_kses_post( $html );
		}

		/**
		 * Callback for Auth Token.
		 *
		 * @param array $args Argument given while adding the field.
		 * @since 7.9
		 */
		public static function wcap_sms_auth_token_callback( $args ) {

			print(
				"<input type='text' style='width:60%;' id='wcap_sms_auth_token' name='wcap_sms_auth_token' value='' readonly />"
			);

			$html = '<label for="wcap_sms_auth_token"> ' . $args[0] . '</label>';
			echo wp_kses_post( $html );
		}

		/**
		 * FB Section.
		 */
		public static function wcap_fb_description() {
			echo esc_html_e( 'Configure the plugin to send notifications to Facebook Messenger using the settings below. Please refer the <a href="https://www.tychesoftwares.com/docs/docs/abandoned-cart-pro-for-woocommerce/send-abandoned-cart-reminder-notifications-using-facebook-messenger" target="_blank">following documentation</a> to complete the setup.', 'woocommerce-abandoned-cart' );
		}

		/**
		 * FB checkbox.
		 *
		 * @param array $args - Arguments.
		 */
		public static function wcap_fb_checkbox_callback( $args ) {

			if ( isset( $args[2] ) ) {
				$checkbox_value = get_option( $args[2] );
				$args_2         = $args[2];
			} else {
				$checkbox_value = '';
				$args_2         = 'wcap_fb_check';
			}

			if ( isset( $checkbox_value ) && '' === $checkbox_value ) {
				$checkbox_value = 'off';
			}

			printf(
				'<input type="checkbox" id="%1$s" name="%1$s" value="on" readonly disabled/>',
				esc_html( $args_2 )
			);

			$html = '<label for="$args_2"> ' . $args[0] . '</label>';
			echo wp_kses_post( $html );

		}

		/**
		 * FB Text Callback.
		 *
		 * @param array $args - Arguments.
		 */
		public static function wcap_fb_text_callback( $args ) {

			$saved_value = isset( $args[2] ) ? get_option( $args[2] ) : '';
			if ( isset( $args[2] ) ) {
				printf(
					'<input type="text" id="%1$s" name="%1$s" value="" readonly />',
					esc_html( $args[2] )
				);
			} else {
				print( "<input type='text' id='wcap_fb' name='wcap_fb' readonly />" );
			}

			$html = '<label for="$args[2]"> ' . $args[0] . '</label>';
			echo wp_kses_post( $html );
		}

		/**
		 * FB Dropdown Callback.
		 *
		 * @param array $args - Arguments.
		 */
		public static function wcap_fb_dropdown_callback( $args ) {

			$selected_value = isset( $args[1] ) ? get_option( $args[1], '' ) : '';
			$selected       = '';

			if ( is_array( $args ) && isset( $args[1] ) && isset( $args[2] ) ) {
				printf(
					'<select name="%1$s" id="%1$s" disabled>',
					esc_html( $args[1] )
				);
				$icon_array = $args[2];
			} else {
				print( "<select name='wcap_fb_user_icon' id='wcap_fb_user_icon' disabled>" );
				$icon_array = array(
					'small'  => 'Small',
					'medium' => 'Medium',
				);
			}

			foreach ( $icon_array as $key => $value ) {
				$selected = $selected_value === $key ? 'selected="selected"' : '';
				printf(
					'<option value="%s" %s >%s</option>',
					esc_html( $key ),
					esc_html( $selected ),
					esc_html( $value )
				);
			}
			print( '</select>' );
			$html = '<label for="$args[1]"> ' . $args[0] . '</label>';

			echo wp_kses_post( $html );

		}

		/**
		 * Guest Time cutoff callback.
		 *
		 * @param array $args - Arguments.
		 */
		public static function wcap_cart_abandoned_time_guest_callback( $args ) {

			$cart_abandoned_time_guest = get_option( 'ac_cart_abandoned_time_guest' );

			printf(
				'<input type="text" id="ac_cart_abandoned_time_guest" name="ac_cart_abandoned_time_guest" value="%s" readonly />',
				isset( $cart_abandoned_time_guest ) ? esc_attr( $cart_abandoned_time_guest ) : ''
			);

			$html = '<label for="ac_cart_abandoned_time_guest"> ' . $args[0] . '</label>';
			echo wp_kses_post( $html );
		}

		/**
		 * Disable Guest cart tracking.
		 *
		 * @param array $args - Arguments.
		 */
		public static function wcap_disable_guest_cart_email_callback( $args ) {

			$disable_guest_cart_email = get_option( 'ac_disable_guest_cart_email' );

			if ( isset( $disable_guest_cart_email ) && '' === $disable_guest_cart_email ) {
				$disable_guest_cart_email = 'off';
			}

			printf(
				'<input type="checkbox" id="ac_disable_guest_cart_email" name="ac_disable_guest_cart_email" value="on"
                ' . checked( 'on', $disable_guest_cart_email, false ) . ' readonly disabled/>'
			);

			$html = '<label for="ac_disable_guest_cart_email"> ' . $args[0] . '</label> <br> <div id ="wcap_atc_disable_msg" class="wcap_atc_disable_msg"></div>';
			echo wp_kses_post( $html );
		}

		/**
		 * Disable registered user cart capture.
		 *
		 * @param array $args - Arguments.
		 */
		public static function wcap_disable_logged_in_cart_email_callback( $args ) {

			$disable_logged_in_cart_email = get_option( 'ac_disable_logged_in_cart_email' );
			if ( isset( $disable_logged_in_cart_email ) && '' === $disable_logged_in_cart_email ) {
				$disable_logged_in_cart_email = 'off';
			}

			printf(
				'<input type="checkbox" id="ac_disable_logged_in_cart_email" name="ac_disable_logged_in_cart_email" value="on"
                ' . checked( 'on', $disable_logged_in_cart_email, false ) . ' readonly disabled/>'
			);

			$html = '<label for="ac_disable_logged_in_cart_email"> ' . $args[0] . '</label>';
			echo wp_kses_post( $html );

		}

		/**
		 * Capture email from URL callback.
		 *
		 * @param array $args - Arguments.
		 */
		public static function wcap_capture_email_address_from_url( $args ) {

			$ac_capture_email_address_from_url = get_option( 'ac_capture_email_address_from_url' );

			printf(
				'<input type="text" id="ac_capture_email_address_from_url" name="ac_capture_email_address_from_url" value="%s" readonly />',
				isset( $ac_capture_email_address_from_url ) ? esc_attr( $ac_capture_email_address_from_url ) : ''
			);

			$html = '<label for="ac_capture_email_address_from_url_label"> ' . $args[0] . '</label>';
			echo wp_kses_post( $html );

		}

		/**
		 * Product Image Size callback.
		 *
		 * @param array $args - Arguments.
		 */
		public static function wcap_product_image_size_callback( $args ) {

			$wcap_product_image_height = get_option( 'wcap_product_image_height' );
			$wcap_product_image_width  = get_option( 'wcap_product_image_width' );
			?>
			<input type="text" id="wcap_product_image_height" style="width:50px" name="wcap_product_image_height" value="<?php echo esc_html( $wcap_product_image_height ); ?>" readonly />
			<?php
			echo esc_html( 'x' );
			?>
			<input type="text" id="wcap_product_image_width" style="width:50px" name="wcap_product_image_width" value="<?php echo esc_html( $wcap_product_image_width ); ?>" readonly />
			<?php
			echo esc_html( ' px' );

			$html = '<label for="wcap_product_image_size"> ' . $args[0] . '</label>';
			echo wp_kses_post( $html );
		}

		/**
		 * Cron Job Section.
		 */
		public static function wcap_cron_job_callback() {}

		/**
		 * Cron Job callback.
		 *
		 * @param array $args - Arguments.
		 */
		public static function wcap_use_auto_cron_callback( $args ) {

			$enable_auto_cron = get_option( 'wcap_use_auto_cron', '' );
			if ( isset( $enable_auto_cron ) && '' === $enable_auto_cron ) {
				$enable_auto_cron = 'off';
			}

			printf(
				'<input type="checkbox" id="wcap_use_auto_cron" name="wcap_use_auto_cron" value="on"
                ' . checked( 'on', $enable_auto_cron, false ) . ' readonly disabled/>'
			);

			$html = '<label for="wcap_use_auto_cron_label"> ' . $args[0] . '</label>';
			echo wp_kses_post( $html );
		}

		/**
		 * Cron Duration callback.
		 *
		 * @param array $args - Arguments.
		 */
		public static function wcap_cron_time_duration_callback( $args ) {

			$wcap_cron_time_duration = get_option( 'wcap_cron_time_duration' );

			printf(
				'<input type="text" id="wcap_cron_time_duration" name="wcap_cron_time_duration" value="%s" readonly/>',
				isset( $wcap_cron_time_duration ) ? esc_attr( $wcap_cron_time_duration ) : ''
			);

			$html = '<label for="wcap_cron_time_duration"> ' . $args[0] . '</label>';
			echo wp_kses_post( $html );
		}

		/**
		 * Custom Restrict Section.
		 */
		public static function wcap_custom_restrict_callback() {}

		/**
		 * Restrict by IP.
		 *
		 * @param array $args - Argumemnts.
		 */
		public static function wcap_restrict_ip_address_callback( $args ) {

			printf(
				'<textarea rows="4" cols="50" id="wcap_restrict_ip_address" name="wcap_restrict_ip_address" placeholder="Add an IP address" readonly /></textarea>'
			);

			$html = '<label for="wcap_restrict_ip_address_label"> ' . $args[0] . '</label>';
			echo wp_kses_post( $html );
		}

		/**
		 * Restrict by Email.
		 *
		 * @param array $args - Arguments.
		 */
		public static function wcap_restrict_email_address_callback( $args ) {

			printf(
				'<textarea rows="4" cols="50" id="wcap_restrict_email_address" name="wcap_restrict_email_address" placeholder="Add an email address" readonly /></textarea>'
			);

			$html = '<label for="wcap_restrict_email_address_label"> ' . $args[0] . '</label>';
			echo wp_kses_post( $html );
		}

		/**
		 * Restrict by Domain.
		 *
		 * @param array $args - Arguments.
		 */
		public static function wcap_restrict_domain_address_callback( $args ) {

			printf(
				'<textarea rows="4" cols="50" id="wcap_restrict_domain_address" name="wcap_restrict_domain_address" placeholder="Add an email domain name (Ex. hotmail.com)" readonly/></textarea>'
			);

			$html = '<label for="wcap_restrict_domain_address_label"> ' . $args[0] . '</label>';
			echo wp_kses_post( $html );
		}

	} // end of class.

	$wcap_pro_settings_callbacks = new WCAP_Pro_Settings_Callbacks();

} // end if.
