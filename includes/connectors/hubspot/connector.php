<?php
/**
 * Hubspot Connector file
 *
 * @package  Abandoned-Cart-Pro-for-WooCommerce/Connectors/Hubspot
 */

/**
 * Class for Hubspot Connector
 */
class Wcap_Hubspot extends Wcap_Connector {

	/**
	 * Connector Name
	 *
	 * @var $connector_name
	 */
	public $connector_name = 'hubspot';
	/**
	 * Slug Name
	 *
	 * @var $slug
	 */
	public $slug = 'wcap_hubspot';
	/**
	 * Name
	 *
	 * @var $name
	 */
	public $name = 'HubSpot';
	/**
	 * Description
	 *
	 * @var $desc
	 */
	public $desc = 'Send emails and abandoned carts collected from the plugin to HubSpot.';
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
	public static $api_end_point = 'https://api.hubapi.com/';
	/**
	 * API Call Headers.
	 *
	 * @var $headers
	 */
	public static $headers = null;
	/**
	 * Property Group ID.
	 *
	 * @var $property_grp_id
	 */
	public $property_grp_id = null;
	/**
	 * Properties.
	 *
	 * @var $properties.
	 */
	public $properties = null;
	/**
	 * Lists.
	 *
	 * @var $lists
	 */
	public $lists = null;
	/**
	 * Workflows.
	 *
	 * @var $workflows
	 */
	public $workflows = null;

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
		$this->connector_url = WCAP_HUBSPOT_PLUGIN_URL;
		add_filter( 'wcap_connectors_loaded', array( $this, 'add_card' ) );
	}

	/**
	 * Function to define constants.
	 */
	public function wcap_define_plugin_properties() {
		if ( ! defined( 'WCAP_HUBSPOT_VERSION' ) ) {
			define( 'WCAP_HUBSPOT_VERSION', '1.0.0' );
		}
		if ( ! defined( 'WCAP_HUBSPOT_FULL_NAME' ) ) {
			define( 'WCAP_HUBSPOT_FULL_NAME', 'Abandoned Carts Automations Connectors: Hubspot' );
		}
		if ( ! defined( 'WCAP_HUBSPOT_PLUGIN_FILE' ) ) {
			define( 'WCAP_HUBSPOT_PLUGIN_FILE', __FILE__ );
		}
		if ( ! defined( 'WCAP_HUBSPOT_PLUGIN_DIR' ) ) {
			define( 'WCAP_HUBSPOT_PLUGIN_DIR', __DIR__ );
		}
		if ( ! defined( 'WCAP_HUBSPOT_PLUGIN_URL' ) ) {
			define( 'WCAP_HUBSPOT_PLUGIN_URL', untrailingslashit( plugin_dir_url( WCAP_HUBSPOT_PLUGIN_FILE ) ) );
		}
		if ( ! defined( 'WCAP_HUBSPOT_PLUGIN_BASENAME' ) ) {
			define( 'WCAP_HUBSPOT_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
		}
	}

	/**
	 * Function to Add card in connector's main page.
	 *
	 * @param array $available_connectors - Avaialble connector for display in main connector page.
	 */
	public function add_card( $available_connectors ) {
		$available_connectors['wcap']['connectors']['wcap_hubspot'] = array(
			'name'            => $this->name,
			'desc'            => __( $this->desc, 'woocommerce-ac' ), // phpcs:ignore
			'connector_class' => 'Wcap_Hubspot',
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
