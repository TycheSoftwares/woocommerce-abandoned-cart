<?php
/**
 * It will fetch the Add to cart data, generate and populate data in the modal.
 *
 * @author  Tyche Softwares
 * @package Abandoned-Cart-Pro-for-WooCommerce/Admin/Settings
 * @since 6.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'Wcap_Add_Cart_Popup_Modal' ) ) {

	/**
	 * It will fetch the Add to cart data, generate and populate data in the modal.
	 *
	 * @since 6.0
	 */
	class Wcap_Add_Cart_Popup_Modal {
		/**
		 * This function will add the add to cart popup medal's settings.
		 *
		 * @since 6.0
		 */
		public static function wcap_add_to_cart_popup_settings() {

			$wcap_atc_enabled    = get_option( 'wcap_atc_enable_modal', '' );
			$wcap_disabled_field = 'off' === $wcap_atc_enabled ? 'disabled="disabled"' : '';
			$frontend_settings   = (object) array();
			$coupon_settings     = (object) array();
			?>

			<div id = "wcap_popup_main_div" class = "wcap_popup_main_div ">
				<table id = "wcap_popup_main_table" class = "wcap_popup_main_table test_borders">
					<tr id = "wcap_popup_main_table_tr" class = "wcap_popup_main_table_tr test_borders">
						<td id = "wcap_popup_main_table_td_settings" class = "wcap_popup_main_table_td_settings test_borders">
							<?php self::wcap_enable_modal_section( $frontend_settings ); ?>
							<hr>
						</td>
					</tr>
					<tr id="wcap_popup_main_table_tr" class="wcap_popup_main_table_tr test_borders">
						<td id="wcap_popup_main_table_td_settings" class="wcap_popup_main_table_td_settings test_borders">
							<?php
							wc_get_template(
								'html-atc-rules-engine.php',
								array(
									'rules' => array(),
									'match' => '',
								),
								'woocommerce-abandoned-cart/',
								WCAL_PLUGIN_PATH . '/includes/templates/atc-rules/'
							);
							?>
							<hr>
						</td>
					</tr>
					<tr id="wcap_popup_main_table_tr" class="wcap_popup_main_table_tr test_borders">
						<td id="wcap_popup_main_table_td_settings" class="wcap_popup_main_table_td_settings test_borders">
							<div class = "wcap_atc_all_fields_container" >
								<?php self::wcap_add_heading_section( $frontend_settings ); ?>
								<?php self::wcap_add_text_section( $frontend_settings ); ?>
								<?php self::wcap_email_placeholder_section( $frontend_settings ); ?>
								<?php self::wcap_button_section( $frontend_settings ); ?>
								<?php self::wcap_mandatory_modal_section( $frontend_settings ); ?>
								<?php self::wcap_non_mandatory_modal_section_field( $frontend_settings ); ?>
								<?php self::wcap_capture_phone( $frontend_settings ); ?>
								<?php self::wcap_phone_placeholder_section( $frontend_settings ); ?>
								<hr>
								<?php self::wcap_coupon_section( $coupon_settings ); ?>
							</div>
						</td>
						<td id = "wcap_popup_main_table_td_preview" class = "wcap_popup_main_table_td_preview test_borders">
							<div class = "wcap_atc_all_fields_container" >
								<?php self::wcap_add_to_cart_popup_modal_preview( $wcap_disabled_field ); ?>
							</div>
						</td>
					</tr>
					<tr>
						<td>
							<div class = "wcap_atc_all_fields_container" >
								<p class = "submit">
									<input type="submit" name="submit" id="submit" disabled class="button button-primary" value="<?php echo esc_html_e( 'Save Changes', 'woocommerce-abandoned-cart' ); ?>" >
									<input type="submit" name="submit" id="submit" disabled class="wcap_reset_button button button-primary" value="<?php echo esc_html_e( 'Reset to default configuration', 'woocommerce-abandoned-cart' ); ?>" >
								</p>
							</div>
						</td>
					</tr>
				</table>
			</div>
			<?php
		}

		/**
		 * It will add the "Enable Add to cart popup modal" setting on the add to cart modal settings page.
		 *
		 * @param string $wcap_disabled_field It will indicate if field need to be disabled or not.
		 * @since 6.0
		 */
		public static function wcap_enable_modal_section( $wcap_disabled_field ) {
			?>
				<table class = "wcap_enable_atc wcap_atc_between_fields_space wcap_atc_content" id = "wcap_enable_atc" >
					<th id = "wcap_button_section_table_heading" class = "wcap_button_section_table_heading"> <?php echo esc_html_e( 'Enable Add to cart popup modal', 'woocommerce-abandoned-cart' ); ?> </th>
					<tr>
						<td>
							<button type = "button" class = "wcap-enable-atc-modal wcap-toggle-atc-modal-enable-status" wcap-atc-switch-modal-enable = 'off' ?> disabled readonly>
							</button>
						</td>
					</tr>
				</table>
			<?php
		}

		/**
		 * It will add the setting for Heading section on the add to cart modal settings page.
		 *
		 * @param object $frontend_settings - ATC settings.
		 * @since 6.0
		 */
		public static function wcap_add_heading_section( $frontend_settings ) {
			$heading = isset( $frontend_settings->wcap_heading_section_text_email ) ? $frontend_settings->wcap_heading_section_text_email : __( 'Please enter your email.', 'woocommerce-abandoned-cart' );
			?>
			<div id="wcap_heading_section_div" class="wcap_heading_section_div wcap_atc_between_fields_space">
				<table id="wcap_heading_section_table" class="wcap_heading_section_table wcap_atc_content">
					<th id="wcap_heading_section_table_heading" class="wcap_heading_section_table_heading"><?php esc_html_e( 'Modal Heading', 'woocommerce-abandoned-cart' ); ?></th>
					<tr id="wcap_heading_section_tr" class="wcap_heading_section_tr" >
						<td id="wcap_heading_section_text_field" class="wcap_heading_section_text_field test_borders">
							<input type="text" id="wcap_heading_section_text_email" v-model="wcap_heading_section_text_email" name="wcap_heading_section_text_email" class = "wcap_heading_section_text_email"
							readonly value="<?php echo esc_html( $heading ); ?>" >
						</td>            				
						<td id="wcap_heading_section_text_field_color" class="wcap_heading_section_text_field_color test_borders">
							<span class = "colorpickpreview" style = "background: #737d97"></span>
							<input type="text" class="wcap_popup_heading_color_picker colorpick" name="wcap_popup_heading_color_picker" value="#737f97" v-model="wcap_popup_heading_color" v-on:input="wcap_atc_popup_heading.color = $event.target.value" readonly >
						</td>
					</tr>
				</table>
			</div>
			<?php
		}

		/**
		 * It will add the setting for Text displayed below heading section on the add to cart modal settings page.
		 *
		 * @param object $frontend_settings - ATC settings.
		 * @since 6.0
		 */
		public static function wcap_add_text_section( $frontend_settings ) {
			?>
			<div id="wcap_text_section_div" class="wcap_text_section_div wcap_atc_between_fields_space">
				<table id="wcap_text_section_table" class="wcap_text_section_table wcap_atc_content">
					<th id="wcap_text_section_table_heading" class="wcap_text_section_table_heading"><?php esc_html_e( 'Modal Text', 'woocommerce-abandoned-cart' ); ?></th>
					<tr id="wcap_text_section_tr" class="wcap_text_section_tr" >
						<td id="wcap_text_section_text_field" class="wcap_text_section_text_field test_borders">
							<input type="text" id="wcap_text_section_text" v-model="wcap_text_section_text_field" class="wcap_text_section_input_text" name="wcap_text_section_text" readonly>
						</td>                    		
						<td id="wcap_text_section_field_color" class="wcap_text_section_field_color test_borders">
							<span class="colorpickpreview" style="background: #bbc9d2"></span>
							<input type="text" class="wcap_popup_text_color_picker colorpick" name="wcap_popup_text_color_picker" value="#bbc9d2" v-model="wcap_popup_text_color" v-on:input="wcap_atc_popup_text.color = $event.target.value" readonly>
						</td>
					</tr>
				</table>
			</div>
			<?php
		}

		/**
		 * It will add the setting for email placeholder on the add to cart modal settings page.
		 *
		 * @param object $frontend_settings - ATC settings.
		 * @since 6.0
		 */
		public static function wcap_email_placeholder_section( $frontend_settings ) {
			?>
			<div id="wcap_email_placeholder_section_div" class="wcap_email_placeholder_section_div wcap_atc_between_fields_space">
				<table id="wcap_email_placeholder_section_table" class="wcap_email_placeholder_section_table wcap_atc_content">
				<th id="wcap_email_placeholder_section_table_heading" class="wcap_email_placeholder_section_table_heading"><?php esc_html_e( 'Email placeholder', 'woocommerce-abandoned-cart' ); ?></th>
					<tr id="wcap_email_placeholder_section_tr" class="wcap_email_placeholder_section_tr" >
						<td id="wcap_email_placeholder_section_text_field" class="wcap_email_placeholder_section_text_field test_borders">
							<input type="text" id="wcap_email_placeholder_section_input_text" v-model="wcap_email_placeholder_section_input_text" class="wcap_email_placeholder_section_input_text" name="wcap_email_placeholder_section_input_text" readonly>
						</td>
					</tr>
				</table>
			</div>
			<?php
		}

		/**
		 * It will add the setting for Add to cart button on the add to cart modal settings page.
		 *
		 * @param object $frontend_settings - ATC settings.
		 * @since 6.0
		 */
		public static function wcap_button_section( $frontend_settings ) {
			?>
			<div id="wcap_button_section_div" class="wcap_button_section_div wcap_atc_between_fields_space">
				<table id="wcap_button_section_table" class="wcap_button_section_table wcap_atc_content">
				<th id="wcap_button_section_table_heading" class="wcap_button_section_table_heading"><?php esc_html_e( 'Add to cart button text', 'woocommerce-abandoned-cart' ); ?></th>
					<tr>
						<td id="wcap_button_section_text_field" class="wcap_button_section_text_field test_borders">
							<input type="text" id="wcap_button_section_input_text" v-model="wcap_button_section_input_text" class="wcap_button_section_input_text" name="wcap_button_section_input_text" readonly>
						</td>
					</tr>
					<tr id="wcap_button_color_section_tr" class="wcap_button_color_section_tr">
						<td id="wcap_button_color_section_text_field" class="wcap_button_color_section_text_field test_borders">
							<span class="colorpickpreview" style="background: #0085ba"></span>
							<input type="text" id="wcap_button_color_picker" value="#0085ba" v-model="wcap_button_bg_color" v-on:input="wcap_atc_button.backgroundColor = $event.target.value" class="wcap_button_color_picker colorpick" name="wcap_button_color_picker" readonly>
						</td>
						<td id="wcap_button_text_color_section_text_field" class="wcap_button_text_color_section_text_field test_borders">
							<span class="colorpickpreview" style="background: #ffffff"></span>
							<input type="text" id="wcap_button_text_color_picker" value= "#ffffff" v-model="wcap_button_text_color" v-on:input="wcap_atc_button.color = $event.target.value" class="wcap_button_text_color_picker colorpick" name="wcap_button_text_color_picker" readonly>
						</td>
					</tr>
				</table>
			</div>
			<?php
		}

		/**
		 * It will add the setting for Email address mandatory field on the add to cart modal settings page.
		 *
		 * @param object $frontend_settings - ATC settings.
		 * @since 6.0
		 */
		public static function wcap_mandatory_modal_section( $frontend_settings ) {
			?>
			<table class="wcap_atc_between_fields_space wcap_atc_content">
				<th id="wcap_button_section_table_heading" class="wcap_button_section_table_heading"><?php esc_html_e( 'Email address is mandatory?', 'woocommerce-abandoned-cart' ); ?></th>
				<tr>
					<td>
						<button type="button" class="wcap-switch-atc-modal-mandatory wcap-toggle-atc-modal-mandatory" wcap-atc-switch-modal-mandatory="off" 
						onClick="wcap_button_choice( this, 'wcap-atc-switch-modal-mandatory' )" readonly>
						</button>
						<input type="hidden" name="wcap_switch_atc_modal_mandatory" id="wcap_switch_atc_modal_mandatory" readonly/>
					</td>
				</tr>
			</table>
			<?php
		}

		/**
		 * It will add the setting for Email address non mandatory field on the add to cart modal settings page.
		 *
		 * @param object $frontend_settings - ATC settings.
		 * @since 6.0
		 */
		public static function wcap_non_mandatory_modal_section_field( $frontend_settings ) {
			?>
			<div id="wcap_non_mandatory_modal_section_fields_div" class="wcap_non_mandatory_modal_section_fields_div wcap_atc_between_fields_space">
				<table id="wcap_non_mandatory_modal_section_fields_div_table" class="wcap_non_mandatory_modal_section_fields_div_table wcap_atc_content">
					<th id="wcap_non_mandatory_modal_section_fields_table_heading" 
					class="wcap_non_mandatory_modal_section_fields_table_heading"><?php esc_html_e( 'Not mandatory text', 'woocommerce-abandoned-cart' ); ?></th>
					<tr id="wcap_non_mandatory_modal_section_fields_tr" class="wcap_non_mandatory_modal_section_fields_tr" >
						<td id="wcap_non_mandatory_modal_section_fields_text_field" class="wcap_non_mandatory_modal_section_fields_text_field test_borders">
							<input type="text" id="wcap_non_mandatory_modal_section_fields_input_text" v-model="wcap_non_mandatory_modal_input_text" class="wcap_non_mandatory_modal_section_fields_input_text" name="wcap_non_mandatory_modal_section_fields_input_text" readonly>
						</td>
					</tr>
				</table>
			</div>
			<?php
		}

		/**
		 * It will add the setting for Phone capture on the add to cart modal settings page.
		 *
		 * @param object $frontend_settings - ATC settings.
		 * @since 8.12.0
		 */
		public static function wcap_capture_phone( $frontend_settings ) {
			?>
			<table class="wcap_atc_between_fields_space wcap_atc_content">
				<th id="wcap_button_section_table_heading" class="wcap_button_section_table_heading"><?php esc_html_e( 'Capture Phone', 'woocommerce-abandoned-cart' ); ?></th>
				<tr>
					<td>
						<button type="button" class="wcap-switch-atc-capture-phone wcap-toggle-atc-capture-phone" wcap-atc-capture-phone="off" 
						onClick="wcap_button_choice( this, 'wcap-atc-capture-phone' )" readonly>
						</button>
						<input type="hidden" name="wcap_switch_atc_capture_phone" id="wcap_switch_atc_capture_phone" value="off" readonly/>
					</td>
				</tr>
			</table>
			<?php
		}

		/**
		 * It will add the setting for Phone field placeholder on the add to cart modal settings page.
		 *
		 * @param object $frontend_settings - ATC settings.
		 * @since 8.12.0
		 */
		public static function wcap_phone_placeholder_section( $frontend_settings ) {
			?>
			<div id="wcap_phone_placeholder_section_div" class="wcap_phone_placeholder_section_div wcap_atc_between_fields_space">
				<table id="wcap_phone_placeholder_section_table" class="wcap_phone_placeholder_section_table wcap_atc_content">
				<th id="wcap_phone_placeholder_section_table_heading" class="wcap_phone_placeholder_section_table_heading"><?php esc_html_e( 'Phone placeholder', 'woocommerce-abandoned-cart' ); ?></th>
					<tr id="wcap_phone_placeholder_section_tr" class="wcap_phone_placeholder_section_tr" >
						<td id="wcap_phone_placeholder_section_text_field" class="wcap_phone_placeholder_section_text_field test_borders">
							<input type="text" id="wcap_phone_placeholder_section_input_text" v-model="wcap_phone_placeholder_section_input_text" class="wcap_phone_placeholder_section_input_text" name="wcap_phone_placeholder_section_input_text" value="<?php esc_html_e( 'Please enter your phone number in E.164 format', 'woocommerce-abandoned-cart' ); ?>" readonly >
						</td>
					</tr>
				</table>
			</div>
			<?php
		}

		/**
		 * Auto Apply coupons for atc settings.
		 *
		 * @param object $coupon_settings - Coupon settings.
		 * @since 8.5.0
		 */
		public static function wcap_coupon_section( $coupon_settings ) {
			$auto_apply_coupon = isset( $coupon_settings->wcap_atc_auto_apply_coupon_enabled ) ? $coupon_settings->wcap_atc_auto_apply_coupon_enabled : 'off';
			$active_text       = __( $auto_apply_coupon, 'woocommerce-abandoned-cart' ); // phpcs:ignore

			$wcap_atc_coupon_type = isset( $coupon_settings->wcap_atc_coupon_type ) ? $coupon_settings->wcap_atc_coupon_type : '';
			$pre_selected         = 'pre-selected' === $wcap_atc_coupon_type || '' === $wcap_atc_coupon_type ? 'selected' : '';
			$unique               = 'unique' === $wcap_atc_coupon_type ? 'selected' : '';

			$coupon_code_id = isset( $coupon_settings->wcap_atc_popup_coupon ) ? $coupon_settings->wcap_atc_popup_coupon : 0;

			$wcap_atc_discount_type = isset( $coupon_settings->wcap_atc_discount_type ) ? $coupon_settings->wcap_atc_discount_type : '';
			$percent_discount       = 'percent' === $wcap_atc_discount_type || '' === $wcap_atc_discount_type ? 'selected' : '';
			$amount_discount        = 'amount' === $wcap_atc_discount_type ? 'selected' : '';

			$wcap_atc_discount_amount      = isset( $coupon_settings->wcap_atc_discount_amount ) ? $coupon_settings->wcap_atc_discount_amount : '';
			$wcap_atc_coupon_free_shipping = isset( $coupon_settings->wcap_atc_coupon_free_shipping ) ? $coupon_settings->wcap_atc_coupon_free_shipping : '';
			$free_shipping_enabled         = 'on' === $wcap_atc_coupon_free_shipping ? 'checked' : '';

			$coupon_validity       = isset( $coupon_settings->wcap_atc_popup_coupon_validity ) ? $coupon_settings->wcap_atc_popup_coupon_validity : '';
			$countdown_msg         = isset( $coupon_settings->wcap_countdown_timer_msg ) ? htmlspecialchars_decode( $coupon_settings->wcap_countdown_timer_msg ) : htmlspecialchars_decode( 'Coupon <coupon_code> expires in <hh:mm:ss>. Avail it now.' );
			$countdown_msg_expired = isset( $coupon_settings->wcap_countdown_msg_expired ) ? $coupon_settings->wcap_countdown_msg_expired : 'The offer is no longer valid.';
			$countdown_cart        = isset( $coupon_settings->wcap_countdown_cart ) ? $coupon_settings->wcap_countdown_cart : 'on';
			$active_cart           = __( $countdown_cart, 'woocommerce-abandoned-cart' ); // phpcs:ignore
			?>
			<div id='wcap_coupon_settings'>
				<table id='wcap_coupon_settings_div_table' class='wcap_coupon_settings_div_table wcap_atc_content'>
					<th id='wcap_auto_apply_coupons_heading' class='wcap_auto_apply_coupons_heading'><?php esc_html_e( 'Auto apply coupons on email address capture:', 'woocommerce-abandoned-cart' ); ?></th>
					<tr>
						<td>
							<button type="button" class="wcap-auto-apply-coupons-atc wcap-toggle-auto-apply-coupons-status" wcap-atc-switch-coupon-enable = "<?php echo esc_attr( $auto_apply_coupon ); ?>"
							onClick="wcap_button_choice( this, 'wcap-atc-switch-coupon-enable' )" readonly disabled >
							<?php echo esc_attr( $active_text ); ?></button>
							<input type="hidden" name="wcap_auto_apply_coupons_atc" id="wcap_auto_apply_coupons_atc" value="<?php echo esc_attr( $auto_apply_coupon ); ?>" readonly disabled/>
						</td>
					</tr>
					<th id='wcap_atc_coupon_type_label' class='wcap_atc_coupon_type_label'><?php esc_html_e( 'Type of Coupon to apply:', 'woocommerce-abandoned-cart' ); ?></th>
					<tr>
						<td>
							<select id='wcap_atc_coupon_type' name='wcap_atc_coupon_type' readonly disabled>
								<option value='pre-selected' <?php echo esc_html( $pre_selected ); ?>><?php esc_html_e( 'Existing Coupons', 'woocommerce-abandoned-cart' ); ?></option>
								<option value='unique' <?php echo esc_html( $unique ); ?>><?php esc_html_e( 'Generate Unique Coupon code', 'woocommerce-abandoned-cart' ); ?></option>
							</select>
						</td>
					</tr>
					<th id='wcap_auto_apply_coupon_id' class='wcap_auto_apply_coupon_id wcap_atc_pre_selected' style='display:none;'><?php esc_html_e( 'Coupon code to apply:', 'woocommerce-abandoned-cart' ); ?></th>
					<tr class='wcap_atc_pre_selected' style='display:none;'>
						<td>
							<div id="coupon_options" class="panel">
								<div class="options_group">
									<p class="form-field" style="padding-left:0px !important;">
									<?php
										$json_ids = array();

									if ( $coupon_code_id > 0 ) {
										$coupon                      = get_the_title( $coupon_code_id );
										$json_ids[ $coupon_code_id ] = $coupon;
									}
									if ( version_compare( WC()->version, '3.0.0', '>=' ) ) {
										?>
											<select id="coupon_ids" name="coupon_ids[]" class="wc-product-search" multiple="multiple" style="width: 37%;" data-placeholder="<?php esc_attr_e( 'Search for a Coupon&hellip;', 'woocommerce' ); ?>" data-action="wcap_json_find_coupons" >
										<?php
										if ( $coupon_code_id > 0 ) {
											$coupon = get_the_title( $coupon_code_id );
											echo '<option value="' . esc_attr( $coupon_code_id ) . '"' . selected( true, true, false ) . '>' . wp_kses_post( $coupon ) . '</option>';
										}
										?>
											</select>
										<?php
									} else {
										?>
										<input type="hidden" id="coupon_ids" name="coupon_ids[]" class="wc-product-search" style="width: 30%;" data-placeholder="<?php esc_attr_e( 'Search for a Coupon&hellip;', 'woocommerce' ); ?>" data-multiple="true" data-action="wcap_json_find_coupons"
										data-selected=" <?php echo esc_attr( wp_json_encode( $json_ids ) ); ?> " value="<?php echo esc_html( implode( ',', array_keys( $json_ids ) ) ); ?>" />
										<?php
									}
									?>

									</p>
								</div>
							</div>
						</td>
					</tr>
					<th id='wcap_atc_discount_type_label' class='wcap_atc_discount_type_label wcap_atc_unique'><?php esc_html_e( 'Discount Type:', 'woocommerce-abandoned-cart' ); ?></th>
					<tr class='wcap_atc_unique'>
						<td>
							<select id='wcap_atc_discount_type' name='wcap_atc_discount_type' readonly disabled>
								<option value='percent' <?php echo esc_html( $percent_discount ); ?>><?php esc_html_e( 'Percentage Discount', 'woocommerce-abandoned-cart' ); ?></option>
								<option value='amount' <?php echo esc_html( $amount_discount ); ?>><?php esc_html_e( 'Fixed Cart Amount', 'woocommerce-abandoned-cart' ); ?></option>
							</select>
						</td>
					</tr>
					<th id='wcap_atc_discount_amount_label' class='wcap_atc_discount_amount_label wcap_atc_unique'><?php esc_html_e( 'Discount Amount:', 'woocommerce-abandoned-cart' ); ?></th>
					<tr class='wcap_atc_unique'>
						<td>
							<input type='number' id='wcap_atc_discount_amount' name='wcap_atc_discount_amount' min='0' value='<?php echo esc_html( $wcap_atc_discount_amount ); ?>' readonly disabled />
						</td>
					</tr>
					<th id='wcap_atc_coupon_free_shipping_label' class='wcap_atc_coupon_free_shipping_label wcap_atc_unique'><?php esc_html_e( 'Allow Free Shipping?', 'woocommerce-abandoned-cart' ); ?></th>
					<tr class='wcap_atc_unique'>
						<td>
							<input type='checkbox' id='wcap_atc_coupon_free_shipping' name='wcap_atc_coupon_free_shipping' <?php echo esc_attr( $free_shipping_enabled ); ?> readonly disabled />
						</td>
					</tr>		
					<th id='wcap_atc_coupon_validity_label' class='wcap_atc_coupon_validity_label'><?php esc_html_e( 'Coupon validity (in minutes):', 'woocommerce-abandoned-cart' ); ?></th>
					<tr>
						<td>
							<input type='number' id='wcap_atc_coupon_validity' name='wcap_atc_coupon_validity' min='0' value='<?php echo esc_attr( $coupon_validity ); ?>' readonly disabled />
						</td>
					</tr>
					<th id='countdown_timer_cart_label' class='countdown_timer_cart_label'><?php esc_html_e( 'Display Urgency message on Cart page (If disabled it will display only on Checkout page)', 'woocommerce-abandoned-cart' ); ?></th>
					<tr>
						<td>
							<button type="button" class="wcap-countdown-timer-cart wcap-toggle-countdown-timer-cart" wcap-atc-countdown-timer-cart-enable = 'off' 
							onClick="wcap_button_choice( this, 'wcap-atc-countdown-timer-cart-enable' )" readonly disabled >
							<?php echo esc_attr( $active_text ); ?></button>
							<input type="hidden" name="wcap_countdown_timer_cart" id="wcap_countdown_timer_cart" value="<?php echo esc_attr( $countdown_cart ); ?>" />
						</td>
					</tr>
					<th id='wcap_countdown_msg_label' class='wcap_countdown_msg_label'><?php esc_html_e( 'Urgency message to boost your conversions', 'woocommerce-abandoned-cart' ); ?></th>
					<tr>
						<td>
							<input type='text' id='wcap_countdown_msg' name='wcap_countdown_msg' placeholder='<?php echo esc_attr( 'Coupon <coupon_code> expires in <hh:mm:ss>. Avail it now.' ); ?>' value='<?php echo esc_attr( $countdown_msg ); ?>' readonly disabled />
							<br>
							<i><?php echo esc_html_e( 'Merge tags available: <coupon_code>, <hh:mm:ss>', 'woocommerce-abandoned-cart' ); ?></i>
						</td>
					</tr>
					<th id='wcap_countdown_msg_label' class='wcap_countdown_msg_expired_label'><?php esc_html_e( 'Message to display after coupon validity is reached', 'woocommerce-abandoned-cart' ); ?></th>
					<tr>
						<td>
							<input type='text' id='wcap_countdown_msg_expired' name='wcap_countdown_msg_expired' placeholder='<?php echo esc_attr( 'The offer is no longer valid.' ); ?>' value='<?php echo esc_attr( $countdown_msg_expired ); ?>' readonly disabled />
							<br>
						</td>
					</tr>
					<th id='wcap_atc_coupon_note' class='wcap_atc_coupon_note'><i><?php esc_html_e( 'Note: For orders which use the coupon selected/generated by the ATC module will be marked as "ATC Coupon Used" in WooCommerce->Orders.', 'woocommerce-abandoned-cart' ); ?></i></th>
					<tr></tr>
				</table>
			</div>
			<?php
		}

		/**
		 * It will will show th preview of the Add To cart Popup modal with the changes made on any of the settings for it.
		 *
		 * @param string $wcap_disabled_field It will indicate if field need to be disabled or not.
		 * @since 6.0
		 */
		public static function wcap_add_to_cart_popup_modal_preview( $wcap_disabled_field ) {
			?>

			<div class = "wcap_container">
				<div class = "wcap_popup_wrapper">
					<div class = "wcap_popup_content">

						<div class = "wcap_popup_heading_container">
							<div class = "wcap_popup_icon_container" >
								<span class = "wcap_popup_icon"  >
									<span class = "wcap_popup_plus_sign" v-bind:style = "wcap_atc_button">
									</span>
								</span>
							</div>
							<div class = "wcap_popup_text_container">
								<h2 class = "wcap_popup_heading" v-bind:style = "wcap_atc_popup_heading" ><?php echo esc_html_e( 'Please enter your email address.', 'woocommerce-abandoned-cart' ); ?></h2>
								<div class = "wcap_popup_text" v-bind:style = "wcap_atc_popup_text" ><?php echo esc_html_e( 'To add this item to your cart, please enter your email address.', 'woocommerce-abandoned-cart' ); ?></div>
							</div>
						</div>

						<div class = "wcap_popup_form">
							<form action = "" name = "wcap_modal_form">
								<div class = "wcap_popup_input_field_container"  >
									<input class = "wcap_popup_input" type = "text" value = "" name = "email" placeholder ="<?php echo esc_html_e( 'Email address', 'woocommerce-abandoned-cart' ); ?>" readonly >
								</div>
								<div class="wcap_popup_input_field_container atc_phone_field" >
									<input id="wcap_atc_phone" class="wcap_popup_input" type="text" name="wcap_atc_phone" placeholder="<?php echo esc_html_e( 'Please enter your phone number in E.164 format', 'woocommerce-abandoned-cart' ); ?>"  readonly />
								</div>
								<button class = "wcap_popup_button" v-bind:style = "wcap_atc_button"><?php echo esc_html_e( 'Add to Cart', 'woocommerce-abandoned-cart' ); ?></button>
								<br>
								<br>
								<div id = "wcap_non_mandatory_text_wrapper" class = "wcap_non_mandatory_text_wrapper">
									<a class = "wcap_popup_non_mandatory_button" href = "" > <?php echo esc_html_e( 'No Thanks', 'woocommerce-abandoned-cart' ); ?></a>
								</div>
							</form>
						</div>
						<div class = "wcap_popup_close" ></div>
					</div>
				</div>
			</div>
			<?php
		}
	}
}
