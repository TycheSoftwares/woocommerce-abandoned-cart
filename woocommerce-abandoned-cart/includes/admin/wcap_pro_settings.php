<?php

/**

 * Display all the settings in PRO

 * 

 * @since 2.4

 */

// Exit if accessed directly

if ( ! defined( 'ABSPATH' ) ) exit;



if ( ! class_exists('WCAP_Pro_Settings' ) ) {



    class WCAP_Pro_Settings {

    

        /**

         * Construct

         * @since 2.4

         */

        public function __construct() {



            add_action( 'admin_init', array( &$this, 'wcal_pro_settings' ) );            

            add_action( 'wcal_add_new_settings', array(&$this, 'wcap_pro_general_settings' ) );

        }

        

        static function wcap_atc_settings() {



            wp_enqueue_style( 'wcap_modal_preview',           WCAL_PLUGIN_URL . '/assets/css/admin/wcap_preview_modal.css' );

            wp_enqueue_style( 'wcap_add_to_cart_popup_modal', WCAL_PLUGIN_URL . '/assets/css/admin/wcap_add_to_cart_popup_modal.min.css' );


            $purchase_link = 'https://www.tychesoftwares.com/store/premium-plugins/woocommerce-abandoned-cart-pro/';

            ?>

            <form method="post" action="admin.php?page=woocommerce_ac_page&action=emailsettings&wcal_section=wcap_atc_settings">

                	<p style="font-size:15px;">

                		<b><i><?php _e( "Upgrade to <a href='$purchase_link' target='_blank'>Abandoned Cart Pro for WooCommerce</a> to enable the feature.", 'woocommerce-abandoned-cart' ); ?></i></b>

            		</p>

                <?php Wcap_Add_Cart_Popup_Modal::wcap_add_to_cart_popup_settings(); ?>

            </form>

            <?php

        }



        static function wcap_fb_settings() {
            
            ?>

                 <form method="post" action="options.php">

                    <?php 

                    //settings_errors();

                    settings_fields( 'woocommerce_fb_settings' );

                    do_settings_sections( 'woocommerce_ac_fb_page' );

                    submit_button(); 

                    ?>

                </form>

            <?php

        }



        static function wcap_pro_general_settings() {



        	$upgrade_pro_msg = '<br><b><i>Upgrade to <a href="https://www.tychesoftwares.com/store/premium-plugins/woocommerce-abandoned-cart-pro/" target="_blank">Abandoned Cart Pro for WooCommerce</a> to enable the setting.</i></b>';

            

        	add_settings_field(

                'ac_enable_cart_emails',

                __( 'Enable abandoned cart emails', 'woocommerce-abandoned-cart' ),

                array( 'WCAP_Pro_Settings_Callbacks', 'wcap_enable_cart_emails_callback' ),

                'woocommerce_ac_page',

                'ac_lite_general_settings_section',

                array( __( "Yes, enable the abandoned cart emails.$upgrade_pro_msg", 'woocommerce-abandoned-cart' ) )

            );

            add_settings_field(

                'ac_cart_abandoned_time_guest',

                __( 'Cart abandoned cut-off time for guest users', 'woocommerce-abandoned-cart' ),

                array( 'WCAP_Pro_Settings_Callbacks', 'wcap_cart_abandoned_time_guest_callback' ),

                'woocommerce_ac_page',

                'ac_lite_general_settings_section',

                array( __( "For guest users & visitors consider cart abandoned after X minutes of item being added to cart & order not placed.$upgrade_pro_msg", 'woocommerce-abandoned-cart' ) )

            );



            add_settings_field(

                'ac_disable_guest_cart_email',

                __( 'Do not track carts of guest users', 'woocommerce-abandoned-cart' ),

                array( 'WCAP_Pro_Settings_Callbacks', 'wcap_disable_guest_cart_email_callback' ),

                'woocommerce_ac_page',

                'ac_lite_general_settings_section',

                array( __( "Abandoned carts of guest users will not be tracked.$upgrade_pro_msg", 'woocommerce-abandoned-cart' ) )

            );



            add_settings_field(

                'ac_disable_logged_in_cart_email',

                __( 'Do not track carts of logged-in users', 'woocommerce-abandoned-cart' ),

                array( 'WCAP_Pro_Settings_Callbacks', 'wcap_disable_logged_in_cart_email_callback' ),

                'woocommerce_ac_page',

                'ac_lite_general_settings_section',

                array( __( "Abandoned carts of logged-in users will not be tracked.$upgrade_pro_msg", 'woocommerce-abandoned-cart' ) )

            );



            add_settings_field(

                'ac_capture_email_address_from_url',

                __( 'Capture Email address from URL', 'woocommerce-abandoned-cart' ),

                array( 'WCAP_Pro_Settings_Callbacks', 'wcap_capture_email_address_from_url' ),

                'woocommerce_ac_page',

                'ac_lite_general_settings_section',

                array( __( "If your site URL contain the same key, then it will capture it as an email address of customer.$upgrade_pro_msg", 'woocommerce-abandoned-cart' ) )

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

               'ac_capture_email_address_from_url'

            );

            

            add_settings_field(

                'wcap_product_image_size',

                __( 'Product Image( H x W )', 'woocommerce-abandoned-cart'  ),

                array( 'WCAP_Pro_Settings_Callbacks', 'wcap_product_image_size_callback' ),

                'woocommerce_ac_email_page',

                'ac_email_settings_section',

                array( "This setting affects the dimension of the product image in the abandoned cart reminder email.$upgrade_pro_msg", 'woocommerce-abandoned-cart' )

            );

            

            register_setting(

            	'ac_email_settings_section',

            	'wcap_product_image_size'

            );



            add_settings_section(

                'ac_cron_job_settings_section',           // ID used to identify this section and with which to register options

                __( 'Setting for sending Emails & SMS using WP Cron', 'woocommerce-abandoned-cart' ),      // Title to be displayed on the administration page

                array( 'WCAP_Pro_Settings_Callbacks', 'wcap_cron_job_callback' ),// Callback used to render the description of the section

                'woocommerce_ac_page'     // Page on which to add this section of options

            );



            add_settings_field(

                'wcap_use_auto_cron',

                __( 'Send  Abandoned cart emails automatically using WP Cron', 'woocommerce-abandoned-cart'  ),

                array( 'WCAP_Pro_Settings_Callbacks', 'wcap_use_auto_cron_callback' ),

                'woocommerce_ac_page',

                'ac_cron_job_settings_section',

                array( "Enabling this setting will send the abandoned cart reminder emails to the customer after the set time. If disabled, abandoned cart reminder emails will not be sent using WP Cron. You will need to set cron job manually from cPanel. If you are unsure how to set the cron job, please <a href= mailto:support@tychesoftwares.com>contact us</a> for it.$upgrade_pro_msg", 'woocommerce-abandoned-cart' )

            );



            add_settings_field(

                'wcap_cron_time_duration',

                __( 'Run Automated WP Cron after X minutes', 'woocommerce-abandoned-cart'  ),

                array( 'WCAP_Pro_Settings_Callbacks', 'wcap_cron_time_duration_callback' ),

                'woocommerce_ac_page',

                'ac_cron_job_settings_section',

                array( "The duration in minutes after which a WP Cron job will run automatically for sending the abandoned cart reminder emails & SMS to the customers.$upgrade_pro_msg", 'woocommerce-abandoned-cart' )

            );



            add_settings_section(

                'ac_restrict_settings_section',           // ID used to identify this section and with which to register options

                __( 'Rules to exclude capturing abandoned carts', 'woocommerce-abandoned-cart' ),      // Title to be displayed on the administration page

                array( 'WCAP_Pro_Settings_Callbacks', 'wcap_custom_restrict_callback' ),// Callback used to render the description of the section

                'woocommerce_ac_page'     // Page on which to add this section of options

            );



            add_settings_field(

                'wcap_restrict_ip_address',

                __( 'Do not capture abandoned carts for these IP addresses', 'woocommerce-abandoned-cart'  ),

                array( 'WCAP_Pro_Settings_Callbacks', 'wcap_restrict_ip_address_callback' ),

                'woocommerce_ac_page',

                'ac_restrict_settings_section',

                array( "The carts abandoned from these IP addresses will not be tracked by the plugin. Accepts wildcards, e.g <code>192.168.*</code> will block all IP addresses which starts from \"192.168\". <i>Separate IP addresses with commas.</i>$upgrade_pro_msg", 'woocommerce-abandoned-cart' )

            );



            add_settings_field(

                'wcap_restrict_email_address',

                __( 'Do not capture abandoned carts for these email addresses', 'woocommerce-abandoned-cart'  ),

                array( 'WCAP_Pro_Settings_Callbacks', 'wcap_restrict_email_address_callback' ),

                'woocommerce_ac_page',

                'ac_restrict_settings_section',

                array( "The carts abandoned using these email addresses will not be tracked by the plugin. <i>Separate email addresses with commas.</i>$upgrade_pro_msg", 'woocommerce-abandoned-cart' )

            );



            add_settings_field(

                'wcap_restrict_domain_address',

                __( 'Do not capture abandoned carts for email addresses from these domains', 'woocommerce-abandoned-cart'  ),

                array( 'WCAP_Pro_Settings_Callbacks', 'wcap_restrict_domain_address_callback' ),

                'woocommerce_ac_page',

                'ac_restrict_settings_section',

                array( "The carts abandoned from email addresses with these domains will not be tracked by the plugin. <i>Separate email address domains with commas.</i>$upgrade_pro_msg", 'woocommerce-abandoned-cart' )

            );





        }

        static function wcap_sms_settings() {
            ?>

            <form method="post" action="options.php">
            
                <?php 

                settings_fields     ( 'woocommerce_sms_settings' );

                do_settings_sections( 'woocommerce_ac_sms_page' );

                submit_button(); 

                ?>

            </form>

            <div id="test_fields">

                <h2><?php _e( 'Send Test SMS', 'woocommerce-abandoned-cart' ); ?></h2>

                <div id="status_msg" style="background: white;border-left: #6389DA 4px solid;padding: 10px;display: none;width: 90%;"></div>

                <table class="form-table">

                    <tr>

                        <th><?php _e( 'Recipient', 'woocommerce-abandoned-cart' ); ?></th>

                        <td>

                            <input id="test_number" name="test_number" type=text readonly />

                            <i><?php _e( 'Must be a valid phone number in E.164 format.', 'woocommerce-abandoned-cart' );?></i>

                        </td>

                    </tr>

                    <tr>

                        <th><?php _e( 'Message', 'woocommerce-abandoned-cart' );?></th>

                        <td><textarea id="test_msg" rows="4" cols="70" readonly ><?php _e( 'Hello World!', 'woocommerce-abandoned-cart' );?></textarea></td>

                    </tr>

                    <tr>

                        <td colspan="2"><input type="button" id="wcap_test_sms" class="button-primary" value="<?php _e( 'Send', 'wocommerce-ac' );?>" /></td>

                    </tr>

                </table>

            </div>

            <?php 

        }

        

        function wcal_pro_settings() {



            $upgrade_pro_msg = '<br><b><i>Upgrade to <a href="https://www.tychesoftwares.com/store/premium-plugins/woocommerce-abandoned-cart-pro/" target="_blank">Abandoned Cart Pro for WooCommerce</a> to enable the setting.</i></b>';

            /**

             * New Settings for SMS Notifications

             */

            add_settings_section(

                'wcap_sms_settings_section',        // ID used to identify this section and with which to register options

                __( 'Twilio', 'woocommerce-abandoned-cart' ),       // Title to be displayed on the administration page

                array( 'WCAP_Pro_Settings_Callbacks', 'wcap_sms_settings_section_callback' ),     // Callback used to render the description of the section

                'woocommerce_ac_sms_page'               // Page on which to add this section of options

            );

            

            add_settings_field(

                'wcap_enable_sms_reminders',

                __( 'Enable SMS', 'woocommerce-abandoned-cart'  ),

                array( 'WCAP_Pro_Settings_Callbacks', 'wcap_enable_sms_reminders_callback' ),

                'woocommerce_ac_sms_page',

                'wcap_sms_settings_section',

                array( "<i>Enable the ability to send reminder SMS for abandoned carts.</i>$upgrade_pro_msg", 'woocommerce-abandoned-cart' )

            );

            

            add_settings_field(

                'wcap_sms_from_phone',

                __( 'From', 'woocommerce-abandoned-cart'  ),

                array( 'WCAP_Pro_Settings_Callbacks', 'wcap_sms_from_phone_callback' ),

                'woocommerce_ac_sms_page',

                'wcap_sms_settings_section',

                array( "<i>Must be a Twilio phone number (in E.164 format) or alphanumeric sender ID.</i>$upgrade_pro_msg", 'woocommerce-abandoned-cart' )

            );

            

            add_settings_field(

                'wcap_sms_account_sid',

                __( 'Account SID', 'woocommerce-abandoned-cart'  ),

                array( 'WCAP_Pro_Settings_Callbacks', 'wcap_sms_account_sid_callback' ),

                'woocommerce_ac_sms_page',

                'wcap_sms_settings_section',

                array( "$upgrade_pro_msg" )

            );

            

            add_settings_field(

                'wcap_sms_auth_token',

                __( 'Auth Token', 'woocommerce-abandoned-cart'  ),

                array( 'WCAP_Pro_Settings_Callbacks', 'wcap_sms_auth_token_callback' ),

                'woocommerce_ac_sms_page',

                'wcap_sms_settings_section',

                array( "$upgrade_pro_msg" )

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

                __( 'Enable Facebook Messenger Reminders', 'woocommerce-abandoned-cart'  ),

                array( 'WCAP_Pro_Settings_Callbacks', 'wcap_fb_checkbox_callback' ),

                'woocommerce_ac_fb_page',

                'wcap_fb_settings_section',

                array( "<i>This option will display a checkbox after the Add to cart button for user consent to connect with Facebook.</i>$upgrade_pro_msg", 'woocommerce-abandoned-cart', 'wcap_enable_fb_reminders' )

            );



            add_settings_field(

                'wcap_enable_fb_reminders_popup',

                __( 'Facebook Messenger on Add to Cart Pop-up modal', 'woocommerce-abandoned-cart'  ),

                array( 'WCAP_Pro_Settings_Callbacks', 'wcap_fb_checkbox_callback' ),

                'woocommerce_ac_fb_page',

                'wcap_fb_settings_section',

                array( "<i>This option will display a checkbox on the pop-up modal to connect with Facebook.</i>$upgrade_pro_msg", 'woocommerce-abandoned-cart', 'wcap_enable_fb_reminders_popup' )

            );



            add_settings_field(

                'wcap_fb_user_icon',

                __( 'Icon size of user', 'woocommerce-abandoned-cart'  ),

                array( 'WCAP_Pro_Settings_Callbacks', 'wcap_fb_dropdown_callback' ),

                'woocommerce_ac_fb_page',

                'wcap_fb_settings_section',

                array( 

                    "<i>Select the size of user icon which shall be displayed below the checkbox in case the user is logged in.</i>$upgrade_pro_msg", 

                    'woocommerce-abandoned-cart', 

                    'wcap_fb_user_icon',

                    array( 

                        'small' => __( 'Small', 'woocommerce-abandoned-cart' ),

                        'medium' => __( 'Medium', 'woocommerce-abandoned-cart' ),

                        'large' => __( 'Large', 'woocommerce-abandoned-cart' ),

                        'standard' => __( 'Standard', 'woocommerce-abandoned-cart' ),

                        'xlarge' => __( 'Extra Large', 'woocommerce-abandoned-cart' )

                    ) 

                )

            );



            add_settings_field(

                'wcap_fb_consent_text',

                __( 'Consent text', 'woocommerce-abandoned-cart'  ),

                array( 'WCAP_Pro_Settings_Callbacks', 'wcap_fb_text_callback' ),

                'woocommerce_ac_fb_page',

                'wcap_fb_settings_section',

                array( "<i>Text that will appear above the consent checkbox. HTML tags are also allowed.</i>$upgrade_pro_msg", 'woocommerce-abandoned-cart', 'wcap_fb_consent_text' )

            );



            add_settings_field(

                'wcap_fb_page_id',

                __( 'Facebook Page ID', 'woocommerce-abandoned-cart'  ),

                array( 'WCAP_Pro_Settings_Callbacks', 'wcap_fb_text_callback' ),

                'woocommerce_ac_fb_page',

                'wcap_fb_settings_section',

                array( "<i>Facebook Page ID in numberic format. You can find your page ID from <a href='https://www.tychesoftwares.com/docs/docs/abandoned-cart-pro-for-woocommerce/send-abandoned-cart-reminder-notifications-using-facebook-messenger#fbpageid' target='_blank'>here</a></i>$upgrade_pro_msg", 'woocommerce-abandoned-cart', 'wcap_fb_page_id' )

            );



            add_settings_field(

                'wcap_fb_app_id',

                __( 'Messenger App ID', 'woocommerce-abandoned-cart'  ),

                array( 'WCAP_Pro_Settings_Callbacks', 'wcap_fb_text_callback' ),

                'woocommerce_ac_fb_page',

                'wcap_fb_settings_section',

                array( "<i>Enter your Messenger App ID</i>$upgrade_pro_msg", 'woocommerce-abandoned-cart', 'wcap_fb_app_id' )

            );



            add_settings_field(

                'wcap_fb_page_token',

                __( 'Facebook Page Token', 'woocommerce-abandoned-cart'  ),

                array( 'WCAP_Pro_Settings_Callbacks', 'wcap_fb_text_callback' ),

                'woocommerce_ac_fb_page',

                'wcap_fb_settings_section',

                array( "<i>Enter your Facebook Page Token</i>$upgrade_pro_msg", 'woocommerce-abandoned-cart', 'wcap_fb_page_token' )

            );



            add_settings_field(

                'wcap_fb_verify_token',

                __( 'Verify Token', 'woocommerce-abandoned-cart'  ),

                array( 'WCAP_Pro_Settings_Callbacks', 'wcap_fb_text_callback' ),

                'woocommerce_ac_fb_page',

                'wcap_fb_settings_section',

                array( "<i>Enter your Verify Token</i>$upgrade_pro_msg", 'woocommerce-abandoned-cart', 'wcap_fb_verify_token' )

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

  

  		public static function wcap_add_to_cart_popup_settings() {

    		$wcap_atc_enabled        = get_option( 'wcap_atc_enable_modal' );

    		$wcap_disabled_field     = '';

    		if ( 'off' == $wcap_atc_enabled ) {

    			$wcap_disabled_field = 'disabled="disabled"';

    		} 

    		?>

			<div id = "wcap_popup_main_div" class = "wcap_popup_main_div ">
                
    			<table id = "wcap_popup_main_table" class = "wcap_popup_main_table test_borders">

    				<tr id = "wcap_popup_main_table_tr" class = "wcap_popup_main_table_tr test_borders">

    					<td id = "wcap_popup_main_table_td_settings" class = "wcap_popup_main_table_td_settings test_borders">    						

    						<?php Wcap_Add_Cart_Popup_Modal::wcap_enable_modal_section( $wcap_disabled_field ); ?>

    						<?php self::wcap_custom_pages_section( $wcap_disabled_field ); ?>

    						<div class = "wcap_atc_all_fields_container" >

	    						<?php Wcap_Add_Cart_Popup_Modal::wcap_add_heading_section( $wcap_disabled_field ); ?>

	    						<?php Wcap_Add_Cart_Popup_Modal::wcap_add_text_section( $wcap_disabled_field ); ?>

	    						<?php Wcap_Add_Cart_Popup_Modal::wcap_email_placeholder_section( $wcap_disabled_field ); ?>

	    						<?php Wcap_Add_Cart_Popup_Modal::wcap_button_section( $wcap_disabled_field ); ?>

	    						<?php Wcap_Add_Cart_Popup_Modal::wcap_mandatory_modal_section( $wcap_disabled_field ); ?>

	    						<?php Wcap_Add_Cart_Popup_Modal::wcap_non_mandatory_modal_section_field( $wcap_disabled_field ); ?>

    						</div>

    					</td>

    					<td id = "wcap_popup_main_table_td_preview" class = "wcap_popup_main_table_td_preview test_borders">

    						<div class = "wcap_atc_all_fields_container" >

    							<?php Wcap_Add_Cart_Popup_Modal::wcap_add_to_cart_popup_modal_preview( $wcap_disabled_field ); ?>

    						</div>

    					</td>

					</tr>

    				<tr>

    					<td>

    						<div class = "wcap_atc_all_fields_container" >

    							<p class = "submit">

    								<input type = "submit" name = "submit" id = "submit" class = "button button-primary" value = "Save Changes" <?php echo $wcap_disabled_field; ?> >

    								<input type = "submit" name = "submit" id = "submit" class = "wcap_reset_button button button-primary" value = "Reset to default configuration" <?php echo $wcap_disabled_field; ?> >

    							</p>

    						</div>

    					</td>

					</tr>

    			</table>

			</div>

    		<?php

    	}

      

    } // end of class

    $WCAP_Pro_Settings = new WCAP_Pro_Settings();

} // end if

?>