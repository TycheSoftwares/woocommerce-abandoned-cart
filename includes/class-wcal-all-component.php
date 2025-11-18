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
		 * It will Display the notices in the admin dashboard for the pro vesion of the plugin.
		 *
		 * @return array $ts_pro_notices All text of the notices
		 */
		public static function wcal_get_notice_text() {
			$ts_pro_notices = array();

			$wcal_ac_pro_link = 'https://www.tychesoftwares.com/store/premium-plugins/woocommerce-abandoned-cart-pro/?utm_source=wpnotice&utm_medium=first&utm_campaign=AbandonedCartLitePlugin';
			$wcal_pro_diff    = 'https://www.tychesoftwares.com/differences-between-pro-and-lite-versions-of-abandoned-cart-for-woocommerce-plugin/';
			/* translators: %1$s Link to Differences article, %2$s link to pro version */
			$message_first = wp_kses_post( sprintf( __( 'Now that you are all set with the Lite version, you can upgrade to Pro version to take your abandoned cart recovery to the next level. You can capture  customer’s email address when they click Add to Cart, get access to 11 unique, fully responsive email templates, send text messages for recovery & <strong><a target="_blank" href= "%1$s">much more</a></strong>. <strong><a target="_blank" href= "%2$s">Purchase now</a></strong>.', 'woocommerce-abandoned-cart' ), $wcal_pro_diff, $wcal_ac_pro_link ) );

			$wcal_ac_pro_link = 'https://www.tychesoftwares.com/store/premium-plugins/woocommerce-abandoned-cart-pro/?utm_source=wpnotice&utm_medium=second&utm_campaign=AbandonedCartLitePlugin';
			/* translators: %s Link to Abandoned Cart Pro */
			$message_two = wp_kses_post( sprintf( __( 'Boost your sales by recovering up to 60% of the abandoned carts with our Abandoned Cart Pro for WooCommerce plugin. You can capture customer email addresses right when they click the Add To Cart button. <strong><a target="_blank" href= "%s"> Grab your copy of Abandon Cart Pro plugin now!</a></strong>', 'woocommerce-abandoned-cart' ), $wcal_ac_pro_link ) );

			$wcal_ac_pro_link = 'https://www.tychesoftwares.com/store/premium-plugins/woocommerce-abandoned-cart-pro/?utm_source=wpnotice&utm_medium=third&utm_campaign=AbandonedCartLitePlugin';
			/* translators: %s Link to Abandoned Cart Pro */
			$message_three = wp_kses_post( sprintf( __( 'Don\'t loose your sales to abandoned carts. Use our Abandon Cart Pro plugin & start recovering your lost sales in less then 60 seconds. <strong><a target="_blank" href= "%s">Grab it now!</a></strong>.', 'woocommerce-abandoned-cart' ), $wcal_ac_pro_link ) );

			$wcal_ac_pro_link = 'https://www.tychesoftwares.com/store/premium-plugins/woocommerce-abandoned-cart-pro/?utm_source=wpnotice&utm_medium=fourth&utm_campaign=AbandonedCartLitePlugin';
			/* translators: %s Link to Abandoned Cart Pro */
			$message_four = wp_kses_post( sprintf( __( 'Send Abandoned Cart reminders that actually convert. Take advantage of our fully responsive email templates designed specially with an intent to trigger conversion. <strong><a target="_blank" href= "%s">Purchase now</a></strong>.', 'woocommerce-abandoned-cart' ), $wcal_ac_pro_link ) );

			$wcal_ac_pro_link = 'https://www.tychesoftwares.com/store/premium-plugins/woocommerce-abandoned-cart-pro/?utm_source=wpnotice&utm_medium=fifth&utm_campaign=AbandonedCartLitePlugin';
			/* translators: %s Link to Abandoned Cart Pro */
			$message_five = wp_kses_post(
				sprintf(
					// translators: Pro version link.
					__(
						'Increase your store sales by recovering your abandoned carts for just $119. No profit sharing, no monthly fees. Our Abandoned Cart Pro plugin comes with a 30 day money back guarantee as well. :) Use coupon code ACPRO20 & save $24!<br>
            <strong><a target="_blank" href= "%s">Grab your copy now!</a></strong>',
						'woocommerce-abandoned-cart'
					),
					$wcal_ac_pro_link
				)
			);

			$_link = 'https://www.tychesoftwares.com/store/premium-plugins/order-delivery-date-for-woocommerce-pro-21/?utm_source=wpnotice&utm_medium=sixth&utm_campaign=AbandonedCartLitePlugin';
			/* translators: %s Link to Order Delivery Date Pro */
			$message_six = wp_kses_post( sprintf( __( 'Reduce cart abandonment rate by 57% with our Order Delivery Date Pro WooCommerce plugin. You can Create Delivery Settings by Shipping Zones & Shipping Classes. <br>Use discount code "ORDPRO20" and grab 20% discount on the purchase of the plugin. The discount code is valid only for the first 20 customers. <strong><a target="_blank" href= "%s">Purchase now</a></strong>', 'woocommerce-abandoned-cart' ), $_link ) );

			$_link = 'https://www.tychesoftwares.com/store/premium-plugins/product-delivery-date-pro-for-woocommerce/?utm_source=wpnotice&utm_medium=seventh&utm_campaign=AbandonedCartLitePlugin';
			/* translators: %s Link to Order Delivery Date Pro */
			$message_seven = wp_kses_post(
				sprintf(
					// translators: Order Delivery Date Pro link.
					__(
						'Allow your customers to select the Delivery Date on Single Product Page using our Product Delivery Date pro for WooCommerce Plugin. <br>
            <strong><a target="_blank" href= "%s">Shop now</a></strong> & be one of the 20 customers to get 20% discount on the plugin price. Use the code "PRDPRO20". Hurry!!',
						'woocommerce-abandoned-cart'
					),
					$_link
				)
			);

			$_link = 'https://www.tychesoftwares.com/store/premium-plugins/woocommerce-booking-plugin/?utm_source=wpnotice&utm_medium=eight&utm_campaign=AbandonedCartLitePlugin';
			/* translators: %s Link to WooCommerce Booking Plugin */
			$message_eight = wp_kses_post( sprintf( __( 'Allow your customers to book an appointment or rent an apartment with our Booking and Appointment for WooCommerce plugin. You can also sell your product as a resource or integrate with a few Vendor plugins. <br>Shop now & Save 20% on the plugin with the code "BKAP20". Only for first 20 customers. <strong><a target="_blank" href= "%s">Have it now!</a></strong>', 'woocommerce-abandoned-cart' ), $_link ) );

			$_link = 'https://www.tychesoftwares.com/store/premium-plugins/deposits-for-woocommerce/?utm_source=wpnotice&utm_medium=eight&utm_campaign=AbandonedCartLitePlugin';
			/* translators: %s Link to Deposits for WooCommerce */
			$message_nine = wp_kses_post(
				sprintf(
					// translators: Deposits for WC pro plugin link.
					__(
						' Allow your customers to pay deposits on products using our Deposits for WooCommerce plugin. <br>
            <strong><a target="_blank" href= "%s">Purchase now</a></strong> & Grab 20% discount with the code "DFWP20". The discount code is valid only for the first 20 customers.',
						'woocommerce-abandoned-cart'
					),
					$_link
				)
			);

			$ts_pro_notices = array(
				1 => $message_first,
				2 => $message_two,
				3 => $message_three,
				4 => $message_four,
				5 => $message_five,
				6 => $message_six,
				7 => $message_seven,
				8 => $message_eight,
				9 => $message_nine,
			);

			return $ts_pro_notices;
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
