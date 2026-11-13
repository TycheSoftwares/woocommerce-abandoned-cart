<?php
/**
 * REST endpoints for the Email Templates screen.
 *
 * Routes and envelope follow the Pro plugin's template controller. What gets
 * stored is what the PHP template form of this plugin stored.
 *
 * @package Abandoned-Cart-Lite-for-WooCommerce/Admin/API
 * @since 6.10.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WCAL_REST_Templates_Controller class.
 */
class WCAL_REST_Templates_Controller extends WCAL_REST_Settings_Controller {

	/**
	 * Register the routes.
	 */
	public static function register_routes() {
		register_rest_route(
			self::REST_NAMESPACE,
			'/templates/email',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( __CLASS__, 'get_templates' ),
					'permission_callback' => array( __CLASS__, 'get_read_permission' ),
					'args'                => array(
						'page'     => array(
							'type'    => 'integer',
							'default' => 1,
							'minimum' => 1,
						),
						'per_page' => array(
							'type'    => 'integer',
							'default' => 20,
							'minimum' => 1,
							'maximum' => 100,
						),
					),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( __CLASS__, 'create_template' ),
					'permission_callback' => array( __CLASS__, 'get_read_permission' ),
					'args'                => self::get_template_args(),
				),
			)
		);

		register_rest_route(
			self::REST_NAMESPACE,
			'/templates/email/(?P<id>\d+)',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( __CLASS__, 'get_template' ),
					'permission_callback' => array( __CLASS__, 'get_read_permission' ),
				),
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( __CLASS__, 'update_template' ),
					'permission_callback' => array( __CLASS__, 'get_read_permission' ),
					'args'                => self::get_template_args(),
				),
				array(
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => array( __CLASS__, 'delete_template' ),
					'permission_callback' => array( __CLASS__, 'get_read_permission' ),
				),
			)
		);

		register_rest_route(
			self::REST_NAMESPACE,
			'/templates/email/(?P<id>\d+)/status',
			array(
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => array( __CLASS__, 'update_status' ),
				'permission_callback' => array( __CLASS__, 'get_read_permission' ),
				'args'                => array(
					'is_active' => array(
						'type'     => 'boolean',
						'required' => true,
					),
				),
			)
		);
	}

	/**
	 * Arguments accepted when saving a template.
	 *
	 * @return array
	 */
	private static function get_template_args() {
		return array(
			'template_name'               => array( 'type' => 'string' ),
			'subject'                     => array( 'type' => 'string' ),
			'body'                        => array( 'type' => 'string' ),
			'frequency'                   => array(
				'type'    => 'integer',
				'minimum' => 1,
				'maximum' => 59,
			),
			'day_or_hour'                 => array(
				'type' => 'string',
				'enum' => array( 'Minutes', 'Hours', 'Days' ),
			),
			'is_wc_template'              => array( 'type' => 'boolean' ),
			'wc_email_header'             => array( 'type' => 'string' ),
			'coupon_code'                 => array(
				'type'    => 'string',
				'pattern' => '^[0-9,]*$',
			),
			'individual_use'              => array( 'type' => 'boolean' ),
			'generate_unique_coupon_code' => array( 'type' => 'boolean' ),
			'discount'                    => array( 'type' => 'string' ),
			'discount_type'               => array(
				'type' => 'string',
				'enum' => array( 'percent', 'fixed' ),
			),
			'discount_shipping'           => array( 'type' => 'boolean' ),
			'discount_expiry'             => array(
				'type'    => 'string',
				'pattern' => '^([0-9]*-(hours|days))?$',
			),
		);
	}

	/**
	 * Templates list, in the order the PHP screen listed them.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response
	 */
	public static function get_templates( $request ) {
		global $wpdb;

		$page     = (int) $request->get_param( 'page' );
		$per_page = (int) $request->get_param( 'per_page' );

		// phpcs:disable WordPress.DB.DirectDatabaseQuery
		$total = (int) $wpdb->get_var( 'SELECT COUNT(*) FROM `' . $wpdb->prefix . 'ac_email_templates_lite`' );
		$rows  = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT wpet.*, ( SELECT COUNT(*) FROM `' . $wpdb->prefix . 'ac_sent_history_lite` AS wpsh WHERE wpsh.template_id = wpet.id ) AS sent
				FROM `' . $wpdb->prefix . 'ac_email_templates_lite` AS wpet
				ORDER BY day_or_hour desc, frequency asc
				LIMIT %d OFFSET %d',
				$per_page,
				( $page - 1 ) * $per_page
			)
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery

		$data = array();
		foreach ( $rows as $row ) {
			$data[] = array(
				'id'            => (int) $row->id,
				'template_name' => $row->template_name,
				'frequency'     => (int) $row->frequency,
				'day_or_hour'   => $row->day_or_hour,
				'sent'          => (int) $row->sent,
				'is_active'     => '1' === (string) $row->is_active,
			);
		}

		return rest_ensure_response(
			array(
				'data' => $data,
				'meta' => array(
					'total'    => $total,
					'page'     => $page,
					'per_page' => $per_page,
					'pages'    => (int) max( 1, ceil( $total / $per_page ) ),
				),
			)
		);
	}

	/**
	 * Single template, for the form.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function get_template( $request ) {
		$row = self::get_row( (int) $request['id'] );
		if ( ! $row ) {
			return self::not_found();
		}
		return self::envelope( self::prepare_item( $row ) );
	}

	/**
	 * Create a template. New templates start inactive.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function create_template( $request ) {
		global $wpdb;

		$fields = self::prepare_fields( $request, null );
		$fields = array_merge(
			array(
				'email_type'       => 'abandoned_cart_email',
				'default_template' => 0,
			),
			$fields
		);

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		if ( ! $wpdb->insert( $wpdb->prefix . 'ac_email_templates_lite', $fields ) ) {
			return self::save_failed();
		}

		$response = self::envelope( self::prepare_item( self::get_row( (int) $wpdb->insert_id ) ) );
		$response->set_status( 201 );
		return $response;
	}

	/**
	 * Update a template. A parameter left out keeps its stored value.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function update_template( $request ) {
		global $wpdb;

		$id  = (int) $request['id'];
		$row = self::get_row( $id );
		if ( ! $row ) {
			return self::not_found();
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		if ( false === $wpdb->update( $wpdb->prefix . 'ac_email_templates_lite', self::prepare_fields( $request, $row ), array( 'id' => $id ) ) ) {
			return self::save_failed();
		}

		return self::envelope( self::prepare_item( self::get_row( $id ) ) );
	}

	/**
	 * Delete a template.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function delete_template( $request ) {
		global $wpdb;

		$id = (int) $request['id'];
		if ( ! self::get_row( $id ) ) {
			return self::not_found();
		}

		$wpdb->delete( $wpdb->prefix . 'ac_email_templates_lite', array( 'id' => $id ), array( '%d' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery

		return self::envelope( array( 'deleted' => true ) );
	}

	/**
	 * Start or stop sending a template.
	 *
	 * Only one template can be active per interval, so activating one
	 * deactivates the others sent after the same time.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function update_status( $request ) {
		global $wpdb;

		$id  = (int) $request['id'];
		$row = self::get_row( $id );
		if ( ! $row ) {
			return self::not_found();
		}

		// phpcs:disable WordPress.DB.DirectDatabaseQuery
		if ( $request->get_param( 'is_active' ) ) {
			$wpdb->query(
				$wpdb->prepare(
					'UPDATE `' . $wpdb->prefix . 'ac_email_templates_lite` SET is_active = %s WHERE frequency = %s AND day_or_hour = %s',
					'0',
					$row->frequency,
					$row->day_or_hour
				)
			);
			$active = '1';
			update_option( 'wcal_template_' . $id . '_time', current_time( 'timestamp' ) ); // phpcs:ignore WordPress.DateTime.CurrentTimeTimestamp.Requested
		} else {
			$active = '0';
		}

		$wpdb->query(
			$wpdb->prepare(
				'UPDATE `' . $wpdb->prefix . 'ac_email_templates_lite` SET is_active = %s WHERE id = %d',
				$active,
				$id
			)
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery

		return self::envelope( array( 'is_active' => '1' === $active ) );
	}

	/**
	 * Build the columns to store from a request.
	 *
	 * @param WP_REST_Request $request  Request.
	 * @param object|null     $existing The stored row when updating, null when creating.
	 * @return array Column values.
	 */
	private static function prepare_fields( $request, $existing ) {
		$fields = array();

		if ( ! $existing || $request->has_param( 'subject' ) ) {
			$fields['subject'] = self::encode_text( (string) $request->get_param( 'subject' ) );
		}

		if ( ! $existing || $request->has_param( 'body' ) ) {
			$body = trim( (string) $request->get_param( 'body' ) );

			// If the link merge tags are prefixed with http://|https:// please remove it.
			foreach ( array( '{{cart.link}}', '{{cart.unsubscribe}}', '{{shop.url}}' ) as $merge_tag ) {
				$body = str_ireplace( array( 'http://' . $merge_tag, 'https://' . $merge_tag ), $merge_tag, $body );
			}
			$fields['body'] = $body;
		}

		if ( ! $existing || $request->has_param( 'frequency' ) ) {
			$fields['frequency'] = $request->has_param( 'frequency' ) ? (int) $request->get_param( 'frequency' ) : 1;
		}

		if ( ! $existing || $request->has_param( 'day_or_hour' ) ) {
			$fields['day_or_hour'] = $request->has_param( 'day_or_hour' ) ? $request->get_param( 'day_or_hour' ) : 'Minutes';
		}

		if ( ! $existing || $request->has_param( 'template_name' ) ) {
			$fields['template_name'] = trim( sanitize_text_field( (string) $request->get_param( 'template_name' ) ) );
		}

		if ( ! $existing || $request->has_param( 'is_wc_template' ) ) {
			$fields['is_wc_template'] = $request->get_param( 'is_wc_template' ) ? '1' : '0';
		}

		if ( ! $existing || $request->has_param( 'wc_email_header' ) ) {
			$fields['wc_email_header'] = self::encode_text( (string) $request->get_param( 'wc_email_header' ) );
		}

		if ( ! $existing || $request->has_param( 'coupon_code' ) ) {
			$coupon_ids            = array_filter( array_map( 'absint', explode( ',', (string) $request->get_param( 'coupon_code' ) ) ) );
			$fields['coupon_code'] = implode( ',', $coupon_ids );
		}

		if ( ! $existing || $request->has_param( 'individual_use' ) ) {
			$fields['individual_use'] = $request->get_param( 'individual_use' ) ? '1' : '0';
		}

		if ( ! $existing || $request->has_param( 'generate_unique_coupon_code' ) ) {
			$fields['generate_unique_coupon_code'] = $request->get_param( 'generate_unique_coupon_code' ) ? '1' : '0';
		}

		if ( ! $existing || $request->has_param( 'discount' ) ) {
			$fields['discount'] = sanitize_text_field( (string) $request->get_param( 'discount' ) );
		}

		if ( ! $existing || $request->has_param( 'discount_type' ) ) {
			$fields['discount_type'] = $request->has_param( 'discount_type' ) ? $request->get_param( 'discount_type' ) : 'percent';
		}

		if ( ! $existing || $request->has_param( 'discount_shipping' ) ) {
			$fields['discount_shipping'] = $request->get_param( 'discount_shipping' ) ? 'yes' : 'off';
		}

		if ( ! $existing || $request->has_param( 'discount_expiry' ) ) {
			$fields['discount_expiry'] = (string) $request->get_param( 'discount_expiry' );
		}

		if ( $existing ) {
			$fields['default_template'] = '1' === (string) $existing->default_template ? 1 : 0;
		}

		return $fields;
	}

	/**
	 * Subject and header are stored encoded.
	 *
	 * @param string $value Text.
	 * @return string
	 */
	private static function encode_text( $value ) {
		return htmlspecialchars( sanitize_text_field( $value ), ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401 );
	}

	/**
	 * Stored encoded text as it should appear in a form field.
	 *
	 * @param string $value Stored text.
	 * @return string
	 */
	private static function decode_text( $value ) {
		return html_entity_decode( stripslashes( (string) $value ), ENT_QUOTES | ENT_HTML401, 'UTF-8' );
	}

	/**
	 * Fetch a template row.
	 *
	 * @param int $id Template ID.
	 * @return object|null
	 */
	private static function get_row( $id ) {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		return $wpdb->get_row(
			$wpdb->prepare(
				'SELECT * FROM `' . $wpdb->prefix . 'ac_email_templates_lite` WHERE id = %d',
				$id
			)
		);
	}

	/**
	 * Format a template row for the form.
	 *
	 * @param object $row Template row.
	 * @return array
	 */
	private static function prepare_item( $row ) {
		return array(
			'id'                          => (int) $row->id,
			'template_name'               => $row->template_name,
			'subject'                     => self::decode_text( $row->subject ),
			'body'                        => str_replace( 'My document title', '', stripslashes( (string) $row->body ) ),
			'frequency'                   => (int) $row->frequency,
			'day_or_hour'                 => $row->day_or_hour,
			'is_active'                   => '1' === (string) $row->is_active,
			'is_wc_template'              => '1' === (string) $row->is_wc_template,
			'wc_email_header'             => self::decode_text( $row->wc_email_header ),
			'coupon_code'                 => (string) $row->coupon_code,
			'individual_use'              => '1' === (string) $row->individual_use,
			'generate_unique_coupon_code' => '1' === (string) $row->generate_unique_coupon_code,
			'discount'                    => (string) $row->discount,
			'discount_type'               => $row->discount_type,
			'discount_shipping'           => 'yes' === $row->discount_shipping,
			'discount_expiry'             => (string) $row->discount_expiry,
		);
	}

	/**
	 * Missing template error.
	 *
	 * @return WP_Error
	 */
	private static function not_found() {
		return new WP_Error( 'wcal_template_not_found', __( 'Template not found.', 'woocommerce-abandoned-cart' ), array( 'status' => 404 ) );
	}

	/**
	 * Failed database write error.
	 *
	 * @return WP_Error
	 */
	private static function save_failed() {
		return new WP_Error( 'wcal_template_not_saved', __( 'The Email Template could not be saved.', 'woocommerce-abandoned-cart' ), array( 'status' => 500 ) );
	}
}
