<?php
/**
 * Display all the settings in PRO
 *
 * @package Abandoned-Cart-Pro-for-WooCommerce/Admin/Settings
 * @since 2.4
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WCAP_Pro_Settings' ) ) {

	/**
	 * AC pro Settings Class.
	 */
	class WCAP_Pro_Settings {

		/**
		 * Show settings with the upgrade to pro modal.
		 *
		 * @param string $settings File name.
		 */
		public static function wcap_show_settings_modal( $settings ) {
			ob_start();
			wc_get_template(
				$settings,
				array(),
				'woocommerce-abandoned-cart',
				WCAL_PLUGIN_UPGRADE_TO_PRO_TEMPLATE_PATH
			);
			echo ob_get_clean(); // phpcs:ignore.
		}

		/**
		 * ATC Settings.
		 */
		public static function wcap_atc_settings() {
			self::wcap_show_settings_modal( 'ac-lite-atc-settings-html.php' );
		}

		/**
		 * FB Settings for AC Pro.
		 */
		public static function wcap_fb_settings() {
			self::wcap_show_settings_modal( 'ac-lite-fb-settings-html.php' );
		}

		/**
		 * Connector tab.
		 *
		 * @since 5.12.0
		 */
		public static function wcap_connectors() {
			self::wcap_show_settings_modal( 'ac-lite-connectors-settings-html.php' );
		}

		/**
		 * SMS Settings for AC Pro
		 */
		public static function wcap_sms_settings() {
			self::wcap_show_settings_modal( 'ac-lite-sms-settings-html.php' );
		}
	} // end of class.

	$wcap_pro_settings = new WCAP_Pro_Settings();

} // end if.
