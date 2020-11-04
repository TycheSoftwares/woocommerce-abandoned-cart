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

				require_once 'component/tracking-data/ts-tracking.php';
				require_once 'component/deactivate-survey-popup/class-ts-deactivation.php';

				require_once 'component/faq-support/ts-faq-support.php';
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

				new Wcal_TS_tracking( $wcal_plugin_prefix, $wcal_plugin_name, $wcal_blog_post_link, $wcal_locale, $wcal_plugin_url, $wcal_settings_page, $wcal_setting_add_on, $wcal_setting_section, $wcal_register_setting );

				new Wcal_TS_Tracker( $wcal_plugin_prefix, $wcal_plugin_name );

				$wcal_deativate = new Wcal_TS_deactivate();
				$wcal_deativate->init( $wcal_file_name, $wcal_plugin_name );

				$ts_pro_faq = self::wcal_get_faq();
				new Wcal_TS_Faq_Support( $wcal_plugin_name, $wcal_plugin_prefix, $wcal_plugins_page, $wcal_locale, $wcal_plugin_folder_name, $wcal_plugin_slug, $ts_pro_faq );

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
					'question' => 'Why abandoned cart reminder emails are not being sent?',
					'answer'   => 'Please ensure you have at least one Email template "Active". As only active email templates are sent to recover the abandoned carts.
                        <br/><br/>
                        For sending the abandoned cart notification emails automatically, we use WP-Cron. If you have Email templates activated and still notification are not sent, then you can debug the issue by following this <a href = "https://www.tychesoftwares.com/docs/docs/abandoned-cart-pro-for-woocommerce/wp_alternate_cron/?utm_source=userwebsite&utm_medium=link&utm_campaign=AbandonedCartProFAQTab" target="_blank" >post</a>.',
				),
				2  => array(
					'question' => 'How is the email address of the customers captured?',
					'answer'   => 'Our plugin captures visitor emails in real-time as they are typing it in to the email address field on the checkout page, so you don\'t need to worry about them changing their mind at the last second.
                        <br/><br/>
                        When logged-in user add the product to the cart we capture the email address from the user’s profile.',
				),
				3  => array(
					'question' => 'I want to know if it is possible to exclude tax from product price for abandoned order.',
					'answer'   => 'Currently it is not possible to exclude tax from the product price from the abandoned order view if tax is applicable for the product.',
				),
				4  => array(
					'question' => 'Is it possible to delete thousands of or all abandoned cart records at a once?',
					'answer'   => 'No, it is not possible to delete thousands of abandoned cart records in bulk. Our plugin does have the Bulk action functionality. But you can delete upto 30 Abandoned Carts in bulk. You can run a SQL query in the Database to delete those carts. For detailed steps please contact us via Support.',
				),
				5  => array(
					'question' => 'Is there any way to not capture the visitors carts?',
					'answer'   => 'You can uncheck the “Start tracking from Cart Page” setting under the General Settings menu of the Settings tab.',
				),
				6  => array(
					'question' => 'How can I know that abandoned cart reminders are being sent to customers?',
					'answer'   => 'It is not possible in the Abandoned Cart LITE plugin to check the records of the Abandoned Cart Reminder email whether email notifications are being sent or not. However you can upgrade to PRO version of our plugin to enable this feature.',
				),
				7  => array(
					'question' => 'Is there an alternate way to send the abandon cart emails automatically at regular intervals? As the WP-Cron is not running on my site.',
					'answer'   => 'Yes, you can setup a manual cron in your server’s administration panel.
                        <br/><br/>
                        For example, if you are using cPanel, it has a section Named as "Cron Jobs" which allows you to create the cron job.
                        <br/><br/>
                        /usr/bin/wget -q -c {your_site_path}/wp-content/plugins/woocommerce-abandoned-cart/cron/class-wcal-cron.php
                        <br/><br/>
                        You can refer to this document for creating a <a href="https://documentation.cpanel.net/display/68Docs/Cron+Jobs">cron job</a> in cPanel.',
				),
				8  => array(
					'question' => 'Does the plugin consider the cart as abandoned for Pending and Failed order status?',
					'answer'   => 'No, our plugin does not consider such carts (Pending Payment and Failed orders) as abandoned. It will not send the abandoned cart reminder email to the customers if they fail to proceed with the payment.',
				),
				9  => array(
					'question' => 'How can we translate the strings of {{products.cart}} merge tag in the email?',
					'answer'   => 'To translate the strings, you need to generate ".po" and ".mo" files in your respective language. These files then need to be added to the following path: "woocommerce-abandoned-cart/i18n/languages"',
				),
				10 => array(
					'question' => 'There was a problem creating an email template on Multisite.',
					'answer'   => 'On Multisite, if you have activated the plugin from Network site then please deactivate it and activate the Abandoned Cart Lite plugin from an Individual site. So, one default email template will be created on the activation of the plugin and you can create new email template.',
				),
				11 => array(
					'question' => 'Abnormal amount of carts are being logged by the plugin.',
					'answer'   => "This might be due to a bot executing 'Add to Cart' urls on the site. Adding a few lines in the robots.txt file can stop the bots from actually adding products to the cart.
                    Please follow the instructions mentioned <a href='https://www.tychesoftwares.com/docs/docs/abandoned-cart-for-woocommerce-lite/track-only-genuine-visitor-carts/' target='_blank'>here.</a>",
				),
			);

			return $ts_faq;
		}
	}
	$wcal_all_component = new Wcal_All_Component();
}
