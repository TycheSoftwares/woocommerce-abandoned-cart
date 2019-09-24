<?php

/**

 * It will fetch the Add to cart data, generate and populate data in the modal.

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

	 * @since 6.0
	 */

	class Wcap_Add_Cart_Popup_Modal {



		/**

		 * This function will add the add to cart popup medal's settings.

		 * @since 6.0
		 */

		public static function wcap_add_to_cart_popup_settings() {

			$wcap_atc_enabled = get_option( 'wcap_atc_enable_modal' );

			$wcap_disabled_field = '';

			if ( 'off' == $wcap_atc_enabled ) {

				$wcap_disabled_field = 'disabled="disabled"';

			}

			?>

			<div id = "wcap_popup_main_div" class = "wcap_popup_main_div ">

				<table id = "wcap_popup_main_table" class = "wcap_popup_main_table test_borders">

					<tr id = "wcap_popup_main_table_tr" class = "wcap_popup_main_table_tr test_borders">

						<td id = "wcap_popup_main_table_td_settings" class = "wcap_popup_main_table_td_settings test_borders">

							<?php self::wcap_enable_modal_section( $wcap_disabled_field ); ?>

							<?php self::wcap_custom_pages_section( $wcap_disabled_field ); ?>

							<div class = "wcap_atc_all_fields_container" >

								<?php self::wcap_add_heading_section( $wcap_disabled_field ); ?>

								<?php self::wcap_add_text_section( $wcap_disabled_field ); ?>

								<?php self::wcap_email_placeholder_section( $wcap_disabled_field ); ?>

								<?php self::wcap_button_section( $wcap_disabled_field ); ?>

								<?php self::wcap_mandatory_modal_section( $wcap_disabled_field ); ?>

								<?php self::wcap_non_mandatory_modal_section_field( $wcap_disabled_field ); ?>

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

									<input type = "submit" name = "submit" id = "submit" disabled class = "button button-primary" value = "Save Changes" <?php echo $wcap_disabled_field; ?> >

									<input type = "submit" name = "submit" id = "submit" disabled class = "wcap_reset_button button button-primary" value = "Reset to default configuration" <?php echo $wcap_disabled_field; ?> >

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

		 * @param string $wcap_disabled_field It will indicate if field need to be disabled or not.

		 * @since 6.0
		 */

		public static function wcap_enable_modal_section( $wcap_disabled_field ) {

			?>

				<table class = "wcap_enable_atc wcap_atc_between_fields_space" id = "wcap_enable_atc" >

					<th id = "wcap_button_section_table_heading" class = "wcap_button_section_table_heading"> <?php _e( 'Enable Add to cart popup modal', 'woocommerce-abandoned-cart' ); ?> </th>

					<tr>

						<td>

						   <?php

							$wcap_atc_enabled = get_option( 'wcap_atc_enable_modal' );

							$active_text = $wcap_atc_enabled;

							?>

						   <button type = "button" class = "wcap-enable-atc-modal wcap-toggle-atc-modal-enable-status" wcap-atc-switch-modal-enable = 'off' ?> readonly>

						   <?php echo $active_text; ?>

						   </button>

						</td>

					</tr>

				</table>

			<?php

		}



		/**

		 * Adds a multi select searchable dropdown from where

		 * the admin can select custom pages on which the

		 * Add to Cart Popup modal should be displayed.
		 *
		 * @param string $wcap_disabled_field It will indicate if field need to be disabled or not.

		 * @since 7.10.0
		 */

		public static function wcap_custom_pages_section( $wcap_disabled_field ) {

			 global $woocommerce;

			$post_title_array = array();

			?>

			<table class = "wcap_custom_pages wcap_atc_between_fields_space" id = "wcap_custom_pages" >

				<tr>

					<th id="wcap_button_section_table_heading" class="wcap_button_section_table_heading"> <?php _e( 'Custom pages to display the pop-up modal on', 'woocommerce-abandoned-cart' ); ?> </th>

				</tr>

				<tr>

					<td>

						<?php

						$custom_pages = get_option( 'wcap_custom_pages_list' );

						?>

						<?php if ( $woocommerce->version >= '3.0' ) { ?>

							<select style="width:80%" multiple="multiple" class="wcap_page_select wc-product-search" name="wcap_page_select[]" data-placeholder='<?php esc_attr__( 'Search for a Page&hellip;', 'woocommerce-abandoned-cart' ); ?>' data-action='wcap_json_find_pages' disabled>



								<?php

								if ( is_array( $custom_pages ) && count( $custom_pages ) > 0 ) {

									foreach ( $custom_pages as $page_ids ) {

										$post_id = $page_ids;

										$post_title = get_the_title( $post_id );

										printf( "<option value='%s' selected>%s</option>\n", $post_id, $post_title );

									}
								}

								?>

							</select>

							<?php
						} else {

							if ( is_array( $post_title_array ) && is_array( $custom_pages ) && count( $custom_pages ) > 0 ) {

								foreach ( $custom_pages as $page_ids ) {

									$post_id = $page_ids;

									$post_title = get_the_title( $post_id );

									$post_title_array[ $post_title ] = $post_title;

								}
							}

							?>



						<input type="hidden" style="width:80%" id = "wcap_page_select" class="wc-product-search" name="wcap_page_select[]" data-placeholder='<?php esc_attr_e( 'Search for a Page&hellip;', 'woocommerce-abandoned-cart' ); ?>' data-multiple="true" data-action='wcap_json_find_pages' data-selected=" <?php echo esc_attr( json_encode( $post_title_array ) ); ?>" value="<?php echo implode( ',', array_keys( $post_title_array ) ); ?>" readonly/>



						<?php } ?>



						<?php $toolTip = __( 'Please add any custom pages (not created by WooCommerce) where you wish to display the Add to cart Pop-up Modal.', 'woocommerce-abandoned-cart' ); ?>

						<?php echo wc_help_tip( $toolTip ); ?>

					</td>

				</tr>

				<tr>

					<td colspan="2" style="text-align: justify;">

						<?php _e( '<b>Note:</b> Please ensure that the Add to Cart button links on these pages are added with the correct classes and attributes to ensure the plugin can capture the cart data correctly. For further guidance, please check the documentation.', 'woocommerce-abandoned-cart' ); ?>

					</td>

				</tr>

			</table>

			<?php

		}

		/**

		 * It will Save the setting on the add to cart modal settings page.

		 * @since 6.0
		 */

		public static function wcap_add_to_cart_popup_save_settings() {

			if ( $_POST ['wcap_heading_section_text_email'] ) {

				update_option( 'wcap_heading_section_text_email', $_POST ['wcap_heading_section_text_email'] );

			}

			if ( $_POST ['wcap_popup_heading_color_picker'] ) {

				update_option( 'wcap_popup_heading_color_picker', $_POST ['wcap_popup_heading_color_picker'] );

			}

			if ( $_POST ['wcap_text_section_text'] ) {

				update_option( 'wcap_text_section_text', $_POST ['wcap_text_section_text'] );

			}

			if ( $_POST ['wcap_popup_text_color_picker'] ) {

				update_option( 'wcap_popup_text_color_picker', $_POST ['wcap_popup_text_color_picker'] );

			}

			if ( $_POST ['wcap_email_placeholder_section_input_text'] ) {

				update_option( 'wcap_email_placeholder_section_input_text', $_POST ['wcap_email_placeholder_section_input_text'] );

			}

			if ( $_POST ['wcap_button_section_input_text'] ) {

				update_option( 'wcap_button_section_input_text', $_POST ['wcap_button_section_input_text'] );

			}

			if ( $_POST ['wcap_button_color_picker'] ) {

				update_option( 'wcap_button_color_picker', $_POST ['wcap_button_color_picker'] );

			}

			if ( isset( $_POST ['wcap_button_text_color_picker'] ) ) {

				update_option( 'wcap_button_text_color_picker', $_POST ['wcap_button_text_color_picker'] );

			}

			if ( isset( $_POST ['wcap_non_mandatory_modal_section_fields_input_text'] ) ) {

				update_option( 'wcap_non_mandatory_text', $_POST ['wcap_non_mandatory_modal_section_fields_input_text'] );

			}

			$custom_pages = isset( $_POST['wcap_page_select'] ) ? $_POST['wcap_page_select'] : array();

			update_option( 'wcap_custom_pages_list', $custom_pages );

		}



		/**

		 * It will add the setting for Heading section on the add to cart modal settings page.

		 * @param string $wcap_disabled_field It will indicate if field need to be disabled or not.

		 * @since 6.0
		 */

		public static function wcap_add_heading_section( $wcap_disabled_field ) {

			?>

			<div id = "wcap_heading_section_div" class = "wcap_heading_section_div wcap_atc_between_fields_space">

				<table id = "wcap_heading_section_table" class = "wcap_heading_section_table">

					<th id = "wcap_heading_section_table_heading" class ="wcap_heading_section_table_heading"> Modal Heading </th>

					<tr id = "wcap_heading_section_tr" class = "wcap_heading_section_tr" >

						<td id = "wcap_heading_section_text_field" class = "wcap_heading_section_text_field test_borders">

							<input id = "wcap_heading_section_text_email" v-model = "wcap_heading_section_text_email"  name = "wcap_heading_section_text_email"class = "wcap_heading_section_text_email"

							<?php echo $wcap_disabled_field; ?> readonly value="<?php _e( 'Please enter your email address', 'woocommerce-abandoned-cart' ); ?>">

						</td>

						<td id = "wcap_heading_section_text_field_color" class = "wcap_heading_section_text_field_color test_borders">

							<?php $wcap_popup_heading_color_picker = get_option( 'wcap_popup_heading_color_picker' ); ?>

							<span class = "colorpickpreview" style = "background:<?php echo $wcap_popup_heading_color_picker; ?>"></span>

							<input class = "wcap_popup_heading_color_picker colorpick" name = "wcap_popup_heading_color_picker" value = "#737f97" v-model = "wcap_popup_heading_color" v-on:input = "wcap_atc_popup_heading.color = $event.target.value"

							   <?php echo $wcap_disabled_field; ?> readonly >

						</td>

					 </tr>

				</table>

			</div>

			<?php

		}



		/**

		 * It will add the setting for Text displayed below heading section on the add to cart modal settings page.

		 * @param string $wcap_disabled_field It will indicate if field need to be disabled or not.

		 * @since 6.0
		 */

		public static function wcap_add_text_section( $wcap_disabled_field ) {

			?>

			<div id = "wcap_text_section_div" class = "wcap_text_section_div wcap_atc_between_fields_space">

				<table id = "wcap_text_section_table" class = "wcap_text_section_table">

					<th id = "wcap_text_section_table_heading" class = "wcap_text_section_table_heading"> Modal Text </th>

					<tr id = "wcap_text_section_tr" class = "wcap_text_section_tr" >

						<td id = "wcap_text_section_text_field" class = "wcap_text_section_text_field test_borders">

							<input id = "wcap_text_section_text" v-model = "wcap_text_section_text_field" class="wcap_text_section_input_text" name = "wcap_text_section_text"

							<?php echo $wcap_disabled_field; ?> readonly value="<?php _e( 'To add this item to your cart, please enter your email address.', 'woocommerce-abandoned-cart' ); ?>">

						</td>

						<td id = "wcap_text_section_field_color" class = "wcap_text_section_field_color test_borders">

							<?php $wcap_atc_popup_text_color = get_option( 'wcap_popup_text_color_picker' ); ?>

							<span class = "colorpickpreview" style = "background:<?php echo $wcap_atc_popup_text_color; ?>"></span>

							<input class = "wcap_popup_text_color_picker colorpick" name = "wcap_popup_text_color_picker" value = "#bbc9d2" v-model = "wcap_popup_text_color" v-on:input = "wcap_atc_popup_text.color = $event.target.value"

							<?php echo $wcap_disabled_field; ?> readonly>

						</td>

					</tr>

				</table>

			</div>

			<?php

		}



		/**

		 * It will add the setting for email placeholder on the add to cart modal settings page.

		 * @param string $wcap_disabled_field It will indicate if field need to be disabled or not.

		 * @since 6.0
		 */

		public static function wcap_email_placeholder_section( $wcap_disabled_field ) {

			?>

			<div id = "wcap_email_placeholder_section_div" class = "wcap_email_placeholder_section_div wcap_atc_between_fields_space">

				<table id = "wcap_email_placeholder_section_table" class = "wcap_email_placeholder_section_table">

				<th id = "wcap_email_placeholder_section_table_heading" class = "wcap_email_placeholder_section_table_heading"> Email placeholder </th>

					<tr id = "wcap_email_placeholder_section_tr" class = "wcap_email_placeholder_section_tr" >

						<td id = "wcap_email_placeholder_section_text_field" class = "wcap_email_placeholder_section_text_field test_borders">

							<input id = "wcap_email_placeholder_section_input_text" v-model = "wcap_email_placeholder_section_input_text" class="wcap_email_placeholder_section_input_text" name = "wcap_email_placeholder_section_input_text"

							<?php echo $wcap_disabled_field; ?> readonly value="<?php _e( 'Email address', 'woocommerce-abandoned-cart' ); ?>">

						</td>

					</tr>

				</table>

			</div>

			<?php

		}



		/**

		 * It will add the setting for Add to cart button on the add to cart modal settings page.

		 * @param string $wcap_disabled_field It will indicate if field need to be disabled or not.

		 * @since 6.0
		 */

		public static function wcap_button_section( $wcap_disabled_field ) {

			?>

			<div id = "wcap_button_section_div" class = "wcap_button_section_div wcap_atc_between_fields_space">

				<table id = "wcap_button_section_table" class = "wcap_button_section_table">

				<th id = "wcap_button_section_table_heading" class="wcap_button_section_table_heading"> Add to cart button text </th>

					<tr>

						<td id = "wcap_button_section_text_field" class = "wcap_button_section_text_field test_borders">

							<input id = "wcap_button_section_input_text" v-model = "wcap_button_section_input_text" class="wcap_button_section_input_text" name = "wcap_button_section_input_text"

							<?php echo $wcap_disabled_field; ?> readonly value="<?php _e( 'Add to Cart', 'woocommerce-abandoned-cart' ); ?>">

						</td>

					</tr>

					<tr id = "wcap_button_color_section_tr" class = "wcap_button_color_section_tr">

						<td id = "wcap_button_color_section_text_field" class = "wcap_button_color_section_text_field test_borders">

							<?php $wcap_atc_button_bg_color = get_option( 'wcap_button_color_picker' ); ?>

							<span class = "colorpickpreview" style = "background:<?php echo $wcap_atc_button_bg_color; ?>"></span>

							<input id = "wcap_button_color_picker" value = "#0085ba" v-model ="wcap_button_bg_color" v-on:input="wcap_atc_button.backgroundColor = $event.target.value" class="wcap_button_color_picker colorpick" name = "wcap_button_color_picker"

							<?php echo $wcap_disabled_field; ?> readonly >

						</td>

						<td id = "wcap_button_text_color_section_text_field" class = "wcap_button_text_color_section_text_field test_borders">

							<?php $wcap_button_text_color_picker = get_option( 'wcap_button_text_color_picker' ); ?>

							<span class = "colorpickpreview" style = "background:<?php echo $wcap_button_text_color_picker; ?>"></span>

							<input id = "wcap_button_text_color_picker" value = "#ffffff" v-model = "wcap_button_text_color" v-on:input = "wcap_atc_button.color = $event.target.value" class="wcap_button_text_color_picker colorpick" name = "wcap_button_text_color_picker"

							<?php echo $wcap_disabled_field; ?> readonly>

						</td>

					</tr>

				</table>

			</div>

			<?php

		}



		/**

		 * It will add the setting for Email address mandatory field on the add to cart modal settings page.

		 * @param string $wcap_disabled_field It will indicate if field need to be disabled or not.

		 * @since 6.0
		 */

		public static function wcap_mandatory_modal_section( $wcap_disabled_field ) {

			?>

			<table class = "wcap_atc_between_fields_space">

				<th id = "wcap_button_section_table_heading" class = "wcap_button_section_table_heading"> Email address is mandatory ? </th>

				<tr>

					<td>

					   <?php

						$wcap_atc_email_mandatory = get_option( 'wcap_atc_mandatory_email' );

						$active_text = $wcap_atc_email_mandatory;

						?>

					   <button type = "button" class = "wcap-switch-atc-modal-mandatory wcap-toggle-atc-modal-mandatory" wcap-atc-switch-modal-mandatory = <?php echo $wcap_atc_email_mandatory; ?>

					   <?php echo $wcap_disabled_field; ?> readonly >

					   <?php echo $active_text; ?> </button>

					</td>

				</tr>

			</table>

			<?php

		}



		/**

		 * It will add the setting for Email address non mandatory field on the add to cart modal settings page.

		 * @param string $wcap_disabled_field It will indicate if field need to be disabled or not.

		 * @since 6.0
		 */

		public static function wcap_non_mandatory_modal_section_field( $wcap_disabled_field ) {

			$wcap_get_mandatory_field = get_option( 'wcap_atc_mandatory_email' );

			$wcap_disabled_email_field = '';

			if ( 'on' == $wcap_get_mandatory_field ) {

				$wcap_disabled_email_field = 'disabled="disabled"';

			}

			?>

			<div id = "wcap_non_mandatory_modal_section_fields_div" class = "wcap_non_mandatory_modal_section_fields_div wcap_atc_between_fields_space">

				<table id = "wcap_non_mandatory_modal_section_fields_div_table" class = "wcap_non_mandatory_modal_section_fields_div_table">

					<th id = "wcap_non_mandatory_modal_section_fields_table_heading"

					class="wcap_non_mandatory_modal_section_fields_table_heading"> Not mandatory text </th>

					<tr id = "wcap_non_mandatory_modal_section_fields_tr" class = "wcap_non_mandatory_modal_section_fields_tr" >

						<td id = "wcap_non_mandatory_modal_section_fields_text_field" class = "wcap_non_mandatory_modal_section_fields_text_field test_borders">

							<input id = "wcap_non_mandatory_modal_section_fields_input_text" v-model = "wcap_non_mandatory_modal_input_text" class = "wcap_non_mandatory_modal_section_fields_input_text" name = "wcap_non_mandatory_modal_section_fields_input_text" readonly value="<?php _e( 'No Thanks', 'woocommerce-abandoned-cart' ); ?>"

							<?php
							echo $wcap_disabled_field;

								  echo $wcap_disabled_email_field;

							?>
							 >

						</td>

					</tr>

				</table>

			</div>

			<?php

		}



		/**

		 * It will will show th preview of the Add To cart Popup modal with the changes made on any of the settings for it.

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

								<h2 class = "wcap_popup_heading" v-bind:style = "wcap_atc_popup_heading" ><?php _e( 'Please enter your email address.', 'woocommerce-abandoned-cart' ); ?></h2>

								<div class = "wcap_popup_text" v-bind:style = "wcap_atc_popup_text" ><?php _e( 'To add this item to your cart, please enter your email address.', 'woocommerce-abandoned-cart' ); ?></div>

							</div>

						</div>

						<div class = "wcap_popup_form">

							<form action = "" name = "wcap_modal_form">

								<div class = "wcap_popup_input_field_container"  >

									<input class = "wcap_popup_input" type = "text" value = "" name = "email" placeholder ="<?php _e( 'Email address', 'woocommerce-abandoned-cart' ); ?>"

									<?php echo $wcap_disabled_field; ?> readonly >

								</div>

								<button class = "wcap_popup_button" v-bind:style = "wcap_atc_button"

								<?php echo $wcap_disabled_field; ?> ><?php _e( 'Add to Cart', 'woocommerce-abandoned-cart' ); ?></button>

								<br>

								<br>

								<div id = "wcap_non_mandatory_text_wrapper" class = "wcap_non_mandatory_text_wrapper">

									<a class = "wcap_popup_non_mandatory_button" href = "" > <?php _e( 'No Thanks', 'woocommerce-abandoned-cart' ); ?></a>

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

