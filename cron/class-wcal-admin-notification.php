<?php
/**
 * This file will send the admin notification for cart abandonment.
 *
 * @author  Tyche Softwares
 * @package Abandoned-Cart-Cart/AdminNotification
 * @since 8.21.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Wcal_Admin_Notification' ) ) {

	/**
	 * Admin Email Notification.
	 */
	class Wcal_Admin_Notification {

		/**
		 * Contructor.
		 */
		public function __construct() {
			add_action( 'wcap_send_admin_notification', array( &$this, 'wcap_send_admin_email' ), 10, 1 );
			add_action( 'plugins_loaded', array( $this, 'wcap_load_email_file' ) );
		}

		/**
		 * Load WC Email Class file
		 *
		 * @since 6.7.0
		 */
		public function wcap_load_email_file() {
			require_once WP_PLUGIN_DIR . '/woocommerce/includes/emails/class-wc-email.php';
		}

		/**
		 * Process and send the email.
		 *
		 * @param int $cart_id - Cart ID.
		 *
		 * @since 6.7.0
		 */
		public function wcap_send_admin_email( $cart_id ) {

			$admin_notification_status = get_option( 'wcap_email_admin_on_abandonment', '' );

			if ( $cart_id > 0 && 'on' === $admin_notification_status ) {
				$cart_history = wcal_get_data_cart_history( $cart_id );

				if ( $cart_history && ( isset( $cart_history->abandoned_cart_info ) && '{"cart":[]}' !== $cart_history->abandoned_cart_info && '[]' !== $cart_history->abandoned_cart_info ) ) {

					if ( isset( $cart_history->recovered_cart, $cart_history->cart_ignored ) && '0' == $cart_history->recovered_cart && '1' != $cart_history->cart_ignored ) { // phpcs:ignore
						$user_id   = $cart_history->user_id;
						$user_type = $cart_history->user_type;

						$billing_first_name = '';
						$billing_last_name  = '';
						$email_id           = '';
						$phone              = '';
						$cut_off            = 0;

						if ( $user_id >= 63000000 && 'GUEST' === $user_type ) {
							$guest_data = wcal_get_data_guest_history( $user_id );

							if ( $guest_data ) {
								$billing_first_name = $guest_data->billing_first_name;
								$billing_last_name  = $guest_data->billing_last_name;
								$email_id           = $guest_data->email_id;
								$phone              = $guest_data->phone;
								$cut_off            = is_numeric( get_option( 'ac_lite_cart_abandoned_time', 10 ) ) ? get_option( 'ac_lite_cart_abandoned_time', 10 ) : 10;
							}
						} elseif ( 'REGISTERED' === $user_type ) {
							$billing_first_name = get_user_meta( $user_id, 'billing_first_name', true );
							$billing_last_name  = get_user_meta( $user_id, 'billing_last_name', true );
							$email_id           = get_user_meta( $user_id, 'billing_email', true );
							$phone              = get_user_meta( $user_id, 'billing_phone', true );
							$cut_off            = is_numeric( get_option( 'ac_lite_cart_abandoned_time', 10 ) ) ? get_option( 'ac_lite_cart_abandoned_time', 10 ) : 10;
						}

						$email_id = apply_filters( 'wcal_cart_abandoned_alter_email_id', $email_id );

						// At the minimum a phone number or email address should've been captured.
						if ( '' !== $email_id || '' !== $phone ) {

							// The current time should've exceeded the cutoff and the cart should be listed in the Abandoned Orders page.
							$abandoned_time         = $cart_history->abandoned_cart_time;
							$time_since_abandonment = $abandoned_time + ( $cut_off * 60 );
							$current_time           = current_time( 'timestamp' ); // phpcs:ignore

							if ( $current_time >= $time_since_abandonment ) {

								$cart_details = self::wcap_get_cart_details( $cart_id, $cart_history );

								if ( $cart_details ) {

									$customer_details            = new StdClass();
									$customer_details->firstname = $billing_first_name;
									$customer_details->lastname  = $billing_last_name;
									$customer_details->phone     = $phone;
									$customer_details->email     = $email_id;
									// Add the generic details.
									$blogname   = get_option( 'blogname' );
									$from_email = get_option( 'admin_email' );

									$admin_list = $from_email;

									$subject = self::wcap_get_email_subject();
									$headers = self::wcap_get_email_headers( $blogname, $from_email );

									$heading = self::wcap_get_email_heading();

									$email_body_template_header = self::wcap_get_wc_header( $heading );
									$email_body_template_footer = self::wcap_get_wc_footer( $blogname );

									$cart_link = esc_url( admin_url() ) . 'admin.php?page=woocommerce_ac_page&action=listcart';

									ob_start();
									wc_get_template(
										'abandonment-notification.php',
										array(
											'blog_name'        => $blogname,
											'cart_details'     => $cart_details,
											'customer_details' => $customer_details,
											'cart_link'        => $cart_link,
										),
										'woocommerce-abandon-cart/',
										WCAL_PLUGIN_PATH . '/includes/templates/emails/'
									);
									$email_body = ob_get_clean();

									$message = self::wcap_apply_wc_email_style( $email_body_template_header . $email_body . $email_body_template_footer );
									self::wcap_send_email( $admin_list, $subject, $message, $headers );

								}
							} else {
								$notification_buffer = apply_filters( 'wcap_admin_notification_buffer', 0 );
								$cut_off            += (int) $notification_buffer;

								$cut_off = $cut_off * 60; // convert to seconds.
								$cart_id = (int) $cart_id;
								if ( $cut_off > 0 ) {
									// Run the hook.
									as_schedule_single_action( time() + $cut_off, 'wcap_send_admin_notification', array( 'id' => $cart_id ) );
								}
							}
						}
					}
				}
			}

		}

		/**
		 * Returns formatted cart details.
		 *
		 * @param int $cart_id - Cart ID.
		 * @param obj $cart_history - Cart Details from DB.
		 * @return obj $cart_details - Formatted cart details.
		 *
		 * @since 9.3.0
		 */
		public static function wcap_get_cart_details( $cart_id, $cart_history ) {

			if ( $cart_id > 0 && $cart_history ) {

				$source_names = array(
					'atc'         => __( 'Add to Cart Popup Modal', 'woocommerce-ac' ),
					'exit_intent' => __( 'Exit Intent Popup Modal', 'woocommerce-ac' ),
					'checkout'    => __( 'Checkout Page, Guest', 'woocommerce-ac' ),
					'url'         => __( 'URL', 'woocommerce-ac' ),
					'custom_form' => __( 'Custom Form', 'woocommerce-ac' ),
				);
				$date_format  = get_option( 'date_format' );
				$time_format  = get_option( 'time_format' );

				// prepare the cart details.
				$abandoned_time = gmdate( "$date_format $time_format", $cart_history->abandoned_cart_time );

				// Product Details.
				$cart_data = json_decode( $cart_history->abandoned_cart_info );
				if ( isset( $cart_data ) && is_object( $cart_data ) && count( get_object_vars( $cart_data ) ) > 0 ) {
					$cart_captured = isset( $cart_data->captured_by ) ? $cart_data->captured_by : '';
				}
				$currency = isset( $cart_data->currency ) ? $cart_data->currency : '';

				$product_details = wcal_get_product_details( $cart_history->abandoned_cart_info, true );
				$totals_data     = self::wcap_get_cart_totals( $product_details, $cart_data );

				if ( is_array( $product_details ) && count( $product_details ) <= 0 ) {
					return false;
				}
				if ( isset( $cart_history->user_id ) && $cart_history->user_id > 0 && $cart_history->user_id < 63000000 ) {
					global $wp_roles;

					$user       = get_userdata( $cart_history->user_id );
					$user_roles = $user->roles[0]; // Get all the user roles for this user as an array.
					$role_name  = $wp_roles->roles[ $user_roles ]['name'];

					// translators: User role name.
					$cart_source = sprintf( __( 'Product Page, %s', 'woocommerce-ac' ), $role_name );
				} elseif ( isset( $cart_history->user_id ) && $cart_history->user_id > 0 && $cart_history->user_id >= 63000000 ) {

					if ( isset( $cart_captured ) ) {
						$cart_source = isset( $source_names[ $cart_captured ] ) ? $source_names[ $cart_captured ] : '';
					}
				}
				$cart_details                 = new StdClass();
				$cart_details->id             = $cart_id;
				$cart_details->abandoned_time = isset( $abandoned_time ) ? $abandoned_time : '';
				$cart_details->source         = isset( $cart_source ) ? $cart_source : '';
				$cart_details->product_data   = $product_details;
				$cart_details->totals_data    = $totals_data;
				$cart_details->currency       = $currency;

				return $cart_details;
			}
			return false;
		}

		/**
		 * Returns cart totals for email.
		 *
		 * @param array $product_details - Product List Details.
		 * @param obj   $cart_data - Cart Data.
		 * @return array $totals_array - Formatted totals array.
		 *
		 * @since 9.3.0
		 */
		public static function wcap_get_cart_totals( $product_details, $cart_data ) {

			$totals_array = array();
			$tax_total    = 0;
			$cart_total   = 0;

			if ( is_array( $product_details ) && count( $product_details ) > 0 ) {
				foreach ( $product_details as $details ) {
					$tax_total  += isset( $details->line_tax ) && $details->line_tax > 0 ? $details->line_tax : 0;
					$cart_total += isset( $details->line_subtotal ) && $details->line_subtotal > 0 ? $details->line_subtotal : 0;
				}
				if ( isset( $cart_data->cart_totals ) && isset( $cart_data->cart_totals->total_tax ) ) { // override tax total if it is saved directly in the record.
					$tax_total = $cart_data->cart_totals->total_tax;
				}
			}
			if ( $tax_total > 0 ) {
				$cart_total += $tax_total;

				$totals_array[] = array(
					'label' => __( 'Tax', 'woocommerce-ac' ),
					'value' => $tax_total,
				);
			}

			if ( isset( $cart_data->shipping_charges ) && $cart_data->shipping_charges > 0 ) {
				$cart_total    += $cart_data->shipping_charges;
				$totals_array[] = array(
					'label' => __( 'Shipping', 'woocommerce-ac' ),
					'value' => $cart_data->shipping_charges,
				);
			}

			if ( $cart_total > 0 ) {
				$totals_array[] = array(
					'label' => __( 'Total', 'woocommerce-ac' ),
					'value' => $cart_total,
				);
			}
			return $totals_array;

		}

		/**
		 * Returns list of email addresses.
		 *
		 * @param string $send_list - Send List from DB.
		 * @return string $final_list - Formatted list of addresses.
		 *
		 * @since 9.3.0
		 */
		public static function wcap_get_send_to_list( $send_list ) {

			$comma_separated = stripos( $send_list, ',' );
			$line_separated  = stripos( $send_list, ' ' );

			if ( $comma_separated > 0 ) {
				$final_list = '';
				$send_list  = explode( ',', $send_list );
				foreach ( $send_list as $addr ) {
					$final_list .= trim( $addr ) . ',';
				}
			} elseif ( $line_separated > 0 ) {
				$final_list = '';
				$send_list  = explode( ' ', $send_list );
				foreach ( $send_list as $addr ) {
					$final_list .= trim( $addr ) . ',';
				}
			} else {
				$final_list = $send_list;
			}

			return $final_list;
		}

		/**
		 * Return Email subject.
		 *
		 * @since 9.3.0
		 */
		public static function wcap_get_email_subject() {

			$subject = apply_filters( 'wcap_admin_notification_subject', __( 'See who has abandoned a cart on your store !', 'woocommerce-ac' ) );
			return $subject;
		}

		/**
		 * Return Email Heading.
		 *
		 * @since 9.3.0
		 */
		public static function wcap_get_email_heading() {

			$heading = apply_filters( 'wcap_admin_notification_heading', __( 'Your missed sales detail', 'woocommerce-ac' ) );
			return $heading;
		}

		/**
		 * Return Email Headers.
		 *
		 * @param string $blogname - Blog name.
		 * @param string $from_email - From email address.
		 * @return string $headers - Email Headers.
		 *
		 * @since 9.3.0
		 */
		public static function wcap_get_email_headers( $blogname, $from_email ) {
			$headers  = "From: " . $blogname . " <" . $from_email . ">" . "\r\n"; // phpcs:ignore
			$headers .= "Content-Type: text/html" . "\r\n"; // phpcs:ignore

			return $headers;
		}

		/**
		 * Return WC email header.
		 *
		 * @param string $heading - Email heading.
		 * @return string - Formatted WC email heading.
		 *
		 * @since 9.3.0
		 */
		public static function wcap_get_wc_header( $heading ) {
			ob_start();
			wc_get_template( 'emails/email-header.php', array( 'email_heading' => $heading ) );
			$email_body_template_header = ob_get_clean();

			return $email_body_template_header;
		}

		/**
		 * Return WC email footer.
		 *
		 * @param string $blogname - Blog name.
		 * @return string - Formatted WC email footer.
		 *
		 * @since 9.3.0
		 */
		public static function wcap_get_wc_footer( $blogname ) {
			ob_start();
			wc_get_template( 'emails/email-footer.php' );
			$email_body_template_footer = ob_get_clean();
			$email_body_template_footer = str_ireplace( '{site_title}', $blogname, $email_body_template_footer );

			return $email_body_template_footer;
		}

		/**
		 * Style email using WC inline styles.
		 *
		 * @param string $message - Email content.
		 * @return string $message - formatted email content.
		 *
		 * @since 9.3.0
		 */
		public static function wcap_apply_wc_email_style( $message ) {
			$email   = new WC_Email();
			$message = apply_filters( 'woocommerce_mail_content', $email->style_inline( $message ) );

			return $message;
		}

		/**
		 * Sends the email.
		 *
		 * @param string $admin_list - Email address to send to.
		 * @param string $subject - Email subject.
		 * @param string $message - Email content.
		 * @param string $headers - Email headers.
		 *
		 * @since 9.3.0
		 */
		public static function wcap_send_email( $admin_list, $subject, $message, $headers ) {
			wp_mail( $admin_list, $subject, stripslashes( $message ), $headers );
		}
	}
}

return new Wcal_Admin_Notification();
