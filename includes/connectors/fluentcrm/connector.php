<?php
/**
 * FluentCRM connector class
 *
 * @package  Abandoned-Cart-Pro-for-WooCommerce/Connectors/fluentcrm
 */

/**
 * Class for FluenctCRM Connector
 */
class Wcap_Fluentcrm extends Wcap_Connector {
	/**
	 * Connector Name
	 *
	 * @var $connector_name
	 */
	public $connector_name = 'fluentcrm';
	/**
	 * Slug Name
	 *
	 * @var $slug
	 */
	public $slug = 'wcap_fluentcrm';
	/**
	 * Name
	 *
	 * @var $name
	 */
	public $name = 'Fluentcrm';
	/**
	 * Description
	 *
	 * @var $desc
	 */
	public $desc = 'Send emails and abandoned carts collected from the plugin to Fluentcrm.';
	/**
	 * Signle instance of the class
	 *
	 * @var $ins
	 */
	private static $ins = null;
	/**
	 * Headers for Curl calls
	 *
	 * @var $headers
	 */
	public static $headers = null;

	/**
	 * Array of events
	 *
	 *  @var array All calls with object
	 */
	public $events = array( 'Created Cart', 'Modifed Cart', 'Ignored Cart', 'Recovered Cart', 'Order Placed' );
	/**
	 * Array of registered calls for function
	 *
	 *  @var array All calls with object
	 */
	public $registered_calls = array();
	/**
	 * Construct. Add hooks and filters.
	 *
	 * @var array All calls with object.
	 */
	public function __construct() {

		if ( strstr( $_SERVER['REQUEST_URI'], 'wp-json' ) ) { //phpcs:ignore
			return;
		}
		$this->wcap_define_plugin_properties();
		$this->connector_url = WCAP_FLUENTCRM_PLUGIN_URL;
		add_filter( 'wcap_connectors_loaded', array( $this, 'add_card' ) );
	}

	/**
	 * Function to define constans
	 */
	public function wcap_define_plugin_properties() {
		if ( ! defined( 'WCAP_FLUENTCRM_VERSION' ) ) {
			define( 'WCAP_FLUENTCRM_VERSION', '1.0.0' );
		}
		if ( ! defined( 'WCAP_FLUENTCRM_FULL_NAME' ) ) {
			define( 'WCAP_FLUENTCRM_FULL_NAME', 'Abandoned Carts Automations Connectors: FLUENTCRM' );
		}
		if ( ! defined( 'WCAP_FLUENTCRM_PLUGIN_FILE' ) ) {
			define( 'WCAP_FLUENTCRM_PLUGIN_FILE', __FILE__ );
		}
		if ( ! defined( 'WCAP_FLUENTCRM_PLUGIN_DIR' ) ) {
			define( 'WCAP_FLUENTCRM_PLUGIN_DIR', __DIR__ );
		}
		if ( ! defined( 'WCAP_FLUENTCRM_PLUGIN_URL' ) ) {
			define( 'WCAP_FLUENTCRM_PLUGIN_URL', untrailingslashit( plugin_dir_url( WCAP_FLUENTCRM_PLUGIN_FILE ) ) );
		}
		if ( ! defined( 'WCAP_FLUENTCRM_PLUGIN_BASENAME' ) ) {
			define( 'WCAP_FLUENTCRM_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
		}
	}

	/**
	 * Function to Add card in connector's main page.
	 *
	 * @param array $available_connectors - Avaialble connector for display in main connector page.
	 */
	public function add_card( $available_connectors ) {
		$available_connectors['wcap']['connectors']['wcap_fluentcrm'] = array(
			'name'            => $this->name,
			'desc'            => __( $this->desc, 'woocommerce-ac' ), //phpcs:ignore
			'connector_class' => 'Wcap_Fluentcrm',
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
