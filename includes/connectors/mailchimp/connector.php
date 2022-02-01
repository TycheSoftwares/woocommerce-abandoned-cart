<?php
/**
 * Mailchimp Connector file
 *
 * @package  Abandoned-Cart-Pro-for-WooCommerce/Connectors/Hubspot
 */

/**
 * Class for Mailchimp Connector
 */
class Wcap_Mailchimp extends Wcap_Connector {

	/**
	 * Connector Name
	 *
	 * @var $connector_name
	 */
	public $connector_name = 'mailchimp';
	/**
	 * Slug Name
	 *
	 * @var $slug
	 */
	public $slug = 'wcap_mailchimp';
	/**
	 * Name
	 *
	 * @var $name
	 */
	public $name = 'Mailchimp';
	/**
	 * Description
	 *
	 * @var $desc
	 */
	public $desc = 'Send emails and abandoned carts collected from the plugin to Mailchimp.';
	/**
	 * Single instance of the class
	 *
	 * @var $ins
	 */
	private static $ins = null;
	/**
	 * API Endpoint.
	 *
	 * @var $api_end_point
	 */
	public static $api_end_point = 'https://<dc>.api.mailchimp.com/';
	/**
	 * API Call Headers.
	 *
	 * @var $headers
	 */
	public static $headers = null;
	/**
	 * All calls with object
	 *
	 * @var $registered_calls
	 */
	public $registered_calls = array();

	/**
	 * Construct.
	 */
	public function __construct() {
		$this->wcap_define_plugin_properties();
		$this->connector_url = WCAP_MAILCHIMP_PLUGIN_URL;
		add_filter( 'wcap_connectors_loaded', array( $this, 'add_card' ) );
	}

	/**
	 * Function to define constants.
	 */
	public function wcap_define_plugin_properties() {
		if ( ! defined( 'WCAP_MAILCHIMP_VERSION' ) ) {
			define( 'WCAP_MAILCHIMP_VERSION', '1.0.0' );
		}
		if ( ! defined( 'WCAP_MAILCHIMP_FULL_NAME' ) ) {
			define( 'WCAP_MAILCHIMP_FULL_NAME', 'Abandoned Carts Automations Connectors: Mailchimp' );
		}
		if ( ! defined( 'WCAP_MAILCHIMP_PLUGIN_FILE' ) ) {
			define( 'WCAP_MAILCHIMP_PLUGIN_FILE', __FILE__ );
		}
		if ( ! defined( 'WCAP_MAILCHIMP_PLUGIN_DIR' ) ) {
			define( 'WCAP_MAILCHIMP_PLUGIN_DIR', __DIR__ );
		}
		if ( ! defined( 'WCAP_MAILCHIMP_PLUGIN_URL' ) ) {
			define( 'WCAP_MAILCHIMP_PLUGIN_URL', untrailingslashit( plugin_dir_url( WCAP_MAILCHIMP_PLUGIN_FILE ) ) );
		}
		if ( ! defined( 'WCAP_MAILCHIMP_PLUGIN_BASENAME' ) ) {
			define( 'WCAP_MAILCHIMP_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
		}
	}

	/**
	 * Function to Add card in connector's main page.
	 *
	 * @param array $available_connectors - Avaialble connector for display in main connector page.
	 */
	public function add_card( $available_connectors ) {
		$available_connectors['wcap']['connectors']['wcap_mailchimp'] = array(
			'name'            => $this->name,
			'desc'            => __( $this->desc, 'woocommerce-ac' ), // phpcs:ignore
			'connector_class' => 'Wcap_Mailchimp',
			'image'           => $this->get_image(),
			'source'          => '',
			'file'            => '',
		);

		return $available_connectors;
	}

	/**
	 * Function to get instance.
	 */
	public static function get_instance() {
		if ( null === self::$ins ) {
			self::$ins = new self();
		}

		return self::$ins;
	}

}
