<?php
/**
 * It will fetch the abandoned cart data & generate and populate data in the modal.
 *
 * @author  Tyche Softwares
 * @package Abandoned-Cart-Lite-for-WooCommerce
 * @since 5.6
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'Wcal_Abandoned_Cart_Details' ) ) {

	/**
	 * It will fetch the abandoned cart data & generate and populate data in the modal.
	 *
	 * @since 5.6
	 */
	class Wcal_Abandoned_Cart_Details {

		/**
		 * This function will fetch all the data and generate the HTML required for the cart details popup modal.
		 * It will be displayed on the Abandoned carts & Send emails tab
		 *
		 * @param array $cart_details - Cart Details received from the AJAX call.
		 * @globals mixed $wpdb
		 *
		 * @since 5.4
		 */
		public static function wcal_get_cart_detail_view( $cart_details ) {
			global $wpdb;

			$wcal_cart_id          = isset( $cart_details['wcal_cart_id'] ) ? $cart_details['wcal_cart_id'] : 0;
			$wcal_email_address    = isset( $cart_details['wcal_email_address'] ) ? $cart_details['wcal_email_address'] : '';
			$wcal_customer_details = isset( $cart_details['wcal_email_address'] ) ? $cart_details['wcal_email_address'] : '';
			$wcal_cart_total       = isset( $cart_details['wcal_cart_total'] ) ? $cart_details['wcal_cart_total'] : 0;
			$wcal_abandoned_date   = isset( $cart_details['wcal_abandoned_date'] ) ? $cart_details['wcal_abandoned_date'] : '';
			$wcal_current_page     = isset( $cart_details['wcal_current_page'] ) ? $cart_details['wcal_current_page'] : '';

			$wc_shipping_charges           = '';
			$shipping_charges_value        = '';
			$wcal_shipping_charges_text    = '';
			$wc_shipping_charges_text      = '';
			$wc_shipping_charges_formatted = '';

			$wcal_show_customer_detail      = '<br><a href="" id "wcal_customer_detail_modal" class ="wcal_customer_detail_modal"> Show Details </a>';
			$wcal_get_abandoned_cart_result = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM `' . $wpdb->prefix . 'ac_abandoned_cart_history_lite` WHERE id = %d', $wcal_cart_id ) ); //phpcs:ignore

			$user_role = '';
			$user_id   = 0;
			$user_type = '';

			if ( isset( $wcal_get_abandoned_cart_result[0]->user_id ) ) {
				$user_id   = $wcal_get_abandoned_cart_result[0]->user_id;
				$user_type = $wcal_get_abandoned_cart_result[0]->user_type;
				if ( $wcal_get_abandoned_cart_result[0]->user_id > 0 && $wcal_get_abandoned_cart_result[0]->user_id < 63000000 ) {
					$user_role = wcal_common::wcal_get_user_role( $user_id );
				} else {
					$user_role = 'Guest';
				}
			}

			$wcal_abandoned_status = '';
			$recovered_order       = 0;

			if ( isset( $wcal_get_abandoned_cart_result[0] ) && $wcal_get_abandoned_cart_result[0]->recovered_cart > 0 ) {
				$wcal_abandoned_status = 'Recovered';
				$recovered_order       = intval( $wcal_get_abandoned_cart_result[0]->recovered_cart );
			} elseif ( isset( $wcal_get_abandoned_cart_result[0] ) && '0' === $wcal_get_abandoned_cart_result[0]->recovered_cart && '1' === $wcal_get_abandoned_cart_result[0]->cart_ignored ) {
				$wcal_abandoned_status = 'Abandoned but new cart created';
			} elseif ( isset( $wcal_get_abandoned_cart_result[0] ) && '0' === $wcal_get_abandoned_cart_result[0]->recovered_cart && '0' === $wcal_get_abandoned_cart_result[0]->cart_ignored ) {
				$wcal_abandoned_status = 'Abandoned';
			}

			$ac_status = __( $wcal_abandoned_status, 'woocommerce-abandon-cart' ); //phpcs:ignore
			switch ( $wcal_abandoned_status ) {
				case 'Abandoned':
					$wcal_abandoned_status = "<span id='wcal_status_modal_abandoned_new' class='wcal_status_modal_abandoned_new'  >" . $ac_status . '</span>';
					break;
				case 'Recovered':
					$wcal_abandoned_status = "<span id='wcal_status_modal_abandoned' class='wcal_status_modal_abandoned'  >" . $ac_status . '</span>';
					break;
				case 'Unsubscribed':
					$wcal_abandoned_status = "<span id ='wcal_unsubscribe_link_modal' class = 'unsubscribe_link'  >" . $ac_status . '</span>';
					break;
				case 'Abandoned - Order Unpaid':
					$wcal_abandoned_status = "<span id ='wcal_status_unpaid_order' class = 'wcal_status_unpaid_order'  >" . $ac_status . '</span>';
					break;
				default:
					$wcal_abandoned_status = "<span id='wcal_status_modal_abandoned_new' class='wcal_status_modal_abandoned_new'  >" . $ac_status . '</span>';
					break;

			}

			$wcal_get_abandoned_sent_result = $wpdb->get_results( $wpdb->prepare( 'SELECT wcet.`template_name`, wsht.`sent_time`, wsht.`id`, wsht.`sent_email_id` FROM `' . $wpdb->prefix . 'ac_sent_history_lite` as wsht LEFT JOIN `' . $wpdb->prefix . 'ac_email_templates_lite` AS wcet ON wsht.template_id = wcet.id WHERE abandoned_order_id = %d', $wcal_cart_id ) ); //phpcs:ignore

			$shipping_charges         = 0;
			$currency                 = '';
			$currency_symbol          = get_woocommerce_currency_symbol();
			$billing_field_display    = 'block';
			$email_field_display      = 'block';
			$phone_field_display      = 'block';
			$shipping_field_display   = 'block';
			$shipping_charges_display = 'none';

			$user_billing_company    = '';
			$user_billing_address_1  = '';
			$user_billing_address_2  = '';
			$user_billing_city       = '';
			$user_billing_postcode   = '';
			$user_billing_state      = '';
			$user_billing_country    = '';
			$user_shipping_company   = '';
			$user_shipping_address_1 = '';
			$user_shipping_address_2 = '';
			$user_shipping_city      = '';
			$user_shipping_postcode  = '';
			$user_shipping_state     = '';
			$user_shipping_country   = '';
			$billing_field_display   = 'block';
			$shipping_field_display  = 'block';

			if ( 'GUEST' === $user_type && 0 !== $user_id ) {
				$results_guest = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM `' . $wpdb->prefix . 'ac_guest_abandoned_cart_history_lite` WHERE id = %d', $user_id ) ); //phpcs:ignore

				$user_first_name        = ( isset( $results_guest[0]->billing_first_name ) && '' !== $results_guest[0]->billing_first_name ) ? $results_guest[0]->billing_first_name : '';
				$user_last_name         = ( isset( $results_guest[0]->billing_last_name ) && '' !== $results_guest[0]->billing_last_name ) ? $results_guest[0]->billing_last_name : '';
				$user_billing_postcode  = ( isset( $results_guest[0]->billing_zipcode ) && '' !== $results_guest[0]->billing_zipcode ) ? $results_guest[0]->billing_zipcode : '';
				$user_shipping_postcode = ( isset( $results_guest[0]->shipping_zipcode ) && '' !== $results_guest[0]->shipping_zipcode ) ? $results_guest[0]->shipping_zipcode : '';
				$shipping_charges       = ( isset( $results_guest[0]->shipping_charges ) && '' !== $results_guest[0]->shipping_charges ) ? $results_guest[0]->shipping_charges : '';
				$user_billing_phone     = ( isset( $results_guest[0]->phone ) && '' !== $results_guest[0]->phone ) ? $results_guest[0]->phone : '';

				if ( isset( $user_billing_phone ) && '' === $user_billing_phone ) {
					$phone_field_display = 'none';
				}

				$user_email = '';
				if ( isset( $results_guest[0]->email_id ) ) {

					$user_email            = $results_guest[0]->email_id;
					$wcal_email_address    = $results_guest[0]->email_id;
					$customer_information  = $user_first_name . ' ' . $user_last_name;
					$wcal_customer_details = '' === $user_billing_phone ? $customer_information . '<br>' . $user_role : $customer_information . '<br>' . $user_billing_phone . '<br>' . $user_role;

				}
			} elseif ( isset( $wcal_get_abandoned_cart_result[0] ) && 'GUEST' === $user_type && 0 === $user_id ) {
				$user_email             = '';
				$user_first_name        = 'Visitor';
				$user_last_name         = '';
				$user_billing_postcode  = '';
				$user_shipping_postcode = '';
				$shipping_charges       = '';
				$user_billing_phone     = '';
				$email_field_display    = 'none';
				$phone_field_display    = 'none';
			} else {

				$user_email = '';
				if ( isset( $wcal_get_abandoned_cart_result[0] ) ) {
					$user_email = get_user_meta( $user_id, 'billing_email', true );
				}

				if ( isset( $user_email ) && '' === $user_email ) {

					if ( isset( $wcal_get_abandoned_cart_result[0] ) ) {
						$current_user_data = get_userdata( $wcal_get_abandoned_cart_result[0]->user_id );
						$user_email        = isset( $current_user_data->user_email ) && '' !== $current_user_data->user_email ? $current_user_data->user_email : '';
					}
				}

				$wcal_email_address = $user_email;

				$user_first_name_temp = '';
				$user_phone_number    = array();

				if ( $user_id > 0 ) {
					$user_first_name_temp = get_user_meta( $user_id, 'billing_first_name', true );
					$user_phone_number    = get_user_meta( $user_id, 'billing_phone' );
				}

				if ( isset( $user_first_name_temp ) && '' === $user_first_name_temp ) {
					$user_data       = get_userdata( $user_id );
					$user_first_name = isset( $user_data->first_name ) && '' !== $user_data->first_name ? $user_data->first_name : '';
				} else {
					$user_first_name = $user_first_name_temp;
				}

				$user_last_name_temp = get_user_meta( $user_id, 'billing_last_name', true );
				if ( isset( $user_last_name_temp ) && '' === $user_last_name_temp ) {
					$user_data      = get_userdata( $user_id );
					$user_last_name = isset( $user_data->last_name ) && '' !== $user_data->last_name ? $user_data->last_name : '';
				} else {
					$user_last_name = $user_last_name_temp;
				}

				$user_billing_phone = '';
				if ( isset( $user_phone_number[0] ) ) {
					$user_billing_phone = $user_phone_number[0];
				}

				$customer_information = $user_first_name . ' ' . $user_last_name;

				$wcal_customer_details = ( '' === $user_billing_phone ) ? $customer_information . '<br>' . $user_role : $customer_information . '<br>' . $user_billing_phone . '<br>' . $user_role;

				if ( $user_id > 0 ) {

					$user_billing_details = self::wcal_get_billing_details( $user_id );

					$user_billing_company   = $user_billing_details['billing_company'];
					$user_billing_address_1 = $user_billing_details['billing_address_1'];
					$user_billing_address_2 = $user_billing_details['billing_address_2'];
					$user_billing_city      = $user_billing_details['billing_city'];
					$user_billing_postcode  = $user_billing_details['billing_postcode'];
					$user_billing_country   = $user_billing_details['billing_country'];
					$user_billing_state     = $user_billing_details['billing_state'];

					$user_shipping_first_name   = get_user_meta( $user_id, 'shipping_first_name' );
					$user_shipping_last_name    = get_user_meta( $user_id, 'shipping_last_name' );
					$user_shipping_company_temp = get_user_meta( $user_id, 'shipping_company' );
					$user_shipping_company      = '';
					if ( isset( $user_shipping_company_temp[0] ) ) {
						$user_shipping_company = $user_shipping_company_temp[0];
					}
					$user_shipping_address_1_temp = get_user_meta( $user_id, 'shipping_address_1' );
					$user_shipping_address_1      = '';
					if ( isset( $user_shipping_address_1_temp[0] ) ) {
						$user_shipping_address_1 = $user_shipping_address_1_temp[0];
					}
					$user_shipping_address_2_temp = get_user_meta( $user_id, 'shipping_address_2' );
					$user_shipping_address_2      = '';
					if ( isset( $user_shipping_address_2_temp[0] ) ) {
						$user_shipping_address_2 = $user_shipping_address_2_temp[0];
					}
					$user_shipping_city_temp = get_user_meta( $user_id, 'shipping_city' );
					$user_shipping_city      = '';
					if ( isset( $user_shipping_city_temp[0] ) ) {
						$user_shipping_city = $user_shipping_city_temp[0];
					}
					$user_shipping_postcode_temp = get_user_meta( $user_id, 'shipping_postcode' );
					$user_shipping_postcode      = '';
					if ( isset( $user_shipping_postcode_temp[0] ) ) {
						$user_shipping_postcode = $user_shipping_postcode_temp[0];
					}
					$user_shipping_country_temp = get_user_meta( $user_id, 'shipping_country' );
					$user_shipping_country      = '';
					if ( isset( $user_shipping_country_temp[0] ) ) {
						$user_shipping_country = $user_shipping_country_temp[0];
						if ( isset( $woocommerce->countries->countries[ $user_shipping_country ] ) ) {
							$user_shipping_country = $woocommerce->countries->countries[ $user_shipping_country ];
						}
					}
					$user_shipping_state_temp = get_user_meta( $user_id, 'shipping_state' );
					$user_shipping_state      = '';
					if ( isset( $user_shipping_state_temp[0] ) ) {
						$user_shipping_state = $user_shipping_state_temp[0];
						if ( isset( $woocommerce->countries->states[ $user_shipping_country_temp[0] ][ $user_shipping_state ] ) ) {
							// code...
							$user_shipping_state = WC()->countries->states[ $user_shipping_country_temp[0] ][ $user_shipping_state ];
						}
					}
					// Get shipping charges.
					$cart_info           = json_decode( stripslashes( $wcal_get_abandoned_cart_result[0]->abandoned_cart_info ) );
					$wc_shipping_charges = isset( $cart_info->shipping_charges ) ? $cart_info->shipping_charges : 0;
				}
			}

			if ( '' === $user_billing_company && '' === $user_billing_address_1 && '' === $user_billing_address_2 &&
				'' === $user_billing_city && '' === $user_billing_postcode && '' === $user_billing_state && '' === $user_billing_country ) {
				$billing_field_display = 'none';
			}

			$wcal_billing_address_text = __( 'Billing Address:', 'woocommerce-abandon-cart' );

			$wcal_create_billing_address = '' !== $user_billing_company ? '<br>' . $user_billing_company . '</br>' : '<br>';
			if ( '' !== $user_billing_address_1 ) {
				$wcal_create_billing_address .= $user_billing_address_1 . '</br>';
			}
			if ( '' !== $user_billing_address_2 ) {
				$wcal_create_billing_address .= $user_billing_address_2 . '</br>';
			}
			if ( '' !== $user_billing_city ) {
				$wcal_create_billing_address .= $user_billing_city . '</br>';
			}
			if ( '' !== $user_billing_postcode ) {
				$wcal_create_billing_address .= $user_billing_postcode;
			}

			$wcal_shipping_address_text = __( 'Shipping Address:', 'woocommerce-abandon-cart' );

			if ( '' === $user_shipping_company &&
				'' === $user_shipping_address_1 &&
				'' === $user_shipping_address_2 &&
				'' === $user_shipping_city &&
				'' === $user_shipping_postcode &&
				'' === $user_shipping_state &&
				'' === $user_shipping_country ) {

				$wcal_create_shipping_address = 'Shipping Address same as Billing Address';
			} else {
				$wcal_create_shipping_address = '' !== $user_shipping_company ? '<br>' . $user_shipping_company . '</br>' : '<br>';
				if ( '' !== $user_shipping_address_1 ) {
					$wcal_create_shipping_address .= $user_shipping_address_1 . '</br>';
				}
				if ( '' !== $user_shipping_address_2 ) {
					$wcal_create_shipping_address .= $user_shipping_address_2 . '</br>';
				}
				if ( '' !== $user_shipping_city ) {
					$wcal_create_shipping_address .= $user_shipping_city . '</br>';
				}
				if ( '' !== $user_shipping_postcode ) {
					$wcal_create_shipping_address .= $user_shipping_postcode;
				}
			}

			if ( '' !== $shipping_charges ) {
				$wcal_shipping_charges_text = __( 'Shipping Charges:', 'woocommerce-abandon-cart' );
				$shipping_charges_value     = wc_price( $shipping_charges );
				$shipping_charges_display   = 'block';
			}

			$wcal_add_customer_details = " <div class= 'wcal_modal_customer_all_details' >
                <span style = 'display: $shipping_charges_display;' >
                     <strong>  $wcal_shipping_charges_text </strong>
                   $shipping_charges_value
                </span>
                <span style = 'display:$billing_field_display;'>
                <strong>  $wcal_billing_address_text </strong>
                   $wcal_create_billing_address
                </span>
                <span style = 'display:$shipping_field_display;'>
                <strong>  $wcal_shipping_address_text </strong>
                   $wcal_create_shipping_address <br/>
                   <strong>  $wc_shipping_charges_text </strong>
                   $wc_shipping_charges_formatted
                </span>

            </div>";

			$wcal_cart_content_var = '';
			$wcal_quantity_total   = 0;
			if ( isset( $wcal_get_abandoned_cart_result[0] ) && ! empty( $wcal_get_abandoned_cart_result ) ) {

				$wcal_cart_info = json_decode( stripslashes( $wcal_get_abandoned_cart_result[0]->abandoned_cart_info ) );

				if ( null === $wcal_cart_info ) {
					$wcal_cart_info = json_decode( $wcal_get_abandoned_cart_result[0]->abandoned_cart_info );
				}

				$wcal_cart_details = isset( $wcal_cart_info->cart ) ? $wcal_cart_info->cart : array();
				$wcal_cart_details = isset( $wcal_cart_details->cart_contents ) ? $wcal_cart_details->cart_contents : $wcal_cart_details;

				// Currency selected.
				$currency = isset( $wcal_cart_info->currency ) ? $wcal_cart_info->currency : '';

				$line_subtotal_tax = '';

				$display_cart_details = self::wcal_get_cart_details( $wcal_cart_details, $wcal_cart_id, $wcal_current_page, $currency, $wcal_cart_total );

				$wcal_quantity_total = ( $display_cart_details ['qty_total'] > 0 ) ? $display_cart_details ['qty_total'] : 0;
				$wcal_cart_total     = ( $display_cart_details ['cart_total'] > 0 ) ? $display_cart_details ['cart_total'] : 0;

				$line_subtotal_tax_total = ( $display_cart_details ['line_subtotal_tax_total'] > 0 ) ? $display_cart_details ['line_subtotal_tax_total'] : 0;

				if ( count( get_object_vars( $wcal_cart_details ) ) > 0 ) {

					foreach ( $wcal_cart_details as $k => $v ) {
						$product_id         = $display_cart_details[ $k ]['product_id'];
						$product_page_url   = get_permalink( $product_id );
						$product_name       = $display_cart_details[ $k ]['product_name'];
						$item_total_display = $display_cart_details[ $k ]['item_total_formatted'];
						$quantity_total     = $display_cart_details[ $k ]['qty'];
						$line_tax_total     = $display_cart_details[ $k ]['line_tax'];

						$qty_item_text = 'item';
						if ( $quantity_total > 1 ) {
							$qty_item_text = 'items';
						}

						$wcal_cart_content_var .= '<tr>';
						$wcal_cart_content_var .= '<td> <a href="' . $product_page_url . '"> ' . $product_name . '</a></td>';
						$wcal_cart_content_var .= '<td> ' . $item_total_display . '</td>';
						$wcal_cart_content_var .= '<td> ' . $quantity_total . ' ' . $qty_item_text . '</td>';
						$wcal_cart_content_var .= '</tr>';
					}
				}

				$wcal_include_tax         = get_option( 'woocommerce_prices_include_tax' );
				$wcal_include_tax_setting = get_option( 'woocommerce_calc_taxes' );
			}

			$wcal_cart_total = apply_filters( 'acfac_change_currency', wcal_common::wcal_get_price( $wcal_cart_total, $currency ), $wcal_cart_id, $wcal_cart_total, 'wcal_ajax' );

			$item_disp = isset( $wcal_quantity_total ) && 1 === $wcal_quantity_total ? __( 'item', 'woocommerce-abandon-cart' ) : __( 'items', 'woocommerce-abandon-cart' );

			$show_taxes = apply_filters( 'wcal_show_taxes', true );

			if ( $show_taxes && isset( $wcal_include_tax ) && 'no' === $wcal_include_tax &&
					isset( $wcal_include_tax_setting ) && 'yes' === $wcal_include_tax_setting ) {

					$line_subtotal_tax_total = apply_filters( 'acfac_change_currency', wcal_common::wcal_get_price( $line_subtotal_tax_total, $currency ), $wcal_cart_id, $line_subtotal_tax_total, 'wcal_ajax' );

					$wcal_cart_total = $wcal_cart_total . '<br>Tax: ' . $line_subtotal_tax_total;
			} elseif ( isset( $wcal_include_tax ) && 'yes' === $wcal_include_tax &&
					isset( $wcal_include_tax_setting ) && 'yes' === $wcal_include_tax_setting ) {
					$line_subtotal_tax_total = apply_filters( 'acfac_change_currency', wcal_common::wcal_get_price( $line_subtotal_tax_total, $currency ), $wcal_cart_id, $line_subtotal_tax_total, 'wcal_ajax' );
				if ( $show_taxes ) {
					$wcal_cart_total = $wcal_cart_total . ' (includes Tax: ' . $line_subtotal_tax_total . ')';
				}
			}
			$wcal_cart_total = $wcal_cart_total . '<br>' . $wcal_quantity_total . ' ' . $item_disp;

			$wcal_cart_email_sent = '';
			if ( ! empty( $wcal_get_abandoned_sent_result ) && count( $wcal_get_abandoned_sent_result ) > 0 ) {
				foreach ( $wcal_get_abandoned_sent_result as $wcal_get_abandoned_sent_key => $wcal_get_abandoned_sent_value ) {

					$wcal_email_sent_time = strtotime( $wcal_get_abandoned_sent_value->sent_time );
					$sent_date_format     = date_i18n( get_option( 'date_format' ), $wcal_email_sent_time );
					$sent_time_format     = date_i18n( get_option( 'time_format' ), $wcal_email_sent_time );
					$wcal_email_sent_time = $sent_date_format . ' ' . $sent_time_format;
					$email_address        = $wcal_get_abandoned_sent_value->sent_email_id;

					$wcal_cart_email_sent .= '<tr>';
					$wcal_cart_email_sent .= '<td>Email template <strong>' . $wcal_get_abandoned_sent_value->template_name . '</strong> was sent to <strong>' . $email_address . ' </strong> on ' . $wcal_email_sent_time . ' </td>';
					$wcal_cart_email_sent .= '</tr>';

				}
			}

			?>

		<div class="wcal-modal__header">
			<h1>
				<?php
				// translators: Cart ID.
				printf( esc_html__( 'Cart #%s', 'woocommerce-abandoned-cart' ), esc_attr( $wcal_cart_id ) );
				?>
			</h1>
			<?php
			echo wp_kses_post( stripslashes( $wcal_abandoned_status ) );
			if ( $recovered_order > 0 ) {
				$order_post      = get_post( $recovered_order );
				$recovered_stamp = strtotime( $order_post->post_date );

				$order_date_format = date_i18n( get_option( 'date_format' ), $recovered_stamp );
				$order_time_format = date_i18n( get_option( 'time_format' ), $recovered_stamp );
				$recovered_date    = "$order_date_format $order_time_format";

				$order_url = admin_url( "post.php?post=$recovered_order&action=edit" );
				echo wp_kses_post(
					sprintf(
						// translators: Recovered Order Link, Order ID, Recovered Date.
						'<h1>' . __( 'Order', 'woocommerce-abandon-cart' ) . " <a href='%s' target='_blank'>#%s</a><h1> <h5>" . __( 'Recovered on %s', 'woocommerce-abandon-cart' ) . '</h5>',
						esc_url( $order_url ),
						esc_attr( $recovered_order ),
						esc_attr( $recovered_date )
					)
				);
			}
			?>
		</div>

		<div class="wcal-modal__body">
			<div class="wcal-modal__body-inner">

				<table cellspacing="0" cellpadding="6" border="1" class="wcal-cart-table">
					<thead>
					<tr>
						<th><?php esc_html_e( 'Email Address', 'woocommerce-abandon-cart' ); ?></th>
						<th><?php esc_html_e( 'Customer Details', 'woocommerce-abandon-cart' ); ?></th>
						<th><?php esc_html_e( 'Order Total', 'woocommerce-abandon-cart' ); ?></th>
						<th><?php esc_html_e( 'Abandoned Date', 'woocommerce-abandon-cart' ); ?></th>
					</tr>
					</thead>
					<tbody>
						<tr>
							<td> <?php echo esc_attr( $wcal_email_address ); ?> </td>
							<td> <?php echo wp_kses_post( $wcal_customer_details . $wcal_show_customer_detail ) . $wcal_add_customer_details; // phpcs:ignore ?> </td>
							<td> <?php echo wp_kses_post( stripslashes( $wcal_cart_total ) ); ?> </td>
							<td> <?php echo esc_attr( $wcal_abandoned_date ); ?> </td>
						</tr>
					</tbody>
				</table>
				<table cellspacing="0" cellpadding="0" class="wcal-modal-cart-content">
					<thead>
					<tr>
						<th><?php esc_html_e( 'Item Name', 'woocommerce-abandon-cart' ); ?></th>
						<th><?php esc_html_e( 'Item Cost', 'woocommerce-abandon-cart' ); ?></th>
						<th><?php esc_html_e( 'Item Quantity', 'woocommerce-abandon-cart' ); ?></th>
					</tr>
					</thead>
					<tbody>
						<?php echo wp_kses_post( $wcal_cart_content_var ); ?>
					</tbody>
				</table>
				<?php if ( ! empty( $wcal_get_abandoned_sent_result ) && count( $wcal_get_abandoned_sent_result ) > 0 ) { ?>
				<table cellspacing="0" cellpadding="0" class="wcal-modal-email-content">
					<tbody>
						<?php echo wp_kses_post( $wcal_cart_email_sent ); ?>
					</tbody>
				</table>
				<?php } ?>
			</div>
		</div>

		<div class="wcal-modal__footer">
			<?php
			$wcal_footer_close_text = __( 'Close', 'woocommerce-abandon-cart' );
			$value_close            = '<a class=" button wcal-icon-close-footer wcal-js-close-modal" >' . $wcal_footer_close_text . '</a>';
			echo wp_kses_post( $value_close );
			?>
		</div>
			<?php
		}

		/**
		 * Returns an array of customer billing information.
		 * Should be called only for registered users.
		 *
		 * @param integer $user_id - User ID.
		 * @return array $billing_details - Contains Billing Address Details.
		 * @since 5.6
		 */
		public static function wcal_get_billing_details( $user_id ) {

			$billing_details = array();

			$user_billing_company_temp = get_user_meta( $user_id, 'billing_company' );
			$user_billing_company      = '';
			if ( isset( $user_billing_company_temp[0] ) ) {
				$user_billing_company = $user_billing_company_temp[0];
			}
			$billing_details['billing_company'] = $user_billing_company;

			$user_billing_address_1_temp = get_user_meta( $user_id, 'billing_address_1' );
			$user_billing_address_1      = '';
			if ( isset( $user_billing_address_1_temp[0] ) ) {
				$user_billing_address_1 = $user_billing_address_1_temp[0];
			}
			$billing_details['billing_address_1'] = $user_billing_address_1;

			$user_billing_address_2_temp = get_user_meta( $user_id, 'billing_address_2' );
			$user_billing_address_2      = '';
			if ( isset( $user_billing_address_2_temp[0] ) ) {
				$user_billing_address_2 = $user_billing_address_2_temp[0];
			}
			$billing_details['billing_address_2'] = $user_billing_address_2;

			$user_billing_city_temp = get_user_meta( $user_id, 'billing_city' );
			$user_billing_city      = '';
			if ( isset( $user_billing_city_temp[0] ) ) {
				$user_billing_city = $user_billing_city_temp[0];
			}
			$billing_details['billing_city'] = $user_billing_city;

			$user_billing_postcode_temp = get_user_meta( $user_id, 'billing_postcode' );
			$user_billing_postcode      = '';
			if ( isset( $user_billing_postcode_temp[0] ) ) {
				$user_billing_postcode = $user_billing_postcode_temp[0];
			}
			$billing_details['billing_postcode'] = $user_billing_postcode;

			$user_billing_country_temp = get_user_meta( $user_id, 'billing_country' );
			$user_billing_country      = '';
			if ( isset( $user_billing_country_temp[0] ) ) {
				$user_billing_country = $user_billing_country_temp[0];
				if ( isset( WC()->countries->countries[ $user_billing_country ] ) ) {
					$user_billing_country = WC()->countries->countries[ $user_billing_country ];
				} else {
					$user_billing_country = '';
				}
			}
			$billing_details['billing_country'] = $user_billing_country;

			$user_billing_state_temp = get_user_meta( $user_id, 'billing_state' );
			$user_billing_state      = '';
			if ( isset( $user_billing_state_temp[0] ) ) {
				$user_billing_state = $user_billing_state_temp[0];
				if ( isset( WC()->countries->states[ $user_billing_country_temp[0] ][ $user_billing_state ] ) ) {
					$user_billing_state = WC()->countries->states[ $user_billing_country_temp[0] ][ $user_billing_state ];
				} else {
					$user_billing_state = '';
				}
			}
			$billing_details['billing_state'] = $user_billing_state;

			return $billing_details;
		}

		/**
		 * Returns the Item Name, Qty and Total for any given product
		 * in the WC Cart.
		 *
		 * @param stdClass $wcal_cart_details - Cart Information from WC()->cart;.
		 * @param integer  $wcal_cart_id - Abandoned Cart ID.
		 * @param string   $wcal_current_page - Current page where the data is needed.
		 * @param string   $currency - Product Currency.
		 * @param float    $wcal_cart_total - Cart Total Amount.
		 * @return array $item_details - Item Data.
		 * @since 5.6
		 */
		public static function wcal_get_cart_details( $wcal_cart_details, $wcal_cart_id = '', $wcal_current_page = '', $currency = '', $wcal_cart_total = 0 ) {

			global $woocommerce;

			$cart_total                  = 0;
			$item_subtotal               = 0;
			$item_total                  = 0;
			$line_subtotal_tax_display   = 0;
			$after_item_subtotal         = 0;
			$after_item_subtotal_display = 0;
			$line_subtotal_tax_total     = 0;
			$wcal_cart_total             = 0;

			$line_subtotal_tax   = 0;
			$wcal_quantity_total = 0;

			$wcal_include_tax         = get_option( 'woocommerce_prices_include_tax' );
			$wcal_include_tax_setting = get_option( 'woocommerce_calc_taxes' );

			$item_details = array();

			foreach ( $wcal_cart_details as $k => $v ) {

				$product_id = $v->product_id;
				$prod_name  = get_post( $product_id );
				if ( count( get_object_vars( $prod_name ) ) > 0 && 'product' === $prod_name->post_type ) {

					$quantity_total      = $v->quantity;
					$wcal_quantity_total = $wcal_quantity_total + $v->quantity;
					$item_name           = $prod_name->post_title;
					$product_name        = apply_filters( 'wcal_product_name', $item_name, $v->product_id );
					$wcal_product        = wc_get_product( $product_id );
					if ( version_compare( $woocommerce->version, '3.0.0', '>=' ) ) {
						$wcal_product_type = $wcal_product->get_type();
					} else {
						$wcal_product_type = $wcal_product->product_type;
					}
					$wcal_product_sku = apply_filters( 'wcal_product_sku', $wcal_product->get_sku(), $v->product_id );
					if ( false !== $wcal_product_sku && '' !== $wcal_product_sku ) {
						if ( 'simple' === $wcal_product_type && '' !== $wcal_product->get_sku() ) {
							$wcal_sku = '<br> SKU: ' . $wcal_product->get_sku();
						} else {
							$wcal_sku = '';
						}
						$product_name = $product_name . $wcal_sku;
					} else {
						$product_name = $product_name;
					}
					$product_name = apply_filters( 'wcal_after_product_name', $product_name, $v->product_id );
					if ( isset( $v->variation_id ) && '' !== $v->variation_id ) {
						$variation_id = $v->variation_id;
						$variation    = wc_get_product( $variation_id );

						if ( false !== $variation ) {
							$name        = $variation->get_formatted_name();
							$explode_all = explode( '&ndash;', $name );

							if ( version_compare( $woocommerce->version, '3.0.0', '>=' ) ) {
								if ( false !== $wcal_product_sku && '' !== $wcal_product_sku ) {
									$wcal_sku = '';
									if ( $variation->get_sku() ) {
										$wcal_sku = 'SKU: ' . $variation->get_sku();
									}
									$wcal_get_formatted_variation = wc_get_formatted_variation( $variation, true );

									$add_product_name = $product_name . ' - <br>' . $wcal_sku . ' ' . $wcal_get_formatted_variation;
								} else {
									$wcal_get_formatted_variation = wc_get_formatted_variation( $variation, true );

									$add_product_name = $product_name . '<br>' . $wcal_get_formatted_variation;
								}

								$pro_name_variation = (array) $add_product_name;

							} else {
								$pro_name_variation = array_slice( $explode_all, 1, -1 );
							}
							$product_name_with_variable = '';
							$explode_many_varaition     = array();
							foreach ( $pro_name_variation as $pro_name_variation_key => $pro_name_variation_value ) {
								$explode_many_varaition = explode( ',', $pro_name_variation_value );
								if ( ! empty( $explode_many_varaition ) ) {
									foreach ( $explode_many_varaition as $explode_many_varaition_key => $explode_many_varaition_value ) {
										$product_name_with_variable = $product_name_with_variable . html_entity_decode( $explode_many_varaition_value ) . '<br>';
									}
								} else {
									$product_name_with_variable = $product_name_with_variable . html_entity_decode( $explode_many_varaition_value ) . '<br>';
								}
							}
							$product_name = apply_filters( 'wcal_after_variable_product_name', $product_name_with_variable, $v->product_id );
						}
					}

					// Item subtotal is calculated as product total including taxes.
					if ( isset( $wcal_include_tax ) && 'no' === $wcal_include_tax &&
						isset( $wcal_include_tax_setting ) && 'yes' === $wcal_include_tax_setting ) {
							$item_subtotal    += isset( $v->line_total ) ? $v->line_total : 0;  // This is fix.
							$line_subtotal_tax = isset( $v->line_tax ) ? $v->line_tax : 0; // This is fix.

							$after_item_subtotal = $item_subtotal;
							// On sent email we need this for first row.
							$line_subtotal_tax_total += $line_subtotal_tax;

					} elseif ( isset( $wcal_include_tax ) && 'yes' === $wcal_include_tax &&
						isset( $wcal_include_tax_setting ) && 'yes' === $wcal_include_tax_setting ) {
							// Item subtotal is calculated as product total including taxes.
						if ( is_numeric( $v->line_tax ) && $v->line_tax > 0 ) {
							$line_subtotal_tax_display = $v->line_tax;

							// After copon code price.
							$after_item_subtotal = $item_subtotal + $v->line_total + $v->line_tax;

							// Calculate the product price.
							$item_subtotal = $item_subtotal + $v->line_subtotal + $v->line_subtotal_tax;

							// On sent emial tab we need this for first row.
							$line_subtotal_tax_total += $line_subtotal_tax_display;
						} else {
							$item_subtotal              = $item_subtotal + $v->line_total;
							$line_subtotal_tax_display += $v->line_tax;
						}
					} else {
						$item_subtotal       = $item_subtotal + $v->line_total;
						$after_item_subtotal = $v->line_total;
					}

					// Line total.
					$item_total                  = $item_subtotal;
					$item_price                  = $item_subtotal / $quantity_total;
					$after_item_subtotal_display = ( $item_subtotal - $after_item_subtotal ) + $after_item_subtotal_display;

					$item_total_display = apply_filters( 'acfac_change_currency', wcal_common::wcal_get_price( $item_total, $currency ), $wcal_cart_id, $item_total, 'wcal_ajax' );

					$item_price = apply_filters( 'acfac_change_currency', wcal_common::wcal_get_price( $item_price, $currency ), $wcal_cart_id, $item_price, 'wcal_ajax' );

					if ( isset( $wcal_include_tax ) && 'no' === $wcal_include_tax &&
						isset( $wcal_include_tax_setting ) && 'yes' === $wcal_include_tax_setting ) {

						$line_tax_currency  = apply_filters( 'acfac_change_currency', wcal_common::wcal_get_price( $line_subtotal_tax, $currency ), $wcal_cart_id, $line_subtotal_tax, 'wcal_ajax' );
						$item_total_display = $item_total_display . '<br>' . __( 'Tax: ', 'woocommerce-abandon-cart' ) . $line_tax_currency;
					} elseif ( isset( $wcal_include_tax ) && 'yes' === $wcal_include_tax &&
							isset( $wcal_include_tax_setting ) && 'yes' === $wcal_include_tax_setting ) {

							$line_tax_currency = apply_filters( 'acfac_change_currency', wcal_common::wcal_get_price( $line_subtotal_tax_display, $currency ), $wcal_cart_id, $line_subtotal_tax_display, 'wcal_ajax' );

							$item_total_display = $item_total_display . ' (' . __( 'includes Tax: ', 'woocommerce-abandon-cart' ) . $line_tax_currency . ')';
					}

					$wcal_cart_total += $after_item_subtotal;

					$product = wc_get_product( $product_id );
					// If bundled product, get the list of sub products.
					if ( isset( $product->bundle_data ) && is_array( $product->bundle_data ) && count( $product->bundle_data ) > 0 ) {
						foreach ( $product->bundle_data as $b_key => $b_value ) {
							$bundle_child[] = $b_key;
						}
					}
					// check if the product is a part of the bundles product, if yes, set qty and totals to blanks.
					if ( isset( $bundle_child ) && count( $bundle_child ) > 0 ) {
						if ( in_array( $product_id, $bundle_child, true ) ) {
							$item_subtotal      = '';
							$item_total_display = '';
							$quantity_total     = '';
						}
					}
				} else {
					$product_name       = __( 'Product has been deleted', 'woocommerce-abandon-cart' );
					$item_total_display = '';
					$quantity_total     = '';
					$qty_item_text      = '';
				}

				$item_details[ $k ]['product_id']           = $product_id;
				$item_details[ $k ]['product_name']         = $product_name;
				$item_details[ $k ]['item_total_formatted'] = $item_total_display;
				$item_details[ $k ]['item_total']           = $item_total;
				$item_details[ $k ]['qty']                  = $quantity_total;
				$item_details[ $k ]['line_tax']             = $line_subtotal_tax_total;

				// reset the fields.
				$item_subtotal = 0;
				$item_total    = 0;
			}

			$item_details['qty_total']               = $wcal_quantity_total;
			$item_details['cart_total']              = $wcal_cart_total;
			$item_details['line_subtotal_tax_total'] = $line_subtotal_tax_total;

			return $item_details;
		}
	}
}
