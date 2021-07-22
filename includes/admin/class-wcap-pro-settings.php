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
		 * Construct
		 *
		 * @since 2.4
		 */
		public function __construct() {
			add_action( 'admin_init', array( &$this, 'wcal_pro_settings' ) );
			add_action( 'wcal_add_new_settings', array( &$this, 'wcap_pro_general_settings' ) );
		}

		/**
		 * ATC Settings.
		 */
		public static function wcap_atc_settings() {

			wp_enqueue_style( 'wcap_modal_preview', WCAL_PLUGIN_URL . '/assets/css/admin/wcap_preview_modal.css', '', WCAL_PLUGIN_VERSION );
			wp_enqueue_style( 'wcap_add_to_cart_popup_modal', WCAL_PLUGIN_URL . '/assets/css/admin/wcap_add_to_cart_popup_modal.min.css', '', WCAL_PLUGIN_VERSION );
			$purchase_link = 'https://www.tychesoftwares.com/store/premium-plugins/woocommerce-abandoned-cart-pro/?utm_source=acupgradetopro&utm_medium=link&utm_campaign=AbandonCartLite';
			?>

			<form method="post" action="admin.php?page=woocommerce_ac_page&action=emailsettings&wcal_section=wcap_atc_settings">
					<p style="font-size:15px;">
						<b><i>
						<?php
						// translators: %s Purchase Link.
						printf( wp_kses_post( __( "Upgrade to <a href='%s' target='_blank'>Abandoned Cart Pro for WooCommerce</a> to enable the feature.", 'woocommerce-abandoned-cart' ) ), esc_url( $purchase_link ) );
						?>
						</i></b>
					</p>

				<?php Wcap_Add_Cart_Popup_Modal::wcap_add_to_cart_popup_settings(); ?>
			</form>

			<?php
		}

		/**
		 * FB Settings for AC Pro.
		 */
		public static function wcap_fb_settings() {
			?>

				<form method="post" action="options.php">
					<?php
					settings_fields( 'woocommerce_fb_settings' );
					do_settings_sections( 'woocommerce_ac_fb_page' );
					submit_button( __( 'Save Changes', 'woocommerce-abandoned-cart' ), 'primary', 'submit', true, array( 'disabled' => 'disabled' ) );
					?>
				</form>

			<?php
		}

		/**
		 * General Settings for AC Pro.
		 */
		public static function wcap_pro_general_settings() {

			$upgrade_pro_msg = '<br><b><i>Upgrade to <a href="https://www.tychesoftwares.com/store/premium-plugins/woocommerce-abandoned-cart-pro/?utm_source=acupgradetopro&utm_medium=link&utm_campaign=AbandonCartLite" target="_blank">Abandoned Cart Pro for WooCommerce</a> to enable the setting.</i></b>';

			add_settings_field(
				'ac_cart_abandoned_time_guest',
				__( 'Cart abandoned cut-off time for guest users', 'woocommerce-abandoned-cart' ),
				array( 'WCAP_Pro_Settings_Callbacks', 'wcap_cart_abandoned_time_guest_callback' ),
				'woocommerce_ac_page',
				'ac_lite_general_settings_section',
				array( __( 'For guest users & visitors consider cart abandoned after X minutes of item being added to cart & order not placed.', 'woocommerce-abandoned-cart' ) . $upgrade_pro_msg )
			);

			add_settings_field(
				'ac_disable_guest_cart_email',
				__( 'Do not track carts of guest users', 'woocommerce-abandoned-cart' ),
				array( 'WCAP_Pro_Settings_Callbacks', 'wcap_disable_guest_cart_email_callback' ),
				'woocommerce_ac_page',
				'ac_lite_general_settings_section',
				array( __( 'Abandoned carts of guest users will not be tracked.', 'woocommerce-abandoned-cart' ) . $upgrade_pro_msg )
			);

			add_settings_field(
				'ac_disable_logged_in_cart_email',
				__( 'Do not track carts of logged-in users', 'woocommerce-abandoned-cart' ),
				array( 'WCAP_Pro_Settings_Callbacks', 'wcap_disable_logged_in_cart_email_callback' ),
				'woocommerce_ac_page',
				'ac_lite_general_settings_section',
				array( __( 'Abandoned carts of logged-in users will not be tracked.', 'woocommerce-abandoned-cart' ) . $upgrade_pro_msg )
			);

			add_settings_field(
				'ac_cart_abandoned_after_x_days_order_placed',
				__( 'Send reminder emails for newly abandoned carts after X days of order placement', 'woocommerce-abandoned-cart' ),
				array( 'WCAP_Pro_Settings_Callbacks', 'wcap_cart_abandoned_after_x_days_order_placed_callback' ),
				'woocommerce_ac_page',
				'ac_lite_general_settings_section',
				array( __( 'Reminder emails will be sent for newly abandoned carts only after X days of a previously placed order for a user with the same email address as that of the abandoned cart', 'woocommerce-abandoned-cart' ) . $upgrade_pro_msg )
			);

			add_settings_field(
				'ac_capture_email_from_forms',
				__( 'Capture email address from custom fields.', 'woocommerce-abandoned-cart' ),
				array( 'WCAP_Pro_Settings_Callbacks', 'wcap_capture_email_from_forms' ),
				'woocommerce_ac_page',
				'ac_lite_general_settings_section',
				array( __( 'Enable this setting to capture email address from other form fields.', 'woocommerce-abandoned-cart' ) . $upgrade_pro_msg )
			);

			add_settings_field(
				'ac_email_forms_classes',
				__( 'Class names of the form fields.', 'woocommerce-abandoned-cart' ),
				array( 'WCAP_Pro_Settings_Callbacks', 'wcap_email_forms_classes' ),
				'woocommerce_ac_page',
				'ac_lite_general_settings_section',
				array( __( 'Enter class names of fields separated by commas from where email needs to be captured.', 'woocommerce-abandoned-cart' ) . $upgrade_pro_msg )
			);

			add_settings_field(
				'ac_capture_email_address_from_url',
				__( 'Capture Email address from URL', 'woocommerce-abandoned-cart' ),
				array( 'WCAP_Pro_Settings_Callbacks', 'wcap_capture_email_address_from_url' ),
				'woocommerce_ac_page',
				'ac_lite_general_settings_section',
				array( __( 'If your site URL contain the same key, then it will capture it as an email address of customer.', 'woocommerce-abandoned-cart' ) . $upgrade_pro_msg )
			);

			add_settings_field(
				'wcac_delete_plugin_data',
				__( 'Remove Data on Uninstall?', 'woocommerce-abandoned-cart' ),
				array( 'WCAP_Pro_Settings_Callbacks', 'wcap_deleting_plugin_data' ),
				'woocommerce_ac_page',
				'ac_lite_general_settings_section',
				array( __( 'Enable this setting if you want to completely remove Abandoned Cart data when plugin is deleted.', 'woocommerce-abandoned-cart' ) . $upgrade_pro_msg )
			);

			add_settings_field(
				'wcap_atc_close_icon_add_product_to_cart',
				__( 'Add Product to Cart when Close Icon is clicked?', 'woocommerce-abandoned-cart' ),
				array( 'WCAP_Pro_Settings_Callbacks', 'wcap_atc_close_icon_add_product_to_cart_callback' ),
				'woocommerce_ac_page',
				'ac_lite_general_settings_section',
				array( __( 'Enable this setting if you want the product to the added to cart when the user clicks on the Close Icon in the Add to Cart Popup Modal.', 'woocommerce-abandoned-cart' ) . $upgrade_pro_msg )
			);

			add_settings_field(
				'wcap_enable_debounce',
				__( 'Enable Email Verification:', 'woocommerce-abandoned-cart' ),
				array( 'WCAP_Pro_Settings_Callbacks', 'wcap_enable_debounce_callback' ),
				'woocommerce_ac_page',
				'ac_lite_general_settings_section',
				array( __( 'Enable this checkbox to allow email verification to be done via DeBounce API services.', 'woocommerce-abandoned-cart' ) . $upgrade_pro_msg )
			);

			add_settings_field(
				'ac_debounce_api',
				__( 'Enter DeBounce API Key', 'woocommerce-abandoned-cart' ),
				array( 'WCAP_Pro_Settings_Callbacks', 'wcap_debounce_api_callback' ),
				'woocommerce_ac_page',
				'ac_lite_general_settings_section',
				array( __( 'Enter DeBounce JS API Key.', 'woocommerce-abandoned-cart' ) . $upgrade_pro_msg )
			);

			add_settings_section(
				'ac_coupon_settings_section',           // ID used to identify this section and with which to register options.
				__( 'Coupon Settings', 'woocommerce-abandoned-cart' ),      // Title to be displayed on the administration page.
				array( 'WCAP_Pro_Settings_Callbacks', 'wcap_coupon_callback' ), // Callback used to render the description of the section.
				'woocommerce_ac_page'     // Page on which to add this section of options.
			);

			add_settings_field(
				'wcap_delete_coupon_data',
				__( 'Delete Coupons Automatically', 'woocommerce-abandoned-cart' ),
				array( 'WCAP_Pro_Settings_Callbacks', 'wcap_deleting_coupon_data' ),
				'woocommerce_ac_page',
				'ac_coupon_settings_section',
				array( __( 'Enable this setting if you want to completely remove the expired and used coupon code automatically every 15 days.', 'woocommerce-abandoned-cart' ) . $upgrade_pro_msg )
			);

			add_settings_field(
				'wcap_delete_coupon_data_manually',
				__( 'Delete Coupons Manually', 'woocommerce-abandoned-cart' ),
				array( 'WCAP_Pro_Settings_Callbacks', 'wcap_deleting_coupon_data_manually' ),
				'woocommerce_ac_page',
				'ac_coupon_settings_section',
				array( __( 'If you want to completely remove the expired and used coupon code now then click on "Delete" button.', 'woocommerce-abandoned-cart' ) . $upgrade_pro_msg )
			);

			register_setting(
				'woocommerce_ac_settings',
				'ac_enable_cart_emails'
			);

			register_setting(
				'woocommerce_ac_settings',
				'ac_cart_abandoned_time_guest'
			);

			register_setting(
				'woocommerce_ac_settings',
				'ac_disable_guest_cart_email'
			);

			register_setting(
				'woocommerce_ac_settings',
				'ac_disable_logged_in_cart_email'
			);

			register_setting(
				'woocommerce_ac_settings',
				'ac_capture_email_from_forms'
			);

			register_setting(
				'woocommerce_ac_settings',
				'ac_cart_abandoned_after_x_days_order_placed'
			);

			register_setting(
				'woocommerce_ac_settings',
				'ac_email_forms_classes'
			);

			register_setting(
				'woocommerce_ac_settings',
				'ac_capture_email_address_from_url'
			);

			register_setting(
				'woocommerce_ac_settings',
				'wcac_delete_plugin_data'
			);

			register_setting(
				'woocommerce_ac_settings',
				'wcap_atc_close_icon_add_product_to_cart'
			);

			register_setting(
				'woocommerce_ac_settings',
				'wcap_enable_debounce'
			);

			register_setting(
				'woocommerce_ac_settings',
				'ac_debounce_api'
			);

			add_settings_field(
				'wcap_product_image_size',
				__( 'Product Image( H x W )', 'woocommerce-abandoned-cart' ),
				array( 'WCAP_Pro_Settings_Callbacks', 'wcap_product_image_size_callback' ),
				'woocommerce_ac_email_page',
				'ac_email_settings_section',
				array( __( 'This setting affects the dimension of the product image in the abandoned cart reminder email.', 'woocommerce-abandoned-cart' ) . $upgrade_pro_msg )
			);

			add_settings_field(
				'wcap_product_name_redirect',
				__( 'Product Name Redirects to', 'woocommerce-ac' ),
				array( 'WCAP_Pro_Settings_Callbacks', 'wcap_product_name_redirect_callback' ),
				'woocommerce_ac_email_page',
				'ac_email_settings_section',
				array( __( 'Select the page where product name in reminder emails should redirect to.', 'woocommerce-ac' ) . $upgrade_pro_msg )
			);

			register_setting(
				'ac_email_settings_section',
				'wcap_product_image_size'
			);

			register_setting(
				'woocommerce_ac_settings',
				'wcap_product_name_redirect'
			);

			add_settings_section(
				'ac_cron_job_settings_section',           // ID used to identify this section and with which to register options.
				__( 'Setting for sending Emails & SMS using Action Scheduler', 'woocommerce-abandoned-cart' ),      // Title to be displayed on the administration page.
				array( 'WCAP_Pro_Settings_Callbacks', 'wcap_cron_job_callback' ), // Callback used to render the description of the section.
				'woocommerce_ac_page'     // Page on which to add this section of options.
			);

			add_settings_field(
				'wcap_use_auto_cron',
				__( 'Send  Abandoned cart emails automatically using Action Scheduler', 'woocommerce-abandoned-cart' ),
				array( 'WCAP_Pro_Settings_Callbacks', 'wcap_use_auto_cron_callback' ),
				'woocommerce_ac_page',
				'ac_cron_job_settings_section',
				array( __( 'Enabling this setting will send the abandoned cart reminder emails to the customer after the set time. If disabled, abandoned cart reminder emails will not be sent using the Action Scheduler. You will need to set cron job manually from cPanel. If you are unsure how to set the cron job, please <a href= mailto:support@tychesoftwares.com>contact us</a> for it.', 'woocommerce-abandoned-cart' ) . $upgrade_pro_msg )
			);

			add_settings_field(
				'wcap_cron_time_duration',
				__( 'Run automated Scheduler every X minutes', 'woocommerce-abandoned-cart' ),
				array( 'WCAP_Pro_Settings_Callbacks', 'wcap_cron_time_duration_callback' ),
				'woocommerce_ac_page',
				'ac_cron_job_settings_section',
				array( __( 'The duration in minutes after which an action should be automatically scheduled to send email, SMS & FB reminders to customers.', 'woocommerce-abandoned-cart' ) . $upgrade_pro_msg )
			);

			add_settings_section(
				'ac_restrict_settings_section',           // ID used to identify this section and with which to register options.
				__( 'Rules to exclude capturing abandoned carts', 'woocommerce-abandoned-cart' ),      // Title to be displayed on the administration page.
				array( 'WCAP_Pro_Settings_Callbacks', 'wcap_custom_restrict_callback' ), // Callback used to render the description of the section.
				'woocommerce_ac_page'     // Page on which to add this section of options.
			);

			add_settings_field(
				'wcap_restrict_ip_address',
				__( 'Do not capture abandoned carts for these IP addresses', 'woocommerce-abandoned-cart' ),
				array( 'WCAP_Pro_Settings_Callbacks', 'wcap_restrict_ip_address_callback' ),
				'woocommerce_ac_page',
				'ac_restrict_settings_section',
				array( __( 'The carts abandoned from these IP addresses will not be tracked by the plugin. Accepts wildcards, e.g <code>192.168.*</code> will block all IP addresses which starts from "192.168". <i>Separate IP addresses with commas.</i>', 'woocommerce-abandoned-cart' ) . $upgrade_pro_msg )
			);

			add_settings_field(
				'wcap_restrict_email_address',
				__( 'Do not capture abandoned carts for these email addresses', 'woocommerce-abandoned-cart' ),
				array( 'WCAP_Pro_Settings_Callbacks', 'wcap_restrict_email_address_callback' ),
				'woocommerce_ac_page',
				'ac_restrict_settings_section',
				array( __( 'The carts abandoned using these email addresses will not be tracked by the plugin. <i>Separate email addresses with commas.</i>', 'woocommerce-abandoned-cart' ) . $upgrade_pro_msg )
			);

			add_settings_field(
				'wcap_restrict_domain_address',
				__( 'Do not capture abandoned carts for email addresses from these domains', 'woocommerce-abandoned-cart' ),
				array( 'WCAP_Pro_Settings_Callbacks', 'wcap_restrict_domain_address_callback' ),
				'woocommerce_ac_page',
				'ac_restrict_settings_section',
				array( __( 'The carts abandoned from email addresses with these domains will not be tracked by the plugin. <i>Separate email address domains with commas.</i>', 'woocommerce-abandoned-cart' ) . $upgrade_pro_msg )
			);

			add_settings_section(
				'ac_unsubscribe_section',
				__( 'Unsubscribe Settings', 'woocommerce-abandoned-cart' ),
				array( 'WCAP_Pro_Settings_Callbacks', 'wcap_unsubscribe_options_callback' ),
				'woocommerce_ac_page'
			);

			$doc_link = 'https://www.tychesoftwares.com/docs/docs/abandoned-cart-pro-for-woocommerce/unsubscribe-landing-page-options';
			add_settings_field(
				'wcap_unsubscribe_landing_page',
				__( 'Unsubscribe Landing Page', 'woocommerce-abandoned-cart' ),
				array( 'WCAP_Pro_Settings_Callbacks', 'wcap_unsubscribe_landing_page_callback' ),
				'woocommerce_ac_page',
				'ac_unsubscribe_section',
				array( __( "Select a source where the user must be redirected when an Unsubscribe link is clicked from reminders sent. For details, please check the <a href='$doc_link' target='_blank'>documentation</a>.", 'woocommerce-abandoned-cart' ) . $upgrade_pro_msg ) // phpcs:ignore
			);

			register_setting(
				'woocommerce_ac_settings',
				'wcap_unsubscribe_landing_page'
			);
		}

		/**
		 * SMS Settings for AC Pro
		 */
		public static function wcap_sms_settings() {
			?>

			<form method="post" action="options.php">
				<?php
				settings_fields( 'woocommerce_sms_settings' );
				do_settings_sections( 'woocommerce_ac_sms_page' );
				submit_button( __( 'Save Changes', 'woocommerce-abandoned-cart' ), 'primary', 'submit', true, array( 'disabled' => 'disabled' ) );
				?>
			</form>

			<div id="test_fields">

				<h2><?php echo esc_html_e( 'Send Test SMS', 'woocommerce-abandoned-cart' ); ?></h2>
				<div id="status_msg" style="background: white;border-left: #6389DA 4px solid;padding: 10px;display: none;width: 90%;"></div>
				<table class="form-table">
					<tr>
						<th><?php echo esc_html_e( 'Recipient', 'woocommerce-abandoned-cart' ); ?></th>
						<td>
							<input id="test_number" name="test_number" type=text readonly />
							<i><?php echo esc_html_e( 'Must be a valid phone number in E.164 format.', 'woocommerce-abandoned-cart' ); ?></i>
						</td>
					</tr>

					<tr>
						<th><?php echo esc_html_e( 'Message', 'woocommerce-abandoned-cart' ); ?></th>
						<td><textarea id="test_msg" rows="4" cols="70" readonly ><?php echo esc_html_e( 'Hello World!', 'woocommerce-abandoned-cart' ); ?></textarea></td>
					</tr>

					<tr>
						<td colspan="2"><input type="button" id="wcap_test_sms" class="button-primary" value="<?php echo esc_html_e( 'Send', 'wocommerce-ac' ); ?>" /></td>
					</tr>
				</table>
			</div>

			<?php
		}

		/**
		 * Pro Settings for SMS.
		 */
		public function wcal_pro_settings() {

			$upgrade_pro_msg = '<br><b><i>Upgrade to <a href="https://www.tychesoftwares.com/store/premium-plugins/woocommerce-abandoned-cart-pro/?utm_source=acupgradetopro&utm_medium=link&utm_campaign=AbandonCartLite" target="_blank">Abandoned Cart Pro for WooCommerce</a> to enable the setting.</i></b>';

			// New Settings for SMS Notifications.
			add_settings_section(
				'wcap_sms_settings_section',        // ID used to identify this section and with which to register options.
				__( 'Twilio', 'woocommerce-abandoned-cart' ),       // Title to be displayed on the administration page.
				array( 'WCAP_Pro_Settings_Callbacks', 'wcap_sms_settings_section_callback' ),     // Callback used to render the description of the section.
				'woocommerce_ac_sms_page'               // Page on which to add this section of options.
			);

			add_settings_field(
				'wcap_enable_sms_reminders',
				__( 'Enable SMS', 'woocommerce-abandoned-cart' ),
				array( 'WCAP_Pro_Settings_Callbacks', 'wcap_enable_sms_reminders_callback' ),
				'woocommerce_ac_sms_page',
				'wcap_sms_settings_section',
				array( __( '<i>Enable the ability to send reminder SMS for abandoned carts.</i>', 'woocommerce-abandoned-cart' ) . $upgrade_pro_msg ) // phpcs:ignore
			);

			add_settings_field(
				'wcap_sms_from_phone',
				__( 'From', 'woocommerce-abandoned-cart' ),
				array( 'WCAP_Pro_Settings_Callbacks', 'wcap_sms_from_phone_callback' ),
				'woocommerce_ac_sms_page',
				'wcap_sms_settings_section',
				array( __( '<i>Must be a Twilio phone number (in E.164 format) or alphanumeric sender ID.</i>', 'woocommerce-abandoned-cart' ) . $upgrade_pro_msg ) // phpcs:ignore
			);

			add_settings_field(
				'wcap_sms_account_sid',
				__( 'Account SID', 'woocommerce-abandoned-cart' ),
				array( 'WCAP_Pro_Settings_Callbacks', 'wcap_sms_account_sid_callback' ),
				'woocommerce_ac_sms_page',
				'wcap_sms_settings_section',
				array( $upgrade_pro_msg )
			);

			add_settings_field(
				'wcap_sms_auth_token',
				__( 'Auth Token', 'woocommerce-abandoned-cart' ),
				array( 'WCAP_Pro_Settings_Callbacks', 'wcap_sms_auth_token_callback' ),
				'woocommerce_ac_sms_page',
				'wcap_sms_settings_section',
				array( $upgrade_pro_msg )
			);

			register_setting(
				'woocommerce_sms_settings',
				'wcap_enable_sms_reminders'
			);

			register_setting(
				'woocommerce_sms_settings',
				'wcap_sms_from_phone'
			);

			register_setting(
				'woocommerce_sms_settings',
				'wcap_sms_account_sid'
			);

			register_setting(
				'woocommerce_sms_settings',
				'wcap_sms_auth_token'
			);

			add_settings_section(
				'wcap_fb_settings_section',
				__( 'Facebook Messenger Settings', 'woocommerce-abandoned-cart' ),
				array( 'WCAP_Pro_Settings_Callbacks', 'wcap_fb_description' ),
				'woocommerce_ac_fb_page'
			);

			add_settings_field(
				'wcap_enable_fb_reminders',
				__( 'Enable Facebook Messenger Reminders', 'woocommerce-abandoned-cart' ),
				array( 'WCAP_Pro_Settings_Callbacks', 'wcap_fb_checkbox_callback' ),
				'woocommerce_ac_fb_page',
				'wcap_fb_settings_section',
				array( wp_kses_post( __( '<i>This option will display a checkbox after the Add to cart button for user consent to connect with Facebook.</i>', 'woocommerce-abandoned-cart' ), 'wcap_enable_fb_reminders' ) . $upgrade_pro_msg ) // phpcs:ignore
			);

			add_settings_field(
				'wcap_enable_fb_reminders_popup',
				__( 'Facebook Messenger on Add to Cart Pop-up modal', 'woocommerce-abandoned-cart' ),
				array( 'WCAP_Pro_Settings_Callbacks', 'wcap_fb_checkbox_callback' ),
				'woocommerce_ac_fb_page',
				'wcap_fb_settings_section',
				array( wp_kses_post( __( '<i>This option will display a checkbox on the pop-up modal to connect with Facebook.</i>', 'woocommerce-abandoned-cart' ), 'wcap_enable_fb_reminders_popup' ) . $upgrade_pro_msg ) // phpcs:ignore
			);

			add_settings_field(
				'wcap_fb_user_icon',
				__( 'Icon size of user', 'woocommerce-abandoned-cart' ),
				array( 'WCAP_Pro_Settings_Callbacks', 'wcap_fb_dropdown_callback' ),
				'woocommerce_ac_fb_page',
				'wcap_fb_settings_section',
				array(
					__( '<i>Select the size of user icon which shall be displayed below the checkbox in case the user is logged in.</i>', 'woocommerce-abandoned-cart' ) . $upgrade_pro_msg, // phpcs:ignore
					'wcap_fb_user_icon',
					array(
						'small'    => __( 'Small', 'woocommerce-abandoned-cart' ),
						'medium'   => __( 'Medium', 'woocommerce-abandoned-cart' ),
						'large'    => __( 'Large', 'woocommerce-abandoned-cart' ),
						'standard' => __( 'Standard', 'woocommerce-abandoned-cart' ),
						'xlarge'   => __( 'Extra Large', 'woocommerce-abandoned-cart' ),
					),
				)
			);

			add_settings_field(
				'wcap_fb_consent_text',
				__( 'Consent text', 'woocommerce-abandoned-cart' ),
				array( 'WCAP_Pro_Settings_Callbacks', 'wcap_fb_text_callback' ),
				'woocommerce_ac_fb_page',
				'wcap_fb_settings_section',
				array( __( '<i>Text that will appear above the consent checkbox. HTML tags are also allowed.</i>', 'woocommerce-abandoned-cart' ) . $upgrade_pro_msg, 'wcap_fb_consent_text' ) // phpcs:ignore
			);

			add_settings_field(
				'wcap_fb_page_id',
				__( 'Facebook Page ID', 'woocommerce-abandoned-cart' ),
				array( 'WCAP_Pro_Settings_Callbacks', 'wcap_fb_text_callback' ),
				'woocommerce_ac_fb_page',
				'wcap_fb_settings_section',
				array( __( "<i>Facebook Page ID in numberic format. You can find your page ID from <a href='https://www.tychesoftwares.com/docs/docs/abandoned-cart-pro-for-woocommerce/send-abandoned-cart-reminder-notifications-using-facebook-messenger#fbpageid' target='_blank'>here</a></i>", 'woocommerce-abandoned-cart' ) . $upgrade_pro_msg, 'wcap_fb_page_id' )
			);

			add_settings_field(
				'wcap_fb_app_id',
				__( 'Messenger App ID', 'woocommerce-abandoned-cart' ),
				array( 'WCAP_Pro_Settings_Callbacks', 'wcap_fb_text_callback' ),
				'woocommerce_ac_fb_page',
				'wcap_fb_settings_section',
				array( __( '<i>Enter your Messenger App ID</i>', 'woocommerce-abandoned-cart' ) . $upgrade_pro_msg, 'wcap_fb_app_id' ) // phpcs:ignore
			);

			add_settings_field(
				'wcap_fb_page_token',
				__( 'Facebook Page Token', 'woocommerce-abandoned-cart' ),
				array( 'WCAP_Pro_Settings_Callbacks', 'wcap_fb_text_callback' ),
				'woocommerce_ac_fb_page',
				'wcap_fb_settings_section',
				array( __( '<i>Enter your Facebook Page Token</i>', 'woocommerce-abandoned-cart' ) . $upgrade_pro_msg, 'wcap_fb_page_token' ) // phpcs:ignore
			);

			add_settings_field(
				'wcap_fb_verify_token',
				__( 'Verify Token', 'woocommerce-abandoned-cart' ),
				array( 'WCAP_Pro_Settings_Callbacks', 'wcap_fb_text_callback' ),
				'woocommerce_ac_fb_page',
				'wcap_fb_settings_section',
				array( __( '<i>Enter your Verify Token</i>', 'woocommerce-abandoned-cart' ) . $upgrade_pro_msg, 'wcap_fb_verify_token' ) // phpcs:ignore
			);

			register_setting(
				'woocommerce_fb_settings',
				'wcap_enable_fb_reminders'
			);

			register_setting(
				'woocommerce_fb_settings',
				'wcap_enable_fb_reminders_popup'
			);

			register_setting(
				'woocommerce_fb_settings',
				'wcap_fb_consent_text'
			);

			register_setting(
				'woocommerce_fb_settings',
				'wcap_fb_page_id'
			);

			register_setting(
				'woocommerce_fb_settings',
				'wcap_fb_user_icon'
			);

			register_setting(
				'woocommerce_fb_settings',
				'wcap_fb_app_id'
			);

			register_setting(
				'woocommerce_fb_settings',
				'wcap_fb_page_token'
			);

			register_setting(
				'woocommerce_fb_settings',
				'wcap_fb_verify_token'
			);

		}

	} // end of class.

	$wcap_pro_settings = new WCAP_Pro_Settings();

} // end if.
