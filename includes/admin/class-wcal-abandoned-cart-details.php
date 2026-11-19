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
				if ( $prod_name && 'product' === $prod_name->post_type ) {

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
										$product_name_with_variable = $product_name_with_variable . html_entity_decode( $explode_many_varaition_value, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401 ) . '<br>';
									}
								} else {
									$product_name_with_variable = $product_name_with_variable . html_entity_decode( $explode_many_varaition_value, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401 ) . '<br>';
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
