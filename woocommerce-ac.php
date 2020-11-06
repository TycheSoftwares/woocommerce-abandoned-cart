<?php
/**
 * Plugin Name: Abandoned Cart Lite for WooCommerce
 * Plugin URI: http://www.tychesoftwares.com/store/premium-plugins/woocommerce-abandoned-cart-pro
 * Description: This plugin captures abandoned carts by logged-in users & emails them about it. <strong><a href="http://www.tychesoftwares.com/store/premium-plugins/woocommerce-abandoned-cart-pro">Click here to get the PRO Version.</a></strong>
 * Version: 5.8.3
 * Author: Tyche Softwares
 * Author URI: http://www.tychesoftwares.com/
 * Text Domain: woocommerce-abandoned-cart
 * Domain Path: /i18n/languages/
 * Requires PHP: 5.6
 * WC requires at least: 3.0.0
 * WC tested up to: 4.6.2
 *
 * @package Abandoned-Cart-Lite-for-WooCommerce
 */

require_once 'class-wcal-update.php';
require_once 'includes/class-wcal-guest-ac.php';
require_once 'includes/class-wcal-default-template-settings.php';
require_once 'includes/class-wcal-delete-handler.php';
require_once 'includes/classes/class-wcal-aes.php';
require_once 'includes/classes/class-wcal-aes-counter.php';
require_once 'includes/class-wcal-common.php';

require_once 'includes/class-wcal-admin-notice.php';
require_once 'includes/class-wcal-tracking-msg.php';
require_once 'includes/admin/class-wcal-personal-data-eraser.php';
require_once 'includes/admin/class-wcal-personal-data-export.php';
require_once 'includes/admin/class-wcal-abandoned-cart-details.php';

require_once 'includes/admin/class-wcap-pro-settings.php';
require_once 'includes/admin/class-wcap-pro-settings-callbacks.php';
require_once 'includes/admin/class-wcap-add-cart-popup-modal.php';

load_plugin_textdomain( 'woocommerce-abandoned-cart', false, basename( dirname( __FILE__ ) ) . '/i18n/languages' );

/**
 * Schedule an action to delete old carts once a day
 *
 * @since 5.1
 * @package Abandoned-Cart-Lite-for-WooCommerce/Cron
 */
if ( ! wp_next_scheduled( 'wcal_clear_carts' ) ) {
	wp_schedule_event( time(), 'daily', 'wcal_clear_carts' );
}

/**
 * Main class
 */
if ( ! class_exists( 'woocommerce_abandon_cart_lite' ) ) {


	/**
	 * It will add the hooks, filters, menu and the variables and all the necessary actions for the plguins which will be used
	 * all over the plugin.
	 *
	 * @since 1.0
	 * @package Abandoned-Cart-Lite-for-WooCommerce/Core
	 */
	class woocommerce_abandon_cart_lite {
		/**
		 * Duration One hour.
		 *
		 * @var int
		 */
		public $one_hour;
		/**
		 * Duration three hours.
		 *
		 * @var int
		 */
		public $three_hours;
		/**
		 * Duration six hours.
		 *
		 * @var int
		 */
		public $six_hours;
		/**
		 * Duration 12 hours.
		 *
		 * @var int
		 */
		public $twelve_hours;
		/**
		 * Duration One day.
		 *
		 * @var int
		 */
		public $one_day;
		/**
		 * Duration One week.
		 *
		 * @var int
		 */
		public $one_week;
		/**
		 * Duration range select
		 *
		 * @var array
		 */
		public $duration_range_select;
		/**
		 * Duration start & end dates
		 *
		 * @var array
		 */
		public $start_end_dates;
		/**
		 * The constructor will add the hooks, filters and the variable which will be used all over the plugin.
		 *
		 * @since 1.0
		 */
		public function __construct() {
			if ( ! defined( 'WCAL_PLUGIN_URL' ) ) {
				define( 'WCAL_PLUGIN_URL', untrailingslashit( plugins_url( '/', __FILE__ ) ) );
			}

			if ( ! defined( 'WCAL_PLUGIN_VERSION' ) ) {
				define( 'WCAL_PLUGIN_VERSION', '5.8.3' );
			}
			$this->one_hour              = 60 * 60;
			$this->three_hours           = 3 * $this->one_hour;
			$this->six_hours             = 6 * $this->one_hour;
			$this->twelve_hours          = 12 * $this->one_hour;
			$this->one_day               = 24 * $this->one_hour;
			$this->one_week              = 7 * $this->one_day;
			$this->duration_range_select = array(
				'yesterday'      => 'Yesterday',
				'today'          => 'Today',
				'last_seven'     => 'Last 7 days',
				'last_fifteen'   => 'Last 15 days',
				'last_thirty'    => 'Last 30 days',
				'last_ninety'    => 'Last 90 days',
				'last_year_days' => 'Last 365',
			);

			$this->start_end_dates = array(
				'yesterday'      => array(
					'start_date' => date( 'd M Y', ( current_time( 'timestamp' ) - 24 * 60 * 60 ) ), // phpcs:ignore
					'end_date'   => date( 'd M Y', ( current_time( 'timestamp' ) - 7 * 24 * 60 * 60 ) ), // phpcs:ignore
				),
				'today'          => array(
					'start_date' => date( 'd M Y', ( current_time( 'timestamp' ) ) ), // phpcs:ignore
					'end_date'   => date( 'd M Y', ( current_time( 'timestamp' ) ) ), // phpcs:ignore
				),
				'last_seven'     => array(
					'start_date' => date( 'd M Y', ( current_time( 'timestamp' ) - 7 * 24 * 60 * 60 ) ), // phpcs:ignore
					'end_date'   => date( 'd M Y', ( current_time( 'timestamp' ) ) ), // phpcs:ignore
				),
				'last_fifteen'   => array(
					'start_date' => date( 'd M Y', ( current_time( 'timestamp' ) - 15 * 24 * 60 * 60 ) ), // phpcs:ignore
					'end_date'   => date( 'd M Y', ( current_time( 'timestamp' ) ) ), // phpcs:ignore
				),
				'last_thirty'    => array(
					'start_date' => date( 'd M Y', ( current_time( 'timestamp' ) - 30 * 24 * 60 * 60 ) ), // phpcs:ignore
					'end_date'   => date( 'd M Y', ( current_time( 'timestamp' ) ) ), // phpcs:ignore
				),
				'last_ninety'    => array(
					'start_date' => date( 'd M Y', ( current_time( 'timestamp' ) - 90 * 24 * 60 * 60 ) ), // phpcs:ignore
					'end_date'   => date( 'd M Y', ( current_time( 'timestamp' ) ) ), // phpcs:ignore
				),
				'last_year_days' => array(
					'start_date' => date( 'd M Y', ( current_time( 'timestamp' ) - 365 * 24 * 60 * 60 ) ), // phpcs:ignore
					'end_date'   => date( 'd M Y', ( current_time( 'timestamp' ) ) ), // phpcs:ignore
				),
			);

			// Initialize settings.
			register_activation_hook( __FILE__, array( &$this, 'wcal_activate' ) );
			register_deactivation_hook( __FILE__, array( &$this, 'wcal_deactivate' ) );

			// Action Scheduler for Cron.
			require_once 'includes/libraries/action-scheduler/action-scheduler.php';
			add_action( 'init', array( &$this, 'wcal_add_scheduled_action' ) );
			require_once 'cron/class-wcal-cron.php';
			require_once 'includes/class-wcal-process-base.php';

			// WordPress Administration Menu.
			add_action( 'admin_menu', array( &$this, 'wcal_admin_menu' ) );

			// Actions to be done on cart update.
			add_action( 'woocommerce_add_to_cart', array( &$this, 'wcal_store_cart_timestamp' ), PHP_INT_MAX );
			add_action( 'woocommerce_cart_item_removed', array( &$this, 'wcal_store_cart_timestamp' ), PHP_INT_MAX );
			add_action( 'woocommerce_cart_item_restored', array( &$this, 'wcal_store_cart_timestamp' ), PHP_INT_MAX );
			add_action( 'woocommerce_after_cart_item_quantity_update', array( &$this, 'wcal_store_cart_timestamp' ), PHP_INT_MAX );
			add_action( 'woocommerce_calculate_totals', array( &$this, 'wcal_store_cart_timestamp' ), PHP_INT_MAX );

			add_filter( 'wcal_block_crawlers', array( &$this, 'wcal_detect_crawlers' ), 10, 1 );

			add_action( 'admin_init', array( &$this, 'wcal_action_admin_init' ) );

			// Update the options as per settings API.
			add_action( 'admin_init', array( 'Wcal_Update', 'wcal_schedule_update_action' ) );
			add_action( 'wcal_update_db', array( 'Wcal_Update', 'wcal_update_db_check' ) );

			// WordPress settings API.
			add_action( 'admin_init', array( &$this, 'wcal_initialize_plugin_options' ) );

			// Language Translation.
			add_action( 'init', array( &$this, 'wcal_update_po_file' ) );

			add_action( 'init', array( &$this, 'wcal_add_component_file' ) );

			// track links.
			add_filter( 'template_include', array( &$this, 'wcal_email_track_links' ), 99, 1 );

			// It will used to unsubcribe the emails.
			add_action( 'template_include', array( &$this, 'wcal_email_unsubscribe' ), 99, 1 );

			add_action( 'admin_enqueue_scripts', array( &$this, 'wcal_enqueue_scripts_js' ) );
			add_action( 'admin_enqueue_scripts', array( &$this, 'wcal_enqueue_scripts_css' ) );
			// delete abandoned order after X number of days.
			if ( class_exists( 'Wcal_Delete_Handler' ) ) {
				add_action( 'wcal_clear_carts', array( 'Wcal_Delete_Handler', 'wcal_delete_abandoned_carts_after_x_days' ) );
			}

			if ( is_admin() ) {
				// Load "admin-only" scripts here.
				add_action( 'admin_head', array( &$this, 'wcal_action_send_preview' ) );
				add_action( 'wp_ajax_wcal_preview_email_sent', array( &$this, 'wcal_preview_email_sent' ) );
				add_action( 'wp_ajax_wcal_toggle_template_status', array( &$this, 'wcal_toggle_template_status' ) );
				add_action( 'wp_ajax_wcal_abandoned_cart_info', array( &$this, 'wcal_abandoned_cart_info' ) );
				add_action( 'wp_ajax_wcal_dismiss_admin_notice', array( &$this, 'wcal_dismiss_admin_notice' ) );

				add_filter( 'ts_tracker_data', array( 'wcal_common', 'ts_add_plugin_tracking_data' ), 10, 1 );
				add_filter( 'ts_tracker_opt_out_data', array( 'wcal_common', 'ts_get_data_for_opt_out' ), 10, 1 );
				add_filter( 'ts_deativate_plugin_questions', array( &$this, 'wcal_deactivate_add_questions' ), 10, 1 );
			}

			// Plugin Settings link in WP->Plugins page.
			$plugin = plugin_basename( __FILE__ );
			add_action( "plugin_action_links_$plugin", array( &$this, 'wcal_settings_link' ) );

			add_action( 'admin_init', array( $this, 'wcal_preview_emails' ) );
			add_action( 'init', array( $this, 'wcal_app_output_buffer' ) );

			add_filter( 'admin_footer_text', array( $this, 'wcal_admin_footer_text' ), 1 );

			add_action( 'admin_notices', array( 'Wcal_Admin_Notice', 'wcal_show_db_update_notice' ) );

			include_once 'includes/frontend/class-wcal-frontend.php';
		}

		/**
		 * Add Recurring Scheduled Action.
		 */
		public static function wcal_add_scheduled_action() {
			if ( false === as_next_scheduled_action( 'woocommerce_ac_send_email_action' ) ) {
				wp_clear_scheduled_hook( 'woocommerce_ac_send_email_action' ); // Remove the cron job is present.
				as_schedule_recurring_action( time() + 60, 900, 'woocommerce_ac_send_email_action' ); // Schedule recurring action.
			}
		}

		/**
		 * Add Settings link to WP->Plugins page.
		 *
		 * @param array $links - Links to be displayed.
		 * @return array $links - Includes custom links.
		 * @since 5.3.0
		 */
		public static function wcal_settings_link( $links ) {
			$settings_link = '<a href="admin.php?page=woocommerce_ac_page&action=emailsettings">' . __( 'Settings', 'woocommerce-abandoned-cart' ) . '</a>';
			array_push( $links, $settings_link );
			return $links;
		}

		/**
		 * It will load the boilerplate components file. In this file we have included all boilerplate files.
		 * We need to inlcude this file after the init hook.
		 *
		 * @hook init
		 */
		public static function wcal_add_component_file() {
			if ( is_admin() ) {
				require_once 'includes/class-wcal-all-component.php';

			}
		}
		/**
		 * It will add the Questions while admin deactivate the plugin.
		 *
		 * @hook ts_deativate_plugin_questions.
		 * @param array $wcal_add_questions Blank array.
		 * @return array $wcal_add_questions List of all questions.
		 */
		public static function wcal_deactivate_add_questions( $wcal_add_questions ) {

			$wcal_add_questions = array(
				0 => array(
					'id'                => 4,
					'text'              => __( 'Emails are not being sent to customers.', 'woocommerce-abandoned-cart' ),
					'input_type'        => '',
					'input_placeholder' => '',
				),
				1 => array(
					'id'                => 5,
					'text'              => __( 'Capturing of cart and other information was not satisfactory.', 'woocommerce-abandoned-cart' ),
					'input_type'        => '',
					'input_placeholder' => '',
				),
				2 => array(
					'id'                => 6,
					'text'              => __( 'I cannot see abandoned cart reminder emails records.', 'woocommerce-abandoned-cart' ),
					'input_type'        => '',
					'input_placeholder' => '',
				),
				3 => array(
					'id'                => 7,
					'text'              => __( 'I want to upgrade the plugin to the PRO version.', 'woocommerce-abandoned-cart' ),
					'input_type'        => '',
					'input_placeholder' => '',
				),

			);
			return $wcal_add_questions;
		}

		/**
		 * Replace Merge tags in email previews.
		 *
		 * @param string $content - Email content.
		 * @return string $content - content with cart data.
		 * @since 5.8
		 */
		public function replace_mergetags( $content ) {

			$admin_args = array(
				'role'   => 'administrator',
				'fields' => array( 'id' ),
			);

			$admin_usr   = get_users( $admin_args );
			$uid         = $admin_usr[0]->id;
			$admin_phone = get_user_meta( $uid, 'billing_phone', true );

			$wcal_price       = wc_price( '150' );
			$wcal_total_price = wc_price( '300' );

			$allowed_html                  = array(
				'span' => array(
					'class' => array(),
				),
			);
			$spectre_img_src               = esc_url( plugins_url( '/assets/images/spectre.jpg', __FILE__ ) );
			$replace_data['products_cart'] = "<table border='0' width='100%' cellspacing='0' cellpadding='0'><b>Your Shopping Cart</b>
			<tbody>
				<tr>
					<td style='background-color: #666666; color: #ffffff; text-align: center; font-size: 13px; text-transform: uppercase; padding: 5px;' align='center' bgcolor='#666666'></td>

					<td style='background-color: #666666; color: #ffffff; text-align: center; font-size: 13px; text-transform: uppercase; padding: 5px;' align='center' bgcolor='#666666'>Product</td>

					<td style='background-color: #666666; color: #ffffff; text-align: center; font-size: 13px; text-transform: uppercase; padding: 5px;' align='center' bgcolor='#666666'>Price</td>

					<td style='background-color: #666666; color: #ffffff; text-align: center; font-size: 13px; text-transform: uppercase; padding: 5px;' align='center' bgcolor='#666666'>Quantity</td>

					<td style='background-color: #666666; color: #ffffff; text-align: center; font-size: 13px; text-transform: uppercase; padding: 5px;' align='center' bgcolor='#666666'>Total</td>

				</tr>
				<tr style='background-color:#f4f5f4;'>
					<td><img src = '$spectre_img_src' height='40px' width='40px'></td><td>Spectre</td><td>" . wp_kses( $wcal_price, $allowed_html ) . '</td><td>2</td><td>' . wp_kses( $wcal_total_price, $allowed_html ) . '</td>
				</tr>
				<tr>
					<td>&nbsp;</td>
					<td>&nbsp;</td>
					<td>&nbsp;</td>
					<th>Cart Total:</th>
					<td>' . wp_kses( $wcal_total_price, $allowed_html ) . '</td>
				</tr>

			</tbody>
			</table>';
			$replace_data['admin_phone']   = $admin_phone;
			$replace_data['site_title']    = get_bloginfo( 'name' );
			$replace_data['site_url']      = get_option( 'siteurl' );

			$content = str_ireplace( '{{products.cart}}', $replace_data['products_cart'], $content );
			$content = str_ireplace( '{{admin.phone}}', $replace_data['admin_phone'], $content );
			$content = str_ireplace( '{{customer.firstname}}', 'John', $content );
			$content = str_ireplace( '{{customer.lastname}}', 'Doe', $content );
			$content = str_ireplace( '{{customer.fullname}}', 'John Doe', $content );
			$content = str_ireplace( 'site_title', $replace_data['site_title'], $content );
			$content = str_ireplace( 'site_url', $replace_data['site_url'], $content );

			return $content;
		}

		/**
		 * It will ganerate the preview email template.
		 *
		 * @hook admin_init
		 * @globals mixed $woocommerce
		 * @since 2.5
		 */
		public function wcal_preview_emails() {
			global $woocommerce;

			if ( isset( $_GET['id'] ) && 0 < sanitize_text_field( wp_unslash( $_GET['id'] ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification
				global $wpdb;
				$id      = sanitize_text_field( wp_unslash( $_GET['id'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
				$content = $wpdb->get_var( // phpcs:ignore
					$wpdb->prepare(
						'SELECT body FROM `' . $wpdb->prefix . 'ac_email_templates_lite` WHERE id = %d',
						absint( $id )
					)
				);
				$content = $this->replace_mergetags( $content );
			}

			if ( isset( $_GET['wcal_preview_woocommerce_mail'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
				if ( isset( $_REQUEST['_wpnonce'] ) && ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_REQUEST['_wpnonce'] ) ), 'woocommerce-abandoned-cart' ) ) {
					die( 'Security check' );
				}
				$message = '';
				// create a new email.
				if ( $woocommerce->version < '2.3' ) {
					global $email_heading;
					ob_start();

					include 'views/wcal-wc-email-template-preview.php';
					$mailer        = WC()->mailer();
					$message       = ob_get_clean();
					$email_heading = __( 'HTML Email Template', 'woocommerce-abandoned-cart' );
					$message       = $mailer->wrap_message( $email_heading, $message );
				} else {
					// load the mailer class.
					$mailer = WC()->mailer();
					// get the preview email subject.
					$email_heading = __( 'Abandoned cart Email Template', 'woocommerce-abandoned-cart' );
					// get the preview email content.
					ob_start();
					if ( isset( $_GET['id'] ) && 0 < sanitize_text_field( wp_unslash( $_GET['id'] ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification
						$message = stripslashes( $content );
					} else {
						include 'views/wcal-wc-email-template-preview.php';
						$message = ob_get_clean();
					}
					// create a new email.
					$email = new WC_Email();
					// wrap the content with the email template and then add styles.
					$message = $email->style_inline( $mailer->wrap_message( $email_heading, $message ) );
				}
				echo $message; // phpcs:ignore
				exit;
			}

			if ( isset( $_GET['wcal_preview_mail'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
				if ( isset( $_REQUEST['_wpnonce'] ) && ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_REQUEST['_wpnonce'] ) ), 'woocommerce-abandoned-cart' ) ) {
					die( 'Security check' );
				}
				// get the preview email content.
				ob_start();
				if ( isset( $_GET['id'] ) && 0 < sanitize_text_field( wp_unslash( $_GET['id'] ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification
					$message = stripslashes( $content );
				} else {
					include_once 'views/wcal-email-template-preview.php';
					$message = ob_get_clean();
				}
				// print the preview email.
				echo $message; // phpcs:ignore
				exit;
			}
		}

		/**
		 * In this version we have allowed customer to transalte the plugin string using .po and .pot file.
		 *
		 * @hook init
		 * @return $loaded
		 * @since 1.6
		 */
		public function wcal_update_po_file() {
			/*
			* Due to the introduction of language packs through translate.wordpress.org, loading our textdomain is complex.
			*
			* In v4.7, our textdomain changed from "woocommerce-ac" to "woocommerce-abandoned-cart".
			*/
			$domain = 'woocommerce-abandoned-cart';
			$locale = apply_filters( 'plugin_locale', get_locale(), $domain );
			$loaded = load_textdomain( $domain, trailingslashit( WP_LANG_DIR ) . $domain . '-' . $locale . '.mo' );
			if ( $loaded ) {
				return $loaded;
			} else {
				load_plugin_textdomain( $domain, false, basename( dirname( __FILE__ ) ) . '/i18n/languages/' );
			}
		}

		/**
		 * It will create the plugin tables & the options reqired for plugin.
		 *
		 * @hook register_activation_hook
		 * @globals mixed $wpdb
		 * @since 1.0
		 */
		public static function wcal_activate() {

			// check whether its a multi site install or a single site install.
			if ( is_multisite() ) {

				$blog_list = get_sites();
				foreach ( $blog_list as $blog_list_key => $blog_list_value ) {
					if ( $blog_list_value->blog_id > 1 ) { // child sites.
						$blog_id = $blog_list_value->blog_id;
						self::wcal_process_activate( $blog_id );
					} else { // parent site.
						self::wcal_process_activate();
					}
				}
			} else { // single site.
				self::wcal_process_activate();
			}
		}

		/**
		 * Things to do when the plugin is deactivated.
		 *
		 * @since 5.8.0
		 */
		public static function wcal_deactivate() {
			if ( false !== as_next_scheduled_action( 'woocommerce_ac_send_email_action' ) ) {
				as_unschedule_action( 'woocommerce_ac_send_email_action' ); // Remove the scheduled action.
			}
			do_action( 'wcal_deactivate' );
		}

		/**
		 * Activation code: Create tables, default settings etc.
		 *
		 * @param int $blog_id - Greater than 0 for subsites in a multisite install, 0 for single sites.
		 */
		public static function wcal_process_activate( $blog_id = 0 ) {
			global $wpdb;

			$db_prefix = ( 0 === $blog_id ) ? $wpdb->prefix : $wpdb->prefix . $blog_id . '_';

			$wcap_collate = '';
			if ( $wpdb->has_cap( 'collation' ) ) {
				$wcap_collate = $wpdb->get_charset_collate();
			}
			$table_name = $db_prefix . 'ac_email_templates_lite';
			$wpdb->query( // phpcs:ignore
				"CREATE TABLE IF NOT EXISTS $table_name (
				`id` int(11) NOT NULL AUTO_INCREMENT,
				`subject` text NOT NULL,
				`body` mediumtext NOT NULL,
				`is_active` enum('0','1') NOT NULL,
				`frequency` int(11) NOT NULL,
				`day_or_hour` enum('Days','Hours') NOT NULL,
				`template_name` text NOT NULL,
				`is_wc_template` enum('0','1') NOT NULL,
				`default_template` int(11) NOT NULL,
				`wc_email_header` varchar(50) NOT NULL,
				PRIMARY KEY (`id`)
				) $wcap_collate AUTO_INCREMENT=1"
			);

			$sent_table_name = $db_prefix . 'ac_sent_history_lite';
			$wpdb->query( // phpcs:ignore
				"CREATE TABLE IF NOT EXISTS $sent_table_name (
				`id` int(11) NOT NULL auto_increment,
				`template_id` varchar(40) collate utf8_unicode_ci NOT NULL,
				`abandoned_order_id` int(11) NOT NULL,
				`sent_time` datetime NOT NULL,
				`sent_email_id` text COLLATE utf8_unicode_ci NOT NULL,
				PRIMARY KEY  (`id`)
				) $wcap_collate AUTO_INCREMENT=1 "
			);

			$ac_history_table_name = $db_prefix . 'ac_abandoned_cart_history_lite';
			$wpdb->query( // phpcs:ignore
				"CREATE TABLE IF NOT EXISTS $ac_history_table_name (
				`id` int(11) NOT NULL AUTO_INCREMENT,
				`user_id` int(11) NOT NULL,
				`abandoned_cart_info` text COLLATE utf8_unicode_ci NOT NULL,
				`abandoned_cart_time` int(11) NOT NULL,
				`cart_ignored` enum('0','1') COLLATE utf8_unicode_ci NOT NULL,
				`recovered_cart` int(11) NOT NULL,
				`user_type` text,
				`unsubscribe_link` enum('0','1') COLLATE utf8_unicode_ci NOT NULL,
				`session_id` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
				`email_reminder_status` varchar(50) COLLATE utf8_unicode_ci NOT NULL,
				PRIMARY KEY (`id`)
				) $wcap_collate"
			);

			$guest_table        = $db_prefix . 'ac_guest_abandoned_cart_history_lite';
			$result_guest_table = $wpdb->get_results( // phpcs:ignore
				"SHOW TABLES LIKE '$guest_table'"
			);

			if ( 0 === count( $result_guest_table ) ) {
				$ac_guest_history_table_name = $db_prefix . 'ac_guest_abandoned_cart_history_lite';
				$wpdb->query( // phpcs:ignore
					"CREATE TABLE IF NOT EXISTS $ac_guest_history_table_name (
					`id` int(15) NOT NULL AUTO_INCREMENT,
					`billing_first_name` text,
					`billing_last_name` text,
					`billing_company_name` text,
					`billing_address_1` text,
					`billing_address_2` text,
					`billing_city` text,
					`billing_county` text,
					`billing_zipcode` text,
					`email_id` text,
					`phone` text,
					`ship_to_billing` text,
					`order_notes` text,
					`shipping_first_name` text,
					`shipping_last_name` text,
					`shipping_company_name` text,
					`shipping_address_1` text,
					`shipping_address_2` text,
					`shipping_city` text,
					`shipping_county` text,
					`shipping_zipcode` double,
					`shipping_charges` double,
					PRIMARY KEY (`id`)
					) $wcap_collate AUTO_INCREMENT=63000000"
				);
			}

			// Default templates - function call to create default templates.
			$check_table_empty = $wpdb->get_var( 'SELECT COUNT(*) FROM `' . $db_prefix . 'ac_email_templates_lite`' ); // phpcs:ignore

			/**
			 * This is add for thos user who Install the plguin first time.
			 * So for them this option will be cheked.
			 */
			if ( 0 === $blog_id ) {
				if ( ! get_option( 'wcal_new_default_templates' ) ) {
					if ( 0 === $check_table_empty ) {
						$default_template = new Wcal_Default_Template_Settings();
						$default_template->wcal_create_default_templates( $db_prefix, $blog_id );
					}
				}
				if ( ! get_option( 'ac_lite_cart_abandoned_time' ) ) {
					add_option( 'ac_lite_cart_abandoned_time', 10 );
				}
				if ( ! get_option( 'ac_lite_track_guest_cart_from_cart_page' ) ) {
					add_option( 'ac_lite_track_guest_cart_from_cart_page', 'on' );
				}
				if ( ! get_option( 'wcal_from_name' ) ) {
					add_option( 'wcal_from_name', 'Admin' );
				}
				$wcal_get_admin_email = get_option( 'admin_email' );
				if ( ! get_option( 'wcal_from_email' ) ) {
					add_option( 'wcal_from_email', $wcal_get_admin_email );
				}

				if ( ! get_option( 'wcal_reply_email' ) ) {
					add_option( 'wcal_reply_email', $wcal_get_admin_email );
				}
			} else {
				if ( ! get_blog_option( $blog_id, 'wcal_new_default_templates' ) ) {
					if ( 0 === $check_table_empty ) {
						$default_template = new Wcal_Default_Template_Settings();
						$default_template->wcal_create_default_templates( $db_prefix, $blog_id );
					}
				}
				if ( ! get_blog_option( $blog_id, 'ac_lite_cart_abandoned_time' ) ) {
					add_blog_option( $blog_id, 'ac_lite_cart_abandoned_time', 10 );
				}
				if ( ! get_blog_option( $blog_id, 'ac_lite_track_guest_cart_from_cart_page' ) ) {
					add_blog_option( $blog_id, 'ac_lite_track_guest_cart_from_cart_page', 'on' );
				}
				if ( ! get_blog_option( $blog_id, 'wcal_from_name' ) ) {
					add_blog_option( $blog_id, 'wcal_from_name', 'Admin' );
				}
				$wcal_get_admin_email = get_option( 'admin_email' );
				if ( ! get_blog_option( $blog_id, 'wcal_from_email' ) ) {
					add_blog_option( $blog_id, 'wcal_from_email', $wcal_get_admin_email );
				}

				if ( ! get_blog_option( $blog_id, 'wcal_reply_email' ) ) {
					add_blog_option( $blog_id, 'wcal_reply_email', $wcal_get_admin_email );
				}
			}
			do_action( 'wcal_activate' );
		}

		/**
		 * It will add the section, field, & registres the plugin fields using Settings API.
		 *
		 * @hook admin_init
		 * @since 2.5
		 */
		public function wcal_initialize_plugin_options() {

			// First, we register a section. This is necessary since all future options must belong to a section.
			add_settings_section(
				'ac_lite_general_settings_section',                 // ID used to identify this section and with which to register options.
				__( 'Settings', 'woocommerce-abandoned-cart' ),     // Title to be displayed on the administration page.
				array( $this, 'ac_lite_general_options_callback' ), // Callback used to render the description of the section.
				'woocommerce_ac_page'                               // Page on which to add this section of options.
			);

			add_settings_field(
				'wcal_enable_cart_emails',
				__( 'Enable abandoned cart emails', 'woocommerce-abandoned-cart' ),
				array( $this, 'wcal_enable_cart_emails_callback' ),
				'woocommerce_ac_page',
				'ac_lite_general_settings_section',
				array( __( 'Yes, enable the abandoned cart emails.', 'woocommerce-abandoned-cart' ) )
			);

			add_settings_field(
				'ac_lite_cart_abandoned_time',
				__( 'Cart abandoned cut-off time', 'woocommerce-abandoned-cart' ),
				array( $this, 'ac_lite_cart_abandoned_time_callback' ),
				'woocommerce_ac_page',
				'ac_lite_general_settings_section',
				array( __( 'Consider cart abandoned after X minutes of item being added to cart & order not placed.', 'woocommerce-abandoned-cart' ) )
			);

			add_settings_field(
				'ac_lite_delete_abandoned_order_days',
				__( 'Automatically Delete Abandoned Orders after X days', 'woocommerce-abandoned-cart' ),
				array( $this, 'wcal_delete_abandoned_orders_days_callback' ),
				'woocommerce_ac_page',
				'ac_lite_general_settings_section',
				array( __( 'Automatically delete abandoned cart orders after X days.', 'woocommerce-abandoned-cart' ) )
			);

			add_settings_field(
				'ac_lite_email_admin_on_recovery',
				__( 'Email admin On Order Recovery', 'woocommerce-abandoned-cart' ),
				array( $this, 'ac_lite_email_admin_on_recovery' ),
				'woocommerce_ac_page',
				'ac_lite_general_settings_section',
				array( __( 'Sends email to Admin if an Abandoned Cart Order is recovered.', 'woocommerce-abandoned-cart' ) )
			);

			add_settings_field(
				'ac_lite_track_guest_cart_from_cart_page',
				__( 'Start tracking from Cart Page', 'woocommerce-abandoned-cart' ),
				array( $this, 'wcal_track_guest_cart_from_cart_page_callback' ),
				'woocommerce_ac_page',
				'ac_lite_general_settings_section',
				array( __( 'Enable tracking of abandoned products & carts even if customer does not visit the checkout page or does not enter any details on the checkout page like Name or Email. Tracking will begin as soon as a visitor adds a product to their cart and visits the cart page.', 'woocommerce-abandoned-cart' ) )
			);

			add_settings_field(
				'wcal_guest_cart_capture_msg',
				__( 'Message to be displayed for Guest users when tracking their carts', 'woocommerce-abandoned-cart' ),
				array( $this, 'wcal_guest_cart_capture_msg_callback' ),
				'woocommerce_ac_page',
				'ac_lite_general_settings_section',
				array( __( '<br>In compliance with GDPR, add a message on the Checkout page to inform Guest users of how their data is being used.<br><i>For example: Your email address will help us support your shopping experience throughout the site. Please check our Privacy Policy to see how we use your personal data.</i>', 'woocommerce-abandoned-cart' ) )
			);

			add_settings_field(
				'wcal_logged_cart_capture_msg',
				__( 'Message to be displayed for registered users when tracking their carts.', 'woocommerce-abandoned-cart' ),
				array( $this, 'wcal_logged_cart_capture_msg_callback' ),
				'woocommerce_ac_page',
				'ac_lite_general_settings_section',
				array( __( '<br>In compliance with GDPR, add a message on the Shop & Product pages to inform Registered users of how their data is being used.<br><i>For example: Please check our Privacy Policy to see how we use your personal data.</i>', 'woocommerce-abandoned-cart' ) )
			);

			add_settings_field(
				'wcal_gdpr_allow_opt_out',
				__( 'Allow the visitor to opt out of cart tracking.', 'woocommerce-abandoned-cart' ),
				array( $this, 'wcal_gdpr_allow_opt_out_callback' ),
				'woocommerce_ac_page',
				'ac_lite_general_settings_section',
				array( __( '<br>In compliance with GDPR, allow the site visitor (guests & registered users) to opt out from cart tracking. This message will be displayed in conjunction with the GDPR message above.</i>', 'woocommerce-abandoned-cart' ) )
			);

			add_settings_field(
				'wcal_gdpr_opt_out_message',
				__( 'Message to be displayed when the user chooses to opt out of cart tracking.', 'woocommerce-abandoned-cart' ),
				array( $this, 'wcal_gdpr_opt_out_msg_callback' ),
				'woocommerce_ac_page',
				'ac_lite_general_settings_section',
				array( __( '<br>Message to be displayed when the user chooses to opt out of cart tracking.</i>', 'woocommerce-abandoned-cart' ) )
			);

			/**
			 * New section for the Adding the abandoned cart setting.
			 *
			 * @since  4.7
			 */
			add_settings_section(
				'ac_email_settings_section',                                                       // ID used to identify this section and with which to register options.
				__( 'Settings for abandoned cart recovery emails', 'woocommerce-abandoned-cart' ), // Title to be displayed on the administration page.
				array( $this, 'wcal_email_callback' ),                                             // Callback used to render the description of the section.
				'woocommerce_ac_email_page'                                                        // Page on which to add this section of options.
			);

			add_settings_field(
				'wcal_from_name',
				__( '"From" Name', 'woocommerce-abandoned-cart' ),
				array( $this, 'wcal_from_name_callback' ),
				'woocommerce_ac_email_page',
				'ac_email_settings_section',
				array( 'Enter the name that should appear in the email sent.', 'woocommerce-abandoned-cart' )
			);

			add_settings_field(
				'wcal_from_email',
				__( '"From" Address', 'woocommerce-abandoned-cart' ),
				array( $this, 'wcal_from_email_callback' ),
				'woocommerce_ac_email_page',
				'ac_email_settings_section',
				array( 'Email address from which the reminder emails should be sent.', 'woocommerce-abandoned-cart' )
			);

			add_settings_field(
				'wcal_reply_email',
				__( 'Send Reply Emails to', 'woocommerce-abandoned-cart' ),
				array( $this, 'wcal_reply_email_callback' ),
				'woocommerce_ac_email_page',
				'ac_email_settings_section',
				array( 'When a contact receives your email and clicks reply, which email address should that reply be sent to?', 'woocommerce-abandoned-cart' )
			);

			// Finally, we register the fields with WordPress.
			register_setting(
				'woocommerce_ac_settings',
				'wcal_enable_cart_emails'
			);

			register_setting(
				'woocommerce_ac_settings',
				'ac_lite_cart_abandoned_time',
				array( $this, 'ac_lite_cart_time_validation' )
			);

			register_setting(
				'woocommerce_ac_settings',
				'ac_lite_delete_abandoned_order_days',
				array( $this, 'wcal_delete_days_validation' )
			);

			register_setting(
				'woocommerce_ac_settings',
				'ac_lite_email_admin_on_recovery'
			);

			register_setting(
				'woocommerce_ac_settings',
				'ac_lite_track_guest_cart_from_cart_page'
			);

			register_setting(
				'woocommerce_ac_settings',
				'wcal_guest_cart_capture_msg'
			);

			register_setting(
				'woocommerce_ac_settings',
				'wcal_logged_cart_capture_msg'
			);

			register_setting(
				'woocommerce_ac_settings',
				'wcal_gdpr_allow_opt_out'
			);

			register_setting(
				'woocommerce_ac_settings',
				'wcal_gdpr_opt_out_message'
			);

			register_setting(
				'woocommerce_ac_email_settings',
				'wcal_from_name'
			);
			register_setting(
				'woocommerce_ac_email_settings',
				'wcal_from_email'
			);
			register_setting(
				'woocommerce_ac_email_settings',
				'wcal_reply_email'
			);

			do_action( 'wcal_add_new_settings' );
		}

		/**
		 * Settings API callback for section "ac_lite_general_settings_section".
		 *
		 * @since 2.5
		 */
		public function ac_lite_general_options_callback() {
		}

		/**
		 * Settings API callback for the enable cart reminder emails.
		 *
		 * @param array $args - Arguments.
		 * @since 5.5
		 */
		public static function wcal_enable_cart_emails_callback( $args ) {

			$enable_cart_emails = get_option( 'wcal_enable_cart_emails', '' );

			if ( isset( $enable_cart_emails ) && '' === $enable_cart_emails ) {
				$enable_cart_emails = 'off';
			}
			printf(
				'<input type="checkbox" id="wcal_enable_cart_emails" name="wcal_enable_cart_emails" value="on" ' . checked( 'on', $enable_cart_emails, false ) . ' />'
			);
			$html = '<label for="wcal_enable_cart_emails"> ' . $args[0] . '</label>';
			echo wp_kses_post( $html );
		}

		/**
		 * Settings API callback for cart time field.
		 *
		 * @param array $args Arguments.
		 * @since 2.5
		 */
		public function ac_lite_cart_abandoned_time_callback( $args ) {
			// First, we read the option.
			$cart_abandoned_time = get_option( 'ac_lite_cart_abandoned_time' );
			// Next, we update the name attribute to access this element's ID in the context of the display options array.
			// We also access the show_header element of the options collection in the call to the checked() helper function.
			printf(
				'<input type="text" id="ac_lite_cart_abandoned_time" name="ac_lite_cart_abandoned_time" value="%s" />',
				isset( $cart_abandoned_time ) ? esc_attr( $cart_abandoned_time ) : ''
			);
			// Here, we'll take the first argument of the array and add it to a label next to the checkbox.
			$html = '<label for="ac_lite_cart_abandoned_time"> ' . $args[0] . '</label>';
			echo wp_kses_post( $html );
		}

		/**
		 * Settings API cart time field validation.
		 *
		 * @param int|string $input - Input to be validated.
		 * @return int|string $output - Validated output.
		 * @since 2.5
		 */
		public function ac_lite_cart_time_validation( $input ) {
			$output = '';
			if ( '' != $input && ( is_numeric( $input ) && $input > 0 ) ) { // phpcs:ignore
				$output = stripslashes( $input );
			} else {
				add_settings_error( 'ac_lite_cart_abandoned_time', 'error found', __( 'Abandoned cart cut off time should be numeric and has to be greater than 0.', 'woocommerce-abandoned-cart' ) );
			}
			return $output;
		}

		/**
		 * Validation for automatically delete abandoned carts after X days.
		 *
		 * @param int | string $input input of the field Abandoned cart cut off time.
		 * @return int | string $output Error message or the input value.
		 * @since  5.0
		 */
		public static function wcal_delete_days_validation( $input ) {
			$output = '';
			if ( '' == $input || ( is_numeric( $input ) && $input > 0 ) ) { // phpcS:ignore
				$output = stripslashes( $input );
			} else {
				add_settings_error( 'ac_lite_delete_abandoned_order_days', 'error found', __( 'Automatically Delete Abandoned Orders after X days has to be greater than 0.', 'woocommerce-abandoned-cart' ) );
			}
			return $output;
		}

		/**
		 * Callback for deleting abandoned order after X days field.
		 *
		 * @param array $args Argument given while adding the field.
		 * @since 5.0
		 */
		public static function wcal_delete_abandoned_orders_days_callback( $args ) {
			// First, we read the option.
			$delete_abandoned_order_days = get_option( 'ac_lite_delete_abandoned_order_days' );
			// Next, we update the name attribute to access this element's ID in the context of the display options array.
			// We also access the show_header element of the options collection in the call to the checked() helper function.
			printf(
				'<input type="text" id="ac_lite_delete_abandoned_order_days" name="ac_lite_delete_abandoned_order_days" value="%s" />',
				isset( $delete_abandoned_order_days ) ? esc_attr( $delete_abandoned_order_days ) : ''
			);
			// Here, we'll take the first argument of the array and add it to a label next to the checkbox.
			$html = '<label for="ac_lite_delete_abandoned_order_days"> ' . $args[0] . '</label>';
			echo wp_kses_post( $html );
		}

		/**
		 * Settings API callback for email admin on cart recovery field.
		 *
		 * @param array $args Arguments.
		 * @since 2.5
		 */
		public function ac_lite_email_admin_on_recovery( $args ) {
			// First, we read the option.
			$email_admin_on_recovery = get_option( 'ac_lite_email_admin_on_recovery', '' );

			// This condition added to avoid the notie displyed while Check box is unchecked.
			if ( isset( $email_admin_on_recovery ) && '' === $email_admin_on_recovery ) {
				$email_admin_on_recovery = 'off';
			}
			// Next, we update the name attribute to access this element's ID in the context of the display options array.
			// We also access the show_header element of the options collection in the call to the checked() helper function.
			$html = '';
			printf(
				'<input type="checkbox" id="ac_lite_email_admin_on_recovery" name="ac_lite_email_admin_on_recovery" value="on"
			' . checked( 'on', $email_admin_on_recovery, false ) . ' />'
			);

			// Here, we'll take the first argument of the array and add it to a label next to the checkbox.
			$html .= '<label for="ac_lite_email_admin_on_recovery"> ' . $args[0] . '</label>';
			echo wp_kses_post( $html );
		}

		/**
		 * Settings API callback for capturing guest cart which do not reach the checkout page.
		 *
		 * @param array $args Arguments.
		 * @since 2.7
		 */
		public function wcal_track_guest_cart_from_cart_page_callback( $args ) {
			// First, we read the option.
			$disable_guest_cart_from_cart_page = get_option( 'ac_lite_track_guest_cart_from_cart_page', '' );

			// This condition added to avoid the notice displyed while Check box is unchecked.
			if ( isset( $disable_guest_cart_from_cart_page ) && '' === $disable_guest_cart_from_cart_page ) {
				$disable_guest_cart_from_cart_page = 'off';
			}
			// Next, we update the name attribute to access this element's ID in the context of the display options array.
			// We also access the show_header element of the options collection in the call to the checked() helper function.
			$html = '';

			printf(
				'<input type="checkbox" id="ac_lite_track_guest_cart_from_cart_page" name="ac_lite_track_guest_cart_from_cart_page" value="on"
				' . checked( 'on', $disable_guest_cart_from_cart_page, false ) . ' />'
			);
			// Here, we'll take the first argument of the array and add it to a label next to the checkbox.
			$html .= '<label for="ac_lite_track_guest_cart_from_cart_page"> ' . $args[0] . '</label>';
			echo wp_kses_post( $html );
		}

		/**
		 * Call back function for guest user cart capture message
		 *
		 * @param array $args Argument for adding field details.
		 * @since 7.8
		 */
		public static function wcal_guest_cart_capture_msg_callback( $args ) {

			$guest_msg = get_option( 'wcal_guest_cart_capture_msg' );

			printf(
				"<textarea rows='4' cols='80' id='wcal_guest_cart_capture_msg' name='wcal_guest_cart_capture_msg'>" . htmlspecialchars( $guest_msg, ENT_QUOTES ) . '</textarea>' // phpcs:ignore
			);

			$html = '<label for="wcal_guest_cart_capture_msg"> ' . $args[0] . '</label>';
			echo wp_kses_post( $html );
		}

		/**
		 * Call back function for registered user cart capture message
		 *
		 * @param array $args Argument for adding field details.
		 * @since 7.8
		 */
		public static function wcal_logged_cart_capture_msg_callback( $args ) {

			$logged_msg = get_option( 'wcal_logged_cart_capture_msg' );

			printf(
				"<input type='text' class='regular-text' id='wcal_logged_cart_capture_msg' name='wcal_logged_cart_capture_msg' value='" . htmlspecialchars( $logged_msg, ENT_QUOTES ) . "' />" // phpcs:ignore
			);

			$html = '<label for="wcal_logged_cart_capture_msg"> ' . $args[0] . '</label>';
			echo wp_kses_post( $html );
		}


		/**
		 * Text to allow the user the choice to opt out of cart tracking.
		 *
		 * @param array $args - Arguments.
		 * @since 5.5
		 */
		public static function wcal_gdpr_allow_opt_out_callback( $args ) {

			$wcal_gdpr_allow_opt_out = get_option( 'wcal_gdpr_allow_opt_out' );

			printf(
				"<input type='text' class='regular-text' id='wcal_gdpr_allow_opt_out' name='wcal_gdpr_allow_opt_out' value='" . htmlspecialchars( $wcal_gdpr_allow_opt_out, ENT_QUOTES ) . "' />" // phpcs:ignore
			);

			$html = '<label for="wcal_gdpr_allow_opt_out"> ' . $args[0] . '</label>';
			echo wp_kses_post( $html );
		}

		/**
		 * Message to display when the user chooses to opt out of cart tracking.
		 *
		 * @param array $args - Arguments.
		 * @since 5.5
		 */
		public static function wcal_gdpr_opt_out_msg_callback( $args ) {

			$wcal_gdpr_opt_out_message = get_option( 'wcal_gdpr_opt_out_message' );

			printf(
				"<input type='text' class='regular-text' id='wcal_gdpr_opt_out_message' name='wcal_gdpr_opt_out_message' value='" . htmlspecialchars( $wcal_gdpr_opt_out_message, ENT_QUOTES ) . "' />" // phpcs:ignore
			);

			$html = '<label for="wcal_gdpr_opt_out_message"> ' . $args[0] . '</label>';
			echo wp_kses_post( $html );
		}

		/**
		 * Settings API callback for Abandoned cart email settings of the plugin.
		 *
		 * @since 3.5
		 */
		public function wcal_email_callback() {
		}

		/**
		 * Settings API callback for from name used in Abandoned cart email.
		 *
		 * @param array $args Arguments.
		 * @since 3.5
		 */
		public static function wcal_from_name_callback( $args ) {
			// First, we read the option.
			$wcal_from_name = get_option( 'wcal_from_name' );
			// Next, we update the name attribute to access this element's ID in the context of the display options array.
			// We also access the show_header element of the options collection in the call to the checked() helper function.
			printf(
				'<input type="text" id="wcal_from_name" name="wcal_from_name" value="%s" />',
				isset( $wcal_from_name ) ? esc_attr( $wcal_from_name ) : ''
			);
			// Here, we'll take the first argument of the array and add it to a label next to the checkbox.
			$html = '<label for="wcal_from_name_label"> ' . $args[0] . '</label>';
			echo wp_kses_post( $html );
		}

		/**
		 * Settings API callback for from email used in Abandoned cart email.
		 *
		 * @param array $args Arguments.
		 * @since 3.5
		 */
		public static function wcal_from_email_callback( $args ) {
			// First, we read the option.
			$wcal_from_email = get_option( 'wcal_from_email' );
			// Next, we update the name attribute to access this element's ID in the context of the display options array.
			// We also access the show_header element of the options collection in the call to the checked() helper function.
			printf(
				'<input type="text" id="wcal_from_email" name="wcal_from_email" value="%s" />',
				isset( $wcal_from_email ) ? esc_attr( $wcal_from_email ) : ''
			);
			// Here, we'll take the first argument of the array and add it to a label next to the checkbox.
			$html = '<label for="wcal_from_email_label"> ' . $args[0] . '</label>';
			echo wp_kses_post( $html );
		}

		/**
		 * Settings API callback for reply email used in Abandoned cart email.
		 *
		 * @param array $args Arguments.
		 * @since 3.5
		 */
		public static function wcal_reply_email_callback( $args ) {
			// First, we read the option.
			$wcal_reply_email = get_option( 'wcal_reply_email' );
			// Next, we update the name attribute to access this element's ID in the context of the display options array.
			// We also access the show_header element of the options collection in the call to the checked() helper function.
			printf(
				'<input type="text" id="wcal_reply_email" name="wcal_reply_email" value="%s" />',
				isset( $wcal_reply_email ) ? esc_attr( $wcal_reply_email ) : ''
			);
			// Here, we'll take the first argument of the array and add it to a label next to the checkbox.
			$html = '<label for="wcal_reply_email_label"> ' . $args[0] . '</label>';
			echo wp_kses_post( $html );
		}

		/**
		 * Add a submenu page under the WooCommerce.
		 *
		 * @hook admin_menu
		 * @since 1.0
		 */
		public function wcal_admin_menu() {
			$page = add_submenu_page( 'woocommerce', __( 'Abandoned Carts', 'woocommerce-abandoned-cart' ), __( 'Abandoned Carts', 'woocommerce-abandoned-cart' ), 'manage_woocommerce', 'woocommerce_ac_page', array( &$this, 'wcal_menu_page' ) );
		}

		/**
		 * Capture the cart and insert the information of the cart into DataBase.
		 *
		 * @hook woocommerce_cart_updated
		 * @globals mixed $wpdb
		 * @globals mixed $woocommerce
		 * @since 1.0
		 */
		public function wcal_store_cart_timestamp() {

			$block_crawlers = apply_filters( 'wcal_block_crawlers', false );

			if ( $block_crawlers ) {
				return;
			}

			if ( get_transient( 'wcal_email_sent_id' ) !== false ) {
				wcal_common::wcal_set_cart_session( 'email_sent_id', get_transient( 'wcal_email_sent_id' ) );
				delete_transient( 'wcal_email_sent_id' );
			}
			if ( get_transient( 'wcal_abandoned_id' ) !== false ) {
				wcal_common::wcal_set_cart_session( 'abandoned_cart_id_lite', get_transient( 'wcal_abandoned_id' ) );
				delete_transient( 'wcal_abandoned_id' );
			}

			global $wpdb,$woocommerce;
			$current_time                    = current_time( 'timestamp' ); // phpcs:ignore
			$cut_off_time                    = get_option( 'ac_lite_cart_abandoned_time' );
			$track_guest_cart_from_cart_page = get_option( 'ac_lite_track_guest_cart_from_cart_page' );
			$cart_ignored                    = 0;
			$recovered_cart                  = 0;

			$track_guest_user_cart_from_cart = '';
			if ( isset( $track_guest_cart_from_cart_page ) ) {
				$track_guest_user_cart_from_cart = $track_guest_cart_from_cart_page;
			}

			if ( isset( $cut_off_time ) ) {
				$cart_cut_off_time = intval( $cut_off_time ) * 60;
			} else {
				$cart_cut_off_time = 60 * 60;
			}
			$compare_time = $current_time - $cart_cut_off_time;

			if ( is_user_logged_in() ) {

				$user_id      = get_current_user_id();
				$gdpr_consent = get_user_meta( $user_id, 'wcal_gdpr_tracking_choice', true );

				if ( '' === $gdpr_consent ) {
					$gdpr_consent = true;
				}

				$wcal_user_restricted = false;
				$wcal_user_restricted = apply_filters( 'wcal_restrict_user', $wcal_user_restricted, $user_id );

				if ( $gdpr_consent && ! $wcal_user_restricted ) {

					$results = $wpdb->get_results( // phpcs:ignore
						$wpdb->prepare(
							'SELECT * FROM `' . $wpdb->prefix . 'ac_abandoned_cart_history_lite` WHERE user_id = %d AND cart_ignored = %s AND recovered_cart = %s',
							$user_id,
							$cart_ignored,
							$recovered_cart
						)
					);
					if ( 0 === count( $results ) ) {
						$cart_info_meta         = array();
						$cart_info_meta['cart'] = WC()->session->cart;
						$cart_info_meta         = wp_json_encode( $cart_info_meta );

						if ( '' !== $cart_info_meta && '{"cart":[]}' !== $cart_info_meta && '""' !== $cart_info_meta ) {
							$cart_info = $cart_info_meta;
							$user_type = 'REGISTERED';
							$wpdb->query( //phpcs:ignore
								$wpdb->prepare(
									'INSERT INTO `' . $wpdb->prefix . 'ac_abandoned_cart_history_lite` ( user_id, abandoned_cart_info, abandoned_cart_time, cart_ignored, user_type ) VALUES ( %d, %s, %d, %s, %s )',
									$user_id,
									$cart_info,
									$current_time,
									$cart_ignored,
									$user_type
								)
							);
							$abandoned_cart_id = $wpdb->insert_id;
							wcal_common::wcal_set_cart_session( 'abandoned_cart_id_lite', $abandoned_cart_id );
						}
					} elseif ( isset( $results[0]->abandoned_cart_time ) && $compare_time > $results[0]->abandoned_cart_time ) {
						$updated_cart_info         = array();
						$updated_cart_info['cart'] = WC()->session->cart;
						$updated_cart_info         = wp_json_encode( $updated_cart_info );

						if ( ! $this->wcal_compare_carts( $user_id, $results[0]->abandoned_cart_info ) ) {
							$updated_cart_ignored = 1;
							$wpdb->query( //phpcs:ignore
								$wpdb->prepare(
									'UPDATE `' . $wpdb->prefix . 'ac_abandoned_cart_history_lite` SET cart_ignored = %s WHERE user_id = %d',
									$updated_cart_ignored,
									$user_id
								)
							);
							$user_type = 'REGISTERED';
							$wpdb->query( //phpcs:ignore
								$wpdb->prepare(
									'INSERT INTO `' . $wpdb->prefix . 'ac_abandoned_cart_history_lite` (user_id, abandoned_cart_info, abandoned_cart_time, cart_ignored, user_type) VALUES (%d, %s, %d, %s, %s)',
									$user_id,
									$updated_cart_info,
									$current_time,
									$cart_ignored,
									$user_type
								)
							);
							update_user_meta( $user_id, '_woocommerce_ac_modified_cart', md5( 'yes' ) );

							$abandoned_cart_id = $wpdb->insert_id;
							wcal_common::wcal_set_cart_session( 'abandoned_cart_id_lite', $abandoned_cart_id );
						} else {
							update_user_meta( $user_id, '_woocommerce_ac_modified_cart', md5( 'no' ) );
						}
					} else {
						$updated_cart_info         = array();
						$updated_cart_info['cart'] = WC()->session->cart;
						$updated_cart_info         = wp_json_encode( $updated_cart_info );

						$wpdb->query( //phpcs:ignore
							$wpdb->prepare(
								'UPDATE `' . $wpdb->prefix . 'ac_abandoned_cart_history_lite` SET abandoned_cart_info = %s, abandoned_cart_time = %d WHERE user_id      = %d AND   cart_ignored = %s',
								$updated_cart_info,
								$current_time,
								$user_id,
								$cart_ignored
							)
						);

						$get_abandoned_record = $wpdb->get_results( //phpcs:ignore
							$wpdb->prepare(
								'SELECT * FROM `' . $wpdb->prefix . 'ac_abandoned_cart_history_lite` WHERE user_id = %d AND cart_ignored = %s',
								$user_id,
								0
							)
						);

						if ( count( $get_abandoned_record ) > 0 ) {
							$abandoned_cart_id = $get_abandoned_record[0]->id;
							wcal_common::wcal_set_cart_session( 'abandoned_cart_id_lite', $abandoned_cart_id );
						}
					}
				}
			} else {
				// start here guest user.
				$user_id = wcal_common::wcal_get_cart_session( 'user_id' );

				// GDPR consent.
				$gdpr_consent  = true;
				$show_gdpr_msg = wcal_common::wcal_get_cart_session( 'wcal_cart_tracking_refused' );
				if ( isset( $show_gdpr_msg ) && 'yes' == $show_gdpr_msg ) { // phpcs:ignore
					$gdpr_consent = false;
				}

				if ( $gdpr_consent ) {
					$results = $wpdb->get_results( //phpcs:ignore
						$wpdb->prepare(
							'SELECT * FROM `' . $wpdb->prefix . 'ac_abandoned_cart_history_lite` WHERE user_id = %d AND cart_ignored = %s AND recovered_cart = %s AND user_id != %s',
							$user_id,
							0,
							0,
							0
						)
					);
					$cart    = array();

					$get_cookie = WC()->session->get_customer_id();

					if ( function_exists( 'WC' ) ) {
						$cart['cart'] = WC()->session->cart;
					} else {
						$cart['cart'] = $woocommerce->session->cart;
					}

					$updated_cart_info = wp_json_encode( $cart );

					if ( count( $results ) > 0 && '{"cart":[]}' !== $updated_cart_info ) {
						if ( $compare_time > $results[0]->abandoned_cart_time ) {
							if ( ! $this->wcal_compare_only_guest_carts( $updated_cart_info, $results[0]->abandoned_cart_info ) ) {

								$wpdb->query( //phpcs:ignore
									$wpdb->prepare(
										'UPDATE `' . $wpdb->prefix . 'ac_abandoned_cart_history_lite` SET cart_ignored = %s WHERE user_id = %s',
										1,
										$user_id
									)
								);
								$user_type = 'GUEST';
								$wpdb->query( //phpcs:ignore
									$wpdb->prepare(
										'INSERT INTO `' . $wpdb->prefix . 'ac_abandoned_cart_history_lite` (user_id, abandoned_cart_info, abandoned_cart_time, cart_ignored, user_type) VALUES (%d, %s, %d, %s, %s)',
										$user_id,
										$updated_cart_info,
										$current_time,
										$cart_ignored,
										$user_type
									)
								);
								update_user_meta( $user_id, '_woocommerce_ac_modified_cart', md5( 'yes' ) );
							} else {
								update_user_meta( $user_id, '_woocommerce_ac_modified_cart', md5( 'no' ) );
							}
						} else {
							$wpdb->query( //phpcs:ignore
								$wpdb->prepare(
									'UPDATE `' . $wpdb->prefix . 'ac_abandoned_cart_history_lite` SET abandoned_cart_info = %s, abandoned_cart_time = %s WHERE user_id = %d AND cart_ignored = %s',
									$updated_cart_info,
									$current_time,
									$user_id,
									0
								)
							);
						}
					} else {
						// Here we capture the guest cart from the cart page @since 3.5.
						if ( 'on' === $track_guest_user_cart_from_cart && isset( $get_cookie ) && '' !== $get_cookie ) {
							$results = $wpdb->get_results( //phpcs:ignore
								$wpdb->prepare(
									'SELECT * FROM `' . $wpdb->prefix . 'ac_abandoned_cart_history_lite` WHERE session_id LIKE %s AND cart_ignored = %s AND recovered_cart = %s',
									$get_cookie,
									0,
									0
								)
							);
							if ( 0 === count( $results ) ) {
								$cart_info       = $updated_cart_info;
								$blank_cart_info = '[]';
								if ( $blank_cart_info !== $cart_info && '{"cart":[]}' !== $cart_info ) {
									$wpdb->query( //phpcs:ignore
										$wpdb->prepare(
											'INSERT INTO `' . $wpdb->prefix . 'ac_abandoned_cart_history_lite` ( abandoned_cart_info , abandoned_cart_time , cart_ignored , recovered_cart, user_type, session_id  ) VALUES ( %s, %s, %s, %s, %s, %s )',
											$cart_info,
											$current_time,
											0,
											0,
											'GUEST',
											$get_cookie
										)
									);
									$abandoned_cart_id = $wpdb->insert_id;
								}
							} elseif ( $compare_time > $results[0]->abandoned_cart_time ) {
								$blank_cart_info = '[]';
								if ( $blank_cart_info !== $updated_cart_info && '{"cart":[]}' !== $updated_cart_info ) {
									if ( ! $this->wcal_compare_only_guest_carts( $updated_cart_info, $results[0]->abandoned_cart_info ) ) {
										$wpdb->query( // phpcs:ignore
											$wpdb->prepare(
												'UPDATE `' . $wpdb->prefix . 'ac_abandoned_cart_history_lite` SET cart_ignored = %s WHERE session_id = %s',
												1,
												$get_cookie
											)
										);
										$wpdb->query( //phpcs:ignore
											$wpdb->prepare(
												'INSERT INTO `' . $wpdb->prefix . 'ac_abandoned_cart_history_lite` ( abandoned_cart_info, abandoned_cart_time, cart_ignored, recovered_cart, user_type, session_id ) VALUES ( %s, %s, %s, %s, %s, %s )',
												$updated_cart_info,
												$current_time,
												0,
												0,
												'GUEST',
												$get_cookie
											)
										);
										$abandoned_cart_id = $wpdb->insert_id;
									}
								}
							} else {
								$blank_cart_info = '[]';
								if ( $blank_cart_info !== $updated_cart_info && '{"cart":[]}' !== $updated_cart_info ) {
									if ( ! $this->wcal_compare_only_guest_carts( $updated_cart_info, $results[0]->abandoned_cart_info ) ) {
										$wpdb->query( //phpcs:ignore
											$wpdb->prepare(
												'UPDATE `' . $wpdb->prefix . 'ac_abandoned_cart_history_lite` SET abandoned_cart_info = %s, abandoned_cart_time  = %s WHERE session_id = %s AND cart_ignored = %s',
												$updated_cart_info,
												$current_time,
												$get_cookie,
												0
											)
										);
									}
								}
							}
							if ( isset( $abandoned_cart_id ) ) {
								// add the abandoned id in the session.
								wcal_common::wcal_set_cart_session( 'abandoned_cart_id_lite', $abandoned_cart_id );
							}
						}
					}
				}
			}
		}

		/**
		 * Detect Crawlers
		 *
		 * @param boolean $ignore - Ignore.
		 * @return boolean $ignore - Ignore.
		 */
		public function wcal_detect_crawlers( $ignore ) {
			$user_agent = isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '';

			if ( '' === $user_agent ) {
				return $ignore;
			}

			// Current list of bots being blocked:
			// 1. Googlebot, BingBot, DuckDuckBot, YandexBot, Exabot.
			// 2. cURL.
			// 3. wget.
			// 4. Yahoo/Slurp.
			// 5. Baiduspider.
			// 6. Sogou.
			// 7. Alexa.
			$bot_agents = array(
				'curl',
				'wget',
				'bot',
				'bots',
				'slurp',
				'baiduspider',
				'sogou',
				'ia_archiver',
			);

			foreach ( $bot_agents as $url ) {
				if ( false !== stripos( $user_agent, $url ) ) {
					return true;
				}
			}

			return $ignore;
		}

		/**
		 * It will unsubscribe the abandoned cart, so user will not recieve further abandoned cart emails.
		 *
		 * @hook template_include
		 * @param string $args Arguments.
		 * @return string $args Arguments.
		 * @globals mixed $wpdb
		 * @since 2.9
		 */
		public function wcal_email_unsubscribe( $args ) {
			global $wpdb;

			if ( isset( $_GET['wcal_track_unsubscribe'] ) && 'wcal_unsubscribe' === sanitize_text_field( wp_unslash( $_GET['wcal_track_unsubscribe'] ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification
				$encoded_email_id              = isset( $_GET['validate'] ) ? rawurldecode( sanitize_text_field( wp_unslash( $_GET['validate'] ) ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
				$validate_email_id_string      = str_replace( ' ', '+', $encoded_email_id );
				$validate_email_address_string = '';
				$validate_email_id_decode      = 0;
				$crypt_key                     = get_option( 'wcal_security_key' );
				$validate_email_id_decode      = Wcal_Aes_Ctr::decrypt( $validate_email_id_string, $crypt_key, 256 );
				if ( isset( $_GET['track_email_id'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
					$encoded_email_address         = rawurldecode( sanitize_text_field( wp_unslash( $_GET['track_email_id'] ) ) ); // phpcs:ignore WordPress.Security.NonceVerification
					$validate_email_address_string = str_replace( ' ', '+', $encoded_email_address );
				}

				$results_sent  = $wpdb->get_results( //phpcs:ignore
					$wpdb->prepare(
						'SELECT * FROM `' . $wpdb->prefix . 'ac_sent_history_lite` WHERE id = %d ',
						$validate_email_id_decode
					)
				);
				$email_address = '';
				if ( isset( $results_sent[0] ) ) {
					$email_address = $results_sent[0]->sent_email_id;
				}
				if ( hash( 'sha256', $email_address ) === $validate_email_address_string && '' !== $email_address ) {
					$email_sent_id     = $validate_email_id_decode;
					$get_ac_id_results = $wpdb->get_results( //phpcs:ignore
						$wpdb->prepare(
							'SELECT abandoned_order_id FROM `' . $wpdb->prefix . 'ac_sent_history_lite` WHERE id = %d',
							$email_sent_id
						)
					);
					$user_id           = 0;
					if ( isset( $get_ac_id_results[0] ) ) {
						$get_user_results  = $wpdb->get_results( //phpcs:ignore
							$wpdb->prepare(
								'SELECT user_id FROM `' . $wpdb->prefix . 'ac_abandoned_cart_history_lite` WHERE id = %d',
								$get_ac_id_results[0]->abandoned_order_id
							)
						);
					}
					if ( isset( $get_user_results[0] ) ) {
						$user_id = $get_user_results[0]->user_id;
					}

					$wpdb->query( //phpcs:ignore
						$wpdb->prepare(
							'UPDATE `' . $wpdb->prefix . 'ac_abandoned_cart_history_lite` SET unsubscribe_link = %s WHERE user_id= %d AND cart_ignored = %s',
							1,
							$user_id,
							0
						)
					);
					echo esc_html( 'Unsubscribed Successfully' );
					sleep( 2 );
					$url = get_option( 'siteurl' );
					?>
					<script>
						location.href = "<?php echo esc_url( $url ); ?>";
					</script>
					<?php
				}
			} else {
				return $args;
			}
		}

		/**
		 * It will track the URL of cart link from email, and it will populate the logged-in and guest users cart.
		 *
		 * @hook template_include
		 * @param string $template - Template name.
		 * @return string $template - Template name.
		 * @globals mixed $wpdb
		 * @globals mixed $woocommerce
		 * @since 1.0
		 */
		public function wcal_email_track_links( $template ) {
			global $woocommerce;

			$track_link = isset( $_GET['wcal_action'] ) ? sanitize_text_field( wp_unslash( $_GET['wcal_action'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification

			if ( 'track_links' === $track_link ) {
				if ( '' === session_id() ) {
					// session has not started.
					session_start();
				}
				global $wpdb;
				$validate_server_string  = isset( $_GET ['validate'] ) ? rawurldecode( wp_unslash( $_GET ['validate'] ) ) : ''; // phpcs:ignore
				$validate_server_string  = str_replace( ' ', '+', $validate_server_string );
				$validate_encoded_string = $validate_server_string;
				$crypt_key               = get_option( 'wcal_security_key' );
				$link_decode             = Wcal_Aes_Ctr::decrypt( $validate_encoded_string, $crypt_key, 256 );
				$sent_email_id_pos       = strpos( $link_decode, '&' );
				$email_sent_id           = substr( $link_decode, 0, $sent_email_id_pos );

				wcal_common::wcal_set_cart_session( 'email_sent_id', $email_sent_id );
				set_transient( 'wcal_email_sent_id', $email_sent_id, 5 );

				$url_pos = strpos( $link_decode, '=' );
				++$url_pos;
				$url               = substr( $link_decode, $url_pos );
				$get_ac_id_results = $wpdb->get_results( //phpcs:ignore
					$wpdb->prepare(
						'SELECT abandoned_order_id FROM `' . $wpdb->prefix . 'ac_sent_history_lite` WHERE id = %d',
						$email_sent_id
					)
				);

				wcal_common::wcal_set_cart_session( 'abandoned_cart_id_lite', $get_ac_id_results[0]->abandoned_order_id );
				set_transient( 'wcal_abandoned_id', $get_ac_id_results[0]->abandoned_order_id, 5 );

				$get_user_results = array();
				if ( count( $get_ac_id_results ) > 0 ) {
					$get_user_results  = $wpdb->get_results( //phpcs:ignore
						$wpdb->prepare(
							'SELECT user_id FROM `' . $wpdb->prefix . 'ac_abandoned_cart_history_lite` WHERE id = %d',
							$get_ac_id_results[0]->abandoned_order_id
						)
					);
				}
				$user_id = isset( $get_user_results ) && count( $get_user_results ) > 0 ? (int) $get_user_results[0]->user_id : 0;

				if ( 0 === $user_id ) {
					echo esc_html( 'Link expired' );
					exit;
				}
				$user = wp_set_current_user( $user_id );
				if ( $user_id >= '63000000' ) {
					$results_guest = $wpdb->get_results( //phpcs:ignore
						$wpdb->prepare(
							'SELECT * from `' . $wpdb->prefix . 'ac_guest_abandoned_cart_history_lite` WHERE id = %d',
							$user_id
						)
					);

					$results = $wpdb->get_results( //phpcs:ignore
						$wpdb->prepare(
							'SELECT recovered_cart FROM `' . $wpdb->prefix . 'ac_abandoned_cart_history_lite` WHERE user_id = %d',
							$user_id
						)
					);
					if ( $results_guest && '0' == $results[0]->recovered_cart ) { // phpcs:ignore
						wcal_common::wcal_set_cart_session( 'guest_first_name', $results_guest[0]->billing_first_name );
						wcal_common::wcal_set_cart_session( 'guest_last_name', $results_guest[0]->billing_last_name );
						wcal_common::wcal_set_cart_session( 'guest_email', $results_guest[0]->email_id );
						wcal_common::wcal_set_cart_session( 'user_id', $user_id );
					} else {
						if ( version_compare( $woocommerce->version, '3.0.0', '>=' ) ) {
							wp_safe_redirect( get_permalink( wc_get_page_id( 'shop' ) ) );
							exit;
						} else {
							wp_safe_redirect( get_permalink( woocommerce_get_page_id( 'shop' ) ) );
							exit;
						}
					}
				}

				if ( $user_id < '63000000' ) {
					$user_login = $user->data->user_login;
					wp_set_auth_cookie( $user_id );
					$my_temp = wc_load_persistent_cart( $user_login, $user );
					do_action( 'wp_login', $user_login, $user );
					if ( isset( $sign_in ) && is_wp_error( $sign_in ) ) {
						echo esc_html( $sign_in->get_error_message() );
						exit;
					}
				} else {
					$my_temp = $this->wcal_load_guest_persistent_cart( $user_id );
				}

				if ( $email_sent_id > 0 && is_numeric( $email_sent_id ) ) {
					wp_safe_redirect( $url );
					exit;
				}
			} else {
				return $template;
			}
		}

		/**
		 * When customer clicks on the abandoned cart link and that cart is for the the guest users the it will load the guest
		 * user's cart detail.
		 *
		 * @globals mixed $woocommerce
		 * @since 1.0
		 */
		public function wcal_load_guest_persistent_cart() {
			if ( wcal_common::wcal_get_cart_session( 'user_id' ) != '' ) { // phpcs:ignore
				global $woocommerce;
				$saved_cart = json_decode( get_user_meta( wcal_common::wcal_get_cart_session( 'user_id' ), '_woocommerce_persistent_cart', true ), true );
				$c          = array();

				$cart_contents_total  = 0;
				$cart_contents_weight = 0;
				$cart_contents_count  = 0;
				$cart_contents_tax    = 0;
				$total                = 0;
				$subtotal             = 0;
				$subtotal_ex_tax      = 0;
				$tax_total            = 0;
				if ( count( $saved_cart ) > 0 ) {
					foreach ( $saved_cart as $key => $value ) {
						foreach ( $value as $a => $b ) {
							$c['product_id']        = $b['product_id'];
							$c['variation_id']      = $b['variation_id'];
							$c['variation']         = $b['variation'];
							$c['quantity']          = $b['quantity'];
							$product_id             = $b['product_id'];
							$c['data']              = wc_get_product( $product_id );
							$c['line_total']        = $b['line_total'];
							$c['line_tax']          = $cart_contents_tax;
							$c['line_subtotal']     = $b['line_subtotal'];
							$c['line_subtotal_tax'] = $cart_contents_tax;
							$value_new[ $a ]        = $c;
							$cart_contents_total    = $b['line_subtotal'] + $cart_contents_total;
							$cart_contents_count    = $cart_contents_count + $b['quantity'];
							$total                  = $total + $b['line_total'];
							$subtotal               = $subtotal + $b['line_subtotal'];
							$subtotal_ex_tax        = $subtotal_ex_tax + $b['line_subtotal'];
						}
						$saved_cart_data[ $key ] = $value_new;
						$woocommerce_cart_hash   = $a;
					}
				}

				if ( $saved_cart ) {
					if ( empty( $woocommerce->session->cart ) || ! is_array( $woocommerce->session->cart ) || 0 === count( $woocommerce->session->cart ) ) {
						$woocommerce->session->cart                 = $saved_cart['cart'];
						$woocommerce->session->cart_contents_total  = $cart_contents_total;
						$woocommerce->session->cart_contents_weight = $cart_contents_weight;
						$woocommerce->session->cart_contents_count  = $cart_contents_count;
						$woocommerce->session->cart_contents_tax    = $cart_contents_tax;
						$woocommerce->session->total                = $total;
						$woocommerce->session->subtotal             = $subtotal;
						$woocommerce->session->subtotal_ex_tax      = $subtotal_ex_tax;
						$woocommerce->session->tax_total            = $tax_total;
						$woocommerce->session->shipping_taxes       = array();
						$woocommerce->session->taxes                = array();
						$woocommerce->session->ac_customer          = array();
						$woocommerce->cart->cart_contents           = $saved_cart_data['cart'];
						$woocommerce->cart->cart_contents_total     = $cart_contents_total;
						$woocommerce->cart->cart_contents_weight    = $cart_contents_weight;
						$woocommerce->cart->cart_contents_count     = $cart_contents_count;
						$woocommerce->cart->cart_contents_tax       = $cart_contents_tax;
						$woocommerce->cart->total                   = $total;
						$woocommerce->cart->subtotal                = $subtotal;
						$woocommerce->cart->subtotal_ex_tax         = $subtotal_ex_tax;
						$woocommerce->cart->tax_total               = $tax_total;
					}
				}
			}
		}

		/**
		 * It will compare only guest users cart while capturing the cart.
		 *
		 * @param json_encode $new_cart New abandoned cart details.
		 * @param json_encode $last_abandoned_cart Old abandoned cart details.
		 * @return boolean true | false.
		 * @since 1.0
		 */
		public function wcal_compare_only_guest_carts( $new_cart, $last_abandoned_cart ) {
			$current_woo_cart   = array();
			$current_woo_cart   = json_decode( stripslashes( $new_cart ), true );
			$abandoned_cart_arr = array();
			$abandoned_cart_arr = json_decode( $last_abandoned_cart, true );
			$temp_variable      = '';
			if ( isset( $current_woo_cart['cart'] ) && isset( $abandoned_cart_arr['cart'] ) ) {
				if ( count( $current_woo_cart['cart'] ) >= count( $abandoned_cart_arr['cart'] ) ) { // phpcs:ignore
					// do nothing.
				} else {
					$temp_variable      = $current_woo_cart;
					$current_woo_cart   = $abandoned_cart_arr;
					$abandoned_cart_arr = $temp_variable;
				}
				if ( is_array( $current_woo_cart ) || is_object( $current_woo_cart ) ) {
					foreach ( $current_woo_cart as $key => $value ) {
						foreach ( $value as $item_key => $item_value ) {
							$current_cart_product_id   = $item_value['product_id'];
							$current_cart_variation_id = $item_value['variation_id'];
							$current_cart_quantity     = $item_value['quantity'];

							if ( isset( $abandoned_cart_arr[ $key ][ $item_key ]['product_id'] ) ) {
								$abandoned_cart_product_id = $abandoned_cart_arr[ $key ][ $item_key ]['product_id'];
							} else {
								$abandoned_cart_product_id = '';
							}
							if ( isset( $abandoned_cart_arr[ $key ][ $item_key ]['variation_id'] ) ) {
								$abandoned_cart_variation_id = $abandoned_cart_arr[ $key ][ $item_key ]['variation_id'];
							} else {
								$abandoned_cart_variation_id = '';
							}
							if ( isset( $abandoned_cart_arr[ $key ][ $item_key ]['quantity'] ) ) {
								$abandoned_cart_quantity = $abandoned_cart_arr[ $key ][ $item_key ]['quantity'];
							} else {
								$abandoned_cart_quantity = '';
							}
							if ( ( $current_cart_product_id != $abandoned_cart_product_id ) || ( $current_cart_variation_id != $abandoned_cart_variation_id ) || ( $current_cart_quantity != $abandoned_cart_quantity ) ) { // phpcs:ignore
									return false;
							}
						}
					}
				}
			}
			return true;
		}

		/**
		 * It will compare only loggedin users cart while capturing the cart.
		 *
		 * @param int | string $user_id User id.
		 * @param json_encode  $last_abandoned_cart Old abandoned cart details.
		 * @return boolean true | false.
		 * @since 1.0
		 */
		public function wcal_compare_carts( $user_id, $last_abandoned_cart ) {
			global $woocommerce;
			$current_woo_cart                 = array();
			$abandoned_cart_arr               = array();
			$wcal_woocommerce_persistent_cart = version_compare( $woocommerce->version, '3.1.0', '>=' ) ? '_woocommerce_persistent_cart_' . get_current_blog_id() : '_woocommerce_persistent_cart';
			$current_woo_cart                 = get_user_meta( $user_id, $wcal_woocommerce_persistent_cart, true );
			$abandoned_cart_arr               = json_decode( $last_abandoned_cart, true );
			$temp_variable                    = '';
			if ( isset( $current_woo_cart['cart'] ) && isset( $abandoned_cart_arr['cart'] ) ) {
				if ( count( $current_woo_cart['cart'] ) >= count( $abandoned_cart_arr['cart'] ) ) { // phpcs:ignore
					// do nothing.
				} else {
					$temp_variable      = $current_woo_cart;
					$current_woo_cart   = $abandoned_cart_arr;
					$abandoned_cart_arr = $temp_variable;
				}
				if ( is_array( $current_woo_cart ) && is_array( $abandoned_cart_arr ) ) {
					foreach ( $current_woo_cart as $key => $value ) {

						foreach ( $value as $item_key => $item_value ) {
							$current_cart_product_id   = $item_value['product_id'];
							$current_cart_variation_id = $item_value['variation_id'];
							$current_cart_quantity     = $item_value['quantity'];

							if ( isset( $abandoned_cart_arr[ $key ][ $item_key ]['product_id'] ) ) {
								$abandoned_cart_product_id = $abandoned_cart_arr[ $key ][ $item_key ]['product_id'];
							} else {
								$abandoned_cart_product_id = '';
							}
							if ( isset( $abandoned_cart_arr[ $key ][ $item_key ]['variation_id'] ) ) {
								$abandoned_cart_variation_id = $abandoned_cart_arr[ $key ][ $item_key ]['variation_id'];
							} else {
								$abandoned_cart_variation_id = '';
							}
							if ( isset( $abandoned_cart_arr[ $key ][ $item_key ]['quantity'] ) ) {
								$abandoned_cart_quantity = $abandoned_cart_arr[ $key ][ $item_key ]['quantity'];
							} else {
								$abandoned_cart_quantity = '';
							}
							if ( ( $current_cart_product_id != $abandoned_cart_product_id ) || ( $current_cart_variation_id != $abandoned_cart_variation_id ) || ( $current_cart_quantity != $abandoned_cart_quantity ) ) { // phpcs:ignore
								return false;
							}
						}
					}
				}
			}
			return true;
		}

		/**
		 * It will add the wp editor for email body on the email edit page.
		 *
		 * @hook admin_init
		 * @since 2.6
		 */
		public function wcal_action_admin_init() {

			// only hook up these filters if we're in the admin panel and the current user has permission.
			// to edit posts and pages.
			if ( ! isset( $_GET['page'] ) || 'woocommerce_ac_page' !== sanitize_text_field( wp_unslash( $_GET['page'] ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification
				return;
			}
			if ( ! current_user_can( 'edit_posts' ) && ! current_user_can( 'edit_pages' ) ) {
				return;
			}
			if ( 'true' === get_user_option( 'rich_editing' ) ) {
				remove_filter( 'the_excerpt', 'wpautop' );
				add_filter( 'tiny_mce_before_init', array( &$this, 'wcal_format_tiny_mce' ) );
				add_filter( 'mce_buttons', array( &$this, 'wcal_filter_mce_button' ) );
				add_filter( 'mce_external_plugins', array( &$this, 'wcal_filter_mce_plugin' ) );
			}
		}

		/**
		 * It will create a button on the WordPress editor.
		 *
		 * @hook mce_buttons
		 * @param array $buttons - List of buttons.
		 * @return array $buttons - List of buttons.
		 * @since 2.6
		 */
		public function wcal_filter_mce_button( $buttons ) {
			// add a separation before our button, here our button's id is abandoncart.
			array_push( $buttons, 'abandoncart', '|' );
			return $buttons;
		}

		/**
		 * It will add the list for the added extra button.
		 *
		 * @hook mce_external_plugins
		 * @param array $plugins - Plugins.
		 * @return array $plugins - Plugins.
		 * @since 2.6
		 */
		public function wcal_filter_mce_plugin( $plugins ) {
			// this plugin file will work the magic of our button.
			$plugins['abandoncart'] = plugin_dir_url( __FILE__ ) . 'assets/js/abandoncart_plugin_button.js';
			return $plugins;
		}

		/**
		 * It will add the tabs on the Abandoned cart page.
		 *
		 * @since 1.0
		 */
		public function wcal_display_tabs() {

			$action                = '';
			$active_listcart       = '';
			$active_emailtemplates = '';
			$active_settings       = '';
			$active_stats          = '';
			$active_dash           = '';

			if ( isset( $_GET['action'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
				$action = sanitize_text_field( wp_unslash( $_GET['action'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
			} else {
				$action = '';
				$action = apply_filters( 'wcal_default_tab', $action );
			}

			switch ( $action ) {
				case '':
				case 'dashboard':
					$active_dash = 'nav-tab-active';
					break;
				case 'listcart':
				case 'orderdetails':
					$active_listcart = 'nav-tab-active';
					break;
				case 'emailtemplates':
					$active_emailtemplates = 'nav-tab-active';
					break;
				case 'emailsettings':
					$active_settings = 'nav-tab-active';
					break;
				case 'stats':
					$active_stats = 'nav-tab-active';
					break;
				case 'report':
					$active_report = 'nav-tab-active';
					break;
			}

			?>
			<div style="background-image: url('<?php echo esc_url( plugins_url( '/assets/images/ac_tab_icon.png', __FILE__ ) ); ?>') !important;" class="icon32"><br>
			</div>
			<h2 class="nav-tab-wrapper woo-nav-tab-wrapper">
				<a href="admin.php?page=woocommerce_ac_page&action=dashboard" class="nav-tab 
				<?php
				if ( isset( $active_dash ) ) {
					echo esc_attr( $active_dash );
				}
				?>
				"> <?php esc_html_e( 'Dashboard', 'woocommerce-abandoned-cart' ); ?> </a>
				<a href="admin.php?page=woocommerce_ac_page&action=listcart" class="nav-tab 
				<?php
				if ( isset( $active_listcart ) ) {
					echo esc_attr( $active_listcart );
				}
				?>
				"> <?php esc_html_e( 'Abandoned Orders', 'woocommerce-abandoned-cart' ); ?> </a>
				<a href="admin.php?page=woocommerce_ac_page&action=emailtemplates" class="nav-tab 
				<?php
				if ( isset( $active_emailtemplates ) ) {
					echo esc_attr( $active_emailtemplates );
				}
				?>
				"> <?php esc_html_e( 'Email Templates', 'woocommerce-abandoned-cart' ); ?> </a>
				<a href="admin.php?page=woocommerce_ac_page&action=emailsettings" class="nav-tab 
				<?php
				if ( isset( $active_settings ) ) {
					echo esc_attr( $active_settings );
				}
				?>
				"> <?php esc_html_e( 'Settings', 'woocommerce-abandoned-cart' ); ?> </a>
				<a href="admin.php?page=woocommerce_ac_page&action=stats" class="nav-tab 
				<?php
				if ( isset( $active_stats ) ) {
					echo esc_attr( $active_stats );
				}
				?>
				"> <?php esc_html_e( 'Recovered Orders', 'woocommerce-abandoned-cart' ); ?> </a>
				<a href="admin.php?page=woocommerce_ac_page&action=report" class="nav-tab 
				<?php
				if ( isset( $active_report ) ) {
					echo esc_attr( $active_report );
				}
				?>
				"> <?php esc_html_e( 'Product Report', 'woocommerce-abandoned-cart' ); ?> </a>

				<?php do_action( 'wcal_add_settings_tab' ); ?>
			</h2>
			<?php
		}

		/**
		 * It will add the scripts needed for the plugin.
		 *
		 * @hook admin_enqueue_scripts
		 * @param string $hook Name of hook.
		 * @since 1.0
		 */
		public function wcal_enqueue_scripts_js( $hook ) {
			global $pagenow, $woocommerce;
			$page   = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
			$action = isset( $_GET['action'] ) ? sanitize_text_field( wp_unslash( $_GET['action'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification

			if ( '' === $page || 'woocommerce_ac_page' !== $page ) {
				return;
			} else {
				wp_enqueue_script( 'jquery' );
				wp_enqueue_script( 'jquery-ui-core' );

				wp_enqueue_script( 'jquery-ui-datepicker' );
				wp_enqueue_script(
					'jquery-tip',
					plugins_url( '/assets/js/jquery.tipTip.minified.js', __FILE__ ),
					'',
					WCAL_PLUGIN_VERSION,
					false
				);
				$mode = isset( $_GET['mode'] ) ? sanitize_text_field( wp_unslash( $_GET['mode'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
				if ( 'emailtemplates' === $action && ( 'addnewtemplate' === $mode || 'edittemplate' === $mode ) ) {
					wp_register_script( 'woocommerce_admin', WC()->plugin_url() . '/assets/js/admin/woocommerce_admin.min.js', array( 'jquery', 'jquery-tiptip' ), WCAL_PLUGIN_VERSION, false );
					wp_enqueue_script( 'woocommerce_admin' );
					$locale  = localeconv();
					$decimal = isset( $locale['decimal_point'] ) ? $locale['decimal_point'] : '.';
					$params  = array(
						// translators: %s: decimal.
						'i18n_decimal_error'               => sprintf( __( 'Please enter in decimal (%s) format without thousand separators.', 'woocommerce' ), $decimal ),
						// translators: %s: price decimal separator.
						'i18n_mon_decimal_error'           => sprintf( __( 'Please enter in monetary decimal (%s) format without thousand separators and currency symbols.', 'woocommerce' ), wc_get_price_decimal_separator() ),
						'i18n_country_iso_error'           => __( 'Please enter in country code with two capital letters.', 'woocommerce' ),
						'i18_sale_less_than_regular_error' => __( 'Please enter in a value less than the regular price.', 'woocommerce' ),
						'decimal_point'                    => $decimal,
						'mon_decimal_point'                => wc_get_price_decimal_separator(),
						'strings'                          => array(
							'import_products' => __( 'Import', 'woocommerce' ),
							'export_products' => __( 'Export', 'woocommerce' ),
						),
						'urls'                             => array(
							'import_products' => esc_url_raw( admin_url( 'edit.php?post_type=product&page=product_importer' ) ),
							'export_products' => esc_url_raw( admin_url( 'edit.php?post_type=product&page=product_exporter' ) ),
						),
					);

					// If we dont localize this script then from the WooCommerce check it will not run the javascript further and tooltip wont show any data.
					// Also, we need above all parameters for the WooCoomerce js file. So we have taken it from the WooCommerce. @since: 5.1.2.
					wp_localize_script( 'woocommerce_admin', 'woocommerce_admin', $params );
				}
				?>
				<script type="text/javascript" >
					function wcal_activate_email_template( template_id, active_state ) {
						location.href = 'admin.php?page=woocommerce_ac_page&action=emailtemplates&mode=activate_template&id='+template_id+'&active_state='+active_state ;
					}
				</script>
				<?php
				$js_src = includes_url( 'js/tinymce/' ) . 'tinymce.min.js';
				wp_enqueue_script( 'tinyMce_ac', $js_src, '', WCAL_PLUGIN_VERSION, false );
				wp_enqueue_script(
					'ac_email_variables',
					plugins_url( '/assets/js/abandoncart_plugin_button.js', __FILE__ ),
					'',
					WCAL_PLUGIN_VERSION,
					false
				);
				wp_enqueue_script(
					'wcal_activate_template',
					plugins_url( '/assets/js/wcal_template_activate.js', __FILE__ ),
					'',
					WCAL_PLUGIN_VERSION,
					false
				);

				// Needed only on the dashboard page.
				if ( 'woocommerce_ac_page' === $page && ( '' === $action || 'dashboard' === $action ) ) {
					wp_register_script( 'jquery-ui-datepicker', WC()->plugin_url() . '/assets/js/admin/ui-datepicker.js', '', WCAL_PLUGIN_VERSION, false );
					wp_enqueue_script( 'jquery-ui-datepicker' );

					wp_enqueue_script(
						'bootstrap_js',
						plugins_url( '/assets/js/admin/bootstrap.min.js', __FILE__ ),
						'',
						WCAL_PLUGIN_VERSION,
						false
					);

					wp_enqueue_script(
						'reports_js',
						plugins_url( '/assets/js/admin/wcal_adv_dashboard.min.js', __FILE__ ),
						'',
						WCAL_PLUGIN_VERSION,
						false
					);
				}
				// Needed only on the abandoned orders page.
				wp_enqueue_script(
					'wcal_abandoned_cart_details',
					plugins_url( '/assets/js/admin/wcal_abandoned_cart_detail_modal.min.js', __FILE__ ),
					'',
					WCAL_PLUGIN_VERSION,
					false
				);

				wp_enqueue_script(
					'wcal_admin_notices',
					plugins_url( '/assets/js/admin/wcal_ts_dismiss_notice.js', __FILE__ ),
					'',
					WCAL_PLUGIN_VERSION,
					false
				);
				wp_localize_script(
					'wcal_admin_notices',
					'wcal_dismiss_params',
					array(
						'ajax_url' => admin_url( 'admin-ajax.php' ),
					)
				);
			}
		}

		/**
		 * It will add the parameter to the editor.
		 *
		 * @hook tiny_mce_before_init
		 * @param array $in - Editor params.
		 * @return array $in - Editor params.
		 * @since 2.6
		 */
		public function wcal_format_tiny_mce( $in ) {
			$in['force_root_block']             = false;
			$in['valid_children']               = '+body[style]';
			$in['remove_linebreaks']            = false;
			$in['gecko_spellcheck']             = false;
			$in['keep_styles']                  = true;
			$in['accessibility_focus']          = true;
			$in['tabfocus_elements']            = 'major-publishing-actions';
			$in['media_strict']                 = false;
			$in['paste_remove_styles']          = false;
			$in['paste_remove_spans']           = false;
			$in['paste_strip_class_attributes'] = 'none';
			$in['paste_text_use_dialog']        = true;
			$in['wpeditimage_disable_captions'] = true;
			$in['wpautop']                      = false;
			$in['apply_source_formatting']      = true;
			$in['cleanup']                      = true;
			$in['convert_newlines_to_brs']      = false;
			$in['fullpage_default_xml_pi']      = false;
			$in['convert_urls']                 = false;
			// Do not remove redundant BR tags.
			$in['remove_redundant_brs'] = false;
			return $in;
		}

		/**
		 * It will add the necesaary css for the plugin.
		 *
		 * @hook admin_enqueue_scripts
		 * @param string $hook Name of page.
		 * @since 1.0
		 */
		public function wcal_enqueue_scripts_css( $hook ) {

			global $pagenow;

			$page   = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
			$action = isset( $_GET['action'] ) ? sanitize_text_field( wp_unslash( $_GET['action'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification

			if ( 'woocommerce_ac_page' !== $page ) {
				return;
			} elseif ( 'woocommerce_ac_page' === $page && ( 'dashboard' === $action || '' === $action ) ) {
				wp_enqueue_style( 'wcal-dashboard-adv', plugins_url( '/assets/css/admin/wcal_reports_adv.css', __FILE__ ), '', WCAL_PLUGIN_VERSION );

				wp_register_style( 'bootstrap_css', plugins_url( '/assets/css/admin/bootstrap.min.css', __FILE__ ), '', WCAL_PLUGIN_VERSION, 'all' );
				wp_enqueue_style( 'bootstrap_css' );

				wp_enqueue_style( 'wcal-font-awesome', plugins_url( '/assets/css/admin/font-awesome.css', __FILE__ ), '', WCAL_PLUGIN_VERSION );

				wp_enqueue_style( 'wcal-font-awesome-min', plugins_url( '/assets/css/admin/font-awesome.min.css', __FILE__ ), '', WCAL_PLUGIN_VERSION );

				wp_enqueue_style( 'jquery-ui', plugins_url( '/assets/css/admin/jquery-ui.css', __FILE__ ), '', WCAL_PLUGIN_VERSION, false );
				wp_enqueue_style( 'woocommerce_admin_styles', WC()->plugin_url() . '/assets/css/admin.css', '', WCAL_PLUGIN_VERSION );
				wp_enqueue_style( 'jquery-ui-style', plugins_url( '/assets/css/admin/jquery-ui-smoothness.css', __FILE__ ), '', WCAL_PLUGIN_VERSION );
				wp_enqueue_style( 'wcal-reports', plugins_url( '/assets/css/admin/wcal_reports.min.css', __FILE__ ), '', WCAL_PLUGIN_VERSION );

			} elseif ( 'woocommerce_ac_page' === $page ) {

				wp_enqueue_style( 'jquery-ui', plugins_url( '/assets/css/admin/jquery-ui.css', __FILE__ ), '', WCAL_PLUGIN_VERSION, false );
				wp_enqueue_style( 'woocommerce_admin_styles', WC()->plugin_url() . '/assets/css/admin.css', '', WCAL_PLUGIN_VERSION );

				wp_enqueue_style( 'jquery-ui-style', plugins_url( '/assets/css/admin/jquery-ui-smoothness.css', __FILE__ ), '', WCAL_PLUGIN_VERSION );
				wp_enqueue_style( 'abandoned-orders-list', plugins_url( '/assets/css/view.abandoned.orders.style.css', __FILE__ ), '', WCAL_PLUGIN_VERSION );
				wp_enqueue_style( 'wcal_email_template', plugins_url( '/assets/css/wcal_template_activate.css', __FILE__ ), '', WCAL_PLUGIN_VERSION );
				wp_enqueue_style( 'wcal_cart_details', plugins_url( '/assets/css/admin/wcal_abandoned_cart_detail_modal.min.css', __FILE__ ), '', WCAL_PLUGIN_VERSION );
			}
		}

		/**
		 * When we have added the wp list table for the listing then while deleting the record with the bulk action it was showing
		 * the notice. To overcome the wp redirect warning we need to start the ob_start.
		 *
		 * @hook init
		 * @since 2.5.2
		 */
		public function wcal_app_output_buffer() {
			ob_start();
		}

		/**
		 * Abandon Cart Settings Page. It will show the tabs, notices for the plugin.
		 * It will also update the template records and display the template fields.
		 * It will also show the abandoned cart details page.
		 * It will also show the details of all the tabs.
		 *
		 * @globals mixed $wpdb
		 * @since 1.0
		 */
		public function wcal_menu_page() {

			if ( is_user_logged_in() ) {
				global $wpdb;
				// Check the user capabilities.
				if ( ! current_user_can( 'manage_woocommerce' ) ) {
					wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'woocommerce-abandoned-cart' ) );
				}
				?>
				<div class="wrap">
				<h2><?php esc_html_e( 'WooCommerce - Abandon Cart Lite', 'woocommerce-abandoned-cart' ); ?></h2>
				<?php

				if ( isset( $_GET['ac_update'] ) && 'email_templates' === sanitize_text_field( wp_unslash( $_GET['ac_update'] ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification
					$status = wcal_common::update_templates_table();

					if ( false !== $status ) {
						wcal_common::show_update_success();
					} else {
						wcal_common::show_update_failure();
					}
				}

				if ( isset( $_GET['action'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
					$action = sanitize_text_field( wp_unslash( $_GET['action'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
				} else {
					$action = '';
					$action = apply_filters( 'wcal_default_tab', $action );
				}
				$mode = isset( $_GET['mode'] ) ? sanitize_text_field( wp_unslash( $_GET['mode'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
				$this->wcal_display_tabs();

				do_action( 'wcal_add_tab_content' );

				// When we delete the item from the below drop down it is registred in action 2.
				$action_two = isset( $_GET['action2'] ) ? sanitize_text_field( wp_unslash( $_GET['action2'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification

				// Detect when a bulk action is being triggered on abandoned orders page.
				if ( 'wcal_delete' === $action || 'wcal_delete' === $action_two ) {
					$ids = isset( $_GET['abandoned_order_id'] ) && is_array( $_GET['abandoned_order_id'] ) ? array_map( 'intval', wp_unslash( $_GET['abandoned_order_id'] ) ) : sanitize_text_field( wp_unslash( $_GET['abandoned_order_id'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
					if ( ! is_array( $ids ) ) {
						$ids = array( $ids );
					}
					foreach ( $ids as $id ) {
						$class = new Wcal_Delete_Handler();
						$class->wcal_delete_bulk_action_handler_function( $id );
					}
				}
				// Abandoned Orders page - Bulk Action - Delete all registered user carts.
				if ( 'wcal_delete_all_registered' === $action || 'wcal_delete_all_registered' === $action_two ) {
					$class = new Wcal_Delete_Handler();
					$class->wcal_bulk_action_delete_registered_carts_handler();
				}
				// Abandoned Orders page - Bulk Action - Delete all guest carts.
				if ( 'wcal_delete_all_guest' === $action || 'wcal_delete_all_guest' === $action_two ) {
					$class = new Wcal_Delete_Handler();
					$class->wcal_bulk_action_delete_guest_carts_handler();
				}
				// Abandoned Orders page - Bulk Action - Delete all visitor carts.
				if ( 'wcal_delete_all_visitor' === $action || 'wcal_delete_all_visitor' === $action_two ) {
					$class = new Wcal_Delete_Handler();
					$class->wcal_bulk_action_delete_visitor_carts_handler();
				}
				// Abandoned Orders page - Bulk Action - Delete all carts.
				if ( 'wcal_delete_all' === $action || 'wcal_delete_all' === $action_two ) {
					$class = new Wcal_Delete_Handler();
					$class->wcal_bulk_action_delete_all_carts_handler();
				}

				// Detect when a bulk action is being triggered on templates page.
				if ( 'wcal_delete_template' === $action || 'wcal_delete_template' === $action_two ) {
					$ids = isset( $_GET['template_id'] ) && is_array( $_GET['template_id'] ) ? array_map( 'intval', wp_unslash( $_GET['template_id'] ) ) : sanitize_text_field( wp_unslash( $_GET['template_id'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
					if ( ! is_array( $ids ) ) {
						$ids = array( $ids );
					}
					foreach ( $ids as $id ) {
						$class = new Wcal_Delete_Handler();
						$class->wcal_delete_template_bulk_action_handler_function( $id );
					}
				}

				if ( isset( $_GET['wcal_deleted'] ) && 'YES' === sanitize_text_field( wp_unslash( $_GET['wcal_deleted'] ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification
					$msg = __( 'The Abandoned cart has been successfully deleted.', 'woocommerce-abandoned-cart' ); // Default Msg.
					if ( isset( $_GET['wcal_deleted_all'] ) && 'YES' === sanitize_text_field( wp_unslash( $_GET['wcal_deleted_all'] ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification
						$msg = __( 'All Abandoned Carts have been successfully deleted.', 'woocommerce-abandoned-cart' ); // Delete All Carts.
					} elseif ( isset( $_GET['wcal_deleted_all_visitor'] ) && 'YES' === sanitize_text_field( wp_unslash( $_GET['wcal_deleted_all_visitor'] ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification
						$msg = __( 'All Visitor carts have been successfully deleted.', 'woocommerce-abandoned-cart' ); // Delete all visitor carts.
					} elseif ( isset( $_GET['wcal_deleted_all_guest'] ) && 'YES' === sanitize_text_field( wp_unslash( $_GET['wcal_deleted_all_guest'] ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification
						$msg = __( 'All Guest carts have been successfully deleted.', 'woocommerce-abandoned-cart' ); // Delete all Guest carts.
					} elseif ( isset( $_GET['wcal_deleted_all_registered'] ) && 'YES' === sanitize_text_field( wp_unslash( $_GET['wcal_deleted_all_registered'] ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification
						$msg = __( 'All Registered carts have been deleted.', 'woocommerce-abandoned-cart' ); // Delete all registered carts.
					}
					?>
					<div id="message" class="updated fade">
						<p><strong><?php echo esc_html( $msg ); ?></strong></p>
					</div>
					<?php
				}
				if ( isset( $_GET ['wcal_template_deleted'] ) && 'YES' === sanitize_text_field( wp_unslash( $_GET['wcal_template_deleted'] ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification
					?>
					<div id="message" class="updated fade">
						<p><strong><?php esc_html_e( 'The Template has been successfully deleted.', 'woocommerce-abandoned-cart' ); ?></strong></p>
					</div>
					<?php
				}
				if ( 'emailsettings' === $action ) {
					// Save the field values.
					?>
					<p><?php esc_html_e( 'Change settings for sending email notifications to Customers, to Admin etc.', 'woocommerce-abandoned-cart' ); ?></p>
					<div id="content">
					<?php
					$wcal_general_settings_class = '';
					$wcal_email_setting          = '';
					$wcap_sms_settings           = '';
					$wcap_atc_settings           = '';
					$wcap_fb_settings            = '';

					$section = isset( $_GET['wcal_section'] ) ? sanitize_text_field( wp_unslash( $_GET['wcal_section'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
					switch ( $section ) {
						case 'wcal_general_settings':
						case '':
							$wcal_general_settings_class = 'current';
							break;
						case 'wcal_email_settings':
							$wcal_email_setting = 'current';
							break;
						case 'wcap_sms_settings':
							$wcap_sms_settings = 'current';
							break;
						case 'wcap_atc_settings':
							$wcap_atc_settings = 'current';
							break;
						case 'wcap_fb_settings':
							$wcap_fb_settings = 'current';
							break;
						default:
							$wcal_general_settings_class = 'current';
							break;
					}
					?>
						<ul class="subsubsub" id="wcal_general_settings_list">
							<li>
								<a href="admin.php?page=woocommerce_ac_page&action=emailsettings&wcal_section=wcal_general_settings" class="<?php echo esc_attr( $wcal_general_settings_class ); ?>"><?php esc_html_e( 'General Settings', 'woocommerce-abandoned-cart' ); ?> </a> |
							</li>
							<li>
								<a href="admin.php?page=woocommerce_ac_page&action=emailsettings&wcal_section=wcal_email_settings" class="<?php echo esc_attr( $wcal_email_setting ); ?>"><?php esc_html_e( 'Email Sending Settings', 'woocommerce-abandoned-cart' ); ?> </a> |
							</li>
							<li>
								<a href="admin.php?page=woocommerce_ac_page&action=emailsettings&wcal_section=wcap_atc_settings" class="<?php echo esc_attr( $wcap_atc_settings ); ?>"><?php esc_html_e( 'Add To Cart Popup Editor', 'woocommerce-ac' ); ?> </a> |
							</li>
							<li>
								<a href="admin.php?page=woocommerce_ac_page&action=emailsettings&wcal_section=wcap_fb_settings" class="<?php echo esc_attr( $wcap_fb_settings ); ?>"><?php esc_html_e( 'Facebook Messenger', 'woocommerce-ac' ); ?> </a> |
							</li>
							<li>
								<a href="admin.php?page=woocommerce_ac_page&action=emailsettings&wcal_section=wcap_sms_settings" class="<?php echo esc_attr( $wcap_sms_settings ); ?>"><?php esc_html_e( 'SMS', 'woocommerce-ac' ); ?> </a>
							</li>
						</ul>
						<br class="clear">
						<?php
						if ( 'wcal_general_settings' === $section || '' === $section ) {
							?>
							<form method="post" action="options.php">
								<?php settings_fields( 'woocommerce_ac_settings' ); ?>
								<?php do_settings_sections( 'woocommerce_ac_page' ); ?>
								<?php settings_errors(); ?>
								<?php submit_button(); ?>
							</form>
							<?php
						} elseif ( 'wcal_email_settings' === $section ) {
							?>
							<form method="post" action="options.php">
								<?php settings_fields( 'woocommerce_ac_email_settings' ); ?>
								<?php do_settings_sections( 'woocommerce_ac_email_page' ); ?>
								<?php settings_errors(); ?>
								<?php submit_button(); ?>
							</form>
							<?php
						} elseif ( 'wcap_atc_settings' === $section ) {
							WCAP_Pro_Settings::wcap_atc_settings();
						} elseif ( 'wcap_fb_settings' === $section ) {
							WCAP_Pro_Settings::wcap_fb_settings();
						} elseif ( 'wcap_sms_settings' === $section ) {
							WCAP_Pro_Settings::wcap_sms_settings();
						}
						?>
					</div>
					<?php
				} elseif ( 'dashboard' === $action || '' === $action || '-1' === $action || '1' === $action_two ) {
					include_once 'includes/classes/class-wcal-dashboard-report.php';
					Wcal_Dashboard_Report::wcal_dashboard_display();
				} elseif ( 'listcart' === $action ) {

					?>
						<p> <?php esc_html_e( 'The list below shows all Abandoned Carts which have remained in cart for a time higher than the "Cart abandoned cut-off time" setting.', 'woocommerce-abandoned-cart' ); ?> </p>
						<?php
						$get_all_abandoned_count      = wcal_common::wcal_get_abandoned_order_count( 'wcal_all_abandoned' );
						$get_registered_user_ac_count = wcal_common::wcal_get_abandoned_order_count( 'wcal_all_registered' );
						$get_guest_user_ac_count      = wcal_common::wcal_get_abandoned_order_count( 'wcal_all_guest' );
						$get_visitor_user_ac_count    = wcal_common::wcal_get_abandoned_order_count( 'wcal_all_visitor' );

						$wcal_user_reg_text = 'User';
						if ( $get_registered_user_ac_count > 1 ) {
							$wcal_user_reg_text = 'Users';
						}
						$wcal_user_gus_text = 'User';
						if ( $get_guest_user_ac_count > 1 ) {
							$wcal_user_gus_text = 'Users';
						}
						$wcal_all_abandoned_carts = '';
						$section                  = '';
						$wcal_all_registered      = '';
						$wcal_all_guest           = '';
						$wcal_all_visitor         = '';

						$section = isset( $_GET['wcal_section'] ) ? sanitize_text_field( wp_unslash( $_GET['wcal_section'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
						if ( 'wcal_all_abandoned' === $section || '' === $section ) {
							$wcal_all_abandoned_carts = 'current';
						}

						if ( 'wcal_all_registered' === $section ) {
							$wcal_all_registered      = 'current';
							$wcal_all_abandoned_carts = '';
						}
						if ( 'wcal_all_guest' === $section ) {
							$wcal_all_guest           = 'current';
							$wcal_all_abandoned_carts = '';
						}

						if ( 'wcal_all_visitor' === $section ) {
							$wcal_all_visitor         = 'current';
							$wcal_all_abandoned_carts = '';
						}
						?>
						<ul class="subsubsub" id="wcal_recovered_orders_list">
							<li>
								<a href="admin.php?page=woocommerce_ac_page&action=listcart&wcal_section=wcal_all_abandoned" class="<?php echo esc_attr( $wcal_all_abandoned_carts ); ?>"><?php esc_html_e( 'All ', 'woocommerce-abandoned-cart' ); ?> <span class = "count" > <?php echo esc_html( "( $get_all_abandoned_count )" ); ?> </span></a>
							</li>

						<?php if ( $get_registered_user_ac_count > 0 ) { ?>
							<li><?php // translators: Users. ?>
								| <a href="admin.php?page=woocommerce_ac_page&action=listcart&wcal_section=wcal_all_registered" class="<?php echo esc_attr( $wcal_all_registered ); ?>"><?php printf( esc_html__( 'Registered %s', 'woocommerce-abandoned-cart' ), esc_html( $wcal_user_reg_text ) ); ?> <span class = "count" > <?php echo esc_html( "( $get_registered_user_ac_count )" ); ?> </span></a>
							</li>
							<?php } ?>

						<?php if ( $get_guest_user_ac_count > 0 ) { ?>
							<li><?php // translators: Users. ?>
								| <a href="admin.php?page=woocommerce_ac_page&action=listcart&wcal_section=wcal_all_guest" class="<?php echo esc_attr( $wcal_all_guest ); ?>"><?php printf( esc_html__( 'Guest %s', 'woocommerce-abandoned-cart' ), esc_html( $wcal_user_gus_text ) ); ?> <span class = "count" > <?php echo esc_html( "( $get_guest_user_ac_count )" ); ?> </span></a>
							</li>
							<?php } ?>

						<?php if ( $get_visitor_user_ac_count > 0 ) { ?>
							<li>
								| <a href="admin.php?page=woocommerce_ac_page&action=listcart&wcal_section=wcal_all_visitor" class="<?php echo esc_attr( $wcal_all_visitor ); ?>"><?php esc_html_e( 'Carts without Customer Details', 'woocommerce-abandoned-cart' ); ?> <span class = "count" > <?php echo esc_html( "( $get_visitor_user_ac_count )" ); ?> </span></a>
							</li>
							<?php } ?>
						</ul>

						<?php
						global $wpdb;
						include_once 'includes/classes/class-wcal-abandoned-orders-table.php';
						$wcal_abandoned_order_list = new WCAL_Abandoned_Orders_Table();
						$wcal_abandoned_order_list->wcal_abandoned_order_prepare_items();
						?>
						<div class="wrap">
							<form id="wcal-abandoned-orders" method="get" >
								<input type="hidden" name="page" value="woocommerce_ac_page" />
								<input type="hidden" name="action" value="listcart" />
							<?php $wcal_abandoned_order_list->display(); ?>
							</form>
						</div>
						<?php
				} elseif ( ( 'emailtemplates' === $action && ( 'edittemplate' !== $mode && 'addnewtemplate' !== $mode ) || '' === $action || '-1' === $action || '-1' === $action_two ) ) {
					?>
					<p> <?php esc_html_e( 'Add email templates at different intervals to maximize the possibility of recovering your abandoned carts.', 'woocommerce-abandoned-cart' ); ?> </p>
					<?php
					// Save the field values.
					$insert_template_successfuly  = '';
					$update_template_successfuly  = '';
					$woocommerce_ac_email_subject = isset( $_POST['woocommerce_ac_email_subject'] ) ? trim( htmlspecialchars( sanitize_text_field( wp_unslash( $_POST['woocommerce_ac_email_subject'] ) ) ), ENT_QUOTES ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
					$woocommerce_ac_email_body    = isset( $_POST['woocommerce_ac_email_body'] ) ? trim( wp_unslash( $_POST['woocommerce_ac_email_body'] ) ) : ''; // phpcs:ignore
					$woocommerce_ac_template_name = isset( $_POST['woocommerce_ac_template_name'] ) ? trim( sanitize_text_field( wp_unslash( $_POST['woocommerce_ac_template_name'] ) ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
					$woocommerce_ac_email_header  = isset( $_POST['wcal_wc_email_header'] ) ? stripslashes( trim( htmlspecialchars( sanitize_text_field( wp_unslash( $_POST['wcal_wc_email_header'] ) ) ), ENT_QUOTES ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification

					$email_frequency = isset( $_POST['email_frequency'] ) ? trim( sanitize_text_field( wp_unslash( $_POST['email_frequency'] ) ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
					$day_or_hour     = isset( $_POST['day_or_hour'] ) ? trim( sanitize_text_field( wp_unslash( $_POST['day_or_hour'] ) ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
					$is_wc_template  = empty( $_POST['is_wc_template'] ) ? '0' : '1'; // phpcs:ignore WordPress.Security.NonceVerification

					if ( isset( $_POST['ac_settings_frm'] ) && 'save' === sanitize_text_field( wp_unslash( $_POST['ac_settings_frm'] ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification
						$default_value = 0;

						$insert_template_successfuly = $wpdb->query( //phpcs:ignore
							$wpdb->prepare(
								'INSERT INTO `' . $wpdb->prefix . 'ac_email_templates_lite` (subject, body, frequency, day_or_hour, template_name, is_wc_template, default_template, wc_email_header ) VALUES ( %s, %s, %d, %s, %s, %s, %d, %s )',
								$woocommerce_ac_email_subject,
								$woocommerce_ac_email_body,
								$email_frequency,
								$day_or_hour,
								$woocommerce_ac_template_name,
								$is_wc_template,
								$default_value,
								$woocommerce_ac_email_header
							)
						);
					}

					if ( isset( $_POST['ac_settings_frm'] ) && 'update' === sanitize_text_field( wp_unslash( $_POST['ac_settings_frm'] ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification

						$updated_is_active = '0';
						$id                = isset( $_POST['id'] ) ? trim( sanitize_text_field( wp_unslash( $_POST['id'] ) ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification

						$check_results = $wpdb->get_results( //phpcs:ignore
							$wpdb->prepare(
								'SELECT * FROM `' . $wpdb->prefix . 'ac_email_templates_lite` WHERE id = %d',
								$id
							)
						);
						$default_value = '';

						if ( count( $check_results ) > 0 ) {
							if ( isset( $check_results[0]->default_template ) && '1' === $check_results[0]->default_template ) {
								$default_value = '1';
							}
						}

						$update_template_successfuly = $wpdb->query( //phpcs:ignore
							$wpdb->prepare(
								'UPDATE `' . $wpdb->prefix . 'ac_email_templates_lite` SET subject = %s, body = %s, frequency = %d, day_or_hour = %s, template_name = %s, is_wc_template = %s, default_template = %d, wc_email_header = %s WHERE id = %d',
								$woocommerce_ac_email_subject,
								$woocommerce_ac_email_body,
								$email_frequency,
								$day_or_hour,
								$woocommerce_ac_template_name,
								$is_wc_template,
								$default_value,
								$woocommerce_ac_email_header,
								$id
							)
						);
					}

					if ( 'emailtemplates' === $action && 'removetemplate' === $mode ) {
						$id_remove = isset( $_GET['id'] ) ? sanitize_text_field( wp_unslash( $_GET['id'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
						$wpdb->query( //phpcs:ignore
							$wpdb->prepare(
								'DELETE FROM `' . $wpdb->prefix . 'ac_email_templates_lite` WHERE id= %d ',
								$id_remove
							)
						);
					}

					if ( 'emailtemplates' === $action && 'activate_template' === $mode ) {
						$template_id             = isset( $_GET['id'] ) ? sanitize_text_field( wp_unslash( $_GET['id'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
						$current_template_status = isset( $_GET['active_state'] ) ? sanitize_text_field( wp_unslash( $_GET['active_state'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification

						if ( '1' === $current_template_status ) {
							$active = '0';
						} else {
							$active                       = '1';
							$get_selected_template_result = $wpdb->get_results( // phpcs:ignore
								$wpdb->prepare(
									'SELECT * FROM `' . $wpdb->prefix . 'ac_email_templates_lite` WHERE id = %d',
									$template_id
								)
							);

							$email_frequncy    = $get_selected_template_result[0]->frequency;
							$email_day_or_hour = $get_selected_template_result[0]->day_or_hour;
							$wcap_updated      = $wpdb->query( // phpcs:ignore
								$wpdb->prepare(
									'UPDATE `' . $wpdb->prefix . 'ac_email_templates_lite` SET is_active = %s WHERE frequency = %s AND day_or_hour = %s',
									0,
									$email_frequncy,
									$email_day_or_hour
								)
							);
						}

						$wpdb->query( // phpcs:ignore
							$wpdb->prepare(
								'UPDATE `' . $wpdb->prefix . 'ac_email_templates_lite` SET is_active = %s WHERE id = %s',
								$active,
								$template_id
							)
						);

						wp_safe_redirect( admin_url( '/admin.php?page=woocommerce_ac_page&action=emailtemplates' ) );
					}

					if ( isset( $_POST['ac_settings_frm'] ) && 'save' === $_POST['ac_settings_frm'] && ( isset( $insert_template_successfuly ) && '' !== $insert_template_successfuly ) ) { // phpcs:ignore WordPress.Security.NonceVerification
						?>
						<div id="message" class="updated fade">
							<p>
								<strong>
									<?php esc_html_e( 'The Email Template has been successfully added. In order to start sending this email to your customers, please activate it.', 'woocommerce-abandoned-cart' ); ?>
								</strong>
							</p>
						</div>
						<?php
					} elseif ( isset( $_POST['ac_settings_frm'] ) && 'save' === $_POST['ac_settings_frm'] && ( isset( $insert_template_successfuly ) && '' === $insert_template_successfuly ) ) { // phpcs:ignore WordPress.Security.NonceVerification
						?>
						<div id="message" class="error fade">
							<p>
								<strong>
								<?php esc_html_e( 'There was a problem adding the email template. Please contact the plugin author via <a href= "https://wordpress.org/support/plugin/woocommerce-abandoned-cart">support forum</a>.', 'woocommerce-abandoned-cart' ); ?>
								</strong>
							</p>
						</div>
						<?php
					}

					if ( isset( $_POST['ac_settings_frm'] ) && 'update' === $_POST['ac_settings_frm'] && isset( $update_template_successfuly ) && false !== $update_template_successfuly ) { // phpcs:ignore WordPress.Security.NonceVerification
						?>
						<div id="message" class="updated fade">
							<p>
								<strong>
								<?php esc_html_e( 'The Email Template has been successfully updated.', 'woocommerce-abandoned-cart' ); ?>
								</strong>
							</p>
						</div>
						<?php
					} elseif ( isset( $_POST['ac_settings_frm'] ) && 'update' === $_POST['ac_settings_frm'] && isset( $update_template_successfuly ) && false === $update_template_successfuly ) { // phpcs:ignore WordPress.Security.NonceVerification
						?>
						<div id="message" class="error fade">
							<p>
								<strong>
								<?php esc_html_e( 'There was a problem updating the email template. Please contact the plugin author via <a href= "https://wordpress.org/support/plugin/woocommerce-abandoned-cart">support forum</a>.', 'woocommerce-abandoned-cart' ); ?>
								</strong>
							</p>
						</div>
						<?php
					}
					?>
					<div class="tablenav">
						<p style="float:left;">
							<a cursor: pointer; href="<?php echo esc_url( 'admin.php?page=woocommerce_ac_page&action=emailtemplates&mode=addnewtemplate' ); ?>" class="button-secondary"><?php esc_html_e( 'Add New Template', 'woocommerce-abandoned-cart' ); ?></a>
						</p>

						<?php
						// From here you can do whatever you want with the data from the $result link.
						include_once 'includes/classes/class-wcal-templates-table.php';
						$wcal_template_list = new WCAL_Templates_Table();
						$wcal_template_list->wcal_templates_prepare_items();
						?>
						<div class="wrap">
							<form id="wcal-abandoned-templates" method="get" >
								<input type="hidden" name="page" value="woocommerce_ac_page" />
								<input type="hidden" name="action" value="emailtemplates" />
								<?php $wcal_template_list->display(); ?>
							</form>
						</div>
					</div>
					<?php
				} elseif ( 'stats' === $action || '' === $action ) {
					?>
					<p>
					<script language='javascript'>
						jQuery( document ).ready( function() {
							jQuery( '#duration_select' ).change( function() {
								var group_name = jQuery( '#duration_select' ).val();
								var today      = new Date();
								var start_date = "";
								var end_date   = "";
								if ( group_name == "yesterday" ) {
									start_date = new Date( today.getFullYear(), today.getMonth(), today.getDate() - 1 );
									end_date   = new Date( today.getFullYear(), today.getMonth(), today.getDate() - 1 );
								} else if ( group_name == "today") {
									start_date = new Date( today.getFullYear(), today.getMonth(), today.getDate() );
									end_date   = new Date( today.getFullYear(), today.getMonth(), today.getDate() );
								} else if ( group_name == "last_seven" ) {
									start_date = new Date( today.getFullYear(), today.getMonth(), today.getDate() - 7 );
									end_date   = new Date( today.getFullYear(), today.getMonth(), today.getDate() );
								} else if ( group_name == "last_fifteen" ) {
									start_date = new Date( today.getFullYear(), today.getMonth(), today.getDate() - 15 );
									end_date   = new Date( today.getFullYear(), today.getMonth(), today.getDate() );
								} else if ( group_name == "last_thirty" ) {
									start_date = new Date( today.getFullYear(), today.getMonth(), today.getDate() - 30 );
									end_date   = new Date( today.getFullYear(), today.getMonth(), today.getDate() );
								} else if ( group_name == "last_ninety" ) {
									start_date = new Date( today.getFullYear(), today.getMonth(), today.getDate() - 90 );
									end_date   = new Date( today.getFullYear(), today.getMonth(), today.getDate() );
								} else if ( group_name == "last_year_days" ) {
									start_date = new Date( today.getFullYear(), today.getMonth(), today.getDate() - 365 );
									end_date   = new Date( today.getFullYear(), today.getMonth(), today.getDate() );
								}

								var monthNames = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];

								var start_date_value = start_date.getDate() + " " + monthNames[start_date.getMonth()] + " " + start_date.getFullYear();
								var end_date_value   = end_date.getDate() + " " + monthNames[end_date.getMonth()] + " " + end_date.getFullYear();

								jQuery( '#start_date' ).val( start_date_value );
								jQuery( '#end_date' ).val( end_date_value );
							} );
						});
					</script>
					<?php
					$duration_range = isset( $_POST['duration_select'] ) ? sanitize_text_field( wp_unslash( $_POST['duration_select'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
					if ( '' === $duration_range ) {
						if ( isset( $_GET['duration_select'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
							$duration_range = sanitize_text_field( wp_unslash( $_GET['duration_select'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
						}
					}
					if ( '' === $duration_range ) {
						$duration_range = 'last_seven';
					}

					echo esc_html_e( 'The Report below shows how many Abandoned Carts we were able to recover for you by sending automatic emails to encourage shoppers.', 'woocommerce-abandoned-cart' );
					?>
					<div id="recovered_stats" class="postbox" style="display:block">
						<div class="inside">
							<form method="post" action="admin.php?page=woocommerce_ac_page&action=stats" id="ac_stats">
								<select id="duration_select" name="duration_select" >
									<?php
									foreach ( $this->duration_range_select as $key => $value ) {
										$sel = '';
										if ( $key == $duration_range ) { // phpcs:ignore
											$sel = ' selected ';
										}
										printf(
											'<option value="%s" %s> %s </option>',
											esc_attr( $key ),
											esc_attr( $sel ),
											esc_attr( $value )
										);
									}
									$date_sett = $this->start_end_dates[ $duration_range ];
									?>
								</select>
								<script type="text/javascript">
									jQuery( document ).ready( function() {
										var formats = ["d.m.y", "d M yy","MM d, yy"];
										jQuery( "#start_date" ).datepicker( { dateFormat: formats[1] } );
									});

									jQuery( document ).ready( function()
									{
										var formats = ["d.m.y", "d M yy","MM d, yy"];
										jQuery( "#end_date" ).datepicker( { dateFormat: formats[1] } );
									});
								</script>
								<?php
								include_once 'includes/classes/class-wcal-recover-orders-table.php';
								$wcal_recover_orders_list = new Wcal_Recover_Orders_Table();
								$wcal_recover_orders_list->wcal_recovered_orders_prepare_items();

								$start_date_range = isset( $_POST['start_date'] ) ? sanitize_text_field( wp_unslash( $_POST['start_date'] ) ) : '';// phpcs:ignore WordPress.Security.NonceVerification

								if ( '' === $start_date_range ) {
									$start_date_range = $date_sett['start_date'];
								}

								$end_date_range = isset( $_POST['end_date'] ) ? sanitize_text_field( wp_unslash( $_POST['end_date'] ) ) : '';// phpcs:ignore WordPress.Security.NonceVerification

								if ( '' === $end_date_range ) {
									$end_date_range = $date_sett['end_date'];
								}
								?>
								<label class="start_label" for="start_day"> <?php esc_html_e( 'Start Date:', 'woocommerce-abandoned-cart' ); ?> </label>
								<input type="text" id="start_date" name="start_date" readonly="readonly" value="<?php echo esc_attr( $start_date_range ); ?>"/>
								<label class="end_label" for="end_day"> <?php esc_html_e( 'End Date:', 'woocommerce-abandoned-cart' ); ?> </label>
								<input type="text" id="end_date" name="end_date" readonly="readonly" value="<?php echo esc_attr( $end_date_range ); ?>"/>
								<input type="submit" name="Submit" class="button-primary" value="<?php esc_attr_e( 'Go', 'woocommerce-abandoned-cart' ); ?>"  />
							</form>
						</div>
					</div>
					<div id="recovered_stats" class="postbox" style="display:block">
						<div class="inside" >
							<?php
							$count              = $wcal_recover_orders_list->total_abandoned_cart_count;
							$total_of_all_order = $wcal_recover_orders_list->total_order_amount;
							$recovered_item     = $wcal_recover_orders_list->recovered_item;
							$recovered_total    = wc_price( $wcal_recover_orders_list->total_recover_amount );
							?>
							<p style="font-size: 15px;">
								<?php
								printf(
									// translators: All counts of items & amounts.
									wp_kses_post(
										// translators: Abandoned & recovered numbers and order totals.
										__( 'During the selected range <strong>%1$d</strong> carts totaling <strong>%2$s</strong> were abandoned. We were able to recover <strong>%3$d</strong> of them, which led to an extra <strong>%4$s</strong>', 'woocommerce-abandoned-cart' )
									),
									esc_attr( $count ),
									wp_kses_post( $total_of_all_order ),
									esc_attr( $recovered_item ),
									wp_kses_post( $recovered_total )
								);
								?>
							</p>
						</div>
					</div>
					<div class="wrap">
						<form id="wcal-recover-orders" method="get" >
							<input type="hidden" name="page" value="woocommerce_ac_page" />
							<input type="hidden" name="action" value="stats" />
							<?php $wcal_recover_orders_list->display(); ?>
						</form>
					</div>
					<?php
				} elseif ( 'orderdetails' === $action ) {
					global $woocommerce;
					$ac_order_id = isset( $_GET['id'] ) ? sanitize_text_field( wp_unslash( $_GET['id'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
					?>
					<div id="ac_order_details" class="postbox" style="display:block">
					<?php // translators: Abandoned Order ID. ?>
						<h3 class="details-title"> <p> <?php printf( esc_html__( 'Abandoned Order #%s Details', 'woocommerce-abandoned-cart' ), esc_attr( $ac_order_id ) ); ?> </p> </h3>
						<div class="inside">
							<table cellpadding="0" cellspacing="0" class="wp-list-table widefat fixed posts">
								<tr>
									<th> <?php esc_html_e( 'Item', 'woocommerce-abandoned-cart' ); ?> </th>
									<th> <?php esc_html_e( 'Name', 'woocommerce-abandoned-cart' ); ?> </th>
									<th> <?php esc_html_e( 'Quantity', 'woocommerce-abandoned-cart' ); ?> </th>
									<th> <?php esc_html_e( 'Line Subtotal', 'woocommerce-abandoned-cart' ); ?> </th>
									<th> <?php esc_html_e( 'Line Total', 'woocommerce-abandoned-cart' ); ?> </th>
								</tr>
							<?php
							$results = $wpdb->get_results( // phpcs:ignore
								$wpdb->prepare(
									'SELECT * FROM `' . $wpdb->prefix . 'ac_abandoned_cart_history_lite` WHERE id = %d',
									sanitize_text_field( wp_unslash( $_GET['id'] ) ) // phpcs:ignore WordPress.Security.NonceVerification
								)
							);

							$shipping_charges = 0;
							$currency_symbol  = get_woocommerce_currency_symbol();
							$number_decimal   = wc_get_price_decimals();
							if ( 'GUEST' === $results[0]->user_type && $results[0]->user_id > 0 ) {
								$results_guest = $wpdb->get_results( // phpcs:ignore
									$wpdb->prepare(
										'SELECT * FROM `' . $wpdb->prefix . 'ac_guest_abandoned_cart_history_lite` WHERE id = %d',
										$results[0]->user_id
									)
								);

								$user_email             = '';
								$user_first_name        = '';
								$user_last_name         = '';
								$user_billing_postcode  = '';
								$user_shipping_postcode = '';
								$shipping_charges       = '';
								if ( count( $results_guest ) > 0 ) {
									$user_email             = $results_guest[0]->email_id;
									$user_first_name        = $results_guest[0]->billing_first_name;
									$user_last_name         = $results_guest[0]->billing_last_name;
									$user_billing_postcode  = $results_guest[0]->billing_zipcode;
									$user_shipping_postcode = $results_guest[0]->shipping_zipcode;
									$shipping_charges       = $results_guest[0]->shipping_charges;
								}
								$user_billing_company   = '';
								$user_billing_address_1 = '';
								$user_billing_address_2 = '';
								$user_billing_city      = '';
								$user_billing_state     = '';
								$user_billing_country   = '';
								$user_billing_phone     = '';

								$user_shipping_company   = '';
								$user_shipping_address_1 = '';
								$user_shipping_address_2 = '';
								$user_shipping_city      = '';
								$user_shipping_state     = '';
								$user_shipping_country   = '';
							} elseif ( 'GUEST' === $results[0]->user_type && $results[0]->user_id > 0 ) {
								$user_email              = '';
								$user_first_name         = 'Visitor';
								$user_last_name          = '';
								$user_billing_postcode   = '';
								$user_shipping_postcode  = '';
								$shipping_charges        = '';
								$user_billing_phone      = '';
								$user_billing_company    = '';
								$user_billing_address_1  = '';
								$user_billing_address_2  = '';
								$user_billing_city       = '';
								$user_billing_state      = '';
								$user_billing_country    = '';
								$user_shipping_company   = '';
								$user_shipping_address_1 = '';
								$user_shipping_address_2 = '';
								$user_shipping_city      = '';
								$user_shipping_state     = '';
								$user_shipping_country   = '';
							} else {
								$user_id = $results[0]->user_id;
								if ( isset( $results[0]->user_login ) ) {
									$user_login = $results[0]->user_login;
								}
								$user_email = get_user_meta( $results[0]->user_id, 'billing_email', true );
								if ( '' == $user_email ) { // phpcs:ignore
									$user_data = get_userdata( $results[0]->user_id );
									if ( isset( $user_data->user_email ) ) {
										$user_email = $user_data->user_email;
									} else {
										$user_email = '';
									}
								}

								$user_first_name      = '';
								$user_first_name_temp = get_user_meta( $user_id, 'billing_first_name', true );
								if ( isset( $user_first_name_temp ) && '' == $user_first_name_temp ) { // phpcs:ignore
									$user_data = get_userdata( $user_id );
									if ( isset( $user_data->first_name ) ) {
										$user_first_name = $user_data->first_name;
									} else {
										$user_first_name = '';
									}
								} else {
									$user_first_name = $user_first_name_temp;
								}
								$user_last_name      = '';
								$user_last_name_temp = get_user_meta( $user_id, 'billing_last_name', true );
								if ( isset( $user_last_name_temp ) && '' == $user_last_name_temp ) { // phpcs:ignore
									$user_data = get_userdata( $user_id );
									if ( isset( $user_data->last_name ) ) {
										$user_last_name = $user_data->last_name;
									} else {
										$user_last_name = '';
									}
								} else {
									$user_last_name = $user_last_name_temp;
								}
								$user_billing_first_name = get_user_meta( $results[0]->user_id, 'billing_first_name' );
								$user_billing_last_name  = get_user_meta( $results[0]->user_id, 'billing_last_name' );

								$user_billing_details = wcal_common::wcal_get_billing_details( $results[0]->user_id );

								$user_billing_company   = $user_billing_details['billing_company'];
								$user_billing_address_1 = $user_billing_details['billing_address_1'];
								$user_billing_address_2 = $user_billing_details['billing_address_2'];
								$user_billing_city      = $user_billing_details['billing_city'];
								$user_billing_postcode  = $user_billing_details['billing_postcode'];
								$user_billing_country   = $user_billing_details['billing_country'];
								$user_billing_state     = $user_billing_details['billing_state'];

								$user_billing_phone_temp = get_user_meta( $results[0]->user_id, 'billing_phone' );
								if ( isset( $user_billing_phone_temp[0] ) ) {
									$user_billing_phone = $user_billing_phone_temp[0];
								} else {
									$user_billing_phone = '';
								}
								$user_shipping_first_name   = get_user_meta( $results[0]->user_id, 'shipping_first_name' );
								$user_shipping_last_name    = get_user_meta( $results[0]->user_id, 'shipping_last_name' );
								$user_shipping_company_temp = get_user_meta( $results[0]->user_id, 'shipping_company' );
								if ( isset( $user_shipping_company_temp[0] ) ) {
									$user_shipping_company = $user_shipping_company_temp[0];
								} else {
									$user_shipping_company = '';
								}
								$user_shipping_address_1_temp = get_user_meta( $results[0]->user_id, 'shipping_address_1' );
								if ( isset( $user_shipping_address_1_temp[0] ) ) {
									$user_shipping_address_1 = $user_shipping_address_1_temp[0];
								} else {
									$user_shipping_address_1 = '';
								}
								$user_shipping_address_2_temp = get_user_meta( $results[0]->user_id, 'shipping_address_2' );
								if ( isset( $user_shipping_address_2_temp[0] ) ) {
									$user_shipping_address_2 = $user_shipping_address_2_temp[0];
								} else {
									$user_shipping_address_2 = '';
								}
								$user_shipping_city_temp = get_user_meta( $results[0]->user_id, 'shipping_city' );
								if ( isset( $user_shipping_city_temp[0] ) ) {
									$user_shipping_city = $user_shipping_city_temp[0];
								} else {
									$user_shipping_city = '';
								}
								$user_shipping_postcode_temp = get_user_meta( $results[0]->user_id, 'shipping_postcode' );
								if ( isset( $user_shipping_postcode_temp[0] ) ) {
									$user_shipping_postcode = $user_shipping_postcode_temp[0];
								} else {
									$user_shipping_postcode = '';
								}
								$user_shipping_country_temp = get_user_meta( $results[0]->user_id, 'shipping_country' );
								$user_shipping_country      = '';
								if ( isset( $user_shipping_country_temp[0] ) ) {
									$user_shipping_country = $user_shipping_country_temp[0];
									if ( isset( $woocommerce->countries->countries[ $user_shipping_country ] ) ) {
										$user_shipping_country = $woocommerce->countries->countries[ $user_shipping_country ];
									} else {
										$user_shipping_country = '';
									}
								}
								$user_shipping_state_temp = get_user_meta( $results[0]->user_id, 'shipping_state' );
								$user_shipping_state      = '';
								if ( isset( $user_shipping_state_temp[0] ) ) {
									$user_shipping_state = $user_shipping_state_temp[0];
									if ( isset( $woocommerce->countries->states[ $user_shipping_country_temp[0] ][ $user_shipping_state ] ) ) {
										// code...
										$user_shipping_state = $woocommerce->countries->states[ $user_shipping_country_temp[0] ][ $user_shipping_state ];
									}
								}
							}

							$cart_details  = array();
							$cart_info     = json_decode( $results[0]->abandoned_cart_info );
							$cart_details  = (array) $cart_info->cart;
							$item_subtotal = 0;
							$item_total    = 0;

							if ( is_array( $cart_details ) && count( $cart_details ) > 0 ) {
								foreach ( $cart_details as $k => $v ) {

									$item_details = wcal_common::wcal_get_cart_details( $v );

									$product_id = $v->product_id;
									$product    = wc_get_product( $product_id );
									if ( ! $product ) { // product not found, exclude it from the cart display.
										continue;
									}
									$prod_image       = $product->get_image( array( 200, 200 ) );
									$product_page_url = get_permalink( $product_id );
									$product_name     = $item_details['product_name'];
									$item_subtotal    = $item_details['item_total_formatted'];
									$item_total       = $item_details['item_total'];
									$quantity_total   = $item_details['qty'];

									$qty_item_text = 'item';
									if ( $quantity_total > 1 ) {
										$qty_item_text = 'items';
									}
									?>
									<tr>
										<td> <?php echo $prod_image; // phpcs:ignore ?></td>
										<td> <?php echo '<a href="' . esc_url( $product_page_url ) . '"> ' . esc_html( $product_name ) . ' </a>'; ?> </td>
										<td> <?php echo esc_html( $quantity_total ); ?></td>
										<td> <?php echo esc_html( $item_subtotal ); ?></td>
										<td> <?php echo esc_html( $item_total ); ?></td>
									</tr>
									<?php
									$item_subtotal = 0;
									$item_total    = 0;
								}
							}
							?>
						</table>
					</div>
				</div>
				<div id="ac_order_customer_details" class="postbox" style="display:block">
					<h3 class="details-title"> <p> <?php esc_html_e( 'Customer Details', 'woocommerce-abandoned-cart' ); ?> </p> </h3>
							<div class="inside" style="height: 300px;" >
								<div id="order_data" class="panel">
									<div style="width:50%;float:left">
										<h3> <p> <?php esc_html_e( 'Billing Details', 'woocommerce-abandoned-cart' ); ?> </p> </h3>
										<p> <strong> <?php esc_html_e( 'Name:', 'woocommerce-abandoned-cart' ); ?> </strong>
										<?php echo esc_html( "$user_first_name $user_last_name" ); ?>
										</p>
										<p> <strong> <?php esc_html_e( 'Address:', 'woocommerce-abandoned-cart' ); ?> </strong>
										<?php
											echo esc_html( $user_billing_company ) . '</br>' .
											esc_html( $user_billing_address_1 ) . '</br>' .
											esc_html( $user_billing_address_2 ) . '</br>' .
											esc_html( $user_billing_city ) . '</br>' .
											esc_html( $user_billing_postcode ) . '</br>' .
											esc_html( $user_billing_state ) . '</br>' .
											esc_html( $user_billing_country ) . '</br>';
										?>
										</p>
										<p> <strong> <?php esc_html_e( 'Email:', 'woocommerce-abandoned-cart' ); ?> </strong>
											<?php $user_mail_to = 'mailto:' . $user_email; ?>
											<a href=<?php echo esc_url( $user_mail_to ); ?>><?php echo esc_html( $user_email ); ?> </a>
										</p>
										<p> <strong> <?php esc_html_e( 'Phone:', 'woocommerce-abandoned-cart' ); ?> </strong>
											<?php echo esc_html( $user_billing_phone ); ?>
										</p>
									</div>
									<div style="width:50%;float:right">
										<h3> <p> <?php esc_html_e( 'Shipping Details', 'woocommerce-abandoned-cart' ); ?> </p> </h3>
										<p> <strong> <?php esc_html_e( 'Address:', 'woocommerce-abandoned-cart' ); ?> </strong>
											<?php
											if ( '' === $user_shipping_company &&
												'' === $user_shipping_address_1 &&
												'' === $user_shipping_address_2 &&
												'' === $user_shipping_city &&
												'' === $user_shipping_postcode &&
												'' === $user_shipping_state &&
												'' === $user_shipping_country ) {
												echo esc_html_e( 'Shipping Address same as Billing Address', 'woocommerce-abandoned-cart' );
											} else {
												?>
												<?php
												echo esc_html( $user_shipping_company ) . '</br>' .
												esc_html( $user_shipping_address_1 ) . '</br>' .
												esc_html( $user_shipping_address_2 ) . '</br>' .
												esc_html( $user_shipping_city ) . '</br>' .
												esc_html( $user_shipping_postcode ) . '</br>' .
												esc_html( $user_shipping_state ) . '</br>' .
												esc_html( $user_shipping_country ) . '</br>';
												?>
												<br><br>
												<strong><?php esc_html_e( 'Shipping Charges', 'woocommerce-abandoned-cart' ); ?>: </strong>
												<?php
												if ( $shipping_charges > 0 ) {
													echo wp_kses_post( $currency_symbol . $shipping_charges );
												}
												?>
										</p>
											<?php } ?>
									</div>
								</div>
							</div>
						</div>
					<?php
				} elseif ( 'report' === $action ) {

						include_once 'includes/classes/class-wcal-product-report-table.php';
						$wcal_product_report_list = new WCAL_Product_Report_Table();
						$wcal_product_report_list->wcal_product_report_prepare_items();
					?>
						<div class="wrap">
							<form id="wcal-sent-emails" method="get" >
								<input type="hidden" name="page" value="woocommerce_ac_page" />
								<input type="hidden" name="action" value="report" />
								<?php $wcal_product_report_list->display(); ?>
							</form>
						</div>
					<?php
				}
			}
			echo( '</table>' );

			$action = isset( $_GET['action'] ) ? sanitize_text_field( wp_unslash( $_GET['action'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
			$mode   = isset( $_GET['mode'] ) ? sanitize_text_field( wp_unslash( $_GET['mode'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification

			$edit_id = 0;
			if ( 'emailtemplates' === $action && ( 'addnewtemplate' === $mode || 'edittemplate' === $mode ) ) {
				if ( 'edittemplate' === $mode ) {
					$results = array();
					if ( isset( $_GET['id'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
						$edit_id = sanitize_text_field( wp_unslash( $_GET['id'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
						$results = $wpdb->get_results( // phpcs:ignore
							$wpdb->prepare(
								'SELECT wpet . *  FROM `' . $wpdb->prefix . 'ac_email_templates_lite` AS wpet WHERE id = %d',
								$edit_id
							)
						);
					}
				}
				$active_post = ( empty( $_POST['is_active'] ) ) ? '0' : '1'; // phpcs:ignore WordPress.Security.NonceVerification
				?>
				<div id="content">
					<form method="post" action="admin.php?page=woocommerce_ac_page&action=emailtemplates" id="ac_settings">
					<input type="hidden" name="mode" value="<?php echo esc_html( $mode ); ?>" />
						<?php
						$id_by = isset( $_GET['id'] ) ? sanitize_text_field( wp_unslash( $_GET['id'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
						?>
						<input type="hidden" name="id" value="<?php echo esc_html( $id_by ); ?>" />
						<?php
						if ( 'edittemplate' === $mode ) {
							print '<input type="hidden" name="ac_settings_frm" value="update">';
							$display_message = 'Edit Email Template';
						} else {
							print '<input type="hidden" name="ac_settings_frm" value="save">';
							$display_message = 'Add Email Template';
						}
						?>
						<div id="poststuff">
							<div> <!-- <div class="postbox" > -->
								<h3 class="hndle"><?php esc_html_e( $display_message, 'woocommerce-abandoned-cart' ); // phpcs:ignore?></h3>
								<div>
									<table class="form-table" id="addedit_template">
									<tr>
										<th>
											<label for="woocommerce_ac_template_name"><b><?php esc_html_e( 'Template Name:', 'woocommerce-abandoned-cart' ); ?></b></label>
										</th>
										<td>
											<?php
											$template_name = '';
											if ( 'edittemplate' === $mode && count( $results ) > 0 && isset( $results[0]->template_name ) ) {
												$template_name = $results[0]->template_name;
											}
											print '<input type="text" name="woocommerce_ac_template_name" id="woocommerce_ac_template_name" class="regular-text" value="' . esc_html( $template_name ) . '">';
											?>
											<img class="help_tip" width="16" height="16" data-tip='<?php esc_html_e( 'Enter a template name for reference', 'woocommerce-abandoned-cart' ); ?>' src="<?php echo esc_url( WC()->plugin_url() ); ?>/assets/images/help.png" />
										</td>
									</tr>

									<tr>
										<th>
											<label for="woocommerce_ac_email_subject"><b><?php esc_html_e( 'Subject:', 'woocommerce-abandoned-cart' ); ?></b></label>
										</th>
										<td>
											<?php
											$subject_edit = '';
											if ( 'edittemplate' === $mode && count( $results ) > 0 && isset( $results[0]->subject ) ) {
												$subject_edit = stripslashes( $results[0]->subject );
											}
											print '<input type="text" name="woocommerce_ac_email_subject" id="woocommerce_ac_email_subject" class="regular-text" value="' . esc_html( $subject_edit ) . '">';
											?>
											<img class="help_tip" width="16" height="16" data-tip='<?php esc_html_e( 'Enter the subject that should appear in the email sent', 'woocommerce-abandoned-cart' ); ?>' src="<?php echo esc_url( WC()->plugin_url() ); ?>/assets/images/help.png" />
										</td>
									</tr>

									<tr>
										<th>
											<label for="woocommerce_ac_email_body"><b><?php esc_html_e( 'Email Body:', 'woocommerce-abandoned-cart' ); ?></b></label>
										</th>
										<td>
											<?php
											$initial_data = '';
											if ( 'edittemplate' === $mode && count( $results ) > 0 && isset( $results[0]->body ) ) {
												$initial_data = stripslashes( $results[0]->body );
											}

											$initial_data = str_replace( 'My document title', '', $initial_data );
											wp_editor(
												$initial_data,
												'woocommerce_ac_email_body',
												array(
													'media_buttons' => true,
													'textarea_rows' => 15,
													'tabindex'      => 4,
													'tinymce'       => array(
														'theme_advanced_buttons1' => 'bold,italic,underline,|,bullist,numlist,blockquote,|,link,unlink,|,spellchecker,fullscreen,|,formatselect,styleselect',
													),
												)
											);

											?>
											<?php echo wp_kses_post( stripslashes( get_option( 'woocommerce_ac_email_body' ) ) ); ?>
											<span class="description">
												<?php
												esc_html_e( 'Message to be sent in the reminder email.', 'woocommerce-abandoned-cart' );
												?>
												<img width="16" height="16" src="<?php echo esc_url( plugins_url() ); ?>/woocommerce-abandoned-cart/assets/images/information.png" onClick="wcal_show_help_tips()"/>
											</span>
											<span id="help_message" style="display:none">
												1. You can add customer & cart information in the template using this icon <img width="20" height="20" src="<?php echo esc_url( plugins_url( '/assets/images/ac_editor_icon.png', __FILE__ ) ); ?>" /> in top left of the editor.<br>
												2. The product information/cart contents table will be added in emails using the {{products.cart}} merge field.<br>
												3. Insert/Remove any of the new shortcodes that have been included for the default template.<br>
												4. Change the look and feel of the table by modifying the table style properties using CSS in "Text" mode. <br>
												5. Change the text color of the table rows by using the Toolbar of the editor. <br>

											</span>
										</td>
									</tr>
									<script type="text/javascript">
										function wcal_show_help_tips() {
											if ( jQuery( '#help_message' ) . css( 'display' ) == 'none') {
												document.getElementById( "help_message" ).style.display = "block";
											}
											else {
												document.getElementById( "help_message" ) . style.display = "none";
											}
										}
									</script>

									<tr>
										<th>
											<label for="is_wc_template"><b><?php esc_html_e( 'Use WooCommerce Template Style:', 'woocommerce-abandoned-cart' ); ?></b></label>
										</th>
										<td>
											<?php
											$is_wc_template = '';
											if ( 'edittemplate' === $mode && count( $results ) > 0 && isset( $results[0]->is_wc_template ) ) {
												$use_wc_template = $results[0]->is_wc_template;

												if ( '1' === $use_wc_template ) {
													$is_wc_template = 'checked';
												} else {
													$is_wc_template = '';
												}
											}
											print '<input type="checkbox" name="is_wc_template" id="is_wc_template" ' . esc_attr( $is_wc_template ) . '>  </input>';
											?>
											<img class="help_tip" width="16" height="16" data-tip='<?php esc_html_e( 'Use WooCommerce default style template for abandoned cart reminder emails.', 'woocommerce' ); ?>' src="<?php echo esc_url( WC()->plugin_url() ); ?>/assets/images/help.png" /><a target = '_blank' href= <?php echo esc_url( wp_nonce_url( admin_url( "?wcal_preview_woocommerce_mail=true&id=$edit_id" ), 'woocommerce-abandoned-cart' ) ); ?> >
											Click here to preview </a>how the email template will look with WooCommerce Template Style enabled. Alternatively, if this is unchecked, the template will appear as <a target = '_blank' href=<?php echo esc_url( wp_nonce_url( admin_url( "?wcal_preview_mail=true&id=$edit_id" ), 'woocommerce-abandoned-cart' ) ); ?>>shown here</a>. <br> <strong>Note: </strong>When this setting is enabled, then "Send From This Name:" & "Send From This Email Address:" will be overwritten with WooCommerce -> Settings -> Email -> Email Sender Options.
										</td>
									</tr>

									<tr>
										<th>
											<label for="wcal_wc_email_header"><b><?php esc_html_e( 'Email Template Header Text: ', 'woocommerce-abandoned-cart' ); ?></b></label>
										</th>
										<td>

										<?php

										$wcal_wc_email_header = '';
										if ( 'edittemplate' === $mode && count( $results ) > 0 && isset( $results[0]->wc_email_header ) ) {
											$wcal_wc_email_header = $results[0]->wc_email_header;
										}
										if ( '' === $wcal_wc_email_header ) {
											$wcal_wc_email_header = 'Abandoned cart reminder';
										}
										print '<input type="text" name="wcal_wc_email_header" id="wcal_wc_email_header" class="regular-text" value="' . esc_html( $wcal_wc_email_header ) . '">';
										?>
										<img class="help_tip" width="16" height="16" data-tip='<?php esc_html_e( 'Enter the header which will appear in the abandoned WooCommerce email sent. This is only applicable when only used when "Use WooCommerce Template Style:" is checked.', 'woocommerce-abandoned-cart' ); ?>' src="<?php echo esc_url( WC()->plugin_url() ); ?>/assets/images/help.png" />
										</td>
									</tr>

									<tr>
										<th>
											<label for="woocommerce_ac_email_frequency"><b><?php esc_html_e( 'Send this email:', 'woocommerce-abandoned-cart' ); ?></b></label>
										</th>
										<td>
											<select name="email_frequency" id="email_frequency">
											<?php
												$frequency_edit = '';
											if ( 'edittemplate' === $mode && count( $results ) > 0 && isset( $results[0]->frequency ) ) {
												$frequency_edit = $results[0]->frequency;
											}
											for ( $i = 1; $i < 4; $i++ ) {
												printf(
													"<option %s value='%s'>%s</option>\n",
													selected( $i, $frequency_edit, false ),
													esc_attr( $i ),
													esc_html( $i )
												);
											}
											?>
											</select>

											<select name="day_or_hour" id="day_or_hour">
												<?php
												$days_or_hours_edit = '';
												if ( 'edittemplate' === $mode && count( $results ) > 0 && isset( $results[0]->day_or_hour ) ) {
													$days_or_hours_edit = $results[0]->day_or_hour;
												}
												$days_or_hours = array(
													'Days' => 'Day(s)',
													'Hours' => 'Hour(s)',
												);
												foreach ( $days_or_hours as $k => $v ) {
													printf(
														"<option %s value='%s'>%s</option>\n",
														selected( $k, $days_or_hours_edit, false ),
														esc_attr( $k ),
														esc_html( $v )
													);
												}
												?>
											</select>
											<span class="description">
											<?php esc_html_e( 'after cart is abandoned.', 'woocommerce-abandoned-cart' ); ?>
											</span>
										</td>
									</tr>

									<tr>
										<th>
											<label for="woocommerce_ac_email_preview"><b><?php esc_html_e( 'Send a test email to:', 'woocommerce-abandoned-cart' ); ?></b></label>
										</th>
										<td>
											<input type="text" id="send_test_email" name="send_test_email" class="regular-text" >
											<input type="button" value="Send a test email" id="preview_email" onclick="javascript:void(0);">
											<img class="help_tip" width="16" height="16" data-tip='<?php esc_html_e( 'Enter the email id to which the test email needs to be sent.', 'woocommerce-abandoned-cart' ); ?>' src="<?php echo esc_url( WC()->plugin_url() ); ?>/assets/images/help.png" />
											<br>
											<img class="ajax_img" src="<?php echo esc_url( plugins_url( '/assets/images/ajax-loader.gif', __FILE__ ) ); ?>" style="display:none;" />
											<div id="preview_email_sent_msg" style="display:none;"></div>
										</td>
									</tr>
								</table>
							</div>
						</div>
					</div>
						<p class="submit">
						<?php
						if ( 'edittemplate' === $mode ) {
							?>
							<input type="submit" name="Submit" class="button-primary" value="<?php esc_attr_e( 'Update Changes', 'woocommerce-abandoned-cart' ); ?>"  />
							<?php
						} else {
							?>
							<input type="submit" name="Submit" class="button-primary" value="<?php esc_attr_e( 'Save Changes', 'woocommerce-abandoned-cart' ); ?>"  />
							<?php
						}
						?>
						</p>
					</form>
				</div>
				<?php
			}
		}

		/**
		 * It will add the footer text for the plugin.
		 *
		 * @hook admin_footer_text
		 * @param string $footer_text Text.
		 * @return string $footer_text
		 * @since 1.0
		 */
		public function wcal_admin_footer_text( $footer_text ) {

			if ( isset( $_GET['page'] ) && 'woocommerce_ac_page' === $_GET['page'] ) { // phpcs:ignore WordPress.Security.NonceVerification
				$footer_text = __( 'If you love <strong>Abandoned Cart Lite for WooCommerce</strong>, then please leave us a <a href="https://wordpress.org/support/plugin/woocommerce-abandoned-cart/reviews/?rate=5#new-post" target="_blank" class="ac-rating-link" data-rated="Thanks :)">★★★★★</a> rating. Thank you in advance. :)', 'woocommerce-abandoned-cart' );
				wc_enqueue_js(
					"
						jQuery( 'a.ac-rating-link' ).click( function() {
							jQuery( this ).parent().text( jQuery( this ).data( 'rated' ) );
						});
				"
				);
			}
			return $footer_text;
		}

		/**
		 * It will sort the record for the product reports tab.
		 *
		 * @param array  $unsort_array Unsorted array.
		 * @param string $order Order details.
		 * @return array $array
		 * @since 2.6
		 */
		public function bubble_sort_function( $unsort_array, $order ) {
			$temp = array();
			foreach ( $unsort_array as $key => $value ) {
				$temp[ $key ] = $value; // concatenate something unique to make sure two equal weights don't overwrite each other.
			}
			asort( $temp, SORT_NUMERIC ); // or ksort( $temp, SORT_NATURAL ); see paragraph above to understand why.

			if ( 'desc' === $order ) {
				$array = array_reverse( $temp, true );
			} elseif ( 'asc' === $order ) {
				$array = $temp;
			}
			unset( $temp );
			return $array;
		}

		/**
		 * It will be called when we send the test email from the email edit page.
		 *
		 * @hook wp_ajax_wcal_preview_email_sent
		 * @since 1.0
		 */
		public function wcal_action_send_preview() {
			?>
			<script type="text/javascript" >
				jQuery( document ).ready( function( $ )
				{
					$( "table#addedit_template input#preview_email" ).click( function()
					{
						$( '.ajax_img' ).show();
						var email_body = '';
						if ( jQuery("#wp-woocommerce_ac_email_body-wrap").hasClass( "tmce-active" ) ) {
							email_body = tinyMCE.get('woocommerce_ac_email_body').getContent();
						} else {
							email_body = jQuery('#woocommerce_ac_email_body').val();
						}
						var subject_email_preview = $( '#woocommerce_ac_email_subject' ).val();
						var body_email_preview    = email_body;
						var send_email_id         = $( '#send_test_email' ).val();
						var is_wc_template        = document.getElementById( "is_wc_template" ).checked;
						var wc_template_header    = $( '#wcal_wc_email_header' ).val() != '' ? $( '#wcal_wc_email_header' ).val() : 'Abandoned cart reminder';
						var data                  = {
														subject_email_preview: subject_email_preview,
														body_email_preview   : body_email_preview,
														send_email_id        : send_email_id,
														is_wc_template       : is_wc_template,
														wc_template_header   : wc_template_header,
														action               : 'wcal_preview_email_sent'
													};

						// since 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php
						$.post( ajaxurl, data, function( response ) {
							$( '.ajax_img' ).hide();
							if ( 'not sent' == response ) {
								$( "#preview_email_sent_msg" ).html( "Test email is not sent as the Email body is empty." );
								$( "#preview_email_sent_msg" ).fadeIn();
									setTimeout( function(){$( "#preview_email_sent_msg" ).fadeOut();}, 4000 );
							} else {
								$( "#preview_email_sent_msg" ).html( "<img src='<?php echo esc_url( plugins_url( '/assets/images/check.jpg', __FILE__ ) ); ?>'>&nbsp;Email has been sent successfully." );
									$( "#preview_email_sent_msg" ).fadeIn();
									setTimeout( function(){$( "#preview_email_sent_msg" ).fadeOut();}, 3000 );
							}
						});
					});
				});
				</script>
				<?php
		}

		/**
		 * Ajax function used to add the details in the abandoned order
		 * popup view.
		 *
		 * @since 5.6
		 */
		public static function wcal_abandoned_cart_info() {
			Wcal_Abandoned_Cart_Details::wcal_get_cart_detail_view( $_POST ); // phpcs:ignore WordPress.Security.NonceVerification
			die();
		}

		/**
		 * Ajax function which will save the notice state as dismissed.
		 *
		 * @since 5.7
		 */
		public static function wcal_dismiss_admin_notice() {

			$notice_key = isset( $_POST['notice'] ) ? sanitize_text_field( wp_unslash( $_POST['notice'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
			if ( '' !== $notice_key ) {
				update_option( $notice_key, true );
			}
			die();
		}

		/**
		 * It will update the template satus when we change the template active status from the email template list page.
		 *
		 * @hook wp_ajax_wcal_toggle_template_status
		 * @globals mixed $wpdb
		 * @since 4.4
		 */
		public static function wcal_toggle_template_status() {
			global $wpdb;
			$template_id             = isset( $_POST['wcal_template_id'] ) ? sanitize_text_field( wp_unslash( $_POST['wcal_template_id'] ) ) : 0; // phpcs:ignore WordPress.Security.NonceVerification
			$current_template_status = isset( $_POST['current_state'] ) ? sanitize_text_field( wp_unslash( $_POST['current_state'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification

			if ( $template_id > 0 ) {
				if ( 'on' === $current_template_status ) {
					$get_selected_template_result = $wpdb->get_results( // phpcs:ignore
						$wpdb->prepare(
							'SELECT * FROM `' . $wpdb->prefix . 'ac_email_templates_lite` WHERE id = %d',
							$template_id
						)
					);
					$email_frequncy               = $get_selected_template_result[0]->frequency;
					$email_day_or_hour            = $get_selected_template_result[0]->day_or_hour;

					$wcal_updated = $wpdb->query( // phpcs:ignore
						$wpdb->prepare(
							'UPDATE `' . $wpdb->prefix . 'ac_email_templates_lite` SET is_active = %d WHERE frequency = %s AND day_or_hour = %s',
							0,
							$email_frequncy,
							$email_day_or_hour
						)
					);

					if ( 1 === $wcal_updated ) {
						$wcal_updated_get_id = $wpdb->get_results( // phpcs:ignore
							$wpdb->prepare(
								'SELECT id FROM  `' . $wpdb->prefix . 'ac_email_templates_lite` WHERE id != %d AND frequency = %s AND day_or_hour = %s',
								$template_id,
								$email_frequncy,
								$email_day_or_hour
							)
						);
						$wcal_all_ids        = '';
						foreach ( $wcal_updated_get_id as $wcal_updated_get_id_key => $wcal_updated_get_id_value ) {
							// code...
							if ( '' === $wcal_all_ids ) {
								$wcal_all_ids = $wcal_updated_get_id_value->id;
							} else {
								$wcal_all_ids = $wcal_all_ids . ',' . $wcal_updated_get_id_value->id;
							}
						}
						echo esc_html( 'wcal-template-updated:' . $wcal_all_ids );
					}

					$active = '1';
					update_option( 'wcal_template_' . $template_id . '_time', current_time( 'timestamp' ) ); // phpcs:ignore
				} else {
					$active = '0';
				}
				$wpdb->query( // phpcs:ignore
					$wpdb->prepare(
						'UPDATE `' . $wpdb->prefix . 'ac_email_templates_lite` SET is_active = %s WHERE id  = %d',
						$active,
						$template_id
					)
				);
			}
			wp_die();
		}
		/**
		 * It will replace the test email data with the static content.
		 *
		 * @since 1.0
		 */
		public function wcal_preview_email_sent() {
			if ( isset( $_POST['body_email_preview'] ) && '' !== $_POST['body_email_preview'] ) { // phpcs:ignore WordPress.Security.NonceVerification
				$from_email_name       = get_option( 'wcal_from_name' );
				$reply_name_preview    = get_option( 'wcal_from_email' );
				$from_email_preview    = get_option( 'wcal_reply_email' );
				$subject_email_preview = isset( $_POST['subject_email_preview'] ) ? stripslashes( sanitize_text_field( wp_unslash( $_POST['subject_email_preview'] ) ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
				$subject_email_preview = convert_smilies( $subject_email_preview );
				$subject_email_preview = str_ireplace( '{{customer.firstname}}', 'John', $subject_email_preview );
				$body_email_preview    = isset( $_POST['body_email_preview'] ) ? convert_smilies( wp_unslash( $_POST['body_email_preview'] ) ) : ''; // phpcs:ignore
				$is_wc_template        = isset( $_POST['is_wc_template'] ) ? sanitize_text_field( wp_unslash( $_POST['is_wc_template'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
				$wc_template_header    = isset( $_POST['wc_template_header'] ) ? stripslashes( sanitize_text_field( wp_unslash( $_POST['wc_template_header'] ) ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification

				$body_email_preview = str_ireplace( '{{customer.firstname}}', 'John', $body_email_preview );
				$body_email_preview = str_ireplace( '{{customer.firstname}}', 'John', $body_email_preview );
				$body_email_preview = str_ireplace( '{{customer.lastname}}', 'Doe', $body_email_preview );
				$body_email_preview = str_ireplace( '{{customer.fullname}}', 'John Doe', $body_email_preview );
				$current_time_stamp = current_time( 'timestamp' ); // phpcs:ignore
				$date_format        = date_i18n( get_option( 'date_format' ), $current_time_stamp );
				$time_format        = date_i18n( get_option( 'time_format' ), $current_time_stamp );
				$test_date          = $date_format . ' ' . $time_format;
				$body_email_preview = str_ireplace( '{{cart.abandoned_date}}', $test_date, $body_email_preview );
				$cart_url           = wc_get_page_permalink( 'cart' );
				$body_email_preview = str_ireplace( '{{cart.link}}', $cart_url, $body_email_preview );
				$body_email_preview = str_ireplace( '{{cart.unsubscribe}}', '#', $body_email_preview );
				$wcal_price         = wc_price( '100' );
				$wcal_total_price   = wc_price( '200' );
				if ( class_exists( 'WP_Better_Emails' ) ) {
					$headers  = 'From: ' . $from_email_name . ' <' . $from_email_preview . '>' . "\r\n";
					$headers .= 'Content-Type: text/plain' . "\r\n";
					$headers .= 'Reply-To:  ' . $reply_name_preview . ' ' . "\r\n";
					$var      = '<table width = 100%>
											<tr> <td colspan="5"> <h3 style="text-align:center">' . __( 'Your Shopping Cart', 'woocommerce-abandoned-cart' ) . '</h3> </td></tr>
											<tr align="center">
											   <th>' . __( 'Item', 'woocommerce-abandoned-cart' ) . '</th>
											   <th>' . __( 'Name', 'woocommerce-abandoned-cart' ) . '</th>
											   <th>' . __( 'Quantity', 'woocommerce-abandoned-cart' ) . '</th>
											   <th>' . __( 'Price', 'woocommerce-abandoned-cart' ) . '</th>
											   <th>' . __( 'Line Subtotal', 'woocommerce-abandoned-cart' ) . '</th>
											</tr>
											<tr align="center">
											   <td><img class="demo_img" width="42" height="42" src="' . plugins_url( '/assets/images/shoes.jpg', __FILE__ ) . '"/></td>
											   <td>' . __( "Men's Formal Shoes", 'woocommerce-abandoned-cart' ) . '</td>
											   <td>1</td>
											   <td>' . $wcal_price . '</td>
											   <td>' . $wcal_price . '</td>
											</tr>
											<tr align="center">
											   <td><img class="demo_img" width="42" height="42" src="' . plugins_url( '/assets/images/handbag.jpg', __FILE__ ) . '"/></td>
											   <td>' . __( "Woman's Hand Bags", 'woocommerce-abandoned-cart' ) . '</td>
											   <td>1</td>
											   <td>' . $wcal_price . '</td>
											   <td>' . $wcal_price . '</td>
											</tr>
											<tr align="center">
											   <td></td>
											   <td></td>
											   <td></td>
											   <td>' . __( 'Cart Total:', 'woocommerce-abandoned-cart' ) . '</td>
											   <td>' . $wcal_total_price . '</td>
											</tr>
										</table>';
				} else {
					$headers  = 'From: ' . $from_email_name . ' <' . $from_email_preview . '>' . "\r\n";
					$headers .= 'Content-Type: text/html' . "\r\n";
					$headers .= 'Reply-To:  ' . $reply_name_preview . ' ' . "\r\n";
					$var      = '<h3 style="text-align:center">' . __( 'Your Shopping Cart', 'woocommerce-abandoned-cart' ) . '</h3>
										<table border="0" cellpadding="10" cellspacing="0" class="templateDataTable">
											<tr align="center">
											   <th>' . __( 'Item', 'woocommerce-abandoned-cart' ) . '</th>
											   <th>' . __( 'Name', 'woocommerce-abandoned-cart' ) . '</th>
											   <th>' . __( 'Quantity', 'woocommerce-abandoned-cart' ) . '</th>
											   <th>' . __( 'Price', 'woocommerce-abandoned-cart' ) . '</th>
											   <th>' . __( 'Line Subtotal', 'woocommerce-abandoned-cart' ) . '</th>
											</tr>
											<tr align="center">
											   <td><img class="demo_img" width="42" height="42" src="' . plugins_url( '/assets/images/shoes.jpg', __FILE__ ) . '"/></td>
											   <td>' . __( "Men's Formal Shoes", 'woocommerce-abandoned-cart' ) . '</td>
											   <td>1</td>
											   <td>' . $wcal_price . '</td>
											   <td>' . $wcal_price . '</td>
											</tr>
											<tr align="center">
											   <td><img class="demo_img" width="42" height="42" src="' . plugins_url( '/assets/images/handbag.jpg', __FILE__ ) . '"/></td>
											   <td>' . __( "Woman's Hand Bags", 'woocommerce-abandoned-cart' ) . '</td>
											   <td>1</td>
											   <td>' . $wcal_price . '</td>
											   <td>' . $wcal_price . '</td>
											</tr>
											<tr align="center">
											   <td></td>
											   <td></td>
											   <td></td>
											   <td>' . __( 'Cart Total:', 'woocommerce-abandoned-cart' ) . '</td>
											   <td>' . $wcal_total_price . '</td>
											</tr>
										 </table>';
				}
				$body_email_preview = str_ireplace( '{{products.cart}}', $var, $body_email_preview );
				if ( isset( $_POST['send_email_id'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
					$to_email_preview = sanitize_text_field( wp_unslash( $_POST['send_email_id'] ) ); // phpcs:ignore WordPress.Security.NonceVerification
				} else {
					$to_email_preview = '';
				}
				$user_email_from          = get_option( 'admin_email' );
				$body_email_final_preview = stripslashes( $body_email_preview );

				if ( isset( $is_wc_template ) && 'true' === $is_wc_template ) {
					ob_start();
					// Get email heading.
					wc_get_template( 'emails/email-header.php', array( 'email_heading' => $wc_template_header ) );
					$email_body_template_header = ob_get_clean();

					ob_start();
					wc_get_template( 'emails/email-footer.php' );
					$email_body_template_footer = ob_get_clean();

					$final_email_body = $email_body_template_header . $body_email_final_preview . $email_body_template_footer;

					$site_title                 = get_bloginfo( 'name' );
					$email_body_template_footer = str_ireplace( '{site_title}', $site_title, $email_body_template_footer );

					wc_mail( $to_email_preview, $subject_email_preview, $final_email_body, $headers );
				} else {
					wp_mail( $to_email_preview, $subject_email_preview, stripslashes( $body_email_preview ), $headers );
				}
				echo 'email sent';
				die();
			} else {
				echo 'not sent';
				die();
			}
		}
	}
}
$woocommerce_abandon_cart = new woocommerce_abandon_cart_lite();
