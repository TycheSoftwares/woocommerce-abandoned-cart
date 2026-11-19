<?php
/**
 * It will Add all the Boilerplate component when we activate the plugin.
 *
 * @author  Tyche Softwares
 * @package Abandoned-Cart-Lite-for-WooCommerce/Admin/Component
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'Wcal_All_Component' ) ) {
	/**
	 * It will Add all the Boilerplate component when we activate the plugin.
	 */
	class Wcal_All_Component {

		/**
		 * It will Add all the Boilerplate component when we activate the plugin.
		 */
		public function __construct() {

			$is_admin = is_admin();

			if ( true === $is_admin ) {

				require_once 'component/woocommerce-check/ts-woo-active.php';

				require_once 'component/faq-support/ts-faq-support.php';
				require_once 'component/upgrade-to-pro/ts-upgrade-to-pro.php';
				require_once 'component/pro-notices-in-lite/ts-pro-notices.php';

				$wcal_plugin_name        = 'Abandoned Cart Lite for WooCommerce';
				$wcal_locale             = 'woocommerce-abandoned-cart';
				$wcal_file_name          = 'woocommerce-abandoned-cart/woocommerce-ac.php';
				$wcal_plugin_prefix      = 'wcal';
				$wcal_lite_plugin_prefix = 'wcal';
				$wcal_plugin_folder_name = 'woocommerce-abandoned-cart/';
				$wcal_plugin_dir_name    = dirname( untrailingslashit( plugin_dir_path( __FILE__ ) ) ) . '/woocommerce-ac.php';
				$wcal_plugin_url         = dirname( untrailingslashit( plugins_url( '/', __FILE__ ) ) );

				$wcal_get_previous_version = get_option( 'wcal_previous_version' );

				$wcal_blog_post_link = 'https://www.tychesoftwares.com/abandoned-cart-lite-usage-tracking/';

				$wcal_plugins_page  = 'admin.php?page=woocommerce_ac_page';
				$wcal_plugin_slug   = 'woocommerce_ac_page';
				$wcal_pro_file_name = 'woocommerce-abandon-cart-pro/woocommerce-ac.php';

				$wcal_settings_page    = 'admin.php?page=woocommerce_ac_page&action=emailsettings';
				$wcal_setting_add_on   = 'woocommerce_ac_page';
				$wcal_setting_section  = 'ac_lite_general_settings_section';
				$wcal_register_setting = 'woocommerce_ac_settings';

				new Wcal_TS_Woo_Active( $wcal_plugin_name, $wcal_file_name, $wcal_locale );

				$ts_pro_faq = self::wcal_get_faq();
				new Wcal_TS_Faq_Support( $wcal_plugin_name, $wcal_plugin_prefix, $wcal_plugins_page, $wcal_locale, $wcal_plugin_folder_name, $wcal_plugin_slug, $ts_pro_faq );

				new Ts_Upgrade_To_Pro_AC( $wcal_plugin_name, $wcal_plugin_prefix, $wcal_plugins_page, $wcal_locale, $wcal_plugin_folder_name, $wcal_plugin_slug, $ts_pro_faq );
			}
		}

		/**
		 * It will contain all the FAQ which need to be display on the FAQ page.
		 *
		 * @return array $ts_faq All questions and answers.
		 */
		public static function wcal_get_faq() {

			$ts_faq = array();

			$ts_faq = array(
				1  => array(
					'question' => 'What qualifies as an abandoned cart?',
					'answer'   => 'A cart is deemed abandoned when a user adds items but leaves the website without completing the purchase. We capture email addresses in real-time during checkout for guest users and use logged-in users emails from their profiles as soon as they add a product to the shopping cart. The cart is marked abandoned once a designated cut-off time has elapsed.',
				),
				2  => array(
					'question' => 'How can I check if reminder emails for abandoned carts have been sent?',
					'answer'   => 'To verify, you can click the "View Order" link under the Abandoned Orders tab for details on specific emails.',
				),
				3  => array(
					'question' => 'Why aren\'t my abandoned cart reminder emails being sent?',
					'answer'   => 'Ensure you have at least one active email template; only active templates trigger abandoned cart recovery. If active and still not sending, check out our troubleshooting guide <a href="https://www.tychesoftwares.com/docs/docs/abandoned-cart-pro-for-woocommerce-new/templates/wp-cron/" target="_blank">here.</a>"',
				),
				4  => array(
					'question' => 'Can I hide tax from product prices in abandoned orders?',
					'answer'   => 'Our plugin doesn’t directly hide tax, but you can use this below custom code in your theme’s "functions.php" file to hide tax details:',
				),
				5  => array(
					'question' => 'Can I delete all abandoned cart records at once?',
					'answer'   => 'Yes, you can use the "Bulk actions" dropdown on the Abandoned Orders tab to delete all carts. You can also delete only the visitor, registered, or guest carts using that option.',
				),
				6  => array(
					'question' => 'How do I stop tracking visitor carts?',
					'answer'   => 'You can disable the "Start tracking from Cart Page" setting present at Abandoned Carts -> Settings -> General Settings to stop capturing the visitor carts where no email address is entered.',
				),
				7  => array(
					'question' => 'Are Pending and Failed orders treated as abandoned?',
					'answer'   => 'No, orders that are Pending Payment or Failed are not considered abandoned, and no reminder emails will be sent.',
				),
				8  => array(
					'question' => 'How can I translate the {{products.cart}} tag in emails?',
					'answer'   => 'You can translate the strings of our plugin to another language using the .po and .mo files we provide in our plugin. To translate the abandoned cart email, create ".po" and ".mo" files using PoEdit translation editor:',
				),
				9  => array(
					'question' => 'Why are there so many logged carts?',
					'answer'   => 'If you notice a high number of logged carts without names and email addresses, it may be due to bots. In that case, please update your <a href="https://www.tychesoftwares.com/docs/woocommerce-abandoned-cart-lite/track-only-genuine-visitor-carts/" target="_blank">robots.txt</a> file to prevent bot interactions with your cart.',
				),
				10 => array(
					'question' => 'Is your plugin GDPR compliant?',
					'answer'   => 'Yes, our Abandoned Cart Lite plugin complies with GDPR. We collect user data only with consent, and all data stays on your site, accessible under the Abandoned Orders tab. GDPR settings can be found in the plugin\'s General Settings.',
				),
				11 => array(
					'question' => 'Can I offer discounts in reminder emails?',
					'answer'   => 'Yes, the Lite version includes a Coupon Code feature, allowing you to offer discounts in reminder emails. You will find the Coupon settings in the Email template.',
				),
				12 => array(
					'question' => 'Do I need to manually set up a cron job in cPanel?',
					'answer'   => 'No, you don\'t need to set up the cron job manually in cPanel. We have introduced the Action Scheduler Library instead of WP-Cron to send reminders automatically. Action Scheduler will reduce the dependency on the WP-Cron and if the WP-Cron is disabled, it will still run the actions on admin page requests. You can check out <a href="https://www.tychesoftwares.com/moving-to-the-action-scheduler-library/" target="_blank">this</a> help guide to know more about the Action Scheduler Library.',
				),

			);

			return $ts_faq;
		}
	}
	$wcal_all_component = new Wcal_All_Component();
}
