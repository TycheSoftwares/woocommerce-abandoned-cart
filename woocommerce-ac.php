<?php
/**
 * Plugin Name: Abandoned Cart Lite for WooCommerce
 * Plugin URI: http://www.tychesoftwares.com/store/premium-plugins/woocommerce-abandoned-cart-pro
 * Description: Track abandoned carts and send automated, customizable abandoned cart recovery emails. Reduce cart abandonment, recover lost revenue & increase sales.
 * Version: 6.9.0
 * Author: Tyche Softwares
 * Author URI: http://www.tychesoftwares.com/
 * Text Domain: woocommerce-abandoned-cart
 * Domain Path: /languages/
 * Requires PHP: 7.4 or higher
 * WC requires at least: 4.0.0
 * WC tested up to: 11.2.1
 * Requires Plugins: woocommerce
 *
 * @package Abandoned-Cart-Lite-for-WooCommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

use Automattic\WooCommerce\Utilities\OrderUtil;

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
require_once 'includes/wcal-functions.php';
require_once 'includes/class-wcal-webhooks.php';
require_once 'includes/api/class-wcal-rest-settings-controller.php';
require_once 'includes/api/class-wcal-rest-reports-controller.php';
require_once 'includes/api/class-wcal-rest-carts-controller.php';
require_once 'includes/api/class-wcal-rest-templates-controller.php';

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
				define( 'WCAL_PLUGIN_VERSION', '6.9.0' );
			}

			/**
			 * Defense-in-depth (Issue #1084): date after which untagged (pre-6.8.2)
			 * recovery tokens are hard-rejected to prevent HMAC-tag-stripping downgrade
			 * attacks. Any store that upgraded before this date will have no valid
			 * legacy tokens still in circulation (typical recovery-email TTL <= 30 days).
			 *
			 * Override in wp-config.php before the plugin loads if you upgraded later:
			 *   define( 'WCAL_REJECT_LEGACY_TOKENS_AFTER', '2025-09-01' );
			 *
			 * Set to '2099-01-01' to keep the legacy path open indefinitely (not recommended).
			 *
			 * @since 6.8.3
			 */
			if ( ! defined( 'WCAL_REJECT_LEGACY_TOKENS_AFTER' ) ) {
				define( 'WCAL_REJECT_LEGACY_TOKENS_AFTER', '2025-07-01' );
			}

			if ( ! defined( 'WCAL_PLUGIN_PATH' ) ) {
				define( 'WCAL_PLUGIN_PATH', untrailingslashit( plugin_dir_path( __FILE__ ) ) );
			}

			if ( ! defined( 'WCAL_PLUGIN_UPGRADE_TO_PRO_TEMPLATE_PATH' ) ) {
				define( 'WCAL_PLUGIN_UPGRADE_TO_PRO_TEMPLATE_PATH', WCAL_PLUGIN_PATH . '/includes/component/upgrade-to-pro/templates/' );
			}

			if ( ! defined( 'WCAL_TRIAL_NAME' ) ) {
				define( 'WCAL_TRIAL_NAME', 'Abandoned Cart Pro for WooCommerce Trial' );
			}

			if ( ! defined( 'WCAL_TRIAL_URL' ) ) {
				define( 'WCAL_TRIAL_URL', 'https://www.tychesoftwares.com/products/woocommerce-abandoned-cart-pro-plugin-trial' );
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
			if ( file_exists( WP_PLUGIN_DIR . '/woocommerce/packages/action-scheduler/action-scheduler.php' ) ) {
				require_once WP_PLUGIN_DIR . '/woocommerce/packages/action-scheduler/action-scheduler.php';
			} else {
				add_action( 'admin_notices', array( &$this, 'wcal_as_failed_notice' ) );
			}
			add_action( 'init', array( &$this, 'wcal_add_scheduled_action' ) );
			require_once 'cron/class-wcal-cron.php';
			require_once 'cron/class-wcal-admin-notification.php';
			require_once 'includes/class-wcal-process-base.php';

			// WordPress Administration Menu.
			add_action( 'admin_menu', array( &$this, 'wcal_admin_menu' ) );
			add_action( 'before_woocommerce_init', array( &$this, 'wcal_custom_order_tables_compatibility' ), 999 );

			// Actions to be done on cart update.
			add_action( 'woocommerce_add_to_cart', array( &$this, 'wcal_store_cart_timestamp' ), PHP_INT_MAX );
			add_action( 'woocommerce_cart_item_removed', array( &$this, 'wcal_store_cart_timestamp' ), PHP_INT_MAX );
			add_action( 'woocommerce_cart_item_restored', array( &$this, 'wcal_store_cart_timestamp' ), PHP_INT_MAX );
			add_action( 'woocommerce_after_cart_item_quantity_update', array( &$this, 'wcal_store_cart_timestamp' ), PHP_INT_MAX );
			add_action( 'woocommerce_calculate_totals', array( &$this, 'wcal_store_cart_timestamp' ), PHP_INT_MAX );

			add_filter( 'wcal_block_crawlers', array( &$this, 'wcal_detect_crawlers' ), 10, 1 );


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
			// delete abandoned order after X number of days.
			if ( class_exists( 'Wcal_Delete_Handler' ) ) {
				add_action( 'wcal_clear_carts', array( 'Wcal_Delete_Handler', 'wcal_delete_abandoned_carts_after_x_days' ) );
			}

			add_action( 'rest_api_init', array( 'WCAL_REST_Settings_Controller', 'register_routes' ) );
			add_action( 'rest_api_init', array( 'WCAL_REST_Reports_Controller', 'register_routes' ) );
			add_action( 'rest_api_init', array( 'WCAL_REST_Carts_Controller', 'register_routes' ) );
			add_action( 'rest_api_init', array( 'WCAL_REST_Templates_Controller', 'register_routes' ) );

			if ( is_admin() ) {
				add_action( 'admin_init', array( __CLASS__, 'wcal_redirect_legacy_admin_urls' ) );
				add_action( 'wp_ajax_wcal_pro_section', array( __CLASS__, 'wcal_pro_section' ) );
				add_action( 'wp_ajax_wcal_preview_email_sent', array( &$this, 'wcal_preview_email_sent' ) );
				add_action( 'wp_ajax_wcal_dismiss_admin_notice', array( &$this, 'wcal_dismiss_admin_notice' ) );
				add_filter( 'pre_update_option_wcal_auto_login_users', array( &$this, 'wcal_update_admin_notice_value' ), 10, 2 );

			}

			// Plugin Settings link in WP->Plugins page.
			$plugin = plugin_basename( __FILE__ );
			add_action( "plugin_action_links_$plugin", array( &$this, 'wcal_settings_link' ) );

			add_action( 'admin_init', array( $this, 'wcal_preview_emails' ) );
			add_action( 'init', array( $this, 'wcal_app_output_buffer' ) );

			add_filter( 'admin_footer_text', array( $this, 'wcal_admin_footer_text' ), 1 );

			add_action( 'admin_notices', array( 'Wcal_Admin_Notice', 'wcal_show_db_update_notice' ) );
			add_action( 'admin_notices', array( 'Wcal_Admin_Notice', 'wcal_show_auto_loggin_notice' ) );

			include_once 'includes/frontend/class-wcal-frontend.php';

			add_filter( 'cron_schedules', array( __CLASS__, 'wcal_add_cron_schedule' ) ); //phpcs:ignore
			add_action( 'woocommerce_ac_delete_coupon_action', array( __CLASS__, 'wcal_delete_expired_used_coupon_code' ) );
			add_action( 'wp_ajax_wcal_delete_expired_used_coupon_code', array( __CLASS__, 'wcal_delete_expired_used_coupon_code' ) );

			add_action( 'woocommerce_coupon_error', array( 'wcal_common', 'wcal_capture_coupon_error' ), 15, 2 );
			add_action( 'woocommerce_applied_coupon', array( 'wcal_common', 'wcal_capture_applied_coupon' ), 15, 2 );

			add_action( 'woocommerce_before_cart_table', array( 'wcal_common', 'wcal_apply_direct_coupon_code' ) );
			// Add coupon when user views checkout page (would not be added otherwise, unless user views cart first).
			add_action( 'woocommerce_before_checkout_form', array( 'wcal_common', 'wcal_apply_direct_coupon_code' ) );
			add_filter( 'woocommerce_email_from_address', array( __CLASS__, 'wcal_from_address_for_emails' ), 10, 2 );
			add_filter( 'woocommerce_email_from_name', array( __CLASS__, 'wcal_from_name_for_emails' ), 10, 2 );
			// 5.14.0
			if ( 'yes' === get_option( 'wcal_guest_users_manual_reset_needed', '' ) ) {
				add_action( 'admin_notices', array( 'Wcal_Update', 'wcal_add_notice' ) );
			}
			// 5.15.0
			add_action( 'wp_login', array( &$this, 'wcal_populate_wc_session' ) );
			if ( '' === get_option( 'wcal_auto_login_users', '' ) ) {
				add_filter( 'woocommerce_login_redirect', array( &$this, 'ts_redirect_login' ), 10, 1 );
			}

			// 5.20.0 - Deactivation and Tracking v2.
			add_action( 'admin_init', array( &$this, 'wcal_include_files_tracking' ) );
			add_filter( 'wp_plugin_check_checks', array( __CLASS__, 'wcal_plugin_check_ignore_files' ), 10 );
		}

		/**
		 * Include tracking & deactivation survey files.
		 */
		public static function wcal_include_files_tracking() {
			if ( strpos( $_SERVER['REQUEST_URI'], 'plugins.php' ) !== false || strpos( $_SERVER['REQUEST_URI'], 'action=deactivate' ) !== false || ( strpos( $_SERVER['REQUEST_URI'], 'admin-ajax.php' ) !== false && isset( $_POST['action'] ) && $_POST['action'] === 'tyche_plugin_deactivation_submit_action' ) ) { //phpcs:ignore
				require_once WCAL_PLUGIN_PATH . '/includes/component/plugin-deactivation/class-tyche-plugin-deactivation.php';

				new Tyche_Plugin_Deactivation(
					array(
						'plugin_name'       => 'Abandoned Cart Lite for WooCommerce',
						'plugin_base'       => 'woocommerce-abandoned-cart/woocommerce-ac.php',
						'script_file'       => WCAL_PLUGIN_URL . '/assets/js/admin/plugin-deactivation.js',
						'plugin_short_name' => 'acp_lite',
						'version'           => WCAL_PLUGIN_VERSION,
						'plugin_locale'     => 'woocommerce-abandoned-cart',
					)
				);
			}
				// Tracking v2 files.
				require_once WCAL_PLUGIN_PATH . '/includes/component/plugin-tracking/class-tyche-plugin-tracking.php';
				new Tyche_Plugin_Tracking(
					array(
						'plugin_name'       => 'Abandoned Cart Lite for WooCommerce',
						'plugin_locale'     => 'woocommerce-abandoned-cart',
						'plugin_short_name' => 'wcal',
						'version'           => WCAL_PLUGIN_VERSION,
						'blog_link'         => 'https://www.tychesoftwares.com/abandoned-cart-lite-usage-tracking/',
					)
				);
				require_once WCAL_PLUGIN_PATH . '/includes/class-wcal-data-tracking.php';
		}
		/**
		 * Action Scheduler library load failed. So add an admin notice.
		 *
		 * @since 5.9.0
		 */
		public static function wcal_as_failed_notice() {
			$as_link = "<a href='https://www.tychesoftwares.com/moving-to-the-action-scheduler-library/' target='_blank'>Action Scheduler library</a>";
			?>
			<div class="notice notice-error">
				<p>
					<?php
					echo wp_kses_post( __( "Abandoned Cart Lite for WooCommerce was unable to load the $as_link. Please ensure you are running WooCommerce 4.0.0 or higher to send automated reminder emails. Currrently no automated reminder emails are being sent for abandoned carts.", 'woocommerce-abandoned-cart' ) ); // phpcs:ignore
					?>
				</p>
			</div>
			<?php
		}

		/**
		 * Add Recurring Scheduled Action.
		 */
		public static function wcal_add_scheduled_action() {
			if ( function_exists( 'as_next_scheduled_action' ) ) {
				if ( false === as_next_scheduled_action( 'woocommerce_ac_send_email_action' ) ) {
					wp_clear_scheduled_hook( 'woocommerce_ac_send_email_action' ); // Remove the cron job is present.
					as_schedule_recurring_action( time() + 60, 900, 'woocommerce_ac_send_email_action' ); // Schedule recurring action.
				}
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
			$settings_link = '<a href="admin.php?page=woocommerce_ac_page#/settings/general">' . __( 'Settings', 'woocommerce-abandoned-cart' ) . '</a>';
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
				if ( file_exists( plugin_dir_path( __FILE__ ) . 'includes/class-wcal-all-component.php' ) ) {
					require_once 'includes/class-wcal-all-component.php';
				}
			}
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

			$current_time = current_time( 'timestamp' ); // phpcs:ignore
			$date_format  = date_i18n( get_option( 'date_format' ), $current_time );
			$time_format  = date_i18n( get_option( 'time_format' ), $current_time );

			$replace_data['admin_phone']    = $admin_phone;
			$replace_data['site_title']     = get_bloginfo( 'name' );
			$replace_data['site_url']       = get_option( 'siteurl' );
			$replace_data['abandoned_date'] = "$date_format $time_format";
			$replace_data['cart_url']       = wc_get_page_permalink( 'cart' );

			$content = str_ireplace( '{{products.cart}}', $replace_data['products_cart'], $content );
			$content = str_ireplace( '{{admin.phone}}', $replace_data['admin_phone'], $content );
			$content = str_ireplace( '{{customer.firstname}}', 'John', $content );
			$content = str_ireplace( '{{customer.lastname}}', 'Doe', $content );
			$content = str_ireplace( '{{customer.fullname}}', 'John Doe', $content );
			$content = str_ireplace( '{{cart.abandoned_date}}', $replace_data['abandoned_date'], $content );
			$content = str_ireplace( '{{cart.link}}', $replace_data['cart_url'], $content );
			$content = str_ireplace( '{{cart.unsubscribe}}', '#', $content );
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

			if ( isset( $_GET['wcal_preview_woocommerce_mail'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
				if ( ! current_user_can( 'manage_woocommerce' ) || ! isset( $_REQUEST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_REQUEST['_wpnonce'] ) ), 'woocommerce-abandoned-cart' ) ) {
					die( 'Security check' );
				}
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
				$message = '';
				// create a new email.
				if ( version_compare( WC_VERSION, '3.0', '>' ) ) {

					ob_start();
					// Get email heading.
					$email_heading = __( 'Abandoned cart Email Template', 'woocommerce-abandoned-cart' );
					wc_get_template( 'emails/email-header.php', array( 'email_heading' => $email_heading ) );
					$email_body_template_header = ob_get_clean();

					ob_start();
					wc_get_template( 'emails/email-footer.php' );
					$email_body_template_footer = ob_get_clean();

					$site_title                 = get_bloginfo( 'name' );
					$email_body_template_footer = str_ireplace( '{site_title}', $site_title, $email_body_template_footer );

					ob_start();
					if ( isset( $_GET['id'] ) && 0 < sanitize_text_field( wp_unslash( $_GET['id'] ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification
						$message = stripslashes( $content );
					} else {
						include 'views/wcal-wc-email-template-preview.php';
						$message = ob_get_clean();
					}
					$message = $email_body_template_header . $message . $email_body_template_footer;
				}
				header(
					"Content-Security-Policy: sandbox; " .
					"default-src 'self'; " .
					"style-src 'self' 'unsafe-inline' https:; " .
					"font-src 'self' https:; " .
					"img-src 'self' data: https:;"
				);
				echo wp_unslash( $message ); // phpcs:ignore
				exit;
			}

			if ( isset( $_GET['wcal_preview_mail'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
				if ( ! current_user_can( 'manage_woocommerce' ) || ! isset( $_REQUEST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_REQUEST['_wpnonce'] ) ), 'woocommerce-abandoned-cart' ) ) {
					die( 'Security check' );
				}
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
				// get the preview email content.
				ob_start();
				if ( isset( $_GET['id'] ) && 0 < sanitize_text_field( wp_unslash( $_GET['id'] ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification
					$message = stripslashes( $content );
				} else {
					include_once 'views/wcal-email-template-preview.php';
					$message = ob_get_clean();
				}
				// print the preview email.
				header(
					"Content-Security-Policy: sandbox; " .
					"default-src 'self'; " .
					"style-src 'self' 'unsafe-inline' https:; " .
					"font-src 'self' https:; " .
					"img-src 'self' data: https:;"
				);
				echo wp_unslash( $message ); // phpcs:ignore
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
				load_plugin_textdomain( $domain, false, basename( dirname( __FILE__ ) ) . '/languages/' );
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
						add_blog_option( $blog_id, 'wcal_db_version', WCAL_PLUGIN_VERSION );
					} else { // parent site.
						self::wcal_process_activate();
						add_blog_option( 1, 'wcal_db_version', WCAL_PLUGIN_VERSION );
					}
				}
			} else { // single site.
				self::wcal_process_activate();
				add_option( 'wcal_db_version', WCAL_PLUGIN_VERSION );
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
			$next_scheduled = wp_next_scheduled( 'wcal_ts_tracker_send_event' );
			if ( $next_scheduled ) {
				wp_unschedule_event( $next_scheduled, 'wcal_ts_tracker_send_event' );
			}
			do_action( 'wcal_deactivate' );
		}
		/**
		 * Sets the compatibility with Woocommerce HPOS.
		 *
		 * @since 5.14.2
		 */
		public static function wcal_custom_order_tables_compatibility() {

			if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
				\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', 'woocommerce-abandoned-cart/woocommerce-ac.php', true );
				\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'orders_cache', 'woocommerce-abandoned-cart/woocommerce-ac.php', true );
			}
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
			$wpdb->query( // phpcs:disable
				"CREATE TABLE IF NOT EXISTS $table_name (
				`id` int(11) NOT NULL AUTO_INCREMENT,
				`email_type` varchar(50) NOT NULL,
				`subject` text NOT NULL,
				`body` mediumtext NOT NULL,
				`is_active` enum('0','1') NOT NULL,
				`frequency` int(11) NOT NULL,
				`day_or_hour` enum('Days','Hours','Minutes') NOT NULL,
				`template_name` text NOT NULL,
				`is_wc_template` enum('0','1') NOT NULL,
				`default_template` int(11) NOT NULL,
				`wc_email_header` varchar(50) NOT NULL,
				`coupon_code` varchar(50)  NOT NULL,
				`discount` varchar(50)  NOT NULL,
		        `discount_type` varchar(50)  NOT NULL,
		        `discount_shipping` varchar(50)  NOT NULL,
				`discount_expiry` varchar(50) NOT NULL,
				`individual_use` enum('0','1') NOT NULL,
		        `generate_unique_coupon_code` enum('0','1') NOT NULL,
				PRIMARY KEY (`id`)
				) $wcap_collate AUTO_INCREMENT=1" // phpcs:ignore
			);

			$sent_table_name = $db_prefix . 'ac_sent_history_lite';
			$wpdb->query( // phpcs:ignore
				"CREATE TABLE IF NOT EXISTS $sent_table_name (
				`id` int(11) NOT NULL auto_increment,
				`template_id` varchar(40) collate utf8_unicode_ci NOT NULL,
				`abandoned_order_id` int(11) NOT NULL,
				`sent_time` datetime NOT NULL,
				`sent_email_id` text COLLATE utf8_unicode_ci NOT NULL,
				`encrypt_key` varchar(500) COLLATE utf8_unicode_ci NOT NULL,
				PRIMARY KEY  (`id`)
				) $wcap_collate AUTO_INCREMENT=1 " // phpcs:ignore
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
				`checkout_link` varchar(500) COLLATE utf8_unicode_ci NOT NULL,
				PRIMARY KEY (`id`),
				KEY `session_id` (`session_id`)
				) $wcap_collate" // phpcs:ignore
			);

			$guest_table        = $db_prefix . 'ac_guest_abandoned_cart_history_lite';
			$result_guest_table = $wpdb->get_results( // phpcs:ignore
				"SHOW TABLES LIKE '$guest_table'" // phpcs:ignore
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
					) $wcap_collate AUTO_INCREMENT=63000000" // phpcs:ignore
				);
			}

			$wcal_guest_user_id_altered = 0 === $blog_id ? get_option( 'wcal_guest_user_id_altered' ) : get_blog_option( $blog_id, 'wcal_guest_user_id_altered' );

			// Alter Guest Table A_I value if needed.
			if ( 'yes' !== $wcal_guest_user_id_altered ) {
				Wcal_Update::wcal_reset_guest_user_id( $db_prefix, $blog_id, 1 );
			}
			// Default templates - function call to create default templates.
			$check_table_empty = $wpdb->get_var( 'SELECT COUNT(*) FROM `' . $db_prefix . 'ac_email_templates_lite`' ); // phpcs:ignore
			// phpcs:enable

			/**
			 * This is add for thos user who Install the plguin first time.
			 * So for them this option will be cheked.
			 */
			if ( 0 === $blog_id ) {
				if ( ! get_option( 'wcal_new_default_templates' ) ) {
					if ( 0 === (int) $check_table_empty ) {
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
				if ( ! get_option( 'wcal_auto_login_users' ) ) {
					add_option( 'wcal_auto_login_users', '' );
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
				if ( ! get_blog_option( $blog_id, 'wcal_auto_login_users' ) ) {
					add_blog_option( $blog_id, 'wcal_auto_login_users', '' );
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
				__( 'Cart Abandonment Settings', 'woocommerce-abandoned-cart' ),     // Title to be displayed on the administration page.
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
				'wcap_email_admin_on_abandonment',
				__( 'Email admin on cart abandonment', 'woocommerce-abandoned-cart' ),
				array( $this, 'wcap_email_admin_on_abandonment' ),
				'woocommerce_ac_page',
				'ac_lite_general_settings_section',
				array( __( 'Enable this option to notify the store administrator when a cart is abandoned', 'woocommerce-abandoned-cart' ) )
			);

			add_settings_field(
				'ac_lite_track_guest_cart_from_cart_page',
				__( 'Start tracking from Cart Page', 'woocommerce-abandoned-cart' ),
				array( $this, 'wcal_track_guest_cart_from_cart_page_callback' ),
				'woocommerce_ac_page',
				'ac_lite_general_settings_section',
				array( __( 'Enable tracking of abandoned products & carts even if customer does not visit the checkout page or does not enter any details on the checkout page like Name or Email. Tracking will begin as soon as a visitor adds a product to their cart and visits the cart page.', 'woocommerce-abandoned-cart' ) )
			);

			add_settings_section(
				'ac_lite_gdpr_settings',                 // ID used to identify this section and with which to register options.
				__( 'GDPR Settings', 'woocommerce-abandoned-cart' ),     // Title to be displayed on the administration page.
				array( $this, 'ac_lite_gdpr_callback' ), // Callback used to render the description of the section.
				'woocommerce_ac_page'                               // Page on which to add this section of options.
			);

			add_settings_field(
				'wcal_enable_gdpr_consent',
				__( 'Enable GDPR Notice', 'woocommerce-abandoned-cart' ),
				array( $this, 'wcal_enable_gdpr_callback' ),
				'woocommerce_ac_page',
				'ac_lite_gdpr_settings',
				array( __( 'Enable this setting to display a notice informing customers that their email and cart data are saved to send abandonment reminders.', 'woocommerce-abandoned-cart' ) )
			);

			add_settings_field(
				'wcal_guest_cart_capture_msg',
				__( 'Message to be displayed for Guest users when tracking their carts', 'woocommerce-abandoned-cart' ),
				array( $this, 'wcal_guest_cart_capture_msg_callback' ),
				'woocommerce_ac_page',
				'ac_lite_gdpr_settings',
				array( __( '<br>In compliance with GDPR, add a message on the Checkout page to inform Guest users of how their data is being used.<br><i>For example: Your email address will help us support your shopping experience throughout the site. Please check our Privacy Policy to see how we use your personal data.</i>', 'woocommerce-abandoned-cart' ) )
			);

			add_settings_field(
				'wcal_logged_cart_capture_msg',
				__( 'Message to be displayed for registered users when tracking their carts.', 'woocommerce-abandoned-cart' ),
				array( $this, 'wcal_logged_cart_capture_msg_callback' ),
				'woocommerce_ac_page',
				'ac_lite_gdpr_settings',
				array( __( '<br>In compliance with GDPR, add a message on the Shop & Product pages to inform Registered users of how their data is being used.<br><i>For example: Please check our Privacy Policy to see how we use your personal data.</i>', 'woocommerce-abandoned-cart' ) )
			);

			add_settings_field(
				'wcal_gdpr_allow_opt_out',
				__( 'Allow the visitor to opt out of cart tracking.', 'woocommerce-abandoned-cart' ),
				array( $this, 'wcal_gdpr_allow_opt_out_callback' ),
				'woocommerce_ac_page',
				'ac_lite_gdpr_settings',
				array( __( '<br>In compliance with GDPR, allow the site visitor (guests & registered users) to opt out from cart tracking. This message will be displayed in conjunction with the GDPR message above.</i>', 'woocommerce-abandoned-cart' ) )
			);

			add_settings_field(
				'wcal_gdpr_opt_out_message',
				__( 'Message to be displayed when the user chooses to opt out of cart tracking.', 'woocommerce-abandoned-cart' ),
				array( $this, 'wcal_gdpr_opt_out_msg_callback' ),
				'woocommerce_ac_page',
				'ac_lite_gdpr_settings',
				array( __( '<br>Message to be displayed when the user chooses to opt out of cart tracking.</i>', 'woocommerce-abandoned-cart' ) )
			);
			add_settings_section(
				'ac_lite_coupon_settings',                 // ID used to identify this section and with which to register options.
				__( 'Coupon Settings', 'woocommerce-abandoned-cart' ),     // Title to be displayed on the administration page.
				array( $this, 'ac_lite_coupon_callback' ), // Callback used to render the description of the section.
				'woocommerce_ac_page'                               // Page on which to add this section of options.
			);
			add_settings_field(
				'wcal_delete_coupon_data',
				__( 'Delete Coupons Automatically', 'woocommerce-abandoned-cart' ),
				array( $this, 'wcal_deleting_coupon_data' ),
				'woocommerce_ac_page',
				'ac_lite_coupon_settings',
				array( __( 'Enable this setting if you want to completely remove the expired and used coupon code automatically every 15 days.', 'woocommerce-abandoned-cart' ) )
			);
			add_settings_field(
				'wcal_delete_coupon_data_manually',
				__( 'Delete Coupons Manually', 'woocommerce-abandoned-cart' ),
				array( $this, 'wcal_deleting_coupon_data_manually' ),
				'woocommerce_ac_page',
				'ac_lite_coupon_settings',
				array( __( 'If you want to completely remove the expired and used coupon code now then click on "Delete" button.', 'woocommerce-abandoned-cart' ) )
			);

			add_settings_section(
				'ac_lite_rules_settings',                 // ID used to identify this section and with which to register options.
				__( 'Rules to exclude capturing abandoned carts', 'woocommerce-abandoned-cart' ),     // Title to be displayed on the administration page.
				array( $this, 'ac_lite_rules_callback' ), // Callback used to render the description of the section.
				'woocommerce_ac_page'                               // Page on which to add this section of options.
			);

			add_settings_field(
				'wcap_restrict_ip_address',
				__( 'Do not capture abandoned carts for these IP Addresses:', 'woocommerce-abandoned-cart' ),
				array( $this, 'wcap_restrict_ip_address_callback' ),
				'woocommerce_ac_page',
				'ac_lite_rules_settings',
				array( __( '<br>The carts abandoned from these IP addresses will not be tracked by the plugin. Accepts wildcards, e.g 192.168.* will block all IP addresses which starts from &quot;192.168&quot;. Separate IP addresses with commas.', 'woocommerce-abandoned-cart' ) )
			);

			add_settings_field(
				'wcap_restrict_email_address',
				__( 'Do not capture abandoned carts for these email addresses:', 'woocommerce-abandoned-cart' ),
				array( $this, 'wcap_restrict_email_address_callback' ),
				'woocommerce_ac_page',
				'ac_lite_rules_settings',
				array( __( '<br>Carts that are abandoned using these email addresses will not be tracked by the plugin. *Separate email addresses with commas.', 'woocommerce-abandoned-cart' ) )
			);

			add_settings_field(
				'wcap_restrict_domain_address',
				__( 'Do not capture abandoned carts for email addresses from these domains:', 'woocommerce-abandoned-cart' ),
				array( $this, 'wcap_restrict_domain_address_callback' ),
				'woocommerce_ac_page',
				'ac_lite_rules_settings',
				array( __( '<br>The carts abandoned from email addresses with these domains will not be tracked by the plugin.', 'woocommerce-abandoned-cart' ) )
			);

			add_settings_field(
				'wcap_restrict_countries',
				__( 'Do not capture carts from countries:', 'woocommerce-abandoned-cart' ),
				array( $this, 'restriced_countries_callback' ),
				'woocommerce_ac_page',
				'ac_lite_rules_settings',
				array( __( '<br>The carts abandoned from these countries will not be tracked by the plugin.', 'woocommerce-abandoned-cart' ) )
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

			add_settings_field(
				'wcal_add_utm_to_links',
				__( 'UTM parameters to be added to all the links in reminder emails', 'woocommerce-abandoned-cart' ),
				array( &$this, 'wcal_add_utm_to_links_callback' ),
				'woocommerce_ac_email_page',
				'ac_email_settings_section',
				array( __( 'UTM parameters that should be added to all the links in reminder emails.', 'woocommerce-abandoned-cart' ) )
			);

			add_settings_field(
				'wcal_auto_login_users',
				__( 'Auto login WordPress users coming to the site using reminder email links', 'woocommerce-abandoned-cart' ),
				array( &$this, 'wcal_auto_login_users_callback' ),
				'woocommerce_ac_email_page',
				'ac_email_settings_section',
				array( __( 'Should users registered on the site be auto logged in when they click a link in the reminder emails?', 'woocommerce-abandoned-cart' ) )
			);

			// Finally, we register the fields with WordPress.
			//phpcs:disable
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
				'wcap_email_admin_on_abandonment'
			);

			register_setting(
				'woocommerce_ac_settings',
				'ac_lite_track_guest_cart_from_cart_page'
			);

			register_setting(
				'woocommerce_ac_settings',
				'wcal_enable_gdpr_consent'
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

			register_setting(
				'woocommerce_ac_email_settings',
				'wcal_add_utm_to_links'
			);

			register_setting(
				'woocommerce_ac_email_settings',
				'wcal_auto_login_users'
			);

			register_setting(
				'woocommerce_ac_settings',
				'wcal_delete_coupon_data'
			);

			register_setting(
				'woocommerce_ac_settings',
				'wcap_restrict_ip_address'
			);

			register_setting(
				'woocommerce_ac_settings',
				'wcap_restrict_email_address'
			);

			register_setting(
				'woocommerce_ac_settings',
				'wcap_restrict_domain_address'
			);

			register_setting(
				'woocommerce_ac_settings',
				'wcap_restrict_countries',
				[
					'sanitize_callback' => array( __CLASS__, 'sanitize_restrict_countries' ),
				]
			);

			do_action( 'wcal_add_new_settings' );
			//phpcs:enable
		}

		/**
		 * Sanitizing the Countries data and also converting to string separated by comma.
		 *
		 * @param array $value Value of Countries.
		 */
		public static function sanitize_restrict_countries( $value ) {

			if ( empty( $value ) || ! is_array( $value ) ) {
				return '';
			}

			$value = array_map( 'sanitize_text_field', $value );

			$value = array_filter( $value );

			return implode( ',', $value );
		}

		/**
		 * Labels and descriptions of the registered settings fields, as plain text.
		 *
		 * @return array
		 * @since 6.10.0
		 */
		public static function wcal_settings_field_labels() {
			global $wp_settings_fields;

			$plain_text = function ( $text ) {
				return trim( html_entity_decode( wp_strip_all_tags( str_replace( '<br>', ' ', $text ) ), ENT_QUOTES, 'UTF-8' ) );
			};

			$labels = array();
			foreach ( array( 'woocommerce_ac_page', 'woocommerce_ac_email_page' ) as $page ) {
				$sections = isset( $wp_settings_fields[ $page ] ) ? $wp_settings_fields[ $page ] : array();
				foreach ( $sections as $fields ) {
					foreach ( $fields as $id => $field ) {
						$labels[ $id ] = array(
							'label'       => $plain_text( $field['title'] ),
							'description' => $plain_text( isset( $field['args'][0] ) ? $field['args'][0] : '' ),
						);
					}
				}
			}
			return $labels;
		}

		/**
		 * Send the URLs of the old admin pages to the matching screen of the React app,
		 * so bookmarks and links in emails and notices keep working.
		 *
		 * @hook admin_init
		 * @since 6.10.0
		 */
		public static function wcal_redirect_legacy_admin_urls() {
			// phpcs:disable WordPress.Security.NonceVerification
			$page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '';
			if ( 'woocommerce_ac_page' !== $page || ! isset( $_GET['action'] ) || isset( $_GET['ac_update'] ) || isset( $_GET['wcal_preview_woocommerce_mail'] ) || wp_doing_ajax() ) {
				return;
			}

			$action  = sanitize_text_field( wp_unslash( $_GET['action'] ) );
			$section = isset( $_GET['wcal_section'] ) ? sanitize_text_field( wp_unslash( $_GET['wcal_section'] ) ) : '';
			$mode    = isset( $_GET['mode'] ) ? sanitize_text_field( wp_unslash( $_GET['mode'] ) ) : '';
			$id      = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
			// phpcs:enable WordPress.Security.NonceVerification

			$cart_tabs     = array(
				'wcal_all_registered' => 'registered',
				'wcal_all_guest'      => 'guest',
				'wcal_all_visitor'    => 'visitor',
			);
			$settings_tabs = array(
				'wcal_general_settings'       => 'general',
				'wcal_email_settings'         => 'general',
				'wcap_atc_settings'           => 'popup-templates',
				'wcap_sms_settings'           => 'sms',
				'wcap_whatsapp_settings'      => 'whatsapp',
				'wcap_connectors'             => 'connectors',
				'wcap_email_report_settings'  => 'email-report',
				'wcap_import_export_settings' => 'import-export',
			);

			switch ( $action ) {
				case 'listcart':
				case 'orderdetails':
					$route = '/abandoned-orders' . ( isset( $cart_tabs[ $section ] ) ? '?tab=' . $cart_tabs[ $section ] : '' );
					break;
				case 'stats':
					$route = '/abandoned-orders?status=recovered';
					break;
				case 'emailtemplates':
					$route = '/email-template';
					if ( 'addnewtemplate' === $mode ) {
						$route = '/email-template/email/add';
					} elseif ( 'edittemplate' === $mode && $id ) {
						$route = '/email-template/email/edit/' . $id;
					}
					break;
				case 'emailsettings':
					$route = 'wcap_fb_settings' === $section ? '/upgrade-to-pro' : '/settings/' . ( isset( $settings_tabs[ $section ] ) ? $settings_tabs[ $section ] : 'general' );
					break;
				case 'report':
					$route = '/product-reports';
					break;
				case 'upgrade_to_pro_page':
					$route = '/upgrade-to-pro';
					break;
				case 'faq_support_page':
					$route = '/faq-support';
					break;
				default:
					$route = '/dashboard';
					break;
			}

			wp_safe_redirect( admin_url( 'admin.php?page=woocommerce_ac_page#' . $route ) );
			exit;
		}

		/**
		 * Return the HTML of a Pro-only section, with the upgrade modal, for the React app.
		 *
		 * @hook wp_ajax_wcal_pro_section
		 * @since 6.10.0
		 */
		public static function wcal_pro_section() {
			check_ajax_referer( 'wcal_pro_section', 'nonce' );

			if ( ! current_user_can( 'manage_woocommerce' ) ) {
				wp_send_json_error( array( 'message' => __( 'You do not have sufficient permissions to access this page.', 'woocommerce-abandoned-cart' ) ), 403 );
			}

			$sections = array(
				'wcap_atc_settings',
				'wcap_fb_settings',
				'wcap_sms_settings',
				'wcap_whatsapp_settings',
				'wcap_connectors',
				'wcap_email_report_settings',
				'wcap_import_export_settings',
			);
			$section  = isset( $_POST['section'] ) ? sanitize_text_field( wp_unslash( $_POST['section'] ) ) : '';

			if ( ! in_array( $section, $sections, true ) ) {
				wp_send_json_error( array( 'message' => __( 'Unknown section.', 'woocommerce-abandoned-cart' ) ), 400 );
			}

			ob_start();
			call_user_func( array( 'WCAP_Pro_Settings', $section ) );
			wp_send_json_success( array( 'html' => ob_get_clean() ) );
		}

		/**
		 * Enqueue the React admin app.
		 *
		 * @since 6.10.0
		 */
		public static function wcal_enqueue_admin_app() {
			$asset = include WCAL_PLUGIN_PATH . '/build/admin/admin.asset.php';

			wp_enqueue_script( 'wcal-admin-app', WCAL_PLUGIN_URL . '/build/admin/admin.js', $asset['dependencies'], $asset['version'], true );
			wp_enqueue_style( 'wcal-admin-app', WCAL_PLUGIN_URL . '/build/admin/admin.css', array( 'wp-components' ), $asset['version'] );
			wp_set_script_translations( 'wcal-admin-app', 'woocommerce-abandoned-cart', WCAL_PLUGIN_PATH . '/languages' );

			// The email template editor is built on wp.editor and the media library.
			wp_enqueue_editor();
			wp_enqueue_media();

			$countries           = array();
			$wc_countries_object = new WC_Countries();
			foreach ( $wc_countries_object->get_countries() as $code => $name ) {
				$countries[] = array(
					'id'   => $code,
					'text' => html_entity_decode( $name, ENT_QUOTES, 'UTF-8' ),
				);
			}

			wp_localize_script(
				'wcal-admin-app',
				'wcalAdminData',
				array(
					'pluginUrl'        => WCAL_PLUGIN_URL,
					'version'          => WCAL_PLUGIN_VERSION,
					'adminUrl'         => admin_url(),
					'restUrl'          => esc_url_raw( rest_url( WCAL_REST_Settings_Controller::REST_NAMESPACE . '/' ) ),
					'currencySymbol'   => html_entity_decode( get_woocommerce_currency_symbol(), ENT_QUOTES, 'UTF-8' ),
					'dateFormat'       => get_option( 'date_format' ),
					'timeFormat'       => get_option( 'time_format' ),
					'gmtOffset'        => get_option( 'gmt_offset' ),
					'canSave'          => current_user_can( 'manage_options' ),
					'fields'           => self::wcal_settings_field_labels(),
					'countries'        => $countries,
					'intro'            => __( 'Change settings for sending email notifications to Customers, to Admin etc.', 'woocommerce-abandoned-cart' ),
					'trialUrl'         => WCAL_TRIAL_URL,
					'proUrl'           => 'https://www.tychesoftwares.com/store/premium-plugins/woocommerce-abandoned-cart-pro/?utm_source=acupgradetopro&utm_medium=link&utm_campaign=AbandonCartLite',
					'faq'              => self::wcal_faq_items(),
					'docsUrl'          => 'https://www.tychesoftwares.com/docs/woocommerce-abandoned-cart-lite/?utm_source=wcalfooter&utm_medium=link&utm_campaign=AbandonCartLite',
					'supportUrl'       => 'https://wordpress.org/support/plugin/woocommerce-abandoned-cart/',
					'rateUrl'          => 'https://wordpress.org/support/plugin/woocommerce-abandoned-cart/reviews/?rate=5#new-post',
					'proSectionNonce'  => wp_create_nonce( 'wcal_pro_section' ),
					'currentUserEmail' => wp_get_current_user()->user_email,
					'testEmailNonce'   => wp_create_nonce( 'wcal_send_test_email' ),
					'previewUrls'      => array(
						'wc'     => esc_url_raw( add_query_arg( array( 'wcal_preview_woocommerce_mail' => 'true', '_wpnonce' => wp_create_nonce( 'woocommerce-abandoned-cart' ) ), admin_url() ) ),
						'custom' => esc_url_raw( add_query_arg( array( 'wcal_preview_mail' => 'true', '_wpnonce' => wp_create_nonce( 'woocommerce-abandoned-cart' ) ), admin_url() ) ),
					),
					'proSectionStyles' => array(
						WCAL_PLUGIN_URL . '/assets/css/admin/bootstrap.min.css',
						WCAL_PLUGIN_URL . '/assets/css/admin/wcap-admin-style.css',
						WCAL_PLUGIN_URL . '/assets/css/admin/wcap_integrations_main.css',
						WCAL_PLUGIN_URL . '/includes/component/upgrade-to-pro/assets/css/ts-upgrade-to-pro.css',
					),
				)
			);
		}

		/**
		 * FAQ entries for the React FAQ page, with the translation steps kept from the PHP template.
		 *
		 * @return array
		 * @since 6.10.0
		 */
		public static function wcal_faq_items() {
			$faq = Wcal_All_Component::wcal_get_faq();

			$faq[8]['answer'] .= '<ol>'
				. '<li>' . esc_html__( 'Download the "woocommerce-abandoned-cart.po" file.', 'woocommerce-abandoned-cart' ) . '</li>'
				. '<li>' . esc_html__( 'Open it in PoEdit translation editor, and translate the strings.', 'woocommerce-abandoned-cart' ) . '</li>'
				. '<li>' . esc_html__( 'Save with your language code (e.g., fr_FR.po) to generate the .mo file.', 'woocommerce-abandoned-cart' ) . '</li>'
				. '<li>' . esc_html__( 'Upload both files to "woocommerce-abandoned-cart/languages" and "wp-content/languages" folder via FTP.', 'woocommerce-abandoned-cart' ) . '</li>'
				. '</ol>'
				. esc_html__( "You can also use the Loco Translate or any other translation plugin for this string's translation.", 'woocommerce-abandoned-cart' );

			return array_values( $faq );
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
		public static function ac_lite_cart_time_validation( $input ) {
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
			if ( is_numeric( $input ) && $input > 0 ) {
				$output = stripslashes( $input );
			} else {
				$output = '365';
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
			$delete_abandoned_order_days = get_option( 'ac_lite_delete_abandoned_order_days', '365' );
			if ( '' === $delete_abandoned_order_days ) {
				$delete_abandoned_order_days = '365';
			}
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
		 * Settings API callback for email admin on cart abaondonment.
		 *
		 * @param array $args Arguments.
		 * @since 2.5
		 */
		public function wcap_email_admin_on_abandonment( $args ) {
			// First, we read the option.
			$wcap_email_admin_on_abandonment = get_option( 'wcap_email_admin_on_abandonment', '' );

			// This condition added to avoid the notie displyed while Check box is unchecked.
			if ( isset( $wcap_email_admin_on_abandonment ) && '' === $wcap_email_admin_on_abandonment ) {
				$wcap_email_admin_on_abandonment = 'off';
			}
			// Next, we update the name attribute to access this element's ID in the context of the display options array.
			// We also access the show_header element of the options collection in the call to the checked() helper function.
			$html = '';
			printf(
				'<input type="checkbox" id="wcap_email_admin_on_abandonment" name="wcap_email_admin_on_abandonment" value="on"
			' . checked( 'on', $wcap_email_admin_on_abandonment, false ) . ' />'
			);

			// Here, we'll take the first argument of the array and add it to a label next to the checkbox.
			$html .= '<label for="wcap_email_admin_on_abandonment"> ' . $args[0] . '</label>';
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
		 * Callback - Enable GDPR consent.
		 *
		 * @param array $args - Arguments.
		 * @since 5.12.0
		 */
		public static function wcal_enable_gdpr_callback( $args ) {
			$wcal_enable_gdpr = get_option( 'wcal_enable_gdpr_consent', '' );
			$wcal_gdpr_status = isset( $wcal_enable_gdpr ) && '' === $wcal_enable_gdpr ? 'off' : 'on';
			?>
			<input type="checkbox" id="wcal_enable_gdpr_consent" name="wcal_enable_gdpr_consent" value="on" <?php echo checked( 'on', $wcal_gdpr_status, false ); ?> />
			<label for="wcal_enable_gdpr_consent"> <?php echo esc_attr( $args[0] ); ?></label>
			<a >
			<?php
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
		 * Callback for UTM parameters.
		 *
		 * @param array $args - Arguments for the setting.
		 * @since 5.9.0
		 */
		public static function wcal_add_utm_to_links_callback( $args ) {

			$wcal_add_utm_to_links = get_option( 'wcal_add_utm_to_links', '' );

			?>
			<textarea id='wcal_add_utm_to_links' rows='4' cols='50' name='wcal_add_utm_to_links' ><?php echo esc_html( $wcal_add_utm_to_links ); ?></textarea>
			<?php
			$html = '<label for="wcal_add_utm_to_links">' . $args[0] . '</label>';
			echo wp_kses_post( $html );
		}

		/**
		 * Callback for Auto login Users.
		 *
		 * @param array $args - Arguments for the setting.
		 * @since 5.9.0
		 */
		public static function wcal_auto_login_users_callback( $args ) {

			$wcal_auto_login_users = get_option( 'wcal_auto_login_users', '' );
			if ( isset( $wcal_auto_login_users ) && '' === $wcal_auto_login_users ) {
				$wcal_auto_login_users = 'off';
			}
			printf(
				'<input type="checkbox" id="wcal_auto_login_users" name="wcal_auto_login_users" value="on" ' . checked( 'on', $wcal_auto_login_users, false ) . ' />'
			);
			$html = '<label for="wcal_auto_login_users"> ' . $args[0] . '</label>';
			echo wp_kses_post( $html );
		}

		/**
		 * Add a submenu page under the WooCommerce.
		 *
		 * @hook admin_menu
		 * @since 1.0
		 */
		public function wcal_admin_menu() {

			$menu_title = sprintf(
				'%s<span class="upgrade-to-pro-admin-menu">&nbsp;%s</span>',
				__( 'Abandoned Carts', 'woocommerce-abandoned-cart' ),
				__( 'Upgrade to Pro!', 'woocommerce-abandoned-cart' )
			);

			$page = add_submenu_page(
				'woocommerce',
				__( 'Abandoned Carts', 'woocommerce-abandoned-cart' ),
				$menu_title,
				'manage_woocommerce',
				'woocommerce_ac_page',
				array( &$this, 'wcal_menu_page' )
			);
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

			if ( isset( $_POST['country'] ) && '' !== $_POST['country'] ) { //phpcs:disable
	                $wcap_is_country_restricted = wcal_common::wcal_is_country_restricted( $_POST['country'] );
	                if ( $wcap_is_country_restricted ) {
	                    return;
	                }
	            }

			global $wpdb, $woocommerce;
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

					/* Rule for excluding the capturing cart abandonment. */

					$user_email_biiling = get_user_meta( $user_id, 'billing_email', true );
					$current_user_email = '';
					if ( '' === $user_email_biiling && isset( $user_email_biiling )  ) {
						$current_user_data  = get_userdata( $user_id );
						$current_user_email = $current_user_data->user_email;
					} else {
						$current_user_email = $user_email_biiling;
					}

					$billing_country = get_user_meta( $user_id, 'billing_country', true );

					$wcal_exclude_cart_abandonment = wcal_common::wcal_exclude_cart_abandonment( $current_user_email, $billing_country );
					if ( $wcal_exclude_cart_abandonment ) {
						return;
					}

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
							wcal_common::wcal_add_checkout_link( $abandoned_cart_id );
							wcal_common::wcal_run_webhook_after_cutoff( $abandoned_cart_id );
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
							wcal_common::wcal_add_checkout_link( $abandoned_cart_id );
							wcal_common::wcal_run_webhook_after_cutoff( $abandoned_cart_id );
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
							wcal_common::wcal_add_checkout_link( $abandoned_cart_id );
							wcal_common::wcal_run_webhook_after_cutoff( $abandoned_cart_id );
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

					/* Rule for excluding the cart abandonment */
					$wcal_exclude_cart_abandonment = wcal_common::wcal_exclude_cart_abandonment( '', '' );
					if ( $wcal_exclude_cart_abandonment ) {
						return;
					}

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
						$wc_version = defined( 'WC_VERSION' ) ? WC_VERSION : null;
						if ( $wc_version && version_compare( $wc_version, '10.0.0', '>=' ) ) {
							$cart['cart'] = WC()->cart->get_cart();
						} else {
							$cart['cart'] = WC()->session->cart;
						}
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
										'INSERT INTO `' . $wpdb->prefix . 'ac_abandoned_cart_history_lite` (user_id, abandoned_cart_info, abandoned_cart_time, cart_ignored, user_type, session_id, checkout_link) VALUES (%d, %s, %d, %s, %s, %s, %s)',
										$user_id,
										$updated_cart_info,
										$current_time,
										$cart_ignored,
										$user_type,
										$get_cookie,
										$results[0]->checkout_link
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
									'SELECT * FROM `' . $wpdb->prefix . 'ac_abandoned_cart_history_lite` WHERE session_id = %s AND cart_ignored = %s AND recovered_cart = %s',
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
				'crawler',
				'spider',
				'ahrefs',
				'semrush',
				'mj12bot',
				'dotbot',
				'bingpreview',
				'facebookexternalhit',
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
				$crypt_key                     = '';
				if ( isset( $_GET['user_email'] ) && '' !== $_GET['user_email'] ) { // phpcs:ignore
					$sent_email = str_replace( ' ', '+', sanitize_text_field( wp_unslash( $_GET['user_email'] ) ) ); // phpcs:ignore
					$key_data   = $wpdb->get_results( // phpcs:ignore
						$wpdb->prepare(
							'SELECT encrypt_key FROM `' . $wpdb->prefix . 'ac_sent_history_lite` WHERE sent_email_id = %s ORDER BY id DESC',
							$sent_email
						)
					);
					if ( $wpdb->num_rows > 0 && isset( $key_data[0]->encrypt_key ) ) {
						$crypt_key = $key_data[0]->encrypt_key;
					}
				}
				if ( ! empty( $crypt_key ) && null !== $crypt_key && '' !== $crypt_key ) {
					$validate_email_id_decode = Wcal_Aes_Ctr::decrypt( $validate_email_id_string, $crypt_key, 256 );
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
						$url = apply_filters( 'wcal_unsubscribe_redirect', get_option( 'home' ) );
						?>
						<script>
							location.href = "<?php echo esc_url( $url ); ?>";
						</script>
						<?php
					}
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

			if ( 'track_links' === $track_link || 'checkout_link' === $track_link ) {
				if ( '' === session_id() ) {
					// session has not started.
					session_start();
				}
				global $wpdb;
				$validate_server_string  = isset( $_GET ['validate'] ) ? rawurldecode( wp_unslash( $_GET ['validate'] ) ) : ''; // phpcs:ignore
				$validate_server_string  = str_replace( ' ', '+', $validate_server_string );
				$validate_encoded_string = $validate_server_string;
				$crypt_key               = '';
				$user_id                 = 0;
				$abandoned_id            = 0;
				if ( isset( $_GET['user_email'] ) && '' !== $_GET['user_email'] ) { // phpcs:ignore
					$sent_email = str_replace( ' ', '+', sanitize_text_field( wp_unslash( $_GET['user_email'] ) ) ); // phpcs:ignore
					$key_data   = $wpdb->get_results( // phpcs:ignore
						$wpdb->prepare(
							'SELECT encrypt_key FROM `' . $wpdb->prefix . 'ac_sent_history_lite` WHERE sent_email_id = %s ORDER BY id DESC',
							$sent_email
						)
					);
					if ( $wpdb->num_rows > 0 && isset( $key_data[0]->encrypt_key ) ) {
						$crypt_key = $key_data[0]->encrypt_key;
					}
				}
				if ( ! empty( $crypt_key ) && null !== $crypt_key && '' !== $crypt_key ) {

					// ── SECURITY FIX #1081.1 (Shivamani Vastrala / v6.8.2) ───
					$hmac_result = self::wcal_verify_and_strip_hmac( $validate_encoded_string, $crypt_key );
					if ( 'tampered' === $hmac_result['status'] ) {
						// Tag present but invalid — hard reject, do not decrypt.
						if ( version_compare( $woocommerce->version, '3.0.0', '>=' ) ) {
							wp_safe_redirect( get_permalink( wc_get_page_id( 'shop' ) ) );
						} else {
							wp_safe_redirect( get_permalink( woocommerce_get_page_id( 'shop' ) ) );
						}
						exit;
					}
					// 'valid'  → HMAC verified, use stripped ciphertext.
					// 'legacy' → no tag (pre-6.8.2 email), token used as-is.
					$verified_ciphertext = $hmac_result['ciphertext'];
					// ── END FIX #1081.1 ──

					$link_decode       = Wcal_Aes_Ctr::decrypt( $verified_ciphertext, $crypt_key, 256 );
					$sent_email_id_pos = strpos( $link_decode, '&' );
					$email_sent_id     = substr( $link_decode, 0, $sent_email_id_pos );

					if ( isset( $_GET['c'] ) ) { // phpcs:ignore 
						$decrypt_coupon_code = rawurldecode( sanitize_text_field( wp_unslash( $_GET['c'] ) ) ); //phpcs:ignore
						$decrypt_coupon_code = str_replace( ' ', '+', $decrypt_coupon_code );
						$decode_coupon_code  = Wcal_Aes_Ctr::decrypt( $decrypt_coupon_code, $crypt_key, 256 );

						wcal_common::wcal_set_cart_session( 'wcal_c', $decode_coupon_code ); // we need to set in session coz we directly apply coupon.
						set_transient( 'wcal_c', $decode_coupon_code, 5 );
					} else {
						$decode_coupon_code = '';
					}

					if ( 'track_links' === $track_link ) {
						$email_sent_id     = 0;
						$sent_email_id_pos = strpos( $link_decode, '&' );
						$email_sent_id     = substr( $link_decode, 0, $sent_email_id_pos );

						wcal_common::wcal_set_cart_session( 'email_sent_id', $email_sent_id );
						set_transient( 'wcal_email_sent_id', $email_sent_id, 5 );

						$get_ac_id_results = $wpdb->get_results( // phpcs:ignore
							$wpdb->prepare(
								'SELECT abandoned_order_id FROM `' . $wpdb->prefix . 'ac_sent_history_lite` WHERE id = %d',
								$email_sent_id
							)
						);
						$abandoned_id      = $get_ac_id_results[0]->abandoned_order_id;
					} elseif ( 'checkout_link' === $track_link ) {
						$abandoned_id     = 0;
						$abandoned_id_pos = strpos( $link_decode, '&' );
						$abandoned_id     = (int) substr( $link_decode, 0, $abandoned_id_pos );
						wcal_common::wcal_set_cart_session( 'wcal_recovered_cart', true );
					}

					// ── SECURITY FIX #1081.2 (Shivamani Vastrala / v6.8.2).
					// Verify the decrypted abandoned_id belongs to the supplied user_email before proceeding. This prevents malicious users from manipulating the URL to access other users' carts.
					if ( $abandoned_id > 0 ) {
						$binding_check = $wpdb->get_var( // phpcs:ignore
							$wpdb->prepare(
								'SELECT COUNT(*) FROM `' . $wpdb->prefix . 'ac_sent_history_lite`
								 WHERE abandoned_order_id = %d
								   AND sent_email_id      = %s',
								$abandoned_id,
								$sent_email
							)
						);
						if ( ! $binding_check || intval( $binding_check ) < 1 ) {
							if ( version_compare( $woocommerce->version, '3.0.0', '>=' ) ) {
								wp_safe_redirect( get_permalink( wc_get_page_id( 'shop' ) ) );
							} else {
								wp_safe_redirect( get_permalink( woocommerce_get_page_id( 'shop' ) ) );
							}
							exit;
						}
					}
					// ── END FIX #1081.2 ──

					$url_pos = strpos( $link_decode, '=' );
					++$url_pos;
					$url = substr( $link_decode, $url_pos );

					wcal_common::wcal_set_cart_session( 'abandoned_cart_id_lite', $abandoned_id );
					set_transient( 'wcal_abandoned_id', $abandoned_id, 5 );

					$get_user_results = array();
					if ( $abandoned_id > 0 ) {
						$get_user_results  = $wpdb->get_results( //phpcs:ignore
							$wpdb->prepare(
								'SELECT user_id FROM `' . $wpdb->prefix . 'ac_abandoned_cart_history_lite` WHERE id = %d',
								$abandoned_id
							)
						);
					}
					$user_id = isset( $get_user_results ) && count( $get_user_results ) > 0 ? (int) $get_user_results[0]->user_id : 0;
				}

				if ( 0 === $user_id ) {
					echo esc_html( 'Link expired' );
					if ( version_compare( $woocommerce->version, '3.0.0', '>=' ) ) {
						wp_safe_redirect( get_permalink( wc_get_page_id( 'shop' ) ) );
						exit;
					} else {
						wp_safe_redirect( get_permalink( woocommerce_get_page_id( 'shop' ) ) );
						exit;
					}
				}
				$user = wp_set_current_user( $user_id ); //phpcs:ignore
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
					$user_login = $user->data->user_login; //phpcs:disable
					if ( 'on' === get_option( 'wcal_auto_login_users', '' ) ) {
						wp_set_auth_cookie( $user_id ); // nosemgrep:audit.php.wp.security.auth-bypass.wp-set-auth-cookie
						$my_temp = wc_load_persistent_cart( $user_login, $user );
						do_action( 'wp_login', $user_login, $user );
						if ( isset( $sign_in ) && is_wp_error( $sign_in ) ) {
							echo esc_html( $sign_in->get_error_message() );
							exit;
						}
					} else {
						wp_set_auth_cookie( $user_id, false, '', 'loggedout' ); // nosemgrep:audit.php.wp.security.auth-bypass.wp-set-auth-cookie
						$my_temp = wc_load_persistent_cart( $user_login, $user );
						set_transient( 'wcal_email_sent_id', $email_sent_id, 1800 );
						set_transient( 'wcal_user_id', $user_id, 1800 );
						$url = apply_filters( 'wcal_change_redirect_link', get_permalink( wc_get_page_id( 'myaccount' ) ) );
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
		 * Append an HMAC-SHA256 tag to an AES-CTR ciphertext token.
		 *
		 * The HMAC key is derived from the per-email encrypt_key already stored in
		 * ac_sent_history_lite, so no new secret needs to be introduced.
		 * Output format: <base64-ciphertext>.<hex-hmac>
		 *
		 * Security fix for CVE / coordinated disclosure by Shivamani Vastrala.
		 *
		 * @param string $ciphertext  Base64-encoded AES-CTR output.
		 * @param string $crypt_key   Per-email encryption key.
		 * @return string             Integrity-protected token.
		 * @since 6.8.2
		 */
		private static function wcal_append_hmac( $ciphertext, $crypt_key ) {
			$hmac = hash_hmac( 'sha256', $ciphertext, $crypt_key );
			return $ciphertext . '.' . $hmac;
		}

		/**
		 * Verify the HMAC tag on a recovery token and return the bare ciphertext.
		 *
		 * Uses hash_equals() for constant-time comparison to prevent timing oracles.
		 * Returns false for tokens with no tag (legacy tokens are rejected to avoid
		 * downgrade attacks) and for tokens whose tag does not match.
		 *
		 * Security fix for CVE / coordinated disclosure by Shivamani Vastrala.
		 *
		 * @param string $token      Full token (<ciphertext>.<hmac>).
		 * @param string $crypt_key  Per-email encryption key.
		 * @return string|false      Bare ciphertext on success; false on failure.
		 * @since 6.8.2
		 */
		private static function wcal_verify_and_strip_hmac( $token, $crypt_key ) {
			$dot = strrpos( $token, '.' );
			if ( false === $dot ) {
				// No dot separator — pre-6.8.2 legacy token with no HMAC tag.
				// Defense-in-depth (Issue #1084): after the configured grace-period
				// date, reject untagged tokens outright so that stripping the HMAC
				// suffix from a v6.8.2 token cannot be used as a downgrade attack.
				// Before that date, allow the legacy path so that recovery emails
				// sent before the 6.8.2 upgrade continue to work for customers.
				if ( defined( 'WCAL_REJECT_LEGACY_TOKENS_AFTER' ) &&
					strtotime( WCAL_REJECT_LEGACY_TOKENS_AFTER ) !== false &&
					time() >= strtotime( WCAL_REJECT_LEGACY_TOKENS_AFTER )
				) {
					// Grace period over — hard-reject untagged tokens.
					return array( 'status' => 'tampered', 'ciphertext' => false );
				}
				// Still within grace period — allow legacy path.
				return array( 'status' => 'legacy', 'ciphertext' => $token );
			}
			$ciphertext    = substr( $token, 0, $dot );
			$provided_hmac = substr( $token, $dot + 1 );
			$expected_hmac = hash_hmac( 'sha256', $ciphertext, $crypt_key );
			// Constant-time comparison prevents timing-based HMAC oracle attacks.
			if ( ! hash_equals( $expected_hmac, $provided_hmac ) ) {
				// Tag present but wrong — genuine tampering attempt; reject hard.
				return array( 'status' => 'tampered', 'ciphertext' => false );
			}
			return array( 'status' => 'valid', 'ciphertext' => $ciphertext );
		}

		/**
		 * Populate WC Session with values on user login.
		 *
		 * @since 5.14.0
		 */
		public static function wcal_populate_wc_session() {
			$email_sent_id = get_transient( 'wcal_email_sent_id' );
			$user_id       = get_transient( 'wcal_user_id' );
			if ( $email_sent_id && $user_id && (int) $email_sent_id > 0 && (int) $user_id > 0 ) {
				wcal_common::wcal_set_cart_session( 'wcal_email_sent_id', $email_sent_id );
				wcal_common::wcal_set_cart_session( 'wcal_user_id', $user_id );
			}
		}

		/**
		 * Redirect on cart page after user logged in.
		 *
		 * @param string $url - URL user is currently on.
		 *
		 * @since 5.16.0
		 */
		public static function ts_redirect_login( $url ) {

			if ( get_transient( 'wcal_email_sent_id' ) ) {
				$redirect_url = wc_get_cart_url();
				return $redirect_url;
			}
			return $url;
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
				if ( ! is_null( $saved_cart ) && isset( $saved_cart ) && is_array( $saved_cart ) && count( $saved_cart ) > 0 ) {
					foreach ( $saved_cart as $key => $value ) {
						if ( is_array( $value ) && count( $value ) > 0 ) {
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

			// Tyche JS constructor - needed for deactivation survey.
			wp_register_script(
				'wcal_tyche',
				WCAL_PLUGIN_URL . '/assets/js/tyche.js',
				array( 'jquery' ),
				1.1,
				true
			);
			wp_enqueue_script( 'wcal_tyche' );
			if ( '' === $page || 'woocommerce_ac_page' !== $page ) {
				return;
			} else {
				self::wcal_enqueue_admin_app();
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
						'ajax_url'                       => admin_url( 'admin-ajax.php' ),
						'ajax_nonce'                     => wp_create_nonce( 'delete_expired_used_coupon_code' ),
						'delete_coupon_confirmation_msg' => __( 'Are you sure you want delete the expired and used coupons created by Abandonment Cart Lite for WooCommerce Plugin?', 'woocommerce-abandoned-cart' ),
						'dismiss_notice_nonce'           => wp_create_nonce( 'wcal_dismiss_notice' ),
					)
				);
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
		 * Verify the nonce of the email templates database update link.
		 *
		 * @since 6.8.1
		 * @return void
		 */
		private function wcal_verify_admin_action_nonce() {
			$nonce = isset( $_REQUEST['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['_wpnonce'] ) ) : '';

			if ( '' !== $nonce && wp_verify_nonce( $nonce, 'wcal_update_email_templates' ) ) {
				return;
			}

			wp_die(
				esc_html__( 'The link you followed has expired. Please refresh the page and try again.', 'woocommerce-abandoned-cart' ),
				esc_html__( 'Security check failed', 'woocommerce-abandoned-cart' ),
				array( 'response' => 403 )
			);
		}

		/**
		 * The plugin's admin page: the mount point of the React app and its notices.
		 * @since 1.0
		 */
		public function wcal_menu_page() {

			if ( is_user_logged_in() ) {
				// Check the user capabilities.
				if ( ! current_user_can( 'manage_woocommerce' ) ) {
					wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'woocommerce-abandoned-cart' ) );
				}
				?>
				<div class="wrap">
				<?php

				if ( isset( $_GET['ac_update'] ) && 'email_templates' === sanitize_text_field( wp_unslash( $_GET['ac_update'] ) ) ) {
					// CSRF: ac_update triggers an ALTER TABLE — require a valid nonce.
					$this->wcal_verify_admin_action_nonce();
					$status = wcal_common::update_templates_table();

					if ( false !== $status ) {
						wcal_common::show_update_success();
					} else {
						wcal_common::show_update_failure();
					}
				}

				self::wcal_sync_coupon_schedule();
				settings_errors();
				?>
				<div id="wcal-admin-root"></div>
				<noscript>
					<div class="notice notice-error"><p><?php esc_html_e( 'JavaScript is required to use this page.', 'woocommerce-abandoned-cart' ); ?></p></div>
				</noscript>
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
				wp_add_inline_script(
					'jquery',
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
		 * Ajax function which will save the notice state as dismissed.
		 *
		 * @since 5.7
		 */
		public static function wcal_dismiss_admin_notice() {

			if ( ! current_user_can( 'manage_woocommerce' ) || ! isset( $_POST['ajax_nonce'] ) || ( isset( $_POST['ajax_nonce'] ) && ! wp_verify_nonce( sanitize_key( $_POST['ajax_nonce'] ), 'wcal_dismiss_notice' ) ) ) {
				wp_send_json( 'Security check failed' );
			}
			$notice_key = isset( $_POST['notice'] ) ? sanitize_text_field( wp_unslash( $_POST['notice'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
			if ( '' !== $notice_key ) {
				update_option( $notice_key, true );// nosemgrep
			}
			$notice_keys = isset( $_POST['notices'] ) ? sanitize_text_field( wp_unslash( $_POST['notices'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
			if ( '' !== $notice_keys ) {
				update_option( $notice_keys, true );// nosemgrep
			}
			die();
		}
		/**
		 * Ajax function which will save the notice state in database.
		 *
		 * @param str $new_value - new value.
		 * @param str $old_value - old value.
		 * @since 5.16.0
		 */
		public static function wcal_update_admin_notice_value( $new_value, $old_value ) {
			// Check test mode.
			if ( $new_value !== $old_value && ! empty( $new_value ) && 'on' === $new_value ) {
				update_option( 'wcal_auto_login_notice_dismiss', false );
			}
			return $new_value;
		}

		/**
		 * Set up from email address in emails sent by our plugin when WC template style is ON.
		 *
		 * @param str    $wp_admin_address - From email address set up in WP.
		 * @param object $email - Email object.
		 * @param str    $from_email - Email Address passed in.
		 *
		 * @since 5.13.0
		 */
		public static function wcal_from_address_for_emails( $wp_admin_address, $email ) {
			$from_address = '' == $email->title ? get_option( 'wcal_from_email' ) : $wp_admin_address; // phpcs:ignore
			return $from_address;
		}

		/**
		 * Set up from name in emails sent by our plugin when WC template style is ON.
		 *
		 * @param str    $wp_admin_name - From name set up in WP.
		 * @param object $email - Email object.
		 * @param str    $from_name_default - Name passed in.
		 *
		 * @since 5.13.0
		 */
		public static function wcal_from_name_for_emails( $wp_admin_name, $email ) {
			$from_name = '' == $email->title ? get_option( 'wcal_from_name' ) : $wp_admin_name; // phpcs:ignore
			return $from_name;
		}
		/**
		 * It will replace the test email data with the static content.
		 *
		 * @since 1.0
		 */
		public function wcal_preview_email_sent() {

			if ( ! current_user_can( 'manage_woocommerce' ) || ! isset( $_POST['ajax_nonce'] ) || ( isset( $_POST['ajax_nonce'] ) && ! wp_verify_nonce( sanitize_key( $_POST['ajax_nonce'] ), 'wcal_send_test_email' ) ) ) {
				wp_send_json_error( 'Security check failed' );
			}
			if ( isset( $_POST['body_email_preview'] ) && '' !== $_POST['body_email_preview'] ) {
				$from_email_name       = get_option( 'wcal_from_name' );
				$reply_name_preview    = get_option( 'wcal_from_email' );
				$from_email_preview    = get_option( 'wcal_reply_email' );
				$subject_email_preview = isset( $_POST['subject_email_preview'] ) ? stripslashes( sanitize_text_field( wp_unslash( $_POST['subject_email_preview'] ) ) ) : '';
				$subject_email_preview = convert_smilies( $subject_email_preview );
				$subject_email_preview = str_ireplace( '{{customer.firstname}}', apply_filters( 'wcal_abandoned_cart_email_content_customer_firstname', 'John', true, null ), $subject_email_preview );
				$body_email_preview    = isset( $_POST['body_email_preview'] ) ? convert_smilies( wp_unslash( $_POST['body_email_preview'] ) ) : ''; // phpcs:ignore
				$is_wc_template        = isset( $_POST['is_wc_template'] ) ? sanitize_text_field( wp_unslash( $_POST['is_wc_template'] ) ) : '';
				$wc_template_header    = isset( $_POST['wc_template_header'] ) ? stripslashes( sanitize_text_field( wp_unslash( $_POST['wc_template_header'] ) ) ) : '';
				$discount_expiry       = isset( $_POST['discount_expiry'] ) ? sanitize_text_field( wp_unslash( $_POST['discount_expiry'] ) ) : '';
				$discount_type         = isset( $_POST['discount_type'] ) ? sanitize_text_field( wp_unslash( $_POST['discount_type'] ) ) : '';
				$discount_shipping     = isset( $_POST['discount_shipping'] ) ? sanitize_text_field( wp_unslash( $_POST['discount_shipping'] ) ) : '';
				$individual_use        = isset( $_POST['individual_use'] ) ? sanitize_text_field( wp_unslash( $_POST['individual_use'] ) ) : '';
				$discount_amount       = isset( $_POST['discount_amount'] ) ? sanitize_text_field( wp_unslash( $_POST['discount_amount'] ) ) : '';
				$generate_unique_code  = isset( $_POST['generate_unique_code'] ) ? sanitize_text_field( wp_unslash( $_POST['generate_unique_code'] ) ) : '';
				$coupon_code           = isset( $_POST['coupon_code'][0] ) ? sanitize_text_field( wp_unslash( $_POST['coupon_code'][0] ) ) : '';
				$discount_details      = array();
				$coupon_code_to_apply  = '';
				if ( stripos( $body_email_preview, '{{coupon.code}}' ) ) {
					$discount_details['discount_expiry']      = $discount_expiry;
					$discount_details['discount_type']        = $discount_type;
					$discount_details['discount_shipping']    = $discount_shipping;
					$discount_details['individual_use']       = $individual_use;
					$discount_details['discount_amount']      = $discount_amount;
					$discount_details['generate_unique_code'] = $generate_unique_code;
					$default_template                         = '';

					$coupon_id   = isset( $coupon_code ) ? $coupon_code : '';
					$coupon_code = '';
					if ( '' !== $coupon_id ) {
						$coupon_to_apply = get_post( $coupon_id, ARRAY_A );
						$coupon_code     = $coupon_to_apply['post_title'];
					}

					$coupon_code_to_apply = wcal_common::wcal_get_coupon_email( $discount_details, $coupon_code, $default_template );
					$body_email_preview   = str_ireplace( '{{coupon.code}}', $coupon_code_to_apply, $body_email_preview );
				}

				$body_email_preview = str_ireplace( '{{customer.firstname}}', apply_filters( 'wcal_abandoned_cart_email_content_customer_firstname', 'John', true, null ), $body_email_preview );
				$body_email_preview = str_ireplace( '{{customer.lastname}}', apply_filters( 'wcal_abandoned_cart_email_content_customer_lastname', 'Doe', true, null ), $body_email_preview );
				$body_email_preview = str_ireplace( '{{customer.fullname}}', apply_filters( 'wcal_abandoned_cart_email_content_customer_fullname', 'John Doe', true, null ), $body_email_preview );
				$current_time_stamp = current_time( 'timestamp' ); // phpcs:ignore
				$date_format        = date_i18n( get_option( 'date_format' ), $current_time_stamp );
				$time_format        = date_i18n( get_option( 'time_format' ), $current_time_stamp );
				$test_date          = $date_format . ' ' . $time_format;
				$body_email_preview = str_ireplace( '{{cart.abandoned_date}}', apply_filters( 'wcal_abandoned_cart_email_content_cart_abandoned_date', $test_date, true, null ), $body_email_preview );
				$cart_url           = wc_get_page_permalink( 'cart' );
				$body_email_preview = str_ireplace( '{{cart.link}}', apply_filters( 'wcal_abandoned_cart_email_content_cart_link', $cart_url, true, null ), $body_email_preview );
				$body_email_preview = str_ireplace( '{{cart.unsubscribe}}', apply_filters( 'wcal_abandoned_cart_email_content_cart_unsubscribe', '#', true, null ), $body_email_preview );
				$wcal_price         = wc_price( '100' );
				$wcal_total_price   = wc_price( '200' );
				if ( class_exists( 'WP_Better_Emails' ) ) {
					$headers  = 'From: ' . $from_email_name . ' <' . $from_email_preview . '>' . "\r\n";
					$headers .= 'Content-Type: text/html' . "\r\n";
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
				$body_email_preview = str_ireplace( '{{products.cart}}', apply_filters( 'wcal_abandoned_cart_email_content_products.cart', $var, true, null ), $body_email_preview );
				if ( isset( $_POST['send_email_id'] ) ) {
					$to_email_preview = sanitize_text_field( wp_unslash( $_POST['send_email_id'] ) );
				} else {
					$to_email_preview = '';
				}
				$user_email_from          = get_option( 'admin_email' );
				$body_email_final_preview = stripslashes( $body_email_preview );
				$subject_email_preview    = str_replace('!', '', $subject_email_preview);

				if ( isset( $is_wc_template ) && 'true' === $is_wc_template ) {
					ob_start();
					// Get email heading.
					wc_get_template( 'emails/email-header.php', array( 'email_heading' => $wc_template_header ) );
					$email_body_template_header = ob_get_clean();

					ob_start();
					wc_get_template( 'emails/email-footer.php' );
					$email_body_template_footer = ob_get_clean();

					$site_title                 = get_bloginfo( 'name' );
					$email_body_template_footer = str_ireplace( '{site_title}', $site_title, $email_body_template_footer );

					$final_email_body = $email_body_template_header . $body_email_final_preview . $email_body_template_footer;

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

		/**
		 * Callback for Coupon settings section.
		 */
		public static function ac_lite_coupon_callback() {}

		/**
		 * Schedule or clear the automatic coupon deletion as per the saved setting.
		 *
		 * @since 6.10.0
		 */
		public static function wcal_sync_coupon_schedule() {
			if ( '' === get_option( 'wcal_delete_coupon_data', '' ) ) {
				wp_clear_scheduled_hook( 'woocommerce_ac_delete_coupon_action' );
			} elseif ( ! wp_next_scheduled( 'woocommerce_ac_delete_coupon_action' ) ) {
				wp_schedule_event( time(), 'wcal_15_days', 'woocommerce_ac_delete_coupon_action' );
			}
		}

		/**
		 * Option for deleting the plugin data upon uninstall.
		 *
		 * @param array $args Argument for adding field details.
		 * @since 8.3
		 */
		public static function wcal_deleting_coupon_data( $args ) {
			$wcal_delete_coupon_data = get_option( 'wcal_delete_coupon_data', '' );
			if ( isset( $wcal_delete_coupon_data ) && '' === $wcal_delete_coupon_data ) {
				$wcal_delete_coupon_data = 'off';
			}
			self::wcal_sync_coupon_schedule();

			?>
			<input type="checkbox" id="wcal_delete_coupon_data" name="wcal_delete_coupon_data" value="on" <?php echo checked( 'on', $wcal_delete_coupon_data, false ); ?> />
			<label for="wcal_delete_coupon_data"> <?php echo esc_attr( $args[0] ); ?></label>
			<a >
			<?php
		}
		/**
		 * It will delete the expired and used coupon codes.
		 *
		 * @hook wp_ajax_wcal_change_manual_email_data
		 * @since: 5.11.0
		 */
		public static function wcal_delete_expired_used_coupon_code() {

			global $wpdb;
			if ( ! current_user_can( 'manage_woocommerce' ) || ! isset( $_POST['ajax_nonce'] ) || ( isset( $_POST['ajax_nonce'] ) && ! wp_verify_nonce( sanitize_key( $_POST['ajax_nonce'] ), 'delete_expired_used_coupon_code' ) ) ) {
				wp_send_json_error( 'Security check failed' );
			}

			$expired_coupons = self::wcal_fetch_expired_coupons();
			$used_coupons    = self::wcal_fetch_used_coupons();
			$coupons         = array_unique( array_merge( $expired_coupons, $used_coupons ) );
			$coupon_count    = count( $coupons );

			if ( $coupon_count ) {
				$coupons_ids = implode( ',', $coupons );
				$wpdb->query( "DELETE FROM {$wpdb->postmeta} WHERE post_id IN(" . $coupons_ids . ')' );//phpcs:ignore
				$wpdb->query( "DELETE FROM {$wpdb->posts} WHERE ID IN(" . $coupons_ids . ')' );//phpcs:ignore
			}

			// translators: %1$s: Coupons Deleted, %2$s: Deleted coupons count'.
			wp_send_json_success( sprintf( __( '%1$s: %2$d', 'woo-cart-abandonment-recovery' ), 'Coupons Deleted', $coupon_count ) );
		}

		/**
		 * Coupon deletion manual button.
		 *
		 * @param array $args - Arguments for the setting.
		 */
		public static function wcal_deleting_coupon_data_manually( $args ) {
			?>
			<input type="button" class="button-secondary" id="wcal_delete_coupons" value="<?php esc_html_e( 'Delete', 'woocommerce-ac' ); ?>" >
			<label> <?php echo esc_attr( $args[0] ); ?></label>
			<br>
			<span class="wcal-spinner" style="display:none;">
				<img class="ajax_img" src="<?php echo esc_attr( WCAL_PLUGIN_URL . '/assets/images/ajax-loader.gif' ); ?>" />
			</span>
			<span class="wcal-coupon-response-msg"></span>
			<?php
		}
		/**
		 * It will fetch all expired coupons
		 *
		 * @hook wp_ajax_wcal_change_manual_email_data.
		 * @since: 5.11
		 */
		public static function wcal_fetch_expired_coupons() {
			$coupon_ids = array();

			$args = array(
				'posts_per_page' => -1,
				'post_type'      => 'shop_coupon',
				'post_status'    => 'publish',
				'meta_query'     => array( //phpcs:ignore
					'relation' => 'AND',
					array(
						'key'     => 'date_expires',
						'value'   => strtotime( 'today' ),
						'compare' => '<=',
					),
					array(
						'key'     => 'date_expires',
						'value'   => '',
						'compare' => '!=',
					),
					array(
						'key'     => 'wcal_created_by',
						'value'   => 'wcal',
						'compare' => '=',
					),
				),
			);

			$args    = array(
				'posts_per_page' => -1,
				'post_type'      => 'shop_coupon',
				'post_status'    => 'publish',
				'meta_query'     => array( // phpcs:ignore
					'relation' => 'AND',
					array(
						'key'     => 'date_expires',
						'value'   => strtotime( 'today' ),
						'compare' => '<=',
					),
					array(
						'key'     => 'date_expires',
						'value'   => '',
						'compare' => '!=',
					),
					array(
						'key'     => 'wcal_created_by',
						'value'   => 'wcal',
						'compare' => '=',
					),
				),
			);
			$coupons = get_posts( $args );
			if ( ! empty( $coupons ) ) {
				$current_time = current_time( 'timestamp' ); // phpcs:ignore

				foreach ( $coupons as $coupon ) {
					array_push( $coupon_ids, $coupon->ID );
				}
			}

			return $coupon_ids;
		}

		/**
		 * It will fetch all used coupons.
		 *
		 * @hook wp_ajax_wcal_change_manual_email_data
		 * @since: 4.2
		 */
		public static function wcal_fetch_used_coupons() {

			$coupon_ids = array();
			$args       = array(
				'posts_per_page' => -1,
				'post_type'      => 'shop_coupon',
				'post_status'    => 'publish',
				'meta_query'     => array( // phpcs:ignore
					'relation' => 'AND',
					array(
						'key'     => 'usage_count',
						'value'   => 0,
						'compare' => '>',
					),
					array(
						'key'     => 'wcal_created_by',
						'value'   => 'wcal',
						'compare' => '=',
					),
				),
			);

			$coupons = get_posts( $args );
			if ( ! empty( $coupons ) ) {
				foreach ( $coupons as $coupon ) {
					array_push( $coupon_ids, $coupon->ID );
				}
			}
			return $coupon_ids;
		}
		/**
		 * It will create the cron job interval.
		 * Default value will be 15 minutes.
		 * If customer has changed the cron job interval time from the settings then it will be considered.
		 *
		 * @param array $schedules Array of all schedule events.
		 * @return array $schedule Array of new added schedule event.
		 * @since 5.0
		 */
		public static function wcal_add_cron_schedule( $schedules ) {
			$duration = get_option( 'wcal_cron_time_duration' );
			if ( isset( $duration ) && $duration > 0 ) {
				$duration_in_seconds = $duration * 60;
			} else {
				$duration_in_seconds = 900;
			}
			$schedules['15_minutes'] = array(
				'interval' => $duration_in_seconds,  // 15 minutes in seconds
				'display'  => __( 'Once Every Fifteen Minutes' ),
			);

			$schedules['wcal_15_days'] = array(
				'interval' => 1296000, // 15 days in seconds
				'display'  => __( 'Once Every Fifteen Days' ),
			);
			return $schedules;
		}

		/**
		 * Callback for Abandoned cart Coupon settings.
		 */
		public static function wcal_coupon_callback() {
		}

		/**
		 * Callback for Coupon settings section.
		 */
		public static function ac_lite_rules_callback() {}

		public static function wcap_restrict_ip_address_callback( $args ) {

			$guest_msg = get_option( 'wcap_restrict_ip_address' );

			printf(
				"<textarea rows='4' cols='80' id='wcap_restrict_ip_address' name='wcap_restrict_ip_address'>" . htmlspecialchars( $guest_msg, ENT_QUOTES ) . '</textarea>' // phpcs:ignore
			);

			$html = '<label for="wcap_restrict_ip_address"> ' . $args[0] . '</label>';
			echo wp_kses_post( $html );
		}

		public static function wcap_restrict_email_address_callback( $args ) {

			$guest_msg = get_option( 'wcap_restrict_email_address' );

			printf(
				"<textarea rows='4' cols='80' id='wcap_restrict_email_address' name='wcap_restrict_email_address'>" . htmlspecialchars( $guest_msg, ENT_QUOTES ) . '</textarea>' // phpcs:ignore
			);

			$html = '<label for="wcap_restrict_email_address"> ' . $args[0] . '</label>';
			echo wp_kses_post( $html );
		}

		public static function wcap_restrict_domain_address_callback( $args ) {

			$guest_msg = get_option( 'wcap_restrict_domain_address' );

			printf(
				"<textarea rows='4' cols='80' id='wcap_restrict_domain_address' name='wcap_restrict_domain_address'>" . htmlspecialchars( $guest_msg, ENT_QUOTES ) . '</textarea>' // phpcs:ignore
			);

			$html = '<label for="wcap_restrict_domain_address"> ' . $args[0] . '</label>';
			echo wp_kses_post( $html );
		}

		public static function restriced_countries_callback( $args ) {

			$stored_value = get_option( 'wcap_restrict_countries', '' );
    		$guest_msg    = ! empty( $stored_value ) ? explode( ',', $stored_value ) : array();

			$wc_countries_object = new WC_Countries();
			$all_countries_list  = $wc_countries_object->get_countries();
			?>
			
			<select
				class="select2 wcap_restrict_countries"
				id="wcap_restrict_countries"
				name="wcap_restrict_countries[]"
				multiple="multiple"
				data-placeholder="<?php esc_attr_e( 'Select countries', 'woocommerce-abandoned-cart' ); ?>"
			>
				<?php foreach ( $all_countries_list as $code => $name ) : ?>
					<option
						value="<?php echo esc_attr( $code ); ?>"
						<?php selected( in_array( $code, $guest_msg, true ), true ); ?>
					>
						<?php echo esc_html( $name ); ?>
					</option>
				<?php endforeach; ?>
			</select>
			<label for="wcap_restrict_countries"><?php echo wp_kses_post( $args[0] ); ?></label>
			<?php
		}

		/**
		 * Callback for GDPR settings section.
		 */
		public static function ac_lite_gdpr_callback() {}

		/**
		 * Skip js files during plugin check validations.
		 *
		 * @param array $checks An array of checks to ignore.
		 * @since 9.16.0
		 */
		public static function wcal_plugin_check_ignore_files( $checks ) {
			unset( $checks['i18n_usage'] );
			unset( $checks['plugin_header_fields'] );
			unset( $checks['file_type'] );
			unset( $checks['trademarks'] );
			unset( $checks['plugin_readme'] );
			unset( $checks['enqueued_resources'] );
			unset( $checks['offloading_files'] );
			return $checks;
		}
	}
}
$woocommerce_abandon_cart = new woocommerce_abandon_cart_lite();
